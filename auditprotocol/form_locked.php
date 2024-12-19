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
	require('includes/users-functions.php');
	 

	$form_id = (int) trim($_REQUEST['id']);
	
	if(empty($form_id)){
		die("Form ID required.");
	}

	$dbh = la_connect_db();

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: restricted.php");
			exit;
		}
	}

	$query = "select form_name from ".LA_TABLE_PREFIX."forms where form_id=?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)){		
		if(!empty($row['form_name'])){		
			$form_name = htmlspecialchars($row['form_name']);
		}else{
			$form_name = 'Untitled Form (#'.$form_id.')';
		}	
	}

	//get lock information
	$query = "select 
					A.user_id,
					A.lock_date,
					B.user_fullname 
				from 
					".LA_TABLE_PREFIX."form_locks A left join ".LA_TABLE_PREFIX."users B on A.user_id=B.user_id 
				where 
					A.form_id=?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$lock_fullname = $row['user_fullname'];
	$lock_date     = la_short_relative_date($row['lock_date']); 

	require('includes/header.php');
	
?>


		<div id="content" class="full">
			<div class="post form_locked">
				<div class="content_header">
					<div class="content_header_title">
						<div style="float: left">
							<h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> Form Locked for Editing</h2>
							<p>Another user is currently <strong>editing</strong> this module.</p>
						</div>	
						
						<div style="clear: both; height: 1px"></div>
					</div>
					
				</div>
				<div class="content_body">
					<div id="form_locked_body">
						<img src="images/icons/106_red_48.png" />
						<h3 style="color: #80b638;margin-top: 20px;margin-bottom:5px">This module was locked by <?php echo htmlspecialchars($lock_fullname).' '.$lock_date; ?>.</h3>
						<p>If you are certain <?php echo htmlspecialchars($lock_fullname); ?> is no longer working in this module, you may unlock it to continue:</p>
						<p><a href="edit_form.php?id=<?php echo $form_id ?>&unlock=<?php echo time(); ?>" id="unlock_form" style="margin: 30px auto">Unlock Form</a></p>
						<h3>Important: Clicking unlock will <strong>discard any unsaved changes</strong> being made by <?php echo htmlspecialchars($lock_fullname); ?>.</h3>
					</div>	
				</div> <!-- /end of content_body -->	
			
			</div><!-- /.post -->
		</div><!-- /#content -->

 
<?php

	require('includes/footer.php'); 
?>
