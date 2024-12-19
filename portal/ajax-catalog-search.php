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
$userEntities = $_POST['user_entities'];
$universalFormStatement = $_POST['universal_form_statement'];
$appendWhereClause = $_POST['search_by'] == "title" ? " AND `".LA_TABLE_PREFIX."forms`.`form_name` LIKE CONCAT('%', ?, '%')" : "";
$bindParam = $_POST['search_by'] == "title" ? array(1, 0, la_sanitize($_POST['search_value'])) : array(1, 0);

$selectedEntity = $_SESSION['la_client_entity_id'];
if (is_numeric($selectedEntity)) {
    // This Ugly Query it the 'Universal Form' Project. It will:
    // 1) Grab All Forms That an entity has access to
    // 2) Grab All Forms that all entities can have access to
    // Because of the way ap_form_relation works, it has to be written this way because:
    // 1) All new Portal Users create their own entity. This entity does NOT get added
    // to the ap_form_relation table for some unexplainable reason. 
    // 2) A Form that has access for all entities is not in ap_form_relation table OR it is labeled as entity_id = 0 for some unexplainable reason.
    $sql = "SELECT `form_id` from `".LA_TABLE_PREFIX."entity_form_relation` 
    WHERE (entity_id != $selectedEntity AND entity_id != 0) 
    AND form_id 
    NOT IN 
    (
        SELECT form_id FROM ".LA_TABLE_PREFIX."entity_form_relation 
        WHERE (entity_id = 0) 
        OR (
            form_id IN (
                SELECT `form_id` from ".LA_TABLE_PREFIX."entity_form_relation 
                WHERE entity_id = $selectedEntity
            )
        )
    )";
    $sth3 = la_do_query($sql,array(),$dbh);
    $formIds = array();
    while($row = la_do_fetch_result($sth3)){    
        $formIds[] =  $row['form_id'];
    }
    if (count($formIds) > 0) {
        $string_form_ids = implode(',', $formIds);
        $universal_form_statement = "AND `form_id` NOT IN ($string_form_ids)";
    }
}

$query = "SELECT `form_id`, `form_name`, `form_description`, `form_theme_id` FROM ".LA_TABLE_PREFIX."forms WHERE `form_active` = ? AND form_name LIKE '%".$_POST['search_value']."%' $universal_form_statement";
$sth2 = $dbh->prepare($query);

try{
    $sth2->execute(array(1));
}catch(PDOException $e) {
    exit;
}

$count = $sth2->rowCount();     
$filtered_forms = [];
for($i=0;$i<$count;$i++){
    $form            = la_do_fetch_result($sth2);
    $form_name       = htmlspecialchars($form['form_name']);
    $form_id         = (int) $form['form_id'];
    $theme_id        = (int) $form['form_theme_id'];
    $formEntities = getFormAccessibleEntities($dbh, $form_id);

    $subscribedStatus = getEntititiesSubscribeStatus($dbh, $userEntities, $form_id);

    if(!in_array("0", $formEntities)){//echo "1";
        $hasAccess = false;
        
        foreach($userEntities as $k => $v){
            if(in_array($v, $formEntities)){
                $hasAccess = true;
            }
        }
        
        if($hasAccess){
            $filtered_forms[$i] = array(
                'form_id' => $form_id, 
                'form_name' => $form_name, 
                
                'form_theme_id' => $theme_id,
                'subscribe_status' => $subscribedStatus
            );  
        }
    } else {
        $filtered_forms[$i] = array(
                'form_id' => $form_id, 
                'form_name' => $form_name, 
                
                'form_theme_id' => $theme_id,
                'subscribe_status' => $subscribedStatus
            );  
    }
}

if($count > 0){
    if($_POST['search_by'] == "title"){
        echo json_encode(array("result" => $filtered_forms, 'forms_elements' => array(), "error" => 0, "message" => ""));
    } else if ($_POST['search_by'] == "element") {
        $form_id_arr = array_map(function($a){
            return $a['form_id'];
        }, $filtered_forms);				
        
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
            if(array_key_exists($user_filtered_forms_temp['form_id'], $user_filtered_forms)){
                array_push(
                    $user_filtered_forms[$user_filtered_forms_temp['form_id']], 
                    array(
                        'element_title' => $user_filtered_forms_temp['element_title'],
                        'element_type' => $user_filtered_forms_temp['element_type'], 
                        'element_position' => $user_filtered_forms_temp['element_position'], 
                        'element_page_number' => $user_filtered_forms_temp['element_page_number'],
                        'subscribe_status' => $subscribedStatus
                    )
                );
            }else{
                $user_filtered_forms[$user_filtered_forms_temp['form_id']] = array();
                array_push(
                    $user_filtered_forms[$user_filtered_forms_temp['form_id']], 
                    array(
                        'element_title' => $user_filtered_forms_temp['element_title'], 
                    'element_type' => $user_filtered_forms_temp['element_type'], 
                    'element_position' => $user_filtered_forms_temp['element_position'], 
                    'element_page_number' => $user_filtered_forms_temp['element_page_number'],
                    'subscribed_status' => $subscribedStatus
                    )
                );
            }
        }
        
        echo json_encode(array("result" => $filtered_forms, 'forms_elements' => $user_filtered_forms, "error" => 0, "message" => ""));
    }
}else{
    echo json_encode(array("result" => array(), 'forms_elements' => array(), "error" => 1, "message" => "<div class='middle_form_bar'><h3>No form found</h3><div style='height: 0px; clear: both;'></div></div>"));
}