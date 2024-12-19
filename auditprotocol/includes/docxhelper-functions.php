<?php
	
ini_set('max_execution_time', '95000');


function isUrlExists($url){ 
	$headers = get_headers($url);
	return stripos($headers[0], "200 OK") ? true : false;
}

function is_url_exist($url){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	if($code >= 200 && $code <= 400){
		$status = true;
	}else{
		$status = false;
	}
	
	curl_close($ch);
	return $status;
}

function generateCascadeData($parameter=array()) {
	$dbh = $parameter['dbh'];
	$la_settings = $parameter['la_settings'];
	$form_id = $parameter['form_id'];
	$client_id = $parameter['client_id'];
	$company_id = $parameter['company_id'];
	$entry_id = $parameter['entry_id'];

	$element_array = array();
	$replace_data_array = array();

	// fetch data from dynamic form table
	$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id = ? AND entry_id = ?";
	$param_forms = array('element_%', $company_id, $entry_id);
	$result_forms = la_do_query($query_forms,$param_forms,$dbh);

	while($row = la_do_fetch_result($result_forms)){
		$row_forms[$row['field_name']] = $row['data_value'];
	}

	$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value`, `element_enhanced_checkbox`, `element_enhanced_multiple_choice`, `element_choice_other_label`, `element_choice_other_score`, `element_choice_other_icon_src`, `element_file_upload_synced` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` != 'section' AND `element_type` != 'page_break' AND `element_type` != 'syndication' AND (`element_machine_code` != '' OR `element_type` = 'casecade_form') ORDER BY element_position ASC";
	$result_form_element = la_do_query($query_form_element, array($form_id), $dbh);
	
	while($row_form_element = la_do_fetch_result($result_form_element)){
		$element_array[$row_form_element['element_id']] = array();
		$element_array[$row_form_element['element_id']]['element_type'] = $row_form_element['element_type'];
		$element_array[$row_form_element['element_id']]['element_machine_code'] = $row_form_element['element_machine_code'];
		$element_array[$row_form_element['element_id']]['element_matrix_allow_multiselect'] = $row_form_element['element_matrix_allow_multiselect'];
		$element_array[$row_form_element['element_id']]['element_matrix_parent_id'] = $row_form_element['element_matrix_parent_id'];
		$element_array[$row_form_element['element_id']]['element_id'] = $row_form_element['element_id'];
		$element_array[$row_form_element['element_id']]['element_default_value'] = $row_form_element['element_default_value'];
		$element_array[$row_form_element['element_id']]['element_enhanced_checkbox'] = $row_form_element['element_enhanced_checkbox'];
		$element_array[$row_form_element['element_id']]['element_enhanced_multiple_choice'] = $row_form_element['element_enhanced_multiple_choice'];
		$element_array[$row_form_element['element_id']]['element_choice_other_label'] = $row_form_element['element_choice_other_label'];
		$element_array[$row_form_element['element_id']]['element_choice_other_score'] = $row_form_element['element_choice_other_score'];
		$element_array[$row_form_element['element_id']]['element_choice_other_icon_src'] = $row_form_element['element_choice_other_icon_src'];
		$element_array[$row_form_element['element_id']]['element_file_upload_synced'] = $row_form_element['element_file_upload_synced'];
	}

	if(count($element_array) > 0){
		$unlink_images = [];
		$timestamp = time();
		$zip_added_files = [];
		$poam_zip_added_files = [];

		//***************F O R M  N A M E ***************
		$query_form  = "SELECT `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = :form_id";
		$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
		$row_form    = la_do_fetch_result($result_form);
		$form_name   = trim($row_form['form_name']);
		$form_name   = str_replace(" ", "_", $form_name);
		$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
		$form_name	 = substr($form_name, 0, 24);
		//***************F O R M  N A M E ***************

		foreach($element_array as $element_id => $element){
			if($element['element_type'] == 'matrix'){
				$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
			}

			if($element['element_type'] == 'simple_name'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']);
			}
			elseif($element['element_type'] == 'address'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']." ".$row_forms['element_'.$element_id.'_3']." ".$row_forms['element_'.$element_id.'_4']." ".$row_forms['element_'.$element_id.'_5']." ".$row_forms['element_'.$element_id.'_6']);
			}
			elseif($element['element_type'] == 'checkbox'){
				$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

				$checkbox_html = "";
				$checkbox_html_for_sheet = "";
				if($element['element_enhanced_checkbox'] == 1){
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
								$icon_src = $element['element_choice_other_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $checkbox_html, 'insertSheet' => $checkbox_html_for_sheet);
			}
			elseif($element['element_type'] == 'radio'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$multiple_choice_html = "";
				$multiple_choice_html_for_sheet = "";
				$other_value_flag = true;
				if($element['element_enhanced_multiple_choice'] == 1) {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							}
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
									$icon_src = $element['element_choice_other_icon_src'];
								} else {
									$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								}
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $multiple_choice_html, 'insertSheet' => $multiple_choice_html_for_sheet);
			}
			elseif($element['element_type'] == 'select'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$row_element_option = la_do_fetch_result($result_element_option);
				
				if(!empty($row_element_option['option'])){
					$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
				}
			}
			elseif($element['element_type'] == 'phone'){
				$phone_val = substr($row_forms['element_'.$element_id],0,3).'-'.substr($row_forms['element_'.$element_id],3,3).'-'.substr($row_forms['element_'.$element_id],6,4);
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
			} 
			elseif($element['element_type'] == 'casecade_form'){
				$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $client_id, 'company_id' => $company_id, 'entry_id' => $entry_id);
				$case_cade_replace_data_array = generateCascadeData($generate_doc_params);
			
				// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
				$replace_data_array = array_merge($replace_data_array, $case_cade_replace_data_array);
			}
			elseif($element['element_type'] == 'file'){
				$file_html = "";
				$file_html_for_sheet = "";
				if($element['element_file_upload_synced'] == 1) {
					$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
					$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_id),$dbh);
					$files_data_row = la_do_fetch_result($files_data_res);
					if( $files_data_row['files_data'] ) {
						$filename_array = json_decode($files_data_row['files_data']);
						foreach ($filename_array as $filename_value) {
							$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
							if(file_exists($file_source)) {
								$file_ext = end(explode(".", $filename_value));
								if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
									$file_src = "/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
									$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
								} else {
									$filename = explode('-', $filename_value, 2)[1];
									$file_html .="<p>{$filename}</p>";
								}
								$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
							}
						}
					}
				} else {
					$filename_array = explode("|", $row_forms['element_'.$element_id]);
					foreach ($filename_array as $filename_value) {
						$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
						if(file_exists($file_source)) {
							$file_ext = end(explode(".", $filename_value));
							if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
								$file_src = "/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
								$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
							} else {
								$filename = explode('-', $filename_value, 2)[1];
								$file_html .="<p>{$filename}</p>";
							}
							$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
						}
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $file_html, 'insertSheet' => $file_html_for_sheet);
			}
			elseif($element['element_type'] == 'signature'){
				//create image from json data
				$signature_html = "";
				if( !empty($row_forms['element_'.$element_id]) ){
					$signature_img_temp = sigJsonToImage($row_forms['element_'.$element_id]);
					if( $signature_img_temp != false ) {
						$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
						$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

						if (!file_exists($destination_dir))
							mkdir($destination_dir, 0777, true);

						$random_string = bin2hex(random_bytes(24));
						$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
						$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
						if( imagepng($signature_img_temp, $image_name) ) {
							$unlink_images[] = $image_name;
						}
						$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
					} else {
						$signature_html .="<p>Unable to load a signature</p>";
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $signature_html, 'insertSheet' => $signature_html);
			}
			elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
				if($row_forms['element_'.$element_id] != "") {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($row_forms['element_'.$element_id])));
				} else {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => "");
				}
			}
			elseif($element['element_type'] == 'textarea'){
				$replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $row_forms['element_'.$element_id]);
			}
			else{
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id]);
			}
		}

		$poam_enabled = false;
		$all_poam_templates = array();
		$poam_templates = array();
		$query = "SELECT `logic_poam_enable` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
		$sth = la_do_query($query, array($form_id), $dbh);
		$row = la_do_fetch_result($sth);
		if($row['logic_poam_enable']) {
			//get all existing poam templates -- we will exclude these templates when generating normal template outputs
			$query_poam_templates = "SELECT DISTINCT t.template_id, t.template FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.target_template_id = t.template_id WHERE p.form_id = ?";
			$sth_poam_templates = la_do_query($query_poam_templates, array($form_id), $dbh);
			while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
				if(file_exists($row_poam_template['template'])) {
					$all_poam_templates[] = $row_poam_template['template'];
					$poam_enabled = true;
				}
			}
		}

		if($poam_enabled) {
			require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
			$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
			$query_poam_target_template = "SELECT DISTINCT p.`target_template_id`, t.`template` FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.`target_template_id` = t.`template_id` WHERE p.`form_id` = ?";
			$sth_poam_target_template = la_do_query($query_poam_target_template, array($form_id), $dbh);
			while ($row_poam_target_template = la_do_fetch_result($sth_poam_target_template)) {
				$target_template_id = $row_poam_target_template['target_template_id'];
				if(file_exists($row_poam_target_template['template'])) {
					//read the template
					$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($row_poam_target_template['template']);
					$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
					$reader->setReadDataOnly(true);
					$reader->setReadEmptyCells(false);
					$spreadsheet = $reader->load($row_poam_target_template['template']);
					
					$template_arr = explode("/", $row_poam_target_template['template']);
					$template_raw_name = end($template_arr);
					$template_name_split = explode('.', $template_raw_name);
					$template_name = $template_name_split[0]."_".$timestamp."_POAM";
					$fileExt = end(explode(".", $row_poam_target_template['template']));

					//get sheet names for the selected template
					$query_poam_target_tab = "SELECT DISTINCT `target_tab` FROM ".LA_TABLE_PREFIX."poam_logic WHERE `form_id` = ? AND `target_template_id` = ?";
					$sth_poam_target_tab = la_do_query($query_poam_target_tab, array($form_id, $target_template_id), $dbh);
					while($row_poam_target_tab = la_do_fetch_result($sth_poam_target_tab)) {
						$target_tab = $row_poam_target_tab['target_tab'];

						//get the last row of the selected sheet and store in $last_row variable
						$worksheet = $spreadsheet->getSheetByName($target_tab);
						if(!empty($worksheet) && !is_null($worksheet)) {
							$update_other_template_codes_with_submitted_entry_data = false;

							$highestRow = $worksheet->getHighestRow();
							$highestColumn = $worksheet->getHighestColumn();
							$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

							$last_row = array();
							for ($col = 1; $col <= $highestColumnIndex; ++$col) {
								$value = $worksheet->getCellByColumnAndRow($col, $highestRow)->getValue();
								array_push($last_row, array('col_id' => $col, 'value' => $value));
							}
							//get all poam settings for the select template and sheet
							$query_poam_templates = "SELECT * FROM `".LA_TABLE_PREFIX."poam_logic` WHERE `form_id` = ? AND `target_template_id` = ? AND `target_tab` = ?";
							$sth_poam_templates = la_do_query($query_poam_templates, array($form_id, $target_template_id, $target_tab), $dbh);
							while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
								//get a list of id of entries that match the poam logic condition and insert the entry data to the last row of the template
								$element_id = end(explode('_', $row_poam_template['element_name']));
								$query_entry_ids = "SELECT DISTINCT `entry_id` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND `data_value` = (SELECT `option_id` FROM `".LA_TABLE_PREFIX."element_options` AS e WHERE e.`form_id` = ? AND e.`element_id` = ? AND `option` = ?) AND `company_id` = ?  ORDER BY entry_id";
								$sth_entry_ids = la_do_query($query_entry_ids, array($row_poam_template['element_name'], $form_id, $element_id, $row_poam_template['rule_keyword'], $company_id), $dbh);
								while($poam_entry_ids = la_do_fetch_result($sth_entry_ids)) {
									$poam_replace_data_array = array();
									if($entry_id == $poam_entry_ids['entry_id']) {
										$update_other_template_codes_with_submitted_entry_data = true;
									}
									$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id = ? AND entry_id = ?";
									$param_forms = array('element_%', $company_id, $poam_entry_ids['entry_id']);
									$result_forms = la_do_query($query_forms, $param_forms, $dbh);

									while($row = la_do_fetch_result($result_forms)) {
										$poam_row_forms[$row['field_name']] = $row['data_value'];
									}

									//get entry data
									foreach($element_array as $element_id => $element) {
										if($element['element_type'] == 'matrix'){
											$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
										}

										if($element['element_type'] == 'simple_name'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']);
										}
										elseif($element['element_type'] == 'address'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']." ".$poam_row_forms['element_'.$element_id.'_3']." ".$poam_row_forms['element_'.$element_id.'_4']." ".$poam_row_forms['element_'.$element_id.'_5']." ".$poam_row_forms['element_'.$element_id.'_6']);
										}
										elseif($element['element_type'] == 'checkbox'){
											$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

											$checkbox_html = "";
											$checkbox_html_for_sheet = "";
											if($element['element_enhanced_checkbox'] == 1){
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $checkbox_html_for_sheet);
										}
										elseif($element['element_type'] == 'radio'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$multiple_choice_html = "";
											$multiple_choice_html_for_sheet = "";
											$other_value_flag = true;
											if($element['element_enhanced_multiple_choice'] == 1) {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $multiple_choice_html_for_sheet);
										}
										elseif($element['element_type'] == 'select'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$param_element_option[':option_id'] = (int) $poam_row_forms['element_'.$element_id];
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$row_element_option = la_do_fetch_result($result_element_option);
											
											if(!empty($row_element_option['option'])){
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
											}
										}
										elseif($element['element_type'] == 'phone'){
											$phone_val = substr($poam_row_forms['element_'.$element_id],0,3).'-'.substr($poam_row_forms['element_'.$element_id],3,3).'-'.substr($poam_row_forms['element_'.$element_id],6,4);
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
										} 
										elseif($element['element_type'] == 'casecade_form'){
											$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $client_id, 'company_id' => $company_id,  'entry_id' => $entry_id);
											$case_cade_poam_replace_data_array = generateCascadeData($generate_doc_params);
										
											// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
											$poam_replace_data_array = array_merge($poam_replace_data_array, $case_cade_poam_replace_data_array);
										}
										elseif($element['element_type'] == 'file'){
											$file_html = "";
											$file_html_for_sheet = "";
											if($element['element_file_upload_synced'] == 1) {
												$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
												$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_id),$dbh);
												$files_data_row = la_do_fetch_result($files_data_res);
												if( $files_data_row['files_data'] ) {
													$filename_array = json_decode($files_data_row['files_data']);
													foreach ($filename_array as $filename_value) {
														$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
														if(file_exists($file_source)) {
															$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
														}
													}
												}
											} else {
												$filename_array = explode("|", $poam_row_forms['element_'.$element_id]);
												foreach ($filename_array as $filename_value) {
													$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
													if(file_exists($file_source)) {
														$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
													}
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $file_html_for_sheet);
										}
										elseif($element['element_type'] == 'signature'){
											//create image from json data
											$signature_html = "";
											if( !empty($poam_row_forms['element_'.$element_id]) ){
												$signature_img_temp = sigJsonToImage($poam_row_forms['element_'.$element_id]);
												if( $signature_img_temp != false ) {
													$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
													$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

													if (!file_exists($destination_dir))
														mkdir($destination_dir, 0777, true);

													$random_string = bin2hex(random_bytes(24));
													$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
													$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
													if( imagepng($signature_img_temp, $image_name) ) {
														$unlink_images[] = $image_name;
													}
													$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
												} else {
													$signature_html .="<p>Unable to load a signature</p>";
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $signature_html);
										}
										elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
											if($poam_row_forms['element_'.$element_id] != "") {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($poam_row_forms['element_'.$element_id])));
											} else {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => "");
											}
										}
										elseif($element['element_type'] == 'textarea'){
											$poam_replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $poam_row_forms['element_'.$element_id]);
										}
										else{
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id]);
										}
									}

									$increase_last_row_flag = false;
									//writes entry data to the last row
									foreach ($last_row as $last_row_cell) {
										if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))])) {
											if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet']);
											} elseif (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph']);
											} elseif(isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'])) {
												$insert_value = $poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'];
											}

											$worksheet->setCellValueByColumnAndRow($last_row_cell['col_id'], $highestRow, $insert_value);
											$increase_last_row_flag = true;
										}
									}
									if($increase_last_row_flag) {
										$highestRow ++;
									}
								}
							}

							if($update_other_template_codes_with_submitted_entry_data) {
								//replace other template codes with current entry data
								foreach ($worksheet->getRowIterator() as $row) {
									$cellIterator = $row->getCellIterator();
									foreach ($cellIterator as $cell) {
										if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
											} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
											} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
												$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
											}

											$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
										}
									}
								}
							}

							//replace the remaining codes with empty string
							foreach ($worksheet->getRowIterator() as $row) {
								$cellIterator = $row->getCellIterator();
								foreach ($cellIterator as $cell) {
									if (substr($cell->getValue(), 0, 1) == "$" && substr($cell->getValue(), -1) == "$"){
										$worksheet->setCellValue($cell->getCoordinate(), "");
									}
								}
							}
						}
					}
					$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
					$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
					$writer->setPreCalculateFormulas(false);
					$writer->save($filename);
					$poam_zip_added_files[] = $template_name.'.xlsx';
				}
			}
		}

		//check if the form has a Wysiwyg template enabled
		$query_wysiwyg_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_templates` AS t ON f.form_template_wysiwyg_id = t.id WHERE f.`form_id` = ? AND f.`form_enable_template_wysiwyg` = ? AND f.form_template_wysiwyg_id != ?";
		$result_wysiwyg_enable = la_do_query($query_wysiwyg_enable, array($form_id, 1, 0), $dbh);
		$wysiwyg_enable = $result_wysiwyg_enable->fetchColumn();

		if( $wysiwyg_enable && extension_loaded('zip') ) {
			$query_template_id = "SELECT `form_template_wysiwyg_id` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
			$result_template_id = la_do_query($query_template_id, array($form_id), $dbh);
			$template_id = $result_template_id->fetchColumn();

			$zip_added_files[] = create_doc_from_wysiwyg_template($dbh, $form_id, $template_id, $replace_data_array, $client_id, $company_id, $la_settings['base_url']);
		}

		//check if the form has any template files attached
		$query_template_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON f.form_id = t.form_id WHERE f.`form_id` = ? AND f.`form_enable_template` = ?";
		$result_template_enable = la_do_query($query_template_enable, array($form_id, 1), $dbh);
		$template_enable = $result_template_enable->fetchColumn();

		if($template_enable > 0){
			$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
			$param_template = array();
			$param_template[':form_id'] = $form_id;
			$result_template = la_do_query($query_template,$param_template,$dbh);
			if(extension_loaded('zip')){
				while($row_template = la_do_fetch_result($result_template)){
					$template_document = trim($row_template['template']);
					if(!in_array($template_document, $all_poam_templates) && file_exists($template_document) == true){
						$template_arr = explode("/", $template_document);
						$template_raw_name = end($template_arr);
						$template_name_split = explode('.', $template_raw_name);
						$template_name = $template_name_split[0]."_".$timestamp;
						$fileExt = end(explode(".", $template_document));
						if($fileExt == 'docx') {
							if(count($replace_data_array) > 0){
								$docx = new CreateDocxFromTemplate($template_document, array('preprocessed' => true));

								$templateVar = $docx->getTemplateVariables();

								foreach($templateVar as $k => $v){
									$placeholders = array_unique($v);
									$replace_text_data_array = array();
									foreach($placeholders as $v1){
										if(isset($replace_data_array[$v1])){
											if(isset($replace_data_array[$v1]['insertHTML'])){
												$insertHTML = $replace_data_array[$v1]['insertHTML'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'stylesReplacementType' => 'usePlaceholderStyles', 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertParagraph'])){
												$insertHTML = $replace_data_array[$v1]['insertParagraph'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertText'])){
												$replace_text_data_array[$v1] = htmlentities($replace_data_array[$v1]['insertText']);
											} else {
												$replace_text_data_array[$v1] = '';
											}
										} else {
											$replace_text_data_array[$v1] = '';
										}
									}
									if(count($replace_text_data_array) > 0) {
										$docxReplaceOptions = array('firstMatch' => false, 'target' => $k, 'raw' => true);
										$docx->replaceVariableByText($replace_text_data_array, $docxReplaceOptions);
									}
								}
								$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name);
								$zip_added_files[] = $template_name.'.docx';
							}
						} elseif($fileExt == 'xlsx') {
							require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
							$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template_document);
							$number_of_sheets = $spreadsheet->getSheetCount();
							$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
							for ($sheet_index=0; $sheet_index < $spreadsheet->getSheetCount(); $sheet_index++) {
								$worksheet = $spreadsheet->getSheet($sheet_index);
								if(!empty($worksheet) && !is_null($worksheet)) {
									foreach ($worksheet->getRowIterator() as $row) {
										$cellIterator = $row->getCellIterator();
										foreach ($cellIterator as $cell) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
												if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
												} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
												} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
													$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
												}

												$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
											}
										}
									}
								}
							}
							$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
							$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
							$writer->setPreCalculateFormulas(false);
							$writer->save($filename);
							$zip_added_files[] = $template_name.'.xlsx';
						} else {
							//do nothing for other templates
						}
					}
				}
			}
		}

		//create a zip and add all the created POAM templates
		if(count($poam_zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp."_POAM.zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($poam_zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `isPOAM`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $client_id;
			$params_docx_insert[':company_id'] = $company_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;		
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp."_POAM.zip";
			$params_docx_insert[':added_files'] = implode(',', $poam_zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}
		
		//create a zip and add all the created templates
		if(count($zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $client_id;
			$params_docx_insert[':company_id'] = (int) $company_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp.".zip";
			$params_docx_insert[':added_files'] = implode(',', $zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}
		//after document creation unlink the images created for signatures
		/*if( count($unlink_images) > 0 ) {
			foreach ($unlink_images as $image_location) {
				unlink($image_location);
			}
		}*/
	}
	return $replace_data_array;
}

function getElementWithValueArray($parameter=array()){
	$dbh = $parameter['dbh'];
	$la_settings = la_get_settings($dbh);
	$form_id = $parameter['form_id'];
	$la_user_id = $parameter['la_user_id'];
	$company_user_id = $parameter['company_user_id'];
	$entry_id = $parameter['entry_id'];
	$zip_name = "";

	// operation variables
	$element_array = array();
	$replace_data_array = array();

	// fetch data from dynamic form table
	$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE 'element_%' AND company_id = ? AND entry_id = ?";
	$result_forms = la_do_query($query_forms, array($company_user_id, $entry_id), $dbh);
			
	while($row = la_do_fetch_result($result_forms)){
		$row_forms[$row['field_name']] = $row['data_value'];
	}

	$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value`, `element_enhanced_checkbox`, `element_enhanced_multiple_choice`, `element_choice_other_label`, `element_choice_other_score`, `element_choice_other_icon_src`, `element_file_upload_synced` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` != 'section' AND `element_type` != 'page_break' AND `element_type` != 'syndication' AND (`element_machine_code` != '' OR `element_type` = 'casecade_form') ORDER BY element_position ASC";

	$result_form_element = la_do_query($query_form_element, array($form_id), $dbh);
	
	while($row_form_element = la_do_fetch_result($result_form_element)){
		$element_array[$row_form_element['element_id']] = array();
		$element_array[$row_form_element['element_id']]['element_type'] = $row_form_element['element_type'];
		$element_array[$row_form_element['element_id']]['element_machine_code'] = $row_form_element['element_machine_code'];
		$element_array[$row_form_element['element_id']]['element_matrix_allow_multiselect'] = $row_form_element['element_matrix_allow_multiselect'];
		$element_array[$row_form_element['element_id']]['element_matrix_parent_id'] = $row_form_element['element_matrix_parent_id'];
		$element_array[$row_form_element['element_id']]['element_id'] = $row_form_element['element_id'];
		$element_array[$row_form_element['element_id']]['element_default_value'] = $row_form_element['element_default_value'];
		$element_array[$row_form_element['element_id']]['element_enhanced_checkbox'] = $row_form_element['element_enhanced_checkbox'];
		$element_array[$row_form_element['element_id']]['element_enhanced_multiple_choice'] = $row_form_element['element_enhanced_multiple_choice'];
		$element_array[$row_form_element['element_id']]['element_choice_other_label'] = $row_form_element['element_choice_other_label'];
		$element_array[$row_form_element['element_id']]['element_choice_other_score'] = $row_form_element['element_choice_other_score'];
		$element_array[$row_form_element['element_id']]['element_choice_other_icon_src'] = $row_form_element['element_choice_other_icon_src'];
		$element_array[$row_form_element['element_id']]['element_file_upload_synced'] = $row_form_element['element_file_upload_synced'];
	}

	if(count($element_array) > 0){
		$unlink_images = [];
		$timestamp = time();
		$zip_added_files = [];
		$poam_zip_added_files = [];

		//***************F O R M  N A M E ***************
		$query_form  = "SELECT `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = :form_id";
		$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
		$row_form    = la_do_fetch_result($result_form);
		$form_name   = trim($row_form['form_name']);
		$form_name   = str_replace(" ", "_", $form_name);
		$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
		$form_name	 = substr($form_name, 0, 24);
		//***************F O R M  N A M E ***************

		foreach($element_array as $element_id => $element){
			if($element['element_type'] == 'matrix'){
				$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
			}

			if($element['element_type'] == 'simple_name'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']);
			}
			elseif($element['element_type'] == 'address'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']." ".$row_forms['element_'.$element_id.'_3']." ".$row_forms['element_'.$element_id.'_4']." ".$row_forms['element_'.$element_id.'_5']." ".$row_forms['element_'.$element_id.'_6']);
			}
			elseif($element['element_type'] == 'checkbox'){
				$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

				$checkbox_html = "";
				$checkbox_html_for_sheet = "";
				if($element['element_enhanced_checkbox'] == 1){
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
								$icon_src = $element['element_choice_other_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $checkbox_html, 'insertSheet' => $checkbox_html_for_sheet);
			}
			elseif($element['element_type'] == 'radio'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$multiple_choice_html = "";
				$multiple_choice_html_for_sheet = "";
				$other_value_flag = true;
				if($element['element_enhanced_multiple_choice'] == 1) {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							}
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
									$icon_src = $element['element_choice_other_icon_src'];
								} else {
									$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								}
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $multiple_choice_html, 'insertSheet' => $multiple_choice_html_for_sheet);
			}
			elseif($element['element_type'] == 'select'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$row_element_option = la_do_fetch_result($result_element_option);
				
				if(!empty($row_element_option['option'])){
					$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
				}
			}
			elseif($element['element_type'] == 'phone'){
				$phone_val = substr($row_forms['element_'.$element_id],0,3).'-'.substr($row_forms['element_'.$element_id],3,3).'-'.substr($row_forms['element_'.$element_id],6,4);
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
			}
			elseif($element['element_type'] == 'casecade_form'){
				$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $la_user_id, 'company_id' => $company_user_id, 'entry_id' => $entry_id);
				$case_cade_replace_data_array = generateCascadeData($generate_doc_params);
			
				// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
				$replace_data_array = array_merge($replace_data_array, $case_cade_replace_data_array);
			}
			elseif($element['element_type'] == 'file'){
				$file_html = "";
				$file_html_for_sheet = "";
				if($element['element_file_upload_synced'] == 1) {
					$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
					$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_user_id),$dbh);
					$files_data_row = la_do_fetch_result($files_data_res);
					if( $files_data_row['files_data'] ) {
						$filename_array = json_decode($files_data_row['files_data']);
						foreach ($filename_array as $filename_value) {
							$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
							if(file_exists($file_source)) {
								$file_ext = end(explode(".", $filename_value));
								if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
									$file_src = "/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
									$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
								} else {
									$filename = explode('-', $filename_value, 2)[1];
									$file_html .="<p>{$filename}</p>";
								}
								$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
							}
						}
					}
				} else {
					$filename_array = explode("|", $row_forms['element_'.$element_id]);
					foreach ($filename_array as $filename_value) {
						$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
						if(file_exists($file_source)) {
							$file_ext = end(explode(".", $filename_value));
							if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
								$file_src = "/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
								$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
							} else {
								$filename = explode('-', $filename_value, 2)[1];
								$file_html .="<p>{$filename}</p>";
							}
							$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
						}
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $file_html, 'insertSheet' => $file_html_for_sheet);
			}
			elseif($element['element_type'] == 'signature'){
				//create image from json data
				$signature_html = "";
				if( !empty($row_forms['element_'.$element_id]) ){
					$signature_img_temp = sigJsonToImage($row_forms['element_'.$element_id]);
					if( $signature_img_temp != false ) {
						$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
						$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

						if (!file_exists($destination_dir))
							mkdir($destination_dir, 0777, true);

						$random_string = bin2hex(random_bytes(24));
						$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
						$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
						if( imagepng($signature_img_temp, $image_name) ) {
							$unlink_images[] = $image_name;
						}
						$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
					} else {
						$signature_html .="<p>Unable to load a signature</p>";
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $signature_html, 'insertSheet' => $signature_html);
			}
			elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
				if($row_forms['element_'.$element_id] != "") {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($row_forms['element_'.$element_id])));
				} else {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => "");
				}
			}
			elseif($element['element_type'] == 'textarea'){
				$replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $row_forms['element_'.$element_id]);
			}
			else{
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id]);
			}
		}

		$poam_enabled = false;
		$all_poam_templates = array();
		$poam_templates = array();
		$query = "SELECT `logic_poam_enable` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
		$sth = la_do_query($query, array($form_id), $dbh);
		$row = la_do_fetch_result($sth);
		if($row['logic_poam_enable']) {
			//get all existing poam templates -- we will exclude these templates when generating normal template outputs
			$query_poam_templates = "SELECT DISTINCT t.template_id, t.template FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.target_template_id = t.template_id WHERE p.form_id = ?";
			$sth_poam_templates = la_do_query($query_poam_templates, array($form_id), $dbh);
			while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
				if(file_exists($row_poam_template['template'])) {
					$all_poam_templates[] = $row_poam_template['template'];
					$poam_enabled = true;
				}
			}
		}

		if($poam_enabled) {
			require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
			$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
			$query_poam_target_template = "SELECT DISTINCT p.`target_template_id`, t.`template` FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.`target_template_id` = t.`template_id` WHERE p.`form_id` = ?";
			$sth_poam_target_template = la_do_query($query_poam_target_template, array($form_id), $dbh);
			while ($row_poam_target_template = la_do_fetch_result($sth_poam_target_template)) {
				$target_template_id = $row_poam_target_template['target_template_id'];
				if(file_exists($row_poam_target_template['template'])) {
					//read the template
					$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($row_poam_target_template['template']);
					$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
					$reader->setReadDataOnly(true);
					$reader->setReadEmptyCells(false);
					$spreadsheet = $reader->load($row_poam_target_template['template']);
					
					$template_arr = explode("/", $row_poam_target_template['template']);
					$template_raw_name = end($template_arr);
					$template_name_split = explode('.', $template_raw_name);
					$template_name = $template_name_split[0]."_".$timestamp."_POAM";
					$fileExt = end(explode(".", $row_poam_target_template['template']));

					//get sheet names for the selected template
					$query_poam_target_tab = "SELECT DISTINCT `target_tab` FROM ".LA_TABLE_PREFIX."poam_logic WHERE `form_id` = ? AND `target_template_id` = ?";
					$sth_poam_target_tab = la_do_query($query_poam_target_tab, array($form_id, $target_template_id), $dbh);
					while($row_poam_target_tab = la_do_fetch_result($sth_poam_target_tab)) {
						$target_tab = $row_poam_target_tab['target_tab'];

						//get the last row of the selected sheet and store in $last_row variable
						$worksheet = $spreadsheet->getSheetByName($target_tab);
						if(!empty($worksheet) && !is_null($worksheet)) {
							$update_other_template_codes_with_submitted_entry_data = false;

							$highestRow = $worksheet->getHighestRow();
							$highestColumn = $worksheet->getHighestColumn();
							$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

							$last_row = array();
							for ($col = 1; $col <= $highestColumnIndex; ++$col) {
								$value = $worksheet->getCellByColumnAndRow($col, $highestRow)->getValue();
								array_push($last_row, array('col_id' => $col, 'value' => $value));
							}
							//get all poam settings for the select template and sheet
							$query_poam_templates = "SELECT * FROM `".LA_TABLE_PREFIX."poam_logic` WHERE `form_id` = ? AND `target_template_id` = ? AND `target_tab` = ?";
							$sth_poam_templates = la_do_query($query_poam_templates, array($form_id, $target_template_id, $target_tab), $dbh);
							while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
								//get a list of id of entries that match the poam logic condition and insert the entry data to the last row of the template
								$element_id = end(explode('_', $row_poam_template['element_name']));
								$query_entry_ids = "SELECT DISTINCT `entry_id` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND `data_value` = (SELECT `option_id` FROM `".LA_TABLE_PREFIX."element_options` AS e WHERE e.`form_id` = ? AND e.`element_id` = ? AND `option` = ?) AND `company_id` = ?  ORDER BY entry_id";
								$sth_entry_ids = la_do_query($query_entry_ids, array($row_poam_template['element_name'], $form_id, $element_id, $row_poam_template['rule_keyword'], $company_user_id), $dbh);
								while($poam_entry_ids = la_do_fetch_result($sth_entry_ids)) {
									$poam_replace_data_array = array();
									if($entry_id == $poam_entry_ids['entry_id']) {
										$update_other_template_codes_with_submitted_entry_data = true;
									}
									$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id = ? AND entry_id = ?";
									$param_forms = array('element_%', $company_user_id, $poam_entry_ids['entry_id']);
									$result_forms = la_do_query($query_forms, $param_forms, $dbh);

									while($row = la_do_fetch_result($result_forms)) {
										$poam_row_forms[$row['field_name']] = $row['data_value'];
									}

									//get entry data
									foreach($element_array as $element_id => $element) {
										if($element['element_type'] == 'matrix'){
											$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
										}

										if($element['element_type'] == 'simple_name'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']);
										}
										elseif($element['element_type'] == 'address'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']." ".$poam_row_forms['element_'.$element_id.'_3']." ".$poam_row_forms['element_'.$element_id.'_4']." ".$poam_row_forms['element_'.$element_id.'_5']." ".$poam_row_forms['element_'.$element_id.'_6']);
										}
										elseif($element['element_type'] == 'checkbox'){
											$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

											$checkbox_html = "";
											$checkbox_html_for_sheet = "";
											if($element['element_enhanced_checkbox'] == 1){
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $checkbox_html_for_sheet);
										}
										elseif($element['element_type'] == 'radio'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$multiple_choice_html = "";
											$multiple_choice_html_for_sheet = "";
											$other_value_flag = true;
											if($element['element_enhanced_multiple_choice'] == 1) {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $multiple_choice_html_for_sheet);
										}
										elseif($element['element_type'] == 'select'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$param_element_option[':option_id'] = (int) $poam_row_forms['element_'.$element_id];
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$row_element_option = la_do_fetch_result($result_element_option);
											
											if(!empty($row_element_option['option'])){
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
											}
										}
										elseif($element['element_type'] == 'phone'){
											$phone_val = substr($poam_row_forms['element_'.$element_id],0,3).'-'.substr($poam_row_forms['element_'.$element_id],3,3).'-'.substr($poam_row_forms['element_'.$element_id],6,4);
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
										} 
										elseif($element['element_type'] == 'casecade_form'){
											$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $client_id, 'company_id' => $company_user_id,  'entry_id' => $entry_id);
											$case_cade_poam_replace_data_array = generateCascadeData($generate_doc_params);
										
											// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
											$poam_replace_data_array = array_merge($poam_replace_data_array, $case_cade_poam_replace_data_array);
										}
										elseif($element['element_type'] == 'file'){
											$file_html = "";
											$file_html_for_sheet = "";
											if($element['element_file_upload_synced'] == 1) {
												$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
												$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_user_id),$dbh);
												$files_data_row = la_do_fetch_result($files_data_res);
												if( $files_data_row['files_data'] ) {
													$filename_array = json_decode($files_data_row['files_data']);
													foreach ($filename_array as $filename_value) {
														$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
														if(file_exists($file_source)) {
															$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
														}
													}
												}
											} else {
												$filename_array = explode("|", $poam_row_forms['element_'.$element_id]);
												foreach ($filename_array as $filename_value) {
													$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
													if(file_exists($file_source)) {
														$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
													}
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $file_html_for_sheet);
										}
										elseif($element['element_type'] == 'signature'){
											//create image from json data
											$signature_html = "";
											if( !empty($poam_row_forms['element_'.$element_id]) ){
												$signature_img_temp = sigJsonToImage($poam_row_forms['element_'.$element_id]);
												if( $signature_img_temp != false ) {
													$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
													$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

													if (!file_exists($destination_dir))
														mkdir($destination_dir, 0777, true);

													$random_string = bin2hex(random_bytes(24));
													$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
													$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
													if( imagepng($signature_img_temp, $image_name) ) {
														$unlink_images[] = $image_name;
													}
													$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
												} else {
													$signature_html .="<p>Unable to load a signature</p>";
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $signature_html);
										}
										elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
											if($poam_row_forms['element_'.$element_id] != "") {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($poam_row_forms['element_'.$element_id])));
											} else {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => "");
											}
										}
										elseif($element['element_type'] == 'textarea'){
											$poam_replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $poam_row_forms['element_'.$element_id]);
										}
										else{
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id]);
										}
									}

									$increase_last_row_flag = false;
									//writes entry data to the last row
									foreach ($last_row as $last_row_cell) {
										if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))])) {
											if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet']);
											} elseif (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph']);
											} elseif(isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'])) {
												$insert_value = $poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'];
											}

											$worksheet->setCellValueByColumnAndRow($last_row_cell['col_id'], $highestRow, $insert_value);
											$increase_last_row_flag = true;
										}
									}
									if($increase_last_row_flag) {
										$highestRow ++;
									}
								}
							}

							if($update_other_template_codes_with_submitted_entry_data) {
								//replace other template codes with current entry data
								foreach ($worksheet->getRowIterator() as $row) {
									$cellIterator = $row->getCellIterator();
									foreach ($cellIterator as $cell) {
										if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
											} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
											} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
												$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
											}

											$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
										}
									}
								}
							}

							//replace the remaining codes with empty string
							foreach ($worksheet->getRowIterator() as $row) {
								$cellIterator = $row->getCellIterator();
								foreach ($cellIterator as $cell) {
									if (substr($cell->getValue(), 0, 1) == "$" && substr($cell->getValue(), -1) == "$"){
										$worksheet->setCellValue($cell->getCoordinate(), "");
									}
								}
							}
						}
					}
					$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
					$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
					$writer->setPreCalculateFormulas(false);
					$writer->save($filename);
					$poam_zip_added_files[] = $template_name.'.xlsx';
				}
			}
		}

		//check if the form has a Wysiwyg template enabled
		$query_wysiwyg_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_templates` AS t ON f.form_template_wysiwyg_id = t.id WHERE f.`form_id` = ? AND f.`form_enable_template_wysiwyg` = ? AND f.form_template_wysiwyg_id != ?";
		$result_wysiwyg_enable = la_do_query($query_wysiwyg_enable, array($form_id, 1, 0), $dbh);
		$wysiwyg_enable = $result_wysiwyg_enable->fetchColumn();

		if( $wysiwyg_enable && extension_loaded('zip') ) {
			$query_template_id = "SELECT `form_template_wysiwyg_id` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
			$result_template_id = la_do_query($query_template_id, array($form_id), $dbh);
			$template_id = $result_template_id->fetchColumn();

			$zip_added_files[] = create_doc_from_wysiwyg_template($dbh, $form_id, $template_id, $replace_data_array, $la_user_id, $company_user_id, $la_settings['base_url']);
		}

		//check if the form has any template files attached
		$query_template_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON f.form_id = t.form_id WHERE f.`form_id` = ? AND f.`form_enable_template` = ?";
		$result_template_enable = la_do_query($query_template_enable, array($form_id, 1), $dbh);
		$template_enable = $result_template_enable->fetchColumn();
		
		if($template_enable > 0){
			$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
			$param_template = array();
			$param_template[':form_id'] = $form_id;
			$result_template = la_do_query($query_template,$param_template,$dbh);
			if(extension_loaded('zip')){
				while($row_template = la_do_fetch_result($result_template)){
					$template_document = trim($row_template['template']);
					if(!in_array($template_document, $all_poam_templates) && file_exists($template_document) == true){
						$template_arr = explode("/", $template_document);
						$template_raw_name = end($template_arr);
						$template_name_split = explode('.', $template_raw_name);
						$template_name = $template_name_split[0]."_".$timestamp;
						$fileExt = end(explode(".", $template_document));
						if($fileExt == 'docx') {
							if(count($replace_data_array) > 0){
								$docx = new CreateDocxFromTemplate($template_document, array('preprocessed' => true));

								$templateVar = $docx->getTemplateVariables();

								foreach($templateVar as $k => $v){
									$placeholders = array_unique($v);
									$replace_text_data_array = array();
									foreach($placeholders as $v1){
										if(isset($replace_data_array[$v1])){
											if(isset($replace_data_array[$v1]['insertHTML'])){
												$insertHTML = $replace_data_array[$v1]['insertHTML'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'stylesReplacementType' => 'usePlaceholderStyles', 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertParagraph'])){
												$insertHTML = $replace_data_array[$v1]['insertParagraph'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertText'])){
												$replace_text_data_array[$v1] = htmlentities($replace_data_array[$v1]['insertText']);
											} else {
												$replace_text_data_array[$v1] = '';
											}
										} else {
											$replace_text_data_array[$v1] = '';
										}
									}
									if(count($replace_text_data_array) > 0) {
										$docxReplaceOptions = array('firstMatch' => false, 'target' => $k, 'raw' => true);
										$docx->replaceVariableByText($replace_text_data_array, $docxReplaceOptions);
									}
								}
								$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name);
								$zip_added_files[] = $template_name.'.docx';
							}
						} elseif($fileExt == 'xlsx') {
							require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
							$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template_document);
							$number_of_sheets = $spreadsheet->getSheetCount();
							$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
							for ($sheet_index=0; $sheet_index < $spreadsheet->getSheetCount(); $sheet_index++) {
								$worksheet = $spreadsheet->getSheet($sheet_index);
								if(!empty($worksheet) && !is_null($worksheet)) {
									foreach ($worksheet->getRowIterator() as $row) {
										$cellIterator = $row->getCellIterator();
										foreach ($cellIterator as $cell) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
												if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
												} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
												} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
													$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
												}

												$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
											}
										}
									}
								}
							}
							$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
							$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
							$writer->setPreCalculateFormulas(false);
							$writer->save($filename);
							$zip_added_files[] = $template_name.'.xlsx';
						} else {
							//do nothing for other templates
						}
					}
				}
			}
		}

		//create a zip and add all the created POAM templates
		if(count($poam_zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp."_POAM.zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($poam_zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `isPOAM`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $la_user_id;
			$params_docx_insert[':company_id'] = (int) $company_user_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;		
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp."_POAM.zip";
			$params_docx_insert[':added_files'] = implode(',', $poam_zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}

		//create a zip and add all the created templates
		if(count($zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $la_user_id;
			$params_docx_insert[':company_id'] = (int) $company_user_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp.".zip";
			$params_docx_insert[':added_files'] = implode(',', $zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}
		//after document creation unlink the images created for signatures
		/*if( count($unlink_images) > 0 ) {
			foreach ($unlink_images as $image_location) {
				unlink($image_location);
			}
		}*/
	}
	return $zip_name;
}

function getPortalElementWithValueArray($parameter=array()){
	// variables required
	$dbh = $parameter['dbh'];
	$la_settings = la_get_settings($dbh);
	$form_id = $parameter['form_id'];
	$company_id = $parameter['company_id'];
	$entry_id = $parameter['entry_id'];
	$userEntities = getEntityIds($dbh, $parameter['client_id']);
	$inQuery = implode(',', array_fill(0, count($userEntities), '?'));
	$client_id = $parameter['client_id'];
	$zip_name = "";

	// operation variables
	$element_array = array();
	$replace_data_array = array();
	
	$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id = ? AND entry_id = ?";
	$param_forms = array('element_%', $company_id, $entry_id);
	$result_forms = la_do_query($query_forms, $param_forms, $dbh);

	while($row = la_do_fetch_result($result_forms)){
		$row_forms[$row['field_name']] = $row['data_value'];
	}

	$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value`, `element_enhanced_checkbox`, `element_enhanced_multiple_choice`, `element_choice_other_label`, `element_choice_other_score`, `element_choice_other_icon_src`, `element_file_upload_synced` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` != 'section' AND `element_type` != 'page_break' AND `element_type` != 'syndication' AND (`element_machine_code` != '' OR `element_type` = 'casecade_form') ORDER BY element_position ASC";
	$result_form_element = la_do_query($query_form_element, array($form_id), $dbh);
	
	while($row_form_element = la_do_fetch_result($result_form_element)){
		$element_array[$row_form_element['element_id']] = array();
		$element_array[$row_form_element['element_id']]['element_type'] = $row_form_element['element_type'];
		$element_array[$row_form_element['element_id']]['element_machine_code'] = $row_form_element['element_machine_code'];
		$element_array[$row_form_element['element_id']]['element_matrix_allow_multiselect'] = $row_form_element['element_matrix_allow_multiselect'];
		$element_array[$row_form_element['element_id']]['element_matrix_parent_id'] = $row_form_element['element_matrix_parent_id'];
		$element_array[$row_form_element['element_id']]['element_id'] = $row_form_element['element_id'];
		$element_array[$row_form_element['element_id']]['element_default_value'] = $row_form_element['element_default_value'];
		$element_array[$row_form_element['element_id']]['element_enhanced_checkbox'] = $row_form_element['element_enhanced_checkbox'];
		$element_array[$row_form_element['element_id']]['element_enhanced_multiple_choice'] = $row_form_element['element_enhanced_multiple_choice'];
		$element_array[$row_form_element['element_id']]['element_choice_other_label'] = $row_form_element['element_choice_other_label'];
		$element_array[$row_form_element['element_id']]['element_choice_other_score'] = $row_form_element['element_choice_other_score'];
		$element_array[$row_form_element['element_id']]['element_choice_other_icon_src'] = $row_form_element['element_choice_other_icon_src'];
		$element_array[$row_form_element['element_id']]['element_file_upload_synced'] = $row_form_element['element_file_upload_synced'];
	}
	
	if(count($element_array) > 0){
		$unlink_images = [];
		$timestamp = time();
		$zip_added_files = [];
		$poam_zip_added_files = [];

		//***************F O R M  N A M E ***************
		$query_form  = "SELECT `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = :form_id";
		$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
		$row_form    = la_do_fetch_result($result_form);
		$form_name   = trim($row_form['form_name']);
		$form_name   = str_replace(" ", "_", $form_name);
		$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
		$form_name	 = substr($form_name, 0, 24);
		//***************F O R M  N A M E ***************

		foreach($element_array as $element_id => $element){
			if($element['element_type'] == 'matrix'){
				$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
			}

			if($element['element_type'] == 'simple_name'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']);
			}
			elseif($element['element_type'] == 'address'){
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']." ".$row_forms['element_'.$element_id.'_3']." ".$row_forms['element_'.$element_id.'_4']." ".$row_forms['element_'.$element_id.'_5']." ".$row_forms['element_'.$element_id.'_6']);
			}
			elseif($element['element_type'] == 'checkbox'){
				$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

				$checkbox_html = "";
				$checkbox_html_for_sheet = "";
				if($element['element_enhanced_checkbox'] == 1){
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
								$icon_src = $element['element_choice_other_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/enhanced_checkbox_checked_icon.png';
							}
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/checkbox_unchecked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					//Add enabled other value if it is selected
					if(isset($row_forms['element_'.$element_id.'_other'])){
						if($row_forms['element_'.$element_id.'_other'] != ""){
							$icon_src = '/auditprotocol/images/icons/checkbox_checked_icon.png';
							$checkbox_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
							$checkbox_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $checkbox_html, 'insertSheet' => $checkbox_html_for_sheet);
			}
			elseif($element['element_type'] == 'radio'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$multiple_choice_html = "";
				$multiple_choice_html_for_sheet = "";
				$other_value_flag = true;
				if($element['element_enhanced_multiple_choice'] == 1) {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							if($row_element_option['option_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$row_element_option['option_icon_src'])) {
								$icon_src = $row_element_option['option_icon_src'];
							} else {
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							}
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								if($element['element_choice_other_icon_src'] != "" && file_exists($_SERVER['DOCUMENT_ROOT'].$element['element_choice_other_icon_src'])) {
									$icon_src = $element['element_choice_other_icon_src'];
								} else {
									$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								}
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				} else {
					while($row_element_option = la_do_fetch_result($result_element_option)) {
						if($row_forms['element_'.$element_id] == $row_element_option['option_id']){
							$other_value_flag = false;
							$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
							$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
						} else {
							$icon_src = '/auditprotocol/images/icons/radio_unchecked_icon.png';
							$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_element_option['option']."</p>";
						}
					}
					
					//Add enabled other value if it is selected
					if($other_value_flag) {
						if(isset($row_forms['element_'.$element_id.'_other'])){
							if($row_forms['element_'.$element_id.'_other'] != ""){
								$icon_src = '/auditprotocol/images/icons/radio_checked_icon.png';
								$multiple_choice_html .= "<p><img src=\"{$icon_src}\" />"."  ".$row_forms['element_'.$element_id.'_other']."</p>";
								$multiple_choice_html_for_sheet .= "<p> - ".$row_forms['element_'.$element_id.'_other']."</p>";
							}
						}
					}
				}
				
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $multiple_choice_html, 'insertSheet' => $multiple_choice_html_for_sheet);
			}
			elseif($element['element_type'] == 'select'){
				$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
				$param_element_option = array();
				$param_element_option[':form_id'] = $form_id;
				$param_element_option[':element_id'] = $element_id;
				$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
				$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
				$row_element_option = la_do_fetch_result($result_element_option);
				
				if(!empty($row_element_option['option'])){
					$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
				}
			}
			elseif($element['element_type'] == 'phone'){
				$phone_val = substr($row_forms['element_'.$element_id],0,3).'-'.substr($row_forms['element_'.$element_id],3,3).'-'.substr($row_forms['element_'.$element_id],6,4);
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
			} 
			elseif($element['element_type'] == 'casecade_form'){
				$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $client_id, 'company_id' => $company_id,  'entry_id' => $entry_id);
				$case_cade_replace_data_array = generateCascadeData($generate_doc_params);
			
				// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
				$replace_data_array = array_merge($replace_data_array, $case_cade_replace_data_array);
			}
			elseif($element['element_type'] == 'file'){
				$file_html = "";
				$file_html_for_sheet = "";
				if($element['element_file_upload_synced'] == 1) {
					$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
					$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_id),$dbh);
					$files_data_row = la_do_fetch_result($files_data_res);
					if( $files_data_row['files_data'] ) {
						$filename_array = json_decode($files_data_row['files_data']);
						foreach ($filename_array as $filename_value) {
							$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
							if(file_exists($file_source)) {
								$file_ext = end(explode(".", $filename_value));
								if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
									$file_src = "/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
									$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
								} else {
									$filename = explode('-', $filename_value, 2)[1];
									$file_html .="<p>{$filename}</p>";
								}
								$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
							}
						}
					}
				} else {
					$filename_array = explode("|", $row_forms['element_'.$element_id]);
					foreach ($filename_array as $filename_value) {
						$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
						if(file_exists($file_source)) {
							$file_ext = end(explode(".", $filename_value));
							if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))) {
								$file_src = "/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
								$file_html .= "<p><img src=\"{$file_src}\" style='width: 200px' /></p>";
							} else {
								$filename = explode('-', $filename_value, 2)[1];
								$file_html .="<p>{$filename}</p>";
							}
							$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
						}
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $file_html, 'insertSheet' => $file_html_for_sheet);
			}
			elseif($element['element_type'] == 'signature'){
				//create image from json data
				$signature_html = "";
				if( !empty($row_forms['element_'.$element_id]) ){
					$signature_img_temp = sigJsonToImage($row_forms['element_'.$element_id]);
					if( $signature_img_temp != false ) {
						$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
						$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

						if (!file_exists($destination_dir))
							mkdir($destination_dir, 0777, true);

						$random_string = bin2hex(random_bytes(24));
						$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
						$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
						if( imagepng($signature_img_temp, $image_name) ) {
							$unlink_images[] = $image_name;
						}
						$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
					} else {
						$signature_html .="<p>Unable to load a signature</p>";
					}
				}
				$replace_data_array[$element['element_machine_code']] = array('insertHTML' => $signature_html, 'insertSheet' => $signature_html);
			}
			elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
				if($row_forms['element_'.$element_id] != "") {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($row_forms['element_'.$element_id])));
				} else {
					$replace_data_array[$element['element_machine_code']] = array("insertText" => "");
				}
			}
			elseif($element['element_type'] == 'textarea'){
				$replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $row_forms['element_'.$element_id]);
			}
			else{
				$replace_data_array[$element['element_machine_code']] = array("insertText" => $row_forms['element_'.$element_id]);
			}
		}

		$poam_enabled = false;
		$all_poam_templates = array();
		$poam_templates = array();
		$query = "SELECT `logic_poam_enable` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
		$sth = la_do_query($query, array($form_id), $dbh);
		$row = la_do_fetch_result($sth);
		if($row['logic_poam_enable']) {
			//get all existing poam templates -- we will exclude these templates when generating normal template outputs
			$query_poam_templates = "SELECT DISTINCT t.template_id, t.template FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.target_template_id = t.template_id WHERE p.form_id = ?";
			$sth_poam_templates = la_do_query($query_poam_templates, array($form_id), $dbh);
			while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
				if(file_exists($row_poam_template['template'])) {
					$all_poam_templates[] = $row_poam_template['template'];
					$poam_enabled = true;
				}
			}
		}

		if($poam_enabled) {
			require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
			$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
			$query_poam_target_template = "SELECT DISTINCT p.`target_template_id`, t.`template` FROM `".LA_TABLE_PREFIX."poam_logic` AS p LEFT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON p.`target_template_id` = t.`template_id` WHERE p.`form_id` = ?";
			$sth_poam_target_template = la_do_query($query_poam_target_template, array($form_id), $dbh);
			while ($row_poam_target_template = la_do_fetch_result($sth_poam_target_template)) {
				$target_template_id = $row_poam_target_template['target_template_id'];
				if(file_exists($row_poam_target_template['template'])) {
					//read the template
					$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($row_poam_target_template['template']);
					$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
					$reader->setReadDataOnly(true);
					$reader->setReadEmptyCells(false);
					$spreadsheet = $reader->load($row_poam_target_template['template']);
					
					$template_arr = explode("/", $row_poam_target_template['template']);
					$template_raw_name = end($template_arr);
					$template_name_split = explode('.', $template_raw_name);
					$template_name = $template_name_split[0]."_".$timestamp."_POAM";
					$fileExt = end(explode(".", $row_poam_target_template['template']));

					//get sheet names for the selected template
					$query_poam_target_tab = "SELECT DISTINCT `target_tab` FROM ".LA_TABLE_PREFIX."poam_logic WHERE `form_id` = ? AND `target_template_id` = ?";
					$sth_poam_target_tab = la_do_query($query_poam_target_tab, array($form_id, $target_template_id), $dbh);
					while($row_poam_target_tab = la_do_fetch_result($sth_poam_target_tab)) {
						$target_tab = $row_poam_target_tab['target_tab'];

						//get the last row of the selected sheet and store in $last_row variable
						$worksheet = $spreadsheet->getSheetByName($target_tab);
						if(!empty($worksheet) && !is_null($worksheet)) {
							$update_other_template_codes_with_submitted_entry_data = false;

							$highestRow = $worksheet->getHighestRow();
							$highestColumn = $worksheet->getHighestColumn();
							$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

							$last_row = array();
							for ($col = 1; $col <= $highestColumnIndex; ++$col) {
								$value = $worksheet->getCellByColumnAndRow($col, $highestRow)->getValue();
								array_push($last_row, array('col_id' => $col, 'value' => $value));
							}
							//get all poam settings for the select template and sheet
							$query_poam_templates = "SELECT * FROM `".LA_TABLE_PREFIX."poam_logic` WHERE `form_id` = ? AND `target_template_id` = ? AND `target_tab` = ?";
							$sth_poam_templates = la_do_query($query_poam_templates, array($form_id, $target_template_id, $target_tab), $dbh);
							while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
								//get a list of id of entries that match the poam logic condition and insert the entry data to the last row of the template
								$element_id = end(explode('_', $row_poam_template['element_name']));
								$query_entry_ids = "SELECT DISTINCT `entry_id` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND `data_value` = (SELECT `option_id` FROM `".LA_TABLE_PREFIX."element_options` AS e WHERE e.`form_id` = ? AND e.`element_id` = ? AND `option` = ?) AND `company_id` = ?  ORDER BY entry_id";
								$sth_entry_ids = la_do_query($query_entry_ids, array($row_poam_template['element_name'], $form_id, $element_id, $row_poam_template['rule_keyword'], $company_id), $dbh);
								while($poam_entry_ids = la_do_fetch_result($sth_entry_ids)) {
									$poam_replace_data_array = array();
									if($entry_id == $poam_entry_ids['entry_id']) {
										$update_other_template_codes_with_submitted_entry_data = true;
									}
									$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id = ? AND entry_id = ?";
									$param_forms = array('element_%', $company_id, $poam_entry_ids['entry_id']);
									$result_forms = la_do_query($query_forms, $param_forms, $dbh);

									while($row = la_do_fetch_result($result_forms)) {
										$poam_row_forms[$row['field_name']] = $row['data_value'];
									}

									//get entry data
									foreach($element_array as $element_id => $element) {
										if($element['element_type'] == 'matrix'){
											$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
										}

										if($element['element_type'] == 'simple_name'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']);
										}
										elseif($element['element_type'] == 'address'){
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id.'_1']." ".$poam_row_forms['element_'.$element_id.'_2']." ".$poam_row_forms['element_'.$element_id.'_3']." ".$poam_row_forms['element_'.$element_id.'_4']." ".$poam_row_forms['element_'.$element_id.'_5']." ".$poam_row_forms['element_'.$element_id.'_6']);
										}
										elseif($element['element_type'] == 'checkbox'){
											$query_element_option = "SELECT `option_id`, `option`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

											$checkbox_html = "";
											$checkbox_html_for_sheet = "";
											if($element['element_enhanced_checkbox'] == 1){
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)){
													if($poam_row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]){
														$checkbox_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												//Add enabled other value if it is selected
												if(isset($poam_row_forms['element_'.$element_id.'_other'])){
													if($poam_row_forms['element_'.$element_id.'_other'] != ""){
														$checkbox_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $checkbox_html_for_sheet);
										}
										elseif($element['element_type'] == 'radio'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value`, `option_icon_src` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id GROUP BY `option_id` ORDER BY `option_id` ASC";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$multiple_choice_html = "";
											$multiple_choice_html_for_sheet = "";
											$other_value_flag = true;
											if($element['element_enhanced_multiple_choice'] == 1) {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											} else {
												while($row_element_option = la_do_fetch_result($result_element_option)) {
													if($poam_row_forms['element_'.$element_id] == $row_element_option['option_id']){
														$other_value_flag = false;
														$multiple_choice_html_for_sheet .= "<p> - ".$row_element_option['option']."</p>";
													}
												}
												
												//Add enabled other value if it is selected
												if($other_value_flag) {
													if(isset($poam_row_forms['element_'.$element_id.'_other'])){
														if($poam_row_forms['element_'.$element_id.'_other'] != ""){
															$multiple_choice_html_for_sheet .= "<p> - ".$poam_row_forms['element_'.$element_id.'_other']."</p>";
														}
													}
												}
											}
											
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $multiple_choice_html_for_sheet);
										}
										elseif($element['element_type'] == 'select'){
											$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id GROUP BY `option_id`";
											$param_element_option = array();
											$param_element_option[':form_id'] = $form_id;
											$param_element_option[':element_id'] = $element_id;
											$param_element_option[':option_id'] = (int) $poam_row_forms['element_'.$element_id];
											$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
											$row_element_option = la_do_fetch_result($result_element_option);
											
											if(!empty($row_element_option['option'])){
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $row_element_option['option']);
											}
										}
										elseif($element['element_type'] == 'phone'){
											$phone_val = substr($poam_row_forms['element_'.$element_id],0,3).'-'.substr($poam_row_forms['element_'.$element_id],3,3).'-'.substr($poam_row_forms['element_'.$element_id],6,4);
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $phone_val);
										} 
										elseif($element['element_type'] == 'casecade_form'){
											$generate_doc_params = array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'client_id' => $client_id, 'company_id' => $company_id,  'entry_id' => $entry_id);
											$case_cade_poam_replace_data_array = generateCascadeData($generate_doc_params);
										
											// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
											$poam_replace_data_array = array_merge($poam_replace_data_array, $case_cade_poam_replace_data_array);
										}
										elseif($element['element_type'] == 'file'){
											$file_html = "";
											$file_html_for_sheet = "";
											if($element['element_file_upload_synced'] == 1) {
												$files_data_sql = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
												$files_data_res = la_do_query($files_data_sql,array($element['element_machine_code'], $company_id),$dbh);
												$files_data_row = la_do_fetch_result($files_data_res);
												if( $files_data_row['files_data'] ) {
													$filename_array = json_decode($files_data_row['files_data']);
													foreach ($filename_array as $filename_value) {
														$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element['element_machine_code']}/{$filename_value}";
														if(file_exists($file_source)) {
															$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
														}
													}
												}
											} else {
												$filename_array = explode("|", $poam_row_forms['element_'.$element_id]);
												foreach ($filename_array as $filename_value) {
													$file_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
													if(file_exists($file_source)) {
														$file_html_for_sheet .="<p>".explode('-', $filename_value, 2)[1]."</p>";
													}
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $file_html_for_sheet);
										}
										elseif($element['element_type'] == 'signature'){
											//create image from json data
											$signature_html = "";
											if( !empty($poam_row_forms['element_'.$element_id]) ){
												$signature_img_temp = sigJsonToImage($poam_row_forms['element_'.$element_id]);
												if( $signature_img_temp != false ) {
													$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/signature_temp_images";
													$destination_dir_url = "/auditprotocol/data/form_{$form_id}/signature_temp_images";

													if (!file_exists($destination_dir))
														mkdir($destination_dir, 0777, true);

													$random_string = bin2hex(random_bytes(24));
													$image_name = $destination_dir."/{$random_string}_element_{$element_id}.png";
													$image_name_url = $destination_dir_url."/{$random_string}_element_{$element_id}.png";
													if( imagepng($signature_img_temp, $image_name) ) {
														$unlink_images[] = $image_name;
													}
													$signature_html .= "<p><img src=\"{$image_name_url}\" style='width: 200px' /></p>";
												} else {
													$signature_html .="<p>Unable to load a signature</p>";
												}
											}
											$poam_replace_data_array[$element['element_machine_code']] = array('insertSheet' => $signature_html);
										}
										elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
											if($poam_row_forms['element_'.$element_id] != "") {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => date("m-d-Y", strtotime($poam_row_forms['element_'.$element_id])));
											} else {
												$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => "");
											}
										}
										elseif($element['element_type'] == 'textarea'){
											$poam_replace_data_array[$element['element_machine_code']] = array('insertParagraph' => $poam_row_forms['element_'.$element_id]);
										}
										else{
											$poam_replace_data_array[$element['element_machine_code']] = array("insertText" => $poam_row_forms['element_'.$element_id]);
										}
									}

									$increase_last_row_flag = false;
									//writes entry data to the last row
									foreach ($last_row as $last_row_cell) {
										if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))])) {
											if (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertSheet']);
											} elseif (isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertParagraph']);
											} elseif(isset($poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'])) {
												$insert_value = $poam_replace_data_array[str_replace("$", "", trim($last_row_cell['value']))]['insertText'];
											}

											$worksheet->setCellValueByColumnAndRow($last_row_cell['col_id'], $highestRow, $insert_value);
											$increase_last_row_flag = true;
										}
									}
									if($increase_last_row_flag) {
										$highestRow ++;
									}
								}
							}

							if($update_other_template_codes_with_submitted_entry_data) {
								//replace other template codes with current entry data
								foreach ($worksheet->getRowIterator() as $row) {
									$cellIterator = $row->getCellIterator();
									foreach ($cellIterator as $cell) {
										if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
											} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
												$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
											} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
												$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
											}

											$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
										}
									}
								}
							}

							//replace the remaining codes with empty string
							foreach ($worksheet->getRowIterator() as $row) {
								$cellIterator = $row->getCellIterator();
								foreach ($cellIterator as $cell) {
									if (substr($cell->getValue(), 0, 1) == "$" && substr($cell->getValue(), -1) == "$"){
										$worksheet->setCellValue($cell->getCoordinate(), "");
									}
								}
							}
						}
					}
					$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
					$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
					$writer->setPreCalculateFormulas(false);
					$writer->save($filename);
					$poam_zip_added_files[] = $template_name.'.xlsx';
				}
			}
		}

		//check if the form has a Wysiwyg template enabled
		$query_wysiwyg_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_templates` AS t ON f.form_template_wysiwyg_id = t.id WHERE f.`form_id` = ? AND f.`form_enable_template_wysiwyg` = ? AND f.form_template_wysiwyg_id != ?";
		$result_wysiwyg_enable = la_do_query($query_wysiwyg_enable, array($form_id, 1, 0), $dbh);
		$wysiwyg_enable = $result_wysiwyg_enable->fetchColumn();

		if( $wysiwyg_enable && extension_loaded('zip') ) {
			$query_template_id = "SELECT `form_template_wysiwyg_id` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
			$result_template_id = la_do_query($query_template_id, array($form_id), $dbh);
			$template_id = $result_template_id->fetchColumn();

			$zip_added_files[] = create_doc_from_wysiwyg_template($dbh, $form_id, $template_id, $replace_data_array, $client_id, $company_id, $la_settings['base_url']);
		}

		//check if the form has any template files attached
		$query_template_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON f.form_id = t.form_id WHERE f.`form_id` = ? AND f.`form_enable_template` = ?";
		$result_template_enable = la_do_query($query_template_enable, array($form_id, 1), $dbh);
		$template_enable = $result_template_enable->fetchColumn();
		
		if($template_enable > 0){
			$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
			$param_template = array();
			$param_template[':form_id'] = $form_id;
			$result_template = la_do_query($query_template,$param_template,$dbh);

			if(extension_loaded('zip')){
				while($row_template = la_do_fetch_result($result_template)){
					$template_document = trim($row_template['template']);
					if(!in_array($template_document, $all_poam_templates) && file_exists($template_document) == true){
						$template_arr = explode("/", $template_document);
						$template_raw_name = end($template_arr);
						$template_name_split = explode('.', $template_raw_name);
						$template_name = $template_name_split[0]."_".$timestamp;
						$fileExt = end(explode(".", $template_document));
						if($fileExt == 'docx') {
							if(count($replace_data_array) > 0){
								$docx = new CreateDocxFromTemplate($template_document, array('preprocessed' => true));

								$templateVar = $docx->getTemplateVariables();

								foreach($templateVar as $k => $v){
									$placeholders = array_unique($v);
									$replace_text_data_array = array();
									foreach($placeholders as $v1){
										if(isset($replace_data_array[$v1])){
											if(isset($replace_data_array[$v1]['insertHTML'])){
												$insertHTML = $replace_data_array[$v1]['insertHTML'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'stylesReplacementType' => 'usePlaceholderStyles', 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertParagraph'])){
												$insertHTML = $replace_data_array[$v1]['insertParagraph'];
												if($insertHTML != "") {
													$docxReplaceOptions = array('isFile' => false, 'downloadImages' => true, 'firstMatch' => false, 'target' => $k);
													$docx->replaceVariableByHtml($v1, 'block', $insertHTML, $docxReplaceOptions);
												}
											} else if(isset($replace_data_array[$v1]['insertText'])){
												$replace_text_data_array[$v1] = htmlentities($replace_data_array[$v1]['insertText']);
											} else {
												$replace_text_data_array[$v1] = '';
											}
										} else {
											$replace_text_data_array[$v1] = '';
										}
									}
									if(count($replace_text_data_array) > 0) {
										$docxReplaceOptions = array('firstMatch' => false, 'target' => $k, 'raw' => true);
										$docx->replaceVariableByText($replace_text_data_array, $docxReplaceOptions);
									}
								}
								$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name);
								$zip_added_files[] = $template_name.'.docx';
							}
						} elseif($fileExt == 'xlsx') {
							require_once("../itam-shared/PhpSpreadsheet/vendor/autoload.php");
							$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template_document);
							$number_of_sheets = $spreadsheet->getSheetCount();
							$wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
							for ($sheet_index=0; $sheet_index < $spreadsheet->getSheetCount(); $sheet_index++) {
								$worksheet = $spreadsheet->getSheet($sheet_index);
								if(!empty($worksheet) && !is_null($worksheet)) {
									foreach ($worksheet->getRowIterator() as $row) {
										$cellIterator = $row->getCellIterator();
										foreach ($cellIterator as $cell) {
											if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
												if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertSheet']);
												} elseif (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph'])) {
													$insert_value = $wizard->toRichTextObject($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertParagraph']);
												} elseif(isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'])) {
													$insert_value = $replace_data_array[str_replace("$", "", trim($cell->getValue()))]['insertText'];
												}

												$worksheet->setCellValue($cell->getCoordinate(), $insert_value);
											}
										}
									}
								}
							}
							$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.xlsx';
							$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
							$writer->setPreCalculateFormulas(false);
							$writer->save($filename);
							$zip_added_files[] = $template_name.'.xlsx';
						} else {
							//do nothing for other templates
						}
					}
				}
			}
		}

		//create a zip and add all the created POAM templates
		if(count($poam_zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp."_POAM.zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($poam_zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `isPOAM`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $client_id;
			$params_docx_insert[':company_id'] = $company_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;		
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp."_POAM.zip";
			$params_docx_insert[':added_files'] = implode(',', $poam_zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}

		//create a zip and add all the created templates
		if(count($zip_added_files) > 0) {
			$zip = new ZipArchive();
			$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";
			$zip->open($zip_name, ZIPARCHIVE::CREATE);

			foreach ($zip_added_files as $zip_added_file) {
				$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$zip_added_file, $zip_added_file);
			}
			
			$zip->close();

			$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `entry_id`, `docx_create_date`, `docxname`, `isZip`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :entry_id, :docx_create_date, :docxname, '1', :added_files)";
			$params_docx_insert = array();
			$params_docx_insert[':client_id'] = (int) $client_id;
			$params_docx_insert[':company_id'] = $company_id;
			$params_docx_insert[':form_id'] = $form_id;
			$params_docx_insert[':entry_id'] = $entry_id;
			$params_docx_insert[':docx_create_date'] = $timestamp;		
			$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp.".zip";
			$params_docx_insert[':added_files'] = implode(',', $zip_added_files);
			la_do_query($query_docx_insert,$params_docx_insert,$dbh);
		}
		//after document creation unlink the images created for signatures
		/*if( count($unlink_images) > 0 ) {
			foreach ($unlink_images as $image_location) {
				unlink($image_location);
			}
		}*/
	}
	
	$query = "update ".LA_TABLE_PREFIX."form_payment_check set form_counter = (form_counter - 1) where form_id = ? and company_id IN ({$inQuery})";
	la_do_query($query, array_merge(array($form_id), $userEntities), $dbh);
	
	return $zip_name;
}

function sigJsonToImage ($json, $options = array()) {
	$defaultOptions = array(
		'imageSize' => array(320, 150)
		,'bgColour' => array(0xff, 0xff, 0xff)
		,'penWidth' => 2
		,'penColour' => array(0x00, 0x00, 0x00)
		,'drawMultiplier'=> 12
	);

	$options = array_merge($defaultOptions, $options);

	$img = imagecreatetruecolor($options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][1] * $options['drawMultiplier']);

	if ($options['bgColour'] == 'transparent') {
		imagesavealpha($img, true);
		$bg = imagecolorallocatealpha($img, 0, 0, 0, 127);
	} else {
		$bg = imagecolorallocate($img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2]);
	}

	$pen = imagecolorallocate($img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2]);
	imagefill($img, 0, 0, $bg);

  	if (is_string($json))
    	$json = json_decode(stripslashes($json));

	if (json_last_error() === JSON_ERROR_NONE) {
		//proceed if json is valid
		foreach ($json as $v)
			drawThickLine($img, $v->lx * $options['drawMultiplier'], $v->ly * $options['drawMultiplier'], $v->mx * $options['drawMultiplier'], $v->my * $options['drawMultiplier'], $pen, $options['penWidth'] * ($options['drawMultiplier'] / 2));

		$imgDest = imagecreatetruecolor($options['imageSize'][0], $options['imageSize'][1]);

		if ($options['bgColour'] == 'transparent') {
			imagealphablending($imgDest, false);
			imagesavealpha($imgDest, true);
		}

		imagecopyresampled($imgDest, $img, 0, 0, 0, 0, $options['imageSize'][0], $options['imageSize'][0], $options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][0] * $options['drawMultiplier']);
		imagedestroy($img);

		return $imgDest;
	} else {
		return false;
	}
}

/**
 *  Draws a thick line
 *  Changing the thickness of a line using imagesetthickness doesn't produce as nice of result
 *
 *  @param object $img
 *  @param int $startX
 *  @param int $startY
 *  @param int $endX
 *  @param int $endY
 *  @param object $colour
 *  @param int $thickness
 *
 *  @return void
 */
function drawThickLine ($img, $startX, $startY, $endX, $endY, $colour, $thickness) {
	$angle = (atan2(($startY - $endY), ($endX - $startX)));

	$dist_x = $thickness * (sin($angle));
	$dist_y = $thickness * (cos($angle));

	$p1x = ceil(($startX + $dist_x));
	$p1y = ceil(($startY + $dist_y));
	$p2x = ceil(($endX + $dist_x));
	$p2y = ceil(($endY + $dist_y));
	$p3x = ceil(($endX - $dist_x));
	$p3y = ceil(($endY - $dist_y));
	$p4x = ceil(($startX - $dist_x));
	$p4y = ceil(($startY - $dist_y));

	$array = array(0=>$p1x, $p1y, $p2x, $p2y, $p3x, $p3y, $p4x, $p4y);
	imagefilledpolygon($img, $array, (count($array)/2), $colour);
}
