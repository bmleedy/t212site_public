<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
// For adults, get MB info. For Scouts, get Rank, Patrol, Postion
// scoutData = patrol, rank, etc for scouts. For adults, it is allows them to choose their scout.
require 'connect.php';
$id = $_POST['id'];
$edit = $_POST['edit'];
$showEdit = $_POST['showEdit'];
$profile = 'yes';
$wm = $_POST['wm'];		// = 1 if scout is wm editing another scout. =0 for adults and if editing his own record
$userAdmin = $_POST['userAdmin'];
$query="SELECT user_type, user_last, family_id FROM users WHERE user_id=".$id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$user_type = $row['user_type'];
$user_last = $row['user_last'];
$family_id = $row["family_id"];
$address1 = "";
$address2 = "";
$city = "";
$state = "";
$zip = "";

if ($user_type=='Scout') {
	$query="SELECT * FROM scout_info WHERE user_id=".$id;
	$results = $mysqli->query($query);
	$row = $results->fetch_assoc();
	if( $row) { 
		$rank = getLabel('ranks',$row['rank_id'],$mysqli);
		$patrol = getLabel('patrols',$row['patrol_id'],$mysqli);
		$position = getLabel('leadership',$row['position_id'],$mysqli);
	} else {
		$profile = 'no';
	}
	if ($edit) { 
		$varRank = getDDL("ranks","rank",$rank,$mysqli);
		$varPatrol = getDDL("patrols","patrol",$patrol,$mysqli) ;
		$varPosition = getDDL("leadership","position",$position,$mysqli) ;
		if ($userAdmin==1) {
			$varLock = ' ' ;
		} else {
			$varLock = 'disabled';
		}
	} else {
		$varRank = '<p>'. $rank . '</p>';
		$varPatrol = '<p>'. $patrol . '</p>';
		$varPosition = '<p>'. $position . '</p>';
	}
	
	$scoutData = '<div class="row"><div class="large-4 columns"><label>Rank</label>' . $varRank . '</div>';
	$scoutData = $scoutData . '<div class="large-4 columns"><label>Patrol</label>' . $varPatrol . '</div>';
	$scoutData = $scoutData . '<div class="large-4 columns"><label>Leadership (If Applicable)</label>' . $varPosition . '</div>';
	$scoutData = $scoutData . '</div>';
	$scoutData = $scoutData . '<div class="large-4 columns"></div></div>';
	
	
/************ Adult Merit Badge Info / Family Info / Address *******************************/
} else {
	/***** Get Scout Info ***********/
	$query="SELECT user_first, user_last FROM users WHERE family_id=".$family_id . " AND user_type='Scout'";
	$results = $mysqli->query($query);
	$scoutData = '<div class="row"><div class="large-6 columns"><label>My Scouts</label><p>';
	while ($row = $results->fetch_assoc()) {
		$scoutData = $scoutData . $row['user_first'].' ' . $row['user_last'] . '<br>';
	}
	$scoutData = $scoutData . '</p></div>';

	/***** Get Address Info ***********/	
	$query = "SELECT * FROM families WHERE family_id=".$family_id;
	$results = $mysqli->query($query);
	if ($results == false) {
            error_log("problem reading from the families DB. Does the DB exist?\n",0);
        }
	if ($results->num_rows) {			
		if ($row = $results->fetch_assoc()) {
			$address1 = $row["address1"];
			$address2 = $row["address2"];
			$city = $row["city"];
			$state = $row["state"];
			$zip = $row["zip"];
			if ($state=="") { $state="WA";}
		}
	}
	/** if read mode, append address & scout data so they appear side by side. blank addressdata when done so it's not displayed**
	** if edit mode, need to terminate scoutData with a closing </div> **/
	if ($edit) {
		$addressData = '<input type="hidden" id="family_id" name="family_id" value="'.$family_id.'">';
		$addressData = $addressData . '<div class="row"><div class="large-12 columns"><label>Address</label>';
		$addressData = $addressData . '<input type="text" id="address1" name="address1" required value="'.$address1.'">';
		$addressData = $addressData . '<input type="text" id="address2" name="address2" value="'.$address2.'">';
		$addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
		$addressData = $addressData . '<input type="text" id="city" name="city" required value="'.$city.'">';
		$addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
		$addressData = $addressData . getStateDDL($state);
		$addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
		$addressData = $addressData . '<input type="text" id="zip" name="zip" required value="'.$zip.'"></div></div>';
		$scoutData = $scoutData . "</div>";
	} else {
		$addressData = '<div class="large-6 columns"><label>Address</label><p>';
		$addressData = $addressData . $address1 . "<br>" ;
		if ($address2 !="") { $addressData = $addressData . $address2 . "<br>"; }
		$addressData = $addressData . $city . ", " . $state . " " . $zip . '</p></div></div>';
		$scoutData = $scoutData . $addressData;
		$addressData = "";
	}

	/***** Get Merit Badge Info ***********/	
	$query="SELECT * FROM mb_counselors WHERE user_id=".$id;
	$results = $mysqli->query($query);
	if ($results == false) {
            error_log("problem reading from the mb_counselors DB. Does the DB exist?\n",0);
        }
        $mb_list = array();
	while ($row = $results->fetch_assoc()) {
		array_push($mb_list , $row['mb_id']);
	}
	$query="SELECT * FROM mb_list Order by mb_name";
	$results = $mysqli->query($query);
	if ($results == false) {
            error_log("problem reading from the mb_list DB. Does the DB exist?\n",0);
        }
	$mbData = '';
	if ($edit && !$wm) {
		$mbData = $mbData . '<p>Please check all which you can be a Counselor for.</p>';
	} else {
		$mbData = $mbData . '<p>Counselor for the following Merit Badges:</p>'; 
	}
	while ($row = $results->fetch_assoc()) {
		if (in_array($row['id'], $mb_list)) {
			$isChecked = "CHECKED";
			$temp = $row['mb_name']."<br>";
		} else {
			$isChecked = "";
			$temp = "";
		}
		if ($edit && !$wm) {
			$mbData = $mbData . "<div class='large-4 columns'><input ".$isChecked." type='checkbox' name='mb' class='mbCheckbox' value='".
			$row['id']."'/> " . $row['mb_name'] . "</div>";
		} else {
			$mbData = $mbData . $temp;
		}
	}
	if ($edit) { 
		$mbData =  $mbData . '<div class="clearfix"></div>'; 
	} else {
		$mbData =  $mbData . '</p>'; 
	}
	
}

