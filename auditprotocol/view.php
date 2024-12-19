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
require('config.php');
require('includes/language.php');
require('includes/db-core.php');
require('includes/common-validator.php');
require('includes/docxhelper-functions.php');
require('includes/post-functions.php');
require('includes/filter-functions.php');
require('includes/entry-functions.php');
require('includes/helper-functions.php');
require('includes/view-functions.php');
require('includes/theme-functions.php');
require('lib/swift-mailer/swift_required.php');
require('lib/HttpClient.class.php');
require('lib/recaptchalib.php');
require('lib/php-captcha/php-captcha.inc.php');
require('lib/text-captcha.php');
require('hooks/custom_hooks.php');
require_once("../itam-shared/includes/chatbot.php");
require_once("../itam-shared/includes/helper-functions.php");
$page_time_start = microtime(true);
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

$dbh 		 = la_connect_db();
$la_settings = la_get_settings($dbh);
$ssl_suffix  = la_get_ssl_suffix();

$_SESSION['admin'] = $_SESSION['la_user_id'];
$form_id = (int)la_sanitize($_GET['id']);
if($form_id == 0) {
	die("Invalid form ID.");
}

$company_id = (int)la_sanitize(trim($_GET['company_id']));
if($company_id == 0) {
	//if company_id doesn't exist in the URL, create a new company_id
	$company_id = time();
	header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$form_id}&company_id={$company_id}&entry_id={$company_id}");
	exit();
}

