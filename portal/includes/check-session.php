<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
	//check if user logged in or not
	//if not redirect them into login page

	$dbh = la_connect_db();

	if(empty($_SESSION['la_client_logged_in'])){
		$ssl_suffix  = la_get_ssl_suffix();

		$current_dir = dirname($_SERVER['PHP_SELF']);
      	if($current_dir == "/" || $current_dir == "\\"){
			$current_dir = '';
		}

		$_SESSION['LA_LOGIN_ERROR'] = 'Your session has expired. Please login.';
		header("Location: index.php?from=".base64_encode($_SERVER['REQUEST_URI']));
		exit;
	}

	$la_settings = la_get_settings($dbh);
	$saml_enabled = $la_settings['saml_'];

	if (($_SESSION['la_client_client_id'] == "" || $_SESSION['la_client_client_id'] == "0" || $_SESSION['la_client_client_id'] == 0 || is_null($_SESSION['la_client_client_id']) || empty($_SESSION['la_client_client_id'])) && $la_settings['saml_login'] == 1) {
		header("Location: https://".$_SERVER['HTTP_HOST']. "/portal/index.php");
	}

?>
