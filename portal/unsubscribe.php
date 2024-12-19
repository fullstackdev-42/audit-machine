<?php
 /********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');

//Connect to the database
$dbh = la_connect_db();

$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);

if(count($userEntities)){
	foreach(array_keys($userEntities, '0') as $key) {
		unset($userEntities[$key]);
	}
}

$inQuery = implode(',', array_fill(0, count($userEntities), '?'));

//Get the form to be added from the url
$form_id = (int) trim($_GET['id']);
$params = array_merge($userEntities, array($form_id));

$query = "DELETE FROM ".LA_TABLE_PREFIX."ask_client_forms WHERE `client_id` IN ({$inQuery}) AND `form_id` = ?";
$sth2 = $dbh->prepare($query);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	exit;
}

$query2 = "DELETE FROM `".LA_TABLE_PREFIX."form_payment_check` WHERE `company_id` IN ({$inQuery}) AND `form_id` = ?";
$sth3 = $dbh->prepare($query2);
try{
	$sth3->execute($params);
}catch(PDOException $e) {
	exit;
}

$query3 = "DELETE FROM `".LA_TABLE_PREFIX."form_payments` WHERE `company_id`  IN ({$inQuery}) AND `form_id` = ?";
$sth3 = $dbh->prepare($query2);
try{
	$sth3->execute($params);
}catch(PDOException $e) {
	exit;
}

//Redirect
$ssl_suffix = la_get_ssl_suffix();
header("Location: client_account.php");