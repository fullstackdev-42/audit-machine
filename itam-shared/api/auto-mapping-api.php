<?php

require_once "../../portal/config.php";
require_once "../../portal/includes/db-core.php";

$dbh          = la_connect_db();
$request_body = file_get_contents('php://input');
$data         = json_decode($request_body);
$form_id      = $data->form_id;
$entity_id    = $data->entity_id;
$task         = $data->task;

if($task == "get auto-mapping settings") {
    
    // $autoMappingMainSettings = $dbh->query("SELECT `auto_mapping_main_settings` FROM ap_settings")->fetch()['auto_mapping_main_settings'];
    $sth = la_do_query("SELECT `auto_mapping_main_settings` FROM ap_settings", array(), $dbh);
    $row = la_do_fetch_result($sth);
    $autoMappingMainSettings = $row['auto_mapping_main_settings'];

    // $autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id")->fetch()['form_enable_auto_mapping'];
    $query  = "SELECT * FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
    $sth = la_do_query($query, array($form_id), $dbh);
    $row = la_do_fetch_result($sth);
    $autoMappingFormSettings = $row['form_enable_auto_mapping'];

    $response = ($autoMappingMainSettings == 1 && $autoMappingFormSettings == 1) ? "auto-mapping is enabled" : "auto-mapping is disabled";

    echo json_encode($response);
    die();
}

if($task == "lock or unlock fields") {
    $currentURL                    = $data->currentURL;
    $usersEmail                    = $data->email;
    $elementMachineCodesOnThisPage = getAllOfTheMachineCodesOnThisPage($dbh, $form_id, $data->elementMachineCodesOnThisPage);
    $eachFormInThisEntity          = getEachFormIdInThisEntity($dbh, $form_id, $entity_id);
    
    $resultObject                           = new stdClass();
    $resultObject->unlockFieldsFromThisForm = unlockFieldsFromThisForm($dbh, $usersEmail, $elementMachineCodesOnThisPage, $eachFormInThisEntity, $form_id);
    $resultObject->thisPagesLocks           = thisPagesLocks($dbh, $usersEmail, $elementMachineCodesOnThisPage, $form_id, $entity_id);

    echo json_encode($resultObject);
    die();
}

if($task == "prefillFieldValues") {
    updateElementMachineCodes($dbh, $form_id, $entity_id, $data);
    $formIdsInThisEntity           = getEachFormIdInThisEntity($dbh, $form_id, $entity_id);
    $elementMachineCodesOnThisPage = getAllOfTheMachineCodesOnThisPage($dbh, $form_id, $data->elementMachineCodesOnThisPage);
    $elementMachineCodesWithInfo   = getElementMachineCodeInfo($dbh, $form_id, $formIdsInThisEntity, $elementMachineCodesOnThisPage);    

    echo json_encode($elementMachineCodesWithInfo);
    die();
}

