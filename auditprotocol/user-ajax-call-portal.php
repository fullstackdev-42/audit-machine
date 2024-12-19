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
require('lib/swift-mailer/swift_required.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/filter-functions.php');
require('lib/password-hash.php');


//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$_POST = la_sanitize($_POST);
if(empty($_POST['mode'])){
	die("This file can't be opened directly.");
}
if(isset($_POST['mode']) && $_POST['mode'] == "suspend"){
	//get the user from the user table
	$query_get_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$sth_get_user = la_do_query($query_get_user, array(), $dbh);
	$row_get_user = la_do_fetch_result($sth_get_user);
	//get entity from the entity table
	$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
    $sth_entity = la_do_query($query_entity, array($row_get_user['client_id']), $dbh);
    $row_entity = la_do_fetch_result($sth_entity);

	$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `client_user_id`= ".$_POST['user_id'];
	la_do_query($query_update,array(':status' => 1),$dbh);

	//send suspend portal user notification
    if($la_settings['enable_registration_notification']){
		$login_user = $_SESSION['email'];
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		$subject = "Continuum GRC Account Management Alert";
		$content = "<h2>Continuum GRC Account Management Alert</h2>";
		$content .= "<h3>Administrative user ".$login_user." has suspended a portal user in ".$site_name.".</h3>";
		$content .= "<hr/>";
		$content .= "<h3>User Details:</h3>";
		$content .= "<table>";
		$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Phone</td><td style='width: 200px;'>Entity</td></tr>";
		$content .= "<tr><td style='width: 200px;'>{$row_get_user['client_user_id']}</td><td style='width: 200px;'>{$row_get_user['full_name']}</td><td style='width: 200px;'>{$row_get_user['username']}</td><td style='width: 200px;'>{$row_get_user['email']}</td><td style='width: 200px;'>{$row_get_user['phone']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
		$content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }
    if($_POST["called_from"] == "manage_users") {
    	$_SESSION["LA_SUCCESS"] = "Selected user has been suspended.";
    }
	echo json_encode(array('status' => 0));
	exit();
}else if(isset($_POST['mode']) && $_POST['mode'] == "unblock"){
	//get the user from the user table
	$query_get_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$sth_get_user = la_do_query($query_get_user, array(), $dbh);
	$row_get_user = la_do_fetch_result($sth_get_user);
	//get entity from the entity table
	$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
    $sth_entity = la_do_query($query_entity, array($row_get_user['client_id']), $dbh);
    $row_entity = la_do_fetch_result($sth_entity);

	$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `client_user_id`= ".$_POST['user_id'];
	la_do_query($query_update,array(':status' => 0),$dbh);

	//send unblock portal user notification
    if($la_settings['enable_registration_notification']){
		$login_user = $_SESSION['email'];
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		$subject = "Continuum GRC Account Management Alert";
		$content = "<h2>Continuum GRC Account Management Alert</h2>";
		$content .= "<h3>Administrative user ".$login_user." has unblocked a portal user in ".$site_name.".</h3>";
		$content .= "<hr/>";
		$content .= "<h3>User Details:</h3>";
		$content .= "<table>";
		$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Phone</td><td style='width: 200px;'>Entity</td></tr>";
		$content .= "<tr><td style='width: 200px;'>{$row_get_user['client_user_id']}</td><td style='width: 200px;'>{$row_get_user['full_name']}</td><td style='width: 200px;'>{$row_get_user['username']}</td><td style='width: 200px;'>{$row_get_user['email']}</td><td style='width: 200px;'>{$row_get_user['phone']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
		$content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }
    if($_POST["called_from"] == "manage_users") {
    	$_SESSION["LA_SUCCESS"] = "Selected user has been unblocked.";
    }
	echo json_encode(array('status' => 0));
	exit();
}else if(isset($_POST['mode']) && $_POST['mode'] == "deleted"){
	//$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `client_user_id`= ".$_POST['user_id'];
	// check whether deleted user is admin or not
	
	/*$select_query = "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
	$result = la_do_query($select_query,array(':client_user_id' => $_POST['user_id']),$dbh);
	$row = la_do_fetch_result($result);
	
	if($row){
		if($row['is_admin'] == 1){
			$select_user = "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_id` = :client_id";
			$result_user = la_do_query($select_user,array(':client_id' => $row['client_id']),$dbh);
			
			while($row_user = la_do_fetch_result($result_user)){
				$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id and `client_id` = :client_id";
				@la_do_query($query_delete_user,array(':client_user_id' => $row_user['client_user_id'], ':client_id' => $row['client_id']),$dbh);
				
				$query_delete_log  = "delete from `".LA_TABLE_PREFIX."portal_user_login_log` where `client_user_id` = :client_user_id";
				@la_do_query($query_delete_log,array(':client_user_id' => $row_user['client_user_id']),$dbh);
			}
			
			$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_user,array(':client_user_id' => $row['client_user_id']),$dbh);
			
			$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_clients` where `client_id` = :client_id";
			@la_do_query($query_delete_user,array(':client_id' => $row['client_id']),$dbh);
			
			$query_delete_log  = "delete from `".LA_TABLE_PREFIX."portal_user_login_log` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_log,array(':client_user_id' => $row['client_user_id']),$dbh);
			
			$query_delete_log  = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_log,array(':client_user_id' => $row['client_user_id']),$dbh);
		}else{
			$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_user,array(':client_user_id' => $row['client_user_id']),$dbh);
			
			$query_delete_log  = "delete from `".LA_TABLE_PREFIX."portal_user_login_log` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_log,array(':client_user_id' => $row['client_user_id']),$dbh);
			
			$query_delete_log  = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `client_user_id` = :client_user_id";
			@la_do_query($query_delete_log,array(':client_user_id' => $row['client_user_id']),$dbh);
		}
	}*/

	//get the user from the user table
	$query_get_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$sth_get_user = la_do_query($query_get_user, array(), $dbh);
	$row_get_user = la_do_fetch_result($sth_get_user);
	//get entity from the entity table
	$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
    $sth_entity = la_do_query($query_entity, array($row_get_user['client_id']), $dbh);
    $row_entity = la_do_fetch_result($sth_entity);

	$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
	@la_do_query($query_delete_user,array(':client_user_id' => $_POST['user_id']),$dbh);
	
	$query_delete_log  = "delete from `".LA_TABLE_PREFIX."portal_user_login_log` where `client_user_id` = :client_user_id";
	@la_do_query($query_delete_log,array(':client_user_id' => $_POST['user_id']),$dbh);
	
	$query_delete_log  = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `client_user_id` = :client_user_id";
	@la_do_query($query_delete_log,array(':client_user_id' => $_POST['user_id']),$dbh);
	
	//send delete portal user notification
    if($la_settings['enable_registration_notification']){
		$login_user = $_SESSION['email'];
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		$subject = "Continuum GRC Account Management Alert";
		$content = "<h2>Continuum GRC Account Management Alert</h2>";
		$content .= "<h3>Administrative user ".$login_user." has deleted a portal user in ".$site_name.".</h3>";
		$content .= "<hr/>";
		$content .= "<h3>User Details:</h3>";
		$content .= "<table>";
		$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Phone</td><td style='width: 200px;'>Entity</td></tr>";
		$content .= "<tr><td style='width: 200px;'>{$row_get_user['client_user_id']}</td><td style='width: 200px;'>{$row_get_user['full_name']}</td><td style='width: 200px;'>{$row_get_user['username']}</td><td style='width: 200px;'>{$row_get_user['email']}</td><td style='width: 200px;'>{$row_get_user['phone']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
		$content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }
    if($_POST["called_from"] == "manage_users") {
    	$_SESSION["LA_SUCCESS"] = "Selected user has been deleted.";
    }
	echo json_encode(array('status' => 2));
	exit();
}else if(isset($_POST['mode']) && $_POST['mode'] == "change_password"){
	$hasher = new Sha256Hash();
	$response = [];
	$response['status'] = 'error';
	if(empty($_POST['np']) && empty($_POST['user_id']) && empty($_POST['cp']) ){
		$response['message'] = "Request is missing required parameters.";
		custom_json_response($response);
	}

	$newpassword = $_POST['np'];
	$retype = $_POST['cp'];
	$user_id 		 = (int) $_POST['user_id'];
	$send_login_info = (int) $_POST['send_login'];

	if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) ){
		//if `50% Rule on Passwords is enabled` create a random password and no need to validate it
		$forced_password = true;
		$password_range = range(15, 20);
		$password_length = array_rand(array_flip($password_range));
		$newpassword = randomPassword($password_length);
	} else {
		//add password validations here
		if( $newpassword == $retype )
			$newpassword = $retype;
		else{
			$response['message'] = "New password does not match";
			custom_json_response($response);
		}

		if(strlen($newpassword) < 8){
			$response['message'] = "The new password must be a minimum of 8 characters";
		} else if( !preg_match("#[0-9]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one number!";
		}else if ( !preg_match("#[a-z]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one letter!";
		}else if ( !preg_match("#[A-Z]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one CAPS!";
		}else if ( !preg_match("#\W+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one symbol!";
		}
		//get last 14 passwords used by user
		$query = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=0 order by `id` DESC limit 14";
		$params = array($user_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$old_passwords = [];
		
		while($row = la_do_fetch_result($sth)){
			$old_passwords[]  = $hasher->CheckPassword($newpassword, $row['password']);
		}

		if( count($old_passwords) > 0 ) {
			if( in_array(1, $old_passwords) ) {
				$response['message'] = "The new password must be different from the user's former 14 passwords.";
				custom_json_response($response);
			}
		}
		if( !empty($response['message']) ){
			custom_json_response($response);
		}
	}
	
	$setting_query = "SELECT `default_from_name`, `default_from_email` FROM `".LA_TABLE_PREFIX."settings`";
	$setting_result = la_do_query($setting_query,array(),$dbh);
	$setting_result_row = la_do_fetch_result($setting_result);
	
	$query_select = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$user_id;
	$query_result = la_do_query($query_select,array(),$dbh);
	$query_result_row = la_do_fetch_result($query_result);
	
	$new_password_hash = $hasher->HashPassword($newpassword);
	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET password = ?, `password_change_date` = ? WHERE client_user_id = ?";
	$params = array($new_password_hash,strtotime(date('Y-m-d H:i:s')),$user_id);
	la_do_query($query,$params,$dbh);
	
	//save new password to `old_password_hash` table
	insert_old_password_hash($user_id, $new_password_hash, 0, $dbh);

	//get user information
	$query = "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id`=? and `status`<>2";
	
	$params = array($user_id);
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$user_fullname = $row['full_name'];
	$user_email = $row['email'];

	//get entity from the entity table
	$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
    $sth_entity = la_do_query($query_entity, array($row['client_id']), $dbh);
    $row_entity = la_do_fetch_result($sth_entity);

	$subject = 'Your IT Audit Machine password change notification';
	$email_content = "<p>Hello {$user_fullname}</p><p>The password for your user account({$user_email}) on {$_SERVER['SERVER_NAME']} has been changed.<p><br><p>Please contact an administrator or set your own password.</p><br>Thank you.";

	$subject = utf8_encode($subject);
	// Always set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	// More headers
	$headers .= 'From: '.$setting_result_row['default_from_name'].'<'.$setting_result_row['default_from_email'].'>' . "\r\n";
	
	if($send_login_info){
		@mail($user_email, $subject, $email_content, $headers);
	}

	//send change password of a portal user notification
    if($la_settings['enable_registration_notification']){
		$login_user = $_SESSION['email'];
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		$subject = "Continuum GRC Account Management Alert";
		$content = "<h2>Continuum GRC Account Management Alert</h2>";
		$content .= "<h3>Administrative user ".$login_user." has changed the password of a portal user in ".$site_name.".</h3>";
		$content .= "<hr/>";
		$content .= "<h3>User Details:</h3>";
		$content .= "<table>";
		$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Entity</td></tr>";
		$content .= "<tr><td style='width: 200px;'>{$row['client_user_id']}</td><td style='width: 200px;'>{$row['full_name']}</td><td style='width: 200px;'>{$row['username']}</td><td style='width: 200px;'>{$row['email']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
		$content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }
	$response['status'] = 'success';
   	$response['new_password'] = $newpassword;
   	custom_json_response($response);
	exit();
}