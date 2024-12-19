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
	
	if(empty($_POST['customer_id'])){
		die("Invalid parameters.");
	}

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		die("Access Denied. You don't have permission to administer IT Audit Machine.");
	}
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$data['customer_name'] = $_POST['customer_name'];
	$data['customer_id'] = $_POST['customer_id'];
	$data['license_key'] = substr($_POST['license_key'], 0,1);
   	la_ap_settings_update($data,$dbh);

   	echo '{"status" : "ok"}';
?>