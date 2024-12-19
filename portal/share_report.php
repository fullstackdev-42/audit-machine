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
	
	$form_id 	= (int) trim($_POST['form_id']);
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("You don't have permission to edit this form.");
		}
	}
	
	//generate access key
	$report_access_key = $form_id.'x'.substr(strtolower(md5(uniqid(rand(), true))),0,10);
	
	//insert into ap_reports table
	$query = "insert into `".LA_TABLE_PREFIX."reports`(form_id,report_access_key) values(?,?)";
	$params = array($form_id,$report_access_key);
	la_do_query($query,$params,$dbh);

	$report_shared_link = "<a href=\"{$la_settings['base_url']}report.php?key={$report_access_key}\" target=\"blank\">{$la_settings['base_url']}report.php?key={$report_access_key}</a>";
			
	$response_data = new stdClass();
	
	$response_data->status    	= "ok";
	$response_data->report_link = $report_shared_link;
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>