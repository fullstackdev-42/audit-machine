<?php

/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com

More info at: http://lazarusalliance.com
********************************************************************************/

require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('../itam-shared/includes/integration-helper-functions.php');
require('includes/check-client-session-ask.php');

$header_data =<<<EOT
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
require('portal-header.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$pdf_header_img = 'data:image/' . pathinfo($la_settings["admin_image_url"], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($la_settings["admin_image_url"]));

$form_id = $_GET["form_id"];
$entity_id = $_SESSION["la_client_entity_id"];
$saint_enabled = check_saint_enabled($dbh, $form_id, $entity_id);
if(!$saint_enabled) {
	header("Location: /portal/view.php?id=".$form_id);
	exit();
}

$saint_report_id = $_GET["saint_report_id"];
$saint_report = get_single_saint_report($dbh, $saint_report_id);

?>

<div class="content_body">
	<style type="text/css">
		td, th {			
			text-align: center;
			padding: 8px;
			vertical-align: middle;
		}
		td {			
			word-break: break-word;
		}
		.dataTables_wrapper {
			margin-top: 10px;
		}
		.dataTables_filter {
			margin-top: 4px;
		}
		.toolbar {
			width: fit-content;
		    float: left;
		    margin-left: 4px;
		}
		.import-data-table td, .import-data-table th {
			border: 1px solid black;
		}
		.import-data-table {
			border-collapse: collapse;
			width: 100%;
		}
	</style>
	<?php
	if(!$saint_report) {
		echo "SAINT report data is not available.";
	} else {
		$overview = $saint_report["overview"];
		$details = $saint_report["details"]["host_info"];
	?>
		<h4>- Host List</h4>
		<p>This table presents an overview of the hosts discovered on the network.</p>
		<div style="overflow-x:auto;">
			<table id="host_list_table" class="hover stripe cell-border data-table" style="width: 100%;" data-table-name = "Host List">
				<thead>
					<th></th>
					<th template-code="CGRC-5217">Host Name</th>
					<th template-code="CGRC-5218">IP Address</th>
					<th template-code="CGRC-5219">Host Type</th>
					<th template-code="CGRC-5220">Critical Problems</th>
					<th template-code="CGRC-5221">Areas of Concern</th>
					<th template-code="CGRC-5222">Potential Problems</th>
				</thead>
				<?php
					foreach ($overview["hosts"]["host"] as $host) {
				?>
					<tr>
						<td></td>
						<td><?php echo $host["hostname"]; ?></td>
						<td><?php echo $host["ipaddr"]; ?></td>
						<td><?php echo $host["hosttype"]; ?></td>
						<td><?php echo $host["reds"]; ?></td>
						<td><?php echo $host["yellows"]; ?></td>
						<td><?php echo $host["browns"]; ?></td>
					</tr>
				<?php
					}
				?>
			</table>
		</div>
		
		<h4>- Vulnerability List</h4>
		<ul id="la_form_list" class="la_form_list">
		<?php
			foreach ($overview["vulnerabilities"]["host_info"] as $h_key => $vulnerability ) {
				$i++;
		?>
				<li>
					<div class="middle_form_bar" data-vulnerability-id="<?php echo $i; ?>">
			            <h3 class="hostname">
			            	<?php echo "Host Name: ".$vulnerability["hostname"]; ?>
			            </h3>
			        </div>
			        <div id="data-vulnerability-detail-<?php echo $i; ?>" style="color:#000; margin:10px 20px; display:none; overflow-x:auto;">
					<?php
						if(is_null($vulnerability["vulnerability"])){
					?>
							<p>Nothing to report</p>
					<?php
						} else {
					?>
							<table id="vulnerability_list_table_<?php echo $i;?>" class="hover stripe cell-border data-table" data-table-name = "Vulnerability list of <?php echo $vulnerability["hostname"]; ?>" style="width: 100%!important;">
								<thead>
									<th></th>
									<th template-code="CGRC-5223">Port</th>
									<th template-code="CGRC-5224">Severity</th>
									<th template-code="CGRC-5225">Vulnerability / Service</th>
									<th template-code="CGRC-5226">Class</th>
									<th template-code="CGRC-5227">CVE</th>
									<th template-code="CGRC-5228">Max. CVSSv2 Base Score</th>
									<th template-code="CGRC-5229">Impact</th>
									<th template-code="CGRC-5230">Resolution</th>
									<th template-code="CGRC-5231">References</th>
									<th template-code="CGRC-5232">Technical Details</th>
								</thead>
								<tbody>
								<?php
								if(!is_null($vulnerability["vulnerability"]["port"])) {
									//vulnerability has just 1 row
								?>
									<tr>
										<td></td>
										<td><?php $port = is_string($vulnerability["vulnerability"]["port"]) ? $vulnerability["vulnerability"]["port"] : ""; echo $port; ?></td>
										<td><?php $severity = is_string($vulnerability["vulnerability"]["severity"]) ? $vulnerability["vulnerability"]["severity"] : ""; echo $severity; ?></td>
										<td><?php $description = is_string($vulnerability["vulnerability"]["description"]) ? $vulnerability["vulnerability"]["description"] : ""; echo $description; ?></td>
										<td><?php $class = is_string($vulnerability["vulnerability"]["class"]) ? $vulnerability["vulnerability"]["class"] : ""; echo $class; ?></td>
										<td><?php $cve = is_string($vulnerability["vulnerability"]["cve"]) ? $vulnerability["vulnerability"]["cve"] : ""; echo $cve; ?></td>
										<td><?php $cvss_base_score = is_string($vulnerability["vulnerability"]["cvss_base_score"]) ? $vulnerability["vulnerability"]["cvss_base_score"] : ""; echo $cvss_base_score; ?></td>
										<td><?php $impact = is_string($details[$h_key]["vulnerability"]["impact"]) ? $details[$h_key]["vulnerability"]["impact"] : ""; echo $impact; ?></td>
										<td><?php $resolution = is_string($details[$h_key]["vulnerability"]["resolution"]) ? $details[$h_key]["vulnerability"]["resolution"] : ""; echo $resolution; ?></td>
										<td><?php $reference = is_string($details[$h_key]["vulnerability"]["reference"]) ? $details[$h_key]["vulnerability"]["reference"] : ""; echo $reference; ?></td>
										<td><?php $vuln_details = is_string($details[$h_key]["vulnerability"]["vuln_details"]) ? $details[$h_key]["vulnerability"]["vuln_details"] : ""; echo $vuln_details; ?></td>
									</tr>
								<?php
								} else {
									////vulnerability has multiple rows
									foreach ($vulnerability["vulnerability"] as $v_key => $value) {
									?>
										<tr>
											<td></td>
											<td><?php $port = is_array($value["port"]) ? "" : $value["port"]; echo $port; ?></td>
											<td><?php echo $value["severity"]; ?></td>
											<td><?php echo $value["description"]; ?></td>
											<td><?php echo $value["class"]; ?></td>
											<td><?php echo $value["cve"]; ?></td>
											<td><?php echo $value["cvss_base_score"]; ?></td>
											<td><?php echo $details[$h_key]["vulnerability"][$v_key]["impact"]; ?></td>
											<td><?php echo $details[$h_key]["vulnerability"][$v_key]["resolution"]; ?></td>
											<td><?php echo $details[$h_key]["vulnerability"][$v_key]["reference"]; ?></td>
											<td><?php $vuln_details = is_array($details[$h_key]["vulnerability"][$v_key]["vuln_details"]) ? "" : $details[$h_key]["vulnerability"][$v_key]["vuln_details"]; echo $vuln_details; ?></td>
										</tr>
									<?php
									}
								}
								?>
								</tbody>
							</table>
					<?php
						}
					?>
			        </div>
				</li>
		<?php
			}
		?>
		</ul>
		<input class="form-info" type="hidden" form-id="<?php echo $form_id; ?>" user-type="user" user-id="<?php echo $_SESSION['la_client_entity_id']; ?>" />
		<input id="pdf_header_img" type="hidden" value="<?php echo $pdf_header_img; ?>">
	<?php
	}
	?>
</div>
<div id="dialog-select-error-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p>Please select a row on the table.</p>
</div>
<div id="dialog-error-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p style="color: red;">Importing failed. Please try again later.</p>
</div>
<div id="dialog-success-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p>Data has been imported successfully.</p>
	<p>If you want to go to the form, please click <b><a id="go_to_form" class="entry_link entry-link-preview" href="" style="display: contents;">here</a></b></p>
</div>
<div id="dialog-import-confirm-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p>Do you want to import the data of the selected row into the form?</p>  
</div>
<?php
$footer_data =<<<EOT
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/saint_report.js"></script>
EOT;
require('portal-footer.php');
?>