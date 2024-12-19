<?php

require('../config.php');
require('db-core.php');

$dbh = la_connect_db();

$form_id = $_GET['form_id'];
$element_machine_code = $_GET['element_machine_code'];
$id = $_GET['id'];

$query = <<<EOT
		SELECT fe.form_id, fe.element_id, fe.element_title, fe.id, fe.element_page_number, f.form_name FROM ap_form_elements AS fe 
		JOIN ap_forms AS f ON (fe.form_id = f.form_id)
		WHERE f.form_id != ? AND fe.element_machine_code IS NOT NULL AND fe.element_machine_code != "" AND fe.element_machine_code = ? AND fe.element_status='1' AND fe.element_type <> 'page_break'
		EOT;
		
$sth = la_do_query($query, [$form_id, $element_machine_code], $dbh);
$mapping_list = [];
while($row = la_do_fetch_result($sth)){
	array_push($mapping_list, $row);
}

$result = "";
if ($mapping_list) {
	$result .= "<div class=\"keystoneviewer guidelines\" style=\"left: -290px\">";
	$result .= "<p style=\"padding: 9px; margin:0\">Auto-Mapped Relationships</p>";
	$result .= "<hr style=\"display: block; margin: 0\"/>";
	$result .= "<ul>";
	foreach ($mapping_list as $item){
		$result .= "<li><a href='/auditprotocol/view.php?id=".$item['form_id']."&la_page=".$item['element_page_number']."&element_id_auto=".$item['id']."' target='_blank' style='margin: 0'>";
		$result .= "<small>Form:".$item['form_name'].", Element: ".$item['element_title']."</small>";
		$result .= "</a></li>";
	}
	$result .= "</ul></div>";
}

echo $result;exit;

?>