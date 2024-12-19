<?php

function getFormFieldCount($dbh, $form_id) {
	$field_count = 0;
	$query = "SELECT element_type, element_default_value 
				FROM 
				".LA_TABLE_PREFIX."form_elements 
			WHERE 
			form_id=? and element_status = '1' and element_type <> 'page_break' and element_type <> 'section'
		ORDER BY 
				element_position asc";

	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
	while($row = la_do_fetch_result($sth)){
		
		if( $row['element_type'] == 'casecade_form' ) {
			//get count for this form too
			$cascade_field_count = getFormFieldCount($dbh, $row['element_default_value']);
			$field_count += $cascade_field_count;
		} else {
			$field_count++;
		}
	}
	return $field_count;
}

function getCasecadeFormIds($dbh, $form_id) {
	$query = "SELECT element_type, element_default_value 
				FROM 
				".LA_TABLE_PREFIX."form_elements 
		   WHERE 
		   		form_id=? and element_status = '1' and element_type = 'casecade_form' 
		ORDER BY 
				element_position asc";

 	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
	$form_ids = [];
	while($row = la_do_fetch_result($sth)){
		$form_ids[] = $row['element_default_value'];
	}
	return $form_ids;
}

function updateDocumentProcessStatus($dbh, $form_id, $company_id, $entry_id, $status) {
	$date_updated = date("Y-m-d H:i:s");
	$query_status = "UPDATE `".LA_TABLE_PREFIX."background_document_proccesses` SET updated_at = ?, status = ? WHERE `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ?";
	$params_status = array($date_updated, $status, $form_id, $company_id, $entry_id);
	la_do_query($query_status,$params_status,$dbh);

	//send email only when document generated successfully
	if( $status == 1 ) {
		$la_settings = la_get_settings($dbh);

		//get form details
		$query_form  = "SELECT `form_name`, `form_email` FROM `ap_forms` WHERE `form_id` = :form_id";
		$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
		$row_form    = la_do_fetch_result($result_form);
		$form_name   = trim($row_form['form_name']);
		$form_email   = trim($row_form['form_email']);

		//get form emails
		$query 	= "SELECT 
					 form_email,
					 form_name
					from 
					 `".LA_TABLE_PREFIX."forms` 
				where 
					 form_id=?";

		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$subject  = 'Document For Form '.$row['form_name'].' Complete';
		
		$recipients 	 = $row['form_email'];

		$email_template =<<<EOT
Hello,

Your Document has been generated for form '%s'. Login to your portal to retrieve your document.

EOT;

	    $email_content = sprintf($email_template, $subject);

		if( !empty($recipients) )
			la_send_email($dbh, $la_settings, $recipients, $subject, $email_content);
	}
}

function add_background_proccess($parameter) {
	$dbh = $parameter['dbh'];
	$la_settings = la_get_settings($dbh);
	$form_id = $parameter['form_id'];
	$entry_id = $parameter['entry_id'];
	$isAdmin = $parameter['isAdmin'];
	
	$la_user_id = ( $isAdmin == 1 ) ? $parameter['la_user_id'] : $parameter['client_id'];
	$company_user_id = ( $isAdmin == 1 ) ? $parameter['company_user_id'] : $parameter['company_id'];

	$date_time = date('Y-m-d H:i:s');

	$query = "INSERT INTO `".LA_TABLE_PREFIX."background_document_proccesses` (`form_id`, `user_id`, `company_user_id`, `entry_id`, `isAdmin`, `created_at`) VALUES (?, ?, ?, ?, ?, ?);";
	la_do_query($query, array($form_id, $la_user_id, $company_user_id, $entry_id, $isAdmin, $date_time), $dbh);
}


