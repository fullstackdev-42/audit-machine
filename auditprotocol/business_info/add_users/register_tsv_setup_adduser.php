<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('../../includes/init.php');

	require('../../config.php');
	require('../../includes/db-core.php');
	require('../../includes/helper-functions.php');

	require('../../includes/filter-functions.php');
	require('../../lib/google-authenticator.php');
	require('../../lib/password-hash.php');


	$ssl_suffix  = la_get_ssl_suffix();

	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$stored_user_email 	  = $_SESSION['email'];

	$authenticator = new PHPGangsta_GoogleAuthenticator();

	//initialize tsv secret for qrcode
	if(empty($_SESSION['la_tsv_setup_secret'])){
		$_SESSION['la_tsv_setup_secret'] =  $authenticator->createSecret();
	}

	$tsv_secret = $_SESSION['la_tsv_setup_secret'];
	$totp_data  = "otpauth://totp/IT Audit Machine:{$stored_user_email}?secret={$tsv_secret}";

	if(!empty($_POST['submit'])){
		$input 	  = la_sanitize($_POST);
		$tsv_code = $input['tsv_code'];

		$tsv_result    = $authenticator->verifyCode($tsv_secret, $tsv_code, 8);  //8 means 4 minutes before or after
		if($tsv_result === true){

			$client_id = (int) $_SESSION['la_client_client_id'];

			//insert user into ap_ask_client_users table
			$query = "INSERT INTO
						`".LA_TABLE_PREFIX."ask_client_users` (
									`client_id`,
									`email`,
									`full_name`,
									`phone`,
									`username`,
									`password`,
									`status`,
									`is_admin`,
									`tsv_secret`,
									`tsv_code`)
				  VALUES (?, ?, ?, ?, ?, ?, 0, 0, '{$_SESSION['la_tsv_setup_secret']}', '{$tsv_code}');";
			$params = array(
								$client_id,
								$_SESSION['email'],
								$_SESSION['full_name'],
								$_SESSION['phone'],
								$_SESSION['username'],
								$_SESSION['password_hash']);
			la_do_query($query,$params,$dbh);

			unset($_SESSION['company_name']);
			unset($_SESSION['email']);
			unset($_SESSION['phone']);
			unset($_SESSION['full_name']);
			unset($_SESSION['username']);
			unset($_SESSION['password_hash']);

			header("Location: reg_done.php");
			exit;
		} else {
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
<link rel="stylesheet" type="text/css" href="../../css/main.css" media="screen" />

<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="../../css/ie7.css" media="screen" />
<![endif]-->

<!--[if IE 8]>
	<link rel="stylesheet" type="text/css" href="../../css/ie8.css" media="screen" />
<![endif]-->

<!--[if IE 9]>
	<link rel="stylesheet" type="text/css" href="../../css/ie9.css" media="screen" />
<![endif]-->

<link href="../../css/theme.css" rel="stylesheet" type="text/css" />
<?php
	if(!empty($la_settings['admin_theme'])){
		echo '<link href="../../css/themes/theme_'.$la_settings['admin_theme'].'.css" rel="stylesheet" type="text/css" />';
	}
?>
<link href="../../css/bb_buttons.css" rel="stylesheet" type="text/css" />
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
<div id="main" class="two-step-div">
<div id="content">
  <div class="post login_main">
    <div style="padding-top: 10px">
      <div> <img src="../../images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
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
                <li class="tsv_setup_title">Step 1. Open Authenticator mobile app</li>
                <li> Open your authenticator mobile app. If you don't have it yet, you can install any of the following apps:
                  <div style="margin-top: 10px;padding-left: 20px"> &#8674; <a class="app_link" href="http://support.google.com/accounts/bin/answer.py?hl=en&answer=1066447" target="_blank">Google Authenticator</a> (Android/iPhone/BlackBerry)<br/>
                    &#8674; <a class="app_link" href="http://guide.duosecurity.com/third-party-accounts" target="_blank">Duo Mobile</a> (Android/iPhone)<br/>
                    &#8674; <a class="app_link" href="http://www.amazon.com/gp/product/B0061MU68M" target="_blank">Amazon AWS MFA</a> (Android)<br/>
                    &#8674; <a class="app_link" href="http://www.windowsphone.com/en-US/apps/021dd79f-0598-e011-986b-78e7d1fa76f8" target="_blank">Authenticator</a> (Windows Phone 7) </div>
                </li>
                <li class="tsv_setup_title">Step 2. Scan Barcode</li>
                <li> Use your authenticator app to scan the barcode below:
                  <div style="width: 80%;padding: 20px;text-align: center">
                    <div id="qrcode"></div>
                  </div>
                  or you can enter this secret key manually: <strong><?php echo $tsv_secret; ?></strong> </li>
                <li class="tsv_setup_title">Step 3. Verify Code</li>
                <li> Once your app is configured, enter the <strong>six-digit security code</strong> generated by your app. </li>
              </ul>
            </li>
            <li id="li_tsv_code">
              <label class="desc" for="tsv_code">Enter your 6-digit code</label>
              <div>
                <input id="tsv_code" style="width: 125px" name="tsv_code" class="element text medium" type="text" maxlength="255" value=""/>
              </div>
            </li>
            <li id="li_submit" class="buttons" style="overflow: auto;margin-top: 5px;margin-bottom: 10px">
              <input type="hidden" name="submit" id="submit" value="1">
              <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px">  Verify Code </button>
            </li>
          </ul>
        </form>
      </div>
    </div>
  </div>
</div>
    <img src="../../images/bottom.png" id="bottom_shadow">
    <div id="footer">
      <p class="copyright">Patent Pending, Copyright &copy; 2000-<script>document.write(new Date().getFullYear());</script> - <a href="http://lazarusalliance.com">Lazarus Alliance.</a> All rights reserved.</p>
      <div class="clear"></div>
    </div>
    <!-- /#footer -->
  </div>
</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="../../js/qrcode/qrcode.js"></script>
<script>
	$(function(){
		var qrcode = new QRCode(document.getElementById("qrcode"), { width : 140, height : 140 });
		qrcode.makeCode('{$totp_data}');
	});
</script>
EOT;
	echo $footer_data;
?>
<script type="text/javascript" src="../../js//jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../js//jquery.tools.min.js"></script>
</body>
</html>
