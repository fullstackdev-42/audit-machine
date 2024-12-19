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

	//get data from database
	$dbh 		= la_connect_db();
	$ssl_suffix = la_get_ssl_suffix();

	$form_id    = (int) trim($_REQUEST['id']);

	$company_id = (int) $_SESSION['company_user_id'];
	$record_id = (int) $_SESSION['company_user_id'];


	if(!empty($_POST['review_submit']) || !empty($_POST['review_submit_x'])){ //if form submitted

		//commit data from review table to actual table
		//however, we need to check if this form has payment enabled or not

		//if the form doesn't have any payment enabled, continue with commit and redirect to success page
		$form_properties['payment_enable_merchant'] = 1;
		if($form_properties['payment_enable_merchant'] != 1){

			$form_params = array();
			$form_params['integration_method'] = 'iframe';

			$commit_result = la_commit_form_review($dbh,$form_id,$record_id,$form_params);

			unset($_SESSION['review_id']);

			if(empty($commit_result['form_redirect'])){
				header("Location: /auditprotocol/confirm_embed.php?id={$form_id}&done=1");
				exit;
			}else{
				header("Location: /auditprotocol/confirm_embed.php?id={$form_id}&done=1");
				/*echo "<script type=\"text/javascript\">top.location.replace('{$commit_result['form_redirect']}')</script>";*/
				exit;
			}
		}
		else{
			//if the form has payment enabled, continue commit and redirect to payment page
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

				$form_params = array();
				$form_params['integration_method'] = 'iframe';

				$commit_result = la_commit_form_review($dbh,$form_id,$record_id,$commit_options,$form_params);
				$_SESSION['la_success_entry_id'] = (int) $commit_result['record_insert_id'];
				$query = "insert into ".LA_TABLE_PREFIX."form_submission_details (`detail_id`, `company_id`, `form_id`, `id_of_form_entry`) values (null, :company_id, :form_id, :id_of_form_entry)";
				$params = array();
				$params[':company_id'] = (int)$company_id;
				$params[':form_id'] = (int)$form_id;
				$params[':id_of_form_entry'] = (int)$commit_result['record_insert_id'];
				la_do_query($query,$params,$dbh);
			}

			unset($_SESSION['review_id']);
			$_SESSION['la_form_completed'][$form_id] = 1;
			/********************************************************************************************/

			header("Location: embed.php?id={$form_id}&done=1");
			exit;
		}

	}elseif(!empty($_POST['review_back']) || !empty($_POST['review_back_x'])){
		//go back to form
		header("Location: embed.php?id={$form_id}&la_page=0");
		exit;
	}else{

		if(empty($form_id)){
			die('ID required.');
		}

		if(!empty($_GET['done']) && !empty($_SESSION['la_form_completed'][$form_id])){

			/**********************************************************************/
			{
				$query = "select id_of_form_entry from ".LA_TABLE_PREFIX."form_submission_details where `company_id` = :company_id and `form_id` = :form_id";
				$params = array();
				$params[':company_id'] = (int)$company_id;
				$params[':form_id']    = (int)$form_id;
				$resultset = la_do_query($query,$params,$dbh);
				$rowdata   = la_do_fetch_result($resultset);

				if(empty($rowdata['id_of_form_entry']) || !isset($rowdata['id_of_form_entry'])){
					$query = "insert into ".LA_TABLE_PREFIX."form_submission_details (`detail_id`, `company_id`, `form_id`, `id_of_form_entry`) values (null, :company_id, :form_id, :id_of_form_entry)";
					$params = array();
					$params[':company_id'] = (int) $company_id;
					$params[':form_id']    = (int) $form_id;
					$params[':id_of_form_entry'] = (int) $_SESSION['la_success_entry_id'];
					la_do_query($query,$params,$dbh);
				}

				$query = "update ".LA_TABLE_PREFIX."form_payment_check set form_counter = (form_counter - 1) where form_id = $form_id and company_id = $company_id";
				$params = array();
				la_do_query($query,$params,$dbh);
			}
		}else{
			$form_params = array();
			$form_params['integration_method'] = 'iframe';
			$from_page_num = (int) $_GET['la_page_from'];
			if(empty($from_page_num)){
				$form_page_num = 1;
			}
			$markup = la_display_form_review($dbh,$form_id,$record_id,$from_page_num,$form_params);
		}
	}


	header("Content-Type: text/html; charset=UTF-8");
	echo $markup;

?>
