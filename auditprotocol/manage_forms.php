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
	require('includes/filter-functions.php');

	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$video_types = array("mp4", "avi", "mpeg");
	$image_types = array("png", "jpg", "jpeg", "bmp");

	if(isset($_POST["declined"]) && ($_POST["declined"] == "1")) {
		addUserActivity($dbh, $_SESSION["la_user_id"], "", "17", "", time(), $_SERVER['REMOTE_ADDR']);
		header("Location: logout.php");
		exit;
	}

	$portal_login_popup_img_url = $la_settings["portal_login_popup_img_url"];
	$ch = curl_init($portal_login_popup_img_url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($code != 200) {
		$portal_login_popup_img_url = "https://continuumgrc.com/wp-content/uploads/2019/08/Logo-2019090601-GRCx300.png";
	}

	$welcome_message = $la_settings["welcome_message"];
	preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $welcome_message, $url_findings);
	foreach ($url_findings[0] as $url_finding) {
		$welcome_message = str_replace($url_finding, "<a href='{$url_finding}' target='_blank' class='welcome-message-url'>{$url_finding}</a>", $welcome_message);
	}

	$selected_form_id = (int) $_GET['id'];
	$user_permissions = la_get_user_permissions_all($dbh,$_SESSION['la_user_id']);

	if(!empty($_GET['hl'])){
		$highlight_selected_form_id = true;
	}else{
		$highlight_selected_form_id = false;
	}
	
	$folder_id = isset($_REQUEST['folder']) ? $_REQUEST['folder'] : 0;
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
	
	$element_search_keyword = "";
	$element_replace_keyword = "";
	$elements_array_list = array();
	$append_query_string = "";
	
	
	//the number of forms being displayed on each page
	$rows_per_page = $la_settings['form_manager_max_rows'];  
	
	//get the list of the form, put them into array
	$query = "select form_name,
					form_id,
					form_tags,
					form_active,
					form_disabled_message,
					form_theme_id,
					folder_id from (SELECT 
					*
				FROM
					".LA_TABLE_PREFIX."forms
				WHERE
					form_active=0 or form_active=1) t1 where folder_id = ?
					{$query_order_by_clause}";
	$params = array($folder_id);
	$sth = la_do_query($query,$params,$dbh);
	
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

	//get folders list
	$query_folder = "select * from ".LA_TABLE_PREFIX."folders order by folder_name";
	$sth_folder = la_do_query($query_folder,array(),$dbh);
	$folder_list = array();
	while($row_folder = la_do_fetch_result($sth_folder)){
	  	array_push($folder_list, $row_folder);
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

	$all_tiles = array();
	$query = "SELECT `id`,	`media_source`, `background_url`, `background_media_type`, `youtube_link`, `title`, `description`, `order`, `rss_feed`, `created_at`, `updated_at` FROM ".LA_TABLE_PREFIX."announcement_slider";
	$sth = la_do_query($query, array(), $dbh);
	while($row = la_do_fetch_result($sth)) {
		$rss_links = array();
		$rss_title = "";
		if (!empty($row["rss_feed"])) {
			$xml = simplexml_load_file($row["rss_feed"]);
			if ($xml !== false) {
				$rss_title = (string) $xml->channel->title;
				for($i = 0; $i < min($xml->channel->item->count(), 3); $i++){
					$link = array();
					$link["title"]		= (string) $xml->channel->item[$i]->title;
					$link["link"]		= (string) $xml->channel->item[$i]->link;
					$link["description"]= (string) $xml->channel->item[$i]->description;
					$link["pubDate"]	= (string) $xml->channel->item[$i]->pubDate;
					array_push($rss_links, $link);
				}
			}
		}
		$row["rss_title"] = $rss_title;
		$row["rss_links"] = $rss_links;
		array_push($all_tiles, $row);
	}

	function usortTiles($a, $b) {
		return strcmp($a["order"], $b["order"]);
	}
	usort($all_tiles, "usortTiles");

	
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
.welcome-message-url {
	background: none!important;
	border: none!important;
	color: #b38b01!important;
	float: none!important;
	margin: 0px!important;
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
<style>
.search_focused{
	float:left !important;
}
.form_private {
	background-color: #F95360!important;
}
<?php
if(isset($_GET['search'])){
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
<link type="text/css" href="js/owlcarousel/owl.carousel.min.css" rel="stylesheet">
<link type="text/css" href="js/owlcarousel/owl.theme.default.min.css" rel="stylesheet">
<div id="content" class="full" data-folder-id="<?php echo $folder_id; ?>">
	<div class="post manage_forms">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left">
					<?php
						if($_SESSION['is_examiner'] == 1) {
						?>
							<h2>My Forms</h2>
							<p>Please select any of your subscriptions below.</p>
						<?php
						} else {
						?>
							<h2>Form Manager</h2>
							<p>Create, edit and manage your forms</p>
						<?php
						}
					?>
				</div>
				<?php if(!empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
					<div style="float: right;margin-right: 5px">
						<a href="edit_form.php" id="button_create_form" class="bb_button bb_small bb_green"> 
							<img src="images/navigation/FFFFFF/24x24/Create_new_form.png"> Create New Form!
						</a>
					</div>
				<?php } ?>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<?php la_show_message(); ?>
		<div class="content_body">
			<?php
				if(isset($_POST['folder'])){
					$query_folder = "select * from ".LA_TABLE_PREFIX."folders where folder_id = ?";
					$sth_folder = la_do_query($query_folder,array($_POST['folder']),$dbh);
					$row_folder = la_do_fetch_result($sth_folder);
				?>
					<a style="margin-left: 5px;" href="manage_forms.php">Back to top</a> << <strong><?php echo $row_folder['folder_name']; ?></strong>
				<?php
				}
			?>
			<?php if(!empty($form_list_array) || (!empty($folder_list))){ ?>
				<div id="la_top_pane">
					<div id="la_search_pane">
						<div id="la_search_box" style="float:left;" class="">
							<div style="position: relative">
								<input name="filter_form_input" id="filter_form_input" type="text" class="text" value="<?php echo $element_search_keyword; ?>" placeholder="Find data" autocomplete="off"/>
								<div id="la_search_title" class="la_pane_selected"><a href="#">form title</a></div>
								<div id="la_search_element"><a href="#">form elements</a></div>
								<div id="la_search_tag"><a href="#">form tags</a></div>
								<div id="la_search_replace"><a href="#">replace</a></div>
							</div>
							<div id="replace_extra_info">
								<input id="replace_form_input" type="text" class="text" value="<?php echo $element_replace_keyword; ?>" placeholder="Replace data" autocomplete="off"/>
								<select id="selected_entity_id">
									<option value="" disabled selected>Select entity</option>
									<?php 
										$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients";
										$sth = la_do_query($query, array(), $dbh);
										while($row = la_do_fetch_result($sth)) {
									?>
										<option value="<?=$row['client_id']?>"><?=$row['company_name']?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div id="div-btn-wrapper" style="float: left; padding: 3px 0px 0px 10px; display:block; margin-left: 30px;">
							<input id="search-go" class="bb_button bb_small bb_green" value="Go" type="button">
							<img id="loader-img" src="images/loader_small_grey.gif" style="margin: 7px 0px 0px 3px; float:right;">
						</div>
					</div>
					<div id="la_filter_pane">
						<div class="dropui dropuiquick dropui-menu dropui-pink dropui-right">
							<a href="javascript:;" class="dropui-tab"> Sort By &#8674; <?php echo $sortby_title; ?> </a>
							<div class="dropui-content">
								<ul>
									<li class="sub_separator">Ascending</li>
									<li <?php if($form_sort_by_complete == 'date_created-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_date_created_link" href="manage_forms.php?sortby=date_created-asc&folder=<?php echo $folder_id; ?>">Date Created</a></li>
									<li <?php if($form_sort_by_complete == 'form_title-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_title_link" href="manage_forms.php?sortby=form_title-asc&folder=<?php echo $folder_id; ?>">Form Title</a></li>
									<li <?php if($form_sort_by_complete == 'form_tags-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_tag_link" href="manage_forms.php?sortby=form_tags-asc&folder=<?php echo $folder_id; ?>">Form Tags</a></li>
									<li <?php if($form_sort_by_complete == 'today_entries-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_today_entries_link" href="manage_forms.php?sortby=today_entries-asc&folder=<?php echo $folder_id; ?>">Today's Entries</a></li>
									<li <?php if($form_sort_by_complete == 'total_entries-asc'){ echo 'class="sort_active"'; } ?>><a id="sort_total_entries_link" href="manage_forms.php?sortby=total_entries-asc&folder=<?php echo $folder_id; ?>">Total Entries</a></li>
									<li class="sub_separator">Descending</li>
									<li <?php if($form_sort_by_complete == 'date_created-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_date_created_link" href="manage_forms.php?sortby=date_created-desc&folder=<?php echo $folder_id; ?>">Date Created</a></li>
									<li <?php if($form_sort_by_complete == 'form_title-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_title_link" href="manage_forms.php?sortby=form_title-desc&folder=<?php echo $folder_id; ?>">Form Title</a></li>
									<li <?php if($form_sort_by_complete == 'form_tags-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_form_tag_link" href="manage_forms.php?sortby=form_tags-desc&folder=<?php echo $folder_id; ?>">Form Tags</a></li>
									<li <?php if($form_sort_by_complete == 'today_entries-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_today_entries_link" href="manage_forms.php?sortby=today_entries-desc&folder=<?php echo $folder_id; ?>">Today's Entries</a></li>
									<li <?php if($form_sort_by_complete == 'total_entries-desc'){ echo 'class="sort_active"'; } ?>><a id="sort_total_entries_link" href="manage_forms.php?sortby=total_entries-desc&folder=<?php echo $folder_id; ?>">Total Entries</a></li>
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
				<div id="filtered_result_none"> Your filter did not match any of your forms. </div>
				<div id="form_result_div" style="display: none;">
					<section id="form_result" class="row">
						<?php if(!isset($_POST['folder']) && count($folder_list) > 0){ ?>
							<style>
								#la_folder_list{
									margin-bottom: 5px;
									padding: 0;
									color: #fff;
									text-shadow: 0 1px 1px rgba(0,0,0,.25), 0 0 1px rgba(0,0,0,.3);
								}
								#la_folder_list .folder-div.middle_form_bar{
									background-color: #1D2155;
									border-radius: 4px;
									box-shadow: 1px 1px 2px rgba(0,0,0,.2);
									border: 2px solid #fff;
								}
								#la_folder_list li h3{
									padding: 10px 10px 10px 13px;
									cursor: pointer;
									display: block;
								}
							</style>

							<div class="col-md-6 padding-right-10">
								<ul id="la_folder_list">
									<?php
										foreach($folder_list as $row_folder){
											//get the list of the form, put them into array
											$query = "select form_name,	form_id, form_tags, form_active, form_disabled_message, form_theme_id, folder_id from (SELECT * FROM ".LA_TABLE_PREFIX."forms WHERE form_active=0 or form_active=1) t1 where folder_id = ? {$query_order_by_clause}";
											$params = array($row_folder['folder_id']);
											$sth = la_do_query($query,$params,$dbh);

											$forms_in_folder_list = array();
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

													$forms_in_folder_list[$i]['form_id']   	  = $row['form_id'];
													$row['form_name'] = la_trim_max_length($row['form_name'],75);
													if(!empty($row['form_name'])){
														$forms_in_folder_list[$i]['form_name'] = $row['form_name'];
													}else{
														$forms_in_folder_list[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
													}	

													$forms_in_folder_list[$i]['folder_id']   			= $row['folder_id'];
													$forms_in_folder_list[$i]['form_active']   			= $row['form_active'];
													$forms_in_folder_list[$i]['form_disabled_message']   = $row['form_disabled_message'];
													$forms_in_folder_list[$i]['form_theme_id'] 			= $row['form_theme_id'];

													$form_disabled_message = json_encode($row['form_disabled_message']);
													//get todays entries count
													//WE NEED TO ADD GROUP BY AT SERVER
													$sub_query = "SELECT SUM(today_entry) AS today_entry FROM (SELECT count(*) today_entry, `data_value` FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` WHERE `field_name` = 'date_created' GROUP BY `company_id` HAVING `data_value` >= '".date('Y-m-d')."') AS t";
													$sub_sth = la_do_query($sub_query,array(),$dbh);
													$sub_row = la_do_fetch_result($sub_sth);

													$forms_in_folder_list[$i]['today_entry'] = $sub_row['today_entry'];

													//get latest entry timing

													//get total entries count
													if($form_sort_by == 'total_entries'){
														$sub_query = "SELECT count(*) total_entry FROM `".LA_TABLE_PREFIX."form_{$row['form_id']}` where field_name='status' and data_value = 1";
														$sub_sth = la_do_query($sub_query,array(),$dbh);
														$sub_row = la_do_fetch_result($sub_sth);
														$forms_in_folder_list[$i]['total_entry'] = $sub_row['total_entry'];
													}

													//get form tags and split them into array
													if(!empty($row['form_tags'])){
														$form_tags_array = explode(',',$row['form_tags']);
														array_walk($form_tags_array, 'la_trim_value');
														$forms_in_folder_list[$i]['form_tags'] = $form_tags_array;
													}
													$i++;
												}
											}

											if($form_sort_by == 'today_entries'){
												if($form_sort_order == 'asc'){
													usort($forms_in_folder_list, 'sort_by_today_entry_asc');
												}else{
													usort($forms_in_folder_list, 'sort_by_today_entry_desc');
												}
											}

											if($form_sort_by == 'total_entries'){
												if($form_sort_order == 'asc'){
													usort($forms_in_folder_list, 'sort_by_total_entry_asc');
												}else{
													usort($forms_in_folder_list, 'sort_by_total_entry_desc');
												}
											}

											if(empty($selected_form_id)){ //if there is no preference for which form being displayed, display the first form
												$selected_form_id = $forms_in_folder_list[0]['form_id'];
											}
											if(count($forms_in_folder_list) > 0) {
											?>

												<li id="lifolder_<?php echo $row_folder['folder_id']; ?>" data-folder-id="<?php echo $row_folder['folder_id']; ?>" data-folder-name="<?php echo $row_folder['folder_name']; ?>">
													<!-- wrapper div start here -->
													<div class="li-div-wrapper">
														<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])) {?>
															<div class="la_link_topfolder">
																<div class="form_option la_folder_rename"><a href="javascript:void(0)" title="Rename"><img src="images/navigation/FFFFFF/16x16/Edit.png"></a></div>
																<div class="form_option la_folder_delete"><a href="javascript:void(0)" title="Delete"><img src="images/navigation/FFFFFF/16x16/Delete.png"></a></div>
																<div style="height: 0px; clear: both;"></div>
															</div>
														<?php } ?>
														<div class="folder-div middle_form_bar">
															<h3><img src="images/navigation/FFFFFF/16x16/Folder.png" style="margin-right:2px;"><?php echo $row_folder['folder_name']; ?></h3>
														</div>
														<div class="la_link_bottomfolder"> 
															<ul class="la_form_list">
																<?php
																	foreach ($forms_in_folder_list as $form_data){
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

																		if(empty($form_data['form_active'])){
																			$form_class[] = 'form_inactive';
																		}

																		$form_class_joined = implode(' ',$form_class);
																		$form_class_tag	   = 'class="'.$form_class_joined.'"';
																	?>
																		<li data-theme_id="<?php echo $theme_id; ?>" id="liforminfolder_<?php echo $form_id; ?>" data-folder-id="<?php echo $form_data['folder_id']; ?>" <?php echo $form_class_tag; ?>>		
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
																							<?php if(!empty($total_entry)) { ?>
																								<div class="form_stat form_stat_total" title="<?php echo $today_entry." entries today. Latest entry ".$latest_entry."."; ?>">
																									<div class="form_stat_count"><?php echo $total_entry; ?></div>
																									<div class="form_stat_msg">total</div>
																								</div>
																							<?php } else if(!empty($today_entry)) { ?>
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
																									<a class="la_link_view" title="View" href="#" data-form_id="<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/View.png"></a>
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
																		</li>
																	<?php
																	}
																	//end foreach $forms_in_folder_list 
																?>
															</ul>
														</div>
													</div>
												<!-- wrapper div ends here -->
												</li>
											<?php
											}
										}
									?>
								</ul>
							</div>
						<?php
						}
						?>

						<?php if(isset($_POST['folder']) || count($folder_list) == 0){ ?>
							<div style="width: 100%;">
						<?php } else { ?>
							<div class="col-md-6 padding-left-10">
						<?php } ?>
								<ul id="la_form_list" class="la_form_list">
									<?php
									$row_num = 1;
									foreach ($form_list_array as $form_data){
										$form_name   	 = noHTML($form_data['form_name']);
										$form_id   	 	 = $form_data['form_id'];
										$today_entry 	 = $form_data['today_entry'];
										$total_entry 	 = $form_data['total_entry'];
										$latest_entry 	 = $form_data['latest_entry'];
										$theme_id		 = (int) $form_data['form_theme_id'];
										$privacy_class = "";
										$query_privacy = "SELECT COUNT(`entity_id`) `entity_no` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` =?";
										$sth_privacy = la_do_query($query_privacy,array($form_id),$dbh);
										$res_privacy = la_do_fetch_result($sth_privacy);
										if($res_privacy["entity_no"] == 0) {
											$privacy_class = "form_private";
										}
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
																	<a class="la_link_view" title="View" href="#" data-form_id="<?php echo $form_id; ?>"><img src="images/navigation/FFFFFF/16x16/View.png"></a>
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
										</li>
									<?php
										$row_num++;
									}
									//end foreach $form_list_array 
									?>
								</ul>
								<div id="result_set_show_more"> <a href="#">Show More Results...</a> </div>
								<!-- start pagination --> 
								<?php echo $pagination_markup; ?> 
								<!-- end pagination -->					
							</div>
					</section>
				</div>
			<?php } else { ?>
				<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($_SESSION['la_user_privileges']['priv_new_forms'])){ ?>
					<div id="form_manager_empty"> <img src="images/icons/arrow_up.png" />
						<h2>Welcome!</h2>
						<h3>You have no forms yet. Go create one by clicking the button above.</h3>
					</div>
				<?php } else { ?>
					<div id="form_manager_empty">
						<h2 style="padding-top: 135px">Welcome!</h2>
						<h3>You currently have no access to any forms.</h3>
					</div>
				<?php } ?>
			<?php } ?>

			<?php if($la_settings['enable_announcement_slider']) { ?>
				<style>
					.my-slider {
						margin-top: 50px;
					}
					.my-slider .item .details {
						position: absolute; 
						top: 10px; 
						left: 10px; 
						z-index: 10;
						width: 100%;
					}
					.my-slider .item .details h3{
						text-align: center;
						margin: 10px;
						color: #b38b01;
					}
					.my-slider .item .details ul {
						list-style-type: circle !important;
						margin-left: 30px;
						color: #b38b01;
					}

					.my-slider .item .details ul li {
						margin-bottom: 10px
					}

					.my-slider .item .details ul li a {
						color: #b38b01;
						font-weight: bold;
						font-size: 16px;
					}

					.my-slider .item .details ul li a:visited {
						color: #b38b01;
					}
					.my-slider .item img {
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
					} 
					.my-slider .item video {
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
					} 
					.my-slider .item iframe {
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
					}
				</style>
				<div class="owl-carousel owl-theme my-slider">
					<?php foreach ($all_tiles as $tile) { ?>
						<div class="item">
							<div style="position: relative; width: 100%;  padding-top: 56.25%;">
								<div class="details" style="position: absolute; top: 10px; left: 10px; z-index: 10;">
									<?php if (count($tile["rss_links"])) { ?>
										<h3><?=$tile["rss_title"]?></h3>
										<ul>
											<?php foreach ($tile["rss_links"] as $link) { ?>
												<li>
													<a href="<?php echo $link["link"];?>" target="_blank"><?php echo $link["title"]; ?></a>
													<p>[<?=$link["pubDate"]?>]</p>
												</li>
											<?php } ?>
										</ul>
									<?php } else { ?>
										<?php echo $tile["description"]; ?>
									<?php } ?>
								</div>
								<?php if ($tile["media_source"] === 'local') { ?>
									<?php if (in_array($tile["background_media_type"], $image_types)) { ?>
										<img src="<?php echo $tile["background_url"] ?>" alt="<?php echo $tile["title"]?>" width="100%" height="100%"/>
									<?php } else if (in_array($tile["background_media_type"], $video_types)){ ?>
										<video src="<?php echo $tile["background_url"]; ?>" id="<?=$tile["id"]?>" controls></video>	
									<?php } ?>
								<?php } else { ?>
									<iframe src="<?=$tile["youtube_link"] ? getYoutubeEmbedUrl($tile["youtube_link"]) : ''?>"></iframe>              
								<?php } ?>
							</div>				
						</div>
					<?php } ?>
				</div>
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
			<div id="dialog-confirm-form-move" title="Are you sure you want to move this form?" class="buttons" style="display: none">
				<div style="text-align:center; width: 100%; margin-bottom: 15px;" class="icon-bubble-notifications">
					<img src="images/navigation/ED1C2A/50x50/Warning.png" style="margin-bottom: 15px;" />
				</div>
				<div>
					<table style="border-spacing:2px; margin-left:100px;" border="1">
					<tbody>
					<tr>
					<td width="50px"></td>
					<td height="30px">
					<select id="folder-id" style="width:230px;">
					<option value="0">Select Top Level</option>
					<?php
					foreach($folder_list as $v){
					if($v['folder_id'] == $folder_id){
					$selected = "selected";
					}else{
					$selected = "";
					}
					?>
					<option <?php echo $selected; ?> value="<?php echo $v['folder_id']; ?>"><?php echo $v['folder_name']; ?></option>
					<?php
					}
					?>
					</select>
					</td>
					</tr>
					<tr>
					<td></td>
					<td style="padding-left: 85px;"><button type="button" id="btn-form-move-ok" class="bb_button bb_small bb_green">Move</button></td>
					</tr>
					<tr><td height="20px"></td><td></td></tr>
					<tr>
					<td></td>
					<td align="center">OR</td>
					</tr>
					<tr><td height="20px"></td><td></td></tr>
					<tr>
					<td><label></label></td>
					<td height="30px"><input id="folder-name" style="width:230px;" placeholder="Enter folder name" type="text"></td>
					</tr>
					<tr><td height="5px"></td><td></td></tr>
					<tr>
					<td></td>
					<td style="padding-left: 55px;"><button type="button" id="btn-form-create-and-move-ok" class="bb_button bb_small bb_green">Create &amp; Move</button></td>
					</tr>
					</tbody>
					</table>
				</div>
			</div>
			<div id="dialog-confirm-folder-rename" title="Are you sure you want to rename this folder?" class="buttons" style="display: none">
				<div style="text-align:center; width: 100%; margin-bottom: 15px;" class="icon-bubble-notifications"><img src="images/navigation/005499/50x50/Notice.png" /></div>
				<div>
					<table style="border-spacing:2px; margin-left:100px;" border="1">
						<tbody>
							<tr>
								<td><label></label></td>
								<td height="30px"><input id="folder-rename" style="width:230px;" placeholder="Enter folder name" type="text"></td>
							</tr>
							<tr><td height="5px"></td><td></td></tr>
							<tr>
								<td></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div id="dialog-confirm-folder-delete" title="Caution! Are you sure you want to delete this folder?" class="buttons" style="display: none; text-align: center;"> <img src="images/navigation/ED1C2A/50x50/Warning.png">
				<p>This action cannot be undone.<br/>
					All forms collected by <span id="folder-delete" style="font-weight:bold;">this folder</span> will be moved to Top Level.<br/>
				</p>
			</div>

			<div id="dialog-confirm-form-delete" title="Are you sure you want to delete this form?" class="buttons" style="display: none">
				<img src="images/navigation/ED1C2A/50x50/Warning.png">
				<p> This action cannot be undone.<br/>
					<strong>All data and files collected by <span id="confirm_form_delete_name">this form</span> will be deleted as well.</strong><br/>
					<br/>
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
			<div id="dialog-confirm-replace" title="Are you sure you want to change all instances of your data with these settings? " class="buttons" style="display: none">
				<img src="images/navigation/ED1C2A/50x50/Warning.png">
				<p> This action cannot be undone.<br/>
			</div>
			<div id="dialog-confirm-create-entry" title="Create a New Entry" class="buttons" style="display: none; text-align: center;">
				<img src="images/navigation/005499/50x50/Notice.png">
				<input type="hidden" id="form_id_for_create_entry">
				<p>As an administrator, you are about to create a new form entry that is unique and belongs to the Admin group, and will not be merged with any other form data that belongs to another Entity group. Do you wish to proceed?</p>
			</div>
			<!-- end dialog boxes --> 
		</div>
		<!-- /end of content_body --> 
	</div>
	<!-- /.post --> 
</div>
<!-- /#content -->
<div id="dialog-confirm-edit" title="Caution! Are you sure you want to edit this form?" class="buttons" style="display: none; text-align: center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-confirm-edit-msg">This form contains user data. If you edit and save this form then the existing data will be deleted. Do you want to proceed?<br/><br/>
	</p>
</div>
<div id="dialog-welcome-message" title="Message" class="buttons" style="display: none; text-align: center;"><img alt="" src="<?php echo $portal_login_popup_img_url; ?>" width="300"><br>
	<br>
	<input id="popup_session_value" type="hidden" value="<?php echo $_SESSION['admin_login_message_enabled']; ?>">
	<p><?php echo $welcome_message; ?></p>
	<form id="decline_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
		<input type="hidden" name="declined" value="1">
	</form>
</div>
<form id="form-folder" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input name="folder" type="hidden" id="folder" />
</form>
<script>
$(document).ready(function(){
	$(document).on('click', '.folder-h3', function(){
		var tmp = ($(this).parents('li').attr('id')).split("_");
		$('form#form-folder #folder').val(tmp[1]);
		$('form#form-folder').submit();
	})
});
</script>
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
<script>
	window.onload = function () {
		document.getElementById("loader-img").style.display="none";
		document.getElementById("form_result_div").style.display="block";
	}
$(document).ready(function(){
	var form_id = 0;
	$(document).on('click', 'a.custom-alert', function(){console.log('here');
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

	$("#dialog-welcome-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Agree',
				id: 'btn-welcome-message-ok',
				'class': 'btn_secondary_action',
				click: function() {
					$.ajax({
			            url:"clear_login_popup_session.php",
			            type:"POST",
			            data:{
			                mode:"clear_login_popup_session",
			                clear_session: true
			            },
			            error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#ui-dialog-title-dialog-error").html('Delete Note');
						   	$("#dialog-error-msg").html("Something went wrong. Please try again later.");
							$("#dialog-error").dialog('open');
						},
			            success: function(response){
			                if(response == "cleared") {
			                    console.log("Welcome message will not pop up.");
			                }
			            }
			        });
			        $(this).dialog('close');
				}
			},
			{
				text: 'Decline',
				'class': 'btn_secondary_action',
				click: function() {
					$("#decline_form").submit();
				}
			}
		]
	});

	if($("#popup_session_value").val() == 1) {
		$("#dialog-welcome-message").dialog('open');
	}
});
</script>

<script src="js/owlcarousel/owl.carousel.min.js"></script>
<script>
$('.owl-carousel').owlCarousel({
    loop:parseInt("<?= count($all_tiles) ?>") > 3 ? true: false,
    margin:10,
	nav:false,
	dots: true,
	autoplay: true,
	autoplayTimeout: <?php echo isset($la_settings['announcement_slider_speed']) ? $la_settings['announcement_slider_speed'] : 1000 ?>,
	autoplayHoverPause: true,
    responsive:{
        0:{
			items:1,
			nav:false,
        },
        800:{
			items:3,
			nav:true,
        },
        1600:{
			items:5,
			nav:true,
        }
    }
})

$('.owl-carousel video').on('play', function() {
	var videoElements = document.getElementsByTagName('video');
	for (var i = 0; i < videoElements.length; i++) {
		if ($(this).attr("id") !== videoElements[i].getAttribute("id")) {
			videoElements[i].pause();
		}
	}
});

</script>