<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://www.continuumgrc.com/
 
 More info at: http://www.continuumgrc.com/
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	
	require('includes/filter-functions.php');
	require('includes/view-functions.php');
	
	$dbh = la_connect_db();

	//check for max_input_vars
	la_init_max_input_vars();
	
	$element_properties_array = la_sanitize($_POST['fp']);
	$form_id				  = (int) $_POST['form_id'];

	$response_data 	  = new stdClass();
	$updated_element_id = ''; 

	//check if auto mapping is enabled of form
	$form_data = getFormData(array(
		'dbh' => $dbh,
		'form_id' => $form_id,
		'column' => 'form_enable_auto_mapping'
	));

	$form_enable_auto_mapping = 0;
	if( $form_data[0]['form_enable_auto_mapping'] ) {
		$form_enable_auto_mapping = $form_data[0]['form_enable_auto_mapping'];
	}
	
	//loop through each element properties
	if(!empty($element_properties_array)){
		foreach($element_properties_array as $element_properties){
			
			unset($element_properties['is_db_live']);
			unset($element_properties['last_option_id']); //this property exist for choices field type
			unset($element_properties['page_total']); //this property exist for page break field type
			
			$element_options = array();
			$element_options = $element_properties['options'];
			unset($element_properties['options']);

			$element_properties['id'] = htmlentities($element_properties['id']);
			
			/***************************************************************************************************************/	
			/* 1. Synch into ap_elements_options table																   	   */
			/***************************************************************************************************************/
			// This is only necessary for multiple choice, checkboxes, dropdown and matrix field
			if(in_array($element_properties['type'],array('radio','checkbox','select'))){
				
				//delete any previous records of this element
				$query = "DELETE FROM 
									 ".LA_TABLE_PREFIX."element_options
								WHERE
									 form_id = :form_id AND element_id = :element_id and live='2'"; 
				
				$params = array(':form_id' => $form_id,
								':element_id' => $element_properties['id']);	
				
				la_do_query($query,$params,$dbh);
				
				//insert the new options
				$query = "INSERT INTO 
									`".LA_TABLE_PREFIX."element_options` 
									(`form_id`,`element_id`,`option_id`,`position`,`option`, `option_value`,`option_is_default`,`live`) 
							  VALUES 
							  		(:form_id,:element_id,:option_id,:position,:option,:option_value,:is_default,'2');"; 
				
				foreach ($element_options as $option_id=>$value){
					
					$params = array(':form_id' => $form_id,
									':element_id' => $element_properties['id'],
									':option_id' => $option_id,
									':position' => $value['position'],
									':option' => $value['option'],
									':option_value' => (isset($value['option_value'])?((int)$value['option_value']):0),
									':is_default' => $value['is_default']);
					la_do_query($query,$params,$dbh);
				}
				
				
			}
						
			/***************************************************************************************************************/	
			/* 2. Synch into ap_form_elements table																	   	   */
			/***************************************************************************************************************/
			$update_values = '';
			$params = array();
			
			//dynamically create the sql update string, based on the input given
			
			
			foreach ($element_properties as $key => $value){
			if(!($key == 'policymachine_code' || $key == 'element_id_auto' || $key == 'form_id')){

				if( $key == 'video_url' ) {
					if (strpos($value, 'data:') !== false) {
						$value = '';
					}
				}

				if (strpos($key, 'element_') !== false) {
              		$key = str_replace('element_', '', $key);
              	}
				$update_values .= "`element_{$key}`= :element_{$key},";
				$params[':element_'.$key] = $value;
              }
			}
          
			$update_values = rtrim($update_values,',');
			
			$query = "UPDATE `".LA_TABLE_PREFIX."form_elements` set 
										$update_values
								  where 
							  	  		form_id = :form_id and element_id = :w_element_id";
										
			$params[':form_id'] = $form_id;
			$params[':w_element_id'] = $element_properties['id'];
			
			la_do_query($query,$params,$dbh);
										
			$updated_element_id .= '#li_'.$element_properties['id'].',';

			//file upload field if mapping enabled always Enable Synced Upload
			if($element_properties['type'] == 'file' && $form_enable_auto_mapping == 1 && !empty($element_properties['machine_code'])){
				$query = "UPDATE 
								`".LA_TABLE_PREFIX."form_elements` 
							 SET 
								`element_file_upload_synced` = 1
						   WHERE 
								form_id = :form_id and element_id = :element_id";

				$params = array();
				$params[':form_id']				= $form_id;
				$params[':element_id']			= $element_properties['id'];

				la_do_query($query,$params,$dbh);
			}	
			
			//if this is matrix field, the element title need to be updated again from the options, the position as well
			if($element_properties['type'] == 'matrix'){
				
				$query = "UPDATE 
								`".LA_TABLE_PREFIX."form_elements` 
							 SET 
								`element_title` = :element_title,
								`element_position` = :element_position		
						   WHERE 
								form_id = :form_id and element_id = :element_id";
				
				
				foreach ($element_options as $m_element_id => $value){
					
					$params = array();
					$params[':element_title'] 		= $value['row_title'];
					$params[':element_position']	= $value['position'];
					$params[':form_id']				= $form_id;
					$params[':element_id']			= $m_element_id;
					
					la_do_query($query,$params,$dbh);	
				} 
			}
			
			if($element_properties['type'] == 'matrix'){
				$column_data = array();
				$column_data = $element_options[$element_properties['id']]['column_data'];
				
				foreach ($element_options as $m_element_id => $value){
					
					$query = "UPDATE `".LA_TABLE_PREFIX."form_elements` set `element_machine_code`= :element_machine_code where form_id = :form_id and element_id = :element_id";
					
					$params = array();				
					$params[':form_id'] = $form_id;
					$params[':element_id'] = $m_element_id;
					$params[':element_machine_code'] = $value['machine_code'];
					
					@la_do_query($query,$params,$dbh);
					
					unset($query);
					unset($params);
					
					//delete any previous records of this element	
					$query = "DELETE FROM 
										 ".LA_TABLE_PREFIX."element_options
									WHERE
										 form_id = :form_id AND element_id = :element_id and live='2'";
					
					
					$params = array(':form_id' => $form_id,
									':element_id' => $m_element_id);	
					la_do_query($query,$params,$dbh);
									
					
					//insert the new options		
					$query = "INSERT INTO 
										`".LA_TABLE_PREFIX."element_options` 
										(`form_id`,`element_id`,`option_id`,`position`,`option`,`option_value`,`option_is_default`,`live`) 
								  VALUES 
								   		(:form_id,:element_id,:option_id,:position,:option,:option_value,'0','2');";
					 
					foreach ($column_data as $option_id=>$data){
						$params = array(
										':form_id' => $form_id,
										':element_id' => $m_element_id,
										':option_id' => $option_id ,
										':position' => $data['position'],
										':option' => $data['column_title'],
										':option_value' => $data['column_score']
										);
						la_do_query($query,$params,$dbh);
					}
					
				}
			}
		}
	}
	
	$updated_element_id = rtrim($updated_element_id,',');
	
	$response_data->status    			= "ok";
	$response_data->updated_element_id 	= $updated_element_id;
	$response_data->csrf_token  		= $_SESSION['csrf_token']; 
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>