<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/filter-functions.php');
	require('includes/check-session.php');
	require('includes/users-functions.php');
	$dbh = la_connect_db();
	
	
	if(isset($_POST['submit_cancel'])){
		$form_id = (int) la_sanitize($_POST['form_id']);
		$company_id = (int) la_sanitize($_POST['form_for_selected_company']);
		
		$query = "UPDATE ".LA_TABLE_PREFIX."form_payment_check SET form_counter = :form_counter WHERE form_id = :form_id AND company_id = :company_id";
		$params = array();
		$params[':form_id'] = $form_id;
		$params[':company_id'] = $company_id;
		$params[':form_counter'] = 0;
		la_do_query($query,$params,$dbh);
		
		$query_del = "DELETE FROM ".LA_TABLE_PREFIX."ask_client_forms WHERE `client_id` = ? and `form_id` = ?";
		la_do_query($query_del,array($company_id, $form_id),$dbh);
	}
	
	$la_settings = la_get_settings($dbh);
	$selected_form_id = (int) la_sanitize($_GET['id']);
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);
		
	//get the available custom themes
	if(!empty($_SESSION['la_user_privileges']['priv_administer'])){
		$query = "SELECT theme_id,theme_name FROM ".LA_TABLE_PREFIX."form_themes WHERE theme_built_in=0 and status=1 ORDER BY theme_name ASC";
		$params = array();
	}else{
		$query = "SELECT 
						theme_id,
						theme_name 
					FROM 
						".LA_TABLE_PREFIX."form_themes 
				   WHERE 
					   	(theme_built_in=0 and status=1 and user_id=?) OR
					   	(theme_built_in=0 and status=1 and user_id <> ? and theme_is_private=0)
				ORDER BY 
						theme_name ASC";
		$params = array($_SESSION['la_user_id'],$_SESSION['la_user_id']);
	}	
	
	$sth = la_do_query($query,$params,$dbh);
	$theme_list_array = array();
	while($row = la_do_fetch_result($sth)){
		$theme_list_array[$row['theme_id']] = noHTML($row['theme_name']);
	}
	//get built-in themes
	$query = "SELECT theme_id,theme_name FROM ".LA_TABLE_PREFIX."form_themes WHERE theme_built_in=1 and status=1 ORDER BY theme_name ASC";
		
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	$theme_builtin_list_array = array();
	while($row = la_do_fetch_result($sth)){
		$theme_builtin_list_array[$row['theme_id']] = noHTML($row['theme_name']);
	}
	
	
	/***************************************/
	/*			fetch all company		   */
	/***************************************/
	
	$query_det = "select client_id from ".LA_TABLE_PREFIX."ask_client_forms where `form_id` = :form_id";	
	$params_det = array();
	$params_det[':form_id'] = $selected_form_id;
	
	$resultset = la_do_query($query_det,$params_det,$dbh);
	$company_id = array();
	while($rowdata = la_do_fetch_result($resultset)){
		$company_id[] = (int)$rowdata['client_id'];
	}
	
	if(count($company_id) > 0){
		$query_com = "select client_id, company_name from ".LA_TABLE_PREFIX."ask_clients where client_id in (".implode(",", $company_id).")";
		$sth_com = la_do_query($query_com,array(),$dbh);
		$select_com = '';
		while($row = la_do_fetch_result($sth_com)){
			$select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
		}
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

	$current_nav_tab = 'manage_forms';
	require('includes/header.php'); 
?>

<div id="content" class="full">
  <div class="post manage_forms">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2>Cancellation Manager</h2>
          <p>Cancel subscription to your forms</p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <div class="content_body">
      <?php
				if(count($company_id) > 0){
				?>
      <form action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>?id=<?php echo $selected_form_id; ?>" method="post">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        </div>
        <div>
          <select class="select" id="form_for_selected_company" name="form_for_selected_company" autocomplete="off" style="width: 200px;">
            <?php echo $select_com; ?>
          </select>
          <input type="hidden" name="form_id" value="<?php echo $selected_form_id; ?>" />
        </div>
        <div style="margin-top:10px;">
          <input type="submit" name="submit_cancel" value="Cancel Membership" class="bb_button bb_green" />
        </div>
      </form>
      <?php
				}else{
				?>
      <h1>No company is subscribed to this form!</h1>
      <?php	
				}
				?>
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->
<?php
$footer_data =<<< EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.highlight.js"></script>
<script type="text/javascript" src="js/form_manager.js"></script>
EOT;
	require('includes/footer.php');
	
	
	/**** Helper Functions *******/
	
	function sort_by_today_entry_asc($a, $b) {
    	return ($b['today_entry'] - $a['today_entry']) * -1;
	}
	function sort_by_today_entry_desc($a, $b) {
    	return $b['today_entry'] - $a['today_entry'];
	}
	
	function sort_by_total_entry_asc($a, $b) {
    	return ($b['total_entry'] - $a['total_entry']) * -1;
	}
	function sort_by_total_entry_desc($a, $b) {
    	return $b['total_entry'] - $a['total_entry'];
	}