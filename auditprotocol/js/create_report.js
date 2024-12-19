$(document).ready(function() {
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	var form_table = $("#form-table").DataTable({
		dom: 'rftip',
		columnDefs: [{
			orderable: false,
			searchable: false,
			className: 'select-checkbox',
			targets: 0
		}],
		select: {
			style: 'multi',
			selector: 'td'
		},
		order: [[1, 'asc']],
		sPaginationType: "numbers"
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

	$("#dialog-select-forms").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 1200,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Save',
			id: 'btn-selected-files-save',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				var selected_rows = form_table.rows({ selected: true })[0];
				if(selected_rows.length != 0) {
					var selected_forms = [];
					var form_content = "<h5 style='text-align: center;'>"+selected_rows.length;
					if(selected_rows.length == 1) {
						form_content += " form has been selected.</h5>";
					} else {
						form_content += " forms have been selected.</h5>";
					}
					$.each(selected_rows, function(i, e) {
						var row = form_table.rows(e).nodes()[0];
						var new_form = $(row).attr("form_id");
						//check if a form has already been selected
						if($.inArray(new_form, selected_forms) == -1) {
							selected_forms.push(new_form);
							form_content += "<div class='selected-form-item'>"+$(row).attr("form_name")+" (# <b>"+$(row).attr("form_id")+")</b></div>";
						}
					})
					$("#div-selected-forms").html(form_content);
					$("#form_array").val(selected_forms.filter(function(a){return a !== ""}).join("|"));

					//get field data of the first selected form
					$.ajax({
						url:"ajax-requests.php",
						type:"POST",
						data:{action:"get_field_data", form_id: selected_forms[0]},
						cache: false,
						dataType: "json",
						error: function(xhr,text_status,e) {
							$("#error-message").html("Unable to get field data. Please try again later.");
							$("#dialog-select-error-message").dialog("open");
						},
						success: function(response_data){
							if(response_data.status == "ok") {
								field_option = "";
								var fields = response_data.fields;
								if(fields.length > 0) {
									fields.forEach(function(field){
										field_option += '<option value="'+field['element_id']+"|=|"+field['element_type']+'">'+field['element_title']+'</option>';
									})
								}
								$("#field-label").html(field_option);
							} else {
								$("#error-message").html("Unable to get field data. Please try again later.");
								$("#dialog-select-error-message").dialog("open");
							}
						}
					});
				}
				$(this).dialog('close');
			}
		},
		{
		text: 'Close',
			'class': 'bb_button bb_small bb_green',
			id: 'btn-selected-files-cancel',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$(document).on('click', '#div-selected-forms', function(e) {
		e.preventDefault();
		var company_id = $("#company_id").val();
		if($("#report-type").val() == "template-code") {
			company_id = 0;
		}
		$.ajax({
			url:"ajax-requests.php",
			type:"POST",
			data:{action:"get_submitted_forms_by_entity", company_id: company_id},
			cache: false,
			dataType: "json",
			error: function(xhr,text_status,e) {
				$("#error-message").html("Something went wrong. Please try again later.");
				$("#dialog-select-error-message").dialog("open");
			},
			success: function(response_data){
				if(response_data.status == "ok") {
					var forms = response_data.forms;
					if(forms.length > 0) {
						form_table.clear();
						var table_rows = "";
						forms.forEach(function(form){
							table_rows += "<tr form_id='"+form["form_id"]+"' form_name='"+form["form_name"]+"'><td></td><td>"+form["form_id"]+"</td><td>"+form["form_name"]+"</td></tr>";
						})
						form_table.rows.add($(table_rows)).draw();
						$("#dialog-select-forms").dialog('open');
					} else {
						$("#error-message").html("The selected entity doesn't have any subscribed forms. Please select another entity.");
						$("#dialog-select-error-message").dialog("open");
					}
				} else {
					$("#error-message").html("Something went wrong. Please try again later.");
					$("#dialog-select-error-message").dialog("open");
				}
			}
		});
	});

	$("select#company_id").change(function(){
		//reset selected forms and form data
		$("#form_array").val("");
		form_table.clear();
		$("#div-selected-forms").html("");
		$("#field-label").html("");
	});

	$("#report-type").change(function() {
		var report_type = $(this).val();
		if(report_type == "field-data") {
			var options = '<option value="line_chart">Line chart</option><option value="area_chart">Area chart</option><option value="column_and_bar_chart">Column and Bar chart</option><option value="pie_chart">Pie chart</option><option value="bubble_chart">Bubble chart</option><option value="combinations">Combinations</option><option value="3d_chart">3D chart</option>';
			$("#display-type").html(options);
			$("#field-label").parent().show();
			$("#math-functions").parent().show();
			$("#display-type").parent().show();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "field-note") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "status-indicator") {
			var options = '<option value="line_chart">Line chart</option><option value="area_chart">Area chart</option><option value="column_and_bar_chart">Column and Bar chart</option><option value="pie_chart">Pie chart</option><option value="bubble_chart">Bubble chart</option><option value="combinations">Combinations</option><option value="3d_chart">3D chart</option><option value="sunburst_chart">Sunburst chart</option><option value="polar_chart">Polar chart</option>';
			$("#display-type").html(options);
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().show();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "risk") {
			var options = '<option value="line_chart">Line chart</option><option value="column_and_bar_chart">Column and Bar chart</option><option value="pie_chart">Pie chart</option><option value="bubble_chart">Bubble chart</option><option value="combinations">Combinations</option><option value="sunburst_chart">Sunburst chart</option><option value="polar_chart">Polar chart</option>';
			$("#display-type").html(options);
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().show();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "maturity") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "compliance-dashboard") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "audit-dashboard") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().hide();
			$("#div-selected-forms").parent().hide();
		} else if(report_type == "artifact-management") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().hide();
			$("#div-selected-forms").parent().hide();
		} else if(report_type == "executive-overview") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().show();
			$("#div-selected-forms").parent().show();
		} else if(report_type == "template-code") {
			$("#field-label").parent().hide();
			$("#math-functions").parent().hide();
			$("#display-type").parent().hide();
			$("#company_id").parent().hide();
			$("#div-selected-forms").parent().show();
		}
	});

	$("select#display-type").change(function(){
		var _selector = $(this);
		var display_type = _selector.val();
		if(display_type == "heat_map" || display_type == "general_map" || display_type == "dynamic_map"){
			$("div#div-address-label > select#address-label").attr("name", "address_label");
			$("div#div-address-label").show();
		}else{
			$("div#div-address-label > select#address-label").removeAttr("name");
			$("div#div-address-label").hide();	
		}
	});

	function select_start_date(dates){
		var _dateSelected = '';
		var _mm = '';
		var _dd = '';
		var _yyyy = '';
		if(dates.length){
			_dateSelected = (dates[0].getMonth() + 1) + '/' + dates[0].getDate() + '/' + dates[0].getFullYear();
			_mm = (dates[0].getMonth() + 1);
			_dd = dates[0].getDate();
			_yyyy = dates[0].getFullYear();
		}
		$('input#start_mm').val(_mm);
		$('input#start_dd').val(_dd);
		$('input#start_yyyy').val(_yyyy);
	}

	function select_completion_date(dates){
		var _dateSelected = '';
		var _mm = '';
		var _dd = '';
		var _yyyy = '';
		if(dates.length){
			_dateSelected = (dates[0].getMonth() + 1) + '/' + dates[0].getDate() + '/' + dates[0].getFullYear();
			_mm = (dates[0].getMonth() + 1);
			_dd = dates[0].getDate();
			_yyyy = dates[0].getFullYear();
		}
		$('input#completion_mm').val(_mm);
		$('input#completion_dd').val(_dd);
		$('input#completion_yyyy').val(_yyyy);
	}

	$("#start_mm").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});
	$("#start_dd").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});
	$("#start_yyyy").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});

	$("#completion_mm").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});
	$("#completion_dd").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});
	$("#completion_yyyy").keypress(function(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});

	$('#start_date').datepick({ 
		onSelect: select_start_date,
		showTrigger: '#start_date_img'
	});

	$('#completion_date').datepick({ 
		onSelect: select_completion_date,
		showTrigger: '#completion_date_img'
	});

	$(document).on('click', '#create-report', function(e) {
		var current_date = new Date();
		var current_year = current_date.getFullYear();
		var company_id = $('select#company_id').val();
		var report_type = $('select#report-type').val();
		var field_label_length = $('select#field-label option:selected').length;
		var error = "";

		if (report_type == "field-data" && field_label_length == 0) {
			error = 'Please select the form data.';
		}

		if(company_id == 0){
			error = 'Please select an entity for the entry datasource.';
		}

		var start_mm = parseInt($("#start_mm").val());
		var start_dd = parseInt($("#start_dd").val());
		var start_yyyy = parseInt($("#start_yyyy").val());
		var completion_mm = parseInt($("#completion_mm").val());
		var completion_dd = parseInt($("#completion_dd").val());
		var completion_yyyy = parseInt($("#completion_yyyy").val());

		if(isNaN(completion_mm) || isNaN(completion_dd) || isNaN(completion_yyyy) || completion_mm > 12 || completion_dd > 31 || completion_yyyy < current_year) {
			error = 'Please select the correct scheduled completion date.';
		} else {
			$('input#completion_date').val(completion_mm+"/"+completion_dd+"/"+completion_yyyy);
		}

		if(isNaN(start_mm) || isNaN(start_dd) || isNaN(start_yyyy) || start_mm > 12 || start_dd > 31 || start_yyyy < current_year) {
			error = 'Please select the correct scheduled start date.';
		} else {
			$('input#start_date').val(start_mm+"/"+start_dd+"/"+start_yyyy);
		}

		if(error != "") {
			$("#error-message").html(error);
			$("#dialog-select-error-message").dialog("open");
		} else {
			$.ajax({
				url:"create_reports.php",
				type:"POST",
				data: $("#generate-report-form").serialize(),
				cache: false,
				dataType: "json",
				error: function(xhr,text_status,e) {
					$("#error-message").html("Unable to create a report. Please try again later.");
					$("#dialog-select-error-message").dialog("open");
				},
				success: function(response_data){
					if(response_data.status == "success") {
						window.location.href = "manage_reports.php";
					} else {
						$("#error-message").html(response_data.message);
						$("#dialog-select-error-message").dialog("open");
					}
				}
			});
		}
	});
});