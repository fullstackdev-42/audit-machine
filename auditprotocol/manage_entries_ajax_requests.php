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
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/entry-functions.php');
	require('includes/users-functions.php');
	require('includes/filter-functions.php');
	require('lib/swift-mailer/swift_required.php');


	// print_r($_SESSION);
	// die();

	if( !empty( $_GET['approval_status'] ) && !empty( $_GET['cId'] ) && !empty( $_GET['form_id'] ) ) {
		$approval_status 	= (int) trim($_GET['approval_status']);
		$company_id 	= (int) trim($_GET['cId']);
		$form_id 	= (int) trim($_GET['form_id']);		
	} else {
		$approval_status 	= (int) trim($_POST['approval_status']);
		$company_id 	= (int) trim($_POST['cId']);
		$form_id 	= (int) trim($_POST['form_id']);
		$message 	= trim($_POST['notes']);
	}
	// $column_preferences = la_sanitize($_POST['col_pref']);
	$user_id 	= (int) $_SESSION['la_user_id'];
	

	if(empty($company_id) || empty($approval_status) || empty($user_id) || empty($form_id)){
		$status = 'error';
		$message = 'Error occured while processing.';
		$_SESSION['LA_ERROR'] = $message;

	} else {

		$dbh = la_connect_db();
		
		$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = :field_name AND `company_id` = :company_id";
		$result = la_do_query($query, array(':company_id' => $company_id,':field_name' => 'approval_status'),$dbh);
		
		while($row = la_do_fetch_result($result)){
			$approval_status_db = $row['data_value'];
		}

		if( $approval_status_db > 0 ) {
			$_SESSION['LA_ERROR'] = 'Entry already updated by other user.';
		} else {

			$insert_form_approvals = false;
			$update_form_id = false;
			$update_form_approvals = false;
			$send_email_to_next = false;

			//check if Single-Step Approval or Multi-Step Approval
			// $query  = "SELECT * FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = :form_id";
			// $result = la_do_query($query, array(':form_id' => $form_id),$dbh);
			
			// while($row = la_do_fetch_result($result)){
			// 	$logic_approver_enable = $row['logic_approver_enable'];
			// 	$logic_approver_enable_1_a = $row['logic_approver_enable_1_a'];
			// }

			$query  = "SELECT `data` FROM `".LA_TABLE_PREFIX."form_approval_logic_entry_data` where `form_id` = {$form_id} and company_id={$company_id}";
			$result = la_do_query($query,array(),$dbh);
			$form_logic_data    = la_do_fetch_result($result);

			$logic_approver_enable = '';
			if($form_logic_data){
				$form_logic_data_arr = json_decode($form_logic_data['data']);
				$logic_approver_enable = $form_logic_data_arr->logic_approver_enable;
				
				$logic_approver_enable_1_a = 0;
 				if( $logic_approver_enable == 1 ) {
					$logic_approver_enable_1_a = $form_logic_data_arr->selected_admin_check_1_a;
 				}
			}


			if( $logic_approver_enable == 1 ) {
				//3 conditions here

				if( $logic_approver_enable_1_a == 1 ) {
					//any user can approve it
					$insert_form_approvals = true;
					$update_form_id = true;
				} elseif ( $logic_approver_enable_1_a == 2 ) {
					//only selected users can approve/deny it but it will be considered as approved/denied based on decision of first user
					// $query = "select 
					// 			user_id
					// 		from 
					// 			".LA_TABLE_PREFIX."approval_logic_conditions 
					// 	   	where 
					// 	   		form_id=?";
					// $params = array($form_id);
					// $sth = la_do_query($query,$params,$dbh);
					
					// while($row = la_do_fetch_result($sth)){
					// 	$approval_allowed_users [] = $row['user_id'];
					// }

					// $user_order_process_arr = $form_logic_data_arr->user_order_process;
					$all_selected_users = $form_logic_data_arr->all_selected_users;
					$approval_allowed_users = explode(',', $all_selected_users);
					

					// foreach ($user_order_process_arr as $user_order_obj) {
					// 	$approval_allowed_users[] = $user_order_obj->user_id;

					// }

					if( in_array($user_id, $approval_allowed_users) ) {
						$update_form_approvals = true;
						$update_form_id = true;
					}
				}
				elseif ( $logic_approver_enable_1_a == 3 ) {
					//only selected users can approve/deny it but it will be considered as approved only if all users approve it
					// die('in 3');
					// var_dump($update_form_id);
					// var_dump($update_form_approvals);
					// die();
					// $query = "select 
					// 			user_id
					// 		from 
					// 			".LA_TABLE_PREFIX."approval_logic_conditions 
					// 	   	where 
					// 	   		form_id=?";
					// $params = array($form_id);
					// $sth = la_do_query($query,$params,$dbh);
					
					// $approval_allowed_users = [];
					// while($row = la_do_fetch_result($sth)){
					// 	$approval_allowed_users [] = $row['user_id'];
					// }

					// $user_order_process_arr = $form_logic_data_arr->user_order_process;
					$all_selected_users = $form_logic_data_arr->all_selected_users;
					$approval_allowed_users = explode(',', $all_selected_users);

					// foreach ($user_order_process_arr as $user_order_obj) {
					// 	$approval_allowed_users[] = $user_order_obj->user_id;

					// }

					// print_r($form_logic_data_arr);
					// print_r($approval_allowed_users);
					// die('in ');

					if( in_array($user_id, $approval_allowed_users) ) {
						if( $approval_status == 2 ) {
							$update_form_approvals = true;
							$update_form_id = true;
						} else if ( $approval_status == 1 ) {
							// die('in 3 else if');
							//first check if this is the last user to verify it and mark it as approved
							$query  = "SELECT count(*) as total_user_left FROM `".LA_TABLE_PREFIX."form_approvals` WHERE is_replied = 0 AND company_id = :company_id AND user_id != $user_id";
							$result = la_do_query($query,array(':company_id' => $company_id),$dbh);
							$row    = la_do_fetch_result($result);
							
							if( $row['total_user_left'] == 0 ) {
								// echo "in if";
								//means this is last user to verify this entry
								$update_form_id = true;
							}
							$update_form_approvals = true;
						}
					}
				}

				
			} elseif ( $logic_approver_enable == 2 ) {
				//users can approve in order only
				// $query = "select 
				// 			user_id, user_order
				// 		from 
				// 			".LA_TABLE_PREFIX."approval_logic_conditions 
				// 	   where 
				// 	   		form_id=?";
				// $params = array($form_id);
				// $sth = la_do_query($query,$params,$dbh);
				
				// $approval_allowed_users = [];
				// while($row = la_do_fetch_result($sth)){
				// 	$approval_allowed_users [] = $row['user_id'];
				// }

				$user_order_process_arr = $form_logic_data_arr->user_order_process;
				$approval_allowed_users = [];

				foreach ($user_order_process_arr as $user_order_obj) {
					$approval_allowed_users[] = $user_order_obj->user_id;

				}

				//this query is to check if this is the turn of current user to approve
				$query = "select 
								user_id, user_order
							from 
								".LA_TABLE_PREFIX."form_approvals 
						   where 
						   		company_id = :company_id AND is_replied = 0 order by user_order LIMIT 1";
				
				$result = la_do_query($query,array(':company_id' => $company_id),$dbh);
				while($row = la_do_fetch_result($result)){
					$current_user_order_id = $row['user_id'];
				}
				

				if( in_array($user_id, $approval_allowed_users) && ($current_user_order_id == $user_id) ) {
					//this use can approve it
					$update_form_approvals = true;
					if( $approval_status == 2 ) {
						//deny form if any user denies it
						$update_form_id = true;
					} else if ( $approval_status == 1 ) {
						
						//first check if this is the last user to verify it and mark it as approved
						$query  = "SELECT count(*) as total_user_left FROM `".LA_TABLE_PREFIX."form_approvals` WHERE is_replied = 0 AND company_id = :company_id AND user_id != $user_id";
						$result = la_do_query($query,array(':company_id' => $company_id),$dbh);
						$row    = la_do_fetch_result($result);
						
						if( $row['total_user_left'] == 0 ) {
							//means this is last user to verify this entry
							$update_form_id = true;
						} else {
							$send_email_to_next = true;
						}
						
					}
				}
			}

			// var_dump($update_form_id);
			// var_dump($update_form_approvals);
			// die();

			if( $insert_form_approvals ) {
				$query = "INSERT INTO `".LA_TABLE_PREFIX."form_approvals` (user_id, company_id, form_id, user_order, is_replied, message) values (?,?,?,?,?,?)";
				$params = array($user_id,$company_id,$form_id,1,$approval_status,$message);
				la_do_query($query,$params,$dbh);
			}

			if( $update_form_approvals ) {
				$update_form_approvals_query = "UPDATE `".LA_TABLE_PREFIX."form_approvals` SET is_replied= $approval_status, message = '".$message."' WHERE user_id=$user_id AND company_id=$company_id";
				la_do_query($update_form_approvals_query,[],$dbh);

				if( $send_email_to_next ) {
					//as total user left is greater than 0 send email to next user

					//get email of user
					$query = "select user_id from ".LA_TABLE_PREFIX."form_approvals where 
						   		company_id = :company_id AND is_replied = 0 order by user_order LIMIT 1";
				
					$result = la_do_query($query,array(':company_id' => $company_id),$dbh);
					$row    = la_do_fetch_result($result);
					$next_user_id = $row['user_id'];

					$query  = "SELECT user_email FROM `".LA_TABLE_PREFIX."users` WHERE user_id = $next_user_id";
					$result = la_do_query($query,array(':company_id' => $company_id),$dbh);
					$row    = la_do_fetch_result($result);
					$user_email = $row['user_email'];
					la_send_approver_next_email($dbh,$form_id,$user_email);
					// die('sending next email');
				}


			}

			if( $update_form_id ) {
				$query1 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET data_value= $approval_status WHERE field_name='approval_status' AND company_id='".$company_id."'";
				la_do_query($query1,$params_table_data,$dbh);
				la_send_logic_notifications_final_approval_status($dbh,$form_id,$company_id,$options=array());
				$status = 'success';
				$message = 'Entry updated successfully.';
			}


			if( $approval_status == 1 )
				$status = 'Apprived';
			if( $approval_status == 2 )
				$status = 'Denied';

			$_SESSION['LA_SUCCESS'] = "Entry has been $status successfully.";
		}

	}

	// $response_data->status    	= $status;
	// $response_data->message 	= $message;
	// $response_data->form_id 	= $form_id;
	
	// $response_json = json_encode($response_data);
	die();
?>