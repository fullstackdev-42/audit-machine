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

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
$load_custom_js = false;

//check permission, is the user allowed to access this page?
if (empty($_SESSION['la_user_privileges']['priv_administer'])) {
    exit;
}
$table = LA_TABLE_PREFIX . "form_templates";
$name = '';
$data = '';
$template_id = '';

if (isset($_GET['id'])) {
    $template_id = (int) $_GET['id'];
}

if (la_is_form_submitted()) {
    $input_array   = la_sanitize($_POST);

    if (!empty($template_id)) {
        $query_update = "UPDATE `{$table}` SET `data` = :template_data, `name` = :template_name, `updated_at` = :updated_at WHERE `id` = :template_id";
        $params = array(':template_data' => $input_array['template_data'], ':template_name' => $input_array['template_name'], ':updated_at' => date('Y-m-d H:i:s'), ':template_id' => $template_id);
        la_do_query($query_update, $params, $dbh);
        $_SESSION['LA_SUCCESS'] = 'Template has been saved successfully.';
    } else {
        $query_insert = "INSERT INTO `{$table}` SET `data` = :template_data, `name` = :template_name";
        la_do_query($query_insert, array(':template_data' => $input_array['template_data'], ':template_name' => $input_array['template_name']), $dbh);
        $new_template_id = la_last_insert_id($dbh);

        $_SESSION['LA_SUCCESS'] = 'Template has been saved successfully.';

        header("Location: /auditprotocol/create_template.php?id=".$new_template_id);
        exit();
    }
}

if ($template_id) {
    $query = "SELECT * FROM " . LA_TABLE_PREFIX . "form_templates WHERE id=:id LIMIT 1";
    $sth = la_do_query($query, array(':id' => $template_id), $dbh);
    $row = la_do_fetch_result($sth);

    if ($row['id']) {
        $name = $row['name'];
        $data = $row['data'];
    }
}

//get form properties
$query  = "SELECT 
            form_id,
            form_name
            FROM " . LA_TABLE_PREFIX . "forms
            WHERE
            form_active = 1";
$sth = la_do_query($query, [], $dbh);

$all_forms = [];
while ($form = la_do_fetch_result($sth)) {
    $all_forms[$form['form_id']] = $form['form_name'];
}

$font_family = ['default', 'Century Gothic'];
$load_google_fonts = '';
$query = "SELECT font_family FROM " . LA_TABLE_PREFIX . "fonts WHERE font_family != 'Century Gothic' GROUP BY font_family ORDER BY font_family";
$sth = la_do_query($query, array(), $dbh);
while ($font = la_do_fetch_result($sth)) {
    $font_family[] = $font['font_family'];
    $load_google_fonts .= '<link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family='.$font["font_family"].'">';
}

