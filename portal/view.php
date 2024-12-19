<?php

/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2017 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com

More info at: http://lazarusalliance.com
********************************************************************************/

require('includes/init.php');
header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");

require_once("../policymachine/classes/CreateDocx.php");
require_once("../policymachine/classes/CreateDocxFromTemplate.php");

require('config.php');
require('includes/language.php');
require('includes/db-core.php');
require('includes/filter-functions.php');
require('includes/common-validator.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/view-functions.php');
require('../auditprotocol/includes/docxhelper-functions.php');
require('includes/post-functions.php');
require('includes/entry-functions.php');
require('includes/theme-functions.php');
require('lib/swift-mailer/swift_required.php');
require('lib/HttpClient.class.php');
require('lib/recaptchalib.php');
require('lib/php-captcha/php-captcha.inc.php');
require('lib/text-captcha.php');
require('hooks/custom_hooks.php');
require_once("../itam-shared/includes/chatbot.php");
require_once("../itam-shared/includes/helper-functions.php");

/******************************************************/

global $exclude_footer_jquery;

function setBackInSes($params=array()){
	$dbh         = $params['dbh'];
	$form_id     = $params['form_id'];
	$la_page     = $params['la_page'];
	$url_strings = $params['url_strings'];

	$query    = "select `element_type` from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id and `element_type` = :element_type";
	$result   = la_do_query($query, array(':form_id' => $form_id, ':element_type' => 'casecade_form'), $dbh);
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

$form_id = (int)la_sanitize(trim($_GET['id']));
if($form_id == 0) {
	die("Invalid form ID.");
}

$entry_id = (int)la_sanitize(trim($_GET['entry_id']));
if(empty($entry_id)) {
	//get the latest entry_id and redirect
	$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
	$sth_entry_id = la_do_query($query_entry_id, array($_SESSION['la_client_entity_id']), $dbh);
	$row_entry_id = la_do_fetch_result($sth_entry_id);
	if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
		header("Location: {$_SERVER['REQUEST_URI']}&entry_id={$row_entry_id['entry_id']}");
		exit;
	} else {
		$entry_id = time();
		header("Location: {$_SERVER['REQUEST_URI']}&entry_id={$entry_id}");
		exit;
	}
}
$company_id = $_SESSION['la_client_entity_id'];
$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);
$inQuery      = implode(',', array_fill(0, count($userEntities), '?'));

$tmp_form_data = NULL;
$submit_result = array();

/***** BEGIN 'CHECK IF USER SUBSCRIBED TO FORM' *****/
// check to see if the form is free or requires a subscription
$formPrice;
foreach($dbh->query("SELECT `payment_price_amount` FROM `ap_forms` WHERE `form_id` = {$form_id}") as $result) {
    $formPrice = $result['payment_price_amount'];
}
if($formPrice !== '0.00') { // then form is not free
	// check if the user is subscribed to the form
	$isUserSubscribedToForm;
	foreach($dbh->query("SELECT * FROM `ap_form_payment_check` WHERE `form_id` = {$form_id} AND `company_id` = {$company_id}") as $result) {
		$isUserSubscribedToForm = $result['payment_date'];
	}
	// if yes, proceed, else, not authorized
	if($isUserSubscribedToForm == null) {
		die("This form requires payment to access, and you are not subscribed. Please subscribe to the form first.");
	}
}
/***** END 'CHECK IF USER SUBSCRIBED TO FORM' *****/

// generate column if not exists
chkColumnExistence($dbh, $form_id);

//if auto-mapping enabled disable lock logic
$form_data = getFormData(array(
	'dbh' => $dbh,
	'form_id' => $form_id,
	'column' => 'form_enable_auto_mapping'
));
$is_auto_mapping_enabled = $form_data[0]['form_enable_auto_mapping'];

if( ! $is_auto_mapping_enabled ) {
	// This function will check whether the form is currently edited by someone or not. If edited then it will redirect it form lock page.
	//isFormLockedForUser(array('dbh' => $dbh, 'form_id' => $form_id, 'entities' => $userEntities, 'entity_user_id' => $_SESSION['la_client_user_id'], 'entity_id' => $company_id ));
}

