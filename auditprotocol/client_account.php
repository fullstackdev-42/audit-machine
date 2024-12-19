<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('includes/init.php');
require('config.php');
require('includes/language.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/view-functions.php');
require('includes/users-functions.php');
require('includes/post-functions.php');
require('portal-header.php');
?>
<div class="content_body">
  <?php
		//Connect to the database
		$dbh = la_connect_db();
		$la_settings = la_get_settings($dbh);
		//Get a list of all subscribed forms
		$client_id = $_SESSION['la_client_client_id'];
		//$query = "SELECT DISTINCT ".LA_TABLE_PREFIX."ask_client_forms.`form_id` FROM ".LA_TABLE_PREFIX."forms, ".LA_TABLE_PREFIX."ask_client_forms WHERE ".LA_TABLE_PREFIX."ask_client_forms.`client_id`='".$client_id."' AND ".LA_TABLE_PREFIX."forms.`form_active` = 1 AND ".LA_TABLE_PREFIX."forms.`form_private_form_check` = 0 AND ".LA_TABLE_PREFIX."ask_client_forms.`form_id`=".LA_TABLE_PREFIX."forms.`form_id`";

		$query = "SELECT DISTINCT `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`, `".LA_TABLE_PREFIX."forms`.`form_for_selected_company`
				  FROM `".LA_TABLE_PREFIX."forms`
				  LEFT JOIN `".LA_TABLE_PREFIX."ask_client_forms` ON (`".LA_TABLE_PREFIX."forms`.`form_id` = `".LA_TABLE_PREFIX."ask_client_forms`.`form_id`)
				  WHERE `".LA_TABLE_PREFIX."ask_client_forms`.`client_id` = '{$client_id}'
				  AND `".LA_TABLE_PREFIX."forms`.`form_active` = '1'
				  AND `".LA_TABLE_PREFIX."forms`.`form_private_form_check` = '0'
				  AND `".LA_TABLE_PREFIX."ask_client_forms`.`form_id` = `".LA_TABLE_PREFIX."forms`.`form_id`";

		//echo $query ; exit;
		$sth2 = $dbh->prepare($query);

		try{
			$sth2->execute($params);
		}catch(PDOException $e) {
			exit;
		}

		$count = $sth2->rowCount();
		$user_subscribed_forms = array();

		for($i=0;$i<$count;$i++){
			$user_subscribed_forms_temp		= la_do_fetch_result($sth2);
			if($user_subscribed_forms_temp['form_for_selected_company'] == 0 || $client_id == $user_subscribed_forms_temp['form_for_selected_company']){
				$user_subscribed_forms[$i] = array();
				$user_subscribed_forms[$i] = array('form_id' => $user_subscribed_forms_temp['form_id'], 'form_for_selected_company' => $user_subscribed_forms_temp['form_for_selected_company']);
			}
		}

		/*foreach($user_subscribed_forms as $key => $form){
			//$query = "SELECT `form_name`, `form_description` FROM ".LA_TABLE_PREFIX."forms WHERE `form_id`='".$form_id."'";
			$query = "SELECT `form_id`, `form_name`, `form_description`, `form_theme_id`, `form_private_form_check`, `form_for_selected_company` FROM ".LA_TABLE_PREFIX."forms WHERE `form_id`='".$form['form_id']."'";
			$sth2 = $dbh->prepare($query);
			try{
				$sth2->execute($params);
			}catch(PDOException $e) {
				exit;
			}
			$form = la_do_fetch_result($sth2);
		}*/

		if(count($user_subscribed_forms) > 0)
		{

		}
		else
		{
			echo "<p>You have not subscribed to any forms.</p>\n<p>Please visit the catalog</p>";
		}
		?>
  <ul id="la_form_list" class="la_form_list">
    <?php
				$row_num = 1;
				foreach ($user_subscribed_forms as $form_details){
					$form_id = $form_details['form_id'];
					$query = "SELECT `form_name`, `form_description`, `form_theme_id` FROM ".LA_TABLE_PREFIX."forms WHERE `form_id`='".$form_id."'";
					$sth2 = $dbh->prepare($query);

					try{
						$sth2->execute($params);
					}catch(PDOException $e) {
						exit;
					}

					$form = la_do_fetch_result($sth2);

					$form_name   	 = htmlspecialchars($form['form_name']);
					$theme_id		 = (int) $form['form_theme_id'];

					if(!empty($form_data['form_tags'])){
						$form_tags_array = array_reverse($form_data['form_tags']);
					}else{
						$form_tags_array = array();
					}
			?>
    <li data-theme_id="<?php echo $theme_id; ?>" id="liform_<?php echo $form_id; ?>" class="form_active form_visible"> <a style="color:#FFF" href="/portal/view.php?id=<?php echo $form_id; ?>" target="_blank">
      <div style="height: 0px; clear: both;"></div>
      <div class="middle_form_bar">
        <h3><?php echo $form_name; ?></h3>
        <div class="form_meta">
          <div class="form_tag">
            <ul class="form_tag_list">
            </ul>
          </div>
        </div>
        <div style="height: 0px; clear: both;"></div>
      </div>
      <div style="height: 0px; clear: both;"></div>
      </a> </li>
    <?php
				    $row_num++;
				}
				?>
  </ul>
</div>
<div id="dialog-welcome-message" title="Message" class="buttons" style="display: none"><img alt="" src="images/navigation/005499/50x50/Notice.png" width="48"><br><br><p><?php echo $la_settings['welcome_message']; ?></p></div>
<?php
require('includes/footer.php');
?>
<script type="text/javascript">
$(document).ready(function() {
	//dialog box to confirm user deletion
	$("#dialog-welcome-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				id: 'btn-welcome-message-ok',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});
	<?php
	if(isset($_SESSION['login_message']) && $_SESSION['login_message'] == true){
		if(isset($la_settings['enable_welcome_message_notification']) && $la_settings['enable_welcome_message_notification'] == 1){
	?>
	$("#dialog-welcome-message").dialog('open').click();
	<?php
		}
		unset($_SESSION['login_message']);
	}
	?>
});
</script>