$header_data = <<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="js/ckeditor5-doc/sample/styles.css">
<style>
    #main {
        width: 100%!important;
    }

    .ck-font-family-dropdown ul.ck-list {
        height: 320px;
        overflow-y: scroll;
    }

    select.medium{
        width:100% !important;
    }

    .document-editor .ck-content p {
        margin-bottom: 0px !important;
    }

    ol {
        list-style-type: decimal;
    }

    p {
        margin: 0;
        padding: 0;
        border: 0;
        font-size: 100%;
        font: inherit;
        vertical-align: baseline;
    }

   .ck-content .table table {
        border-collapse: collapse;
        border-spacing: 0;
        width: 100%;
        height: 100%;
        border: 1px double #000000 !important;
    }

   .ck-content .table table td,
   .ck-content .table table th {
        min-width: 2em;
        border: 1px solid #000000;
        padding-top: 12px !important;
        padding-bottom: 12px !important;
    }

    .ck-content .table table th {
        font-weight: 700;
        background: #0085CC !important;
    }

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

    label.description {
        color: #000;
        display: block;
        font-size: 95%;
        font-weight: 700;
        line-height: 150%;
        padding: 3px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        width: 100%;
    }

    a:link,
    a:visited {
        color: #b38b01 !important;
    }

    .editor {
        min-height: 300px !important;
    }

    .document-editor {
        border: 1px solid var(--ck-color-base-border);
        border-radius: var(--ck-border-radius);

        /* Set vertical boundaries for the document editor. */
        max-height: 700px;

        /* This element is a flex container for easier rendering. */
        display: flex;
        flex-flow: column nowrap;
    }

    .document-editor__toolbar {
        /* Make sure the toolbar container is always above the editable. */
        z-index: 1;

        /* Create the illusion of the toolbar floating over the editable. */
        box-shadow: 0 0 5px hsla( 0,0%,0%,.2 );

        /* Use the CKEditor CSS variables to keep the UI consistent. */
        border-bottom: 1px solid var(--ck-color-toolbar-border);
    }

    /* Adjust the look of the toolbar inside the container. */
    .document-editor__toolbar .ck-toolbar {
        border: 0;
        border-radius: 0;
    }

    /* Make the editable container look like the inside of a native word processor application. */
    .document-editor__editable-container {
        padding: calc( 2 * var(--ck-spacing-large) );
        background: var(--ck-color-base-foreground);

        /* Make it possible to scroll the "page" of the edited content. */
        overflow-y: scroll;
        z-index: 0;
    }

    .document-editor__editable-container .ck-editor__editable {
        /* Set the dimensions of the "page". */
        width: 15.8cm;
        min-height: 21cm;

        /* Keep the "page" off the boundaries of the container. */
        padding: 1cm 2cm 2cm;

        border: 1px hsl( 0,0%,82.7% ) solid;
        border-radius: var(--ck-border-radius);
        background: white;

        /* The "page" should cast a slight shadow (3D illusion). */
        box-shadow: 0 0 5px hsla( 0,0%,0%,.1 );

        /* Center the "page". */
        margin: 0 auto;
    }

    .document-editor .ck-content,
    .document-editor .ck-heading-dropdown .ck-list .ck-button__label {
        font: 16px/1.6 "Helvetica Neue", Helvetica, Arial, sans-serif;
    }

    /* Adjust the headings dropdown to host some larger heading styles. */
    .document-editor .ck-heading-dropdown .ck-list .ck-button__label {
        line-height: calc( 1.7 * var(--ck-line-height-base) * var(--ck-font-size-base) );
        min-width: 6em;
    }

    /* Scale down all heading previews because they are way too big to be presented in the UI.
    Preserve the relative scale, though. */
    .document-editor .ck-heading-dropdown .ck-list .ck-button:not(.ck-heading_paragraph) .ck-button__label {
        transform: scale(0.8);
        transform-origin: left;
    }

    /* Set the styles for "Heading 1". */
    .document-editor .ck-content h2,
    .document-editor .ck-heading-dropdown .ck-heading_heading1 .ck-button__label {
        font-size: 2.18em;
        font-weight: normal;
    }

    .document-editor .ck-content h2 {
        line-height: 1.37em;
        padding-top: .342em;
        margin-bottom: .142em;
    }

    /* Set the styles for "Heading 2". */
    .document-editor .ck-content h3,
    .document-editor .ck-heading-dropdown .ck-heading_heading2 .ck-button__label {
        font-size: 20px;
        font-weight: normal;
        color: hsl( 203, 100%, 50% );
    }

    .document-editor .ck-heading-dropdown .ck-heading_heading2.ck-on .ck-button__label {
        color: var(--ck-color-list-button-on-text);
    }

    /* Set the styles for "Heading 2". */
    .document-editor .ck-content h3 {
        line-height: 1.86em;
        padding-top: .171em;
        margin-bottom: .357em;
    }

    /* Set the styles for "Heading 3". */
    .document-editor .ck-content h4,
    .document-editor .ck-heading-dropdown .ck-heading_heading3 .ck-button__label {
        font-size: 17px;
        font-weight: bold;
    }

    .document-editor .ck-content h4 {
        line-height: 1.24em;
        padding-top: .286em;
        margin-bottom: .952em;
    }

    /* Set the styles for "Paragraph". */
    .document-editor .ck-content p {
        /*font-size: 1em;
        line-height: 1.63em;
        padding-top: .5em;
        margin-bottom: 1.13em;*/
    }

    /* Make the block quoted text serif with some additional spacing. */
    .document-editor .ck-content blockquote {
        font-family: Georgia, serif;
        margin-left: calc( 2 * var(--ck-spacing-large) );
        margin-right: calc( 2 * var(--ck-spacing-large) );
    }
