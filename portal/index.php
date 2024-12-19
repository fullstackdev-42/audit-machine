<?php

/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2017 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/

require('config.php');
require('includes/init.php');
require('includes/db-core.php');
require('lib/swift-mailer/swift_required.php');
require('includes/helper-functions.php');
require('lib/password-hash.php');
require('../itam-shared/includes/helper-functions.php');

/*
//set the session variables for the user==========
$_SESSION['la_logged_in'] = true;
$_SESSION['la_user_id']   = 1;
$_SESSION['la_user_privileges']['priv_administer'] = 1;
$_SESSION['la_user_privileges']['priv_new_forms']  = 1;
$_SESSION['la_user_privileges']['priv_new_themes'] = 1;
//===============================================

        header("location:../auditprotocol/manage_forms.php");
        //echo "Here I Am!";
        exit();
*/

//******************************************************************************************************* */
//CHECK IF USER IS LOGGED IN!!!!!!! IF TRUE, SEND TO CLIENT ACCOUNT PAGE
if(!empty($_SESSION['la_client_logged_in']) && $_SESSION['la_client_logged_in'] == true){
        if ( !isset($_GET['slo']) && !isset($_GET['sls']) ) {
                header("Location: /portal/client_account.php");
                exit;
        }
}
//******************************************************************************************************* */

$ssl_suffix = la_get_ssl_suffix();
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$sql = "select `client_id`, `company_name` from `".LA_TABLE_PREFIX."ask_clients`";
$sth = la_do_query($sql,array(),$dbh);
$entities = array();
while($row = la_do_fetch_result($sth)){
        $company_name = htmlspecialchars($row['company_name']);
        $entities[] =  array(
                'client_id' => $row['client_id'],
                'company_name' => $company_name
        );
}

