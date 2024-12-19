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
	
	function searchFormElement($dbh, $search_data, $folder_id){
		$user_filtered_forms = array();
		$query = "select `id`, `form_id`, `element_title`, `element_type`, `element_position`, `element_page_number` from `".LA_TABLE_PREFIX."form_elements` WHERE `element_title` LIKE CONCAT('%', ?, '%')";
				
		$sth = $dbh->prepare($query);
		
		try{
			$sth->execute(array($search_data));
		}catch(PDOException $e){
			echo $e->getMessage();
			exit;
		}
		
		$user_filtered_forms = array();
		
		while($user_filtered_forms_temp = la_do_fetch_result($sth)){		
			if(array_key_exists($user_filtered_forms_temp['form_id'], $user_filtered_forms)){
				array_push($user_filtered_forms[$user_filtered_forms_temp['form_id']], array('element_id_auto' => $user_filtered_forms_temp['id'], 'element_title' => $user_filtered_forms_temp['element_title'], 'element_type' => $user_filtered_forms_temp['element_type'], 'element_position' => $user_filtered_forms_temp['element_position'], 'element_page_number' => $user_filtered_forms_temp['element_page_number']));
			}else{
				$user_filtered_forms[$user_filtered_forms_temp['form_id']] = array();
				array_push($user_filtered_forms[$user_filtered_forms_temp['form_id']], array('element_id_auto' => $user_filtered_forms_temp['id'], 'element_title' => $user_filtered_forms_temp['element_title'], 'element_type' => $user_filtered_forms_temp['element_type'], 'element_position' => $user_filtered_forms_temp['element_position'], 'element_page_number' => $user_filtered_forms_temp['element_page_number']));
			}
		}
		
		return $user_filtered_forms;
	}

	function replaceForms($dbh, $entity_id, $search_data, $replace_data) {
		$query = "select * from `".LA_TABLE_PREFIX."forms`";
		$sth = la_do_query($query,array(),$dbh);
		$result = array();
		while($row = la_do_fetch_result($sth)){
			if (la_mysql_table_exist(LA_TABLE_PREFIX."form_{$row['form_id']}", $dbh)) {
				$search_query = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` WHERE `company_id` = ? and `data_value` LIKE CONCAT('%', ?, '%')";
				$search_sth = la_do_query($search_query, array($entity_id, $search_data), $dbh);
				$search_row = la_do_fetch_result($search_sth);
				if ($search_row) {
					$update_query = "UPDATE `".LA_TABLE_PREFIX."form_{$row['form_id']}` SET `data_value` = REPLACE(`data_value`, ?, ?) WHERE `company_id` = ?";
					la_do_query($update_query, array($search_data, $replace_data, $entity_id), $dbh);
					array_push($result, $row['form_id']);
				}
			}
		}
		return $result;
	}

	$folder_id = "";//isset($_REQUEST['folder']) ? $_REQUEST['folder'] : 0;
	
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);
	$selected_form_id = (int) $_GET['id'];
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);
	
	$formElement = array();
	$searchWhere = "";
	$searchParam = array();
	
	$formWhere = "";
	$form_id_arr = array();
	
	if(isset($_POST['search_by']) && !empty($_POST['search_by'])){
		if(!empty($_POST['search_data'])){
			switch($_POST['search_by']){
				case "title":
					$searchWhere = " form_name LIKE CONCAT('%', ?, '%')";
					$searchParam = array($_POST['search_data']);
					break;
					
				case "tag":
					//$searchWhere = " and find_in_set('{$_POST['search_data']}', form_tags) <> 0";
					$searchWhere = " form_tags LIKE CONCAT('%', ?, '%')";
					$searchParam = array($_POST['search_data']);
					break;
				
				case "element":
					$formElement = searchFormElement($dbh, $_POST['search_data'], $folder_id);
					
					if(count($formElement)){
						$form_id_arr = array_keys($formElement);
						$formWhere = " `form_id` in (".join(',', array_fill(0, count($form_id_arr), '?')).") ";
					}else{
						$formWhere = " `form_id` in ('-123') ";
					}
					break;

				case "replace":
					$form_id_arr = replaceForms($dbh, $_POST['entity_id'], $_POST['search_data'], $_POST['replace_data']);
					if (count($form_id_arr)) {
						$formWhere = " `form_id` in (".join(',', array_fill(0, count($form_id_arr), '?')).") ";
					} else {
						$formWhere = " `form_id` in ('-123') ";
					}
					break;
			}
		}
	}

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
	
	$element_search_keyword = "find form...";
	$elements_array_list = array();
	$append_query_string = "";
	
	//the number of forms being displayed on each page
	$rows_per_page = $la_settings['form_manager_max_rows'];  
	
	//get the list of the form, put them into array
	$query = "select form_name, form_id, form_tags, form_active, form_disabled_message, form_theme_id, folder_id from (SELECT * FROM ".LA_TABLE_PREFIX."forms WHERE form_active=0 or form_active=1 {$query_order_by_clause}) t1";
	
	if(!empty($searchWhere) || !empty($formWhere)){
		$query .= " where {$searchWhere} {$formWhere}";
	}
	
	$params = array_merge($searchParam, $form_id_arr);
	$sth = la_do_query($query, $params,$dbh);
	
	$form_list_array = array();
	$i=0;
	
	while($row = la_do_fetch_result($sth)){		
		$table_exists = "SELECT count(*) AS counter FROM information_schema.tables WHERE table_schema = '".LA_DB_NAME."' AND table_name = '".LA_TABLE_PREFIX."form_{$row['form_id']}'";
		$result_table_exists = la_do_query($table_exists,array(),$dbh);
		$row_table_exists = la_do_fetch_result($result_table_exists);
		
		//if($row_table_exists['counter'] > 0 && in_array($row['form_id'], array_keys($elements_array_list))){
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
			
			$form_list_array[$i]['folder_id']   			= $row['folder_id'];
			$form_list_array[$i]['form_active']   			= $row['form_active'];
			$form_list_array[$i]['form_disabled_message']   = $row['form_disabled_message'];
			$form_list_array[$i]['form_theme_id'] 			= $row['form_theme_id'];
			
			$form_disabled_message = json_encode($row['form_disabled_message']);
			$jquery_data_code .= "\$('#liform_{$row['form_id']}').data('form_disabled_message',{$form_disabled_message});\n";
			//get todays entries count
			//WE NEED TO ADD GROUP BY AT SERVER
			$sub_query = "SELECT SUM(today_entry) AS today_entry FROM (SELECT count(*) today_entry, `data_value` FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` WHERE `field_name` = 'date_created' GROUP BY `company_id` HAVING `data_value` >= '".date('Y-m-d')."') AS t";
			$sub_sth = la_do_query($sub_query,array(),$dbh);
			$sub_row = la_do_fetch_result($sub_sth);
			
			$form_list_array[$i]['today_entry'] = $sub_row['today_entry'];
			
			//get latest entry timing

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
?>
<style>
<?php
if(isset($_REQUEST['search_by']) && $_REQUEST['search_by'] == "element"){
?>
.div-for-element{
	display:block;
}
<?php
}else{
?>
.div-for-element{
	display:none;
}
<?php
}
?>
</style>
	<?php if(!empty($form_list_array)){ ?>
		<?php
			$row_num = 1;
			
			foreach ($form_list_array as $form_data){
				$form_name   	 = noHTML($form_data['form_name']);
				$form_id   	 	 = $form_data['form_id'];
				$today_entry 	 = $form_data['today_entry'];
				$total_entry 	 = $form_data['total_entry'];
				$latest_entry 	 = $form_data['latest_entry'];
				$theme_id		 = (int) $form_data['form_theme_id'];
				//added by alex for checking form privacy
				$privacy_class = "";
				$query_privacy = "SELECT COUNT(`entity_id`) `entity_no` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` =?";
				$sth_privacy = la_do_query($query_privacy,array($form_id),$dbh);
				$res_privacy = la_do_fetch_result($sth_privacy);
				if($res_privacy["entity_no"] == 0) {
					$privacy_class = "form_private";
				}
				//added by alex for checking form privacy
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
        <li data-theme_id="<?php echo $theme_id; ?>" id="liform_<?php echo $form_id; ?>" data-folder-id="<?php echo $form_data['folder_id']; ?>" <?php echo $form_class_tag; ?>>		
			<!-- wrapper div start here -->
			<div class="li-div-wrapper">
				<div style="width:100%;">
					<!-- TOP TABS START HERE -->
					<?php if (!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_entries']) || !empty($user_permissions[$form_id]['view_entries']) || !empty($user_permissions[$form_id]['edit_form'])) { ?>
						<div class="form_option la_link_toptabs"> 
							<?php if (!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_entries']) || !empty($user_permissions[$form_id]['view_entries'])) { ?>
								<a title="Entries" href="manage_entries.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Module.png"></a> 
							<?php } ?>
							<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_form'])) {?>
								<div class="form_option la_link_move">
									<a title="Folder" href="#"><img src="images/navigation/FFFFFF/16x16/Folder.png" style="width:16px;"></a> 
								</div>
							<?php } ?>
						</div>
					<?php } ?>
					<!-- End of TOP TABS -->
					<div style="height: 0px; clear: both;"></div>
					<div class="middle_form_bar <?php echo $privacy_class; ?>">
						<h3><?php echo $form_name; ?></h3>
						<div class="form_meta">
							<div style="float: right; padding: 14px 10px 0 0;" class="div-for-element">
								<a href="javascript:void(0)" class="anc-form-elements" data-form-id="<?php echo $form_id; ?>" data-form-toggle="0">
								<img src="images/icons/49_red_16.png">
								</a>
							</div>
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
							<div class="form_tag">
								<ul class="form_tag_list">
									<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_form'])){ ?>
										<li class="form_tag_list_icon"><a title="Add a Tag Name" class="addtag" id="addtag_<?php echo $form_id; ?>" href="#"><img src="images/navigation/FFFFFF/16x16/Tag.png"></a></li>
									<?php } ?>
									<?php
										if(!empty($form_tags_array)){
											foreach ($form_tags_array as $tagname){
												if(empty($_SESSION['is_examiner'])) {
													echo "<li>".htmlspecialchars($tagname)." <a class=\"removetag\" href=\"#\" title=\"Remove this tag.\"><img src=\"images/navigation/005499/16x16/Cancellation.png\"></a></li>";
												} else {
													echo "<li>".htmlspecialchars($tagname)."</li>";
												}
											}
										}
									?>
								</ul>
							</div>
						</div>
						<div style="height: 0px; clear: both;"></div>
					</div>
					<!-- START OF BOTTOM TABS -->
					<?php if(empty($_SESSION['is_examiner'])) { ?>
						<div class="form_option la_link_group">
							<?php
								if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_form'])) {
									$check_form_data = "select count(id) as counter from ".LA_TABLE_PREFIX."form_{$form_id}";
									$check_form_result = la_do_query($check_form_data,array(),$dbh);
									$check_form_row = la_do_fetch_result($check_form_result);
									if ($check_form_row['counter'] > 0) { ?>
										<a href="javascript:void(0);" class="custom-alert" title="Edit" data-id="<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Edit.png"></a>
									<?php } else { ?>
										<a href="edit_form.php?id=<?php echo $form_id; ?>" title="Edit"><img src="images/navigation/FFFFFF/16x16/Edit.png"></a>
									<?php } ?>
								<?php } ?>
								<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['view_entries']) || !empty($user_permissions[$form_id]['edit_entries'])) { ?>
									<a class="la_link_view" title="View" href="imported_report_list.php?form_id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/View.png"></a>
								<?php } ?>
								<?php if (!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($_SESSION['la_user_privileges']['priv_new_forms'])) { ?>
									<a class="la_link_duplicate" title="Duplicate"><img src="images/navigation/FFFFFF/16x16/Duplicate.png"></a>
								<?php } ?>
								<a class="la_link_emails" title="Notifications" href="notification_settings.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Notification.png"></a>  
								<a class="la_link_logic" title="Logic" href="logic_settings.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Logic.png"></a>
								<a class="la_link_theme" title="Theme" href="#"><img src="images/navigation/FFFFFF/16x16/Theme.png"></a>
								<a class="la_link_payment" title="Payment" href="payment_settings.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Payment.png"></a>
								<a class="la_link_integration" title="Integration" href="integration_settings.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Integration.png"></a>
								<a class="la_link_cancel" title="Cancellation" href="cancellation_settings.php?id=<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/Cancellation.png"></a>
								<?php
								if(empty($form_data['form_active'])){
									echo '<a class="la_link_disable" title="Enable" href="javascript:void(0)"><img src="images/navigation/FFFFFF/16x16/Enable.png"></a>';	
								} else {
									echo '<a class="la_link_disable" title="Disable" href="javascript:void(0)"><img src="images/navigation/FFFFFF/16x16/Disable.png"></a>';	
								}
								if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_permissions[$form_id]['edit_form'])) { ?>
									<a class="la_link_delete" title="Delete"><img src="images/navigation/FFFFFF/16x16/Delete.png"></a>
								<?php }
							?>
						</div>
					<?php } ?>
					<!-- End of BOTTOM TABS -->
				</div>
			</div>
			<!-- wrapper div ends here -->
		  
			<div class="div-element-list-wrapper" id="form-<?php echo $form_id; ?>" style="color: rgb(0, 0, 0); margin: 10px 0px 0px 20px; display:none;">
				<div style="margin:10px 0 10px 10px; width:100%; float:left;">
				  <table class="list-form-elements" border="0">
					<tr>
						<td class="field_listing" style="width:100%;">Field Title</td>
					</tr>
					<?php
					if(isset($formElement[$form_id])){
						foreach($formElement[$form_id] as $sk => $sv){
					?>	
					<tr>
						<td class="field_listing" style="width:100%; padding: 5px 0px;">
							<a href="imported_report_list.php?form_id=<?php echo $form_id; ?>&mredirect=true&la_page=<?php echo $sv['element_page_number']; ?>&element_id_auto=<?php echo $sv['element_id_auto']; ?>"><?php echo $sv['element_title']; ?></a>
						</td>
					</tr>
					<?php
						}
					}
					?>
				  </table>
				</div>
			</div>
			<div style="height: 0px; clear: both;"></div>
		  
		</div>
		  <!-- wrapper div ends here -->
        </li>
        <?php 
				$row_num++; 
			}
			//end foreach $form_list_array 
			if($row_num > 0){
		?>
		<div id="result_set_show_more"> <a href="#">Show More Results...</a> </div>
		<script type="text/javascript">
			$(function(){
				<?php echo $jquery_data_code; ?>	
		    });
		</script>
		<!-- start pagination --> 
		<?php echo $pagination_markup; ?> 
		<!-- end pagination -->
		<?php
			}
		?>
    <?php } ?>	