</style>
EOT;

    $current_nav_tab = 'manage_templates';
    require('includes/header.php');
?>
<div id="content" class="full" data-editor="DecoupledDocumentEditor" data-collaboration="false">
    <div class="post logic_settings">
        <div class="content_header">
            <div class="content_header_title">
                <div>
                    <h2>Create Template</h2>
                    <p>Create or edit template to be used in forms.</p>
                </div>
            </div>
        </div>
        <?php la_show_message(); ?>
        <div class="content_body">
            <div class="left">
                <div id="editor_loading" style="color: #FFFFFF; font-size: 120%;"> Loading Google Fonts... Please wait... </div>
                <div class="display-after-loading" style="display: none;">
                    <form id="ls_form" method="post" action="<?php echo noHTML($_SERVER['PHP_SELF']).'?id='.$template_id; ?>">
                        <div style="display:none;">
                            <input type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                            <input type="hidden" name="template_data" id="template_data" value="<?php echo $data; ?>" />
                            <input type="hidden" name="submit_form" value="1" />
                        </div>
                        <label class="description" for="template_name">Template Name</label>
                        <input type="text" id="template_name" name="template_name" class="text medium" value="<?=$name?>"/>
                        <label class="description" for="template_data">Template Data</label>
                        <div class="document-editor">
                            <div class="document-editor__toolbar"></div>
                            <div class="document-editor__editable-container">
                                <div class="document-editor__editable">
                                    <?php echo $data; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                     <button id="save_form" type="submit" class="bb_button bb_green" style="margin-top: 10px;"><img src="images/navigation/FFFFFF/24x24/Create_new_form.png"> Save Template</button>
                </div>
            </div>
            <div class="right">
                <div id="" class="ns_box_main gradient_blue">
                    <div class="ns_box_title">
                        <div class="ls_box_content">
                            <label class="description" for="ls_select_form" style="margin-top: 2px"> Select a Form</label>
                            <select class="select medium" id="ls_select_form" name="ls_select_form" autocomplete="off">
                                <option value=""></option>
                                <?php
                                foreach ($all_forms as $form_id => $form_name) {
                                    $form_name   = trim($form_name);
                                    $form_name     = substr($form_name, 0, 24);
                                    echo '<option value="' . $form_id . '">' . $form_name . '</option>';
                                }
                                ?>
                            </select>
                            <label class="description" for="ls_select_field" style="margin-top: 2px"> Select Field </label>
                            <select class="select medium" id="ls_select_field" name="ls_select_field" autocomplete="off">
                                <option value=""></option>
                            </select>
                            <label class="description" for="ls_select_field_rule" style="margin-top: 2px"> Machine Code </label>
                            <input id="machine_code_selected" name="" class="text full" value="" type="text"> <span onclick="copyCode()" class="copy-code">Copy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.post -->
<div id="processing-dialog" style="display: none;text-align: center;font-size: 150%;">
    Processing Request...<br>
    <img src="images/loading-gears.gif" style="height: 100px; width: 100px" />
</div>
<div id="dialog-success" title="Success" class="buttons" style="display: none; text-align:center;">
    <img src="images/navigation/005499/50x50/Success.png" />
    <p id="dialog-success-msg"> Success </p>
</div>
<div id="dialog-warning" title="Error" class="buttons" style="display: none; text-align:center;">
    <img src="images/navigation/ED1C2A/50x50/Warning.png" />
    <p id="dialog-warning-msg"> Error </p>
</div>
<?php
$footer_data = <<<EOT
    {$load_google_fonts}
    <script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
    <script src="js/ckeditor5-doc/build/ckeditor.js"></script>
