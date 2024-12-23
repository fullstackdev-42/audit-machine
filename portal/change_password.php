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

	require('includes/filter-functions.php');
	require('lib/password-hash.php');
	require('lib/swift-mailer/swift_required.php');
	
	$dbh = la_connect_db();
	
	$input = la_sanitize($_POST);

	if(empty($input['np']) && empty($input['user_id'])){
		die("Error! You can't open this file directly");
	}else{
		$new_password_plain = $input['np'];
		$user_id = (int) $input['user_id'];
		$send_login_info = (int) $input['send_login'];
	}

	//check permissions and privileges
	//normal user should only be able to change his own password
	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(!empty($_SESSION['la_user_privileges']['priv_administer'])){
		//this is administrator, allowed to change the password of any other user's password
		//except the main administrator password
		if($user_id == 1 && $_SESSION['la_user_id'] != 1){
			die("Access Denied. You don't have permission to change Main Administrator password.");
		}
	}else{
		$user_id = $_SESSION['la_user_id']; //this is normal user, make sure he only change his own password
	}

	$hasher = new Sha256Hash();
	$new_password_hash = $hasher->HashPassword($new_password_plain);
	
	$query = "UPDATE ".LA_TABLE_PREFIX."users SET user_password = ? WHERE user_id = ?";
	$params = array($new_password_hash,$user_id);
	la_do_query($query,$params,$dbh);

	//if send_login parameter exist, resend the login information to user
	if(!empty($send_login_info)){
		la_send_login_info($dbh,$user_id,$new_password_plain);
	}

   	echo '{"status" : "ok"}';
	
?>