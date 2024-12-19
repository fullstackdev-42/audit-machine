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
	require('includes/language.php');
	require('includes/common-validator.php');
	require('lib/swift-mailer/swift_required.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');	

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	}

	$user_id = (int) trim($_GET['id']);
	
	if(empty($user_id)){
		$_SESSION['LA_DENIED'] = "The administrative user doesn't exist.";
		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	}

	$query = "SELECT * FROM ".LA_TABLE_PREFIX."users WHERE user_id=? AND `status` > 0 AND `is_examiner` = 0";
	$sth = la_do_query($query, array($user_id), $dbh);
	$row = la_do_fetch_result($sth);
	if(!$row) {
		$_SESSION['LA_DENIED'] = "The administrative user doesn't exist.";
		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	} else {
		if($user_id == 1 && $_SESSION['la_user_id'] != 1){
			$_SESSION['LA_DENIED'] = "You don't have permission to edit Main Administrator.";
			$ssl_suffix = la_get_ssl_suffix();
			header("Location: restricted.php");
			exit;
		}
	}

	//get the list of the form, put them into array
	$query = "SELECT 
					form_name,
					form_id
				FROM
					".LA_TABLE_PREFIX."forms
				WHERE
					form_active=0 or form_active=1
			 ORDER BY 
					form_name ASC";
	
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	
	$form_list_array = array();
	$i=0;
	while($row = la_do_fetch_result($sth)){
		$form_list_array[$i]['form_id']   	  = $row['form_id'];

		if(!empty($row['form_name'])){		
			$form_list_array[$i]['form_name'] = $row['form_name'];
		}else{
			$form_list_array[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
		}
		$i++;
	}
	
	if(la_is_form_submitted()){ //if form submitted
		//get all required inputs
		$user_input['user_name'] 		= $_POST['au_user_name'];
		$user_input['user_email'] 		= strtolower($_POST['au_user_email']);
		$user_input['avatar_url'] 		= $_POST['avatar_url'];
		$user_input['user_phone'] 		= $_POST['au_user_phone'];
		$user_input['job_classification'] = $_POST['au_job_classification'];
		$user_input['job_title'] 		= $_POST['au_job_title'];
		$user_input['about_me'] 		= $_POST['au_about_me'];
		$user_input['user_id'] 			= (int) $_POST['user_id'];
		$user_input['tsv_enable'] 		= (int) $_POST['au_tsv_enable'];

		$user_input['priv_new_forms'] 	= (int) $_POST['au_priv_new_forms'];
		$user_input['priv_new_themes'] 	= (int) $_POST['au_priv_new_themes'];
		$user_input['priv_administer'] 	= (int) $_POST['au_priv_administer'];

		if(empty($user_input['user_id'])){
			die('User ID required.');
		}

		//only administrator can modify himself
		if($user_input['user_id'] == 1 && $_SESSION['la_user_id'] != 1){
			die("Access Denied. You don't have permission to edit Main Administrator.");
		}

		//make sure that Main Administrator privileges can't be modified
		if($user_input['user_id'] == 1){
			$user_input['priv_administer'] = 1;
		}

		//if the user has administer privileges, make sure to get all other privileges as well
		if(!empty($user_input['priv_administer'])){
			$user_input['priv_new_forms'] = 1;
			$user_input['priv_new_themes'] = 1;
		}

		foreach ($form_list_array as $value) {
			$form_id = $value['form_id'];

			$user_input['perm_editform_'.$form_id] 	  = (int) $_POST['perm_editform_'.$form_id];
			$user_input['perm_editentries_'.$form_id] = (int) $_POST['perm_editentries_'.$form_id];
			$user_input['perm_viewentries_'.$form_id] = (int) $_POST['perm_viewentries_'.$form_id];
		}
		
		//clean the inputs
		$user_input = la_sanitize($user_input);

		//validate inputs
		$error_messages = array();

		//validate name
		if(empty($user_input['user_name'])){
			$error_messages['user_name'] = 'This field is required. Please enter a name.';
		}

		//validate email
		if(empty($user_input['user_email'])){
			$error_messages['user_email'] = 'This field is required. Please enter an email.';
		}else{
			//check for valid email address
			$email_regex  = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]*\.[A-z0-9]{2,6}$/';
			$regex_result = preg_match($email_regex, $user_input['user_email']);
				
			if(empty($regex_result)){
				//the email being entered is incorrectly formatted
				$error_messages['user_email'] = 'Please enter a valid email address.';
			}else{
				//check for duplicate
				$query = "select count(user_email) total_user from `".LA_TABLE_PREFIX."users` where user_email = ? and user_id <> ? and `status` > 0";
				
				$params = array($user_input['user_email'],$user_input['user_id']);
				$sth = la_do_query($query,$params,$dbh);
				$row = la_do_fetch_result($sth);

				if(!empty($row['total_user'])){
					$error_messages['user_email'] = 'This email address is already in use.';
				}
			}
		}

		//validate phone number
		if(!empty($user_input['user_phone']) && ($user_input['user_phone'] != "")){
			if(la_validate_simple_phone(array($user_input['user_phone'])) !== true){
				$error_messages['user_phone'] = la_validate_simple_phone(array($user_input['user_phone']))."<br>";
			}
		}

		if(!empty($error_messages)){
			$_SESSION['LA_ERROR'] = 'Please correct the marked field(s) below.';
		}else{
			//everything is validated, continue updating user
			//compare the old and new data, if they are different, email account management alert notification
			if($la_settings['enable_registration_notification']){
				$changed_content = "";
				$query_get_old_data = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
				$sth_get_old_data = la_do_query($query_get_old_data, array($user_input['user_id']), $dbh);
				$row_old_data = la_do_fetch_result($sth_get_old_data);
				if($row_old_data['user_fullname'] != $user_input['user_name']){
					$changed_content .= "<tr><td style='width: 300px;'>User Name:</td><td style='width: 200px;'>{$row_old_data['user_fullname']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_name']}</td></tr>";
				}
				if($row_old_data['user_email'] != $user_input['user_email']){
					$changed_content .= "<tr><td style='width: 300px;'>Email:</td><td style='width: 200px;'>{$row_old_data['user_email']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_email']}</td></tr>";
				}
				if($row_old_data['user_phone'] != $user_input['user_phone']){
					$changed_content .= "<tr><td style='width: 300px;'>Phone Number:</td><td style='width: 200px;'>{$row_old_data['user_phone']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_phone']}</td></tr>";
				}
				if($row_old_data['job_classification'] != $user_input['job_classification']){
					$changed_content .= "<tr><td style='width: 300px;'>Job Classification:</td><td style='width: 200px;'>{$row_old_data['job_classification']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['job_classification']}</td></tr>";
				}
				if($row_old_data['job_title'] != $user_input['job_title']){
					$changed_content .= "<tr><td style='width: 300px;'>Job Title:</td><td style='width: 200px;'>{$row_old_data['job_title']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['job_title']}</td></tr>";
				}
				if($row_old_data['about_me'] != $user_input['about_me']){
					$changed_content .= "<tr><td style='width: 300px;'>More Info:</td><td style='width: 200px;'>{$row_old_data['about_me']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['about_me']}</td></tr>";
				}
				if($row_old_data['priv_new_forms'] != $user_input['priv_new_forms']){
					if($row_old_data['priv_new_forms'] == 0){
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to create new forms:</td><td style='width: 200px;'>Disallow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>Allow</td></tr>";
					} else {
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to create new forms:</td><td style='width: 200px;'>Allow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>Disallow</td></tr>";
					}
				}
				if($row_old_data['priv_new_themes'] != $user_input['priv_new_themes']){
					if($row_old_data['priv_new_themes'] == 0){
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to create new themes:</td><td style='width: 200px;'>Disallow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>Allow</td></tr>";
					} else {
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to create new themes:</td><td style='width: 200px;'>Allow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>Disallow</td></tr>";
					}
				}
				if($row_old_data['priv_administer'] != $user_input['priv_administer']){
					if($row_old_data['priv_administer'] == 0){
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to administer IT Audit Machine:</td><td style='width: 200px;'>Disallow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>Allow</td></tr>";
					} else {
						$changed_content .= "<tr><td style='width: 300px;'>Allow user to administer IT Audit Machine:</td><td style='width: 200px;'>Allow</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px; color: #0085CC;'>Disallow</td></tr>";
					}
				}
				if($changed_content != ""){
					$login_user = $_SESSION['email'];
					$site_name = "https://".$_SERVER['SERVER_NAME'];
					$subject = "Continuum GRC Account Management Alert";
					$content = "<h2>Continuum GRC Account Management Alert</h2>";
					$content .= "<h3>Administrative user ".$login_user." has edited an administrative user ".$row_old_data['user_email']." in ".$site_name.".</h3>";
					$content .= "<hr/>";
					$content .= "<h3>Changed Information:</h3>";
					$content .= "<table>";
					$content .= $changed_content;
					$content .= "</table>";
					sendUserManagementNotification($dbh, $la_settings, $subject, $content);
				}
			}

			//update ap_users table
			$query = "UPDATE 
							`".LA_TABLE_PREFIX."users` 
						SET	
							`user_email`=?,
							`user_fullname`=?,
							`user_phone`=?,
							`job_classification`=?,
							`job_title`=?,
							`about_me`=?,
							`priv_administer`=?,
							`priv_new_forms`=?,
							`priv_new_themes`=?,
							`tsv_enable`=?
					 WHERE  `user_id` = ?";
			$params = array(
							$user_input['user_email'],
							$user_input['user_name'],
							$user_input['user_phone'],
							$user_input['job_classification'],
							$user_input['job_title'],
							$user_input['about_me'],
							$user_input['priv_administer'],
							$user_input['priv_new_forms'],
							$user_input['priv_new_themes'],
							$user_input['tsv_enable'],
							$user_input['user_id']);
			la_do_query($query,$params,$dbh);

			// add user activity to log: activity - 10 (Admin account updated)
			$action_text = json_encode( array('action_performed_by' => $_SESSION['email']) );
			addUserActivity($dbh, $user_input['user_id'], 0, 10, $action_text, time(), $_SERVER['REMOTE_ADDR']);

			$action_text = json_encode( array('action_performed_on' => $user_input['user_email']) );
			addUserActivity($dbh, $_SESSION['la_user_id'], 0, 10, $action_text, time(), $_SERVER['REMOTE_ADDR']);

			//when TSV being disabled, reset secret key and previous tsv code log
			if(empty($user_input['tsv_enable'])){
				$query = "UPDATE ".LA_TABLE_PREFIX."users set tsv_enable=0,tsv_secret='',tsv_code_log='' where user_id=?";
				$params = array($user_input['user_id']);
				la_do_query($query,$params,$dbh);
			}

			//delete existing permissions
			$query = "DELETE from ".LA_TABLE_PREFIX."permissions WHERE user_id = ?";
			$params = array($user_input['user_id']);
			la_do_query($query,$params,$dbh);

			//insert into ap_permissions table
			foreach ($form_list_array as $value) {
				$form_id = $value['form_id'];

				if(!empty($user_input['perm_editentries_'.$form_id])){
					$user_input['perm_viewentries_'.$form_id] = 1;
				}
				
				//if all permission are empty, don't do insert
				if(empty($user_input['perm_editform_'.$form_id]) && empty($user_input['perm_editentries_'.$form_id]) && empty($user_input['perm_viewentries_'.$form_id])){
					continue;
				}

				$params = array(
								$form_id, 
								$user_input['user_id'], 
								$user_input['perm_editform_'.$form_id], 
								$user_input['perm_editentries_'.$form_id], 
								$user_input['perm_viewentries_'.$form_id]);

				$query = "INSERT INTO 
									`".LA_TABLE_PREFIX."permissions` (
															`form_id`, 
															`user_id`, 
															`edit_form`, 
															`edit_entries`, 
															`view_entries`) 
								VALUES (?, ?, ?, ?, ?);";
				la_do_query($query,$params,$dbh);
			}

			
			//redirect to manage_users page and display success message
			$_SESSION['LA_SUCCESS'] = 'User #'.$user_input['user_id'].' has been updated.';

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: edit_user.php?id=".$user_input['user_id']);
			exit;
		}
	}else{
		$user_input['user_id'] = $user_id;

		//get user profile data
		$query = "SELECT 
						*
				    FROM 
						".LA_TABLE_PREFIX."users 
				   WHERE 
				   		user_id=? and `status` > 0";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$user_input['user_name']  		= $row['user_fullname'];
		$user_input['user_email'] 	    = $row['user_email'];
		$user_input['user_phone'] 	    = $row['user_phone'];
		$user_input['job_classification'] 	    = $row['job_classification'];
		$user_input['job_title'] 	    = $row['job_title'];
		$user_input['about_me'] 	    = $row['about_me'];
		$user_input['avatar_url'] 	    = $row['avatar_url'];
		$user_input['priv_new_forms'] 	= $row['priv_new_forms'];
		$user_input['priv_new_themes'] 	= $row['priv_new_themes'];
		$user_input['priv_administer'] 	= $row['priv_administer'];
		$user_input['tsv_enable'] 		= (int) $row['tsv_enable'];
 
		//if this user is admin, all privileges should be available
		if(!empty($user_input['priv_administer'])){
			$user_input['priv_new_forms'] = 1;
			$user_input['priv_new_themes'] = 1;
		}

		//get permission list for this user
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
		
		while($row = la_do_fetch_result($sth)){ 
			$form_id = (int) $row['form_id'];
		
			$user_input['perm_editform_'.$form_id] 	  = $row['edit_form'];
			$user_input['perm_editentries_'.$form_id] = $row['edit_entries'];
			$user_input['perm_viewentries_'.$form_id] = $row['view_entries'];
		}

	}
	
