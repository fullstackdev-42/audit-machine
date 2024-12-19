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

//Get client id from session
$client_id = $_SESSION['la_client_client_id'];

//set variables for update
$compnay_name		= la_sanitize($_POST['business']);
$contact_full_name		= la_sanitize($_POST['contact_full_name']);
$contact_email			= la_sanitize($_POST['contact_email']);
$contact_phone			= la_sanitize(preg_replace("/[^0-9]/","",$_POST['contact_phone']));
$company_name			= la_sanitize($_POST['company_name']);

//set redirect variables
$ssl_suffix = '';
if(!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')){
		$ssl_suffix = 's';
}

$current_dir = dirname($_SERVER['PHP_SELF']);
if($current_dir == "/" || $current_dir == "\\"){
	$current_dir = '';
}

//Validate email
if(!filter_var($contact_email, FILTER_VALIDATE_EMAIL))
{
	$error = "Email is not valid.";
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}

//Run query
$query = "UPDATE `".LA_TABLE_PREFIX."ask_clients` SET `company_name`=?, `contact_full_name`=?, `contact_email`=?, `contact_phone`=? WHERE `client_id`=?";
$sth2 = $dbh->prepare($query);
$params = array(
	$compnay_name,
	$contact_full_name,
	$contact_email,
	$contact_phone,
	$client_id
);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	$error = $e;
	$_SESSION['error'] = $error;
	header("Location: index.php");
	exit;
}

header("Location: index.php");
exit;

?>
