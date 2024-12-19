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
	require('includes/helper-functions.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('includes/check-session.php');
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
	
	if(la_is_form_submitted()){ //if form submitted
		//get all required inputs
		$user_input['client_id'] = trim($_POST['au_client_id']);
		$user_input['client_name'] = trim($_POST['au_client_name']);
		$user_input['client_full_name']	= trim($_POST['au_client_full_name']);
		$user_input['client_email']	= trim($_POST['au_client_email']);
		$user_input['client_phone']	= trim($_POST['au_client_phone']);
		$user_input['client_description']	= trim($_POST['au_client_description']);
		
		//clean the inputs
		$user_input = la_sanitize($user_input);

		//validate inputs
		$error_messages = array();

		//validate name
		if(empty($user_input['client_name'])){
			$error_messages['client_name'] = 'This field is required. Please enter a name.';
		}

		//validate description
		if(empty($user_input['client_description'])){
			$error_messages['client_description'] = 'This field is required. Please enter description.';
		}

		//validate email
		if($user_input['client_email'] != ""){
			if(la_validate_email(array($user_input['client_email'])) !== true){
				$error_messages['client_email'] = "The email address entered is not in correct format.<br>";
			}
		}

		//validate phone number
		if($user_input['client_phone'] != "") {			
			if(la_validate_simple_phone(array($user_input['client_phone'])) !== true){
				$error_messages['client_phone'] = la_validate_simple_phone(array($user_input['client_phone']))."<br>";
			}
		}

		if(!empty($error_messages)){
			$_SESSION['LA_ERROR'] = 'Please correct the marked field(s) below.';
		}else{
			//everything is validated, continue creating entity
			$query = "UPDATE `".LA_TABLE_PREFIX."ask_clients` SET `company_name` = ?, `contact_email` = ?, `contact_phone` = ?, `contact_full_name` = ?, `entity_description` = ? WHERE `client_id` = ?;";
			$params = array($user_input['client_name'], $user_input['client_email'], $user_input['client_phone'], $user_input['client_full_name'], $user_input['client_description'], $user_input['client_id']);
			
			la_do_query($query,$params,$dbh);

			//redirect to manage_users page and display success message
			$_SESSION['LA_SUCCESS'] = 'Entity info has been updated successfully.';

			$ssl_suffix = la_get_ssl_suffix();						
			header("Location: edit_entity.php?entity_id=".$user_input['client_id']);
			exit;
		}
	}
	
	$client_user_data = NULL;
	
	if(isset($_GET['entity_id'])){
		$entity_id = la_sanitize($_GET['entity_id']);
		//Get user info
		$query = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
		$result = la_do_query($query, array($entity_id), $dbh);
		$client_user_data = la_do_fetch_result($result);
	}
	
	$current_nav_tab = 'manage_users';
	require('includes/header.php'); 	
?>

<div id="content" class="full">
  <div class="post add_user">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2><a class="breadcrumb" href='manage_users.php'>Portal Entity</a> <img src="images/icons/resultset_next.gif" /> Edit Entity</h2>
          <p>Edit entity</p>
        </div>
        <!--<div style="float: right;margin-right: 0px;padding-top: 26px;"> <a href="add_user_bulk.php" id="add_user_bulk_link" class=""> Switch to <strong>Bulk Add Users</strong> Mode </a> </div>-->
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <form id="add_user_form" method="post" action="<?php echo noHTML($_SERVER['REQUEST_URI']); ?>">
		<input name="au_client_id" value="<?php echo noHTML($client_user_data['client_id']); ?>" type="hidden">
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
              <div class="au_box_content" style="padding-bottom: 15px;">
                <label class="description <?php if(!empty($error_messages['client_name'])){ echo 'label_red'; } ?>" for="au_client_name">Entity Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Entity name of the entity."/></label>
                <input id="au_client_name" name="au_client_name" class="element text large" value="<?php echo noHTML($client_user_data['company_name']); ?>" type="text">
                <?php
					if(!empty($error_messages['client_name'])){
						echo '<span class="au_error_span">'.$error_messages['client_name'].'</span>';
					}
				?>
				<label class="description <?php if(!empty($error_messages['client_full_name'])){ echo 'label_red'; } ?>" for="au_client_full_name">Contact Name <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Full name of the entity."/></label>
                <input id="au_client_full_name" name="au_client_full_name" class="element text large" value="<?php echo noHTML($client_user_data['contact_full_name']); ?>" type="text">
                <?php
					if(!empty($error_messages['client_full_name'])){
						echo '<span class="au_error_span">'.$error_messages['client_full_name'].'</span>';
					}
				?>
				<label class="description <?php if(!empty($error_messages['client_email'])){ echo 'label_red'; } ?>" for="au_client_email">Contact Email Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Email Address of the entity."/></label>
                <input id="au_client_email" name="au_client_email" class="element text large" value="<?php echo noHTML($client_user_data['contact_email']); ?>" type="text">
                <?php
					if(!empty($error_messages['client_email'])){
						echo '<span class="au_error_span">'.$error_messages['client_email'].'</span>';
					}
				?>				
				<label class="description <?php if(!empty($error_messages['client_phone'])){ echo 'label_red'; } ?>" for="au_client_phone">Contact Phone Number <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Phone number of the entity."/></label>
                <input id="au_client_phone" name="au_client_phone" class="element text large" value="<?php echo noHTML($client_user_data['contact_phone']); ?>" type="text">
                <?php
					if(!empty($error_messages['client_phone'])){
						echo '<span class="au_error_span">'.$error_messages['client_phone'].'</span>';
					}
				?>
                <label class="description <?php if(!empty($error_messages['client_description'])){ echo 'label_red'; } ?>" for="au_client_description">Description <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Please enter a simple description of your entity."/></label>
                <textarea id="au_client_description" name="au_client_description" style="width: 90%;" rows="3"><?php echo noHTML($client_user_data['entity_description']); ?></textarea>
                <?php
					if(!empty($error_messages['client_description'])){
						echo '<span class="au_error_span">'.$error_messages['client_description'].'</span>';
					}
				?>
                 </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <li>
            <div> <a href="#" id="button_add_user" class="bb_button bb_small bb_green"> 
              <img src="images/navigation/FFFFFF/24x24/Save.png"> Save Changes </a> </div>
          </li>
        </ul>
        <input type="hidden" name="submit_form" value="1" />
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
EOT;

	require('includes/footer.php'); 