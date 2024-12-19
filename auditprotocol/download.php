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

	ob_clean(); //clean the output buffer
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//get query string and parse it, query string is base64 encoded
	$query_string = trim($_GET['q']);
	parse_str(base64_decode($query_string),$params);

	//called this function only for the synced files
	if( !empty($params['call_type']) && $params['call_type'] == 'ajax_synced' ) {
		$file_name = $params['file_name'];
		$encoded_file_name = str_replace("%", "%25", $file_name);
		$encoded_file_name = str_replace("#", "%23", $encoded_file_name);

		$element_machine_code = $params['element_machine_code'];
		$target_file 	= $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_machine_code}/$file_name";

		$response = [];
		if(file_exists($target_file)) {
			
			$filename_ext   = end(explode(".", $file_name));

			if( $filename_ext == 'pdf' ) {
				$pdf_src = $la_settings['base_url']."data/file_upload_synced/{$element_machine_code}/{$encoded_file_name}";
				$response['file_src'] = $pdf_src;
				$response['status'] = 'success';
				$response['download_src'] = $pdf_src;
			} else {
				$ext_not_allowerd = ['zip','php','php3','php4','php5','phtml','exe','pl','cgi','html','htm','js','sh','tar'];
				if( in_array($filename_ext, $ext_not_allowerd) ) {
					//no need to show preview for these extenstion, just download button
					$response['status'] = 'success';
					$response['download_src'] = $la_settings['base_url']."data/file_upload_synced/{$element_machine_code}/{$encoded_file_name}";
					$response['only_download'] = true;
				} else {
					require_once("class_transformDocToPdf.php");
					$docx = new TransformDocToPdf();
					$sourceFileInfo = pathinfo($file_name);
					$extension = $sourceFileInfo['extension'];

					$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/previews";

					$target_file = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$element_machine_code}/{$file_name}";

					if (!file_exists($destination_dir))
						mkdir($destination_dir, 0777, true);
					
					$destination_file = $destination_dir.'/'.$sourceFileInfo['filename'].'.pdf';

					$is_file_created = $docx->transformDocument($target_file, $destination_file, '', array('outdir' => $destination_dir, 'debug' => false));
					if( $is_file_created != false ) {
						$str = $sourceFileInfo['filename'];
						$str = str_replace("%", "%25", $str);
						$str = str_replace("#", "%23", $str);
						$response['file_src'] = $la_settings['base_url']."data/file_upload_synced/previews/".$str.'.pdf';
						$response['status'] = 'success';
						$response['download_src'] = $la_settings['base_url']."data/file_upload_synced/{$element_machine_code}/{$encoded_file_name}";
					} else {
						$response['status'] = 'error';
					}
				}
			}
		} else {
			$response['status'] = 'error';
		}
		echo json_encode($response);
		exit;
	}
	//called this function only for the documents created by the app
	else if( isset($params['document_preview']) && ($params['document_preview'] == 1) ) {
		//no need to query database in this case
		$response = [];
		$response['status'] = 'error';
		if( isset($params['file_path']) ) {
			require_once("class_transformDocToPdf.php");
			$docx = new TransformDocToPdf();
			$file_name = $params['file_name'];
			$sourceFileInfo = pathinfo($params['file_name']);
			$form_id = $params['form_id'];
			$target_file = $params['file_path'];
			$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/previews";

			if (!file_exists($destination_dir))
				mkdir($destination_dir, 0777, true);

			$destination_file = $destination_dir.'/'.$sourceFileInfo['filename'].'.pdf';
			$is_file_created = $docx->transformDocument($target_file, $destination_file, '', array('outdir' => $destination_dir, 'debug' => false));
			
			if( $is_file_created != false ) {
				$response['file_src'] = $la_settings['base_url']."data/form_{$form_id}/previews/". $sourceFileInfo['filename'] . '.pdf';
				$response['status'] = 'success';
				$base_url = str_replace('auditprotocol', 'portal', $la_settings['base_url']);
				$response['download_src'] = "{$base_url}template_output/$file_name";
			} else {
				$response['status'] = 'error';
			}
		}

		echo json_encode($response);
		exit;
	}
	//called this function only for the unsynced files
	else if( !empty($params['call_type']) && $params['call_type'] == 'ajax_normal' ) {
		$form_id 	= (int) $params['form_id'];
		$file_name = $params['file_name'];
		$encoded_file_name = str_replace("%", "%25", $file_name);
		$encoded_file_name = str_replace("#", "%23", $encoded_file_name);
		$target_file 	= $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/$file_name";
	
		$response = [];
		if(file_exists($target_file)) {
			
			$filename_ext   = end(explode(".", $file_name));

			if( $filename_ext == 'pdf' ) {
				$pdf_src = $la_settings['base_url']."data/form_{$form_id}/files/{$encoded_file_name}";
				$response['file_src'] = $pdf_src;
				$response['status'] = 'success';
				$response['only_download'] = false;
				$response['download_src'] = $pdf_src;
			} else {
				$ext_not_allowerd = ['zip','php','php3','php4','php5','phtml','exe','pl','cgi','html','htm','js','sh','tar'];
				if( in_array($filename_ext, $ext_not_allowerd) ) {
					//no need to show preview for these extenstion, just download button
					$response['status'] = 'success';
					$response['download_src'] = $la_settings['base_url']."data/form_{$form_id}/files/{$encoded_file_name}";
					$response['only_download'] = true;
				} else {
					require_once("class_transformDocToPdf.php");
					$docx = new TransformDocToPdf();
					$sourceFileInfo = pathinfo($file_name);
					$extension = $sourceFileInfo['extension'];

					$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/previews";

					if (!file_exists($destination_dir))
						mkdir($destination_dir, 0777, true);

					$destination_file = $destination_dir.'/'.$sourceFileInfo['filename'].'.pdf';

					$is_file_created = $docx->transformDocument($target_file, $destination_file, '', array('outdir' => $destination_dir, 'debug' => false));
					if( $is_file_created != false ) {
						$str = $sourceFileInfo['filename'];
						$str = str_replace("%", "%25", $str);
						$str = str_replace("#", "%23", $str);
						$response['file_src'] = $la_settings['base_url']."data/form_{$form_id}/previews/".$str.'.pdf';
						$response['status'] = 'success';
						$response['only_download'] = false;
						$response['download_src'] = $la_settings['base_url']."data/form_{$form_id}/files/{$encoded_file_name}";
					} else {
						$response['status'] = 'error';
					}
				}
			}
		} else {
			$response['status'] = 'error';
		}
		echo json_encode($response);
		exit;
	}