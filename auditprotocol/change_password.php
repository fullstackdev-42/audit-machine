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
	require('lib/password-hash.php');	
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$input = la_sanitize($_POST);
	$hasher = new Sha256Hash();


	$response = [];
	$response['status'] = 'error';
	if(empty($input['np']) && empty($input['user_id']) && empty($input['cp']) ){
		$response['message'] = "Request is missing required parameters.";
		custom_json_response($response);
	}
	
	$newpassword = $input['np'];
	$retype = $input['cp'];
	$user_id = $input['user_id'];

	/*
		*check permissions and privileges
		*normal user should only be able to change his own password
		*check user privileges, is this user has privilege to administer IT Audit Machine?
	*/
	if(!empty($_SESSION['la_user_privileges']['priv_administer'])){
		//this is administrator, allowed to change the password of any other user's password
		//except the main administrator password
		if($user_id == 1 && $_SESSION['la_user_id'] != 1){
			$response['message'] = "Access Denied. You don't have permission to change Main Administrator password.";
			custom_json_response($response);
		}
	} else{
		$user_id = $_SESSION['la_user_id']; //this is normal user, make sure he only change his own password

	}

	if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) ){
		//if `50% Rule on Passwords is enabled` create a random password and no need to validate it
		$forced_password = true;
		$password_range = range(15, 20);
		$password_length = array_rand(array_flip($password_range));
		$newpassword = randomPassword($password_length);
	} else {
		//add password validations here
		if( $newpassword == $retype )
			$newpassword = $retype;
		else{
			$response['message'] = "New password does not match";
			custom_json_response($response);
		}

		if(strlen($newpassword) < 8){
			$response['message'] = "The new password must be a minimum of 8 characters";
		} else if( !preg_match("#[0-9]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one number!";
		}else if ( !preg_match("#[a-z]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one letter!";
		}else if ( !preg_match("#[A-Z]+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one CAPS!";
		}else if ( !preg_match("#\W+#", $newpassword) ) {
			$response['message'] = "The new password must include at least one symbol!";
		}

		if( !empty($response['message']) )
			custom_json_response($response);	

		//get last 14 passwords used by user
		$query = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=1 order by `id` DESC limit 14";
		$params = array($user_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$old_passwords = [];
		
		while($row = la_do_fetch_result($sth)){
			$old_passwords[]  = $hasher->CheckPassword($newpassword, $row['password']);
		}

		if( count($old_passwords) > 0 ) {
			if( in_array(1, $old_passwords) ) {
				$response['message'] = "The new password must be different from the user's former 14 passwords.";
				custom_json_response($response);
			}
		}
	}
	

	//if passed all validations update password
	$new_password_hash = $hasher->HashPassword($newpassword);

	$query = "UPDATE ".LA_TABLE_PREFIX."users SET user_password = ? WHERE user_id = ?";
	$params = array($new_password_hash,$user_id);
	la_do_query($query,$params,$dbh);

	//save new password to `old_password_hash` table
	insert_old_password_hash($user_id, $new_password_hash, 1, $dbh);

	//if send_login parameter exist, resend the login information to user
	if( isset($input['send_login']) && ( !empty($input['send_login']) ) ){
		la_send_password_change_notification($dbh, $user_id);
	}

	//send change password of an admin notification
    if($la_settings['enable_registration_notification']){
    	//get user from the user table
		$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
	    $sth_user = la_do_query($query_user, array($user_id), $dbh);
	    $row_user = la_do_fetch_result($sth_user);
		$login_user = $_SESSION['email'];
		$site_name = "https://".$_SERVER['SERVER_NAME'];
		$subject = "Continuum GRC Account Management Alert";
		$content = "<h2>Continuum GRC Account Management Alert</h2>";
		$content .= "<h3>Administrative user ".$login_user." has changed the password of an administrative user in ".$site_name.".</h3>";
		$content .= "<hr/>";
		$content .= "<h3>User Details:</h3>";
		$content .= "<table>";
		$content .= "<tr><td style='width: 200px;'>User ID</td><td style='width: 200px;'>Full Name</td><td style='width: 200px;'>Email</td></tr>";
		$content .= "<tr><td style='width: 200px;'>{$row_user['user_id']}</td><td style='width: 200px;'>{$row_user['user_fullname']}</td><td style='width: 200px;'>{$row_user['user_email']}</td></tr>";
		$content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }

   	$response['status'] = 'success';
   	$response['new_password'] = $newpassword;
   	custom_json_response($response);
	
?>