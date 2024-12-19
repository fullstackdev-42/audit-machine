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
	require('includes/users-functions.php');
	require('includes/filter-functions.php');
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->message = "You don't have permission to create reports.";
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}
	
	if($_POST["action"] == "create_report"){
		$form_array = explode("|", $_POST["form_array"]);
		$start_date = strtotime($_POST['start_date']);
		$completion_date = strtotime($_POST['completion_date']);
		$report_type = $_POST["report-type"];
		$company_id = $_POST["company_id"];
		$display_type = $_POST["display_type"];
		$field_label = $_POST["field_label"];
		$math_functions = $_POST["math_functions"];
		$error_status = true;
		$error_message = "";

		if($report_type == "audit-dashboard") {
			$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
			la_do_query($insert_report_query, array(0, 0, $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
		} else if($report_type == "artifact-management") {
			$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
			la_do_query($insert_report_query, array(0, 0, $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
		} else if($report_type == "compliance-dashboard") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "executive-overview") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "field-data") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							if(isset($field_label) && count($field_label) > 0){
								$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
								la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $math_functions, $start_date, $completion_date, time(), 0), $dbh);
								$report_id = $dbh->lastInsertId();

								foreach($field_label as $key => $label){
									$labelArr = explode("|=|", $label);
									$insert_report_element_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report_elements` (`report_element_id`, `report_id`, `form_id`, `element_id`, `element_type`) VALUES (NULL, ?, ?, ?, ?)";
									la_do_query($insert_report_element_query, array($report_id, $form_array[0], $labelArr[0], $labelArr[1]), $dbh);
								}
							} else {
								$error_status = false;
								$error_message = "Please select the form data.";
							}
						}
					} else {
						//check if the selected forms are identical or not
						$formElementArr = array();
						$identical = true;
						$identicalMsg = "";
						foreach($form_array as $key => $form_id){
							$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
							$query_result = la_do_query($field_query, array($form_id), $dbh);
							$formElementArr[$form_id] = array();
							while($field_row = la_do_fetch_result($query_result)){
								array_push($formElementArr[$form_id], array('element_id' => $field_row['element_id'], 'element_type' => $field_row['element_type']));
							}
						}
						foreach($form_array as $key => $formId){
							if(!isset($form_array[($key+1)]) && $form_array[($key+1)] === null){
								break;
							}
							if($formElementArr[$form_array[$key]] === $formElementArr[$form_array[($key+1)]]) {
								//echo 'matched';
							}else{
								$identical = false;
								$identicalMsg = "Form # {$form_array[$key]} is not identical with Form # {$form_array[($key+1)]}";
								break;
							}
						}
						if($identical) {
							if(isset($field_label) && count($field_label) > 0){
								$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
								la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $math_functions, $start_date, $completion_date, time(), 1), $dbh);
								$report_id = $dbh->lastInsertId();

								for ($i=0; $i < count($form_array); $i++) { 
									$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
									la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
									foreach($field_label as $key => $label){
										$labelArr = explode("|=|", $label);
										$insert_report_element_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report_elements` (`report_element_id`, `report_id`, `form_id`, `element_id`, `element_type`) VALUES (NULL, ?, ?, ?, ?)";
										la_do_query($insert_report_element_query, array($report_id, $form_array[$i], $labelArr[0], $labelArr[1]), $dbh);
									}
								}
							} else {
								$error_status = false;
								$error_message = "Please select the form data.";
							}
						} else {
							$error_status = false;
							$error_message = $identicalMsg;
						}
					}
				}
			}
		} else if($report_type == "field-note") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "maturity") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "risk") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "status-indicator") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
							la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
						}
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
						$report_id = $dbh->lastInsertId();
						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "template-code") {
			if(empty($form_array) || count($form_array) == 0) {
				$error_status = false;
				$error_message = "Please select forms.";
			} else {
				if(count($form_array) == 1) {
					if($form_array[0] == 0){
						$error_status = false;
						$error_message = "Please select forms.";
					} else {
						$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
						la_do_query($insert_report_query, array(0, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0), $dbh);
					}
				} else {
					$insert_report_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report` (`report_id`, `company_id`, `form_id`, `display_type`, `math_function`, `start_date`, `completion_date`, `report_created_on`, `multiple_form_report`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
					la_do_query($insert_report_query, array(0, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1), $dbh);
					$report_id = $dbh->lastInsertId();
					for ($i=0; $i < count($form_array); $i++) { 
						$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
						la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
					}
				}
			}
		} else {
			$error_status = false;
			$error_message = "Please select a report type.";
		}
		$response_data = new stdClass();
		if($error_status) {
			$_SESSION['LA_SUCCESS'] = 'New report has been created.';
			$response_data->status = "success";
		} else {
			$response_data->status = "error";
			$response_data->message = $error_message;
		}
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	} else if($_POST["action"] == "edit_report"){
		$report_id = $_POST["report_id"];

		$delete_multiple_report_query = "DELETE FROM `".LA_TABLE_PREFIX."form_multiple_report` WHERE `report_id`=?";
		la_do_query($delete_multiple_report_query, array($report_id), $dbh);

		$delete_field_data_query = "DELETE FROM `".LA_TABLE_PREFIX."form_report_elements` WHERE `report_id`=?";
		la_do_query($delete_field_data_query, array($report_id), $dbh);

		$form_array = explode("|", $_POST["form_array"]);
		$start_date = strtotime($_POST['start_date']);
		$completion_date = strtotime($_POST['completion_date']);
		$report_type = $_POST["report-type"];
		$company_id = $_POST["company_id"];
		$display_type = $_POST["display_type"];
		$field_label = $_POST["field_label"];
		$math_functions = $_POST["math_functions"];
		$error_status = true;
		$error_message = "";

		if($report_type == "audit-dashboard") {
			$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
			la_do_query($update_report_query, array(0, 0, $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
		} else if($report_type == "artifact-management") {
			$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
			la_do_query($update_report_query, array(0, 0, $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
		} else if($report_type == "compliance-dashboard") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "executive-overview") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "field-data") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							if(isset($field_label) && count($field_label) > 0){
								$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
								la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $math_functions, $start_date, $completion_date, time(), 0, $report_id), $dbh);

								foreach($field_label as $key => $label){
									$labelArr = explode("|=|", $label);
									$insert_report_element_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report_elements` (`report_element_id`, `report_id`, `form_id`, `element_id`, `element_type`) VALUES (NULL, ?, ?, ?, ?)";
									la_do_query($insert_report_element_query, array($report_id, $form_array[0], $labelArr[0], $labelArr[1]), $dbh);
								}
							} else {
								$error_status = false;
								$error_message = "Please select the form data.";
							}
						}
					} else {
						//check if the selected forms are identical or not
						$formElementArr = array();
						$identical = true;
						$identicalMsg = "";
						foreach($form_array as $key => $form_id){
							$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
							$query_result = la_do_query($field_query, array($form_id), $dbh);
							$formElementArr[$form_id] = array();
							while($field_row = la_do_fetch_result($query_result)){
								array_push($formElementArr[$form_id], array('element_id' => $field_row['element_id'], 'element_type' => $field_row['element_type']));
							}
						}
						foreach($form_array as $key => $formId){
							if(!isset($form_array[($key+1)]) && $form_array[($key+1)] === null){
								break;
							}
							if($formElementArr[$form_array[$key]] === $formElementArr[$form_array[($key+1)]]) {
								//echo 'matched';
							}else{
								$identical = false;
								$identicalMsg = "Form # {$form_array[$key]} is not identical with Form # {$form_array[($key+1)]}";
								break;
							}
						}
						if($identical) {
							if(isset($field_label) && count($field_label) > 0){
								$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
								la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $math_functions, $start_date, $completion_date, time(), 1, $report_id), $dbh);

								for ($i=0; $i < count($form_array); $i++) { 
									$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
									la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
									foreach($field_label as $key => $label){
										$labelArr = explode("|=|", $label);
										$insert_report_element_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report_elements` (`report_element_id`, `report_id`, `form_id`, `element_id`, `element_type`) VALUES (NULL, ?, ?, ?, ?)";
										la_do_query($insert_report_element_query, array($report_id, $form_array[$i], $labelArr[0], $labelArr[1]), $dbh);
									}
								}
							} else {
								$error_status = false;
								$error_message = "Please select the form data.";
							}
						} else {
							$error_status = false;
							$error_message = $identicalMsg;
						}
					}
				}
			}
		} else if($report_type == "field-note") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "maturity") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "risk") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "status-indicator") {
			if(empty($company_id)) {
				$error_status = false;
				$error_message = "Please select an entity for the entry datasource.";
			} else {
				if(empty($form_array) || count($form_array) == 0) {
					$error_status = false;
					$error_message = "Please select forms.";
				} else {
					if(count($form_array) == 1) {
						if($form_array[0] == 0){
							$error_status = false;
							$error_message = "Please select forms.";
						} else {
							$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
							la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
						}
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array($company_id, $form_array[0], $display_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

						for ($i=0; $i < count($form_array); $i++) { 
							$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
							la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
						}
					}
				}
			}
		} else if($report_type == "template-code") {
			if(empty($form_array) || count($form_array) == 0) {
				$error_status = false;
				$error_message = "Please select forms.";
			} else {
				if(count($form_array) == 1) {
					if($form_array[0] == 0){
						$error_status = false;
						$error_message = "Please select forms.";
					} else {
						$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
						la_do_query($update_report_query, array(0, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 0, $report_id), $dbh);
					}
				} else {
					$update_report_query = "UPDATE `".LA_TABLE_PREFIX."form_report` SET `company_id`=?, `form_id`=?, `display_type`=?, `math_function`=?, `start_date`=?, `completion_date`=?, `report_created_on`=?, `multiple_form_report`=? WHERE `report_id`=?";
					la_do_query($update_report_query, array(0, $form_array[0], $report_type, $report_type, $start_date, $completion_date, time(), 1, $report_id), $dbh);						

					for ($i=0; $i < count($form_array); $i++) { 
						$multiple_query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_multiple_report` (`multiple_report_id`, `report_id`, `form_id`) VALUES (NULL, ?, ?);";
						la_do_query($multiple_query_insert, array($report_id, $form_array[$i]), $dbh);
					}
				}
			}
		} else {
			$error_status = false;
			$error_message = "Please select a report type.";
		}
		$response_data = new stdClass();
		if($error_status) {
			$_SESSION['LA_SUCCESS'] = 'Report has been edited.';
			$response_data->status = "success";
		} else {
			$response_data->status = "error";
			$response_data->message = $error_message;
		}
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	} else if($_POST["mode"] == "send_field_note_report") {
		$report_id = $_POST["report_id"];
		$frequency_type = $_POST["frequency_type"];
		$frequency_date = $_POST["frequency_date"];
		$frequency_weekly = $_POST["frequency_weekly"];
		$frequency_date_pick = $_POST["frequency_date_pick"];
		$frequency_quaterly = $_POST["frequency_quaterly"];
		$frequency_annually = $_POST["frequency_annually"];
		$following_up_days = $_POST["following_up_days"];
		if($frequency_date == "") {
			$frequency_date = 0;
		} else {
			$frequency_date = strtotime($frequency_date);
		}
		if($following_up_days == "") {
			$following_up_days = 0;
		}
		$recipients = $_POST["recipients"];

		$query = "UPDATE ".LA_TABLE_PREFIX."form_report SET `frequency_type`=?, `frequency_date`=?, `frequency_weekly`=?, `frequency_date_pick`=?, `frequency_quaterly`=?, `frequency_annually`=?, `following_up_days`=?, `recipients`=? WHERE `report_id`=?";
		la_do_query($query, array($frequency_type, $frequency_date, $frequency_weekly, $frequency_date_pick, $frequency_quaterly, $frequency_annually, $following_up_days, $recipients, $report_id), $dbh);
		echo "success";
		exit();
	} else {
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->message = "You don't have permission to create reports.";
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}