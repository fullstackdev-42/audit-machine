<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/language.php');
require('includes/common-validator.php');
require('includes/helper-functions.php');
require('includes/filter-functions.php');
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');
require('lib/password-hash.php');
require('../common-lib/web3/web3.class.php');

//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$web3Ethereum = new Web3Ethereum();
$hasher = new Sha256Hash();

//get pdf_header_img
$pdf_header_img = 'data:image/' . pathinfo($la_settings["admin_image_url"], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($la_settings["admin_image_url"]));

//Get user information from database table
$admin_id = $_SESSION["la_user_id"];

$active_tab = "personal_info";
if(isset($_GET["active_tab"])) {
	$active_tab = $_GET["active_tab"];
}

//check forced password rule
$forced_password = false;
if(isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1)) {
	$forced_password = true;
}

//get the newly generated password from $_SESSION when the forced password rule is enabled
$generate_password_notification = $_SESSION["generate_password_notification"];
unset($_SESSION["generate_password_notification"]);

//get user information from DB
$query = "SELECT * FROM ".LA_TABLE_PREFIX."users WHERE `user_id`= ?";
$sth = la_do_query($query, array($admin_id), $dbh);
$res = la_do_fetch_result($sth);
if(isset($res)) {
	$my_full_name = la_sanitize($res["user_fullname"]);
	$my_email = la_sanitize($res["user_email"]);
	$my_phone = la_sanitize($res["user_phone"]);
	$my_job_classification = la_sanitize($res["job_classification"]);
	$my_job_title = la_sanitize($res["job_title"]);
	$about_me = $res["about_me"];
	$my_register_date = la_sanitize($res["register_datetime"]);
	$my_avatar_url = $res["avatar_url"];

	if(!file_exists($my_avatar_url)) {
		$my_avatar_url = "avatars/default.png";
	}

	$tmp_my_full_name = la_sanitize($res["user_fullname"]);
	$tmp_my_email = la_sanitize($res["user_email"]);
	$tmp_my_phone = la_sanitize($res["user_phone"]);
	$tmp_my_job_classification = la_sanitize($res["job_classification"]);
	$tmp_my_job_title = la_sanitize($res["job_title"]);
	$tmp_about_me = $res["about_me"];	
}