$entry_id = (int)la_sanitize(trim($_GET['entry_id']));
if($entry_id == 0) {
	//if entry_id doesn't exist in the URL, create a new entry_id
	$entry_id = $company_id;
	header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$form_id}&company_id={$company_id}&entry_id={$entry_id}");
	exit();
}

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
	$is_form_submitted = true;
	$input_array   = la_sanitize($_POST);
	$tmp_form_data = getFormData(array(
		'dbh' => $dbh,
		'form_id' => $form_id,
		'column' => 'form_page_total',
	));

	$submit_result = la_process_form($dbh,$input_array);
	
	if	(!empty($input_array['submit_secondary'])) {
		//SR Added - Back button javascript 6/30/18
		?>
		<script>window.history.go(-2);</script>
		<?php
		exit;
		//die();
	}

	if(!isset($input_array['password'])){ //if normal form submitted
		if($submit_result['status'] === true){
			if(!empty($submit_result['form_resume_url'])){ //the user saving a form, display success page with the resume URL
				if($_REQUEST['coming_from_session_timeout'] == "true") {
					header("Location: /auditprotocol/logout.php");
					exit;
				}
				$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];
				header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1&resume_form=1");
				exit;
			}
			else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
				$target_page_id = $submit_result['target_page_id'];
				if(is_numeric($target_page_id)){
					header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page={$target_page_id}");
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
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
						exit;
					}else if($form_properties['payment_merchant_type'] == 'paypal_standard'){
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
						exit;
					}
				}
				else if($target_page_id == 'review'){
					if(!empty($submit_result['origin_page_number'])){
						$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
					}
					$_SESSION['review_id'] = $submit_result['review_id'];
					header("Location: confirm.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}{$page_num_params}");
					exit;
				}
				else if($target_page_id == 'success'){
					//redirect to success page
					if(empty($submit_result['form_redirect'])){
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
						exit;
					}else{
						header("Location: ".$submit_result['form_redirect']);
						exit;
					}
					
				} else {
					header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page={$submit_result['next_page_number']}");
					exit;
				}
			}
			else{
				if(isset($input_array['submit_secondary']) && isset($_SESSION['casecade_back_session'])){
					if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']])){


						header("Location: /auditprotocol/view.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']]);
					}else{
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}");
					}
					exit();
				}
				if(isset($input_array['go_to_ask_page']) && !empty($input_array['go_to_ask_page'])){
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
					if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']])){
						header("Location: /auditprotocol/view.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']]);
					}else{
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page={$input_array['go_to_ask_page']}");
					}
					exit;
				}
				if(isset($input_array['casecade_form_page_number']) && $input_array['casecade_form_page_number'] && $input_array['page_number'] == $tmp_form_data[0]['form_page_total']){
					if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page_from={$input_array['page_number']}");
						exit;
					}else{
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
						exit;
					}
				}
				if(isset($_REQUEST['casecade_form_page_number']) && $_REQUEST['casecade_form_page_number']){
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

					if($_REQUEST['casecade_form_page_number'] != 'NO_ELEMENTS') {
						$_REQUEST['casecade_form_page_number']++;
					}

					header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}");
					exit;
				}
				if(!empty($submit_result['next_page_number'])){//redirect to the next page number
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
					header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&la_page={$submit_result['next_page_number']}");
					exit;
				}
				else if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
					//echo 'Test'; exit;
					if(!empty($submit_result['origin_page_number'])){
						$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
					}

					$_SESSION['review_id'] = $submit_result['review_id'];
					header("Location: confirm.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}{$page_num_params}");
					exit;
				}
				else{ //otherwise display success message or redirect to the custom redirect URL or payment page
					if(la_is_payment_has_value($dbh,$input_array['form_id'],$submit_result['entry_id'])){
						//redirect to credit card payment page, if the merchant is being enabled and the amount is not zero
						//allow access to payment page
						$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
						$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];
						// edited on 05-11-2014
						header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
						//header("Location: payment.php?id={$input_array['form_id']}");
						exit;
					}
					else{
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: /auditprotocol/view.php?id={$input_array['form_id']}&company_id={$company_id}&entry_id={$entry_id}&done=1");
							exit;
						}else{
							header("Location: ".$submit_result['form_redirect']);
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
			$form_params['company_id'] = $_SESSION['la_user_id'];
			$form_params['called_from'] = 'view';

			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
		}
	}else{ //if password form submitted

		if($submit_result['status'] === true){ //on success, display the form
			$form_params['called_from'] = 'view';
			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id);
		}else{
			$custom_error = $submit_result['custom_error']; //error, display the pasword form again

			$form_params = array();
			$form_params['custom_error'] = $custom_error;
			$form_params['company_id'] = $_SESSION['la_user_id'];
			$form_params['called_from'] = 'view';
			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
		}
	}
} else {
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
		$session_time = sessionTime($dbh, $_SESSION['la_user_id']);
		addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 5, "Session Time {$session_time}", time());
		incrementFormCounter($dbh, array('form_id' => $form_id, 'company_id' => $_SESSION['la_user_id']));

		/*********************************************************************************/
		$query_form_data_sql = "SELECT `form_id`, `form_redirect`, `form_redirect_enable` FROM `ap_forms` WHERE `form_id` = :form_id";
		$query_form_data_result = la_do_query($query_form_data_sql,array(':form_id' => $form_id),$dbh);
		$query_form_data_row = la_do_fetch_result($query_form_data_result);

		if(!empty($query_form_data_row['form_redirect_enable']) && $query_form_data_row['form_redirect_enable'] == 1){
			header("location:{$query_form_data_row['form_redirect']}");
			exit();
		}else{
			$markup = la_display_success($dbh,$form_id,$company_id,$entry_id,array(),0);
		}
	}else{
		$showSubmit = true;
		/**********************************************************************/
		{
			$form_params = array();
			$form_params['page_number'] = $page_number;
			$form_params['called_from'] = 'view';
			$markup = la_display_form($dbh, $form_id, $company_id, $entry_id, $form_params, $showSubmit);
		}
		/***************************************************************/
	}
}

