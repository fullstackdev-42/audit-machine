<?php

/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com

More info at: http://lazarusalliance.com
********************************************************************************/

// enable the two lines below to hide all php errors

require('includes/init.php');
require('config.php');
require('includes/language.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/filter-functions.php');
require('includes/view-functions.php');
require('includes/users-functions.php');
require('includes/post-functions.php');
require('portal-header.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$video_types = array("mp4", "avi", "mpeg");
$image_types = array("png", "jpg", "jpeg", "bmp");

if(isset($_POST["declined"]) && ($_POST["declined"] == "1")) {
	addUserActivity($dbh, $_SESSION["la_client_user_id"], "", "17", "", time(), $_SERVER['REMOTE_ADDR']);
	header("Location: client_logout.php");
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
//the number of forms being displayed on each page 
$rows_per_page = $la_settings['form_manager_max_rows'];
$selected_page_number = 1;
$selectedEntity = $_SESSION['la_client_entity_id'];
$has_form = false;
$universal_form_statement = "";
$user_forms = array();
$subscribed_form_ids = array();
$all_form_ids = array();
$cascade_subform_ids = array();
$all_folders = array();
$folders = array();
$all_tiles = array();

$search_filter_type = "";
$search_filter_input = "";
$search_filter_replace = "";
if(isset($_POST["search_filter"])){
	$search_filter_type = $_POST["search_filter"];
}
if(isset($_POST["filter_form_input"])){
	$search_filter_input = $_POST["filter_form_input"];
}
if(isset($_POST["replace_form_input"])){
	$search_filter_replace = $_POST["replace_form_input"];
}

/**********************************************/
/*   Fetching Company data from client table   */
/**********************************************/

?>

<link rel="stylesheet" href="js/owlcarousel/owl.carousel.min.css">
<link rel="stylesheet" href="js/owlcarousel/owl.theme.default.min.css">

<style>
#la_search_pane {
	float: left;
	width: 100%;
	margin-bottom: 10px;
	padding: 2px;
}
#la_filter_pane {
	padding-top: 10px;
	float: right;
	width: 47%;
}
#la_search_box {
	width: 135px;
	position: relative;
}
#la_search_box.search_focused {
	width: 300px;
}
.search_focused #filter_form_input {
	width: 275px;
}
#la_search_title {
	display: none;
	position: absolute;
	right: 186px;
	top: 100%;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #818C9B;
	font-size: 90%;
	padding: 0 10px 3px 10px;
	border-radius: 0 0 4px 4px;
}
#la_search_element {
	display: none;
	position: absolute;
	right: 80px;
	top: 100%;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #818C9B;
	font-size: 90%;
	padding: 0 10px 3px 10px;
	border-radius: 0 0 4px 4px;
}
#la_search_replace {
	display: none;
	position: absolute;
	right: 10px;
	top: 100%;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #818C9B;
	font-size: 90%;
	padding: 0 10px 3px 10px;
	border-radius: 0 0 4px 4px;
}
#la_search_title a, #la_search_element a, #la_search_replace a {
	color: #1D2155;
}
#la_search_title a:hover, #la_search_element a:hover, #la_search_replace a:hover{
	text-decoration: none!important;
}
#la_search_title.la_pane_selected a, #la_search_element.la_pane_selected a, #la_search_replace.la_pane_selected a {
	color: #FFFFFF;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.25), 0 0 1px rgba(255, 255, 255, 0.3);
	font-weight: bold;
}

#replace_extra_info {
	display: none;
	margin-top: 25px;
	width: calc(100% - 16px);
}

#replace_form_input {
	width: 100%;
	padding: 10px;
}

#replace_form_input::placeholder, #filter_form_input::placeholder {
	color: white;
}