if($task == "map values to other entries") {
    updateElementMachineCodes($dbh, $form_id, $entity_id, $data);
    $codeValueMap                    = $data->codeValueMap; // vars have same values because this one gets modified
    $codeValueMap2                   = $data->codeValueMap2; // but this doesn't get modified
    $typeOfMatrixFields              = $data->typeOfMatrixFields; // but this doesn't get modified
    $disabledFields                  = $data->disabledFields;
    $elementMachineCodesOnThisPage   = $data->elementMachineCodesOnThisPage;
    $useremail   = $data->useremail;
    $formsIdsBelongingToThisEntity   = getEachFormIdInThisEntity($dbh, $form_id, $entity_id);
    $elementsInEntityThatContainCode = array();
    $objOfElementsOnThisFormWithCode = new stdClass();

    // make sure there are actually other forms that we can map to
    if(count($formsIdsBelongingToThisEntity) < 2) { // if only one form belongs to this entity
        unlockFieldsAfterSubmit($dbh, $elementMachineCodesOnThisPage, $useremail, $form_id);
        echo(json_encode("There aren't any other forms that auto-mapping can map to."));
        die();
    }

    // get all elements which contain an element_machine_code, for all forms in this entity (can take a while)
    // *** we can add in "WHERE element_machine_code = $element_machine_code[0] OR element_machine_code = $element_machine_code[1]" to optimize further
    // $query = "SELECT element_machine_code, element_id, element_type, form_id, element_matrix_allow_multiselect FROM ap_form_elements WHERE LENGTH(element_machine_code) > 0 AND element_type != 'file' AND (form_id = ".$formsIdsBelongingToThisEntity[0]."";
    // for($i=1; $i<count($formsIdsBelongingToThisEntity); $i++) { // i=1 because we already provided the first form_id in the line above
    //     $query = $query." OR form_id = ".$formsIdsBelongingToThisEntity[$i]; // attach each form_id belonging to this entity to the end of our query
    // }
    // $query = $query.")";
    $imploded_ids = implode("','", $formsIdsBelongingToThisEntity);
    $query = "SELECT element_machine_code, element_id, element_type, form_id, element_matrix_allow_multiselect FROM ap_form_elements WHERE LENGTH(`element_machine_code`) > 0 AND `element_type` != 'file' AND `form_id` IN (?)";
    $sth = la_do_query($query, array($imploded_ids), $dbh);

    // foreach($dbh->query($query) as $row) {
    while ($row = la_do_fetch_result($sth)) {
        // create an object containing the element data
        $object                                   = new stdClass();
        $object->element_machine_code             = $row["element_machine_code"];
        $object->element_id                       = $row["element_id"];
        $object->element_type                     = $row["element_type"];
        $object->form_id                          = $row["form_id"];
        $object->element_matrix_allow_multiselect = $row['element_matrix_allow_multiselect'];

        // store that object inside the array
        array_push($elementsInEntityThatContainCode, $object);

        // store information of all elements on this form inside of an object
        if($row['form_id'] == $form_id) {
            $element_id                                   = "element_".$row['element_id']; // change the formatting of the element_id
            $object->element_id                           = $element_id;
            $objOfElementsOnThisFormWithCode->$element_id = $object;
        }
    }

    // *******************************************************************
    // ***************** FOR EACH ELEMENT ON THIS FORM *******************
    // *******************************************************************

    $arrCodeValueMapKeyNames = array_keys(get_object_vars($codeValueMap));

    for($i=0; $i<count($arrCodeValueMapKeyNames); $i++) {
        $object                      = new stdClass();
        $element_id_original         = $arrCodeValueMapKeyNames[$i];
        $object->element_id_original = $element_id_original;

        // *******************************************************************
        // *********** MAKE SURE ELEMENT_ID IS FORMATTED PROPERLY ************
        // *******************************************************************

        // if element_id = element_1_2 -> turn drop the _2 and leave as element_1
        $element_id = explode("_", $arrCodeValueMapKeyNames[$i]);
        if(count($element_id) == 3) { // element_id = element_x_x
            $element_id = $element_id[0]."_".$element_id[1];
        } else { // element_id = element_x
            $element_id = $arrCodeValueMapKeyNames[$i];
        }

        // if this element does not contain a element_machine_code, skip this iteration of the loop, and move on to the next element
        if(property_exists($objOfElementsOnThisFormWithCode, $element_id) == false) {
            continue;
        }

        // use the now formatted version of the element_id to lookup the element_type and element_machine_code
        $element_type                     = $objOfElementsOnThisFormWithCode->$element_id->element_type;
        $element_machine_code             = $objOfElementsOnThisFormWithCode->$element_id->element_machine_code;
        $element_matrix_allow_multiselect = $objOfElementsOnThisFormWithCode->$element_id->element_matrix_allow_multiselect;

        // find which other forms this element_machine_code exists for this particular company id
        $formsIdsBelongingToThisEntityImploded =  implode(",", $formsIdsBelongingToThisEntity);
        $arrayOfFormIdsWhereThisCodeExists = array(); // this takes a while, we should find these forms somehow from the already existing objects

        $imploded_ids = implode("','", $formsIdsBelongingToThisEntityImploded);
        $query = "SELECT `form_id` from `ap_form_elements` WHERE `element_machine_code` = ? AND form_id IN (?)";
        $sth = la_do_query($query, array($element_machine_code, $formsIdsBelongingToThisEntityImploded), $dbh);
        // foreach($dbh->query("SELECT `form_id` from `ap_form_elements` WHERE `element_machine_code` = '$element_machine_code' AND form_id IN ({$formsIdsBelongingToThisEntityImploded})") as $row) {
        while ($row = la_do_fetch_result($sth)) {
            array_push($arrayOfFormIdsWhereThisCodeExists, $row['form_id']);
        }

        // *******************************************
        // *********** STORE ELEMENT INFO ************
        // *******************************************
        
        $object->otherFormsWhereCodeExists = $arrayOfFormIdsWhereThisCodeExists;
        $object->element_id_formatted      = $element_id;
        $object->element_type              = $element_type;
        $object->element_machine_code      = $element_machine_code;
        $element_value                     = $codeValueMap->$element_id_original; // elements not in an if statement below are left as is

        // ************************************************************************
        // *********** MAKE SURE ELEMENT VALUES ARE FORMATTED PROPERLY ************
        // ************************************************************************

        if($element_type == "phone") {
            $id_1          = $element_id."_1";
            $id_2          = $element_id."_2";
            $id_3          = $element_id."_3";

            // if usa number
            if(!empty($codeValueMap2->$id_2)) {
                // "element_1_1 (123)"."element_1_2 (456)"."element_1_3 (7890)" = "1234567890"
                $element_value = $codeValueMap2->$id_1.$codeValueMap2->$id_2.$codeValueMap2->$id_3;
            } else { // if international number
                $element_value = $codeValueMap2->$element_id_original;
            }
        }

        if($element_type == "time") {
            // "element_1_1 (12)"."element_1_2 (30)"."element_1_3 (AM)" = "0:30:00"
            // "element_1_1 (5)"."element_1_2 (30)"."element_1_3 (PM)"  = "5:30:00" -> yes only one first zero
            // "element_1_1 (05)"."element_1_2 (30)"."element_1_3 (PM)" = "17:30:00"

            $id_1 = $element_id."_1";
            $id_2 = $element_id."_2";
            $id_3 = $element_id."_3";
            $id_4 = $element_id."_4";

            $value_1 = $codeValueMap2->$id_1;
            $value_2 = $codeValueMap2->$id_2;
            $value_3 = "";
            $value_4 = "";

            if(!empty($codeValueMap2->$id_3)) {
                $value_3 = $codeValueMap2->$id_3;
            } else {
                $value_3 = "00";
            }

            if(!empty($codeValueMap2->$id_4)) {
                $value_4 = $codeValueMap2->$id_4;
            } else {
                $value_4 = "";
            }

            // convert to military time
            if($value_4 == "PM") {
                if($value_1 < 12) {
                    $value_1 = $value_1 + 12;
                }
            }

            if($value_4 == "AM") {
                if($value_1 == "12") {
                    $value_1 = "0";
                }
                if(!empty($value_1[0]) && !empty($value_1[1]) && $value_1[0] == "0") { // e.g. if == "02" instead of == "2"
                    if(!empty($value_1[1])) {
                        $value_1 = $value_1[1];
                    }
                }
            }

            $element_value = $value_1.":".$value_2.":".$value_3;
        }

        if($element_type == "radio") {
            // "element_1_1 (X) (false)"."element_1_2 (X) (false)"."element_1_3 (X) (true)" = "3"
            $index      = 1;
            $element_id = $element_id."_".$index;

            while(property_exists($codeValueMap2, $element_id) && $codeValueMap2->$element_id != true) { // iterate through all radio buttons in this element until we find the one that is "true"
                $element_id = substr_replace($element_id ,"", -1); // drop last character (index) from the id
                $index      = $index + 1; // increment
                $element_id = $element_id.$index; // add new character (index) onto the id
            }

            if(property_exists($codeValueMap2, $element_id) == false) {
                $element_value = false;
                $element_other = "element_".explode("_", $element_id_original)[1]."_other";
                if(property_exists($codeValueMap2, $element_other) && $codeValueMap2->$element_other !== "") {
                    $element_value = "0"; // = 0 if other and other != ""
                } else {
                    $element_value = false; // = "" if no other or other = ""
                }
            } else {
                $element_value = $index; // value = that elements index (starting at 1)
            }

            if(strpos($element_id_original, 'other') !== false) {
                if(property_exists($codeValueMap2, $element_id_original) !== false) {
                    $element_value = $codeValueMap2->$element_id_original; // value = that elements index (starting at 1)
                } else {
                    $element_value = "";
                }
            }
        }

        if($element_type == "matrix") {
            if ($typeOfMatrixFields->$element_id_original == "radio") { // radio
                // "element_1_1 (X) (false)"."element_1_2 (X) (false)"."element_1_3 (X) (true)" = "3"
                $index      = 1;
                $element_id = $element_id."_".$index; // e.g. element_5_1 || element_2_3
    
                while(property_exists($codeValueMap2, $element_id) && $codeValueMap2->$element_id != true) { // iterate through all radio buttons in this element until we find the one that is "true"
                    $index      = $index + 1; // increment
                    $element_id = substr_replace($element_id ,"", -1); // drop last character (index) from the id
                    $element_id = $element_id.$index; // add new character (index) onto the id
                }
    
                if(property_exists($codeValueMap2, $element_id) == false) {
                    $element_value = false;
                } else {
                    $element_value = $index; // value = that elements index (starting at 1)
                }
            } else { // matrix select multiple (checkboxes)
                $element_value = $codeValueMap2->$element_id_original;

                if($element_value == false) { // db expects not checked to = blank
                    $element_value = "";
                } else {
                    $element_value = substr($element_id_original, -1); // index
                }
            }
        }

        if($element_type == "date") {
            // "element_1_1 (08)"."element_1_2 (25)"."element_1_3 (2019)" = "2019-08-25"
            $id_1          = $element_id."_1";
            $id_2          = $element_id."_2";
            $id_3          = $element_id."_3";
            $element_value = $codeValueMap2->$id_3."-".$codeValueMap2->$id_1."-".$codeValueMap2->$id_2;
            if($element_value == "--") {
                $element_value = "";
            }
        }
        
        if($element_type == "europe_date") {
            // "element_1_1 (08)"."element_1_2 (25)"."element_1_3 (2019)" = "2019-08-25"
            $id_1          = $element_id."_1";
            $id_2          = $element_id."_2";
            $id_3          = $element_id."_3";
            $element_value = $codeValueMap2->$id_3."-".$codeValueMap2->$id_2."-".$codeValueMap2->$id_1;
            if($element_value == "--") {
                $element_value = "";
            }
        }

        if($element_type == "money") {
            // "price_1 (12)"."price_2 (50)" = "12.50"
            $id_1 = $element_id."_1";
            $id_2 = $element_id."_2";

            if(empty($codeValueMap2->$id_2)) { // currency type = yen (yen is the only currency we offer that doesn't have a second field)
                $element_value = $codeValueMap2->$element_id_original;
            } else {
                $element_value = $codeValueMap2->$id_1.".".$codeValueMap2->$id_2;
            }

            if($element_value == ".") { // if empty, set change value from "." to ""
                $element_value = "";
            }
        }

        if($element_type == "checkbox" && $element_value == false) {
            $element_value = "";
        }

        if($element_type == "textarea" && $element_value == "<p><br></p>") {
            $element_value = "";
        }

        if($element_type == "file") {
            //$element_value = trim($element_value);
            continue;
        }

        // *********************************************
        // *********** STORE ELEMENT VALUES ************
        // *********************************************

        $object->element_value              = $element_value;
        $codeValueMap->$element_id_original = $object;
    }

    // **************************************************************************
    // *********** UPDATE DATABASE ENTRIES USING ELEMENT INFO/VALUES ************
    // **************************************************************************

    $field_code_queries = array();
    // print_r($codeValueMap);
    // die();

    foreach($codeValueMap as $key) {
        if(empty($key->element_id_original)) { // if this key does not contain element_id_original, element_machine_code must not exist on this element, so skip this iteration of the loop, and move on to the next element
            continue;
        }

        $element_id_original       = $key->element_id_original;
        $element_machine_code      = $key->element_machine_code;
        $element_type              = $key->element_type;
        $element_value             = $key->element_value;
        $otherFormsWhereCodeExists = $key->otherFormsWhereCodeExists;
        $like                      = "%".substr($element_id_original, -1);

        if(in_array($element_machine_code, $disabledFields)) {
            continue; // dont map disabled fields - go to next iteration
        }

        for($i=0; $i<count($otherFormsWhereCodeExists); $i++) {
            $database_table_name = "ap_form_".$otherFormsWhereCodeExists[$i];
            
            // $doesTableExist = $dbh->query("SHOW TABLES FROM `".LA_DB_NAME."` WHERE `Tables_in_".LA_DB_NAME."` LIKE '$database_table_name'")->fetch();
            $query = "SHOW TABLES FROM `".LA_DB_NAME."` WHERE `Tables_in_".LA_DB_NAME."` LIKE ?";
            $sth = la_do_query($query, array($database_table_name), $dbh);
            $doesTableExist = la_do_fetch_result($sth);

            if($doesTableExist == false) {
                continue;
            }

            // this first if does not include 'radio' because 'radio' needs to run twice, to catch BOTH 'multiple choice' and 'matrix' elements
            if(
                $element_type != "simple_name" &&
                $element_type != "address" &&
                $element_type != "phone" &&
                $element_type != "time" &&
                $element_type != "number" &&
                $element_type != "url" &&
                $element_type != "textarea" &&
                $element_type != "money" &&
                $element_type != "date" &&
                $element_type != "select" &&
                $element_type != "email" &&
                $element_type != "text" &&
                $element_type != "matrix" &&
                $element_type != "file" &&
                $element_type != "radio"
                ) {
                $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                $params = array($element_value, $element_machine_code, $like, $entity_id);
                la_do_query($query, $params, $dbh);
            }

            if(
                $element_type == "phone" ||
                $element_type == "time" ||
                $element_type == "number" ||
                $element_type == "url" ||
                $element_type == "textarea" ||
                $element_type == "money" ||
                $element_type == "date" ||
                $element_type == "select" ||
                $element_type == "signature" ||
                $element_type == "text" ||
                $element_type == "email"
            ) {
                //flipping status indicators
                if($element_type == "text" || $element_type == "textarea" || $element_type == "signature" || $element_type == "select") {
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($element_machine_code, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $element_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }
                }
                $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ?";  
                $params = array($element_value, $element_machine_code, $entity_id);
                la_do_query($query, $params, $dbh);
            }

            // the element_types below require their own custom query

            if($element_type == "file") {
                if(substr_count($element_id_original, "_") == 1) {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ?";
                    $params = array($element_value, $element_machine_code, $entity_id);
                    la_do_query($query, $params, $dbh);
                } 
            }

            // matrix
            if($element_type == "matrix") { // radio buttons
                if ($typeOfMatrixFields->$element_id_original == "radio") { // radio
                    //flipping status indicators
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($element_machine_code, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $element_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ?";
                    $params = array($element_value, $element_machine_code, $entity_id);
                    la_do_query($query, $params, $dbh);
                } else { // matrix select multiple - checkboxes
                    //flipping status indicators
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($element_machine_code, $like, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $element_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }

                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, $like, $entity_id);
                    la_do_query($query, $params, $dbh);
                }
            }

            // simple_name
            if($element_type == "simple_name") {
                if(substr($element_id_original, -1) == "1") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_1', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "2") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_2', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
            }

            // address
            if($element_type == "address") {
                if(substr($element_id_original, -1) == "1") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_1', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "2") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_2', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "3") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_3', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "4") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_4', $entity_id);
                    la_do_query($query, $params, $dbh);

                    $query = "UPDATE {$database_table_name} SET field_score = '$element_value' WHERE element_machine_code = '$element_machine_code' AND field_name LIKE '%_%_4' AND company_id = {$entity_id}";  
                    // $dbh->query($query);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "5") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_5', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
                if(substr($element_id_original, -1) == "6") {
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";  
                    $params = array($element_value, $element_machine_code, '%_%_6', $entity_id);
                    la_do_query($query, $params, $dbh);
                }
            }
            
            // radio
            if($element_type == "radio") { // multiple choice
                $element_suffix = explode("_", $element_id_original)[2];
                if($element_suffix == "other") {
                    $like = '%_%_'.$element_suffix;
                    //flipping status indicators
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($element_machine_code, $like, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $element_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND field_name LIKE ? AND company_id = ?";
                    $params = array($element_value, $element_machine_code, $like, $entity_id);
                    la_do_query($query, $params, $dbh);                    
                } else {
                    //flipping status indicators
                    $query_existing_value = "SELECT field_name, data_value FROM {$database_table_name} WHERE element_machine_code = ? AND company_id = ?";
                    $sth_existing_value = la_do_query($query_existing_value, array($element_machine_code, $entity_id), $dbh);
                    while ($row_existing_value = la_do_fetch_result($sth_existing_value)) {
                        if($row_existing_value) {
                            //if the element has former data saved in the database
                            $string_submitted = str_replace("<p><br></p>", "<p>&nbsp;</p>", $element_value);
                            $string_existing = str_replace("<p><br></p>", "<p>&nbsp;</p>", $row_existing_value["data_value"]);
                            if(preg_replace( "/\r|\n/", "", $string_submitted) != preg_replace( "/\r|\n/", "", $string_existing)) {
                                $tmp_element_id = explode("_", $row_existing_value["field_name"])[1];
                                
                                $query_status_2 = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `element_id` = ? AND `company_id` = ?";
                                la_do_query($query_status_2, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id), $dbh);
                                $query_status_3 = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `indicator`) VALUES (null, ?, ?, ?, ?)";
                                la_do_query($query_status_3, array($otherFormsWhereCodeExists[$i], $tmp_element_id, $entity_id, 2), $dbh);
                            }
                        }
                    }
                    $query = "UPDATE {$database_table_name} SET data_value = ? WHERE element_machine_code = ? AND company_id = ?";
                    $params = array($element_value, $element_machine_code, $entity_id);
                    la_do_query($query, $params, $dbh);
                }
            }

            // ******************************************************************************
            // *********** update field_scores (matrix, checkbox, select, radio) ************
            // ******************************************************************************

            if($element_type == "matrix") {
                if ($typeOfMatrixFields->$element_id_original == "checkbox") { // find type of matrix
                    $element_type = "checkbox"; // checkbox matrix
                }
            }

            if($element_type == "matrix" || $element_type == "select" || $element_type == "radio") {
                $element_id  = explode("_", $element_id_original)[1]; // "element_7" = "7"
                // $field_score = $dbh->query("SELECT option_value FROM ap_element_options WHERE form_id = '$form_id' AND element_id = '$element_id' AND option_id = '$element_value'")->fetch()['option_value'];
                $query = "SELECT option_value FROM ap_element_options WHERE `form_id` = ? AND `element_id` = ? AND `option_id` = ?";
                $sth = la_do_query($query, array($form_id, $element_id, $element_value), $dbh);
                $row = la_do_fetch_result($sth);
                $field_score = $row['option_value'];

                $like        = "%".$element_id;
                $query       = "UPDATE {$database_table_name} SET field_score = ? WHERE element_machine_code = ? and field_name LIKE ? AND company_id = ?";
                $params = array($field_score, $element_machine_code, $like, $entity_id);
                la_do_query($query, $params, $dbh);   
            }
            
            if($element_type == "checkbox") {
                $element_id  = explode("_", $element_id_original)[1]; // "element_7" || "element_7_1" = "7"
                $option      = explode("_", $element_id_original)[2];
                $field_score = "0"; // blank by default
                if($element_value == 1) {
                    // $field_score = $dbh->query("SELECT option_value FROM ap_element_options WHERE form_id = '$form_id' AND element_id = '$element_id' AND option_id = '$option'")->fetch()['option_value'];
                    $query = "SELECT option_value FROM ap_element_options WHERE `form_id` = ? AND `element_id` = ? AND `option_id` = ?";
                    $sth = la_do_query($query, array($form_id, $element_id, $option), $dbh);
                    $row = la_do_fetch_result($sth);
                    $field_score = $row['option_value'];
                }
                $like  = '%_%_'.explode("_", $element_id_original)[2];
                $query = "UPDATE {$database_table_name} SET field_score = ? WHERE element_machine_code = ? and field_name LIKE ? AND company_id = ?";
                $params = array($field_score, $element_machine_code, $like, $entity_id);
                la_do_query($query, $params, $dbh);
            }

        }
    }

    unlockFieldsAfterSubmit($dbh, $elementMachineCodesOnThisPage, $useremail, $form_id);

    echo json_encode("finished mapping values to the database");
    die();
}

