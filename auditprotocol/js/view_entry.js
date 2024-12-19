$(function(){
    
	//dialog box to confirm entry deletion
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
		buttons: [{
				text: 'Yes. Delete this entry',
				id: 'btn-confirm-entry-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					//disable the delete button while processing
					$("#btn-confirm-entry-delete-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-confirm-entry-delete-cancel").hide();
					$("#btn-confirm-entry-delete-ok").text('Deleting...');
					$("#btn-confirm-entry-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					var form_id = $("#ve_details").data("form_id");
					var company_id  = $("#ve_details").data("company_id");
					var entry_id = $("#ve_details").data("entry_id"); 
					var selected_entry = [{company_id: company_id, entry_id: entry_id}];
					//do the ajax call to delete the entries
					$.ajax({
						type: "POST",
						async: true,
						url: "delete_entries.php",
						data: {
							form_id: form_id,
							selected_entries: selected_entry
						},
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#btn-confirm-entry-delete-ok").prop("disabled",false);
							$("#btn-confirm-entry-delete-cancel").show();
							$("#btn-confirm-entry-delete-ok").text("Yes. Delete this entry");
							$("#btn-confirm-entry-delete-ok").next().remove();
							$("#dialog-confirm-entry-delete").dialog('close');

							$("#dialog-error").dialog("option", "title", "Unable to delete this entry data");
							$("#dialog-error-msg").html("Something went wrong. Please try again later.");
							$("#dialog-error").dialog("open");
						},
						success: function(response_data){
							if(response_data.status == "ok") {
								window.location.replace('manage_entries.php?id=' + form_id);
							} else {
								$("#btn-confirm-entry-delete-ok").prop("disabled",false);
								$("#btn-confirm-entry-delete-cancel").show();
								$("#btn-confirm-entry-delete-ok").text("Yes. Delete this entry");
								$("#btn-confirm-entry-delete-ok").next().remove();
								$("#dialog-confirm-entry-delete").dialog('close');

								$("#dialog-error").dialog("option", "title", "Unable to delete this entry data");
								$("#dialog-error-msg").html("Something went wrong. Please try again later.");
								$("#dialog-error").dialog("open");
							}
						},
						complete:function(){}
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
			}]
	});
	
	//open the deletion dialog when the delete entry link clicked
	$("#ve_action_delete").click(function(){	
		$("#dialog-confirm-entry-delete").dialog('open');
		return false;
	});

	$("#submit_form").click(function(){	
		var processingDiv = document.getElementById("WaitDialog");
		if (processingDiv != null) {
			if (processingDiv.style.display === "none") {
				processingDiv.style.display = "block";
			}
		}	
	});

	//dialog box to email the entry
	$("#dialog-email-entry").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Email Entry',
			id: 'dialog-email-entry-btn-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {

				if($("#dialog-email-entry-input").val() == ""){
					alert('Please enter the email address!');
				}else{
					
					var form_id = $("#ve_details").data("form_id");
					var company_id = $("#ve_details").data("company_id");
					var entry_id = $("#ve_details").data("entry_id");

					//disable the email entry button while processing
					$("#dialog-email-entry-btn-ok").prop("disabled",true);
					
					//display loader image
					$("#dialog-email-entry-btn-cancel").hide();
					$("#dialog-email-entry-btn-ok").text('Sending...');
					$("#dialog-email-entry-btn-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					//do the ajax call to send the entry
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "email_entry.php",
						   data: {
								  	form_id: form_id,
								  	company_id: company_id,
								  	entry_id: entry_id,
								  	target_email: $("#dialog-email-entry-input").val()
								  },
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
						   		//restore the buttons on the dialog
								$("#dialog-email-entry").dialog('close');
								$("#dialog-email-entry-btn-ok").prop("disabled",false);
								$("#dialog-email-entry-btn-cancel").show();
								$("#dialog-email-entry-btn-ok").text('Email Entry');
								$("#dialog-email-entry-btn-ok").next().remove();
								$("#dialog-email-entry-input").val('');
								
								alert('Error! Unable to send entry. \nError message: ' + xhr.responseText); 
						   },
						   success: function(response_data){
								
								//restore the buttons on the dialog
								$("#dialog-email-entry").dialog('close');
								$("#dialog-email-entry-btn-ok").prop("disabled",false);
								$("#dialog-email-entry-btn-cancel").show();
								$("#dialog-email-entry-btn-ok").text('Email Entry');
								$("#dialog-email-entry-btn-ok").next().remove();
								$("#dialog-email-entry-input").val('');
								   	   
							   if(response_data.status == 'ok'){
								   //display the confirmation message
								   $("#dialog-entry-sent").dialog('open');
							   } 
								   
						   }
					});
				}
			}
		},
		{
			text: 'Cancel',
			id: 'dialog-email-entry-btn-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//open the email entry dialog when the email entry link clicked
	$("#ve_action_email").click(function(){	
		$("#dialog-email-entry").dialog('open');
		return false;
	});

	//if the user submit the form by hitting the enter key, make sure to call the button-email-entry handler
	$("#dialog-email-entry-form").submit(function(){
		$("#dialog-email-entry-btn-ok").click();
		return false;
	});

	//Dialog to display entry being sent successfully
	$("#dialog-entry-sent").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'OK',
			id: 'dialog-entry-sent-btn-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//attach event to "change payment status" link
	$("#payment_status_change_link").click(function(){	
		$("#payment_status_static").hide();
		$("#payment_status_form").show();

		return false;
	});

	//attach event to "cancel" link on payment status
	$("#payment_status_cancel_link").click(function(){	
		$("#payment_status_form").hide();
		$("#payment_status_static").show();

		return false;
	});

	//attach event to "save" link on payment status
	$("#payment_status_save_link").click(function(){
		
		$("#payment_status_dropdown").prop("disabled",true);
		$("#payment_status_save_cancel").hide();
		$("#payment_status_loader").show();

		var form_id = $("#ve_details").data("form_id");
		var entry_id = $("#ve_details").data("entry_id");

		//do the ajax call to send the entry
		$.ajax({
			   type: "POST",
			   async: true,
			   url: "change_payment_status.php",
			   data: {
					  	form_id: form_id,
					  	entry_id: entry_id,
					  	payment_status: $("#payment_status_dropdown").val()
					  },
			   cache: false,
			   global: false,
			   dataType: "json",
			   error: function(xhr,text_status,e){
			   		//restore the links to original and display alert
					$("#payment_status_dropdown").prop("disabled",false);
					$("#payment_status_save_cancel").show();
					$("#payment_status_loader").hide();

					alert('Error! Unable to change status. \nError message: ' + xhr.responseText); 
			   },
			   success: function(response_data){
					//restore the link and update the payment status
					$("#payment_status_dropdown").prop("disabled",false);
					$("#payment_status_save_cancel").show();
					$("#payment_status_loader").hide();

					if(response_data.status == 'ok'){
						$(".payment_status").removeClass('paid').text(response_data.payment_status.toUpperCase());	

						if(response_data.payment_status == 'paid'){
							$(".payment_status").addClass('paid');
						}

						$("#payment_status_form").hide();
						$("#payment_status_static").show();
					}else{
						alert('Error! Unable to change status. \nError message: ' + xhr.responseText); 
					}    
			   }
		});

		return false;
	});
	
	//start::show disclaimer before zip download
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
	
	//open the download document disclaimer when the download document link clicked
	$(".action-download-document-zip").click(function(){
		$("#btn-download-document-zip").data('documentdownloadlink', $(this).attr('data-documentdownloadlink'));
		$("#dialog-download-document-zip").dialog('open');
		return false;
	});
	//end::show disclaimer before zip download
	
	$("#processing-dialog-view, #processing-dialog-file, #processing-dialog-edit-entry, #processing-dialog-document").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	$("#processing-dialog-document").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$("#document-preview").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 930,
		draggable: false,
		resizable: false,
		open: function(){
			//$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$("#dialog-error").dialog({
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

	//dialog box to reset all the form's status indicators
	$("#dialog-reset-status-indicators").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 650,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-reset-status-indicators-reset").blur();
		},
		buttons: [{
			text: 'Yes. Reset',
			id: 'btn-reset-status-indicators-reset',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the reset button while processing
				$("#btn-reset-status-indicators-reset").prop("disabled",true);
					
				//display loader image
				$("#btn-reset-status-indicators-cancel").hide();
				$("#btn-reset-status-indicators-reset").text('Resetting...');
				$("#btn-reset-status-indicators-reset").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				
				var form_id = $("#ve_details").data("form_id");
				var company_id  = $("#ve_details").data("company_id");
				var entry_id = $("#ve_details").data("entry_id");
				var status = $("input[name='change-status-to']:checked").val();
				$.ajax({
					type: "POST",
					async: true,
					url: "ajax-requests.php",
					data: {
						action: "change_all_status",
						form_id: form_id,
						company_id: company_id,
						entry_id: entry_id,
						status: status
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#btn-reset-status-indicators-reset").prop("disabled",false);
						$("#btn-reset-status-indicators-cancel").show();
						$("#btn-reset-status-indicators-reset").text("Yes. Reset");
						$("#btn-reset-status-indicators-reset").next().remove();
						$("#dialog-reset-status-indicators").dialog("close");

						$("#dialog-error").dialog("option", "title", "Unable to reset status indicators");
						$("#dialog-error-msg").html("Something went wrong. Please try again later.");
						$("#dialog-error").dialog("open");
					},
					success: function(response_data){
						if(response_data.status == 'ok'){
							window.location.reload();
						} else {
							$("#btn-reset-status-indicators-reset").prop("disabled",false);
							$("#btn-reset-status-indicators-cancel").show();
							$("#btn-reset-status-indicators-reset").text("Yes. Reset");
							$("#btn-reset-status-indicators-reset").next().remove();
							$("#dialog-reset-status-indicators").dialog("close");

							$("#dialog-error").dialog("option", "title", "Unable to reset status indicators");
							$("#dialog-error-msg").html("Something went wrong. Please try again later.");
							$("#dialog-error").dialog("open");
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-reset-status-indicators-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$(document).on('click', '.entry-link-preview', function(e){
		e.preventDefault();
		$('#document-preview-content').html("");
		$('#file_viewer_download_button').attr('href', "");
		var identifier = $(this).data('identifier');
		var ext = $(this).data('ext');
		var src = $(this).data('src');

		$('#document-preview-content').html("");
		if( identifier == 'image_format' ) {
			//means this document is an image and has format one of these ('png', 'jpg', 'jpeg')
			//so we can show directly it in popup
			$('#document-preview-content').html('<img src="'+src+'" style="max-width: 100%;max-height: 100%;margin: auto;display: block;" />');
			$('#file_viewer_download_button').attr('href', src);
			$('#document-preview').dialog('open');
		} else if( identifier == 'other' ) {
			$('#processing-dialog-file').dialog('open');

			//do the ajax call to get pdf link
			$.ajax({
				type: "GET",
				async: true,
				url: "download.php?q="+src,
				cache: false,
				global: false,
				dataType: "json",
				error: function(xhr,text_status,e){
					//show error message to user
					$('#processing-dialog-file').dialog('close');
					$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
					$('#document-preview').dialog('open');
				},
				success: function(response){
					if( response.status == 'success' ) {
						if( response.only_download ) {
							$('#document-preview-content').html('Preview is not available for this file extension.');
						} else {
							$('#document-preview-content').html('<embed src="'+response.file_src+'#toolbar=0" type="application/pdf" width="100%" height="100%">');
						}
						$('#processing-dialog-file').dialog('close');
						$('#document-preview').dialog('open');
						$('#file_viewer_download_button').attr('href', response.download_src);

					} else {
						$('#processing-dialog-file').dialog('close');
						$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
						$('#document-preview').dialog('open');
					}
				}
			});
		}
	});

	$(document).on('click', '#ve_action_status_change', function(e){
		$("#dialog-reset-status-indicators").dialog('open');
	});

	$(document).on('click', '.cascade-form-expand', function(e){
		e.preventDefault();
		var cascade_form_link = $(this);
		var form_id = $(this).data('form_id');
		var parent_form_id = $("#ve_details").data("form_id");
		var company_id  = $("#ve_details").data("company_id");
		var entry_id = $("#ve_details").data("entry_id");
		$.ajax({
			type: "GET",
			async: true,
			url: "view_entry_ajax_requests.php",
			data: {
			    entry_id: entry_id,
				parent_form_id: parent_form_id,
				form_id: form_id,
				company_id: company_id,
			},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message	
			},
			success: function(response){
			    $('.cascade-form-expand-content').remove();
			    $('#cascade-form-'+form_id).append('<table class="cascade-form-expand-content" style="width:100%">'+response+'</table>');
		        $('.cascade-form-expand').show();
		        cascade_form_link.hide();
			},
			complete: function(){
				setAjaxDefaultParam();
			}
		});
	});
	
	$(document).on('click', 'input#move-to-company', function(){
		if ($('select#portal-company').val() == '0') {
			alert('Please select entity');
		} else {
			var form_id = $("#ve_details").data("form_id");
			var company_id  = $("#ve_details").data("company_id");
			var entry_id = $("#ve_details").data("entry_id");
			$.ajax({
				type: "POST",
				async: true,
				url: "move-data-to-entity.php",
				data: {
					post_csrf_token: $('#csrf-token-meta').attr('content'),
					form_id: form_id,
					company_id: company_id,
					entry_id: entry_id,
					entity_id: $('select#portal-company').val()
				},
				cache: false,
				global: false,
				error: function(xhr,text_status,e){
					//error, display the generic error message
					$("#dialog-error").dialog("option", "title", "Unable to move entry data.");
					$("#dialog-error-msg").html("Something went wrong. Please try again later.");
					$("#dialog-error").dialog("open");
				},
				success: function(response){
					if(!alert('Data assigned to selected entity')){
						window.location.href = "/auditprotocol/view_entry.php?form_id="+form_id+"&company_id="+$('select#portal-company').val()+"&entry_id="+entry_id;
					}
				},
				complete: function(){
					setAjaxDefaultParam();
				}
			});
		}
	});
	
	$('#ve_action_edit').click(function(){
		var message_div = $('div#opening-entry-dialog');
		message_div.css("visibility", "visible");
	});
	
	// Export to PDF
	$('#ve_action_pdf').click(function(){
		var message_div = $('div#processing-pdf-dialog');
		message_div.css("visibility", "visible");
		var form_id = $("#ve_details").data("form_id");
		var _form_details = '<table>' + $('#ve_detail_table').html() + '</table><table>' + $('#ve_table_info').html() + '</table>';
		
		$.ajax({
			type: "POST",
			async: true,
			url: "generate_entries_pdf.php",
			data: {
				post_csrf_token: $('#csrf-token-meta').attr('content'),
				form_id: form_id,
				form_details: _form_details
			},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message
			},
			success: function(response){
				response = JSON.parse(response);

				if(response['status'] == 'ok') {
					var link = document.createElement("a");
					link.download = response['file_name'];
					link.href = response['download_path'];
					link.click();
				} else {

				}
				message_div.css("visibility", "hidden");
			},
			complete: function(){
				setAjaxDefaultParam();
			}
		});
	});
});

//function to generate document for entry
function generate_entry_document(form_id, la_user_id, company_user_id, entry_id) {
	$("#processing-dialog-document").dialog('open');
	
	$.ajax({
	    type: "POST",
	    async: true,
	    url: "ajax-requests.php",
	    data: {
	        action: 'generate_entry_document',
	        form_id: form_id,
	        la_user_id: la_user_id,
	        company_user_id: company_user_id,
	        entry_id: entry_id
	    },
	    cache: false,
	    global: false,
	    dataType: "json",
	    error: function(h, f, g) {
	    	$('#processing-dialog-document').html('Error Occurred while requesting. Please try again later.');
	    },
	    success: function(e) {
			if (e.success) {
				$('#processing-dialog-document').html('Document has been generated successfully.');
				document.location.reload();
			} else if (e.error) {
				$('#processing-dialog-document').html('Error Occurred while requesting. Please try again later.');
			}
	    }
    });
}