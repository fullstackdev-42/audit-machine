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
					form_active=0 or form_active=1
					{$query_order_by_clause}";
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	
	$form_list_array = array();
	$i=0;
	while($row = la_do_fetch_result($sth)){
		
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
		$form_table = LA_TABLE_PREFIX."form_{$row['form_id']}";

		$sub_query = "SELECT count(*) today_entry from `{$form_table}` a 
		LEFT JOIN `{$form_table}` b ON a.id = b.id
		WHERE a.field_name='status' and a.data_value = 1
		and b.field_name = 'date_created' and b.data_value >= date_format(curdate(),'%Y-%m-%d 00:00:00') ";



		$sub_sth = la_do_query($sub_query,array(),$dbh);
		$sub_row = la_do_fetch_result($sub_sth);
		
		
		$form_list_array[$i]['today_entry'] = $sub_row['today_entry'];
		
		//get latest entry timing
		if(!empty($sub_row['today_entry'])){
			$sub_query = "select date_created from `".LA_TABLE_PREFIX."form_{$row['form_id']}` order by id desc limit 1";
			$sub_sth = la_do_query($sub_query,array(),$dbh);
			$sub_row = la_do_fetch_result($sub_sth);
			
			$form_list_array[$i]['latest_entry'] = la_relative_date($sub_row['date_created']);
		}
		
		//get total entries count
		if($form_sort_by == 'total_entries'){
			$sub_query = "SELECT count(*) total_entry FROM `{$form_table}` where field_name='status' and data_value = 1";
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
	// echo "<pre>";
	// print_r($form_list_array);
	// 	die();
	
	
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
</style>
EOT;
		
		
		
	
	$current_nav_tab = 'manage_score_report';
	
	require('includes/header.php'); 
	
?>
		<div id="content" class="full">
			<div class="post manage_forms">
				<div class="content_header">
					<div class="content_header_title">
						<div style="float: left">
							<h2>Report Manager</h2>
							<p>Create, edit and manage your reports</p>
						</div>
						
						<?php if(!empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
						<div style="float: right;margin-right: 5px">
								<a href="javascript:void(0)" id="button_create_form" class="bb_button bb_small bb_green">
									<span class="icon-file3" style="margin-right: 5px"></span>Create Report!
								</a>
						</div>
						<?php } ?>
						<div style="clear: both; height: 1px"></div>
					</div>
				</div>
				
				<?php //la_show_message(); ?>
				<div class="content_body">
				<?php if(!empty($form_list_array)){ ?>
                	<form id="form-reporting" action="" method="post">
					<div id="la_top_pane">
                        <div>
						<?php
                        /***************************************/
                        /*			fetch all company		   */
                        /***************************************/
                        $query_com = "select client_id, company_name from ".LA_TABLE_PREFIX."ask_clients";
                        $sth_com = la_do_query($query_com,array(),$dbh);
                        $select_com = '<option value="0">ALL</option>';
                        while($row = la_do_fetch_result($sth_com)){
                            $select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
                        }
                        ?>
                        <label>Exclusive report for: </label><select class="select" id="company_id" name="company_id" autocomplete="off">
                            <?php echo $select_com; ?>
                        </select>
                        </div>
						<div id="la_filter_pane">
							<div class="dropui dropuiquick dropui-menu dropui-pink dropui-right">
								<a href="javascript:;" class="dropui-tab">
									Sort By &#8674; <?php echo $sortby_title; ?>
								</a>
							
								<div class="dropui-content">
									<ul>
										<li class="sub_separator">Ascending</li>
										<li <?php if($form_sort_by_complete == 'date_created-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_date_created_link" href="manage_forms.php?sortby=date_created-asc">Date Created</a></li>
										<li <?php if($form_sort_by_complete == 'form_title-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_title_link" href="manage_forms.php?sortby=form_title-asc">Form Title</a></li>
										<li <?php if($form_sort_by_complete == 'form_tags-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_tag_link" href="manage_forms.php?sortby=form_tags-asc">Form Tags</a></li>
										<li <?php if($form_sort_by_complete == 'today_entries-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_today_entries_link" href="manage_forms.php?sortby=today_entries-asc">Today's Entries</a></li>
										<li <?php if($form_sort_by_complete == 'total_entries-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_total_entries_link" href="manage_forms.php?sortby=total_entries-asc">Total Entries</a></li>
										<li class="sub_separator">Descending</li>
										<li <?php if($form_sort_by_complete == 'date_created-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_date_created_link" href="manage_forms.php?sortby=date_created-desc">Date Created</a></li>
										<li <?php if($form_sort_by_complete == 'form_title-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_title_link" href="manage_forms.php?sortby=form_title-desc">Form Title</a></li>
										<li <?php if($form_sort_by_complete == 'form_tags-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_tag_link" href="manage_forms.php?sortby=form_tags-desc">Form Tags</a></li>
										<li <?php if($form_sort_by_complete == 'today_entries-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_today_entries_link" href="manage_forms.php?sortby=today_entries-desc">Today's Entries</a></li>
										<li <?php if($form_sort_by_complete == 'total_entries-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_total_entries_link" href="manage_forms.php?sortby=total_entries-desc">Total Entries</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div id="filtered_result_box">
						<div style="float: left">Filtered Results for &#8674; <span class="highlight"></span></div>
						<div id="filtered_result_box_right">
							<ul>
								<li><a href="#" id="la_filter_reset" title="Clear filter"><img src="images/icons/56.png" /></a></li>
								<li id="filtered_result_total">Found 0 forms</li>
							</ul>
						</div>
					</div>
					<div id="filtered_result_none">
						Your filter did not match any of your forms.
					</div>
                    <input type="hidden" name="report" value="111" />
					<ul id="la_form_list" class="la_form_list">
					<?php 
						/*$query_select = "SELECT `form_id` FROM `ap_score_reporting` WHERE `admin_id` = :admin_id";
						$param_select = array();
						$param_select[':admin_id'] = (int) $_SESSION['la_user_id'];
						$result_select = la_do_query($query_select,$param_select,$dbh);
						$row_select = la_do_fetch_result($result_select);
						$form_id_str = $row_select['form_id'];
						if(strpos($form_id_str, ",") !== false){
							$form_id_arr = explode(",", $form_id_str);
						}else{
							$form_id_arr[] = $form_id_str;	
						}
						if(in_array($form_id, $form_id_arr) == true){
							$checked = 'checked="checked"';
						}else{
							$checked = '';	
						}*/
						
						$checked = '';
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
								$form_class[] = 'form_selected';
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
							<div class="middle_form_bar">
								<h3><input type="checkbox" <?php echo $checked; ?> class="report-checkbox" name="form_id[]" id="form_<?php echo $form_id; ?>" value="<?php echo $form_id; ?>">&nbsp;<?php echo $form_name; ?></h3>
                                <div style="height: 0px; clear: both;"></div>
							</div>							
							<div style="height: 0px; clear: both;"></div>
						</li>						
					<?php 
							$row_num++; 
						}//end foreach $form_list_array 
					?>
						
					</ul>
                    </form>
					<div id="result_set_show_more">
						<a href="#">Show More Results...</a>
					</div>
					
					<!-- start pagination -->
					
					<?php echo $pagination_markup; ?>
					
					<!-- end pagination -->
					<?php }else{ ?>
							
							<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
							
							<div id="form_manager_empty">
								<img src="images/icons/arrow_up.png" />
								<h2>Welcome!</h2>
								<h3>You have no forms yet. Go create one by clicking the button above.</h3>
							</div>
							
							<?php } else{ ?>
							<div id="form_manager_empty">
								<h2 style="padding-top: 135px">Welcome!</h2>
								<h3>You currently have no access to any forms.</h3>
							</div>
							<?php } ?>	
					
					<?php } ?>
					
					
					<!-- start dialog boxes -->
					<div id="dialog-enter-tagname" title="Enter a Tag Name" class="buttons" style="display: none"> 
						<form id="dialog-enter-tagname-form" class="dialog-form" style="padding-left: 10px;padding-bottom: 10px">				
							<ul>
								<li>
									<div>
									<input type="text" value="" class="text" name="dialog-enter-tagname-input" id="dialog-enter-tagname-input" />
									<div class="infomessage"><img src="images/icons/70_green.png" style="vertical-align: middle"/> Tag name is optional. Use it when you have many forms, to group them into categories.</div>
									</div> 
								</li>
							</ul>
						</form>
					</div>
					<div id="dialog-confirm-form-delete" title="Are you sure you want to delete this form?" class="buttons" style="display: none">
						<img src="images/navigation/ED1C2A/50x50/Warning.png">
						<p>
							This action cannot be undone.<br/>
							<strong>All data and files collected by <span id="confirm_form_delete_name">this form</span> will be deleted as well.</strong><br/><br/>
							
						</p>
						
					</div>
					<div id="dialog-change-theme" title="Select a Theme" class="buttons" style="display: none"> 
						<form id="dialog-change-theme-form" class="dialog-form" style="padding-left: 10px;padding-bottom: 10px">				
							<ul>
								<li>
									<div>
										<select class="select full" id="dialog-change-theme-input" name="dialog-change-theme-input">
										<?php if(!empty($theme_list_array) || !empty($_SESSION['la_user_privileges']['priv_new_themes'])){ ?>	
											<optgroup label="Your Themes">
												<?php 
													if(!empty($theme_list_array)){
														foreach ($theme_list_array as $theme_id=>$theme_name){
															echo "<option value=\"{$theme_id}\">{$theme_name}</option>";
														}
													}
												?>
												<?php if(!empty($_SESSION['la_user_privileges']['priv_new_themes'])){ ?>
													<option value="new">&#8674; Create New Theme!</option>
												<?php } ?>
											</optgroup>
										<?php } ?>
											<optgroup label="Built-in Themes">
												<option value="0">White</option>
												<?php 
													if(!empty($theme_builtin_list_array)){
														foreach ($theme_builtin_list_array as $theme_id=>$theme_name){
															echo "<option value=\"{$theme_id}\">{$theme_name}</option>";
														}
													}
												?>
											</optgroup>
										</select>
									</div> 
								</li>
							</ul>
						</form>
					</div>
					<div id="dialog-disabled-message" title="Please Enter a Message" class="buttons" style="display: none"> 
						<form class="dialog-form">				
							<ul>
								<li>
									<label for="dialog-disabled-message-input" class="description">Your form will be closed and the message below will be displayed:</label>
									<div>
										<textarea cols="90" rows="8" class="element textarea medium" name="dialog-disabled-message-input" id="dialog-disabled-message-input"></textarea>
									</div>
								</li>
							</ul>
						</form>
					</div>
					<!-- end dialog boxes -->
				
				</div> <!-- /end of content_body -->	
			</div><!-- /.post -->
		</div><!-- /#content -->
 
<?php
	if($highlight_selected_form_id == true){
		$highlight_selected_form_id = $selected_form_id;
	}else{
		$highlight_selected_form_id = 0;
	}
	$footer_data =<<< EOT
<script type="text/javascript">
	var selected_form_id_highlight = {$highlight_selected_form_id};
	$(function(){
		{$jquery_data_code}
		$('a#button_create_form').click(function(){
			var check = 0;
			$('input.report-checkbox').each(function(){
				if($(this).prop('checked') == true){
					check += 1;
				}
			});
			if(check > 0){
				$('form#form-reporting').submit();
			}
		});		
    });
</script>
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
	
?>
