<?php
//include $_SERVER['DOCUMENT_ROOT'].'/itam-shared/api/phpsocket.io/vendor/autoload.php';
function map_single_field($dbh, $fieldInfo) {
	$entity_id = $fieldInfo->selected_entity_id;
	$field_machine_code = $fieldInfo->field_machine_code;
	$useremail = $fieldInfo->userEmail;
	$form_id = $fieldInfo->formId;
	$field_value = $fieldInfo->value;
    $socket_element_id = $fieldInfo->element_id;


	$field_sub_type = '';
    $field_value_db = '';

	if( property_exists($fieldInfo, 'fieldType') )
		$field_type = $fieldInfo->fieldType;

	if( property_exists($fieldInfo, 'fieldSubType') )
		$field_sub_type = $fieldInfo->fieldSubType;

    if( property_exists($fieldInfo, 'valueDb') )
        $field_value_db = $fieldInfo->valueDb;

	$formsIdsBelongingToThisEntity = getEachFormIdInThisEntity($dbh, $entity_id);

    //get other form rows for this machine code
	$imploded_forms = implode("','", $formsIdsBelongingToThisEntity);
    $elementsInEntityThatContainCode = [];
    $query = "SELECT element_machine_code, element_id, element_type, form_id, element_matrix_allow_multiselect FROM ap_form_elements WHERE element_machine_code = '".$field_machine_code."' AND element_type != 'file' AND form_id IN ('".$imploded_forms."')";

    foreach($dbh->query($query) as $row) {
        // create an object containing the element data
        $object                                   = new stdClass();
        $object->element_machine_code             = $row["element_machine_code"];
        $object->element_id                       = $row["element_id"];
        $object->element_type                     = $row["element_type"];
        $object->form_id                          = $row["form_id"];
        $object->element_matrix_allow_multiselect = $row['element_matrix_allow_multiselect'];

        // store that object inside the array
        array_push($elementsInEntityThatContainCode, $object);
    }

    // print_r($elementsInEntityThatContainCode);

    if( count($elementsInEntityThatContainCode) > 0 ) {
    	foreach ($elementsInEntityThatContainCode as $key => $form_field) {
    		$element_type = $form_field->element_type;
    		$element_id = $form_field->element_id;
    		$other_form_id = $form_field->form_id;

    		$database_table_name = LA_TABLE_PREFIX."form_".$other_form_id;
    		$field_params = [
                'form_id' => $other_form_id,
    			'table_name' => $database_table_name,
    			'element_id' => $element_id,
    			'field_value' => $field_value,
    			'field_machine_code' => $field_machine_code,
    			'entity_id' => $entity_id,
    			'field_sub_type' => $field_sub_type,
                'socket_element_id' => $socket_element_id,
    			'dbh' => $dbh
    		];
    		if(
		        $element_type == "text" ||
		        $element_type == "email" ||
		        $element_type == "number" ||
		        $element_type == "url" ||
                $element_type == "phone" ||
                $element_type == "time" ||
                $element_type == "money" ||
                $element_type == "date" ||
		        $element_type == "textarea" ||
                $element_type == "select"
		    ) {

                if( !empty($field_value_db) )
                    $field_value = $field_value_db;

                //flipping status indicators
                if($element_type == "text" || $element_type == "textarea" || $element_type == "select") {
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($field_machine_code, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $field_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($other_form_id, $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($other_form_id, $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }
                }

		        $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ?";
		        $params = array($field_value, $field_machine_code, $entity_id);
		        la_do_query($query, $params, $dbh);
		    } else if ( $field_type == 'simple_name' ) {
		    	update_simple_name($field_params);
		    } else if ( $field_type == 'address' ) {
		    	update_address($field_params);
            } else if ( $field_type == 'radio' ) {
                update_radio($field_params);
		    } else if ( $field_type == 'checkbox' ) {
                update_checkbox($field_params);
            }
    	}
    }
    

    // print_r($elementsInEntityThatContainCode);

    if(count($formsIdsBelongingToThisEntity) > 1)
    	unlockFieldAfterBlur($dbh, [$field_machine_code], $useremail, $form_id);

}

function update_radio($field_params) {
    extract($field_params);

    if( $field_sub_type == 'element_radio_other' ) {
        $field_name = 'element_'.$element_id;
        $params = array(0, $field_machine_code, $entity_id, $field_name);
    } else if( $field_sub_type == 'element_radio_other_text' ) {
        $field_name = 'element_'.$element_id.'_other';
        $params = array($field_value, $field_machine_code, $entity_id, $field_name);

        //if element_radio_other_text option selected for radio, always set element_{$element_id} to 0
        $field_name_parent = 'element_'.$element_id;
        $params_parent = array(0, $field_machine_code, $entity_id, $field_name_parent);
        $query_parent = "UPDATE {$table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
        la_do_query($query_parent, $params_parent, $dbh);


    } else {
        $field_name = 'element_'.$element_id;
        $params = array($field_value, $field_machine_code, $entity_id, $field_name);
    }

    //flipping status indicators
    $query_existing_value = "SELECT data_value FROM {$table_name} WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
    $sth_existing_value = la_do_query($query_existing_value, array($field_machine_code, $entity_id, $field_name), $dbh);
    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
        if($row_existing_value) {
            //if the element has former data saved in the database
            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $field_value);
            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                $tmp_element_id = explode("_", $field_name)[1];
                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                la_do_query($query_status_2, array($form_id, $tmp_element_id, $entity_id), $dbh);
                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                la_do_query($query_status_3, array($form_id, $tmp_element_id, $entity_id, 2), $dbh);
            }
        }
    }
    $query = "UPDATE {$table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
    la_do_query($query, $params, $dbh);
    
}

