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
	$column_preferences = la_sanitize($_POST['col_pref']);
	$user_id 	= (int) $_SESSION['la_user_id'];

	$incomplete_entries = (int) $_POST['incomplete_entries']; //if this is operation targetted to incomplete entries, this will contain '1'
	if(empty($incomplete_entries)){
		$incomplete_entries = 0;
	}

	if(empty($form_id)){
		die("This file can't be opened directly.");
	}

	$dbh = la_connect_db();
	
	//first delete all previous preferences
	$query = "delete from `".LA_TABLE_PREFIX."column_preferences` where form_id=? and user_id=? and incomplete_entries=?";
	$params = array($form_id,$user_id,$incomplete_entries);
	la_do_query($query,$params,$dbh);

	//save the new preference
	$query = "insert into `".LA_TABLE_PREFIX."column_preferences`(form_id,user_id,element_name,position,incomplete_entries) values(?,?,?,?,?)";

	$position = 1;
	if(!empty($column_preferences)){
		foreach($column_preferences as $data){
			$column_name = $data['name'];
			
			$params = array($form_id,$user_id,$column_name,$position,$incomplete_entries);
			la_do_query($query,$params,$dbh);

			$position++;
		}
	}
	
	$response_data = new stdClass();
	$response_data->status    	= "ok";
	$response_data->form_id 	= $form_id;
	
	$response_json = json_encode($response_data);
	
	$_SESSION['LA_SUCCESS'] = 'Your fields preference has been saved.';

	echo $response_json;
?>