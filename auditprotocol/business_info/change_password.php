<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('../includes/init.php');
	require('../config.php');
	require('../includes/db-core.php');
	require('../includes/helper-functions.php');
	require('../includes/filter-functions.php');
	//require('../lib/password-hash.php');
	//require('../lib/swift-mailer/swift_required.php');
	
	
	$dbh = la_connect_db();
	
	$input = la_sanitize($_POST);

	if(empty($input['np']) && empty($input['user_id'])){
		die("Error! You can't open this file directly");
	}else{
		$new_password_plain = $input['np'];
		$user_id = (int) $input['user_id'];
		$send_login_info = (int) $input['send_login'];
	}
	echo '8<br>';
	$hasher = new Sha256Hash();
	$new_password_hash = $hasher->HashPassword($new_password_plain);
	echo '9<br>';
	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET password = ? WHERE user_id = ?";
	$params = array($new_password_hash,$user_id);
	la_do_query($query,$params,$dbh);
echo '10<br>';
	//if send_login parameter exist, resend the login information to user
	if(!empty($send_login_info)){
		la_send_login_info($dbh,$user_id,$new_password_plain);
	}
echo '11<br>';
   	echo '{"status" : "ok"}';