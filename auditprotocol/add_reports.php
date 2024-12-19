<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
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
	require('includes/filter-functions.php');
		
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to create reports.";
		header("Location: restricted.php");
		exit;
	}
	
	function getFormAccessibleEntities($dbh, $form_id){
		$entities = array();
		
		$query = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ?";
		$sth = la_do_query($query, array($form_id), $dbh);
		
		while($row = la_do_fetch_result($sth)){
			array_push($entities, $row['entity_id']);
		}

		return $entities;
	}

	if(!empty($_GET["report_id"])) {
		$report_id = (int) $_GET["report_id"];
		$query_report = "SELECT * FROM ".LA_TABLE_PREFIX."form_report WHERE report_id=?";
		$sth_report = la_do_query($query_report, array($report_id), $dbh);
		$row_report = la_do_fetch_result($sth_report);
		if(empty($row_report)) {
			$_SESSION['LA_ERROR'] = "Unable to edit the report, for invalid report ID.";
			header("location:manage_reports.php");
			exit();
		} else {
			$action_value = "edit_report";
			$form_id = $row_report["form_id"];
			$company_id = $row_report["company_id"];
			$start_date = date("m/d/Y", $row_report["start_date"]);
			$start_mm = explode("/", $start_date)[0];
			$start_dd = explode("/", $start_date)[1];
			$start_yyyy = explode("/", $start_date)[2];
			$completion_date = date("m/d/Y", $row_report["completion_date"]);
			$completion_mm = explode("/", $completion_date)[0];
			$completion_dd = explode("/", $completion_date)[1];
			$completion_yyyy = explode("/", $completion_date)[2];

			//get all companies
			$query_com = "SELECT client_id, company_name from ".LA_TABLE_PREFIX."ask_clients ORDER BY company_name";
			$sth_com = la_do_query($query_com, array(), $dbh);
			$select_com = '';
			while($row = la_do_fetch_result($sth_com)){
				if($row_report["company_id"] == $row["client_id"]) {
					$select_com .= '<option value="'.$row['client_id'].'" selected>'.$row['company_name'].'</option>';
				} else {
					$select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
				}
			}

			//get selected forms
			$selected_form_IDs = array();
			$selected_forms_dom = "";
			if($row_report["multiple_form_report"] == 1) {
				$query_multiple = "SELECT f.`form_id`, f.`form_name` FROM ".LA_TABLE_PREFIX."form_multiple_report AS r LEFT JOIN ".LA_TABLE_PREFIX."forms AS f ON r.form_id = f.form_id WHERE r.`report_id` = ?";
				$sth_multiple = la_do_query($query_multiple, array($report_id), $dbh);
				while ($row_multiple = la_do_fetch_result($sth_multiple)) {
					array_push($selected_form_IDs, $row_multiple["form_id"]);
					$selected_forms_dom.= '<div class="selected-form-item">'.$row_multiple["form_name"].' (# <b>'.$row_multiple["form_id"].')</b></div>';
				}
			} else {
				$query_single_form = "SELECT form_id, form_name FROM ".LA_TABLE_PREFIX."forms WHERE form_id = ?";
				$sth_single_form = la_do_query($query_single_form, array($form_id), $dbh);
				while ($row_single_form = la_do_fetch_result($sth_single_form)) {
					array_push($selected_form_IDs, $row_single_form["form_id"]);
					$selected_forms_dom.= '<div class="selected-form-item">'.$row_single_form["form_name"].' (# <b>'.$row_single_form["form_id"].')</b></div>';
				}
			}
			
			switch ($row_report["math_function"]) {
				case 'status-indicator':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = true;
					$display_data_flag = false;
					$display_computation_flag = false;
					$display_type = $row_report["display_type"];
					$report_type = "status-indicator";
					break;
				case 'risk':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = true;
					$display_data_flag = false;
					$display_computation_flag = false;
					$display_type = $row_report["display_type"];
					$report_type = "risk";
					break;
				case 'field-note':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "field-note";
					break;
				case 'maturity':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "maturity";
					break;
				case 'compliance-dashboard':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "compliance-dashboard";
					break;
				case 'executive-overview':
					$display_entity_flag = true;
					$display_forms_flag = true;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "executive-overview";
					break;
				case 'audit-dashboard':
					$display_entity_flag = false;
					$display_forms_flag = false;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "audit-dashboard";
					break;
				case 'artifact-management':
					$display_entity_flag = false;
					$display_forms_flag = false;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "artifact-management";
					break;
				case 'template-code':
					$display_entity_flag = false;
					$display_forms_flag = true;
					$display_chart_type_flag = false;
					$display_data_flag = false;
					$display_computation_flag = false;
					$report_type = "template-code";
					break;
				default:
					//when report type is field data
					$display_chart_type_flag = true;
					$display_data_flag = true;
					$display_computation_flag = true;
					$display_type = $row_report["display_type"];
					$math_function = $row_report["math_function"];
					$report_type = "field-data";
					$fieldDataArray = array();
					$query_field_data = "SELECT * FROM ".LA_TABLE_PREFIX."form_report_elements WHERE report_id = ? AND form_id = ?";
					$sth_field_data = la_do_query($query_field_data, array($report_id, $form_id), $dbh);
					while ($row_field_data = la_do_fetch_result($sth_field_data)) {
						array_push($fieldDataArray, $row_field_data['element_id']."|=|".$row_field_data['element_type']);
					}

					//get field data
					$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
					$query_result = la_do_query($field_query, array($form_id), $dbh);
					$no_of_rows = $query_result->fetchColumn();
					$field_option = "";
					if($no_of_rows > 0){
						$field_result = la_do_query($field_query, array($form_id), $dbh);
						while($field_row = la_do_fetch_result($field_result)) {
							if(in_array($field_row['element_id']."|=|".$field_row['element_type'], $fieldDataArray)) {
								$field_option .= '<option value="'.$field_row['element_id']."|=|".$field_row['element_type'].'" selected>'.$field_row['element_title'].'</option>';
							} else {
								$field_option .= '<option value="'.$field_row['element_id']."|=|".$field_row['element_type'].'">'.$field_row['element_title'].'</option>';
							}
						}
					}
					break;
			}
		}
	}else{
		$display_entity_flag = false;
		$display_forms_flag = false;
		$display_chart_type_flag = false;
		$display_data_flag = false;
		$display_computation_flag = false;
		$display_type = "";
		//get all companies
		$query_com = "SELECT client_id, company_name from ".LA_TABLE_PREFIX."ask_clients ORDER BY company_name";
		$sth_com = la_do_query($query_com, array(), $dbh);
		$select_com = '';
		while($row = la_do_fetch_result($sth_com)){
			$select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
		}
		$action_value = "create_report";
	}

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/dropui.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<style>
.ui-dialog .ui-dialog-content {
	text-align: center;
}
.dropui-menu li a {
	padding: 2px 0 2px 27px;
	font-size: 115%;
}
.dropui .dropui-tab {
	font-size: 95%;
}
.datepicker {
	cursor: pointer;
}
.report-elements {
	float: left;
	padding: 8px;
}
.report-elements label {
	width:140px;
	vertical-align:top;
	float:left;
}
.report-elements select {
	margin-left:10px;
	float:left;
}
.selected-form-item {
	margin: 10px;
	padding: 7px;
	border: solid 1px #0085CC;
}
#div-selected-forms {
	float: left;
	width: 348px;
	margin-left: 10px;
	min-height: 20px;
	background: #FFF;
	border: solid 1px #767676;
	cursor: pointer;
	max-height: 250px;
	overflow-y: scroll;
}
#generate-report-form {
	width: 520px;
	padding: 25px 100px;
    margin: 0 auto;
    min-height: 300px;
}
</style>
EOT;

	$current_nav_tab = 'manage_reports';
	require('includes/header.php');
