<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2016 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com 
 ********************************************************************************/

require('includes/init.php');
header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
require('config.php');
require('includes/language.php');
require('includes/db-core.php');

$dbh = la_connect_db();

$query = "select `form_id` from `".LA_TABLE_PREFIX."forms` where `form_active` = :form_active";
$result = la_do_query($query, array(':form_active' => 1), $dbh);

while($row = la_do_fetch_result($result)){

	$query_col = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".LA_TABLE_PREFIX."form_{$row['form_id']}' AND `COLUMN_NAME` = 'unique_row_data'";		
	$sth_col = la_do_query($query_col, array(), $dbh);
	$row_col = la_do_fetch_result($sth_col);
	
	if(!$row_col){
		
		$query_drop_copy_tbl = "DROP TABLE IF EXISTS `".LA_TABLE_PREFIX."form_{$row['form_id']}_copy`";
		la_do_query($query_drop_copy_tbl, array(), $dbh);
		
		$query_create_copy = "CREATE TABLE `".LA_TABLE_PREFIX."form_{$row['form_id']}_copy` LIKE `".LA_TABLE_PREFIX."form_{$row['form_id']}`";
		la_do_query($query_create_copy, array(), $dbh);
		
		$query_insert_into_copy = "INSERT `".LA_TABLE_PREFIX."form_{$row['form_id']}_copy` SELECT * FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}`";
		la_do_query($query_insert_into_copy, array(), $dbh);
		
		$query_trunc = "TRUNCATE TABLE ".LA_TABLE_PREFIX."form_{$row['form_id']}";
		la_do_query($query_trunc, array(), $dbh);
		
		$query_alter = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$row['form_id']}` ADD `unique_row_data` VARCHAR(64) NOT NULL , ADD UNIQUE (`unique_row_data`);";
		la_do_query($query_alter, array(), $dbh);
		
		$query_insert_into_copy = "INSERT INTO `".LA_TABLE_PREFIX."form_{$row['form_id']}` SELECT *, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`) `unique_row_data` FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}_copy`;";
		la_do_query($query_insert_into_copy, array(), $dbh);
		
		$query_drop_copy_tbl = "DROP TABLE IF EXISTS `".LA_TABLE_PREFIX."form_{$row['form_id']}_copy`";
		la_do_query($query_drop_copy_tbl, array(), $dbh);
		
	}
	
}