.search_focused #la_search_title, .search_focused #la_search_element, .search_focused #la_search_replace {
	display: block;
}
#la_filter_pane .dropui {
	float: right;
}
.highlight {
	background-color: #31d971;
}
.field_listing {
    float: left;
}
.welcome-message-url {
	background: none!important;
	border: none!important;
	color: #b38b01!important;
	float: none!important;
	margin: 0px!important;
}
</style>

<div class="content_body">

	<?php
		//Get a list of all invisible cascade sub forms
		$cascade_subform_ids = get_cascade_subform_ids($dbh);
		//Get a list of all subscribed forms

		$query1 = "SELECT DISTINCT `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`, `".LA_TABLE_PREFIX."forms`.`form_name`, `form_description`, `folder_id`, `form_theme_id`
				  FROM `".LA_TABLE_PREFIX."forms`
				  LEFT JOIN `".LA_TABLE_PREFIX."ask_client_forms` ON (`".LA_TABLE_PREFIX."forms`.`form_id` = `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`)
				  WHERE `".LA_TABLE_PREFIX."ask_client_forms`.`client_id` = {$selectedEntity}
				  AND `".LA_TABLE_PREFIX."forms`.`form_active` = 1";
		
		$sth1 = la_do_query($query1, array(), $dbh);

		while($row = la_do_fetch_result($sth1)){

			$formEntities = getFormAccessibleEntities($dbh, $row['form_id']);

			if(!in_array("0", $formEntities)){

				if(in_array($selectedEntity, $formEntities)){
					$user_forms[$row['form_id']] =  array(
							'form_id' => $row['form_id'],
							'form_name' => $row['form_name'],
							'form_description' => $row['form_description'],
							'folder_id' => $row['folder_id'],
							'subscribed' => true,
							'theme_id' => $row['theme_id']
					);
					array_push($subscribed_form_ids, $row['form_id']);
					array_push($all_form_ids, $row['form_id']);
				}
			} else {
				$user_forms[$row['form_id']] =  array(
 						'form_id' => $row['form_id'],
 						'form_name' => $row['form_name'],
 						'form_description' => $row['form_description'],
 						'folder_id' => $row['folder_id'],
 						'subscribed' => true,
 						'theme_id' => $row['theme_id']
				);
				array_push($subscribed_form_ids, $row['form_id']);
				array_push($all_form_ids, $row['form_id']);
			}
		}
		
		//Get a list of accessible but unsubscribed forms

		if (is_numeric($selectedEntity)) {
			//Get a list of all accessible forms
			$query2 = "SELECT form_id FROM ".LA_TABLE_PREFIX."entity_form_relation
				WHERE (entity_id = 0)
				OR (entity_id = $selectedEntity)";
			$sth2 = la_do_query($query2,array(),$dbh);
			$formIds = array();
			while($row = la_do_fetch_result($sth2)){
				$formIds[] =  $row['form_id'];
			}
			if (count($formIds) > 0) {
				$string_form_ids = implode(',', $formIds);
				$universal_form_statement = "AND `form_id` IN ($string_form_ids)";
			}

			$query3 = "SELECT `form_id`, `form_name`, `form_description`, `folder_id`, `form_theme_id` FROM ".LA_TABLE_PREFIX."forms WHERE `form_active` = 1 $universal_form_statement";
			$sth3 = la_do_query($query3, array(), $dbh);
			//Get a list of all unsubscribed forms
			while ($row = la_do_fetch_result($sth3)) {
				if(!in_array($row['form_id'], $subscribed_form_ids)){
					$user_forms[$row['form_id']] =  array(
						'form_id' => $row['form_id'],
						'form_name' => $row['form_name'],
						'form_description' => $row['form_description'],
						'folder_id' => $row['folder_id'],
						'subscribed' => false,
						'theme_id' => $row['form_theme_id']
					);
					array_push($all_form_ids, $row['form_id']);					
				}
			}
		}
		//Implement Search Functionality
		if($search_filter_type != "" && $search_filter_input != ""){
			switch ($search_filter_type) {
				case 'name':
					foreach ($user_forms as $key => $form) {
						if(stripos( $form["form_name"], $search_filter_input ) === false){							
							unset($user_forms[$key]);
							if (($form_key = array_search($key, $all_form_ids)) !== false) {
							    unset($all_form_ids[$form_key]);
							}
						}
					}
					break;
				
				case 'element':
					$res = array();
					$in = implode(',', array_fill(0, count($all_form_ids), '?'));
					$query_element = "SELECT DISTINCT form_id FROM ".LA_TABLE_PREFIX."form_elements WHERE element_title LIKE CONCAT('%', ?, '%') AND form_id IN ({$in})";
					$sth_element = la_do_query($query_element, array_merge(array($search_filter_input), $all_form_ids), $dbh);					
					while($row_element = la_do_fetch_result($sth_element)){
						array_push($res, $row_element["form_id"]);
					};

					foreach ($user_forms as $key => $form) {
						if(!in_array($key, $res)){
							unset($user_forms[$key]);
							if (($form_key = array_search($key, $all_form_ids)) !== false) {
							    unset($all_form_ids[$form_key]);
							}
						}
					}
					break;
				case 'replace':
					$res = array();
					foreach ($all_form_ids as $form_id) {
						$search_query = "SELECT * FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ? and `data_value` LIKE CONCAT('%', ?, '%')";
						$search_sth = la_do_query($search_query, array($selectedEntity, $search_filter_input), $dbh);
						$search_row = la_do_fetch_result($search_sth);
						if ($search_row) {
							$update_query = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET `data_value` = REPLACE(`data_value`, ?, ?) WHERE `company_id` = ?";
							la_do_query($update_query, array($search_filter_input, $search_filter_replace, $selectedEntity), $dbh);
							array_push($res, $form_id);
						}
					}

					foreach ($user_forms as $key => $form) {
						if(!in_array($key, $res)){
							unset($user_forms[$key]);
							if (($form_key = array_search($key, $all_form_ids)) !== false) {
							    unset($all_form_ids[$form_key]);
							}
						}
					}
					break;
			}
		}

		//Remove invisible cascade sub forms from the list of forms
		foreach ($cascade_subform_ids as $row) {
			if(in_array($row['form_id'], $all_form_ids) && in_array($row['element_default_value'], $all_form_ids)){
				foreach ($user_forms as $key => $form) {
					if($form['form_id'] == $row['element_default_value']){
						unset($user_forms[$key]);
						if (($form_key = array_search($key, $all_form_ids)) !== false) {
						    unset($all_form_ids[$form_key]);
						}
					}
				}
			}
		}
		
		//get submission time of all entries for this user
		foreach ($all_form_ids as $form_id) {
			$query_date_created = "select data_value from ".LA_TABLE_PREFIX."form_".$form_id." where company_id = ? AND field_name = 'date_created'";
			$sth_date_created = la_do_query($query_date_created,array($selectedEntity),$dbh);
			$user_forms[$form_id]['date_created'] = null;
			while($row_date_created = la_do_fetch_result($sth_date_created)){
			  	$user_forms[$form_id]['date_created'] = $row_date_created['data_value'];
			}
		}

		function date_compare($a, $b)
		{
		    $t1 = strtotime($a['date_created']);
		    $t2 = strtotime($b['date_created']);
		    return $t2 - $t1;
		}
		usort($user_forms, 'date_compare');
		if(count($user_forms) > 0){
			$has_form = true;
		}
		//Get a list of folders
		$query_folder = "select * from ".LA_TABLE_PREFIX."folders order by folder_name";
		$sth_folder = la_do_query($query_folder,array(),$dbh);
		while($row = la_do_fetch_result($sth_folder)){
		  	array_push($all_folders, $row);
		}
		//Sort the forms by folder
		foreach($all_folders as $folder){
			$forms_in_folder = array();
			foreach ($user_forms as $key => $form) {
				if(($folder['folder_id'] == $form['folder_id']) && ($form['folder_id'] > 0)){
					array_push($forms_in_folder, $form);
					unset($user_forms[$key]); //Remove form that is included in any folder
				}
			}
			if(count($forms_in_folder) > 0){
				array_push($folders, array(
						'folder_id' => $folder['folder_id'],
						'folder_name' => $folder['folder_name'],
						'forms' => $forms_in_folder
					)
				);
			}
			$user_forms = array_values($user_forms);
		}
		//Pagination
		//build pagination markup
		$total_rows = count($user_forms);
		$total_page = ceil($total_rows / $rows_per_page);
		
		if($total_page > 1){
			
			$start_form_index = 0;
			$pagination_markup = '<ul id="la_pagination" class="pages green small">'."\n";
			
			for($i=1;$i<=$total_page;$i++){
				
				//attach the data code into each pagination button
				$end_form_index = $start_form_index + $rows_per_page;
				$liform_ids_array = array();
				
				for ($j=$start_form_index;$j<$end_form_index;$j++) {
					if(!empty($user_forms[$j]['form_id'])){
						$liform_ids_array[] = '#liform_'.$user_forms[$j]['form_id'];
						
						//put the page number into the array
						$user_forms[$j]['page_number'] = $i;
					}
				}
				
				$liform_ids_joined = implode(',',$liform_ids_array);
				$start_form_index = $end_form_index;
				
				$jquery_data_code = isset($jquery_data_code)
					? $jquery_data_code."\$('#pagebtn_{$i}').data('liform_list','{$liform_ids_joined}');\n"
					: "\$('#pagebtn_{$i}').data('liform_list','{$liform_ids_joined}');\n";
				
				
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
			foreach ($user_forms as $key=>$value){
				$user_forms[$key]['page_number'] = 1;
			}
		}

		$query = "SELECT `id`,	`media_source`, `background_url`, `background_media_type`, `youtube_link`, `title`, `description`, `order`, `rss_feed`, `created_at`, `updated_at` FROM ".LA_TABLE_PREFIX."announcement_slider";
		$sth = la_do_query($query, array(), $dbh);
		while($row = la_do_fetch_result($sth)) {
			$full_path = '../auditprotocol/';
			$full_path .= $row["background_url"] ? $row["background_url"] : 'avatars/default.png';
			$row["full_path"] = $full_path;
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
	?>
	<?php la_show_message(); ?>
	<div id="la_top_pane">
		<div class="search-box" style="width: 47%">
			<form id="search_form" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>" method="post">
			
				<div id="la_search_pane">
				  	<div id="la_search_box" style="float:left;">
					  	<div style="position: relative">
							<?php
								if($search_filter_type != "" && $search_filter_input !=""){
							?>
								<input name="filter_form_input" id="filter_form_input" class="text" value="<?php echo $search_filter_input; ?>" type="text" autocomplete="off">				  			
							<?php
								} else {
							?>
								<input name="filter_form_input" id="filter_form_input" class="text" value="find form..." type="text" autocomplete="off">
							<?php
								}
							?>
							<?php
								if($search_filter_type == "element"){
							?>
								<div id="la_search_title"><a href="#">form title</a></div>
								<div id="la_search_element" class="la_pane_selected"><a href="#">form elements</a></div>
								<div id="la_search_replace"><a href="#">replace</a></div>
								<input id="search_filter" type="hidden" name="search_filter" value="element">
							<?php
								} else if($search_filter_type == "replace"){
							?>
								<div id="la_search_title"><a href="#">form title</a></div>
								<div id="la_search_element"><a href="#">form elements</a></div>
								<div id="la_search_replace" class="la_pane_selected"><a href="#">replace</a></div>
								<input id="search_filter" type="hidden" name="search_filter" value="replace">
							<?php
								} else {
							?>
								<div id="la_search_title" class="la_pane_selected"><a href="#">form title</a></div>
								<div id="la_search_element"><a href="#">form elements</a></div>
								<div id="la_search_replace"><a href="#">replace</a></div>
								<input id="search_filter" type="hidden" name="search_filter" value="name">
							<?php
								}
							?>
						</div>
						<div id="replace_extra_info">
							<input id="replace_form_input" name="replace_form_input" type="text" class="text" value="<?php echo $element_replace_keyword; ?>" placeholder="Replace data" autocomplete="off"/>
							<input id="selected_entity_id" name="selected_entity_id" type="hidden" value="<?php echo $selectedEntity; ?>">
						</div>
				  	</div>
				  	<div style="float: left; padding: 3px 0px 0px 10px; width: 20%; margin-left: 30px;">
					    <input id="search-go" class="bb_button bb_small bb_green" value="Go" type="button">
					    <img id="loader-img" src="images/loader_small_grey.gif" style="margin: 0px 0px 0px 3px; display:none;">
				  	</div>
				</div>
			</form>
		</div>
	</div>	
	<section id="form_result" class="row">
	<?php
	if($has_form){
		if(count($folders) > 0){ ?>
		<style type="text/css">
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
			foreach ($folders as $folder) {
				?>
				<li id="lifolder_<?php echo $folder['folder_id']; ?>">
					<div class="li-div-wrapper">
						<div class="folder-div middle_form_bar">
							<h3><img src="images/navigation/FFFFFF/16x16/Folder.png" style="margin-right:2px;"><?php echo $folder['folder_name']; ?></h3>
						</div>
						<div class="la_link_bottomfolder">
							<ul class="la_form_list">
								<?php
								foreach ($folder['forms'] as $form) {
									if($form['subscribed'] == true){
									?>
										<li data-theme_id="<?php echo noHTML($form['theme_id']); ?>" id="liform_<?php echo noHTML($form['form_id']); ?>" class="form_active form_visible">
											<div class="li-div-wrapper">
												<div style="width:100%;">												
													<div class="form_option la_link_toptabs">
														<a title="View" href="#" class="view-entry-btn" form-id="<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/View.png" style="width:16px;"></a>
														<div class="form_option la_link_move">
															<a title="Entries" href="manage_entries.php?id=<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/Module.png"></a>
														</div>
													</div>
													<div style="height: 0px; clear: both;"></div>
													<div class="middle_form_bar">
														<?php
														if($search_filter_type == "name" && $search_filter_input != ""){
															$pattern = preg_quote($search_filter_input);
														?>
														<h3><?php echo preg_replace("/($pattern)/i", "<span class='highlight'>$1</span>", $form['form_name']) ?></h3>
														<?php
														} else {
														?>
														<h3><?php echo $form['form_name']; ?></h3>
														<?php
														}
														?>
													</div>
												</div>
											</div>
										</li>
									<?php
									} else { ?>
										<li data-theme_id="<?php echo noHTML($form['theme_id']); ?>" id="liform_<?php echo noHTML($form['form_id']); ?>" class="form_active form_visible">
											<div class="li-div-wrapper">
												<div style="width:100%;">												
													<div class="form_option la_link_toptabs">
														<a title="View" href="#" class="view-entry-btn" form-id="<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/View.png" style="width:16px;"></a>
														<div class="form_option la_link_move">
															<a title="Entries" href="manage_entries.php?id=<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/Module.png"></a>
														</div>
													</div>
													<div style="height: 0px; clear: both;"></div>
													<div class="middle_form_bar">
														<?php
														if($search_filter_type == "name" && $search_filter_input != ""){
															$pattern = preg_quote($search_filter_input);
														?>
														<h3><?php echo preg_replace("/($pattern)/i", "<span class='highlight'>$1</span>", $form['form_name']) ?></h3>
														<?php
														} else {
														?>
														<h3><?php echo $form['form_name']; ?></h3>
														<?php
														}
														?>
													</div>
												</div>
											</div>
										</li>
									<?php
									}
								}
								?>
							</ul>
						</div>
					</div>
				</li>
			<?php
			}
			?>
			</ul>
		</div>
		<div class="col-md-6 padding-left-10">
		<?php } else {?>
		<div style="width: 100%;">
		<?php }?>		

			<ul id="la_form_list" class="la_form_list">
			<?php
				foreach ($user_forms as $form) {

					$form_display_string = "display: none;";
					if($form['page_number'] == $selected_page_number) {
						$form_display_string = "display: block;";
					}

					if($form['subscribed'] == true){
					?>
						<li data-theme_id="<?php echo noHTML($form['theme_id']); ?>" id="liform_<?php echo noHTML($form['form_id']); ?>" style="<?php echo $form_display_string; ?>">
							<div class="li-div-wrapper">
								<div style="width:100%;">												
									<div class="form_option la_link_toptabs">
										<a title="View" href="#" class="view-entry-btn" form-id="<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/View.png" style="width:16px;"></a>
										<div class="form_option la_link_move">
											<a title="Entries" href="manage_entries.php?id=<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/Module.png"></a>
										</div>
									</div>
									<div style="height: 0px; clear: both;"></div>
									<div class="middle_form_bar">
										<?php
										if($search_filter_type == "name" && $search_filter_input != ""){
											$pattern = preg_quote($search_filter_input);
										?>
										<h3><?php echo preg_replace("/($pattern)/i", "<span class='highlight'>$1</span>", $form['form_name']) ?></h3>
										<?php
										} else {
										?>
										<h3><?php echo $form['form_name']; ?></h3>
										<?php
										}
										?>
									</div>
								</div>
							</div>
						</li>
					<?php
					} else { ?>
						<li data-theme_id="<?php echo noHTML($form['theme_id']); ?>" id="liform_<?php echo noHTML($form['form_id']); ?>" style="<?php echo $form_display_string; ?>">
							<div class="li-div-wrapper">
								<div style="width:100%;">												
									<div class="form_option la_link_toptabs">
										<a a title="View" href="#" class="view-entry-btn" form-id="<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/View.png" style="width:16px;"></a>
										<div class="form_option la_link_move">
											<a title="Entries" href="manage_entries.php?id=<?php echo $form['form_id']; ?>"><img src="images/navigation/FFFFFF/16x16/Module.png"></a>
										</div>
									</div>
									<div style="height: 0px; clear: both;"></div>
									<div class="middle_form_bar">
										<?php
										if($search_filter_type == "name" && $search_filter_input != ""){
											$pattern = preg_quote($search_filter_input);
										?>
										<h3><?php echo preg_replace("/($pattern)/i", "<span class='highlight'>$1</span>", $form['form_name']) ?></h3>
										<?php
										} else {
										?>
										<h3><?php echo $form['form_name']; ?></h3>
										<?php
										}
										?>
									</div>
								</div>
							</div>
						</li>
					<?php
					}
				}
			?>
			</ul>
			<!-- start pagination --> 
				<?php echo $pagination_markup; ?> 
			<!-- end pagination -->
		</div>
	<?php } else { ?>
		<div>
			No form is assigned to your entities.
		</div>
	<?php } ?>
	</section>
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
								<img src="<?php echo $tile["full_path"] ?>" alt="<?php echo $tile["title"]?>" width="100%" height="100%"/>
							<?php } else if (in_array($tile["background_media_type"], $video_types)){ ?>
								<video src="<?php echo $tile["full_path"]; ?>" id="<?=$tile["id"]?>" controls></video>	
							<?php } ?>
						<?php } else { ?>
							<iframe src="<?=$tile["youtube_link"] ? getYoutubeEmbedUrl($tile["youtube_link"]) : ''?>"></iframe>              
						<?php } ?>
					</div>				
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</div>

