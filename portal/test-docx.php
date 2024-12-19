<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2017 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com 
 ********************************************************************************/
 	require('includes/init.php');
	header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/filter-functions.php');
	require('includes/common-validator.php');
	require('includes/view-functions.php');
	require('includes/post-functions.php');
	require('includes/entry-functions.php');
	require('includes/helper-functions.php');
	require('includes/theme-functions.php');
	require('includes/report-helper-function.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('lib/recaptchalib.php');
	require('lib/php-captcha/php-captcha.inc.php');
	require('lib/text-captcha.php');
	require('hooks/custom_hooks.php');
	/******************************************************/
	require_once '../policymachine/classes/CreateDocx.inc';
	require_once '../policymachine/classes/CreateDocxFromTemplate.inc';
	
	$dbh 		= la_connect_db();
	$la_settings = la_get_settings($dbh);
	$ssl_suffix = la_get_ssl_suffix();
	
	$form_id = "292530";
	$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
	$param_template = array();
	$param_template[':form_id'] = $form_id;
	$result_template = la_do_query($query_template,$param_template,$dbh);
	
	$iLoop = 1;	
	if(extension_loaded('zip')){
		/***************F O R M  N A M E ****************/
		$query_form  = "SELECT `form_name` FROM `ap_forms` WHERE `form_id` = :form_id";
		$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
		$row_form    = la_do_fetch_result($result_form);
		$form_name   = trim($row_form['form_name']);
		$form_name   = str_replace(" ", "_", $form_name);
		$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
		$form_name	 = substr($form_name, 0, 24);
		/***************F O R M  N A M E ****************/
		
		$zip = new ZipArchive();	
		$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";
		
		if($num_rows > 0){
			if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){		
				
			}
		}
		
		while($row_template = la_do_fetch_result($result_template)){
			
			$template_document = trim($row_template['template']);
			
			if(file_exists($template_document) == true){
				$fileExt = end(explode(".", $template_document));
				if($fileExt == 'docx'){
					$docx = new CreateDocxFromTemplate($template_document);
					$templateVar = $docx->getTemplateVariables();
					echo '<pre>';print_r($templateVar);echo '</pre>';
				}
			}
			
		}						
	}