/* this function add user's session time to user_sessions table*/
function logUserSession($dbh, $user_id, $session_id, $action_type = 'login', $is_admin = 1)
{
	$table = LA_TABLE_PREFIX."user_sessions";
	$unix_time = time();

	if(isset($user_id) && isset($session_id) ){
		
			if( $action_type == 'login' ) {
				$query = "INSERT INTO `{$table}` (`id`, `user_id`, `session_id`, `login_time`, `is_admin`) VALUES (NULL, :user_id, :session_id, :login_time, :is_admin)";
				la_do_query($query,array(
					':user_id' => $user_id,
					':session_id' => $session_id,
					':login_time' => $unix_time,
					':is_admin' => $is_admin
				),$dbh);
			} else {
				//when loggin out just update existing row, dont insert new one
				$query = "SELECT id FROM `{$table}` WHERE user_id = :user_id AND is_admin = :is_admin AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
				$sth =la_do_query($query,array(
					':user_id' => $user_id,
					':is_admin' => $is_admin
				),$dbh);
				$row = la_do_fetch_result($sth);
				
				$row_id = $row['id'];
				if( $row_id ) {
					$query = "UPDATE `{$table}` SET `logout_time` = ? WHERE `id` = ?";
					la_do_query($query, array($unix_time, $row_id), $dbh);
				}
			}
	}else{
		return false;
	}
}

function sessionTime($dbh, $user_id, $is_admin = 1) {

	$table = LA_TABLE_PREFIX."user_sessions";
	$unix_time = time();

	if(isset($user_id)){
		
		$query = "SELECT login_time FROM `{$table}` WHERE user_id = :user_id AND is_admin = :is_admin AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
		$sth =la_do_query($query,array(
			':user_id' => $user_id,
			':is_admin' => $is_admin
		),$dbh);
		$row = la_do_fetch_result($sth);
		
		$login_time = $row['login_time'];
		if( $login_time ) {
		    $datetime1 = new DateTime();
		    $datetime2 = new DateTime("@$login_time");

		    // echo $datetime2->format('Y-m-d H:i:s');
		    // echo "<br>";
		   
		    $interval = date_diff($datetime1, $datetime2);
		   
		    return $interval->format('%i : %s');

		}
	}else{
		return false;
	}
}

function sync_form_field($dbh, $form_id, $element_machine_code, $element_id) {
	/***************************************************************************************************************/
		/* sync form fields according to machine code																   */
		/***************************************************************************************************************/

		//keep the element position same when syncing other values

		// echo $form_id;
		// echo $element_machine_code;
		// echo $element_id;

		// die('in here');

		$query = "SELECT * FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id != ? AND element_machine_code = ? LIMIT 1";
		$sth = la_do_query($query, array($form_id, $element_machine_code), $dbh);
		$row = la_do_fetch_result($sth);

		// print_r($row);

		if( !empty($row['element_machine_code']) ) {
			//there is another form with similar machine code, sync it with that field
			$query = "UPDATE `".LA_TABLE_PREFIX."form_elements` set `element_title` = :element_title,`element_guidelines` = :element_guidelines,`element_size` = :element_size,`element_is_required` = :element_is_required,`element_is_unique` = :element_is_unique,`element_is_private` = :element_is_private,`element_type` = :element_type WHERE form_id = :form_id and element_id = :element_id";
					
			$params = array();

			$params[':element_title'] = $row['element_title'];
			$params[':element_guidelines'] = $row['element_guidelines'];
			$params[':element_size'] = $row['element_size'];
			$params[':element_is_required'] = $row['element_is_required'];
			$params[':element_is_unique'] = $row['element_is_unique'];
			$params[':element_is_private'] = $row['element_is_private'];
			$params[':element_type'] = $row['element_type'];
			
			$params[':form_id'] = $form_id;
			$params[':element_id'] = $element_id;

			la_do_query($query,$params,$dbh);
		}
}


function getFieldPrimaryEntityValue ($dbh, $form_id, $company_id, $field_name) {
    $field_value = '';
    $query = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id = ? and field_name = ? LIMIT 1";
	$sth = la_do_query($query, array($company_id, $field_name), $dbh);
	$row = la_do_fetch_result($sth);
	if( $row ) {
		$field_value = $row['data_value'];
		
	}
	// die('in getFieldPrimaryEntityValue');
    return $field_value;
}

