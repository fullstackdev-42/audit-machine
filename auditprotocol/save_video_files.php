<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://www.continuumgrc.com/
 
 More info at: http://www.continuumgrc.com/
 ********************************************************************************/
	
	ini_set('memory_limit', '-1');
	ini_set('post_max_size', '512M');
	
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/common-validator.php');
	require('includes/filter-functions.php');
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check for max_input_vars
	la_init_max_input_vars();

	$form_id  = (int) la_sanitize($_POST['form_id']);
	//when duplicating media player fields update the element_video_url for new field
	$duplicatedDraftFieldsStr  = la_sanitize($_POST['duplicatedDraftFields']);
	if( !empty($duplicatedDraftFieldsStr) )
		$duplicatedDraftFieldsObj = json_decode($duplicatedDraftFieldsStr);
		
	$form_path = $la_settings['upload_dir'] . '/form_' . $form_id;
	if (!file_exists($form_path))
		mkdir($form_path, 0777, true);
	
	$form_file_base_path = $la_settings['base_url'] . str_replace('./', '', $la_settings['upload_dir']) . '/form_' . $form_id;

	function add_duplicate_if_key_exist($duplicatedDraftFieldsObj, $key, $form_file_public_path, $form_id, $dbh) {
		if(property_exists($duplicatedDraftFieldsObj, $key)) {
	    	$imploded_value = $duplicatedDraftFieldsObj->{$key};
			if( strpos($imploded_value, ',') ) {
				$multipleDuplicates = explode(',', $imploded_value);
				foreach ($multipleDuplicates as $key => $value) {
					$query  = "UPDATE `" . LA_TABLE_PREFIX . "form_elements` SET `element_video_url` = ? WHERE `form_id` = ? AND element_id = ?";
	    			la_do_query($query, array($form_file_public_path, $form_id, $value), $dbh);
	    			add_duplicate_if_key_exist($duplicatedDraftFieldsObj, $value, $form_file_public_path, $form_id, $dbh);
				}
			} else {
				$query  = "UPDATE `" . LA_TABLE_PREFIX . "form_elements` SET `element_video_url` = ? WHERE `form_id` = ? AND element_id = ?";
	    			la_do_query($query, array($form_file_public_path, $form_id, $imploded_value), $dbh);
	    			add_duplicate_if_key_exist($duplicatedDraftFieldsObj, $imploded_value, $form_file_public_path, $form_id, $dbh);
			}
		}
    }

	if(!empty($_FILES) && is_writable($la_settings['upload_dir'])) {
        if(is_dir($form_path)) {
            foreach ($_FILES as $key => $file) {
                $file_type = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
                $file_path = $form_path . '/files/file_' . $key . '_' . $form_id . '.' . $file_type;
                $form_file_public_path = $form_file_base_path . '/files/file_' . $key . '_' . $form_id . '.' . $file_type;

                if (!file_exists($form_path . '/files'))
				    mkdir($form_path . '/files', 0777, true);
				
                if(file_exists($file_path))
                    unlink($file_path);
                

                move_uploaded_file($file['tmp_name'], $file_path);

                $query  = "UPDATE `" . LA_TABLE_PREFIX . "form_elements` SET `element_video_url` = ? WHERE `form_id` = ? AND element_id = ?";
                la_do_query($query, array($form_file_public_path, $form_id, $key), $dbh);

				add_duplicate_if_key_exist($duplicatedDraftFieldsObj, $key, $form_file_public_path, $form_id, $dbh);

            }
        }
    }


?>