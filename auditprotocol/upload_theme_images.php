<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	session_write_close(); //close the session from init.php file first
	
	ob_start();
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/filter-functions.php');

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	$upload_success = false;
	
	$itauditmachine_data_path = '';
	$uploaded_file_height = 0;
	
	if(!empty($_FILES) && !empty($_POST['session_id'])){
		
		//get the session for this user
		$session_id  	 = trim($_POST['session_id']);
		$uploader_origin = trim($_POST['uploader_origin']);

		session_id($session_id);
		session_start();
		
		//validate the session and make sure to user is logged in
		if(empty($_SESSION['la_logged_in'])){
			die("You don't have permission to upload images");
		}

		if(!file_exists($itauditmachine_data_path.$la_settings['data_dir']."/themes/images")){
			$old_mask = umask(0);
			mkdir($itauditmachine_data_path.$la_settings['data_dir']."/themes/images",0777, true);
			umask($old_mask);
		}

		if(!is_writable($itauditmachine_data_path.$la_settings['data_dir']."/themes/images")){
			echo "Unable to write into data folder! (".$itauditmachine_data_path.$la_settings['data_dir']."/themes/images)";
		}
		
		$file_enable_type_limit = 1;
		$file_block_or_allow 	= 'a';
		$file_type_list 		= 'bmp,gif,jpg,jpe,jpeg,png,tif,tiff'; //only allow image files
			

		//validate file type
		$ext = pathinfo(strtolower($_FILES['Filedata']['name']), PATHINFO_EXTENSION);
		if(!empty($file_type_list) && !empty($file_enable_type_limit)){
		
			$file_type_array = explode(',',$file_type_list);
			array_walk($file_type_array, 'getFileTypes');
			
			if($file_block_or_allow == 'b'){
				if(in_array($ext,$file_type_array)){
					die('Error! Filetype blocked!');
				}	
			}else if($file_block_or_allow == 'a'){
				if(!in_array($ext,$file_type_array)){
					die('Error! Only image files allowed!');
				}
			}
		}
		
		$file_token = md5(uniqid(rand(), true));

		//move file and check for invalid file
		$destination_file = $itauditmachine_data_path.$la_settings['data_dir']."/themes/images/img_{$file_token}-{$_FILES['Filedata']['name']}";
		$destination_file = la_sanitize($destination_file);

		$source_file	  = $_FILES['Filedata']['tmp_name'];
		if(move_uploaded_file($source_file,$destination_file)){
			$uploaded_file_url = str_replace('/./data/', '/data/', $la_settings['base_url'].$destination_file);	
			$upload_success = true;

			//determine image height
			if(function_exists('getimagesize')){
				$image_info = getimagesize($destination_file);
				$uploaded_file_height = $image_info[1];
			}
		}else{
			$upload_success = false;
			$error_message  = "Unable to move file!";
		}
		
	}
	
	$response_data = new stdClass();
	
	if($upload_success){
		$response_data->status    	 	 = "ok";
		$response_data->image_url 	 	 = la_sanitize($uploaded_file_url);
		$response_data->image_height 	 = $uploaded_file_height;
		$response_data->uploader_origin  = $uploader_origin;
	}else{
		$response_data->status    	= "error";
		$response_data->message 	= $error_message;
	}
	
	$response_json = json_encode($response_data);
	
	echo $response_json;
	
	//we need to use output buffering to be able capturing error messages
	$output = ob_get_contents();
	ob_end_clean();
	
	echo $output;
?>