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

//Connect to the database
$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$company_id = $_SESSION['la_client_entity_id'];
$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);
$formsId = getSubscribeFormsCompanyId($dbh, $userEntities);
$inQuery = "";
//echo "<pre>";print_r($userEntities);echo "</pre>";
//echo "<pre>";print_r($formsId);echo "</pre>";
if(count($userEntities)){
	$inQuery = implode(',', array_fill(0, count($userEntities), '?'));
}

if(isset($_GET['delete'])){
	$query = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? and `docx_create_date` = ?";
	$param = array($_GET['form_id'], $_GET['create_date']);
	$result = la_do_query($query,$param,$dbh);

	while($row_com = la_do_fetch_result($result)){
		$template_path = "template_output/";
		$docxname = $template_path.$row_com['docxname'];
		if(file_exists($docxname)){
			@unlink($docxname);
		}
	}

	$query = "DELETE FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE`form_id` = ? and `docx_create_date` = ?";
	la_do_query($query,$param,$dbh);

	header("location:template_document.php");
	exit();
}
?>
<style type="text/css">
	body .middle_form_bar {
		padding: 10px;
		display: block;
	}
	body .middle_form_bar .form_meta {
	    color: #fff;
		width: 85%;
		height: auto !important;
		float: none !important;
		display: inline-block;
		float: left;
		height: auto;
	}
	body .middle_form_bar .delete-docs-div {
		color: #fff;
	    font-weight: bold;
	    display: inline-block;
	    text-align: center;
	    float: right;
	}
	.document_preview {
		background: #F3F7FB;
		color: #000 !important;
	}
	.document_preview table {
		width: 100%;
	}
	td.preview-report {
		text-align: right !important;
    	padding-right: 42px !important;
	}
</style>
<div class="content_body">
  <ul id="la_form_list" class="la_form_list">
    <?php
	if(count($formsId['formIds'])){
		//$query = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `company_id` IN ({$inQuery}) AND `isZip` = ? ORDER BY `docx_create_date` DESC, `isZip` DESC";
		//$result = la_do_query($query, array_merge($userEntities, array(1)), $dbh);

		$query = "SELECT *, (select `form_name` from `".LA_TABLE_PREFIX."forms` WHERE `form_id` = `".LA_TABLE_PREFIX."template_document_creation`.`form_id`) `form_name` FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `company_id` = ? AND `isZip` = ? ORDER BY `docx_create_date` DESC, `isZip` DESC";
		$result = la_do_query($query, array($company_id, 1), $dbh);
		
		// client user details
		$query_client = "select full_name from ".LA_TABLE_PREFIX."ask_client_users where client_user_id = ?";
		$result_client = la_do_query($query_client, array($_SESSION['la_client_user_id']), $dbh);
		$row_client = la_do_fetch_result($result_client);
		
		while($row_com = la_do_fetch_result($result)){
			// print_r($row_com);
			$form_id = $row_com['form_id'];
			$document_count = 0;
			$document_list = [];
			if( !empty($row_com['added_files']) ) {
				$added_files = explode(',', $row_com['added_files']);
				
				foreach ($added_files as $docxname) {
			
					$target_file 	= $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
					$filename_ext   = end(explode(".", $docxname));
					$q_string = "file_path={$target_file}&file_name={$docxname}&form_id={$form_id}&document_preview=1";
					
					$document_list[$document_count]['ext'] = $filename_ext;
					$document_list[$document_count]['q_string'] = $q_string;
					$document_list[$document_count]['file_name'] = $docxname;
					$document_count++;
				}
			}
			
	?>
    <li data-theme_id="form_<?php echo noHTML($row_com['form_id']); ?>" style="display: block!important;">
      <div class="middle_form_bar">
        <div class="form_meta">
        	<p><strong>Download Report -</strong> <a title="<?php echo noHTML($row_com['docxname']); ?>" class="action-download-document-zip" href="javascript:void(0);" data-documentdownloadlink="<?php echo 'download_document_zip.php?docx_name='.$row_com['docxname'].'&form_id='.$row_com['form_id'].'&company_id='.$row_com['company_id']; ?>" target="_blank" <?php echo ($row_com['isZip'] == 1) ? 'style="font-weight:bold; color:#fff; margin:5px;"' : ''; ?>><?php echo noHTML($row_com['form_name']); ?> - <?php echo noHTML($row_client['full_name']); ?> at <?php echo date("H:i a", noHTML($row_com['docx_create_date'])); ?> on <?php echo date("m/d/Y", noHTML($row_com['docx_create_date'])); ?> submitted the form.
        	</a></p>
        	
        </div>
        <div class="delete-docs-div"><a href="javascript:void(0)" style="color:#fff; font-weight:bold;" class="delete-docs" data-form-id="<?php echo noHTML($row_com['form_id']); ?>" data-create-date="<?php echo noHTML($row_com['docx_create_date']); ?>">Delete</a></div>
      </div>
      <div class="document_preview">
      	<table>
      		<?php
	        	if( count($document_list) > 0 ) {
					echo '<tr><td width="35%" class="preview-report"><strong>Preview Report(s)</strong></td><td>';
						
					foreach ($document_list as $document_view_data) {
						echo '<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$document_view_data['ext'].'" data-src="'.base64_encode($document_view_data['q_string']).'">'.$document_view_data['file_name'].'</a><br/>';
					}
					echo '</td></tr>';
				}
			?>
      	</table>
      </div>
    </li>
    <?php
    		
		}
	}else{
	?>
	<li data-theme_id="" id="liform_<?php echo noHTML($row_com['form_id']); ?>"  class="form_active form_visible">
      <div style="height: 0px; clear: both;"></div>
	  <div class="middle_form_bar">
		<div class="form_meta" style="display:table; margin:5px auto 0; color:#fff; float:left; width: 95%; text-align: center; line-height: 2;"> <a href="javascript:void(0)" target="_blank">No documents available</a></div>
	    <div style="float:right; color:#fff; font-weight:bold;width: 5%; line-height: 2;">&nbsp;</div>
        <div style="height: 0px; clear: both;"></div>
	  </div>
	</li>
	<?php
	}
	?>
  </ul>
