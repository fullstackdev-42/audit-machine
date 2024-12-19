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
	require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.class.php');

	
	ob_clean(); //clean the output buffer
	
	$web3Ethereum = new Web3Ethereum();
	$user_id 	 	 = (int) trim($_REQUEST['user_id']);
	$clean_form_name = trim($_REQUEST['filename']);
	$is_portal = trim($_REQUEST['is_portal']);


	if(empty($user_id)){
		die("Invalid user ID.");
	}

	if (empty($is_portal))
		$is_portal = 0;

	$ssl_suffix = la_get_ssl_suffix();
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$column_labels = array('Form #', 'File Name', 'Datetime', 'Added to Chain', 'Hash Matched');
	
	header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public", false);
    header("Content-Description: File Transfer");
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"{$clean_form_name}.csv\"");
        
    $output_stream = fopen('php://output', 'w');
    fputcsv($output_stream, $column_labels,',');

	$query = "SELECT `al`.*, `f`.`form_name` FROM ".LA_TABLE_PREFIX."eth_file_data `al` left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = :user_id AND `al`.`is_portal` = :is_portal order by `al`.`id` DESC";
	$sth1 = la_do_query($query,array(':user_id' => $user_id, ':is_portal' => $_GET['is_portal']),$dbh);
	
	$row_num = 1;
	
	while($row = la_do_fetch_result($sth1)){
		
		$i++;
	  	$row_id = $row['id'];
	  	$form_name = $row['form_name'];
	  	$entry_id = $row['entry_id'];
	  	$form_id = $row['form_id'];
	  	$date = date("m/d/Y H:i", $row['action_datetime']);

	  	$full_file_name = $row['data'];
      	$file_name = substr($full_file_name,strpos($full_file_name, '-')+1);

      	switch ($row['added_to_chain']) {
		    case 0:
		        $file_status = "Pending";
		        break;
		    case 1:
		        $file_status = "Added";
		        break;
		    case 2:
		        $file_status = "Error";
		        break;
		}

		$form_dir = $la_settings['upload_dir']."/form_{$form_id}/files";
		$file_location = $form_dir.'/'.$full_file_name;
		
		if( file_exists ( $file_location ) ) {
			if( !empty($entry_id) ) {
				$file_hash = hash_file ( "sha256", $file_location );
				$result_chain = $web3Ethereum->call('getEntryByDocumentHash','0x'.$file_hash); 
				$result_chain['documentHash'] = '';
				if( !empty($result_chain['documentHash']) ) {
					$chain_status = 'Matched';
				} else {
					$chain_status = 'Not Matched';
				}
			}
		} else {
			$chain_status = 'Not Matched';
		}

      	$csv_row = array($form_id, $file_name, $date, $file_status, $chain_status);
		
		fputcsv($output_stream, $csv_row,',');
		

		$row_num++;
	}
	
	
	fclose($output_stream);