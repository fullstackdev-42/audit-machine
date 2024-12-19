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
		$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";		
		header("Location: restricted.php");
		exit;
	}

	function rrmdir($dir) {
		if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") 
					rrmdir($dir."/".$object); 
				else unlink   ($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
		}
	}

	$form_file_name = la_sanitize($_POST['file_name']);
	$form_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/".$form_file_name;

	$response_data = new stdClass();
	$response_data->status  = "error";

	if(file_exists($form_file_path)){
		//move the file to a new folder and prepare for extracting it
		$createTime = time();
		$upload_folder_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/{$createTime}";
		mkdir($upload_folder_path, 0777, true);
		$new_form_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/{$createTime}/".$form_file_name;
		rename($form_file_path, $new_form_file_path);
		$form_file_path = $new_form_file_path;

		//extract the entry zip file
		$zip = new ZipArchive;
		if ($zip->open($form_file_path) === true) {
			$zip->extractTo($upload_folder_path);
			$zip->close();
			$handle = @fopen($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/{$createTime}/export_content.json", "r");
			
			if($handle) {
				$form_ids_relation = array();

				while (($line_data = fgets($handle)) !== false) {
					//parse the data
					$data_obj = new stdClass();
					$data_obj = json_decode($line_data);

					$is_parse_success = false;

					if(!empty($data_obj->itauditmachine_version)){
						//compare the file first
						//if the file being imported is using newer version than the current itauditmachine version, stop the operation
						if(version_compare($la_settings['itauditmachine_version'], $data_obj->itauditmachine_version,">=")){
							$imported_form_version = $data_obj->itauditmachine_version;
							$imported_form_name	= $data_obj->form_name;
							$original_form_id = $data_obj->form_id;
						}else{
							$response_data->status  = 'error';
							$response_data->message = 'The form is not compatible with your IT Audit Machine.<br/> Please update your IT Audit Machine and try again.';
							break;
						}
					} else if (!empty($data_obj->table_name)){
						$current_table_name = $data_obj->table_name;
						$is_parse_success = true;
					} else {
						//insert into tables
						$data_array 		 = array();
						$data_array 		 = get_object_vars($data_obj);

						$column_names_array  = array_keys($data_array);

						//skip these column names

						if($current_table_name == 'forms'){
							//get new form_id
							$query = "select max(form_id) max_form_id from ".LA_TABLE_PREFIX."forms";
							$params = array();
							
							$sth = la_do_query($query,$params,$dbh);
							$row = la_do_fetch_result($sth);
							
							if(empty($row['max_form_id'])){
								$last_form_id = 10000;
							}else{
								$last_form_id = $row['max_form_id'];
							}
							
							$new_form_id = $last_form_id + rand(100,1000);
							$old_form_id = $data_array['form_id'];
							$form_ids_relation[$old_form_id] = $new_form_id;

							//start createing form table----------				
							$query = "CREATE TABLE IF NOT EXISTS `".LA_TABLE_PREFIX."form_{$new_form_id}` (
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

							//end creating form table-------------

							//Add enable_auto_mapping if it's not there:

							$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$new_form_id}' AND `COLUMN_NAME` = 'element_machine_code';";
							$sth = la_do_query($query,array(),$dbh);
							$row = la_do_fetch_result($sth);

							if( empty($row['COLUMN_NAME']) ){
								$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$new_form_id}` ADD `element_machine_code` varchar(100) NULL AFTER `unique_row_data`;";
								la_do_query($query,array(),$dbh);
							}

							//create data folder
							if(is_writable($la_settings['data_dir'])){
						
								$old_mask = umask(0);
								mkdir($la_settings['data_dir']."/form_{$new_form_id}",0777, true);
								mkdir($la_settings['data_dir']."/form_{$new_form_id}/css",0777, true);
								if($la_settings['data_dir'] != $la_settings['upload_dir']){
									@mkdir($la_settings['upload_dir']."/form_{$new_form_id}",0777, true);
								}
								mkdir($la_settings['upload_dir']."/form_{$new_form_id}/files",0777, true);
								@file_put_contents($la_settings['upload_dir']."/form_{$new_form_id}/files/index.html",' '); //write empty index.html
								
								//copy default view.css to css folder
								if(copy("./view.css",$la_settings['data_dir']."/form_{$new_form_id}/css/view.css")){
									//on success update 'form_has_css' field on ap_forms table
									$form_update_input['form_has_css'] = 1;
									la_ap_forms_update($new_form_id,$form_update_input,$dbh);
								}
								
								umask($old_mask);
							}
						} else if($current_table_name == 'form_elements'){
							$skip_columns = ['id'];

							foreach ($skip_columns as $skip_column) {
								if (($key = array_search($skip_column, $column_names_array)) !== false) {
									unset($column_names_array[$key]);
								}
							}
						}

						$old_form_id = $data_array['form_id'];
						$new_form_id = $form_ids_relation[$old_form_id];

						$column_names_joined = implode("`,`", $column_names_array);

						$params = array();

						foreach ($column_names_array as $column_name) {
							if($current_table_name == 'report_elements' || $current_table_name == 'reports'){
								if($column_name == 'access_key' || $column_name == 'report_access_key'){
									$data_array[$column_name] = str_replace($old_form_id.'x', $new_form_id.'x', $data_array[$column_name]);
								}
							}

							if( $current_table_name == 'form_elements' && $column_name == 'id' ) {
								continue;
							}

							//while import set element_guidelines to new form_id in case of cascaded sub forms
							if( $current_table_name == 'form_elements' && $data_array['element_type'] == 'casecade_form' && $column_name == 'element_guidelines') {
								$data_array[$column_name] = str_replace($data_array['element_default_value'], $form_ids_relation[$data_array['element_default_value']], $data_array[$column_name]);
							}

							//while import set element_default_value to new form_id in case of cascaded sub forms
							if( $current_table_name == 'form_elements' && $data_array['element_type'] == 'casecade_form' && $column_name == 'element_default_value') {
								$data_array[$column_name] = $form_ids_relation[$data_array['element_default_value']];
							}

							//while import set form_theme_id to default_form_theme_id
							if( $current_table_name == 'forms' && $column_name == 'form_theme_id' ) {
								$data_array[$column_name] = $la_settings['default_form_theme_id'];
							}

							$params[':'.$column_name] = $data_array[$column_name];
						}

						$param_names = implode(",", array_keys($params));
						$params[':form_id'] = $new_form_id;

						$query = "INSERT INTO ".LA_TABLE_PREFIX."{$current_table_name}(`{$column_names_joined}`) VALUES({$param_names})";
						la_do_query($query,$params,$dbh);

						$is_parse_success = true;
					}
				}
				if($is_parse_success){
					//move local videos/images and set element_video_url
					$query_media = "SELECT `id`, `element_video_url` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` = ? AND `element_video_source` = ? AND `element_video_url` != ?";
					$sth_media = la_do_query($query_media, array($new_form_id, "video_player", "local", ""), $dbh);
					while($row_media = la_do_fetch_result($sth_media)) {
						$media_source = $row_media['element_video_url'];
						$media_name = end(explode("/", $media_source));
						if(file_exists($upload_folder_path."/".$media_name)) {
							$new_media_source = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/".str_replace("/form_".$old_form_id, "/form_".$new_form_id, end(explode("auditprotocol/", $media_source)));
							$new_video_url = $la_settings['base_url'].str_replace("/form_".$old_form_id, "/form_".$new_form_id, end(explode("auditprotocol/", $media_source)));
							rename($upload_folder_path."/".$media_name, $new_media_source);
						} else {
							$new_video_url = null;
						}
						$query_update_media = "UPDATE `".LA_TABLE_PREFIX."form_elements` set `element_video_url` = ? WHERE `id` = ?";
						la_do_query($query_update_media, array($new_video_url, $row_media['id']), $dbh);
					}

					//move checkbox/radio icons and set option_icon_src
					if(!file_exists($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$new_form_id}/icons")){
						mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$new_form_id}/icons",0777, true);
					}
					$query_icon = "SELECT `aeo_id`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = ? AND `option_icon_src` != ?";
					$sth_icon = la_do_query($query_icon, array($new_form_id, ""), $dbh);
					while($row_icon = la_do_fetch_result($sth_icon)) {
						$icon_source = $row_icon['option_icon_src'];
						$icon_name = end(explode("/", $icon_source));
						if(file_exists($upload_folder_path."/".$icon_name)) {
							$new_icon_source = $_SERVER['DOCUMENT_ROOT'].str_replace("/form_".$old_form_id, "/form_".$new_form_id, $icon_source);
							$new_icon_url = str_replace("/form_".$old_form_id, "/form_".$new_form_id, $icon_source);
							rename($upload_folder_path."/".$icon_name, $new_icon_source);
						} else {
							$new_icon_url = null;
						}
						$query_update_icon = "UPDATE `".LA_TABLE_PREFIX."element_options` set `option_icon_src` = ? WHERE `aeo_id` = ?";
						la_do_query($query_update_icon, array($new_icon_url, $row_icon['aeo_id']), $dbh);
					}

					//send success status
					$response_data->status = 'ok';
					$response_data->new_form_name = htmlentities($imported_form_name,ENT_QUOTES);
					$response_data->new_form_id   = $form_ids_relation[$original_form_id];
				}
				fclose($handle);

				//delete the folder
				rrmdir($upload_folder_path);
			}
		} else {
			$zip->close();
			rrmdir($upload_folder_path);
			$error_message = "Something went wrong with unzipping the file.";
			$response_data = new stdClass();
			$response_data->status = "error";
			$response_data->message = $error_message;
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		}
	}else{
		$response_data->status  = 'error';
		$response_data->message = 'Invalid form file name.';
	}
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
	
?>