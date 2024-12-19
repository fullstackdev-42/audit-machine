<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('../../includes/init.php');
require('../../config.php');
require('../../includes/db-core.php');
require('../../includes/helper-functions.php');
require('../../includes/filter-functions.php');
require('../../includes/check-client-session-ask.php');
require('../../includes/users-functions.php');
require('../../portal-header.php');

//Connect to the database
$dbh = la_connect_db();

//Get user id from session
$user_id = $_SESSION['la_client_user_id'];

//If the session error is set apply it to a local variable
if(isset($_SESSION['error']))
{
	$error = $_SESSION['error'];
	unset($_SESSION['error']);
}

//Get user information from database table
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`=?";
$sth2 = $dbh->prepare($query);
$params = array($user_id);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	exit;
}
$user_data	= la_do_fetch_result($sth2);

$full_name	=	la_sanitize($user_data['full_name']);
$username	=	la_sanitize($user_data['username']);

?>
					<div class="content_body">
		<form action="submit.php" method="post" name="edit" id="edit">
					<ul id="ms_main_list">
						<li>
							<div id="ms_box_account" class="ms_box_main gradient_blue">
								<div class="ms_box_title">
									<label class="choice">Update Password</label>
								</div>
								<div class="ms_box_email">
									<label class="description" for="username">Username: <span class="required">*</span></label>
									<input title="Username cannot be changed" type="text" readonly value="<?php echo $username; ?>" name="username" id="username" />
								</div>
								<div class="ms_box_email">
									<label class="description" for="password">Current Password: <span class="required">*</span></label>
									<input title="Type your current password" type="password" name="password" id="password">
								</div>
								<div class="ms_box_email">
									<label class="description" for="new_password">New Password: <span class="required">*</span></label>
									<input type="password" name="new_password" id="new_password">
								</div>
								<div class="ms_box_email">
									<label class="description" for="retype">Retype Password: <span class="required">*</span></label>
									<input type="password" name="retype" id="retype">
								</div>
							</div>
						</li>
						<li style="padding:1em;">
							<a href="#" id="button_save_main_settings" class="bb_button bb_small bb_green" onclick="edit.submit();">
								<img src="../../images/navigation/FFFFFF/24x24/Save.png"> Submit
							</a>
							<input type="submit" style="display:none;"/>
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
require('../../includes/footer.php');
?>
