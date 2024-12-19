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
	
	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();						
		header("Location: restricted.php");
		exit;
	}

	$query = "SELECT form_name, form_id, form_template_wysiwyg_id FROM ".LA_TABLE_PREFIX."forms WHERE form_enable_template_wysiwyg = 1 and form_active = 1";
	$sth = la_do_query($query, array(), $dbh);
	$form_details = [];
	while($row = la_do_fetch_result($sth)){
		$form_details[$row['form_template_wysiwyg_id']][] = [
			'form_name' => $row['form_name'],
			'form_id' => $row['form_id']
		];
	}

$header_data = <<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="../itam-shared/Plugins/DataTable/datatables.min.css" rel="stylesheet" />
<link type="text/css" href="css/main.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
EOT;
	
	$current_nav_tab = 'manage_templates';
	require('includes/header.php');	
?>
<style type="text/css">
	#tabs {
		min-height: inherit;
	}
	ul.ui-tabs-nav {
		background: #0085CC;
	    padding: 8px!important;
	    margin-bottom: 25px!important;
	}
	li.custom-tabs-tab {
		background: #33BF8C!important;
    	border: none!important;
    	border-bottom-right-radius: 3px;
    	border-bottom-left-radius: 3px;
    	color: white!important;
    	padding: 0px!important;
	}
	li.ui-tabs-active {
		border: none!important;
	    background: #505356!important;
	}
	.ui-widget {
		font-family: "glober_regularregular", "Lucida Grande", Tahoma, Arial, sans-serif;
	}
	div.dataTables_wrapper div.dataTables_filter {
	  	float: left;
	  	text-align: left;
	  	margin-top: 6px;
	}
	div.custom-buttons {
		float: right;
  		text-align: right;
	}
	div.add-entity-btn {
		float: right;
  		text-align: right;
	}
	div.add-user-btn {
		float: right;
  		text-align: right;
	}
	td {
		text-align: center;
		vertical-align: middle;
	}
	.action-view:hover {
		cursor: pointer;
	}
	#import_template_btn {
		margin-right: 20px;
	}
	.import-template-div {
		display: none;
	}
</style>
<div id="content" class="full">
	<div class="post manage_users">
		<div class="content_header">
			<div class="content_header_title">
				<div>
				  	<h2>Template Management</h2>
				  	<p>Create, edit and manage templates.<span style="margin-left: 20px;"><img id="loader-img" src="images/loader_small_grey.gif"></span></p>
				</div>
			</div>
		</div>
		<?php la_show_message(); ?>
		<div class="content_body">
			<div class="import-template-div">
				<input name="ImageFile[]" id="import-template-button" type="file" accept=".json" style="display:none;" />
			</div>
			<table id="template_table" class="hover stripe cell-border" style="width: 100%;">
				<thead>
					<tr>
						<th>#</th>
						<th>Template Name</th>
						<th>Forms Used On</th>
						<th>Created At</th>
						<th>Updated At</th>
						<th>Edit</th>
						<th>Export</th>
						<th>Delete</th>
					</tr>
				</thead>
				<tbody>
				<?php
					$query = "SELECT * FROM ".LA_TABLE_PREFIX."form_templates";
					$sth = la_do_query($query, array(), $dbh);
					$i = 1;
					while($row = la_do_fetch_result($sth)){
				?>
					<tr template-id="<?php echo $row['id']; ?>">
						<td class="action-view"><?=$i?></td>
						<td class="action-view"><b><?=$row["name"]; ?></b></td>
						<td>
							<?php
								if( array_key_exists( $row['id'], $form_details ) ) {
									$forms_list = $form_details[$row['id']];
									if( count($forms_list) > 0 ) {
										echo "<ul>";
										foreach ($forms_list as $key => $value) {
											echo "<li><a href=\"/auditprotocol/manage_forms.php?id={$value['form_id']}&hl=1\">{$value['form_name']}</a></li>";
											
										}
										echo "</ul>";
									}
								}
							?>
						</td>
						<td class="action-view">
							<?php
								$created_at = strtotime($row['created_at']);
								echo date("Y/m/d g:i a", $created_at);
							?>
						</td>
						<td class="action-view">
							<?php
								if( $row["updated_at"] ) {
									$updated_at = strtotime($row['updated_at']);
									echo date("Y/m/d g:i a", $updated_at);
								}
							?>
						</td>
						<td>
							<a href="/auditprotocol/create_template.php?id=<?=$row['id']?>">
								<img src="images/navigation/ED1C2A/16x16/Edit.png" alt="Edit" title="Edit">
							</a>
						</td>
						<td>
							<a class="action-export" title="Export" href="#">
								<img src="images/navigation/ED1C2A/16x16/Export.png">
							</a>
						</td>
						<td>
							<a class="action-delete" title="Delete" href="#">
								<img src="images/navigation/ED1C2A/16x16/Delete.png">
							</a>
						</td>
					</tr>
				<?php
					$i++;
					}
				?>
				</tbody>
			</table>
		</div>
	<!-- /end of content_body --> 
	</div>
	<!-- /.post --> 
</div>
<!-- /#content -->
<div id="dialog-success" title="Success Title" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/005499/50x50/success.png" />
	<p id="dialog-success-msg"> Success </p>
</div>
<div id="dialog-warning" title="Error Title" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-confirm-template-delete" title="Are you sure you want to delete this template?" class="buttons" style="display: none; text-align:center;"><img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<input type="hidden" id="template_delete_id">
	<p id="dialog-confirm-template-delete-msg"> This action cannot be undone.<br/>
		<strong id="dialog-confirm-template-delete-info">The template will be deleted permanently.</strong><br/><br/>
	</p>
