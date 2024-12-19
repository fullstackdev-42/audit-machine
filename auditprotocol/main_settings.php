<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
permission from http://lazarusalliance.com/

More info at: http://lazarusalliance.com/
 ********************************************************************************/
require('includes/init.php');

require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');

require('includes/filter-functions.php');
require('includes/entry-functions.php');

$video_types = array("mp4", "avi", "mpeg");
$image_types = array("png", "jpg", "jpeg", "bmp");

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$ssl_suffix = "";

//check user privileges, is this user has privilege to administer IT Audit Machine?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
    $_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

    $ssl_suffix = "s";
    header("Location: restricted.php");
    exit;
}

//ajax function for removing tile
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $id	= trim($_REQUEST['tile_id']);
    $query  = "delete from `".LA_TABLE_PREFIX."announcement_slider` where id = ?";
    $params = array($id);
    
    la_do_query($query,$params,$dbh);
    $response_data = new stdClass();
    $response_data->status    	= "ok";	
    $response_json = json_encode($response_data);			
    echo $response_json;
    return;
}
    
//handle form submission if there is any
if(!empty($_POST['submit_form'])){
    $smtp_enable 			= la_sanitize($_POST['smtp_enable']);
    $smtp_host 	 			= la_sanitize($_POST['smtp_host']);
    $smtp_auth   			= la_sanitize($_POST['smtp_auth']);
    $smtp_secure 			= la_sanitize($_POST['smtp_secure']);
    $smtp_username 			= la_sanitize($_POST['smtp_username']);
    $smtp_password 			= la_sanitize($_POST['smtp_password']);
    $smtp_port 	   			= la_sanitize($_POST['smtp_port']);
    $admin_image_url   		= la_sanitize($_POST['admin_image_url']);
    $base_url   			= la_sanitize($_POST['base_url']);
    $default_from_name   	= la_sanitize($_POST['default_from_name']);
    $default_from_email   	= la_sanitize($_POST['default_from_email']);
    $upload_dir   			= la_sanitize($_POST['upload_dir']);
    $form_manager_max_rows  = la_sanitize($_POST['form_manager_max_rows']);
    $disable_itauditmachine_link  = la_sanitize($_POST['disable_itauditmachine_link']);
    $enforce_tsv  			= la_sanitize($_POST['enforce_tsv']);
    $enable_ip_restriction  = la_sanitize($_POST['enable_ip_restriction']);
    $ip_whitelist  			= la_sanitize($_POST['ip_whitelist']);
    $enable_account_locking		= la_sanitize($_POST['enable_account_locking']);
    $enable_session_timeout		= la_sanitize($_POST['enable_session_timeout']);
    $session_timeout_period		= la_sanitize($_POST['session_timeout_period']);
    $account_lock_period	    = (int) la_sanitize($_POST['account_lock_period']);
    $account_lock_max_attempts	= (int) la_sanitize($_POST['account_lock_max_attempts']);
    $default_form_theme_id	    = (int) la_sanitize($_POST['default_form_theme_id']);
    $document_font_id      = (int) la_sanitize($_POST['document_font_id']);
    $enable_registration_notification = (boolean) la_sanitize($_POST['enable_registration_notification']);
    $registration_notification_email = la_sanitize(trim($_POST['registration_notification_email']));
    $enable_welcome_message_notification = (boolean) la_sanitize($_POST['enable_welcome_message_notification']);

    $enable_announcement_slider = (boolean) la_sanitize($_POST['enable_announcement_slider']);
    $announcement_slider_speed = (int) la_sanitize($_POST['announcement_slider_speed']);
    $announcement_slider_tiles = la_sanitize($_POST['tile']);
    $announcement_slider_files = reArrayFiles($_FILES['tilefiles']);

    $welcome_message = la_sanitize(trim($_POST['welcome_message']));
    $disclaimer_message = la_sanitize(trim($_POST['disclaimer_message']));
    $enable_site_down = la_sanitize($_POST['enable_site_down']);
    $footer_login_image_url = la_sanitize($_POST['footer_login_image_url']);
    $portal_home_video_url = la_sanitize($_POST['portal_home_video_url']);
    $portal_login_popup_img_url = la_sanitize($_POST['portal_login_popup_img_url']);
    $portal_registration = la_sanitize($_POST['portal_registration']);
    $disable_email_based_otp = la_sanitize($_POST['disable_email_based_otp']);
    $file_viewer_download_option = la_sanitize($_POST['file_viewer_download_option']);

    $enable_password_expiration = la_sanitize($_POST['enable_password_expiration']);
    $enforce_rule_on_passwords = (int) $_POST['enforce_rule_on_passwords'];
    $enable_password_days = la_sanitize($_POST['enable_password_days']);

    $enable_account_suspension_inactive = la_sanitize($_POST['enable_account_suspension_inactive']);
    $account_suspension_inactive = la_sanitize($_POST['account_suspension_inactive']);
    $enable_account_deletion_inactive = la_sanitize($_POST['enable_account_deletion_inactive']);
    $account_deletion_inactive = la_sanitize($_POST['account_deletion_inactive']);

    $admin_help_url = la_sanitize($_POST['admin_help_url']);
    $user_help_url = la_sanitize($_POST['user_help_url']);
    $one_time_url_expiration_date = la_sanitize($_POST['one_time_url_expiration_date']);
    //validate inputs
    $error_messages = array();

    //validate name
    if(empty($disclaimer_message)){
        $error_messages['disclaimer_message'] = 'This field is required. This field can not be empty.';
    }

    if(!empty($error_messages)){
        $_SESSION['LA_ERROR'] = 'Please correct the marked field(s) below.';
    } else {
        //everything is validated, continue
        savePortalRegistrationFlagChange(array(
            'dbh' => $dbh,
            'user_id' => $_SESSION['la_user_id'],
            'portal_registration' => $la_settings['portal_registration'],
            'portal_registration_change' => empty(la_sanitize($_POST['portal_registration'])) ? 0 : 1,
        ));

        //if account lock settings empty, set the default max attempts = 6 and lock period = 30 minutes
        //these defaults are based on PCI DSS standard
        if(empty($account_lock_period)){
            $account_lock_period = 30;
        }
        if(empty($account_lock_max_attempts)){
            $account_lock_max_attempts = 6;
        }


        //save the settings
        $settings['smtp_enable'] 			= (int) $smtp_enable;
        $settings['smtp_host'] 				= $smtp_host;
        $settings['smtp_auth'] 				= $smtp_auth;
        $settings['smtp_secure']		 	= $smtp_secure;
        $settings['smtp_username'] 			= $smtp_username;
        $settings['smtp_password'] 			= $smtp_password;
        $settings['smtp_port'] 				= $smtp_port;
        $settings['admin_image_url'] 		= $admin_image_url;
        $settings['base_url'] 				= $base_url;
        $settings['default_from_name'] 		= $default_from_name;
        $settings['default_from_email'] 	= $default_from_email;
        $settings['upload_dir'] 			= $upload_dir;
        $settings['form_manager_max_rows'] 	= $form_manager_max_rows;
        $settings['disable_itauditmachine_link'] 	= $disable_itauditmachine_link;
        $settings['enforce_tsv'] 			= $enforce_tsv;
        $settings['enable_ip_restriction'] 	= $enable_ip_restriction;
        $settings['ip_whitelist'] 			= $ip_whitelist;
        $settings['enable_session_timeout']	= $enable_session_timeout;
        $settings['session_timeout_period']	= $session_timeout_period;
        $settings['enable_account_locking']	= $enable_account_locking;
        $settings['account_lock_period'] 	= $account_lock_period;
        $settings['account_lock_max_attempts'] 	= $account_lock_max_attempts;
        $settings['default_form_theme_id'] 	= $default_form_theme_id;
        $settings['document_font_id']  = $document_font_id;
        $settings['enable_registration_notification'] 	= $enable_registration_notification;
        $settings['registration_notification_email'] 	= $registration_notification_email;
        $settings['enable_welcome_message_notification'] 	= $enable_welcome_message_notification;

        $settings['enable_announcement_slider'] = $enable_announcement_slider;
        $settings['announcement_slider_speed'] = $announcement_slider_speed;
            
        $settings['welcome_message'] 	= $welcome_message;
        $settings['disclaimer_message']    = $disclaimer_message;
        $settings['enable_site_down'] 	= $enable_site_down;
        $settings['footer_login_image_url'] = $footer_login_image_url;
        $settings['portal_home_video_url'] = $portal_home_video_url;
        $settings['portal_login_popup_img_url'] = $portal_login_popup_img_url;
        $settings['portal_registration'] = $portal_registration;
        $settings['disable_email_based_otp'] = $disable_email_based_otp;
        $settings['file_viewer_download_option'] = $file_viewer_download_option;
        $settings['admin_help_url'] = $admin_help_url;
        $settings['user_help_url'] = $user_help_url;
        $settings['one_time_url_expiration_date'] = $one_time_url_expiration_date;

        $settings['enable_password_expiration'] = (int) $enable_password_expiration;
        $settings['enforce_rule_on_passwords'] = (int) $enforce_rule_on_passwords;
        $settings['enable_password_days'] 		= (int) $enable_password_days;

        $settings['enable_account_suspension_inactive'] = (int) $enable_account_suspension_inactive;
        $settings['account_suspension_inactive'] = (int) $account_suspension_inactive;
        $settings['enable_account_deletion_inactive'] = (int) $enable_account_deletion_inactive;
        $settings['account_deletion_inactive'] = (int) $account_deletion_inactive;

        // ReCAPTCHA
        $settings['recaptcha_public'] = $_POST['recaptcha_public'];
        $settings['recaptcha_secret'] = $_POST['recaptcha_secret'];

        /****************  S A M L    S E T T I N G S   ***********************/
        $settings['saml_login'] = (int)la_sanitize($_POST['saml_login']);
        $settings['sp_entityId'] = "https://".$_SERVER['HTTP_HOST']."/itam-shared/simplesamlphp/www/module.php/saml/sp/metadata.php/default-sp";
        $settings['sp_singleSignOnUrl'] = "https://".$_SERVER['HTTP_HOST']."/itam-shared/simplesamlphp/www/module.php/saml/sp/saml2-acs.php/default-sp";
        $settings['idp_entityId'] = la_sanitize($_POST['idp_entityId']);
        $settings['idp_singleSignOnService'] = la_sanitize($_POST['idp_singleSignOnService']);
        $settings['idp_x509cert'] = la_sanitize($_POST['idp_x509cert']);

        la_ap_settings_update($settings,$dbh);
        

        // Here is the part for adding/updating slider tiles.
        $target_dir = $upload_dir."/images/tiles/";
        if(is_dir($target_dir) === false){
			@mkdir($target_dir, 0777, true);
        }
        
        $error_messages = array();
        $tstamp = $_SERVER["REQUEST_TIME"];
        foreach($announcement_slider_tiles as $key => $tile) {
            $user_input = array();
            $user_input['id']                       =   (int) $tile['id'];
            $user_input['media_source']             =   trim($tile['media_source']);
            $user_input['background_url'] 			= 	trim($tile['background_url']);
            $user_input['background_media_type']	= 	trim($tile['background_media_type']);
            $user_input['youtube_link']	            = 	trim($tile['youtube_link']);            
            $user_input['title'] 					= 	trim($tile['title']);
            $user_input['description'] 				= 	trim($tile['description']);
            $user_input['rss_feed'] 				= 	trim($tile['rss_feed']);
            $user_input['order']					= 	(int) $tile['order'];
            $user_input = la_sanitize($user_input);

            
            $file = $announcement_slider_files[$key];
            
            if(!empty($file["name"])){
                $target_file = $target_dir.basename($file["name"]);
                $mediaType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                $error_messages[$key] = array();
                if (file_exists($target_file)) {
                    @unlink($target_file);
                }
                
                if ($file["size"] > 500000){
                    $error_messages[$key]['background_url'] = 'Sorry, your file is too large.';
                } else if (!in_array($mediaType, $video_types) && !in_array($mediaType, $image_types)) {
                    $error_messages[$key]['background_url'] = 'Sorry, only JPG, JPEG, PNG, GIF AVI, MPEG and MP4 files are allowed.';
                } else {
                    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
                        $error_messages[$key]['background_url'] = 'Sorry, there was an error uploading your file.';
                    } else {
                        $user_input['background_url'] = $target_file;
                        $user_input['background_media_type'] = $mediaType;
                    }
                }
            }

            if ($user_input['id']<0) {
                $query = "INSERT INTO 
                            `".LA_TABLE_PREFIX."announcement_slider`( 
                                        `media_source`,
                                        `background_url`, 
                                        `background_media_type`,
                                        `youtube_link`,
                                        `title`, 
                                        `description`, 
                                        `rss_feed`,
                                        `order`,
                                        `created_at`,
                                        `updated_at`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
                $params = array(
                            $user_input['media_source'],
                            $user_input['background_url'],
                            $user_input['background_media_type'],
                            $user_input['youtube_link'],
                            $user_input['title'],
                            $user_input['description'],
                            $user_input['rss_feed'],
                            (int) $user_input['order'],
                            $tstamp,
                            $tstamp
                        );

                la_do_query($query,$params,$dbh);
            } else {
                $query =   "UPDATE `".LA_TABLE_PREFIX."announcement_slider` 
                            SET `media_source` = ?,
                                `background_url`= ?, 
                                `background_media_type`= ?,
                                `youtube_link`= ?,
                                `title`= ?,
                                `description`= ?,
                                `rss_feed` = ?,
                                `order`= ?,
                                `updated_at` = ?
                            WHERE `id` = ?";
                $params = array(
                    $user_input['media_source'],
                    $user_input['background_url'],
                    $user_input['background_media_type'],
                    $user_input['youtube_link'],
                    $user_input['title'],
                    $user_input['description'],
                    $user_input['rss_feed'],
                    (int) $user_input['order'],
                    $tstamp,
                    (int) $user_input['id']
                );
                la_do_query($query,$params,$dbh);
            }
        }

        $_SESSION['LA_SUCCESS'] = 'System settings has been saved.';
        header("location:main_settings.php");
        exit();
    }
}
else{
    $smtp_enable 			= noHTML($la_settings['smtp_enable']);
    $smtp_host 	 			= noHTML($la_settings['smtp_host']);
    $smtp_auth   			= noHTML($la_settings['smtp_auth']);
    $smtp_secure 			= noHTML($la_settings['smtp_secure']);
    $smtp_username 			= noHTML($la_settings['smtp_username']);
    $smtp_password 			= noHTML($la_settings['smtp_password']);
    $smtp_port 	   			= noHTML($la_settings['smtp_port']);
    $admin_help_url         = noHTML($la_settings['admin_help_url']);
    $user_help_url          = noHTML($la_settings['user_help_url']);
    $admin_image_url   		= noHTML($la_settings['admin_image_url']);
    $base_url   			= noHTML($la_settings['base_url']);
    $default_from_name   	= noHTML($la_settings['default_from_name']);
    $default_from_email   	= noHTML($la_settings['default_from_email']);
    $upload_dir   			= noHTML($la_settings['upload_dir']);
    $form_manager_max_rows  = noHTML($la_settings['form_manager_max_rows']);
    $disable_itauditmachine_link  = noHTML($la_settings['disable_itauditmachine_link']);
    $enforce_tsv			= noHTML($la_settings['enforce_tsv']);
    $enable_ip_restriction	= noHTML($la_settings['enable_ip_restriction']);
    $ip_whitelist			= noHTML($la_settings['ip_whitelist']);

    $recaptcha_public = noHTML($la_settings['recaptcha_public']);
    $recaptcha_secret = noHTML($la_settings['recaptcha_secret']);

    //prepare default ip whitelist
    if(empty($ip_whitelist)){
        $current_ip = $_SERVER['REMOTE_ADDR'];

        $exploded     = explode('.', $current_ip);
        $current_ip_2 = $exploded[0].'.'.$exploded[1].'.'.$exploded[2].'.*';

        $ip_whitelist = "{$current_ip}\n{$current_ip_2}";
    }

    $enable_account_locking		= (int) noHTML($la_settings['enable_account_locking']);
    $enable_session_timeout		= (int) noHTML($la_settings['enable_session_timeout']);
    $session_timeout_period		= (int) noHTML($la_settings['session_timeout_period']);
    $account_lock_period	    = (int) noHTML($la_settings['account_lock_period']);
    $account_lock_max_attempts	= (int) noHTML($la_settings['account_lock_max_attempts']);
    $default_form_theme_id		= (int) noHTML($la_settings['default_form_theme_id']);
    $document_font_id      = (int) noHTML($la_settings['document_font_id']);
    $enable_registration_notification	= (int) noHTML($la_settings['enable_registration_notification']);
    $registration_notification_email	= noHTML($la_settings['registration_notification_email']);
    $enable_welcome_message_notification	= (int) noHTML($la_settings['enable_welcome_message_notification']);
    $enable_announcement_slider = (int) noHTML($la_settings['enable_announcement_slider']);
    $announcement_slider_speed = (int) noHTML($la_settings['announcement_slider_speed']);
    $welcome_message	= noHTML($la_settings['welcome_message']);
    $disclaimer_message    = noHTML($la_settings['disclaimer_message']);
    $enable_site_down	= noHTML($la_settings['enable_site_down']);
    $footer_login_image_url = noHTML($la_settings['footer_login_image_url']);
    $portal_home_video_url = noHTML($la_settings['portal_home_video_url']);
    $portal_login_popup_img_url = noHTML($la_settings['portal_login_popup_img_url']);
    $portal_registration = noHTML($la_settings['portal_registration']);
    $disable_email_based_otp = (int) noHTML($la_settings['disable_email_based_otp']);
    $file_viewer_download_option = (int) noHTML($la_settings['file_viewer_download_option']);

    $enable_password_expiration				= noHTML($la_settings['enable_password_expiration']);
    $enforce_rule_on_passwords             = (int) $la_settings['enforce_rule_on_passwords'];
    $enable_password_days  					= noHTML($la_settings['enable_password_days']);

    $account_suspension_inactive = noHTML($la_settings['account_suspension_inactive']);
    $account_deletion_inactive = noHTML($la_settings['account_deletion_inactive']);
    $enable_account_suspension_inactive = (int) $la_settings['enable_account_suspension_inactive'];
    $enable_account_deletion_inactive = (int) $la_settings['enable_account_deletion_inactive'];
    $one_time_url_expiration_date = (int) $la_settings['one_time_url_expiration_date'];


    /****************  S A M L    S E T T I N G S   ***********************/
    $saml_login = (int)$la_settings['saml_login'];
    $sp_entityId = noHTML($la_settings['sp_entityId']);
    $sp_singleSignOnUrl = noHTML($la_settings['sp_singleSignOnUrl']);
    $idp_entityId = noHTML($la_settings['idp_entityId']);
    $idp_singleSignOnService = noHTML($la_settings['idp_singleSignOnService']);
    $idp_x509cert = noHTML($la_settings['idp_x509cert']);



    //if account lock settings empty, set the default max attempts = 6 and lock period = 30 minutes
    //these defaults are based on PCI DSS standard
    if(empty($account_lock_period)){
        $account_lock_period = 30;
    }
    if(empty($account_lock_max_attempts)){
        $account_lock_max_attempts = 6;
    }

}

