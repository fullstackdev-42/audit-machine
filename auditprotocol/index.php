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
	require('lib/password-hash.php');
	require('../itam-shared/includes/helper-functions.php');

	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();
	
	//immediately redirect to installer page if the config values are correct but no ap_forms table found
	$query = "select count(*) from ".LA_TABLE_PREFIX."settings";
	$sth = $dbh->prepare($query);
	try{
		$sth->execute(array());
	}catch(PDOException $e) {
		header("Location: installer.php");
		exit;
	}

	$la_settings = la_get_settings($dbh);

	$allow_login = false;

	function getHoursMinutes($seconds, $format = '%02d:%02d') {
		if (empty($seconds) || ! is_numeric($seconds)) {
			return false;
		}

		$minutes = round($seconds / 60);
		$hours = floor($minutes / 60);
		$remainMinutes = ($minutes % 60);

		return sprintf($format, $hours, $remainMinutes);
	}

	//check for ip address restriction, if enabled, compare the ip address
	if(!empty($la_settings['enable_ip_restriction'])){
		$allow_login = la_is_whitelisted_ip_address($dbh,$_SERVER['REMOTE_ADDR']);

		if($allow_login === false){
			$_SESSION['LA_LOGIN_ERROR'] = '<br/>- Forbidden -<br/><br/>Your IP address ('.$_SERVER['REMOTE_ADDR'].') <br/>is not allowed to access this page.<br/><br/>';
		}
	}else{
		$allow_login = true;
	}
	
	//process login submission
	if($allow_login){
		//check if the user has "remember me" cookie or not
		if(!empty($_COOKIE['la_remember']) && empty($_SESSION['la_logged_in'])){
			$query  = "SELECT
							`user_id`,
							`priv_administer`,
							`priv_new_forms`,
							`priv_new_themes`,
							`is_examiner`
						FROM
							`".LA_TABLE_PREFIX."users`
						WHERE
							`cookie_hash`=? and `status`=1";
			$params = array($_COOKIE['la_remember']);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			$user_id 			  = $row['user_id'];
			$priv_administer	  = (int) $row['priv_administer'];
			$priv_new_forms		  = (int) $row['priv_new_forms'];
			$priv_new_themes	  = (int) $row['priv_new_themes'];
			$is_examiner		  = (int) $row['is_examiner'];

			$last_ip_address 	  = $_SERVER['REMOTE_ADDR'];

			if(!empty($user_id)){
				$_SESSION['la_logged_in'] = true;
				$_SESSION['la_user_id']   = $user_id;
				$_SESSION['la_user_privileges']['priv_administer'] = $priv_administer;
				$_SESSION['la_user_privileges']['priv_new_forms']  = $priv_new_forms;
				$_SESSION['la_user_privileges']['priv_new_themes'] = $priv_new_themes;
				$_SESSION['is_examiner'] = $is_examiner;
				if($la_settings['enable_welcome_message_notification'] == 1 && $_SESSION['la_user_id'] != 1) {
					$_SESSION['admin_login_message_enabled'] = true;
				}
			}

			// add user activity to log: activity - 6 (LOGIN)
			addUserActivity($dbh, $_SESSION['la_user_id'], 0, 6, "", time(), $last_ip_address);

			//log user login time
			logUserSession($dbh, $_SESSION['la_user_id'], session_id());

			//add user to blockchain if not added
			addUserToChain($dbh, $_SESSION['la_user_id']);
		}

		//redirect to form manager if already logged-in
		if(!empty($_SESSION['la_logged_in']) && $_SESSION['la_logged_in'] == true){
			header("Location: manage_forms.php");
			exit;
		}

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
				$query125 = "SELECT `user_id` FROM `".LA_TABLE_PREFIX."users` WHERE `user_email` = ?";
				$sth125 = la_do_query($query125, array($nameID), $dbh);
				$row125 = la_do_fetch_result($sth125);
				
				//update last_login_date and last_ip_address
				$last_login_date = date("Y-m-d H:i:s");
				$last_ip_address = $_SERVER['REMOTE_ADDR'];
				
				if($row125){
					$user_id = $row125['user_id'];
					$query  = "SELECT
								`user_id`,
								`priv_administer`,
								`priv_new_forms`,
								`priv_new_themes`,
								`is_examiner`
							FROM
								`".LA_TABLE_PREFIX."users`
							WHERE
								`user_id` = ?";
					$params = array($user_id);
					$sth = la_do_query($query,$params,$dbh);
					$row = la_do_fetch_result($sth);

					//set the session variables for the user=========
					$_SESSION['la_logged_in'] = true;
					$_SESSION['la_user_id']   = $row['user_id'];
					$_SESSION['la_user_privileges']['priv_administer'] = (int) $row['priv_administer'];
					$_SESSION['la_user_privileges']['priv_new_forms']  = (int) $row['priv_new_forms'];
					$_SESSION['la_user_privileges']['priv_new_themes'] = (int) $row['priv_new_themes'];
					$_SESSION['is_examiner'] = (int) $row['is_examiner'];
					if($la_settings['enable_welcome_message_notification'] == 1 && $_SESSION['la_user_id'] != 1) {
						$_SESSION['admin_login_message_enabled'] = true;
					}
					//===============================================

					$query  = "UPDATE ".LA_TABLE_PREFIX."users set last_login_date=?, last_ip_address=? WHERE `user_id`=?";
					$params = array($last_login_date,$last_ip_address,$user_id);
					la_do_query($query,$params,$dbh);

					$_SESSION['la_user_logged_in_time'] = time();
					
					// add user activity to log: activity - 6 (LOGIN)
					if(!function_exists('addUserActivity')) {
						addUserActivity($dbh, $_SESSION['la_user_id'], 0, 6, "", time(), $last_ip_address);
					}
					header("Location: manage_forms.php");
					exit();
				}else{
					header("Location: ".$auth->getLogoutURL());
					exit();
				}
			} else {
				header("Location: ".$auth->getLogoutURL());
				exit();
			}
		}

		if(!empty($_POST['submit'])){

			$username = strtolower(la_sanitize($_POST['admin_username']));
			$password = la_sanitize($_POST['admin_password']);
			$remember_me = la_sanitize($_POST['admin_remember']);

			if(empty($username) || empty($password)){
				$_SESSION['LA_LOGIN_ERROR'] = 'Incorrect email or password!';
			}else{
				//get the password hash from the database
				$query  = "SELECT
							`user_password`,
							`user_id`,
							`priv_administer`,
							`priv_new_forms`,
							`priv_new_themes`,
							`tsv_enable`,
							`tsv_secret`,
							`login_attempt_date`,
							`login_attempt_count`,
							`user_email`,
							`how_user_registered`,
							`account_sharing_count`,
							`is_examiner`
						FROM
							`".LA_TABLE_PREFIX."users`
						WHERE
							`user_email`=? and `status`=1";
				$params = array($username);
				$sth = la_do_query($query,$params,$dbh);
				$row = la_do_fetch_result($sth);

              			$_SESSION['email'] 	  = $row['user_email'];

				$stored_password_hash = $row['user_password'];
				$user_id 			  = $row['user_id'];
				$priv_administer	  = (int) $row['priv_administer'];
				$priv_new_forms		  = (int) $row['priv_new_forms'];
				$priv_new_themes	  = (int) $row['priv_new_themes'];
				$is_examiner		  = (int) $row['is_examiner'];

				$tsv_enable	  		  = (int) $row['tsv_enable'];
				$tsv_secret 		  = $row['tsv_secret'];

				$login_attempt_date   = $row['login_attempt_date'];
				$login_attempt_count  = $row['login_attempt_count'];

				//check the password
				$hasher 	   = new Sha256Hash();
				$check_result  = $hasher->CheckPassword($password, $stored_password_hash);

				if($check_result){
					$login_is_valid = true;
					//add user to blockchain if not added
					addUserToChain($dbh, $user_id);
				}else{
					$login_is_valid = false;
					$_SESSION['LA_LOGIN_ERROR'] = 'Incorrect email or password!';

					//if account locking enabled, increase the login attempt counter  // (login_attempt_count + 1)
					if(!empty($la_settings['enable_account_locking']) && !empty($user_id)){
						$query = "UPDATE ".LA_TABLE_PREFIX."users
									  SET
									  	 login_attempt_date=?,
									  	 login_attempt_count=?
								    WHERE
								    	 user_id = ?";
						$new_login_attempt_date = date("Y-m-d H:i:s");
						$login_attempt_count += 1;
						$params = array($new_login_attempt_date,$login_attempt_count,$user_id);
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

				//if login is validated and password is correct
				if($login_is_valid){

					//check for Multi-Factor Authentication, is it enabled or not
					$show_tsv_page = false;

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
							$_SESSION['la_tsv_verify'] = $user_id;
							$_SESSION['la_tsv_verify_remember_me'] = $remember_me;

                          	if($row['how_user_registered']){
                          		//if "Enforce 50% Rule on Passwords" is enabled dont send code in email
                          		if ( empty($la_settings['disable_email_based_otp']) ) {
                                	/************this piece of code will send mail to the user with one time code **************/

									$user_email 				= $_SESSION['email'];

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
									$email_content_type = 'text/html';

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

									$_SESSION['LA_CODE_SEND']  = true;
									$_SESSION['ONE_TIME_CODE'] = $one_time_code;
									$_SESSION['EXPIRY_TIME']   = strtotime("+5 minutes");


										header("Location: login-using-code.php");
										exit;
								} else {
									header("Location: login_verify.php");
								}
                            }else
								header("Location: login_verify.php");
								exit;
						}
					}else{
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
						$_SESSION['is_examiner'] = $is_examiner;
						//===============================================

						//update last_login_date and last_ip_address
						$last_login_date = date("Y-m-d H:i:s");
						$last_ip_address = $_SERVER['REMOTE_ADDR'];

						$query  = "UPDATE ".LA_TABLE_PREFIX."users set last_login_date=?, last_ip_address=? WHERE `user_id`=?";
						$params = array($last_login_date,$last_ip_address,$user_id);
						la_do_query($query,$params,$dbh);


						// add user activity to log: activity - 6 (LOGIN)
						addUserActivity($dbh, $_SESSION['la_user_id'], 0, 6, "", time(), $last_ip_address);

						//log user login time
						logUserSession($dbh, $_SESSION['la_user_id'], session_id());


						//if the user select the "remember me option"
						//set the cookie and make it active for the next 30 days
						if(!empty($remember_me)){
							$cookie_hash = $hasher->HashPassword(mt_rand()); //generate random hash and save it into ap_users table

							$query = "update ".LA_TABLE_PREFIX."users set cookie_hash=? where `user_id`=?";
				   			$params = array($cookie_hash,$user_id);
				   			la_do_query($query,$params,$dbh);

				   			//send the cookie
				   			setcookie('la_remember',$cookie_hash, time()+3600*24*30, "/");
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
		}

		if(!empty($_GET['from'])){
			$_SESSION['prev_referer'] = base64_decode($_GET['from']);
		}
	} //end allow_login

	$auth = null;

    if(!empty($_POST['submit']) && isset($_POST['submit'])){
        $username = strtolower(trim($_POST['client_username']));
        $password = trim($_POST['client_password']);

        if(empty($username) || empty($password)){
                $_SESSION['LA_CLIENT_LOGIN_ERROR'] = 'Incorrect email or password!';
        }else{

            normalLogin(array('dbh' => $dbh, 'la_settings' => $la_settings, 'username' => $username, 'password' => $password));
        }
    }

    if(!empty($_GET['from'])){
        $_SESSION['prev_referer'] = base64_decode($_GET['from']);
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
<div id="container" style="margin-bottom: 0px!important;">
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
<div id="content" style="margin-bottom: 15px!important;">
  <div class="post login_main">
    <div style="padding-top: 10px">
      <div> <img src="images/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
        <h3>Sign in to Administration Portal</h3>
        <p>Sign in below to manage the Continuum GRC IT Audit Machine's digital assets.</p>
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
        <form id="form_login2" class="itauditm"  method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off">
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
            <?php if($allow_login){ ?>
            <li id="li_email_address">
              <label class="desc" for="admin_username">Username</label>
              <div>
                <input id="admin_username" name="admin_username" class="element text large" type="text" maxlength="255" value="<?php echo htmlspecialchars(la_sanitize($_POST['admin_username'])); ?>" autocomplete="off" readonly/>
              </div>
            </li>
            <li id="li_password">
              <label class="desc" for="admin_password">Password </label>
              <div>
                <input id="admin_password" name="admin_password" class="element text large" type="password" maxlength="255" value="<?php echo htmlspecialchars(la_sanitize($_POST['admin_password'])); ?>" autocomplete="off" readonly/>
              </div>
            </li>
            <li id="li_remember_me"> 
			  <label class="desc" for="admin_remember"></label>
			  <div>
				<div class="form-check">
					<input type="checkbox" value="1" class="element checkbox form-check-input" name="admin_remember" id="admin_remember">
					<label for="admin_remember" class="choice">Remember me</label>
				</div> 
			  </div>
			</li>
            <li id="li_submit" class="buttons" style="overflow: auto">
              <input type="hidden" name="submit" id="submit" value="1">
              <label class="desc"></label>
              <button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left;border-radius: 2px; margin-left: 17px;"> Sign In </button>
            </li>
            <?php } ?>
          </ul>
        </form>
      </div>
      <?php if($allow_login){ ?>
      <ul style="float: right;padding-top: 5px">
        <li> <span>
          <input type="checkbox" value="1" class="element checkbox" name="admin_forgot" id="admin_forgot" style="margin-left: 0px">
          <label for="admin_forgot" class="choice" style="color: #80b638;">I forgot my password</label>
          </span> </li>
      </ul>
      <?php } ?>
      <?php } ?>
    </div>
  </div>
</div>
<div id="dialog-login-page" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-login-page-msg"> Success </p>
</div>

<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/login_admin.js"></script>
EOT;
	require('includes/footerlogin.php');
?>
<script type="text/javascript">
$(document).ready(function() {
	$("input").click(function(e){
		$(this).attr("readonly", false);
	})
});
</script>
<script>
    localStorage.removeItem("auto-save-then-logout");
</script>