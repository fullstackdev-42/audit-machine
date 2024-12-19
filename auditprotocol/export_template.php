<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/entry-functions.php');
	require('includes/users-functions.php');


	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to export templates.";
				
		header("Location: restricted.php");
		exit;
	}

	ob_clean(); //clean the output buffer

	$template_id = (int) trim($_REQUEST['template_id']);

	if(empty($template_id)){
		die("Invalid template ID.");
	}

	$export_content = '';
	
	$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_templates` WHERE id = ?";
	$sth = la_do_query($query, array($template_id), $dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)) {
		$template_data = new stdClass();
		$template_data->template_name = $row["name"];
		$template_data->template_content = $row["data"];
		$export_content = json_encode($template_data);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public", false);
		header("Content-Description: File Transfer");
		header("Content-Type: text/plain");
		header("Content-Disposition: attachment; filename=\"template-{$template_data->template_name}.json\"");

		$output_stream = fopen('php://output', 'w');
		fwrite($output_stream, $export_content);
		fclose($output_stream);
	} else {
		die("Invalid template ID.");
	}
?>