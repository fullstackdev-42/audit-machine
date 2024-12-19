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
require('lib/swift-mailer/swift_required.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/users-functions.php');

//get an array containing id number of all filtered users id within ap_users table, based on $filter_data
function la_get_filtered_users_ids_new($dbh,$filter_data,$exclude_admin=true){
	
	$query = "SELECT `client_user_id` FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `status` <> 2 AND `is_invited`= '0'";
	$params = array();
	$sth = la_do_query($query,$params,$dbh);
	
	$filtered_user_id_array = array();
	
	while($row = la_do_fetch_result($sth)){
		$filtered_user_id_array[] = $row['client_user_id'];
	}

	return $filtered_user_id_array;
}

function getUserLastLogin($dbh, $user_id)
{
	$query = "SELECT `last_login`, `user_ip` FROM `ap_portal_user_login_log` WHERE `client_user_id` = '{$user_id}' ORDER BY `last_login` DESC LIMIT 1";
	$sth = la_do_query($query,array(),$dbh);
	$client_user_data = la_do_fetch_result($sth);
	if(isset($client_user_data['last_login']) && $client_user_data['last_login'] != ""){
		return $client_user_data;
	}
	return false;
}

//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$user_id = (int) trim($_GET['user_id']);
if(empty($user_id)){
	die("Invalid Request");
}
//add logic for 50% Rule on Passwords
$forced_password = false;
$newpassword = '';
if( isset($la_settings['enforce_rule_on_passwords']) && ($la_settings['enforce_rule_on_passwords'] == 1 ) ){
	$forced_password = true;
	$newpassword = 'someValueForJs:)';
}

$user_id = (int) trim($_GET['user_id']);
$nav 	 = isset($_GET['nav']) ? trim($_GET['nav']) : '';