//get the available custom themes
$query = "SELECT theme_id,theme_name FROM ".LA_TABLE_PREFIX."form_themes WHERE theme_built_in=0 and status=1 ORDER BY theme_name ASC";
$params = array();

$sth = la_do_query($query,$params,$dbh);

$theme_list_array = array();
while($row = la_do_fetch_result($sth)){
    $theme_list_array[$row['theme_id']] = htmlspecialchars($row['theme_name']);
}

//get built-in themes
$query = "SELECT theme_id,theme_name FROM ".LA_TABLE_PREFIX."form_themes WHERE theme_built_in=1 and status=1 ORDER BY theme_name ASC";

$params = array();
$sth = la_do_query($query,$params,$dbh);

$theme_builtin_list_array = array();
while($row = la_do_fetch_result($sth)){
    $theme_builtin_list_array[$row['theme_id']] = htmlspecialchars($row['theme_name']);
}


//get fonts
$query_font = "SELECT font_id,font_family FROM ".LA_TABLE_PREFIX."fonts ORDER BY font_family ASC";

$sth_font = la_do_query($query_font,array(),$dbh);

$doc_fonts = array();
while($row_font = la_do_fetch_result($sth_font)){
    $doc_fonts[$row_font['font_id']] = htmlspecialchars($row_font['font_family']);
}

