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

	//check for max_input_vars
	la_init_max_input_vars();

	if(empty($_POST['payment_properties'])){
		die("Error! You can't open this file directly");
	}
	
	$payment_properties = la_sanitize($_POST['payment_properties']);
	//$field_prices = la_sanitize($_POST['field_prices']);
	
	$form_id = (int) $payment_properties['form_id'];
	unset($payment_properties['form_id']);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("Access Denied. You don't have permission to edit this form.");
		}
	}
	
	//save payment properties into ap_forms table
	foreach ($payment_properties as $key=>$value){
		$form_input['payment_'.$key] = $value;
	}
	
	//if the merchant type is "check/cash" we need to make sure to disable the tax calculation
	if($form_input['payment_merchant_type'] == 'check'){
		$form_input['payment_enable_tax'] = 0;
	}

	//make sure only positive numbers entered into discount amount
	if(!empty($form_input['payment_discount_amount'])){
		$form_input['payment_discount_amount'] = (float) abs($form_input['payment_discount_amount']);

		//make sure discount percentage is not larger than 100%
		if($form_input['payment_discount_type'] == 'percent_off' && $form_input['payment_discount_amount'] > 100){
			$form_input['payment_discount_amount'] = 100;
		}
	}

	//make sure discount max redemption is a positive number
	if(!empty($form_input['payment_discount_max_usage'])){
		$form_input['payment_discount_max_usage'] = (int) abs($form_input['payment_discount_max_usage']);
	}

	la_ap_forms_update($form_id,$form_input,$dbh);
	
   
	$_SESSION['LA_SUCCESS'] = 'Payment settings has been saved.';
	
   	echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';
	exit();