<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/

 	function display_casecade_form_fields($parameter=array())
	{
		if(!$parameter['form_id']){
			return;
		}
		
		$dbh = $parameter['dbh'];
		$la_settings = la_get_settings($dbh);
		$form_id = $parameter['form_id'];
		$parent_form_id = $parameter['parent_form_id'];
		$entry_id = $parameter['entry_id'];
		$company_id = $parameter['company_id'];

		//get casecade_element_position of the sub form from the parent form
		$query = "SELECT `element_position` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = {$parent_form_id} AND `element_type` = 'casecade_form' AND `element_default_value` = {$form_id}";
		$sth = la_do_query($query, array(), $dbh);
		$row = la_do_fetch_result($sth);
		$tmp_casecade_element_position = $row["element_position"];

		$statusElementArr = array();
		$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		$result = la_do_query($sql_query, array($form_id, $company_id, $entry_id), $dbh);
		
		while($row=la_do_fetch_result($result)){
			$statusElementArr[$row['element_id']] = $row['indicator'];
		}
		//get entry details for particular entry_id
		$param['checkbox_image'] = 'images/icons/59_blue_16.png';
		$entry_details = la_get_entry_details($dbh, $form_id, $company_id, $entry_id, $param);
		$toggle = false;
		$row_markup = '';
        $row_markup_doc = '';
		
		foreach ($entry_details as $data){ 
		
		
 		
			if($data['label'] == 'la_page_break' && $data['value'] == 'la_page_break'){
				continue;
			}

			$edit_entry_url = "view.php?id={$parent_form_id}&entry_id={$entry_id}";

			if ( isset($data['element_page_number']) ) {
				$edit_entry_url.= "&casecade_form_page_number=".$data['element_page_number'];
			}

			if( isset($parameter['cascade_parent_page_number']) ) {
				$edit_entry_url.= "&la_page=".$parameter['cascade_parent_page_number'];
			}

			if( isset($tmp_casecade_element_position) ) {
				$edit_entry_url.= "&casecade_element_position=".$tmp_casecade_element_position;
			}

			if( !empty($data['element_id_auto']) ) {
				$edit_entry_url.= "&element_id_auto=".$data['element_id_auto'];
			}
		
			if($toggle){
				$toggle = false;
				$row_style = 'class="alt"';
			}else{
				$toggle = true;
				$row_style = '';
			}
	
			$element_id = $data['element_id'];

			$status_indicator = "";
			$indicator_count = 0;
			
			if(in_array($data['element_type'], array('text', 'textarea', 'file', 'radio', 'checkbox', 'select', 'signature', 'matrix')) && $data['element_status_indicator'] == 1){
				if(isset($statusElementArr[$data['element_id']])){
					$indicator_count = $statusElementArr[$data['element_id']];
				}
				if(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 0){
					$status_indicator_image = 'Circle_Gray.png';
				}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 1){
					$status_indicator_image = 'Circle_Red.png';
				}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 2){
					$status_indicator_image = 'Circle_Yellow.png';
				}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 3){
					$status_indicator_image = 'Circle_Green.png';
				}else{
					$status_indicator_image = 'Circle_Gray.png';
				}	

				$status_indicator = '<img class="status-icon status-icon-action-view" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;" />';
			}
		
			if($data['element_type'] == 'section' || $data['element_type'] == 'textarea') {
				if($data['element_type'] == 'textarea'){
					$data['value'] = html_entity_decode($data['value']);
				}

				if(!empty($data['label']) && !empty($data['value']) && ($data['value'] != '&nbsp;')){
					$section_separator = '<br/>';
				}else{
					$section_separator = '';
				}

				if ($data["value"] != strip_tags($data["value"])) {  $contains_html = true; } else { $contains_html = false; }

				if ($contains_html) {
					echo "<script> function resizeIframe_{$entry_id}(obj) {   obj.style.height = (obj.contentWindow.document.body.scrollHeight + 20) + 'px';	}</script>";
				
					$display_data = "<iframe srcdoc='" . $data['value'] . "' style='width:100%; border:0px;' scrolling='no' onload='resizeIframe_" . $entry_id . "(this)'></iframe>";
					
 				} else {
					$display_data = nl2br($data['value']);
				}
		
				$section_break_content = '<span class="la_section_title"><strong>'.nl2br($data['label']).'</strong>'.$status_indicator.'</span>'.$section_separator.'<span class="la_section_content">'.$display_data.'</span>';
		
				$row_markup .= "<tr {$row_style}>\n";
				$row_markup .= "<td width=\"80%\" colspan=\"2\">{$section_break_content}</td>\n";
				$row_markup .= "<td width=\"20%\"><a href=\"{$edit_entry_url}\">Edit field</a></td>\n";
				$row_markup .= "</tr>\n";
			}
			
			elseif($data['element_type'] == 'casecade_form') {

				$row_markup .= "<tr {$row_style}>\n";
				$row_markup .= "<td width=\"100%\" colspan=\"2\">HEY</td>\n";
				$row_markup .= "</tr>\n";
			}
				
			elseif($data['element_type'] == 'signature') {
				if($data['element_size'] == 'small'){
					$canvas_height = 70;
					$line_margin_top = 50;
				}elseif($data['element_size'] == 'medium'){
					$canvas_height = 130;
					$line_margin_top = 95;
				}else{
					$canvas_height = 260;
					$line_margin_top = 200;
				}
		
					$signature_markup = <<<EOT
					<div id="la_sigpad_{$parent_form_id}_{$form_id}_{$element_id}" class="la_sig_wrapper {$data['element_size']}">
					  <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
					</div>
					<script type="text/javascript">
						$(function(){
							var sigpad_options_{$parent_form_id}_{$form_id}_{$element_id} = {
							   drawOnly : true,
							   displayOnly: true,
							   bgColour: '#fff',
							   penColour: '#000',
							   output: '#element_{$parent_form_id}_{$form_id}_{$element_id}',
							   lineTop: {$line_margin_top},
							   lineMargin: 10,
							   validateFields: false
							};
							var sigpad_data_{$parent_form_id}_{$form_id}_{$element_id} = {$data['value']};
							$('#la_sigpad_{$parent_form_id}_{$form_id}_{$element_id}').signaturePad(sigpad_options_{$parent_form_id}_{$form_id}_{$element_id}).regenerate(sigpad_data_{$parent_form_id}_{$form_id}_{$element_id});
						});
					</script>
EOT;
				$row_markup .= "<tr>\n";
				$row_markup .= "<td width=\"30%\" style=\"vertical-align: top\"><span><strong>{$data['label']}</strong>".$status_indicator."</span></td>\n";
				$row_markup .= "<td width=\"50%\">{$signature_markup}</td>\n";
				$row_markup .= "<td width=\"20%\"><a href=\"{$edit_entry_url}\">Edit field</a></td>\n";
				$row_markup .= "</tr>\n";
			}
			elseif($data['element_type'] == 'casecade_form') {
                $row_markup_array = display_casecade_form_fields(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $company_id));
                $row_markup_doc .= $row_markup_array['row_markup_doc'];
                $row_markup .= $row_markup_array['row_markup'];
			}else{
				$tmpData = nl2br($data['value']);
				$row_markup .= "<tr {$row_style}>\n";
				$row_markup .= "<td width=\"30%\"><span><strong>{$data['label']}</strong>".$status_indicator."</span></td>\n";
				$row_markup .= "<td width=\"50%\">{$tmpData}</td>\n";
				$row_markup .= "<td width=\"20%\"><a href=\"{$edit_entry_url}\">Edit field</a></td>\n";
				$row_markup .= "</tr>\n";
			}
		} 
		//check if the document is added to ap_background_document_proccesses table list and if has not created yet
		$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` where `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ? AND status != 1 order by id DESC LIMIT 1";
		$sth_document_process = la_do_query($query_document_process, array($form_id, $company_id, $entry_id), $dbh);
		$row_document_process = la_do_fetch_result($sth_document_process);
		if( $row_document_process['id'] ) {
			//latest document has not been created yet, but as parent called it dont show message to generate it
			$row_markup_doc .= "";
		} else {
			// fetch doc details if available
      $query11 = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND isZip = 1 order by `docx_create_date` DESC LIMIT 1";
			$sth11 = la_do_query($query11, array($form_id, $company_id, $entry_id), $dbh);

      if($toggle){
        $toggle = false;
        $row_style = 'class="alt"';
      }else{
        $toggle = true;
        $row_style = '';
      }

			$document_list = [];
			$document_count = 0;
			while($document_data = la_do_fetch_result($sth11)){
        		
				$row_markup_doc .= "<tr class=\"{$row_style}\">\n";
				$row_markup_doc .= "<td><strong>Download Report</strong></td>\n";
				$row_markup_doc .= "<td><a target=\"_blank\" href=\"javascript:void()\" class=\"action-download-document-zip\" data-documentdownloadlink=\"download_document_zip.php?id={$document_data['docxname']}&form_id={$form_id}&entry_id={$entry_id}&company_id={$company_id}\">{$document_data['docxname']}</a></td>\n";
				$row_markup_doc .="<td></td>";
				$row_markup_doc .= "</tr>\n";

				if( !empty($document_data['added_files']) ) {
					$added_files = explode(',', $document_data['added_files']);

					foreach ($added_files as $docxname) {
				
						$target_file 	= $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
						$filename_ext   = end(explode(".", $docxname));
						$q_string = "file_path={$target_file}&file_name={$docxname}&form_id={$form_id}&document_preview=1";
						
						$document_list[$document_count]['ext'] = $filename_ext;
						$document_list[$document_count]['q_string'] = $q_string;
						$document_list[$document_count]['file_name'] = $docxname;
						$document_count++;
					}
				}
			}

			if( count($document_list) > 0 ) {
				$row_markup_doc .= '<tr><td><strong>Preview Report(s)</strong></td><td>';
					
				foreach ($document_list as $document_view_data) {
					$row_markup_doc .= '<img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$document_view_data['ext'].'" data-src="'.base64_encode($document_view_data['q_string']).'">'.$document_view_data['file_name'].'</a><br/>';
				}
				$row_markup_doc .= '</td><td></td></tr>';
			}
		}
		
		
		return array('row_markup' => $row_markup, 'row_markup_doc' => $row_markup_doc);
	}

	function display_casecade_form_fields_status_only($parameter=array())
	{
		if(!$parameter['form_id']){
			return;
		}

		$dbh = $parameter['dbh'];
		$la_settings = la_get_settings($dbh);
		$form_id = $parameter['form_id'];
		$parent_form_id = $parameter['parent_form_id'];
		$entry_id = $parameter['entry_id'];
		$company_id = $parameter['company_id'];
		$accordion_head_count_Arr = [0,0,0,0];

		//get page number and casecade_element_position of the sub form from the parent form
		$query = "SELECT `element_position`, `element_page_number` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` = 'casecade_form' AND `element_default_value` = ?";
		$sth = la_do_query($query, array($parent_form_id, $form_id), $dbh);
		$row = la_do_fetch_result($sth);
		$tmp_casecade_element_position = $row["element_position"];
		$tmp_page_number = $row["element_page_number"];
		
		//get status indicator related data
		$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
		$result = la_do_query($sql_query,array($form_id, $company_id, $entry_id),$dbh);
		$statusElementArr = [];

		while($row=la_do_fetch_result($result)){
			$statusElementArr[$row['element_id']] = $row['indicator'];
		}

		//get entry details for particular entry_id
		$param['checkbox_image'] = 'images/icons/59_blue_16.png';

		$entry_details = la_get_entry_details($dbh, $form_id, $company_id, $entry_id, $param);
		$toggle = false;
		$row_markup = [];
			
		foreach ($entry_details as $data){ 
		
			if($data['label'] == 'la_page_break' && $data['value'] == 'la_page_break'){
				continue;
			}

			$element_id = $data['element_id'];
			if(in_array( $data['element_type'],
           		array( 'text',
                  'textarea',
                  'file',
                  'radio',
                  'checkbox',
                  'select',
                  'signature',
                  'matrix' )) && $data['element_status_indicator'] == 1) {
				$statusElementArrId = $statusElementArr[$data['element_id']];
				if(isset($statusElementArrId)){
					$indicator_count = $statusElementArrId;

					if( $statusElementArrId == 0){
						$status_indicator_image = 'Circle_Gray.png';
						$accordion_head_count_Arr[0] = $accordion_head_count_Arr[0]+1;
					}elseif( $statusElementArrId == 1){
						$status_indicator_image = 'Circle_Red.png';
						$accordion_head_count_Arr[1] =$accordion_head_count_Arr[1]+1; 
					}elseif( $statusElementArrId == 2){
						$status_indicator_image = 'Circle_Yellow.png';
						$accordion_head_count_Arr[2] =$accordion_head_count_Arr[2]+1;
					}elseif( $statusElementArrId == 3){
						$status_indicator_image = 'Circle_Green.png';
						$accordion_head_count_Arr[3] = $accordion_head_count_Arr[3]+1;
					}
				} else {
					$statusElementArrId = 0;
          $status_indicator_image = 'Circle_Gray.png';
          $accordion_head_count_Arr[0] = $accordion_head_count_Arr[0]+1;
				}
				if( $data['element_type'] == 'textarea' ) {
					$field_value = html_entity_decode($data['value']);
				} elseif($data['element_type'] == 'signature') {
					if($data['element_size'] == 'small'){
						$canvas_height = 70;
						$line_margin_top = 50;
					}elseif($data['element_size'] == 'medium'){
						$canvas_height = 130;
						$line_margin_top = 95;
					}else{
						$canvas_height = 260;
						$line_margin_top = 200;
					}
			
						$signature_markup = <<<EOT
						<div id="la_sigpad_{$parent_form_id}_{$form_id}_{$element_id}" class="la_sig_wrapper {$data['element_size']}">
						  <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
						</div>
						<script type="text/javascript">
							$(function(){
								var sigpad_options_{$parent_form_id}_{$form_id}_{$element_id} = {
								   drawOnly : true,
								   displayOnly: true,
								   bgColour: '#fff',
								   penColour: '#000',
								   output: '#element_{$parent_form_id}_{$form_id}_{$element_id}',
								   lineTop: {$line_margin_top},
								   lineMargin: 10,
								   validateFields: false
								};
								var sigpad_data_{$parent_form_id}_{$form_id}_{$element_id} = {$data['value']};
								$('#la_sigpad_{$parent_form_id}_{$form_id}_{$element_id}').signaturePad(sigpad_options_{$parent_form_id}_{$form_id}_{$element_id}).regenerate(sigpad_data_{$parent_form_id}_{$form_id}_{$element_id});
							});
						</script>
EOT;
					$field_value = $signature_markup;
				}else{
					$field_value = $data['value'];
				}
				$status_indicator = '<td width="30%" class="status_parent"><strong>'.$data['label'].'</strong><img class="status-icon status-icon-action-status" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;"><div class="all_statuses"></div></td><td width="50%">'.$field_value.'</td><td width="20%"><a target="_blank" style="float: right;" href="view.php?id='.$parent_form_id.'&la_page='.$tmp_page_number.'&casecade_element_position='.$tmp_casecade_element_position.'&casecade_form_page_number='.$data['element_page_number'].'&element_id_auto='.$data['element_id_auto'].'">Go To Field</a></td>';

				$row_markup[$statusElementArrId][] = $status_indicator;
			}
		}

		return array('row_markup' => $row_markup, 'accordion_head_count_Arr' => $accordion_head_count_Arr);
	}
	
	//get an array containing values from respective table for certain id
	function la_get_entry_values($dbh, $form_id, $company_id, $entry_id) {
		$form_id = (int) $form_id;
		$company_id = isset($company_id) ? $company_id : $_SESSION['la_client_entity_id'];
		$entry_id = (int) $entry_id;

		$la_settings = la_get_settings($dbh);
		$form_elements = array();
    $form_values = array();
		
		//check if entry has been made or not
		$query = "SELECT count(id) as `total_count` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
		$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
		$row = la_do_fetch_result($sth);
		if($row['total_count'] > 0) {
			//get form elements	
			$query  = "select 
							 element_id,
							 element_type,
							 element_constraint,
							 element_matrix_allow_multiselect,
							 element_time_24hour,
							 element_default_value,
							 element_machine_code,
							 element_file_upload_synced
					     from 
					     	 `".LA_TABLE_PREFIX."form_elements` 
					    where 
					    	 form_id=? 
					 order by 
					 		 element_position asc";
			$params = array($form_id);
			
			$sth = la_do_query($query,$params,$dbh);
			$i=0;
			
			while($row = la_do_fetch_result($sth)){
				$form_elements[$i]['element_id'] 		 			   = $row['element_id'];
				$form_elements[$i]['element_type'] 		 			   = $row['element_type'];
				$form_elements[$i]['element_constraint'] 			   = $row['element_constraint'];
				$form_elements[$i]['element_matrix_allow_multiselect'] = $row['element_matrix_allow_multiselect'];
				$form_elements[$i]['element_time_24hour'] 			   = $row['element_time_24hour'];
				$form_elements[$i]['element_default_value'] 		   = $row['element_default_value'];
				$form_elements[$i]['element_machine_code'] 		   = $row['element_machine_code'];
				$form_elements[$i]['element_file_upload_synced'] = $row['element_file_upload_synced'];
				$i++;
			}
			
			//get whole entry for current company_id and entry_id
			$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
			$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
			
			while($row = la_do_fetch_result($sth)) {
				$entry_data[$row['field_name']] = $row['data_value'];
			}
			
			//get form element options
			$query = "SELECT `element_id`, `option_id`, `option` FROM ".LA_TABLE_PREFIX."element_options WHERE `form_id` = ? AND live = 1 order by option_id";
			$params = array($form_id);		
			$sth = la_do_query($query, $params, $dbh);
			
			while($row = la_do_fetch_result($sth)){
				$element_id = $row['element_id'];
				$option_id  = $row['option_id'];
				$element_option_lookup[$element_id][$option_id] = true; //array index will hold option_id
			}		
			
			//loop through each element to get the values
			foreach ($form_elements as $element){
				$element_type 		= $element['element_type'];
				$element_id   		= $element['element_id'];
				$element_constraint = $element['element_constraint'];
				$element_matrix_allow_multiselect = $element['element_matrix_allow_multiselect'];
			
				if('simple_name' == $element_type){ //Simple Name - 2 elements
					$form_values['element_'.$element_id.'_1']['default_value'] = $entry_data['element_'.$element_id.'_1'];
					$form_values['element_'.$element_id.'_2']['default_value'] = $entry_data['element_'.$element_id.'_2'];
				}
				elseif ('simple_name_wmiddle' == $element_type){ //Simple Name with Middle - 3 elements
					$form_values['element_'.$element_id.'_1']['default_value'] = $entry_data['element_'.$element_id.'_1'];
					$form_values['element_'.$element_id.'_2']['default_value'] = $entry_data['element_'.$element_id.'_2'];
					$form_values['element_'.$element_id.'_3']['default_value'] = $entry_data['element_'.$element_id.'_3'];
				}
				elseif ('name' == $element_type){ //Extended Name - 4 elements
					$form_values['element_'.$element_id.'_1']['default_value'] = $entry_data['element_'.$element_id.'_1'];
					$form_values['element_'.$element_id.'_2']['default_value'] = $entry_data['element_'.$element_id.'_2'];
					$form_values['element_'.$element_id.'_3']['default_value'] = $entry_data['element_'.$element_id.'_3'];
					$form_values['element_'.$element_id.'_4']['default_value'] = $entry_data['element_'.$element_id.'_4'];
				}
				elseif ('name_wmiddle' == $element_type){ //Name with Middle - 5 elements
					$form_values['element_'.$element_id.'_1']['default_value'] = $entry_data['element_'.$element_id.'_1'];
					$form_values['element_'.$element_id.'_2']['default_value'] = $entry_data['element_'.$element_id.'_2'];
					$form_values['element_'.$element_id.'_3']['default_value'] = $entry_data['element_'.$element_id.'_3'];
					$form_values['element_'.$element_id.'_4']['default_value'] = $entry_data['element_'.$element_id.'_4'];
					$form_values['element_'.$element_id.'_5']['default_value'] = $entry_data['element_'.$element_id.'_5'];
				}
				elseif ('time' == $element_type){ //Time - 4 elements

					//convert into time and split into 4 elements
					if(!empty($entry_data['element_'.$element_id])){
						$time_value = $entry_data['element_'.$element_id];

						if(!empty($element['element_time_24hour'])){
							$time_value = date("H/i/s/A",strtotime($time_value));
						}else{
							$time_value = date("h/i/s/A",strtotime($time_value));
						}

						$exploded = array();
						$exploded = explode('/',$time_value);
						
						$form_values['element_'.$element_id.'_1']['default_value'] = $exploded[0];
						$form_values['element_'.$element_id.'_2']['default_value'] = $exploded[1];
						$form_values['element_'.$element_id.'_3']['default_value'] = $exploded[2];
						$form_values['element_'.$element_id.'_4']['default_value'] = $exploded[3];
					}
				}
				elseif ('address' == $element_type){ //Address - 6	 elements
					$form_values['element_'.$element_id.'_1']['default_value'] = $entry_data['element_'.$element_id.'_1'];
					$form_values['element_'.$element_id.'_2']['default_value'] = $entry_data['element_'.$element_id.'_2'];
					$form_values['element_'.$element_id.'_3']['default_value'] = $entry_data['element_'.$element_id.'_3'];
					$form_values['element_'.$element_id.'_4']['default_value'] = $entry_data['element_'.$element_id.'_4'];
					$form_values['element_'.$element_id.'_5']['default_value'] = $entry_data['element_'.$element_id.'_5'];
					$form_values['element_'.$element_id.'_6']['default_value'] = $entry_data['element_'.$element_id.'_6'];
				}
				elseif ('money' == $element_type){ //Price
					if($element_constraint == 'yen'){ //yen only has 1 element
						$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];
					}else{ //other has 2 fields
						$exploded = array();
						$exploded = explode('.',$entry_data['element_'.$element_id]);
						
						$form_values['element_'.$element_id.'_1']['default_value'] = $exploded[0];
						$form_values['element_'.$element_id.'_2']['default_value'] = $exploded[1];
					}		
				}
				elseif ('date' == $element_type){  //date with format MM/DD/YYYY
					if(!empty($entry_data['element_'.$element_id]) && ($entry_data['element_'.$element_id] != '0000-00-00')){
						$date_value = $entry_data['element_'.$element_id];
						$date_value = date("m/d/Y",strtotime($date_value));
						
						$exploded = array();
						$exploded = explode('/',$date_value);
		
						$form_values['element_'.$element_id.'_1']['default_value'] = $exploded[0];
						$form_values['element_'.$element_id.'_2']['default_value'] = $exploded[1];
						$form_values['element_'.$element_id.'_3']['default_value'] = $exploded[2];
					}
				}
				elseif ('europe_date' == $element_type){  //date with format DD/MM/YYYY
					if(!empty($entry_data['element_'.$element_id]) && ($entry_data['element_'.$element_id] != '0000-00-00')){
						$date_value = $entry_data['element_'.$element_id];
						$date_value = date("d/m/Y",strtotime($date_value));
						
						$exploded = array();
						$exploded = explode('/',$date_value);
		
						$form_values['element_'.$element_id.'_1']['default_value'] = $exploded[0];
						$form_values['element_'.$element_id.'_2']['default_value'] = $exploded[1];
						$form_values['element_'.$element_id.'_3']['default_value'] = $exploded[2];
					}
				}
				elseif ('phone' == $element_type){ //Phone - 3 elements
					$phone_value = $entry_data['element_'.$element_id];
					$phone_1 = substr($phone_value,0,3);
					$phone_2 = substr($phone_value,3,3);
					$phone_3 = substr($phone_value,-4);
					
					$form_values['element_'.$element_id.'_1']['default_value'] = $phone_1;
					$form_values['element_'.$element_id.'_2']['default_value'] = $phone_2;
					$form_values['element_'.$element_id.'_3']['default_value'] = $phone_3;
				}
				elseif ('checkbox' == $element_type){ //Checkbox - multiple elements
					$checkbox_childs = $element_option_lookup[$element_id];
					
					if(!empty($checkbox_childs)){
						foreach ($checkbox_childs as $option_id=>$dumb){
							$form_values['element_'.$element_id.'_'.$option_id]['default_value'] = $entry_data['element_'.$element_id.'_'.$option_id];
						}
					}
					
					if(!empty($entry_data['element_'.$element_id.'_other'])){
						$form_values['element_'.$element_id.'_other']['default_value'] = $entry_data['element_'.$element_id.'_other'];
					}
				}
				elseif ('file' == $element_type){ //File 
					//if synced upload is enabled
					if($element['element_file_upload_synced'] && $element['element_machine_code'] != '') {
						$filename_array  = array();
						$query_synced_file = "SELECT `files_data` FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE `company_id` = ? AND `element_machine_code` = ?";
						$sth_synced_file = la_do_query($query_synced_file, array($company_id, $element['element_machine_code']), $dbh);
						$row_synced_file = la_do_fetch_result($sth_synced_file);

						if(!empty($row_synced_file['files_data'])) {
							$filename_array  = json_decode($row_synced_file['files_data']);
						}

						if(!empty($filename_array)){
							$i=0;
							
							foreach ($filename_array as $filename_value) {
								$file_size = 10;
								$form_values['element_'.$element_id]['default_value'][$i]['filename'] = $filename_value ;
								$form_values['element_'.$element_id]['default_value'][$i]['filesize'] = $file_size;
								$form_values['element_'.$element_id]['default_value'][$i]['entry_id'] = $entry_id;
								$i++;
							}
						}
					} else {
						$filename_record = $entry_data['element_'.$element_id];
						$filename_array  = array();
						
						if(!empty($filename_record)) {
							$filename_array  = explode('|',$filename_record);
						}
						
						if(!empty($filename_array)){
							$i=0;
							
							foreach ($filename_array as $filename_value) {
								$file_size = 10;
								$form_values['element_'.$element_id]['default_value'][$i]['filename'] = $filename_value ;
								$form_values['element_'.$element_id]['default_value'][$i]['filesize'] = $file_size;
								$form_values['element_'.$element_id]['default_value'][$i]['entry_id'] = $entry_id;
								$i++;
							}
						}
					}
				}
				else if ('matrix' == $element_type) {
					if(!empty($element_matrix_allow_multiselect)){ //this is checkboxes matrix
						$temp_matrix_child_element_id_array = explode(',',trim($element_constraint));
						array_unshift($temp_matrix_child_element_id_array, $element_id);
							
						foreach ($temp_matrix_child_element_id_array as $mc_element_id){
							$sub_query = "select 
											option_id 
										from 
											".LA_TABLE_PREFIX."element_options 
									   where 
									   		form_id=? and element_id=? and live=1 
									order by 
											`option_id`";
							$params = array($form_id,$mc_element_id);
								
							$sub_sth = la_do_query($sub_query,$params,$dbh);
							while($sub_row = la_do_fetch_result($sub_sth)){
								$element_to_get = "element_{$mc_element_id}_{$sub_row['option_id']}";
								$form_values[$element_to_get]['default_value'] = $entry_data[$element_to_get];
							}	
						}
					}else{
						$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];
					}
				}
				else if('radio' == $element_type){ 
					$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];

					if(!empty($entry_data['element_'.$element_id.'_other']) && empty($entry_data['element_'.$element_id])){
						$form_values['element_'.$element_id.'_other']['default_value'] = $entry_data['element_'.$element_id.'_other'];
					}
				}
				else if('signature' == $element_type){ 
					$form_values['element_'.$element_id]['default_value'] = htmlspecialchars_decode($entry_data['element_'.$element_id],ENT_QUOTES);
				}
				else if('casecade_form' == $element_type){
					if($element['element_default_value']){ 
						$form_values['element_'.$element_id][$element['element_default_value']] = la_get_entry_values($dbh, $element['element_default_value'], $company_id, $entry_id);
					}
				}
				else{ //element with only 1 input
					$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];
				}
			}
		}

		return $form_values;
	}

	//get an array containing values from respective table for certain id
	//similar to la_get_entry_values() function, but this one is higher level and include labels
	function la_get_entry_details($dbh, $form_id, $company_id, $entry_id, $options=array()){
		$form_id = (int) $form_id;
		$company_id = (int) $company_id;
		$entry_id = (int) $entry_id;
		$la_settings = la_get_settings($dbh);

		$admin_clause = '';
		if(!empty($options['review_mode'])){ //hide admin fields in review page
			$admin_clause = ' and element_is_private=0 ';
		}

		if(!empty($options['checkbox_image'])){
			$checkbox_image = $options['checkbox_image'];
		}else{
			$checkbox_image = $options['itauditmachine_path'].'images/icons/59_blue_16.png';
		}

		//get form elements	
		$query  = "select
						 id,
						 element_id,
						 element_type,
						 element_constraint,
						 element_title,
						 element_size,
						 element_file_as_attachment,
						 element_time_showsecond,
						 element_time_24hour,
						 element_section_display_in_email,
						 element_guidelines,
						 element_default_value,
						 element_page_number,
						 element_file_upload_synced,
						 element_machine_code,
						 element_status_indicator,
						 (select if(element_matrix_parent_id=0,
							 		element_matrix_allow_multiselect,
									(select 
											B.element_matrix_allow_multiselect 
									   from 
									   		".LA_TABLE_PREFIX."form_elements B 
									  where 
									  		B.form_id=A.form_id and 
									  		B.element_id=A.element_matrix_parent_id
									)
								 )
						 ) matrix_multiselect_status  
					 from 
					 	 `".LA_TABLE_PREFIX."form_elements` A
					where 
						 form_id=? and 
						 element_status = 1 
						 {$admin_clause} 
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$i=0;
		while($row = la_do_fetch_result($sth)){

			//if this is section break and doesn't have option to be displayed in email, skip it
			if($row['element_type'] == 'section' && empty($row['element_section_display_in_email'])){
				continue;
			}
			$form_elements[$i]['element_id_auto'] 	 = $row['id'];
			$form_elements[$i]['element_id'] 		 = $row['element_id'];
			$form_elements[$i]['element_type'] 		 = $row['element_type'];
			$form_elements[$i]['element_size'] 		 = $row['element_size'];
			$form_elements[$i]['element_constraint'] = $row['element_constraint'];
			$form_elements[$i]['element_guidelines'] = $row['element_guidelines'];
			$form_elements[$i]['element_file_as_attachment'] = $row['element_file_as_attachment'];
			$form_elements[$i]['element_time_showsecond'] = $row['element_time_showsecond'];
			$form_elements[$i]['element_time_24hour'] 	  = $row['element_time_24hour'];
			$form_elements[$i]['element_default_value'] = $row['element_default_value'];
			$form_elements[$i]['element_page_number'] = $row['element_page_number'];
			$form_elements[$i]['element_matrix_allow_multiselect'] = $row['matrix_multiselect_status'];
			$form_elements[$i]['element_file_upload_synced'] = $row['element_file_upload_synced'];
			$form_elements[$i]['element_machine_code'] = $row['element_machine_code'];
			$form_elements[$i]['element_status_indicator'] = $row['element_status_indicator'];
			
			//store element title into array for reference later
			$element_title_lookup[$row['element_id']] = $row['element_title'];
			
			$i++;
		}

		$query  = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id = ? AND entry_id = ?";
		$sth = la_do_query($query,array($company_id, $entry_id),$dbh);
		while($row = la_do_fetch_result($sth)){
			$entry_data[$row['field_name']] = htmlspecialchars($row['data_value'],ENT_QUOTES);
			$entry_data[$row['field_name'].'_score'] = end(explode(",", trim($row['field_score'])));
		}

		//get form element options
		$query = "select element_id,option_id,`option` from ".LA_TABLE_PREFIX."element_options where form_id=? and live=1 order by position asc";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$element_option_lookup[$element_id][$option_id] = $row['option']; //array index will hold option_id
		}
		
		//get element options for matrix fields
		$query = "select DISTINCT A.element_id, A.option_id, A.option as option_label
					from 
						".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
					where  
						A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
					order by 
						A.element_id,A.option_id asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$matrix_element_option_lookup[$element_id][$option_id] = htmlspecialchars($row['option_label'],ENT_QUOTES);
		}

		//loop through each element to get the values
		$i = 0;
		foreach ($form_elements as $element){
			$element_id_auto    = $element['element_id_auto'];
			$element_type 		= $element['element_type'];
			$element_id   		= $element['element_id'];
			$element_constraint = $element['element_constraint'];
			$element_size 		= $element['element_size'];
			$element_guidelines 		= $element['element_guidelines'];
			$element_file_as_attachment = $element['element_file_as_attachment'];
			$element_time_24hour 		= $element['element_time_24hour'];
			$element_time_showsecond 	= $element['element_time_showsecond'];
			$element_matrix_allow_multiselect = $element['element_matrix_allow_multiselect'];
			$element_page_number = $element['element_page_number'];
			$element_file_upload_synced = $element['element_file_upload_synced'];
            $element_machine_code = $element['element_machine_code'];
            $element_status_indicator = $element['element_status_indicator'];

			$entry_details[$i]['label'] = $element_title_lookup[$element_id];
			$entry_details[$i]['value'] = ($element['element_type'] == 'casecade_form') ? $element['element_default_value'] : '&nbsp;'; //default value
			$entry_details[$i]['element_id'] 	= $element_id;
			$entry_details[$i]['element_type'] 	= $element_type;
			$entry_details[$i]['element_page_number'] 	= $element_page_number;
			$entry_details[$i]['element_id_auto'] 	= $element_id_auto;
			$entry_details[$i]['element_status_indicator'] 	= $element_status_indicator;
			// This variable is for plain text email format
			$entry_details[$i]['plain_text_value'] = "";			
			
			if('simple_name' == $element_type){ //Simple Name - 2 elements
				$simple_name_value = trim($entry_data['element_'.$element_id.'_1'].' '.$entry_data['element_'.$element_id.'_2']);
				if(!empty($simple_name_value)){
					$entry_details[$i]['value'] = $simple_name_value;
				}
				$entry_details[$i]['plain_text_value'] = trim($simple_name_value);
			}elseif ('simple_name_wmiddle' == $element_type){ //Simple Name with Middle - 3 elements
				$simple_name_wmiddle_value = trim($entry_data['element_'.$element_id.'_1'].' '.$entry_data['element_'.$element_id.'_2'].' '.$entry_data['element_'.$element_id.'_3']);
				if(!empty($simple_name_wmiddle_value)){
					$entry_details[$i]['value'] = $simple_name_wmiddle_value;
				}
				$entry_details[$i]['plain_text_value'] = trim($simple_name_wmiddle_value);
			}elseif ('name' == $element_type){ //Extended Name - 4 elements
				$name_value = trim($entry_data['element_'.$element_id.'_1'].' '. $entry_data['element_'.$element_id.'_2'].' '.$entry_data['element_'.$element_id.'_3'].' '.$entry_data['element_'.$element_id.'_4']);
				if(!empty($name_value)){
					$entry_details[$i]['value'] = $name_value;
				}
				$entry_details[$i]['plain_text_value'] = trim($name_value);
			}elseif ('name_wmiddle' == $element_type){ //Extended Name  with Middle- 5 elements
				$name_wmiddle_value = trim($entry_data['element_'.$element_id.'_1'].' '. $entry_data['element_'.$element_id.'_2'].' '.$entry_data['element_'.$element_id.'_3'].' '.$entry_data['element_'.$element_id.'_4'].' '.$entry_data['element_'.$element_id.'_5']);
				if(!empty($name_wmiddle_value)){
					$entry_details[$i]['value'] = $name_wmiddle_value;
				}
				$entry_details[$i]['plain_text_value'] = trim($name_wmiddle_value);
			}elseif ('time' == $element_type){ //Time - 4 elements
				//convert into time and split into 4 elements
				if(!empty($entry_data['element_'.$element_id])){
					$time_value = $entry_data['element_'.$element_id];
					
					if(!empty($element_time_24hour)){
						if(!empty($element_time_showsecond)){
							$time_value = date("H:i:s",strtotime($time_value));
						}else{
							$time_value = date("H:i",strtotime($time_value));
						}
					}else{
						if(!empty($element_time_showsecond)){
							$time_value = date("h:i:s A",strtotime($time_value));
						}else{
							$time_value = date("h:i A",strtotime($time_value));
						}
					}
					
					$entry_details[$i]['value'] = $time_value;
					$entry_details[$i]['plain_text_value'] = trim($time_value);
				}
			}elseif ('address' == $element_type){ //Address - 6	 elements
								
				if(!empty($entry_data['element_'.$element_id.'_3'])){
					$entry_data['element_'.$element_id.'_3'] = $entry_data['element_'.$element_id.'_3'].',';
				}
				
				$entry_details[$i]['value'] = $entry_data['element_'.$element_id.'_1'].' '.$entry_data['element_'.$element_id.'_2'].'<br />'.$entry_data['element_'.$element_id.'_3'].' '.$entry_data['element_'.$element_id.'_4'].' '.$entry_data['element_'.$element_id.'_5'].'<br />'.$entry_data['element_'.$element_id.'_6'];
				
				//if empty, shows blank instead of breaks
				if(trim(str_replace("<br />","",$entry_details[$i]['value'])) == ""){
					$entry_details[$i]['value'] = '&nbsp;';
				}
				
				$entry_details[$i]['plain_text_value'] = trim(str_replace("<br />","",$entry_details[$i]['value']));											  
			}elseif ('money' == $element_type){ //Price
				switch ($element_constraint){
					case 'pound'  : $currency = '&#163;';break;
					case 'euro'   : $currency = '&#8364;';break;
					case 'yen' 	  : $currency = '&#165;';break;
					case 'baht'   : $currency = '&#3647;';break;
					case 'rupees' : $currency = 'Rs';break;
					case 'rand'   : $currency = 'R';break;
					case 'forint' : $currency = '&#70;&#116;';break;
					case 'franc'  : $currency = 'CHF';break;
					case 'koruna' : $currency = '&#75;&#269;';break;
					case 'krona'  : $currency = 'kr';break;
					case 'pesos'  : $currency = '&#36;';break;
					case 'ringgit' : $currency = 'RM';break;
					case 'zloty'  : $currency = '&#122;&#322;';break;
					case 'riyals' : $currency = '&#65020;';break;
					default : $currency = '$';break;	
				}
				
				if(!empty($entry_data['element_'.$element_id]) || $entry_data['element_'.$element_id] === 0 || $entry_data['element_'.$element_id] === '0'){
					$entry_details[$i]['value'] = $currency.$entry_data['element_'.$element_id];
					$entry_details[$i]['plain_text_value'] = trim($currency.$entry_data['element_'.$element_id]);
				}
						
			}elseif ('date' == $element_type){  //date with format MM/DD/YYYY
				if(!empty($entry_data['element_'.$element_id]) && ($entry_data['element_'.$element_id] != '0000-00-00')){
					$date_value = $entry_data['element_'.$element_id];
					$date_value = date("m/d/Y", strtotime($date_value));
					
					$entry_details[$i]['value'] = $date_value;
					$entry_details[$i]['plain_text_value'] = trim($date_value);
				}
				
			}elseif ('europe_date' == $element_type){  //date with format DD/MM/YYYY
				if(!empty($entry_data['element_'.$element_id]) && ($entry_data['element_'.$element_id] != '0000-00-00')){
					$date_value = $entry_data['element_'.$element_id];
					$date_value = date("d/m/Y",strtotime($date_value));
					
					$entry_details[$i]['value'] = $date_value;
					$entry_details[$i]['plain_text_value'] = trim($date_value);
				}
				
			}elseif ('phone' == $element_type){ //Phone - 3 elements
				
				$phone_value = $entry_data['element_'.$element_id];
				$phone_1 = substr($phone_value,0,3);
				$phone_2 = substr($phone_value,3,3);
				$phone_3 = substr($phone_value,-4);
				
				if(!empty($phone_value)){
					$entry_details[$i]['value'] = "($phone_1) {$phone_2}-{$phone_3}";
					$entry_details[$i]['plain_text_value'] = trim("($phone_1) {$phone_2}-{$phone_3}");
				}
							
			}elseif ('checkbox' == $element_type){ //Checkbox - multiple elements
				$checkbox_childs = $element_option_lookup[$element_id];
								
				$checkbox_content = '';
				if($checkbox_childs){
					foreach ($checkbox_childs as $option_id=>$option_label){
						if(!empty($entry_data['element_'.$element_id.'_'.$option_id])){
							if(empty($options['strip_checkbox_image'])){
								$checkbox_content .= '<img src="'.$checkbox_image.'" align="absmiddle" /> '.$option_label." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_'.$option_id.'_score']}\">(Score: {$entry_data['element_'.$element_id.'_'.$option_id.'_score']})</span>".'<br />';
							}else{
								$checkbox_content .= '- '.$option_label." (Score: {$entry_data['element_'.$element_id.'_'.$option_id.'_score']})".'<br />';
							}
							
							$entry_details[$i]['plain_text_value'] .= trim($option_label." (Score: {$entry_data['element_'.$element_id.'_'.$option_id.'_score']}),");
						}
					}
				}

				if(!empty($entry_data['element_'.$element_id.'_other'])){
					
					if(empty($options['strip_checkbox_image'])){
						$checkbox_content .= '<img src="'.$checkbox_image.'" align="absmiddle" /> '.$entry_data['element_'.$element_id.'_other']." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_other_score']}\">(Score: {$entry_data['element_'.$element_id.'_other_score']})</span>".'<br />';
					}else{
						$checkbox_content .= '- '.$entry_data['element_'.$element_id.'_other']." (Score: {$entry_data['element_'.$element_id.'_other_score']})".'<br />';
					}
					
					$entry_details[$i]['plain_text_value'] .= trim($entry_data['element_'.$element_id.'_other']);
				}				
				
				if(!empty($checkbox_content)){
					$entry_details[$i]['value'] = $checkbox_content;
				}
			}elseif ('file' == $element_type){ //File
				if ( $element_file_upload_synced == 1 && !empty($element_machine_code) ) {
					//get data for this machine code
					$filename_array  = array();
					$plain_filename_array  = array();
					$files_data_sql = "select `files_data` from `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? and company_id = ?;";
					$files_data_res = la_do_query($files_data_sql,array($element_machine_code, $company_id),$dbh);
					$files_data_row = la_do_fetch_result($files_data_res);

					$files_arr = [];
					if( $files_data_row['files_data'] ) {

						$filename_array = json_decode($files_data_row['files_data']);

						$entry_details[$i]['value'] = '';
                        $j = 0 ;
    					$ssl_suffix = la_get_ssl_suffix();

						foreach($filename_array as $filename_value){
							$filename_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/file_upload_synced/{$element_machine_code}/{$filename_value}";							
							if(file_exists($filename_source)) {
                            	$encoded_file_name = urlencode($filename_value);
								$filename_path = "{$la_settings['base_url']}data/file_upload_synced/{$element_machine_code}/{$filename_value}";
                            	$filename_path = str_replace("%", "%25", $filename_path);
								$filename_path = str_replace("#", "%23", $filename_path);
								array_push($plain_filename_array, trim($encoded_file_name));
	                            $file_1 	    = substr($filename_value,strpos($filename_value,'-'));
	                            $file_name_mod = substr($file_1,strpos($file_1,'-'));
								$file_name_mod = strpos($file_name_mod, "-") !== false ? ltrim($file_name_mod, "-") : $file_name_mod;
								
								$filename_ext   = end(explode(".", $encoded_file_name));

								$q_string = base64_encode("element_machine_code={$element_machine_code}&file_name={$encoded_file_name}&call_type=ajax_synced");
										
								if(in_array(strtolower($filename_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){

									$entry_details[$i]['value'] .= '<div style="width:100%; margin:5px;"><img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a style="display:inline-block;" class="entry_link entry-link-preview" href="#" data-identifier="image_format" data-ext="'.$filename_ext.'" data-src="'.$filename_path.'"><img src="'.$filename_path.'" align="absmiddle" style="vertical-align: middle; width: 120px;" /></a></div>';//echo "7<br>";
									
								}else{
									$entry_details[$i]['value'] .= '<img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$filename_ext.'" data-src="'.$q_string.'">'.$file_name_mod.'</a><br/>';//echo "8<br>";
								}
								
	                            if(!empty($element_file_as_attachment)){//echo "9<br>";
                                    $entry_details[$i]['filedata'][$j]['filename_path']  = $filename_path;
                                    $entry_details[$i]['filedata'][$j]['filename_value'] = $filename_value;
                                }
	                            $j++;
							}
						}
						$entry_details[$i]['plain_text_value'] = implode(" ", $plain_filename_array);
					}
				} else {
				
					$filename_record = htmlspecialchars_decode($entry_data['element_'.$element_id],ENT_QUOTES);
					$filename_array  = array();
					$plain_filename_array  = array();
					
					if(!empty($filename_record)){
						$filename_array  = explode('|',$filename_record);
					}
					
					if(!empty($filename_array)){
						$entry_details[$i]['value'] = '';
						$j = 0 ;

						foreach($filename_array as $filename_value){
							$filename_source = "{$_SERVER['DOCUMENT_ROOT']}/auditprotocol/data/form_{$form_id}/files/{$filename_value}";
                            if(file_exists($filename_source)) {
                            	$encoded_file_name = urlencode($filename_value);
                            	$filename_path = "{$la_settings['base_url']}data/form_{$form_id}/files/{$filename_value}";
                            	$filename_path = str_replace("%", "%25", $filename_path);
								$filename_path = str_replace("#", "%23", $filename_path);
                            	array_push($plain_filename_array, trim($encoded_file_name));
                            	$file_size = @la_format_bytes(filesize($filename_path));
                            
	                            $file_1 	    = substr($filename_value,strpos($filename_value,'-'));
	                            $file_name_mod = substr($file_1,strpos($file_1,'-'));
								$file_name_mod = strpos($file_name_mod, "-") !== false ? ltrim($file_name_mod, "-") : $file_name_mod;
								
								$filename_ext   = end(explode(".", $encoded_file_name));
	                            
	                            $q_string = base64_encode("form_id={$form_id}&file_name={$encoded_file_name}&call_type=ajax_normal");
								if(in_array(strtolower($filename_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
									$entry_details[$i]['value'] .= '<div style="width:100%; margin:5px;"><img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a style="display:inline-block;" class="entry_link entry-link-preview" href="#" data-identifier="image_format" data-ext="'.$filename_ext.'" data-src="'.$filename_path.'"><img src="'.$filename_path.'" align="absmiddle" style="vertical-align: middle; width: 120px;" /></a></div>';//echo "7<br>";
									
								}else{
									$entry_details[$i]['value'] .= '<img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$filename_ext.'" data-src="'.$q_string.'">'.$file_name_mod.'</a><br/>';//echo "8<br>";
								}
	                            if(!empty($element_file_as_attachment)){//echo "9<br>";
                                    $entry_details[$i]['filedata'][$j]['filename_path']  = $filename_path;
                                    $entry_details[$i]['filedata'][$j]['filename_value'] = $filename_value;
                                }
	                            $j++;
                            }
                        }	                        
						$entry_details[$i]['plain_text_value'] = implode(" ", $plain_filename_array);
					}
				}
				
			}elseif('select' == $element_type){
				if(!empty($entry_data['element_'.$element_id])){
                    $entry_details[$i]['value'] = $element_option_lookup[$element_id][$entry_data['element_'.$element_id]]." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_score']}\">(Score: {$entry_data['element_'.$element_id.'_score']})</span>";
					$entry_details[$i]['plain_text_value'] = trim($element_option_lookup[$element_id][$entry_data['element_'.$element_id]]." (Score: {$entry_data['element_'.$element_id.'_score']})");
				}
			}elseif('section' == $element_type){
				if(!empty($element_guidelines)){
					$entry_details[$i]['value'] = $element_guidelines;
					$entry_details[$i]['plain_text_value'] = $element_guidelines;
				}
			}elseif('radio' == $element_type){
				if(!empty($entry_data['element_'.$element_id])){
					$entry_details[$i]['plain_text_value'] = $element_option_lookup[$element_id][$entry_data['element_'.$element_id]]."(Score: {$entry_data['element_'.$element_id.'_score']})";
                    $entry_details[$i]['value'] = $element_option_lookup[$element_id][$entry_data['element_'.$element_id]]." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_score']}\">(Score: {$entry_data['element_'.$element_id.'_score']})</span>";
                }else{
                    if(!empty($entry_data['element_'.$element_id.'_other'])){
						$entry_details[$i]['plain_text_value'] = $entry_data['element_'.$element_id.'_other']."(Score: {$entry_data['element_'.$element_id.'_other_score']})";
                        $entry_details[$i]['value'] = $entry_data['element_'.$element_id.'_other']." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_other_score']}\">(Score: {$entry_data['element_'.$element_id.'_other_score']})</span>";
                    }else{
                        $entry_details[$i]['value'] = '&nbsp;';
                    }
                }
			}elseif('matrix' == $element_type){
				if(!empty($element_matrix_allow_multiselect)){ //this is checkbox matrix
                    $checkbox_childs = $element_option_lookup[$element_id];
                    
					$checkBoxArr = array();
                    $checkbox_content = '';
                    foreach ($checkbox_childs as $option_id=>$option_label){
                        if(!empty($entry_data['element_'.$element_id.'_'.$option_id])){
                            if(empty($options['strip_checkbox_image'])){
                                $checkbox_content .= '<img src="'.$checkbox_image.'" align="absmiddle" /> '.$option_label." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_'.$option_id.'_score']}\">(Score: {$entry_data['element_'.$element_id.'_'.$option_id.'_score']})</span>".'<br />';
                            }else{
                                $checkbox_content .= '- '.$option_label.'<br />';
                            }
							
							array_push($checkBoxArr, $option_label." (Score: {$entry_data['element_'.$element_id.'_'.$option_id.'_score']})");
                        }
                    }
					
					$entry_details[$i]['plain_text_value'] = implode(",", $checkBoxArr);

                    if(!empty($entry_data['element_'.$element_id.'_other'])){
                        $checkbox_content .= '<img src="'.$checkbox_image.'" align="absmiddle" /> '.$entry_data['element_'.$element_id.'_other'];
                    }				
                    
                    if(!empty($checkbox_content)){
                        $entry_details[$i]['value'] = $checkbox_content;
                    }
                }else{ //this is radio matrix
                    
                    if(!empty($entry_data['element_'.$element_id])){
						$entry_details[$i]['plain_text_value'] = $matrix_element_option_lookup[$element_id][$entry_data['element_'.$element_id]]." (Score: {$entry_data['element_'.$element_id.'_score']})";
                        $entry_details[$i]['value'] = $matrix_element_option_lookup[$element_id][$entry_data['element_'.$element_id]]." <span class=\"score-span\" data-score-value=\"{$entry_data['element_'.$element_id.'_score']}\">(Score: {$entry_data['element_'.$element_id.'_score']})</span>";
                    }else{
                        $entry_details[$i]['value'] = '&nbsp;';	
                    }
                }
			}elseif ('url' == $element_type){
				if(!empty($entry_data['element_'.$element_id])){
					$entry_details[$i]['value'] = "<a class=\"entry_link\" href=\"{$entry_data['element_'.$element_id]}\">{$entry_data['element_'.$element_id]}</a>";
					$entry_details[$i]['plain_text_value'] = $entry_data['element_'.$element_id];
				}
			}elseif('page_break' == $element_type){
				$entry_details[$i]['value'] = 'la_page_break';
				$entry_details[$i]['label'] = 'la_page_break';
				$entry_details[$i]['plain_text_value'] = 'la_page_break';
			}else if('signature' == $element_type){ 
				if(isset($entry_data['element_'.$element_id])){
					$entry_details[$i]['value'] = htmlspecialchars_decode($entry_data['element_'.$element_id],ENT_QUOTES);
					$entry_details[$i]['element_size'] = $element_size;
					$entry_details[$i]['plain_text_value'] = htmlspecialchars_decode($entry_data['element_'.$element_id],ENT_QUOTES);
				}
			}elseif($element['element_type'] == 'casecade_form' && $options['email_entry_call'] == true){
      	$entry_details[$i]['value'] = la_get_entry_details($dbh,$element['element_default_value'],$company_id, $entry_id,$options);
      } else{ //element with only 1 input
                if(isset($entry_data['element_'.$element_id])){
                    $entry_details[$i]['value'] = $entry_data['element_'.$element_id];
					$entry_details[$i]['plain_text_value'] = $entry_data['element_'.$element_id];
                }
            }
			
			$i++;
		}
		
		return $entry_details;
	}
	
	//display a table which contain entries of a selected form
	function la_display_entries_table($dbh,$form_id,$options){

		$form_id = (int) $form_id;

		$max_data_length = 80; //maximum length of column content
		$pageno 	   = $options['page_number'];
		$rows_per_page = $options['rows_per_page'];
		$sort_element  = $options['sort_element'];
		$sort_order	   = $options['sort_order'];
		$filter_data   = $options['filter_data'];
		$filter_type   = $options['filter_type'];
		$user_id 	   = $options['column_preferences_user_id'];
		$display_incomplete_entries = $options['display_incomplete_entries'];

		if(empty($sort_element)){ //set the default sorting order
			$sort_element = 'id';
			$sort_order	  = 'desc';
		}

		$form_properties = la_get_form_properties($dbh,$form_id,array('payment_currency','payment_enable_merchant'));
		$payment_currency = strtoupper($form_properties['payment_currency']);

		/******************************************************************************************/
		//prepare column header names lookup

		//get form element options first (checkboxes, choices, dropdown)
		$query = "select 
						element_id,
						option_id,
						`option`
					from 
						".LA_TABLE_PREFIX."element_options 
				   where 
				   		form_id=? and live=1 
				order by 
						element_id,position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$element_option_lookup[$element_id][$option_id] = htmlspecialchars(strip_tags($row['option']),ENT_QUOTES);
		}

		//get element options for matrix fields
		/*$query = "select 
						A.element_id,
						A.option_id,
						(select if(B.element_matrix_parent_id=0,A.option,
							(select 
									C.`option` 
							   from 
							   		".LA_TABLE_PREFIX."element_options C 
							  where 
							  		C.element_id=B.element_matrix_parent_id and 
							  		C.form_id=A.form_id and 
							  		C.live=1 and 
							  		C.option_id=A.option_id))
						) 'option_label'
					from 
						".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
				   where 
				   		A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
				order by 
						A.element_id,A.option_id asc";*/
						
		$query = "select DISTINCT 
					A.element_id,
					A.option_id,
					(select if(B.element_matrix_parent_id=0,A.option,
						(select DISTINCT 
								C.`option` 
						   from 
						   		".LA_TABLE_PREFIX."element_options C 
						  where 
						  		C.element_id=B.element_matrix_parent_id and 
						  		C.form_id=A.form_id and 
						  		C.live=1 and 
						  		C.option_id=A.option_id))
					) 'option_label'
				from 
					".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
			   where 
			   		A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
			order by 
					A.element_id,A.option_id asc";
						
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$matrix_element_option_lookup[$element_id][$option_id] = htmlspecialchars(strip_tags($row['option_label']),ENT_QUOTES);
		}
		
		//get 'multiselect' status of matrix fields
		$query = "select 
						  A.element_id,
						  A.element_matrix_parent_id,
						  A.element_matrix_allow_multiselect,
						  (select if(A.element_matrix_parent_id=0,A.element_matrix_allow_multiselect,
						  			 (select B.element_matrix_allow_multiselect from ".LA_TABLE_PREFIX."form_elements B where B.form_id=A.form_id and B.element_id=A.element_matrix_parent_id)
						  			)
						  ) 'multiselect' 
					  from 
					 	  ".LA_TABLE_PREFIX."form_elements A
					 where 
					 	  A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
		}


		/******************************************************************************************/
		//set column properties for basic fields
		$column_name_lookup['date_created']   = 'Date Created';
		$column_name_lookup['date_updated']   = 'Date Updated';
		$column_name_lookup['ip_address'] 	  = 'IP Address';
		
		$column_type_lookup['id'] 			= 'number';
		$column_type_lookup['row_num']		= 'number';
		$column_type_lookup['date_created'] = 'date';
		$column_type_lookup['date_updated'] = 'date';
		$column_type_lookup['ip_address'] 	= 'text';
		

		if($form_properties['payment_enable_merchant'] == 1){
			$column_name_lookup['payment_amount'] = 'Payment Amount';
			$column_name_lookup['payment_status'] = 'Payment Status';
			$column_name_lookup['payment_id']	  = 'Payment ID';
			
			$column_type_lookup['payment_amount'] = 'money';
			$column_type_lookup['payment_status'] = 'payment_status';
			$column_type_lookup['payment_id']	  = 'text';
		}
		
		//get column properties for other fields
		$query  = "select 
						 element_id,
						 element_title,
						 element_type,
						 element_constraint,
						 element_choice_has_other,
						 element_choice_other_label,
						 element_time_showsecond,
						 element_time_24hour,
						 element_matrix_allow_multiselect  
				     from 
				         `".LA_TABLE_PREFIX."form_elements` 
				    where 
				    	 form_id=? and element_status=1 and element_type not in('section','page_break')
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$element_radio_has_other = array();

		while($row = la_do_fetch_result($sth)){

			$element_type 	    = $row['element_type'];
			$element_constraint = $row['element_constraint'];
			

			//get 'other' field label for checkboxes and radio button
			if($element_type == 'checkbox' || $element_type == 'radio'){
				if(!empty($row['element_choice_has_other'])){
					$element_option_lookup[$row['element_id']]['other'] = htmlspecialchars(strip_tags($row['element_choice_other_label']),ENT_QUOTES);
				
					if($element_type == 'radio'){
						$element_radio_has_other['element_'.$row['element_id']] = true;	
					}
				}
			}

			$row['element_title'] = htmlspecialchars(strip_tags($row['element_title']),ENT_QUOTES);

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
				
				foreach ($this_checkbox_options as $option_id=>$option){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
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
			}else if('signature' == $element_type){
				//don't display signature field
				continue;
			}else{ //for other elements with only 1 field
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
			}

			
		}
		/******************************************************************************************/
		
		
		//get column preferences and store it into array
		if($display_incomplete_entries === true){
			$incomplete_status = 1;
		}else{
			$incomplete_status = 0;
		}

		$query = "select element_name from ".LA_TABLE_PREFIX."column_preferences where form_id=? and user_id=? and incomplete_entries=? order by position asc";
		$params = array($form_id,$user_id,$incomplete_status);
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['element_name'] == 'id'){
				continue;
			}
			$column_prefs[] = $row['element_name'];
		}


		//if there is no column preferences, display the first 6 fields
		if(empty($column_prefs)){
			$temp_slice = array_slice($column_name_lookup,0,8);
			unset($temp_slice['date_updated']);
			unset($temp_slice['ip_address']);
			$column_prefs = array_keys($temp_slice);
		}
		
		//determine column labels
		//the first 2 columns are always id and row_num
		$column_labels = array();

		$column_labels[] = 'la_id';
		$column_labels[] = 'la_row_num';
		
		foreach($column_prefs as $column_name){
			$column_labels[] = $column_name_lookup[$column_name];
		}

		$payment_table_columns = array('payment_amount','payment_status','payment_id');

		//determine if the filter data contain payment fields or not
		$filter_has_payment_field = false;
		if(!empty($filter_data)){
			foreach ($filter_data as $value) {
				$element_name = $value['element_name'];
				if(in_array($element_name, $payment_table_columns)){
					$filter_has_payment_field = true;
					break;
				}
			}
		}

		$sort_element_is_payment_field = false;
		if(in_array($sort_element, $payment_table_columns)){
			$sort_element_is_payment_field = true;
		}

		//get the entries from ap_form_x table and store it into array
		//but first we need to check if there is any column preferences from ap_form_payments table
		$payment_columns_prefs = array_intersect($payment_table_columns, $column_prefs);

		//if the user doesn't select any payment fields as a preference but the filter data or sorting preference refer to one of them, we need to manually include the payment fields as preference
		if((empty($payment_columns_prefs) && $filter_has_payment_field === true) || ($sort_element_is_payment_field === true)){
			$payment_columns_prefs = array('payment_amount','payment_status','payment_id');
		}

		if(!empty($payment_columns_prefs)){
			//there is one or more column from ap_form_payments
			//don't include this column into $column_prefs_joined variable
			$column_prefs_temp = array();
			foreach ($column_prefs as $value) {
				if(in_array($value, $payment_table_columns)){
					continue;
				}
				$column_prefs_temp[] = $value;
			}

			if(!empty($column_prefs_temp)){
				$column_prefs_joined = ',`'.implode("`,`",$column_prefs_temp).'`';
			}

			//build the query to ap_form_payments table
			$payment_table_query = '';
			foreach ($payment_columns_prefs as $column_name) {
				if($column_name == 'payment_status'){
					$payment_table_query .= ",ifnull((select 
													`{$column_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1),'unpaid') {$column_name}";
				}else{
					$payment_table_query .= ",(select 
													`{$column_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1) {$column_name}";
				}
			}

		}else{
			//there is no column from ap_form_payments
			$column_prefs_joined = ',`'.implode("`,`",$column_prefs).'`';
		}
		

		//if there is any radio fields which has 'other', we need to query that field as well
		if(!empty($element_radio_has_other)){
			$radio_has_other_array = array();
			foreach($element_radio_has_other as $element_name=>$value){
				$radio_has_other_array[] = $element_name.'_other';
			}
			$radio_has_other_joined = '`'.implode("`,`",$radio_has_other_array).'`';
			$column_prefs_joined = $column_prefs_joined.','.$radio_has_other_joined;
		}
		
		if($display_incomplete_entries === true){
			//only display incomplete entries
			$status_clause = "`status`=2";
		}else{
			//only display completed entries
			$status_clause = "`status`=1";
		}

		//check for filter data and build the filter query
		if(!empty($filter_data)){

			if($filter_type == 'all'){
				$condition_type = ' AND ';
			}else{
				$condition_type = ' OR ';
			}

			$where_clause_array = array();

			foreach ($filter_data as $value) {
				$element_name 	  = $value['element_name'];
				$filter_condition = $value['filter_condition'];
				$filter_keyword   = addslashes($value['filter_keyword']);

				$filter_element_type = $column_type_lookup[$element_name];

				$temp = explode('_', $element_name);
				$element_id = $temp[1];

				//if the filter is a column from ap_form_payments table
				//we need to replace $element_name with the subquery to ap_form_payments table
				if(!empty($payment_columns_prefs) && in_array($element_name, $payment_table_columns)){
					if($element_name == 'payment_status'){
						$element_name = "ifnull((select 
													`{$element_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1),'unpaid')";
					}else{
						$element_name = "(select 
													`{$element_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1)";
					}
				}
				
				
				if(in_array($filter_element_type, array('radio','select','matrix_radio'))){
					
					//these types need special steps to filter
					//we need to look into the ap_element_options first and do the filter there
					$null_clause = '';
					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}

					//do a query to ap_element_options table
					$query = "select 
									option_id 
								from 
									".LA_TABLE_PREFIX."element_options 
							   where 
							   		form_id=? and
							   		element_id=? and
							   		live=1 and 
							   		`option` {$where_operand} {$where_keyword}";
					
					$params = array($form_id,$element_id);
			
					$filtered_option_id_array = array();
					$sth = la_do_query($query,$params,$dbh);
					while($row = la_do_fetch_result($sth)){
						$filtered_option_id_array[] = $row['option_id'];
					}

					$filtered_option_id = implode("','", $filtered_option_id_array);

					$filter_radio_has_other = false;
					if($filter_element_type == 'radio' && !empty($radio_has_other_array)){
						if(in_array($element_name.'_other', $radio_has_other_array)){
							$filter_radio_has_other = true;
						}else{
							$filter_radio_has_other = false;
						}
					}
					
					if($filter_radio_has_other){ //if the filter is radio button field with 'other'
						if(!empty($filtered_option_id_array)){
							$where_clause_array[] = "({$element_name} IN('{$filtered_option_id}') OR {$element_name}_other {$where_operand} {$where_keyword} {$null_clause})"; 
						}else{
							$where_clause_array[] = "({$element_name}_other {$where_operand} {$where_keyword} {$null_clause})";
						}
					}else{//otherwise, for the rest of the field types
						if(!empty($filtered_option_id_array)){							
							if(!empty($null_clause)){
								$where_clause_array[] = "({$element_name} IN('{$filtered_option_id}') {$null_clause})";
							}else{
								$where_clause_array[] = "{$element_name} IN('{$filtered_option_id}')";
							} 
						}else{
							if(!empty($null_clause)){
								$where_clause_array[] = str_replace("OR", '', $null_clause);
							}
						}
					}
				}else if(in_array($filter_element_type, array('date','europe_date'))){

					$date_exploded = array();
					$date_exploded = explode('/', $filter_keyword); //the filter_keyword has format mm/dd/yyyy

					$filter_keyword = $date_exploded[2].'-'.$date_exploded[0].'-'.$date_exploded[1];

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}

					$where_clause_array[] = "date({$element_name}) {$where_operand} {$where_keyword}"; 
				}else{
					$null_clause = '';

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_one'){
						$where_operand = '=';
						$where_keyword = "'1'";
					}else if($filter_condition == 'is_zero'){
						$where_operand = '=';
						$where_keyword = "'0'";
					}
		 			
		 			if(!empty($null_clause)){
		 				$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword} {$null_clause})";
		 			}else{
		 				$where_clause_array[] = "{$element_name} {$where_operand} {$where_keyword}"; 
		 			}
					
				}
			}
			
			$where_clause = implode($condition_type, $where_clause_array);
			
			if(empty($where_clause)){
				$where_clause = "WHERE {$status_clause}";
			}else{
				$where_clause = "WHERE ({$where_clause}) AND {$status_clause}";
			}
			
						
		}else{
			$where_clause = "WHERE {$status_clause}";
		}
		
		//check the sorting element
		//if the element type is radio, select or matrix_radio, we need to add a sub query to the main query
		//so that the fields can be sorted properly (the sub query need to get values from ap_element_options table)
		$sort_element_type = $column_type_lookup[$sort_element];
		if(in_array($sort_element_type, array('radio','select','matrix_radio'))){
			if($sort_element_type == 'radio' && !empty($radio_has_other_array)){
				if(in_array($sort_element.'_other', $radio_has_other_array)){
					$sort_radio_has_other = true;
				}
			}

			$temp = explode('_', $sort_element);
			$sort_element_id = $temp[1];

			if($sort_radio_has_other){ //if this is radio button field with 'other' enabled
				$sorting_query = ",(	
										select if(A.{$sort_element}=0,A.{$sort_element}_other,
													(select 
															`option` 
														from ".LA_TABLE_PREFIX."element_options 
													   where 
													   		form_id='{$form_id}' and 
													   		element_id='{$sort_element_id}' and 
													   		option_id=A.{$sort_element} and 
													   		live=1)
									   	)
								   ) {$sort_element}_key";
			}else{
				$sorting_query = ",(
									select 
											`option` 
										from ".LA_TABLE_PREFIX."element_options 
									   where 
									   		form_id='{$form_id}' and 
									   		element_id='{$sort_element_id}' and 
									   		option_id=A.{$sort_element} and 
									   		live=1
								 ) {$sort_element}_key";
			}

			//override the $sort_element
			$sort_element .= '_key';
		}


		/** pagination **/
		//identify how many database rows are available
		/*$query = "select count(*) total_row from (select 
						`id`,
						`id` as `row_num`
						{$column_prefs_joined} 
						{$sorting_query} 
						{$payment_table_query} 
				    from 
				    	".LA_TABLE_PREFIX."form_{$form_id} A 
				    	{$where_clause} ) B ";
		$params = array();
			
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		$numrows   = $row['total_row'];
		$lastpage  = ceil($numrows/$rows_per_page);*/
							
							
		//ensure that $pageno is within range
		//this code checks that the value of $pageno is an integer between 1 and $lastpage
		$pageno = (int) $pageno;
							
		if ($pageno < 1) { 
		   $pageno = 1;
		}
		elseif ($pageno > $lastpage){
			$pageno = $lastpage;
		}
							
		//construct the LIMIT clause for the sql SELECT statement
		if(!empty($numrows)){
			$limit = 'LIMIT ' .($pageno - 1) * $rows_per_page .',' .$rows_per_page;
		}
		/** end pagination **/

		$query = "select 
						`id`,
						`id` as `row_num`
						{$column_prefs_joined} 
						{$sorting_query} 
						{$payment_table_query} 
				    from 
				    	".LA_TABLE_PREFIX."form_{$form_id} A 
				    	{$where_clause} 
				order by 
						{$sort_element} {$sort_order}
						{$limit}";
		
		$params = array();
		$sth = la_do_query($query,$params,$dbh);
		$i=0;
		
		//prepend "id" and "row_num" into the column preferences
		array_unshift($column_prefs,"id","row_num");
		
		while($row = la_do_fetch_result($sth)){
			$j=0;
			foreach($column_prefs as $column_name){
				$form_data[$i][$j] = '';

				//limit the data length, unless for file element
				if($column_type_lookup[$column_name] != 'file'){
					if(strlen($row[$column_name]) > $max_data_length){
						$row[$column_name] = substr($row[$column_name],0,$max_data_length).'...';
					}
				}
				
				if($column_type_lookup[$column_name] == 'time'){
					if(!empty($row[$column_name])){
						$form_data[$i][$j] = date("h:i:s A",strtotime($row[$column_name]));
					}else {
						$form_data[$i][$j] = '';
					}
				}elseif($column_type_lookup[$column_name] == 'time_noseconds'){ 
					if(!empty($row[$column_name])){
						$form_data[$i][$j] = date("h:i A",strtotime($row[$column_name]));
					}else {
						$form_data[$i][$j] = '';
					}
				}elseif($column_type_lookup[$column_name] == 'time_24hour_noseconds'){ 
					if(!empty($row[$column_name])){
						$form_data[$i][$j] = date("H:i",strtotime($row[$column_name]));
					}else {
						$form_data[$i][$j] = '';
					}
				}elseif($column_type_lookup[$column_name] == 'time_24hour'){ 
					if(!empty($row[$column_name])){
						$form_data[$i][$j] = date("H:i:s",strtotime($row[$column_name]));
					}else {
						$form_data[$i][$j] = '';
					}
				}elseif(substr($column_type_lookup[$column_name],0,5) == 'money'){ //set column formatting for money fields
					$column_type_temp = explode('_',$column_type_lookup[$column_name]);
					$column_type = $column_type_temp[1];

					switch ($column_type){
						case 'dollar' : $currency = '&#36;';break;	
						case 'pound'  : $currency = '&#163;';break;
						case 'euro'   : $currency = '&#8364;';break;
						case 'yen' 	  : $currency = '&#165;';break;
						case 'baht'   : $currency = '&#3647;';break;
						case 'forint' : $currency = '&#70;&#116;';break;
						case 'franc'  : $currency = 'CHF';break;
						case 'koruna' : $currency = '&#75;&#269;';break;
						case 'krona'  : $currency = 'kr';break;
						case 'pesos'  : $currency = '&#36;';break;
						case 'rand'   : $currency = 'R';break;
						case 'ringgit' : $currency = 'RM';break;
						case 'rupees' : $currency = 'Rs';break;
						case 'zloty'  : $currency = '&#122;&#322;';break;
						case 'riyals' : $currency = '&#65020;';break;
					}

					//if the column name is "payment_amount", this column is coming from ap_form_payments table
					//in this case, we need to use the currency  setting from the ap_forms table
					if($column_name == 'payment_amount'){
						switch ($payment_currency) {
							case 'USD' : $currency = '&#36;';break;
							case 'EUR' : $currency = '&#8364;';break;
							case 'GBP' : $currency = '&#163;';break;
							case 'AUD' : $currency = '&#36;';break;
							case 'CAD' : $currency = '&#36;';break;
							case 'JPY' : $currency = '&#165;';break;
							case 'THB' : $currency = '&#3647;';break;
							case 'HUF' : $currency = '&#70;&#116;';break;
							case 'CHF' : $currency = 'CHF';break;
							case 'CZK' : $currency = '&#75;&#269;';break;
							case 'SEK' : $currency = 'kr';break;
							case 'DKK' : $currency = 'kr';break;
							case 'NOK' : $currency = 'kr';break;
							case 'PHP' : $currency = '&#36;';break;
							case 'IDR' : $currency = 'Rp';break;
							case 'MYR' : $currency = 'RM';break;
							case 'PLN' : $currency = '&#122;&#322;';break;
							case 'BRL' : $currency = 'R&#36;';break;
							case 'HKD' : $currency = '&#36;';break;
							case 'MXN' : $currency = 'Mex&#36;';break;
							case 'TWD' : $currency = 'NT&#36;';break;
							case 'TRY' : $currency = 'TL';break;
							case 'NZD' : $currency = '&#36;';break;
							case 'SGD' : $currency = '&#36;';break;
							default: $currency_symbol = ''; break;
						}

						if($row[$column_name] == '0.00'){
							$row[$column_name] = ''; //don't display zero payments
						}
					}

					if(!empty($row[$column_name])){
						$form_data[$i][$j] = '<div class="me_right_div">'.$currency.$row[$column_name].'</div>';
					}else{
						$form_data[$i][$j] = '';
					}
				}elseif($column_type_lookup[$column_name] == 'date'){ //date with format MM/DD/YYYY
					if(!empty($row[$column_name]) && ($row[$column_name] != '0000-00-00')){
						$form_data[$i][$j]  = date('M d, Y',strtotime($row[$column_name]));
					}

					if($column_name == 'date_created' || $column_name == 'date_updated'){
						$form_data[$i][$j] = la_short_relative_date($row[$column_name]);
					}
				}elseif($column_type_lookup[$column_name] == 'europe_date'){ //date with format DD/MM/YYYY
					
					if(!empty($row[$column_name]) && ($row[$column_name] != '0000-00-00')){
						$form_data[$i][$j]  = date('d M Y',strtotime($row[$column_name]));
					}
				}elseif($column_type_lookup[$column_name] == 'number'){ 
					$form_data[$i][$j] = $row[$column_name];
				}elseif (in_array($column_type_lookup[$column_name],array('radio','select'))){ //multiple choice or dropdown
					$exploded = array();
					$exploded = explode('_',$column_name);
					$this_element_id = $exploded[1];
					$this_option_id  = $row[$column_name];
					
					$form_data[$i][$j] = $element_option_lookup[$this_element_id][$this_option_id];
					
					if($column_type_lookup[$column_name] == 'radio'){
						if($element_radio_has_other['element_'.$this_element_id] === true && empty($form_data[$i][$j])){
							$form_data[$i][$j] = $row['element_'.$this_element_id.'_other'];
						}
					}
				}elseif(substr($column_type_lookup[$column_name],0,6) == 'matrix'){
					$exploded = array();
					$exploded = explode('_',$column_type_lookup[$column_name]);
					$matrix_type = $exploded[1];

					if($matrix_type == 'radio'){
						$exploded = array();
						$exploded = explode('_',$column_name);
						$this_element_id = $exploded[1];
						$this_option_id  = $row[$column_name];
						
						$form_data[$i][$j] = $matrix_element_option_lookup[$this_element_id][$this_option_id];
					}else if($matrix_type == 'checkbox'){
						if(!empty($row[$column_name])){
							$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
						}else{
							$form_data[$i][$j]  = '';
						}
					}
				}elseif($column_type_lookup[$column_name] == 'checkbox'){
					
					if(!empty($row[$column_name])){
						if(substr($column_name,-5) == "other"){ //if this is an 'other' field, display the actual value
							$form_data[$i][$j] = htmlspecialchars($row[$column_name],ENT_QUOTES);
						}else{
							$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
						}
					}else{
						$form_data[$i][$j]  = '';
					}
					
				}elseif(in_array($column_type_lookup[$column_name],array('phone','simple_phone'))){ 
					if(!empty($row[$column_name])){
						if($column_type_lookup[$column_name] == 'phone'){
							$form_data[$i][$j] = '('.substr($row[$column_name],0,3).') '.substr($row[$column_name],3,3).'-'.substr($row[$column_name],6,4);
						}else{
							$form_data[$i][$j] = $row[$column_name];
						}
					}
				}elseif($column_type_lookup[$column_name] == 'file'){
					if(!empty($row[$column_name])){
						$raw_files = array();
						$raw_files = explode('|',$row[$column_name]);
						$clean_filenames = array();

						foreach($raw_files as $hashed_filename){
							$file_1 	    =  substr($hashed_filename,strpos($hashed_filename,'-')+1);
							$filename_value = substr($file_1,strpos($file_1,'-')+1);
							$clean_filenames[] = htmlspecialchars($filename_value);
						}

						$clean_filenames_joined = implode(', ',$clean_filenames);
						$form_data[$i][$j]  = '<div class="me_file_div">'.$clean_filenames_joined.'</div>';
					}
				}elseif($column_type_lookup[$column_name] == 'payment_status'){
					if($row[$column_name] == 'paid'){
						$payment_status_color = 'style="color: green;font-weight: bold"';
						$payment_status_label = strtoupper($row[$column_name]);
					}else{
						$payment_status_color = '';
						$payment_status_label = ucfirst(strtolower($row[$column_name]));
					}

					$form_data[$i][$j] = '<span '.$payment_status_color.'>'.$payment_status_label.'</span>';
				}else{
					$form_data[$i][$j] = htmlspecialchars(str_replace("\r","",str_replace("\n"," ",$row[$column_name])),ENT_QUOTES);
				}
				

				$j++;
			}
			$i++;
		}
		
		//generate table markup for the entries
		$table_header_markup = '<thead><tr>'."\n";

		foreach($column_labels as $label_name){
			if($label_name == 'la_id'){
				$table_header_markup .= '<th class="me_action" scope="col"><input type="checkbox" value="1" name="col_select" id="col_select" /></th>'."\n";
			}else if($label_name == 'la_row_num'){
				$table_header_markup .= '<th class="me_number" scope="col">#</th>'."\n";
			}else{
				$table_header_markup .= '<th scope="col"><div title="'.$label_name.'">'.$label_name.'</div></th>'."\n";	
			}
			
		}

		$table_header_markup .= '</tr></thead>'."\n";

		$table_body_markup = '<tbody>'."\n";

		$toggle = false;
		
		$first_row_number = ($pageno -1) * $rows_per_page + 1;
		$last_row_number  = $first_row_number;

		if(!empty($form_data)){
			foreach($form_data as $row_data){
				if($toggle){
					$toggle = false;
					$row_style = 'class="alt"';
				}else{
					$toggle = true;
					$row_style = '';
				}

				$table_body_markup .= "<tr id=\"row_{$row_data[0]}\" {$row_style}>";
				foreach ($row_data as $key=>$column_data){
					if($key == 0){ //this is "id" column
						$table_body_markup .= '<td class="me_action"><input type="checkbox" id="entry_'.$column_data.'" name="entry_'.$column_data.'" value="1" /></td>'."\n";
					}elseif ($key == 1){ //this is "row_num" column
						$table_body_markup .= '<td class="me_number">'.$column_data.'</td>'."\n";
					}else{
						$table_body_markup .= '<td><div>'.$column_data.'</div></td>'."\n";
					}
				}
				$table_body_markup .= "</tr>"."\n";
				$last_row_number++;
			}
		}else{
			$table_body_markup .= "<tr><td colspan=\"".count($column_labels)."\"> <div id=\"filter_no_results\"><h3>Your search returned no results.</h3></div></td></tr>";
		}

		$last_row_number--;

		if($display_incomplete_entries === true){
			$incomplete_status = 1;
		}else{
			$incomplete_status = 0;
		}

		$table_body_markup .= '</tbody>'."\n";
		$table_markup = '<table width="100%" cellspacing="0" cellpadding="0" data-incomplete="'.$incomplete_status.'" border="0" id="entries_table">'."\n";
		$table_markup .= $table_header_markup.$table_body_markup;
		$table_markup .= '</table>'."\n";

		$entries_markup = '<div id="entries_container">';
		$entries_markup .= $table_markup;
		$entries_markup .= '</div>';

		$pagination_markup = '';
		

		$self_path = htmlentities($_SERVER['PHP_SELF']);

		if(!empty($lastpage) && $numrows > $rows_per_page){
			
			if ($pageno != 1) {
			   if($lastpage > 13 && $pageno > 7){	
			   		$pagination_markup .= "<li class=\"page\"><a href='{$self_path}?id={$form_id}&pageno=1'>&#8676; First</a></li>";
			   }
			   $prevpage = $pageno-1;
			} 
			
			//middle navigation
			if($pageno == 1){
				$i=1;
				while(($i<=13) && ($i<=$lastpage)){
					if($i != 1){
							$active_style = '';
						}else{
							$active_style = 'current_page';
					}
					$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$i}'>{$i}</a></li>";
					$i++;
				}
				if($lastpage > $i){
					$pagination_markup .= "<li class=\"page_more\">...</li>";
				}
			}elseif ($pageno == $lastpage){
				
				if(($lastpage - 13) > 1){
					$pagination_markup .= "<li class=\"page_more\">...</li>";
					$i=1;
					$j=$lastpage - 12;
					while($i<=13){
						if($j != $lastpage){
							$active_style = '';
						}else{
							$active_style = 'current_page';
						}
						$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$j}'>{$j}</a></li>";
						$i++;
						$j++;
					}
				}else{
					$i=1;
					while(($i<=13) && ($i<=$lastpage)){
						if($i != $lastpage){
							$active_style = '';
						}else{
							$active_style = 'current_page';
						}
						$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$i}'>{$i}</a></li>";
						$i++;
					}
				}
				
			}else{
				$next_pages = false;
				$prev_pages = false;
				
				if(($lastpage - ($pageno + 6)) >= 1){
					$next_pages = true;
				}
				if(($pageno - 6) > 1){
					$prev_pages = true;
				}
				
				if($prev_pages){ //if there are previous pages
					$pagination_markup .= "<li class=\"page_more\">...</li>";
					if($next_pages){ //if there are next pages
						$i=1;
						$j=$pageno - 6;
						while($i<=13){
							if($j != $pageno){
								$active_style = '';
							}else{
								$active_style = 'current_page';
							}
							$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$j}'>{$j}</a></li>";
							$i++;
							$j++;
						}
						$pagination_markup .= "<li class=\"page_more\">...</li>";
					}else{
						
						$i=1;
						$j=$pageno - 9;
						while(($i<=13) && ($j <= $lastpage)){
							if($j != $pageno){
								$active_style = '';
							}else{
								$active_style = 'current_page';
							}
							$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$j}'>{$j}</a></li>";
							$i++;
							$j++;
						}
					}	
				}else{ //if there aren't previous pages
				
					$i=1;
  					while(($i<=13) && ($i <= $lastpage)){
  						if($i != $pageno){
							$active_style = '';
						}else{
							$active_style = 'current_page';
						}
						$pagination_markup .= "<li class=\"page {$active_style}\"><a href='{$self_path}?id={$form_id}&pageno={$i}'>{$i}</a></li>";
						$i++;	
					}
					if($next_pages){
						$pagination_markup .= "<li class=\"page_more\">...</li>";
					}
				}
				
				
			}
				
			if ($pageno != $lastpage) 
			{
			   $nextpage = $pageno+1;
			   if($lastpage > 13){
			   		$pagination_markup .= "<li class=\"page\"><a href='{$self_path}?id={$form_id}&pageno=$lastpage'>Last &#8677;</a></li>";
			   }
			}
			
			$pagination_markup = '<ul class="pages bluesoft small" id="me_pagination">'.$pagination_markup.'</ul>';
			$pagination_markup .= "<div id=\"me_pagination_label\">Displaying <strong>{$first_row_number}-{$last_row_number}</strong> of <strong id=\"me_entries_total\">{$numrows}</strong> entries</div>";
		}else{
			$pagination_markup = '<div style="width: 100%; height: 20px;"></div>';
		}
		
		
		$entries_markup .= $pagination_markup;
		
		return $entries_markup;

	}

	//get an array of all element fields' label and types within a form
	function la_get_columns_meta($dbh,$form_id){
		
		$form_id = (int) $form_id;

		//get form element options first (checkboxes, choices, dropdown)
		$query = "select 
						element_id,
						option_id,
						`option`
					from 
						".LA_TABLE_PREFIX."element_options 
				   where 
				   		form_id=? and live=1 
				order by 
						element_id,position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$element_option_lookup[$element_id][$option_id] = htmlspecialchars(strip_tags($row['option']),ENT_QUOTES);
		}

		//get element options for matrix fields
						
		$query = "select DISTINCT 
					A.element_id,
					A.option_id,
					(select if(B.element_matrix_parent_id=0,A.option,
						(select DISTINCT 
								C.`option` 
						   from 
						   		".LA_TABLE_PREFIX."element_options C 
						  where 
						  		C.element_id=B.element_matrix_parent_id and 
						  		C.form_id=A.form_id and 
						  		C.live=1 and 
						  		C.option_id=A.option_id))
					) 'option_label'
				from 
					".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
			   where 
			   		A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
			order by 
					A.element_id,A.option_id asc";
						
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$matrix_element_option_lookup[$element_id][$option_id] = htmlspecialchars(strip_tags($row['option_label']),ENT_QUOTES);
		}

		//get 'multiselect' status of matrix fields
		$query = "select 
						  A.element_id,
						  A.element_matrix_parent_id,
						  A.element_matrix_allow_multiselect,
						  (select if(A.element_matrix_parent_id=0,A.element_matrix_allow_multiselect,
						  			 (select B.element_matrix_allow_multiselect from ".LA_TABLE_PREFIX."form_elements B where B.form_id=A.form_id and B.element_id=A.element_matrix_parent_id)
						  			)
						  ) 'multiselect' 
					  from 
					 	  ".LA_TABLE_PREFIX."form_elements A
					 where 
					 	  A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
		}


		
		//set column properties for basic fields
		$column_name_lookup['id'] 			= 'ID#';
		$column_name_lookup['date_created'] = 'Date Created';
		$column_name_lookup['date_updated'] = 'Date Updated';
		$column_name_lookup['ip_address'] 	= 'IP Address';

		$column_type_lookup['id'] 			= 'number';
		$column_type_lookup['date_created'] = 'date';
		$column_type_lookup['date_updated'] = 'date';
		$column_type_lookup['ip_address'] 	= 'text';
		
		
		//get column properties for other fields
		$query  = "select 
						 element_id,
						 element_title,
						 element_type,
						 element_constraint,
						 element_choice_has_other,
						 element_choice_other_label,
						 element_time_showsecond,
						 element_time_24hour,
						 element_matrix_allow_multiselect  
				     from 
				         `".LA_TABLE_PREFIX."form_elements` 
				    where 
				    	 form_id=? and element_status=1 and element_type not in('section','page_break')
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$element_radio_has_other = array();

		while($row = la_do_fetch_result($sth)){

			$element_type 	    = $row['element_type'];
			$element_constraint = $row['element_constraint'];
			

			//get 'other' field label for checkboxes and radio button
			if($element_type == 'checkbox' || $element_type == 'radio'){
				if(!empty($row['element_choice_has_other'])){
					$element_option_lookup[$row['element_id']]['other'] = htmlspecialchars(strip_tags($row['element_choice_other_label']),ENT_QUOTES);
				
					if($element_type == 'radio'){
						$element_radio_has_other['element_'.$row['element_id']] = true;	
					}
				}
			}

			$row['element_title'] = htmlspecialchars(strip_tags($row['element_title']),ENT_QUOTES);

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

				$column_type_lookup['element_'.$row['element_id']] = 'money';

			}elseif('checkbox' == $element_type){ //checkboxes, get childs elements
							
				$this_checkbox_options = $element_option_lookup[$row['element_id']];
				
				foreach ($this_checkbox_options as $option_id=>$option){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
					$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = $row['element_type'];
				}
			}elseif ('time' == $element_type){
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];

				$column_type_lookup['element_'.$row['element_id']] = 'time';

			}else if('matrix' == $element_type){ 
				
				if(empty($matrix_multiselect_status[$row['element_id']])){
					$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
					$column_type_lookup['element_'.$row['element_id']] = 'matrix';
				}else{
					$this_checkbox_options = $matrix_element_option_lookup[$row['element_id']];
					
					foreach ($this_checkbox_options as $option_id=>$option){
						$option = $option.' - '.$row['element_title'];
						$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
						$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = 'matrix';
					}
				}
			}else{ //for other elements with only 1 field
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
			}

			
		}
		
		$column_meta['name_lookup'] = $column_name_lookup;
		$column_meta['type_lookup'] = $column_type_lookup;

		return $column_meta;
	}

	//get an array containing id number of all filtered entries within a form
	function la_get_filtered_entries_ids($dbh,$form_id,$options=array()){
		
		$form_id = (int) $form_id;

		$is_incomplete_entry = false;
		if($options['is_incomplete_entry'] === true){
			$is_incomplete_entry = true;
		}

		//get filter keywords from ap_form_filters table
		if($is_incomplete_entry){
			$incomplete_status = 1;
		}else{
			$incomplete_status = 0;
		}

		$query = "select
						element_name,
						filter_condition,
						filter_keyword
					from 
						".LA_TABLE_PREFIX."form_filters
				   where
				   		`form_id` = ? and `user_id` = ? and incomplete_entries = ? 
				order by 
				   		aff_id asc";
		$params = array($form_id,$_SESSION['la_user_id'],$incomplete_status);
		$sth = la_do_query($query,$params,$dbh);
		$i = 0;
		while($row = la_do_fetch_result($sth)){
			$filter_data[$i]['element_name'] 	 = $row['element_name'];
			$filter_data[$i]['filter_condition'] = $row['filter_condition'];
			$filter_data[$i]['filter_keyword'] 	 = $row['filter_keyword'];
			$i++;
		}

		$query 	= "select 
						 entries_filter_type,
						 entries_sort_by,
						 entries_incomplete_filter_type,
						 entries_incomplete_sort_by 
				     from 
				     	 ".LA_TABLE_PREFIX."entries_preferences 
				    where 
				    	 form_id = ? and `user_id` = ?";
		$params = array($form_id,$_SESSION['la_user_id']);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row)){
			if($is_incomplete_entry){
				$filter_type   = $row['entries_incomplete_filter_type'];
				$sort_by 	   = $row['entries_incomplete_sort_by'];
			}else{
				$filter_type   = $row['entries_filter_type'];
				$sort_by 	   = $row['entries_sort_by'];
			}
		}else{
			$filter_type   = 'all';
			$sort_by 	   = 'id-desc';
		}

		$exploded = explode('-', $sort_by);
		$sort_element = $exploded[0]; //the element name, e.g. element_2
		$sort_order	  = $exploded[1]; //asc or desc

		/******************************************************************************************/
		//prepare column header names lookup

		//get form element options first (checkboxes, choices, dropdown)
		$query = "select 
						element_id,
						option_id,
						`option`
					from 
						".LA_TABLE_PREFIX."element_options 
				   where 
				   		form_id=? and live=1 
				order by 
						element_id,option_id asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$element_option_lookup[$element_id][$option_id] = htmlspecialchars($row['option'],ENT_QUOTES);
		}

		//get element options for matrix fields
		/*$query = "select 
						A.element_id,
						A.option_id,
						(select if(B.element_matrix_parent_id=0,A.option,
							(select 
									C.`option` 
							   from 
							   		".LA_TABLE_PREFIX."element_options C 
							  where 
							  		C.element_id=B.element_matrix_parent_id and 
							  		C.form_id=A.form_id and 
							  		C.live=1 and 
							  		C.option_id=A.option_id))
						) 'option_label'
					from 
						".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
				   where 
				   		A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
				order by 
						A.element_id,A.option_id asc";*/
						
		$query = "select DISTINCT 
					A.element_id,
					A.option_id,
					(select if(B.element_matrix_parent_id=0,A.option,
						(select DISTINCT 
								C.`option` 
						   from 
						   		".LA_TABLE_PREFIX."element_options C 
						  where 
						  		C.element_id=B.element_matrix_parent_id and 
						  		C.form_id=A.form_id and 
						  		C.live=1 and 
						  		C.option_id=A.option_id))
					) 'option_label'
				from 
					".LA_TABLE_PREFIX."element_options A left join ".LA_TABLE_PREFIX."form_elements B on (A.element_id=B.element_id and A.form_id=B.form_id)
			   where 
			   		A.form_id=? and A.live=1 and B.element_type='matrix' and B.element_status=1
			order by 
					A.element_id,A.option_id asc";
						
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$element_id = $row['element_id'];
			$option_id  = $row['option_id'];
			
			$matrix_element_option_lookup[$element_id][$option_id] = htmlspecialchars($row['option_label'],ENT_QUOTES);
		}

		//get 'multiselect' status of matrix fields
		$query = "select 
						  A.element_id,
						  A.element_matrix_parent_id,
						  A.element_matrix_allow_multiselect,
						  (select if(A.element_matrix_parent_id=0,A.element_matrix_allow_multiselect,
						  			 (select B.element_matrix_allow_multiselect from ".LA_TABLE_PREFIX."form_elements B where B.form_id=A.form_id and B.element_id=A.element_matrix_parent_id)
						  			)
						  ) 'multiselect' 
					  from 
					 	  ".LA_TABLE_PREFIX."form_elements A
					 where 
					 	  A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
		}


		/******************************************************************************************/
		//set column properties for basic fields
		$column_name_lookup['date_created'] = 'Date Created';
		$column_name_lookup['date_updated'] = 'Date Updated';
		$column_name_lookup['ip_address'] 	= 'IP Address';
		$column_name_lookup['payment_amount'] = 'Payment Amount';
		$column_name_lookup['payment_status'] = 'Payment Status';
		$column_name_lookup['payment_id']	  = 'Payment ID';
		
		$column_type_lookup['id'] 			= 'number';
		$column_type_lookup['row_num']		= 'number';
		$column_type_lookup['date_created'] = 'date';
		$column_type_lookup['date_updated'] = 'date';
		$column_type_lookup['ip_address'] 	= 'text';
		$column_type_lookup['payment_amount'] = 'money';
		$column_type_lookup['payment_status'] = 'payment_status';
		$column_type_lookup['payment_id']	  = 'text';
		
		//get column properties for other fields
		$query  = "select 
						 element_id,
						 element_title,
						 element_type,
						 element_constraint,
						 element_choice_has_other,
						 element_choice_other_label,
						 element_time_showsecond,
						 element_time_24hour,
						 element_matrix_allow_multiselect  
				     from 
				         `".LA_TABLE_PREFIX."form_elements` 
				    where 
				    	 form_id=? and element_status=1 and element_type not in('section','page_break')
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$element_radio_has_other = array();

		while($row = la_do_fetch_result($sth)){

			$element_type 	    = $row['element_type'];
			$element_constraint = $row['element_constraint'];
			

			//get 'other' field label for checkboxes and radio button
			if($element_type == 'checkbox' || $element_type == 'radio'){
				if(!empty($row['element_choice_has_other'])){
					$element_option_lookup[$row['element_id']]['other'] = htmlspecialchars($row['element_choice_other_label'],ENT_QUOTES);
				
					if($element_type == 'radio'){
						$element_radio_has_other['element_'.$row['element_id']] = true;	
					}
				}
			}

			$row['element_title'] = htmlspecialchars($row['element_title'],ENT_QUOTES);

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
				
				foreach ($this_checkbox_options as $option_id=>$option){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = htmlspecialchars($option,ENT_QUOTES);
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
				
				if(empty($matrix_multiselect_status[$row['element_id']])){
					$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
					$column_type_lookup['element_'.$row['element_id']] = 'matrix_radio';
				}else{
					$this_checkbox_options = $matrix_element_option_lookup[$row['element_id']];
					
					foreach ($this_checkbox_options as $option_id=>$option){
						$option = $option.' - '.$row['element_title'];
						$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = htmlspecialchars($option,ENT_QUOTES);
						$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = 'matrix_checkbox';
					}
				}
			}else{ //for other elements with only 1 field
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
			}

			
		}
		/******************************************************************************************/

		//get column preferences and store it into array
		$query = "select element_name from ".LA_TABLE_PREFIX."column_preferences where form_id=? and user_id=? and incomplete_entries=? order by position asc";
		$params = array($form_id,$_SESSION['la_user_id'],$incomplete_status);
		$sth = la_do_query($query,$params,$dbh);
		while($row = la_do_fetch_result($sth)){
			$column_prefs[] = $row['element_name'];
		}


		//if there is no column preferences, display the first 6 fields
		if(empty($column_prefs)){
			$temp_slice = array_slice($column_name_lookup,0,8);
			unset($temp_slice['date_updated']);
			unset($temp_slice['ip_address']);
			$column_prefs = array_keys($temp_slice);
		}
		
		//get the entries from ap_form_x table and store it into array
		//but first we need to check if there is any column preferences from ap_form_payments table
		$payment_table_columns = array('payment_amount','payment_status','payment_id');
		$payment_columns_prefs = array_intersect($payment_table_columns, $column_prefs);

		if(!empty($payment_columns_prefs)){
			//there is one or more column from ap_form_payments
			//don't include this column into $column_prefs_joined variable
			$column_prefs_temp = array();
			foreach ($column_prefs as $value) {
				if(in_array($value, $payment_table_columns)){
					continue;
				}
				$column_prefs_temp[] = $value;
			}

			$column_prefs_joined = '`'.implode("`,`",$column_prefs_temp).'`';

			//build the query to ap_form_payments table
			$payment_table_query = '';
			foreach ($payment_columns_prefs as $column_name) {
				if($column_name == 'payment_status'){
					$payment_table_query .= ",ifnull((select 
													`{$column_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1),'unpaid') {$column_name}";
				}else{
					$payment_table_query .= ",(select 
													`{$column_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1) {$column_name}";
				}
			}

		}else{
			//there is no column from ap_form_payments
			$column_prefs_joined = '`'.implode("`,`",$column_prefs).'`';
		}

		//if there is any radio fields which has 'other', we need to query that field as well
		if(!empty($element_radio_has_other)){
			$radio_has_other_array = array();
			foreach($element_radio_has_other as $element_name=>$value){
				$radio_has_other_array[] = $element_name.'_other';
			}
			$radio_has_other_joined = '`'.implode("`,`",$radio_has_other_array).'`';
			$column_prefs_joined = $column_prefs_joined.','.$radio_has_other_joined;
		}

		if($is_incomplete_entry){
			//only display incomplete entries
			$status_clause = "`status`=2";
		}else{
			//only display completed entries
			$status_clause = "`status`=1";
		}

		//check for filter data and build the filter query
		if(!empty($filter_data)){

			if($filter_type == 'all'){
				$condition_type = ' AND ';
			}else{
				$condition_type = ' OR ';
			}

			$where_clause_array = array();

			foreach ($filter_data as $value) {
				$element_name 	  = $value['element_name'];
				$filter_condition = $value['filter_condition'];
				$filter_keyword   = $value['filter_keyword'];

				$filter_element_type = $column_type_lookup[$element_name];

				$temp = explode('_', $element_name);
				$element_id = $temp[1];

				//if the filter is a column from ap_form_payments table
				//we need to replace $element_name with the subquery to ap_form_payments table
				if(!empty($payment_columns_prefs) && in_array($element_name, $payment_table_columns)){
					if($element_name == 'payment_status'){
						$element_name = "ifnull((select 
													`{$element_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1),'unpaid')";
					}else{
						$element_name = "(select 
													`{$element_name}` 
												 from ".LA_TABLE_PREFIX."form_payments 
												where 
													 form_id='{$form_id}' and record_id=A.id 
											 order by 
											 		 afp_id desc limit 1)";
					}
				}
				
				if(in_array($filter_element_type, array('radio','select','matrix_radio'))){
					
					//these types need special steps to filter
					//we need to look into the ap_element_options first and do the filter there
					$null_clause = '';
					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}else if($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}

					//do a query to ap_element_options table
					$query = "select 
									option_id 
								from 
									".LA_TABLE_PREFIX."element_options 
							   where 
							   		form_id=? and 
									element_id=? and
							   		live=1 and 
							   		`option` {$where_operand} {$where_keyword}";
					$params = array($form_id,$element_id);
			
					$filtered_option_id_array = array();
					$sth = la_do_query($query,$params,$dbh);
					while($row = la_do_fetch_result($sth)){
						$filtered_option_id_array[] = $row['option_id'];
					}

					$filtered_option_id = implode("','", $filtered_option_id_array);

					$filter_radio_has_other = false;
					if($filter_element_type == 'radio' && !empty($radio_has_other_array)){
						if(in_array($element_name.'_other', $radio_has_other_array)){
							$filter_radio_has_other = true;
						}else{
							$filter_radio_has_other = false;
						}
					}
					
					if($filter_radio_has_other){ //if the filter is radio button field with 'other'
						if(!empty($filtered_option_id_array)){
							$where_clause_array[] = "({$element_name} IN('{$filtered_option_id}') OR {$element_name}_other {$where_operand} {$where_keyword} {$null_clause})"; 
						}else{
							$where_clause_array[] = "({$element_name}_other {$where_operand} {$where_keyword} {$null_clause})";
						}
					}else{//otherwise, for the rest of the field types
						if(!empty($filtered_option_id_array)){							
							if(!empty($null_clause)){
								$where_clause_array[] = "({$element_name} IN('{$filtered_option_id}') {$null_clause})";
							}else{
								$where_clause_array[] = "{$element_name} IN('{$filtered_option_id}')";
							} 
						}else{
							if(!empty($null_clause)){
								$where_clause_array[] = str_replace("OR", '', $null_clause);
							}
						}
					}

				}else if(in_array($filter_element_type, array('date','europe_date'))){

					$date_exploded = array();
					$date_exploded = explode('/', $filter_keyword); //the filter_keyword has format mm/dd/yyyy

					$filter_keyword = $date_exploded[2].'-'.$date_exploded[0].'-'.$date_exploded[1];

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}

					$where_clause_array[] = "date({$element_name}) {$where_operand} {$where_keyword}"; 
				}else{
					$null_clause = '';

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}else if($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}else if($filter_condition == 'is_one'){
						$where_operand = '=';
						$where_keyword = "'1'";
					}else if($filter_condition == 'is_zero'){
						$where_operand = '=';
						$where_keyword = "'0'";
					}
		 			
					if(!empty($null_clause)){
		 				$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword} {$null_clause})";
		 			}else{
		 				$where_clause_array[] = "{$element_name} {$where_operand} {$where_keyword}"; 
		 			} 
				}
			}
			
			$where_clause = implode($condition_type, $where_clause_array);
			
			if(empty($where_clause)){
				$where_clause = "WHERE {$status_clause}";
			}else{
				$where_clause = "WHERE ({$where_clause}) AND {$status_clause}";
			}
			
						
		}else{
			$where_clause = "WHERE {$status_clause}";
		}


		//check the sorting element
		//if the element type is radio, select or matrix_radio, we need to add a sub query to the main query
		//so that the fields can be sorted properly (the sub query need to get values from ap_element_options table)
		$sort_element_type = $column_type_lookup[$sort_element];
		if(in_array($sort_element_type, array('radio','select','matrix_radio'))){
			if($sort_element_type == 'radio' && !empty($radio_has_other_array)){
				if(in_array($sort_element.'_other', $radio_has_other_array)){
					$sort_radio_has_other = true;
				}
			}

			$temp = explode('_', $sort_element);
			$sort_element_id = $temp[1];

			if($sort_radio_has_other){ //if this is radio button field with 'other' enabled
				$sorting_query = ",(	
										select if(A.{$sort_element}=0,A.{$sort_element}_other,
													(select 
															`option` 
														from ".LA_TABLE_PREFIX."element_options 
													   where 
													   		form_id='{$form_id}' and 
													   		element_id='{$sort_element_id}' and 
													   		option_id=A.{$sort_element} and 
													   		live=1)
									   	)
								   ) {$sort_element}_key";
			}else{
				$sorting_query = ",(
									select 
											`option` 
										from ".LA_TABLE_PREFIX."element_options 
									   where 
									   		form_id='{$form_id}' and 
									   		element_id='{$sort_element_id}' and 
									   		option_id=A.{$sort_element} and 
									   		live=1
								 ) {$sort_element}_key";
			}

			//override the $sort_element
			$sort_element .= '_key';
		}

		$query = "select 
						`id`,
						`id` as `row_num`,
						{$column_prefs_joined}
						{$sorting_query} 
						{$payment_table_query} 
				    from 
				    	".LA_TABLE_PREFIX."form_{$form_id} A 
				    	{$where_clause}
				order by 
						{$sort_element} {$sort_order}";
		
		$params = array();
		$sth = la_do_query($query,$params,$dbh);
		
		$filtered_entry_id_array = array();
		while($row = la_do_fetch_result($sth)){
			$filtered_entry_id_array[] = $row['id'];
		}

		return $filtered_entry_id_array;
	}

	//return form field information
	function get_form_fields($dbh, $form_id){
		$query = "SELECT 
				element_id,
				element_title,
				element_type,
				element_page_number,
				element_position
			FROM 
				".LA_TABLE_PREFIX."form_elements 
		   WHERE 
		   		form_id = ? and element_status='1' and element_type not in('section','page_break')
		ORDER BY 
				element_position asc";
		//print_r($params);
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		$j=0;
		$table_header = [];
		while($row = la_do_fetch_result($sth)){

			$table_header[$j]['element_id'] = $row['element_id'];
			$table_header[$j]['element_title'] = $row['element_title'];
			$table_header[$j]['element_type'] = $row['element_type'];
			$table_header[$j]['element_page_number'] = $row['element_page_number'];
			$table_header[$j]['element_position'] = $row['element_position'];
			$j++;
		}
 		
		return $table_header;
	}
?>