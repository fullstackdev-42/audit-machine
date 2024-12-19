<?php
@ini_set("max_execution_time",1800);
@ini_set("max_input_time",1200);

require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/common-validator.php');
require('includes/filter-functions.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$user_id = (int) $_SESSION['la_user_id'];
$company_id = time();

function insertDocxInfo($params=array()) {
	$dbh = $params['dbh'];
	$query = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `docx_create_date`, `docxname`, `isZip`) VALUES (NULL, :client_id, :company_id, :form_id, :docx_create_date, :docxname, :isZip);";
	la_do_query($query,array(':client_id' => $params['client_id'], ':company_id' => $params['company_id'], ':form_id' => $params['form_id'], ':docx_create_date' => $params['docx_create_date'], ':docxname' => $params['docxname'], ':isZip' => $params['isZip']), $dbh);
}

function getNoOfQueryToInsert($params=array()) {
	$queries = array();
	$values  = array();
	
	$dbh = $params['dbh'];
	$form_id = $params['form_id'];
	$company_id = $params['company_id'];
	$elements = $params['elements'];
	$data_array = $params['data_array'];
	
	/*print_r($data_array);
	print_r($elements);
	die;*/
	
	unset($data_array[0]);
	unset($data_array[1]);
	unset($data_array[2]);
	
	$data_array = array_values($data_array);
	
	foreach($elements['column_prefs'] as $k => $v){
		array_push($queries, "(NULL, ?, ?, ?, ?, ?, ?, ?)");
		$e = explode("_", $v);
		
		if(in_array($elements['column_types'][$k], array('phone'))){
			$data_array[$k] = str_replace(array("(", ")", " "), array("", "", ""), $data_array[$k]);
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", str_replace("\"", "", $data_array[$k]), "", "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('address'))){
			$option_value = $e[2] == 6 ? str_replace("\"", "", $data_array[$k]) : "";
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", str_replace("\"", "", $data_array[$k]), $option_value, "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('checkbox'))){
			$data_array[$k] = str_replace("\"", "", $data_array[$k]);
			$data_array[$k] = trim($data_array[$k]) == "Checked" ? 1 : trim($data_array[$k]);
			$option_value = $data_array[$k] == 1 ? $elements['column_option'][$e[1]][$e[2]]['option_value'] : "0.0";
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", $data_array[$k], $option_value, "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('radio', 'select'))){
			$data_array[$k] = str_replace("\"", "", $data_array[$k]);
			$option_value = "0.0";
			if(isset($elements['column_option'][$e[1]])){
				foreach($elements['column_option'][$e[1]] as $ko => $vo){
					if(isset($vo['option']) && strtolower(trim($vo['option'])) == strtolower(trim($data_array[$k]))){
						$data_array[$k] = $ko;
						$option_value = $vo['option_value'];
					}
				}
			}
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", str_replace("\"", "", $data_array[$k]), $option_value, "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('matrix_radio'))){
			$option_value = "0.0";
			$data_array[$k] = str_replace("\"", "", $data_array[$k]);			
			$m_element_id = $elements['matrix_element_parent'][$e[1]];						
			foreach($elements['column_option'][$m_element_id] as $ko => $vo){
				if(strtolower(trim($vo['option'])) == strtolower(trim($data_array[$k]))){
					$data_array[$k] = $ko;
					$option_value = $vo['option_value'];
				}
			}
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", $data_array[$k], $option_value, "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('matrix_checkbox'))){
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", str_replace("\"", "", $data_array[$k]), "", "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array('signature'))){
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", base64_decode(str_replace("\"", "", $data_array[$k])), "", "0", md5($company_id."_".$v)));
		}elseif(in_array($elements['column_types'][$k], array("payment_amount", "payment_status", "payment_id"))){
			
		}else{
			$values = array_merge($values, array($company_id, "{$v}", "code_{$e[1]}", str_replace("\"", "", $data_array[$k]), "", "0", md5($company_id."_".$v)));
		}
	}
	
	//print_r($elements);
	//die;
	
	// ip_address, date_created and status
	array_push($queries, "(NULL, ?, ?, ?, ?, ?, ?, ?)");
	array_push($queries, "(NULL, ?, ?, ?, ?, ?, ?, ?)");
	array_push($queries, "(NULL, ?, ?, ?, ?, ?, ?, ?)");
	
	$values = array_merge($values, array($company_id, 'ip_address', '', $_SERVER['REMOTE_ADDR'], '', '0', md5($company_id."_ip_address")));
	$values = array_merge($values, array($company_id, 'date_created', '', date('Y-m-d H:i:s'), '', '0', md5($company_id."_date_created")));
	$values = array_merge($values, array($company_id, 'status', '', 1, '', '0', md5($company_id."_status")));
	
	return array('queries' => $queries, 'values' => $values);
}

