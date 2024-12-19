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
require('includes/check-session.php');
require('includes/users-functions.php');
require('includes/filter-functions.php');
require('lib/password-hash.php');

//get an array containing id number of all filtered users id within ap_users table, based on $filter_data
function la_get_filtered_users_ids($dbh,$filter_data,$exclude_admin=true){

	$query = "SELECT `client_user_id` FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `status` <> 2 AND `is_invited`= '0' AND `client_id`= ?";
	$params = array($_SESSION['la_client_client_id']);
	$sth = la_do_query($query,$params,$dbh);

	$filtered_user_id_array = array();

	while($row = la_do_fetch_result($sth)){
		$filtered_user_id_array[] = $row['client_user_id'];
	}

	return $filtered_user_id_array;
}


//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$user_id = (int) trim($_GET['user_id']);
$nav 	 = trim($_GET['nav']);

//if there is "nav" parameter, we need to determine the correct entry id and override the existing user_id
if(!empty($nav)){
	$exclude_admin = false;

	$all_user_id_array = la_get_filtered_users_ids($dbh,"",$exclude_admin);
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

	//if there is no user_id, fetch the first/last member of the array
	/*f(empty($user_id)){
		if($nav == 'prev'){
			$user_id = array_pop($all_user_id_array);
		}else{
			$user_id = $all_user_id_array[0];
		}
	}*/
}

$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `status` <> 2 AND `is_invited`= '0' AND `client_user_id`= ".$user_id;
$sth3 = la_do_query($query_user,array(),$dbh);
$client_user_data = la_do_fetch_result($sth3);
$client_user_id = $client_user_data['client_user_id'];
$email		=	$client_user_data['email'];
$full_name	=	$client_user_data['full_name'];
$phone		=	$client_user_data['phone'];
$username	=	$client_user_data['username'];
$status	    =	$client_user_data['status'];
$is_admin   =   $client_user_data['is_admin'];
?>
<!DOCTYPE html>
<html>
<head>
<title>View Account | <?php echo $full_name; ?></title>
<link rel="stylesheet" type="text/css" href="../css/main.css" media="screen" />

<!--[if IE 7]>
			<link rel="stylesheet" type="text/css" href="../css/ie7.css" media="screen" />
		<![endif]-->

<!--[if IE 8]>
			<link rel="stylesheet" type="text/css" href="../css/ie8.css" media="screen" />
		<![endif]-->

<!--[if IE 9]>
			<link rel="stylesheet" type="text/css" href="../css/ie9.css" media="screen" />
		<![endif]-->

