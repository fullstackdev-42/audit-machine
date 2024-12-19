<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	
	require('includes/users-functions.php');
	require('includes/filter-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$form_file_name = la_sanitize($_POST['file_name']);
	$form_file_path = $la_settings['data_dir']."/temp/".$form_file_name;

	$response_data = new stdClass();
	$response_data->status  = "error";
	
	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";
		
		header("Location: restricted.php");
		exit;
	}

	if(file_exists($form_file_path)){
		$handle = @fopen($form_file_path, "r");
		
		if($handle) {
			//get new form_id
		    $query = "select max(form_id) max_form_id from ".LA_TABLE_PREFIX."forms";
			$params = array();
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			if(empty($row['max_form_id'])){
				$last_form_id = 10000;
			}else{
				$last_form_id = $row['max_form_id'];
			}
			
			$form_id = $last_form_id + rand(100,1000);

		    while (($line_data = fgets($handle)) !== false) {
				//parse the data        
		    	$data_obj = new stdClass();
		    	$data_obj = json_decode($line_data);

		    	$is_parse_success = false;

		    	if(!empty($data_obj->itauditmachine_version)){
		    		//compare the file first
		    		//if the file being imported is using newer version than the current itauditmachine version, stop the operation
		    		if(version_compare($la_settings['itauditmachine_version'], $data_obj->itauditmachine_version,">=")){
		    			$imported_form_version = $data_obj->itauditmachine_version;
		    			$imported_form_name	   = $data_obj->form_name;	
		    		}else{
		    			$response_data->status  = 'error';
						$response_data->message = 'The form is not compatible with your IT Audit Machine.<br/> Please update your IT Audit Machine and try again.';
						break;
		    		}
		    	}else if(!empty($data_obj->table_name)){
		    		$current_table_name = $data_obj->table_name;
		    		$is_parse_success = true;
		    	}else{
		    		//insert into tables
		    		$data_array 		 = array();
		    		$data_array 		 = get_object_vars($data_obj);
		    		
		    		$column_names_array  = array_keys($data_array);
		    		$column_names_joined = implode("`,`", $column_names_array);
		    		
		    		$params = array();
		    		$old_form_id = $data_array['form_id'];

		    		foreach ($column_names_array as $column_name) {
		    			if($current_table_name == 'report_elements' || $current_table_name == 'reports'){
		    				if($column_name == 'access_key' || $column_name == 'report_access_key'){
		    					$data_array[$column_name] = str_replace($old_form_id.'x', $form_id.'x', $data_array[$column_name]);
		    				}
		    			}

		    			$params[':'.$column_name] = $data_array[$column_name];
		    		}
		    		$param_names = implode(",", array_keys($params));
		    		$params[':form_id'] = $form_id;

		    		$query = "INSERT INTO ".LA_TABLE_PREFIX."{$current_table_name}(`{$column_names_joined}`) VALUES({$param_names})";
		    		la_do_query($query,$params,$dbh);

		    		$is_parse_success = true;
		    	}
		    }

		    if($is_parse_success){
		    	//start createing form table----------
		    	$query = "CREATE TABLE `".LA_TABLE_PREFIX."form_{$form_id}` (
  													`id` int(11) NOT NULL auto_increment,
  													`date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  													`date_updated` datetime default NULL,
  													`ip_address` varchar(15) default NULL,
  													`status` int(4) unsigned NOT NULL DEFAULT '1',
  													`resume_key` varchar(10) default NULL,
  													PRIMARY KEY (`id`)
  													) DEFAULT CHARACTER SET utf8;";
				$params = array();
				la_do_query($query,$params,$dbh);

				//Get the fields from ap_form_elements table with status = 1 and create the field column into the form's table
				$query = "SELECT 
			   					element_id,
			   					element_matrix_allow_multiselect 
			   				FROM 
			   					".LA_TABLE_PREFIX."form_elements 
			   			   WHERE 
			   			   		form_id = ? and element_type='matrix' and element_matrix_parent_id=0";
				$params = array($form_id);
				
				$sth = la_do_query($query,$params,$dbh);
				while($row = la_do_fetch_result($sth)){
					$matrix_multiselect_settings[$row['element_id']] = $row['element_matrix_allow_multiselect'];
				}
				
				
				$matrix_child_array = array();
				$query = "SELECT 
			   					element_id, element_type,
			   					element_constraint,element_position,
			   					element_matrix_parent_id,
			   					element_matrix_allow_multiselect,
			   					element_choice_has_other 
			   				FROM 
			   					".LA_TABLE_PREFIX."form_elements 
			   			   WHERE 
			   			   		form_id = ? and element_status=1 
			   			ORDER BY 
			   					element_position asc";
				$params = array($form_id);
				$sth = la_do_query($query,$params,$dbh);
				
			   	while($row = la_do_fetch_result($sth)){
					$element_type = $row['element_type'];
					$element_id	  = $row['element_id'];
					$element_matrix_parent_id 		  = $row['element_matrix_parent_id'];
					$element_matrix_allow_multiselect = $row['element_matrix_allow_multiselect'];
					$element_choice_has_other 		  = $row['element_choice_has_other'];
					
					if($element_type == 'checkbox'){
						//get all child element of the checkboxes
						$query = "select option_id from ".LA_TABLE_PREFIX."element_options where form_id = ? and element_id = ? and live = 1 order by option_id asc";
						$params_checkbox = array($form_id,$element_id);
						
						$sth2 = la_do_query($query,$params_checkbox,$dbh);
						while($row2 = la_do_fetch_result($sth2)){
							table_add_field($dbh,$form_id,$element_id,$element_type,$row2['option_id']);
						}
					}elseif($element_type == 'matrix'){
						
						//a matrix field can be a group of multiple choices or checkboxes
						//determine the matrix type
						if(empty($element_matrix_parent_id)){ //if this is the first row of the matrix
							$matrix_allow_multiselect = $element_matrix_allow_multiselect;
						}else{
							$matrix_allow_multiselect = $matrix_multiselect_settings[$element_matrix_parent_id];
							$matrix_child_array[$element_matrix_parent_id][] = $element_id; 
						}
					
						if(!empty($matrix_allow_multiselect)){ //if this is checkboxes matrix
							//get all child element of the checkboxes
							$query = "select option_id from ".LA_TABLE_PREFIX."element_options where form_id = ? and element_id = ? and live = 1 order by option_id asc";
							$params3 = array($form_id,$element_id);
							
							$sth3 = la_do_query($query,$params3,$dbh);
							while($row3 = la_do_fetch_result($sth3)){
								table_add_field($dbh,$form_id,$element_id,'checkbox',$row3['option_id']);
							}
						}else{ //if this is multiple choice matrix
							table_add_field($dbh,$form_id,$element_id,'radio');
						}
					}else{ //other field types
						table_add_field($dbh,$form_id,$element_id,$element_type);
					}
					
					//check for 'other' field into checkboxes and multiple choices field
					//if the 'other' field is active, make sure to add the 'other' column into the table
					if ($element_type == 'checkbox' || $element_type == 'radio'){
						if(!empty($element_choice_has_other)){
							//add the 'other' field into the table, but check first, just in case the field already exist
							if(!la_mysql_column_exist(LA_TABLE_PREFIX."form_{$form_id}","element_{$element_id}_other",$dbh)){
								$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_other` text NULL COMMENT 'Choice - Other';";
								la_do_query($query,array(),$dbh);
							}
						}
					}
				}

		    	//end creating form table-------------

		    	//start creating review table---------
		    	$form_properties = la_get_form_properties($dbh,$form_id, array('form_review','form_page_total'));
		    	$form_review = (int) $form_properties['form_review'];
		    	$form_page_total = (int) $form_properties['form_page_total'];

		    	if(!empty($form_review) || ($form_page_total > 1)){
		    		$query = "CREATE TABLE `".LA_TABLE_PREFIX."form_{$form_id}_review` like `".LA_TABLE_PREFIX."form_{$form_id}`";
					la_do_query($query,array(),$dbh);
					
					$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}_review` ADD COLUMN `session_id` varchar(128) NULL";
					la_do_query($query,array(),$dbh);
		    	}
		    	//end creating review table-----------

		    	//create data folder
		    	if(is_writable($la_settings['data_dir'])){
			
					$old_mask = umask(0);
					mkdir($la_settings['data_dir']."/form_{$form_id}",0777);
					mkdir($la_settings['data_dir']."/form_{$form_id}/css",0777);
					if($la_settings['data_dir'] != $la_settings['upload_dir']){
						@mkdir($la_settings['upload_dir']."/form_{$form_id}",0777);
					}
					mkdir($la_settings['upload_dir']."/form_{$form_id}/files",0777);
					@file_put_contents($la_settings['upload_dir']."/form_{$form_id}/files/index.html",' '); //write empty index.html
					
					//copy default view.css to css folder
					if(copy("./view.css",$la_settings['data_dir']."/form_{$form_id}/css/view.css")){
						//on success update 'form_has_css' field on ap_forms table
						$form_update_input['form_has_css'] = 1;
						la_ap_forms_update($form_id,$form_update_input,$dbh);
					}
					
					umask($old_mask);
				}

		    	//send success status
		    	$response_data->status = 'ok';
			    $response_data->new_form_name = htmlentities($imported_form_name,ENT_QUOTES);
			    $response_data->new_form_id   = $form_id;
		    }
		   

		    fclose($handle);

		    //delete the file
		    @unlink($form_file_path);
		}
	}else{
		$response_data->status  = 'error';
		$response_data->message = 'Invalid form file name.';
	}
	
	$response_json = json_encode($response_data);
	
	echo $response_json;




	/***************************************************************************************************************/	
	/* Functions																								   */
	/***************************************************************************************************************/
   	
	//add fields to the specified form table
	function table_add_field($dbh,$form_id,$element_id,$type,$option_id=0){
		$comment_desc['text'] 		= 'Single Line Text';
		$comment_desc['phone'] 		= 'Phone';
		$comment_desc['simple_phone'] = 'Phone';
		$comment_desc['url'] 		= 'Web Site';
		$comment_desc['email'] 		= 'Email';
		$comment_desc['file'] 		= 'File Upload';
		$comment_desc['textarea'] 	= 'Paragraph Text';
		$comment_desc['radio'] 		= 'Multiple Choice';
		$comment_desc['select'] 	= 'Drop Down';
		$comment_desc['time'] 		= 'Time';
		$comment_desc['date'] 		= 'Date';
		$comment_desc['europe_date'] = 'Europe Date';
		$comment_desc['money'] 		 = 'Price';
		$comment_desc['number'] 	 = 'Number';
		$comment_desc['simple_name'] = 'Normal Name';
		$comment_desc['simple_name_wmiddle'] = 'Normal Name with Middle';
		$comment_desc['name'] 		 		 = 'Extended Name';
		$comment_desc['name_wmiddle'] 		 = 'Extended Name with Middle';
		$comment_desc['address'] 	 = 'Address';
		$comment_desc['checkbox'] 	 = 'Checkbox';
		$comment_desc['signature'] 	 = 'Signature';
		
		$comment = @$comment_desc[$type];
			
		if(('text' == $type) || ('phone' == $type) || ('simple_phone' == $type) || ('url' == $type) || ('email' == $type) || ('file' == $type)){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` text NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif ('textarea' == $type || 'signature' == $type){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` mediumtext NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif (('radio' == $type) || ('select' == $type)){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif ('time' == $type){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` time NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif (('date' == $type) || ('europe_date' == $type)){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` date NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif ('money' == $type){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` decimal(62,2) NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif ('number' == $type){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}` double NULL COMMENT '{$comment}';";
			la_do_query($query,array(),$dbh);
		}elseif ('simple_name' == $type){
			//add two field, first and last name
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_1` text NULL COMMENT '{$comment} - First', ADD COLUMN `element_{$element_id}_2` text NULL COMMENT '{$comment} - Last';";
			la_do_query($query,array(),$dbh);
		}elseif ('simple_name_wmiddle' == $type){
			//add three fields, first, middle and last name
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_1` text NULL COMMENT '{$comment} - First', ADD COLUMN `element_{$element_id}_2` text NULL COMMENT '{$comment} - Middle', ADD COLUMN `element_{$element_id}_3` text NULL COMMENT '{$comment} - Last';";
			la_do_query($query,array(),$dbh);
		}elseif ('name' == $type){
			//add four field, title, first, last, suffix 
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_1` text NULL COMMENT '{$comment} - Title', ADD COLUMN `element_{$element_id}_2` text NULL COMMENT '{$comment} - First', ADD COLUMN `element_{$element_id}_3` text NULL COMMENT '{$comment} - Last', ADD COLUMN `element_{$element_id}_4` text NULL COMMENT '{$comment} - Suffix';";
			la_do_query($query,array(),$dbh);
		}elseif ('name_wmiddle' == $type){
			//add five fields, title, first, middle, last, suffix 
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_1` text NULL COMMENT '{$comment} - Title', ADD COLUMN `element_{$element_id}_2` text NULL COMMENT '{$comment} - First', ADD COLUMN `element_{$element_id}_3` text NULL COMMENT '{$comment} - Middle', ADD COLUMN `element_{$element_id}_4` text NULL COMMENT '{$comment} - Last', ADD COLUMN `element_{$element_id}_5` text NULL COMMENT '{$comment} - Suffix';";
			la_do_query($query,array(),$dbh);
		}elseif ('address' == $type){
			//add six field
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_1` text NULL COMMENT '{$comment} - Street', ADD COLUMN `element_{$element_id}_2` text NULL COMMENT '{$comment} - Line 2', ADD COLUMN `element_{$element_id}_3` text NULL COMMENT '{$comment} - City', ADD COLUMN `element_{$element_id}_4` text NULL COMMENT '{$comment} - State/Province/Region', ADD COLUMN `element_{$element_id}_5` text NULL COMMENT '{$comment} - Zip/Postal Code', ADD COLUMN `element_{$element_id}_6` text NULL COMMENT '{$comment} - Country';";
			la_do_query($query,array(),$dbh);
		}elseif ('checkbox' == $type){
			$query = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$form_id}` ADD COLUMN `element_{$element_id}_{$option_id}` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '{$comment} - {$option_id}';";
			la_do_query($query,array(),$dbh);
		}
			
	}
	
?>