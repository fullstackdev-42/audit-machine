<?php
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/common-validator.php');
require('includes/filter-functions.php');


$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

function updateCSS($data_dir, $form_id){
	if(file_exists($data_dir."/form_{$form_id}/css/view.css")){
		unlink($data_dir."/form_{$form_id}/css/view.css");
	}

	// need to update css file for rich text feature
	copy("./view.css",$data_dir."/form_{$form_id}/css/view.css");
}

$query = "select `form_id` from `".LA_TABLE_PREFIX."forms` where form_active = '1'";
$result = la_do_query($query, array(), $dbh);
while($row = la_do_fetch_result($result)){
	updateCSS($la_settings['data_dir'], $row['form_id']);
	echo "Form# {$row['form_id']} css file updated successfully.<br/>";
}