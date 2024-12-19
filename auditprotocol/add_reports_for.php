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
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);
	
	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/dropui.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<style>
.ui-dialog .ui-dialog-content {
	text-align: center;
}
.dropui-menu li a{
	padding: 2px 0 2px 27px;
	font-size: 115%;
}
.dropui .dropui-tab{
	font-size: 95%;
}
.report-elements{
	float: left;
	padding: 8px;
}
.report-elements label{
	width:140px;
	vertical-align:top;
	float:left;
}
.report-elements select{
	margin-left:10px;
	float:left;
}
#generate-report-form {
	width: 520px;
	padding: 25px 100px;
    margin: 0 auto;
    min-height: 400px;
}
</style>
EOT;
	
	$form_id = 0;
	$form_name = "";
	$company_id = (int) la_sanitize($_GET['company_id']);
	$formcount = count(la_sanitize($_GET['form_id']));
	$identical = true;
	$form_name_arr = array();
	$identicalMsg = "";
	$field_option = "";
	$address_option = "";
	$formElementArr = array();
	$formArray = array();

	if($formcount > 0 && $formcount == 1){
		$form_arr = explode("|==|", urldecode($_GET['form_id'][0]));
		$form_id = (int) $form_arr[0];
		$form_name = $form_arr[1];
	
		$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
		$field_param = array();
		$field_param[':form_id'] = $form_id;
		$query_result = la_do_query($field_query,$field_param,$dbh);
		$no_of_rows = $query_result->fetchColumn();
	
		if($no_of_rows > 0){
			$field_result = la_do_query($field_query,$field_param,$dbh);
			while($field_row = la_do_fetch_result($field_result)){
				$field_option .= '<option value="'.$field_row['element_id']."|=|".$field_row['element_type'].'">'.$field_row['element_title'].'</option>';
			}
		}
	
		$address_query = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id and `element_type` = 'address' AND `element_status` = 1 ORDER BY `element_position` ASC";
		$address_param = array();
		$address_param[':form_id'] = $form_id;
		$address_query_result = la_do_query($address_query,$address_param,$dbh);
		$no_of_rows_add = $address_query_result->fetchColumn();
	
		if($no_of_rows_add > 0){
			$address_result = la_do_query($address_query,$address_param,$dbh);
			while($address_row = la_do_fetch_result($address_result)){
				$address_option .= '<option value="'.$address_row['element_id']."|=|".$address_row['element_type'].'">'.$address_row['element_title'].'</option>';
			}
		}	
	}elseif($formcount > 1){
		
		foreach($_GET['form_id'] as $key => $formVal){
			$form_arr = explode("|==|", urldecode($formVal));
			$form_id = (int) $form_arr[0];
			$form_name = trim($form_arr[1]);
			array_push($form_name_arr, $form_name);
			array_push($formArray, $form_id);
		
			$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
			$field_param = array();
			$field_param[':form_id'] = $form_id;
			$query_result = la_do_query($field_query,$field_param,$dbh);
			$no_of_rows = $query_result->fetchColumn();
			
			if($no_of_rows > 0){
				$field_result = la_do_query($field_query,$field_param,$dbh);
				$formElementArr[$form_id] = array();
				while($field_row = la_do_fetch_result($field_result)){
					array_push($formElementArr[$form_id], array('element_id' => $field_row['element_id'], 'element_type' => $field_row['element_type']));
				}
			}
		}
		
		foreach($formArray as $key => $formId){
			if(!isset($formArray[($key+1)]) && $formArray[($key+1)] === null){
				break;
			}
			if($formElementArr[$formArray[$key]] === $formElementArr[$formArray[($key+1)]]) {
				//echo 'matched';
			}else{
				$identical = false;
				$identicalMsg = "Form No. {$formArray[$key]} is not identical with Form No. {$formArray[($key+1)]}";
				break;
			}
		}
		
		if($identical === true){
			$form_arr = explode("|==|", urldecode($_GET['form_id'][0]));
			$form_id = (int) $form_arr[0];
			$form_name = $form_arr[1];
		
			$field_query = "SELECT element_id, element_title, element_type FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_status` = 1 AND (`element_type` = 'select' OR `element_type` = 'radio' OR `element_type` = 'checkbox' OR `element_type` = 'matrix') ORDER BY `element_position` ASC";
			$field_param = array();
			$field_param[':form_id'] = $form_id;
			$query_result = la_do_query($field_query,$field_param,$dbh);
			$no_of_rows = $query_result->fetchColumn();
		
			if($no_of_rows > 0){
				$field_result = la_do_query($field_query,$field_param,$dbh);
				while($field_row = la_do_fetch_result($field_result)){
					$field_option .= '<option value="'.$field_row['element_id']."|=|".$field_row['element_type'].'">'.$field_row['element_title'].'</option>';
				}
			}
		
			$address_query = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id and `element_type` = 'address' AND `element_status` = 1 ORDER BY `element_position` ASC";
			$address_param = array();
			$address_param[':form_id'] = $form_id;
			$address_query_result = la_do_query($address_query,$address_param,$dbh);
			$no_of_rows_add = $address_query_result->fetchColumn();
		
			if($no_of_rows_add > 0){
				$address_result = la_do_query($address_query,$address_param,$dbh);
				while($address_row = la_do_fetch_result($address_result)){
					$address_option .= '<option value="'.$address_row['element_id']."|=|".$address_row['element_type'].'">'.$address_row['element_title'].'</option>';
				}
			}
		}
		
	}else{
		header("location:manage_reports.php");
		exit();
	}

	//get entities that subcribed the form
	$query_com = "select c.client_id, c.company_name, c.contact_email from ".LA_TABLE_PREFIX."ask_clients c INNER JOIN ".LA_TABLE_PREFIX."form_{$form_id} f ON c.client_id = f.company_id GROUP BY f.company_id ORDER BY c.company_name";
	$sth_com = la_do_query($query_com,array(),$dbh);
	$select_com = '';
	while($row = la_do_fetch_result($sth_com)){
		$select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'('.$row['contact_email'].')</option>';
	}
	
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
      <?php
	if($identical === false){
		echo $identicalMsg;
	}else{
	?>
      <form action="create_reports.php" method="post" id="generate-report-form" class="gradient_blue">
        <div style="display:none;">
          	<input type="hidden" id="post-csrf-token" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
          	<input type="hidden" name="form_id" value="<?php echo $form_id; ?>" />
	        <input type="hidden" name="report_name" value="<?php echo $form_name; ?>" />
	        <input type="hidden" name="multiple_report" value="<?php echo (($formcount > 1) ? 1 : 0); ?>" />
	        <input type="hidden" name="multiple_report_name" value="<?php echo implode(", ", $form_name_arr); ?>" />
	        <input type="hidden" name="multiple_form_id" value="<?php echo implode(",", $formArray); ?>" />
	        <input type="hidden" name="create_report" value="1" />
        </div>
        <div class="report-elements">
        	<label>Select Start Date: </label>
            <span style="margin-left: 10px;">
            <input type="text" value="" maxlength="2" size="2" class="element text" id="start_mm">
            </span>/ <span>
            <input type="text" value="" maxlength="2" size="2" class="element text" id="start_dd">
            </span>/ <span>
            <input type="text" value="" maxlength="4" size="4" class="element text" id="start_yyyy">
            </span>
            <input type="hidden" id="start_date" name="start_date" value="">
            <span style="display: none;"> &nbsp;<img id="start_date_img" class="datepicker" src="images/calendar.gif" alt="Pick a date." style="vertical-align: middle;" /></span>
        </div>
        <div class="report-elements">
        	<label>Select Completion Date: </label>
            <span style="margin-left: 10px;">
            <input type="text" value="" maxlength="2" size="2" class="element text" id="completion_mm">
            </span>/ <span>
            <input type="text" value="" maxlength="2" size="2" class="element text" id="completion_dd">
            </span>/ <span>
            <input type="text" value="" maxlength="4" size="4" class="element text" id="completion_yyyy">
            </span>
            <input type="hidden" id="completion_date" name="completion_date" value="">
            <span style="display: none;"> &nbsp;<img id="completion_date_img" class="datepicker" src="images/calendar.gif" alt="Pick a date." style="vertical-align: middle;" /></span>
        </div>
        <div class="report-elements">
        	<label>Select Entity:</label>
        	<select id="company_id" name="company_id" autocomplete="off" style="width: 350px;">
              <?php echo $select_com; ?>
			</select>
        </div>        
		<div class="report-elements">
		  <label>Report Type:</label>
		  <select id="report-type" name="report-type" style="width:350px;">
		  	<option value="field-data">Field Data</option>
		  	<option value="field-note">Field Notes</option>
		  	<option value="status-indicator">Status Indicators</option>
		  	<option value="risk">Risk Scores</option>
		  	<option value="maturity">Maturity</option>
		  	<option value="compliance">Compliance Dashboard</option>
		  </select>		  
		</div>
		<div class="report-elements">
          <label>Chart Type:</label>
          <select name="display_type" id="display-type" style="width:350px;">
            <option value="line_chart">Line chart</option>
            <option value="area_chart">Area chart</option>
            <option value="column_and_bar_chart">Column and Bar chart</option>
            <option value="pie_chart">Pie chart</option>
            <option value="bubble_chart">Bubble chart</option>
            <!--<option value="dynamic_chart">Dynamic chart</option>-->
            <option value="combinations">Combinations</option>
            <option value="3d_chart">3D chart</option>
			
            <!--<option value="gauges">Gauges</option>-->
            <?php
				  if(!empty($address_option)){
				  ?>
            <!--<option value="heat_map">Heat map</option>-->
            <option value="general_map">General map</option>
            <!--<option value="dynamic_map">Dynamic map</option>-->
            <?php
				  }
				  ?>
          </select>
        </div>
        <div class="report-elements">
          <label>Select Data:</label>
          <select name="field_label[]" id="field-label" size="10" multiple="multiple" style="width:350px;">
            <?php echo $field_option; ?>
          </select>
        </div>
        <div class="report-elements" id="div-address-label" style="display:none;">
          <label>Address Label:</label>
          <select id="address-label" style="width:350px;">
            <option value="">Select</option>
            <?php echo $address_option; ?>
          </select>
        </div>
        <div class="report-elements" id="div-math-functions">
          <label>Computation:</label>
          <select name="math_functions" id="math-functions" style="width:350px;">
            <option value="sum">Sum</option>
            <option value="average">Average</option>
            <option value="median">Median</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </form>
      <?php
	}
	?>
    </div>
    <!-- /end of content_body --> 
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->
<div id="dialog-select-error-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br>
	<br>
	<p id="error-message">Please select a row on the table.</p>
</div>
<?php
	$footer_data =<<< EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.ext.js"></script>
<script type="text/javascript" src="js/jquery.highlight.js"></script>
<script type="text/javascript" src="js/create_report.js"></script>
EOT;
	require('includes/footer.php');	
?>
