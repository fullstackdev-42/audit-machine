<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/

	require('includes/init.php');
	header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
	
	require_once("../policymachine/classes/CreateDocx.php");
	require_once("../policymachine/classes/CreateDocxFromTemplate.php");
	require_once("../policymachine/PHPExcel/Classes/PHPExcel/IOFactory.php");
	require_once("../policymachine/PHPExcel/Classes/PHPExcel.php");
	
	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/common-validator.php');
	require('includes/view-functions.php');
	require('includes/docxhelper-functions.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/helper-functions.php');
	require('includes/theme-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('lib/recaptchalib.php');
	require('lib/php-captcha/php-captcha.inc.php');
	require('lib/text-captcha.php');
	require('hooks/custom_hooks.php');

	function setBackInSes($params=array()){
		$dbh = $params['dbh'];
		$form_id = $params['form_id'];
		$la_page = $params['la_page'];
		$url_strings = $params['url_strings'];

		$query = "select `element_type` from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id and `element_type` = :element_type";
		$result = la_do_query($query, array(':form_id' => $form_id, ':element_type' => 'casecade_form'), $dbh);
		$num_rows = $result->fetchColumn();

		if($num_rows){
			if(isset($_SESSION['casecade_back_session'])){
				$_SESSION['casecade_back_session'][$form_id][$la_page] = $url_strings;
			}else{
				$_SESSION['casecade_back_session'] = array();
				$_SESSION['casecade_back_session'][$form_id][$la_page] = $url_strings;
			}
		}
	}


	$form_id  = (int) trim($_GET['form_id']);
	$entry_id = (int) trim($_GET['entry_id']);
	$nav = trim($_GET['nav']);

	$dbh 		= la_connect_db();
	$la_settings = la_get_settings($dbh);
	$ssl_suffix = la_get_ssl_suffix();

	$_SESSION['admin'] = $_SESSION['la_user_id'];

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
	
	/*get company id, will be used to populate fields later*/
	// if( isset($entry_id) && !empty($entry_id) ) {
	// 	$start = $entry_id - 1;
	// 	$query1  =  "SELECT DISTINCT(company_id) FROM `".LA_TABLE_PREFIX."form_{$form_id}` LIMIT ".$start.",1";
	// 	$params1 = array();
		
	// 	$sth1 = la_do_query($query1,$params1,$dbh);
	// 	$row1 = la_do_fetch_result($sth1);
	// 	$company_id = $row1['company_id'];
	// 	$_SESSION['company_user_id'] = $company_id;
	// }
	//function la_process_form itself selects $company_id if entry_id given in url
	/*get company id, will be used to populate fields later*/



	$tmp_form_data = NULL;
	$submit_result = array();

	// generate column if not exists
	chkColumnExistence($dbh, $form_id);

	if(isset($_GET['mredirect']) && $_GET['mredirect'] == 'true'){
		$mpage = la_sanitize($_GET['la_page']);

		for($i=0; $i<$mpage; $i++)
			$_SESSION['la_pages_history'][$form_id][] = $i + 1;

		$_SESSION['la_form_access'][$form_id][$mpage] = true;
	}

	if(isset($_GET['la_page'])){
		setBackInSes(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'la_page' => $_GET['la_page'],
			'url_strings' => $_SERVER['QUERY_STRING'],
		));
	}

	if(la_is_form_submitted()){ //if form submitted
		$input_array   = la_sanitize($_POST);
		$tmp_form_data = getFormData(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'column' => 'form_page_total',
		));
		echo '<pre>';print_r($tmp_form_data);echo '</pre>';
		echo '<pre>';print_r($input_array);echo '</pre>';
		$submit_result = la_process_form($dbh,$input_array);
      	// echo '$submit_result:- <br> <pre>';print_r($submit_result);echo '</pre>';die;

	  	if	(!empty($input_array['submit_secondary'])) {


		 			 //SR Added - Back button javascript 6/30/18

					?>
					<script>	    window.history.go(-2);</script>
					<?php


						exit;

		  		//die();

	  	}



		if(!isset($input_array['password'])){ //if normal form submitted
			if($submit_result['status'] === true){

				if(!empty($submit_result['form_resume_url'])){ //the user saving a form, display success page with the resume URL
					$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];
					header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&resume_form=1&entry_id={$entry_id}");
					exit;
				}
				else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
					$target_page_id = $submit_result['target_page_id'];
					if(is_numeric($target_page_id)){
						header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&la_page={$target_page_id}&entry_id={$entry_id}");
						exit;
					}
					else if($target_page_id == 'payment'){
						//redirect to payment page, based on selected merchant
						$form_properties = la_get_form_properties($dbh,$input_array['form_id'],array('payment_merchant_type'));
						if(in_array($form_properties['payment_merchant_type'], array('stripe','authorizenet','paypal_rest','braintree'))){
							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];

							// edited on 05-11-2014
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							//header("Location: payment.php?id={$input_array['form_id']}");
							exit;
						}else if($form_properties['payment_merchant_type'] == 'paypal_standard'){
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							exit;
						}
					}
					else if($target_page_id == 'review'){
						if(!empty($submit_result['origin_page_number'])){
							$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
						}
						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm.php?form_id={$input_array['form_id']}{$page_num_params}&entry_id={$entry_id}");
						exit;
					}
					else if($target_page_id == 'success'){
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							exit;
						}else{
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							exit;
						}
					}
				}
				else{
					if(isset($input_array['submit_secondary']) && isset($_SESSION['casecade_back_session'])){
						if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']])){


							header("Location: /auditprotocol/edit_entry2.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']]);
						}else{
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&entry_id={$entry_id}");
						}
						exit();
					}
					if(isset($input_array['go_to_ask_page']) && !empty($input_array['go_to_ask_page'])){
                        $_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']])){
							header("Location: /auditprotocol/edit_entry2.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']]);
						}else{
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&la_page={$input_array['go_to_ask_page']}&entry_id={$entry_id}");
						}
						exit;
                    }
					if(isset($input_array['casecade_form_page_number']) && $input_array['casecade_form_page_number'] && $input_array['page_number'] == $tmp_form_data[0]['form_page_total']){
						if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
							$_SESSION['review_id'] = $submit_result['review_id'];
							header("Location: confirm.php?form_id={$input_array['form_id']}&la_page_from={$input_array['page_number']}&entry_id={$entry_id}");
							exit;
						}else{
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							exit;
						}
					}
					if(isset($_REQUEST['casecade_form_page_number']) && $_REQUEST['casecade_form_page_number']){
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

						if($_REQUEST['casecade_form_page_number'] != 'NO_ELEMENTS') {
							$_REQUEST['casecade_form_page_number']++;
						}

						header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}&entry_id={$entry_id}");
						exit;
					}
					if(!empty($submit_result['next_page_number'])){//redirect to the next page number
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}&entry_id={$entry_id}");
						exit;
					}
					else if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
						//echo 'Test'; exit;
						if(!empty($submit_result['origin_page_number'])){
							$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
						}

						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm.php?form_id={$input_array['form_id']}{$page_num_params}&entry_id={$entry_id}");
						exit;
					}
					else{ //otherwise display success message or redirect to the custom redirect URL or payment page
						if(la_is_payment_has_value($dbh,$input_array['form_id'],$submit_result['entry_id'])){
							//redirect to credit card payment page, if the merchant is being enabled and the amount is not zero
							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];
							// edited on 05-11-2014
							header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
							//header("Location: payment.php?id={$input_array['form_id']}");
							exit;
						}
						else{
							//redirect to success page
							if(empty($submit_result['form_redirect'])){
								header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
								exit;
							}else{
								header("Location: /auditprotocol/edit_entry2.php?form_id={$input_array['form_id']}&done=1&entry_id={$entry_id}");
								exit;
							}
						}
					}
				}

			}
			else if($submit_result['status'] === false){ //there are errors, display the form again with the errors
				$old_values 	= $submit_result['old_values'];
				$custom_error 	= @$submit_result['custom_error'];
				$error_elements = $submit_result['error_elements'];

				$form_params = array();
				$form_params['page_number'] = $input_array['page_number'];
				$form_params['populated_values'] = $old_values;
				$form_params['error_elements'] = $error_elements;
				$form_params['custom_error'] = $custom_error;

				$markup = la_display_form($dbh,$input_array['form_id'],$form_params);
			}
		}else{ //if password form submitted

			if($submit_result['status'] === true){ //on success, display the form
				$markup = la_display_form($dbh,$input_array['form_id'],true);
			}else{
				$custom_error = $submit_result['custom_error']; //error, display the pasword form again

				$form_params = array();
				$form_params['custom_error'] = $custom_error;
 				$markup = la_display_form($dbh,$input_array['form_id'],$form_params,true);
			}
		}
	}
	else{
		$page_number	= (int) la_sanitize($_GET['la_page']);
		// $page_number 	= la_verify_page_access($form_id,$page_number);
		$resume_key		= la_sanitize($_GET['la_resume']);
		if( empty($page_number) )
			$page_number = 1;

		if(!empty($resume_key)){
			$_SESSION['la_form_resume_key'][$form_id] = $resume_key;
		}

		if(!empty($_GET['done'])){

            // add user activity to log: activity - 5 (SUBMITTED)
			addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 5, "", time());
			incrementFormCounter($dbh, array('form_id' => $form_id, 'company_id' => $_SESSION['la_user_id']));

			/*********************************************************************************/

			if(!isset($_SESSION['company_user_id'])){
				echo la_display_success($dbh,$form_id,array(),0);
				return true;
			}

			unset($_SESSION['company_user_id']);
			unset($_SESSION['casecade_back_session']);

			if(isset($_SESSION['tmp_company_user_id']))
				unset($_SESSION['tmp_company_user_id']);

			$query_form_data_sql = "SELECT `form_id`, `form_redirect`, `form_redirect_enable` FROM `ap_forms` WHERE `form_id` = :form_id";
			$query_form_data_result = la_do_query($query_form_data_sql,array(':form_id' => $form_id),$dbh);
			$query_form_data_row = la_do_fetch_result($query_form_data_result);

			$markup = la_display_success($dbh,$form_id,array(),0);
		}else{
			/**********************************************/
			/*   Fetching Company data from clent table   */
			/**********************************************/
			// echo "in end";
			$_SESSION['tmp_company_user_id'] = time();

			$showSubmit = true;
			/**********************************************************************/
			{
				$form_params = array();
				$form_params['page_number'] = $page_number;
				$markup = la_display_form($dbh,$form_id,$form_params,$showSubmit);
			}
			/***************************************************************/
		}
	}

	if(isset($_SESSION['la_user_id'])){
		//header("Content-Type: text/html; charset=UTF-8");
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
      <div id="ve_details" data-formid="<?php echo $form_id; ?>" data-entryid="<?php echo $entry_id; ?>" data-incomplete="<?php if($is_incomplete_entry){ echo '1';}else{ echo '0';} ?>"> <?php echo $markup; ?> </div>
      <div id="ve_actions">
        <div id="ve_entry_navigation" style="margin: 5px 0 5px 28px; padding-bottom: 0; text-align: center;"> <a href="<?php echo "edit_entry.php?form_id={$form_id}&entry_id={$entry_id}&nav=prev"; ?>" title="Previous Entry" style="margin-left: 1px"><img src="images/navigation/005499/24x24/Back.png"></a> <a href="<?php echo "edit_entry.php?form_id={$form_id}&entry_id={$entry_id}&nav=next"; ?>" title="Next Entry" style="margin-left: 5px"><img src="images/navigation/005499/24x24/Forward.png"></a> </div>
        <div id="ve_entry_actions" class="gradient_blue">
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
<div id="WaitDialogXXX" style="text-align: center; vertical-align: middle; display: none; position: fixed; top: 0; left: 0; padding-top: 200px; height: 100%; width: 100%; z-index: 99; background: #969696; opacity: 0.5;">
		<img  src="images/loading.gif" />
		<div style="margin-top: 10px; color: white">
			<b>Please wait...</b>
		</div>
</div>
<?php 

$time_estimate = 1;
echo $time_estimate;
if ($form_element_count > 1000) {
	$time_estimate = 10;
} else if ($form_element_count > 600) {
	$time_estimate = 5;
} else if ($form_element_count > 300) {
	$time_estimate = 3;
} else if ($form_element_count > 100) {
	$time_estimate = 2;
}

?>

<div id="processing-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100;">
	<div style="font-weight: bold; font-size: 110%; text-align: center; vertical-align: middle; position: absolute; top: 25%; left: 40%; color: black; background-color: white; padding: 1rem 0rem; width: 28rem; border-radius: 0.5rem;">
				Saving your entries and generating your output document(s).<br>
	 			This may take up to <?php echo $time_estimate ?> minute(s) to complete the process.<br>
				Please do not close your browser. Thank you!
				<img src="images/loading-gears.gif" style="height: 60%; width: 60%"/>
	</div>
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
	
	$('#submit_form').click(function(){
		var message_div = $('div#processing-dialog');
		message_div.css("visibility", "visible");
	});
	
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

	
} else {
	echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
}
