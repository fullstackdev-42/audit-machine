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
require('includes/users-functions.php');
require('../itam-shared/includes/integration-helper-functions.php');
require('includes/check-client-session-ask.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$form_id = $_GET["form_id"];
$company_id = time();
$entry_id = $company_id;

//check permission, is the user allowed to access this page?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
	$user_perms = la_get_user_permissions($dbh, $form_id, $_SESSION['la_user_id']);

	//this page need edit_form permission
	if(empty($user_perms['edit_form']) || empty($user_perms['edit_entries']) || empty($user_perms['view_entries'])) {
		header("Location: /auditprotocol/view.php?id=".$form_id."&company_id=".$company_id."&entry_id=".$entry_id);
		exit();
	}
}
//get form name
$query  = "SELECT form_name FROM ".LA_TABLE_PREFIX."forms WHERE form_id = ?";
$sth = la_do_query($query, array($form_id),$dbh);
$row = la_do_fetch_result($sth);

if(!empty($row)){
	$row['form_name'] = la_trim_max_length($row['form_name'], 55);
	$form_name = noHTML($row['form_name']);
}

$saint_report_list = get_all_saint_reports($dbh, $form_id);
$nessus_report_list = get_all_nessus_reports($dbh, $form_id);
$has_saint_report = (!is_null($saint_report_list) && (count($saint_report_list) > 0)) ? true : false;
$has_nessus_report = (!is_null($nessus_report_list) && (count($nessus_report_list) > 0)) ? true : false;
if(!$has_saint_report && !$has_nessus_report) {
	header("Location: /auditprotocol/view.php?id=".$form_id."&company_id=".$company_id."&entry_id=".$entry_id);
	exit();
}
$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;

$current_nav_tab = 'manage_forms';
require('includes/header.php');
?>
<style type="text/css">
	.action-view:hover {
		cursor: pointer;
	}
	.action-delete:hover {
		cursor: pointer;
	}
</style>
<div id="content" class="full">
	<div class="post" style="padding: 0px;">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left;">
					<h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> My Reports From Other Services</h2>
					<p>You can easily make an entry by using one of the imported reports below.</p>
				</div>
				<div style="float: right;">
					<a href="<?php echo 'view.php?id='.$form_id.'&entry_id='.time(); ?>" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Forward.png">  Skip </a>
				</div>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<?php la_show_message(); ?>
		<div class="content_body">
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
							<th>Delete</th>
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
										<td>
											<a class="action-delete" title="Delete"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a>
										</td>
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
							<th>Delete</th>
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
										<td>
											<a class="action-delete" title="Delete"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a>
										</td>
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
	</div>
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-confirm-report-delete" title="Are you sure you want to delete selected report?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="delete_report_id">
	<input type="hidden" id="delete_report_type">
	<p id="dialog-confirm-report-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-report-delete-info">The report data will be deleted permanently.</strong><br/><br/>
	</p>
</div>
<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/imported_report_list.js"></script>
EOT;
require('includes/footer.php');
?>