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
	
	$form_id = (int) la_sanitize($_REQUEST['id']);
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: restricted.php");
			exit;
		}
	}
	
	//load the payment settings property from ap_forms table
	$payment_properties = new stdClass();
	$jquery_data_code = '';

	$query 	= "select 
					 form_name,
					 payment_enable_merchant,
					 payment_merchant_type,
					 ifnull(payment_paypal_email,'') payment_paypal_email,
					 payment_paypal_language,
					 payment_currency,
					 payment_show_total,
					 payment_total_location,
					 payment_enable_recurring,
					 payment_recurring_cycle,
					 payment_recurring_unit,
					 payment_enable_trial,
					 payment_trial_period,
					 payment_trial_unit,
					 payment_trial_amount,
					 payment_price_type,
					 payment_price_amount,
					 payment_price_name,
					 payment_stripe_live_secret_key,
					 payment_stripe_live_public_key,
					 payment_stripe_test_secret_key,
					 payment_stripe_test_public_key,
					 payment_stripe_enable_test_mode,
					 payment_authorizenet_live_apiloginid,
					 payment_authorizenet_live_transkey,
					 payment_authorizenet_test_apiloginid,
					 payment_authorizenet_test_transkey,
					 payment_authorizenet_enable_test_mode,
					 payment_authorizenet_save_cc_data,
					 payment_braintree_live_merchant_id,
					 payment_braintree_live_public_key,
					 payment_braintree_live_private_key,
					 payment_braintree_live_encryption_key,
					 payment_braintree_test_merchant_id,
					 payment_braintree_test_public_key,
					 payment_braintree_test_private_key,
					 payment_braintree_test_encryption_key,
					 payment_braintree_enable_test_mode,
					 payment_paypal_rest_live_clientid,
					 payment_paypal_rest_live_secret_key,
					 payment_paypal_rest_test_clientid,
					 payment_paypal_rest_test_secret_key,
					 payment_paypal_rest_enable_test_mode,
					 payment_paypal_enable_test_mode,
					 payment_enable_invoice,
					 payment_invoice_email,
					 payment_delay_notifications,
					 payment_ask_billing,
					 payment_ask_shipping,
					 payment_enable_tax,
					 payment_tax_rate,
					 payment_enable_discount,
					 payment_discount_type,
					 payment_discount_code,
					 payment_discount_amount,
					 payment_discount_element_id,
					 payment_discount_max_usage,
					 payment_discount_expiry_date  
			     from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){
		$row['form_name'] = la_trim_max_length($row['form_name'],50);	
		$form_name = noHTML($row['form_name']);
		
		$payment_properties->form_id = $form_id;
		$payment_properties->enable_merchant = (int) $row['payment_enable_merchant'];
		$payment_properties->merchant_type 	 = $row['payment_merchant_type'];
		$payment_properties->paypal_email 	 = $row['payment_paypal_email'];
		$payment_properties->paypal_language = $row['payment_paypal_language'];
		$payment_properties->paypal_enable_test_mode  = (int) $row['payment_paypal_enable_test_mode'];
		
		$payment_properties->currency 		  = $row['payment_currency'];
		$payment_properties->show_total 	  = (int) $row['payment_show_total'];
		$payment_properties->total_location   = $row['payment_total_location'];
		$payment_properties->enable_recurring = (int) $row['payment_enable_recurring'];
		$payment_properties->recurring_cycle  = (int) $row['payment_recurring_cycle'];
		$payment_properties->recurring_unit   = $row['payment_recurring_unit'];

		$payment_properties->enable_trial = (int) $row['payment_enable_trial'];
		$payment_properties->trial_period = (int) $row['payment_trial_period'];
		$payment_properties->trial_unit   = $row['payment_trial_unit'];
		$payment_properties->trial_amount = $row['payment_trial_amount'];

		$payment_properties->enable_tax = (int) $row['payment_enable_tax'];
		$payment_properties->tax_rate 	= $row['payment_tax_rate'];

		$payment_properties->enable_discount = (int) $row['payment_enable_discount'];
		$payment_properties->discount_type 	 = $row['payment_discount_type'];
		$payment_properties->discount_code 	 = $row['payment_discount_code'];
		$payment_properties->discount_amount = (float) $row['payment_discount_amount'];
		$payment_properties->discount_element_id = (int) $row['payment_discount_element_id'];
		$payment_properties->discount_max_usage = (int) $row['payment_discount_max_usage'];
		$payment_properties->discount_expiry_date 	= $row['payment_discount_expiry_date'];

		$payment_properties->stripe_live_secret_key   = trim($row['payment_stripe_live_secret_key']);
		$payment_properties->stripe_live_public_key   = trim($row['payment_stripe_live_public_key']);
		$payment_properties->stripe_test_secret_key   = trim($row['payment_stripe_test_secret_key']);
		$payment_properties->stripe_test_public_key   = trim($row['payment_stripe_test_public_key']);
		$payment_properties->stripe_enable_test_mode  = (int) $row['payment_stripe_enable_test_mode'];

		$payment_properties->authorizenet_live_apiloginid   = trim($row['payment_authorizenet_live_apiloginid']);
		$payment_properties->authorizenet_live_transkey   	= trim($row['payment_authorizenet_live_transkey']);
		$payment_properties->authorizenet_test_apiloginid   = trim($row['payment_authorizenet_test_apiloginid']);
		$payment_properties->authorizenet_test_transkey   	= trim($row['payment_authorizenet_test_transkey']);
		$payment_properties->authorizenet_enable_test_mode  = (int) $row['payment_authorizenet_enable_test_mode'];
		$payment_properties->authorizenet_save_cc_data  	= (int) $row['payment_authorizenet_save_cc_data'];

		$payment_properties->braintree_live_merchant_id    = trim($row['payment_braintree_live_merchant_id']);
		$payment_properties->braintree_live_public_key     = trim($row['payment_braintree_live_public_key']);
		$payment_properties->braintree_live_private_key    = trim($row['payment_braintree_live_private_key']);
		$payment_properties->braintree_live_encryption_key = trim($row['payment_braintree_live_encryption_key']);
		$payment_properties->braintree_test_merchant_id    = trim($row['payment_braintree_test_merchant_id']);
		$payment_properties->braintree_test_public_key     = trim($row['payment_braintree_test_public_key']);
		$payment_properties->braintree_test_private_key    = trim($row['payment_braintree_test_private_key']);
		$payment_properties->braintree_test_encryption_key = trim($row['payment_braintree_test_encryption_key']);
		$payment_properties->braintree_enable_test_mode    = (int) $row['payment_braintree_enable_test_mode'];
		
		$payment_properties->paypal_rest_live_clientid  	= trim($row['payment_paypal_rest_live_clientid']);
		$payment_properties->paypal_rest_live_secret_key  	= trim($row['payment_paypal_rest_live_secret_key']);
		$payment_properties->paypal_rest_test_clientid  	= trim($row['payment_paypal_rest_test_clientid']);
		$payment_properties->paypal_rest_test_secret_key  	= trim($row['payment_paypal_rest_test_secret_key']);
		$payment_properties->paypal_rest_enable_test_mode  	= (int) $row['payment_paypal_rest_enable_test_mode'];

		$payment_properties->enable_invoice  	  = (int) $row['payment_enable_invoice'];
		$payment_properties->delay_notifications  = (int) $row['payment_delay_notifications'];
		$payment_properties->ask_billing  		  = (int) $row['payment_ask_billing'];
		$payment_properties->ask_shipping  		  = (int) $row['payment_ask_shipping'];
		$payment_properties->invoice_email 		  = $row['payment_invoice_email'];
		
		$payment_properties->price_type   = $row['payment_price_type'];
		$payment_properties->price_amount = (float) $row['payment_price_amount'];
		$payment_properties->price_name   = $row['payment_price_name'];
		
		if(empty($payment_properties->price_name)){
			$payment_properties->price_name = $form_name.' Fee';
		}
		
		//payment_enable_merchant has 3 possible values:
		// -1 : disabled
		//  0 : disabled
		//  1 : enabled
		//the -1 is the default for all newly created form
		//once the user save the payment settings page, the only possible values are 0 or 1
		//we put -1 as an option, so that when the first time user load the payment settings page, it will enable the payment setting by default
		/*if($payment_properties->enable_merchant === -1){
			$payment_properties->enable_merchant = 1;
		}*/
	}
	
	//get the currency symbol first
	switch($payment_properties->currency){
		case 'USD' : $currency_symbol = '&#36;';break;
		case 'EUR' : $currency_symbol = '&#8364;';break;
		case 'GBP' : $currency_symbol = '&#163;';break;
		case 'AUD' : $currency_symbol = 'A&#36;';break;
		case 'CAD' : $currency_symbol = 'C&#36;';break;
		case 'JPY' : $currency_symbol = '&#165;';break;
		case 'THB' : $currency_symbol = '&#3647;';break;
		case 'HUF' : $currency_symbol = '&#70;&#116;';break;
		case 'CHF' : $currency_symbol = 'CHF';break;
		case 'CZK' : $currency_symbol = '&#75;&#269;';break;
		case 'SEK' : $currency_symbol = 'kr';break;
		case 'DKK' : $currency_symbol = 'kr';break;
		case 'NOK' : $currency_symbol = 'kr';break;
		case 'PHP' : $currency_symbol = '&#36;';break;
		case 'IDR' : $currency_symbol = 'Rp';break;
		case 'MYR' : $currency_symbol = 'RM';break;
		case 'ZAR' : $currency_symbol = 'R';break;
		case 'PLN' : $currency_symbol = '&#122;&#322;';break;
		case 'BRL' : $currency_symbol = 'R&#36;';break;
		case 'HKD' : $currency_symbol = 'HK&#36;';break;
		case 'MXN' : $currency_symbol = 'Mex&#36;';break;
		case 'TWD' : $currency_symbol = 'NT&#36;';break;
		case 'TRY' : $currency_symbol = 'TL';break;
	}
	

