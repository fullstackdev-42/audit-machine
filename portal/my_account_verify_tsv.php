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
	require('includes/check-session.php');

	require('includes/filter-functions.php');
	require('lib/google-authenticator.php');
	
	$dbh = la_connect_db();
	
	$input = la_sanitize($_POST);
	
	$tsv_secret = $input['tsv_secret'];
	$tsv_code	= $input['tsv_code'];

	if(empty($tsv_secret) && empty($tsv_code)){
		die("Error! You can't open this file directly");
	}

	$authenticator = new PHPGangsta_GoogleAuthenticator();
	$tsv_result    = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  //8 means 4 minutes before or after
	
	if($tsv_result === true){
		//insert tsv code into ap_users table and enable tsv
		$user_id = $_SESSION['la_user_id'];
		
		$query = "UPDATE ".LA_TABLE_PREFIX."users SET tsv_enable = 1,tsv_secret = ?,tsv_code_log = ? WHERE user_id = ?";
		$params = array($tsv_secret,$tsv_code,$user_id);
		la_do_query($query,$params,$dbh);

	   	echo '{"status" : "ok"}';
	}else{
		echo '{"status" : "error"}';
	}
?>