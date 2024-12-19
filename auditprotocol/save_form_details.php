<?php
	require('includes/init.php');

	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/common-validator.php');
	require('includes/filter-functions.php');
	require_once("../itam-shared/includes/helper-functions.php");


	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check for max_input_vars
	la_init_max_input_vars();

	$element_properties_array  = la_sanitize($_POST['ep']);
	$form_id				   = (int) la_sanitize($_POST['form_id']);
	$form_properties		   = la_sanitize($_POST['fp']);
	$last_pagebreak_properties = la_sanitize($_POST['lp']);
	$pull_from_form = la_sanitize($_POST['pull_from_form']);

	/******************14-oct-2014************************/
	$form_input = array();
	/*****************************************************/
	parse_str($_POST['el_pos'], $el_pos);
	$element_positions = $el_pos; //contain the positions of the elements
	//print_r($element_positions);die;
	unset($el_pos);

	if($form_properties['active'] == 2){
		$is_new_form = true;
	}else{
		$is_new_form = false;
	}

	//if automapping is enabled, update machine code for all entries
	if($form_properties['enable_auto_mapping'] == 1){
		update_machine_codes($dbh, $form_id, $form_properties['for_selected_company']);
	}

	// This code will delete old css file
	if(file_exists($la_settings['data_dir']."/form_{$form_id}/css/view.css")){
		unlink($la_settings['data_dir']."/form_{$form_id}/css/view.css");
	}
	// need to update css file for rich text feature
	copy("./view.css",$la_settings['data_dir']."/form_{$form_id}/css/view.css");

	// generate column if not exists
	$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$form_id}' AND `COLUMN_NAME` = 'submitted_from'";
	$sth = la_do_query($query,array(),$dbh);
	$row = la_do_fetch_result($sth);
	if(!$row){
		$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD `submitted_from` int(1) NOT NULL DEFAULT '1' AFTER `unique_row_data`, ADD `other_info` TEXT NOT NULL AFTER `submitted_from`, ADD UNIQUE (`unique_row_data`);";
		la_do_query($query,array(),$dbh);
	}

	foreach ($form_properties as $key => $value){

		if($key == 'schedule_start_hour' || $key == 'schedule_end_hour'){

			$exploded = array();
			$exploded = explode(':', $value);

			$hour_value   = $exploded[0];
			$minute_value = $exploded[1];
			$am_pm_value  = $exploded[2];

			$value = date("H:i:s",strtotime("{$hour_value}:{$minute_value} {$am_pm_value}"));
		}

		if($key == 'for_selected_entity'){
			// do nothing
			continue;
		}

		if($key == 'folder_id'){
			// do nothing
			continue;
		}

		$form_input['form_'.$key] = $value;

		if($key == 'redirect'){
			if(!empty($value)){
				if (strpos($value, 'http://') !== false) {

				}elseif (strpos($value, 'https://') !== false){

				}else{
					$form_input['form_'.$key] = "http://{$value}";
				}
			}
		}
	}

	la_ap_forms_update($form_id,$form_input,$dbh);
	$query = "DELETE FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ?;";
	la_do_query($query, array($form_id), $dbh);

	if(isset($form_properties['for_selected_entity'])){
		if(count($form_properties['for_selected_entity'])){
			foreach($form_properties['for_selected_entity'] as $v){
				$query = "INSERT INTO `".LA_TABLE_PREFIX."entity_form_relation` (`entity_form_relation`, `entity_id`, `form_id`) VALUES (NULL, ?, ?);";
				la_do_query($query, array($v, $form_id), $dbh);
			}
		}
	}

	// add/update automatic entry
	if( !empty($pull_from_form) && !empty($form_properties['for_selected_company']) ) {
		addAutomaticEntryPullFrom($dbh, $form_id, $pull_from_form, $form_properties['for_selected_company']);
	} else if (!empty($form_properties['for_selected_company'])) {
		addAutomaticEntry($dbh, $form_id, $form_properties['for_selected_company']);
	}

	$response_data = new stdClass();

	$response_data->status    	= "ok";
	$response_data->form_id    	= $form_id;
	$response_data->csrf_token  = $_SESSION['csrf_token'];

	$response_json 				= json_encode($response_data);

	echo $response_json;
	exit();
