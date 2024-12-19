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

	require('includes/language.php');
	require('includes/common-validator.php');
	require('includes/view-functions.php');
	require('includes/theme-functions.php');
	require('includes/post-functions.php');
	require('includes/entry-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('hooks/custom_hooks.php');

	/******************************************************/

	//get data from database
	$dbh 		= la_connect_db();
	$ssl_suffix = la_get_ssl_suffix();

	$form_id    = (int) noHTML(trim($_REQUEST['id']));
	$company_id = (int) $_SESSION['la_client_entity_id'];

	if(!empty($_POST['review_submit']) || !empty($_POST['review_submit_x'])){ //if form submitted

		//commit data from review table to actual table
		//however, we need to check if this form has payment enabled or not

		//if the form doesn't have any payment enabled, continue with commit and redirect to success page
		$form_properties['payment_enable_merchant'] = 1;
		if($form_properties['payment_enable_merchant'] != 1){
			$commit_result = la_commit_form_review($dbh,$form_id,$record_id);

			unset($_SESSION['review_id']);

			if(empty($commit_result['form_redirect'])){
				header("Location: /portal/confirm.php?id={$form_id}&done=1");
				exit;
			}else{
				header("Location: /portal/confirm.php?id={$form_id}&done=1");
				exit;
			}
		}
		else{
			//if the form has payment enabled, continue commit and redirect to payment page
			//$record_id = $_SESSION['review_id'];
			$commit_options = array();
			//delay notifications only available on some merchants
			if(!empty($form_properties['payment_delay_notifications']) && in_array($form_properties['payment_merchant_type'], array('stripe','paypal_standard','authorizenet','paypal_rest','braintree'))){
				$commit_options['send_notification'] = false;
			}

			/*************************************new added**********************************************/
			$query = "select id_of_form_entry from ".LA_TABLE_PREFIX."form_submission_details where `company_id` = :company_id and `form_id` = :form_id";
			$params = array();
			$params[':company_id'] = (int)$company_id;
			$params[':form_id'] = (int)$form_id;
			$resultset = la_do_query($query,$params,$dbh);
			$rowdata = la_do_fetch_result($resultset);

			$_SESSION['la_success_entry_id'] = (int) $_SESSION['company_user_id'];;
			if(empty($rowdata['id_of_form_entry']) && !empty($row)){
				$commit_result = la_commit_form_review($dbh,$form_id,$record_id,$commit_options);
				$_SESSION['la_success_entry_id'] = (int) $commit_result['record_insert_id'];
			}

			unset($_SESSION['review_id']);
			$_SESSION['la_form_completed'][$form_id] = 1;
			/********************************************************************************************/
			header("Location: view.php?id={$form_id}&done=1");
			exit;
		}

	}
	elseif(!empty($_POST['review_back']) || !empty($_POST['review_back_x'])){
		if(isset($_SESSION['casecade_back_session'])){
			if(isset($_SESSION['casecade_back_session'][$_POST['id']][$_POST['la_page_from']])){
				header("Location: view.php?".$_SESSION['casecade_back_session'][$_POST['id']][$_POST['la_page_from']]);
			}
		}else{
			header("Location: view.php?id={$form_id}&la_page=0");
			exit;
		}
	}else{

		if(empty($form_id)){
			die('ID required.');
		}

		$from_page_num = (int) isset($_GET['la_page_from']) ? "" : 1;

		if(empty($from_page_num)){
			$form_page_num = 1;
		}

		$markup = la_display_form_review($dbh,$form_id,$record_id,$from_page_num);
	}

	header("Content-Type: text/html; charset=UTF-8");
	echo $markup;
