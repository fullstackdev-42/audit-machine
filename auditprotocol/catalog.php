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
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');
require('portal-header.php');
?>
          <div class="content_body">
            <?php

//Connect to the database
$dbh = la_connect_db();
//Get a list of all subscribed forms and save it to an array variable
//Get a list of all subscribed forms
$client_id = $_SESSION['la_client_client_id'];
$query = "SELECT `form_id` FROM ".LA_TABLE_PREFIX."ask_client_forms WHERE `client_id`='".$client_id."'";
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
	$user_subscribed_forms[$i] = $user_subscribed_forms_temp['form_id'];
}

/**********************************************/
/*   Fetching Company data from clent table   */
/**********************************************/
$query_com = "SELECT `client_id` FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_id`='".$client_id."'";
$sth_com = la_do_query($query_com,array(),$dbh);
$row_com = la_do_fetch_result($sth_com);
?>
            <ul id="la_form_list" class="la_form_list">
              <?php
				$client_id = $_SESSION['la_client_user_id'];
				$query = "SELECT `form_id`, `form_name`, `form_description`, `form_theme_id`, `form_private_form_check`, `form_for_selected_company` FROM ".LA_TABLE_PREFIX."forms WHERE `form_active` = 1";
				$sth2 = $dbh->prepare($query);
				try{
					$sth2->execute($params);
				}catch(PDOException $e) {
					exit;
				}
				$count = $sth2->rowCount();
				for($i=0;$i<$count;$i++){
					$form 			 = la_do_fetch_result($sth2);
					$form_name   	 = htmlspecialchars($form['form_name']);
					$form_id         = (int) $form['form_id'];
					$theme_id		 = (int) $form['form_theme_id'];
					if($form['form_private_form_check'] == 0){
						if($row_com['client_id'] == $form['form_for_selected_company']){
			  ?>
                  <li data-theme_id="<?php echo $theme_id; ?>" id="liform_<?php echo $form_id; ?>"  class="form_active form_visible">
                <div style="height: 0px; clear: both;"></div>
                <div class="middle_form_bar">
                  <h3><?php echo $form_name; ?></h3>
                  <div class="form_meta">
                    <div class="form_tag">
                      <ul class="form_tag_list">
                        <li class="form_tag_list_icon"><a href="#" style="color:#FFF">
                          <?php
						  	if(in_array($form['form_id'], $user_subscribed_forms)){
								echo "<a style=\"color:#FFF\" href=\"unsubscribe.php?id=" . $form['form_id'] . "\">Unsubscribe</a>";
							}
							else {
								echo '<a style="color:#FFF" href="pay-to-subscribe.php?id='.$form['form_id'].'">Subscribe</a>';
							}
							?>
                          </a></li>
                      </ul>
                    </div>
                  </div>
                  <div style="height: 0px; clear: both;"></div>
                </div>
                <div style="height: 0px; clear: both;"></div>
                </li>
                    <?php
							$row_num++;
						}else{
							if($form['form_for_selected_company'] == 0){
					?>
				  <li data-theme_id="<?php echo $theme_id; ?>" id="liform_<?php echo $form_id; ?>"  class="form_active form_visible">
					<div style="height: 0px; clear: both;"></div>
					<div class="middle_form_bar">
					  <h3><?php echo $form_name; ?></h3>
					  <div class="form_meta">
						<div class="form_tag">
						  <ul class="form_tag_list">
							<li class="form_tag_list_icon"><a href="#" style="color:#FFF">
							  <?php
								if(in_array($form['form_id'], $user_subscribed_forms)){
									echo "<a style=\"color:#FFF\" href=\"unsubscribe.php?id=" . $form['form_id'] . "\">Unsubscribe</a>";
								}
								else {
									echo '<a style="color:#FFF" href="pay-to-subscribe.php?id='.$form['form_id'].'">Subscribe</a>';
								}
								?>
							  </a></li>
						  </ul>
						</div>
					  </div>
					  <div style="height: 0px; clear: both;"></div>
					</div>
					<div style="height: 0px; clear: both;"></div>
					</li>
			  <?php
								$row_num++;
							}
						}
					}
				}
				//end foreach $form_list_array
			  ?>
            </ul>
          </div>
<?php
require('includes/footer.php');
?>
