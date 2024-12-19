<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
 
/********************************************
	    X S S     P R O T E C T I O N
********************************************/

$purifierConfig = HTMLPurifier_Config::createDefault();
$htmlp = new HTMLPurifier($purifierConfig);
global $htmlp;

/**
 * Escape all HTML, JavaScript, and CSS
 * 
 * @param string $input The input string
 * @param string $encoding Which character encoding are we using?
 * @return string
 */
function sanitizeInput($input, $encoding = 'UTF-8'){
	return $input;
	
	/*if(!empty($input)){
		$input = is_array($input) ? array_map('sanitize_deeply', $input) : strip_tags(htmlspecialchars(trim($input), ENT_QUOTES, $encoding));
	}
	
	return $input;*/
}

function sanitize_deeply($value, $encoding = 'UTF-8'){
	$value = is_array($value) ? array_map('sanitize_deeply', $value) : strip_tags(htmlspecialchars(trim($value), ENT_QUOTES, $encoding));
	return $value;
}

/**
 * Escape all HTML, JavaScript, and CSS
 * 
 * @param string $input The input string
 * @param string $encoding Which character encoding are we using?
 * @return string
 */
function noHTML($input, $encoding = 'UTF-8'){	
	global $htmlp;
	
	if(!empty($input)){
		$input = is_array($input) ? array_map('sanitize_deeply_nohtml', $input) : $htmlp->purify(htmlspecialchars(trim($input), ENT_QUOTES, $encoding));
	}
	
	return $input;
}

function sanitize_deeply_nohtml($value, $encoding = 'UTF-8'){
	global $htmlp;
	$value = is_array($value) ? array_map('sanitize_deeply_nohtml', $value) : $htmlp->purify(htmlspecialchars(trim($value), ENT_QUOTES, $encoding));
	return $value;
}

if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST'){
	$_POST = sanitizeInput($_POST);
}else if(strtoupper($_SERVER['REQUEST_METHOD']) === 'GET'){
	$_GET = sanitizeInput($_GET);
}

/********************************************
	   C S R F     P R O T E C T I O N
********************************************/
function createCsrfToken(){
	if (function_exists('mcrypt_create_iv')) {
		$_SESSION['csrf_token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
	} else {
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
	}
}

if (empty($_SESSION['csrf_token'])) {
	// version >= PHP 5.3
	createCsrfToken();
}

if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {	
	createCsrfToken();

	/*$fileName = end(explode("/", $_SERVER['SCRIPT_FILENAME']));
	
	if(!in_array($fileName, array('upload.php'))){	
		if(hash_equals($_SESSION['csrf_token'], $_POST['post_csrf_token'])) {
			// Proceed to process the form data
			unset($_SESSION['csrf_token']);
			createCsrfToken();
		} else {
			// Log this as a warning and keep an eye on these attempts
			die('Not Valid CSRF');
		}
	}*/
}