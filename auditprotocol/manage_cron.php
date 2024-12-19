<?php
/********************************************************************************
 IT Audit Machine
  
 Copyright 2000-2014 Lazarus Alliance Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('includes/check-session.php');
	require('includes/users-functions.php');
	
	//error_reporting(1);
	
	$dbh 		 = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
		$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<link type="text/css" href="css/dropui.css" rel="stylesheet" />
<style>
.dropui-menu li a{
 	padding: 2px 0 2px 27px;
 	font-size: 115%;
}
.dropui .dropui-tab{
 	font-size: 95%;
}
</style>
EOT;
		
	if(isset($_REQUEST['c']) && $_REQUEST['c'] == 'account_suspension'){
		$cur_date = strtotime(date('Y-m-d'));
		$query = "SELECT `".LA_TABLE_PREFIX."ask_client_users`.*, MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`) `last_login`, IFNULL(DATEDIFF(CURDATE(), FROM_UNIXTIME(MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`), '%Y-%m-%d')), DATEDIFF(CURDATE(), FROM_UNIXTIME(`register_datetime`))) `no_of_days_last_login` FROM `".LA_TABLE_PREFIX."ask_client_users` LEFT JOIN `".LA_TABLE_PREFIX."portal_user_login_log` ON (`".LA_TABLE_PREFIX."ask_client_users`.`client_user_id` = `".LA_TABLE_PREFIX."portal_user_login_log`.`client_user_id`) GROUP BY `".LA_TABLE_PREFIX."ask_client_users`.`client_user_id`";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['account_suspension_strict_date_flag'] == 1 && $row['account_suspension_strict_date'] < $cur_date){
				$query_upd = "UPDATE `ap_ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_upd,array(),$dbh);
			}
			if($row['account_suspension_inactive_flag'] == 1 && $row['account_suspension_inactive'] < $row['no_of_days_last_login']){
				$query_upd = "UPDATE `ap_ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_upd,array(),$dbh);
			}
		}
		header("location:manage_cron.php");
		exit();
	}	
	
	if(isset($_REQUEST['c']) && $_REQUEST['c'] == 'account_deletion'){
		$cur_date = strtotime(date('Y-m-d'));
		$query = "SELECT `".LA_TABLE_PREFIX."ask_client_users`.*, IFNULL(MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`), 0) `last_login`, IFNULL(DATEDIFF(CURDATE(), FROM_UNIXTIME(MAX(`".LA_TABLE_PREFIX."portal_user_login_log`.`last_login`), '%Y-%m-%d')), DATEDIFF(CURDATE(), FROM_UNIXTIME(`register_datetime`))) `no_of_days_last_login` FROM `".LA_TABLE_PREFIX."ask_client_users` LEFT JOIN `".LA_TABLE_PREFIX."portal_user_login_log` ON (`".LA_TABLE_PREFIX."ask_client_users`.`client_user_id` = `".LA_TABLE_PREFIX."portal_user_login_log`.`client_user_id`) GROUP BY `".LA_TABLE_PREFIX."ask_client_users`.`client_user_id`";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['account_suspension_strict_date_flag'] == 1 && $row['account_suspension_strict_date'] < $cur_date){
				$query_upd = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_upd,array(),$dbh);
			}
			if($row['account_suspension_inactive_flag'] == 1 && $row['account_suspension_inactive'] < $row['no_of_days_last_login']){
				$query_upd = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `status` = '1' WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_upd,array(),$dbh);
			}
			// Account deletion after inactivity or strict date suspension
			$deletion_date_from_suspension_strict_date = strtotime("+{$row['account_suspended_deletion']} day", $row['account_suspension_strict_date']);
			if($row['suspended_account_auto_deletion_flag'] == 1 && $row['status'] == 1 && $row['account_suspension_strict_date_flag'] == 1 && $deletion_date_from_suspension_strict_date < $cur_date){
				$query_del = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_del,array(),$dbh);
			}
			$deletion_date_from_suspension_strict_date = ($row['last_login']>0) ? strtotime("+{$row['account_suspended_deletion']} day", $row['last_login']) : strtotime("+{$row['account_suspended_deletion']} day", $row['register_datetime']);
			if($row['suspended_account_auto_deletion_flag'] == 1 && $row['status'] == 1 && $row['account_suspension_inactive_flag'] == 1 && $row['account_suspension_inactive'] < $row['no_of_days_last_login'] && $deletion_date_from_suspension_strict_date < $cur_date){
				$query_del = "DELETE FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = '{$row['client_user_id']}'";
				la_do_query($query_del,array(),$dbh);
			}
		}
		header("location:manage_cron.php");
		exit();
	}
	
	if(isset($_REQUEST['c']) && $_REQUEST['c'] == 'notification'){
		$cur_datetime = strtotime(date('Y-m-d'));
		$cur_date = date('d');
		$cur_month = date('n');
		$query = "select * from ".LA_TABLE_PREFIX."mechanism_for_notification";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			// Additional headers
			$headers .= "To: {$row['email']}\r\n";
			$headers .= 'From: Administrator <noreply@lazarusalliance.com>' . "\r\n";
			$subject = $row['subject'];
			$body = nl2br($row['body']);
			
			if(!empty($row['form_url'])){
				$body .= '<br><br>';
				$body .= '<a href="'.$row['form_url'].'">click here to open form</a>';
			}
			
			if($row['frequency_type'] == 1 && $row['frequency_date'] == $cur_datetime){					
				@mail($row['email'], $subject, $body, $headers);
			}
			
			if($row['frequency_type'] == 2 && $row['frequency_date_pick'] == $cur_date){					
				@mail($row['email'], $subject, $body, $headers);
			}
			
			if($row['frequency_type'] == 3 && $row['frequency_date_pick'] == $cur_date){
				if($row['frequency_quaterly'] == 1 && in_array($cur_month, array(1, 4, 7, 10))){				
					@mail($row['email'], $subject, $body, $headers);
				}elseif($row['frequency_quaterly'] == 2 && in_array($cur_month, array(2, 5, 8, 11))){				
					@mail($row['email'], $subject, $body, $headers);
				}elseif($row['frequency_quaterly'] == 3 && in_array($cur_month, array(3, 6, 9, 12))){				
					@mail($row['email'], $subject, $body, $headers);
				}
			}
			
			if($row['frequency_type'] == 4 && $row['frequency_date_pick'] == $cur_date && $cur_month == $row['frequency_annually']){					
				@mail($row['email'], $subject, $body, $headers);
			}
		}
		header("location:manage_cron.php");
		exit();
	}
	
	if(isset($_REQUEST['c']) && $_REQUEST['c'] == 'reminder_notices'){
		$query = "select `enable_password_expiration`, `enable_password_days` from ".LA_TABLE_PREFIX."settings";
		$sth = la_do_query($query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['enable_password_expiration']){
				
				$query_user = "select `email`, `full_name`, datediff(NOW(), from_unixtime(password_change_date)) `no_of_days_of_last_password_change` from `".LA_TABLE_PREFIX."ask_client_users` where `status` = 0";
				$sth_user = la_do_query($query_user,array(),$dbh);
				while($row_user = la_do_fetch_result($sth_user)){
					if($row['enable_password_days'] <= $row_user['no_of_days_of_last_password_change']){
						/************this piece of code will send mail to the user with one time code **************/					
						$user_email 				= array($row_user['email']);
						$email_param 				= array();
						$email_param['from_name'] 	= 'IT Audit Machine';
						$email_param['from_email'] 	= $la_settings['default_from_email'];
						$email_param['subject'] 	= "Please update your password";
						$email_param['as_plain_text'] = true;
						
						//create the mail transport
						if(!empty($la_settings['smtp_enable'])){
							$s_transport = Swift_SmtpTransport::newInstance($la_settings['smtp_host'], $la_settings['smtp_port']);
							
							if(!empty($la_settings['smtp_secure'])){
								//port 465 for (SSL), while port 587 for (TLS)
								if($la_settings['smtp_port'] == '587'){
									$s_transport->setEncryption('tls');
								}else{
									$s_transport->setEncryption('ssl');
								}
							}
							
							if(!empty($la_settings['smtp_auth'])){
								$s_transport->setUsername($la_settings['smtp_username']);
								$s_transport->setPassword($la_settings['smtp_password']);
							}
						}else{
							$s_transport = Swift_MailTransport::newInstance(); //use PHP mail() transport
						}
						
						//create mailer instance
						$s_mailer = Swift_Mailer::newInstance($s_transport);
						
						$from_name 	= html_entity_decode($email_param['from_name'] ,ENT_QUOTES);
						$from_email = html_entity_decode($email_param['from_email'] ,ENT_QUOTES);
						$subject 	= html_entity_decode($email_param['subject'] ,ENT_QUOTES);
						$email_content_type = 'text/html';
					
	$body = "<html>
