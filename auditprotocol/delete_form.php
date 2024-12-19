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
require('includes/theme-functions.php');
require('includes/users-functions.php');
require('../itam-shared/includes/helper-functions.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

if(empty($_POST['form_id'])){
	die("Error! You can't open this file directly");
}

$form_id = (int) la_sanitize($_POST['form_id']);

//check permission, is the user allowed to access this page?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
	$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

	//this page need edit_form permission
	if(empty($user_perms['edit_form'])){
		die("Access Denied. You don't have permission to delete this form.");
	}
}

// add user activity to log: activity - 3 (DELETE)
$session_time = sessionTime($dbh, $_SESSION['la_user_id']);
addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 3, "Session Time {$session_time}", time());

//depends on the config file 
//when deleting the form, we can simply set the status of the form_active to '9' which means deleted
//or delete the form data completely
if(LA_CONF_TRUE_DELETE === true){ //true deletion
	
	//remove from ap_forms
	$query = "delete from ".LA_TABLE_PREFIX."forms where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove from ap_form_elements
	$query = "delete from ".LA_TABLE_PREFIX."form_elements where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove from ap_element_options
	$query = "delete from ".LA_TABLE_PREFIX."element_options where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove from ap_column_preferences
	$query = "delete from ".LA_TABLE_PREFIX."column_preferences where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_entries_preferences
	$query = "delete from ".LA_TABLE_PREFIX."entries_preferences where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_form_locks
	$query = "delete from ".LA_TABLE_PREFIX."form_locks where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_element_prices
	$query = "delete from ".LA_TABLE_PREFIX."element_prices where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_field_logic_elements table
	$query = "delete from ".LA_TABLE_PREFIX."field_logic_elements where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_field_logic_conditions table
	$query = "delete from ".LA_TABLE_PREFIX."field_logic_conditions where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_page_logic table
	$query = "delete from ".LA_TABLE_PREFIX."page_logic where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_page_logic_conditions table
	$query = "delete from ".LA_TABLE_PREFIX."page_logic_conditions where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_email_logic table
	$query = "delete from ".LA_TABLE_PREFIX."email_logic where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_email_logic_conditions table
	$query = "delete from ".LA_TABLE_PREFIX."email_logic_conditions where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_webhook_options table
	$query = "delete from ".LA_TABLE_PREFIX."webhook_options where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_webhook_parameters table
	$query = "delete from ".LA_TABLE_PREFIX."webhook_parameters where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_reports table
	$query = "delete from ".LA_TABLE_PREFIX."reports where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_report_elements table
	$query = "delete from ".LA_TABLE_PREFIX."report_elements where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_report_filters table
	$query = "delete from ".LA_TABLE_PREFIX."report_filters where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);

	//remove from ap_grid_columns table
	$query = "delete from ".LA_TABLE_PREFIX."grid_columns where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove review table
	$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}_review`";
	$params = array();
	la_do_query($query,$params,$dbh);
	
	//remove the actual form table
	$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}`";
	$params = array();
	la_do_query($query,$params,$dbh);
	
	//remove from ap_form_submission_details table
	$query = "delete from ".LA_TABLE_PREFIX."form_submission_details where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	

	//remove from ap_form_template table
	$query = "delete from ".LA_TABLE_PREFIX."form_template where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	

	//remove from ap_form_payment_check table
	$query = "delete from ".LA_TABLE_PREFIX."form_payment_check where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	
	//remove from ap_form_report_elements table
	$query = "delete `fre` from `".LA_TABLE_PREFIX."form_report_elements` as `fre` left join `".LA_TABLE_PREFIX."form_report` as `fr` ON (`fre`.`report_id` = `fr`.`report_id`) where `fr`.`form_id` = ?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	

	//remove from ap_form_report table
	$query = "delete from ".LA_TABLE_PREFIX."form_report where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove form folder
	@la_full_rmdir($la_settings['upload_dir']."/form_{$form_id}");
	if($la_settings['upload_dir'] != $la_settings['data_dir']){
		@la_full_rmdir($la_settings['data_dir']."/form_{$form_id}");
	}

	//delete entries from ap_permissions table, regardless of the config
	$query = "delete from ".LA_TABLE_PREFIX."permissions where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);		
}else{ 
	//safe deletion
	$query = "update ".LA_TABLE_PREFIX."forms set form_active = '2' where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove the actual form table
	$query = "truncate table `".LA_TABLE_PREFIX."form_{$form_id}`";
	$params = array();
	la_do_query($query,$params,$dbh);
	
	$query = "insert into `".LA_TABLE_PREFIX."deleted_form` (id, form_id, delete_datetime) values (NULL, :form_id, :delete_datetime)";
	$params = array(':form_id' => $form_id, ':delete_datetime' => time());
	la_do_query($query,$params,$dbh);
}

$_SESSION['LA_SUCCESS'] = 'The form has been deleted.';
echo '{ "status" : "ok" }';
exit();