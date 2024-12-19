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

	ob_clean(); //clean the output buffer

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

	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";
				
			header("Location: restricted.php");
			exit;
		}
	}

	$export_content = '';

	$query = "SELECT form_name FROM ".LA_TABLE_PREFIX."forms where form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)){
		$form_meta_obj = new StdClass();
		$form_meta_obj->form_id   		 = $form_id;
		$form_meta_obj->form_name 		 = $row['form_name'];
		$form_meta_obj->itauditmachine_version = $la_settings['itauditmachine_version'];
		$form_meta_obj->export_date 	 = date("Y-m-d H:i:s");
	}else{
		die("Error. Invalid Form ID.");
	}

	$form_meta_json  = json_encode($form_meta_obj);
	if(!empty($form_meta_json)){
		$export_content .= $form_meta_json."\n";
	}
	
	$form_ids = array($form_id);
	//get sub form IDs
	$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' AND element_default_value != ? ORDER BY element_position ASC";
	$sth = la_do_query($query, array($form_id, ""), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($form_ids, (int) $row['element_default_value']);
	}

	//export ap_forms
	$ap_forms_json 	 = la_export_table_rows($dbh,'forms',$form_ids);

	if(!empty($ap_forms_json)){
		$export_content .= $ap_forms_json."\n";
	}

	//export ap_form_elements
	$ap_form_elements_json = la_export_table_rows($dbh,'form_elements',$form_ids);
	if(!empty($ap_form_elements_json)){
		$export_content .= $ap_form_elements_json."\n";
	}
	
	//export ap_element_options
	$ap_element_options_json = la_export_table_rows($dbh,'element_options',$form_ids);
	if(!empty($ap_element_options_json)){
		$export_content .= $ap_element_options_json."\n";
	}
	
	//export ap_element_prices
	$ap_element_prices_json = la_export_table_rows($dbh,'element_prices',$form_ids);
	if(!empty($ap_element_prices_json)){
		$export_content .= $ap_element_prices_json."\n";	
	}
	
	//export ap_email_logic
	$ap_email_logic_json = la_export_table_rows($dbh,'email_logic',$form_ids);
	if(!empty($ap_email_logic_json)){
		$export_content .= $ap_email_logic_json."\n";	
	}

	//export ap_email_logic_conditions
	$ap_email_logic_conditions_json = la_export_table_rows($dbh,'email_logic_conditions',$form_ids);
	if(!empty($ap_email_logic_conditions_json)){
		$export_content .= $ap_email_logic_conditions_json."\n";
	}
	
	//export ap_field_logic_conditions
	$ap_field_logic_conditions_json = la_export_table_rows($dbh,'field_logic_conditions',$form_ids);
	if(!empty($ap_field_logic_conditions_json)){
		$export_content .= $ap_field_logic_conditions_json."\n";
	}
	
	//export ap_field_logic_elements
	$ap_field_logic_elements_json = la_export_table_rows($dbh,'field_logic_elements',$form_ids);
	if(!empty($ap_field_logic_elements_json)){
		$export_content .= $ap_field_logic_elements_json."\n";
	}
	
	//export ap_grid_columns
	$ap_grid_columns_json = la_export_table_rows($dbh,'grid_columns',$form_ids);
	if(!empty($ap_grid_columns_json)){
		$export_content .= $ap_grid_columns_json."\n";
	}
	
	//export ap_page_logic
	$ap_page_logic_json = la_export_table_rows($dbh,'page_logic',$form_ids);
	if(!empty($ap_page_logic_json)){
		$export_content .= $ap_page_logic_json."\n";
	}

	//export ap_page_logic_conditions
	$ap_page_logic_conditions_json = la_export_table_rows($dbh,'page_logic_conditions',$form_ids);
	if(!empty($ap_page_logic_conditions_json)){
		$export_content .= $ap_page_logic_conditions_json."\n";
	}

	//export ap_report_elements
	$ap_report_elements_json = la_export_table_rows($dbh,'report_elements',$form_ids);
	if(!empty($ap_report_elements_json)){
		$export_content .= $ap_report_elements_json."\n";
	}

	//export ap_report_filters
	$ap_report_filters_json = la_export_table_rows($dbh,'report_filters',$form_ids);
	if(!empty($ap_report_filters_json)){
		$export_content .= $ap_report_filters_json."\n";
	}

	//export ap_reports
	$ap_reports_json = la_export_table_rows($dbh,'reports',$form_ids);
	if(!empty($ap_reports_json)){
		$export_content .= $ap_reports_json."\n";
	}

	//export ap_webhook_logic_conditions
	$ap_webhook_logic_conditions_json = la_export_table_rows($dbh,'webhook_logic_conditions',$form_ids);
	if(!empty($ap_webhook_logic_conditions_json)){
		$export_content .= $ap_webhook_logic_conditions_json."\n";
	}

	//export ap_webhook_options
	$ap_webhook_options_json = la_export_table_rows($dbh,'webhook_options',$form_ids);
	if(!empty($ap_webhook_options_json)){
		$export_content .= $ap_webhook_options_json."\n";
	}

	//export ap_webhook_parameters
	$ap_webhook_parameters_json = la_export_table_rows($dbh,'webhook_parameters',$form_ids);
	if(!empty($ap_webhook_parameters_json)){
		$export_content .= $ap_webhook_parameters_json."\n";
	}

	$export_content = trim($export_content);
	
	//generate a zip file that includes all form data and files

	$zip = NULL;
	$zip_name = '';
	$export_link = '';
	$createTime = time();
	if(is_dir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}") === false){
		@mkdir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}", 0777, true);
	}
	if(is_dir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form") === false){
		@mkdir($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form", 0777, true);
	}

	if(extension_loaded('zip')){
		$zip = new ZipArchive();
		$zip_name = $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form/{$clean_form_name}_{$form_id}.zip";
		$export_link = "data/form_{$form_id}/export_form/{$clean_form_name}_{$form_id}.zip";
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
		$error_message = "A zip cannot be created.";
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->message = $error_message;
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}

	//generate form data JSON file and add it to the zip
	$fp_export_content = fopen($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form/export_content.json", 'w');
	fwrite($fp_export_content, $export_content);
	fclose($fp_export_content);
	$zip->addFile($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form/export_content.json", "export_content.json");

	//get local videos/images and add them to the zip
	$query_media = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` = ? AND `element_video_source` = ? AND `element_video_url` != ?";
	$sth_media = la_do_query($query_media, array($form_id, "video_player", "local", ""), $dbh);
	while($row_media = la_do_fetch_result($sth_media)) {
		$media_source = $_SERVER["DOCUMENT_ROOT"]."/auditprotocol".explode("auditprotocol", $row_media['element_video_url'])[1];
		if(file_exists($media_source)) {
			$file_name = end(explode("/", $media_source));
			$zip->addFile($media_source, $file_name);
		}
	}

	//get icons for radios and checkboxes and add them to the zip
	$query_icon = "SELECT `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = ? AND `option_icon_src` != ?";
	$sth_icon = la_do_query($query_icon, array($form_id, ""), $dbh);
	while($row_icon = la_do_fetch_result($sth_icon)) {
		$icon_source = $_SERVER["DOCUMENT_ROOT"]."/auditprotocol".explode("auditprotocol", $row_icon['option_icon_src'])[1];
		if(file_exists($icon_source)) {
			$icon_name = end(explode("/", $icon_source));
			$zip->addFile($icon_source, $icon_name);
		}
	}
	$zip->close();

	unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/export_form/export_content.json");
	$response_data = new stdClass();
	$response_data->status = "success";
	$response_data->export_link = "data/form_{$form_id}/export_form/{$clean_form_name}_{$form_id}.zip";
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
	/*********************************************************************************/
	/** Functions **/

	//export table rows into JSON data
	//each record into one line
	function la_export_table_rows($dbh,$table_name,$form_ids){
		//get the data
		$complete_table_name = LA_TABLE_PREFIX.$table_name;
		
		$table_meta_obj = new StdClass();
		$table_meta_obj->table_name = $table_name;
		$table_meta_json = json_encode($table_meta_obj);

		$inQueryForms = implode(',', array_fill(0, count($form_ids), '?'));

		$query  = "SELECT * FROM `{$complete_table_name}` WHERE `form_id` IN ({$inQueryForms})";
		$sth = la_do_query($query, $form_ids, $dbh);

		$table_data_json = '';
		$unused_columns = array('aeo_id','aep_id','aec_id','alc_id','agc_id','apc_id','arf_id','wlc_id','awo_id','awp_id');

		while($row = la_do_fetch_result($sth)){
			foreach ($row as $column_name => $column_data) {
				if(in_array($column_name, $unused_columns)){
					continue;
				}
				
				if(in_array($column_name, array('form_upload_template', 'folder_id'))){
					if($column_name == 'folder_id'){
						$row_data[$column_name] = "0";
					}else{
						$row_data[$column_name] = "";
					}
				}else{
					$row_data[$column_name] = $column_data;
				}
			}
			$table_data_json .= json_encode($row_data)."\n";
		}

		$table_data_json = trim($table_data_json);

		if(!empty($table_data_json)){
			$table_data_json = $table_meta_json."\n".$table_data_json;
		}

		return $table_data_json;		
	}

?>