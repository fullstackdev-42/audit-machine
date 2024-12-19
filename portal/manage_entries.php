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

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$form_id = (int)la_sanitize($_GET['id']);
$form_name_for_header = "";
$query = "SELECT form_name FROM ".LA_TABLE_PREFIX."forms WHERE form_id = ?";
$sth = la_do_query($query, array($form_id) ,$dbh);
$row = la_do_fetch_result($sth);

if(!empty($row)){
	$form_name_for_header = $row['form_name'];
}
$sort_by = la_sanitize($_GET['sortby']);
$company_id = $_SESSION["la_client_entity_id"];
$entQuery = "SELECT company_name FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
$entSth = la_do_query($entQuery, array($company_id), $dbh);
$entRow = la_do_fetch_result($entSth);
$entity_name = $entRow["company_name"];
$entry_list = array();

$poam_enabled = false;
//check if POAM is enabled
$query = "SELECT `logic_poam_enable` FROM `".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
$sth = la_do_query($query, array($form_id), $dbh);
$row = la_do_fetch_result($sth);
if($row['logic_poam_enable']) {
	$poam_enabled = true;
}

$form_query = "SELECT * FROM ".LA_TABLE_PREFIX."forms WHERE `form_id`=?";
$res_form_query = la_do_query($form_query, array($form_id), $dbh);
$row_form = la_do_fetch_result($res_form_query);
if(!empty($row_form) && isset($row_form)) {
	$form_name = $row_form["form_name"];
	$table_exists = "SELECT count(*) AS counter FROM information_schema.tables WHERE table_schema = '".LA_DB_NAME."' AND table_name = '".LA_TABLE_PREFIX."form_{$form_id}'";
	$result_table_exists = la_do_query($table_exists,array(),$dbh);
	$row_table_exists = la_do_fetch_result($result_table_exists);

	if($row_table_exists['counter'] == 1) {
		//get entry id
		$query_entry_id = "SELECT DISTINCT(`entry_id`) FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `company_id` = ?";
		$sth_entry_id = la_do_query($query_entry_id, array($company_id), $dbh);
		while($row_entry = la_do_fetch_result($sth_entry_id)) {
			$entry_id = $row_entry['entry_id'];
			$date_created = "";
			$document_description = "";
			$document_templates = array();
			$poam_status = "";
			$poam_reports = array();

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
			array_push($entry_list, array("entry_id" => $entry_id, "entity_name" => $entity_name, "document_description" => $document_description, "document_templates" => $document_templates, "poam_status" => $poam_status, "poam_reports" => $poam_reports, "date_created" => $date_created));
		}
	} else {
		//create a new entry data
		header("Location: /portal/view.php?id=".$form_id."&entry_id".time());
	    exit;
	}
} else {
	die("Invalid form ID.");
}
$header_data =<<<EOT
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
require('portal-header.php');
?>
<?php la_show_message(); ?>
<style type="text/css">
	#entry_management_table td {
		text-align: center;
	}
	.action-view:hover {
	    cursor: pointer;
	}
</style>
<div class="content_body">
	<div>
		<table id="entry_management_table" class="hover stripe cell-border" style="width: 100%;">
			<thead>
				<tr>
					<th>#</th>
					<th>Entity</th>
					<th>Template Outputs</th>
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
						<tr data-form-id = "<?php echo $form_id; ?>" data-company-id = "<?php echo $company_id; ?>" data-entry-id = "<?php echo $entry['entry_id']; ?>">
							<td class="action-view"><?php echo $i; ?></td>
							<td class="action-view"><?php echo $entry["entity_name"]; ?></td>
							<?php
								$template_document_ele = $entry["document_description"];
								foreach ($entry["document_templates"] as $template) {
									$documentdownloadlink = "download_document_zip.php?id=".$template."&form_id=".$form_id."&entry_id=".$entry["entry_id"]."&company_id=".$company_id;
									$template_document_ele .= '<br><a target="_blank" href="javascript:void(0);" class="action-download-document-zip" data-documentdownloadlink="'.$documentdownloadlink.'">'.$template.'</a>';
								}
							?>
							<td class="action-view"><?php echo preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $template_document_ele); ?></td>
							<?php
								if($poam_enabled) {
									$poam_reports_ele = '';
									foreach ($entry["poam_reports"] as $template) {
										$documentdownloadlink = "download_document_zip.php?id=".$template."&form_id=".$form_id."&entry_id=".$entry["entry_id"]."&company_id=".$company_id;
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
</div>
<div id="dialog-download-document-zip" title="Download Document" class="buttons" style="display: none">
	<p style="text-align: center"><?php echo htmlspecialchars($la_settings['disclaimer_message'], ENT_QUOTES); ?></p>
</div>
<div id="processing-dialog" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<?php
$footer_data =<<<EOT
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/manage_entries.js"></script>
EOT;
require('portal-footer.php');
?>