// **********************************************************
// ******************** HELPER FUNCTIONS ********************
// **********************************************************
/*
function getEachFormIdInThisEntity ($dbh, $form_id, $entity_id) {
    $formIdsInThisEntity = array();
    $query1 = "SELECT DISTINCT `".LA_TABLE_PREFIX."ask_client_forms`.`form_id` 
                  FROM `".LA_TABLE_PREFIX."forms`
                  LEFT JOIN `".LA_TABLE_PREFIX."ask_client_forms` ON (`".LA_TABLE_PREFIX."forms`.`form_id` = `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`)
                  WHERE `".LA_TABLE_PREFIX."ask_client_forms`.`client_id` = {$entity_id}
                  AND `".LA_TABLE_PREFIX."forms`.`form_active` = 1
                  AND `".LA_TABLE_PREFIX."forms`.`form_private_form_check` = 0
                  AND `".LA_TABLE_PREFIX."ask_client_forms`.`form_id` = `".LA_TABLE_PREFIX."forms`.`form_id`";
        
        $sth1 = la_do_query($query1, array(), $dbh);

        while($row = la_do_fetch_result($sth1)){
            $form_id_1               = $row['form_id'];
            $autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id_1")->fetch()['form_enable_auto_mapping'];
            if($autoMappingFormSettings == 1) {
                array_push($formIdsInThisEntity, $form_id_1);        
            }
        }

    return $formIdsInThisEntity;
}*/


