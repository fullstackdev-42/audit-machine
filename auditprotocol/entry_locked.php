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
	// require('includes/check-client-session-ask.php');
	require('includes/users-functions.php');
	require('includes/check-session.php');

	if(isset($_REQUEST['unlock'])){
		$dbh = la_connect_db();
		$form_id = (int) trim($_REQUEST['id']);
		$entry_id = (int) trim($_REQUEST['entry_id']);
		$user_id = (int) trim($_REQUEST['unlock']);
		$entity_id = (int) trim($_REQUEST['entity_id']);
		
		unlockForm(array('formUnlockedBy' => $_SESSION['la_user_id'], 'form_id' => $form_id, 'entity_user_id' => $user_id,'entity_id' => $entity_id, 'dbh' => $dbh));
		
		header("location:edit_entry.php?form_id={$form_id}&entry_id={$entry_id}");
		exit();
	}

	$form_id = (int) trim($_REQUEST['id']);
	$entry_id = (int) trim($_REQUEST['entry_id']);
	$user_id = (int) trim($_REQUEST['user_id']);
	$entity_id = (int) trim($_REQUEST['entity_id']);
	
	if(empty($form_id)){
		die("Form ID required.");
	}
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

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
	if( $_REQUEST['isAdmin'] == 1 ) {
		$user_type = 'Admin';
		$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `user_fullname` FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1' order by locked_id DESC limit 1";
		$sth = la_do_query($query,array($form_id, $user_id),$dbh);
		$row = la_do_fetch_result($sth);	

		$lock_fullname = $row['user_fullname'];
	} else {
		$user_type = 'Portal User';
		$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `full_name` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1' order by locked_id DESC limit 1";
		$sth = la_do_query($query,array($form_id, $user_id),$dbh);
		$row = la_do_fetch_result($sth);	

		$lock_fullname = $row['user_fullname'];
	}

	if( !empty($row) ){
	
		$locked_id     = (int)$row['locked_id'];
		$lock_date     = la_short_relative_date(date("Y-m-d H:i:s", $row['lockDateTime']));
	}
?>

<?php
	$load_custom_js = false;
	require('includes/header.php'); 
	
?>
<style>
.content_header_title{
	border-bottom: 0px dotted #CCCCCC !important;
}
.bootstrap-alert {
    position: relative;
    padding: .75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: .25rem;
}
.bootstrap-alert-warning {
	border: 2px solid #C2D7EF;
    border-radius: 5px;
    background-color: #eff6ff;
}
.bootstrap-alert-warning a {
	color: #3B699F;
	font-family: 'globerbold', Arial, helvetica, 'Helvetica Neue', Arial, 'Trebuchet MS', 'Lucida Grande';
	font-weight: bold;
}
</style>
	<div id="content" class="full">
  		<div class="post" style="padding: 0px;">

			<div id="form_locked_body">
				<div class="bootstrap-alert bootstrap-alert-warning" role="alert">
				<?php if( !empty($row) ){ ?>
				
				
			  		<h3 class="text-center">Read Only View</h3>
					<p>This module is currently locked by <strong>[<?=$user_type?>] <?php echo htmlspecialchars($lock_fullname).' on '.$lock_date; ?>.</strong></p>
					<p>If you are certain <?php echo htmlspecialchars($lock_fullname); ?> is no longer working in this module, you may unlock it to continue: <a href="entry_locked.php?id=<?= $form_id ?>&entry_id=<?=$entry_id?>&unlock=<?= $user_id ?>&entity_id=<?=$entity_id?>" id="unlock_form" style="margin: 30px auto">Unlock Form</a></p>
					<p>Important: Clicking unlock will <strong>discard any unsaved changes</strong> being made by <?php echo htmlspecialchars($lock_fullname); ?>.</p>
				

				<?php } else { ?>
					<p>Link expired - Please go to <a href="/auditprotocol/edit_entry.php?form_id=<?php echo $form_id ?>&entry_id=<?=$entry_id?>" style="color: #000;">this page</a> to check latest data.</p>
				<?php } ?>
				</div>
			</div>
		</div>
	</div>
 
<?php

	$disable_jquery_loading = true;	
	require('includes/footer.php'); 
?>
