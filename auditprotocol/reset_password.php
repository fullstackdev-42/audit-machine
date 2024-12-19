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
	require('lib/swift-mailer/swift_required.php');
	require('lib/password-hash.php');
	
	$target_email = strtolower(trim($_POST['target_email']));

	if(empty($target_email)){
		die("Invalid parameters.");
	}
	
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	//validate the email address
	$is_registered_email = false;
	
	$query  = "SELECT user_email, user_id, user_fullname  FROM `".LA_TABLE_PREFIX."users` WHERE `user_email`=? and `status`=1";
	$params = array($target_email);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	if(!empty($row['user_id'])){
		$is_registered_email = true;
		$user_id = $row['user_id'];
	}
	
	//if the wrong email addess being entered, return error message
	if($is_registered_email === false){
		echo '{"status" : "error", "message" : "Incorrect email address. Please try again."}';
		die();
	}

	$token = sha1(uniqid($user_input['user_email'], true));
	$tstamp = $_SERVER["REQUEST_TIME"];

	$query = "UPDATE `".LA_TABLE_PREFIX."users` SET `token` = ?, tstamp = ? WHERE `user_id` = ?;";
	la_do_query($query, array($token, $tstamp, $user_id), $dbh);

	$params = array('token' => $token, 'forgot_password' => 1);
    $la_send_one_time_url = la_send_one_time_url($dbh, $row['user_fullname'], $row['user_email'], $params);

	if( $la_send_one_time_url ) {
   		echo '{"status" : "ok", "message" : "New password information has been sent to your email address."}';
	} else {
		echo '{"status" : "error", "message" : "Error occured while sending email. Please try again later."}';
	}
	die();
?>