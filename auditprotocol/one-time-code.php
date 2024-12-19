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
	require('lib/swift-mailer/swift_required.php');
	require('lib/password-hash.php');

	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();

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

	$_SESSION['LA_CODE_SEND'] = false;

	if(!empty($_POST['submit']) && isset($_POST['submit'])){

		$username = strtolower(trim($_POST['client_username']));

		if(empty($username)){
			$_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email';
		}else{
			$password_is_valid = false;

			//get the password hash from the database
			$query  = "SELECT
							*
						FROM
							`".LA_TABLE_PREFIX."ask_client_users`
					   WHERE
					   		(`username` = ? OR `email` = ?)";
			$params = array($username,$username);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			$user_id 			  = $row['client_user_id'];
			$client_id 			  = $row['client_id'];
			$user_email			  = $row['email'];

			if($row){
				$password_is_valid = true;
			}

			if($password_is_valid){

				//regenerate session id for protection against session fixation
				session_regenerate_id();

				$email_param 				= array();
				$email_param['from_name'] 	= 'IT Audit Machine';
				$email_param['from_email'] 	= $la_settings['default_from_email'];
				$email_param['subject'] 	= $email_param['from_name'].' - Single-use authentication code';
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
				$email_address = $email;
				$email_content_type = 'text/html';

				$base64_client_id = base64_encode($client_id);
				$base64_email = base64_encode($email);
				$random_code = md5(time());
				$one_time_code = substr($random_code, 0, 6);
		$body = <<<EOD
<html>
	<head></head>
	<body>
		<p>Your single-usage code: {$one_time_code}</p>
	</body>
</html>
EOD;
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

				$query  = "DELETE FROM `".LA_TABLE_PREFIX."client_user_one_time_code` WHERE `client_user_id` = :client_user_id AND `user_type` = :user_type";
				$params = array(':client_user_id' => $user_id, ':user_type' => 'P');
				@la_do_query($query,$params,$dbh);

				$query  = "INSERT INTO `".LA_TABLE_PREFIX."client_user_one_time_code` (`one_time_code_id`, `client_user_id`, `one_time_code`, `datetime_send`) VALUES (NULL, :client_user_id, :one_time_code, :datetime_send)";
				$params = array(':client_user_id' => $user_id, ':one_time_code' => $one_time_code, ':datetime_send' => time());
				@la_do_query($query,$params,$dbh);

				$_SESSION['LA_CODE_SEND'] = true;

			}else{
				$_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email';
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
  <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
  <div class="clear"></div>
</div>
<div id="main">
<div id="content" style="margin: 0 0 20px !important;">
  <div class="post login_main">
    <div style="padding-top: 10px">
      <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
        <h3>User Portal</h3>
        <p>Welcome to the Continuum GRC IT Audit Machine (ITAM).</p>
        <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
      </div>
      <div style="border-bottom: 1px dotted #CCCCCC;margin-top: 10px">
        <form id="form_login" class="itauditm"  method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" autocomplete="off">
          <ul>
            <?php if(!empty($_SESSION['LA_CLIENT_LOGIN_ERROR'])){ ?>
            <li id="li_login_notification">
              <h5><?php echo $_SESSION['LA_CLIENT_LOGIN_ERROR']; ?></h5>
            </li>
            <?php
				   unset($_SESSION['LA_CLIENT_LOGIN_ERROR']);
				}
			?>
            <li id="li_email_address">
              <label class="desc" for="client_username">Email</label>
              <div>
                <input id="client_username" name="client_username" class="element text large" type="text" maxlength="255" value="<?php echo htmlspecialchars($username); ?>"/>
              </div>
            </li>
            <li id="li_submit" class="buttons" style="overflow: auto">
              <input type="hidden" name="submit" id="submit" value="1">
              <button type="submit" class="bb_button bb_green" name="submit_button" style="float: left;border-radius: 2px"> Submit </button>
              <button type="button" class="bb_button bb_green" style="float: left;border-radius: 2px" onclick="document.location='client_register.php'" > Register a New Account </button>
            </li>
          </ul>
        </form>
      </div>
      <ul style="float: right;padding-top: 5px">
        <li>&nbsp;</li>
      </ul>
    </div>
  </div>
</div>
<div id="dialog-login-page" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-login-page-msg"> Success </p>
</div>
<div id="dialog-welcome-message" title="" class="buttons" style="display: none"><img alt="" height="48" src="images/navigation/005499/50x50/Notice.png" width="48"><br><br>A single-use authentication code has been sent to your email address. Please use that code to continue with the login process</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/login_admin.js"></script>
EOT;
	require('includes/footerlogin.php');
?>
<script type="text/javascript">
$(document).ready(function() {
	//dialog box to confirm user deletion
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
					location.href = "login-using-code.php";
				}
			}
		]
	});
	<?php
	if(isset($_SESSION['LA_CODE_SEND']) && $_SESSION['LA_CODE_SEND'] == TRUE){
	?>
	$("#dialog-welcome-message").dialog('open').click();
	<?php
		unset($_SESSION['LA_CODE_SEND']);
	}
	?>
});
</script>
