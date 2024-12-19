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
	require('lib/swift-mailer/swift_required.php');
	require('includes/helper-functions.php');
	require('includes/filter-functions.php');
	require('lib/google-authenticator.php');
	require('lib/password-hash.php');

	$ssl_suffix  = la_get_ssl_suffix();

	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check for verify session, if not exist, redirect back to login page

	if(empty($_SESSION['la_tsv_verify'])){
		header("Location: index.php");
		exit;
	}

	//verify security code
	if(!empty($_POST['submit'])){
		$input 	  = la_sanitize($_POST);

		$tsv_code = $input['tsv_code'];
		$user_id  = $_SESSION['la_tsv_verify'];

		$query  = "SELECT
						`priv_administer`,
						`priv_new_forms`,
						`priv_new_themes`,
						`tsv_secret`,
						`tsv_code_log`,
						`login_attempt_date`,
						`login_attempt_count`
					FROM
						`".LA_TABLE_PREFIX."users`
				   WHERE
					   	`user_id`=? and `status`=1";
		$params = array($user_id);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$priv_administer	  = (int) $row['priv_administer'];
		$priv_new_forms		  = (int) $row['priv_new_forms'];
		$priv_new_themes	  = (int) $row['priv_new_themes'];
		$tsv_secret 		  = $row['tsv_secret'];
		$tsv_code_log 		  = $row['tsv_code_log'];
		$tsv_code_log_array   = explode(',', $tsv_code_log);
		$login_attempt_date   = $row['login_attempt_date'];
		$login_attempt_count  = $row['login_attempt_count'];

		//first make sure the tsv code haven't being used previously

		if(empty($tsv_code)){
			$_SESSION['LA_LOGIN_ERROR'] = 'Error! Code has already been used.';
		}else{
			//$authenticator = new PHPGangsta_GoogleAuthenticator();
			//$tsv_result    = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  //8 means 4 minutes before or after

			$tsv_result = false;

			if($_SESSION['ONE_TIME_CODE'] == $tsv_code && $_SESSION['EXPIRY_TIME'] >= time()){
				$tsv_result = true;
			}

			if($tsv_result === true){
				$login_is_valid = true;
			}else{
				$login_is_valid = false;
				$_SESSION['LA_LOGIN_ERROR'] = 'Error! Incorrect code.';

				//if account locking enabled, increase the login attempt counter
				if(!empty($la_settings['enable_account_locking']) && !empty($user_id)){
					$query = "UPDATE ".LA_TABLE_PREFIX."users
								  SET
								  	 login_attempt_date=?,
								  	 login_attempt_count=(login_attempt_count + 1)
							    WHERE
							    	 user_id = ?";
					$new_login_attempt_date = date("Y-m-d H:i:s");
					$params = array($new_login_attempt_date,$user_id);
					la_do_query($query,$params,$dbh);
				}
			}

			//check for account locking status
			if(!empty($la_settings['enable_account_locking']) && !empty($user_id)){
				$account_lock_period	   = (int) $la_settings['account_lock_period'];
				$account_lock_max_attempts = (int) $la_settings['account_lock_max_attempts'];

				$account_blocked_message   = "Sorry, this account is temporarily blocked. Please try again after {$account_lock_period} minutes.";

				//check the lock period
				$login_attempt_date 	   = strtotime($login_attempt_date);
				$account_lock_expiry_date  = $login_attempt_date + (60 * $account_lock_period);
				$current_datetime 		   = strtotime(date("Y-m-d H:i:s"));

				//if lock period still valid, check max attempts
				if($current_datetime < $account_lock_expiry_date){

					//if max attempts already exceed the limit, block the user
					if($login_attempt_count >= $account_lock_max_attempts){
						$login_is_valid = false;
						$_SESSION['LA_LOGIN_ERROR'] = $account_blocked_message;

						//send login notification
						if($la_settings['enable_registration_notification']){
							$login_user = $_SESSION['email'];
							$site_name = "https://".$_SERVER['SERVER_NAME'];
							$subject = "Continuum GRC Account Login Notification";
							$content = "<h2>Continuum GRC Account Login Notification</h2>";
							$content .= "<h3>".$login_user." has failed to login to the admin portal of ".$site_name.". This account is temporarily blocked for {$account_lock_period} minutes.</h3>";
							sendUserManagementNotification($dbh, $la_settings, $subject, $content);
						}
					}
				}else{

					//else if lock period already expired
					$query = "UPDATE ".LA_TABLE_PREFIX."users
								  SET
								  	 login_attempt_date = ?,
								  	 login_attempt_count = ?
							    WHERE
							    	 user_id = ?";

					//if password is correct, reset to zero
					//else if password is incorrect, set counter to 1
					if($login_is_valid){
						$login_attempt_date  = '';
						$login_attempt_count = 0;
					}else{
						$login_attempt_date  = date("Y-m-d H:i:s");
						$login_attempt_count = 1;
					}

					$params = array($login_attempt_date,$login_attempt_count,$user_id);
					la_do_query($query,$params,$dbh);
				}
			}

			//if login is validated
			if($login_is_valid){
				//save the code into the log
				if(count($tsv_code_log_array) >= 10){
					array_shift($tsv_code_log_array);
				}

				$tsv_code_log_array[] = $tsv_code;
				$tsv_code_log = implode(',', $tsv_code_log_array);
				$remember_me  = $_SESSION['la_tsv_verify_remember_me'];

				//invalidate la_tsv_verify session
				$_SESSION['la_tsv_verify'] = '';
				$_SESSION['la_tsv_verify_remember_me'] = '';
				unset($_SESSION['la_tsv_verify']);
				unset($_SESSION['la_tsv_verify_remember_me']);

				//reset login counter
				$query = "UPDATE ".LA_TABLE_PREFIX."users
								  SET
								  	 login_attempt_date = NULL,
								  	 login_attempt_count = 0
							    WHERE
							    	 user_id = ?";
				$params = array($user_id);
				la_do_query($query,$params,$dbh);

				//regenerate session id for protection against session fixation
				session_regenerate_id();

				//set the session variables for the user=========
				$_SESSION['la_logged_in'] = true;
				$_SESSION['la_user_id']   = $user_id;
				$_SESSION['la_user_privileges']['priv_administer'] = $priv_administer;
				$_SESSION['la_user_privileges']['priv_new_forms']  = $priv_new_forms;
				$_SESSION['la_user_privileges']['priv_new_themes'] = $priv_new_themes;
				//===============================================

				//update last_login_date and last_ip_address
				$last_login_date = date("Y-m-d H:i:s");
				$last_ip_address = $_SERVER['REMOTE_ADDR'];

				$query  = "UPDATE ".LA_TABLE_PREFIX."users set last_login_date=?,last_ip_address=?,tsv_code_log=? WHERE `user_id`=?";
				$params = array($last_login_date,$last_ip_address,$tsv_code_log,$user_id);
				la_do_query($query,$params,$dbh);

				// add user activity to log: activity - 6 (LOGIN)
				addUserActivity($dbh, $_SESSION['la_user_id'], 0, 6, "", time(), $last_ip_address);

				//if the user select the "remember me option"
				//set the cookie and make it active for the next 30 days
				if(!empty($remember_me)){
					$hasher 	   = new Sha256Hash();
					$cookie_hash = $hasher->HashPassword(mt_rand()); //generate random hash and save it into ap_users table

					$query = "update ".LA_TABLE_PREFIX."users set cookie_hash=? where `user_id`=?";
			   		$params = array($cookie_hash,$user_id);
			   		la_do_query($query,$params,$dbh);

			   		//send the cookie
			   		setcookie('la_remember',$cookie_hash, time()+3600*24*30, "/");
				}

				//send login notification
				if($la_settings['enable_registration_notification']){
					$login_user = $_SESSION['email'];
					$site_name = "https://".$_SERVER['SERVER_NAME'];
					$subject = "Continuum GRC Account Login Notification";
					$content = "<h2>Continuum GRC Account Login Notification</h2>";
					$content .= "<h3>".$login_user." has logged into the admin portal of ".$site_name.".</h3>";
					sendUserManagementNotification($dbh, $la_settings, $subject, $content);
				}
				
				if(!empty($_SESSION['prev_referer'])){
					$next_page = $_SESSION['prev_referer'];

					unset($_SESSION['prev_referer']);
					header("Location: ".$next_page);

					exit;
				}else{
					header("Location: manage_forms.php");
					exit;
				}


			}
		}


	}


