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
	$selected_users    = la_sanitize($_POST['selected_users']);
	$no_session_msg	   = (int) $_POST['no_session_msg'];
	$origin			   = trim($_POST['origin']);

	$login_user = $_SESSION['email'];
	$site_name = "https://".$_SERVER['SERVER_NAME'];
	
	if(empty($action)){
		die("This file can't be opened directly.");
	}else{
		if($action == 'delete'){
			$new_user_status = 2;
		}else if($action == 'suspend'){
			$new_user_status = 1;
		}else if($action == 'unsuspend'){
			$new_user_status = 0;
		}else{
			die("Invalid action value.");
		}
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$selected_users = $_REQUEST['selected_users'];
	$delete_user = array();
	if(count($selected_users)){
		foreach($selected_users as $key => $value){
			array_push($delete_user, (int)$value['value']);
		}
	}
	
	if($action == 'delete'){		
		if(count($delete_user)){
			$deleted_users = array();
			foreach($delete_user as $key => $post){
				$select_query = "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
				$result = la_do_query($select_query,array(':client_user_id' => $post),$dbh);
				$row = la_do_fetch_result($result);
				
				if($row){
					$check_entity_manager_query =  "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` != :client_user_id AND `client_id` = :client_id";
					$check_entity_manager_result = la_do_query($check_entity_manager_query,array(':client_user_id' => $post, ':client_id' => $row['client_id']),$dbh);
					$check_entity_manager_row = la_do_fetch_result($check_entity_manager_result);
					if(!$check_entity_manager_row){
						$query_delete_entity_relation  = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `entity_id` = :client_id";
						la_do_query($query_delete_entity_relation,array(':client_id' => $row['client_id']),$dbh);
					}
					array_push($deleted_users, $row);
					$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
					la_do_query($query_delete_user,array(':client_user_id' => $row['client_user_id']),$dbh);
					
					$query_delete_log  = "delete from `".LA_TABLE_PREFIX."portal_user_login_log` where `client_user_id` = :client_user_id";
					la_do_query($query_delete_log,array(':client_user_id' => $row['client_user_id']),$dbh);

					$query_delete_entity_relation  = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `client_user_id` = :client_user_id";
					la_do_query($query_delete_entity_relation,array(':client_user_id' => $row['client_user_id']),$dbh);
				}
			}
			//send delete portal users notification
			if(($la_settings['enable_registration_notification']) && (sizeof($deleted_users) > 0)){
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				if(sizeof($deleted_users) > 1){
					$content .= "<h3>Administrative user ".$login_user." has deleted ".sizeof($deleted_users)." portal users in ".$site_name.".</h3><hr/><h3>Deleted users are:</h3>";
				} else {
					$content .= "<h3>Administrative user ".$login_user." has deleted a portal user in ".$site_name.".</h3><hr/><h3> Deleted user is:</h3>";
				}
				$content .= "<table>";
	            $content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Phone</td><td style='width: 200px;'>Entity</td></tr>";			            
				foreach($deleted_users as $deleted_user){
					$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
				    $sth_entity = la_do_query($query_entity, array($deleted_user['client_id']), $dbh);
				    $row_entity = la_do_fetch_result($sth_entity);
					$content .= "<tr><td style='width: 200px;'>{$deleted_user['client_user_id']}</td><td style='width: 200px;'>{$deleted_user['full_name']}</td><td style='width: 200px;'>{$deleted_user['username']}</td><td style='width: 200px;'>{$deleted_user['email']}</td><td style='width: 200px;'>{$deleted_user['phone']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
				}
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}
		}		
	}else{
		if(count($delete_user)){
			$deleted_users = array();
			foreach($delete_user as $key => $post){
				$select_query = "select * from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id` = :client_user_id";
				$result = la_do_query($select_query,array(':client_user_id' => $post),$dbh);
				$row = la_do_fetch_result($result);
				
				if($row){
					array_push($deleted_users, $row);
				}
			}
			//send suspend/unblock portal users notification
			if(($la_settings['enable_registration_notification']) && (sizeof($deleted_users) > 0)){
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				if(sizeof($deleted_users) > 1){
					if($new_user_status == 1){
						$content .= "<h3>Administrative user ".$login_user." has suspended ".sizeof($deleted_users)." portal users in ".$site_name.".</h3><hr/><h3>Suspended users are:</h3>";
					}
					if($new_user_status == 0){
						$content .= "<h3>Administrative user ".$login_user." has unblocked ".sizeof($deleted_users)." portal users in ".$site_name.".</h3><hr/><h3>Unblocked users are:</h3>";
					}
					
				} else {
					if($new_user_status == 1){
						$content .= "<h3>Administrative user ".$login_user." has suspended a portal user in ".$site_name.".</h3><hr/><h3> Suspended user is:</h3>";
					}
					if($new_user_status == 0){
						$content .= "<h3>Administrative user ".$login_user." has unblocked a portal user in ".$site_name.".</h3><hr/><h3> Unblocked user is:</h3>";
					}					
				}
				$content .= "<table>";
	            $content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>User Name</td><td style='width: 200px;'>Email</td><td style='width: 200px;'>Phone</td><td style='width: 200px;'>Entity</td></tr>";			            
				foreach($deleted_users as $deleted_user){
					$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
				    $sth_entity = la_do_query($query_entity, array($deleted_user['client_id']), $dbh);
				    $row_entity = la_do_fetch_result($sth_entity);
					$content .= "<tr><td style='width: 200px;'>{$deleted_user['client_user_id']}</td><td style='width: 200px;'>{$deleted_user['full_name']}</td><td style='width: 200px;'>{$deleted_user['username']}</td><td style='width: 200px;'>{$deleted_user['email']}</td><td style='width: 200px;'>{$deleted_user['phone']}</td><td style='width: 200px;'>{$row_entity['company_name']}</td></tr>";
				}
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}
		}	
		@la_do_query("UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '{$new_user_status}' WHERE `client_user_id` IN (".implode(",", $delete_user).")",array(),$dbh);
	}	
	
	$response_data = new stdClass();
	
	$response_data->status    	= "ok";
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
	
	exit();