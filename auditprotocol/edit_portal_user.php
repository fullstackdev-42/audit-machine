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

$user_id = (int) trim($_GET['user_id']);

//check user privileges, is this user has privilege to administer IT Audit Machine?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
	$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

	$ssl_suffix = la_get_ssl_suffix();						
	header("Location: restricted.php");
	exit;
}
	
if(la_is_form_submitted()){ //if form submitted
	//echo '<pre>';print_r($_POST);echo '</pre>';die;
	//get all required inputs
	$user_input['user_name'] 		= $_POST['au_user_name'];
	$user_input['user_email'] 		= strtolower($_POST['au_user_email']);
	$user_input['avatar_url'] 		= $_POST['avatar_url'];
	$user_input['user_phone'] 		= $_POST['au_user_phone'];
	$user_input['job_classification'] = $_POST['au_job_classification'];
	$user_input['job_title'] 		= $_POST['au_job_title'];
	$user_input['about_me'] 		= $_POST['au_about_me'];
	$user_input['primary_entity']   = $_POST['primary_entity'];
	$user_input['user_entities'] 	= $_POST['form_for_selected_entity'] == NULL ? array() : $_POST['form_for_selected_entity'];
	$user_input['user_id'] 			= (int) $_POST['user_id'];
	if(!in_array($user_input['primary_entity'], $user_input['user_entities'])){
		array_push($user_input['user_entities'], $user_input['primary_entity']);
	}
	if(empty($user_input['user_id'])){
		die('User ID required.');
	}

	//only administrator can modify himself
	if($user_input['user_id'] == 1 && $_SESSION['la_user_id'] != 1){
		die("Access Denied. You don't have permission to edit Main Administrator.");
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
			$query = "select count(email) total_user from `".LA_TABLE_PREFIX."ask_client_users` where email = ? and client_user_id <> ? and `status` = 0";
			
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
		/***************************************/
		/*			fetch all company		   */
		/***************************************/
		$query = "SELECT `client_id` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
		$sth = la_do_query($query, array($user_id), $dbh);
		while($row = la_do_fetch_result($sth)){
			$client_id = $row['client_id'];
		}
		$companyIDStr     = getOtherEntityIDs($dbh, $user_input['user_id'], $client_id);
		$userEntities     = explode(",", $companyIDStr);

		$query_com = "select `client_id`, `company_name` from ".LA_TABLE_PREFIX."ask_clients ORDER BY `company_name`";
		$sth_com = la_do_query($query_com,array(),$dbh);
		$select_primary_ent = '';
		$select_other_ent = '';
		while($row = la_do_fetch_result($sth_com)){
			$selection_1 = '';
			$selection_2 = '';
			if($row['client_id'] == $client_id){
				$selection_1 = ' selected="selected"';
			}
			if(in_array($row['client_id'], $userEntities)){
				$selection_2 = ' selected="selected"';
			}
			$select_primary_ent .= '<option '.$selection_1.' value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
			$select_other_ent .= '<option '.$selection_2.' value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
		}
	}else{
		//everything is validated, continue updating user
		//compare the old and new data, if they are different, email account management alert notification
		if($la_settings['enable_registration_notification']){
			$changed_content = "";
			$query_get_old_data = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
			$sth_get_old_data = la_do_query($query_get_old_data, array($user_input['user_id']), $dbh);
			$row_old_data = la_do_fetch_result($sth_get_old_data);
			if($row_old_data['full_name'] != $user_input['user_name']){
				$changed_content .= "<tr><td style='width: 300px;'>User Name:</td><td style='width: 200px;'>{$row_old_data['full_name']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_name']}</td></tr>";
			}
			if($row_old_data['email'] != $user_input['user_email']){
				$changed_content .= "<tr><td style='width: 300px;'>Email:</td><td style='width: 200px;'>{$row_old_data['email']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_email']}</td></tr>";
			}
			if($row_old_data['phone'] != $user_input['user_phone']){
				$changed_content .= "<tr><td style='width: 300px;'>Phone:</td><td style='width: 200px;'>{$row_old_data['phone']}</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>{$user_input['user_phone']}</td></tr>";
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
			$userEntities = getEntityIds($dbh, $user_input['user_id']);
			if(sizeof(array_diff($userEntities, $user_input['user_entities'])) > 0){
				$query_entities = "select `client_id`, `company_name` from ".LA_TABLE_PREFIX."ask_clients ORDER BY `company_name`";
				$sth_entities = la_do_query($query_entities,array(),$dbh);
				$changed_content .= "<tr><td style='width: 300px;'>Assigned Entities:</td><td style='width: 200px;'>";
				$old_entities = "";
				$new_entities = "";
				while($row = la_do_fetch_result($sth_entities)){
					if(in_array($row['client_id'], $userEntities)){
						$old_entities .= "&bull;".$row['company_name']."<br/>";
					}
					if(in_array($row['client_id'], $user_input['user_entities'])){
						$new_entities .= "&bull;".$row['company_name']."<br/>";
					}
				}
				$changed_content .= $old_entities;
				$changed_content .= "</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width: 200px;color: #0085CC;'>";
				$changed_content .= $new_entities;
				$changed_content .= "</td></tr>";
			}

			if($changed_content != ""){
				$login_user = $_SESSION['email'];
				$site_name = "https://".$_SERVER['SERVER_NAME'];
				$subject = "Continuum GRC Account Management Alert";
				$content = "<h2>Continuum GRC Account Management Alert</h2>";
				$content .= "<h3>Administrative user ".$login_user." has edited a portal user ".$row_old_data['email']." in ".$site_name.".</h3>";
				$content .= "<hr/>";
				$content .= "<h3>Changed Information:</h3>";
				$content .= "<table>";
				$content .= $changed_content;
				$content .= "</table>";
				sendUserManagementNotification($dbh, $la_settings, $subject, $content);
			}
		}

		//update ap_ask_client_users table
		$query = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `client_id`=?, `email`=?, `full_name`=?, `phone`=?, `job_classification`=?, `job_title`=?, `about_me`=? WHERE `client_user_id` = ?";
		$params = array($user_input['primary_entity'], $user_input['user_email'], $user_input['user_name'], $user_input['user_phone'], $user_input['job_classification'], $user_input['job_title'], $user_input['about_me'], $user_input['user_id']);
		la_do_query($query,$params,$dbh);
		
		$query_entity = "delete from `".LA_TABLE_PREFIX."entity_user_relation` WHERE `client_user_id` = ?";
		la_do_query($query_entity, array($user_input['user_id']), $dbh);

		foreach($user_input['user_entities'] as $v){
			if($v != 0){
				$query_entity = "INSERT INTO `".LA_TABLE_PREFIX."entity_user_relation` (`entity_user_relation_id`, `entity_id`, `client_user_id`) VALUES (NULL, ?, ?);";
				la_do_query($query_entity, array($v, $user_input['user_id']), $dbh);
			}
		}
		
		//redirect to manage_users page and display success message
		$_SESSION['LA_SUCCESS'] = 'User #'.$user_input['user_id'].' has been updated.';

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: edit_portal_user.php?user_id=".$user_input['user_id']);
		exit;
	}
} else {
	//populate user data

	if(empty($user_id)){
		die("Invalid Request");
	}

	$user_input['user_id'] = $user_id;

	//get user profile data
	$query = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE client_user_id=?";
	$params = array($user_id);
			
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$user_input['user_name'] = $row['full_name'];
	$user_input['user_email'] = $row['email'];
	$user_input['user_phone'] = $row['phone'];
	$user_input['job_classification'] = $row['job_classification'];
	$user_input['job_title'] = $row['job_title'];
	$user_input['about_me'] = $row['about_me'];
	$user_input['avatar_url'] = $row['avatar_url'];

	/***************************************/
	/*			fetch all company		   */
	/***************************************/
	$query = "SELECT `client_id` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
	$sth = la_do_query($query, array($user_id), $dbh);
	while($row = la_do_fetch_result($sth)){
		$client_id = $row['client_id'];
	}
	$companyIDStr     = getOtherEntityIDs($dbh, $user_input['user_id'], $client_id);
	$userEntities     = explode(",", $companyIDStr);

	$query_com = "select `client_id`, `company_name` from ".LA_TABLE_PREFIX."ask_clients ORDER BY `company_name`";
	$sth_com = la_do_query($query_com,array(),$dbh);
	$select_primary_ent = '';
	$select_other_ent = '';
	while($row = la_do_fetch_result($sth_com)){
		$selection_1 = '';
		$selection_2 = '';
		if($row['client_id'] == $client_id){
			$selection_1 = ' selected="selected"';
		}
		if(in_array($row['client_id'], $userEntities)){
			$selection_2 = ' selected="selected"';
		}
		$select_primary_ent .= '<option '.$selection_1.' value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
		$select_other_ent .= '<option '.$selection_2.' value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
	}

	if($row['is_saml_user']) {
		//redirect to manage_users page and display success message
		$_SESSION['LA_SUCCESS'] = 'User #'.$user_input['user_id'].' can not be updated.';

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: edit_portal_user.php?user_id=".$user_input['user_id']);
		exit;
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
          <h2><a class="breadcrumb" href='manage_users.php'>Users Manager</a> <img src="images/icons/resultset_next.gif" /> <a class="breadcrumb" href='view_portal_user.php?user_id=<?php echo $user_input['user_id']; ?>'>#<?php echo $user_input['user_id']; ?></a> <img src="images/icons/resultset_next.gif" /> Edit</h2>
          <p>Editing portal user #<?php echo $user_input['user_id']; ?></p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <form id="add_user_form" method="post" action="<?php echo noHTML($_SERVER['REQUEST_URI']); ?>">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
		  <input type="hidden" name="user_id" value="<?php echo $user_input['user_id']; ?>" />
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
                <label class="description <?php if(!empty($error_messages['user_name'])){ echo 'label_red'; } ?>" for="au_user_name">Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Full name of the new user."/></label>
                <input id="au_user_name" name="au_user_name" class="element text large" value="<?php echo noHTML($user_input['user_name']); ?>" type="text">
                <?php
					if(!empty($error_messages['user_name'])){
						echo '<span class="au_error_span">'.$error_messages['user_name'].'</span>';
					}
				?>
                <label class="description <?php if(!empty($error_messages['user_email'])){ echo 'label_red'; } ?>" for="au_user_email">Email Address <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The email address must be unique. No two users can have the same email address."/></label>
                <input id="au_user_email" name="au_user_email" class="element text large" value="<?php echo noHTML($user_input['user_email']); ?>" type="text">
                <?php
					if(!empty($error_messages['user_email'])){
						echo '<span class="au_error_span">'.$error_messages['user_email'].'</span>';
					}
				?>
				<label class="description <?php if(!empty($error_messages['user_phone'])){ echo 'label_red'; } ?>" for="au_user_phone">Phone <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Phone number of the user. Recommended format: +1-888-896-6207"/></label>
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
              </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <li>
            <div id="au_box_privileges" class="au_box_main gradient_blue">
              <div class="au_box_meta">
                <h1>2.</h1>
                <h6>Edit Entities</h6>
              </div>
              <div class="au_box_content" style="padding-bottom: 10px; min-height: 90px;">
				<!-- <input type="text" id="company_name" name="company_name" placeholder="Begin typing to see entities."/>
				<input type="hidden" name="selected_entity" id="client_id" />
                <input type="hidden" name="form_for_selected_entity[]" id="user_entity" /> -->
                <label class="description" for="primary_entity">Primary Entity <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Assign a user as a mananger of the entity."/></label>
                <select id="primary_entity" name="primary_entity" class="element" style="width: 200px;">
                	<?php echo $select_primary_ent; ?>
                </select>
                <label class="description" for="other_entities">Other Entities <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Assign a user to other entities."/></label>
                <select multiple id="other_entities" name="form_for_selected_entity[]" class="element" style="width: 200px; height: 100px;">
                	<?php echo $select_other_ent; ?>
                </select>
                <div id="resultsContainer"></div>
				<?php
					if(!empty($error_messages['user_entities'])){
						echo '<span class="au_error_span">'.$error_messages['user_entities'].'</span>';
					}
				?>
              </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
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
			"is_admin": 0,
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
?>