<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
?>
<br />

<div class='row'>
	<?php
		if ($login->isUserLoggedIn() == true) {
			require "includes/m_sidebar.html";
		} else {
			require "includes/sidebar.html";
		}
	?>
	<div class="large-9 columns">
		<div class="panel">
			<h3>Patrol Corners Sample Agenda</h3>
			<p>Patrol Name: ________________________________________</p>
			<br>
			<ol>
			  <li>Every Week Stuff
			   <ol type="A">
			    <li>Collect dues (notify scouts past due)</li>
			    <li>Find out how Patrol members are doing on their advancement</li>
			    <li>Ask for concerns and needs of Patrol</li>
			   </ol>
			  </li>
			  <li>PLC Information
			   <ol type="A">
			    <li>Week before - Get input from Patrol</li>
			    <li> Week of - Let Patrol know what happened</li>
			   </ol>
			  </li>
			  <li>Prepare For Troop Activities
			   <ol type="A">
			    <li>1 week before - Decide menu, find out who is going, assign Quartermaster, check on Patrol equipment</li>
			    <li>Week of - Confirm who is attending, that equipment is ready, everyone has permission slips, know when and where to be</li>
			   </ol>
			  </li>
			  <li>Patrol Goals
			   <ol type="A">
			    <li>Baden-Powell award, plan Patrol service project, plan Patrol special event, Patrol training (Merit Badge, advancement, Scout skills)</li>
			   </ol>
			  </li>
			  <li>Other Responsibilities
			   <ol type="A">
			    <li>Patrol Quartermaster's Report (how is the patrol equipment)</li>
			    <li>Remind Patrol - next time we're Service and Program Patrol</li>
			   </ol>
			  </li>
			</ol>
		</div>
	</div>
</div>

<?php require "includes/footer.html"; ?>