function getData($dbh, $form_id, $params=array(), $elements) {
	$insert_data = array();
	$columnDataArr = array();
	
	$data_file 	 = $params['data_file']; 
	$email_file  = $params['email_file']; // Name of your CSV file with path
	$delimitter	 = $params['delimitter']; 
	$docxArray	 = $params['docxArray'];  
	$dataFile  	 = fopen($data_file, 'r');
	$columnData  = fgets($dataFile);
	$columnMatched = true;
	
	$email_json = json_decode(file_get_contents($email_file), true);
	
	if(strpos($columnData, $delimitter) !== false){	
		$columnDataArr = explode($delimitter, $columnData);
	}
	
	unset($columnDataArr[0]);
	unset($columnDataArr[1]);
	unset($columnDataArr[2]);
	
	$payment = false;
	
	foreach(array("payment_amount", "payment_status", "payment_id") as $kd => $vd){
		if($k = array_search($vd, $columnDataArr)){
			$payment = true;
			unset($columnDataArr[$k]);
		}
	}
	
	$columnDataArr = array_values($columnDataArr);
	
	if(count($elements['column_prefs']) == count($columnDataArr)){
		foreach($elements['column_prefs'] as $kc => $vc){
			if(trim($columnDataArr[$kc]) != trim($vc)){
				$columnMatched = false;
			}
		}
		
		if(!$columnMatched){
			return array('status' => 'ERROR_3');
		}
	}else{
		return array('status' => 'ERROR_2');
	}
	
	// check whether column exists or not
	$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$form_id}' AND `COLUMN_NAME` = 'unique_row_data'";		
	$sth = la_do_query($query,array(),$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!$row['COLUMN_NAME']){
		$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD `unique_row_data` VARCHAR(64) NOT NULL , ADD UNIQUE (`unique_row_data`);";
		la_do_query($query,array(),$dbh);
	}
		
	$query   	 = "REPLACE INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `field_name`, `field_code`, `data_value`, `field_score`, `form_resume_enable`, `unique_row_data`) VALUES ";
	$values		 = array();
	$queries 	 = array();

	$i = 1;
	
	while(!feof($dataFile)){
		$_data = fgets($dataFile);
		//echo $_data;
		//$d = str_getcsv($_data, ",");
		//print_r($d);die;
		
		//$_data = str_replace(array("\""), array(""), $_data);		
	   
	   $_data_array  = array();
	   
	   if(strpos($_data, $delimitter) !== false){		   
		   // trying to genearate unique random number
		   $microtime    = microtime();
		   $microtimeAr  = explode(".", $microtime);
		   $microtimeAr  = explode(" ", $microtimeAr[1]);
		   $company_id	 = $microtimeAr[1] + $microtimeAr[0] + $i;
		   
		   if(isset($email_json[($i-1)]) && $email_json[($i-1)]['client_email'] != "ADMINISTRATOR"){
			   $company_query = "SELECT `client_id` FROM `ap_ask_clients` WHERE `contact_email` = ?";
			   $company_sth = la_do_query($company_query, array($email_json[($i-1)]['client_email']), $dbh);
			   if($company_row = la_do_fetch_result($company_sth)){
				   $company_id = $company_row['client_id'];
			   }
		   }		   
		   
		   $validArray   = false;
	   	   $_data_array  = str_getcsv($_data, $delimitter);
		   $tmp_arr_data = array();
		   
		   if($payment){
			   unset($_data_array[3]);
			   unset($_data_array[4]);
			   unset($_data_array[5]);
		   }
		   
		   foreach($_data_array as $kn => $vn){
			   $tmp_arr_data[$kn] = str_replace("_NO_DATA_", "", $vn);
		   }
		   
		   $_data_array = $tmp_arr_data;
		   unset($tmp_arr_data);
		   
		   if(count($_data_array)){
			   foreach($_data_array as $v){
				   $tmpVal = trim($tmpVal);
				   $tmpVal = str_replace(array("\"", " ", "-", ")", "("), array("", "", "", "", ""), $v);				   
				   
				   if(!empty($tmpVal)){
					   $validArray = true;
				   }
			   }
		   }
		   
		   if(!$validArray){
			   continue;   
		   }
		   
		   $queries_with_values = getNoOfQueryToInsert(array('dbh' => $dbh, 'form_id' => $form_id, 'company_id' => $company_id, 'elements' => $elements, 'data_array' => $_data_array));
		   $values  = array_merge($values, $queries_with_values['values']);
		   $queries = array_merge($queries, $queries_with_values['queries']);
		   
		   // add docx info to new entries with admin id		   
		   if(isset($docxArray[($i-1)])){
			   insertDocxInfo(array('dbh' => $dbh, 'client_id' => $_SESSION['la_user_id'], 'company_id' => $company_id, 'form_id' => $form_id, 'docx_create_date' => time(), 'docxname' => $docxArray[($i-1)], 'isZip' => 1));
		   }
		   
		   // delay execution for half seconds
	   	   $i++;
	   }
	   
	}
	
	$query .= implode(",", $queries);
	$result = la_do_query($query,$values,$dbh);

	fclose($dataFile);	
	return array('status' => 'Import');
}

