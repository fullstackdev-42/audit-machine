<?php
echo 'a';

require($_SERVER['DOCUMENT_ROOT'].'/auditprotocol/includes/init.php');
	
require($_SERVER['DOCUMENT_ROOT'].'/auditprotocol/config.php');

//ethereum
	require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.class.php');

	$web3Ethereum = new Web3Ethereum();
	// $isEthAccount = $web3Ethereum->createAccount($newpassword); //works fine
	$signer = '0x7d3d7fd8a9d424c56bc81673f3f39b59c544788c';
 
	echo "<pre>";
	//$web3Ethereum->call('getEntriesBySigner',LA_ETH_MAIN_ACCOUNT); //not able to get entries signed by signer
	// $web3Ethereum->call('getEntriesBySigner',$signer);
	// print_r($result);
	// print_r($result1);

	echo $web3Ethereum->getEntryCount();



echo 'b';
