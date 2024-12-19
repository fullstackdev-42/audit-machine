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
require('includes/filter-functions.php');
require('includes/post-functions.php');
require('lib/password-hash.php');

$dbh         = la_connect_db();
$la_settings = la_get_settings($dbh);

if($la_settings['portal_registration'] == 0) {
	header("location:index.php");
	exit();
}

$invitation_flag = false;
//add logic for 50% Rule on Passwords
$forced_password = false;
if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) )
  $forced_password = true;
// if form is submitted
if(isset($_POST['submit'])) {
	function processForm($forced_password) {
		if(!(isset($_POST['client_id']) && isset($_POST['full_name']) && isset($_POST['phone']) && isset($_POST['email']) && isset($_POST['username']) && isset($_POST['password1']) && isset($_POST['password2']))) {
			header("Location: /portal/client_register.php");
			exit;
		}

		$client_id    = la_sanitize($_POST['client_id']);
		$company_name = la_sanitize($_POST['company_name']);
		$full_name    = la_sanitize($_POST['full_name']);
		$phone        = la_sanitize(preg_replace("/[^0-9]/","",$_POST['phone']));
		$email        = la_sanitize($_POST['email']);
		$username     = la_sanitize($_POST['username']);

		if( $forced_password ) {
			$password_range = range(15, 20);
			$password_length = array_rand(array_flip($password_range));
			$password1 = $password2 = randomPassword($password_length);
		} else {
			$password1    = la_sanitize($_POST['password1']);
			$password2    = la_sanitize($_POST['password2']);
		}

		if($company_name == "") {
			$error = "Entity name cannot be blank";
			return $error;
		}
		if($full_name == "") {
			$error = "Name cannot be blank";
			return $error;
		}
		if($phone == "") {
			$error = "Phone Number cannot be blank";
			return $error;
		}
		if($email == "") {
			$error = "Email cannot be blank";
			return $error;
		}
		if($username == "") {
			$error = "Username cannot be blank";
			return $error;
		}
		if(preg_match('/\s/',$username)) {
			$error = "Username cannot contain a space";
			return $error;
		}
	
		//Registering user must enter a new entity name when performing a self-register
		$dbh         = la_connect_db();
		$la_settings = la_get_settings($dbh);
	
		if(!$_SESSION['INVITED_USER'] && isExistingEntityName($dbh, $company_name)) {
			$error = "The entity name you are attempting to use belongs to another member. Please contact them to receive an invitation to join their entity group or create a new unique entity group to gain system access.";
			return $error;
		}
	
		if($password1 == $password2) {
			$password = $password1;
		} else {
			$error = "The passwords do not match";
			return $error;
		}
		if(strlen($password) < 8) {
			$error = "The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.";
			return $error;
		}
		if(!preg_match("#[0-9]+#", $password)) {
			$error = "Password must include at least one number!";
			return $error;
		}
		if(!preg_match("#[a-z]+#", $password)) {
			$error = "Password must include at least one letter!";
			return $error;
		}
		if(!preg_match("#[A-Z]+#", $password)) {
			$error = "Password must include at least one CAPS!";
			return $error;
		}
		if(!preg_match("#\W+#", $password)) {
			$error = "Password must include at least one symbol!";
			return $error;
		}
		
		//Check if username is available
		$query  = "SELECT count(username) total_user FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE username = ?";
		$params = array($username);
		$sth    = la_do_query($query,$params,$dbh);
		$row    = la_do_fetch_result($sth);
		if(!empty($row['total_user'])){
			$error = 'This username is already being used.';
			return $error;
		}
		
		if($client_id == ""){
			$client_id = 0;
		}

		$hasher        = new Sha256Hash();
		$password_hash = $hasher->HashPassword($password);
	
		$_SESSION['ses_client_id']  = $client_id;
		$_SESSION['company_name']   = $company_name;
		$_SESSION['email']          = $email;
		$_SESSION['phone']          = $phone;
		$_SESSION['full_name']      = $full_name;
		$_SESSION['username']       = $username;
		$_SESSION['password_hash']  = $password_hash;	
		$_SESSION['password_plain'] = $password;
		
		header("Location: /portal/register_tsv_setup.php");
		exit;
	}
	$error = processForm($forced_password);
}

