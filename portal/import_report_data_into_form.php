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
	require('includes/filter-functions.php');
	
	$dbh = la_connect_db();
	if(empty($_POST['form_id'])){
		die("Error! You can't open this file directly");
	}

	//check for max_input_vars
	la_init_max_input_vars();

	$form_id = (int) trim($_POST['form_id']);
	$entry_id = time();
	if($form_id == 0){
		die("Form is not available.");
	} else {
		$query = "CREATE TABLE IF NOT EXISTS `".LA_TABLE_PREFIX."form_{$form_id}` (
  													`id` int(11) NOT NULL auto_increment,
  													`company_id` int(11) NOT NULL,
  													`entry_id` int(11) NOT NULL,
  													`field_name` varchar(200) NOT NULL,
  													`field_code` varchar(50) NOT NULL,
  													`data_value` longtext NOT NULL,
  													`field_score` text NOT NULL,
													`form_resume_enable` int(11) NOT NULL,
													`unique_row_data` varchar(64) NOT NULL,
													`submitted_from` int(1) NOT NULL,
													`other_info` text NOT NULL,
													`element_machine_code` varchar(100) NULL,
  													PRIMARY KEY (`id`),
													UNIQUE KEY `unique_row_data` (`unique_row_data`)
  													) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		$params = array();
		la_do_query($query, $params, $dbh);

		$user_type = $_POST["user_info"]["user_type"];
		$user_id = $_POST["user_info"]["user_id"];
		if($user_type == "admin") {
			$user_id = time();
		}
		$elements = $_POST['data'];

		$tmpQryArr = array();
		$tmpDatArr = array();
		$query_insert_or_update = "INSERT INTO `".LA_TABLE_PREFIX."form_{$form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `field_score`, `unique_row_data`) VALUES ";
		foreach ($elements as $element) {
			$query_get_no_element = "SELECT count(`element_id`) total_row FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id=? AND element_machine_code=? AND element_status=1 AND (element_type ='text' OR element_type ='textarea')";
			$sth_get_no_element	= la_do_query($query_get_no_element, array($form_id, $element["template_code"]), $dbh);
			$row_get_no_element = la_do_fetch_result($sth_get_no_element);
			if($row_get_no_element['total_row'] == 1){
				$query_get_element_id = "SELECT `element_id` FROM ".LA_TABLE_PREFIX."form_elements WHERE form_id=? AND element_machine_code=? AND element_status=1 AND (element_type ='text' OR element_type ='textarea')";
				$sth_get_element_id	= la_do_query($query_get_element_id, array($form_id, $element["template_code"]), $dbh);
				$row_get_element_id = la_do_fetch_result($sth_get_element_id);
				array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`))");
				$tmpDatArr = array_merge($tmpDatArr, array($user_id, $entry_id, "element_{$row_get_element_id['element_id']}", "code_{$row_get_element_id['element_id']}", $element["value"], ""));
			}
		}
		array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`))");
		array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`))");
		array_push($tmpQryArr, "(NULL, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`))");
			
		$tmpDatArr = array_merge($tmpDatArr, array($user_id, $entry_id, "ip_address", "", $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_ADDR']));
		$tmpDatArr = array_merge($tmpDatArr, array($user_id, $entry_id, "status", "", 1, ""));
		$tmpDatArr = array_merge($tmpDatArr, array($user_id, $entry_id, "date_created", "", date("Y-m-d H:i:s"), date("Y-m-d H:i:s")));
		$query_insert_or_update .= implode(",", $tmpQryArr);
		$query_insert_or_update .= " ON DUPLICATE KEY UPDATE `data_value` = VALUES(`data_value`), `field_score` = IF(VALUES(`field_score`) = '', NULL, CONCAT(`field_score`, ',' ,VALUES(`field_score`)));";
		la_do_query($query_insert_or_update, $tmpDatArr, $dbh);
		echo '{ "status" : "ok", "form_id" : "'.$form_id.'", "entry_id" : "'.$entry_id.'" }';
	}