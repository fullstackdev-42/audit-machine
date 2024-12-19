$(document).ready(function() {
	//initiate DataTables
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	var saint_list_table = $("#saint_list_table").DataTable({
		dom: 'ftip',
		pageLength: 10,
		sPaginationType: "numbers",
		scrollX: true
	});
	var nessus_list_table = $("#nessus_list_table").DataTable({
		dom: 'ftip',
		pageLength: 10,
		sPaginationType: "numbers",
		scrollX: true
	});
	
	//Generic warning dialog to be used everywhere
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
				window.location.reload();
			}
		}]
	});
	//go to saint report details page
	$("#saint_list_table").on("click", ".action-view", function(e){
		var saint_report_id = $(this).parent().attr("saint-id");
		var form_id = $("#form_id").val();
		window.location.href = "saint_report_details.php?saint_report_id="+saint_report_id+"&form_id="+form_id;
	});
	//delete seleted saint report
	$("#saint_list_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var report_id = $(this).parent().parent().attr("saint-id");
		$("#delete_report_id").val(report_id);
		$("#delete_report_type").val("SAINT");
		$("#dialog-confirm-report-delete").dialog('open');
	});

	//go to nessus report details page
	$("#nessus_list_table").on("click", ".action-view", function(e){
		var nessus_report_id = $(this).parent().attr("nessus-id");
		var form_id = $("#form_id").val();
		window.location.href = "nessus_report_details.php?nessus_report_id="+nessus_report_id+"&form_id="+form_id;
	});
	//delete seleted nessus report
	$("#nessus_list_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var report_id = $(this).parent().parent().attr("nessus-id");
		$("#delete_report_id").val(report_id);
		$("#delete_report_type").val("Nessus");
		$("#dialog-confirm-report-delete").dialog('open');
	});

	//dialog box to confirm report deletion
	$("#dialog-confirm-report-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-report-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete report',
			id: 'btn-confirm-report-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-report-delete-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-report-delete-cancel").hide();
				$("#btn-confirm-report-delete-ok").text('Deleting...');
				$("#btn-confirm-report-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var delete_report_id = $("#delete_report_id").val();
				var delete_report_type = $("#delete_report_type").val();
				//do the ajax call to delete report
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: {
						action_type: 'single_report_delete',
						delete_report_id: delete_report_id,
						delete_report_type: delete_report_type,
						form_id: $("#form_id").val()
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog("option", "title", "Unable to delete the report!");
						$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
						$("#dialog-confirm-report-delete").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){
						if(response_data.status == "ok") {
							window.location.reload();
						} else {
							$("#dialog-warning").dialog("option", "title", "Unable to delete the report!");
							$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
							$("#dialog-confirm-report-delete").dialog('close');
							$("#dialog-warning").dialog('open');
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-report-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
});