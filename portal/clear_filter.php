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

	require('includes/filter-functions.php');
	
	$form_id 	= (int) trim($_POST['form_id']);
	$user_id	= (int) $_SESSION['la_user_id'];

	$incomplete_entries = (int) $_POST['incomplete_entries']; //if this is operation targetted to incomplete entries, this will contain '1'
	if(empty($incomplete_entries)){
		$incomplete_entries = 0;
	}

	if(empty($form_id)){
		die("This file can't be opened directly.");
	}

	$dbh = la_connect_db();
	
	//first delete all previous filter
	$query = "delete from `".LA_TABLE_PREFIX."form_filters` where form_id=? and user_id=? and incomplete_entries=?";
	$params = array($form_id,$user_id,$incomplete_entries);
	la_do_query($query,$params,$dbh);

	//update existing record within ap_entries_preferences
	if(empty($incomplete_entries)){
		$query = "update ".LA_TABLE_PREFIX."entries_preferences set entries_enable_filter=0,entries_filter_type='all' where form_id=? and user_id=?";
	}else{
		$query = "update ".LA_TABLE_PREFIX."entries_preferences set entries_incomplete_enable_filter=0,entries_incomplete_filter_type='all' where form_id=? and user_id=?";
	}
	
	$params = array($form_id,$user_id);
	la_do_query($query,$params,$dbh);
	
	$response_data = new stdClass();
	$response_data->status    	= "ok";
	$response_data->form_id 	= $form_id;
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>