function update_checkbox($field_params) {
    extract($field_params);
    // echo "in checkbox";

    $socket_element_id_exploded = explode('_', $socket_element_id);

    $socket_field_key = $socket_element_id_exploded[2];

    if( $field_sub_type == 'element_radio_other' ) {
        //dont save any value for Other checkbox field
    } else if( $field_sub_type == 'element_checkbox_other_text' ) {
        $field_name = 'element_'.$element_id.'_other';
    } else {
        $field_name = 'element_'.$element_id.'_'.$socket_field_key;
    }

    //flipping status indicators
    $query_existing_value = "SELECT data_value FROM {$table_name} WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
    $sth_existing_value = la_do_query($query_existing_value, array($field_machine_code, $entity_id, $field_name), $dbh);
    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
        if($row_existing_value) {
            //if the element has former data saved in the database
            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $field_value);
            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                 $tmp_element_id = explode("_", $field_name)[1];
                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                la_do_query($query_status_2, array($form_id, $tmp_element_id, $entity_id), $dbh);
                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                la_do_query($query_status_3, array($form_id, $tmp_element_id, $entity_id, 2), $dbh);
            }
        }
    }

    $query = "UPDATE {$table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
    $params = array($field_value, $field_machine_code, $entity_id, $field_name);
    la_do_query($query, $params, $dbh);
    
}

function update_query($field_params) {
	extract($field_params);
	$field_codes_arr = array_flip($field_codes_arr);
	$field_code = $field_codes_arr[$field_sub_type];
	$field_name = 'element_'.$element_id.'_'.$field_code;



	$query = "UPDATE {$table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ? AND field_name = ?";
    $params = array($field_value, $field_machine_code, $entity_id, $field_name);
    la_do_query($query, $params, $dbh);
}

function update_address($field_params) {

	$field_codes_arr = [
		1 => 'addressstreet',
		2 => 'addressstreet2',
		3 => 'addresscity',
		4 => 'addressstate',
		5 => 'addresszip',
		6 => 'addresscountry'
	];

	$field_params['field_codes_arr'] = $field_codes_arr;
	update_query($field_params);
}

function update_simple_name($field_params) {
	$field_codes_arr = [
		1 => 'firstname',
		2 => 'lastname'
	];
	
	$field_params['field_codes_arr'] = $field_codes_arr;
	update_query($field_params);
}

function getEachFormIdInThisEntity ($dbh, $entity_id) {
    $formIdsInThisEntity = array();
    foreach($dbh->query("SELECT form_id FROM ap_forms WHERE form_for_selected_company = $entity_id") as $row) {
        // filter our forms that have auto-mapping disabled in the settings
        $form_id_1               = $row['form_id'];
        $autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id_1")->fetch()['form_enable_auto_mapping'];
        if($autoMappingFormSettings == 1) {
            array_push($formIdsInThisEntity, $form_id_1);        
        }
    }
    return $formIdsInThisEntity;
}

function unlockFieldAfterBlur($dbh, array $fields, $usersEmail, $form_id) {
    //after lock delete lock and notify other users that user has left the field
    $imploded_fields = implode("','", $fields);
    $query = "DELETE FROM ap_blocked_form_fields WHERE element_machine_code IN ('{$imploded_fields}') AND form_id_where_lock_originated = {$form_id} AND users_email = '{$usersEmail}'";
    $dbh->query($query);

    $emitter = new Emitter();
    $emitter->emit('unlock fields', array('fields'=> $fields, 'userEmail'=> $usersEmail, 'formId' => $form_id));
}