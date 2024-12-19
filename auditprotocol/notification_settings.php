<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/users-functions.php');

	$form_id = (int) la_sanitize($_GET['id']);

	if(!empty($_POST['form_id'])){
		$form_id = (int) la_sanitize($_POST['form_id']);
	}
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	function genDatePick($select=0){
		$option = '';
		for($i=1; $i<29; $i++){
			if($select == $i){
				$selected = ' selected="selected" ';
			}else{
				$selected = '';
			}
			$option .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';	
		}
		return $option;
	}
	
	function genWeekly($select){
		$option = '';
		foreach(array(
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
			7 => 'Sunday'
		) as $key => $value){
			if($select == $key){
				$selected = ' selected="selected" ';
			}else{
				$selected = '';
			}
			$option .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';	
		}
		return $option;
	}

	function genQuaterly($select){
		$option = '';
		foreach(array(
			1 => 'January April July October',
			2 => 'February May August November',
			0 => 'March June September December'
		) as $key => $value){
			if($select == $key){
				$selected = ' selected="selected" ';
			}else{
				$selected = '';
			}
			$option .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';	
		}
		return $option;
	}
	
	function genAnnually($select){
		$option = '';
		foreach(array(
			1 => 'January',
			2 => 'February',
			3 => 'March',
			4 => 'April',
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December'
		) as $key => $value){
			if($select == $key){
				$selected = ' selected="selected" ';
			}else{
				$selected = '';
			}
			$option .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';	
		}
		return $option;
	}

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: restricted.php");
			exit;
		}
	}

	//handle form submission if there is any
	if(!empty($_POST['form_id'])){
		$form_id = la_sanitize($_POST['form_id']);
		//start handle administrative and reminder notification settings
		$queryNotification = "select * from ".LA_TABLE_PREFIX."mechanism_for_notification where form_id = ?";
		$params = array($form_id);
		$sth = la_do_query($queryNotification,$params,$dbh);
		$rowNotification = la_do_fetch_result($sth);
		$mechanism_for_notification_flag = ($_POST['mechanism_for_notification_flag']) ? la_sanitize($_POST['mechanism_for_notification_flag']) : 0;
		
		$form_id = la_sanitize($_POST['form_id']);
		$recipients = implode(', ', $_POST['recipients']);
		$additional_recipients = la_sanitize($_POST['additional_recipients']);
		$message_subject = la_sanitize($_POST['message_subject']);
		$message_body = la_sanitize($_POST['message_body']);
		$reminder_subject = la_sanitize($_POST['reminder_subject']);
		$reminder_body = la_sanitize($_POST['reminder_body']);
		$following_up_days = la_sanitize($_POST['following_up_days']);
		$frequency_type = la_sanitize($_POST['frequency_type']);

		if ($frequency_type == 1) {
			$frequency_date = strtotime(la_sanitize($_POST['frequency_date']));
			$frequency_weekly = 0;
			$frequency_date_pick = 0;
			$frequency_quaterly = 0;
			$frequency_annually = 0;
		} elseif($frequency_type == 2) {
			$frequency_date = 0;
			$frequency_weekly = 0;
			$frequency_date_pick = 0;
			$frequency_quaterly = 0;
			$frequency_annually = 0;
		} elseif($frequency_type == 3) {
			$frequency_date = 0;
			$frequency_weekly = la_sanitize($_POST['frequency_date_pick_3']);
			$frequency_date_pick = 0;
			$frequency_quaterly = 0;
			$frequency_annually = 0;
		} elseif($frequency_type == 4) {
			$frequency_date = 0;
			$frequency_weekly = 0;
			$frequency_date_pick = la_sanitize($_POST['frequency_date_pick_4']);
			$frequency_quaterly = 0;
			$frequency_annually = 0;
		} elseif($frequency_type == 5) {
			$frequency_date = 0;
			$frequency_weekly = 0;
			$frequency_date_pick = la_sanitize($_POST['frequency_date_pick_5']);
			$frequency_quaterly = la_sanitize($_POST['frequency_quaterly']);
			$frequency_annually = 0;
		} elseif($frequency_type == 6) {
			$frequency_date = 0;
			$frequency_weekly = 0;
			$frequency_date_pick = la_sanitize($_POST['frequency_date_pick_6']);
			$frequency_quaterly = 0;
			$frequency_annually = la_sanitize($_POST['frequency_annually']);
		}
		
		$form_url = $_POST['form_url'];
		
		if(!empty($rowNotification)){
			$queryupd = "update ".LA_TABLE_PREFIX."mechanism_for_notification set recipients = :recipients, additional_recipients = :additional_recipients, subject = :subject, body = :body, frequency_type = :frequency_type, frequency_date = :frequency_date, frequency_weekly = :frequency_weekly, frequency_date_pick = :frequency_date_pick, frequency_quaterly = :frequency_quaterly, frequency_annually = :frequency_annually, reminder_subject = :reminder_subject, reminder_body = :reminder_body, following_up_days = :following_up_days, mechanism_for_notification_flag = :mechanism_for_notification_flag where form_id = :form_id";
			la_do_query($queryupd,array(':recipients' => $recipients, ':additional_recipients' => $additional_recipients, ':subject' => $message_subject, ':body' => $message_body, ':frequency_type' => $frequency_type, ':frequency_date' => $frequency_date, ':frequency_weekly' => $frequency_weekly, ':frequency_date_pick' => $frequency_date_pick, ':frequency_quaterly' => $frequency_quaterly, ':frequency_annually' => $frequency_annually, ':reminder_subject' => $reminder_subject, ':reminder_body' => $reminder_body, ':following_up_days' => $following_up_days, ':form_id' => $form_id, ':mechanism_for_notification_flag' => $mechanism_for_notification_flag), $dbh);
		}else{
			$queryins = "INSERT INTO ".LA_TABLE_PREFIX."mechanism_for_notification (`id`, `form_id`, `recipients`, `additional_recipients`, `subject`, `body`, `frequency_type`, `frequency_date`, `frequency_weekly`, `frequency_date_pick`, `frequency_quaterly`, `frequency_annually`, `reminder_subject`, `reminder_body`, `following_up_days`, `mechanism_for_notification_flag`) VALUES (NULL, :form_id, :recipients, :additional_recipients, :subject, :body, :frequency_type, :frequency_date, :frequency_weekly, :frequency_date_pick, :frequency_quaterly, :frequency_annually, :reminder_subject, :reminder_body, :following_up_days, :mechanism_for_notification_flag);";
			la_do_query($queryins,array(':recipients' => $recipients, ':additional_recipients' => $additional_recipients, ':subject' => $message_subject, ':body' => $message_body, ':frequency_type' => $frequency_type, ':frequency_date' => $frequency_date, ':frequency_weekly' => $frequency_weekly, ':frequency_date_pick' => $frequency_date_pick, ':frequency_quaterly' => $frequency_quaterly, ':frequency_annually' => $frequency_annually, ':reminder_subject' => $reminder_subject, ':reminder_body' => $reminder_body, ':following_up_days' => $following_up_days, ':form_id' => $form_id, ':mechanism_for_notification_flag' => $mechanism_for_notification_flag), $dbh);
		}
		//end handle administrative and reminder notification settings

		$notification_settings = la_sanitize($_POST);
		array_walk($notification_settings, 'la_trim_value');

		//save settings for 'Send Notification Emails to My Inbox' section
		$form_input['esl_enable'] = (int) $notification_settings['esl_enable'];

		if(empty($notification_settings['esl_email_address'])){
			$form_input['esl_enable'] = 0;
		}

		$form_input['form_email'] = $notification_settings['esl_email_address'];
		
		if($notification_settings['esl_from_name'] == 'custom'){
			$form_input['esl_from_name'] = $notification_settings['esl_from_name_custom'];
		}else{
			$form_input['esl_from_name'] = $notification_settings['esl_from_name'];
		}

		if($notification_settings['esl_from_email_address'] == 'custom'){
			$form_input['esl_from_email_address'] = $notification_settings['esl_from_email_address_custom'];
		}else{
			$form_input['esl_from_email_address'] = $notification_settings['esl_from_email_address'];
		}

		if($notification_settings['esl_replyto_email_address'] == 'custom'){
			$form_input['esl_replyto_email_address'] = $notification_settings['esl_replyto_email_address_custom'];
		}else{
			$form_input['esl_replyto_email_address'] = $notification_settings['esl_replyto_email_address'];
		}

		$form_input['esl_subject'] = $notification_settings['esl_subject'];
		$form_input['esl_content'] = $notification_settings['esl_content'];
		$form_input['esl_plain_text'] = (int) $notification_settings['esl_plain_text'];
		// New
		$form_input['esl_pdf_attach'] = (int) $notification_settings['esl_pdf_attach'];
		$form_input['esl_zip_attach'] = (int) $notification_settings['esl_zip_attach'];
		$form_input['esl_bcc_email']  = $notification_settings['esl_bcc_email'];
		
		//save settings for 'Send Confirmation to User' section
		$form_input['esr_enable'] = (int) $notification_settings['esr_enable'];
		$form_input['esr_email_address'] = $notification_settings['esr_email_address'];
		
		if($notification_settings['esr_from_name'] == 'custom'){
			$form_input['esr_from_name'] = $notification_settings['esr_from_name_custom'];
		}else{
			$form_input['esr_from_name'] = $notification_settings['esr_from_name'];
		}

		if($notification_settings['esr_from_email_address'] == 'custom'){
			$form_input['esr_from_email_address'] = $notification_settings['esr_from_email_address_custom'];
		}else{
			$form_input['esr_from_email_address'] = $notification_settings['esr_from_email_address'];
		}

		if($notification_settings['esr_replyto_email_address'] == 'custom'){
			$form_input['esr_replyto_email_address'] = $notification_settings['esr_replyto_email_address_custom'];
		}else{
			$form_input['esr_replyto_email_address'] = $notification_settings['esr_replyto_email_address'];
		}

		$form_input['esr_subject'] = $notification_settings['esr_subject'];
		$form_input['esr_content'] = $notification_settings['esr_content'];
		$form_input['esr_plain_text'] = (int) $notification_settings['esr_plain_text'];
		//New
		$form_input['esr_pdf_attach'] = (int) $notification_settings['esr_pdf_attach'];
		$form_input['esr_zip_attach'] = (int) $notification_settings['esr_zip_attach'];
		$form_input['esr_bcc_email']  = $notification_settings['esr_bcc_email'];

		//save settings for 'Send Form Data to Another Website'
		$form_input['webhook_enable'] = (int) $notification_settings['webhook_enable'];
		
		la_ap_forms_update($form_id,$form_input,$dbh);

		//save into ap_webhook_options table
		$query = "delete from ".LA_TABLE_PREFIX."webhook_options where form_id = ? and rule_id = 0";
		$params = array($form_id);
		la_do_query($query,$params,$dbh);

		$query = "insert into ".LA_TABLE_PREFIX."webhook_options(
							form_id,
							rule_id,
							webhook_url,
							webhook_method,
							webhook_format,
							webhook_raw_data,
							enable_http_auth,
							http_username,
							http_password,
							enable_custom_http_headers,
							custom_http_headers) 
					 values(?,?,?,?,?,?,?,?,?,?,?)";
		
		$params = array($form_id,
						0,
						$notification_settings['webhook_url'],
						$notification_settings['webhook_method'],
						$notification_settings['webhook_format'],
						$notification_settings['webhook_raw_data'],
						(int) $notification_settings['webhook_enable_http_auth'],
						$notification_settings['webhook_http_username'],
						$notification_settings['webhook_http_password'],
						(int) $notification_settings['webhook_enable_custom_http_headers'],
						$notification_settings['webhook_custom_http_headers']);
		la_do_query($query,$params,$dbh);

		//save into ap_webhook_parameters table
		if(!empty($notification_settings['webhook_param_names'])){
			//delete previous params
			$query = "delete from ".LA_TABLE_PREFIX."webhook_parameters where form_id = ? and rule_id = 0";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//insert new params
			$webhook_param_names = explode(',', $notification_settings['webhook_param_names']);
			foreach ($webhook_param_names as $value) {
				$param_name  = $notification_settings[$value];
				$value = str_replace('name', 'value', $value);
				$param_value = $notification_settings[$value];

				$query = "insert into ".LA_TABLE_PREFIX."webhook_parameters(form_id,param_name,param_value) values(?,?,?)";
				$params = array($form_id,$param_name,$param_value);
				la_do_query($query,$params,$dbh);
			}
		}


		$_SESSION['LA_SUCCESS'] = 'Notification settings has been saved.';

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: notification_settings.php?id={$form_id}");
		exit;
	}

	$entities = getEntities($dbh);

	$queryNotification = "select * from ".LA_TABLE_PREFIX."mechanism_for_notification where form_id = ?";
	$params = array($form_id);
	$sth = la_do_query($queryNotification,$params,$dbh);
	$rowNotification = la_do_fetch_result($sth);
	
	$mechanism_for_notification_flag = 0;
	$notification_sent_flag = 0;
	$recipients = '';
	$additional_recipients ='';
	$message_subject = '';
	$message_body = '';
	$frequency_type = 0;
	$frequency_date = time();
	$frequency_weekly = 0;
	$frequency_date_pick = 0;
	$frequency_quaterly = 0;
	$frequency_annually = 0;
	$reminder_subject = '';
	$reminder_body = '';
	$following_up_days = 0;
	
	if(!empty($rowNotification)){
		$mechanism_for_notification_flag = ($rowNotification['mechanism_for_notification_flag']) ? $rowNotification['mechanism_for_notification_flag'] : 0;
		if(!empty($rowNotification['notification_sent_date'])){
			$notification_sent_flag = 1;
		}
		$recipients = $rowNotification['recipients'];
		$additional_recipients = $rowNotification['additional_recipients'];
		$message_subject = $rowNotification['subject'];
		$message_body = $rowNotification['body'];
		$frequency_type = $rowNotification['frequency_type'];
		$frequency_date = $rowNotification['frequency_date'];
		$frequency_weekly = $rowNotification['frequency_weekly'];
		$frequency_date_pick = $rowNotification['frequency_date_pick'];
		$frequency_quaterly = $rowNotification['frequency_quaterly'];
		$frequency_annually = $rowNotification['frequency_annually'];
		$reminder_subject = $rowNotification['reminder_subject'];
		$reminder_body = $rowNotification['reminder_body'];
		$following_up_days = $rowNotification['following_up_days'];
	}
	
	//get form properties
	$query 	= "select 
					form_name,
					form_email,
					esl_enable,
					esl_bcc_email,
					esl_from_name,
					esl_from_email_address,
					esl_replyto_email_address,
					esl_subject,
					esl_content,
					esl_plain_text,
					esl_pdf_attach,
					esl_zip_attach,
					esr_enable,
					esr_email_address,
					esr_bcc_email,
					esr_from_name,
					esr_from_email_address,
					esr_replyto_email_address,
					esr_subject,
					esr_content,
					esr_plain_text,
					esr_pdf_attach,
					esr_zip_attach,					
					payment_enable_merchant,
					webhook_enable 
			     from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){
		$row['form_name'] = la_trim_max_length($row['form_name'],45);

		$form_name 		= noHTML($row['form_name']);
		$form_email 	= noHTML($row['form_email']);
		$esl_bcc_email 	= noHTML($row['esl_bcc_email']); // New Added
		$esl_from_name 	= noHTML($row['esl_from_name']);
		$esl_from_email_address		= noHTML($row['esl_from_email_address']);
		$esl_replyto_email_address	= noHTML($row['esl_replyto_email_address']);
		$esl_subject 	= noHTML($row['esl_subject']);
		$esl_content 	= noHTML($row['esl_content']);
		$esl_plain_text	= noHTML($row['esl_plain_text']);
		$esl_pdf_attach	= noHTML($row['esl_pdf_attach']);
		$esl_zip_attach	= noHTML($row['esl_zip_attach']);
		$esr_email_address = noHTML($row['esr_email_address']);
		$esr_bcc_email 	= noHTML($row['esr_bcc_email']); // New Added
		$esr_from_name 	= noHTML($row['esr_from_name']);
		$esr_from_email_address		= noHTML($row['esr_from_email_address']);
		$esr_replyto_email_address	= noHTML($row['esr_replyto_email_address']);
		$esr_subject 	= noHTML($row['esr_subject']);
		$esr_content 	= noHTML($row['esr_content']);
		$esr_plain_text	= noHTML($row['esr_plain_text']);
		$esr_pdf_attach	= noHTML($row['esr_pdf_attach']);
		$esr_zip_attach	= noHTML($row['esr_zip_attach']);
		$esl_enable     = (int) $row['esl_enable'];
		$esr_enable     = (int) $row['esr_enable'];
		$payment_enable_merchant = (int) $row['payment_enable_merchant'];
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}

		$webhook_enable = (int) $row['webhook_enable'];
	}

	//get all webhook settings
	$query 	= "select 
					webhook_url,
					webhook_method,
					webhook_format,
					webhook_raw_data,
					enable_http_auth,
					http_username,
					http_password,
					enable_custom_http_headers,
					custom_http_headers
			     from 
			     	 ".LA_TABLE_PREFIX."webhook_options 
			    where 
			    	 form_id = ? and rule_id = 0";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$webhook_url						= noHTML($row['webhook_url']);
	$webhook_method 					= strtolower(noHTML($row['webhook_method']));
	$webhook_format 					= $row['webhook_format'];
	$webhook_raw_data 					= noHTML($row['webhook_raw_data']);
	$webhook_enable_http_auth 			= (int) $row['enable_http_auth'];
	$webhook_http_username 				= noHTML($row['http_username']);
	$webhook_http_password 				= noHTML($row['http_password']);
	$webhook_enable_custom_http_headers = (int) $row['enable_custom_http_headers'];
	$webhook_custom_http_headers 		= noHTML($row['custom_http_headers']);

	if(empty($webhook_method)){
		$webhook_method = 'post';
	}
	
	if(empty($webhook_format)){
		$webhook_format = 'key-value';
	}
	
	if(empty($webhook_custom_http_headers)){
		$webhook_custom_http_headers =<<<EOT
{
  "Content-Type": "text/plain",
  "User-Agent": "IT Audit Machine Webhook v{$la_settings['itauditmachine_version']}"
} 
EOT;
		$webhook_custom_http_headers = noHTML($webhook_custom_http_headers);
	}
	
	//get email fields for this form
	$query = "select 
					element_id,
					element_title 
				from 
					`".LA_TABLE_PREFIX."form_elements` 
			   where 
			   		form_id=? and element_type='email' and element_is_private=0 and element_status=1
			order by 
					element_title asc";
	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);

	$i=1;
	$email_fields = array();
	while($row = la_do_fetch_result($sth)){
		$email_fields[$i]['label'] = $row['element_title'];
		$email_fields[$i]['value'] = $row['element_id'];
		$i++;
	}
	
	$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);

	//get "from name" fields for this form, which are name fields and single line text fields
	//get email fields for this form
	$query = "select 
					element_id,
					element_title 
				from 
					`".LA_TABLE_PREFIX."form_elements` 
			   where 
			   		form_id=? and element_is_private=0 and element_status=1
			   		and element_type in('text','simple_name','simple_name_wmiddle','name','name_wmiddle')
			order by 
					element_title asc";
	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);

	$i=1;
	$name_fields = array();
	while($row = la_do_fetch_result($sth)){
		$name_fields[$i]['label'] = $row['element_title'];
		$name_fields[$i]['value'] = $row['element_id'];
		$i++;
	}

	//prepare the values for 'Send Notification Emails to My Inbox'
	
	//from name
	if(empty($esl_from_name)){
		$esl_from_name = 'IT Audit Machine';
	}

	$esl_from_name_list[0]['label'] = 'IT Audit Machine';
	$esl_from_name_list[0]['value'] = 'IT Audit Machine';
	$esl_from_name_list = array_merge($esl_from_name_list,$name_fields);
		
	$array_max_index = count($esl_from_name_list);

	$esl_from_name_list[$array_max_index]['label'] = '&#8674; Set Custom Name';
	$esl_from_name_list[$array_max_index]['value'] = 'custom';

	$esl_from_name_values = array();
	foreach ($esl_from_name_list as $value) {
		$esl_from_name_values[] = $value['value'];
	}

	if(!in_array($esl_from_name, $esl_from_name_values)){
		$esl_from_name_custom = $esl_from_name;
		$esl_from_name = 'custom';
	}

	//from email address
	if(empty($esl_from_email_address)){
		$esl_from_email_address = $la_settings['default_from_email'];
	}

	//reply-to email address
	if(empty($esl_replyto_email_address)){
		$esl_replyto_email_address = $la_settings['default_from_email'];
	}

	$esl_replyto_email_address_list[0]['label'] = "no-reply@{$domain}";
	$esl_replyto_email_address_list[0]['value'] = "no-reply@{$domain}";
	$esl_replyto_email_address_list = array_merge($esl_replyto_email_address_list,$email_fields);
		
	$array_max_index = count($esl_replyto_email_address_list);

	$esl_replyto_email_address_list[$array_max_index]['label'] = '&#8674; Set Custom Address';
	$esl_replyto_email_address_list[$array_max_index]['value'] = 'custom';

	$esl_replyto_email_address_values = array();
	foreach ($esl_replyto_email_address_list as $value) {
		$esl_replyto_email_address_values[] = $value['value'];
	}

	if(!in_array($esl_replyto_email_address, $esl_replyto_email_address_values)){
		$esl_replyto_email_address_custom = $esl_replyto_email_address;
		$esl_replyto_email_address = 'custom';
	}

	//subject
	if(empty($esl_subject)){
		$esl_subject = '{form_name} [#{entry_no}]';
	}

	//content
	if(empty($esl_content)){
		$esl_content = '{entry_data}';
	}


	//prepare the values for 'Send Confirmation Email to User'
	
	//from name
	if(empty($esr_from_name)){
		$esr_from_name = 'IT Audit Machine';
	}

	$esr_from_name_list[0]['label'] = 'IT Audit Machine';
	$esr_from_name_list[0]['value'] = 'IT Audit Machine';
	$esr_from_name_list = array_merge($esr_from_name_list,$name_fields);
		
	$array_max_index = count($esr_from_name_list);

	$esr_from_name_list[$array_max_index]['label'] = '&#8674; Set Custom Name';
	$esr_from_name_list[$array_max_index]['value'] = 'custom';

	$esr_from_name_values = array();
	foreach ($esr_from_name_list as $value) {
		$esr_from_name_values[] = $value['value'];
	}

	if(!in_array($esr_from_name, $esr_from_name_values)){
		$esr_from_name_custom = $esr_from_name;
		$esr_from_name = 'custom';
	}

	//from email address
	if(empty($esr_from_email_address)){
		$esr_from_email_address = $la_settings['default_from_email'];
	}

	//reply-to email address
	if(empty($esr_replyto_email_address)){
		$esr_replyto_email_address = $la_settings['default_from_email'];
	}

	$esr_replyto_email_address_list[0]['label'] = "no-reply@{$domain}";
	$esr_replyto_email_address_list[0]['value'] = "no-reply@{$domain}";
	$esr_replyto_email_address_list = array_merge($esr_replyto_email_address_list,$email_fields);
		
	$array_max_index = count($esr_replyto_email_address_list);

	$esr_replyto_email_address_list[$array_max_index]['label'] = '&#8674; Set Custom Address';
	$esr_replyto_email_address_list[$array_max_index]['value'] = 'custom';

	$esr_replyto_email_address_values = array();
	foreach ($esr_replyto_email_address_list as $value) {
		$esr_replyto_email_address_values[] = $value['value'];
	}

	if(!in_array($esr_replyto_email_address, $esr_replyto_email_address_values)){
		$esr_replyto_email_address_custom = $esr_replyto_email_address;
		$esr_replyto_email_address = 'custom';
	}



	//subject
	if(empty($esr_subject)){
		$esr_subject = '{form_name} - Receipt';
	}

	//content
	if(empty($esr_content)){
		$esr_content = '{entry_data}';
	}


	//get all available columns label
	$query  = "select 
					 element_id,
					 element_title,
					 element_type 
			     from
			     	 `".LA_TABLE_PREFIX."form_elements` 
			    where 
			    	 form_id=? and 
			    	 element_type != 'section' and 
			    	 element_status=1
			 order by 
			 		 element_position asc";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	
	
	$columns_label = array();
	$complex_field_columns_label = array();
	while($row = la_do_fetch_result($sth)){
		$element_title = $row['element_title'];
		$element_id    = $row['element_id'];
		$element_type  = $row['element_type']; 

		//limit the title length to 40 characters max
		if(strlen($element_title) > 40){
			$element_title = substr($element_title,0,40).'...';
		}

		$element_title = htmlspecialchars($element_title,ENT_QUOTES);
		$columns_label['element_'.$element_id] = $element_title;

		//for some field type, we need to provide more detailed template variables
		//the special field types are Name and Address
		if('simple_name' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_1'] = $element_title." (First)";
			$complex_field_columns_label['element_'.$element_id.'_2'] = $element_title." (Last)";
		}else if('simple_name_wmiddle' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_1'] = $element_title." (First)";
			$complex_field_columns_label['element_'.$element_id.'_2'] = $element_title." (Middle)";
			$complex_field_columns_label['element_'.$element_id.'_3'] = $element_title." (Last)";			
		}else if('name' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_1'] = $element_title." (Title)";
			$complex_field_columns_label['element_'.$element_id.'_2'] = $element_title." (First)";
			$complex_field_columns_label['element_'.$element_id.'_3'] = $element_title." (Last)";
			$complex_field_columns_label['element_'.$element_id.'_4'] = $element_title." (Suffix)";
		}else if('name_wmiddle' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_1'] = $element_title." (Title)";
			$complex_field_columns_label['element_'.$element_id.'_2'] = $element_title." (First)";
			$complex_field_columns_label['element_'.$element_id.'_3'] = $element_title." (Middle)";
			$complex_field_columns_label['element_'.$element_id.'_4'] = $element_title." (Last)";
			$complex_field_columns_label['element_'.$element_id.'_5'] = $element_title." (Suffix)";
		}else if('address' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_1'] = $element_title." (Street)";
			$complex_field_columns_label['element_'.$element_id.'_2'] = $element_title." (Address Line 2)";
			$complex_field_columns_label['element_'.$element_id.'_3'] = $element_title." (City)";
			$complex_field_columns_label['element_'.$element_id.'_4'] = $element_title." (State)";
			$complex_field_columns_label['element_'.$element_id.'_5'] = $element_title." (Postal/Zip Code)";
			$complex_field_columns_label['element_'.$element_id.'_6'] = $element_title." (Country)";
		}else if('date' == $element_type || 'europe_date' == $element_type){
			$complex_field_columns_label['element_'.$element_id.'_dd'] = $element_title." (DD)";
			$complex_field_columns_label['element_'.$element_id.'_mm'] = $element_title." (MM)";
			$complex_field_columns_label['element_'.$element_id.'_yyyy'] = $element_title." (YYYY)";
		}
	}

	//get webhook parameters
	//on this page 'rule_id' is always 0
	//non zero rule_id is being used for webhook logic
	$webhook_parameters = array();
	$query = "select param_name,param_value from ".LA_TABLE_PREFIX."webhook_parameters where form_id = ? and rule_id = 0 order by awp_id asc";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$i=0;
	while($row = la_do_fetch_result($sth)){
		$webhook_parameters[$i]['param_name'] = noHTML(trim($row['param_name']));
		$webhook_parameters[$i]['param_value'] = noHTML($row['param_value']);
		$i++;
	}

	//if there is no webhook parameters being defined, provide with the default parameters
	if(empty($webhook_parameters)){
		$webhook_parameters[0]['param_name']  = 'FormID';
		$webhook_parameters[0]['param_value'] = '{form_id}';

		$webhook_parameters[1]['param_name']  = 'EntryNumber';
		$webhook_parameters[1]['param_value'] = '{entry_no}';

		$webhook_parameters[2]['param_name']  = 'DateCreated';
		$webhook_parameters[2]['param_value'] = '{date_created}';

		$webhook_parameters[3]['param_name']  = 'IpAddress';
		$webhook_parameters[3]['param_value'] = '{ip_address}';
	}

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
EOT;

	$current_nav_tab = 'manage_forms';
	require('includes/header.php'); 
	
