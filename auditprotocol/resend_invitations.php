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
	require('includes/users-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$action = trim($_POST['action']);
	$user_type = trim($_POST['user_type']);
	$user_id = trim($_POST['user_id']);

	if($action == "resend-invitation") {
		if($user_type == "admin" || $user_type == "examiner") {
			//generate new token and invitation time
			$token = sha1(uniqid($user['user_email'], true));
			$tstamp = $_SERVER["REQUEST_TIME"];

			//update token and tstamp
			$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `token` = ?, `tstamp` = ? WHERE `user_id` = ?";
			la_do_query($query_update, array($token, $tstamp, $user_id), $dbh);

			//get user information
			$query = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
			$result = la_do_query($query, array($user_id), $dbh);
			$user = la_do_fetch_result($result);

			$params = array('token' => $user['token']);
			la_send_one_time_url($dbh, $user['user_fullname'], $user['user_email'], $params);
			$_SESSION['LA_SUCCESS'] = 'A new invitation has been sent to '.$user['user_fullname'].'.';
			echo "success";
			exit();
		} else if($user_type == "user") {
			$tstamp = $_SERVER["REQUEST_TIME"];

			//update tstamp
			$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `tstamp` = ? WHERE `client_user_id` = ?";
			la_do_query($query_update, array( $tstamp, $user_id), $dbh);

			//get user information
			$query = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
			$result = la_do_query($query, array($user_id), $dbh);
			$user = la_do_fetch_result($result);

			//get entity information
			$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
			$result_entity = la_do_query($query_entity, array($user['client_id']), $dbh);
			$entity = la_do_fetch_result($result_entity);

			sendUserInviteNotification($dbh, $user['full_name'], $user['email'], $entity['company_name'], $entity['client_id']);
			$_SESSION['LA_SUCCESS'] = 'A new invitation has been sent to '.$user['full_name'].'.';
			echo "success";
			exit();
		}
	}
?>