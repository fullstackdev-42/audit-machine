<?php

require('includes/init.php');

require('config.php');

 

require('../common-lib/web3/web3.class.php');



//ethereum
//	require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.class.php');

	$web3Ethereum = new Web3Ethereum();
	// $isEthAccount = $web3Ethereum->createAccount($newpassword); //works fine
//	$signer = '0xf2ee2a9c1ca730004afb387eb174bdaa38473650';
 

	
 //	$file_hash = '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e730';
 		
 //	$file_hash = '966a6ff2ec8deabe90845312d7b414fa6793a0969b6c8fe5e492c0e606862928';

//	$eth_account = '0xffa11dfd5a6b2c7c83dec1acccf02e583b5f6180';
	
 $entry_id = $web3Ethereum->addEntryFor('0xf4af1524ba6f58900694a45dd4e53d706deaa92407879b5f6499fd2820106434','0xc7364fb798734af23a1483d1b4940f091ff78c8c');
	
	var_dump($entry_id);
	
	echo "<pre>";
	//$web3Ethereum->call('getEntriesBySigner',LA_ETH_MAIN_ACCOUNT); //not able to get entries signed by signer
//	 $result = 	$web3Ethereum->call('getEntriesBySigner',$signer);


//	$file_hash = 'cadbb5a3a28e471deee6f31af88767e5331244017aa4b17e7b5f454e0ce544c';
 //$result_chain = $web3Ethereum->call('getEntryByDocumentHash','0x'.$file_hash);

//	 var_dump($result);
 
 var_dump($result_chain);
	 
	// print_r($result);
	// print_r($result1);

 echo $web3Ethereum->getEntryCount();



echo 'b';
