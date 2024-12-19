$(document).ready(function() {
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	$("#entry_management_table").DataTable({
		dom: 'rtip',
		pageLength: 20,
		sPaginationType: "numbers",
		order: [[0, 'asc']],
		responsive: true
	});

	$("#processing-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

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
	
	//open the deletion dialog when the download document link clicked
	$("#entry_management_table").on("click", ".action-download-document-zip", function(e){
		$("#btn-download-document-zip").data('documentdownloadlink', $(this).attr('data-documentdownloadlink'));
		$("#dialog-download-document-zip").dialog('open');
		return false;
	});
	//go to entry details page
	$("#entry_management_table").on("click", ".action-view", function(e){
		var form_id = $(this).parent().attr("data-form-id");
		var company_id = $(this).parent().attr("data-company-id");
		var entry_id = $(this).parent().attr("data-entry-id");
		$('#processing-dialog').dialog('open');
		window.location.href = "view_entry.php?form_id="+form_id+"&company_id="+company_id+"&entry_id="+entry_id;
	});
});