$current_nav_tab = 'manage_users';
$header_data =<<<EOT
<link type="text/css" href="../itam-shared/Plugins/Croppic/assets/css/croppic.css" rel="stylesheet" />
EOT;
require('includes/header.php'); 
	
?>
<div id="content" class="full">
  <div class="post add_user">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2><a class="breadcrumb" href='manage_users.php'>Users Manager</a> <img src="images/icons/resultset_next.gif" /> <a class="breadcrumb" href='view_user.php?id=<?php echo $user_input['user_id']; ?>'>#<?php echo $user_input['user_id']; ?></a> <img src="images/icons/resultset_next.gif" /> Edit</h2>
          <p>Editing user #<?php echo $user_input['user_id']; ?></p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <form id="add_user_form" method="post" action="<?php echo noHTML($_SERVER['REQUEST_URI']); ?>">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        </div>
        <ul id="au_main_list">
          <li>
            <div id="au_box_user_profile" class="au_box_main gradient_blue">
              <div class="au_box_meta">
                <h1>1.</h1>
                <h6>Edit Profile</h6>
              </div>
              <div class="au_box_content" style="padding-bottom: 15px">
              	<label class="description">User Photo</label>
              	<div class="profile-userpic">
					<input id="user_id" type="hidden" value="<?php echo $user_input['user_id']; ?>">
					<input type="hidden" name="avatar_url" value="<?php echo $user_input['avatar_url']; ?>">
					<img src="<?php echo $user_input['avatar_url']; ?>">
					<div id="profile_image_upload"></div>
				</div>
                <label class="description <?php if(!empty($error_messages['user_name'])){ echo 'label_red'; } ?>" for="au_user_name">Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Full name of the user."/></label>
                <input id="au_user_name" name="au_user_name" class="element text large" value="<?php echo noHTML($user_input['user_name']); ?>" type="text">
                <?php
					if(!empty($error_messages['user_name'])){
						echo '<span class="au_error_span">'.$error_messages['user_name'].'</span>';
					}
				?>
                <label class="description <?php if(!empty($error_messages['user_email'])){ echo 'label_red'; } ?>" for="au_user_email">Email Address <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The email address must be unique. No two administrative users or examiners can have the same email address."/></label>
                <input id="au_user_email" name="au_user_email" class="element text large" value="<?php echo noHTML($user_input['user_email']); ?>" type="text">
                <?php
					if(!empty($error_messages['user_email'])){
						echo '<span class="au_error_span">'.$error_messages['user_email'].'</span>';
					}
				?>
                <label class="description <?php if(!empty($error_messages['user_phone'])){ echo 'label_red'; } ?>" for="au_user_phone">Phone Number <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Phone number of the user. Recommended format: +1-888-896-6207"/></label>
                <input id="au_user_phone" name="au_user_phone" class="element text large" value="<?php echo noHTML($user_input['user_phone']); ?>" type="text">
                <?php
					if(!empty($error_messages['user_phone'])){
						echo '<span class="au_error_span">'.$error_messages['user_phone'].'</span>';
					}
				?>
				<label class="description" for="au_job_classification">Job Classification <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Job classification of the user."/></label>
                <input id="au_job_classification" name="au_job_classification" class="element text large" value="<?php echo noHTML($user_input['job_classification']); ?>" type="text">
                <label class="description" for="au_job_title">Job Title <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Job title of the user."/></label>
                <input id="au_job_title" name="au_job_title" class="element text large" value="<?php echo noHTML($user_input['job_title']); ?>" type="text">
                <label class="description" for="au_about_me">More Info <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="More info of the user."/></label>
                <textarea id="au_about_me" name="au_about_me" rows="3" style="width: 90%;"><?php echo noHTML($user_input['about_me']); ?></textarea>
                <div style="clear: both;margin-top: 10px"></div>
                <input id="au_tsv_enable" name="au_tsv_enable" class="checkbox" <?php if(!empty($user_input['tsv_enable'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px;">
                <label class="choice" for="au_tsv_enable">Enable Multi-Factor Authentication </label>
                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Multi-Factor Authentication (MFA) is an optional but highly recommended security feature that adds an extra layer of protection to IT Audit Machine account. Once enabled, IT Audit Machine will require a six-digit security code in addition to the standard password whenever users sign in. Additionally, should you need to reset your MFA, uncheck this option, save then immediately recheck it and save. You will be prompted to reset your MFA when you log in next."/> </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <li>
            <div id="au_box_privileges" class="au_box_main gradient_blue">
              <div class="au_box_meta">
                <h1>2.</h1>
                <h6>Edit Privileges</h6>
              </div>
              <div class="au_box_content" style="padding-top: 10px;min-height: 90px;">
                <input id="au_priv_new_forms" name="au_priv_new_forms" class="checkbox" <?php if(!empty($user_input['priv_new_forms'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px" <?php if(!empty($user_input['priv_administer'])){ echo 'disabled="disabled"'; } ?>>
                <label class="choice" for="au_priv_new_forms">Allow user to create new forms</label>
                <div style="clear: both;margin-top: 10px"></div>
                <input id="au_priv_new_themes" name="au_priv_new_themes" class="checkbox" <?php if(!empty($user_input['priv_new_themes'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px;" <?php if(!empty($user_input['priv_administer'])){ echo 'disabled="disabled"'; } ?>>
                <label class="choice" for="au_priv_new_themes">Allow user to create new themes</label>
                <div style="clear: both;margin-top: 10px"></div>
                <input id="au_priv_administer" name="au_priv_administer" class="checkbox" <?php if(!empty($user_input['priv_administer'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px;" <?php if($user_input['user_id'] == 1){ echo 'disabled="disabled"'; } ?>>
                <label class="choice" for="au_priv_administer">Allow user to administer IT Audit Machine</label>
              </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <?php
							if(!empty($form_list_array)){
						?>
          <li class="user_permissions_list" <?php if(!empty($user_input['priv_administer'])){ echo 'style="display: none"'; } ?>>
            <div id="au_box_permissions" class="au_box_main gradient_blue">
              <div class="au_box_meta">
                <h1>3.</h1>
                <h6>Edit Permissions</h6>
              </div>
              <div class="au_box_content">
                <ul id="au_li_permissions">
                <?php
					foreach ($form_list_array as $value) {
						$form_id = $value['form_id'];

						if(!empty($user_input['perm_editform_'.$form_id])){
							$class_attr = 'class="highlight_red"';
						}else if(!empty($user_input['perm_editentries_'.$form_id])){
							$class_attr = 'class="highlight_yellow"';
						}else if(!empty($user_input['perm_viewentries_'.$form_id])){
							$class_attr = 'class="highlight_green"';
						}else{
							$class_attr = '';
						}
				?>
                  <li id="li_<?php echo $form_id; ?>" <?php echo $class_attr; ?>>
                    <div class="au_perm_title"><?php echo $value['form_name']; ?></div>
                    <div class="au_perm_controls"> <span class="au_perm_guide">allow user to</span> <span class="au_perm_arrow">&#8674;</span>
                      <input id="perm_editform_<?php echo $form_id; ?>" name="perm_editform_<?php echo $form_id; ?>" <?php if(!empty($user_input['perm_editform_'.$form_id])){ echo 'checked="checked"'; } ?> class="checkbox cb_editform" value="1" type="checkbox" style="margin-left: 5px">
                      <label class="choice" for="perm_editform_<?php echo $form_id; ?>">Edit Form</label>
                      <input id="perm_editentries_<?php echo $form_id; ?>" name="perm_editentries_<?php echo $form_id; ?>" <?php if(!empty($user_input['perm_editentries_'.$form_id])){ echo 'checked="checked"'; } ?> class="checkbox cb_editentries" value="1" type="checkbox">
                      <label class="choice" for="perm_editentries_<?php echo $form_id; ?>">Edit Entries</label>
                      <input id="perm_viewentries_<?php echo $form_id; ?>" name="perm_viewentries_<?php echo $form_id; ?>" <?php if(!empty($user_input['perm_viewentries_'.$form_id]) || !empty($user_input['perm_editentries_'.$form_id])){ echo 'checked="checked"'; } ?> class="checkbox cb_viewentries" <?php if(!empty($user_input['perm_editentries_'.$form_id])){ echo 'disabled="disabled"'; } ?> value="1" type="checkbox">
                      <label class="choice" for="perm_viewentries_<?php echo $form_id; ?>">View Entries</label>
                    </div>
                  </li>
                  <?php
											}
										?>
                </ul>
                <div id="au_bulk_select">
                  <select class="element select" id="au_bulk_action" name="au_bulk_action">
                    <option value="">Bulk Action</option>
                    <optgroup label="Select All:">
                    <option value="select_editform">Edit Form</option>
                    <option value="select_editentries">Edit Entries</option>
                    <option value="select_viewentries">View Entries</option>
                    </optgroup>
                    <optgroup label="Unselect All:">
                    <option value="unselect_editform">Edit Form</option>
                    <option value="unselect_editentries">Edit Entries</option>
                    <option value="unselect_viewentries">View Entries</option>
                    </optgroup>
                  </select>
                </div>
              </div>
            </div>
          </li>
          <li class="ps_arrow user_permissions_list" <?php if(!empty($user_input['priv_administer'])){ echo 'style="display: none"'; } ?>><img src="images/icons/33_red.png" /></li>
          <?php } ?>
          <li>
            <div> <a href="#" id="button_edit_user" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Changes </a> </div>
          </li>
        </ul>
        <input type="hidden" name="submit_form" value="1" />
        <input type="hidden" name="user_id" value="<?php echo (int) $user_input['user_id']; ?>" />
      </form>
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/add_user.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/Croppic/croppic.js"></script>
<script>
$(document).ready(function() {
	var user_id = $("#user_id").val();
	//initiating avatar
	var cropperOptions = {
		processInline: true,
		cropUrl:'save_avatar.php',
		modal: true,
		cropData:{
			"is_admin": 1,
			"user_id": user_id,
			"mode": "edit_user"
		},
		onAfterImgCrop:	function(){location.reload(true);},
		onError: function(errormsg){console.log(errormsg);/*location.reload(true);*/}
	}
	
	var cropperHeader = new Croppic('profile_image_upload', cropperOptions);
});
</script>
EOT;
require('includes/footer.php');