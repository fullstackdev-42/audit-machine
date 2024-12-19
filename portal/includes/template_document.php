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

//Connect to the database
$dbh = la_connect_db();

$company_id = $_SESSION['la_client_client_id'];

if(isset($_GET['delete'])){
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."template_document_creation WHERE `company_id` = ? and `form_id` = ? and `docx_create_date` = ?";
	$param = array($company_id, $_GET['form_id'], $_GET['create_date']);
	$result = la_do_query($query,$param,$dbh);
	
	while($row_com = la_do_fetch_result($result)){
		$template_path = "template_output/";
		$docxname = $template_path.$row_com['docxname'];
		if(file_exists($docxname)){
			@unlink($docxname);
		}
	}
	
	$query = "DELETE FROM ".LA_TABLE_PREFIX."template_document_creation WHERE `company_id` = ? and `form_id` = ? and `docx_create_date` = ?";
	$param = array($company_id, $_GET['form_id'], $_GET['create_date']);
	la_do_query($query,$param,$dbh);
	
	header("location:template_document.php");
	exit();
}
require('portal-header.php');
?>

<div class="content_body">
  <ul id="la_form_list" class="la_form_list">
    <?php		
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."template_document_creation WHERE `company_id` = ? AND `isZip` = ? order by docx_create_date DESC, isZip DESC";
	$param = array($company_id, 1);
	$result = la_do_query($query,$param,$dbh);
	while($row_com = la_do_fetch_result($result)){
		// client user details
		$query_client = "select full_name from ".LA_TABLE_PREFIX."ask_client_users where client_id = ? and client_user_id = ?";
		$param_client = array($company_id, $row_com['client_id']);
		$result_client = la_do_query($query_client,$param_client,$dbh);
		$row_client = la_do_fetch_result($result_client);
	?>
    <li data-theme_id="" id="liform_<?php echo noHTML($row_com['form_id']); ?>"  class="form_active form_visible">
      <div style="height: 0px; clear: both;"></div>
      <div class="middle_form_bar">
        <div class="form_meta" style="display:table; margin:5px auto 0; color:#fff;float:left;width: 95%;text-align: center; line-height: 2;"> <a href="template_output/<?php echo noHTML($row_com['docxname']); ?>" target="_blank" <?php echo ($row_com['isZip'] == 1) ? 'style="font-weight:bold; display:block; color:#fff;"' : ''; ?>><?php echo noHTML($row_client['full_name']); ?> at <?php echo date("H:i a", noHTML($row_com['docx_create_date'])); ?> on <?php echo date("m/d/Y", noHTML($row_com['docx_create_date'])); ?> submitted the form. </a></div>
        <div style="float:right; color:#fff; font-weight:bold;width: 5%; line-height: 2;"><a href="javascript:void(0)" style="color:#fff; font-weight:bold;" class="delete-docs" data-form-id="<?php echo noHTML($row_com['form_id']); ?>" data-create-date="<?php echo noHTML($row_com['docx_create_date']); ?>">Delete</a></div>
        <div style="height: 0px; clear: both;"></div>
      </div>
      <div style="height: 0px; clear: both;"></div>
    </li>
    <?php
	}
	?>
  </ul>
</div>
<?php
require('portal-footer.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('.delete-docs').click(function(){
		var _selector = $(this);
		if(confirm("Do you really want to delete document?")){
			location.href = 'template_document.php?delete=777&form_id='+_selector.attr('data-form-id')+'&create_date='+_selector.attr('data-create-date');
		}
	});
});
</script>