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
require('includes/users-functions.php');
require('includes/post-functions.php');

$form_id = (int) la_sanitize($_GET['id']);

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

//check permission, is the user allowed to access this page?
if (empty($_SESSION['la_user_privileges']['priv_administer'])) {
    $user_perms = la_get_user_permissions($dbh, $form_id, $_SESSION['la_user_id']);

    //this page need edit_form permission
    if (empty($user_perms['edit_form'])) {
        $_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

        $ssl_suffix = la_get_ssl_suffix();
        header("Location: restricted.php");
        exit;
    }
}
$table = LA_TABLE_PREFIX . "form_template_data";

if (la_is_form_submitted()) {
    $input_array   = la_sanitize($_POST);
    $params = array(
        ':data_value' => $input_array['template_data'], 
        ':form_id' => (int) $form_id,
        ':template_id' => (int) $input_array['template_id']
    );
    if ($input_array['action'] == 'update') {
        $query_update = "UPDATE `{$table}` SET `data` = :data_value, `template_id` = :template_id WHERE `form_id` = :form_id ";
        la_do_query($query_update, $params, $dbh);
    } else {
        $query_insert = "INSERT INTO `{$table}` SET `data` = :data_value, `form_id` = :form_id, , `template_id` = :template_id";
        la_do_query($query_insert, $params, $dbh);
    }

    $_SESSION['LA_SUCCESS'] = 'Template has been saved.';
}

$query  = "SELECT * FROM `{$table}` WHERE `form_id` = :form_id LIMIT 1";
$result = la_do_query($query, array(':form_id' => $form_id), $dbh);
$row    = la_do_fetch_result($result);

if ($row['id']) {
    $action = "update";
    $template_data = $row['data'];
    $template_id = $row['template_id'];
} else {
    $action = "create";
}

//get form properties
$query  = "select 
            form_name,
            form_page_total,
            logic_field_enable,
            logic_page_enable,
            logic_email_enable,
            logic_webhook_enable,
            form_review,
            payment_enable_merchant,
            payment_merchant_type
            from 
                " . LA_TABLE_PREFIX . "forms 
        where 
                form_id = ?";
$params = array($form_id);

$sth = la_do_query($query, $params, $dbh);
$row = la_do_fetch_result($sth);

if (!empty($row)) {
    $row['form_name']     = la_trim_max_length($row['form_name'], 55);
    $form_name             = noHTML($row['form_name']);
    $form_page_total    = (int) $row['form_page_total'];
}


//get the list of all fields within the form (without any child elements)
$query = "select 
					element_id,
					if(element_type = 'matrix',element_guidelines,element_title) element_title,
					element_type,
					element_page_number,
                    element_position,
                    element_machine_code
 				from 
 					" . LA_TABLE_PREFIX . "form_elements 
			   where 
					form_id = ? and 
					element_status = 1 and 
					element_is_private = 0 and 
					element_type <> 'page_break' and 
                    element_type <> 'casecade_form' and 
					element_matrix_parent_id = 0 
		    order by 
		    		element_position asc";
$params = array($form_id);
$sth = la_do_query($query, $params, $dbh);

$all_fields_array = array();
$all_machine_codes = [];
while ($row = la_do_fetch_result($sth)) {
    $element_page_number = (int) $row['element_page_number'];
    $element_id          = (int) $row['element_id'];
    $element_title = noHTML($row['element_title']);
    $element_position      = (int) $row['element_position'] + 1;

    if (empty($element_title)) {
        $element_title = '-untitled field-';
    }

    if (strlen($element_title) > 120) {
        $element_title = substr($element_title, 0, 120) . '...';
    }

    $all_fields_array[$element_page_number][$element_id]['element_title'] = $element_position . '. ' . $element_title;

    if (!empty($row['element_machine_code'])) {
        $all_machine_codes[$element_id] = $row['element_machine_code'];
    }
}

$all_machine_codes_json = json_encode($all_machine_codes);


$header_data = <<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<style>
select.medium{
	width:100% !important;      
}
</style>
EOT;