function getFormColumnData($params=array())
{
	$dbh = $params['dbh'];
	$form_id = $params['form_id'];
	$column = isset($params['column']) ? $params['column'] : "*";
	$condition = isset($params['condition']) ? $params['condition'] : "";
	$query = "select {$params['column']} from `".LA_TABLE_PREFIX."forms` where `form_id` = :form_id {$condition}";
	$result = la_do_query($query,array(':form_id' => $form_id),$dbh);
	
	$default_value = array();
	
	while($row = la_do_fetch_result($result)){
		$tmpAr = array();
		
		foreach($row as $k => $v){
			$tmpAr[$k] = $row[$k];
		}
		
		$default_value[] = $tmpAr;
	}
	
	return $default_value;	
}

function getFormElementWhereMachineCodeNotEmpty($dbh, $form_id,array $return_columns){
		$query = "SELECT *
			FROM 
				".LA_TABLE_PREFIX."form_elements 
		   WHERE 
		   		LENGTH(element_machine_code) > 0 AND form_id = ?
		ORDER BY 
				element_position asc";


		//print_r($params);
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		$j=0;
		$return_data = [];
		
		while($row = la_do_fetch_result($sth)){

			foreach ($row as $key => $value) {
				if( in_array($key, $return_columns) ) {
					$return_data[$j][$key] = $value;
				}
			}
			$j++;
		}
 		
		return $return_data;
	}
function addAutomaticEntryPullFrom($dbh, $form_id, $pull_from_form, $company_id) {

	//get all fields having machine code for child form
	$query = "SELECT id, element_machine_code, element_id
		FROM 
			".LA_TABLE_PREFIX."form_elements 
		WHERE 
			LENGTH(element_machine_code) > 0 AND form_id = ?
	ORDER BY 
			element_position asc";

	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
	$child_form_fields = [];
	$child_form_machine_codes = [];
	
	while($row = la_do_fetch_result($sth)){
		$child_form_fields[$row['id']] = [
			'element_machine_code' => $row['element_machine_code'],
			'element_id' => $row['element_id']
		];

		$child_form_machine_codes[$row['element_id']] = $row['element_machine_code'];
	}

	if( is_array($child_form_fields) && count($child_form_fields) ) {
		$child_form_machine_codes_imp = "'".implode("','", $child_form_machine_codes)."'";

		$query = "SELECT field_name, field_code, data_value, element_machine_code FROM ap_form_{$pull_from_form} WHERE LENGTH(element_machine_code) > 0 AND element_machine_code IN({$child_form_machine_codes_imp})";

		$sth = la_do_query($query,array(),$dbh);
		$field_values = [];

		$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `element_machine_code`, `unique_row_data`) VALUES ";

		$tmpQryArr = array();
		$tmpDatArr = array();

		while($row = la_do_fetch_result($sth)){
			//for this machine code check if the child form has multiple fields having same machine code
			$filterBy = $row['element_machine_code'];
			$child_fields = array_filter($child_form_fields, function ($var) use ($filterBy) {
				return ($var['element_machine_code'] == $filterBy);
			});
			
			foreach ($child_fields as $field_id => $field) {
				$child_field_code = $field['element_id'];
				$field_name_arr = explode('_', $row['field_name']);

				$field_name = 'element_'.$child_field_code;
				//using this for fields having muliple sub fields
				if( !empty($field_name_arr[2]) )
					$field_name .= '_'.$field_name_arr[2];

				$field_code = 'code_'.$child_field_code;
				$data_value = $row['data_value'];
				$element_machine_code = $row['element_machine_code'];


				array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, MD5(CONCAT(`company_id`, '_', `field_name`)))");
				$tmpDatArr = array_merge($tmpDatArr, array($company_id, $field_name, $field_code, $data_value, "0", $element_machine_code));
			}
		}

		$query .= implode(",", $tmpQryArr);
		$query .= " ON DUPLICATE KEY UPDATE `data_value` = values(`data_value`);";

		if(is_array($tmpDatArr) && count($tmpDatArr))
			la_do_query($query, $tmpDatArr, $dbh);
	}

	//add auotmatic entry for cascade forms too	for same entity
	$cascade_forms = getCascadeFormIds($dbh, $form_id);
	if( is_array($cascade_forms) && count($cascade_forms) ) {
		foreach ($cascade_forms as $cascade_form_id) {
			addAutomaticEntryPullFrom($dbh, $cascade_form_id, $pull_from_form, $company_id);
		}
	}

}

