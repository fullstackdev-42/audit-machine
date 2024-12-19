<?php
/********************************************************************************
 IT Audit Machine
 
 Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	$pathSeparator = "../";
	
	require('includes/init-cron.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/users-functions.php');
	require('lib/swift-mailer/swift_required.php');


	require_once("../policymachine/classes/CreateDocx.php");
	require_once("../policymachine/classes/CreateDocxFromTemplate.php");
 
  	require('includes/docxhelper-functions.php');
 	require('includes/post-functions.php');
 	require_once("../itam-shared/includes/helper-functions.php");
 		
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);

	function addHashToBlockchain($dbh, $la_settings, $user_type){

		require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.class.php');
		$web3Ethereum = new Web3Ethereum();

		if( $user_type == 'admin' ) {
			$query = "SELECT eth.id, eth.user_id, eth.form_id, eth.data, users.eth_account FROM `".LA_TABLE_PREFIX."eth_file_data` as eth LEFT JOIN `".LA_TABLE_PREFIX."users` as users ON eth.user_id = users.user_id WHERE eth.added_to_chain != 1 AND users.eth_account IS NOT NULL AND users.eth_account != ''";
		} else {
			$query = "SELECT eth.id, eth.user_id, eth.form_id, eth.data, users.eth_account FROM `".LA_TABLE_PREFIX."eth_file_data` as eth LEFT JOIN `".LA_TABLE_PREFIX."ask_client_users` as users ON eth.user_id = users.client_user_id WHERE eth.added_to_chain != 1 AND users.eth_account IS NOT NULL AND users.eth_account != ''";
		}
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			$file_name = $row['data'];
			$form_id = $row['form_id'];
			echo $id = $row['id'];
			$user_id = $row['user_id'];
			$eth_account = $row['eth_account'];

			$form_dir = $la_settings['upload_dir']."/form_{$form_id}/files";
			echo $file_location = $form_dir.'/'.$file_name;
			echo "<br> File Hash:";
			
			if( file_exists ( $file_location ) ) {
				echo $file_hash = hash_file ( "sha256", $file_location );
				$entry_id = $web3Ethereum->addEntryFor('0x'.$file_hash, $eth_account);

				if( $entry_id != false ) {
					$query = "UPDATE `".LA_TABLE_PREFIX."eth_file_data` SET `entry_id` = :entry_id, `added_to_chain` = 1 WHERE id = :id";
					la_do_query($query,array(':entry_id' => $entry_id, ':id' => $id),$dbh);

					//add activity to audit_log table
					addUserActivity($dbh, $user_id, $form_id, 16, $file_name, time(), $_SERVER['REMOTE_ADDR']);
				} else {
					$query = "UPDATE `".LA_TABLE_PREFIX."eth_file_data` SET added_to_chain = 2 WHERE id = :id";
					la_do_query($query,array(':id' => $id),$dbh);
				}
			}
		}
	}

	if( isset($_REQUEST['c']) && (isset($_REQUEST['is_portal'])) && ($_REQUEST['c'] == 'blockchain') ){
		$user_type = 'admin';
		if( $_REQUEST['is_portal'] == 1 )
			$user_type = 'portal';

		addHashToBlockchain($dbh, $la_settings, $user_type);
	}

	if( isset($_REQUEST['c']) && ($_REQUEST['c'] == 'background_proccess') ){
		$query = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE status = 0 LIMIT 1";
		$sth = la_do_query($query,array(),$dbh);
   		$row = la_do_fetch_result($sth);

		if($row){
			
			updateDocumentProcessStatus($dbh, $row['form_id'], $row['company_user_id'], 2);
			if( $row['isAdmin'] == 1 ) {
				$params = array('dbh' => $dbh, 'form_id' => $row['form_id'], 'la_user_id' => $row['user_id'], 'company_user_id' => $row['company_user_id'], 'called_from' => 'cron');
				$zipPath = getElementWithValueArray($params);
			} else {
				$params = array('dbh' => $dbh, 'form_id' => $row['form_id'], 'company_id' => $row['company_user_id'], 'client_id' => $row['user_id'], 'called_from' => 'cron');
				$zipPath = getPortalElementWithValueArray($params);
			}
			echo  $zipPath;
			if( $zipPath ) {
				updateDocumentProcessStatus($dbh, $row['form_id'], $row['company_user_id'], 1);
			}
		}
	}