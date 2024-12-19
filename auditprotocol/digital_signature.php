<?php

/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
*********************************************************************************/
 
require('includes/init.php');

require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/filter-functions.php');
require('lib/swift-mailer/swift_required.php');
require('lib/password-hash.php');

$ssl_suffix = la_get_ssl_suffix();

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$signer_id = $_SESSION['la_client_user_id'];
$client_id = $_SESSION['la_client_client_id'];
$tstamp = date('Y-m-d H:i:s');

$user_data = getUserDetailsFromId($dbh, $signer_id);
$signer_full_name = $user_data['full_name'];

if(isset($_POST['action'])){
    if ($_POST['action'] == 'sign') {
        $result = "";
        $form_id = $_POST['form_id'];

        //get the latest signature information from DB
		$query = "SELECT * FROM ".LA_TABLE_PREFIX."digital_signatures WHERE `id`=(SELECT MAX(id) FROM ".LA_TABLE_PREFIX."digital_signatures WHERE user_id=?)";
		$sth = la_do_query($query, array($signer_id), $dbh);
		$res = la_do_fetch_result($sth);
        if (isset($res)) {
			$signature_id = la_sanitize($res["signature_id"]);
		}

        //get the current sign information from DB
        $query = "SELECT * FROM ".LA_TABLE_PREFIX."signed_forms WHERE client_id=? and form_id=?";
		$sth = la_do_query($query, array($client_id, $form_id), $dbh);
		$res = la_do_fetch_result($sth);

		if (isset($res)) {
			$cur_signed_signature_id = la_sanitize($res["signature_id"]);
		}

        if (isset($signature_id)) {
            if (isset($cur_signed_signature_id)) {
                $query =   "UPDATE `".LA_TABLE_PREFIX."signed_forms` 
                SET `signature_id`= ?, `created_at` = ?
                WHERE `form_id` = ? AND `signer_id` = ? AND `client_id` = ?";
                $params = array(
                    $signature_id,
                    $tstamp,
                    $form_id,
                    $signer_id,
                    $client_id
                );
                la_do_query($query,$params,$dbh);
            } else {
                $query = "INSERT INTO 
                `".LA_TABLE_PREFIX."signed_forms`( 
                            `form_id`,
                            `signer_id`,
                            `client_id`,
                            `signature_id`,
                            `created_at`)
                VALUES (?, ?, ?, ?, ?);";
                $params = array(
                            $form_id,
                            $signer_id,
                            $client_id,
                            $signature_id,
                            $tstamp
                        );
                la_do_query($query,$params,$dbh);
            }
            $result = array(
                "status" => "ok",
                "signature_id" => $signature_id,
                "created_at" => $tstamp,
                "signer_full_name" => $signer_full_name
            );
        } else {
            $result = array(
                "status" => "error",
                "message" => "There was an error when signning this form."
            );
        }
        echo json_encode($result);

    } else if ($_POST['action'] == 'register_new_signature') {
        $error_message="";
		$signature_type = la_sanitize($_POST["signature_type"]);
		$signer_full_name = la_sanitize($_POST["signer_full_name"]);

		$signature_data = "";
		if ($signature_type == "type") {
			$signature_data = $signer_full_name;
		} else if ($signature_type == "draw") {
			$signature_data = la_sanitize($_POST["signature_data"]);
		} else if ($signature_type == "image") {
			$signature_data = la_sanitize($_POST["signature_file_data"]);
		} else {
			$error_message.="signature type should be selected.";
		}

		$signature_hash = md5($signature_data);
		$signature_id = base64_encode("signer_id={$signer_id}&signature_type={$signature_type}&signer_full_name={$signer_full_name}&signature_hash={$signature_hash}");

		//save the signature info and refresh the page
		if ($signature_id) {
			$query = "INSERT INTO 
						`".LA_TABLE_PREFIX."digital_signatures`( 
									`user_id`,
									`signer_full_name`, 
									`signature_type`,
									`signature_data`,
									`signature_id`,
									`created_at`,
									`updated_at`)
						VALUES (?, ?, ?, ?, ?, ?, ?);";
			$params = array(
						$signer_id,
						$signer_full_name,
						$signature_type,
						$signature_data,
						$signature_id,
						$tstamp,
						$tstamp
					);

			la_do_query($query,$params,$dbh);
		}
        if (!$error_message) {
            $result = array(
                "status" => "ok",
                "signature_id" => $signature_id,
                "created_at" => $tstamp,
                "signer_full_name" => $signer_full_name
            );
        } else {
            $result = array(
                "status" => "error",
                "message" => $error_message
            );
        }
        echo json_encode($result);
    } else if ($_POST['action'] == 'remove') {
        $form_id = $_POST['form_id'];

        //remove the current sign information from DB
        $query = "DELETE FROM `".LA_TABLE_PREFIX."signed_forms` WHERE `client_id`=? and `form_id`=?";
        $sth = la_do_query($query, array($client_id, $form_id), $dbh);

        //get the latest signature information from DB
		$query = "SELECT * FROM ".LA_TABLE_PREFIX."digital_signatures WHERE `id`=(SELECT MAX(id) FROM ".LA_TABLE_PREFIX."digital_signatures WHERE user_id=?)";
		$sth = la_do_query($query, array($signer_id), $dbh);
		$res = la_do_fetch_result($sth);
        $signature_id = null;
        if (isset($res)) {
			$signature_id = la_sanitize($res["signature_id"]);
		}

        $result = array(
            "status" => "ok",
            "signature_id" => $signature_id,
        );
        echo json_encode($result);
    }
}