if(isset($_SESSION['la_user_id'])){
	//header("Content-Type: text/html; charset=UTF-8");
	echo $markup;
} else {
	echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
}
	$select_assignee = '';

	$query_admin = "SELECT `user_id`, `user_fullname` FROM ".LA_TABLE_PREFIX."users WHERE status = 1 ORDER BY user_fullname"; // fectch only active admins
	$sth_admin = la_do_query($query_admin,array(),$dbh);
	while($row_admin = la_do_fetch_result($sth_admin)){
		$select_assignee .= '<option role="admin" value="'.$row_admin['user_id'].'">'.'[Admin] '.$row_admin['user_fullname'].'</option>';
	}
	$accessible_entities = array();
	$query_entity_form_relation = "SELECT `entity_id` FROM ".LA_TABLE_PREFIX."entity_form_relation WHERE `form_id` = ?";
	$sth_entity_form_relation = la_do_query($query_entity_form_relation, array($form_id), $dbh);
	while($row_entity_form_relation = la_do_fetch_result($sth_entity_form_relation)){
		array_push($accessible_entities, $row_entity_form_relation["entity_id"]);
	}
	array_unique($accessible_entities);

	if(count($accessible_entities) > 0) {
		if(in_array("0", $accessible_entities)) {
			$query_entity = "SELECT client_id, company_name FROM ".LA_TABLE_PREFIX."ask_clients GROUP BY client_id ORDER BY company_name";
			$sth_entity = la_do_query($query_entity, array(), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)){
				$select_assignee .= '<option role="entity" value="'.$row_entity['client_id'].'">'.'[Entity] '.$row_entity['company_name'].'</option>';
				$query_user = "SELECT u.client_user_id, u.full_name FROM ".LA_TABLE_PREFIX."ask_client_users u LEFT JOIN ".LA_TABLE_PREFIX."entity_user_relation r ON u.client_user_id = r.client_user_id WHERE u.status = 0 AND r.entity_id = ?"; // fectch only active users
				$sth_user = la_do_query($query_user, array($row_entity["client_id"]), $dbh);
				while($row_user = la_do_fetch_result($sth_user)) {
					$select_assignee .= '<option role="user" value="'.$row_entity['client_id']."-".$row_user['client_user_id'].'" style="padding-left: 20px;">- [User] '.$row_user['full_name'].'</option>';
				}
			}
		} else {
			$inQueryEntity = implode(',', array_fill(0, count($accessible_entities), '?'));
			$query_entity = "SELECT client_id, company_name FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id` IN ({$inQueryEntity})";
	    	$sth_entity = la_do_query($query_entity, $accessible_entities, $dbh);
	    	while($row_entity = la_do_fetch_result($sth_entity)) {
	    		$select_assignee .= '<option role="entity" value="'.$row_entity['client_id'].'">'.'[Entity] '.$row_entity['company_name'].'</option>';
				$query_user = "SELECT u.client_user_id, u.full_name FROM ".LA_TABLE_PREFIX."ask_client_users u LEFT JOIN ".LA_TABLE_PREFIX."entity_user_relation r ON u.client_user_id = r.client_user_id WHERE u.status = 0 AND r.entity_id = ?"; // fectch only active users
				$sth_user = la_do_query($query_user, array($row_entity["client_id"]), $dbh);
				while($row_user = la_do_fetch_result($sth_user)) {
					$select_assignee .= '<option role="user" value="'.$row_entity['client_id']."-".$row_user['client_user_id'].'" style="padding-left: 20px;">- [User] '.$row_user['full_name'].'</option>';
				}
	    	}
		}
	}	
?>
<div id="dialog-select-error-message" title="Error!" class="buttons" style="display: none">
	<img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p id="error-message">Please select a row on the table.</p>
</div>
<div id="dialog-element-note" title="Save or Clear a note of this element" style="display: none;">
	<div style="float: left; padding: 8px; text-align: left;">
		<label style="width: 140px; float: left;">Assign To:</label>
		<select id="assignees" name="assignees" autocomplete="off" multiple style="float: left; width: 500px; height: 200px;">
	      	<?php echo $select_assignee; ?>
		</select>
	</div>
	<div style="float: left; padding: 8px; text-align: left;">
		<label style="width: 140px; float: left;">Field Note:</label>
		<textarea id="element-note" rows="8" style="width: 494px;"></textarea>
	</div>
</div>
<div id="dialog-assigned-note" title="Field notes assigned to you." style="display: none;">
	<table id="table-assigned-note">
		<thead>
			<tr>
				<th width="5%">#</th>
				<th width="60%">Note</th>
				<th width="20%">Assigner</th>
				<th>Status</th>
				<th>Delete</th>
			</tr>
		</thead>
		<tbody id="tbody-assigned-note"></tbody>
	</table>
