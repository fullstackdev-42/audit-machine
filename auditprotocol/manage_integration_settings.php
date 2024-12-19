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
	require('lib/password-hash.php');
	require('../itam-shared/includes/integration-helper-functions.php');
	
	$dbh = la_connect_db();
	
	if(empty($_POST['form_id'])){
		echo '{"status" : "error", "msg" : "Invalid Form ID!"}';
	}

	//check for max_input_vars
	la_init_max_input_vars();

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			echo '{"status" : "error", "msg" : "Access Denied. You don\'t have permission to edit this form."}';
		}
	}

	$form_id = (int) trim($_POST['form_id']);
	$action_type = $_POST['action_type'];

	if($form_id == 0) {
		echo '{"status" : "error", "msg" : "Invalid Form ID!"}';
	} else {
		if($action_type == "save_chatbot_settings") {
			$chatbot = $_POST['chatbot'];
			//save chatbot integration settings
			$chat_bot_enable = 1;
			$chat_bot_type = $chatbot['chat_bot_type'];
			$chatstack_domain = $chatbot['chat_bot_domain'];
			$chatstack_id = $chatbot['chat_bot_id'];
			
			$chat_bot_properties = [];

			$chat_bot_properties[0]['field_name'] = 'chatstack_domain';
			$chat_bot_properties[0]['field_value'] = $chatstack_domain;

			$chat_bot_properties[1]['field_name'] = 'chatstack_id';
			$chat_bot_properties[1]['field_value'] = $chatstack_id;

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

			//enable chatbot in ap_forms table
			$form_input = array();
			$form_input['chat_bot_enable']   = $chat_bot_enable;
			$form_input['chat_bot_type']   = $chat_bot_type;

			la_ap_forms_update($form_id,$form_input,$dbh);
			$_SESSION['LA_SUCCESS'] = 'The ChatStack integration settings has been saved.';
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "save_saint_settings") {
			$saint_data = $_POST['saint'];
			$form_input = array();
			$form_input['saint_enable'] = 1;
			la_ap_forms_update($form_id,$form_input,$dbh);
			//save saint_settings
			save_saint_settings($dbh, $form_id, $saint_data);
			$_SESSION['LA_SUCCESS'] = 'The SAINT integration settings has been saved.';
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "save_nessus_settings") {
			$nessus_data = $_POST['nessus'];
			$form_input = array();
			$form_input['nessus_enable'] = 1;
			la_ap_forms_update($form_id,$form_input,$dbh);
			//save nessus_settings
			save_nessus_settings($dbh, $form_id, $nessus_data);
			$_SESSION['LA_SUCCESS'] = 'The Nessus integration settings has been saved.';
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "test_saint_settings") {
			$saint_url = $_POST['saint_url'];
			$saint_port = $_POST['saint_port'];
			$saint_api_token = $_POST['saint_api_token'];
			$saint_job_id = $_POST['saint_job_id'];
			$saint_ssl_enable = $_POST['saint_ssl_enable'];
			$data = test_saint_api_config($dbh, $saint_url, $saint_port, $saint_api_token, $saint_job_id, $saint_ssl_enable);
			echo json_encode($data);
			exit();
		} else if($action_type == "test_nessus_settings") {
			$nessus_access_key = $_POST['nessus_access_key'];
			$nessus_secret_key = $_POST['nessus_secret_key'];
			$nessus_scan_name = $_POST['nessus_scan_name'];
			$data = test_nessus_api_config($dbh, $nessus_access_key, $nessus_secret_key, $nessus_scan_name);
			echo json_encode($data);
			exit();
		} else if($action_type == "delete_saint_settings") {
			$saint_id = $_POST['saint_id'];
			delete_saint_settings($dbh, $form_id, $saint_id);
			$_SESSION['LA_SUCCESS'] = "You've deleted one of the SAINT API configuration settings.";
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "delete_nessus_settings") {
			$nessus_id = $_POST['nessus_id'];
			delete_nessus_settings($dbh, $form_id, $nessus_id);
			$_SESSION['LA_SUCCESS'] = "You've deleted one of the Nessus API configuration settings.";
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "disable_chatbot_settings") {
			//delete chatbot settings for this form
			$query = "delete from ".LA_TABLE_PREFIX."form_integration_fields where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);

			//disable chatbot in ap_forms table
			$form_input = array();
			$form_input['chat_bot_enable'] = 0;
			$form_input['chat_bot_type'] = "";

			la_ap_forms_update($form_id,$form_input,$dbh);
			$_SESSION['LA_SUCCESS'] = "The ChatStack integration settings has been disabled.";
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "disable_saint_settings") {
			delete_all_saint_settings($dbh, $form_id);
			$form_input = array();
			$form_input['saint_enable'] = 0;
			la_ap_forms_update($form_id,$form_input,$dbh);
			$_SESSION['LA_SUCCESS'] = "The SAINT integration settings has been disabled.";
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "disable_nessus_settings") {
			delete_all_nessus_settings($dbh, $form_id);
			$form_input = array();
			$form_input['nessus_enable'] = 0;
			la_ap_forms_update($form_id, $form_input, $dbh);
			$_SESSION['LA_SUCCESS'] = "The Nessus integration settings has been disabled.";
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "single_report_delete") {
			$delete_report_id = $_POST['delete_report_id'];
			$delete_report_type = $_POST['delete_report_type'];
			if($delete_report_type == "SAINT") {
				delete_single_saint_report($dbh, $delete_report_id);
				$_SESSION['LA_SUCCESS'] = 'The SAINT report has been deleted.';
			} else if($delete_report_type == "Nessus") {
				delete_single_nessus_report($dbh, $delete_report_id);
				$_SESSION['LA_SUCCESS'] = 'The Nessus report has been deleted.';
			}
			echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
			exit();
		} else if($action_type == "save_migration_wizard_settings") {
			$target_url = $_POST['target_url'];
			$scheme = parse_url($target_url)["scheme"];
			$host = parse_url($target_url)["host"];
			if($host == $_SERVER["http_host"]) {
				echo '{"status" : "error", "msg" : "Please enter the URL of another system!"}';
				exit();
			} else {
				$target_url = $scheme."://".$host;
				$connector_role = $_POST['connector_role'];
				$key = $_POST['key'];
				$res = save_migration_wizard_settings($dbh, $form_id, $target_url, $connector_role, $key);
				echo $res;
				exit();
			}
		} else if($action_type == "disable_migration_wizard_settings") {
			$form_id = $_POST['form_id'];
			$query_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_migration_wizard_settings` WHERE form_id = ?";
			la_do_query($query_delete, array($form_id), $dbh);

			$query_update = "UPDATE `".LA_TABLE_PREFIX."forms` SET `migration_wizard_enable` = ? WHERE `form_id` = ?";
			la_do_query($query_update, array(0, $form_id), $dbh);

			$res = '{ "status" : "ok", "msg" : "The Migration Wizard\'s settings have been successfully deleted." }';
			$_SESSION['LA_SUCCESS'] = "The Migration Wizard's settings have been successfully deleted.";
			echo $res;
			exit();
		} else if($action_type == "generate_authorization_key_for_migration_wizard_settings") {
			$password_range = range(15, 20);
			$password_length = array_rand(array_flip($password_range));
			$newpassword = randomPassword($password_length);
			$res = '{ "status" : "ok", "authorization_key" : "'.$newpassword.'" }';
			echo $res;
			exit();
		}
	}
?>	