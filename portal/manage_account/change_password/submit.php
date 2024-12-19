<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
$pathSeparator = "../../";
require($pathSeparator.'includes/init.php');
require($pathSeparator.'config.php');
require($pathSeparator.'includes/db-core.php');
require($pathSeparator.'includes/helper-functions.php');
require($pathSeparator.'includes/filter-functions.php');
require($pathSeparator.'includes/check-client-session-ask.php');
require($pathSeparator.'includes/users-functions.php');
require($pathSeparator.'lib/password-hash.php');
require($pathSeparator.'lib/swift-mailer/swift_required.php');


//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

//add logic for 50% Rule on Passwords
$forced_password = false;
if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) )
  $forced_password = true;

//Get user id from session
$user_id = $_SESSION['la_client_user_id'];

//Validate form was submitted correctly
if(!(
		isset($_POST['username'])
	&&	isset($_POST['password'])
	&& 	isset($_POST['new_password'])
	&&	isset($_POST['retype'])
))
{
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$uri = 'https://';
	} else {
		$uri = 'http://';
	}
	header('Location: ./');
	exit;
}

//set redirect variables
$ssl_suffix = '';
if(!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')){
		$ssl_suffix = 's';
}

$current_dir = dirname($_SERVER['PHP_SELF']);
if($current_dir == "/" || $current_dir == "\\"){
	$current_dir = '';
}


//Set variables for update
$username		= la_sanitize($_POST['username']);
$password		= la_sanitize($_POST['password']);
$newpassword	= la_sanitize($_POST['new_password']);
$retype			= la_sanitize($_POST['retype']);
if($username == ""){
	$error = "Username cannot be blank";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}

if( $forced_password ) {
	//if `50% Rule on Passwords is enabled` create a random password and no need to validate it
	$password_range = range(15, 20);
	$password_length = array_rand(array_flip($password_range));
	$newpassword = randomPassword($password_length);
} else {

	if($newpassword == $retype)
		$newpassword = $retype;
	else{
		$error = "New password does not match";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if(strlen($newpassword) < 8){
		$error = "The new password must be a minimum of 8 characters";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if($newpassword == ""){
		$error = "The new password cannot be blank";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if( !preg_match("#[0-9]+#", $newpassword) ) {
		$error = "The new password must include at least one number!";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if( !preg_match("#[a-z]+#", $newpassword) ) {
		$error = "The new password must include at least one letter!";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if( !preg_match("#[A-Z]+#", $newpassword) ) {
		$error = "The new password must include at least one CAPS!";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
	if( !preg_match("#\W+#", $newpassword) ) {
		$error = "The new password must include at least one symbol!";
		$_SESSION['error'] = $error;
		header("Location: index.php");
		exit;
	}
}

//Test login attempt with password
$login = FALSE;

$username = strtolower(trim($username));
$password = trim($password);

if(empty($username) || empty($password)){
	$_SESSION['error'] = 'Incorrect email or password!';
}else{
	$password_is_valid = false;

	//get the password hash from the database
	$query  = "SELECT `password`, `client_user_id`, `client_id` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `username`=?";
	$sth2 = $dbh->prepare($query);
	$params = array($username);
	$sth2->execute($params);
	$row = la_do_fetch_result($sth2);

	$stored_password_hash = $row['password'];
	$user_id 			  = $row['client_user_id'];
	$client_id 			  = $row['client_id'];
	$hasher 	   = new Sha256Hash();
	$check_result  = $hasher->CheckPassword($password, $stored_password_hash);
	if($check_result){
		$password_is_valid = true;
	}

	if($password_is_valid){
		$login = TRUE;
	}

	//Get Hash for new password
	$password_hash = $hasher->HashPassword($newpassword);

	//no need to check user's old passwords when 50% Rule on Passwords is enabled
	/*if( !$forced_password ) {

		//get last 3 passwords used by user
		$query = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=0 order by `id` DESC limit 3";
		$params = array($user_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$old_passwords = [];
		while($row = la_do_fetch_result($sth)){
			$old_passwords[]  = $hasher->CheckPassword($newpassword, $row['password']);
		}

		if( count($old_passwords) > 0 ) {
			if( in_array(1, $old_passwords) ) {
				$_SESSION['error'] = 'Your new password must be different from your previous 3 passwords.';
				header("Location: index.php");
				exit;	
			}
		}
	}*/
}

if($login){

	//Run query
	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `password` = ?, `password_change_date` = ? WHERE `client_user_id` = ? AND `username` = ?";
	$sth2 = $dbh->prepare($query);
	$params = array( $password_hash, strtotime(date('Y-m-d H:i:s')), $user_id, $username);
	$sth2->execute($params);


	if( !$forced_password ) {
		//save new password to `old_password_hash` table
		insert_old_password_hash($user_id, $password_hash, $dbh);


		$query_pass_insert = "INSERT INTO `".LA_TABLE_PREFIX."old_password_hash` (`user_id`, `password`, `is_admin`) VALUES (:user_id, :password, 0 )";
		$params_pass_insert = array();
		$params_pass_insert[':user_id'] = (int) $user_id;
		$params_pass_insert[':password'] = $password_hash;
		la_do_query($query_pass_insert,$params_pass_insert,$dbh);
		
		$_SESSION['success'] = 'Password updated successfully';
	} else {
		la_send_login_info($dbh,$user_id,$newpassword);
		$_SESSION['success'] = "Password updated successfully. Your new password is <span style=\"color:#F00\">{$newpassword}</span>";
	}
	
	//Redirect
	header("Location: ../");
	exit;
}else{
	//Redirect back (incorrect password)
	$error = "Incorrect Current Password";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}