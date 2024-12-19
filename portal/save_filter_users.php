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
	
	$filter_properties_array = la_sanitize($_POST['filter_prop']);
	$filter_type = la_sanitize($_POST['filter_type']);

	if(empty($filter_type) || empty($filter_properties_array)){
		die("This file can't be opened directly.");
	}

	//we only need to save the filter into session variable
	$_SESSION['filter_users'] = array();

	$i=0;
	foreach($filter_properties_array as $data){
		$_SESSION['filter_users'][$i]['element_name'] 	  = $data['element_name'];
		$_SESSION['filter_users'][$i]['filter_condition'] = $data['condition'];
		$_SESSION['filter_users'][$i]['filter_keyword']   = $data['keyword'];
		$i++;
	}
	
	$_SESSION['filter_users_type'] = $filter_type;

	$response_data = new stdClass();
	$response_data->status    	= "ok";
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>