function getHoursMinutes($seconds, $format = '%02d:%02d') {
    if (empty($seconds) || ! is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = ($minutes % 60);

    return sprintf($format, $hours, $remainMinutes);
}

function normalLogin($parameter=array()){
        $password_is_valid  = false;
        $login_is_valid         = false;
        $dbh = $parameter['dbh'];
        $la_settings = $parameter['la_settings'];
        $username = strtolower(trim($parameter['username']));
        $password = trim($parameter['password']);
        $selectedEntity = $parameter['selected_entity'];

        //get the password hash from the database
        $query  = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE (`username` = ? OR `email` = ?)";
        $params = array($username, $username);
        $sth = la_do_query($query,$params,$dbh);
        $row = la_do_fetch_result($sth);

        $stored_password_hash = $row['password'];
        $user_id              = $row['client_user_id'];
        $client_id            = $row['client_id'];
        $hasher               = new Sha256Hash();
        $check_result         = $hasher->CheckPassword($password, $stored_password_hash);
        $tsv_enable           = (int) $row['tsv_enable'];
        $tsv_secret           = $row['tsv_secret'];
        $how_user_registered  = $row['how_user_registered'];
        $login_attempt_date   = $row['login_attempt_date'];
        $login_attempt_count  = $row['login_attempt_count'];
        $_SESSION['email']    = $row['email'];

        if ($check_result) {
                $login_is_valid     = true;
                $password_is_valid  = true;
        }

        //check for account locking status
        if(!empty($la_settings['enable_account_locking']) && !empty($username)){
                $account_lock_period       = (int) $la_settings['account_lock_period'];
                $account_lock_max_attempts = (int) $la_settings['account_lock_max_attempts'];

                $account_blocked_message   = "Sorry, this account is temporarily blocked. Please try again after {$account_lock_period} minutes.";

                //check the lock period
                $account_lock_expiry_date  = $login_attempt_date + (60 * $account_lock_period);
                $current_datetime                  = strtotime(date("Y-m-d H:i:s"));

                //if lock period still valid, check max attempts
                //if($current_datetime < $account_lock_expiry_date){ /*Before change - S.R. 3/21/19
                if( ($current_datetime < $account_lock_expiry_date ) && empty($check_result) ){                         
                        
                        $login_is_valid = false;
                        
                        //if max attempts already exceed the limit, block the user
                        if($login_attempt_count >= $account_lock_max_attempts){
                                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = $account_blocked_message;
                                //send login notification
                                if($la_settings['enable_registration_notification']){
                                        $login_user = $_SESSION['email'];
                                        $site_name = "https://".$_SERVER['SERVER_NAME'];
                                        $subject = "Continuum GRC Account Login Notification";
                                        $content = "<h2>Continuum GRC Account Login Notification</h2>";
                                        $content .= "<h3>".$login_user." has failed to login to the user portal of ".$site_name.". This account is temporarily blocked for {$account_lock_period} minutes.</h3>";
                                        sendUserManagementNotification($dbh, $la_settings, $subject, $content);
                                }
                        }else{
                                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email or password!';
                                //else if lock period already expired
                                $query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` set `login_attempt_count` = ?, `login_attempt_date` = ? where (`username` = ? OR `email` = ?)";

                                $login_attempt_date  = strtotime(date("Y-m-d H:i:s"));
                                $login_attempt_count += 1;

                                $params = array($login_attempt_count, $login_attempt_date, $username, $username);
                                la_do_query($query, $params, $dbh);
                        }
                } else {
                        //else if lock period already expired
                        $query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` set `login_attempt_count` = ?, `login_attempt_date` = ? where (`username` = ? OR `email` = ?)";

                        //if password is correct, reset to zero
                        //else if password is incorrect, set counter to 1
                        if($login_is_valid){
                                $login_attempt_date  = 0;
                                $login_attempt_count = 0;
                                $password_is_valid   = true;
                        }else{
                                $login_attempt_date  = strtotime(date("Y-m-d H:i:s"));
                                $login_attempt_count += 1;
                                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email or password!';
                        }

                        $params = array($login_attempt_count, $login_attempt_date, $username, $username);
                        la_do_query($query, $params, $dbh);
                }
        }

        // Check if they have permission to view Entity
        if ($password_is_valid) {

                //add user to blockchain if not added
                addUserToChain($dbh, $user_id);

                $query  = "SELECT * FROM `".LA_TABLE_PREFIX."entity_user_relation` WHERE (`entity_id` = ? AND `client_user_id` = ?)";

                $params = array($selectedEntity, $row['client_user_id']);
                $sth2 = la_do_query($query,$params,$dbh);
                $entityAccessGranted = la_do_fetch_result($sth2);

                if (!$entityAccessGranted) {

                        // Initial entities do not have a record in entity_relation table
                        // Which means we have to check this table for permissions
                        $noParentEntity = null;
                        if ($selectedEntity == $row['client_id']) {
                                $query  = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE  `client_id` = ?";
                                $params = array($row['client_id']);
                                $sth2 = la_do_query($query,$params,$dbh);
                                $noParentEntity = la_do_fetch_result($sth2);
                        }
                        if (!$noParentEntity) {
                                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'You do not have permission to access this entity';
                                $login_is_valid = false;
                        }
                }
        }
        if($row['status'] == 0){
            if($password_is_valid && $login_is_valid){
                    //check for Multi-Factor Authentication, is it enabled or not
                    $show_tsv_page = false;
                    $_SESSION['la_client_entity_id'] = $selectedEntity;
                    //if TSV is enforced globally
                    if(!empty($la_settings['enforce_tsv'])){
                        $show_tsv_page = true;

                        if(empty($tsv_secret)){
                            //display TSV setup page
                            $tsv_page_target = 'setup';
                        }else{
                            //display TSV verify page
                            $tsv_page_target = 'verify';
                        }
                    }else{
                        if(!empty($tsv_enable)){
                            $show_tsv_page = true;
                            if(empty($tsv_secret)){
                                    //display TSV setup page
                                    $tsv_page_target = 'setup';
                            }else{
                                    //display TSV verify page
                                    $tsv_page_target = 'verify';
                            }
                        }
                    }

                    if($show_tsv_page === true){
                            if($tsv_page_target == 'setup'){
                                    //display TSV setup page
                                    $_SESSION['la_tsv_setup'] = $user_id;
                                    $_SESSION['la_tsv_setup_remember_me'] = $remember_me;
                                    header("Location: login_tsv_setup.php");
                                    exit;
                            }else if($tsv_page_target == 'verify'){
                                    //display TSV verify page
                                    $_SESSION['la_tsv_verify'] = $user_id;
                                    $_SESSION['la_tsv_verify_remember_me'] = $remember_me;
                                    if($how_user_registered){
                                            //if "Enforce 50% Rule on Passwords" is enabled dont send code in email
                                            if ( empty($la_settings['disable_email_based_otp']) ) {
                                            /************this piece of code will send mail to the user with one time code **************/
                                            $user_email                     = $_SESSION['email'];
                                            $email_param                    = array();
                                            $email_param['from_name']       = 'IT Audit Machine';
                                            $email_param['from_email']      = $la_settings['default_from_email'];
                                            $email_param['subject']         = $email_param['from_name'].' - Single-use authentication code';
                                            $email_param['as_plain_text']   = true;

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
                                            $from_name      = html_entity_decode($email_param['from_name'] ,ENT_QUOTES);
                                            $from_email     = html_entity_decode($email_param['from_email'] ,ENT_QUOTES);
                                            $subject        = html_entity_decode($email_param['subject'] ,ENT_QUOTES);
                                            $email_content_type = 'text/html';
                                            $random_code = md5(time());
                                            $one_time_code = substr($random_code, 0, 6);
                                            $body = "<html>
                                                            <head></head>
                                                            <body>
                                                            <p>Your single-usage code: " . $one_time_code . " </p>
                                                            </body>
                                                    </html>";
                                            $s_message = Swift_Message::newInstance()
                                                    ->setCharset('utf-8')
                                                    ->setMaxLineLength(1000)
                                                    ->setSubject($subject)
                                                    ->setFrom(array($from_email => $from_name))
                                                    ->setSender($from_email)
                                                    ->setReturnPath($from_email)
                                                    ->setTo($user_email)
                                                    ->setBody($body, $email_content_type);

                                            //send the message
                                            $s_mailer->send($s_message);
                                            $_SESSION['LA_CODE_SEND']  = true;
                                            $_SESSION['ONE_TIME_CODE'] = $one_time_code;
                                            $_SESSION['EXPIRY_TIME']   = strtotime("+5 minutes");
                                            header("location:login-using-code.php");
                                            exit();
                                            } else {
                                                header("Location: login_verify.php");
                                                exit;
                                            }
                                    } else {
                                        header("Location: login_verify.php");
                                        exit;
                                    }
                            }
                    }

                    //regenerate session id for protection against session fixation
                    session_regenerate_id();
                    //set the session variables for the user=========
                    $_SESSION['la_client_logged_in'] = true;
                    $_SESSION['la_client_user_id']   = $user_id;
                    $_SESSION['la_client_client_id'] = $client_id;
                    if($la_settings['enable_welcome_message_notification'] == 1) {
                        $_SESSION['user_login_message_enabled'] = true;
                    }
                    
                    //===============================================
                    
                    //log user login time
                    logUserSession($dbh, $_SESSION['la_client_user_id'], session_id(), 'login', 0);

                    if(!empty($_SESSION['prev_referer'])){
                            $next_page = $_SESSION['prev_referer'];
                            unset($_SESSION['prev_referer']);
                            header("Location: ".$next_page);
                            exit;
                    }else{
                            $log_query = "INSERT INTO `".LA_TABLE_PREFIX."portal_user_login_log` (`log_id`, `client_user_id`, `last_login`, `user_ip`) VALUES (NULL, '{$user_id}', '".time()."', '".$_SERVER['REMOTE_ADDR']."');";
                            la_do_query($log_query,array(),$dbh);
                            header("Location: client_account.php");
                            exit;
                    }
            }
        }
        else {
                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Your account is suspended';
        }
}


//**************************************************************
//END normalLogin() method
//**************************************************************

$auth = null;

if($la_settings['saml_login']){
    require('../itam-shared/simplesamlphp/lib/_autoload.php');
    $auth = new SimpleSAML_Auth_Simple('default-sp');
    if (!$auth->isAuthenticated()) {
        $auth->requireAuth(array('KeepPost' => FALSE));
    }
    $nameID = $auth->getAuthData('saml:sp:NameID')->getValue();
    $session = \SimpleSAML\Session::getSessionFromRequest();
    $session->cleanup();
    
    if(!empty($nameID)) {
        $query125 = "SELECT `client_user_id`, `client_id` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `email` = ?";
        $sth125 = la_do_query($query125, array($nameID), $dbh);
        $row125 = la_do_fetch_result($sth125);
        
        //update last_login_date and last_ip_address
        $last_login_date = date("Y-m-d H:i:s");
        $last_ip_address = $_SERVER['REMOTE_ADDR'];
        $user_id = 0;
        $client_id = 0;

        if($row125){
            $user_id = $row125['client_user_id'];
            $client_id = $row125['client_id'];

            //set the session variables for the user=========
            $_SESSION['la_client_logged_in'] = true;
            $_SESSION['la_client_user_id']   = $user_id;
            $_SESSION['la_client_client_id'] = $client_id;
            $_SESSION['la_client_entity_id'] = $client_id;
            $_SESSION['la_user_logged_in_time'] = time();
            if($la_settings['enable_welcome_message_notification'] == 1) {
                $_SESSION['user_login_message_enabled'] = true;
            }
            //===============================================

            //log user login time
            logUserSession($dbh, $_SESSION['la_client_user_id'], session_id(), 'login', 0);

            setEntityRelationForNormalUsers($dbh, $client_id, $user_id);

            header("Location: client_account.php");
            exit();
        } else {
            /*$attrs = $auth->getAttributes();
            if(isset($attrs['memberof'][0]) && $attrs['memberof'][0] != '') {
                $memberof = $attrs['memberof'][0];
            } else {
                $memberof = $nameID;
            }

            $query123 = "INSERT INTO `".LA_TABLE_PREFIX."ask_clients` (`client_id`, `company_name`, `contact_email`, `contact_phone`, `contact_full_name`, `entity_description`) VALUES (NULL, ?, ?, '', '', '');";
            $sth123 = la_do_query($query123, array($memberof, $nameID), $dbh);
            $client_id = $dbh->lastInsertId();

            $query125 = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users` (`client_user_id`, `client_id`, `email`, `full_name`, `phone`, `username`, `password`, `status`, `is_admin`, `is_invited`, `tsv_enable`, `tsv_secret`, `tsv_code_log`, `tsv_code`, `account_suspension_strict_date`, `account_suspension_inactive`, `account_suspended_deletion`, `account_suspension_strict_date_flag`, `account_suspension_inactive_flag`, `suspended_account_auto_deletion_flag`, `register_datetime`, `login_attempt_date`, `login_attempt_count`, `how_user_registered`, `no_of_days_to_change_password`, `password_change_date`) VALUES (NULL, ?, ?, ?, '', ?, '', '0', '0', '0', '0', '', '', NULL, '', '', '', '0', '0', '0', ?, '', '', '0', '', '');";
            $sth125 = la_do_query($query125, array($client_id, $nameID, $nameID, $nameID, time()), $dbh);
            $user_id = $dbh->lastInsertId();

            $user_entity_sql = "INSERT INTO `".LA_TABLE_PREFIX."entity_user_relation` (`entity_user_relation_id`, `entity_id`, `client_user_id`) VALUES (NULL, ?, ?);";
            la_do_query($user_entity_sql, array($client_id, $user_id), $dbh);*/

            header("Location: ".$auth->getLogoutURL());
            exit();
        }
    } else {
        header("Location: ".$auth->getLogoutURL());
        exit();
    }
}

if(!empty($_POST['submit']) && isset($_POST['submit'])){
        $username = strtolower(trim($_POST['client_username']));
        $password = trim($_POST['client_password']);
        $selectedEntity = $_POST['selected_entity'];

        if(empty($username) || empty($password)){
                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email or password!';
        }else if($selectedEntity == 0){
                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'You do not have permission to access this entity';
        }else{
                normalLogin(array('dbh' => $dbh, 'la_settings' => $la_settings, 'username' => $username, 'password' => $password, 'selected_entity' => $selectedEntity));
        }
}

if(!empty($_GET['from'])){
        $_SESSION['prev_referer'] = base64_decode($_GET['from']);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>IT Audit Machine Client Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="index, nofollow" />
<meta name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
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
        echo '<link href="css/themes/theme_'.$la_settings['admin_theme'].'.css" rel="stylesheet" type="text/css" />';
}
?>

<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/edit_form.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<link href="css/override.css" rel="stylesheet" type="text/css" />
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

<div id="main">

<div id="content" style="margin: 0 0 15px !important;">
    <div class="post login_main">
        <div style="padding-top: 10px">
          <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
            <h3>User Portal</h3>
            <p>Welcome to the Continuum GRC IT Audit Machine (ITAM).</p>
            <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
          </div>
          <?php if($la_settings["enable_site_down"]) { ?>
            <div class="site-down">
                <img src="images/navigation/ED1C2A/50x50/Warning.png">
                <h3>This site has been temporarily deactivated by the system administrator.</h3>
                <h3>Please contact the site administrator for additional information.</h3>
            </div>
          <?php } else { ?>
          <div style="border-bottom: 1px dotted #CCCCCC;margin-top: 10px">
            <form id="form_login2" class="itauditm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off">
              <div style="display:none;">
                <input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
                    <?php
                    if(isset($_GET['user'])){
                            echo '<input type="hidden" name="user" value="login" />';
                    }
                    ?>
              </div>
              <ul>
                <?php
                    if(!empty($_SESSION['LA_CLIENT_LOGIN_ERROR'])){
                            echo '<li id="li_login_notification"><h5>';
                            echo $_SESSION['LA_CLIENT_LOGIN_ERROR'];
                            echo '</h5></li>';
                            unset($_SESSION['LA_CLIENT_LOGIN_ERROR']);
                    }

                    if(!empty($_SESSION['password_plain']) && isset($_GET['p']) ){
                            echo '<li id="li_login_notification"><h5> Password generated successfully. Use password <span id="temp-pass" style="color:#33BF8C;cursor:pointer;" title="Copy Password" onclick="copyToClipboard(\'#temp-pass\')">';
                            echo $_SESSION['password_plain'];
                            echo '</span> to login to your account.</h5></li>';
                            unset($_SESSION['password_plain']);
                    }
                ?>
                <li id="li_email_address">
                  <label class="desc" for="client_username">Username</label>
                  <div>
                    <input id="client_username" name="client_username" class="element text large" type="text" maxlength="255" value="<?php echo htmlspecialchars($_POST['client_username']); ?>" autocomplete="off" readonly/>
                  </div>
                </li>
                <li id="li_entity">
                    <label class="desc" for="li_entity">Entity</label>
                    <div>
                            <input type="text" id="company_name" name="company_name" placeholder="Begin typing to see entities." value="<?php echo htmlspecialchars($_POST['company_name']); ?>" /> 
                            <input type="hidden" name="selected_entity" id="client_id" value="<?php echo htmlspecialchars($_POST['selected_entity']); ?>" /> 
                            <div id="resultsContainer"></div> 
                    </div>
                </li>
                <li id="li_password">
                  <label class="desc" for="client_password">Password </label>
                  <div>
                    <input id="client_password" name="client_password" class="element text large" type="password" maxlength="255" value="<?php echo htmlspecialchars($_POST['client_password']); ?>" autocomplete="off" readonly/>
                  </div>
                </li>
                <li id="li_submit" class="buttons" style="overflow: auto">
                  <label class="desc"></label>
                  <input type="hidden" name="submit" id="submit" value="1">
                  <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left; border-radius: 2px; margin-left: 17px;"> Sign In </button>
                  <?php
                    if($la_settings['portal_registration'] == 1){
                    ?>
                            <button type="button" class="bb_button bb_green" style="float: left;border-radius: 2px" onclick="document.location='client_register.php'" > Register a New Account </button>
                    <?php
                    }
                    ?>
                </li>
              </ul>
            </form>
          </div>
          <ul style="padding-top: 5px">
            <li>
                <span style="float:right;">
                <input type="checkbox" value="1" class="element checkbox" name="admin_forgot" id="admin_forgot" style="margin-left: 0px">
                <label for="admin_forgot" class="choice" style="color: #33BF8C;">Forgot password?</label>
                <input type="checkbox" value="1" class="element checkbox" name="username_forgot" id="username_forgot" style="margin-left: 0px">
                <label for="username_forgot" class="choice" style="color: #33BF8C;">Forgot username or entity name?</label>
                </span>
            </li>
          </ul>
          <?php } ?>
        </div>
    </div>
  <?php
    if(!empty($la_settings['portal_home_video_url'])) {
            echo '<div class="post" style="margin-top:15px;">';
            $isUrl = true;
            if($isUrl){
                    echo '<video id="video2" width="100%" height="100%" controls controlsList="nodownload">';
                    echo '<source src="' . $la_settings['portal_home_video_url'] . '" type="video/mp4">';
                    echo '</video>';
            }
    echo '</div>';
    }
  ?>
</div>

<div id="dialog-login-page" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
    <p id="dialog-login-page-msg"> Success </p>
</div>

<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.video-extend.js"></script>
<script type="text/javascript" src="js/login_admin.js"></script>
EOT;

require('includes/footerlogin.php');
?>

<script type="text/javascript">
function copyToClipboard(element) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(element).text()).select();
    document.execCommand("copy");
    $temp.remove();
}


