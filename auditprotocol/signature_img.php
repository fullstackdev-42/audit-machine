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
	require('lib/signature-to-image.php');
	
	//get query string and parse it, query string is base64 encoded
	
	$query_string = trim($_GET['q']);
	parse_str(base64_decode($query_string),$params);
	
	$form_id 	= (int) $params['form_id'];
	$id      	= (int) $params['id'];
	$field_name = str_replace(array("`","'",';'), '', $params['el']);
	$signature_hash  = $params['hash'];
	
	
	if(empty($form_id) || empty($id) || empty($field_name) || empty($signature_hash)){
		die("Error. Incorrect URL.");
	}


	$dbh = la_connect_db();

	$query 	= "select data_value from `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name='{$field_name}' AND company_id=".$id;
	$params = array();

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$signature_data = $row['data_value'];

	if($signature_hash != md5($signature_data)){
		die("Error. Incorrect Signature URL.");
	}

	//get signature height
	$exploded = explode('_', $field_name);
	$element_id = (int) $exploded[1];

	$query  = "select element_size from ".LA_TABLE_PREFIX."form_elements where form_id = ? and element_id = ?";
	$params = array($form_id,$element_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$element_size = $row['element_size'];
	if($element_size == 'small'){
		$signature_height = 70;		
	}else if($element_size == 'medium'){
		$signature_height = 130;
	}else{
		$signature_height = 260;
	}

	$signature_options['imageSize'] = array(309,$signature_height);
	$signature_options['penColour'] = array(0x00, 0x00, 0x00);
	$signature_img = sigJsonToImage($signature_data,$signature_options);

	// Output to browser
	header('Content-Type: image/png');
	imagepng($signature_img);

	// Destroy the image in memory when complete
	imagedestroy($signature_img);

?>