function getEachFormIdInThisEntity ($dbh, $form_id, $entity_id) {
    $formIdsInThisEntity = array();

    $sth = la_do_query("SELECT form_id FROM ap_forms WHERE `form_for_selected_company` = ?", array($entity_id), $dbh);
    // foreach($dbh->query("SELECT form_id FROM ap_forms WHERE form_for_selected_company = $entity_id") as $row) {
    while ($row = la_do_fetch_result($sth)) {
        // filter our forms that have auto-mapping disabled in the settings
        $form_id_1               = $row['form_id'];
        // $autoMappingFormSettings = $dbh->query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = $form_id_1")->fetch()['form_enable_auto_mapping'];
        $sth = la_do_query("SELECT `form_enable_auto_mapping` FROM ap_forms WHERE form_id = ?", array($form_id_1), $dbh);
        $row = la_do_fetch_result($sth);
        $autoMappingFormSettings = $row['form_enable_auto_mapping'];
        if($autoMappingFormSettings == 1) {
            array_push($formIdsInThisEntity, $form_id_1);        
        }
    }
    return $formIdsInThisEntity;
}

function getAllOfTheMachineCodesOnThisPage($dbh, $form_id, $elementMachineCodesOnThisPage) {
    // add matrix field's element_machine_codes to element machine codes on this page, even though matrix fields will be for all pages on this form, not just this page, since were pulling from db
    
    $sth = la_do_query("SELECT element_machine_code FROM ap_form_elements WHERE `element_type` = 'matrix' AND `form_id` = ?", array($form_id), $dbh);
    // foreach($dbh->query("SELECT element_machine_code FROM ap_form_elements WHERE element_type = 'matrix' AND form_id = $form_id") as $row) { // for matrix fields only since those codes we are unable to scrape from the DOM
    while ($row = la_do_fetch_result($sth)) {
        if(in_array($row['element_machine_code'], $elementMachineCodesOnThisPage) == false) {
            if($row['element_machine_code'] !== "") {
                array_push($elementMachineCodesOnThisPage, $row['element_machine_code']);
            }
        }
    }

    return $elementMachineCodesOnThisPage;
}

