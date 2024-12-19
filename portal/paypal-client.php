<?php
require '../itam-shared/PayPal-PHP-SDK/autoload.php';

function generatePayPalURL($form_name, $payment_price_name, $paymentAmount, $formID, $clientID, $userID, $paypalClientID, $paypalSecretID) {
    $payer = new \PayPal\Api\Payer();
    $payer->setPaymentMethod("paypal");

    $item1 = new \PayPal\Api\Item();
    $item1->setName($payment_price_name)
    ->setCurrency('USD')
    ->setQuantity(1)
    ->setSku($formID)
    ->setPrice($paymentAmount);

    $itemList = new \PayPal\Api\ItemList();
    $itemList->setItems(array($item1));

    $amount = new \PayPal\Api\Amount();
    $amount->setCurrency("USD")
    ->setTotal($paymentAmount);
    
    $transaction = new \PayPal\Api\Transaction();
    $transaction->setAmount($amount)
    ->setItemList($itemList)
    ->setDescription("Payment description")
    ->setInvoiceNumber(uniqid());
    
    $hostDomain = $_SERVER['HTTP_HOST'];
    if (!strpos($hostDomain, 'http')) {
        $hostDomain = 'https://' . $hostDomain;
    }

    $redirectUrls = new \PayPal\Api\RedirectUrls();
    $redirectUrls->setReturnUrl($hostDomain . '/portal/paypal-return.php?success=true&formID=' . $formID)
    ->setCancelUrl($hostDomain . '/portal/paypal-return.php?success=false');

    $payment = new \PayPal\Api\Payment();
    $payment->setIntent("sale")
    ->setPayer($payer)
    ->setRedirectUrls($redirectUrls)
    ->setTransactions(array($transaction));    
    
    $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential($paypalClientID, $paypalSecretID)
    );

    $apiContext->setConfig(
        array(
            'mode' => 'live',
            )
        );
        
        try {
            // try with live credentials
            $payment->create($apiContext);
            echo $payment;
            return $payment->getApprovalLink();
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            // if live failed, key might be sandbox
            if(strpos($ex->getData(), "error")) {
                $apiContext->setConfig(
                    array(
                        'mode' => 'sandbox',
                        'log.LogEnabled' => true,
                        'log.FileName' => 'PayPal.log',
                        'log.LogLevel' => 'DEBUG',
                )
            );
            
            try { // try again using sandbox
                $payment->create($apiContext);
                echo $payment;
                return $payment->getApprovalLink();
            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                // if still fails, then show error
                echo $ex->getData();
                return false;
                exit();
            }
        }
    }
}
