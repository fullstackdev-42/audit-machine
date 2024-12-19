<?php
/********************************************************************************
 IT Audit Machine
  
 Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/filter-functions.php');
	require('includes/check-client-session-ask.php');
	require('includes/users-functions.php');
	require('includes/entry-functions.php');
	require('includes/report-helper-function.php');

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	require('portal-header.php');

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
			font-size: 125%!important;
		}
		span.completion-date{
			color: black;
			margin-right: 10px;
		}
		.report-detail-wrapper {
			margin: 10px 20px;
			color: #000;
			display: none;
		}
		.field-list {
			padding: 5px 0px;
		}
		.field-label {
			float:left;
			width: 250px;
		}
		.field-content {
			display: inline-block;
		}
	</style>
	<div class="content_body">
		<ul id="la_form_list" class="la_form_list">
			<?php
			$company_id = $_SESSION["la_client_entity_id"];
			$query_report = "SELECT `".LA_TABLE_PREFIX."form_report`.*, (SELECT company_name FROM `".LA_TABLE_PREFIX."ask_clients` WHERE client_id = `".LA_TABLE_PREFIX."form_report`.`company_id`) AS `company_name`, (SELECT form_name FROM `".LA_TABLE_PREFIX."forms` WHERE form_id = `".LA_TABLE_PREFIX."form_report`.`form_id`) AS `form_name` FROM `".LA_TABLE_PREFIX."form_report` WHERE (company_id =? OR display_type = 'artifact-management') AND (display_type != 'audit-dashboard') AND (display_type != 'template-code') ORDER BY `report_created_on` DESC";
			$result_report = la_do_query($query_report, array($company_id), $dbh);
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

				if ($row_report["math_function"] == "artifact-management") {
					$report_type = "Artifact Management";
					$report_name = $report_type." Report";
					$form_list_flag = false;
					$entity_flag = false;
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
						$report_name = $report_type." Multiple Report for ".$row_report["company_name"];
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
	<?php
		require('portal-footer.php');
	?>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('h3.report-header').click(function(){
		var _report_id = $(this).attr('data-report-id');
		$('div#report-detail-'+_report_id).toggle('slow');
	});
});
</script>