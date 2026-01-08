<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Validate inputs
$id = validate_int_post('id', true);
$edit = validate_bool_post('edit', false);

// Authorization check - user can only view their own data unless they have permission
if ($id != $current_user_id) {
  require_user_access($id, $current_user_id);
}

$address1 = "";
$address2 = "";
$city = "";
$state = "";
$zip = "";
$family_id = "";

$stmt = $mysqli->prepare("SELECT family_id FROM users WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $family_id = $row["family_id"];
  if ($family_id == null) {
    //skip
  } else {
    $stmt2 = $mysqli->prepare("SELECT * FROM families WHERE family_id=?");
    $stmt2->bind_param("i", $family_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows) {
      if ($row2 = $result2->fetch_assoc()) {
        $address1 = $row2["address1"];
        $address2 = $row2["address2"];
        $city = $row2["city"];
        $state = $row2["state"];
        $zip = $row2["zip"];
      }
    }
    $stmt2->close();
  }
}
$stmt->close();

if ($state=="") {
  $state="WA";
}

if ($edit) {
$addressData = '<input type="hidden" id="family_id" name="family_id" value="'.escape_html($family_id).'">';
$addressData = $addressData . '<label>Address</label><div class="row">';
$addressData = $addressData . '<div class="large-12 columns">';
$addressData = $addressData . '<input type="text" id="address1" name="address1" required value="'.escape_html($address1).'">';
$addressData = $addressData . '<input type="text" id="address2" name="address2" value="'.escape_html($address2).'">';
$addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
$addressData = $addressData . '<input type="text" id="city" name="city" required value="'.escape_html($city).'">';
$addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
$addressData = $addressData . getStateDDL($state);
$addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
$addressData = $addressData . '<input type="text" id="zip" name="zip" required value="'.escape_html($zip).'"></div></div>';
} else {
$addressData = '<label>Address</label><div class="row">';
$addressData = $addressData . '<div class="large-12 columns">';
$addressData = $addressData . escape_html($address1) . '<br>';
$addressData = $addressData . escape_html($address2);
$addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
$addressData = $addressData . escape_html($city);
$addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
$addressData = $addressData . escape_html($state);
$addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
$addressData = $addressData . escape_html($zip). '</div></div>';
}

$returnData = 'success';
$returnMsg = array(
  'addressData' => $addressData
);

echo json_encode($returnMsg);
die;


/******************* Functions *****************/
function getStateDDL($strState) {

  $strDDL = '<select id="state" name="state">';

  $abbrevs = array("AL","AK","AZ","AR","CA","CO","CT","DE","DC","FL","GA","HI","ID","IL","IN","IA","KS","KY","LA","ME","MD","MA","MI","MN","MS","MO","MT","NE","NV","NH","NJ","NM","NY","NC","ND","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VT","VA","WA","WV","WI","WY");

  $states = array('Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','District Of Columbia','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming');

  for ($i = 0; $i <= 50; $i++){
    if ($abbrevs[$i]==$strState) {
      $strDDL = $strDDL . '<option selected="selected" value="' . escape_html($abbrevs[$i]) . '">' . escape_html($states[$i]) ;
    } else{
      $strDDL = $strDDL . '<option value="' . escape_html($abbrevs[$i]) . '">' . escape_html($states[$i]) ;
    }
  }

  $strDDL = $strDDL . '</select>';
  return $strDDL;
}
?>
