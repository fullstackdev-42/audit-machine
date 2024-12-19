<?php

function getFormFieldCount($dbh, $form_id, $include_cascade=0) {
	$field_count = 0;
	if( $include_cascade ) {
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
	} else {
		$query = "SELECT count(*) as field_count  
   				FROM 
					".LA_TABLE_PREFIX."form_elements 
			   WHERE 
			   		form_id=? and element_status = '1' and element_type <> 'page_break' and element_type <> 'section' 
			ORDER BY 
					element_position asc";
 
	 	$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$field_count = $row['field_count'];

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

function updateDocumentProcessStatus($dbh, $form_id, $company_id, $status) {
	$date_updated = date("Y-m-d H:i:s");
	$query_status = "UPDATE `".LA_TABLE_PREFIX."background_document_proccesses` SET updated_at = ?, status = ? WHERE `form_id` = ? AND `company_user_id` = ?";
	$params_status = array($date_updated, $status, $form_id, $company_id);
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

/*function insertDocumentProcess($dbh, $user_id, $form_id, $company_id, $called_from) {

}*/

function add_background_proccess($parameter) {
	$dbh = $parameter['dbh'];
	$la_settings = la_get_settings($dbh);
	$form_id = $parameter['form_id'];
	$isAdmin = $parameter['isAdmin'];
	$called_from = $parameter['called_from'];

	if( empty($called_from) )
		$called_from = 'background_process';
	
	$la_user_id = ( $isAdmin == 1 ) ? $parameter['la_user_id'] : $parameter['client_id'];
	$company_user_id = ( $isAdmin == 1 ) ? $parameter['company_user_id'] : $parameter['company_id'];

	$date_time = date('Y-m-d H:i:s');

	$query = "INSERT INTO `".LA_TABLE_PREFIX."background_document_proccesses` (`form_id`, `user_id`, `company_user_id`, `isAdmin`, `created_at`) VALUES (?, ?, ?, ?, ?);";
	la_do_query($query, array($form_id,$la_user_id, $company_user_id, $isAdmin, $date_time), $dbh);
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
					$query = "UPDATE `{$table}` SET `logout_time` = :logout_time WHERE `user_id` = :user_id AND `is_admin` = :is_admin";
					la_do_query($query,array(
						':logout_time' => $unix_time,
						':user_id' => $user_id,
						':is_admin' => $is_admin
					),$dbh);
				}
				/*$query = "UPDATE `{$table}` SET `logout_time` = :logout_time WHERE `session_id` = :session_id";
				la_do_query($query,array(
					':logout_time' => $unix_time,
					':session_id' => $session_id,
				),$dbh);*/
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

	// echo $form_id."<br>";
	// echo $pull_from_form;

	// die('in here');
	//get all fields having machine code for child form
	$element_ids_arr_child = getFormElementWhereMachineCodeNotEmpty($dbh, $form_id, array('element_machine_code', 'element_id'));


 
 
	$child_form_machine_codes = [];
	foreach ($element_ids_arr_child as $key => $element) {
		// $field_name = 'element_'.$element['element_id'];
		// $field_code = 'code_'.$element['element_id'];
		$child_form_machine_codes[$element['element_id']] = $element['element_machine_code'];
		
	}
	$child_form_machine_codes_flipped = array_flip($child_form_machine_codes);

	if( count($child_form_machine_codes) ) {
		$child_form_machine_codes_imp = "'".implode("','", $child_form_machine_codes)."'";

		$query = "SELECT field_name, field_code, data_value, element_machine_code FROM ap_form_{$pull_from_form} WHERE LENGTH(element_machine_code) > 0 AND element_machine_code IN({$child_form_machine_codes_imp})";

			error_log("Query 1");
			error_log($query);

		$sth = la_do_query($query,array(),$dbh);
		$field_values = [];

		$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `field_name`, `field_code`, `data_value`, `form_resume_enable`, `element_machine_code`, `unique_row_data`) VALUES ";

	error_log("Query 2");
			error_log($query);

		$tmpQryArr = array();
		$tmpDatArr = array();

		while($row = la_do_fetch_result($sth)){
			// print_r($row);

	error_log("Printing Row From Query 2.");
	error_log($row);


			error_log($query);
			//get field_code of child for this machine code
			$child_field_code = $child_form_machine_codes_flipped[$row['element_machine_code']];

			// if( strpos($row['field_name'], '_') )
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

		$query .= implode(",", $tmpQryArr);
		$query .= " ON DUPLICATE KEY UPDATE `data_value` = values(`data_value`);";

		// print_r($tmpQryArr);
		// print_r($tmpDatArr);
		// print_r($query);

	error_log("INSERT VALUE QUERY");
	error_log($tmpQryArr);

		if(count($tmpDatArr))
			la_do_query($query, $tmpDatArr, $dbh);
	}

}

function addAutomaticEntry($dbh, $form_id, $for_selected_company) {
	
	$all_form_ids = getEachFormIdInThisEntity($dbh, $for_selected_company);
	$pos = array_search($form_id, $all_form_ids);
	//remove current form_id from list
	unset($all_form_ids[$pos]);

	if( count($all_form_ids) ) {

		//get all fields having machine code for child form
		$element_ids_arr_child = getFormElementWhereMachineCodeNotEmpty($dbh, $form_id, array('element_machine_code', 'element_id'));

		$child_form_machine_codes = [];
		foreach ($element_ids_arr_child as $key => $element) {
			$child_form_machine_codes[$element['element_id']] = $element['element_machine_code'];
		}
		$child_form_machine_codes_flipped = array_flip($child_form_machine_codes);
		if( count($child_form_machine_codes) ) {

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
					
					$form_machine_codes = [];
					$entry_field_names = [];
					foreach ($machine_code_arr as $key => $machine_code_row) {
						$entry_field_names[] = 'code_'.$machine_code_row['element_id'];
					}
					$entry_field_names_imp = "'".implode("','", $entry_field_names)."'";
					$query = "SELECT data_value, field_code, field_name, element_machine_code, CONCAT(field_name, field_code) AS cc FROM `".LA_TABLE_PREFIX."form_{$form_id_1}` WHERE field_code IN ({$entry_field_names_imp}) AND company_id = ? AND LENGTH(element_machine_code) > 0 AND data_value != ''";

					$sth = la_do_query($query,array($for_selected_company),$dbh);
					$field_values = [];

					while($row = la_do_fetch_result($sth)){

						//get field_code of child for this machine code
						$child_field_code = $child_form_machine_codes_flipped[$row['element_machine_code']];

						
						$field_name_arr = explode('_', $row['field_name']);

						$field_name = 'element_'.$child_field_code;
						//using this for fields having muliple sub fields
						if( !empty($field_name_arr[2]) )
							$field_name .= '_'.$field_name_arr[2];

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
            $form_id_1               = $row['form_id'];
            $autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id_1")->fetch()['form_enable_auto_mapping'];
            if($autoMappingFormSettings == 1) {
                array_push($formIdsInThisEntity, $form_id_1);        
            }
        }

    return $formIdsInThisEntity;
}

function update_machine_codes($dbh, $form_id) {
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
		$entry_rows = [];
		while($row = la_do_fetch_result($sth)){
			$entry_rows[$row['id']] = $row['field_code'];
		}
	}

	$query = "";
	$table = LA_TABLE_PREFIX."form_{$form_id}";
	if( count($entry_rows) ) {
		//update every row with new machine code
		foreach ($entry_rows as $id => $code) {
			$element_machine_code = $machine_codes[$code];
			$query .= "UPDATE {$table} SET element_machine_code = '".$element_machine_code."' WHERE id = {$id};";	
		}
		$sth = la_do_query($query,[],$dbh);
	}


}