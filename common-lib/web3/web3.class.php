<?php

require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.php/vendor/autoload.php');
use Web3\Contract;
use Web3\Web3;

use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

class Web3Ethereum {

	private $eth_host;
	private $eth_password;
	private $eth_main_account;
	private $eth_contractAddress;
	private $personal;
	private $web3;
	private $abi;
	private $contract;

	public function __construct() {
		$this->eth_host = LA_ETH_HOST;
		$this->eth_password = LA_ETH_PASSWORD;
		$this->eth_main_account = LA_ETH_MAIN_ACCOUNT;
		$this->eth_contractAddress = LA_ETH_CONTRACTADDRESS;

		$timeout = 30;
		$this->web3 = new Web3(new HttpProvider(new HttpRequestManager($this->eth_host, $timeout)));

		$this->abi = file_get_contents($_SERVER['DOCUMENT_ROOT']."/common-lib/web3/HashRegistry_abi.json");
		$this->contract = new Contract($this->eth_host, $this->abi);
		$this->personal = $this->web3->personal;
	}

	public function createAccount($password) {
		$newAccount = '';
		$this->personal->newAccount($password, function ($err, $account) use (&$newAccount) {

	        if ($err !== null) {
	            // echo 'Error: ' . $err->getMessage();
	            return false;
	        }
	        $newAccount = $account;
	    });
	    // echo '$newAccount:-'.$newAccount;

	    return ( !empty($newAccount) ) ? $newAccount : false;
	}

	public function getNetworkId() {
		$this->web3->net->version(function ($error, $id) {
			if ($err !== null) {
                echo 'Error : '.$err->getMessage();
            }
            print_r($id);
		});
	}

	public function getTransactionCountForAddress($address) {
		
		$this->web3->eth->getTransactionCount($address, function ($error, $count) use ($eth) {
			if ($err !== null) {
                echo 'Error : '.$err->getMessage();
            }
            print_r($count);
		});
	}


	public function unlockAccount() {
		$account_unlocked = '';
		$this->personal->unlockAccount($this->eth_main_account, $this->eth_password, function ($err, $unlocked) use (&$account_unlocked) {
            if ($err !== null) {
                echo 'Error : '.$err->getMessage();
            }
            $account_unlocked = $unlocked;
            
        });

     	return ( !empty($account_unlocked) ) ? $account_unlocked : false;   
	}

	public function lockAccount() {
		$account_locked = '';
		$this->personal->lockAccount($this->eth_main_account, function ($err, $locked) use (&$account_locked) {
            if ($err !== null) {
                echo 'Error : '.$err->getMessage();
            }

            $account_locked = $locked;
            
        });

     	return ( !empty($account_locked) ) ? $account_locked : false;
	}

	public function addEntryFor($hash, $signer) {
		$entryId = $blockNumber = '';

		try {
			$unlockAccount = $this->unlockAccount();
			if( $unlockAccount ) {
				$this->contract->at($this->eth_contractAddress)->send("addEntryFor", $hash, $signer, [
					'from' => $this->eth_main_account,
					'gas' => '0x200b20'
				], function ($err, $result) use ($contract, $signer, &$entryId) {
					if ($err !== null) {
						echo $err->getMessage();
					}
					if ($result) {
						// echo "\nTransaction was submitted. Transaction ID: " . $result . "\n";
						$entryId = $result;  // store the Transaction ID
					}
					
					// print_r($result);
					/*if( $transactionId ) {
						echo "in if";
						$this->contract->eth->getTransactionReceipt($transactionId, function ($err, $transaction) use ($signer, $contract) {
							if ($err !== null) {
								echo $err->getMessage();
							}
							
							var_dump($transaction);
							echo "\nTransaction has been mined. Block Number: " . $transaction->blockNumber . "\n";
							
						});
					}*/
				});
				echo '$entryId:-'.$entryId;
				return ( !empty($entryId) ) ? $entryId : false;
			} else {
				//not able to unlock account
				return false;
			}

		} catch (Exception $e) {
			// echo '<br>Caught exception: ',  $e->getMessage(), "\n";
			return false;
		}		
	}

	/*
	 * use function to call authorizeSigner, deauthorizeSigner, authorizeAdmin, deauthorizeAdmin

	*/
	public function send($function_name, $address) {

		$allowed_functions = ['authorizeSigner', 'deauthorizeSigner', 'authorizeAdmin', 'deauthorizeAdmin'];

		if( !in_array($function_name, $allowed_functions) )
			return 'Function is not in allow list';

		$transactionId = '';

		try {
			$unlockAccount = $this->unlockAccount();
			if( $unlockAccount ) {
				$this->contract->at($this->eth_contractAddress)->send($function_name, $address, [
					'from' => $this->eth_main_account,
					'gas' => '0x2F4D60'
				], function ($err, $result) use (&$transactionId) {
					if ($err !== null) {
						echo $err->getMessage();
					}
					if ($result) {
						echo "\nTransaction was submitted. Transaction ID: " . $result . "\n";
						$transactionId = $result;
					}
				});
				// echo '$transactionId:-'.$transactionId;
				return ( !empty($transactionId) ) ? $transactionId : false;
			} else {
				return false;
			}

		} catch (Exception $e) {
			echo '<br>Caught exception: ',  $e->getMessage(), "\n";
			return false;
		}
	}


