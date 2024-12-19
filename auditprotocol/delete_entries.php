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
	require('includes/entry-functions.php');
	require('includes/users-functions.php');
	//echo 'Test'; exit;
	
	$form_id 		   	= (int) trim($_POST['form_id']);
	$selected_entries  	= la_sanitize($_POST['selected_entries']);
	$user_id		   	= (int) $_SESSION['la_user_id'];

	if(empty($form_id)){
		die("This file can't be opened directly.");
	}

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_entries permission
		if(empty($user_perms['edit_entries'])){
			die("Access Denied. You don't have permission to edit this entry.");
		}
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);	
	
	foreach ($selected_entries as $row) {
		$company_id = $row['company_id'];
		$entry_id = $row['entry_id'];

		//delete data from the table
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
		la_do_query($query, array($company_id, $entry_id), $dbh);

		//delete status indicators
		$query = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		la_do_query($query, array($form_id, $company_id, $entry_id), $dbh);

		//delete generated document outputs
		$query = "DELETE FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		la_do_query($query, array($form_id, $company_id, $entry_id), $dbh);

		//delete background proccess for generating document outputs
		$query = "DELETE FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ?";
		la_do_query($query, array($form_id, $company_id, $entry_id), $dbh);
	}
	
	$response_data = new stdClass();
	$response_data->status    	= "ok";
	$response_json = json_encode($response_data);
	echo $response_json;
?>