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
	
	$target_email = strtolower(trim($_POST['target_email']));
	//$target_email = "alex.miller@continuumgrc.com";
	if(empty($target_email)){
		die("Invalid parameters.");
	}
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	//validate the email address

	$query  = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `email`=?";
	$params = array($target_email);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	if(!empty($row['email'])){
		if($row["status"] == 1) {
			echo '{"status" : "error", "message" : "Your account is temporarily suspended. Please contact admins."}';
		} else if($row["status"] == 0) {
			$user_entities = array();
			$query_entity = "SELECT DISTINCT entity.company_name FROM `".LA_TABLE_PREFIX."ask_clients` entity LEFT JOIN `".LA_TABLE_PREFIX."entity_user_relation` users ON entity.client_id = users.entity_id WHERE users.client_user_id = ?";
			$sth_entity = la_do_query($query_entity, array($row["client_user_id"]), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)) {
				array_push($user_entities, $row_entity["company_name"]);
			}
			if(count($user_entities) == 0) {
				echo '{"status" : "error", "message" : "You don\'t currently have any entities."}';
			} else {
				$subject = "Continuum GRC User Information Notification";
				$content = "<h2>Continuum GRC Information Notification</h2>";
				$content .= "<hr/>";
				$content .= "<h3>You can use the following information to log into the user portal on https://".$_SERVER['SERVER_NAME'].".</h3>";
				$content .= "<hr/>";
		    	$content .= "<h3>Your username and entity details:</h3>";
		        $content .= "<table>";
		        $content .= "<tr><td style='width:200px;'>Username:</td><td style='width:500px;'>{$row['username']}</td></tr>";
		        if(count($user_entities) == 1) {
		        	$content .= "<tr><td style='width:200px;'>Entity:</td><td style='width:500px;'>{$user_entities[0]}</td></tr>";
		        } else {
		        	$content .= "<tr><td style='width:200px;'>Entities:</td><td style='width:500px;'>You have several entities. You can use one of these: </br>".implode(', ', $user_entities)."</td></tr>";
		        }		        
		        $content .= "</table>";
	    		$email_param                  = array();
				$email_param['from_name']	  = 'IT Audit Machine';
				$email_param['from_email'] 	  = $la_settings['default_from_email'];
				$email_param['subject'] 	  = $subject;
				$email_param['as_plain_text'] = true;
				$from_name 	                  = html_entity_decode($email_param['from_name'] ,ENT_QUOTES);
				$from_email                   = html_entity_decode($email_param['from_email'] ,ENT_QUOTES);
				$subject_1 	                  = html_entity_decode($email_param['subject'] ,ENT_QUOTES);
				$email_content_type           = 'text/html';

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
				$s_message = Swift_Message::newInstance()
						->setCharset('utf-8')
						->setMaxLineLength(1000)
						->setSubject($subject_1)
						->setFrom(array($from_email => $from_name))
						->setSender($from_email)
						->setReturnPath($from_email)
						->setTo($target_email)
						->setBody($content, $email_content_type);
				//send the message
				$send_result = null;
				$send_result = $s_mailer->send($s_message);
				if(!empty($send_result)){
					//echo $send_result;
					/*echo "Error occured while sending email. Please try again later.";
					die();*/
				}
				echo '{"status" : "ok", "message" : "Your username and entity name has been sent to your email address."}';
			}
			
		}
	} else {
		echo '{"status" : "error", "message" : "Incorrect email address. Please try again."}';
	}

	die();
?>