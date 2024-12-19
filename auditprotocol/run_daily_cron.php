<?php
/********************************************************************************
 IT Audit Machine
  
 Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	$pathSeparator = "../";
	
	require('includes/init-cron.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/report-helper-function.php');
	require('includes/helper-functions.php');
	require('includes/users-functions.php');
	require('lib/swift-mailer/swift_required.php');


	require_once("../policymachine/classes/CreateDocx.php");
	require_once("../policymachine/classes/CreateDocxFromTemplate.php");
 
  	require('includes/docxhelper-functions.php');
 	require('includes/post-functions.php');
 	require_once("../itam-shared/includes/helper-functions.php");
 	require_once('../itam-shared/includes/integration-helper-functions.php');
 		
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	function get_date($frequency_date_pick){
		$temp_date = $frequency_date_pick;
		if(strlen($temp_date) == 1){
			$temp_date = "0".$temp_date;
		}
		return $temp_date;
	}

	function sendMail($dbh, $la_settings, $subject, $body, $to_mail){
		$email_param 				= array();
		$email_param['from_name'] 	= 'IT Audit Machine';
		$email_param['from_email'] 	= $la_settings['default_from_email'];
		$email_param['subject'] 	= $subject;
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
		
		// mail body		
		$to_emails 		= str_replace(';',',',$to_mail);
    	$to_emails 		= str_replace(' ','',$to_emails);
    	$to_emails 		= str_replace('&nbsp;','',$to_emails);
		$to_emails		= strpos($to_emails, ",") !== false ? explode(",", $to_emails) : array($to_emails);	

		$s_message = Swift_Message::newInstance()
		  ->setCharset('utf-8')
		  ->setMaxLineLength(1000)
		  ->setSubject($subject)
		  ->setFrom(array($from_email => $from_name))
		  ->setSender($from_email)
		  ->setReturnPath($from_email)
		  ->setTo($to_emails)
		  ->setBody($body, $email_content_type);
		
		// send mail
		$a = $s_mailer->send($s_message);		
	}

	function account_suspension($dbh, $la_settings){
		$cur_date = strtotime(date('Y-m-d'));
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		//suspend or delete user accounts

		$query = "SELECT `".LA_TABLE_PREFIX."ask_client_users`.*, IFNULL(MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`), 0) `last_login`, IFNULL(DATEDIFF(CURDATE(), FROM_UNIXTIME(MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`), '%Y-%m-%d')), DATEDIFF(CURDATE(), FROM_UNIXTIME(`register_datetime`))) `no_of_days_last_login` FROM `".LA_TABLE_PREFIX."ask_client_users` LEFT JOIN `".LA_TABLE_PREFIX."portal_user_login_log` ON (`".LA_TABLE_PREFIX."ask_client_users`.`client_user_id` = `".LA_TABLE_PREFIX."portal_user_login_log`.`client_user_id`) GROUP BY `".LA_TABLE_PREFIX."ask_client_users`.`client_user_id`";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			$suspend_notification_sent = false;
			$delete_notification_sent = false;
			if($row["is_invited"] == 1) {
				$no_of_days_last_login = floor(($cur_date - $row["tstamp"])/86400);
			} else {
				if($row["last_login"] == 0 && $row["no_of_days_last_login"] == 0) {
					//registered but not logged in yet
					$no_of_days_last_login = floor(($cur_date - $row["register_datetime"])/86400);
				} else {
					$no_of_days_last_login = $row["no_of_days_last_login"];
				}
			}

			//suspend user accounts after strict date
			if($row['account_suspension_strict_date_flag'] == 1 && $row["status"] == 0) {
				if(($row['account_suspension_strict_date'] - 259200 <= $cur_date) && $row['account_suspension_strict_date'] >= $cur_date) {
					//send account suspension reminder notification email to users for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account will be suspended in ".(ceil(($row['account_suspension_strict_date'] - $cur_date)/86400) + 1)." day(s) in <strong>{$site_name}</strong>.<br>To avoid an account suspension, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$suspend_notification_sent = true;
				}
				if($row['account_suspension_strict_date'] < $cur_date) {
					//suspend and then send suspension notification email to users					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account has been suspended in <strong>{$site_name}</strong>.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$suspend_notification_sent = true;
				}
			}

			//suspend user accounts for inactivity
			if($row['account_suspension_inactive_flag'] == 1 && $row['account_suspension_inactive'] > 0 && $row["status"] == 0){
				if(($row['account_suspension_inactive'] - 2 <= $no_of_days_last_login) && ($row['account_suspension_inactive'] >= $no_of_days_last_login)) {
					//send account suspension reminder notification email to users for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account will be suspended in ".($row['account_suspension_inactive'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To avoid an account suspension, please log into the user portal of {$site_name}.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$suspend_notification_sent = true;
				}
				if($row['account_suspension_inactive'] < $no_of_days_last_login) {
					//suspend and then send suspension notification email to users					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account has been suspended in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$suspend_notification_sent = true;
				}
			}

			//delete user accounts for inactivity
			if($row['suspended_account_auto_deletion_flag'] == 1 && $row['account_suspended_deletion'] > 0) {
				if(($row['account_suspended_deletion'] - 2 <= $no_of_days_last_login) && ($row['account_suspended_deletion'] >= $no_of_days_last_login)) {
					//send account deletion reminder notification email to users for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Deletion Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Reminder Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account will be deleted in ".($row['account_suspended_deletion'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspended_deletion']} days.<br>To avoid an account deletion, please log into the user portal of {$site_name}.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$delete_notification_sent = true;
				}
				if($row['account_suspended_deletion'] < $no_of_days_last_login) {
					//delete and then send deletion notification email to users					
					$query_del = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = '{$row['client_user_id']}'";
					la_do_query($query_del,array(),$dbh);

					$email_subject = "Continuum GRC Account Deletion Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account has been deleted in <strong>{$site_name}</strong> for inactivity after {$row['account_suspended_deletion']} days.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
					$delete_notification_sent = true;
				}
			}

			//suspend user accounts for inactivity based on the global account suspension settings
			//if suspension/deletion [reminder] notifications were already sent, don't send again here. Individual settings are high priority
			if($la_settings["enable_account_suspension_inactive"] == 1 && $la_settings["account_suspension_inactive"] > 0 && $suspend_notification_sent == false && $delete_notification_sent == false && $row["status"] == 0){
				if(($la_settings["account_suspension_inactive"] - 2 <= $no_of_days_last_login) && ($la_settings["account_suspension_inactive"] >= $no_of_days_last_login)) {
					//send account suspension reminder notification email to users for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account will be suspended in ".($la_settings["account_suspension_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To avoid an account suspension, please log into the user portal of {$site_name}.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
				}				
				if($la_settings["account_suspension_inactive"] < $no_of_days_last_login) {
					//suspend and then send suspension notification email to users					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account has been suspended in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
				}
			}

			//delete user accounts for inactivity based on the global account suspension settings
			//if deletion [reminder] notifications were already sent, don't send again here. Individual settings are high priority
			if($la_settings["enable_account_deletion_inactive"] == 1 && $la_settings["account_deletion_inactive"] > 0 && $delete_notification_sent == false) {
				if(($la_settings["account_deletion_inactive"] - 2 <= $no_of_days_last_login) && ($la_settings["account_deletion_inactive"] >= $no_of_days_last_login)) {
					//send account deletion reminder notification email to users for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Deletion Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Reminder Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account will be deleted in ".($la_settings["account_deletion_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To avoid an account deletion, please log into the user portal of {$site_name}.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
				}
				if($la_settings["account_deletion_inactive"] < $no_of_days_last_login) {
					//delete and then send deletion notification email to users					
					$query_del = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = '{$row['client_user_id']}'";
					la_do_query($query_del,array(),$dbh);

					$email_subject = "Continuum GRC Account Deletion Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Notification</h3><br>";
					$email_content .= "Hello {$row['full_name']},<br><br>Your user account has been deleted in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.";
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["email"]);
				}
			}
		}

		//suspend or delete admin accounts except the number one account
		$query = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE user_id != 1";
		$sth = la_do_query($query, array(), $dbh);
		while ($row = la_do_fetch_result($sth)) {
			$no_of_days_last_login = 0;
			if($row["status"] == 3) {
				//invited but not registered yet
				$no_of_days_last_login = floor(($cur_date - $row["tstamp"])/86400);
			} else {
				if(is_null($row["last_login_date"])) {
					if($row["register_datetime"] == 0) {
						//invited but not registered yet and suspended
						$no_of_days_last_login = floor(($cur_date - $row["tstamp"])/86400);
					} else {
						//registered but not logged in yet
						$no_of_days_last_login = floor(($cur_date - $row["register_datetime"])/86400);
					}
				} else {
					$no_of_days_last_login = floor(($cur_date - strtotime(date($row["last_login_date"])))/86400);
				}
			}

			$suspend_notification_sent = false;
			$delete_notification_sent = false;

			//suspend admin accounts after strict date
			if($row['account_suspension_strict_date_flag'] == 1 && $row["status"] == 1) {
				if(($row['account_suspension_strict_date'] - 259200 <= $cur_date) && $row['account_suspension_strict_date'] >= $cur_date) {
					//send account suspension reminder notification email to admins for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account will be suspended in ".(ceil(($row['account_suspension_strict_date'] - $cur_date)/86400) + 1)." day(s) in <strong>{$site_name}</strong>.<br>To avoid an account suspension, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account will be suspended in ".(ceil(($row['account_suspension_strict_date'] - $cur_date)/86400) + 1)." day(s) in <strong>{$site_name}</strong>.<br>To avoid an account suspension, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$suspend_notification_sent = true;
				}
				if($row['account_suspension_strict_date'] < $cur_date) {
					//suspend and then send suspension notification email to admins					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."users` SET `status` = '2' WHERE `user_id` = '{$row['user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account has been suspended in <strong>{$site_name}</strong>.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account has been suspended in <strong>{$site_name}</strong>.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$suspend_notification_sent = true;
				}
			}

			//suspend admin accounts for inactivity
			if($row['account_suspension_inactive_flag'] == 1 && $row['account_suspension_inactive'] > 0 && $row["status"] == 1){
				if(($row['account_suspension_inactive'] - 2 <= $no_of_days_last_login) && ($row['account_suspension_inactive'] >= $no_of_days_last_login)) {
					//send account suspension reminder notification email to admins for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account will be suspended in ".($row['account_suspension_inactive'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To avoid an account suspension, please log into the admin portal of {$site_name}.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account will be suspended in ".($row['account_suspension_inactive'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To avoid an account suspension, please log into the admin portal of {$site_name}.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$suspend_notification_sent = true;
				}
				if($row['account_suspension_inactive'] < $no_of_days_last_login) {
					//suspend and then send suspension notification email to admins					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."users` SET `status` = '2' WHERE `user_id` = '{$row['user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account has been suspended in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account has been suspended in <strong>{$site_name}</strong> for inactivity after {$row['account_suspension_inactive']} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$suspend_notification_sent = true;
				}
			}

			//delete admin accounts for inactivity
			if($row['suspended_account_auto_deletion_flag'] == 1 && $row['account_suspended_deletion'] > 0 && $row["status"] != 0) {
				if(($row['account_suspended_deletion'] - 2 <= $no_of_days_last_login) && ($row['account_suspended_deletion'] >= $no_of_days_last_login)) {
					//send account deletion reminder notification email to admins for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Deletion Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Reminder Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account will be deleted in ".($row['account_suspended_deletion'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspended_deletion']} days.<br>To avoid an account deletion, please log into the admin portal of {$site_name}.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account will be deleted in ".($row['account_suspended_deletion'] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$row['account_suspended_deletion']} days.<br>To avoid an account deletion, please log into the admin portal of {$site_name}.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$delete_notification_sent = true;
				}
				if($row['account_suspended_deletion'] < $no_of_days_last_login) {
					//delete and then send deletion notification email to admins					
					$query_upd = "DELETE FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = '{$row['user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Deletion Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account has been deleted in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account has been deleted in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
					$delete_notification_sent = true;
				}
			}

			//suspend admin accounts for inactivity based on the global account suspension settings
			//if suspension/deletion [reminder] notifications were already sent, don't send again here. Individual settings are high priority
			if($la_settings["enable_account_suspension_inactive"] == 1 && $la_settings["account_suspension_inactive"] > 0 && $suspend_notification_sent == false && $delete_notification_sent == false && $row["status"] == 1){
				if(($la_settings["account_suspension_inactive"] - 2 <= $no_of_days_last_login) && ($la_settings["account_suspension_inactive"] >= $no_of_days_last_login)) {
					//send account suspension reminder notification email to admins for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Suspension Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Reminder Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account will be suspended in ".($la_settings["account_suspension_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To avoid an account suspension, please log into the admin portal of {$site_name}.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account will be suspended in ".($la_settings["account_suspension_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To avoid an account suspension, please log into the admin portal of {$site_name}.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
				}
				if($la_settings["account_suspension_inactive"] < $no_of_days_last_login) {
					//suspend and then send suspension notification email to admins					
					$query_upd = "UPDATE `".LA_TABLE_PREFIX."users` SET `status` = '2' WHERE `user_id` = '{$row['user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Suspension Notification";
					$email_content = "<h3>Continuum GRC Account Suspension Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account has been suspended in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account has been suspended in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_suspension_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
				}
			}

			//delete admin accounts for inactivity based on the global account suspension settings
			//if deletion [reminder] notifications were already sent, don't send again here. Individual settings are high priority
			if($la_settings["enable_account_deletion_inactive"] == 1 && $la_settings["account_deletion_inactive"] > 0 && $delete_notification_sent == false && $row["status"] != 0) {
				if(($la_settings["account_deletion_inactive"] - 2 <= $no_of_days_last_login) && ($la_settings["account_deletion_inactive"] >= $no_of_days_last_login)) {
					//send account deletion reminder notification email to admins for 3 days in a row before suspension
					$email_subject = "Continuum GRC Account Deletion Reminder Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Reminder Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account will be deleted in ".($la_settings["account_deletion_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To avoid an account deletion, please log into the admin portal of {$site_name}.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account will be deleted in ".($la_settings["account_deletion_inactive"] - $no_of_days_last_login + 1)." day(s) in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To avoid an account deletion, please log into the admin portal of {$site_name}.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
				}
				if($la_settings["account_deletion_inactive"] < $no_of_days_last_login) {
					//delete and then send deletion notification email to admins					
					$query_upd = "DELETE FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = '{$row['user_id']}'";
					la_do_query($query_upd,array(),$dbh);

					$email_subject = "Continuum GRC Account Deletion Notification";
					$email_content = "<h3>Continuum GRC Account Deletion Notification</h3><br>";
					if(empty($row['is_examiner'])) {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your admin account has been deleted in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					} else {
						$email_content .= "Hello {$row['user_fullname']},<br><br>Your examiner account has been deleted in <strong>{$site_name}</strong> for inactivity after {$la_settings["account_deletion_inactive"]} days.<br>To reactivate it, please contact an ITAM admin support or call 1-888-896-6207 for immediate assistance.";
					}
					sendMail($dbh, $la_settings, $email_subject, $email_content, $row["user_email"]);
				}
			}
		}
	}	

	function reminder_notification($dbh, $la_settings){
		$cur_datetime = strtotime(date('Y-m-d'));
		$cur_date  = date('d');
		$cur_day = date('N');
		$cur_month = date('n');

		$query = "SELECT * FROM ".LA_TABLE_PREFIX."mechanism_for_notification WHERE `mechanism_for_notification_flag` = ?";
		$sth = la_do_query($query, array(1), $dbh);

		while($row = la_do_fetch_result($sth)){
			//Start sending administrative notices
			$sendNotification = false;

			$frequency_date_pick = get_date($row['frequency_date_pick']);
			
			if($row['frequency_type'] == 1 && $row['frequency_date'] == $cur_datetime){
				$sendNotification = true;
			}

			if($row['frequency_type'] == 2){
				$sendNotification = true;
			}

			if($row['frequency_type'] == 3 && $row['frequency_weekly'] == $cur_day){
				$sendNotification = true;
			}

			if($row['frequency_type'] == 4 && $frequency_date_pick == $cur_date){
				$sendNotification = true;
			}
			
			if($row['frequency_type'] == 5 && $frequency_date_pick == $cur_date && $cur_month % 3 == $row['frequency_quaterly']){
				$sendNotification = true;
			}
			
			if($row['frequency_type'] == 6 && $frequency_date_pick == $cur_date && $cur_month == $row['frequency_annually']){
				$sendNotification = true;
			}

			if($sendNotification){
				$subject = $row['subject'];
				$body = nl2br($row['body']);
				$body .= '<br><br>';
				$body .= '<a target="_blank" href="'.str_replace('auditprotocol/', 'portal/view.php?id=', $la_settings['base_url']).$row['form_id'].'">Click here to open form</a>';

				$string_entity_ids = $row['recipients'];
				if(!empty($string_entity_ids)){
					$query1 = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE status = 0 AND `client_id` IN ($string_entity_ids)";
					$sth1 = la_do_query($query1,array(),$dbh);
					while ($row1 = la_do_fetch_result($sth1)) {
						if(!empty($row1['email'])){
							sendMail($dbh, $la_settings, $subject, $body, $row1['email']);
						}						
					}
				}					
				if(!empty($row['additional_recipients'])){
					sendMail($dbh, $la_settings, $subject, $body, $row['additional_recipients']);
				}				

				$queryupd = "UPDATE ".LA_TABLE_PREFIX."mechanism_for_notification SET notification_sent_date = :notification_sent_date WHERE form_id = :form_id";
				la_do_query($queryupd, array(':form_id' => $row['form_id'], ':notification_sent_date' => $cur_datetime), $dbh);
			}			
			//End sending administrative notices
			//Start sending reminder notices
			if(($row['following_up_days'] != 0) && (($cur_datetime - $row['notification_sent_date']) == $row['following_up_days'] * 86400)) {
				$reminder_subject = $row['reminder_subject'];
				$reminder_body = nl2br($row['reminder_body']);
				$reminder_body .= '<br><br>';
				$reminder_body .= '<a target="_blank" href="'.str_replace('auditprotocol/', 'portal/view.php?id=', $la_settings['base_url']).$row['form_id'].'">Click here to open form</a>';
				$string_entity_ids = $row['recipients'];
				if(!empty($string_entity_ids)){
					$query1 = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE status = 0 AND `client_id` IN ($string_entity_ids)";
					$sth1 = la_do_query($query1,array(),$dbh);
					while ($row1 = la_do_fetch_result($sth1)) {
						if(!empty($row1['email'])){
							sendMail($dbh, $la_settings, $reminder_subject, $reminder_body, $row1['email']);
						}
					}
				}
				if(!empty($row['additional_recipients'])){
					sendMail($dbh, $la_settings, $reminder_subject, $reminder_body, $row['additional_recipients']);
				}
			}
			//End sending reminder notices
		}
	}

	function delete_old_form_data($dbh, $la_settings){
		//get a list of all form IDs
		$form_ids = array();
		$form_names = array();
		$query = "SELECT `form_id` FROM ".LA_TABLE_PREFIX."forms";
		$sth = la_do_query($query, array(), $dbh);
		while ($row = la_do_fetch_result($sth)) {
			array_push($form_ids, $row["form_id"]);
			array_push($form_names, "'".LA_TABLE_PREFIX."form_".$row["form_id"]."'");
		}

		//get a list of deleted forms before 30 days and remove them from all forms list
		$no_of_days = 30;
		$search_query = "SELECT `form_id` FROM `".LA_TABLE_PREFIX."deleted_form` WHERE CURDATE() > DATE_ADD(FROM_UNIXTIME(`delete_datetime`, '%Y-%m-%d'), INTERVAL {$no_of_days} DAY) ORDER BY `delete_datetime` ASC";
		$sth = la_do_query($search_query, array(), $dbh);
		while ($row = la_do_fetch_result($sth)) {
			if (($key = array_search($row["form_id"], $form_ids)) !== false) {
			    unset($form_ids[$key]);
			}
			if (($key = array_search("'".LA_TABLE_PREFIX."form_".$row["form_id"]."'", $form_names)) !== false) {
			    unset($form_names[$key]);
			}
		}

		$str_form_ids = implode(',', $form_ids);
		$str_form_names = implode(',', $form_names);
		//array of table names that have records need to be deleted - listed alphabetically
		$table_names = array(
			"approver_logic",
			"approver_logic_conditions",
			"ask_client_forms",
			"background_document_proccesses",
			"column_preferences",
			"deleted_form",
			"element_options",
			"element_prices",
			"element_status_indicator",
			"email_logic",
			"email_logic_conditions",
			"email_logic_conditions_final_approval_status",
			"entity_form_relation",
			"entries_preferences",
			"eth_file_data",
			"field_logic_conditions",
			"field_logic_elements",
			"form_approval_logic_data",
			"form_approval_logic_entry_data",
			"form_approvals",
			"form_editing_locked",
			"form_element_note",
			"form_elements",
			"form_filters",
			"form_integration_fields",
			"form_locks",
			"form_multiple_report",
			"form_payment_check",
			"form_payments",
			"form_report",
			"form_report_elements",
			"form_score",
			"form_submission_details",
			"form_template",
			"forms",
			"forms_submission_counter",
			"grid_columns",
			"mechanism_for_notification",
			"page_logic",
			"page_logic_conditions",
			"permissions",
			"report_elements",
			"report_filters",
			"reports",
			"saint_settings",
			"template_document_creation",
			"webhook_logic_conditions",
			"webhook_options",
			"webhook_parameters"
		);

		foreach ($table_names as $table_name) {
			$query = "DELETE FROM ".LA_TABLE_PREFIX."{$table_name} WHERE form_id NOT IN ({$str_form_ids})";
			la_do_query($query, array(), $dbh);
		}

		//remove records from other tables
		$query = "DELETE FROM ".LA_TABLE_PREFIX."blocked_form_fields WHERE form_id_where_lock_originated NOT IN ({$str_form_ids})";
		la_do_query($query, array(), $dbh);

		$query = "DELETE FROM ".LA_TABLE_PREFIX."fix_bad_data WHERE form_table_name NOT IN ({$str_form_names})";
		la_do_query($query, array(), $dbh);

		// select all forms like ap_form_%
        $queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_%'";
        $resultFormTable = la_do_query($queryFormTable, array(), $dbh);
		$all_table_names = array();
		while ($row = la_do_fetch_result($resultFormTable)) {
			foreach ($row as $key => $value) {
				array_push($all_table_names, $value);
			}
		}
		foreach ($all_table_names as $table_name) {
			$form_id = (int)explode("_", $table_name)[2];
			if($form_id != 0 && !in_array($form_id, $form_ids)) {
				$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}`";
				la_do_query($query, array(), $dbh);

				$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}_review`";
				la_do_query($query, array(), $dbh);

				$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}_saved_entries`";
				la_do_query($query, array(), $dbh);
			}
		}

		//delete folders and files in auditprotocol/upload_dir and auditprotocol/data_dir
		$all_dirs_in_upload_folder = glob($la_settings['upload_dir'].'/*' , GLOB_ONLYDIR);
		foreach ($all_dirs_in_upload_folder as $folder) {
			$form_id = (int) substr(explode("/", $folder)[2], 5);
			if($form_id != 0 && !in_array($form_id, $form_ids)) {
				@la_full_rmdir($folder);
			}
		}
		if($la_settings['upload_dir'] != $la_settings['data_dir']){
			$all_dirs_in_data_folder = glob($la_settings['data_dir'].'/*' , GLOB_ONLYDIR);
			foreach ($all_dirs_in_data_folder as $folder) {
				$form_id = (int) substr(explode("/", $folder)[2], 5);
				if($form_id != 0 && !in_array($form_id, $form_ids)) {
					@la_full_rmdir($folder);
				}
			}
		}

		//delete folders and files in auditprotocol/templates
		$all_dirs_in_templates_folder = glob('./templates/*' , GLOB_ONLYDIR);
		foreach ($all_dirs_in_templates_folder as $folder) {
			$target_form_ids = glob($folder.'/*' , GLOB_ONLYDIR);
			foreach ($target_form_ids as $full_path) {
				$form_id = explode("/", $full_path)[3];
				if($form_id != 0 && !in_array($form_id, $form_ids)){
					@la_full_rmdir($folder."/".$form_id);
				}
			}
		}

		//delete generated documents in portal/template_output
		//delete old generated document records from ap_template_document_creation
		$query = "DELETE  a
					FROM    ".LA_TABLE_PREFIX."template_document_creation a
					        LEFT JOIN
					        (
					            SELECT MAX(docx_id) ID, client_id, company_id, form_id, isZip, docx_create_date, LEFT(docxname, CHAR_LENGTH(docxname) - LOCATE('_', REVERSE(docxname))) AS docxname
					            FROM    ".LA_TABLE_PREFIX."template_document_creation
					            GROUP   BY company_id, form_id, isZip, LEFT(docxname, CHAR_LENGTH(docxname) - LOCATE('_', REVERSE(docxname)))
					        ) b ON  a.docx_id = b.ID AND
					                a.company_id = b.company_id AND
					                a.form_id = b.form_id AND
					                a.isZip = b.isZip
					WHERE   b.ID IS NULL";
		la_do_query($query, array(), $dbh);
		//get all available generated document file names
		$files = array();
		$query = "SELECT docxname FROM ".LA_TABLE_PREFIX."template_document_creation";
		$sth = la_do_query($query, array(), $dbh);
		while ($row = la_do_fetch_result($sth)) {
			array_push($files, $row["docxname"]);
		}
		//get filenames of existing generated documents
		$all_documents = array_filter(glob('../portal/template_output/*'), 'is_file');
		foreach ($all_documents as $file) {
			$filename = explode("/", $file)[3];
			if(!in_array($filename, $files)) {
				unlink($file);
			}
		}
		//delete entry backup files that were not saved in database
		foreach ($form_ids as $form_id) {
			foreach(glob("{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/entries_backup_*.*") as $file) {
				$queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$form_id}_saved_entries'";
				$resultFormTable = la_do_query($queryFormTable, array(), $dbh);
				$rowFormTable    = la_do_fetch_result($resultFormTable);
				if($rowFormTable) {
					$query_server_entry = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id}_saved_entries WHERE pathtofile = ?";
					$sth_server_entry = la_do_query($query_server_entry, array($file), $dbh);
					$row_server_entry = la_do_fetch_result($sth_server_entry);
					if(!$row_server_entry) {
						unlink($file);
					}
				} else {
					unlink($file);
				}
			}
			@la_full_rmdir("{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/tmp_uploaded_entry");
		}
	}

	function deleteUnusedForms($dbh, $la_settings){
        $withoutTableStack = array();

        // select all forms whose status is 1
        $query = "select `form_id`, `form_name` from `".LA_TABLE_PREFIX."forms` where `form_active` = :form_active";
        $sth = la_do_query($query,array(':form_active' => 1),$dbh);
        while($row = la_do_fetch_result($sth)){
            // select all forms whose status is 1
            $queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$row['form_id']}'";
            $resultFormTable = la_do_query($queryFormTable,array(),$dbh);
            $rowFormTable    = la_do_fetch_result($resultFormTable);

            if(!$rowFormTable){
                $withoutTableStack[$row['form_id']] = $row['form_name']." - ".$row['form_id'];
            }
        }
      
        $body = "<h4>List of FormId# without dbtable</h4>";
        $body   .= "<hr />";
        $body   .= "<table><tr><td>".implode("</td><td>", $withoutTableStack)."</td></tr></table>";

        if(count($withoutTableStack) == 0){
          $body = "<h4>No form found</h4>";
        }else{
          foreach($withoutTableStack as $key => $value){
              $qry_delete = "delete from `".LA_TABLE_PREFIX."forms` where form_id = :form_id";
              la_do_query($qry_delete,array(':form_id' => $key),$dbh);
          }
        }
    }	
	
	function sendPasswordChangeReminder($dbh, $la_settings){
		$query = "select `enable_password_expiration`, `enable_password_days` from ".LA_TABLE_PREFIX."settings";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['enable_password_expiration']){
				
				$query_user = "select `client_user_id`, `email`, `full_name`, datediff(NOW(), from_unixtime(password_change_date)) `no_of_days_of_last_password_change` from `".LA_TABLE_PREFIX."ask_client_users` where `status` = 0 and `is_invited` = 0";
				$sth_user = la_do_query($query_user,array(),$dbh);

				while($row_user = la_do_fetch_result($sth_user)){
					if($row['enable_password_days'] <= $row_user['no_of_days_of_last_password_change']){
						/************this piece of code will send mail to the user with one time code **************/

						$user_email = array($row_user['email']);						
						$token = sha1(uniqid($row_user['email'], true));
						$tstamp = $_SERVER["REQUEST_TIME"];

						$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `token` = ?, tstamp = ? WHERE `client_user_id` = ?";
						la_do_query($query, array($token, $tstamp, $row_user["client_user_id"]), $dbh);

						$email_param 				= array();
						$email_param['from_name'] 	= 'IT Audit Machine (ITAM)';
						$email_param['from_email'] 	= $la_settings['default_from_email'];
						$email_param['subject'] 	= "Continuum GRC Password Expiration Notification";
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
						
						$site_name = "https://".$_SERVER['SERVER_NAME'];
						$one_time_url = $site_name.'/portal/one_time_password.php?utoken='.$token;
						$email_content = "";
						$email_content .= "Hello, ".$row_user['full_name'].",<br>";
						$email_content .= "Your password will expire soon in ".$site_name.".<br>";
						$email_content .= "Click the link to reset your password. <a href=\"{$one_time_url}\">Reset Password</a><br>";
						if($la_settings['one_time_url_expiration_date'] == "1") {
							$email_content .= "<br>Please note that this link will expire after {$la_settings['one_time_url_expiration_date']} day.";
						} else {
							$email_content .= "<br>Please note that this link will expire after {$la_settings['one_time_url_expiration_date']} days.";
						}

						$s_message = Swift_Message::newInstance()
						->setCharset('utf-8')
						->setMaxLineLength(1000)
						->setSubject($subject)
						->setFrom(array($from_email => $from_name))
						->setSender($from_email)
						->setReturnPath($from_email)
						->setTo($user_email)
						->setBody($email_content, $email_content_type);
						
						//send the message
						$a = $s_mailer->send($s_message);
					}
				}
			}
		}
	}

	function field_note_reminder($dbh, $la_settings) {
		$query_note = $query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note`";
		$sth_note = la_do_query($query_note, array(), $dbh);
		while($row_note = la_do_fetch_result($sth_note)) {
			if(($row_note["status"] < 3) && ((time() - strtotime($row_note["reminder_sent_date"]) > 604800))) {
				sendNoteNotification($dbh, $la_settings, $row_note["form_element_note_id"]);
			}
		}
	}

	function import_saint_reports($dbh) {
		//get all SAINT settings
		$saint_settings = get_all_saint_settings($dbh);
		foreach ($saint_settings as $setting) {
			//check if the form is available
			$query_form = "SELECT * FROM ".LA_TABLE_PREFIX."forms WHERE form_id=?";
			$sth_form = la_do_query($query_form, array($setting["form_id"]), $dbh);
			$row_form = la_do_fetch_result($sth_form);
			if(!empty($row_form["form_id"])) {
				if(((time()- $setting["tstamp"]) > $setting["frequency"] * 86400) && $setting["frequency"] != "0") {
					import_saint_report($dbh, $setting["id"]);
				}
			}
		}
	}

	function import_nessus_reports($dbh) {
		//get all Nessus settings
		$nessus_settings = get_all_nessus_settings($dbh);
		foreach ($nessus_settings as $setting) {
			//check if the form is available
			$query_form = "SELECT * FROM ".LA_TABLE_PREFIX."forms WHERE form_id=?";
			$sth_form = la_do_query($query_form, array($setting["form_id"]), $dbh);
			$row_form = la_do_fetch_result($sth_form);
			if(!empty($row_form["form_id"])) {
				if(((time()- $setting["tstamp"]) > $setting["frequency"] * 86400) && $setting["frequency"] != "0") {
					import_nessus_report($dbh, $setting["id"]);
				}
			}
		}
	}

	function send_field_note_report($dbh, $la_settings) {
		$cur_datetime = strtotime(date('Y-m-d'));
		$cur_date  = date('d');
		$cur_day = date('N');
		$cur_month = date('n');

		$query_report = "SELECT * FROM `".LA_TABLE_PREFIX."form_report` WHERE `math_function` = ?";
		$sth_report = la_do_query($query_report, array("field-note"), $dbh);
		while ($row = la_do_fetch_result($sth_report)) {
			$sendReport = false;

			$frequency_date_pick = get_date($row['frequency_date_pick']);
			
			if($row['frequency_type'] == 1 && $row['frequency_date'] == $cur_datetime){
				$sendReport = true;
			}

			if($row['frequency_type'] == 2){
				$sendReport = true;
			}

			if($row['frequency_type'] == 3 && $row['frequency_weekly'] == $cur_day){
				$sendReport = true;
			}

			if($row['frequency_type'] == 4 && $frequency_date_pick == $cur_date){
				$sendReport = true;
			}
			
			if($row['frequency_type'] == 5 && $frequency_date_pick == $cur_date && $cur_month % 3 == $row['frequency_quaterly']){
				$sendReport = true;
			}
			
			if($row['frequency_type'] == 6 && $frequency_date_pick == $cur_date && $cur_month == $row['frequency_annually']){
				$sendReport = true;
			}

			if($sendReport) {
				sendFieldNoteReport($dbh, $la_settings, $row["report_id"]);
				$queryupd = "UPDATE ".LA_TABLE_PREFIX."form_report SET report_sent_date = ? WHERE report_id = ?";
				la_do_query($queryupd, array($cur_datetime, $row["report_id"]), $dbh);
			}

			if($row["frequency_type"] == 1 || $row["frequency_type"] == 2 || $row["frequency_type"] == 3 || $row["frequency_type"] == 4 || $row["frequency_type"] == 5 || $row["frequency_type"] == 6) {
				if(($row['following_up_days'] != 0) && (($cur_datetime - $row['report_sent_date']) == $row['following_up_days'] * 86400)) {
					sendFieldNoteReport($dbh, $la_settings, $row["report_id"]);
				}
			}
		}
	}
	account_suspension($dbh, $la_settings);
	deleteUnusedForms($dbh, $la_settings);
	delete_old_form_data($dbh, $la_settings);
	sendPasswordChangeReminder($dbh, $la_settings);
	reminder_notification($dbh, $la_settings);
	field_note_reminder($dbh, $la_settings);
	import_saint_reports($dbh);
	import_nessus_reports($dbh);
	send_field_note_report($dbh, $la_settings);
?>