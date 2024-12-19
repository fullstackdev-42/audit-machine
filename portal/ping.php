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
	require('includes/users-functions.php');

	$dbh = la_connect_db();
	
	$form_id = (int) $_POST['form_id'];

	if(empty($form_id)){
		die("Parameter required.");
	}

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("Access Denied. You don't have permission to edit this form.");
		}
	}

	//delete previous record on ap_form_locks table
	$query = "delete from ".LA_TABLE_PREFIX."form_locks where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//insert new record
	$current_timestamp = date("Y-m-d H:i:s");

	$query = "insert into ".LA_TABLE_PREFIX."form_locks(form_id,user_id,lock_date) values(?,?,?)";
	$params = array($form_id,$_SESSION['la_user_id'],$current_timestamp);
	la_do_query($query,$params,$dbh);

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	
   	echo '{"status" : "ok"}';

?>