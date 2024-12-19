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
	
	if(empty($_POST['unregister'])){
		die("Invalid parameters.");
	}

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		die("Access Denied. You don't have permission to administer IT Audit Machine.");
	}
	
	$dbh = la_connect_db();
	$data['customer_name'] = 'unregistered';
	$data['customer_id']   = '';
	$data['license_key']   = '';

   	la_ap_settings_update($data,$dbh);

   	echo '{"status" : "ok"}';
?>