	/*
	 * function is being used to call getEntriesBySigner, getEntryByDocumentHash, getEntryById
	*/
	public function call($function_name, $param) {
		$return_result = '';
		$allowed_functions = ['getEntriesBySigner', 'getEntryByDocumentHash', 'getEntryById', 'getEntryById', 'getAuthorizedSigner', 'getAuthorizedAdmin'];

		if( !in_array($function_name, $allowed_functions) )
			return 'Function is not in allow list';

		try {
			$unlockAccount = $this->unlockAccount();
			if( $unlockAccount ) {

				// print_r($function_name);
				// print_r($param);

				$this->contract->at($this->eth_contractAddress)->call($function_name, $param, function ($err, $result) use (&$return_result){
					if ($err !== null) {
						echo $err->getMessage();
					}
					$return_result = $result;
					
				});
				return ( !empty($return_result) ) ? $return_result : false;
			} else {
				return false;
			}

		} catch (Exception $e) {
			echo '<br>Caught exception: ',  $e->getMessage(), "\n";
			// return false;
		}
	}

	public function addEntry($hash) {
		$transactionId = $blockNumber = '';

		try {
			$unlockAccount = $this->unlockAccount();
			if( $unlockAccount ) {
				$this->contract->at($this->eth_contractAddress)->send("addEntry", $hash, [
					'from' => $this->eth_main_account,
					'gas' => '0x200b20'
				], function ($err, $result) use ($contract, &$transactionId) {
					if ($err !== null) {
						echo $err->getMessage();
					}
					if ($result) {
						echo "\nTransaction was submitted. Transaction ID: " . $result . "\n";
					}
					
					$transactionId = $result;  // store the Transaction ID
					/*if( $transactionId ) {
						$this->contract->eth->getTransactionReceipt($transactionId, function ($err, $transaction) use ($contract) {
							if ($err !== null) {
								echo $err->getMessage();
							}
							
							var_dump($transaction);
							echo "\nTransaction has been mined. Block Number: " . $transaction->blockNumber . "\n";
							
						});
					}*/
				});
				echo '$transactionId:-'.$transactionId;
				return ( !empty($transactionId) ) ? $transactionId : false;
			} else {
				//not able to unlock account
				return false;
			}

		} catch (Exception $e) {
			echo '<br>Caught exception: ',  $e->getMessage(), "\n";
			return false;
		}		
	}


	public function getTransactionByHash($transactionId) {
		
		$transaction_details = '';
		$this->contract->eth->getTransactionByHash($transactionId, function ($err, $transaction) use ($signer, $contract, &$transaction_details) {
			if ($err !== null) {
				echo $err->getMessage();
			}
			
			$transaction_details = $transaction;
			print_r($transaction);
			
		});
		return ( !empty($transaction_details) ) ? $transaction_details : false;
	}

	public function getTransactionReceipt($transactionId) {
		$transaction_details = '';
		$this->contract->eth->getTransactionReceipt($transactionId, function ($err, $transaction) use ($signer, $contract, &$transaction_details) {
			if ($err !== null) {
				echo $err->getMessage();
			}
			
			// echo "\nTransaction has been mined. Block Number: " . $transaction->blockNumber . "\n";
			$transaction_details = $transaction;
			print_r($transaction_details);
		});

		return ( !empty($transaction_details) ) ? $transaction_details : false;
	}

	/*public function getEntriesBySigner($signer) {
		$entries = [];
		$unlockAccount = $this->unlockAccount();
		if( $unlockAccount ) {

			$this->contract->at($this->eth_contractAddress)->call('getEntriesBySigner', $signer, [
				'from' => $this->eth_main_account
			], function ($err, $result) use ($contract, &$entries) {
				if ($err !== null) {
					echo $err->getMessage();
					return false;
				}
				print_r($result);
				// do something with the result
				$entries = $result;
			});
		}

		return ( !empty($entries) ) ? $entries : false;
	}*/
	
	public function getEntryCount() {
	// call contract function

		$this->contract->at($this->eth_contractAddress)->call('getEntryCount', [
	    	'from' => $this->eth_main_account
	  	], function ($err, $result) use ($contract) {
	    	if ($err !== null) {
	      		echo $err->getMessage();
	    	}
	    	print_r($result);
	    	print_r($result['entryCount']->toString());
	  	});
	}

	// estimate function gas
	public function getEstimateGas($functionName, $params) {

		$this->contract->at($this->eth_contractAddress)->estimateGas($functionName, $params
	  		, function ($err, $result) {
	    	if ($err !== null) {
	      		echo $err->getMessage();
	    	}
	    	print_r($result);
	  	});
	}
	
	
}