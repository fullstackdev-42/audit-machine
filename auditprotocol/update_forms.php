<?php

require('includes/init.php');
header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");
require('config.php');
require('includes/language.php');
require('includes/db-core.php');

$dbh = la_connect_db();

$query = "select `form_id` from `".LA_TABLE_PREFIX."forms`";
$result = la_do_query($query, array(), $dbh);

while($row = la_do_fetch_result($result)){
		
	$query_alter = "ALTER TABLE `".LA_TABLE_PREFIX."form_{$row['form_id']}` ADD `element_machine_code` VARCHAR(999) NULL";
	$sth_alter = $dbh->prepare($query_alter);
	try{
		$sth_alter->execute(array());
	}catch(PDOException $e) {
		echo $e->getMessage();
		echo "<br>";
	}
}