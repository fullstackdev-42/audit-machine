$(function () {
	var tables = [];
	//initiate dataTables
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	$(".data-table").each(function(){
		var _table_name = $(this).attr("data-table-name");
		var table = $(this).DataTable({
	    	dom: 'B<"toolbar">frtip',
	    	buttons: [
	    		{
		            extend: 'csvHtml5',
		            text: 'Save as CSV',
		            filename: _table_name,
		            title: _table_name,
		            className: 'bb_button bb_small bb_green',
		            exportOptions: {
                        columns: ':not(:eq(0))',
                        orthogonal: {
                            display: ':null'
                        },
                        modifier: {
				            order: 'current',
				            page: 'all',
				            selected: null,
				        }
                	}
		        },
	            {
		            extend: 'excelHtml5',
		            text: 'Save as Excel',
		            filename: _table_name,
		            title: _table_name,
		            className: 'bb_button bb_small bb_green',
		            exportOptions: {
                        columns: ':not(:eq(0))',
                        orthogonal: {
                            display: ':null'
                        },
                        modifier: {
				            order: 'current',
				            page: 'all',
				            selected: null,
				        }
                	}
		        },
		        {
		            extend: 'pdfHtml5',
		            text: 'Save as PDF',
		            filename: _table_name,
		            title: _table_name,
		            orientation: 'landscape',
                	pageSize: 'TABLOID',
                	className: 'bb_button bb_small bb_green',
                	exportOptions: {
                        columns: ':not(:eq(0))',
                        orthogonal: {
                            display: ':null'
                        },
                        modifier: {
				            order: 'current',
				            page: 'all',
				            selected: null,
				        }
                	},
                	customize: function ( doc ) {
                		var pdf_header_img = $("#pdf_header_img").val();
                		doc.defaultStyle.alignment = 'center';
						doc.content.splice( 0, 0, {
							margin: [ 0, 0, 20, 20 ],
							alignment: 'left',
							image: pdf_header_img
						});
						doc.content[2].layout = "Borders";
					}
		        }
	        ],
	        columnDefs: [
	        	{
			        orderable: false,
			        searchable: false,
		            className: 'select-checkbox',
		            targets:   0
			    },
		        {
			        targets: '_all',
			        render: function ( data, type, row ) {
					    return type === 'display' && data.length > 150 ?
					        data.substr( 0, 150 ) +'…' :
					        data;
					}
			    }
		    ],
		    select: {
	            style:    'single',
	            selector: 'td:first-child'
	        },
		    order: [[1, 'asc']],
			sPaginationType: "numbers"
		});
		tables.push(table);
	});

	//add custom toolbar for import button
	$("div.toolbar").html(
    	'<button type="button" class="bb_button bb_small bb_green dt-button import-data-btn" style="display:none;">Import data into form</button>'
   	);

	$(".data-table").on("click", "td.select-checkbox", function () {
		import_btn = $(this).parent().parent().parent().parent().find("button.import-data-btn")
		table_index = table_index = $("body").find("button.import-data-btn").index(import_btn);
		setTimeout(function(){
			if(tables[table_index].rows({ selected: true }).count() > 0) {
				import_btn.show();
			} else {
				import_btn.hide();
			}
		}, 300);
	});

	$("#dialog-select-error-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				id: 'btn-error-message-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	$("#dialog-error-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	$("#dialog-success-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	$("#dialog-import-confirm-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Ok',
				id: 'btn-import-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					var dialog = $(this);
					var data = [];
					var table_index = $(".form-info").attr("table-index");

					//add host info manually if the table is in vulnerability list
					tables[0].rows().eq(0).filter(function (rowIdx){
						if(tables[0].cell( rowIdx, 1 ).data() == $(".form-info").attr("hostname")) {
							var selected_host_row = tables[0].row(rowIdx).data();
							$.each(selected_host_row, function(i, cell){
								//if(cell != "") {
									data.push({"template_code": $(tables[0].columns(i).header()).attr("template-code"), "value": cell.replace(/(\r\n|\n|\r)/gm,"")});
								//}
							});
						}
					});

					var selected_rows = tables[table_index].rows({ selected: true }).data();
					$.each(selected_rows, function(index, row){
						var cells = row;
						$.each(cells, function(i, cell){
							//if(cell != "") {
								data.push({"template_code": $(tables[table_index].columns(i).header()).attr("template-code"), "value": cell.replace(/(\r\n|\n|\r)/gm,"")});
							//}
						});
					});

					var final_data = {
						"form_id": $(".form-info").attr("form-id"),
						"user_info": {"user_type": $(".form-info").attr("user-type"), "user_id": $(".form-info").attr("user-id")},
						"data": data
					}
					$.ajax({
						type: "POST",
						async: true,
						url: "import_report_data_into_form.php",
						data: final_data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	//error, display the generic error message
						   	dialog.dialog("close");
						   	$("#dialog-error-message").dialog("open");
						},
					   	success: function(response_data){
					   		dialog.dialog("close");
					   		if(response_data.status == "ok"){
					   			$("#go_to_form").attr("href", "view.php?form_id=" + response_data.form_id + "&entry_id=" + response_data.entry_id);
					   			$("#dialog-success-message").dialog("open");
					   		} else {
					   			$("#dialog-error-message").dialog("open");
					   		}
					   	}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-import-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	$("button.import-data-btn").click(function(){
		$(".form-info").removeAttr("table-index");
		$(".form-info").removeAttr("hostname");
		var table_index = $("body").find("button.import-data-btn").index(this);
		var selected_rows = tables[table_index].rows({ selected: true }).data();
		if(selected_rows.length == 0){
			$("#dialog-select-error-message").dialog("open");
		} else {
			$(".form-info").attr("table-index", table_index);
			var table = $(this).parent().parent().find("table");
			var content = "<table class = 'import-data-table'><thead><th style='width: 100px;'>Field</th><th>Data</th><th>Template Code</th></thead><tbody>";
			if($(this).parent().parent().find("table").attr("id") != "host_list_table") {				
				hostname = $(table).attr("data-table-name").replace("Vulnerability list of ", "");
				$(".form-info").attr("hostname", hostname);
			}
			$("#dialog-import-confirm-message").dialog("open");
		}
	});

	$(".middle_form_bar").click(function(){
		var _vulnerability_id = $(this).attr("data-vulnerability-id");
		$("div#data-vulnerability-detail-"+_vulnerability_id).toggle("slow");
	});
})