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

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

// check user privileges - does this user have admin pivileges to IT Audit Machine?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
	$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";
	header("Location: /auditprotocol/restricted.php");
	exit;
}

// if form submitted
if(!empty($_POST['submit_form'])) {
	if(!isset($_POST['user_email']) || !isset($_POST['user_name']) || !isset($_POST['company_name']) || !isset($_POST['client_id'])) {
    header("Location: /auditprotocol/manage_users.php?active_tab=user_tab");
		exit;
	}
	
	// get all required inputs
	$user_input['user_name'] 	  = trim($_POST['user_name']);
	$user_input['user_email']   = strtolower(trim($_POST['user_email']));
  $user_input['company_name'] = $_POST['company_name'];
  $user_input['client_id']    = $_POST['client_id'];
	$user_input                 = la_sanitize($user_input); // clean the inputs

  $error = null;
  if($user_input['user_name'] == ""){
    $error = "Please enter user name.";
  }
	if($user_input['user_email'] == ""){
		$error .= "<br/>Email cannot be blank.";
	}elseif(!filter_var($user_input['user_email'], FILTER_VALIDATE_EMAIL)){
		$error .= "<br/>Please enter valid email address.";
  }

  if($user_input['client_id'] == 0){
    $error .= "<br/>Entity cannot be blank.";
  }
		
	if (empty($error)) {		
    // BEGIN - check if email is available
		$query  = "SELECT count(email) total_user FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE email = ?";
		$params = array($user_input['user_email']);
		$sth    = la_do_query($query, $params, $dbh);
		$row    = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
      $error = 'This email address is already in use.';
    }
    // END - check if email is available
  }

  if(empty($error)){
    // username is available - so insert into database
    $query  = "INSERT INTO `".LA_TABLE_PREFIX."ask_client_users` (`client_id`, `email`, `full_name`, `is_invited`, `is_admin`, `tstamp`) VALUES (?, ?, ?, ?, ?, ?);";
    $params = array($user_input['client_id'], $user_input['user_email'], $user_input['user_name'], 1, 1, $_SERVER['REQUEST_TIME']);
    la_do_query($query, $params, $dbh);
    $user_id = (int) $dbh->lastInsertId();

    $query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
    $sth_entity = la_do_query($query_entity, array($user_input['client_id']), $dbh);
    $row_entity = la_do_fetch_result($sth_entity);

    //send add admin user notification
    if($la_settings['enable_registration_notification']){
      $login_user = $_SESSION['email'];
      $site_name = "https://".$_SERVER['SERVER_NAME'];
      $subject = "Continuum GRC Account Management Notification";
      $content = "<h2>Continuum GRC Account Management Notification</h2>";
      $content .= "<h3>Administrative user ".$login_user." has added a new portal user in ".$site_name.".</h3>";
            $content .= "<hr/>";
            $content .= "<h3>User Details:</h3>";
            $content .= "<table>";
            $content .= "<tr><td style='width:100px;'>User ID:</td><td>{$user_id}</td></tr>";
            $content .= "<tr><td style='width:100px;'>User Name:</td><td>{$user_input['user_name']}</td></tr>";
            $content .= "<tr><td style='width:100px;'>Email:</td><td>{$user_input['user_email']}</td></tr>";
            $content .= "<tr><td style='width:100px;'>Entity:</td><td>{$row_entity['company_name']}</td></tr>";
            $content .= "</table>";
      sendUserManagementNotification($dbh, $la_settings, $subject, $content);
    }
    // Send email to user with the invite information and link to complete registration
    sendUserInviteNotification($dbh, $user_input['user_name'], $user_input['user_email'], $row_entity['company_name'], $user_input['client_id']);

    header("Location: /auditprotocol/manage_users.php?active_tab=user_tab");    
    exit;
  }
}

$_SESSION['LA_ERROR'] = $error;
$current_nav_tab = 'manage_users';
require('includes/header.php'); 
?>

<div id="content" class="full">
  <div class="post add_user">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2>
            <a class="breadcrumb" href='manage_users.php'>Portal User Manager</a>
            <img src="images/icons/resultset_next.gif" />
             Add Portal User
          </h2>
          <p>Create a New Portal User</p>
        </div>
        <div style="float: right;margin-right: 0px;padding-top: 26px; "> <a href="add_entity.php" style="color: #3661A1 !important; font-size: 120%;"> <strong>Add New Entity</strong></a> </div>
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
            <div id="au_box_user_profile" class="au_box_main gradient_blue">
              <div class="au_box_meta">
								<h1>1.</h1>
                <h6>User Information</h6>
              </div>
              <div class="au_box_content" style="padding-bottom: 15px">
                <label class="description <?php if(!empty($error_messages['user_name'])){ echo 'label_red'; } ?>" for="au_user_name">Full Name <span class="required">*</span></label>
                <input id="user_name" name="user_name" class="element text large" value="<?php echo noHTML($user_input['user_name']); ?>" type="text">
                <?php
                if(!empty($error_messages['user_name'])){
                  echo '<span class="au_error_span">'.$error_messages['user_name'].'</span>';
                }
                ?>
                <label class="description <?php if(!empty($error_messages['user_email'])){ echo 'label_red'; } ?>" for="au_user_email">Email Address <span class="required">*</span></label>
                <input id="user_email" name="user_email" class="element text large" value="<?php echo noHTML($user_input['user_email']); ?>" type="text">
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
            <div id="au_box_privileges" class="au_box_main gradient_blue">
              <div class="au_box_meta">
                <h1>2.</h1>
                <h6>Select User's Entity</h6>
              </div>
              <div class="au_box_content" style="padding-top: 35px;min-height: 50px;">
                <input type="text" id="company_name" name="company_name" placeholder="Begin typing to see entities."/>
                <input type="hidden" name="client_id" id="client_id" value="0"/>
                <div id="resultsContainer"></div>
              </div>
            </div>
          </li>
          <li class="ps_arrow"><img src="images/icons/33_red.png" /></li>
          <li>
            <div>
              <a href="#" id="button_add_user" class="bb_button bb_small bb_green"> 
              <img src="images/navigation/FFFFFF/24x24/Add_user.png">
               Send User Invite 
              </a>
            </div>
          </li>
        </ul>
        <input type="hidden" name="submit_form" value="1" />
      </form>
    </div>
  </div>
</div>

<!-- BEGIN SEARCH SUGGESTIONS -->
<style> 
#company_name { 
  border-bottom: 1px solid #ddd;
  border-left: 1px solid #c3c3c3;
  border-right: 1px solid #c3c3c3;
  border-top: 1px solid #7c7c7c;
  color: #333;
  font-size: 100%;
  margin: 0;
  padding: 3px 0px;
  width: 90%;
} 
#password1 { 
	border: 0.5px solid #9A9A9A !important; 
} 
#password2 { 
	border: 0.5px solid #9A9A9A !important; 
} 
.resultList { 
	color: black !important; 
  margin: 0; 
  padding: 0; 
  list-style: none; 
  width: 90% !important;
} 
.resultList li {
  color: black !important;
  cursor: pointer;
  text-align: left !important;
  background-color: white;
  border-bottom: 1px solid #ccc;
  border-right: 1px solid #ccc;
  border-left: 1px solid #ccc;
	height: 20px;
}
label {
	color: black;
	padding-right: 15px;
}
#resultsContainer {
	color: black;
}
</style>

<script src="../itam-shared/js/entity-search-suggestions.js"></script>
<!-- END SEARCH SUGGESTIONS -->

<?php
$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/add_user.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
EOT;

require('includes/footer.php'); 
?>
