<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 In development by matt wood
 ********************************************************************************/	
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('lib/signature-to-image.php');
	
	//get query string and parse it, query string is base64 encoded
	
	$signature_id = trim($_GET['q']);
	parse_str(base64_decode($signature_id),$params);

	$signature_hash  = $params['signature_hash'];
	

	if(empty($signature_hash)){
		die("Error. Incorrect URL.");
	}

	$dbh = la_connect_db();

	// $query 	= "select data_value from `".LA_TABLE_PREFIX."digital_signatures` WHERE field_name='{$field_name}' AND company_id=".$id;
	// $params = array();

	// $sth = la_do_query($query,$params,$dbh);
	// $row = la_do_fetch_result($sth);
	// $signature_data = $row['data_value'];
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."digital_signatures WHERE `signature_id`=?";
	$sth = la_do_query($query, array($signature_id), $dbh);
	$row = la_do_fetch_result($sth);

	$signature_data = $row['signature_data'];

	if($signature_hash != md5($signature_data)){
		die("Error. Incorrect Signature URL.");
	}

	if ($row['signature_type']=="type") {
		// Set the content-type
		header('Content-Type: image/png');

		// Create the image
		$im = imagecreatetruecolor(309, 130);

		// Create some colors
		$white = imagecolorallocate($im, 255, 255, 255);
		$grey = imagecolorallocate($im, 128, 128, 128);
		$black = imagecolorallocate($im, 20, 20, 20);
		imagefilledrectangle($im, 0, 0, 308, 129, $white);

		$text = $row['signature_data'];
		$font = dirname(__FILE__) . '/digital.ttf';
	
		// Add the text
		$result = imagettftext($im, 28, 0, 10, 70, $black, $font, $text);

		// Using imagepng() results in clearer text compared with imagejpeg()
		imagepng($im);
		imagedestroy($im);
	} else if ($row['signature_type']=="draw") {
		$signature_options['imageSize'] = array(309,130);
		$signature_options['penColour'] = array(0x00, 0x00, 0x00);
		$signature_img = sigJsonToImage($signature_data,$signature_options);

		// Output to browser
		header('Content-Type: image/png');
		imagepng($signature_img);

		// Destroy the image in memory when complete
		imagedestroy($signature_img);
	} else if ($row['signature_type']=="image") {
		$signature_data = explode(",", $signature_data);
		$signature_data = base64_decode($signature_data[1]);
		$im = imagecreatefromstring($signature_data);
		header('Content-Type: image/png');
		imagepng($im);
		imagedestroy($im);
	}

?>