$(document).ready(function() {
    $("input").click(function(e){
        $(this).attr("readonly", false);
    })
});
</script>

<?php if($isVideo) {?>
<script>
    $(document).ready(function(){
        $('video').videoExtend();
    });
</script>
<?php } ?>

<!-- BEGIN SEARCH SUGGESTIONS --> 
<style> 
#company_name { 
    background: #fff url(../images/shadow.gif) repeat-x top;
    border-bottom: 1px solid #ddd;
    border-left: 1px solid #c3c3c3;
    border-right: 1px solid #c3c3c3;
    border-top: 1px solid #7c7c7c;
    color: #333;
    font-size: 100%;
    margin: 0;
    padding: 3px;
    border-radius: 4px;
    -moz-border-radius: 4px;
    -webkit-border-radius: 4px;
    width: 99%;
} 
#password1 { 
    border: 0.5px solid #9A9A9A !important; 
} 
#password2 { 
    border: 0.5px solid #9A9A9A !important;
}
.result {
        width: 101.5% !important;
}
.resultList { 
    color: black !important; 
    margin: 0; 
    padding: 0; 
    list-style: none;
    width: 300.75px;
} 
.resultList li { 
    color: black !important; 
    cursor: pointer; 
    text-align: left !important; 
    background-color: white; 
    border-bottom: 1px solid #ccc; 
    border-right: 1px solid #ccc; 
    border-left: 1px solid #ccc; 
    height: 26px; 
} 
label { 
    color: black; 
    padding-right: 15px; 
} 
#resultsContainer { 
    color: black;
    padding-left: 0px!important;
} 
</style> 
<script src="../itam-shared/js/entity-search-suggestions.js"></script>
<script>
    localStorage.removeItem("auto-save-then-logout");
</script>
<!-- END SEARCH SUGGESTIONS -->
