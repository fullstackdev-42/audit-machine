<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com

More info at: http://lazarusalliance.com
********************************************************************************/

session_start();
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/filter-functions.php');
require('includes/post-functions.php');
require('lib/password-hash.php');

$dbh         = la_connect_db();
$la_settings = la_get_settings($dbh);

function processForm() {
	//redirect (based on https and/or http)
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$ssl_suffix = 's';
	} else {
		$ssl_suffix = '';
	}
	
	if(!(
			isset($_POST['company_name'])
		&&	isset($_POST['full_name'])
		&& 	isset($_POST['phone'])
		&&	isset($_POST['email'])
		&&	isset($_POST['username'])
		&&	isset($_POST['password1'])
		&&	isset($_POST['password2'])
	)){
		header("Location: client_register.php");
		exit;
	}
	
	$client_id		= (int)la_sanitize($_POST['client_id']);
	$company_name	= la_sanitize($_POST['company_name']);
	$full_name		= la_sanitize($_POST['full_name']);
	$phone			= la_sanitize(preg_replace("/[^0-9]/","",$_POST['phone']));
	$email			= la_sanitize($_POST['email']);
	$username		= la_sanitize($_POST['username']);
	$password1		= la_sanitize($_POST['password1']);
	$password2		= la_sanitize($_POST['password2']);

	if($company_name == "") {
		$error = "Entity name cannot be blank";
		return $error;
	}
	if(strpos($company_name, "'") !== false) {
		$company_name = str_replace ("'", "''", $company_name);
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

	$dbh         = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//Check if username is available
	$query  = "select count(username) total_user from `".LA_TABLE_PREFIX."ask_client_users` where username = ?";
	$params = array($username);
	$sth    = la_do_query($query,$params,$dbh);
	$row    = la_do_fetch_result($sth);

	if(!empty($row['total_user'])){
		$error = 'This username address already being used.';
		return $error;
	}

	$hasher        = new Sha256Hash();
	$password_hash = $hasher->HashPassword($password);
	
	$_SESSION['ses_client_id'] = $client_id;
	$_SESSION['company_name']  =  $company_name;
	$_SESSION['email']         =  $email;
	$_SESSION['phone']         =  $phone;
	$_SESSION['full_name']     =  $full_name;
	$_SESSION['username']      =  $username;
	$_SESSION['password_hash'] =  $password_hash;	
	
	header("Location: register_tsv_setup.php");
	exit;
}

if(isset($_POST['submit'])) {
	$error = processForm();
}

// -------

$client_id  = NULL;
$user_email = NULL;
$row_user   = NULL;

if(isset($_GET['company_id']) && !empty($_GET['company_id'])){
	// select all fields
	$client_id 	 = la_sanitize($_GET['company_id']);
	$client_id 	 = base64_decode($client_id);
	$user_email  = la_sanitize($_GET['user']);
	$user_email  = base64_decode($user_email);
	$query_user  = "select * from `".LA_TABLE_PREFIX."ask_clients` where client_id = ?";
	$params_user = array($client_id);
	$sth_user 	 = la_do_query($query_user,$params_user,$dbh);
	$row_user 	 = la_do_fetch_result($sth_user);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>IT Audit Machine Client Account Registration</title>
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
      <div id="content" style="margin: 0 0 20px !important;">
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
            <form method="POST" action="<?php echo noHTML($_SERVER['PHP_SELF']); ?>">
              <table>
              <?php
			  if($row_user){
			  ?>
              <input type="hidden" name="client_id" value="<?php echo $row_user['client_id']; ?>" />
                <tr>
                  <td style="width:150px;"><label for="company_name">Entity Name:* </label></td>
                  <td><input type="hidden" name="company_name" value="<?php echo $row_user['company_name']; ?>" /><?php echo $row_user['company_name']; ?></td>
                </tr>
              <?php  
			  }else{
			  ?>
              <input type="hidden" name="client_id" value="0" />
                <tr>
                  <td><label for="company_name">Entity Name:* </label></td>
                  <td><input type="text" name="company_name" id="company_name" value="<?php if(isset($_POST['company_name'])){echo $_POST['company_name'];} ?>" required placeholder="Continuum GRC" autofocus/></td>
                </tr>
              <?php  
			  }
			  ?>
                <tr>
                  <td><label for="full_name">Full Name:* </label></td>
                  <td><input type="text" name="full_name" id="full_name"  value="<?php if(isset($_POST['full_name'])){echo $_POST['full_name'];} ?>" required placeholder="Jane Smith"/></td>
                </tr>
                <tr>
                  <td><label for="phone">Phone Number:* </label></td>
                  <td><input type="text" name="phone" id="phone" maxlength="15" value="<?php if(isset($_POST['phone'])){echo $_POST['phone'];} ?>" required placeholder="888-896-6207"/></td>
                </tr>
              <?php
			  if($row_user){
			  ?>
                <tr>
                  <td><label for="email">Email:* </label></td>
                  <td><input type="hidden" name="email" value="<?php echo $user_email; ?>" /><?php echo $user_email; ?></td>
                </tr>
              <?php  
			  }else{
			  ?>
                <tr>
                  <td><label for="email">Email:* </label></td>
                  <td><input type="email" name="email" id="email" value="<?php if(isset($_POST['email'])){echo $_POST['email'];} ?>" required placeholder="user@domain.com"/></td>
                </tr>
              <?php  
			  }
			  ?>
                <tr>
                  <td><label for="username">Username:* </label></td>
                  <td><input type="text" name="username" id="username" value="<?php if(isset($_POST['username'])){echo $_POST['username'];} ?>" required placeholder="user@domain.com"/></td>
                </tr>
                <tr>
                  <td><label for="password">Password:** </label></td>
                  <td><input type="password" name="password1" id="password1" pattern=".{8,}" required  title="Minimum 8 characters, CAPS, Symbol, and Number"  placeholder="A minimum of 8 characters, CAPS, Symbol, and Number"/></td>
                </tr>
                <tr>
                  <td><label for="password">Retype Password:* </label></td>
                  <td><input type="password" name="password2" id="password2" pattern=".{8,}" title="Minimum 8 characters long" required placeholder="Verify your new password again please." /></td>
                </tr>
              </table>
              <?php
				if(isset($error))
				echo "<p id=\"error\">".$error."</p>\n";
			  ?>
                <div style="float:left; width:100%; margin:10px 0 10px 0;">
                <input type="submit" class="bb_button bb_green" style="float: left; border-radius: 4px; margin-right: 10px;" value="Register" name="submit" id="submit">
              	<button class="bb_button bb_green" style="float: left;border-radius: 2px;" onclick="window.location='./';return false;">Cancel</button>
  			  </div>
              <div>
              <h5>* Required fields.</h5>
              <h5>** The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.</h5>
              </div>
            </form>
          </div>
        </div>
      </div>
<?php include_once("includes/footer.php"); ?>
