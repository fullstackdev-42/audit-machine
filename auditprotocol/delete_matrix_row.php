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
	require('includes/users-functions.php');
		
	$dbh = la_connect_db();
	
	$form_id				= (int) $_POST['form_id'];
	$element_id				= (int) $_POST['element_id'];

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			die("You don't have permission to edit this form.");
		}
	}
	
	//we need to know if this matrix row is live or just a draft
	//if this is a live row, only set the status to 0
	//if this is a draft row, delete the row completely from the table
	
	$query  = "select 
					 element_status,
					 element_matrix_parent_id 
				from 
					 `".LA_TABLE_PREFIX."form_elements` 
			   where 
			   		 form_id=? and 
		 			 element_id=?";
	$params = array($form_id,$element_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$element_matrix_parent_id = $row['element_matrix_parent_id'];
	
	if($row['element_status'] == 2){ //if this is just a draft row
		$query = "delete from `".LA_TABLE_PREFIX."form_elements` where form_id = ? and element_id = ? and element_status=2";	
		$params = array($form_id,$element_id);
		la_do_query($query,$params,$dbh);
		
		$query = "delete from `".LA_TABLE_PREFIX."element_options` where form_id = ? and element_id = ?";
		$params = array($form_id,$element_id);
		la_do_query($query,$params,$dbh);
	}else{
		$table_is_empty = false;

		$query = "select count(*) total_row from `".LA_TABLE_PREFIX."form_{$form_id}`";
		$params = array($form_id,$element_id);
		
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(empty($row['total_row'])){
			$table_is_empty = true;
		}

		//delete permanently if true_delete is turned on or the table is still empty (having no entries)
		if(LA_CONF_TRUE_DELETE === true || $table_is_empty === true){
			//delete on table ap_form_elements
			$query = "delete from `".LA_TABLE_PREFIX."form_elements` where form_id = ? and element_id = ?";
			$params = array($form_id,$element_id);
			la_do_query($query,$params,$dbh);
							
			//delete on table ap_element_options
			$query = "delete from `".LA_TABLE_PREFIX."element_options` where form_id = ? and element_id = ?";
			$params = array($form_id,$element_id);
			la_do_query($query,$params,$dbh);

		}else{
			//update the status of the deleted row
			$query = "update `".LA_TABLE_PREFIX."form_elements` set element_status='0' where form_id = ? and element_id = ?";
			$params = array($form_id,$element_id);
			la_do_query($query,$params,$dbh);
			
			$query = "update `".LA_TABLE_PREFIX."element_options` set `live`='0' where form_id = ? and element_id = ?";
			$params = array($form_id,$element_id);
			la_do_query($query,$params,$dbh);
		}
	}

	//update the element_constraint column on parent matrix row
	$query = "select element_constraint from ".LA_TABLE_PREFIX."form_elements where form_id=? and element_id=?";
	$params = array($form_id,$element_matrix_parent_id);
		
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$element_constraint = $row['element_constraint'];
		
	$element_constraint_array = explode(',', $element_constraint);
	$key = array_search($element_id, $element_constraint_array);
	unset($element_constraint_array[$key]);

	$element_constraint_joined = implode(',', $element_constraint_array);
	$query = "update `".LA_TABLE_PREFIX."form_elements` set element_constraint=? where form_id = ? and element_id = ?";
	$params = array($element_constraint_joined,$form_id,$element_matrix_parent_id);
	la_do_query($query,$params,$dbh);

	$response_data = new stdClass();
	
	$response_data->status    	= "ok";
	$response_data->element_id 	= $element_id;
	$response_data->csrf_token  = $_SESSION['csrf_token']; 
	
	$response_json = json_encode($response_data);

	echo $response_json;
	exit();