function getColumnAndCodeName($params=array()) {
	$dbh = $params['dbh'];
	$form_id = $params['form_id'];
	
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
	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
		
	while($row = la_do_fetch_result($sth)){
		$element_option_lookup[$row['element_id']][$row['option_id']] = array('option' => $row['option'], 'option_value' => $row['option_value']);
	}
	
	$matrix_element_parent = array();
	
	$query  = "select 
					 element_id,
					 element_title,
					 element_type,
					 element_constraint,
					 element_choice_has_other,
					 element_choice_other_label,
					 element_time_showsecond,
					 element_time_24hour,
					 element_matrix_allow_multiselect,
					 element_matrix_parent_id  
			     from 
			         `".LA_TABLE_PREFIX."form_elements` 
			    where 
			    	 form_id=? and element_status=1 and element_type not in('section','page_break','casecade_form')
			 order by 
			 		 element_position asc";
	$sth = la_do_query($query,array($form_id),$dbh);
	$element_radio_has_other = array();

	while($row = la_do_fetch_result($sth)){
		$element_type 	    = $row['element_type'];
		$element_constraint = $row['element_constraint'];			

		//get 'other' field label for checkboxes and radio button
		if($element_type == 'checkbox' || $element_type == 'radio'){
			if(!empty($row['element_choice_has_other'])){
				$element_option_lookup[$row['element_id']]['other'] = $row['element_choice_other_label'];
				
				if($element_type == 'radio'){
					$element_radio_has_other['element_'.$row['element_id']] = true;	
				}
			}
		}

		if('address' == $element_type){ //address has 6 fields
			$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - Street Address';
			$column_name_lookup['element_'.$row['element_id'].'_2'] = 'Address Line 2';
			$column_name_lookup['element_'.$row['element_id'].'_3'] = 'City';
			$column_name_lookup['element_'.$row['element_id'].'_4'] = 'State/Province/Region';
			$column_name_lookup['element_'.$row['element_id'].'_5'] = 'Zip/Postal Code';
			$column_name_lookup['element_'.$row['element_id'].'_6'] = 'Country';
				
			$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_4'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_5'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_6'] = $row['element_type'];
				
		}elseif ('simple_name' == $element_type){ //simple name has 2 fields
			$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
			$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Last';
				
			$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				
		}elseif ('simple_name_wmiddle' == $element_type){ //simple name with middle has 3 fields
			$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
			$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Middle';
			$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
				
			$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
				
		}elseif ('name' == $element_type){ //name has 4 fields
			$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - Title';
			$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - First';
			$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
			$column_name_lookup['element_'.$row['element_id'].'_4'] = $row['element_title'].' - Suffix';
				
			$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_4'] = $row['element_type'];
				
		}elseif ('name_wmiddle' == $element_type){ //name with middle has 5 fields
			$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - Title';
			$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - First';
			$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Middle';
			$column_name_lookup['element_'.$row['element_id'].'_4'] = $row['element_title'].' - Last';
			$column_name_lookup['element_'.$row['element_id'].'_5'] = $row['element_title'].' - Suffix';
				
			$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_4'] = $row['element_type'];
			$column_type_lookup['element_'.$row['element_id'].'_5'] = $row['element_type'];
				
		}elseif('money' == $element_type){//money format
			$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
			if(!empty($element_constraint)){
				$column_type_lookup['element_'.$row['element_id']] = 'money_'.$element_constraint; //euro, pound, yen,etc
			}else{
				$column_type_lookup['element_'.$row['element_id']] = 'money_dollar'; //default is dollar
			}
		}elseif('checkbox' == $element_type){ //checkboxes, get childs elements							
			$this_checkbox_options = $element_option_lookup[$row['element_id']];
				
			foreach ($this_checkbox_options as $option_id => $option){
				if(isset($option['option'])){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option['option'];
				}else{
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
				}
				$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = $row['element_type'];
			}
		}elseif ('time' == $element_type){				
			if(!empty($row['element_time_showsecond']) && !empty($row['element_time_24hour'])){
				$column_type_lookup['element_'.$row['element_id']] = 'time_24hour';
			}else if(!empty($row['element_time_showsecond'])){
				$column_type_lookup['element_'.$row['element_id']] = 'time';
			}else if(!empty($row['element_time_24hour'])){
				$column_type_lookup['element_'.$row['element_id']] = 'time_24hour_noseconds';
			}else{
				$column_type_lookup['element_'.$row['element_id']] = 'time_noseconds';
			}
				
			$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
		}else if('matrix' == $element_type){ 
			$matrix_element_parent[$row['element_id']] = !$row['element_matrix_parent_id'] ? $row['element_id'] : $row['element_matrix_parent_id'];
						
			if(empty($matrix_multiselect_status[$row['element_id']])){
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				$column_type_lookup['element_'.$row['element_id']] = 'matrix_radio';
			}else{
				$this_checkbox_options = $matrix_element_option_lookup[$row['element_id']];
					
				foreach ($this_checkbox_options as $option_id=>$option){
					$option = $option.' - '.$row['element_title'];
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
					$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = 'matrix_checkbox';
				}
			}
		}else{ //for other elements with only 1 field
			$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
			$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
		}
		
	}
			
	//display all columns
	$column_prefs = array_keys($column_name_lookup);
	$column_types = array_values($column_type_lookup);
	
	return array('column_prefs' => $column_prefs, 'column_types' => $column_types, 'column_option' => $element_option_lookup, 'matrix_element_parent' => $matrix_element_parent);
}

