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

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$form_id = $_GET["form_id"];
$entity_id = $_SESSION["la_client_entity_id"];

$saint_report_list = get_saint_report_list($dbh, $form_id, $entity_id);
$nessus_report_list = get_nessus_report_list($dbh, $form_id, $entity_id);
$has_saint_report = (!is_null($saint_report_list) && (count($saint_report_list) > 0)) ? true : false;
$has_nessus_report = (!is_null($nessus_report_list) && (count($nessus_report_list) > 0)) ? true : false;
if(!$has_saint_report && !$has_nessus_report) {
	//create entering new entry mode
	header("Location: /portal/view.php?id=".$form_id."&entry_id=".time());
	exit();
}
$header_data =<<<EOT
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
require('portal-header.php');
?>
<?php la_show_message(); ?>
<div class="content_body">
	<style type="text/css">
		.action-view:hover {
			cursor: pointer;
		}
		.action-delete:hover {
			cursor: pointer;
		}
	</style>
	<input type="hidden" id="form_id" value="<?php echo $form_id; ?>">
	<h3 style="margin-bottom: 10px;">- SAINT Scan Reports</h3>
	<?php
		if($has_saint_report) {
	?>
			<table id="saint_list_table" class="hover stripe cell-border data-table" style="width: 100%;">
				<thead>
					<th>#</th>
					<th>Scan Name</th>
					<th>Scan Level</th>
					<th>Date Generated</th>
				</thead>
				<tbody>
					<?php
						$i = 0;
						foreach ($saint_report_list as $report) {
							$i++;
							$report_info = json_decode(json_encode(simplexml_load_string($report["data"])), true)["scan_information"];
					?>
							<tr saint-id="<?php echo $report['report_id']?>">
								<td class="action-view"><?php echo $i; ?></td>
								<td class="action-view"><?php echo $report["job_name"]; ?></td>
								<td class="action-view"><?php echo $report_info["scan_level"]; ?></td>
								<td class="action-view"><?php echo date('h:i:s A m-d-Y', $report['import_datetime']); ?></td>
							</tr>
					<?php
						}
					?>
				</tbody>
			</table>
	<?php
		} else {
	?>
			<p style="padding-left: 20px;">You don't have any SAINT scan reports imported at the moment.</p>
	<?php
		}
	?>

	<h3 style="margin: 20px 0px 10px 0px;">- Nessus Scan Reports</h3>
	<?php
		if($has_nessus_report) {
	?>
			<table id="nessus_list_table" class="hover stripe cell-border data-table" style="width: 100%;">
				<thead>
					<th>#</th>
					<th>Scan Name</th>
					<th>Scan Level</th>
					<th>Date Generated</th>
				</thead>
				<tbody>
					<?php
						$i = 0;
						foreach ($nessus_report_list as $report) {
							$i++;
					?>
							<tr nessus-id="<?php echo $report['report_id']?>">
								<td class="action-view"><?php echo $i; ?></td>
								<td class="action-view"><?php echo $report["scan_name"]; ?></td>
								<td class="action-view"><?php echo $report["scanner_name"]; ?></td>
								<td class="action-view"><?php echo date('h:i:s A m-d-Y', $report['import_datetime']); ?></td>
							</tr>
					<?php
						}
					?>
				</tbody>
			</table>
	<?php
		} else {
	?>
			<p style="padding-left: 20px;">You don't have any Nessus scan reports imported at the moment.</p>
	<?php
		}
	?>
</div>

<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/imported_report_list.js"></script>
EOT;

require('portal-footer.php');
?>