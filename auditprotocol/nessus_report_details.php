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

$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;

$current_nav_tab = 'manage_forms';
require('includes/header.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$pdf_header_img = 'data:image/' . pathinfo($la_settings["admin_image_url"], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($la_settings["admin_image_url"]));

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
$sth = la_do_query($query, array($form_id), $dbh);
$row = la_do_fetch_result($sth);
if(!empty($row)){
	$row['form_name'] = la_trim_max_length($row['form_name'],55);
	$form_name = noHTML($row['form_name']);
}

$nessus_report_id = $_GET["nessus_report_id"];
$nessus_report = get_single_nessus_report($dbh, $nessus_report_id);

?>
<style type="text/css">
	.middle_form_bar {
		display: flex;
	}
	.ui-dialog .ui-dialog-content {
		text-align: center;
	}
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
<div id="content" class="full">
	<div class="post" style="padding: 0px;">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left;">
					<h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> Nessus Scan Report Data</h2>
					<p>You can import this report data into the form by selecting a row on the tables or export as CSV, Excel or PDF files.</p>
				</div>
				<div style="float: right;">
					<a href="<?php echo 'view.php?id='.$form_id.'&company_id='.$company_id.'&entry_id='.$entry_id; ?>" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Forward.png">  Skip </a>
				</div>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<div class="content_body">			
			<?php
			if(!$nessus_report) {
				echo "Nessus report data is not available.";
			} else {
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
							<th template-code="CGRC-4569-1">Critical</th>
							<th template-code="CGRC-4569-2">High</th>
							<th template-code="CGRC-4569-3">Medium</th>
							<th template-code="CGRC-4569-4">Low</th>
							<th template-code="CGRC-4569-5">Info</th>
						</thead>
						<?php
							foreach ($nessus_report as $host) {
						?>
							<tr>
								<td></td>
								<td><?php echo $host["hostname"]; ?></td>
								<td><?php echo $host["ip_address"]; ?></td>
								<td><?php echo $host["host_type"]; ?></td>
								<td><?php echo $host["critical"]; ?></td>
								<td><?php echo $host["high"]; ?></td>
								<td><?php echo $host["medium"]; ?></td>
								<td><?php echo $host["low"]; ?></td>
								<td><?php echo $host["info"]; ?></td>
							</tr>
						<?php
							}
						?>
					</table>
				</div>
				
				<h4>- Vulnerability List</h4>
				<ul id="la_form_list" class="la_form_list">
				<?php
					foreach ($nessus_report as $host) {
						$i++;
				?>
						<li style="display: block;">
							<div class="middle_form_bar" data-vulnerability-id="<?php echo $i; ?>">
								<h3 class="hostname">
									<?php echo "Host Name: ".$host["hostname"]; ?>
								</h3>
							</div>
							<div id="data-vulnerability-detail-<?php echo $i; ?>" style="color:#000; margin:10px 20px; display:none; overflow-x:auto;">
							<?php
								if(is_null($host["vulnerability_details"])){
							?>
									<p>Nothing to report</p>
							<?php
								} else {
							?>
									<table id="vulnerability_list_table_<?php echo $i;?>" class="hover stripe cell-border data-table" data-table-name = "Vulnerability list of <?php echo $host["hostname"]; ?>" style="width: 100%!important;">
										<thead>
											<th></th>
											<th template-code="CGRC-5223">Port</th>
											<th template-code="CGRC-5224">Risk</th>
											<th template-code="CGRC-5226">Class</th>
											<th template-code="CGRC-5227">CVE</th>
											<th template-code="CGRC-5228">CVSS Base Score</th>
											<th template-code="CGRC-5225">Synopsis</th>
											<th template-code="CGRC-5229">Description</th>
											<th template-code="CGRC-5230">Resolution</th>
											<th template-code="CGRC-5231">References</th>
											<th template-code="CGRC-5232">Technical Details</th>
										</thead>
										<tbody>
										<?php
										foreach ($host["vulnerability_details"] as $value) {
										?>
											<tr>
												<td></td>
												<td><?php echo $value["port"]; ?></td>
												<td><?php echo $value["risk"]; ?></td>
												<td><?php echo $value["class"]; ?></td>
												<td><?php echo $value["cve"]; ?></td>
												<td><?php echo $value["cvss_base_score"]; ?></td>
												<td><?php echo $value["synopsis"]; ?></td>
												<td><?php echo $value["description"]; ?></td>
												<td><?php echo $value["resolution"]; ?></td>
												<td><?php echo $value["references"]; ?></td>
												<td><?php echo $value["technical_details"]; ?></td>
											</tr>
										<?php
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
				<input class="form-info" type="hidden" form-id="<?php echo $form_id; ?>" user-type="admin" user-id="<?php echo $_SESSION['la_user_id']; ?>" />
				<input id="pdf_header_img" type="hidden" value="<?php echo $pdf_header_img; ?>">
			<?php
			}
			?>
		</div>
	</div>
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
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/nessus_report.js"></script>
EOT;
require('includes/footer.php');
?>