</div>
<div id="dialog-file-management" style="display: none;">
	<input id="file_target_id" type="hidden" name="file_target_id">
	<div id="file_management" class="tab-panel">
		<div class="tab-panel-header">
			<span>File Management</span>
			<div class="actions">
				<button toggle="div_uploaded_files" class="btn-activity active">Uploaded Files</button>
				<button toggle="div_generated_reports" class="btn-activity">Generated Reports</button>
			</div>
		</div>
		<div class="tab-panel-content">
			<div id="div_uploaded_files" class="activity-div" style="display: none;">
				<table id="table-uploaded-files" class="hover stripe cell-border display nowrap" style="width: 100%;">
					<thead>
						<tr>
							<th></th>
							<th>#</th>
							<th>Form ID</th>
							<th>Form Name</th>
							<th>Field Label</th>
							<th>File Name</th>
							<th>Go To Field</th>
						</tr>
					</thead>
					<tbody id="tbody-uploaded-files" class="hover stripe cell-border display nowrap" style="width: 100%;">
					</tbody>
				</table>
			</div>
			<div id="div_generated_reports" class="activity-div" style="display: none;">
				<table id="table-generated-reports" class="hover stripe cell-border display nowrap" style="width: 100%;">
					<thead>
						<tr>
							<th></th>
							<th>#</th>
							<th>Form ID</th>
							<th>Form Name</th>
							<th>Report For</th>
							<th>Report Name</th>
						</tr>
					</thead>
					<tbody id="tbody-generated-reports">
					</tbody>
				</table>
			</div>
		</div>
	</div>	
</div>
<div id="processing-dialog-file" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">
		<?php if( isset($la_settings['file_viewer_download_option']) && ($la_settings['file_viewer_download_option'] == 1) ) { ?>
			<div style="text-align: right;margin-bottom: 10px;">
				<a href="#" id="file_viewer_download_button" class="bb_button bb_small bb_green" download> 
		          <img src="images/navigation/FFFFFF/24x24/Save.png"> Download
		      	</a>
		    </div>
		<?php } ?>
	
    <div id="document-preview-content" style="height: 440px;">
		<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
	</div>
</div>
<?php
include_once("includes/footer.php");
?>
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<script type="text/javascript">		
	<?php	
	$base_url = str_replace("http:", "https:", $la_settings['base_url']); 
	?>
	app_base_url = '<?=$base_url;?>';
</script>
<style type="text/css">
	#bottom_shadow {
		width: 640px!important;
	}
	#footer {
		margin-left: calc((100% - 640px) / 2);
		clear:both;
		color:#999999;
		text-align:center;
		padding-bottom: 15px;
		font-size: 85%;
		text-align: left;
	}
	.ui-dialog .ui-dialog-titlebar-close {display:none;}
	.ui-dialog {
	    font-family: "globersemibold","Lucida Grande",Tahoma,Arial,Verdana,sans-serif;
	    padding: 0;
		position: absolute;
		overflow: hidden;
		background-color: #0085CC !important;
	    border: 8px solid #0085CC !important;
	}
	.ui-widget-content {
		color: #222222;
	}
	.ui-widget {
		font-size: 1.1em;
	}
	.ui-helper-clearfix::after {
		clear: both;
	    content: ".";
	    display: block;
	    height: 0;
	    visibility: hidden;
	}
	.ui-dialog .ui-dialog-titlebar {
		background: none repeat scroll 0 0 #fff;
	    border: medium none;
	    border-radius: 2px 2px 0 0;
	    text-align: center;
		padding: 0.4em 1em;
	    position: relative;
	}
	.ui-widget-header {
		color: #222222;
	    font-weight: bold;
	}
	.ui-dialog .ui-dialog-title {
		color: #80b638;
	    float: none !important;
	    font-size: 150%;
	    font-weight: 400;
	    margin: 0;
	    text-align: center;
	}
	.ui-dialog .ui-dialog-content {
		background-color: #fff;
	    border: 0 none;
	    overflow: auto;
	    padding: 0.5em 1em;
		padding-bottom: 15px;
	    position: relative;
		color: #222222;
		text-align: center;
	}
	.ui-helper-clearfix::after {
		clear: both;
	    content: ".";
	    display: block;
	    height: 0;
	    visibility: hidden;
	}
	.ui-dialog .ui-dialog-buttonpane {
		background-color: #ededed;
	    border-radius: 0 0 2px 2px;
	    border-top: 1px solid #cacaca;
	    padding-top: 10px;
		margin: 0;
		background-image: none;
		text-align: left;
	}
	.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset {
		float: left;
	    padding-left: 15px;
	}
	.ui-dialog .ui-dialog-buttonpane button {
		font-family: "globerbold", "Lucida Grande", Tahoma, Arial, Verdana, sans-serif !important;
	}
	.btn_secondary_action {
		border: none !important;
		background-color: transparent !important;
		padding: 7px 10px !important;
		color: #529214;
	}
	#table-assigned-note {
		width: 100%;
		border-collapse: collapse;
	}
	#table-assigned-note .alt{
		background-color: #F3F7FB !important;
	}
	#table-assigned-note th {
		color: #fff;
	    font-family: Arial, helvetica, 'Helvetica Neue', Arial, 'Trebuchet MS', 'Lucida Grande';
	    font-size: 13px;
	    font-weight: 500;
	    padding:5px;
	    background-color: #3B699F;
	    text-shadow: 0 1px 1px #000000;
	    border-right: 1px dotted #ffffff;
	}
	#table-assigned-note td {
		padding: 5px;
	    border-bottom: 1px solid #8EACCF;
	    color: #000000;
	    vertical-align: middle;
	}
	#table-assigned-note td img {
		margin-top: 5px;
	}
	#table-assigned-note tbody tr:hover td {
	    background-color: #7aa7d6;
	}
	*, ::after, ::before {
		box-sizing: unset!important;
	}
	button:focus {
		outline: unset!important;
	}
