<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('../includes/init.php');
require('../config.php');
require('../includes/db-core.php');
require('../includes/helper-functions.php');
require('../includes/filter-functions.php');
require('../includes/post-functions.php');
require('../lib/password-hash.php');
require('../portal-header.php');

//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

function processForm($dbh)
{
	if(!(
			isset($_POST['full_name'])
		&& 	isset($_POST['phone'])
		&&	isset($_POST['email'])
		&&	isset($_POST['username'])
	))
	{
		header("Location: /auditprotocol/business_info/index.php");
		exit;
	}

	$client_id 		= $_SESSION['la_client_client_id'];
	$user_id		= la_sanitize($_POST['user_id']);
	$full_name		= la_sanitize($_POST['full_name']);
	$phone			= la_sanitize(preg_replace("/[^0-9]/","",$_POST['phone']));
	$email			= la_sanitize($_POST['email']);
	$username		= la_sanitize($_POST['username']);

	$query_user = "SELECT `username`, `email` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$user_id;
	$sth3 		= la_do_query($query_user,array(),$dbh);
	$client_user_data = la_do_fetch_result($sth3);
	$username_db	=	$client_user_data['username'];
	$email_db		=	$client_user_data['email'];

	if($full_name == "")
	{
		$error = "Name cannot be blank";
		return $error;
	}

	if($phone == "")
	{
		$error = "Phone Number cannot be blank";
		return $error;
	}

	if($email == "")
	{
		$error = "Email cannot be blank";
		return $error;
	}

	if($username == "")
	{
		$error = "Username cannot be blank";
		return $error;
	}

	if(preg_match('/\s/',$username))
	{
		$error = "Username cannot contain a space";
		return $error;
	}

	if(strtolower($username_db) != strtolower($username)){

		//Check if username is available
		$query = "select count(username) total_user from `".LA_TABLE_PREFIX."ask_client_users` where username = ?";
		$params = array($username);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
			$error = 'This username address already being used.';
			return $error;
		}

	}

	if(strtolower($email_db) != strtolower($email)){

		//Check if username is available
		$query = "select count(email) total_user from `".LA_TABLE_PREFIX."ask_client_users` where email = ?";
		$params = array($email);
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['total_user'])){
			$error = 'This emailid address already being used.';
			return $error;
		}

	}

	$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `username` = :username, `full_name` = :full_name, `phone` = :phone, `email` = :email WHERE `client_user_id`= ".$user_id;
	la_do_query($query_update,array(':username' => $username, ':full_name' => $full_name, ':phone' => $phone, ':email' => $email),$dbh);

	//redirect
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$ssl_suffix = 's';
	} else {
		$ssl_suffix = '';
	}

	header("Location: view_user.php?user_id={$user_id}");
	exit;
}

if(isset($_POST['submit']))
{
	$error = processForm($dbh);
}

$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id`= ".$_GET['user_id'];
$sth3 = la_do_query($query_user,array(),$dbh);
$client_user_data = la_do_fetch_result($sth3);
$client_user_id = $client_user_data['client_user_id'];
$email		=	$client_user_data['email'];
$full_name	=	$client_user_data['full_name'];
$phone		=	$client_user_data['phone'];
$username	=	$client_user_data['username'];
?>
          <div class="content_body">
            <form action="" method="post" name="edit">
              <ul id="ms_main_list">
                <li>
                  <div id="ms_box_account" data-userid="1" class="ms_box_main gradient_blue">
                    <div class="ms_box_title">
                      <label class="choice">User Information</label>
                      <input type="hidden" name="user_id" value="<?php echo $client_user_id; ?>" />
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="full_name">Full Name: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the name of the new user."/></label>
                      <input required id="full_name" name="full_name" class="element text medium" value="<?php if(isset($_POST['full_name'])){echo $_POST['full_name'];}else{ echo $full_name; } ?>" type="text">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="phone">Phone Number: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the phone number of the new user."/></label>
                      <input required id="phone" name="phone" class="element text medium" value="<?php if(isset($_POST['phone'])){echo $_POST['phone'];}else{ echo $phone; } ?>" type="text">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="email">Email: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the email of the new user."/></label>
                      <input required id="email" name="email" class="element text medium" value="<?php if(isset($_POST['email'])){echo $_POST['email'];}else{ echo $email; } ?>" type="email">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="username">Username: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the new username of the user."/></label>
                      <input required id="username" name="username" class="element text medium" value="<?php if(isset($_POST['username'])){echo $_POST['username'];}else{ echo $username; } ?>" type="text">
                    </div>
                  </div>
                </li>
                <li style="padding:1em;">
                  <input type="submit" value="Update" name="submit" id="submit">
                </li>
              </ul>
              </p>
            </form>
          </div>
<?php
require('../../includes/footer.php');
?>