?>
<!DOCTYPE html>
<html lang="en">
  <head>
  <title>IT Audit Machine Admin Panel</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="robots" content="index, nofollow" />
  <meta id="csrf-token-meta" name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
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
		if(!empty($la_settings['admin_image_url'])){
			$itauditmachine_logo_main = $la_settings['admin_image_url'];
		}else{
			$itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
		}
	?>
        <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="IT Audit Machine" /> </div>
        <div class="clear"></div>
      </div>
    <div id="main">
        <div id="content">
        <div class="post login_main">
            <div style="padding-top: 10px">
            <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
                <h3>Enter Security Code</h3>
                <p>We have sent you code on your registered email id. Please enter the code to verify</p>
                <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
              </div>
            <div style="margin-top: 10px">
                <form id="form_login" class="itauditm"  method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
                <div style="display:none;">
                    <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                  </div>
                <ul>
                    <?php if(!empty($_SESSION['LA_LOGIN_ERROR'])){ ?>
                    <li id="li_login_notification">
                    <h5><?php echo $_SESSION['LA_LOGIN_ERROR']; ?></h5>
                  </li>
                    <?php
									   unset($_SESSION['LA_LOGIN_ERROR']);
									}
								?>
                    <li id="li_email_address">
                    <label class="desc" for="tsv_code">Enter your 6-digit code</label>
                    <div>
                        <input id="tsv_code" autocomplete="off" style="width: 125px" name="tsv_code" class="element text medium" type="text" maxlength="255" value="<?php echo noHTML($username); ?>"/>
                      </div>
                  </li>
                    <li id="li_submit" class="buttons" style="overflow: auto;margin-top: 5px;margin-bottom: 10px">
                    <input type="hidden" name="submit" id="submit" value="1">
                    <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px"> Verify Code </button>
                  </li>
                  </ul>
              </form>
              </div>
          </div>
          </div>
      </div>
        <?php

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
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				id: 'btn-welcome-message-ok',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
					location.href='login-using-code.php';
				}
			}
		]
	});

	$("#dialog-error-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
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
		  url: "../portal/ajax-request.php",
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
<div id="dialog-welcome-message" title="Single-usage code" class="buttons" style="display: none; text-align:center;"><img alt="" height="48" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
    <br>
    Enter the code sent to your email</div>
<div id="dialog-error-message" title="Single-usage code" class="buttons" style="display: none; text-align:center;"><img alt="" height="48" src="images/navigation/ED1C2A/50x50/Warning.png" width="48"><br>
    <br>
    Something went wrong. Try again</div>