function addAutomaticEntry($dbh, $form_id, $for_selected_company) {

	$all_form_ids = getEachFormIdInThisEntity($dbh, $for_selected_company);
	$pos = array_search($form_id, $all_form_ids);
	//remove current form_id from list
	unset($all_form_ids[$pos]);

	if( count($all_form_ids) ) {

		$query = "SELECT id, element_machine_code, element_id, element_type
			FROM 
				".LA_TABLE_PREFIX."form_elements 
		   WHERE 
		   		LENGTH(element_machine_code) > 0 AND form_id = ?
		ORDER BY 
				element_position asc";

		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$child_form_fields = [];
		$child_form_machine_codes = [];
		
		while($row = la_do_fetch_result($sth)){
			$child_form_fields[$row['id']] = [
				'element_machine_code' => $row['element_machine_code'],
				'element_id' => $row['element_id'],
				'element_type' => $row['element_type']
			];

			$child_form_machine_codes[$row['element_id']] = $row['element_machine_code'];
		}

		//for each child field get the values from forms
		if( count($child_form_fields) ) {
			$child_form_machine_codes_imp = "'".implode("','", $child_form_machine_codes)."'";
			$all_form_ids_imp = "'".implode("','", $all_form_ids)."'";

			$query = "SELECT element_machine_code, element_id, element_type, form_id FROM ap_form_elements WHERE LENGTH(element_machine_code) > 0 AND element_machine_code IN({$child_form_machine_codes_imp}) AND form_id IN({$all_form_ids_imp}) ORDER BY form_id DESC";

			$sth = la_do_query($query,array(),$dbh);

			//group machine code acc to form_id to reduce query count
			$group_machine_codes = [];
			while($row = la_do_fetch_result($sth)){
				$group_machine_codes[$row['form_id']][] = $row;
			}

			$parent_array = [];
			if( count($group_machine_codes) ) {
				foreach ($group_machine_codes as $form_id_1 => $machine_code_arr) {
					$entry_field_names = [];
					foreach ($machine_code_arr as $key => $machine_code_row) {
						//fetching code because some fields like address has multiple field_name like element_n_1, element_n_2
						$entry_field_names[] = 'code_'.$machine_code_row['element_id'];
					}
					$entry_field_names_imp = "'".implode("','", $entry_field_names)."'";


					$query = "SELECT data_value, field_code, field_name, element_machine_code FROM `".LA_TABLE_PREFIX."form_{$form_id_1}` WHERE field_code IN ({$entry_field_names_imp}) AND company_id = ? AND LENGTH(element_machine_code) > 0 AND data_value != ''";

					$sth = la_do_query($query,array($for_selected_company),$dbh);
					
					while($row = la_do_fetch_result($sth)){
						//for this machine code check if the child form has multiple fields having same machine code
						$filterBy = $row['element_machine_code'];
						$child_fields = array_filter($child_form_fields, function ($var) use ($filterBy) {
							return ($var['element_machine_code'] == $filterBy);
						});
						
						foreach ($child_fields as $field_id => $field) {
							

							//check if field type is file and enable synced files logic
							if( $field['element_type'] == 'file' ) {
								
								$element_machine_code = $field['element_machine_code'];
								$company_id = $for_selected_company;
								$data_value = $row['data_value'];

								//check if row already exists file_upload_synced
								$sql = "select `id` from `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ? LIMIT 1;";
								$res = la_do_query($sql,array($element_machine_code, $company_id),$dbh);
								$row = la_do_fetch_result($res);

								if( $row['id'] ) {
									//dont insert data as it is already being synced
								} else {
									if( !empty($element_machine_code) && !empty($data_value) ) {
										//check if dir exists for this machine code
										$upload_location = $_SERVER["DOCUMENT_ROOT"].'/auditprotocol/data/file_upload_synced/'.$element_machine_code;

										if(is_writable($_SERVER["DOCUMENT_ROOT"].'/auditprotocol/data')){
											if (!is_dir($upload_location))
												mkdir($upload_location, 0777);

											$all_files = explode("|", $data_value);
											$time_stamp = time();
											$copied_files = [];
											foreach ($all_files as $file_name) {
												$source = $_SERVER["DOCUMENT_ROOT"]."/auditprotocol/data/form_{$form_id_1}/files/{$file_name}";	

												if(file_exists($source)){
													//add random numbers to the filename
													$new_file_name = "{$time_stamp}_$file_name";
													$destination = $upload_location."/".$new_file_name;

													if( copy($source, $destination) ) {  
														$copied_files[] = $new_file_name;
													} else {
														// echo "File can't be copied! \n";  
													}
												}
											}

											if( count($copied_files) ) {
												$query  = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`element_machine_code`, `files_data`, company_id) VALUES (?, ?, ?);";
												la_do_query($query,array($element_machine_code, json_encode($copied_files), $company_id),$dbh);
											}
										}
									}
								}
							} else {
								$child_field_code = $field['element_id'];

								$field_name = 'element_'.$child_field_code;

								$field_name_arr_parent = explode('_', $row['field_name']);

								//using this for fields having muliple sub fields
								if( !empty($field_name_arr_parent[2]) )
									$field_name .= '_'.$field_name_arr_parent[2];

								$field_code = 'code_'.$child_field_code;
								$data_value = $row['data_value'];
								$element_machine_code = $row['element_machine_code'];

								$child_array = [
									'field_name' => $field_name,
									'element_machine_code' => $element_machine_code,
									'field_code' => $field_code,
									'data_value' => $data_value
								];

								$parent_array[$field_name.'-'.$element_machine_code] = $child_array;
							}
						}

					}

				}
				if( count($parent_array) ) {
					$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `element_machine_code`, `unique_row_data`) VALUES ";

					$tmpQryArr = array();
					$tmpDatArr = array();
					foreach ($parent_array as $key => $row_data) {
						array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, MD5(CONCAT(`company_id`, '_', `field_name`)))");

						$field_name = $row_data['field_name'];
						$field_code = $row_data['field_code'];
						$data_value = $row_data['data_value'];
						$element_machine_code = $row_data['element_machine_code'];

						$tmpDatArr = array_merge($tmpDatArr, array($for_selected_company, $field_name, $field_code, $data_value, "0", $element_machine_code));
					}
					$query .= implode(",", $tmpQryArr);
					$query .= " ON DUPLICATE KEY UPDATE `data_value` = values(`data_value`);";

					la_do_query($query, $tmpDatArr, $dbh);
				}
				
			}
		}
	}

	//add auotmatic entry for cascade forms too	for same entity
	$cascade_forms = getCascadeFormIds($dbh, $form_id);
	if( count($cascade_forms) ) {
		foreach ($cascade_forms as $cascade_form_id) {
			addAutomaticEntry($dbh, $cascade_form_id, $for_selected_company);
		}
	}
}

