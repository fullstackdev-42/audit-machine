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
	require('../itam-shared/includes/helper-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	$user_id  = $_SESSION['la_client_user_id'];

	logUserSession($dbh, $user_id, session_id(), 'logout', 0);

	//unlock all entries locked by this user
	deleteAllLock(array('entity_user_id' => $user_id, 'dbh' => $dbh));	
	
	$query = "update ".LA_TABLE_PREFIX."users set cookie_hash=? where user_id=?";
	$params = array('',$user_id);
	la_do_query($query,$params,$dbh);

	session_unset();
	session_destroy();
	session_write_close();
	setcookie(session_name(),'',0,'/');
	setcookie('la_remember','', time()-3600, "/"); //delete the remember me cookie	
	session_regenerate_id(true);
	
	if (isset($la_settings['saml_login']) && $la_settings['saml_login'] == 1) {
		header("Location: /itam-shared/simplesamlphp/www/module.php/core/as_logout.php?AuthId=default-sp&ReturnTo=/portal/index.php");
		exit();
	} else {
		header("Location: /portal/index.php");
		exit();
    }
?>
