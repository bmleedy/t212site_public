<!DOCTYPE html>

<?php 

require "includes/header.html";
// if the "logoff" parameter is set, then clear the session so user
//  will need to log in again 
require_once( 'login/inc_login.php');
// if (isset($_GET['logout'])) {
//  //$_SESSION = array();  // empty the variables
//  //session_destroy();    // end the session so the start must be called again
// }

?>
<br />
<div class="row">
  <div class="large-6 columns">
    <img src="/images/Gig-Harbor.jpg" style ="height:200px; width:550px; margin:0px 0 0 0;" >
  </div>
  <div class="large-6 columns">
    <div class="panel">
      <h4>Welcome!</h4>
      <p>Troop 212 is an active Scout Led Troop which meets every Tuesday at the Gig Harbor Elks Lodge, 7-8:30PM. Each week the boys meet to work on Scout skills, listen to guest speakers, develop their leadership skills, play team games and more.</p>
    </div>
  </div>
</div>

<div class="row">


<!--
  <div class="large-12 columns">
    <div class="panel">
      <p>Troop 212 is excited to offer these high quality <a href="https://www.t212.org/WreathSales.php">Wreaths & Swags</a> from Collelo's of Port Orchard. If you would like to order any of these fine products, please contact your friendly Troop 212 Boy Scout. If you do not know any of our fine Scouts, please email <a href="mailto:Troop212_sm@googlegroups.com">Scoutmasters</a> and someone will assist you.</p>
    </div>
  </div>
-->


  <div class="large-12 columns">
    <div class="panel">
      <p>Our troop has a long history of service, dedication and honor.  
        So check out the links, read more about us and come join the fun!</p>
        <p>Scouting promises you the great outdoors. As a Scout, you can learn 
        how to camp and hike without leaving a trace and how to take care of 
        the land. You'll study wildlife up close and learn about nature all 
        around you. There are plenty of skills for you to master, and you can 
        teach others what you have learned.</P>
        <p>You can contact us by sending an email to our <a href="mailto:Troop212_sm@googlegroups.com">Scoutmasters</a>, or you can stop by 
        any Tuesday!</p>
    </div>
  </div>


  <a href="Calendar.php">
    <div class="large-3 columns">
      <div class="panel">
        <p><i class="fi-calendar" style="font-size:20px"></i> Troop Calendar</p>
      </div>
    </div>
  </a>

  <a href="https://www.facebook.com/Troop212">
    <div class="large-3 columns">
      <div class="panel">
        <p><i class="fi-social-facebook" style="font-size:20px"></i> Troop Photos</p>
      </div>
    </div>
  </a>

  <a href="Members.php">
    <div class="large-3 columns">
      <div class="panel">
        <p><i class="fi-mountains" style="font-size:20px"></i> Members</p>
      </div>
    </div>
  </a>

  <a href="Scoutmaster.php">
    <div class="large-3 columns">
      <div class="panel">
        <p><i class="fi-star" style="font-size:20px"></i> Scoutmaster</p>
      </div>
    </div>
  </a>
</div>


<?php require "includes/footer.html"; ?>