</style>

<script type="text/javascript">
		$("#submit_primary").click(function(){
			if($(this).val() == "Submit"){
				var message_div = $('div#processing-dialog');
				message_div.css("visibility", "visible");
			}		
		});

		$("#processing-dialog-file").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 400,
			draggable: false,
			resizable: false
		});

		$("#document-preview").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 930,
			draggable: false,
			resizable: false,
			open: function(){
				//$(this).next().find('button').blur()
			},
			buttons: [{
				text: 'Close',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}
			}]
		});

		$(document).on('click', '.entry-link-preview', function(e){
			e.preventDefault();
			$('#document-preview-content').html("");
			$('#file_viewer_download_button').attr('href', "");
			var identifier = $(this).data('identifier');
			var ext = $(this).data('ext');
			var src = $(this).data('src');

			$('#document-preview-content').html("");
			if( identifier == 'image_format' ) {
				//means this document is an image and has format one of these ('png', 'jpg', 'jpeg')
				//so we can show directly it in popup
				$('#document-preview-content').html('<img src="'+src+'" style="max-width: 100%;max-height: 100%;margin: auto;display: block;" />');
				$('#file_viewer_download_button').attr('href', src);
				$('#document-preview').dialog('open');
			} else if( identifier == 'other' ) {
				$('#processing-dialog-file').dialog('open');

				//do the ajax call to get pdf link
				$.ajax({
					type: "GET",
					async: true,
					url: "download.php?q="+src,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//show error message to user
						$('#processing-dialog-file').dialog('close');
						$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
						$('#document-preview').dialog('open');
					},
					success: function(response){
						if( response.status == 'success' ) {
							if( response.only_download ) {
								$('#document-preview-content').html('Preview is not available for this file extension.');
							} else {
								$('#document-preview-content').html('<embed src="'+response.file_src+'#toolbar=0" type="application/pdf" width="100%" height="100%">');
							}
							$('#processing-dialog-file').dialog('close');
							$('#document-preview').dialog('open');
							$('#file_viewer_download_button').attr('href', response.download_src);

						} else {
							$('#processing-dialog-file').dialog('close');
							$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
							$('#document-preview').dialog('open');
						}
					}
				});
			}
		});
</script>

<script>
	var autoSave = localStorage.getItem("auto-save-then-logout");
	if(autoSave && autoSave == "true") {
		localStorage.removeItem("auto-save-then-logout");
		document.location.href = "/auditprotocol/logout.php";
	}
</script>

<?php 
$page_time_end = microtime(true);
// echo "page execution time: ".strval($page_time_end - $page_time_start);
?>