<?php
/********************************************************************************
IT Audit Machine
  
Patent Pending, Copyright 2000-2016 Continuum GRC Software. This code cannot be redistributed without
permission from http://lazarusalliance.com/
 
More info at: http://lazarusalliance.com/
********************************************************************************/
require ('includes/init.php');
require ('config.php');
require ('includes/db-core.php');
require ('includes/helper-functions.php');
require ('includes/check-session.php');
require ('includes/entry-functions.php');
require ('includes/users-functions.php');
require ('includes/filter-functions.php');
$form_id = (int)la_sanitize($_GET['id']);

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
//check permission, is the user allowed to access this page?
if (empty($_SESSION['la_user_privileges']['priv_administer'])) {
    $user_perms = la_get_user_permissions($dbh, $form_id, $_SESSION['la_user_id']);
    //this page need edit_entries or view_entries permission
    if (empty($user_perms['edit_entries']) && empty($user_perms['view_entries'])) {
        $_SESSION['LA_DENIED'] = "You don't have permission to access this page.";
        $ssl_suffix = la_get_ssl_suffix();
        header("Location: restricted.php");
        exit;
    }
}
$query = "SELECT `form_name` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
$sth = la_do_query($query, array($form_id), $dbh);
$row = la_do_fetch_result($sth);
if (!empty($row)) {
    $row['form_name'] = la_trim_max_length($row['form_name'], 65);
    if (!empty($row['form_name'])) {
        $form_name = htmlspecialchars($row['form_name']);
    } else {
        $form_name = 'Untitled Form (#' . $form_id . ')';
    }
} else {
    die("Error. Unknown form ID.");
}

$poam_enabled = false;
//check if POAM is enabled
$query = "SELECT `logic_poam_enable` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
$sth = la_do_query($query, array($form_id), $dbh);
$row = la_do_fetch_result($sth);
if($row['logic_poam_enable']) {
  $poam_enabled = true;
}

