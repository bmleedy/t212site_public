<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');

require 'connect.php';
$id = $_POST['id'];
$edit = $_POST['edit'];

$address1 = "";
$address2 = "";
$city = "";
$state = "";
$zip = "";
$family_id = "";

$query = "SELECT family_id FROM users WHERE user_id=" . $id;
$results = $mysqli->query($query);
if ($row = $results->fetch_assoc()) {
	$family_id = 1; //$row["family_id"];
	if ($family_id == null) {
		//skip		
	} else {
		$query = "SELECT * FROM families WHERE family_id=".$family_id;
		$results = $mysqli->query($query);
		if ($results->num_rows) {			
			if ($row = $results->fetch_assoc()) {
				$address1 = $row["address1"];
				$address2 = $row["address2"];
				$city = $row["city"];
				$state = $row["state"];
				$zip = $row["zip"];
			}
		}	
	}
	
}

if ($state=="") {
	$state="WA";
}

if ($edit) {
$addressData = '<input type="hidden" id="family_id" name="family_id" value="'.$family_id.'">';
$addressData = $addressData . '<label>Address</label><div class="row">';
$addressData = $addressData . '<div class="large-12 columns">';
$addressData = $addressData . '<input type="text" id="address1" name="address1" required value="'.$address1.'">';
$addressData = $addressData . '<input type="text" id="address2" name="address2" value="'.$address2.'">';
$addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
$addressData = $addressData . '<input type="text" id="city" name="city" required value="'.$city.'">';
$addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
$addressData = $addressData . getStateDDL($state);
$addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
$addressData = $addressData . '<input type="text" id="zip" name="zip" required value="'.$zip.'"></div></div>';
} else {
$addressData = $addressData . '<label>Address</label><div class="row">';
$addressData = $addressData . '<div class="large-12 columns">';
$addressData = $addressData . $address1;
$addressData = $addressData . $address2;
$addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
$addressData = $addressData . $city;
$addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
$addressData = $addressData . $state;
$addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
$addressData = $addressData . $zip. '</div></div>';
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
			$strDDL = $strDDL . '<option selected="selected" value="' . $abbrevs[$i] . '">' . $states[$i] ;
		} else{
			$strDDL = $strDDL . '<option value="' . $abbrevs[$i] . '">' . $states[$i] ;
		}
	}
	
	$strDDL = $strDDL . '</select>';
	return $strDDL;
}
?>