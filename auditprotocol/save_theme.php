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
	require('includes/theme-functions.php');
		
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	if(empty($_POST['tp'])){
		die("Error! You can't open this file directly");
	}
	
	$theme_properties = la_sanitize($_POST['tp']);
	$unshare_theme	  = (int) $_POST['unshare'];
	$theme_id = (int) $theme_properties['theme_id'];
	
	unset($theme_properties['theme_id']);
	
	//check user privileges, is this user has privilege to create new theme (or edit)?
	if(empty($_SESSION['la_user_privileges']['priv_new_themes'])){
		die("Access Denied. You don't have permission to create/edit themes.");
	}


	if(empty($theme_id)){
		$is_new_theme = true;
	}else{
		$is_new_theme = false;
	}
	 
	
	//If this is new theme, insert new record into the table
   	if($is_new_theme){
   		
   		$theme_properties['status'] = 1;
   		$theme_properties['user_id'] = $_SESSION['la_user_id'];
   		$theme_properties['theme_is_private'] = 1; //by default all new themes are private
   		
   		//dynamically create the field list and field values, based on the input given
		$params = array();
		foreach ($theme_properties as $key=>$value){
			$field_list    .= "`{$key}`,";
			$field_values  .= ":{$key},";
			$params[':'.$key] = $value;
		}
		
		$field_list = rtrim($field_list,',');
		$field_values = rtrim($field_values,',');
		
		//insert into ap_form_themes  table
		$query = "INSERT INTO `".LA_TABLE_PREFIX."form_themes` ($field_list) VALUES ($field_values);"; 
		la_do_query($query,$params,$dbh);
		
		$theme_id = (int) $dbh->lastInsertId();
   		
   	}else{ //If this is old theme, update the data
   		
   		//check is this user allowed to edit this theme or not
		if(empty($_SESSION['la_user_privileges']['priv_administer'])){
			$query = "select user_id from ".LA_TABLE_PREFIX."form_themes where theme_id=?";
			$params = array($theme_id);

			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			if($row['user_id'] != $_SESSION['la_user_id']){
				die("You don't have permission to edit this theme.");
			}
		}

   		la_ap_form_themes_update($theme_id,$theme_properties,$dbh);

   		//if the theme is being deleted (status=0) or being set as private (unshare=1)
   		//we need to update ap_forms table and update all forms which use the theme
   		$theme_status = (int) $theme_properties['status'];
   		if(empty($theme_status) || !empty($unshare_theme)){
   			$query = "update ".LA_TABLE_PREFIX."forms set form_theme_id=0 where form_theme_id=?";
   			$params = array($theme_id);
   			la_do_query($query,$params,$dbh);
   		}
   	}
	
   	//create/update the CSS file for the theme
	$css_theme_filename = $la_settings['data_dir']."/themes/theme_{$theme_id}.css";
	$css_theme_content  = la_theme_get_css_content($dbh,$theme_id);
	
	$fpc_result = @file_put_contents($css_theme_filename,$css_theme_content);
	
	if(empty($fpc_result)){ //if we're unable to write into the css file, set the 'theme_has_css' to 0
		$params = array(0,$theme_id);
	}else{
		$params = array(1,$theme_id);
	}
	$query = "UPDATE ".LA_TABLE_PREFIX."form_themes SET theme_has_css = ? WHERE theme_id = ?";
	la_do_query($query,$params,$dbh);
	
	
   	if(!empty($theme_id)){
   		echo '{ "status" : "ok", "theme_id" : "'.$theme_id.'" }';
   	}else{
   		echo '{ "status" : "error", "message" : "Unable to save theme." }';
   	}
	
?>