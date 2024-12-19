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
require('includes/entry-functions.php');

$dbh = la_connect_db();

$from = base64_decode($_POST["from"]);
$receiver = base64_decode($_POST["receiver"]);
$receiver_ip_address = base64_decode($_POST["receiver_ip_address"]);
$post_form_name = base64_decode($_POST["form_name"]);
$post_form_structure = json_decode(base64_decode($_POST["form_structure"]), true);
$post_key = base64_decode($_POST["key"]);

$response_data = new stdClass();

$query_settings = "SELECT f.form_id, m.admin_id FROM ".LA_TABLE_PREFIX."forms AS f INNER JOIN ".LA_TABLE_PREFIX."form_migration_wizard_settings AS m ON f.form_id = m.form_id WHERE f.form_name = ? AND m.target_url = ? AND m.key = ? AND m.connector_role = ?";
$sth_settings = la_do_query($query_settings, array($post_form_name, $from, $post_key, 0), $dbh);
$row_settings = la_do_fetch_result($sth_settings);
if($row_settings) {
	$form_id = (int) $row_settings["form_id"];
	$admin_id = (int) $row_settings["admin_id"];

	$form_ids = array($form_id);
	//get sub form IDs
	$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' AND element_default_value != ? ORDER BY element_position ASC";
	$sth = la_do_query($query, array($form_id, ""), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($form_ids, (int) $row['element_default_value']);
	}
	//get form structure
	$form_structure = getFormStructure($dbh, $form_ids);
	if($post_form_structure === $form_structure) {
		$companyIDs = array();

		$query1  = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id}";
		$params = array();
		$sth1 = la_do_query($query1,$params,$dbh);
		while($row1 = la_do_fetch_result($sth1)){
			array_push($companyIDs, $row1['company_id']);
		}
		if(count($companyIDs) > 0) {
			$export_data = array();
			$files_data = array();

			//get entry data including cascaded sub forms
			foreach ($companyIDs as $company_id) {
				//get company emails
				$query_company = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
				$sth_company = la_do_query($query_company, array($company_id), $dbh);
				$row_company = la_do_fetch_result($sth_company);
				if($row_company) {
					$company_name = $row_company['company_name'];
					$company_email = $row_company['contact_email'];
				} else {
					$company_name = "ADMINISTRATOR";
					$company_email = "ADMINISTRATOR";
				}
				
				//get entry data
				$entry_data = getEntryData($dbh, $form_ids, $company_id);

				//get element status indicators
				$status_indicators = getStatusIndicators($dbh, $form_ids, $company_id);

				//get synced uploaded files
				$synced_files = getSyncedFiles($dbh, $form_ids, $company_id);

				//get a list of individual synced uploaded files and add it to $files_data array
				foreach ($synced_files as $key => $value) {
					foreach (json_decode($value['files_data']) as $file) {
						array_push($files_data, array("synced" => 1, "file_name" => $file, "element_machine_code" => $value['element_machine_code']));
					}
				}
				array_push($export_data, array(
					"company_name" => $company_name,
					"company_email" => $company_email,
					"entry_data" => $entry_data,
					"status_indicators" => $status_indicators,
					"synced_files" => $synced_files
				));
			}

			//get a list of individual normally uploaded files and add it to $files_data array
			$normal_files = getNormalFiles($dbh, $form_ids, $companyIDs);
			$files_data = array_merge($files_data, $normal_files);

			//save the action in the audit logs
			$action_text = $receiver." in ".$from." migrated entry data.";
			$query_audit_log = "INSERT INTO `".LA_TABLE_PREFIX."audit_log`(`user_id`, `form_id`, `action_type_id`, `action_text`, `user_ip`, `action_datetime`) VALUES (?,?,?,?,?,?)";
			la_do_query($query_audit_log, array($admin_id, $form_id, 18, $action_text, $receiver_ip_address, time()), $dbh);

			$response_data->status = "ok";
			$response_data->form_ids = $form_ids;
			$response_data->export_data = $export_data;
			$response_data->files_data = $files_data;
		} else {
			$response_data->status = "error";
			$response_data->msg = "The form you are attempting to migrate entry data from is empty.";
		}
	} else {
		$response_data->status = "error";
		$response_data->msg = "The form structures are not identical.";
	}
} else {
	$response_data->status = "error";
	$response_data->msg = "The system you are attempting to migrate data from doesn't have any available forms that are set to allow the migration of entry data.";
}

$response_json = json_encode($response_data);
echo $response_json;
exit();