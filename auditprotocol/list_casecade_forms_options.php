<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
permission from http://www.continuumgrc.com/

More info at: http://www.continuumgrc.com/
********************************************************************************/

require('includes/init.php');

require('config.php');
require('includes/db-core.php');
require('includes/common-validator.php');
require('includes/filter-functions.php');

$dbh = la_connect_db();

$form_id = (int)la_sanitize($_POST['form_id']);

//get the list of the form, put them into array
$query = "SELECT form_name, form_id FROM ".LA_TABLE_PREFIX."forms WHERE form_active=0 or form_active=1 and form_id <> {$form_id} ORDER BY form_name ASC";

$params = array();
$sth = la_do_query($query,$params,$dbh);

$form_list_array = array();
$form_list_array[0]['form_id'] = 0;
$form_list_array[0]['form_name'] = "Select Form";

$i=1;

while($row = la_do_fetch_result($sth)){
	$form_list_array[$i]['form_id']   	  = $row['form_id'];

	if(!empty($row['form_name'])){		
		$form_list_array[$i]['form_name'] = htmlentities($row['form_name'],ENT_QUOTES)." (#{$row['form_id']})";
	}else{
		$form_list_array[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
	}
	$i++;
}

$options = '';

if(!empty($form_list_array)){
	foreach ($form_list_array as $value) {
		if($value['form_id'] == $_POST['default_value'])
			$options .= "<option value='{$value['form_id']}' selected>{$value['form_name']}</option>";
		else
			$options .= "<option value='{$value['form_id']}'>{$value['form_name']}</option>";
	}
}

$response_data = new stdClass();
$response_data->options = $options;
$response_data->csrf_token  = $_SESSION['csrf_token'];

$response_json = json_encode($response_data);

echo $response_json;
exit();