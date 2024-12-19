<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
session_start();
require('../../config.php');
require('../../includes/db-core.php');
require('../../includes/helper-functions.php');
require('../../includes/filter-functions.php');
require('../../includes/post-functions.php');
require('../../lib/swift-mailer/swift_required.php');
require('../../lib/password-hash.php');
require('../../includes/check-client-session-ask.php');
require('../../portal-header.php');

function processForm()
{
	if(!isset($_POST['email']))
	{
		header("Location: /auditprotocol/index.php");
		exit;
	}

	$client_id      = $_SESSION['la_client_client_id'];
	$company_name   = $_SESSION['la_client_company_name'];
	$email			= la_sanitize($_POST['email']);

	if($email == "")
	{
		$error = "Email cannot be blank";
		return $error;
	}elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		$error = "Please enter valid email address";
		return $error;
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//Check if username is available
	$query = "select count(email) total_user from `".LA_TABLE_PREFIX."ask_client_users` where email = ?";
	$params = array($email);
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row['total_user'])){
		$error = 'This email address already being used.';
		return $error;
	}

	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$ssl_suffix = 's';
	} else {
		$ssl_suffix = '';
	}

	$query = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users` (`client_id`, `email`, `is_invited`) VALUES (?, ?, ?);";
	$params = array($client_id, $email, 1);
	la_do_query($query,$params,$dbh);

	// select all fields
	$query_user = "select * from `".LA_TABLE_PREFIX."ask_client_users` where client_id = ? AND is_admin = '1'";
	$params_user = array($client_id);
	$sth_user = la_do_query($query_user,$params_user,$dbh);
	$row_user = la_do_fetch_result($sth_user);

	if(!empty($row_user['client_id'])){
		$email_param 				= array();
		$email_param['from_name'] 	= 'IT Audit Machine - '.$company_name;
		$email_param['from_email'] 	= $row_user['email'];
		$email_param['subject'] 	= 'Invitation to register';
		$email_param['as_plain_text'] = true;

		//get settings first
    	$la_settings = la_get_settings($dbh);

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
		$body = <<<EOD
<html>
	<head></head>
	<body>
		Please <a href="/portal/client_register.php?company_id={$base64_client_id}&user={$base64_email}">click here</a> to complete your registration
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
		->setTo($email_address)
		->setBody($body, $email_content_type);

		//send the message
		$s_mailer->send($s_message);
	}

	//redirect
	header("Location: reg_done.php");
	exit;
}
if(isset($_POST['submit']))
{
	$error = processForm();
}
?>
          <div class="content_body">
            <form action="" method="post" name="edit">
              <ul id="ms_main_list">
                <li>
                  <div id="ms_box_account" data-userid="1" class="ms_box_main gradient_blue">
                    <div class="ms_box_title">
                      <label class="choice">User Information</label>
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="email">Email: <span class="required">*</span> <img class="helpmsg" src="../../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the email of the new user."/></label>
                      <input required id="email" name="email" class="element text medium" value="<?php if(isset($_POST['email'])){echo $_POST['email'];} ?>" type="text">
                    </div>
                  </div>
                </li>
                <li style="padding:1em;">
                  <input type="submit" value="Invite a user" name="submit" id="submit">
                </li>
              </ul>
              <p>
                <?php
if(isset($error))
	echo "				<p id=\"error\">" . $error . "</p>\n";
?>
              </p>
            </form>
          </div>
<?php
require('../../includes/footer.php');
?>
