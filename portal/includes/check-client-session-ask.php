<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
if(!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')){
	$ssl_suffix = 's';
}

if(empty($_SESSION['la_client_logged_in'])){

	$current_dir = dirname($_SERVER['PHP_SELF']);
	if($current_dir == "/" || $current_dir == "\\"){
		$current_dir = '';
	}

	// if(empty($_SESSION['la_logged_in'])){

	// 	$current_dir = dirname($_SERVER['PHP_SELF']);
	// 	if($current_dir == "/" || $current_dir == "\\"){
	// 		$current_dir = '';
	// 	}

		$_SESSION['LA_LOGIN_ERROR'] = 'Your session has expired. Please login.';
		header("Location: /portal/index.php?from=".base64_encode($_SERVER['REQUEST_URI']));
		exit;
	// }
}

$dbh	= la_connect_db();
$la_settings = la_get_settings($dbh);

if($la_settings["enable_site_down"]) {
	header("Location: client_logout.php");
	exit;
}

if (($_SESSION['la_client_client_id'] == "" || $_SESSION['la_client_client_id'] == "0" || $_SESSION['la_client_client_id'] == 0 || is_null($_SESSION['la_client_client_id']) || empty($_SESSION['la_client_client_id'])) && $la_settings['saml_login'] == 1) {
	header("Location: https://".$_SERVER['HTTP_HOST']. "/portal/index.php");
}

//redirect to the client_account.php if a login msg is enabled but a user attempts to access other pages by entering the url
if($_SESSION['user_login_message_enabled'] == 1) {
	if($_SERVER['PHP_SELF'] == "/portal/client_account.php" || $_SERVER['PHP_SELF'] == "/portal/client_logout.php") {
		//do nothing
	} else {
		header("Location: /portal/client_account.php");
		exit();
	}
}

?>
