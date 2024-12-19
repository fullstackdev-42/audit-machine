<?php
require('../common-lib/web3/web3.php/vendor/autoload.php');

use Web3\Contract;
use Web3\Web3;


use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;


$timeout = 30;

// $web3 = new Web3('http://54.214.69.187:8501');

$web3 = new Web3(new HttpProvider(new HttpRequestManager('http://54.214.69.187:8501', $timeout)));
$signer = '0x7d3d7fd8a9d424c56bc81673f3f39b59c544788c';
$eth = $web3->eth;
 
$eth_accounts =  $eth->eth_main_account;
  
  
  
$abi = file_get_contents("HashRegistry_abi.json");
$contract = new Contract('http://54.214.69.187:8501', $abi);
$contractAddress = '0x14ea66c202b91dfde1d64e9a881bf6995f02ab1d';

// call contract function
// $contract->at($contractAddress)->call($functionName, $params, $callback);


  /*start::fetch a list of entries associated with a given signer*/
  
  $signer = '0xf2ee2a9c1ca730004afb387eb174bdaa38473650';
  $contract->at($contractAddress)->call('getEntriesBySigner', $signer, [
    'from' => $signer
  ], function ($err, $result) use ($contract) {
    if ($err !== null) {
        echo $err->getMessage();
    }
    if (isset($result)) {
      // do something with the result
        print_r($result);
    }
  });
  die();
  /*end::fetch a list of entries associated with a given signer*/


  /*start::send ether to account*/


    $fromAccount = '0xf2ee2a9c1ca730004afb387eb174bdaa38473650';
    $toAccount = '0x8ca2a1be3e0b42795cd601c50eac11e3151fd138';


    $personal->unlockAccount($fromAccount, 'roller-051-standby-957-octagon', function ($err, $unlocked) {
            if ($err !== null) {
                echo 'Error : '.$err->getMessage();
            }
            if ( $unlocked ) {
                print_r($unlocked);
            }
            
        });
    // die();

    // $eth->sendTransaction([
    //     'from' => $fromAccount,
    //     'to' => $toAccount,
    //     'value' => '0x100000'
    // ], function ($err, $transaction) use ($eth, $fromAccount, $toAccount) {
    //     if ($err !== null) {
    //         echo 'Error : ' . $err->getMessage();
    //         return;
    //     }
    //     echo 'Tx hash: ' . $transaction . PHP_EOL;

    //     // get balance
    //     $eth->getBalance($fromAccount, function ($err, $balance) use($fromAccount) {
    //         if ($err !== null) {
    //             echo 'Error: ' . $err->getMessage();
    //             return;
    //         }
    //         echo $fromAccount . ' Balance: ' . $balance . PHP_EOL;
    //     });
    //     $eth->getBalance($toAccount, function ($err, $balance) use($toAccount) {
    //         if ($err !== null) {
    //             echo 'Error: ' . $err->getMessage();
    //             return;
    //         }
    //         echo '<br>'.$toAccount . ' Balance: ' . $balance . PHP_EOL;
    //     });
    // });

    /*end::send ether to account*/


    /*start::lock and unlock account*/
        /*$personal->unlockAccount($this->newAccount, '123456', function ($err, $unlocked) {
            if ($err !== null) {
                return $this->fail($err->getMessage());
            }
            $this->assertTrue($unlocked);
        });
        //other way
        $personal->unlockAccount($this->newAccount, '123456', 100, function ($err, $unlocked) {
            if ($err !== null) {
                return $this->fail($err->getMessage());
            }
            $this->assertTrue($unlocked);
        });

        $personal->lockAccount($this->newAccount, function ($err, $locked) {
            if ($err !== null) {
                return $this->fail($err->getMessage());
            }
            $this->assertTrue($locked);
        });*/
    /*end::lock and unlock account*/


  /*start:: to sign a new hash entry*/
  $hash = '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e730';

  try {
      $contract->at($contractAddress)->send("addEntry", $hash, [
        'from' => $signer,
        'gas' => '0x200b20'
      ], function ($err, $result) use ($contract, $signer) {
        if ($err !== null) {
          echo $err->getMessage();
        }
        if ($result) {
            echo "\nTransaction was submitted. Transaction ID: " . $result . "\n";
        }
        $transactionId = $result;  // you may want to store the Transaction ID
        $contract->eth->getTransactionReceipt($transactionId, function ($err, $transaction) use ($signer, $contract) {
          if ($err !== null) {
            return $this->fail($err);
          }
          if ($transaction) {
            echo "\nTransaction has been mined. Block Number: " . $transaction->blockNumber . "\n";
            // any events or data returned by the contract will be available here
          }
        });
      });
    } catch (Exception $e) {
        echo '<br>Caught exception: ',  $e->getMessage(), "\n";
    }