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
require('includes/users-functions.php');
require('includes/check-session.php');
require_once("../itam-shared/includes/chatbot.php");
require_once("../itam-shared/includes/helper-functions.php");

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

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	$ssl_suffix  = la_get_ssl_suffix();

	$form_id  = (int) trim($_GET['form_id']);
	$company_id  = (int) trim($_GET['company_id']);
	$entry_id = (int) trim($_GET['entry_id']);

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
	
	$row['form_name'] = la_trim_max_length($row['form_name'], 65);

	if(!empty($row)){
		$form_name = htmlspecialchars($row['form_name']);
	}else{
		die("Error. Unknown form ID.");
	}

	if(empty($company_id)) {
		die("Error. Invalid company_id.");
	}

	if(empty($entry_id)) {
		//get the latest entry_id and redirect
		$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
		$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
		$row_entry_id = la_do_fetch_result($sth_entry_id);
		if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
			header("Location: {$_SERVER['REQUEST_URI']}&entry_id={$row_entry_id['entry_id']}");
			exit;
		} else {
			die("Error. Invalid entry_id.");
		}
	}
	
	$_SESSION['admin'] = $_SESSION['la_user_id'];

	$tmp_form_data = NULL;
	$submit_result = array();

	// generate column if not exists
	chkColumnExistence($dbh, $form_id);

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
		$input_array['called_from'] = 'edit_entry';

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
					$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];
					header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&done=1&resume_form=1");
					exit;
				}
				else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
					$target_page_id = $submit_result['target_page_id'];
					if(is_numeric($target_page_id)){
						header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&la_page={$target_page_id}");
						exit;
					}
					else if($target_page_id == 'success'){
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&done=1");
							exit;
						}else{
							header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&done=1");
							exit;
						}
					}
				}
				else{
					if(isset($input_array['submit_secondary']) && isset($_SESSION['casecade_back_session'])){
						if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']])){
							header("Location: /auditprotocol/edit_entry.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$submit_result['next_page_number']]);
						}else{
							header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}");
						}
						exit();
					}
					if(isset($input_array['go_to_ask_page']) && !empty($input_array['go_to_ask_page'])){
            $_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						if(isset($_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']])){
							header("Location: /auditprotocol/edit_entry.php?".$_SESSION['casecade_back_session'][$input_array['form_id']][$input_array['go_to_ask_page']]);
						}else{
							header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&la_page={$input_array['go_to_ask_page']}");
						}
						exit;
                    }
					if(isset($input_array['casecade_form_page_number']) && $input_array['casecade_form_page_number'] && $input_array['page_number'] == $tmp_form_data[0]['form_page_total']){
						if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
							$_SESSION['review_id'] = $submit_result['review_id'];
							header("Location: confirm.php?id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&la_page_from={$input_array['page_number']}");
							exit;
						}else{
							header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&done=1");
							exit;
						}
					}
					if(isset($_REQUEST['casecade_form_page_number']) && $_REQUEST['casecade_form_page_number']){
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

						if($_REQUEST['casecade_form_page_number'] != 'NO_ELEMENTS') {
							$_REQUEST['casecade_form_page_number']++;
						}

						header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}");
						exit;
					}
					if(!empty($submit_result['next_page_number'])){//redirect to the next page number
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						header("Location: /auditprotocol/edit_entry.php?form_id={$input_array['form_id']}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}&la_page={$submit_result['next_page_number']}");
						exit;
					} else {
						//delete lock
						deleteLock(array('dbh' => $dbh, 'form_id' => $form_id, 'entity_user_id' => $_SESSION['la_user_id'], 'entity_id' => $company_id));

						unset($_SESSION['casecade_back_session']);
						
						header("Location: /auditprotocol/view_entry.php?form_id={$form_id}&company_id={$input_array['company_id']}&entry_id={$input_array['entry_id']}");
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

				$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
			}
		}else{ //if password form submitted

			if($submit_result['status'] === true){ //on success, display the form
				$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,array(),true);
			}else{
				$custom_error = $submit_result['custom_error']; //error, display the pasword form again

				$form_params = array();
				$form_params['custom_error'] = $custom_error;
 				$markup = la_display_form($dbh,$input_array['form_id'],$company_id,$entry_id,$form_params);
			}
		}
	}
	else{

		//if auto-mapping enabled disable lock logic
		$form_data = getFormData(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'column' => 'form_enable_auto_mapping'
		));

		if( ! $form_data[0]['form_enable_auto_mapping'] ) {
			//check if form is not locked
			isFormLockedForUser(array('dbh' => $dbh, 'form_id' => $form_id, 'entry_id' => $entry_id, 'entity_user_id' => $_SESSION['la_user_id'], 'entity_id' => $company_id));	
		}

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

			//delete lock
			deleteLock(array('dbh' => $dbh, 'form_id' => $form_id, 'entity_user_id' => $_SESSION['la_user_id'], 'entity_id' => $company_id));

			/*********************************************************************************/

			unset($_SESSION['casecade_back_session']);

			header("Location: /auditprotocol/view_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}");
		}else{
			/**********************************************/
			/*   Fetching Company data from clent table   */
			/**********************************************/

			$showSubmit = true;
			
			$form_params = array();
			$form_params['page_number'] = $page_number;
			$form_params['called_from'] = 'edit_entry';
			
			$markup = la_display_form($dbh, $form_id, $company_id, $entry_id, $form_params, $showSubmit);
			
			/***************************************************************/
		}
	}

	if(isset($_SESSION['la_user_id'])){
		//header("Content-Type: text/html; charset=UTF-8");
			$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/entry_print.css" media="print">
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
EOT;

	$current_nav_tab = 'manage_forms';
	$load_custom_js = false;
	require('includes/header.php'); 	
?>
<style>
html {
	background: none!important;
}
#form_container:before {
	display: none!important;
}
#form_container:after {
	display: none!important;
}
#main_body .buttons{
	margin-top:0 !important;
}
/*form.itauditm {
	position: relative;
}*/

