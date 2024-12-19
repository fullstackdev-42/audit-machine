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
    require('includes/users-functions.php');
    require('../itam-shared/includes/integration-helper-functions.php');

    $form_id = (int) la_sanitize($_GET['id']);

    if(!empty($_POST['form_id'])){
        $form_id = (int) la_sanitize($_POST['form_id']);
    }
    
    $dbh = la_connect_db();
    $la_settings = la_get_settings($dbh);

    //check permission, is the user allowed to access this page?
    if(empty($_SESSION['la_user_privileges']['priv_administer'])){
        $user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

        //this page need edit_form permission
        if(empty($user_perms['edit_form'])){
            $_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

            $ssl_suffix = la_get_ssl_suffix();
            header("Location: restricted.php");
            exit;
        }
    }

    $chatstack_domain = $chatstack_id = '';
    //get form properties
    $query  = "select 
                    form_name,
                    chat_bot_enable,
                    chat_bot_type,
                    saint_enable,
                    nessus_enable,
                    migration_wizard_enable
                 from 
                     ".LA_TABLE_PREFIX."forms 
                where 
                     form_id = ?";
    $params = array($form_id);
    
    $sth = la_do_query($query,$params,$dbh);
    $row = la_do_fetch_result($sth);
    
    if(!empty($row)){
        $row['form_name']   = la_trim_max_length($row['form_name'],55);

        $form_name          = noHTML($row['form_name']);
        $chat_bot_enable = (int) $row['chat_bot_enable'];
        $chat_bot_type = $row['chat_bot_type'];
        $saint_enable = (int) $row['saint_enable'];
        $nessus_enable = (int) $row['nessus_enable'];
        $migration_wizard_enable = (int) $row['migration_wizard_enable'];

        if( $chat_bot_enable ) {
            $query  = "select 
                    field_name,
                    field_value
                from 
                     ".LA_TABLE_PREFIX."form_integration_fields
                where 
                     form_id = ?";
            $params = array($form_id);
            
            $sth = la_do_query($query,$params,$dbh);
            
            while($row = la_do_fetch_result($sth)){
                ${$row['field_name']} = $row['field_value'];
            }
        }

        if($chat_bot_enable) {
            $chatbot_service_class = "enabled";
        } else {
            $chatbot_service_class = "disabled";
        }
        if($saint_enable) {
            $saint_service_class = "enabled";
        } else {
            $saint_service_class = "disabled";
        }
        if($nessus_enable) {
            $nessus_service_class = "enabled";
        } else {
            $nessus_service_class = "disabled";
        }
        if($migration_wizard_enable) {
            $migration_wizard_service_class = "enabled";
        } else {
            $migration_wizard_service_class = "disabled";
        }
    }
    
    //get entities that can access this form
    $form_accessible_entities = array();

    $accessible_entity_ids = array();
    $query_entity_form_relation = "SELECT `entity_id` FROM ".LA_TABLE_PREFIX."entity_form_relation WHERE `form_id` = ?";
    $sth_entity_form_relation = la_do_query($query_entity_form_relation, array($form_id), $dbh);
    while($row_entity_form_relation = la_do_fetch_result($sth_entity_form_relation)){
        array_push($accessible_entity_ids, $row_entity_form_relation["entity_id"]);
    }
    array_unique($accessible_entity_ids);

    if(count($accessible_entity_ids) > 0) {
        if(in_array("0", $accessible_entity_ids)) {
            $query_entity = "SELECT client_id, company_name, contact_email FROM ".LA_TABLE_PREFIX."ask_clients GROUP BY client_id ORDER BY company_name";
            $sth_entity = la_do_query($query_entity, array(), $dbh);
            while($row_entity = la_do_fetch_result($sth_entity)){
                array_push($form_accessible_entities, array("entity_id" => $row_entity['client_id'], "info" => "[Entity] ".$row_entity['company_name']." (".$row_entity['contact_email'].")"));
            }
        } else {
            $inQueryEntity = implode(',', array_fill(0, count($accessible_entity_ids), '?'));
            $query_entity = "SELECT client_id, company_name FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id` IN ({$inQueryEntity})";
            $sth_entity = la_do_query($query_entity, $accessible_entity_ids, $dbh);
            while($row_entity = la_do_fetch_result($sth_entity)) {
                array_push($form_accessible_entities, array("entity_id" => $row_entity['client_id'], "info" => "[Entity] ".$row_entity['company_name']." (".$row_entity['contact_email'].")"));
            }
        }
    }

    $header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