$current_nav_tab = 'manage_forms';
require('includes/header.php');
?>
<div id="content" class="full">
    <div class="post logic_settings">
        <div class="content_header">
            <div class="content_header_title">
                <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>" . $form_name . '</a>'; ?> <img src="images/icons/resultset_next.gif" /> Template Code Settings</h2>
                <p>Set template for the document</p>
            </div>
        </div>
        <?php la_show_message(); ?>
        <div class="content_body">
            <div class="left">
                <form id="ls_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']) . '?id=' . $form_id; ?>">
                    <div style="display:none;">
                        <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                        <input type="hidden" name="form_id" value="<?= $form_id ?>" />
                        <input type="hidden" name="submit_form" value="1" />

                        <input type="hidden" name="action" value="<?= $action ?>" />
                        <input type="hidden" name="template_id" id="template_id" value="" />
                    </div>
                    <textarea name="template_data" id="editor" rows="10" cols="80">
                        <?= $template_data ?>
                    </textarea>

                    <div><button id="save_form" type="submit" class="bb_button bb_green" style="margin-top: 10px;"><img src="images/navigation/FFFFFF/24x24/Create_new_form.png"> Save Form</button></div>
                </form>
            </div>
            <!-- /end of content_body -->
            <div class="right">
                <div id="ls_box_field_rules" class="ns_box_main gradient_blue">
                    <div class="ns_box_title">
                        <div class="ls_box_content">
                            <label class="description" for="ls_select_template" style="margin-top: 2px"> Select a Template </label>
                            <select class="select medium" id="ls_select_template" name="ls_select_template" autocomplete="off">
                                <option value=""></option>
                                <?php
                                $query = "SELECT * FROM " . LA_TABLE_PREFIX . "form_templates";
                                $sth = la_do_query($query, array(), $dbh);
                                while ($row = la_do_fetch_result($sth)) {
                                    $selected = '';
                                    if( $row['id'] == $template_id ) {
                                        $selected = 'selected';
                                    }
                                    echo "<option value=\"{$row['id']}\" {$selected}>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="ls_box_content">
                            <label class="description" for="ls_select_field_rule" style="margin-top: 2px"> Select a Field to get Template Code </label>
                            <select class="select medium" id="ls_select_field_rule" name="ls_select_field_rule" autocomplete="off">
                                <option value=""></option>
                                <?php
                                for ($i = 1; $i <= $form_page_total; $i++) {
                                    if ($form_page_total > 1) {
                                        echo '<optgroup label="Page ' . $i . '">' . "\n";
                                    }

                                    $current_page_fields = array();
                                    $current_page_fields = $all_fields_array[$i];

                                    foreach ($current_page_fields as $element_id => $value) {
                                        if (!empty($all_logic_elements_id)) {
                                            if (in_array($element_id, $all_logic_elements_id)) {
                                                continue;
                                            }
                                        }

                                        $element_title = strip_tags(html_entity_decode($value['element_title']));
                                        echo '<option value="' . $element_id . '">' . $element_title . '</option>' . "\n";
                                    }

                                    if ($form_page_total > 1) {
                                        echo '</optgroup>' . "\n";
                                    }
                                }
                                ?>
                            </select>
                            <label class="description" for="ls_select_field_rule" style="margin-top: 2px"> Machine Code </label>
                            <input id="machine_code_selected" name="" class="text full" value="" type="text"> <span onclick="copyCode()" class="copy-code">Copy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.post -->
</div>
<!-- /#content -->
<style>
    .left {
        width: calc(75% - 40px);
        margin-bottom: 20px;
    }

    .right {
        width: 25%;
    }

    .content_body #ls_box_field_rules {
        width: unset;
    }

    .copy-code {
        cursor: pointer;
        text-decoration: underline;
    }
</style>
<?php
$footer_data = <<<EOT
<script type="text/javascript">
$(function(){
    all_machine_codes_json = {$all_machine_codes_json};	
});
</script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/template_code_settings.js"></script>
<script type="text/javascript" src="js/ckeditor-full/ckeditor.js"></script>
<script type="text/javascript">
    CKEDITOR.replace('editor');
    
    /*$('#save_form').click(function(){
        var desc = CKEDITOR.instances['editor'].getData();
        $('#data').val(desc);
        $('#ls_form').submit();
    });*/

    $('#ls_select_field_rule').on('change', function() {
        $('#machine_code_selected').val('$'+all_machine_codes_json[this.value]+'$');
    });
    $('#ls_select_template').on('change', function() {
        $('#template_id').val(this.value);
    });
    function copyCode() {
        var copyText = document.getElementById("machine_code_selected");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Copied the text: " + copyText.value);
    }
    
</script>

EOT;

require('includes/footer.php');
