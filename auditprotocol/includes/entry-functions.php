<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://continuumgrc.com
 
 More info at: http://continuumgrc.com
 ********************************************************************************/
 	//genearate casecade form fields
 	
 	
	function display_casecade_form_fields_mod($parameter=array())
	{
		if(!$parameter['form_id']){
			return;
		}
		
		$dbh = $parameter['dbh'];
		$form_id = $parameter['form_id'];
		$parent_form_id = $parameter['parent_form_id'];
		$entry_id = $parameter['entry_id'];
		$company_id = $parameter['company_id'];
		$element_id = $parameter['element_id'];
		
		//get entry details for particular entry_id
		$param['checkbox_image'] = 'images/icons/59_blue_16.png';
		$from_cascade = true;
		
		$entry_details = la_get_entry_details($dbh, $form_id, $company_id, $entry_id, $param, $from_cascade);
	  
		//echo '<pre>';print_r($entry_details);echo '</pre>';die;
		$toggle = false;
		$row_markup = '';
    $row_markup_doc = '';
		foreach ($entry_details as $data){ 
			
				if ($data['element_id'] == $element_id) {
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
	
			$element_id = $data['element_id'];
		
			if($data['element_type'] == 'section' || $data['element_type'] == 'textarea') {
				if(!empty($data['label']) && !empty($data['value']) && ($data['value'] != '&nbsp;')){
					$section_separator = '<br/>';
				}else{
					$section_separator = '';
				}
		
				$section_break_content = '<span class="la_section_title"><strong>'.nl2br($data['label']).'</strong></span>'.$section_separator.'<span class="la_section_content">'.nl2br($data['value']).'</span>';
		
				// $row_markup .= "<tr {$row_style}>\n";
				// $row_markup .= "<td width=\"100%\" colspan=\"2\">{$section_break_content}</td>\n";
				// $row_markup .= "</tr>\n";

				$row_markup .= "<td>{$section_break_content}</td>";
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
				// $row_markup .= "<tr>\n";
				// $row_markup .= "<td width=\"40%\" style=\"vertical-align: top\"><strong>{$data['label']}</strong></td>\n";
				// $row_markup .= "<td width=\"60%\">{$signature_markup}</td>\n";
				// $row_markup .= "</tr>\n";
				$row_markup .= "<td>{$data['label']} {$signature_markup}</td>\n";
			}
			elseif($data['element_type'] == 'casecade_form') {
                $row_markup_array = display_casecade_form_fields_mod(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $company_id));
                $row_markup_doc .= $row_markup_array['row_markup_doc'];
                $row_markup .= $row_markup_array['row_markup'];
			}else{
				$tmpData = nl2br($data['value']);
				// $row_markup .= "<tr {$row_style}>\n";
				// $row_markup .= "<td width=\"40%\"><strong>{$data['label']}</strong></td>\n";
				// $row_markup .= "<td width=\"60%\">{$tmpData}</td>\n";
				$row_markup .= "{$tmpData}";
				// $row_markup .= "</tr>\n";
			}
		} 
		}
		
		
		// fetch doc details if available
		$query11 = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND `isZip` = 1 order by `docx_create_date` DESC";
		$sth11 = la_do_query($query11, array($form_id, $company_id, $entry_id), $dbh);
		$row11 = la_do_fetch_result($sth11);
		
		if($toggle){
		  $toggle = false;
		  $row_style = 'class="alt"';
		}else{
		  $toggle = true;
		  $row_style = '';
		}
		        
		if($row11){
			// $row_markup_doc .= "<tr class=\"{$row_style}\">\n";
			//   $row_markup_doc .= "<td width=\"40%\"><strong>Document</strong></td>\n";
			//   $row_markup_doc .= "<td width=\"60%\"><a href=\"../portal/template_output/{$row11['docxname']}\">{$row11['docxname']}</a></td>\n";
			// $row_markup_doc .= "</tr>\n";
			$row_markup_doc .= "<td><a href=\"../portal/template_output/{$row11['docxname']}\">{$row11['docxname']}</a></td>";
		}
		
		return array('row_markup' => $row_markup, 'row_markup_doc' => $row_markup_doc);
	}

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

			$edit_entry_url = $la_settings['base_url']."edit_entry.php?form_id={$parent_form_id}&company_id={$company_id}&entry_id={$entry_id}";

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

				$status_indicator = '<img class="status-icon status-icon-action-view" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-entry_id="'.$entry_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;" />';
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
				$row_markup_doc .= "<td><a target=\"_blank\" href=\"javascript:void()\" class=\"action-download-document-zip\" data-documentdownloadlink=\"{$la_settings['base_url']}download_document_zip.php?id={$document_data['docxname']}&form_id={$form_id}&entry_id={$entry_id}&company_id={$company_id}\">{$document_data['docxname']}</a></td>\n";
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
				$status_indicator = '<td width="30%" class="status_parent"><strong>'.$data['label'].'</strong><img class="status-icon status-icon-action-status" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-entry_id="'.$entry_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;"><div class="all_statuses"></div></td><td width="50%">'.$field_value.'</td><td width="20%"><a target="_blank" style="float: right;" href="edit_entry.php?form_id='.$parent_form_id.'&entry_id='.$entry_id.'&la_page='.$tmp_page_number.'&casecade_element_position='.$tmp_casecade_element_position.'&casecade_form_page_number='.$data['element_page_number'].'&element_id_auto='.$data['element_id_auto'].'">Go To Field</a></td>';

				$row_markup[$statusElementArrId][] = $status_indicator;
			}
		}

		return array('row_markup' => $row_markup, 'accordion_head_count_Arr' => $accordion_head_count_Arr);
	}

	//get an array containing values from respective table for certain id
	function la_get_entry_values($dbh, $form_id, $company_id, $entry_id)
	{	
		$form_id = (int) $form_id;
		$company_id = (int) $company_id;
		$entry_id = (int) $entry_id;
		$la_settings = la_get_settings($dbh);
        $form_elements = array();
        $form_values = array();
		$entry_data = array();
		$entry_data_other_info = array();
		
		if($use_review_table){
			$table_suffix = '';
		}else{
			$table_suffix = '';
		}
			
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

		$query  = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
		$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
		
		while($row = la_do_fetch_result($sth)){
			$entry_data[$row['field_name']] = $row['data_value'];
		}

		//get form element options
		$query = "select element_id, option_id,`option` from ".LA_TABLE_PREFIX."element_options where form_id=? and live=1 order by option_id";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
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
                $checkTimeData = $entry_data['element_'.$element_id];
                $checkTimeData = str_replace(array(":", " "), array("", ""), $checkTimeData);
				if(!empty($checkTimeData)){
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
			elseif ('matrix' == $element_type) {
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
			elseif('radio' == $element_type){ 
				$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];

				if(!empty($entry_data['element_'.$element_id.'_other']) && empty($entry_data['element_'.$element_id])){
					$form_values['element_'.$element_id.'_other']['default_value'] = $entry_data['element_'.$element_id.'_other'];
				}
			}
			elseif('signature' == $element_type){ 
				$form_values['element_'.$element_id]['default_value'] = htmlspecialchars_decode($entry_data['element_'.$element_id],ENT_QUOTES);
			}
			elseif('casecade_form' == $element_type){
     			if($element['element_default_value']){
					$form_values['element_'.$element_id][$element['element_default_value']] = la_get_entry_values($dbh, $element['element_default_value'], $company_id, $entry_id);
                }                
			}else{ //element with only 1 input
				$form_values['element_'.$element_id]['default_value'] = $entry_data['element_'.$element_id];
			}
		}
		
		return $form_values;	
	}

	//get an array containing values from respective table for certain id
	//similar to la_get_entry_values() function, but this one is higher level and include labels
	function la_get_entry_details($dbh,$form_id,$company_id,$entry_id,$options=array(),$from_cascade = false)
	{
		$form_id = (int) $form_id;
		$company_id = (int) $company_id;
		$entry_id = (int) $entry_id;
		$la_settings = la_get_settings($dbh);
        $form_elements = array();
        $entry_details = array();
		$auditFilePath = $_SERVER["DOCUMENT_ROOT"].'/auditprotocol/data/';
		
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
						 element_position,
						 element_constraint,
						 element_title,
						 element_size,
						 element_file_as_attachment,
						 element_time_showsecond,
						 element_time_24hour,
						 element_section_display_in_email,
						 element_matrix_parent_id,
						 element_guidelines,
						 element_matrix_allow_multiselect as matrix_multiselect_status,
						 element_default_value,
						 element_page_number,
						 element_file_upload_synced,
						 element_machine_code,
						 element_status_indicator
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
		$matrix_allow_multi_parent = 0;
		while($row = la_do_fetch_result($sth)){

			//if this is section break and doesn't have option to be displayed in email, skip it
			if($row['element_type'] == 'section' && empty($row['element_section_display_in_email'])){
				continue;
			}

			if ($row['element_matrix_parent_id'] == 0) {
				$matrix_allow_multi_parent = $row['matrix_multiselect_status'];
			}
			//Use the 'allow multiselect' value from the parent element because the values are not stored accurately for the child rows
			$form_elements[$i]['element_matrix_allow_multiselect'] = $matrix_allow_multi_parent;
			//All other values come from the DB row
			$form_elements[$i]['element_id_auto'] 		 = $row['id'];
			$form_elements[$i]['element_id'] 		 = $row['element_id'];
			$form_elements[$i]['element_type'] 		 = $row['element_type'];
			$form_elements[$i]['element_position'] 		 = $row['element_position'];
			$form_elements[$i]['element_size'] 		 = $row['element_size'];
			$form_elements[$i]['element_constraint'] = $row['element_constraint'];
			$form_elements[$i]['element_guidelines'] = $row['element_guidelines'];
			$form_elements[$i]['element_file_as_attachment'] = $row['element_file_as_attachment'];
			$form_elements[$i]['element_time_showsecond'] = $row['element_time_showsecond'];
			$form_elements[$i]['element_time_24hour'] 	  = $row['element_time_24hour'];
			$form_elements[$i]['element_default_value'] = $row['element_default_value'];
			$form_elements[$i]['element_page_number'] = $row['element_page_number'];
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
			$entry_data[$row['field_name']] 	     = htmlspecialchars($row['data_value'],ENT_QUOTES);
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
      	$entry_details[$i]['value'] = la_get_entry_details($dbh,$element['element_default_value'],$company_id, $entry_id,$options,1);
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
	function la_display_entries_table($dbh,$form_id,$options)
	{		
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
		$la_settings_base_url = $options['base_url'];

		if(empty($sort_element)){ //set the default sorting order
			$sort_element = 'id';
			$sort_order	  = 'asc';
		}

		$form_properties = la_get_form_properties($dbh,$form_id,array('payment_currency','payment_enable_merchant'));

		



		

		$payment_currency = strtoupper($form_properties['payment_currency']);
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
		$query = "select DISTINCT A.element_id, A.option_id, IF (B.element_matrix_parent_id = 0, A.option, null)as option_label
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
						A.element_matrix_allow_multiselect as 'multiselect'
					from 
						".LA_TABLE_PREFIX."form_elements A
					where 
						A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
			$matrix_parent_id[$row['element_id']] = $row['element_matrix_parent_id'];
		}

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
						 element_matrix_allow_multiselect,
						 element_default_value 
				     from 
				         `".LA_TABLE_PREFIX."form_elements` 
				    where 
				    	 form_id=? and element_status=1 and element_type not in('section','page_break')
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$element_radio_has_other = array();
		
		$multi_status_parent_element = 0;

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
				
			}
			elseif ('simple_name' == $element_type){ //simple name has 2 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Last';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				
			}
			elseif ('simple_name_wmiddle' == $element_type){ //simple name with middle has 3 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Middle';
				$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
				
			}
			elseif ('name' == $element_type){ //name has 4 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - Title';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
				$column_name_lookup['element_'.$row['element_id'].'_4'] = $row['element_title'].' - Suffix';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_4'] = $row['element_type'];
				
			}
			elseif ('name_wmiddle' == $element_type){ //name with middle has 5 fields
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
				
			}
			elseif('money' == $element_type){//money format
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				if(!empty($element_constraint)){
					$column_type_lookup['element_'.$row['element_id']] = 'money_'.$element_constraint; //euro, pound, yen,etc
				}else{
					$column_type_lookup['element_'.$row['element_id']] = 'money_dollar'; //default is dollar
				}
			}
			elseif('checkbox' == $element_type){ //checkboxes, get childs elements
							
				$this_checkbox_options = $element_option_lookup[$row['element_id']];
				
				foreach ($this_checkbox_options as $option_id=>$option){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = $option;
					$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = $row['element_type'];
				}
			}
			elseif ('time' == $element_type){
				
				if(!empty($row['element_time_showsecond']) && !empty($row['element_time_24hour'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time_24hour';
				}elseif(!empty($row['element_time_showsecond'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time';
				}elseif(!empty($row['element_time_24hour'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time_24hour_noseconds';
				}else{
					$column_type_lookup['element_'.$row['element_id']] = 'time_noseconds';
				}
				
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
			}
			elseif('matrix' == $element_type){ 
				if ($matrix_parent_id[$row['element_id']] == 0) {
					$multi_status_parent_element = $matrix_multiselect_status[$row['element_id']];
				}
				//if(empty($matrix_multiselect_status[$row['element_id']])){
				if($multi_status_parent_element == 0){
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
			}
			elseif('signature' == $element_type){
				//don't display signature field
				continue;
			}
			else{
				//add options for cascade form here 
				// echo "in else element type:-".$row['element_type'].'<br>';
				// echo "in else element title:-".$row['element_title'].'<br>';
				// echo "in else element id:-".$row['element_id'].'<br>';
				//for other elements with only 1 field
				// $column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				// $column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
 
				if( $row['element_type'] == 'casecade_form' ) {
					//add checks for cascade forms
					$casecade_form_id = $row['element_default_value'];
					// echo "in if".$casecade_form_id;
					//select form name working here
 
					$form_name = get_form_name($dbh, $casecade_form_id);
					
					if( empty($form_name) )
						$form_name = 'Cascade Form';

					$cascade_form_fields = get_form_fields($dbh, $casecade_form_id);
					// print_r($cascade_form_fields);
					
					if( count($cascade_form_fields) > 0 ) {
						
						foreach ($cascade_form_fields as $field) {
							$table_header_1 = $field['element_title'];

							$column_name_lookup['cascade_'.$casecade_form_id.'_'.$field['element_id']] = $table_header_1;
							$column_type_lookup['cascade_'.$casecade_form_id.'_'.$field['element_id']] = $field['element_type'];
						}
					}
				} else {
					//these were there already, dont remove as only titles are shown for other fields
					$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
					$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
				}
				// $cascade_form_data = display_casecade_form_fields(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $row1['company_id']));
			}
		}
		
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

			// if($row['element_name'] != 'date_created'){
			// 	continue;
			// }
			$column_prefs[] = $row['element_name'];
		}		

		// var_dump($column_prefs);
		// print_r($column_name_lookup);
		//if there is no column preferences, display the first 6 fields
		if(empty($column_prefs)){
			$temp_slice = array_slice($column_name_lookup,0,10);
			unset($temp_slice['date_updated']);
			unset($temp_slice['ip_address']);
			$column_prefs = array_keys($temp_slice);
		}
		
		//determine column labels
		//the first 2 columns are always id and row_num
		$column_labels = array();

		$column_labels[] = 'la_id';
		$column_labels[] = 'la_row_num';
		$column_labels[] = 'Entity';
		$column_labels[] = 'approval_status';
		$column_labels[] = 'template_document';
		$column_labels[] = 'Audit Status';
		
		// print_r($column_prefs);
		foreach($column_prefs as $column_name){
			//dont show any extra columns for form fields in table header
			if( $column_name != 'date_created' )
				continue;
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

		//check for filter data and build the filter query
		if(!empty($filter_data)){

			$condition_type = ' OR ';

			$where_clause_array = array();

			foreach ($filter_data as $value) {
				$element_name 	  = $value['element_name'];
				$filter_condition = $value['filter_condition'];
				$filter_keyword   = addslashes($value['filter_keyword']);

				$filter_element_type = $column_type_lookup[$element_name];

				$temp = explode('_', $element_name);
				$element_id = $temp[1];
				if(in_array($filter_element_type, array('radio','select','matrix_radio'))){
					
					//these types need special steps to filter
					//we need to look into the ap_element_options first and do the filter there
					$null_clause = '';
					if($filter_condition == 'is'){
						if(empty($filter_keyword)){
							$where_operand = '<>';
							$where_keyword = "'{$filter_keyword}'";
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						} else{
							$where_operand = '=';
							$where_keyword = "'{$filter_keyword}'";
						}
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
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
							$where_clause_array[] = "(field_name = '{$element_name}' AND data_value IN('{$filtered_option_id}') OR field_name = '{$element_name}_other' AND data_value {$where_operand} {$where_keyword} {$null_clause})"; 
						}else{
							$where_clause_array[] = "(field_name = '{$element_name}_other' AND data_value {$where_operand} {$where_keyword} {$null_clause})";
						}
					}else{//otherwise, for the rest of the field types
						if(!empty($filtered_option_id_array)){							
							if(!empty($null_clause)){
								$where_clause_array[] = "(field_name='{$element_name}' AND data_value IN('{$filtered_option_id}') {$null_clause})";
							}else{
								$where_clause_array[] = "field_name='{$element_name}' AND data_value IN('{$filtered_option_id}')";
							} 
						}else{
							if(!empty($null_clause)){
								$where_clause_array[] = str_replace("OR", '', $null_clause);
							}
						}
					}
				}elseif(in_array($filter_element_type, array('date','europe_date'))){
					
					$date_exploded = array();
					$date_exploded = explode('/', $filter_keyword); //the filter_keyword has format mm/dd/yyyy

					$filter_keyword = $date_exploded[2].'-'.$date_exploded[0].'-'.$date_exploded[1];

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}

					$where_clause_array[] = "field_name = '{$element_name}' AND date(data_value) {$where_operand} {$where_keyword}"; 
				}else{
					$null_clause = '';
					
					if($filter_condition == 'is'){
						if(empty($filter_keyword)){
							$where_operand = '<>';
							$where_keyword = "'{$filter_keyword}'";
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						} else{
							$where_operand = '=';
							$where_keyword = "'{$filter_keyword}'";
						}
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_one'){
						$where_operand = '=';
						$where_keyword = "'1'";
					}elseif($filter_condition == 'is_zero'){
						
						$where_operand = '<>';
						$where_keyword = "'0'";
					}
		 			
		 			if(!empty($null_clause)){
						if($element_name == 'id'){
							$null_clause = "OR {$element_name} <> NULL";
							$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword} {$null_clause})";
						} else {
		 					$where_clause_array[] = "(field_name='{$element_name}' AND data_value {$where_operand} {$where_keyword} {$null_clause})";
						}
		 			}else{
						if($element_name == 'id'){
							$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword})";
						} else {
		 					$where_clause_array[] = "field_name='{$element_name}' AND data_value {$where_operand} {$where_keyword}";
						}
		 			}
					
				}
			}
			
			$where_clause = implode($condition_type, $where_clause_array);
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

		}

		/** pagination **/
		//identify how many database rows are available
		if($options['form_resume_enable'] == 1){
			$form_resume_enable = "form_resume_enable=1";
		} else {
			$form_resume_enable = 1;
		}
		//check if the user is examiner or not
		$examiner_where_clause = "";
		if($_SESSION['is_examiner'] == 1) {
			$entity_array = array("0");
			$query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
			$sth_entity = la_do_query($query_entity, array($user_id), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)) {
				array_push($entity_array, $row_entity['entity_id']);
			}
			$string_entity_ids = implode(',', $entity_array);
			$examiner_where_clause = "`company_id` IN ($string_entity_ids)";
			if($where_clause != '') {
				$where_clause .= " AND `company_id` IN ($string_entity_ids)";
			} else {
				$where_clause .= "`company_id` IN ($string_entity_ids)";
			}
		}
		if($examiner_where_clause == "") {
			$query = "SELECT COUNT(DISTINCT(company_id)) as total_row FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE ".$form_resume_enable."";
		} else {
			$query = "SELECT COUNT(DISTINCT(company_id)) as total_row FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE ".$form_resume_enable." AND ".$examiner_where_clause;
		}		
		$params = array();
			
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$numrows   = $row['total_row']; 
		$lastpage  = ceil($numrows/$rows_per_page);
							
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
		array_unshift($column_prefs,"id","row_num","approval_status","template_document","audit");
		
		//update $i according to page number
		if( $pageno == 1 ) {
			$i=0;
		} else {
			$i = $rows_per_page * ($pageno - 1);
		}
		
		if($sort_element != 'id'){
			if($sort_element == 'date_created'){
				if($sort_order == 'asc'){
					$sort_order = 'desc';
				} else {
					$sort_order = 'asc';
				}
				if($where_clause != ''){
				$query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} ORDER BY id {$sort_order}";
			} else {
				$query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} ORDER BY id {$sort_order}";
				}
			} else {
				if($where_clause != ''){
						$query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} OR field_name=' {$sort_element}' ORDER BY data_value {$sort_order} {$limit}";
				} else {
					   $query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND  field_name = '{$sort_element}' ORDER BY data_value {$sort_order} {$limit}";
				}
			}
		} 
		else {
			if($where_clause != ''){
				$query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} ORDER BY id {$sort_order} {$limit}";
			} else {
				$query1 = "SELECT DISTINCT(company_id) FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} ORDER BY id {$sort_order} {$limit}";
			}
		}
		
		$params = array();
		$sth1 = la_do_query($query1,$params,$dbh);
		$allowed_columns = ['approval_status', 'date_created', 'template_document', 'audit'];
		$show_approval_column = false;
		$$show_template_document_column = false;
		while($row1 = la_do_fetch_result($sth1)){
			$j=0;
			// print_r($column_prefs);
			foreach($column_prefs as $column_name){
				

				if($column_name == 'id'){
					$form_data[$i][$j] = $i+1;
				} elseif($column_name == 'row_num'){
					$form_data[$i][$j] = $i+1;
				}else {
					if( in_array($column_name, $allowed_columns) ) {

					} else if( $column_name != 'date_created' ){
						continue;
					}

					$query = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id = '".$row1['company_id']."' AND field_name = '".$column_name."'";
					$params = array();
					$sth = la_do_query($query,$params,$dbh);
					$row = la_do_fetch_result($sth);

					// if( $column_name == 'approval_status' ) {
					// 	if(strlen($row['data_value']) > $max_data_length){
					// 		$row['data_value'] = substr($row['data_value'],0,$max_data_length).'...';
					// 	}
					// } else 

					if( $column_name != 'approval_status' ) {
						if($column_type_lookup[$column_name] != 'file' ){
							if(strlen($row['data_value']) > $max_data_length){
								$row['data_value'] = substr($row['data_value'],0,$max_data_length).'...';
							}
						}
					}

					if ( $column_name == 'approval_status' ) {

						$user_can_approve = 0;


						$query  = "SELECT `data` FROM `".LA_TABLE_PREFIX."form_approval_logic_entry_data` where `form_id` = {$form_id} and company_id = {$row1['company_id']}";
						$result = la_do_query($query,array(),$dbh);
						$form_logic_data    = la_do_fetch_result($result);

						$logic_approver_enable = '';
						$logic_approver_enable_1_a = 0;

						if($form_logic_data){
							$form_logic_data_arr = json_decode($form_logic_data['data']);
							$logic_approver_enable = $form_logic_data_arr->logic_approver_enable;
							
							if( $logic_approver_enable == 1 ) {
								$logic_approver_enable_1_a = $form_logic_data_arr->selected_admin_check_1_a;
			 				}

			 				if( $show_approval_column == false )
			 					$show_approval_column = true;

						}





						if( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a == 1 ) ) {
							$user_can_approve = 1;
						} elseif( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a > 1 ) ) { //get users who can update form appove/deny status
							
							// $user_order_process_arr = $form_logic_data_arr->user_order_process;
							$all_selected_users = $form_logic_data_arr->all_selected_users;
							$approval_allowed_users = explode(',', $all_selected_users);

							// foreach ($user_order_process_arr as $user_order_obj) {
							// 	$approval_allowed_users[] = $user_order_obj->user_id;

							// }
							// print_r($user_id);
							// print_r($approval_allowed_users);

							if( in_array($user_id, $approval_allowed_users) ) {
								$user_can_approve = 1;
							}
						} elseif( $logic_approver_enable == 2 ) {
							//also take care of user order

							$user_order_process_arr = $form_logic_data_arr->user_order_process;
							$approval_allowed_users = [];

							foreach ($user_order_process_arr as $user_order_obj) {
								$approval_allowed_users[] = $user_order_obj->user_id;

							}


							if( in_array($user_id, $approval_allowed_users) ) {
								//this use can approve it
								$user_can_approve = 1;
							}
						}


						$not_my_turn = 0;
						$is_replied = 0;
						if( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a == 3 ) ) {
							//only selected users can approve/deny it but it will be considered as approved only if all users approve it
							$self_approval_status_query = "SELECT * FROM ".LA_TABLE_PREFIX."form_approvals WHERE company_id = '".$row1['company_id']."' AND user_id = $user_id";
							$params = array();
							$sth = la_do_query($self_approval_status_query,$params,$dbh);
							$self_approval_status_row = la_do_fetch_result($sth);
							$is_replied = $self_approval_status_row['is_replied'];
						}

						if( $logic_approver_enable == 2 ) {
							//only selected users can approve/deny it but it will be considered as approved only if all users approve it
							$self_approval_status_query = "SELECT * FROM ".LA_TABLE_PREFIX."form_approvals WHERE company_id = '".$row1['company_id']."' AND user_id = $user_id";
							$params = array();
							$sth = la_do_query($self_approval_status_query,$params,$dbh);
							$self_approval_status_row = la_do_fetch_result($sth);
							$is_replied = $self_approval_status_row['is_replied'];

							if( $is_replied == 0 ) {

								$query = "select 
										user_id, user_order
									from 
										".LA_TABLE_PREFIX."form_approvals 
								   where 
								   		company_id = '".$row1['company_id']."' AND is_replied = 0 order by user_order LIMIT 1";
								$params = array();
								$sth = la_do_query($query,$params,$dbh);
								while($row_current = la_do_fetch_result($sth)){
									$current_user_order_id = $row_current['user_id'];
								}
								// echo '$current_user_order_id-'.$current_user_order_id;
								// echo '$user_id-'.$user_id;
								if( $current_user_order_id == $user_id ) {
									$not_my_turn = 0;
								} else {
									$not_my_turn = 1;
								}
							}
						}




						$form_data[$i][$j] = $row['data_value'].','.$row1['company_id'].','.$is_replied.','.$not_my_turn.','.$user_can_approve;
						
					}
					else if ( $column_name == 'template_document' ) {

						//check if the document is in cron queue
						$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` where `form_id` = ? and `company_user_id` = ? and status != 1 order by id DESC LIMIT 1";
						$sth_document_process = la_do_query($query_document_process,array($form_id, $row1['company_id']),$dbh);
						$row_document_process = la_do_fetch_result($sth_document_process);
						if( $row_document_process['id'] ) {
							//check if the form has any template files attached
							$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
							$param_template_count = array($form_id);
							$result_template_count = la_do_query($query_template_count, array($form_id), $dbh);
							$template_rows = $result_template_count->fetchColumn();
							if( $template_rows > 0 ) {
								//latest document has not been created yet
								if( $row_document_process['status'] == 0 ) {
									$form_data[$i][$j] = "queue,Document is scheduled to be created.,{$row1['company_id']}";
								} else if( $row_document_process['status'] == 2 ) {
									$form_data[$i][$j] = "queue,Document is generating now.,{$row1['company_id']}";
								}
								if( $show_template_document_column == false )
				 					$show_template_document_column = true;
							}							
						} else {
							//get template_document generated for this entry
							$query_template_document = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = {$form_id} and `company_id` = {$row1['company_id']} and `isZip` = 1 order by `docx_create_date` DESC";
				            $sth_template_document = la_do_query($query_template_document,array(),$dbh);
				            $row_template_document = la_do_fetch_result($sth_template_document);
				            if( $row_template_document ) {
				            	$form_data[$i][$j] = $row_template_document['docxname'].','.$row1['company_id'];

				            	if( $show_template_document_column == false )
				 					$show_template_document_column = true;

				            } else {
				            	$form_data[$i][$j] = '';
				            }
				        }
					}
					else if($column_type_lookup[$column_name] == 'time'){
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i:s A",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} 
					elseif ($column_type_lookup[$column_name] == 'time_noseconds'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i A",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} 
				  	elseif ( strpos($column_name, 'cascade_') !== false) {
				  
						$split_column = explode('_',$column_name);
						$form_type = $split_column[0];
						$cascade_form_id = $split_column[1];
						$cascade_element_id = $split_column[2];
						$row_markup_array = display_casecade_form_fields_mod(array('dbh' => $dbh, 'form_id' => $cascade_form_id, 'parent_form_id' => $form_id, 'entry_id' => $row1['entry_id'], 'company_id' => $row1['company_id'], 'element_id' => $cascade_element_id));
						
						$params = array();					
						$sth = la_do_query($query,$params,$dbh);
						$row = la_do_fetch_result($sth);
						$cascade_data_value  = $row['data_value'];  
						$form_data[$i][$j] = $row_markup_array['row_markup'];
 
					}
					
					elseif ($column_type_lookup[$column_name] == 'time_24hour_noseconds'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'time_24hour'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i:s",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} elseif (substr($column_type_lookup[$column_name],0,5) == 'money'){ //set column formatting for money fields
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
							$form_data[$i][$j] = '<div class="me_right_div">'.$currency.$row['data_value'].'</div>';
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'date'){ //date with format MM/DD/YYYY
						if(!empty($row['data_value']) && ($row['data_value'] != '0000-00-00')){
							$form_data[$i][$j]  = date('M d, Y',strtotime($row['data_value']));
						}else{
							$form_data[$i][$j] = '';
						}
						if($column_name == 'date_created' || $column_name == 'date_updated'){
							$form_data[$i][$j] = la_short_relative_date($row['data_value']);
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'europe_date'){ //date with format DD/MM/YYYY
						if(!empty($row['data_value']) && ($row['data_value'] != '0000-00-00')){
							$form_data[$i][$j]  = date('d M Y',strtotime($row['data_value']));
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'number'){
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = $row['data_value'];
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif (in_array($column_type_lookup[$column_name],array('radio','select'))){ //multiple choice or dropdown
						$exploded = array();
						$exploded = explode('_',$column_name);
						$this_element_id = $exploded[1];
						$this_option_id  = $row['data_value'];
						
						$form_data[$i][$j] = $element_option_lookup[$this_element_id][$this_option_id];
						
						if($column_type_lookup[$column_name] == 'radio'){
							if($element_radio_has_other['element_'.$this_element_id] === true && empty($form_data[$i][$j])){
								$form_data[$i][$j] = $row['data_value'];
							}
						}
					} elseif(substr($column_type_lookup[$column_name],0,6) == 'matrix'){
						$exploded = array();
						$exploded = explode('_',$column_type_lookup[$column_name]);
						$matrix_type = $exploded[1];
	
						if($matrix_type == 'radio'){
							$exploded = array();
							$exploded = explode('_',$column_name);
							$this_element_id = $exploded[1];
							$this_option_id  = $row['data_value'];
							
							$form_data[$i][$j] = $matrix_element_option_lookup[$this_element_id][$this_option_id];
						}elseif($matrix_type == 'checkbox'){
							if(!empty($row['data_value'])){
								$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
							}else{
								$form_data[$i][$j]  = '';
							}
						}
					} elseif($column_type_lookup[$column_name] == 'checkbox'){
					
						if(!empty($row['data_value'])){
							if(substr($column_name,-5) == "other"){ //if this is an 'other' field, display the actual value
								$form_data[$i][$j] = htmlspecialchars($row['data_value'],ENT_QUOTES);
							}else{
								$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
							}
						}else{
							$form_data[$i][$j]  = '';
						}
					
					} elseif(in_array($column_type_lookup[$column_name],array('phone','simple_phone'))){ 
						if(!empty($row['data_value'])){
							if($column_type_lookup[$column_name] == 'phone'){
								$form_data[$i][$j] = '('.substr($row['data_value'],0,3).') '.substr($row['data_value'],3,3).'-'.substr($row['data_value'],6,4);
							}else{
								$form_data[$i][$j] = $row['data_value'];
							}
						}
					} elseif($column_type_lookup[$column_name] == 'file'){
						if(!empty($row['data_value'])){
							$raw_files = array();
							$raw_files = explode('|',$row['data_value']);
							$clean_filenames = array();
	
							foreach($raw_files as $hashed_filename){
								$file_1 	    =  substr($hashed_filename,strpos($hashed_filename,'-')+1);
								$filename_value = substr($file_1,strpos($file_1,'-'));
								$clean_filenames[] = htmlspecialchars($filename_value);
							}
	
							$clean_filenames_joined = implode(', ',$clean_filenames);
							$form_data[$i][$j]  = '<div class="me_file_div">'.$clean_filenames_joined.'</div>';
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif($column_type_lookup[$column_name] == 'payment_status'){
						if($row[$column_name] == 'paid'){
							$payment_status_color = 'style="color: green;font-weight: bold"';
							$payment_status_label = strtoupper($row['data_value']);
						}else{
							$payment_status_color = '';
							$payment_status_label = ucfirst(strtolower($row['data_value']));
						}	
						$form_data[$i][$j] = '<span '.$payment_status_color.'>'.$payment_status_label.'</span>';
					}else{
						$form_data[$i][$j] = html_entity_decode($row['data_value']);//htmlspecialchars(str_replace("\r","",str_replace("\n"," ",$row['data_value'])),ENT_QUOTES);
					}
				}
				
				$j++;
				
			}
			
			$company_id[$i] = $row1['company_id'];
			$i++;
			
	    }
		
		//generate table markup for the entries
		$table_header_markup = '<thead><tr>'."\n";
		foreach($column_labels as $label_name){
			if($label_name == 'la_id'){
				$table_header_markup .= '<th class="me_action" scope="col"><input type="checkbox" value="1" name="col_select" id="col_select" /></th>'."\n";
			}elseif($label_name == 'la_row_num'){
				$table_header_markup .= '<th class="me_number" scope="col">#</th>'."\n";
			}elseif($label_name == 'approval_status'){
				$table_header_markup .= '<th scope="col" class="approval_status">Approval Status</th>'."\n";
			}elseif($label_name == 'template_document'){
				$table_header_markup .= '<th scope="col" class="template_document">Template Document</th>'."\n";
			}else{
				$table_header_markup .= '<th scope="col"><div title="'.$label_name.'">'.$label_name.'</div></th>'."\n";	
			}
			
		}

		$table_header_markup .= '</tr></thead>'."\n";

		$table_body_markup = '<tbody>'."\n";

		$toggle = false;
		
		$first_row_number = ($pageno -1) * $rows_per_page + 1;
		$last_row_number  = $first_row_number;
		
		$m = 0;

		$casecade_children = get_casecade_children($dbh, $form_id);

		if(!empty($form_data)){
			// print_r($form_data);
			// die();
			foreach($form_data as $row_data){
				$n = 1;
				if($toggle){
					$toggle = false;
					$row_style = 'class="alt"';
				}else{
					$toggle = true;
					$row_style = '';
				}
				$query = "select DISTINCT(company_id) from ".LA_TABLE_PREFIX."form_{$form_id}";
				$params = array();
				$sth = la_do_query($query,$params,$dbh);
				
				$o = 0;
				$cid = null;
				while($row = la_do_fetch_result($sth)){
					$companyId_approval = $row['company_id'];
					if($row['company_id'] == $company_id[$m]){
						$o = $n;
						$cid = $row['company_id'];
					}
					$n++;
				}
							
				$table_body_markup .= '<input type="hidden" name="companyId[]" id="companyId_'.$m.'" companyId="'.$cid.'" value="'.$o.'">';
				
				$table_body_markup .= "<tr id=\"row_{$row_data[0]}\" {$row_style}>";
				foreach ($row_data as $key => $column_data){
					if($key == 0){ //this is "id" column
						$table_body_markup .= '<td class="me_action"><input class="entry_selection" type="checkbox" id="entry_'.$column_data.'" name="entry_'.$column_data.'" value="'.$o.'" /></td>'."\n";
					}elseif ($key == 1){ //this is "row_num" column
						$table_body_markup .= '<td class="me_number">'.$column_data.'</td>'."\n";
					}elseif ($key == 2){ //this is "approval" column
						$status_company = explode(',', $column_data);
						// $column_data = 1;
						$form_approval_status = $status_company[0];
						$companyid = $status_company[1];
						$is_replied = $status_company[2];
						$not_my_turn = $status_company[3];
						$user_can_approve = $status_company[4];

						$entQuery = "SELECT company_name FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
						$entParams = array($companyid);
						$sth2 = la_do_query($entQuery,$entParams,$dbh);
						$entitiesText = '';
						while($row = la_do_fetch_result($sth2)){
							$entitiesText .= $row['company_name'] . "<br>";
						}
						if ($entitiesText == '') {
							$entitiesText = "Admin";
						}
						$table_body_markup .="<td>$entitiesText</td>";

						


						if( is_numeric($form_approval_status) ) {
							
							if ( $form_approval_status == 1) { // form is approved
								$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Approved</td>';
							} elseif ( $form_approval_status == 2) { // form is dis-approved
								$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-denied"></span> Denied</td>';
							} else { //the form approval status is not updated

								if( $user_can_approve == 1 ) { //it means only certian users are allowed to appove/deny this form

									if( $not_my_turn ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Not My Turn</td>';
									}elseif( $is_replied == 1 ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Approval pending</td>';
									} elseif( $is_replied == 2 ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-denied"></span> Approval pending</td>';
									}else {
										$table_body_markup .= '<td class="me_action approval_status" data-cid="'.$companyid.'"><a class="approve_action bb_button bb_green" data-action="approve">Approve</a> <a class="approve_action bb_button bb_red deny-entry"  data-action="deny">Deny</a></td>'."\n";
									}

								} else {
									$table_body_markup .='<td class="approval_status"></td>';
								}

							}
						} else {
							$table_body_markup .='<td class="approval_status"></td>';
						}

					}elseif ($key == 3){ //this is "template document" column
						$document_data_company_id = '';
						$document_data_template_name = '';
						if( !empty($column_data) ) {
							$document_data_arr = explode(',', $column_data);
							if( $document_data_arr[0] == 'queue' ) {
								$table_body_markup .="<td class=\"me_action template_document\">{$document_data_arr[1]}";
								$document_data_company_id = $document_data_arr[2];
							} else  {
								$document_data_company_id = $document_data_arr[1];
								$document_data_template_name = $document_data_arr[0];
								$table_body_markup .="<td class=\"me_action template_document\"><a target=\"_blank\" href=\"javascript:void(0);\" class=\"action-download-document-zip\" data-documentdownloadlink=\"{$la_settings_base_url}download_document_zip.php?id={$document_data_template_name}&form_id={$form_id}&entry_id={$row_data[1]}&company_id={$document_data_company_id}\" >{$document_data_template_name}</a>";	
							}

							//check if this form has any casecade child and if document is created for child casecade
							// print_r($casecade_children);
							if( !empty($casecade_children) ) {
								foreach ($casecade_children as $casecade_child) {
									$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` where `form_id` = ? and `company_user_id` = ? and status != 1 order by id DESC LIMIT 1";
									$sth_document_process = la_do_query($query_document_process,array($casecade_child, $document_data_company_id),$dbh);
									$row_document_process = la_do_fetch_result($sth_document_process);
									if( $row_document_process['id'] ) {
										//latest document has not been created yet
										if( $row_document_process['status'] == 0 ) {
											$table_body_markup .= "<br>A Document for Cascade form #{$casecade_child} is scheduled to be created.";
										} else if( $row_document_process['status'] == 2 ) {
											$table_body_markup .= "<br>A Document for Cascade form #{$casecade_child} is generating now.";
										}
									} else {
									
										$query_casecade_child = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = {$casecade_child} and `company_id` = {$document_data_company_id} and `isZip` = 1 order by `docx_create_date` DESC";
							            $sth_casecade_child = la_do_query($query_casecade_child,array(),$dbh);
							            $row_casecade_child = la_do_fetch_result($sth_casecade_child);

							            if( $row_casecade_child ) {
							            	$table_body_markup .="<br><a target=\"_blank\" href=\"javascript:void(0);\" class=\"action-download-document-zip\" data-documentdownloadlink=\"{$la_settings_base_url}download_document_zip.php?id={$row_casecade_child['docxname']}&form_id={$casecade_child}&entry_id={$row_data[1]}&company_id={$document_data_company_id}\" >{$row_casecade_child['docxname']}</a>";
							            }
										
									}
								}
							}
							$table_body_markup .= "</td>";
						} else {
							$table_body_markup .="<td class=\"me_action template_document\"></td>";
						}
						
					}elseif ($key == 4){
						$checked = $column_data == "1" ? "checked" : "";
						$label_class = $column_data == "1" ? "enabled" : ""; 

						$table_body_markup .= '<td class="me_action" audit="'.$column_data.'">
							<label class="switch">
								<input type="checkbox" id="audit-mode-'.$cid.'" '.$checked.'>
								<span class="slider round"></span>
							</label>
							<label class="'.$label_class.'" for="audit-mode-'.$cid.'">Audit Mode</label>
						</td>'."\n";
					}else {
						
						//$column_data
					
						if ($column_data != strip_tags($column_data)) { 
							//Is HTML
							$column_data = strip_tags($column_data);
						}
						
						$table_body_markup .= '<td><div>'.$column_data.'</div></td>'."\n";
					}
				}
				// if (count($row_data) == $key+1) {
				// 	// Mismatched extra
				// 	$table_body_markup .='<td class="last"></td>';
				// }
				$table_body_markup .= "</tr>"."\n";
				$last_row_number++;
				$m++;
				
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

		$entries_markup = '<div id="entries_container" data-formid="'.$form_id.'">';
		$entries_markup .= $table_markup;
		$entries_markup .= '</div>';

		//hide the approval logic column if no entry has data for it
		if( $show_approval_column == false )
			$entries_markup .= '<style type="text/css">#entries_table .approval_status { display:none; }</style>';

		//hide the approval logic column if no entry has data for it
		if( $show_template_document_column == false )
			$entries_markup .= '<style type="text/css">#entries_table .template_document { display:none; }</style>';

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

	function la_display_entries_table_v2($dbh, $form_id, $options, $column_name_lookup, $column_type_lookup)
	{		
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
		$la_settings_base_url = $options['base_url'];

		if(empty($sort_element)){ //set the default sorting order
			$sort_element = 'id';
			$sort_order	  = 'asc';
		}

		$form_properties = la_get_form_properties($dbh,$form_id,array('payment_currency','payment_enable_merchant'));
		$payment_currency = strtoupper($form_properties['payment_currency']);
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
		$query = "select DISTINCT A.element_id, A.option_id, IF (B.element_matrix_parent_id = 0, A.option, null)as option_label
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
						A.element_matrix_allow_multiselect as 'multiselect'
					from 
						".LA_TABLE_PREFIX."form_elements A
					where 
						A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
			$matrix_parent_id[$row['element_id']] = $row['element_matrix_parent_id'];
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
						 element_matrix_allow_multiselect,
						 element_default_value 
				     from 
				         `".LA_TABLE_PREFIX."form_elements` 
				    where 
				    	 form_id=? and element_status=1 and element_type not in('section','page_break')
				 order by 
				 		 element_position asc";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		$element_radio_has_other = array();
		
		$multi_status_parent_element = 0;

		
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

			// if($row['element_name'] != 'date_created'){
			// 	continue;
			// }
			$column_prefs[] = $row['element_name'];
		}		

		// var_dump($column_prefs);
		// print_r($column_name_lookup);
		//if there is no column preferences, display the first 6 fields
		if(empty($column_prefs)){
			$temp_slice = array_slice($column_name_lookup,0,10);
			unset($temp_slice['date_updated']);
			unset($temp_slice['ip_address']);
			unset($temp_slice['id']);
			$column_prefs = array_keys($temp_slice);
		}

		//determine column labels
		//the first 2 columns are always id and row_num
		$column_labels = array();

		$column_labels[] = 'la_id';
		$column_labels[] = 'la_row_num';
		$column_labels[] = 'Entity';
		$column_labels[] = 'approval_status';
		$column_labels[] = 'template_document';
		$column_labels[] = 'Audit Status';
		
		// print_r($column_prefs);
		foreach($column_prefs as $column_name){
			//dont show any extra columns for form fields in table header
			if( $column_name != 'date_created' )
				continue;
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

		//check for filter data and build the filter query
		if(!empty($filter_data)){

			$condition_type = ' OR ';

			$where_clause_array = array();

			foreach ($filter_data as $value) {
				$element_name 	  = $value['element_name'];
				$filter_condition = $value['filter_condition'];
				$filter_keyword   = addslashes($value['filter_keyword']);

				$filter_element_type = $column_type_lookup[$element_name];

				$temp = explode('_', $element_name);
				$element_id = $temp[1];
				if(in_array($filter_element_type, array('radio','select','matrix_radio'))){
					
					//these types need special steps to filter
					//we need to look into the ap_element_options first and do the filter there
					$null_clause = '';
					if($filter_condition == 'is'){
						if(empty($filter_keyword)){
							$where_operand = '<>';
							$where_keyword = "'{$filter_keyword}'";
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						} else{
							$where_operand = '=';
							$where_keyword = "'{$filter_keyword}'";
						}
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
						}
					}elseif($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> 0";
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
							$where_clause_array[] = "(field_name = '{$element_name}' AND data_value IN('{$filtered_option_id}') OR field_name = '{$element_name}_other' AND data_value {$where_operand} {$where_keyword} {$null_clause})"; 
						}else{
							$where_clause_array[] = "(field_name = '{$element_name}_other' AND data_value {$where_operand} {$where_keyword} {$null_clause})";
						}
					}else{//otherwise, for the rest of the field types
						if(!empty($filtered_option_id_array)){							
							if(!empty($null_clause)){
								$where_clause_array[] = "(field_name='{$element_name}' AND data_value IN('{$filtered_option_id}') {$null_clause})";
							}else{
								$where_clause_array[] = "field_name='{$element_name}' AND data_value IN('{$filtered_option_id}')";
							} 
						}else{
							if(!empty($null_clause)){
								$where_clause_array[] = str_replace("OR", '', $null_clause);
							}
						}
					}
				}elseif(in_array($filter_element_type, array('date','europe_date'))){
					
					$date_exploded = array();
					$date_exploded = explode('/', $filter_keyword); //the filter_keyword has format mm/dd/yyyy

					$filter_keyword = $date_exploded[2].'-'.$date_exploded[0].'-'.$date_exploded[1];

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}

					$where_clause_array[] = "field_name = '{$element_name}' AND date(data_value) {$where_operand} {$where_keyword}"; 
				}else{
					$null_clause = '';
					
					if($filter_condition == 'is'){
						if(empty($filter_keyword)){
							$where_operand = '<>';
							$where_keyword = "'{$filter_keyword}'";
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						} else{
							$where_operand = '=';
							$where_keyword = "'{$filter_keyword}'";
						}
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR field_name='{$element_name}' AND data_value <> NULL";
						}
					}elseif($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_one'){
						$where_operand = '=';
						$where_keyword = "'1'";
					}elseif($filter_condition == 'is_zero'){
						
						$where_operand = '<>';
						$where_keyword = "'0'";
					}
		 			
		 			if(!empty($null_clause)){
						if($element_name == 'id'){
							$null_clause = "OR {$element_name} <> NULL";
							$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword} {$null_clause})";
						} else {
		 					$where_clause_array[] = "(field_name='{$element_name}' AND data_value {$where_operand} {$where_keyword} {$null_clause})";
						}
		 			}else{
						if($element_name == 'id'){
							$where_clause_array[] = "({$element_name} {$where_operand} {$where_keyword})";
						} else {
		 					$where_clause_array[] = "field_name='{$element_name}' AND data_value {$where_operand} {$where_keyword}";
						}
		 			}
					
				}
			}
			
			$where_clause = implode($condition_type, $where_clause_array);
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

		}

		/** pagination **/
		//identify how many database rows are available
		if($options['form_resume_enable'] == 1){
			$form_resume_enable = "form_resume_enable=1";
		} else {
			$form_resume_enable = 1;
		}
		//check if the user is examiner or not
		$examiner_where_clause = "";
		if($_SESSION['is_examiner'] == 1) {
			$entity_array = array("0");
			$query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
			$sth_entity = la_do_query($query_entity, array($user_id), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)) {
				array_push($entity_array, $row_entity['entity_id']);
			}
			$string_entity_ids = implode(',', $entity_array);
			$examiner_where_clause = "`company_id` IN ($string_entity_ids)";
			if($where_clause != '') {
				$where_clause .= " AND `company_id` IN ($string_entity_ids)";
			} else {
				$where_clause .= "`company_id` IN ($string_entity_ids)";
			}
		}
		if($examiner_where_clause == "") {
			$query = "SELECT COUNT(DISTINCT `company_id`, `entry_id`) as total_row FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE ".$form_resume_enable."";
		} else {
			$query = "SELECT COUNT(DISTINCT `company_id`, `entry_id`) as total_row FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE ".$form_resume_enable." AND ".$examiner_where_clause;
		}		
		$params = array();
			
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$numrows   = $row['total_row']; 
		$lastpage  = ceil($numrows/$rows_per_page);
							
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
		array_unshift($column_prefs,"id","row_num","approval_status","template_document","audit");
		
		//update $i according to page number
		if( $pageno == 1 ) {
			$i=0;
		} else {
			$i = $rows_per_page * ($pageno - 1);
		}
		
		if($sort_element != 'id'){
			if($sort_element == 'date_created'){
				if($sort_order == 'asc'){
					$sort_order = 'desc';
				} else {
					$sort_order = 'asc';
				}
				if($where_clause != ''){
				$query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} ORDER BY id {$sort_order}";
			} else {
				$query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} ORDER BY id {$sort_order}";
				}
			} else {
				if($where_clause != ''){
						$query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} OR field_name=' {$sort_element}' ORDER BY data_value {$sort_order} {$limit}";
				} else {
					   $query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND  field_name = '{$sort_element}' ORDER BY data_value {$sort_order} {$limit}";
				}
			}
		} 
		else {
			if($where_clause != ''){
				$query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} AND {$where_clause} ORDER BY id {$sort_order} {$limit}";
			} else {
				$query1 = "SELECT DISTINCT `company_id`, `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE {$form_resume_enable} ORDER BY id {$sort_order} {$limit}";
			}
		}
		
		$params = array();
		$sth1 = la_do_query($query1,$params,$dbh);
		$allowed_columns = ['approval_status', 'date_created', 'template_document', 'audit'];
		$show_approval_column = false;
		$$show_template_document_column = false;
		while($row1 = la_do_fetch_result($sth1)){
			$j=0;
			foreach($column_prefs as $column_name){
				if($column_name == 'id'){
					$form_data[$i][$j] = $i+1;
				} elseif($column_name == 'row_num'){
					$form_data[$i][$j] = $i+1;
				}else {
					if( in_array($column_name, $allowed_columns) ) {

					} else if( $column_name != 'date_created' ){
						continue;
					}

					$query = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id = ? AND entry_id = ? AND field_name = ?";
					$params = array();
					$sth = la_do_query($query, array($row1['company_id'], $row1['entry_id'], $column_name), $dbh);
					$row = la_do_fetch_result($sth);

					if( $column_name != 'approval_status' ) {
						if($column_type_lookup[$column_name] != 'file' ){
							if(strlen($row['data_value']) > $max_data_length){
								$row['data_value'] = substr($row['data_value'],0,$max_data_length).'...';
							}
						}
					}

					if ( $column_name == 'approval_status' ) {

						$user_can_approve = 0;

						$query  = "SELECT `data` FROM `".LA_TABLE_PREFIX."form_approval_logic_entry_data` where `form_id` = {$form_id} and company_id = {$row1['company_id']}";
						$result = la_do_query($query,array(),$dbh);
						$form_logic_data    = la_do_fetch_result($result);

						$logic_approver_enable = '';
						$logic_approver_enable_1_a = 0;

						if($form_logic_data){
							$form_logic_data_arr = json_decode($form_logic_data['data']);
							$logic_approver_enable = $form_logic_data_arr->logic_approver_enable;
							
							if( $logic_approver_enable == 1 ) {
								$logic_approver_enable_1_a = $form_logic_data_arr->selected_admin_check_1_a;
			 				}

			 				if( $show_approval_column == false )
			 					$show_approval_column = true;
						}

						if( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a == 1 ) ) {
							$user_can_approve = 1;
						} elseif( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a > 1 ) ) { //get users who can update form appove/deny status
							
							// $user_order_process_arr = $form_logic_data_arr->user_order_process;
							$all_selected_users = $form_logic_data_arr->all_selected_users;
							$approval_allowed_users = explode(',', $all_selected_users);

							// foreach ($user_order_process_arr as $user_order_obj) {
							// 	$approval_allowed_users[] = $user_order_obj->user_id;

							// }
							// print_r($user_id);
							// print_r($approval_allowed_users);

							if( in_array($user_id, $approval_allowed_users) ) {
								$user_can_approve = 1;
							}
						} elseif( $logic_approver_enable == 2 ) {
							//also take care of user order

							$user_order_process_arr = $form_logic_data_arr->user_order_process;
							$approval_allowed_users = [];

							foreach ($user_order_process_arr as $user_order_obj) {
								$approval_allowed_users[] = $user_order_obj->user_id;
							}

							if( in_array($user_id, $approval_allowed_users) ) {
								//this use can approve it
								$user_can_approve = 1;
							}
						}

						$not_my_turn = 0;
						$is_replied = 0;
						if( ( $logic_approver_enable == 1 ) && ( $logic_approver_enable_1_a == 3 ) ) {
							//only selected users can approve/deny it but it will be considered as approved only if all users approve it
							$self_approval_status_query = "SELECT * FROM ".LA_TABLE_PREFIX."form_approvals WHERE company_id = '".$row1['company_id']."' AND user_id = $user_id";
							$params = array();
							$sth = la_do_query($self_approval_status_query,$params,$dbh);
							$self_approval_status_row = la_do_fetch_result($sth);
							$is_replied = $self_approval_status_row['is_replied'];
						}

						if( $logic_approver_enable == 2 ) {
							//only selected users can approve/deny it but it will be considered as approved only if all users approve it
							$self_approval_status_query = "SELECT * FROM ".LA_TABLE_PREFIX."form_approvals WHERE company_id = '".$row1['company_id']."' AND user_id = $user_id";
							$params = array();
							$sth = la_do_query($self_approval_status_query,$params,$dbh);
							$self_approval_status_row = la_do_fetch_result($sth);
							$is_replied = $self_approval_status_row['is_replied'];

							if( $is_replied == 0 ) {

								$query = "select 
										user_id, user_order
									from 
										".LA_TABLE_PREFIX."form_approvals 
								   where 
								   		company_id = '".$row1['company_id']."' AND is_replied = 0 order by user_order LIMIT 1";
								$params = array();
								$sth = la_do_query($query,$params,$dbh);
								while($row_current = la_do_fetch_result($sth)){
									$current_user_order_id = $row_current['user_id'];
								}
								// echo '$current_user_order_id-'.$current_user_order_id;
								// echo '$user_id-'.$user_id;
								if( $current_user_order_id == $user_id ) {
									$not_my_turn = 0;
								} else {
									$not_my_turn = 1;
								}
							}
						}

						$form_data[$i][$j] = $row['data_value'].','.$row1['company_id'].','.$is_replied.','.$not_my_turn.','.$user_can_approve;
					}
					else if ( $column_name == 'template_document' ) {

						//check if the document is in cron queue
						$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ? AND status != 1 order by id DESC LIMIT 1";
						$sth_document_process = la_do_query($query_document_process, array($form_id, $row1['company_id'], $row1['entry_id']), $dbh);
						$row_document_process = la_do_fetch_result($sth_document_process);
						if( $row_document_process['id'] ) {
							//check if the form has any template files attached
							$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
							$param_template_count = array($form_id);
							$result_template_count = la_do_query($query_template_count, array($form_id), $dbh);
							$template_rows = $result_template_count->fetchColumn();
							if( $template_rows > 0 ) {
								//latest document has not been created yet
								if( $row_document_process['status'] == 0 ) {
									$form_data[$i][$j] = "queue,Document is scheduled to be created.,{$row1['company_id']}";
								} else if( $row_document_process['status'] == 2 ) {
									$form_data[$i][$j] = "queue,Document is generating now.,{$row1['company_id']}";
								}
								if( $show_template_document_column == false )
				 					$show_template_document_column = true;
							}							
						} else {
							//get template_document generated for this entry
							$query_template_document = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = {$form_id} AND `company_id` = ? AND `entry_id` = ? AND `isZip` = 1 order by `docx_create_date` DESC";
				            $sth_template_document = la_do_query($query_template_document,array($row1['company_id'], $row1['entry_id']),$dbh);
				            $row_template_document = la_do_fetch_result($sth_template_document);
				            if( $row_template_document ) {
				            	$form_data[$i][$j] = $row_template_document['docxname'].','.$row1['company_id'];

				            	if( $show_template_document_column == false )
				 							$show_template_document_column = true;

				            } else {
				            	$form_data[$i][$j] = '';
				            }
				        }
					}
					else if($column_type_lookup[$column_name] == 'time'){
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i:s A",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} 
					elseif ($column_type_lookup[$column_name] == 'time_noseconds'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i A",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} 
				  	elseif ( strpos($column_name, 'cascade_') !== false) {
				  
						$split_column = explode('_',$column_name);
						$form_type = $split_column[0];
						$cascade_form_id = $split_column[1];
						$cascade_element_id = $split_column[2];
						$row_markup_array = display_casecade_form_fields_mod(array('dbh' => $dbh, 'form_id' => $cascade_form_id, 'parent_form_id' => $_GET['id'], 'entry_id' => $row1['entry_id'], 'company_id' => $row1['company_id'], 'element_id' => $cascade_element_id));
						
						$params = array();
						$sth = la_do_query($query,$params,$dbh);
						$row = la_do_fetch_result($sth);
						$cascade_data_value  = $row['data_value'];  
						$form_data[$i][$j] = $row_markup_array['row_markup'];
 
					}
					
					elseif ($column_type_lookup[$column_name] == 'time_24hour_noseconds'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'time_24hour'){ 
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = date("H:i:s",strtotime($row['data_value']));
						}else {
							$form_data[$i][$j] = '';
						}
					} elseif (substr($column_type_lookup[$column_name],0,5) == 'money'){ //set column formatting for money fields
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
							$form_data[$i][$j] = '<div class="me_right_div">'.$currency.$row['data_value'].'</div>';
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'date'){ //date with format MM/DD/YYYY
						if(!empty($row['data_value']) && ($row['data_value'] != '0000-00-00')){
							$form_data[$i][$j]  = date('M d, Y',strtotime($row['data_value']));
						}else{
							$form_data[$i][$j] = '';
						}
						if($column_name == 'date_created' || $column_name == 'date_updated'){
							$form_data[$i][$j] = la_short_relative_date($row['data_value']);
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'europe_date'){ //date with format DD/MM/YYYY
						if(!empty($row['data_value']) && ($row['data_value'] != '0000-00-00')){
							$form_data[$i][$j]  = date('d M Y',strtotime($row['data_value']));
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif ($column_type_lookup[$column_name] == 'number'){
						if(!empty($row['data_value'])){
							$form_data[$i][$j] = $row['data_value'];
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif (in_array($column_type_lookup[$column_name],array('radio','select'))){ //multiple choice or dropdown
						$exploded = array();
						$exploded = explode('_',$column_name);
						$this_element_id = $exploded[1];
						$this_option_id  = $row['data_value'];
						
						$form_data[$i][$j] = $element_option_lookup[$this_element_id][$this_option_id];
						
						if($column_type_lookup[$column_name] == 'radio'){
							if($element_radio_has_other['element_'.$this_element_id] === true && empty($form_data[$i][$j])){
								$form_data[$i][$j] = $row['data_value'];
							}
						}
					} elseif(substr($column_type_lookup[$column_name],0,6) == 'matrix'){
						$exploded = array();
						$exploded = explode('_',$column_type_lookup[$column_name]);
						$matrix_type = $exploded[1];
	
						if($matrix_type == 'radio'){
							$exploded = array();
							$exploded = explode('_',$column_name);
							$this_element_id = $exploded[1];
							$this_option_id  = $row['data_value'];
							
							$form_data[$i][$j] = $matrix_element_option_lookup[$this_element_id][$this_option_id];
						}elseif($matrix_type == 'checkbox'){
							if(!empty($row['data_value'])){
								$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
							}else{
								$form_data[$i][$j]  = '';
							}
						}
					} elseif($column_type_lookup[$column_name] == 'checkbox'){
					
						if(!empty($row['data_value'])){
							if(substr($column_name,-5) == "other"){ //if this is an 'other' field, display the actual value
								$form_data[$i][$j] = htmlspecialchars($row['data_value'],ENT_QUOTES);
							}else{
								$form_data[$i][$j]  = '<div class="me_center_div"><img src="images/icons/62_blue_16.png" align="absmiddle" /></div>';
							}
						}else{
							$form_data[$i][$j]  = '';
						}
					
					} elseif(in_array($column_type_lookup[$column_name],array('phone','simple_phone'))){ 
						if(!empty($row['data_value'])){
							if($column_type_lookup[$column_name] == 'phone'){
								$form_data[$i][$j] = '('.substr($row['data_value'],0,3).') '.substr($row['data_value'],3,3).'-'.substr($row['data_value'],6,4);
							}else{
								$form_data[$i][$j] = $row['data_value'];
							}
						}
					} elseif($column_type_lookup[$column_name] == 'file'){
						if(!empty($row['data_value'])){
							$raw_files = array();
							$raw_files = explode('|',$row['data_value']);
							$clean_filenames = array();
	
							foreach($raw_files as $hashed_filename){
								$file_1 	    =  substr($hashed_filename,strpos($hashed_filename,'-')+1);
								$filename_value = substr($file_1,strpos($file_1,'-'));
								$clean_filenames[] = htmlspecialchars($filename_value);
							}
	
							$clean_filenames_joined = implode(', ',$clean_filenames);
							$form_data[$i][$j]  = '<div class="me_file_div">'.$clean_filenames_joined.'</div>';
						}else{
							$form_data[$i][$j] = '';
						}
					} elseif($column_type_lookup[$column_name] == 'payment_status'){
						if($row[$column_name] == 'paid'){
							$payment_status_color = 'style="color: green;font-weight: bold"';
							$payment_status_label = strtoupper($row['data_value']);
						}else{
							$payment_status_color = '';
							$payment_status_label = ucfirst(strtolower($row['data_value']));
						}	
						$form_data[$i][$j] = '<span '.$payment_status_color.'>'.$payment_status_label.'</span>';
					}else{
						$form_data[$i][$j] = html_entity_decode($row['data_value']);//htmlspecialchars(str_replace("\r","",str_replace("\n"," ",$row['data_value'])),ENT_QUOTES);
					}
				}
				
				$j++;
				
			}
			
			$company_id[$i] = $row1['company_id'];
			$i++;
			
	    }
		
		//generate table markup for the entries
		$table_header_markup = '<thead><tr>'."\n";
		foreach($column_labels as $label_name){
			if($label_name == 'la_id'){
				$table_header_markup .= '<th class="me_action" scope="col"><input type="checkbox" value="1" name="col_select" id="col_select" /></th>'."\n";
			}elseif($label_name == 'la_row_num'){
				$table_header_markup .= '<th class="me_number" scope="col">#</th>'."\n";
			}elseif($label_name == 'approval_status'){
				$table_header_markup .= '<th scope="col" class="approval_status">Approval Status</th>'."\n";
			}elseif($label_name == 'template_document'){
				$table_header_markup .= '<th scope="col" class="template_document">Template Document</th>'."\n";
			}else{
				$table_header_markup .= '<th scope="col"><div title="'.$label_name.'">'.$label_name.'</div></th>'."\n";	
			}
			
		}

		$table_header_markup .= '</tr></thead>'."\n";

		$table_body_markup = '<tbody>'."\n";

		$toggle = false;
		
		$first_row_number = ($pageno -1) * $rows_per_page + 1;
		$last_row_number  = $first_row_number;
		
		$m = 0;

		$casecade_children = get_casecade_children($dbh, $form_id);

		if(!empty($form_data)){
			// print_r($form_data);
			// die();
			foreach($form_data as $row_data){
				$n = 1;
				if($toggle){
					$toggle = false;
					$row_style = 'class="alt"';
				}else{
					$toggle = true;
					$row_style = '';
				}
				$query = "select DISTINCT(company_id) from ".LA_TABLE_PREFIX."form_{$form_id}";
				$params = array();
				$sth = la_do_query($query,$params,$dbh);
				
				$o = 0;
				$cid = null;
				while($row = la_do_fetch_result($sth)){
					$companyId_approval = $row['company_id'];
					if($row['company_id'] == $company_id[$m]){
						$o = $n;
						$cid = $row['company_id'];
					}
					$n++;
				}
							
				$table_body_markup .= '<input type="hidden" name="companyId[]" id="companyId_'.$m.'" companyId="'.$cid.'" value="'.$o.'">';
				
				$table_body_markup .= "<tr id=\"row_{$row_data[0]}\" {$row_style}>";
				foreach ($row_data as $key => $column_data){
					if($key == 0){ //this is "id" column
						$table_body_markup .= '<td class="me_action"><input class="entry_selection" type="checkbox" id="entry_'.$column_data.'" name="entry_'.$column_data.'" value="'.$o.'" /></td>'."\n";
					}elseif ($key == 1){ //this is "row_num" column
						$table_body_markup .= '<td class="me_number">'.$column_data.'</td>'."\n";
					}elseif ($key == 2){ //this is "approval" column
						$status_company = explode(',', $column_data);
						// $column_data = 1;
						$form_approval_status = $status_company[0];
						$companyid = $status_company[1];
						$is_replied = $status_company[2];
						$not_my_turn = $status_company[3];
						$user_can_approve = $status_company[4];

						$entQuery = "SELECT company_name FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
						$entParams = array($companyid);
						$sth2 = la_do_query($entQuery,$entParams,$dbh);
						$entitiesText = '';
						while($row = la_do_fetch_result($sth2)){
							$entitiesText .= $row['company_name'] . "<br>";
						}
						if ($entitiesText == '') {
							$entitiesText = "Admin";
						}
						$table_body_markup .="<td>$entitiesText</td>";

						


						if( is_numeric($form_approval_status) ) {
							
							if ( $form_approval_status == 1) { // form is approved
								$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Approved</td>';
							} elseif ( $form_approval_status == 2) { // form is dis-approved
								$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-denied"></span> Denied</td>';
							} else { //the form approval status is not updated

								if( $user_can_approve == 1 ) { //it means only certian users are allowed to appove/deny this form

									if( $not_my_turn ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Not My Turn</td>';
									}elseif( $is_replied == 1 ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-approved"></span> Approval pending</td>';
									} elseif( $is_replied == 2 ) {
										$table_body_markup .='<td class="approval_status" data-cid="'.$companyid.'"><span class="dot dot-denied"></span> Approval pending</td>';
									}else {
										$table_body_markup .= '<td class="me_action approval_status" data-cid="'.$companyid.'"><a class="approve_action bb_button bb_green" data-action="approve">Approve</a> <a class="approve_action bb_button bb_red deny-entry"  data-action="deny">Deny</a></td>'."\n";
									}

								} else {
									$table_body_markup .='<td class="approval_status"></td>';
								}

							}
						} else {
							$table_body_markup .='<td class="approval_status"></td>';
						}

					}elseif ($key == 3){ //this is "template document" column
						$document_data_company_id = '';
						$document_data_template_name = '';
						if( !empty($column_data) ) {
							$document_data_arr = explode(',', $column_data);
							if( $document_data_arr[0] == 'queue' ) {
								$table_body_markup .="<td class=\"me_action template_document\">{$document_data_arr[1]}";
								$document_data_company_id = $document_data_arr[2];
							} else  {
								$document_data_company_id = $document_data_arr[1];
								$document_data_template_name = $document_data_arr[0];
								$table_body_markup .="<td class=\"me_action template_document\"><a target=\"_blank\" href=\"javascript:void(0);\" class=\"action-download-document-zip\" data-documentdownloadlink=\"{$la_settings_base_url}download_document_zip.php?id={$document_data_template_name}&form_id={$form_id}&entry_id={$row_data[1]}&company_id={$document_data_company_id}\" >{$document_data_template_name}</a>";	
							}

							//check if this form has any casecade child and if document is created for child casecade
							// print_r($casecade_children);
							if( !empty($casecade_children) ) {
								foreach ($casecade_children as $casecade_child) {
									$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` where `form_id` = ? and `company_user_id` = ? and status != 1 order by id DESC LIMIT 1";
									$sth_document_process = la_do_query($query_document_process,array($casecade_child, $document_data_company_id),$dbh);
									$row_document_process = la_do_fetch_result($sth_document_process);
									if( $row_document_process['id'] ) {
										//latest document has not been created yet
										if( $row_document_process['status'] == 0 ) {
											$table_body_markup .= "<br>A Document for Cascade form #{$casecade_child} is scheduled to be created.";
										} else if( $row_document_process['status'] == 2 ) {
											$table_body_markup .= "<br>A Document for Cascade form #{$casecade_child} is generating now.";
										}
									} else {
									
										$query_casecade_child = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` where `form_id` = {$casecade_child} and `company_id` = {$document_data_company_id} and `isZip` = 1 order by `docx_create_date` DESC";
							            $sth_casecade_child = la_do_query($query_casecade_child,array(),$dbh);
							            $row_casecade_child = la_do_fetch_result($sth_casecade_child);

							            if( $row_casecade_child ) {
							            	$table_body_markup .="<br><a target=\"_blank\" href=\"javascript:void(0);\" class=\"action-download-document-zip\" data-documentdownloadlink=\"{$la_settings_base_url}download_document_zip.php?id={$row_casecade_child['docxname']}&form_id={$casecade_child}&entry_id={$row_data[1]}&company_id={$document_data_company_id}\" >{$row_casecade_child['docxname']}</a>";
							            }
										
									}
								}
							}
							$table_body_markup .= "</td>";
						} else {
							$table_body_markup .="<td class=\"me_action template_document\"></td>";
						}
						
					}elseif ($key == 4){
						$checked = $column_data == "1" ? "checked" : "";
						$label_class = $column_data == "1" ? "enabled" : ""; 

						$table_body_markup .= '<td class="me_action" audit="'.$column_data.'" onclick="switch_audit_mode(this)">
							<label class="switch">
								<input type="checkbox" id="audit-mode-'.$cid.'" '.$checked.'>
								<span class="slider round"></span>
							</label>
							<label class="'.$label_class.'" for="audit-mode-'.$cid.'">Audit Mode</label>
						</td>'."\n";
					}else {
						
						//$column_data
					
						if ($column_data != strip_tags($column_data)) { 
							//Is HTML
							$column_data = strip_tags($column_data);
						}
						
						$table_body_markup .= '<td><div>'.$column_data.'</div></td>'."\n";
					}
				}
				// if (count($row_data) == $key+1) {
				// 	// Mismatched extra
				// 	$table_body_markup .='<td class="last"></td>';
				// }
				$table_body_markup .= "</tr>"."\n";
				$last_row_number++;
				$m++;
				
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

		$entries_markup = '<div id="entries_container" data-formid="'.$form_id.'">';
		$entries_markup .= $table_markup;
		$entries_markup .= '</div>';

		//hide the approval logic column if no entry has data for it
		if( $show_approval_column == false )
			$entries_markup .= '<style type="text/css">#entries_table .approval_status { display:none; }</style>';

		//hide the approval logic column if no entry has data for it
		if( $show_template_document_column == false )
			$entries_markup .= '<style type="text/css">#entries_table .template_document { display:none; }</style>';

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
	function la_get_columns_meta($dbh,$form_id)
	{
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
		$query = "select DISTINCT A.element_id, A.option_id, IF (B.element_matrix_parent_id = 0, A.option, null)as option_label
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

		$multi_status_parent_element = 0;
		//get 'multiselect' status of matrix fields
		$query = "select 
						A.element_id,
						A.element_matrix_parent_id,
						A.element_matrix_allow_multiselect as 'multiselect'
					from 
						".LA_TABLE_PREFIX."form_elements A
					where 
						A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
			$matrix_parent_id[$row['element_id']] = $row['element_matrix_parent_id'];
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
						 element_matrix_allow_multiselect,
						 element_default_value
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
			}
			elseif('matrix' == $element_type){ 
				if ($matrix_parent_id[$row['element_id']] == 0) {
					$multi_status_parent_element = $matrix_multiselect_status[$row['element_id']];
				}
				//if(empty($matrix_multiselect_status[$row['element_id']])){
				if($multi_status_parent_element == 0){
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
			}
			
			elseif($element_type == 'casecade_form' ) {
					//add checks for cascade forms
					$casecade_form_id = $row['element_default_value'];
					// echo "in if".$casecade_form_id;
					//select form name working here
					$form_name = get_form_name($dbh, $casecade_form_id);
					
					if( empty($form_name) )
						$form_name = 'Cascade Form';

					$cascade_form_fields = get_form_fields($dbh, $casecade_form_id);
					// print_r($cascade_form_fields);
					
					if( count($cascade_form_fields) > 0 ) {
						
						foreach ($cascade_form_fields as $field) {
							$table_header_1 = $field['element_title'];
							$column_name_lookup['cascade_'.$casecade_form_id.'_'.$field['element_id']] = $table_header_1;
							$column_type_lookup['cascade_'.$casecade_form_id.'_'.$field['element_id']] = $field['element_type'];
						}
					}
				}			
				else{
			 
					//these were there already, dont remove as only titles are shown for other fields
					$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
					$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];

				}
				// $cascade_form_data = display_casecade_form_fields(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $row1['company_id']));
		}
		
		$column_meta['name_lookup'] = $column_name_lookup;
		$column_meta['type_lookup'] = $column_type_lookup;
		return $column_meta;
	}

	//get an array containing id number of all filtered entries within a form
	function la_get_filtered_entries_ids($dbh,$form_id,$options=array())
	{
		
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
		$query = "select DISTINCT A.element_id, A.option_id, IF (B.element_matrix_parent_id = 0, A.option, null)as option_label
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
		
		$multi_status_parent_element = 0;
		//get 'multiselect' status of matrix fields
		$query = "select 
						A.element_id,
						A.element_matrix_parent_id,
						A.element_matrix_allow_multiselect as 'multiselect'
					from 
						".LA_TABLE_PREFIX."form_elements A
					where 
						A.form_id=? and A.element_status=1 and A.element_type='matrix'";
		$params = array($form_id);
		$sth = la_do_query($query,$params,$dbh);
		
		while($row = la_do_fetch_result($sth)){
			$matrix_multiselect_status[$row['element_id']] = $row['multiselect'];
			$matrix_parent_id[$row['element_id']] = $row['element_matrix_parent_id'];
		}
		
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
				
			}
			elseif ('simple_name' == $element_type){ //simple name has 2 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Last';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				
			}
			elseif ('simple_name_wmiddle' == $element_type){ //simple name with middle has 3 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - Middle';
				$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
				
			}
			elseif ('name' == $element_type){ //name has 4 fields
				$column_name_lookup['element_'.$row['element_id'].'_1'] = $row['element_title'].' - Title';
				$column_name_lookup['element_'.$row['element_id'].'_2'] = $row['element_title'].' - First';
				$column_name_lookup['element_'.$row['element_id'].'_3'] = $row['element_title'].' - Last';
				$column_name_lookup['element_'.$row['element_id'].'_4'] = $row['element_title'].' - Suffix';
				
				$column_type_lookup['element_'.$row['element_id'].'_1'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_2'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_3'] = $row['element_type'];
				$column_type_lookup['element_'.$row['element_id'].'_4'] = $row['element_type'];
				
			}
			elseif ('name_wmiddle' == $element_type){ //name with middle has 5 fields
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
				
			}
			elseif('money' == $element_type){//money format
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				if(!empty($element_constraint)){
					$column_type_lookup['element_'.$row['element_id']] = 'money_'.$element_constraint; //euro, pound, yen,etc
				}else{
					$column_type_lookup['element_'.$row['element_id']] = 'money_dollar'; //default is dollar
				}
			}
			elseif('checkbox' == $element_type){ //checkboxes, get childs elements
							
				$this_checkbox_options = $element_option_lookup[$row['element_id']];
				
				foreach ($this_checkbox_options as $option_id=>$option){
					$column_name_lookup['element_'.$row['element_id'].'_'.$option_id] = htmlspecialchars($option,ENT_QUOTES);
					$column_type_lookup['element_'.$row['element_id'].'_'.$option_id] = $row['element_type'];
				}
			}
			elseif ('time' == $element_type){
				
				if(!empty($row['element_time_showsecond']) && !empty($row['element_time_24hour'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time_24hour';
				}elseif(!empty($row['element_time_showsecond'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time';
				}elseif(!empty($row['element_time_24hour'])){
					$column_type_lookup['element_'.$row['element_id']] = 'time_24hour_noseconds';
				}else{
					$column_type_lookup['element_'.$row['element_id']] = 'time_noseconds';
				}
				
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
			}
			elseif('matrix' == $element_type){
				if ($matrix_parent_id[$row['element_id']] == 0) {
					$multi_status_parent_element = $matrix_multiselect_status[$row['element_id']];
				}
				//if(empty($matrix_multiselect_status[$row['element_id']])){
				if($multi_status_parent_element == 0){
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
			}
			else{ //for other elements with only 1 field
				$column_name_lookup['element_'.$row['element_id']] = $row['element_title'];
				$column_type_lookup['element_'.$row['element_id']] = $row['element_type'];
			}

		}

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
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} = 0";
						}
					}elseif($filter_condition == 'not_contain'){
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

				}elseif(in_array($filter_element_type, array('date','europe_date'))){

					$date_exploded = array();
					$date_exploded = explode('/', $filter_keyword); //the filter_keyword has format mm/dd/yyyy

					$filter_keyword = $date_exploded[2].'-'.$date_exploded[0].'-'.$date_exploded[1];

					if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_after'){
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
					}elseif($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}elseif($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}elseif($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}elseif($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}elseif($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";

						if(!empty($filter_keyword)){
							$null_clause = "OR {$element_name} IS NULL";
						}
					}elseif($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
					}elseif($filter_condition == 'is_one'){
						$where_operand = '=';
						$where_keyword = "'1'";
					}elseif($filter_condition == 'is_zero'){
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

		}

		$query = "select COUNT(DISTINCT(company_id)) as cnt from ".LA_TABLE_PREFIX."form_{$form_id}";
		$params = array();
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$filtered_entry_id_array = array();
		
		for($i =1;$i<=$row['cnt'];$i++){
			$filtered_entry_id_array[] = $i;
		}
		
		return $filtered_entry_id_array;
	}
	
	
		function get_form_name($dbh, $form_id) {

		$form_name = '';

		$query 	= "select 
		 			form_name
					from 
				     	 ".LA_TABLE_PREFIX."forms 
				    where 
				    	 form_id = ?";
		$params = array($form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row)){
			if( !empty($row['form_name']) )
				$form_name = $row['form_name'];
		}
		return $form_name;
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

	//get all cascade children of parent form
	function get_casecade_children($dbh,$parent_form_id) {
		//get form elements	
		$query  = "select 
						 element_default_value
					 from 
					 	 `".LA_TABLE_PREFIX."form_elements` 
					where 
						 form_id=? AND element_type = 'casecade_form'";
		$params = array($parent_form_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$all_child_casecade_forms = [];
		while($row = la_do_fetch_result($sth)){
			$all_child_casecade_forms[] = $row['element_default_value'];
		}
		return $all_child_casecade_forms;
	}
	
	function getEntryData($dbh, $form_ids, $company_id, $entry_id) {
		$res = array();
		foreach ($form_ids as $tmp_form_id) {
			$query_entry_data = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$tmp_form_id}` WHERE `company_id` = ? AND `entry_id` = ?";
			$sth_entry_data = la_do_query($query_entry_data, array($company_id, $entry_id), $dbh);
			while($row_entry_data = la_do_fetch_result($sth_entry_data)) {
				array_push($res, array(
					"form_id" => $tmp_form_id,
					"field_name" => $row_entry_data['field_name'],
					"field_code" => $row_entry_data['field_code'],
					"data_value" => $row_entry_data['data_value'],
					"field_score" => $row_entry_data['field_score'],
					"form_resume_enable" => $row_entry_data['form_resume_enable'],
					"submitted_from" => $row_entry_data['submitted_from'],
					"other_info" => $row_entry_data['other_info'],
					"element_machine_code" => $row_entry_data['element_machine_code']
				));
			}
		}
		return $res;
	}

	function getStatusIndicators($dbh, $form_ids, $company_id, $entry_id) {
		$res = array();
		$inQueryFormIDs = implode(',', array_fill(0, count($form_ids), '?'));
		$query_status = "SELECT * FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE form_id IN ({$inQueryFormIDs}) AND `company_id` = ? AND `entry_id` = ?";
		$sth_status = la_do_query($query_status, array_merge($form_ids, array($company_id, $entry_id)), $dbh);
		while ($row_status = la_do_fetch_result($sth_status)) {
			$row = $row_status;
			unset($row['id']);
			unset($row['company_id']);
			unset($row['entry_id']);
			array_push($res, $row);
		}
		return $res;
	}

	function getSyncedFiles($dbh, $form_ids, $company_id) {
		$res = array();
		foreach ($form_ids as $tmp_form_id) {
			$query_synced_files = "SELECT f.element_machine_code, f.files_data FROM `".LA_TABLE_PREFIX."form_elements` AS e JOIN `".LA_TABLE_PREFIX."file_upload_synced` AS f ON (e.element_machine_code = f.element_machine_code) WHERE e.form_id = ? AND e.element_type = 'file' AND e.element_file_upload_synced = 1 AND f.company_id = ?";
			$sth_synced_files = la_do_query($query_synced_files, array($tmp_form_id, $company_id), $dbh);
			while($row_synced_files = la_do_fetch_result($sth_synced_files)) {
				array_push($res, $row_synced_files);
			}
		}
		array_unique($res);
		return $res;
	}

	function getNormalFiles($dbh, $form_ids, $company_id, $entry_id) {
		$res = array();
		foreach ($form_ids as $tmp_form_id) {
			$query_files = "SELECT f.data_value FROM `".LA_TABLE_PREFIX."form_elements` AS e JOIN `".LA_TABLE_PREFIX."form_{$tmp_form_id}` AS f ON (CONCAT('element_', e.element_id)= f.field_name) WHERE e.form_id = ? AND e.element_type = 'file' AND e.element_file_upload_synced = 0 AND f.company_id = ? AND f.entry_id = ?";
			$sth_files = la_do_query($query_files, array($tmp_form_id, $company_id, $entry_id), $dbh);
			while ($row_files = la_do_fetch_result($sth_files)) {
				if($row_files['data_value'] != "") {
					$files = explode("|", $row_files['data_value']);
					foreach ($files as $file) {
						array_push($res, array("synced" => 0, "file_name" => $file, "form_id" => $tmp_form_id));
					}
				}
			}
		}
		return $res;
	}