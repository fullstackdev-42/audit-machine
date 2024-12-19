<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');

	@ini_set("include_path", './lib/pear/'.PATH_SEPARATOR.ini_get("include_path"));
	@ini_set("max_execution_time",1800);
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/entry-functions.php');
	require('includes/users-functions.php');
	
	ob_clean(); //clean the output buffer
	
	$user_id 	 	 = (int) trim($_REQUEST['user_id']);
	$export_type 	 = trim($_REQUEST['type']);
	$clean_form_name = trim($_REQUEST['filename']);
	$is_portal = trim($_REQUEST['is_portal']);
	$user_fullname = "";
	if(empty($user_id)){
		die("Invalid user ID.");
	}

	if (empty($is_portal))
		$is_portal = 0;

	$ssl_suffix = la_get_ssl_suffix();
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	if($is_portal == 1) {
		//user information
		$query = "SELECT 
					email as user_email,
					full_name as user_fullname,
					tsv_enable,
					`status`
			    FROM 
					".LA_TABLE_PREFIX."ask_client_users 
			   WHERE 
			   		client_user_id=?";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$user_profile = $row;
		$user_fullname = "[User] ".$user_profile["user_fullname"]."(".$user_profile["user_email"].")";
	} else {
		//admin information
		$query = "SELECT 
					user_email,
					user_fullname,
					priv_administer,
					priv_new_forms,
					priv_new_themes,
					last_login_date,
					last_ip_address,
					tsv_enable,
					`status` 
			    FROM 
					".LA_TABLE_PREFIX."users 
			   WHERE 
			   		user_id=? and `status` > 0";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$user_profile = $row;
		$user_fullname = "[Admin] ".$user_profile["user_fullname"]."(".$user_profile["user_email"].")";
	}
	
	$column_labels = array('Form #', 'Action', 'Datetime');
	
	//prepare the header response, based on the export type
	if($export_type == 'xls'){
		require('Spreadsheet/Excel/Writer.php');
		
		// Creating a workbook
		$workbook = new Spreadsheet_Excel_Writer();
		
		$workbook->setTempDir($la_settings['upload_dir']);
		
		// sending HTTP headers
		$workbook->send("{$clean_form_name}.xls");
		
		if(function_exists('iconv')){
			$workbook->setVersion(8); 
		}
		
		// Creating a worksheet
		$clean_form_name = substr($clean_form_name,0,30); //must be less than 31 characters
		$worksheet =& $workbook->addWorksheet($clean_form_name);
		
		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();
		$format_bold->setFgColor(22);
		$format_bold->setPattern(1);
		$format_bold->setBorder(1);
						
		if(function_exists('iconv')){
			$worksheet->setInputEncoding('UTF-8');
		}
		
		$format_wrap = $workbook->addFormat();
		$format_wrap->setTextWrap();

		$i=0;
		foreach ($column_labels as $label){
			$worksheet->write(0, $i, $label,$format_bold);
			$i++;
		}

	}else if ($export_type == 'csv') {
		header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Cache-Control: public", false);
	    header("Content-Description: File Transfer");
	    header("Content-Type: application/vnd.ms-excel");
	    header("Content-Disposition: attachment; filename=\"{$clean_form_name}.csv\"");
	        
	    $output_stream = fopen('php://output', 'w');
	    fputcsv($output_stream, $column_labels,',');
		
	}elseif ($export_type == 'txt') {
		header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Cache-Control: public", false);
	    header("Content-Description: File Transfer");
	    header("Content-Type: application/vnd.ms-excel");
	    header("Content-Disposition: attachment; filename=\"{$clean_form_name}.txt\"");
	        
	    $output_stream = fopen('php://output', 'w');
	    fputcsv($output_stream, $column_labels,"\t");
	}

	$log_table = LA_TABLE_PREFIX."audit_log";
	if( !empty($is_portal) )
		$log_table = LA_TABLE_PREFIX."audit_client_log";

	$hide_login_logs = $hide_login_logs_count_query = '';

	if($_SESSION['la_user_id'] != 1){
  		$hide_login_logs = "AND `al`.`action_type_id` != 6";
  		$hide_login_logs_count_query = "AND action_type_id != 6";
  	}

	// $query  = $query = "SELECT `al`.*, `aat`.`action_type`, `f`.`form_name` FROM `".LA_TABLE_PREFIX."audit_log` `al` left join `".LA_TABLE_PREFIX."audit_action_type` `aat` on (`aat`.`action_type_id` = `al`.`action_type_id`) left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = :user_id order by `al`.`action_datetime` DESC";

	$query = "SELECT `al`.*, `aat`.`action_type`, `f`.`form_name` FROM `{$log_table}` `al` left join `".LA_TABLE_PREFIX."audit_action_type` `aat` on (`aat`.`action_type_id` = `al`.`action_type_id`) left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = :user_id {$hide_login_logs} order by `al`.`action_datetime` DESC";

	$params = array(':user_id' => $user_id);
	$sth1 = la_do_query($query,$params,$dbh);
	
	$row_num = 1;
	
	while($row1 = la_do_fetch_result($sth1)){
		
		if($row1['action_type_id'] == 6){
			if($_SESSION['la_user_id'] == 1){
				$row = array("", "{$user_fullname} {$row1['action_type']} from {$row1['user_ip']}", date("m/d/Y H:i", $row1['action_datetime']));
			}
		} elseif($row1['action_type_id'] == 7 || $row1['action_type_id'] == 8){
			$row = array($row1['form_id'], "{$user_fullname} {$row1['action_text']}", date("m/d/Y H:i", $row1['action_datetime']));

		} elseif($row1['action_type_id'] == 9 || $row1['action_type_id'] == 10 || $row1['action_type_id'] == 11 || $row1['action_type_id'] == 12 || $row1['action_type_id'] == 13){

			$action_text_array = json_decode($row1['action_text']);
			if( isset($action_text_array->action_performed_on) ) {
				$action_text = $user_fullname.' '.$row1['action_type'].' '.$action_text_array->action_performed_on;	
			} else {
				$action_text = '[Admin] '.$action_text_array->action_performed_by.' '.$row1['action_type'].' '.$user_fullname;
			}
			$row = array("", $action_text, date("m/d/Y H:i", $row1['action_datetime']));
		} elseif($row1['action_type_id'] == 14 || $row1['action_type_id'] == 15 || $row1['action_type_id'] == 16) {
			$file_name = substr($row1['action_text'], strpos($row1['action_text'], '-') +1 );
			$row = array($row1['form_id'], "{$user_fullname} {$row1['action_type']} {$file_name} Form #{$row1['form_id']}", date("m/d/Y H:i", $row1['action_datetime']));
		} else{
			$row = array($row1['form_id'], "{$user_fullname} {$row1['action_type']} Form #{$row1['form_id']}", date("m/d/Y H:i", $row1['action_datetime']));
		}
		
		if($export_type == 'xls'){
			$col_num = 0;
			foreach ($row as $data){
				$data = str_replace("\r","",$data);
				if($col_num > 4){
					$worksheet->write($row_num, $col_num, $data, $format_wrap);
				}else{
					$worksheet->write($row_num, $col_num, $data);
				}
				$col_num++;	
			}
		}elseif ($export_type == 'csv') {
			fputcsv($output_stream, $row,',');
		}elseif ($export_type == 'txt') {
			fputcsv($output_stream, $row,"\t");
		}

		$row_num++;
	}
	
	//close the stream
	if($export_type == 'xls'){
		$workbook->close();
	}else{
		fclose($output_stream);
	}