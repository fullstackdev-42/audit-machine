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
require('includes/users-functions.php');
require('lib/swift-mailer/swift_required.php');
ob_clean(); //clean the output buffer
$dbh = la_connect_db();

function download_zip ($form_id, $company_id, $entry_id, $dbh) {
	$query = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = {$form_id} and `company_id` = {$company_id} and `isZip` = 1 order by `docx_create_date` DESC LIMIT 1";
	$sth = la_do_query($query,array(),$dbh);
	$row = la_do_fetch_result($sth);
	$file_name = $file = $row['docxname'];
	if( !empty($file) ) {
		$file = $_SERVER["DOCUMENT_ROOT"] . "/portal/template_output/" . $file;
		
		if (file_exists($file)) {

			send_entry_document($dbh, $form_id, $entry_id, $file);
			// add user activity to log: activity - 8 (document download)
			addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 8, "downloaded document file <b>{$file_name}</b>", time(), $_SERVER['REMOTE_ADDR']);

			header('Content-Description: File Transfer');
			header('Content-type: application/zip');
			header('Content-Disposition: attachment; filename="'.basename($file). '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			exit;
		} else {
			echo "File not found!";
		}
	}
}



if ( isset($_GET['form_id']) && isset($_GET['entry_id']) ) {
	$form_id  = (int) trim($_GET['form_id']);
	$company_id = (int) trim($_GET['company_id']);
	$entry_id = (int) trim($_GET['entry_id']);

	download_zip ($form_id, $company_id, $entry_id, $dbh);
}