#dialog-confirm-entry-delete{
	height: 135px !important;	
}
#processing-dialog img{
	height: 30% !important;
}
body .WarpShadow {
	position: unset;
}
*, ::after, ::before {
	box-sizing: unset!important;
}
button:focus {
	outline: unset!important;
}
</style>
<div id="content" class="full">
  <div class="post edit_entry">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <?php if($is_incomplete_entry){ ?>
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_entries.php?id={$form_id}'>Entries</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_incomplete_entries.php?id={$form_id}'>Incomplete</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> #<?php echo $entry_id; ?></h2>
          <?php }else{ ?>
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_entries.php?id={$form_id}'>Entries</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> #<?php echo $entry_id; ?></h2>
          <?php } ?>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <div id="ve_details" data-formid="<?php echo $form_id; ?>" data-entryid="<?php echo $entry_id; ?>" data-incomplete="<?php if($is_incomplete_entry){ echo '1';}else{ echo '0';} ?>"> <?php echo $markup; ?> </div>
      <div id="ve_actions">
      	<?php
      		//get previous and next entry_ids for the company
					$query = "SELECT entry_id AS current_entry_id,
								(SELECT entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} s1
									WHERE s1.entry_id < s.entry_id AND s1.company_id = s.company_id
									ORDER BY entry_id DESC LIMIT 1) AS previous_entry_id,
								(SELECT entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} s2
									WHERE s2.entry_id > s.entry_id AND s2.company_id = s.company_id
									ORDER BY entry_id ASC LIMIT 1) AS next_entry_id
							FROM ".LA_TABLE_PREFIX."form_{$form_id} s WHERE s.company_id = ? AND s.entry_id = ? GROUP BY entry_id";
					$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
					$row = la_do_fetch_result($sth);
					$previous_entry_id = !empty($row['previous_entry_id']) ? $row['previous_entry_id'] : null;
					$next_entry_id = !empty($row['next_entry_id']) ? $row['next_entry_id'] : null;

      	?>
      	<div id="ve_entry_navigation"> 
        	<?php
						if(!is_null($previous_entry_id)) {
							?>
							<a href="<?php echo "edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$previous_entry_id}"; ?>" title="Previous Entry">
								<img src="images/navigation/005499/24x24/Back.png">
							</a>
							<?php
						} else {
							?>
							<a href="<?php echo "edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}"; ?>" title="Previous Entry" style="visibility: hidden;">
								<img src="images/navigation/005499/24x24/Back.png">
							</a>
							<?php
						}

						if(!is_null($next_entry_id)) {
							?>
							<a href="<?php echo "edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$next_entry_id}"; ?>" title="Next Entry" style="margin-left: 5px">
								<img src="images/navigation/005499/24x24/Forward.png">
							</a>
							<?php
						} else {
							?>
							<a href="<?php echo "edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}"; ?>" title="Next Entry" style="visibility: hidden;">
								<img src="images/navigation/005499/24x24/Forward.png">
							</a>
							<?php
						}
					?>
        </div>
		<div id="ve_entry_actions">
			<ul>
				<li style="border-bottom: 1px dashed #8EACCF">
					<a id="ve_action_view" href="<?php echo "view_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}"; ?>"><img src="images/navigation/005499/16x16/View.png">View Entry Data</a>
				</li>
				<?php if(empty($_SESSION['is_examiner'])){ ?>
					<li style="border-bottom: 1px dashed #8EACCF">
						<a id="ve_action_email" title="Email Entry Data" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Email.png">Email Entry Data</a>
					</li>
				<?php } ?>
				<?php
					$show_generate_entry_document = false;
					if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_perms['edit_entries'])){
						if( $form_properties['form_enable_template_wysiwyg'] == 1 && !empty( $form_properties['form_template_wysiwyg_id'] ) ) {
							$show_generate_entry_document = true;
						} else {
							$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
							$param_template_count = array($form_id);
							$result_template_count = la_do_query($query_template_count, array($form_id), $dbh);
							$num_rows = $result_template_count->fetchColumn();
							if( $num_rows > 0 ) {
								$show_generate_entry_document = true;
							}
						}
						if( $show_generate_entry_document ) { ?>
							<li style="border-bottom: 1px dashed #8EACCF">
								<a href="javascript:void(0)" onclick="generate_entry_document(<?=$form_id?>, <?=$_SESSION['la_user_id']?>, <?=$company_id?>, <?=$entry_id?>);"><img src="images/navigation/005499/16x16/List.png">Generate Document</a>
							</li>
					<?php 
						}
					} ?>
				<li>
					<a id="ve_action_delete" title="Delete Entry" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Delete.png ">Delete Entry Data</a>
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
<div id="dialog-entry-sent" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-entry-sent-msg"> The entry has been sent. </p>
</div>
<div id="processing-dialog-edit-entry" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
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
<div id="WaitDialogXXX" style="text-align: center; vertical-align: middle; display: none; position: fixed; top: 0; left: 0; padding-top: 200px; height: 100%; width: 100%; z-index: 99; background: #969696; opacity: 0.5;">
		<img  src="images/loading.gif" />
		<div style="margin-top: 10px; color: white">
			<b>Please wait...</b>
		</div>
