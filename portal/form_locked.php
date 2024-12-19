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
	require('includes/check-client-session-ask.php');
	require('includes/users-functions.php');

	require('includes/language.php');
	require('includes/entry-functions.php');

	if(isset($_REQUEST['unlock'])){
		$dbh = la_connect_db();
		$form_id = (int) trim($_REQUEST['id']);
		$user_id = (int) trim($_REQUEST['unlock']);
		
		unlockForm(array('formUnlockedBy' => $_SESSION['la_client_user_id'], 'form_id' => $form_id, 'entity_user_id' => $user_id, 'entity_id' => $_SESSION['la_client_entity_id'], 'dbh' => $dbh));

		// add user activity to log: activity - 7 (UNLOCK FORM)
		$actionTxt = "unlocked Form #{$form_id} (earlier locked by {$_SESSION['earlier_locked_by']})";
		addUserActivity($dbh, $_SESSION['la_client_user_id'], $form_id, 7, $actionTxt, time(), "");
		unset($_SESSION['earlier_locked_by']);
		header("location:view.php?id={$form_id}");
		exit();
	}

	$form_id = (int) trim($_REQUEST['id']);
	$user_id = (int) trim($_REQUEST['user_id']);
	
	if(empty($form_id)){
		die("Form ID required.");
	}
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);

	$query = "select form_name, form_description from ".LA_TABLE_PREFIX."forms where form_id=?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)){		
		if(!empty($row['form_name'])){		
			$form_name = htmlspecialchars($row['form_name']);
		}else{
			$form_name = 'Untitled Form (#'.$form_id.')';
		}
		$form_description = $row['form_description'];
	}

	//get lock information

	if( $_REQUEST['isAdmin'] == 1 ) {
		$user_type = 'Admin';
		$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `user_fullname` FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1' limit 1";
		$sth = la_do_query($query,array($form_id, $user_id),$dbh);
		$lock_row = la_do_fetch_result($sth);	

		$lock_fullname = $lock_row['user_fullname'];
	} else {
		$user_type = 'Portal User';
		$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `full_name` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1'";
		$sth = la_do_query($query,array($form_id, $user_id),$dbh);
		$lock_row = la_do_fetch_result($sth);	

		$lock_fullname = $lock_row['user_fullname'];
	}	

	if( !empty($lock_row) ){
	
		$locked_id     = (int)$lock_row['locked_id'];
		//print_r($row);
		//echo "lock_date: {$row['lock_date']}";
		$lock_date     = la_short_relative_date(date("Y-m-d H:i:s", $lock_row['lockDateTime']));
	}
	$itauditmachine_path = '';
	$jquery_url = $itauditmachine_path.'js/jquery.min.js';


	//start::logic to view entry data
		if(empty($_SESSION['la_client_entity_id']))
		header("Location: client_account.php");
	//get entry information (date created/updated/ip address/resume key)
	
	$company_id = $_SESSION['la_client_entity_id'];
	
	$query  = "select * from ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id='".$company_id."'";
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	
	$totalScore = 0;
	$entry_data = array();
	$datesArr   = array();
	
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){

		while($row = la_do_fetch_result($sth)){
			$entry_data[$row['field_name']] = htmlspecialchars($row['data_value'],ENT_QUOTES);
			if($row['field_name'] == 'date_created'){
				if(!empty($row['field_score'])){
					$datesArr = explode(",", trim($row['field_score']));
				}
			}else{
				if (($row['field_score']) != "") {
					$totalScore += end(explode(",", trim($row['field_score'])));
				}
			}
		}
	} else {
		//means the entry was deleted and now we need to unlock it for portal if form exists
		header("Location: form_locked.php?id={$form_id}&unlock={$user_id}");
		die("Error. Unknown form ID.");
	}


	
	$tArr = array();
	if(count($datesArr) > 1){
		foreach($datesArr as $key => $value){
			$tArr[] = strtotime($value);
		}
	}
	
	$date_created    = date("m/d/Y H:i:s", strtotime($entry_data['date_created']));
	$date_updated    = '&nbsp;';
	
	if(count($tArr) > 0 && !empty($tArr)){
		$date_updated = date("m/d/Y H:i:s", max($tArr));
	}
	
	$ip_address   	 = $entry_data['ip_address'];
	$entry_status 	 = $entry_data['status'];
	$form_resume_key = '';//$row['resume_key'];

	$is_incomplete_entry = false;
	if($entry_status == 2){
		$is_incomplete_entry = true;
	}

	if($is_incomplete_entry && !empty($form_resume_key)){
		$form_resume_url = $la_settings['base_url']."view.php?id={$form_id}&la_resume={$form_resume_key}";
	}


	//get entry details for particular entry_id
	$param['checkbox_image'] = 'images/icons/59_blue_16.png';
	$param['company_id'] = $company_id;
	$param['page_from'] = 'view_entry';
	$entry_details = la_get_entry_details($dbh,$form_id,false,$param);
	// print_r($entry_details);
	// die();
	
	$cntStatusArr = array(0, 0, 0, 0);
	$statusElementArr = array();
	$statusCompanyId = $company_id;
	
	
	$sql_query = "SELECT `indicator`, count(`indicator`) `cnt` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = {$form_id} AND `company_id` = {$company_id} GROUP BY indicator";
	$result = la_do_query($sql_query,array(),$dbh);
	
	while($row=la_do_fetch_result($result)){
		$cntStatusArr[$row['indicator']] = $row['cnt'];
	}
	
	$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = {$form_id} AND `company_id` = {$company_id}";
	$result = la_do_query($sql_query,array(),$dbh);
	
	while($row=la_do_fetch_result($result)){
		$statusElementArr[$row['element_id']] = $row['indicator'];
	}
	

	//echo '<pre>';print_r($statusElementArr);

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
		$disable_jquery_loading = true;
		$signature_pad_init = '<script type="text/javascript" src="js/jquery.min.js"></script>'."\n".
							  '<script type="text/javascript" src="js/jquery-migrate.min.js"></script>'."\n".
							  '<!--[if lt IE 9]><script src="js/signaturepad/flashcanvas.js"></script><![endif]-->'."\n".
							  '<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>'."\n".
							  '<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>'."\n";
	}

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/entry_print.css" media="print">
<!--[if lt IE 9]><script src="js/signaturepad/flashcanvas.js"></script><![endif]-->
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
EOT;
	//end::logic to view entry data


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html {$html_class_tag} xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?=$form_name?></title>
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
<link rel="stylesheet" type="text/css" href="<?=$itauditmachine_path?>view.mobile.css" media="all" />
<!-- <link rel="stylesheet" type="text/css" href="<?=$itauditmachine_path?>css/main.css" media="all" /> -->
<link rel="stylesheet" type="text/css" href="<?=$itauditmachine_path?>js/video-js/video-js.css" />


