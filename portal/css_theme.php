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
	
	require('includes/theme-functions.php');
	
	$theme_id = (int) $_GET['theme_id'];
	
	$dbh = la_connect_db();
	
	$css_content = la_theme_get_css_content($dbh,$theme_id);
	
	header('Content-type: text/css');
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	
	echo $css_content;
?>