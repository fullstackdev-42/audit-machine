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
require('includes/filter-functions.php');
require('lib/google-authenticator.php');
require('lib/password-hash.php');
require('lib/swift-mailer/swift_required.php');

$ssl_suffix  = la_get_ssl_suffix();

$dbh 		 = la_connect_db();
$la_settings = la_get_settings($dbh);

if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) )
    $forced_password = true;

$stored_user_email 	  = $_SESSION['email'];

$authenticator = new PHPGangsta_GoogleAuthenticator();

//initialize tsv secret for qrcode
if(empty($_SESSION['la_tsv_setup_secret'])){
    $_SESSION['la_tsv_setup_secret'] =  $authenticator->createSecret();
}
$tsv_secret = $_SESSION['la_tsv_setup_secret'];
$totp_data  = "otpauth://totp/ITAuditMachine:{$stored_user_email}?secret={$tsv_secret}";

$how_user_registered = 0;

// BEGIN SEND EMAIL
function sendMail($dbh, $la_settings, $subject, $body, $to_mail){
    $email_param 				= array();
    $email_param['from_name'] 	= 'IT Audit Machine';
    $email_param['from_email'] 	= $la_settings['default_from_email'];
    $email_param['subject'] 	= $subject;
    $email_param['as_plain_text'] = true;
    
    //create the mail transport
    if(!empty($la_settings['smtp_enable'])){
        $s_transport = Swift_SmtpTransport::newInstance($la_settings['smtp_host'], $la_settings['smtp_port']);
        
        if(!empty($la_settings['smtp_secure'])){
            //port 465 for (SSL), while port 587 for (TLS)
            if($la_settings['smtp_port'] == '587'){
                $s_transport->setEncryption('tls');
            }else{
                $s_transport->setEncryption('ssl');
            }
        }
        
        if(!empty($la_settings['smtp_auth'])){
            $s_transport->setUsername($la_settings['smtp_username']);
            $s_transport->setPassword($la_settings['smtp_password']);
        }
    }else{
        $s_transport = Swift_MailTransport::newInstance(); //use PHP mail() transport
    }
    
    //create mailer instance
    $s_mailer = Swift_Mailer::newInstance($s_transport);
    
    $from_name 	= html_entity_decode($email_param['from_name'] ,ENT_QUOTES);
    $from_email = html_entity_decode($email_param['from_email'] ,ENT_QUOTES);
    $subject 	= html_entity_decode($email_param['subject'] ,ENT_QUOTES);
    
    $email_content_type = 'text/html';
    
    // mail body
    $to_mail		= trim($to_mail);
    $to_mail 		= str_replace(';',',',$to_mail);
    $to_mail 		= str_replace(' ','',$to_mail);
    $to_mail 		= str_replace('&nbsp;','',$to_mail);
    $to_mail		= explode(",", $to_mail);
    $to_mails 		= array_map(function($email){
        return str_replace(" ", "", $email);
    }, $to_mail);
    
    $s_message = Swift_Message::newInstance()
    ->setCharset('utf-8')
    ->setMaxLineLength(1000)
    ->setSubject($subject)
    ->setFrom(array($from_email => $from_name))
    ->setSender($from_email)
    ->setReturnPath($from_email)
    ->setTo($to_mails)
    ->setBody($body, $email_content_type);
    
    // send mail
    $s_mailer->send($s_message);
}
// END SEND EMAIL