</div>
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
<style type="text/css">
	#dialog-email-entry {
		height: 85px!important;
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
</style>
<?php
	$footer_data =<<<EOT
<!--[if lt IE 9]><script src="js/signaturepad/flashcanvas.js"></script><![endif]-->
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/view_entry.js"></script>
<script type="text/javascript" src="js/uploadifive/jquery.uploadifive.js"></script>
<script src="js/uploadify/swfobject.js" type="text/javascript"></script>
<script src="js/uploadify/jquery.uploadify.js" type="text/javascript"></script>
<script src="js/jquery.jqplugin.min.js" type="text/javascript"></script>

<script>
$(document).ready(function(e) {
	$("#submit_primary").click(function(){
		if($(this).val() == "Submit"){
			var message_div = $('div#processing-dialog');
			message_div.css("visibility", "visible");
		}
	});
	var total_score = 0;
	$('.score-span').each(function() {
        if($(this).attr('data-score-value') != "")
        	total_score += parseInt($(this).attr('data-score-value'));
	});
	$('#total-score').html(total_score);
});
</script>
<script>
	var autoSave = localStorage.getItem("auto-save-then-logout");
	if(autoSave && autoSave == "true") {
		localStorage.removeItem("auto-save-then-logout");
		document.location.href = "/auditprotocol/logout.php";
	}
</script>
EOT;

$disable_jquery_loading = true;	
require('includes/footer.php'); 
	
} else {
	echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
}

