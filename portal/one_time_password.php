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
	require('lib/swift-mailer/swift_required.php');
	require('lib/password-hash.php');
	
	

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$hasher = new Sha256Hash();

	$sent_again = false;
	$forced_password = false;
	if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) )
		$forced_password = true;

	if(!empty($_POST['submit'])){

		$token = $_POST['token'];
		$newpassword = $_POST['new_password'];
		$retype = $_POST['confirm_password'];

		//add password validations here
		if( empty($newpassword) ) {
			$la_login_error = "Password can not be empty.";
		} else if ( empty($retype) ) {
			$la_login_error = "Confirm Password can not be empty.";
		} else if ( $newpassword != $retype )
			$la_login_error = "Password does not match";
		else{
			$newpassword = $retype;

			if(strlen($newpassword) < 8){
				$la_login_error = "The new password must be a minimum of 8 characters";
			} else if( !preg_match("#[0-9]+#", $newpassword) ) {
				$la_login_error = "The new password must include at least one number!";
			}else if ( !preg_match("#[a-z]+#", $newpassword) ) {
				$la_login_error = "The new password must include at least one letter!";
			}else if ( !preg_match("#[A-Z]+#", $newpassword) ) {
				$la_login_error = "The new password must include at least one CAPS!";
			}else if ( !preg_match("#\W+#", $newpassword) ) {
				$la_login_error = "The new password must include at least one symbol!";
			}
		}		

		if( !empty($la_login_error) ) {
			$_SESSION['LA_LOGIN_ERROR'] = $la_login_error;
			$show_password_change_form = true;
		} else {
			//if passed all validations update password
			$query  = "SELECT
						`client_user_id` as `user_id`,
						`password` as `user_password`,
						`email` as `user_email`
					FROM
						`".LA_TABLE_PREFIX."ask_client_users`
				   WHERE
				   		`token`=?";

			$params = array($token);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			if( !empty( $row['user_id'] ) ) {

				//get last 14 passwords used by user
				$query_old_passwords = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=0 order by `id` DESC limit 14";
				
				$sth_old_passwords = la_do_query($query_old_passwords,array($row['user_id']),$dbh);
				$old_passwords = [];
				
				while($row_old_passwords = la_do_fetch_result($sth_old_passwords)){
					$old_passwords[]  = $hasher->CheckPassword($newpassword, $row_old_passwords['password']);
				}

				if( in_array(1, $old_passwords) ) {
					$_SESSION['LA_LOGIN_ERROR'] = "Your new password must be different from your former 14 passwords.";
					$show_password_change_form = true;
				} else {
					$new_password_hash = $hasher->HashPassword($newpassword);

					$query = "UPDATE ".LA_TABLE_PREFIX."ask_client_users SET password = ?, token = ?, password_change_date = ? WHERE client_user_id = ?";
					$params = array($new_password_hash, '', time(), $row['user_id']);
					la_do_query($query,$params,$dbh);

					//save new password to `old_password_hash` table
					insert_old_password_hash($row['user_id'], $new_password_hash, 0, $dbh);
					
					$_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Password updated successfully. Use new password to login to your account.';
					header("Location: index.php");	
					exit;
				}
			}
		}
	} else {
		$_SESSION['LA_LOGIN_ERROR'] = '';
		$show_password_change_form = false;

		if (isset($_GET["utoken"]) && preg_match('/^[0-9A-F]{40}$/i', $_GET["utoken"])) {
		    $token = $_GET["utoken"];

			//check if the temp_password_hash is valid
			$query  = "SELECT
							`tstamp`,
							`client_user_id` as `user_id`,
							`email` as `user_email`,
							`full_name`
						FROM
							`".LA_TABLE_PREFIX."ask_client_users`
					   WHERE
					   		`token`=?";

			$params = array($token);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			// 1 day measured in seconds = 60 seconds * 60 minutes * 24 hours
			$delta = 86400 * (int) $la_settings["one_time_url_expiration_date"];

			if( !empty( $row['user_id'] ) ) {
				// Check to see if link has expired
				if ($_SERVER["REQUEST_TIME"] - $row['tstamp'] > $delta) {
					
					$token = sha1(uniqid($row['user_email'], true));
					$tstamp = $_SERVER["REQUEST_TIME"];

					$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `token` = ?, tstamp = ? WHERE `client_user_id` = ?;";
					la_do_query($query, array($token, $tstamp, $row['user_id']), $dbh);

					$params = array('token' => $token, 'forgot_password' => 1);
				    $la_send_one_time_url = la_send_one_time_url($dbh, $row['full_name'], $row['user_email'], $params);

				    $sent_again = true;
					$_SESSION['LA_LOGIN_ERROR'] = "<h5 class=\"green\">This link has expired. A new link had been sent to your registered email address.</h5>";
				} else {
					$show_password_change_form = true;
					if( $forced_password ) {
						$password_range = range(15, 20);
						$password_length = array_rand(array_flip($password_range));
						$new_password = randomPassword($password_length);

						$new_password_hash = $hasher->HashPassword($new_password);

						$query = "UPDATE ".LA_TABLE_PREFIX."ask_client_users SET password = ?, token = ?, password_change_date = ? WHERE client_user_id = ?";
						$params = array($new_password_hash, '', time(), $row['user_id']);
						la_do_query($query,$params,$dbh);

						$show_password_change_form = false;
						$_SESSION['LA_LOGIN_ERROR'] = "<h5 class=\"green\">Enforce 50% Rule on Passwords is enabled. Your New password is <strong>{$new_password}</strong></h5>";
					}
				}
			} else {
				$_SESSION['LA_LOGIN_ERROR'] = "Valid token not provided.";
			}
		} else {
		    $_SESSION['LA_LOGIN_ERROR'] = "Valid token not provided.";
		}
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
        if(!empty($la_settings['admin_image_url'])){
                $itauditmachine_logo_main = $la_settings['admin_image_url'];
        }else{
                if(!empty($la_settings['admin_theme'])){
                        $itauditmachine_logo_main = 'images/itauditmachine_logo_'.$la_settings['admin_theme'].'.png';
                }else{
                        $itauditmachine_logo_main = 'images/ITAM_LOGO2018_150xRed.png';
                }
        }
?>
  <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
  <div class="clear"></div>
</div>
<div id="main">
<div id="content" style="margin: 0 0 20px !important;">
  <div class="post login_main">
    <div style="padding-top: 10px">
      <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
        <h3>Create your password here</h3>
        <p>Create password below to manage the Continuum GRC IT Audit Machine's digital assets.</p>
        <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
      </div>
      <?php ?>
      <div style="border-bottom: 1px dotted #CCCCCC;margin-top: 10px">
        <form id="form_change_password" class=""  method="post" action="">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        </div>
          <ul>
            <?php if(!empty($_SESSION['LA_LOGIN_ERROR'])){ ?>
	            <li id="li_login_notification">
	              <p style="color: #ef1829;"><?php echo $_SESSION['LA_LOGIN_ERROR']; ?></p>
	            </li>
            <?php
				   unset($_SESSION['LA_LOGIN_ERROR']);
				}
			?>
            <?php if($show_password_change_form){ ?>
            
            
            <li id="li_email_address">
              <label class="desc" for="admin_username">New Password **</label>
              <div>
                <input id="new_password" name="new_password" class="element text large" type="password" maxlength="255"/>
              </div>
            </li>
            <li id="li_password">
              <label class="desc" for="admin_password">Confirm Password **</label>
              <div>
                <input id="confirm_password" name="confirm_password" class="element text large" type="password" maxlength="255"/>
              </div>
            </li>
            
            <li id="li_submit" class="buttons" style="overflow: auto">
              <input type="hidden" name="submit" id="submit" value="1">
              <input type="hidden" name="token" value="<?=$token;?>">
              <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px">  Create Password </button>
            </li>
            
            <li>
            	<p style="margin-top: 10px;">* Required fields.</p>
				<p>** The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.</p>
            </li>
        	

            <?php } else {
            	if( ! ( $sent_again || $forced_password ) )
            		echo '<h5 style="color: #ef1829; text-align: center;">This url has expired. Please contact administrator.</h5>';
            } ?>
          </ul>
        </form>
      </div>
      
    </div>
  </div>
</div>
<?php
	require('includes/footerlogin.php');
?>