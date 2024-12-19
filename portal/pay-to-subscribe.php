<?php
/********************************************************************************
IT Audit Machine
Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com
More info at: http://lazarusalliance.com
********************************************************************************/

require('includes/init.php');
require('includes/language.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/view-functions.php');
require('includes/users-functions.php');
require('includes/post-functions.php');
require('paypal-client.php');

$dbh                     = la_connect_db();
$form_id                 = (int) trim($_GET['id']);
$query 	                 = "select form_name, payment_price_name, payment_enable_merchant, payment_merchant_type, payment_price_amount, payment_paypal_rest_live_clientid, payment_paypal_rest_live_secret_key from `".LA_TABLE_PREFIX."forms` where form_id=?";
$params                  = array($form_id);
$sth                     = la_do_query($query,$params,$dbh);
$row                     = la_do_fetch_result($sth);
$form_name               = $row['form_name'];
$payment_price_name      = $row['payment_price_name'];
$payment_enable_merchant = $row['payment_enable_merchant'];
$payment_merchant_type   = $row['payment_merchant_type'];
$payment_price_amount    = $row['payment_price_amount'];
$payment_description     = $row['payment_price_name'];
$paypalClientID          = $row['payment_paypal_rest_live_clientid'];
$paypalSecretID          = $row['payment_paypal_rest_live_secret_key'];
$userEntities            = getEntityIds($dbh, $_SESSION['la_client_user_id']);

if (count($userEntities)) {
	foreach (array_keys($userEntities, '0') as $key) {
		unset($userEntities[$key]);
	}
}

if (!empty($payment_enable_merchant) && $payment_price_amount > 0) {
	if (!empty($form_id) && $form_id != 0 && $payment_merchant_type == 'paypal_standard') { // sometime payment_merchant_type = paypal_standard
		$clientID = $_SESSION['la_client_client_id'];
		$userID = $_SESSION['la_client_user_id'];
		$form_redirect = generatePayPalURL($form_name, $payment_price_name, $payment_price_amount, $form_id, $clientID, $userID, $paypalClientID, $paypalSecretID);
		header("Location: " . $form_redirect);
		exit();
	} else { // payment not configured correctly
		echo "$form_id<br>";
		echo "$payment_merchant_type<br>";
		echo "Error during form subscription process!<br>";
		echo "The form payment settings need to be updated by the adminstrator. Please contact your ITAM support.";
		exit();
	}
} else { // grant the user access to the form without payment (form is free)
	if (count($userEntities)) {
		foreach ($userEntities as $company_id) {
			$query                   = "INSERT INTO `".LA_TABLE_PREFIX."form_payment_check` (chk_id, form_id, company_id, payment_date, form_counter) VALUES (null, :form_id, :company_id, :payment_date, :form_counter)";
			$params                  = array();
			$params[':form_id']      = $form_id;
			$params[':company_id']   = $company_id;
			$params[':payment_date'] = time();
			$params[':form_counter'] = 1;
			la_do_query($query,$params,$dbh);
		
			$query                 = "INSERT INTO ".LA_TABLE_PREFIX."ask_client_forms (`client_id`, `form_id`) VALUES (:company_id, :form_id)";
			$params                = array();
			$params[':form_id']    = $form_id;
			$params[':company_id'] = $company_id;
			la_do_query($query,$params,$dbh);
		}
		header("Location: /portal/imported_report_list.php?form_id=".$form_id);
		exit();
	}
}
?>