$returnMsg = array(
	'profile' => $profile,
	'user_type' => $user_type, 
	'scoutData' => $scoutData,
	'addressData' => $addressData,
	'mbData' => $mbData
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

function getLabel($strTable,$id,$mysqli){
	if ($id) {
		$query = 'SELECT label FROM '.$strTable.' WHERE id='.$id;
		$results = $mysqli->query($query);
	        if ($results == false) {
                    error_log("problem reading from the strtable DB. Does the DB exist?\n",0);
                }
		$row = $results->fetch_assoc();
		return $row['label'];
	} else {
		return "";
	}
}	

function getDDL($strTable,$strSelect,$strDefault,$mysqli) {
	$query = 'SELECT * FROM '.$strTable.' ORDER BY sort';
	$results = $mysqli->query($query);
	if ($results == false) {
           error_log("problem reading from the strtable DB. Does the DB exist?\n",0);
        }
	$returnDDL = '<select id="'.$strSelect.'"><option value="">-Select-</option>';
	
	while ($row = $results->fetch_assoc()) {
		$label = $row['label'];
		$id = $row['id'];
		if ($strDefault==$label) {
			$sel = " SELECTED ";
		} else {
			$sel = "" ;
		}
		$returnDDL = $returnDDL . '<option value="'.$id.'" '.$sel.'>'.$label.'</option>';
	}
	$returnDDL = $returnDDL . '</select>' ;
	return $returnDDL ;
}

function getScoutDDL($mysqli,$user_last) {
	$query = "SELECT user_first, user_last, user_id FROM users WHERE user_type='Scout' AND user_last='".$user_last."' ORDER BY user_last, user_first" ;
	$results = $mysqli->query($query);
	if ($results == false) {
           error_log("problem reading from the users DB. Does the DB exist?\n",0);
        }
	$returnDDL = "";
	
	while ($row = $results->fetch_assoc()) {
		$label = $row['user_last'] . ", " . $row['user_first'];
		$scout_id = $row['user_id'];
		$returnDDL = $returnDDL . '<option value="'.$scout_id.'">'.$label.'</option>';
	}
	$returnDDL = $returnDDL . '</select>' ;
	return $returnDDL ;
}
?>