//get a list of entries
$entry_list = array();
if(empty($_SESSION['is_examiner'])) {
  //if admin, get a list of all the entries
  $query_entry = "SELECT DISTINCT `company_id`, `entry_id` FROM `" . LA_TABLE_PREFIX . "form_{$form_id}` ORDER BY `company_id`";
} else {
  //if examiner, get a list of entries of the assigned entities
  $entity_array = array("0");
  $query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
  $sth_entity = la_do_query($query_entity, array($_SESSION['la_user_id']), $dbh);
  while($row_entity = la_do_fetch_result($sth_entity)) {
    array_push($entity_array, $row_entity['entity_id']);
  }
  $string_entity_ids = implode(',', $entity_array);

  $query_entry = "SELECT DISTINCT `company_id`, `entry_id` FROM `" . LA_TABLE_PREFIX . "form_{$form_id}` WHERE `company_id` IN ($string_entity_ids) ORDER BY `company_id`";
}
$sth_entry = la_do_query($query_entry, array(), $dbh);
while ($row_entry = la_do_fetch_result($sth_entry)) {
  $company_id = $row_entry['company_id'];
  $entry_id = $row_entry['entry_id'];
  $company_name = "";
  $date_created = "";
  $document_description = "";
  $document_templates = array();
  $poam_status = "";
  $poam_reports = array();
  $is_audit = false;

  //get company name
  $query_entity = "SELECT `company_name` FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id` = ?";
  $sth_entity = la_do_query($query_entity, array($company_id), $dbh);
  $row_entity = la_do_fetch_result($sth_entity);
  if(!empty($row_entity)) {
    $company_name = $row_entity['company_name'];
  } else {
    $company_name = "Administrator";
  }

  //get entry submission date
  $query = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name = ? AND company_id = ? AND entry_id = ?";
  $sth = la_do_query($query, array("date_created", $company_id, $entry_id), $dbh);
  $row = la_do_fetch_result($sth);
  if(!empty($row["data_value"]) && $row["data_value"] != "0000-00-00") {
    $date_created = la_short_relative_date($row["data_value"]);
  }

  //get generated documents including cascaded sub forms
  $formIdArr = array($form_id);
  //get cascaded sub form IDs
  $query_cascade_forms = "SELECT `element_default_value` FROM ".LA_TABLE_PREFIX."form_elements WHERE `form_id` = ? AND `element_type` = ?";
  $sth_cascade_forms = la_do_query($query_cascade_forms, array($form_id, "casecade_form"), $dbh);
  while($row_cascade_form = la_do_fetch_result($sth_cascade_forms)) {
    array_push($formIdArr, $row_cascade_form['element_default_value']);
  }
  foreach ($formIdArr as $temp_form_id) {
    //check if the document is in cron queue
    $query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ? AND status != 1 order by id DESC LIMIT 1";
    $sth_document_process = la_do_query($query_document_process, array($temp_form_id, $company_id, $entry_id), $dbh);
    $row_document_process = la_do_fetch_result($sth_document_process);
    if( $row_document_process['id'] ) {
      //latest document has not been created yet
      if( $row_document_process['status'] == 0 ) {
        if($temp_form_id == $form_id) {
          $document_description .= "Document is scheduled to be created.<br>";
        } else {
          $document_description .= "<br>Document for Cascade sub form #{$temp_form_id} is scheduled to be created.";
        }
      } else if( $row_document_process['status'] == 2 ) {
        if($temp_form_id == $form_id) {
          $document_description .= "Document is generating now. Sometimes it could take up to an hour.";
        } else {
          $document_description .= "<br>Document for Cascade sub form #{$temp_form_id} is generating now. Sometimes it could take up to an hour.";
        }
      }
    } else {
      $query_document = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND `isZip` = ? AND `isPOAM` = 0 order by `docx_create_date` DESC";
            $sth_document = la_do_query($query_document, array($temp_form_id, $company_id, $entry_id, 1), $dbh);
      $row_document = la_do_fetch_result($sth_document);
      if( $row_document ) {
        array_push($document_templates, $row_document['docxname']);
      }
    }
  }

  //get entry audit status
  $query = "SELECT `data_value` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name = ? AND company_id = ? AND entry_id = ?";
  $sth = la_do_query($query, array("audit", $company_id, $entry_id), $dbh);
  $row = la_do_fetch_result($sth);
  if(!empty($row["data_value"]) && $row["data_value"] == "1") {
    $is_audit = true;
  }
  //get POAM status and POAM reports for the entry
  if($poam_enabled) {
    //get POAM status
    $query_poam_templates = "SELECT DISTINCT o.option AS `poam_status` FROM ".LA_TABLE_PREFIX."poam_logic AS l LEFT JOIN ".LA_TABLE_PREFIX."element_options AS o ON (l.form_id = o.form_id AND l.element_name = CONCAT('element_', o.element_id) AND l.rule_keyword = o.option) LEFT JOIN ".LA_TABLE_PREFIX."form_{$form_id} AS f ON (l.element_name = f.field_name AND o.option_id = f.data_value) LEFT JOIN ".LA_TABLE_PREFIX."form_template AS t ON (l.target_template_id = t.template_id) WHERE l.form_id = ? AND f.company_id = ? AND f.entry_id = ?";
    $sth_poam_templates = la_do_query($query_poam_templates, array($form_id, $company_id, $entry_id), $dbh);
    while ($row_poam_template = la_do_fetch_result($sth_poam_templates)) {
      $poam_status = $row_poam_template['poam_status'];
    }

    //get POAM reports
    $query_document = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND `isZip` = ? AND `isPOAM` = 1 order by `docx_create_date` DESC";
          $sth_document = la_do_query($query_document, array($temp_form_id, $company_id, $entry_id, 1), $dbh);
    $row_document = la_do_fetch_result($sth_document);
    if( $row_document ) {
      array_push($poam_reports, $row_document['docxname']);
    }
  }
  array_push($entry_list, array("company_id" => $company_id, "entry_id" => $entry_id, "company_name" => $company_name, "document_description" => $document_description, "document_templates" => $document_templates, "poam_status" => $poam_status, "poam_reports" => $poam_reports, "date_created" => $date_created, "is_audit" => $is_audit));
}