$license_key = $la_settings['license_key'];
if($license_key[0] == 'S'){
    $license_type = 'IT Audit Machine Standard';
}else if($license_key[0] == 'P'){
    $license_type = 'IT Audit Machine Professional';
}elseif ($license_key[0] == 'U') {
    $license_type = 'IT Audit Machine Unlimited';
}else{
    $license_type = "Invalid License";
}

//get the list of the form, put them into array
$query = "SELECT
					form_name,
					form_id
				FROM
					".LA_TABLE_PREFIX."forms
				WHERE
					form_active=0 or form_active=1
			 ORDER BY
					form_name ASC";

$params = array();
$sth = la_do_query($query,$params,$dbh);

$form_list_array = array();
$i=0;
while($row = la_do_fetch_result($sth)){
    $form_list_array[$i]['form_id']   	  = $row['form_id'];

    if(!empty($row['form_name'])){
        $form_list_array[$i]['form_name'] = htmlentities($row['form_name'],ENT_QUOTES)." (#{$row['form_id']})";
    }else{
        $form_list_array[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
    }
    $i++;
}

$all_tiles = array();
$query = "SELECT `id`,	`media_source`, `background_url`, `background_media_type`, `youtube_link`, `title`, `description`, `order`, `rss_feed`, `created_at`, `updated_at` FROM ".LA_TABLE_PREFIX."announcement_slider";
$sth = la_do_query($query, array(), $dbh);
while($row = la_do_fetch_result($sth)) {
    array_push($all_tiles, $row);
}
function usortTiles($a, $b) {
    return strcmp($a["order"], $b["order"]);
}
usort($all_tiles, "usortTiles");
$session_id = session_id();
$jquery_data_code = '';

$jquery_data_code .= "\$('.main_settings').data('session_id','{$session_id}');\n";

$header_data =<<<EOT
<style>
.uploadifive-queue-item { border: none !important; }
.ck-editor__editable {
    height: 200px!important;
    min-height: initial!important;
}
</style>
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
EOT;

$current_nav_tab = 'main_settings';
require('includes/header.php');

?>
<div id="content" class="full">
    <div class="post main_settings">
        <div class="content_header">
            <div class="content_header_title">
                <div style="float: left">
                    <h2>System Settings</h2>
                    <p>Configure system wide settings.</p>
                </div>
                <div style="float: right;">
                    <a href="#" id="button_save_main_settings" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </a>
                </div>
                <div style="clear: both; height: 1px"></div>
            </div>
        </div>
        <?php la_show_message(); ?>
        <div class="content_body">
            <form id="ms_form" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                <div style="display:none;">
                    <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                </div>
                <ul id="ms_main_list">
                    <li>
                        <div id="ms_box_smtp" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <input type="checkbox" <?php if(!empty($smtp_enable)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="smtp_enable" name="smtp_enable">
                                <label for="smtp_enable" class="choice">Use SMTP Server to Send Emails</label>
                                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If your forms doesn't send the result to your email, most likely you'll need to enable this option. This will send all emails from IT Audit Machine through SMTP server."/>
                            </div>
                            <div class="ms_box_email" <?php if(empty($smtp_enable)){echo 'style="display: none"';} ?>>
                                <label class="description" for="smtp_host">SMTP Server</label>
                                <input id="smtp_host" name="smtp_host" class="element text medium" value="<?php echo htmlspecialchars($smtp_host,ENT_QUOTES); ?>" type="text">
                                <label class="description" for="smtp_auth">Use Authentication</label>
                                <select class="element select small" id="smtp_auth" name="smtp_auth">
                                    <option <?php if(empty($smtp_auth)){ echo 'selected="selected"'; } ?> value="0">No</option>
                                    <option <?php if(!empty($smtp_auth)){ echo 'selected="selected"'; } ?> value="1">Yes</option>
                                </select>
                                <label class="description" for="smtp_secure">Use TLS/SSL</label>
                                <select class="element select small" id="smtp_secure" name="smtp_secure">
                                    <option <?php if(empty($smtp_secure)){ echo 'selected="selected"'; } ?> value="0">No</option>
                                    <option <?php if(!empty($smtp_secure)){ echo 'selected="selected"'; } ?> value="1">Yes</option>
                                </select>
                                <label class="description" for="smtp_username">SMTP User Name</label>
                                <input id="smtp_username" name="smtp_username" class="element text medium" value="<?php echo htmlspecialchars($smtp_username,ENT_QUOTES); ?>" type="text">
                                <label class="description" for="smtp_password">SMTP Password</label>
                                <input id="smtp_password" name="smtp_password" class="element text medium" value="<?php echo htmlspecialchars($smtp_password,ENT_QUOTES); ?>" type="text">
                                <label class="description" for="smtp_port">SMTP Port</label>
                                <input id="smtp_port" name="smtp_port" class="element text small" value="<?php echo htmlspecialchars($smtp_port,ENT_QUOTES); ?>" type="text" style="width: 50px">
                            </div>
                        </div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div id="saml_box_misc" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <input type="checkbox" class="checkbox" id="saml_login" value="1" name="saml_login" <?php if(!empty($saml_login)){echo 'checked="checked"';} ?>>
                                <label class="choice" for="saml_login">&nbsp;SAML for Single Sign On</label>
                                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, all logins will be authenticated against the defined IDP server."/> </div>
                            <div class="ms_box_email" <?php if(empty($saml_login)){echo ' style="display:none;"';} ?>>
                                <div style="margin:10px 0 10px 0;">
                                    <label class="description" for="sp_entityId">SP Entity Id<span style="color:red;">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Identifier of the SP entity  (must be a URI)"/></label>
                                    <a target="_blank" href="<?php echo $sp_entityId; ?>"><?php echo $sp_entityId; ?></a>
                                </div>
                                <div style="margin:10px 0 10px 0;">
                                    <label class="description" for="sp_entityId">SP Single Sign On URL<span style="color:red;">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Single Sing On URL on the Service Provider  (must be a URI)"/></label>
                                    <a target="_blank" href="<?php echo $sp_singleSignOnUrl; ?>"><?php echo $sp_singleSignOnUrl; ?></a>
                                </div>
                                <div style="margin:10px 0 10px 0;">
                                    <label class="description" for="idp_entityId">IDP Entity Id<span style="color:red;">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Identifier of the IdP entity  (must be a URI)"/></label>
                                    <input id="idp_entityId" name="idp_entityId" class="element text large" value="<?php echo $idp_entityId; ?>" type="text">
                                </div>
                                <div style="margin:10px 0 10px 0;">
                                    <label class="description" for="idp_singleSignOnService">IDP SingleSignOnService<span style="color:red;">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="SSO endpoint info of the IdP. (Authentication Request protocol) URL Target of the IdP where the SP will send the Authentication Request Message"/></label>
                                    <input id="idp_singleSignOnService" name="idp_singleSignOnService" class="element text large" value="<?php echo $idp_singleSignOnService; ?>" type="text">
                                </div>
                                <div style="margin:10px 0 10px 0;">
                                    <label class="description" for="idp_x509cert">IDP x509cert<span style="color:red;">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Public x509 certificate of the IdP"/></label>
                                    <textarea class="element textarea large" style="margin-top: 5px" name="idp_x509cert" id="idp_x509cert"><?php echo htmlentities($idp_x509cert,ENT_QUOTES); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div id="management_announcement_slider" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <input type="checkbox" class="checkbox" id="enable_announcement_slider" value="1" name="enable_announcement_slider" <?php if(!empty($enable_announcement_slider)){echo 'checked="checked"';} ?>>
                                <label class="choice" for="enable_announcement_slider">&nbsp;Enable Silder Management</label>
                                <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, all logins will be authenticated against the defined IDP server."/>
                            </div>
                            <div id="ss_box_misc" <?php if(empty($enable_announcement_slider)){echo ' style="display:none;"';} ?>>
                                <div class="ms_box_email">
                                    <label class="description" for="upload_recaptcha">
                                        Slider Speed <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can change the speed of the announcement slider"/></label>
                                    </label>
                                    <input type="number" name="announcement_slider_speed" class="element text large" value="<?php echo $announcement_slider_speed ?>">
                                    <div id="sortable-tiles">
                                    <?php foreach ($all_tiles as $key => $tile) { ?>
                                        <div class="tile-settings" tile-id=<?=$tile["id"]?>>
                                            <div class="delete-tile-btn" style="float:right" onclick="removeTile(event)">
                                                <img src="images/icons/51_red_16.png">
                                            </div>
                                            <div>
                                                <label class="description" for="title">Title <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is title"/></label></label>
                                                <input type="text" name="tile[<?=$key?>][title]" class="element text large" placeholder="title" value="<?=$tile["title"]?>">
                                            </div>
                                            <div>
                                                <label class="description" for="description">Description <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Description"/></label>
                                                <textarea name="tile[<?=$key?>][description]" class="slider-ckeditor"><?=$tile["description"]?></textarea>
                                            </div>
                                            <div>
                                                <label class="description">RSS Feed <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="RSS Feed"/></label></label>
                                                <input type="text" name="tile[<?=$key?>][rss_feed]" class="element text large" placeholder="RSS Feed" value="<?=$tile["rss_feed"]?>">
                                            </div>
                                            <div style="display:flex; justify-content: space-between">
                                                <div>
                                                    <input class="id" type="hidden" name="tile[<?=$key?>][id]" value="<?=$tile["id"]?>" placeholder="id">
                                                    <input class="background_url" type="hidden" name="tile[<?=$key?>][background_url]" value="<?=$tile["background_url"]?>" placeholder="background url">
                                                    <input class="background_media_type" type="hidden" name="tile[<?=$key?>][background_media_type]" value="<?=$tile["background_media_type"]?>" placeholder="background media type">
                                                    <input class="order" type="hidden" name="tile[<?=$key?>][order]" value="<?=$tile["order"]?>">
                                                </div>
                                                <div class="media_input_area">
                                                    <div style="margin-top: 13px">
                                                        <input id="local_media_<?=$key?>"  name="tile[<?=$key?>][media_source]" class="element radio" type="radio" value="local" <?=$tile["media_source"]==='local' ? 'checked' : ''?> onchange="changeMediaSource(event)"/>
                                                        <label for="local_media_<?=$key?>">Local Media</label>
                                                        <input id="youtube_media_<?=$key?>"  name="tile[<?=$key?>][media_source]" class="element radio" type="radio" value="youtube" <?=$tile["media_source"]==='youtube' ? 'checked' : ''?> onchange="changeMediaSource(event)"/>
                                                        <label for="youtube_media_<?=$key?>">Youtube</label>
                                                    </div>
                                                    <div class="local" style="display:<?=$tile["media_source"]==='local'?'block':'none'?>">
                                                        <label class="description" >Media(Image/Video) <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Media(Image/Video) <br/> Maximum File Size: 500KB <br/> Proper Image Ratio: 16: 9"/></label>
                                                        <input class="form-control upload_tile_media" name="tilefiles[<?=$key?>]" type="file" accept='image/*, video/*' onchange="changeBackgroundMedia(event)"/>
                                                    </div>
                                                    <div class="youtube" style="display:<?=$tile["media_source"]==='youtube'?'block':'none'?>">
                                                        <label class="description" for="youtube_link">Media(Youtube) <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Youtube Link"/></label>
                                                        <input class="element text large youtube_link" name="tile[<?=$key?>][youtube_link]" type="text" onchange="changeYoutubeLink(event)" value="<?=$tile['youtube_link']?>"/>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="description" for="preview">Preview <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Preview"/></label>
                                                    <div class="preview_container">
                                                        <div class="delete-media-file-btn" onclick="removeMediaFile(event)" style="display: <?=$tile["background_url"]? "block":"none"?>">
                                                            <img src="images/icons/51_red_16.png">
                                                        </div>
                                                        <img class="preview" src="<?=$tile["media_source"] == 'local' && $tile["background_url"] ? $tile["background_url"] : ''?>" style="display: <?=in_array($tile["background_media_type"], $image_types)?'block':'none'?>"/>
                                                        <video  class="preview" src="<?=$tile["media_source"] == 'local' && $tile["background_url"] ? $tile["background_url"]: ''?>" controls style="display: <?=in_array($tile["background_media_type"], $video_types)?'block':'none'?>"></video>
                                                        <iframe class="preview" src="<?=$tile["media_source"] == 'youtube' && $tile["youtube_link"] ? getYoutubeEmbedUrl($tile["youtube_link"]) : ''?>" style="display: <?=$tile["media_source"] == 'youtube'?'block':'none'?>"></iframe>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    </div>
                                    <div class="add-new-tile">
                                        <button id="save_tiles_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Tiles </button>
                                        <div class="add-new-tile-btn">
                                            <img src="images/icons/49_green_16.png">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div id="ms_box_misc" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <label class="choice">Security Settings</label>
                            </div>
                            <div id="ss_box_misc" style="display:block;">
                                <div class="ms_box_email">
                                    <div class="multi-selection-content" style="margin-top:15px;">
                                        <input type="checkbox" <?php if(!empty($enforce_tsv)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enforce_tsv" name="enforce_tsv">
                                        <label class="description inline" for="enforce_tsv">Enforce Multi-Factor Authentication on users <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, all IT Audit Machine users are enrolled in Multi-Factor Authentication. Once enabled, IT Audit Machine will require a six-digit security code (generated by TOTP authenticator mobile app) in addition to the standard password whenever they sign in to IT Audit Machine. "/> </label>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($disable_email_based_otp)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="disable_email_based_otp" name="disable_email_based_otp">
                                        <label class="description inline" for="disable_email_based_otp">Disable Email Based OTP <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="This feature will disable email based OTP"/> </label>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_ip_restriction)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_ip_restriction" name="enable_ip_restriction">
                                        <label class="description inline" for="enable_ip_restriction">Enable IP Address Restriction <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, all users can only login to IT Audit Machine panel from IP address listed here. Users using other IP address will be blocked. "/> </label>
                                    </div>
                                    <div id="div_ip_whitelist" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_ip_restriction)){ echo 'block'; }else{ echo 'none'; } ?>">
                                        <label class="checkbox" for="ip_whitelist" style="vertical-align: top;">Only allow login from these IP Addresses: </label>
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top; padding-bottom: 3px" title="You can enter multiple ip addresses, one ip address per line. Use the asterisk (*) as a wildcard to specify a range of address (examples: 192.168.1.*, 192.168.*, 192.168.*.120)"/>
                                        <div>
                                            <textarea class="element textarea small" style="width: 250px;margin-top: 5px" name="ip_whitelist" id="ip_whitelist"><?php echo htmlentities($ip_whitelist,ENT_QUOTES); ?></textarea>
                                        </div>                                        
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_account_locking)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_account_locking" name="enable_account_locking">
                                        <label class="description inline" for="enable_account_locking">Enable Account Locking <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, users account will be temporarily locked after several invalid login attempts."/> </label>
                                    </div>
                                    <div id="div_account_locking" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_account_locking)){ echo 'block'; }else{ echo 'none'; } ?>"> Lock account for
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($account_lock_period,ENT_QUOTES); ?>" class="text" style="width: 20px" id="account_lock_period" name="account_lock_period">
                                        minutes after
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($account_lock_max_attempts,ENT_QUOTES); ?>" class="text" style="width: 20px" id="account_lock_max_attempts" name="account_lock_max_attempts">
                                        invalid login attempts
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_session_timeout)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_session_timeout" name="enable_session_timeout">
                                        <label class="description inline" for="enable_session_timeout">Enable Session Timeout <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, user's account will be temporarily locked after several invalid login attempts."/> </label>
                                    </div>
                                    <div id="div_session_timeout" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_session_timeout)){ echo 'block'; }else{ echo 'none'; } ?>"> Log user out after
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($session_timeout_period,ENT_QUOTES); ?>" class="text" style="width: 20px" id="session_timeout_period" name="session_timeout_period">
                                         minutes of inactivity.
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_site_down)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_site_down" name="enable_site_down">
                                        <label class="description inline" for="enable_site_down">Enable site down <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the user will not be able to login in portal."/> </label>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($portal_registration)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="portal_registration" name="portal_registration">
                                        <label class="description inline" for="portal_registration">Allow self-registration in User Portal <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, users will be permitted to self-register in User Portal."/> </label>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_password_expiration)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_password_expiration" name="enable_password_expiration">
                                        <label class="description inline" for="enable_password_expiration">Enable Password Expiration <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the ITAM users will be notified to change their password based on the number of days set by the administrator."/> </label>
                                    </div>
                                    <div id="div_enable_password_expiration" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_password_expiration)){ echo 'block'; }else{ echo 'none'; } ?>"> Password Expiration
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($enable_password_days,ENT_QUOTES); ?>" class="text" style="width: 50px; text-align: right;" id="enable_password_days" name="enable_password_days"> day(s)
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enforce_rule_on_passwords)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enforce_rule_on_passwords" name="enforce_rule_on_passwords">
                                        <label class="description inline" for="enforce_rule_on_passwords">Enforce 50% Rule on Passwords <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the passwords of the ITAM users will be generated automatically. Users will not be allowed to choose their own passwords."/> </label>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_account_suspension_inactive)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_account_suspension_inactive" name="enable_account_suspension_inactive">
                                        <label class="description inline" for="enable_account_suspension_inactive">Enable Automatic Account Suspension for Inactivity <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the ITAM users including admins will be suspended for inactivity based on the number of days set by the administrator."/> </label>
                                    </div>
                                    <div id="div_enable_account_suspension_inactive" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_account_suspension_inactive)){ echo 'block'; }else{ echo 'none'; } ?>"> Automatically suspend account for inactivity after:
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($account_suspension_inactive,ENT_QUOTES); ?>" class="text" style="width: 50px; text-align: right;" id="account_suspension_inactive" name="account_suspension_inactive"> day(s)
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_account_deletion_inactive)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_account_deletion_inactive" name="enable_account_deletion_inactive">
                                        <label class="description inline" for="enable_account_deletion_inactive">Enable Automatic Account Deletion for Inactivity <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the ITAM users including admins will be deleted for inactivity based on the number of days set by the administrator."/> </label>
                                    </div>
                                    <div id="div_enable_account_deletion_inactive" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_account_deletion_inactive)){ echo 'block'; }else{ echo 'none'; } ?>"> Automatically delete account for inactivity after:
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($account_deletion_inactive,ENT_QUOTES); ?>" class="text" style="width: 50px; text-align: right;" id="account_deletion_inactive" name="account_deletion_inactive"> day(s)
                                    </div>
                                    <div>
                                        <label class="description" for="one_time_url_expiration_date">Invite and Password Reset Link Expirations <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Generated links for Registrations & Password Resets will be set to expire in days based on the value set, and users will no longer be able to use those links to register or reset their passwords."/></label>
                                        <div style="margin-left: 22px; margin-top: 10px;">
                                            Set the amount of time in days for invite and password reset link expirations:
                                            <input class="text" type="text" name="one_time_url_expiration_date" style="width: 50px; text-align: right" value="<?php echo $one_time_url_expiration_date; ?>">
                                        </div>                                        
                                    </div>
                                    <div class="recaptcha-separator">
                                        <label class="description" for="upload_recaptcha">
                                            Site reCAPTCHA Key <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="These are required keys to use reCAPTCHA on your forms for SPAM protection. You can get these keys from https://www.google.com/recaptcha/admin"/></label>
                                        </label>
                                        <input type="text" name="recaptcha_public" class="element text large" value="<?php echo $recaptcha_public ?>">
                                        <label class="description" for="secret_recaptcha">
                                            Secret reCAPTCHA Key <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="These are required keys to use reCAPTCHA on your forms for SPAM protection. You can get these keys from https://www.google.com/recaptcha/admin"/></label>
                                        </label>
                                        <input type="text" name="recaptcha_secret" class="element text large" value="<?php echo $recaptcha_secret ?>">
                                    </div>
                                    <div style="clear: both"></div>                                    
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div id="ms_box_misc" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <!--<input type="checkbox" class="checkbox" id="miscellaneous-settings">-->
                                <label class="choice">Miscellaneous Settings</label>
                            </div>
                            <div id="misc-settings-div" style="display:block;">
                                <div class="ms_box_email">
                                    <label class="description" for="admin_help_url">Admin Help URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Provide a full HELP URL for admins."/></label>
                                    <input id="admin_help_url" name="admin_help_url" class="element text large" value="<?php echo htmlspecialchars($admin_help_url,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="user_help_url">User Help URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Provide a full HELP URL for users."/></label>
                                    <input id="user_help_url" name="user_help_url" class="element text large" value="<?php echo htmlspecialchars($user_help_url,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="admin_image_url">Admin Panel Header Image URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Provide a full URL to an image which is displayed on the admin panel header. A transparent PNG no larger than 475px wide by 124px high is recommended."/></label>
                                    <input id="admin_image_url" name="admin_image_url" class="element text large" value="<?php echo htmlspecialchars($admin_image_url,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="footer_login_image_url">Login Footer Image URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Footer login image."/></label>
                                    <input type="text" value="<?php echo htmlspecialchars($footer_login_image_url,ENT_QUOTES); ?>" class="element text large" name="footer_login_image_url" id="footer_login_image_url">
                                    <label class="description" for="portal_home_video_url">User Portal Homepage Video URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The URL of a video file to be displayed on the User Portal homepage."/></label>
                                    <input type="text" value="<?php echo htmlspecialchars($portal_home_video_url,ENT_QUOTES); ?>" class="element text large" name="portal_home_video_url" id="portal_home_video_url">
                                    <label class="description <?php if(!empty($error_messages['disclaimer_message'])){ echo 'label_red'; } ?>" for="disclaimer_message">Disclaimer Message <span class="required">*</span> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Here you can set the disclaimer message that shows up in a popup when a user attempts to download a file."/></label>
                                    <textarea name="disclaimer_message" id="disclaimer_message" rows="10" style="width:99%;"><?php echo htmlspecialchars($disclaimer_message,ENT_QUOTES); ?></textarea>
                                    <?php
                                        if(!empty($error_messages['disclaimer_message'])){
                                            echo '<span class="au_error_span">'.$error_messages['disclaimer_message'].'</span>';
                                        }
                                    ?>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_registration_notification)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_registration_notification" name="enable_registration_notification">
                                        <label class="description inline" for="enable_registration_notification">Enable Account Management Notification <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the administrator will get notified when a new user gets registered."/> </label>
                                    </div>
                                    <div id="div_enable_registration_notification" style="margin-left: 22px;margin-top: 10px;margin-bottom: 10px; display: <?php if(!empty($enable_registration_notification)){ echo 'block'; }else{ echo 'none'; } ?>">
                                        <input type="text" maxlength="255" value="<?php echo htmlspecialchars($registration_notification_email,ENT_QUOTES); ?>" class="element text medium" id="registration_notification_email" name="registration_notification_email">
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="You can enter multiple email addresses. Simply separate them with commas."/>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($enable_welcome_message_notification)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="enable_welcome_message_notification" name="enable_welcome_message_notification">
                                        <label class="description inline" for="enable_welcome_message_notification">Enable Welcome Message Notification <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: bottom; padding-bottom: 3px" title="If enabled, the user will get a welcome message in a popup window."/> </label>
                                    </div>
                                    <div id="div_enable_welcome_message_notification" style="margin-left: 22px;margin-top: 10px; display: <?php if(!empty($enable_welcome_message_notification)){ echo 'block'; }else{ echo 'none'; } ?>">
                                        <label for="portal_login_popup_img_url">Welcome Message Image URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Provide a full URL to an image which is displayed in the user portal login popup."/></label>
                                        <input type="text" value="<?php echo htmlspecialchars($portal_login_popup_img_url,ENT_QUOTES); ?>" class="element text large" name="portal_login_popup_img_url" id="portal_login_popup_img_url" style="margin-bottom: 10px;">
                                        <label for="welcome_message">Welcome Message</label>
                                        <textarea name="welcome_message" id="welcome_message" rows="5" style="width:99%;"><?php echo htmlspecialchars($welcome_message,ENT_QUOTES); ?></textarea>
                                    </div>
                                    <div style="clear: both"></div>
                                    <div id="file_viewer_download_button_parent" class="multi-selection-content" style="margin-top: 10px;">
                                        <input type="checkbox" <?php if(!empty($file_viewer_download_option)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="file_viewer_download_option" name="file_viewer_download_option">
                                        <label class="description" for="file_viewer_download_option">Allow Download Button For File Viewer <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top"></label>
                                    </div>
                                </div>
                                <div style="clear: both"></div>
                                <div class="ms_box_more" style="display: none">
                                    <label class="description" for="default_from_name">Default Email From Name <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the default name being used to send all form notifications and system-related emails from IT Audit Machine (example: password reset email, form resume email)."/></label>
                                    <input id="default_from_name" name="default_from_name" class="element text medium" value="<?php echo htmlspecialchars($default_from_name,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="default_from_email">Default Email From Address <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the default email address being used to send all form notifications and system-related emails from IT Audit Machine (example: password reset email, form resume email)."/></label>
                                    <input id="default_from_email" name="default_from_email" class="element text medium" value="<?php echo htmlspecialchars($default_from_email,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="base_url">IT Audit Machine URL <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The URL to your IT Audit Machine admin panel. Normally you don't need to modify this setting. Don't change this setting if you aren't sure."/></label>
                                    <input id="base_url" name="base_url" class="element text large" value="<?php echo htmlspecialchars($base_url,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="upload_dir">File Upload Folder <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The path for your file upload folder. If you change it, make sure to provide a full path to your upload folder. Don't change this setting if you aren't sure."/></label>
                                    <input id="upload_dir" name="upload_dir" class="element text medium" value="<?php echo htmlspecialchars($upload_dir,ENT_QUOTES); ?>" type="text">
                                    <label class="description" for="default_form_theme_id">Default Form Theme <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The default theme being used on every new forms."/></label>
                                    <select class="element select medium" id="default_form_theme_id" name="default_form_theme_id">
                                        <optgroup label="Built-in Themes">
                                            <option value="0">White</option>
                                            <?php
                                            if(!empty($theme_builtin_list_array)){
                                                foreach ($theme_builtin_list_array as $theme_id=>$theme_name){
                                                    $selected_tag = '';
                                                    if($default_form_theme_id == $theme_id){
                                                        $selected_tag = 'selected="selected"';
                                                    }
                                                    echo "<option value=\"{$theme_id}\" {$selected_tag}>{$theme_name}</option>";
                                                }
                                            }
                                            ?>
                                        </optgroup>
                                        <?php if(!empty($theme_list_array)){ ?>
                                            <optgroup label="Custom Themes">
                                                <?php
                                                if(!empty($theme_list_array)){
                                                    foreach ($theme_list_array as $theme_id=>$theme_name){
                                                        $selected_tag = '';
                                                        if($default_form_theme_id == $theme_id){
                                                            $selected_tag = 'selected="selected"';
                                                        }
                                                        echo "<option value=\"{$theme_id}\" {$selected_tag}>{$theme_name}</option>";
                                                    }
                                                }
                                                ?>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                    <label class="description" for="document_font_id">Document Font Family <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This font family will be used on every document created."/></label>
                                    <select class="element select medium" id="document_font_id" name="document_font_id">
                                        <?php
                                        if(!empty($doc_fonts)){
                                            foreach ($doc_fonts as $font_id=>$font_family){
                                                $selected_tag = '';
                                                if($document_font_id == $font_id){
                                                    $selected_tag = 'selected="selected"';
                                                }
                                                echo "<option value=\"{$font_id}\" {$selected_tag}>{$font_family}</option>";
                                            }
                                        }
                                        ?>
                                    </select>    
                                    <label class="description" for="form_manager_max_rows">Form Manager Max List <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The number of forms to be displayed for each page on the Form Manager."/></label>
                                    <input id="form_manager_max_rows" style="width: 50px" name="form_manager_max_rows" class="element text small" value="<?php echo htmlspecialchars($form_manager_max_rows,ENT_QUOTES); ?>" type="text">
                                    <div style="clear: both;width: 100%;border-bottom: 1px dashed #3B699F;margin-top: 20px;margin-bottom: 5px"></div>
                                    <div class="multi-selection-content">
                                        <input type="checkbox" <?php if(!empty($disable_itauditmachine_link)){echo 'checked="checked"';} ?> value="1" class="checkbox" id="disable_itauditmachine_link" name="disable_itauditmachine_link">
                                        <label class="description inline" for="disable_itauditmachine_link">Remove the "Powered by IT Audit Machine" link from all my forms</label>
                                    </div>
                                </div>
                                <div class="ms_box_more_switcher"> <a id="more_option_misc_settings" href="#">advanced options</a> <img id="misc_settings_img_arrow" style="vertical-align: top;margin-left: 3px" src="images/icons/38_rightred_16.png"> </div>
                            </div>
                        </div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div id="ms_box_export_tool" class="ms_box_main gradient_blue">
                            <div class="ms_box_title">
                                <!--<input type="checkbox" class="checkbox" id="exp-imp-settings">-->
                                <label class="choice">Module Export/Import Tool</label>
                                &nbsp;<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Use this tool to export your module structure into a file and then import the module file into another instance of ITAM."/> </div>
                            <div class="ms_box_email" style="padding-top: 15px; display:block;">
                                <span>
                                    <input id="export_import_type_1"  name="export_import_type" class="element radio" type="radio" value="export" checked="checked" style="margin-left: 0px" />
                                    <label for="export_import_type_1">Export Module</label>
                                    </span> <span style="margin-left: 20px">
                                    <input id="export_import_type_2"  name="export_import_type" class="element radio" type="radio" value="import" />
                                    <label for="export_import_type_2">Import Module</label>
                                </span>
                                <div id="tab_export_form">
                                    <label style="margin-top: 10px" class="description" for="export_form_id">Choose Module to Export</label>
                                    <select class="element select" id="export_form_id" name="export_form_id" style="width: 300px;margin-right: 10px">
                                        <?php
                                        if(!empty($form_list_array)){
                                            foreach ($form_list_array as $value) {
                                                echo "<option value=\"{$value['form_id']}\">{$value['form_name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <input type="button" id="ms_btn_export_form" value="Export" class="bb_button bb_grey">
                                </div>
                                <div id="tab_import_form" style="display: none">
                                    <div id="ms_form_import_upload">
                                        <label class="description" for="ms_form_import_file">Upload Module File</label>
                                        <input id="ms_form_import_file" name="ms_form_import_file" class="element file" type="file" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <input type="hidden" id="submit_form" name="submit_form" value="1">
            </form>
            <div id="license_box" data-licensekey="<?php echo noHTML($la_settings['license_key']); ?>">
                <table id="license_box_table" width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <th colspan="2" scope="col">License Information</th>
                    </tr>
                    <tr>
                        <td class="ms_lic_left" width="50%" align="right">Customer ID</td>
                        <td class="ms_lic_right" width="50%"><span id="lic_customer_id">
              <?php if(!empty($la_settings['customer_id'])){echo noHTML($la_settings['customer_id']);}else{echo '-none-';} ?>
              </span></td>
                    </tr>
                    <tr>
                        <td class="ms_lic_left" align="right">Name</td>
                        <td class="ms_lic_right"><span id="lic_customer_name">
              <?php if(!empty($la_settings['customer_name'])){echo noHTML($la_settings['customer_name']);}else{echo '-none-';} ?>
              </span>
                            <?php if(empty($la_settings['customer_id'])){ echo '<a id="lic_activate" href="#">activate now</a>'; } ?></td>
                    </tr>
                    <tr>
                        <td class="ms_lic_left" align="right">License Type</td>
                        <td class="ms_lic_right"><span id="lic_type"><?php echo $license_type; ?></span></td>
                    </tr>
                    <tr>
                        <td class="ms_lic_left" align="right">IT Audit Machine Version</td>
                        <td class="ms_lic_right"><?php echo noHTML($la_settings['itauditmachine_version']); ?></td>
                    </tr>
                </table>
            </div>
            <div id="dialog-change-password" title="Change Admin Password" class="buttons" style="display: none">
                <form id="dialog-change-password-form" class="dialog-form" style="margin-bottom: 10px">
                    <ul>
                        <li>
                            <label for="dialog-change-password-input1" class="description">Enter New Password</label>
                            <input type="password" id="dialog-change-password-input1" name="dialog-change-password-input1" class="text large" value="">
                            <label for="dialog-change-password-input2" style="margin-top: 15px" class="description">Confirm New Password</label>
                            <input type="password" id="dialog-change-password-input2" name="dialog-change-password-input2" class="text large" value="">
                        </li>
                    </ul>
                </form>
            </div>
            <div id="dialog-change-license" title="Change License Key" class="buttons" style="display: none">
                <form id="dialog-change-license-form" class="dialog-form" style="margin-bottom: 10px">
                    <ul>
                        <li>
                            <label for="dialog-change-license-input" class="description">Enter New License Key</label>
                            <input type="text" id="dialog-change-license-input" name="dialog-change-license-input" class="text large" value="">
                        </li>
                    </ul>
                </form>
            </div>
            <div id="dialog-form-import-success" title="Success! Import completed" class="buttons" style="display: none; text-align: center;"> <img src="images/navigation/005499/50x50/Success.png">
                <p> <strong>The following form has been imported:</strong><br/>
                    <a id="form-imported-link" target="_blank" style="color: #529214;font-size: 120%;border: none;background: none;float: none" href="#">x</a> </p>
            </div>
            <div id="dialog-warning" title="Error! Import failed" class="buttons" style="display: none; text-align: center;"> <img src="images/navigation/ED1C2A/50x50/Warning.png">
                <p id="dialog-warning-msg" style="margin-bottom: 20px"> The form file seems to be corrupted.<br/>
                    Please try again with another file. </p>
            </div>
            <div id="dialog-confirm-tile-delete" title="Are you sure you want to delete selected tile?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
                <input type="hidden" id="tile_delete_id">
                <p id="dialog-confirm-admin-delete-msg"> This action cannot be undone.<br/>
                    <strong id="dialog-confirm-admin-delete-info">The tile will be deleted permanently and no longer has access to IT Audit Machine.</strong><br/><br/>
                </p>
            </div>
        </div>
        <!-- /end of content_body -->

    </div>
    <!-- /.post -->
</div>
<!-- /#content -->
<div id="processing-dialog" style="display: none;text-align: center;font-size: 150%;">
    Processing Request...<br>
    <img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<?php
$footer_data =<<<EOT
<style>
#misc-settings-div label.description.label_red {
    color: red;
}
</style>
<script type="text/javascript">
	$(function(){
		{$jquery_data_code}
    });
</script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/uploadifive/jquery.uploadifive.js"></script>
<script type="text/javascript" src="js/ckeditor5-classic/build/ckeditor.js"></script>
<script type="text/javascript" src="js/main_settings.js"></script>
<script type="text/javascript">

$(document).ready(function(e) {
    $('input#enable_registration_notification').click(function(){
		if($(this).prop('checked') == true){
			$('#div_enable_registration_notification').slideDown();
		}else{
			$('#div_enable_registration_notification').slideUp();
		}
	});
    $('input#enable_welcome_message_notification').click(function(){
		if($(this).prop('checked') == true){
			$('#div_enable_welcome_message_notification').slideDown();
		}else{
			$('#div_enable_welcome_message_notification').slideUp();
		}
	});
	$('input#security-settings').click(function(){
		if($(this).prop("checked") == true){
			$("#ss_box_misc .ms_box_email").slideDown();
		}else{
			$("#ss_box_misc .ms_box_email").slideUp();
		}
	});
	$('input#miscellaneous-settings').click(function(){
		if($(this).prop("checked") == true){
			$("#misc-settings-div").slideDown();
		}else{
			$("#misc-settings-div").slideUp();
		}
	});
	$('input#exp-imp-settings').click(function(){
		if($(this).prop("checked") == true){
			$("#ms_box_export_tool .ms_box_email").slideDown();
		}else{
			$("#ms_box_export_tool .ms_box_email").slideUp();
		}
	});

	$('input#saml_login').click(function(){
		if($(this).prop("checked") == true){
			$("#saml_box_misc .ms_box_email").slideDown();
		}else{
			$("#saml_box_misc .ms_box_email").slideUp();
		}
    });
    
    $('input#enable_announcement_slider').click(function(){
		if($(this).prop("checked") == true){
			$("#management_announcement_slider #ss_box_misc").slideDown();
		}else{
			$("#management_announcement_slider #ss_box_misc").slideUp();
		}
	});

	$('input#enable_password_expiration').click(function(){
		if($(this).prop("checked") == true){
			$("div#div_enable_password_expiration").slideDown();
		}else{
			$("div#div_enable_password_expiration").slideUp();
		}
	});
    $('input#enable_account_suspension_inactive').click(function(){
        if($(this).prop("checked") == true){
            $("div#div_enable_account_suspension_inactive").slideDown();
        }else{
            $("div#div_enable_account_suspension_inactive").slideUp();
        }
    });
    $('input#enable_account_deletion_inactive').click(function(){
        if($(this).prop("checked") == true){
            $("div#div_enable_account_deletion_inactive").slideDown();
        }else{
            $("div#div_enable_account_deletion_inactive").slideUp();
        }
    });
});
</script>

EOT;

require('includes/footer.php');
?>
