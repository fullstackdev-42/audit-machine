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
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('lib/password-hash.php');
	require('lib/swift-mailer/swift_required.php');

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: restricted.php");
		exit;
	}

	//check current license usage, if this is Standard or Professional
	if($la_settings['license_key'][0] == 'S' || $la_settings['license_key'][0] == 'P'){
		$query = "select count(user_id) user_total from ".LA_TABLE_PREFIX."users where `status` > 0";
		
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
			$_SESSION['LA_DENIED'] = "You have 0 users left. Please upgrade your license to add more.";

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
		$user_input['user_name'] 		= trim($_POST['au_user_name']);
		$user_input['user_email'] 		= trim(strtolower($_POST['au_user_email']));
		$user_input['user_password'] 	= $_POST['au_user_password'];

		$user_input['send_login'] 		= (int) $_POST['au_send_login'];

		$user_input['priv_new_forms'] 	= (int) $_POST['au_priv_new_forms'];
		$user_input['priv_new_themes'] 	= (int) $_POST['au_priv_new_themes'];
		$user_input['priv_administer'] 	= (int) $_POST['au_priv_administer'];

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
			$email_regex  = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]*\.[A-z0-9]{2,15}$/';
			$regex_result = preg_match($email_regex, $user_input['user_email']);
				
			if(empty($regex_result)){
				//the email being entered is incorrectly formatted
				$error_messages['user_email'] = 'Please enter a valid email address.';
			}else{
				//check for duplicate
				$query = "select count(user_email) total_user from `".LA_TABLE_PREFIX."users` where user_email = ? and `status` > 0";
				
				$params = array($user_input['user_email']);
				$sth = la_do_query($query,$params,$dbh);
				$row = la_do_fetch_result($sth);

				if(!empty($row['total_user'])){
					$error_messages['user_email'] = 'This email address is already in use.';
				}
			}
		}

		//validate password
		if(empty($user_input['user_password'])){
			$error_messages['user_password'] = 'This field is required. Please enter a password.';
		}

		if(!empty($error_messages)){
			$_SESSION['LA_ERROR'] = 'Please correct the marked field(s) below.';
		}else{
			//everything is validated, continue creating user

			//insert into ap_users table
			$hasher = new Sha256Hash();
			$password_hash = $hasher->HashPassword($user_input['user_password']);

			$query = "INSERT INTO 
								`".LA_TABLE_PREFIX."users`( 
											`user_email`, 
											`user_password`, 
											`user_fullname`, 
											`priv_administer`, 
											`priv_new_forms`, 
											`priv_new_themes`, 
											`status`) 
						  VALUES (?, ?, ?, ?, ?, ?, ?);";
			$params = array(
							$user_input['user_email'],
							$password_hash,
							$user_input['user_name'],
							(int) $user_input['priv_administer'],
							(int) $user_input['priv_new_forms'],
							(int) $user_input['priv_new_themes'],
							1);
			
			la_do_query($query,$params,$dbh);
			$user_id = (int) $dbh->lastInsertId();

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
								$user_id, 
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

			//send notification email to the user, if enabled
			if(!empty($user_input['send_login'])){
				la_send_login_info($dbh,$user_id,$user_input['user_password']);
			}

			//redirect to manage_users page and display success message
			$_SESSION['LA_SUCCESS'] = 'A new user has been added.';

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: manage_users.php");
			exit;
		}
	}
	
	$current_nav_tab = 'users';
	require('portal-header.php'); 
	
