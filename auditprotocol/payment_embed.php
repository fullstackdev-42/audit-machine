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
	
	require('includes/language.php');
	require('includes/common-validator.php');
	require('includes/view-functions.php');
	require('includes/theme-functions.php');
	require('includes/post-functions.php');
	require('includes/entry-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('hooks/custom_hooks.php');
	
	$dbh 		  = la_connect_db();
	$form_id 	  = (int) trim($_REQUEST['id']);
	$paid_form_id = (int) trim($_POST['form_id_redirect']);


	if(!empty($paid_form_id) && $_SESSION['la_payment_completed'][$paid_form_id] === true){
		//when payment succeeded, $paid_form_id should contain the form id number
		$form_properties = la_get_form_properties($dbh,$paid_form_id,array('form_redirect_enable','form_redirect','form_review','form_page_total','payment_delay_notifications'));
		
		//process any delayed notifications
		if(!empty($form_properties['payment_delay_notifications'])){
			la_process_delayed_notifications($dbh,$paid_form_id,$_SESSION['la_payment_record_id'][$paid_form_id]);
		}
		

		//redirect to the default success page or the custom redirect URL being set on form properties
		if(!empty($form_properties['form_redirect_enable']) && !empty($form_properties['form_redirect'])){
			
			//parse redirect URL for any template variables first
			$form_properties['form_redirect'] = la_parse_template_variables($dbh,$paid_form_id,$_SESSION['la_payment_record_id'][$paid_form_id],$form_properties['form_redirect']);
			
			echo "<script type=\"text/javascript\">top.location.replace('{$form_properties['form_redirect']}')</script>";
			exit;
		}else{
			$ssl_suffix = la_get_ssl_suffix();
			
			header("Location: embed.php?id={$paid_form_id}&done=1");
			exit;
		}
	}else{
		//display payment form
		if(empty($form_id)){
			die('ID required.');
		}else{
			$record_id = $_SESSION['la_payment_record_id'][$form_id];

			$form_params = array();
			$form_params['integration_method'] = 'iframe';

			//if payment token exist, the user is resuming payment from previously unpaid entry
			if(!empty($_GET['pay_token'])){
				$form_params['pay_token'] = $_GET['pay_token'];
			}	

			$markup    = la_display_form_payment($dbh,$form_id,$record_id,$form_params);
				
			header("Content-Type: text/html; charset=UTF-8");
			echo $markup;
		}
	}
?>