function getCascadeFormIds($dbh, $parentFormId) {
	$query = "select 
				element_default_value 
			from 
				".LA_TABLE_PREFIX."form_elements 
		   	where 
		   		element_type = 'casecade_form' and 
		   		element_status = 1 and 
		   		form_id = ?";
	$params = array($parentFormId);

	$sth = la_do_query($query,$params,$dbh);
	$cascade_forms = [];
	while($row = la_do_fetch_result($sth)){
		$child_form_id = $row["element_default_value"];
		if( $child_form_id )
			$cascade_forms[] = $child_form_id;
	}
	return $cascade_forms;
}

/*
 * get all form Ids for entity
*/
function getEachFormIdInThisEntity ($dbh, $entity_id) {
	$formIdsInThisEntity = array();
	$query1 = "SELECT form_id
			FROM `".LA_TABLE_PREFIX."forms` forms WHERE form_for_selected_company = {$entity_id}
			UNION
			SELECT form_id
			FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE entity_id = {$entity_id};";

	$sth1 = la_do_query($query1, array(), $dbh);

	while($row = la_do_fetch_result($sth1)){
		$form_id_1 = $row['form_id'];
		$autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id_1")->fetch()['form_enable_auto_mapping'];
		if($autoMappingFormSettings == 1) {
			array_push($formIdsInThisEntity, $form_id_1);
		}
	}

	return $formIdsInThisEntity;
}

