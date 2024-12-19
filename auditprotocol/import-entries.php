<?php
require('includes/init.php');

@ini_set("max_execution_time",1800);
@ini_set("max_input_time",1200);

require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');

require('includes/entry-functions.php');
require('includes/users-functions.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$user_id = (int) $_SESSION['la_user_id'];

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

if(isset($_POST)) {
	if(isset($_POST["action"]) && $_POST["action"] == "import_from_server") {
		//import from the server
		$form_id = (int) trim($_POST['form_id']);
		if(empty($form_id)){
			$error_message = "Invalid form ID.";
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
				$error_message = "Invalid form ID.";
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_data->message = $error_message;
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
		}
		$file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol".$_POST["pathToFile"];
		if(file_exists($file_path)) {
			$file_name = end(explode("/", $file_path));
			$upload_folder_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/tmp_uploaded_entry";
			if(is_dir($upload_folder_path) === false){
				@mkdir($upload_folder_path, 0777, true);
			}
			$unzip_folder = $upload_folder_path."/".explode(".zip", $file_name)[0];
			copy($file_path, $upload_folder_path."/".$file_name);

			//extract the entry zip file
			$zip = new ZipArchive;
			if ($zip->open($upload_folder_path."/".$file_name) === true) {
				$zip->extractTo($unzip_folder);
				$zip->close();

				//check if the uploaded entry backup file is using up-to-date format
				if(!file_exists($unzip_folder."/form_structure.json") || !file_exists($unzip_folder."/form_ids.json") || !file_exists($unzip_folder."/export_data.json") || !file_exists($unzip_folder."/files_data.json")) {
					rrmdir($upload_folder_path);
					$error_message = "The entry backup file {$_FILES['ImageFile']['name'][$key]} is no longer supported in ITAM. Please upload an up-to-date entry backup file.";
					$response_data = new stdClass();
					$response_data->status = "error";
					$response_data->message = $error_message;
					$response_json = json_encode($response_data);
					echo $response_json;
					exit();
				} else {
					//compare form structure and data availability
					$form_ids = array($form_id);

					//get sub form IDs
					$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' ORDER BY element_position ASC";
					$sth = la_do_query($query, array($form_id), $dbh);
					while($row = la_do_fetch_result($sth)){
						array_push($form_ids, (int) $row['element_default_value']);
					}

					//get a structure of the current form
					$current_form_structure = getFormStructure($dbh, $form_ids);

					//get a strucutre of the imported form and compare
					$string = file_get_contents($unzip_folder."/form_structure.json");
					$exported_form_structure = json_decode($string, true);

					if($current_form_structure === $exported_form_structure){
						//get form ids of the exported form
						$string = file_get_contents($unzip_folder."/form_ids.json");
						$exported_form_ids = json_decode($string, true);
						

						//get exported entry data
						$string = file_get_contents($unzip_folder."/export_data.json");
						$exported_entry_data = json_decode($string, true);

						//add entry data, status indicators, synced files to the corresponding tables
						foreach ($exported_entry_data as $data) {
							//decide a company_id based on the email address of the exported entry data
							if($data['company_email'] == "ADMINISTRATOR") {
								$company_id = time();
							} else {
								$query_company = "SELECT client_id FROM ".LA_TABLE_PREFIX."ask_clients WHERE contact_email = ?";
								$sth_company = la_do_query($query_company, array($data['company_email']), $dbh);
								$row_company = la_do_fetch_result($sth_company);
								if($row_company) {
									$company_id = $row_company['client_id'];
								} else {
									$company_id = time();
								}
							}
							$entry_id = time();
							//insert entry data into corresponding tables
							foreach ($data["entry_data"] as $entry_row) {
								$form_id_key = array_search($entry_row["form_id"], $exported_form_ids);
								$new_form_id = $form_ids[$form_id_key];

								//create a form table if it doesn't exist
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
								la_do_query($query, array(), $dbh);

								$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$new_form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `field_score`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?) ON DUPLICATE KEY update `data_value` = values(`data_value`);";
								la_do_query($query, array($company_id, $entry_id, $entry_row["field_name"], $entry_row["field_code"], $entry_row["data_value"], $entry_row["field_score"], $entry_row["form_resume_enable"], $entry_row["element_machine_code"]), $dbh);
							}

							//insert status indicators
							foreach ($data["status_indicators"] as $status_row) {
								$form_id_key = array_search($status_row["form_id"], $exported_form_ids);
								$new_form_id = $form_ids[$form_id_key];

								//delete exisiting status indicator
								$query = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE form_id = ? AND element_id = ? AND company_id = ? AND entry_id = ?";
								la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id), $dbh);

								$query = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?)";
								la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id, $status_row["indicator"]), $dbh);
							}

							//insert synced files
							foreach ($data["synced_files"] as $synced_file_row) {
								//delete exisiting synced files row
								$query = "DELETE FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ?";
								la_do_query($query, array($synced_file_row["element_machine_code"], $company_id), $dbh);

								$query = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`id`, `element_machine_code`, `files_data`, `company_id`) VALUES (NULL, ?, ?, ?)";
								la_do_query($query, array($synced_file_row["element_machine_code"], $synced_file_row["files_data"], $company_id), $dbh);
							}
						}

						//move uploaded files to proper folders
						$string = file_get_contents($unzip_folder."/files_data.json");
						$uploaded_files = json_decode($string, true);
						foreach ($uploaded_files as $uploaded_file) {
							if($uploaded_file["synced"] == 1) {
								$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$uploaded_file['element_machine_code']}";
							} else {
								$form_id_key = array_search($uploaded_file["form_id"], $exported_form_ids);
								$new_form_id = $form_ids[$form_id_key];
								$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$new_form_id}/files";
							}
							if(is_dir($destination_folder) === false){
								@mkdir($destination_folder, 0777, true);
							}
							if(file_exists($unzip_folder."/".$uploaded_file["file_name"])) {
								copy($unzip_folder."/".$uploaded_file["file_name"], $destination_folder."/".$uploaded_file["file_name"]);
							}
						}

						rrmdir($upload_folder_path);
						$_SESSION['LA_SUCCESS'] = "The entry data has been imported successfully.";
						$response_data = new stdClass();
						$response_data->status = "success";
						$response_json = json_encode($response_data);
						echo $response_json;
						exit();
					} else {
						rrmdir($upload_folder_path);
						$error_message = "Form structure is not identical.";
						$response_data = new stdClass();
						$response_data->status = "error";
						$response_data->message = $error_message;
						$response_json = json_encode($response_data);
						echo $response_json;
						exit();
					}
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
		} else {
			$error_message = "Entry backup file does not exist on the server.";
			$response_data = new stdClass();
			$response_data->status = "error";
			$response_data->message = $error_message;
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		}
		
	} else {
		//import from computer
		$form_id = (int) trim($_POST['ImageFolderFormId']);
		if(empty($form_id)){
			$error_message = "Invalid form ID.";
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
				$error_message = "Invalid form ID.";
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_data->message = $error_message;
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
		}

		$folder_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}";
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		//$_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/tmp_uploaded_entry" is a folder where the entry zip file will be uploaded to.
		$upload_folder_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/tmp_uploaded_entry";
		if(is_dir($upload_folder_path) === false){
			@mkdir($upload_folder_path, 0777, true);
		}
		
		$iSleep = 0;
		foreach($_FILES['ImageFile']['tmp_name'] as $key => $val) {
			$iSleep++;
				
			if(!isset($_FILES['ImageFile']['tmp_name'][$key]) || !is_uploaded_file($_FILES['ImageFile']['tmp_name'][$key])){
				$error_message = "Something went wrong with uploading the file. Please try again later.";
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_data->message = $error_message;
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
							
			$ImageName      = str_replace(' ', '_', ($_FILES['ImageFile']['name'][$key]));
			$ImageType      = $_FILES['ImageFile']['type'][$key];
			$ImageExt 		= substr($ImageName, strrpos($ImageName, '.'));
			$ImageExt 		= str_replace('.' ,'', $ImageExt);
			$ImageName      = preg_replace("/\.[^.\s]{3,4}$/", "", $ImageName);
			$ImageName      = str_replace('.', '_', $ImageName);

			if(file_exists($upload_folder_path."/".$ImageName.'.'.$ImageExt)){
				$unzip_folder =  $upload_folder_path."/".$ImageName.'_'.$iSleep;
				$ImageName = $ImageName.'_'.$iSleep.'.'.$ImageExt;
			}else{
				$unzip_folder =  $upload_folder_path."/".$ImageName;
				$ImageName = $ImageName.'.'.$ImageExt;
			}

			if(@move_uploaded_file($_FILES['ImageFile']['tmp_name'][$key], $upload_folder_path."/".$ImageName)){ // move file to proper directory
				//extract the entry zip file
				$zip = new ZipArchive;
				if ($zip->open($upload_folder_path."/".$ImageName) === true) {
					$zip->extractTo($unzip_folder);
					$zip->close();

					//check if the uploaded entry backup file is using up-to-date format
					if(!file_exists($unzip_folder."/form_structure.json") || !file_exists($unzip_folder."/form_ids.json") || !file_exists($unzip_folder."/export_data.json") || !file_exists($unzip_folder."/files_data.json")) {
						rrmdir($upload_folder_path);
						$error_message = "The uploaded entry backup file {$_FILES['ImageFile']['name'][$key]} is no longer supported in ITAM. Please upload an up-to-date entry backup file.";
						$response_data = new stdClass();
						$response_data->status = "error";
						$response_data->message = $error_message;
						$response_json = json_encode($response_data);
						echo $response_json;
						exit();
					} else {
						//compare form structure and data availability
						$form_ids = array($form_id);

						//get sub form IDs
						$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' ORDER BY element_position ASC";
						$sth = la_do_query($query, array($form_id), $dbh);
						while($row = la_do_fetch_result($sth)){
							array_push($form_ids, (int) $row['element_default_value']);
						}

						//get a structure of the current form
						$current_form_structure = getFormStructure($dbh, $form_ids);

						//get a strucutre of the imported form and compare
						$string = file_get_contents($unzip_folder."/form_structure.json");
						$exported_form_structure = json_decode($string, true);

						if($current_form_structure === $exported_form_structure){
							//get form ids of the exported form
							$string = file_get_contents($unzip_folder."/form_ids.json");
							$exported_form_ids = json_decode($string, true);
							

							//get exported entry data
							$string = file_get_contents($unzip_folder."/export_data.json");
							$exported_entry_data = json_decode($string, true);

							//add entry data, status indicators, synced files to the corresponding tables
							foreach ($exported_entry_data as $data) {
								//decide a company_id based on the email address of the exported entry data
								if($data['company_email'] == "ADMINISTRATOR") {
									$company_id = time();
								} else {
									$query_company = "SELECT client_id FROM ".LA_TABLE_PREFIX."ask_clients WHERE contact_email = ?";
									$sth_company = la_do_query($query_company, array($data['company_email']), $dbh);
									$row_company = la_do_fetch_result($sth_company);
									if($row_company) {
										$company_id = $row_company['client_id'];
									} else {
										$company_id = time();
									}
								}
								$entry_id = time();
								//insert entry data into corresponding tables
								foreach ($data["entry_data"] as $entry_row) {
									$form_id_key = array_search($entry_row["form_id"], $exported_form_ids);
									$new_form_id = $form_ids[$form_id_key];

									//create a form table if it doesn't exist
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
									la_do_query($query, array(), $dbh);

									$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$new_form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `field_score`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?) ON DUPLICATE KEY update `data_value` = values(`data_value`);";
									la_do_query($query, array($company_id, $entry_id, $entry_row["field_name"], $entry_row["field_code"], $entry_row["data_value"], $entry_row["field_score"], $entry_row["form_resume_enable"], $entry_row["element_machine_code"]), $dbh);
								}

								//insert status indicators
								foreach ($data["status_indicators"] as $status_row) {
									$form_id_key = array_search($status_row["form_id"], $exported_form_ids);
									$new_form_id = $form_ids[$form_id_key];

									//delete exisiting status indicator
									$query = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE form_id = ? AND element_id = ? AND company_id = ? AND entry_id = ?";
									la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id), $dbh);

									$query = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?)";
									la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id, $status_row["indicator"]), $dbh);
								}

								//insert synced files
								foreach ($data["synced_files"] as $synced_file_row) {
									//delete exisiting synced files row
									$query = "DELETE FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ?";
									la_do_query($query, array($synced_file_row["element_machine_code"], $company_id), $dbh);

									$query = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`id`, `element_machine_code`, `files_data`, `company_id`) VALUES (NULL, ?, ?, ?)";
									la_do_query($query, array($synced_file_row["element_machine_code"], $synced_file_row["files_data"], $company_id), $dbh);
								}
							}

							//move uploaded files to proper folders
							$string = file_get_contents($unzip_folder."/files_data.json");
							$uploaded_files = json_decode($string, true);
							foreach ($uploaded_files as $uploaded_file) {
								if($uploaded_file["synced"] == 1) {
									$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$uploaded_file['element_machine_code']}";
								} else {
									$form_id_key = array_search($uploaded_file["form_id"], $exported_form_ids);
									$new_form_id = $form_ids[$form_id_key];
									$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$new_form_id}/files";
								}
								if(is_dir($destination_folder) === false){
									@mkdir($destination_folder, 0777, true);
								}
								if(file_exists($unzip_folder."/".$uploaded_file["file_name"])) {
									copy($unzip_folder."/".$uploaded_file["file_name"], $destination_folder."/".$uploaded_file["file_name"]);
								}
							}

							rrmdir($upload_folder_path);
							$_SESSION['LA_SUCCESS'] = "The entry data has been imported successfully.";
							$response_data = new stdClass();
							$response_data->status = "success";
							$response_json = json_encode($response_data);
							echo $response_json;
							exit();
						} else {
							rrmdir($upload_folder_path);
							$error_message = "Form structure is not identical.";
							$response_data = new stdClass();
							$response_data->status = "error";
							$response_data->message = $error_message;
							$response_json = json_encode($response_data);
							echo $response_json;
							exit();
						}
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
			} else {
				$error_message = "Something went wrong with uploading the file. Please try again later.";
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_data->message = $error_message;
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}
		}
	}
}