<head></head>
<body>
	<p>Please update your password</p>
</body>
</html>";

						$s_message = Swift_Message::newInstance()
						->setCharset('utf-8')
						->setMaxLineLength(1000)
						->setSubject($subject)
						->setFrom(array($from_email => $from_name))
						->setSender($from_email)
						->setReturnPath($from_email)
						->setTo($user_email)
						->setBody($body, $email_content_type);
						
						//send the message
						$a = $s_mailer->send($s_message);
					}
				}
			}
		}
	}
	
	if(isset($_REQUEST['c']) && $_REQUEST['c'] == 'delete_form'){
		//$no_of_days = 2;
		$no_of_days   = 30;
		$search_query = "SELECT `form_id` FROM `ap_deleted_form` WHERE CURDATE() >= DATE_ADD(FROM_UNIXTIME(`delete_datetime`, '%Y-%m-%d'), INTERVAL {$no_of_days} DAY) ORDER BY `delete_datetime` ASC";
		$sth = la_do_query($search_query,array(),$dbh);
		while($row = la_do_fetch_result($sth)){
			$form_id = (int)$row['form_id'];
			//remove from ap_forms
			$query = "delete from ".LA_TABLE_PREFIX."forms where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove from ap_form_elements
			$query = "delete from ".LA_TABLE_PREFIX."form_elements where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove from ap_element_options
			$query = "delete from ".LA_TABLE_PREFIX."element_options where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove from ap_column_preferences
			$query = "delete from ".LA_TABLE_PREFIX."column_preferences where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_entries_preferences
			$query = "delete from ".LA_TABLE_PREFIX."entries_preferences where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_form_locks
			$query = "delete from ".LA_TABLE_PREFIX."form_locks where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_element_prices
			$query = "delete from ".LA_TABLE_PREFIX."element_prices where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_field_logic_elements table
			$query = "delete from ".LA_TABLE_PREFIX."field_logic_elements where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_field_logic_conditions table
			$query = "delete from ".LA_TABLE_PREFIX."field_logic_conditions where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_page_logic table
			$query = "delete from ".LA_TABLE_PREFIX."page_logic where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_page_logic_conditions table
			$query = "delete from ".LA_TABLE_PREFIX."page_logic_conditions where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_email_logic table
			$query = "delete from ".LA_TABLE_PREFIX."email_logic where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_email_logic_conditions table
			$query = "delete from ".LA_TABLE_PREFIX."email_logic_conditions where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_webhook_options table
			$query = "delete from ".LA_TABLE_PREFIX."webhook_options where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_webhook_parameters table
			$query = "delete from ".LA_TABLE_PREFIX."webhook_parameters where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_reports table
			$query = "delete from ".LA_TABLE_PREFIX."reports where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_report_elements table
			$query = "delete from ".LA_TABLE_PREFIX."report_elements where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_report_filters table
			$query = "delete from ".LA_TABLE_PREFIX."report_filters where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_grid_columns table
			$query = "delete from ".LA_TABLE_PREFIX."grid_columns where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove review table
			$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}_review`";
			$params = array();
			la_do_query($query,$params,$dbh);
			
			//remove the actual form table
			$query = "drop table if exists `".LA_TABLE_PREFIX."form_{$form_id}`";
			$params = array();
			la_do_query($query,$params,$dbh);
			
			//remove from ap_form_submission_details table
			$query = "delete from ".LA_TABLE_PREFIX."form_submission_details where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_form_template table
			$query = "delete from ".LA_TABLE_PREFIX."form_template where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_form_payment_check table
			$query = "delete from ".LA_TABLE_PREFIX."form_payment_check where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove from ap_form_report_elements table
			$query = "delete `fre` from `".LA_TABLE_PREFIX."form_report_elements` as `fre` left join `".LA_TABLE_PREFIX."form_report` as `fr` ON (`fre`.`report_id` = `fr`.`report_id`) where `fr`.`form_id` = ?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
	
			//remove from ap_form_report table
			$query = "delete from ".LA_TABLE_PREFIX."form_report where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);
			
			//remove form folder
			@la_full_rmdir($la_settings['upload_dir']."/form_{$form_id}");
			if($la_settings['upload_dir'] != $la_settings['data_dir']){
				@la_full_rmdir($la_settings['data_dir']."/form_{$form_id}");
			}
		
			//delete entries from ap_permissions table, regardless of the config
			$query = "delete from ".LA_TABLE_PREFIX."permissions where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);	
			
			//delete entries from ap_deleted_form
			$query = "delete from ".LA_TABLE_PREFIX."deleted_form where form_id=?";
			$params = array($form_id);
			la_do_query($query,$params,$dbh);	
		}
	}
	
	$current_nav_tab = 'manage_cron';
	require('includes/header.php'); 
?>
<div id="content" class="full">
  <div class="post manage_forms">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left">
          <h2>Refresh Scheduled Processes</h2>
          <p>This automated function may be refreshed by clicking the following process links:</p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <div class="content_body">
      <ul id="la_form_list" class="la_form_list">
        <li class="form_selected form_visible" id="liform_88842" data-theme_id="23">
			<div class="middle_form_bar">
            <h3><a href="manage_cron.php?c=account_suspension" style="display:block;"><span class="icon-file2"></span>Security: User portal account suspensions</a></h3>
            <div style="height: 0px; clear: both;"></div>
          </div>        
        </li>
        <li class="form_selected form_visible" id="liform_88842" data-theme_id="23">
			<div class="middle_form_bar">
            <h3><a href="manage_cron.php?c=account_deletion" style="display:block;"><span class="icon-file2"></span>Security: User portal account deletions</a></h3>
            <div style="height: 0px; clear: both;"></div>
          </div>        
        </li>
        <li class="form_selected form_visible" id="liform_88842" data-theme_id="23">
			<div class="middle_form_bar">
            <h3><a href="manage_cron.php?c=notification" style="display:block;"><span class="icon-file2"></span>Administrative Notices to User</a></h3>
            <div style="height: 0px; clear: both;"></div>
          </div>        
        </li>
        <li class="form_selected form_visible" id="liform_88842" data-theme_id="23">
			<div class="middle_form_bar">
            <h3><a href="manage_cron.php?c=reminder_notices" style="display:block;"><span class="icon-file2"></span>Send Password Change Notices to User</a></h3>
            <div style="height: 0px; clear: both;"></div>
          </div>        
        </li>
        <li class="form_selected form_visible" id="liform_88842" data-theme_id="23">
			<div class="middle_form_bar">
            <h3><a href="manage_cron.php?c=delete_form" style="display:block;"><span class="icon-file2"></span>Remove deleted forms</a></h3>
            <div style="height: 0px; clear: both;"></div>
          </div>        
        </li>
      </ul>
    </div>
    <!-- /end of content_body --> 
    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<?php
	if($highlight_selected_form_id == true){
		$highlight_selected_form_id = $selected_form_id;
	}else{
		$highlight_selected_form_id = 0;
	}
	$footer_data =<<< EOT
<script type="text/javascript">
	var selected_form_id_highlight = {$highlight_selected_form_id};
	$(function(){
		{$jquery_data_code}		
    });
</script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.highlight.js"></script>
<script type="text/javascript" src="js/form_manager.js"></script>
EOT;
	require('includes/footer.php');
	
	
	/**** Helper Functions *******/
	
	function sort_by_today_entry_asc($a, $b) {
    	return ($b['today_entry'] - $a['today_entry']) * -1;
	}
	function sort_by_today_entry_desc($a, $b) {
    	return $b['today_entry'] - $a['today_entry'];
	}
	
	function sort_by_total_entry_asc($a, $b) {
    	return ($b['total_entry'] - $a['total_entry']) * -1;
	}
	function sort_by_total_entry_desc($a, $b) {
    	return $b['total_entry'] - $a['total_entry'];
	}	
?>