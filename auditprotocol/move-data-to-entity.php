<?php
require('includes/init.php');
	
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/filter-functions.php');
require('includes/language.php');
require('includes/view-functions.php');
require('includes/users-functions.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

if(isset($_POST['form_id'])){
	$post_form_id = (int) la_sanitize($_REQUEST['form_id']);
	$company_id = (int) la_sanitize($_REQUEST['company_id']);
	$entity_id = (int) la_sanitize($_REQUEST['entity_id']);
	$entry_id = (int) la_sanitize($_REQUEST['entry_id']);
	
	//get sub form IDs
	$form_ids = array($post_form_id);
	$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' AND element_default_value != ? ORDER BY element_position ASC";
	$sth = la_do_query($query, array($post_form_id, ""), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($form_ids, (int) $row['element_default_value']);
	}
	foreach ($form_ids as $form_id) {
		$query = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
		$result = la_do_query($query, array($company_id, $entry_id), $dbh);
		$row = la_do_fetch_result($result);
		if ($row) {
			$query1 = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_forms` WHERE `form_id` = ? AND `client_id` = ?";
			$sth1 = la_do_query($query1, array($form_id, $entity_id), $dbh);
			$row1 = la_do_fetch_result($sth1);
			if(!$row1){
				la_do_query("INSERT INTO `".LA_TABLE_PREFIX."ask_client_forms` (`registration_id`, `client_id`, `form_id`) VALUES (NULL, ?, ?)", array(
					$entity_id, 
					$form_id
				), $dbh);
			}
			$query1 = "SELECT * FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ? AND `entity_id` = ?";
			$sth1 = la_do_query($query1, array($form_id, $entity_id), $dbh);
			$row1 = la_do_fetch_result($sth1);
			if(!$row1){
				la_do_query("INSERT INTO `".LA_TABLE_PREFIX."entity_form_relation` (`entity_form_relation`, `entity_id`, `form_id`) VALUES (NULL, ?, ?)", array($entity_id, $form_id), $dbh);
			}
			//update status indicators
			$query = "UPDATE `".LA_TABLE_PREFIX."element_status_indicator` SET `company_id` = ? WHERE `company_id` = ? AND `form_id` = ? AND `entry_id` = ?";
			la_do_query($query, array($entity_id, $company_id, $form_id, $entry_id), $dbh);		
			
			//update entry data
			$query = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = ? WHERE `company_id` = ? AND `entry_id` = ?";
			la_do_query($query, array($entity_id, $company_id, $entry_id), $dbh);
			
			$query = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET unique_row_data = CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`) WHERE `company_id` = ? AND `entry_id` = ?";
			la_do_query($query, array($entity_id, $entry_id), $dbh);

			//update form_element_note table and update company_id
			$query = "UPDATE `".LA_TABLE_PREFIX."form_element_note` SET `company_id` = ? WHERE `form_id` = ? and `company_id` = ?";
			la_do_query($query, array($entity_id, $form_id, $company_id), $dbh);

			//update ap_form_approval_logic_entry_data
			$query = "UPDATE `".LA_TABLE_PREFIX."form_approval_logic_entry_data` SET `company_id` = ? WHERE `form_id` = ? and `company_id` = ?";
			la_do_query($query, array($entity_id, $form_id, $company_id), $dbh);

			//update ap_form_approval_logic_entry_data
			$query = "UPDATE `".LA_TABLE_PREFIX."form_approvals` SET `company_id` = ? WHERE `form_id` = ? and `company_id` = ?";
			la_do_query($query, array($entity_id, $form_id, $company_id), $dbh);
			
			//update template document
			$query = "UPDATE `".LA_TABLE_PREFIX."template_document_creation` SET `company_id` = ? WHERE `company_id` = ? AND `form_id` = ? AND `entry_id` = ?";
			la_do_query($query, array($entity_id, $company_id, $form_id, $entry_id), $dbh);
			
			//update form submission
			$query = "UPDATE `".LA_TABLE_PREFIX."form_submission_details` SET `company_id` = ? WHERE `company_id` = ? and `form_id` = ?";
			la_do_query($query, array($entity_id, $company_id, $form_id), $dbh);
		}

		//move synced files
		$query_synced_files = "SELECT f.id FROM `".LA_TABLE_PREFIX."file_upload_synced` AS f LEFT JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.element_machine_code = e.element_machine_code WHERE e.form_id = ? AND e.element_file_upload_synced = ? AND f.company_id = ?";
		$sth_synced_files = la_do_query($query_synced_files, array($form_id, 1, $company_id), $dbh);
		while($row_synced_file = la_do_fetch_result($sth_synced_files)) {
			$query_update_synced_file = "UPDATE `".LA_TABLE_PREFIX."file_upload_synced` SET `company_id` = ? WHERE `id` = ?";
			la_do_query($query_update_synced_file, array($entity_id, $row_synced_file["id"]), $dbh);
		}
	}
	echo "SUCCESS";
}