?>
<div id="content" class="full">
	<div class="post manage_forms">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left">
				<h2>Report Manager</h2>
				<p>Analyze data collected and manage your reports</p>
			</div>
			<?php if(!empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
				<div style="float: right;">
					<button class="bb_button bb_small bb_green" id="create-report">Create Report</button>
				</div>
			<?php } ?>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<div class="content_body">
			<form id="generate-report-form" class="gradient_blue">
				<div style="display:none;">
					<input type="hidden" id="post-csrf-token" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
					<input id="form_array" type="hidden" name="form_array" value="<?php echo implode('|', $selected_form_IDs) ?>" />
					<input type="hidden" name="action" value="<?php echo $action_value; ?>" />
					<input type="hidden" name="report_id" value="<?php echo $report_id; ?>" />
				</div>
				<div class="report-elements">
					<label>Start Date: </label>
					<span style="margin-left: 10px;">
					<input type="text" value="<?php echo $start_mm; ?>" maxlength="2" size="2" class="element text" id="start_mm">
					</span>/ <span>
					<input type="text" value="<?php echo $start_dd; ?>" maxlength="2" size="2" class="element text" id="start_dd">
					</span>/ <span>
					<input type="text" value="<?php echo $start_yyyy; ?>" maxlength="4" size="4" class="element text" id="start_yyyy">
					</span>
					<input type="hidden" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
					<span style="display: none;"> &nbsp;<img id="start_date_img" class="datepicker" src="images/calendar.gif" alt="Pick a date." style="vertical-align: middle;" /></span>
				</div>
				<div class="report-elements">
					<label>Completion Date: </label>
					<span style="margin-left: 10px;">
					<input type="text" value="<?php echo $completion_mm; ?>" maxlength="2" size="2" class="element text" id="completion_mm">
					</span>/ <span>
					<input type="text" value="<?php echo $completion_dd; ?>" maxlength="2" size="2" class="element text" id="completion_dd">
					</span>/ <span>
					<input type="text" value="<?php echo $completion_yyyy; ?>" maxlength="4" size="4" class="element text" id="completion_yyyy">
					</span>
					<input type="hidden" id="completion_date" name="completion_date" value="<?php echo $completion_date; ?>">
					<span style="display: none;"> &nbsp;<img id="completion_date_img" class="datepicker" src="images/calendar.gif" alt="Pick a date." style="vertical-align: middle;" /></span>
				</div>
				<div class="report-elements">
					<label>Report Type:</label>
					<select id="report-type" name="report-type" style="width:350px;">
						<option value="audit-dashboard" <?php if($report_type == "audit-dashboard"){echo "selected";} ?>>Audit Dashboard</option>
						<option value="artifact-management" <?php if($report_type == "artifact-management"){echo "selected";} ?>>Artifact Management</option>
						<option value="compliance-dashboard" <?php if($report_type == "compliance-dashboard"){echo "selected";} ?>>Compliance Dashboard</option>
						<option value="executive-overview" <?php if($report_type == "executive-overview"){echo "selected";} ?>>Executive Overview</option>
						<option value="field-data" <?php if($report_type == "field-data"){echo "selected";} ?>>Field Data</option>
						<option value="field-note" <?php if($report_type == "field-note"){echo "selected";} ?>>Field Notes</option>
						<option value="maturity" <?php if($report_type == "maturity"){echo "selected";} ?>>Maturity</option>
						<option value="risk" <?php if($report_type == "risk"){echo "selected";} ?>>Risk Scores</option>
						<option value="status-indicator" <?php if($report_type == "status-indicator"){echo "selected";} ?>>Status Indicators</option>
						<option value="template-code" <?php if($report_type == "template-code"){echo "selected";} ?>>Template Code Report</option>
					</select>
				</div>
				<div class="report-elements" style="<?php if(!$display_entity_flag){echo 'display: none;';} ?>">
					<label>Entity:</label>
					<select id="company_id" name="company_id" autocomplete="off" style="width: 350px;">
						<?php echo $select_com; ?>
					</select>
				</div>
				<div class="report-elements" style="<?php if(!$display_forms_flag){echo 'display: none;';} ?>">
					<label>Forms:</label>
					<div id="div-selected-forms">
						<?php
						if(count($selected_form_IDs) == 1) {
							echo '<h5 style="text-align: center;">1 form has been selected.</h5>';
						} else if(count($selected_form_IDs) > 1) {
							echo '<h5 style="text-align: center;">'.count($selected_form_IDs).' forms have been selected.</h5>';
						}
						echo $selected_forms_dom;
						?>
					</div>
				</div>
				<div class="report-elements" style="<?php if(!$display_chart_type_flag){echo 'display: none;';} ?>">
					<label>Chart Type:</label>
					<select name="display_type" id="display-type" style="width:350px;">
						<option value="line_chart" <?php if($display_type == "line_chart"){echo "selected";} ?>>Line chart</option>
						<option value="area_chart" <?php if($display_type == "area_chart"){echo "selected";} ?>>Area chart</option>
						<option value="column_and_bar_chart" <?php if($display_type == "column_and_bar_chart"){echo "selected";} ?>>Column and Bar chart</option>
						<option value="pie_chart" <?php if($display_type == "pie_chart"){echo "selected";} ?>>Pie chart</option>
						<option value="bubble_chart" <?php if($display_type == "bubble_chart"){echo "selected";} ?>>Bubble chart</option>
						<option value="combinations" <?php if($display_type == "combinations"){echo "selected";} ?>>Combinations</option>
						<?php
							if($report_type == "status-indicator") {
							?>
								<option value="3d_chart" <?php if($display_type == "3d_chart"){echo "selected";} ?>>3D chart</option>
								<option value="sunburst_chart" <?php if($display_type == "sunburst_chart"){echo "selected";} ?>>Sunburst chart</option>
								<option value="polar_chart" <?php if($display_type == "polar_chart"){echo "selected";} ?>>Polar chart</option>
							<?php
							} else if($report_type == "risk") {
							?>
								<option value="sunburst_chart" <?php if($display_type == "sunburst_chart"){echo "selected";} ?>>Sunburst chart</option>
								<option value="polar_chart" <?php if($display_type == "polar_chart"){echo "selected";} ?>>Polar chart</option>
							<?php
							} else {
							?>
								<option value="3d_chart" <?php if($display_type == "3d_chart"){echo "selected";} ?>>3D chart</option>
							<?php
							}
						?>
					</select>
				</div>
				<div class="report-elements" style="<?php if(!$display_data_flag){echo 'display: none;';} ?>">
					<label>Form Data:</label>
					<select name="field_label[]" id="field-label" size="10" multiple="multiple" style="width:350px;">
						<?php echo $field_option; ?>
					</select>
				</div>
				<div class="report-elements" style="<?php if(!$display_computation_flag){echo 'display: none;';} ?>">
					<label>Computation:</label>
					<select name="math_functions" id="math-functions" style="width:350px;">
						<option value="sum" <?php if($math_function == "sum"){echo "selected";} ?>>Sum</option>
						<option value="average" <?php if($math_function == "average"){echo "selected";} ?>>Average</option>
						<option value="median" <?php if($math_function == "median"){echo "selected";} ?>>Median</option>
					</select>
				</div>
				<div style="clear:both"></div>
			</form>
		</div>
	</div>
</div>
<div id="dialog-select-error-message" title="Error!" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p id="error-message">Please select a row on the table.</p>
</div>
<div id="dialog-select-forms" title="Please select forms." style="display: none;">
	<table id="form-table" class="hover stripe cell-border nowrap" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th>Form #</th>
				<th>Form Name</th>
			</tr>
		</thead>
		<tbody id="tbody-form-table">
		</tbody>
	</table>
</div>
<?php
	$footer_data =<<< EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.ext.js"></script>
<script type="text/javascript" src="js/jquery.highlight.js"></script>
<script type="text/javascript" src="js/create_report.js"></script>
EOT;
	require('includes/footer.php');	
?>