</div>
<div id="dialog-download-document-zip" title="Download Document" class="buttons" style="display: none">
	<p style="text-align: center"><?php echo htmlspecialchars($la_settings['disclaimer_message'],ENT_QUOTES); ?></p>
</div>
<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">
		<?php if( isset($la_settings['file_viewer_download_option']) && ($la_settings['file_viewer_download_option'] == 1) ) { ?>
			<div style="text-align: right;margin-bottom: 10px;">
				<a href="#" id="file_viewer_download_button" class="bb_button bb_small bb_green" download> 
		          <img src="images/navigation/FFFFFF/24x24/Save.png"> Download
		      	</a>
		    </div>
		<?php } ?>
	
    <div id="document-preview-content" style="height: 440px;">
		<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
	</div>
</div>
<div id="document-processing-dialog" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<style type="text/css">
.ui-dialog .ui-dialog-content {
    background-color: #fff;
}
</style>
<?php
global $include_code_after_scripts;
$include_code_after_scripts = <<<EOT
<script type="text/javascript">
	$(document).ready(function(){
		//dialog box to download document disclaimer
		$("#dialog-download-document-zip").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 550,
			draggable: false,
			resizable: false,
			buttons: [{
					text: 'I accept',
					id: 'btn-download-document-zip',
					'class': 'bb_button bb_small bb_green',
					click: function() {
						// var documentdownloadlink = $("#action-download-document-zip").data('documentdownloadlink');
						var documentdownloadlink = $("#btn-download-document-zip").data('documentdownloadlink');
						window.location.href = documentdownloadlink;
						$(this).dialog('close');
					}
				},
				{
					text: 'Cancel',
					id: 'btn-download-document-zip-cancel',
					'class': 'btn_secondary_action',
					click: function() {
						$(this).dialog('close');
					}
				}]

		});
	});

	//open the deletion dialog when the download document link clicked
	$(".action-download-document-zip").click(function(){
		$("#btn-download-document-zip").data('documentdownloadlink', $(this).attr('data-documentdownloadlink'));
		$("#dialog-download-document-zip").dialog('open');
		return false;
	});

	$('.delete-docs').click(function(){
		var _selector = $(this);
		if(confirm("Do you really want to delete document?")){
			location.href = 'template_document.php?delete=777&form_id='+_selector.attr('data-form-id')+'&create_date='+_selector.attr('data-create-date');
		}
	});
</script>
<script type="text/javascript" src="view.js"></script>
EOT;


require('portal-footer.php');
?>
