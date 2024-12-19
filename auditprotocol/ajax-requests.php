<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
require('includes/init.php');

require('config.php');

require_once("../policymachine/classes/CreateDocx.php");
require_once("../policymachine/classes/CreateDocxFromTemplate.php");

require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/docxhelper-functions.php');
require('includes/post-functions.php');
require('includes/check-session.php');

require('includes/filter-functions.php');
require('includes/entry-functions.php');
require('includes/users-functions.php');
require('lib/swift-mailer/swift_required.php');


require_once("../itam-shared/includes/helper-functions.php");



$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

function getFormAccessibleEntities($dbh, $form_id){
	$entities = array();
	
	$query = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ?";
	$sth = la_do_query($query, array($form_id), $dbh);
	
	while($row = la_do_fetch_result($sth)){
		array_push($entities, $row['entity_id']);
	}

	return $entities;
}

//check permission, is the user allowed to access this page?
if(empty($_SESSION["is_examiner"]) && empty($_SESSION['la_user_privileges']['priv_administer'])){
	$form_id = (int) trim($_POST['form_id']);
	$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

	//this page need edit_form permission
	if(empty($user_perms['edit_form'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

		$ssl_suffix = la_get_ssl_suffix();
		$response['error'] = 1;
		$response['redirect'] = "http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/restricted.php";
		$response['error_message'] = 'Permission Issue';
		echo json_encode($response);
		exit;
	}
}

if( $_POST['action'] == 'field_embed_content' ) {
	$form_id = (int) trim($_POST['form_id']);
	$field_selected = trim($_POST['field_selected']);
	$embed_selected = trim($_POST['embed_selected']);
	$element_page_number = trim($_POST['element_page_number']);
	$embed_code_type_user = trim($_POST['embed_code_type_user']);
	
	if( empty( $form_id ) || empty($field_selected) || empty($embed_selected) || empty($element_page_number) || empty($embed_code_type_user) ) {
		$response['error'] = 1;
		$response['error_message'] = 'All required fields not supplied';
		echo json_encode($response);
		exit;
	}


	//get form properties
	$query 	= "select 
					form_name,
					form_frame_height,
					form_captcha
			    from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){
		$row['form_name'] = la_trim_max_length($row['form_name'],65);
		
		$form_name 	= htmlspecialchars($row['form_name']);
		$form_frame_height  = (int) $row['form_frame_height'];

		if(empty($row['form_captcha'])){
			$form_frame_height += 80;
		}else{
			$form_frame_height += 250;
		}
	}


	//create link
	$response['success']  = 1;
	$embed_extra_info = ' Copy and Paste the Code Below into Your Website Page.';


	//check if we are creating code for admin or portal
	if( $embed_code_type_user == 'admin' ) {
		$form_base_url = $la_settings['base_url'];
		//construct iframe code
		$iframe_src = $form_base_url.'embed.php?id='.$form_id.'&userid='.base64_encode($_SESSION['la_user_id']).'&element_id_auto='.$field_selected;
			
	} else {
		$host = str_replace('auditprotocol/','',$la_settings['base_url']);
		$form_base_url = $host.'portal/';

		//construct iframe code
		$iframe_src = $form_base_url.'view.php?id='.$form_id.'&element_id_auto='.$field_selected;
	}

	if( $element_page_number > 1 ) {
		$iframe_src .= '&la_page='.$element_page_number;
	}

	//construct simple link code
	$simple_link_form_code = '<a href="'.$iframe_src.'" title="'.$form_name.'">'.$form_name.'</a>';

	//construct popup link code
	if($form_frame_height > 750){
		$popup_height = 750;
	}else{
		$popup_height = $form_frame_height;
	}
	$popup_link_form_code = '<a href="'.$$iframe_src.'" onclick="window.open(this.href,  null, \'height='.$popup_height.', width=800, toolbar=0, location=0, status=0, scrollbars=1, resizable=1\'); return false;">'.$form_name.'</a>';


	if( $embed_selected == 'iframe' ) {
		$response['embed_main_title'] = 'Iframe Code';
		$response['embed_extra_info'] = "This code will insert the form into your existing web page seamlessly. Thus the form background, border and logo header won't be displayed. You might also need to adjust the iframe height value.".$embed_extra_info;

		//construct iframe code
		$iframe_form_code = '<iframe height="'.$form_frame_height.'" allowTransparency="true" frameborder="0" scrolling="no" style="width:100%;border:none" src="'.$iframe_src.'" title="'.$form_name.'"></iframe>';
		$response['embed_textarea_content'] = $iframe_form_code;
	} else if ( $embed_selected == 'simple_link' ) {
		$response['embed_main_title'] = 'Simple Link';
		$response['embed_extra_info'] = "This code will display direct link to your form. Use this code to share your form with others through emails or web pages.".$embed_extra_info;
		$response['embed_textarea_content'] = $simple_link_form_code;

	} else if ( $embed_selected == 'popup_link' ) {
		$response['embed_main_title'] = 'Popup Link';
		$response['embed_extra_info'] = "This code will display your form into a popup window.".$embed_extra_info;

		$response['embed_textarea_content'] = $popup_link_form_code;
	}

	echo json_encode($response);
	exit;
} else if( $_POST['action'] == 'generate_entry_document' ) {
	$form_id = $_POST['form_id'];
	$la_user_id = $_POST['la_user_id'];
	$company_user_id = $_POST['company_user_id'];
	$entry_id = $_POST['entry_id'];
	//set status to In progress
	updateDocumentProcessStatus($dbh, $form_id, $company_user_id, $entry_id, 2);

	$props = array('form_enable_template_wysiwyg', 'form_template_wysiwyg_id');
	$form_properties = la_get_form_properties($dbh,$form_id,$props);

	$props = array('dbh' => $dbh, 'form_id' => $form_id, 'la_user_id' => $la_user_id, 'company_user_id' => $company_user_id, 'entry_id' => $entry_id, 'called_from' => 'ajax');
	
	$zipPath = getElementWithValueArray($props);
	if( !empty($zipPath) ) {
		$response['success']  = 1;

		//update all rows status in table background_document_proccesses to 1 for this entry
		updateDocumentProcessStatus($dbh, $form_id, $company_user_id, $entry_id, 1);
	} else {
		$response['error']  = 1;
	}
	echo json_encode($response);
	exit();
} else if( $_POST['action'] == 'get_submitted_forms_by_entity' ) {
	$response_data = new stdClass();
	$form_array = array();

	$company_id = $_POST['company_id'];
	if($company_id == 0) {
		$query1 = "SELECT `form_id`, `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_active` = 1";			
		$sth1 = la_do_query($query1, array(), $dbh);
		while($row = la_do_fetch_result($sth1)){
			array_push($form_array, array('form_id' => $row['form_id'], 'form_name' => $row['form_name']));
		}
	} else {
		$query1 = "SELECT DISTINCT `".LA_TABLE_PREFIX."entity_form_relation`.`form_id`, `".LA_TABLE_PREFIX."forms`.`form_name` FROM `".LA_TABLE_PREFIX."forms` LEFT JOIN `".LA_TABLE_PREFIX."entity_form_relation` ON (`".LA_TABLE_PREFIX."forms`.`form_id` = `".LA_TABLE_PREFIX."entity_form_relation`.`form_id`) WHERE (`".LA_TABLE_PREFIX."entity_form_relation`.`entity_id` = {$company_id} OR `".LA_TABLE_PREFIX."entity_form_relation`.`entity_id` = 0) AND `".LA_TABLE_PREFIX."forms`.`form_active` = 1";			
		$sth1 = la_do_query($query1, array(), $dbh);
		while($row = la_do_fetch_result($sth1)){
			$form_id = $row["form_id"];
			$queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$form_id}'";
			$resultFormTable = la_do_query($queryFormTable, array(), $dbh);
			$rowFormTable    = la_do_fetch_result($resultFormTable);
			if($rowFormTable) {
				$query = "SELECT count(*) AS `total` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE company_id = ?";
				$sth = la_do_query($query, array($company_id), $dbh);
				$res = la_do_fetch_result($sth);
				if($res["total"] > 0) {
					array_push($form_array, array('form_id' => $row['form_id'], 'form_name' => $row['form_name']));
				}
			}
		}
	}

	$response_data->status = "ok";
	$response_data->forms = $form_array;
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
} else if( $_POST['action'] == 'get_field_data' ) {
	$response_data = new stdClass();
	$form_id = $_POST['form_id'];
	$fields = array();

	$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
	$field_result = la_do_query($field_query, array($form_id), $dbh);

	while($field_row = la_do_fetch_result($field_result)){
		array_push($fields, array("element_id" => $field_row['element_id'], "element_type" => $field_row['element_type'], "element_title" => $field_row['element_title']));
	}

	$response_data->status = "ok";
	$response_data->fields = $fields;
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
} else if( $_POST['action'] == 'get_uploaded_files' ) {
	//get a list of all files previously uploaded into all forms by the entity
	// select all forms whose status is 1
	$existing_files = array();
	$query_forms = "SELECT `form_id`, `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_active` = ?";
	$sth_forms = la_do_query($query_forms, array(1), $dbh);
	while($row_form = la_do_fetch_result($sth_forms)){
		//check if form_{form_id} table exists or not
		$temp_form_id = $row_form["form_id"];
		$queryFormTable = $dbh->query("SHOW TABLES LIKE 'ap_form_{$temp_form_id}'");
		if($queryFormTable) {
			//get only upload files, not synced files
			$query_files = "SELECT f.company_id, f.data_value, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto FROM `".LA_TABLE_PREFIX."form_{$temp_form_id}` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.field_name = CONCAT('element_', e.element_id) WHERE e.form_id = ? AND e.element_type = ? AND f.data_value != ? AND (e.element_machine_code = ? OR e.element_file_upload_synced != ?)";
			$result_files = la_do_query($query_files, array($temp_form_id, "file", "", "", 1), $dbh);
			while ($row_file = la_do_fetch_result($result_files)) {
				
				$files = explode("|", $row_file["data_value"]);
				foreach ($files as $file_name) {
					$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$temp_form_id}/files/{$file_name}";
					if(file_exists($file_complete_path)) {
						$encoded_file_name = urlencode($file_name);
						$filename_explode1 = explode('-', $file_name, 2);
						$display_filename = $filename_explode1[1];
						$file_ext   = end(explode(".", $file_name));

						//get entry_id based on the company_id
						$tmp_entry_id = 0;
						$temp_entry_id = 0;
						$query_entry_id = "SELECT DISTINCT(company_id) FROM `".LA_TABLE_PREFIX."form_{$temp_form_id}`";
						$sth_entry_id = la_do_query($query_entry_id, array(), $dbh);
						while($row_entry = la_do_fetch_result($sth_entry_id)) {
							$temp_entry_id += 1;
							if ($row_entry["company_id"] == $row_file["company_id"]) {
								$tmp_entry_id = $temp_entry_id;
							}
						}
						
						if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
							$data_identifier = "image_format";
							$q_string = $la_settings["base_url"]."data/form_{$temp_form_id}/files/{$file_name}";
							$q_string = str_replace("%", "%25", $q_string);
							$q_string = str_replace("#", "%23", $q_string);
						} else {
							$data_identifier = "other";
							$q_string = base64_encode("form_id={$temp_form_id}&file_name={$encoded_file_name}&call_type=ajax_normal");
						}
						$field_link = "edit_entry.php?form_id={$temp_form_id}&entry_id={$tmp_entry_id}";
						if( isset($row_file['element_page_number']) && $row_file['element_page_number'] > 1 ) {
							$field_link.= "&la_page=".$row_file['element_page_number'];
						}
						if( !empty($row_file['element_id_auto']) ) {
							$field_link.= "&element_id_auto=".$row_file['element_id_auto'];
						}
						array_push($existing_files, array("form_id" => $temp_form_id, "form_name" => $row_form["form_name"], "element_id" => $row_file["element_id"], "element_title" => $row_file["element_title"], "file_path" => "data/form_{$temp_form_id}/files/{$file_name}", "display_filename" => $display_filename, "data_identifier" => $data_identifier, "file_ext" => $file_ext, "q_string" => $q_string, "field_link" => $field_link));
					}
				}
			}

			//get synced files
			$query_synced_files = "SELECT f.company_id, f.files_data, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto, e.element_machine_code FROM `".LA_TABLE_PREFIX."file_upload_synced` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.element_machine_code = e.element_machine_code WHERE e.form_id = ? AND e.element_type = ? AND f.files_data != ? AND e.element_machine_code != ? AND e.element_file_upload_synced = ?";
			$result_synced_files = la_do_query($query_synced_files, array($temp_form_id, "file", "", "", 1), $dbh);
			while ($row_synced_file = la_do_fetch_result($result_synced_files)) {
				$files = json_decode($row_synced_file['files_data']);
				foreach ($files as $file_name) {
					$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
					if(file_exists($file_complete_path)) {
						$encoded_file_name = urlencode($file_name);
						$filename_explode1 = explode('-', $file_name, 2);
						$display_filename = $filename_explode1[1];
						$file_ext   = end(explode(".", $file_name));

						//get entry_id based on the company_id
						$entry_id = 0;
						$temp_entry_id = 0;
						$query_entry_id = "SELECT DISTINCT(company_id) FROM `".LA_TABLE_PREFIX."form_{$temp_form_id}`";
						$sth_entry_id = la_do_query($query_entry_id, array(), $dbh);
						while($row_entry = la_do_fetch_result($sth_entry_id)) {
							$temp_entry_id += 1;
							if ($row_entry["company_id"] == $row_synced_file["company_id"]) {
								$entry_id = $temp_entry_id;
							}
						}

						if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
							$data_identifier = "image_format";
							$q_string = $la_settings["base_url"]."data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
							$q_string = str_replace("%", "%25", $q_string);
							$q_string = str_replace("#", "%23", $q_string);
						} else {
							$data_identifier = "other";
							$q_string = base64_encode("element_machine_code={$row_synced_file['element_machine_code']}&file_name={$encoded_file_name}&call_type=ajax_synced");
						}
						$field_link = "edit_entry.php?form_id={$temp_form_id}&entry_id={$entry_id}";
						if( isset($row_synced_file['element_page_number']) && $row_synced_file['element_page_number'] > 1 ) {
							$field_link.= "&la_page=".$row_synced_file['element_page_number'];
						}
						if( !empty($row_synced_file['element_id_auto']) ) {
							$field_link.= "&element_id_auto=".$row_synced_file['element_id_auto'];
						}
						array_push($existing_files, array("form_id" => $temp_form_id, "form_name" => $row_form["form_name"], "element_id" => $row_synced_file["element_id"], "element_title" => $row_synced_file["element_title"], "file_path" => "data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}", "display_filename" => $display_filename, "data_identifier" => $data_identifier, "file_ext" => $file_ext, "q_string" => $q_string, "field_link" => $field_link));
					}
				}
			}
		}
	}

	//get a list of generated documents
	$generated_documents = array();
	$query_reports = "SELECT `docx`.*, `form`.`form_name`, `company`.`company_name` FROM
						(SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `docx_id` IN 
							(SELECT MAX(docx_id) FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `isZip` = ? GROUP BY `company_id`, `form_id`)
						) AS `docx`
					LEFT JOIN `".LA_TABLE_PREFIX."forms` AS `form` ON (`docx`.`form_id` = `form`.`form_id`)
					LEFT JOIN `".LA_TABLE_PREFIX."ask_clients` AS `company` ON (`docx`.`company_id` = `company`.`client_id`) WHERE `form`.`form_active` = ?";
	$sth_reports = la_do_query($query_reports, array(1, 1), $dbh);
	while($row_report = la_do_fetch_result($sth_reports)) {
		if(!empty($row_report['company_name'])) {
			if( !empty($row_report['added_files']) ) {
				$added_files = explode(',', $row_report['added_files']);
				foreach ($added_files as $docxname) {
					if(!empty($docxname) && file_exists($_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}")) {
						$target_file = $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
						$q_string = base64_encode("file_path={$target_file}&file_name={$docxname}&form_id={$row_report['form_id']}&document_preview=1");
						array_push($generated_documents, array("form_id" => $row_report["form_id"], "form_name" => $row_report["form_name"], "file_path" => "../portal/template_output/{$docxname}", "display_filename" => $docxname, "data_identifier" => "other", "file_ext" => "docx", "q_string" => $q_string, "report_for" => $row_report['company_name']));
					}
				}
			}
		} else {
			if(strlen($row_report['company_name']) == 10) {
				if( !empty($row_report['added_files']) ) {
					$added_files = explode(',', $row_report['added_files']);
					foreach ($added_files as $docxname) {
						if(!empty($docxname) && file_exists($_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}")) {
							$target_file = $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
							$q_string = base64_encode("file_path={$target_file}&file_name={$docxname}&form_id={$row_report['form_id']}&document_preview=1");
							array_push($generated_documents, array("form_id" => $row_report["form_id"], "form_name" => $row_report["form_name"], "file_path" => "../portal/template_output/{$docxname}", "display_filename" => $docxname, "data_identifier" => "other", "file_ext" => "docx", "q_string" => $q_string, "report_for" => "Admin"));
						}
					}
				}
			}
		}
	}

	$response_data = new stdClass();
	$response_data->status = "ok";
	$response_data->uploaded_files = $existing_files;
	$response_data->generated_documents = $generated_documents;
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
} else if( $_POST['action'] == 'get_form_fields' ) {
	$form_id = (int) $_POST['form_id'];

	$query = "select 
		element_id,
		if(element_type = 'matrix',element_guidelines,element_title) element_title,
		element_type,
		element_page_number,
		element_position,
		element_machine_code
	from 
		" . LA_TABLE_PREFIX . "form_elements 
	where 
		form_id = ? and 
		element_status = 1 and 
		element_is_private = 0 and 
		element_type <> 'page_break' and 
		element_type <> 'casecade_form' and 
		element_matrix_parent_id = 0 
	order by 
		element_position asc";
	$params = array($form_id);
	$sth = la_do_query($query, $params, $dbh);

	$field_titles = [];
	$machine_codes = [];
	
	while ($row = la_do_fetch_result($sth)) {
		$element_page_number = (int) $row['element_page_number'];
		$element_id          = (int) $row['element_id'];
		$element_title = noHTML($row['element_title']);
		$element_position 	 = (int) $row['element_position'] + 1;

		if (empty($element_title)) {
			$element_title = '-untitled field-';
		}

		if (strlen($element_title) > 120) {
			$element_title = substr($element_title, 0, 120) . '...';
		}

		$field_titles[$element_page_number][$element_id]['element_title'] = $element_position . '. ' . $element_title;

		if(!empty($row['element_machine_code'])) {
			$machine_codes[$element_id] = $row['element_machine_code'];
		}
	}
	if( count($machine_codes) > 0 ) {
		$response = [
			'success' => 1,
			'field_titles' => $field_titles,
			'machine_codes' => $machine_codes
		];
	} else {
		$response = [
			'error' => 1
		];
	}
	echo json_encode($response);
	exit();
} else if ($_POST['action'] == 'search_element') {
	$result = "";
	$form_id = $_POST['form_id'];
	$search_keyword = $_POST['search_keyword'];
	$query_elements = "SELECT `id`, `element_page_number`, `element_title` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_title` LIKE '%{$search_keyword}%' AND element_type != 'section' AND element_type != 'casecade_form'";
	$result_elements = la_do_query($query_elements, array($form_id), $dbh);
	while ($row_element = la_do_fetch_result($result_elements)) {
		$field_link = "{$form_id}&la_page={$row_element['element_page_number']}&element_id_auto={$row_element['id']}";
		$result .= '<li class="go-to-field-li" data-field-link="'.$field_link.'">'.$row_element["element_title"].'</li>';
	}
	//get elements in cascaded sub forms
	$query_forms = "SELECT `element_page_number`, `element_position`, `element_default_value` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` = ?";
	$result_forms = la_do_query($query_forms, array($form_id, "casecade_form"), $dbh);
	while ($row_form = la_do_fetch_result($result_forms)) {
		$query_elements = "SELECT `id`, `element_page_number`, `element_title` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_title` LIKE '%{$search_keyword}%' AND element_type != 'section' AND element_type != 'casecade_form'";
		$result_elements = la_do_query($query_elements, array($row_form["element_default_value"]), $dbh);
		while ($row_element = la_do_fetch_result($result_elements)) {
			$field_link = "{$form_id}&la_page={$row_form['element_page_number']}&casecade_form_page_number={$row_element['element_page_number']}&casecade_element_position={$row_form['element_position']}&element_id_auto={$row_element['id']}";
			$result .= '<li class="go-to-field-li" data-field-link="'.$field_link.'">'.$row_element["element_title"].'</li>';
		}
	}
	echo $result;
} else if ($_POST['action'] == 'toggle_audit_form_entries') {
	$form_id = (int) trim($_POST['form_id']);
	$company_id = (int) trim($_POST['company_id']);
	$entry_id = (int) trim($_POST['entry_id']);
	$audit = (int) trim($_POST['audit']);

	$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES ";
	$query .= "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)";
	$query .= " ON DUPLICATE KEY update `data_value` = values(`data_value`);";

	$params = array($company_id, $entry_id, "audit", "", $audit, "0", "");
	la_do_query($query, $params, $dbh);
	$response = [ 'success' => 1 ];
	echo json_encode($response);
	exit();
} else if ($_POST['action'] == 'delete_entry_backup_from_server') {
	if(!empty($_POST['entry_id']) && !empty($_POST['form_id']) && !empty($_POST['path_to_file'])) {
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_{$_POST['form_id']}_saved_entries` WHERE id=?";
		la_do_query($query, array($_POST['entry_id']), $dbh);
		// delete file from server
		unlink($_SERVER['DOCUMENT_ROOT']."/auditprotocol{$_POST['path_to_file']}");
		$_SESSION['LA_SUCCESS'] = "The entry data has been deleted from the server successfully.";
		echo "success";

	} else {
		echo "Unable to delete the entry data from the server. Please try again later.";
	}
} else if($_POST['action'] == 'change_all_status') {
	$form_id = $_POST["form_id"];
	$company_id = $_POST["company_id"];
	$entry_id = $_POST["entry_id"];
	$status = (int) $_POST["status"];
	$form_ids = array($form_id);

	//get cascaded sub form IDs
	$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' ORDER BY element_position ASC";
	$sth = la_do_query($query, array($form_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($form_ids, (int) $row['element_default_value']);
	}
	$form_id_string = implode(",", $form_ids);
	$element_type_array = array("'text'", "'textarea'", "'file'", "'radio'", "'checkbox'", "'select'", "'signature'", "'matrix'");
	$element_type_string = implode(",", $element_type_array);

	//delete existing status indicators
	$query_delete = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `company_id`=? AND `entry_id` = ? AND `form_id` IN ({$form_id_string})";
	la_do_query($query_delete, array($company_id, $entry_id), $dbh);

	//get element_id of the elements from each form and insert new status indicator
	$query_element = "SELECT form_id, element_id FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` IN ({$form_id_string}) AND `element_type` IN ({$element_type_string})";
	$sth_element = la_do_query($query_element, array(), $dbh);
	while ($row_element = la_do_fetch_result($sth_element)) {
		$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?)";
		la_do_query($query_insert, array($row_element["form_id"], $row_element["element_id"], $company_id, $entry_id, $status), $dbh);
	}
	$response_data = new stdClass();
	$response_data->status = "ok";
	$response_json = json_encode($response_data);
	echo $response_json;
	exit();
} else if($_POST['action'] == 'delete_wysiwyg_template') {
	$template_id = $_POST["template_id"];
	if(!empty($template_id)) {
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_templates` WHERE id = ?";
		la_do_query($query, array($template_id), $dbh);

		$_SESSION['LA_SUCCESS'] = "The template has been deleted successfully.";
		$response_data = new stdClass();
		$response_data->status = "ok";
		$response_json = json_encode($response_data);
		echo $response_json;
	} else {
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->msg = "The template doesn't exist.";
		$response_json = json_encode($response_data);
		echo $response_json;
	}
} else if($_POST['action'] == 'import_wysiwyg_template') {
	$template_name = trim($_POST["template_name"]);
	if($template_name == "") {
		$template_name = "Untitled Template";
	}
	$template_content = trim($_POST["template_content"]);

	if($template_content == "") {
		$response_data = new stdClass();
		$response_data->status = "error";
		$response_data->msg = "The template is empty.";
		$response_json = json_encode($response_data);
		echo $response_json;
	} else {
		$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_templates` (`name`, `data`) VALUES (?, ?)";
		la_do_query($query_insert, array($template_name, $template_content), $dbh);

		$_SESSION['LA_SUCCESS'] = "The template has been imported successfully.";
		$response_data = new stdClass();
		$response_data->status = "ok";
		$response_json = json_encode($response_data);
		echo $response_json;
	}
}
?>