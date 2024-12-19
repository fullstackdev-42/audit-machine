<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('../../includes/init.php');
	require('../../config.php');
	require('../../includes/db-core.php');
	require('../../includes/helper-functions.php');
	require('../../includes/filter-functions.php');
	require('../../includes/check-client-session-ask.php');
	require('../../includes/users-functions.php');
	require('../../lib/password-hash.php');


//Connect to the database
$dbh = la_connect_db();

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
if($username == "")
{
	$error = "Username cannot be blank";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}
if($newpassword == $retype)
	$newpassword = $retype;
else
{
	$error = "New password does not match";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}
if(strlen($newpassword) < 8)
{
	$error = "The new password must be a minimum of 8 characters";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}
if($newpassword == "")
{
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
//Test login attempt with password
$login = FALSE;

$username = strtolower(trim($username));
$password = trim($password);

if(empty($username) || empty($password)){
	$_SESSION['error'] = 'Incorrect email or password!';
}else{
	$password_is_valid = false;

	//get the password hash from the database
	$query  = "SELECT
					`password`,
					`client_user_id`,
					`client_id`
				FROM
					`".LA_TABLE_PREFIX."ask_client_users`
			   WHERE
					`username`=?";
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
}

if($login)
{
	//Update password

	//Get Hash
	$hasher = new Sha256Hash();
	$password_hash = $hasher->HashPassword($newpassword);

	//Run query
	$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `password` = ? WHERE `client_user_id` = ? AND `username` = ?";
	$sth2 = $dbh->prepare($query);
	$params = array(
					$password_hash,
					$user_id,
					$username);
	$sth2->execute($params);

	//Redirect
	header("Location: /change_done/index.php");
	exit;
}
else
{
	//Redirect back (incorrect password)
	$error = "Incorrect password";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}
?>
