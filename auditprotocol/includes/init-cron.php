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
			
	header("Content-Type: text/html; charset=UTF-8");