?>


		<div id="content" class="full">
			<div class="post add_user">
				<div class="content_header">
					<div class="content_header_title">
						<div style="float: left">
							<h2><a class="breadcrumb" href='manage_users.php'>Users Manager</a> <img src="images/icons/resultset_next.gif" /> Add User</h2>
							<p>Create a new user and set permissions</p>
						</div>
						<div style="float: right;margin-right: 0px;padding-top: 26px;">
								<a href="add_user_bulk.php" id="add_user_bulk_link" class="">
									Switch to <strong>Bulk Add Users</strong> Mode
								</a>
						</div>	
						<div style="clear: both; height: 1px"></div>
					</div>
					
				</div>
				
				<?php la_show_message(); ?>

				<div class="content_body">
					<form id="add_user_form" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
					<ul id="au_main_list">
						<li>
							<div id="au_box_user_profile" class="au_box_main gradient_blue">
								<div class="au_box_meta">
									<h1>1.</h1>
									<h6>Define Profile</h6>
								</div>
								<div class="au_box_content" style="padding-bottom: 15px">
									<label class="description <?php if(!empty($error_messages['user_name'])){ echo 'label_red'; } ?>" for="au_user_name">Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Full name of the new user."/></label>
									<input id="au_user_name" name="au_user_name" class="element text large" value="<?php echo htmlspecialchars($user_input['user_name']); ?>" type="text">
									<?php
										if(!empty($error_messages['user_name'])){
											echo '<span class="au_error_span">'.$error_messages['user_name'].'</span>';
										}
									?>
									
									<label class="description <?php if(!empty($error_messages['user_email'])){ echo 'label_red'; } ?>" for="au_user_email">Email Address <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The email address must be unique. No two users can have the same email address."/></label>
									<input id="au_user_email" name="au_user_email" class="element text large" value="<?php echo htmlspecialchars($user_input['user_email']); ?>" type="text">
									<?php
										if(!empty($error_messages['user_email'])){
											echo '<span class="au_error_span">'.$error_messages['user_email'].'</span>';
										}
									?>

									<label class="description <?php if(!empty($error_messages['user_password'])){ echo 'label_red'; } ?>" for="au_user_password">Password <span class="required">*</span></label>
									<input id="au_user_password" name="au_user_password" class="element text medium" value="<?php echo htmlspecialchars($user_input['user_password']); ?>" type="password" style="width: 60%">
									<?php
										if(!empty($error_messages['user_password'])){
											echo '<span class="au_error_span">'.$error_messages['user_password'].'</span>';
										}
									?>

									<span style="display: block;margin-top: 15px">	
										<input id="au_send_login" name="au_send_login" class="checkbox" <?php if(!empty($user_input['send_login'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px">
										<label class="choice" for="au_send_login">Send login information to user</label> 
										<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will send an email to the user. The email contains their password and the login link to IT Audit Machine admin panel."/>
									</span>

								</div>
							</div>
						</li>
						<li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
						<li>
							<div id="au_box_privileges" class="au_box_main gradient_blue">
								<div class="au_box_meta">
									<h1>2.</h1>
									<h6>Set Privileges</h6>
								</div>
								<div class="au_box_content" style="padding-top: 10px;min-height: 90px;">
										<input id="au_priv_new_forms" name="au_priv_new_forms" class="checkbox" <?php if(!empty($user_input['priv_new_forms'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px" <?php if(!empty($user_input['priv_administer'])){ echo 'disabled="disabled"'; } ?>>
										<label class="choice" for="au_priv_new_forms">Allow user to create new forms</label>
											
										<div style="clear: both;margin-top: 10px"></div>
										
										<input id="au_priv_new_themes" name="au_priv_new_themes" class="checkbox" <?php if(!empty($user_input['priv_new_themes'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px;" <?php if(!empty($user_input['priv_administer'])){ echo 'disabled="disabled"'; } ?>>
										<label class="choice" for="au_priv_new_themes">Allow user to create new themes</label>
										
										<div style="clear: both;margin-top: 10px"></div>
										
										<input id="au_priv_administer" name="au_priv_administer" class="checkbox" <?php if(!empty($user_input['priv_administer'])){ echo 'checked="checked"'; } ?> value="1" type="checkbox" style="margin-left: 0px;">
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
									<h6>Set Permissions</h6>
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
													<div class="au_perm_controls">
														<span class="au_perm_guide">allow user to</span> <span class="au_perm_arrow">&#8674;</span>
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
							<div>
								<a href="#" id="button_add_user" class="bb_button bb_small bb_green">
									<span class="icon-disk" style="margin-right: 5px"></span>Add User
								</a>
							</div>
						</li>	
					</ul>
					<input type="hidden" name="submit_form" value="1" />
					</form>
					
				</div> <!-- /end of content_body -->	
			
			</div><!-- /.post -->
		</div><!-- /#content -->

 
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/add_user.js"></script>
EOT;

	require('includes/footer.php'); 
?>
