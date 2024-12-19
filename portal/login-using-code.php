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
	require('lib/swift-mailer/swift_required.php');
	require('includes/helper-functions.php');
	require('lib/password-hash.php');
	require('../itam-shared/includes/helper-functions.php');

	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();

	//check for verify session, if not exist, redirect back to login page
	if(empty($_SESSION['la_tsv_verify'])){
		header("Location: index.php");
		exit;
	}

	//immediately redirect to installer page if the config values are correct but no ap_forms table found
	$query = "select count(*) from ".LA_TABLE_PREFIX."settings";
	$sth = $dbh->prepare($query);
	try{
		$sth->execute($params);
	}catch(PDOException $e) {
		header("Location: /auditprotocol/installer.php");
		exit;
	}

	$la_settings = la_get_settings($dbh);

	//redirect to account manager if already logged-in
	if(!empty($_SESSION['la_client_logged_in']) && $_SESSION['la_client_logged_in'] == true){
		header("Location: client_account.php");
		exit;
	}

	if(!empty($_POST['submit']) && isset($_POST['submit'])){

		$user_id = $_SESSION['la_tsv_verify'];
		$one_time_code = trim($_POST['one_time_code']);

		if(empty($one_time_code)){
			$_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect single-usage code!';
		}else{
			$password_is_valid = false;
			$one_time_code_is_valid = false;

			//get the password hash from the database
			$query  = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE (`client_user_id` = ?)";
			$params = array($user_id);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			$stored_password_hash = $row['password'];
			$user_id 			  = $row['client_user_id'];
			$client_id 			  = $row['client_id'];
			$selectedEntity       = $_SESSION['la_client_entity_id'];
			$hasher 	   		  = new Sha256Hash();
			$check_result  		  = true;
			$tsv_enable	  		  = (int) $row['tsv_enable'];
			$tsv_secret 		  = $row['tsv_secret'];

			$code_expiry_time = $_SESSION['EXPIRY_TIME'];

			if($one_time_code == $_SESSION['ONE_TIME_CODE'] && $code_expiry_time >= $log_time){
				// login log maintain
				$log_query = "INSERT INTO `".LA_TABLE_PREFIX."portal_user_login_log` (`log_id`, `client_user_id`, `last_login`, `user_ip`) VALUES (NULL, ?, ?, ?);";
				la_do_query($log_query,array($user_id, time(), $_SERVER['REMOTE_ADDR']),$dbh);

				unset($_SESSION['LA_CODE_SEND']);
				unset($_SESSION['ONE_TIME_CODE']);
				unset($_SESSION['EXPIRY_TIME']);
				//regenerate session id for protection against session fixation
				session_regenerate_id();

				//set the session variables for the user=========
				$_SESSION['la_client_logged_in'] = true;
				$_SESSION['la_client_user_id']   = $user_id;
				$_SESSION['la_client_client_id'] = $client_id;
				$_SESSION['la_client_entity_id'] = $selectedEntity;
				//===============================================
				//log user login time
				logUserSession($dbh, $_SESSION['la_client_user_id'], session_id(), 'login', 0);

				setEntityRelationForNormalUsers($dbh, $client_id, $user_id);

				$_SESSION['la_user_logged_in_time'] = time();

				if($la_settings['enable_welcome_message_notification'] == 1) {
                    $_SESSION['user_login_message_enabled'] = true;
                }

				//send login notification
				if($la_settings['enable_registration_notification']){
					$login_user = $_SESSION['email'];
					$site_name = "https://".$_SERVER['SERVER_NAME'];
					$subject = "Continuum GRC Account Login Notification";
					$content = "<h2>Continuum GRC Account Login Notification</h2>";
					$content .= "<h3>".$login_user." has logged into the user portal of ".$site_name.".</h3>";
					sendUserManagementNotification($dbh, $la_settings, $subject, $content);
				}
				
				if(!empty($_SESSION['prev_referer'])){
					$next_page = $_SESSION['prev_referer'];

					unset($_SESSION['prev_referer']);
					header("Location: ".$next_page);

					exit;
				}else{
					header("Location: client_account.php");
					exit;
				}
			}else{
				$_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Single-usage code expired. Regenerate code';
			}
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
<style>
.auto-style2 {
    display: none;
    text-align: center;
}
</style>
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
  <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
  <div class="clear"></div>
</div>
<div id="main">
<div id="content" style="margin: 0px!important;">
  <div class="post login_main">
    <div style="padding-top: 10px">
      <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
        <h3>Enter Security Code</h3>
        <p>We have sent you code on your registered email id. Please enter the code to verify.</p>
        <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
      </div>
      <div style="margin-top: 10px">
        <form id="form_login" class="itauditm"  method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" autocomplete="off">
          <div style="display:none;">
            <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type="hidden" id="reset_mfa_user_id" value="<?php echo $_SESSION['la_tsv_verify']; ?>" />
          </div>
          <ul>
            <?php if(!empty($_SESSION['LA_CLIENT_LOGIN_ERROR'])){ ?>
            <li id="li_login_notification">
              <h5><?php echo $_SESSION['LA_CLIENT_LOGIN_ERROR']; ?></h5>
            </li>
            <?php
									   unset($_SESSION['LA_CLIENT_LOGIN_ERROR']);
									}
								?>
            <li id="li_one_time_code">
              <label class="desc" for="one_time_code">Single-usage code </label>
              <div>
                <input id="one_time_code" name="one_time_code" class="element text medium" type="text" maxlength="255" value="" style="width:125px;" />
              </div>
            </li>
            <li id="li_submit" class="buttons" style="overflow: auto;margin-top: 5px;margin-bottom: 10px">
              <input type="hidden" name="submit" id="submit" value="1">
              <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px">  Verify Code </button>
              <button class="bb_button bb_green" id="reset_mfa_button" style="padding: 5px 10px 5px 7px;    line-height: 17px;border-radius: 2px">Reset MFA</button>
            </li>
          </ul>
        </form>
      </div>
    </div>
  </div>
</div>
<div id="dialog-login-page" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-login-page-msg"> Success </p>
</div>
<div id="dialog-welcome-message2" title="Single-usage code" class="buttons" style="display: none"><img alt="" height="48" src="images/navigation/005499/50x50/Notice.png" width="48"><br><br>Enter the code sent to your email</div>
<div id="dialog-error-message" title="Single-usage code" class="buttons" style="display: none"><img alt="" height="48" src="images/navigation/ED1C2A/50x50/Warning.png" width="48"><br><br>Something went wrong. Try again</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/login_admin.js"></script>
EOT;
	require('includes/footer.php');
?>

<script type="text/javascript">
$(document).ready(function(){

	$("#dialog-welcome-message2").dialog({
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
			url: "ajax-request.php",
			beforeSend:function(){},
			success: function(response){
				if(response == 'LA_CODE_SEND'){
					$("#dialog-welcome-message2").dialog('open').click();
				}else{
					$("#dialog-error-message").dialog('open').click();
				}
			}
		});
	});

	$('#reset_mfa_button').click(function(e){
		e.preventDefault();
		var user_id  = $("#reset_mfa_user_id").val();
		//do the ajax call to reset MFA
		$.ajax({
			type: "POST",
			async: true,
			url: "../auditprotocol/reset_authentication.php",
			data: {
				action: 'reset_user_mfa',
				origin: 'login_verify',
				user_id: user_id
			},
			cache: false,
			global: false,
			dataType: "json",
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$("#dialog-error-message").dialog("option", "title", "Unable to Reset MFA!");
				$("#dialog-error-message").dialog('open');
			},
			success: function(response_data){
				if(response_data.status == 'ok'){
					window.location.href = "index.php";
				} else {
					$("#dialog-error-message").dialog("option", "title", "Unable to Reset MFA!");
					$("#dialog-error-message").dialog('open');
				}
			}
		});
	});
});
</script>
