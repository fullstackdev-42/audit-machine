<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com 
 ********************************************************************************/
date_default_timezone_set('America/Los_Angeles');
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');
require('includes/entry-functions.php');
require('includes/report-helper-function.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$pdf_header_img = 'data:image/' . pathinfo($la_settings["admin_image_url"], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($la_settings["admin_image_url"]));
$display_type = array(
	"line_chart" => "Line chart",
	"area_chart" => "Area chart",
	"column_and_bar_chart" => "Column and Bar chart",
	"pie_chart" => "Pie chart",
	"bubble_chart" => "Bubble chart",
	"dynamic_chart" => "Dynamic chart",
	"combinations" => "Combinations",
	"3d_chart" => "3D chart",
	"windrose_chart" => "Wind Rose chart",
	"sunburst_chart" => "Sunburst chart",
	"polar_chart" => "Polar Chart",
	"heatmap_chart" => "Heat Map chart",
	"general_map" => "General map chart",
	"dynamic_map" => "Dynamic map chart",
	"maturity" => "Maturity",
	"compliance-dashboard" => "Compliance Dashboard",
	"field-note" => "Field Note"
);
$math_functions = array(
	"sum" => "Sum", 
	"average" => "Average", 
	"percentage" => "Percentage", 
	"multiplication" => "Multiplication", 
	"division" => "Division", 
	"addition" => "Addition", 
	"median" => "Median"
);
$report_id  = base64_decode($_GET['report_id']);
$query_report = "SELECT r.*, c.`company_name`, c.`contact_email`, c.`contact_full_name`, f.`form_name` FROM `".LA_TABLE_PREFIX."form_report` AS r LEFT JOIN `".LA_TABLE_PREFIX."ask_clients` AS c ON (r.`company_id` = c.`client_id`) LEFT JOIN `".LA_TABLE_PREFIX."forms` AS f ON (r.`form_id` = f.`form_id`) WHERE r.`report_id` = ?";
$sth_report = la_do_query($query_report, array($report_id), $dbh);
$res_report = la_do_fetch_result($sth_report);
if($res_report) {
	if($res_report["math_function"] == "audit-dashboard" || $res_report["math_function"] == "template-code") {
		header("Location: my_report.php");
		exit;
	}
}
$header_data =<<<EOT
<link type="text/css" href="css/dropui.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<style>
.chart-data-row{
	color: #000;	
}
.content_header {
	display: none;
}
</style>
EOT;
	require('portal-header.php');
?>
<style type="text/css">
	.text-align-center {
		text-align: center;
	}
</style>
<div id="content" class="full">
	<div id="report_container">
		<div class="report-generation-msg">
			Generating a report...
			<img id="loader-img" src="images/loader_small_grey.gif" style="margin-left: 10px;">
		</div>
		<input id="pdf_header_img" type="hidden" value="<?php echo $pdf_header_img; ?>">
		<?php
			if($res_report) {
				$start_date = date("m/d/Y", $res_report["start_date"]);
				$completion_date = date("m/d/Y", $res_report["completion_date"]);
				$report_created_date = date('m/d/Y H:i a', $res_report['report_created_on']);
				if($res_report["math_function"] == "artifact-management") {
				?>
					<h4 class="report-title">Artifact Management Report</h4>
					<div class="report-brief-content">
						<div class="field-list">
							<label class="field-label">Scheduled Start Date:</label>
							<div class="field-content"><?php echo $start_date; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Scheduled Completion Date:</label>
							<div class="field-content"><?php echo $completion_date; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Report Created:</label>
							<div class="field-content"><?php echo $report_created_date; ?></div>
						</div>
					</div>
					<div class="report-details" style="display: none;">
						<table id="artifact-management-table" class="hover stripe cell-border nowrap" style="width: 100%;">
							<thead>
								<tr>
									<th>#</th>
									<th>File Name</th>
									<th>Form ID</th>
									<th>Form Name</th>
									<th>Field Label</th>
									<th>Uploaded By</th>
									<th>Go To Field</th>
								</tr>
							</thead>
							<tbody>
							<?php
								//get a list of all files previously uploaded into all forms
								// select all forms whose status is 1
								$i = 0;
								$uploaded_files = array();
								$query_forms = "SELECT `form_id`, `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_active` = ?";
								$sth_forms = la_do_query($query_forms, array(1), $dbh);
								while($row_form = la_do_fetch_result($sth_forms)){
									//check if form_{form_id} table exists or not
									$temp_form_id = $row_form["form_id"];
									$queryFormTable = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$temp_form_id}'";
									$resultFormTable = la_do_query($queryFormTable, array(), $dbh);
									$rowFormTable    = la_do_fetch_result($resultFormTable);
									if($rowFormTable) {
										//get only upload files, not synced files
										$query_files = "SELECT f.company_id, f.entry_id, f.data_value, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto, c.company_name FROM `".LA_TABLE_PREFIX."form_{$temp_form_id}` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.field_name = CONCAT('element_', e.element_id) LEFT JOIN `".LA_TABLE_PREFIX."ask_clients` AS c ON f.company_id = c.client_id WHERE e.form_id = ? AND e.element_type = ? AND f.data_value != ? AND (e.element_machine_code = ? OR e.element_file_upload_synced != ?)";
										$result_files = la_do_query($query_files, array($temp_form_id, "file", "", "", 1), $dbh);
										while ($row_file = la_do_fetch_result($result_files)) {
											
											$files = explode("|", $row_file["data_value"]);
											foreach ($files as $file_name) {
												$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/form_{$temp_form_id}/files/{$file_name}";
												if(file_exists($file_complete_path)) {
													$i++;
													$encoded_file_name = urlencode($file_name);
													$filename_explode1 = explode('-', $file_name, 2);
													$display_filename = $filename_explode1[1];
													$file_ext   = end(explode(".", $file_name));
													
													if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
														$data_identifier = "image_format";
														$q_string = $la_settings["base_url"]."data/form_{$temp_form_id}/files/{$file_name}";
														$q_string = str_replace("%", "%25", $q_string);
														$q_string = str_replace("#", "%23", $q_string);
													} else {
														$data_identifier = "other";
														$q_string = base64_encode("form_id={$temp_form_id}&file_name={$encoded_file_name}&call_type=ajax_normal");
													}
													$field_link = "view.php?id={$temp_form_id}&entry_id={$row_file['entry_id']}";
													if( isset($row_file['element_page_number']) && $row_file['element_page_number'] > 1 ) {
														$field_link.= "&la_page=".$row_file['element_page_number'];
													}
													if( !empty($row_file['element_id_auto']) ) {
														$field_link.= "&element_id_auto=".$row_file['element_id_auto'];
													}
													$entity_name = strlen($row_file["company_id"]) == 10 ? "Admin" : $row_file["company_name"]; 
													?>
													<tr>
														<td class="me_number"><?php echo $i; ?></td>
														<td class="text-align-center"><a class="entry_link entry-link-preview" href="#" data-identifier='<?php echo $data_identifier; ?>' data-ext='<?php echo $file_ext; ?>' data-src='<?php echo $q_string; ?>'><?php echo $display_filename; ?></a></td>
														<td class="text-align-center"><?php echo $temp_form_id; ?></td>
														<td class="text-align-center"><?php echo $row_form["form_name"]; ?></td>
														<td class="text-align-center"><?php echo $row_file["element_title"]; ?></td>
														<td class="text-align-center"><?php echo $entity_name; ?></td>
														<td class="text-align-center"><a target="_blank" href='<?php echo $field_link; ?>'>Go To Field</a></td>
													</tr>
													<?php
												}
											}
										}

										//get synced files
										$query_synced_files = "SELECT f.company_id, f.files_data, e.element_title, e.element_id, e.element_page_number, e.id AS element_id_auto, e.element_machine_code, c.company_name FROM `".LA_TABLE_PREFIX."file_upload_synced` AS f INNER JOIN `".LA_TABLE_PREFIX."form_elements` AS e ON f.element_machine_code = e.element_machine_code LEFT JOIN `".LA_TABLE_PREFIX."ask_clients` AS c ON f.company_id = c.client_id WHERE e.form_id = ? AND e.element_type = ? AND f.files_data != ? AND e.element_machine_code != ? AND e.element_file_upload_synced = ?";
										$result_synced_files = la_do_query($query_synced_files, array($temp_form_id, "file", "", "", 1), $dbh);
										while ($row_synced_file = la_do_fetch_result($result_synced_files)) {
											$files = json_decode($row_synced_file['files_data']);
											foreach ($files as $file_name) {
												$file_complete_path = $_SERVER['DOCUMENT_ROOT']."/auditprotocol/data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
												if(file_exists($file_complete_path)) {
													$i++;
													$encoded_file_name = urlencode($file_name);
													$filename_explode1 = explode('-', $file_name, 2);
													$display_filename = $filename_explode1[1];
													$file_ext   = end(explode(".", $file_name));

													if(in_array(strtolower($file_ext), array('png', 'bmp', 'gif', 'jpg', 'jpeg'))){
														$data_identifier = "image_format";
														$q_string = $la_settings["base_url"]."data/file_upload_synced/{$row_synced_file['element_machine_code']}/{$file_name}";
														$q_string = str_replace("%", "%25", $q_string);
														$q_string = str_replace("#", "%23", $q_string);
													} else {
														$data_identifier = "other";
														$q_string = base64_encode("element_machine_code={$row_synced_file['element_machine_code']}&file_name={$encoded_file_name}&call_type=ajax_synced");
													}
													$field_link = "view.php?id={$temp_form_id}";
													if( isset($row_synced_file['element_page_number']) && $row_synced_file['element_page_number'] > 1 ) {
														$field_link.= "&la_page=".$row_synced_file['element_page_number'];
													}
													if( !empty($row_synced_file['element_id_auto']) ) {
														$field_link.= "&element_id_auto=".$row_synced_file['element_id_auto'];
													}
													$entity_name = strlen($row_synced_file["company_id"]) == 10 ? "Admin" : $row_synced_file["company_name"]; 
													?>
													<tr>
														<td class="me_number"><?php echo $i; ?></td>
														<td class="text-align-center"><a class="entry_link entry-link-preview" href="#" data-identifier='<?php echo $data_identifier; ?>' data-ext='<?php echo $file_ext; ?>' data-src='<?php echo $q_string; ?>'><?php echo $display_filename; ?></a></td>
														<td class="text-align-center"><?php echo $temp_form_id; ?></td>
														<td class="text-align-center"><?php echo $row_form["form_name"]; ?></td>
														<td class="text-align-center"><?php echo $row_synced_file["element_title"]; ?></td>
														<td class="text-align-center"><?php echo $entity_name; ?></td>
														<td class="text-align-center"><a target="_blank" href='<?php echo $field_link; ?>'>Go To Field</a></td>
													</tr>
													<?php
												}
											}
										}
									}
								}
							?>
							</tbody>
						</table>
					</div>
				<?php
				} else {
					$report_title = "";
					if ($res_report['math_function'] == "status-indicator") {
						$report_title = "Status Indicators Report for ".$res_report["company_name"]." , Chart Type: ".$display_type[$res_report['display_type']];
					} else if($res_report['math_function'] == "risk") {
						$report_title = "Risk Score Report for ".$res_report["company_name"]." , Chart Type: ".$display_type[$res_report['display_type']];
					} else if($res_report['math_function'] == "maturity") {
						$report_title = "Maturity Report for ".$res_report["company_name"];
					} else if($res_report['math_function'] == "compliance-dashboard"){
						$report_title = "Compliance Dashboard Report for ".$res_report["company_name"];
					} else if($res_report['math_function'] == "field-note"){
						$report_title = "Field Note Report for ".$res_report["company_name"];
					} else if($res_report['math_function'] == "executive-overview"){
						$report_title = "Executive Overview Report for ".$res_report["company_name"];
					} else {
						$report_title = "Field Data Report for ".$res_report["company_name"]." , Chart Type: ".$display_type[$res_report['display_type']]." , Computation: ".$math_functions[$res_report['math_function']];
					}
					$formIDArr = array();
					$forms_array = array();
					$form_list = "";
					$company_id = $res_report["company_id"];
					$reports = array();
					if($res_report["multiple_form_report"] == 0) {
						array_push($forms_array, array("form_id" => $res_report["form_id"], "form_name" => $res_report["form_name"]));
						array_push($formIDArr, $res_report["form_id"]);
					} else {
						$query_multiple_report = "SELECT f.`form_id`, f.`form_name` FROM `".LA_TABLE_PREFIX."form_multiple_report` AS r LEFT JOIN `".LA_TABLE_PREFIX."forms` AS f ON (r.`form_id` = f.`form_id`) WHERE `report_id` = ?";
						$sth_multiple_report = la_do_query($query_multiple_report, array($res_report["report_id"]), $dbh);

						while($row_multiple_report = la_do_fetch_result($sth_multiple_report)) {
							array_push($forms_array, array("form_id" => $row_multiple_report["form_id"], "form_name" => $row_multiple_report["form_name"]));
							array_push($formIDArr, $row_multiple_report["form_id"]);
						}
					}
					foreach ($forms_array as $form) {
						$form_id = $form["form_id"];
						$form_name = $form["form_name"];
						$reports[$form_id] = array();
						//get the last entry_id for the company on the form
						$entry_id = 0;
						$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
						$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
						$row_entry_id = la_do_fetch_result($sth_entry_id);
						if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
							$entry_id = $row_entry_id['entry_id'];
						}
						$reports[$form_id] = getFormScores($dbh, $form_id, $company_id, $entry_id);
						$form_list .= "<strong>(#".$form["form_id"].")  ".$form["form_name"]."</strong><br>(<span class='gray-status'>Pending: </span>".($reports[$form_id])['count_status_array']["0"].", <span class='red-status'>In Remediation: </span>".($reports[$form_id])['count_status_array']["1"].", <span class='yellow-status'>In Progress: </span>".($reports[$form_id])['count_status_array']["2"].", <span class='green-status'>Compliant: </span>".($reports[$form_id])['count_status_array']["3"].")<br><br>";
					}
					?>
					<h4 class="report-title"><?php echo $report_title; ?></h4>
					<div class="report-brief-content">
						<div class="field-list">
							<label class="field-label">Primary Point of Contact:</label>
							<div class="field-content"><?php echo $res_report["contact_full_name"]; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Contact Email:</label>
							<div class="field-content"><?php echo $res_report["contact_email"]; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Entity:</label>
							<div class="field-content"><?php echo $res_report["company_name"]; ?></div>
						</div>
						<div style="height: 15px;"></div>
						<div class="field-list">
							<label class="field-label">Forms:</label>
							<div class="field-content"><?php echo $form_list; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Scheduled Start Date:</label>
							<div class="field-content"><?php echo $start_date; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Scheduled Completion Date:</label>
							<div class="field-content"><?php echo $completion_date; ?></div>
						</div>
						<div class="field-list">
							<label class="field-label">Report Created:</label>
							<div class="field-content"><?php echo $report_created_date; ?></div>
						</div>
					</div>
					<?php
					if($res_report["math_function"] == "field-note") {

						$res = getFieldNotes($dbh, $la_settings, $formIDArr);
						$field_notes = $res["field_notes"];
						?>
						<div class="report-details" style="display: none;">
							<div style="display: flex; overflow: overlay;">
								<div class="field-note-div">
									<table id="field_note_report" class="hover stripe cell-border data-table" style="width: 100%;" data-table-name="Field note report">
										<thead>
											<th>Form ID</th>
											<th>Form Name</th>
											<th>Field Title</th>
											<th>Field Note</th>
											<th>Assigner</th>
											<th>Assignees</th>
											<th>Date Created</th>
											<th>Status</th>
											<th class="field_link">Field Link</th>
										</thead>
										<tbody>
											<?php
											foreach ($field_notes as $field_note) {
												$status_class = "";
												$staus = "";
												if($field_note["status"] >= 3) {
													$status = "Expired";
													$status_class = "status-expired";
												} else {
													$status = "Active";
													$status_class = "status-active";
												}
											?>
												<tr>
													<td><?php echo $field_note["form_id"]; ?></td>
													<td><?php echo $field_note["form_name"]; ?></td>
													<td><?php echo $field_note["element_title"]; ?></td>
													<td class="<?php echo $status_class; ?>"><?php echo $field_note["note"]; ?></td>
													<td><?php echo $field_note["assigner"]; ?></td>
													<td><?php echo $field_note["assignees"]; ?></td>
													<td><?php echo $field_note["date_created"]; ?></td>
													<td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
													<td><a target="_blank" href="<?php echo "/portal".$field_note["field_link"]; ?>">Go To Field</a></td>
												</tr>
											<?php
											}										
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<?php
					} else {
					?>
						<div class="report-details" style="display: none;">
							<table width="100%" cellspacing="0" cellpadding="0" border="0" id="entries_table">
						<?php
							if ($res_report['math_function'] == 'status-indicator') {
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									//get the last entry_id for the company on the form
									$entry_id = 0;
									$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
									$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
									$row_entry_id = la_do_fetch_result($sth_entry_id);
									if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
										$entry_id = $row_entry_id['entry_id'];
									}
									$max_score_last = ($reports[$form_id])['max_score'];
									$count_status_array = ($reports[$form_id])['count_status_array'];
									$total_indicator_count = array_sum($count_status_array);
									?>
									<tr>
										<td colspan="3">
											<?php
											if($res_report['display_type'] == "sunburst_chart" || $res_report['display_type'] == "polar_chart") {
											?>
												<div class="display-chart" form-id="<?php echo $form_id; ?>" report-name='<?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?>' report-type="status-indicator" chart-type="<?php echo $res_report['display_type']; ?>" data="<?php echo implode(',', $count_status_array); ?>" status-data='<?php 
												echo htmlspecialchars(json_encode($reports[$form_id]["status_element_array"]), ENT_QUOTES,'UTF-8') ?>' style="display: inline-block;width: 48%;"></div>
												<div id="<?php echo 'chart_interact_'.$form_id; ?>" style="display: inline-block;width: 48%;"></div>
											<?php
											} else {
											?>
												<div class="display-chart" form-id="<?php echo $form_id; ?>" report-name='<?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?>' report-type="status-indicator" chart-type="<?php echo $res_report['display_type']; ?>" data="<?php echo implode(',', $count_status_array); ?>" status-data='<?php 
												echo htmlspecialchars(json_encode($reports[$form_id]["status_element_array"]), ENT_QUOTES,'UTF-8') ?>'></div>
											<?php
											}
											?>
										</td>
									</tr>
									<?php
									echo '<tr>';
									echo '<th>Total</th>';
									echo '<th>Indicator Value</th>';
									echo '<th>Status</th>';
									echo '</tr>';
									?>
									<style type="text/css">
										.indicator-detail-wrapper:hover{
											background:#eee !important;
										}
									</style>
									<tr>
										<td><?php echo $count_status_array[0]; ?></td>
										<td>Gray</td>
										<td>Pending</td>
									</tr>
									<tr>
										<td><?php echo $count_status_array[1]; ?></td>
										<td>Red</td>
										<td>In Remediation</td>
									</tr>
									<tr>
										<td><?php echo $count_status_array[2]; ?></td>
										<td>Yellow</td>
										<td>In Progress</td>
									</tr>
									<tr>
										<td><?php echo $count_status_array[3]; ?></td>
										<td>Green</td>
										<td>Compliant</td>
									</tr>
									<tr class="indicator-detail-wrapper">
										<td colspan=3>
											<?php
												$param['checkbox_image'] = 'images/icons/59_blue_16.png';
												$param['company_id'] = $company_id;
												$entry_details_array[$form_id] = la_get_entry_details($dbh, $form_id, $company_id, $entry_id, $param);
												$entry_details =  $entry_details_array[$form_id];
												$statusElementArr = array();
												$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
												$result = la_do_query($sql_query, array($form_id, $company_id, $entry_id), $dbh);
												
												while($row=la_do_fetch_result($result)){
													$statusElementArr[$row['element_id']] = $row['indicator'];
												}
												include('view_report_status_indicator_detail.php');
											?>
										</td>
									</tr>
								<?php
								}
								?>
								<?php
							} else if($res_report['math_function'] == 'risk') {
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									
									$max_score_last = ($reports[$form_id])['max_score'];
									$count_status_array = ($reports[$form_id])['count_status_array'];
									$total_indicator_count = array_sum($count_status_array);
									if($max_score_last > 0) {
									?>
										<tr>
											<td colspan="3">
											<?php
											if($res_report['display_type'] == "sunburst_chart" || $res_report['display_type'] == "polar_chart") {
											?>
												<div class="display-chart" colspan="3" form-id="<?php echo $form_id; ?>" report-name='<?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?>' report-type="risk" chart-type="<?php echo $res_report['display_type']; ?>" data="<?php echo implode(',', $count_status_array); ?>" risk-score = "<?php echo $reports[$form_id]['score_percentage']; ?>" max-score="<?php echo $max_score_last; ?>" risk-data='<?php 
												echo htmlspecialchars(json_encode($reports[$form_id]["status_element_array"]), ENT_QUOTES,"UTF-8") ?>' style="display: inline-block;width: 48%;"></div>
												<div id="<?php echo 'chart_interact_'.$form_id; ?>" style="display: inline-block;width: 48%;"></div>
											<?php
											} else {
											?>
												<div class="display-chart" colspan="3" form-id="<?php echo $form_id; ?>" report-name='<?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?>' report-type="risk" chart-type="<?php echo $res_report['display_type']; ?>" data="<?php echo implode(',', $count_status_array); ?>" max-score="<?php echo $max_score_last; ?>" risk-score = "<?php echo $reports[$form_id]['score_percentage']; ?>" risk-data='<?php 
												echo htmlspecialchars(json_encode($reports[$form_id]["status_element_array"]), ENT_QUOTES, "UTF-8") ?>'></div>
											<?php
											}
											?>
											</td>
										</tr>
										<?php
										echo '<tr>';
										echo '<th style="text-align: center;">Field Label</th>';
										echo '<th style="text-align: center;">Score</th>';
										echo '<th style="text-align: center;">Score Percentage</th>';
										echo '</tr>';

										if(count($reports[$form_id]) > 0) {
											foreach ($reports[$form_id]['status_element_array'] as $key => $value) {
												if (!isset($value['score'])) continue;
												?>
												
												<tr>
													<td><?= $value['label']; ?></td>
													<td style="text-align:right; border:1px dotted #8EACCF"><?= $value['score'] ?></td>
													<td style="text-align:right; border:1px dotted #8EACCF">-</td>
												</tr>
												<?php
											}
											?>
											<tr style="font-weight: bold;">
												<td>Total Score</td>
												<td style="text-align:right; border:1px dotted #8EACCF"><?= $reports[$form_id]['total_score'] ?></td>
												<td style="text-align:right; border:1px dotted #8EACCF"><?= $reports[$form_id]['score_percentage'] ?>%</td>
											</tr>
											<?php
										} else {
											?>
											<tr><td colspan="3" style="border-bottom:none; color: black !important; text-align: center;">No risk data found!</td></tr>
											<?php	
										}
									} else {
										echo '<tr>';
										echo '<th style="text-align: center;">Field Label</th>';
										echo '<th style="text-align: center;">Score</th>';
										echo '<th style="text-align: center;">Score Percentage</th>';
										echo '</tr>';
										?>
										<tr><td colspan="3" style="border-bottom:none; color: black !important; text-align: center;">No risk data found!</td></tr>
										<?php
									}
								}
								?>
								<?php
							} else if($res_report['math_function'] == 'maturity') {
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									
									$max_score_last = ($reports[$form_id])['max_score'];
									$count_status_array = ($reports[$form_id])['count_status_array'];
									$total_indicator_count = array_sum($count_status_array);
									if($total_indicator_count == 0) {
										$status_percentage_last = 0;
									} else {
										$status_percentage_last = round($count_status_array[3] / array_sum($count_status_array) * 100);
									}							
									$score_percentage_last = ($reports[$form_id])['score_percentage'];
									$maturity_last = round((100 + $status_percentage_last - $score_percentage_last) / 2);
									?>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;"><?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?></h4></td></tr>
									<tr>
										<td class="display-chart" report-type="maturity" chart-type="status" data="<?php echo implode(',', $count_status_array); ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $status_percentage_last; ?>%</strong>
										</td>
										<td class="display-chart" report-type="maturity" chart-type="score" data="<?php echo $score_percentage_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $score_percentage_last ?>%</strong>
										</td>
										<td class="display-chart" report-type="maturity" chart-type="maturity" data="<?php echo $maturity_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $maturity_last; ?>%</strong>
										</td>
									</tr>
									<?php
									if($max_score_last > 0) {
										echo '<tr>';
										echo '<th style="text-align: center;">Field Label</th>';
										echo '<th style="text-align: center;">Score</th>';
										echo '<th style="text-align: center;">Score Percentage</th>';
										echo '</tr>';

										if(count($reports[$form_id]) > 0) {
											foreach ($reports[$form_id]['status_element_array'] as $key => $value) {
												if (!isset($value['score'])) continue;
												?>
												
												<tr>
													<td><?= $value['label']; ?></td>
													<td style="text-align:right; border:1px dotted #8EACCF"><?= $value['score'] ?></td>
													<td style="text-align:right; border:1px dotted #8EACCF">-</td>
												</tr>
												<?php
											}
											?>
											<tr style="font-weight: bold;">
												<td>Total Score</td>
												<td style="text-align:right; border:1px dotted #8EACCF"><?= $reports[$form_id]['total_score'] ?></td>
												<td style="text-align:right; border:1px dotted #8EACCF"><?= $score_percentage_last ?>%</td>
											</tr>
											<?php
										} else {
											?>
											<tr><td colspan="3" style="border-bottom:none; color: black !important; text-align: center;">No risk data found!</td></tr>
											<?php	
										}
									} else {
										echo '<tr>';
										echo '<th style="text-align: center;">Field Label</th>';
										echo '<th style="text-align: center;">Score</th>';
										echo '<th style="text-align: center;">Score Percentage</th>';
										echo '</tr>';
										?>
										<tr><td colspan="3" style="border-bottom:none; color: black !important; text-align: center;">No risk data found!</td></tr>
										<?php
									}
								}
							} else if($res_report['math_function'] == 'compliance-dashboard') {
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									
									$max_score_last = ($reports[$form_id])['max_score'];
									$count_status_array = ($reports[$form_id])['count_status_array'];
									$total_indicator_count = array_sum($count_status_array);
									if($total_indicator_count == 0) {
										$status_percentage_last = 0;
									} else {
										$status_percentage_last = round($count_status_array[3] / array_sum($count_status_array) * 100);
									}
									$score_percentage_last = ($reports[$form_id])['score_percentage'];
									$maturity_last = round((100 + $status_percentage_last - $score_percentage_last) / 2);
									$maturityArr = array();
									$scoreArr = ($reports[$form_id])['score_array'];
									foreach ($scoreArr as $key => $value) {
										$maturityArr[$key] = round((100 + $status_percentage_last - $value) / 2);
									}
									$dateArr = ($reports[$form_id])['dates'];

									?>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;"><?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?></h4></td></tr>
									<tr>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;">Status Timeline</h4></td></tr>
									<tr>
						        		<td colspan="3" class="style-none display-chart" report-type="compliance-dashboard" data-score="<?php echo implode(',', $scoreArr); ?>" data-maturity="<?php echo implode(',', $maturityArr); ?>" data-date ="<?php echo implode(',', $dateArr); ?>">
						        			<div style="height:400px; width: 100%;"></div>
						        		</td>
									</tr>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;">Status Summary</h4></td></tr>

									<tr>
										<td class="display-chart style-none" report-type="maturity" chart-type="status" data="<?php echo implode(',', $count_status_array); ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $status_percentage_last; ?>%</strong>
										</td>
										<td class="display-chart style-none" report-type="maturity" chart-type="score" data="<?php echo $score_percentage_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $score_percentage_last ?>%</strong>
										</td>
										<td class="display-chart style-none" report-type="maturity" chart-type="maturity" data="<?php echo $maturity_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $maturity_last; ?>%</strong>
										</td>
									</tr>
									<style type="text/css">
										.indicator-detail-wrapper:hover{
											background:#eee !important;
										}
										.hidden_in_compliance_dashboard{
											display: none!important;
										}
									</style>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;">Status Detail</h4></td></tr>
									<tr class="indicator-detail-wrapper">
										<td colspan=3 class="style-none">
											<?php
												$param['checkbox_image'] = 'images/icons/59_blue_16.png';
												//get the last entry_id for the company on the form
												$entry_id = 0;
												$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
												$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
												$row_entry_id = la_do_fetch_result($sth_entry_id);
												if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
													$entry_id = $row_entry_id['entry_id'];
												}
												$entry_details_array[$form_id] = la_get_entry_details($dbh,$form_id, $company_id, $entry_id, $param);
												$entry_details =  $entry_details_array[$form_id];
												$statusElementArr = array();
												$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = {$form_id} AND `company_id` = {$company_id}";
												$result = la_do_query($sql_query,array(),$dbh);
												
												while($row=la_do_fetch_result($result)){
													$statusElementArr[$row['element_id']] = $row['indicator'];
												}
												include('view_report_status_indicator_detail.php');
											?>
										</td>
									</tr>
									<?php
								}
							} else if($res_report['math_function'] == 'executive-overview') {
								?>
									<style type="text/css">
										.report-details {
											width: 60%;
											margin: auto;
										}
									</style>
								<?php
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									
									$max_score_last = ($reports[$form_id])['max_score'];
									$count_status_array = ($reports[$form_id])['count_status_array'];
									$total_indicator_count = array_sum($count_status_array);
									if($total_indicator_count == 0) {
										$status_percentage_last = 0;
									} else {
										$status_percentage_last = round($count_status_array[3] / array_sum($count_status_array) * 100);
									}
									$score_percentage_last = ($reports[$form_id])['score_percentage'];
									$maturity_last = round((100 + $status_percentage_last - $score_percentage_last) / 2);
									$maturityArr = array();
									$scoreArr = ($reports[$form_id])['score_array'];
									foreach ($scoreArr as $key => $value) {
										$maturityArr[$key] = round((100 + $status_percentage_last - $value) / 2);
									}
									$dateArr = ($reports[$form_id])['dates'];

									?>									
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;"><?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?></h4></td></tr>
									<tr>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;">Status Timeline</h4></td></tr>
									<tr>
						        		<td colspan="3" class="style-none display-chart" report-type="compliance-dashboard" data-score="<?php echo implode(',', $scoreArr); ?>" data-maturity="<?php echo implode(',', $maturityArr); ?>" data-date ="<?php echo implode(',', $dateArr); ?>">
						        			<div style="height:400px; width: 100%;"></div>
						        		</td>
									</tr>
									<tr><td colspan="3" style="border-bottom: none!important;"><h4 style="text-align: center;">Status Summary</h4></td></tr>

									<tr>
										<td class="display-chart style-none" report-type="maturity" chart-type="status" data="<?php echo implode(',', $count_status_array); ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $status_percentage_last; ?>%</strong>
										</td>
										<td class="display-chart style-none" report-type="maturity" chart-type="score" data="<?php echo $score_percentage_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $score_percentage_last ?>%</strong>
										</td>
										<td class="display-chart style-none" report-type="maturity" chart-type="maturity" data="<?php echo $maturity_last; ?>" style="position: relative;">
											<section style="height: 300px!important"></section>
											<strong style="position:absolute;top: 50%; left: 47%;font-size:18px;font-family:inherit;font-weight:bold; display:none;"><?= $maturity_last; ?>%</strong>
										</td>
									</tr>
									<?php
								}
							} else {
								foreach ($forms_array as $form) {
									$form_id = $form["form_id"];
									$form_name = $form["form_name"];
									$reports[$form_id] = array();
									//get the last entry_id for the company on the form
									$entry_id = 0;
									$query_entry_id = "SELECT DISTINCT `entry_id` FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? ORDER BY entry_id DESC LIMIT 1";
									$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
									$row_entry_id = la_do_fetch_result($sth_entry_id);
									if(isset($row_entry_id['entry_id']) && !empty($row_entry_id['entry_id'])) {
										$entry_id = $row_entry_id['entry_id'];
									}
									$reports[$form_id] = getScore($dbh, $report_id, $form_id, $company_id, $entry_id);
									$scoreArr = array();
									$dateArr = array();
									if(count($reports[$form_id]) > 0) {
										foreach($reports[$form_id] as $key => $values) {
											$insert_id = $values['id'];
											$insert_date = $values['date_created'];
											unset($values['date_created']);
											unset($values['id']);
											$only_values = array_values($values);
											$score = 0;
											$score_mul = 1;
											$tmpArray = array();
											foreach($only_values as $keys => $value) {
												if(is_numeric($value) == true) {
													array_push($tmpArray, $value);
													if($res_report['math_function'] == "sum" || $res_report['math_function'] == "status-indicator" ||
													$res_report['math_function'] == "average" || $res_report['math_function'] == "addition") {
														$score += $value;
													}
													if($res_report['math_function'] == "multiplication") {
														if($value > 0) {
															$score_mul *= $value;
														}
													}
												}
											}
											if($res_report['math_function'] == "median"){
												$score = calculateMedian($tmpArray);
											}
											if($res_report['math_function'] == "multiplication"){
												$score = $score_mul;
											}
											if($res_report['math_function'] == "average"){
												$score = round($score/count($tmpArray));
											}
											array_push($scoreArr, $score);
											array_push($dateArr, date('m/d/Y', strtotime($insert_date)));										
											$ikLoop++;
										}
										?>
										<tr>
											<td colspan="3" class="display-chart" report-name='<?php echo $report_title." ( Form #: ".$form_id.", Form Name: ".$form_name." )"; ?>' report-type="field-data" chart-type="<?php echo $res_report['display_type']; ?>" data-score="<?php echo implode(',', $scoreArr); ?>" data-date ="<?php echo implode(',', $dateArr); ?>"></td>
										</tr>
										<tr>
											<th>Score</th>
											<th>State</th>
											<th>Date (N/A)</th>
										</tr>
										<?php
										for($i=0; $i<count($dateArr); $i++){
											?>
											<tr>
												<td><?php echo $scoreArr[$i]; ?></td>
												<td>-</td>
												<td><?php echo $dateArr[$i]; ?></td>
											</tr>
											<?php
										}
									} else {
										?>
										<tr><td colspan="3" style="border-bottom:none;">No data found!</td></tr>
										<?php
									}
								}
							}
						?>
							</table>
						</div>
				<?php
					}
				}
			} else {
				echo "Report doesn't exist.";
			}
		?>
	</div>
</div>

<?php
	$cb = function ($fn) {
	    return $fn;
	};
	require('portal-footer.php'); 
?>
<div id="processing-dialog-file" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">
		<?php if( isset($la_settings['file_viewer_download_option']) && ($la_settings['file_viewer_download_option'] == 1) ) { ?>
			<div style="text-align: right;margin-bottom: 10px;">
				<a href="#" id="file_viewer_download_button" class="bb_button bb_small bb_green" download> 
					<img src="images/navigation/FFFFFF/24x24/Save.png"> Download
				</a>
			</div>
		<?php } ?>
	<div id="document-preview-content" style="height: 440px;">
		<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
	</div>
</div>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="/itam-shared/js/state_abbrvs.js"></script>
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/modules/sunburst.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts-more.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts-3d.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/maps/modules/data.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/maps/modules/exporting.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/maps/modules/offline-exporting.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/mapdata/countries/us/us-all.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/view_report.js"></script>