<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://continuumgrc.com/
 
 More info at: http://continuumgrc.com/
 ********************************************************************************/
	require('includes/init.php');
	header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
	
	  
	require_once("../policymachine/classes/CreateDocx.inc");
	require_once("../policymachine/classes/CreateDocxFromTemplate.inc");
	
	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/common-validator.php');
	require('includes/helper-functions.php');
	require('includes/view-functions.php');
	require('includes/docxhelper-functions.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');	
	require('includes/users-functions.php');
	require('includes/check-session.php');
	
	$form_id  = (int) trim($_GET['form_id']);
	$entry_id = (int) trim($_GET['entry_id']);
	$nav = trim($_GET['nav']);
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$_SESSION['admin'] = $_SESSION['la_user_id'];
	
	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_entries permission
		if(empty($user_perms['edit_entries'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this entry.";

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: restricted.php");
			exit;
		}
	}
	
	//get form name
	$query 	= "select 
					 form_name
			     from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	$row['form_name'] = la_trim_max_length($row['form_name'],65);

	if(!empty($row)){
		$form_name = htmlspecialchars($row['form_name']);
	}else{
		die("Error. Unknown form ID.");
	}

	//get entry status information 

	$entry_status = $row['status'];

	$is_incomplete_entry = false;
	
	if($entry_status == 2){
		$is_incomplete_entry = true;
	}
	//if there is "nav" parameter, we need to determine the correct entry id and override the existing entry_id
	if(!empty($nav)){
		$entries_options = array();
		$entries_options['is_incomplete_entry'] = $is_incomplete_entry;

		$all_entry_id_array = la_get_filtered_entries_ids($dbh,$form_id,$entries_options);
		$entry_key = array_keys($all_entry_id_array,$entry_id);
		$entry_key = $entry_key[0];

		if($nav == 'prev'){
			$entry_key--;
		}else{
			$entry_key++;
		}

		$entry_id = $all_entry_id_array[$entry_key];

		//if there is no entry_id, fetch the first/last member of the array
		if(empty($entry_id)){
			if($nav == 'prev'){
				$entry_id = array_pop($all_entry_id_array);
			}else{
				$entry_id = $all_entry_id_array[0];
			}
		}
	}

  

	if(la_is_form_submitted()){ //if form submitted
		
		$input_array   = la_sanitize($_POST);
		//echo '<pre>';print_r($input_array);echo '</pre>';die;
		 
		
		$submit_result = la_process_form($dbh,$input_array);
 		
		
		$row_forms 	   = [];
	 
		if($submit_result['status'] === true){
			$_SESSION['LA_SUCCESS'] = 'Entry #'.$input_array['edit_id'].' has been updated.';
			$ssl_suffix = la_get_ssl_suffix();	
			
			$company_id = $_SESSION['company_user_id'];
			
			$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` LIKE 'element_%' AND company_id=".$company_id."";
			$param_forms = array();
			
		 
			
			$result_forms = la_do_query($query_forms,$param_forms,$dbh);
			
			while($row = la_do_fetch_result($result_forms)){
				$row_forms[$row['field_name']] = $row['data_value'];
			}
			
			
/* CASESCADE FORM UPDATE */

 
 //GET ALL CASCADE FORMS INSIDE FORM
 $param_cascade_forms = array();
 $cascade_forms = array();
 
$query_cascade_forms = "SELECT  
						element_default_value 
 
					FROM 
						".LA_TABLE_PREFIX."form_elements 
				   WHERE 
				   		form_id = {$form_id} and element_status='1'  and element_type <> 'page_break'
				   		AND NOT element_default_value = ''
				ORDER BY 
						element_position";
	
 
						
		$result_cascade_forms = la_do_query($query_cascade_forms,$param_cascade_forms,$dbh);


			
			while($row_cascade = la_do_fetch_result($result_cascade_forms)){
				
				array_push($cascade_forms, $row_cascade['element_default_value']);
				
 			}
			
			//var_dump($cascade_forms);
		
 			foreach($cascade_forms as $cascade_form) {
				
				$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$cascade_form}` WHERE `field_name` LIKE 'element_%' AND company_id=".$company_id."";
				$param_forms = array();
				//echo $query_forms;
				
				$result_forms = la_do_query($query_forms,$param_forms,$dbh);
				
				while($row = la_do_fetch_result($result_forms)){
					$row_forms[$row['field_name']] = $row['data_value'];
				}
			
							
				
			}
			  
			
			
			$result_forms = la_do_query($query_forms,$param_forms,$dbh);
			
			while($row = la_do_fetch_result($result_forms)){
				$row_forms[$row['field_name']] = $row['data_value'];
			}
			
/* CASESCADE FORM UPDATE */
						
	 
			
			 
			$element_array = array();
			$replace_data_array = array();
			$address_counter = 0;
			
			$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_type` != 'section' AND `element_type` != 'page_break'";
			
			$param_form_element = array();
			$param_form_element[':form_id'] = $form_id;
			
	 
			
			$result_form_element = la_do_query($query_form_element,$param_form_element,$dbh);
			
			while($row_form_template = la_do_fetch_result($result_form_element)){
				$element_array[$row_form_template['element_id']] 						 			 = array();
				$element_array[$row_form_template['element_id']]['element_type'] 		 			 = $row_form_template['element_type'];
				$element_array[$row_form_template['element_id']]['element_machine_code'] 			 = $row_form_template['element_machine_code'];
				$element_array[$row_form_template['element_id']]['element_matrix_allow_multiselect'] = $row_form_template['element_matrix_allow_multiselect'];
				$element_array[$row_form_template['element_id']]['element_matrix_parent_id'] 		 = $row_form_template['element_matrix_parent_id'];
				$element_array[$row_form_template['element_id']]['element_id'] 						 = $row_form_template['element_id'];
				$element_array[$row_form_template['element_id']]['element_default_value'] 			 = $row_form_template['element_default_value'];
			}
						
						 
						
			foreach($element_array as $element_id => $element){
				$replace_string = array();
				if($element['element_type'] == 'address'){						
					// update form address field
					updateScoreField($dbh, $form_id, $company_id, array('element_'.$element_id.'_4'));
				}
				elseif($element['element_type'] == 'radio' || $element['element_type'] == 'checkbox' || $element['element_type'] == 'matrix' || $element['element_type'] == 'select'){
					
					if($element['element_type'] == 'matrix'){
						$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
					}
					
					if($element['element_type'] == 'checkbox'){
						
						$query_element_option = "SELECT `option_id`, `option`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id";
						
						$param_element_option = array();
						$param_element_option[':form_id'] = $form_id;
						$param_element_option[':element_id'] = $element_id;
						$i= 0;
						$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
						
						while($row_element_option = la_do_fetch_result($result_element_option)){
							$query20  = "SELECT data_value, field_score FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."' AND company_id='".$company_id."'";							
							$result20 = la_do_query($query20,$params_table_data,$dbh);
							$row20    = la_do_fetch_result($result20);
							
							if($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']] >= 1){

								if($row20['field_score'] == ''){
									
									$filed_score = $row_element_option['option_value'];
									
								} else {
									$filed_score = $row20['field_score'].','.$row_element_option['option_value'];									
								}
								
								$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."' AND company_id='".$company_id."'";
								la_do_query($query17,$params_table_data,$dbh);
											
							} else {
								
								if($row20['field_score'] == ''){
									$filed_score = '0';
								} else {
									$filed_score = $row20['field_score'].',0';									
								}
								
								$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."' AND company_id='".$company_id."'";
								la_do_query($query17,$params_table_data,$dbh);
							}
						}
					}
					else{
						
						$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id";
						$param_element_option = array();
						$param_element_option[':form_id'] = $form_id;
						$param_element_option[':element_id'] = $element_id;
						$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
						$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
						$row_element_option = la_do_fetch_result($result_element_option);

						$query20 = "SELECT data_value, field_score FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name='element_".$element_id."' AND company_id='".$company_id."'";
							
						$result20 = la_do_query($query20,$params_table_data,$dbh);
						$row20 = la_do_fetch_result($result20);
						
						if($row20['field_score'] == ''){
										
							$filed_score = $row_element_option['option_value'];
									
						} else {
							
							$filed_score = $row20['field_score'].','.$row_element_option['option_value'];
									
						}
						
						$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id."' AND company_id='".$company_id."'";
						la_do_query($query17,$params_table_data,$dbh);	
					}
					
				}
			}
				
			// update form address field and date field
			updateScoreField($dbh, $form_id, $company_id, array('date_created'));
			
			getElementWithValueArray(array('dbh' => $dbh, 'form_id' => $form_id, 'la_user_id' => $_SESSION['company_user_id'], 'company_user_id' => $_SESSION['company_user_id']));
			
			if(isset($_SESSION['company_user_id'])){
				unset($_SESSION['company_user_id']);	
			}
			
			header("Location: view_entry.php?form_id={$input_array['form_id']}&entry_id={$input_array['edit_id']}");
			exit;
			
		}else if($submit_result['status'] === false){ //there are errors, display the form again with the errors
			$old_values 	= $submit_result['old_values'];
			$custom_error 	= @$submit_result['custom_error'];
			$error_elements = $submit_result['error_elements'];
				
			$form_params = array();
			$form_params['populated_values'] = $old_values;
			$form_params['error_elements']   = $error_elements;
			$form_params['custom_error'] 	 = $custom_error;
			$form_params['edit_id']			 = $input_array['edit_id'];
			$form_params['integration_method'] = 'php';
			$form_params['page_number'] = 0; //display all pages (if any) as a single page
			
			$form_markup = la_display_form($dbh,$input_array['form_id'],$form_params);
		}

	}
	else{ 
		//otherwise, display the form with the values
		//set session value to override password protected form
		$_SESSION['user_authenticated'] = $form_id;
		
		//set session value to bypass unique checking
		$_SESSION['edit_entry']['form_id']  = $form_id;
		$_SESSION['edit_entry']['entry_id'] = $entry_id;

		$form_values = la_get_entry_values($dbh,$form_id,$entry_id);
		//echo '<pre>';print_r($form_values);echo '</pre>';die;
		$form_params = array();
		$form_params['populated_values']   = $form_values;
		$form_params['edit_id']			   = $entry_id;
		$form_params['integration_method'] = 'php';
		$form_params['page_number'] 	   = 0; //display all pages (if any) as a single page
		$form_params['form_fields_with_div_only'] = true;
		
		
		$form_markup = la_display_form($dbh,$form_id,$form_params);
		
		
	}

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/entry_print.css" media="print">
EOT;

	$current_nav_tab = 'manage_forms';
	$load_custom_js = false;
	require('includes/header.php'); 	
?>
<style>
#main_body .buttons{
	margin-top:0 !important;
}

#dialog-confirm-entry-delete{
	height: 135px !important;	
}
</style>
<div id="content" class="full">
  <div class="post edit_entry">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <?php if($is_incomplete_entry){ ?>
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_entries.php?id={$form_id}'>Entries</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_incomplete_entries.php?id={$form_id}'>Incomplete</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> #<?php echo $entry_id; ?></h2>
          <!--<p>Editing incomplete entry #<?php echo $entry_id; ?></p>-->
          <?php }else{ ?>
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_entries.php?id={$form_id}'>Entries</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> #<?php echo $entry_id; ?></h2>
          <!--<p>Editing entry #<?php echo $entry_id; ?></p>-->
          <?php } ?>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <div id="ve_details" data-formid="<?php echo $form_id; ?>" data-entryid="<?php echo $entry_id; ?>" data-incomplete="<?php if($is_incomplete_entry){ echo '1';}else{ echo '0';} ?>"> <?php echo $form_markup; ?> </div>
      <div id="ve_actions">
        <div id="ve_entry_navigation" style="margin: 5px 0 5px 28px; padding-bottom: 0; text-align: center;"> <a href="<?php echo "edit_entry.php?form_id={$form_id}&entry_id={$entry_id}&nav=prev"; ?>" title="Previous Entry" style="margin-left: 1px"><img src="images/navigation/005499/24x24/Back.png"></a> <a href="<?php echo "edit_entry.php?form_id={$form_id}&entry_id={$entry_id}&nav=next"; ?>" title="Next Entry" style="margin-left: 5px"><img src="images/navigation/005499/24x24/Forward.png"></a> </div>
        <div id="ve_entry_actions">
          <ul style="width: 100%;">
            <li style="text-align: center; width: 100%;">
              <div style="width:50%; float:left; text-align:center;">
                <img style="float: left; margin: 10px 0px 0px 30px;" title="Edit" alt="Edit" src="images/navigation/005499/16x16/View.png">
              </div>
              <div style="border-bottom: 1px dashed rgb(142, 172, 207); width:50%; float:left; text-align: left;">
                <a id="ve_action_view" title="View Entry" href="<?php echo "view_entry.php?form_id={$form_id}&entry_id={$entry_id}"; ?>">View</a>
              </div>
              <div style="clear:both;"></div>
            </li>
            <li style="text-align: center; width: 100%;">
              <div style="width:50%; float:left; text-align:center;">
                <img style="float: left; margin: 10px 0px 0px 30px;" title="Edit" alt="Edit" src="images/navigation/message_already_read_16.png">
              </div>
              <div style="border-bottom: 1px dashed rgb(142, 172, 207); width:50%; float:left; text-align: left;">
                <a id="ve_action_email" title="Email Entry" href="javascript:void(0)">Email</a>
              </div>
              <div style="clear:both;"></div>
            </li>
            <li style="text-align: center; width: 100%;">
              <div style="width:50%; float:left; text-align:center;">
                <img style="float: left; margin: 10px 0px 0px 30px;" title="Edit" alt="Edit" src="images/navigation/005499/16x16/Delete.png ">
              </div>
              <div style="border-bottom: 1px dashed rgb(142, 172, 207); width:50%; float:left; text-align: left;">
                <a id="ve_action_delete" title="Delete Entry" href="javascript:void(0)">Delete</a>
              </div>
              <div style="clear:both;"></div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->


<div id="dialog-confirm-entry-delete" title="Are you sure you want to delete this entry?" class="buttons" style="display: none">
	<img src="images/navigation/ED1C2A/50x50/Warning.png"> 
	<p id="dialog-confirm-entry-delete-msg">
		 This action cannot be undone.<br>Data and files associated with this entry will be deleted.
	</p>
</div>
<div id="dialog-email-entry" title="Email entry #<?php echo $entry_id; ?> to:" class="buttons" style="display: none">
  <form id="dialog-email-entry-form" class="dialog-form" style="padding-left: 10px;padding-bottom: 10px">
    <ul>
      <li>
        <div>
          <input type="text" value="" class="text" name="dialog-email-entry-input" id="dialog-email-entry-input" />
        </div>
        <div class="infomessage" style="padding-top: 5px;padding-bottom: 0px">Use commas to separate email addresses.</div>
      </li>
    </ul>
  </form>
</div>
<div id="dialog-entry-sent" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-entry-sent-msg"> The entry has been sent. </p>
</div>
<?php
	$footer_data =<<<EOT
<!--[if lt IE 9]><script src="js/signaturepad/flashcanvas.js"></script><![endif]-->
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/view_entry.js"></script>
<script type="text/javascript" src="js/uploadifive/jquery.uploadifive.js"></script>
<script src="js/uploadify/swfobject.js" type="text/javascript"></script>
<script src="js/uploadify/jquery.uploadify.js" type="text/javascript"></script>
<script src="js/jquery.jqplugin.min.js" type="text/javascript"></script>

<script>
$(document).ready(function(e) {
	var total_score = 0;
	$('.score-span').each(function() {
        if($(this).attr('data-score-value') != "")
        	total_score += parseInt($(this).attr('data-score-value'));
    });
    $('#total-score').html(total_score);
	
	// Export to PDF
	$('#ve_action_pdf').click(function(){
		var _form_details = $('div#ve_details').html();
		    //_form_details = _form_details.find('tr#entry_info_header_tr').remove();
			
		$.ajax({
			type: "POST",
			async: true,
			url: "generate_entries_pdf.php",
			data: {
				form_id: '<?php echo $form_id; ?>',
				form_name: '<?php echo $form_full_name; ?>',
				form_details: _form_details
			},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message	
				console.log(xhr);	  
				console.log(text_status);	  
				console.log(e);	  
			},
			success: function(response){
				response = JSON.parse(response);
				window.location.href = 'generate_entries_pdf.php?download_pdf=true&download_pdf_name='+response.pdffile_name;
			}
		});
	});
});
</script>
EOT;

	$disable_jquery_loading = true;	
	require('includes/footer.php'); 
?>
<style>
	body {
		
		color: black !important;
	}