<link href="../css/theme.css" rel="stylesheet" type="text/css" />
<link href="../css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link href="../css/pagination_classic.css" type="text/css" />
</head>
<body>
<div id="bg">
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
      <div id="navigation">
        <ul id="nav">
          <li class="page_item nav_logout"><span id="unregisted_holder"><a id="nav_logout" href="../client_logout.php" title="Sign Out"><span class="icon-exit"></span>Sign Out</a></li>
          <li class="page_item nav_manage_forms"><a href="../client_account.php"><span class="icon-file"></span>My Forms</a></li>
          <li class="page_item nav_manage_forms"><a href="../template_document.php"><span class="icon-file"></span>My Documents</a></li>
          <li class="page_item nav_manage_forms"><a href="../my_report.php"><span class="icon-file"></span>My Reports</a></li>
          <li class="page_item nav_users current_page_item"><a id="nav_users" href="../business_info/" title="Business Information"><span class="icon-user"></span>Business Information</a></li>
          <li class="page_item nav_my_account "><a id="nav_my_account" href="../manage_account/" title="My Account"><span class="icon-key"></span>My Account</a></li>
        </ul>
        <div class="clear"></div>
      </div>
      <div id="content" class="full">
        <div class="post manage_forms">
          <div class="content_header">
            <div class="content_header_title">
              <div style="float: left;">
                <h2>Business Information <img src="../images/icons/resultset_next.gif" />View User Details</h2>
                <p>Please add a new user account to your business to authorized members only.</p>
              </div>
              <div style="clear: both; height: 1px;"></div>
            </div>
          </div>
          <div class="content_body">
            <div id="vu_details" style="padding-top: 0px" data-userid="<?php echo $user_id; ?>">
              <div id="vu_profile">
                <h2 class="vu_userfullname"><?php echo $full_name; ?></h2>
                <h5 class="vu_email"><?php echo $email; ?></h5>
              </div>
              <?php
			  if($status == 1){
			  ?>
              <div style="display:block; float:left;" id="vu_suspended">This user is currently being <span>SUSPENDED</span></div>
              <?php
			  }else{
			  ?>
              <div style="display:none; float:left;" id="vu_suspended">This user is currently being <span>SUSPENDED</span></div>
              <?php
			  }
			  ?>
            </div>
            <div id="ve_actions">
              <div id="ve_entry_navigation"> <a href="<?php echo "view_user.php?user_id={$user_id}&nav=prev"; ?>" title="Previous User"><span class="icon-arrow-left"></span></a> <a href="<?php echo "view_user.php?user_id={$user_id}&nav=next"; ?>" title="Next User" style="margin-left: 5px"><span class="icon-arrow-right"></span></a> </div>
              <div id="ve_entry_actions" class="gradient_blue">
                <ul>
                  <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_edit" title="Edit User" href="<?php echo "edit_user.php?user_id={$user_id}"; ?>"><span class="icon-pencil"></span>Edit</a></li>
                  <?php
				  if($is_admin == 0){
				  	  if($status == 1){
				  ?>
                  <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" data-ajax="unblock" title="Unlock User" href="javascript:void(0)"><span class="icon-unlocked"></span>Unblock</a></li>
                  <?php
				  	  }else{
				  ?>
                  <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_suspend" data-ajax="suspend" title="Suspend User" href="javascript:void(0)"><span class="icon-user-block"></span>Suspend</a></li>
                  <?php
				  	  }
				  }
				  ?>
                  <li style="border-bottom: 1px dashed #8EACCF"><a id="vu_action_password" title="Change Password" href="#"><span class="icon-key"></span>Password</a></li>
                  <?php
				  if($is_admin == 0){
				  ?>
                  <li><a id="vu_action_delete" title="Delete User" href="#"><span class="icon-remove"></span>Delete</a></li>
                  <?php
				  }
				  ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <img src="../images/bottom.png" id="bottom_shadow">
    <div id="footer">
      <p class="copyright">Patent Pending, Copyright &copy; 2000-<script>document.write(new Date().getFullYear());</script> - <a href="http://lazarusalliance.com">Lazarus Alliance.</a> All rights reserved.</p>
      <div class="clear"></div>
    </div>
    <!-- /#footer -->
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
      <ul>
        <li>
          <label for="dialog-change-password-input1" class="description">Enter New Password</label>
          <input type="password" id="dialog-change-password-input1" name="dialog-change-password-input1" class="text large" value="">
          <label for="dialog-change-password-input2" style="margin-top: 15px" class="description">Confirm New Password</label>
          <input type="password" id="dialog-change-password-input2" name="dialog-change-password-input2" class="text large" value="">
          <span style="display: block;margin-top: 10px">
          <input type="checkbox"  value="1" class="checkbox" id="dialog-change-password-send-login" name="dialog-change-password-send-login" style="margin-left: 0px">
          <label for="dialog-change-password-send-login" class="choice change-password">Send login information to user</label>
          </span> </li>
      </ul>
    </form>
  </div>
  <div id="dialog-password-changed" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
    <p id="dialog-password-changed-msg"> The new password has been saved. </p>
  </div>
</div>
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/jquery.tools.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

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
					   url: "user-ajax-call.php",
					   data: {mode: 'deleted', user_id: <?php echo $user_id; ?>},
					   cache: false,
					   global: false,
					   dataType: "json",
					   error: function(xhr,text_status,e){
						   //error, display the generic error message
					   },
					   success: function(response){

						if(response.status == 1){
							alert(response.message);
						}else{
						   window.location.replace('');
						}
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
			text: 'Save Password',
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
					alert('Please enter both password fields!');
				}else if(password_1 != password_2){
					alert("Please enter the same password for both fields!");
				}else{
					//disable the save changes button while processing
					$("#dialog-change-password-btn-save-changes").prop("disabled",true);

					//display loader image
					$("#dialog-change-password-btn-cancel").hide();
					$("#dialog-change-password-btn-save-changes").text('Saving...');
					$("#dialog-change-password-btn-save-changes").after("<div class='small_loader_box'><img src='../images/loader_small_grey.gif' /></div>");

					//do the ajax call to change the password
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "user-ajax-call.php",
						   data: {np: password_1, user_id: current_user_id, send_login: send_login_info, mode: "change_password"},
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
							   //error, display the generic error message
							   alert('Unable to save the password!');
							   $(this).dialog('close');
						   },
						   success: function(response_data){
							   console.log(response_data.status);
							   //restore the buttons on the dialog
								$("#dialog-change-password").dialog('close');
								$("#dialog-change-password-btn-save-changes").prop("disabled",false);
								$("#dialog-change-password-btn-cancel").show();
								$("#dialog-change-password-btn-save-changes").text('Save Password');
								$("#dialog-change-password-btn-save-changes").next().remove();
								$("#dialog-change-password-input1").val('');
								$("#dialog-change-password-input2").val('');
								$("#dialog-change-password-send-login").prop("checked",false);

								if(response_data.status == 'ok'){
									//display the confirmation message
									alert('Password successfully changed');
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
			url: "user-ajax-call.php",
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
						_selector.html('<span class="icon-user-block"></span>Suspend');
						$('div#vu_suspended').hide();
					}else if(_selector.attr('data-ajax') == 'suspend'){
						_selector.attr({
							'data-ajax': 'unblock',
							'title': "Unblock User"
						});
						_selector.html('<span class="icon-unlocked"></span>Unblock');
						$('div#vu_suspended').show();
					}
				}
			}
		});
	});
});
</script>
</body>
</html>