function update_machine_codes($dbh, $form_id, $company_id) {
	if(!empty($company_id)) {
		$all_form_ids = getEachFormIdInThisEntity($dbh, $company_id);

		if( count($all_form_ids) ) {
			foreach($all_form_ids as $form_id_single){
				update_machine_codes_single_form($dbh, $form_id_single);
			}
		}
	} else {
		update_machine_codes_single_form($dbh, $form_id);
	}
}

function update_machine_codes_single_form($dbh, $form_id) {
	// generate column if not exists
	$query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$form_id}' AND `COLUMN_NAME` = 'element_machine_code'";
	$sth = la_do_query($query,array(),$dbh);
	$row = la_do_fetch_result($sth);
	if(!$row){
		$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD `element_machine_code` varchar(250) NOT NULL DEFAULT '' AFTER `unique_row_data`;";
		la_do_query($query,array(),$dbh);
	}

	//get form element details
	$query = "select 
				element_id, element_machine_code 
			from 
				".LA_TABLE_PREFIX."form_elements 
		   	where 
			   element_machine_code <> '' and  
		   		form_id = ?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$machine_codes = [];
	while($row = la_do_fetch_result($sth)){
		$code = "code_".$row['element_id'];
		$machine_codes[$code] = $row['element_machine_code'];
	}
	
	$entry_rows = [];

	if( count($machine_codes) ) {
		//select all entry rows
		$query = "select 
					id, field_code 
				from 
					".LA_TABLE_PREFIX."form_{$form_id} 
				where 
				field_code <> ''";
		$params = array();

		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$entry_rows[$row['id']] = $row['field_code'];
		}
	}

	$query = "";
	$table = LA_TABLE_PREFIX."form_{$form_id}";
	if( is_array($entry_rows) && count($entry_rows) ) {
		//update every row with new machine code
		foreach ($entry_rows as $id => $code) {
			$element_machine_code = $machine_codes[$code];
			$query .= "UPDATE {$table} SET element_machine_code = '".$element_machine_code."' WHERE id = {$id};";	
		}
		$sth = la_do_query($query,[],$dbh);
	}
}

function update_entry_machine_codes($dbh, $form_id) {
	//get form element details
	$query = "select 
				element_id, element_machine_code 
			from 
				".LA_TABLE_PREFIX."form_elements 
		   	where 
			   element_machine_code <> '' and  
		   		form_id = ?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$machine_codes = [];
	while($row = la_do_fetch_result($sth)){
		$code = "code_".$row['element_id'];
		$machine_codes[$code] = $row['element_machine_code'];
	}

	$update_query = '';
	if( count($machine_codes) ) {
		foreach ($machine_codes as $field_code => $machine_code) {
			$table = LA_TABLE_PREFIX."form_{$form_id}";
			$update_query .= "UPDATE {$table} SET element_machine_code = '".$machine_code."' WHERE field_code = '".$field_code."';";	
		}
		// echo $update_query;
		$sth = la_do_query($update_query,[],$dbh);
	}
}