?>
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<style>
#div_mechanism_for_notification_flag .multi-selection-content input[type="radio"], #div_mechanism_for_notification_flag .multi-selection-content input[type="checkbox"] {
	margin-left: 21px;
	float: left;
}
</style>
<div id="content" class="full">
  <div class="post notification_settings">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> Notification Settings</h2>
          <p>Configure email or web notification options for your form</p>
        </div>
        <div style="float: right;margin-right: 5px"> <a href="#" id="button_save_notification" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </a> </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <form id="ns_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>" autocomplete = "off">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        </div>
        <ul id="ns_main_list">
          <li>
            <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
              <div class="ns_box_title">
                <input type="checkbox" value="1" class="checkbox" id="esl_enable" name="esl_enable" <?php if(!empty($esl_enable)){ echo 'checked="checked"';} ?>>
                <label for="esl_enable" class="choice">Send Notification Emails to My Inbox</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option to send all successful form submission to your email address (all the data will still be accessible from your itauditmachine admin panel as well)."/> </div>
              <div class="ns_box_email" <?php if(empty($esl_enable)){ echo 'style="display: none"'; } ?>>
                <label class="description" for="esl_email_address">Your Email Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can enter multiple email addresses. Simply separate them with semicolon."/></label>
                <input id="esl_email_address" name="esl_email_address" class="element text medium" value="<?php echo $form_email; ?>" type="text">
              </div>
              <div class="ns_box_more" style="display: none">
				<!--  New Section Added  -->         
                <label class="description" for="esl_bcc_email">Bcc Email Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can enter multiple email addresses. Simply separate them with semicolon."/></label>
                <input id="esl_bcc_email" name="esl_bcc_email" class="element text large" value="<?php echo $esl_bcc_email; ?>" type="text">
              	<!--  *****************  -->    
                <label class="description" for="esl_from_name">From Name <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If your form has 'Name' or 'Single Line Text' field type, it will be available here and you can choose it as the 'From Name' of the email. Or you can set your own custom 'From Name'"/></label>
                <select name="esl_from_name" id="esl_from_name" class="element select medium">
                  <?php
											foreach ($esl_from_name_list as $data){
												if($esl_from_name == $data['value']){
													$selected = 'selected="selected"';
												}else{
													$selected = '';
												}

												echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
											}
										?>
                </select>
                <span id="esl_from_name_custom_span" <?php if(empty($esl_from_name_custom)){ echo 'style="display: none"'; } ?>>&#8674;
                <input id="esl_from_name_custom" name="esl_from_name_custom" class="element text" style="width: 44%" value="<?php echo $esl_from_name_custom; ?>" type="text">
                </span>
                <label class="description" for="esl_replyto_email_address">Reply-To Email <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If your form has 'Email' field type, it will be available here and you can choose it as the reply-to address. Or you can set your own custom reply-to address."/></label>
                <select name="esl_replyto_email_address" id="esl_replyto_email_address" class="element select medium">
                  <?php
											foreach ($esl_replyto_email_address_list as $data){
												if($esl_replyto_email_address == $data['value']){
													$selected = 'selected="selected"';
												}else{
													$selected = '';
												}

												echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
											}
										?>
                </select>
                <span id="esl_replyto_email_address_custom_span" <?php if(empty($esl_replyto_email_address_custom)){ echo 'style="display: none"'; } ?>>&#8674;
                <input id="esl_replyto_email_address_custom" name="esl_replyto_email_address_custom" class="element text" style="width: 44%" value="<?php echo $esl_replyto_email_address_custom; ?>" type="text">
                </span>
                <label class="description" for="esl_from_email_address">From Email <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="To ensure delivery of your notification emails, we STRONGLY recommend to use email from the same domain as IT Audit Machine located.<br/> e.g. no-reply@<?php echo $domain; ?>"/></label>
                <input id="esl_from_email_address" name="esl_from_email_address" class="element text medium" value="<?php echo $esl_from_email_address; ?>" type="text">
                <label class="description" for="esl_subject">Email Subject</label>
                <input id="esl_subject" name="esl_subject" class="element text large" value="<?php echo $esl_subject; ?>" type="text">
                <label class="description" for="esl_content">Email Content <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This field accept HTML codes."/></label>
                <textarea class="element textarea medium" name="esl_content" id="esl_content"><?php echo $esl_content; ?></textarea>
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($esl_plain_text)){ echo 'checked="checked"'; } ?> id="esl_plain_text" name="esl_plain_text" style="margin-left: 0px">
                <label for="esl_plain_text" >Send Module Data In Plain Text Email Format</label>
                </span> 
                <!--  New Section Added  -->    
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($esl_pdf_attach)){ echo 'checked="checked"'; } ?> id="esl_pdf_attach" name="esl_pdf_attach" style="margin-left: 0px">
                <label for="esl_pdf_attach" >Send Module Data In PDF Email Attachment</label>
                </span>
                <!--  *****************  -->  
				<!--  New Section Added  -->    
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($esl_zip_attach)){ echo 'checked="checked"'; } ?> id="esl_zip_attach" name="esl_zip_attach" style="margin-left: 0px">
                <label for="esl_zip_attach" >Send Module Report Data In Zip Email Attachment</label>
                </span>
                <!--  *****************  -->
                <span class="ns_temp_vars"><img style="vertical-align: middle" src="images/icons/70_blue.png"> You can insert <a href="#" class="tempvar_link">template variables</a> into the email template.</span> </div>
              <div class="ns_box_more_switcher" <?php if(empty($esl_enable)){ echo 'style="display: none"'; } ?>> <a id="more_option_myinbox" href="#">more options</a> <img id="myinbox_img_arrow" style="vertical-align: top;margin-left: 3px" src="images/icons/38_rightblue_16.png"> </div>
            </div>
          </li>
          <li>&nbsp;</li>
          <li>
            <div id="ns_box_user_email" class="ns_box_main gradient_blue">
              <div class="ns_box_title">
                <input type="checkbox" value="1" class="checkbox" id="esr_enable" name="esr_enable" <?php if(!empty($esr_enable) && !empty($esr_email_address)){ echo 'checked="checked"';} ?>>
                <label for="esr_enable" class="choice">Send Confirmation Email to User</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option to send confirmation email to the user after successful form submission. Your form need to have an email field to use this option."/> </div>
              <div class="ns_box_email" <?php if(empty($esr_enable)){ echo 'style="display: none"'; } ?>>
                <?php if(!empty($email_fields)){ ?>
                <label class="description" for="esr_email_address">User Email Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Confirmation email will be sent to the email address being entered by the user to this field."/></label>
                <select name="esr_email_address" id="esr_email_address" class="element select medium">
                  <?php
											foreach ($email_fields as $data){
												if($esr_email_address == $data['value']){
													$selected = 'selected="selected"';
												}else{
													$selected = '';
												}

												echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
											}
										?>
                </select>
                <?php }else{ ?>
                <label class="description" style="color: #80b638">No email field available! <br />
                  You need to add an email field into your form.</label>
                <?php } ?>
              </div>
              <div class="ns_box_more" style="display: none">
				<!--  New Section Added  -->         
                <label class="description" for="esr_bcc_email">Bcc Email Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can enter multiple email addresses. Simply separate them with semicolon."/></label>
                <input id="esr_bcc_email" name="esr_bcc_email" class="element text large" value="<?php echo $esr_bcc_email; ?>" type="text">
              	<!--  *****************  -->    
                <label class="description" for="esr_from_name">From Name <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If your form has 'Name' or 'Single Line Text' field type, it will be available here and you can choose it as the 'From Name' of the email. Or you can set your own custom 'From Name'"/></label>
                <select name="esr_from_name" id="esr_from_name" class="element select medium">
                  <?php
											foreach ($esr_from_name_list as $data){
												if($esr_from_name == $data['value']){
													$selected = 'selected="selected"';
												}else{
													$selected = '';
												}

												echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
											}
										?>
                </select>
                <span id="esr_from_name_custom_span" <?php if(empty($esr_from_name_custom)){ echo 'style="display: none"'; } ?>>&#8674;
                <input id="esr_from_name_custom" name="esr_from_name_custom" class="element text" style="width: 44%" value="<?php echo $esr_from_name_custom; ?>" type="text">
                </span>
                <label class="description" for="esr_replyto_email_address">Reply-To Email <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If your form has 'Email' field type, it will be available here and you can choose it as the reply-to address. Or you can set your own custom reply-to address."/></label>
                <select name="esr_replyto_email_address" id="esr_replyto_email_address" class="element select medium">
                  <?php
											foreach ($esr_replyto_email_address_list as $data){
												if($esr_replyto_email_address == $data['value']){
													$selected = 'selected="selected"';
												}else{
													$selected = '';
												}

												echo "<option value=\"{$data['value']}\" {$selected}>{$data['label']}</option>";
											}
										?>
                </select>
                <span id="esr_replyto_email_address_custom_span" <?php if(empty($esr_replyto_email_address_custom)){ echo 'style="display: none"'; } ?>>&#8674;
                <input id="esr_replyto_email_address_custom" name="esr_replyto_email_address_custom" class="element text" style="width: 44%" value="<?php echo $esr_replyto_email_address_custom; ?>" type="text">
                </span>
                <label class="description" for="esr_from_email_address">From Email <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="To ensure delivery of your notification emails, we STRONGLY recommend to use email from the same domain as IT Audit Machine located.<br/> e.g. no-reply@<?php echo $domain; ?>"/></label>
                <input id="esr_from_email_address" name="esr_from_email_address" class="element text medium" value="<?php echo $esr_from_email_address; ?>" type="text">
                <label class="description" for="esr_subject">Email Subject</label>
                <input id="esr_subject" name="esr_subject" class="element text large" value="<?php echo $esr_subject; ?>" type="text">
                <label class="description" for="esr_content">Email Content <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This field accept HTML codes."/></label>
                <textarea class="element textarea medium" name="esr_content" id="esl_content"><?php echo $esr_content; ?></textarea>
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" <?php if(!empty($esr_plain_text)){ echo 'checked="checked"'; } ?> class="checkbox" id="esr_plain_text" name="esr_plain_text" style="margin-left: 0px">
                <label for="esr_plain_text" >Send Module Data In Plain Text Email Format</label>
                </span> 
                
                <!--  New Section Added  -->    
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($esr_pdf_attach)){ echo 'checked="checked"'; } ?> id="esr_pdf_attach" name="esr_pdf_attach" style="margin-left: 0px">
                <label for="esr_pdf_attach" >Send Module Data In PDF Email Attachment</label>
                </span>
                <!--  *****************  -->

				<!--  New Section Added  -->    
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($esr_zip_attach)){ echo 'checked="checked"'; } ?> id="esr_zip_attach" name="esr_zip_attach" style="margin-left: 0px">
                <label for="esr_zip_attach" >Send Module Report Data In Zip Email Attachment</label>
                </span>
                <!--  *****************  -->
                
                <span class="ns_temp_vars"><img style="vertical-align: middle" src="images/icons/70_red2.png"> You can insert <a href="#" class="tempvar_link">template variables</a> into the email template.</span> </div>
              <?php if(!empty($email_fields)){ ?>
              <div class="ns_box_more_switcher" <?php if(empty($esr_enable)){ echo 'style="display: none"'; } ?>> <a id="more_option_confirmation_email" href="#">more options</a> <img id="confirmation_email_img_arrow" style="vertical-align: top;margin-left: 3px" src="images/icons/38_rightred_16.png"> </div>
              <?php } ?>
            </div>
          </li>
          <li>&nbsp;</li>
          <li>
            <div id="ns_box_url_notification" class="ns_box_main gradient_blue">
              <div class="ns_box_title">
                <input type="checkbox" value="1" class="checkbox" id="webhook_enable" name="webhook_enable" <?php if(!empty($webhook_enable)){ echo 'checked="checked"';} ?>>
                <label for="webhook_enable" class="choice">Send Form Data to Another Website</label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can enable this option to send form data to any other custom URL. Useful to integrate your form with many other web applications, such as Continuum GRC, MailChimp, Salesforce, Basecamp, etc."/> </div>
              <div class="ns_box_content" <?php if(!empty($webhook_enable)){ echo 'style="display: block"'; } ?>>
                <label class="description" for="webhook_url">Website URL</label>
                <input id="webhook_url" name="webhook_url" class="element text large" value="<?php echo $webhook_url; ?>" type="text">
                <label class="description" for="webhook_method">HTTP Method</label>
                <select name="webhook_method" id="webhook_method" class="element select medium">
                  <option <?php if($webhook_method == 'post'){ echo 'selected="selected"'; }; ?> value="post">HTTP POST (recommended)</option>
                  <option <?php if($webhook_method == 'get'){ echo 'selected="selected"'; }; ?> value="get">HTTP GET</option>
                  <option <?php if($webhook_method == 'put'){ echo 'selected="selected"'; }; ?> value="put">HTTP PUT</option>
                </select>
                <span style="display: block;margin-top: 15px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($webhook_enable_http_auth)){ echo 'checked="checked"'; } ?> id="webhook_enable_http_auth" name="webhook_enable_http_auth" style="margin-left: 0px">
                <label for="webhook_enable_http_auth" >Use HTTP Authentication</label>
                </span>
                <div id="ns_http_auth_div" <?php if(empty($webhook_enable_http_auth)){ echo 'style="display: none"'; } ?>>
                  <label class="description" for="webhook_http_username" style="margin-top: 10px">HTTP User Name</label>
                  <input id="webhook_http_username" name="webhook_http_username" class="element text" style="width: 93%" value="<?php echo $webhook_http_username; ?>" type="text">
                  <label class="description" for="webhook_http_password" style="margin-top: 10px">HTTP Password</label>
                  <input id="webhook_http_password" name="webhook_http_password" class="element text" style="width: 93%" value="<?php echo $webhook_http_password; ?>" type="text">
                </div>
                <span style="display: block;margin-top: 10px">
                <input type="checkbox" value="1" class="checkbox" <?php if(!empty($webhook_enable_custom_http_headers)){ echo 'checked="checked"'; } ?> id="webhook_enable_custom_http_headers" name="webhook_enable_custom_http_headers" style="margin-left: 0px">
                <label for="webhook_enable_custom_http_headers">Use Custom HTTP Headers</label>
                </span>
                <div id="ns_http_header_div" <?php if(empty($webhook_enable_custom_http_headers)){ echo 'style="display: none"'; } ?>>
                  <label class="description" style="margin-top: 10px" for="webhook_custom_http_headers">HTTP Headers <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="A JSON object of all HTTP Headers you need to send."/></label>
                  <textarea class="element textarea small" name="webhook_custom_http_headers" id="webhook_custom_http_headers"><?php echo $webhook_custom_http_headers; ?></textarea>
                </div>
                <label class="description">Data Format </label>
                <div> <span>
                  <input id="webhook_data_format_key_value"  name="webhook_format" class="element radio" type="radio" value="key-value" <?php if($webhook_format == 'key-value'){ echo 'checked="checked"'; } ?> />
                  <label for="webhook_data_format_key_value">Send Key-Value Pairs</label>
                  </span> <span style="margin-left: 20px">
                  <input id="webhook_data_format_raw"  name="webhook_format" class="element radio" type="radio" value="raw" <?php if($webhook_format == 'raw'){ echo 'checked="checked"'; } ?> />
                  <label for="webhook_data_format_raw">Send Raw Data</label>
                  </span> </div>
                <div id="ns_webhook_raw_div" <?php if($webhook_format == 'key-value'){ echo 'style="display: none"'; } ?>>
                  <label class="description" style="border-bottom: 1px dashed #97BF6B;padding-bottom: 10px;margin-bottom: 15px">Raw Data <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter any content you would like to send here. You can use any data format (e.g. JSON, XML or raw text). Just make sure to set the proper 'Content-Type' HTTP header as well."/></label>
                  <textarea class="element textarea large" name="webhook_raw_data" id="webhook_raw_data"><?php echo $webhook_raw_data; ?></textarea>
                </div>
                <label id="ns_webhook_parameters_label" <?php if($webhook_format == 'raw'){ echo 'style="display: none"'; } ?> class="description" style="border-bottom: 1px dashed #97BF6B;padding-bottom: 10px">Parameters <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Name -> You can type any parameter name you prefer here. <br/><br/>Value -> Should be the template variable of the field you would like to send. Such as {element_1} or {element_2} etc. You can also enter any static value."/></label>
                <ul id="ns_webhook_parameters" <?php if($webhook_format == 'raw'){ echo 'style="display: none"'; } ?>>
                  <li class="ns_url_column_label">
                    <div class="ns_param_name">
                      <label class="description" for="esl_from_name" style="margin-top: 0px">Name</label>
                    </div>
                    <div class="ns_param_spacer" style="visibility: hidden"> &#8674; </div>
                    <div class="ns_param_value">
                      <label class="description" for="esl_from_name" style="margin-top: 0px">Value</label>
                    </div>
                  </li>
                  <?php 
											$i=1;
											foreach ($webhook_parameters as $value) { 
										?>
                  <li class="ns_url_params">
                    <div class="ns_param_name">
                      <input id="webhookname_<?php echo $i; ?>" name="webhookname_<?php echo $i; ?>" class="element text" style="width: 100%" value="<?php echo $value['param_name']; ?>" type="text">
                    </div>
                    <div class="ns_param_spacer"> &#8674; </div>
                    <div class="ns_param_value">
                      <input id="webhookvalue_<?php echo $i; ?>" name="webhookvalue_<?php echo $i; ?>" class="element text" style="width: 100%" value="<?php echo $value['param_value']; ?>" type="text">
                    </div>
                    <div class="ns_param_control"> <a class="a_delete_webhook_param" name="deletewebhookparam_<?php echo $i; ?>" id="deletewebhookparam_<?php echo $i; ?>" href="#"><img src="images/icons/51_green_16.png"></a> </div>
                  </li>
                  <?php $i++;} ?>
                  <li class="ns_url_add_param" style="padding-bottom: 0px;text-align: right; border-top: 1px dashed #97BF6B;padding-top: 10px"> <a class="a_add_condition" id="ns_add_webhook_param" href="#"><img src="images/icons/49_green_16.png"></a> </li>
                </ul>
                <span class="ns_temp_vars"><img src="images/icons/70_green_white.png" style="vertical-align: middle"> You can insert <a class="tempvar_link" href="#">template variables</a> into parameter values or data.</span> </div>
            </div>
          </li>
          <li>&nbsp;</li>
          <li>
          	<div id="div_mechanism_for_notification_flag" class="ns_box_main gradient_blue">
          		<div class="ns_box_title">
	                <input type="checkbox" value="1" class="checkbox" id="mechanism_for_notification_flag" name="mechanism_for_notification_flag" <?php if($mechanism_for_notification_flag){ echo 'checked="checked"';} ?>>
	                <label for="mechanism_for_notification_flag" class="choice">Send Administrative Notices and Reminder Notices to User</label>
	                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option to send administrative notices and reminder notices to users based on a schedule."/>
	            </div>
	            <div <?php if($mechanism_for_notification_flag){ echo '';}else{echo ' style="display:none;"';} ?> id="notification-form">
	            	<?php if($notification_sent_flag == 1){
	            		echo '<div class="ns_box_content">Notices have already been sent.</div>';
	            	}
	            	?>
	                <div class="ns_box_content">
	                  <label class="description" for="recipient">Recipients <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select the entities that you are going to send notifications."/></label>
	                  <?php
	                  if(count($entities) == 0){
	                  	echo "Please add entities.";
	                  } else {
	                  ?>
	                  <select multiple id="recipients" class="element" name="recipients[]" style="width: 200px; height: 100px;">
	                  	<?php
	                  		$recipients_array = explode(', ', $recipients);
	                  		foreach($entities as $entity) {
	                  			if(in_array($entity['entity_id'], $recipients_array)){
	                  	?>
	                  			<option selected value="<?php echo $entity['entity_id']; ?>"><?php echo $entity['entity_name']; ?></option>
		                  	<?php
		                  		} else {	
		                  	?>
	                  		<option value="<?php echo $entity['entity_id']; ?>"><?php echo $entity['entity_name']; ?></option>
	                  	<?php
	                  			}
	                  		}
	                  	?>
	                  </select>
	                  <?php
	              	  }
	              	  ?>
	                </div>
	                <div class="row">
	                	<div class="col-md-6">
	                		<div class="ns_box_content">
								<label class="description" for="recipient">Additional Recipients <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter the additional recipient's or group's email addresses. Simply separate them with semicolon or comma."/></label>
								<input id="additional_recipients" type="text" name="additional_recipients" class="element text large" value="<?php echo $additional_recipients;  ?>">
		                	</div>
	                	</div>	                	
	                </div>	                
	            	<div class="row">
		          		<div class="col-md-6">
			                <div class="ns_box_content">
			                  <label class="description" for="message_subject">Initial Message Subject <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Include a meaningful subject line to your recipients just like you would a regular email message."/></label>
			                  <input id="message_subject" name="message_subject" class="element text large" value="<?php echo $message_subject; ?>" type="text">
			                </div>
			                <div class="ns_box_content">
			                  <label class="description" for="message_body">Initial Message Body <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Include a meaningful message to your recipients in the message body just like you would a regular email message."/></label>
			                  <textarea cols="47" rows="5" style="width:99%;" name="message_body" id="message_body"><?php echo $message_body; ?></textarea>
			                </div>
			                <div class="ns_box_content">
			                  <label class="description">Initial Notification Frequency <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The Notification Frequency section allows you to determine the schedule your administrative notice will be sent."/></label>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_1" class="checkbox frequency_type" value="1" <?php echo ($frequency_type == 1) ? ' checked="checked"' : (($frequency_type == 0) ? ' checked="checked"' : ''); ?>>
			                  <label for="frequency_type_1" class="description inline">One Time <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The One Time option is used when you want to send an Administrative Notice to your recipients just once. You will select the date for the action to occur." > </label>
			                </div>
			                <div class="multi-selection-content" id="frequency_type_1_div" <?php echo ($frequency_type == 1) ? '' : (($frequency_type == 0) ? '' : ' style="display:none;" '); ?>>
			                  <div class="ns-box-date">
			                    <label class="description">Select Date: </label>
			                    <span>
			                    <input style="width:20px;" type="text" value="<?php echo ($frequency_date > 0) ? date("m", $frequency_date) : date("m"); ?>" maxlength="2" size="2" class="element text" id="mm">
			                    </span>/ <span>
			                    <input style="width:20px;" type="text" value="<?php echo ($frequency_date > 0) ? date("d", $frequency_date) : date("d"); ?>" maxlength="2" size="2" class="element text" id="dd">
			                    </span>/ <span>
			                    <input style="width:35px;" type="text" value="<?php echo ($frequency_date > 0) ? date("Y", $frequency_date) : date("Y"); ?>" maxlength="4" size="4" class="element text" id="yyyy">
			                    </span>
			                    <input type="hidden" id="frequency_date" name="frequency_date" value="<?php echo ($frequency_date > 0) ? date("m/d/Y", $frequency_date) : date("m/d/Y"); ?>">
			                    <span style="display: none;"> &nbsp;<img id="cal_img_5" class="datepicker" src="images/calendar.gif" alt="Pick a date." /></span> </div>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_2" class="checkbox frequency_type" value="2" <?php echo ($frequency_type == 2) ? ' checked="checked"' : ''; ?>>
			                  <label class="description inline">Daily <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The Daily option is used when you want to send an Administrative Notice to your recipients every day."> </label>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_3" class="checkbox frequency_type" value="3" <?php echo ($frequency_type == 3) ? ' checked="checked"' : ''; ?>>
			                  <label class="description inline">Weekly <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The Weekly option is used when you want to send an Administrative Notice to your recipients once a week. You will select the day of the week for the action to occur."> </label>
			                </div>
			                <div class="multi-selection-content" id="frequency_type_3_div" <?php echo ($frequency_type == 3) ? '' : 'style="display:none;"'; ?>>
			                  <div class="ns-box-date">
			                    <label class="description">Select Day of The Week: </label>
			                    <select name="frequency_date_pick_3" id="frequency_date_pick_3">
			                      <?php echo genWeekly($frequency_weekly); ?>
			                    </select>
			                  </div>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_4" class="checkbox frequency_type" value="4" <?php echo ($frequency_type == 4) ? ' checked="checked"' : ''; ?>>
			                  <label class="description inline">Monthly <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The Monthly option is used when you want to send an Administrative Notice to your recipients once a month. You will select the date for the action to occur."> </label>
			                </div>
			                <div class="multi-selection-content" id="frequency_type_4_div" <?php echo ($frequency_type == 4) ? '' : 'style="display:none;"'; ?>>
			                  <div class="ns-box-date">
			                    <label class="description">Select Date: </label>
			                    <select name="frequency_date_pick_4" id="frequency_date_pick_4">
			                      <?php echo genDatePick($frequency_date_pick); ?>
			                    </select>
			                  </div>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_5" class="checkbox frequency_type" value="5" <?php echo ($frequency_type == 5) ? ' checked="checked"' : ''; ?>>
			                  <label class="description inline">Quaterly <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The Quarterly option is used when you want to send an Administrative Notice to your recipients every quarter. You will select the start month and date for the action to occur."> </label>
			                </div>
			                <div class="multi-selection-content" id="frequency_type_5_div" <?php echo ($frequency_type == 5) ? '' : 'style="display:none;"'; ?>>
			                  <div class="ns-box-date">
			                    <label class="description">Select Date: </label>
			                    <select name="frequency_date_pick_5" id="frequency_date_pick_5">
			                      <?php echo genDatePick($frequency_date_pick); ?>
			                    </select>
			                    <select name="frequency_quaterly" id="frequency_quaterly">
			                      <?php echo genQuaterly($frequency_quaterly); ?>
			                    </select>
			                  </div>
			                </div>
			                <div class="multi-selection-content">
			                  <input type="radio" name="frequency_type" id="frequency_type_6" class="checkbox frequency_type" value="6" <?php echo ($frequency_type == 6) ? ' checked="checked"' : ''; ?>>
			                  <label for="frequency_type" class="description inline">Annually <img style="vertical-align: bottom; padding-bottom: 3px" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="The Annually option is used when you want to send an Administrative Notice to your recipients just once a year. You will select the date for the action to occur."> </label>
			                </div>
			                <div class="multi-selection-content" id="frequency_type_6_div" <?php echo ($frequency_type == 6) ? '' : 'style="display:none;"'; ?>>
			                  <div class="ns-box-date">
			                    <label class="description">Select Date: </label>
			                    <select name="frequency_date_pick_6" id="frequency_date_pick_6">
			                      <?php echo genDatePick($frequency_date_pick); ?>
			                    </select>
			                    <select name="frequency_annually" id="frequency_annually">
			                      <?php echo genAnnually($frequency_annually); ?>
			                    </select>
			                  </div>
			                </div>
		          		</div>
		          		<div class="col-md-6">
			                <div class="ns_box_content">
			                  <label class="description" for="reminder_subject">Reminder Message Subject <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Include a meaningful subject line to your recipients just like you would a regular email message."/></label>
			                  <input id="reminder_subject" name="reminder_subject" class="element text large" value="<?php echo $reminder_subject; ?>" type="text">
			                </div>
			                <div class="ns_box_content">
			                  <label class="description" for="reminder_body">Reminder Message Body <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Include a meaningful message to your recipients in the message body just like you would a regular email message."/></label>
			                  <textarea cols="47" rows="5" style="width:99%;" name="reminder_body" id="reminder_body"><?php echo $reminder_body; ?></textarea>
			                </div>
			                <div class="ns_box_content">
			                  <label class="description" for="following_up_days">Following Up Days After The Initial Notification <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter the number of days the system will wait before sending out a follow-up notification." /></label>
			                  <input id="following_up_days" name="following_up_days" class="element text small" value="<?php echo $following_up_days; ?>" type="text">
			                </div>
		          		</div>
		          	</div>
	            </div>          		
          	</div>
          </li>
        </ul>
        <input type="hidden" id="form_id" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" id="webhook_param_names" name="webhook_param_names" value="">
      </form>
      <div id="dialog-template-variable" title="Template Variable Lookup" class="buttons" style="display: none">
        <form id="dialog-template-variable-form" class="dialog-form" style="padding-left: 10px;padding-bottom: 10px">
          <ul>
            <li>
              <div>
                <div style="margin: 0px 0 10px 0"> Template variable &#8674; <span id="tempvar_value">{form_name}</span> </div>
                <select class="select full" id="dialog-template-variable-input" style="margin-bottom: 10px" name="dialog-template-variable-input">
                  <optgroup label="Form Fields">
                  <?php 
												foreach ($columns_label as $element_name => $element_label) {
													echo "<option value=\"{$element_name}\">{$element_label}</option>\n";
												}
											?>
                  </optgroup>
                  <?php
												if(!empty($complex_field_columns_label)){
													echo "<optgroup label=\"Complex Form Fields (Detailed)\">";
													foreach ($complex_field_columns_label as $element_name => $element_label) {
														echo "<option value=\"{$element_name}\">{$element_label}</option>\n";
													}
													echo "</optgroup>";
												}
											?>
                  <optgroup label="Entry Information">
                  <option value="entry_no">Entry No.</option>
                  <option value="date_created">Date Created</option>
                  <option value="ip_address">IP Address</option>
                  <option value="form_id">Form ID</option>
                  <option value="form_name" selected="selected">Form Name</option>
                  <option value="entry_data">Complete Entry</option>
                  </optgroup>
                  <?php if(!empty($payment_enable_merchant)){ ?>
                  <optgroup label="Payment Information">
                  <option value="total_amount">Total Amount</option>
                  <option value="payment_status">Payment Status</option>
                  <option value="payment_id">Payment ID</option>
                  <option value="payment_date">Payment Date</option>
                  <option value="payment_fullname">Full Name</option>
                  <option value="billing_address">Billing Address</option>
                  <option value="shipping_address">Shipping Address</option>
                  </optgroup>
                  <?php } ?>
                </select>
                <div>
                  <div id="tempvar_help_content" style="display: none">
                    <h5>What is template variable?</h5>
                    <p>A template variable is a special identifier that is automatically replaced with data typed in by a user.</p>
                    <h5>How can I use it?</h5>
                    <p>Simply copy the variable name (including curly braces) into your email template.</p>
                    <h5>Where can I use it?</h5>
                    <p>You can insert template variable into Email Subject and Email Content.</p>
                  </div>
                  <div id="tempvar_help_trigger" style="overflow: auto"><a href="">more info</a></div>
                </div>
              </div>
            </li>
          </ul>
        </form>
      </div>
      <div id="dialog-warning" title="Error Title" class="buttons" style="display: none"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Warning" />
        <p id="dialog-warning-msg"> Error </p>
      </div>
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/notification_settings.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.ext.js"></script>
EOT;

	require('includes/footer.php'); 
