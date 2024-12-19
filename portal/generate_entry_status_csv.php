<?php
/********************************************************************************
 * IT Audit Machine
 *
 * Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 * permission from http://lazarusalliance.com
 *
 * More info at: http://lazarusalliance.com
 ********************************************************************************/
require( 'includes/init.php' );

require( 'config.php' );
require( 'includes/db-core.php' );
require( 'includes/helper-functions.php' );
require( 'includes/check-session.php' );

$dbh = la_connect_db();
$la_settings = la_get_settings( $dbh );

# PROCESS REQUEST
if ( isset( $_GET['download_csv'] ) && isset( $_GET['form_id'] ) && isset( $_GET['company_id'] ) && isset( $_GET['entry_id'] ) ) {
	$form_id = (int)$_GET['form_id'];
	$company_id = (int)$_GET['company_id'];
	$entry_id = (int)$_GET['entry_id'];

	# FORM NAME
	$form_name_query = 'select form_name
			     from 
			     	 '.LA_TABLE_PREFIX.'forms 
			    where 
			    	 form_id = ?';
	$form_name_sth = la_do_query( $form_name_query, array($form_id), $dbh );
	$form_name_row = la_do_fetch_result( $form_name_sth );
	if ( !empty( $form_name_row ) ) {
		$form_name = strip_tags($form_name_row['form_name']);
	} else {
		die( "Error. Unknown form ID." );
	} # form name

	$formIdArr = array($form_id);
	//get cascaded sub form IDs
	$query_cascade_forms = "SELECT `element_default_value` FROM ".LA_TABLE_PREFIX."form_elements WHERE `form_id` = ? AND `element_type` = ?";
	$sth_cascade_forms = la_do_query($query_cascade_forms, array($form_id, "casecade_form"), $dbh);
	while($row_cascade_form = la_do_fetch_result($sth_cascade_forms)) {
		array_push($formIdArr, $row_cascade_form['element_default_value']);
	}
	$csv_arr = [];
	foreach ($formIdArr as $temp_form_id) {
		if($temp_form_id != $form_id) {
			//get page number and casecade_element_position of the sub form from the parent form
			$query111 = "SELECT `element_position`, `element_page_number` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = {$form_id} AND `element_type` = 'casecade_form' AND `element_default_value` = {$temp_form_id}";
			$sth111 = la_do_query($query111, array(), $dbh);
			$row111 = la_do_fetch_result($sth111);
			$tmp_casecade_element_position = $row111["element_position"];
			$tmp_page_number = $row111["element_page_number"];
		}

		$query = '
		select
		e.id element_id_auto,
		e.element_page_number,
		e.element_id element_id,
		e.element_title element_title,
		si.indicator indicator
		FROM
			'.LA_TABLE_PREFIX.'form_elements e
		LEFT JOIN '.LA_TABLE_PREFIX.'element_status_indicator si
			ON si.element_id = e.element_id
			AND si.form_id = e.form_id
		WHERE
			e.form_id = ? AND
			e.element_status = 1 AND
			si.company_id = ? AND si.entry_id = ?
		GROUP BY
		element_id
		ORDER BY
			indicator ASC, 
			e.element_position
			';
		$sth = la_do_query( $query, array($temp_form_id, $company_id, $entry_id), $dbh );

		# BUILD CSV DATA
		
		while ( $row = la_do_fetch_result( $sth ) ) { # ROW
			$prev_query_result = [];
			# STATUS (COLUMN 1)
		  	switch($row['indicator']){
				case 0:
				  $prev_query_result['status'] = $row['indicator'].' / Grey';
				  break;
				case 1:
				  $prev_query_result['status'] = $row['indicator'].' / Red';
				  break;
				case 2:
				  $prev_query_result['status'] = $row['indicator'].' / Yellow';
				  break;
				case 3:
				  $prev_query_result['status'] = $row['indicator'].' / Green';
				  break;
			}
		  	# ELEMENT TITLE (COLUMN 2)
			$prev_query_result['field_name'] = $row['element_title'];

			# ELEMENT LINK (COLUMN 3)
			$element_link = "https://".$_SERVER['SERVER_NAME'].'/portal/view.php?form_id='.$form_id.'&entry_id='.$entry_id.'&element_id_auto='.$row['element_id_auto'];
			if($temp_form_id == $form_id) {				
				if( $row['element_page_number'] > 1 ) {
					$element_link .= '&la_page='.$row['element_page_number'];
				}
			} else {
				$element_link .= '&la_page='.$tmp_page_number.'&casecade_element_position='.$tmp_casecade_element_position.'&casecade_form_page_number='.$row['element_page_number'];
			}
			
			$prev_query_result['field_link'] = $element_link;
			$csv_arr[] = $prev_query_result;
		}
	}	

	# CSV DATA: write to file
	if ( !empty( $csv_arr ) ) {
		$dir = $_SERVER['DOCUMENT_ROOT'].'/auditprotocol/data/form_'.$form_id.'/csv';
		$file_name = 'status_'.date( 'm-d-Y-h-i-s', time() ).'.csv';
		$file_path = $dir.$file_name;

		# CREATE DIRECTORY
		if ( !is_dir( $dir ) ) {
			if ( !mkdir( $dir, 0775, true ) ) {
				die( 'Failed to create folders...' );
			}
		}
		# INSUFFICIENT FILESYSTEM PERMISSIONS
		if ( !is_writable( $dir ) ) {
			echo 'Unable to write into CSV folder';
			die();
		}

		# CREATE CSV FILE
		if ( $fh = fopen( $file_path, 'w' ) ) {

			# HEADER (fields)
			$csv_header = [
			 'Status', # COLUMN 1
			 'Field Name (Form #'.$form_id.' '.$form_name.')', # COLUMN 2
			 'Field Link' # COLUMN 3
			];
			fputcsv( $fh, $csv_header );

			# ROWS (data)
			foreach ( $csv_arr as $row ) {
				fputcsv( $fh, $row );
			}
			fclose( $fh );
			if ( file_exists( $file_path ) ) {
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: text/csv' );
				header( 'Content-Disposition: attachment; filename='.$file_name );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Length: '.filesize( $file_path ) );

				ob_clean();
				flush();
				readfile( $file_path );
			} else die( 'Create File Failed' );
		} # create csv file
	} # csv data to file ($csv_arr)
} # process request