if(isset($_GET['la_page'])){
	setBackInSes(array(
		'dbh' => $dbh,
		'form_id' => $form_id,
		'la_page' => la_sanitize($_GET['la_page']),
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
	}

	if(!isset($input_array['password'])){ //if normal form submitted
		if($submit_result['status'] === true){
			if(!empty($submit_result['form_resume_url'])){ //the user saving a form, display success page with the resume URL
				if($_REQUEST['coming_from_session_timeout'] == "true") {
					header("Location: /portal/client_logout.php");
					exit;
				}
				$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];

				header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}&done=1&resume_form=1");
				exit;
			}else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
				$target_page_id = $submit_result['target_page_id'];
				if(is_numeric($target_page_id)){
					header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}&la_page={$target_page_id}");
					exit;
				}else if($target_page_id == 'payment'){
					//redirect to payment page, based on selected merchant
					$form_properties = la_get_form_properties($dbh,$input_array['form_id'],array('payment_merchant_type'));
					if(in_array($form_properties['payment_merchant_type'], array('stripe','authorizenet','paypal_rest','braintree'))){
						//allow access to payment page
						$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
						$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['company_id'];

						// edited on 05-11-2014
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
						exit;
					}else if($form_properties['payment_merchant_type'] == 'paypal_standard'){
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
						exit;
					}
				}else if($target_page_id == 'review'){
					if(!empty($submit_result['origin_page_number'])){
						$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
					}
					$_SESSION['review_id'] = $submit_result['review_id'];
					header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/confirm.php?id={$input_array['form_id']}&entry_id={$entry_id}{$page_num_params}");
					exit;
				}else if($target_page_id == 'success'){
					//redirect to success page
					if(empty($submit_result['form_redirect'])){
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
						exit;
					}else{
						header("Location: ".$submit_result['form_redirect']);
						exit;
					}
				}
			}else{
				if(isset($input_array['submit_secondary']) && isset($_SESSION['casecade_back_session'])){
					if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']])){
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?".$_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']]);
					}else{
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}");
					}
					exit();
				}
				if(isset($input_array['go_to_ask_page']) && !empty($input_array['go_to_ask_page'])){
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
					if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']])){
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?".$_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']]);
					}else{
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}&la_page={$input_array['go_to_ask_page']}");
					}
					exit;
				}
				if(isset($input_array['casecade_form_page_number']) && $input_array['casecade_form_page_number'] && $input_array['page_number'] == $tmp_form_data[0]['form_page_total']){
					if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/confirm.php?id={$input_array['form_id']}&entry_id={$entry_id}&la_page_from={$input_array['page_number']}");
						exit;
					}else{
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
						exit;
					}
				}
				if(isset($_REQUEST['casecade_form_page_number']) && $_REQUEST['casecade_form_page_number']){
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

					if($_REQUEST['casecade_form_page_number'] != 'NO_ELEMENTS') {
						$_REQUEST['casecade_form_page_number']++;
					}

					header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}");
					exit;
				}
				if(!empty($submit_result['next_page_number'])){ //redirect to the next page number
					$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
					header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}&entry_id={$entry_id}&la_page={$submit_result['next_page_number']}");
					exit;
				} else if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
					if(!empty($submit_result['origin_page_number'])){
						$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
					}

					$_SESSION['review_id'] = $submit_result['review_id'];
					header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/confirm.php?id={$input_array['form_id']}&entry_id={$entry_id}{$page_num_params}");
					exit;
				} else { //otherwise display success message or redirect to the custom redirect URL or payment page
					if(la_is_payment_has_value($dbh,$input_array['form_id'],$submit_result['entry_id'])){
						//redirect to credit card payment page, if the merchant is being enabled and the amount is not zero
						//allow access to payment page
						$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
						$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];
						// edited on 05-11-2014
						header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
						exit;
					}else{
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?id={$input_array['form_id']}entry_id={$entry_id}&done=1");
							exit;
						}else{
							header("Location: ".$submit_result['form_redirect']);
							exit;
						}
					}
				}
			}
		}else if($submit_result['status'] === false){ //there are errors, display the form again with the errors
			$exclude_footer_jquery = true;
			$old_values 	= $submit_result['old_values'];
			$custom_error 	= @$submit_result['custom_error'];
			$error_elements = $submit_result['error_elements'];

			$form_params = array();
			$form_params['page_number'] = $input_array['page_number'];
			$form_params['populated_values'] = $old_values;
			$form_params['error_elements'] = $error_elements;
			$form_params['custom_error'] = $custom_error;

			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
		}
	}else{ //if password form submitted
		$exclude_footer_jquery = true;
		if($submit_result['status'] === true){ //on success, display the form
			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id);
		}else{
			$custom_error = $submit_result['custom_error']; //error, display the pasword form again

			$form_params = array();
			$form_params['custom_error'] = $custom_error;
			$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
		}
	}
} else {
	$page_number	= (int) trim($_GET['la_page']);
	// $page_number 	= la_verify_page_access($form_id,$page_number);
	$resume_key		= la_sanitize(trim($_GET['la_resume']));
	if( empty($page_number) )
		$page_number = 1;

	if(!empty($resume_key)){
		$_SESSION['la_form_resume_key'][$form_id] = $resume_key;
	}

	if(!empty($_GET['done'])){
		deleteLock(array('dbh' => $dbh, 'form_id' => $form_id, 'entity_user_id' => $_SESSION['la_client_user_id']));

		// add user activity to log: activity - 5 (SUBMITTED)
		$session_time = sessionTime($dbh, $_SESSION['la_client_user_id'], 0);
		addUserActivity($dbh, $_SESSION['la_client_user_id'], $form_id, 5, "Session Time {$session_time}", time());

		if(!isset($_REQUEST['resume_form'])){
			incrementFormCounter($dbh, array('form_id' => $form_id, 'company_id' => $company_id));
		}

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
		$exclude_footer_jquery = true;
		/**********************************************/
		/*   Fetching Company data from client table   */
		/**********************************************/
		$query_frm = "select form_counter from ".LA_TABLE_PREFIX."form_payment_check where form_id = ? and company_id IN ({$inQuery}) order by payment_date desc limit 1";
		$resultSet = la_do_query($query_frm, array_merge(array($form_id), $userEntities),$dbh);
		$rowdata   = la_do_fetch_result($resultSet);
		if((!empty($rowdata['form_counter']) && $rowdata['form_counter'] >= 1)){
			$showSubmit = true;
		}else{
			$showSubmit = false;
		}

		/**********************************************************************/

		$form_values                       = la_get_entry_values($dbh, $form_id, $company_id, $entry_id);

		$form_params                       = array();
		$form_params['populated_values']   = $form_values;
		$form_params['page_number']        = (isset($_REQUEST['la_page']) ? la_sanitize($_REQUEST['la_page']) : 1);
		$markup                            = la_display_form($dbh, $form_id, $company_id, $entry_id, $form_params, $showSubmit);

		/***************************************************************/
	}
}