function extractAndCopyFiles($dbh, $params=array()) {
	$docxArray = array();
	if(extension_loaded('zip')){
		$zip = new ZipArchive;
		$res = $zip->open($params['file']);
		if ($res === TRUE) {
			$zip->extractTo($params['tempPath']);
			for ($i = 0; $i < $zip->numFiles; $i++) {
				// get the name of the file
				$filename = $zip->getNameIndex($i);
				// check .zip file exists or not in the list
				if(strpos($filename, ".zip") !== false){
					if(copy($params['tempPath'].$filename, $params['portalPath'].$filename)){
						array_push($docxArray, $filename);
					}
				}
			}
			$zip->close();
		} 
	}
	return $docxArray;
}

















if(isset($_POST['files'])) {
	$form_id = (int) la_sanitize($_POST['form_id']);
	la_update_template_file($dbh, $form_id);

	$folder_path = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['SCRIPT_NAME'])."/templates/".$user_id;
	if(is_dir($folder_path) === false){
		@mkdir($folder_path, 0777, true);
	}
	$folder_path = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['SCRIPT_NAME'])."/templates/".$user_id."/".$form_id;
	if(is_dir($folder_path) === false){
		@mkdir($folder_path, 0777, true);
	}

	$fileCount = 0;
	foreach(json_decode($_POST['files']) as $pathToFile) {
		$fileCount++;
		
		$FileName = explode("files/", $pathToFile)[1];
		$FileName = preg_replace("/\.[^.\s]{3,4}$/", "", $FileName);
		$FileName = str_replace('.', '_', $FileName);

		if(file_exists($folder_path."/".$FileName.'.zip')){
			$FileName = $FileName.'_'.$fileCount.'.zip';	
		}else{
			$FileName = $FileName.'.zip';
		}

		$pathToFile = $_SERVER['DOCUMENT_ROOT']."/auditprotocol".$pathToFile;

		// extract files
		$docxArray = extractAndCopyFiles($dbh, array('file' => $pathToFile, 'tempPath' => $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/", 'portalPath' => $_SERVER["DOCUMENT_ROOT"]."/portal/template_output/"));
		
		$folder_path = explode("entries_backup_", $pathToFile)[0];
		
		$FileName = explode(".zip", $FileName)[0];

		$csvFile = $folder_path."{$FileName}.csv";
		$csvFile = explode("entries_backup_", $csvFile)[1];
		$csvFile = explode("_1", $csvFile)[0]; // remove timestamp
		$csvFile = $csvFile.".csv";
		
		if (file_exists($folder_path.$csvFile) && file_exists($folder_path."company_email.json")) {
			$elements = getColumnAndCodeName(array('dbh' => $dbh, 'form_id' => $form_id));
			$response = getData($dbh, $form_id, array('data_file' => $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/{$csvFile}", 'email_file' => $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id}/files/company_email.json", 'delimitter' => ",", 'docxArray' => $docxArray), $elements);
		} else {
			$response = array('status' => 'ERROR_1');
		}
		echo json_encode($response);
		exit();
	}
}
