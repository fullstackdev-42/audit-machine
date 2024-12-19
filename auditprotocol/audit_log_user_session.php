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
	
	$is_admin = 1;
	$user_id 	 	 = (int) trim($_REQUEST['user_id']);
	if( isset($_GET['is_portal']) && !empty($_GET['is_portal']) )
		$is_admin = 0;

	$export_type 	 = trim($_REQUEST['type']);
	$clean_form_name = trim($_REQUEST['filename']);

	if(empty($user_id)){
		die("Invalid user ID.");
	}

	if (empty($is_portal))
		$is_portal = 0;

	$ssl_suffix = la_get_ssl_suffix();
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$column_labels = array('Log-In', 'Log-Out', 'Session Time');
	
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

	//query
	$log_table = LA_TABLE_PREFIX."user_sessions";
	
	$query = "SELECT * FROM `{$log_table}` where user_id = :user_id AND is_admin = :is_admin ORDER BY id DESC";
	$result = la_do_query($query,array(':user_id' => $user_id, ':is_admin' => $is_admin),$dbh);
	//query
	
	$row_num = 1;
	
	while($row = la_do_fetch_result($result)){
		
		$login_time = $row['login_time'];
		$logout_time = $row['logout_time'];
		$session_time = '';

		$login_time_mod = ( !empty($login_time) ) ? date("m/d/Y H:i", $row['login_time']) : '-';
		$logout_time_mod = ( !empty($logout_time) ) ? date("m/d/Y H:i", $row['logout_time']) : '-';
		
		
		if( !empty($logout_time) ) {
			$then = date("m/d/Y H:i:s", $login_time);
			$now = date("m/d/Y H:i:s", $logout_time);
			
			$then = new DateTime($then);
			$now = new DateTime($now);
			 
			$sinceThen = $then->diff($now);
			$session_time = $sinceThen->format('%i minutes %s seconds');
		}


		$row = array($login_time_mod, $logout_time_mod, $session_time);
		
		
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