/***************************************************************************/
$query_access = "SELECT `entity_id` FROM ".LA_TABLE_PREFIX."entity_form_relation WHERE `form_id` = ?";
$result_access = la_do_query($query_access, array($form_id), $dbh);
$form_access_flag = false;
while ($row_access = la_do_fetch_result($result_access)) {
	if($row_access["entity_id"] == 0 || $row_access["entity_id"] == $_SESSION["la_client_entity_id"]) {
		$form_access_flag = true;
	}
}
if(!$form_access_flag) {
	$_SESSION['form_not_subscribed'] = 'This form is not viewable.';
	$_SESSION['form_not_subscribed_id'] = $form_id;
	header("Location:client_account.php");
}

$query_det  = "select `client_id` from ".LA_TABLE_PREFIX."ask_client_forms where `form_id` = ? and `client_id` IN ({$inQuery})";
$result_det = la_do_query($query_det, array_merge(array($form_id), $userEntities), $dbh);
$row_det    = la_do_fetch_result($result_det);
/***************************************************************************/

if((!empty($row_det) && isset($row_det['client_id']))){

	if(getEntititiesSubscribeStatus($dbh, $userEntities, $form_id)){
		header("Content-Type: text/html; charset=UTF-8");
		echo $markup;
	}else{
		// echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
		$_SESSION['form_not_subscribed'] = 'This form is not viewable. Please subscribe to the form.';
		$_SESSION['form_not_subscribed_id'] = $form_id;
		header("Location:client_account.php");
	}

}else{
	// echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
	$_SESSION['form_not_subscribed'] = 'This form is not viewable. Please subscribe to the form.';
	$_SESSION['form_not_subscribed_id'] = $form_id;
	header("Location:client_account.php");
}
?>
<?php
	
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
<div id="dialog-element-note" title="Save or Clear a note of this element" style="display: none; height: 400px;">
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
<?php
include_once("portal-footer.php");
?>
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
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
	/*#main_body form li span label {
		display: initial!important;
	}*/
</style>
<script type="text/javascript">
	$("#submit_primary").click(function(){
		if($(this).val() == "Submit"){
			var message_div = $('div#processing-dialog');
			message_div.css("visibility", "visible");
		}		
	});
</script>

<script>
	var autoSave = localStorage.getItem("auto-save-then-logout");
	if(autoSave && autoSave == "true") {
		localStorage.removeItem("auto-save-then-logout");
		document.location.href = "/portal/client_logout.php";
	}
</script>