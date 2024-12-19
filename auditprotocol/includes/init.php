<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/

	// Prevents javascript XSS attacks aimed to steal the session ID
	ini_set('session.cookie_httponly', 1);

	// Session ID cannot be passed through URLs
	ini_set('session.use_only_cookies', 1);

	// Uses a secure connection (HTTPS) if possible
	ini_set('session.cookie_secure', 1);

	session_start();

	// httponly and secure flags enable for PHPSESSID
	$currentCookieParams = session_get_cookie_params();
	$sidvalue = session_id();
	setcookie(
		'PHPSESSID', //name
		$sidvalue, //value
		0, //expires at end of session
		$currentCookieParams['path'], //path
		$currentCookieParams['domain'], //domain
		true, //secure
		true //httponly
	);

	date_default_timezone_set('America/Los_Angeles');

	/********************************************
	  C S R F     X S S     P R O T E C T I O N
	********************************************/

	$separator = "";

	if(isset($pathSeparator) && !empty($pathSeparator)){
		$separator = $pathSeparator;
	}

	include_once("{$separator}lib/htmlpurifier/library/HTMLPurifier.auto.php");
	include_once("xss-csrf.php");

	header("Content-Type: text/html; charset=UTF-8");

	//To shutdown the session monitor you can comment out this line of code.
	// If youre not using the disable switch in the settings screen because its broke...
	// THEN JUST GO FIX IT!!!!
