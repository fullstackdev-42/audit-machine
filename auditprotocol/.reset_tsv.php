<?php
/********************************************************************************
 IT Audit Machine
  
 Copyright 2007-2012 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/	
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('lib/password-hash.php');
	
	
	$dbh = la_connect_db();
	
	$query = "UPDATE ".LA_TABLE_PREFIX."users set tsv_enable=0,tsv_secret='',tsv_code_log='' where user_id=1";
	$params = array();
	la_do_query($query,$params,$dbh);

	$query = "UPDATE ".LA_TABLE_PREFIX."settings set enforce_tsv=0";
	$params = array();
	la_do_query($query,$params,$dbh);
	
	echo "Multi-Factor Authentication has been disabled. Please delete this file.";
?>
