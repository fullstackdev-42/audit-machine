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

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

if(isset($_GET['download_csv']) && isset($_GET['form_id']) && isset($_GET['company_id']) ){
	$form_id = $_GET['form_id'];
	$company_id = $_GET['company_id'];

	$query = "SELECT 
					A.note,
					A.element_id,
					B.element_title
				FROM 
					".LA_TABLE_PREFIX."form_element_note A INNER JOIN ".LA_TABLE_PREFIX."form_elements B
				  ON 
				  	A.form_id = B.form_id AND  A.element_id = B.element_id
			   	WHERE
					A.company_id = :company_id AND A.form_id = :form_id AND note <> '' 
			ORDER BY 
					A.element_id asc";

	$params = array();
	$params[':form_id'] 	 = $form_id;
	$params[':company_id'] 	 = $company_id;
	$sth = la_do_query($query,$params,$dbh);
	$row_count = la_get_row_count($dbh, $params, $query);
	if( empty($row_count) )
		die('Notes not available for this entry.');

	$csv_arr = [];

	while($row = la_do_fetch_result($sth)){
		$prev_query_result = [];
		$query_form_id = "SELECT 
						data_value
					FROM
						".LA_TABLE_PREFIX."form_{$form_id}
					WHERE
						company_id = ? AND field_name = ?";
		$params = array($company_id, 'element_'.$row['element_id']);
		$query = la_do_query($query_form_id,$params,$dbh);
		$element_value = la_do_fetch_result($query);
		
		$prev_query_result['field_name'] = $row['element_title'];
		$prev_query_result['field_value'] = $element_value['data_value'];
		$prev_query_result['note'] = $row['note'];
		$csv_arr[] = $prev_query_result;
	}

	if( !empty($csv_arr) ) {
		$dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_$form_id/csv";
		$file_name = 'notes_'.date('m-d-Y-h-i-s', time()).'.csv';
		$file_path = $dir.$file_name;
		
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0775, true)) {
			    die('Failed to create folders...');
			}
		}
		if(!is_writable($dir)){
			echo "Unable to write into csv folder!";die();
		}

		$fh = fopen($file_path, 'w');

	    # write out the headers
	    $csv_header = ['Field Name','Field Value','Note'];
	    fputcsv($fh, $csv_header);

	    # write out the data
	    foreach ( $csv_arr as $row ) {
	            fputcsv($fh, $row);
	    }
	    fclose($fh);
	    if( file_exists($file_path) ) {
		    header('Content-Description: File Transfer');
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename='.$file_name);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file_path));

			ob_clean();
			flush();
			readfile($file_path);
		}
	}
}