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
	
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/users-functions.php');

	$form_id = (int) trim($_POST['form_id']);
	$embed_selected = trim($_POST['embed_selected']);
	$ec_code_type_user = trim($_POST['ec_code_type_user']);
	
	if( empty( $form_id ) || empty($embed_selected) || empty($ec_code_type_user) ) {
		$response['error'] = 1;
		$response['error_message'] = 'All required fields not supplied';
		echo json_encode($response);
		exit;
	}


	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

			$ssl_suffix = la_get_ssl_suffix();						
			$response['error'] = 1;
			$response['redirect'] = "http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/restricted.php";
			$response['error_message'] = 'Permission Issue';
			echo json_encode($response);
			exit;
		}
	}
	
	//get form properties
	$query 	= "select 
					form_name,
					form_frame_height,
					form_captcha
			     from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){
		$row['form_name'] = la_trim_max_length($row['form_name'],65);
		
		$form_name 	= htmlspecialchars($row['form_name']);
		$form_frame_height  = (int) $row['form_frame_height'];

		if(empty($row['form_captcha'])){
			$form_frame_height += 80;
		}else{
			$form_frame_height += 250;
		}
	}

	$ssl_suffix = la_get_ssl_suffix();

	
	if( $ec_code_type_user == 'admin' ) {
		$form_base_url = $la_settings['base_url'];

		//construct iframe code
		$iframe_form_code = '<iframe onload="javascript:parent.scrollTo(0,0);" height="'.$form_frame_height.'" allowTransparency="true" frameborder="0" scrolling="no" style="width:100%;border:none" src="http'.$ssl_suffix.'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/embed.php?id='.$form_id.'&userid='.base64_encode($_SESSION['la_user_id']).'" title="'.$form_name.'"><a href="http'.$ssl_suffix.'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/view.php?id='.$form_id.'" title="'.$form_name.'">'.$form_name.'</a></iframe>';	
	} else {
		$host = str_replace('auditprotocol/','',$la_settings['base_url']);
		$form_base_url = $host.'portal/';

		//construct iframe code
		$iframe_form_code = '<iframe onload="javascript:parent.scrollTo(0,0);" height="'.$form_frame_height.'" allowTransparency="true" frameborder="0" scrolling="no" style="width:100%;border:none" src="'.$form_base_url .'view.php?id='.$form_id.'" title="'.$form_name.'"></iframe>';	
	}



	$form_embed_url 	= $form_base_url.'embed.php?id='.$form_id;
	$itauditmachine_base_url 	= $form_base_url;
	$jquery_url 		= $form_base_url.'/js/jquery.min.js';
	
	//construct javascript code
	$javascript_form_code =<<<EOT
<script type="text/javascript">
var __itauditmachine_url = '{$form_embed_url}';
var __itauditmachine_height = {$form_frame_height};
</script>
<div id="la_placeholder"></div>
<script type="text/javascript" src="{$jquery_url}"></script>
<script type="text/javascript" src="{$itauditmachine_base_url}js/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_base_url}js/jquery.ba-postmessage.min.js"></script>
<script type="text/javascript" src="{$itauditmachine_base_url}js/itauditmachine_loader.js"></script>
EOT;

	
	
	//construct php embed code
	$current_dir 	  = rtrim(dirname($_SERVER['PHP_SELF']));
	if($current_dir == "/" || $current_dir == "\\"){
		$current_dir = '';
	}
	
	$absolute_dir_path = rtrim(dirname($_SERVER['SCRIPT_FILENAME'])); 


	//construct simple link code
	$simple_link_form_code = '<a href="'.$form_base_url.'view.php?id='.$form_id.'" title="'.$form_name.'">'.$form_name.'</a>';

	//construct popup link code
	if($form_frame_height > 750){
		$popup_height = 750;
	}else{
		$popup_height = $form_frame_height;
	}
	$popup_link_form_code = '<a href="'.$form_base_url.'view.php?id='.$form_id.'" onclick="window.open(this.href,  null, \'height='.$popup_height.', width=800, toolbar=0, location=0, status=0, scrollbars=1, resizable=1\'); return false;">'.$form_name.'</a>';




	$current_nav_tab = 'manage_forms';
	// require('includes/header.php');
	$response['success']  = 1;
	$embed_extra_info = " Copy and Paste the Code Below into Your Website Page" ;
	if( $embed_selected == 'javascript' ) {
		$response['embed_main_title'] = 'Javascript Code';
		$response['embed_extra_info'] = "This code will insert the form into your existing web page seamlessly. Thus the form background, border and logo header won't be displayed.".$embed_extra_info;
		$response['embed_textarea_content'] = $javascript_form_code;

	} else if ( $embed_selected == 'iframe' ) {
		$response['embed_main_title'] = 'Iframe Code';
		$response['embed_extra_info'] = "This code will insert the form into your existing web page seamlessly. Thus the form background, border and logo header won't be displayed. You might also need to adjust the iframe height value.".$embed_extra_info;

		$response['embed_textarea_content'] = $iframe_form_code;

	} else if ( $embed_selected == 'simple_link' ) {
		$response['embed_main_title'] = 'Simple Link';
		$response['embed_extra_info'] = "This code will display direct link to your form. Use this code to share your form with others through emails or web pages.".$embed_extra_info;
		$response['embed_textarea_content'] = $simple_link_form_code;

	} else if ( $embed_selected == 'popup_link' ) {
		$response['embed_main_title'] = 'Popup Link';
		$response['embed_extra_info'] = "This code will display your form into a popup window.".$embed_extra_info;

		$response['embed_textarea_content'] = $popup_link_form_code;
	}

	
	echo json_encode($response);
	exit;
?>