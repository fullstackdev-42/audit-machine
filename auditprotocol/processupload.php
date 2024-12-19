<?php
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require_once("../policymachine/classes/CreateDocx.php");
require_once("../policymachine/classes/CreateDocxFromTemplate.php");
require('includes/report-helper-function.php');
require('includes/helper-functions.php');
//require('includes/check-session.php');
require('includes/common-validator.php');
require('includes/filter-functions.php');
require('lib/swift-mailer/swift_required.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$user_id = (int) $_SESSION['la_user_id'];

function createFolder($dbh, $param=array()){
	$query = "INSERT INTO `".LA_TABLE_PREFIX."folders` (`folder_id`, `folder_name`, `folder_description`, `create_date`, `update_date`) VALUES (NULL, ?, ?, ?, ?);";
	$sth = la_do_query($query, array($param['folder_name'], $param['folder_description'], time(), time()), $dbh);
	return $dbh->lastInsertId();
}

function updateFolder($dbh, $param=array()){
	$query = "UPDATE `".LA_TABLE_PREFIX."folders` SET `folder_name` = ? WHERE `folder_id` = ?";
	la_do_query($query, array($param['folder_name'], $param['folder_id']), $dbh);
}

function deleteFolder($dbh, $param=array()){
	$query = "DELETE FROM `".LA_TABLE_PREFIX."folders` WHERE `folder_id` = ?";
	la_do_query($query, array($param['folder_id']), $dbh);
	
	$query = "update ".LA_TABLE_PREFIX."forms set folder_id = ? where folder_id = ?";
	la_do_query($query, array(0, $param['folder_id']), $dbh);
}

function updateFormFolder($dbh, $param=array()){
	$query = "update ".LA_TABLE_PREFIX."forms set folder_id = ? where form_id = ?";
	la_do_query($query, array($param['folder_id'], $param['form_id']), $dbh);
}

if(isset($_POST)){
	if($_POST['mode'] == "create_and_move"){
		echo $folder_id = createFolder($dbh, array('folder_name' => $_POST['folder_name'], 'folder_description' => $_POST['folder_description']));
		updateFormFolder($dbh, array('form_id' => $_POST['form_id'], 'folder_id' => $folder_id));
	}
	elseif($_POST['mode'] == "move"){
		updateFormFolder($dbh, array('form_id' => $_POST['form_id'], 'folder_id' => $_POST['folder_id']));
	}
	elseif($_POST['mode'] == "rename_folder"){
		updateFolder($dbh, array('folder_id' => $_POST['folder_id'], 'folder_name' => $_POST['folder_name']));
	}
	elseif($_POST['mode'] == "delete_folder"){
		deleteFolder($dbh, array('folder_id' => $_POST['folder_id']));
	}
	elseif($_POST['mode'] == "del_image"){
		/* deleting image name from table */
		
		$form_id = (int) trim($_POST['form_id']);
		$template_id = (int) $_POST['image_name'];
		
		$query 	= "select form_upload_template from ".LA_TABLE_PREFIX."forms where form_id = :form_id";
		$params = array();
		$params[':form_id'] = $form_id;
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row) && !empty($row['form_upload_template'])){
			if(strpos($row['form_upload_template'], ",") !== false){
				$tmpArr = explode(",", $row['form_upload_template']);
			}else{
				$tmpArr[] = $row['form_upload_template'];
			}
			
			if(($key = array_search(trim($_POST['image_name_str']), $tmpArr)) !== false){
				unset($tmpArr[$key]);
			}
			
			$form_upload_template = implode(",", $tmpArr);
			$query 	= "update ".LA_TABLE_PREFIX."forms set form_upload_template = '".$form_upload_template."' where form_id = :form_id";
			$params = array();
			$params[':form_id'] = $form_id;
			la_do_query($query,$params,$dbh);
		}
		
		$query_get = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `template_id` = :template_id";
		$params_get = array();
		$params_get[':template_id'] = $template_id;
		$result_get = la_do_query($query_get,$params_get,$dbh);
		$row_get = la_do_fetch_result($result_get);
		
		$response_data = new stdClass();
						
		if(!empty($row_get['template'])){
			$filename = trim($row_get['template']);
			if(file_exists($filename)) { 
				@unlink($filename);
				$query = "DELETE FROM `".LA_TABLE_PREFIX."form_template` WHERE `template_id` = :template_id";
				$params = array();
				$params[':template_id'] = $template_id;
				la_do_query($query,$params,$dbh);

				//update background_document_proccesses
				$query_background_document_proccesses = "DELETE FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE `form_id` = :form_id AND `status` = 0";
				$params = array();
				$params[':form_id'] = $form_id;
				la_do_query($query_background_document_proccesses,$params,$dbh);

				$response_data->message = 'success';
			}else{
				$response_data->message = 'file not found';
			}
		}
		
		$response_data->csrf_token  = $_SESSION['csrf_token'];
		
		$response_json = json_encode($response_data);
		echo $response_json;
		/* ****************************** */
	}
	elseif($_POST['mode'] == "updatenote"){

		$note_id = "";
		$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
		$sth_entity = la_do_query($query_entity, array($_POST["company_id"]), $dbh);
		$row_entity = la_do_fetch_result($sth_entity);
		if(empty($row_entity)) {//if admin
			$query = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = :form_id and `element_id` = :element_id and `admin_id` = :admin_id";
			$params = array();
			$params[':form_id'] 	 = $_POST['form_id'];
			$params[':element_id'] 	 = $_POST['element_id'];
			$params[':admin_id']	 = $_SESSION['la_user_id'];

			$sth = la_do_query($query,$params,$dbh);
			$row_get = la_do_fetch_result($sth);
			
			if($row_get) {
				$note_id = $row_get["form_element_note_id"];
				$query = "UPDATE `".LA_TABLE_PREFIX."form_element_note` SET `company_id` = :company_id, `user_id` = :user_id, `note` = :note, `assignees` = :assignees, `status` = :status, `reminder_sent_date` = CURRENT_TIMESTAMP WHERE `form_element_note_id` = :note_id";
				$params = array();
				$params[':note_id'] = $note_id;
				$params[':company_id'] = $_POST['company_id'];
				$params[':user_id'] = 0;
				$params[':note'] = $_POST['note'];
				$params[':assignees'] = $_POST['assignees'];
				$params[':status'] = 0;
				la_do_query($query,$params,$dbh);
			} else {
				$query = "INSERT INTO `".LA_TABLE_PREFIX."form_element_note` (`form_element_note_id`, `form_id`, `element_id`, `admin_id`, `company_id`, `user_id`, `assignees`, `note`, `create_date`, `reminder_sent_date`, `status`) VALUES (NULL, :form_id, :element_id, :admin_id, :company_id, :user_id, :assignees, :note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :status)";
				$params = array();
				$params[':note'] 		 = $_POST['note'];
				$params[':form_id'] 	 = $_POST['form_id'];
				$params[':element_id'] 	 = $_POST['element_id'];
				$params[':admin_id'] 	 = $_SESSION['la_user_id'];
				$params[':company_id']	 = $_POST['company_id'];
				$params[':user_id']	 = 0;
				$params[':assignees'] 	 = $_POST['assignees'];
				$params[':status'] 	 = 0;
				la_do_query($query,$params,$dbh);
				$note_id = $dbh->lastInsertId();
			}			
		} else {//if entity user
			$query = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = :form_id and `element_id` = :element_id and `company_id` = :company_id";
			$params = array();
			$params[':form_id'] 	 = $_POST['form_id'];
			$params[':element_id'] 	 = $_POST['element_id'];
			$params[':company_id']	 = $_POST['company_id'];

			$sth = la_do_query($query,$params,$dbh);
			$row_get = la_do_fetch_result($sth);

			if($row_get) {
				$note_id = $row_get["form_element_note_id"];
				$query = "UPDATE `".LA_TABLE_PREFIX."form_element_note` SET `note` = :note, `assignees` = :assignees, `status` = :status, `reminder_sent_date` = CURRENT_TIMESTAMP WHERE `form_element_note_id` = :note_id";
				$params = array();
				$params[':note_id'] = $note_id;
				$params[':note'] 		 = $_POST['note'];
				$params[':assignees'] 	 = $_POST['assignees'];
				$params[':status'] 	 = 0;
				la_do_query($query,$params,$dbh);
			} else {
				$query = "INSERT INTO `".LA_TABLE_PREFIX."form_element_note` (`form_element_note_id`, `form_id`, `element_id`, `admin_id`, `company_id`, `user_id`, `assignees`, `note`, `create_date`, `reminder_sent_date`, `status`) VALUES (NULL, :form_id, :element_id, :admin_id, :company_id, :user_id, :assignees, :note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :status)";
				$params = array();
				$params[':note'] 		 = $_POST['note'];
				$params[':form_id'] 	 = $_POST['form_id'];
				$params[':element_id'] 	 = $_POST['element_id'];
				$params[':admin_id'] 	 = 0;
				$params[':company_id']		 = $_POST['company_id'];
				$params[':user_id']		 = 0;
				$params[':assignees'] 	 = $_POST['assignees'];
				$params[':status'] 	 = 0;
				la_do_query($query,$params,$dbh);
				$note_id = $dbh->lastInsertId();				
			}
		}
		sendNoteNotification($dbh, $la_settings, $note_id);
		echo empty($_POST['note']) ? 'gray' : 'green';
		exit();		
	}
	elseif($_POST['mode'] == "clearnote"){
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_element_note_id` = :note_id";
		$params = array();
		$params[':note_id'] = $_POST['note_id'];
		la_do_query($query,$params,$dbh);
		echo "deleted";
		exit();		
	}
	elseif($_POST['mode'] == "getnote"){

		$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
		$sth_entity = la_do_query($query_entity, array($_POST["company_id"]), $dbh);
		$row_entity = la_do_fetch_result($sth_entity);
		if(empty($row_entity)) {//if admin
			$query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = :form_id and `element_id` = :element_id and `admin_id` = :admin_id";
			$params = array();
			$params[':form_id'] 	 = $_POST['form_id'];
			$params[':element_id'] 	 = $_POST['element_id'];
			$params[':admin_id'] 	 = $_SESSION['la_user_id'];
			$sth_note = la_do_query($query_note,$params,$dbh);
			$row_note = la_do_fetch_result($sth_note);
			if(empty($row_note)) {
				echo "No element note found";
			} else {
				echo json_encode(array('note_id' => $row_note['form_element_note_id'], 'note' => $row_note['note'], 'assignees' => $row_note['assignees']));
			}
		} else {//if entity user
			$query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = :form_id and `element_id` = :element_id and `company_id` = :company_id";
			$params = array();
			$params[':form_id'] 	 = $_POST['form_id'];
			$params[':element_id'] 	 = $_POST['element_id'];
			$params[':company_id'] 	 = $_POST['company_id'];
			$sth_note = la_do_query($query_note,$params,$dbh);
			$row_note = la_do_fetch_result($sth_note);
			if(empty($row_note)) {
				echo "No element note found";
			} else {
				echo json_encode(array('note_id' => $row_note['form_element_note_id'], 'note' => $row_note['note'], 'assignees' => $row_note['assignees']));
			}
		}
		exit();		
	}
	elseif($_POST['mode'] == "getassignednotes"){
		$form_id = $_POST["form_id"];
		$element_id = $_POST["element_id"];
		$assignee_id = $_POST["assignee_id"];
		$role = $_POST["role"];
		$notes = array();
		$query_note = $query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = ? and `element_id` = ?";
		$sth_note = la_do_query($query_note, array($form_id, $element_id), $dbh);

		if($role == "admin") {
			while ($row_note = la_do_fetch_result($sth_note)) {
				if(!empty($row_note["note"]) && in_array($assignee_id, explode(",", explode(";", $row_note["assignees"])[0]))){
					if($row_note["admin_id"] == 0) {//if assigned by entity
						$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
						$sth_entity = la_do_query($query_entity, array($row_note["company_id"]), $dbh);
						$row_entity = la_do_fetch_result($sth_entity);
						$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
						$sth_user = la_do_query($query_user, array($row_note["user_id"]), $dbh);
						$row_user = la_do_fetch_result($sth_user);
						if(isset($row_user) && !empty($row_user) && isset($row_entity) && !empty($row_entity)){
							array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[User] ".$row_user["full_name"]."</br>[Entity] ".$row_entity["company_name"], "status" => $row_note["status"], "avatar_url" => $la_settings["base_url"].$row_user["avatar_url"]));
						} else {
							if(isset($row_entity) && !empty($row_entity)) {
								array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[Entity] ".$row_entity["company_name"], "status" => $row_note["status"], "avatar_url" => ""));
							} else {
								array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "-", "status" => $row_note["status"], "avatar_url" => ""));
							}
						}
					} else {//if assigned by admin
						$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
						$sth_admin = la_do_query($query_admin, array($row_note["admin_id"]), $dbh);
						$row_admin = la_do_fetch_result($sth_admin);
						if(isset($row_admin) && !empty($row_admin)) {
							array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[Admin] ".$row_admin["user_fullname"], "status" => $row_note["status"], "avatar_url" => $la_settings["base_url"].$row_admin["avatar_url"]));
						} else {
							array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "-", "status" => $row_note["status"], "avatar_url" => ""));
						}
					}
				}
			}
		} else {
			while ($row_note = la_do_fetch_result($sth_note)) {

				if(!empty($row_note["note"]) && in_array($assignee_id, explode(",", explode(";", $row_note["assignees"])[1]))){
					if($row_note["admin_id"] == 0) {//if assigned by entity
						$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
						$sth_entity = la_do_query($query_entity, array($row_note["company_id"]), $dbh);
						$row_entity = la_do_fetch_result($sth_entity);
						$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
						$sth_user = la_do_query($query_user, array($row_note["user_id"]), $dbh);
						$row_user = la_do_fetch_result($sth_user);
						if(isset($row_user) && !empty($row_user) && isset($row_entity) && !empty($row_entity)){
							array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[User] ".$row_user["full_name"]."</br>[Entity] ".$row_entity["company_name"], "status" => $row_note["status"], "avatar_url" => $la_settings["base_url"].$row_user["avatar_url"]));
						} else {
							if(isset($row_entity) && !empty($row_entity)) {
								array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[Entity] ".$row_entity["company_name"], "status" => $row_note["status"], "avatar_url" => ""));
							}
						}
					} else {//if assigned by admin
						$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
						$sth_admin = la_do_query($query_admin, array($row_note["admin_id"]), $dbh);
						$row_admin = la_do_fetch_result($sth_admin);
						if(isset($row_admin) && !empty($row_admin)) {
							array_push($notes, array("note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => "[Admin] ".$row_admin["user_fullname"], "status" => $row_note["status"], "avatar_url" => $la_settings["base_url"].$row_admin["avatar_url"]));
						}
					}
				}
			}
		}
		if(count($notes) == 0){
			echo "No element note found";
		} else {
			echo json_encode(array('notes' => $notes));
		}
		exit();		
	}
	elseif($_POST['mode'] == 'updateindicator'){
		$indicator = fmod((int) $_POST['indicator'] + 1, 4);
		$status_icon_arr = ['Gray', 'Red', 'Yellow', 'Green'];
		$status_icon = $status_icon_arr[$indicator];
		$query_indicator = "DELETE FROM ".LA_TABLE_PREFIX."element_status_indicator WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		la_do_query($query_indicator, array($_POST['form_id'], $_POST['element_id'], $_POST['company_id'], $_POST['entry_id']), $dbh);
		
		$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?);";
		la_do_query($query_insert, array($_POST['form_id'], $_POST['element_id'], $_POST['company_id'], $_POST['entry_id'], $indicator), $dbh);
		
		echo json_encode(array('status_icon' => $status_icon, 'indicator' => $indicator));
		exit();
	}
	elseif($_POST['mode'] == 'updateindicatorany'){
		$status_icon_arr = ['Gray', 'Red', 'Yellow', 'Green'];
		$indicator = $_POST['indicator'];
		$status_icon = $status_icon_arr[$indicator];
		
		$query_indicator = "DELETE FROM ".LA_TABLE_PREFIX."element_status_indicator WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		la_do_query($query_indicator, array($_POST['form_id'], $_POST['element_id'], $_POST['company_id'], $_POST['entry_id']), $dbh);
		
		$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?);";
		la_do_query($query_insert, array($_POST['form_id'], $_POST['element_id'], $_POST['company_id'], $_POST['entry_id'], $indicator), $dbh);
		echo json_encode(array('status_icon' => $status_icon, 'indicator' => $indicator));
		exit();
	}
	elseif($_POST['mode'] == 'upload_icon'){
		$form_id = (int) la_sanitize($_POST['ImageFolderFormId']);
		$option_id = $_POST['option_id'];
		$folder_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data";
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		$folder_path .= "/form_".$form_id;
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		$folder_path .= "/icons";
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		foreach($_FILES['ImageFile']['tmp_name'] as $key => $val) {
			if(!isset($_FILES['ImageFile']['tmp_name'][$key]) || !is_uploaded_file($_FILES['ImageFile']['tmp_name'][$key])){
				$response_data = new stdClass();
				$response_data->status = "error";
				$response_data->msg = "Something went wrong while uploading the icon.";
				$response_json = json_encode($response_data);
				echo $response_json;
				exit();
			}

			$ImageName      = str_replace(' ', '_', strtolower($_FILES['ImageFile']['name'][$key]));
			$ImageType      = $_FILES['ImageFile']['type'][$key];
			$ImageExt 		= substr($ImageName, strrpos($ImageName, '.'));
			$ImageExt 		= str_replace('.' ,'', $ImageExt);
			$ImageName      = preg_replace("/\.[^.\s]{3,4}$/", "", $ImageName);
			$ImageName      = str_replace('.' ,'_', $ImageName);

			$ImageName = $ImageName.time().'.'.$ImageExt;
			
			if(@move_uploaded_file($_FILES['ImageFile']['tmp_name'][$key], $folder_path."/".$ImageName)){
				$uploaded_icon_src = "/auditprotocol/data/form_{$form_id}/icons/{$ImageName}";
			}
		}
		$response_data = new stdClass();
		$response_data->status = "ok";
		$response_data->uploaded_icon_src = $uploaded_icon_src;
		$response_data->option_id = $option_id;
		$response_json = json_encode($response_data);
		echo $response_json;
		exit();
	}
	else{
		
		$form_id = (int) la_sanitize($_POST['ImageFolderFormId']);
		la_update_template_file($dbh, $form_id);
		
		$folder_path = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['SCRIPT_NAME'])."/templates/".$user_id;
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		
		$folder_path = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['SCRIPT_NAME'])."/templates/".$user_id."/".$form_id;
		if(is_dir($folder_path) === false){
			@mkdir($folder_path, 0777, true);
		}
		
		$responseArr = array();
		
		$iSleep = 0;
		
		foreach($_FILES['ImageFile']['tmp_name'] as $key => $val)
		{	
			$iSleep++;
				
			if(!isset($_FILES['ImageFile']['tmp_name'][$key]) || !is_uploaded_file($_FILES['ImageFile']['tmp_name'][$key])){
				die('Something went wrong with Upload!');
			}
							
			$RandomNum   	= date("m_d_y")."_".time();
			$ImageName      = str_replace(' ', '_', strtolower($_FILES['ImageFile']['name'][$key]));
			$ImageType      = $_FILES['ImageFile']['type'][$key];
			$ImageExt 		= substr($ImageName, strrpos($ImageName, '.'));
			$ImageExt 		= str_replace('.' ,'', $ImageExt);
			$ImageName      = preg_replace("/\.[^.\s]{3,4}$/", "", $ImageName);
			$ImageName      = str_replace('.' ,'_', $ImageName);
								
			if(file_exists($folder_path."/".$ImageName.'.'.$ImageExt)){
				$ImageName = $ImageName.'_'.$iSleep.'.'.$ImageExt;	
			}else{
				$ImageName = $ImageName.'.'.$ImageExt;
			}
			
			if(@move_uploaded_file($_FILES['ImageFile']['tmp_name'][$key], $folder_path."/".$ImageName)){
				//Optimize the uploaded template
				if($ImageExt == "docx") {
					$template = $folder_path."/".$ImageName;
					$template_name = $ImageName;

					$docx = new CreateDocxFromTemplate($template);
					$templateVar = $docx->getTemplateVariables();
					$place_holders = array();
					if(!is_null($templateVar["document"])) {
						$place_holders = $templateVar["document"];
					}
					if(!is_null($templateVar["header"])) {
						$place_holders = array_merge($place_holders, $templateVar["header"]);
					}
					if(!is_null($templateVar["footer"])) {
						$place_holders = array_merge($place_holders, $templateVar["footer"]);
					}
					$optimized_template = str_replace($template_name, "optimized_".$template_name, $template);

					$docx = new ProcessTemplate();
					$docx->optimizeTemplate($template, $optimized_template, $place_holders);
					
					unlink($template);
					rename($optimized_template, $template);
				}
				
				$query = "INSERT INTO `".LA_TABLE_PREFIX."form_template` (`template_id`, `form_id`, `template`) VALUES (NULL, :form_id, :template)";
				$params = array();
				$params[':form_id'] = $form_id;
				$params[':template'] = $folder_path."/".$ImageName;
				$result = la_do_query($query,$params,$dbh);
				$template_id = $dbh->lastInsertId();
				$response = $ImageName."|||".$template_id;
				
				$query_get 	= "select form_upload_template from ".LA_TABLE_PREFIX."forms where form_id = :form_id";
				$params_get = array();
				$params_get[':form_id'] = $form_id;
				$result_get = la_do_query($query_get,$params_get,$dbh);
				$row_get = la_do_fetch_result($result_get);
				
				$tmpArr = array();
				if(!empty($row_get['form_upload_template'])){
					if(strpos($row_get['form_upload_template'], ",") !== false){
						$tmpArr = explode(",", $row_get['form_upload_template']);
					}else{
						$tmpArr[] = $row_get['form_upload_template'];
					}
				}
				array_push($tmpArr, $response);
					
				$form_upload_template = implode(",", $tmpArr);
				$query_sv 	= "update ".LA_TABLE_PREFIX."forms set form_upload_template = '".$form_upload_template."' where form_id = :form_id";
				$params_sv = array();
				$params_sv[':form_id'] = $form_id;
				la_do_query($query_sv,$params_sv,$dbh);
				
				array_push($responseArr, $response);
			}				
		}
		
		$response_data = new stdClass();
		$response_data->imagesNames = implode(",", $responseArr);
		$response_data->csrf_token  = $_SESSION['csrf_token'];
	
		$response_json = json_encode($response_data);
		echo $response_json;
	}
}
?>