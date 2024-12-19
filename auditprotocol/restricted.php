<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	require('includes/header.php'); 
	
	$deny_message = "You don't have permission to access this page.";
	if(!empty($_SESSION['LA_DENIED'])){
		$deny_message = $_SESSION['LA_DENIED'];
		$_SESSION['LA_DENIED'] = '';
	}
?>


		<div id="content" class="full">
			<div class="post access_denied">
				<div class="content_header">
					&nbsp;
				</div>
				<div class="content_body">
					<div id="access_denied_body">
						<img src="images/navigation/005499/50x50/Notice.png">
						<h2>Access Denied.</h2>
						<h3><?php echo $deny_message; ?></h3>
					</div>	
				</div> <!-- /end of content_body -->	
			
			</div><!-- /.post -->
		</div><!-- /#content -->

 
<?php

	require('includes/footer.php'); 
?>