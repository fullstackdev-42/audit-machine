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
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);

	if(isset($_GET['del_report'])){
		$query_delete = "DELETE FROM `ap_score_reporting` WHERE `report_id` = :report_id";
		$param = array();
		$param[':report_id'] = (int) $_GET['del_report'];
		la_do_query($query_delete,$param,$dbh);
		header("location:show_all_reports.php");
		exit();
	}

	$selected_form_id = (int) $_GET['id'];
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);

	if(!empty($_GET['hl'])){
		$highlight_selected_form_id = true;
	}else{
		$highlight_selected_form_id = false;
	}

	if(isset($_POST['report']) && $_POST['report'] == '111'){
		$query_insert = "INSERT INTO `ap_score_reporting` (`report_id`, `company_id`, `form_id`, `report_created_on`) VALUES (NULL, :company_id, :form_id, :report_created_on)";
		$param = array();
		$param[':company_id'] = (int) $_POST['company_id'];
		$param[':form_id'] = implode(",", $_POST['form_id']);
		$param[':report_created_on'] = time();
		la_do_query($query_insert,$param,$dbh);
	}

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
</style>
EOT;

	$current_nav_tab = 'show_all_report';

	require('includes/header.php');

?>

<div id="content" class="full">
  <div class="post manage_forms">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2>Report List</h2>
          <p>Create, edit and manage your reports</p>
        </div>
        <?php if(!empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
        <div style="float: right;margin-right: 5px"> <a href="manage_score_reports.php" id="button_create_form" class="bb_button bb_small bb_green"> <span class="icon-file3" style="margin-right: 5px"></span>Create New Report! </a> </div>
        <?php } ?>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php //la_show_message(); ?>
    <div class="content_body">
      <ul id="la_form_list" class="la_form_list">
        <?php
					$query_select = "SELECT `ap_score_reporting`.*, IFNULL(`ap_ask_clients`.`company_name`, 'ALL') AS `company_name` FROM `ap_score_reporting` LEFT JOIN `ap_ask_clients` ON `ap_score_reporting`.`company_id` = `ap_ask_clients`.`client_id` ORDER BY `ap_ask_clients`.`company_name`, `ap_score_reporting`.`report_created_on` DESC";
					$param_select = array();
					$result_select = la_do_query($query_select,$param_select,$dbh);
					while($row_select = la_do_fetch_result($result_select)){
		?>
        <li data-theme_id="27" style="display:block;">
          <div class="middle_form_bar">
            <h3 class="report-header" data-report-id="<?php echo $row_select['report_id']; ?>">Report for <?php echo $row_select['company_name']; ?> ( <?php echo date('m/d/Y H:i a', $row_select['report_created_on']); ?> )</h3>
            <a style="color: rgb(255, 255, 255); float: right; margin: 8px; font-weight: bold;" href="javascript:void(0)" class="del-report" data-report-id="<?php echo $row_select['report_id']; ?>">Delete</a>
            <div style="height: 0px; clear: both;"></div>
          </div>
          <div id="report-detail-<?php echo $row_select['report_id']; ?>" style="color:#000; margin:10px 0 0 20px; display:none;">
            Reports between forms :
            <div style="margin:10px 0 10px 10px;">
              <ul>
              <?php
			  $query_form = "SELECT `form_name` FROM `ap_forms` WHERE `form_id` IN ({$row_select['form_id']})";
			  $param_form = array();
			  $result_form = la_do_query($query_form,$param_form,$dbh);
			  while($row_form = la_do_fetch_result($result_form)){
			  ?>
                <li>
                	<?php echo $row_form['form_name']; ?>
                </li>
              <?php
			  }
			  ?>
              </ul>
            </div>
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
<!-- /#content -->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-migrate.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('h3.report-header').click(function(){
		var _report_id = $(this).attr('data-report-id');
		$('div#report-detail-'+_report_id).toggle('slow');
	});
	$('a.del-report').click(function(){
		var _report_id = $(this).attr('data-report-id');
		var _confirm = confirm("Are you sure you want to delete this report?");
		if(_confirm){
			window.location = 'show_all_reports.php?del_report='+_report_id;
		}
	});
});
</script>
<?php require('includes/footer.php'); ?>
