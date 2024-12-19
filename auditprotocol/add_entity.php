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
require('includes/post-functions.php');
require('includes/filter-functions.php');
require('lib/password-hash.php');

$dbh         = la_connect_db();
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
	$sth    = la_do_query($query,$params,$dbh);
	$row    = la_do_fetch_result($sth);

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
	$user_input['user_name'] 		= trim($_POST['au_user_name']);
	$user_input['user_email'] 		= trim($_POST['au_user_email']);
	
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
		$error_messages['user_email'] = 'This field is required. Please enter description.';
	}

	if(!empty($error_messages)){
		$_SESSION['LA_ERROR'] = 'Please correct the marked field(s) below.';
	}else{
		//everything is validated, continue creating entity

		//insert into ap_users table
		$hasher = new Sha256Hash();
		$password_hash = $hasher->HashPassword($user_input['user_password']);

		$query = "INSERT INTO `".LA_TABLE_PREFIX."ask_clients` (`client_id`, `company_name`, `contact_email`, `contact_phone`, `contact_full_name`, `entity_description`) VALUES (NULL, ?, '', '', '', ?)";
		$params = array($user_input['user_name'], $user_input['user_email']);
		
		la_do_query($query,$params,$dbh);

		//redirect to manage_users page and display success message
		$_SESSION['LA_SUCCESS'] = 'A new entity has been added.';

		//send add entity notification
		if($la_settings['enable_registration_notification']){
			$login_user = $_SESSION['email'];
			$site_name = "https://".$_SERVER['SERVER_NAME'];
			$subject = "Continuum GRC Account Management Notification";
			$content = "<h2>Continuum GRC Account Management Notification</h2>";
			$content .= "<h3>Administrative user ".$login_user." has added a new entity in ".$site_name.".</h3>";
			$content .= "<hr/>";
        	$content .= "<h3>Entity Details:</h3>";
            $content .= "<table>";
            $content .= "<tr><td style='width:150px;'>Entity Name:</td><td>{$user_input['user_name']}</td></tr>";
            $content .= "<tr><td style='width:150px;'>Entity Description:</td><td>{$user_input['user_email']}</td></tr>";
            $content .= "</table>";
			sendUserManagementNotification($dbh, $la_settings, $subject, $content);
		}
		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: manage_users.php?active_tab=1");
		exit;
	}
}

$current_nav_tab = 'manage_users';
require('includes/header.php'); 
?>

<style>
.au_box_content {
    float: right;
    min-height: 112px;
    border-left: 0px dashed #8eaccf;
    text-align: left;
    padding-left: 0px;
}
</style>
<div id="content" class="full">
  <div class="post add_user">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2><a class="breadcrumb" href='manage_users.php'>Portal Entity</a> <img src="images/icons/resultset_next.gif" /> Add Entity</h2>
          <p>Create a new entity</p>
        </div>
        <!--<div style="float: right;margin-right: 0px;padding-top: 26px;"> <a href="add_user_bulk.php" id="add_user_bulk_link" class=""> Switch to <strong>Bulk Add Users</strong> Mode </a> </div>-->
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <form id="add_user_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
        <div style="display:none;">
          <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        </div>
        <ul id="au_main_list">
          <li>
            <div id="au_box_user_profile" class="au_box_main gradient_blue" style="display: flex; text-align: center;">
              <div class="au_box_content" style="padding-bottom: 25px; margin: 0 auto;">
                <label class="description <?php if(!empty($error_messages['user_name'])){ echo 'label_red'; } ?>" for="au_user_name">Name <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Full name of the new entity."/></label>
                <input id="au_user_name" name="au_user_name" class="element text large" value="<?php echo noHTML($user_input['user_name']); ?>" type="text">
                <?php
				if(!empty($error_messages['user_name'])){
					echo '<span class="au_error_span">'.$error_messages['user_name'].'</span>';
				}
				?>
                <label class="description <?php if(!empty($error_messages['user_email'])){ echo 'label_red'; } ?>" for="au_user_email">Description <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Please enter a simple description of your new entity."/></label>
                <textarea id="au_user_email" name="au_user_email" class="element text large" rows="5" style="height:150px;"><?php echo noHTML($user_input['user_email']); ?></textarea>
                <?php
				if(!empty($error_messages['user_email'])){
					echo '<span class="au_error_span">'.$error_messages['user_email'].'</span>';
				}
				?>
                 </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <li>
            <div> <a href="#" id="button_add_user" class="bb_button bb_small bb_green"> 
              <img src="images/navigation/FFFFFF/24x24/Add_user.png"> Add Entity </a> </div>
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
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
EOT;

require('includes/footer.php'); 
