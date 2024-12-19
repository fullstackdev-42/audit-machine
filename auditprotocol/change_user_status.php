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
	require('includes/check-session.php');

	require('includes/filter-functions.php');
	require('includes/users-functions.php');
	
	$action 		   = trim($_POST['action']);
	$user_type         = trim($_POST['user_type']);
	$selected_users    = la_sanitize($_POST['selected_users']);
	$select_all		   = (int) $_POST['delete_all'];
	$no_session_msg	   = (int) $_POST['no_session_msg'];
	$origin			   = trim($_POST['origin']);
	$login_user = $_SESSION['email'];
	$site_name = "https://".$_SERVER['SERVER_NAME'];

	if(empty($action)){
		die("This file can't be opened directly.");
	}else{
		if($action == 'delete'){
			$action_type_id = 11;
		}else if($action == 'suspend'){
			$new_user_status = 2;
			$action_type_id = 12;
		}else if($action == 'unsuspend'){
			$new_user_status = 1;
			$action_type_id = 13;
		}else{
			die("Invalid action value.");
		}
	}

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		die("Access Denied. You don't have permission to administer IT Audit Machine.");
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$user_ip = $_SERVER['REMOTE_ADDR'];
	$action_text = json_encode( array('action_performed_by' => $_SESSION['email']) );
		
	if(!empty($selected_users)){
		$target_user_id_array = array();

		foreach ($selected_users as $data) {
			$user_id_value = (int) str_replace('entry_', '', $data['name']);
			
			//main administrator has user_id = 1 and should be excluded
			if($user_id_value !== 1){
				$target_user_id_array[] = $user_id_value;
			}
		}

		if(!empty($target_user_id_array)){

			//if the request coming from view_user.php page, only 1 entry being deleted
			if(!empty($origin) && ($origin == 'view_user')){

				$_SESSION['LA_SUCCESS'] = "User #{$target_user_id_array[0]} has been deleted.";

				//get the next entry_id
				$exclude_admin = false;

				$all_user_id_array = la_get_filtered_users_ids($dbh,$_SESSION['filter_users'],$exclude_admin);
				$user_key = array_keys($all_user_id_array,$target_user_id_array[0]);
				$user_key = $user_key[0];
			
				$user_key++;

				$next_user_id = $all_user_id_array[$user_key];

				//if there is no entry_id, fetch the first member of the array
				if(empty($next_user_id) && ($target_user_id_array[0] != $all_user_id_array[0])){
					$next_user_id = $all_user_id_array[0];
				}

			}else{
				if($action == 'delete'){
					if($user_type == 'admin') {
						$_SESSION['LA_SUCCESS'] = 'Selected admin has been deleted.';
					} else if($user_type == 'examiner') {
						$_SESSION['LA_SUCCESS'] = 'Selected examiner has been deleted.';
					}
				}else if($action == 'suspend'){
					if($user_type == 'admin') {
						$_SESSION['LA_SUCCESS'] = 'Selected admin has been suspended.';
					} else if($user_type == 'examiner') {
						$_SESSION['LA_SUCCESS'] = 'Selected examiner has been suspended.';
					}
				}else if($action == 'unsuspend'){
					if($user_type == 'admin') {
						$_SESSION['LA_SUCCESS'] = 'Selected admin has been unblocked.';
					} else if($user_type == 'examiner') {
						$_SESSION['LA_SUCCESS'] = 'Selected examiner has been unblocked.';
					}
				}
			}

			if(!empty($no_session_msg)){
				unset($_SESSION['LA_SUCCESS']);
			}

			$target_user_id_joined = implode("','", $target_user_id_array);
			$deleted_users = array();
			$query_deleted_users = "select * from `".LA_TABLE_PREFIX."users` where `user_id` in('{$target_user_id_joined}')";
			$params = array();
			$sth_deleted_users = la_do_query($query_deleted_users, $params, $dbh);
			while($row = la_do_fetch_result($sth_deleted_users)){
				array_push($deleted_users, $row);
			}
			
			if($action == 'delete'){
				$query = "delete from `".LA_TABLE_PREFIX."users` where `user_id` in('{$target_user_id_joined}')";
				$params = array();
				la_do_query($query,$params,$dbh);

				//delete records from ap_permissions table
				$query = "delete from `".LA_TABLE_PREFIX."permissions` where `user_id` in('{$target_user_id_joined}')";
				$params = array();
				la_do_query($query,$params,$dbh);

				//delete records from ap_permissions table
				$query = "delete from `".LA_TABLE_PREFIX."entity_examiner_relation` where `user_id` in('{$target_user_id_joined}')";
				$params = array();
				la_do_query($query,$params,$dbh);
				
				//send delete notification
				if($la_settings['enable_registration_notification']){
					if($user_type == 'admin') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has deleted ".sizeof($deleted_users)." administrative users in ".$site_name.".</h3><hr/><h3>Deleted admins are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has deleted an administrative user in ".$site_name.".</h3><hr/><h3> Deleted admin is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					} else if($user_type == 'examiner') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has deleted ".sizeof($deleted_users)." examiner users in ".$site_name.".</h3><hr/><h3>Deleted examiners are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has deleted an examiner user in ".$site_name.".</h3><hr/><h3> Deleted examiner is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					}
				}
			}else if($action == 'suspend'){
				//simply set the status of the record
				$query = "update `".LA_TABLE_PREFIX."users` set `status`=? where `user_id` in('{$target_user_id_joined}')";
				$params = array($new_user_status);
				la_do_query($query,$params,$dbh);
				addUserActivityBatch($target_user_id_array, $action_type_id, $action_text, $user_ip, $dbh);

				//send suspend notification
				if($la_settings['enable_registration_notification']){
					if($user_type == 'admin') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has suspended ".sizeof($deleted_users)." administrative users in ".$site_name.".</h3><hr/><h3>Suspended admins are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has suspended an administrative user in ".$site_name.".</h3><hr/><h3> Suspended admin is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					} else if($user_type == 'examiner') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has suspended ".sizeof($deleted_users)." examiner users in ".$site_name.".</h3><hr/><h3>Suspended examiners are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has suspended an examiner user in ".$site_name.".</h3><hr/><h3> Suspended examiner is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					}
				}
			}else if($action == 'unsuspend'){
				//simply set the status of the record
				//also unblock the user from account locking
				$query = "update `".LA_TABLE_PREFIX."users` set `status`=?,login_attempt_date=NULL,login_attempt_count=0 where `user_id` in('{$target_user_id_joined}') and `status`=2";
				$params = array($new_user_status);
				la_do_query($query,$params,$dbh);
				addUserActivityBatch($target_user_id_array, $action_type_id, $action_text, $user_ip, $dbh);

				//send unblock notification
				if($la_settings['enable_registration_notification']){
					if($user_type == 'admin') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has unblocked ".sizeof($deleted_users)." administrative users in ".$site_name.".</h3><hr/><h3>Unblocked admins are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has unblocked an administrative user in ".$site_name.".</h3><hr/><h3> Unblocked admin is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					} else if($user_type == 'examiner') {
						$subject = "Continuum GRC Account Management Alert";
						$content = "<h2>Continuum GRC Account Management Alert</h2>";
						if(sizeof($deleted_users) > 1){
							$content .= "<h3>Administrative user ".$login_user." has unblocked ".sizeof($deleted_users)." examiner users in ".$site_name.".</h3><hr/><h3>Unblocked examiners are:</h3>";
						} else {
							$content .= "<h3>Administrative user ".$login_user." has unblocked an examiner user in ".$site_name.".</h3><hr/><h3> Unblocked examiner is:</h3>";
						}
						$content .= "<table>";
						$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td></tr>";
						foreach($deleted_users as $deleted_user){
							$content .= "<tr><td style='width: 200px;'>{$deleted_user['user_id']}</td><td style='width: 200px;'>{$deleted_user['user_fullname']}</td><td style='width: 200px;'>{$deleted_user['user_email']}</td></tr>";
						}
						$content .= "</table>";
						sendUserManagementNotification($dbh, $la_settings, $subject, $content);
					}
				}
			}
		}
	}

	$response_data = new stdClass();
	$response_data->status    	= "ok";
	
	if(!empty($next_user_id)){
		$response_data->user_id = $next_user_id;
	}else{
		$response_data->user_id = 0;
	}

	$response_json = json_encode($response_data);
		
	echo $response_json;
?>