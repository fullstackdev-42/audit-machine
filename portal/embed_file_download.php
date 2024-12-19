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
	require('includes/check-session.php');
	require('includes/users-functions.php');

	$form_id 	 = (int) trim($_REQUEST['id']);

	if(empty($form_id)){
		die("Invalid form ID.");
	}

	$dbh = la_connect_db();

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

			$ssl_suffix = la_get_ssl_suffix();
			header("Location: restricted.php");
			exit;
		}
	}

	//get form properties
	$query 	= "select
					form_name,
					form_frame_height,
					form_captcha
			     from
			     	 ".LA_TABLE_PREFIX."forms
			    where
			    	 form_id = ?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)){
		$form_name 	= $row['form_name'];
		$clean_form_name = preg_replace("/[^A-Za-z0-9_-]/","",$form_name);
	}

	$ssl_suffix = la_get_ssl_suffix();

	$current_dir 	  = rtrim(dirname($_SERVER['PHP_SELF']));
	if($current_dir == "/" || $current_dir == "\\"){
		$current_dir = '';
	}

	$absolute_dir_path = rtrim(dirname($_SERVER['SCRIPT_FILENAME']));

	$php_embed_form_code =<<<EOT
<?php

header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
session_start();

require("{$absolute_dir_path}/itauditmachine.php");

\$la_param['form_id'] = {$form_id};
\$la_param['base_path'] = '';
\$la_param['show_border'] = true;
display_itauditmachine(\$la_param);

?>
EOT;

	if(empty($clean_form_name)){
		$clean_form_name = "form";
	}


	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public", false);
	header("Content-Description: File Transfer");
	header("Content-Type: text/plain");
	header("Content-Disposition: attachment; filename=\"{$clean_form_name}.php\"");

	$output_stream = fopen('php://output', 'w');
	fwrite($output_stream, $php_embed_form_code);
	fclose($output_stream);
?>