function getElementMachineCodeInfo($dbh, $form_id, $formIdsInThisEntity, $elementMachineCodesOnThisPage) {  
    $elementMachineCodesWithInfo = new stdClass();

    for($i=0; $i<count($elementMachineCodesOnThisPage); $i++) {
        $count                             = 0;
        $element_machine_code              = $elementMachineCodesOnThisPage[$i];
        $object                            = new stdClass();
        $object->forms_where_code_is_found = new stdClass();
        $object->element_machine_code      = $element_machine_code;
        $object->prefillValues             = array();

        // $query = "SELECT element_machine_code, element_id, element_type, form_id, element_matrix_allow_multiselect FROM ap_form_elements WHERE element_machine_code = '$element_machine_code' AND (form_id = ".$formIdsInThisEntity[0]."";
        // for($j=1; $j<count($formIdsInThisEntity); $j++) {
        //     $query = $query." OR form_id = ".$formIdsInThisEntity[$j];
        // }
        // $query = $query.")";
        $imploded_ids = implode("','", $formIdsInThisEntity);
        $query = "SELECT element_machine_code, element_id, element_type, form_id, element_matrix_allow_multiselect FROM ap_form_elements WHERE `element_machine_code` = ? AND form_id IN (?)";
        $sth = la_do_query($query, array($element_machine_code, $imploded_ids), $dbh);

        // foreach($dbh->query($query) as $row) {
        while ($row = la_do_fetch_result($sth)) {
            $count                                                           = $count + 1;
            $count2                                                          = 0;
            $this_form_id                                                    = $row['form_id'];
            $field_code                                                      = "code_".$row['element_id'];
            $object->element_type                                            = $row["element_type"];
            $object->element_id                                              = $row["element_id"];
            $object->element_matrix_allow_multiselect                        = $row['element_matrix_allow_multiselect'];
            $object->forms_where_code_is_found->$count                       = new stdClass();
            $object->forms_where_code_is_found->$count->form_id              = $this_form_id;
            $object->forms_where_code_is_found->$count->field_code           = $field_code;
            $object->forms_where_code_is_found->$count->element_machine_code = $element_machine_code;
            $field_names_object                                              = new stdClass();

            $query = "SELECT field_name, data_value FROM ap_form_{$this_form_id} WHERE element_machine_code = ?";
            $sth = la_do_query($query, array($element_machine_code), $dbh);
            
            // foreach($dbh->query("SELECT field_name, data_value FROM ap_form_{$this_form_id} WHERE element_machine_code = '$element_machine_code'") as $row) {
            while ($row1 = la_do_fetch_result($sth)) {
                $count2                          = $count2 + 1;
                $field_name                      = $row1['field_name'];
                $result_object                   = new stdClass();
                $result_object->field_name       = $row1['field_name'];
                $result_object->data_value       = $row1['data_value'];
                $field_names_object->$field_name = $result_object;

                array_push($object->prefillValues, $result_object->data_value);
            }
            
            $object->forms_where_code_is_found->$count->field_names = $field_names_object;
        }

        $elementMachineCodesWithInfo->$element_machine_code = $object;
    }

    return $elementMachineCodesWithInfo;
}

