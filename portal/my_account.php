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
require('lib/swift-mailer/swift_required.php');
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

if(isset($_REQUEST['delete'])){
	$query = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ? AND `is_invited` = '1'";
	la_do_query($query, array($_REQUEST['invited_user_id']), $dbh);
	$_SESSION['LA_SUCCESS'] = 'User has been deleted successfully.';
	header("Location: my_account.php?active_tab=my_entity");
}
//get pdf_header_img
$pdf_header_img = 'data:image/' . pathinfo($la_settings["admin_image_url"], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($la_settings["admin_image_url"]));

//Get user information from database table
$user_id = $_SESSION["la_client_user_id"];
$client_id = $_SESSION['la_client_client_id'];

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

//get other entities that user is assigned to
$my_other_entities = getOtherEntityNames($dbh, $user_id, $client_id);

//get user information from DB
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ?";
$sth = la_do_query($query, array($user_id), $dbh);
$res = la_do_fetch_result($sth);
if(isset($res)) {
	$my_full_name = la_sanitize($res["full_name"]);
	$my_email = la_sanitize($res["email"]);
	$my_username = la_sanitize($res["username"]);
	$my_phone = la_sanitize($res["phone"]);
	$my_job_classification = la_sanitize($res["job_classification"]);
	$my_job_title = la_sanitize($res["job_title"]);
	$about_me = $res["about_me"];
	$my_register_date = la_sanitize($res["register_datetime"]);
	$is_admin = la_sanitize($res["is_admin"]);
	$my_avatar_url = "../auditprotocol/".la_sanitize($res["avatar_url"]);

	if(!file_exists($my_avatar_url)) {
		$my_avatar_url = "../auditprotocol/avatars/default.png";
	}
	$my_account_suspension_strict_date = "";
	$my_account_suspension_inactive = "";
	$my_account_deletion_inactive = "";
	if($res["account_suspension_strict_date_flag"] == 1 && $res["account_suspension_strict_date"] > time()) {
		$my_account_suspension_strict_date = date("m-d-Y", $res['account_suspension_strict_date']);
	}
	if($res["account_suspension_inactive_flag"] == 1 && $res["account_suspension_inactive"] > 0) {
		if($la_settings["enable_account_suspension_inactive"] == 1 && $la_settings["account_suspension_inactive"] > 0) {
			$my_account_suspension_inactive = min($res["account_suspension_inactive"], $la_settings["account_suspension_inactive"]);
		} else {
			$my_account_suspension_inactive = $res["account_suspension_inactive"];
		}
	} else {
		if($la_settings["enable_account_suspension_inactive"] == 1 && $la_settings["account_suspension_inactive"] > 0) {
			$my_account_suspension_inactive = $la_settings["account_suspension_inactive"];
		}
	}
	if($res["suspended_account_auto_deletion_flag"] == 1 && $res["account_suspended_deletion"] > 0) {
		if($la_settings["enable_account_deletion_inactive"] == 1 && $la_settings["account_deletion_inactive"] > 0) {
			$my_account_deletion_inactive = min($res["account_suspended_deletion"], $la_settings["account_deletion_inactive"]);
		} else {
			$my_account_deletion_inactive = $res["account_suspended_deletion"];
		}
	} else {
		if($la_settings["enable_account_deletion_inactive"] == 1 && $la_settings["account_deletion_inactive"] > 0) {
			$my_account_deletion_inactive = $la_settings["account_deletion_inactive"];
		}
	}

	$tmp_my_full_name = la_sanitize($res["full_name"]);
	$tmp_my_email = la_sanitize($res["email"]);
	$tmp_my_username = la_sanitize($res["username"]);
	$tmp_my_phone = la_sanitize($res["phone"]);
	$tmp_my_job_classification = la_sanitize($res["job_classification"]);
	$tmp_my_job_title = la_sanitize($res["job_title"]);
	$tmp_about_me = $res["about_me"];	
}

