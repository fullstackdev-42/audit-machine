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
	$deleteEntries = la_sanitize($_POST['deleteEntries']);


$element_properties_array  = la_sanitize($_POST['ep']);


$get_upload_sync_ids = "select id from ap_form_elements
INNER JOIN ap_forms ON ap_forms.form_id = ap_form_elements.form_id
 where ap_form_elements.form_id = ? AND element_machine_code != '' AND element_type = 'file'
 AND ap_forms.form_enable_auto_mapping = 1";
$params = array($form_id);

$sth_1 = la_do_query($get_upload_sync_ids,$params,$dbh);
$ids_to_edit = array();
while($row = la_do_fetch_result($sth_1)) {

      array_push($ids_to_edit, $row['id']);

}
$ids_to_edit_string = implode(',',$ids_to_edit);

if ($ids_to_edit_string != "" && $ids_to_edit_string != NULL) {
    $do_query = "UPDATE ap_form_elements  SET  element_file_upload_synced = 1 WHERE id IN ($ids_to_edit_string)";
    la_do_query($do_query, array(), $dbh);
}

	/******************14-oct-2014************************/
	$form_input = array();
	/*****************************************************/
	parse_str($_POST['el_pos'], $el_pos);
	$element_positions = $el_pos['el_pos']; //contain the positions of the elements
	//print_r($element_positions);die;
	unset($el_pos);

	/***************************************************************************************************************/
	/* 1. Process form properties																			   	   */
	/***************************************************************************************************************/

	if($form_properties['active'] == 2){
		$is_new_form = true;
	}else{
		$is_new_form = false;
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

	//wrong form_page_total here
	//print_r($_POST);
	// print_r($form_input);
	// die;

	//If this is new form, create the form table and form folder+css
   	if($is_new_form){
   		//check user privileges, is this user has privilege to create new form?
		if(empty($_SESSION['la_user_privileges']['priv_new_forms'])){
			die('{ "status" : "error","message" : "Access Denied. You don\'t have permission to create new forms."}');
		}

		//get default form theme
		$query_save = "SELECT * FROM ".LA_TABLE_PREFIX."settings";
		$query = "SELECT default_form_theme_id FROM ".LA_TABLE_PREFIX."settings";
		$sth = la_do_query($query,array(),$dbh);
		$row = la_do_fetch_result($sth);
		$default_form_theme_id = (int) $row['default_form_theme_id'];


   		//update form status to 1 and set default theme
   		$form_input['form_active'] = 1;
   		$form_input['form_theme_id'] = $default_form_theme_id;

		la_ap_forms_update($form_id,$form_input,$dbh);


		//create new table for this form
		$query = "CREATE TABLE IF NOT EXISTS `".LA_TABLE_PREFIX."form_{$form_id}` (
  													`id` int(11) NOT NULL auto_increment,
  													`company_id` int(11) NOT NULL,
  													`entry_id` int(11) NOT NULL,
  													`field_name` varchar(200) NOT NULL,
  													`field_code` varchar(50) NOT NULL,
  													`data_value` longtext NOT NULL,
  													`field_score` text NOT NULL,
													`form_resume_enable` int(11) NOT NULL,
													`unique_row_data` varchar(64) NOT NULL,
													`submitted_from` int(1) NOT NULL,
													`other_info` text NOT NULL,
													`element_machine_code` varchar(100) NULL,
  													PRIMARY KEY (`id`),
													UNIQUE KEY `unique_row_data` (`unique_row_data`)
  													) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		$params = array();
		la_do_query($query,$params,$dbh);

		// add user activity to log: activity - 1 (CREATED)
		$session_time = sessionTime($dbh, $_SESSION['la_user_id']);
		addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 1, "Session Time {$session_time}", time());

		/*$query = "CREATE TABLE IF NOT EXISTS `".LA_TABLE_PREFIX."form_{$form_id}_review` (
												`id` int(11) NOT NULL auto_increment,
												`form_id` int(11) NOT NULL ,
												`company_id` int(11) NOT NULL ,
												`email_address` varchar(100) NOT NULL,
												`ip_address` varchar(15) NOT NULL,
												`status` int(11) NOT NULL ,
												`date_created` datetime NOT NULL default '0000-00-00 00:00:00',
												`date_updated` datetime NOT NULL default '0000-00-00 00:00:00',
												`session_id`varchar(64) NOT NULL,
												PRIMARY KEY (`id`)
												) DEFAULT CHARACTER SET utf8;";

	   la_do_query($query,array(),$dbh);*/

		/*******************************************************************/
		/**                                                               **/
		/*******************************************************************/

		//the 'status' column on the form table above has 3 possible values:
		//0 - deleted, 1 - live, 2 - draft/incomplete

		//create data folder for this form
		if(is_writable($la_settings['data_dir'])){

			$old_mask = umask(0);
			mkdir($la_settings['data_dir']."/form_{$form_id}",0777, true);
			mkdir($la_settings['data_dir']."/form_{$form_id}/css",0777, true);
			if($la_settings['data_dir'] != $la_settings['upload_dir']){
				@mkdir($la_settings['upload_dir']."/form_{$form_id}",0777, true);
			}
			mkdir($la_settings['upload_dir']."/form_{$form_id}/files",0777, true);
			@file_put_contents($la_settings['upload_dir']."/form_{$form_id}/files/index.html",' '); //write empty index.html

			//copy default view.css to css folder
			if(copy("./view.css",$la_settings['data_dir']."/form_{$form_id}/css/view.css")){
				//on success update 'form_has_css' field on ap_forms table
				$form_update_input['form_has_css'] = 1;
				la_ap_forms_update($form_id,$form_update_input,$dbh);
			}

			umask($old_mask);
		}

   	}
	else{ //If this is old form, only update ap_forms table

								error_log("OLD FORM");

		if(file_exists($la_settings['data_dir']."/form_{$form_id}/css/view.css")){
			unlink($la_settings['data_dir']."/form_{$form_id}/css/view.css");
		}
		// need to update css file for rich text feature
		copy("./view.css",$la_settings['data_dir']."/form_{$form_id}/css/view.css");

		$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$form_id}' AND `COLUMN_NAME` = 'unique_row_data'";
		$sth = la_do_query($query,array(),$dbh);
   		$row = la_do_fetch_result($sth);

		if(!$row){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD `unique_row_data` VARCHAR(64) NOT NULL AFTER `form_resume_enable`, ADD `submitted_from` int(1) NOT NULL DEFAULT '1' AFTER `unique_row_data`, ADD `other_info` TEXT NOT NULL AFTER `submitted_from`, ADD UNIQUE (`unique_row_data`);";
			la_do_query($query,array(),$dbh);
		}

   		//make sure the form really exist first
   		$query = "select form_id from ".LA_TABLE_PREFIX."forms where form_id = ?";
   		$params = array($form_id);

   		$sth = la_do_query($query,$params,$dbh);
   		$row = la_do_fetch_result($sth);

   		// echo 'in else';
   		// print_r($form_input);
								error_log("1");

   		if(!empty($row)){

   											error_log("2");

	   		$result = la_ap_forms_update($form_id,$form_input,$dbh);
			check_result($result);

			$activity = 2;

			// if form_create_date equals to 0 thats means its an old form
			$form_create_date = checkFormCreateDate($dbh, $form_id);

			if($form_create_date > 0){
				$form_create_date = date("Y-m-d", $form_create_date);

				if($form_create_date == date("Y-m-d")){
					$activity = 1;
				}
			}

			// check form data exist's or not
			if(checkFormData($dbh, $form_id))
				$activity = 4;
   											error_log("3");

			// add user activity to log: activity - 2 or 4 (UPDATED or UPDATE_AND_DELETED_DATA)
			$session_time = sessionTime($dbh, $_SESSION['la_user_id']);
			addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, $activity, "Session Time {$session_time}", time());

		}else{
			die('{ "status" : "error","message" : "Unknown form id"}');
		}

   	}

   	$query = "DELETE FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ?;";
	la_do_query($query, array($form_id), $dbh);

	if(isset($form_properties['for_selected_entity'])){
		if(is_array($form_properties['for_selected_entity']) && count($form_properties['for_selected_entity'])){
			foreach($form_properties['for_selected_entity'] as $v){
				$query = "INSERT INTO `".LA_TABLE_PREFIX."entity_form_relation` (`entity_form_relation`, `entity_id`, `form_id`) VALUES (NULL, ?, ?);";
				la_do_query($query, array($v, $form_id), $dbh);
			}
		}
	}
   											error_log("4");

	$queriesList = array();

	if($deleteEntries == "true") {
		array_push($queriesList, "TRUNCATE TABLE ".LA_TABLE_PREFIX."form_{$form_id}");
	}
   											error_log("5");

	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."form_elements where `form_id` = :form_id and `element_status` = 0");
	array_push($queriesList, "delete from `".LA_TABLE_PREFIX."forms_submission_counter` where `form_id` = :form_id");
	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."form_report where form_id = :form_id");
	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."form_report_elements where form_id = :form_id");
	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."form_multiple_report where form_id = :form_id");
	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."element_status_indicator where form_id = :form_id");
	la_do_query(implode(";", $queriesList), array(':form_id' => $form_id), $dbh);


	/***************************************************************************************************************/
	/* 2. Process fields																					   	   */
	/***************************************************************************************************************/

   	// 2.1 Process new fields
   	// Get the new fields from ap_form_elements table with status = 2, change the status to 1 and create the field column into the form's table

	$matrix_child_array = array();
	$matrix_child_note_array = array();

	$query = "SELECT element_id, element_type, element_constraint, element_position, element_matrix_parent_id, element_matrix_allow_multiselect, element_choice_has_other, element_status, element_note FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id = :form_id and element_type = 'matrix' ORDER BY element_position asc";
	$sth = la_do_query($query, array(
		':form_id' => $form_id
	), $dbh);

	while($row = la_do_fetch_result($sth)){
		if($row['element_matrix_parent_id'] == 0){
			$matrix_multiselect_settings[$row['element_id']] = $row['element_matrix_allow_multiselect'];
		}

		if($row['element_status'] == 2){
			if(!empty($row['element_matrix_parent_id'])){ //if this is the first row of the matrix
				$matrix_child_array[$row['element_matrix_parent_id']][] = $row['element_id'];
			}
		}
	}

	//print_r($matrix_child_array);die;

	$queriesList = array();

	array_push($queriesList, "update `".LA_TABLE_PREFIX."form_elements` set `element_status` = 1 where form_id = :form_id and element_status=2");
	array_push($queriesList, "delete from `".LA_TABLE_PREFIX."element_options` where form_id = :form_id and live=0");
	array_push($queriesList, "update `".LA_TABLE_PREFIX."element_options` set `live` = 1 where form_id = :form_id and live=2");
	la_do_query(implode(";", $queriesList), array(':form_id' => $form_id), $dbh);

	//update matrix 'constraint' with the child ids
	if(!empty($matrix_child_array)){
		$matrixQueriesArr = array();
		$matrixParams = array();
		foreach($matrix_child_array as $m_parent_id=>$m_child_id_array){
			$m_child_id = implode(',',$m_child_id_array);

			array_push($matrixQueriesArr, "update `".LA_TABLE_PREFIX."form_elements` set `element_constraint` = ? where form_id = ? and element_id = ?");
			$matrixParams = array_merge($matrixParams, array($m_child_id,$form_id,$m_parent_id));
		}
		if(is_array($matrixQueriesArr) && count($matrixQueriesArr)){
			$matrixQueries = implode(";", $matrixQueriesArr);
			la_do_query($matrixQueries, $matrixParams, $dbh);
		}
		$matrixQueriesArr = NULL;
		$matrixParams = NULL;
	}

	//2.2 Process old field
	//Get the old fields parameters from the ajax post

	$matrix_child_array = array();

	//loop through each element properties
	//set the live property of the options within ap_element_options to 0

	if(is_array($element_properties_array) && count($element_properties_array)){
		$elementUpdateQueries = array();
		$elementUpdateParams = array();
		$multipleChoicesId = array();
		$matrixChoicesId = array();

		foreach($element_properties_array as $element_properties){
			$element_type = $element_properties['type'];
			$element_id	  = $element_properties['id'];

			if(in_array($element_type, array('radio', 'checkbox', 'select', 'matrix'))){
				if($element_type == "matrix"){
					$tmpArray = explode(',', $element_properties['constraint']);

					$multipleChoicesId = array_merge($multipleChoicesId, $tmpArray);
					array_push($multipleChoicesId, $element_id);

					$matrixChoicesId = array_merge($matrixChoicesId, $tmpArray);
					array_push($matrixChoicesId, $element_id);
					unset($tmpArray);
				}else{
					array_push($multipleChoicesId, $element_id);
				}
			}
		}

		if(is_array($multipleChoicesId) && count($multipleChoicesId)){
			$in = join(',', array_fill(0, count($multipleChoicesId), '?'));
			la_do_query("UPDATE `".LA_TABLE_PREFIX."element_options` `eo` LEFT JOIN `".LA_TABLE_PREFIX."form_elements` `fe` ON (`eo`.`element_id` = `fe`.`element_id`) SET `eo`.`live` = 0 WHERE `eo`.`form_id` = ? AND `eo`.`element_id` IN ({$in})", array_merge(array($form_id), $multipleChoicesId), $dbh);
			unset($multipleChoicesId);
			unset($in);
		}

		if(is_array($matrixChoicesId) && count($matrixChoicesId)){
			$in = join(',', array_fill(0, count($matrixChoicesId), '?'));
			la_do_query("UPDATE `".LA_TABLE_PREFIX."form_elements` SET `element_status` = 0 WHERE `form_id` = ? AND `element_id` IN ({$in})", array_merge(array($form_id), $matrixChoicesId), $dbh);
			unset($matrixChoicesId);
			unset($in);
		}

		foreach($element_properties_array as $element_properties){
			$element_type = $element_properties['type'];
			$element_id	  = $element_properties['id'];

			unset($element_properties['is_db_live']);
			unset($element_properties['last_option_id']); //this property exist for choices field type

			$element_options = array();
			$element_options = $element_properties['options'];
			unset($element_properties['options']);

			//2.2.1 Synch into ap_element_options table
			//This is only necessary for multiple choice, checkboxes, dropdown and matrix field

			if(in_array($element_type,array('radio','checkbox','select'))){
				//there are 3 possibilities, new choice being added, old choice being deleted, old choice being updated
				//we need to handle all of those. update the ap_element_options and update the form's table as well

				$optionQueriesArr = array();
				$optionQueriesParams = array();

				$insertFlag = false;
				$insertOptionQuery = array();

				foreach($element_options as $option_id => $value){
					if(empty($value['is_db_live'])){ //this is new choice
						if(!$insertFlag){
							$insertFlag = true;
							array_push($insertOptionQuery, "INSERT INTO `".LA_TABLE_PREFIX."element_options` (`form_id`,`element_id`,`option_id`,`position`,`option`,`option_value`,`option_is_default`,`live`,`option_icon_src`) VALUES (?,?,?,?,?,?,?,'1',?)");
						}else{
							array_push($insertOptionQuery, "(?,?,?,?,?,?,?,'1',?)");
						}

						$value['option_value'] = (!empty($value['option_value']) ? $value['option_value'] : 0);
						$optionQueriesParams = array_merge($optionQueriesParams, array($form_id,$element_id,$option_id,$value['position'],$value['option'],$value['option_value'],$value['is_default'],$value['option_icon_src']));
					}
				}

				if(is_array($insertOptionQuery) && count($insertOptionQuery)){
					array_push($optionQueriesArr, implode(",", $insertOptionQuery));
				}

				foreach($element_options as $option_id => $value){
					if(!empty($value['is_db_live'])){ //this is new choice
						array_push($optionQueriesArr, "UPDATE `".LA_TABLE_PREFIX."element_options` SET `live`=1,`option` = ?,`option_value` = ?,`option_is_default` = ?,`position` = ?,`option_icon_src`=? WHERE form_id = ? and element_id = ? and `option_id` = ?");
						$optionQueriesParams = array_merge($optionQueriesParams, array($value['option'],$value['option_value'],$value['is_default'],$value['position'],$value['option_icon_src'],$form_id,$element_id,$option_id));
					}
				}

				if(is_array($optionQueriesArr) && count($optionQueriesArr)){
					la_do_query(implode(";", $optionQueriesArr), $optionQueriesParams, $dbh);
					unset($optionQueriesArr);
					unset($optionQueriesParams);
				}
			}
			else if($element_type == 'matrix'){
				$matrix_all_row_ids = array();
				$matrix_all_row_ids = explode(',',$element_properties['constraint']);
				$matrix_all_row_ids[] = $element_id;

				//process the first row of the matrix
				$first_row_matrix_data = array();
				$first_row_matrix_data = $element_options[$element_properties['id']];

				array_push($elementUpdateQueries, "update `".LA_TABLE_PREFIX."form_elements` set `element_status`=1 where `form_id`=? and `element_id`=?");
				$elementUpdateParams = array_merge($elementUpdateParams, array($form_id, $element_properties['id']));

				//update/insert column data
				$matrix_column_data = array();
				$matrix_column_data = $first_row_matrix_data['column_data'];

				$optionQueriesArr = array();
				$optionQueriesParams = array();

				$insertFlag = false;
				$insertOptionQuery = array();

				foreach($matrix_column_data as $c_option_id => $value){
					if(empty($value['is_db_live'])){
						//this is new column, add the column
						//insert into ap_element_options table, for all rows
						foreach ($matrix_all_row_ids as $m_row_element_id){
							if(!$insertFlag){
								array_push($insertOptionQuery, "INSERT INTO `".LA_TABLE_PREFIX."element_options` (`form_id`,`element_id`,`option_id`,`position`,`option`,`option_value`,`option_is_default`,`live`) VALUES (?,?,?,?,?,?,'0','1')");
								$insertFlag = true;
							}else{
								array_push($insertOptionQuery, "(?,?,?,?,?,?,'0','1')");
							}
							$optionQueriesParams = array_merge($optionQueriesParams, array($form_id,$m_row_element_id,$c_option_id,$value['position'],$value['column_title'],$value['column_score']));
						}
					}
				}

				if(is_array($insertOptionQuery) && count($insertOptionQuery)){
					array_push($optionQueriesArr, implode(",", $insertOptionQuery));
				}

				foreach ($matrix_column_data as $c_option_id => $value){
					if(!empty($value['is_db_live'])){
						array_push($optionQueriesArr, "UPDATE `".LA_TABLE_PREFIX."element_options` SET `live`=1, `position` = ?, `option` = ?, `option_value` = ? WHERE form_id = ? and element_id = ? and `option_id` = ?");
						$optionQueriesParams = array_merge($optionQueriesParams, array($value['position'],$value['column_title'],$value['column_score'],$form_id,$element_properties['id'],$c_option_id));
					}
				}

				//loop through other matrix rows
				foreach($element_options as $m_element_id => $value){
					if($m_element_id == $element_properties['id']){ //if this the first row of the matrix
						continue; //skip first row, we already process it above
					}

					$child_position = $value['position'];
					$matrix_child_array[$element_properties['id']][$child_position] = $m_element_id;

					if(empty($value['is_db_live'])){ //this is new row
						//update the status on ap_form_elements table
						array_push($elementUpdateQueries, "update `".LA_TABLE_PREFIX."form_elements` set `element_status`=1 where form_id = ? and element_id = ?");
						$elementUpdateParams = array_merge($elementUpdateParams, array($form_id,$m_element_id));

						if(!isset($value['column_data'])){
							// this will delete answers added at time of creating questions
							la_do_query("DELETE FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = ? AND `element_id` = ?", array($form_id, $m_element_id), $dbh);

							$insertFlag = false;
							$insertOptionQuery = array();
							$insertOptionParam = array();

							foreach ($matrix_column_data as $c_option_id => $values){
								//this is new column, add the column
								//insert into ap_element_options table, for all rows
								if(!$insertFlag){
									array_push($insertOptionQuery, "INSERT INTO `".LA_TABLE_PREFIX."element_options` (`form_id`,`element_id`,`option_id`,`position`,`option`,`option_value`,`option_is_default`,`live`) VALUES (?,?,?,?,?,?,'0','1')");
									$insertFlag = true;
								}else{
									array_push($insertOptionQuery, "(?,?,?,?,?,?,'0','1')");
								}
								array_push($insertOptionParam, array($form_id,$m_element_id,$c_option_id,$values['position'],$values['column_title'],$values['column_score']));
								$optionQueriesParams = array_merge($optionQueriesParams, array($form_id,$m_element_id,$c_option_id,$values['position'],$values['column_title'],$values['column_score']));
							}

							if(is_array($insertOptionQuery) && count($insertOptionQuery)){
								array_push($optionQueriesArr, implode(",", $insertOptionQuery));
							}

						}
					}
					else{ //this is an existing row, just update
						array_push($elementUpdateQueries, "update `".LA_TABLE_PREFIX."form_elements` set `element_status`=1 where `form_id` = ? and `element_id` = ?");
						$elementUpdateParams = array_merge($elementUpdateParams, array($form_id,$m_element_id));

						foreach ($matrix_column_data as $c_option_id => $values){
							if(!empty($value['is_db_live'])){
								array_push($optionQueriesArr, "UPDATE `".LA_TABLE_PREFIX."element_options` SET `live`=1, `position` = ?, `option` = ?, `option_value` = ? WHERE `form_id` = ? and `element_id` = ? and `option_id` = ?");
								$optionQueriesParams = array_merge($optionQueriesParams, array($values['position'], $values['column_title'], $values['column_score'], $form_id, $m_element_id, $c_option_id));
							}
						}

					}
				}

				if(is_array($optionQueriesArr) && count($optionQueriesArr)){
					la_do_query(implode(";", $optionQueriesArr), $optionQueriesParams, $dbh);
					unset($optionQueriesArr);
					unset($optionQueriesParams);
				}

			}

			//2.2.2 Synch into ap_form_elements table
			$update_values = '';
			$params = array();

			$element_properties['status'] = 1;

			//dynamically create the sql update string, based on the input given
			foreach ($element_properties as $key => $value){
				if( $key == 'element_id_auto' || $key == 'form_id' || $key == 'element_page_number')
					continue;
				
				if( $key == 'video_url' ) {
					if (strpos($value, 'data:') !== false) {
						$value = '';
					}
				}

				if($value == "null"){
					$value = null;
				}

				$update_values .= "`element_{$key}`= ?,";
				$params[] = $value;

			}

			$update_values = rtrim($update_values,',');

			$query = "UPDATE `".LA_TABLE_PREFIX."form_elements` set $update_values where form_id = ? and element_id = ?";
			$params[] = $form_id;
			$params[] = $element_properties['id'];

			array_push($elementUpdateQueries, $query);
			$elementUpdateParams = array_merge($elementUpdateParams, $params);

			//if this is matrix field, the element title need to be updated again from the options, the position as well

									   											error_log("33221");

			if($element_properties['type'] == 'matrix'){
				$query = "UPDATE `".LA_TABLE_PREFIX."form_elements` SET `element_title` = ?, `element_machine_code` = ?, `element_position` = ? WHERE form_id = ? and element_id = ? and element_status=1";

				foreach($element_options as $m_element_id => $value){
					$params = array();
					$params[] = $value['row_title'];
					$params[] = $value['machine_code'];
					$params[] = $value['position'];
					$params[] = $form_id;
					$params[] = $m_element_id;

					array_push($elementUpdateQueries, $query);
					$elementUpdateParams = array_merge($elementUpdateParams, $params);
				}
				//end foreach element_options
			}
						   											error_log("113121");


			if ($element_properties['type'] == 'file') {
								error_log("FILE");
								error_log(form_properties['enable_auto_mapping']);
								error_log($element_properties['machine_code']);



			}
 
		}
		//end foreach element properties

		if(is_array($elementUpdateQueries) && count($elementUpdateQueries)){
			la_do_query(implode(";", $elementUpdateQueries), $elementUpdateParams, $dbh);
			unset($elementUpdateQueries);
			unset($elementUpdateParams);
		}

		//update matrix 'constraint' with the child ids
		if(!empty($matrix_child_array)){
			$matrixQueriesArr = array();
			$matrixQueriesParams = array();

			foreach($matrix_child_array as $m_parent_id => $m_child_id_array){
				ksort($m_child_id_array); //sort the matrix child based on position
				$m_child_id = implode(',',$m_child_id_array);
				array_push($matrixQueriesArr, "update `".LA_TABLE_PREFIX."form_elements` set `element_constraint` = ? where form_id = ? and element_id = ?");
				$matrixQueriesParams = array_merge($matrixQueriesParams, array($m_child_id, $form_id, $m_parent_id));

				array_push($matrixQueriesArr, "UPDATE `".LA_TABLE_PREFIX."form_elements` `t1` SET `t1`.`element_note` = (SELECT `element_note` FROM (SELECT `element_note` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_id` = ?) `t3`) WHERE `t1`.`form_id` = ? AND `t1`.`element_id` IN (".join(',', array_fill(0, count($m_child_id_array), '?')).")");
				$matrixQueriesParams = array_merge($matrixQueriesParams, array_merge(array($form_id, $m_parent_id, $form_id), $m_child_id_array));
			}

			if(is_array($matrixQueriesArr) && count($matrixQueriesArr)){
				la_do_query(implode(";", $matrixQueriesArr), $matrixQueriesParams, $dbh);
				unset($matrixQueriesArr);
				unset($matrixQueriesParams);
			}
		}
	}



	//end !empty element properties

	/***************************************************************************************************************/
	/* 3. Additional calculations on ap_form_elements table														   */
	/***************************************************************************************************************/

	// 3.1 Calculate element positions, each matrix row is considered as separate field

	//first create a list of matrix fields on the current form, get the parent matrix only
	$matrix_parent_constraint = array();
	$query = "SELECT element_id, element_constraint FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id = ? and element_type='matrix' and element_status=1 and element_matrix_parent_id=0 ORDER BY element_position asc";

	$sth = la_do_query($query,array($form_id),$dbh);
	while($row = la_do_fetch_result($sth)){
		$matrix_parent_constraint[$row['element_id']] = trim($row['element_constraint']);
	}

	$element_final_position = array();
	foreach ($element_positions as $element_id){
		$matrix_childs = '';

		$element_final_position[] = $element_id;
		$matrix_childs = $matrix_parent_constraint[$element_id];

		if(!empty($matrix_childs)){
			$matrix_childs_array = array();
			$matrix_childs_array = explode(",",$matrix_childs);

			foreach ($matrix_childs_array as $child_element_id){
				$element_final_position[] = $child_element_id;
			}
		}
	}

	//update position into ap_form_elements table

	$positionQueriesArr = array();
	$positionParams = array();

	//update position into ap_form_elements table
	foreach ($element_final_position as $position => $element_id){
		array_push($positionQueriesArr, "update `".LA_TABLE_PREFIX."form_elements` set element_position = ? where form_id = ? and element_id = ?");
		$positionParams = array_merge($positionParams, array($position, $form_id, $element_id));
	}

	if(is_array($positionQueriesArr) && count($positionQueriesArr)){
		$positionQueries = implode(";", $positionQueriesArr);
		la_do_query($positionQueries, $positionParams,$dbh);
	}

	unset($positionQueriesArr);
	unset($positionParams);

	// 3.2 Calculate element page number
	$query = "SELECT element_id, element_position FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id = ? and element_type='page_break' and element_status=1 ORDER BY element_position asc";
	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
	$page_number = 1;

	while($row = la_do_fetch_result($sth)){
		$page_break_list[$page_number] = $row['element_position'];
		$page_number++;
	}

	$total_page = $page_number;
	//page number is correct here
	if(!empty($page_break_list)){
		krsort($page_break_list);
	}

	//set the page number of all fields to the highest page number
	$query = "UPDATE ".LA_TABLE_PREFIX."form_elements SET element_page_number = ? WHERE form_id = ? and element_status=1";
	$params = array($total_page,$form_id);
	la_do_query($query,$params,$dbh);

	$positionQueriesArr = array();
	$positionParams = array();

	//then loop through each page break and set the page number of all fields below that page break
	if(!empty($page_break_list)){
		foreach ($page_break_list as $page_number=>$position){
			array_push($positionQueriesArr, "UPDATE ".LA_TABLE_PREFIX."form_elements SET element_page_number = ? WHERE form_id = ? and element_status=1 and element_position <= ?");
			$positionParams = array_merge($positionParams, array($page_number,$form_id,$position));
		}
	}

	//3.3 Make sure that all elements which have "range" properties doesn't have "range min" which is greater than "range max"

	array_push($positionQueriesArr, "update ".LA_TABLE_PREFIX."form_elements set element_range_min=0 where form_id = ? and element_range_min > element_range_max and element_range_max > 0");
	$positionParams = array_merge($positionParams, array($form_id));

	if(is_array($positionQueriesArr) && count($positionQueriesArr)){
		$positionQueries = implode(";", $positionQueriesArr);
		la_do_query($positionQueries, $positionParams,$dbh);
	}

	unset($positionQueriesArr);
	unset($positionParams);

	/***************************************************************************************************************/
	/* 4. Additional calculations on ap_forms table														  		   */
	/***************************************************************************************************************/
	$queriesList = array();
	$queriesListParam = array();

	// echo 'total page in update query:-'.$total_page;

	//Set form properties which related with multipage
	if(!empty($last_pagebreak_properties)){
		if($last_pagebreak_properties['submit_primary_img'] === "null"){
			$last_pagebreak_properties['submit_primary_img'] = null;
		}
		if($last_pagebreak_properties['submit_secondary_img'] === "null"){
			$last_pagebreak_properties['submit_secondary_img'] = null;
		}

		$last_pagebreak_properties['submit_use_image'] = (int) $last_pagebreak_properties['submit_use_image'];

		array_push($queriesList, "UPDATE ".LA_TABLE_PREFIX."forms SET form_page_total=?,form_lastpage_title=?,form_submit_primary_text=?, form_submit_secondary_text=?,form_submit_primary_img=?, form_submit_secondary_img=?,form_submit_use_image=?, form_last_page_break_bg_color=? WHERE form_id=?");
		$queriesListParam = array_merge($queriesListParam, array($total_page,$last_pagebreak_properties['page_title'],$last_pagebreak_properties['submit_primary_text'], $last_pagebreak_properties['submit_secondary_text'],$last_pagebreak_properties['submit_primary_img'], $last_pagebreak_properties['submit_secondary_img'],$last_pagebreak_properties['submit_use_image'], $last_pagebreak_properties['page_break_bg_color'], $form_id));

	}
	else if($total_page === 1){//if this is just a single page form
		array_push($queriesList, "update ".LA_TABLE_PREFIX."forms set form_page_total=1 where form_id=?");
		$queriesListParam = array_merge($queriesListParam, array($form_id));
	}
	else{
		array_push($queriesList, "update ".LA_TABLE_PREFIX."forms set form_page_total=? where form_id=?");
		$queriesListParam = array_merge($queriesListParam, array($total_page, $form_id));
	}

	/***************************************************************************************************************/
	/* 5. Process form review (on/off)																			   */
	/***************************************************************************************************************/

	//every time we save the form, the review table will be deleted
	//it needs to be created again when one of the following conditions happened:
	// 1) form review enabled
	// 2) the form has multiple pages
	// 3) the 'save and resume' option of the form is enabled

	//delete review table if exists

	//create review table

	/***************************************************************************************************************/
	/* 6. Insert into permissions table																			   */
	/***************************************************************************************************************/

	if($is_new_form){
		array_push($queriesList, "delete from ".LA_TABLE_PREFIX."permissions where form_id=? and user_id=?");
		$queriesListParam = array_merge($queriesListParam, array($form_id,$_SESSION['la_user_id']));
		array_push($queriesList, "insert into ".LA_TABLE_PREFIX."permissions(form_id,user_id,edit_form,edit_entries,view_entries) values(?,?,1,1,1)");
		$queriesListParam = array_merge($queriesListParam, array($form_id,$_SESSION['la_user_id']));
	}

	/*//Sam commented this
	query fetches all the cascade forms added to parent form but not fetching the pages included in the
	cascade forms.

	$query = "SELECT IFNULL(sum(`f`.`form_page_total`-1), 0) `casecade_form_page_total` FROM `".LA_TABLE_PREFIX."forms` `f` left join `".LA_TABLE_PREFIX."form_elements` `fe` on (`f`.`form_id`=`fe`.`element_default_value`) WHERE `fe`.`form_id`=? and `fe`.`element_type`='casecade_form'";
	// echo '$form_id:-'.$form_id;
	$sth = la_do_query($query,array($form_id),$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row['casecade_form_page_total'])){
		array_push($queriesList, "update ".LA_TABLE_PREFIX."forms set form_page_total=form_page_total+{$row['casecade_form_page_total']} where form_id=?");
		$queriesListParam = array_merge($queriesListParam, array($form_id));
	}
	Sam commented this*/

	// echo 'He is adding one more page for every cascade page';
	// print_r($queriesListParam);
	// print_r($queriesList);

	/***************************************************************************************************************/
	/* 7. if user enable Auto mapping add missing column element_machine_code, and add automatic entry with mapped data*/
	/***************************************************************************************************************/
	if( $form_properties['enable_auto_mapping'] ) {
		$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$form_id}' AND `COLUMN_NAME` = 'element_machine_code';";
		$sth = la_do_query($query,array(),$dbh);
   		$row = la_do_fetch_result($sth);

		if( empty($row['COLUMN_NAME']) ){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD `element_machine_code` varchar(100) NULL AFTER `unique_row_data`;";
			la_do_query($query,array(),$dbh);
		}

		// add/update automatic entry
		if( !empty($pull_from_form) && !empty($form_properties['for_selected_company']) ) {
			addAutomaticEntryPullFrom($dbh, $form_id, $pull_from_form, $form_properties['for_selected_company']);
		} else if (!empty($form_properties['for_selected_company'])) {
			addAutomaticEntry($dbh, $form_id, $form_properties['for_selected_company']);
		}

		//if automapping is enabled, update machine code for all entries
		update_machine_codes($dbh, $form_id, $form_properties['for_selected_company']);

	}

	/***************************************************************************************************************/
	/* 7. Unlock the form																						   */
	/***************************************************************************************************************/

	array_push($queriesList, "delete from ".LA_TABLE_PREFIX."form_locks where form_id=?");
	$queriesListParam = array_merge($queriesListParam, array($form_id));
	la_do_query(implode(";", $queriesList), $queriesListParam, $dbh);

	unset($queriesList);
	unset($queriesListParam);

	/***************************************************************************************************************/
	/* 8. Remove the form signature																					   */
	/***************************************************************************************************************/
	// remove signature
	$query = "DELETE FROM `".LA_TABLE_PREFIX."signed_forms` WHERE `form_id` = ?";
	$sth = la_do_query($query,array($form_id),$dbh);
	

	   //echo '{ "status" : "ok", "form_id" : "'.$form_id.'" }';

   	/***************************************************************************************************************/
	/* Functions																								   */
	/***************************************************************************************************************/

   	function check_result($result){
		if($result !== true){
			if(!is_array($result)){ //if one line error message
				$error = '{ "status" : "error","message" : "'.$result.'"}';
				echo $error;
			}
		}
	}

	$response_data = new stdClass();

	$response_data->status    	= "ok";
	$response_data->form_id    	= $form_id;
	$response_data->csrf_token  = $_SESSION['csrf_token'];

	$response_json 				= json_encode($response_data);

	echo $response_json;
	exit();
