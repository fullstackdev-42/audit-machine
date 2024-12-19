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
	require('../includes/helper-functions.php');
	require('../includes/filter-functions.php');
	require('../includes/check-client-session-ask.php');
	require('../includes/users-functions.php');

//Define functions
function validate_username($str) {

  // each array entry is an special char allowed
  // besides the ones from ctype_alnum
  $allowed = array(".", "-", "_");

  if ( ctype_alnum( str_replace($allowed, '', $str ) ) ) {
    return TRUE;
  } else {
    return FALSE;
  }
}

//Connect to the database
$dbh = la_connect_db();

//Get user id from session
$user_id = $_SESSION['la_client_user_id'];

//set variables for update
$full_name		= la_sanitize($_POST['full_name']);
$email			= la_sanitize($_POST['email']);
$phone			= la_sanitize(preg_replace("/[^0-9]/","",$_POST['phone']));
$username		= la_sanitize($_POST['username']);

//set redirect variables
$ssl_suffix = '';
if(!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')){
		$ssl_suffix = 's';
}

$current_dir = dirname($_SERVER['PHP_SELF']);
if($current_dir == "/" || $current_dir == "\\"){
	$current_dir = '';
}

//Validate username, email, phone number_format
if(!(validate_username($username)))
{
	$error = "Username is not valid.";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL))
{
	$error = "Email is not valid.";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}

//Check if username is available
$query = "select count(username) total_user from `".LA_TABLE_PREFIX."ask_client_users` where username = ?";
$params = array($username);
$sth = la_do_query($query,$params,$dbh);
$row = la_do_fetch_result($sth);

if(!empty($row['total_user'])){
	$error = 'This username address already being used.';
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}


//Run query
$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `full_name`=?, `email`=?, `phone`=?, `username`=? WHERE `client_user_id`=?";
$sth2 = $dbh->prepare($query);
$params = array(
	$full_name,
	$email,
	$phone,
	$username,
	$user_id
);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	$error = "Username is not available.";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}

header("Location: index.php");
exit;


?>