//get entity information from DB
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id`= ?";
$sth = la_do_query($query, array($client_id), $dbh);
$res = la_do_fetch_result($sth);
if(isset($res)) {
	$entity_name = la_sanitize($res["company_name"]);
	$entity_email = la_sanitize($res["contact_email"]);
	$entity_phone = la_sanitize($res["contact_phone"]);
	$entity_full_name = la_sanitize($res["contact_full_name"]);
	$entity_description = $res["entity_description"];

	$tmp_entity_name = la_sanitize($res["company_name"]);
	$tmp_entity_email = la_sanitize($res["contact_email"]);
	$tmp_entity_phone = la_sanitize($res["contact_phone"]);
	$tmp_entity_full_name = la_sanitize($res["contact_full_name"]);
	$tmp_entity_description = $res["entity_description"];
}

//get the latest signature information from DB
$query = "SELECT * FROM ".LA_TABLE_PREFIX."digital_signatures WHERE `id`=(SELECT MAX(id) FROM ".LA_TABLE_PREFIX."digital_signatures WHERE user_id=?)";
$sth = la_do_query($query, array($user_id), $dbh);
$res = la_do_fetch_result($sth);

if (isset($res)) {
	$signer_full_name = la_sanitize($res["signer_full_name"]);
	$signature_type = la_sanitize($res["signature_type"]);
	$signature_data = la_sanitize($res["signature_data"]);
	$signature_id = la_sanitize($res["signature_id"]);
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
		if(la_validate_username(array($_POST["my_username"])) !== true){
			$personal_info_error .= "The username must consist of alphanumeric characters with or without underscores.<br>";
		}

		//check if new email is available
		$query = "SELECT count(email) total_user FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email = ? AND client_user_id != ?";
		$sth = la_do_query($query, array($_POST["my_email"], $user_id), $dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
			$personal_info_error .= 'This email address is already in use.';
		}

		if($personal_info_error != "") {
			//keep the data user entered and display errors
			$tmp_my_full_name = $_POST["my_full_name"];
			$tmp_my_email = $_POST["my_email"];
			$tmp_my_username = $_POST["my_username"];
			$tmp_my_phone = $_POST["my_phone"];
			$tmp_my_job_classification = $_POST["my_job_classification"];
			$tmp_my_job_title = $_POST["my_job_title"];
			$tmp_about_me = $_POST["about_me"];
		} else {
			//save the user info and refresh the page
			$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `full_name` = ?, `email` = ?, `phone` = ?, `username` = ?, `job_classification` = ?, `job_title` = ?, `about_me` = ? WHERE `client_user_id`= ?";
			la_do_query($query_update, array($_POST["my_full_name"], $_POST["my_email"], $_POST["my_phone"], $_POST["my_username"], $_POST["my_job_classification"], $_POST["my_job_title"], $_POST["about_me"], $user_id), $dbh);
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

			$query = "UPDATE ".LA_TABLE_PREFIX."ask_client_users SET password = ?, password_change_date = ? WHERE client_user_id = ?";
			la_do_query($query, array($new_password_hash, time(), $user_id), $dbh);
			$_SESSION["generate_password_notification"] = "Your new password is <b>".$new_password."</b>";
			$_POST = array();
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
			$query_old_passwords = "SELECT password FROM ".LA_TABLE_PREFIX."old_password_hash WHERE user_id=? and is_admin=0 order by `id` DESC limit 14";
			
			$sth_old_passwords = la_do_query($query_old_passwords, array($user_id), $dbh);
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
				$query = "UPDATE ".LA_TABLE_PREFIX."ask_client_users SET password = ?, password_change_date = ? WHERE client_user_id = ?";
				la_do_query($query, array($new_password_hash, time(), $user_id), $dbh);
				insert_old_password_hash($user_id, $new_password_hash, 0, $dbh);
				$_POST = array();
				$_SESSION['LA_SUCCESS'] = 'Your password has been updated successfully.';
				header("Location: my_account.php?active_tab=change_password");
				exit;
			}
		}
	}

	if($_POST["form_name"] == "my_entity") {
		//process my_entity_form submission
		$my_entity_error = "";
		if($_POST["entity_email"] != "") {
			if(la_validate_email(array($_POST["entity_email"])) !== true){
				$my_entity_error .= "The email address entered is not in correct format.<br>";
			}
		}
		if($_POST["entity_phone"] != "") {			
			if(la_validate_simple_phone(array($_POST["entity_phone"])) !== true){
				$my_entity_error .= la_validate_simple_phone(array($_POST["entity_phone"]))."<br>";
			}
		}

		if($my_entity_error != "") {
			//keep the data user entered and display errors
			$tmp_entity_name = la_sanitize($_POST["entity_name"]);
			$tmp_entity_email = la_sanitize($_POST["entity_email"]);
			$tmp_entity_phone = la_sanitize($_POST["entity_phone"]);
			$tmp_entity_full_name = la_sanitize($_POST["entity_full_name"]);
			$tmp_entity_description = $_POST["entity_description"];
		} else {
			//save the user info and refresh the page
			$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_clients` SET `company_name` = ?, `contact_email` = ?, `contact_phone` = ?, `contact_full_name` = ?, `entity_description` = ? WHERE `client_id`= ?";
			la_do_query($query_update, array($_POST["entity_name"], $_POST["entity_email"], $_POST["entity_phone"], $_POST["entity_full_name"], $_POST["entity_description"], $client_id), $dbh);
			$_POST = array();
			$_SESSION['LA_SUCCESS'] = 'Your entity info has been updated successfully.';
			header("Location: my_account.php?active_tab=my_entity");
			exit;
		}
	}

	if($_POST["form_name"] == "invite_user") {
		//process personal_info_form submission
		$invite_user_error = "";
		if(la_validate_email(array($_POST["user_email"])) !== true){
			$invite_user_error .= "The email address entered is not in correct format.<br>";
		}

		//check if new email is available
		$query = "SELECT count(email) total_user FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email = ?";
		$sth = la_do_query($query, array($_POST["user_email"]), $dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
			$invite_user_error .= 'This email address is already in use.';
		}

		if($invite_user_error != "") {
			//keep the data user entered and display errors
			$user_full_name = $_POST["user_full_name"];
			$user_email = $_POST["user_email"];
		} else {
			//insert user, send invitation email, refresh the page
			$query = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users` (`client_id`, `email`, `full_name`, `is_invited`, `tstamp`) VALUES (?, ?, ?, ?, ?);";
			$params = array($client_id, $_POST["user_email"], $_POST["user_full_name"], 1, $_SERVER['REQUEST_TIME']);
			la_do_query($query,$params,$dbh);
			$new_user_id = (int) $dbh->lastInsertId();
			//send add admin user notification
			if($la_settings['enable_registration_notification']){
				$site_name = "https://".$_SERVER['SERVER_NAME'];
				$subject = "Continuum GRC Account Management Notification";
				$content = "<h2>Continuum GRC Account Management Notification</h2>";
				$content .= "<h3>Portal user ".$my_email." has invited a new portal user in ".$site_name.".</h3>";
	            $content .= "<hr/>";
	            $content .= "<h3>User Details:</h3>";
	            $content .= "<table>";
	            $content .= "<tr><td style='width:100px;'>User ID:</td><td>{$new_user_id}</td></tr>";
	            $content .= "<tr><td style='width:100px;'>Full Name:</td><td>{$_POST['user_full_name']}</td></tr>";
	            $content .= "<tr><td style='width:100px;'>Email:</td><td>{$_POST['user_email']}</td></tr>";
	            $content .= "<tr><td style='width:100px;'>Entity:</td><td>{$entity_name}</td></tr>";
	            $content .= "</table>";
	            sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}
			
			sendUserInviteNotification($dbh, $_POST["user_full_name"], $_POST["user_email"], $entity_name, $client_id);

			$_SESSION['LA_SUCCESS'] = 'You have sent an invitation to '.$_POST['user_email'].' successfully.';
			$_POST = array();
			header("Location: my_account.php?active_tab=my_entity");
			exit;
		}
	}

	if($_POST["form_name"] == "reset_mfa") {
		$user_id = $_SESSION["la_client_user_id"];
		$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `tsv_secret` = ?, `tsv_code_log` = ?, `how_user_registered` = ? WHERE `client_user_id`= ?";
		la_do_query($query_update, array("", "", 0, $user_id), $dbh);

		unset($_SESSION["email"]);
		unset($_SESSION["la_client_entity_id"]);
		unset($_SESSION["la_client_user_id"]);
		unset($_SESSION["la_client_client_id"]);
		unset($_SESSION["la_client_logged_in"]);
		unset($_SESSION["la_tsv_setup_secret"]);
		$_SESSION['LA_CLIENT_LOGIN_ERROR'] = "Please log in again to set up the new multi-factor authentication.";
		header("Location: index.php");
		exit;
	}

	if($_POST["form_name"] == "digital_signature") {
		$my_entity_error = "";
		$signer_id = $user_id;
		$signature_type = la_sanitize($_POST["signature_type"]);
		$signer_full_name = la_sanitize($_POST["signer_full_name"]);

		$signature_data = "";
		if ($signature_type == "type") {
			$signature_data = $signer_full_name;
		} else if ($signature_type == "draw") {
			$signature_data = la_sanitize($_POST["signature_data"]);
		} else if ($signature_type == "image") {
			// $signature_data = $_FILES['signature_file'];
			$signature_data = la_sanitize($_POST["signature_file_data"]);
		} else {
			$my_entity_error .= "Siganture type should be selected.<br>";
		}

		$signature_hash = md5($signature_data);
		$signature_id = base64_encode("signer_id={$signer_id}&signature_type={$signature_type}&signer_full_name={$signer_full_name}&signature_hash={$signature_hash}");

		//save the signature info and refresh the page
		if ($signature_id) {
			$query = "INSERT INTO 
						`".LA_TABLE_PREFIX."digital_signatures`( 
									`user_id`,
									`signer_full_name`, 
									`signature_type`,
									`signature_data`,
									`signature_id`,
									`created_at`,
									`updated_at`)
						VALUES (?, ?, ?, ?, ?, ?, ?);";
			$params = array(
						$signer_id,
						$signer_full_name,
						$signature_type,
						$signature_data,
						$signature_id,
						$tstamp,
						$tstamp
					);

			la_do_query($query,$params,$dbh);
		}
		
		$_POST = array();
		$_SESSION['LA_SUCCESS'] = 'Your signature info has been updated successfully.';
		header("Location: my_account.php?active_tab=other_settings");
		exit;
	}
}
$header_data =<<<EOT
<link type="text/css" href="../itam-shared/Plugins/Croppic/assets/css/croppic.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/view_default.css" media="all" />
EOT;
require('portal-header.php');
?>
<?php la_show_message(); ?>
<div class="content_body">
	<div class="profile gradient_blue">
		<div class="profile-sidebar portlet light">
			<div class="profile-userpic">
				<input id="my_user_id" type="hidden" value="<?php echo $user_id; ?>">
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
                    <li class="tab-item my_entity_tab">
                        <a href="#my_entity">
                            <i class="fas fa-building"></i> My Entity </a>
                    </li>
                    <?php if($is_admin) { ?>
	                    <li class="tab-item invite_user_tab">
	                        <a href="#invite_user">
	                            <i class="fas fa-user-plus"></i> Invite User </a>
	                    </li>
                	<?php } ?>
                    <li class="tab-item my_activity_tab">
                        <a href="#my_activity">
                            <i class="fas fa-file-alt"></i> My Activity </a>
                    </li>
                    <li class="tab-item other_settings_tab">
                        <a href="#other_settings">
                            <i class="fas fa-cog"></i> Other Settings </a>
                    </li>
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
							<label class="control-label" for="my_username">User Name<span class="required">*</span></label>
            				<input id="my_username" class="form-control" type="text" name="my_username" value="<?php echo $tmp_my_username; ?>" placeholder="John_Doe">
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
			<div id="my_entity" class="tab-panel" style="display: none;">
				<div class="tab-panel-header">
					<span>My Entity</span>
				</div>
				<div class="tab-panel-content">
					<form id="my_entity_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=my_entity'; ?>" method="post">
						<div class="form-group">
							<label class="control-label" for="entity_name">Entity Name<span class="required">*</span></label>
            				<input id="entity_name" class="form-control" type="text" name="entity_name" value="<?php echo $tmp_entity_name; ?>" placeholder="Continuum GRC">
						</div>
						<div class="form-group">
							<label class="control-label" for="entity_full_name">Contact Name</label>
            				<input id="entity_full_name" class="form-control" type="text" name="entity_full_name" value="<?php echo $tmp_entity_full_name; ?>" placeholder="John Doe">
						</div>
						<div class="form-group">
							<label class="control-label" for="entity_email">Contact Email</label>
            				<input id="entity_email" class="form-control" type="text" name="entity_email" value="<?php echo $tmp_entity_email; ?>" placeholder="example@mail.com">
						</div>
						<div class="form-group">
							<label class="control-label" for="entity_phone">Contact Phone</label>
            				<input id="entity_phone" class="form-control" type="text" name="entity_phone" value="<?php echo $tmp_entity_phone; ?>" placeholder="(888) 896-6207">
						</div>
						<div class="form-group">
							<label class="control-label" for="entity_description">Entity Description<span class="required">*</span></label>
            				<textarea id="entity_description" class="form-control" rows="3" name="entity_description" placeholder="Continuum GRC, Inc. software solutions provides the most efficient, cost-effective and proactive Governance, Risk and Compliance (GRC) assessment software tools available."><?php echo $tmp_entity_description; ?></textarea>
						</div>
						<div class="form-group">
							<p class="error"><?php echo $my_entity_error; ?></p>
						</div>
						<div class="form-group">
							<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
							<input type="hidden" name="form_name" value="my_entity">
							<button id="my_entity_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Changes </button>
						</div>
					</form>
					<div id="other_users">
						<div class="tab-panel-header">
							<span>Other Users Assigned To This Entity</span>
						</div>
						<table id="other_users_table" class="hover stripe cell-border" style="width: 100%;">
							<thead>
								<tr>
									<th>#</th>
									<th>Full Name</th>
									<th>Email Address</th>
									<th>User Name</th>
									<th>Phone Number</th>
									<th>Job Classification</th>
									<th>Job Title</th>
									<th>About User</th>
									<th>Status</th>
									<th>Date Created</th>
									<th>Uninvite</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$client_users = array();
									$query1 = "SELECT `client_user_id` FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_id`= ?";
									$sth1 = la_do_query($query1, array($client_id), $dbh);
									while($row1 = la_do_fetch_result($sth1)){
										array_push($client_users, $row1['client_user_id']);
									}
									$query2 = "SELECT `client_user_id` FROM ".LA_TABLE_PREFIX."entity_user_relation WHERE `entity_id`= ?";
									$sth2 = la_do_query($query2, array($client_id), $dbh);
									while($row2 = la_do_fetch_result($sth2)){
										array_push($client_users, $row2['client_user_id']);
									}
									$client_users = array_unique($client_users);
									if (($key = array_search($user_id, $client_users)) !== false) {
									    unset($client_users[$key]);
									}
									foreach($client_users as $client_user) {
										$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ? ";
										$sth = la_do_query($query, array($client_user), $dbh);
										$client_user_data = la_do_fetch_result($sth);
										if($client_user_data) {
											$user_regDate    = $client_user_data['register_datetime'] ? date("m-d-Y", $client_user_data['register_datetime']) : "";
											$user_status = "";
											$user_uninvite = "";
											if(la_sanitize($client_user_data['is_invited']) == 1){
												$user_status = 'Invited</br></br><button class="bb_button bb_small bb_green resend-invitation">Resend Invitation</button>';
												$user_status_class = "mu_invited";
												if($is_admin) {
													$user_uninvite = '<a class="action-delete" data-user-id="'.$client_user_data['client_user_id'].'" data-user-email="'.$client_user_data['email'].'" title="Uninvite" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png"></a>';
												}
											}else{
												if(la_sanitize($client_user_data['status']) == 0){
													$user_status = 'Active';
													$user_status_class = "mu_active";
												}else if(la_sanitize($client_user_data['status']) == 1){
													$user_status = 'Suspended';
													$user_status_class = "mu_suspended";
												}
											}
										?>
											<tr user-id="<?php echo $client_user_data['client_user_id']; ?>">
												<td><?php echo $client_user_data['client_user_id']; ?></td>
												<td><?php echo $client_user_data['full_name']; ?></td>
												<td><?php echo $client_user_data['email']; ?></td>
												<td><?php echo $client_user_data['username']; ?></td>
												<td><?php echo $client_user_data['phone']; ?></td>
												<td><?php echo $client_user_data['job_classification']; ?></td>
												<td><?php echo $client_user_data['job_title']; ?></td>
												<td><?php echo $client_user_data['about_me']; ?></td>
												<td class="<?php echo $user_status_class; ?>"><?php echo $user_status; ?></td>
												<td><?php echo $user_regDate; ?></td>
												<td><?php echo $user_uninvite; ?></td>
											</tr>
								<?php
										}
									}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php if($is_admin) { ?>
				<div id="invite_user" class="tab-panel" style="display: none;">
					<div class="tab-panel-header">
						<span>Invite User</span>
					</div>
					<div class="tab-panel-content">
						<form id="invite_user_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=invite_user'; ?>" method="post">
							<div class="form-group">
								<label class="control-label" for="user_full_name">Full Name<span class="required">*</span></label>
	            				<input id="user_full_name" class="form-control" type="text" name="user_full_name" value="<?php echo $user_full_name; ?>" placeholder="John Doe">
							</div>
							<div class="form-group">
								<label class="control-label" for="user_email">Email Address<span class="required">*</span></label>
	            				<input id="user_email" class="form-control" type="text" name="user_email" value="<?php echo $user_email; ?>" placeholder="example@mail.com">
							</div>
							<div class="form-group">
								<p class="error"><?php echo $invite_user_error; ?></p>
							</div>
							<div class="form-group">
								<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
								<input type="hidden" name="form_name" value="invite_user">
								<button id="invite_user_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Add_user.png"> Invite User </button>
							</div>
						</form>
					</div>
				</div>
			<?php } ?>
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
								$query = "SELECT `al`.*, `aat`.`action_type`, `f`.`form_name` FROM ".LA_TABLE_PREFIX."audit_client_log `al` left join `".LA_TABLE_PREFIX."audit_action_type` `aat` on (`aat`.`action_type_id` = `al`.`action_type_id`) left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = ? order by `al`.`action_datetime`";
								$result = la_do_query($query, array($user_id), $dbh);
								while ($row = la_do_fetch_result($result)) {
									$i++;
										if($row['action_type_id'] == 7 || $row['action_type_id'] == 8){
									?>
										<tr>
							              	<td><?php echo $i; ?></td>
							              	<td><?php echo $row['form_id']; ?></td>
							              	<td><div><?php echo $my_full_name; ?> <?php echo $row['action_text']; ?></div></td>
							              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
							            </tr>
									<?php
										}elseif($row['action_type_id'] == 15 || $row['action_type_id'] == 16){
											$file_name = substr($row['action_text'], strpos($row['action_text'], '-') +1 );
									?>
										<tr>
							              	<td><?php echo $i; ?></td>
							             	<td><?php echo $row['form_id']; ?></td>
							              	<td><div><?php echo $my_full_name; ?> <?php echo $row['action_type']; ?> <strong><?=$file_name?></strong>  Form #<?php echo $row['form_id']; ?></div></td>
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
							              					echo $my_full_name.' <strong>'.$row['action_type'].'</strong> '.$action_text_array->action_performed_on;	
							              				} else {
							              					echo "[Admin] ".$action_text_array->action_performed_by.' <strong>'.$row['action_type'].'</strong> '.$my_full_name;
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
							              	<td><div><?php echo $my_full_name; ?> <?php echo $row['action_type']; ?> <strong><?=$file_name?></strong>  Form #<?php echo $row['form_id']; ?></div></td>
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
							              					echo $my_full_name.' <strong>'.$row['action_type'].'</strong> '.$action_text_array->action_performed_on;	
							              				} else {
							              					echo "[Admin] ".$action_text_array->action_performed_by.' <strong>'.$row['action_type'].'</strong> '.$my_full_name;
							              				}
							              			?>
							              		</div>
							              	</td>
							              	<td><?php echo date("m-d-Y H:i", $row['action_datetime']); ?></td>
							            </tr>
							        <?php
										}elseif($row['action_type_id'] == 17){
									?>
							            <tr>
							              	<td><?php echo $i; ?></td>
							              	<td>-</td>
							              	<td><div><?php echo $my_full_name; ?> was forced to log out for the reason of declining assistance with access to IT Audit Machine.</div></td>
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
							              	<td><div><?php echo $my_full_name; ?> <strong><?=$row['action_type']?></strong> Form #<?php echo $row['form_id']; ?> <?=$action_text?></div></td>
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
			  						$result = la_do_query($query, array($user_id, 1), $dbh);
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
			  						$result = la_do_query($query, array($user_id, 0), $dbh);
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
			<div id="other_settings" class="tab-panel" style="display: none;">
				<div class="tab-panel-header">
					<span>Other Settings</span>
				</div>
				<div class="tab-panel-content">
					<?php
						if($my_account_suspension_strict_date != "") {
					?>
						<div class="notification alert">Your account is set to be automatically suspended on <b><?php echo $my_account_suspension_strict_date; ?></b>.</div>
					<?php
						}
					?>
					<?php
						if($my_account_suspension_inactive != "") {
					?>
						<div class="notification alert">Your account is set to be automatically suspended for inactivity after <b><?php echo $my_account_suspension_inactive; ?></b> days.</div>
					<?php
						}
					?>
					<?php
						if($my_account_deletion_inactive != "") {
					?>
						<div class="notification alert">Your account is set to be automatically deleted for inactivity after <b><?php echo $my_account_deletion_inactive; ?></b> days.</div>
					<?php
						}
					?>
					<?php
						if($my_other_entities != "") {
					?>
						<div class="notification info">Other entities that you are assigned to: <b><?php echo $my_other_entities; ?></b>.</div>
					<?php
						}
					?>
					<div class="digital-signature-settings">
						<div class="tab-panel-header">
							<span>Digital Signature</span>
						</div>
						<div style="display: flex">
							<form id="other_settings_form" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?active_tab=other_settings'; ?>" method="post">
								<p>Signature Type:</p>
								<input class="signature_type" type="radio" id="type" name="signature_type" value="type" <?php echo $signature_type=="type"?"checked":"";?>>
								<label for="type">Type</label><br>
								<input class="signature_type" type="radio" id="draw" name="signature_type" value="draw" <?php echo $signature_type=="draw"?"checked":"";?>>
								<label for="draw">Draw</label><br>
								<input class="signature_type" type="radio" id="image" name="signature_type" value="image" <?php echo $signature_type=="image"?"checked":"";?>>
								<label for="image">Image</label>
								
								<div class="form-group" style="margin-top: 12px;">
									<label>Full Name</label>
									<input class="form-control" type="text"  value="<?php echo $my_full_name_for_header; ?>" disabled style="width: 315px"/>
								</div>
								<div id="type-d-sign" class="d-sign form-group" style="display:<?php echo $signature_type=="type"?"block":"none";?>">
									<input id="signer_full_name" name="signer_full_name" class="form-control" style="font-family: Brush Script MT, Brush Script Std, cursive; font-size:28px; width: 315px" value="<?php echo $signature_type=="type" ? $signature_data:""; ?>"/>
								</div>
								<div id="draw-d-sign" class="d-sign"  style="display:<?php echo $signature_type=="draw"?"block":"none";?>">
									<div id="la_sigpad" class="la_sig_wrapper">
										<canvas class="la_canvas_pad" width="309" height="130"></canvas>
										<input type="hidden" name="signature_data" id="signature_data" value='<?php echo $signature_type=="draw" ? $signature_data:""; ?>'/>
									</div>
									<a class="la_sigpad_clear" href="#">Clear</a>
								</div>
								<div id="image-d-sign" class="d-sign" style="display:<?php echo $signature_type=="image"?"block":"none";?>">
									<input type='file' id="image-d-sign-file" style="display:block"/>
									<input type='hidden' id="signature_file_data" name="signature_file_data"/>
									<img 
										id="image-d-sign-preview" 
										src='<?php echo $signature_type=="image" ? $signature_data : "https://lh3.googleusercontent.com/proxy/8CLEDH30GdZdSN-u3Zo-OfiVVWSiqf4IQgafiu7E51Pb9uX4mfJ14f5Z-RSo1OIaj-HZ292Pi-XyU92Zfz2qbAyHu51ntYwHFXG1IJj01y7FgWYv_zAXgTOO8Ks"; ?>'
										width="315" 
										height="130" 
										style="margin-top: 5px; border: 1px solid #c2cad8;"
									/>
								</div>
								<div>
									<input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
									<input type="hidden" name="form_name" value="digital_signature"/>
									<input type="hidden" name="signature_id" value="-1"/>
									<button id="other_settings_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Changes </button>
								</div>
							</form>
							<div style="margin-left: 50px">
								<p>Signature Image:</p>
								<?php if (isset($signature_id)) {?>
									<div style="margin-top: 30px; padding: 20px; height: 130px; width: 250px; border: 1px dashed #8EACCF;">
										<img src="<?php echo "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/digital_signature_img.php?q='.$signature_id;?>" width="200" height="100"/>
										<div style="float: right">Signed by <?php echo $my_full_name_for_header; ?></div>
									</div>
								<?php } else { ?>
									You have never registered signature info yet.
								<?php } ?> 
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="dialog-uninvite-message" title="Are you sure you want to uninvite this user?" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/005499/50x50/Notice.png" />
  	<p id="dialog-confirm-edit-msg">This action cannot be undone.<br/>
    <br/>
	</p>
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Error </p>
</div>
<?php
$footer_data =<<<EOT
<script type="text/javascript" src="../itam-shared/Plugins/Croppic/croppic.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/my_account.js"></script>
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
EOT;
require('portal-footer.php');
?>