if(isset($_POST['cron_settings'])){
	$account_suspension_strict_date_flag = isset($_POST['account_suspension_strict_date_flag']) ? true : false;
	$account_suspension_strict_date = (isset($_POST['account_suspension_strict_date']) && !empty($_POST['account_suspension_strict_date'])) ? strtotime($_POST['account_suspension_strict_date']) : 0;
	$account_suspension_inactive_flag = isset($_POST['account_suspension_inactive_flag']) ? true : false;
	$account_suspension_inactive = (isset($_POST['account_suspension_inactive']) && !empty($_POST['account_suspension_inactive'])) ? $_POST['account_suspension_inactive'] : 0;
	$suspended_account_auto_deletion_flag = isset($_POST['suspended_account_auto_deletion_flag']) ? true : false;
	$account_suspended_deletion = (isset($_POST['account_suspended_deletion']) && !empty($_POST['account_suspended_deletion'])) ? $_POST['account_suspended_deletion'] : 0;
	
	//compare the old and new data, if they are different, email account management alert notification
	if($la_settings['enable_registration_notification']){
		$changed_content = "";
		$query_get_old_data = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
		$sth_get_old_data = la_do_query($query_get_old_data, array($user_id), $dbh);
		$row_old_data = la_do_fetch_result($sth_get_old_data);
		if($row_old_data['account_suspension_strict_date_flag'] != $account_suspension_strict_date_flag){
			if($row_old_data['account_suspension_strict_date_flag'] == 0){
				$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".date('m/d/Y', $account_suspension_strict_date)."</td></tr>";
			} else {
				$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>".date('m/d/Y', $row_old_data['account_suspension_strict_date'])."</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
			}
		} else {
			if($row_old_data['account_suspension_strict_date_flag'] == 1){
				if($row_old_data['account_suspension_strict_date'] != $account_suspension_strict_date){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account after date:</td><td style='width:120px;'>".date('m/d/Y', $row_old_data['account_suspension_strict_date'])."</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".date('m/d/Y', $account_suspension_strict_date)."</td></tr>";
				}
			}
		}
		if($row_old_data['account_suspension_inactive_flag'] != $account_suspension_inactive_flag){
			if($row_old_data['account_suspension_inactive_flag'] == 0){
				$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspension_inactive." day(s)</td></tr>";
			} else {
				$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspension_inactive']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
			}
		} else {
			if($row_old_data['account_suspension_inactive_flag'] == 1){
				if($row_old_data['account_suspension_inactive'] != $account_suspension_inactive){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically suspend account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspension_inactive']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspension_inactive." day(s)</td></tr>";
				}
			}
		}
		if($row_old_data['suspended_account_auto_deletion_flag'] != $suspended_account_auto_deletion_flag){
			if($row_old_data['suspended_account_auto_deletion_flag'] == 0){
				$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>Disabled</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspended_deletion." day(s)</td></tr>";
			} else {
				$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspended_deletion']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>Disabled</td></tr>";
			}
		} else {
			if($row_old_data['suspended_account_auto_deletion_flag'] == 1){
				if($row_old_data['account_suspended_deletion'] != $account_suspended_deletion){
					$changed_content .= "<tr><td style='width: 350px;'>Automatically delete account for inactivity after:</td><td style='width:120px;'>".$row_old_data['account_suspended_deletion']." day(s)</td><td style='padding: 0px 50px;'>"."&rarr;"."</td><td style='width:120px; color: #0085CC;'>".$account_suspended_deletion." day(s)</td></tr>";
				}
			}
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
	
	$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `account_suspension_strict_date_flag` = :account_suspension_strict_date_flag, `account_suspension_strict_date` = :account_suspension_strict_date, `account_suspension_inactive_flag` = :account_suspension_inactive_flag, `account_suspension_inactive` = :account_suspension_inactive, `suspended_account_auto_deletion_flag` = :suspended_account_auto_deletion_flag, `account_suspended_deletion` = :account_suspended_deletion WHERE `client_user_id` = '{$user_id}'";
	
	la_do_query($query_update,array(':account_suspension_strict_date_flag' => $account_suspension_strict_date_flag, ':account_suspension_strict_date' => $account_suspension_strict_date, ':account_suspension_inactive_flag' => $account_suspension_inactive_flag, ':account_suspension_inactive' => $account_suspension_inactive, ':suspended_account_auto_deletion_flag' => $suspended_account_auto_deletion_flag, ':account_suspended_deletion' => $account_suspended_deletion),$dbh);
	$_SESSION["LA_SUCCESS"] = "User setting has been updated successfully.";
	header("location:view_portal_user.php?user_id={$user_id}");
	exit();
}

//if there is "nav" parameter, we need to determine the correct entry id and override the existing user_id
if(!empty($nav)){
	$exclude_admin = false;

	$all_user_id_array = la_get_filtered_users_ids_new($dbh,"",$exclude_admin);
	$no_of_elements = (count($all_user_id_array)-1);
	//echo '<pre style="color:red;">';
	//print_r($all_user_id_array);
	$user_key = array_search($user_id, $all_user_id_array);
	//echo '<br>';
	if($nav == 'prev'){
		if($user_key == 0){
			$user_key = $no_of_elements;
		}else{
			$user_key--;
		}
	}else{
		if($user_key == $no_of_elements){
			$user_key = 0;
		}else{
			$user_key++;
		}
	}
	//echo '</pre>';
	$user_id = $all_user_id_array[$user_key];
}

$query_user 							= "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `status` <> 2 AND `is_invited`= '0' AND `client_user_id`= ".$user_id;
$sth3 									= la_do_query($query_user,array(),$dbh);
$client_user_data 						= la_do_fetch_result($sth3);

$client_user_id 						= $client_user_data['client_user_id'];
$email									= $client_user_data['email'];
$full_name								= $client_user_data['full_name'];
$phone									= $client_user_data['phone'];
$avatar_url								= $client_user_data['avatar_url'];
$username								= $client_user_data['username'];
$status	    							= $client_user_data['status'];
$is_admin   							= $client_user_data['is_admin'];
$user_ip    							= $client_user_data['user_ip'];

$account_suspension_strict_date 		= $client_user_data['account_suspension_strict_date'];
$account_suspension_strict_date_flag 	= $client_user_data['account_suspension_strict_date_flag'];
$account_suspension_inactive 			= $client_user_data['account_suspension_inactive'];
$account_suspension_inactive_flag 		= $client_user_data['account_suspension_inactive_flag'];
$account_suspended_deletion 			= $client_user_data['account_suspended_deletion'];
$suspended_account_auto_deletion_flag 	= $client_user_data['suspended_account_auto_deletion_flag'];

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
{$perm_style}
EOT;


$current_nav_tab = 'manage_users';
require('includes/header.php'); 	
?>
<div id="content" class="full">
  <div class="post manage_forms">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left;">
          <h2>View User Details</h2>
        </div>
        <div style="float: right;margin-right: 5px"> <a href="#" id="button_save_notification" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save User Setting </a> </div>
        <div style="clear: both; height: 1px;"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body" style="overflow: auto;">
      <div id="vu_details" style="padding-top: 0px" data-userid="<?php echo $user_id; ?>">
        <div id="vu_profile">
        	<div class="vu_avatar">
				<img src="<?php echo $avatar_url; ?>" width="70px;">
			</div>
			<div class="vu_info">
				<h2 class="vu_userfullname"><?php echo $full_name; ?></h2>
				<h5 class="vu_email"><?php echo $email; ?></h5>
			</div>
          	<?php 
				$last_login = getUserLastLogin($dbh, $user_id);
			?>
          	<div id="vu_log">Last login <strong><?php echo ($last_login != false) ? date("M d", $last_login['last_login']) : "No login information found!"; ?></strong> <?php echo ($last_login != false) ? 'from' : '';  ?> <strong><?php echo $last_login['user_ip']; ?></strong></div>
        </div>
        <?php
			  if($status == 1){
			  ?>
        <div style="display:block;" id="vu_suspended">This user is currently being <span> SUSPENDED</span></div>
        <?php
			  }else{
			  ?>
        <div style="display:none;" id="vu_suspended">This user is currently being<span> SUSPENDED</span></div>
        <?php 
			  }
			  ?>
		<br>
		<!-- entity listing -->	  
		<table width="100%" cellspacing="0" cellpadding="0" border="0" id="vu_privileges" style="margin-left: 0px;">
			<tbody>		
				<tr>
					<td>
						<div class="vu_title">
							Entity Membership
						</div>
					</td>
				</tr>
				<?php
				$client_id = "";
				/*$query = "SELECT `company_name` FROM `".LA_TABLE_PREFIX."ask_clients` LEFT JOIN `".LA_TABLE_PREFIX."entity_user_relation` ON (`".LA_TABLE_PREFIX."ask_clients`.`client_id` = `".LA_TABLE_PREFIX."entity_user_relation`.`entity_id`) WHERE `client_user_id` = ?";
				$companySth = la_do_query($query, array($user_id), $dbh);*/
				$query = "SELECT `client_id` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
				$sth = la_do_query($query, array($user_id), $dbh);
				while($row = la_do_fetch_result($sth)){
					$client_id = $row['client_id'];
				}
				$primary_entity_name = getEntityName($dbh, $client_id);
				
				$companyNameStr     = getOtherEntityNames($dbh, $client_user_id, $client_id);
				$companyNameArr     = explode("||==||", $companyNameStr);
				if($primary_entity_name != ''){
					echo '<tr><td><span class="vu_checkbox" style = "color: red;"><strong>'.$primary_entity_name.'</strong></span></td></tr>';
				}
				if(count($companyNameArr) > 0 && $companyNameArr[0] != ""){
					for($i = 0; $i< count($companyNameArr); $i++){
						if($i%2 == 0){
							echo '<tr class="alt"><td><span class="vu_checkbox"><strong>'.$companyNameArr[$i].'</strong></span></td></tr>';
						}else{
							echo '<tr><td><span class="vu_checkbox"><strong>'.$companyNameArr[$i].'</strong></span></td></tr>';
						}
					}
				}
				?>
			</tbody>
		</table>
		<div style="margin: 20px;">
	        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?user_id=<?php echo $user_id; ?>" method="post" id="cron-setting-form">
	          <div style="display:none;">
	            <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
	          </div>
	          <ul id="ns_main_list">
	            <li>
	              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
	                <div class="ns_box_title">
	                  <input type="checkbox" value="1" class="checkbox" id="account_suspension_strict_date_flag" name="account_suspension_strict_date_flag" <?php echo ($account_suspension_strict_date_flag == 1) ? 'checked' : ''; ?>>
	                  <label for="account_suspension_strict_date_flag" class="choice">Automatically suspend account after date:</label>
	                  <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--> 
	                </div>
	                <div class="ns_box_email" id="strict-date" <?php echo ($account_suspension_strict_date_flag == 1) ? '' : 'style="display:none;"'; ?>>
	                  <label class="description" for="esl_email_address">Select Date: <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--></label>
	                  <span><input style="width:20px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("m", $account_suspension_strict_date) : ''; ?>" maxlength="2" size="2" class="element text" id="mm"></span>/
	                  <span><input style="width:20px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("d", $account_suspension_strict_date) : ''; ?>" maxlength="2" size="2" class="element text" id="dd"></span>/
	                  <span><input style="width:35px;" type="text" value="<?php echo ($account_suspension_strict_date > 0) ? date("Y", $account_suspension_strict_date) : ''; ?>" maxlength="4" size="4" class="element text" id="yyyy"></span>
	                  <input type="hidden" id="account_suspension_strict_date_hidden" name="account_suspension_strict_date" value="<?php echo ($account_suspension_strict_date > 0) ? date("m/d/Y", $account_suspension_strict_date) : ''; ?>">
	                  <span style="display: none;"> &nbsp;<img id="cal_img_5" class="datepicker" src="images/calendar.gif" alt="Pick a date." /></span>
	                </div>
	              </div>
	            </li>
	            <li>&nbsp;</li>
	            <li>
	              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
	                <div class="ns_box_title">
	                  <input type="checkbox" value="1" class="checkbox" id="account_suspension_inactive_flag" name="account_suspension_inactive_flag" <?php echo ($account_suspension_inactive_flag == 1) ? 'checked' : ''; ?>>
	                  <label for="account_suspension_inactive_flag" class="choice">Automatically suspend account for inactivity after:</label>
	                  <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--> 
	                </div>
	                <div class="ns_box_email" id="inactive-day" <?php echo ($account_suspension_inactive_flag == 1) ? '' : 'style="display:none;"'; ?>>
	                  <label class="description" for="esl_email_address">Number of days: <!--<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top">--></label>
	                  <input id="account_suspension_inactive" name="account_suspension_inactive" class="element text medium" value="<?php echo $account_suspension_inactive; ?>" type="text">
	                </div>
	              </div>
	            </li>
	            <li>&nbsp;</li>
	            <li>
	              <div id="ns_box_myinbox" class="ns_box_main gradient_blue">
	                <div class="ns_box_title">
	                  <input type="checkbox" value="1" class="checkbox" id="suspended_account_auto_deletion_flag" name="suspended_account_auto_deletion_flag" <?php echo ($suspended_account_auto_deletion_flag == 1) ? 'checked' : ''; ?>>
	                  <label for="suspended_account_auto_deletion_flag" class="choice">Automatically delete account for inactivity after:</label>
	                </div>
	                <div class="ns_box_email" id="inactive-delete-day" <?php echo ($suspended_account_auto_deletion_flag == 1) ? '' : 'style="display:none;"'; ?>>
	                  <label class="description" for="esl_email_address">Number of days: </label>
	                  <input id="account_suspended_deletion" name="account_suspended_deletion" class="element text medium" value="<?php echo $account_suspended_deletion; ?>" type="text">
	                </div>
	              </div>
	            </li>
	          </ul>
	          <input type="hidden" name="cron_settings" value="1">
	        </form>
	    </div>
      	<div class="vu_title">
		    <a href="list_audit_log.php?user_id=<?php echo base64_encode($user_id); ?>&client=1">Audit Log</a> |
		    <a href="uploaded_document_log.php?user_id=<?php echo base64_encode($user_id); ?>&is_portal=1">Uploaded Files Document Log</a> | 
		    <a href="list_user_session_log.php?user_id=<?php echo base64_encode($user_id); ?>&is_portal=1">User Session Log</a>
	  	</div>		
      </div>
      <div id="ve_actions">
        <div id="ve_entry_navigation"> <a href="<?php echo "view_portal_user.php?user_id={$user_id}&nav=prev"; ?>" title="Previous User"><img src="images/navigation/005499/24x24/Back.png"></a> <a href="<?php echo "view_portal_user.php?user_id={$user_id}&nav=next"; ?>" title="Next User" style="margin-left: 5px"><img src="images/navigation/005499/24x24/Forward.png"></a> </div>
        <div id="ve_entry_actions">
          <ul>
			<?php
			if(!$client_user_data['is_saml_user']){
			?>
			<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_edit" title="Edit User" href="<?php echo "edit_portal_user.php?user_id={$user_id}"; ?>"><img src="images/navigation/005499/16x16/Edit.png">Edit</a></li>
			<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_password" title="Change Password" href="#"><img src="images/navigation/005499/16x16/My_account.png">Password</a></li>
			<li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_reset_mfa" title="Reset Multi-Factor Authentication" href="#"><img src="images/navigation/005499/16x16/My_account.png">Reset MFA</a></li>
			<?php
			}
			?>
            <?php
				  //if($is_admin == 0){
				  	  if($status == 1){
				  ?>
            <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" data-ajax="unblock" title="Unlock User" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Unlock.png">Unblock</a></li>
            <?php
				  	  }else{
				  ?>
            <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" data-ajax="suspend" title="Suspend User" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Suspend.png">Suspend</a></li>
            <?php  
				  	  }
				  //}
				  ?>
            
            <?php
				  //if($is_admin == 0){
				  ?>
            <li><a id="vu_action_delete" title="Delete User" href="#"><img src="images/navigation/005499/16x16/Delete.png">Delete</a></li>
            <?php
				  //}
				  ?>
          </ul>
        </div>
      </div>      
    </div>
  </div>
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Something went wrong. Please try again later.</p>
</div>
<div id="dialog-confirm-user-delete" title="Are you sure you want to delete this user?" class="buttons" style="display: none">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
  <p id="dialog-confirm-user-delete-msg"> This action cannot be undone.<br/>
    <strong id="dialog-confirm-user-delete-info">This user will be deleted and blocked.</strong><br/>
    <br/>
  </p>
</div>

<div id="dialog-change-password" title="Change User Password" class="buttons" style="display: none"> 
	<form id="dialog-change-password-form" class="dialog-form" style="margin-bottom: 10px">
		<p id="change-password-notifications" style="display: none;"></p>
		<ul>
			<li>
				<?php if( $forced_password ) { ?>
					<p style="margin-top:15px;"><b>50% Rule on Passwords</b> is enabled. The password will be generated automatically. Click Update Password to generate new password for user.</p>
					<style type="text/css">
						.not_forced {
							display: none !important;
						}
					</style>
				<?php } ?>

				<label for="dialog-change-password-input1" class="description not_forced">Enter New Password *</label>
				<input type="password" id="dialog-change-password-input1" name="dialog-change-password-input1" class="text large not_forced" value="<?=$newpassword?>" <?php if($forced_password) { echo "readonly"; }?>>
				<label for="dialog-change-password-input2" style="margin-top: 15px" class="description not_forced">Confirm New Password *</label>
				<input type="password" id="dialog-change-password-input2" name="dialog-change-password-input2" class="text large not_forced" value="<?=$newpassword?>" <?php if($forced_password) { echo "readonly"; }?>>
				<span style="display: block;margin-top: 10px">
					<input type="checkbox"  value="1" class="checkbox" id="dialog-change-password-send-login" name="dialog-change-password-send-login" style="margin-left: 0px">
					<label for="dialog-change-password-send-login" class="choice change-password">Send password change notification to user</label>
				</span>
				<p class="not_forced" style="margin-top:15px;">* The password must be a minimum of 8 characters, contain at least one number, one upper case letter, and one special character.</p>
			</li>
		</ul>
	</form>
</div>

<div id="dialog-password-changed" title="Success!" class="buttons" style="display: none">
	<img src="images/navigation/005499/50x50/Success.png" title="Success" /> 
	<p id="dialog-password-changed-msg"></p>
</div>
<div id="dialog-reset-mfa" title="Reset Multi-Factor Authentication" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
	<p id="dialog-reset-mfa-msg">
		This action cannot be undone.<br/>
		<strong id="dialog-reset-mfa-info">The user will be asked to set up new multi-factor authentication during their next login.</strong><br/><br/>
	</p>
</div>
<?php
	
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.ext.js"></script>
EOT;

	require('includes/footer.php'); 
?>
<script type="text/javascript">
$(document).ready(function() {
	
	//Generic warning dialog to be used everywhere
	$("#dialog-warning").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$(this).next().find('button').blur();
		},
		buttons: [{
			text: 'OK',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				window.location.reload();
			}
		}]
	});

	function select_date(dates){
		var _dateSelected = '';
		var _mm = '';
		var _dd = '';
		var _yyyy = '';
		if(dates.length){
			var _dateSelected = (dates[0].getMonth() + 1) + '/' + dates[0].getDate() + '/' + dates[0].getFullYear();
			var _mm = (dates[0].getMonth() + 1);
			var _dd = dates[0].getDate();
			var _yyyy = dates[0].getFullYear();
		}
		$('input#account_suspension_strict_date_hidden').val(_dateSelected);
		$('input#mm').val(_mm);
		$('input#dd').val(_dd);
		$('input#yyyy').val(_yyyy);
	}
	
	$('a#button_save_notification').click(function(){
		$('form#cron-setting-form').submit();
	});
	
	$('#account_suspension_strict_date_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#strict-date').show();
		}else{
			$('div#strict-date').hide();
		}
	});
	
	$('#account_suspension_inactive_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#inactive-day').show();
		}else{
			$('div#inactive-day').hide();
		}
	});
	
	$('#suspended_account_auto_deletion_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#inactive-delete-day').show();
		}else{
			$('div#inactive-delete-day').hide();
		}
	});
	
	$("#vu_action_delete").click(function(){	
		$("#dialog-confirm-user-delete").dialog('open');
		return false;
	});
	
	//dialog box to confirm user deletion
	$("#dialog-confirm-user-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-user-delete-ok").blur();
		},
		buttons: [{
				text: 'Yes. Delete this user',
				id: 'btn-confirm-user-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					//disable the delete button while processing
					$("#btn-confirm-user-delete-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-confirm-user-delete-cancel").hide();
					$("#btn-confirm-user-delete-ok").text('Deleting...');
					$("#btn-confirm-user-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
										
					//do the ajax call to delete the users
					$.ajax({
					   type: "POST",
					   async: true,
					   url: "user-ajax-call-portal.php",
					   data: {mode: 'deleted', user_id: <?php echo $user_id; ?>},
					   cache: false,
					   global: false,
					   dataType: "json",
					   error: function(xhr,text_status,e){
						   //error, display the generic error message		  
					   },
					   success: function(response){
						//console.log(response);
						if(response.status == 1){
							alert(response.message);
						}else{
						    location.href = 'manage_users.php';
						}							   
					   },
					   complete:function(){
						   
					   }
					});
					
				}
			},
			{
				text: 'Cancel',
				id: 'btn-confirm-entry-delete-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	$("#vu_action_password").click(function(){	
		$("#dialog-change-password").dialog('open');
		return false;
	});
	
	//dialog box to change password
	$("#dialog-change-password").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Update Password',
			id: 'dialog-change-password-btn-save-changes',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				var password_1 = $.trim($("#dialog-change-password-input1").val());
				var password_2 = $.trim($("#dialog-change-password-input2").val());
				var current_user_id = <?php echo $user_id; ?>;

				var send_login_info = 0;
				if($("#dialog-change-password-send-login").prop("checked") == true){
					send_login_info = 1;
				}

				if(password_1 == "" || password_2 == ""){
					$('#change-password-notifications').addClass('error').text('Please enter both password fields!').show();
				}else if(password_1 != password_2){
					$('#change-password-notifications').addClass('error').text('Please enter the same password for both fields!').show();
				}else{
					//disable the save changes button while processing
					$("#dialog-change-password-btn-save-changes").prop("disabled",true);
						
					//display loader image
					$("#dialog-change-password-btn-cancel").hide();
					$("#dialog-change-password-btn-save-changes").text('Saving...');
					$("#dialog-change-password-btn-save-changes").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");

					//do the ajax call to change the password
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "user-ajax-call-portal.php",
						   data: {np: password_1,cp: password_2, user_id: current_user_id, send_login: send_login_info, mode: "change_password"},
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
							   //error, display the generic error message
							   $('#change-password-notifications').addClass('error').text('Unable to save the password!').show(); 
						   },
						   success: function(response_data){
							   //console.log(response_data.status);	   
							   //restore the buttons on the dialog
								$("#dialog-change-password-btn-save-changes").prop("disabled",false);
								$("#dialog-change-password-btn-cancel").show();
								$("#dialog-change-password-btn-save-changes").text('Update Password');
								$("#dialog-change-password-btn-save-changes").next().remove();
								$("#dialog-change-password-input1").val('');
								$("#dialog-change-password-input2").val('');
								$("#dialog-change-password-send-login").prop("checked",false);
									   	   
								if(response_data.status == 'success'){
									//display the confirmation message
									$("#dialog-change-password").dialog('close');
									$('#dialog-password-changed-msg').text('The new password has been saved. New Password is '+response_data.new_password);
									$("#dialog-password-changed").dialog('open');
								} else if (response_data.status == 'error') {
									$('#change-password-notifications').addClass('error').text(response_data.message).show();
								}
						   }
					});
				}
			}
		},
		{
			text: 'Cancel',
			id: 'dialog-change-password-btn-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]

	});
	
	$('#vu_action_suspend').click(function(){
		var _selector = jQuery(this);
		$.ajax({
			type: "POST",
			url: "user-ajax-call-portal.php",
			data: {mode: _selector.attr('data-ajax'), user_id: <?php echo $user_id; ?>},
			dataType: "json",
			beforeSend: function(){},
			success: function(response){
				if(response.status == 1){
					alert(response.message);
				}else{
					if(_selector.attr('data-ajax') == 'unblock'){
						_selector.attr({
							'data-ajax': 'suspend',
							'title': "Suspend User"
						});
						_selector.html('<img src="images/navigation/005499/16x16/Suspend.png "> Suspend');
						$('div#vu_suspended').hide();
					}else if(_selector.attr('data-ajax') == 'suspend'){
						_selector.attr({
							'data-ajax': 'unblock',
							'title': "Unblock User"
						});
						_selector.html('<img src="images/navigation/005499/16x16/Unlock.png"> Unblock');
						$('div#vu_suspended').show();	
					}
				}
			},
			complete:function(){
			
			}
		});
	});
	
	$('#account_suspension_strict_date_hidden').datepick({ 
		onSelect: select_date,
		showTrigger: '#cal_img_5'
	});

	//Dialog to display password has been changed successfully
	$("#dialog-password-changed").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'OK',
				id: 'dialog-password-changed-btn-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
					location.reload();
				}
			}]

	});

	//dialog box to reset MFA
	$("#dialog-reset-mfa").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-reset-mfa-ok").blur();
		},
		buttons: [
			{
				text: 'Yes. Reset',
				id: 'btn-reset-mfa-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					//disable the reset button while processing
					$("#btn-reset-mfa-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-reset-mfa-cancel").hide();
					$("#btn-reset-mfa-ok").text('Resetting...');
					$("#btn-reset-mfa-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					var user_id  = <?php echo $user_id; ?>;

					//do the ajax call to reset MFA
					$.ajax({
						type: "POST",
						async: true,
						url: "reset_authentication.php",
						data: {
							action: 'reset_user_mfa',
							origin: 'view_user',
							user_id: user_id
						},
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#dialog-reset-mfa").dialog('close');
							$("#dialog-warning").dialog('open');
						},
						success: function(response_data){
							if(response_data.status == 'ok'){
								window.location.reload();
							} else {
								$("#dialog-reset-mfa").dialog('close');
								$("#dialog-warning").dialog('open');
							}
						}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-reset-mfa-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	//open the reset MFA dialog when the reset MFA user link clicked
	$("#vu_action_reset_mfa").click(function(){	
		$("#dialog-reset-mfa").dialog('open');
		return false;
	});
});
</script>
