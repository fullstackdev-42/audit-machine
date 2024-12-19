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
	
	//go to saint report details page
	$("#saint_list_table").on("click", ".action-view", function(e){
		var saint_report_id = $(this).parent().attr("saint-id");
		var form_id = $("#form_id").val();
		window.location.href = "saint_report_details.php?saint_report_id="+saint_report_id+"&form_id="+form_id;
	});
	
	//go to nessus report details page
	$("#nessus_list_table").on("click", ".action-view", function(e){
		var nessus_report_id = $(this).parent().attr("nessus-id");
		var form_id = $("#form_id").val();
		window.location.href = "nessus_report_details.php?nessus_report_id="+nessus_report_id+"&form_id="+form_id;
	});
});