EOT;
require('includes/footer.php');
?>
<script>
    function copyCode() {
        var copyText = document.getElementById("machine_code_selected");
        if(copyText.value == "") {
            $("#dialog-warning-msg").html("Machine code is empty.");
            $("#dialog-warning").dialog('open');
        } else {
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            $("#dialog-success-msg").html("Copied the text: " + copyText.value);
            $("#dialog-success").dialog('open');
        }
    }

    $(document).ready(function() {
        $("#processing-dialog").dialog({
            modal: true,
            autoOpen: false,
            closeOnEscape: false,
            width: 400,
            draggable: false,
            resizable: false
        });
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
                    $(this).dialog('close');
                }
            }]
        });
        $("#dialog-success").dialog({
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
                    $(this).dialog('close');
                }
            }]
        });

        var font_family = <?php echo json_encode($font_family); ?>;
        let myEditor;
        DecoupledDocumentEditor.create(document.querySelector('.document-editor__editable'), {
                fontFamily: {
                    options: font_family,
                    supportAllValues: true
                },
                fontSize: {
                    options: [
                        9,
                        11,
                        13,
                        14,
                        15,
                        16,
                        'default',
                        17,
                        18,
                        19,
                        21
                    ],
                    supportAllValues: true
                },
                toolbar: {
                    items: [
                        'heading', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
                        'alignment', 'outdent', 'indent', '|',
                        '-',
                        'todoList', 'numberedList', 'bulletedList', '|',
                        'specialCharacters', 'subscript', 'superscript', '|',
                        'insertTable', '|',
                        'horizontalLine', 'blockQuote', 'link', '|',
                        'imageUpload', 'mediaEmbed', '|',
                        'undo', 'redo'
                    ],
                    shouldNotGroupWhenFull: true
                },
                language: 'en',
                image: {
                    toolbar: [
                        'imageTextAlternative',
                        'imageStyle:full',
                        'imageStyle:side'
                    ]
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                        'tableCellProperties',
                        'tableProperties'
                    ]
                },
                licenseKey: ''
            })
            .then(editor => {
                // Set a custom container for the toolbar.
                myEditor = editor;
                document.querySelector('.document-editor__toolbar').appendChild(editor.ui.view.toolbar.element);
                document.querySelector('.ck-toolbar').classList.add('ck-reset_all');
            })
            .catch(error => {
                $("#dialog-warning-msg").html(error);
                $("#dialog-warning").dialog('open');
            });

        $('#save_form').click(function(){
            if($("#template_name").val() == "") {
                $("#dialog-warning-msg").html("Please enter a template name.");
                $("#dialog-warning").dialog('open');
            } else {
                var template_data = myEditor.getData();
                $('#template_data').val(template_data);
                $('#save_form').attr('disabled',true);
                $('#ls_form').submit();
            }
        });

        var machine_codes;    
        
        $('#ls_select_form').on('change', function() {
            $("#processing-dialog").dialog('open');
            $('#ls_select_field').find('option').remove();
            $.ajax({
                type: "POST",
                async: true,
                url: "ajax-requests.php",
                data: {
                    action: 'get_form_fields',
                    form_id: this.value
                },
                cache: false,
                global: false,
                dataType: "json",
                error: function(h, f, g) {
                    $("#dialog-warning-msg").html("Error Occured while request. Please try again later.");
                    $("#dialog-warning").dialog('open');
                },
                success: function(e) {
                    if (e.success) {
                        machine_codes = e.machine_codes;

                        $('#ls_select_field').append($("<option></option>")
                            .attr("value", '')
                            .text('Select a Field'));

                        for (const [key, fields] of Object.entries(e.field_titles)) {
                            $('#ls_select_field')
                                .append('<optgroup label="Page ' + key + '">');

                            for (const [key, value] of Object.entries(fields)) {
                                $('#ls_select_field')
                                    .append($("<option></option>")
                                            .attr("value", key)
                                            .text(value.element_title));
                            }
                        }
                    } else if (e.error) {
                        $("#dialog-warning-msg").html("Error Occured while request. Please try again later.");
                        $("#dialog-warning").dialog('open');
                    }
                },
                complete: function(e) {
                    $("#processing-dialog").dialog('close');
                }
            });

            $('#ls_select_field').on('change', function() {
                $('#machine_code_selected').val('$'+machine_codes[this.value]+'$');
            });
        });

        $(".display-after-loading").show();
        $("#editor_loading").hide();
    });
</script>