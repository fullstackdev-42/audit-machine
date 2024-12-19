<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://www.continuumgrc.com/

 More info at: http://www.continuumgrc.com/
 ********************************************************************************/

	ini_set('memory_limit', '-1');
	ini_set('post_max_size', '512M');

	require('includes/init.php');

	require('config.php');
	require('includes/db-core.php');
	require_once("../itam-shared/includes/helper-functions.php");

	$dbh = la_connect_db();

    $form_id = $_GET['form_id'];

    if( !empty($form_id) )
        update_entry_machine_codes($dbh, $form_id);
    
?>