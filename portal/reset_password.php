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
	$tsv_code = '';
	
	$query  = "SELECT `email`, `tsv_code`, `client_user_id`, `full_name` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `email`=? AND `status` = 0 AND `is_invited` = 0";
	$params = array($target_email);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	if(!empty($row['email'])){
		$is_registered_email = true;
		$tsv_code = $row['tsv_code'];
		$client_user_id = $row['client_user_id'];
	}

	//if the wrong email addess being entered, return error message
	if($is_registered_email === false){
		echo '{"status" : "error", "message" : "Incorrect email address. Please try again."}';
		return true;
	}

   	$token = sha1(uniqid($user_input['user_email'], true));
	$tstamp = $_SERVER["REQUEST_TIME"];

	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `token` = ?, tstamp = ? WHERE `client_user_id` = ?";
	la_do_query($query, array($token, $tstamp, $client_user_id), $dbh);

	$params = array('token' => $token, 'forgot_password' => 1);
    $la_send_one_time_url = la_send_one_time_url($dbh, $row['full_name'], $row['email'], $params);
    
	if( $la_send_one_time_url ) {
   		echo '{"status" : "ok", "message" : "New password information has been sent to your email address."}';
	} else {
		echo '{"status" : "error", "message" : "Error occured while sending email. Please try again later."}';
	}
	die();
?>