//Process form submission
if(isset($_POST["form_name"])) {
	if($_POST["form_name"] == "personal_info") {
		//process personal_info_form submission
		$personal_info_error = "";
		if(la_validate_email(array($_POST["my_email"])) !== true){
			$personal_info_error .= "The email address entered is not in correct format.<br>";
		}
		if(la_validate_simple_phone(array($_POST["my_phone"])) !== true){
			$personal_info_error .= la_validate_simple_phone(array($_POST["my_phone"]))."<br>";
		}

		//check if new email is available
		$query = "SELECT count(user_email) total_user FROM `".LA_TABLE_PREFIX."users` WHERE user_email = ? AND user_id != ?";
		$sth = la_do_query($query, array($_POST["my_email"], $admin_id), $dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
			$personal_info_error .= 'This email address is already in use.';
		}

		if($personal_info_error != "") {
			//keep the data user entered and display errors
			$tmp_my_full_name = $_POST["my_full_name"];
			$tmp_my_email = $_POST["my_email"];
			$tmp_my_phone = $_POST["my_phone"];
			$tmp_my_job_classification = $_POST["my_job_classification"];
			$tmp_my_job_title = $_POST["my_job_title"];
			$tmp_about_me = $_POST["about_me"];
		} else {
			//save the user info and refresh the page
			$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `user_fullname` = ?, `user_email` = ?, `user_phone` = ?, `job_classification` = ?, `job_title` = ?, `about_me` = ? WHERE `user_id`= ?";
			la_do_query($query_update, array($_POST["my_full_name"], $_POST["my_email"], $_POST["my_phone"], $_POST["my_job_classification"], $_POST["my_job_title"], $_POST["about_me"], $admin_id), $dbh);
			$_POST = array();
			$_SESSION['LA_SUCCESS'] = 'Your personal info has been updated successfully.';
			header("Location: my_account.php");
			exit;
		}
	}
	
	if($_POST["form_name"] == "change_password") {
		//process change_password_form submission
		if(isset($_POST["forced_password"]) && $_POST["forced_password"] == true) {
			$password_range = range(15, 20);
			$password_length = array_rand(array_flip($password_range));
			$new_password = randomPassword($password_length);

			$new_password_hash = $hasher->HashPassword($new_password);

			$query = "UPDATE ".LA_TABLE_PREFIX."users SET user_password = ? WHERE user_id = ?";
			la_do_query($query, array($new_password_hash, $admin_id), $dbh);
			$_SESSION["generate_password_notification"] = "Your new password is <b>".$new_password."</b>";
			$_SESSION['LA_SUCCESS'] = 'Your password has been updated successfully.';
			header("Location: my_account.php?active_tab=change_password");
			exit;
		}
		if(isset($_POST["forced_password"]) && $_POST["forced_password"] == false) {

			$change_password_error = "";
			$newpassword = $_POST["my_password_confirm"];
			if(strlen($newpassword) < 8){
				$change_password_error .= "The new password must be a minimum of 8 characters.<br>";
			} else if( !preg_match("#[0-9]+#", $newpassword) ) {
				$change_password_error .= "The new password must include at least one number.<br>";
			} else if ( !preg_match("#[a-z]+#", $newpassword) ) {
				$change_password_error .= "The new password must include at least one letter.<br>";
			} else if ( !preg_match("#[A-Z]+#", $newpassword) ) {
				$change_password_error .= "The new password must include at least one capital letter.<br>";
			} else if ( !preg_match("#\W+#", $newpassword) ) {
				$change_password_error .= "The new password must include at least one symbol.<br>";
			}
			//get last 14 passwords used by user
			$query_old_passwords = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=1 order by `id` DESC limit 14";
			
			$sth_old_passwords = la_do_query($query_old_passwords, array($admin_id), $dbh);
			$old_passwords = [];
			
			while($row_old_passwords = la_do_fetch_result($sth_old_passwords)){
				$old_passwords[]  = $hasher->CheckPassword($newpassword, $row_old_passwords['password']);
			}

			if( in_array(1, $old_passwords) ) {
				$change_password_error .= "The new password must be different from your former 14 passwords.<br>";
			}

			if($change_password_error != "") {
				//keep the password entered and display errors
				$tmp_my_password = $_POST["my_password"];
				$tmp_my_password_confirm = $_POST["my_password_confirm"];
			} else {
				//save password and refresh the page
				$new_password_hash = $hasher->HashPassword($newpassword);
				$query = "UPDATE ".LA_TABLE_PREFIX."users SET user_password = ? WHERE user_id = ?";
				la_do_query($query, array($new_password_hash, $admin_id), $dbh);
				insert_old_password_hash($admin_id, $new_password_hash, 1, $dbh);
				$_POST = array();
				$_SESSION['LA_SUCCESS'] = 'Your password has been updated successfully.';
				header("Location: my_account.php?active_tab=change_password");
				exit;
			}
		}
	}

	if($_POST["form_name"] == "reset_mfa") {
		$user_id = $_SESSION["la_user_id"];
		$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `tsv_enable` = ?, `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `user_id`= ?";
		la_do_query($query_update, array(0, "", "", 0, $user_id), $dbh);

		unset($_SESSION["email"]);
		unset($_SESSION["la_user_id"]);
		unset($_SESSION["la_logged_in"]);
		unset($_SESSION["la_tsv_setup_secret"]);
		unset($_SESSION["la_user_privileges"]);		
		$_SESSION['LA_LOGIN_ERROR'] = "Please log in again to set up the new multi-factor authentication.";
		header("Location: index.php");
		exit;
	}
}
$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/Croppic/assets/css/croppic.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
$current_nav_tab = 'manage_users';
require('includes/header.php');
?>
<div id="content" class="full">
	<div class="post my_profile">
		<div class="content_header">
			<div class="content_header_title">
				<div>
				  	<h2>My Profile</h2>
				  	<p>Please update any information regarding your business.</p>
				</div>
			</div>
		</div>
		<?php la_show_message(); ?>
		<div class="content_body">
			<div class="profile gradient_blue">
				<div class="profile-sidebar portlet light">
					<div class="profile-userpic">
						<input id="my_user_id" type="hidden" value="<?php echo $admin_id; ?>">
						<img src="<?php echo $my_avatar_url; ?>">
						<div id="profile_image_upload"></div>
					</div>
					<div class="profile-username">
						<div class="profile-full-name">
							<?php echo $my_full_name; ?>
						</div>
						<div class="profile-job-title">
							<?php echo $my_job_title; ?>
						</div>
						<div class="profile-register-date">
							Member Since <?php echo date("m-d-Y", $my_register_date); ?>
						</div>
					</div>
					<div class="profile-usermenu">
						<ul class="nav" id="tabs" active-tab="<?php echo $active_tab; ?>">
							<li class="tab-item personal_info_tab">
								<a href="#personal_info">
									<i class="fas fa-user"></i> Personal Info </a>
							</li>
							<li class="tab-item change_password_tab">
								<a href="#change_password">
									<i class="fas fa-key"></i> Change Password </a>
							</li>
							<li class="tab-item reset_mfa_tab">
								<a href="#reset_mfa">
									<i class="fas fa-key"></i> Reset MFA </a>
							</li>
							<li class="tab-item my_activity_tab">
								<a href="#my_activity">
									<i class="fas fa-file-alt"></i> My Activity </a>
							</li>
							<?php if(!empty($_SESSION['is_examiner'])) {?>
								<li class="tab-item entity_info_tab">
									<a href="#entity_info">
										<i class="fas fa-user"></i> My Entities </a>
								</li>
							<?php } ?>
						</ul>
					</div>
				</div>
				<div class="profile-content portlet light">
					<div id="personal_info" class="tab-panel" style="display: none;">
						<div class="tab-panel-header">
							<span>Personal Info</span>
						</div>
						<div class="tab-panel-content">
							<form id="personal_info_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=personal_info'; ?>" method="post">
								<div class="form-group">
									<label class="control-label" for="my_full_name">Full Name<span class="required">*</span></label>
		            				<input id="my_full_name" class="form-control" type="text" name="my_full_name" value="<?php echo $tmp_my_full_name; ?>" placeholder="John Doe">
								</div>
								<div class="form-group">
									<label class="control-label" for="my_email">Email Address<span class="required">*</span></label>
		            				<input id="my_email" class="form-control" type="text" name="my_email" value="<?php echo $tmp_my_email; ?>" placeholder="example@mail.com">
								</div>
								<div class="form-group">
									<label class="control-label" for="my_phone">Phone Number<span class="required">*</span></label>
		            				<input id="my_phone" class="form-control" type="text" name="my_phone" value="<?php echo $tmp_my_phone; ?>" placeholder="(888) 896-6207">
								</div>
								<div class="form-group">
									<label class="control-label" for="my_job_classification">Job Classification</label>
		            				<input id="my_job_classification" class="form-control" type="text" name="my_job_classification" value="<?php echo $tmp_my_job_classification; ?>" placeholder="IT, Security, Finance etc">
								</div>
								<div class="form-group">
									<label class="control-label" for="my_job_title">Job Title</label>
		            				<input id="my_job_title" class="form-control" type="text" name="my_job_title" value="<?php echo $tmp_my_job_title; ?>" placeholder="Security Expert">
								</div>
								<div class="form-group">
									<label class="control-label" for="about_me">About Me</label>
		            				<textarea id="about_me" class="form-control" rows="3" name="about_me" placeholder="I am a security expert."><?php echo $tmp_about_me; ?></textarea>
								</div>
								<div class="form-group">
									<p class="error"><?php echo $personal_info_error; ?></p>
								</div>
								<div class="form-group">
									<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
									<input type="hidden" name="form_name" value="personal_info">
									<button id="personal_info_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Changes </button>
								</div>
							</form>
						</div>
					</div>
					<div id="change_password" class="tab-panel" style="display: none;">
						<div class="tab-panel-header">
							<span>Change Password</span>
						</div>
						<div class="tab-panel-content">
							<?php if($forced_password) {?>
								<form id="generate_password_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=change_password'; ?>" method="post">
									<div class="form-group">
										<p class="notification"><b>Forced Password Rule</b> is enabled. The system will generate your password automatically.</p>
									</div>
									<div class="form-group">
										<p class="error"><?php echo $generate_password_notification; ?></p>
									</div>
									<div class="form-group">
										<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
										<input type="hidden" name="form_name" value="change_password">
										<input type="hidden" name="forced_password" value="<?php echo $forced_password; ?>">
										<button id="generate_password_btn" class="bb_button bb_small bb_green">Generate Password</button>
									</div>
								</form>
							<?php } else { ?>						
								<form id="change_password_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=change_password'; ?>" method="post">
									<div class="form-group">
										<label class="control-label" for="">New Password<span class="required">*</span></label>
										<div style="position: relative;">
											<icon toggle="my_password" class="fas fa-eye toggle-password"></icon>
											<input id="my_password" class="form-control" type="password" name="my_password" value="<?php echo $tmp_my_password; ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label" for="">Confirm Password<span class="required">*</span></label>
										<div style="position: relative;">
											<icon toggle="my_password_confirm" class="fas fa-eye toggle-password"></icon>
											<input id="my_password_confirm" class="form-control" type="password" name="my_password_confirm" value="<?php echo $tmp_my_password_confirm; ?>">
										</div>
									</div>
									<div class="form-group">
										<p class="error"><?php echo $change_password_error; ?></p>
									</div>
									<div class="form-group">
										<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
										<input type="hidden" name="form_name" value="change_password">
										<input type="hidden" name="forced_password" value="<?php echo $forced_password; ?>">
										<button id="change_password_btn" class="bb_button bb_small bb_green">Change Password</button>
									</div>
								</form>
							<?php } ?>
						</div>
					</div>
					<div id="reset_mfa" class="tab-panel" style="display: none;">
						<div class="tab-panel-header">
							<span>Reset Multi-Factor Authentication</span>
						</div>
						<div class="tab-panel-content">
							<form id="reset_mfa_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=reset_mfa'; ?>" method="post">
								<div class="form-group">
									<p class="notification">You will be signed out and asked to set up new multi-factor authentication during your next login.</p>
								</div>
								<div class="form-group">
									<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
									<input type="hidden" name="form_name" value="reset_mfa">
									<button id="reset_mfa_btn" class="bb_button bb_small bb_green">Reset MFA</button>
								</div>
							</form>
						</div>
					</div>
					<div id="my_activity" class="tab-panel" style="display: none;">
						<div class="tab-panel-header">
							<span>My Activity</span>
							<div class="actions">
								<button toggle="div_audit_log" class="btn-activity active">Audit Log</button>
								<button toggle="div_uploaded_files_log" class="btn-activity">Uploaded Files Document Log</button>
								<button toggle="div_session_log" class="btn-activity">User Session Log</button>
							</div>
						</div>
						<div class="tab-panel-content">
							<input id="pdf_header_img" type="hidden" value="<?php echo $pdf_header_img; ?>">
							<div id="div_audit_log" class="activity-div">
								<table id="audit_log_table" data-table-name="Continuum GRC User Audit Log" class="data-table hover stripe cell-border" style="width: 100%;">
									<thead>
										<tr>
							              	<th>#</th>
							              	<th>Form #</th>
							              	<th>Audit Log</th>
							              	<th>Datetime</th>
							            </tr>
									</thead>
									<tbody>
									<?php
										//get audit log list from DB
										$i = 0;
										$query = "SELECT `al`.*, `aat`.`action_type`, `f`.`form_name` FROM ".LA_TABLE_PREFIX."audit_log `al` left join `".LA_TABLE_PREFIX."audit_action_type` `aat` on (`aat`.`action_type_id` = `al`.`action_type_id`) left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = ? and `al`.`action_type_id` != ? order by `al`.`action_datetime`";
										$result = la_do_query($query, array($admin_id, 6), $dbh);
										while ($row = la_do_fetch_result($result)) {
											$i++;
												if($row['action_type_id'] == 7 || $row['action_type_id'] == 8){
											?>
												<tr>
									              	<td><?php echo $i; ?></td>
									              	<td><?php echo $row['form_id']; ?></td>
									              	<td><div><?php echo $my_email; ?> <?php echo $row['action_text']; ?></div></td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
											<?php
												}elseif($row['action_type_id'] == 9 || $row['action_type_id'] == 10 || $row['action_type_id'] == 11 || $row['action_type_id'] == 12 || $row['action_type_id'] == 13){
											?>
												<tr>
									              	<td><?php echo $i; ?></td>
									              	<td>-</td>
									              	<td>
									              		<div>
									              			<?php
									              				$action_text_array = json_decode($row['action_text']);
									              				if( isset($action_text_array->action_performed_on) ) {
									              					echo $my_email.' <strong>'.$row['action_type'].'</strong> '.$action_text_array->action_performed_on;	
									              				} else {
									              					echo $action_text_array->action_performed_by.' <strong>'.$row['action_type'].'</strong> '.$my_email;
									              				}
									              			?>
									              		</div>
									              	</td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
									        <?php
												}elseif($row['action_type_id'] == 14 || $row['action_type_id'] == 15 || $row['action_type_id'] == 16){
													$file_name = substr($row['action_text'], strpos($row['action_text'], '-') +1 );
											?>
												<tr>
									              	<td><?php echo $i; ?></td>
									              	<td><?php echo $row['form_id']; ?></td>
									              	<td><div><?php echo $my_email; ?> <?php echo $row['action_type']; ?> <strong><?=$file_name?></strong>  Form #<?php echo $row['form_id']; ?></div></td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
									        <?php
												}elseif($row['action_type_id'] == 17){
											?>
									            <tr>
									              	<td><?php echo $i; ?></td>
									              	<td>-</td>
									              	<td><div><?php echo $my_email; ?> was forced to log out for the reason of declining assistance with access to IT Audit Machine.</div></td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
											<?php
												}elseif($row['action_type_id'] == 18){
											?>
									            <tr>
									              	<td><?php echo $i; ?></td>
									              	<td><?php echo $row['form_id']; ?></td>
									              	<td><div><?php echo $row['action_text']; ?></div></td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
											<?php
												}else{
												  	$action_text = '';
												  	if( !empty($row['action_text']) && strrpos($row['action_text'], 'Session') !== false )
												  		$action_text = "({$row['action_text']})";
											?>
									            <tr>
									              	<td><?php echo $i; ?></td>
									              	<td><?php echo $row['form_id']; ?></td>
									              	<td><div><?php echo $my_email; ?> <strong><?=$row['action_type']?></strong> Form #<?php echo $row['form_id']; ?> <?=$action_text?></div></td>
									              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
									            </tr>
									        <?php		  
												}
										}
									?>
									</tbody>
								</table>
							</div>
							<div id="div_uploaded_files_log" class="activity-div" style="display: none">
								<table id="audit_uploaded_files_table" data-table-name="Continuum GRC Uploaded Files Document Log" class="data-table hover stripe cell-border" style="width: 100%;">
									 <thead>
							            <tr>
							              	<th>#</th>
							              	<th>Form #</th>
							              	<th>File Name</th>
							              	<th>Datetime</th>
							              	<th>Added to Chain</th>
							              	<th>Hash Matched</th>
							            </tr>
							        </thead>
							        <tbody>
							        	<?php
							        		$query = "SELECT `al`.*, `f`.`form_name` FROM ".LA_TABLE_PREFIX."eth_file_data `al` left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = ? AND `al`.`is_portal` = ? order by `al`.`id` ASC";
					  						$result = la_do_query($query, array($admin_id, 0), $dbh);
					  						$i = 0;
										  	while($row = la_do_fetch_result($result)){
											  	$i++;
											  	$row_id = $row['id'];
											  	$form_id = $row['form_id'];
											  	$form_name = $row['form_name'];
											  	$entry_id = $row['entry_id'];
											  	$date = date("m-d-Y H:i", $row['action_datetime']);

											  	$full_file_name = $row['data'];
								              	$file_name = substr($full_file_name,strpos($full_file_name, '-')+1);
										?>
								            <tr>
								              	<td><?php echo $i; ?></td>
								              	<td><?=$form_id?></td>
								              	<td><?=$file_name?></td>
								              	<td><?=$date?></td>
								              	<td>
								              		<?php
								              			switch ($row['added_to_chain']) {
														    case 0:
														        echo "Pending";
														        break;
														    case 1:
														        echo "Added";
														        break;
														    case 2:
														        echo "Error";
														        break;
														}
								              		?>						              		
								              	</td>
								            	<td>
								            		<?php
								            			$form_dir = $la_settings['upload_dir']."/form_{$form_id}/files";
														$file_location = $form_dir.'/'.$full_file_name;
														//if file does not exist on server show error image
								            			if( file_exists ( $file_location ) ) {
								            				if( !empty($entry_id) ) {
								            					$file_hash = hash_file ( "sha256", $file_location );
									            				$result_chain = $web3Ethereum->call('getEntryByDocumentHash','0x'.$file_hash); 
									            				// $result_chain['documentHash'] = '';
																if( !empty($result_chain['documentHash']) ) {
																	echo "<img src=\"images/Checkmark-icon.png\">";
																} else {
																	echo "<img src=\"images/cancel.png\">";
																}
									            			}
									            		} else {
								            				echo "<img src=\"images/cancel.png\">";
								            			}
								            		?>
								            	</td>
								            </tr>
								        <?php
											}
										?>
							        </tbody>
								</table>
							</div>
							<div id="div_session_log" class="activity-div" style="display: none">
								<table id="audit_session_log_table" data-table-name="Continuum GRC User Session Log" class="data-table hover stripe cell-border" style="width: 100%;">
									 <thead>
							            <tr>
							              	<th>#</th>
							              	<th>Log In</th>
							              	<th>Log Out</th>
							              	<th>Session Time</th>
							            </tr>
							        </thead>
							        <tbody>
							        	<?php
							        		$query = "SELECT * FROM ".LA_TABLE_PREFIX."user_sessions WHERE user_id = ? AND is_admin = ? ORDER BY id ASC";
					  						$result = la_do_query($query, array($admin_id, 1), $dbh);
					  						$i = 0;
										  	while($row = la_do_fetch_result($result)){
											  	$i++;
										?>
											<tr>
												<td><?php echo $i; ?></td>
												<td>
													<?php
														if( !empty($row['login_time']) ) {
															echo date("m-d-Y H:i", $row['login_time']);
														} else {
															echo "-";
														}
													?>
												</td>
												<td>
													<?php
														if( !empty($row['logout_time']) ) {
															echo date("m-d-Y H:i", $row['logout_time']);
														} else {
															echo "-";
														}
													?>
												</td>
												<td>
													<?php
														$login_time = $row['login_time'];
														$logout_time = $row['logout_time'];
														if( !empty($logout_time) ) {
															$then = date("m/d/Y H:i:s", $login_time);
															$now = date("m/d/Y H:i:s", $logout_time);
															
															$then = new DateTime($then);
															$now = new DateTime($now);
															
															$sinceThen = $then->diff($now);

															echo $sinceThen->format('%i minutes %s seconds');
														}
													?>
												</td>
											</tr>
								        <?php
											}
										?>
							        </tbody>
								</table>
							</div>
						</div>
					</div>
					<?php if(!empty($_SESSION['is_examiner'])) {?>
						<div id="entity_info" class="tab-panel" style="display: none;">
							<div class="tab-panel-header">
								<span>My Entities</span>
							</div>
							<div class="tab-panel-content">
								<table id="entity_table" class="hover stripe cell-border" style="width: 100%;">
									<thead>
										<tr>
											<th>#</th>
											<th>Entity Name</th>
											<th>Contact Email</th>
											<th>Contact Phone Number</th>
											<th>Contact Name</th>
											<th>Entity Description</th>
										</tr>
									</thead>
									<tbody>
									<?php
										$query_entity = "SELECT `e`.* FROM ".LA_TABLE_PREFIX."ask_clients AS `e` JOIN ".LA_TABLE_PREFIX."entity_examiner_relation AS `r` ON `e`.`client_id` = `r`.`entity_id` WHERE `r`.`user_id` = ?";
										$sth_entity = la_do_query($query_entity, array($admin_id), $dbh);
										while ($row_entity = la_do_fetch_result($sth_entity)) {
									?>
										<tr entity-id="<?php echo $row_entity['client_id']; ?>">
											<td class="action-view"><?php echo $row_entity["client_id"]; ?></td>
											<td class="action-view"><?php echo $row_entity["company_name"]; ?></td>
											<td class="action-view"><?php echo $row_entity["contact_email"]; ?></td>
											<td class="action-view"><?php echo $row_entity["contact_phone"]; ?></td>
											<td class="action-view"><?php echo $row_entity["contact_full_name"]; ?></td>
											<td class="action-view"><?php echo $row_entity["entity_description"]; ?></td>
										</tr>
									<?php
										}
									?>	
									</tbody>
								</table>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<!-- /end of content_body --> 
	</div>
  	<!-- /.post --> 
</div>
<!-- /#content -->
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Something went wrong. Please try again later.</p>
</div>
<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/Croppic/croppic.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/my_account.js"></script>
EOT;
require('includes/footer.php');
?>