$client_id    = 0;
$company_name = NULL;
$user_email   = NULL;

if(isset($_GET['is_invited']) && !empty($_GET['is_invited']) && $_GET['is_invited'] == 1){
	// select all fields
	$invitation_flag = true;
	$client_id 	              = la_sanitize($_GET['company_id']);
	$client_id 	              = base64_decode($client_id);
	$company_name 	          = la_sanitize($_GET['company_name']);
	$company_name 	          = base64_decode($company_name);
	$user_email               = la_sanitize($_GET['user_email']);
	$user_email               = base64_decode($user_email);
	$user_name               = la_sanitize($_GET['user_name']);
	$user_name               = base64_decode($user_name);
	$_SESSION['INVITED_USER'] = true;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>IT Audit Machine Client Account Registration</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="index, nofollow" />
<meta name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
<link rel="stylesheet" type="text/css" href="css/main.css" media="screen" />
<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="css/ie7.css" media="screen" />
<![endif]-->
<!--[if IE 8]>
	<link rel="stylesheet" type="text/css" href="css/ie8.css" media="screen" />
<![endif]-->
<!--[if IE 9]>
	<link rel="stylesheet" type="text/css" href="css/ie9.css" media="screen" />
<![endif]-->
<link href="css/theme.css" rel="stylesheet" type="text/css" />
<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<style type="text/css">
.auto-style1 {
	font-weight: normal;
}
.auto-style2 {
	text-align: right;
}
</style>
</head>

<body>
<div id="bg" class="login_page">
  <div id="container">
    <div id="header">
	  <?php
	  if (!empty($la_settings['admin_image_url'])) {
	    $itauditmachine_logo_main = $la_settings['admin_image_url'];
	  } else {
	    $itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
	  }
	  ?>
	  <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
	  <div class="clear"></div>
	</div>
    <div id="main">
      <div id="content" style="margin: 0px!important;">
        <div class="post login_main">
          <div class="content_header">
            <div class="content_header_title">
              <div style="">
				  <img align="absmiddle" src="images/Cybervisor_64x64.png" style="width: 64px; height: 64px;float: left;padding-right: 5px">
				  <h3>Account Registration</h3>
				</div>
              <div style="clear: both; height: 1px"><br></div>
            </div>
          </div>
          <div class="content_body">
            <form method="POST" id="form" action="<?php echo noHTML($_SERVER['REQUEST_URI']); ?>" autocomplete="off">
              <div style="display:none;">
                <input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
              </div>
              <table>
              <?php if($invitation_flag) { ?>
                <tr>
                  <td style="width: 150px;">
				  	<label for="company_name">Entity Name:* </label>
				  </td>
                  <td>
				  	<input type="text" id="company_name" name="company_name" placeholder="Type your own entity name." value="<?php echo noHTML($company_name); ?>" disabled/>
				  	<input type="hidden" name="client_id" value="<?php echo noHTML($client_id); ?>" />
				  	<input type="hidden" name="company_name" value="<?php echo noHTML($company_name); ?>" />
				  </td>
                </tr>
              <?php } else { ?>
				<tr>
					<td><label for="company_name">Entity Name:* </label></td>
					<td>
					  <input type="text" id="company_name" name="company_name" placeholder="Type your own entity name." value="<?php if(isset($_POST['company_name'])){echo noHTML($_POST['company_name']);} ?>"/>
					  <input type="hidden" name="client_id" id="client_id" value="<?php if(isset($_POST['client_id'])){echo noHTML($_POST['client_id']);} ?>"/>
					</td>
				</tr>
              <?php } ?>
                <tr>
					<td><label for="full_name">Full Name:* </label></td>
					<td>
					<?php if($invitation_flag) { ?>
                  		<input type="hidden" name="full_name" value="<?php echo $user_name; ?>" />
						<input type="text" name="full_name" id="full_name" value="<?php if(isset($user_name)){echo noHTML($user_name);} ?>" required disabled/>
                  	<?php } else { ?>
                  		<input type="text" name="full_name" id="full_name"  value="<?php if(isset($_POST['full_name'])){echo noHTML($_POST['full_name']);} ?>" required placeholder="Jane Smith"/>
                  	<?php } ?>
                  </td>
                </tr>
                <tr>
                  <td><label for="phone">Phone Number:* </label></td>
                  <td><input type="text" name="phone" id="phone" maxlength="15" value="<?php if(isset($_POST['phone'])){echo noHTML($_POST['phone']);} ?>" required placeholder="(888) 896-6207"/></td>
                </tr>
                <tr>
                  <td>
				  	<label for="email">Email:* </label>
				  </td>
                  <td>
					<?php if($invitation_flag) { ?>
						<input type="hidden" name="email" value="<?php echo $user_email; ?>" />
						<input type="email" name="email" id="email" value="<?php if(isset($user_email)){echo noHTML($user_email);} ?>" required disabled/>
					<?php } else { ?>
						<input type="email" name="email" id="email" value="<?php if(isset($_POST['email'])){echo noHTML($_POST['email']);}?>" required/>
					<?php } ?>
				  </td>
				</tr>
				  <tr>
					<td><label for="username">Username:* </label></td>
					<td><input type="text" name="username" id="username" value="<?php if(isset($_POST['username'])){echo noHTML($_POST['username']);} ?>" required placeholder="Username" autocomplete="off" readonly/></td>
				  </tr>
				
                <tr class="not_forced">
                  <td><label for="password">Password:** </label></td>
                  <td><input type="password" name="password1" id="password1" pattern=".{8,}" required  title="Minimum 8 characters, CAPS, Symbol, and Number"  placeholder="A minimum of 8 characters, CAPS, Symbol, and Number" value="" autocomplete="off" readonly/></td>
                </tr>
                <tr class="not_forced">
                  <td><label for="password">Retype Password:* </label></td>
                  <td><input type="password" name="password2" id="password2" pattern=".{8,}" title="Minimum 8 characters long" required placeholder="Verify password." value="" autocomplete="off" readonly/></td>
                </tr>
              </table>
              <?php if(isset($error)) echo "<p id=\"error\">".$error."</p>\n"; ?>
            	<div style="float:left; width:100%; margin:10px 0 10px 0;">
	                <input type="submit" class="bb_button bb_green" value="Register" name="submit" id="submit">
	              	<button class="bb_button bb_green" style="float: left;border-radius: 2px;" onclick="window.location='./';return false;">Cancel</button>
				</div>
              <div>
              <?php if( $forced_password ) { ?>
                <div class="ms_box_email">
                    <h5>* Required fields.</h5>
                    <p><b>50% Rule on Passwords</b> is enabled. New password will be generated by system itself.</p>
                </div>
                <style type="text/css">
                    .not_forced {
                        display: none !important;
                    }
                </style>
            <?php } else { ?>
              <div class="ms_box_email not_forced">
              	<h5>* Required fields.</h5>
              	<h5>** The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.</h5>
              </div>
            <?php } ?>
              
            	
              </div>
            </form>
          </div>
        </div>
	</div>

<?php include_once("includes/footer.php"); ?>

<!-- BEGIN SEARCH SUGGESTIONS -->
<style>
#form table, #form table input {
	width: 100%;
}
#submit {
	float: left;
	border-radius: 2px;
	margin-right: 10px;
	margin-left: 145px;
}
.resultList {
	color: black !important;
	margin: 0;
	padding: 0;
	list-style: none;
}
.resultList li {
	color: black !important;
	cursor: pointer;
	text-align: left !important;
	background-color: white;
	border-bottom: 1px solid #ccc;
	border-right: 1px solid #ccc;
	border-left: 1px solid #ccc;
	height: 10px;
	width: 97%;
}
label {
	color: black;
	padding-right: 15px;
}
#resultsContainer {
	color: black;
}
</style>
<script type="text/javascript">
	$(document).ready(function() {
		$("input").click(function(e){
			$(this).attr("readonly", false);
		})
	})
</script>
<!-- END SEARCH SUGGESTIONS -->
