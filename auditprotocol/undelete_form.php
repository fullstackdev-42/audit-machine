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
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	if(empty($_POST['form_id'])){
		die("Error! You can't open this file directly");
	}
	
	$form_id = (int) $_POST['form_id'];
	
	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("Access Denied. You don't have permission to delete this form.");
		}
	}
	
	//safe deletion
	$query = "update ".LA_TABLE_PREFIX."forms set form_active = '1' where form_id=?";
	$params = array($form_id);
	la_do_query($query,$params,$dbh);
	
	//remove the actual form table
	$query = "truncate table `".LA_TABLE_PREFIX."form_{$form_id}`";
	$params = array();
	la_do_query($query,$params,$dbh);
	
	$query = "delete from `".LA_TABLE_PREFIX."deleted_form` where form_id = :form_id";
	$params = array(':form_id' => $form_id);
	la_do_query($query,$params,$dbh);

	$_SESSION['LA_SUCCESS'] = 'The form is now active again.';
   	echo '{ "status" : "ok" }';