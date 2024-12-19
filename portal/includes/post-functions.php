<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	function getRecaptchaSecretKey(){
		$dbh = la_connect_db();
		$sql = "select `recaptcha_secret` from `".LA_TABLE_PREFIX."settings`";
		$res = la_do_query($sql,array(),$dbh);
		$row = la_do_fetch_result($res);
			
		return $row['recaptcha_secret'];
	}
		
 	function getMatrixNewType($dbh, $form_id, $element)
	{
		if($element['element_matrix_allow_multiselect'] == 1 && $element['element_matrix_parent_id'] == 0){
			$element['element_type'] = 'checkbox';
		}else{
			$query_matrix = "select element_matrix_allow_multiselect from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id and `element_id` = :element_id";
			$result_element_matrix = la_do_query($query_matrix,array(':form_id' => $form_id, ':element_id' => $element['element_matrix_parent_id']),$dbh);
			$row_element_matrix = la_do_fetch_result($result_element_matrix);
			
			if($row_element_matrix['element_matrix_allow_multiselect'] == 1){
				$element['element_type'] = 'checkbox';
			}else{
				$element['element_type'] = 'radio';
			}
		}
		
		return $element['element_type'];
	}
	
	function calculateScoreAndGenerateDoc($parameter=array())
	{
		if(!$parameter['form_id']){
			return array();	
		}
		
		$dbh = $parameter['dbh'];
		$la_settings = $parameter['la_settings'];
		$form_id = $parameter['form_id'];
		$company_id = $parameter['company_id'];
		$user_id = $parameter['user_id'];
		
		/*********************************************************************************/
		$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
		$result_template_count = la_do_query($query_template_count, $array($form_id), $dbh);
		$num_rows = $result_template_count->fetchColumn();
		
		$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE ? AND company_id=?";
		$param_forms = array('element_%', $company_id);
		$result_forms = la_do_query($query_forms,$param_forms,$dbh);
		
		while($row = la_do_fetch_result($result_forms)){
			$row_forms[$row['field_name']] = $row['data_value'];
		}
		
		$element_array = array();
		$replace_data_array = array();
		$address_counter = 0;
		
		$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_type` != 'section' AND `element_type` != 'page_break' order by element_id asc";
		
		$param_form_element = array();
		$param_form_element[':form_id'] = $form_id;
		$result_form_element = la_do_query($query_form_element,$param_form_element,$dbh);
		
		while($row_form_template = la_do_fetch_result($result_form_element)){
			$element_array[$row_form_template['element_id']] 						 			 = array();
			$element_array[$row_form_template['element_id']]['element_type'] 		 			 = $row_form_template['element_type'];
			$element_array[$row_form_template['element_id']]['element_machine_code'] 			 = $row_form_template['element_machine_code'];
			$element_array[$row_form_template['element_id']]['element_matrix_allow_multiselect'] = $row_form_template['element_matrix_allow_multiselect'];
			$element_array[$row_form_template['element_id']]['element_matrix_parent_id'] 		 = $row_form_template['element_matrix_parent_id'];
			$element_array[$row_form_template['element_id']]['element_id'] 						 = $row_form_template['element_id'];
			$element_array[$row_form_template['element_id']]['element_default_value'] 			 = $row_form_template['element_default_value'];
		}
		
		if(count($element_array) > 0){
			foreach($element_array as $element_id => $element){
				$replace_string = array();
				if($element['element_type'] == 'simple_name'){
					
					if(trim($element['element_machine_code']) != '' && trim($element['element_machine_code']) != 'Null'){
						
						$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2'];
						
					}
					
				}
				elseif($element['element_type'] == 'address'){
					
					if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
								
						$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']." ".$row_forms['element_'.$element_id.'_3']." ".$row_forms['element_'.$element_id.'_4']." ".$row_forms['element_'.$element_id.'_5']." ".$row_forms['element_'.$element_id.'_6'];
						
					}					
				}
				elseif($element['element_type'] == 'radio' || $element['element_type'] == 'checkbox' || $element['element_type'] == 'matrix' || $element['element_type'] == 'select'){
					
					if($element['element_type'] == 'matrix'){
						$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
					}
					
					if($element['element_type'] == 'checkbox'){
						
						$query_element_option = "SELECT `option_id`, `option`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id ORDER BY `option_id` DESC";
						
						$param_element_option = array();
						$param_element_option[':form_id'] = $form_id;
						$param_element_option[':element_id'] = $element_id;
						$i= 0;
						$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
						
						while($row_element_option = la_do_fetch_result($result_element_option)){
							if(!empty($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]) && $row_forms['element_'.$element_id.'_'.$row_element_option['option_id']] >= 1){
								$replace_string[] =  $row_element_option['option'];
								$totalScore += $row_element_option['option_value'];							
							}
						}						
	
						if(isset($row_forms['element_'.$element_id.'_other']) && !empty($row_forms['element_'.$element_id.'_other'])){
							$replace_string[] =  $row_forms['element_'.$element_id.'_other'];
						}
						
						$_string = implode("\r\n", $replace_string);
						
						if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
							
							$replace_data_array[$element['element_machine_code']] = $_string;
						}
						
					}else{
					
						$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id";
						$param_element_option = array();
						$param_element_option[':form_id'] = $form_id;
						$param_element_option[':element_id'] = $element_id;
						$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
						$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
						$row_element_option = la_do_fetch_result($result_element_option);
						
						if(isset($row_forms['element_'.$element_id.'_other']) && !empty($row_forms['element_'.$element_id.'_other'])){
							$replace_string[] =  $row_forms['element_'.$element_id.'_other'];
						}
						if(!empty($row_element_option['option'])){
							$replace_string[] =  $row_element_option['option'];
						}
						$_string = implode("\r\n", $replace_string);
						
						if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
						
							$replace_data_array[$element['element_machine_code']] = $_string;
						}
	
						$totalScore += $row_element_option['option_value'];	
					}
					
				}
				elseif($element['element_type'] == 'phone'){
					$phone_val = substr($row_forms['element_'.$element_id],0,3).'-'.substr($row_forms['element_'.$element_id],3,3).'-'.substr($row_forms['element_'.$element_id],7,4);
					if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
						
						$replace_data_array[$element['element_machine_code']] = $phone_val;
					}
				
				}
				elseif($element['element_type'] == 'casecade_form'){
					$case_cade_replace_data_array = calculateScoreAndGenerateDoc(array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'company_id' => $company_id, 'user_id' => $user_id));
					
					// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
					$replace_data_array = array_merge($replace_data_array, $case_cade_replace_data_array);
				}
				elseif($element['element_type'] == 'date' || $element['element_type'] == 'europe_date'){
					if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
						$replace_data_array[$element['element_machine_code']] = date("m/d/Y", strtotime($row_forms['element_'.$element_id]));
					}
				}
				elseif($element['element_type'] == 'textarea'){
					if(trim($element['element_machine_code']) != '' && trim($element['element_machine_code']) != 'Null'){
						$replace_data_array[$element['element_machine_code']] = array();
						$replace_data_array[$element['element_machine_code']] = array('image' => $row_forms['element_'.$element_id]);
					}
				}
				else{
					if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
						$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id];
					}
						
				}
			}
	
			$timestamp = time();
			
			if($num_rows > 0){
				
				//echo '<pre>';print_r($replace_data_array);echo '</pre>';exit;
		
				$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
				$param_template = array();
				$param_template[':form_id'] = $form_id;
				$result_template = la_do_query($query_template,$param_template,$dbh);
				
				$iLoop = 1;	
				if(extension_loaded('zip')){
					
					/***************F O R M  N A M E ****************/
					$query_form  = "SELECT `form_name` FROM `ap_forms` WHERE `form_id` = :form_id";
					$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
					$row_form    = la_do_fetch_result($result_form);
					$form_name   = trim($row_form['form_name']);
					$form_name   = str_replace(" ", "_", $form_name);
					$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
					$form_name	 = substr($form_name, 0, 24);
					/***************F O R M  N A M E ****************/
					
					
					$zip = new ZipArchive();	
					$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";
					
					if($num_rows > 0){
						if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){		
							
						}
					}
					$zip_added_files = [];
					while($row_template = la_do_fetch_result($result_template)){
						
						$template_document = trim($row_template['template']);
						
						if(file_exists($template_document) == true){
							$template_arr = explode("/", $template_document);
							$template_raw_name = end($template_arr);
							$template_name_split = explode('.', $template_raw_name);
							$template_name = $template_name_split[0]."_".$timestamp;
							
							if(count($replace_data_array) > 0){
								$docx = new CreateDocxFromTemplate($template_document);
								
								// foreach($replace_data_array as $key => $value){
								// 	$docx->replaceVariableByText(array($key => $value));
								// }
								foreach($replace_data_array as $key => $value){
									
									if(is_array($value)) {
										if(count($value) > 0) {
											$content = '';
											$isHtml = false;
											if(isset($value['image'])) {
												$isHtml = true;
												$content .= $value['image'];
											}

											if(isset($value['file'])) {
												$content .= '<br>'.$value['file'];
											}

											if($isHtml) {
												if ($content != "") {
													$docxReplaceOptions = array('isFile' => false, 'parseDivsAsPs' => true, 'downloadImages' => true, 'firstMatch' => false);
													 $content .= '<style> p {font-size: 11pt; font-family: "Century Gothic" } </style>';																		

													$docx->replaceVariableByHtml($key, 'block', $content, $docxReplaceOptions);
												}
											}
											else {
												$docxReplaceOptions = array('firstMatch' => false);
												$docx->replaceVariableByText(array($key => $content),$docxReplaceOptions);
											}
										}
									}
									else {
										$docx->replaceVariableByText(array($key => $value));
									}
								}
								//$docx->setDefaultFont('Century Gothic');
								$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.docx');
							}
							
							$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `docx_create_date`, `docxname`) VALUES (null, :client_id, :company_id, :form_id, :docx_create_date, :docxname)";
							$params_docx_insert = array();
							$params_docx_insert[':client_id'] = (int) $user_id;
							$params_docx_insert[':company_id'] = (int) $company_id;
							$params_docx_insert[':form_id'] = $form_id;
							$params_docx_insert[':docx_create_date'] = $timestamp;
							$params_docx_insert[':docxname'] = (string) $template_name.'.docx';
							la_do_query($query_docx_insert,$params_docx_insert,$dbh);
							$iLoop++;
	
							$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.docx', $template_name.'.docx');
							$zip_added_files[] = $template_name.'.docx';
						}
						
					}
					
					if($num_rows > 0){
						$zip->close();
						$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `docx_create_date`, `docxname`, `isZip`, `added_files`) VALUES (null, :client_id, :company_id, :form_id, :docx_create_date, :docxname, '1', :added_files)";
						$params_docx_insert = array();
						$params_docx_insert[':client_id'] = (int) $user_id;
						$params_docx_insert[':company_id'] = (int) $company_id;
						$params_docx_insert[':form_id'] = $form_id;
						$params_docx_insert[':docx_create_date'] = $timestamp;
						$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp.".zip";
						$params_docx_insert[':added_files'] = implode(',', $zip_added_files);
						
						la_do_query($query_docx_insert,$params_docx_insert,$dbh);
					}
					
				}
			
			}
		}
		
		return $replace_data_array;
	}	
	
	function save_casecade_data($params=array())
	{
		if(!$params['form_id']){
			return;
		}
		
		// db connection
		$dbh = $params['dbh'];
		// settings information
		$la_settings = $params['la_settings'];
		
		$form_id = $params['form_id'];
		$edit_id = $params['edit_id'];
		$company_id = $params['company_id'];
		$input = $params['input'];
		$element_info = $params['element_info'];
		
		foreach($element_info as $k1 => $v1){
			if($v1['type'] != "file"){
				foreach($input as $k => $v){
					$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = :field_name AND company_id = :company_id";
					$result = la_do_query($query,array(':field_name' => $k, ':company_id' => $company_id),$dbh);
					$row    = la_do_fetch_result($result);
					
					// get element id to insert with code field
					$tmpArr = explode("_", $k);
					$element_id = $tmpArr[1];
					
					if(!$row['field_name']){
						$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = :company_id, `field_name` = :field_name, `field_code` = :field_code, data_value = :data_value";
						la_do_query($query_insert, array(':company_id' => $company_id, ':field_name' => $k, ':field_code' => "code_{$element_id}", ':data_value' => (!empty($v) ? $v : '')),$dbh);
					} else {
						$query_update = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET `data_value` = :data_value WHERE `field_name` = :field_name AND `company_id` = :company_id";
						la_do_query($query_update, array(':company_id' => $company_id, ':field_name' => $k, ':data_value' => (!empty($v) ? $v : '')),$dbh);
					}
				}
			}
			else{								
				$itauditmachine_data_path = '';
				
				$query_file = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = :field_name AND `company_id` = :company_id";
				$sth_file = la_do_query($query_file, array(":field_name" => "element_{$k1}", ":company_id" => $company_id), $dbh);
				$row_file = la_do_fetch_result($sth_file);
				
				if(!$row_file['field_name']){
					$file = $_SESSION["element_{$form_id}_{$k1}"];	
					$new_file = array(); 
					
					if(strpos($file,'|') !== false){					
						$file_explode = explode('|',$file);
						
						if(count($file_explode) > 0){														
							foreach($file_explode as $file_explode_value){	
								if(!empty($file_explode_value))	{						
									$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file_explode_value;
									
									if(file_exists($complete_filename)){									
										$new_file[] = $file_explode_value;
									}
								}
							}	
							
							$new_file = implode('|',$new_file);
						}						
					} else {						
						$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file;
						
						if(file_exists($complete_filename)){							
							$new_file = $file;							
						} else {							
							$new_file = '';
						}
					}
														
					$newfile1 = $new_file;
					
					if(substr($newfile1,0,1)== '|'){						
						$newfile1 = substr($newfile1,1);
					}
					
					$newfile1 = mysqli_real_escape_string ($newfile1);
					
					$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = :company_id, `field_name` = :field_name, `field_code` = :field_code, data_value = :data_value";
					la_do_query($query_insert, array(':company_id' => $company_id, ':field_name' => "element_{$k1}", ':field_code' => "code_{$k1}", ':data_value' => (!empty($newfile1) ? $newfile1 : '')), $dbh);					
					unset($_SESSION["element_{$form_id}_{$k1}"]);
				} 
				else{		
					$file = $_SESSION["element_{$form_id}_{$k1}"];					
					$new_file = array(); 
					
					if(strpos($file,'|') !== false){						
						$file_explode = explode('|',$file);
						
						if(count($file_explode) > 0){							
							foreach($file_explode as $file_explode_value){	
								if(!empty($file_explode_value)){					
									$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file_explode_value;
									
									if(file_exists($complete_filename)){									
										$new_file[] = $file_explode_value;
									}
								}
							}
							
							$new_file = implode('|',$new_file);
						}						
					} 
					else {						
						$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file;
						if(file_exists($complete_filename)){							
							$new_file = $file;							
						} else {							
							$new_file = '';
						}
					}
					
					if($new_file == ''){						
						$newfile1 = $row_file['data_value'];						
					} else {						
						$newfile1 = $row_file['data_value'].'|'.$new_file;
					}
					
					if(substr($newfile1,0,1)== '|'){						
						$newfile1 = substr($newfile1,1);
					}

					$query_update = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET `data_value` = :data_value WHERE `field_name` = :field_name AND `company_id` = :company_id";
					la_do_query($query_update, array(':company_id' => $company_id, ':field_name' => "element_{$k1}", ':data_value' => (!empty($newfile1) ? $newfile1 : '')), $dbh);
					
					unset($_SESSION["element_{$form_id}_{$k1}"]);					
				}							
			}
			
		}		
		
		if(!$edit_id){
			// insert data user_ip, date created, status
			$query_date_created = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = :company_id, `field_name` = :field_name, `field_code` = :field_code, data_value = :data_value";
			la_do_query($query_date_created, array(':company_id' => $company_id, ':field_name' => "ip_address", ':field_code' => "", ':data_value' => $_SERVER['REMOTE_ADDR']), $dbh);
	
			$query_date_created = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = :company_id, `field_name` = :field_name, `field_code` = :field_code, data_value = :data_value";
			la_do_query($query_date_created, array(':company_id' => $company_id, ':field_name' => "date_created", ':field_code' => "", ':data_value' => date('Y-m-d H:i:s')), $dbh);
	
			$query_date_created = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` SET `company_id` = :company_id, `field_name` = :field_name, `field_code` = :field_code, data_value = :data_value";
			la_do_query($query_date_created, array(':company_id' => $company_id, ':field_name' => "status", ':field_code' => "", ':data_value' => 1), $dbh);
		}
	}
	
	function la_process_casecade_form($params=array())
	{
		// db connection
		$dbh = $params['dbh'];
		
		// settings information
		$la_settings = $params['la_settings'];
		
		// form input fields with data
		$input = $params['input'];
		
		if(!$params['form_id']){
			return;	
		}
		
		$form_id = (int) trim($params['form_id']);
		$edit_id = (int) trim($params['edit_id']);
		$company_id = (int) trim($params['company_id']);
		$entry_id = (int) trim($params['entry_id']);

		$page_number = isset($params['page_number']) ? $params['page_number'] : 1;
		
		if($page_number == "NO_ELEMENTS"){
			$frm_query = "select form_page_total from `".LA_TABLE_PREFIX."forms` where `form_id`=:form_id";
			$frm_result = la_do_query($frm_query, array(':form_id' => $form_id), $dbh);
			$frm_row = la_do_fetch_result($frm_result);
			
			$page_number = $frm_row['form_page_total'];
		}
		
		$page_number_clause = $page_number ? ($page_number > 0 ? 'and element_page_number = ?' : '') : 'and element_page_number = ?';
		$element_params = $page_number ? ($page_number > 0 ? array($form_id, $page_number) : array($form_id)) : array($form_id, $page_number);
		
		$element_child_lookup['address'] 	 = 5;
		$element_child_lookup['simple_name'] = 1;
		$element_child_lookup['simple_name_wmiddle'] = 2;
		$element_child_lookup['name'] 		 = 3;
		$element_child_lookup['name_wmiddle'] = 4;
		$element_child_lookup['phone'] 		 = 2;
		$element_child_lookup['date'] 		 = 2;
		$element_child_lookup['europe_date'] = 2;
		$element_child_lookup['time'] 		 = 3;
		$element_child_lookup['money'] 		 = 1; //this applies to dollar,euro and pound. yen don't have child
		$element_child_lookup['checkbox'] 	 = 1; //this is just a dumb value
		$element_child_lookup['matrix'] 	 = 1; //this is just a dumb value
		
		//never trust user input, get a list of input fields based on info stored on table
		//element has real child -> address, simple_name, name, simple_name_wmiddle, name_wmiddle
		//element has virtual child -> phone, date, europe_date, time, money
		
		$query = "SELECT 
						element_id,
       					element_title,
       					element_is_required,
       					element_is_unique,
       					element_is_private,
       					element_type, 
       					element_constraint,
       					element_total_child,
       					element_file_enable_multi_upload,
       					element_file_max_selection,
       					element_file_enable_type_limit,
       					element_file_block_or_allow,
       					element_file_type_list,
       					element_range_max,
       					element_range_min,
       					element_range_limit_by,
       					element_choice_has_other,
       					element_choice_other_score,
       					element_time_showsecond,
       					element_time_24hour,
       					element_matrix_parent_id,
       					element_matrix_allow_multiselect,
       					element_date_enable_range,
       					element_date_range_min,
       					element_date_range_max,
       					element_date_past_future,
       					element_date_disable_past_future,
       					element_date_enable_selection_limit,
						element_date_selection_max,
						element_date_disable_weekend,
						element_date_disable_specific,
						element_date_disabled_list,
						element_default_value,
						element_machine_code,
						element_file_upload_synced,
						element_file_enable_advance,
						element_file_select_existing_files
					FROM 
						".LA_TABLE_PREFIX."form_elements 
				   WHERE 
				   		form_id=? and element_status = '1' and element_type <> 'page_break' and element_type <> 'section' {$page_number_clause}
				ORDER BY 
						element_position asc";
		
		$sth = la_do_query($query, $element_params, $dbh);		
		
		$element_to_get = array();
		$private_elements = array(); //admin-only fields
		$matrix_childs_array = array();
		$element_info = array();
		
		while($row = la_do_fetch_result($sth)){
			
			if($row['element_type'] == 'section'){
				continue;
			}
			
			//store element info
			$element_info[$row['element_id']]['title'] = $row['element_title'];
			$element_info[$row['element_id']]['type'] = $row['element_type'];
			$element_info[$row['element_id']]['is_required'] = $row['element_is_required'];
			$element_info[$row['element_id']]['is_unique'] = $row['element_is_unique'];
			$element_info[$row['element_id']]['is_private'] = $row['element_is_private'];
			$element_info[$row['element_id']]['constraint'] = $row['element_constraint'];
			$element_info[$row['element_id']]['file_enable_multi_upload'] = $row['element_file_enable_multi_upload'];
			$element_info[$row['element_id']]['file_max_selection'] = $row['element_file_max_selection'];
			$element_info[$row['element_id']]['file_enable_type_limit'] = $row['element_file_enable_type_limit'];
			$element_info[$row['element_id']]['file_block_or_allow'] = $row['element_file_block_or_allow'];
			$element_info[$row['element_id']]['file_type_list'] = $row['element_file_type_list'];
			$element_info[$row['element_id']]['range_min'] = $row['element_range_min'];
			$element_info[$row['element_id']]['range_max'] = $row['element_range_max'];
			$element_info[$row['element_id']]['range_limit_by'] = $row['element_range_limit_by'];
			$element_info[$row['element_id']]['choice_has_other'] = $row['element_choice_has_other'];
			$element_info[$row['element_id']]['choice_other_score'] = $row['element_choice_other_score'];
			$element_info[$row['element_id']]['time_showsecond'] = (int) $row['element_time_showsecond'];
			$element_info[$row['element_id']]['time_24hour'] = (int) $row['element_time_24hour'];
			$element_info[$row['element_id']]['matrix_parent_id'] = (int) $row['element_matrix_parent_id'];
			$element_info[$row['element_id']]['matrix_allow_multiselect'] = (int) $row['element_matrix_allow_multiselect'];
			$element_info[$row['element_id']]['date_enable_range'] = (int) $row['element_date_enable_range'];
			$element_info[$row['element_id']]['date_range_max'] = $row['element_date_range_max'];
			$element_info[$row['element_id']]['date_range_min'] = $row['element_date_range_min'];
			$element_info[$row['element_id']]['date_past_future'] = $row['element_date_past_future'];
			$element_info[$row['element_id']]['date_disable_past_future'] = (int) $row['element_date_disable_past_future'];
			$element_info[$row['element_id']]['date_enable_selection_limit'] = (int) $row['element_date_enable_selection_limit'];
			$element_info[$row['element_id']]['date_selection_max'] = (int) $row['element_date_selection_max'];
			$element_info[$row['element_id']]['date_disable_weekend'] = (int) $row['element_date_disable_weekend'];
			$element_info[$row['element_id']]['date_disable_specific'] = (int) $row['element_date_disable_specific'];
			$element_info[$row['element_id']]['date_disabled_list'] = $row['element_date_disabled_list'];
			$element_info[$row['element_id']]['default_value'] = $row['element_default_value'];
			$element_info[$row['element_id']]['element_machine_code'] = $row['element_machine_code'];
			$element_info[$row['element_id']]['file_upload_synced'] = (int) $row['element_file_upload_synced'];
			$element_info[$row['element_id']]['file_enable_advance'] = (int) $row['element_file_enable_advance'];
			$element_info[$row['element_id']]['file_select_existing_files'] = (int) $row['element_file_select_existing_files'];
			
			//get element form name, complete with the childs			
			if(empty($element_child_lookup[$row['element_type']]) || ($row['element_constraint'] == 'yen')){ //elements with no child
				$element_to_get[] = 'element_'.$row['element_id'];			
			}
			else{ 
				//elements with child
				if($row['element_type'] == 'checkbox' || ($row['element_type'] == 'matrix' && !empty($row['element_matrix_allow_multiselect']))){
					
					//for checkbox, get childs elements from ap_element_options table 
					$sub_query = "select 
										option_id 
									from 
										".LA_TABLE_PREFIX."element_options 
								   where 
								   		form_id=? and element_id=? and live=1 
								order by 
										`position` asc";
					$params = array($form_id,$row['element_id']);
					
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					
					while($sub_row = la_do_fetch_result($sub_sth)){
						$element_to_get[] = "element_{$row['element_id']}_{$sub_row['option_id']}";
						$checkbox_childs[$row['element_id']][] =  $sub_row['option_id']; //store the child into array for further reference
					}
					
					//if this is the parent of the matrix (checkbox matrix only), get the child as well
					if($row['element_type'] == 'matrix' && !empty($row['element_matrix_allow_multiselect'])){
						
						$temp_matrix_child_element_id_array = explode(',',trim($row['element_constraint']));
						
						foreach ($temp_matrix_child_element_id_array as $mc_element_id){
							$sub_query = "select 
											option_id 
										from 
											".LA_TABLE_PREFIX."element_options 
									   where 
									   		form_id=? and element_id=? and live=1 
									order by 
											`position` asc";
							$params = array($form_id,$mc_element_id);
							
							$sub_sth = la_do_query($sub_query,$params,$dbh);
							
							while($sub_row = la_do_fetch_result($sub_sth)){
								$element_to_get[$mc_element_id][] = "element_{$mc_element_id}_{$sub_row['option_id']}";
								$checkbox_childs[$mc_element_id][] =  $sub_row['option_id']; //store the child into array for further reference
							}
							
						}
					}
				}else if($row['element_type'] == 'matrix' && empty($row['element_matrix_allow_multiselect'])){ //radio button matrix, each row doesn't have childs
					$element_to_get[] = 'element_'.$row['element_id'];
				}else{
					$max = $element_child_lookup[$row['element_type']] + 1;
					
					for ($j=1;$j<=$max;$j++){
						$element_to_get[] = "element_{$row['element_id']}_{$j}";
					}
				}
			}			
			
			//if the back button pressed after review page, or this is multipage form, we need to store the file info
			if((!empty($_SESSION['review_id']) && !empty($form_review)) || ($form_page_total > 1) || ($is_edit_page === true)){
				if($row['element_type'] == 'file'){
					$existing_file_id[] = $row['element_id'];
				}
			}
			
			//if this is matrix field, particularly the child rows, we need to store the id into temporary array
			//we need to loop through it later, to set the "required" property based on the matrix parent value
			if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){
				$matrix_childs_array[$row['element_id']] = $row['element_matrix_parent_id'];
			}
			//if this is text field, check for the coupon code field status
			if(($form_properties['payment_enable_merchant'] == 1) && ($is_edit_page === false) && !empty($form_properties['payment_enable_discount']) && 
				!empty($form_properties['payment_discount_element_id']) && !empty($form_properties['payment_discount_code']) &&
				($form_properties['payment_discount_element_id'] == $row['element_id'])){
				
				$element_info[$row['element_id']]['is_coupon_field'] = true;
			}
			//extra security measure for file upload
			//even though the user disabled 'file type limit', we need to enforce it here and block dangerous files
			if($row['element_type'] == 'file'){
				
				//if the 'Limit File Upload Type' disabled by user, enable it here and check for dangerous files
				if(empty($row['element_file_enable_type_limit'])){
					$element_info[$row['element_id']]['file_enable_type_limit'] = 1;
					$element_info[$row['element_id']]['file_block_or_allow'] = 'b'; //block
					$element_info[$row['element_id']]['file_type_list'] = 'php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
				}else{
					//if the limit being enabled but the list type is empty
					if(empty($element_info[$row['element_id']]['file_type_list'])){
						$element_info[$row['element_id']]['file_block_or_allow'] = 'b'; //block
						$element_info[$row['element_id']]['file_type_list'] = 'php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
					}else{
						//if the list is not empty, and it set to block files, make sure to add dangerous file types into the list
						if($element_info[$row['element_id']]['file_block_or_allow'] == 'b'){
							$element_info[$row['element_id']]['file_type_list'] .= ',php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
						}
					}
				}
			}
		}

		//loop through each matrix childs array
		//if the parent matrix has required=1, the child need to be set the same
		//if the parent matrix allow multi select, the child need to be set the same
		if(!empty($matrix_childs_array)){
			foreach ($matrix_childs_array as $matrix_child_element_id=>$matrix_parent_element_id){
				if(!empty($element_info[$matrix_parent_element_id]['is_required'] )){
					$element_info[$matrix_child_element_id]['is_required'] = 1; 
				}
				if(!empty($element_info[$matrix_parent_element_id]['matrix_allow_multiselect'] )){
					$element_info[$matrix_child_element_id]['matrix_allow_multiselect'] = 1; 
				}
			}
		}
		
		if(!empty($existing_file_id)){
			$existing_file_id_list = '';
			foreach ($existing_file_id as $value){
				$existing_file_id_list .= 'element_'.$value.',';
			}
			$existing_file_id_list = rtrim($existing_file_id_list,',');
			
		}		
		
		$element_option_lookup = array();
		
		//get form element options first (checkboxes, choices, dropdown)
		$query = "select 
						`element_id`,
						`option_id`,
						`option`,
						`option_value`
					from 
						`".LA_TABLE_PREFIX."element_options` 
				   where 
						`form_id`=? and `live`=1 
				order by 
						`element_id`, `position` asc";
		$sth = la_do_query($query,array($form_id),$dbh);
			
		while($row = la_do_fetch_result($sth)){
			$element_option_lookup[$row['element_id']][$row['option_id']] = array('option' => $row['option'], 'option_value' => $row['option_value']);
		}	
		
		//pick user input
		$user_input = array();
		foreach($element_to_get as $k => $element_name){
			$user_input[$element_name] = @$input[$element_name];
		}
		
		$error_elements = array();
		$table_data = array();
		
		//validate input based on rules specified for each field
		foreach ($user_input as $element_name => $element_data)
		{ // foreach start here : 1
			
			//get element_id from element_name
			$exploded = array();
			$exploded = explode('_',$element_name);
			$element_id = $exploded[1];
			
			$rules = array();
			$target_input = array();
			
			$element_type = $element_info[$element_id]['type'];
			
			//print_r($element_info[$element_id]);die;
			
			//if this is private fields and not logged-in as admin, bypass operation below, just supply the default value if any
			//if this is private fields and logged-in as admin and this is not edit-entry page, bypass operation below as well
			if($element_info[$element_id]['is_private'] == 1){
				if(!empty($element_info[$element_id]['default_value'])){
					$table_data['element_'.$element_id] = $element_info[$element_id]['default_value'];
					
					if('date' == $element_type || 'europe_date' == $element_type){
						if(strpos($element_info[$element_id]['default_value'], "/") !== false){
							$tmpValueArr = explode("/", $element_info[$element_id]['default_value']);
							$table_data['element_'.$element_id] = $tmpValueArr[2]."-".$tmpValueArr[0]."-".$tmpValueArr[1];
						}else if($element_info[$element_id]['default_value'] == "today"){
							$table_data['element_'.$element_id] = date("Y-m-d");
						}else if($element_info[$element_id]['default_value'] == "tomorrow"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("+1 day"));
						}else if($element_info[$element_id]['default_value'] == "last friday"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("last Friday"));
						}else if($element_info[$element_id]['default_value'] == "+1 week"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("+1 week"));
						}else if($element_info[$element_id]['default_value'] == "last day of next month"){
							$table_data['element_'.$element_id] = date("Y-m-t", strtotime("+1 month"));
						}else if($element_info[$element_id]['default_value'] == "3 days ago"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("-3 day"));
						}else if($element_info[$element_id]['default_value'] == "monday next week"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("next Monday"));
						}
					}
				}
				continue;
			}
			
			//if this is matrix field, we need to convert the field type into radio button or checkbox
			if('matrix' == $element_type){
				$is_matrix_field = true;
				if(!empty($element_info[$element_id]['matrix_allow_multiselect'])){
					$element_type = 'checkbox';
				}else{
					$element_type = 'radio';
				}
			}
			else{
				$is_matrix_field = false;
			}			
			
			if ('text' == $element_type){ //Single Line Text
											
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] = $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
						
				if(!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}
				
				if($element_info[$element_id]['is_coupon_field'] === true){
					$rules[$element_name]['coupon'] = $form_id;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'coupon' rule
				}
				
				$target_input[$element_name] = $element_data;
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('textarea' == $element_type){ //Paragraph
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				if(!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}
												
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('signature' == $element_type){ //Signature
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				$target_input[$element_name] = $element_data;
				if($target_input[$element_name] == '[]'){ //this is considered as empty signature
					$target_input[$element_name] = '';
				}
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data,ENT_NOQUOTES); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('radio' == $element_type){ //Multiple Choice
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name.'_other'] = '';
				}
				
				//if this field has 'other' label
				if(!empty($element_info[$element_id]['choice_has_other'])){
					if(empty($element_data) && !empty($input[$element_name.'_other'])){
						$element_data = $input[$element_name.'_other'];
						
						//save old data into array, for form redisplay in case errors occured
						$form_data[$element_name.'_other']['default_value'] = $element_data; 
						$table_data[$element_name.'_other'] = $element_data;
						//make sure to set the main element value to 0
						$form_data[$element_name]['default_value'] = 0; 
						$table_data[$element_name] = 0;
					}
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					if($is_matrix_field && !empty($matrix_childs_array[$element_id])){
						$error_elements[$matrix_childs_array[$element_id]] = $validation_result;
					}else{
						$error_elements[$element_id] = $validation_result;
					}
				}
				
				//save old data into array, for form redisplay in case errors occured
				if(empty($form_data[$element_name.'_other']['default_value'])){
					$form_data[$element_name]['default_value'] = $element_data; 
				}
				
				//prepare data for table column
				if(empty($table_data[$element_name.'_other'])){
					$table_data[$element_name] = $element_data; 
				}
				
			}
			elseif ('number' == $element_type){ //Number
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				//check for numeric if not empty
				if(!empty($user_input[$element_name])){ 
					$rules[$element_name]['numeric'] = true;
				}
				
				if((!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])) && ($element_info[$element_id]['range_limit_by'] == 'd')){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}else if((!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])) && ($element_info[$element_id]['range_limit_by'] == 'v')){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_value'] = $element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_value'] = $element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_value'] = $element_info[$element_id]['range_min'];
					}	
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
				//if the user removed the number, set the value to null
				if($table_data[$element_name] == ""){
					$table_data[$element_name] = null;
				}
			}
			elseif ('url' == $element_type){ //Website
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$rules[$element_name]['website'] = true;
														
				if($element_data == 'http://'){
					$element_data = '';
				}
						
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('email' == $element_type){ //Email
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$rules[$element_name]['email'] = true;
														
										
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('simple_name' == $element_type){ //Simple Name
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 2 elements total	
				$element_name_2 = substr($element_name,0,-1).'2';
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed on next loop
				
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
				}
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
					$rules[$element_name_2]['required'] = true;
				}
																		
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_2] = $user_input[$element_name_2];
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				
			}
			elseif ('simple_name_wmiddle' == $element_type){ //Simple Name with Middle
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other elements, 3 elements total	
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed on next loop
				$processed_elements[] = $element_name_3;
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
																		
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				
			}
			elseif ('name' == $element_type){ //Name -  Extended
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 4 elements total	
				//only element no 2&3 matters (first and last name)
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
				}
																		
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				
			}
			elseif ('name_wmiddle' == $element_type){ //Name -  Extended, with Middle
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 5 elements total	
				//only element no 2,3,4 matters (first, middle, last name)
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				$element_name_5 = substr($element_name,0,-1).'5';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
				$processed_elements[] = $element_name_5;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_4]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
					$user_input[$element_name_5] = '';
				}
																		
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				$form_data[$element_name_5]['default_value'] = htmlspecialchars($user_input[$element_name_5]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				$table_data[$element_name_5] = $user_input[$element_name_5];
				
			}
			elseif ('time' == $element_type){ //Time
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 4 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
					if(empty($element_info[$element_id]['time_24hour'])){
						$rules[$element_name_4]['required'] = true;
					}
				}
				//check time validity if any of the compound field entered
				$time_entry_exist = false;
				if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2]) || !empty($user_input[$element_name_3])){
					$rules['element_time']['time'] = true;
					$time_entry_exist = true;
				}
				
				//for backward compatibility with itauditmachine v2 and beyond
				if($element_info[$element_id]['constraint'] == 'show_seconds'){
					$element_info[$element_id]['time_showsecond'] = 1;
				}
				
				if($time_entry_exist && empty($element_info[$element_id]['time_showsecond'])){
					$user_input[$element_name_3] = '00';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules['element_time_no_meridiem']['unique'] = $form_id.'#'.substr($element_name,0,-2); //to check uniquenes we need to use 24 hours HH:MM:SS format
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
							
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				if($time_entry_exist){
					$target_input['element_time']  = trim($user_input[$element_name].':'.$user_input[$element_name_2].':'.$user_input[$element_name_3].' '.$user_input[$element_name_4]);
					$target_input['element_time_no_meridiem'] = @date("G:i:s",strtotime($target_input['element_time']));
				}
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				if($element_info[$element_id]['is_hidden']){
					$target_input['element_time_no_meridiem'] = '';
				}
				
				//prepare data for table column
				$table_data[substr($element_name,0,-2)] 	 = @$target_input['element_time_no_meridiem'];
								
			}
			elseif ('address' == $element_type){ //Address
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 6 elements total, element #2 (address line 2) is optional	
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				$element_name_5 = substr($element_name,0,-1).'5';
				$element_name_6 = substr($element_name,0,-1).'6';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
				$processed_elements[] = $element_name_5;
				$processed_elements[] = $element_name_6;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] = true;
					$rules[$element_name_3]['required'] = true;
					$rules[$element_name_4]['required'] = true;
					$rules[$element_name_5]['required'] = true;
					$rules[$element_name_6]['required'] = true;
					
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = ''; 
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
					$user_input[$element_name_5] = '';
					$user_input[$element_name_6] = '';
				}
				
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				$target_input[$element_name_5] = $user_input[$element_name_5];
				$target_input[$element_name_6] = $user_input[$element_name_6];
			
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				$form_data[$element_name_5]['default_value'] = htmlspecialchars($user_input[$element_name_5]);
				$form_data[$element_name_6]['default_value'] = htmlspecialchars($user_input[$element_name_6]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				$table_data[$element_name_5] = $user_input[$element_name_5];
				$table_data[$element_name_6] = $user_input[$element_name_6];
				
			}
			elseif ('money' == $element_type){ //Price
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 2 elements total (for currency other than yen)	
				if($element_info[$element_id]['constraint'] != 'yen'){ //if other than yen
					$base_element_name = substr($element_name,0,-1);
					$element_name_2 = $base_element_name.'2';
					$processed_elements[] = $element_name_2;
										
					if($element_info[$element_id]['is_required']){
						$rules[$base_element_name]['required'] 	= true;
					}
					if($element_info[$element_id]['is_hidden']){
						$user_input[$element_name] = '';
						$user_input[$element_name_2] = '';
					}
					
					//check for numeric if not empty
					if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2])){
						$rules[$base_element_name]['numeric'] = true;
					}
					
					if($element_info[$element_id]['is_unique']){
						$rules[$base_element_name]['unique'] 	= $form_id.'#'.substr($element_name,0,-2);
						$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
					}
				
					$target_input[$base_element_name]   = $user_input[$element_name].'.'.$user_input[$element_name_2]; //join dollar+cent
					if($target_input[$base_element_name] == '.'){
						$target_input[$base_element_name] = '';
					}
					
					//save old data into array, for form redisplay in case errors occured
					$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
					$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
					
					//prepare data for table column
					if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2]) 
					   || $user_input[$element_name] === '0' || $user_input[$element_name_2] === '0'){
						$table_data[substr($element_name,0,-2)] = $user_input[$element_name].'.'.$user_input[$element_name_2];
					}
					
					//if the user removed the number, set the value to null
					if($user_input[$element_name] == "" && $user_input[$element_name_2] == ""){
						$table_data[substr($element_name,0,-2)] = null;
					} 		
				}else{
					if($element_info[$element_id]['is_required']){
						$rules[$element_name]['required'] 	= true;
					}
					
					if($element_info[$element_id]['is_hidden']){
						$user_input[$element_name] = '';
					}
					//check for numeric if not empty
					if(!empty($user_input[$element_name])){ 
						$rules[$element_name]['numeric'] = true;
					}
					
					if($element_info[$element_id]['is_unique']){
						$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
						$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
					}
									
					$target_input[$element_name]   = $user_input[$element_name];
					
					//save old data into array, for form redisplay in case errors occured
					$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
					
					//prepare data for table column
					$table_data[$element_name] 	 = $user_input[$element_name];
					
					//if the user removed the number, set the value to null
					if($table_data[$element_name] == ""){
						$table_data[$element_name] = null;
					} 
								
				}
								
				
												
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
			}
			elseif ('checkbox' == $element_type){ //Checkboxes
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				
				$all_child_array = array();
				$all_child_array = $checkbox_childs[$element_id];  
				
				
				$base_element_name = 'element_' . $element_id . '_';
					
				if(!empty($element_info[$element_id]['choice_has_other'])){
					$all_checkbox_value = $input[$base_element_name.'other'];
						
					//save old data into array, for form redisplay in case errors occured
					$form_data[$base_element_name.'other']['default_value'] = $input[$base_element_name.'other']; 
					if($element_info[$element_id]['is_hidden']){
						$input[$base_element_name.'other'] = '';
					}
						
					$table_data[$base_element_name.'other'] = $input[$base_element_name.'other'];
				}else{
					$all_checkbox_value = '';
				}
				
				if($element_info[$element_id]['is_required']){
					//checking 'required' for checkboxes is more complex
					//we need to get total child, and join it into one element
					//only one element is required to be checked
					
					foreach ($all_child_array as $i){
						$all_checkbox_value .= $user_input[$base_element_name.$i];
						$processed_elements[] = $base_element_name.$i;
						
						//save old data into array, for form redisplay in case errors occured
						$form_data[$base_element_name.$i]['default_value']   = $user_input[$base_element_name.$i];
						
						//prepare data for table column
						$table_data[$base_element_name.$i] = $user_input[$base_element_name.$i];
				
					}
					
					$rules[$base_element_name]['required'] 	= true;
					
					$target_input[$base_element_name] = $all_checkbox_value;
					$validation_result = la_validate_element($target_input,$rules);
					
					if($validation_result !== true){
						if($is_matrix_field && !empty($matrix_childs_array[$element_id])){
							$error_elements[$matrix_childs_array[$element_id]] = $validation_result;
						}else{
							$error_elements[$element_id] = $validation_result;
						}
					}	
					
				}else{ //if not required, we only need to capture all data
						
					foreach ($all_child_array as $i){
											
						//save old data into array, for form redisplay in case errors occured
						$form_data[$base_element_name.$i]['default_value']   = $user_input[$base_element_name.$i];
						
						if($element_info[$element_id]['is_hidden']){
							$user_input[$base_element_name.$i] = '';
						}
						//prepare data for table column
						$table_data[$base_element_name.$i] = $user_input[$base_element_name.$i];
					}
					
				}
			}
			elseif ('select' == $element_type){ //Drop Down
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = $user_input[$element_name]; 
				
				//prepare data for table column
				$table_data[$element_name] = $user_input[$element_name]; 
				
			}
			elseif ('date' == $element_type || 'europe_date' == $element_type){ //Date
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 3 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
								
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
												
				if(!empty($element_info[$element_id]['is_required'])){
					$rules[$element_name]['required'] = true;
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name]	 = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
				
				$rules['element_date']['date'] = 'yyyy/mm/dd';
				
				if(!empty($element_info[$element_id]['is_unique'])){
					$rules['element_date']['unique'] = $form_id.'#'.substr($element_name,0,-2); 
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				if(!empty($element_info[$element_id]['date_enable_range'])){
					if(!empty($element_info[$element_id]['date_range_max']) || !empty($element_info[$element_id]['date_range_min'])){
						$rules['element_date']['date_range'] = $element_info[$element_id]['date_range_min'].'#'.$element_info[$element_id]['date_range_max'];
					}
				}
				
				//disable past/future dates, if enabled. this rule override the date range rule being set above
				if(!empty($element_info[$element_id]['date_disable_past_future']) && $is_edit_page === false){
					$today_date = date('Y-m-d',$time);
					
					if($element_info[$element_id]['date_past_future'] == 'p'){ //disable past dates
						$rules['element_date']['date_range'] = $today_date.'#0000-00-00';
					}else if($element_info[$element_id]['date_past_future'] == 'f'){ //disable future dates
						$rules['element_date']['date_range'] = '0000-00-00#'.$today_date;
					}
				}
				
				//check for weekend dates rule
				if(!empty($element_info[$element_id]['date_disable_weekend'])){
					$rules['element_date']['date_weekend'] = true;
				}
				
				//get disabled dates (either coming from 'date selection limit' or 'disable specific dates' rules)
				$disabled_dates = array();
				
				//get disabled dates from 'date selection limit' rule
				if(!empty($element_info[$element_id]['date_enable_selection_limit']) && !empty($element_info[$element_id]['date_selection_max'])){
					
					//if this is edit entry page, bypass the date selection limit rule when the selection being made is the same
					$disabled_date_where_clause = '';
					if(!empty($edit_id) && ($_SESSION['la_logged_in'] === true || $_SESSION['la_client_logged_in'] === true)){ // edited on 29 oct 2014
						$disabled_date_where_clause = "AND `id` <> {$edit_id}";
					}
					
					/*$sub_query = "select 
										selected_date 
									from (
											select 
												  date_format(element_{$element_id},'%Y-%c-%e') as selected_date,
												  count(element_{$element_id}) as total_selection 
										      from 
										      	  ".LA_TABLE_PREFIX."form_{$form_id} 
										     where 
										     	  status=1 and element_{$element_id} is not null {$disabled_date_where_clause} 
										  group by 
										  		  element_{$element_id}
										 ) as A
								   where 
										 A.total_selection >= ?";*/
					
					//$sub_query = "select selected_date from ( select date_format(element_{$element_id},'%Y-%c-%e') as selected_date, count(element_{$element_id}) as total_selection from ".LA_TABLE_PREFIX."form_{$form_id} where status=1 and element_{$element_id} is not null {$disabled_date_where_clause} group by element_{$element_id} ) as A where A.total_selection >= ?";
					
					$sub_query = "select selected_date from (select date_format(`data_value`, '%Y-%c-%e') as selected_date, count(`data_value`) as total_selection from `".LA_TABLE_PREFIX."form_{$form_id}` where `field_name` = 'element_{$row['element_id']}' and `data_value` is not null {$disabled_date_where_clause} group by `data_value`) as A where A.total_selection >= ?";
					
					$params = array($element_info[$element_id]['date_selection_max']);
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					
					while($sub_row = la_do_fetch_result($sub_sth)){
						$disabled_dates[] = $sub_row['selected_date'];
					}
				}
				
				
				//get disabled dates from 'disable specific dates' rules
				if(!empty($element_info[$element_id]['date_disable_specific']) && !empty($element_info[$element_id]['date_disabled_list'])){
					$exploded = array();
					$exploded = explode(',',$element_info[$element_id]['date_disabled_list']);
					foreach($exploded as $date_value){
						$disabled_dates[] = date('Y-n-j',strtotime(trim($date_value)));
					}
				}
				
				if(!empty($disabled_dates)){
					$rules['element_date']['disabled_dates'] = $disabled_dates;
				}
				
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				$base_element_name = substr($element_name,0,-2);
				if('date' == $element_type){ //MM/DD/YYYY
					$target_input['element_date'] = $user_input[$element_name_3].'-'.$user_input[$element_name].'-'.$user_input[$element_name_2];
					
					//prepare data for table column
					$table_data[$base_element_name] = $user_input[$element_name_3].'-'.$user_input[$element_name].'-'.$user_input[$element_name_2];
				}else{ //DD/MM/YYYY
					$target_input['element_date'] = $user_input[$element_name_3].'-'.$user_input[$element_name_2].'-'.$user_input[$element_name];
					
					//prepare data for table column
					$table_data[$base_element_name] = $user_input[$element_name_3].'-'.$user_input[$element_name_2].'-'.$user_input[$element_name];
				}
				
				$test_empty = str_replace('-','',$target_input['element_date']); //if user not submitting any entry, remove the dashes
				if(empty($test_empty)){
					unset($target_input['element_date']);
					$table_data[$base_element_name] = '';
				}
										
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
								
			}
			elseif ('simple_phone' == $element_type){ //Simple Phone
							
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
				}
				
				if(!empty($user_input[$element_name])){
					$rules[$element_name]['simple_phone'] = true;
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
									
				$target_input[$element_name]   = $user_input[$element_name];
							
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				
				//prepare data for table column
				$table_data[$element_name] = $user_input[$element_name]; 
								
			}
			elseif ('phone' == $element_type){ //Phone - US format
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 3 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
								
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
												
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required']   = true;
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				
				$rules['element_phone']['phone'] = true;
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name]   = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
				
				
				if($element_info[$element_id]['is_unique']){
					$rules['element_phone']['unique'] = $form_id.'#'.substr($element_name,0,-2); 
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$target_input[$element_name]   = $user_input[$element_name];			
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input['element_phone'] = $user_input[$element_name].$user_input[$element_name_2].$user_input[$element_name_3];
									
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				
				//prepare data for table column
				$table_data[substr($element_name,0,-2)] = $user_input[$element_name].$user_input[$element_name_2].$user_input[$element_name_3];
				
			}
			elseif ('file' == $element_type){ //File
				
				if($element_info[$element_id]['file_enable_advance'] == 1 && $element_info[$element_id]['file_select_existing_files'] == 1) {
					if($element_info[$element_id]['file_upload_synced'] == 1 && !empty($element_info[$element_id]['element_machine_code'])) {
						//copy selected existing files to /auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']} and insert the file names into ap_file_upload_synced table
						if($input["element_".$element_id."_selected_existing_files"] != "") {
							
							$newly_added_files = array();
							$existing_files = array();
							//make a folder if it doesn't exist
							if (!file_exists($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}")) {
								mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}", 0777, true);
							}

							$selected_files = explode("|", $input["element_".$element_id."_selected_existing_files"]);
							foreach ($selected_files as $selected_file) {
								$time_stamp = time();
								$selected_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/".explode("*", $selected_file)[0];
								$destination_file_token = $input["element_".$element_id."_token"];
								$destination_file_name = "{$time_stamp}_{$form_id}_{$element_id}_{$destination_file_token}-".explode("*", $selected_file)[1];
								
								$destination_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}/{$destination_file_name}";
								if(copy($selected_file_path, $destination_file_path)) {
									array_push($newly_added_files, $destination_file_name);
								}
							}
							if(count($newly_added_files) > 0) {
								$query_file = "SELECT `id`, `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ? LIMIT 1;";
								$sth_file = la_do_query($query_file, array($element_info[$element_id]['element_machine_code'], $company_id), $dbh);
								$row_file = la_do_fetch_result($sth_file);
								if($row_file["id"]) {
									$existing_files = json_decode($row_file['files_data']);
									$existing_files = array_merge($existing_files, $newly_added_files);
									array_unique($existing_files);
									$query  = "UPDATE `".LA_TABLE_PREFIX."file_upload_synced` SET `files_data` = ? WHERE `element_machine_code` = ? AND company_id = ?;";
									la_do_query($query, array(json_encode($existing_files), $element_info[$element_id]['element_machine_code'], $company_id), $dbh);
								} else {
									$query  = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`element_machine_code`, `files_data`, company_id) VALUES (?, ?, ?);";
									la_do_query($query, array($element_info[$element_id]['element_machine_code'], json_encode($newly_added_files), $company_id), $dbh);
								}
								//flipping status indicators
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $element_id, $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $element_id, $company_id, $entry_id, 2), $dbh);
							}
						}
					} else {
						//copy selected existing files to /auditprotocol/data/form_{$form_id}/files/ and insert the file names into DB
						if($input["element_".$element_id."_selected_existing_files"] != "") {
							$tmpQryArr = array();
							$tmpDatArr = array();
							$eth_data_Arr = array();
							$newly_added_files = array();
							$existing_files = array();
							//make a folder if it doesn't exist
							if (!file_exists($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files")) {
								mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files", 0777, true);
							}

							$selected_files = explode("|", $input["element_".$element_id."_selected_existing_files"]);
							foreach ($selected_files as $selected_file) {
								$selected_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/".explode("*", $selected_file)[0];
								$destination_file_token = $input["element_".$element_id."_token"];
								$destination_file_name = "element_{$element_id}_{$destination_file_token}-".explode("*", $selected_file)[1];
								
								$destination_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/{$destination_file_name}";
								if(copy($selected_file_path, $destination_file_path)) {
									array_push($newly_added_files, $destination_file_name);
								}
							}
							if(count($newly_added_files) > 0) {
								$eth_data_Arr[] = array(
									'company_id' => $company_id,
									'field_name' => "element_{$element_id}",
									'field_code' => "code_{$element_id}",
									'files' => implode("|", $newly_added_files)
								);

								$query_file = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND company_id = ? AND entry_id = ?";
								$sth_file = la_do_query($query_file, array("element_{$element_id}", $company_id, $entry_id), $dbh);
								$row_file = la_do_fetch_result($sth_file);
								if(!empty($row_file["field_name"]) && !empty($row_file["data_value"])) {
									$existing_files = explode("|", $row_file['data_value']);
								}
								$existing_files = array_merge($existing_files, $newly_added_files);
								array_unique($existing_files);

								$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES ";
								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "element_{$element_id}", "code_{$element_id}", implode("|", $existing_files), "0", $element_info[$element_id]['element_machine_code']));

								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
								
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "ip_address", "", $_SERVER['REMOTE_ADDR'], "0", ''));
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "status", "", 1, "0", ''));
								//if date_created doesn't exist, add created date
								$query_date  = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` where `company_id` = ? AND `entry_id` = ? and `field_name` = ?";
								$result_date = la_do_query($query_date, array($company_id, $entry_id, "date_created"), $dbh);
								$row_date    = la_do_fetch_result($result_date);
								
								if(empty($row_date['data_value'])){
									array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
									$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "date_created", "", date("Y-m-d H:i:s"), "0", ''));
								}
								$query .= implode(",", $tmpQryArr);
								$query .= " ON DUPLICATE KEY update `data_value` = values(`data_value`);";
								if(count($tmpDatArr)){
									la_do_query($query, $tmpDatArr, $dbh);
									add_eth_data($dbh, $form_id, $eth_data_Arr);
								}
								//flipping status indicators
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $element_id, $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $element_id, $company_id, $entry_id, 2), $dbh);
							}
						}
					}
				}
				
				$listfile_name = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/listfile_{$input[$element_name.'_token']}.php";
				
				if(!file_exists($listfile_name)){
					
					$check_filetype = false;
					if($element_info[$element_id]['is_required']){
						$rules[$element_name]['required_file'] 	= true;
						$rules[$element_name]['filetype'] 	= true;
						$check_filetype = true;
						
						//if form review enabled, and user pressed back button after going to review page
						//or if this is multipage form
						//disable the required file checking if file already uploaded
						if(!empty($_SESSION['review_id']) || ($form_page_total > 1) || ($is_edit_page === true)){
							if(!empty($element_info[$element_id]['existing_file'])){
								unset($rules[$element_name]['required_file']);
								
								//file type validation should only disabled when no file being uploaded
								if($_FILES[$element_name]['size'] == 0){
									unset($rules[$element_name]['filetype']);
									$check_filetype = false;
								}
							}
						}
					}else{
						if($_FILES[$element_name]['size'] > 0){
							$rules[$element_name]['filetype'] 	= true;
							$check_filetype = true;
						}
					}
					
					if($check_filetype == true && !empty($element_info[$element_id]['file_enable_type_limit'])){
						if($element_info[$element_id]['file_block_or_allow'] == 'b'){ //block file type
							$target_input['file_block_or_allow'] = 'b';
						}elseif($element_info[$element_id]['file_block_or_allow'] == 'a'){
							$target_input['file_block_or_allow'] = 'a';
						}
						
						$target_input['file_type_list'] = $element_info[$element_id]['file_type_list'];
					}
																	
					$target_input[$element_name] = $element_name; //special for file, only need to pass input name
					$validation_result = la_validate_element($target_input,$rules);
					
					if($validation_result !== true){
						$error_elements[$element_id] = $validation_result;
					}else{
						//if validation passed, store uploaded file info into array
						if($_FILES[$element_name]['size'] > 0){
							$uploaded_files[] = $element_name;
						}
					}
				}
				else{ //if files were uploaded using advance uploader
					//file type validation already done in upload.php, so we don't need to do validation again here
					
					//store uploaded file list into array
					$current_element_uploaded_files_advance = array();
					$current_element_uploaded_files_advance = file($listfile_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					array_shift($current_element_uploaded_files_advance); //remove the first index of the array
					array_pop($current_element_uploaded_files_advance); //remove the last index of the array
					
					$uploaded_files_advance[$element_id]['listfile_name'] 	 = $listfile_name;
					$uploaded_files_advance[$element_id]['listfile_content'] = $current_element_uploaded_files_advance;
					
					
					//save old token into array, for form redisplay in case errors occured
					$form_data[$element_name]['file_token']  = $input[$element_name.'_token'];
					
				}
				
				$table_data[$element_name] = $element_name; 		
				
			}

		}

		return array('table_data' => $table_data, 'element_info' => $element_info, 'error_elements' => $error_elements, 'element_option_lookup' => $element_option_lookup);
	}
	
	function chkCascadeInForm($params=array())
	{
		if(!$params['form_id']){
			return false;
		}
		
		$dbh 	= $params['dbh'];
		$query  = "SELECT `element_type` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id and `element_type` = 'casecade_form' limit 1";
		$result = la_do_query($query, array(':form_id' => $params['form_id']), $dbh);
		$row    = la_do_fetch_result($result);
		
		if($row){
			return true;
		}else{
			return false;
		}
	}
	
	function updateFieldScores($dbh, $form_id, $company_id, $entry_id, $field_array=array())
	{
		foreach($field_array as $field){
			$field_name = $field["field_name"];
			$field_score = $field["field_score"];
			$query  = "SELECT `data_value`, `field_score` FROM `".LA_TABLE_PREFIX."form_{$form_id}` where `company_id` = ? AND `field_name` = ? AND `entry_id` = ?";
			$result = la_do_query($query,array($company_id, $field_name, $entry_id), $dbh);
			$row    = la_do_fetch_result($result);
			
			if($row){
				if(!empty($row['field_score'])){
					$field_score = $row['field_score'].",".$field_score;
				}else{
					$field_score  = $field_score;
				}
				
				$query_u = "update `".LA_TABLE_PREFIX."form_{$form_id}` set `field_score` = ? where `company_id` = ? and `field_name` = ? and `entry_id` = ?";
				la_do_query($query_u, array($field_score, $company_id, $field_name, $entry_id), $dbh);
			}
		}
	}

	// function generateCallTrace()
	// {
	//     $e = new Exception();
	//     $trace = explode("\n", $e->getTraceAsString());
	//     // reverse array to make steps line up chronologically
	//     $trace = array_reverse($trace);
	//     array_shift($trace); // remove {main}
	//     array_pop($trace); // remove call to this method
	//     $length = count($trace);
	//     $result = array();
	    
	//     for ($i = 0; $i < $length; $i++)
	//     {
	//         $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	//     }
	    
	//     return "\t" . implode("\n\t", $result);
	// }

	function insertAndUpdateFormData($params=array()) {

		$dbh = $params['dbh'];
		$la_settings = $params['la_settings'];
		$is_committed = isset($params['is_committed']) ? $params['is_committed'] : false;
		$form_id = $params['form_id'];
		$company_id = $params['company_id'];
		$entry_id = $params['entry_id'];
		$table_data = isset($params['table_data']) ? $params['table_data'] : array();
		$element_info = isset($params['element_info']) ? $params['element_info'] : array();
		$element_resume_checkbox = isset($params['element_resume_checkbox']) ? $params['element_resume_checkbox'] : 0;
		$element_option_lookup = isset($params['element_option_lookup']) ? $params['element_option_lookup'] : array();
		$matrix_element_parent = array();
		
		$casecade_page_number = isset($params['casecade_page_number']) ? $params['casecade_page_number'] : 1;
		$casecade_is_committed = isset($params['casecade_is_committed']) ? $params['casecade_is_committed'] : false;
		
		$tmpQryArr = array();
		$tmpDatArr = array();
		$field_score_Arr = array();
		$eth_data_Arr = array();
		
		$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES ";
		
		if(count($element_info)){
			foreach($element_info as $k => $v){
				if($v['type'] == 'matrix'){
					$matrix_element_parent[$k] = !$v['matrix_parent_id'] ? $k : $v['matrix_parent_id'];
				}
			}
		}
		
		if(count($table_data)){
			foreach($table_data as $k => $v){
				if(!in_array($k, array('ip_address', 'date_created', 'casecade_form', 'syndication', 'audit'))){
					$e_info = explode("_", $k);
					$element_machine_code = $element_info[$e_info[1]]['element_machine_code'];

					//flipping status indicators except file type
					if(in_array($element_info[$e_info[1]]['type'], array('text', 'textarea', 'radio', 'checkbox', 'select', 'signature', 'matrix'))) {
						//compare the submitted value to the former saved data
						$query_existing_value = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name = ? AND company_id = ? AND entry_id = ?";
						$sth_existing_value = la_do_query($query_existing_value, array($k, $company_id, $entry_id), $dbh);
						$row_existing_value = la_do_fetch_result($sth_existing_value);
						if($row_existing_value) {
							$string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $v);
							$string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
							if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $e_info[1], $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $e_info[1], $company_id, $entry_id, 2), $dbh);
							}
						} else {
							//if the element doesn't have data yet and the new data is not empty
							if(!is_null($v) && preg_replace( "/\r|\n/", "", $v) !="") {
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $e_info[1], $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $e_info[1], $company_id, $entry_id, 2), $dbh);
							}
						}
					}
					
					if($element_info[$e_info[1]]['type'] == 'matrix'){
						$element_info[$e_info[1]]['type'] = !$element_info[$e_info[1]]['matrix_allow_multiselect'] ? 'radio' : 'checkbox';
					}
					if($element_info[$e_info[1]]['type'] != 'casecade_form' && $element_info[$e_info[1]]['type'] != 'file'){
						$option_value = "0.0";
						array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
						$v = is_null($v) ? "" : $v;
						$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, $k, "code_{$e_info[1]}", $v, "0", $element_machine_code));
					}
					if($element_info[$e_info[1]]['type'] == 'address'){
						$option_value = $e_info[2] == 4 ? $v : "";
					}
					elseif($element_info[$e_info[1]]['type'] == 'radio' || $element_info[$e_info[1]]['type'] == 'select'){
						if(strpos($k, "other") !== false) {
							$option_value = $element_info[$e_info[1]]['choice_other_score'];
						} else {
							$option_value = isset($element_option_lookup[$e_info[1]][trim($v)]['option_value']) ? $element_option_lookup[$e_info[1]][trim($v)]['option_value'] : "0.0";							
						}
						array_push($field_score_Arr, array("field_name" => $k, "field_score" => $option_value));
					}
					elseif($element_info[$e_info[1]]['type'] == 'checkbox'){
						if(strpos($k, "other") !== false) {
							$option_value = $element_info[$e_info[1]]['choice_other_score'];
						} else {
							$option_value = isset($element_option_lookup[$e_info[1]][$e_info[2]]['option_value']) ? $element_option_lookup[$e_info[1]][$e_info[2]]['option_value'] : "0.0";
						}
						array_push($field_score_Arr, array("field_name" => $k, "field_score" => $option_value));
					}
					elseif($element_info[$e_info[1]]['type'] == 'file'){
						if($element_info[$e_info[1]]['file_upload_synced'] != 1 || empty($element_machine_code)) {
							$query_file = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND company_id = ? AND entry_id = ?";
							$sth_file = la_do_query($query_file, array("element_{$e_info[1]}", $company_id, $entry_id), $dbh);
							$row_file = la_do_fetch_result($sth_file);
							
							if(!$row_file['field_name']) {
								$existing_files = "";
							} else {
								$existing_files = $row_file['data_value'];
							}
							
					 		$file = $_SESSION["element_{$form_id}_{$e_info[1]}"];

							//check if last character is |
							if (substr($file, -1) == '|') {
								$file = substr($file,0,-1);
							}

							$new_file = array(); 
							
							if(strpos($file,'|') !== false){							
								$file_explode = explode('|',$file);
								if(count($file_explode) > 0){
									foreach($file_explode as $file_explode_value){
										$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file_explode_value.".tmp";			
										if(file_exists($complete_filename)){
											rename($complete_filename, $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file_explode_value);
											$new_file[] = $file_explode_value;
										}
									}
								}
							} else {							
								$complete_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file.".tmp";
								
								if(file_exists($complete_filename)){
									$new_file[] = $file;
									rename($complete_filename, $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/".$file);
								}
							}

							if (empty(array_filter($new_file))){
								$newfile1 = $existing_files;
							} else {
								$newfile1 = $existing_files.'|'.implode('|',$new_file);
								//flipping status indicators
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $e_info[1], $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $e_info[1], $company_id, $entry_id, 2), $dbh);
							}
							
							if(substr($newfile1,0,1)== '|'){							
								$newfile1 = substr($newfile1,1);
							}

							if (substr($newfile1, -1) == '|') {
								$newfile1 = substr($newfile1,0,-1);
							}
							
							$v = $newfile1;

							$v = explode('|',$v);
							$v = array_unique($v);
							$v = array_filter($v);
							$v = implode('|',$v);
							
							array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
							$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, $k, "code_{$e_info[1]}", $v, "0", $element_machine_code));

							if( !empty($v) ) {
								$eth_data_Arr[] = array(
									'company_id' => $company_id,
									'field_name' => $k,
									'field_code' => "code_{$e_info[1]}",
									'files' => $v
								);
							}
						}
					}
					elseif($element_info[$e_info[1]]['type'] == 'casecade_form'){					
						if(isset($v[$element_info[$e_info[1]]['default_value']]) && count($v[$element_info[$e_info[1]]['default_value']]['table_data'])){
							insertAndUpdateFormData(array(
								'dbh' => $dbh,
								'la_settings' => $la_settings,
								'is_committed' => $is_committed,
								'form_id' => (int)$element_info[$e_info[1]]['default_value'],
								'company_id' => $company_id,
								'entry_id' => $entry_id,
								'table_data' => $v[$element_info[$e_info[1]]['default_value']]['table_data'],
								'element_info' => $v[$element_info[$e_info[1]]['default_value']]['element_info'],
								'element_option_lookup' => $v[$element_info[$e_info[1]]['default_value']]['element_option_lookup'],
								'casecade_page_number' => $casecade_page_number
							));
						}
						
					}
					else{
						$option_value = "0.0";
					}
				}
			}
		}

		if($is_committed){
			array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
			array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
			array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");			
			
			$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "ip_address", "", $_SERVER['REMOTE_ADDR'], "0", ''));
			$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "status", "", 1, "0", ''));
			$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "audit", "", isset($table_data['audit'])?$table_data['audit']:"0", "0", ''));				

			//if date_created doesn't exist, add created date
			$query_date  = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `field_name` = ? AND `entry_id` = ?";
			$result_date = la_do_query($query_date, array($company_id, "date_created", $entry_id), $dbh);
			$row_date    = la_do_fetch_result($result_date);
			
			if(empty($row_date['data_value'])){
				array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
				$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "date_created", "", date("Y-m-d H:i:s"), "0", ''));
			}

			// tracking field scores as well as updated date and ip address
			array_push($field_score_Arr, array("field_name" => "ip_address", "field_score" => $_SERVER['REMOTE_ADDR']));
			array_push($field_score_Arr, array("field_name" => "date_created", "field_score" =>  date("Y-m-d H:i:s")));

			//save meta only once, dont add meta if already present
			$meta_already_query  = "SELECT count(*) as meta_added FROM `".LA_TABLE_PREFIX."form_{$form_id}` where `company_id` = {$company_id} AND field_name='approval_status' AND entry_id={$entry_id}";
			$meta_already_query_result = la_do_query($meta_already_query,array(),$dbh);
			$meta_already_query_data    = la_do_fetch_result($meta_already_query_result);
			
			if( $meta_already_query_data['meta_added'] == 0 )
				send_approval_emails_front($dbh,$company_id,$form_id);
				
		}
		$query .= implode(",", $tmpQryArr);
		$query .= " ON DUPLICATE KEY update `data_value` = values(`data_value`);";
		
		if(count($tmpDatArr)){
			la_do_query($query, $tmpDatArr, $dbh);

			// tracking field scores as well as updated date and ip address
			updateFieldScores($dbh, $form_id, $company_id, $entry_id, $field_score_Arr);

			add_eth_data($dbh, $form_id, $eth_data_Arr);
		}
	}

	function add_eth_data($dbh, $form_id, $eth_data_Arr) {
 		if(!empty($eth_data_Arr)){
	 		
			$query = "INSERT IGNORE INTO `".LA_TABLE_PREFIX."eth_file_data`(user_id, form_id, company_id, field_name, field_code, data, action_datetime, is_portal) VALUES ";
			$queryArr = [];
			$dataArr = [];
			$files = [];
			$action_datetime = time();
 			foreach ($eth_data_Arr as $eth_data) {
 				if( strpos($eth_data['files'], '|') !== false ) {
			  		//multiple files
			  		$file_names = explode('|', $eth_data['files']);
			  		foreach ($file_names as $key => $file) {
			  			array_push($queryArr, "(?,?,?,?,?,?,?,?)" );
			  			$dataArr = array_merge($dataArr, array($_SESSION['la_client_user_id'], $form_id, $eth_data['company_id'], $eth_data['field_name'], $eth_data['field_code'], $file, $action_datetime,1));
			  		}
			  	} else {
			  		array_push($queryArr, "(?,?,?,?,?,?,?,?)" );
			  		$dataArr = array_merge($dataArr, array($_SESSION['la_client_user_id'], $form_id, $eth_data['company_id'], $eth_data['field_name'], $eth_data['field_code'], $eth_data['files'], $action_datetime,1));
			  	}
 			}
 			
 			$query .= implode(',', $queryArr);
			la_do_query($query,$dataArr,$dbh);
			//get newly uploaded files
			$query_added_files = "SELECT `data` FROM `".LA_TABLE_PREFIX."eth_file_data` WHERE `user_id`=? AND `form_id`=? AND `action_datetime`=?";
			$res_added_files = la_do_query($query_added_files, array($_SESSION['la_client_user_id'], $form_id, $action_datetime), $dbh);
			while($row_added_file = la_do_fetch_result($res_added_files)){
				$files[] = $row_added_file['data'];
			}
 			addUserActivityFileUpload($dbh, $_SESSION['la_client_user_id'], $form_id, $files); 			
		}
 	}
	
	//add meta for approve/deny buttons
	function send_approval_emails_front($dbh,$company_id,$form_id) {
 		$first_order_user_id = '';

 		//for every entry save approval data to ap_form_approval_logic_entry_data , form_approval_logic_data
 		$query  = "SELECT `data` FROM `".LA_TABLE_PREFIX."form_approval_logic_data` where `form_id` = {$form_id}";
		$result = la_do_query($query,array(),$dbh);
		$form_logic_data    = la_do_fetch_result($result);
			
		if($form_logic_data){
			if(!empty($form_logic_data['data'])){
				$query = "insert into `".LA_TABLE_PREFIX."form_approval_logic_entry_data`(form_id,company_id,data) values(?,?,?)";
				$params = array($form_id,$company_id,$form_logic_data['data']);
				la_do_query($query,$params,$dbh);
				

				$form_logic_data_arr = json_decode($form_logic_data['data']);
				$logic_approver_enable = $form_logic_data_arr->logic_approver_enable;


				if( $logic_approver_enable > 0 ){
					// $tmpDatArr = array_merge($tmpDatArr, array($company_id, "approval_status", "", 0, "0"));

					// $query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (company_id, field_name,data_value,unique_row_data) values (?,?,?,CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`))";
					$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (company_id, field_name,data_value,unique_row_data) values (?,?,?,MD5(UNIX_TIMESTAMP()))";
					// $query .= " ON DUPLICATE KEY UPDATE `data_value` = values(`data_value`);";
					$params = array($company_id,'approval_status',0);
					la_do_query($query,$params,$dbh);
				}


				
				$logic_approver_enable_1_a = 0;
 				if( $logic_approver_enable == 1 ) {
					$logic_approver_enable_1_a = $form_logic_data_arr->selected_admin_check_1_a;
 				}

 				if( $logic_approver_enable_1_a > 1 ) {

 					$all_selected_users = $form_logic_data_arr->all_selected_users;
					$all_user_ids = explode(',', $all_selected_users);

 					$form_approvals_query = "insert into `".LA_TABLE_PREFIX."form_approvals`(user_id,company_id,form_id,user_order) values(?,?,?,?)";

					foreach ($all_user_ids as $user_id) {
						$params = array($user_id,$company_id,$form_id,0);
						la_do_query($form_approvals_query,$params,$dbh);
					}


					
					if( count( $all_user_ids ) > 0 ) {
						$approval_query = "SELECT 
								user_email
							FROM 
								".LA_TABLE_PREFIX."users 
						   	WHERE 
						   		user_id IN ($all_selected_users)";
						$params = [];
						$sth = la_do_query($approval_query,$params,$dbh);
						$user_emails = [];
						while($row = la_do_fetch_result($sth)){
							$user_emails[] = $row['user_email'];
						}

						la_send_approval_email($dbh, $form_id, $user_emails);
					}
						
 				}

		 		if( $logic_approver_enable == 2 ) {
		 			
					$user_order_process_arr = $form_logic_data_arr->user_order_process;
					
					
					if( !empty($user_order_process_arr) ) {
						$form_approvals_query = "insert into `".LA_TABLE_PREFIX."form_approvals`(user_id,company_id,form_id,user_order) values(?,?,?,?)";

						$all_user_ids = [];

						foreach ($user_order_process_arr as $user_order_obj) {
							
							$user_id = $user_order_obj->user_id;
							$all_user_ids[] = $user_id;
							$user_order = $user_order_obj->order;
							
							if( $logic_approver_enable == 2 && $user_order == 1 ) {
								$first_order_user_id = $user_id;
							}

							$params = array($user_id,$company_id,$form_id,$user_order);
							la_do_query($form_approvals_query,$params,$dbh);
						}

						//send email notifiation to users
						//send email to user with first order in case of multi step approval otherwise to all

						
							
						$approval_query = "SELECT 
								user_email
							FROM 
								".LA_TABLE_PREFIX."users 
						   	WHERE 
						   		user_id = $first_order_user_id";
						$params = [];
						$sth = la_do_query($approval_query,$params,$dbh);
						$user_emails = [];
						while($row = la_do_fetch_result($sth)){
							$user_email = $row['user_email'];
						}

						la_send_approval_email($dbh, $form_id, $user_email);
						
					}
				}
			}
		}
 	}


	function checkIsFormEditable($parameter=array())
	{
		if(!$parameter['form_id']){
			return false;
		}
		
		$dbh 	= $parameter['dbh'];
		$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$parameter['form_id']}` WHERE `company_id` = :company_id";
		$result = la_do_query($query, array(':company_id' => $parameter['company_id']),$dbh);
		$row    = la_do_fetch_result($result);
		
		if($row){
			return true;
		}else{
			return false;
		}
	}

	function la_process_form($dbh,$input)
	{		
		global $la_lang;

		$form_id 	 = (int) trim($input['form_id']);
		$entry_id 	 = (int) trim($input['entry_id']);
		$company_id  = (int) trim($input['company_id']);
		$edit_id	 = (int) trim($input['edit_id']);
		
		$zipPath     = ""; 
		
		if(empty($input['page_number'])){
			$page_number = 1;
		}else{
			$page_number = (int) $input['page_number'];
		}
		
		$is_committed = false;
		
		$la_settings = la_get_settings($dbh);
		
		$isCasecadeForm = chkCascadeInForm(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
		));
		
		//this function handle password submission and general form submission
		//check for password requirement
		$query = "select 
						form_password,
						form_language,
						form_review,
						form_page_total,
						logic_field_enable,
						logic_page_enable 
					from 
						`".LA_TABLE_PREFIX."forms` where form_id=?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$form_review 	    = $row['form_review'];
		$form_page_total    = (int) $row['form_page_total'];
		$logic_field_enable = (int) $row['logic_field_enable'];
		$logic_page_enable  = (int) $row['logic_page_enable'];
		
		if(!empty($row['form_password'])){
			$require_password = true;
		}else{
			$require_password = false;
		}
		
		if(!empty($row['form_language'])){
			la_set_language($row['form_language']);
		}
		
		//if this form require password and no session has been set
		if($require_password && (empty($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] != $form_id)){ 
			
			$query = "select count(form_id) valid_password from `".LA_TABLE_PREFIX."forms` where form_id=? and form_password=?";
			$params = array($form_id,$input['password']);
		
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			if(!empty($row['valid_password'])){
				$process_result['status'] = true;
				$_SESSION['user_authenticated'] = $form_id;
			}else{
				$process_result['status'] = false;
				$process_result['custom_error'] = $la_lang['form_pass_invalid'];
			}
			
			return $process_result;
		}
		
		$delay_notifications = false;
		$form_properties = array();
		
		$form_properties = la_get_form_properties(
													$dbh,
													$form_id,
													array('payment_enable_merchant',
														'payment_delay_notifications',
														'payment_merchant_type',
														'payment_enable_discount',
														'payment_discount_element_id',
														'payment_discount_code')
												);
		//delay notifications if this option turned on within payment setting page
		//this option is not available for check/cash
		if(($form_properties['payment_enable_merchant'] == 1) && !empty($form_properties['payment_delay_notifications']) && 
			in_array($form_properties['payment_merchant_type'], array('stripe','paypal_standard','authorizenet','paypal_rest','braintree'))){
			$delay_notifications = true;
		}
		
		$element_child_lookup['address'] 	 = 5;
		$element_child_lookup['simple_name'] = 1;
		$element_child_lookup['simple_name_wmiddle'] = 2;
		$element_child_lookup['name'] 		 = 3;
		$element_child_lookup['name_wmiddle'] = 4;
		$element_child_lookup['phone'] 		 = 2;
		$element_child_lookup['date'] 		 = 2;
		$element_child_lookup['europe_date'] = 2;
		$element_child_lookup['time'] 		 = 3;
		$element_child_lookup['money'] 		 = 1; //this applies to dollar,euro and pound. yen don't have child
		$element_child_lookup['checkbox'] 	 = 1; //this is just a dumb value
		$element_child_lookup['matrix'] 	 = 1; //this is just a dumb value
		
		//never trust user input, get a list of input fields based on info stored on table
		//element has real child -> address, simple_name, name, simple_name_wmiddle, name_wmiddle
		//element has virtual child -> phone, date, europe_date, time, money
		
		$is_edit_page = false;
		$casecade_is_committed = false;
		
		if(!empty($edit_id)){ // edited on 29 oct 2014
			//if this is edit_entry page, process all elements on all pages at once
			$params = array($form_id);
			if($_SESSION['la_client_logged_in'] === true){
				$page_number_clause = 'and element_page_number =?';
				$params = array($form_id,$page_number);
			}
			$is_edit_page = true;
		}
		else{
			$page_number_clause = 'and element_page_number = ?';
			$params = array($form_id, $page_number);
			
			if($isCasecadeForm){
				if(isset($_POST['parent_nxt_element'])){
					$tmpElements = json_decode(base64_decode(noHTML($_POST['parent_nxt_element'])), true);
					$page_number_clause = $tmpElements['page_number_clause'];
					$params = $tmpElements['params'];
					
					$casecade_is_committed = $_POST['casecade_form_page_number'] == "NO_ELEMENTS" ? true : false;
				}
			}			
		}
		
		$query = "SELECT 
						element_id,
       					element_title,
       					element_is_required,
       					element_is_unique,
       					element_is_private,
       					element_type, 
       					element_constraint,
       					element_total_child,
       					element_file_enable_multi_upload,
       					element_file_max_selection,
       					element_file_enable_type_limit,
       					element_file_block_or_allow,
       					element_file_type_list,
       					element_range_max,
       					element_range_min,
       					element_range_limit_by,
       					element_choice_has_other,
       					element_choice_other_score,
       					element_time_showsecond,
       					element_time_24hour,
       					element_matrix_parent_id,
       					element_matrix_allow_multiselect,
       					element_date_enable_range,
       					element_date_range_min,
       					element_date_range_max,
       					element_date_past_future,
       					element_date_disable_past_future,
       					element_date_enable_selection_limit,
						element_date_selection_max,
						element_date_disable_weekend,
						element_date_disable_specific,
						element_date_disabled_list,
						element_default_value,
						element_machine_code,
						element_file_upload_synced,
						element_file_enable_advance,
						element_file_select_existing_files
					FROM 
						".LA_TABLE_PREFIX."form_elements 
				   WHERE 
				   		form_id=? and element_status = '1' {$page_number_clause} and element_type <> 'page_break' and element_type <> 'section'
				ORDER BY 
						element_id asc";
		
		$sth = la_do_query($query,$params,$dbh);
		
		
		$element_to_get = array();
		$private_elements = array(); //admin-only fields
		$matrix_childs_array = array();
		
		while($row = la_do_fetch_result($sth)){
			if($row['element_type'] == 'section'){
				continue;
			}
			
			//store element info
			$element_info[$row['element_id']]['title'] 			= $row['element_title'];
			$element_info[$row['element_id']]['type'] 			= $row['element_type'];
			$element_info[$row['element_id']]['is_required'] 	= $row['element_is_required'];
			$element_info[$row['element_id']]['is_unique'] 		= $row['element_is_unique'];
			$element_info[$row['element_id']]['is_private'] 	= $row['element_is_private'];
			$element_info[$row['element_id']]['constraint'] 	= $row['element_constraint'];
			$element_info[$row['element_id']]['file_enable_multi_upload'] 	= $row['element_file_enable_multi_upload'];
			$element_info[$row['element_id']]['file_max_selection'] 	= $row['element_file_max_selection'];
			$element_info[$row['element_id']]['file_enable_type_limit'] = $row['element_file_enable_type_limit'];
			$element_info[$row['element_id']]['file_block_or_allow'] 	= $row['element_file_block_or_allow'];
			$element_info[$row['element_id']]['file_type_list'] 		= $row['element_file_type_list'];
			$element_info[$row['element_id']]['range_min'] 		= $row['element_range_min'];
			$element_info[$row['element_id']]['range_max'] 		= $row['element_range_max'];
			$element_info[$row['element_id']]['range_limit_by'] = $row['element_range_limit_by'];
			$element_info[$row['element_id']]['choice_has_other'] = $row['element_choice_has_other'];
			$element_info[$row['element_id']]['choice_other_score'] = $row['element_choice_other_score'];
			$element_info[$row['element_id']]['time_showsecond']  = (int) $row['element_time_showsecond'];
			$element_info[$row['element_id']]['time_24hour']  	  = (int) $row['element_time_24hour'];
			$element_info[$row['element_id']]['matrix_parent_id'] = (int) $row['element_matrix_parent_id'];
			$element_info[$row['element_id']]['matrix_allow_multiselect'] = (int) $row['element_matrix_allow_multiselect'];
			$element_info[$row['element_id']]['date_enable_range'] = (int) $row['element_date_enable_range'];
			$element_info[$row['element_id']]['date_range_max']    = $row['element_date_range_max'];
			$element_info[$row['element_id']]['date_range_min']    = $row['element_date_range_min'];
			$element_info[$row['element_id']]['date_past_future']    = $row['element_date_past_future'];
			$element_info[$row['element_id']]['date_disable_past_future'] = (int) $row['element_date_disable_past_future'];
			$element_info[$row['element_id']]['date_enable_selection_limit'] = (int) $row['element_date_enable_selection_limit'];
			$element_info[$row['element_id']]['date_selection_max'] = (int) $row['element_date_selection_max'];
			$element_info[$row['element_id']]['date_disable_weekend'] = (int) $row['element_date_disable_weekend'];
			$element_info[$row['element_id']]['date_disable_specific'] = (int) $row['element_date_disable_specific'];
			$element_info[$row['element_id']]['date_disabled_list'] = $row['element_date_disabled_list'];
			$element_info[$row['element_id']]['default_value'] = $row['element_default_value'];			
			$element_info[$row['element_id']]['element_machine_code'] = $row['element_machine_code'];		
			$element_info[$row['element_id']]['file_upload_synced'] = (int) $row['element_file_upload_synced'];
			$element_info[$row['element_id']]['file_enable_advance'] = (int) $row['element_file_enable_advance'];
			$element_info[$row['element_id']]['file_select_existing_files'] = (int) $row['element_file_select_existing_files'];		
			
			//get element form name, complete with the childs
			if(empty($element_child_lookup[$row['element_type']]) || ($row['element_constraint'] == 'yen')){ //elements with no child
				$element_to_get[] = 'element_'.$row['element_id'];			
			}
			else{ //elements with child
				if($row['element_type'] == 'checkbox' || ($row['element_type'] == 'matrix' && !empty($row['element_matrix_allow_multiselect']))){
					
					//for checkbox, get childs elements from ap_element_options table 
					$sub_query = "select 
										option_id 
									from 
										".LA_TABLE_PREFIX."element_options 
								   where 
								   		form_id=? and element_id=? and live=1 
								order by 
										`position` asc";
					$params = array($form_id,$row['element_id']);
					
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					while($sub_row = la_do_fetch_result($sub_sth)){
						$element_to_get[] = "element_{$row['element_id']}_{$sub_row['option_id']}";
						$checkbox_childs[$row['element_id']][] =  $sub_row['option_id']; //store the child into array for further reference
					}
					
					//if this is the parent of the matrix (checkbox matrix only), get the child as well
					if($row['element_type'] == 'matrix' && !empty($row['element_matrix_allow_multiselect'])){
						
						$temp_matrix_child_element_id_array = explode(',',trim($row['element_constraint']));
						
						foreach ($temp_matrix_child_element_id_array as $mc_element_id){
							$sub_query = "select 
											option_id 
										from 
											".LA_TABLE_PREFIX."element_options 
									   where 
									   		form_id=? and element_id=? and live=1 
									order by 
											`position` asc";
							$params = array($form_id,$mc_element_id);
							
							$sub_sth = la_do_query($sub_query,$params,$dbh);
							while($sub_row = la_do_fetch_result($sub_sth)){
								$element_to_get[] = "element_{$mc_element_id}_{$sub_row['option_id']}";
								$checkbox_childs[$mc_element_id][] =  $sub_row['option_id']; //store the child into array for further reference
							}
							
						}
					}
				}else if($row['element_type'] == 'matrix' && empty($row['element_matrix_allow_multiselect'])){ //radio button matrix, each row doesn't have childs
					$element_to_get[] = 'element_'.$row['element_id'];
				}else{
					$max = $element_child_lookup[$row['element_type']] + 1;
					
					for ($j=1;$j<=$max;$j++){
						$element_to_get[] = "element_{$row['element_id']}_{$j}";
					}
				}
			}
			
			
			//if the back button pressed after review page, or this is multipage form, we need to store the file info
			if((!empty($_SESSION['review_id']) && !empty($form_review)) || ($form_page_total > 1) || ($is_edit_page === true)){
				if($row['element_type'] == 'file'){
					$existing_file_id[] = $row['element_id'];
				}
			}

			//if this is matrix field, particularly the child rows, we need to store the id into temporary array
			//we need to loop through it later, to set the "required" property based on the matrix parent value
			if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){
				$matrix_childs_array[$row['element_id']] = $row['element_matrix_parent_id'];
			}
			//if this is text field, check for the coupon code field status
			if(($form_properties['payment_enable_merchant'] == 1) && ($is_edit_page === false) && !empty($form_properties['payment_enable_discount']) && 
				!empty($form_properties['payment_discount_element_id']) && !empty($form_properties['payment_discount_code']) &&
				($form_properties['payment_discount_element_id'] == $row['element_id'])){
				
				$element_info[$row['element_id']]['is_coupon_field'] = true;
			}
			//extra security measure for file upload
			//even though the user disabled 'file type limit', we need to enforce it here and block dangerous files
			if($row['element_type'] == 'file'){
				
				//if the 'Limit File Upload Type' disabled by user, enable it here and check for dangerous files
				if(empty($row['element_file_enable_type_limit'])){
					$element_info[$row['element_id']]['file_enable_type_limit'] = 1;
					$element_info[$row['element_id']]['file_block_or_allow'] = 'b'; //block
					$element_info[$row['element_id']]['file_type_list'] = 'php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
				}else{
					//if the limit being enabled but the list type is empty
					if(empty($element_info[$row['element_id']]['file_type_list'])){
						$element_info[$row['element_id']]['file_block_or_allow'] = 'b'; //block
						$element_info[$row['element_id']]['file_type_list'] = 'php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
					}else{
						//if the list is not empty, and it set to block files, make sure to add dangerous file types into the list
						if($element_info[$row['element_id']]['file_block_or_allow'] == 'b'){
							$element_info[$row['element_id']]['file_type_list'] .= ',php,php3,php4,php5,phtml,exe,pl,cgi,html,htm,js';
						}
					}
				}
			}
		}
		
		//loop through each matrix childs array
		//if the parent matrix has required=1, the child need to be set the same
		//if the parent matrix allow multi select, the child need to be set the same
		if(!empty($matrix_childs_array)){
			foreach ($matrix_childs_array as $matrix_child_element_id=>$matrix_parent_element_id){
				if(!empty($element_info[$matrix_parent_element_id]['is_required'] )){
					$element_info[$matrix_child_element_id]['is_required'] = 1; 
				}
				if(!empty($element_info[$matrix_parent_element_id]['matrix_allow_multiselect'] )){
					$element_info[$matrix_child_element_id]['matrix_allow_multiselect'] = 1; 
				}
			}
		}
		
		if(!empty($existing_file_id)){
			$existing_file_id_list = '';
			foreach ($existing_file_id as $value){
				$existing_file_id_list .= 'element_'.$value.',';
			}
			$existing_file_id_list = rtrim($existing_file_id_list,',');
			
			foreach ($existing_file_id as $value){
				if(!empty($row['element_'.$value])){
					$element_info[$value]['existing_file'] 	= $row['element_'.$value];
				}
			}
		}
		
		$element_option_lookup = array();
		//get form element options first (checkboxes, choices, dropdown)
		$query = "select 
						`element_id`,
						`option_id`,
						`option`,
						`option_value`
					from 
						`".LA_TABLE_PREFIX."element_options` 
				   where 
						`form_id`=? and `live`=1 
				order by 
						`element_id`, `position` asc";
		$sth = la_do_query($query,array($form_id),$dbh);
			
		while($row = la_do_fetch_result($sth)){
			$element_option_lookup[$row['element_id']][$row['option_id']] = array('option' => $row['option'], 'option_value' => $row['option_value']);
		}

		//pick user input
		$user_input = array();
		foreach($element_to_get as $element_name){
			$user_input[$element_name] = @$input[$element_name];
		}
		
		//if conditional logic for field is being enabled, and this is not edit entry page
		//we need to check the status of all elements which become "hidden" due to conditions
		//any hidden fields should be discarded, so that it won't be required and won't be displayed within review page/email
		if(!empty($logic_field_enable) && $is_edit_page === false){
			$hidden_elements = array();
			$hidden_elements = la_get_hidden_elements($dbh,$form_id,$page_number,$input);
			if(!empty($hidden_elements)){
				foreach ($hidden_elements as $element_id => $hidden_status) {
					$element_info[$element_id]['is_hidden'] = $hidden_status;
					if($element_info[$element_id]['is_hidden'] == 1){
						$element_info[$element_id]['is_required'] = 0;
					}
					
					//if this is matrix field, particularly the parent, we need to set the required property of the childs as well
					if($element_info[$element_id]['type'] == 'matrix' && empty($element_info[$element_id]['matrix_parent_id']) ){						
						foreach ($matrix_childs_array as $matrix_child_element_id=>$matrix_parent_element_id){
							if($matrix_parent_element_id == $element_id){
								if($hidden_status == 1){
									$element_info[$matrix_child_element_id]['is_required'] = 0;
									$element_info[$matrix_child_element_id]['is_hidden']   = 1;
								}else{
									//only set to 'required' if the field is actually having 'required' attribute from the beginning
									if(!empty($element_info[$matrix_parent_element_id]['is_required'] )){
										$element_info[$matrix_child_element_id]['is_required'] = 1;
										$element_info[$matrix_child_element_id]['is_hidden']   = 0;
									}
								}
							}
						}
					}
				}
			}
		}
		else if( (!empty($logic_field_enable) && $is_edit_page === true) || (!empty($logic_page_enable) && $is_edit_page === true) ){
			//if this edit entry page and has logic enabled (either skip page or field logic), disable all "required" fields
			foreach ($element_info as $element_id => $value) {
				$element_info[$element_id]['is_required'] = 0;
			}
		}			
					
		$error_elements = array();
		$table_data = array();

		foreach ($user_input as $element_name => $element_data)
		{ // foreach start here : 1
			
			//get element_id from element_name
			$exploded = array();
			$exploded = explode('_',$element_name);
			$element_id = $exploded[1];
			
			$rules = array();
			$target_input = array();
			
			$element_type = $element_info[$element_id]['type'];
			
			//if this is private fields and not logged-in as admin, bypass operation below, just supply the default value if any
			//if this is private fields and logged-in as admin and this is not edit-entry page, bypass operation below as well
			if($element_info[$element_id]['is_private'] == 1){
				if(!empty($element_info[$element_id]['default_value'])){
					$table_data['element_'.$element_id] = $element_info[$element_id]['default_value'];
					
					if('date' == $element_type || 'europe_date' == $element_type){
						if(strpos($element_info[$element_id]['default_value'], "/") !== false){
							$tmpValueArr = explode("/", $element_info[$element_id]['default_value']);
							$table_data['element_'.$element_id] = $tmpValueArr[2]."-".$tmpValueArr[0]."-".$tmpValueArr[1];
						}else if($element_info[$element_id]['default_value'] == "today"){
							$table_data['element_'.$element_id] = date("Y-m-d");
						}else if($element_info[$element_id]['default_value'] == "tomorrow"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("+1 day"));
						}else if($element_info[$element_id]['default_value'] == "last friday"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("last Friday"));
						}else if($element_info[$element_id]['default_value'] == "+1 week"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("+1 week"));
						}else if($element_info[$element_id]['default_value'] == "last day of next month"){
							$table_data['element_'.$element_id] = date("Y-m-t", strtotime("+1 month"));
						}else if($element_info[$element_id]['default_value'] == "3 days ago"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("-3 day"));
						}else if($element_info[$element_id]['default_value'] == "monday next week"){
							$table_data['element_'.$element_id] = date("Y-m-d", strtotime("next Monday"));
						}
					}
				}
				continue;
			}
			
			//if this is matrix field, we need to convert the field type into radio button or checkbox
			if('matrix' == $element_type){
				$is_matrix_field = true;
				if(!empty($element_info[$element_id]['matrix_allow_multiselect'])){
					$element_type = 'checkbox';
				}else{
					$element_type = 'radio';
				}
			}
			else{
				$is_matrix_field = false;
			}			
			
			if ('text' == $element_type){ //Single Line Text
											
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] = $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
						
				if(!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}
				
				if($element_info[$element_id]['is_coupon_field'] === true){
					$rules[$element_name]['coupon'] = $form_id;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'coupon' rule
				}
				
				$target_input[$element_name] = $element_data;
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('textarea' == $element_type){ //Paragraph
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				if(!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}
												
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('signature' == $element_type){ //Signature
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				$target_input[$element_name] = $element_data;
				if($target_input[$element_name] == '[]'){ //this is considered as empty signature
					$target_input[$element_name] = '';
				}
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data,ENT_NOQUOTES); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('radio' == $element_type){ //Multiple Choice
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name.'_other'] = '';
				}
				
				//if this field has 'other' label
				if(!empty($element_info[$element_id]['choice_has_other'])){
					if(empty($element_data) && !empty($input[$element_name.'_other'])){
						$element_data = $input[$element_name.'_other'];
						
						//save old data into array, for form redisplay in case errors occured
						$form_data[$element_name.'_other']['default_value'] = $element_data; 
						$table_data[$element_name.'_other'] = $element_data;
						//make sure to set the main element value to 0
						$form_data[$element_name]['default_value'] = 0; 
						$table_data[$element_name] = 0;
					}
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					if($is_matrix_field && !empty($matrix_childs_array[$element_id])){
						$error_elements[$matrix_childs_array[$element_id]] = $validation_result;
					}else{
						$error_elements[$element_id] = $validation_result;
					}
				}
				
				//save old data into array, for form redisplay in case errors occured
				if(empty($form_data[$element_name.'_other']['default_value'])){
					$form_data[$element_name]['default_value'] = $element_data; 
				}
				
				//prepare data for table column
				if(empty($table_data[$element_name.'_other'])){
					$table_data[$element_name] = $element_data; 
				}
				
			}
			elseif ('number' == $element_type){ //Number
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
					$user_input[$element_name] = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				//check for numeric if not empty
				if(!empty($user_input[$element_name])){ 
					$rules[$element_name]['numeric'] = true;
				}
				
				if((!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])) && ($element_info[$element_id]['range_limit_by'] == 'd')){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_length'] = $element_info[$element_id]['range_limit_by'].'#'.$element_info[$element_id]['range_min'];
					}	
				}else if((!empty($user_input[$element_name]) || is_numeric($user_input[$element_name])) && ($element_info[$element_id]['range_limit_by'] == 'v')){
					if(!empty($element_info[$element_id]['range_max']) && !empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['range_value'] = $element_info[$element_id]['range_min'].'#'.$element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_max'])){
						$rules[$element_name]['max_value'] = $element_info[$element_id]['range_max'];
					}else if(!empty($element_info[$element_id]['range_min'])){
						$rules[$element_name]['min_value'] = $element_info[$element_id]['range_min'];
					}	
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
				//if the user removed the number, set the value to null
				if($table_data[$element_name] == ""){
					$table_data[$element_name] = null;
				}
			}
			elseif ('url' == $element_type){ //Website
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$rules[$element_name]['website'] = true;
														
				if($element_data == 'http://'){
					$element_data = '';
				}
						
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('email' == $element_type){ //Email
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$element_data = '';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$rules[$element_name]['email'] = true;
														
										
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = htmlspecialchars($element_data); 
				
				//prepare data for table column
				$table_data[$element_name] = $element_data; 
				
			}
			elseif ('simple_name' == $element_type){ //Simple Name
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 2 elements total	
				$element_name_2 = substr($element_name,0,-1).'2';
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed on next loop
				
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
				}
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
					$rules[$element_name_2]['required'] = true;
				}
																		
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_2] = $user_input[$element_name_2];
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				
			}
			elseif ('simple_name_wmiddle' == $element_type){ //Simple Name with Middle
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other elements, 3 elements total	
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed on next loop
				$processed_elements[] = $element_name_3;
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
																		
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				
			}
			elseif ('name' == $element_type){ //Name -  Extended
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 4 elements total	
				//only element no 2&3 matters (first and last name)
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
				}
																		
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				
			}
			elseif ('name_wmiddle' == $element_type){ //Name -  Extended, with Middle
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 5 elements total	
				//only element no 2,3,4 matters (first, middle, last name)
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				$element_name_5 = substr($element_name,0,-1).'5';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
				$processed_elements[] = $element_name_5;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_4]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
					$user_input[$element_name_5] = '';
				}
																		
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				$form_data[$element_name_5]['default_value'] = htmlspecialchars($user_input[$element_name_5]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				$table_data[$element_name_5] = $user_input[$element_name_5];
				
			}
			elseif ('time' == $element_type){ //Time
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 4 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
				//echo '<pre>';print_r($processed_elements);echo '</pre>';				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
					if(empty($element_info[$element_id]['time_24hour'])){
						$rules[$element_name_4]['required'] = true;
					}
				}
				//check time validity if any of the compound field entered
				$time_entry_exist = false;
				if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2]) || !empty($user_input[$element_name_3])){
					$rules['element_time']['time'] = true;
					$time_entry_exist = true;
				}
				
				//for backward compatibility with itauditmachine v2 and beyond
				if($element_info[$element_id]['constraint'] == 'show_seconds'){
					$element_info[$element_id]['time_showsecond'] = 1;
				}
				
				if($time_entry_exist && empty($element_info[$element_id]['time_showsecond'])){
					$user_input[$element_name_3] = '00';
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules['element_time_no_meridiem']['unique'] = $form_id.'#'.substr($element_name,0,-2); //to check uniquenes we need to use 24 hours HH:MM:SS format
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
							
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				if($time_entry_exist){
					$target_input['element_time']  = trim($user_input[$element_name].':'.$user_input[$element_name_2].':'.$user_input[$element_name_3].' '.$user_input[$element_name_4]);
					$target_input['element_time_no_meridiem'] = @date("G:i:s",strtotime($target_input['element_time']));
				}
				
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				if($element_info[$element_id]['is_hidden']){
					$target_input['element_time_no_meridiem'] = '';
				}
				
				//prepare data for table column
				$table_data[substr($element_name,0,-2)] 	 = @$target_input['element_time_no_meridiem'];
								
			}
			elseif ('address' == $element_type){ //Address
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 6 elements total, element #2 (address line 2) is optional	
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
				$element_name_4 = substr($element_name,0,-1).'4';
				$element_name_5 = substr($element_name,0,-1).'5';
				$element_name_6 = substr($element_name,0,-1).'6';
				
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
				$processed_elements[] = $element_name_4;
				$processed_elements[] = $element_name_5;
				$processed_elements[] = $element_name_6;
								
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] = true;
					$rules[$element_name_3]['required'] = true;
					$rules[$element_name_4]['required'] = true;
					$rules[$element_name_5]['required'] = true;
					$rules[$element_name_6]['required'] = true;
					
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = ''; 
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
					$user_input[$element_name_4] = '';
					$user_input[$element_name_5] = '';
					$user_input[$element_name_6] = '';
				}
				
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input[$element_name_4] = $user_input[$element_name_4];
				$target_input[$element_name_5] = $user_input[$element_name_5];
				$target_input[$element_name_6] = $user_input[$element_name_6];
			
				
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				$form_data[$element_name_4]['default_value'] = htmlspecialchars($user_input[$element_name_4]);
				$form_data[$element_name_5]['default_value'] = htmlspecialchars($user_input[$element_name_5]);
				$form_data[$element_name_6]['default_value'] = htmlspecialchars($user_input[$element_name_6]);
				
				//prepare data for table column
				$table_data[$element_name] 	 = $user_input[$element_name]; 
				$table_data[$element_name_2] = $user_input[$element_name_2];
				$table_data[$element_name_3] = $user_input[$element_name_3];
				$table_data[$element_name_4] = $user_input[$element_name_4];
				$table_data[$element_name_5] = $user_input[$element_name_5];
				$table_data[$element_name_6] = $user_input[$element_name_6];
				
			}
			elseif ('money' == $element_type){ //Price
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 2 elements total (for currency other than yen)	
				if($element_info[$element_id]['constraint'] != 'yen'){ //if other than yen
					$base_element_name = substr($element_name,0,-1);
					$element_name_2 = $base_element_name.'2';
					$processed_elements[] = $element_name_2;
										
					if($element_info[$element_id]['is_required']){
						$rules[$base_element_name]['required'] 	= true;
					}
					if($element_info[$element_id]['is_hidden']){
						$user_input[$element_name] = '';
						$user_input[$element_name_2] = '';
					}
					
					//check for numeric if not empty
					if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2])){
						$rules[$base_element_name]['numeric'] = true;
					}
					
					if($element_info[$element_id]['is_unique']){
						$rules[$base_element_name]['unique'] 	= $form_id.'#'.substr($element_name,0,-2);
						$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
					}
				
					$target_input[$base_element_name]   = $user_input[$element_name].'.'.$user_input[$element_name_2]; //join dollar+cent
					if($target_input[$base_element_name] == '.'){
						$target_input[$base_element_name] = '';
					}
					
					//save old data into array, for form redisplay in case errors occured
					$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
					$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
					
					//prepare data for table column
					if(!empty($user_input[$element_name]) || !empty($user_input[$element_name_2]) 
					   || $user_input[$element_name] === '0' || $user_input[$element_name_2] === '0'){
					  	$table_data[substr($element_name,0,-2)] = $user_input[$element_name].'.'.$user_input[$element_name_2];
					}
					
					//if the user removed the number, set the value to null
					if($user_input[$element_name] == "" && $user_input[$element_name_2] == ""){
						$table_data[substr($element_name,0,-2)] = null;
					} 		
				}else{
					if($element_info[$element_id]['is_required']){
						$rules[$element_name]['required'] 	= true;
					}
					
					if($element_info[$element_id]['is_hidden']){
						$user_input[$element_name] = '';
					}
					//check for numeric if not empty
					if(!empty($user_input[$element_name])){ 
						$rules[$element_name]['numeric'] = true;
					}
					
					if($element_info[$element_id]['is_unique']){
						$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
						$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
					}
									
					$target_input[$element_name]   = $user_input[$element_name];
					
					//save old data into array, for form redisplay in case errors occured
					$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
					
					//prepare data for table column
					$table_data[$element_name] 	 = $user_input[$element_name];
					
					//if the user removed the number, set the value to null
					if($table_data[$element_name] == ""){
						$table_data[$element_name] = null;
					} 
								
				}
								
				
												
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
			}
			elseif ('checkbox' == $element_type){ //Checkboxes
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				
				$all_child_array = array();
				$all_child_array = $checkbox_childs[$element_id];  
				
				
				$base_element_name = 'element_' . $element_id . '_';
					
				if(!empty($element_info[$element_id]['choice_has_other'])){
					$all_checkbox_value = $input[$base_element_name.'other'];
						
					//save old data into array, for form redisplay in case errors occured
					$form_data[$base_element_name.'other']['default_value'] = $input[$base_element_name.'other']; 
					if($element_info[$element_id]['is_hidden']){
						$input[$base_element_name.'other'] = '';
					}
						
					$table_data[$base_element_name.'other'] = $input[$base_element_name.'other'];
				}else{
					$all_checkbox_value = '';
				}
				
				if($element_info[$element_id]['is_required']){
					//checking 'required' for checkboxes is more complex
					//we need to get total child, and join it into one element
					//only one element is required to be checked
					
					foreach ($all_child_array as $i){
						$all_checkbox_value .= $user_input[$base_element_name.$i];
						$processed_elements[] = $base_element_name.$i;
						
						//save old data into array, for form redisplay in case errors occured
						$form_data[$base_element_name.$i]['default_value']   = $user_input[$base_element_name.$i];
						
						//prepare data for table column
						$table_data[$base_element_name.$i] = $user_input[$base_element_name.$i];
				
					}
					
					$rules[$base_element_name]['required'] 	= true;
					
					$target_input[$base_element_name] = $all_checkbox_value;
					$validation_result = la_validate_element($target_input,$rules);
					
					if($validation_result !== true){
						if($is_matrix_field && !empty($matrix_childs_array[$element_id])){
							$error_elements[$matrix_childs_array[$element_id]] = $validation_result;
						}else{
							$error_elements[$element_id] = $validation_result;
						}
					}	
					
				}else{ //if not required, we only need to capture all data
						
					foreach ($all_child_array as $i){
											
						//save old data into array, for form redisplay in case errors occured
						$form_data[$base_element_name.$i]['default_value']   = $user_input[$base_element_name.$i];
						
						if($element_info[$element_id]['is_hidden']){
							$user_input[$base_element_name.$i] = '';
						}
						//prepare data for table column
						$table_data[$base_element_name.$i] = $user_input[$base_element_name.$i];
					}
				    
				}
			}
			elseif ('select' == $element_type){ //Drop Down
				
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
				}
																
				$target_input[$element_name] = $element_data;
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value'] = $user_input[$element_name]; 
				
				//prepare data for table column
				$table_data[$element_name] = $user_input[$element_name]; 
				
			}
			elseif ('date' == $element_type || 'europe_date' == $element_type){ //Date
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 3 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
								
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
												
				if(!empty($element_info[$element_id]['is_required'])){
					$rules[$element_name]['required'] = true;
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name]	 = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
				
				$rules['element_date']['date'] = 'yyyy/mm/dd';
				
				if(!empty($element_info[$element_id]['is_unique'])){
					$rules['element_date']['unique'] = $form_id.'#'.substr($element_name,0,-2); 
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				if(!empty($element_info[$element_id]['date_enable_range'])){
					if(!empty($element_info[$element_id]['date_range_max']) || !empty($element_info[$element_id]['date_range_min'])){
						$rules['element_date']['date_range'] = $element_info[$element_id]['date_range_min'].'#'.$element_info[$element_id]['date_range_max'];
					}
				}
				
				//disable past/future dates, if enabled. this rule override the date range rule being set above
				if(!empty($element_info[$element_id]['date_disable_past_future']) && $is_edit_page === false){
					$today_date = date('Y-m-d',$time);
					
					if($element_info[$element_id]['date_past_future'] == 'p'){ //disable past dates
						$rules['element_date']['date_range'] = $today_date.'#0000-00-00';
					}else if($element_info[$element_id]['date_past_future'] == 'f'){ //disable future dates
						$rules['element_date']['date_range'] = '0000-00-00#'.$today_date;
					}
				}
				
				//check for weekend dates rule
				if(!empty($element_info[$element_id]['date_disable_weekend'])){
					$rules['element_date']['date_weekend'] = true;
				}
				
				//get disabled dates (either coming from 'date selection limit' or 'disable specific dates' rules)
				$disabled_dates = array();
				
				//get disabled dates from 'date selection limit' rule
				if(!empty($element_info[$element_id]['date_enable_selection_limit']) && !empty($element_info[$element_id]['date_selection_max'])){
					
					//if this is edit entry page, bypass the date selection limit rule when the selection being made is the same
					$disabled_date_where_clause = '';
					if(!empty($edit_id) && ($_SESSION['la_logged_in'] === true || $_SESSION['la_client_logged_in'] === true)){ // edited on 29 oct 2014
						$disabled_date_where_clause = "AND `id` <> {$edit_id}";
					}
					
					$sub_query = "select selected_date from (select date_format(`data_value`, '%Y-%c-%e') as selected_date, count(`data_value`) as total_selection from `".LA_TABLE_PREFIX."form_{$form_id}` where `field_name` = 'element_{$row['element_id']}' and `data_value` is not null {$disabled_date_where_clause} group by `data_value`) as A where A.total_selection >= ?";
					
					$params = array($element_info[$element_id]['date_selection_max']);
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					
					while($sub_row = la_do_fetch_result($sub_sth)){
						$disabled_dates[] = $sub_row['selected_date'];
					}
				}
				
				
				//get disabled dates from 'disable specific dates' rules
				if(!empty($element_info[$element_id]['date_disable_specific']) && !empty($element_info[$element_id]['date_disabled_list'])){
					$exploded = array();
					$exploded = explode(',',$element_info[$element_id]['date_disabled_list']);
					foreach($exploded as $date_value){
						$disabled_dates[] = date('Y-n-j',strtotime(trim($date_value)));
					}
				}
				
				if(!empty($disabled_dates)){
					$rules['element_date']['disabled_dates'] = $disabled_dates;
				}
				
				$target_input[$element_name]   = $user_input[$element_name];
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				
				$base_element_name = substr($element_name,0,-2);
				if('date' == $element_type){ //MM/DD/YYYY
					$target_input['element_date'] = $user_input[$element_name_3].'-'.$user_input[$element_name].'-'.$user_input[$element_name_2];
					
					//prepare data for table column
					$table_data[$base_element_name] = $user_input[$element_name_3].'-'.$user_input[$element_name].'-'.$user_input[$element_name_2];
				}else{ //DD/MM/YYYY
					$target_input['element_date'] = $user_input[$element_name_3].'-'.$user_input[$element_name_2].'-'.$user_input[$element_name];
					
					//prepare data for table column
					$table_data[$base_element_name] = $user_input[$element_name_3].'-'.$user_input[$element_name_2].'-'.$user_input[$element_name];
				}
				
				$test_empty = str_replace('-','',$target_input['element_date']); //if user not submitting any entry, remove the dashes
				if(empty($test_empty)){
					unset($target_input['element_date']);
					$table_data[$base_element_name] = '';
				}
										
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
								
			}
			elseif ('simple_phone' == $element_type){ //Simple Phone
							
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required'] 	= true;
				}
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name] = '';
				}
				
				if(!empty($user_input[$element_name])){
					$rules[$element_name]['simple_phone'] = true;
				}
				
				if($element_info[$element_id]['is_unique']){
					$rules[$element_name]['unique'] 	= $form_id.'#'.$element_name;
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
									
				$target_input[$element_name]   = $user_input[$element_name];
							
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				
				//prepare data for table column
				$table_data[$element_name] = $user_input[$element_name]; 
								
			}
			elseif ('phone' == $element_type){ //Phone - US format
				
				if(!empty($processed_elements) && is_array($processed_elements) && in_array($element_name,$processed_elements)){
					continue;
				}
				
				//compound element, grab the other element, 3 elements total	
				
				$element_name_2 = substr($element_name,0,-1).'2';
				$element_name_3 = substr($element_name,0,-1).'3';
								
				$processed_elements[] = $element_name_2; //put this element into array so that it won't be processed next
				$processed_elements[] = $element_name_3;
												
				if($element_info[$element_id]['is_required']){
					$rules[$element_name]['required']   = true;
					$rules[$element_name_2]['required'] = true;
					$rules[$element_name_3]['required'] = true;
				}
				
				$rules['element_phone']['phone'] = true;
				if($element_info[$element_id]['is_hidden']){
					$user_input[$element_name]   = '';
					$user_input[$element_name_2] = '';
					$user_input[$element_name_3] = '';
				}
				
				
				if($element_info[$element_id]['is_unique']){
					$rules['element_phone']['unique'] = $form_id.'#'.substr($element_name,0,-2); 
					$target_input['dbh'] = $dbh; //we need to pass the $dbh for this 'unique' rule
				}
				
				$target_input[$element_name]   = $user_input[$element_name];			
				$target_input[$element_name_2] = $user_input[$element_name_2];
				$target_input[$element_name_3] = $user_input[$element_name_3];
				$target_input['element_phone'] = $user_input[$element_name].$user_input[$element_name_2].$user_input[$element_name_3];
									
				$validation_result = la_validate_element($target_input,$rules);
				
				if($validation_result !== true){
					$error_elements[$element_id] = $validation_result;
				}
				
				//save old data into array, for form redisplay in case errors occured
				$form_data[$element_name]['default_value']   = htmlspecialchars($user_input[$element_name]); 
				$form_data[$element_name_2]['default_value'] = htmlspecialchars($user_input[$element_name_2]);
				$form_data[$element_name_3]['default_value'] = htmlspecialchars($user_input[$element_name_3]);
				
				//prepare data for table column
				$table_data[substr($element_name,0,-2)] = $user_input[$element_name].$user_input[$element_name_2].$user_input[$element_name_3];
				
			}
			elseif ('file' == $element_type){ //File
				if($element_info[$element_id]['file_enable_advance'] == 1 && $element_info[$element_id]['file_select_existing_files'] == 1) {
					if($element_info[$element_id]['file_upload_synced'] == 1 && !empty($element_info[$element_id]['element_machine_code'])) {
						//copy selected existing files to /auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']} and insert the file names into ap_file_upload_synced table
						if($input["element_".$element_id."_selected_existing_files"] != "") {
							$newly_added_files = array();
							$existing_files = array();
							//make a folder if it doesn't exist
							if (!file_exists($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}")) {
								mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}", 0777, true);
							}

							$selected_files = explode("|", $input["element_".$element_id."_selected_existing_files"]);
							foreach ($selected_files as $selected_file) {
								$time_stamp = time();
								$selected_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/".explode("*", $selected_file)[0];
								$destination_file_token = $input["element_".$element_id."_token"];
								$destination_file_name = "{$time_stamp}_{$form_id}_{$element_id}_{$destination_file_token}-".explode("*", $selected_file)[1];
								
								$destination_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_info[$element_id]['element_machine_code']}/{$destination_file_name}";
								if(copy($selected_file_path, $destination_file_path)) {
									array_push($newly_added_files, $destination_file_name);
								}
							}
							if(count($newly_added_files) > 0) {
								$query_file = "SELECT `id`, `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ? LIMIT 1;";
								$sth_file = la_do_query($query_file, array($element_info[$element_id]['element_machine_code'], $company_id), $dbh);
								$row_file = la_do_fetch_result($sth_file);
								if($row_file["id"]) {
									$existing_files = json_decode($row_file['files_data']);
									$existing_files = array_merge($existing_files, $newly_added_files);
									array_unique($existing_files);
									$query  = "UPDATE `".LA_TABLE_PREFIX."file_upload_synced` SET `files_data` = ? WHERE `element_machine_code` = ? AND company_id = ?;";
									la_do_query($query, array(json_encode($existing_files), $element_info[$element_id]['element_machine_code'], $company_id), $dbh);
								} else {
									$query  = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`element_machine_code`, `files_data`, company_id) VALUES (?, ?, ?);";
									la_do_query($query, array($element_info[$element_id]['element_machine_code'], json_encode($newly_added_files), $company_id), $dbh);
								}
								//flipping status indicators
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $element_id, $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $element_id, $company_id, $entry_id, 2), $dbh);
							}
						}
					} else {
						//copy selected existing files to /auditprotocol/data/form_{$form_id}/files/ and insert the file names into DB
						if($input["element_".$element_id."_selected_existing_files"] != "") {
							$tmpQryArr = array();
							$tmpDatArr = array();
							$eth_data_Arr = array();
							$newly_added_files = array();
							$existing_files = array();
							//make a folder if it doesn't exist
							if (!file_exists($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files")) {
								mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files", 0777, true);
							}

							$selected_files = explode("|", $input["element_".$element_id."_selected_existing_files"]);
							foreach ($selected_files as $selected_file) {
								$selected_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/".explode("*", $selected_file)[0];
								$destination_file_token = $input["element_".$element_id."_token"];
								$destination_file_name = "element_{$element_id}_{$destination_file_token}-".explode("*", $selected_file)[1];
								
								$destination_file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/{$destination_file_name}";
								if(copy($selected_file_path, $destination_file_path)) {
									array_push($newly_added_files, $destination_file_name);
								}
							}
							if(count($newly_added_files) > 0) {
								$eth_data_Arr[] = array(
									'company_id' => $company_id,
									'field_name' => "element_{$element_id}",
									'field_code' => "code_{$element_id}",
									'files' => implode("|", $newly_added_files)
								);

								$query_file = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` = ? AND company_id = ? AND entry_id = ?";
								$sth_file = la_do_query($query_file, array("element_{$element_id}", $company_id, $entry_id), $dbh);
								$row_file = la_do_fetch_result($sth_file);
								if(!empty($row_file["field_name"]) && !empty($row_file["data_value"])) {
									$existing_files = explode("|", $row_file['data_value']);
								}
								$existing_files = array_merge($existing_files, $newly_added_files);
								array_unique($existing_files);

								$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES ";
								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "element_{$element_id}", "code_{$element_id}", implode("|", $existing_files), "0", $element_info[$element_id]['element_machine_code']));

								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
								array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");			
								
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "ip_address", "", $_SERVER['REMOTE_ADDR'], "0", ''));
								$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "status", "", 1, "0", ''));
								//if date_created doesn't exist, add created date
								$query_date  = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ? AND `field_name` = ?";
								$result_date = la_do_query($query_date, array($company_id, $entry_id, "date_created"), $dbh);
								$row_date    = la_do_fetch_result($result_date);
								
								if(empty($row_date['data_value'])){
									array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?)");
									$tmpDatArr = array_merge($tmpDatArr, array($company_id, $entry_id, "date_created", "", date("Y-m-d H:i:s"), "0", ''));
								}
								$query .= implode(",", $tmpQryArr);
								$query .= " ON DUPLICATE KEY update `data_value` = values(`data_value`);";
								if(count($tmpDatArr)){
									la_do_query($query, $tmpDatArr, $dbh);
									add_eth_data($dbh, $form_id, $eth_data_Arr);
								}
								//flipping status indicators
								$query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ? AND `entry_id` = ?";
								la_do_query($query_status_2, array($form_id, $element_id, $company_id, $entry_id), $dbh);
								$query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (null, ?, ?, ?, ?, ?)";
								la_do_query($query_status_3, array($form_id, $element_id, $company_id, $entry_id, 2), $dbh);
							}
						}
					}
				}
				
				$listfile_name = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/listfile_{$input[$element_name.'_token']}.php";
				
				if(!file_exists($listfile_name)) {
					
					$check_filetype = false;
					if($element_info[$element_id]['is_required']){
						$rules[$element_name]['required_file'] 	= true;
						$rules[$element_name]['filetype'] 	= true;
						$check_filetype = true;
						
						//if form review enabled, and user pressed back button after going to review page
						//or if this is multipage form
						//disable the required file checking if file already uploaded
						if(!empty($_SESSION['review_id']) || ($form_page_total > 1) || ($is_edit_page === true)){
							if(!empty($element_info[$element_id]['existing_file'])){
								unset($rules[$element_name]['required_file']);
								
								//file type validation should only disabled when no file being uploaded
								if($_FILES[$element_name]['size'] == 0){
									unset($rules[$element_name]['filetype']);
									$check_filetype = false;
								}
							}
						}
					}else{
						if($_FILES[$element_name]['size'] > 0){
							$rules[$element_name]['filetype'] 	= true;
							$check_filetype = true;
						}
					}
					
					if($check_filetype == true && !empty($element_info[$element_id]['file_enable_type_limit'])){
						if($element_info[$element_id]['file_block_or_allow'] == 'b'){ //block file type
							$target_input['file_block_or_allow'] = 'b';
						}elseif($element_info[$element_id]['file_block_or_allow'] == 'a'){
							$target_input['file_block_or_allow'] = 'a';
						}
						
						$target_input['file_type_list'] = $element_info[$element_id]['file_type_list'];
					}
																	
					$target_input[$element_name] = $element_name; //special for file, only need to pass input name
					$validation_result = la_validate_element($target_input,$rules);
					
					if($validation_result !== true){
						$error_elements[$element_id] = $validation_result;
					}else{
						//if validation passed, store uploaded file info into array
						if($_FILES[$element_name]['size'] > 0){
							$uploaded_files[] = $element_name;
						}

						// print_r($uploaded_files);
						// die();
					}
				} else { //if files were uploaded using advance uploader
					//file type validation already done in upload.php, so we don't need to do validation again here
					
					//store uploaded file list into array
					$current_element_uploaded_files_advance = array();
					$current_element_uploaded_files_advance = file($listfile_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					array_shift($current_element_uploaded_files_advance); //remove the first index of the array
					array_pop($current_element_uploaded_files_advance); //remove the last index of the array
					
					$uploaded_files_advance[$element_id]['listfile_name'] 	 = $listfile_name;
					$uploaded_files_advance[$element_id]['listfile_content'] = $current_element_uploaded_files_advance;
					
					
					//save old token into array, for form redisplay in case errors occured
					$form_data[$element_name]['file_token']  = $input[$element_name.'_token'];					
				}
				
				// $table_data[$element_name] = $element_name;	
				$table_data[$element_name] = !empty($input[$element_name.'_token']) ? $input[$element_name.'_token'] : $element_name;
				
			}
			elseif ('casecade_form' == $element_type){				
				$casecade_input 		   = array();
				$casecade_input['input']   = $_POST[$form_id."_".$element_info[$element_id]['default_value']];
				$casecade_input['form_id'] = (int)$element_info[$element_id]['default_value'];
				$casecade_input['edit_id'] = false;
				$casecade_table_data = la_process_casecade_form(array('dbh' => $dbh, 'la_settings' => $la_settings, 'is_committed' => $is_committed, 'form_id' => $casecade_input['form_id'], 'company_id' => $company_id, 'input' => $casecade_input['input'], 'page_number' => isset($_POST['casecade_form_page_number']) ? $_POST['casecade_form_page_number'] : 1));
				
				if(!empty($casecade_table_data['error_elements'])){
					$error_elements['casecade'][$casecade_input['form_id']] = $casecade_table_data['error_elements'];
				}
				
				$table_data[$element_name] = array($casecade_input['form_id'] => $casecade_table_data);
			}
		}

		//get form redirect info, if any
		//get form properties data
		$query 	= "select 
						 form_redirect,
						 form_redirect_enable,
						 form_email,
						 form_unique_ip,
						 form_captcha,
						 form_captcha_type,
						 form_review,
						 form_page_total,
						 form_resume_enable,
						 form_name,
						 esl_enable,
						 esl_from_name,
						 esl_from_email_address,
						 esl_replyto_email_address,
						 esl_subject,
						 esl_content,
						 esl_plain_text,
						 esr_enable,
						 esr_email_address,
						 esr_from_name,
						 esr_from_email_address,
						 esr_replyto_email_address,
						 esr_subject,
						 esr_content,
						 esr_plain_text,
						 webhook_enable,
						 payment_enable_merchant,
						 payment_merchant_type,
						 ifnull(payment_paypal_email,'') payment_paypal_email,
						 payment_paypal_language,
						 payment_currency,
						 payment_show_total,
						 payment_total_location,
						 payment_enable_recurring,
						 payment_recurring_cycle,
						 payment_recurring_unit,
						 payment_price_type,
						 payment_price_amount,
						 payment_price_name,
						 logic_email_enable,
						 logic_webhook_enable,
						 esl_bcc_email,
						 esl_pdf_attach,
						 esl_zip_attach,
						 esr_bcc_email,
						 esr_pdf_attach,
						 esr_zip_attach
				     from 
				     	 `".LA_TABLE_PREFIX."forms` 
				    where 
				    	 form_id=?";
		
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row['form_redirect_enable'])){
			$form_redirect   = $row['form_redirect'];
		}
		$form_unique_ip  = $row['form_unique_ip'];
		$form_email 	 = $row['form_email'];
		$form_captcha	 = $row['form_captcha'];
		$form_captcha_type	= $row['form_captcha_type'];
		$form_review	 = $row['form_review'];
		$form_page_total = $row['form_page_total'];
		$form_name		 = $row['form_name'];
		
		$user_ip_address 	= $_SERVER['REMOTE_ADDR'];
		
		$esl_enable	    = $row['esl_enable'];
		$esl_from_name 	= $row['esl_from_name'];
		$esl_from_email_address    = $row['esl_from_email_address'];
		$esl_replyto_email_address = $row['esl_replyto_email_address'];
		$esl_subject 	= $row['esl_subject'];
		$esl_content 	= $row['esl_content'];
		$esl_plain_text	= $row['esl_plain_text'];
		// New
		$esl_bcc_email  = $row['esl_bcc_email'];
		$esl_pdf_attach = $row['esl_pdf_attach'];
		$esl_zip_attach = $row['esl_zip_attach'];
		$esr_enable 	= $row['esr_enable'];
		$esr_email_address 	= $row['esr_email_address'];
		$esr_from_name 	= $row['esr_from_name'];
		$esr_from_email_address    = $row['esr_from_email_address'];
		$esr_replyto_email_address = $row['esr_replyto_email_address'];
		$esr_subject 	= $row['esr_subject'];
		$esr_content 	= $row['esr_content'];
		$esr_plain_text	= $row['esr_plain_text'];
		// New
		$esr_bcc_email  = $row['esr_bcc_email'];
		$esr_pdf_attach = $row['esr_pdf_attach'];
		$esr_zip_attach = $row['esr_zip_attach'];
		$form_resume_enable = $row['form_resume_enable'];
		
		$webhook_enable = (int) $row['webhook_enable'];
		
		$payment_enable_merchant = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		
		$payment_merchant_type 	 = $row['payment_merchant_type'];
		$payment_paypal_email 	 = $row['payment_paypal_email'];
		$payment_paypal_language = $row['payment_paypal_language'];
		
		$payment_currency 		  = $row['payment_currency'];
		$payment_show_total 	  = (int) $row['payment_show_total'];
		$payment_total_location   = $row['payment_total_location'];
		$payment_enable_recurring = (int) $row['payment_enable_recurring'];
		$payment_recurring_cycle  = (int) $row['payment_recurring_cycle'];
		$payment_recurring_unit   = $row['payment_recurring_unit'];
		
		$payment_price_type   = $row['payment_price_type'];
		$payment_price_amount = (float) $row['payment_price_amount'];
		$payment_price_name   = $row['payment_price_name'];
		$logic_email_enable	  = (int) $row['logic_email_enable'];
		$logic_webhook_enable = (int) $row['logic_webhook_enable'];

		//check if users added for { Add User to Approvers }
		$query = "SELECT count(*) as total_count from `".LA_TABLE_PREFIX."approver_logic` where form_id = ?";
		$params = array($form_id);
		$result = la_do_query($query,$params,$dbh);

		while($row = la_do_fetch_result($result)){
			$approver_logic_count = $row['total_count'];
		}

		//if the user is saving a form to resume later, we need to discard all validation errors
		if(!empty($input['generate_resume_url']) && !empty($form_resume_enable) && ($form_page_total > 1)){
			$is_saving_form_resume = true;
			$error_elements = array();
		}else{
			$is_saving_form_resume = false;
		}
		
		$process_result['form_redirect']  = $form_redirect;
		$process_result['old_values'] 	  = $form_data;
		$process_result['error_elements'] = $error_elements;
		
		//if this is edit_entry page, unique ip address validation should be bypassed
		$check_unique_ip = false;

		if(!empty($edit_id) && ($_SESSION['la_logged_in'] === true || $_SESSION['la_client_logged_in'] === true)){ // edited on 29 oct 2014
			$check_unique_ip = false;
		}else if(!empty($form_unique_ip)){
			$check_unique_ip = true;
		}
		//check for ip address
		
		if(!empty($_SESSION['edit_entry']['form_id']) && $_SESSION['edit_entry']['form_id'] === $form_id){
			//when editing an entry, the captcha shouldn't be checked
			$is_bypass_captcha = true;
		}else{
			$is_bypass_captcha = false;
		}
		
		//check for captcha if enabled and there is no errors from previous fields
		//on multipage form, captcha should be validated on the last page only
		if(!empty($form_captcha) && empty($error_elements) && ($is_bypass_captcha !== true)){
			
			if($form_page_total == 1 || ($form_page_total == $page_number)){
				
				if($form_captcha_type == 'i'){//if simple image captcha is being used
					
					if(!empty($_POST['captcha_response_field'])){
						
						$captcha_response_field = trim($_POST['captcha_response_field']);
						 if (PhpCaptcha::Validate($captcha_response_field) !== true) {
						 	$error_elements['element_captcha'] = 'el-required';
					        $process_result['error_elements'] = $error_elements;
						 }else{
						 	//captcha succesfully validated
						 	//set a session variable, so that the user won't need to fill it again, if this is a multi-page form
						 	$_SESSION['captcha_passed'][$form_id] = true;
						 }
					}else{ //user not entered the words at all
						
						$error_elements['element_captcha'] = 'el-required';
					    $process_result['error_elements']  = $error_elements;
					}
					
				}else if($form_captcha_type == 't'){//if simple text captcha is being used
					
					if(!empty($_POST['captcha_response_field'])){
						
						$captcha_response_field =  strtolower(trim($_POST['captcha_response_field']));
						
						if($captcha_response_field != strtolower($_SESSION['LA_TEXT_CAPTCHA_ANSWER'])) {
						 	$error_elements['element_captcha'] = 'incorrect-text-captcha-sol';
					        $process_result['error_elements'] = $error_elements;
						}else{
							unset($_SESSION['LA_TEXT_CAPTCHA_ANSWER']);
							//captcha succesfully validated
						 	//set a session variable, so that the user won't need to fill it again, if this is a multi-page form
						 	$_SESSION['captcha_passed'][$form_id] = true;
						}
					}else{ //user not entered the words at all
						
						$error_elements['element_captcha'] = 'el-text-required';
					    $process_result['error_elements']  = $error_elements;
					}
					
				}else if($form_captcha_type == 'r'){ //otherwise reCaptcha is being used
					if(!empty($_POST['g-recaptcha-response'])){
						$recaptcha_response = recaptcha_check_answer (getRecaptchaSecretKey(),
						$user_ip_address,
						$_POST["g-recaptcha-response"]);
						
			            if($recaptcha_response !== false){ //if false, then we can't connect to captcha server, bypass captcha checking            	
			            	if ($recaptcha_response->is_valid === false) {
								$error_elements['element_captcha'] = $recaptcha_response->error;
					            $process_result['error_elements'] = $error_elements;
					        }else{
					        	//captcha succesfully validated
							 	//set a session variable, so that the user won't need to fill it again, if this is a multi-page form
							 	$_SESSION['captcha_passed'][$form_id] = true;
					        }
			            }
					}else{ //user not entered the words at all
						$error_elements['element_captcha'] = 'el-required';
					    $process_result['error_elements']  = $error_elements;
					}
				}
			}
            
		}
		
		//if the 'previous' button being clicked, we need to discard any validation errors
		if(!empty($input['submit_secondary']) || !empty($input['submit_secondary_x'])){
			$process_result['error_elements'] = '';
			$process_result['custom_error'] = '';
			$error_elements = array();
		}
		
		//insert ip address and date created
		$table_data['ip_address']   = $user_ip_address;
		$table_data['date_created'] = date("Y-m-d H:i:s");
		$table_data['audit'] = "0";
		
		
		$is_inserted = false;
		
		//start insert data into table ----------------------		
		//dynamically create the field list and field values, based on the input given
		$has_value = true;
					
		$query705="SELECT max(element_page_number) as max_page_number from ".LA_TABLE_PREFIX."form_elements WHERE form_id=?";
		$params705 = array($form_id);
		$sth705 = la_do_query($query705,$params705,$dbh);
		$row705 = la_do_fetch_result($sth705);
		$max_page_number = $row705['max_page_number']; 


		if($input['page_number'] == $form_page_total || (isset($input["save_form_resume_later"]) && $input["save_form_resume_later"] == true )){
			$is_committed = true;
		}

		if($has_value){ //if blank form submitted, dont insert anything				
			//start insert query ----------------------------------------	
			$field_list   = substr($field_list,0,-1);
			$field_values = substr($field_values,0,-1);
			
			$is_inserted = true;

			//insert to temporary table, if form review is enabled or this is multipage form
			// var_dump($table_data);
			if(isset($_SESSION['la_client_entity_id'])){
				insertAndUpdateFormData(array('dbh' => $dbh, 'la_settings' => $la_settings, 'is_committed' => $is_committed, 'form_id' => $form_id, 'company_id' => $company_id, 'entry_id' => $entry_id, 'table_data' => $table_data, 'element_info' => $element_info, 'element_resume_checkbox' => (isset($_POST['element_resume_checkbox']) && !$_POST['element_resume_checkbox'] ? 1 : 0), 'element_option_lookup' => $element_option_lookup, 'casecade_page_number' => (isset($_POST['casecade_form_page_number']) ? $_POST['casecade_form_page_number'] : 1), 'casecade_is_committed' => $casecade_is_committed));	
			}
			
			//end insert query ------------------------------------------
			
		}
		//end insert data into table -------------------------
		
		//process any rules to skip pages, if this functionality is being enabled
		
		$process_result['logic_page_enable'] = false;
		
		//print_r($logic_page_enable);
		
		if(!empty($logic_page_enable)){
			
			//if the back button being clicked, don't evaluate the logic conditions
			//simply get the previous page from the array
			if(!empty($input['submit_secondary']) || !empty($input['submit_secondary_x'])){
				
				$pages_history = array();
				$pages_history = $_SESSION['la_pages_history'][$form_id];
				
				$page_number_array_index = array_search($page_number, $pages_history);
				$previous_page_number 	 = $pages_history[$page_number_array_index - 1];
				$process_result['logic_page_enable'] = true;
				$process_result['target_page_id'] 	 = $previous_page_number;								
			}else{
					
				//submit/continue button being clicked
				//get all the destination pages from ap_page_logic
				//only get pages with larger page number. the skip page logic can't move backward
				$query = "SELECT 
								page_id,
								rule_all_any 
							FROM 
								".LA_TABLE_PREFIX."page_logic 
						   WHERE 
								form_id = ? and ((1 = 1 OR page_id in('payment','review','success'))) ORDER BY page_id asc";

				$params = array($form_id);
				$sth = la_do_query($query,$params,$dbh);
				
				$page_logic_array = array();
				$i = 0;
				while($row = la_do_fetch_result($sth)){
					$page_logic_array[$i]['page_id'] 	  = $row['page_id'];
					$page_logic_array[$i]['rule_all_any'] = $row['rule_all_any'];
					$i++;
				}
				
				//evaluate the condition for each destination page
				//once a condition results true, break the loop and send the result
                if(!empty($page_logic_array)){
                    //get page_logic_conditions for the current page
                    $query = "SELECT plc.*, e.element_id FROM ".LA_TABLE_PREFIX."page_logic_conditions AS plc LEFT JOIN ".LA_TABLE_PREFIX."form_elements AS e ON (plc.form_id = e.form_id AND plc.element_name = CONCAT('element_', e.element_id)) WHERE plc.form_id = ? AND e.element_page_number = ? ORDER BY apc_id";

					$sth = la_do_query($query, array($form_id, $page_number), $dbh);
                	$logic_pages = [];
					while($row = la_do_fetch_result($sth)) {

                        $condition_params = array();
                        $condition_params['form_id'] = $form_id;
                        $condition_params['element_name'] = $row['element_name'];
                        $condition_params['rule_condition'] = $row['rule_condition'];
                        $condition_params['rule_keyword'] = $row['rule_keyword'];

                        $current_page_conditions_status = la_get_condition_status_from_table($dbh, $condition_params);
                        if($current_page_conditions_status) {
                        	$process_result['logic_page_enable'] = true;
							$process_result['target_page_id'] 	 = $row['target_page_id'];
							break;
                        }
                    }
                }
			}
		}

		if($is_inserted && $is_committed){
			//check if the form has any template files attached or Wysiwyg template
			$query_template_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_template` AS t ON f.form_id = t.form_id WHERE f.`form_id` = ? AND f.`form_enable_template` = ?";
			$result_template_enable = la_do_query($query_template_enable, array($form_id, 1), $dbh);
			$template_enable = $result_template_enable->fetchColumn();

			$query_wysiwyg_enable = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."forms` AS f RIGHT JOIN `".LA_TABLE_PREFIX."form_templates` AS t ON f.form_template_wysiwyg_id = t.id WHERE f.`form_id` = ? AND f.`form_enable_template_wysiwyg` = ? AND f.form_template_wysiwyg_id != ?";
			$result_wysiwyg_enable = la_do_query($query_wysiwyg_enable, array($form_id, 1, 0), $dbh);
			$wysiwyg_enable = $result_wysiwyg_enable->fetchColumn();

			if($template_enable > 0 || $wysiwyg_enable > 0) {
				//check if the form has more than 1000 fields or not
				$form_element_count = getFormFieldCount($dbh, $form_id);

				$params = array('dbh' => $dbh, 'form_id' => $form_id, 'company_id' => $company_id, 'client_id' => $_SESSION['la_client_user_id'], 'entry_id' => $entry_id, 'isAdmin' => 0);

				if( $form_element_count > 1000 ) {
					add_background_proccess($params);
				} else {
					$zipPath = getPortalElementWithValueArray($params);
				}
			}
		}

		//start sending notification email to admin ------------------------------------------
		if(($is_inserted && !empty($esl_enable) && !empty($form_email) && empty($form_review) && ($form_page_total == 1) && empty($edit_id) && ($delay_notifications === false) ) || 
		   ($is_inserted && !empty($esl_enable) && !empty($form_email) && $is_committed && empty($edit_id) && ($delay_notifications === false) )
		){
			//get parameters for the email
				
			//from name
			if(!empty($esl_from_name)){			
				if(is_numeric($esl_from_name)){
					$admin_email_param['from_name'] = '{element_'.$esl_from_name.'}';
				}else{
					$admin_email_param['from_name'] = $esl_from_name;
				}
			}else{
				$admin_email_param['from_name'] = 'IT Audit Machine';
			}
			
			//from email address
			if(!empty($esl_from_email_address)){
				if(is_numeric($esl_from_email_address)){
					$admin_email_param['from_email'] = '{element_'.$esl_from_email_address.'}';
				}else{
					$admin_email_param['from_email'] = $esl_from_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$admin_email_param['from_email'] = "no-reply@{$domain}";
			}
			//reply-to email address
			if(!empty($esl_replyto_email_address)){
				if(is_numeric($esl_replyto_email_address)){
					$admin_email_param['replyto_email'] = '{element_'.$esl_replyto_email_address.'}';
				}else{
					$admin_email_param['replyto_email'] = $esl_replyto_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$admin_email_param['replyto_email'] = "no-reply@{$domain}";
			}
			
			//subject
			if(!empty($esl_subject)){
				$admin_email_param['subject'] = $esl_subject;
			}else{
				$admin_email_param['subject'] = '{form_name} [#{entry_no}]';
			}
			
			//content
			if(!empty($esl_content)){
				$admin_email_param['content'] = $esl_content;
			}else{
				$admin_email_param['content'] = '{entry_data}';
			}
			
			$admin_email_param['as_plain_text'] = $esl_plain_text;
			$admin_email_param['target_is_admin'] = true; 
			$admin_email_param['itauditmachine_base_path'] = $input['itauditmachine_base_path'];
			$admin_email_param['check_hook_file'] = true; 
			
			$admin_email_param['bcc_email']  = $esl_bcc_email;
			$admin_email_param['pdf_attach'] = $esl_pdf_attach;
			$admin_email_param['zip_attach'] = $esl_zip_attach;
			$admin_email_param['zip_attach_path'] = $zipPath;
			$admin_email_param['is_inserted'] = 1;

			la_send_notification($dbh,$form_id,$company_id,$entry_id,$form_email,$admin_email_param);
    		
		}
		// echo $_SESSION['la_client_entity_id'];
		//end emailing notifications to admin ----------------------------------------------

		//start sending notification email to user ------------------------------------------
		if(($is_inserted && !empty($esr_enable) && !empty($esr_email_address) && empty($form_review) && ($form_page_total == 1) && empty($edit_id) && ($delay_notifications === false) ) || 
		   ($is_inserted && !empty($esr_enable) && !empty($esr_email_address) && $is_committed && empty($edit_id) && ($delay_notifications === false) )
		){
			//get parameters for the email
			
			//to email
			if(is_numeric($esr_email_address)){
				$esr_email_address = '{element_'.$esr_email_address.'}'; 
			}
					
			//from name
			if(!empty($esr_from_name)){			
				if(is_numeric($esr_from_name)){
					$user_email_param['from_name'] = '{element_'.$esr_from_name.'}';
				}else{
					$user_email_param['from_name'] = $esr_from_name;
				}
			}else{
				$user_email_param['from_name'] = 'IT Audit Machine';
			}
			
			//from email address
			if(!empty($esr_from_email_address)){
				if(is_numeric($esr_from_email_address)){
					$user_email_param['from_email'] = '{element_'.$esr_from_email_address.'}';
				}else{
					$user_email_param['from_email'] = $esr_from_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$user_email_param['from_email'] = "no-reply@{$domain}";
			}
			//reply-to email address
			if(!empty($esr_replyto_email_address)){
				if(is_numeric($esr_replyto_email_address)){
					$user_email_param['replyto_email'] = '{element_'.$esr_replyto_email_address.'}';
				}else{
					$user_email_param['replyto_email'] = $esr_replyto_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$user_email_param['replyto_email'] = "no-reply@{$domain}";
			}
			
			//subject
			if(!empty($esr_subject)){
				$user_email_param['subject'] = $esr_subject;
			}else{
				$user_email_param['subject'] = '{form_name} - Receipt';
			}
			
			//content
			if(!empty($esr_content)){
				$user_email_param['content'] = $esr_content;
			}else{
				$user_email_param['content'] = '{entry_data}';
			}
			
			$user_email_param['as_plain_text'] = $esr_plain_text;
			$user_email_param['target_is_admin'] = false; 
			$user_email_param['itauditmachine_base_path'] = $input['itauditmachine_base_path'];
			
			$user_email_param['bcc_email']  = $esr_bcc_email;
			$user_email_param['pdf_attach'] = $esr_pdf_attach;
			$user_email_param['zip_attach'] = $esr_zip_attach;
			$user_email_param['zip_attach_path'] = $zipPath;
			
			if(isset($_REQUEST['page_number']) && $_REQUEST['page_number'] == $row705['max_page_number']){
			
				$query700  = "select email from `".LA_TABLE_PREFIX."ask_client_users` WHERE client_id=".$_SESSION['la_client_entity_id'];
				$params700 = array();
				
				$sth700 = la_do_query($query700,$params700,$dbh);
				
				$row700 = la_do_fetch_result($sth700);
				$esr_email_address2 = $row700['email'];
				//echo $esr_email_address; exit;
				la_send_notification($dbh,$form_id,$company_id,$entry_id,$esr_email_address2,$user_email_param);
				
				$query800  = "select form_email from `".LA_TABLE_PREFIX."forms` WHERE form_id=".$form_id;
				$params800 = array();
				$sth800 = la_do_query($query800,$params800,$dbh);
				$row800 = la_do_fetch_result($sth800);
				
				if($row800['form_email'] != ''){
					$esr_email_address1 = $row800['form_email'];
					la_send_notification($dbh,$form_id,$company_id,$entry_id,$esr_email_address1,$user_email_param);
				}
			}
		}
		//end emailing notifications to user ---------------------------------------------	

		//send all notifications triggered by email-logic ---------------------
		//email logic is not affected by 'delay notifications until paid' option
        /*if($is_inserted && $is_committed && !empty($logic_email_enable)){
			$logic_email_param = array();
			$logic_email_param['itauditmachine_base_path'] = $input['itauditmachine_base_path'];
			la_send_logic_notifications($dbh,$form_id,$record_insert_id,$logic_email_param);
		}

		//send {Approve/Deny Notification Logic} logic emails
		if($is_inserted && $is_committed && !empty($approver_logic_count)){
			la_send_approver_notifications($dbh,$form_id,$company_id);
		}
      
		//send webhook notification
        if($is_inserted && $is_committed && !empty($webhook_enable)){
			la_send_webhook_notification($dbh,$form_id,$record_insert_id,0);
		}
		
		//send webhook notifications triggered by logic
		//webhook logic is not affected by 'delay notifications until paid' option
        if($is_inserted && $is_committed && !empty($logic_webhook_enable)){
			la_send_logic_webhook_notifications($dbh,$form_id,$record_insert_id);
		}*/
		
		//if there is no error message or elements, send true as status

		if(empty($error_elements) && empty($process_result['custom_error'])){		
			$process_result['status'] = true;
			if($form_page_total > 1){ //if this is multipage form
				$_SESSION['la_form_loaded'][$form_id][$page_number] = true;
				if($is_saving_form_resume){
					//if the user is saving his progress instead of submitting the form
					//copy the record from review table into main form table and set the status to incomplete (status=2)
					//also generate resume url
					$has_invalid_resume_email = false;
					
					//validate the email address first, if the user entered invalid email address, display error message
					if(!empty($input['element_resume_email'])){
						$regex  = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}$/';
						$resume_email = trim($input['element_resume_email']);
						
						$preg_result = preg_match($regex, $resume_email);
							
						if(empty($preg_result)){
							$has_invalid_resume_email = true;
							$error_elements['element_resume_email'] = $la_lang['val_email']; 
							
							$process_result['status'] = false;
							$process_result['error_elements'] = $error_elements;
							$process_result['old_values']['element_resume_email'] = $input['element_resume_email'];
						}
					}
					
					if(!$has_invalid_resume_email){
						$set_url = str_replace('auditprotocol/','',$la_settings['base_url']);
						$portal_site_url = $set_url.'portal/';
						if(isset($_REQUEST['la_page'])){
							$form_resume_url = $portal_site_url."view.php?id={$form_id}&entry_id={$entry_id}&la_page={$_REQUEST['la_page']}#main_body";
						} else {
							$form_resume_url = $portal_site_url."view.php?id={$form_id}&entry_id={$entry_id}";
						}
						
						$process_result['form_resume_url'] = $form_resume_url;
						
						if(!empty($resume_email)){
							//send the resume link to the provided email
							la_send_resume_link($dbh,$form_name,$form_resume_url,$resume_email);
						}
					}

					if( !empty($form_email) ) {
						$base_url = str_replace('/auditprotocol/','',$la_settings['base_url']);
						$portal_site_url = $base_url.'/portal';
						$form_url = $portal_site_url.'/view.php?id={$form_id}&entry_id={$entry_id}';
						$params['current_url'] = $base_url.$_SERVER["REQUEST_URI"];
						$params['la_page'] = $_REQUEST['la_page'];

						$user_id = $_SESSION['la_client_user_id'];
						$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ? ";
						$sth2 = $dbh->prepare($query);
						$query_params = array($user_id);
						$sth2->execute($query_params);
						
						$user_data	= la_do_fetch_result($sth2);

						$full_name	=	la_sanitize($user_data['full_name']);
						$username	=	la_sanitize($user_data['username']);

						$user_name = (empty($full_name)) ? $username : $full_name;

						$params['user_name'] = $user_name;

						la_send_tagging_notification($dbh,$form_name,$form_url,$form_email,$params);
					}
				}else{
					//get the next page number and send it
					//don't send page number if this is already the last page, unless back button being clicked
					if($input['page_number'] < $form_page_total){
						if(!empty($input['submit_primary']) || !empty($input['submit_primary_x'])){
							$process_result['next_page_number'] = $page_number + 1;
						}elseif (!empty($input['submit_secondary']) || !empty($input['submit_secondary_x'])){
							$process_result['next_page_number'] = $page_number - 1;
						}else{
							$process_result['next_page_number'] = $page_number + 1;
						}
					}else{ //if this is the last page
						
						if(!empty($input['submit_primary']) || !empty($input['submit_primary_x'])){
							if(!empty($form_review)){
								$process_result['review_id']   = $record_insert_id;
							}
						}elseif (!empty($input['submit_secondary']) || !empty($input['submit_secondary_x'])){
							$process_result['next_page_number'] = $page_number - 1;
						}else{
							if(!empty($form_review)){
								$process_result['review_id']   = $record_insert_id;
							}
						}
					}
				}
			}else{//if this is single page form
				//if 'form review' enabled, send review_id
				if(!empty($form_review)){
					$process_result['review_id'] = $record_insert_id;
				}else{
					//form submitted successfully, set the session to display success page
					$_SESSION['la_form_completed'][$form_id] = true;
					$process_result['company_id'] = $company_id;
					$process_result['entry_id'] = $entry_id;
				}
			}
		}else{
			$process_result['status'] = false;
		}
						
		
		//parse redirect URL for any template variables
		if(($is_inserted && !empty($process_result['form_redirect']) && empty($form_review) && ($form_page_total == 1) && empty($edit_id)) || 
		   ($is_inserted && !empty($process_result['form_redirect']) && $is_committed && empty($edit_id))
		){
			$process_result['form_redirect'] = la_parse_template_variables($dbh,$form_id,$company_id,$entry_id,$process_result['form_redirect']);
			$process_result['form_redirect'] = str_replace(array('&amp;','&#039;','&quot;'),array('&','%27','%22'),htmlentities($process_result['form_redirect'],ENT_QUOTES));
		}
		//get payment processor URL, if applicable for this form
		if(($is_inserted && empty($form_review) && ($form_page_total == 1) && empty($edit_id)) || 
		 	($is_inserted && $is_committed && empty($edit_id))){
			
			$merchant_redirect_url = @la_get_merchant_redirect_url($dbh,$form_id,$record_insert_id);
			
			if(!empty($merchant_redirect_url) && $bypass_merchant_redirect_url !== true){	
				$process_result['form_redirect'] = $merchant_redirect_url;
			}
		}
		
		//save the entry id into session for success message
		if(!empty($process_result['company_id'])){
			$_SESSION['la_success_company_id'] = $process_result['company_id'];
		}
		if($form_review == 1){
			$process_result['review_flag'] = 1;
		} else {
			$process_result['review_flag'] = 0;
		}
		return $process_result;
	}
	
	
	//process form review submit
	//move the record from temporary review table to the actual table
	function la_commit_form_review($dbh,$form_id,$record_id,$options=array()){

		
		
		$la_settings = la_get_settings($dbh);
		//by default, this function will send notification email
		if($options['send_notification'] === false){
			$send_notification = false;
		}else{
			$send_notification = true;
		}
		//move data from ap_form_x_review table to ap_form_x table
		//get all column name except session_id and id

		$columns = array();
		foreach($row as $column_name=>$column_data){
			if($column_name != 'id' && $column_name != 'session_id' && $column_name != 'status' && $column_name != 'resume_key'){
				$columns[] = $column_name;				
			}
		}	
		
		$columns_joined = implode("`,`",$columns);
		$columns_joined = '`'.$columns_joined.'`';
		
		//copy data from review table
		$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}`($columns_joined) SELECT {$columns_joined} from `".LA_TABLE_PREFIX."form_{$form_id}_review` WHERE id=?";
		$params = array($record_id);
		
		la_do_query($query,$params,$dbh);
		
		$new_record_id = (int) $dbh->lastInsertId();
		//update date_created with the current time
		$date_created = date("Y-m-d H:i:s");
		$query = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET date_created = ? WHERE `id` = ?";
		$params = array($date_created,$new_record_id);
	
		la_do_query($query,$params,$dbh);
		
		//check for resume_key from the review table
		//if there is resume_key, we need to delete the incomplete record within ap_form_x table which contain that resume_key
		/*$query = "SELECT `resume_key` FROM `".LA_TABLE_PREFIX."form_{$form_id}_review` WHERE id=?";
		$params = array($record_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);*/
		
		if(!empty($row['resume_key'])){
			$query = "DELETE from `".LA_TABLE_PREFIX."form_{$form_id}` where resume_key=? and `status`=2";
			$params = array($row['resume_key']);
			
			la_do_query($query,$params,$dbh);
		}
		
		//rename file uploads, if any
		//get all file uploads elements first
		$query = "SELECT 
						element_id 
					FROM 
						".LA_TABLE_PREFIX."form_elements 
				   WHERE 
				   		form_id=? AND 
				   		element_type='file' AND 
				   		element_status=1 AND
				   		element_is_private=0";
		$params = array($form_id);
		
		$file_uploads_array = array();
		
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			$file_uploads_array[] = 'element_'.$row['element_id'];
		}
		
		if(!empty($file_uploads_array)){
			$file_uploads_column = implode('`,`',$file_uploads_array);
			$file_uploads_column = '`'.$file_uploads_column.'`';
			
			$query = "SELECT {$file_uploads_column} FROM `".LA_TABLE_PREFIX."form_{$form_id}_review` where id=?";
			$params = array($record_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			$file_update_query = '';
			
			foreach ($file_uploads_array as $element_name){
				$filename_record = $row[$element_name];
				
				if(empty($filename_record)){
					continue;
				}
				
				//if the file upload field is using advance uploader, $filename would contain multiple file names, separated by pipe character '|'
				$filename_array = array();
				$filename_array = explode('|',$filename_record);
				
				$file_joined_value = '';
				foreach ($filename_array as $filename){
					$target_filename 	  = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/{$filename}.tmp";
					
					$regex    = '/^element_([0-9]*)_([0-9a-zA-Z]*)-([0-9]*)-(.*)$/';
					$matches  = array();
					preg_match($regex, $filename,$matches);
					$filename_noelement = $matches[4];
					
					$file_token = md5(uniqid(rand(), true)); //add random token to uploaded filename, to increase security
					$destination_filename = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/{$element_name}_{$file_token}-{$new_record_id}-{$filename_noelement}";
					
					if(file_exists($target_filename)){
						rename($target_filename,$destination_filename);
					}
				
					$filename_noelement = addslashes(stripslashes($filename_noelement));
					$file_joined_value .= "{$element_name}_{$file_token}-{$new_record_id}-{$filename_noelement}|";
				}
				
				//build update query
				$file_joined_value  = rtrim($file_joined_value,'|');
				$file_update_query .= "`{$element_name}`='{$file_joined_value}',";
			}
			
			$file_update_query = rtrim($file_update_query,',');
			if(!empty($file_update_query)){
				$query = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET {$file_update_query} WHERE id=?";
				$params = array($new_record_id);
				
				la_do_query($query,$params,$dbh);
			}
		}
		
		$_SESSION['la_form_completed'][$form_id] = true;
		
		//send notification emails
		//get form properties data
		$query 	= "select 
						 form_redirect,
						 form_redirect_enable,
						 form_email,
						 esl_enable,
						 esl_from_name,
						 esl_from_email_address,
						 esl_replyto_email_address,
						 esl_subject,
						 esl_content,
						 esl_plain_text,
						 esr_enable,
						 esr_email_address,
						 esr_from_name,
						 esr_from_email_address,
						 esr_replyto_email_address,
						 esr_subject,
						 esr_content,
						 esr_plain_text,
						 logic_email_enable,
						 logic_webhook_enable,
						 webhook_enable,
						 esl_bcc_email,
						 esl_pdf_attach,
						 esl_zip_attach,
						 esr_bcc_email,
						 esr_pdf_attach,
						 esr_zip_attach
				     from 
				     	 `".LA_TABLE_PREFIX."forms` 
				    where 
				    	 form_id=?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row['form_redirect_enable'])){
			$form_redirect   = $row['form_redirect'];
		}
		$form_email 	= $row['form_email'];
		
		$esl_from_name 	= $row['esl_from_name'];
		$esl_from_email_address    = $row['esl_from_email_address'];
		$esl_replyto_email_address = $row['esl_replyto_email_address'];
		$esl_subject 	= $row['esl_subject'];
		$esl_content 	= $row['esl_content'];
		$esl_plain_text	= $row['esl_plain_text'];
		$esl_enable     = $row['esl_enable'];
		$esl_bcc_email  = $row['esl_bcc_email'];
		$esl_pdf_attach = $row['esl_pdf_attach'];
		$esl_zip_attach = $row['esl_zip_attach'];
		$esr_email_address 	= $row['esr_email_address'];
		$esr_from_name 	= $row['esr_from_name'];
		$esr_from_email_address    = $row['esr_from_email_address'];
		$esr_replyto_email_address = $row['esr_replyto_email_address'];
		$esr_subject 	= $row['esr_subject'];
		$esr_content 	= $row['esr_content'];
		$esr_plain_text	= $row['esr_plain_text'];
		$esr_enable		= $row['esr_enable'];
		$esr_bcc_email  = $row['esr_bcc_email'];
		$esr_pdf_attach = $row['esr_pdf_attach'];
		$esr_zip_attach = $row['esr_zip_attach'];
		
		$logic_email_enable = (int) $row['logic_email_enable'];
		$logic_webhook_enable = (int) $row['logic_webhook_enable'];
		$webhook_enable = (int) $row['webhook_enable'];
		
		//start sending notification email to admin ------------------------------------------
		if(!empty($esl_enable) && !empty($form_email) && $send_notification === true){
			//get parameters for the email
					
			//from name
			if(!empty($esl_from_name)){
				if(is_numeric($esl_from_name)){
					$admin_email_param['from_name'] = '{element_'.$esl_from_name.'}';
				}else{
					$admin_email_param['from_name'] = $esl_from_name;
				}
			}else{
				$admin_email_param['from_name'] = 'IT Audit Machine';
			}
			
			//from email address
			if(!empty($esl_from_email_address)){
				if(is_numeric($esl_from_email_address)){
					$admin_email_param['from_email'] = '{element_'.$esl_from_email_address.'}';
				}else{
					$admin_email_param['from_email'] = $esl_from_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$admin_email_param['from_email'] = "no-reply@{$domain}";
			}
			//reply-to email address
			if(!empty($esl_replyto_email_address)){
				if(is_numeric($esl_replyto_email_address)){
					$admin_email_param['replyto_email'] = '{element_'.$esl_replyto_email_address.'}';
				}else{
					$admin_email_param['replyto_email'] = $esl_replyto_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$admin_email_param['replyto_email'] = "no-reply@{$domain}";
			}
			
			//subject
			if(!empty($esl_subject)){
				$admin_email_param['subject'] = $esl_subject;
			}else{
				$admin_email_param['subject'] = '{form_name} [#{entry_no}]';
			}
			
			//content
			if(!empty($esl_content)){
				$admin_email_param['content'] = $esl_content;
			}else{
				$admin_email_param['content'] = '{entry_data}';
			}
			
			$admin_email_param['as_plain_text'] = $esl_plain_text;
			$admin_email_param['target_is_admin'] = true; 
			$admin_email_param['itauditmachine_base_path'] = $options['itauditmachine_path'];
			$admin_email_param['check_hook_file'] = true;
			
			
			 
			la_send_notification($dbh,$form_id,$new_record_id,$form_email,$admin_email_param);
    	
		}
		//end emailing notifications to admin ----------------------------------------------
		
		
		//start sending notification email to user ------------------------------------------
		if(!empty($esr_enable) && !empty($esr_email_address) && $send_notification === true){
			//get parameters for the email
			
			//to email 
			if(is_numeric($esr_email_address)){
				$esr_email_address = '{element_'.$esr_email_address.'}';
			}
					
			//from name
			if(!empty($esr_from_name)){
				if(is_numeric($esr_from_name)){
					$user_email_param['from_name'] = '{element_'.$esr_from_name.'}';
				}else{
					$user_email_param['from_name'] = $esr_from_name;
				}
			}else{
				$user_email_param['from_name'] = 'IT Audit Machine';
			}
			
			//from email address
			if(!empty($esr_from_email_address)){
				if(is_numeric($esr_from_email_address)){
					$user_email_param['from_email'] = '{element_'.$esr_from_email_address.'}';
				}else{
					$user_email_param['from_email'] = $esr_from_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$user_email_param['from_email'] = "no-reply@{$domain}";
			}
			//reply-to email address
			if(!empty($esr_replyto_email_address)){
				if(is_numeric($esr_replyto_email_address)){
					$user_email_param['replyto_email'] = '{element_'.$esr_replyto_email_address.'}';
				}else{
					$user_email_param['replyto_email'] = $esr_replyto_email_address;
				}
			}else{
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$user_email_param['replyto_email'] = "no-reply@{$domain}";
			}
			
			//subject
			if(!empty($esr_subject)){
				$user_email_param['subject'] = $esr_subject;
			}else{
				$user_email_param['subject'] = '{form_name} - Receipt';
			}
			
			//content
			if(!empty($esr_content)){
				$user_email_param['content'] = $esr_content;
			}else{
				$user_email_param['content'] = '{entry_data}';
			}
			
			$user_email_param['as_plain_text'] = $esr_plain_text;
			$user_email_param['target_is_admin'] = false;
			$user_email_param['itauditmachine_base_path'] = $options['itauditmachine_path']; 
			la_send_notification($dbh,$form_id,$new_record_id,$esr_email_address,$user_email_param);
		}
		//end emailing notifications to user ----------------------------------------------
		//send all notifications triggered by email-logic ---------------------
		//email logic is not affected by 'delay notifications until paid' option
		/*if(!empty($logic_email_enable)){
			$logic_email_param = array();
			$logic_email_param['itauditmachine_base_path'] = $options['itauditmachine_path'];
			la_send_logic_notifications($dbh,$form_id,$new_record_id,$logic_email_param);
		}*/
		
		//send webhook notification
		/*if(!empty($webhook_enable) && $send_notification === true){
			la_send_webhook_notification($dbh,$form_id,$new_record_id,0);
		}*/
		//send webhook notification triggered by logic
		//email logic is not affected by 'delay notifications until paid' option
		/*if(!empty($logic_webhook_enable)){
			la_send_logic_webhook_notifications($dbh,$form_id,$new_record_id);
		}*/
		
		//delete all entry from this user in review table
		$session_id = session_id();
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_{$form_id}_review` where id=? or session_id=?";
		$params = array($record_id,$session_id);
		
		la_do_query($query,$params,$dbh);
		
		//remove form history from session
		$_SESSION['la_form_loaded'][$form_id] = array();
		unset($_SESSION['la_form_loaded'][$form_id]);
		
		//remove form access session
		$_SESSION['la_form_access'][$form_id] = array();
		unset($_SESSION['la_form_access'][$form_id]);
		
		$_SESSION['la_form_resume_url'][$form_id] = array();
		unset($_SESSION['la_form_resume_url'][$form_id]);
		//remove pages history
		$_SESSION['la_pages_history'][$form_id] = array();
		unset($_SESSION['la_pages_history'][$form_id]);
		//unset the form resume session, if any
		$_SESSION['la_form_resume_loaded'][$form_id] = false;
		unset($_SESSION['la_form_resume_loaded'][$form_id]);
		
		//get merchant redirect url, if enabled for this form
		$merchant_redirect_url = la_get_merchant_redirect_url($dbh,$form_id,$new_record_id);
		if(!empty($merchant_redirect_url)){
			$form_redirect = $merchant_redirect_url;
		}
		
		//parse redirect URL for any template variables
		if(!empty($form_redirect)){
			$form_redirect = la_parse_template_variables($dbh,$form_id,$new_record_id,$form_redirect);
			$form_redirect = str_replace(array('&amp;','&#039;','&quot;'),array('&','%27','%22'),htmlentities($form_redirect,ENT_QUOTES));
		}
		$commit_result['form_redirect'] = $form_redirect;
		$commit_result['record_insert_id'] = $new_record_id;
		//save the entry id into session for success message
		$_SESSION['la_success_entry_id'] = $new_record_id;
		
		return $commit_result;
	}
	
	//this is a helper function to check POST variable
	//if there is submit button being sent, return true
	function la_is_form_submitted(){
		if(!empty($_POST['submit_form']) || !empty($_POST['submit_primary']) || !empty($_POST['submit_secondary'])){
			return true;
		}else{
			return false;
		}
	}
	
	//this function checks if the user is allowed to see this particular form page
	function la_verify_page_access($form_id,$page_number){
		if(empty($form_id)){
			die('ID required.');
		}
		
		if(empty($page_number)){
			return 1; //send the user to page 1 of the form if no page_number being specified
		}else{
			if($_SESSION['la_form_access'][$form_id][$page_number] === true){
				return $page_number;
			}else{
				return 1;
			}
		}
	}
	
	//generate the merchant redirect URL for particular form
	//the redirect URL contain complete payment information
	function la_get_merchant_redirect_url($dbh,$form_id,$entry_id){
		
		global $la_lang;
		$la_settings 		   = la_get_settings($dbh);
		
		$la_settings['base_url'] = trim($la_settings['base_url'],'/').'/';
		$merchant_redirect_url 	 = '';
		
		$payment_has_value  = false;
		
		$query 	= "select 
						 payment_enable_merchant,
						 payment_merchant_type,
						 ifnull(payment_paypal_email,'') payment_paypal_email,
						 payment_paypal_language,
						 payment_currency,
						 payment_show_total,
						 payment_total_location,
						 payment_enable_recurring,
						 payment_recurring_cycle,
						 payment_recurring_unit,
						 payment_enable_trial,
						 payment_trial_period,
						 payment_trial_unit,
						 payment_trial_amount,
						 payment_price_type,
						 payment_price_amount,
						 payment_price_name,
						 payment_paypal_enable_test_mode,
						 payment_enable_tax,
						 payment_tax_rate,
						 form_redirect,
						 form_redirect_enable,
						 form_language,
						 payment_enable_discount,
						 payment_discount_type,
						 payment_discount_amount,
						 payment_discount_element_id
				     from 
				     	 `".LA_TABLE_PREFIX."forms` 
				    where 
				    	 form_id=?";
		
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
			
		$payment_enable_merchant = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		if(!empty($row['form_language'])){
			la_set_language($row['form_language']);
		}
		$payment_merchant_type 	 = $row['payment_merchant_type'];
		$payment_paypal_email 	 = $row['payment_paypal_email'];
		$payment_paypal_language = $row['payment_paypal_language'];
		
		$payment_currency 		  = $row['payment_currency'];
		$payment_show_total 	  = (int) $row['payment_show_total'];
		$payment_total_location   = $row['payment_total_location'];
		$payment_enable_recurring = (int) $row['payment_enable_recurring'];
		$payment_recurring_cycle  = (int) $row['payment_recurring_cycle'];
		$payment_recurring_unit   = $row['payment_recurring_unit'];
		$form_redirect_enable	  = (int) $row['form_redirect_enable'];
		$form_redirect	  		  = $row['form_redirect'];
		$payment_paypal_enable_test_mode = (int) $row['payment_paypal_enable_test_mode'];
		if(!empty($payment_paypal_enable_test_mode)){
			$paypal_url = "www.sandbox.paypal.com";
		}else{
			$paypal_url = "www.paypal.com";
		}
		$payment_enable_trial = (int) $row['payment_enable_trial'];
		$payment_trial_period = (int) $row['payment_trial_period'];
		$payment_trial_unit   = $row['payment_trial_unit'];
		$payment_trial_amount = $row['payment_trial_amount'];
		
		$payment_price_type   = $row['payment_price_type'];
		$payment_price_amount = (float) $row['payment_price_amount'];
		$payment_price_name   = $row['payment_price_name'];
		$payment_enable_tax = (int) $row['payment_enable_tax'];
		$payment_tax_rate 	= (float) $row['payment_tax_rate'];
		$payment_enable_discount = (int) $row['payment_enable_discount'];
		$payment_discount_type 	 = $row['payment_discount_type'];
		$payment_discount_amount = (float) $row['payment_discount_amount'];
		$payment_discount_element_id = (int) $row['payment_discount_element_id'];
		$is_discount_applicable = false;
		//if the discount element for the current entry_id having any value, we can be certain that the discount code has been validated and applicable
		if(!empty($payment_enable_discount) && !empty($payment_enable_merchant)){
			$query = "select element_{$payment_discount_element_id} coupon_element from ".LA_TABLE_PREFIX."form_{$form_id} where `id` = ? and `status` = 1";
			$params = array($entry_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			if(!empty($row['coupon_element'])){
				$is_discount_applicable = true;
			}
		}
		
		if(!empty($form_redirect_enable) && !empty($form_redirect)){
			$form_redirect  = la_parse_template_variables($dbh,$form_id,$entry_id,$form_redirect);
			$form_redirect = str_replace(array('&amp;','&#039;','&quot;'),array('&','%27','%22'),htmlentities($form_redirect,ENT_QUOTES));
		}
		
		if(!empty($payment_enable_merchant)){ //if merchant is enabled
				
				//paypal website payment standard
				if($payment_merchant_type == 'paypal_standard'){
					//get current entry timestamp
					//$query = "select unix_timestamp(date_created) entry_timestamp from ".LA_TABLE_PREFIX."form_{$form_id} where `id` = ? and `status` = 1";
					$query = "select unix_timestamp(data_value) entry_timestamp from ".LA_TABLE_PREFIX."form_{$form_id} where field_name = 'date_created'";
					$params = array($entry_id);
		
					$sth = la_do_query($query,$params,$dbh);
					$row = la_do_fetch_result($sth);
					$entry_timestamp = $row['entry_timestamp'];
					$paypal_params = array();
					
					$paypal_params['charset'] 	    = 'UTF-8';
					$paypal_params['upload']  		= 1;
					$paypal_params['business']      = $payment_paypal_email;
					$paypal_params['currency_code'] = $payment_currency;
					$paypal_params['custom'] 		= $form_id.'_'.$entry_id.'_'.$entry_timestamp;
					$paypal_params['rm'] 			= 2; //the buyer’s browser is redirected to the return URL by using the POST method, and all payment variables are included
					$paypal_params['lc'] 			= $payment_paypal_language;
					
					if(!empty($form_redirect)){
						$paypal_params['return'] 	= $form_redirect; 
					}else{
						$paypal_params['return'] 	= $la_settings['base_url'].'view.php?id='.$form_id.'&done=1'; 
					}
					
					$paypal_params['notify_url'] 	= $la_settings['base_url'].'paypal_ipn.php';
					$paypal_params['no_shipping'] 	= 1;
					
					if(!empty($payment_enable_recurring)){ //this is recurring payment
						$paypal_params['cmd'] = '_xclick-subscriptions';
						$paypal_params['src'] = 1; //subscription payments recur, until user cancel it
						$paypal_params['sra'] = 1; //reattempt failed recurring payments before canceling
						$paypal_params['item_name'] = $payment_price_name;
						$paypal_params['p3'] 		= $payment_recurring_cycle;
						$paypal_params['t3'] 		= strtoupper($payment_recurring_unit[0]);
							
						if($paypal_params['t3'] == 'Y' && $payment_recurring_cycle > 5){
							$paypal_params['p3'] = 5; //paypal can only handle 5-year-period recurring payments, maximum	
						}
								
						if($payment_price_type == 'fixed'){ //this is fixed amount payment	
							$paypal_params['a3'] 		= $payment_price_amount;
							
							if(!empty($payment_price_amount) && ($payment_price_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$payment_price_amount -= $payment_calculated_discount;
									
									$paypal_params['a3'] = $payment_price_amount;
									$paypal_params['item_name'] .= " (-{$la_lang['discount']})";
								}
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['a3'] = $payment_price_amount + $payment_tax_amount;
									$paypal_params['item_name'] .= " (+{$la_lang['tax']} {$payment_tax_rate}%)";
								}
							}
						}else if($payment_price_type == 'variable'){
							
							$total_payment_amount = 0;
							
							//get price fields information from ap_element_prices table
							$query = "select 
											A.element_id,
											A.option_id,
											A.price,
											B.element_title,
											B.element_type,
											(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
										from
											".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
										where
											A.form_id = ?
										order by 
											A.element_id,A.option_id asc";
							
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							$price_field_columns = array();
							
							while($row = la_do_fetch_result($sth)){
								$element_id   = (int) $row['element_id'];
								$option_id 	  = (int) $row['option_id'];
								$element_type = $row['element_type'];
								
								if($element_type == 'checkbox'){
									$column_name = 'element_'.$element_id.'_'.$option_id;
								}else{
									$column_name = 'element_'.$element_id;
								}	
								
								if(!in_array($column_name,$price_field_columns)){
									$price_field_columns[] = $column_name;
									$price_field_types[$column_name] = $row['element_type'];
								}
								
								$price_values[$element_id][$option_id] 	 = $row['price'];
								
								if($element_type == 'money'){
									$price_titles[$element_id][$option_id] = $row['element_title'];
								}else{
									$price_titles[$element_id][$option_id] = $row['option_title'];
								}
							}
							$price_field_columns_joined = implode(',',$price_field_columns);
							//get quantity fields
							$quantity_fields_info = array();
							$quantity_field_columns = array();
							
							$query = "select 
									 		element_id,
									 		element_number_quantity_link
										from 
											".LA_TABLE_PREFIX."form_elements 
									   where
									   		form_id = ? and
									   		element_status = 1 and
									   		element_type = 'number' and
									   		element_number_enable_quantity = 1 and
									   		element_number_quantity_link is not null
									group by 
											element_number_quantity_link 
									order by
									   		element_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							while($row = la_do_fetch_result($sth)){
								$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
								$quantity_field_columns[] = 'element_'.$row['element_id'];			
							}
							if(!empty($quantity_fields_info)){
								$quantity_field_columns_joined = implode(',', $quantity_field_columns);
								$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
							}
							
							//check the value of the price fields from the ap_form_x table
							$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
							$params = array($entry_id);
							$sth = la_do_query($query,$params,$dbh);
							$row = la_do_fetch_result($sth);
							
							$processed_column_name = array();
							$selected_item_names = array();
							
							foreach ($price_field_columns as $column_name){
								if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
									$temp = explode('_',$column_name);
									$element_id = (int) $temp[1];
									$option_id = (int) $temp[2];
									$item_name = '';
									
									if($price_field_types[$column_name] == 'money'){
										$item_name = $price_titles[$element_id][0];
										if(!empty($quantity_fields_info['element_'.$element_id])){
									  		$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
									   	}
										$total_payment_amount += ($row[$column_name] * $quantity);
									}else if($price_field_types[$column_name] == 'checkbox'){
										$item_name = $price_titles[$element_id][$option_id];
										if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
										$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
									}else{ //dropdown or multiple choice
										$option_id = $row[$column_name];
										$item_name = $price_titles[$element_id][$option_id];
										if(!empty($quantity_fields_info['element_'.$element_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
										$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
									}
									if(!empty($item_name)){
										$selected_item_names[] = $item_name;
									}
									$processed_column_name[] = $column_name;
								}
							}
							
							$paypal_params['item_name'] = implode(' - ', $selected_item_names);
							$paypal_params['a3'] = $total_payment_amount;
							
							if(!empty($total_payment_amount) && ($total_payment_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $total_payment_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$total_payment_amount -= $payment_calculated_discount;
									
									$paypal_params['a3'] = $total_payment_amount;
									$paypal_params['item_name'] .= " (-{$la_lang['discount']})";
								}
								
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $total_payment_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['a3'] = $total_payment_amount + $payment_tax_amount;
									$paypal_params['item_name'] .= " (+{$la_lang['tax']} {$payment_tax_rate}%)";
								}
							}
						}//end of variable-recurring payment
						//trial periods
						if(!empty($payment_enable_trial)){
							//set trial price
							if($payment_trial_amount === '0.00'){
								$payment_trial_amount = 0;
							}
							$paypal_params['a1'] = $payment_trial_amount;
							//set trial period
							$paypal_params['p1'] = $payment_trial_period;
							$paypal_params['t1'] = strtoupper($payment_trial_unit[0]);
							//check for limits being set by PayPal
							if($paypal_params['t1'] == 'Y' && $payment_trial_period > 5){
								$paypal_params['p1'] = 5; //max 5 years recurring
							}
						}
					}else{ //non recurring payment
						$paypal_params['cmd'] = '_cart';
						
						if($payment_price_type == 'fixed'){ //this is fixed amount payment
							
							$paypal_params['item_name_1'] = $payment_price_name;
							$paypal_params['amount_1']	  = $payment_price_amount;
							
							if(!empty($payment_price_amount) && ($payment_price_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$payment_price_amount -= $payment_calculated_discount;
									$paypal_params['discount_amount_cart'] = $payment_calculated_discount;
								}
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['tax_cart'] = $payment_tax_amount;
								}
							}
						}else if($payment_price_type == 'variable'){ //this is variable amount payment
							
							//get price fields information from ap_element_prices table
							$query = "select 
											A.element_id,
											A.option_id,
											A.price,
											B.element_title,
											B.element_type,
											(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
										from
											".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
										where
											A.form_id = ?
										order by 
											A.element_id,A.option_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							$price_field_columns = array();
							
							while($row = la_do_fetch_result($sth)){
								$element_id   = (int) $row['element_id'];
								$option_id 	  = (int) $row['option_id'];
								$element_type = $row['element_type'];
								
								if($element_type == 'checkbox'){
									$column_name = 'element_'.$element_id.'_'.$option_id;
								}else{
									$column_name = 'element_'.$element_id;
								}	
								
								if(!in_array($column_name,$price_field_columns)){
									$price_field_columns[] = $column_name;
									$price_field_types[$column_name] = $row['element_type'];
								}
								
								$price_values[$element_id][$option_id] 	 = $row['price'];
								
								if($element_type == 'money'){
									$price_titles[$element_id][$option_id] = $row['element_title'];
								}else{
									$price_titles[$element_id][$option_id] = $row['option_title'];
								}
							}
							$price_field_columns_joined = implode(',',$price_field_columns);
							//get quantity fields
							$quantity_fields_info = array();
							$quantity_field_columns = array();
							
							$query = "select 
									 		element_id,
									 		element_number_quantity_link
										from 
											".LA_TABLE_PREFIX."form_elements 
									   where
									   		form_id = ? and
									   		element_status = 1 and
									   		element_type = 'number' and
									   		element_number_enable_quantity = 1 and
									   		element_number_quantity_link is not null
									group by 
											element_number_quantity_link 
									order by
									   		element_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							while($row = la_do_fetch_result($sth)){
								$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
								$quantity_field_columns[] = 'element_'.$row['element_id'];			
							}
							if(!empty($quantity_fields_info)){
								$quantity_field_columns_joined = implode(',', $quantity_field_columns);
								$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
							}
							
							//check the value of the price fields from the ap_form_x table
							$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
							$params = array($entry_id);
							$sth = la_do_query($query,$params,$dbh);
							$row = la_do_fetch_result($sth);
							
							$i = 1;
							$processed_column_name = array();
							
							foreach ($price_field_columns as $column_name){
								if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
									
									$temp = explode('_',$column_name);
									$element_id = (int) $temp[1];
									$option_id = (int) $temp[2];
									
									$item_name = '';
									$amount = '';
									 
									if($price_field_types[$column_name] == 'money'){
										$item_name = $price_titles[$element_id][0];
									  	$amount 	 = $row[$column_name];
									  	if(!empty($quantity_fields_info['element_'.$element_id])){
									  		$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
									   	}
									}else if($price_field_types[$column_name] == 'checkbox'){
									  	$item_name = $price_titles[$element_id][$option_id];
									  	$amount 	 = $price_values[$element_id][$option_id];
									  	if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
									}else{ //dropdown or multiple choice
									  	$option_id = $row[$column_name];
									  	$item_name = $price_titles[$element_id][$option_id];
									  	$amount 	 = $price_values[$element_id][$option_id];
									  	if(!empty($quantity_fields_info['element_'.$element_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
									}
									 
									$processed_column_name[] = $column_name;
									 
									if(!empty($amount) && ($amount !== '0.00')){
									  $payment_has_value = true;
									 
									  $paypal_params['item_name_'.$i] = $item_name;
									  $paypal_params['amount_'.$i] 	  = $amount;
									  $paypal_params['quantity_'.$i]  = $quantity;
									  $i++;
									}
								}
							}
							
							$payment_price_amount = (double) la_get_payment_total($dbh,$form_id,$entry_id,0,'live');
							//calculate discount if applicable
							if($is_discount_applicable){
								$payment_calculated_discount = 0;
								if($payment_discount_type == 'percent_off'){
									//the discount is percentage
									$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
									$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
								}else{
									//the discount is fixed amount
									$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
								}
								$payment_price_amount -= $payment_calculated_discount;
								$paypal_params['discount_amount_cart'] = $payment_calculated_discount;
							}
							//calculate tax if enabled
							if(!empty($payment_enable_tax) && !empty($payment_tax_rate) && $payment_has_value){
								
								$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
								$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
								$paypal_params['tax_cart'] = $payment_tax_amount;
							}
							
						}//end of non-recurring variable payment
					}//end of non-recurring payment
					
					
					$merchant_redirect_url = 'https://' . $paypal_url.'/cgi-bin/webscr?'.http_build_query($paypal_params,'','&');
					$merchant_redirect_url = str_replace('&curren', '&amp;curren', $merchant_redirect_url);
					$merchant_redirect_url = str_replace('&no', '&amp;no', $merchant_redirect_url);
				}//end paypal standard		
		}
		
		if($payment_has_value){
			return $merchant_redirect_url;
		}else{
			return ''; //if total amount is zero, don't redirect to PayPal
		}
			
	}
	
	function la_get_merchant_redirect_url_two($dbh, $form_id, $entry_id, $company_id){
	
		global $la_lang;
		$la_settings 		   = la_get_settings($dbh);
		
		$la_settings['base_url'] = trim($la_settings['base_url'],'/').'/';
		$merchant_redirect_url 	 = '';
		
		$payment_has_value  = false;
		
		$query 	= "select 
						 payment_enable_merchant,
						 payment_merchant_type,
						 ifnull(payment_paypal_email,'') payment_paypal_email,
						 payment_paypal_language,
						 payment_currency,
						 payment_show_total,
						 payment_total_location,
						 payment_enable_recurring,
						 payment_recurring_cycle,
						 payment_recurring_unit,
						 payment_enable_trial,
						 payment_trial_period,
						 payment_trial_unit,
						 payment_trial_amount,
						 payment_price_type,
						 payment_price_amount,
						 payment_price_name,
						 payment_paypal_enable_test_mode,
						 payment_enable_tax,
						 payment_tax_rate,
						 form_redirect,
						 form_redirect_enable,
						 form_language,
						 payment_enable_discount,
						 payment_discount_type,
						 payment_discount_amount,
						 payment_discount_element_id
				     from 
				     	 `".LA_TABLE_PREFIX."forms` 
				    where 
				    	 form_id=?";
		
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$payment_enable_merchant = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		
		if(!empty($row['form_language'])){
			la_set_language($row['form_language']);
		}
		$payment_merchant_type 	  = $row['payment_merchant_type'];
		$payment_paypal_email 	  = $row['payment_paypal_email'];
		$payment_paypal_language  = $row['payment_paypal_language'];
		
		$payment_currency 		  = $row['payment_currency'];
		$payment_show_total 	  = (int) $row['payment_show_total'];
		$payment_total_location   = $row['payment_total_location'];
		$payment_enable_recurring = (int) $row['payment_enable_recurring'];
		$payment_recurring_cycle  = (int) $row['payment_recurring_cycle'];
		$payment_recurring_unit   = $row['payment_recurring_unit'];
		$form_redirect_enable	  = (int) $row['form_redirect_enable'];
		$form_redirect	  		  = $row['form_redirect'];
		$payment_paypal_enable_test_mode = (int) $row['payment_paypal_enable_test_mode'];
		if(!empty($payment_paypal_enable_test_mode)){
			$paypal_url = "www.sandbox.paypal.com";
		}else{
			$paypal_url = "www.paypal.com";
		}
		$payment_enable_trial = (int) $row['payment_enable_trial'];
		$payment_trial_period = (int) $row['payment_trial_period'];
		$payment_trial_unit   = $row['payment_trial_unit'];
		$payment_trial_amount = $row['payment_trial_amount'];
		
		$payment_price_type   = $row['payment_price_type'];
		$payment_price_amount = (float) $row['payment_price_amount'];
		$payment_price_name   = $row['payment_price_name'];
		$payment_enable_tax = (int) $row['payment_enable_tax'];
		$payment_tax_rate 	= (float) $row['payment_tax_rate'];
		$payment_enable_discount = (int) $row['payment_enable_discount'];
		$payment_discount_type 	 = $row['payment_discount_type'];
		$payment_discount_amount = (float) $row['payment_discount_amount'];
		$payment_discount_element_id = (int) $row['payment_discount_element_id'];
		$is_discount_applicable = false;
		
		//if the discount element for the current entry_id having any value, we can be certain that the discount code has been validated and applicable
		if(!empty($payment_enable_discount) && !empty($payment_enable_merchant)){
			$query = "select element_{$payment_discount_element_id} coupon_element from ".LA_TABLE_PREFIX."form_{$form_id} where `id` = ? and `status` = 1";
			$params = array($entry_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			if(!empty($row['coupon_element'])){
				$is_discount_applicable = true;
			}
		}
				
		if(!empty($payment_enable_merchant)){ //if merchant is enabled
				
				//paypal website payment standard
				if($payment_merchant_type == 'paypal_standard'){
					//get current entry timestamp
					//$query = "select unix_timestamp(date_created) entry_timestamp from ".LA_TABLE_PREFIX."form_{$form_id} where `id` = ? and `status` = 1";
					$query = "select unix_timestamp(data_value) entry_timestamp from ".LA_TABLE_PREFIX."form_{$form_id} where field_name = 'date_created'";
					$params = array($entry_id);
		
					$sth = la_do_query($query,$params,$dbh);
					$row = la_do_fetch_result($sth);
					$entry_timestamp = $row['entry_timestamp'];
					$paypal_params = array();
					
					$paypal_params['charset'] 	    = 'UTF-8';
					$paypal_params['upload']  		= 1;
					$paypal_params['business']      = $payment_paypal_email;
					$paypal_params['currency_code'] = $payment_currency;
					$paypal_params['custom'] 		= $form_id.'_'.$entry_id.'_'.$entry_timestamp.'_'.$company_id;
					$paypal_params['rm'] 			= 2; //the buyer’s browser is redirected to the return URL by using the POST method, and all payment variables are included
					$paypal_params['lc'] 			= $payment_paypal_language;
									
					if(!empty($form_redirect)){
						//$paypal_params['return'] 	= $la_settings['base_url'].'subscribe.php?id='.$form_id;//$form_redirect; 
						//$paypal_params['return'] = 'http://www.panchabati.com/demo_web/portal/client_account.php';
						//$paypal_params['return'] 	= 'https://auditmachine.com/portal/client_account.php';
						$paypal_params['return'] 	= LA_BASE_URL_FOR_PAYPAL.'portal/client_account.php';
					}else{
						//$paypal_params['return'] 	= $la_settings['base_url'].'subscribe.php?id='.$form_id; 
						//$paypal_params['return'] = 'http://www.panchabati.com/demo_web/portal/client_account.php';
						//$paypal_params['return'] 	= 'https://auditmachine.com/portal/client_account.php'; 
						$paypal_params['return'] 	= LA_BASE_URL_FOR_PAYPAL.'portal/client_account.php';
					}
					
					//$paypal_params['notify_url'] 	= $la_settings['base_url'].'paypal_ipn_new.php';
					//$paypal_params['notify_url'] = 'http://www.panchabati.com/demo_web/portal/paypal_ipn_new.php';
					//$paypal_params['notify_url'] 	= 'https://auditmachine.com/portal/paypal_ipn_new.php';
					$paypal_params['notify_url'] 	= LA_BASE_URL_FOR_PAYPAL.'portal/paypal_ipn_new.php';
					
					$paypal_params['no_shipping'] 	= 1;
					
					if(!empty($payment_enable_recurring)){ //this is recurring payment
						$paypal_params['cmd'] = '_xclick-subscriptions';
						$paypal_params['src'] = 1; //subscription payments recur, until user cancel it
						$paypal_params['sra'] = 1; //reattempt failed recurring payments before canceling
						$paypal_params['item_name'] = $payment_price_name;
						$paypal_params['p3'] 		= $payment_recurring_cycle;
						$paypal_params['t3'] 		= strtoupper($payment_recurring_unit[0]);
							
						if($paypal_params['t3'] == 'Y' && $payment_recurring_cycle > 5){
							$paypal_params['p3'] = 5; //paypal can only handle 5-year-period recurring payments, maximum	
						}
								
						if($payment_price_type == 'fixed'){ //this is fixed amount payment	
							$paypal_params['a3'] 		= $payment_price_amount;
							
							if(!empty($payment_price_amount) && ($payment_price_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$payment_price_amount -= $payment_calculated_discount;
									
									$paypal_params['a3'] = $payment_price_amount;
									$paypal_params['item_name'] .= " (-{$la_lang['discount']})";
								}
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['a3'] = $payment_price_amount + $payment_tax_amount;
									$paypal_params['item_name'] .= " (+{$la_lang['tax']} {$payment_tax_rate}%)";
								}
							}
						}else if($payment_price_type == 'variable'){
							
							$total_payment_amount = 0;
							
							//get price fields information from ap_element_prices table
							$query = "select 
											A.element_id,
											A.option_id,
											A.price,
											B.element_title,
											B.element_type,
											(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
										from
											".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
										where
											A.form_id = ?
										order by 
											A.element_id,A.option_id asc";
							
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							$price_field_columns = array();
							
							while($row = la_do_fetch_result($sth)){
								$element_id   = (int) $row['element_id'];
								$option_id 	  = (int) $row['option_id'];
								$element_type = $row['element_type'];
								
								if($element_type == 'checkbox'){
									$column_name = 'element_'.$element_id.'_'.$option_id;
								}else{
									$column_name = 'element_'.$element_id;
								}	
								
								if(!in_array($column_name,$price_field_columns)){
									$price_field_columns[] = $column_name;
									$price_field_types[$column_name] = $row['element_type'];
								}
								
								$price_values[$element_id][$option_id] 	 = $row['price'];
								
								if($element_type == 'money'){
									$price_titles[$element_id][$option_id] = $row['element_title'];
								}else{
									$price_titles[$element_id][$option_id] = $row['option_title'];
								}
							}
							$price_field_columns_joined = implode(',',$price_field_columns);
							//get quantity fields
							$quantity_fields_info = array();
							$quantity_field_columns = array();
							
							$query = "select 
									 		element_id,
									 		element_number_quantity_link
										from 
											".LA_TABLE_PREFIX."form_elements 
									   where
									   		form_id = ? and
									   		element_status = 1 and
									   		element_type = 'number' and
									   		element_number_enable_quantity = 1 and
									   		element_number_quantity_link is not null
									group by 
											element_number_quantity_link 
									order by
									   		element_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							while($row = la_do_fetch_result($sth)){
								$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
								$quantity_field_columns[] = 'element_'.$row['element_id'];			
							}
							if(!empty($quantity_fields_info)){
								$quantity_field_columns_joined = implode(',', $quantity_field_columns);
								$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
							}
							
							//check the value of the price fields from the ap_form_x table
							$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
							$params = array($entry_id);
							$sth = la_do_query($query,$params,$dbh);
							$row = la_do_fetch_result($sth);
							
							$processed_column_name = array();
							$selected_item_names = array();
							
							foreach ($price_field_columns as $column_name){
								if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
									$temp = explode('_',$column_name);
									$element_id = (int) $temp[1];
									$option_id = (int) $temp[2];
									$item_name = '';
									
									if($price_field_types[$column_name] == 'money'){
										$item_name = $price_titles[$element_id][0];
										if(!empty($quantity_fields_info['element_'.$element_id])){
									  		$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
									   	}
										$total_payment_amount += ($row[$column_name] * $quantity);
									}else if($price_field_types[$column_name] == 'checkbox'){
										$item_name = $price_titles[$element_id][$option_id];
										if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
										$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
									}else{ //dropdown or multiple choice
										$option_id = $row[$column_name];
										$item_name = $price_titles[$element_id][$option_id];
										if(!empty($quantity_fields_info['element_'.$element_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
										$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
									}
									if(!empty($item_name)){
										$selected_item_names[] = $item_name;
									}
									$processed_column_name[] = $column_name;
								}
							}
							
							$paypal_params['item_name'] = implode(' - ', $selected_item_names);
							$paypal_params['a3'] = $total_payment_amount;
							
							if(!empty($total_payment_amount) && ($total_payment_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $total_payment_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$total_payment_amount -= $payment_calculated_discount;
									
									$paypal_params['a3'] = $total_payment_amount;
									$paypal_params['item_name'] .= " (-{$la_lang['discount']})";
								}
								
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $total_payment_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['a3'] = $total_payment_amount + $payment_tax_amount;
									$paypal_params['item_name'] .= " (+{$la_lang['tax']} {$payment_tax_rate}%)";
								}
							}
						}//end of variable-recurring payment
						//trial periods
						if(!empty($payment_enable_trial)){
							//set trial price
							if($payment_trial_amount === '0.00'){
								$payment_trial_amount = 0;
							}
							$paypal_params['a1'] = $payment_trial_amount;
							//set trial period
							$paypal_params['p1'] = $payment_trial_period;
							$paypal_params['t1'] = strtoupper($payment_trial_unit[0]);
							//check for limits being set by PayPal
							if($paypal_params['t1'] == 'Y' && $payment_trial_period > 5){
								$paypal_params['p1'] = 5; //max 5 years recurring
							}
						}
					}else{ //non recurring payment
						$paypal_params['cmd'] = '_cart';
						
						if($payment_price_type == 'fixed'){ //this is fixed amount payment
							
							$paypal_params['item_name_1'] = $payment_price_name;
							$paypal_params['amount_1']	  = $payment_price_amount;
							
							if(!empty($payment_price_amount) && ($payment_price_amount !== '0.00')){
								$payment_has_value = true;
								//calculate discount if applicable
								if($is_discount_applicable){
									$payment_calculated_discount = 0;
									if($payment_discount_type == 'percent_off'){
										//the discount is percentage
										$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
										$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
									}else{
										//the discount is fixed amount
										$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
									}
									$payment_price_amount -= $payment_calculated_discount;
									$paypal_params['discount_amount_cart'] = $payment_calculated_discount;
								}
								//calculate tax if enabled
								if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
									$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
									$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
									$paypal_params['tax_cart'] = $payment_tax_amount;
								}
							}
						}else if($payment_price_type == 'variable'){ //this is variable amount payment
							
							//get price fields information from ap_element_prices table
							$query = "select 
											A.element_id,
											A.option_id,
											A.price,
											B.element_title,
											B.element_type,
											(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
										from
											".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
										where
											A.form_id = ?
										order by 
											A.element_id,A.option_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							$price_field_columns = array();
							
							while($row = la_do_fetch_result($sth)){
								$element_id   = (int) $row['element_id'];
								$option_id 	  = (int) $row['option_id'];
								$element_type = $row['element_type'];
								
								if($element_type == 'checkbox'){
									$column_name = 'element_'.$element_id.'_'.$option_id;
								}else{
									$column_name = 'element_'.$element_id;
								}	
								
								if(!in_array($column_name,$price_field_columns)){
									$price_field_columns[] = $column_name;
									$price_field_types[$column_name] = $row['element_type'];
								}
								
								$price_values[$element_id][$option_id] 	 = $row['price'];
								
								if($element_type == 'money'){
									$price_titles[$element_id][$option_id] = $row['element_title'];
								}else{
									$price_titles[$element_id][$option_id] = $row['option_title'];
								}
							}
							$price_field_columns_joined = implode(',',$price_field_columns);
							//get quantity fields
							$quantity_fields_info = array();
							$quantity_field_columns = array();
							
							$query = "select 
									 		element_id,
									 		element_number_quantity_link
										from 
											".LA_TABLE_PREFIX."form_elements 
									   where
									   		form_id = ? and
									   		element_status = 1 and
									   		element_type = 'number' and
									   		element_number_enable_quantity = 1 and
									   		element_number_quantity_link is not null
									group by 
											element_number_quantity_link 
									order by
									   		element_id asc";
							$params = array($form_id);
							$sth = la_do_query($query,$params,$dbh);
							
							while($row = la_do_fetch_result($sth)){
								$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
								$quantity_field_columns[] = 'element_'.$row['element_id'];			
							}
							if(!empty($quantity_fields_info)){
								$quantity_field_columns_joined = implode(',', $quantity_field_columns);
								$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
							}
							
							//check the value of the price fields from the ap_form_x table
							$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
							$params = array($entry_id);
							$sth = la_do_query($query,$params,$dbh);
							$row = la_do_fetch_result($sth);
							
							$i = 1;
							$processed_column_name = array();
							
							foreach ($price_field_columns as $column_name){
								if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
									
									$temp = explode('_',$column_name);
									$element_id = (int) $temp[1];
									$option_id = (int) $temp[2];
									
									$item_name = '';
									$amount = '';
									 
									if($price_field_types[$column_name] == 'money'){
										$item_name = $price_titles[$element_id][0];
									  	$amount 	 = $row[$column_name];
									  	if(!empty($quantity_fields_info['element_'.$element_id])){
									  		$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
									   	}
									}else if($price_field_types[$column_name] == 'checkbox'){
									  	$item_name = $price_titles[$element_id][$option_id];
									  	$amount 	 = $price_values[$element_id][$option_id];
									  	if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
									}else{ //dropdown or multiple choice
									  	$option_id = $row[$column_name];
									  	$item_name = $price_titles[$element_id][$option_id];
									  	$amount 	 = $price_values[$element_id][$option_id];
									  	if(!empty($quantity_fields_info['element_'.$element_id])){
											$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
											if(empty($quantity) || $quantity < 0){
												$quantity = 0;
											}
										}else{
											$quantity = 1;
										}
									}
									 
									$processed_column_name[] = $column_name;
									 
									if(!empty($amount) && ($amount !== '0.00')){
									  $payment_has_value = true;
									 
									  $paypal_params['item_name_'.$i] = $item_name;
									  $paypal_params['amount_'.$i] 	  = $amount;
									  $paypal_params['quantity_'.$i]  = $quantity;
									  $i++;
									}
								}
							}
							
							$payment_price_amount = (double) la_get_payment_total($dbh,$form_id,$entry_id,0,'live');
							//calculate discount if applicable
							if($is_discount_applicable){
								$payment_calculated_discount = 0;
								if($payment_discount_type == 'percent_off'){
									//the discount is percentage
									$payment_calculated_discount = ($payment_discount_amount / 100) * $payment_price_amount;
									$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
								}else{
									//the discount is fixed amount
									$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
								}
								$payment_price_amount -= $payment_calculated_discount;
								$paypal_params['discount_amount_cart'] = $payment_calculated_discount;
							}
							//calculate tax if enabled
							if(!empty($payment_enable_tax) && !empty($payment_tax_rate) && $payment_has_value){
								
								$payment_tax_amount = ($payment_tax_rate / 100) * $payment_price_amount;
								$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal
								$paypal_params['tax_cart'] = $payment_tax_amount;
							}
							
						}//end of non-recurring variable payment
					}//end of non-recurring payment
					
					
					$merchant_redirect_url = 'https://' . $paypal_url.'/cgi-bin/webscr?'.http_build_query($paypal_params,'','&');
					$merchant_redirect_url = str_replace('&curren', '&amp;curren', $merchant_redirect_url);
					$merchant_redirect_url = str_replace('&no', '&amp;no', $merchant_redirect_url);
				}//end paypal standard		
		}
		
		if($payment_has_value){
			return $merchant_redirect_url;
		}else{
			return ''; //if total amount is zero, don't redirect to PayPal
		}
			
	}
	
	//return true if a payment-enabled form is being submitted and has value (not zero)
	//currently this is only being used for stripe, authorize.net, braintree and paypal pro
	function la_is_payment_has_value($dbh,$form_id,$entry_id){
		
		$payment_has_value = false;
		$props = array('payment_enable_merchant',
					   'payment_merchant_type',
					   'payment_price_amount',
					   'payment_price_type',
					   'payment_delay_notifications',
					   'form_review',
					   'form_page_total');
		$form_properties = la_get_form_properties($dbh,$form_id,$props);
		if(($form_properties['payment_enable_merchant'] == 1) && in_array($form_properties['payment_merchant_type'], array('stripe','authorizenet','paypal_rest','braintree'))){
			if($form_properties['payment_price_type'] == 'variable'){
				
				$total_payment_amount = (double) la_get_payment_total($dbh,$form_id,$entry_id,0,'live');
				
				if(!empty($total_payment_amount)){
					$payment_has_value = true;
				}
			}else if($form_properties['payment_price_type'] == 'fixed'){
				$total_payment_amount = (double) $form_properties['payment_price_amount'];
				if(!empty($total_payment_amount)){
					$payment_has_value = true;
				}
			}
		}
		return $payment_has_value;
	}
	//get the total payment of a submission from ap_form_x_review or ap_form_x table
	//this function doesn't include tax calculation
	function la_get_payment_total($dbh,$form_id,$record_id,$exclude_page_number,$target_table='review'){
		
		$form_id = (int) $form_id;
		$total_payment_amount = 0;
							
		//get price fields information from ap_element_prices table
		$query = "select 
						A.element_id,
						A.option_id,
						A.price,
						B.element_title,
						B.element_type,
						(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
					from
						".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
				   where
						A.form_id = ? and B.element_page_number <> ?
				order by 
						A.element_id,A.option_id asc";
		
		$params = array($form_id,$exclude_page_number);
		$sth = la_do_query($query,$params,$dbh);
							
		$price_field_columns = array();
							
		while($row = la_do_fetch_result($sth)){
			$element_id   = (int) $row['element_id'];
			$option_id 	  = (int) $row['option_id'];
			$element_type = $row['element_type'];
								
			if($element_type == 'checkbox'){
				$column_name = 'element_'.$element_id.'_'.$option_id;
			}else{
				$column_name = 'element_'.$element_id;
			}	
								
			if(!in_array($column_name,$price_field_columns)){
				$price_field_columns[] = $column_name;
				$price_field_types[$column_name] = $row['element_type'];
			}
								
			$price_values[$element_id][$option_id] 	 = $row['price'];						
		}
		if(empty($price_field_columns)){
			return 0;
		}
		$price_field_columns_joined = implode(',',$price_field_columns);
		//get quantity fields
		$quantity_fields_info = array();
		$quantity_field_columns = array();
		
		$query = "select 
				 		element_id,
				 		element_number_quantity_link
					from 
						".LA_TABLE_PREFIX."form_elements 
				   where
				   		form_id = ? and
				   		element_status = 1 and
				   		element_type = 'number' and
				   		element_number_enable_quantity = 1 and
				   		element_number_quantity_link is not null
				group by 
						element_number_quantity_link 
				order by
				   		element_id asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
			$quantity_field_columns[] = 'element_'.$row['element_id'];			
		}
		if(!empty($quantity_fields_info)){
			$quantity_field_columns_joined = implode(',', $quantity_field_columns);
			$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
		}
						
		//check the value of the price fields from the ap_form_x_review or ap_form_x table
		if($target_table == 'review'){
			$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id}_review where `session_id`=?";
		}else{
			$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
		}
		$params = array($record_id);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
							
		$processed_column_name = array();
						
		foreach ($price_field_columns as $column_name){
			if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
				$temp = explode('_',$column_name);
				$element_id = (int) $temp[1];
				$option_id = (int) $temp[2];
				
				if($price_field_types[$column_name] == 'money'){
					if(!empty($quantity_fields_info['element_'.$element_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
						
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$total_payment_amount += ($row[$column_name] * $quantity);
				}else if($price_field_types[$column_name] == 'checkbox'){
					
					if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
						
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
				}else{ //dropdown or multiple choice
					
					if(!empty($quantity_fields_info['element_'.$element_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
						
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$option_id = $row[$column_name];
					$total_payment_amount += ($price_values[$element_id][$option_id] * $quantity);
				}
				$processed_column_name[] = $column_name;
			}
		}
						
		return $total_payment_amount;
	}
	//get a list/array of all items to be paid within a form
	function la_get_payment_items($dbh,$form_id,$record_id,$target_table='review'){
		
		$payment_items = array();
		$form_id = (int) $form_id;
		//get price fields information from ap_element_prices table
		$query = "select 
						A.element_id,
						A.option_id,
						A.price,
						B.element_title,
						B.element_type,
						(select `option` from ".LA_TABLE_PREFIX."element_options where form_id=A.form_id and element_id=A.element_id and option_id=A.option_id and live=1 limit 1) option_title
					from
						".LA_TABLE_PREFIX."element_prices A left join ".LA_TABLE_PREFIX."form_elements B on (A.form_id=B.form_id and A.element_id=B.element_id)
				   where
						A.form_id = ? 
				order by 
						B.element_position,A.option_id asc";
		
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
							
		$price_field_columns = array();
							
		while($row = la_do_fetch_result($sth)){
			$element_id   = (int) $row['element_id'];
			$option_id 	  = (int) $row['option_id'];
			$element_type = $row['element_type'];
								
			if($element_type == 'checkbox'){
				$column_name = 'element_'.$element_id.'_'.$option_id;
			}else{
				$column_name = 'element_'.$element_id;
			}	
								
			if(!in_array($column_name,$price_field_columns)){
				$price_field_columns[] = $column_name;
				$price_field_types[$column_name] = $row['element_type'];
			}
								
			$price_values[$element_id][$option_id] 	 = $row['price'];						
		}
		if(empty($price_field_columns)){
			return false;
		}
		$price_field_columns_joined = implode(',',$price_field_columns);
		
		//get quantity fields
		$quantity_fields_info = array();
		$quantity_field_columns = array();
							
		$query = "select 
				 		element_id,
				 		element_number_quantity_link
					from 
						".LA_TABLE_PREFIX."form_elements 
				   where
				   		form_id = ? and
				   		element_status = 1 and
				   		element_type = 'number' and
				   		element_number_enable_quantity = 1 and
				   		element_number_quantity_link is not null
				group by 
						element_number_quantity_link 
				order by
				   		element_id asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$quantity_fields_info[$row['element_number_quantity_link']] = $row['element_id'];
			$quantity_field_columns[] = 'element_'.$row['element_id'];			
		}
		if(!empty($quantity_fields_info)){
			$quantity_field_columns_joined = implode(',', $quantity_field_columns);
			$price_field_columns_joined = $price_field_columns_joined.','.$quantity_field_columns_joined;
		}
		//get price-ready fields for this form and put them into array
		//price-ready fields are the following types: price, checkboxes, multiple choice, dropdown
		$query = "select 
						element_title,
						element_id,
						element_type 
					from 
						".LA_TABLE_PREFIX."form_elements 
				   where 
				   		form_id=? and 
				   		element_status=1 and 
				   		element_is_private=0 and 
				   		element_type in('radio','money','select','checkbox') 
			    order by 
			    		element_position asc";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$price_field_array = array();
		$price_field_options_lookup = array();
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$price_field_array[$element_id]['element_title'] = htmlspecialchars(strip_tags($row['element_title']));
			$price_field_array[$element_id]['element_type'] = $row['element_type'];
			if($row['element_type'] != 'money'){
				//get the choices for the field
				$sub_query = "select 
									option_id,
									`option` 
								from 
									".LA_TABLE_PREFIX."element_options 
							   where 
							   		form_id=? and 
							   		live=1 and 
							   		element_id=? 
							order by 
									`position` asc";
				$sub_params = array($form_id,$element_id);
				$sub_sth = la_do_query($sub_query,$sub_params,$dbh);
				$i=0;
				while($sub_row = la_do_fetch_result($sub_sth)){
					$price_field_options_lookup[$element_id][$sub_row['option_id']] = htmlspecialchars($sub_row['option']);
					$i++;
				}
				
			}
		}
		//check the value of the price fields from the ap_form_x_review or ap_form_x table
		if($target_table == 'review'){
			$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id}_review where `session_id`=?";
		}else{
			$query = "select {$price_field_columns_joined} from ".LA_TABLE_PREFIX."form_{$form_id} where `id`=?";
		}
		$params = array($record_id);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
							
		$processed_column_name = array();
		
		$i=0;	
		foreach ($price_field_columns as $column_name){
			if(!empty($row[$column_name]) && !in_array($column_name,$processed_column_name)){
				$temp = explode('_',$column_name);
				$element_id = (int) $temp[1];
				$option_id = (int) $temp[2];
									
				if($price_field_types[$column_name] == 'money'){
					$payment_items[$i]['type']   = 'money';
					$payment_items[$i]['amount'] = $row[$column_name];
					$payment_items[$i]['title']  = $price_field_array[$element_id]['element_title'];
					if(!empty($quantity_fields_info['element_'.$element_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
											
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$payment_items[$i]['quantity'] = $quantity;
				}else if($price_field_types[$column_name] == 'checkbox'){
					$payment_items[$i]['type']   = 'checkbox';
					$payment_items[$i]['amount'] = $price_values[$element_id][$option_id];
					$payment_items[$i]['title']  = $price_field_array[$element_id]['element_title'];
					$payment_items[$i]['sub_title'] = $price_field_options_lookup[$element_id][$option_id];
					if(!empty($quantity_fields_info['element_'.$element_id.'_'.$option_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id.'_'.$option_id]];
						
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$payment_items[$i]['quantity'] = $quantity;
				}else if($price_field_types[$column_name] == 'radio' || $price_field_types[$column_name] == 'select'){ //this is dropdown or multiple choice
					$option_id = $row[$column_name];
					$payment_items[$i]['type']   = $price_field_types[$column_name];
					$payment_items[$i]['amount'] = $price_values[$element_id][$option_id];
					$payment_items[$i]['title']  = $price_field_array[$element_id]['element_title'];
					$payment_items[$i]['sub_title'] = $price_field_options_lookup[$element_id][$option_id];
					if(!empty($quantity_fields_info['element_'.$element_id])){
						$quantity = $row['element_'.$quantity_fields_info['element_'.$element_id]];
						
						if(empty($quantity) || $quantity < 0){
							$quantity = 0;
						}
					}else{
						$quantity = 1;
					}
					$payment_items[$i]['quantity'] = $quantity;
				}
				$processed_column_name[] = $column_name;
				$i++;
			}
		}
						
		return $payment_items;
	}
	//get the "hidden" status of all fields within a form page, depend on the conditions for each field
	function la_get_hidden_elements($dbh,$form_id,$page_number,$user_input){
		//get all fields within current page which has has conditions
		$query = "SELECT 
						A.element_id 
					FROM 
						".LA_TABLE_PREFIX."form_elements A LEFT JOIN ".LA_TABLE_PREFIX."field_logic_elements B 
					  ON 
					  	A.form_id=B.form_id and A.element_id=B.element_id
				   WHERE 
				   		A.form_id = ? and 
				   		A.element_status = 1 and 
				   		A.element_page_number = ? and 				   		
				   		B.element_id is not null
				ORDER BY 
						A.element_position asc";
		$params = array($form_id,$page_number);
		$sth = la_do_query($query,$params,$dbh);
		
		$required_fields_array = array();
		while($row = la_do_fetch_result($sth)){
			$required_fields_array[] = $row['element_id'];
		}
		$hidden_elements_status = array();
		//loop through each field and check for the conditions
		if(!empty($required_fields_array)){
			foreach ($required_fields_array as $element_id) {
				$current_element_conditions_status = array();
				$query = "select rule_show_hide,rule_all_any from ".LA_TABLE_PREFIX."field_logic_elements where form_id = ? and element_id = ?";
				$params = array($form_id,$element_id);
				$sth = la_do_query($query,$params,$dbh);
				$row = la_do_fetch_result($sth);
				$rule_show_hide = $row['rule_show_hide'];
				$rule_all_any	= $row['rule_all_any'];
				//get all conditions for current field
				$query = "SELECT l.target_element_id, l.element_name, l.rule_condition, l.rule_keyword, e.element_id AS condition_element_id, e.element_page_number AS condition_element_page_number, e.element_type AS condition_element_type FROM ".LA_TABLE_PREFIX."field_logic_conditions AS l LEFT JOIN ".LA_TABLE_PREFIX."form_elements AS e ON l.element_name = CONCAT('element_', e.element_id) AND l.form_id = e.form_id WHERE l.form_id = ? AND l.target_element_id = ?";
				$params = array($form_id,$element_id);
				$sth = la_do_query($query,$params,$dbh);
				
				$i=0;
				$logic_conditions_array = array();
				while($row = la_do_fetch_result($sth)){
					$logic_conditions_array[$i]['element_name']   = $row['element_name'];
					$logic_conditions_array[$i]['element_type']   = $row['condition_element_type'];
					$logic_conditions_array[$i]['rule_condition'] = $row['rule_condition'];
					$logic_conditions_array[$i]['rule_keyword']   = $row['rule_keyword'];
					$logic_conditions_array[$i]['element_page_number'] 	= (int) $row['condition_element_page_number'];
					$i++;
				}
				//loop through each condition which is not coming from the current page
				foreach ($logic_conditions_array as $value) {
					
					if($value['element_page_number'] == $page_number){
						continue;
					}
					$condition_params = array();
					$condition_params['form_id']		= $form_id;
					$condition_params['element_name'] 	= $value['element_name'];
					$condition_params['rule_condition'] = $value['rule_condition'];
					$condition_params['rule_keyword'] 	= $value['rule_keyword'];
					$current_element_conditions_status[] = la_get_condition_status_from_table($dbh,$condition_params);
				}
				//loop through each condition which is coming from the current page
				foreach ($logic_conditions_array as $value) {
					
					if($value['element_page_number'] != $page_number){
						continue;
					}
					$condition_params = array();
					$condition_params['form_id']		= $form_id;
					$condition_params['element_name'] 	= $value['element_name'];
					$condition_params['rule_condition'] = $value['rule_condition'];
					$condition_params['rule_keyword'] 	= $value['rule_keyword'];
					$current_element_conditions_status[] = la_get_condition_status_from_input($dbh,$condition_params,$user_input);
				}
				//decide the status of the current element_id based on all conditions
				//any field which is hidden due to conditions, should have 'is_hidden' being set to 1
				if($rule_all_any == 'all'){
					if(in_array(false, $current_element_conditions_status)){
						$all_conditions_status = false;
					}else{
						$all_conditions_status = true;
					}
				}else if($rule_all_any == 'any'){
					if(in_array(true, $current_element_conditions_status)){
						$all_conditions_status = true;
					}else{
						$all_conditions_status = false;
					}
				}
				if($rule_show_hide == 'show'){
					if($all_conditions_status === true){
						$element_status = true; //show
					}else{
						$element_status = false; //hide
					}
				}else if($rule_show_hide == 'hide'){
					if($all_conditions_status === true){
						$element_status = false; //hide
					}else{
						$element_status = true; //show
					}
				}
				if($element_status === true){
					$hidden_elements_status[$element_id] = 0; //the field is not hidden
				}else{
					$hidden_elements_status[$element_id] = 1; //the field is hidden
				}
			} //end foreach required fields	
		}
		return $hidden_elements_status;
	}