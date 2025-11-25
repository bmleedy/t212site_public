window.paypal
  .Buttons({
    style: {
      shape: "pill",
      layout: "vertical",
      color: "gold",
      label: "paypal",
    },
    message: {
      //todo: pass this from the page
      amount: 100,
    },

    // called when the button is clicked
    async createOrder() {
      try {
        //todo: /api/orders is where the php should return what the current order is.
        const response = await fetch("/api/create_order.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          // use the "body" param to optionally pass additional order information
          // like product ids and quantities
          cart_from_page = document.getElementById("cart_table");
          cart = [];
          //iterate over the table and stuff whatever's in the first 2 columns
          //  into the cart array.
          for (let i = 0; i < cart.rows.length; i++) {
            const row = cart.rows[i];
            cart[i] = {};
            cart[i]["id"] = row?.cells?.[0];
            cart[i]["quantity"] = row?.cells?.[1];
            // REMEMBER - these are actually unused
          }

          body: JSON.stringify(cart),
        });

        // this will either respond with the order ID, or null
        const orderData = await response.json();

        if (orderData.id) {
          // This is the highest order number for the user for this page.
          return orderData.id;
        }
        //
        const errorDetail = orderData?.details?.[0];
        const errorMessage = errorDetail
          ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
          : JSON.stringify(orderData);

        throw new Error(errorMessage);
      } catch (error) {
        console.error(error);
        resultMessage(`Could not initiate PayPal Checkout...<br><br>${error}`);
      }
    },

    // This gets initiated upon completion of the order.
    async onApprove(data, actions) {
      try {
        const response = await fetch(`/api/orders/${data.orderID}/capture`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
        });

        const orderData = await response.json();
        // Three cases to handle:
        //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
        //   (2) Other non-recoverable errors -> Show a failure message
        //   (3) Successful transaction -> Show confirmation or thank you message

        const errorDetail = orderData?.details?.[0];

        if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
          // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
          // recoverable state, per
          // https://developer.paypal.com/docs/checkout/standard/customize/handle-funding-failures/
          return actions.restart();
        } else if (errorDetail) {
          // (2) Other non-recoverable errors -> Show a failure message
          throw new Error(`${errorDetail.description} (${orderData.debug_id})`);
        } else if (!orderData.purchase_units) {
          throw new Error(JSON.stringify(orderData));
        } else {
          // (3) Successful transaction -> Show confirmation or thank you message
          // Or go to another URL:  actions.redirect('thank_you.html');
          //todo: add a call to the server here to log the order id.
          const transaction =
            orderData?.purchase_units?.[0]?.payments?.captures?.[0] ||
            orderData?.purchase_units?.[0]?.payments?.authorizations?.[0];
          resultMessage(
            `Transaction ${transaction.status}: ${transaction.id}<br>
          <br>See console for all available details`
          );
          console.log(
            "Capture result",
            orderData,
            JSON.stringify(orderData, null, 2)
          );
        }
      } catch (error) {
        console.error(error);
        resultMessage(
          `Sorry, your transaction could not be processed...<br><br>${error}`
        );
      }
    },
  })
  .render("#paypal-button-container");

// Example function to show a result to the user. Your site's UI library can be used instead.
function resultMessage(message) {
  const container = document.querySelector("#result-message");
  container.innerHTML = message;
}

async function updateCart(order_type) {
  size = document.getElementById("shirt_size").value;
  quantity = document.getElementById("shirt_quantity").value;
  //alert("hey, this works " + order_type+ " "+size + " " + quantity);

  // Now, call the server to put this in the DB, which will also update the total
  const response = await fetch("api/add_merch.php", {
    method: "post",
    headers: {
      "Content-Type": "application/json",
    },
    // use the "body" param to optionally pass additional order information
    // like product ids and quantities
    body: JSON.stringify({
      cart: [
        {
          //can user ID be fetched on the server side
          type: order_type,
          //unique ID of the type of product (one id per product)
          id: "tshirt2024_"+size,
          //todo: render this from the php page
          quantity: quantity,
          //todo: add event information based on the event
          rando_string: "hellow world",
        },
      ],
    }),
  });
  const result = await response.json();

// const reader = result.getReader();
// while(true) {
//   const {done, value} = await reader.read();
//   if (done) break;
//   console.log(value);
// }

  console.log(`card updates complete: ${JSON.stringify(result)}`);

  order_table_div = document.getElementById("order_info")
  tbl = document.createElement("table");
  tbl.id = "cart_table";
  tblbody = document.createElement("tbody");

  //create header row
  var row = document.createElement("tr");
  var cell = document.createElement("td");
  var celltext = document.createTextNode("Item"); // Item header
  cell.appendChild(celltext);
  row.appendChild(cell);
  var cell = document.createElement("td");
  var celltext = document.createTextNode("Qty");  // Qty header
  cell.appendChild(celltext);
  row.appendChild(cell);
  tblbody.appendChild(row);

  //create cells
  for (i=0; i<result.items.length; i++) {
    // construct each row
    var row = document.createElement("tr");
    var cell = document.createElement("td");
    var celltext = document.createTextNode(result.items[i].item_id); // Item header
    cell.appendChild(celltext);
    row.appendChild(cell);
    var cell = document.createElement("td");
    var celltext = document.createTextNode(result.items[i].item_quantity);  // Qty header
    cell.appendChild(celltext);
    row.appendChild(cell);
    tblbody.appendChild(row);
  }

  tbl.appendChild(tblbody);
  tbl.setAttribute("border", "2");
  order_table_div.replaceChildren(tbl); //nix anything else that was in that div

}
