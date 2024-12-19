<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('../includes/init.php');
require('../config.php');
require('../includes/db-core.php');
require('../lib/password-hash.php');
require('../lib/swift-mailer/swift_required.php');

//Connect to the database
$dbh = la_connect_db();

if(isset($_POST['mode']) && $_POST['mode'] == "suspend"){
	$query_select = "SELECT `is_admin` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$query_result = la_do_query($query_select,array(),$dbh);
	$query_result_row = la_do_fetch_result($query_result);
	if($query_result_row['is_admin'] == 1){
		echo json_encode(array('status' => 1, 'message' => 'This user cannot be suspended'));	
		exit();
	}else{
		$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `is_admin` = '0' AND `client_user_id`= ".$_POST['user_id'];
		la_do_query($query_update,array(':status' => 1),$dbh);
		echo json_encode(array('status' => 0));
		exit();
	}
}else if(isset($_POST['mode']) && $_POST['mode'] == "unblock"){
	$query_select = "SELECT `is_admin` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$query_result = la_do_query($query_select,array(),$dbh);
	$query_result_row = la_do_fetch_result($query_result);
	if($query_result_row['is_admin'] == 1){
		echo json_encode(array('status' => 1, 'message' => 'This user cannot be suspended'));	
		exit();
	}else{
		$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `is_admin` = '0' AND `client_user_id`= ".$_POST['user_id'];
		la_do_query($query_update,array(':status' => 0),$dbh);
		echo json_encode(array('status' => 0));
		exit();
	}
}else if(isset($_POST['mode']) && $_POST['mode'] == "deleted"){
	$query_select = "SELECT `is_admin` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_POST['user_id'];
	$query_result = la_do_query($query_select,array(),$dbh);
	$query_result_row = la_do_fetch_result($query_result);
	if($query_result_row['is_admin'] == 1){
		echo json_encode(array('status' => 1, 'message' => 'This user cannot be deleted'));	
		exit();
	}else{
		//$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = :status WHERE `is_admin` = '0' AND `client_user_id`= :client_user_id";
		$query_update = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `is_admin` = '0' AND `client_user_id`= :client_user_id";
		la_do_query($query_update,array(':client_user_id' => $_POST['user_id']),$dbh);
		echo json_encode(array('status' => 2));
		exit();
	}
}else if(isset($_POST['mode']) && $_POST['mode'] == "change_password"){
	$new_password_plain = $_POST['np'];
	$user_id 		 = (int) $_POST['user_id'];
	$send_login_info = (int) $_POST['send_login'];
	
	$setting_query = "SELECT `default_from_name`, `default_from_email` FROM `".LA_TABLE_PREFIX."settings`";
	$setting_result = la_do_query($setting_query,array(),$dbh);
	$setting_result_row = la_do_fetch_result($setting_result);
	
	$query_select = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$user_id;
	$query_result = la_do_query($query_select,array(),$dbh);
	$query_result_row = la_do_fetch_result($query_result);
	
	$hasher = new Sha256Hash();
	$new_password_hash = $hasher->HashPassword($new_password_plain);
	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET password = ? WHERE client_user_id = ?";
	$params = array($new_password_hash,$user_id);
	la_do_query($query,$params,$dbh);
	
	//get user information
	$query = "select full_name, `email` from `".LA_TABLE_PREFIX."ask_client_users` where `client_user_id`=? and `status`<>2";
	
	$params = array($user_id);
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$user_fullname = $row['full_name'];
	$user_email = $row['email'];
	
	$subject = 'Your IT Audit Machine login information';
	$email_content = "<p>Hello {$user_fullname}</p><p>You can login to IT Audit Machine panel using the following information:</p><p><b>Email:</b> {$user_email}</p><p><b>Password:</b> {$new_password_plain}</p><br>Thank you.";

	$subject = utf8_encode($subject);
	// Always set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	// More headers
	$headers .= 'From: '.$setting_result_row['default_from_name'].'<'.$setting_result_row['default_from_email'].'>' . "\r\n";
	
	if($send_login_info){
		@mail($user_email, $subject, $email_content, $headers);
	}
	
	echo '{"status" : "ok"}';
	exit();
}