<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');

	header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");

	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/common-validator.php');
	require('includes/view-functions.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/helper-functions.php');
	require('includes/theme-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('lib/recaptchalib.php');
	require('lib/php-captcha/php-captcha.inc.php');
	require('lib/text-captcha.php');
	require('hooks/custom_hooks.php');

	//get data from database
	$dbh = la_connect_db();
	$ssl_suffix = la_get_ssl_suffix();

	if(la_is_form_submitted()){ //if form submitted
		$input_array   = la_sanitize($_POST);
		$submit_result = la_process_form($dbh,$input_array);

		if(!isset($input_array['password'])){ //if normal form submitted

			if($submit_result['status'] === true){
				if(!empty($submit_result['form_resume_url'])){ //the user saving a form, display success page with the resume URL
					$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];

					header("Location: ?id={$input_array['form_id']}&done=1");
					exit;
				}else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
					$target_page_id = $submit_result['target_page_id'];

					if(is_numeric($target_page_id)){
						header("Location: ?id={$input_array['form_id']}&la_page={$target_page_id}");
						exit;
					}else if($target_page_id == 'payment'){
						//redirect to payment page, based on selected merchant
						$form_properties = la_get_form_properties($dbh,$input_array['form_id'],array('payment_merchant_type'));

						if(in_array($form_properties['payment_merchant_type'], array('stripe','authorizenet','paypal_rest','braintree'))){
							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];

							header("Location: payment_embed.php?id={$input_array['form_id']}");
							exit;
						}else if($form_properties['payment_merchant_type'] == 'paypal_standard'){
							echo "<script type=\"text/javascript\">top.location.replace('{$submit_result['form_redirect']}')</script>";
							exit;
						}
					}else if($target_page_id == 'review'){
						if(!empty($submit_result['origin_page_number'])){
							$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
						}

						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm_embed.php?id={$input_array['form_id']}{$page_num_params}");
						exit;
					}else if($target_page_id == 'success'){
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: ?id={$input_array['form_id']}&done=1");
							exit;
						}else{
							echo "<script type=\"text/javascript\">top.location.replace('{$submit_result['form_redirect']}')</script>";
							exit;
						}
					}

				}else if(!empty($submit_result['review_id'])){ //redirect to review page
					if(!empty($submit_result['origin_page_number'])){
						$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
					}

					$_SESSION['review_id'] = $submit_result['review_id'];
					header("Location: confirm_embed.php?id={$input_array['form_id']}{$page_num_params}");
					exit;
				}else{
					if(!empty($submit_result['next_page_number'])){ //redirect to the next page number
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

						header("Location: ?id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}");
						exit;
					}else{ //otherwise display success message or redirect to the custom redirect URL or payment page

						if(la_is_payment_has_value($dbh,$input_array['form_id'],$submit_result['entry_id'])){
							//redirect to credit card payment page, if the merchant is being enabled and the amount is not zero

							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];

							header("Location: payment_embed.php?id={$input_array['form_id']}");
							exit;
						}else{
							//redirect to success page
							if(empty($submit_result['form_redirect'])){
								header("Location: ?id={$input_array['form_id']}&done=1");
								exit;
							}else{
								echo "<script type=\"text/javascript\">top.location = '{$submit_result['form_redirect']}'</script>";
								exit;
							}
						}

					}
				}
			}else if($submit_result['status'] === false){ //there are errors, display the form again with the errors
				$old_values 	= $submit_result['old_values'];
				$custom_error 	= @$submit_result['custom_error'];
				$error_elements = $submit_result['error_elements'];

				$form_params = array();
				$form_params['page_number'] = $input_array['page_number'];
				$form_params['populated_values'] = $old_values;
				$form_params['error_elements'] = $error_elements;
				$form_params['custom_error'] = $custom_error;
				$form_params['integration_method'] = 'iframe';

				$markup = la_display_form($dbh,$input_array['form_id'],$form_params);
			}
		}else{ //if password form submitted

			if($submit_result['status'] === true){ //on success, display the form
				$form_params = array();
				$form_params['integration_method'] = 'iframe';

				$markup = la_display_form($dbh,$input_array['form_id'],$form_params);
			}else{
				$custom_error = $submit_result['custom_error']; //error, display the pasword form again

				$form_params = array();
				$form_params['custom_error'] = $custom_error;
 				$form_params['integration_method'] = 'iframe';

 				$markup = la_display_form($dbh,$input_array['form_id'],$form_params);
			}
		}
	}else{
		$form_id 		= (int) trim($_GET['id']);
		$page_number	= (int) trim($_GET['la_page']);

		$page_number 	= la_verify_page_access($form_id,$page_number);

		$resume_key		= trim($_GET['la_resume']);
		if(!empty($resume_key)){
			$_SESSION['la_form_resume_key'][$form_id] = $resume_key;
		}

		if(!empty($_GET['done']) && (!empty($_SESSION['la_form_completed'][$form_id]) || !empty($_SESSION['la_form_resume_url'][$form_id]))){

			$form_params = array();
			$form_params['integration_method'] = 'iframe';

			$markup = la_display_success($dbh,$form_id,$form_params);
		}else{
			$form_params = array();
			$form_params['page_number'] = $page_number;
			$form_params['integration_method'] = 'iframe';

			$markup = la_display_form($dbh,$form_id,$form_params);
		}
	}

	header("Content-Type: text/html; charset=UTF-8");
	echo $markup;

?>
