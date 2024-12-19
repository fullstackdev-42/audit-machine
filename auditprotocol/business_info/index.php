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

//part 1: business information

/*$query = "update `".LA_TABLE_PREFIX."ask_client_users` set status = 0 where client_user_id = ?";
$params = array($_SESSION['la_client_user_id']);
la_do_query($query,$params,$dbh);*/

//Get user information from database table
$client_id = $_SESSION['la_client_client_id'];
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id`= ?";
$sth2 = $dbh->prepare($query);
$params = array($client_id);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	exit;
}
$user_data	= la_do_fetch_result($sth2);

$contact_email		=	la_sanitize($user_data['contact_email']);
$contact_full_name	=	la_sanitize($user_data['contact_full_name']);
$contact_phone		=	la_sanitize($user_data['contact_phone']);
$company_name		=	la_sanitize($user_data['company_name']);
$_SESSION['la_client_company_name'] = $company_name;

//If the session error is set apply it to a local variable
if(isset($_SESSION['error']))
{
	$error = $_SESSION['error'];
	unset($_SESSION['error']);
}
?>
          <div class="content_body">
            <form action="submit.php" method="post" name="edit">
              <ul id="ms_main_list">
                <li>
                  <div id="ms_box_account" data-userid="1" class="ms_box_main gradient_blue">
                    <div class="ms_box_title">
                      <label class="choice">My Business Profile</label>
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="business">Business Name: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the name of your business."/></label>
                      <input id="business" name="business" class="element text medium" value="<?php echo $company_name; ?>" type="text">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="contact_full_name">Contact Name: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the name of the contact for your business."/></label>
                      <input id="contact_full_name" name="contact_full_name" class="element text medium" value="<?php echo $contact_full_name; ?>" type="text">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="contact_email">Contact Email: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the email of the contact for your business."/></label>
                      <input id="contact_email" name="contact_email" class="element text medium" value="<?php echo $contact_email; ?>" type="text">
                    </div>
                    <div class="ms_box_email">
                      <label class="description" for="contact_phone">Contact Phone: <span class="required">*</span> <img class="helpmsg" src="../images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the phone number of the contact for your business."/></label>
                      <input id="contact_phone" name="contact_phone" class="element text medium" value="<?php echo $contact_phone; ?>" type="text">
                    </div>
                  </div>
                </li>
                <li style="padding:1em;"> <a href="#" id="button_save_main_settings" class="bb_button bb_small bb_green" onclick="edit.submit();"><img src="../images/navigation/FFFFFF/24x24/Save.png">  Save Changes </a> </li>
              </ul>
              <p>
                <?php
if(isset($error))
	echo "				<p id=\"error\">" . $error . "</p>\n";
?>
              </p>
            </form>
            <h2>User Accounts</h2>
            <div id="entries_container">
              <?php

//part two users in current client account

//Get list of users
$query = "SELECT `client_user_id` FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_id`= ? AND `status` <> 2";
$sth2 = $dbh->prepare($query);
$params = array($client_id);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	exit;
}
$count = $sth2->rowCount();
$client_users = array();
for($i=0;$i<$count;$i++){
	$client_users_temp		= la_do_fetch_result($sth2);
	$client_users[$i]		= $client_users_temp['client_user_id'];
}
$table = "		<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" id=\"entries_table\" >\n";
$table .= "			<thead>\n				<tr>\n					<th style=\"width:30%;\">Username</th>\n					<th style=\"width:20%;\">Full Name</th>\n					<th style=\"width:12%;\">Phone</th>\n					<th style=\"width:30%;\">Email</th>\n <th style=\"width:8%;\">Status</th>\n				</tr>\n			</thead>\n			<tbody>\n";
foreach($client_users as $client_user)
{
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ? ";
	$sth3 = $dbh->prepare($query);
	$params = array($client_user);
	try{
		$sth3->execute($params);
	}catch(PDOException $e) {
		exit;
	}

	$client_user_data = la_do_fetch_result($sth3);
	$client_user_id = la_sanitize($client_user_data['client_user_id']);
	$email		=	la_sanitize($client_user_data['email']);
	$full_name	=	la_sanitize($client_user_data['full_name']);
	$phone		=	la_sanitize($client_user_data['phone']);
	$username	=	la_sanitize($client_user_data['username']);

	if(la_sanitize($client_user_data['is_invited']) == 1){
		$class = '';
		$status = 'Invited';
	}else{
		$class = ' class="tr-click"';
		if(la_sanitize($client_user_data['status']) == 0){
			$status = 'Active';
		}else if(la_sanitize($client_user_data['status']) == 1){
			$status = 'Suspended';
		}
	}

	$table .= '<tr style="cursor:poniter;" data-user-id="'.$client_user_id.'"'.$class.'><td>' . $username . '</td><td>' . $full_name . '</td><td>' . $phone . '</td><td>' . $email . '</td><td>' . $status . '</td></tr>';
}
$table .= "</tbody>\n</table>\n";

//Display the table
echo $table;
?>
            </div>
            <div style="padding:1em;"> <a href="add_users/" id="button_add_user" class="bb_button bb_small bb_green">
              <img src="../images/navigation/FFFFFF/24x24/Add_user.png">  Add a User </a> </div>
          </div>
<?php
require('../includes/footer.php');
?>
<script type="text/javascript">
$(document).ready(function() {
    $('tr.tr-click').click(function(){console.log(1);
		window.location = 'view_user.php?user_id='+$(this).attr('data-user-id');
	});
});
</script>
