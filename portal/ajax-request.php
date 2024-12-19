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
	$email_address = $email;
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

	echo "LA_CODE_SEND";
	exit();
