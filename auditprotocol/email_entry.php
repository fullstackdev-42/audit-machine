<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/entry-functions.php');
	require('includes/post-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('includes/users-functions.php');
	
	$form_id  = (int) trim($_POST['form_id']);
	$company_id = (int) trim($_POST['company_id']);
	$entry_id = (int) trim($_POST['entry_id']);
	$target_email = trim($_POST['target_email']);

	if(empty($form_id) || empty($company_id) || empty($entry_id) || empty($target_email)){
		die("Invalid parameters.");
	}
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh, $form_id, $_SESSION['la_user_id']);

		//this page need edit_entries or view_entries permission
		if(empty($user_perms['edit_entries']) && empty($user_perms['view_entries'])){
			die("Access Denied. You don't have permission to email the entry data.");
		}
	}

	//get form properties data
	$query 	= "select 
					 form_language,
					 form_email,
					 esl_enable,
					 esl_from_name,
					 esl_from_email_address,
					 esl_replyto_email_address,
					 esl_subject,
					 esl_content,
					 esl_plain_text,
					 esr_enable,
					 esr_email_address,
					 esr_from_name,
					 esr_from_email_address,
					 esr_subject,
					 esr_content,
					 esr_plain_text
			     from 
			     	 `".LA_TABLE_PREFIX."forms` 
			    where 
			    	 form_id=?";
	$params = array($form_id);
		
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
		
	if(!empty($row['form_language'])){
		la_set_language($row['form_language']);
	}

	$esl_from_name 	= $row['esl_from_name'];
	$esl_from_email_address    = $row['esl_from_email_address'];
	$esl_replyto_email_address = $row['esl_replyto_email_address'];
	$esl_subject 	= $row['esl_subject'];
	$esl_content 	= $row['esl_content'];
	$esl_plain_text	= $row['esl_plain_text'];
	$esl_enable     = $row['esl_enable'];
		
	$esr_email_address 	= $row['esr_email_address'];
	$esr_from_name 	= $row['esr_from_name'];
	$esr_from_email_address = $row['esr_from_email_address'];
	$esr_subject 	= $row['esr_subject'];
	$esr_content 	= $row['esr_content'];
	$esr_plain_text	= $row['esr_plain_text'];
	$esr_enable		= $row['esr_enable'];
	
	//get parameters for the email

	//from name
	if(!empty($esl_from_name)){
		if(is_numeric($esl_from_name)){
			$admin_email_param['from_name'] = '{element_'.$esl_from_name.'}';
		}else{
			$admin_email_param['from_name'] = $esl_from_name;
		}
	}else{
		if(!empty($la_settings['default_from_name'])){
    		$admin_email_param['from_name'] = $la_settings['default_from_name'];
    	}else{
    		$admin_email_param['from_name'] = 'IT Audit Machine';	
    	}
	}

			
	//from email address
	if(!empty($esl_from_email_address)){
		if(is_numeric($esl_from_email_address)){
			$admin_email_param['from_email'] = '{element_'.$esl_from_email_address.'}';
		}else{
			$admin_email_param['from_email'] = $esl_from_email_address;
		}
	}else{
		if(!empty($la_settings['default_from_email'])){
    		$admin_email_param['from_email'] = $la_settings['default_from_email'];
    	}else{
	    	$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
			$admin_email_param['from_email'] = "no-reply@{$domain}";
		}
	}

	//reply-to email address
	if(!empty($esl_replyto_email_address)){
		if(is_numeric($esl_replyto_email_address)){
			$admin_email_param['replyto_email'] = '{element_'.$esl_replyto_email_address.'}';
		}else{
			$admin_email_param['replyto_email'] = $esl_replyto_email_address;
		}
	}else{
		if(!empty($la_settings['default_from_email'])){
    		$admin_email_param['replyto_email'] = $la_settings['default_from_email'];
    	}else{
	    	$domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
			$admin_email_param['replyto_email'] = "no-reply@{$domain}";
		}
	}
			
	//subject
	if(!empty($esl_subject)){
		$admin_email_param['subject'] = $esl_subject;
	}else{
		$admin_email_param['subject'] = '{form_name} [#{entry_no}]';
	}
			
	//content
	if(!empty($esl_content)){
		$admin_email_param['content'] = $esl_content;
	}else{
		$admin_email_param['content'] = '{entry_data}';
	}

	$admin_email_param['as_plain_text'] = $esl_plain_text;
	$admin_email_param['target_is_admin'] = true; 
	$admin_email_param['email_entry_call'] = true; 
	$admin_email_param['link_and_file_name'] = true; 

	la_send_notification($dbh,$form_id,$company_id,$entry_id,$target_email,$admin_email_param);
	
   	echo '{"status" : "ok"}';

?>