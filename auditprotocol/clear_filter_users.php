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

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		die("You don't have permission to administer IT Audit Machine.");
	}

	$_SESSION['filter_users'] = array();
	unset($_SESSION['filter_users']);
	
	
	$response_data = new stdClass();
	$response_data->status    	= "ok";
	
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>