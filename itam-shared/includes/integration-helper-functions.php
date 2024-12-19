<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
permission from http://continuumgrc.com/

More info at: http://continuumgrc.com/
********************************************************************************/

function import_saint_report($dbh, $saint_id) {
	$data = array();
	
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings WHERE id=?";
	$sth = la_do_query($query,array($saint_id),$dbh);
	$saint = la_do_fetch_result($sth);
	$saint_url = $saint["saint_url"];
	$saint_port = $saint["saint_port"];
	$saint_api_token = $saint["saint_api_token"];
	$saint_job_id = $saint["saint_job_id"];
	$saint_ssl_enable = $saint["saint_ssl_enable"];

	if($saint_ssl_enable == "1") {
		
		$curl_job_url = $saint_url.":".$saint_port."/scanjob/".$saint_job_id."?api_token=".$saint_api_token;
		$curl_job = curl_init();
		curl_setopt_array($curl_job, array(
			CURLOPT_URL => $curl_job_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response_job = curl_exec($curl_job);

		if($response_job == false) {
			//url does not exist
			$data = array("status" => "error", "msg" => "Please enter a valid SAINT web server URL and API port.");
		} else {
			if(is_null(json_decode($response_job, true))) {
				//got the error msg returned from the SAINT API
				$data = array("status" => "error", "msg" => $response_job);
			} else {
				//get the info of the SAINT job
				$scanrun_id = end(json_decode($response_job, true)["scanruns"])["id"];
				if($scanrun_id == "") {
					$data = array("status" => "error", "msg" => "This job has no report data.");
				} else {
					$curl_report_url = $saint_url.":".$saint_port."/report"."?api_token=".$saint_api_token."&job_id=".$saint_job_id."&scanrun_id=".$scanrun_id."&format=7&type=full_scan";
					$curl_report = curl_init();
					curl_setopt_array($curl_report, array(
						CURLOPT_URL => $curl_report_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "GET",
						CURLOPT_SSL_VERIFYHOST => 0,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_HTTPHEADER => array(
							"Content-Type: application/json"
						),
					));
					$response_report = curl_exec($curl_report);
					$xml = (array)simplexml_load_string($response_report);
					if(is_array($xml)){
						$dom = new DOMDocument();
						$dom->loadXML($response_report);
						$summary = $dom->getElementsByTagName('summary');

						foreach ($summary as $node) {
							$node->parentNode->removeChild($node);
						}
						$report = $dom->saveXML();

						$data = array("status" => "ok", "report" => $report, "job_name" => json_decode($response_job, true)["description"]);
					} else {
						$data = array("status" => "error", "msg" => "Unable to import the report data.");
					}
				}
			}
		}	
	} else {
		
		$curl_job_url = $saint_url.":".$saint_port."/scanjob/".$saint_job_id."?api_token=".$saint_api_token;
		$curl_job = curl_init();
		curl_setopt_array($curl_job, array(
			CURLOPT_URL => $curl_job_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response_job = curl_exec($curl_job);

		if($response_job == false) {
			//url does not exist
			$data = array("status" => "error", "msg" => "Please enter a valid SAINT web server URL and API port.");
		} else {
			if(is_null(json_decode($response_job, true))) {
				//got the error msg returned from the SAINT API
				$data = array("status" => "error", "msg" => $response_job);
			} else {
				//get the info of the SAINT job
				$scanrun_id = end(json_decode($response_job, true)["scanruns"])["id"];
				if($scanrun_id == "") {
					$data = array("status" => "error", "msg" => "This job has no report data.");
				} else {
					$curl_report_url = $saint_url.":".$saint_port."/report"."?api_token=".$saint_api_token."&job_id=".$saint_job_id."&scanrun_id=".$scanrun_id."&format=7&type=full_scan";
					$curl_report = curl_init();
					curl_setopt_array($curl_report, array(
						CURLOPT_URL => $curl_report_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "GET",
						CURLOPT_HTTPHEADER => array(
							"Content-Type: application/json"
						),
					));
					$response_report = curl_exec($curl_report);
					$xml = (array)simplexml_load_string($response_report);
					if(is_array($xml)){
						$dom = new DOMDocument();
						$dom->loadXML($response_report);
						$summary = $dom->getElementsByTagName('summary');

						foreach ($summary as $node) {
							$node->parentNode->removeChild($node);
						}
						$report = $dom->saveXML();
						$data = array("status" => "ok", "report" => $report, "job_name" => json_decode($response_job, true)["description"]);
					} else {
						$data = array("status" => "error", "msg" => "Unable to import the report data.");
					}
				}
			}
		}
	}
	if($data["status"] == "error") {
		$query = "UPDATE `".LA_TABLE_PREFIX."saint_settings` SET `saint_api_valid` = ?, `saint_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
		$params = array(0, $data["msg"], 0, $saint_id);
		la_do_query($query, $params, $dbh);
	} else if($data["status"] == "ok") {
		$query = "UPDATE `".LA_TABLE_PREFIX."saint_settings` SET `saint_api_valid` = ?, `saint_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
		$params = array(1, "", time(), $saint_id);
		la_do_query($query, $params, $dbh);
		//save the imported report data into the saint_reports table
		$query_report = "INSERT INTO `".LA_TABLE_PREFIX."saint_reports`(saint_id, job_name, report_data, tstamp) values(?,?,?,?)";
		$params_report = array($saint_id, $data["job_name"], $data["report"], time());
		la_do_query($query_report, $params_report, $dbh);
	}
}

function get_all_saint_settings($dbh) {
	//get all SAINT settings
	$saint_settings = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings";
	$sth = la_do_query($query,array(),$dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($saint_settings, $row);
	}
	return $saint_settings;
}

function get_saint_settings($dbh, $form_id) {
	//get SAINT settings for this form
	$saint_settings = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=?";
	$sth = la_do_query($query,array($form_id),$dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($saint_settings, $row);
	}
	return $saint_settings;
}

function save_saint_settings($dbh, $form_id, $data) {
	foreach ($data as $row) {
		if($row["saint_id"] == "0"){
			//this is a new configuration settings that needs to be added 
			$query = "INSERT INTO `".LA_TABLE_PREFIX."saint_settings`(form_id,entity_id,saint_url,saint_port,saint_job_id,saint_api_token,saint_ssl_enable,frequency,saint_api_valid,saint_error_msg,tstamp) values(?,?,?,?,?,?,?,?,?,?,?)";
			$params = array($form_id, $row['entity_id'], $row['saint_url'], $row['saint_port'], $row['saint_job_id'], $row['saint_api_token'], $row['saint_ssl_enable'], $row['frequency'], 1, "", 0);
			la_do_query($query, $params, $dbh);
			import_saint_report($dbh, la_last_insert_id($dbh));
		} else {
			//this is an existing config that needs to be updated
			$query = "UPDATE `".LA_TABLE_PREFIX."saint_settings` SET `entity_id` = ?, `saint_url` = ?, `saint_port` = ?, `saint_job_id` = ?, `saint_api_token` = ?, `saint_ssl_enable` = ?, `frequency` = ?, `saint_api_valid` = ?, `saint_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
			$params = array($row['entity_id'], $row['saint_url'], $row['saint_port'], $row['saint_job_id'], $row['saint_api_token'], $row['saint_ssl_enable'], $row['frequency'], 1, "", 0, $row['saint_id']);
			la_do_query($query, $params, $dbh);
			import_saint_report($dbh, $row["saint_id"]);
		}
	}
}

function delete_saint_settings($dbh, $form_id, $saint_id) {
	//delete a particular SAINT settings
	$query = "DELETE FROM ".LA_TABLE_PREFIX."saint_settings WHERE id=?";
	la_do_query($query, array($saint_id), $dbh);

	$query_report = "DELETE FROM ".LA_TABLE_PREFIX."saint_reports WHERE saint_id=?";
	la_do_query($query_report, array($saint_id), $dbh);

	$query = "SELECT COUNT(*) AS Saint_no FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	$row = la_do_fetch_result($sth);
	if($row["Saint_no"] == 0) {
		$query = "UPDATE `".LA_TABLE_PREFIX."forms` SET `saint_enable` = 0 WHERE `form_id` = ?";
		la_do_query($query, array($form_id), $dbh);
	}
}

function delete_all_saint_settings($dbh, $form_id) {
	//delete all the SAINT settings and report data of this form
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=?";
	$sth = la_do_query($query,array($form_id),$dbh);
	while($row = la_do_fetch_result($sth)){
		delete_saint_settings($dbh, $form_id, $row["id"]);
	}
}

function test_saint_api_config($dbh, $saint_url, $saint_port, $saint_api_token, $saint_job_id, $saint_ssl_enable) {
	$data = array();
	if($saint_ssl_enable == "1") {
		
		$curl_job_url = $saint_url.":".$saint_port."/scanjob/".$saint_job_id."?api_token=".$saint_api_token;
		$curl_job = curl_init();
		curl_setopt_array($curl_job, array(
			CURLOPT_URL => $curl_job_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response_job = curl_exec($curl_job);

		if($response_job == false) {
			//url does not exist
			$data = array("status" => "error", "msg" => "Please enter a valid SAINT web server URL and API port.");
		} else {
			if(is_null(json_decode($response_job, true))) {
				//got the error msg returned from the SAINT API
				$data = array("status" => "error", "msg" => $response_job);
			} else {
				//get the info of the SAINT job
				$scanrun_id = end(json_decode($response_job, true)["scanruns"])["id"];
				if($scanrun_id == "") {
					$data = array("status" => "error", "msg" => "This job has no report data.");
				} else {
					$curl_report_url = $saint_url.":".$saint_port."/report"."?api_token=".$saint_api_token."&job_id=".$saint_job_id."&scanrun_id=".$scanrun_id."&format=7&type=full_scan";
					$curl_report = curl_init();
					curl_setopt_array($curl_report, array(
						CURLOPT_URL => $curl_report_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "GET",
						CURLOPT_SSL_VERIFYHOST => 0,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_HTTPHEADER => array(
							"Content-Type: application/json"
						),
					));
					$response_report = curl_exec($curl_report);
					$xml = (array)simplexml_load_string($response_report);
					if(is_array($xml)){
						$data = array("status" => "ok", "msg" => "This SAINT API configuration is valid for importing the report data.");
					} else {
						$data = array("status" => "error", "msg" => "Unable to import the report data.");
					}
				}
			}
		}
	} else {
		
		$curl_job_url = $saint_url.":".$saint_port."/scanjob/".$saint_job_id."?api_token=".$saint_api_token;
		$curl_job = curl_init();
		curl_setopt_array($curl_job, array(
			CURLOPT_URL => $curl_job_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response_job = curl_exec($curl_job);

		if($response_job == false) {
			//url does not exist
			$data = array("status" => "error", "msg" => "Please enter a valid SAINT web server URL and API port.");
		} else {
			if(is_null(json_decode($response_job, true))) {
				//got the error msg returned from the SAINT API
				$data = array("status" => "error", "msg" => $response_job);
			} else {
				//get the info of the SAINT job
				$scanrun_id = end(json_decode($response_job, true)["scanruns"])["id"];
				if($scanrun_id == "") {
					$data = array("status" => "error", "msg" => "This job has no report data.");
				} else {
					$curl_report_url = $saint_url.":".$saint_port."/report"."?api_token=".$saint_api_token."&job_id=".$saint_job_id."&scanrun_id=".$scanrun_id."&format=7&type=full_scan";
					$curl_report = curl_init();
					curl_setopt_array($curl_report, array(
						CURLOPT_URL => $curl_report_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "GET",
						CURLOPT_HTTPHEADER => array(
							"Content-Type: application/json"
						),
					));
					$response_report = curl_exec($curl_report);
					$xml = (array)simplexml_load_string($response_report);
					if(is_array($xml)){
						$data = array("status" => "ok", "msg" => "This SAINT API configuration is valid for importing the report data.");
					} else {
						$data = array("status" => "error", "msg" => "Unable to import the report data.");
					}
				}
			}
		}
	}
	return $data;
}

function check_saint_enabled($dbh, $form_id, $entity_id) {
	$result = false;
	$query = "SELECT COUNT(*) AS SAINT_no FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=? AND entity_id=?";
	$sth = la_do_query($query, array($form_id, $entity_id), $dbh);
	$row = la_do_fetch_result($sth);
	if($row["SAINT_no"] > 0) {
		$result = true;
	}
	return $result;
}

function get_saint_report_list($dbh, $form_id, $entity_id) {
	$result = array();
	$saint_ids = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=? AND entity_id=?";
	$sth = la_do_query($query, array($form_id, $entity_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($saint_ids, $row["id"]);
	}

	if(count($saint_ids) == 0 ){
		return null;
	} else {
		$inQueryIds = implode(',', array_fill(0, count($saint_ids), '?'));

		$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."saint_reports WHERE `saint_id` IN ({$inQueryIds})";
		$sth_report = la_do_query($query_report, $saint_ids, $dbh);
		while($row_report = la_do_fetch_result($sth_report)) {
			array_push($result, array("report_id" => $row_report["id"], "job_name" => $row_report["job_name"], "data" => $row_report["report_data"], "import_datetime" => $row_report["tstamp"]));
		}
		return $result;
	}
}

function get_all_saint_reports($dbh, $form_id) {
	$result = array();
	$saint_ids = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."saint_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($saint_ids, $row["id"]);
	}

	if(count($saint_ids) == 0 ){
		return null;
	} else {
		$inQueryIds = implode(',', array_fill(0, count($saint_ids), '?'));

		$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."saint_reports WHERE `saint_id` IN ({$inQueryIds}) ORDER BY tstamp desc";
		$sth_report = la_do_query($query_report, $saint_ids, $dbh);
		while($row_report = la_do_fetch_result($sth_report)) {
			array_push($result, array("report_id" => $row_report["id"], "job_name" => $row_report["job_name"], "data" => $row_report["report_data"], "import_datetime" => $row_report["tstamp"]));
		}
		return $result;
	}
}

function get_single_saint_report($dbh, $saint_report_id) {
	$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."saint_reports WHERE id=?";
	$sth_report = la_do_query($query_report, array($saint_report_id), $dbh);
	$row_report = la_do_fetch_result($sth_report);
	return json_decode(json_encode(simplexml_load_string($row_report["report_data"])), true);
}

function delete_single_saint_report($dbh, $saint_report_id) {
	$query_report = "DELETE FROM ".LA_TABLE_PREFIX."saint_reports WHERE id=?";
	la_do_query($query_report, array($saint_report_id), $dbh);	
}

function import_nessus_report($dbh, $nessus_id) {
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings WHERE id=?";
	$sth = la_do_query($query,array($nessus_id),$dbh);
	$nessus = la_do_fetch_result($sth);
	$nessus_access_key = $nessus["nessus_access_key"];
	$nessus_secret_key = $nessus["nessus_secret_key"];
	$nessus_scan_name = $nessus["nessus_scan_name"];

	$data = array();
	//get all the scans that a user can access with the API keys
	$curl = curl_init();

	curl_setopt_array($curl, [
		CURLOPT_URL => "https://cloud.tenable.com/scans",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
		"Accept: application/json",
		"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
		],
	]);

	$response = json_decode(curl_exec($curl), true);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
	} else {
		if(is_null($response)) {
			$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
		} else {
			if(isset($response["scans"]) && !empty($response["scans"])) {
				if(array_search($nessus_scan_name, array_column($response["scans"], "name"))) {
					$status = $response["scans"][array_search($nessus_scan_name, array_column($response["scans"], "name"))]["status"];
					switch ($status) {
						case "completed":
							$scan_id = $response["scans"][array_search($nessus_scan_name, array_column($response["scans"], "name"))]["schedule_uuid"];
							//get scan details using $scan_id
							$curl = curl_init();

							curl_setopt_array($curl, [
								CURLOPT_URL => "https://cloud.tenable.com/scans/".$scan_id,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "GET",
								CURLOPT_HTTPHEADER => [
									"Accept: application/json",
									"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
								],
							]);

							$response = json_decode(curl_exec($curl), true);
							$err = curl_error($curl);

							curl_close($curl);
							if ($err) {
								$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
							} else {
								if(is_array($response["hosts"]) && count($response["hosts"]) > 0) {
									$scanner_name = $response["info"]["scanner_name"];
									$hosts_array = $response["hosts"];
									//get report data in CSV format
									//create export file first
									$curl = curl_init();

									curl_setopt_array($curl, [
										CURLOPT_URL => "https://cloud.tenable.com/scans/".$scan_id."/export",
										CURLOPT_RETURNTRANSFER => true,
										CURLOPT_ENCODING => "",
										CURLOPT_MAXREDIRS => 10,
										CURLOPT_TIMEOUT => 30,
										CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
										CURLOPT_CUSTOMREQUEST => "POST",
										CURLOPT_POSTFIELDS => "{\"format\":\"csv\"}",
										CURLOPT_HTTPHEADER => [
											"Accept: application/json",
											"Content-Type: application/json",
											"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
										],
									]);

									$response = json_decode(curl_exec($curl), true);
									$err = curl_error($curl);

									curl_close($curl);

									if ($err) {
										$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
									} else {
										$file_id = $response["file"];
										//download the report in CSV format string
										$curl = curl_init();

										curl_setopt_array($curl, [
											CURLOPT_URL => "https://cloud.tenable.com/scans/".$scan_id."/export/".$file_id."/download",
											CURLOPT_RETURNTRANSFER => true,
											CURLOPT_ENCODING => "",
											CURLOPT_MAXREDIRS => 10,
											CURLOPT_TIMEOUT => 30,
											CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
											CURLOPT_CUSTOMREQUEST => "GET",
											CURLOPT_HTTPHEADER => [
												"Accept: application/octet-stream",
												"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
											],
										]);

										$response = curl_exec($curl);
										$err = curl_error($curl);

										curl_close($curl);

										if ($err) {
											$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
										} else {
											$lines = explode(PHP_EOL, $response);
											$temp_vulnerability_details = array();
											foreach ($lines as $line) {
												$temp_vulnerability_details[] = str_getcsv($line);
											}
											$vulnerability_header = array_flip($temp_vulnerability_details[0]);
											array_shift($temp_vulnerability_details);
											$hosts = array();
											foreach ($hosts_array as $host) {
												$vulnerability_details = array();
												//get host details and sort vulnerability_details
												$curl = curl_init();

												curl_setopt_array($curl, [
												CURLOPT_URL => "https://cloud.tenable.com/scans/".$scan_id."/hosts/".$host["host_id"],
												CURLOPT_RETURNTRANSFER => true,
												CURLOPT_ENCODING => "",
												CURLOPT_MAXREDIRS => 10,
												CURLOPT_TIMEOUT => 30,
												CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
												CURLOPT_CUSTOMREQUEST => "GET",
												CURLOPT_HTTPHEADER => [
													"Accept: application/json",
													"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
													],
												]);

												$response = json_decode(curl_exec($curl), true);
												$err = curl_error($curl);

												curl_close($curl);

												if ($err) {
													$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
												} else {
													$info = 0;
													$low = 0;
													$medium = 0;
													$high = 0;
													$critical = 0;
													foreach ($temp_vulnerability_details as $row) {
														if($response["info"]["host-fqdn"] == $row[$vulnerability_header["FQDN"]]) {
															switch ($row[$vulnerability_header["Risk"]]) {
																case 'None':
																	$info++;
																	break;
																case 'Low':
																	$low++;
																	break;
																case 'Medium':
																	$medium++;
																	break;
																case 'High':
																	$high++;
																	break;
																case 'Critical':
																	$critical++;
																	break;
															}
															array_push($vulnerability_details, array(
																"port" => $row[$vulnerability_header["Port"]]."/".$row[$vulnerability_header["Protocol"]],
																"risk" => $row[$vulnerability_header["Risk"]] == "None" ? "Info" : $row[$vulnerability_header["Risk"]],
																"class" => $row[$vulnerability_header["Plugin Family"]],
																"cve" => $row[$vulnerability_header["CVE"]],
																"cvss_base_score" => $row[$vulnerability_header["CVSS Base Score"]],
																"synopsis" => trim($row[$vulnerability_header["Synopsis"]]),
																"description" => trim($row[$vulnerability_header["Description"]]),
																"resolution" => trim($row[$vulnerability_header["Solution"]]),
																"references" => trim($row[$vulnerability_header["See Also"]]),
																"technical_details" => trim($row[$vulnerability_header["Plugin Output"]])
															));
														}
													}
													array_push($hosts, array("hostname" => $response["info"]["host-fqdn"], "ip_address" => $response["info"]["host-ip"], "host_type" => $response["info"]["operating-system"], "info" => $info, "low" => $low, "medium" => $medium, "high" => $high, "critical" => $critical, "vulnerability_details" => $vulnerability_details));
												}
											}
											$data = array("status" => "ok", "report" => $hosts, "scan_name" => $nessus_scan_name, "scanner_name" => $scanner_name);
										}
									}
								} else {
									$data = array("status" => "ok", "report" => array(), "scan_name" => $nessus_scan_name, "scanner_name" => $scanner_name);
								}
							}
							break;
						case "aborted":
							$data = array("status" => "error", "msg" => "Tenable.io or the scanner encountered problems during the latest run and aborted the scan. The scan results associated with the run reflect only the completed tasks.");
							break;
						case "canceled":
							$data = array("status" => "error", "msg" => "At the user's request, Tenable.io successfully stopped the latest scan run.");
							break;
						case "empty":
							$data = array("status" => "error", "msg" => "The scan configuration is new or has yet to run.");
							break;
						case "imported":
							$data = array("status" => "error", "msg" => "A user imported the scan. You cannot run imported scans. Scan history is unavailable for imported scans.");
							break;
						case "initializing":
							$data = array("status" => "error", "msg" => "Tenable.io is preparing the scan request for processing.");
							break;
						case "pausing":
							$data = array("status" => "error", "msg" => "A user paused a running scan, and Tenable.io is in the process of terminating tasks for the scan.");
							break;
						case "paused":
							$data = array("status" => "error", "msg" => "At the user's request, Tenable.io successfully paused active tasks related to the scan. The paused tasks continue to fill the task capacity of the scanner that the tasks were assigned to. Tenable.io does not dispatch new tasks from a paused scan job. If the scan remains in a paused state for more than 14 days, the scan times out. Tenable.io then aborts the related tasks on the scanner and categorizes the scan as aborted.");
							break;
						case "pending":
							$data = array("status" => "error", "msg" => "Tenable.io has finished initializing and processing the scan request and is waiting for a scanner or agent to pick up the job.");
							break;
						case "processing":
							$data = array("status" => "error", "msg" => "Tenable.io is processing tasks for the scan. For example, Tenable.io may be importing scan results from the scanner that performed the latest run of the scan.");
							break;
						case "resuming":
							$data = array("status" => "error", "msg" => "Tenable.io is restarting tasks for a paused scan. When you resume a scan, Tenable.io instructs the scanner to start the tasks from the point at which the scan was paused. If Tenable.io or the scanner encounters problems when resuming the scan, the scan fails, and Tenable.io updates the scan status to aborted.");
							break;
						case "running":
							$data = array("status" => "error", "msg" => "The scan is currently running.");
							break;
						case "stopped":
							$data = array("status" => "error", "msg" => "A user stopped a pending, running, or paused scan, and Tenable.io is in the process of terminating tasks for the scan.");
							break;
						case "stopping":
							$data = array("status" => "error", "msg" => "Tenable.io is processing a request to stop a scan.");
							break;
					}
				} else {
					$data = array("status" => "error", "msg" => "Unable to find out the scan. Please confirm the scan name.");
				}
			} else {
				$data = array("status" => "error", "msg" => "Please enter valid Nessus API keys.");
			}
		}
	}

	if($data["status"] == "error") {
		$query = "UPDATE `".LA_TABLE_PREFIX."nessus_settings` SET `nessus_api_valid` = ?, `nessus_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
		$params = array(0, $data["msg"], 0, $nessus_id);
		la_do_query($query, $params, $dbh);
	} else if($data["status"] == "ok") {
		$query = "UPDATE `".LA_TABLE_PREFIX."nessus_settings` SET `nessus_api_valid` = ?, `nessus_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
		$params = array(1, "", time(), $nessus_id);
		la_do_query($query, $params, $dbh);
		//save the imported report data into the nessus_reports table
		$query_report = "INSERT INTO `".LA_TABLE_PREFIX."nessus_reports`(nessus_id, scan_name, scanner_name, report_data, tstamp) values(?,?,?,?,?)";
		$params_report = array($nessus_id, $data["scan_name"], $data["scanner_name"], json_encode($data["report"]), time());
		la_do_query($query_report, $params_report, $dbh);
	}
}

function get_all_nessus_settings($dbh) {
	//get all Nessus settings
	$nessus_settings = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings";
	$sth = la_do_query($query,array(),$dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($nessus_settings, $row);
	}
	return $nessus_settings;
}

function get_nessus_settings($dbh, $form_id) {
	//get Nessus settings for this form
	$nessus_settings = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($nessus_settings, $row);
	}
	return $nessus_settings;
}

function save_nessus_settings($dbh, $form_id, $data) {
	foreach ($data as $row) {
		if($row["nessus_id"] == "0"){
			//this is a new configuration settings that needs to be added 
			$query = "INSERT INTO `".LA_TABLE_PREFIX."nessus_settings`(form_id,entity_id,nessus_access_key,nessus_secret_key,nessus_scan_name,frequency,nessus_api_valid,nessus_error_msg,tstamp) values(?,?,?,?,?,?,?,?,?)";
			$params = array($form_id, $row['entity_id'], $row['nessus_access_key'], $row['nessus_secret_key'], $row['nessus_scan_name'], $row['frequency'], 1, "", 0);
			la_do_query($query, $params, $dbh);
			import_nessus_report($dbh, la_last_insert_id($dbh));
		} else {
			//this is an existing config that needs to be updated
			$query = "UPDATE `".LA_TABLE_PREFIX."nessus_settings` SET `entity_id` = ?, `nessus_access_key` = ?, `nessus_secret_key` = ?, `nessus_scan_name` = ?, `frequency` = ?, `nessus_api_valid` = ?, `nessus_error_msg` = ?, `tstamp` = ? WHERE `id` = ?";
			$params = array($row['entity_id'], $row['nessus_access_key'], $row['nessus_secret_key'], $row['nessus_scan_name'], $row['frequency'], 1, "", 0, $row['nessus_id']);
			la_do_query($query, $params, $dbh);
			import_nessus_report($dbh, $row["nessus_id"]);
		}
	}
}

function delete_nessus_settings($dbh, $form_id, $nessus_id) {
	//delete a particular Nessus settings
	$query = "DELETE FROM ".LA_TABLE_PREFIX."nessus_settings WHERE id=?";
	la_do_query($query, array($nessus_id), $dbh);

	$query_report = "DELETE FROM ".LA_TABLE_PREFIX."nessus_reports WHERE nessus_id=?";
	la_do_query($query_report, array($nessus_id), $dbh);

	$query = "SELECT COUNT(*) AS Nessus_no FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	$row = la_do_fetch_result($sth);
	if($row["Nessus_no"] == 0) {
		$query = "UPDATE `".LA_TABLE_PREFIX."forms` SET `nessus_enable` = 0 WHERE `form_id` = ?";
		la_do_query($query, array($form_id), $dbh);
	}
}

function delete_all_nessus_settings($dbh, $form_id) {
	//delete all the Nessus settings and report data of this form
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		delete_nessus_settings($dbh, $form_id, $row["id"]);
	}
}

function test_nessus_api_config($dbh, $nessus_access_key, $nessus_secret_key, $nessus_scan_name) {
	$data = array();
	//get all the scans that a user can access with the API keys
	$curl = curl_init();

	curl_setopt_array($curl, [
		CURLOPT_URL => "https://cloud.tenable.com/scans",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
		"Accept: application/json",
		"X-ApiKeys: accessKey=".$nessus_access_key.";secretKey=".$nessus_secret_key
		],
	]);

	$response = json_decode(curl_exec($curl), true);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
	} else {
		if(is_null($response)) {
			$data = array("status" => "error", "msg" => "Unable to access the Nessus API. Please try again later.");
		} else {
			if(isset($response["scans"]) && !empty($response["scans"])) {
				if(array_search($nessus_scan_name, array_column($response["scans"], "name"))) {
					$status = $response["scans"][array_search($nessus_scan_name, array_column($response["scans"], "name"))]["status"];
					switch ($status) {
						case "completed":
							$data = array("status" => "ok", "msg" => "This API configuration is valid for importing the report data.");
							break;
						case "aborted":
							$data = array("status" => "error", "msg" => "Tenable.io or the scanner encountered problems during the latest run and aborted the scan. The scan results associated with the run reflect only the completed tasks.");
							break;
						case "canceled":
							$data = array("status" => "error", "msg" => "At the user's request, Tenable.io successfully stopped the latest scan run.");
							break;
						case "empty":
							$data = array("status" => "error", "msg" => "The scan configuration is new or has yet to run.");
							break;
						case "imported":
							$data = array("status" => "error", "msg" => "A user imported the scan. You cannot run imported scans. Scan history is unavailable for imported scans.");
							break;
						case "initializing":
							$data = array("status" => "error", "msg" => "Tenable.io is preparing the scan request for processing.");
							break;
						case "pausing":
							$data = array("status" => "error", "msg" => "A user paused a running scan, and Tenable.io is in the process of terminating tasks for the scan.");
							break;
						case "paused":
							$data = array("status" => "error", "msg" => "At the user's request, Tenable.io successfully paused active tasks related to the scan. The paused tasks continue to fill the task capacity of the scanner that the tasks were assigned to. Tenable.io does not dispatch new tasks from a paused scan job. If the scan remains in a paused state for more than 14 days, the scan times out. Tenable.io then aborts the related tasks on the scanner and categorizes the scan as aborted.");
							break;
						case "pending":
							$data = array("status" => "error", "msg" => "Tenable.io has finished initializing and processing the scan request and is waiting for a scanner or agent to pick up the job.");
							break;
						case "processing":
							$data = array("status" => "error", "msg" => "Tenable.io is processing tasks for the scan. For example, Tenable.io may be importing scan results from the scanner that performed the latest run of the scan.");
							break;
						case "resuming":
							$data = array("status" => "error", "msg" => "Tenable.io is restarting tasks for a paused scan. When you resume a scan, Tenable.io instructs the scanner to start the tasks from the point at which the scan was paused. If Tenable.io or the scanner encounters problems when resuming the scan, the scan fails, and Tenable.io updates the scan status to aborted.");
							break;
						case "running":
							$data = array("status" => "error", "msg" => "	The scan is currently running.");
							break;
						case "stopped":
							$data = array("status" => "error", "msg" => "A user stopped a pending, running, or paused scan, and Tenable.io is in the process of terminating tasks for the scan.");
							break;
						case "stopping":
							$data = array("status" => "error", "msg" => "Tenable.io is processing a request to stop a scan.");
							break;
					}
				} else {
					$data = array("status" => "error", "msg" => "Unable to find out the scan. Please confirm the scan name.");
				}
			} else {
				$data = array("status" => "error", "msg" => "Please enter valid Nessus API keys.");
			}
		}
	}
	return $data;
}

function check_nessus_enabled($dbh, $form_id, $entity_id) {
	$result = false;
	$query = "SELECT COUNT(*) AS Nessus_no FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=? AND entity_id=?";
	$sth = la_do_query($query, array($form_id, $entity_id), $dbh);
	$row = la_do_fetch_result($sth);
	if($row["Nessus_no"] > 0) {
		$result = true;
	}
	return $result;
}

function get_nessus_report_list($dbh, $form_id, $entity_id) {
	$result = array();
	$nessus_ids = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=? AND entity_id=?";
	$sth = la_do_query($query, array($form_id, $entity_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($nessus_ids, $row["id"]);
	}
	if(count($nessus_ids) == 0 ){
		return null;
	} else {
		$inQueryIds = implode(',', array_fill(0, count($nessus_ids), '?'));

		$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_reports WHERE `nessus_id` IN ({$inQueryIds})";
		$sth_report = la_do_query($query_report, $nessus_ids, $dbh);
		while($row_report = la_do_fetch_result($sth_report)) {
			array_push($result, array("report_id" => $row_report["id"], "scan_name" => $row_report["scan_name"], "scanner_name" => $row_report["scanner_name"], "data" => json_decode($row_report["report_data"]), "import_datetime" => $row_report["tstamp"]));
		}
		return $result;
	}
}

function get_all_nessus_reports($dbh, $form_id) {
	$result = array();
	$nessus_ids = array();
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_settings WHERE form_id=?";
	$sth = la_do_query($query, array($form_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		array_push($nessus_ids, $row["id"]);
	}

	if(count($nessus_ids) == 0 ){
		return null;
	} else {
		$inQueryIds = implode(',', array_fill(0, count($nessus_ids), '?'));

		$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_reports WHERE `nessus_id` IN ({$inQueryIds}) ORDER BY tstamp desc";
		$sth_report = la_do_query($query_report, $nessus_ids, $dbh);
		while($row_report = la_do_fetch_result($sth_report)) {
			array_push($result, array("report_id" => $row_report["id"], "scan_name" => $row_report["scan_name"], "scanner_name" => $row_report["scanner_name"], "data" => json_decode($row_report["report_data"]), "import_datetime" => $row_report["tstamp"]));
		}
		return $result;
	}
}

function get_single_nessus_report($dbh, $nessus_report_id) {
	$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."nessus_reports WHERE id=?";
	$sth_report = la_do_query($query_report, array($nessus_report_id), $dbh);
	$row_report = la_do_fetch_result($sth_report);
	return json_decode($row_report["report_data"], true);
}

function delete_single_nessus_report($dbh, $nessus_report_id) {
	$query_report = "DELETE FROM ".LA_TABLE_PREFIX."nessus_reports WHERE id=?";
	la_do_query($query_report, array($nessus_report_id), $dbh);	
}

function migrate_entry_data($dbh, $form_id, $target_url, $key) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $target_url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => ""
	));
	curl_exec($curl);
	$err = curl_error($curl);
	$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	if ($err) {
		$res = '{ "status" : "error", "msg" : "'.$err.'" }';
	} else {
		//get form name
		$query_form = "SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id=?";
		$sth_form = la_do_query($query_form, array($form_id), $dbh);
		$row_form = la_do_fetch_result($sth_form);
		$form_name = $row_form["form_name"];

		$form_ids = array($form_id);
		//get sub form IDs
		$query  = "SELECT element_default_value FROM `".LA_TABLE_PREFIX."form_elements` WHERE form_id=? AND element_type = 'casecade_form' AND element_default_value != ? ORDER BY element_position ASC";
		$sth = la_do_query($query, array($form_id, ""), $dbh);
		while($row = la_do_fetch_result($sth)){
			array_push($form_ids, (int) $row['element_default_value']);
		}

		//get form structure
		$form_structure = getFormStructure($dbh, $form_ids);
		
		//get receiver info
		$query_receiver = "SELECT user_email, user_fullname FROM `".LA_TABLE_PREFIX."users` WHERE user_id=? ";
		$sth_receiver = la_do_query($query_receiver, array($_SESSION["la_user_id"]), $dbh);
		$row_receiver = la_do_fetch_result($sth_receiver);
		$receiver = "[Admin] ".$row_receiver["user_fullname"]."(".$row_receiver["user_email"].")";
		$postData = array(
				'from' => base64_encode($_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"]),
				'receiver' => base64_encode($receiver),
				'receiver_ip_address' => base64_encode($_SERVER['REMOTE_ADDR']),
				'form_name' => base64_encode($form_name),
				'form_structure' => base64_encode(json_encode($form_structure)),
				'key' => base64_encode($key)
			);
		$ch = curl_init( $target_url."/auditprotocol/migrate_data.php" );
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);
		$err = curl_error($ch);
		curl_close($ch);
		if($err) {
			$res = '{ "status" : "error", "msg" : "'.$err.'" }';
		} else {
			if($response["status"] == "ok") {
				$exported_form_ids = $response["form_ids"];
				$exported_entry_data = $response["export_data"];
				$uploaded_files = $response["files_data"];

				foreach ($exported_entry_data as $data) {
					//decide a company_id based on the email address of the exported entry data
					if($data['company_name'] == "ADMINISTRATOR") {
						$company_id = time();
					} else {
						$query_company = "SELECT client_id FROM ".LA_TABLE_PREFIX."ask_clients WHERE company_name = ?";
						$sth_company = la_do_query($query_company, array($data['company_name']), $dbh);
						$row_company = la_do_fetch_result($sth_company);
						if($row_company) {
							$company_id = $row_company['client_id'];
						} else {
							$query_company_insert = "INSERT INTO `".LA_TABLE_PREFIX."ask_clients`(`company_name`, `contact_email`) VALUES (?,?)";
							la_do_query($query_company_insert, array($data["company_name"], $data["company_email"]), $dbh);
							$company_id = la_last_insert_id($dbh);
						}
					}
					$entry_id = time();
					//insert entry data into corresponding tables
					foreach ($data["entry_data"] as $entry_row) {
						$form_id_key = array_search($entry_row["form_id"], $exported_form_ids);
						$new_form_id = $form_ids[$form_id_key];

						//create a form table if it doesn't exist
						$query = "CREATE TABLE IF NOT EXISTS `".LA_TABLE_PREFIX."form_{$new_form_id}` (
														`id` int(11) NOT NULL auto_increment,
														`company_id` int(11) NOT NULL,
														`entry_id` int(11) NOT NULL,
														`field_name` varchar(200) NOT NULL,
														`field_code` varchar(50) NOT NULL,
														`data_value` longtext NOT NULL,
														`field_score` text NOT NULL,
														`form_resume_enable` int(11) NOT NULL,
														`unique_row_data` varchar(64) NOT NULL,
														`submitted_from` int(1) NOT NULL,
														`other_info` text NOT NULL,
														`element_machine_code` varchar(100) NULL,
															PRIMARY KEY (`id`),
														UNIQUE KEY `unique_row_data` (`unique_row_data`)
															) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
						la_do_query($query, array(), $dbh);

						$query = "INSERT INTO `".LA_TABLE_PREFIX."form_{$new_form_id}` (`id`, `company_id`, `entry_id`, `field_name`, `field_code`, `data_value`, `field_score`, `form_resume_enable`, `unique_row_data`, `element_machine_code`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, CONCAT(`company_id`, '_', `entry_id`, '_', `field_name`), ?) ON DUPLICATE KEY update `data_value` = values(`data_value`), `field_score` = values(`field_score`)";
						la_do_query($query, array($company_id, $entry_id, $entry_row["field_name"], $entry_row["field_code"], $entry_row["data_value"], $entry_row["field_score"], $entry_row["form_resume_enable"], $entry_row["element_machine_code"]), $dbh);
					}

					//insert status indicators
					foreach ($data["status_indicators"] as $status_row) {
						$form_id_key = array_search($status_row["form_id"], $exported_form_ids);
						$new_form_id = $form_ids[$form_id_key];

						//delete exisiting status indicator
						$query = "DELETE FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE form_id = ? AND element_id = ? AND company_id = ? AND entry_id = ?";
						la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id), $dbh);

						$query = "INSERT INTO `".LA_TABLE_PREFIX."element_status_indicator` (`id`, `form_id`, `element_id`, `company_id`, `entry_id`, `indicator`) VALUES (NULL, ?, ?, ?, ?, ?)";
						la_do_query($query, array($new_form_id, $status_row["element_id"], $company_id, $entry_id, $status_row["indicator"]), $dbh);
					}

					//insert synced files
					foreach ($data["synced_files"] as $synced_file_row) {
						//delete exisiting synced files row
						$query = "DELETE FROM `".LA_TABLE_PREFIX."file_upload_synced` WHERE element_machine_code = ? AND company_id = ?";
						la_do_query($query, array($synced_file_row["element_machine_code"], $company_id), $dbh);

						$query = "INSERT INTO `".LA_TABLE_PREFIX."file_upload_synced` (`id`, `element_machine_code`, `files_data`, `company_id`) VALUES (NULL, ?, ?, ?)";
						la_do_query($query, array($synced_file_row["element_machine_code"], $synced_file_row["files_data"], $company_id), $dbh);
					}
				}

				foreach ($uploaded_files as $uploaded_file) {
					if($uploaded_file["synced"] == 1) {
						$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$uploaded_file['element_machine_code']}";
						$remote_file = "{$target_url}/auditprotocol/data/file_upload_synced/".rawurlencode($uploaded_file['element_machine_code'])."/".rawurlencode($uploaded_file['file_name']);
					} else {
						$form_id_key = array_search($uploaded_file["form_id"], $exported_form_ids);
						$new_form_id = $form_ids[$form_id_key];
						$destination_folder = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$new_form_id}/files";
						$remote_file = "{$target_url}/auditprotocol/data/form_{$uploaded_file['form_id']}/files/".rawurlencode($uploaded_file['file_name']);
					}
					//Open file
					$handle = @fopen($remote_file, 'r');

					// Check if file exists
					if($handle){
						if(is_dir($destination_folder) === false){
							@mkdir($destination_folder, 0777, true);
						}
						copy($remote_file, $destination_folder."/".$uploaded_file["file_name"]);
					}
				}
				
				//save the action in the audit logs
				$action_text = $receiver." migrated entry data from ".$target_url.".";
				$query_audit_log = "INSERT INTO `".LA_TABLE_PREFIX."audit_log`(`user_id`, `form_id`, `action_type_id`, `action_text`, `user_ip`, `action_datetime`) VALUES (?,?,?,?,?,?)";
				la_do_query($query_audit_log, array($_SESSION["la_user_id"], $form_id, 18, $action_text, $_SERVER['REMOTE_ADDR'], time()), $dbh);

				$res = '{"status" : "ok", "msg" : "The entry data has been migrated successfully."}';
			} elseif($response["status"] == "error") {
				$res = '{ "status" : "error", "msg" : "'. $response["msg"] .'" }';
			} else {
				$res = '{"status" : "error", "msg" : "Something went wrong while migrating the entry data."}';
			}
		}
	}
	return $res;
}