?>
<script type="text/javascript">
$(document).ready(function() {
	//dont allow comma in esl_email_address and bcc_email_address
	$('#esl_email_address').keyup(function() {
		$('.ns_box_email').find('.esl_email_address_error').remove();
		var esl_email_address = $(this).val();
		if( esl_email_address ) {
			if (esl_email_address.indexOf(",") >= 0) {
				$(this).after('<span class="au_error_span esl_email_address_error">Use semicolon instead of comma to separate email addresses.</span>');
				$(this).val(esl_email_address.replace(',', ';'));
			} else {
				$('.ns_box_email').find('.esl_email_address_error').remove();
				
			}
		}
	});

	$('#esl_bcc_email').keyup(function() {
		$('.ns_box_more').find('.esl_email_address_error').remove();
		var esl_bcc_email = $(this).val();
		if( esl_bcc_email ) {
			if (esl_bcc_email.indexOf(",") >= 0) {
				$(this).after('<span class="au_error_span esl_email_address_error">Use semicolon instead of comma to separate email addresses.</span>');
				$(this).val(esl_bcc_email.replace(',', ';'));
			} else {
				$('.ns_box_email').find('.esl_email_address_error').remove();
				
			}
		}
	});

	function select_date(dates){
		var _dateSelected = '';
		var _mm = '';
		var _dd = '';
		var _yyyy = '';
		if(dates.length){
			_dateSelected = (dates[0].getMonth() + 1) + '/' + dates[0].getDate() + '/' + dates[0].getFullYear();
			_mm = (dates[0].getMonth() + 1);
			_dd = dates[0].getDate();
			_yyyy = dates[0].getFullYear();
		}
		$('input#frequency_date').val(_dateSelected);
		$('input#mm').val(_mm);
		$('input#dd').val(_dd);
		$('input#yyyy').val(_yyyy);
	}
	
	$('#frequency_date').datepick({ 
		onSelect: select_date,
		showTrigger: '#cal_img_5'
	});
	
	$('input.frequency_type').click(function(){
		if ($(this).val() == 1) {
			$('#frequency_type_1_div').slideDown();
			$('#frequency_type_3_div').slideUp();
			$('#frequency_type_4_div').slideUp();
			$('#frequency_type_5_div').slideUp();
			$('#frequency_type_6_div').slideUp();
		} else if($(this).val() == 2) {
			$('#frequency_type_1_div').slideUp();
			$('#frequency_type_3_div').slideUp();
			$('#frequency_type_4_div').slideUp();
			$('#frequency_type_5_div').slideUp();
			$('#frequency_type_6_div').slideUp();
		} else if($(this).val() == 3) {
			$('#frequency_type_1_div').slideUp();
			$('#frequency_type_3_div').slideDown();
			$('#frequency_type_4_div').slideUp();
			$('#frequency_type_5_div').slideUp();
			$('#frequency_type_6_div').slideUp();
		} else if($(this).val() == 4) {
			$('#frequency_type_1_div').slideUp();
			$('#frequency_type_3_div').slideUp();
			$('#frequency_type_4_div').slideDown();
			$('#frequency_type_5_div').slideUp();
			$('#frequency_type_6_div').slideUp();
		} else if($(this).val() == 5) {
			$('#frequency_type_1_div').slideUp();
			$('#frequency_type_3_div').slideUp();
			$('#frequency_type_4_div').slideUp();
			$('#frequency_type_5_div').slideDown();
			$('#frequency_type_6_div').slideUp();
		} else if($(this).val() == 6) {
			$('#frequency_type_1_div').slideUp();
			$('#frequency_type_3_div').slideUp();
			$('#frequency_type_4_div').slideUp();
			$('#frequency_type_5_div').slideUp();
			$('#frequency_type_6_div').slideDown();
		}
	});
	
	$('#mechanism_for_notification_flag').click(function(){
		if($(this).prop('checked') == true){
			$('#notification-form').slideDown();
		}else{
			$('#notification-form').slideUp();
		}
	});
});
</script>