EOT;

    $current_nav_tab = 'manage_forms';
    require('includes/header.php'); 
?>
<style type="text/css">
    .small_loader_box {
        float: right!important;
    }
</style>
<div id="content" class="full">
  <div class="post integration-settings">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left;">
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> Integration Settings</h2>
          <p>Configure integration options for your form</p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
        <div class="services-container">
            <div class="service-item <?php echo $chatbot_service_class; ?>">
                <div class="service-item-logo" data-enabled="<?php echo $chat_bot_enable; ?>">
                    <img src="images/company_logos/chatstack_logo.png">                        
                </div>
                <?php
                if($chat_bot_enable) {
                ?>
                    <div class="service-disable" id="disable_chatbot_btn">
                        <i class="fas fa-trash"></i>
                    </div>
                <?php
                }
                ?>
                <div class="service-item-content" style="display: none;">
                    <div class="service-item-info">
                        <p>Chat instantly to your website visitors with ChatStack</p>
                        <p class="link"><a target="_blank" href="https://www.chatstack.com/">Visit site for more details</a></p>
                    </div>
                    <div class="service-item-settings">
                        <div style="padding:0px 10px;">
                            <p class="error chatbot-error"></p>
                        </div>
                        <div class="padding-10">
                            <label class="description" for="chatstack_domain">ChatStack Domain: </label>
                            <input id="chatstack_domain" name="chatstack_domain" value="<?=$chatstack_domain?>" type="text" autocomplete = "off">
                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Tooltop for Chatstack Domain">
                        </div>
                        <div class="padding-10">
                            <label class="description" for="chatstack_id">ChatStack ID: </label>
                            <input id="chatstack_id" name="chatstack_id" value="<?=$chatstack_id?>" type="text" autocomplete = "off">
                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Tooltop for Chatstack ID">
                        </div>
                        <div class="padding-10">
                            <button id="save_chatstack_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="service-item <?php echo $saint_service_class; ?>">
                <div class="service-item-logo" data-enabled="<?php echo $saint_enable; ?>">
                    <img src="images/company_logos/saint_logo.png">                        
                </div>
                <?php
                if($saint_enable) {
                ?>
                    <div class="service-disable" id="disable_saint_btn">
                        <i class="fas fa-trash"></i>
                    </div>
                <?php
                }
                ?>
                <div class="service-item-content" style="display: none;">
                    <div class="service-item-info">
                        <p>Carson & SAINT provides comprehensive cybersecurity product and service solutions to both public and private-sectors, world-wide. SAINT's award-winning vulnerability management solution enables customers, service providers and technology partners of any size to assess risk exposures and reduce risk, with a true business context. Our professional services portfolio includes a broad spectrum of offerings, from advisory services to C-level managers, to technology and industry-compliance expertise and professional services. Our core value is to action as your Trusted Partner to deliver powerful and effective security and compliance solutions as part of your overall risk management strategy.</p>
                        <p class="link"><a target="_blank" href="https://www.carson-saint.com/">Visit site for more details</a></p>
                    </div>
                    <div class="service-item-settings">
                    <?php
                        //get SAINT settings
                        $saint_settings = get_saint_settings($dbh, $form_id);
                        $saint_num = count($saint_settings);
                        if($saint_num > 0) {
                            for($i = 0; $i<$saint_num; $i++) {
                                $saint_error = "";
                                if($saint_settings[$i]['saint_api_valid'] == "0") {
                                    $saint_error = $saint_settings[$i]['saint_error_msg'];
                                } 
                    ?>
                                <div class="saint-settings" saint-id="<?php echo $saint_settings[$i]['id']; ?>">
                                    <div class="inner-box api-config">
                                        <label class="description">- SAINT API Configuration</label>
                                        <div class="row">
                                            <p class="notification saint-notification" style="display: none;"></p>
                                            <p class="error saint-error" style="<?php if($saint_error == ""){echo "display: none;";} ?>"><?php echo $saint_error; ?></p>
                                        </div>                                    
                                        <div class="row">
                                            <label>SAINT Web Server URL: </label>
                                            <input type="text" name="saint_url" class="saint-url" placeholder="http://saintserver.com" value="<?php echo $saint_settings[$i]['saint_url']; ?>">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the SAINT web server URL that doesn't contain a port number.">
                                        </div>
                                        <div class="row">
                                            <label>SAINT API Port: </label>
                                            <input type="text" name="saint_port" class="saint-port" placeholder="4242" value="<?php echo $saint_settings[$i]['saint_port']; ?>">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the port number on which to listen to for API requests. You should log into the SAINT web interface, click on <span class='font-italic'>Configuration/API</span> and enter the port number for API requests before entering it here.">
                                        </div>
                                        <div class="row">
                                            <label>SAINT API Token: </label>
                                            <input type="text" name="saint_api_token" class="saint-api-token" value="<?php echo $saint_settings[$i]['saint_api_token']; ?>">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please copy the API token and paste it here.<br><br>How to generate an API token:<br>1. Log into SAINT using the web interface.<br>2. Click on <span class='font-italic'>Profile</span> if the desired API user is the user logged into the web interface. Otherwise, click on <span class='font-italic'>Manage</span>, then <span class='font-italic'>Users</span>, then the EDIT button beside the desired user.<br>3. If an API token has not yet been created for this user, click <span class='font-italic'>Create</span>.<br>4. The API token appears beside the label “API Token”.">
                                        </div>
                                        <div class="row">
                                            <label>SAINT Job ID: </label>
                                            <input type="text" name="saint_job_id" class="saint-job-id" value="<?php echo $saint_settings[$i]['saint_job_id']; ?>">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the ID of the SAINT job that you want to import a scan report from.">
                                        </div>
                                        <div class="row">
                                            <input type="checkbox" class="saint-ssl-enable" name="saint_ssl_enable" <?php if($saint_settings[$i]['saint_ssl_enable'] == "1"){ echo 'checked="checked"'; } ?>>
                                            <label class="saint-ssl-enable-label">Use TLS/SSL</label>
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="If SSL was selected in SAINT configurations, please check this option.">
                                        </div>
                                        <div class="row">
                                            <button class="bb_button bb_small bb_green saint-api-test">Test this configuration</button>
                                        </div>
                                    </div>
                                    <div class="inner-box select-entity">
                                        <label class="description">- Assign Scan Report Data To:</label>
                                        <div class="row">
                                            <select class="assignees" name="assignees" autocomplete="off" style="width: 300px;">
                                                <?php
                                                    $select_assignee = "";
                                                    foreach($form_accessible_entities as $value) {
                                                        if($saint_settings[$i]['entity_id'] == $value['entity_id']) {
                                                            $select_assignee .= '<option role="entity" selected value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                        } else {
                                                            $select_assignee .= '<option role="entity" value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                        }
                                                    }
                                                    echo $select_assignee;
                                                ?>
                                            </select>
                                        </div>                                    
                                    </div>
                                    <div class="inner-box">
                                        <label class="description">- Import a SAINT Scan Report Every <span><input type="text" name="frequency" class="frequency" value="<?php echo $saint_settings[$i]['frequency']; ?>"></span> Day(s)</label>
                                    </div>
                                    <div class="remove-saint">
                                        <div class="remove-saint-btn">
                                            <img src="images/icons/51_green_16.png">
                                        </div>
                                    </div>
                                </div>
                    <?php
                            }
                        } else {
                    ?>
                            <div class="saint-settings" saint-id="0">
                                <div class="inner-box api-config">
                                    <label class="description">- SAINT API Configuration</label>
                                    <div class="row">
                                        <p class="notification saint-notification" style="display: none;"></p>
                                        <p class="error saint-error" style="display: none;"></p>
                                    </div>                                    
                                    <div class="row">
                                        <label>SAINT Web Server URL: </label>
                                        <input type="text" name="saint_url" class="saint-url" placeholder="http://saintserver.com">
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the SAINT web server URL that doesn't contain a port number.">
                                    </div>
                                    <div class="row">
                                        <label>SAINT API Port: </label>
                                        <input type="text" name="saint_port" class="saint-port" placeholder="4242">
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the port number on which to listen to for API requests. You should log into the SAINT web interface, click on <span class='font-italic'>Configuration/API</span> and enter the port number for API requests before entering it here.">
                                    </div>
                                    <div class="row">
                                        <label>SAINT API Token: </label>
                                        <input type="text" name="saint_api_token" class="saint-api-token">
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please copy the API token and paste it here.<br><br>How to generate an API token:<br>1. Log into SAINT using the web interface.<br>2. Click on <span class='font-italic'>Profile</span> if the desired API user is the user logged into the web interface. Otherwise, click on <span class='font-italic'>Manage</span>, then <span class='font-italic'>Users</span>, then the EDIT button beside the desired user.<br>3. If an API token has not yet been created for this user, click <span class='font-italic'>Create</span>.<br>4. The API token appears beside the label “API Token”.">
                                    </div>
                                    <div class="row">
                                        <label>SAINT Job ID: </label>
                                        <input type="text" name="saint_job_id" class="saint-job-id">
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the ID of the SAINT job that you want to import a scan report from.">
                                    </div>
                                    <div class="row">
                                        <input type="checkbox" class="saint-ssl-enable" name="saint_ssl_enable" value="1">
                                        <label class="saint-ssl-enable-label">Use TLS/SSL</label>
                                        <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="If SSL was selected in SAINT configurations, please check this option.">
                                    </div>
                                    <div class="row">
                                        <button class="bb_button bb_small bb_green saint-api-test">Test this configuration</button>
                                    </div>
                                </div>
                                <div class="inner-box select-entity">
                                    <label class="description">- Assign Scan Report Data To:</label>
                                    <div class="row">
                                        <select class="assignees" name="assignees" autocomplete="off" style="width: 300px;">
                                            <?php
                                                $select_assignee = "";
                                                foreach($form_accessible_entities as $value) {
                                                    $select_assignee .= '<option role="entity" value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                }
                                                echo $select_assignee;
                                            ?>
                                        </select>
                                    </div>                                    
                                </div>
                                <div class="inner-box">
                                    <label class="description">- Import a SAINT Scan Report Every <span><input type="text" name="frequency" class="frequency"></span> Day(s)</label>
                                </div>
                                <div class="remove-saint" style="display: none;">
                                    <div class="remove-saint-btn">
                                        <img src="images/icons/51_green_16.png">
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    ?>
                        <div class="add-new-saint">
                            <button id="save_saint_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </button>
                            <div class="add-new-saint-btn">
                                <img src="images/icons/49_green_16.png">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="service-item <?php echo $nessus_service_class; ?>">
                <div class="service-item-logo" data-enabled="<?php echo $nessus_enable; ?>">
                    <img src="images/company_logos/tenable_logo.png">                        
                </div>
                <?php
                if($nessus_enable) {
                ?>
                    <div class="service-disable" id="disable_nessus_btn">
                        <i class="fas fa-trash"></i>
                    </div>
                <?php
                }
                ?>
                <div class="service-item-content" style="display: none;">
                    <div class="service-item-info">
                        <p>The Tenable Cyber Exposure platform is the industry’s first solution to holistically assess, manage and measure cyber risk across the modern attack surface. The Tenable platform uniquely provides the breadth of visibility into cyber risk across IT, Cloud, IoT and OT environments and the depth of analytics to measure and communicate cyber risk in business terms to make better strategic decisions.
                        As the creator of Nessus, Tenable extended its expertise in vulnerabilities to deliver the world’s first platform to see and secure any digital asset on any computing platform.</p>
                        <p class="link"><a target="_blank" href="https://www.tenable.com/">Visit site for more details</a></p>
                    </div>
                    <div class="service-item-settings">
                        <?php
                        //get Nessus settings
                        $nessus_settings = get_nessus_settings($dbh, $form_id);
                        $nessus_num = count($nessus_settings);
                        if($nessus_num > 0) {
                            for($i = 0; $i<$nessus_num; $i++) {
                                $nessus_error = "";
                                if($nessus_settings[$i]['nessus_api_valid'] == "0") {
                                    $nessus_error = $nessus_settings[$i]['nessus_error_msg'];
                                } 
                    ?>
                                <div class="nessus-settings" nessus-id="<?php echo $nessus_settings[$i]['id']; ?>">
                                    <div class="inner-box api-config">
                                        <label class="description">- Nessus API Configuration</label>
                                        <div class="row">
                                            <p class="notification nessus-notification" style="display: none;"></p>
                                            <p class="error nessus-error" style="<?php if($nessus_error == ""){echo "display: none;";} ?>"><?php echo $nessus_error; ?></p>
                                        </div>
                                        <div class="row">
                                            <label>Access Key: </label>
                                            <textarea name="nessus_access_key" class="nessus-access-key" rows="3"><?php echo $nessus_settings[$i]['nessus_access_key']; ?></textarea>
                                        </div>
                                        <div class="row">
                                            <label>Secret Key: </label>
                                            <textarea name="nessus_secret_key" class="nessus-secret-key" rows="3"><?php echo $nessus_settings[$i]['nessus_secret_key']; ?></textarea>
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please generate API keys for your own account in the new Tenable.io interface, copy and paste them here.<br><br>How to generate API keys:<br>1. Log into the new Teable.io interface.<br>2. In the upper-right conner, click the <i class='far fa-user'></i> button and then click <span class='font-italic'>My Account</span>.<br>3. Click the <span class='font-italic'>API Keys</span> and then click <span class='font-italic'>Generate</span>.<br><br><span class='font-bold'>Caution</span>: Any existing API keys are replaced when you click the <span class='font-italic'>Generate button</span>. You must update the applications where the previous API keys were used.<br>Be sure to copy the access and secret keys before you close the <span class='font-bold'>API Keys</span> tab. After you close this tab, you cannot retrieve the keys from Tenable.io.">
                                        </div>
                                        <div class="row">
                                            <label>Scan Name: </label>
                                            <input type="text" name="nessus_scan_name" class="nessus-scan-name" value="<?php echo $nessus_settings[$i]['nessus_scan_name']; ?>">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the name of the scan that you want to import a scan report from.">
                                        </div>
                                        <div class="row">
                                            <button class="bb_button bb_small bb_green nessus-api-test">Test this configuration</button>
                                        </div>
                                    </div>
                                    <div class="inner-box select-entity">
                                        <label class="description">- Assign Scan Report Data To:</label>
                                        <div class="row">
                                            <select class="assignees" name="assignees" autocomplete="off" style="width: 300px;">
                                                <?php
                                                    $select_assignee = "";
                                                    foreach($form_accessible_entities as $value) {
                                                        if($nessus_settings[$i]['entity_id'] == $value['entity_id']) {
                                                            $select_assignee .= '<option role="entity" selected value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                        } else {
                                                            $select_assignee .= '<option role="entity" value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                        }
                                                    }
                                                    echo $select_assignee;
                                                ?>
                                            </select>
                                        </div>                                    
                                    </div>
                                    <div class="inner-box">
                                        <label class="description">- Import a Nessus Scan Report Every <span><input type="text" name="frequency" class="frequency" value="<?php echo $nessus_settings[$i]['frequency']; ?>"></span> Day(s)</label>
                                    </div>
                                    <div class="remove-nessus">
                                        <div class="remove-nessus-btn">
                                            <img src="images/icons/51_green_16.png">
                                        </div>
                                    </div>
                                </div>
                    <?php
                            }
                        } else {
                    ?>
                            <div class="nessus-settings" nessus-id="0">
                                <div class="inner-box api-config">
                                    <label class="description">- Nessus API Configuration</label>
                                    <div class="row">
                                        <p class="notification nessus-notification" style="display: none;"></p>
                                        <p class="error nessus-error" style="display: none;"></p>
                                    </div>
                                    <div class="row">
                                            <label>Access Key: </label>
                                            <textarea name="nessus_access_key" class="nessus-access-key" rows="3"></textarea>
                                        </div>
                                        <div class="row">
                                            <label>Secret Key: </label>
                                            <textarea name="nessus_secret_key" class="nessus-secret-key" rows="3"></textarea>
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please generate API keys for your own account in the new Tenable.io interface, copy and paste them here.<br><br>How to generate API keys:<br>1. Log into the new Teable.io interface.<br>2. In the upper-right conner, click the <i class='far fa-user'></i> button and then click <span class='font-italic'>My Account</span>.<br>3. Click the <span class='font-italic'>API Keys</span> and then click <span class='font-italic'>Generate</span>.<br><br><span class='font-bold'>Caution</span>: Any existing API keys are replaced when you click the <span class='font-italic'>Generate button</span>. You must update the applications where the previous API keys were used.<br>Be sure to copy the access and secret keys before you close the <span class='font-bold'>API Keys</span> tab. After you close this tab, you cannot retrieve the keys from Tenable.io.">
                                        </div>
                                        <div class="row">
                                            <label>Scan Name: </label>
                                            <input type="text" name="nessus_scan_name" class="nessus-scan-name">
                                            <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="Please enter the name of the scan that you want to import a scan report from.">
                                        </div>
                                    <div class="row">
                                        <button class="bb_button bb_small bb_green nessus-api-test">Test this configuration</button>
                                    </div>
                                </div>
                                <div class="inner-box select-entity">
                                    <label class="description">- Assign Scan Report Data To:</label>
                                    <div class="row">
                                        <select class="assignees" name="assignees" autocomplete="off" style="width: 300px;">
                                            <?php
                                                $select_assignee = "";
                                                foreach($form_accessible_entities as $value) {
                                                    $select_assignee .= '<option role="entity" value="'.$value['entity_id'].'">'.$value['info'].'</option>';
                                                }
                                                echo $select_assignee;
                                            ?>
                                        </select>
                                    </div>                                    
                                </div>
                                <div class="inner-box">
                                    <label class="description">- Import a Nessus Scan Report Every <span><input type="text" name="frequency" class="frequency"></span> Day(s)</label>
                                </div>
                                <div class="remove-nessus" style="display: none;">
                                    <div class="remove-nessus-btn">
                                        <img src="images/icons/51_green_16.png">
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    ?>                            
                        <div class="add-new-nessus">
                            <button id="save_nessus_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings </button>
                            <div class="add-new-nessus-btn">
                                <img src="images/icons/49_green_16.png">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="service-item <?php echo $migration_wizard_service_class; ?>">
                <div class="service-item-logo" data-enabled="<?php echo $migration_wizard_enable; ?>">
                    <img src="images/company_logos/migration_wizard_logo.png">                        
                </div>
                <?php
                $migration_wizard_generate_authorization_key_style = "display: none";
                $migration_wizard_save_btn_label = "Save Settings";
                if($migration_wizard_enable) {
                    $query_migration_wizard = "SELECT * FROM ".LA_TABLE_PREFIX."form_migration_wizard_settings WHERE `form_id` = ?";
                    $sth_migration_wizard = la_do_query($query_migration_wizard, array($form_id), $dbh);
                    $res_migration_wizard = la_do_fetch_result($sth_migration_wizard);
                    $migration_wizard_target_url = $res_migration_wizard["target_url"];
                    $migration_wizard_connector_role = $res_migration_wizard["connector_role"];
                    if($migration_wizard_connector_role == 0) {
                        $migration_wizard_connector_role_sender = "checked";
                        $migration_wizard_connector_role_receiver = "";
                        $migration_wizard_generate_authorization_key_style = "";
                    } else {
                        $migration_wizard_connector_role_sender = "";
                        $migration_wizard_connector_role_receiver = "checked";
                        $migration_wizard_save_btn_label = "Migrate Entry Data";
                    }
                    $migration_wizard_key = $res_migration_wizard["key"];
                ?>
                    <div class="service-disable" id="disable_migration_wizard_btn">
                        <i class="fas fa-trash"></i>
                    </div>
                <?php
                }
                ?>
                <div class="service-item-content" style="display: none;">
                    <div class="service-item-info">
                        <p>Migrate entry data from one system to another.</p>
                    </div>
                    <div class="service-item-settings">
                        <div style="padding:0px 10px;">
                            <p class="error migration-wizard-error"></p>
                        </div>
                        <div class="padding-10">
                            <label class="description" for="migration_wizard_target_url">System URL: <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="The System URL in which you plan to send or receive entry data, to or from."></label>
                            <input id="migration_wizard_target_url" name="migration_wizard_target_url" value="<?=$migration_wizard_target_url?>" type="text" autocomplete = "off" placeholder="https://qualityassurance.auditmachine.com">
                        </div>
                        <div class="padding-10">
                            <label class="description" for="migration_wizard_connector_role">Connector Role: </label>
                            <input id="migration_wizard_connector_role_sender" name="migration_wizard_connector_role" value="0" type="radio" style="width: 20px!important;margin-left:50px;" <?php echo $migration_wizard_connector_role_sender; ?>> Sender<br>
                            <input id="migration_wizard_connector_role_receiver" name="migration_wizard_connector_role" value="1" type="radio" style="width: 20px!important;margin-left:50px;" <?php echo $migration_wizard_connector_role_receiver; ?>> Receiver
                        </div>
                        <div class="padding-10">
                            <label class="description" for="migration_wizard_key">Authorization Key: <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" title="The Key that is used to encrypt and decrypt the data."></label>                            
                            <input id="migration_wizard_key" name="migration_wizard_key" value="<?=$migration_wizard_key?>" type="text" autocomplete = "off" style="width: 200px!important;">
                            <button id="migration_wizard_generate_authorization_key" class="bb_button bb_small bb_green" style="<?php echo $migration_wizard_generate_authorization_key_style; ?>">Generate Key</button>
                        </div>
                        <div class="padding-10">
                            <button id="save_migration_wizard_btn" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Save.png"> <?php echo  $migration_wizard_save_btn_label; ?> </button>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="form_id" name="form_id" value="<?php echo $form_id; ?>">
        </div>
    </div>
    <!-- /end of content_body -->    
  </div>
  <!-- /.post -->
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
    <img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-remove-saint-config" title="Do you really want to delete the SAINT API configuration?" class="buttons" style="display: none; text-align:center;">
    <img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p id="dialog-remove-saint-config-msg"> This SAINT API configuration and imported reports will be deleted permanently. </p>
    <input type="hidden" id="remove_saint_id">
</div>
<div id="dialog-remove-nessus-config" title="Do you really want to delete the Nessus API configuration?" class="buttons" style="display: none; text-align:center;">
    <img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p id="dialog-remove-nessus-config-msg"> This Nessus API configuration and imported reports will be deleted permanently. </p>
    <input type="hidden" id="remove_nessus_id">
</div>
<div id="dialog-confirm-disable-chatbot" title="Do you really want to disable the ChatStack integration settings?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p>This action cannot be undone.<br/>
        <strong>All the ChatStack integration settings will be deleted permanently.</strong><br/><br/>
    </p>
</div>
<div id="dialog-confirm-disable-saint" title="Do you really want to disable the SAINT integration settings?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p>This action cannot be undone.<br/>
        <strong>All the SAINT integration settings and imported reports will be deleted permanently.</strong><br/><br/>
    </p>
</div>
<div id="dialog-confirm-disable-nessus" title="Do you really want to disable the Nessus integration settings?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p> This action cannot be undone.<br/>
        <strong>All the Nessus integration settings and imported reports will be deleted permanently.</strong><br/><br/>
    </p>
</div>
<div id="dialog-confirm-disable-migration-wizard" title="Do you really want to disable the Migration Wizard settings?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p> This action cannot be undone.<br/>
        <strong>The current Migration Wizard's settings will be permanently deleted.</strong><br/><br/>
    </p>
</div>
<!-- /#content -->
<?php
    $footer_data =<<<EOT
    <script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.tools.min.js"></script>
    <script type="text/javascript" src="js/integration_settings.js"></script>
EOT;
    
    require('includes/footer.php');