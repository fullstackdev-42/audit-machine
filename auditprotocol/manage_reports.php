<?php
/********************************************************************************
 IT Audit Machine
  
 Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	date_default_timezone_set('America/Los_Angeles');
	require('includes/init.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	require('includes/users-functions.php');
	require('includes/entry-functions.php');
	require('includes/report-helper-function.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	if(isset($_GET['del_report_id'])){
		
		$query_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_report` WHERE `report_id` = ?";
		la_do_query($query_delete, array($_GET['del_report_id']), $dbh);
		
		$query_element_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_report_elements` WHERE `report_id` = ?";
		la_do_query($query_element_delete, array($_GET['del_report_id']), $dbh);
		
		$query_multiple_delete = "DELETE FROM `".LA_TABLE_PREFIX."form_multiple_report` WHERE `report_id` = ?";
		la_do_query($query_multiple_delete, array($_GET['del_report_id']), $dbh);

		$_SESSION['LA_SUCCESS'] = 'Report has been deleted.';
		
		header("location:manage_reports.php");
		exit();
	}
	
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);
		
$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<link type="text/css" href="css/dropui.css" rel="stylesheet" />
<style>
.dropui-menu li a{
	padding: 2px 0 2px 27px;
	font-size: 115%;
}
.dropui .dropui-tab{
	font-size: 95%;
}
.middle_form_bar.red {
	background-color: #E43A45!important;
}
.middle_form_bar.yellow {
	background-color: #F3C200!important;
}
.middle_form_bar.green {
	background-color: #33BF8C!important;
}
.middle_form_bar {
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-box-align: center;
	-ms-flex-align: center;
	align-items: center;
}
.middle_form_bar h3{
	flex-basis: 50%;
	-webkit-box-flex: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
}
span.completion-date{
	color: black;
	-webkit-box-flex: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
}
</style>
EOT;
	
	$current_nav_tab = 'manage_reports';
	require('includes/header.php');

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
		"field-note" => "Field Note",
		"audit-dashboard" => "Audit Dashboard",
		"artifact-management" => "Artifact Management",
		"executive-overview" => "Executive Overview",
		"template-code" => "Template Code",
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
?>
<div id="content" class="full">
	<div class="post manage_forms">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left">
					<?php if(empty($_SESSION['is_examiner'])){ ?>
						<h2>Existing Reports</h2>
						<p>Create, edit and manage your reports</p>
					<?php } else {?>
						<h2>My Reports</h2>
						<p>Depending on your subscription level, you may not see reports here. Only ITAM administrators have the ability to create dynamic reports generated from data within the system. If you would like to upgrade your subscription, <a href="https://continuumgrc.com/contact/"> please contact us today!</a><br>Additionally, you will also only see reports for modules that you are actively subscribed to in your catalog.</p>
					<?php }?>
				</div>
				<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])){ ?>
					<div style="float: right;margin-right: 5px"> <a href="add_reports.php" id="button_create_form" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Create_new_report.png">  Create New Report </a> </div>
				<?php } ?>
				<?php la_show_message(); ?>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<div class="content_body">
			<ul id="la_form_list" class="la_form_list">
				<?php 
				if(empty($_SESSION['is_examiner'])) {
					$query_report = "SELECT `".LA_TABLE_PREFIX."form_report`.*, (SELECT company_name FROM `".LA_TABLE_PREFIX."ask_clients` WHERE client_id = `".LA_TABLE_PREFIX."form_report`.`company_id`) AS `company_name`, (SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = `".LA_TABLE_PREFIX."form_report`.`form_id`) AS `form_name` FROM `".LA_TABLE_PREFIX."form_report` ORDER BY `report_created_on` DESC";
				} else {
					$query_report = "SELECT `".LA_TABLE_PREFIX."form_report`.*, (SELECT company_name FROM `".LA_TABLE_PREFIX."ask_clients` WHERE client_id = `".LA_TABLE_PREFIX."form_report`.`company_id`) AS `company_name`, (SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = `".LA_TABLE_PREFIX."form_report`.`form_id`) AS `form_name` FROM `".LA_TABLE_PREFIX."form_report` WHERE (`company_id` IN (SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = {$_SESSION['la_user_id']}) OR display_type = 'artifact-management') AND (display_type != 'audit-dashboard') AND (display_type != 'template-code') ORDER BY `report_created_on` DESC";
				}
				$result_report = la_do_query($query_report, array(), $dbh);
				while($row_report = la_do_fetch_result($result_report)) {
					$report_type = "";
					$report_name = "";
					$form_list = "";
					$field_data = "";
					$chart_flag = false;
					$form_list_flag = true;
					$entity_flag = true;
					$computation_flag = false;
					$field_data_flag = false;

					if ($row_report["math_function"] == "audit-dashboard") {
						$report_type = "Audit Dashboard";
						$report_name = $report_type." Report";
						$form_list_flag = false;
						$entity_flag = false;
					} else if ($row_report["math_function"] == "artifact-management") {
						$report_type = "Artifact Management";
						$report_name = $report_type." Report";
						$form_list_flag = false;
						$entity_flag = false;
					} else if ($row_report["math_function"] == "template-code") {
						$report_type = "Template Code";
						$report_name = $report_type." Report";
						$form_list_flag = true;
						$entity_flag = false;
						if($row_report["multiple_form_report"] == 0) {
							$form_list = "(#".$row_report["form_id"].")  ".$row_report["form_name"];
						} else {
							$query_multiple_report = "SELECT `".LA_TABLE_PREFIX."form_multiple_report`.`form_id`, (SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = `".LA_TABLE_PREFIX."form_multiple_report`.`form_id`) AS `form_name` FROM `".LA_TABLE_PREFIX."form_multiple_report` WHERE report_id = ?";
							$result_multiple_report = la_do_query($query_multiple_report, array($row_report["report_id"]), $dbh);
							while($row_multiple_report = la_do_fetch_result($result_multiple_report)) {
								$form_list .=  "(#".$row_multiple_report["form_id"].")  ".$row_multiple_report["form_name"]."<br>";
							}
						}
					} else {
						if($row_report["math_function"] == "status-indicator") {
							$report_type = "Status Indicators";
							$chart_flag = true;
						} else if ($row_report["math_function"] == "risk") {
							$report_type = "Risk Score";
							$chart_flag = true;
						} else if ($row_report["math_function"] == "maturity") {
							$report_type = "Maturity";
						} else if ($row_report["math_function"] == "compliance-dashboard") {
							$report_type = "Compliance Dashboard";
						} else if ($row_report["math_function"] == "field-note") {
							$report_type = "Field Note";
						} else if ($row_report["math_function"] == "executive-overview") {
							$report_type = "Executive Overview";
						} else {
							$report_type = "Field Data";
							$chart_flag = true;
							$computation_flag = true;
							$field_data_flag = true;

							$report_id = $row_report['report_id'];
							$form_id = $row_report['form_id'];
							$query_option = "SELECT * FROM `".LA_TABLE_PREFIX."form_report_elements` WHERE `report_id` = {$report_id} AND `form_id` = {$form_id}";
							$result_option = la_do_query($query_option, array(), $dbh);							
							while($rowoption = la_do_fetch_result($result_option)) {
								$element_id = $rowoption['element_id'];
								$element_option_query = "SELECT element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = {$form_id} AND `element_id` = {$element_id}";
								$result_element = la_do_query($element_option_query, array(), $dbh);
								$rowelement = la_do_fetch_result($result_element);
								$field_data .= $rowelement["element_title"]."<br>"; 
							}
						}
						if($row_report["multiple_form_report"] == 0) {
							$report_name = $report_type." Report for ".$row_report["company_name"];
							$form_list = "(#".$row_report["form_id"].")  ".$row_report["form_name"];
						} else {
							$report_name = $report_type." Report for ".$row_report["company_name"];
							$query_multiple_report = "SELECT `".LA_TABLE_PREFIX."form_multiple_report`.`form_id`, (SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = `".LA_TABLE_PREFIX."form_multiple_report`.`form_id`) AS `form_name` FROM `".LA_TABLE_PREFIX."form_multiple_report` WHERE report_id = ?";
							$result_multiple_report = la_do_query($query_multiple_report, array($row_report["report_id"]), $dbh);
							while($row_multiple_report = la_do_fetch_result($result_multiple_report)) {
								$form_list .=  "(#".$row_multiple_report["form_id"].")  ".$row_multiple_report["form_name"]."<br>";
							}
						}
					}

					$start_date = date('m/d/Y', $row_report["start_date"]);
					$completion_date = date('m/d/Y', $row_report["completion_date"]);
					$background_color = "green";

					if(time() > $row_report["completion_date"]){
						$background_color = "red";
					}
				?>
				<li data-theme_id="27" style="display:block;">
					<div class="middle_form_bar <?php echo $background_color; ?>">
						<h3 class="report-header" data-report-id="<?php echo $row_report['report_id']; ?>">
						<?php echo $report_name.' ('.date('m/d/Y H:i a', $row_report['report_created_on']).')'; ?>
						</h3>
						<span class="completion-date">Scheduled Completion Date: <?php echo $completion_date; ?></span>
						<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])){ ?>
							<a style="color: #FFFFFF; float: right; margin: 10px; font-weight: bold;" href="add_reports.php?report_id=<?php echo $row_report['report_id']; ?>">Edit</a>
							<a style="color: #FFFFFF; float: right; margin: 10px; font-weight: bold;" href="javascript:void(0)" class="del-report" data-report-id="<?php echo $row_report['report_id']; ?>">Delete</a>
						<?php } ?>
						<div style="height: 0px; clear: both;"></div>
					</div>
					<div id="report-detail-<?php echo $row_report['report_id']; ?>" class="report-detail-wrapper">
						<div class="field-list"><label class="field-label">Report Type:</label><div class="field-content"><?php echo $report_type; ?></div></div>
						<?php
							if($entity_flag) {
						?>
							<div class="field-list"><label class="field-label">Entity:</label><div class="field-content"><?php echo $row_report["company_name"]; ?></div></div>
						<?php
							}
						?>
						<?php
							if($form_list_flag) {
						?>
							<div class="field-list"><label class="field-label">Forms:</label><div class="field-content"><?php echo $form_list; ?></div></div>
						<?php
							}
						?>
						<?php
							if($chart_flag) {
						?>
							<div class="field-list"><label class="field-label">Chart Type:</label><div class="field-content"><?php echo $display_type[$row_report['display_type']]; ?></div></div>
						<?php
							}
						?>
						<?php
							if($field_data_flag) {
						?>
							<div class="field-list"><label class="field-label">Field Data:</label><div class="field-content"><?php echo $field_data; ?></div></div>
						<?php
							}
						?>
						<?php
							if($computation_flag) {
						?>
							<div class="field-list"><label class="field-label">Computation:</label><div class="field-content"><?php echo $math_functions[$row_report['math_function']]; ?></div></div>
						<?php
							}
						?>
						<div class="field-list"><label class="field-label">Scheduled Start Date:</label><div class="field-content"><?php echo $start_date; ?></div></div>
						<div class="field-list"><label class="field-label">Scheduled Completion Date:</label><div class="field-content"><?php echo $completion_date; ?></div></div>
						<div class="field-list"><label class="field-label">Report Created:</label><div class="field-content"><?php echo date('m/d/Y H:i a', $row_report['report_created_on']); ?></div></div>
						<div class="field-list"><a target="_blank" href="view_report.php?report_id=<?php echo base64_encode($row_report['report_id']); ?>">View Report</a></div>
					</div>
					<div style="height: 0px; clear: both;"></div>
				</li>
				<?php
				}
				?>
			</ul>
		</div>
		<!-- /end of content_body --> 
	</div>
	<!-- /.post --> 
</div>
<div id="dialog-confirm-report-delete" title="Are you sure you want to delete this report?" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
	<input type="hidden" id="report-id" />
	<p> This action cannot be undone.<br/>
		<strong>All report data will be deleted.</strong><br/>
		<br/>
	</p>
</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/ajaxupload/jquery.form.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
EOT;
?>
<?php
	require('includes/footer.php');
?>
<script type="text/javascript">
	$(document).ready(function() {
		$('h3.report-header').click(function(){
			var _report_id = $(this).attr('data-report-id');
			$('div#report-detail-'+_report_id).toggle('slow');
		});
		$('a.del-report').click(function(){
			var _report_id = $(this).attr('data-report-id');
			$('input#report-id').val(_report_id);
			$("#dialog-confirm-report-delete").dialog('open');
			return false;
		});
		$("#dialog-confirm-report-delete").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 550,
			draggable: false,
			resizable: false,
			open: function()
			{
				$("#btn-confirm-edit-ok").blur();
			},
			buttons: [{
				text: 'Yes. Proceed',
				id: 'btn-confirm-edit-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() 
				{
					//disable the delete button while processing
					$("#btn-confirm-edit-ok").prop("disabled",true);
					window.location = '<?php echo $_SERVER['PHP_SELF']; ?>?del_report_id='+$('input#report-id').val();	
					$(this).dialog('close');
				}
			},
			{
				text: 'Cancel',
				id: 'btn-confirm-entry-delete-cancel',
				'class': 'btn_secondary_action',
				click: function() 
				{
					$(this).dialog('close');
				}
			}]
		});
	});
</script>