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

	require('includes/filter-functions.php');
	require('includes/users-functions.php');
	
	$action = trim($_POST['action']);
	$portal_user_id = trim($_POST['portal_user_id']);	
	$no_of_days_to_change_password = trim($_POST['no_of_days_to_change_password']);
	
	if(empty($action)){
		die("This file can't be opened directly.");
	}
	
	$dbh = la_connect_db();	
	$curtime = time();
	
	if($action == 'change'){
		$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `no_of_days_to_change_password` = ?, `password_change_date` = ? WHERE `client_user_id` = ?;";
		la_do_query($query,array($no_of_days_to_change_password, $curtime, $portal_user_id),$dbh);
	}
	
	$response_data = new stdClass();
	$response_data->status    	= "ok";
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();