function updateElementMachineCodes($dbh, $form_id, $entity_id, $data) {
    $formIdsInThisEntity           = getEachFormIdInThisEntity($dbh, $form_id, $entity_id);
    // print_r($formIdsInThisEntity);
    if(count($formIdsInThisEntity) < 1) { // if only one form belongs to this entity
        echo(json_encode("This entity does not have any existing entry."));
        die();
    }
    $elementMachineCodesOnThisPage = getAllOfTheMachineCodesOnThisPage($dbh, $form_id, $data->elementMachineCodesOnThisPage);
    $elementMachineCodesToUpdate   = new stdClass();

    for($i=0; $i<count($elementMachineCodesOnThisPage); $i++) {
        $element_machine_code                                                          = $elementMachineCodesOnThisPage[$i];
        $elementMachineCodesToUpdate->$element_machine_code                            = new stdClass();
        $elementMachineCodesToUpdate->$element_machine_code->forms_where_code_is_found = new stdClass();

        // $query = "SELECT element_machine_code, element_id, form_id FROM ap_form_elements WHERE element_machine_code = '$element_machine_code' AND (form_id = ".$formIdsInThisEntity[0]."";
        // for($j=1; $j<count($formIdsInThisEntity); $j++) {
        //     $query = $query." OR form_id = ".$formIdsInThisEntity[$j];
        // }
        // $query = $query.")";
        $imploded_ids = implode("','", $formIdsInThisEntity);
        $query = "SELECT element_machine_code, element_id, form_id FROM ap_form_elements WHERE element_machine_code = ? AND form_id IN (?)";
        $sth = la_do_query($query, array($element_machine_code, $imploded_ids), $dbh);
        // echo $query;

        // foreach($dbh->query($query) as $row) {
        while ($row = la_do_fetch_result($sth)) {
            $this_element_machine_code                                                                                               = $row['element_machine_code'];
            $this_form_id                                                                                                            = $row['form_id'];
            $elementMachineCodesToUpdate->$this_element_machine_code->forms_where_code_is_found->$this_form_id                       = new stdClass();
            $elementMachineCodesToUpdate->$this_element_machine_code->forms_where_code_is_found->$this_form_id->element_machine_code = $this_element_machine_code;
            $elementMachineCodesToUpdate->$this_element_machine_code->forms_where_code_is_found->$this_form_id->form_id              = $row['form_id'];
            $elementMachineCodesToUpdate->$this_element_machine_code->forms_where_code_is_found->$this_form_id->field_code           = "code_".$row['element_id'];
        }
    }

    // run queries
    foreach($elementMachineCodesToUpdate as $object) {
        $forms_where_code_is_found = $object->forms_where_code_is_found;

        foreach($forms_where_code_is_found as $form) {
            $form_id_2 = $form->form_id;
            // $doesTableExist = $dbh->query("SHOW TABLES FROM `".LA_DB_NAME."` WHERE `Tables_in_".LA_DB_NAME."` LIKE 'ap_form_".$form_id_2."'")->fetch();
            $sth = la_do_query("SHOW TABLES FROM `".LA_DB_NAME."` WHERE `Tables_in_".LA_DB_NAME."` LIKE 'ap_form_".$form_id_2."'", array(), $dbh);
            $doesTableExist = la_do_fetch_result($sth);

            // if table exists
            if($doesTableExist !== false) {
                // $doesColumnExist = $dbh->query("SHOW COLUMNS FROM `ap_form_".$form_id_2."` LIKE 'element_machine_code'")->fetch();
                $sth = la_do_query("SHOW COLUMNS FROM `ap_form_".$form_id_2."` LIKE 'element_machine_code'", array(), $dbh);
                $doesColumnExist = la_do_fetch_result($sth);

                // if column doesn't exist
                if($doesColumnExist == false) {
                    // create column
                    // $dbh->query("ALTER TABLE `ap_form_".$form_id_2."` ADD COLUMN `element_machine_code` varchar(100) NOT NULL DEFAULT 'none set'");
                    la_do_query("ALTER TABLE `ap_form_".$form_id_2."` ADD COLUMN `element_machine_code` varchar(100) NOT NULL DEFAULT 'none set'", array(), $dbh);
                }
                // set element_machine_code value
                //adding machine code with la_process_form now
                /*$field_code           = $form->field_code;
                $element_machine_code = $form->element_machine_code;
                $query = "UPDATE ap_form_".$form_id_2." SET element_machine_code = '$element_machine_code' WHERE field_code = '$field_code'";
                $dbh->query($query);*/
            }

        }
    }
}