$header_data = <<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
$current_nav_tab = 'manage_forms';
require ('includes/header.php');
?>
<style>
  #ve_table_info td.cus-btn{
    background-color:#3B699F; 
    color:#ffffff; 
    font-size:12px; 
    text-align:center; 
    vertical-align: middle;
    padding: 2px;
  }
  .deny-entry {
  width: 48px;
    text-align: center;
    /*margin-top: 5px;*/
  }
  .dot {
    height: 8px;
      width: 8px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
  }
  .dot-approved {
    background-color: #63a62f;
  }
  .dot-denied {
    background-color: #e6433d;
  }
  #entries_table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    width: 100%;
    cursor: pointer;
  }
  #entries_table .template_document {
    vertical-align: top;
  }
  table.dataTable tbody td.select-checkbox:before {
    position: relative!important;
    top: 0!important;
    margin-top: 0px!important;
  }
  table.dataTable tbody tr.odd {
    background-color: #f3f7fb !important;
  }
</style>
<div id="content" class="full">
  <div class="post manage_entries">
    <div class="content_header">
      <div class="content_header_title">
        <div id="me_form_title" style="max-width: 80%">
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>" . $form_name . '</a>'; ?> <img src="images/icons/resultset_next.gif" /> Entries</h2>
          <p>Edit and manage your form entries</p>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <div class="content_body">
      <?php la_show_message(); ?>

      <div id="data-form-id" data-form-id="<?php echo $form_id; ?>"></div>
      <?php if(empty($_SESSION['is_examiner'])) {
        ?>
        <div id="server_entries_container" style="margin-bottom: 20px;">
            <table id="server_entries_table" class="hover stripe cell-border data-table" style="width: 100%;">
              <thead>
                <tr>
                  <th></th>
                  <th>#</th>
                  <th>Backup File</th>
                  <th>Date Created</div>
                </tr>
              </thead>
              <tbody>
                <?php
                $HTMLElement     = "";
                $numberOfResults = 0;
                $queryFormTable  = "SHOW TABLES LIKE '".LA_TABLE_PREFIX."form_{$form_id}_saved_entries'";
                $resultFormTable = la_do_query($queryFormTable, array(), $dbh);
                $rowFormTable    = la_do_fetch_result($resultFormTable);
                  if($rowFormTable) {
                  $query_server_entry = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id}_saved_entries ORDER BY id DESC";
                  $sth_server_entry = la_do_query($query_server_entry, array(), $dbh);
                  while($row_server_entry = la_do_fetch_result($sth_server_entry)){
                    if(file_exists($row_server_entry["pathtofile"])){
                      $numberOfResults     = $numberOfResults + 1;
                      $dateCreated         = explode("entries_backup_", $row_server_entry['pathtofile'])[1];
                      $dateCreated         = explode("_", $dateCreated)[1];
                      $dateCreated         = explode(".zip", $dateCreated)[0];
                      $dateCreated         = date("m-d-Y",$dateCreated);
                      $fileNameFromPath    = explode("entries_backup_", $row_server_entry['pathtofile'])[1];
                      $fileNameFromPath    = explode("_", $fileNameFromPath)[0];
                      $formattedPathToFile = explode("auditprotocol", $row_server_entry['pathtofile'])[1];
                      echo "<tr data-db-id='".$row_server_entry['id']."' data-path-to-file='".$formattedPathToFile."'><td>
                          </td>
                          <td>".$numberOfResults."</td>
                          <td>".$fileNameFromPath."<br></td>
                          <td>".$dateCreated."<br></td>
                      </tr>";
                    }
                  }
                }
                ?>
              </tbody>
            </table>
          </div>
      <?php } ?>
      <div class="entry-list">
        <table id="entries_table" class="hover stripe cell-border data-table" style="width: 100%;">
          <thead>
            <tr>
              <th></th>
              <th>#</th>
              <th>Entity</th>
              <th>Template Outputs</th>
              <th>Audit Status</th>
              <?php
                if($poam_enabled) {
              ?>
              <th>POAM Status</th>
              <th>POAM Reports</th>
              <?php
                }
              ?>
              <th>Date Created</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $i = 0;
              foreach ($entry_list as $entry) {
                $i++;
            ?>
                <tr data-form-id = "<?php echo $form_id; ?>" data-company-id = "<?php echo $entry['company_id']; ?>" data-entry-id = "<?php echo $entry['entry_id']; ?>">
                  <td></td>
                  <td class="action-view"><?php echo $i; ?></td>
                  <td class="action-view"><?php echo $entry["company_name"]; ?></td>             
                  <?php
                    $template_document_ele = $entry["document_description"];
                    foreach ($entry["document_templates"] as $template) {
                      $documentdownloadlink = "download_document_zip.php?id=".$template."&form_id=".$form_id."&entry_id=".$entry["entry_id"]."&company_id=".$entry['company_id'];
                      $template_document_ele .= '<br><a target="_blank" href="javascript:void(0);" class="action-download-document-zip" data-documentdownloadlink="'.$documentdownloadlink.'">'.$template.'</a>';
                    }
                  ?>
                  <td class="action-view"><?php echo preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $template_document_ele); ?></td>
                  <td class="me_action">
                    <label class="switch">
                      <input type="checkbox" <?php if($entry['is_audit']) echo "checked"; ?>>
                      <span class="slider round"></span>
                    </label>
                  </td>
                  <?php
                    if($poam_enabled) {
                      $poam_reports_ele = '';
                      foreach ($entry["poam_reports"] as $template) {
                        $documentdownloadlink = "download_document_zip.php?id=".$template."&form_id=".$form_id."&entry_id=".$entry["entry_id"]."&company_id=".$entry['company_id'];
                        $poam_reports_ele .= '<br><a target="_blank" href="javascript:void(0);" class="action-download-document-zip" data-documentdownloadlink="'.$documentdownloadlink.'">'.$template.'</a>';
                      }
                  ?>
                  <td class="action-view"><?php echo $entry["poam_status"]; ?></td>
                  <td class="action-view"><?php echo preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $poam_reports_ele); ?></td>
                  <?php
                    }
                  ?>
                  <td class="action-view"><?php echo $entry["date_created"]; ?></td>
                </tr>
            <?php
              }
            ?>
          </tbody>
        </table>
      </div>
      <div class="admin-list">
        <table id="ve_table_info" width="100%" cellspacing="0" cellpadding="0" border="0">
          <thead>
          <tr>
            <td style="color:#3B699F; font-weight:bold; width:70%;">Administrative users with access to this form:</td>
            <td class="cus-btn" style="border-right: 1px solid #ffffff;">Edit Form</td>
            <td class="cus-btn" style="border-right: 1px solid #ffffff;">Edit Entries</td>
            <td class="cus-btn">View Entries</td>
          </tr>
          </thead>
          <tbody>
          <?php
            $query = "select `user_id`, `user_fullname`, `priv_administer` from `" . LA_TABLE_PREFIX . "users` where `status` <> '0' AND `is_examiner` = '0'";
            $result = la_do_query($query, array(), $dbh);
            $i = 0;
            while ($row = la_do_fetch_result($result)) {
                if ($i % 2) {
                    $tr_class = "";
                } else {
                    $tr_class = "background-color: #f3f7fb !important;";
                }
                $img_edit = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                $img_entries = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                $img_view = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                if (!$row['priv_administer']) {
                    $query_per = "SELECT `edit_form`, `edit_entries`, `view_entries` FROM `" . LA_TABLE_PREFIX . "permissions` where `form_id` = ? and `user_id` = ?";
                    $result_per = la_do_query($query_per, array($form_id, $row['user_id']), $dbh);
                    $row_per = la_do_fetch_result($result_per);
                    if ($row_per) {
                        if (!$row_per['edit_form']) {
                            $img_edit = '';
                        }
                        if (!$row_per['edit_entries']) {
                            $img_entries = '';
                        }
                        if (!$row_per['view_entries']) {
                            $img_view = '';
                        }
                    }
                }
                $i++;
              ?>
              <tr style="<?php echo $tr_class; ?>">
                <td style="vertical-align: middle;"><img style="width:16px;" src="images/navigation/005499/16x16/User.png" align="absmiddle">&nbsp;&nbsp;<?php echo $row['user_fullname']; ?></td>
                <td style="vertical-align: middle; text-align:center;"><?php echo $img_edit; ?></td>
                <td style="vertical-align: middle; text-align:center;"><?php echo $img_entries; ?></td>
                <td style="vertical-align: middle; text-align:center;"><?php echo $img_view; ?></td>
              </tr>
            <?php
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="examiner-list">
        <table id="ve_table_info" width="100%" cellspacing="0" cellpadding="0" border="0">
          <thead>
          <tr>
            <td style="color:#3B699F; font-weight:bold; width:70%;">Examiner users with access to this form:</td>
            <td class="cus-btn" style="border-right: 1px solid #ffffff;">Edit Entries</td>
            <td class="cus-btn">View Entries</td>
          </tr>
          </thead>
          <tbody>
          <?php
            $query = "select `user_id`, `user_fullname` from `" . LA_TABLE_PREFIX . "users` where `status` <> '0' AND `is_examiner` = '1'";
            $result = la_do_query($query, array(), $dbh);
            $i = 0;
            while ($row = la_do_fetch_result($result)) {
                if ($i % 2) {
                    $tr_class = "";
                } else {
                    $tr_class = "background-color: #f3f7fb !important;";
                }
                $img_entries = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                $img_view = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                $entity_array = array("0");
                $query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
                $sth_entity = la_do_query($query_entity, array($row['user_id']), $dbh);
                while($row_entity = la_do_fetch_result($sth_entity)) {
                  array_push($entity_array, $row_entity['entity_id']);
                }
                $string_entity_ids = implode(',', $entity_array);
                $query_form = "SELECT COUNT(*) total_row FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `entity_id` IN ($string_entity_ids) AND `form_id` = ?";
                $sth_form = la_do_query($query_form, array($form_id), $dbh);
                $row_form = la_do_fetch_result($sth_form);
                if (empty($row_form['total_row'])) {
                  $img_entries = '';
                  $img_view = '';
                }
                $i++;
              ?>
              <tr style="<?php echo $tr_class; ?>">
              <td style="vertical-align: middle;"><img style="width:16px;" src="images/navigation/005499/16x16/User.png" align="absmiddle">&nbsp;&nbsp;<?php echo $row['user_fullname']; ?></td>
              <td style="vertical-align: middle; text-align:center;"><?php echo $img_entries; ?></td>
              <td style="vertical-align: middle; text-align:center;"><?php echo $img_view; ?></td>
            </tr>
            <?php
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="portal-list">
        <table id="ve_table_info" width="100%" cellspacing="0" cellpadding="0" border="0">
          <thead>
          <tr>
            <td style="color:#3B699F; font-weight:bold; width:70%;">Portal users with access to this form:</td>
            <td class="cus-btn" style="border-right: 1px solid #ffffff;">Edit Entries</td>
            <td class="cus-btn">View Entries</td>
          </tr>
          </thead>
          <tbody>
          <?php
            $query = "select DISTINCT `client_id`, `company_name`, '1' `subscribed` from `ap_ask_clients` WHERE `client_id` IN (SELECT `entity_id` FROM `" . LA_TABLE_PREFIX . "entity_form_relation` WHERE `form_id` = ?) OR '0' IN (SELECT `entity_id` FROM `" . LA_TABLE_PREFIX . "entity_form_relation` WHERE `form_id` = ?)";
            $result = la_do_query($query, array($form_id, $form_id), $dbh);
            $i = 0;
            while ($row = la_do_fetch_result($result)) {
                if ($i % 2) {
                    $tr_class = "";
                } else {
                    $tr_class = "background-color: #f3f7fb !important;";
                }
                $img_entries = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                $img_view = '<img style="width:16px;" src="images/icons/59_blue_16.png" align="absmiddle">';
                if (!$row['subscribed']) {
                    $img_entries = '';
                    $img_view = '';
                }
                $i++;
              ?>
              <tr style="<?php echo $tr_class; ?>">
              <td style="vertical-align: middle;"><img style="width:16px;" src="images/navigation/005499/16x16/User.png" align="absmiddle">&nbsp;&nbsp;<?php echo $row['company_name']; ?></td>
              <td style="vertical-align: middle; text-align:center;"><?php echo $img_entries; ?></td>
              <td style="vertical-align: middle; text-align:center;"><?php echo $img_view; ?></td>
            </tr>
            <?php
            }
            ?>
          </tbody>
        </table>
      </div>
      <div style="width:50%;">
        <table id="ve_table_info" width="100%" cellspacing="0" cellpadding="0" border="0">
          <thead>
          <tr>
            <td style="color:#3B699F; font-weight:normal;">Form Info:</td>
          </tr>
          </thead>
          <tbody>
          <?php
          $query_created = "SELECT `t2`.`user_fullname`, `t1`.`action_datetime` FROM `" . LA_TABLE_PREFIX . "audit_log` `t1` LEFT JOIN `" . LA_TABLE_PREFIX . "users` `t2` ON (`t1`.`user_id` = `t2`.`user_id`) WHERE `action_type_id` = 1 AND `form_id` = ?";
          $result_created = la_do_query($query_created, array($form_id), $dbh);
          $row_created = la_do_fetch_result($result_created);
          $created_date = $row_created ? date("M d, Y", $row_created['action_datetime']) : "";
          $created_by = $row_created ? $row_created['user_fullname'] : "";
          $total_entries = "SELECT COUNT(DISTINCT `company_id`, `entry_id`) `total_entries` FROM `" . LA_TABLE_PREFIX . "form_{$form_id}`";
          $result_entries = la_do_query($total_entries, array(), $dbh);
          $row_entries = la_do_fetch_result($result_entries);
          $query_completed = "SELECT COUNT(DISTINCT `company_id`, `entry_id`) `completed` FROM `" . LA_TABLE_PREFIX . "form_{$form_id}` WHERE `field_name` = 'status' AND `data_value` = 1";
          $result_completed = la_do_query($query_completed, array(), $dbh);
          $row_completed = la_do_fetch_result($result_completed);
          $completed = $row_completed ? $row_completed['completed'] : "0";
          $incompleted = $row_entries ? $row_entries['total_entries'] - $row_completed['completed'] : "0";
          $deleted_entries = "0";
          $query_last = "SELECT `data_value` FROM `" . LA_TABLE_PREFIX . "form_{$form_id}` WHERE `field_name` = 'date_created' GROUP BY `company_id`, `entry_id` ORDER BY `data_value` DESC LIMIT 1";
          $result_last = la_do_query($query_last, array(), $dbh);
          $row_last = la_do_fetch_result($result_last);
          $last_entry = "";
          if ($row_last) {
              $date1 = new DateTime(date("Y-m-dTH:i:s", strtotime($row_last['data_value'])));
              $date2 = new DateTime();
              $difference = $date2->diff($date1);
              $last_entry = $difference->format('%a Day and %h hours');
          }
          ?>
            <tr style="background-color: #f3f7fb !important;">
            <td style="vertical-align: middle;">Created Date: <strong><?php echo $created_date; ?></strong></td>
          </tr>
          <tr>
            <td style="vertical-align: middle;">Created By: <span style="color:#3B699F; font-weight:bold;"><?php echo $created_by; ?></span></td>
          </tr>
          <tr style="background-color: #f3f7fb !important;">
            <td style="vertical-align: middle;">Total Completed Entries: <strong><?php echo $completed; ?></strong></td>
          </tr>
          <tr>
            <td style="vertical-align: middle;">Total Incomplete Entries: <strong><?php echo $incompleted; ?></strong></td>
          </tr>
          <!--<tr style="background-color: #f3f7fb !important;">
            <td style="vertical-align: middle;">Total Deleted Entries: <strong><?php echo $deleted_entries; ?></strong></td>
          </tr>-->
          <tr>
            <td style="vertical-align: middle;">Last Entry: <strong><?php echo $last_entry; ?></strong></td>
          </tr>
          </tbody>
        </table>
      </div>
    <!-- ************ **-->
    </div>
    <!-- /end of content_body --> 
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<div id="dialog-warning" title="Error Title" class="buttons" style="display: none"> <img src="images/navigation/ED1C2A/50x50/Warning.png">
  <p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-export-entries" title="Export Entry Data" class="buttons" style="display: none; text-align: center;">
  <img src="images/navigation/005499/50x50/Notice.png">
  <p id="dialog-export-msg" style="margin: 20px;"></p>
  <div style="text-align: left; padding: 0px 135px;">
    <input type="radio" id="export-radio" name="export-entry" checked>
    <label for="export-radio">Export entry data without saving to the server</label><br>
    <input type="radio" id="save-export-radio" name="export-entry">
    <label for="save-export-radio"> Save entry data to the server and export</label><br>
  </div>
</div>
<div id="dialog-import-entries" title="Select File Type" class="buttons" style="display: none">
  <ul>
    <li>Select CSV File:
      <input type="button" id="upload-files" name="fileupload" value="Select Files" style="background-color:#ccc; border-radius:0; padding:2px 20px; border:0; color:#666; cursor:pointer;" />
    </li>
  </ul>
  <form method="post" enctype="multipart/form-data" id="upload-form">
    <div style="display:none;">
      <input type="hidden" id="post-csrf-token" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
    </div>
    <input name="ImageFile[]" id="image-file" type="file" style="display:none;" accept=".zip" multiple />
    <input name="ImageFolder" id="image-file-folder" type="hidden" value="" />
    <input name="ImageFolderFormId" id="image-folder-form-id" type="hidden" value="<?php echo noHTML($form_id); ?>" />
  </form>
</div>
<div id="dialog-confirm-entry-delete" title="Are you sure you want to delete selected entries?" class="buttons" style="display: none">
  <img src="images/navigation/ED1C2A/50x50/Warning.png">
  <p id="dialog-confirm-entry-delete-msg"> This action cannot be undone.<br/>
    <strong id="dialog-confirm-entry-delete-info">Data and files associated with your selected entries will be deleted.</strong><br/>
    <br/>
  </p>
</div>
<div id="dialog-download-document-zip" title="Download Document" class="buttons" style="display: none">
  <p style="text-align: center"><?php echo htmlspecialchars($la_settings['disclaimer_message'], ENT_QUOTES); ?></p>
</div>
<div id="dialog-form-approval-action" title="Are you sure you want to do it?" class="buttons" style="display: none; text-align: center ">
  <img src="images/navigation/005499/50x50/Notice.png">
  <p id="dialog-form-approval-action-msg"> This action cannot be undone.<br/>
  <input type="hidden" name="dialog-form-approval-cId">
  <input type="hidden" name="dialog-form-approval-form-id">
  <input type="hidden" name="dialog-form-approval-approval-status">
  <p style="margin-top: 15px">Add Note(optional)</p>
  <textarea name="approval-action-note" style="width: 366px;height: 102px;border: 1px solid #ced4da;border-radius: .25rem;"></textarea>
    <!-- <strong id="dialog-form-approval-action-info">This form allows only Single Type Approvals.</strong><br/> -->
    <br/>
  </p>
</div>
<div id="processing-dialog" style="display: none;text-align: center;font-size: 150%;">
  Processing Request...<br>
  <img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>

<?php
$footer_data = <<<EOT
<script type="text/javascript">
  $(function(){
    {$jquery_data_code}   
    });
  </script>
  <script type="text/javascript" src="js/ajaxupload/jquery.form.js"></script>
  <script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
  <script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
  <script type="text/javascript" src="js/manage_entries.js"></script>
EOT;
require ('includes/footer.php');
?>

