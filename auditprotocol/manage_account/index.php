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
require('../includes/check-client-session-ask.php');
require('../includes/users-functions.php');
require('../portal-header.php');

//Connect to the database
$dbh = la_connect_db();

//Get user information from database table
$user_id = $_SESSION['la_client_user_id'];
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ? ";
$sth2 = $dbh->prepare($query);
$params = array($user_id);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	exit;
}
$user_data	= la_do_fetch_result($sth2);

$email		=	la_sanitize($user_data['email']);
$full_name	=	la_sanitize($user_data['full_name']);
$phone		=	la_sanitize($user_data['phone']);
$username	=	la_sanitize($user_data['username']);

//If the session error is set apply it to a local variable
if(isset($_SESSION['error']))
{
	$error = $_SESSION['error'];
	unset($_SESSION['error']);
}
?>
					<div class="content_body">
		<form action="submit.php" method="post" name="edit" id="edit">


					<ul id="ms_main_list">
						<li>
							<div id="ms_box_account" data-userid="1" class="ms_box_main gradient_blue">
								<div class="ms_box_title">
									<label class="choice">Account Details</label>
								</div>
								<div class="ms_box_email">
									<label class="description" for="username">Username: <span class="required">*</span></label>
									<input id="username" name="username" class="element text medium" value="<?php echo $username; ?>" type="text">
								</div>
								<div class="ms_box_email">
									<label class="description" for="full_name">Full Name: <span class="required">*</span></label>
									<input id="full_name" name="full_name" class="element text medium" value="<?php echo $full_name; ?>" type="text">
								</div>
								<div class="ms_box_email">
									<label class="description" for="email">Email: <span class="required">*</span></label>
									<input id="email" name="email" class="element text medium" value="<?php echo $email; ?>" type="text">
								</div>
								<div class="ms_box_email">
									<label class="description" for="phone">Phone: <span class="required">*</span></label>
									<input id="phone" name="phone" class="element text medium" value="<?php echo $phone; ?>" type="text">
									<a id="ms_change_password" href="change_password/">Change Password</a>
								</div>
							</div>
						</li>
						<li style="padding:1em;">
							<a href="#" id="button_save_main_settings" class="bb_button bb_small bb_green" onclick="edit.submit();"><img src="../images/navigation/FFFFFF/24x24/Save.png">  Save Changes
							</a>
						</li>
					</ul>
				<p><?php
if(isset($error))
	echo "				<p id=\"error\">" . $error . "</p>\n";
?>
</p>
		</form>
							</div>
<?php
require('../includes/footer.php');
?>