function fieldsToLockOnThisPage($fieldsToLockOnThisPage) {

    return $fieldsToLockOnThisPage;
}

function unlockFieldsFromThisForm($dbh, $usersEmail, $elementMachineCodesOnThisPage, $eachFormInThisEntity, $form_id) {
    // create ap_blocked_form_fields table if not exists
    // $dbh->query("CREATE TABLE IF NOT EXISTS `ap_blocked_form_fields` (
    //     `id` int(11) NOT NULL auto_increment,
    //     `element_machine_code` varchar(250)  NOT NULL default '',
    //     `form_id_where_lock_originated` varchar(250)  NOT NULL default '',
    //     `entity_id` varchar(250)  NOT NULL default '',
    //     `users_email` varchar(250)  NOT NULL default '',
    //     PRIMARY KEY  (`id`)
    // )");

    la_do_query("CREATE TABLE IF NOT EXISTS `ap_blocked_form_fields` (
        `id` int(11) NOT NULL auto_increment,
        `element_machine_code` varchar(250)  NOT NULL default '',
        `form_id_where_lock_originated` varchar(250)  NOT NULL default '',
        `entity_id` varchar(250)  NOT NULL default '',
        `users_email` varchar(250)  NOT NULL default '',
        PRIMARY KEY  (`id`)
    ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;", array(), $dbh);

    // echo "DELETE FROM ap_blocked_form_fields WHERE form_id_where_lock_originated = {$form_id} AND users_email = '{$usersEmail}'";
    // die();
    //$dbh->query("DELETE FROM ap_blocked_form_fields WHERE form_id_where_lock_originated = {$form_id} AND users_email = '{$usersEmail}'");

    return "success";
}

function unlockFieldsAfterSubmit($dbh, array $fields, $usersEmail, $form_id) {
    //after lock delete notify other users that user has left the page
    $imploded_fields = implode("','", $fields);
    // $query = "DELETE FROM ap_blocked_form_fields WHERE element_machine_code IN ('{$imploded_fields}') AND form_id_where_lock_originated = {$form_id} AND users_email = '{$usersEmail}'";
    // $dbh->query($query);
    $query = "DELETE FROM ap_blocked_form_fields WHERE `element_machine_code` IN (?) AND `form_id_where_lock_originated` = ? AND `users_email` = ?";
    la_do_query($query, array($imploded_fields, $form_id, $usersEmail), $dbh);

    include $_SERVER['DOCUMENT_ROOT'].'/itam-shared/api/phpsocket.io/vendor/autoload.php';
    $emitter = new Emitter();
    $emitter->emit('unlock fields', array('fields'=> $fields, 'userEmail'=> $usersEmail, 'formId' => $form_id));
}

function thisPagesLocks($dbh, $usersEmail, $elementMachineCodesOnThisPage, $form_id, $entity_id) {
    $fieldsToLockOnThisPage = new stdClass();
    
    // dont (double) block codes, if they are already blocked. take each field code on this page
    for($i=0; $i<count($elementMachineCodesOnThisPage); $i++) {
        // check if its already blocked
        // $query = "SELECT element_machine_code, form_id_where_lock_originated, entity_id, users_email FROM ap_blocked_form_fields WHERE LENGTH(element_machine_code) > 0 AND (element_machine_code = '".$elementMachineCodesOnThisPage[0]."'";
        // for($i=1; $i<count($elementMachineCodesOnThisPage); $i++) { // i=1 because we already provided the first form_id in the line above
        //     $query = $query." OR element_machine_code = '".$elementMachineCodesOnThisPage[$i]."'"; // attach each form_id belonging to this entity to the end of our query
        // }
        // $query = $query.") AND entity_id = '".$entity_id."' AND form_id_where_lock_originated != {$form_id}";

        // echo $query;
        // echo "<br>";

        $imploded_ids = implode("','", $elementMachineCodesOnThisPage);
        $query = "SELECT element_machine_code, form_id_where_lock_originated, entity_id, users_email FROM ap_blocked_form_fields WHERE LENGTH(element_machine_code) > 0 AND element_machine_code IN (?) AND entity_id = ? AND form_id_where_lock_originated != ?";
        $sth = la_do_query($query, array($imploded_ids, $entity_id, $form_id), $dbh);
        
        // foreach($dbh->query($query) as $row) {
        while ($row = la_do_fetch_result($sth)) {
            // if code is already blocked
            if (in_array($row['element_machine_code'], $elementMachineCodesOnThisPage)) {
                // remove from array
                $index = array_search($row['element_machine_code'], $elementMachineCodesOnThisPage);
                unset($elementMachineCodesOnThisPage[$index]);

                $object = new stdClass();
                $object->form_id_where_lock_originated = $row['form_id_where_lock_originated'];
                $object->entity_id                     = $row['entity_id'];
                $object->users_email                   = $row['users_email'];

                $code = $row['element_machine_code'];
                $fieldsToLockOnThisPage->$code = $object;
            }
        }
    }

    // reindex array
    // $elementMachineCodesOnThisPage = array_values($elementMachineCodesOnThisPage);
    
    // if not blocked, then block it
   /* $codes_blocked = array();
    for($i=0; $i<count($elementMachineCodesOnThisPage); $i++) {
        $code = $elementMachineCodesOnThisPage[$i];
        $dbh->query("INSERT INTO ap_blocked_form_fields (element_machine_code, form_id_where_lock_originated, entity_id, users_email) VALUES ('$code', '$form_id', '$entity_id', '$usersEmail')");
        array_push($codes_blocked, $code);
    }*/    
    
    $codes_blocked = array();
    $query = "SELECT * FROM ap_blocked_form_fields WHERE LENGTH(element_machine_code) > 0 AND entity_id = ? AND form_id_where_lock_originated = ?";
    $sth = la_do_query($query,[$entity_id, $form_id],$dbh);
    
    while($row = la_do_fetch_result($sth)){
       array_push($codes_blocked, $row['element_machine_code']); 
    }


    
    $result                          = new stdClass();
    $result->fieldsLockedViaThisPage = $codes_blocked;
    // $result->fieldsLockedViaThisPage = [];
    // $result->fieldsToLockOnThisPage  = fieldsToLockOnThisPage($fieldsToLockOnThisPage);
    $result->fieldsToLockOnThisPage  = $fieldsToLockOnThisPage;

    return $result;
}

?>