// BEGIN PROCESS SUBMIT
if(!empty($_POST['submit'])){
    $tsv_result = false;
    $input 	    = la_sanitize($_POST);
    if($_POST['submit'] == 1){
        $how_user_registered = 0;
        $tsv_code = $input['tsv_code'];
        $tsv_result = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  // 8 means 4 minutes before or after
    }else{
        if($_SESSION['ONE_TIME_CODE'] == $input['single_usage_code']){
            $tsv_result = true;
            $tsv_code = '';
            $how_user_registered = 1;
            
            unset($_SESSION['LA_CODE_SEND']);
            unset($_SESSION['ONE_TIME_CODE']);
            unset($_SESSION['EXPIRY_TIME']);
        }
    }
    
    if($tsv_result === true){
        
        if($_SESSION['ses_client_id'] == 0){ // new entity
            // DELETE INVITED USER
            $query = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email=? AND client_id=? AND is_invited=?";
            $params = array($_SESSION['email'], 0, 1);
            la_do_query($query, $params, $dbh);
            
            // insert company into ap_ask_clients table
            $query = "INSERT INTO `".LA_TABLE_PREFIX."ask_clients` ( `company_name`, `contact_email`, `contact_phone`, `contact_full_name`) VALUES (?, ?, ?, ?);";
            $params = array( $_SESSION['company_name'], $_SESSION['email'], $_SESSION['phone'], $_SESSION['full_name']);
            la_do_query($query,$params,$dbh);
            $client_id = (int) $dbh->lastInsertId();
            
            // insert user into ap_ask_client_users table
            $query = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users` ( `client_id`, `email`, `full_name`, `phone`, `username`, `password`, `status`, `is_admin`, `tsv_secret`, `register_datetime`, `how_user_registered`, `password_change_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
            $params = array( $client_id, $_SESSION['email'], $_SESSION['full_name'], $_SESSION['phone'], $_SESSION['username'], $_SESSION['password_hash'], 0, 1, $tsv_secret, time(), $how_user_registered, time());
            la_do_query($query,$params,$dbh);
            $client_user_id = (int) $dbh->lastInsertId();

            //add user to blockchain if not added
            addUserToChain($dbh, $client_user_id);
            
            // save new password to `old_password_hash` table
            insert_old_password_hash($client_user_id, $_SESSION['password_hash'], 0, $dbh);
        } else { // existing entity

            // There was issues occuring when updating existing user, so until further notice, deleting the user, then replacing with a new one

            // DELETE INVITED USER
            $query = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email=? AND client_id=? AND is_invited=?";
            $params = array($_SESSION['email'], $_SESSION['ses_client_id'],1);
            la_do_query($query, $params, $dbh);

            $query = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email=? AND client_id=? AND is_invited=?";
            $params = array($_SESSION['email'], 0, 1);
            la_do_query($query, $params, $dbh);

            // THEN ADD NEW USER
            $query = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users`( 
                `client_id`, 
                `email`, 
                `full_name`, 
                `phone`, 
                `username`, 
                `password`, 
                `status`, 
                `is_admin`, 
                `tsv_secret`, 
                `register_datetime`, 
                `how_user_registered`,
                `password_change_date`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);"; 
            $params = array( 
                $_SESSION['ses_client_id'], 
                $_SESSION['email'], 
                $_SESSION['full_name'], 
                $_SESSION['phone'], 
                $_SESSION['username'], 
                $_SESSION['password_hash'], 
                0, 
                1, 
                $tsv_secret, 
                time(), 
                $how_user_registered,
                time()
            ); 
            la_do_query($query,$params,$dbh);             
            $client_user_id = (int) $dbh->lastInsertId(); 
            
            //add user to blockchain if not added
            addUserToChain($dbh, $client_user_id);

            // save new password to `old_password_hash` table
            insert_old_password_hash($client_user_id, $_SESSION['password_hash'], 0, $dbh);
        }

        // send email notifying a new portal user was registered
        if($la_settings['enable_registration_notification']){
            $subject = 'New user registration notification';
            
            $email_content  = "<h2>New user registration notification<h2>";
            $email_content .= "<hr/>";
            $email_content .= "<h3>User Details:</h3>";
            $email_content .= "<table>";
            $email_content .= "<tr><td style='width:100px;'>Entity Name:</td><td>{$_SESSION['company_name']}</td></tr>";
            $email_content .= "<tr><td style='width:100px;'>User Name:</td><td>{$_SESSION['full_name']}</td></tr>";
            $email_content .= "<tr><td style='width:100px;'>Email:</td><td>{$_SESSION['email']}</td></tr>";
            $email_content .= "<tr><td style='width:100px;'>Phone:</td><td>{$_SESSION['phone']}</td></tr>";
            $email_content .= "</table>";
            
            $subject = utf8_encode($subject);
            // Always set content-type when sending HTML email
            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            // More headers
            $headers .= 'From: '.$la_settings['default_from_name'].'<'.$la_settings['default_from_email'].'>' . "\r\n";
            
            // @mail($la_settings['registration_notification_email'], $subject, $email_content, $headers);
            sendMail($dbh, $la_settings, $subject, $email_content, $la_settings['registration_notification_email']);
        }
        
        unset($_SESSION['ses_client_id']);
        unset($_SESSION['company_name']);
        unset($_SESSION['email']);
        unset($_SESSION['phone']);
        unset($_SESSION['full_name']);
        unset($_SESSION['username']);
        unset($_SESSION['password_hash']);
        unset($_SESSION['la_tsv_setup_secret']);

        $redirect_location = "index.php";

        if( $forced_password ) {
            $redirect_location .= "?p=1";
        } else {
            unset($_SESSION['password_plain']);
        }


        header("Location: {$redirect_location}");
        exit;
    } else {
        $_SESSION['LA_LOGIN_ERROR'] = 'Error! Incorrect code.';
    }
}
// END PROCESS SUBMIT

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>IT Audit Machine Admin Panel</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="index, nofollow" />
    <meta name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
    <link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="css/main.css" media="screen" />

    <!--[if IE 7]>
        <link rel="stylesheet" type="text/css" href="css/ie7.css" media="screen" />
    <![endif]-->

    <!--[if IE 8]>
        <link rel="stylesheet" type="text/css" href="css/ie8.css" media="screen" />
    <![endif]-->

    <!--[if IE 9]>
        <link rel="stylesheet" type="text/css" href="css/ie9.css" media="screen" />
    <![endif]-->

    <link href="css/theme.css" rel="stylesheet" type="text/css" />
    <?php
    if(!empty($la_settings['admin_theme'])){
        echo '<link href="css/themes/theme_'.noHTML($la_settings['admin_theme']).'.css" rel="stylesheet" type="text/css" />';
    }
    ?>
    <link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div id="bg" class="login_page">
    <div id="container">
        <div id="header">

        <?php
        if (!empty($la_settings['admin_image_url'])) {
                $itauditmachine_logo_main = $la_settings['admin_image_url'];
        } else {
                $itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
        }
        ?>

          <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
          <div class="clear"></div>
        </div>
        <div id="main" class="two-step-div">
            <div id="content">
                <div class="post login_main">
                    <div style="padding-top: 10px">
                        <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
                            <h3>Verification Required</h3>
                            <p>Please follow the steps in one of these two (2) options below to continue:</p>
                            <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
                        </div>
                        <div style="margin-top: 10px">
                            <form id="form_login" class="itauditm" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
                                <div style="display:none;">
                                    <input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
                                </div>
                                <ul>
                                    <?php if(!empty($_SESSION['LA_LOGIN_ERROR'])){ ?>
                                        <li id="li_login_notification">
                                            <h5><?php echo noHTML($_SESSION['LA_LOGIN_ERROR']); ?></h5>
                                        </li>
                                        <?php
                                        unset($_SESSION['LA_LOGIN_ERROR']);
                                    }
                                    ?>
                                    <li id="li_login_tsv_setup">
                                        <ul>
                                            <li class="tsv_setup_title">
                                                <?php if ( empty($la_settings['disable_email_based_otp']) ) { ?>
                                                    <h3>Option 1</h3>
                                                <?php } ?>
                                                <br>
                                                <strong>Step 1: Open mobile authenticator application.</strong></li>
                                            <li>If you do not have one yet, you can install any of the following applications listed:
                                                <div style="margin-top: 10px;padding-left: 20px"> &#8674; <a class="app_link" href="http://support.google.com/accounts/bin/answer.py?hl=en&answer=1066447" target="_blank">Google Authenticator</a> (Android/iPhone/BlackBerry)<br/>
                                                    &#8674; <a class="app_link" href="http://guide.duosecurity.com/third-party-accounts" target="_blank">Duo Mobile</a> (Android/iPhone)<br/>
                                                    &#8674; <a class="app_link" href="http://www.amazon.com/gp/product/B0061MU68M" target="_blank">Amazon AWS MFA</a> (Android)<br/>
                                                    &#8674; <a class="app_link" href="https://itunes.apple.com/us/app/hde-otp-generator/id571240327?mt=8" target="_blank">HDE OTP Generator</a> (iPhone)<br/>
                                                    &#8674; <a class="app_link" href="https://itunes.apple.com/us/app/2stp-authenticator/id954311670?mt=8" target="_blank">2STP Authenticator</a> (iPhone)<br/>
                                                    &#8674; <a class="app_link" href="https://itunes.apple.com/us/app/otp-auth/id659877384?mt=8" target="_blank">OTP Auth</a> (iPhone)<br/>
                                                    &#8674; <a class="app_link" href="http://www.windowsphone.com/en-US/apps/021dd79f-0598-e011-986b-78e7d1fa76f8" target="_blank">Authenticator</a> (Windows Phone) </div>
                                            </li>
                                            <li class="tsv_setup_title"><strong>Step 2: Scan Barcode.</strong></li>
                                            <li>Use your authenticator application to scan the barcode below:
                                                <div style="width: 80%;padding: 20px;text-align: center">
                                                    <div id="qrcode"></div>
                                                </div>
                                                or you can enter this secret key manually: <strong><?php echo noHTML($tsv_secret); ?></strong></li>
                                            <li class="tsv_setup_title"><strong>Step 3: Verify Code.</strong></li>
                                            <li>Once your application is configured, enter the <strong>six-digit security code</strong> generated by your application below.</li>
                                        </ul>
                                    </li>
                                    <li id="li_tsv_code">
                                        <label class="desc" for="tsv_code">Enter your six-digit code.</label>
                                        <div>
                                            <input id="tsv_code" style="width: 150px" name="tsv_code" class="element text medium" type="text" maxlength="255" value=""/>
                                        </div>
                                    </li>
                                    <li id="li_submit" class="buttons" style="overflow: auto;margin-top: 5px;margin-bottom: 10px">
                                        <input type="hidden" name="submit" id="submit" value="1">
                                        <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px"> Verify Code </button>
                                    </li>
                                </ul>
                            </form>
                            <?php if(!isset($la_settings['disable_email_based_otp']) || empty($la_settings['disable_email_based_otp'])): ?>
                                <div style="width:100%; margin: 5px 0 0px 0; float:left;">
                                    <div style="border: 1px solid #000;border-style: dotted; width:100%; margin: 0 10px 20px 0px;"></div>
                                    <div style="margin:0 10px 20px 0px;">
                                        <h3>Option 2</h3>
                                        <br>
                                        <strong>Step 1: Enter in your email address below used for your ITAM username and click submit.</strong></b></div>
                                    <div style="width:100%; margin: 0 0 5px 0;"><b>Email address.</b></div>
                                    <div>
                                        <input type="text" style="width:250px; margin: 0 0 20px 0; background-color: #bbbbbb;" value="<?php echo noHTML($_SESSION['email']); ?>" readonly>
                                    </div>
                                    <div>
                                        <button style="float: left;border-radius: 2px" name="submit_button" id="sendcode_button" class="bb_button bb_green" type="submit"> Submit </button>
                                    </div>
                                </div>
                                <form method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
                                    <div style="display:none;">
                                        <input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
                                    </div>
                                    <input type="hidden" name="submit" id="submit" value="2">
                                    <div style="width:100%; margin-top:20px; float:left; margin-bottom: 20px;">
                                        <div style="margin:0 10px 20px 0px;"><b>Step 2: Look for an email with a six-character security code in it and enter it in the field below.</b></div>
                                        <div style="width:100%; margin: 0 0 5px 0;"><b>Enter your six-character code.</b></div>
                                        <div>
                                            <input type="text" name="single_usage_code" style="width:150px; margin: 0 0 20px 0;">
                                        </div>
                                        <div style="margin-bottom:20px;">
                                            <button style="float: left;border-radius: 2px" name="verify_button" id="verify_button" class="bb_button bb_green" type="submit"> Verify Code </button>
                                        </div>
                                        <div style="clear:both;"></div>
                                    </div>
                                </form>
                            <?php endif ?>
                        </div>
                    </div>
            </div>
            <div id="dialog-welcome-message" title="Single-usage code" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Notice.png" title="Success" />
                <p>Please enter the security code sent to your email address</p>
            </div>
            <div id="dialog-error-message" title="Single-usage code" class="buttons" style="display: none"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Failure" />
                <p>We are sorry but something went wrong. Please make sure you are not also logged into the User Portal and try again.</p>
            </div>
        </div>

        <?php
        $footer_data =<<<EOT
<script type="text/javascript" src="js/qrcode/qrcode.js"></script>
<script>
	$(function(){
		var qrcode = new QRCode(document.getElementById("qrcode"), { width : 140, height : 140 });
		qrcode.makeCode('{$totp_data}');
	});	
</script>
EOT;
require('includes/footer.php');
?>

<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){

        $("#dialog-welcome-message").dialog({
            modal: true,
            autoOpen: false,
            closeOnEscape: false,
            width: 550,
            position: { my: "top", at: "top+175", of: window },
            draggable: false,
            resizable: false,
            buttons: [
                {
                    text: 'Ok',
                    id: 'btn-welcome-message-ok',
                    'class': 'btn_secondary_action',
                    click: function() {
                        $(this).dialog('close');
                    }
                }
            ]
        });

        $("#dialog-error-message").dialog({
            modal: true,
            autoOpen: false,
            closeOnEscape: false,
            width: 550,
            position: { my: "top", at: "top+175", of: window },
            draggable: false,
            resizable: false,
            buttons: [
                {
                    text: 'Ok',
                    id: 'btn-welcome-message-ok',
                    'class': 'btn_secondary_action',
                    click: function() {
                        $(this).dialog('close');
                    }
                }
            ]
        });

        $('#sendcode_button').click(function(){
            $.ajax({
                method: "GET",
                url: "ajax-request.php",
                beforeSend:function(){},
                success: function(response){
                    console.log(response);
                    if(response == 'LA_CODE_SEND'){
                        $("#dialog-welcome-message").dialog('open').click();
                    }else{
                        $("#dialog-error-message").dialog('open').click();
                    }
                }
            });
        });
    });
</script>
