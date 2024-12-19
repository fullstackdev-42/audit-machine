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
	//echo md5("{$_SESSION['la_client_entity_id']}_{$params['el']}");
	// echo '<pre>';print_r($params);echo '</pre>';
	// die;
	if( !empty($params['call_type']) && $params['call_type'] == 'ajax_synced' ) {
		$response = [];

		$file_name = $params['file_name'];
		$element_machine_code = $params['element_machine_code'];


		if( !empty($file_name) ) {

			$filename_ext   = end(explode(".", $file_name));

			if( $filename_ext == 'pdf' ) {
				$pdf_src = $la_settings['base_url']."data/file_upload_synced/{$element_machine_code}/{$file_name}";
				$response['file_src'] = $pdf_src;
				$response['status'] = 'success';
				$response['download_src'] = $pdf_src;
			} else {
				
				require_once $_SERVER['DOCUMENT_ROOT'].'/auditprotocol/class_transformDocToPdf.php';
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
					$response['file_src'] = $la_settings['base_url']."data/file_upload_synced/previews/". $sourceFileInfo['filename'] . '.pdf';
					$response['status'] = 'success';
					$response['download_src'] = $target_file;
				} else {
					$response['status'] = 'error';
					$response['message'] = 'Document transform failed';
				}

			}
			
		} else {
			$response['status'] = 'error';
			$response['message'] = 'Invalid file name';
		}
		echo json_encode($response);
		exit;
	} else if( isset($params['document_preview']) && ($params['document_preview'] == 1) ) {
		//no need to query database in this case
		$response = [];
		$response['status'] = 'error';
		if( isset($params['file_path']) ) {
			require_once $_SERVER['DOCUMENT_ROOT'].'/auditprotocol/class_transformDocToPdf.php';
			$docx = new TransformDocToPdf();
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
				$base_url = str_replace('auditprotocol', 'portal', $la_settings['base_url']);
				$file_path_encoded = base64_encode($params['file_path']);
				$response['download_src'] = "{$base_url}download_document_zip.php?file_path={$file_path_encoded}&download_file=1";
				$response['status'] = 'success';
			} else {
				echo "not able to create file";
			}
		}

		echo json_encode($response);
		exit;
	}
	
	$form_id 	= (int) $params['form_id'];
	//$id      	= (int) $params['id'];
	$id = 1;
	
	$field_name = str_replace(array("`","'",';'), '', $params['el']);
	$file_hash  = trim($params['hash']);
	
	
	$id1 = $id - 1;

	//get filename
	$query 	= "select data_value from `".LA_TABLE_PREFIX."form_{$form_id}` where unique_row_data = ?";
	$params = array(md5("{$_SESSION['la_client_entity_id']}_{$params['el']}"));
		
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	$filename_array  = array();	
	//die;	
	
	//echo '<pre>';print_r($row);echo '</pre>';
	//die;
		
	$filename_md5_array = array();
	if(strpos($row['data_value'],'|') !== false ){
		$filename_array  = explode('|',$row['data_value']);
		foreach ($filename_array as $value) {
			$filename_md5_array[] = md5($value);
		}
	} else {
		$filename_array[0]  	=  $row['data_value'];
		$filename_md5_array[0]  = md5($row['data_value']);
	}
		
	$file_key = array_keys($filename_md5_array,$file_hash);
	
	//print_r($file_key);
	//print_r($filename_md5_array);
	//print_r($file_hash);
	//die;
	
