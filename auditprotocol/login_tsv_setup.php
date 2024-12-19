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

	//check for tsv setup session, if not exist, redirect back to login page
	if(empty($_SESSION['la_tsv_setup'])){
		header("Location: index.php");
		exit;
	}

	//echo 'here:'.$_SESSION['email'];die();

	$user_id  = $_SESSION['la_tsv_setup'];

	$query  = "SELECT
					`priv_administer`,
					`priv_new_forms`,
					`priv_new_themes`,
					`user_email`,
					`is_examiner`
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
	$is_examiner		  = (int) $row['is_examiner'];
	$stored_user_email 	  = $row['user_email'];

	$authenticator = new PHPGangsta_GoogleAuthenticator();

	//initialize tsv secret for qrcode
	if(empty($_SESSION['la_tsv_setup_secret'])){
		$_SESSION['la_tsv_setup_secret'] =  $authenticator->createSecret();
	}
	$tsv_secret = $_SESSION['la_tsv_setup_secret'];
	$totp_data  = "otpauth://totp/IT Audit Machine:{$stored_user_email}?secret={$tsv_secret}";

	//verify security code
	if(!empty($_POST['submit'])){
		$input 	  = la_sanitize($_POST);

		if($_POST['submit'] == 1){
			$how_user_registered = 0;
			$tsv_code = $input['tsv_code'];
			$tsv_result = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  //8 means 4 minutes before or after
		}else{
			if($_SESSION['ONE_TIME_CODE'] == $input['single_usage_code'] && $_SESSION['EXPIRY_TIME'] >= time()){
				$tsv_result = true;
				$tsv_code = '';
				$how_user_registered = 1;

				unset($_SESSION['LA_CODE_SEND']);
				unset($_SESSION['ONE_TIME_CODE']);
				unset($_SESSION['EXPIRY_TIME']);
			}
		}

		/*$tsv_code = $input['tsv_code'];
		$tsv_result    = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  //8 means 4 minutes before or after*/

		if($tsv_result === true){
				$remember_me  = $_SESSION['la_tsv_setup_remember_me'];

				//invalidate la_tsv_setup session
				$_SESSION['la_tsv_setup'] = '';
				$_SESSION['la_tsv_setup_remember_me'] = '';
				unset($_SESSION['la_tsv_setup']);
				unset($_SESSION['la_tsv_setup_remember_me']);

				//regenerate session id for protection against session fixation
				session_regenerate_id();

				//set the session variables for the user=========
				$_SESSION['la_logged_in'] = true;
				$_SESSION['la_user_id']   = $user_id;
				$_SESSION['la_user_privileges']['priv_administer'] = $priv_administer;
				$_SESSION['la_user_privileges']['priv_new_forms']  = $priv_new_forms;
				$_SESSION['la_user_privileges']['priv_new_themes'] = $priv_new_themes;
				$_SESSION['is_examiner'] = $is_examiner;
				if($la_settings['enable_welcome_message_notification'] == 1 && $_SESSION['la_user_id'] != 1) {
					$_SESSION['admin_login_message_enabled'] = true;
				}
				//===============================================

				//update last_login_date and last_ip_address
				$last_login_date = date("Y-m-d H:i:s");
				$last_ip_address = $_SERVER['REMOTE_ADDR'];

				$query  = "UPDATE ".LA_TABLE_PREFIX."users set last_login_date=?,last_ip_address=?,tsv_code_log=?,tsv_secret=?,tsv_enable=1,how_user_registered=? WHERE `user_id`=?";
				$params = array($last_login_date,$last_ip_address,$tsv_code,$tsv_secret,$how_user_registered,$user_id);
				la_do_query($query,$params,$dbh);

				//if the user select the "remember me option"
				//set the cookie and make it active for the next 30 days
				if(!empty($remember_me)){
					$hasher 	 = new Sha256Hash();
					$cookie_hash = $hasher->HashPassword(mt_rand()); //generate random hash and save it into ap_users table

					$query = "update ".LA_TABLE_PREFIX."users set cookie_hash=? where `user_id`=?";
			   		$params = array($cookie_hash,$user_id);
			   		la_do_query($query,$params,$dbh);

			   		//send the cookie
			   		setcookie('la_remember',$cookie_hash, time()+3600*24*30, "/");
				}

				$_SESSION['LA_SUCCESS'] = 'Account Successfully Verified.';

				// add user activity to log: activity - 6 (LOGIN)
				addUserActivity($dbh, $_SESSION['la_user_id'], 0, 6, "", time(), $last_ip_address);

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
		}else{
			$_SESSION['LA_LOGIN_ERROR'] = 'Error! Incorrect code.';
		}
	}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>IT Audit Machine Admin Panel</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="index, nofollow" />
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
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="custom-view-js-func.js"></script>
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
		<div id="logo">
			<img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="IT Audit Machine" />
		</div>


		<div class="clear"></div>

	</div>
	<div id="main">


		<div id="content">
			<div class="post login_main">

				<div style="padding-top: 10px">

					<div>
						<img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
						<h3>Verification Required</h3>
						<p>Please follow these steps below to continue:</p>
						<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
					</div>

					<div style="margin-top: 10px">
							<form id="form_login" class="itauditm"  method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
							<ul>

								<?php if(!empty($_SESSION['LA_LOGIN_ERROR'])){ ?>
									<li id="li_login_notification">
										<h5><?php echo $_SESSION['LA_LOGIN_ERROR']; ?></h5>
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
											<br><b>Step 1. Open Authenticator mobile app</b></li>
										<li>
											Open your authenticator mobile app. If you don't have it yet, you can install any of the following apps:
											<div style="margin-top: 10px;padding-left: 20px">
											&#8674; <a class="app_link" href="http://support.google.com/accounts/bin/answer.py?hl=en&answer=1066447" target="_blank">Google Authenticator</a> (Android/iPhone/BlackBerry)<br/>
											&#8674; <a class="app_link" href="http://guide.duosecurity.com/third-party-accounts" target="_blank">Duo Mobile</a> (Android/iPhone)<br/>
											&#8674; <a class="app_link" href="http://www.amazon.com/gp/product/B0061MU68M" target="_blank">Amazon AWS MFA</a> (Android)<br/>
											&#8674; <a class="app_link" href="https://itunes.apple.com/us/app/hde-otp-generator/id571240327?mt=8" target="_blank">HDE OTP Generator</a> (iPhone)<br/>
											&#8674; <a class="app_link" href="https://itunes.apple.com/us/app/2stp-authenticator/id954311670?mt=8" target="_blank">2STP Authenticator</a> (iPhone)<br/>
											&#8674; <a class="app_link" href="https://itunes.apple.com/us/app/otp-auth/id659877384?mt=8" target="_blank">OTP Auth</a> (iPhone)<br/>
											&#8674; <a class="app_link" href="http://www.windowsphone.com/en-US/apps/021dd79f-0598-e011-986b-78e7d1fa76f8" target="_blank">Authenticator</a> (Windows Phone)<br/>
<br/>
											</div>
										</li>
										<li class="tsv_setup_title"><b>Step 2. Scan Barcode</b></li>
										<li>
											Use your authenticator app to scan the barcode below:
											<div style="width: 80%;padding: 20px;text-align: center">
												<div id="qrcode"></div>
											</div>
											or you can enter this secret key manually: <strong><?php echo $tsv_secret; ?></strong>
<br/>
<br/>
										</li>
										<li class="tsv_setup_title">Step 3. Verify Code</li>
										<li>
											Once your app is configured, enter the <strong>six-digit security code</strong> generated by your app.
										</li>
									</ul>
								</li>
								<li id="li_tsv_code">
									<label class="desc" for="tsv_code">Single-usage code</label>
									<div>
										<input id="tsv_code" style="width: 125px" name="tsv_code" class="element text medium" type="text" maxlength="255" value="<?php echo htmlspecialchars($username); ?>"/>
									</div>
								</li>
					    		<li id="li_submit" class="buttons" style="overflow: auto;margin-top: 5px;margin-bottom: 10px">
					    			<input type="hidden" name="submit" id="submit" value="1">
							    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px">
								        Verify Code
								    </button>
								</li>
							</ul>
							</form>
							<?php if(!isset($la_settings['disable_email_based_otp']) || empty($la_settings['disable_email_based_otp'])): ?>
							<!-- new section -->

							<div style="width:100%; margin: 5px 0 0px 0; float:left;">
							<div style="border: 1px solid #000;border-style: dotted; width:100%; margin: 0 10px 20px 0px;"></div>
							  <div style="margin:0 10px 20px 0px;"><h3>Option 2</h3><br><strong>Step 1: Enter in your email address below used for your ITAM username and click submit.</strong></div>
							  <div style="width:100%; margin: 0 0 5px 0;"><b>Email address.</b></div>
							<div><input type="text" style="width:250px; margin: 0 0 20px 0; background-color: #bbbbbb;" value="<?php echo $_SESSION['email']; ?>" readonly=""></div>
							<div><button style="float: left;border-radius: 2px" name="submit_button" id="sendcode_button" class="bb_button bb_green" type="submit"> Submit </button></div>
							</div>
							<form method="post" action="login_tsv_setup.php">
								<input type="hidden" name="submit" id="submit" value="2">
								<div style="width:100%; margin-top:20px; float:left; margin-bottom: 20px;">
								<div style="margin:0 10px 20px 0px;"><b>Step 2: Look for an email with a six-character security code in it and enter it in the field below.</b></div>
								  <div style="width:100%; margin: 0 0 5px 0;"><b>Enter your six-character code.</b></div>
								<div><input type="text" name="single_usage_code" style="width:150px; margin: 0 0 20px 0;"></div>
								<div style="margin-bottom:20px;"><button style="float: left;border-radius: 2px" name="verify_button" id="verify_button" class="bb_button bb_green" type="submit"> Verify Code </button></div>
								<div style="clear:both;"></div>
							</div>
							</form>
						<?php endif; ?>


					</div>

				</div>

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
<div id="dialog-welcome-message" title="Single-usage code" class="buttons" style="display: none; text-align:center;"> <img src="images/navigation/005499/50x50/Notice.png" title="Success" /><p id="dialog-confirm-edit-msg">Please enter the security code sent to your email address<br /></p></div>
<div id="dialog-error-message" title="Single-usage code" class="buttons" style="display: none; text-align:center;"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Error" /><p id="dialog-confirm-edit-msg">We are sorry but something went wrong. Please make sure you are not also logged into the User Portal and try again.<br /></p></div>
