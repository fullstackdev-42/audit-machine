<?php

/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
*********************************************************************************/
 
require('includes/init.php');

require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/filter-functions.php');
require('lib/swift-mailer/swift_required.php');
require('lib/password-hash.php');

$ssl_suffix = la_get_ssl_suffix();

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
if(isset($_POST['action'])){
	if($_POST['action'] == "search"){

		//Get a list of all subscribed forms
		$client_id = $_SESSION['la_client_client_id'];
		$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);
		$inQuery = implode(',', array_fill(0, count($userEntities), '?'));

		$appendWhereClause = $_POST['search_by'] == "title" ? " AND `".LA_TABLE_PREFIX."forms`.`form_name` LIKE CONCAT('%', ?, '%')" : "";
		$bindParam = $_POST['search_by'] == "title" ? array(1, la_sanitize($_POST['search_value'])) : array(1);
		// print_r($bindParam);

		$query = "SELECT DISTINCT `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`, `".LA_TABLE_PREFIX."forms`.`form_name`, `form_description`, `form_theme_id` 
				  FROM `".LA_TABLE_PREFIX."forms` 
				  LEFT JOIN `".LA_TABLE_PREFIX."ask_client_forms` ON (`".LA_TABLE_PREFIX."forms`.`form_id` = `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`) 
				  WHERE `".LA_TABLE_PREFIX."ask_client_forms`.`client_id` IN ({$inQuery})
				  AND `".LA_TABLE_PREFIX."forms`.`form_active` = ?
				  AND `".LA_TABLE_PREFIX."ask_client_forms`.`form_id` = `".LA_TABLE_PREFIX."forms`.`form_id` {$appendWhereClause}";

		$sth2 = $dbh->prepare($query);

		try{
			$sth2->execute(array_merge($userEntities, $bindParam));
		}catch(PDOException $e) {
			echo $e->getMessage();
			exit;
		}

		$count = $sth2->rowCount();
		$user_subscribed_forms = array();
		$user_forms = array();

        for($i=0;$i<$count;$i++){
            $user_subscribed_forms_temp		= la_do_fetch_result($sth2);
            // print_r($user_subscribed_forms_temp);

            $formEntities = getFormAccessibleEntities($dbh, $user_subscribed_forms_temp['form_id']);

            if(!in_array("0", $formEntities)){
                $hasAccess = false;

                foreach($userEntities as $k => $v){
                    if(in_array($v, $formEntities)){
                        $hasAccess = true;
                    }
                }

                if($hasAccess){
                    $user_subscribed_forms[$i] = array();
                    $user_subscribed_forms[$i] = array('form_id' => $user_subscribed_forms_temp['form_id'], 'form_name' => $user_subscribed_forms_temp['form_name'], 'form_description' => $user_subscribed_forms_temp['form_description'], 'form_theme_id' => $user_subscribed_forms_temp['form_theme_id']);
                    array_push($user_forms, $user_subscribed_forms_temp['form_id']);
                }
            } else {
                $user_subscribed_forms[$i] = array();
                $user_subscribed_forms[$i] = array('form_id' => $user_subscribed_forms_temp['form_id'], 'form_name' => $user_subscribed_forms_temp['form_name'], 'form_description' => $user_subscribed_forms_temp['form_description'], 'form_theme_id' => $user_subscribed_forms_temp['form_theme_id']);
            }
        }
		
		if(count($user_subscribed_forms) > 0){
			if($_POST['search_by'] == "title"){
				echo json_encode(array("result" => $user_subscribed_forms, 'forms_elements' => array(), "error" => 0, "message" => ""));
			}else{
				$form_id_arr = array_map(function($a){
					return $a['form_id'];
				}, $user_subscribed_forms);
				
				$query = "select `form_id`, `element_title`, `element_type`, `element_position`, `element_page_number` from `".LA_TABLE_PREFIX."form_elements` where `form_id` in (".join(',', array_fill(0, count($form_id_arr), '?')).") AND `element_title` LIKE CONCAT('%', ?, '%')";
				
				$sth = $dbh->prepare($query);
				
				try{
					$search_value = la_sanitize($_POST['search_value']);
					$sth->execute(array_merge($form_id_arr, array($search_value)));
				}catch(PDOException $e){
					echo $e->getMessage();
					exit;
				}
				$user_filtered_forms = array();
				
				while($user_filtered_forms_temp = la_do_fetch_result($sth)){		
					if (!in_array($user_filtered_forms_temp['form_id'], $user_filtered_forms)) {
						$user_filtered_forms[] = $user_filtered_forms_temp['form_id'];
					}
				}
				for ($c = 0; $c < count($user_subscribed_forms); $c++) {
					if (!in_array($user_subscribed_forms[$c]['form_id'], $user_filtered_forms)) {
						array_splice($user_subscribed_forms, $c, 1);
					}
				}

				if (count($user_subscribed_forms) == 0) {
                    echo json_encode(array("result" => array(), 'forms_elements' => array(), "error" => 1, "message" => "<div class='middle_form_bar'><h3>No form found</h3><div style='height: 0px; clear: both;'></div></div>"));
                } else {
                    echo json_encode(array("result" => $user_subscribed_forms, 'forms_elements' => $user_filtered_forms, "error" => 0, "message" => ""));
                }
			}
		}else{
			echo json_encode(array("result" => array(), 'forms_elements' => array(), "error" => 1, "message" => "<div class='middle_form_bar'><h3>No form found</h3><div style='height: 0px; clear: both;'></div></div>"));
		}
		
	} else if ($_POST['action'] == 'get_uploaded_files') {
		//get a list of all files previously uploaded into all forms by the entity
		// select all forms whose status is 1
		$existing_files = array();
		$company_id = $_SESSION["la_client_entity_id"];
		$query_forms = "SELECT `form_id`, `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_active` = ?";
		$sth_forms = la_do_query($query_forms, array(1), $dbh);
		while($row_form = la_do_fetch_result($sth_forms)){
			//check if form_{form_id} table exists or not			
			$temp_form_id = $row_form["form_id"];
			$queryFormTable = $dbh->query("SHOW TABLES LIKE 'ap_form_{$temp_form_id}'");
			if($queryFormTable) {
				//get only upload files, not synced files
				$query_files = "SELECT f.data_value, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto FROM `".LA_TABLE_PREFIX."form_{$temp_form_id}` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.field_name = CONCAT('element_', e.element_id) WHERE e.form_id = ? AND e.element_type = ? AND f.company_id = ? AND f.data_value != ? AND (e.element_machine_code = ? OR e.element_file_upload_synced != ?)";
				$result_files = la_do_query($query_files, array($temp_form_id, "file", $company_id, "", "", 1), $dbh);
				while ($row_file = la_do_fetch_result($result_files)) {
					$files = explode("|", $row_file["data_value"]);
					foreach ($files as $file_name) {
						$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$temp_form_id}/files/{$file_name}";
						if(file_exists($file_complete_path)) {
							$encoded_file_name = urlencode($file_name);							
							$filename_explode1 = explode('-', $file_name, 2);
							$display_filename = $filename_explode1[1];
							$file_ext   = end(explode(".", $file_name));

							if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
								$data_identifier = "image_format";
								$q_string = $la_settings["base_url"]."data/form_{$temp_form_id}/files/{$file_name}";
								$q_string = str_replace("%", "%25", $q_string);
								$q_string = str_replace("#", "%23", $q_string);
							} else {
								$data_identifier = "other";
								$q_string = base64_encode("form_id={$temp_form_id}&file_name={$encoded_file_name}&call_type=ajax_normal");
							}
							$field_link = "view.php?id={$temp_form_id}";
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
				$query_synced_files = "SELECT f.files_data, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto, e.element_machine_code FROM `".LA_TABLE_PREFIX."file_upload_synced` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.element_machine_code = e.element_machine_code WHERE e.form_id = ? AND e.element_type = ? AND f.company_id = ? AND f.files_data != ? AND e.element_machine_code != ? AND e.element_file_upload_synced = ?";
				$result_synced_files = la_do_query($query_synced_files, array($temp_form_id, "file", $company_id, "", "", 1), $dbh);
				while ($row_synced_file = la_do_fetch_result($result_synced_files)) {
					$files = json_decode($row_synced_file['files_data']);
					foreach ($files as $file_name) {
						$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
						if(file_exists($file_complete_path)) {
							$encoded_file_name = urlencode($file_name);
							$filename_explode1 = explode('-', $file_name, 2);
							$display_filename = $filename_explode1[1];
							$file_ext   = end(explode(".", $file_name));

							if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
								$data_identifier = "image_format";
								$q_string = $la_settings["base_url"]."data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
								$q_string = str_replace("%", "%25", $q_string);
								$q_string = str_replace("#", "%23", $q_string);
							} else {
								$data_identifier = "other";
								$q_string = base64_encode("element_machine_code={$row_synced_file['element_machine_code']}&file_name={$encoded_file_name}&call_type=ajax_synced");
							}
							$field_link = "view.php?id={$temp_form_id}";
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
		$query_reports = "SELECT `docx`.*, `form`.`form_name` FROM
							(SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `docx_id` IN 
								(SELECT MAX(docx_id) FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `isZip` = ? AND company_id = ? GROUP BY `form_id`)
							) AS `docx`
						LEFT JOIN `".LA_TABLE_PREFIX."forms` AS `form` ON (`docx`.`form_id` = `form`.`form_id`)
						WHERE `form`.`form_active` = ?";
		$sth_reports = la_do_query($query_reports, array(1, $company_id, 1), $dbh);
		while($row_report = la_do_fetch_result($sth_reports)) {
			if( !empty($row_report['added_files']) ) {
				$added_files = explode(',', $row_report['added_files']);
				foreach ($added_files as $docxname) {
					if(!empty($docxname) && file_exists($_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}")) {
						$target_file = $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
						$q_string = base64_encode("file_path={$target_file}&file_name={$docxname}&form_id={$row_report['form_id']}&document_preview=1");
						array_push($generated_documents, array("form_id" => $row_report["form_id"], "form_name" => $row_report["form_name"], "file_path" => "../portal/template_output/{$docxname}", "display_filename" => $docxname, "data_identifier" => "other", "file_ext" => "docx", "q_string" => $q_string));
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
	} else if ($_POST['action'] == 'check_if_form_has_entry') {
		$form_id = $_POST['form_id'];
		$query = "SELECT MAX(`entry_id`) AS `latest_entry_id` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? GROUP BY `entry_id`";
		$sth = la_do_query($query, array($_SESSION['la_client_entity_id']), $dbh);
		$row = la_do_fetch_result($sth);
		if(!empty($row)) {
			$response_data = new stdClass();
			$response_data->entry_id = $row['latest_entry_id'];
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		} else {
			$response_data = new stdClass();
			$response_data->entry_id = 0;
			$response_json = json_encode($response_data);
			echo $response_json;
			exit();
		}
	}
}