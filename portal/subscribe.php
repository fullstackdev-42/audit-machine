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



//Get the client ID from the session data

$client_id = $_SESSION['la_client_client_id'];



//Get the form to be added from the url

$form_id = (int) trim($_GET['id']);



$query = "INSERT INTO ".LA_TABLE_PREFIX."ask_client_forms (`client_id`, `form_id`) VALUES (" . $client_id . ", " . $form_id . ")";

$sth2 = $dbh->prepare($query);

try{

	$sth2->execute($params);

}catch(PDOException $e) {

	exit;

}

//Redirect

$ssl_suffix = la_get_ssl_suffix();

header("Location: /portal/client_account.php");

 ?>

