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
	require_once('../policymachine/dompdf/autoload.inc.php');
	use Dompdf\Dompdf;
	use Dompdf\Options;
	//require('includes/check-session.php');

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	//get form name
	$form_name = "Untitled Form";
	$form_id = $_POST['form_id'];
	$query = "SELECT `form_name` FROM ".LA_TABLE_PREFIX."forms WHERE `form_id` = ?";
	$sth = la_do_query($query, array($form_id), $dbh);
	$row = la_do_fetch_result($sth);
	if(!empty($row)){
		$form_full_name = $row['form_name'];
		$form_name = htmlspecialchars($form_full_name);
	}

	$form_details = html_entity_decode($_POST['form_details']);
	$content  = "
		<html>
			<head>
				<link rel='stylesheet' type='text/css' href='css/main.css'>
				<style>
					.score-span {
						float: right;
						font-weight: bold;
						padding-left: 30px;
					}

					.status-icon{
						vertical-align: middle;
						width:10px;
					}
					.deny-entry {
						width: 48px;
					    text-align: center;
					    margin-top: 5px;
					}
					#ve_table_info {
						color: #000000;
					}
					.highcharts-container {
						margin: 0 auto;
					}
					.highcharts-credits {
						display: none!important;
					}
					.ve_detail_table {
						color: #000;
					}
					.view_entry {
						min-height: 500px;
					}
					.breadcrumb {
						border: none!important;
					}
					.small_loader_box {
						padding-top: 7px!important;
					}
				</style>
			</head>
			<body>
				<div>
					<h3>".$form_name."</h3>
				</div>
				<div>".$form_details."</div>
			</body>
		</html>";
	$content = str_replace($la_settings['base_url'], "", $content);
	$options = new Options();
	$options->set('isRemoteEnabled', TRUE);
	$options->set('chroot', $_SERVER["DOCUMENT_ROOT"]."/auditprotocol");
	$dompdf = new Dompdf($options);
	
	$dompdf->loadHtml($content);
	$dompdf->setPaper('A4', 'landscape');
	$dompdf->render();
	$pdf_gen = $dompdf->output();

	$file_name = "form_data_for_{$form_name}.pdf";
	mkdir($_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/", 0777, true);
	$file_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/temp/{$file_name}";

	if(!file_put_contents($file_path, $pdf_gen)){
		echo json_encode(array("status" => "error"));
		exit();
	} else {
		$download_path = "/auditprotocol/data/temp/{$file_name}";
		echo json_encode(array("download_path" => $download_path, "file_name" => $file_name, "status" => "ok"));
		exit();
	}