<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://www.continuumgrc.com/
 
 More info at: http://www.continuumgrc.com/
 ********************************************************************************/

	function getRecaptchaPublicKey(){
		$dbh = la_connect_db();
		$sql = "select `recaptcha_public` from `".LA_TABLE_PREFIX."settings`";
		$res = la_do_query($sql,array(),$dbh);
		$row = la_do_fetch_result($res);
		return $row['recaptcha_public'];
	}

	function getFormData($params=array())
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
	
	function getNoOfPages($dbh,$form_id)
	{
		$query  = "SELECT count(`element_type`) `total_page`  FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id and `element_type` = 'page_break'";
		$result = la_do_query($query,array(':form_id' => $form_id),$dbh);
		$row    = la_do_fetch_result($result);
		
		return (int) $row['total_page'];
	}
	
	function getElementData($params=array())
	{
		$dbh = $params['dbh'];
		$form_id = $params['form_id'];
		$column = isset($params['column']) ? $params['column'] : "element_default_value";
		$condition = isset($params['condition']) ? $params['condition'] : " and `element_type` = 'casecade_form'";
		$query = "select {$params['column']} from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id {$condition}";
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
	
	function getPageNumber($params=array())
	{
		$dbh = $params['dbh'];
		$form_id = $params['form_id'];
		$casecade_element_position = $params['casecade_element_position'];
		$query  = "SELECT `element_default_value`, `element_page_number`  FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id and `element_position` = :element_position";
		$result = la_do_query($query,array(':form_id' => $form_id, ':element_position' => $casecade_element_position),$dbh);
		$row    = la_do_fetch_result($result);
		
		return array('form_id' => (int) $row['element_default_value'], 'element_page_number' => (int) $row['element_page_number']);
	}

    function checkPageLogicExistense($dbh,$form_id)
    {
       $query  = "SELECT count(*) `page_logic` FROM `".LA_TABLE_PREFIX."page_logic` where `form_id` = :form_id";
       $result = la_do_query($query,array(':form_id' => $form_id),$dbh);
	   $row    = la_do_fetch_result($result);
		
	   return ($row['page_logic'] > 0) ? true : false;
    }
	
	function getFormCounterDetail($dbh, $params)
	{
		$query  = "SELECT * FROM `".LA_TABLE_PREFIX."forms_submission_counter` where `form_id` = :form_id AND `company_id` = :company_id";
		$result = la_do_query($query,array(':form_id' => $params['form_id'], ':company_id' => $params['company_id']),$dbh);
		return la_do_fetch_result($result);
	}
	
	function getFormCounter($dbh, $params)
	{
		$row    = getFormCounterDetail($dbh, $params);		
		return $row ? $row['form_counter'] : 0;
	}
	
	function incrementFormCounter($dbh, $params)
	{
		$row	= getFormCounterDetail($dbh, $params);
		
		if($row){
			$query  = "UPDATE `".LA_TABLE_PREFIX."forms_submission_counter` SET `form_counter` = `form_counter` + 1 WHERE `form_counter_id` = ?;";
			la_do_query($query,array($row['form_counter_id']),$dbh);
		}else{
			$query  = "INSERT INTO `".LA_TABLE_PREFIX."forms_submission_counter` (`form_counter_id`, `form_id`, `company_id`, `form_counter`) VALUES (NULL, ?, ?, ?);";
			la_do_query($query,array($params['form_id'], $params['company_id'], 1),$dbh);
		}
	}
	
	function getEntityIdsForView($dbh, $client_user_id){
		$entities = array();
		
		$query = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_user_relation` WHERE `client_user_id` = ?";
		$sth = la_do_query($query, array($client_user_id), $dbh);
		
		while($row = la_do_fetch_result($sth)){
			array_push($entities, $row['entity_id']);
		}
		
		return $entities;
	}	
	
	function getElementNote($params=array())
	{
		$dbh = $params['dbh'];
		$form_id = $params['form_id'];
		$element_id = $params['element_id'];
		$company_id = $params['company_id'];

		$query = "SELECT `status` FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = :form_id and `element_id` = :element_id and `company_id` = :company_id";
		$result = la_do_query($query, array(':form_id' => $form_id, ':element_id' => $element_id, ':company_id' => $company_id), $dbh);
		$row    = la_do_fetch_result($result);
		
		return $row['status'];
	}
	
	function generateNoteIcon($param=array())
	{	
		$param['field_note_status'] = getElementNote(array('dbh' => la_connect_db(), 'form_id' => $param['form_id'], 'element_id' => $param['element_id'], 'company_id' => $_SESSION['la_client_entity_id']));

		$element_note = '';
		$assigned_flag = false;
		$assignee_id = $_SESSION['la_client_entity_id'];
		//check assigned notes
		$query_notes = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = ? and `element_id` = ?";
		$sth_notes = la_do_query($query_notes, array($param['form_id'], $param['element_id']), la_connect_db());

		while($row_note = la_do_fetch_result($sth_notes)){
			if(!empty($row_note["note"])){
				if(in_array($_SESSION["la_client_entity_id"], explode(",", explode(";", $row_note["assignees"])[1])) || in_array($_SESSION["la_client_entity_id"]."-".$_SESSION["la_client_user_id"], explode(",", explode(";", $row_note["assignees"])[2]))) {
					$assigned_flag = true;
				}
			}
		}

		if(isset($param['is_design_mode']) && $param['is_design_mode'] == 1){
			$img = "";
			if(empty($param['field_note_status'])) {
				$img = 'images/downarrow-grayx16.png';
			} else {
				if($param['field_note_status'] == 3) {
					$img = 'images/downarrow-redx16.png';
				} else {
					$img = 'images/downarrow-greenx16.png';
				}
			}
			if(!$assigned_flag) {
				$element_note = '<div><img id="img-'.$param['form_id'].'-'.$param['element_id'].'" class="element-note" data-form-id="'.$param['form_id'].'" data-element-id="'.$param['element_id'].'" src="'.$img.'" style="cursor:pointer;"></div>';
			} else {
				$element_note = '<div><img id="img-'.$param['form_id'].'-'.$param['element_id'].'" class="element-note" data-form-id="'.$param['form_id'].'" data-element-id="'.$param['element_id'].'" src="'.$img.'" style="cursor:pointer;"><img class="assigned-note" assignee-id="'.$assignee_id.'" role="entity" form-id="'.$param['form_id'].'" element-id="'.$param['element_id'].'" src="images/downarrow-orangex16.png" style="cursor:pointer;"></div>';
			}
		}else{
			if($param['element_note'] == 1){
				$img = "";
				if(empty($param['field_note_status'])) {
					$img = 'images/downarrow-grayx16.png';
				} else {
					if($param['field_note_status'] == 3) {
						$img = 'images/downarrow-redx16.png';
					} else {
						$img = 'images/downarrow-greenx16.png';
					}
				}
				if(!$assigned_flag) {
					$element_note = '<div><img id="img-'.$param['form_id'].'-'.$param['element_id'].'" class="element-note" data-form-id="'.$param['form_id'].'" data-element-id="'.$param['element_id'].'" src="'.$img.'" style="cursor:pointer;"></div>';
				} else {
					$element_note = '<div><img id="img-'.$param['form_id'].'-'.$param['element_id'].'" class="element-note" data-form-id="'.$param['form_id'].'" data-element-id="'.$param['element_id'].'" src="'.$img.'" style="cursor:pointer;"><img class="assigned-note" assignee-id="'.$assignee_id.'" role="entity" form-id="'.$param['form_id'].'" element-id="'.$param['element_id'].'" src="images/downarrow-orangex16.png" style="cursor:pointer;"></div>';
				}
			}
		}
		
		return $element_note;
	}

	function generateStatusIndicator($param=array()){
		$dbh = la_connect_db();
		$img = "";
		
		if(isset($param['is_design_mode']) && $param['is_design_mode'] == 1){
			
		}else{
			if($param['element_status_indicator'] == 1){		
				$indicator = 0;
				$company_id = $param['company_id'];
				
				$query_indicator = "SELECT indicator from ".LA_TABLE_PREFIX."element_status_indicator WHERE form_id = ? AND element_id = ? AND company_id = ? AND entry_id = ?";
				$sth_indicator = la_do_query($query_indicator, array($param['form_id'], $param['element_id'], $company_id, $param['entry_id']), $dbh);
				$row_indicator = la_do_fetch_result($sth_indicator);
				
				if($row_indicator){
					$indicator = $row_indicator['indicator'];
					
					if($row_indicator['indicator'] == 0){
						$img_name = "Circle_Gray.png";
					}elseif($row_indicator['indicator'] == 1){
						$img_name = "Circle_Red.png";
					}elseif($row_indicator['indicator'] == 2){
						$img_name = "Circle_Yellow.png";
					}elseif($row_indicator['indicator'] == 3){
						$img_name = "Circle_Green.png";
					}
				}else{
					//If no record exists for specific status indicator, create new record with value of 0 (gray)
					$insert_indicator = "INSERT INTO " .LA_TABLE_PREFIX. "element_status_indicator (form_id, element_id, company_id, entry_id, indicator) VALUES (?, ?, ?, ?, ?)";
					$sth_indicator = la_do_query($insert_indicator, array($param['form_id'], $param['element_id'], $company_id, $param['entry_id'], 0), $dbh);
					$img_name = "Circle_Gray.png";
				}
				
				$img = '<img class="status-icon-action" data-form_id="'.$param['form_id'].'" data-entry_id="'.$param['entry_id'].'" data-element_id="'.$param['element_id'].'" data-company_id="'.$company_id.'" data-indicator="'.$indicator.'" src="images/'.$img_name.'" style="width:10px; display: inline; vertical-align: middle; margin-bottom: 5px; margin-top:3px; margin-left:5px; cursor:pointer;" />';
			}
		}
		
		return $img;
	}
	
	function viewCasecadeForm($params=array())
	{
		$dbh = $params['dbh'];
		$form_id = $params['form_id'];
		$parent_form_id = $params['parent_form_id'];
		$company_id = $params['company_id'];
		$entry_id = $params['entry_id'];
		$form_params = $params['form_params'];
		$error_elements = isset($form_params['error_elements']) ? $form_params['error_elements'] : array();
		$populated_values = $params['form_params']['populated_values'];
		$options_lookup = array();
		$page_number = $params['page_number'] == 'NO_ELEMENTS' ? 1 : $params['page_number'];
		$page_number_clause = isset($page_number) ? ($page_number > 0 ? 'and element_page_number = ?' : '') : 'and element_page_number = ?';
		$element_params = isset($page_number) ? ($page_number > 0 ? array($form_id, $page_number) : array($form_id)) : array($form_id, $page_number);
		
		//get elements data
		//get element options first and store it into array
		$query = "SELECT `element_id`, `option_id`, `position`, `option`, `option_is_default` FROM `".LA_TABLE_PREFIX."element_options` where form_id = ? and live=1 order by element_id asc,`position` asc";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			$options_lookup[$element_id][$option_id]['position'] 		  = $row['position'];
			$options_lookup[$element_id][$option_id]['option'] 			  = $row['option'];
			$options_lookup[$element_id][$option_id]['option_is_default'] = $row['option_is_default'];
			
			if(isset($element_prices_array[$element_id][$option_id])){
				$options_lookup[$element_id][$option_id]['price_definition'] = $element_prices_array[$element_id][$option_id];
			}
		}
		
		$query = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id = ? and element_status='1' {$page_number_clause} and element_type <> 'page_break' ORDER BY element_position asc";
		
		$sth = la_do_query($query,$element_params,$dbh);
		
		$j=0;
		$has_calendar = true; //assume the form doesn't have calendar, so it won't load calendar.js
		$has_advance_uploader = true;
		$has_signature_pad = true;
		$has_guidelines = false;
		$element = array();
		
		while($row = la_do_fetch_result($sth)){
			
			$element[$j] = new stdClass();
			
			$element_id = $row['element_id'];
			
			//lookup element options first
			if(!empty($options_lookup[$element_id])){
				$element_options = array();
				$i=0;
				foreach ($options_lookup[$element_id] as $option_id=>$data){
					$element_options[$i] = new stdClass();
					$element_options[$i]->id 		 = $option_id;
					$element_options[$i]->option 	 = $data['option'];
					$element_options[$i]->is_default = $data['option_is_default'];
					$element_options[$i]->is_db_live = 1;
					
					if(isset($data['price_definition'])){
						$element_options[$i]->price_definition = $data['price_definition'];
					}
					
					$i++;
				}
			}
		
			//populate elements
			$element[$j]->title 		= nl2br($row['element_title']);
			$element[$j]->guidelines 	= $row['element_guidelines'];
			
			if(!empty($row['element_guidelines']) && ($row['element_type'] != 'section') && ($row['element_type'] != 'matrix') && empty($row['element_is_private'])){
				$has_guidelines = true;
			}
			
			$element[$j]->size 			= $row['element_size'];
			$element[$j]->is_required 	= $row['element_is_required'];
			$element[$j]->is_unique 	= $row['element_is_unique'];
			$element[$j]->is_private 	= $row['element_is_private'];
			$element[$j]->type 			= $row['element_type'];
			$element[$j]->position 		= $row['element_position'];
			$element[$j]->id 			= $row['element_id'];
			$element[$j]->is_db_live 	= 1;
			$element[$j]->form_id 		= $form_id;
			$element[$j]->choice_has_other   = (int) $row['element_choice_has_other'];
			$element[$j]->choice_other_label = $row['element_choice_other_label'];
			$element[$j]->choice_columns   	 = (int) $row['element_choice_columns'];
			$element[$j]->time_showsecond    = (int) $row['element_time_showsecond'];
			$element[$j]->time_24hour    	 = (int) $row['element_time_24hour'];
			$element[$j]->address_hideline2	 = (int) $row['element_address_hideline2'];
			$element[$j]->address_us_only	 = (int) $row['element_address_us_only'];
			$element[$j]->date_enable_range	 = (int) $row['element_date_enable_range'];
			$element[$j]->date_range_min	 = $row['element_date_range_min'];
			$element[$j]->date_range_max	 = $row['element_date_range_max'];
			$element[$j]->date_enable_selection_limit	 = (int) $row['element_date_enable_selection_limit'];
			$element[$j]->date_selection_max	 		 = (int) $row['element_date_selection_max'];
			$element[$j]->date_disable_past_future	 	= (int) $row['element_date_disable_past_future'];
			$element[$j]->date_past_future	 			= $row['element_date_past_future'];
			$element[$j]->date_disable_weekend	 		= (int) $row['element_date_disable_weekend'];
			$element[$j]->date_disable_specific	 		= (int) $row['element_date_disable_specific'];
			$element[$j]->date_disabled_list	 		= $row['element_date_disabled_list'];
			$element[$j]->file_enable_type_limit	 	= (int) $row['element_file_enable_type_limit'];						
			$element[$j]->file_block_or_allow	 		= $row['element_file_block_or_allow'];
			$element[$j]->file_type_list	 			= $row['element_file_type_list'];
			$element[$j]->file_as_attachment	 		= (int) $row['element_file_as_attachment'];	
			$element[$j]->file_enable_advance	 		= (int) $row['element_file_enable_advance'];
			
			if(!empty($element[$j]->file_enable_advance) && ($row['element_type'] == 'file')){
				$has_advance_uploader = true;
			}
			
			$element[$j]->file_auto_upload	 			= (int) $row['element_file_auto_upload'];
			$element[$j]->file_enable_multi_upload	 	= (int) $row['element_file_enable_multi_upload'];
			$element[$j]->file_max_selection	 		= (int) $row['element_file_max_selection'];
			$element[$j]->file_enable_size_limit	 	= (int) $row['element_file_enable_size_limit'];
			$element[$j]->file_size_max	 				= (int) $row['element_file_size_max'];
			$element[$j]->matrix_allow_multiselect	 	= (int) $row['element_matrix_allow_multiselect'];
			$element[$j]->matrix_parent_id	 			= (int) $row['element_matrix_parent_id'];
			$element[$j]->upload_dir	 				= $la_settings['upload_dir'];		
			$element[$j]->range_min	 					= $row['element_range_min'];
			$element[$j]->range_max	 					= $row['element_range_max'];
			$element[$j]->range_limit_by	 			= $row['element_range_limit_by'];
			$element[$j]->itauditmachine_path	 		= $itauditmachine_path;
			$element[$j]->itauditmachine_data_path	 	= $itauditmachine_data_path;
			$element[$j]->section_display_in_email	 	= (int) $row['element_section_display_in_email'];
			$element[$j]->section_enable_scroll	 		= (int) $row['element_section_enable_scroll'];
			$element[$j]->default_value  				= $row['element_default_value'];
			$element[$j]->element_id_auto  				= $row['id'];
			$element[$j]->element_machine_code 			= $row['element_machine_code'];
			$element[$j]->file_upload_synced    		= (int) $row['element_file_upload_synced'];
			$element[$j]->element_status_indicator    	= $row['element_status_indicator'];
			$element[$j]->file_select_existing_files    = (int) $row['element_file_select_existing_files'];
	
			if(!empty($form->payment_enable_merchant) && !empty($row['element_number_enable_quantity']) && !empty($row['element_number_quantity_link'])){
				$element[$j]->number_quantity_link	 	= $row['element_number_quantity_link'];
			}
			
			$element[$j]->parent_form_id = $parent_form_id;
			$element[$j]->company_id = $company_id;
			$element[$j]->entry_id = $entry_id;

			//this data came from db or form submit
			//being used to display edit form or redisplay form with errors and previous inputs
			//this should be optimized in the future, only pass necessary data, not the whole array
			//echo '<pre>';print_r($populated_values);echo '</pre>';die;
			
			if($element[$j]->type == 'casecade_form'){
				$element[$j]->populated_value['element_'.$row['element_id']] = $populated_values['element_'.$element[$j]->id][$element[$j]->default_value];
			}
			elseif($element[$j]->type == 'checkbox'){
				$element[$j]->enhanced_checkbox = $row['element_enhanced_checkbox'];
				if(!empty($element_options)){
					foreach($element_options as $option){
						$element[$j]->populated_value['element_'.$row['element_id'].'_'.$option->id]['default_value'] = $populated_values['element_'.$row['element_id'].'_'.$option->id]['default_value'];
					}
				}
			}
			elseif($element[$j]->type == 'date' || $element[$j]->type == 'europe_date'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'address'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_4'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_4']['default_value']) ? $populated_values['element_'.$row['element_id'].'_4']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_5'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_5']['default_value']) ? $populated_values['element_'.$row['element_id'].'_5']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_6'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_6']['default_value']) ? $populated_values['element_'.$row['element_id'].'_6']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'phone'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'time'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_4'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_4']['default_value']) ? $populated_values['element_'.$row['element_id'].'_4']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'money'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'simple_name'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'name'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_4'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_4']['default_value']) ? $populated_values['element_'.$row['element_id'].'_4']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'simple_name_wmiddle'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'name_wmiddle'){
				$element[$j]->populated_value['element_'.$row['element_id'].'_1'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_1']['default_value']) ? $populated_values['element_'.$row['element_id'].'_1']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_2'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_2']['default_value']) ? $populated_values['element_'.$row['element_id'].'_2']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_3'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_3']['default_value']) ? $populated_values['element_'.$row['element_id'].'_3']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_4'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_4']['default_value']) ? $populated_values['element_'.$row['element_id'].'_4']['default_value'] : ''));
				$element[$j]->populated_value['element_'.$row['element_id'].'_5'] = array('default_value' => (isset($populated_values['element_'.$row['element_id'].'_5']['default_value']) ? $populated_values['element_'.$row['element_id'].'_5']['default_value'] : ''));
			}
			elseif($element[$j]->type == 'matrix'){
				if($element[$j]->matrix_parent_id == 0){
					$element[$j]->populated_value['element_'.$row['element_id']] = $populated_values['element_'.$row['element_id']];
					
					$query_matrix_child = "select `element_id` from `".LA_TABLE_PREFIX."form_elements` where `element_matrix_parent_id` = :element_matrix_parent_id";
					$result_matrix_child = la_do_query($query_matrix_child,array(':element_matrix_parent_id' => $row['element_id']),$dbh);
					
					while($row_matrix_child = la_do_fetch_result($result_matrix_child)){
						$element[$j]->populated_value['element_'.$row_matrix_child['element_id']] = $populated_values['element_'.$row_matrix_child['element_id']];
					}
				}
			}
			else{
				$element[$j]->populated_value['element_'.$row['element_id']] = $populated_values['element_'.$row['element_id']];
			}
			
			//set prices for price-enabled field
			if($row['element_type'] == 'money' && isset($element_prices_array[$row['element_id']][0])){
				$element[$j]->price_definition = 0;
			}
			
			//if there is file upload type, set form enctype to multipart
			if($row['element_type'] == 'file'){
				$form_enc_type = 'enctype="multipart/form-data"';
				
				//if this is single page form with review enabled or multipage form
				if ((!empty($form->review) && !empty($_SESSION['review_id']) && !empty($populated_file_values)) ||
					($form->page_total > 1 && !empty($populated_file_values))	
				) {
					//populate the default value for uploaded files, when validation error occured
	
					//make sure to keep the file token if exist
					if(!empty($populated_values['element_'.$row['element_id']]['file_token'])){
						$populated_file_values['element_'.$row['element_id']]['file_token'] = $populated_values['element_'.$row['element_id']]['file_token'];
					}
	
					$element[$j]->populated_value = $populated_file_values;
				}
			}
	
			if(!empty($edit_id) && $_SESSION['la_logged_in'] === true){
				//if this is edit_entry page
				$element[$j]->is_edit_entry = true;
			}
			
			if(!empty($error_elements[$element[$j]->id])){
				$element[$j]->is_error 	    = 1;
				$element[$j]->error_message = $error_elements[$element[$j]->id];
			}
			
			$element[$j]->default_value = $row['element_default_value'];
			
			$element[$j]->constraint 	= $row['element_constraint'];
			if(!empty($element_options)){
				$element[$j]->options 	= $element_options;
			}
			else{
				$element[$j]->options 	= '';
			}
			
			//check for signature type
			if($row['element_type'] == 'signature'){
				$has_signature_pad = true;
			}
			
			//check for calendar type
			if($row['element_type'] == 'date' || $row['element_type'] == 'europe_date'){
				$has_calendar = true;
				
				//if the field has date selection limit, we need to do query to existing entries and disable all date which reached the limit
				if(!empty($row['element_date_enable_selection_limit']) && !empty($row['element_date_selection_max'])){
					//$sub_query = "select selected_date from ( select date_format(element_{$row['element_id']},'%m/%d/%Y') as selected_date, count(element_{$row['element_id']}) as total_selection from ".LA_TABLE_PREFIX."form_{$form_id} where status=1 and element_{$row['element_id']} is not null group by element_{$row['element_id']} ) as A where A.total_selection >= ?";
					$sub_query = "select selected_date from (select date_format(`data_value`, '%m/%d/%Y') as selected_date, count(`data_value`) as total_selection from `".LA_TABLE_PREFIX."form_{$form_id}` where `field_name` = 'element_{$row['element_id']}' and `data_value` is not null group by `data_value`) as A where A.total_selection >= ?";
					$params = array($row['element_date_selection_max']);
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					$current_date_disabled_list = array();
					$current_date_disabled_list_joined = '';
					
					while($sub_row = la_do_fetch_result($sub_sth)){
						$current_date_disabled_list[] = $sub_row['selected_date'];
					}
					
					$current_date_disabled_list_joined = implode(',',$current_date_disabled_list);
					if(!empty($element[$j]->date_disable_specific)){ //add to existing disable date list
						if(empty($element[$j]->date_disabled_list)){
							$element[$j]->date_disabled_list = $current_date_disabled_list_joined;
						}else{
							$element[$j]->date_disabled_list .= ','.$current_date_disabled_list_joined;
						}
					}else{
						//'disable specific date' is not enabled, we need to override and enable it from here
						$element[$j]->date_disable_specific = 1;
						$element[$j]->date_disabled_list = $current_date_disabled_list_joined;
					}
					
				}
			}
			
			//if the element is a matrix field and not the parent, store the data into a lookup array for later use when rendering the markup
			if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){
				
				$parent_id 	 = $row['element_matrix_parent_id'];
				$el_position = $row['element_position'];
				$matrix_elements[$parent_id][$el_position]['title'] = $element[$j]->title; 
				$matrix_elements[$parent_id][$el_position]['id'] 	= $element[$j]->id; 
				$matrix_elements[$parent_id][$el_position]['element_status_indicator'] 	= $row['element_status_indicator'];
				$matrix_child_option_id = '';
				foreach($element_options as $value){
					$matrix_child_option_id .= $value->id.',';
				}
				$matrix_child_option_id = rtrim($matrix_child_option_id,',');
				$matrix_elements[$parent_id][$el_position]['children_option_id'] = $matrix_child_option_id; 
				
				//remove it from the main element array
				$element[$j] = array();
				unset($element[$j]);
				$j--;
			}
			
			$element[$j]->rich_text	    				= $row['element_rich_text'];
			$element[$j]->element_note				 	= $row['element_note'];
			$element[$j]->form_id	 					= $form_id;
			
			$j++;
		}
		
		//echo '<pre>';print_r($element);echo '</pre>';die;
		$container_class = '';
		$all_element_markup = '';
		
		foreach ($element as $element_data){
			$keystone_viewer = la_display_keystone_viewer($dbh, $form_id, $element_data);

			if($element_data->is_private && empty($edit_id)){ //don't show private element on live forms
				continue;
			}
			
			//if this is matrix field, build the children data from $matrix_elements array
			if($element_data->type == 'matrix'){
				$element_data->matrix_children = $matrix_elements[$element_data->id];
			}
			
			if($element_data->type == 'casecade_form'){
				$all_element_markup .= viewCasecadeForm(array('dbh' => $dbh, 'form_id' => $element_data->default_value, 'parent_form_id' => $form_id, 'company_id' => $company_id, 'entry_id' => $entry_id, 'form_params' => array('populated_values' => $element_data->populated_value['element_'.$element_data->id])));
			}else if($element_data->type == 'syndication'){
				$all_element_markup .= call_user_func('la_display_'.$element_data->type, $element_data, false, $dbh);	
			}else if($element_data->type == 'file'){
				if( $element_data->file_upload_synced == 1 && !empty($element_data->element_machine_code) ) {
					$element_data->company_id = $company_id;
					$all_element_markup .= call_user_func('la_display_file_synced', $element_data, $dbh, true);
				} else {
					$all_element_markup .= call_user_func('la_display_'.$element_data->type, $element_data, true);
				}
			}else if($element_data->type == 'matrix'){
				$all_element_markup .= call_user_func('la_display_'.$element_data->type,$element_data, true);	
			}
			else{	
				$all_element_markup .= call_user_func('la_display_'.$element_data->type, $element_data, $keystone_viewer, true);
			}
		}
		//die;
		
		return $all_element_markup;
	}
	
	function chkCascadeInViewForm($params=array())
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
	
    function generateReviewDetails($params=array())
	{
    	$dbh = $params['dbh'];
        $record_id = $params['record_id'];
        $use_review_table = $params['use_review_table'];
        $param = $params['param'];
    	$entry_details = $params['entry_details'];
        $form_id = $params['form_id'];
        $entry_detail = '';
        $row = array();
        
        $query = "SELECT *  FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id = :form_id order by element_position asc";
        $sth = la_do_query($query, array(':form_id' => $form_id), $dbh);
        
        while($data = la_do_fetch_result($sth)){
        	$row[$data['element_id']] = $data;
        }
        
    	//echo '<pre>';print_r($entry_details);echo '</pre>';
    	//echo '<pre>';print_r($row);echo '</pre>';
        
        $str = '';
        $str_address = '';
        $str_phone ='';
        $str_time = '';
        $str_name = '';
        $str_date = '';
        $str_price = '';
        $eid = '';
        
    	foreach($entry_details as $key => $val){  
            $key_explode = explode('_',$key);
            $key_new = $key_explode[1];
            //echo '<pre>';print_r($row[$key_new]);echo '</pre>';
            if($row[$key_new]['element_type'] != 'page_break'){            
                if($row[$key_new]['element_type'] == 'checkbox'){
                    
                    $query1 = "SELECT * FROM `".LA_TABLE_PREFIX."element_options` WHERE form_id='".$form_id."' AND element_id='".$key_explode[1]."' AND option_id='".$key_explode[2]."'";
                    $sth1 = la_do_query($query1,array(),$dbh);
                    $row1 = la_do_fetch_result($sth1);
                    
                    if($entry_details[$key]['default_value'] == 1){
                    
                        if(strpos($str,$key_explode[1]) === false){
                      
                            $str .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        /*$entry_detail .= '<img src="images/icons/59_blue_16.png" />'.$row1['option'].' ';
                        
                        if(strpos($str,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }*/
                        
                        if($entry_details[$key]['default_value'] == 1){
                            $entry_detail .= '<img src="images/icons/59_blue_16.png" />'.$row1['option'].' ';
                        
                        }
                    
                        if($row[$key_new]['element_choice_has_other'] == 1 && $eid != $row[$key_new]['element_id']){
                            $entry_detail .= $entry_details['element_'.$row[$key_new]['element_id'].'_other']['default_value'];
                            $eid = $row[$key_new]['element_id'];		
                        }
                        
                   }    
                
                } 
                else if($row[$key_new]['element_type'] == 'radio'){
                
                    $query2 = "SELECT * FROM `".LA_TABLE_PREFIX."element_options` WHERE form_id='".$form_id."' AND element_id='".$key_explode[1]."' AND option_id='".$entry_details[$key]['default_value']."'";
                    $sth2 = la_do_query($query2,array(),$dbh);
                    $row2 = la_do_fetch_result($sth2);
                
                    $entry_detail .= "<tr>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row2['option']."</td>\n";
                    $entry_detail .= "</tr>\n";
                    
                } 
                else if($row[$key_new]['element_type'] == 'select'){
                
                    $query3 = "SELECT * FROM `".LA_TABLE_PREFIX."element_options` WHERE form_id='".$form_id."' AND element_id='".$key_explode[1]."' AND option_id='".$entry_details[$key]['default_value']."'";
                    $sth3 = la_do_query($query3,array(),$dbh);
                    $row3 = la_do_fetch_result($sth3);
                
                    $entry_detail .= "<tr>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row3['option']."</td>\n";
                    $entry_detail .= "</tr>\n";
                    
                } 
                else if($row[$key_new]['element_type'] == 'matrix'){
                
                    $query4 = "SELECT * FROM `".LA_TABLE_PREFIX."element_options` WHERE form_id='".$form_id."' AND element_id='".$key_explode[1]."' AND option_id='".$entry_details[$key]['default_value']."'";
                    $sth4 = la_do_query($query4,array(),$dbh);
                    $row4 = la_do_fetch_result($sth4);
                
                    $entry_detail .= "<tr>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row4['option']."</td>\n";
                    $entry_detail .= "</tr>\n";
                    
                } 
                else if($row[$key_new]['element_type'] == 'address'){
                        
                     if(strpos($str_address,$key_explode[1]) === false){
                      
                            $str_address .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        $entry_detail .= $entry_details[$key]['default_value'].' ';
                        
                        if(strpos($str_address,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }  
                
                
                } 
                else if($row[$key_new]['element_type'] == 'phone'){
                        
                     if(strpos($str_phone,$key_explode[1]) === false){
                      
                            $str_phone .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        $entry_detail .= $entry_details[$key]['default_value'].' ';
                        
                        if(strpos($str_phone,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }  
                
                
                } 
                else if($row[$key_new]['element_type'] == 'time'){
                        
                     if(strpos($str_time,$key_explode[1]) === false){
                      
                            $str_time .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        $entry_detail .= $entry_details[$key]['default_value'].' ';
                        
                        if(strpos($str_time,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }  
                
                
                } 
                else if($row[$key_new]['element_type'] == 'simple_name'){
                        

                     if(strpos($str_name,$key_explode[1]) === false){
                      
                            $str_name .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        $entry_detail .= $entry_details[$key]['default_value'].' ';
                        
                        if(strpos($str_name,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }  
                
                
                } 
                else if($row[$key_new]['element_type'] == 'date' || $row[$key_new]['element_type'] == 'europe_date'){
                    if(strpos($str_date,$key_explode[1]) === false){
                        $str_date .= $key_explode[1].',';
                        $entry_detail .= "<tr>\n";
                        $entry_detail .= '<td class="la_review_section_break" width="20%">'.$row[$key_new]['element_title'].'</td>\n';
                        $entry_detail .= '<td class="la_review_section_break" width="20%">';
                    }
        
        			if($key_explode[2] == 3){
                        $entry_detail .= $entry_details[$key]['default_value'];
                    }else{
                        $entry_detail .= $entry_details[$key]['default_value'].'/';
                    }
                    
                    if(strpos($str_date,$key_explode[1]) === false){
                        $entry_detail .= "</td>\n";
                        $entry_detail .= "</tr>\n"; 
                    }  
                } 
                else if($row[$key_new]['element_type'] == 'money'){
                        
                     if(strpos($str_price,$key_explode[1]) === false){
                      
                            $str_price .= $key_explode[1].',';
                            $entry_detail .= "<tr>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                            $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                         }
            
                        $entry_detail .= $entry_details[$key]['default_value'].' ';
                        
                        if(strpos($str_price,$key_explode[1]) === false){
                            $entry_detail .= "</td>\n";
                            $entry_detail .= "</tr>\n"; 
                        }  
                
                
                } 
                else if($row[$key_new]['element_type'] == 'file'){
                    $entry_detail .= "<tr>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                    if(!empty($entry_details[$key]['default_value'])){
                         $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">";
                        foreach($entry_details[$key]['default_value'] as $val){
                            $fname =  explode('-',$val['filename']);
                            $entry_detail .= $fname[1].' ';
                            
                        }
                       $entry_detail .= "</td>\n";
                    }
                   
                    $entry_detail .= "</tr>\n";
                      
                } 
                else if($row[$key_new]['element_type'] == 'casecade_form') {
                	$case_entry_details = la_get_entry_values($dbh, $row[$key_new]['element_default_value'], $record_id, $use_review_table, $param);
                	$entry_detail .= generateReviewDetails(array('dbh' => $dbh, 'entry_details' => $case_entry_details, 'form_id' => $row[$key_new]['element_default_value'], 'record_id' => $record_id, 'use_review_table' => $use_review_table, 'param' => $param));
                } 
                else {
                    $entry_detail .= "<tr>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$row[$key_new]['element_title']."</td>\n";
                    $entry_detail .= "<td class=\"la_review_section_break\" width=\"20%\">".$entry_details[$key]['default_value']."</td>\n";
                    $entry_detail .= "</tr>\n";
                }
            
            }
        }
        
        return $entry_detail;
    }

	//Casecade Form
	function la_display_casecade_form($element){		
		$li_class = '';
		$el_class = array();
		
		$el_class[] = "casecade_form";
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}
	
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = noHTML($_GET['element_'.$element->id]);
		}
		
		//check for populated value, if exist, use it instead default_value
		if( isset($element->populated_value['element_'.$element->id]['default_value']) && ( !empty($element->populated_value['element_'.$element->id]['default_value']) || is_numeric($element->populated_value['element_'.$element->id]['default_value']) ) ){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}	
		
		if(empty($element->default_value)){
			$element->default_value = 0;
		}
        
        $element->title = empty($element->guidelines) ? str_replace('<strong>', '', $element->title) : $element->guidelines;
					
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
			<h3 id="casecade_{$element->id}">{$element->title}</h3>
		</li>
EOT;
		
		return $element_markup;
	}

	function la_display_keystone_viewer($dbh, $form_id, $element) {
		
		$server_url = 'https://'.$_SERVER['HTTP_HOST'].'/portal/includes/keystone-viewer.php';
		$result = <<<EOT
			<script>
				$('#li_{$element->id}').mouseover(function() {
					let exist_keystoneviewer = $('#li_{$element->id} .keystoneviewer');
					if (exist_keystoneviewer.length) return;
					$.get("$server_url",{
							form_id: {$form_id},
							id: {$element->id},
							element_machine_code: "{$element->element_machine_code}",
							la_client_user_id: {$_SESSION['la_client_user_id']}
						},
						function(res){
							$('#li_{$element->id}').prepend(res);
						}
					);
				});
			</script>
		EOT;
		return $result;
	}

	//Single Line Text
	function la_display_text($element, $keystone_viewer='', $casecade=false){
		global $la_lang;

		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
			if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
			}else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
			}
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){
			if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}
		}
		
		//check for constraint
		if($element->constraint == 'password'){
			$element_type = 'password';
		}else{
			$element_type = 'text';
		}

		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}
		
		//check for populated value, if exist, use it instead default_value
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && (!empty($element->populated_value['element_'.$element->id]['default_value']) || is_numeric($element->populated_value['element_'.$element->id]['default_value']) ) ){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}	
		
		if(!empty($element->is_design_mode)){
			$range_limit_by = '<var class="range_limit_by">'.$range_limit_by.'</var>';
		}
		
		$input_handler = '';
		$maxlength = '';
		
		if($element->range_limit_by == 'c'){
			$range_limit_by = $la_lang['range_type_chars'];
		}
		else if($element->range_limit_by == 'w'){
			$range_limit_by = $la_lang['range_type_words'];
		}
		
		if(!empty($element->range_min) || !empty($element->range_max)){
			$currently_entered_length = 0;
			if(!empty($element->default_value)){
				if($element->range_limit_by == 'c'){
					$currently_entered_length = strlen($element->default_value);
				}else if($element->range_limit_by == 'w'){
					$currently_entered_length = count(preg_split("/[\s\.]+/", $element->default_value, NULL, PREG_SPLIT_NO_EMPTY));
				}
			}
		}
		
		if($casecade){
			if(!empty($element->range_min) && !empty($element->range_max)){
				if($element->range_min == $element->range_max){
					$range_min_max_tag = sprintf($la_lang['range_min_max_same'],"<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}else{
					$range_min_max_tag = sprintf($la_lang['range_min_max'],"<var id=\"range_min_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}
	
				$currently_entered_tag = sprintf($la_lang['range_min_max_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_min_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_max'],"<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_max_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_min'],"<var id=\"range_min_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_min}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_min_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_min_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"count_input({$element->id},'{$element->range_limit_by}');\" onchange=\"count_input({$element->id},'{$element->range_limit_by}');\"";
			}
			else{
				$range_limit_markup = '';
			}
		}else{
			if(!empty($element->range_min) && !empty($element->range_max)){
				if($element->range_min == $element->range_max){
					$range_min_max_tag = sprintf($la_lang['range_min_max_same'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}else{
					$range_min_max_tag = sprintf($la_lang['range_min_max'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}
	
				$currently_entered_tag = sprintf($la_lang['range_min_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_max'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_min'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_min_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"count_input({$element->id},'{$element->range_limit_by}');\" onchange=\"count_input({$element->id},'{$element->range_limit_by}');\"";
			}
			else{
				$range_limit_markup = '';
			}
		}
		
		if(!empty($element->is_design_mode)){
			$input_handler = '';
		}
		
		//if there is any error message unrelated with range rules, don't display the range markup
		global $is_form_submitted;
		if(!empty($error_message) || $is_form_submitted != true ){
			$range_limit_markup = '';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$element->default_value = noHTML($element->default_value);
		$label_styles = label_styles($element);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
		}

		if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div style="width:100%; float:left;">
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" {$maxlength} class="element text {$element->size} socket-enabled" type="{$element_type}" value="{$element->default_value}"  {$input_handler} />
			{$range_limit_markup} 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}
		else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description" for="element_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div style="width:100%; float:left;">
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->id}" name="element_{$element->id}" {$maxlength} class="element text {$element->size} socket-enabled" type="{$element_type}" value="{$element->default_value}"  {$input_handler} />
			{$range_limit_markup} 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}
		

		return $element_markup;
	}	
	
	//Paragraph Text
	function la_display_textarea($element, $keystone_viewer='', $casecade=false){
		global $la_lang;

		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
			if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
			}else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
			}
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){
			if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}

		//check for populated value, if exist, use it instead default_value
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && !empty($element->populated_value['element_'.$element->id]['default_value'])){
			$element->default_value = html_entity_decode($element->populated_value['element_'.$element->id]['default_value']);
		}
		
		if(!empty($element->is_design_mode)){
			$range_limit_by = '<var class="range_limit_by">'.$range_limit_by.'</var>';
		}
		
		$input_handler = '';
		
		if($element->range_limit_by == 'c'){
			$range_limit_by = $la_lang['range_type_chars'];
		}else if($element->range_limit_by == 'w'){
			$range_limit_by = $la_lang['range_type_words'];
		}
		
		if(!empty($element->range_min) || !empty($element->range_max)){
			$currently_entered_length = 0;
			if(!empty($element->default_value)){
				if($element->range_limit_by == 'c'){
					$currently_entered_length = strlen($element->default_value);
				}else if($element->range_limit_by == 'w'){
					$currently_entered_length = count(preg_split("/[\s\.]+/", $element->default_value, NULL, PREG_SPLIT_NO_EMPTY));
				}
			}
		}
		
		if($casecade){
			if(!empty($element->range_min) && !empty($element->range_max)){
				if($element->range_min == $element->range_max){
					$range_min_max_tag = sprintf($la_lang['range_min_max_same'],"<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}else{
					$range_min_max_tag = sprintf($la_lang['range_min_max'],"<var id=\"range_min_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}
	
				$currently_entered_tag = sprintf($la_lang['range_min_max_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_min_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_max'],"<var id=\"range_max_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_max_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_min'],"<var id=\"range_min_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$element->range_min}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_min_entered'],"<var id=\"currently_entered_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}\">{$range_min_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"count_input({$element->id},'{$element->range_limit_by}');\" onchange=\"count_input({$element->id},'{$element->range_limit_by}');\"";
			}
			else{
				$range_limit_markup = '';
			}
		}else{
			if(!empty($element->range_min) && !empty($element->range_max)){
				if($element->range_min == $element->range_max){
					$range_min_max_tag = sprintf($la_lang['range_min_max_same'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}else{
					$range_min_max_tag = sprintf($la_lang['range_min_max'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}
	
				$currently_entered_tag = sprintf($la_lang['range_min_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_max'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				
				if($element->range_limit_by == 'c'){
					$maxlength = 'maxlength="'.$element->range_max.'"';
				}
			}
			elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_min'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_min_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");
	
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"count_input({$element->id},'{$element->range_limit_by}');\" onchange=\"count_input({$element->id},'{$element->range_limit_by}');\"";
			}
			else{
				$range_limit_markup = '';
			}
		}
		
		if(!empty($element->is_design_mode)){
			$input_handler = '';
		}
		
		//if there is any error message unrelated with range rules, don't display the range markup
		global $is_form_submitted;
		if(!empty($error_message) || $is_form_submitted != true ){
			$range_limit_markup = '';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$element->default_value = noHTML($element->default_value);
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));

		$text_formatting = !empty($element->rich_text) ? true : false;
		$additional_class = "";
		

		$field_sub_type = '';
		if ($text_formatting) {
			$additional_class = "textarea-formatting";
			$field_sub_type = 'data-field_sub_type="wysiwyg"';
		}

		$label_styles = label_styles($element);
		
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"textarea\"";
		}

		if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<div style="width:100%; float:left;"><label style="{$label_styles}" class="description" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			<textarea {$element_machine_code_html} {$element_machine_data_html} {$field_type} {$field_sub_type} id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element textarea {$element->size} {$additional_class} socket-enabled" rows="8" cols="90" {$input_handler}>{$element->default_value}</textarea> 
			{$range_limit_markup} 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<div style="width:100%; float:left;"><label style="{$label_styles}" class="description" for="element_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			<textarea {$element_machine_code_html} {$element_machine_data_html} {$field_type} {$field_sub_type} id="element_{$element->id}" name="element_{$element->id}" class="element textarea {$element->size} {$additional_class} socket-enabled" rows="8" cols="90" {$input_handler}>{$element->default_value}</textarea> 
			{$range_limit_markup} 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}
		
		return $element_markup;
	}

	//Signature Field
	function la_display_signature($element, $casecade=false){
		global $la_lang;

		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
			if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
			}else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
			}
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){
			if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
			}
		}
		
		//check for populated value, if exist, use it instead default_value
		$signature_default_value = '[]';
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && !empty($element->populated_value['element_'.$element->id]['default_value'])){
			$signature_default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		if(!empty($element->is_design_mode)){
			$signature_markup = "<div class=\"signature_pad {$element->size}\"><h6>Signature Pad</h6></div>";
		}
		else{
			if($element->size == 'small'){
				$canvas_height = 70;
				$line_margin_top = 50;
			}
			else if($element->size == 'medium'){
				$canvas_height = 130;
				$line_margin_top = 95;
			}
			else{
				$canvas_height = 260;
				$line_margin_top = 200;
			}
			
			if($casecade){
			$signature_markup = <<<EOT
	        <div class="la_sig_wrapper {$element->size}">
	          <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
	          <input element_machine_code="{$element->element_machine_code}" type="hidden" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">
	        </div>
	        <a class="la_sigpad_clear element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_clear" href="#">Clear</a>
	        <script type="text/javascript">
				$(function(){
					var sigpad_options_{$element->parent_form_id}_{$element->form_id}_{$element->id} = {
		               drawOnly : true,
		               displayOnly: false,
		               clear: '.element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_clear',
		               bgColour: '#fff',
		               penColour: '#000',
		               output: '#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}',
		               lineTop: {$line_margin_top},
		               lineMargin: 10,
		               validateFields: false
		        	};
		        	var sigpad_data_{$element->parent_form_id}_{$element->form_id}_{$element->id} = {$signature_default_value};
		      		$('#la_sigpad_{$element->parent_form_id}_{$element->form_id}_{$element->id}').signaturePad(sigpad_options_{$element->parent_form_id}_{$element->form_id}_{$element->id}).regenerate(sigpad_data_{$element->parent_form_id}_{$element->form_id}_{$element->id});
				});
			</script>
EOT;
			}else{
			$signature_markup = <<<EOT
	        <div class="la_sig_wrapper {$element->size}">
	          <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
	          <input element_machine_code="{$element->element_machine_code}" type="hidden" name="element_{$element->id}" id="element_{$element->id}">
	        </div>
	        <a class="la_sigpad_clear element_{$element->id}_clear" href="#">Clear</a>
	        <script type="text/javascript">
				$(function(){
					var sigpad_options_{$element->id} = {
		               drawOnly : true,
		               displayOnly: false,
		               clear: '.element_{$element->id}_clear',
		               bgColour: '#fff',
		               penColour: '#000',
		               output: '#element_{$element->id}',
		               lineTop: {$line_margin_top},
		               lineMargin: 10,
		               validateFields: false
		        	};
		        	var sigpad_data_{$element->id} = {$signature_default_value};
		      		$('#la_sigpad_{$element->id}').signaturePad(sigpad_options_{$element->id}).regenerate(sigpad_data_{$element->id});
				});
			</script>
EOT;
            }
		}
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));

		$label_styles = label_styles($element);

		if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		<div><label style="{$label_styles}" class="description" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div id="la_sigpad_{$element->parent_form_id}_{$element->form_id}_{$element->id}">
			{$signature_markup} 
		</div>{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		<div><label style="{$label_styles}" class="description" for="element_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div id="la_sigpad_{$element->id}">
			{$signature_markup} 

		</div>{$guidelines} {$error_message}
		</li>
EOT;
        }
		return $element_markup;
	}

	//Digital Signature
	function la_display_digital_signature($dbh, $form_id){
		//get the current user info
		$current_user_data = getUserDetailsFromId($dbh, $_SESSION['la_client_user_id']);
		$my_full_name = $current_user_data["full_name"];

		//get the current signature info from DB
		$query = "SELECT * FROM ".LA_TABLE_PREFIX."signed_forms WHERE client_id=? and form_id=?";
		$sth = la_do_query($query, array($_SESSION['la_client_client_id'], $form_id), $dbh);
		$res = la_do_fetch_result($sth);

		if (isset($res)) {
			$cur_signer_id = $res["signer_id"];
			$cur_signed_signature_id = la_sanitize($res["signature_id"]);
			$cur_sign_date = la_sanitize($res["created_at"]);

			$user_data = getUserDetailsFromId($dbh, $cur_signer_id);
			$cur_signer_full_name = $user_data['full_name'];
		}

		$digital_signature_img_link = "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/digital_signature_img.php?q=';
		
		$display_label = isset($cur_signed_signature_id)?"none":"block";
		$display_body = isset($cur_signed_signature_id)?"block":"none";
		$cur_signature_image = <<<EOT
			<div class="digital_signature_btn">
				<div style="margin-top: 30px; padding: 20px; height: 130px; width: 250px; border: 1px dashed #8EACCF;">
					<p style="display:${display_label}">Please click here to sign this form</p>
					<img style="display:${display_body}" class="signer-img" src="{$digital_signature_img_link}{$cur_signed_signature_id}" width="200" height="100"/>
					<div style="float: right; display:${display_body}" class="signer-name">Signed by {$cur_signer_full_name}</div>
					<div style="float: right; display:${display_body}" class="signed-at">Signed at {$cur_sign_date}</div>
				</div>
			</div>
		EOT;

		//get the latest signature information from DB
		$query = "SELECT * FROM ".LA_TABLE_PREFIX."digital_signatures WHERE `id`=(SELECT MAX(id) FROM ".LA_TABLE_PREFIX."digital_signatures WHERE user_id=?)";
		$sth = la_do_query($query, array($_SESSION['la_client_user_id']), $dbh);
		$res = la_do_fetch_result($sth);

		if (isset($res)) {
			$signature_type = la_sanitize($res["signature_type"]);
			$signature_data = la_sanitize($res["signature_data"]);
			$signature_id = la_sanitize($res["signature_id"]);
		}

		$registered_display_label = isset($signature_id)?"Signature Image:":"Please register the digital signature";
		$registered_display_body = isset($signature_id)?"block":"none";
		$show_signature_id = isset($cur_signed_signature_id) ? $cur_signed_signature_id : $signature_id;

		$registered_signature_image = <<<EOT
			<div class="registered_signature_image" style="margin: 40px">
				<p>{$registered_display_label}</p>
				<div style="margin-top: 30px; padding: 20px; height: 130px; width: 250px; border: 1px dashed #8EACCF;">
					<img class="signer-img" src="{$digital_signature_img_link}{$show_signature_id}" width="200" height="100" style="display:{$registered_display_body}"/>
					<div class="signer-name" style="float: right; display:{$registered_display_body}">Signed by {$cur_signer_full_name}</div>
					<div class="signed-at" style="float: right; display:{$registered_display_body}">Signed at yyyy-mm-dd hh:mm:ss</div>
				</div>
			</div>
		EOT;

		$is_show_del_sign_btn = isset($cur_signed_signature_id)?"display:inline-block":"display:none";
		$is_show_sign_btn = !isset($cur_signed_signature_id) && isset($signature_id)?"display:inline-block":"display:none";
		$del_sign_btn = '<button id="del_sign_button" class="bb_button bb_red" style="'.$is_show_del_sign_btn.'">Delete</button>';
		$sign_btn = '<button id="sign_button" class="bb_button bb_green" style="'.$is_show_sign_btn.'">Click to Sign</button>';

		$digital_signature_api_url = "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/digital_signature.php";

		$signature_area = <<<EOT
			<div id="second" class="digital-signature-settings" style="display: none">
				<span>Signature Type:</span>
				<input class="signature_type" type="radio" id="type" name="signature_type" value="type" checked>
				<label for="type">Type</label>
				<input class="signature_type" type="radio" id="draw" name="signature_type" value="draw">
				<label for="draw">Draw</label>
				<input class="signature_type" type="radio" id="image" name="signature_type" value="image">
				<label for="image">Image</label>
				
				<div class="form-group" style="margin-bottom: 12px">
					<label for="signer_full_name">Full Name</label>
					<input class="form-control" type="text" value="{$my_full_name}" disabled style="width: 400px"/>
				</div>
				<div id="type-d-sign" class="d-sign form-group">
					<input id="signer_full_name" name="signer_full_name" class="form-control" style="font-family: Brush Script MT, Brush Script Std, cursive; font-size:28px; width: 400px" value=""/>
				</div>
				<div id="draw-d-sign" class="d-sign"  style="display:none">
					<div id="la_sigpad" class="la_sig_wrapper">
						<canvas class="la_canvas_pad" width="309" height="130"></canvas>
						<input type="hidden" name="signature_data" id="signature_data" value=''/>
					</div>
					<a class="la_sigpad_clear" href="#">Clear</a>
				</div>
				<div id="image-d-sign" class="d-sign" style="display:none">
					<input type='file' id="image-d-sign-file" style="display:block"/>
					<input type='hidden' id="signature_file_data" name="signature_file_data"/>
					<img 
						id="image-d-sign-preview" 
						src='https://www.lifewire.com/thmb/2KYEaloqH6P4xz3c9Ot2GlPLuds=/1920x1080/smart/filters:no_upscale()/cloud-upload-a30f385a928e44e199a62210d578375a.jpg'
						width="100%" 
						height="130" 
						style="margin-top: 5px; margin-bottom: 5px; border: 1px solid #c2cad8;"
					/>
				</div>
				<div>
					<input type="hidden" name="form_name" value="digital_signature"/>
					<input type="hidden" name="signature_id" value="-1"/>
					<button class="cancel_button bb_button bb_small bb_green"> Cancel </button>
					<button id="signature_save_btn" class="bb_button bb_small bb_green"> Sign </button>
				</div>
			</div>
			EOT;

		$element_markup = <<<EOT
			<li id="li_signature">
				{$cur_signature_image}
			</li>
			<div id="digital-signature-dialog" style="text-align:left; display: none">
				<div id="first">
					<p>By signing, I agree to both this agreement and the Consumer Disclosure. My use of my signature is governed by the Terms of Use.</p>
					{$registered_signature_image}
					<div style="margin-top: 10px">
						<button class="cancel_button bb_button bb_green">Cancel</button>
						{$sign_btn}
						{$del_sign_btn}
						<button id="new_signature_btn" class="bb_button bb_small bb_green"> Register New Sign </button>
					</div>
				</div>
				{$signature_area}
			</div>
			<script>
				$(function(){
					$('.digital_signature_btn').click(function() {
						disableScroll();
						$('#digital-signature-dialog').dialog('open');
					});

					$("#digital-signature-dialog").dialog({
						modal: true,
						autoOpen: false,
						closeOnEscape: false,
						width: 450,
						draggable: false,
						resizable: false
					});

					$("#sign_button").click(function() {
						$.post("{$digital_signature_api_url}", {action: "sign", form_id: {$form_id}}, function(data, status) {
							let res = JSON.parse(data);
							if (res.status='ok') {
								updateSignature(res.signature_id, res.signer_full_name, res.created_at);
							}
						});
					});

					$("#del_sign_button").click(function() {
						$.post("{$digital_signature_api_url}", {action: "remove", form_id: {$form_id}}, function(data, status) {
							let res = JSON.parse(data);
							removeSignature(res.signature_id);
						});
					});

					$("#new_signature_btn").click(function() {
						$("#first").hide();
						$("#second").show();
					});

					$(".cancel_button").click(function() {
						enableScroll();
						$("#first").show();
						$("#second").hide();
						$('#digital-signature-dialog').dialog('close');
					});

					$("#signature_save_btn").click(function() {
						var signature_type = $("input[name=signature_type]:checked").val();
						var signer_full_name = $("input[name=signer_full_name]").val();
						var signature_data = $("input[name=signature_data]").val();
						var signature_file_data = $("input[name=signature_file_data]").val();

						$.post("{$digital_signature_api_url}", {
							action: "register_new_signature", 
							form_id: {$form_id}, 
							signature_type: signature_type, 
							signer_full_name: signer_full_name,
							signature_data: signature_data,
							signature_file_data: signature_file_data
						}, function(res, status) {
							$.post("{$digital_signature_api_url}", {action: "sign", form_id: {$form_id}}, function(data, status) {
								// location.reload();
								let res = JSON.parse(data);
								if (res.status='ok') {
									updateSignature(res.signature_id, res.signer_full_name, res.created_at);
								}
							});
						});
					});

					$('.digital-signature-settings').on("change", ".signature_type", function(e){
						$(".d-sign").css("display", "none");
						$('#'+e.target.value+'-d-sign').css("display", "block");
					});
				
					$('.digital-signature-settings').on("change", "#image-d-sign-file", function(e){
						if(e.target.files && e.target.files[0]) {
							var reader = new FileReader();
				
							reader.onload = function (event) {
								$('#image-d-sign-preview').attr('src', event.target.result);
								$('#signature_file_data').val(event.target.result);
							};
				
							reader.readAsDataURL(e.target.files[0]);
						}
					})
				
					var sigpad_options = {
						drawOnly : true,
						displayOnly: false,
						bgColour: '#fff',
						penColour: '#000',
						output: '#signature_data',
						clear: '.la_sigpad_clear',
						lineTop: 110,
						lineMargin: 10,
						validateFields: false
					};
					var sigpad_data = $("#signature_data").val();
					$('#draw-d-sign').signaturePad(sigpad_options);
				});

				function disableScroll() {
					// Get the current page scroll position
					scrollBottom = window.pageYOffset || document.documentElement.scrollBottom;
					scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
				
					// if any scroll is attempted, set this to the previous value
					window.onscroll = function() {
						window.scrollTo(scrollLeft, scrollBottom);
					};
				}

				function updateSignature(signature_id, signer_full_name, created_at) {
					enableScroll();
					$("#first").show();
					$("#second").hide();
					$('#digital-signature-dialog').dialog('close');

					$('.digital_signature_btn p').css('display', 'none');
					$('.digital_signature_btn .signer-img').css('display', 'block');
					$('.digital_signature_btn .signer-name').css('display', 'block');
					$('.digital_signature_btn .signed-at').css('display', 'block');
					$('.digital_signature_btn .signer-img').attr('src', '{$digital_signature_img_link}' + signature_id);
					$('.digital_signature_btn .signer-name').text('Signed by ' + signer_full_name);
					$('.digital_signature_btn .signed-at').text('Signed at ' + created_at);


					$('.registered_signature_image .signer-img').css('display', 'block');
					$('.registered_signature_image .signer-name').css('display', 'block');
					$('.registered_signature_image .signed-at').css('display', 'block');
					$('.registered_signature_image .signer-img').attr('src', '{$digital_signature_img_link}' + signature_id);
					$('.registered_signature_image .signer-name').text('Signed by ' + signer_full_name);
					$('.registered_signature_image .signed-at').text('Signed at ' + created_at);

					$('#del_sign_button').css('display', 'inline-block');
					$('#sign_button').css('display', 'none');
				}

				function removeSignature(signature_id) {
					enableScroll();
					$("#first").show();
					$("#second").hide();
					$('#digital-signature-dialog').dialog('close');

					$('.digital_signature_btn p').css('display', 'block');
					$('.digital_signature_btn .signer-img').css('display', 'none');
					$('.digital_signature_btn .signer-name').css('display', 'none');
					$('.digital_signature_btn .signed-at').css('display', 'none');

					$('.registered_signature_image .signer-img').attr('src', '{$digital_signature_img_link}' + signature_id);
					$('.registered_signature_image .signer-name').text('Signed by ' + '{$cur_signer_full_name}');
					$('.registered_signature_image .signed-at').text('Signed at yyyy-mm-dd hh:mm:ss');

					$('#del_sign_button').css('display', 'none');
					$('#sign_button').css('display', 'inline-block');
				}
				
				function enableScroll() {
					window.onscroll = function() {};
				}
			</script>
			EOT;
		return $element_markup;
	}

	function la_display_file_synced($element, $dbh, $casecade=false, $isEditable=true){
		
		global $la_lang;
		$la_settings = la_get_settings(la_connect_db());
		
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		$itauditmachine_filepath = "";
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}
		
		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}

		if(!empty($element->css_class)){
			$el_class[] = trim($element->css_class);
		}

		if(!empty($element->itauditmachine_path)){
			$itauditmachine_path = $element->itauditmachine_path;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		if(isset($element->itauditmachine_filepath)){
			if(!empty($element->itauditmachine_filepath)){
				$itauditmachine_filepath = $element->itauditmachine_filepath;
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}

		//check for populated value 
		//get data for this machine code
		$sql = "select `files_data` from `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ?;";
		$res = la_do_query($sql,array($element->element_machine_code, $element->company_id),$dbh);
		$row = la_do_fetch_result($res);

		$files_arr = [];
		if( $row['files_data'] )
			$files_arr = json_decode($row['files_data']);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
		}

		if(count($files_arr)){
			
			foreach ($files_arr as $file_name){
				
				$data['filename'] = $file_name;
            	
            	$encoded_file_name = urlencode($file_name);

            	if($casecade){
					$queue_id = "element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$element->element_machine_code}";
                }else{
                	$queue_id = "element_{$element->id}_{$element->element_machine_code}";
                }
				
				$filename_explode1 = explode('-', $data['filename'], 2);
                $display_filename = $filename_explode1[1];
				
				$fileExtension = end(explode(".", $display_filename));
                
                //trim filename if more than 20 characters
				if(strlen($display_filename) > 25){
					$display_filename = substr($display_filename,0,25)."...";
				}else{
					$display_filename = $display_filename;
				}
                
                $key_id_explode1 = explode('_',$filename_explode1[0]);
                $entry_id = $key_id_explode1[2];
					
				if($element->is_edit_entry){
					$db_live_status = 2;
				}else{
					$db_live_status = 1;
				}
				
				//encode the long query string for more readibility
                $q_string = base64_encode("element_machine_code={$element->element_machine_code}&file_name={$encoded_file_name}&call_type=ajax_synced");
				
				$divStyle = "";
				$deleteFile = "";
				$showFile = '';
				$startAnc = '';
				$endAnc = '';				
				$file_name_class = md5($file_name);
				$base64_filename = base64_encode($file_name);
					$deleteFile = <<<EOT
				<div class="cancel">									
							<a href="javascript:remove_synced_attachment('{$base64_filename}','{$element->element_machine_code}', '{$file_name_class}', '{$element->company_id}');"><img border="0" src="{$itauditmachine_path}images/icons/delete.png"></a>
						</div>
EOT;
					$filename_ext   = end(explode(".", $data['filename']));
					if(in_array(strtolower($fileExtension), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
						$filename_path = $la_settings["base_url"]."data/file_upload_synced/{$element->element_machine_code}/".$file_name;
						$filename_path = str_replace("%", "%25", $filename_path);
						$filename_path = str_replace("#", "%23", $filename_path);
						$startAnc = '<a class="entry_link entry-link-preview" href="#" data-identifier="image_format" data-ext="png" data-src="'.$filename_path.'">';
						$endAnc = '</a>';
						$divStyle = 'Style="padding-bottom: 0 !important;"';
						$showFile = '<div style=" width: 100%; padding-top: 15px; margin-top: 15px; "> <img src="'.$filename_path.'" style="width: 100%;"></div>';
					}else{
						$startAnc = '<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$filename_ext.'" data-src="'.$q_string.'">';
						$endAnc = '</a>';
					}
				
				$queue_content .= <<<EOT
						<div class="uploadifyQueueItem completed {$file_name_class}" id="{$queue_id}" {$divStyle}>
							{$deleteFile}{$startAnc}
						<span class="fileName">
						  <img align="absmiddle" src="{$itauditmachine_path}images/icons/attach.gif" class="file_attached">{$display_filename}
						</span>
						{$showFile}
						{$endAnc}
						</div>
EOT;
			
			}
		}
		
		if(!$element->is_design_mode && !empty($element->file_enable_advance)){
			
			$file_token = md5(uniqid(rand(), true)); 
			
			
			//generate parameters for auto upload
			if(!empty($element->file_auto_upload)){
				$auto_upload = 'true';
			}else{
				$auto_upload = 'false';
                
                if($casecade){
                    $upload_link_show_tag = "$(\"#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link,#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive\").show();";
                    $upload_link_hide_tag = "$(\"#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link,#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive\").hide();";
                }else{
                    $upload_link_show_tag = "$(\"#element_{$element->id}_upload_link,#element_{$element->id}_upload_link_uploadifive\").show();";
                    $upload_link_hide_tag = "$(\"#element_{$element->id}_upload_link,#element_{$element->id}_upload_link_uploadifive\").hide();";
                }
			}
			
			//generate parameters for multi upload
			if(!empty($element->file_enable_multi_upload)){
				$multi_upload = 'true';
				$queue_limit  = $element->file_max_selection;
			}else{
				$multi_upload = 'false';
				$queue_limit  = 1;
			}
			
			//generate parameters for file size limit
			if(!empty($element->file_enable_size_limit)){
				if(!empty($element->file_size_max)){
					$file_size_max_bytes = 1048576 * $element->file_size_max;
					$size_limit 			= "'sizeLimit' : {$file_size_max_bytes},";
					$size_limit_uploadifive = "'fileSizeLimit'  : '{$element->file_size_max}MB',";
				}else{
					$size_limit = "'sizeLimit' : 10485760,"; //default 10MB
					$size_limit_uploadifive = "'fileSizeLimit'  : '0',";
				}
			}
			
			if(!empty($element->file_type_list) && !empty($element->file_enable_type_limit)){
				if($element->file_block_or_allow == 'a'){ //if this is an allow list
					$allowed_file_types = explode(',',$element->file_type_list);
					$file_type_limit_exts = implode(',',$allowed_file_types);

					array_walk($allowed_file_types,'getFileExtensions');
					$allowed_file_types_joined = implode(';',$allowed_file_types);
					
					$file_type_limit_allow = <<<EOT
					 'fileExt'     : '{$allowed_file_types_joined}',
  					 'fileDesc'    : '{$allowed_file_types_joined}',
EOT;
					
				}else if($element->file_block_or_allow == 'b'){ //if this is a block list
					$blocked_file_types = explode(',',$element->file_type_list);
					array_walk($blocked_file_types,'getFileTypes');
					$blocked_file_types_joined = implode(',',$blocked_file_types);
					
					$file_type_limit_block = "'fileExtBlocked'  : '{$blocked_file_types_joined}',";
					$file_type_limit_exts  = $blocked_file_types_joined;
				}
			}
			
			$msg_queue_limited = sprintf($la_lang['file_queue_limited'],$queue_limit);
			$msg_upload_max	   = sprintf($la_lang['file_upload_max'],$element->file_size_max);

			// $upload_file_logic = $itauditmachine_path.'upload.php?file_upload_synced=0';
			//save data to file_upload_synced table
			// if( $element->file_upload_synced )
				$upload_file_logic = $itauditmachine_path.'upload.php?file_upload_synced=1';


            if($casecade){
			$uploader_script = <<<EOT
<script type="text/javascript">
	$(function(){
		 if(is_support_html5_uploader()){
		 	$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifive({
		 		'uploadScript'     : '{$upload_file_logic}',
		 		'removeCompleted' : false,
				'formData'         : {
									  'form_id': {$element->form_id},
				        			  'element_id': {$element->id},
				        			  'file_token': '{$file_token}',
				        			  'element_machine_code': '{$element->element_machine_code}',
				        			  'selected_entity_id': $('form').data("selected_entity_id")
				                     },
				'auto'             : {$auto_upload},
				'multi'       	   : {$multi_upload},
				'queueSizeLimit' : {$queue_limit},
				{$size_limit_uploadifive}
				'queueID'          : 'element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue',

				'onAddQueueItem' : function(file) {
		            var file_block_or_allow  = '{$element->file_block_or_allow}';
		            var file_type_limit_exts = '{$file_type_limit_exts}';
		            var file_type_limit_exts_array = file_type_limit_exts.split(',');

		            var uploaded_file_ext 	 = file.name.split('.').pop().toLowerCase();
		            
		            var file_exist_in_array = false;
		            $.each(file_type_limit_exts_array,function(index,value){
		            	if(value == uploaded_file_ext){
		            		file_exist_in_array = true;
		            	}
		            });
					
		            if((file_block_or_allow == 'b' && file_exist_in_array == true) || (file_block_or_allow == 'a' && file_exist_in_array == false)){
		            	$("#" + file.queueItem.attr('id')).addClass('error');
			            $("#" + file.queueItem.attr('id') + ' span.fileinfo').text(" - {$la_lang['file_type_limited']}");
		            }

		            {$upload_link_show_tag}
		            if($("html").hasClass("embed")){
				    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
				   	}
		        },
				'onUploadComplete' : function(file, response) { 
					{$upload_link_hide_tag}

					var is_valid_response = false;
					try{
						var response_json = jQuery.parseJSON(response);
						is_valid_response = true;
					}catch(e){
						is_valid_response = false;
						alert(response);
					}
					var queue_item_id =  file.queueItem.attr('id');
					
					if(is_valid_response == true && response_json.status == "ok"){
						var filename = '';
						if( response_json.file_name_complete )
							filename = response_json.file_name_complete;
						else
							filename = response_json.message;

						var file_name_class = response_json.file_name_class;

						var remove_link = "<a class=\"cancel\" href=\"javascript:remove_synced_attachment('" + filename + "','{$element->element_machine_code}','"+ file_name_class + "', {$element->company_id});\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
						
						$("#" + queue_item_id + " a.close").replaceWith(remove_link);
				        $("#" + queue_item_id + ' span.filename').prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">');

				        $("#" + queue_item_id).addClass(file_name_class);

				        //socket logic
				        var element_machine_code = '{$element->element_machine_code}';
						var selected_entity_id = $('form').data("selected_entity_id");
						var userEmail = $('form').data("useremail");
						if( element_machine_code ) {
					        var data = {
								file_name : response_json.message,
								field_machine_code : element_machine_code,
								selected_entity_id : selected_entity_id,
								userEmail : userEmail,
								fieldType : 'file',
								queue_item_id : file_name_class,
								filename: filename
							};
							console.log('emitting file data', data);
					        socket.emit('file upload', JSON.stringify(data));
				    	}
			        }else{
			        	$("#" + queue_item_id).addClass('error');
			        	$("#" + queue_item_id + " a.close").replaceWith('<img style="float: right" border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
						$("#" + queue_item_id + " span.fileinfo").text(" - {$la_lang['file_error_upload']}");
					} 

					if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
				       	upload_all_files();
					}
				}
			});
			$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link").remove();
		 }
         else{
	     	$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_token").remove();
		 }
    });
</script>
<input type="hidden" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_token" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_token]" value="{$file_token}" />
<a id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link" style="display: none" href="javascript:$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifyUpload();">{$la_lang['file_attach']}</a>
<a id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive" style="display: none" href="javascript:$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifive('upload');">{$la_lang['file_attach']}</a>
EOT;
			$file_queue = "<div id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue\" class=\"file_queue\">{$queue_content}</div>";
            
            }else{
            
			$uploader_script = <<<EOT
<script type="text/javascript">
	$(function(){
		 if(is_support_html5_uploader()){
		 	$('#element_{$element->id}').uploadifive({
		 		'uploadScript'     : '{$upload_file_logic}',
		 		'removeCompleted' : false,
				'formData'         : {
									  'form_id': {$element->form_id},
				        			  'element_id': {$element->id},
				        			  'file_token': '{$file_token}',
				        			  'element_machine_code': '{$element->element_machine_code}',
				        			  'selected_entity_id': $('form').data("selected_entity_id")
				                     },
				'auto'             : {$auto_upload},
				'multi'       	   : {$multi_upload},
				'queueSizeLimit' : {$queue_limit},
				{$size_limit_uploadifive}
				'queueID'          : 'element_{$element->id}_queue',
				'onAddQueueItem' : function(file) {
		            var file_block_or_allow  = '{$element->file_block_or_allow}';
		            var file_type_limit_exts = '{$file_type_limit_exts}';
		            var file_type_limit_exts_array = file_type_limit_exts.split(',');

		            var uploaded_file_ext 	 = file.name.split('.').pop().toLowerCase();
		            
		            var file_exist_in_array = false;
		            $.each(file_type_limit_exts_array,function(index,value){
		            	if(value == uploaded_file_ext){
		            		file_exist_in_array = true;
		            	}
		            });
					
		            if((file_block_or_allow == 'b' && file_exist_in_array == true) || (file_block_or_allow == 'a' && file_exist_in_array == false)){
		            	$("#" + file.queueItem.attr('id')).addClass('error');
			            $("#" + file.queueItem.attr('id') + ' span.fileinfo').text(" - {$la_lang['file_type_limited']}");
		            }

		            {$upload_link_show_tag}
		            if($("html").hasClass("embed")){
				    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
				   	}
		        },
				'onUploadComplete' : function(file, response) { 
					{$upload_link_hide_tag}

					var is_valid_response = false;
					try{
						var response_json = jQuery.parseJSON(response);
						is_valid_response = true;
					}catch(e){
						is_valid_response = false;
						alert(response);
					}
					var queue_item_id =  file.queueItem.attr('id');
					
					if(is_valid_response == true && response_json.status == "ok"){
						var filename = '';
						if( response_json.file_name_complete )
							filename = response_json.file_name_complete;
						else
							filename = response_json.message;

						var file_name_class = response_json.file_name_class;

						var remove_link = "<a class=\"cancel\" href=\"javascript:remove_synced_attachment('" + filename + "','{$element->element_machine_code}','"+ file_name_class + "', {$element->company_id});\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
						
						$("#" + queue_item_id + " a.close").replaceWith(remove_link);
				        $("#" + queue_item_id + ' span.filename').prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">');

				        $("#" + queue_item_id).addClass(file_name_class);

				        //socket logic
				        var element_machine_code = '{$element->element_machine_code}';
						var selected_entity_id = $('form').data("selected_entity_id");
						var userEmail = $('form').data("useremail");
						if( element_machine_code ) {
					        var data = {
								file_name : response_json.message,
								field_machine_code : element_machine_code,
								selected_entity_id : selected_entity_id,
								userEmail : userEmail,
								fieldType : 'file',
								queue_item_id : file_name_class,
								filename: filename
							};
							console.log('emitting file data', data);
					        socket.emit('file upload', JSON.stringify(data));
				    	}

			        }else{
			        	$("#" + queue_item_id).addClass('error');
			        	$("#" + queue_item_id + " a.close").replaceWith('<img style="float: right" border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
						$("#" + queue_item_id + " span.fileinfo").text(" - {$la_lang['file_error_upload']}");
					} 

					if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
				       	upload_all_files();
					}
				}
			});
			$("#element_{$element->id}_upload_link").remove();
		 }
         else{
	     	$("#element_{$element->id}_token").remove();
		 }
    });
</script>
<input type="hidden" id="element_{$element->id}_token" name="element_{$element->id}_token" value="{$file_token}" />
<a id="element_{$element->id}_upload_link" style="display: none" href="javascript:$('#element_{$element->id}').uploadifyUpload();">{$la_lang['file_attach']}</a>
<a id="element_{$element->id}_upload_link_uploadifive" style="display: none" href="javascript:$('#element_{$element->id}').uploadifive('upload');">{$la_lang['file_attach']}</a>
EOT;
			$file_queue = "<div id=\"element_{$element->id}_queue\" class=\"file_queue\">{$queue_content}</div>";
            }
		}
		
		if(!empty($queue_content)){
        	if($casecade){
				$file_queue = "<div id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue\" class=\"file_queue uploadifyQueue\">{$queue_content}</div>";
            }else{
				$file_queue = "<div id=\"element_{$element->id}_queue\" class=\"file_queue uploadifyQueue\">{$queue_content}</div>";
            }
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		//disable the file upload field on design mode
		if($element->is_design_mode){
			$disabled_tag = 'disabled="disabled"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));		
		
		if($casecade){
			if($element->file_enable_advance == 1 && $element->file_select_existing_files == 1) {
				$html_for_selecting_existing_files = "<a class=\"bb_button bb_green btn-select-file-management\" target_id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_selected_existing_files\">File Management</a><input type=\"hidden\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_selected_existing_files\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_selected_existing_files]\">";
			} else {
				$html_for_selecting_existing_files = "";
			}
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		<div><label style="float:left;" class="description" for="element_{$element->id}">{$element->title} {$span_required}</label>{$statusIndicatorHtml}</div>
		<div style="width:100%; float:left;">
			<input {$element_machine_code_html} {$element_machine_data_html} data-field_type="file" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element file" multiple="true" type="file" {$disabled_tag} />
			{$html_for_selecting_existing_files}
			{$file_queue} 
			{$uploader_script}
		</div>{$noteHtml}{$file_option}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
			if($element->file_enable_advance == 1 && $element->file_select_existing_files == 1) {
				$html_for_selecting_existing_files = "<a class=\"bb_button bb_green btn-select-file-management\" target_id=\"element_{$element->id}_selected_existing_files\">File Management</a><input type=\"hidden\" id=\"element_{$element->id}_selected_existing_files\" name=\"element_{$element->id}_selected_existing_files\">";
			} else {
				$html_for_selecting_existing_files = "";
			}
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		<div><label style="float:left;" class="description" for="element_{$element->id}">{$element->title} {$span_required}</label>{$statusIndicatorHtml}</div>
		<div style="width:100%; float:left;">
			<input {$element_machine_code_html} {$element_machine_data_html} data-field_type="file" id="element_{$element->id}" name="element_{$element->id}" class="element file" multiple="true" type="file" {$disabled_tag} />
			{$html_for_selecting_existing_files}
			{$file_queue} 
			{$uploader_script}
		</div>{$noteHtml}{$file_option} {$guidelines} {$error_message}
		</li>
EOT;
        }
		return $element_markup;
	
	}	
	
	//File Upload
	function la_display_file($element, $casecade=false, $isEditable=true){
		global $la_lang;
		$la_settings = la_get_settings(la_connect_db());

		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}
		
		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}

		if(!empty($element->itauditmachine_path)){
			$itauditmachine_path = $element->itauditmachine_path;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}

		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}

		//check for populated value 
		if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
			
			foreach ($element->populated_value['element_'.$element->id]['default_value'] as $data){
				
				$filename_md5 = md5(trim($data['filename']));
            
            	if($casecade){
					$queue_id = "element_{$element->parent_form_id}_{$element->form_id}_{$element->id}".substr(strtoupper(md5(mt_rand())),0,6);
                }else{
                	$queue_id = "element_{$element->id}".substr(strtoupper(md5(mt_rand())),0,6);
                }
				
				$encoded_file_name = urlencode($data['filename']);
				
				$filename_explode1 = explode('-', $data['filename'], 2);
                $display_filename = $filename_explode1[1];
				
				$fileExtension = end(explode(".", $display_filename));
                
                //trim filename if more than 20 characters
				if(strlen($display_filename) > 25){
					$display_filename = substr($display_filename,0,25)."...";
				}else{
					$display_filename = $display_filename;
				}
                
                $key_id_explode1 = explode('_',$filename_explode1[0]);
                $entry_id = $key_id_explode1[2];
					
				if($element->is_edit_entry){
					$db_live_status = 2;
				}else{
					$db_live_status = 1;
				}
				
				//encode the long query string for more readibility
                $q_string = base64_encode("form_id={$element->form_id}&file_name={$encoded_file_name}&call_type=ajax_normal");
				
				$divStyle = "";
				$deleteFile = "";
				$showFile = '';
				$startAnc = '';
				$endAnc = '';
				$base64_filename = base64_encode($data['filename']);
				$deleteFile = <<<EOT
					<div class="cancel">									
						<a href="javascript:remove_attachment('{$base64_filename}',{$element->form_id},{$element->id},'{$queue_id}',{$db_live_status},'{$entry_id}');"><img border="0" src="{$itauditmachine_path}images/icons/delete.png"></a>
					</div>
EOT;
					
				$filename_ext   = end(explode(".", $data['filename']));
				if(in_array(strtolower($fileExtension), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
					$filename_path = $la_settings["base_url"].'data/form_'.$element->form_id.'/files/'.$data['filename'];
					$filename_path = str_replace("%", "%25", $filename_path);
					$filename_path = str_replace("#", "%23", $filename_path);
					$startAnc = '<a class="entry_link entry-link-preview" href="#" data-identifier="image_format" data-ext="png" data-src="'.$filename_path.'">';
					$endAnc = '</a>';
					$divStyle = 'Style="padding-bottom: 0 !important;"';
					$showFile = '<div style=" width: 100%; padding-top: 15px; margin-top: 15px; "> <img src="'.$filename_path.'" style="width: 100%;"></div>';
				}else{
					$startAnc = '<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$filename_ext.'" data-src="'.$q_string.'">';
					$endAnc = '</a>';
				}
				
				$queue_content .= <<<EOT
						<div class="uploadifyQueueItem completed" id="{$queue_id}" {$divStyle}>
							{$deleteFile}{$startAnc}
						<span class="fileName">
						  <img align="absmiddle" src="{$itauditmachine_path}images/icons/attach.gif" class="file_attached">{$display_filename} ({$data['filesize']})
						</span>
						{$showFile}
						{$endAnc}
						</div>
EOT;
			
			}
		}
		
		if(!$element->is_design_mode && !empty($element->file_enable_advance)){
			
			if(!empty($element->populated_value['element_'.$element->id]['file_token'])){
				$file_token = $element->populated_value['element_'.$element->id]['file_token'];
				
				//check for existing listfile
				$listfile_name = $element->itauditmachine_data_path.$element->upload_dir."/form_{$element->parent_form_id}_{$element->form_id}/files/listfile_{$file_token}.php";
				if(file_exists($listfile_name)){
					$uploaded_files = file($listfile_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					array_shift($uploaded_files);
					array_pop($uploaded_files);
					
					foreach($uploaded_files as $tmp_filename_path){
						$file_size = la_format_bytes(filesize($tmp_filename_path));
						
						$tmp_filename_only =  basename($tmp_filename_path);
						$filename_value    =  substr($tmp_filename_only,strpos($tmp_filename_only,'-')+1);
						$filename_value    =  str_replace('.tmp', '', $filename_value);			
						$filename_value	   =  str_replace('|','',$filename_value);
                        if($casecade){
                            $queue_id = "element_{$element->parent_form_id}_{$element->form_id}_{$element->id}".substr(strtoupper(md5(mt_rand())),0,6);
						}else{
                            $queue_id = "element_{$element->id}".substr(strtoupper(md5(mt_rand())),0,6);
                        }
                        
						//trim filename if more than 20 characters
						if(strlen($filename_value) > 20){
							$display_filename = substr($filename_value,0,20)."...";
						}else{
							$display_filename = $filename_value;
						}
						$base64_filename = base64_encode($filename_value);
						$queue_content .= <<<EOT
							<div class="uploadifyQueueItem completed" id="{$queue_id}">
							<div class="cancel">									
							<a href="javascript:remove_attachment('{$base64_filename}',{$element->form_id},{$element->id},'{$queue_id}',0,'{$file_token}');"><img border="0" src="{$itauditmachine_path}images/icons/delete.png"></a>
						    </div>		
							<span class="fileName">
							  <img align="absmiddle" src="{$itauditmachine_path}images/icons/attach.gif" class="file_attached">{$display_filename} ({$file_size})
							</span>
							</div>
EOT;
					}
				}
				
			}else{
				$file_token = md5(uniqid(rand(), true)); 
			}
			
			//generate parameters for auto upload
			if(!empty($element->file_auto_upload)){
				$auto_upload = 'true';
			}else{
				$auto_upload = 'false';
                
                if($casecade){
                    $upload_link_show_tag = "$(\"#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link,#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive\").show();";
                    $upload_link_hide_tag = "$(\"#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link,#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive\").hide();";
                }else{
                    $upload_link_show_tag = "$(\"#element_{$element->id}_upload_link,#element_{$element->id}_upload_link_uploadifive\").show();";
                    $upload_link_hide_tag = "$(\"#element_{$element->id}_upload_link,#element_{$element->id}_upload_link_uploadifive\").hide();";
                }
			}
			
			//generate parameters for multi upload
			if(!empty($element->file_enable_multi_upload)){
				$multi_upload = 'true';
				$queue_limit  = $element->file_max_selection;
			}else{
				$multi_upload = 'false';
				$queue_limit  = 1;
			}
			
			//generate parameters for file size limit
			if(!empty($element->file_enable_size_limit)){
				if(!empty($element->file_size_max)){
					$file_size_max_bytes = 1048576 * $element->file_size_max;
					$size_limit 			= "'sizeLimit' : {$file_size_max_bytes},";
					$size_limit_uploadifive = "'fileSizeLimit'  : '{$element->file_size_max}MB',";
				}else{
					$size_limit = "'sizeLimit' : 10485760,"; //default 10MB
					$size_limit_uploadifive = "'fileSizeLimit'  : '0',";
				}
			}
			
			if(!empty($element->file_type_list) && !empty($element->file_enable_type_limit)){
				if($element->file_block_or_allow == 'a'){ //if this is an allow list
					$allowed_file_types = explode(',',$element->file_type_list);
					$file_type_limit_exts = implode(',',$allowed_file_types);

					array_walk($allowed_file_types,'getFileExtensions');
					$allowed_file_types_joined = implode(';',$allowed_file_types);
					
					$file_type_limit_allow = <<<EOT
					 'fileExt'     : '{$allowed_file_types_joined}',
  					 'fileDesc'    : '{$allowed_file_types_joined}',
EOT;
					
				}else if($element->file_block_or_allow == 'b'){ //if this is a block list
					$blocked_file_types = explode(',',$element->file_type_list);
					array_walk($blocked_file_types,'getFileTypes');
					$blocked_file_types_joined = implode(',',$blocked_file_types);
					
					$file_type_limit_block = "'fileExtBlocked'  : '{$blocked_file_types_joined}',";
					$file_type_limit_exts  = $blocked_file_types_joined;
				}
			}
			
			$msg_queue_limited = sprintf($la_lang['file_queue_limited'],$queue_limit);
			$msg_upload_max	   = sprintf($la_lang['file_upload_max'],$element->file_size_max);
            
            if($casecade){
			$uploader_script = <<<EOT
<script type="text/javascript">
	$(function(){
		 if(is_support_html5_uploader()){
		 	$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifive({
		 		'uploadScript'     : '{$itauditmachine_path}upload.php',
		 		'removeCompleted' : false,
				'formData'         : {
									  'form_id': {$element->form_id},
				        			  'element_id': {$element->id},
				        			  'file_token': '{$file_token}'
				                     },
				'auto'             : {$auto_upload},
				'multi'       	   : {$multi_upload},
				'queueSizeLimit' : {$queue_limit},
				{$size_limit_uploadifive}
				'queueID'          : 'element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue',

				'onAddQueueItem' : function(file) {
		            var file_block_or_allow  = '{$element->file_block_or_allow}';
		            var file_type_limit_exts = '{$file_type_limit_exts}';
		            var file_type_limit_exts_array = file_type_limit_exts.split(',');

		            var uploaded_file_ext 	 = file.name.split('.').pop().toLowerCase();
		            
		            var file_exist_in_array = false;
		            $.each(file_type_limit_exts_array,function(index,value){
		            	if(value == uploaded_file_ext){
		            		file_exist_in_array = true;
		            	}
		            });
					
		            if((file_block_or_allow == 'b' && file_exist_in_array == true) || (file_block_or_allow == 'a' && file_exist_in_array == false)){
		            	$("#" + file.queueItem.attr('id')).addClass('error');
			            $("#" + file.queueItem.attr('id') + ' span.fileinfo').text(" - {$la_lang['file_type_limited']}");
		            }

		            {$upload_link_show_tag}
		            if($("html").hasClass("embed")){
				    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
				   	}
		        },
				'onUploadComplete' : function(file, response) { 
					{$upload_link_hide_tag}

					var is_valid_response = false;
					try{
						var response_json = jQuery.parseJSON(response);
						is_valid_response = true;
					}catch(e){
						is_valid_response = false;
						alert(response);
					}
					var queue_item_id =  file.queueItem.attr('id');
					
					if(is_valid_response == true && response_json.status == "ok"){
						var remove_link = "<a class=\"close\" href=\"javascript:remove_attachment('" + response_json.message + "',{$element->form_id},{$element->id},'" + queue_item_id + "',0,'{$file_token}');\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
						
						$("#" + queue_item_id + " a.close").replaceWith(remove_link);
				        $("#" + queue_item_id + ' span.filename').prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">'); 
			        }else{
			        	$("#" + queue_item_id).addClass('error');
			        	$("#" + queue_item_id + " a.close").replaceWith('<img style="float: right" border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
						$("#" + queue_item_id + " span.fileinfo").text(" - {$la_lang['file_error_upload']}");
					} 

					if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
				       	upload_all_files();
					}
				}
			});
			$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link").remove();
		 }
         else if($.browser.flash == true){
		      $('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadify({
		        'uploader'   	  : '{$itauditmachine_path}js/uploadify/uploadify.swf',
		        'script'     	  : '{$itauditmachine_path}upload.php',
		        'cancelImg'  	  : '{$itauditmachine_path}images/icons/stop.png',
		        'removeCompleted' : false,
		        'displayData' 	  : 'percentage',
		        'scriptData'  : {
		        				 'form_id': {$element->form_id},
		        				 'element_id': {$element->id},
		        				 'file_token': '{$file_token}'
								},
				{$file_type_limit_allow}
				{$file_type_limit_block}
		        'auto'        : {$auto_upload},
		        'multi'       : {$multi_upload},
		        'queueSizeLimit' : {$queue_limit},
		        'onQueueFull'    : function (event,queueSizeLimit) {
				      alert('{$msg_queue_limited}');
				    },
		        'queueID'	  : 'element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue',
		        {$size_limit}
		        'buttonImg'   : '{$itauditmachine_path}images/upload_button.png',
		        'onError'     : function (event,ID,fileObj,errorObj) {
			      	if(errorObj.type == 'file_size_limited'){
			      		$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " span.percentage").text(' - {$msg_upload_max}');
					}else if(errorObj.type == 'file_type_blocked'){
						$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " span.percentage").text(" - {$la_lang['file_type_limited']}");
					}
		        	{$upload_link_hide_tag}
			    },
		        'onSelectOnce' : function(event,data) {
				       {$upload_link_show_tag}
				       check_upload_queue({$element->id},{$multi_upload},{$queue_limit},'{$msg_queue_limited}');
				      
				       if($("html").hasClass("embed")){
				       		$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
				   	   }
				    },
				'onAllComplete' : function(event,data) {
				       $("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link").hide();
				       
				       if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
				       		upload_all_files();
					   }
				    },
				'onComplete'  : function(event, ID, fileObj, response, data) {
					var is_valid_response = false;
					try{
						var response_json = jQuery.parseJSON(response);
						is_valid_response = true;
					}catch(e){
						is_valid_response = false;
						alert(response);
					}
					
					if(is_valid_response == true && response_json.status == "ok"){
						var remove_link = "<a href=\"javascript:remove_attachment('" + response_json.message + "',{$element->form_id},{$element->id},'element_{$element->id}" + ID + "',0,'{$file_token}');\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
						$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " > div.cancel > a").replaceWith(remove_link);
				        $("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " > span.fileName").prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">'); 
			        }else{
			        	$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID).addClass('uploadifyError');
			        	$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " div.cancel > a ").replaceWith('<img border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
						$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" + ID + " span.percentage").text(" - {$la_lang['file_error_upload']}");
					}  
			    }
		      });
			  $("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive").remove();
	     }
         else{
	     	$("#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_token").remove();
		 }
    });
</script>
<input type="hidden" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_token" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_token]" value="{$file_token}" />
<a id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link" style="display: none" href="javascript:$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifyUpload();">{$la_lang['file_attach']}</a>
<a id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_upload_link_uploadifive" style="display: none" href="javascript:$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}').uploadifive('upload');">{$la_lang['file_attach']}</a>
EOT;
			$file_queue = "<div id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue\" class=\"file_queue\">{$queue_content}</div>";
            
            }else{
            
			$uploader_script = <<<EOT
				<script type="text/javascript">
					$(function(){
						if(is_support_html5_uploader()){
							$('#element_{$element->id}').uploadifive({
								'uploadScript'     : '{$itauditmachine_path}upload.php',
								'removeCompleted' : false,
								'formData'         : {
													'form_id': {$element->form_id},
													'element_id': {$element->id},
													'file_token': '{$file_token}'
													},
								'auto'             : {$auto_upload},
								'multi'       	   : {$multi_upload},
								'queueSizeLimit' : {$queue_limit},
								{$size_limit_uploadifive}
								'queueID'          : 'element_{$element->id}_queue',
								'onAddQueueItem' : function(file) {
									var file_block_or_allow  = '{$element->file_block_or_allow}';
									var file_type_limit_exts = '{$file_type_limit_exts}';
									var file_type_limit_exts_array = file_type_limit_exts.split(',');

									var uploaded_file_ext 	 = file.name.split('.').pop().toLowerCase();
									
									var file_exist_in_array = false;
									$.each(file_type_limit_exts_array,function(index,value){
										if(value == uploaded_file_ext){
											file_exist_in_array = true;
										}
									});
									
									if((file_block_or_allow == 'b' && file_exist_in_array == true) || (file_block_or_allow == 'a' && file_exist_in_array == false)){
										$("#" + file.queueItem.attr('id')).addClass('error');
										$("#" + file.queueItem.attr('id') + ' span.fileinfo').text(" - {$la_lang['file_type_limited']}");
									}

									{$upload_link_show_tag}
									if($("html").hasClass("embed")){
										$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
									}
								},
								'onUploadComplete' : function(file, response) { 
									{$upload_link_hide_tag}

									var is_valid_response = false;
									try{
										var response_json = jQuery.parseJSON(response);
										is_valid_response = true;
									}catch(e){
										is_valid_response = false;
										alert(response);
									}
									var queue_item_id =  file.queueItem.attr('id');
									
									if(is_valid_response == true && response_json.status == "ok"){
										var remove_link = "<a class=\"close\" href=\"javascript:remove_attachment('" + response_json.message + "',{$element->form_id},{$element->id},'" + queue_item_id + "',0,'{$file_token}');\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
										
										$("#" + queue_item_id + " a.close").replaceWith(remove_link);
										$("#" + queue_item_id + ' span.filename').prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">'); 
									}else{
										$("#" + queue_item_id).addClass('error');
										$("#" + queue_item_id + " a.close").replaceWith('<img style="float: right" border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
										$("#" + queue_item_id + " span.fileinfo").text(" - {$la_lang['file_error_upload']}");
									} 

									if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
										upload_all_files();
									}
								}
							});
							$("#element_{$element->id}_upload_link").remove();
						}
						else if($.browser.flash == true){
							$('#element_{$element->id}').uploadify({
								'uploader'   	  : '{$itauditmachine_path}js/uploadify/uploadify.swf',
								'script'     	  : '{$itauditmachine_path}upload.php',
								'cancelImg'  	  : '{$itauditmachine_path}images/icons/stop.png',
								'removeCompleted' : false,
								'displayData' 	  : 'percentage',
								'scriptData'  : {
												'form_id': {$element->form_id},
												'element_id': {$element->id},
												'file_token': '{$file_token}'
												},
								{$file_type_limit_allow}
								{$file_type_limit_block}
								'auto'        : {$auto_upload},
								'multi'       : {$multi_upload},
								'queueSizeLimit' : {$queue_limit},
								'onQueueFull'    : function (event,queueSizeLimit) {
									alert('{$msg_queue_limited}');
									},
								'queueID'	  : 'element_{$element->id}_queue',
								{$size_limit}
								'buttonImg'   : '{$itauditmachine_path}images/upload_button.png',
								'onError'     : function (event,ID,fileObj,errorObj) {
									if(errorObj.type == 'file_size_limited'){
										$("#element_{$element->id}" + ID + " span.percentage").text(' - {$msg_upload_max}');
									}else if(errorObj.type == 'file_type_blocked'){
										$("#element_{$element->id}" + ID + " span.percentage").text(" - {$la_lang['file_type_limited']}");
									}
									{$upload_link_hide_tag}
								},
								'onSelectOnce' : function(event,data) {
									{$upload_link_show_tag}
									check_upload_queue({$element->id},{$multi_upload},{$queue_limit},'{$msg_queue_limited}');
									
									if($("html").hasClass("embed")){
											$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
									}
									},
								'onAllComplete' : function(event,data) {
									$("#element_{$element->id}_upload_link").hide();
									
									if($("#form_{$element->parent_form_id}_{$element->form_id}").data('form_submitting') === true){
											upload_all_files();
									}
									},
								'onComplete'  : function(event, ID, fileObj, response, data) {
									var is_valid_response = false;
									try{
										var response_json = jQuery.parseJSON(response);
										is_valid_response = true;
									}catch(e){
										is_valid_response = false;
										alert(response);
									}
									
									if(is_valid_response == true && response_json.status == "ok"){
										var remove_link = "<a href=\"javascript:remove_attachment('" + response_json.message + "',{$element->form_id},{$element->id},'element_{$element->id}" + ID + "',0,'{$file_token}');\"><img border=\"0\" src=\"{$itauditmachine_path}images/icons/delete.png\" /></a>";
										$("#element_{$element->id}" + ID + " > div.cancel > a").replaceWith(remove_link);
										$("#element_{$element->id}" + ID + " > span.fileName").prepend('<img align="absmiddle" class="file_attached" src="{$itauditmachine_path}images/icons/attach.gif">'); 
									}else{
										$("#element_{$element->id}" + ID).addClass('uploadifyError');
										$("#element_{$element->id}" + ID + " div.cancel > a ").replaceWith('<img border="0" src="{$itauditmachine_path}images/icons/exclamation.png" />');
										$("#element_{$element->id}" + ID + " span.percentage").text(" - {$la_lang['file_error_upload']}");
									}  
								}
							});
							$("#element_{$element->id}_upload_link_uploadifive").remove();
						}
						else{
							$("#element_{$element->id}_token").remove();
						}
					});
				</script>
				<input element_machine_code="{$element->element_machine_code}" type="hidden" id="element_{$element->id}_token" name="element_{$element->id}_token" value="{$file_token}" />
				<a id="element_{$element->id}_upload_link" style="display: none" href="javascript:$('#element_{$element->id}').uploadifyUpload();">{$la_lang['file_attach']}</a>
				<a id="element_{$element->id}_upload_link_uploadifive" style="display: none" href="javascript:$('#element_{$element->id}').uploadifive('upload');">{$la_lang['file_attach']}</a>
EOT;
			$file_queue = "<div id=\"element_{$element->id}_queue\" class=\"file_queue\">{$queue_content}</div>";
            }
		}
		
		if(!empty($queue_content)){
        	if($casecade){
				$file_queue = "<div id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_queue\" class=\"file_queue uploadifyQueue\">{$queue_content}</div>";
            }else{
				$file_queue = "<div id=\"element_{$element->id}_queue\" class=\"file_queue uploadifyQueue\">{$queue_content}</div>";
            }
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		//disable the file upload field on design mode
		if($element->is_design_mode){
			$disabled_tag = 'disabled="disabled"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));

		$label_styles = label_styles($element);
		
		if($casecade){
			if($element->file_enable_advance == 1 && $element->file_select_existing_files == 1) {
				$html_for_selecting_existing_files = "<a class=\"bb_button bb_green btn-select-file-management\" target_id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_selected_existing_files\">File Management</a><input type=\"hidden\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_selected_existing_files\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_selected_existing_files]\">";
			} else {
				$html_for_selecting_existing_files = "";
			}
			$element_markup = <<<EOT
					<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
					<div><label style="{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
					<div style="width:100%; float:left;">
						<input id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element file" multiple="true" type="file" element_machine_code="{$element->element_machine_code}" {$disabled_tag} />
						{$html_for_selecting_existing_files}
						{$file_queue} 
						{$uploader_script}
					</div>{$noteHtml}{$file_option}{$guidelines} {$error_message}
					</li>
EOT;
		}else{
			if($element->file_enable_advance == 1 && $element->file_select_existing_files == 1) {
				$html_for_selecting_existing_files = "<a class=\"bb_button bb_green btn-select-file-management\" target_id=\"element_{$element->id}_selected_existing_files\">File Management</a><input type=\"hidden\" id=\"element_{$element->id}_selected_existing_files\" name=\"element_{$element->id}_selected_existing_files\">";
			} else {
				$html_for_selecting_existing_files = "";
			}
			$element_markup = <<<EOT
					<li id="li_{$element->id}" {$li_class}>
					<div><label style="{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
					<div style="width:100%; float:left;">
						<input id="element_{$element->id}" name="element_{$element->id}" class="element file" multiple="true" type="file" element_machine_code="{$element->element_machine_code}" {$disabled_tag} />
						{$html_for_selecting_existing_files}
						{$file_queue}
						{$uploader_script}
					</div>{$noteHtml}{$file_option} {$guidelines} {$error_message}
					</li>
EOT;
        }
		return $element_markup;
	}
	
	//Website
	function la_display_url($element, $keystone_viewer="", $casecade=false){
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for default value
		if(empty($element->default_value)){
			$element->default_value = '';
		}

		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}
		
		//check for populated value, if exist, use it instead default_value
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && !empty($element->populated_value['element_'.$element->id]['default_value'])){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $element->default_value = noHTML($element->default_value);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
		}
		
        if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element text {$element->size} socket-enabled" type="text"  value="{$element->default_value}" /> 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->id}" name="element_{$element->id}" class="element text {$element->size} socket-enabled" type="text"  value="{$element->default_value}" /> 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
		
		return $element_markup;
	}
	
	//Email
	function la_display_email($element, $keystone_viewer='', $casecade=false){
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}

		//check for populated value, if exist, use it instead default_value
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && !empty($element->populated_value['element_'.$element->id]['default_value'])){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $element->default_value = noHTML($element->default_value);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
		}
		
        if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element text {$element->size} socket-enabled" type="text"  value="{$element->default_value}" /> 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->id}" name="element_{$element->id}" class="element text {$element->size} socket-enabled" type="text"  value="{$element->default_value}" /> 
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
		
		return $element_markup;
	}	
	
	//Phone - Extended
	function la_display_phone($element, $keystone_viewer='', $casecade=false){
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('phone');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check default value
		if(!empty($element->default_value)){
			//split into (xxx) xxx - xxxx
			$default_value_1 = substr($element->default_value,0,3);
			$default_value_2 = substr($element->default_value,3,3);
			$default_value_3 = substr($element->default_value,6,4);
		}
		//echo $default_value_1.'-'.$default_value_2.'-'.$default_value_3."<br>";
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1']) && !empty($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2']) && !empty($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3']) && !empty($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		//echo $default_value_1.'-'.$default_value_2.'-'.$default_value_3."<br>";
		//check for populated values, if exist override the default value
        
        $element->populated_value['element_'.$element->id.'_1']['default_value'] = trim($element->populated_value['element_'.$element->id.'_1']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_1']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_1']['default_value']))
        {
        	$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
        }
        
        $element->populated_value['element_'.$element->id.'_2']['default_value'] = trim($element->populated_value['element_'.$element->id.'_2']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_2']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_2']['default_value']))
        {
        	$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
        }
        
        $element->populated_value['element_'.$element->id.'_3']['default_value'] = trim($element->populated_value['element_'.$element->id.'_3']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_3']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_3']['default_value']))
        {
        	$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
        }
		//echo $default_value_1.'-'.$default_value_2.'-'.$default_value_3."<br>";
        //echo '<pre>';print_r($element->populated_value);echo '</pre>';die;
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$label_styles = label_styles($element);
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"phone\"";
		}

        if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="phone_1">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_1" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text socket-enabled" size="3" maxlength="3" value="{$default_value_1}" type="text" /> -
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">###</label>
		</span>
		<span class="phone_2">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_2" id="element{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text socket-enabled" size="3" maxlength="3" value="{$default_value_2}" type="text" /> -
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">###</label>
		</span>
		<span class="phone_3">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_3" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">####</label>
		</span>
		{$guidelines}
        {$error_message}
		<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="phone_1">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_1" id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text socket-enabled" size="3" maxlength="3" value="{$default_value_1}" type="text" /> -
			<label for="element_{$element->id}_1">###</label>
		</span>
		<span class="phone_2">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_2" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text socket-enabled" size="3" maxlength="3" value="{$default_value_2}" type="text" /> -
			<label for="element_{$element->id}_2">###</label>
		</span>
		<span class="phone_3">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="phone_3" id="element_{$element->id}_3" name="element_{$element->id}_3" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->id}_3">####</label>
		</span>
		{$guidelines} {$error_message}
		<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }

		return $element_markup;
	}
	
	//Phone - Simple
	function la_display_simple_phone($element, $keystone_viewer='', $casecade=false){
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}

		//check for populated value
		if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $element->default_value = noHTML($element->default_value);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

        if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element text {$element->size}" type="text"  value="{$element->default_value}" /> 
		</div>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}" name="element_{$element->id}" class="element text {$element->size}" type="text"  value="{$element->default_value}" /> 
		</div>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }
		
		return $element_markup;
	}	
	
	//Date - Normal
	function la_display_date($element, $keystone_viewe='', $casecade=false){
		//echo '<pre>';print_r($element);echo '</pre>';die;
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('date_field');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for default value
		$cal_default_value = '';
		if(!empty($element->default_value)){
			//the default value can be mm/dd/yyyy or any valid english date words
			//we need to convert the default value into three parts (mm, dd, yyyy)
			$element->default_value = strtolower($element->default_value);
            
            if(strpos($element->default_value, "/") !== false){
            	$tmpValueArr = explode("/", $element->default_value);
                $element->default_value = $tmpValueArr[2]."-".$tmpValueArr[0]."-".$tmpValueArr[1];
            }else if($element->default_value == "today"){
				$element->default_value = date("Y-m-d");
			}else if($element->default_value == "tomorrow"){
				$element->default_value = date("Y-m-d", strtotime("+1 day"));
			}else if($element->default_value == "last friday"){
				$element->default_value = date("Y-m-d", strtotime("last Friday"));
			}else if($element->default_value == "+1 week"){
				$element->default_value = date("Y-m-d", strtotime("+1 week"));
			}else if($element->default_value == "last day of next month"){
				$element->default_value = date("Y-m-t", strtotime("+1 month"));
			}else if($element->default_value == "3 days ago"){
				$element->default_value = date("Y-m-d", strtotime("-3 day"));
			}else if($element->default_value == "monday next week"){
				$element->default_value = date("Y-m-d", strtotime("next Monday"));
			}
            
			$timestamp = strtotime($element->default_value);

			if(($timestamp !== false) && ($timestamp != -1)){
				$valid_default_date = date('m-d-Y', $timestamp);
				$valid_default_date = explode('-',$valid_default_date);
				
				$default_value_1 = (int) $valid_default_date[0];
				$default_value_2 = (int) $valid_default_date[1];
				$default_value_3 = (int) $valid_default_date[2];
			}else{ //it's not a valid date, display blank
				$default_value_1 = '';
				$default_value_2 = '';
				$default_value_3 = '';
			}
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
        
		//if there's value submitted from the form, overwrite the default value
        //echo '<pre>';print_r($element->populated_value);echo '</pre>';die;
        $element->populated_value['element_'.$element->id.'_1']['default_value'] = trim($element->populated_value['element_'.$element->id.'_1']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_1']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_1']['default_value']))
        {
        	$default_value_1 = (int) $element->populated_value['element_'.$element->id.'_1']['default_value'];
        }
        
        $element->populated_value['element_'.$element->id.'_2']['default_value'] = trim($element->populated_value['element_'.$element->id.'_2']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_2']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_2']['default_value']))
        {
        	$default_value_2 = (int) $element->populated_value['element_'.$element->id.'_2']['default_value'];
        }
        
        $element->populated_value['element_'.$element->id.'_3']['default_value'] = trim($element->populated_value['element_'.$element->id.'_3']['default_value']);
        if(isset($element->populated_value['element_'.$element->id.'_3']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_3']['default_value']))
        {
        	$default_value_3 = (int) $element->populated_value['element_'.$element->id.'_3']['default_value'];
        }
		
		if(!empty($default_value_1) && !empty($default_value_2) && !empty($default_value_3)){
			$cal_default_value = "\$('#element_{$element->id}_datepick').datepick('setDate', \$.datepick.newDate({$default_value_3}, {$default_value_1}, {$default_value_2}));";
		}
		
		$itauditmachine_path = '';
		if(!empty($element->itauditmachine_path)){
			$itauditmachine_path = $element->itauditmachine_path;
		}

		$cal_min_date = '';
		$cal_max_date = '';
		if(!empty($element->date_enable_range)){
			if(!empty($element->date_range_min) && ($element->date_range_min != '0000-00-00')){ //value: yyyy-mm-dd
				$date_range_min = explode('-',$element->date_range_min);
				$cal_min_date = ", minDate: '{$date_range_min[1]}/{$date_range_min[2]}/{$date_range_min[0]}'"; //the calendar needs mm/dd/yyyy format
			}
			
			if(!empty($element->date_range_max) && ($element->date_range_max != '0000-00-00')){ //value: yyyy-mm-dd
				$date_range_max = explode('-',$element->date_range_max);
				$cal_max_date = ", maxDate: '{$date_range_max[1]}/{$date_range_max[2]}/{$date_range_max[0]}'"; //the calendar needs mm/dd/yyyy format
			}
		}
        
		if(!empty($element->date_disable_past_future)){
			$today_date = date('m/d/Y');
			if($element->date_past_future == 'p'){ //disable past dates
				//set minDate to today's date
				$cal_min_date = ", minDate: '{$today_date}'";
			}else if($element->date_past_future == 'f'){ //disable future dates
				//set maxDate to today's date
				$cal_max_date = ", maxDate: '{$today_date}'";
			}
		}
		
		//disable weekend dates
		$cal_disable_weekend = '';
		if(!empty($element->date_disable_weekend)){
			$cal_disable_weekend = ' , onDate: $.datepick.noWeekends';
		}
		
		//disable specific dates
		$cal_disable_specific = '';
		$cal_disable_specific_callback = '';
		if(!empty($element->date_disable_specific) && !empty($element->date_disabled_list)){
			
			$date_disabled_list = explode(',',$element->date_disabled_list);
			$disabled_days = '';
			foreach ($date_disabled_list as $a_day){
				$a_day = trim($a_day);
				$a_day_exploded = explode('/',$a_day);
				$disabled_days .= "[{$a_day_exploded[0]}, {$a_day_exploded[1]}, {$a_day_exploded[2]}],";
			}
			$disabled_days = rtrim($disabled_days,',');
            
            if($casecade){
			$disabled_days = "var disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id} = [".$disabled_days."];";
		
$cal_disable_specific = <<<EOT
{$disabled_days}
			function disable_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}(date, inMonth) { 
			    var disable_weekend = {$element->date_disable_weekend};
				if (inMonth) { 
					var is_weekend = 0;
					if((date.getDay() || 7) >= 6){
						is_weekend = 1;
					}
					
			    	if(disable_weekend == 1 && is_weekend == 1){
			    		return {selectable: false};
			    	}else{
				        for (i = 0; i < disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}.length; i++) { 
				            if (date.getMonth() + 1 == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][0] && 
				                date.getDate() == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][1] &&
				                date.getFullYear() == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][2]
				                ) { 
				                return {dateClass: 'day_disabled', selectable: false}; 
				            } 
				        } 
			        }
			        
			    } 
			    return {}; 
			}	
EOT;
			$cal_disable_specific_callback = ", onDate: disable_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}";
            }else{
			$disabled_days = "var disabled_days_{$element->id} = [".$disabled_days."];";
		
$cal_disable_specific = <<<EOT
{$disabled_days}
			function disable_days_{$element->id}(date, inMonth) { 
			    var disable_weekend = {$element->date_disable_weekend};
				if (inMonth) { 
					var is_weekend = 0;
					if((date.getDay() || 7) >= 6){
						is_weekend = 1;
					}
					
			    	if(disable_weekend == 1 && is_weekend == 1){
			    		return {selectable: false};
			    	}else{
				        for (i = 0; i < disabled_days_{$element->id}.length; i++) { 
				            if (date.getMonth() + 1 == disabled_days_{$element->id}[i][0] && 
				                date.getDate() == disabled_days_{$element->id}[i][1] &&
				                date.getFullYear() == disabled_days_{$element->id}[i][2]
				                ) { 
				                return {dateClass: 'day_disabled', selectable: false}; 
				            } 
				        } 
			        }
			        
			    } 
			    return {}; 
			}	
EOT;
			$cal_disable_specific_callback = ", onDate: disable_days_{$element->id}";
            }
		}
		
		//if this is edit entry, disable any date rules. admin should be able to use any dates
		if($element->is_edit_entry === true){
			$cal_min_date = '';
			$cal_max_date = '';
			$cal_disable_weekend = '';
			$cal_disable_specific_callback = '';
		}

		if($casecade){	
$calendar_script = <<<EOT
<script type="text/javascript">
			{$cal_disable_specific}
			$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_datepick').datepick({ 
	    		onSelect: select_date_casecade,
	    		showTrigger: '#cal_img_{$element->parent_form_id}_{$element->form_id}_{$element->id}'
	    		{$cal_min_date}
	    		{$cal_max_date}
	    		{$cal_disable_weekend}
	    		{$cal_disable_specific_callback}
			});
			{$cal_default_value}
</script>
EOT;
		}else{
$calendar_script = <<<EOT
<script type="text/javascript">
			{$cal_disable_specific}
			$('#element_{$element->id}_datepick').datepick({ 
	    		onSelect: select_date,
	    		showTrigger: '#cal_img_{$element->id}'
	    		{$cal_min_date}
	    		{$cal_max_date}
	    		{$cal_disable_weekend}
	    		{$cal_disable_specific_callback}
			});
			{$cal_default_value}
</script>
EOT;
        }

		//don't call the calendar script if this is on edit_form page
		$cal_img_style = 'display: none';
		if($element->is_design_mode){
			$calendar_script = '';
			$cal_img_style = 'display: block';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"date\"";
		}
        
        if($casecade){		
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="date_mm">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_mm" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_1}" type="text" /> /
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['date_mm']}</label>
		</span>
		<span class="date_dd">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_dd" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" /> /
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['date_dd']}</label>
		</span>
		<span class="date_yyyy">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_yy" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['date_yyyy']}</label>
		</span>
	
		<span id="calendar_{$element->parent_form_id}_{$element->form_id}_{$element->id}">
		    <input element_machine_code="{$element->element_machine_code}" type="hidden" value="" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_datepick]" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_datepick">
			<div style="{$cal_img_style}"><img id="cal_img_{$element->parent_form_id}_{$element->form_id}_{$element->id}" class="datepicker" src="{$itauditmachine_path}images/calendar.gif" alt="Pick a date." /></div>	
		</span>
		{$calendar_script}
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="date_mm">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_mm" id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_1}" type="text" /> /
			<label for="element_{$element->id}_1">{$la_lang['date_mm']}</label>
		</span>
		<span class="date_dd">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_dd" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" /> /
			<label for="element_{$element->id}_2">{$la_lang['date_dd']}</label>
		</span>
		<span class="date_yyyy">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_yy" id="element_{$element->id}_3" name="element_{$element->id}_3" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->id}_3">{$la_lang['date_yyyy']}</label>
		</span>
	
		<span id="calendar_{$element->id}">
		    <input element_machine_code="{$element->element_machine_code}" type="hidden" value="" name="element_{$element->id}_datepick" id="element_{$element->id}_datepick">
			<div style="{$cal_img_style}"><img id="cal_img_{$element->id}" class="datepicker" src="{$itauditmachine_path}images/calendar.gif" alt="Pick a date." /></div>	
		</span>
		{$calendar_script}{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }
	
		return $element_markup;
	}
	
	//Date - Normal
	function la_display_europe_date($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('europe_date_field');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = (int) htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}

		//check for default value
		$cal_default_value = '';
		if(!empty($element->default_value)){
			//the default value can be mm/dd/yyyy or any valid english date words
			//we need to convert the default value into three parts (dd, mm, yyyy)
			$timestamp = strtotime($element->default_value);

			if(($timestamp !== false) && ($timestamp != -1)){
				$valid_default_date = date('d-m-Y', $timestamp);
				$valid_default_date = explode('-',$valid_default_date);
				
				$default_value_1 = (int) $valid_default_date[0];
				$default_value_2 = (int) $valid_default_date[1];
				$default_value_3 = (int) $valid_default_date[2];
			}else{ //it's not a valid date, display blank
				$default_value_1 = '';
				$default_value_2 = '';
				$default_value_3 = '';
			}
		}
		
		//if there's value submitted from the form, overwrite the default value
		if((isset($element->populated_value['element_'.$element->id.'_1']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_1']['default_value'])) || 
		   (isset($element->populated_value['element_'.$element->id.'_2']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_2']['default_value'])) ||
		   (isset($element->populated_value['element_'.$element->id.'_3']['default_value']) && !empty($element->populated_value['element_'.$element->id.'_3']['default_value']))
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_1 = (int) $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = (int) $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = (int) $element->populated_value['element_'.$element->id.'_3']['default_value'];
		}
		
		if(!empty($default_value_1) && !empty($default_value_2) && !empty($default_value_3)){
        	if($casecade){
                $cal_default_value = "\$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_datepick').datepick('setDate', \$.datepick.newDate({$default_value_3}, {$default_value_2}, {$default_value_1}));";
            }else{
                $cal_default_value = "\$('#element_{$element->id}_datepick').datepick('setDate', \$.datepick.newDate({$default_value_3}, {$default_value_2}, {$default_value_1}));";
            }
		}
		
		$itauditmachine_path = '';
		if(!empty($element->itauditmachine_path)){
			$itauditmachine_path = $element->itauditmachine_path;
		}
		
		$cal_min_date = '';
		$cal_max_date = '';
		if(!empty($element->date_enable_range)){
			if(!empty($element->date_range_min) && ($element->date_range_min != '0000-00-00')){ //value: yyyy-mm-dd
				$date_range_min = explode('-',$element->date_range_min);
				$cal_min_date = ", minDate: '{$date_range_min[1]}/{$date_range_min[2]}/{$date_range_min[0]}'"; //the calendar needs mm/dd/yyyy format
			}
			
			if(!empty($element->date_range_max) && ($element->date_range_max != '0000-00-00')){ //value: yyyy-mm-dd
				$date_range_max = explode('-',$element->date_range_max);
				$cal_max_date = ", maxDate: '{$date_range_max[1]}/{$date_range_max[2]}/{$date_range_max[0]}'"; //the calendar needs mm/dd/yyyy format
			}
		}
		if(!empty($element->date_disable_past_future)){
			$today_date = date('m/d/Y');
			if($element->date_past_future == 'p'){ //disable past dates
				//set minDate to today's date
				$cal_min_date = ", minDate: '{$today_date}'";
			}else if($element->date_past_future == 'f'){ //disable future dates
				//set maxDate to today's date
				$cal_max_date = ", maxDate: '{$today_date}'";
			}
		}
		
		//disable weekend dates
		$cal_disable_weekend = '';
		if(!empty($element->date_disable_weekend)){
			$cal_disable_weekend = ' , onDate: $.datepick.noWeekends';
		}
		
		//disable specific dates
		$cal_disable_specific = '';
		$cal_disable_specific_callback = '';
		if(!empty($element->date_disable_specific) && !empty($element->date_disabled_list)){
			
			$date_disabled_list = explode(',',$element->date_disabled_list);
			$disabled_days = '';
			foreach ($date_disabled_list as $a_day){
				$a_day = trim($a_day);
				$a_day_exploded = explode('/',$a_day);
				$disabled_days .= "[{$a_day_exploded[0]}, {$a_day_exploded[1]}, {$a_day_exploded[2]}],";
			}
			$disabled_days = rtrim($disabled_days,',');
            
            if($casecade){
			$disabled_days = "var disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id} = [".$disabled_days."];";
		
$cal_disable_specific = <<<EOT
{$disabled_days}
			function disable_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}(date, inMonth) { 
			    var disable_weekend = {$element->date_disable_weekend};
				if (inMonth) { 
					var is_weekend = 0;
					if((date.getDay() || 7) >= 6){
						is_weekend = 1;
					}
					
			    	if(disable_weekend == 1 && is_weekend == 1){
			    		return {selectable: false};
			    	}else{
				        for (i = 0; i < disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}.length; i++) { 
				            if (date.getMonth() + 1 == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][0] && 
				                date.getDate() == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][1] &&
				                date.getFullYear() == disabled_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}[i][2]
				                ) { 
				                return {dateClass: 'day_disabled', selectable: false}; 
				            } 
				        } 
			        }
			        
			    } 
			    return {}; 
			}	
EOT;
			$cal_disable_specific_callback = ", onDate: disable_days_{$element->parent_form_id}_{$element->form_id}_{$element->id}";
            }else{
			$disabled_days = "var disabled_days_{$element->id} = [".$disabled_days."];";
		
$cal_disable_specific = <<<EOT
{$disabled_days}
			function disable_days_{$element->id}(date, inMonth) { 
			    var disable_weekend = {$element->date_disable_weekend};
				if (inMonth) { 
					var is_weekend = 0;
					if((date.getDay() || 7) >= 6){
						is_weekend = 1;
					}
					

			    	if(disable_weekend == 1 && is_weekend == 1){
			    		return {selectable: false};
			    	}else{
				        for (i = 0; i < disabled_days_{$element->id}.length; i++) { 
				            if (date.getMonth() + 1 == disabled_days_{$element->id}[i][0] && 
				                date.getDate() == disabled_days_{$element->id}[i][1] &&
				                date.getFullYear() == disabled_days_{$element->id}[i][2]
				                ) { 
				                return {dateClass: 'day_disabled', selectable: false}; 
				            } 
				        } 
			        }
			        
			    } 
			    return {}; 
			}	
EOT;
			$cal_disable_specific_callback = ", onDate: disable_days_{$element->id}";
            }
		}
		
		//if this is edit entry, disable any date rules. admin should be able to use any dates
		if($element->is_edit_entry === true){
			$cal_min_date = '';
			$cal_max_date = '';
			$cal_disable_weekend = '';
			$cal_disable_specific_callback = '';
		}

		if($casecade){	
$calendar_script = <<<EOT
<script type="text/javascript">
			{$cal_disable_specific}
			$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_datepick').datepick({ 
	    		onSelect: select_date_casecade,
	    		showTrigger: '#cal_img_{$element->parent_form_id}_{$element->form_id}_{$element->id}'
	    		{$cal_min_date}
	    		{$cal_max_date}
	    		{$cal_disable_weekend}
	    		{$cal_disable_specific_callback}
			});
			{$cal_default_value}
</script>
EOT;
		}else{
$calendar_script = <<<EOT
<script type="text/javascript">
			{$cal_disable_specific}
			$('#element_{$element->id}_datepick').datepick({ 
	    		onSelect: select_date,
	    		showTrigger: '#cal_img_{$element->id}'
	    		{$cal_min_date}
	    		{$cal_max_date}
	    		{$cal_disable_weekend}
	    		{$cal_disable_specific_callback}
			});
			{$cal_default_value}
</script>
EOT;
        }

		//don't call the calendar script if this is on edit_form page
		$cal_img_style = 'display: none';
		if($element->is_design_mode){
			$calendar_script = '';
			$cal_img_style = 'display: block';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
        
        $label_styles = label_styles($element);
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"date\"";
		}

        if($casecade){	
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="date_dd">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_dd" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_1}" type="text" /> /
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['date_dd']}</label>
		</span>
		<span class="date_mm">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_mm" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" /> /
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['date_mm']}</label>
		</span>
		<span class="date_yyyy">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_yy" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['date_yyyy']}</label>
		</span>
	
		<span id="calendar_{$element->parent_form_id}_{$element->form_id}_{$element->id}">
			<input  element_machine_code="{$element->element_machine_code}" type="hidden" value="" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_datepick]" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_datepick">
			<div style="{$cal_img_style}"><img id="cal_img_{$element->parent_form_id}_{$element->form_id}_{$element->id}" class="datepicker" src="{$itauditmachine_path}images/calendar.gif" alt="Pick a date." /></div>	
		</span>
		{$calendar_script}
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="date_dd">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_dd" id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_1}" type="text" /> /
			<label for="element_{$element->id}_1">{$la_lang['date_dd']}</label>
		</span>
		<span class="date_mm">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_mm" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" /> /
			<label for="element_{$element->id}_2">{$la_lang['date_mm']}</label>
		</span>
		<span class="date_yyyy">
	 		<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="date_yy" id="element_{$element->id}_3" name="element_{$element->id}_3" class="element text socket-enabled" size="4" maxlength="4" value="{$default_value_3}" type="text" />
			<label for="element_{$element->id}_3">{$la_lang['date_yyyy']}</label>
		</span>
	
		<span id="calendar_{$element->id}">
			<input  element_machine_code="{$element->element_machine_code}" type="hidden" value="" name="element_{$element->id}_datepick" id="element_{$element->id}_datepick">
			<div style="{$cal_img_style}"><img id="cal_img_{$element->id}" class="datepicker" src="{$itauditmachine_path}images/calendar.gif" alt="Pick a date." /></div>	
		</span>
		{$calendar_script}
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }
		return $element_markup;
	}	
	
	//Multiple Choice
	function la_display_radio($element, $keystone_viewer='', $casecade=false){
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('multiple_choice');
		
		
		if($element->is_private){
			$el_class[] = 'private';
		}
		
		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->choice_columns)){
			$col_number = (int) $element->choice_columns;
			if($col_number == 2){
				$el_class[] = 'two_columns';
			}else if($col_number == 3){
				$el_class[] = 'three_columns';
			}else if($col_number == 9){
				$el_class[] = 'inline_columns';
			}
		}
		
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			$error_message = "<p class=\"error\">{$element->error_message}</p>";
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		$option_markup = '';
		
		//don't shuffle the choice on edit form page
		if(($element->constraint == 'random') && ($element->is_design_mode != true)){
			$temp = $element->options;
			shuffle($temp);
			$element->options = $temp;
		}
		
		$has_price_definition = false;
		$selected_price_value = 0;

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"radio\"";
		}
		
		foreach ($element->options as $option){
			
			if($option->is_default){
				$checked = 'checked="checked"';
				$selected_price_value = $option->price_definition;

				//default value shouldn't be loaded during edit entry if the field is not admin only
				if(!empty($option->is_default) && empty($element->is_private) && $element->is_edit_entry){
					$checked = '';
				}
			}else{
				$checked = '';
			}
			
			//check for GET parameter to populate default value
			if(isset($_GET['element_'.$element->id]) && $_GET['element_'.$element->id] == $option->id){
				$checked = 'checked="checked"';
				$selected_price_value = $option->price_definition;
			}

			//check for populated values
			if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
				$checked = '';

				if($element->populated_value['element_'.$element->id]['default_value'] == $option->id){
					$checked = 'checked="checked"';
					$selected_price_value = $option->price_definition;
				}
			}
			
			if(isset($option->price_definition)){
				$price_definition_data_attr = 'data-pricedef="'.$option->price_definition.'"';
				$has_price_definition = true;
			}else{
				$price_definition_data_attr = '';
			}
			
            if($casecade){
                $pre_option_markup = '';			
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]\" class=\"element radio socket-enabled\" type=\"radio\" value=\"{$option->id}\" {$checked} onclick=\"\$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_other').val('');\" />\n";
                $pre_option_markup .= "<label class=\"choice\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\">{$option->option}</label>\n";
                
                $option_markup .= '<span>'.$pre_option_markup."</span>\n";
            }else{
                $pre_option_markup = '';			
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"element_{$element->id}\" class=\"element radio socket-enabled\" type=\"radio\" value=\"{$option->id}\" {$checked} onclick=\"\$('#element_{$element->id}_other').val('');\" />\n";
                $pre_option_markup .= "<label class=\"choice\" for=\"element_{$element->id}_{$option->id}\">{$option->option}</label>\n";
                
                $option_markup .= '<span>'.$pre_option_markup."</span>\n";
            }
		}
		
		//if 'other choice' is enabled, add a new choice at the end and add text field
		if(!empty($element->choice_has_other)){
			//check for GET parameter to populate default value
			if($casecade) {
				$dbh = la_connect_db();
				$query_check_existing_value = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$element->form_id}` WHERE `field_name` = ? AND company_id = ?";
				$sth_check_existing_value = la_do_query($query_check_existing_value, array("element_".$element->id, $_SESSION["la_client_entity_id"]), $dbh);
				$res_check_existing_value = la_do_fetch_result($sth_check_existing_value);
				if(empty($res_check_existing_value["data_value"])) {
					$query_other_value = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$element->form_id}` WHERE `field_name` = ? AND company_id = ?";
					$sth_other_value = la_do_query($query_other_value, array("element_".$element->id."_other", $_SESSION["la_client_entity_id"]), $dbh);
					$res_other_value = la_do_fetch_result($sth_other_value);
					
					if(!empty($res_other_value["data_value"])) {
						$other_value = $res_other_value["data_value"];
						$checked = 'checked="checked"';
					} else {
						$other_value = '';
						$checked = '';
					}
				} else {
					$other_value = '';
					$checked = '';
				}
				
				$pre_option_markup = '';
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_radio_other\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]\" class=\"element radio socket-enabled\" type=\"radio\" value=\"\" {$checked} />\n";
                $pre_option_markup .= "<label class=\"choice other\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\">{$element->choice_other_label}</label>\n";
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_radio_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_other]\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_other\" onclick=\"\$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0').prop('checked',true).click();\" />\n";
                
                $option_markup .= '<span>'.$pre_option_markup."</span>\n";
			} else {
				if(isset($_GET['element_'.$element->id.'_other'])){
					$element->populated_value['element_'.$element->id.'_other']['default_value'] = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_other']),ENT_QUOTES);
				}

				if(!empty($element->populated_value['element_'.$element->id.'_other']['default_value'])){
					$other_value = $element->populated_value['element_'.$element->id.'_other']['default_value'];
					$checked = 'checked="checked"';
				}else{
					$checked = '';
					$other_value = '';	
				}
				$pre_option_markup = '';
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_radio_other\" id=\"element_{$element->id}_0\" name=\"element_{$element->id}\" class=\"element radio socket-enabled\" type=\"radio\" value=\"\" {$checked} />\n";
                $pre_option_markup .= "<label class=\"choice other\" for=\"element_{$element->id}_0\">{$element->choice_other_label}</label>\n";
                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_radio_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"element_{$element->id}_other\" id=\"element_{$element->id}_other\" onclick=\"\$('#element_{$element->id}_0').prop('checked',true).click();\" />\n";
                
                $option_markup .= '<span>'.$pre_option_markup."</span>\n";
			}
		}

		if($has_price_definition === true){
			$price_data_tag = 'data-pricefield="radio" data-pricevalue="'.$selected_price_value.'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			{$option_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			{$option_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
		return $element_markup;
	}
	
	//Checkboxes
	function la_display_checkbox($element, $keystone_viewer='', $casecade=false){
		//check for error
        //echo '<pre>';print_r($element);echo '</pre>';
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('checkboxes');
		
		if($element->is_private){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
	
		
		if(!empty($element->choice_columns)){
			$col_number = (int) $element->choice_columns;
			if($col_number == 2){
				$el_class[] = 'two_columns';
			}else if($col_number == 3){
				$el_class[] = 'three_columns';
			}else if($col_number == 9){
				$el_class[] = 'inline_columns';
			}
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			$error_message = "<p class=\"error\">{$element->error_message}</p>";
		}
		
		//build the class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for populated value first, if any exist, unselect all default value
		$is_populated = false;
        
        if(isset($element->options) && (is_array($element->options) || is_object($element->options))){
            foreach ($element->options as $option){			
                if(!empty($element->populated_value['element_'.$element->id.'_'.$option->id]['default_value'])){
                    $is_populated = true;
                    break;
                }
            }
        }
	
		$option_markup = '';
		$has_price_definition = false;
		$selected_price_value = 0;

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"checkbox\"";
		}
		
        if(isset($element->options) && (is_array($element->options) || is_object($element->options))){
            foreach ($element->options as $option){
                if(!$is_populated){
                    if($option->is_default && ($element->is_edit_entry !== true)){
                        $checked = 'checked="checked"';
                        $selected_price_value += (double) $option->price_definition;
                    }else if(isset($_GET['element_'.$element->id.'_'.$option->id])){
                        $checked = 'checked="checked"';
                        $selected_price_value += (double) $option->price_definition;
                    }else{
                        $checked = '';
                    }
                }else{
                    
                    if(!empty($element->populated_value['element_'.$element->id.'_'.$option->id]['default_value'])){
                        $checked = 'checked="checked"';
                        $selected_price_value += (double) $option->price_definition;
                    }else{
                        $checked = '';	
                    }
                }
                
                if(isset($option->price_definition)){
                    $price_definition_data_attr = 'data-pricedef="'.$option->price_definition.'"';
                    $has_price_definition = true;
                }else{
                    $price_definition_data_attr = '';
                }
                
                if($casecade){
                    if($element->enhanced_checkbox == 1){
                		$pre_option_markup = '';
	                    $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_{$option->id}]\" class=\"element element_checkbox enhanced_checkbox socket-enabled\" type=\"checkbox\" value=\"1\" {$checked} />\n";
	                    $pre_option_markup .= "<label class=\"element_checkbox_label enhanced_checkbox_label\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\">{$option->option}</label>\n";
	                    
	                    $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
                	} else {
                		$pre_option_markup = '';
	                    $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_{$option->id}]\" class=\"element element_checkbox checkbox socket-enabled\" type=\"checkbox\" value=\"1\" {$checked} />\n";
	                    $pre_option_markup .= "<label class=\"element_checkbox_label choice\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_{$option->id}\">{$option->option}</label>\n";
	                    
	                    $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
                	}
                }else{
                	if($element->enhanced_checkbox == 1){
                		$pre_option_markup = '';
	                    $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"element_{$element->id}_{$option->id}\" class=\"element element_checkbox enhanced_checkbox socket-enabled\" type=\"checkbox\" value=\"1\" {$checked} />\n";
	                    $pre_option_markup .= "<label class=\"element_checkbox_label enhanced_checkbox_label\" for=\"element_{$element->id}_{$option->id}\">{$option->option}</label>\n";
	                    
	                    $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
                	} else {
                		$pre_option_markup = '';
	                    $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_{$option->id}\" id=\"element_{$element->id}_{$option->id}\" {$price_definition_data_attr} name=\"element_{$element->id}_{$option->id}\" class=\"element element_checkbox checkbox socket-enabled\" type=\"checkbox\" value=\"1\" {$checked} />\n";
	                    $pre_option_markup .= "<label class=\"element_checkbox_label choice\" for=\"element_{$element->id}_{$option->id}\">{$option->option}</label>\n";
	                    
	                    $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
                	}
                }
            }
		}
        
		//if 'other checkbox' is enabled, add a new checkbox at the end and add text field
		if(!empty($element->choice_has_other)){
			//check for GET parameter to populate default value
			if($casecade) {
				$dbh = la_connect_db();
				$query_other_value = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$element->form_id}` WHERE `field_name` = ? AND company_id = ?";
				$sth_other_value = la_do_query($query_other_value, array("element_".$element->id."_other", $_SESSION["la_client_entity_id"]), $dbh);
				$res_other_value = la_do_fetch_result($sth_other_value);
				if(!empty($res_other_value["data_value"])) {
					$other_value = $res_other_value["data_value"];
					$checked = 'checked="checked"';
				} else {
					$other_value = '';
					$checked = '';
				}

				if($element->enhanced_checkbox == 1){
            		$pre_option_markup = '';
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]\" class=\"element element_checkbox enhanced_checkbox socket-enabled\" onchange=\"clear_cb_other(this);\"  type=\"checkbox\" value=\"\" {$checked} />\n";
	                $pre_option_markup .= "<label class=\"element_checkbox_label enhanced_checkbox_label other\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\">{$element->choice_other_label}</label>\n";
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_other]\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_other\" onclick=\"\$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0').prop('checked',true);\" />\n";
	                
	                $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
            	} else {
            		$pre_option_markup = '';
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]\" class=\"element element_checkbox checkbox socket-enabled\" onchange=\"clear_cb_other(this);\"  type=\"checkbox\" value=\"\" {$checked} />\n";
	                $pre_option_markup .= "<label class=\"element_checkbox_label choice other\" for=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0\">{$element->choice_other_label}</label>\n";
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_other]\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_other\" onclick=\"\$('#element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_0').prop('checked',true);\" />\n";
	                
	                $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
            	}  
			} else {
				if(isset($_GET['element_'.$element->id.'_other'])){
					$element->populated_value['element_'.$element->id.'_other']['default_value'] = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_other']),ENT_QUOTES);
				}

				if(!empty($element->populated_value['element_'.$element->id.'_other']['default_value'])){
					$other_value = $element->populated_value['element_'.$element->id.'_other']['default_value'];
					$checked = 'checked="checked"';
				}else{
					$checked = '';
					$other_value = '';	
				}
				if($element->enhanced_checkbox == 1){
            		$pre_option_markup = '';
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other\" id=\"element_{$element->id}_0\" name=\"element_{$element->id}\" class=\"element element_checkbox enhanced_checkbox socket-enabled\" onchange=\"clear_cb_other(this);\"  type=\"checkbox\" value=\"\" {$checked} />\n";
	                $pre_option_markup .= "<label class=\"element_checkbox_label enhanced_checkbox_label other\" for=\"element_{$element->id}_0\">{$element->choice_other_label}</label>\n";
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"element_{$element->id}_other\" id=\"element_{$element->id}_other\" onclick=\"\$('#element_{$element->id}_0').prop('checked',true);\" />\n";
	                
	                $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
            	} else {
            		$pre_option_markup = '';
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other\" id=\"element_{$element->id}_0\" name=\"element_{$element->id}\" class=\"element element_checkbox checkbox socket-enabled\" onchange=\"clear_cb_other(this);\"  type=\"checkbox\" value=\"\" {$checked} />\n";
	                $pre_option_markup .= "<label class=\"element_checkbox_label choice other\" for=\"element_{$element->id}_0\">{$element->choice_other_label}</label>\n";
	                $pre_option_markup .= "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"element_checkbox_other_text\" type=\"text\" value=\"{$other_value}\" class=\"element text other socket-enabled\" name=\"element_{$element->id}_other\" id=\"element_{$element->id}_other\" onclick=\"\$('#element_{$element->id}_0').prop('checked',true);\" />\n";
	                
	                $option_markup .= "<span style='display: block;'>".$pre_option_markup."</span>\n";
            	}
			}		
		}
		
		if($has_price_definition === true){
			$selected_price_value = (double) $selected_price_value;
			$price_data_tag = 'data-pricefield="checkbox" data-pricevalue="'.$selected_price_value.'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			{$option_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="float:none;{$label_styles}" class="description">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div>
			{$option_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
        
		return $element_markup;
	}
	
	//Dropdown
	function la_display_select($element, $keystone_viewer='', $casecade=false){
		//check for error
        //echo '<pre>';print_r($element->populated_value);echo '</pre>';
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('dropdown');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		$option_markup = '';
		$has_price_definition = false;
		$selected_price_value = 0;
		
		$has_default = false;
		foreach ($element->options as $option){
			
			if($option->is_default || (isset($_GET['element_'.$element->id]) && $_GET['element_'.$element->id] == $option->id)){
				$selected = 'selected="selected"';
				$has_default = true;
				$selected_price_value = $option->price_definition;

				//default value shouldn't be loaded during edit entry if the field is not admin only
				if(!empty($option->is_default) && empty($element->is_private) && $element->is_edit_entry){
					$has_default = false;
					$selected = '';
				}
			}else{
				$selected = '';
			}
			
			if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
				$selected = '';
				if($element->populated_value['element_'.$element->id]['default_value'] == $option->id){
					$selected = 'selected="selected"';
					$selected_price_value = $option->price_definition;
				}
			}
			
			if(isset($option->price_definition)){
				$price_definition_data_attr = 'data-pricedef="'.$option->price_definition.'"';
				$has_price_definition = true;
			}else{
				$price_definition_data_attr = '';
			}
			
			$option_markup .= "<option value=\"{$option->id}\" {$price_definition_data_attr} {$selected}>{$option->option}</option>\n";
		}
		
		if(!$has_default){
			if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
				$option_markup = '<option value=""></option>'."\n".$option_markup;
			}else{
				$option_markup = '<option value="" selected="selected"></option>'."\n".$option_markup;
			}
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		if($has_price_definition === true){
			$price_data_tag = 'data-pricefield="select" data-pricevalue="'.$selected_price_value.'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"select\"";
		}
		
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div style="width:100%; float:left;">
		<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} class="element select {$element->size} socket-enabled" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]"> 
			{$option_markup}
		</select>
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$price_data_tag} {$li_class}>
		{$keystone_viewer}
		<div><label style="{$label_styles}" class="description" for="element_{$element->id}">{$element->title} {$span_required} {$statusIndicatorHtml}</label></div>
		<div style="width:100%; float:left;">
		<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} class="element select {$element->size} socket-enabled" id="element_{$element->id}" name="element_{$element->id}"> 
			{$option_markup}
		</select>
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
        
		return $element_markup;
	}	
	
	//Name - Simple
	function la_display_simple_name($element, $keystone_viewer='', $casecade=false){
		
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('simple_name');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}

		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
		}

		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        		
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"simple_name\"";
		}
		
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="simple_name_1">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="firstname" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" type="text" class="element text name-ele-ment socket-enabled" maxlength="255" size="8" value="{$default_value_1}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['name_first']}</label>
		</span>
		<span class="simple_name_2">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="lastname" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" type="text" class="element text name-ele-ment socket-enabled" maxlength="255" size="14" value="{$default_value_2}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['name_last']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_2");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
        }else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="simple_name_1">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="firstname" id="element_{$element->id}_1" name="element_{$element->id}_1" type="text" class="element text name-ele-ment socket-enabled" maxlength="255" size="8" value="{$default_value_1}" />
			<label for="element_{$element->id}_1">{$la_lang['name_first']}</label>
		</span>
		<span class="simple_name_2">
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="lastname" id="element_{$element->id}_2" name="element_{$element->id}_2" type="text" class="element text name-ele-ment socket-enabled" maxlength="255" size="14" value="{$default_value_2}" />
			<label for="element_{$element->id}_2">{$la_lang['name_last']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->id.'_2");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
        }
		return $element_markup;
	}
	
	//Name - Simple, with Middle Name
	function la_display_simple_name_wmiddle($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('simple_name_wmiddle');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		
		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_3']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
		}

		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        		
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="simple_name_wmiddle_1">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" type="text" class="element text" maxlength="255" size="8" value="{$default_value_1}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['name_first']}</label>
		</span>
		<span class="simple_name_wmiddle_2">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" type="text" class="element text" maxlength="255" size="8" value="{$default_value_2}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['name_middle']}</label>
		</span>
		<span class="simple_name_wmiddle_3">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" type="text" class="element text" maxlength="255" size="14" value="{$default_value_3}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['name_last']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_3");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="simple_name_wmiddle_1">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_1" name="element_{$element->id}_1" type="text" class="element text" maxlength="255" size="8" value="{$default_value_1}" />
			<label for="element_{$element->id}_1">{$la_lang['name_first']}</label>
		</span>
		<span class="simple_name_wmiddle_2">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_2" name="element_{$element->id}_2" type="text" class="element text" maxlength="255" size="8" value="{$default_value_2}" />
			<label for="element_{$element->id}_2">{$la_lang['name_middle']}</label>
		</span>
		<span class="simple_name_wmiddle_3">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_3" name="element_{$element->id}_3" type="text" class="element text" maxlength="255" size="14" value="{$default_value_3}" />
			<label for="element_{$element->id}_3">{$la_lang['name_last']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->id.'_3");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
        }
		return $element_markup;
	}

	//Name 
	function la_display_name($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('fullname');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}

		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_4'])){
			$default_value_4 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_4']),ENT_QUOTES);
		}
		
		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_3']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_4']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_4 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
			$default_value_4 = $element->populated_value['element_'.$element->id.'_4']['default_value'];
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
        $default_value_4 = noHTML($default_value_4);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="fullname_1">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" type="text" class="element text" maxlength="255" size="2" value="{$default_value_1}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['name_title']}</label>
		</span>
		<span class="fullname_2">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" type="text" class="element text" maxlength="255" size="8" value="{$default_value_2}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['name_first']}</label>
		</span>
		<span class="fullname_3">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" type="text" class="element text" maxlength="255" size="14" value="{$default_value_3}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['name_last']}</label>
		</span>
		<span class="fullname_4">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_4]" type="text" class="element text" maxlength="255" size="3" value="{$default_value_4}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4">{$la_lang['name_suffix']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_3");
		var name_4 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_4");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_4.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}		
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="fullname_1">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_1" name="element_{$element->id}_1" type="text" class="element text" maxlength="255" size="2" value="{$default_value_1}" />
			<label for="element_{$element->id}_1">{$la_lang['name_title']}</label>
		</span>
		<span class="fullname_2">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_2" name="element_{$element->id}_2" type="text" class="element text" maxlength="255" size="8" value="{$default_value_2}" />
			<label for="element_{$element->id}_2">{$la_lang['name_first']}</label>
		</span>
		<span class="fullname_3">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_3" name="element_{$element->id}_3" type="text" class="element text" maxlength="255" size="14" value="{$default_value_3}" />
			<label for="element_{$element->id}_3">{$la_lang['name_last']}</label>
		</span>
		<span class="fullname_4">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_4" name="element_{$element->id}_4" type="text" class="element text" maxlength="255" size="3" value="{$default_value_4}" />
			<label for="element_{$element->id}_4">{$la_lang['name_suffix']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->id.'_3");
		var name_4 = document.getElementById("element_'.$element->id.'_4");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_4.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
        }
		return $element_markup;
	}
	
	//Name, with Middle
	function la_display_name_wmiddle($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('fullname_wmiddle');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_4'])){
			$default_value_4 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_4']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_5'])){
			$default_value_5 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_5']),ENT_QUOTES);
		}
		
		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_3']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_4']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_5']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_4 = '';
			$default_value_5 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
			$default_value_4 = $element->populated_value['element_'.$element->id.'_4']['default_value'];
			$default_value_5 = $element->populated_value['element_'.$element->id.'_5']['default_value'];
		}

		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}

        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
        $default_value_3 = noHTML($default_value_3);
        $default_value_4 = noHTML($default_value_4);
        $default_value_5 = noHTML($default_value_5);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        		
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="namewm_ext">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" type="text" class="element text large" maxlength="255" value="{$default_value_1}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['name_title']}</label>
		</span>
		<span class="namewm_first">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" type="text" class="element text large" maxlength="255" value="{$default_value_2}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['name_first']}</label>
		</span>
		<span class="namewm_middle">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" type="text" class="element text large" maxlength="255" value="{$default_value_3}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['name_middle']}</label>
		</span>
		<span class="namewm_last">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_4]" type="text" class="element text large" maxlength="255" value="{$default_value_4}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4">{$la_lang['name_last']}</label>
		</span>
		<span class="namewm_ext">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_5" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_5]" type="text" class="element text large" maxlength="255" value="{$default_value_5}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_5">{$la_lang['name_suffix']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_3");
		var name_4 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_4");
		var name_5 = document.getElementById("element_'.$element->parent_form_id.'_'.$element->form_id.'_'.$element->id.'_5");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_4.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_5.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="namewm_ext">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_1" name="element_{$element->id}_1" type="text" class="element text large" maxlength="255" value="{$default_value_1}" />
			<label for="element_{$element->id}_1">{$la_lang['name_title']}</label>
		</span>
		<span class="namewm_first">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_2" name="element_{$element->id}_2" type="text" class="element text large" maxlength="255" value="{$default_value_2}" />
			<label for="element_{$element->id}_2">{$la_lang['name_first']}</label>
		</span>
		<span class="namewm_middle">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_3" name="element_{$element->id}_3" type="text" class="element text large" maxlength="255" value="{$default_value_3}" />
			<label for="element_{$element->id}_3">{$la_lang['name_middle']}</label>
		</span>
		<span class="namewm_last">
			<input element_machine_code="{$element->element_machine_code}"  id="element_{$element->id}_4" name="element_{$element->id}_4" type="text" class="element text large" maxlength="255" value="{$default_value_4}" />
			<label for="element_{$element->id}_4">{$la_lang['name_last']}</label>
		</span>
		<span class="namewm_ext">
			<input  element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_5" name="element_{$element->id}_5" type="text" class="element text large" maxlength="255" value="{$default_value_5}" />
			<label for="element_{$element->id}_5">{$la_lang['name_suffix']}</label>
		</span>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		
        $element_markup .= '<script type="text/javascript">
		var regex  = /^[A-Za-z\']+$/;
		var name_1 = document.getElementById("element_'.$element->id.'_1");
		var name_2 = document.getElementById("element_'.$element->id.'_2");
		var name_3 = document.getElementById("element_'.$element->id.'_3");
		var name_4 = document.getElementById("element_'.$element->id.'_4");
		var name_5 = document.getElementById("element_'.$element->id.'_5");
		
		name_1.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_2.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_3.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_4.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		
		name_5.onblur = function(){
			var v = this.value;
			
			if(!regex.test(v) && v != ""){
				var v = this.value;
				alert("Please enter letters only");
				this.value = "";
			}
		};
		</script>';
        }
		return $element_markup;
	}
	
	//Time
	function la_display_time($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('time_field');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}

		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_4'])){
			if($_GET['element_'.$element->id.'_4'] == 'AM'){
				$selected_am = 'selected';
			}else{
				$selected_pm = 'selected';
			}
		}

		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_3']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
		}
		
		if(!empty($element->populated_value['element_'.$element->id.'_4']['default_value'])){
			if($element->populated_value['element_'.$element->id.'_4']['default_value'] == 'AM'){
				$selected_am = 'selected';
			}else{
				$selected_pm = 'selected';
			}
		}
		
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"time\"";
		}

		if(!empty($element->time_showsecond)){
        	
            if($casecade){
			$seconds_markup =<<<EOT
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="timeseconds" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_3}" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['time_ss']}</label>
		</span>
EOT;
			}else{
			$seconds_markup =<<<EOT
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="timeseconds" id="element_{$element->id}_3" name="element_{$element->id}_3" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_3}" />
			<label for="element_{$element->id}_3">{$la_lang['time_ss']}</label>
		</span>
EOT;
            }
			$seconds_separator = ':';
		}else{
			$seconds_markup = '';
			$seconds_separator = '';
		}
		
		if(empty($element->time_24hour)){
        	if($casecade){
			$am_pm_markup =<<<EOT
		<span>
			<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time24hour" class="element select socket-enabled" style="width:5em" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_4]">
				<option value="AM" {$selected_am}>AM</option>
				<option value="PM" {$selected_pm}>PM</option>
			</select>
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4">AM/PM</label>
		</span>
EOT;
			}else{
			$am_pm_markup =<<<EOT
		<span>
			<select  {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time24hour" class="element select socket-enabled" style="width:5em" id="element_{$element->id}_4" name="element_{$element->id}_4">
				<option value="AM" {$selected_am}>AM</option>
				<option value="PM" {$selected_pm}>PM</option>
			</select>
			<label for="element_{$element->id}_4">AM/PM</label>
		</span>
EOT;
            }
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);
        $default_value_2 = noHTML($default_value_2);
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time_hh" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_1}" /> : 
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['time_hh']}</label>
		</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time_mm" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_2}" /> {$seconds_separator} 
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['time_mm']}</label>
		</span>
		{$seconds_markup}
		{$am_pm_markup}{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time_hh" element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_1}" /> : 
			<label for="element_{$element->id}_1">{$la_lang['time_hh']}</label>
		</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="time_mm" element_machine_code="{$element->element_machine_code}" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text socket-enabled" size="2" type="text" maxlength="2" value="{$default_value_2}" /> {$seconds_separator} 
			<label for="element_{$element->id}_2">{$la_lang['time_mm']}</label>
		</span>
		{$seconds_markup}
		{$am_pm_markup}{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }
		return $element_markup;
	}	
	
	//Price
	function la_display_money($element, $keystone_viewer='', $casecade=false){
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array('price');
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
			
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		if($element->constraint != 'yen'){ 
			if($element->constraint == 'pound'){
				$main_cur  = $la_lang['price_pound_main'];
				$child_cur = $la_lang['price_pound_sub'];
				$cur_symbol = '&#163;';
			}elseif ($element->constraint == 'euro'){
				$main_cur  = $la_lang['price_euro_main'];
				$child_cur = $la_lang['price_euro_sub'];
				$cur_symbol = '&#8364;';
			}elseif ($element->constraint == 'baht'){
				$main_cur  = $la_lang['price_baht_main'];
				$child_cur = $la_lang['price_baht_sub'];
				$cur_symbol = '&#3647;';
			}elseif ($element->constraint == 'rupees'){
				$main_cur  = $la_lang['price_rupees_main'];
				$child_cur = $la_lang['price_rupees_sub'];
				$cur_symbol = 'Rs';
			}elseif ($element->constraint == 'rand'){
				$main_cur  = $la_lang['price_rand_main'];
				$child_cur = $la_lang['price_rand_sub'];
				$cur_symbol = 'R';
			}elseif ($element->constraint == 'forint'){
				$main_cur  = $la_lang['price_forint_main'];
				$child_cur = $la_lang['price_forint_sub'];
				$cur_symbol = '&#70;&#116;';
			}elseif ($element->constraint == 'franc'){
				$main_cur  = $la_lang['price_franc_main'];
				$child_cur = $la_lang['price_franc_sub'];
				$cur_symbol = 'CHF';
			}elseif ($element->constraint == 'koruna'){
				$main_cur  = $la_lang['price_koruna_main'];
				$child_cur = $la_lang['price_koruna_sub'];
				$cur_symbol = '&#75;&#269;';
			}elseif ($element->constraint == 'krona'){
				$main_cur  = $la_lang['price_krona_main'];
				$child_cur = $la_lang['price_krona_sub'];
				$cur_symbol = 'kr';
			}elseif ($element->constraint == 'pesos'){
				$main_cur  = $la_lang['price_pesos_main'];
				$child_cur = $la_lang['price_pesos_sub'];
				$cur_symbol = '&#36;';
			}elseif ($element->constraint == 'ringgit'){
				$main_cur  = $la_lang['price_ringgit_main'];
				$child_cur = $la_lang['price_ringgit_sub'];
				$cur_symbol = 'RM';
			}elseif ($element->constraint == 'zloty'){
				$main_cur  = $la_lang['price_zloty_main'];
				$child_cur = $la_lang['price_zloty_sub'];
				$cur_symbol = '&#122;&#322;';
			}elseif ($element->constraint == 'riyals'){
				$main_cur  = $la_lang['price_riyals_main'];
				$child_cur = $la_lang['price_riyals_sub'];
				$cur_symbol = '&#65020;';
			}else{ //dollar
				$main_cur  = $la_lang['price_dollar_main'];
				$child_cur = $la_lang['price_dollar_sub'];
				$cur_symbol = '$';
			}

			//check for GET parameter to populate default value
			if(isset($_GET['element_'.$element->id.'_1'])){
				$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
			}
			if(isset($_GET['element_'.$element->id.'_2'])){
				$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
			}

			//check for populated values, if exist override the default value

			if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
			   !empty($element->populated_value['element_'.$element->id.'_2']['default_value'])
			){
				$default_value_1 = '';
				$default_value_2 = '';
				$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
				$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			}

			if(isset($element->price_definition)){
				$price_value  = $default_value_1.'.'.$default_value_2;
				$price_value  = (double) $price_value;
				
				$price_data_tag = 'data-pricevalue="'.$price_value.'" data-pricefield="money"';
			}	
			$label_styles = label_styles($element);

			if( !empty($element->element_machine_code) ) {
				$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
				$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
				$field_type = "data-field_type=\"money\"";
			}
		
            if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class} {$price_data_tag}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="symbol">{$cur_symbol}</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_main_cur" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text currency socket-enabled" size="10" value="{$default_value_1}" type="text" /> .		
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$main_cur}</label>
		</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_child_cur" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" />
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$child_cur}</label>
		</span>
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
            }else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class} {$price_data_tag}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		<span class="symbol">{$cur_symbol}</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_main_cur" id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text currency socket-enabled" size="10" value="{$default_value_1}" type="text" /> .		
			<label for="element_{$element->id}_1">{$main_cur}</label>
		</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_child_cur" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text socket-enabled" size="2" maxlength="2" value="{$default_value_2}" type="text" />
			<label for="element_{$element->id}_2">{$child_cur}</label>
		</span>
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        	}
		}else{ //for yen, only display one textfield
			$main_cur  = $la_lang['price_yen'];
			$cur_symbol = '&#165;';

			//check for GET parameter to populate default value
			if(isset($_GET['element_'.$element->id])){
				$default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
			}
		
			//check for populated values, if exist override the default value
			if(!empty($element->populated_value['element_'.$element->id]['default_value'])){
				$default_value = '';
				$default_value = $element->populated_value['element_'.$element->id]['default_value'];
			}
			
			if(isset($element->price_definition)){
				$price_value  = $default_value;
				$price_value  = (double) $price_value;
				
				$price_data_tag = 'data-pricevalue="'.$price_value.'" data-pricefield="money_simple"';
			}
            
        $default_value = noHTML($default_value);
						
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class} {$price_data_tag}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required}</label>
		<span class="symbol">{$cur_symbol}</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_main_cur" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element text currency socket-enabled" size="10" value="{$default_value}" type="text" />	
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$main_cur}</label>
		</span>
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
			}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class} {$price_data_tag}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->id}">{$element->title} {$span_required}</label>
		<span class="symbol">{$cur_symbol}</span>
		<span>
			<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="money_main_cur" id="element_{$element->id}" name="element_{$element->id}" class="element text currency socket-enabled" size="10" value="{$default_value}" type="text" />	
			<label for="element_{$element->id}">{$main_cur}</label>
		</span>
		{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
            }
		}

		return $element_markup;
	}
	
	//Section Break
	function la_display_section($element, $casecade=false){
		$li_class = '';
		$el_class = array();
		
		$el_class[] = "section_break";
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
	

		if(!empty($element->section_enable_scroll)){
			if($element->size == 'large'){
				$el_class[] = 'section_scroll_large';
			}else if($element->size == 'medium'){
				$el_class[] = 'section_scroll_medium';
			}else{
				$el_class[] = 'section_scroll_small';
			}
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $element->title = str_replace(array('<strong>', '</strong>'), array('', ''), $element->title);
		
		$element->guidelines = nl2br(str_replace(array('<strong>', '</strong>'), array('', ''), $element->guidelines));	
        
        if($casecade){		
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
			<h3>{$element->title}</h3>
			<p>{$element->guidelines}</p>
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
			<h3>{$element->title}</h3>
			<p>{$element->guidelines}</p>
		</li>
EOT;
        }
		return $element_markup;
	}
	
	//Page Break
	function la_display_page_break($element, $casecade=false){
		//echo '<pre>';print_r($element);echo '</pre>';
		$firstpage_class = '';
		
		if($element->page_number == 1){
			$firstpage_class = ' firstpage';
		}
		
		if($element->submit_use_image == 1){
			$btn_class = ' hide';

			$image_class = '';
		}else{
			$btn_class = '';
			$image_class = ' hide';
		}
		
		if(empty($element->submit_primary_img)){
			$element->submit_primary_img = 'images/empty.gif';
		}
		
		if(empty($element->submit_secondary_img)){
			$element->submit_secondary_img = 'images/empty.gif';
		}
		
        if($casecade){
$element_markup = '';
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" class="page_break{$firstpage_class}" title="Click to edit">
			<div>
				<table class="ap_table_pagination" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td align="left" style="vertical-align: bottom">
							<input  element_machine_code="{$element->element_machine_code}" type="submit" disabled="disabled" value="{$element->submit_primary_text}" id="btn_submit_{$element->id}" name="btn_submit_{$element->id}" class="btn_primary btn_submit{$btn_class}">
							<input  element_machine_code="{$element->element_machine_code}" type="submit" disabled="disabled" value="{$element->submit_secondary_text}" id="btn_prev_{$element->id}" name="btn_prev_{$element->id}" class="btn_secondary btn_submit{$btn_class}">
							<input  element_machine_code="{$element->element_machine_code}" type="image" disabled="disabled" src="{$element->submit_primary_img}" alt="Continue" value="Continue" id="img_submit_{$element->id}" name="img_submit_{$element->id}" class="img_primary img_submit{$image_class}">
							<input element_machine_code="{$element->element_machine_code}"  type="image" disabled="disabled" src="{$element->submit_secondary_img}" alt="Previous" value="Previous" id="img_prev_{$element->id}" name="img_prev_{$element->id}" class="img_secondary img_submit{$image_class}">
						</td> 
						<td align="center" style="vertical-align: top" width="75px">
							<span id="pagenum_{$element->id}" name="pagenum_{$element->id}" class="ap_tp_num">{$element->page_number}</span>
							<span id="pagetotal_{$element->id}" name="pagetotal_{$element->id}" class="ap_tp_text">Page {$element->page_number} of {$element->page_total}</span>
						</td>
					</tr>
				</table>
			</div>
		</li>	
EOT;
        }
		
        return $element_markup;
	}	
	
	//Number
	function la_display_number($element, $keystone_viewer='', $casecade=false){
		
		global $la_lang;

		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		$el_class = array();
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
				$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){			
            if($casecade){
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
				$guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id])){
			$element->default_value = htmlspecialchars(la_sanitize($_GET['element_'.$element->id]),ENT_QUOTES);
		}

		//check for populated value, if exist, use it instead default_value
		if(isset($element->populated_value['element_'.$element->id]['default_value']) && !empty($element->populated_value['element_'.$element->id]['default_value'])){
			$element->default_value = $element->populated_value['element_'.$element->id]['default_value'];
		}
		
		$input_handler = '';
		$maxlength = '';
		
		if(!empty($element->range_min) || !empty($element->range_max)){
			$currently_entered_length = 0;
			if(!empty($element->default_value) && $element->range_limit_by == 'd'){
					$currently_entered_length = strlen($element->default_value);
			}
		}
		
		if($element->range_limit_by == 'd'){
			$range_limit_by = $la_lang['range_type_digit'];
			
			if(!empty($element->is_design_mode)){
				$range_limit_by = '<var class="range_limit_by">'.$range_limit_by.'</var>';
			}
			
			if(!empty($element->range_min) && !empty($element->range_max)){
				if($element->range_min == $element->range_max){
					$range_min_max_tag = sprintf($la_lang['range_min_max_same'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}else{
					$range_min_max_tag = sprintf($la_lang['range_min_max'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				}

				$currently_entered_tag = sprintf($la_lang['range_min_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");

				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				$maxlength = 'maxlength="'.$element->range_max.'"';
			}elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_max'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_max_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");

				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_max_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\" onchange=\"limit_input({$element->id},'{$element->range_limit_by}',{$element->range_max});\"";
				$maxlength = 'maxlength="'.$element->range_max.'"';
			}elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_min'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var> {$range_limit_by}");
				$currently_entered_tag = sprintf($la_lang['range_min_entered'],"<var id=\"currently_entered_{$element->id}\">{$currently_entered_length}</var> {$range_limit_by}");

				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_tag}&nbsp;&nbsp; <em class=\"currently_entered\">{$currently_entered_tag}</em></label>";
				$input_handler = "onkeyup=\"count_input({$element->id},'{$element->range_limit_by}');\" onchange=\"count_input({$element->id},'{$element->range_limit_by}');\"";
			}else{
				$range_limit_markup = '';
			}

			
		}else if($element->range_limit_by == 'v'){
			if(!empty($element->range_min) && !empty($element->range_max)){
				$range_min_max_tag = sprintf($la_lang['range_number_min_max'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var>","<var id=\"range_max_{$element->id}\">{$element->range_max}</var>");
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_max_tag}</label>";
			}elseif(!empty($element->range_max)){
				$range_max_tag = sprintf($la_lang['range_number_max'],"<var id=\"range_max_{$element->id}\">{$element->range_max}</var>");
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_max_tag}</label>";
			}elseif(!empty($element->range_min)){
				$range_min_tag = sprintf($la_lang['range_number_min'],"<var id=\"range_min_{$element->id}\">{$element->range_min}</var>");
				$range_limit_markup = "<label for=\"element_{$element->id}\">{$range_min_tag}</label>";
			}else{
				$range_limit_markup = '';
			}
		}
		
		if(!empty($element->is_design_mode)){
			$input_handler = '';
		}
		
		//if there is any error message unrelated with range rules, don't display the range markup
		global $is_form_submitted;
		if(!empty($error_message) || $is_form_submitted != true ){
			$range_limit_markup = '';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}

		//build the tag for the quantity link if exist
		if(!empty($element->number_quantity_link)){
			$quantity_link_data_tag = 'data-quantity_link="'.$element->number_quantity_link.'"';
		}
        
        $element->default_value = noHTML($element->default_value);	
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
		}
		
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}]" class="element text {$element->size} socket-enabled" type="text" {$maxlength} {$quantity_link_data_tag} value="{$element->default_value}" {$input_handler} /> 
			{$range_limit_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
		}else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}" for="element_{$element->id}">{$element->title} {$span_required}</label>
		<div>
			<input {$element_machine_code_html} {$element_machine_data_html} id="element_{$element->id}" name="element_{$element->id}" class="element text {$element->size} socket-enabled" type="text" {$maxlength} {$quantity_link_data_tag} value="{$element->default_value}" {$input_handler} /> 
			{$range_limit_markup}
		</div>{$noteHtml}{$guidelines} {$error_message}
		</li>
EOT;
        }
		
		return $element_markup;
	}
	
	//Address
	function la_display_address($element, $keystone_viewer='', $casecade=false){
		
		$country = la_get_country_list();
        
		$state_list[0]['label'] = 'Alabama';
		$state_list[1]['label'] = 'Alaska';
		$state_list[2]['label'] = 'Arizona';
		$state_list[3]['label'] = 'Arkansas';
		$state_list[4]['label'] = 'California';
		$state_list[5]['label'] = 'Colorado';
		$state_list[6]['label'] = 'Connecticut';
		$state_list[7]['label'] = 'Delaware';
		$state_list[8]['label'] = 'District of Columbia';
		$state_list[9]['label'] = 'Florida';
		$state_list[10]['label'] = 'Georgia';
		$state_list[11]['label'] = 'Hawaii';
		$state_list[12]['label'] = 'Idaho';
		$state_list[13]['label'] = 'Illinois';
		$state_list[14]['label'] = 'Indiana';
		$state_list[15]['label'] = 'Iowa';
		$state_list[16]['label'] = 'Kansas';
		$state_list[17]['label'] = 'Kentucky';
		$state_list[18]['label'] = 'Louisiana';
		$state_list[19]['label'] = 'Maine';
		$state_list[20]['label'] = 'Maryland';
		$state_list[21]['label'] = 'Massachusetts';
		$state_list[22]['label'] = 'Michigan';
		$state_list[23]['label'] = 'Minnesota';
		$state_list[24]['label'] = 'Mississippi';
		$state_list[25]['label'] = 'Missouri';
		$state_list[26]['label'] = 'Montana';
		$state_list[27]['label'] = 'Nebraska';
		$state_list[28]['label'] = 'Nevada';
		$state_list[29]['label'] = 'New Hampshire';
		$state_list[30]['label'] = 'New Jersey';
		$state_list[31]['label'] = 'New Mexico';
		$state_list[32]['label'] = 'New York';
		$state_list[33]['label'] = 'North Carolina';
		$state_list[34]['label'] = 'North Dakota';
		$state_list[35]['label'] = 'Ohio';
		$state_list[36]['label'] = 'Oklahoma';
		$state_list[37]['label'] = 'Oregon';
		$state_list[38]['label'] = 'Pennsylvania';
		$state_list[39]['label'] = 'Rhode Island';
		$state_list[40]['label'] = 'South Carolina';
		$state_list[41]['label'] = 'South Dakota';
		$state_list[42]['label'] = 'Tennessee';
		$state_list[43]['label'] = 'Texas';
		$state_list[44]['label'] = 'Utah';
		$state_list[45]['label'] = 'Vermont';
		$state_list[46]['label'] = 'Virginia';
		$state_list[47]['label'] = 'Washington';
		$state_list[48]['label'] = 'West Virginia';
		$state_list[49]['label'] = 'Wisconsin';
		$state_list[50]['label'] = 'Wyoming';
		$state_list[0]['value'] = 'Alabama';
		$state_list[1]['value'] = 'Alaska';
		$state_list[2]['value'] = 'Arizona';
		$state_list[3]['value'] = 'Arkansas';
		$state_list[4]['value'] = 'California';
		$state_list[5]['value'] = 'Colorado';
		$state_list[6]['value'] = 'Connecticut';
		$state_list[7]['value'] = 'Delaware';
		$state_list[8]['value'] = 'District of Columbia';
		$state_list[9]['value'] = 'Florida';
		$state_list[10]['value'] = 'Georgia';
		$state_list[11]['value'] = 'Hawaii';
		$state_list[12]['value'] = 'Idaho';
		$state_list[13]['value'] = 'Illinois';
		$state_list[14]['value'] = 'Indiana';
		$state_list[15]['value'] = 'Iowa';
		$state_list[16]['value'] = 'Kansas';
		$state_list[17]['value'] = 'Kentucky';
		$state_list[18]['value'] = 'Louisiana';
		$state_list[19]['value'] = 'Maine';
		$state_list[20]['value'] = 'Maryland';
		$state_list[21]['value'] = 'Massachusetts';
		$state_list[22]['value'] = 'Michigan';
		$state_list[23]['value'] = 'Minnesota';
		$state_list[24]['value'] = 'Mississippi';
		$state_list[25]['value'] = 'Missouri';
		$state_list[26]['value'] = 'Montana';
		$state_list[27]['value'] = 'Nebraska';
		$state_list[28]['value'] = 'Nevada';
		$state_list[29]['value'] = 'New Hampshire';
		$state_list[30]['value'] = 'New Jersey';
		$state_list[31]['value'] = 'New Mexico';
		$state_list[32]['value'] = 'New York';
		$state_list[33]['value'] = 'North Carolina';
		$state_list[34]['value'] = 'North Dakota';
		$state_list[35]['value'] = 'Ohio';
		$state_list[36]['value'] = 'Oklahoma';
		$state_list[37]['value'] = 'Oregon';
		$state_list[38]['value'] = 'Pennsylvania';
		$state_list[39]['value'] = 'Rhode Island';
		$state_list[40]['value'] = 'South Carolina';
		$state_list[41]['value'] = 'South Dakota';
		$state_list[42]['value'] = 'Tennessee';
		$state_list[43]['value'] = 'Texas';
		$state_list[44]['value'] = 'Utah';
		$state_list[45]['value'] = 'Vermont';
		$state_list[46]['value'] = 'Virginia';
		$state_list[47]['value'] = 'Washington';
		$state_list[48]['value'] = 'West Virginia';
		$state_list[49]['value'] = 'Wisconsin';
		$state_list[50]['value'] = 'Wyoming';
		
		global $la_lang;
		
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		
		$el_class = array();
		
		$el_class[] = 'address';
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
        	if($casecade){
				$span_required = "<span id=\"required_{$element->parent_form_id}_{$element->form_id}_{$element->id}\" class=\"required\">*</span>";
            }else{
            	$span_required = "<span id=\"required_{$element->id}\" class=\"required\">*</span>";
            }
		}
		
		//check for guidelines
		if(!empty($element->guidelines)){
        	if($casecade){
                $guidelines = "<p class=\"guidelines\" id=\"guide_{$element->parent_form_id}_{$element->form_id}_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }else{
                $guidelines = "<p class=\"guidelines\" id=\"guide_{$element->id}\"><small>{$element->guidelines}</small></p>";
            }
		}
		
		if(!empty($element->default_value)){
			$default_value_6 = $element->default_value;
		}

		//check for GET parameter to populate default value
		if(isset($_GET['element_'.$element->id.'_1'])){
			$default_value_1 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_1']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_2'])){
			$default_value_2 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_2']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_3'])){
			$default_value_3 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_3']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_4'])){
			$default_value_4 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_4']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_5'])){
			$default_value_5 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_5']),ENT_QUOTES);
		}
		if(isset($_GET['element_'.$element->id.'_6'])){
			$default_value_6 = htmlspecialchars(la_sanitize($_GET['element_'.$element->id.'_6']),ENT_QUOTES);
		}
		
		//check for populated values, if exist override the default value
		if(!empty($element->populated_value['element_'.$element->id.'_1']['default_value']) || 
		   !empty($element->populated_value['element_'.$element->id.'_2']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_3']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_4']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_5']['default_value']) ||
		   !empty($element->populated_value['element_'.$element->id.'_6']['default_value'])
		){
			$default_value_1 = '';
			$default_value_2 = '';
			$default_value_3 = '';
			$default_value_4 = '';
			$default_value_5 = '';
			$default_value_1 = $element->populated_value['element_'.$element->id.'_1']['default_value'];
			$default_value_2 = $element->populated_value['element_'.$element->id.'_2']['default_value'];
			$default_value_3 = $element->populated_value['element_'.$element->id.'_3']['default_value'];
			$default_value_4 = $element->populated_value['element_'.$element->id.'_4']['default_value'];
			$default_value_5 = $element->populated_value['element_'.$element->id.'_5']['default_value'];
			$default_value_6 = $element->populated_value['element_'.$element->id.'_6']['default_value'];
		}
		
		//create country markup, if no default value, provide a blank option
		if(!empty($element->address_us_only)){
			$default_value_6 = 'United States';
		}
		
		if(empty($default_value_6)){
			$country_markup = '<option value="" selected="selected"></option>'."\n";
		}else{
			$country_markup = '';
		}
		
		foreach ($country as $data){
			if(!empty($data['value']) && $data['value'] == $default_value_6){
				$selected = 'selected="selected"';
			}else{
				$selected = '';
			}
			
			$country_markup .= "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>\n";
		}
		
		//if this address field is restricted to US only
		if(empty($element->is_design_mode) && !empty($element->address_us_only)){
			$country_markup = '<option selected="selected" value="United States">United States</option>';
		}
		
		if( !empty($element->element_machine_code) ) {
			$element_machine_code_html = "element_machine_code=\"{$element->element_machine_code}\"";
			$element_machine_data_html = "data-element_machine_code=\"{$element->element_machine_code}\"";
			$field_type = "data-field_type=\"address\"";
		}

		//decide which state markup being used
		if(empty($element->address_us_only)){
			//display simple input for the state
            if($casecade){
                $state_markup = "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"addressstate\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_4]\" class=\"element text large socket-enabled\"  value=\"{$default_value_4}\" type=\"text\" />";
            }else{
                $state_markup = "<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"addressstate\" id=\"element_{$element->id}_4\" name=\"element_{$element->id}_4\" class=\"element text large socket-enabled\"  value=\"{$default_value_4}\" type=\"text\" />";
            }
		}else{
			//display us state dropdown
            if($casecade){
                $state_markup = "<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"addressstate\" class=\"element select large socket-enabled\" id=\"element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4\" name=\"{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_4]\">";
            }else{
                $state_markup = "<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type=\"addressstate\" class=\"element select large socket-enabled\" id=\"element_{$element->id}_4\" name=\"element_{$element->id}_4\">";
            }
			$state_markup .= '<option value="" selected="selected">Select a State</option>'."\n";
			
			foreach ($state_list as $data){
				if($data['value'] == $default_value_4){
					$selected = 'selected="selected"';
				}else{
					$selected = '';
				}
				
				$state_markup .= "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>\n";
			}
			
			$state_markup .= "</select>";
			
		}
		
		//set the 'address line 2' visibility, based on selected option
		if(!empty($element->address_hideline2)){
			$address_line2_style = 'style="display: none"';
		}else{
			$address_line2_style = '';
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
        
        $default_value_1 = noHTML($default_value_1);	
        $default_value_2 = noHTML($default_value_2);	
        $default_value_3 = noHTML($default_value_3);	
        $default_value_4 = noHTML($default_value_4);	
        $default_value_5 = noHTML($default_value_5);	
        $default_value_6 = noHTML($default_value_6);

		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => $element->id, 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);
        
        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		
		<div>
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_1">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addressstreet" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_1]" class="element text large socket-enabled" value="{$default_value_1}" type="text" />
				<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_1">{$la_lang['address_street']}</label>
			</span>
		
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_2" {$address_line2_style}>
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addressstreet2" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_2]" class="element text large socket-enabled" value="{$default_value_2}" type="text" />
				<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_2">{$la_lang['address_street2']}</label>
			</span>
		
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_3" class="left state_list">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresscity" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_3]" class="element text large socket-enabled" value="{$default_value_3}" type="text" />
				<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_3">{$la_lang['address_city']}</label>
			</span>
		
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_4" class="right state_list">
				{$state_markup}
				<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_4">{$la_lang['address_state']}</label>
			</span>
		
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_5" class="left">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresszip" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_5" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_5]" class="element text large socket-enabled" maxlength="15" value="{$default_value_5}" type="text" />
				<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_5">{$la_lang['address_zip']}</label>
			</span>
			
			<span id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}_span_6" class="right">
				<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresscountry" class="element select large socket-enabled" id="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_6" name="{$element->parent_form_id}_{$element->form_id}[element_{$element->id}_6]"> 
				{$country_markup}	
				</select>
			<label for="element_{$element->parent_form_id}_{$element->form_id}_{$element->id}_6">{$la_lang['address_country']}</label>
		    </span>
	    </div>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
        }else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
		{$keystone_viewer}
		<label class="description" style="{$label_styles}">{$element->title} {$span_required}</label>
		
		<div>
			<span id="li_{$element->id}_span_1">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addressstreet"  id="element_{$element->id}_1" name="element_{$element->id}_1" class="element text large socket-enabled" value="{$default_value_1}" type="text" />
				<label for="element_{$element->id}_1">{$la_lang['address_street']}</label>
			</span>
		
			<span id="li_{$element->id}_span_2" {$address_line2_style}>
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addressstreet2" id="element_{$element->id}_2" name="element_{$element->id}_2" class="element text large socket-enabled" value="{$default_value_2}" type="text" />
				<label for="element_{$element->id}_2">{$la_lang['address_street2']}</label>
			</span>
		
			<span id="li_{$element->id}_span_3" class="left state_list">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresscity" id="element_{$element->id}_3" name="element_{$element->id}_3" class="element text large socket-enabled" value="{$default_value_3}" type="text" />
				<label for="element_{$element->id}_3">{$la_lang['address_city']}</label>
			</span>
		
			<span id="li_{$element->id}_span_4" class="right state_list">
				{$state_markup}
				<label for="element_{$element->id}_4">{$la_lang['address_state']}</label>
			</span>
		
			<span id="li_{$element->id}_span_5" class="left">
				<input {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresszip" id="element_{$element->id}_5" name="element_{$element->id}_5" class="element text large socket-enabled" maxlength="15" value="{$default_value_5}" type="text" />
				<label for="element_{$element->id}_5">{$la_lang['address_zip']}</label>
			</span>
			
			<span id="li_{$element->id}_span_6" class="right">
				<select {$element_machine_code_html} {$element_machine_data_html} {$field_type} data-field_sub_type="addresscountry" class="element select large socket-enabled" id="element_{$element->id}_6" name="element_{$element->id}_6"> 
				{$country_markup}	
				</select>
			<label for="element_{$element->id}_6">{$la_lang['address_country']}</label>
		    </span>
	    </div>{$guidelines} {$error_message}<div style="clear: both;">{$noteHtml}</div>
		</li>
EOT;
		}	
	
		return $element_markup;
	}	
	
	//Matrix Table
	function la_display_matrix($element, $casecade=false){
		//echo '<pre>';print_r($element);echo '</pre>';die;
		//check for error
		$li_class = '';
		$error_message = '';
		$span_required = '';
		$el_class = array();
        
        $casecade_form_id = ($casecade) ? "{$element->parent_form_id}"."_"."{$element->form_id}" : "";
		
		$el_class[] = "matrix";
		
		if(!empty($element->is_private)){
			$el_class[] = 'private';
		}

		if(!empty($element->element_id_auto)){
			$el_class[] = 'element_id_auto_'.$element->element_id_auto;
		}
		
		if(!empty($element->is_error)){
			$el_class[] = 'error';
			if($element->error_message != 'error_no_display'){
				$error_message = "<p class=\"error\">{$element->error_message}</p>";
			}
		}
		
		//check for required
		if($element->is_required){
			$span_required = "<span id=\"required_{$casecade_form_id}_{$element->id}\" class=\"required\">*</span>";
		}
		
		//check matrix field type
		if($element->matrix_allow_multiselect){
			$input_type = 'checkbox';
		}else{
			$input_type = 'radio';
		}
		
		//calculate the table columns width
		$total_answer = count($element->options) + 1;
		$initial_width = 100 / $total_answer;
		$first_col_width = 2 * $initial_width;
		$first_col_width = round($first_col_width);
		$other_col_width = (100 - $first_col_width) / ($total_answer - 1);
		$other_col_width = round($other_col_width);

		//build th markup and first row markup		
		$th_markup = '';
		$first_row_td = '';
		
		foreach($element->options as $option){
			
			if($input_type == 'checkbox'){
				$option_id_var = '_'.$option->id;

				if(!empty($element->populated_value['element_'.$element->id.'_'.$option->id]['default_value']) && ($element->populated_value['element_'.$element->id.'_'.$option->id]['default_value'] == $option->id)){
					$checked_markup = 'checked="checked"';
				}else{
					$checked_markup = '';
				}
			}else{
				$option_id_var = '';
				
				if(!empty($element->populated_value['element_'.$element->id]['default_value']) && ($element->populated_value['element_'.$element->id]['default_value'] == $option->id)){
					$checked_markup = 'checked="checked"';
				}else{
					$checked_markup = '';
				}
			}			
            
            if($casecade){
				$th_markup 	  .= "<th id=\"mc_{$casecade_form_id}_{$element->id}_{$option->id}\" style=\"width: {$other_col_width}%\" scope=\"col\">{$option->option}</th>\n";
                $first_row_td .= "<td><input element_machine_code=\"{$element->element_machine_code}\"  id=\"element_{$casecade_form_id}_{$element->id}_{$option->id}\" name=\"{$casecade_form_id}[element_{$element->id}{$option_id_var}]\" type=\"{$input_type}\" value=\"{$option->id}\" {$checked_markup} /></td>\n";
            }else{
				$th_markup 	  .= "<th id=\"mc_{$element->id}_{$option->id}\" style=\"width: {$other_col_width}%\" scope=\"col\">{$option->option}</th>\n";
                $first_row_td .= "<td><input  element_machine_code=\"{$element->element_machine_code}\" id=\"element_{$element->id}_{$option->id}\" name=\"element_{$element->id}{$option_id_var}\" type=\"{$input_type}\" value=\"{$option->id}\" {$checked_markup} /></td>\n";
            }
		}
		
		//build other rows markup
		$tr_markup = '';
		$show_alt = false;
		if(!empty($element->matrix_children)){
			foreach ($element->matrix_children as $matrix_item){
			
				$children_option_id = array();
				$children_option_id = explode(',',$matrix_item['children_option_id']);
				
				$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $matrix_item['id'], 'element_status_indicator' => $matrix_item['element_status_indicator'], 'element_indicator' => $matrix_item['element_indicator'], 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
				
				$td_markup = "<td class=\"first_col\">{$matrix_item['title']} {$statusIndicatorHtml}</td>";
				foreach ($children_option_id as $option_id){
					
					
					if($input_type == 'checkbox'){
						$option_id_var = '_'.$option_id;

						if(!empty($element->populated_value['element_'.$matrix_item['id'].'_'.$option_id]['default_value']) && ($element->populated_value['element_'.$matrix_item['id'].'_'.$option_id]['default_value'] == $option_id)){
							$checked_markup = 'checked="checked"';
						}else{
							$checked_markup = '';
						}
					}else{
						$option_id_var = '';
						
						if(!empty($element->populated_value['element_'.$matrix_item['id']]['default_value']) && ($element->populated_value['element_'.$matrix_item['id']]['default_value'] == $option_id)){
							$checked_markup = 'checked="checked"';
						}else{
							$checked_markup = '';
						}
					}
				
                    if($casecade){
                        $td_markup .= "<td><input  element_machine_code=\"{$element->element_machine_code}\" id=\"element_{$casecade_form_id}_{$matrix_item['id']}_{$option_id}\" name=\"{$casecade_form_id}[element_{$matrix_item['id']}{$option_id_var}]\" type=\"{$input_type}\" value=\"{$option_id}\" {$checked_markup} /></td>\n";
                    }else{
                        $td_markup .= "<td><input  element_machine_code=\"{$element->element_machine_code}\" id=\"element_{$matrix_item['id']}_{$option_id}\" name=\"element_{$matrix_item['id']}{$option_id_var}\" type=\"{$input_type}\" value=\"{$option_id}\" {$checked_markup} /></td>\n";
                    }
				}
				
				if($show_alt){
					$row_style = ' class="alt" ';
					$show_alt = false;
				}else{
					$row_style = '';
					$show_alt = true;
				}
				
                if($casecade){
                    $tr_markup .= "<tr {$row_style} id=\"mr_{$casecade_form_id}_{$matrix_item['id']}\">".$td_markup."</tr>";
                }else{
                	$tr_markup .= "<tr {$row_style} id=\"mr_{$matrix_item['id']}\">".$td_markup."</tr>";
                }
			}
		}
		
		//build the li class
		if(!empty($el_class)){
			foreach ($el_class as $value){
				$li_class .= $value.' ';
			}
			
			$li_class = 'class="'.rtrim($li_class).'"';
		}
		
		// Generate Note Html
		$noteHtml = generateNoteIcon(array('form_id' => $element->form_id, 'element_id' => (!$element->id ? $element->matrix_parent_id : $element->id), 'element_note' => $element->element_note, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		
		$statusIndicatorHtml = generateStatusIndicator(array('form_id' => $element->form_id, 'company_id' => $element->company_id, 'entry_id' => $element->entry_id, 'element_id' => $element->id, 'element_status_indicator' => $element->element_status_indicator, 'element_indicator' => $element->element_indicator, 'is_design_mode' => (isset($element->is_design_mode) ? $element->is_design_mode : 0)));
		$label_styles = label_styles($element);

        if($casecade){
$element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
			<div style="width:100%; float:left;">
				<label class="description" style="{$label_styles}">{$element->guidelines} {$span_required}</label>
			</div>
			<table>				
			    <thead>
			    	<tr>
			        	<th style="width: {$first_col_width}%" scope="col">&nbsp;</th>
			            {$th_markup}
			        </tr>
			    </thead>
			    <tbody>
			    	<tr class="alt" id="mr_{$casecade_form_id}_{$element->id}">
			        	<td class="first_col">{$element->title} {$statusIndicatorHtml}</td>
			            {$first_row_td}
			        </tr>
			        {$tr_markup}
			    </tbody>
			</table>
			{$noteHtml}
		{$error_message}
		</li>
EOT;
        }else{
$element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
			<div style="width:100%; float:left;">
				<label class="description" style="{$label_styles}">{$element->guidelines} {$span_required}</label>
			</div>
			<table>
			    <thead>
			    	<tr>
			        	<th style="width: {$first_col_width}%" scope="col">&nbsp;</th>
			            {$th_markup}
			        </tr>
			    </thead>
			    <tbody>
			    	<tr class="alt" id="mr_{$element->id}">
			        	<td class="first_col">{$element->title} {$statusIndicatorHtml}</td>
			            {$first_row_td}
			        </tr>
			        {$tr_markup}
			    </tbody>
			</table>
			{$noteHtml}
		{$error_message}
		</li>
EOT;
}
		
		return $element_markup;
	}	

	//Captcha
	function la_display_captcha($element, $casecade=false){
		if(!empty($element->error_message)){
			$error_code = $element->error_message;
		}else{
			$error_code = '';
		}
					
		//check for error
		$error_class = '';
		$error_message = '';
		$span_required = '';
		$guidelines = '';
		global $la_lang;	
		
		if(!empty($element->is_error)){
			if($element->error_message == 'el-required' || $element->error_message == 'el-required'){
				$element->error_message = $la_lang['captcha_required'];
				$error_code = '';	
			}else if($element->error_message == 'el-text-required'){
				$element->error_message = $la_lang['val_required'];
				$error_code = '';	
			}elseif ($element->error_message == 'incorrect-text-captcha-sol'){
				$element->error_message = $la_lang['captcha_text_mismatch'];
			}else{
				$element->error_message = "{$la_lang['captcha_error']} ({$element->error_message})";
			}
			
			$error_class = 'class="error"';
			$error_message = "<p class=\"error\">{$element->error_message}</p>";
		}

		
		if(!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')){
			$use_ssl = true;
		}else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
			$use_ssl = true;
		}else if (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] == 'on'){
			$use_ssl = true;
		}else{
			$use_ssl = false;
		}
		
		
		if($element->captcha_type == 'i'){ //if this is internal captcha type
		
			$itauditmachine_path = '';
			if(!empty($element->itauditmachine_path)){
				$itauditmachine_path = $element->itauditmachine_path;
			}
			
			$timestamp = time(); //use this as paramater for captcha.php, to prevent caching
			
			$element->title = $la_lang['captcha_simple_image_title'];
$captcha_html = <<<EOT
<img id="captcha_image" src="{$itauditmachine_path}captcha.php?t={$timestamp}" width="200" height="60" alt="Please refresh your browser to see this image." /><br />
<input id="captcha_response_field" name="captcha_response_field" class="element text small" type="text" /><div id="dummy_captcha_internal"></div>
EOT;
	 		
		}else if($element->captcha_type == 'r'){ //if this is recaptcha
			$pubkey = getRecaptchaPublicKey();
			$captcha_html = recaptcha_get_html($pubkey, $error_code,$use_ssl);
	
			if($captcha_html === false){
				$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
				$captcha_html = "<b>Error!</b> You have enabled CAPTCHA but no API key available. <br /><br />To use CAPTCHA you must get an API key from <a href='".recaptcha_get_signup_url($domain,'IT Audit Machine')."'>https://www.google.com/recaptcha/admin</a><br /><br />After getting the API key, save them into save them in the settings page.";
				$error_class = 'class="error"';
			}else{
				$recaptcha_post_message = <<<EOT
<script type="text/javascript">
    $(function(){
    	$.postMessage({la_iframe_height: $('body').outerHeight(true) + 130}, '*', parent );
    });
</script>
EOT;
				//manually add 130px padding for recaptcha, since google is building the captcha after the dom is loaded
				$captcha_html .= "\n".$recaptcha_post_message; 
			}

			$recaptcha_theme = RECAPTCHA_THEME;
			$recaptcha_language = RECAPTCHA_LANGUAGE;
			$recaptcha_theme_init = <<<EOT
				<script type="text/javascript">
				 var RecaptchaOptions = {
				    theme : '{$recaptcha_theme}',

				    lang: '{$recaptcha_language}'
				 };
				</script>
EOT;

		}else if($element->captcha_type == 't'){ //if this is simple text captcha
			
			$element->title = $la_lang['captcha_simple_text_title'];

			$text_captcha = la_get_text_captcha();
			
			$_SESSION['LA_TEXT_CAPTCHA_ANSWER'] = $text_captcha['answer'];
			$text_captcha_question = htmlspecialchars($text_captcha['question'],ENT_QUOTES);

			$captcha_html = <<<EOT
<span class="text_captcha">{$text_captcha_question}</span>
<input id="captcha_response_field" name="captcha_response_field" class="element text small" type="text" />

EOT;
		}
		

		$label_styles = label_styles($element);
        
$element_markup = <<<EOT
		<li id="li_captcha" {$error_class}> {$recaptcha_theme_init}
		<label class="description" style="{$label_styles}" for="captcha_response_field">{$element->title} {$span_required}</label>
		<div>
			{$captcha_html}	
		</div>	 
		{$guidelines} {$error_message}
		</li>
EOT;
		
		return $element_markup;
	}

	//Syndication
    function la_display_syndication($element, $casecade=false, $dbh){
        $li_class = '';
        $el_class = array();

        $el_class[] = "syndication";

        $feedInfo = html_entity_decode($element->default_value);
        $feedInfo = json_decode($feedInfo, true);

        $query = "select `form_title_font_type`, `form_title_font_weight`, `form_title_font_style`, `form_title_font_size`, `form_title_font_color`, `form_desc_font_type`, `form_desc_font_weight`, `form_desc_font_style`, `form_desc_font_size`, `form_desc_font_color`, `header_bg_type`, `header_bg_color`, `header_bg_pattern`, `header_bg_custom`, `form_bg_type`, `form_bg_color`, `form_bg_pattern`, `form_bg_custom` from ap_form_themes where theme_id = {$feedInfo['theme_id']}";
        $result = la_do_query($query,array(),$dbh);
        $row = la_do_fetch_result($result);


        if(!$row){
            $row = NULL;
        }

        $element->title = str_replace(array('<strong>', '</strong>'), array('', ''), $element->title);
        $element->guidelines = nl2br(str_replace(array('<strong>', '</strong>'), array('', ''), $element->guidelines));
        $label_styles = label_styles($element);
        if($casecade){
            $element_markup = <<<EOT
		<li id="li_{$element->parent_form_id}_{$element->form_id}_{$element->id}" {$li_class}>
			<div id="div_{$element->parent_form_id}_{$element->form_id}_{$element->id}" style="background-color:{$row['form_bg_color']}">
               <div style="background-color:{$row['header_bg_color']}; font-family:{$row['form_title_font_type']}; font-weight:{$row['form_title_font_weight']}; font-style:{$row['form_title_font_style']}; font-size:{$row['form_title_font_size']}; font-color:{$row['form_title_font_color']};">{$element->title}</div>
			   <div class="smartmarquee smartmarquee-feed" style="font-family:{$row['form_desc_font_type']}; font-weight:{$row['form_desc_font_weight']}; font-style:{$row['form_desc_font_style']}; font-size:{$row['form_desc_font_size']}; font-color:{$row['form_desc_font_color']};">{$element->guidelines}</div>
            </div>
		</li>
EOT;

            $feed_url = $feedInfo['feed_url'];
            $element_markup .= "<script>renderRss({li:".$element->parent_form_id."_".$element->form_id."_".$element->id.", feed_url:'".$feed_url."'});</script>";
        }else{
            $element_markup = <<<EOT
		<li id="li_{$element->id}" {$li_class}>
			<div style="width:100%; float:left;">
			    <label class="description" style="{$label_styles}">{$element->title}</label>
			    <div class="feed-container"></div>
			    <div class="feed-loader" id="loader_{$element->id}"><img src="images/loader.gif"></div>
                <!--<div style="background-color:{$row['header_bg_color']}; font-family:{$row['form_title_font_type']}; font-weight:{$row['form_title_font_weight']}; font-style:{$row['form_title_font_style']}; font-size:{$row['form_title_font_size']}; font-color:{$row['form_title_font_color']};">{$element->title}</div>-->
		        <!--<div class="smartmarquee smartmarquee-feed" style="font-family:{$row['form_desc_font_type']}; font-weight:{$row['form_desc_font_weight']}; font-style:{$row['form_desc_font_style']}; font-size:{$row['form_desc_font_size']}; font-color:{$row['form_desc_font_color']};">{$element->guidelines}</div>-->
            </div>
		</li>
EOT;

            $element_markup .= "<script> renderRss({li:$element->id, feedInfo:'".json_encode($feedInfo)."'}); </script>";
        }

        return $element_markup;
    }

	function la_display_video_player($element, $casecade = false, $dbh = false) {
		$dbh = la_connect_db();
		$fields = 'element_id, element_title, element_video_loop, element_video_auto_play, element_video_url, element_video_source, element_media_type';
		$query = 'SELECT ' . $fields . ' FROM ' .LA_TABLE_PREFIX . 'form_elements WHERE form_id = ? AND element_id = ?';
		$result = la_do_query($query, array($element->form_id, $element->id), $dbh);
		$data = la_do_fetch_result($result);

		$element->title = str_replace(array('<strong>', '</strong>'), array('', ''), $element->title);
		$label_styles = label_styles($element);
        
		$template = '<li id="li_' . $element->id . '" class="video-player">';
		$template .= '<div style="width:100%; float:left;">';
		$template .= '<label class="description" style="'.$label_styles.'">' . $element->title . '</label>';
		$template .= '<div class="video-player-container"><img src="images/video_player_image.png"></div>';
		$template .= '</div>';
		$template .= '</li>';
		$template .= '<script>renderVideos(' . json_encode($data) . '); </script>';

		return $template;
	}
	
	//simple function to return an array of countries
	function la_get_country_list(){
		$country[0]['label'] = "United States";
		$country[1]['label'] = "United Kingdom";
		$country[2]['label'] = "Canada";
		$country[3]['label'] = "Australia";
		$country[4]['label'] = "Netherlands";
		$country[5]['label'] = "France";
		$country[6]['label'] = "Germany";
		$country[7]['label'] = "-------";
		$country[8]['label'] = "Afghanistan";
		$country[9]['label'] = "Albania";
		$country[10]['label'] = "Algeria";
		$country[11]['label'] = "Andorra";
		$country[12]['label'] = "Antigua and Barbuda";
		$country[13]['label'] = "Argentina";
		$country[14]['label'] = "Armenia";
		$country[15]['label'] = "Austria";
		$country[16]['label'] = "Azerbaijan";
		$country[17]['label'] = "Bahamas";
		$country[18]['label'] = "Bahrain";
		$country[19]['label'] = "Bangladesh";
		$country[20]['label'] = "Barbados";
		$country[21]['label'] = "Belarus";
		$country[22]['label'] = "Belgium";
		$country[23]['label'] = "Belize";
		$country[24]['label'] = "Benin";
		$country[25]['label'] = "Bhutan";
		$country[26]['label'] = "Bolivia";
		$country[27]['label'] = "Bosnia and Herzegovina";
		$country[28]['label'] = "Botswana";
		$country[29]['label'] = "Brazil";
		$country[30]['label'] = "Brunei";
		$country[31]['label'] = "Bulgaria";
		$country[32]['label'] = "Burkina Faso";
		$country[33]['label'] = "Burundi";
		$country[34]['label'] = "Cambodia";
		$country[35]['label'] = "Cameroon";	
		$country[36]['label'] = "Cape Verde";
		$country[37]['label'] = "Central African Republic";
		$country[38]['label'] = "Chad";
		$country[39]['label'] = "Chile";
		$country[40]['label'] = "China";
		$country[41]['label'] = "Colombia";
		$country[42]['label'] = "Comoros";
		$country[43]['label'] = "Congo";
		$country[44]['label'] = "Costa Rica";
		$country[45]['label'] = "Côte d'Ivoire";
		$country[46]['label'] = "Croatia";
		$country[47]['label'] = "Cuba";
		$country[48]['label'] = "Cyprus";
		$country[49]['label'] = "Czech Republic";
		$country[50]['label'] = "Denmark";
		$country[51]['label'] = "Djibouti";
		$country[52]['label'] = "Dominica";
		$country[53]['label'] = "Dominican Republic";
		$country[54]['label'] = "East Timor";
		$country[55]['label'] = "Ecuador";
		$country[56]['label'] = "Egypt";
		$country[57]['label'] = "El Salvador";
		$country[58]['label'] = "Equatorial Guinea";
		$country[59]['label'] = "Eritrea";
		$country[60]['label'] = "Estonia";
		$country[61]['label'] = "Ethiopia";
		$country[62]['label'] = "Fiji";
		$country[63]['label'] = "Finland";
		$country[64]['label'] = "Gabon";
		$country[65]['label'] = "Gambia";
		$country[66]['label'] = "Georgia";
		$country[67]['label'] = "Ghana";
		$country[68]['label'] = "Greece";
		$country[69]['label'] = "Grenada";
		$country[70]['label'] = "Guatemala";
		$country[71]['label'] = "Guernsey";
		$country[72]['label'] = "Guinea";
		$country[73]['label'] = "Guinea-Bissau";
		$country[74]['label'] = "Guyana";
		$country[75]['label'] = "Haiti";
		$country[76]['label'] = "Honduras";
		$country[77]['label'] = "Hong Kong";
		$country[78]['label'] = "Hungary";
		$country[79]['label'] = "Iceland";
		$country[80]['label'] = "India";
		$country[81]['label'] = "Indonesia";
		$country[82]['label'] = "Iran";
		$country[83]['label'] = "Iraq";
		$country[84]['label'] = "Ireland";
		$country[85]['label'] = "Israel";
		$country[86]['label'] = "Italy";
		$country[87]['label'] = "Jamaica";
		$country[88]['label'] = "Japan";
		$country[89]['label'] = "Jordan";
		$country[90]['label'] = "Kazakhstan";
		$country[91]['label'] = "Kenya";
		$country[92]['label'] = "Kiribati";
		$country[93]['label'] = "North Korea";
		$country[94]['label'] = "South Korea";
		$country[95]['label'] = "Kuwait";
		$country[96]['label'] = "Kyrgyzstan";
		$country[97]['label'] = "Laos";
		$country[98]['label'] = "Latvia";
		$country[99]['label'] = "Lebanon";
		$country[100]['label'] = "Lesotho";
		$country[101]['label'] = "Liberia";
		$country[102]['label'] = "Libya";
		$country[103]['label'] = "Liechtenstein";
		$country[104]['label'] = "Lithuania";
		$country[105]['label'] = "Luxembourg";
		$country[106]['label'] = "Macedonia";
		$country[107]['label'] = "Madagascar";
		$country[108]['label'] = "Malawi";
		$country[109]['label'] = "Malaysia";
		$country[110]['label'] = "Maldives";
		$country[111]['label'] = "Mali";
		$country[112]['label'] = "Malta";
		$country[113]['label'] = "Marshall Islands";
		$country[114]['label'] = "Mauritania";
		$country[115]['label'] = "Mauritius";
		$country[116]['label'] = "Mexico";
		$country[117]['label'] = "Micronesia";
		$country[118]['label'] = "Moldova";
		$country[119]['label'] = "Monaco";
		$country[120]['label'] = "Mongolia";
		$country[121]['label'] = "Montenegro";
		$country[122]['label'] = "Morocco";
		$country[123]['label'] = "Mozambique";
		$country[124]['label'] = "Myanmar";
		$country[125]['label'] = "Namibia";
		$country[126]['label'] = "Nauru";
		$country[127]['label'] = "Nepal";
		$country[128]['label'] = "New Zealand";
		$country[129]['label'] = "Nicaragua";
		$country[130]['label'] = "Niger";
		$country[131]['label'] = "Nigeria";
		$country[132]['label'] = "Norway";
		$country[133]['label'] = "Oman";
		$country[134]['label'] = "Pakistan";
        $country[135]['label'] = "Palestine";
		$country[136]['label'] = "Palau";
		$country[137]['label'] = "Panama";
		$country[138]['label'] = "Papua New Guinea";
		$country[139]['label'] = "Paraguay";
		$country[140]['label'] = "Peru";
		$country[141]['label'] = "Philippines";
		$country[142]['label'] = "Poland";
		$country[143]['label'] = "Portugal";
		$country[144]['label'] = "Puerto Rico";
		$country[145]['label'] = "Qatar";
		$country[146]['label'] = "Romania";
		$country[147]['label'] = "Russia";
		$country[148]['label'] = "Rwanda";
		$country[149]['label'] = "Saint Kitts and Nevis";
		$country[150]['label'] = "Saint Lucia";
		$country[151]['label'] = "Saint Vincent and the Grenadines";
		$country[152]['label'] = "Samoa";
		$country[153]['label'] = "San Marino";
		$country[154]['label'] = "Sao Tome and Principe";
		$country[155]['label'] = "Saudi Arabia";
		$country[156]['label'] = "Senegal";
		$country[157]['label'] = "Serbia and Montenegro";
		$country[158]['label'] = "Seychelles";
		$country[159]['label'] = "Sierra Leone";
		$country[160]['label'] = "Singapore";
		$country[161]['label'] = "Slovakia";
		$country[162]['label'] = "Slovenia";
		$country[163]['label'] = "Solomon Islands";
		$country[164]['label'] = "Somalia";
		$country[165]['label'] = "South Africa";
		$country[166]['label'] = "Spain";
		$country[167]['label'] = "Sri Lanka";
		$country[168]['label'] = "Sudan";
		$country[169]['label'] = "Suriname";
		$country[170]['label'] = "Swaziland";
		$country[171]['label'] = "Sweden";
		$country[172]['label'] = "Switzerland";
		$country[173]['label'] = "Syria";
		$country[174]['label'] = "Taiwan";
		$country[175]['label'] = "Tajikistan";
		$country[176]['label'] = "Tanzania";
		$country[177]['label'] = "Thailand";
		$country[178]['label'] = "Togo";
		$country[179]['label'] = "Tonga";
		$country[180]['label'] = "Trinidad and Tobago";
		$country[181]['label'] = "Tunisia";
		$country[182]['label'] = "Turkey";
		$country[183]['label'] = "Turkmenistan";
		$country[184]['label'] = "Tuvalu";
		$country[185]['label'] = "Uganda";
		$country[186]['label'] = "Ukraine";
		$country[187]['label'] = "United Arab Emirates";
		$country[188]['label'] = "Uruguay";
		$country[189]['label'] = "Uzbekistan";
		$country[190]['label'] = "Vanuatu";
		$country[191]['label'] = "Vatican City";
		$country[192]['label'] = "Venezuela";
		$country[193]['label'] = "Vietnam";
		$country[194]['label'] = "Yemen";
		$country[195]['label'] = "Zambia";
		$country[196]['label'] = "Zimbabwe";

		$country[0]['value'] = "United States";
		$country[1]['value'] = "United Kingdom";
		$country[2]['value'] = "Canada";
		$country[3]['value'] = "Australia";
		$country[4]['value'] = "Netherlands";
		$country[5]['value'] = "France";
		$country[6]['value'] = "Germany";
		$country[7]['value'] = "";
		$country[8]['value'] = "Afghanistan";
		$country[9]['value'] = "Albania";
		$country[10]['value'] = "Algeria";
		$country[11]['value'] = "Andorra";
		$country[12]['value'] = "Antigua and Barbuda";
		$country[13]['value'] = "Argentina";
		$country[14]['value'] = "Armenia";
		$country[15]['value'] = "Austria";
		$country[16]['value'] = "Azerbaijan";
		$country[17]['value'] = "Bahamas";
		$country[18]['value'] = "Bahrain";
		$country[19]['value'] = "Bangladesh";
		$country[20]['value'] = "Barbados";
		$country[21]['value'] = "Belarus";
		$country[22]['value'] = "Belgium";
		$country[23]['value'] = "Belize";
		$country[24]['value'] = "Benin";
		$country[25]['value'] = "Bhutan";
		$country[26]['value'] = "Bolivia";
		$country[27]['value'] = "Bosnia and Herzegovina";
		$country[28]['value'] = "Botswana";
		$country[29]['value'] = "Brazil";
		$country[30]['value'] = "Brunei";
		$country[31]['value'] = "Bulgaria";
		$country[32]['value'] = "Burkina Faso";
		$country[33]['value'] = "Burundi";
		$country[34]['value'] = "Cambodia";
		$country[35]['value'] = "Cameroon";	
		$country[36]['value'] = "Cape Verde";
		$country[37]['value'] = "Central African Republic";
		$country[38]['value'] = "Chad";
		$country[39]['value'] = "Chile";
		$country[40]['value'] = "China";
		$country[41]['value'] = "Colombia";
		$country[42]['value'] = "Comoros";
		$country[43]['value'] = "Congo";
		$country[44]['value'] = "Costa Rica";
		$country[45]['value'] = "Côte d'Ivoire";
		$country[46]['value'] = "Croatia";
		$country[47]['value'] = "Cuba";
		$country[48]['value'] = "Cyprus";
		$country[49]['value'] = "Czech Republic";
		$country[50]['value'] = "Denmark";
		$country[51]['value'] = "Djibouti";
		$country[52]['value'] = "Dominica";
		$country[53]['value'] = "Dominican Republic";
		$country[54]['value'] = "East Timor";
		$country[55]['value'] = "Ecuador";
		$country[56]['value'] = "Egypt";
		$country[57]['value'] = "El Salvador";
		$country[58]['value'] = "Equatorial Guinea";
		$country[59]['value'] = "Eritrea";
		$country[60]['value'] = "Estonia";
		$country[61]['value'] = "Ethiopia";
		$country[62]['value'] = "Fiji";
		$country[63]['value'] = "Finland";
		$country[64]['value'] = "Gabon";
		$country[65]['value'] = "Gambia";
		$country[66]['value'] = "Georgia";
		$country[67]['value'] = "Ghana";
		$country[68]['value'] = "Greece";
		$country[69]['value'] = "Grenada";
		$country[70]['value'] = "Guatemala";
		$country[71]['value'] = "Guernsey";
		$country[72]['value'] = "Guinea";
		$country[73]['value'] = "Guinea-Bissau";
		$country[74]['value'] = "Guyana";
		$country[75]['value'] = "Haiti";
		$country[76]['value'] = "Honduras";
		$country[77]['value'] = "Hong Kong";
		$country[78]['value'] = "Hungary";
		$country[79]['value'] = "Iceland";
		$country[80]['value'] = "India";
		$country[81]['value'] = "Indonesia";
		$country[82]['value'] = "Iran";
		$country[83]['value'] = "Iraq";
		$country[84]['value'] = "Ireland";
		$country[85]['value'] = "Israel";
		$country[86]['value'] = "Italy";
		$country[87]['value'] = "Jamaica";
		$country[88]['value'] = "Japan";
		$country[89]['value'] = "Jordan";
		$country[90]['value'] = "Kazakhstan";
		$country[91]['value'] = "Kenya";
		$country[92]['value'] = "Kiribati";
		$country[93]['value'] = "North Korea";
		$country[94]['value'] = "South Korea";
		$country[95]['value'] = "Kuwait";
		$country[96]['value'] = "Kyrgyzstan";
		$country[97]['value'] = "Laos";
		$country[98]['value'] = "Latvia";
		$country[99]['value'] = "Lebanon";
		$country[100]['value'] = "Lesotho";
		$country[101]['value'] = "Liberia";
		$country[102]['value'] = "Libya";
		$country[103]['value'] = "Liechtenstein";
		$country[104]['value'] = "Lithuania";
		$country[105]['value'] = "Luxembourg";
		$country[106]['value'] = "Macedonia";
		$country[107]['value'] = "Madagascar";
		$country[108]['value'] = "Malawi";
		$country[109]['value'] = "Malaysia";
		$country[110]['value'] = "Maldives";
		$country[111]['value'] = "Mali";
		$country[112]['value'] = "Malta";
		$country[113]['value'] = "Marshall Islands";
		$country[114]['value'] = "Mauritania";
		$country[115]['value'] = "Mauritius";
		$country[116]['value'] = "Mexico";
		$country[117]['value'] = "Micronesia";
		$country[118]['value'] = "Moldova";
		$country[119]['value'] = "Monaco";
		$country[120]['value'] = "Mongolia";
		$country[121]['value'] = "Montenegro";
		$country[122]['value'] = "Morocco";
		$country[123]['value'] = "Mozambique";
		$country[124]['value'] = "Myanmar";
		$country[125]['value'] = "Namibia";
		$country[126]['value'] = "Nauru";
		$country[127]['value'] = "Nepal";
		$country[128]['value'] = "New Zealand";
		$country[129]['value'] = "Nicaragua";
		$country[130]['value'] = "Niger";
		$country[131]['value'] = "Nigeria";
		$country[132]['value'] = "Norway";
		$country[133]['value'] = "Oman";
		$country[134]['value'] = "Pakistan";
        $country[135]['value'] = "Palestine";
		$country[136]['value'] = "Palau";
		$country[137]['value'] = "Panama";
		$country[138]['value'] = "Papua New Guinea";
		$country[139]['value'] = "Paraguay";
		$country[140]['value'] = "Peru";
		$country[141]['value'] = "Philippines";
		$country[142]['value'] = "Poland";
		$country[143]['value'] = "Portugal";
		$country[144]['value'] = "Puerto Rico";
		$country[145]['value'] = "Qatar";
		$country[146]['value'] = "Romania";
		$country[147]['value'] = "Russia";
		$country[148]['value'] = "Rwanda";
		$country[149]['value'] = "Saint Kitts and Nevis";
		$country[150]['value'] = "Saint Lucia";
		$country[151]['value'] = "Saint Vincent and the Grenadines";
		$country[152]['value'] = "Samoa";
		$country[153]['value'] = "San Marino";
		$country[154]['value'] = "Sao Tome and Principe";
		$country[155]['value'] = "Saudi Arabia";
		$country[156]['value'] = "Senegal";
		$country[157]['value'] = "Serbia and Montenegro";
		$country[158]['value'] = "Seychelles";
		$country[159]['value'] = "Sierra Leone";
		$country[160]['value'] = "Singapore";
		$country[161]['value'] = "Slovakia";
		$country[162]['value'] = "Slovenia";
		$country[163]['value'] = "Solomon Islands";
		$country[164]['value'] = "Somalia";
		$country[165]['value'] = "South Africa";
		$country[166]['value'] = "Spain";
		$country[167]['value'] = "Sri Lanka";
		$country[168]['value'] = "Sudan";
		$country[169]['value'] = "Suriname";
		$country[170]['value'] = "Swaziland";
		$country[171]['value'] = "Sweden";
		$country[172]['value'] = "Switzerland";
		$country[173]['value'] = "Syria";
		$country[174]['value'] = "Taiwan";
		$country[175]['value'] = "Tajikistan";
		$country[176]['value'] = "Tanzania";
		$country[177]['value'] = "Thailand";
		$country[178]['value'] = "Togo";
		$country[179]['value'] = "Tonga";
		$country[180]['value'] = "Trinidad and Tobago";
		$country[181]['value'] = "Tunisia";
		$country[182]['value'] = "Turkey";
		$country[183]['value'] = "Turkmenistan";
		$country[184]['value'] = "Tuvalu";
		$country[185]['value'] = "Uganda";
		$country[186]['value'] = "Ukraine";
		$country[187]['value'] = "United Arab Emirates";
		$country[188]['value'] = "Uruguay";
		$country[189]['value'] = "Uzbekistan";
		$country[190]['value'] = "Vanuatu";
		$country[191]['value'] = "Vatican City";
		$country[192]['value'] = "Venezuela";
		$country[193]['value'] = "Vietnam";
		$country[194]['value'] = "Yemen";
		$country[195]['value'] = "Zambia";
		$country[196]['value'] = "Zimbabwe";

		return $country;
	}	
	
	function is_under_audit($dbh, $form_id, $company_id, $entry_id) {
		$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ? AND `field_name` = ?";
		$sth = la_do_query($query, array($company_id, $entry_id, 'audit'), $dbh);
		while($row = la_do_fetch_result($sth)){				
			return $row['data_value'] == "1" ? true : false;
		}
		return false;
	}
	//Main function to display a form
	//There are few mode when displaying a form
	//1. New blank form (form populated with default values)
	//2. New form with error (displayed when 1 submitted and having error, form populated with user inputs)
	//3. Edit form (form populated with data from db)
	//4. Edit form with error (displayed when #3 submitted and having error)
	function la_display_form($dbh, $form_id, $company_id, $entry_id, $form_params=array(), $showSubmit=true){
		global $la_lang;

		$form_id = (int) $form_id;
		$casecade_form_id_arr = array();
		$user_data = getUserDetailsFromId($dbh, $_SESSION['la_client_user_id']);
		$user_email = $user_data['email'];
		$is_under_audit = is_under_audit($dbh, $form_id, $company_id, $entry_id);		
		//parameters mapping
		if(isset($form_params['page_number'])){
			$page_number = (int) $form_params['page_number'];
		}else{
			$page_number = 1;
		}
		
		// csrf token
		$post_csrf_token = noHTML($_SESSION['csrf_token']);
		
		// casecade pagination code
		$casecade_form_page_number = 0;
		$casecade_element_position = 0;
		$parent_nxt_element = '<input type="hidden" name="parent_nxt_element" value="0" />';
		$form_casecade = array();
		$total_pages = 0;
		
		$isCasecadeForm = chkCascadeInViewForm(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
		));
		
		if(isset($_GET['casecade_form_page_number']) && $isCasecadeForm){
			$casecade_form_page_number = la_sanitize($_GET['casecade_form_page_number']);
			$casecade_element_position = la_sanitize($_GET['casecade_element_position']);
			$form_casecade = getPageNumber(array('dbh' => $dbh, 'form_id' => $form_id, 'casecade_element_position' => $casecade_element_position));
			$total_pages = getNoOfPages($dbh, $form_casecade['form_id']);
			
			if(($total_pages+1) == $casecade_form_page_number)
				$parent_nxt_element = '<input type="hidden" name="parent_nxt_element" value="1" />';
		}

		if(isset($form_params['error_elements'])){
			$error_elements = $form_params['error_elements'];
		}
		else{
			$error_elements = array();
		}
		
		if(isset($form_params['custom_error'])){
			$custom_error = $form_params['custom_error'];
		}
		else{
			$custom_error = '';
		}
		
		if(isset($form_params['edit_id'])){
			$edit_id = (int) $form_params['edit_id'];
		}
		else{
			$edit_id = 0;
		}
		
		if(isset($form_params['integration_method'])){ 
			$integration_method = $form_params['integration_method'];
		}
		else{
			$integration_method = '';
		}
        
        $button_escape = '';
        $pages = getNoOfPages($dbh,$form_id);
      
        if(checkPageLogicExistense($dbh,$form_id)){
            $pages = 0;
        }
            
		$la_settings = la_get_settings($dbh);

		//if there is custom error, don't show other errors
		if(!empty($custom_error)){
			$error_elements = array();
		}
		
		//get form properties data
		$query 	= "SELECT * FROM `".LA_TABLE_PREFIX."forms`  WHERE form_id = ?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		//check for non-existent or currently drafted forms or inactive forms
		if($row === false){
			die('Invalid form ID.');
		}
		else{
			$form_active = (int) $row['form_active'];
		
			if($form_active !== 0 && $form_active !== 1){
				die('The form has been inactivated.');
			}
		}

		$form = new stdClass();
		
		$form->id 				= $form_id;
		$form->name 			= $row['form_name'];
		$form->description 		= $row['form_description'];
		$form->redirect 		= $row['form_redirect'];
		$form->success_message  = $row['form_success_message'];
		$form->password 		= $row['form_password'];
		$form->frame_height 	= $row['form_frame_height'];
		$form->unique_ip 		= $row['form_unique_ip'];
		$form->has_css 			= $row['form_has_css'];
		$form->active 			= $row['form_active'];
		$form->disabled_message = $row['form_disabled_message'];
		$form->captcha 			= $row['form_captcha'];
		$form->captcha_type 	= $row['form_captcha_type'];
		$form->review 			= $row['form_review'];
		$form->label_alignment  = $row['form_label_alignment'];
		$form->page_total 		= $row['form_page_total'];
		
		if($page_number === 0){ //this is edit_entry page
			$form->page_total = 1;
		}

		$form->lastpage_title 	= $row['form_lastpage_title'];
		$form->submit_primary_text 	 = $row['form_submit_primary_text'];
		$form->submit_secondary_text = $row['form_submit_secondary_text'];
		$form->submit_primary_img 	 = $row['form_submit_primary_img'];
		$form->submit_secondary_img  = $row['form_submit_secondary_img'];
		$form->last_page_break_bg_color  = $row['form_last_page_break_bg_color'];
		$form->submit_use_image  	 = (int) $row['form_submit_use_image'];
		$form->pagination_type		 = $row['form_pagination_type'];
		$form->review_primary_text 	 = $row['form_review_primary_text'];
		$form->review_secondary_text = $row['form_review_secondary_text'];
		$form->review_primary_img 	 = $row['form_review_primary_img'];
		$form->review_secondary_img  = $row['form_review_secondary_img'];
		$form->review_use_image  	 = (int) $row['form_review_use_image'];
		$form->review_title			 = $row['form_review_title'];
		$form->review_description	 = $row['form_review_description'];
		$form->resume_enable	 	 = $row['form_resume_enable'];
		$form->theme_id	    	 	 = (int) $row['form_theme_id'];
		$form->payment_show_total	 = (int) $row['payment_show_total'];
		$form->payment_total_location = $row['payment_total_location'];
		$form->payment_enable_merchant = (int) $row['payment_enable_merchant'];
		
		if($form->payment_enable_merchant < 1){
			$form->payment_enable_merchant = 0;
		}
		
		$form->payment_currency 	   = $row['payment_currency'];
		$form->payment_price_type 	   = $row['payment_price_type'];
		$form->payment_price_amount    = $row['payment_price_amount'];
		$form->limit_enable  		   = (int) $row['form_limit_enable'];
		$form->limit  				   = (int) $row['form_limit'];
		$form->schedule_enable  	   = (int) $row['form_schedule_enable'];
		$form->schedule_start_date  = $row['form_schedule_start_date'];
		$form->schedule_end_date  	= $row['form_schedule_end_date'];
		$form->schedule_start_hour  = $row['form_schedule_start_hour'];
		$form->schedule_end_hour  	= $row['form_schedule_end_hour'];
		$form->language 			= trim($row['form_language']);
		$form->logic_field_enable  	= (int) $row['logic_field_enable'];
		$form->logic_page_enable  	= (int) $row['logic_page_enable'];

		$form->enable_discount 		= (int) $row['payment_enable_discount'];
		$form->discount_type 	 	= $row['payment_discount_type'];
		$form->discount_amount 		= (float) $row['payment_discount_amount'];
		$form->discount_element_id 	= (int) $row['payment_discount_element_id'];

		$form->enable_tax 		 	= (int) $row['payment_enable_tax'];
		$form->tax_rate 			= (float) $row['payment_tax_rate'];

		$form->custom_script_enable = (int) $row['form_custom_script_enable'];
		$form->custom_script_url 	= $row['form_custom_script_url'];
		$form->form_for_selected_company 	= $row['form_for_selected_company'];
		$form->form_enable_auto_mapping 	= (int)$row['form_enable_auto_mapping'];

		//if the form has page logic enabled, store the page history
		if(!empty($form->logic_page_enable) && !empty($page_number)){
			//store the page numbers into session for history
			if($page_number == 1){ //if there is no current history, initialize with page 1
				$_SESSION['la_pages_history'][$form_id] = array();
				$_SESSION['la_pages_history'][$form_id][] = 1; 
			}else{
				//if the pages history already exist and the current page number already being stored
				//we need to remove it from the array first, along with any subsequent pages
				if(!empty($_SESSION['la_pages_history'][$form_id])) {
					if(in_array($page_number, $_SESSION['la_pages_history'][$form_id])){
						$current_page_index = array_search($page_number, $_SESSION['la_pages_history'][$form_id]);
						array_splice($_SESSION['la_pages_history'][$form_id], $current_page_index);
					}
				}

				$_SESSION['la_pages_history'][$form_id][] = (int) $page_number;
			}
		}
		
		if(!empty($form->language)){
			la_set_language($form->language);
		}

		if(empty($error_elements)){
			$form->is_error 	= 0;
		}
		else{
			$form->is_error 	= 1;
		}

		if(!empty($edit_id)){
			$form->active = 1;
		}		
		
		if(isset($form_params['populated_values'])) {
			$populated_values = $form_params['populated_values'];
		} else {
			$populated_values = la_get_entry_values($dbh, $form_id, $company_id, $entry_id);
		}
		
		//get price definitions for fields, if the merchant feature is enabled
		if(!empty($form->payment_enable_merchant) && $form->payment_price_type == 'variable'){
			$query = "select `element_id`, `option_id`, `price` from `".LA_TABLE_PREFIX."element_prices` where `form_id`=? order by `element_id`,`option_id` asc";
			$params = array($form_id);
			$sth = la_do_query($query,$params,$dbh);
			while($row = la_do_fetch_result($sth)){
				$element_prices_array[$row['element_id']][$row['option_id']] = $row['price'];
			}	
		}
		
		//get elements data
		//get element options first and store it into array
		$query = "SELECT `element_id`, `option_id`, `position`, `option`, `option_is_default` FROM ".LA_TABLE_PREFIX."element_options where form_id = ? and live=1 order by element_id asc,`position` asc";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			$options_lookup[$element_id][$option_id]['position'] 		  = $row['position'];
			$options_lookup[$element_id][$option_id]['option'] 			  = $row['option'];
			$options_lookup[$element_id][$option_id]['option_is_default'] = $row['option_is_default'];
			
			if(isset($element_prices_array[$element_id][$option_id])){
				$options_lookup[$element_id][$option_id]['price_definition'] = $element_prices_array[$element_id][$option_id];
			}
		}
	
		$matrix_elements = array();
		
		//get elements data
		$element = array();

		if($page_number === 0){ //if page_number is 0, display all pages (this is being used on edit_entry page)
			$page_number_clause = '';
			$params = array($form_id);
		}
		else{
			$page_number_clause = 'and element_page_number = ?';
			$params = array($form_id,$page_number);
			
			if($isCasecadeForm){
				if(isset($_GET['casecade_form_page_number']) && $_GET['casecade_form_page_number'] != 'NO_ELEMENTS'){//echo '1';
					$page_number_clause .= ' and element_position >= ?';
					$params = array($form_id, $form_casecade['element_page_number'], $casecade_element_position);
				}
				
				if(isset($_GET['casecade_form_page_number']) && $_GET['casecade_form_page_number'] == 'NO_ELEMENTS'){//echo '2';
					$nxt_tmp_data = getElementData(array(
						'dbh' => $dbh,
						'form_id' => $form_id,
						'column' => 'element_page_number',
						'condition' => " and element_position = $casecade_element_position",
					));
					
					$page_number_clause .= ' and element_position > ?';
					$params = array($form_id, ($nxt_tmp_data[0]['element_page_number']+1), $casecade_element_position);
				}
			}
		} 
		
		if($isCasecadeForm){
			$post_element_query = base64_encode(json_encode(array('page_number_clause' => $page_number_clause, 'params' => $params)));   
			$parent_nxt_element = '<input type="hidden" name="parent_nxt_element" value="'.$post_element_query.'" />';  
		}

		if(!empty($form_params['itauditmachine_path'])){
			$itauditmachine_path = $form_params['itauditmachine_path'];
		}

		if(!empty($form_params['itauditmachine_data_path'])){
			$itauditmachine_data_path = $form_params['itauditmachine_data_path'];
		}

		$query = "SELECT * FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id = ? and element_status='1' {$page_number_clause} and element_type <> 'page_break' ORDER BY element_position asc";
		$sth = la_do_query($query,$params,$dbh);
		
		$j=0;
		$has_calendar = true; //assume the form doesn't have calendar, so it won't load calendar.js
		$has_advance_uploader = true;
		$has_signature_pad = true;
		$has_guidelines = false;
		
		while($row = la_do_fetch_result($sth)){
			$element[$j] = new stdClass();
			
			$element_id = $row['element_id'];
			
			//lookup element options first
			if(!empty($options_lookup[$element_id])){
				$element_options = array();
				$i=0;
				foreach ($options_lookup[$element_id] as $option_id=>$data){
					$element_options[$i] = new stdClass();
					$element_options[$i]->id 		 = $option_id;
					$element_options[$i]->option 	 = $data['option'];
					$element_options[$i]->is_default = $data['option_is_default'];
					$element_options[$i]->is_db_live = 1;
					
					if(isset($data['price_definition'])){
						$element_options[$i]->price_definition = $data['price_definition'];
					}
					
					$i++;
				}
			}			
		
			//populate elements
			$element[$j]->title 						= nl2br($row['element_title']);
			$element[$j]->guidelines 					= $row['element_guidelines'];
			
			if(!empty($row['element_guidelines']) && ($row['element_type'] != 'section') && ($row['element_type'] != 'matrix') && empty($row['element_is_private'])){
				$has_guidelines = true;
			}
			
			$element[$j]->size 							= $row['element_size'];
			$element[$j]->is_required 					= $row['element_is_required'];
			$element[$j]->is_unique 					= $row['element_is_unique'];
			$element[$j]->is_private 					= $row['element_is_private'];
			$element[$j]->type 							= $row['element_type'];
			$element[$j]->position 						= $row['element_position'];
			$element[$j]->id 							= $row['element_id'];
			$element[$j]->form_id   					= $row['form_id'];
			$element[$j]->is_db_live 					= 1;
			$element[$j]->form_id 						= $form_id;
			$element[$j]->choice_has_other   			= (int) $row['element_choice_has_other'];
			$element[$j]->choice_other_label 			= $row['element_choice_other_label'];
			$element[$j]->choice_columns   	 			= (int) $row['element_choice_columns'];
			$element[$j]->time_showsecond    			= (int) $row['element_time_showsecond'];
			$element[$j]->time_24hour    	 			= (int) $row['element_time_24hour'];
			$element[$j]->address_hideline2	 			= (int) $row['element_address_hideline2'];
			$element[$j]->address_us_only	 			= (int) $row['element_address_us_only'];
			$element[$j]->date_enable_range	 			= (int) $row['element_date_enable_range'];
			$element[$j]->date_range_min	 			= $row['element_date_range_min'];
			$element[$j]->date_range_max	 			= $row['element_date_range_max'];
			$element[$j]->date_enable_selection_limit	= (int) $row['element_date_enable_selection_limit'];
			$element[$j]->date_selection_max	 		= (int) $row['element_date_selection_max'];
			$element[$j]->date_disable_past_future	 	= (int) $row['element_date_disable_past_future'];
			$element[$j]->date_past_future	 			= $row['element_date_past_future'];
			$element[$j]->date_disable_weekend	 		= (int) $row['element_date_disable_weekend'];
			$element[$j]->date_disable_specific	 		= (int) $row['element_date_disable_specific'];
			$element[$j]->date_disabled_list	 		= $row['element_date_disabled_list'];
			$element[$j]->file_enable_type_limit	 	= (int) $row['element_file_enable_type_limit'];						
			$element[$j]->file_block_or_allow	 		= $row['element_file_block_or_allow'];
			$element[$j]->file_type_list	 			= $row['element_file_type_list'];
			$element[$j]->file_as_attachment	 		= (int) $row['element_file_as_attachment'];	
			$element[$j]->file_enable_advance	 		= (int) $row['element_file_enable_advance'];
			
			if(!empty($element[$j]->file_enable_advance) && ($row['element_type'] == 'file')){
				$has_advance_uploader = true;
			}
			
			$element[$j]->file_auto_upload	 			= (int) $row['element_file_auto_upload'];
			$element[$j]->file_enable_multi_upload	 	= (int) $row['element_file_enable_multi_upload'];
			$element[$j]->file_max_selection	 		= (int) $row['element_file_max_selection'];
			$element[$j]->file_enable_size_limit	 	= (int) $row['element_file_enable_size_limit'];
			$element[$j]->file_size_max	 				= (int) $row['element_file_size_max'];
			$element[$j]->matrix_allow_multiselect	 	= (int) $row['element_matrix_allow_multiselect'];
			$element[$j]->matrix_parent_id	 			= (int) $row['element_matrix_parent_id'];
			$element[$j]->upload_dir	 				= $la_settings['upload_dir'];		
			$element[$j]->range_min	 					= $row['element_range_min'];
			$element[$j]->range_max	 					= $row['element_range_max'];
			$element[$j]->range_limit_by	 			= $row['element_range_limit_by'];
			$element[$j]->itauditmachine_path	 		= $itauditmachine_path;
			$element[$j]->itauditmachine_data_path	 	= $itauditmachine_data_path;
			$element[$j]->section_display_in_email	 	= (int) $row['element_section_display_in_email'];
			$element[$j]->section_enable_scroll	 		= (int) $row['element_section_enable_scroll'];
			$element[$j]->element_id_auto	 			= (int) $row['id'];
			$element[$j]->label_background_color	 	= $row['element_label_background_color'];
			$element[$j]->label_color	 				= $row['element_label_color'];
			$element[$j]->element_machine_code			= $row['element_machine_code'];
			$element[$j]->file_upload_synced    		= $row['element_file_upload_synced'];
			$element[$j]->file_select_existing_files   	= (int) $row['element_file_select_existing_files'];

			if(!empty($form->payment_enable_merchant) && !empty($row['element_number_enable_quantity']) && !empty($row['element_number_quantity_link'])){
				$element[$j]->number_quantity_link	 	= $row['element_number_quantity_link'];
			}

			//this data came from db or form submit
			//being used to display edit form or redisplay form with errors and previous inputs
			//this should be optimized in the future, only pass necessary data, not the whole array	
            
         // Populated Values		
			$element[$j]->populated_value = $populated_values;
			$element[$j]->company_id = $company_id;
			$element[$j]->entry_id = $entry_id;
			
			//set prices for price-enabled field
			if($row['element_type'] == 'money' && isset($element_prices_array[$row['element_id']][0])){
				$element[$j]->price_definition = 0;
			}
			
			//if there is file upload type, set form enctype to multipart
			if($row['element_type'] == 'file'){
				$form_enc_type = 'enctype="multipart/form-data"';
				
				//if this is single page form with review enabled or multipage form
                //echo $_SESSION['review_id']; exit;
				if ((!empty($form->review) && !empty($_SESSION['review_id']) && !empty($populated_file_values)) ||
					($form->page_total > 1 && !empty($populated_file_values))	
				) {
					//populate the default value for uploaded files, when validation error occured

					//make sure to keep the file token if exist
					if(!empty($populated_values['element_'.$row['element_id']]['file_token'])){
						$populated_file_values['element_'.$row['element_id']]['file_token'] = $populated_values['element_'.$row['element_id']]['file_token'];
					}

					// Populated Values
					$element[$j]->populated_value = $populated_file_values;
				}
			}

			if(!empty($edit_id) && $_SESSION['la_logged_in'] === true){
				//if this is edit_entry page
				$element[$j]->is_edit_entry = true;
			}
			
			if(!empty($error_elements[$element[$j]->id])){
				$element[$j]->is_error 	    = 1;
				$element[$j]->error_message = $error_elements[$element[$j]->id];
			}			
			
			// Default value
			$element[$j]->default_value = $row['element_default_value'];			
			
			$element[$j]->constraint 	= $row['element_constraint'];
			if(!empty($element_options)){
				$element[$j]->options 	= $element_options;
			}
			else{
				$element[$j]->options 	= '';
			}
			
			//check for signature type
			if($row['element_type'] == 'signature'){
				$has_signature_pad = true;
			}
			
			//check for calendar type
			if($row['element_type'] == 'date' || $row['element_type'] == 'europe_date'){
				$has_calendar = true;
				
				//if the field has date selection limit, we need to do query to existing entries and disable all date which reached the limit
				if(!empty($row['element_date_enable_selection_limit']) && !empty($row['element_date_selection_max'])){
					//$sub_query = "select selected_date from ( select date_format(element_{$row['element_id']},'%m/%d/%Y') as selected_date, count(element_{$row['element_id']}) as total_selection from ".LA_TABLE_PREFIX."form_{$form_id} where status=1 and element_{$row['element_id']} is not null group by element_{$row['element_id']} ) as A where A.total_selection >= ?";
					$sub_query = "select selected_date from (select date_format(`data_value`, '%m/%d/%Y') as selected_date, count(`data_value`) as total_selection from `".LA_TABLE_PREFIX."form_{$form_id}` where `field_name` = 'element_{$row['element_id']}' and `data_value` is not null group by `data_value`) as A where A.total_selection >= ?";
					$params = array($row['element_date_selection_max']);
					$sub_sth = la_do_query($sub_query,$params,$dbh);
					$current_date_disabled_list = array();
					$current_date_disabled_list_joined = '';
					
					while($sub_row = la_do_fetch_result($sub_sth)){
						$current_date_disabled_list[] = $sub_row['selected_date'];
					}
					
					$current_date_disabled_list_joined = implode(',',$current_date_disabled_list);
					if(!empty($element[$j]->date_disable_specific)){ //add to existing disable date list
						if(empty($element[$j]->date_disabled_list)){
							$element[$j]->date_disabled_list = $current_date_disabled_list_joined;
						}else{
							$element[$j]->date_disabled_list .= ','.$current_date_disabled_list_joined;
						}
					}else{
						//'disable specific date' is not enabled, we need to override and enable it from here
						$element[$j]->date_disable_specific = 1;
						$element[$j]->date_disabled_list = $current_date_disabled_list_joined;
					}
					
				}
			}
			
			//if the element is a matrix field and not the parent, store the data into a lookup array for later use when rendering the markup
			if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){
				
				$parent_id 	 = $row['element_matrix_parent_id'];
				$el_position = $row['element_position'];
				$matrix_elements[$parent_id][$el_position]['title'] = $element[$j]->title; 
				$matrix_elements[$parent_id][$el_position]['id'] 	= $element[$j]->id; 
				$matrix_elements[$parent_id][$el_position]['element_status_indicator'] 	= $row['element_status_indicator'];	
				$matrix_elements[$parent_id][$el_position]['element_indicator'] 	= $row['element_indicator'];

				$matrix_child_option_id = '';
				foreach($element_options as $value){
					$matrix_child_option_id .= $value->id.',';
				}
				$matrix_child_option_id = rtrim($matrix_child_option_id,',');
				$matrix_elements[$parent_id][$el_position]['children_option_id'] = $matrix_child_option_id; 
				
				//remove it from the main element array
				$element[$j] = array();
				unset($element[$j]);
				$j--;
			}
			
			//check for enhanced checkbox
			if($row['element_type'] == 'checkbox'){
				$element[$j]->enhanced_checkbox = $row['element_enhanced_checkbox'];
			}
			
			$element[$j]->element_note				 	= $row['element_note'];
			$element[$j]->element_status_indicator	    = $row['element_status_indicator'];
			$element[$j]->rich_text			    		= $row['element_rich_text'];
			$element[$j]->element_indicator				= $row['element_indicator'];
			$element[$j]->form_id	 					= $form_id;
			$element[$j]->element_page_number           = $row['element_page_number'];
			
			$j++;
		}
				
		//add captcha if enabled
		//on multipage form, captcha should be displayed on the last page only
		if(!empty($form->captcha) && (empty($edit_id))){
			if($form->page_total == 1 || ($form->page_total == $page_number)){
				$element[$j] = new stdClass();
				$element[$j]->type 			= 'captcha';
				$element[$j]->captcha_type 	= $form->captcha_type;
				$element[$j]->form_id 		= $form_id;
				$element[$j]->is_private	= 0;
				$element[$j]->itauditmachine_path	= $itauditmachine_path;

				if(!empty($error_elements['element_captcha'])){
					$element[$j]->is_error 	    = 1;
					$element[$j]->error_message = $error_elements['element_captcha'];
				}
			}
		}
		
		//generate html markup for each element
		$container_class = '';
		
		$switch_column_btn = '<div class="d-none d-lg-block" style="padding: 9px">
			<input class="always-enable" id="switch-form-responsive" type="checkbox">
			<label for="switch-form-responsive">Responsive</label>
		</div>';
		$all_element_markup = '<div class="row m-0 all-elements"><div class="first-column p-0 col-12">';
	
		$form_default_element = getElementData(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'column' => 'element_default_value',
		));		
		
		foreach ($form_default_element as $element_data){
			array_push($casecade_form_id_arr, trim($element_data['element_default_value']));
		}
		
		//echo '<pre>';print_r($element);echo '</pre>';die;
		$element_counts = count($element);

		foreach($element as $key => $element_data){
			$keystone_viewer = la_display_keystone_viewer($dbh, $form_id, $element_data);

			if (($element_counts % 2 == 0 && $key == intval($element_counts/2)) || ($element_counts % 2 == 1 && $key == intval($element_counts/2) + 1)) {
				$all_element_markup .= <<<EOT
					</div>
					<div class="second-column p-0 col-12">
				EOT;
			}
			if($element_data->is_private && empty($edit_id)){ //don't show private element on live forms
				continue;
			}
			
			$casecade_element_position = $element_data->position;
			
			//if this is matrix field, build the children data from $matrix_elements array
			if($element_data->type == 'matrix'){
				$element_data->matrix_children = $matrix_elements[$element_data->id];
			}
				
			if($element_data->type == 'casecade_form'){
				$cascade_form_id = $element_data->default_value;

				$casecade_error_elements = isset($error_elements['casecade'][$element_data->default_value]) ? $error_elements['casecade'][$element_data->default_value] : array();
				$all_element_markup .= viewCasecadeForm(array('dbh' => $dbh, 'form_id' => $element_data->default_value, 'parent_form_id' => $form_id, 'company_id' => $company_id, 'entry_id' => $entry_id, 'form_params' => array('populated_values' => $populated_values['element_'.$element_data->id][$element_data->default_value], 'error_elements' => $casecade_error_elements), 'parent_form_page_number' => $element_data->element_page_number, 'element_position' => $element_data->position, 'page_number' => (!$casecade_form_page_number ? 1 : $casecade_form_page_number)));
				$casecade_form_page_number = isset($_REQUEST['casecade_form_page_number']) ? $_REQUEST['casecade_form_page_number'] : 1;
				$casecade_element_position = $element_data->position;
				
				if(!isset($_GET['casecade_form_page_number'])){//echo '1';
					break;
				}elseif(($total_pages+1) != $casecade_form_page_number && $_GET['casecade_form_page_number'] != 'NO_ELEMENTS'){//echo '2';
					break;	
				}elseif($_GET['casecade_form_page_number'] == 'NO_ELEMENTS'){//echo '3';
					$casecade_form_page_number = 1;
					$parent_nxt_element = '<input type="hidden" name="parent_nxt_element" value="'.$post_element_query.'" />';
					break;
				}else{//echo '4';
					$nxt_tmp_data = getElementData(array(
						'dbh' => $dbh,
						'form_id' => $form_id,
						'column' => 'element_type',
						'condition' => " and element_position = $casecade_element_position",
					));
					
					// here needs the last condition
					
					if($nxt_tmp_data[0]['element_type'] == 'casecade_form'){
						if($_GET['casecade_element_position'] == $casecade_element_position && $_GET['casecade_form_page_number'] != 'NO_ELEMENTS'){
							$casecade_form_page_number = 'NO_ELEMENTS';
						}else{
							$casecade_form_page_number = 1;
							break;
						}
						$parent_nxt_element = '<input type="hidden" name="parent_nxt_element" value="'.$post_element_query.'" />';
					}
				}
			}
			else if($element_data->type == 'syndication'){
				$all_element_markup .= call_user_func('la_display_'.$element_data->type, $element_data, false, $dbh);	
			}
			else if($element_data->type == 'file'){
				if( $element_data->file_upload_synced == 1 && !empty($element_data->element_machine_code) ) {
					$all_element_markup .= call_user_func('la_display_file_synced', $element_data, $dbh, false, $showSubmit);
				} else {
					$all_element_markup .= call_user_func('la_display_'.$element_data->type, $element_data, false, $showSubmit);
				}
			}
			else if ($element_data->type == 'signature') {
				$all_element_markup .= call_user_func('la_display_digital_signature', $dbh, $form_id);
			}
			else if ($element_data->type == 'matrix' || $element_data->type == 'section') {
				$all_element_markup .= call_user_func('la_display_'.$element_data->type,$element_data, false);
			} else {
				$all_element_markup .= call_user_func('la_display_'.$element_data->type,$element_data, $keystone_viewer);
			}
		}
		$all_element_markup .= "</div></div>";

		if(!empty($custom_error)){
			$form->error_message =<<<EOT
			<li id="error_message">
					<h3 id="error_message_title">{$custom_error}</h3>
			</li>	
EOT;
		}
		elseif(!empty($error_elements)){
			$form->error_message =<<<EOT
			<li id="error_message">
					<h3 id="error_message_title">{$la_lang['error_title']}</h3>
					<p id="error_message_desc">{$la_lang['error_desc']}</p>
			</li>	
EOT;
		}
		
		//if this form is using custom theme and not on edit entry page
		if(!empty($form->theme_id) && isset($_SESSION['la_client_entity_id'])){
			//get the field highlight color for the particular theme
			$query = "SELECT 
							highlight_bg_type,
							highlight_bg_color,
							form_shadow_style,
							form_shadow_size,
							form_shadow_brightness,
							form_button_type,
							form_button_text,
							form_button_image,
							theme_has_css  
						FROM 
							".LA_TABLE_PREFIX."form_themes 
					   WHERE 
					   		theme_id = ?";
			$params = array($form->theme_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			$form_shadow_style 		= $row['form_shadow_style'];
			$form_shadow_size 		= $row['form_shadow_size'];
			$form_shadow_brightness = $row['form_shadow_brightness'];
			$theme_has_css 			= (int) $row['theme_has_css'];
			
			//if the theme has css file, make sure to refer to that file
			//otherwise, generate the css dynamically

			$css_timestamp_1 = "";
			//make sure to put a timestamp for the CSS file for logged in users
			//so that the CSS file generated by the theme editor is always being applied immediately
			//echo '<pre>';print_r($_SESSION);echo '</pre>';
			if(!empty($_SESSION['la_client_logged_in'])){
				$css_timestamp_1 = "?t=".time(); 
				$css_timestamp_2 = "&t=".time();
			}

			if(!empty($theme_has_css)){
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.$la_settings['data_dir'].'/themes/theme_'.$form->theme_id.'.css'.$css_timestamp_1.'" media="all" />';
			}else{
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.'css_theme.php?theme_id='.$form->theme_id.$css_timestamp_2.'" media="all" />';
			}
			
			if($row['highlight_bg_type'] == 'color'){
				$field_highlight_color = $row['highlight_bg_color'];
			}else{ 
				//if the field highlight is using pattern instead of color, set the color to empty string
				$field_highlight_color = ''; 
			}
			
			//get the css link for the fonts
			//$font_css_markup = la_theme_get_fonts_link($dbh,$form->theme_id);
			
			//get the form shadow classes
			if(!empty($form_shadow_style) && ($form_shadow_style != 'disabled')){
				preg_match_all("/[A-Z]/",$form_shadow_style,$prefix_matches);
				//this regex simply get the capital characters of the shadow style name
				//example: RightPerspectiveShadow result to RPS and then being sliced to RP
				$form_shadow_prefix_code = substr(implode("",$prefix_matches[0]),0,-1);
				
				$form_shadow_size_class  = $form_shadow_prefix_code.ucfirst($form_shadow_size);
				$form_shadow_brightness_class = $form_shadow_prefix_code.ucfirst($form_shadow_brightness);

				if(empty($integration_method)){ //only display shadow if the form is not being embedded using any method
					$form_container_class = $form_shadow_style.' '.$form_shadow_size_class.' '.$form_shadow_brightness_class;
				}
			}
			
			//get the button text/image setting
			 
			
			if(empty($form->review)){
            	
				if($row['form_button_type'] == 'text'){
					$submit_button_markup = '<input id="submit_form" class="button_text" type="submit" name="submit_form" value="'.$row['form_button_text'].'" />';
					$submit_button_markup_top = '<input id="submit_form_top" class="button_text" type="button" value="'.$row['form_button_text'].'" />';
				}else{
					$submit_button_markup = '<input class="submit_img_primary" type="image" alt="Submit" id="submit_form" name="submit_form" src="'.$row['form_button_image'].'" />';
					$submit_button_markup_top = '<input class="submit_img_primary" type="image" alt="Submit" id="submit_form_top" src="'.$row['form_button_image'].'" />';
				}			
			}
			else{
				if($row['form_button_type'] == 'text'){
					$submit_button_markup = '<input id="submit_form" class="button_text" type="submit" name="submit_form" value="'.$la_lang['continue_button'].'" />';
					$submit_button_markup_top = '<input id="submit_form_top" class="button_text" type="button" value="'.$la_lang['continue_button'].'" />';
				}else{
					$submit_button_markup = '<input class="submit_img_primary" type="image" alt="Submit" id="submit_form" name="submit_form" src="'.$row['form_button_image'].'" />';
					$submit_button_markup_top = '<input class="submit_img_primary" type="image" alt="Submit" id="submit_form_top" src="'.$row['form_button_image'].'" />';
				}
			}
			
		}
		else{ //if the form doesn't have any theme being applied
			$field_highlight_color = '#FFF7C0';
			
			if(empty($integration_method)){
				$form_container_class = ''; //default shadow as of v4.2 is no-shadow
			}else{
				$form_container_class = ''; //dont show any shadow when the form being embedded
			}
			
			
			if(empty($form->review)){
				$submit_button_markup = '<input id="submit_form" class="button_text" type="submit" name="submit_form" value="'.$la_lang['submit_button'].'" />';
				$submit_button_markup_top = '<input id="submit_form_top" class="button_text" type="button" value="'.$la_lang['submit_button'].'" />';
			}else{
				$submit_button_markup = '<input id="submit_form" class="button_text" type="submit" name="submit_form" value="'.$la_lang['continue_button'].'" />';
				$submit_button_markup_top = '<input id="submit_form_top" class="button_text" type="button" value="'.$la_lang['continue_button'].'" />';
			}
		}
		
		//display edit_id if there is any, this is being called on edit_entry.php page
		if(!empty($edit_id)){
			$edit_markup = "<input type=\"hidden\" name=\"edit_id\" value=\"{$edit_id}\" />\n";
			$submit_button_markup = '<input id="submit_form" class="bb_button bb_green" type="submit" name="submit_form" value="Save Changes" />';
			$submit_button_markup_top = '<input id="submit_form_top" class="bb_button bb_green" type="button" value="Save Changes" />';
		}else{
			$edit_markup = '';
		}
		

		//check for specific form css, if any, use it instead
		if($form->has_css){
			$css_dir = $la_settings['data_dir']."/form_{$form_id}/css/";
		}
		
		if(!empty($form->password) && empty($_SESSION['user_authenticated'])){ //if form require password and password hasn't set yet
			$show_password_form = true;
			
		}elseif (!empty($form->password) && !empty($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] != $form_id){ //if user authenticated but not for this form
			$show_password_form = true;
			
		}else{ //user authenticated for this form, or no password required
			$show_password_form = false;
		}

		
		if($show_password_form){
			$submit_button_markup = '<input id="submit_form" class="button_text" type="submit" name="submit_form" value="'.$la_lang['submit_button'].'" />';
			$submit_button_markup_top = '<input id="submit_form_top" class="button_text" type="button" value="'.$la_lang['submit_button'].'" />';
		}	

		$exit_button = '<input id="exit_form_view" class="button_text always-enable" type="button" value="Exit" />';
		//default markup for single page form submit button
		$button_markup =<<<EOT
			<li id="li_buttons" class="buttons" style="background-color:#ffffff;">
					<input type="hidden" name="form_id" value="{$form->id}" />
					<input type="hidden" name="company_id" value="{$company_id}" />
					<input type="hidden" name="entry_id" value="{$entry_id}" />
					{$edit_markup}
					<input type="hidden" name="submit_form" value="1" />
					<input type="hidden" name="page_number" value="{$page_number}" />
					<input type="hidden" name="casecade_form_page_number" value="{$casecade_form_page_number}" />
					<input type="hidden" name="casecade_element_position" value="{$casecade_element_position}" />
					{$parent_nxt_element}
					{$submit_button_markup}
					{$exit_button}
			</li>
EOT;

		$button_markup_top =<<<EOT
			<li id="li_buttons_top" class="buttons" style="background-color:#ffffff;">
					{$submit_button_markup_top}
					{$exit_button}
			</li>
EOT;
		
		//check for form limit rule
		$form_has_maximum_entries = false;
		
		/*if(!empty($form->limit_enable)){
			$total_entries  = getFormCounter($dbh, array('form_id' => $form_id, 'company_id' => $_SESSION['la_user_id']));

			if($total_entries >= $form->limit){
				$form_has_maximum_entries = true;
			}
		}*/

		//check for automatic scheduling limit, if enabled
		if(!empty($form->schedule_enable) && empty($edit_id)){
			$schedule_start_time = strtotime($form->schedule_start_date.' '.$form->schedule_start_hour);
			$schedule_end_time = strtotime($form->schedule_end_date.' '.$form->schedule_end_hour);

			$current_time = strtotime(date("Y-m-d H:i:s"));

			if(!empty($schedule_start_time)){
				if($current_time < $schedule_start_time){
					$form->active = 0;
				}
			}

			if(!empty($schedule_end_time)){
				if($current_time > $schedule_end_time){
					$form->active = 0;
				}
			}
		}

		$is_edit_entry = false;
		if(!empty($edit_id) && $_SESSION['la_logged_in'] === true){
			//if this is edit_entry page
			$is_edit_entry = true;
		}
				
		if( (empty($form->active) || $form_has_maximum_entries) && $is_edit_entry === false ){ //if form is not active, don't show the fields
			$form_desc_div ='';	
			$all_element_markup = '';
			$button_markup = '';
			$button_markup_top = '';
			$ul_class = 'class="password"';

			if($form_has_maximum_entries){
				$inactive_message = $la_lang['form_limited'];
			}else{
				$inactive_message = $la_lang['form_inactive'];
			}

			if(!empty($form->disabled_message)){
				$inactive_message = nl2br($form->disabled_message);
			}

			$custom_element =<<<EOT
				<li>
					<h2>{$inactive_message}</h2>
				</li>
EOT;
		}
		elseif($show_password_form){ //don't show form description if this page is password protected and user not authenticated
			$form_desc_div ='';	
			$all_element_markup = '';	
			$custom_element =<<<EOT
				<li>
					<h2>{$la_lang['form_pass_title']}</h2>
					<div>
					<input type="password" value="" class="text" name="password" id="password" />
					<label for="password" class="desc">{$la_lang['form_pass_desc']}</label>
					</div>
				</li>
EOT;
			$ul_class = 'class="password"';
		}
		else{
			if(!empty($form->name) || !empty($form->description)){
				$form->description = nl2br($form->description);
				$form_desc_div =<<<EOT
					<div class="form_description">
						<h2>{$form->name}</h2>
						<p>{$form->description}</p>
					</div>
EOT;
			}
		}

		// if(!$has_guidelines){
		// 	$container_class .= " no_guidelines";
		// }
		
		if($integration_method == 'iframe'){
			$html_class_tag = 'class="embed"';
		}
		
		if($has_calendar){
			$calendar_init = '<script type="text/javascript" src="'.$itauditmachine_path.'js/datepick/jquery.datepick.js"></script>'."\n".
							 '<script type="text/javascript" src="'.$itauditmachine_path.'js/datepick/jquery.datepick.ext.js"></script>'."\n".
							 '<link type="text/css" href="'.$itauditmachine_path.'js/datepick/smoothness.datepick.css" rel="stylesheet" />';
		}else{
			$calendar_init = '';
		}

		if($has_signature_pad){
			$signature_pad_init = '<!--[if lt IE 9]><script src="'.$itauditmachine_path.'js/signaturepad/flashcanvas.js"></script><![endif]-->'."\n".
							 	  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/jquery.signaturepad.min.js"></script>'."\n".
							 	  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/json2.min.js"></script>'."\n";
		}else{
			$signature_pad_init = '';
		}

		//generate conditional logic code, if enabled and not on edit entry page
		if(!empty($form->logic_field_enable) && empty($edit_id)){
			$logic_js = la_get_logic_javascript($dbh,$form_id,$page_number);
		}else{
			$logic_js = '';
		}
		
		if($form->form_enable_auto_mapping)
			$auto_mapp_js = '<script type="text/javascript"> var form_enable_auto_mapping = 1 </script>';
		else
			$auto_mapp_js = '<script type="text/javascript"> var form_enable_auto_mapping = 0 </script>';

		//if the form has multiple pages
		//display the pagination header
		if($form->page_total > 1 && $show_password_form === false || count($casecade_form_id_arr) && $show_password_form === false){
			//build pagination header based on the selected type. possible values:
			//steps - display multi steps progress
			//percentage - display progress bar with percentage
			//disabled - disabled
			
			$page_breaks_data = array();
			$page_title_array = array();
			
			//get page titles
			$query = "SELECT 
							element_page_title,
							element_page_number,
							element_submit_use_image,
						    element_submit_primary_text,
							element_submit_secondary_text,
							element_submit_primary_img,
							element_submit_secondary_img,
							element_default_value,
							element_page_break_bg_color 
						FROM 
							".LA_TABLE_PREFIX."form_elements
					   WHERE
							form_id = ? and element_status = 1 and element_type = 'page_break'
					ORDER BY 
					   		element_page_number asc";
			$params = array($form_id);			
			$sth = la_do_query($query,$params,$dbh);
			
			$temp_page_number = 0;
			
			while($row = la_do_fetch_result($sth)){
				$temp_page_number = $row['element_page_number'];
				$page_breaks_data[$temp_page_number]['use_image'] 		= $row['element_submit_use_image'];
				$page_breaks_data[$temp_page_number]['primary_text'] 	= $row['element_submit_primary_text'];
				$page_breaks_data[$temp_page_number]['secondary_text'] 	= $row['element_submit_secondary_text'];
				$page_breaks_data[$temp_page_number]['primary_img']		= $row['element_submit_primary_img'];
				$page_breaks_data[$temp_page_number]['secondary_img'] 	= $row['element_submit_secondary_img'];
				$page_breaks_data[$temp_page_number]['page_break_bg_color'] 	= $row['element_page_break_bg_color'];
				
				$page_title_array[] = $row['element_page_title'];
			}
			
	 
							
			//add the last page buttons info into the array for easy lookup
			$page_breaks_data[$form->page_total]['use_image'] 		= $form->submit_use_image;
			$page_breaks_data[$form->page_total]['primary_text'] 	= $form->submit_primary_text;
			$page_breaks_data[$form->page_total]['secondary_text'] 	= $form->submit_secondary_text;
			$page_breaks_data[$form->page_total]['primary_img'] 	= $form->submit_primary_img;
			$page_breaks_data[$form->page_total]['secondary_img'] 	= $form->submit_secondary_img;
			$page_breaks_data[$form->page_total]['page_break_bg_color'] 	= $form->last_page_break_bg_color;
			
			//set background color of page
			$page_background = '#fffff';
			if( !empty($page_breaks_data) ) {
				if(!empty($page_breaks_data[$page_number]['page_break_bg_color']))
					$page_background = $page_breaks_data[$page_number]['page_break_bg_color'];
			}

			if($form->pagination_type == 'steps'){
				
				$page_titles_markup = '';
				
				$i=1;
				foreach ($page_title_array as $page_title){
					if($i == $page_number){
						$ap_tp_num_active = ' ap_tp_num_active';
						$ap_tp_text_active = ' ap_tp_text_active';
					}else{
						$ap_tp_num_active = '';
						$ap_tp_text_active = '';
					}
					
					$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num'.$ap_tp_num_active.'">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text'.$ap_tp_text_active.'">'.$page_title.'</span></td><td align="center" class="ap_tp_arrow">&gt;</td>'."\n";
					$i++;
				}
				
				//add the last page title into the pagination header markup
				if($i == $page_number){
					$ap_tp_num_active = ' ap_tp_num_active';
					$ap_tp_text_active = ' ap_tp_text_active';
				}else{
					$ap_tp_num_active = '';
					$ap_tp_text_active = '';
				}
				
				$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num'.$ap_tp_num_active.'">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text'.$ap_tp_text_active.'">'.$form->lastpage_title.'</span></td>';
			
				//if form review enabled, we need to add the pagination header
				if(!empty($form->review)){
            
					$i++;
					$page_titles_markup .= '<td align="center" class="ap_tp_arrow">&gt;</td><td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$form->review_title.'</span></td>';
         
				}

				//if payment enabled, we need to add the pagination header
				if(!empty($form->payment_enable_merchant)){

				}
				
				$pagination_header =<<<EOT
					<li id="pagination_header" class="li_pagination" style="background-color:#fff">
					<table class="ap_table_pagination" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr> 
						{$page_titles_markup}
					</tr>
					</table>
					</li>
EOT;
			}
			else if($form->pagination_type == 'percentage'){
				
				$page_total = count($page_title_array) + 1;
  				
				if(!empty($form->review)){
					$page_total++;
				}

				if(!empty($form->payment_enable_merchant)){
					//$page_total++;
				}

				$percent_value = round(($page_number/$page_total) * 100);
				
				if($percent_value == 100){ //it's not make sense to display 100% when the form is not really submitted yet
					$percent_value = 99;
				}
				
				if(!empty($form->review) && !empty($form->payment_enable_merchant)){
					if(($page_total-2) == $page_number){ //if this is last page of the form
						$current_page_title = $form->lastpage_title;
					}else{
						$current_page_title = $page_title_array[$page_number-1];
					}
				}else if(!empty($form->review) || !empty($form->payment_enable_merchant)){
					if(($page_total-1) == $page_number){ //if this is last page of the form
						$current_page_title = $form->lastpage_title;
					}else{
						$current_page_title = $page_title_array[$page_number-1];
					}
				}else{
					if($page_total == $page_number){ //if this is last page of the form
						$current_page_title = $form->lastpage_title;
					}else{
						$current_page_title = $page_title_array[$page_number-1];
					}
				}

				
				$page_number_title = sprintf($la_lang['page_title'],$page_number,$page_total);
				$pagination_header =<<<EOT
					<li id="pagination_header" class="li_pagination">
						<h3 id="page_title_{$page_number}">{$page_number_title} - {$current_page_title}</h3>
						<div class="la_progress_container">          
							<div id="la_progress_percentage" class="la_progress_value" style="width: {$percent_value}%"><span>{$percent_value}%</span></div>
						</div>
					</li>
EOT;
			}
			else{			
				$pagination_header = '';
			}

			//build the submit buttons markup
			if(empty($edit_id)){
				if(empty($page_breaks_data[$page_number]['use_image'])){ //if using text buttons as submit
				
					if($page_number > 1){
						$button_secondary_markup = '<input class="button_text btn_secondary" type="submit" id="submit_secondary" name="submit_secondary" value="'.$page_breaks_data[$page_number]['secondary_text'].'" />';
						$button_secondary_markup_top = '<input class="button_text btn_secondary" type="button" id="submit_secondary_top" value="'.$page_breaks_data[$page_number]['secondary_text'].'" />';
					}



					//Gets the total number of pages of the current Cascade Form.
					
					$total_cascade_pages = getNoOfPages($dbh,$cascade_form_id);
					
					//Adds 1 to it, since it will be 0 if there is no page breaks, when in actuality any form has 1 page.
					//If there's 1 page break, there is 2 pages. 1 and 2.
										
					$total_cascade_pages = $total_cascade_pages + 1;
					
					//If it has only 1 page, then we will set total_cascade_pages to NO ELEMENTS so it doesn't advance this variable when submitting the form.
					if ($total_cascade_pages == 1) {						
						$casecade_form_page_number = "NO_ELEMENTS";						
					}

 

					//Here's where I think it needs tweaks. We should only advance the Main Page Variable (
					if ($_GET['casecade_form_page_number'] != $total_cascade_pages && $page_number != 1 && $total_cascade_pages > 1) {
						
					//	echo "YES SUBTRACT A PAGE!";
						
				 		$page_number = $page_number - 1;
					//	$page_number = $page_number - $_GET['casecade_form_page_number']; SOMETHING LIKE THIS
				 		
					}

					//add check here
					// echo '$page_number:-'.$page_number;
					// echo '$form->page_total:-'.$form->page_total;
					if( $form->page_total == $page_number ) {
						//means this is the last page of form
						$last_page_var = '<input type="hidden" name="last_page_var" value="'.$page_number.'" /> ';
					}
					
 					

					
					$button_markup =<<<EOT
						<li id="li_buttons" class="buttons" style="background-color:#fff">
								<input type="hidden" name="form_id" value="{$form->id}" />
								<input type="hidden" name="entry_id" value="{$entry_id}" />
								<input type="hidden" name="company_id" value="{$company_id}" />
								{$edit_markup}
								<input type="hidden" name="submit_form" value="1" />
								<input type="hidden" name="page_number" value="{$page_number}" />
								<input type="hidden" name="casecade_form_page_number" value="{$casecade_form_page_number}" />
								<input type="hidden" name="casecade_element_position" value="{$casecade_element_position}" />
								<input class="button_text btn_primary" type="submit" id="submit_primary" name="submit_primary" value="{$page_breaks_data[$page_number]['primary_text']}" />
								{$parent_nxt_element}
								{$button_secondary_markup}
								{$exit_button}
						</li>
EOT;
					$button_markup_top =<<<EOT
						<li id="li_buttons_top" class="buttons" style="background-color:#fff">				    
								<input class="button_text btn_primary" type="button" id="submit_primary_top" value="{$page_breaks_data[$page_number]['primary_text']}" />
								{$button_secondary_markup_top}
								{$exit_button}
						</li>
EOT;
				}else{ //if using images as submit
					
					if($page_number > 1){
						$button_secondary_markup = '<input class="submit_img_secondary" type="image" alt="Previous" id="submit_secondary" name="submit_secondary" src="'.$page_breaks_data[$page_number]['secondary_img'].'" />';
						$button_secondary_markup_top = '<input class="submit_img_secondary" type="image" alt="Previous" id="submit_secondary_top" src="'.$page_breaks_data[$page_number]['secondary_img'].'" />';
					}
					
					$button_markup =<<<EOT
						<li id="li_buttons" class="buttons" style="background-color:#fff">
								<input type="hidden" name="form_id" value="{$form->id}" />
								<input type="hidden" name="entry_id" value="{$entry_id}" />
								<input type="hidden" name="company_id" value="{$company_id}" />
								{$edit_markup}
								<input type="hidden" name="submit_form" value="1" />
								<input type="hidden" name="page_number" value="{$page_number}" />
								<input type="hidden" name="casecade_form_page_number" value="{$casecade_form_page_number}" />
								<input type="hidden" name="casecade_element_position" value="{$casecade_element_position}" />
								<input class="submit_img_primary" type="image" alt="Continue" id="submit_primary" name="submit_primary" src="{$page_breaks_data[$page_number]['primary_img']}" />
								{$parent_nxt_element}
								{$button_secondary_markup}
								{$exit_button}
						</li>
EOT;
					$button_markup_top =<<<EOT
						<li id="li_buttons_top" class="buttons" style="background-color:#fff">
								<input class="submit_img_primary" type="image" alt="Continue" id="submit_primary_top" src="{$page_breaks_data[$page_number]['primary_img']}" />
								{$button_secondary_markup_top}
								{$exit_button}
						</li>
EOT;
				}
			}
			else{ //if there is edit_id, then this is edit_entry page, display a standard button
				$button_markup =<<<EOT
					<li id="li_buttons" class="buttons" style="background-color:#fff">
							<input type="hidden" name="form_id" value="{$form->id}" />
							<input type="hidden" name="entry_id" value="{$entry_id}" />
							<input type="hidden" name="company_id" value="{$company_id}" />
							{$edit_markup}
							<input type="hidden" name="submit_form" value="1" />
							<input type="hidden" name="page_number" value="{$page_number}" />
							<input type="hidden" name="casecade_form_page_number" value="{$casecade_form_page_number}" />
							<input type="hidden" name="casecade_element_position" value="{$casecade_element_position}" />
							<input class="button_text btn_primary" type="submit" id="submit_primary" name="submit_primary" value="Save Changes" />
							{$parent_nxt_element}
							{$exit_button}
					</li>
EOT;
				$button_markup_top =<<<EOT
					<li id="li_buttons_top" class="buttons" style="background-color:#fff">
							<input class="button_text btn_primary" type="button" id="submit_primary_top" value="Save Changes" />
							{$exit_button}
					</li>
EOT;
			}
			
		}

 		
		// goto next page
        if($form->page_total > 1)
        {
            $button_escape = '<li class="button_escape">&nbsp;</li>
                              <li id="li_buttons_escape" class="button_escape">
                                 Navigate to Field Label:
                                 <div style="display: inline-table; margin: 0px!important; padding: 0px!important;">
                                 <input type="text" id="target-element" data-form-id="'.$form->id.'" autocomplete="off" style="width:260px;" />
                                 <div id="results-container" style="background: #FFF; margin: 1px!important; padding: 0px!important"></div>
                                 </div>
                                 <img id="loader-img" src="images/loader_small_grey.gif" style="display: none;">
                                 <input id="go-to-target-element" disabled type="button" class="button_text btn_primary" value="Go"><br>
                                 <style>
                                 	.go-to-field-li {
                                 		cursor: pointer;
                                 	}
                                 	.go-to-field-li:hover {
                                 		background: gray;
                                 	}
                                 </style>
                              </li>';
        }
		
		if($has_advance_uploader){

			if(!empty($itauditmachine_path)){
				$la_path_script =<<<EOT
					<script type="text/javascript">
					var __itauditmachine_path = '{$itauditmachine_path}';
					</script>
EOT;
			}

			$advance_uploader_js =<<<EOT
				<script type="text/javascript" src="{$itauditmachine_path}js/uploadify/swfobject.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/uploadify/jquery.uploadify.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/uploadifive/jquery.uploadifive.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery.jqplugin.min.js"></script>
				{$la_path_script}
EOT;
		}

		if($integration_method == 'iframe'){
			$auto_height_js =<<<EOT
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery.ba-postmessage.min.js"></script>
				<script type="text/javascript">
					$(function(){
						$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
					});
				</script>
EOT;
		}

		//if the form has resume enabled and this is multi page form (single page form doesn't have resume option)
		if(!empty($form->resume_enable) && $form->page_total > 1 && $show_password_form === false && empty($inactive_message)){

			if(!empty($error_elements['element_resume_email'])){
				$li_resume_email_style = '';
				$li_resume_error_message = "<p class=\"error\">{$error_elements['element_resume_email']}</p>";
				$li_resume_class = 'class="error"';
				$li_resume_checked = 'checked="checked"';
				$li_resume_button_status = 1;
			}else{
				$li_resume_email_style = 'style="display: none"';
				$li_resume_error_message = '';
				$li_resume_class = '';
				$li_resume_checked = '';
				$li_resume_button_status = 0;
			}
			
			$form_resume_markup_top = <<<EOT
				<li id="li_resume_checkbox_top">
				<div>
					<span><input type="checkbox" value="1" class="element checkbox" id="element_resume_checkbox_top" {$li_resume_checked}>
						<label for="element_resume_checkbox_top" class="choice">{$la_lang['resume_checkbox_title']}</label>
					</span>
				</div> 
				</li>
				<li id="li_resume_email_top" {$li_resume_class} {$li_resume_email_style}>
					<label for="element_resume_email_top" class="description">{$la_lang['resume_email_input_label']}</label>
					<div>
						<input type="text" value="{$populated_values['element_resume_email']}" class="element text medium" id="element_resume_email_top"> 
					</div><p id="guide_resume_email_top" class="guidelines"><small>{$la_lang['resume_guideline']}</small></p> {$li_resume_error_message}
				</li>
EOT;

			$form_resume_markup = <<<EOT
				<li id="li_resume_checkbox">
				<div>
					<input type="hidden" value="false" id="coming_from_session_timeout" name="coming_from_session_timeout">
					<span><input type="checkbox" value="1" class="element checkbox" name="element_resume_checkbox" id="element_resume_checkbox" {$li_resume_checked}>
						<label for="element_resume_checkbox" class="choice">{$la_lang['resume_checkbox_title']}</label>
					</span>
				</div> 
				</li>
				<li id="li_resume_email" {$li_resume_class} {$li_resume_email_style} data-resumebutton="{$li_resume_button_status}" data-resumelabel="{$la_lang['resume_submit_button_text']}">
					<label for="element_resume_email" class="description">{$la_lang['resume_email_input_label']}</label>
					<div>
						<input type="text" value="{$populated_values['element_resume_email']}" class="element text medium" name="element_resume_email" id="element_resume_email"> 
					</div><p id="guide_resume_email" class="guidelines"><small>{$la_lang['resume_guideline']}</small></p> {$li_resume_error_message}
				</li>
EOT;

		}
		
 		

		if(!empty($inactive_message)){
			$pagination_header = '';
			$button_markup = '';
 			$button_markup_top = '';
		}
		
		
 		
		if(empty($la_settings['disable_itauditmachine_link'])){
			$powered_by_markup = 'Powered by ITAM, the <a href="https://auditmachine.com/" target="_blank">IT Audit Machine</a>';
		}else{
			$powered_by_markup = '';
		}

		$jquery_url = $itauditmachine_path.'js/jquery.min.js';

		//load custom javascript if enabled
		$custom_script_js = '';
		if(!empty($form->custom_script_enable) && !empty($form->custom_script_url)){
			$custom_script_js = '<script type="text/javascript" src="'.$form->custom_script_url.'"></script>';
		}

		//scroll to element
		$element_id_auto_scroll_css = $element_id_auto_scroll_js = '';
		if( $_GET['element_id_auto'] ) {
			$element_id_auto_scroll_js = "<script type=\"text/javascript\">$(document).ready(function() { var scroll_target = $('.element_id_auto_{$_GET['element_id_auto']}');$(\"html, body\").animate({ scrollTop: scroll_target.offset().top }, 1);  });</script>";

			$element_id_auto_scroll_css = "<style type=\"text/css\">.element_id_auto_{$_GET['element_id_auto']}{background-color: #dedede;}</style>";
		}

 

		//if advanced form code being used, display the form without body container
        $showSubmit = true;
        $chatbot = chatbot($dbh, $form_id);


        $document_preview = '';
        $document_preview .= '<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">';
		if( isset($la_settings['file_viewer_download_option']) && ($la_settings['file_viewer_download_option'] == 1) ) {
			$document_preview .= '<div style="text-align: right;margin-bottom: 10px;">
				<a href="#" id="file_viewer_download_button" class="bb_button bb_small bb_green" download> 
					<img src="images/navigation/FFFFFF/24x24/Save.png"> Download
				</a>
			</div>';
		}
		$document_preview .= '<div id="document-preview-content" style="height: 440px;">
				<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
			</div>
			<div id="document-processing-dialog" style="display: none;text-align: center;font-size: 150%;">
				Processing Request...<br>
				<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
			</div>';

		if( $form->form_enable_auto_mapping == 1) {
			$socket_scripts =<<<EOT
				<script type="text/javascript" src="{$itauditmachine_path}../../../../itam-shared/js/socket.io.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}../../../../itam-shared/js/md5.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}../../../../itam-shared/js/socket-main.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}../../../../itam-shared/js/auto-mapping.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}../../../../itam-shared/js/moment.min.js"></script>
				<script type="text/javascript"> window.autoMapping = "enabled"; </script>
EOT;

			$textarea_socket_logic=<<<EOT
				function sendSocketData(data) {
					socket.emit('lock socket field', JSON.stringify(data));
				}
				function sendSocketDataOnBlur(data) {
					socket.emit('sync single field', JSON.stringify(data));
				}
				
				document.querySelectorAll('.textarea-formatting').forEach((node, index) => {
					newEditor = editors[index];
					newEditor.model.document.on('change:data', () => {
						var inputElemId = $(node).attr('id');
						var machineCode = $(node).data('element_machine_code');
						selectorString = '*[data-element_machine_code="'+machineCode+'"]';

						inputElem = $('form').find(selectorString);
						if( !inputElem.hasClass('locked') ){

							var formObj = inputElem
											.closest( "form" );
							var formId = formObj.data("formid");
							var userEmail = formObj.data("useremail");
							var entityId = formObj.attr('entity_id');
							var selected_entity_id = formObj.data("selected_entity_id");

							var data = editors[index].getData();
							var fieldVal = data;
							var fieldId = inputElemId;

							//remove the socket-info message in case user starts typing after field unlock
							var liElem = inputElem.closest('li');
							liElem.find('.socket-info').remove();

							var data = {
								element_id : fieldId,
								field_machine_code : machineCode,
								value : fieldVal,
								formId : formId,
								userEmail : userEmail,
								fieldType : 'textarea',
								fieldSubType : 'wysiwyg',
								entityId: entityId,
								selected_entity_id: selected_entity_id,
								form_domain: $('form').data("domain")
							};

							sendSocketData(data);
						}
					});

					newEditor.ui.focusTracker.on('change:isFocused', (evt, name, isFocused) => {
						if(!isFocused) {
							var inputElemId = $(node).attr('id');
							var machineCode = $(node).data('element_machine_code');
							selectorString = '*[data-element_machine_code="'+machineCode+'"]';

							inputElem = $('form').find(selectorString);
							if( !inputElem.hasClass('locked') ){

								var formObj = inputElem
												.closest( "form" );
								var formId = formObj.data("formid");
								var userEmail = formObj.data("useremail");
								var entityId = formObj.attr('entity_id');
								var selected_entity_id = formObj.data("selected_entity_id");

								var data = editors[index].getData();
								var fieldVal = data;
								var fieldId = inputElemId;

								//remove the socket-info message in case user starts typing after field unlock
								var liElem = inputElem.closest('li');
								liElem.find('.socket-info').remove();

								var data = {
									element_id : fieldId,
									field_machine_code : machineCode,
									value : fieldVal,
									formId : formId,
									userEmail : userEmail,
									fieldType : 'textarea',
									fieldSubType : 'wysiwyg',
									entityId: entityId,
									selected_entity_id: selected_entity_id,
									form_domain: $('form').data("domain")
								};

								sendSocketDataOnBlur(data);
							}
						}
					});
				});
EOT;

			$socket_connection_error_html =<<<EOT
				<div class="bootstrap-alert bootstrap-alert-notification" id="socket-connection-error" style="display:none;" role="alert">
							Not able to connect to socket. Please contact administrator.
							</div>
EOT;
		} else {
			$socket_scripts =<<<EOT
				<script type="text/javascript"> window.autoMapping = "disabled"; </script>
EOT;
		}

		$under_audit_dialog = <<<EOT
			<div id="under-audit-dialog" style="display: none">
				<p>This entry data is under audit</p>
			</div>
		EOT;

		$under_audit_js = "";
		if( $is_under_audit ) {
			$under_audit_js = <<<EOT
				<script type="text/javascript">
					$(document).ready(function() {
						$('#form_{$form->id} *').each(function() {
							if (!$(this).hasClass('always-enable')) {
								$(this).attr('disabled', 'disabled');
								$(this).off('click');
							}
						});

						$("#under-audit-dialog").dialog({
							modal: true,
							autoOpen: true,
							closeOnEscape: false,
							width: 550,
							draggable: false,
							resizable: false,
							buttons: [
								{
									text: 'Okay',
									id: 'btn-welcome-message-ok',
									'class': 'btn_secondary_action',
									click: function() {
										$(this).dialog('close');
									}
								}
							]
						});
					});
				</script>
			EOT;
		}
		
		if($integration_method == 'php'){
			if(!empty($edit_id) && $_SESSION['la_logged_in'] === true){
				$view_css_markup = '<link rel="stylesheet" type="text/css" href="css/edit_entry.css" media="all" />';
			}else{
				
			//	$view_css_markup = "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/view_default.css\" media=\"all\" />";
 				$view_css_markup = '<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />';
				
			}
            
            if($showSubmit == true){
            	$button_markup = $button_markup;
            	$button_markup_top = $button_markup_top;
            }else{
            	$button_markup = '';
            	$button_markup_top = '';
            }
            


            if(isset($_SESSION['la_client_entity_id']) && $_SESSION['la_client_entity_id'] == true){   



            	$container_class = "no_guidelines";
            	$form_markup = <<<EOT
					<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
					<html {$html_class_tag} xmlns="http://www.w3.org/1999/xhtml">
					<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
					<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
					<meta name="csrf-token" content="{$post_csrf_token}">
					<title>{$form->name}</title>
					<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
					<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
					<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}js/video-js/video-js.css" />
					<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
					<link type="text/css" href="css/override.css" rel="stylesheet" />
					<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
					<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
					<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
					{$theme_css_link}
					{$font_css_markup}
					<script type="text/javascript" src="{$jquery_url}"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
					<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
					<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/ckeditor5-classic/build/ckeditor.js"></script>
					<script type="text/javascript">
					$(document).ready(function(){
						window.editors = {};
						document.querySelectorAll('.textarea-formatting').forEach((node, index) => {
							ClassicEditor
								.create(node, {
									fontSize: {
										options: [
											9, 11, 13, 14, 15, 16, 'default', 17, 18, 19, 21
										],
										supportAllValues: true
									},
									toolbar: {
										items: [
											'heading', '|',
											'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
											'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
											'alignment', 'outdent', 'indent', '|',
											'todoList', 'numberedList', 'bulletedList', '|',
											'specialCharacters', 'subscript', 'superscript', '|',
											'horizontalLine', 'blockQuote', '|',
											'insertTable', '|',
											'link', 'imageUpload', 'mediaEmbed', '|',
											'removeFormat', '|',
											'undo', 'redo'
										],
										shouldNotGroupWhenFull: true
									},
									language: 'en',
									image: {
										toolbar: [
											'imageTextAlternative',
											'imageStyle:full',
											'imageStyle:side'
										]
									},
									table: {
										contentToolbar: [
											'tableColumn',
											'tableRow',
											'mergeTableCells',
											'tableCellProperties',
											'tableProperties'
										]
									},
									indentBlock: {
										offset: 1,
										unit: 'em'
									},
									licenseKey: ''
								}).then(newEditor => {
									window.editors[ index ] = newEditor;
								})
								.catch(error => {
									console.error(error);
								});
						});
					});

					var csrftoken =  (function() {
						// not need Jquery for doing that
						var metas = window.document.getElementsByTagName('meta');

						// finding one has csrf token 
						for(var i=0 ; i < metas.length ; i++) {
							if ( metas[i].name === "csrf-token") {
								return  metas[i].content;       
							}
						}  
					})();

					$(function () {
						$.ajaxSetup({
							headers: {
								"X-CSRFToken": csrftoken
							},
							data: {
								post_csrf_token: csrftoken
							}
						});
						
						var hashValue = location.hash;  
						hashValue = hashValue.replace(/^#/, '');  
						console.log("3");
						console.log(jQuery.trim(hashValue) == "");
						
						//do something with the value here	
						if(jQuery.trim(hashValue) != ""){
							$('html, body').animate({
								scrollTop: $("#" + hashValue).offset().top
							}, 2000);
						}
					});
					</script>
					<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
					<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/marquee/jquery.marquee.min.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/video-js/video.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/video-js/youtube.min.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/video-js/vimeo.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}js/app.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
					<script type="text/javascript" src="{$itauditmachine_path}custom-view-js-func.js"></script>
					{$advance_uploader_js}
					{$calendar_init}
					{$signature_pad_init}
					{$logic_js}
					{$auto_height_js}
					{$custom_script_js}
					{$under_audit_js}
					</head>
					<body id="main_body" class="{$container_class}">
						<div id="form_container" class="{$form_container_class}">
							<h1><a>{$form->name}</a></h1>
							<form id="form_{$form->id}"  entity_id="{$form->form_for_selected_company}" class="itauditm {$form->label_alignment}" {$form_enc_type} method="post" data-highlightcolor="{$field_highlight_color}" action="#main_body" data-useremail="{$user_email}" data-formid="{$form->id}"  data-domain="{$_SERVER['HTTP_HOST']}">
							<div style="display:none;">
								<input type="hidden" name="post_csrf_token" value="{$post_csrf_token}" />
							</div>
								{$form_desc_div}						
								<ul {$ul_class} style="background-color:{$page_background}">
								{$pagination_header}
								{$form->error_message}
								{$form_resume_markup_top}
								{$button_markup_top}
								{$switch_column_btn}
								{$payment_total_markup_top}
								{$all_element_markup}
								{$custom_element}
								{$payment_total_markup_bottom}
								{$form_resume_markup}
								{$button_markup}
								{$button_escape}
								</ul>
							</form>	
							<div id="form_footer">
								{$powered_by_markup}
							</div>
						</div>
						<div id="processing-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100; z-index: 1000;">
							<div style="font-size: 140%; text-align: center; vertical-align: middle; position: absolute; top: 30%; left: 37%; color: black; background-color: white; padding: 1rem 0rem 2rem; width: 32rem; border-radius: 0.5rem;">
								Saving your entries and generating your output document(s).<br>
								This may take up to 5 minutes to complete the process.<br>
								<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
								<p style="font-weight: bold;">Please do not close your browser. Thank you!</p>
							</div>
						</div>
						{$under_audit_dialog}
						{$document_preview}
						{$element_id_auto_scroll_js}
						{$element_id_auto_scroll_css}
						{$chatbot}
						{$primary_entity_not_selected}

EOT;
            }
            else{
            $container_class .= " integrated";
			$form_markup = <<<EOT
				{$view_css_markup}
				<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
				<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}js/video-js/video-js.css" />
				<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
				<link type="text/css" href="css/override.css" rel="stylesheet" />
				<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
				<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
				<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
				{$theme_css_link}
				{$font_css_markup}
				<style>
				html{
					background: none repeat scroll 0 0 transparent;
					background-color: transparent;
				}
				</style>
				<meta name="csrf-token" content="{$post_csrf_token}">
				<script type="text/javascript" src="{$jquery_url}"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
				<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/ckeditor5-classic/build/ckeditor.js"></script>
				<script type="text/javascript">
				$(document).ready(function(){
					window.editors = {};
					document.querySelectorAll('.textarea-formatting').forEach((node, index) => {
						ClassicEditor
							.create(node, {
								fontSize: {
									options: [
										9, 11, 13, 14, 15, 16, 'default', 17, 18, 19, 21
									],
									supportAllValues: true
								},
								toolbar: {
									items: [
										'heading', '|',
										'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
										'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
										'alignment', 'outdent', 'indent', '|',
										'todoList', 'numberedList', 'bulletedList', '|',
										'specialCharacters', 'subscript', 'superscript', '|',
										'horizontalLine', 'blockQuote', '|',
										'insertTable', '|',
										'link', 'imageUpload', 'mediaEmbed', '|',
										'removeFormat', '|',
										'undo', 'redo'
									],
									shouldNotGroupWhenFull: true
								},
								language: 'en',
								image: {
									toolbar: [
										'imageTextAlternative',
										'imageStyle:full',
										'imageStyle:side'
									]
								},
								table: {
									contentToolbar: [
										'tableColumn',
										'tableRow',
										'mergeTableCells',
										'tableCellProperties',
										'tableProperties'
									]
								},
								indentBlock: {
									offset: 1,
									unit: 'em'
								},
								licenseKey: ''
							}).then(newEditor => {
								window.editors[ index ] = newEditor;
							})
							.catch(error => {
								console.error(error);
							});
					});
				});

				var csrftoken =  (function() {
					// not need Jquery for doing that
					var metas = window.document.getElementsByTagName('meta');

					// finding one has csrf token 
					for(var i=0 ; i < metas.length ; i++) {
						if ( metas[i].name === "csrf-token") {
							return  metas[i].content;       
						}
					}  
				})();

				$(function () {
					$.ajaxSetup({
						headers: {
							"X-CSRFToken": csrftoken
						},
						data: {
							post_csrf_token: csrftoken
						}
					});
					
					var hashValue = location.hash;  
					hashValue = hashValue.replace(/^#/, '');  
					console.log("3");
					console.log(jQuery.trim(hashValue) == "");
					
					//do something with the value here	
					if(jQuery.trim(hashValue) != ""){
						$('html, body').animate({
							scrollTop: $("#" + hashValue).offset().top
						}, 2000);
					}
				});
				</script>
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
				<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/marquee/jquery.marquee.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/video.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/youtube.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/vimeo.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/app.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}custom-view-js-func.js"></script>
				{$advance_uploader_js}
				{$calendar_init}
				{$signature_pad_init}
				{$logic_js}
				{$custom_script_js}
				{$under_audit_js}
				<div id="main_body" class="{$container_class}">

					<div id="form_container">
					
						<h1><a>{$form->name}</a></h1>
						<form id="form_{$form->id}"  entity_id="{$form->form_for_selected_company}" class="itauditm {$form->label_alignment}" {$form_enc_type} method="post" data-highlightcolor="{$field_highlight_color}" action="#main_body" data-useremail="{$user_email}" data-formid="{$form->id}" data-domain="{$_SERVER['HTTP_HOST']}">
						<div style="display:none;">
							<input type="hidden" name="post_csrf_token" value="{$post_csrf_token}" />
						</div>
							{$form_desc_div}						
							<ul {$ul_class} style="background-color:{$page_background}">
							{$pagination_header}
							{$form->error_message}
							{$form_resume_markup_top}
							{$button_markup_top}
							{$switch_column_btn}
							{$payment_total_markup_top}
							{$all_element_markup}
							{$custom_element}
							{$payment_total_markup_bottom}
							{$form_resume_markup}
							{$button_markup}
							{$button_escape}
							</ul>
						</form>	
						<div id="form_footer">
							{$powered_by_markup}
						</div>
					</div>
				</div>
				<div id="processing-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100; z-index: 1000;">
					<div style="font-size: 140%; text-align: center; vertical-align: middle; position: absolute; top: 30%; left: 37%; color: black; background-color: white; padding: 1rem 0rem 2rem; width: 32rem; border-radius: 0.5rem;">
						Saving your entries and generating your output document(s).<br>
						This may take up to 5 minutes to complete the process.<br>
						<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
						<p style="font-weight: bold;">Please do not close your browser. Thank you!</p>
					</div>
				</div>
				{$under_audit_dialog}
				{$document_preview}
				{$chatbot}
				{$primary_entity_not_selected}

EOT;
            }
		}else{
			if($showSubmit == true){
            	$button_markup = $button_markup;
            	$button_markup_top = $button_markup_top;
            }else{
            	$button_markup = '';
            	$button_markup_top = '';
            }
            
			$form_markup = <<<EOT
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html {$html_class_tag} xmlns="http://www.w3.org/1999/xhtml">
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
				<meta name="csrf-token" content="{$post_csrf_token}">
				<title>{$form->name}</title>
				<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
				<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
				<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}js/video-js/video-js.css" />
				<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
				<link type="text/css" href="css/override.css" rel="stylesheet" />
				<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
				<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
				<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
				{$theme_css_link}
				{$font_css_markup}
				{$auto_mapp_js}
				<script type="text/javascript" src="{$jquery_url}"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/ckeditor5-classic/build/ckeditor.js"></script>
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
				<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
				{$socket_scripts}
				<script type="text/javascript">
				$(document).ready(function(){
					window.editors = {};
					document.querySelectorAll('.textarea-formatting').forEach((node, index) => {
						ClassicEditor
							.create(node, {
								fontSize: {
									options: [
										9, 11, 13, 14, 15, 16, 'default', 17, 18, 19, 21
									],
									supportAllValues: true
								},
								toolbar: {
									items: [
										'heading', '|',
										'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
										'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
										'alignment', 'outdent', 'indent', '|',
										'todoList', 'numberedList', 'bulletedList', '|',
										'specialCharacters', 'subscript', 'superscript', '|',
										'horizontalLine', 'blockQuote', '|',
										'insertTable', '|',
										'link', 'imageUpload', 'mediaEmbed', '|',
										'removeFormat', '|',
										'undo', 'redo'
									],
									shouldNotGroupWhenFull: true
								},
								language: 'en',
								image: {
									toolbar: [
										'imageTextAlternative',
										'imageStyle:full',
										'imageStyle:side'
									]
								},
								table: {
									contentToolbar: [
										'tableColumn',
										'tableRow',
										'mergeTableCells',
										'tableCellProperties',
										'tableProperties'
									]
								},
								indentBlock: {
									offset: 1,
									unit: 'em'
								},
								licenseKey: ''
							}).then(newEditor => {
								window.editors[ index ] = newEditor;
							})
							.catch(error => {
								console.error(error);
							});
					});
				});

				var csrftoken =  (function() {
					// not need Jquery for doing that
					var metas = window.document.getElementsByTagName('meta');

					// finding one has csrf token 
					for(var i=0 ; i < metas.length ; i++) {
						if ( metas[i].name === "csrf-token") {
							return  metas[i].content;       
						}
					}  
				})();

				$(function () {
					$.ajaxSetup({
						headers: {
							"X-CSRFToken": csrftoken
						},
						data: {
							post_csrf_token: csrftoken
						}
					});
					
					var hashValue = location.hash;  
					hashValue = hashValue.replace(/^#/, '');  
					console.log("3");
					console.log(jQuery.trim(hashValue) == "");
					
					//do something with the value here	
					if(jQuery.trim(hashValue) != ""){
						$('html, body').animate({
							scrollTop: $("#" + hashValue).offset().top
						}, 2000);
					}
				});
				</script>
				<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
				<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/marquee/jquery.marquee.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/video.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/youtube.min.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/video-js/vimeo.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}js/app.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
				<script type="text/javascript" src="{$itauditmachine_path}custom-view-js-func.js"></script>
				{$advance_uploader_js}
				{$calendar_init}
				{$signature_pad_init}
				{$logic_js}
				{$auto_height_js}
				{$custom_script_js}
				{$under_audit_js}
				</head>
				<body id="main_body" class="{$container_class}">
					<div id="form_container" class="{$form_container_class}">
						<h1><a>{$form->name}</a></h1>
						<form id="form_{$form->id}" data-selected_entity_id="{$_SESSION['la_client_entity_id']}" entity_id="{$form->form_for_selected_company}" data-selected_entry_id="{$entry_id}" class="itauditm {$form->label_alignment}" {$form_enc_type} method="post" data-highlightcolor="{$field_highlight_color}" action="#main_body" data-useremail="{$user_email}" data-formid="{$form->id}" data-domain="{$_SERVER['HTTP_HOST']}">
						<div style="display:none;">
							<input type="hidden" name="post_csrf_token" value="{$post_csrf_token}" />
						</div>
							{$form_desc_div}
							{$socket_connection_error_html}
							<ul {$ul_class} style="background-color:{$page_background}">
							{$pagination_header}
							{$form->error_message}
							{$form_resume_markup_top}
							{$button_markup_top}
							{$switch_column_btn}
							{$payment_total_markup_top}
							{$all_element_markup}
							{$custom_element}
							{$payment_total_markup_bottom}
							{$form_resume_markup}
							{$button_markup}
							{$button_escape}
							</ul>
						</form>	
						<div id="form_footer">
							{$powered_by_markup}
						</div>
					</div>
					<div id="processing-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100; z-index: 1000;">
						<div style="font-size: 140%; text-align: center; vertical-align: middle; position: absolute; top: 30%; left: 37%; color: black; background-color: white; padding: 1rem 0rem 2rem; width: 32rem; border-radius: 0.5rem;">
							Saving your entries and generating your output document(s).<br>
							This may take up to 5 minutes to complete the process.<br>
							<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
							<p style="font-weight: bold;">Please do not close your browser. Thank you!</p>
						</div>
					</div>
					{$under_audit_dialog}
					{$document_preview}
					{$element_id_auto_scroll_js}
					{$element_id_auto_scroll_css}
					{$chatbot}
					{$primary_entity_not_selected}
					<script type="text/javascript">
						$(document).ready(function(){
							{$textarea_socket_logic}
						});
					</script>
EOT;
		}

		return $form_markup;
	}
    
	//display the form within the form builder page
	function la_display_raw_form($dbh,$form_id){
		
		global $la_lang;		
		
		//get form properties data
		$query 	= "select 
						 form_name,
						 form_description,
						 form_label_alignment,
						 form_page_total,
						 form_lastpage_title,
						 form_submit_primary_text,
						 form_submit_secondary_text,
						 form_submit_primary_img,
						 form_submit_secondary_img,
						 form_submit_use_image,
						 form_pagination_type,
						 form_for_selected_company
				     from 
				     	 ".LA_TABLE_PREFIX."forms 
				    where 
				    	 form_id = ?";
		$params = array($form_id);
	
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
	
		$form = new stdClass();
		
		$form->id 				= $form_id;
		$form->name 			= $row['form_name'];
		$form->description 		= $row['form_description'];
		$form->label_alignment 	= $row['form_label_alignment'];
		$form->page_total 		= $row['form_page_total'];
		$form->lastpage_title 	= $row['form_lastpage_title'];
		$form->submit_primary_text 	 = $row['form_submit_primary_text'];
		$form->submit_secondary_text = $row['form_submit_secondary_text'];
		$form->submit_primary_img 	 = $row['form_submit_primary_img'];
		$form->submit_secondary_img  = $row['form_submit_secondary_img'];
		$form->submit_use_image  	 = (int) $row['form_submit_use_image'];
		$form->pagination_type		 = $row['form_pagination_type'];
		$form->form_for_selected_company		 = $row['form_for_selected_company'];
		
		$matrix_elements = array();
		
		//get elements data
		//get element options first and store it into array
		$query = "select 
						element_id,
						option_id,
						`position`,
						`option`,
						option_is_default 
				    from 
				    	".LA_TABLE_PREFIX."element_options 
				   where 
				   		form_id = ? and live = 1 
				order by 
						element_id asc,`position` asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			$options_lookup[$element_id][$option_id]['position'] 		  = $row['position'];
			$options_lookup[$element_id][$option_id]['option'] 			  = $row['option'];
			$options_lookup[$element_id][$option_id]['option_is_default'] = $row['option_is_default'];
		}
	
		
		//get elements data
		$element = array();
		$query = "select 
						element_id,
						element_title,
						element_guidelines,
						element_size,
						element_is_required,
						element_is_unique,
						element_is_private,
						element_type,
						element_position,
						element_default_value,
						element_constraint,
						element_choice_has_other,
						element_choice_other_label,
						element_choice_columns,
						element_time_showsecond, 
						element_time_24hour,
						element_address_hideline2,
						element_address_us_only,
						element_date_enable_range,
						element_date_range_min,
						element_date_range_max,
						element_date_enable_selection_limit,
						element_date_selection_max,
						element_date_disable_past_future,
						element_date_past_future,
						element_date_disable_weekend,
						element_date_disable_specific,
						element_date_disabled_list,
						element_file_enable_type_limit,
						element_file_block_or_allow,
						element_file_type_list,
						element_file_as_attachment,
						element_file_enable_advance,
						element_file_auto_upload,
						element_file_enable_multi_upload,
						element_file_max_selection,
						element_file_enable_size_limit,
						element_file_size_max,
						element_submit_use_image,
						element_submit_primary_text,
						element_submit_secondary_text,
						element_submit_primary_img,
						element_submit_secondary_img,
						element_page_title,
						element_page_number,
						element_matrix_allow_multiselect,
						element_matrix_parent_id,
						element_range_min,
						element_range_max,
						element_range_limit_by,
						element_section_display_in_email,
						element_section_enable_scroll,
						element_file_select_existing_files
					from 
						".LA_TABLE_PREFIX."form_elements 
				   where 
				   		form_id = ? and element_status='1'
				order by 
						element_position asc";
		$params = array($form_id);
	
		$sth = la_do_query($query,$params,$dbh);
		
		$j=0;
		$has_calendar = false;
		$has_guidelines = false;
		
		$page_title_array = array();
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			
			//lookup element options first
			$element_options = array();
			if(!empty($options_lookup[$element_id])){
				
				$i=0;
				foreach ($options_lookup[$element_id] as $option_id=>$data){
					$element_options[$i] = new stdClass();
					$element_options[$i]->id 		 = $option_id;
					$element_options[$i]->option 	 = $data['option'];
					$element_options[$i]->is_default = $data['option_is_default'];
					$element_options[$i]->is_db_live = 1;
					
					$i++;
				}
			}
			
			//populate elements
			$element[$j] = new stdClass();
			$element[$j]->title 		= nl2br($row['element_title']);
			
			
			$element[$j]->guidelines 	= $row['element_guidelines'];
			
			if(!empty($row['element_guidelines']) && ($row['element_type'] != 'section') && ($row['element_type'] != 'matrix')){
				$has_guidelines = true;
			}
			
			$element[$j]->size 			= $row['element_size'];
			$element[$j]->default_value = $row['element_default_value'];
			$element[$j]->is_required 	= $row['element_is_required'];
			$element[$j]->is_unique 	= $row['element_is_unique'];
			$element[$j]->is_private 	= $row['element_is_private'];
			$element[$j]->type 			= $row['element_type'];
			$element[$j]->position 		= $row['element_position'];
			$element[$j]->id 			= $row['element_id'];
			$element[$j]->is_db_live 	 = 1;
			$element[$j]->is_design_mode = true;
			$element[$j]->choice_has_other   = (int) $row['element_choice_has_other'];
			$element[$j]->choice_other_label = $row['element_choice_other_label'];
			$element[$j]->choice_columns   	 = (int) $row['element_choice_columns'];
			$element[$j]->time_showsecond	 = (int) $row['element_time_showsecond'];
			$element[$j]->time_24hour	 	 = (int) $row['element_time_24hour'];
			$element[$j]->address_hideline2	 = (int) $row['element_address_hideline2'];
			$element[$j]->address_us_only	 = (int) $row['element_address_us_only'];
			$element[$j]->date_enable_range	 = (int) $row['element_date_enable_range'];
			$element[$j]->date_range_min	 = $row['element_date_range_min'];
			$element[$j]->date_range_max	 = $row['element_date_range_max'];
			$element[$j]->date_enable_selection_limit	= (int) $row['element_date_enable_selection_limit'];
			$element[$j]->date_selection_max	 		= (int) $row['element_date_selection_max'];
			$element[$j]->date_disable_past_future	 	= (int) $row['element_date_disable_past_future'];
			$element[$j]->date_past_future	 			= $row['element_date_past_future'];
			$element[$j]->date_disable_weekend	 		= (int) $row['element_date_disable_weekend'];
			$element[$j]->date_disable_specific	 		= (int) $row['element_date_disable_specific'];
			$element[$j]->date_disabled_list	 		= $row['element_date_disabled_list'];
			$element[$j]->file_enable_type_limit	 	= (int) $row['element_file_enable_type_limit'];						
			$element[$j]->file_block_or_allow	 		= $row['element_file_block_or_allow'];
			$element[$j]->file_type_list	 			= $row['element_file_type_list'];
			$element[$j]->file_as_attachment	 		= (int) $row['element_file_as_attachment'];	
			$element[$j]->file_enable_advance	 		= (int) $row['element_file_enable_advance'];
			$element[$j]->file_auto_upload	 			= (int) $row['element_file_auto_upload'];
			$element[$j]->file_enable_multi_upload	 	= (int) $row['element_file_enable_multi_upload'];
			$element[$j]->file_max_selection	 		= (int) $row['element_file_max_selection'];
			$element[$j]->file_enable_size_limit	 	= (int) $row['element_file_enable_size_limit'];
			$element[$j]->file_size_max	 				= (int) $row['element_file_size_max'];
			$element[$j]->submit_use_image	 			= (int) $row['element_submit_use_image'];
			$element[$j]->submit_primary_text	 		= $row['element_submit_primary_text'];
			$element[$j]->submit_secondary_text	 		= $row['element_submit_secondary_text'];
			$element[$j]->submit_primary_img	 		= $row['element_submit_primary_img'];
			$element[$j]->submit_secondary_img	 		= $row['element_submit_secondary_img'];
			$element[$j]->page_title	 				= $row['element_page_title'];
			$element[$j]->page_number	 				= (int) $row['element_page_number'];
			$element[$j]->page_total	 				= $form->page_total;
			$element[$j]->matrix_allow_multiselect	 	= (int) $row['element_matrix_allow_multiselect'];
			$element[$j]->matrix_parent_id	 			= (int) $row['element_matrix_parent_id'];
			$element[$j]->range_min	 					= $row['element_range_min'];
			$element[$j]->range_max	 					= $row['element_range_max'];
			$element[$j]->range_limit_by	 			= $row['element_range_limit_by'];
			$element[$j]->section_display_in_email	 	= (int) $row['element_section_display_in_email'];
			$element[$j]->section_enable_scroll	 		= (int) $row['element_section_enable_scroll'];
			$element[$j]->file_select_existing_files	= (int) $row['element_file_select_existing_files'];

			$element[$j]->constraint 	= $row['element_constraint'];
			if(!empty($element_options)){
				$element[$j]->options 	= $element_options;
			}else{
				$element[$j]->options 	= '';
			}
			
			if($row['element_type'] == 'page_break'){
				$page_title_array[] = $row['element_page_title'];
			}
			
			//if the element is a matrix field and not the parent, store the data into a lookup array for later use when rendering the markup
			if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){
				
				$parent_id 	 = $row['element_matrix_parent_id'];
				$el_position = $row['element_position'];
				$matrix_elements[$parent_id][$el_position]['title'] = $element[$j]->title; 
				$matrix_elements[$parent_id][$el_position]['id'] 	= $element[$j]->id; 
				
				$matrix_child_option_id = '';
				foreach($element_options as $value){
					$matrix_child_option_id .= $value->id.',';
				}
				$matrix_child_option_id = rtrim($matrix_child_option_id,',');
				$matrix_elements[$parent_id][$el_position]['children_option_id'] = $matrix_child_option_id; 
				
				//remove it from the main element array
				$element[$j] = array();
				unset($element[$j]);
				$j--;
			}
			
			$j++;
		}

		
		
		
		//generate html markup for each element
		$all_element_markup = '';
		foreach ($element as $element_data){
			//if this is matrix field, build the children data from $matrix_elements array
			if($element_data->type == 'matrix'){
				$element_data->matrix_children = $matrix_elements[$element_data->id];
			}
			$all_element_markup .= call_user_func('la_display_'.$element_data->type,$element_data);
		}
		
		if(empty($all_element_markup)){
			$all_element_markup = '<li id="li_dummy">&nbsp;</li>';
		}	
				

		if(!empty($form->name) || !empty($form->description)){
			$form->description = nl2br($form->description);
			$form_desc_div =<<<EOT
		<div id="form_header" class="form_description">
			<h2 id="form_header_title">{$form->name}</h2>
			<p id="form_header_desc">{$form->description}</p>
		</div>
EOT;
		}else{
			$form_desc_div =<<<EOT
				<div id="form_header" class="form_description">
					<h2 id="form_header_title"><i>This form has no title</i></h2>
					<p id="form_header_desc"></p>
				</div>
EOT;
		}

		if($has_guidelines){
			$container_class = "integrated";
		}else{
			$container_class = "integrated no_guidelines";
		}
		
		
		//if the form has multiple pages
		//display the pagination header
		if($form->page_total > 1){
			
			
			//build pagination header based on the selected type. possible values:
			//steps - display multi steps progress
			//percentage - display progress bar with percentage
			//disabled - disabled
			
			if($form->pagination_type == 'steps'){
				
				$page_titles_markup = '';
				
				$i=1;
				foreach ($page_title_array as $page_title){
					if($i==1){
						$ap_tp_num_active = ' ap_tp_num_active';
						$ap_tp_text_active = ' ap_tp_text_active';
					}else{
						$ap_tp_num_active = '';
						$ap_tp_text_active = '';
					}
					
					$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num'.$ap_tp_num_active.'">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text'.$ap_tp_text_active.'">'.$page_title.'</span></td><td align="center" class="ap_tp_arrow">&gt;</td>'."\n";
					$i++;
				}
				
				//add the last page title into the pagination header markup
				$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$form->lastpage_title.'</span></td>';
				
			
				$pagination_header =<<<EOT
			<li id="pagination_header" class="li_pagination" title="Click to edit">
			 <table class="ap_table_pagination" width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr> 
			  	{$page_titles_markup}
			  </tr>
			</table>
			</li>
EOT;
			}else if($form->pagination_type == 'percentage'){
				$page_total = count($page_title_array) + 1;
				$percent_value = round((1/$page_total) * 100);
				$pagination_header =<<<EOT
			<li id="pagination_header" class="li_pagination" title="Click to edit">
			    <h3 id="page_title_1">Page 1 of {$page_total} - {$page_title_array[0]}</h3>
				<div class="la_progress_container">          
			    	<div id="la_progress_percentage" class="la_progress_value" style="width: {$percent_value}%"><span>{$percent_value}%</span></div>
				</div>
			</li>
EOT;
			}else{
			
				$pagination_header =<<<EOT
			<li id="pagination_header" class="li_pagination" title="Click to edit">
			    <h3 class="no_header">Pagination Header Disabled</h3>
			</li>
EOT;
			}

			if($form->submit_use_image == 1){
				$btn_class = ' hide';
				$image_class = '';
			}else{
				$btn_class = '';
				$image_class = ' hide';
			}  	
			
			if(empty($form->submit_primary_img)){
				$form->submit_primary_img = 'images/empty.gif';
			}
			
			if(empty($form->submit_secondary_img)){
				$form->submit_secondary_img = 'images/empty.gif';
			}

			$pagination_footer =<<<EOT
		<li title="Click to edit" class="page_break synched" id="li_lastpage">
			<div>
				<table width="100%" cellspacing="0" cellpadding="0" border="0" class="ap_table_pagination">
					<tbody><tr>
						<td align="left" style="vertical-align: bottom;">
							<input type="submit" class="btn_primary btn_submit{$btn_class}" name="btn_submit_lastpage" id="btn_submit_lastpage" value="{$form->submit_primary_text}" disabled="disabled">
							<input type="submit" class="btn_secondary btn_submit{$btn_class}" name="btn_prev_lastpage" id="btn_prev_lastpage" value="{$form->submit_secondary_text}" disabled="disabled">
							<input type="image" src="{$form->submit_primary_img}" class="img_primary img_submit{$image_class}" alt="Submit" name="img_submit_lastpage" id="img_submit_lastpage" value="Submit" disabled="disabled">
							<input type="image" src="{$form->submit_secondary_img}" class="img_secondary img_submit{$image_class}" alt="Previous" name="img_prev_lastpage" id="img_prev_lastpage" value="Previous" disabled="disabled">
						</td> 
						<td width="75px" align="center" style="vertical-align: top;">
							<span class="ap_tp_num" name="pagenum_lastpage" id="pagenum_lastpage">{$form->page_total}</span>
							<span class="ap_tp_text" name="pagetotal_lastpage" id="pagetotal_lastpage">Page {$form->page_total} of {$form->page_total}</span>
						</td>
					</tr>
				</tbody></table>
			</div>
		</li>
EOT;
		}

			
		$form_markup =<<<EOT
<div id="main_body" class="{$container_class}">
		
	<div id="form_container">
	
		<h1><a>{$form->name}</a></h1>
		<form id="form_builder_preview"  entity_id="{$form->form_for_selected_company}" class="itauditm {$form->label_alignment}" method="post" action="#main_body">
			{$form_desc_div}				
			<ul {$ul_class} id="form_builder_sortable" title="Click field to edit. Drag to reorder.">
			{$pagination_header}	
			{$all_element_markup}
			{$pagination_footer}
			</ul>
		</form>	
	</div>
</div>
EOT;
		return $form_markup;
		
	}
	
	function la_display_success($dbh,$form_id,$company_id,$entry_id,$form_params=array(),$score=0)
    {
		global $la_lang;
		
		if(!empty($form_params['integration_method'])){
			$integration_method = $form_params['integration_method'];
		}else{
			$integration_method = '';
		}

		if(!empty($form_params['itauditmachine_path'])){
			$itauditmachine_path = $form_params['itauditmachine_path'];
		}else{
			$itauditmachine_path = '';
		}



		$la_settings = la_get_settings($dbh);
		
		//get form properties data
		$query 	= "select 
						  form_success_message,
						  form_has_css,
						  form_name,
						  form_theme_id,
						  form_language,
						  form_custom_script_enable,
						  form_custom_script_url,
						  form_for_selected_company
				     from 
				     	 ".LA_TABLE_PREFIX."forms 
				    where 
				    	 form_id=?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
	
		$form = new stdClass();
		
		$form->id 				= $form_id;
		$form->success_message  = $row['form_success_message'];
		$form->has_css 			= $row['form_has_css'];
		$form->name 			= $row['form_name'];
		$form->theme_id 		= $row['form_theme_id'];
		$form->language 		= trim($row['form_language']);
		$form->form_for_selected_company 		= trim($row['form_for_selected_company']);

		$form->custom_script_enable = (int) $row['form_custom_script_enable'];
		$form->custom_script_url 	= $row['form_custom_script_url'];
		
		if(!empty($form->language)){
			la_set_language($form->language);
		}

		//parse success messages with template variables
		if(!empty($_SESSION['la_success_entry_id']) && empty($_SESSION['la_form_resume_url'][$form_id])){
			$form->success_message = la_parse_template_variables($dbh,$form_id,$company_id,$entry_id,$form->success_message);
		}
		//check for specific form css, if any, use it instead
		if($form->has_css){
			$css_dir = $la_settings['data_dir']."/form_{$form_id}/css/";
		}
		
		//if this form is using custom theme
		if(!empty($form->theme_id)){
			//get the field highlight color for the particular theme
			$query = "SELECT 
							highlight_bg_type,
							highlight_bg_color,
							form_shadow_style,
							form_shadow_size,
							form_shadow_brightness,
							form_button_type,
							form_button_text,
							form_button_image,
							theme_has_css  
						FROM 
							".LA_TABLE_PREFIX."form_themes 
					   WHERE 
					   		theme_id = ?";
			$params = array($form->theme_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			$form_shadow_style 		= $row['form_shadow_style'];
			$form_shadow_size 		= $row['form_shadow_size'];
			$form_shadow_brightness = $row['form_shadow_brightness'];
			$theme_has_css = (int) $row['theme_has_css'];
			
			//if the theme has css file, make sure to refer to that file
			//otherwise, generate the css dynamically
			
			if(!empty($theme_has_css)){
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.$la_settings['data_dir'].'/themes/theme_'.$form->theme_id.'.css" media="all" />';
			}else{
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.'css_theme.php?theme_id='.$form->theme_id.'" media="all" />';
			}
			
			if($row['highlight_bg_type'] == 'color'){
				$field_highlight_color = $row['highlight_bg_color'];
			}else{ 
				//if the field highlight is using pattern instead of color, set the color to empty string
				$field_highlight_color = ''; 
			}
			
			//get the css link for the fonts
			$font_css_markup = la_theme_get_fonts_link($dbh,$form->theme_id);
			
			//get the form shadow classes
			if(!empty($form_shadow_style) && ($form_shadow_style != 'disabled')){
				preg_match_all("/[A-Z]/",$form_shadow_style,$prefix_matches);
				//this regex simply get the capital characters of the shadow style name
				//example: RightPerspectiveShadow result to RPS and then being sliced to RP
				$form_shadow_prefix_code = substr(implode("",$prefix_matches[0]),0,-1);
				
				$form_shadow_size_class  = $form_shadow_prefix_code.ucfirst($form_shadow_size);
				$form_shadow_brightness_class = $form_shadow_prefix_code.ucfirst($form_shadow_brightness);

				if(empty($integration_method)){ //only display shadow if the form is not being embedded using any method
					$form_container_class = $form_shadow_style.' '.$form_shadow_size_class.' '.$form_shadow_brightness_class;
				}
			}
			
			
			
		}else{ //if the form doesn't have any theme being applied
			$field_highlight_color = '#FFF7C0';
			
			if(empty($integration_method)){
				$form_container_class = ''; //default shadow as of v4.2 is no-shadow
			}else{
				$form_container_class = ''; //dont show any shadow when the form being embedded
			}
		}
		
		
		/*if(!empty($_SESSION['la_form_resume_url'][$form_id])){

			$resume_success_title = $la_lang['resume_success_title'];
			$resume_success_content = sprintf($la_lang['resume_success_content'],$_SESSION['la_form_resume_url'][$form_id]);

			$success_markup = <<<EOT
			<h2>{$resume_success_title}</h2>
			<h3>{$resume_success_content}</h3>
EOT;
		}else{
			$success_markup = "<h2>{$form->success_message}</h2>";		
		}*/
        
        if(isset($_REQUEST['resume_form']) && $_REQUEST['resume_form'] == 1){

			$resume_success_title = $la_lang['resume_success_title'];
			$resume_success_content = sprintf($la_lang['resume_success_content'],$_SESSION['la_form_resume_url'][$form_id]);

			$success_markup = <<<EOT
			<h2>{$resume_success_title}</h2>
			<h3>{$resume_success_content}</h3>
EOT;
		}else{
			$success_markup = "<h2>{$form->success_message}</h2>";		
		}
        

		if(empty($la_settings['disable_itauditmachine_link'])){
			$powered_by_markup = 'Powered by ITAM, the <a href="http://www.lazarusalliance.com" target="_blank">IT Audit Machine</a>';
		}else{
			$powered_by_markup = '';
		}
		
		//load custom javascript if enabled
		$custom_script_js = '';
		if(!empty($form->custom_script_enable) && !empty($form->custom_script_url)){
			$custom_script_js = '<script type="text/javascript" src="'.$form->custom_script_url.'"></script>';
		}

		$jquery_url = $itauditmachine_path.'js/jquery.min.js';

		if($integration_method == 'php'){
			$form_markup = <<<EOT
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
{$theme_css_link}
{$font_css_markup}
{$custom_script_js}
<style>
html{
	background: none repeat scroll 0 0 transparent;
}
</style>

<div id="main_body" class="integrated">
	<div id="form_container">
		<h1><a>Continuum GRC IT Audit Machine</a></h1>
		<div class="form_success">
			{$success_markup}
		</div>
		<div id="form_footer" class="success">
			{$powered_by_markup}
		</div>		
	</div>
	
</div>
EOT;

		}else{
	
			if($integration_method == 'iframe'){
				$embed_class = 'class="embed"';
				$auto_height_js =<<<EOT
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.ba-postmessage.min.js"></script>
<script type="text/javascript">
    $(function(){
    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
    });
</script>
EOT;
			}else{
				$embed_class = '';
			}
            
            $score_html = '';
            if($score > 0){
				$score_html = '<div><h4>Your score is '.$score.'</h4></div><div><a href="my_report.php">Click here</a> to go to view Forms Score</div>';
			}
			
			$form_markup = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html {$embed_class} xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>{$form->name}</title>
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
{$theme_css_link}
{$font_css_markup}
{$auto_height_js}
{$custom_script_js}
</head>
<body id="main_body">
	<div id="form_container" class="{$form_container_class}">
	
		<h1><a>Continuum GRC IT Audit Machine</a></h1>
		<div class="form_success">
			{$success_markup}
            {$score_html}
            <div><a href="client_account.php">Click here</a> to go to My Forms</div>
		</div>
		<div id="form_footer" class="success">
			{$powered_by_markup}
		</div>		
	</div>
</body>
</html>
EOT;
		}

		return $form_markup;
	}
    
	//display form confirmation page
	function la_display_form_review($dbh,$form_id,$record_id,$from_page_num,$form_params=array()){
    	
		global $la_lang;
        $casecade_form_id_arr = array();
       
		if(!empty($form_params['integration_method'])){
			$integration_method = $form_params['integration_method'];
		}else{
			$integration_method = '';
		}
		
		if(!empty($form_params['itauditmachine_path'])){
			$itauditmachine_path = $form_params['itauditmachine_path'];
		}else{
			$itauditmachine_path = '';
		}

		if(!empty($form_params['itauditmachine_data_path'])){
			$itauditmachine_data_path = $form_params['itauditmachine_data_path'];
		}else{
			$itauditmachine_data_path = '';
		}
		
		$la_settings = la_get_settings($dbh);
        
        $form_default_element = getElementData(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'column' => 'element_default_value',
		));		
		
		foreach ($form_default_element as $element_data){
			array_push($casecade_form_id_arr, trim($element_data['element_default_value']));
		}
		
		//get form properties data
		$query 	= "select 
						  form_name,
						  form_has_css,
						  form_redirect,
						  form_review_primary_text,
						  form_review_secondary_text,
						  form_review_primary_img,
						  form_review_secondary_img,
						  form_review_use_image,
						  form_review_title,
						  form_review_description,
						  form_resume_enable,
						  form_page_total,
						  form_lastpage_title,
						  form_pagination_type,
						  form_theme_id,
						  payment_show_total,
						  payment_total_location,
						  payment_enable_merchant,
						  payment_currency,
						  payment_price_type,
						  payment_price_amount,
						  payment_enable_discount,
						  payment_discount_type,
						  payment_discount_amount,
						  payment_discount_element_id,
						  payment_enable_tax,
					 	  payment_tax_rate,
					 	  logic_field_enable,
					 	  form_custom_script_enable,
						  form_custom_script_url,
						  form_for_selected_company
				     from 
				     	 ".LA_TABLE_PREFIX."forms 
				    where 
				    	 form_id=?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$form_has_css 			= $row['form_has_css'];
		$form_redirect			= $row['form_redirect'];
		$form_review_primary_text 	 = $row['form_review_primary_text'];
		$form_review_secondary_text  = $row['form_review_secondary_text'];
		$form_review_primary_img 	 = $row['form_review_primary_img'];
		$form_review_secondary_img   = $row['form_review_secondary_img'];
		$form_review_use_image  	 = (int) $row['form_review_use_image'];
		$form_review_title			 = $row['form_review_title'];
		$form_review_description	 = $row['form_review_description'];
		$form_page_total 			 = $row['form_page_total'];
		$form_lastpage_title 		 = $row['form_lastpage_title'];
		$form_pagination_type		 = $row['form_pagination_type'];
		$form_name					 = htmlspecialchars($row['form_name'],ENT_QUOTES);
		$form_theme_id				 = $row['form_theme_id'];
		$form_resume_enable  	 	 = (int) $row['form_resume_enable'];
		$logic_field_enable 		 = (int) $row['logic_field_enable'];

		$payment_show_total	 		 = (int) $row['payment_show_total'];
		$payment_total_location 	 = $row['payment_total_location'];
		$payment_enable_merchant 	 = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		$payment_currency 	   		 = $row['payment_currency'];
		$payment_price_type 	     = $row['payment_price_type'];
		$payment_price_amount    	 = $row['payment_price_amount'];

		$payment_enable_discount 		= (int) $row['payment_enable_discount'];
		$payment_discount_type 	 		= $row['payment_discount_type'];
		$payment_discount_amount 		= (float) $row['payment_discount_amount'];
		$payment_discount_element_id 	= (int) $row['payment_discount_element_id'];

		$payment_enable_tax 		 	= (int) $row['payment_enable_tax'];
		$payment_tax_rate 				= (float) $row['payment_tax_rate'];

		$form_custom_script_enable 		= (int) $row['form_custom_script_enable'];
		$form_custom_script_url 		= $row['form_custom_script_url'];
		$form_for_selected_company      = $row['form_for_selected_company'];
		
        $param['review_mode']    	  = true;
                
		//prepare entry data for previewing
		$param['strip_download_link'] = true;
		$param['show_attach_image']   = true;
		$param['itauditmachine_data_path'] = $itauditmachine_data_path;
		$param['itauditmachine_path'] = $itauditmachine_path;
		//echo '12345'; exit;
        
        $use_review_table = false;
        //echo "{$form_id}-{$record_id}";
		$entry_details = la_get_entry_values($dbh,$form_id,$record_id,$use_review_table,$param);
        //echo '<pre>';print_r($entry_details);echo '</pre>';
		
		//if logic is enable, get hidden elements
		//we'll need it to hide section break
		if($logic_field_enable){
			$entry_values = la_get_entry_values($dbh,$form_id,$record_id,true);
			foreach ($entry_values as $element_name => $value) {
				$input_data[$element_name] = $value['default_value'];
			}

			$hidden_elements = array();
			for($i=1;$i<=$form_page_total;$i++){
				$current_page_hidden_elements = array();
				$current_page_hidden_elements = la_get_hidden_elements($dbh,$form_id,$i,$input_data);
				
				$hidden_elements += $current_page_hidden_elements; //use '+' so that the index won't get lost
			}
		}
		
		$entry_data = '<table id="itauditmachine_review_table" width="100%" border="0" cellspacing="0" cellpadding="0"><tbody>'."\n";
		
		$toggle = false;
        
		foreach ($entry_details as $data){
			//0 should be displayed, empty string don't
			if((empty($data['value']) || $data['value'] == '&nbsp;') && $data['value'] !== 0 && $data['value'] !== '0' && $data['element_type'] !== 'section'){
				continue;
			}	

			//don't display page break within review page
			if($data['label'] == 'la_page_break' && $data['value'] == 'la_page_break'){
				continue;
			}

			if($toggle){
				$toggle = false;
				$row_style = 'class="alt"';
			}else{
				$toggle = true;
				$row_style = '';
			}	
			
			if($data['element_type'] == 'section') {
				
				//if this section break is hidden due to logic, don't display it
				if(!empty($hidden_elements) && !empty($hidden_elements[$data['element_id']])){
					continue;
				}

				if(!empty($data['label']) && !empty($data['value']) && ($data['value'] != '&nbsp;')){
					$section_separator = '<br/>';
				}else{
					$section_separator = '';
				}

				$section_break_content = '<span class="la_section_title">'.nl2br($data['label']).'</span>'.$section_separator.'<span class="la_section_content">'.nl2br($data['value']).'</span>';

				$entry_data .= "<tr>\n";
				$entry_data .= "<td class=\"la_review_section_break\" width=\"100%\" colspan=\"2\">".$section_break_content."</td>\n";
				$entry_data .= "</tr>\n";
			}else if($data['element_type'] == 'signature') {
				$element_id = $data['element_id'];

				if($data['element_size'] == 'small'){
					$canvas_height = 70;
				}else if($data['element_size'] == 'medium'){
					$canvas_height = 130;
				}else{
					$canvas_height = 260;
				}

				$signature_markup = <<<EOT
							<div class="la_sigpad_view" id="la_sigpad_{$element_id}">
								<canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
							</div>
							<script type="text/javascript">
								$(function(){
									var sigpad_options_{$element_id} = {
										               drawOnly : true,
										               displayOnly: true,
										               bgColour: '#fff',
										               penColour: '#000',
										               validateFields: false
									};
									var sigpad_data_{$element_id} = {$data['value']};
									$('#la_sigpad_{$element_id}').signaturePad(sigpad_options_{$element_id}).regenerate(sigpad_data_{$element_id});
								});
							</script>
EOT;

				$entry_data .= "<tr {$row_style}>\n";
				$entry_data .= "<td class=\"la_review_label\" width=\"40%\" style=\"vertical-align: top\">{$data['label']}</td>\n";
				$entry_data .= "<td class=\"la_review_value\" width=\"60%\">{$signature_markup}</td>\n";
				$entry_data .= "</tr>\n";
			}else{
	  			$entry_data .= "<tr {$row_style}>\n";
	  	    	$entry_data .= "<td class=\"la_review_label\" width=\"40%\">{$data['label']}</td>\n";
	  			$entry_data .= "<td class=\"la_review_value\" width=\"60%\">".nl2br($data['value'])."</td>\n";
	  			$entry_data .= "</tr>\n";
  			}
 		}   	
		 	
   	    $entry_data .= '</tbody></table>';

		//check for specific form css, if any, use it instead
		if($form_has_css){
			$css_dir = $la_settings['data_dir']."/form_{$form_id}/css/";
		}
		
		if($integration_method == 'iframe'){
			$embed_class = 'class="embed"';
		}		
		
		//if the form has multiple pages
		//display the pagination header
		if($form_page_total > 1){
			
			//build pagination header based on the selected type. possible values:
			//steps - display multi steps progress
			//percentage - display progress bar with percentage
			//disabled - disabled
			
			$page_breaks_data = array();
			$page_title_array = array();
			
			//get page titles
			$query = "SELECT 
							element_page_title
						FROM 
							".LA_TABLE_PREFIX."form_elements
					   WHERE
							form_id = ? and element_status = 1 and element_type = 'page_break'
					ORDER BY 
					   		element_page_number asc";
			$params = array($form_id);
			
			$sth = la_do_query($query,$params,$dbh);
			while($row = la_do_fetch_result($sth)){
				$page_title_array[] = $row['element_page_title'];
			}
            
            if(count($casecade_form_id_arr)){
            	foreach($casecade_form_id_arr as $k => $v){
                	$params = array($v);
			
                    $sth = la_do_query($query,$params,$dbh);
                    while($row = la_do_fetch_result($sth)){
                        $page_title_array[] = $row['element_page_title'];
                    }
                }
            }
			
			if($form_pagination_type == 'steps'){
				
				$page_titles_markup = '';
				
				$i=1;
				foreach ($page_title_array as $page_title){
					$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$page_title.'</span></td><td align="center" class="ap_tp_arrow">&gt;</td>'."\n";
					$i++;
				}
				
				//add the last page title into the pagination header markup
				$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$form_lastpage_title.'</span></td>';
			
				$i++;
				$page_titles_markup .= '<td align="center" class="ap_tp_arrow">&gt;</td><td align="center"><span id="page_num_'.$i.'" class="ap_tp_num ap_tp_num_active">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text ap_tp_text_active">'.$form_review_title.'</span></td>';
				
				//if payment enabled, we need to add the pagination header
				if(!empty($payment_enable_merchant)){
					//$i++;
					//$page_titles_markup .= '<td align="center" class="ap_tp_arrow">&gt;</td><td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$la_lang['form_payment_header_title'].'</span></td>';
				}
				
				$pagination_header =<<<EOT
			<ul>
			<li id="pagination_header" class="li_pagination">
			 <table class="ap_table_pagination" width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr> 
			  	{$page_titles_markup}
			  </tr>
			</table>
			</li>
			</ul>
EOT;
			}
            else if($form_pagination_type == 'percentage'){
				
				//if(!empty($payment_enable_merchant)){
					//$page_total = count($page_title_array) + 3;
					//$current_page_number = $page_total - 1;
					//$percent_value = round(($current_page_number/$page_total) * 100);
				//}else{
					$page_total = count($page_title_array) + 2;
					$current_page_number = $page_total;
					$percent_value = 99; //it's not make sense to display 100% when the form is not really submitted yet
				//}
				
				$page_number_title = sprintf($la_lang['page_title'],$current_page_number,$page_total);
				$pagination_header =<<<EOT
			<ul>
				<li id="pagination_header" class="li_pagination" title="Click to edit">
			    <h3 id="page_title_{$page_total}">{$page_number_title}</h3>
				<div class="la_progress_container">          
			    	<div id="la_progress_percentage" class="la_progress_value" style="width: {$percent_value}%"><span>{$percent_value}%</span></div>
				</div>
				</li>
			</ul>
EOT;
			}
            else{			
				$pagination_header = '';
			}
			
		}
		
		//build the button markup (image or text)
		if(!empty($form_review_use_image)){
			$button_markup =<<<EOT
<input id="review_submit" class="submit_img_primary" type="image" name="review_submit" alt="{$form_review_primary_text}" src="{$form_review_primary_img}" />
<input id="review_back" class="submit_img_secondary" type="image" name="review_back" alt="{$form_review_secondary_text}" src="{$form_review_secondary_img}" />
EOT;
		}
        else{
			$button_markup =<<<EOT
<input id="review_submit" class="button_text btn_primary" type="submit" name="review_submit" value="{$form_review_primary_text}" />
<input id="review_back" class="button_text btn_secondary" type="submit" name="review_back" value="{$form_review_secondary_text}" />
EOT;
		}

		//if this form is using custom theme
		if(!empty($form_theme_id)){
			//get the field highlight color for the particular theme
			$query = "SELECT 
							highlight_bg_type,
							highlight_bg_color,
							form_shadow_style,
							form_shadow_size,
							form_shadow_brightness,
							form_button_type,
							form_button_text,
							form_button_image,
							theme_has_css  
						FROM 
							".LA_TABLE_PREFIX."form_themes 
					   WHERE 
					   		theme_id = ?";
			$params = array($form_theme_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			$form_shadow_style 		= $row['form_shadow_style'];
			$form_shadow_size 		= $row['form_shadow_size'];
			$form_shadow_brightness = $row['form_shadow_brightness'];
			$theme_has_css = (int) $row['theme_has_css'];
			
			//if the theme has css file, make sure to refer to that file
			//otherwise, generate the css dynamically
			
			if(!empty($theme_has_css)){
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.$la_settings['data_dir'].'/themes/theme_'.$form_theme_id.'.css" media="all" />';
			}else{
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.'css_theme.php?theme_id='.$form_theme_id.'" media="all" />';
			}
			
			if($row['highlight_bg_type'] == 'color'){
				$field_highlight_color = $row['highlight_bg_color'];
			}else{ 
				//if the field highlight is using pattern instead of color, set the color to empty string
				$field_highlight_color = ''; 
			}
			
			//get the css link for the fonts
			$font_css_markup = la_theme_get_fonts_link($dbh,$form_theme_id);
			
			//get the form shadow classes
			if(!empty($form_shadow_style) && ($form_shadow_style != 'disabled')){
				preg_match_all("/[A-Z]/",$form_shadow_style,$prefix_matches);
				//this regex simply get the capital characters of the shadow style name
				//example: RightPerspectiveShadow result to RPS and then being sliced to RP
				$form_shadow_prefix_code = substr(implode("",$prefix_matches[0]),0,-1);
				
				$form_shadow_size_class  = $form_shadow_prefix_code.ucfirst($form_shadow_size);
				$form_shadow_brightness_class = $form_shadow_prefix_code.ucfirst($form_shadow_brightness);

				if(empty($integration_method)){ //only display shadow if the form is not being embedded using any method
					$form_container_class = $form_shadow_style.' '.$form_shadow_size_class.' '.$form_shadow_brightness_class;
				}
			}
		}else{ //if the form doesn't have any theme being applied
			$field_highlight_color = '#FFF7C0';
			
			if(empty($integration_method)){
				$form_container_class = ''; //default shadow as of v4.2 is no-shadow
			}else{
				$form_container_class = ''; //dont show any shadow when the form being embedded
			}
		}

		//if the form has enabled merchant support and set the total payment to be displayed
		if(!empty($payment_enable_merchant) && !empty($payment_show_total)){
			
			$currency_symbol = '&#36;';
			
			switch($payment_currency){
				case 'USD' : $currency_symbol = '&#36;';break;
				case 'EUR' : $currency_symbol = '&#8364;';break;
				case 'GBP' : $currency_symbol = '&#163;';break;
				case 'AUD' : $currency_symbol = 'A&#36;';break;
				case 'CAD' : $currency_symbol = 'C&#36;';break;
				case 'JPY' : $currency_symbol = '&#165;';break;
				case 'THB' : $currency_symbol = '&#3647;';break;
				case 'HUF' : $currency_symbol = '&#70;&#116;';break;
				case 'CHF' : $currency_symbol = 'CHF';break;
				case 'CZK' : $currency_symbol = '&#75;&#269;';break;
				case 'SEK' : $currency_symbol = 'kr';break;
				case 'DKK' : $currency_symbol = 'kr';break;
				case 'NOK' : $currency_symbol = 'kr';break;
				case 'PHP' : $currency_symbol = '&#36;';break;
				case 'IDR' : $currency_symbol = 'Rp';break;
				case 'MYR' : $currency_symbol = 'RM';break;
				case 'ZAR' : $currency_symbol = 'R';break;
				case 'PLN' : $currency_symbol = '&#122;&#322;';break;
				case 'BRL' : $currency_symbol = 'R&#36;';break;
				case 'HKD' : $currency_symbol = 'HK&#36;';break;
				case 'MXN' : $currency_symbol = 'Mex&#36;';break;
				case 'TWD' : $currency_symbol = 'NT&#36;';break;
				case 'TRY' : $currency_symbol = 'TL';break;
				case 'NZD' : $currency_symbol = '&#36;';break;
				case 'SGD' : $currency_symbol = '&#36;';break;
			}
		
			
			$session_id = session_id();

			if($payment_price_type == 'variable'){
				$total_payment = (double) la_get_payment_total($dbh,$form_id,$session_id,0);
			}elseif ($payment_price_type == 'fixed') {
				$total_payment = $payment_price_amount;
			}

			$total_payment = sprintf("%.2f",$total_payment);

			//display tax info if enabled
			if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
				$tax_markup = "<h5>+{$la_lang['tax']} {$payment_tax_rate}&#37;</h5>";
			}

			//display discount info if applicable
			//the discount info can only being displayed when the form is having review enabled or a multipage form
			$is_discount_applicable = false;

			//if the discount element for the current session having any value, we can be certain that the discount code has been validated and applicable
			if(!empty($payment_enable_discount)){
				$query = "select element_{$payment_discount_element_id} coupon_element from ".LA_TABLE_PREFIX."form_{$form_id}_review where `id` = ?";
				$params = array($record_id);
				
				$sth = la_do_query($query,$params,$dbh);
				$row = la_do_fetch_result($sth);
				
				if(!empty($row['coupon_element'])){
					$is_discount_applicable = true;
				}
			}

			if($is_discount_applicable){
				if($payment_discount_type == 'percent_off'){
					$discount_markup = "<h5>-{$la_lang['discount']} {$payment_discount_amount}&#37;</h5>";
				}else{
					$discount_markup = "<h5>-{$la_lang['discount']} {$currency_symbol}{$payment_discount_amount}</h5>";
				}
			}
			
			if(!empty($tax_markup) || !empty($discount_markup)){
				$payment_extra_markup =<<<EOT
				<span class="total_extra">
					{$discount_markup}
					{$tax_markup}
				</span>
EOT;
			}

			$payment_total_markup = <<<EOT
				<ul><li class="total_payment la_review">
					<span>
						<h3>{$currency_symbol}<var>{$total_payment}</var></h3>
						<h5>{$la_lang['payment_total']}</h5>
					</span>
					{$payment_extra_markup}
				</li></ul>
EOT;
			
		}
		
		if(empty($la_settings['disable_itauditmachine_link'])){
			$powered_by_markup = 'Powered by ITAM, the <a href="http://www.lazarusalliance.com" target="_blank">IT Audit Machine</a>';
		}else{
			$powered_by_markup = '';
		}

		//load custom javascript if enabled
		$custom_script_js = '';
		if(!empty($form_custom_script_enable) && !empty($form_custom_script_url)){
			$custom_script_js = '<script type="text/javascript" src="'.$form_custom_script_url.'"></script>';
		}

		//check for any 'signature' field, if there is any, we need to include the javascript library to display the signature
		$query = "select 
						count(form_id) total_signature_field 
					from 
						".LA_TABLE_PREFIX."form_elements 
				   where 
				   		element_type = 'signature' and 
				   		element_status=1 and 
				   		form_id=?";
		$params = array($form_id);

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		if(!empty($row['total_signature_field'])){
			$has_signature_field = true;
		}else{
			$has_signature_field = true;
		}

		$self_address = htmlentities($_SERVER['PHP_SELF']); //prevent XSS
		$jquery_url	  = $itauditmachine_path.'js/jquery.min.js';
        

		if($integration_method == 'php'){

			if($has_signature_field){
				$signature_pad_init = '<!--[if lt IE 9]><script src="'.$itauditmachine_path.'js/signaturepad/flashcanvas.js"></script><![endif]-->'."\n".
									  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/jquery.signaturepad.min.js"></script>'."\n".
									  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/json2.min.js"></script>'."\n";
			}

			$form_markup = <<<EOT
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
{$signature_pad_init}
{$custom_script_js}
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
{$theme_css_link}
{$font_css_markup}
<style>
html{
	background: none repeat scroll 0 0 transparent;
}
</style>

<div id="main_body" class="integrated">
	<div id="form_container">
		<form id="form_{$form->id}"  entity_id="{$form->form_for_selected_company}" class="itauditm" method="post" action="{$self_address}">
		    <div class="form_description">
				<h2>{$form_review_title}</h2>
				<p>{$form_review_description}</p>
			</div>
			{$pagination_header}
			{$entry_data}
			<ul>
			{$payment_total_markup}
			<li id="li_buttons" class="buttons">
			    <input type="hidden" name="id" value="{$form_id}" />
			    <input type="hidden" name="la_page_from" value="{$from_page_num}" />
			    {$button_markup}
			</li>
			</ul>
		</form>		
	</div>
</div>
EOT;
		}
        else{
            $str = '';
            $str_address = '';
            $str_phone ='';
            $str_time = '';
            $str_name = '';
            $str_date = '';
            $str_price = '';
            $eid = '';
        	
            if(!empty($entry_details)){
            
            	$entry_detail = '<table border="0" cellspacing="0" cellpadding="0"><tbody>'."\n";
                
                // call recursive function to get entry details
            	$entry_detail .= generateReviewDetails(array('dbh' => $dbh, 'entry_details' => $entry_details, 'form_id' => $form_id, 'record_id' => $record_id, 'use_review_table' => $use_review_table, 'param' => $param));
                
                $entry_detail .= '</tbody></table>';
            }
           
			if($integration_method == 'iframe'){	
				$auto_height_js =<<<EOT
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.ba-postmessage.min.js"></script>
<script type="text/javascript">
    $(function(){
    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
    });
</script>
EOT;
			}

			if($has_signature_field){
					$signature_pad_init = '<!--[if lt IE 9]><script src="'.$itauditmachine_path.'js/signaturepad/flashcanvas.js"></script><![endif]-->'."\n".
										  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/jquery.signaturepad.min.js"></script>'."\n".
										  '<script type="text/javascript" src="'.$itauditmachine_path.'js/signaturepad/json2.min.js"></script>'."\n";
			}

			$form_markup = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html {$embed_class} xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>{$form_name}</title>
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
{$theme_css_link}
{$font_css_markup}
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
{$auto_height_js}
{$signature_pad_init}
{$custom_script_js}
</head>
<body id="main_body">
	
	<img id="top" src="{$itauditmachine_path}images/top.png" alt="" />
	<div id="form_container" class="{$form_container_class}">
	
		<h1><a>IT Audit Machine</a></h1>
		<form id="form_{$form_id}" class="itauditm" method="post" action="{$self_address}">
		    <div class="form_description">
				<h2>{$form_review_title}</h2>
				<p>{$form_review_description}</p>
			</div>
			{$pagination_header}
			{$payment_total_markup}
            {$entry_detail}
			<ul>
			<li id="li_buttons" class="buttons">
			    <input type="hidden" name="id" value="{$form_id}" />
			    <input type="hidden" name="la_page_from" value="{$from_page_num}" />
			    {$button_markup}
			</li>
			</ul>
		</form>		
			
	</div>
	<img id="bottom" src="{$itauditmachine_path}images/bottom.png" alt="" />
	</body>
</html>
EOT;
		}
        
		return $form_markup;
	}

	//display form payment page
	function la_display_form_payment($dbh,$form_id,$record_id,$form_params=array())
    {
		global $la_lang;
		
		if(!empty($form_params['integration_method'])){
			$integration_method = $form_params['integration_method'];
		}else{
			$integration_method = '';
		}

		if(!empty($form_params['itauditmachine_path'])){
			$itauditmachine_path = $form_params['itauditmachine_path'];
		}else{
			$itauditmachine_path = '';
		}

		if(!empty($form_params['itauditmachine_data_path'])){
			$itauditmachine_data_path = $form_params['itauditmachine_data_path'];
		}else{
			$itauditmachine_data_path = '';
		}

		//check for payment_token
		//if exist, the user is resuming the payment from previously unpaid entry
		//we need to set all necessary session if the token is valid
		if(!empty($form_params['pay_token'])){
			$payment_resume_token = trim($form_params['pay_token']);
			$payment_resume_token = base64_decode($payment_resume_token);

			$exploded  = explode('-', $payment_resume_token);
			$record_id = (int) $exploded[0];
			$date_created_md5 = $exploded[1];

			//compare the date created md5 with the existing record
			$query = "SELECT date_created FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `id`=?";
			$params = array($record_id);
		
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			if(md5($row['date_created']) == $date_created_md5){
				$_SESSION['la_payment_record_id'][$form_id] = $record_id;
				$_SESSION['la_form_payment_access'][$form_id]  = true;
				$_SESSION['la_form_completed'][$form_id] = true;
			}
		}

		//check permission to access this page
		if($_SESSION['la_form_payment_access'][$form_id] !== true){
			return "Your session has been expired. Please <a href='view.php?id={$form_id}'>click here</a> to start again.";
		}

		$la_settings = la_get_settings($dbh);
		
		//get form properties data
		$query 	= "select 
						  form_name,
						  form_has_css,
						  form_redirect,
						  form_language,
						  form_review,
						  form_review_primary_text,
						  form_review_secondary_text,
						  form_review_primary_img,
						  form_review_secondary_img,
						  form_review_use_image,
						  form_review_title,
						  form_review_description,
						  form_resume_enable,
						  form_page_total,
						  form_lastpage_title,
						  form_pagination_type,
						  form_theme_id,
						  payment_show_total,
						  payment_total_location,
						  payment_enable_merchant,
						  payment_merchant_type,
						  payment_currency,
						  payment_price_type,
						  payment_price_name,
						  payment_price_amount,
						  payment_ask_billing,
						  payment_ask_shipping,
						  payment_stripe_live_public_key,
						  payment_stripe_test_public_key,
						  payment_stripe_enable_test_mode,
						  payment_braintree_live_encryption_key,
						  payment_braintree_test_encryption_key,
						  payment_braintree_enable_test_mode,
						  payment_enable_recurring,
						  payment_recurring_cycle,
						  payment_recurring_unit,
						  payment_enable_trial,
						  payment_trial_period,
						  payment_trial_unit,
						  payment_trial_amount,
						  payment_delay_notifications,
						  payment_enable_tax,
					 	  payment_tax_rate,
					 	  payment_enable_discount,
						  payment_discount_type,
						  payment_discount_amount,
						  payment_discount_element_id,
						  form_custom_script_enable,
						  form_custom_script_url,
						  form_for_selected_company
				     from 
				     	 ".LA_TABLE_PREFIX."forms 
				    where 
				    	 form_id=?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$form_language = $row['form_language'];

		if(!empty($form_language)){
			la_set_language($form_language);
		}
		
		$form_payment_title			 = $la_lang['form_payment_title'];
		$form_payment_description	 = $la_lang['form_payment_description'];

		$form_has_css 			= $row['form_has_css'];
		$form_redirect			= $row['form_redirect'];
		$form_review  	 		= (int) $row['form_review'];
		$form_review_primary_text 	 = $row['form_review_primary_text'];
		$form_review_secondary_text  = $row['form_review_secondary_text'];
		$form_review_primary_img 	 = $row['form_review_primary_img'];
		$form_review_secondary_img   = $row['form_review_secondary_img'];
		$form_review_use_image  	 = (int) $row['form_review_use_image'];
		$form_review_title			 = $row['form_review_title'];
		$form_review_description	 = $row['form_review_description'];
		$form_page_total 			 = (int) $row['form_page_total'];
		$form_lastpage_title 		 = $row['form_lastpage_title'];
		$form_pagination_type		 = $row['form_pagination_type'];
		$form_name					 = htmlspecialchars($row['form_name'],ENT_QUOTES);
		$form_theme_id				 = $row['form_theme_id'];
		$form_resume_enable  	 	 = (int) $row['form_resume_enable'];

		$form_custom_script_enable 	= (int) $row['form_custom_script_enable'];
		$form_custom_script_url 	= $row['form_custom_script_url'];

		$payment_show_total	 		 = (int) $row['payment_show_total'];
		$payment_total_location 	 = $row['payment_total_location'];
		$payment_enable_merchant 	 = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		$payment_enable_tax 		 = (int) $row['payment_enable_tax'];
		$payment_tax_rate 			 = (float) $row['payment_tax_rate'];

		$payment_currency 	   		 = $row['payment_currency'];
		$payment_price_type 	     = $row['payment_price_type'];
		$payment_price_amount    	 = $row['payment_price_amount'];
		$payment_price_name			 = htmlspecialchars($row['payment_price_name'],ENT_QUOTES);
		$payment_ask_billing 	 	 = (int) $row['payment_ask_billing'];
		$payment_ask_shipping 	 	 = (int) $row['payment_ask_shipping'];
		$payment_merchant_type		 = $row['payment_merchant_type'];
		$payment_stripe_enable_test_mode = (int) $row['payment_stripe_enable_test_mode'];
		$payment_stripe_live_public_key	 = trim($row['payment_stripe_live_public_key']);
		$payment_stripe_test_public_key	 = trim($row['payment_stripe_test_public_key']);

		$payment_braintree_live_encryption_key  = trim($row['payment_braintree_live_encryption_key']);
		$payment_braintree_test_encryption_key  = trim($row['payment_braintree_test_encryption_key']);
		$payment_braintree_enable_test_mode 	= (int) $row['payment_braintree_enable_test_mode'];

		$payment_enable_recurring = (int) $row['payment_enable_recurring'];
		$payment_recurring_cycle  = (int) $row['payment_recurring_cycle'];
		$payment_recurring_unit   = $row['payment_recurring_unit'];

		//paypal pro and braintree currently doesn't support creating subscription through API
		if(in_array($payment_merchant_type, array('paypal_rest','braintree'))){
			$payment_enable_recurring = 0;
		}

		$payment_enable_trial = (int) $row['payment_enable_trial'];
		$payment_trial_period = (int) $row['payment_trial_period'];
		$payment_trial_unit   = $row['payment_trial_unit'];
		$payment_trial_amount = (float) $row['payment_trial_amount'];

		$payment_enable_discount = (int) $row['payment_enable_discount'];
		$payment_discount_type 	 = $row['payment_discount_type'];
		$payment_discount_amount = (float) $row['payment_discount_amount'];
		$payment_discount_element_id = (int) $row['payment_discount_element_id'];

		$payment_delay_notifications = (int) $row['payment_delay_notifications'];

		$is_discount_applicable = false;

		//if the discount element for the current entry_id having any value, we can be certain that the discount code has been validated and applicable
		if(!empty($payment_enable_discount)){
			$query = "select element_{$payment_discount_element_id} coupon_element from ".LA_TABLE_PREFIX."form_{$form_id} where `id` = ? and `status` = 1";
			$params = array($record_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			if(!empty($row['coupon_element'])){
				$is_discount_applicable = true;
			}
		}

		//check for specific form css, if any, use it instead
		if($form_has_css){
			$css_dir = $la_settings['data_dir']."/form_{$form_id}/css/";
		}
		
		if($integration_method == 'iframe'){
			$embed_class = 'class="embed"';
		}
		
		//get total payment
		$currency_symbol 	  = '&#36;';
		
		switch($payment_currency){
				case 'USD' : $currency_symbol = '&#36;';break;
				case 'EUR' : $currency_symbol = '&#8364;';break;
				case 'GBP' : $currency_symbol = '&#163;';break;
				case 'AUD' : $currency_symbol = 'A&#36;';break;
				case 'CAD' : $currency_symbol = 'C&#36;';break;
				case 'JPY' : $currency_symbol = '&#165;';break;
				case 'THB' : $currency_symbol = '&#3647;';break;
				case 'HUF' : $currency_symbol = '&#70;&#116;';break;
				case 'CHF' : $currency_symbol = 'CHF';break;
				case 'CZK' : $currency_symbol = '&#75;&#269;';break;
				case 'SEK' : $currency_symbol = 'kr';break;
				case 'DKK' : $currency_symbol = 'kr';break;
				case 'NOK' : $currency_symbol = 'kr';break;
				case 'PHP' : $currency_symbol = '&#36;';break;
				case 'IDR' : $currency_symbol = 'Rp';break;
				case 'MYR' : $currency_symbol = 'RM';break;
				case 'ZAR' : $currency_symbol = 'R';break;
				case 'PLN' : $currency_symbol = '&#122;&#322;';break;
				case 'BRL' : $currency_symbol = 'R&#36;';break;
				case 'HKD' : $currency_symbol = 'HK&#36;';break;
				case 'MXN' : $currency_symbol = 'Mex&#36;';break;
				case 'TWD' : $currency_symbol = 'NT&#36;';break;
				case 'TRY' : $currency_symbol = 'TL';break;
				case 'NZD' : $currency_symbol = '&#36;';break;
				case 'SGD' : $currency_symbol = '&#36;';break;
		}

		if($payment_price_type == 'variable'){

			$total_payment_amount = (double) la_get_payment_total($dbh,$form_id,$record_id,0,'live');
			$payment_items = la_get_payment_items($dbh,$form_id,$record_id,'live');
			
			
			//build the payment list markup
			$payment_list_items_markup = '';
			if(!empty($payment_items)){
				foreach ($payment_items as $item) {
					if($item['quantity'] > 1){
						$quantity_tag = ' <span style="font-weight: normal;padding-left:5px">x'.$item['quantity'].'</span>';
					}else{
						$quantity_tag = '';
					}

					if($item['type'] == 'money'){
						$payment_list_items_markup .= "<li>{$item['title']} <span>{$currency_symbol}{$item['amount']}{$quantity_tag}</span></li>"."\n";
					}else if($item['type'] == 'checkbox'){
						$payment_list_items_markup .= "<li>{$item['sub_title']} <span>{$currency_symbol}{$item['amount']}{$quantity_tag}</span></li>"."\n";
					}else if($item['type'] == 'select' || $item['type'] == 'radio'){
						$payment_list_items_markup .= "<li>{$item['title']} <em>({$item['sub_title']})</em> <span>{$currency_symbol}{$item['amount']}{$quantity_tag}</span></li>"."\n";
					}
				}

				//calculate discount if applicable
				if($is_discount_applicable){
					$payment_calculated_discount = 0;

					if($payment_discount_type == 'percent_off'){
						//the discount is percentage
						$payment_calculated_discount = ($payment_discount_amount / 100) * $total_payment_amount;
						$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
						$discount_percentage_label  = '('.$payment_discount_amount.'%)';
					}else{
						//the discount is fixed amount
						$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
						$discount_percentage_label = '';
					}

					$total_payment_amount -= $payment_calculated_discount;

					$payment_list_items_markup .= "<li>{$la_lang['discount']} {$discount_percentage_label}<span>-{$currency_symbol}{$payment_calculated_discount}</span></li>"."\n";
				}

				//calculate tax if enabled
				if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
					$payment_tax_amount = ($payment_tax_rate / 100) * $total_payment_amount;
					$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal

					$total_payment_amount += $payment_tax_amount;
					$payment_list_items_markup .= "<li>{$la_lang['tax']} ({$payment_tax_rate}%)<span>{$currency_symbol}{$payment_tax_amount}</span></li>"."\n";
				}
			}
		}else if($payment_price_type == 'fixed'){
			$total_payment_amount = $payment_price_amount;

			$payment_list_items_markup = "<li>{$payment_price_name}</li>";

			//calculate discount if applicable
			if($is_discount_applicable){
				$payment_calculated_discount = 0;

				if($payment_discount_type == 'percent_off'){
					//the discount is percentage
					$payment_calculated_discount = ($payment_discount_amount / 100) * $total_payment_amount;
					$payment_calculated_discount = round($payment_calculated_discount,2); //round to 2 digits decimal
					$discount_amount_label = $payment_discount_amount.'%';
				}else{
					//the discount is fixed amount
					$payment_calculated_discount = round($payment_discount_amount,2); //round to 2 digits decimal
					$discount_amount_label = $currency_symbol.$payment_calculated_discount;
				}

				$total_payment_amount -= $payment_calculated_discount;
				$discount_label = "-{$la_lang['discount']} {$discount_amount_label}";

				$payment_list_items_markup .= "<li>{$discount_label}</li>";
			}


			//calculate tax if enabled
			$tax_label = '';
			if(!empty($payment_enable_tax) && !empty($payment_tax_rate)){
				$payment_tax_amount = ($payment_tax_rate / 100) * $total_payment_amount;
				$payment_tax_amount = round($payment_tax_amount,2); //round to 2 digits decimal

				$total_payment_amount += $payment_tax_amount;
				$tax_label = "+{$la_lang['tax']} {$payment_tax_rate}%";

				$payment_list_items_markup .= "<li>{$tax_label}</li>";
			}

			
		}

		
		//construct payment terms
		if(!empty($payment_enable_recurring)){
			$payment_plurals = '';
			if($payment_recurring_cycle > 1){
				$payment_plurals = 's';
				$payment_recurring_cycle_markup = $payment_recurring_cycle.' ';
			}

			if(!empty($payment_enable_trial)){
				//recurring with trial period
				$payment_trial_price = $currency_symbol.$payment_trial_amount;
				if(empty($payment_trial_amount)){
					$payment_trial_price = 'free';
				}

				$payment_trial_plurals = '';
				if($payment_trial_period > 1){
					$payment_trial_plurals = 's';
				}

				$payment_term_markup =<<<EOT
					<li class="payment_summary_term">
						<em>Trial period: {$payment_trial_period} {$payment_trial_unit}{$payment_trial_plurals} ({$payment_trial_price})</em><br>
						<em>Then you will be charged {$currency_symbol}{$total_payment_amount} every {$payment_recurring_cycle_markup}{$payment_recurring_unit}{$payment_plurals}</em>
					</li>
EOT;
				$total_payment_amount = $payment_trial_amount; //when trial being enabled, we need to display the trial amount into the TOTAL
			}else{
				$payment_term_markup = "<li class=\"payment_summary_term\"><em>You will be charged {$currency_symbol}{$total_payment_amount} every {$payment_recurring_cycle_markup}{$payment_recurring_unit}{$payment_plurals}</em></li>";
			}
		}
		
		//if the form has multiple pages
		//display the pagination header
		if($form_page_total > 1){
			
			//build pagination header based on the selected type. possible values:
			//steps - display multi steps progress
			//percentage - display progress bar with percentage
			//disabled - disabled
			
			$page_breaks_data = array();
			$page_title_array = array();
			
			//get page titles
			$query = "SELECT 
							element_page_title
						FROM 
							".LA_TABLE_PREFIX."form_elements
					   WHERE
							form_id = ? and element_status = 1 and element_type = 'page_break'
					ORDER BY 
					   		element_page_number asc";
			$params = array($form_id);
			
			$sth = la_do_query($query,$params,$dbh);
			while($row = la_do_fetch_result($sth)){
				$page_title_array[] = $row['element_page_title'];
			}
			
			if($form_pagination_type == 'steps'){
				
				$page_titles_markup = '';
				
				$i=1;
				foreach ($page_title_array as $page_title){
					$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$page_title.'</span></td><td align="center" class="ap_tp_arrow">&gt;</td>'."\n";
					$i++;
				}
				
				//add the last page title into the pagination header markup
				$page_titles_markup .= '<td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$form_lastpage_title.'</span></td>';
				
				if(!empty($form_review)){
					$i++;
					$page_titles_markup .= '<td align="center" class="ap_tp_arrow">&gt;</td><td align="center"><span id="page_num_'.$i.'" class="ap_tp_num">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text">'.$form_review_title.'</span></td>';
				}

				$i++;
				$page_titles_markup .= '<td align="center" class="ap_tp_arrow">&gt;</td><td align="center"><span id="page_num_'.$i.'" class="ap_tp_num ap_tp_num_active">'.$i.'</span><span id="page_title_'.$i.'" class="ap_tp_text ap_tp_text_active">'.$la_lang['form_payment_header_title'].'</span></td>';
				
				
				$pagination_header =<<<EOT
			<ul>
			<li id="pagination_header" class="li_pagination">
			 <table class="ap_table_pagination" width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr> 
			  	{$page_titles_markup}
			  </tr>
			</table>
			</li>
			</ul>
EOT;
			}else if($form_pagination_type == 'percentage'){
				
				$page_total = count($page_title_array) + 2;
				if(!empty($form_review)){
					$page_total++;
				}
				
				$percent_value = 99;
				
				$page_number_title = sprintf($la_lang['page_title'],$page_total,$page_total);
				$pagination_header =<<<EOT
			<ul>
				<li id="pagination_header" class="li_pagination" title="Click to edit">
			    <h3 id="page_title_{$page_total}">{$page_number_title}</h3>
				<div class="la_progress_container">          
			    	<div id="la_progress_percentage" class="la_progress_value" style="width: {$percent_value}%"><span>{$percent_value}%</span></div>
				</div>
				</li>
			</ul>
EOT;
			}else{			
				$pagination_header = '';
			}
			
		}

		
		//build the button markup
		$button_markup =<<<EOT
<input id="btn_submit_payment" class="button_text btn_primary" type="submit" data-originallabel="{$la_lang['payment_submit_button']}" value="{$la_lang['payment_submit_button']}" />
EOT;

		//if this form is using custom theme
		if(!empty($form_theme_id)){
			//get the field highlight color for the particular theme
			$query = "SELECT 
							highlight_bg_type,
							highlight_bg_color,
							form_shadow_style,
							form_shadow_size,
							form_shadow_brightness,
							form_button_type,
							form_button_text,
							form_button_image,
							theme_has_css  
						FROM 
							".LA_TABLE_PREFIX."form_themes 
					   WHERE 
					   		theme_id = ?";
			$params = array($form_theme_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			
			$form_shadow_style 		= $row['form_shadow_style'];
			$form_shadow_size 		= $row['form_shadow_size'];
			$form_shadow_brightness = $row['form_shadow_brightness'];
			$theme_has_css = (int) $row['theme_has_css'];
			
			//if the theme has css file, make sure to refer to that file
			//otherwise, generate the css dynamically
			
			if(!empty($theme_has_css)){
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.$la_settings['data_dir'].'/themes/theme_'.$form_theme_id.'.css" media="all" />';
			}else{
				$theme_css_link = '<link rel="stylesheet" type="text/css" href="'.$itauditmachine_path.'css_theme.php?theme_id='.$form_theme_id.'" media="all" />';
			}
			
			if($row['highlight_bg_type'] == 'color'){
				$field_highlight_color = $row['highlight_bg_color'];
			}else{ 
				//if the field highlight is using pattern instead of color, set the color to empty string
				$field_highlight_color = ''; 
			}
			
			//get the css link for the fonts
			$font_css_markup = la_theme_get_fonts_link($dbh,$form_theme_id);
			
			//get the form shadow classes
			if(!empty($form_shadow_style) && ($form_shadow_style != 'disabled')){
				preg_match_all("/[A-Z]/",$form_shadow_style,$prefix_matches);
				//this regex simply get the capital characters of the shadow style name
				//example: RightPerspectiveShadow result to RPS and then being sliced to RP
				$form_shadow_prefix_code = substr(implode("",$prefix_matches[0]),0,-1);
				
				$form_shadow_size_class  = $form_shadow_prefix_code.ucfirst($form_shadow_size);
				$form_shadow_brightness_class = $form_shadow_prefix_code.ucfirst($form_shadow_brightness);

				if(empty($integration_method)){ //only display shadow if the form is not being embedded using any method
					$form_container_class = $form_shadow_style.' '.$form_shadow_size_class.' '.$form_shadow_brightness_class;
				}
			}
			
			
			
		}else{ //if the form doesn't have any theme being applied
			$field_highlight_color = '#FFF7C0';
			
			if(empty($integration_method)){
				$form_container_class = ''; //default shadow as of v4.2 is no-shadow
			}else{
				$form_container_class = ''; //dont show any shadow when the form being embedded
			}
		}

		if(empty($la_settings['disable_itauditmachine_link'])){
			$powered_by_markup = 'Powered by ITAM, the <a href="http://www.lazarusalliance.com" target="_blank">IT Audit Machine</a>';
		}else{
			$powered_by_markup = '';
		}

		$self_address = htmlentities($_SERVER['PHP_SELF']); //prevent XSS

		$country = la_get_country_list();
		$country_markup = '<option value="" selected="selected"></option>'."\n";

		foreach ($country as $data){
			$country_markup .= "<option value=\"{$data['value']}\">{$data['label']}</option>\n";
		}

		$billing_address_markup = '';
		if(!empty($payment_ask_billing)){
			$billing_address_markup =<<<EOT
				<li id="li_billing_address" class="address">
					<label class="description">Billing Address <span class="required">*</span></label>
					<div>
						<span id="li_billing_span_1">
							<input id="billing_street" class="element text large" value="" type="text" />
							<label for="billing_street">{$la_lang['address_street']}</label>
						</span>
					
						<span id="li_billing_span_2" class="left state_list">
							<input id="billing_city" class="element text large" value="" type="text" />
							<label for="billing_city">{$la_lang['address_city']}</label>
						</span>
					
						<span id="li_billing_span_3" class="right state_list">
							<input id="billing_state" class="element text large" value="" type="text" />
							<label for="billing_state">{$la_lang['address_state']}</label>
						</span>
					
						<span id="li_billing_span_4" class="left">
							<input id="billing_zipcode" class="element text large" maxlength="15" value="{$default_value_5}" type="text" />
							<label for="billing_zipcode">{$la_lang['address_zip']}</label>
						</span>
						
						<span id="li_billing_span_5" class="right">
							<select class="element select large" id="billing_country"> 
								{$country_markup}	
							</select>
						<label for="billing_country">{$la_lang['address_country']}</label>
					    </span>
				    </div><p id="billing_error_message" class="error" style="display: none"></p>
				</li>
EOT;
		}

		$shipping_address_markup = '';
		if(!empty($payment_ask_shipping)){

			//if both billing and shipping being enabled, display a checkbox to allow the user to mark the address as the same
			if(!empty($payment_ask_billing)){
				$same_shipping_markup =<<<EOT
					<div>
					    <input type="checkbox" value="1" checked="checked" class="checkbox" id="la_same_shipping_address">
						<label for="la_same_shipping_address" class="choice">My shipping address is the same as my billing address</label>
					</div>
EOT;
				$shipping_display = 'display: none';
			}

			$shipping_address_markup =<<<EOT
				<li id="li_shipping_address" class="address">
					<label class="description shipping_address_detail" style="{$shipping_display}">Shipping Address <span class="required">*</span></label>
					<div class="shipping_address_detail" style="{$shipping_display}">
						<span id="li_shipping_span_1">
							<input id="shipping_street" class="element text large" value="" type="text" />
							<label for="shipping_street">{$la_lang['address_street']}</label>
						</span>
					
						<span id="li_shipping_span_2" class="left state_list">
							<input id="shipping_city" class="element text large" value="" type="text" />
							<label for="shipping_city">{$la_lang['address_city']}</label>
						</span>
					
						<span id="li_shipping_span_3" class="right state_list">
							<input id="shipping_state" class="element text large" value="" type="text" />
							<label for="shipping_state">{$la_lang['address_state']}</label>
						</span>
					
						<span id="li_shipping_span_4" class="left">
							<input id="shipping_zipcode" class="element text large" maxlength="15" value="{$default_value_5}" type="text" />
							<label for="shipping_zipcode">{$la_lang['address_zip']}</label>
						</span>
						
						<span id="li_shipping_span_5" class="right">
							<select class="element select large" id="shipping_country"> 
								{$country_markup}	
							</select>
						<label for="shipping_country">{$la_lang['address_country']}</label>
					    </span>
					    <p id="shipping_error_message" class="error" style="display: none"></p>
				    </div>
				    {$same_shipping_markup}
				</li>
EOT;
		}

		$credit_card_logos = array();
		$credit_card_logos['visa'] 		 = '<img src="'.$itauditmachine_path.'images/cards/visa.png" alt="Visa" title="Visa" />';
		$credit_card_logos['mastercard'] = '<img src="'.$itauditmachine_path.'images/cards/mastercard.png" alt="MasterCard" title="MasterCard" />';
		$credit_card_logos['amex'] 		 = '<img src="'.$itauditmachine_path.'images/cards/amex.png" alt="American Express" title="American Express" />';
		$credit_card_logos['jcb'] 		 = '<img src="'.$itauditmachine_path.'images/cards/jcb.png" alt="JCB" title="JCB" />';
		$credit_card_logos['discover']   = '<img src="'.$itauditmachine_path.'images/cards/discover.png" alt="Discover" title="Discover" />';
		$credit_card_logos['diners'] 	 = '<img src="'.$itauditmachine_path.'images/cards/diners.png" alt="Diners Club" title="Diners Club" />';

		$accepted_card_types = array('visa','mastercard','amex','jcb','discover','diners'); //the default accepted credit card types

		if($payment_merchant_type == 'stripe'){
			if(!empty($payment_stripe_enable_test_mode)){
				$stripe_public_key = $payment_stripe_test_public_key;
			}else{
				$stripe_public_key = $payment_stripe_live_public_key;
			}

			$merchant_js =<<<EOT
<script type="text/javascript" src="https://js.stripe.com/v1/"></script>
<script type="text/javascript">
	Stripe.setPublishableKey('{$stripe_public_key}');
</script>
<script type="text/javascript" src="{$itauditmachine_path}js/payment_stripe.js"></script>
EOT;
			
			if($payment_currency != 'USD'){
				$accepted_card_types = array('visa','mastercard','amex');
			}
		}else if($payment_merchant_type == 'authorizenet'){

			$merchant_js =<<<EOT
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.payment.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/payment_authorizenet.js"></script>
EOT;
		}else if($payment_merchant_type == 'paypal_rest'){

			$merchant_js =<<<EOT
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.payment.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/payment_paypal_rest.js"></script>
EOT;
			$accepted_card_types = array('visa','mastercard','amex','discover');
		}else if($payment_merchant_type == 'braintree'){
			if(!empty($payment_braintree_enable_test_mode)){
				$braintree_client_side_encryption_key = $payment_braintree_test_encryption_key;
			}else{
				$braintree_client_side_encryption_key = $payment_braintree_live_encryption_key;
			}

			$merchant_js =<<<EOT
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.payment.js"></script>
<script type="text/javascript" src="https://js.braintreegateway.com/v1/braintree.js"></script>
<script>
  var la_braintree = Braintree.create("{$braintree_client_side_encryption_key}");
</script>
<script type="text/javascript" src="{$itauditmachine_path}js/payment_braintree.js"></script>
EOT;
		}

		//build the credit card logo markup
		$credit_card_logo_markup = '';
		foreach ($accepted_card_types as $card_type) {
			$credit_card_logo_markup .= $credit_card_logos[$card_type]."\n";
		}

		$jquery_url = $itauditmachine_path.'js/jquery.min.js';

		$current_year = date("Y");
		$year_dropdown_markup = '';
		foreach (range($current_year, $current_year + 15) as $year) {
			$year_dropdown_markup .= "<option value=\"{$year}\">{$year}</option>"."\n";
		}

		//load custom javascript if enabled
		$custom_script_js = '';
		if(!empty($form_custom_script_enable) && !empty($form_custom_script_url)){
			$custom_script_js = '<script type="text/javascript" src="'.$form_custom_script_url.'"></script>';
		}

		if($integration_method == 'php'){

			$form_markup = <<<EOT
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
{$theme_css_link}
{$font_css_markup}
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
{$merchant_js}
{$custom_script_js}
<style>
html{
	background: none repeat scroll 0 0 transparent;
}
</style>

<div id="main_body" class="integrated no_guidelines" data-itauditmachinepath="{$itauditmachine_path}">
	<div id="form_container">
		<form id="form_{$form_id}" class="itauditm" method="post" action="javascript:" data-highlightcolor="{$field_highlight_color}">
		    <div class="form_description">
				<h2>{$form_payment_title}</h2>
				<p>{$form_payment_description}</p>
			</div>
			{$pagination_header}
			
			<ul class="payment_summary">
				<li class="payment_summary_amount total_payment" data-basetotal="{$total_payment_amount}">
					<span>
						<h3>{$currency_symbol}<var>0</var></h3>
						<h5>{$la_lang['payment_total']}</h5>
					</span>
				</li>
				<li class="payment_summary_list">
					<ul class="payment_list_items">
						{$payment_list_items_markup}
					</ul>
				</li>
				{$payment_term_markup}
			</ul>
			<ul class="payment_detail_form">
				<li id="error_message" style="display: none">
						<h3 id="error_message_title">{$la_lang['error_title']}</h3>
						<p id="error_message_desc">{$la_lang['error_desc']}</p>
				</li>	
				<li id="li_accepted_cards">
					{$credit_card_logo_markup}
				</li>
				<li id="li_credit_card" class="credit_card">
					<label class="description">Credit Card <span class="required">*</span></label>
					<div>
						<span id="li_cc_span_1" class="left">
							<input id="cc_first_name" class="element text large" value="" type="text" />
							<label for="cc_first_name">First Name</label>
						</span>
					
						<span id="li_cc_span_2" class="right">
							<input id="cc_last_name" class="element text large" value="" type="text" />
							<label for="cc_last_name">Last Name</label>
						</span>

						<span id="li_cc_span_3" class="left">
							<input id="cc_number" class="element text large" value="" type="text" />
							<label for="cc_number">Credit Card Number</label>
						</span>
					
						<span id="li_cc_span_4" class="right">
							<input id="cc_cvv" class="element text large" value="" type="text" />
							<label for="cc_cvv">CVV</label>
						</span>

						<span id="li_cc_span_5" style="text-align: right">
							<img id="cc_secure_icon" src="{$itauditmachine_path}images/icons/lock.png" alt="Secure" title="Secure" /> 
							<label for="cc_expiry_month" style="display: inline">Expiration: </label>
							<select class="element select" id="cc_expiry_month">
								<option value="01">01 - January</option>
								<option value="02">02 - February</option>
								<option value="03">03 - March</option>
								<option value="04">04 - April</option>
								<option value="05">05 - May</option>
								<option value="06">06 - June</option>
								<option value="07">07 - July</option>
								<option value="08">08 - August</option>
								<option value="09">09 - September</option>
								<option value="10">10 - October</option>
								<option value="11">11 - November</option>
								<option value="12">12 - December</option>
							</select>
							<select class="element select" id="cc_expiry_year">
								{$year_dropdown_markup}
							</select>
						</span>
					</div><p id="credit_card_error_message" class="error" style="display: none"></p>
				</li>
				<li id="li_2" class="section_break">
				</li>
				{$billing_address_markup}
				{$shipping_address_markup}
				<li id="li_buttons" class="buttons">
					<input type="hidden" id="form_id" value="{$form_id}" />
				    {$button_markup}
				    <img id="la_payment_loader_img" style="display: none" src="{$itauditmachine_path}images/loader_small_grey.gif" />
				</li>
			</ul>
		</form>		
		<form id="form_payment_redirect" method="post" action="{$self_address}">
			<input type="hidden" id="form_id_redirect" name="form_id_redirect" value="{$form_id}" />
		</form>		
	</div>
</div>
EOT;
		}else{

			if($integration_method == 'iframe'){
				$auto_height_js =<<<EOT
<script type="text/javascript" src="{$itauditmachine_path}js/jquery.ba-postmessage.min.js"></script>
<script type="text/javascript">
    $(function(){
    	$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
    });
</script>
EOT;
			}

			$form_markup = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html {$embed_class} xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>{$form_name}</title>
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}view.mobile.css" media="all" />
<link rel="stylesheet" type="text/css" href="{$itauditmachine_path}js/video-js/video-js.css" />
{$theme_css_link}
{$font_css_markup}
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_path}view.js"></script>
{$merchant_js}
{$auto_height_js}
{$custom_script_js}
</head>
<body id="main_body" class="no_guidelines" data-itauditmachinepath="{$itauditmachine_path}">
	
	<div id="form_container" class="{$form_container_class}">
	
		<h1><a>IT Audit Machine</a></h1>
		<form id="form_{$form_id}" class="itauditm" method="post" action="javascript:" data-highlightcolor="{$field_highlight_color}">
		    <div class="form_description">
				<h2>{$form_payment_title}</h2>
				<p>{$form_payment_description}</p>
			</div>
			{$pagination_header}
			
			<ul class="payment_summary">
				<li class="payment_summary_amount total_payment" data-basetotal="{$total_payment_amount}">
					<span>
						<h3>{$currency_symbol}<var>0</var></h3>
						<h5>{$la_lang['payment_total']}</h5>
					</span>
				</li>
				<li class="payment_summary_list">
					<ul class="payment_list_items">
						{$payment_list_items_markup}
					</ul>
				</li>
				{$payment_term_markup}
			</ul>
			<ul class="payment_detail_form">
				<li id="error_message" style="display: none">
						<h3 id="error_message_title">{$la_lang['error_title']}</h3>
						<p id="error_message_desc">{$la_lang['error_desc']}</p>
				</li>	
				<li id="li_accepted_cards">
					{$credit_card_logo_markup}
				</li>
				<li id="li_credit_card" class="credit_card">
					<label class="description">Credit Card <span class="required">*</span></label>
					<div>
						<span id="li_cc_span_1" class="left">
							<input id="cc_first_name" class="element text large" value="" type="text" />
							<label for="cc_first_name">First Name</label>
						</span>
					
						<span id="li_cc_span_2" class="right">
							<input id="cc_last_name" class="element text large" value="" type="text" />
							<label for="cc_last_name">Last Name</label>
						</span>

						<span id="li_cc_span_3" class="left">
							<input id="cc_number" class="element text large" value="" type="text" />
							<label for="cc_number">Credit Card Number</label>
						</span>
					
						<span id="li_cc_span_4" class="right">
							<input id="cc_cvv" class="element text large" value="" type="text" />
							<label for="cc_cvv">CVV</label>
						</span>

						<span id="li_cc_span_5" style="text-align: right">
							<img id="cc_secure_icon" src="{$itauditmachine_path}images/icons/lock.png" alt="Secure" title="Secure" /> 
							<label for="cc_expiry_month" style="display: inline">Expiration: </label>
							<select class="element select" id="cc_expiry_month">
								<option value="01">01 - January</option>
								<option value="02">02 - February</option>
								<option value="03">03 - March</option>
								<option value="04">04 - April</option>
								<option value="05">05 - May</option>
								<option value="06">06 - June</option>
								<option value="07">07 - July</option>
								<option value="08">08 - August</option>
								<option value="09">09 - September</option>
								<option value="10">10 - October</option>
								<option value="11">11 - November</option>
								<option value="12">12 - December</option>
							</select>
							<select class="element select" id="cc_expiry_year">
								{$year_dropdown_markup}
							</select>
						</span>
					</div><p id="credit_card_error_message" class="error" style="display: none"></p>
				</li>
				<li id="li_2" class="section_break">
				</li>
				{$billing_address_markup}
				{$shipping_address_markup}
				<li id="li_buttons" class="buttons">
					<input type="hidden" id="form_id" value="{$form_id}" />
				    {$button_markup}
				    <img id="la_payment_loader_img" style="display: none" src="{$itauditmachine_path}images/loader_small_grey.gif" />
				</li>
			</ul>
		</form>		
		<form id="form_payment_redirect" method="post" action="{$self_address}">
			<input type="hidden" id="form_id_redirect" name="form_id_redirect" value="{$form_id}" />
		</form>	
	</div>
	
	</body>
</html>
EOT;
		}

		return $form_markup;
	}

	function label_styles($element) {
    	$label_styles = '';
    	if( !empty($element->label_background_color) ) {
        	$label_styles .= "background-color:{$element->label_background_color};";
        }
        
        if( !empty($element->label_color) ) {
            $label_styles .= "color:{$element->label_color};";
        }
        return $label_styles;
    }