//initialize the discount field with the first 'single line text' field on the form, if the field is currently not being set
if(empty($payment_properties->discount_element_id) && !empty($coupon_code_fields)){
	$payment_properties->discount_element_id = $coupon_code_fields[1]['value'];
}

$json_payment_properties = json_encode($payment_properties);
$jquery_data_code .= "\$('#ps_main_list').data('payment_properties',{$json_payment_properties});\n";

$header_data =<<<EOT
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
EOT;
	
$current_nav_tab = 'manage_forms';
require('includes/header.php'); 
	
?>

<div id="content" class="full">
  <div class="post payment_settings">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> Payment Settings</h2>
          <p>Configure payment options for your form</p>
        </div>
        <div style="float: right;margin-right: 5px"> <a href="#" id="button_save_payment" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </a> </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <div class="content_body">
      <ul id="ps_main_list">
        <li>
          <div id="ps_box_merchant_settings" class="ps_box_main gradient_blue">
            <div class="ps_box_meta">
              <h1>1.</h1>
              <h6>Merchant Settings</h6>
            </div>
            <div class="ps_box_content"> <span>
              <input id="ps_enable_merchant" class="checkbox" value="" type="checkbox" style="margin-left: 0px" <?php if(!empty($payment_properties->enable_merchant)){ echo 'checked="checked"'; } ?>>
              <label class="choice" for="ps_enable_merchant">Enable Merchant</label>
              <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Disabling this option will turn off the payment functionality of your form."/> </span>
              <label class="description" for="ps_select_merchant" style="margin-top: 10px"> Select a Merchant <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="A merchant will process transactions on your form and authorize the payments."/> </label>
              <select class="select" id="ps_select_merchant" style="width: 60%" autocomplete="off">
              
                <option selected="selected" value="paypal_rest">PayPal API (Enabled)</option>

								<!--<option <?php //if(($payment_properties->merchant_type == 'paypal_rest'){ echo 'selected="selected"'; } ?> value="paypal_rest">PayPal API (Enabled)</option>-->
								<!--<option <?php //if($payment_properties->merchant_type == 'stripe'){ echo 'selected="selected"'; } ?> value="stripe">Stripe (Disabled)</option>-->
                <!--<option <?php //if($payment_properties->merchant_type == 'paypal_standard'){ echo 'selected="selected"'; } ?> value="paypal_standard">PayPal Standard (Disabled)</option>-->
                <!--<option <?php //if($payment_properties->merchant_type == 'authorizenet'){ echo 'selected="selected"'; } ?> value="authorizenet">Authorize.net (Disabled)</option>-->
                <!--<option <?php //if($payment_properties->merchant_type == 'braintree'){ echo 'selected="selected"'; } ?> value="braintree">Braintree (Disabled)</option>-->
                <!--<option <?php //if($payment_properties->merchant_type == 'check'){ echo 'selected="selected"'; } ?> value="check">Check / Cash (Disabled)</option>-->
              </select>

              <div id="ps_paypal_rest_options" class="merchant_options" <?php if($payment_properties->merchant_type != 'paypal_rest'){ echo 'style="display: none"'; } ?>>
                <div id="ps_paypal_rest_live_keys" <?php if(!empty($payment_properties->paypal_rest_enable_test_mode)){ echo 'style="display: none;"'; } ?>>
                  <label class="description" for="ps_paypal_rest_live_clientid">Client ID <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="These are keys provided by PayPal. Login to https://developer.paypal.com and go to Applications -> My apps -> Create App. Follow the instruction and create an app with the name 'itauditmachine'. You'll get all your keys there."/></label>
                  <input id="ps_paypal_rest_live_clientid" name="ps_paypal_rest_live_clientid" class="element text large" value="<?php echo htmlspecialchars($payment_properties->paypal_rest_live_clientid); ?>" type="text">
                  <label class="description" for="ps_paypal_rest_live_secret_key">Secret Key <span class="required">*</span></label>
                  <input id="ps_paypal_rest_live_secret_key" name="ps_paypal_rest_live_secret_key" class="element text large" value="<?php echo htmlspecialchars($payment_properties->paypal_rest_live_secret_key); ?>" type="text">
                </div>
                <div id="ps_paypal_rest_test_keys">
                  <!--
									<input id="ps_paypal_rest_enable_test_mode" <?php if(!empty($payment_properties->paypal_rest_enable_test_mode)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px;">
                  <label class="choice" for="ps_paypal_rest_enable_test_mode">Enable Test Mode</label>
									<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, all credit card transactions won't go through the actual credit card network. You can use some test cards numbers (provided by PayPal) to simulate a successful transaction."/>
									-->
                  <p>***Test mode is enabled by using a Sandbox Client ID</p>
									
                  <div id="ps_paypal_rest_test_keys_div" <?php if(empty($payment_properties->paypal_rest_enable_test_mode)){ echo 'style="display: none;"'; } ?>>
                    <label class="description" for="ps_paypal_rest_test_clientid" style="margin-top: 10px">Test Client ID <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="These are keys provided by PayPal. Login to https://developer.paypal.com and go to Applications -> My apps -> Create App. Follow the instruction and create an app with the name 'itauditmachine'. You'll get all your keys there."/></label>
                    <input id="ps_paypal_rest_test_clientid" name="ps_paypal_rest_test_clientid" class="element text large" value="<?php echo htmlspecialchars($payment_properties->paypal_rest_test_clientid); ?>" type="text">
                    </span>
                    <label class="description" for="ps_paypal_rest_test_secret_key" style="margin-top: 15px">Test Secret Key</label>
                    <input id="ps_paypal_rest_test_secret_key" name="ps_paypal_rest_test_secret_key" class="element text large" value="<?php echo htmlspecialchars($payment_properties->paypal_rest_test_secret_key); ?>" type="text">
                    </span> </div>
                </div>
              </div>

              <div id="ps_stripe_info" class="merchant_options" <?php if($payment_properties->merchant_type != 'stripe'){ echo 'style="display: none"'; } ?>> Stripe is the easiest way to start receiving credit card payments. <a class="blue_dotted" href="https://stripe.com/" target="_blank">Learn More</a> </div>
              <div id="ps_check_options" class="merchant_options" <?php if($payment_properties->merchant_type != 'check'){ echo 'style="display: none"'; } ?>> This allows you to create payment forms (with option to have total calculations) without having actual payment processor integration. </div>
            </div>
          </div>
        </li>
        <li class="ps_arrow" <?php if($payment_properties->enable_merchant === 0){ echo 'style="display: none;"'; } ?>><img src="images/icons/33_red.png" /></li>
        <li <?php if($payment_properties->enable_merchant === 0){ echo 'style="display: none;"'; } ?>>
          <div id="ps_box_payment_options" class="ps_box_main gradient_blue">
            <div class="ps_box_meta">
              <h1>2.</h1>
              <h6>Payment Options</h6>
            </div>
            <div class="ps_box_content">
              <div id="ps_currency_paypal_div" <?php if($payment_properties->merchant_type != 'paypal_standard'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_paypal" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_paypal" name="ps_currency_paypal" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euro</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="JPY" <?php if($payment_properties->currency == 'JPY'){ echo 'selected="selected"'; } ?>>&#165; - Japanese Yen</option>
                  <option value="THB" <?php if($payment_properties->currency == 'THB'){ echo 'selected="selected"'; } ?>>&#3647; - Thai Baht</option>
                  <option value="HUF" <?php if($payment_properties->currency == 'HUF'){ echo 'selected="selected"'; } ?>>&#70;&#116; - Hungarian Forint</option>
                  <option value="CHF" <?php if($payment_properties->currency == 'CHF'){ echo 'selected="selected"'; } ?>>CHF - Swiss Francs</option>
                  <option value="SGD" <?php if($payment_properties->currency == 'SGD'){ echo 'selected="selected"'; } ?>>&#36; - Singapore Dollar</option>
                  <option value="CZK" <?php if($payment_properties->currency == 'CZK'){ echo 'selected="selected"'; } ?>>&#75;&#269; - Czech Koruna</option>
                  <option value="SEK" <?php if($payment_properties->currency == 'SEK'){ echo 'selected="selected"'; } ?>>kr - Swedish Krona</option>
                  <option value="DKK" <?php if($payment_properties->currency == 'DKK'){ echo 'selected="selected"'; } ?>>kr - Danish Krone</option>
                  <option value="NOK" <?php if($payment_properties->currency == 'NOK'){ echo 'selected="selected"'; } ?>>kr - Norwegian Krone</option>
                  <option value="PHP" <?php if($payment_properties->currency == 'PHP'){ echo 'selected="selected"'; } ?>>&#36; - Philippine Peso</option>
                  <option value="MYR" <?php if($payment_properties->currency == 'MYR'){ echo 'selected="selected"'; } ?>>RM - Malaysian Ringgit</option>
                  <option value="NZD" <?php if($payment_properties->currency == 'NZD'){ echo 'selected="selected"'; } ?>>NZ&#36; - New Zealand Dollar</option>
                  <option value="PLN" <?php if($payment_properties->currency == 'PLN'){ echo 'selected="selected"'; } ?>>&#122;&#322; - Polish Złoty</option>
                  <option value="BRL" <?php if($payment_properties->currency == 'BRL'){ echo 'selected="selected"'; } ?>>R&#36; - Brazilian Real</option>
                  <option value="HKD" <?php if($payment_properties->currency == 'HKD'){ echo 'selected="selected"'; } ?>>HK&#36; - Hong Kong Dollar</option>
                  <option value="MXN" <?php if($payment_properties->currency == 'MXN'){ echo 'selected="selected"'; } ?>>Mex&#36; - Mexican Peso</option>
                  <option value="TWD" <?php if($payment_properties->currency == 'TWD'){ echo 'selected="selected"'; } ?>>NT&#36; - Taiwan New Dollar</option>
                  <option value="TRY" <?php if($payment_properties->currency == 'TRY'){ echo 'selected="selected"'; } ?>>TL - Turkish Lira</option>
                </select>
              </div>
              <div id="ps_currency_stripe_div" <?php if($payment_properties->merchant_type != 'stripe'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_stripe" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_stripe" name="ps_currency_stripe" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euro</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="JPY" <?php if($payment_properties->currency == 'JPY'){ echo 'selected="selected"'; } ?>>&#165; - Japanese Yen</option>
                  <option value="THB" <?php if($payment_properties->currency == 'THB'){ echo 'selected="selected"'; } ?>>&#3647; - Thai Baht</option>
                  <option value="HUF" <?php if($payment_properties->currency == 'HUF'){ echo 'selected="selected"'; } ?>>&#70;&#116; - Hungarian Forint</option>
                  <option value="CHF" <?php if($payment_properties->currency == 'CHF'){ echo 'selected="selected"'; } ?>>CHF - Swiss Francs</option>
                  <option value="SGD" <?php if($payment_properties->currency == 'SGD'){ echo 'selected="selected"'; } ?>>&#36; - Singapore Dollar</option>
                  <option value="CZK" <?php if($payment_properties->currency == 'CZK'){ echo 'selected="selected"'; } ?>>&#75;&#269; - Czech Koruna</option>
                  <option value="SEK" <?php if($payment_properties->currency == 'SEK'){ echo 'selected="selected"'; } ?>>kr - Swedish Krona</option>
                  <option value="DKK" <?php if($payment_properties->currency == 'DKK'){ echo 'selected="selected"'; } ?>>kr - Danish Krone</option>
                  <option value="NOK" <?php if($payment_properties->currency == 'NOK'){ echo 'selected="selected"'; } ?>>kr - Norwegian Krone</option>
                  <option value="PHP" <?php if($payment_properties->currency == 'PHP'){ echo 'selected="selected"'; } ?>>&#36; - Philippine Peso</option>
                  <option value="ZAR" <?php if($payment_properties->currency == 'ZAR'){ echo 'selected="selected"'; } ?>>R - South African Rand</option>
                  <option value="IDR" <?php if($payment_properties->currency == 'IDR'){ echo 'selected="selected"'; } ?>>Rp - Indonesian Rupiah</option>
                  <option value="MYR" <?php if($payment_properties->currency == 'MYR'){ echo 'selected="selected"'; } ?>>RM - Malaysian Ringgit</option>
                  <option value="NZD" <?php if($payment_properties->currency == 'NZD'){ echo 'selected="selected"'; } ?>>NZ&#36; - New Zealand Dollar</option>
                  <option value="PLN" <?php if($payment_properties->currency == 'PLN'){ echo 'selected="selected"'; } ?>>&#122;&#322; - Polish Złoty</option>
                  <option value="BRL" <?php if($payment_properties->currency == 'BRL'){ echo 'selected="selected"'; } ?>>R&#36; - Brazilian Real</option>
                  <option value="HKD" <?php if($payment_properties->currency == 'HKD'){ echo 'selected="selected"'; } ?>>HK&#36; - Hong Kong Dollar</option>
                  <option value="MXN" <?php if($payment_properties->currency == 'MXN'){ echo 'selected="selected"'; } ?>>Mex&#36; - Mexican Peso</option>
                  <option value="TWD" <?php if($payment_properties->currency == 'TWD'){ echo 'selected="selected"'; } ?>>NT&#36; - Taiwan New Dollar</option>
                  <option value="TRY" <?php if($payment_properties->currency == 'TRY'){ echo 'selected="selected"'; } ?>>TL - Turkish Lira</option>
                </select>
              </div>
              <div id="ps_currency_authorizenet_div" <?php if($payment_properties->merchant_type != 'authorizenet'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_authorizenet" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_authorizenet" name="ps_currency_authorizenet" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euros
                  </optbesok ion>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                  <option value="NZD" <?php if($payment_properties->currency == 'NZD'){ echo 'selected="selected"'; } ?>>NZ&#36; - New Zealand Dollar</option>
                </select>
              </div>
              <div id="ps_currency_braintree_div" <?php if($payment_properties->merchant_type != 'braintree'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_braintree" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_braintree" name="ps_currency_braintree" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euros
                  </optbesok ion>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                </select>
              </div>
              <div id="ps_currency_paypal_rest_div" <?php if($payment_properties->merchant_type != 'paypal_rest'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_paypal_rest" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_paypal_rest" name="ps_currency_paypal_rest" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euro</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="JPY" <?php if($payment_properties->currency == 'JPY'){ echo 'selected="selected"'; } ?>>&#165; - Japanese Yen</option>
                  <option value="THB" <?php if($payment_properties->currency == 'THB'){ echo 'selected="selected"'; } ?>>&#3647; - Thai Baht</option>
                  <option value="HUF" <?php if($payment_properties->currency == 'HUF'){ echo 'selected="selected"'; } ?>>&#70;&#116; - Hungarian Forint</option>
                  <option value="CHF" <?php if($payment_properties->currency == 'CHF'){ echo 'selected="selected"'; } ?>>CHF - Swiss Francs</option>
                  <option value="SGD" <?php if($payment_properties->currency == 'SGD'){ echo 'selected="selected"'; } ?>>&#36; - Singapore Dollar</option>
                  <option value="CZK" <?php if($payment_properties->currency == 'CZK'){ echo 'selected="selected"'; } ?>>&#75;&#269; - Czech Koruna</option>
                  <option value="SEK" <?php if($payment_properties->currency == 'SEK'){ echo 'selected="selected"'; } ?>>kr - Swedish Krona</option>
                  <option value="DKK" <?php if($payment_properties->currency == 'DKK'){ echo 'selected="selected"'; } ?>>kr - Danish Krone</option>
                  <option value="NOK" <?php if($payment_properties->currency == 'NOK'){ echo 'selected="selected"'; } ?>>kr - Norwegian Krone</option>
                  <option value="PHP" <?php if($payment_properties->currency == 'PHP'){ echo 'selected="selected"'; } ?>>&#36; - Philippine Peso</option>
                  <option value="MYR" <?php if($payment_properties->currency == 'MYR'){ echo 'selected="selected"'; } ?>>RM - Malaysian Ringgit</option>
                  <option value="NZD" <?php if($payment_properties->currency == 'NZD'){ echo 'selected="selected"'; } ?>>NZ&#36; - New Zealand Dollar</option>
                  <option value="PLN" <?php if($payment_properties->currency == 'PLN'){ echo 'selected="selected"'; } ?>>&#122;&#322; - Polish Złoty</option>
                  <option value="BRL" <?php if($payment_properties->currency == 'BRL'){ echo 'selected="selected"'; } ?>>R&#36; - Brazilian Real</option>
                  <option value="HKD" <?php if($payment_properties->currency == 'HKD'){ echo 'selected="selected"'; } ?>>HK&#36; - Hong Kong Dollar</option>
                  <option value="MXN" <?php if($payment_properties->currency == 'MXN'){ echo 'selected="selected"'; } ?>>Mex&#36; - Mexican Peso</option>
                  <option value="TWD" <?php if($payment_properties->currency == 'TWD'){ echo 'selected="selected"'; } ?>>NT&#36; - Taiwan New Dollar</option>
                  <option value="TRY" <?php if($payment_properties->currency == 'TRY'){ echo 'selected="selected"'; } ?>>TL - Turkish Lira</option>
                </select>
              </div>
              <div id="ps_currency_check_div" <?php if($payment_properties->merchant_type != 'check'){ echo 'style="display: none"'; } ?>>
                <label class="description" for="ps_currency_check" style="margin-top: 2px"> Currency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the currency you would like to use to accept the payment from your clients."/> </label>
                <select class="select large" id="ps_currency_check" name="ps_currency_check" autocomplete="off">
                  <option value="USD" <?php if($payment_properties->currency == 'USD'){ echo 'selected="selected"'; } ?>>&#36; - U.S. Dollar</option>
                  <option value="EUR" <?php if($payment_properties->currency == 'EUR'){ echo 'selected="selected"'; } ?>>&#8364; - Euro</option>
                  <option value="GBP" <?php if($payment_properties->currency == 'GBP'){ echo 'selected="selected"'; } ?>>&#163; - Pound Sterling</option>
                  <option value="AUD" <?php if($payment_properties->currency == 'AUD'){ echo 'selected="selected"'; } ?>>A&#36; - Australian Dollar</option>
                  <option value="CAD" <?php if($payment_properties->currency == 'CAD'){ echo 'selected="selected"'; } ?>>C&#36; - Canadian Dollar</option>
                  <option value="JPY" <?php if($payment_properties->currency == 'JPY'){ echo 'selected="selected"'; } ?>>&#165; - Japanese Yen</option>
                  <option value="THB" <?php if($payment_properties->currency == 'THB'){ echo 'selected="selected"'; } ?>>&#3647; - Thai Baht</option>
                  <option value="HUF" <?php if($payment_properties->currency == 'HUF'){ echo 'selected="selected"'; } ?>>&#70;&#116; - Hungarian Forint</option>
                  <option value="CHF" <?php if($payment_properties->currency == 'CHF'){ echo 'selected="selected"'; } ?>>CHF - Swiss Francs</option>
                  <option value="SGD" <?php if($payment_properties->currency == 'SGD'){ echo 'selected="selected"'; } ?>>&#36; - Singapore Dollar</option>
                  <option value="CZK" <?php if($payment_properties->currency == 'CZK'){ echo 'selected="selected"'; } ?>>&#75;&#269; - Czech Koruna</option>
                  <option value="SEK" <?php if($payment_properties->currency == 'SEK'){ echo 'selected="selected"'; } ?>>kr - Swedish Krona</option>
                  <option value="DKK" <?php if($payment_properties->currency == 'DKK'){ echo 'selected="selected"'; } ?>>kr - Danish Krone</option>
                  <option value="NOK" <?php if($payment_properties->currency == 'NOK'){ echo 'selected="selected"'; } ?>>kr - Norwegian Krone</option>
                  <option value="PHP" <?php if($payment_properties->currency == 'PHP'){ echo 'selected="selected"'; } ?>>&#36; - Philippine Peso</option>
                  <option value="ZAR" <?php if($payment_properties->currency == 'ZAR'){ echo 'selected="selected"'; } ?>>R - South African Rand</option>
                  <option value="IDR" <?php if($payment_properties->currency == 'IDR'){ echo 'selected="selected"'; } ?>>Rp - Indonesian Rupiah</option>
                  <option value="MYR" <?php if($payment_properties->currency == 'MYR'){ echo 'selected="selected"'; } ?>>RM - Malaysian Ringgit</option>
                  <option value="NZD" <?php if($payment_properties->currency == 'NZD'){ echo 'selected="selected"'; } ?>>NZ&#36; - New Zealand Dollar</option>
                  <option value="PLN" <?php if($payment_properties->currency == 'PLN'){ echo 'selected="selected"'; } ?>>&#122;&#322; - Polish Złoty</option>
                  <option value="BRL" <?php if($payment_properties->currency == 'BRL'){ echo 'selected="selected"'; } ?>>R&#36; - Brazilian Real</option>
                  <option value="HKD" <?php if($payment_properties->currency == 'HKD'){ echo 'selected="selected"'; } ?>>HK&#36; - Hong Kong Dollar</option>
                  <option value="MXN" <?php if($payment_properties->currency == 'MXN'){ echo 'selected="selected"'; } ?>>Mex&#36; - Mexican Peso</option>
                  <option value="TWD" <?php if($payment_properties->currency == 'TWD'){ echo 'selected="selected"'; } ?>>NT&#36; - Taiwan New Dollar</option>
                  <option value="TRY" <?php if($payment_properties->currency == 'TRY'){ echo 'selected="selected"'; } ?>>TL - Turkish Lira</option>
                </select>
              </div>
              <div id="ps_optional_settings">
                <label class="description" style="margin-bottom: 15px"> Optional Settings </label>
                <input id="ps_show_total_amount" <?php if(!empty($payment_properties->show_total)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                <label class="choice" for="ps_show_total_amount">Show Total Amount</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Shows the total amount of the payment to the client as they are filling out the form. You can also select the location of the total amount placement within the form."/>
                <div id="ps_show_total_location_div" <?php if(empty($payment_properties->show_total)){ echo 'style="display: none"'; } ?>> Display at
                  <select class="select medium" id="ps_show_total_location" name="ps_show_total_location" autocomplete="off">
                    <option <?php if($payment_properties->total_location == 'top'){ echo 'selected="selected"'; } ?> id="ps_location_top" value="top">top</option>
                    <option <?php if($payment_properties->total_location == 'bottom'){ echo 'selected="selected"'; } ?> id="ps_location_bottom" value="bottom">bottom</option>
                    <option <?php if($payment_properties->total_location == 'top-bottom'){ echo 'selected="selected"'; } ?> id="ps_location_top_bottom" value="top-bottom">top and bottom</option>
                  </select>
                </div>
                <div style="clear: both;margin-top: 10px"></div>
                <div class="paypal_option stripe_option authorizenet_option paypal_rest_option braintree_option" <?php if(!in_array($payment_properties->merchant_type,array('stripe','paypal_standard','authorizenet','paypal_rest','braintree'))){ echo 'style="display: none"'; } ?>>
                  <input id="ps_enable_tax" <?php if(!empty($payment_properties->enable_tax)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                  <label class="choice" for="ps_enable_tax">Add Sales Tax</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Upon checkout, sales tax will automatically be added to the order total. You can define the tax rate here."/>
                  <div id="ps_tax_rate_div" <?php if(empty($payment_properties->enable_tax)){ echo 'style="display: none"'; } ?>> Tax Rate:
                    <input id="ps_tax_rate" name="ps_tax_rate" class="element text" style="width: 40px"  value="<?php echo htmlspecialchars($payment_properties->tax_rate); ?>" type="text">
                    % </div>
                </div>
                <div style="clear: both;margin-top: 10px"></div>
                <div class="paypal_option stripe_option authorizenet_option" <?php if(!in_array($payment_properties->merchant_type,array('stripe','paypal_standard','authorizenet'))){ echo 'style="display: none"'; } ?>>
                  <input id="ps_enable_recurring" <?php if(!empty($payment_properties->enable_recurring)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px;">
                  <label class="choice" for="ps_enable_recurring">Enable Recurring Payments</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, your clients will be charged automatically for every period of time."/>
                  <div id="ps_recurring_div" <?php if(empty($payment_properties->enable_recurring)){ echo 'style="display: none"'; } ?>>
                    <label class="description" style="margin-top: 5px">Charge Payment Every:</label>
                    <select id="ps_recurring_cycle">
                      <?php 
														for($i=1;$i<=10;$i++){
															if($i == $payment_properties->recurring_cycle){
																echo '<option value="'.$i.'" selected="selected">'.$i.'</option>';
															}else{
																echo '<option value="'.$i.'">'.$i.'</option>';	
															}
														}
													?>
                    </select>
                    <select id="ps_recurring_cycle_unit" <?php if(!in_array($payment_properties->merchant_type,array('paypal_standard','authorizenet'))){ echo 'style="display: none"'; }; ?>>
                      <option value="day" <?php if($payment_properties->recurring_unit == 'day'){ echo 'selected="selected"'; } ?>>Day(s)</option>
                      <option value="week" <?php if($payment_properties->recurring_unit == 'week'){ echo 'selected="selected"'; } ?>>Week(s)</option>
                      <option value="month" <?php if($payment_properties->recurring_unit == 'month'){ echo 'selected="selected"'; } ?>>Month(s)</option>
                      <option value="year" <?php if($payment_properties->recurring_unit == 'year'){ echo 'selected="selected"'; } ?>>Year(s)</option>
                    </select>
                    <select id="ps_recurring_cycle_unit_month_year" <?php if($payment_properties->merchant_type != 'stripe'){ echo 'style="display: none"'; }; ?>>
                      <option value="week" <?php if($payment_properties->recurring_unit == 'week'){ echo 'selected="selected"'; } ?>>Week(s)</option>
                      <option value="month" <?php if($payment_properties->recurring_unit == 'month'){ echo 'selected="selected"'; } ?>>Month(s)</option>
                      <option value="year" <?php if($payment_properties->recurring_unit == 'year'){ echo 'selected="selected"'; } ?>>Year(s)</option>
                    </select>
                  </div>
                </div>
                <div style="clear: both;margin-top: 10px"></div>
                <div class="paypal_option stripe_option authorizenet_option" id="ps_trial_div_container" <?php if(empty($payment_properties->enable_recurring) || !in_array($payment_properties->merchant_type, array('paypal_standard','stripe','authorizenet'))){ echo 'style="display: none"'; } ?>>
                  <input id="ps_enable_trial" <?php if(!empty($payment_properties->enable_trial)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px;">
                  <label class="choice" for="ps_enable_trial">Enable Trial Periods</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can enable trial periods to let your clients to try your subscription service before their regular subscription begin. You can set the price and duration of trial periods independently of the regular subscription price and billing cycle. Enter '0' to offer free trial."/>
                  <div id="ps_trial_div" <?php if(empty($payment_properties->enable_trial)){ echo 'style="display: none"'; } ?>>
                    <label class="description" style="margin-top: 5px">Trial Period:</label>
                    <select id="ps_trial_period">
                      <?php 
														for($i=1;$i<=10;$i++){
															if($i == $payment_properties->trial_period){
																echo '<option value="'.$i.'" selected="selected">'.$i.'</option>';
															}else{
																echo '<option value="'.$i.'">'.$i.'</option>';	
															}
														}
													?>
                    </select>
                    <select id="ps_trial_unit">
                      <option value="day" <?php if($payment_properties->trial_unit == 'day'){ echo 'selected="selected"'; } ?>>Day(s)</option>
                      <option value="week" <?php if($payment_properties->trial_unit == 'week'){ echo 'selected="selected"'; } ?>>Week(s)</option>
                      <option value="month" <?php if($payment_properties->trial_unit == 'month'){ echo 'selected="selected"'; } ?>>Month(s)</option>
                      <option value="year" <?php if($payment_properties->trial_unit == 'year'){ echo 'selected="selected"'; } ?>>Year(s)</option>
                    </select>
                    <label class="description" style="margin-top: 5px">Trial Price:</label>
                    <span class="symbol">$</span><span>
                    <input id="ps_trial_amount" name="ps_trial_amount" class="element text medium" value="<?php echo htmlspecialchars($payment_properties->trial_amount); ?>" type="text">
                    </span> </div>
                </div>
                <div style="clear: both;margin-top: 10px"></div>
                <div class="paypal_option stripe_option authorizenet_option paypal_rest_option braintree_option" id="ps_discount_div_container">
                  <input id="ps_enable_discount" <?php if(!empty($payment_properties->enable_discount)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px;">
                  <label class="choice" for="ps_enable_discount">Enable Discount</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Allows your client to enter coupon code and receive discount."/>
                  <div id="ps_discount_div" <?php if(empty($payment_properties->enable_discount)){ echo 'style="display: none"'; } ?>>
                    <ul>
                      <li>
                        <select class="select medium" id="ps_discount_type" name="ps_discount_type" autocomplete="off">
                          <option <?php if($payment_properties->discount_type == 'percent_off'){ echo 'selected="selected"'; } ?> value="percent_off">Percent Off</option>
                          <option <?php if($payment_properties->discount_type == 'amount_off'){ echo 'selected="selected"'; } ?> value="amount_off">Amount Off</option>
                        </select>
                        &#8674; <span class="symbol" id="discount_type_currency_sign" style="display: <?php if($payment_properties->discount_type == 'percent_off'){ echo 'none'; }else{ echo 'inline'; }  ?>"><?php echo $currency_symbol; ?></span>
                        <input id="ps_discount_amount" name="ps_discount_amount" class="element text" style="width: 40px"  value="<?php echo htmlspecialchars($payment_properties->discount_amount); ?>" type="text">
                        <span style="display: <?php if($payment_properties->discount_type == 'percent_off'){ echo 'inline'; }else{ echo 'none'; }  ?>" id="discount_type_percentage_sign">&#37;</span> </li>
                      <li>
                        <label class="description" for="ps_discount_code" style="margin-top: 0px">Coupon Code: <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Coupon codes are case insensitive. Use commas to separate multiple coupon codes."/></label>
                        <input id="ps_discount_code" name="ps_discount_code" class="element text large" value="<?php echo htmlspecialchars($payment_properties->discount_code,ENT_QUOTES); ?>" type="text">
                      </li>
                      <li>
                        <label class="description" for="ps_discount_code" style="margin-top: 0px">Select Coupon Code Field: <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the field on your form to be used as the coupon code field. The field type must be 'Single Line Text'. If your form doesn't have it, you need to add it first."/></label>
                        <select class="select" id="ps_discount_element_id" name="ps_discount_element_id" style="width: 90%" autocomplete="off">
                          <?php 
																if(!empty($coupon_code_fields)){ 
																	foreach ($coupon_code_fields as $data){
																		if($payment_properties->discount_element_id == $data['value']){
																			$selected = 'selected="selected"';
																		}else{
																			$selected = '';
																		}

																		echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
																	}
																}else{
																	echo '<option selected="selected" value="">-- No Text Field Available --</option>';
															 	} 
															?>
                        </select>
                      </li>
                      <li> Max Redemptions:
                        <input id="ps_discount_max_usage" name="ps_discount_max_usage" class="element text" style="width: 40px"  value="<?php echo htmlspecialchars($payment_properties->discount_max_usage); ?>" type="text">
                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The coupon can only be used this many times in total. Enter '0' for unlimited usage."/> </li>
                      <li id="ps_li_discount_expiry"> <span> Expires On: </span>
                        <?php
															if(!empty($payment_properties->discount_expiry_date) && $payment_properties->discount_expiry_date != '0000-00-00'){
																list($discount_expiry_yyyy, $discount_expiry_mm, $discount_expiry_dd) = explode('-', $payment_properties->discount_expiry_date);
															}
														?>
                        <span>
                        <input type="text" value="<?php echo $discount_expiry_mm; ?>" maxlength="2" size="2" style="width: 2em;" class="text" name="discount_expiry_mm" id="discount_expiry_mm">
                        <label for="discount_expiry_mm">MM</label>
                        </span> <span>
                        <input type="text" value="<?php echo $discount_expiry_dd; ?>" maxlength="2" size="2" style="width: 2em;" class="text" name="discount_expiry_dd" id="discount_expiry_dd">
                        <label for="discount_expiry_dd">DD</label>
                        </span> <span>
                        <input type="text" value="<?php echo $discount_expiry_yyyy; ?>" maxlength="4" size="4" style="width: 3em;" class="text" name="discount_expiry_yyyy" id="discount_expiry_yyyy">
                        <label for="discount_expiry_yyyy">YYYY</label>
                        </span> <span id="discount_expiry_cal">
                        <input type="hidden" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="linked_picker_discount_expiry" id="linked_picker_discount_expiry">
                        <div style="display: none"><img id="discount_expiry_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="margin-top: 3px; cursor: pointer" /></div>
                        </span> </li>
                    </ul>
                  </div>
                </div>
                <span class="ps_options_span paypal_option stripe_option authorizenet_option paypal_rest_option braintree_option" <?php if(in_array($payment_properties->merchant_type,array('paypal_standard','stripe','authorizenet','paypal_rest','braintree'))){ echo 'style="display: block"'; } ?>>
                <input id="ps_delay_notifications" <?php if(!empty($payment_properties->delay_notifications)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                <label class="choice" for="ps_delay_notifications">Delay Notifications Until Paid</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By default, notification emails are being sent when payment is made successfully. If you disable this option (not recommended), notification emails are being sent immediately upon form submission, regardless of payment status."/> </span> <span id="ask_billing_span" class="ps_options_span stripe_option authorizenet_option paypal_rest_option braintree_option" <?php if(in_array($payment_properties->merchant_type,array('stripe','authorizenet','paypal_rest','braintree'))){ echo 'style="display: block"'; } ?>>
                <input id="ps_ask_billing" <?php if(!empty($payment_properties->ask_billing)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                <label class="choice" for="ps_ask_billing">Ask for Billing Address</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, the payment page will prompt your clients to enter their billing address."/> </span> <span id="ask_shipping_span" class="ps_options_span stripe_option authorizenet_option paypal_rest_option braintree_option" <?php if(in_array($payment_properties->merchant_type,array('stripe','authorizenet','paypal_rest','braintree'))){ echo 'style="display: block"'; } ?>>
                <input id="ps_ask_shipping" <?php if(!empty($payment_properties->ask_shipping)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                <label class="choice" for="ps_ask_shipping">Ask for Shipping Address</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, the payment page will prompt your clients to enter their shipping address."/> </span> <span id="save_cc_data_span" class="ps_options_span authorizenet_option" <?php if(in_array($payment_properties->merchant_type,array('authorizenet'))){ echo 'style="display: block"'; } ?>>
                <input id="ps_save_cc_data" <?php if(!empty($payment_properties->authorizenet_save_cc_data)){ echo 'checked="checked"'; } ?> class="checkbox" value="" type="checkbox" style="margin-left: 0px">
                <label class="choice" for="ps_save_cc_data">Save Cards to Authorize.net</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, all your customers data (including credit card numbers) will be stored into your account on Authorized.net server. You need to enable CIM on your Authorize.net account to use this feature."/> </span> </div>
            </div>
          </div>
        </li>
        <li class="ps_arrow" <?php if($payment_properties->enable_merchant === 0){ echo 'style="display: none;"'; } ?>><img src="images/icons/33_red.png" /></li>
        <li <?php if($payment_properties->enable_merchant === 0){ echo 'style="display: none;"'; } ?>>
          <div id="ps_box_define_prices" class="ps_box_main gradient_blue">
            <div class="ps_box_meta">
              <h1>3.</h1>
              <h6>Define Price</h6>
            </div>
            <div class="ps_box_content">
              <div id="ps_box_price_selector">
                <select id="ps_pricing_type">
                  <option value="fixed" <?php if($payment_properties->price_type == 'fixed'){ echo 'selected="selected"'; } ?>>Fixed Amount</option>
                  <!--<option value="variable" <?php //if($payment_properties->price_type == 'variable'){ echo 'selected="selected"'; } ?>>Variable Amount</option> -->
                </select>
              </div>
              <div id="ps_box_price_fields">
                <div id="ps_box_price_fixed_amount_div" <?php if($payment_properties->price_type == 'variable'){ echo 'style="display: none;"'; } ?>>
                  <label class="description" for="ps_price_amount" style="margin-top: 0px">Price Amount <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter the amount to be charged to your client."/></label>
                  <span class="symbol"><?php echo $currency_symbol; ?></span><span>
                  <input id="ps_price_amount" name="ps_price_amount" class="element text medium" value="<?php echo $payment_properties->price_amount; ?>" type="text">
                  </span>
                  <label class="description" for="ps_price_name" style="margin-top: 15px">Price Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter a descriptive name for the price. This will be displayed into PayPal pages and the receipt email being sent to your client."/></label>
                  <input id="ps_price_name" name="ps_price_name" class="element text large" value="<?php echo $payment_properties->price_name; ?>" type="text">
                  <p><img class="helpmsg" src="images/icons/70_green2.png" style="vertical-align: top" /> Fixed Amount - Your clients will be charged a fixed amount per form submission.</p>
                </div>
                
							</div>					
            </div>
          </div>
        </li>
      </ul>
    </div>
    <!-- /end of content_body --> 
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<?php
	$footer_data =<<<EOT
<script type="text/javascript">
	$(function(){
		{$jquery_data_code}		
    });
</script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/payment_settings.js"></script>
EOT;

	require('includes/footer.php'); 