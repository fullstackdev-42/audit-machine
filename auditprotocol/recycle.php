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
	require('includes/check-session.php');
	require('includes/users-functions.php');
	//echo $_SESSION['la_client_client_id']; exit;
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);
	$selected_form_id = (int) $_GET['id'];
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);
	//$_SESSION['la_client_client_id'] = time();

	if(!empty($_GET['hl'])){
		$highlight_selected_form_id = true;
	}else{
		$highlight_selected_form_id = false;
	}
	
	//determine the sorting order
	$form_sort_by_complete = 'date_created-desc'; //the default sort order
	
	if(!empty($_GET['sortby'])){
		$form_sort_by_complete = strtolower(trim($_GET['sortby'])); //the user select a new sort order
		
		//save the sort order into ap_form_sorts table
		$query = "delete from ".LA_TABLE_PREFIX."form_sorts where user_id=?";
		$params = array($_SESSION['la_user_id']);
		la_do_query($query,$params,$dbh);
		$query = "insert into ".LA_TABLE_PREFIX."form_sorts(user_id,sort_by) values(?,?)";
		$params = array($_SESSION['la_user_id'],$form_sort_by_complete);
		la_do_query($query,$params,$dbh);
		
	}else{ //load the previous saved sort order
		$query = "select sort_by from ".LA_TABLE_PREFIX."form_sorts where user_id=?";
		$params = array($_SESSION['la_user_id']);
	
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		if(!empty($row)){
			$form_sort_by_complete = $row['sort_by'];
		}
	} 
	
	$exploded = array();
	$exploded = explode('-', $form_sort_by_complete);
	$form_sort_by 	 = $exploded[0];
	$form_sort_order = $exploded[1];
			
	if(empty($form_sort_order)){
		$form_sort_order = 'asc';
	}
	//lets hardcode it to make sure, to prevent SQL injection
	if($form_sort_order == 'desc'){
		$form_sort_order = 'desc';
	}else{
		$form_sort_order = 'asc';
	}
	$query_order_by_clause = '';
	
	if($form_sort_by == 'form_title'){
		$query_order_by_clause = " ORDER BY form_name {$form_sort_order}";
		$sortby_title = 'Form Title';
	}else if($form_sort_by == 'form_tags'){
		$query_order_by_clause = " ORDER BY form_tags {$form_sort_order}";
		$sortby_title = 'Form Tags';
	}else if($form_sort_by == 'today_entries'){
		$sortby_title = "Today's Entries";
	}else if($form_sort_by == 'total_entries'){
		$sortby_title = "Total Entries";
	}else{ //the default date created sort
		$query_order_by_clause = " ORDER BY form_id {$form_sort_order}";
		$sortby_title = "Date Created";
	}
	
	//the number of forms being displayed on each page
	$rows_per_page = $la_settings['form_manager_max_rows'];  
	
	//get the list of the form, put them into array
	$query = "SELECT 
					form_name,
					form_id,
					form_tags,
					form_active,
					form_disabled_message,
					form_theme_id
				FROM
					".LA_TABLE_PREFIX."forms
				WHERE
					form_active=2
					{$query_order_by_clause}";
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	
	$form_list_array = array();
	$i=0;
	while($row = la_do_fetch_result($sth)){
		
		$table_exists = "SELECT count(*) AS counter FROM information_schema.tables WHERE table_schema = '".LA_DB_NAME."' AND table_name = '".LA_TABLE_PREFIX."form_{$row['form_id']}'";
		$result_table_exists = la_do_query($table_exists,array(),$dbh);
		$row_table_exists = la_do_fetch_result($result_table_exists);
		
		if($row_table_exists['counter'] > 0){
		
			//check user permission to this form
			if(empty($_SESSION['la_user_privileges']['priv_administer']) && empty($user_permissions[$row['form_id']])){
				continue;
			}
			
			$form_list_array[$i]['form_id']   	  = $row['form_id'];
			$row['form_name'] = la_trim_max_length($row['form_name'],75);
			if(!empty($row['form_name'])){		
				$form_list_array[$i]['form_name'] = $row['form_name'];
			}else{
				$form_list_array[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
			}	
			
			$form_list_array[$i]['form_active']   			= $row['form_active'];
			$form_list_array[$i]['form_disabled_message']   = $row['form_disabled_message'];
			$form_list_array[$i]['form_theme_id'] 			= $row['form_theme_id'];
			
			$form_disabled_message = json_encode($row['form_disabled_message']);
			$jquery_data_code .= "\$('#liform_{$row['form_id']}').data('form_disabled_message',{$form_disabled_message});\n";
			//get todays entries count
			//WE NEED TO ADD GROUP BY AT SERVER
			$sub_query = "SELECT SUM(today_entry) AS today_entry FROM (SELECT count(*) today_entry, `data_value` FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` WHERE `field_name` = 'date_created' GROUP BY `company_id` HAVING `data_value` >= '".date('Y-m-d')."') AS t";
			//$sub_query = "select count(*) today_entry from `".LA_TABLE_PREFIX."form_{$row['form_id']}` where date_created >= date_format(curdate(),'%Y-%m-%d 00:00:00')";
			//$sub_query = "select count(*) today_entry from `".LA_TABLE_PREFIX."form_{$row['form_id']}`";
			$sub_sth = la_do_query($sub_query,array(),$dbh);
			$sub_row = la_do_fetch_result($sub_sth);
			
			$form_list_array[$i]['today_entry'] = $sub_row['today_entry'];
			
			//get latest entry timing
			/*if(!empty($sub_row['today_entry'])){
				$sub_query = "select date_created from `".LA_TABLE_PREFIX."form_{$row['form_id']}` order by id desc limit 1";
				$sub_sth = la_do_query($sub_query,array(),$dbh);
				$sub_row = la_do_fetch_result($sub_sth);
				
				$form_list_array[$i]['latest_entry'] = la_relative_date($sub_row['date_created']);
			}*/
			
			//get total entries count
			if($form_sort_by == 'total_entries'){
				$sub_query = "SELECT count(*) total_entry FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` where field_name='status' and data_value = 1";
				$sub_sth = la_do_query($sub_query,array(),$dbh);
				$sub_row = la_do_fetch_result($sub_sth);
				
				$form_list_array[$i]['total_entry'] = $sub_row['total_entry'];
			}
			
			
			//get form tags and split them into array
			if(!empty($row['form_tags'])){
				$form_tags_array = explode(',',$row['form_tags']);
				array_walk($form_tags_array, 'la_trim_value');
				$form_list_array[$i]['form_tags'] = $form_tags_array;
			}
			
			$i++;
			
		}
	}
	
	
	if($form_sort_by == 'today_entries'){
		if($form_sort_order == 'asc'){
			usort($form_list_array, 'sort_by_today_entry_asc');
		}else{
			usort($form_list_array, 'sort_by_today_entry_desc');
		}
	}
	
	if($form_sort_by == 'total_entries'){
		if($form_sort_order == 'asc'){
			usort($form_list_array, 'sort_by_total_entry_asc');
		}else{
			usort($form_list_array, 'sort_by_total_entry_desc');
		}
	}
	
	if(empty($selected_form_id)){ //if there is no preference for which form being displayed, display the first form
		$selected_form_id = $form_list_array[0]['form_id'];
	}
	$selected_page_number = 1;
	
	//build pagination markup
	$total_rows = count($form_list_array);
	$total_page = ceil($total_rows / $rows_per_page);
	
	if($total_page > 1){
		
		$start_form_index = 0;
		$pagination_markup = '<ul id="la_pagination" class="pages green small">'."\n";
		
		for($i=1;$i<=$total_page;$i++){
			
			//attach the data code into each pagination button
			$end_form_index = $start_form_index + $rows_per_page;
			$liform_ids_array = array();
			
			for ($j=$start_form_index;$j<$end_form_index;$j++) {
				if(!empty($form_list_array[$j]['form_id'])){
					$liform_ids_array[] = '#liform_'.$form_list_array[$j]['form_id'];
					
					//put the page number into the array
					$form_list_array[$j]['page_number'] = $i;
					
					//we need to determine on which page the selected_form_id being displayed
					if($selected_form_id == $form_list_array[$j]['form_id']){
						$selected_page_number = $i;
					}
				}
			}
			
			$liform_ids_joined = implode(',',$liform_ids_array);
			$start_form_index = $end_form_index;
			
			$jquery_data_code .= "\$('#pagebtn_{$i}').data('liform_list','{$liform_ids_joined}');\n";
			
			
			if($i == $selected_page_number){
				if($selected_page_number > 1){
					$pagination_markup = str_replace('current_page','',$pagination_markup);
				}
				
				$pagination_markup .= '<li id="pagebtn_'.$i.'" class="page current_page">'.$i.'</li>'."\n";
			}else{
				$pagination_markup .= '<li id="pagebtn_'.$i.'" class="page">'.$i.'</li>'."\n";
			}
			
		}
		
		$pagination_markup .= '</ul>';
	}else{
		//if there is only 1 page, set the page_number property for each form to 1
		foreach ($form_list_array as $key=>$value){
			$form_list_array[$key]['page_number'] = 1;
		}
	}
	//get the available tags
	$query = "select form_tags from ".LA_TABLE_PREFIX."forms where form_tags is not null and form_tags <> ''";
	$params = array();
	
	$sth = la_do_query($query,$params,$dbh);
	$raw_tags = array();
	while($row = la_do_fetch_result($sth)){
		$raw_tags = array_merge(explode(',',$row['form_tags']),$raw_tags);
	}
	$all_tagnames = array_unique($raw_tags);
	sort($all_tagnames);
	
	$jquery_data_code .= "\$('#dialog-enter-tagname-input').data('available_tags',".json_encode($all_tagnames).");\n";
	
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
		$theme_list_array[$row['theme_id']] = htmlspecialchars($row['theme_name']);
	}
	//get built-in themes
	$query = "SELECT theme_id,theme_name FROM ".LA_TABLE_PREFIX."form_themes WHERE theme_built_in=1 and status=1 ORDER BY theme_name ASC";
		
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	$theme_builtin_list_array = array();
	while($row = la_do_fetch_result($sth)){
		$theme_builtin_list_array[$row['theme_id']] = htmlspecialchars($row['theme_name']);
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
.la_link_delete{
	display:none;
}
</style>
EOT;
	$current_nav_tab = 'manage_forms';
	require('includes/header.php'); 
?>
<script type="text/javascript">
function get_confirmation(id){ 
	customAlert(id);
}
</script>
<div id="content" class="full">
  <div class="post manage_forms">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2>Deleted Forms</h2>
          <p>Manage your deleted forms</p>
        </div>
        <?php if(!empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
        <?php } ?>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <?php if(!empty($form_list_array)){ ?>
      <div id="filtered_result_box">
        <div style="float: left">Filtered Results for &#8674; <span class="highlight"></span></div>
        <div id="filtered_result_box_right">
          <ul>
            <li><a href="#" id="la_filter_reset" title="Clear filter"><img src="images/icons/56.png" /></a></li>
            <li id="filtered_result_total">Found 0 forms</li>
          </ul>
        </div>
      </div>
      <div id="filtered_result_none"> Your filter did not match any of your forms. </div>
      <ul id="la_form_list" class="la_form_list">
        <?php 
						
						$row_num = 1;
						
						foreach ($form_list_array as $form_data){
							$form_name   	 = htmlspecialchars($form_data['form_name']);
							$form_id   	 	 = $form_data['form_id'];
							$today_entry 	 = $form_data['today_entry'];
							$total_entry 	 = $form_data['total_entry'];
							$latest_entry 	 = $form_data['latest_entry'];
							$theme_id		 = (int) $form_data['form_theme_id'];
							
							if(!empty($form_data['form_tags'])){
								$form_tags_array = array_reverse($form_data['form_tags']);
							}else{
								$form_tags_array = array();
							}
							
							
							$form_class = array();
							$form_class_tag = '';
							
							if($form_id == $selected_form_id){
								$form_class[] = '';
							}
							
							if(empty($form_data['form_active'])){
								$form_class[] = 'form_inactive';
							}
							
							if($selected_page_number == $form_data['page_number']){
								$form_class[] = 'form_visible';
							}
							
							$form_class_joined = implode(' ',$form_class);
							$form_class_tag	   = 'class="'.$form_class_joined.'"';
							
							
					?>
        <li data-theme_id="<?php echo $theme_id; ?>" id="liform_<?php echo $form_id; ?>" <?php echo $form_class_tag; ?>>
          <?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_form'])){ ?>
          <div class="form_option la_link_delete la_link_toptabs"> <a href="#"><span><img src="images/un-delete.gif"></span>Undelete</a> </div>
          <?php } ?>
          <div style="height: 0px; clear: both;"></div>
          <div class="middle_form_bar">
            <h3><span class="icon-file2"></span><?php echo $form_name; ?></h3>
            <div class="form_meta">
              <?php if(!empty($total_entry)){ ?>
              <div class="form_stat form_stat_total" title="<?php echo $today_entry." entries today. Latest entry ".$latest_entry."."; ?>">
                <div class="form_stat_count"><?php echo $total_entry; ?></div>
                <div class="form_stat_msg">total</div>
              </div>
              <?php }else if(!empty($today_entry)){ ?>
              <div class="form_stat" title="<?php echo $today_entry." entries today. Latest entry ".$latest_entry."."; ?>">
                <div class="form_stat_count"><?php echo $today_entry; ?></div>
                <div class="form_stat_msg">today</div>
              </div>
              <?php } ?>
            </div>
            <div style="height: 0px; clear: both;"></div>
          </div>
          <div style="height: 0px; clear: both;"></div>
        </li>
        <?php 
							$row_num++; 
						}//end foreach $form_list_array 
					?>
      </ul>
      <div id="result_set_show_more"> <a href="#">Show More Results...</a> </div>
      
      <!-- start pagination --> 
      
      <?php echo $pagination_markup; ?> 
      
      <!-- end pagination -->
      <?php }else{ ?>
      <?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
      <div id="form_manager_empty">
        <h2>Welcome!</h2>
        <h3>You have no deleted forms</h3>
      </div>
      <?php } else{ ?>
      <div id="form_manager_empty">
        <h2 style="padding-top: 135px">Welcome!</h2>
        <h3>You currently have no access to any forms.</h3>
      </div>
      <?php } ?>
      <?php } ?>
      
      <!-- start dialog boxes -->
      <div id="dialog-confirm-form-delete" title="Are you sure you want to undelete this form?" class="buttons" style="display: none">
      	<img src="images/navigation/ED1C2A/50x50/Warning.png">
        <p> <strong>Do you want to undelete this form?</strong><br/>
          <br/>
        </p>
      </div>
      <!-- end dialog boxes --> 
      
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->
<?php
	$footer_data =<<< EOT
<script type="text/javascript">
	$(function(){
		{$jquery_data_code}		
    });
</script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.highlight.js"></script>
<script type="text/javascript" src="js/recycle.js"></script>
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
	
?>
<script>
$(document).ready(function(){
	var form_id = 0;
	$(document).on('click', 'a.custom-alert', function(){
		form_id = $(this).attr('data-id');
		$("#dialog-confirm-edit").dialog('open');
		return false;
	});
	
	$("#dialog-confirm-edit").dialog({
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
				window.location.href = "edit_form.php?id="+form_id+"&action=trunc";
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
