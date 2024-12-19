<?php
require('includes/init.php');

require('config.php');

require('includes/db-core.php');

$dbh = la_connect_db();

$tstamp = time();

$query_alter = "ALTER TABLE `ap_background_document_proccesses` ADD COLUMN `entry_id` INT(11) NOT NULL AFTER `company_user_id`";
la_do_query($query_alter, array(), $dbh);

$query_update = "UPDATE `ap_background_document_proccesses` SET `entry_id` = ?";
la_do_query($query_update, array($tstamp), $dbh);

$query_alter = "ALTER TABLE `ap_element_status_indicator` ADD COLUMN `entry_id` INT(11) NOT NULL AFTER `company_id`";
la_do_query($query_alter, array(), $dbh);

$query_update = "UPDATE `ap_element_status_indicator` SET `entry_id` = ?";
la_do_query($query_update, array($tstamp), $dbh);

$query_alter = "ALTER TABLE `ap_template_document_creation` ADD COLUMN `entry_id` INT(11) NOT NULL AFTER `company_id`";
la_do_query($query_alter, array(), $dbh);

$query_update = "UPDATE `ap_template_document_creation` SET `entry_id` = ?";
la_do_query($query_update, array($tstamp), $dbh);

//get the list of the form
$query = "SELECT `form_id` FROM	`ap_forms`";
$sth = la_do_query($query, array(), $dbh);
while($row = la_do_fetch_result($sth)) {
	$table_exists = "SELECT count(*) AS counter FROM information_schema.tables WHERE table_schema = '".LA_DB_NAME."' AND table_name = 'ap_form_{$row['form_id']}'";
	$result_table_exists = la_do_query($table_exists,array(),$dbh);
	$row_table_exists = la_do_fetch_result($result_table_exists);
	if($row_table_exists['counter'] > 0) {
		$query_alter = "ALTER TABLE `ap_form_{$row['form_id']}` ADD COLUMN `entry_id` INT(11) NOT NULL AFTER `company_id`";
		la_do_query($query_alter, array(), $dbh);

		$query_update = "UPDATE `ap_form_{$row['form_id']}` SET `entry_id` = ?";
		la_do_query($query_update, array($tstamp), $dbh);

		//check if `unique_row_data` exists in the table
		$query_exists = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = 'ap_form_{$row['form_id']}' AND `COLUMN_NAME` = 'unique_row_data'";		
		$sth_exists = la_do_query($query_exists, array(), $dbh);
		$row_exists = la_do_fetch_result($sth_exists);
		
		if(!$row_exists['COLUMN_NAME']){
			$query_alter = "ALTER TABLE `ap_form_{$row['form_id']}` ADD `unique_row_data` VARCHAR(64) NOT NULL , ADD UNIQUE (`unique_row_data`);";
			la_do_query($query_alter, array(), $dbh);
		}

		//delete the duplicated rows
		$query_delete = "DELETE form_1 FROM `ap_form_{$row['form_id']}` form_1, `ap_form_{$row['form_id']}` form_2 WHERE form_1.id < form_2.id AND form_1.company_id = form_2.company_id AND form_1.entry_id = form_2.entry_id AND form_1.field_name = form_2.field_name";
		la_do_query($query_delete, array(), $dbh);

		$query_update = "UPDATE `ap_form_{$row['form_id']}` SET `unique_row_data` = CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`)";
		la_do_query($query_update, array(), $dbh);
	}
}

?>