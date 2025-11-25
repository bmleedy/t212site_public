// // Simple table sorting script (vanilla JS)
// document.addEventListener('DOMContentLoaded', function() {
//   document.querySelectorAll('table').forEach(function(table) {
//     // Add click event to each header
//     table.querySelectorAll('th').forEach(function(header, colIndex) {
//       header.style.cursor = 'pointer';
//       header.addEventListener('click', function() {
//         sortTable(table, colIndex);
//       });
//     });
//   });
// });

window.initSortableTables = function() {
  console.log('initSortableTables called');
  document.querySelectorAll('table').forEach(function(table) {
    // Prevent double-binding
    if (table.getAttribute('data-sortable')) return;
    table.setAttribute('data-sortable', 'true');
    table.querySelectorAll('th').forEach(function(header, colIndex) {
      header.style.cursor = 'pointer';
      header.addEventListener('click', function() {
        sortTable(table, colIndex);
      });
    });
  });
};

document.addEventListener('DOMContentLoaded', function() {
  window.initSortableTables();
});

function sortTable(table, col, reverse) {
  var rows = Array.from(table.rows).slice(1); // skip header
  reverse = table.getAttribute('data-sort-col') == col && table.getAttribute('data-sort-dir') == 'asc';
  rows.sort(function(a, b) {
    console.log('sorting here');
    var A = a.cells[col].innerText.trim();
    var B = b.cells[col].innerText.trim();
    var numA = parseFloat(A.replace(/[^0-9.-]+/g, ""));
    var numB = parseFloat(B.replace(/[^0-9.-]+/g, ""));
    if (!isNaN(numA) && !isNaN(numB)) {
      return reverse ? numB - numA : numA - numB;
    }
    return reverse ? B.localeCompare(A) : A.localeCompare(B);
  });
  rows.forEach(function(row) { table.tBodies[0].appendChild(row); });
  table.setAttribute('data-sort-col', col);
  table.setAttribute('data-sort-dir', reverse ? 'desc' : 'asc');
}

