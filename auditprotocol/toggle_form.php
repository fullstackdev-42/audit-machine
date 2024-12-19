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
	require('includes/filter-functions.php');
	require('includes/check-session.php');
	require('includes/users-functions.php');
	
	$dbh = la_connect_db();
	
	$form_id				= (int) la_sanitize($_POST['form_id']);
	$action					= la_sanitize($_POST['action']);

	if(!empty($_POST['disabled_message'])){
		$disabled_message	= la_sanitize($_POST['disabled_message']);
	}

	$update_success = false;

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("Access Denied. You don't have permission to edit this form.");
		}
	}
	
	if(!empty($form_id) && !empty($action)){
		if($action == 'enable' || $action == 'disable'){
			if($action == 'enable'){
				$form_active = 1;
			}else if($action == 'disable'){
				$form_active = 0;
			}
			
			if(!empty($disabled_message)){
				$params = array($form_active,$disabled_message,$form_id);
				$query = "UPDATE `".LA_TABLE_PREFIX."forms` SET form_active=?,form_disabled_message=? WHERE form_id=?";
			}else{
				$params = array($form_active,$form_id);
				$query = "UPDATE `".LA_TABLE_PREFIX."forms` SET form_active=? WHERE form_id=?";
			}
			
			la_do_query($query,$params,$dbh);
			
			$update_success = true;
		}
	}

	$response_data = new stdClass();
	
	if($update_success){
		$response_data->status    	= "ok";
	}else{
		$response_data->status    	= "error";
	}
	
	$response_data->form_id 	= $form_id;
	$response_data->action 		= $action;
	$response_json = json_encode($response_data);
	
	echo $response_json;
	exit();