// var_dump(is_numeric($file_key[0]));

	if( (empty($file_key)) && (!is_numeric($file_key[0]))){
		die("Error. File Not Found!");
	}else{
		$file_key = $file_key[0];
	}
	
	$complete_filename = $filename_array[$file_key]; 
	
	//remove the element_x-xx- suffix we added to all uploaded files
	$file_1 	   	= substr($complete_filename,strpos($complete_filename,'-')+1);
	$filenameArr    = explode("-", $complete_filename);
	array_splice($filenameArr, 0, 1);
	$filename_only 	= implode("-", $filenameArr);
	$target_file 	= $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/files/{$complete_filename}";
	//echo $complete_filename;
	// echo $target_file; exit;
	// echo $target_file; exit;
	//if called from ajax request no need to download file in browser
	if( !empty($_GET['call_type']) && $_GET['call_type'] == 'ajax' ) {
		$response = [];
		if( !empty($complete_filename) ) {

			$filename_ext   = end(explode(".", $complete_filename));

			if( $filename_ext == 'pdf' ) {
				$pdf_src = $la_settings['base_url']."data/form_{$form_id}/files/{$complete_filename}";
				$response['file_src'] = $pdf_src;
				$response['status'] = 'success';
				$response['only_download'] = false;
				$response['download_src'] = $pdf_src;
			} else {
				$ext_not_allowerd = ['zip','php','php3','php4','php5','phtml','exe','pl','cgi','html','htm','js','sh'];
				if( in_array($filename_ext, $ext_not_allowerd) ) {
					//no need to show preview for these extenstion, just download button
					$response['status'] = 'success';
					$response['download_src'] = $la_settings['base_url']."data/form_{$form_id}/files/{$complete_filename}";
					$response['only_download'] = true;
				} else {
					require_once $_SERVER['DOCUMENT_ROOT'].'/auditprotocol/class_transformDocToPdf.php';
					$docx = new TransformDocToPdf();
					$sourceFileInfo = pathinfo($complete_filename);
					$extension = $sourceFileInfo['extension'];

					$destination_dir = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$form_id}/previews";

					if (!file_exists($destination_dir))
						mkdir($destination_dir, 0777, true);

					$destination_file = $destination_dir.'/'.$sourceFileInfo['filename'].'.pdf';

					$is_file_created = $docx->transformDocument($target_file, $destination_file, '', array('outdir' => $destination_dir, 'debug' => false));
					if( $is_file_created != false ) {
						$response['file_src'] = $la_settings['base_url']."data/form_{$form_id}/previews/". $sourceFileInfo['filename'] . '.pdf';
						$response['status'] = 'success';
						$response['only_download'] = false;
						$response['download_src'] = $la_settings['base_url']."data/form_{$form_id}/files/{$complete_filename}";
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
	
	if(file_exists($target_file)){
		//prompt user to download the file
		
		// Get extension of requested file
        $extension = strtolower(substr(strrchr($filename_only, "."), 1));
        
        // Determine correct MIME type
        switch($extension){
 				case "asf":     $type = "video/x-ms-asf";                break;
                case "avi":     $type = "video/x-msvideo";               break;
                case "bin":     $type = "application/octet-stream";      break;
                case "bmp":     $type = "image/bmp";                     break;
                case "cgi":     $type = "magnus-internal/cgi";           break;
                case "css":     $type = "text/css";                      break;
                case "dcr":     $type = "application/x-director";        break;
                case "dxr":     $type = "application/x-director";        break;
                case "dll":     $type = "application/octet-stream";      break;
                case "doc":     $type = "application/msword";            break;
                case "exe":     $type = "application/octet-stream";      break;
                case "gif":     $type = "image/gif";                     break;
				case "gtar":    $type = "application/x-gtar";            break;
				case "gz":      $type = "application/gzip";              break;
				case "htm":     $type = "text/html";                     break;
				case "html":    $type = "text/html";                     break;
				case "iso":     $type = "application/octet-stream";      break;
				case "jar":     $type = "application/java-archive";      break;
				case "java":    $type = "text/x-java-source";            break;
				case "jnlp":    $type = "application/x-java-jnlp-file";  break;
				case "js":      $type = "application/x-javascript";      break;
				case "jpg":     $type = "image/jpeg";                    break;
				case "jpe":     $type = "image/jpeg";                    break;
				case "jpeg":    $type = "image/jpeg";                    break;
				case "lzh":     $type = "application/octet-stream";      break;
				case "mdb":     $type = "application/mdb";               break;
				case "mid":     $type = "audio/x-midi";                  break;
				case "midi":    $type = "audio/x-midi";                  break;
				case "mov":     $type = "video/quicktime";               break;
				case "mp2":     $type = "audio/x-mpeg";                  break;
				case "mp3":     $type = "audio/mpeg";                    break;
				case "mpg":     $type = "video/mpeg";                    break;
				case "mpe":     $type = "video/mpeg";                    break;
				case "mpeg":    $type = "video/mpeg";                    break;
				case "pdf":     $type = "application/pdf";               break;
				case "php":     $type = "application/x-httpd-php";       break;
				case "php3":    $type = "application/x-httpd-php3";      break;
				case "php4":    $type = "application/x-httpd-php";       break;
				case "png":     $type = "image/png";                     break;
				case "ppt":     $type = "application/mspowerpoint";      break;
				case "qt":      $type = "video/quicktime";               break;
				case "qti":     $type = "image/x-quicktime";             break;
				case "rar":     $type = "encoding/x-compress";           break;
				case "ra":      $type = "audio/x-pn-realaudio";          break;
				case "rm":      $type = "audio/x-pn-realaudio";          break;
				case "ram":     $type = "audio/x-pn-realaudio";          break;
				case "rtf":     $type = "application/rtf";               break;
				case "swa":     $type = "application/x-director";        break;
				case "swf":     $type = "application/x-shockwave-flash"; break;
				case "tar":     $type = "application/x-tar";             break;
				case "tgz":     $type = "application/gzip";              break;
				case "tif":     $type = "image/tiff";                    break;
				case "tiff":    $type = "image/tiff";                    break;
				case "torrent": $type = "application/x-bittorrent";      break;
				case "txt":     $type = "text/plain";                    break;
				case "wav":     $type = "audio/wav";                     break;
				case "wma":     $type = "audio/x-ms-wma";                break;
				case "wmv":     $type = "video/x-ms-wmv";                break;
				case "xls":     $type = "application/vnd.ms-excel";      break;
				case "xml":     $type = "application/xml";               break;
				case "7z":      $type = "application/x-compress";        break;
				case "zip":     $type = "application/x-zip-compressed";  break;
				default:        $type = "application/force-download";    break; 
         }
         
         // Fix IE bug [0]
         $header_file = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? preg_replace('/\./', '%2e', $filename_only, substr_count($filename_only, '.') - 1) : $filename_only;
         

         // print_r($type);die();
         //Prepare headers
         header("Pragma: public");
         header("Expires: 0");
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         header("Cache-Control: public", false);
         header("Content-Description: File Transfer");
         header("Content-Type: " . $type);
         header("Accept-Ranges: bytes");
         header("Content-Disposition: attachment; filename=\"" . addslashes($header_file) . "\"");
         header("Content-Transfer-Encoding: binary");
         header("Content-Length: " . filesize($target_file));
         
               
         // Send file for download
         if ($stream = fopen($target_file, 'rb')){
         	while(!feof($stream) && connection_status() == 0){
            	//reset time limit for big files
                @set_time_limit(0);
                print(fread($stream,1024*8));
                flush();
             }
             fclose($stream);
         }
	}else{
		echo '<br>File not found!';
	}