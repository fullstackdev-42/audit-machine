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
	
	$action = trim($_POST['action']);
	

	if(empty($action)){
		die("This file can't be opened directly.");
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	if($action == "reset_admin_mfa") {
		if($_POST["origin"] == "view_user") {
			$error = "";
			$login_user = $_SESSION['email'];
			$site_name = "https://".$_SERVER['SERVER_NAME'];
			$user_id = $_POST["user_id"];

			$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `tsv_enable` = ?, `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `user_id`= ?";
			la_do_query($query_update, array(0, "", "", 0, $user_id), $dbh);

			//get user from the user table
			$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
			$sth_user = la_do_query($query_user, array($user_id), $dbh);
			$row_user = la_do_fetch_result($sth_user);
			//send MFA reset notification to a user
			$subject = utf8_encode("IT Audit Machine - Reset MFA");
			$email_content = "";
			$email_content .= "Hello ".$row_user["user_fullname"].",<br><br>";
			$email_content .= "You have been asked to set up new multi-factor authentication in ".$site_name.".<br>";
			$email_content .= "Please log in to set up the new multi-factor authentication.<br><br>";
			$email_content .= "Thank you.";

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
			if(file_exists($la_settings['upload_dir']."/form_{$form_id}/files") && is_writable($la_settings['upload_dir']."/form_{$form_id}/files")){
				Swift_Preferences::getInstance()->setCacheType('disk')->setTempDir($la_settings['upload_dir']."/form_{$form_id}/files");
			}else{
				Swift_Preferences::getInstance()->setCacheType('array');
			}
			
			$from_name  = html_entity_decode($la_settings['default_from_name'],ENT_QUOTES);
			$from_email = $la_settings['default_from_email'];
			$user_email = $row_user["user_email"];
			
			if(!empty($user_email)){
				$s_message = Swift_Message::newInstance()
				->setCharset('utf-8')
				->setMaxLineLength(1000)
				->setSubject($subject)
				->setFrom(array($from_email => $from_name))
				->setSender($from_email)
				->setReturnPath($from_email)
				->setTo($user_email)
				->setBody($email_content, 'text/html');

				//send the message
				$send_result = $s_mailer->send($s_message);
				
				if(empty($send_result)){
					$error = "Error occured while sending an email.";
				}
			}
			//send MFA reset notification to main admins
			if($la_settings['enable_registration_notification']){
				$login_user = $_SESSION['email'];
				$site_name = "https://".$_SERVER['SERVER_NAME'];
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				$content .= "<h3>Administrative user ".$login_user." has reset a multi-factor authentication of an administrative user in ".$site_name.".</h3>";
				$content .= "<hr/>";
				$content .= "<h3>User Details:</h3>";
				$content .= "<table>";
				$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>Email</td></tr>";
				$content .= "<tr><td style='width: 200px;'>{$row_user['user_id']}</td><td style='width: 200px;'>{$row_user['user_fullname']}</td><td style='width: 200px;'>{$row_user['user_email']}</td></tr>";
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}

			if($error == "") {
				$_SESSION['LA_SUCCESS'] = "You've successfully reset the MFA of this user.";
				$response_data = new stdClass();
				$response_data->status = "ok";
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			} else {
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
		} else if($_POST["origin"] == "login_verify") {
			$user_id = $_POST["user_id"];
			$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `tsv_enable` = ?, `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `user_id`= ?";
			la_do_query($query_update, array(0, "", "", 0, $user_id), $dbh);

			$_SESSION['LA_LOGIN_ERROR'] = "Please log in again to set up the new multi-factor authentication.";
			$response_data = new stdClass();
			$response_data->status = "ok";
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		}
	} elseif ($action == "reset_user_mfa") {
		if($_POST["origin"] == "view_user") {
			$error = "";
			$login_user = $_SESSION['email'];
			$site_name = "https://".$_SERVER['SERVER_NAME'];
			$user_id = $_POST["user_id"];

			$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `client_user_id`= ?";
			la_do_query($query_update, array("", "", 0, $user_id), $dbh);

			//get user from the user table
			$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
			$sth_user = la_do_query($query_user, array($user_id), $dbh);
			$row_user = la_do_fetch_result($sth_user);
			//send MFA reset notification to a user
			$subject = utf8_encode("IT Audit Machine - Reset MFA");
			$email_content = "";
			$email_content .= "Hello ".$row_user["full_name"].",<br><br>";
			$email_content .= "You have been asked to set up new multi-factor authentication in ".$site_name.".<br>";
			$email_content .= "Please log in to set up the new multi-factor authentication.<br><br>";
			$email_content .= "Thank you.";

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
			if(file_exists($la_settings['upload_dir']."/form_{$form_id}/files") && is_writable($la_settings['upload_dir']."/form_{$form_id}/files")){
				Swift_Preferences::getInstance()->setCacheType('disk')->setTempDir($la_settings['upload_dir']."/form_{$form_id}/files");
			}else{
				Swift_Preferences::getInstance()->setCacheType('array');
			}
			
			$from_name  = html_entity_decode($la_settings['default_from_name'],ENT_QUOTES);
			$from_email = $la_settings['default_from_email'];
			$user_email = $row_user["email"];
			
			if(!empty($user_email)){
				$s_message = Swift_Message::newInstance()
				->setCharset('utf-8')
				->setMaxLineLength(1000)
				->setSubject($subject)
				->setFrom(array($from_email => $from_name))
				->setSender($from_email)
				->setReturnPath($from_email)
				->setTo($user_email)
				->setBody($email_content, 'text/html');

				//send the message
				$send_result = $s_mailer->send($s_message);
				
				if(empty($send_result)){
					$error = "Error occured while sending an email.";
				}
			}
			//send MFA reset notification to main admins
			if($la_settings['enable_registration_notification']){
				$login_user = $_SESSION['email'];
				$site_name = "https://".$_SERVER['SERVER_NAME'];
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				$content .= "<h3>Administrative user ".$login_user." has reset a multi-factor authentication of a portal user in ".$site_name.".</h3>";
				$content .= "<hr/>";
				$content .= "<h3>User Details:</h3>";
				$content .= "<table>";
				$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>Email</td></tr>";
				$content .= "<tr><td style='width: 200px;'>{$row_user['client_user_id']}</td><td style='width: 200px;'>{$row_user['full_name']}</td><td style='width: 200px;'>{$row_user['email']}</td></tr>";
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}

			if($error == "") {
				$_SESSION['LA_SUCCESS'] = "You've successfully reset the MFA of this user.";
				$response_data = new stdClass();
				$response_data->status = "ok";
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			} else {
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
		} else if($_POST["origin"] == "login_verify") {
			$user_id = $_POST["user_id"];
			$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `client_user_id`= ?";
			la_do_query($query_update, array("", "", 0, $user_id), $dbh);

			$_SESSION['LA_CLIENT_LOGIN_ERROR'] = "Please log in again to set up the new multi-factor authentication.";
			$response_data = new stdClass();
			$response_data->status = "ok";
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		}
	}
?>