<script type="text/javascript" src="<?=$jquery_url?>"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/marquee/jquery.marquee.min.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/video-js/video.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/video-js/youtube.min.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/video-js/vimeo.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>js/app.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>view.js"></script>
<script type="text/javascript" src="<?=$itauditmachine_path?>custom-view-js-func.js"></script>
<?=$header_data?>
<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link href="css/override.css" rel="stylesheet" type="text/css" />

<style type="text/css">
	#ve_detail_table td {
		padding: 10px;
	}
	img.status-icon {
		width: 10px;
	}
	.bootstrap-alert {
	    position: relative;
	    padding: .75rem 1.25rem;
	    margin-bottom: 1rem;
	    border: 1px solid transparent;
	    border-radius: .25rem;
	}
	.bootstrap-alert-warning {
		border: 2px solid #C2D7EF;
	    border-radius: 5px;
	    background-color: #eff6ff;
	}
	.bootstrap-alert-warning a {
		color: #3B699F;
		font-family: 'globerbold', Arial, helvetica, 'Helvetica Neue', Arial, 'Trebuchet MS', 'Lucida Grande';
    	font-weight: bold;
 	}
 	#bottom_shadow {
		width: 640px!important;
	}
</style>
</head>
<body id="main_body" class="{$container_class}">
	
	<div id="form_container" class="{$form_container_class}">
		<h1><a><?=$form_name?></a></h1>
		<form class="itauditm top_label" method="get" action="#">
			
		  	<?php if( !empty($lock_row) ){ ?>
		  		<div class="bootstrap-alert bootstrap-alert-warning" role="alert">
			  		<h3>Read Only View</h3>
					<p>This module is currently locked by <strong>[<?=$user_type?>] <?php echo htmlspecialchars($lock_fullname).' on '.$lock_date; ?>.</strong></p>
					<?php $_SESSION['earlier_locked_by'] = "[$user_type] $lock_fullname"; ?>
					<p>If you are certain <?php echo htmlspecialchars($lock_fullname); ?> is no longer working in this module, you may unlock it to continue: <a href="form_locked.php?id=<?php echo $form_id ?>&unlock=<?php echo $user_id; ?>" id="unlock_form" style="margin: 30px auto">Unlock Form</a></p>
					<p>Important: Clicking unlock will <strong>discard any unsaved changes</strong> being made by <?php echo htmlspecialchars($lock_fullname); ?>.</p>
				</div>
			<?php } ?>
			<div id="form_header" class="form_description">
				<h2 id="form_header_title"><?=$form_name?></h2>
				<p id="form_header_desc"><?=$form_description?></p>
			</div>
			<table id="ve_detail_table" width="100%" border="0" cellspacing="0" cellpadding="0">
	          <tbody>
	            <?php 
				$toggle = false;
				foreach ($entry_details as $data){ 
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

					$row_markup = '';
					$row_markup_doc = '';
					$element_id = $data['element_id'];
					
					$status_indicator = "";
					$indicator_count = 0;
					
					if(in_array($data['element_type'], array('text', 'textarea', 'file', 'radio', 'checkbox', 'select', 'signature', 'matrix'))){
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

						$status_indicator = '<img class="status-icon status-icon-action-view" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$statusCompanyId.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;" />';
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
						?>
					<script>
					  function resizeIframe_<?php echo $company_id; ?>(obj) {   obj.style.height = (obj.contentWindow.document.body.scrollHeight + 20) + 'px';	}
					</script>				
					<?php														 
						$display_data = "<iframe srcdoc='" . $data['value'] . "' style='width:100%; border:0px;' scrolling='no' onload='resizeIframe_" . $company_id . "(this)'></iframe>";
		 				//$display_data = $data["value"];
						
	 				}
					
					else {
						
						$display_data = nl2br($data['value']);
						
						
					}

	 

						$section_break_content = '<span class="la_section_title"><strong>'.nl2br($data['label']).'</strong>'.$status_indicator.'</span>'.$section_separator.'<span class="la_section_content">'.$display_data.'</span>';

						$row_markup .= "<tr {$row_style}>\n";
						$row_markup .= "<td width=\"100%\" colspan=\"2\">{$section_break_content}</td>\n";
						$row_markup .= "</tr>\n";
					}
					else if($data['element_type'] == 'signature') {
						if($data['element_size'] == 'small'){
							$canvas_height = 70;
							$line_margin_top = 50;
						}else if($data['element_size'] == 'medium'){
							$canvas_height = 130;
							$line_margin_top = 95;
						}else{
							$canvas_height = 260;
							$line_margin_top = 200;
						}

						$signature_markup = <<<EOT
						<div id="la_sigpad_{$element_id}" class="la_sig_wrapper {$data['element_size']}">
						  <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
						</div>
						<script type="text/javascript">
							$(function(){
								var sigpad_options_{$element_id} = {
								   drawOnly : true,
								   displayOnly: true,
								   bgColour: '#fff',
								   penColour: '#000',
								   output: '#element_{$element_id}',
								   lineTop: {$line_margin_top},
								   lineMargin: 10,
								   validateFields: false
								};
								var sigpad_data_{$element_id} = {$data['value']};
								$('#la_sigpad_{$element_id}').signaturePad(sigpad_options_{$element_id}).regenerate(sigpad_data_{$element_id});
							});
						</script>
EOT;

						$row_markup .= "<tr>\n";
						$row_markup .= "<td width=\"40%\" style=\"vertical-align: top\"><span><strong>{$data['label']}</strong>".$status_indicator."</span></td>\n";
						$row_markup .= "<td width=\"60%\">{$signature_markup}</td>\n";
						$row_markup .= "</tr>\n";
	                }
	                else if($data['element_type'] == 'casecade_form') {
	                	$row_markup_array = display_casecade_form_fields(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $_SESSION['la_client_entity_id'], 'page_from' => 'view_entry'));
	                    $row_markup_doc .= $row_markup_array['row_markup_doc'];
	                	$row_markup .= $row_markup_array['row_markup'];
					}else{
						$row_markup .= "<tr {$row_style}>\n";
						$row_markup .= "<td width=\"40%\"><span><strong>{$data['label']}</strong>".$status_indicator."</span></td>\n";
						$row_markup .= "<td width=\"60%\">".nl2br($data['value'])."</td>\n";
						$row_markup .= "</tr>\n";
					}

					echo $row_markup;
				} 
	            ?>
	          </tbody>
	        </table>

	    </form>
		<div id="form_footer">
			<?php
				if(empty($la_settings['disable_itauditmachine_link'])){
					echo 'Powered by ITAM, the <a href="http://www.lazarusalliance.com" target="_blank">IT Audit Machine</a>';
				}
			?>

		</div>
	</div>

	<div id="document-processing-dialog" style="display: none;text-align: center;font-size: 150%;">
		Processing Request...<br>
		<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
	</div>

	<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">
		<div id="document-preview-content" style="height: 440px;">
			<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
		</div>
	</div>

	<script type="text/javascript" src="<?=$itauditmachine_path?>js/jquery.corner.js"></script>
	<script type="text/javascript" src="<?=$itauditmachine_path?>js/jquery-ui/jquery-ui.min.js"></script>
	<?php include_once("portal-footer.php"); ?>
	</body>
</html>
	