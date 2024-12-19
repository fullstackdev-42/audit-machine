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
	
	$dbh = la_connect_db();
	
	if(empty($_POST['form_id'])){
		die("Error! You can't open this file directly");
	}

	//check for max_input_vars
	la_init_max_input_vars();


	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("Access Denied. You don't have permission to edit this form.");
		}
	}

	$user_id_session = $_SESSION['la_user_id'];
	$form_id = (int) trim($_POST['form_id']);
	
	$integration_settings  = la_sanitize($_POST['integration_settings']);

	$chat_bot_enable   = (int) $integration_settings['chat_bot_enable'];
	$chat_bot_type   = $integration_settings['chat_bot_type'];


	$chatstack_domain   = $_POST['chatstack_domain'];
	$chatstack_id   = $_POST['chatstack_id'];

	$chat_bot_properties = [];

	$chat_bot_properties[0]['field_name'] = 'chatstack_domain';
	$chat_bot_properties[0]['field_value'] = $chatstack_domain;

	$chat_bot_properties[1]['field_name'] = 'chatstack_id';
	$chat_bot_properties[1]['field_value'] = $chatstack_id;


	

	/** Field Logic **/
	//save chat_bot_properties into ap_field_logic_elements table
	$query = "delete from ".LA_TABLE_PREFIX."form_integration_fields where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	if(!empty($chat_bot_enable)){
		$query = "insert into `".LA_TABLE_PREFIX."form_integration_fields`(form_id,field_name,field_value) values(?,?,?)";
		foreach ($chat_bot_properties as $data) {
			$params = array($form_id,$data['field_name'],$data['field_value']);
			la_do_query($query,$params,$dbh);
		}
	}

	//save integration into ap_forms table
	$form_input = array();
	$form_input['chat_bot_enable']   = $chat_bot_enable;
	$form_input['chat_bot_type']   = $chat_bot_type;

	la_ap_forms_update($form_id,$form_input,$dbh);

	$_SESSION['LA_SUCCESS'] = 'Logics has been saved.';
   	echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';