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
	require('includes/filter-functions.php');
	//require('includes/users-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	$server_root = $_SERVER['DOCUMENT_ROOT'];
	$response_data = new stdClass();
	
	if( $_POST['file_upload_synced'] == 1 ) {
		$filename = base64_decode($_POST['filename']);
		$element_machine_code = trim($_POST['element_machine_code']);
		$company_id = (int)$_POST['company_id'];

		$file_location = "{$_SERVER["DOCUMENT_ROOT"]}/auditprotocol/data/file_upload_synced/{$element_machine_code}/{$filename}";

		//get data for this machine code
		$sql = "select `id`, `files_data` from `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ? LIMIT 1;";
		$res = la_do_query($sql,array($element_machine_code, $company_id),$dbh);
		$row = la_do_fetch_result($res);

		$files_arr = [];
		if( $row['id'] ) {
			if( !empty($row['files_data']) ) 
				$files_arr = json_decode($row['files_data']);
			
			if (($key = array_search($filename, $files_arr)) !== false) {
				unset($files_arr[$key]);
				if(file_exists($file_location)) {
					unlink($file_location);
				}
			
				//update existing data in case of success
				$query  = "UPDATE `".LA_TABLE_PREFIX."file_upload_synced` SET `files_data` = ? WHERE `element_machine_code` = ? AND company_id = ?;";
				la_do_query($query,array(json_encode(array_values($files_arr)), $element_machine_code, $company_id),$dbh);

				//flipping status indicators
				$query_status_1 = "SELECT s.id FROM `".LA_TABLE_PREFIX."element_status_indicator` AS s LEFT JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON (s.form_id = e.form_id AND s.element_id = e.element_id) WHERE e.element_machine_code = ? AND e.element_type = 'file' AND e.element_file_upload_synced = 1 AND s.company_id = ? AND s.indicator = 3";
					$sth_stauts_1 = la_do_query($query_status_1, array($element_machine_code, $company_id), $dbh);
				while ($res_status_1 = la_do_fetch_result($sth_stauts_1)) {
					$query_status_2 = "UPDATE `".LA_TABLE_PREFIX."element_status_indicator` SET `indicator` = ? WHERE `id` = ?";
					la_do_query($query_status_2, array(2, $res_status_1["id"]), $dbh);
				}
			}
		}
		
		$response_data->status = "ok";
	} else {
		$form_id		= (int) la_sanitize($_POST['form_id']);
		$holder_id		= la_sanitize($_POST['holder_id']);
		$filename		= base64_decode($_POST['filename']);
		$element_id		= (int) la_sanitize($_POST['element_id']);
		$is_db_live		= (int) la_sanitize($_POST['is_db_live']);	
		$key_id			= la_sanitize($_POST['key_id']);
	 	$itauditmachine_data_path = '';
		
		$is_delete_completed = false;
		
		$new_files = array();
		
		$complete_filename = "";
	 	
		if(!empty($is_db_live)){
			// echo "in is_db_live";
			//if the file already inserted into the review table
			$file_token = $key_id;
			
			//directory traversal prevention
			$filename = str_replace('.tmp', '', $filename);
			$filename = str_replace('..','',$filename);
			
			if($company_id){
				$query = "select data_value as file_record, company_id, entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE field_name = ? AND company_id = ? AND data_value LIKE CONCAT('%', ?, '%')";
				$sth = la_do_query($query, array("element_{$element_id}", $company_id, $filename),$dbh);
			}else{
				$query = "select data_value as file_record, company_id, entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE field_name = ? AND data_value LIKE CONCAT('%', ?, '%')";
				$sth = la_do_query($query, array("element_{$element_id}", $filename),$dbh);
			}
			
			$row = la_do_fetch_result($sth);
	 		if(!empty($row)){
	 			$company_id = $row['company_id'];
	 			$entry_id = $row['entry_id'];
	 			if(!empty($row['file_record'])){
					$file_record_array = explode('|',$row['file_record']);
					
					foreach ($file_record_array as $current_file_record){
					
						if($current_file_record == $filename){
							$complete_filename = $current_file_record;
						}else{
							$new_files[] = $current_file_record;
						}					
					}
				}
	 			if(!empty($complete_filename)){
					
					$file_tmp_suffix = "";

					$complete_filename = $server_root."/auditprotocol/data/form_{$form_id}/files/{$complete_filename}{$file_tmp_suffix}";
					
					if(file_exists($complete_filename)){
	 					unlink($complete_filename);
	 					//add activity to audit_log table
						addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 14, $filename, time(), $_SERVER['REMOTE_ADDR']);
					}

					//update the data within the table
					$new_files_joined = implode('|',$new_files);
					$query = "update ".LA_TABLE_PREFIX."form_{$form_id}{$table_suffix} set `data_value` = ? where field_name = ? AND company_id = ? AND entry_id = ?";
					$params = array($new_files_joined, "element_{$element_id}", $company_id, $entry_id);
					la_do_query($query,$params,$dbh);
					$is_delete_completed = true;
					
					$listfile_name = $server_root."/auditprotocol/data/form_{$form_id}/files/listfile_{$file_token}.php";
				
					if(file_exists($listfile_name)){
	 					$current_files = file($listfile_name);
						$new_files = '';
						foreach ($current_files as $value){
							$current_line = trim($value);
							$target_file  = $complete_filename.".tmp";
							
							if($target_file != $current_line){
								$new_files .= $value;
							}
						}
						
						if($new_files == "<?php\n?>"){
							unlink($listfile_name);
						}else{
							file_put_contents($listfile_name, $new_files, LOCK_EX);
						}
					}

					//flipping status indicators
					$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
					la_do_query($query_status_2, array($form_id, $element_id, $company_id, $entry_id), $dbh);
					$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
					la_do_query($query_status_3, array($form_id, $element_id, $company_id, $entry_id, 2), $dbh);
				}
			}else{
				
	 			//file is not being saved into the table yet - only in the list file
	 			
				$file_token = $key_id;		
				
				//directory traversal prevention
				$filename = str_replace('../','',$filename);
				
				$complete_filename = $server_root."/auditprotocol/data/form_{$form_id}/files/element_{$element_id}_{$file_token}-{$filename}.tmp";
				
				if(file_exists($complete_filename)){
					unlink($complete_filename);
					$is_delete_completed = true;
				}
				
				$listfile_name = $server_root."/auditprotocol/data/form_{$form_id}/files/listfile_{$file_token}.php";
				
				if(file_exists($listfile_name)){
	 				$current_files = file($listfile_name);
					$new_files = '';
					foreach ($current_files as $value){
						$current_line = trim($value);
						$target_file  = $complete_filename;
						
						if($target_file != $current_line){
							$new_files .= $value;
						}
					}
	 				if($new_files == "<?php\n?>"){
						unlink($listfile_name);
					}else{
						file_put_contents($listfile_name, $new_files, LOCK_EX);
					}
				}
			}

			//add activity to audit_log table
			addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 14, $filename, time(), $_SERVER['REMOTE_ADDR']);
		}else{
	 
	 		//file is not being saved into  table yet, only in the list file
			$file_token = $key_id;		
			
			//directory traversal prevention
			$filename = str_replace('../','',$filename);
			
			$tmpArrr = array();
			$tmpArr = strpos($_SESSION["element_{$form_id}_{$element_id}"], "|") !== false ? explode("|", $_SESSION["element_{$form_id}_{$element_id}"]) : array($_SESSION["element_{$form_id}_{$element_id}"]);
			
			foreach($tmpArr as $k => $v){
				if($v != $filename){
					array_push($tmpArrr, $v);
				}
			}
			
			$_SESSION["element_{$form_id}_{$element_id}"] = implode("|", $tmpArrr);
			
			$complete_filename = $server_root."/auditprotocol/data/form_{$form_id}/files/element_{$element_id}_{$file_token}-{$filename}.tmp";
			
			if(file_exists($complete_filename)){
				unlink($complete_filename);
				$is_delete_completed = true;
			}
			
			$listfile_name = $server_root."/auditprotocol/data/form_{$form_id}/files/listfile_{$file_token}.php";
			
			if(file_exists($listfile_name)){
				$current_files = file($listfile_name);
				$new_files = '';
				foreach ($current_files as $value){
					$current_line = trim($value);
					$target_file  = $complete_filename;
					
					if($target_file != $current_line){
						$new_files .= $value;
					}
				}
				
				if($new_files == "<?php\n?>"){
					unlink($listfile_name);
				}else{
					file_put_contents($listfile_name, $new_files, LOCK_EX);
				}
			}
		}
		
		if($is_delete_completed){
			$response_data->status    	= "ok";
			$response_data->holder_id	= $holder_id;
			$response_data->element_id  = $element_id;
		}else{
			$response_data->status    	= "error-here";
		}
	}
	
	$response_json = json_encode($response_data);
	
	echo $response_json;