function create_doc_from_wysiwyg_template($dbh, $form_id, $template_id, $replace_data_array, $la_user_id, $company_user_id, $base_url = null) {
	$table = LA_TABLE_PREFIX."form_templates";
	$query  = "SELECT * FROM `{$table}` WHERE `id` = :template_id LIMIT 1";
	$result = la_do_query($query,array(':template_id' => $template_id),$dbh);
	$row    = la_do_fetch_result($result);
	$machine_code_data = $row['data'];

	/***************F O R M  N A M E ****************/
	$query_form  = "SELECT `form_name`  FROM `ap_forms` WHERE `form_id` = :form_id";
	$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
	$row_form    = la_do_fetch_result($result_form);
	$form_name   = trim($row_form['form_name']);
	$form_name   = str_replace(" ", "_", $form_name);
	$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
	$form_name	 = substr($form_name, 0, 24);
	/***************F O R M  N A M E ****************/

	$machine_codes = array();
	$replacements = array();

	$code_i = 0;
	foreach ($replace_data_array as $code => $replacement) {
		if( is_array($replacement) ) {
			$temp_arr = $replacement;
			$replacement = '';
			
			if(array_key_exists('insertHTML', $temp_arr)) {
				$replacement = $temp_arr['insertHTML'];
			} else if(array_key_exists('insertParagraph', $temp_arr)) {
				$replacement = $temp_arr['insertParagraph'];
			} else if(array_key_exists('insertText', $temp_arr)) {
				$replacement = $temp_arr['insertText'];
			}
		}
		$machine_codes[$code_i] = '/\$'.$code.'\$/';
		$replacements[$code_i] = $replacement;
		$code_i++;
	}

	$final_string = preg_replace($machine_codes, $replacements, $machine_code_data);
	$final_string = preg_replace('/(\$.*?\$)/', '', $final_string);
	$final_string = "
	<head>
	<style>
p {
	margin: 0px;
	padding: 0px;
	border: 0px;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}
.text-tiny {
    font-size: .7em;
}
.text-small {
    font-size: .85em;
}
.text-big {
    font-size: 1.4em;
}
.text-huge {
    font-size: 1.8em;
}
.marker-yellow {
    background-color: #FDFD77;
}
.marker-green {
    background-color: #62F962;
}
.marker-pink {
    background-color: #FC7899;
}
.marker-blue {
    background-color: #72CCFD;
}
.pen-red {
    color: #E71313;
    background-color: transparent;
}
.pen-green {
    color: #128A00;
    background-color: transparent;
}
.image {
    display: table;
    clear: both;
    text-align: center;
    margin: 1em;
}
.image img {
    display: block;
    margin: 0px;
    max-width: 650px;
    min-width: 50px;
    width: 100%
}
.image > figcaption {
    display: table-caption;
    caption-side: bottom;
    word-break: break-word;
    color: #333333;
    background-color: #F7F7F7;
    padding: .6em;
    font-size: .75em;
    outline-offset: -1px;
}
.image.image_resized {
    max-width: 100%;
    display: block;
    box-sizing: border-box;
}
.image.image_resized img {
    width: 100%;
}
.image.image_resized > figcaption {
    display: block;
}
hr {
    margin: 15px 0px;
    height: 4px;
    background: #DEDEDE;
    border: 0px;
}
.image-style-side {
    float: right;
    margin-left: 1.5em;
    max-width: 50%;
}
.image-style-align-left {
    float: left;
    margin-right: 1.5em;
}
.image-style-align-center {
    margin-left: auto;
    margin-right: auto;
}
.image-style-align-right {
    float: right;
    margin-left: 1.5em;
}
blockquote {
    overflow: hidden;
    padding-right: 1.5em;
    padding-left: 1.5em;
    margin-left: 0px;
    margin-right: 0px;
    font-style: italic;
    border-left: 5px solid #CCCCCC;
}
[dir=\"rtl\"] blockquote {
    border-left: 0px;
    border-right: 5px solid #CCCCCC;
}
code {
    background-color: #C7C7C7;
    padding: .15em;
    border-radius: 2px;
}
.table {
    margin: 1em auto;
    display: table;
}

.table table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    height: 100%;
    border: 1px double #000000;
}


