<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
permission from http://lazarusalliance.com/

More info at: http://lazarusalliance.com/
 ********************************************************************************/
require('includes/init.php');

require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');

require('includes/entry-functions.php');
require('includes/users-functions.php');
require('includes/filter-functions.php');


if( !empty( $_GET['form_id'] ) && !empty( $_GET['parent_form_id'] ) && !empty( $_GET['entry_id'] ) && !empty( $_GET['company_id'] )) {
    $form_id = $_GET['form_id'];
    $parent_form_id = $_GET['parent_form_id'];
    $entry_id = $_GET['entry_id'];
    $company_id = $_GET['company_id'];

    $dbh = la_connect_db();
    $la_settings = la_get_settings($dbh);

    //get casecade_element_position and la_page_number of the sub form from the parent form
    $query = "SELECT `element_position`, `element_page_number` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_type` = 'casecade_form' AND `element_default_value` = ?";
    $sth = la_do_query($query, array($parent_form_id, $form_id), $dbh);
    $row = la_do_fetch_result($sth);
    $tmp_casecade_element_position = $row["element_position"];
    $tmp_casecade_element_page_number = $row["element_page_number"];

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

        if( isset($tmp_casecade_element_page_number) ) {
            $edit_entry_url.= "&la_page=".$tmp_casecade_element_page_number;
        }

        if ( isset($data['element_page_number']) ) {
            $edit_entry_url.= "&casecade_form_page_number=".$data['element_page_number'];
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

    print($row_markup);
}

//$response_data = [];
//$response_data->status    	= $status;
//$response_data->message 	= $row_markup;

// $response_json = json_encode($response_data);

?>