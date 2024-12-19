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
	require('includes/check-session.php');
	require('includes/filter-functions.php');
	require('includes/users-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	}

	//get active tab
	$active_tab = $_GET["active_tab"];
	if(!isset($active_tab)) {
		$active_tab = 'admin_tab';
	}
	//check current license usage, if this is Standard or Professional
	$is_user_max = 0;
	if($la_settings['license_key'][0] == 'S' || $la_settings['license_key'][0] == 'P'){
		$query = "SELECT count(user_id) user_total FROM ".LA_TABLE_PREFIX."users WHERE `status` > 0 AND `is_examiner` = 0";
		
		$params = array();
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$current_total_user = $row['user_total'];

		if($la_settings['license_key'][0] == 'S'){
			$max_user = 2;
		}else if($la_settings['license_key'][0] == 'P'){
			$max_user = 21;
		}

		if($current_total_user >= $max_user){
			$is_user_max = 1;
		}

		$total_user_left = $max_user - $current_total_user;
	}

$header_data = <<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/main.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
	
	$current_nav_tab = 'manage_users';
	require('includes/header.php');	
?>
<style type="text/css">
	div.dataTables_wrapper div.dataTables_filter {
		float: left;
		text-align: left;
		margin-top: 6px;
	}
	td {
		text-align: center;
		vertical-align: middle;
	}
	ul.ui-tabs-nav {
		background: #0085CC;
		padding: 8px!important;
		margin-bottom: 25px!important;
	}
	li.ui-tabs-active {
		border: none!important;
		background: #505356!important;
	}
</style>
<div id="content" class="full">
	<div class="post manage_users">
		<div class="content_header">
			<div class="content_header_title">
				<div>
					<h2>User Management</h2>
					<p>Create, edit and manage administrative users, portal entities and portal users.<span style="margin-left: 20px;"><img id="loader-img" src="images/loader_small_grey.gif"></span></p>
				</div>
			</div>
		</div>
		<?php la_show_message(); ?>
		<div class="content_body">
			<div id="table_settings" style="display: none;">
				<input id="add_admin_flag" type="hidden" value="<?php echo 1-$is_user_max; ?>">
				<input id="active_tab" type="hidden" value="<?php echo $active_tab?>">
			</div>
			<div id="tabs" style="display: none">
				<ul>
					<li class="custom-tabs-tab"><a href="#admin_tab">Administrative Users</a></li>
					<li class="custom-tabs-tab"><a href="#examiner_tab">Examiner Users</a></li>
					<li class="custom-tabs-tab"><a href="#entity_tab">Portal Entities</a></li>
					<li class="custom-tabs-tab"><a href="#user_tab">Portal Users</a></li>
				</ul>
				<div id="admin_tab">
					<table id="admin_table" class="hover stripe cell-border nowrap" style="width: 100%;">
						<thead>
							<tr>
								<th>#</th>
								<th>Name</th>
								<th>Email Address</th>
								<th>Phone Number</th>
								<th>Job Classification</th>
								<th>Job Title</th>
								<th>More Info</th>
								<th>Privileges</th>
								<th>Status</th>
								<th>Member Since</th>
								<th>Suspend/Unblock</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$query = "SELECT `user_id`,	`user_fullname`, `user_email`, `user_phone`, `avatar_url`, `job_title`, `job_classification`, `about_me`, `register_datetime`,
								`tstamp`,
								if(`priv_administer`=1,'Administrator','') `priv_administer`,
								CASE
									WHEN `status` = 1 THEN 'Active'
									WHEN `status` = 2 THEN 'Suspended'
									WHEN `status` = 3 THEN 'Invited'
								END AS `status` FROM ".LA_TABLE_PREFIX."users WHERE `is_examiner` = 0";
							$sth = la_do_query($query, array(), $dbh);
							while($row = la_do_fetch_result($sth)){
								if($row["status"] == "Suspended") {
									$status_class = "mu_suspended";
									$sus_ele = '<a class="action-unblock" title="Unblock" href="#"><img src="images/navigation/ED1C2A/16x16/Unlock.png"></a>';
									$date_created = date("m-d-Y", $row["register_datetime"]);
									$admin_status = $row["status"];
								} else if($row["status"] == "Active") {
									$status_class = "mu_active";
									$sus_ele = '<a class="action-suspend" title="Suspend" href="#"><img src="images/navigation/ED1C2A/16x16/Suspend.png"></a>';
									$date_created = date("m-d-Y", $row["register_datetime"]);
									$admin_status = $row["status"];
								} else if($row["status"] == "Invited") {
									$status_class = "mu_invited";
									$sus_ele = "";
									$date_created = date("m-d-Y", $row["tstamp"]);
									$admin_status = $row["status"]."</br><button class='bb_button bb_small bb_green resend-invitation'>Resend Invitation</button>";
								}
						?>
							<tr admin-id="<?php echo $row['user_id']; ?>">
								
							<?php
							if($row["user_id"] == 1) {
							?>
								<td class="action-view"><?php echo $row["user_id"]; ?></td>
								<td class="action-view"><img class="avatar" src="<?php echo $la_settings['base_url'].$row['avatar_url']; ?>"><div class="username"><?php echo $row["user_fullname"]."(Main)"; ?></div></td>
								<td class="action-view"><?php echo $row["user_email"]; ?></td>
								<td class="action-view"><?php echo $row["user_phone"]; ?></td>
								<td class="action-view"><?php echo $row["job_classification"]; ?></td>
								<td class="action-view"><?php echo $row["job_title"]; ?></td>
								<td class="action-view"><?php echo $row["about_me"]; ?></td>
								<td class="action-view"><b><?php echo $row["priv_administer"]."(Main)"; ?></b></td>
								<td class="<?php echo $status_class; ?>"><?php echo $admin_status; ?></td>
								<td class="action-view"><?php echo $date_created; ?></td>
								<td></td>
								<td></td>
							<?php
							} else {
							?>
								<td class="action-view"><?php echo $row["user_id"]; ?></td>
								<td class="action-view"><img class="avatar" src="<?php echo $la_settings['base_url'].$row['avatar_url']; ?>"><div class="username"><?php echo $row["user_fullname"]; ?></div></td>
								<td class="action-view"><?php echo $row["user_email"]; ?></td>
								<td class="action-view"><?php echo $row["user_phone"]; ?></td>
								<td class="action-view"><?php echo $row["job_classification"]; ?></td>
								<td class="action-view"><?php echo $row["job_title"]; ?></td>
								<td class="action-view"><?php echo $row["about_me"]; ?></td>
								<td class="action-view"><?php echo $row["priv_administer"] ?></td>
								<td class="<?php echo $status_class; ?>"><?php echo $admin_status; ?></td>
								<td class="action-view"><?php echo $date_created; ?></td>
								<td><?php echo $sus_ele; ?></td>
								<td><a class="action-delete" title="Delete" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a></td>
							<?php
							}
							?>
							</tr>
						<?php
							}
						?>
						</tbody>
					</table>
				</div>
				<div id="examiner_tab">
					<table id="examiner_table" class="hover stripe cell-border nowrap" style="width: 100%;">
						<thead>
							<tr>
								<th>#</th>
								<th>Name</th>
								<th>Email Address</th>
								<th>Assigned Entities</th>
								<th>Phone Number</th>
								<th>Job Classification</th>
								<th>Job Title</th>
								<th>More Info</th>								
								<th>Status</th>
								<th>Member Since</th>
								<th>Suspend/Unblock</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$query = "SELECT `user_id`,	`user_fullname`, `user_email`, `user_phone`, `avatar_url`, `job_title`, `job_classification`, `about_me`, `register_datetime`,
								`tstamp`,
								CASE
									WHEN `status` = 1 THEN 'Active'
									WHEN `status` = 2 THEN 'Suspended'
									WHEN `status` = 3 THEN 'Invited'
								END AS `status` FROM ".LA_TABLE_PREFIX."users WHERE `is_examiner` = 1";
							$sth = la_do_query($query, array(), $dbh);
							while($row = la_do_fetch_result($sth)) {
								if($row["status"] == "Suspended") {
									$status_class = "mu_suspended";
									$sus_ele = '<a class="action-unblock" title="Unblock" href="#"><img src="images/navigation/ED1C2A/16x16/Unlock.png"></a>';
									$date_created = date("m-d-Y", $row["register_datetime"]);
									$examiner_status = $row["status"];
								} else if($row["status"] == "Active") {
									$status_class = "mu_active";
									$sus_ele = '<a class="action-suspend" title="Suspend" href="#"><img src="images/navigation/ED1C2A/16x16/Suspend.png"></a>';
									$date_created = date("m-d-Y", $row["register_datetime"]);
									$examiner_status = $row["status"];
								} else if($row["status"] == "Invited") {
									$status_class = "mu_invited";
									$sus_ele = "";
									$date_created = date("m-d-Y", $row["tstamp"]);
									$examiner_status = $row["status"]."</br><button class='bb_button bb_small bb_green resend-invitation'>Resend Invitation</button>";
								}
								//get assigned entities
								$entity_permission = '';
								$query_entity = "SELECT DISTINCT `e`.`company_name` FROM ".LA_TABLE_PREFIX."ask_clients AS `e` JOIN ".LA_TABLE_PREFIX."entity_examiner_relation AS `r` ON `e`.`client_id` = `r`.`entity_id` WHERE `r`.`user_id` = ?";
								$sth_entity = la_do_query($query_entity, array($row['user_id']), $dbh);
								while ($row_entity = la_do_fetch_result($sth_entity)) {
									$entity_permission .= '<div><span class="vu_checkbox">'.$row_entity['company_name'].'</span></div><div>';
								}
						?>
							<tr examiner-id="<?php echo $row['user_id']; ?>">
								<td class="action-view"><?php echo $row["user_id"]; ?></td>
								<td class="action-view"><img class="avatar" src="<?php echo $la_settings['base_url'].$row['avatar_url']; ?>"><div class="username"><?php echo $row["user_fullname"]; ?></div></td>
								<td class="action-view"><?php echo $row["user_email"]; ?></td>
								<td class="action-view"><?php echo $entity_permission; ?></td>
								<td class="action-view"><?php echo $row["user_phone"]; ?></td>
								<td class="action-view"><?php echo $row["job_classification"]; ?></td>
								<td class="action-view"><?php echo $row["job_title"]; ?></td>
								<td class="action-view"><?php echo $row["about_me"]; ?></td>
								<td class="<?php echo $status_class; ?>"><?php echo $examiner_status; ?></td>
								<td class="action-view"><?php echo $date_created; ?></td>
								<td><?php echo $sus_ele; ?></td>
								<td><a class="action-delete" title="Delete" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a></td>
							</tr>
						<?php
							}
						?>
						</tbody>
					</table>
				</div>
				<div id="entity_tab">
					<table id="entity_table" class="hover stripe cell-border" style="width: 100%;">
						<thead>
							<tr>
								<th>#</th>
								<th>Entity Name</th>
								<th>Contact Email</th>
								<th>Contact Phone Number</th>
								<th>Contact Name</th>
								<th>Entity Description</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients";
							$sth = la_do_query($query, array(), $dbh);
							while($row = la_do_fetch_result($sth)){
						?>
							<tr entity-id="<?php echo $row['client_id']; ?>">
								<td class="action-view"><?php echo $row["client_id"]; ?></td>
								<td class="action-view"><?php echo $row["company_name"]; ?></td>
								<td class="action-view"><?php echo $row["contact_email"]; ?></td>
								<td class="action-view"><?php echo $row["contact_phone"]; ?></td>
								<td class="action-view"><?php echo $row["contact_full_name"]; ?></td>
								<td class="action-view"><?php echo $row["entity_description"]; ?></td>
								<td><a class="action-delete" title="Delete" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a></td>
							</tr>
						<?php
							}
						?>	
						</tbody>
					</table>
				</div>
				<div id="user_tab">
					<table id="user_table" class="hover stripe cell-border display nowrap" style="width: 100%;">
						<thead>
							<tr>
								<th>#</th>
								<th>Name</th>
								<th>Entity</th>
								<th>Username</th>
								<th>Email Address</th>
								<th>Phone Number</th>
								<th>Job Classification</th>
								<th>Job Title</th>
								<th>More Info</th>
								<th>Status</th>
								<th>Member Since</th>
								<th>Suspend/Unblock</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$query = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users`";
							$sth = la_do_query($query, array(), $dbh);
							while($row = la_do_fetch_result($sth)){
								$companyNameStr     = getOtherEntityNames($dbh, $row["client_user_id"], $row["client_id"]);
								$companyNameArr     = explode("||==||", $companyNameStr);
								$primary_entity_name = getEntityName($dbh, $row["client_id"]);
								
								if($primary_entity_name == ''){
									$entities = '<div><span class="vu_checkbox">'.implode('</span></div><div><span class="vu_checkbox">', $companyNameArr).'</span></div>';
								} else {
									$entities = '<div><span class="vu_checkbox" style="color:red;">'.$primary_entity_name.'(Main Entity)</span></div><div><span class="vu_checkbox">'.implode('</span></div><div><span class="vu_checkbox">', $companyNameArr).'</span></div>';
								}
								
								if($row['is_invited'] == 1) {
									$view_class = "action-view";
									$status_class = "mu_invited";
									$status = 'Invited'.'</br><button class="bb_button bb_small bb_green resend-invitation">Resend Invitation</button>';
									$sus_ele = '';
									$date_created = date("m-d-Y", $row["tstamp"]);
								} else {
									$date_created = date("m-d-Y", $row["register_datetime"]);
									if($row['status'] == 0) {
										$view_class = "action-view";
										$status_class = "mu_active";
										$status = 'Active';
										$sus_ele = '<a class="action-suspend" title="Suspend" href="#"><img src="images/navigation/ED1C2A/16x16/Suspend.png"></a>';
									} else if($row['status'] == 1) {
										$view_class = "action-view";
										$status_class = "mu_suspended";
										$status = 'Suspended';
										$sus_ele = '<a class="action-unblock" title="Unblock" href="#"><img src="images/navigation/ED1C2A/16x16/Unlock.png"></a>';
									}
								}
						?>
							<tr user-id="<?php echo $row['client_user_id']; ?>">
								<td class="<?php echo $view_class; ?>"><?php echo $row["client_user_id"]; ?></td>
								<td class="<?php echo $view_class; ?>"><img class="avatar" src="<?php echo $la_settings['base_url'].$row['avatar_url']; ?>"><div class="username"><?php echo $row["full_name"]; ?></div></td>
								<td class="<?php echo $view_class; ?>"><?php echo $entities; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["username"]; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["email"]; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["phone"]; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["job_classification"]; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["job_title"]; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $row["about_me"]; ?></td>
								<td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
								<td class="<?php echo $view_class; ?>"><?php echo $date_created; ?></td>
								<td><?php echo $sus_ele; ?></td>
								<td><a class="action-delete" title="Delete" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a></td>
							</tr>
						<?php
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<!-- /end of content_body --> 
	</div>
	<!-- /.post --> 
</div>
<!-- /#content -->
<div id="dialog-success" title="Success" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/005499/50x50/Success.png" />
	<p id="dialog-success-msg"> Success </p>
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-confirm-admin-delete" title="Are you sure you want to delete selected admin?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="admin_delete_id">
	<p id="dialog-confirm-admin-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-admin-delete-info">The admin will be deleted permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-admin-suspend" title="Are you sure you want to suspend selected admin?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="admin_suspend_id">
	<p id="dialog-confirm-admin-suspend-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-admin-suspend-info">The admin will be suspended permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-admin-unblock" title="Are you sure you want to unblock selected admin?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="admin_unblock_id">
	<p id="dialog-confirm-admin-unblock-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-admin-unblock-info">The admin will be unblocked and have access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-examiner-delete" title="Are you sure you want to delete selected examiner?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="examiner_delete_id">
	<p id="dialog-confirm-examiner-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-examiner-delete-info">The examiner will be deleted permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-examiner-suspend" title="Are you sure you want to suspend selected examiner?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="examiner_suspend_id">
	<p id="dialog-confirm-examiner-suspend-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-examiner-suspend-info">The examiner will be suspended permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-examiner-unblock" title="Are you sure you want to unblock selected examiner?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="examiner_unblock_id">
	<p id="dialog-confirm-examiner-unblock-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-examiner-unblock-info">The examiner will be unblocked and have access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-entity-delete" title="Are you sure you want to delete selected entity?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="entity_delete_id">
	<p id="dialog-confirm-entity-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-entity-delete-info">The entity will be deleted permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-user-delete" title="Are you sure you want to delete selected user?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="user_delete_id">
	<p id="dialog-confirm-user-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-user-delete-info">The user will be deleted permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-user-suspend" title="Are you sure you want to suspend selected user?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="user_suspend_id">
	<p id="dialog-confirm-user-suspend-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-user-suspend-info">The user will be suspended permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<div id="dialog-confirm-user-unblock" title="Are you sure you want to unblock selected user?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="user_unblock_id">
	<p id="dialog-confirm-user-unblock-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-user-unblock-info">The user will be unblocked and have access to IT Audit Machine.</strong><br/><br/>
	</p>
</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/manage_users.js"></script>
EOT;
	require('includes/footer.php');
?>