.table table td,
.table table th {
    min-width: 2em;
    border: 1px solid #000000;
    padding-top:12px !important;
    padding-bottom:12px !important;

}
.table table th {
    font-weight: bold;
    background: #0085CC;
}
[dir=\"rtl\"] .table th {
    text-align: right;
}
[dir=\"ltr\"] .table th {
    text-align: left;
}
.page-break {
    position: relative;
    clear: both;
    padding: 5px 0px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.page-break::after {
    content: '';
    position: absolute;
    border-bottom: 2px dashed #C4C4C4;
    width: 100%;
}
.page-break__label {
    position: relative;
    z-index: 1;
    padding: .3em .6em;
    display: block;
    text-transform: uppercase;
    border: 1px solid #C4C4C4;
    border-radius: 2px;
    font-family: Helvetica, Arial, Tahoma, Verdana, Sans-Serif;
    font-size: 0.75em;
    font-weight: bold;
    color: #C4C4C4;
    background: #FFFFFF;
}
.media {
    clear: both;
    margin: 1em 0px;
    display: block;
    min-width: 15em;
}
.todo-list {
    list-style: none;
}
.todo-list li {
    margin-bottom: 5px;
}
.todo-list li .todo-list {
    margin-top: 5px;
}
.todo-list .todo-list__label > input {
    -webkit-appearance: none;
    display: inline-block;
    position: relative;
    width: 16px;
    height: 16px;
    vertical-align: middle;
    border: 0;
    left: -25px;
    margin-right: -15px;
    right: 0px;
    margin-left: 0px;
}
.todo-list .todo-list__label > input::before {
    display: block;
    position: absolute;
    box-sizing: border-box;
    content: '';
    width: 100%;
    height: 100%;
    border: 1px solid #C4C4C4;
    border-radius: 2px;
}
.todo-list .todo-list__label > input::after {
    display: block;
    position: absolute;
    box-sizing: content-box;
    pointer-events: none;
    content: '';
    left: 5px;
    top: 3px;
    width: 3px;
    height: 5px;
    border-style: solid;
    border-color: transparent;
    border-width: 0px 2px 2px 0px;
}
.todo-list .todo-list__label > input[checked]::before {
    background: #26AB33;
    border-color: #26AB33;
}
.todo-list .todo-list__label > input[checked]::after {
    border-color: #FFFFFF;
}
.todo-list .todo-list__label .todo-list__label__description {
    vertical-align: middle;
}
.raw-html-embed {
    margin: 1em auto;
    min-width: 15em;
    font-style: normal;
}
pre {
    padding: 1em;
    color: #333333;
    background: #C7C7C7;
    border: 1px solid #C4C4C4;
    border-radius: 2px;
    text-align: left;
    tab-size: 4;
    white-space: pre-wrap;
    font-style: normal;
    min-width: 200px;
}
pre code {
    background: unset;
    padding: 0px;
    border-radius: 0px;
}
.mention {
    color: ##990030;
}
@media print {
    .page-break {
        padding: 0px;
    }
    .page-break::after {
        display: none;
    }
}
</style>
	</head>
	{$final_string}
	
	";



	$docx = new CreateDocx();
	$docx->embedHTML($final_string, array('useHTMLExtended' => true, 'downloadImages' => true));

	//add wysiwyg styles

	$timestamp = time();
	$template_name = $form_name."_".$timestamp;

	$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name);

	$created_file = $template_name.'.docx';
	
	return $created_file;
}

function generateWysiwygImageUrls($replace_data_array, $base_url, $form_id){
	$tmpArr = array();

	$filePath = $_SERVER["DOCUMENT_ROOT"].'/auditprotocol/data/form_'.$form_id.'/files/';
	$fileUrlPath = "{$base_url}data/form_{$form_id}/files/";
	$tmpArr = array();
	foreach($replace_data_array as $key => $value){
		if(empty($key)){
			continue;
		}
		
		$tmpArr[$key] = array();

		if(!empty($value)){
			if(strpos($value, "|") !== false){
				$valueArr = explode("|", $value);
			}else{
				$valueArr = array($value);
			}
		}
		if(is_array($valueArr) && count($valueArr) > 0){
			$imagesArr = array();
			$anchorArr = array();
			foreach($valueArr as $val){
				$file = $filePath.$val;
				$fileUrl = $fileUrlPath.$val;
				$ext = strtolower(end(explode(".", $val)));
				if(in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'bmp'))){
					if(file_exists($file)){
						array_push($imagesArr, $fileUrl);
					}
				} else {
					array_push($anchorArr, $fileUrl);
				}
			}
			
			if(is_array($imagesArr) && count($imagesArr)){
				$tmpArr[$key]['file_wysiwyg']['images'] = $imagesArr;
			}
			if(is_array($imagesArr) && count($imagesArr)){
				$tmpArr[$key]['file_wysiwyg']['links'] = $anchorArr;
			}
		}
	}
	return $tmpArr;
}