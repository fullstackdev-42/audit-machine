<?php
/********************************************************************************
IT Audit Machine

Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
permission from http://lazarusalliance.com/

More info at: http://lazarusalliance.com/
********************************************************************************/
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/users-functions.php');
require('lib/swift-mailer/swift_required.php');

$dbh 		 = la_connect_db();
$la_settings = la_get_settings($dbh);

function deleteUnsedForms($dbh, $la_settings){
	$withoutTableStack = array();

	// select all forms whose status is 1
	$query = "select `form_id`, `form_name` from `".LA_TABLE_PREFIX."forms` where `form_active` = :form_active";
	$sth = la_do_query($query,array(':form_active' => 1),$dbh);
	while($row = la_do_fetch_result($sth)){
		// select all forms whose status is 1
		$queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$row['form_id']}'";
		$resultFormTable = la_do_query($queryFormTable,array(),$dbh);
		$rowFormTable    = la_do_fetch_result($resultFormTable);
		
		if(!$rowFormTable){
			$withoutTableStack[$row['form_id']] = $row['form_name']." - ".$row['form_id'];
		}
	}

	$body = "<h4>List of FormId# without dbtable</h4>";
	$body   .= "<hr />";
	$body   .= "<table><tr><td>".implode("</td><td>", $withoutTableStack)."</td></tr></table>";

	if(count($withoutTableStack) == 0){
	  	$body = "<h4>No form found</h4>";
	}else{
	  
	  //send the message
	}

	echo $body;
}

deleteUnsedForms($dbh, $la_settings);