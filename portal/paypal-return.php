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
require('includes/check-client-session-ask.php');
require('../itam-shared/PayPal-PHP-SDK/autoload.php');

use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;

if ($_REQUEST['success'] == 'true'
    && $_REQUEST['formID']
    && $_REQUEST['paymentId']
    && $_REQUEST['token']
    && $_REQUEST['PayerID']) {

    $entityID  = $_SESSION['la_client_client_id'];
    $formID    = $_REQUEST['formID'];
    $paymentId = $_REQUEST['paymentId'];

    $paypalClientID = getPayPalClientID($dbh, $formID);
    $paypalSecretID = getPayPalSecretID($dbh, $formID);
    $apiContext     = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($paypalClientID, $paypalSecretID));
    
    $payment;
    try {
        $apiContext->setConfig(array('mode' => 'live'));
        $payment = Payment::get($paymentId, $apiContext);
    } catch (\PayPal\Exception\PayPalConnectionException $ex) {
        $apiContext->setConfig(array('mode' => 'sandbox'));
        $payment = Payment::get($paymentId, $apiContext);
    }

    $transactionArray = $payment->getTransactions();
    $transaction      = $transactionArray[0];
    $formID           = $transaction->getCustom();
    $formAccess       = grantEntityFormAccess($dbh, $formID, $entityID);
    
    if ($formAccess == true) {
        $hostDomain = $_SERVER['HTTP_HOST'];
        if (!strpos($hostDomain, 'https')) {
            $hostDomain = 'http://'.$hostDomain;
        }
        $form_redirect = $hostDomain.'/portal/view.php?id='.$formID;
        header("Location: ".$form_redirect);
        exit();
    } else {
        echo 'An error occurred while granting access to the form. Contact the system administrator';
        exit();
    }
} else {
    echo "There was an error with the PayPal payment.";
}
?>
