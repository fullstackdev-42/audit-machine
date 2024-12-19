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
	
	$form_id = (int) trim($_POST['form_id']);
	
	parse_str($_POST['widget_pos']); 
	$widget_positions = $widget_pos; //contain the positions of the widgets
	unset($el_pos);
	

	if(empty($form_id)){
		die("This file can't be opened directly.");
	}

	$dbh = la_connect_db();
	
	//update widget positions
	$query = "UPDATE ".LA_TABLE_PREFIX."report_elements SET chart_position = ? WHERE form_id = ? AND chart_id = ?";

	$i = 1;
	foreach($widget_positions as $chart_id){
		$params = array($i,$form_id,$chart_id);
		la_do_query($query,$params,$dbh);
		$i++;
	}

	$response_data = new stdClass();
	$response_data->status    	= "ok";
	$response_data->form_id 	= $form_id;
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
?>