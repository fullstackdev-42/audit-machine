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

	$query 	= "select `{$field_name}` from `".LA_TABLE_PREFIX."form_{$form_id}` where id=?";
	$params = array($id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$signature_data = $row[$field_name];

	if($signature_hash != md5($signature_data)){
		die("Error. Incorrect Signature URL.");
	}

?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <title>Signature</title>
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/jquery-migrate.min.js"></script>
  <!--[if lt IE 9]><script src="js/signaturepad/flashcanvas.js"></script><![endif]-->
  <script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
  <script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
</head>
<body>
	<div id="la_sigpad" class="la_sig_wrapper">
		<canvas class="la_canvas_pad" width="309" height="260"></canvas>
	</div>
	<script type="text/javascript">
		$(function(){
			var sigpad_options = {
				drawOnly : true,
				displayOnly: true,
				bgColour: '#fff',
				penColour: '#000',
				validateFields: false
			};
			var sigpad_data = <?php echo $signature_data; ?>;
			$('#la_sigpad').signaturePad(sigpad_options).regenerate(sigpad_data);
		});
	</script>
</body>
