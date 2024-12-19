<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');

	$referrer = trim($_GET['ref']);
	$referrer = htmlspecialchars(base64_decode($referrer),ENT_QUOTES);

	setcookie("la_safari_cookie_fix", "1", 0); //cookie expire at the end of session (browser being closed)

	header("Location: {$referrer}");
	exit;	
?>