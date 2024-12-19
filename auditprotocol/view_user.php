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
	require('lib/swift-mailer/swift_required.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	require('includes/users-functions.php');
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$user_id = (int) trim($_GET['id']);
	$nav 	 = trim($_GET['nav']);

	if(empty($user_id)){
		die("Invalid Request");
	}

	if(isset($_POST['cron_settings'])){
		$account_suspension_strict_date_flag = isset($_POST['account_suspension_strict_date_flag']) ? true : false;
		$account_suspension_strict_date = (isset($_POST['account_suspension_strict_date']) && !empty($_POST['account_suspension_strict_date'])) ? strtotime($_POST['account_suspension_strict_date']) : 0;
		$account_suspension_inactive_flag = isset($_POST['account_suspension_inactive_flag']) ? true : false;
		$account_suspension_inactive = (isset($_POST['account_suspension_inactive']) && !empty($_POST['account_suspension_inactive'])) ? $_POST['account_suspension_inactive'] : 0;
		$suspended_account_auto_deletion_flag = isset($_POST['suspended_account_auto_deletion_flag']) ? true : false;
		$account_suspended_deletion = (isset($_POST['account_suspended_deletion']) && !empty($_POST['account_suspended_deletion'])) ? $_POST['account_suspended_deletion'] : 0;
		
		//compare the old and new data, if they are different, email account management alert notification
		if($la_settings['enable_registration_notification']){
			$changed_content = "";
			$query_get_old_data = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
			$sth_get_old_data = la_do_query($query_get_old_data, array($user_id), $dbh);
			$row_old_data = la_do_fetch_result($sth_get_old_data);
			if($row_old_data['account_suspension_strict_date_flag'] != $account_suspension_strict_date_flag){
				if($row_old_data['account_suspension_strict_date_flag'] == 0){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".date('m/d/Y', $account_suspension_strict_date)."</td></tr>";
				} else {
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>".date('m/d/Y', $row_old_data['account_suspension_strict_date'])."</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
				}
			} else {
				if($row_old_data['account_suspension_strict_date_flag'] == 1){
					if($row_old_data['account_suspension_strict_date'] != $account_suspension_strict_date){
						$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>".date('m/d/Y', $row_old_data['account_suspension_strict_date'])."</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".date('m/d/Y', $account_suspension_strict_date)."</td></tr>";
					}
				}
			}
			if($row_old_data['account_suspension_inactive_flag'] != $account_suspension_inactive_flag){
				if($row_old_data['account_suspension_inactive_flag'] == 0){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspension_inactive." day(s)</td></tr>";
				} else {
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspension_inactive']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
				}
			} else {
				if($row_old_data['account_suspension_inactive_flag'] == 1){
					if($row_old_data['account_suspension_inactive'] != $account_suspension_inactive){
						$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspension_inactive']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspension_inactive." day(s)</td></tr>";
					}
				}
			}
			if($row_old_data['suspended_account_auto_deletion_flag'] != $suspended_account_auto_deletion_flag){
				if($row_old_data['suspended_account_auto_deletion_flag'] == 0){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspended_deletion." day(s)</td></tr>";
				} else {
					$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspended_deletion']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
				}
			} else {
				if($row_old_data['suspended_account_auto_deletion_flag'] == 1){
					if($row_old_data['account_suspended_deletion'] != $account_suspended_deletion){
						$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspended_deletion']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspended_deletion." day(s)</td></tr>";
					}
				}
			}

			if($changed_content != ""){
				$login_user = $_SESSION['email'];
				$site_name = "https://".$_SERVER['SERVER_NAME'];
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				$content .= "<h3>Administrative user ".$login_user." has edited an admin setting of ".$row_old_data['user_email']." in ".$site_name.".</h3>";
				$content .= "<hr/>";
				$content .= "<h3>Changed Information:</h3>";
				$content .= "<table>";
				$content .= $changed_content;
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}
		}
		
		$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `account_suspension_strict_date_flag` = :account_suspension_strict_date_flag, `account_suspension_strict_date` = :account_suspension_strict_date, `account_suspension_inactive_flag` = :account_suspension_inactive_flag, `account_suspension_inactive` = :account_suspension_inactive, `suspended_account_auto_deletion_flag` = :suspended_account_auto_deletion_flag, `account_suspended_deletion` = :account_suspended_deletion WHERE `user_id` = '{$user_id}'";
		
		la_do_query($query_update,array(':account_suspension_strict_date_flag' => $account_suspension_strict_date_flag, ':account_suspension_strict_date' => $account_suspension_strict_date, ':account_suspension_inactive_flag' => $account_suspension_inactive_flag, ':account_suspension_inactive' => $account_suspension_inactive, ':suspended_account_auto_deletion_flag' => $suspended_account_auto_deletion_flag, ':account_suspended_deletion' => $account_suspended_deletion),$dbh);
		$_SESSION["LA_SUCCESS"] = "Admin setting has been updated successfully.";
		header("location:view_user.php?id={$user_id}");
		exit();
	}

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: restricted.php");
		exit;
	}	

	//add logic for 50% Rule on Passwords
	$forced_password = false;
	$newpassword = '';
	if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) ){
		$forced_password = true;
		$newpassword = 'someValueForJs:)';
	}
	
	//if there is "nav" parameter, we need to determine the correct entry id and override the existing user_id
	if(!empty($nav)){
		$exclude_admin = false;

		$all_user_id_array = la_get_filtered_users_ids($dbh,$_SESSION['filter_users'],$exclude_admin);
		$user_key = array_keys($all_user_id_array,$user_id);
		$user_key = $user_key[0];

		if($nav == 'prev'){
			$user_key--;
		}else{
			$user_key++;
		}

		$user_id = $all_user_id_array[$user_key];

		//if there is no user_id, fetch the first/last member of the array
		if(empty($user_id)){
			if($nav == 'prev'){
				$user_id = array_pop($all_user_id_array);
			}else{
				$user_id = $all_user_id_array[0];
			}
		}
	}

	//get user information
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."users WHERE user_id=? and `status` > 0";
	$params = array($user_id);
			
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$user_profile = $row;

	$account_suspension_strict_date 		= $user_profile['account_suspension_strict_date'];
	$account_suspension_strict_date_flag 	= $user_profile['account_suspension_strict_date_flag'];
	$account_suspension_inactive 			= $user_profile['account_suspension_inactive'];
	$account_suspension_inactive_flag 		= $user_profile['account_suspension_inactive_flag'];
	$account_suspended_deletion 			= $user_profile['account_suspended_deletion'];
	$suspended_account_auto_deletion_flag 	= $user_profile['suspended_account_auto_deletion_flag'];

	$is_examiner = $user_profile['is_examiner'];
	if($is_examiner) {

	} else {
		//if this user is admin, all privileges should be available
		if(!empty($user_profile['priv_administer'])){
			$user_profile['priv_new_forms'] = 1;
			$user_profile['priv_new_themes'] = 1;
		}

		$is_user_suspended = false;
		if($user_profile['status'] == 2){
			$is_user_suspended = true;
		}	
		
		$privileges = array();
		if(!empty($user_profile['priv_new_forms'])){
			$privileges[] = 'Able to <strong>create new forms</strong>';
		}
		if(!empty($user_profile['priv_new_themes'])){
			$privileges[] = 'Able to <strong>create new themes</strong>';
		}

		$user_is_admin = false;

		if(!empty($user_profile['priv_administer'])){
			if($user_id == 1){
				$privileges[] = 'Able to <strong>administer IT Audit Machine</strong> (Main Administrator)';
			}else{
				$privileges[] = 'Able to <strong>administer IT Audit Machine</strong>';
			}
			$user_is_admin = true;
		}

		//get form permissions data
		$query = "SELECT 
						A.form_id,
						A.edit_form,
						A.edit_entries,
						A.view_entries,
						B.form_name
				    FROM
				   		".LA_TABLE_PREFIX."permissions A LEFT JOIN ".LA_TABLE_PREFIX."forms B on A.form_id=B.form_id
				   WHERE 
				   		A.user_id = ? and (B.form_active=0 or B.form_active=1)
				ORDER BY 
						B.form_name ASC";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$permissions_data = array();
		$i=0;
		while($row = la_do_fetch_result($sth)){ 
			if(!empty($row['form_name'])){		
				$permissions_data[$i]['form_name'] = $row['form_name'];
			}else{
				$permissions_data[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
			}

			$permissions_data[$i]['edit_form'] 	  = $row['edit_form'];
			$permissions_data[$i]['edit_entries'] = $row['edit_entries'];
			$permissions_data[$i]['view_entries'] = $row['view_entries'];

			$i++;
		}
		
		if($i >= 15){
			$perm_style =<<<EOT
<style>
	.me_center_div { padding-left: 10px; }
</style>
EOT;
		}
	}	

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
{$perm_style}
EOT;

	$current_nav_tab = 'manage_users';
	require('includes/header.php');	
?>

<style type="text/css">
	.dialog-form input:read-only {
		background: #DDDDDD;
	}
</style>
<div id="content" class="full">
	<div class="post view_user">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left">
					<h2><a class="breadcrumb" href='manage_users.php'>Users Manager</a>
						<img src="images/icons/resultset_next.gif" />
						#<?php echo $user_id; ?>
					</h2>
				</div>
				<?php if($user_id != "1") { ?>
					<div style="float: right;margin-right: 5px"> <a href="#" id="button_save_notification" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Admin Setting </a> </div>
				<?php } ?>
				<div style="clear: both; height: 1px"></div>
			</div>
			
		</div>

		<?php la_show_message(); ?>

		<div class="content_body">
			<div id="vu_details" data-userid="<?php echo $user_id; ?>">
				<div id="vu_profile">
					<div class="vu_avatar">
						<img src="<?php echo $user_profile['avatar_url']; ?>" width="70px;">
					</div>
					<div class="vu_info">
						<h2 class="vu_userfullname">
							<?php 
								echo htmlspecialchars($user_profile['user_fullname']);
								if($is_examiner) {
									echo " (Examiner)";
								} else {
									echo " (Administrative User)";
								}
							?>
						</h2>
						<h5 class="vu_email"><?php echo htmlspecialchars($user_profile['user_email']); ?></h5>
						<input id="is_examiner" type="hidden" name="is_examiner" value="<?php echo $is_examiner; ?>">
					</div>
					<?php
						if(!empty($user_profile['last_login_date']) && !empty($user_profile['last_ip_address'])){
							echo '<div id="vu_log">Last login <strong>'.la_short_relative_date($user_profile['last_login_date']).'</strong> from <strong>'.$user_profile['last_ip_address'].'</strong></div>';
						}
						
						if($is_user_suspended){
							echo '<div id="vu_suspended">This user is currently being <span>SUSPENDED</span></div>';
						}
					?>
				</div>
				<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_privileges">
					<tbody>		
						<tr>
					  	    <td>
					  	    	<div class="vu_title">
					  	    		Privileges
					  	    	</div>
					  	    </td>
					  	</tr> 
						<?php
							if(!empty($privileges)){
								$i = 2;
								foreach ($privileges as $priv_title) {
									$class_tag = '';
									if($i % 2 == 0){
										$class_tag = 'class="alt"';
									}
									echo '<tr '.$class_tag.'><td><span class="vu_checkbox">'.$priv_title.'</span></td></tr>';
									$i++;
								}
							}else{
						?>
							<tr class="alt">
						  	    <td><span class="vu_nopriv">This user has <strong>no privileges</strong> to create new forms, themes or administer IT Audit Machine.</span></td>
						  	</tr>
					  	<?php } ?>
					</tbody>
				</table>
				<?php
					if($is_examiner) {
						$entity_array = array();
						$query_entity = "SELECT DISTINCT `e`.`company_name` FROM ".LA_TABLE_PREFIX."ask_clients AS `e` JOIN ".LA_TABLE_PREFIX."entity_examiner_relation AS `r` ON `e`.`client_id` = `r`.`entity_id` WHERE `r`.`user_id` = ?";
						$sth_entity = la_do_query($query_entity, array($user_id), $dbh);
						while ($row_entity = la_do_fetch_result($sth_entity)) {
							array_push($entity_array, $row_entity["company_name"]);
						}
					?>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_perm_header">
							<tbody>
								<tr>
							  	    <td>
							  	    	<div class="vu_title">
							  	    		Assigned Entities
							  	    	</div>
							  	    </td>
							  	</tr> 
								<?php
									if(!empty($entity_array)){
										$i = 2;
										foreach ($entity_array as $value) {
											$class_tag = '';
											if($i % 2 == 0){
												$class_tag = 'class="alt"';
											}
											echo '<tr '.$class_tag.'><td><span class="vu_checkbox">'.$value.'</span></td></tr>';
											$i++;
										}
									}else{
								?>
									<tr class="alt">
								  	    <td><span class="vu_nopriv">This user has <strong>no access</strong> to any entities.</span></td>
								  	</tr>
							  	<?php } ?>
							</tbody>
						</table>
					<?php
					} else {
						if($user_is_admin === true){ ?>
							<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_perm_header">
									<tbody>		
										<tr>
									  	    <td>
									  	    	<div class="vu_title">
									  	    		Permissions
									  	    	</div>
									  	    </td>
									  	</tr>
									  	<tr class="alt">
									  	    <td>
									  	    	<span class="vu_checkbox">This user has <strong>full permission</strong> to all forms and entries.</span>
									  	    </td>
									  	</tr> 
									</tbody>
							</table>
						<?php 
						} else {
							if(!empty($permissions_data)){
						?>	
								<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_perm_header">
										<tbody>		
											<tr>
										  	    <td>
										  	    	<div class="vu_title">
										  	    		Permissions
										  	    	</div>
										  	    </td>
										  		<td class="vu_permission_header" width="75px">Edit Form</td>
										  		<td class="vu_permission_header" width="75px">Edit Entries</td>
										  		<td class="vu_permission_header" width="75px">View Entries</td>
										  	</tr> 
										</tbody>
								</table>

								<div id="vu_permission_container">
									<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_perm_body" style="margin-top: 0px">
										<tbody>		
											<?php
												$i = 2;
												$checkmark_tag = '<div class="me_center_div"><img align="absmiddle" style="vertical-align: middle" src="images/icons/62_blue_16.png"></div>';

												foreach ($permissions_data as $value) {
													$class_tag = '';
													if($i % 2 == 0){
														$class_tag = 'class="alt"';
													}
												
											?>
													<tr <?php echo $class_tag; ?>>
												  	    <td><div class="vu_perm_title"><?php echo htmlspecialchars($value['form_name']); ?></div></td>
												  	    <td width="75px"><?php if(!empty($value['edit_form'])){ echo $checkmark_tag; }else{ echo '&nbsp;'; }; ?></td>
												  	    <td width="75px"><?php if(!empty($value['edit_entries'])){ echo $checkmark_tag; }else{ echo '&nbsp;'; }; ?></td>
												  	    <td width="75px"><?php if(!empty($value['view_entries'])){ echo $checkmark_tag; }else{ echo '&nbsp;'; }; ?></td>
												  	</tr>

										  	<?php 
										  			$i++;
										  		} 
										  	?>
										  	
										</tbody>
									</table>
								</div>
							<?php 
							} else {
							?>
								<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_perm_header">
									<tbody>
										<tr>
									  	    <td>
									  	    	<div class="vu_title">
									  	    		Permissions
									  	    	</div>
									  	    </td>
									  	</tr>
									  	<tr class="alt">
									  	    <td>
									  	    	<span class="vu_nopriv">This user has <strong>no permission</strong> to any forms or entries.</span>
									  	    </td>
									  	</tr> 
									</tbody>
								</table>
						<?php
							}
						}
					}
				?>

				<?php if(!empty($user_profile['tsv_enable'])){ ?>
				<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_security">
					<tbody>		
						<tr>
					  	    <td>
					  	    	<div class="vu_title">
					  	    		Security
					  	    	</div>
					  	    </td>
					  	</tr>
					  	<tr class="alt">
						  	<td><span class="vu_checkbox">Multi-Factor Authentication <strong>enabled</strong></span></td>
						</tr> 
					</tbody>
				</table>
				<?php } ?>
				<?php if($user_id != "1") { ?>
				<div style="margin: 20px;">
			        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $user_id; ?>" method="post" id="cron-setting-form">
			          <div style="display:none;">
			            <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
			          </div>
			          <ul id="ns_main_list">
			            <li>
			              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
			                <div class="ns_box_title">
			                  <input type="checkbox" value="1" class="checkbox" id="account_suspension_strict_date_flag" name="account_suspension_strict_date_flag" <?php echo ($account_suspension_strict_date_flag == 1) ? 'checked' : ''; ?>>
			                  <label for="account_suspension_strict_date_flag" class="choice">Automatically suspend account after date:</label>
			                  <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--> 
			                </div>
			                <div class="ns_box_email" id="strict-date" <?php echo ($account_suspension_strict_date_flag == 1) ? '' : 'style="display:none;"'; ?>>
			                  <label class="description" for="esl_email_address">Select Date: <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--></label>
			                  <span><input style="width:20px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("m", $account_suspension_strict_date) : ''; ?>" maxlength="2" size="2" class="element text" id="mm"></span>/
			                  <span><input style="width:20px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("d", $account_suspension_strict_date) : ''; ?>" maxlength="2" size="2" class="element text" id="dd"></span>/
			                  <span><input style="width:35px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("Y", $account_suspension_strict_date) : ''; ?>" maxlength="4" size="4" class="element text" id="yyyy"></span>
			                  <input type="hidden" id="account_suspension_strict_date_hidden" name="account_suspension_strict_date" value="<?php echo ($account_suspension_strict_date > 0) ? date("m/d/Y", $account_suspension_strict_date) : ''; ?>">
			                  <span style="display: none;"> &nbsp;<img id="cal_img_5" class="datepicker" src="images/calendar.gif" alt="Pick a date." /></span>
			                </div>
			              </div>
			            </li>
			            <li>&nbsp;</li>
			            <li>
			              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
			                <div class="ns_box_title">
			                  <input type="checkbox" value="1" class="checkbox" id="account_suspension_inactive_flag" name="account_suspension_inactive_flag" <?php echo ($account_suspension_inactive_flag == 1) ? 'checked' : ''; ?>>
			                  <label for="account_suspension_inactive_flag" class="choice">Automatically suspend account for inactivity after:</label>
			                  <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--> 
			                </div>
			                <div class="ns_box_email" id="inactive-day" <?php echo ($account_suspension_inactive_flag == 1) ? '' : 'style="display:none;"'; ?>>
			                  <label class="description" for="esl_email_address">Number of days: <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--></label>
			                  <input id="account_suspension_inactive" name="account_suspension_inactive" class="element text medium" value="<?php echo $account_suspension_inactive; ?>" type="text">
			                </div>
			              </div>
			            </li>
			            <li>&nbsp;</li>
			            <li>
			              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
			                <div class="ns_box_title">
			                  <input type="checkbox" value="1" class="checkbox" id="suspended_account_auto_deletion_flag" name="suspended_account_auto_deletion_flag" <?php echo ($suspended_account_auto_deletion_flag == 1) ? 'checked' : ''; ?>>
			                  <label for="suspended_account_auto_deletion_flag" class="choice">Automatically delete account for inactivity after:</label>
			                </div>
			                <div class="ns_box_email" id="inactive-delete-day" <?php echo ($suspended_account_auto_deletion_flag == 1) ? '' : 'style="display:none;"'; ?>>
			                  <label class="description" for="esl_email_address">Number of days: </label>
			                  <input id="account_suspended_deletion" name="account_suspended_deletion" class="element text medium" value="<?php echo $account_suspended_deletion; ?>" type="text">
			                </div>
			              </div>
			            </li>
			          </ul>
			          <input type="hidden" name="cron_settings" value="1">
			        </form>
			    </div>
			    <?php } ?>
                <div class="vu_title" style="margin-top: 20px;">
                    <a href="list_audit_log.php?user_id=<?php echo base64_encode($user_id); ?>">Audit Log</a> | 
                    <a href="uploaded_document_log.php?user_id=<?php echo base64_encode($user_id); ?>&is_portal=0">Uploaded Files Document Log</a> | 
                    <a href="list_user_session_log.php?user_id=<?php echo base64_encode($user_id); ?>&is_portal=0">User Session Log</a>
                </div>
			</div>
			<div id="ve_actions">
				<div id="ve_entry_navigation">
					<a href="<?php echo "view_user.php?id={$user_id}&nav=prev"; ?>" title="Previous User">
						<img src="images/navigation/005499/24x24/Back.png">
					</a>
					<a href="<?php echo "view_user.php?id={$user_id}&nav=next"; ?>" title="Next User" style="margin-left: 5px">
						<img src="images/navigation/005499/24x24/Forward.png">
					</a>
				</div>
				<?php if($user_id == 1 && $_SESSION['la_user_id'] != 1){ ?>
				
				<?php }else{ ?>
				<div id="ve_entry_actions">
					<ul>
						<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_edit" title="Edit User" href="<?php if($is_examiner){echo "edit_examiner.php?id={$user_id}";} else {echo "edit_user.php?id={$user_id}";} ?>"><img src="images/navigation/005499/16x16/Edit.png">Edit</a></li>
						<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_password" title="Change Password" href="#"><img src="images/navigation/005499/16x16/My_account.png">Password</a></li>
						<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_reset_mfa" title="Reset Multi-Factor Authentication" href="#"><img src="images/navigation/005499/16x16/My_account.png">Reset MFA</a></li>
						<?php if($user_id != 1){ ?>
						<?php
							if($is_user_suspended){
								echo '<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" class="unsuspend" title="Un-Suspend User" href="#"><img src="images/navigation/005499/16x16/Unlock.png">Unblock</a></li>';
							}else{
								echo '<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" title="Suspend User" href="#"><img src="images/navigation/005499/16x16/Suspend.png ">Suspend</a></li>';
							}
						?>
						<li><a id="vu_action_delete" title="Delete User" href="#"><img src="images/navigation/005499/16x16/Delete.png">Delete</a></li>
						<?php } ?>
					</ul>
				</div>
				<?php } ?>
			</div>
		</div> <!-- /end of content_body -->	
	
	</div><!-- /.post -->
</div><!-- /#content -->
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Something went wrong. Please try again later.</p>
</div>

<div id="dialog-confirm-user-delete" title="Are you sure you want to delete this user?" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
	<p id="dialog-confirm-user-delete-msg">
		This action cannot be undone.<br/>
		<strong id="dialog-confirm-user-delete-info">The user will be deleted and blocked.</strong><br/><br/>		
	</p>
</div>

<div id="dialog-change-password" title="Change User Password" class="buttons" style="display: none"> 
	<form id="dialog-change-password-form" class="dialog-form" style="margin-bottom: 10px">
		<p id="change-password-notifications" style="display: none;"></p>
		<ul>
			<li>
				<?php if( $forced_password ) { ?>
					<p style="margin-top:15px;"><b>50% Rule on Passwords</b> is enabled. The password will be generated automatically. Click Update Password to generate new password for user.</p>
					<style type="text/css">
						.not_forced {
							display: none !important;
						}
					</style>
				<?php } ?>

				<label for="dialog-change-password-input1" class="description not_forced">Enter New Password *</label>
				<input type="password" id="dialog-change-password-input1" name="dialog-change-password-input1" class="text large not_forced" value="<?=$newpassword?>" <?php if($forced_password) { echo "readonly"; }?>>
				<label for="dialog-change-password-input2" style="margin-top: 15px" class="description not_forced">Confirm New Password *</label>
				<input type="password" id="dialog-change-password-input2" name="dialog-change-password-input2" class="text large not_forced" value="<?=$newpassword?>" <?php if($forced_password) { echo "readonly"; }?>>
				<span style="display: block;margin-top: 10px">
					<input type="checkbox"  value="1" class="checkbox" id="dialog-change-password-send-login" name="dialog-change-password-send-login" style="margin-left: 0px">
					<label for="dialog-change-password-send-login" class="choice change-password">Send password change notification to user</label>
				</span>
				<p class="not_forced" style="margin-top:15px;">* The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.</p>
			</li>
		</ul>
	</form>
</div>

<div id="dialog-password-changed" title="Success!" class="buttons" style="display: none">
	<img src="images/navigation/005499/50x50/Success.png" title="Success" /> 
	<p id="dialog-password-changed-msg"></p>
</div>

<div id="dialog-reset-mfa" title="Reset Multi-Factor Authentication" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
	<p id="dialog-reset-mfa-msg">
		This action cannot be undone.<br/>
		<strong id="dialog-reset-mfa-info">The user will be asked to set up new multi-factor authentication during their next login.</strong><br/><br/>
	</p>
</div>
<?php
	
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.ext.js"></script>
<script type="text/javascript" src="js/view_user.js"></script>
EOT;

	require('includes/footer.php'); 
?>