<div id="dialog-view-entry" title="Create a new entry data or edit the latest version." class="buttons" style="display: none"><img src="images/navigation/005499/50x50/Notice.png"><br>
	<input id="view_form_id" type="hidden" value="">
	<input id="view_form_latest_entry_id" type="hidden" value="">
	<p>Do you want to create a new entry data or edit the latest version?</p>
</div>

<div id="dialog-welcome-message" title="Message" class="buttons" style="display: none"><img alt="" src="<?php echo $portal_login_popup_img_url; ?>" width="300"><br>
	<br>
	<input id="popup_session_value" type="hidden" value="<?php echo $_SESSION['user_login_message_enabled']; ?>">
	<p><?php echo $welcome_message; ?></p>
	<form id="decline_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
		<input type="hidden" name="declined" value="1">
	</form>
</div>

<?php
require('portal-footer.php');
?>

<script type="text/javascript" src="js/highlight.js"></script>
<script type="text/javascript">

	if ( window.history.replaceState ) {
	  	window.history.replaceState( null, null, window.location.href );
	}

	$(function(){
		<?php echo $jquery_data_code; ?>	
    });

	function generateLi(params){
		var _htmlElements = new Array();
		var _forms_elements = params.forms_elements;
		var _showAnc = "";

		if(typeof _forms_elements !== 'undefined'){
			_showAnc = '<a href="javascript:void(0)" class="show-elements" data-show="1" style="float:right; margin:10px;"><img src="images/icons/49_red_16.png" /></a>';

			if(_forms_elements.length){
				_htmlElements.push('<div class="form_element" style="color: rgb(0, 0, 0); margin: 10px 0px 0px 20px; display:none;">'+
				  '<div style="margin:10px 0 10px 10px; width:100%">'+
					'<ul><li><div class="field_listing" style="width:100%;">Field Title</div></li>');

				$.each(_forms_elements, function(i, v){
					_htmlElements.push('<li><div class="field_listing" style="width:100%;"><a href="manage_entries.php?id=' + params.form_id + '&la_page=' + v.element_page_number + '#li_' + (parseInt(v.element_position) + 1) + '">' + v.element_title + '</div></li>');
				});

				_htmlElements.push('</ul>'+
				  '</div>'+
				'<div style="height: 0px; clear: both;"></div></div>');
			}
		}

		var _html = '<li data-theme_id="' + params.theme_id + '" id="liform_' + params.form_id + '" class="form_active form_visible">'+
	      '<div class="middle_form_bar">'+
		    '<a data-show="1" style="color:#FFF" href="manage_entries.php?id=' + params.form_id + '">'+
	          '<h3>' + params.form_name + '</h3>'+
			'</a>'+
			_showAnc+
			'<div style="height: 0px; clear: both;"></div>'+
	      '</div>'+
		  _htmlElements.join('\n')+
		'</li>';

		return _html;
	}

	$(document).ready(function() {
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
		
		$("#dialog-view-entry").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 550,
			draggable: false,
			resizable: false,
			buttons: [
				{	
					text: 'Edit the latest version',
					'class': 'btn_primary_action',
					click: function() {
						form_id = $('#view_form_id').val();
						entry_id = $('#view_form_latest_entry_id').val();
						window.location.href = "view.php?id=" + form_id + "&entry_id=" + entry_id;
					}
				},
				{
					text: 'Create a new entry',
					'class': 'btn_primary_action',
					click: function() {
						form_id = $('#view_form_id').val();
						window.location.href = "pay-to-subscribe.php?id=" + form_id;
					}
				},
				{	
					text: 'Close',
					'class': 'btn_secondary_action',
					click: function() {
						$(this).dialog('close');
					}
				},
			]
		});

		$(document).on('click', '.view-entry-btn', function(e){
			e.preventDefault();
			var form_id = $(this).attr('form-id');
			$.ajax({
				url:"ajax-common-request.php",
				type:"POST",
				async: true,
				data:{
					action: "check_if_form_has_entry",
					form_id: form_id
				},
				cache: false,
				global: false,
				dataType: "json",
				error: function(xhr,text_status,e) {
					window.location.href = "pay-to-subscribe.php?id=" + form_id;
				},
				success: function(response_data) {
					var entry_id = response_data.entry_id;
					if(entry_id == 0) {
						window.location.href = "pay-to-subscribe.php?id=" + form_id;
					} else {
						$('#view_form_id').val(form_id);
						$('#view_form_latest_entry_id').val(entry_id);
						$("#dialog-view-entry").dialog('open');
					}
				}
			});
		});
		if($("#popup_session_value").val() == 1) {
			$("#dialog-welcome-message").dialog('open');
		}
		
		//expand the search box
		$("#filter_form_input").bind('focusin click',function(){
			if($(this).val() == "find form...") {
				$(this).val("");
			}
			$("#la_search_box").animate({'width': '300px'},{duration:200,queue:false}).css('overflow', 'visible', 'important');
			$("#filter_form_input").animate({'width': '265px'},{duration:200,queue:false}).css('overflow', 'visible', 'important');
			$("#la_search_box,#filter_form_input").promise().done(function() {
				$("#la_search_title,#la_search_element,#la_search_replace").slideDown('medium');

				$("#la_search_title,#la_search_element,#la_search_replace").promise().done(function(){
					$("#la_search_box").addClass('search_focused');
				});
			});
		});

		//attach event to 'form title / form elements' tabs
		$("#la_search_title").on('click', function(){
			$("#la_search_element").removeClass('la_pane_selected');
			$("#la_search_replace").removeClass('la_pane_selected');
			$(this).addClass('la_pane_selected');
			$("#search_filter").val("name");
			$("#filter_form_input").focus();
			$("#replace_extra_info").hide();
			return false;
		});

		$("#la_search_element").on('click', function(){
			$("#la_search_title").removeClass('la_pane_selected');
			$("#la_search_replace").removeClass('la_pane_selected');
			$(this).addClass('la_pane_selected');
			$("#search_filter").val("element");
			$("#filter_form_input").focus();
			$("#replace_extra_info").hide();
			return false;
		});

		$("#la_search_replace").on('click', function(){
			$("#la_search_title").removeClass('la_pane_selected');
			$("#la_search_element").removeClass('la_pane_selected');
			$(this).addClass('la_pane_selected');
			$("#search_filter").val("replace");
			$("#filter_form_input").focus();
			$("#replace_extra_info").show();
			return false;
		});

		$(document).on('click', 'a.show-elements', function(){
			var _selector = $(this);

			if(parseInt(_selector.attr('data-show'))){
				_selector.find('img').attr('src', 'images/icons/51_red_16.png');
				_selector.attr('data-show', 0);
			}else{
				_selector.find('img').attr('src', 'images/icons/49_red_16.png');
				_selector.attr('data-show', 1);
			}

			_selector.parent().next().toggle('slow');
		});
	});
	
	$(document).on('click', '#search-go', function(){
		$("#search_form").submit();
	});

	$(document).on('click', ".middle_form_bar", function(){
		var selected_form_li_id = $(this).parents('li').attr('id');
		var tmpVal = selected_form_li_id.split("_");
		//show or hide all the options
		if($(this).hasClass("folder-div")){
			$("#" + selected_form_li_id + ">.li-div-wrapper>.la_link_bottomfolder").slideToggle('medium');
		} else {
			$("#" + selected_form_li_id + " .form_option").slideToggle('medium');
			$("#" + selected_form_li_id + " .form_option").promise().done(function() {
				$(this).parent().toggleClass('form_selected');
			});
		}		
	});

	$(document).on('click', "#la_pagination > li", function(){
		var display_list = $(this).data('liform_list');
		
		$("#la_form_list > li").hide();
		$(display_list).show();
		
		$("#la_pagination > li.current_page").removeClass('current_page');
		$(this).addClass('current_page');
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
