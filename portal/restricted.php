<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	
	require('portal-header.php'); 
	
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
						<img src="images/navigation/ED1C2A/50x50/Warning.png">
						<h2>Access Denied.</h2>
						<h3><?php echo $deny_message; ?></h3>
					</div>	
				</div> <!-- /end of content_body -->	
			
			</div><!-- /.post -->
		</div><!-- /#content -->

 
<?php

	require('includes/footer.php'); 
?>