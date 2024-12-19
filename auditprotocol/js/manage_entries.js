$(document).ready(function() {
	//Generic entry list and entry backuplist tables using DataTable
	var entry_list_table = $("#entries_table").DataTable({
		dom: '<"entry_toolbar">frtip',
		columnDefs: [
			{
				orderable: false,
				searchable: false,
				className: 'select-checkbox',
				targets:   0
			},
			{ "width": "50px", "targets": 0 },
			{ "width": "50px", "targets": 1 },
			{ "width": "100px", "targets": 4 },
			{ "width": "150px", "targets": 5 }
		],
		select: {
			style:    'multi',
			selector: 'td:first-child'
		},
		order: [[1, 'asc']],
		pageLength: 15,
		sPaginationType: "numbers",
		responsive: true
	});
	$("div.entry_toolbar").html(
		'<div id="entries_actions" class="gradient_red" style="margin-bottom: 15px;"> \
			<ul> \
				<li> \
					<a id="entry_delete" href="#"><img src="images/navigation/ED1C2A/16x16/Delete.png "> Delete</a> \
				</li> \
				<li> \
					<div style="border-left: 1px dotted #80b638;height: 25px;margin-top:5px"></div> \
				</li> \
				<li> <a id="entry_export" href="javascript:void(0)"><img src="images/navigation/ED1C2A/16x16/Export.png"> Export</a> </li> \
				<li> \
					<div style="border-left: 1px dotted #80b638;height: 25px;margin-top:5px"></div> \
				</li> \
				<li> \
					<a id="entry_import" href="javascript:void(0)"><img src="images/navigation/ED1C2A/16x16/Import.png"> Import From Computer</a> \
				</li> \
			</ul> \
			<img src="images/icons/29a.png" style="position: absolute;left:5px;top:100%"> \
		</div>'
	);

	var entry_backup_list_table = $("#server_entries_table").DataTable({
		dom: '<"backup_toolbar">frtip',
		columnDefs: [
			{
				orderable: false,
				searchable: false,
				className: 'select-checkbox',
				targets:   0
			},
			{ "width": "50px", "targets": 0 },
			{ "width": "50px", "targets": 1 },
			{ "width": "150px", "targets": 3 }
		],
		select: {
			style:    'single',
			selector: 'td:first-child'
		},
		order: [[1, 'asc']],
		pageLength: 15,
		sPaginationType: "numbers",
		responsive: true
	});
	$("div.backup_toolbar").html(
		'<div id="entries_actions" class="gradient_red" style="margin-bottom: 15px;"> \
            <ul> \
              <li style="height: 25px; margin-top:5px"> \
                <a id="delete_backup_from_server_button" style="margin-top: -5px;" href="javascript:void(0)"><img src="images/navigation/ED1C2A/16x16/Delete.png "> Delete</a> \
              </li> \
              <li> \
                <div style="border-left: 1px dotted #80b638;height: 25px;margin-top:5px"></div> \
              </li> \
              <li> <a id="server_entry_export" href="javascript:void(0)"><img src="images/navigation/ED1C2A/16x16/Export.png"> Export</a> </li> \
              <li> \
                <div style="border-left: 1px dotted #80b638;height: 25px;margin-top:5px"></div> \
              </li> \
              <li id="entry_import_from_server"> \
                <a id="entry_import_from_server_button" href="javascript:void(0)"><img src="images/navigation/ED1C2A/16x16/Import.png"> Import From Server</a> \
              </li> \
              <li> \
                <div style="border-left: 1px dotted #80b638;height: 25px;margin-top:5px"></div> \
              </li> \
            </ul> \
            <img src="images/icons/29a.png" style="position: absolute;left:5px;top:100%" /> \
          </div> \
          <span id="import-loader-from-server" style="display:none;"><img src="images/loader_small_grey.gif" style="margin:8px;" /></span>'
	);
	
	//Generic warning dialog to be used everywhere
	$("#dialog-warning").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		open: function(){
			$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'OK',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//Generic processing dialog to be used everywhere
	$("#processing-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	//delete entry backup file
	$('#delete_backup_from_server_button').click(function() {
		if(entry_backup_list_table.rows({ selected: true }).count() == 0) {
			$("#dialog-warning").dialog("option", "title", "Unable to delete entry data from the server");
			$("#dialog-warning-msg").html("Please select an entry backup data to be deleted.");
			$("#dialog-warning").dialog('open');
		} else {
			$('#processing-dialog').dialog('open');
			var form_id = $("#data-form-id").data("form-id");
			var selected_entries = [];
			var selected_rows = entry_backup_list_table.rows('.selected').nodes();
			var entry_id = "";
			var path_to_file = "";
			$.each(selected_rows, function(index, selected_row) {
				entry_id = $(selected_row).data('db-id');
				path_to_file = $(selected_row).data('path-to-file');
			});
			$.ajax({
				type: "POST",
				async: true,
				url: "ajax-requests.php",
				data: {
					form_id: form_id,
					action: "delete_entry_backup_from_server",
					entry_id: entry_id,
					path_to_file: path_to_file
				},
				cache: false,
				global: false,
				error: function(xhr,text_status,e){
					//error, display the generic error message
					$('#processing-dialog').dialog('close');
					$("#dialog-warning").dialog("option", "title", "Unable to delete entry data from the server");
					$("#dialog-warning-msg").html("Sorry, you are unable to delete entry data from the server. Please try again later.");
					$("#dialog-warning").dialog('open');
				},
				success: function(response_data){
					$('#processing-dialog').dialog('close');
					if(response_data == "success") {
						window.location.reload();
					} else {
						$("#dialog-warning").dialog("option", "title", "Unable to delete entry data from the server");
						$("#dialog-warning-msg").html(response_data);
						$("#dialog-warning").dialog('open');
					}
				}
			});
		}
	});

	//import backup file from server
	$('#entry_import_from_server_button').click(function() {
		if(entry_backup_list_table.rows({ selected: true }).count() == 0) {
			$("#dialog-warning").dialog("option", "title", "Unable to import entry data from the server");
			$("#dialog-warning-msg").html("Please select an entry backup data to be imported.");
			$("#dialog-warning").dialog('open');
		} else {
			$('#processing-dialog').dialog('open');
			var form_id = $("#data-form-id").data("form-id");
			var selected_entries = [];
			var selected_rows = entry_backup_list_table.rows('.selected').nodes();
			var pathToFile = "";
			$.each(selected_rows, function(index, selected_row) {
				pathToFile = $(selected_row).data('path-to-file');
			});
			$.ajax({
				type: "POST",
				async: true,
				url: "import-entries.php",
				data: {
					form_id: form_id,
					action: "import_from_server",
					pathToFile: pathToFile
				},
				cache: false,
				global: false,
				dataType: "json",
				error: function(xhr,text_status,e){
					//error, display the generic error message
					$('#processing-dialog').dialog('close');
					$("#dialog-warning").dialog("option", "title", "Unable to import entry data");
					$("#dialog-warning-msg").html("Sorry, you are unable to import entry data from the server. Please try again later.");
					$("#dialog-warning").dialog('open');
				},
				success: function(response_data){
					$('#processing-dialog').dialog('close');
					if(response_data.status == "success") {
						window.location.reload();
					} else {
						$("#dialog-warning").dialog("option", "title", "Unable to import entry data");
						$("#dialog-warning-msg").html(response_data.message);
						$("#dialog-warning").dialog('open');
					}
				}
			});
		}
	});

	//export entry backup file from server
	$('#server_entry_export').click(function() {
		if(entry_backup_list_table.rows({ selected: true }).count() == 0) {
			$("#dialog-warning").dialog("option", "title", "Unable to export entry data from the server");
			$("#dialog-warning-msg").html("Please select an entry backup data to be exported.");
			$("#dialog-warning").dialog('open');
		} else {
			var form_id = $("#data-form-id").data("form-id");
			var selected_entries = [];
			var selected_rows = entry_backup_list_table.rows('.selected').nodes();
			var path_to_file = "";
			$.each(selected_rows, function(index, selected_row) {
				path_to_file = $(selected_row).data('path-to-file');
			});
			window.location.href = "/auditprotocol" + path_to_file;
		}
	});

	/************************EXPORT ENTRY************************/
	//export entries dialog box
	$("#dialog-export-entries").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'Export',
			id: 'btn-export-entry',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
				$('#processing-dialog').dialog('open');

				var form_id = $("#data-form-id").data("form-id");
				var selected_entries = [];
				var selected_rows = entry_list_table.rows('.selected').nodes();
				$.each(selected_rows, function(index, selected_row) {
					selected_entries.push({company_id: $(selected_row).data('company-id'), entry_id: $(selected_row).data('entry-id')});
				});
				save_entries_to_server = 0;
				if($("#save-export-radio").attr("checked")) {
					save_entries_to_server = 1;
				}

				$.ajax({
					type: "POST",
					async: true,
					url: "export_entries.php",
					data: {
						form_id: form_id,
						selected_entries: selected_entries,
						save_entries_to_server: save_entries_to_server
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$('#processing-dialog').dialog('close');
						$("#dialog-warning").dialog("option", "title", "Unable to export entry data");
						$("#dialog-warning-msg").html("Sorry, you are unable to export entry data. Please try again later.");
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){
						$('#processing-dialog').dialog('close');
						if(response_data.status == "success") {
							window.location.href = response_data.export_link;
						} else {
							$("#dialog-warning").dialog("option", "title", "Unable to export entry data");
							$("#dialog-warning-msg").html(response_data.message);
							$("#dialog-warning").dialog('open');
						}
					}
				});
			}
		}, {
			text: 'Cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//open the export dialog when the export link being clicked
	$("#entry_export").click(function(e){
		e.preventDefault();
		exportEntries();
	});
	
	function exportEntries(e){
		if(entry_list_table.rows({ selected: true }).count() > 0){
			if(entry_list_table.rows({ selected: true }).count() == 1) {
				$("#dialog-export-msg").html("1 entry has been selected to be exported.");
			} else {
				$("#dialog-export-msg").html(entry_list_table.rows({ selected: true }).count() + " entries have been selected to be exported.");
			}
			$("#dialog-export-entries").dialog('open');
		}else{ //none of the entries being selected, consider this as full selection as well
			$("#dialog-warning").dialog('option', 'title', 'Unable to export entry data');
			$("#dialog-warning-msg").html("You haven't selected any entry.<br />Please select at least one entry to be exported.");
			$("#dialog-warning").dialog('open');
		}
		return false;
	}
	/*************************************************************/

	/************************IMPORT ENTRY*************************/
	//import entries dialog box
	$("#dialog-import-entries").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	
	//open the import dialog when the import link being clicked
	$("#entry_import").click(function(){
		$('#upload-files').click();
		return false;
	});
	
	$('#upload-files').click(function(){ 
		$('#image-file').trigger('click');
	});
	
	$('input#image-file').live('change', function(){
		if (window.File && window.FileReader && window.FileList && window.Blob){
			var isValidArr = new Array();
			var filesObj = $('input#image-file')[0].files;
			for(index in filesObj) {
				if(!isNaN(index)){
					var filename = $('input#image-file')[0].files[parseInt(index)].name; //get file name
					var filesize = $('input#image-file')[0].files[parseInt(index)].size; //get file size
					var filetype = $('input#image-file')[0].files[parseInt(index)].type; //get file type
					if('application/zip' != filetype && 'application/x-zip-compressed' != filetype && 'multipart/x-zip' != filetype && 'application/x-compressed' != filetype){
						isValidArr.push({
							'filename': filename 
						});
					}
				}
            }
			if(isValidArr.length == 0){
				$('#upload-form').trigger('submit');
			}else{
				$("#dialog-warning").dialog("option", "title", "Unable to import entry data");
				$("#dialog-warning-msg").html("Please upload only zip files");
				$("#dialog-warning").dialog('open');
				return false;
			}
        }else{
		    //Error for older unsupported browsers that doesn't support HTML5 File API
		    $("#dialog-warning").dialog("option", "title", "Unable to import entry data");
			$("#dialog-warning-msg").html("Please upgrade your browser, because your current browser lacks some new features we need");
			$("#dialog-warning").dialog('open');
			return false;
		}
	});

	$("#upload-form").submit(function(e) {
		e.preventDefault();
		$('#processing-dialog').dialog('open');

		var formData = new FormData(this);

		$.ajax({
			type: "POST",
			async: true,
			url: "import-entries.php",
			data: formData,
			cache: false,
			global: false,
			dataType: "json",
			contentType: false,
			processData: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$('#processing-dialog').dialog('close');
				$("#dialog-warning").dialog("option", "title", "Unable to import entry data");
				$("#dialog-warning-msg").html("Sorry, you are unable to import entry data. Please try again later.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){
				$('#processing-dialog').dialog('close');
				if(response_data.status == "success") {
					window.location.reload();
				} else {
					$("#dialog-warning").dialog("option", "title", "Unable to import entry data");
					$("#dialog-warning-msg").html(response_data.message);
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});
	/*************************************************************/

	/************************DELETE ENTRY*************************/
	//dialog box to confirm entries deletion
	$("#dialog-confirm-entry-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-entry-delete-ok").blur();
		},
		buttons: [
			{
				text: 'Yes. Delete selected entries.',
				id: 'btn-confirm-entry-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					var form_id = $("#data-form-id").data("form-id");
					var selected_entries = [];
					var selected_rows = entry_list_table.rows('.selected').nodes();
					$.each(selected_rows, function(index, selected_row) {
						selected_entries.push({company_id: $(selected_row).data('company-id'), entry_id: $(selected_row).data('entry-id')});
					});
					//disable the delete button while processing
					$("#btn-confirm-entry-delete-ok").prop("disabled",true);
					
					//display loader image
					$("#btn-confirm-entry-delete-cancel").hide();
					$("#btn-confirm-entry-delete-ok").text('Deleting...');
					$("#btn-confirm-entry-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					//do the ajax call to delete the entries
					$.ajax({
						type: "POST",
						async: true,
						url: "delete_entries.php",
						data: {
							form_id: form_id,
							selected_entries: selected_entries
						},
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#btn-confirm-entry-delete-ok").prop("disabled",false);
							$("#btn-confirm-entry-delete-cancel").show();
							$("#btn-confirm-entry-delete-ok").text("Yes. Delete selected entries");
							$("#btn-confirm-entry-delete-ok").next().remove();
							$("#dialog-confirm-entry-delete").dialog('close');

							$("#dialog-error").dialog("option", "title", "Unable to delete this entry data");
							$("#dialog-error-msg").html("Something went wrong. Please try again later.");
							$("#dialog-error").dialog("open");
						},
						success: function(response_data){
							if(response_data.status == 'ok'){
								window.location.reload();
							} else {
								$("#btn-confirm-entry-delete-ok").prop("disabled",false);
								$("#btn-confirm-entry-delete-cancel").show();
								$("#btn-confirm-entry-delete-ok").text("Yes. Delete selected entries");
								$("#btn-confirm-entry-delete-ok").next().remove();
								$("#dialog-confirm-entry-delete").dialog('close');

								$("#dialog-error").dialog("option", "title", "Unable to delete this entry data");
								$("#dialog-error-msg").html("Something went wrong. Please try again later.");
								$("#dialog-error").dialog("open");
							}
						}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-confirm-entry-delete-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	//open confirm entries deletion dialog when the delete server entry link clicked
	$("#entry_delete").click(function(){
		if(entry_list_table.rows({ selected: true }).count() > 0){
			$("#dialog-confirm-entry-delete").dialog('open');
		} else {
			$("#dialog-warning").dialog('option', 'title', 'Unable to delete entry data');
			$("#dialog-warning-msg").html("You haven't selected any entry.<br />Please select at least one entry to be deleted.");
			$("#dialog-warning").dialog('open');
		}
		return false;
	});
	/*************************************************************/

	/******************DOWNLOAD TEMPLATE OUTPUTS******************/
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
	
	//open the download document zip dialog when the download document link clicked
	$(".action-download-document-zip").click(function(){
		$("#btn-download-document-zip").data('documentdownloadlink', $(this).attr('data-documentdownloadlink'));
		$("#dialog-download-document-zip").dialog('open');
		return false;
	});
	/*************************************************************/

	//turn on/off audit mode
	$("#entries_table tbody td .switch input").change(function(){
		var checkbox = $(this);
		var temp = checkbox.parent().parent().parent();
		var form_id = temp.data("form-id");
		var company_id = temp.data("company-id");
		var entry_id = temp.data("entry-id");
		var audit = checkbox.prop('checked') == true ? "1" : "0";

		$('#processing-dialog').dialog('open');

		$.ajax({
			type: "POST",
			async: true,
			url: "ajax-requests.php",
			data: {
				action: 'toggle_audit_form_entries',
				form_id: form_id,
				company_id: company_id,
				entry_id: entry_id,
				audit: audit
			},
			cache: false,
			global: false,
			dataType: "json",
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$("#processing-dialog").dialog('close');
				if(checkbox.prop('checked') == true) {
					checkbox.prop('checked', false);
				} else {
					checkbox.prop('checked', true);
				}
				$("#dialog-warning").dialog("option", "title", "Error");
				$("#dialog-warning-msg").html("Something went wrong. Please try again.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){
				$("#processing-dialog").dialog('close');
			}
		});
	});

	//dialog box to confirm approving entry(This is no longer supported)
	$("#dialog-form-approval-action").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-form-approval-ok").blur();
		},
		buttons: [{
				text: 'Yes. Approve entry.',
				id: 'btn-confirm-form-approval-ok',
				'class': 'bb_button bb_small bb_green',
				click: function(e) {
					e.preventDefault();
					
					//disable the delete button while processing
					$("#btn-confirm-form-approval-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-confirm-entry-delete-cancel").hide();
					$("#btn-confirm-form-approval-ok").text('Updating...');
					$("#btn-confirm-form-approval-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					var form_id = $("#data-form-id").data("form-id");
					var cId = $('input[name="dialog-form-approval-cId"]').val();
					var approval_status = $('input[name="dialog-form-approval-approval-status"]').val();
					var notes = $('textarea[name="approval-action-note"]').val();

					$.ajax({
					   	type: "POST",
					   	async: true,
					   	url: "manage_entries_ajax_requests.php",
					   	data: {approval_status: approval_status,
							  cId: cId,
							  form_id: form_id,
							  notes: notes
							  },
					   	cache: false,
					   	global: false,
					   	dataType: "json",
					   	error: function(xhr,text_status,e){
							   //error, display the generic error message
							alert('Error occured while processing.');
							$("#dialog-form-approval-action").dialog('close');
					   },
					   	success: function(response_data){
							location.reload();
						}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-confirm-form-approval-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]
	});

	//go to view entry page
	$("#entries_table").on("click", ".action-view", function(e){
		var form_id = $(this).parent().attr("data-form-id");
		var company_id = $(this).parent().attr("data-company-id");
		var entry_id = $(this).parent().attr("data-entry-id");
		$('#processing-dialog').dialog('open');
		window.location.href = "view_entry.php?form_id="+form_id+"&company_id="+company_id+"&entry_id="+entry_id;
	});
});