</div>

<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../itam-shared/Plugins/DataTable/datatables.min.js"></script>
<script type="text/javascript" src="js/manage_users.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var admin_table = $("#template_table").DataTable({
			dom: 'rf<"custom-buttons">tip',
			pageLength: 20,
			sPaginationType: "numbers",
			order: [[0, 'asc']]
		});

		$("div.custom-buttons").html(
			'<button id="import_template_btn" class="bb_button bb_green"> Import Template</button><a href="/auditprotocol/create_template.php"><button style="float: right;" class="bb_button bb_green"> Create New Template</button></a>'
		);

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

		$("#dialog-confirm-template-delete").dialog({
			modal: true,
			autoOpen: false,
			closeOnEscape: false,
			width: 550,
			draggable: false,
			resizable: false,
			open: function(){
				$("#btn-confirm-template-delete-ok").blur();
			},
			buttons: [{
				text: 'Yes. Delete selected template',
				id: 'btn-confirm-template-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					//disable the delete button while processing
					$("#btn-confirm-template-delete-ok").prop("disabled",true);
					
					//display loader image
					$("#btn-confirm-template-delete-cancel").hide();
					$("#btn-confirm-template-delete-ok").text('Deleting...');
					$("#btn-confirm-template-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					var template_delete_id = $("#dialog-confirm-template-delete #template_delete_id").val();
						console.log(template_delete_id);

					//do the ajax call to delete template
					$.ajax({
						type: "POST",
						async: true,
						url: "ajax-requests.php",
						data:
							{
								action: 'delete_wysiwyg_template',
								template_id: template_delete_id
							},
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#btn-confirm-template-delete-ok").prop("disabled", false);
							$("#btn-confirm-template-delete-cancel").show();
							$("#btn-confirm-template-delete-ok").text('Yes. Delete selected template');
							$(".small_loader_box").remove();
							$("#dialog-confirm-template-delete").dialog('close');

							$("#dialog-warning").dialog({title: 'Template Deletion'});
							$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
							$("#dialog-warning").dialog('open');
						},
						success: function(response_data){
							if(response_data.status == 'ok'){
								window.location.reload();
							} else {
								//error, display the generic error message
								$("#btn-confirm-template-delete-ok").prop("disabled", false);
								$("#btn-confirm-template-delete-cancel").show();
								$("#btn-confirm-template-delete-ok").text('Yes. Delete selected template');
								$(".small_loader_box").remove();
								$("#dialog-confirm-template-delete").dialog('close');

								$("#dialog-warning").dialog({title: 'Template Deletion'});
								$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
								$("#dialog-warning").dialog('open');
							}
						}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-confirm-template-delete-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]
		});

		$('.action-export').on('click', function(e) {
			e.preventDefault();
			var template_id = $(this).parent().parent().attr('template-id');
			window.location.href = 'export_template.php?template_id=' + template_id;
		});

		$('.action-delete').on('click', function(e) {
			e.preventDefault();
			var template_id = $(this).parent().parent().attr('template-id');
			$('#template_delete_id').val(template_id);
			$("#dialog-confirm-template-delete").dialog('open');
		});

		$('#import_template_btn').on('click', function(e) {
			e.preventDefault();
			$('#import-template-button').trigger('click');
		});

		$('input#import-template-button').live('change', function(){
			if (window.File && window.FileReader && window.FileList && window.Blob){
				var filesObj = $('input#import-template-button')[0].files;
				var fext = "";
				for(index in filesObj) {
					if(!isNaN(index)){
						var fname = $('input#import-template-button')[0].files[parseInt(index)].name; //get file name
						var fsize = $('input#import-template-button')[0].files[parseInt(index)].size; //get file size
						fext = fname.split('.').pop().toLowerCase();
					}
				}
				if($.inArray(fext, ['json']) == -1){
					$("#dialog-warning").dialog({title: 'Template Import'});
					$("#dialog-warning-msg").html("Please upload only JSON files.");
					$("#dialog-warning").dialog('open');
					return false;
				} else {
					var file = filesObj[0];

					var reader = new FileReader();
					reader.onload = function (e) {
						try {
							var template_data = JSON.parse(e.target.result);
							$.ajax({
								type: "POST",
								async: true,
								url: "ajax-requests.php",
								data:
									{
										action: 'import_wysiwyg_template',
										template_name: template_data.template_name,
										template_content: template_data.template_content,
									},
								cache: false,
								global: false,
								dataType: "json",
								error: function(xhr,text_status,e){
									//error, display the generic error message
									$("#dialog-warning").dialog({title: 'Template Import'});
									$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
									$("#dialog-warning").dialog('open');
								},
								success: function(response_data){
									if(response_data.status == 'ok'){
										window.location.reload();
									} else {
										//error, display the generic error message
										$("#dialog-warning").dialog({title: 'Template Import'});
										$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
										$("#dialog-warning").dialog('open');
									}
								}
							});
						} catch (e) {
							$("#dialog-warning").dialog({title: 'Template Import'});
							$("#dialog-warning-msg").html("The file is not a JSON.");
							$("#dialog-warning").dialog('open');
						}
						
					};

					reader.readAsText(file);
				}
			} else {
				//Error for older unsupported browsers that doesn't support HTML5 File API
				$("#dialog-warning").dialog({title: 'Template Import'});
				$("#dialog-warning-msg").html("Please upgrade your browser, because your current browser lacks some new features we need!");
				$("#dialog-warning").dialog('open');
				return false;
			}
		});
	});
</script>
EOT;
	require('includes/footer.php');
?>