function delete_migration_wizard_settings($dbh, $form_id) {
	$query_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_migration_wizard_settings` WHERE form_id = ?";
	la_do_query($query_delete, array($form_id), $dbh);

	$query_update = "UPDATE `".LA_TABLE_PREFIX."forms` SET `migration_wizard_enable` = ? WHERE `form_id` = ?";
	la_do_query($query_update, array(0, $form_id), $dbh);
}

function save_migration_wizard_settings($dbh, $form_id, $target_url, $connector_role, $key) {
	if($connector_role == 0) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $target_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => ""
		));
		curl_exec($curl);
		$err = curl_error($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($err) {
			$res = '{ "status" : "error", "msg" : "'.$err.'" }';
		} else {
			if($code == 200) {
				$res = '{ "status" : "ok", "msg" : "Migration Wizard settings have been saved successfully." }';
			} else {
				$res = '{"status" : "error", "msg" : "The System URL doesn\'t exist. Please enter a valid System URL!"}';
			}
		}
	} else {
		$res = migrate_entry_data($dbh, $form_id, $target_url, $key);
	}
	if(json_decode($res, true)["status"] == "ok") {
		$query_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_migration_wizard_settings` WHERE form_id = ?";
		la_do_query($query_delete, array($form_id), $dbh);

		$query_insert = "INSERT INTO `".LA_TABLE_PREFIX."form_migration_wizard_settings`(`form_id`, `target_url`, `connector_role`, `key`, `admin_id`) VALUES (?,?,?,?,?)";
		la_do_query($query_insert, array($form_id, $target_url, $connector_role, $key, $_SESSION['la_user_id']), $dbh);

		$query_update = "UPDATE `".LA_TABLE_PREFIX."forms` SET `migration_wizard_enable` = ? WHERE `form_id` = ?";
		la_do_query($query_update, array(1, $form_id), $dbh);
		$_SESSION['LA_SUCCESS'] = json_decode($res, true)["msg"];
	}
	return $res;
}
?>