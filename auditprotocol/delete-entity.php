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
	
	if(empty($action)){
		die("This file can't be opened directly.");
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$user_exists_in_entity = array();
	$delete_user = array();
	$deleted_users = array();
	if(count($selected_users)){
		foreach($selected_users as $key => $value){
			array_push($delete_user, (int)$value['value']);
		}
	}

	if($action == 'delete'){
		if(count($delete_user)){
			foreach($delete_user as $key => $post){
				$select_query = "select * from `".LA_TABLE_PREFIX."ask_clients` where `client_id` = :client_id";
				$result = la_do_query($select_query,array(':client_id' => $post),$dbh);
				$row = la_do_fetch_result($result);
				
				if($row){
					// check users exists or not
					$select_user_query = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` LEFT JOIN `".LA_TABLE_PREFIX."entity_user_relation` ON `".LA_TABLE_PREFIX."ask_client_users`.client_user_id = `".LA_TABLE_PREFIX."entity_user_relation`.client_user_id WHERE `".LA_TABLE_PREFIX."entity_user_relation`.`entity_id` = :client_id";
					$result_user = la_do_query($select_user_query,array(':client_id' => $row['client_id']),$dbh);
					$row_user = la_do_fetch_result($result_user);
					
					if(!$row_user){
						//push deleted user into an array
						$select_deleted_user = "select * from `".LA_TABLE_PREFIX."ask_clients` where `client_id` = :client_id";
						$result_deleted_user = la_do_query($select_deleted_user,array(':client_id' => $row['client_id']),$dbh);
						$row_deleted_user = la_do_fetch_result($result_deleted_user);
						array_push($deleted_users, $row_deleted_user);

						$query_delete_user = "delete from `".LA_TABLE_PREFIX."ask_clients` where `client_id` = :client_id";
						la_do_query($query_delete_user,array(':client_id' => $row['client_id']),$dbh);
						$query_delete_relation = "delete from `".LA_TABLE_PREFIX."entity_user_relation` where `entity_id` = :client_id";
						la_do_query($query_delete_relation,array(':client_id' => $row['client_id']),$dbh);
						$_SESSION['LA_SUCCESS'] = 'Selected entity has been deleted.';
					}else{
						array_push($user_exists_in_entity, array('client_id' => $row['client_id'], 'company_name' => $row['company_name']));
					}					
				}
			}
			//send delete entities notification
			if(sizeof($deleted_users) > 0){
				if($la_settings['enable_registration_notification']){
					$login_user = $_SESSION['email'];
					$site_name = "https://".$_SERVER['SERVER_NAME'];
					$subject = "Continuum GRC Account Management Alert";
					$content = "<h2>Continuum GRC Account Management Alert</h2>";
					if(sizeof($deleted_users) > 1){
						$content .= "<h3>Administrative user ".$login_user." has deleted ".sizeof($deleted_users)." entities in ".$site_name.".</h3>";
						$content .= "<hr/>";
		            	$content .= "<h3>Deleted entities are:</h3>";
					} else {
						$content .= "<h3>Administrative user ".$login_user." has deleted an entity in ".$site_name.".</h3>";
						$content .= "<hr/>";
		            	$content .= "<h3>Deleted entity is:</h3>";
					}			
		            
		            $content .= "<table>";
		            $content .= "<tr><td style='width:150px;'>Entity ID</td><td style='width:150px;'>Entity Name</td><td style='width:150px;'>Contact Email</td><td style='width:150px;'>Contact Phone</td><td style='width:150px;'>Entity Description</td></tr>";
		            foreach ($deleted_users as $deleted_entity) {
		            	$content .= "<tr><td style='width:150px;'>{$deleted_entity['client_id']}</td><td style='width:150px;'>{$deleted_entity['company_name']}</td><td style='width:150px;'>{$deleted_entity['contact_email']}</td><td style='width:150px;'>{$deleted_entity['contact_phone']}</td><td style='width:150px;'>{$deleted_entity['entity_description']}</td></tr>";
		            }
		            $content .= "</table>";
					sendUserManagementNotification($dbh, $la_settings, $subject, $content);
				}
			}
		}		
	}

	$response_data = new stdClass();	
	$response_data->status    	= "ok";
	$response_data->user_exists_in_entity = $user_exists_in_entity;
	$response_json = json_encode($response_data);	
	echo $response_json;	
	exit();