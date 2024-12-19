<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2016 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
require('includes/init.php');

@ini_set("include_path", './lib/pear/'.PATH_SEPARATOR.ini_get("include_path"));
@ini_set("max_execution_time",1800);
@ini_set('memory_limit','512M');

require('config.php');
require('includes/db-core.php');
require('includes/filter-functions.php');
require('includes/helper-functions.php');
require('includes/check-session.php');

require('includes/entry-functions.php');
require('includes/users-functions.php');


$ssl_suffix = la_get_ssl_suffix();

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

ob_clean(); //clean the output buffer

$error_message = "";

$form_id = (int) trim($_POST['form_id']);

if(empty($form_id)){
	$error_message = "Invaild form ID.";
	$response_data = new stdClass();
	$response_data->status = "error";
	$response_data->message = $error_message;
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
} else {
	//get form name
	$query_form = "SELECT form_name FROM ".LA_TABLE_PREFIX."forms WHERE form_id = ?";
	$sth_form = la_do_query($query_form, array($form_id), $dbh);
	$row_form = la_do_fetch_result($sth_form);
	if($row_form) {
		$clean_form_name = preg_replace("/[^A-Za-z0-9_-]/","", $row_form['form_name']);
	} else {
		$error_message = "Invaild form ID.";
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->message = $error_message;
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}
}

$form_ids = array($form_id);
//get sub form IDs
$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' AND element_default_value != ? ORDER BY element_position ASC";
$sth = la_do_query($query, array($form_id, ""), $dbh);
while($row = la_do_fetch_result($sth)){
	array_push($form_ids, (int) $row['element_default_value']);
}

$selected_entries = la_sanitize($_POST['selected_entries']);

//get form structure
$form_structure = getFormStructure($dbh, $form_ids);
$export_data = array();
$files_data = array();

//get entry data including cascaded sub forms
foreach ($selected_entries as $row) {
	$company_id = $row['company_id'];
	$entry_id = $row['entry_id'];
	//get company emails
	$query_company = "SELECT contact_email FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
	$sth_company = la_do_query($query_company, array($company_id), $dbh);
	$row_company = la_do_fetch_result($sth_company);
	if(!empty($row_company['contact_email'])) {
		$company_name = $row_company['contact_email'];
	} else {
		$company_name = "ADMINISTRATOR";
	}
	
	//get entry data
	$entry_data = getEntryData($dbh, $form_ids, $company_id, $entry_id);

	//get element status indicators
	$status_indicators = getStatusIndicators($dbh, $form_ids, $company_id, $entry_id);

	//get synced uploaded files
	$synced_files = getSyncedFiles($dbh, $form_ids, $company_id);

	//get a list of individual synced uploaded files and add it to $files_data array
	foreach ($synced_files as $key => $value) {
		foreach (json_decode($value['files_data']) as $file) {
			array_push($files_data, array("synced" => 1, "file_name" => $file, "element_machine_code" => $value['element_machine_code']));
		}
	}

	//get normal files
	$normal_files = getNormalFiles($dbh, $form_ids, $company_id, $entry_id);
	$files_data = array_merge($files_data, $normal_files);

	array_push($export_data, array(
		"company_email" => $company_name,
		"entry_data" => $entry_data,
		"status_indicators" => $status_indicators,
		"synced_files" => $synced_files
	));
}

//generate a zip file that includes all entry data and files

$zip = NULL;
$zip_name = '';
$export_link = '';
$createTime = time();
if(is_dir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}") === false){
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}", 0777, true);
}
if(is_dir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files") === false){
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files", 0777, true);
}
if(extension_loaded('zip')){
	$zip = new ZipArchive();
	$zip_name = $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/entries_backup_{$clean_form_name}_{$createTime}.zip";
	$export_link = "data/form_{$form_id}/files/entries_backup_{$clean_form_name}_{$createTime}.zip";
	if($zip->open($zip_name, ZIPARCHIVE::CREATE) !== TRUE){
		$error_message = "A zip cannot be created.";
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->message = $error_message;
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}
} else {
	$error_message = "zip cannot be created.";
	$response_data = new stdClass();
	$response_data->status = "error";
	$response_data->message = $error_message;
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
}

//generate form ids JSON file including cascades sub forms
$fp_form_ids = fopen($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_ids.json", 'w');
fwrite($fp_form_ids, json_encode($form_ids));
fclose($fp_form_ids);
$zip->addFile($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_ids.json", "form_ids.json");

//generate form structure JSON file including cascades sub forms
$fp_form_structure = fopen($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_structure.json", 'w');
fwrite($fp_form_structure, json_encode($form_structure));
fclose($fp_form_structure);
$zip->addFile($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_structure.json", "form_structure.json");

//generate file data JSON file
$fp_file = fopen($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/files_data.json", 'w');
fwrite($fp_file, json_encode($files_data));
fclose($fp_file);
$zip->addFile($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/files_data.json", "files_data.json");

//generate export data JSON file
$fp_export_data = fopen($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/export_data.json", 'w');
fwrite($fp_export_data, json_encode($export_data));
fclose($fp_export_data);
$zip->addFile($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/export_data.json", "export_data.json");

//add files to zip
foreach ($files_data as $file) {
	if($file["synced"] == 1) {
		$destination_file = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$file['element_machine_code']}/{$file['file_name']}";
	} else {
		$destination_file = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$file['form_id']}/files/{$file['file_name']}";
	}
	if(file_exists($destination_file)) {
		$zip->addFile($destination_file, $file['file_name']);
	}
}
$zip->close();
if ($_POST['save_entries_to_server'] == 1) {
	// create table in database ap_form_{$form_id}_saved_entries if not exists
	$queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$form_id}_saved_entries'";
	$resultFormTable = la_do_query($queryFormTable, array(), $dbh);
	$rowFormTable    = la_do_fetch_result($resultFormTable);
	if($rowFormTable) {
		// insert into table
		$dbh->query("INSERT INTO ap_form_{$form_id}_saved_entries (pathtofile) VALUES ('$zip_name')");
	} else {
		// create table
		$dbh->query("CREATE TABLE ap_form_{$form_id}_saved_entries (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, pathtofile VARCHAR(300) NOT NULL)");
		
		// insert into table
		$dbh->query("INSERT INTO ap_form_{$form_id}_saved_entries (pathtofile) VALUES ('$zip_name')");
	}
}
unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_structure.json");
unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/files_data.json");
unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/export_data.json");
unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/form_ids.json");
$response_data = new stdClass();
$response_data->status = "success";
$response_data->export_link = $export_link;
$response_json = json_encode($response_data);
echo $response_json;
exit();