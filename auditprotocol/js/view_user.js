$(function(){
	var user_type = "admin";
	if($("#is_examiner").val() == 1) {
		user_type = "examiner";
	}
	function select_date(dates){
		var _dateSelected = '';
		var _mm = '';
		var _dd = '';
		var _yyyy = '';
		if(dates.length){
			var _dateSelected = (dates[0].getMonth() + 1) + '/' + dates[0].getDate() + '/' + dates[0].getFullYear();
			var _mm = (dates[0].getMonth() + 1);
			var _dd = dates[0].getDate();
			var _yyyy = dates[0].getFullYear();
		}
		$('input#account_suspension_strict_date_hidden').val(_dateSelected);
		$('input#mm').val(_mm);
		$('input#dd').val(_dd);
		$('input#yyyy').val(_yyyy);
	}
	
	$('a#button_save_notification').click(function(){
		$('form#cron-setting-form').submit();
	});
	
	$('#account_suspension_strict_date_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#strict-date').show();
		}else{
			$('div#strict-date').hide();
		}
	});
	
	$('#account_suspension_inactive_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#inactive-day').show();
		}else{
			$('div#inactive-day').hide();
		}
	});
	
	$('#suspended_account_auto_deletion_flag').click(function(){
		if($(this).prop('checked') == true){
			$('div#inactive-delete-day').show();
		}else{
			$('div#inactive-delete-day').hide();
		}
	});

	$('#account_suspension_strict_date_hidden').datepick({ 
		onSelect: select_date,
		showTrigger: '#cal_img_5'
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

	//dialog box to confirm user deletion
	$("#dialog-confirm-user-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-user-delete-ok").blur();
		},
		buttons: [{
				text: 'Yes. Delete this user',
				id: 'btn-confirm-user-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					//disable the delete button while processing
					$("#btn-confirm-user-delete-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-confirm-user-delete-cancel").hide();
					$("#btn-confirm-user-delete-ok").text('Deleting...');
					$("#btn-confirm-user-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					var user_id  = $("#vu_details").data("userid");
					var current_user = [{ name : "entry_" + user_id, value : "1"}];

					//do the ajax call to delete the users
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "change_user_status.php",
						   data: {
								  	action: 'delete',
								  	user_type: user_type,
								  	origin: 'view_user',
								  	selected_users: current_user
								  },
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
								   //error, display the generic error message		  
						   },
						   success: function(response_data){
									   
							   if(response_data.status == 'ok'){
								   //redirect to entries page again
								   if(response_data.user_id != '0' && response_data.user_id != ''){
								   		window.location.replace('view_user.php?id=' + response_data.user_id);
								   }else{
								   		window.location.replace('manage_users.php');
								   }
								  
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
			}]

	});
	
	//open the deletion dialog when the delete user link clicked
	$("#vu_action_delete").click(function(){	
		$("#dialog-confirm-user-delete").dialog('open');
		return false;
	});

	//suspend/unspend user when the suspend link being clicked
	$("#vu_action_suspend").click(function(){

		if($(this).data('processing') !== true){
			$(this).data('processing',true);

			if($(this).hasClass('unsuspend')){
				
				//unsuspend the user
				$(this).removeClass('unsuspend').append(' <img src="images/loader_small_grey.gif" style="vertical-align: middle" />');

				//do the ajax call to unsuspend the user
				var user_id  = $("#vu_details").data("userid");
				var current_user = [{ name : "entry_" + user_id, value : "1"}];
					
				//do the ajax call to delete the users
				$.ajax({
					   type: "POST",
					   async: true,
					   url: "change_user_status.php",
					   data: {
							  	action: 'unsuspend',
							  	user_type: user_type,
							  	no_session_msg: '1',
							  	selected_users: current_user
							  },
					   cache: false,
					   global: false,
					   dataType: "json",
					   error: function(xhr,text_status,e){
							   //error, display the generic error message		  
					   },
					   success: function(response_data){
									   
						   if(response_data.status == 'ok'){
						   		 $("#vu_action_suspend").data("processing",false);
							     $("#vu_action_suspend").html('<img src="images/navigation/005499/16x16/Suspend.png "> Suspend');
							     $("#vu_suspended").fadeOut(function(){
							     	$(this).remove();
							     });
						   }	  
								   
					   }
				});
			}else{
				//suspend the user
				$(this).addClass('unsuspend').append(' <img src="images/loader_small_grey.gif" style="vertical-align: middle" />');

				//do the ajax call to unsuspend the user
				var user_id  = $("#vu_details").data("userid");
				var current_user = [{ name : "entry_" + user_id, value : "1"}];

				//do the ajax call to delete the users
				$.ajax({
					   type: "POST",
					   async: true,
					   url: "change_user_status.php",
					   data: {
							  	action: 'suspend',
							  	user_type: user_type,
							  	no_session_msg: '1',
							  	selected_users: current_user
							  },
					   cache: false,
					   global: false,
					   dataType: "json",
					   error: function(xhr,text_status,e){
							   //error, display the generic error message		  
					   },
					   success: function(response_data){
									   
						   if(response_data.status == 'ok'){
						   		 $("#vu_action_suspend").data("processing",false);
							     $("#vu_action_suspend").html('<img src="images/navigation/005499/16x16/Unlock.png"> Unblock');
							     $("#vu_profile").append('<div id="vu_suspended" style="display: none">This user is currently being <span>SUSPENDED</span></div>');
						   		 $("#vu_suspended").fadeIn();
						   }	  
								   
					   }
				});
			}
		}

		return false;
	});	

	//dialog box to change password
	$("#dialog-change-password").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Update Password',
			id: 'dialog-change-password-btn-save-changes',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				var password_1 = $.trim($("#dialog-change-password-input1").val());
				var password_2 = $.trim($("#dialog-change-password-input2").val());
				var current_user_id = $("#vu_details").data("userid");

				var send_login_info = 0;
				if($("#dialog-change-password-send-login").prop("checked") == true){
					send_login_info = 1;
				}

				if(password_1 == "" || password_2 == ""){
					$('#change-password-notifications').addClass('error').text('Please enter both password fields!').show();
				}else if(password_1 != password_2){
					$('#change-password-notifications').addClass('error').text('Please enter the same password for both fields!').show();
				}else{
					//disable the save changes button while processing
					$("#dialog-change-password-btn-save-changes").prop("disabled",true);
						
					//display loader image
					$("#dialog-change-password-btn-cancel").hide();
					$("#dialog-change-password-btn-save-changes").text('Saving...');
					$("#dialog-change-password-btn-save-changes").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");

					//do the ajax call to change the password
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "change_password.php",
						   data: {
								  	np: password_1,
								  	cp: password_2,
								  	user_id: current_user_id,
								  	send_login: send_login_info
								  },
						   dataType: "json",
						   error: function(xhr,text_status,e){
							   //error, display the generic error message
							   $('#change-password-notifications').addClass('error').text('Unable to save the password!').show(); 
						   },
						   success: function(response_data){	   
							   //restore the buttons on the dialog
								$("#dialog-change-password-btn-save-changes").prop("disabled",false);
								$("#dialog-change-password-btn-cancel").show();
								$("#dialog-change-password-btn-save-changes").text('Save Password');
								$("#dialog-change-password-btn-save-changes").next().remove();
								$("#dialog-change-password-input1").val('');
								$("#dialog-change-password-input2").val('');
								$("#dialog-change-password-send-login").prop("checked",false);
									   	   
								if(response_data.status == 'success'){
									//display the confirmation message
									$("#dialog-change-password").dialog('close');
									$('#dialog-password-changed-msg').text('The new password has been saved. New Password is '+response_data.new_password);
									$("#dialog-password-changed").dialog('open');
								} else if (response_data.status == 'error') {
									$('#change-password-notifications').addClass('error').text(response_data.message).show();
								} 
						   }
					});
				}
			}
		},
		{
			text: 'Cancel',
			id: 'dialog-change-password-btn-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]

	});

	//open the change password dialog
	$("#vu_action_password").click(function(){	
		$("#dialog-change-password").dialog('open');
		return false;
	});

	//Dialog to display password has been changed successfully
	$("#dialog-password-changed").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'OK',
				id: 'dialog-password-changed-btn-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
					location.reload();
				}
			}]

	});
	
	//dialog box to reset MFA
	$("#dialog-reset-mfa").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-reset-mfa-ok").blur();
		},
		buttons: [
			{
				text: 'Yes. Reset',
				id: 'btn-reset-mfa-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					//disable the reset button while processing
					$("#btn-reset-mfa-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-reset-mfa-cancel").hide();
					$("#btn-reset-mfa-ok").text('Resetting...');
					$("#btn-reset-mfa-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					var user_id  = $("#vu_details").data("userid");
					
					//do the ajax call to reset MFA
					$.ajax({
						type: "POST",
						async: true,
						url: "reset_authentication.php",
						data: {
							action: 'reset_admin_mfa',
							origin: 'view_user',
							user_id: user_id
						},
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
							//error, display the generic error message
							$("#dialog-reset-mfa").dialog('close');
							$("#dialog-warning").dialog('open');
						},
						success: function(response_data){
							if(response_data.status == 'ok'){
								window.location.reload();
							} else {
								$("#dialog-reset-mfa").dialog('close');
								$("#dialog-warning").dialog('open');
							}
						}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'btn-reset-mfa-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});
	
	//open the reset MFA dialog when the reset MFA user link clicked
	$("#vu_action_reset_mfa").click(function(){	
		$("#dialog-reset-mfa").dialog('open');
		return false;
	});

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
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	
	//open the export dialog when the export link being clicked
	$("#entry_export").click(function(){
		$("#dialog-export-entries").dialog('open');
		return false;
	});

	$("#document_log_export").click(function(){
		$("#dialog-export-documents").dialog('open');
		return false;
	});

	$("#dialog-export-documents").dialog({
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

	//start::list_user_session_log export

	$("#user_session_log_export").click(function(){
		$("#user-session-export-documents").dialog('open');
		return false;
	});

	$("#user-session-export-documents").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			// $(this).next().find('button').blur()
		},
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$("#user-session-export-documents a").click(function(e){
		var user_id 	   	    = $("#entries_table").data("user-id");
		var full_selection      = 1;
		var clicked_link		= $(this).attr("id");
		var filename     		= $(this).attr("data-filename");
		var isportal     		= $(this).data("isportal");
		var export_type			= 'xls';

		if(clicked_link == 'export_as_excel'){
			export_type = 'xls';	
		}if(clicked_link == 'export_as_csv'){
			export_type = 'csv';
		}if(clicked_link == 'export_as_txt'){
			export_type = 'txt';
		}

		e.preventDefault();  //stop the browser from following the redirect
		
		if(full_selection == 1){ //all entries being selected
			window.location.href = 'audit_log_user_session.php?type='+ export_type +'&user_id=' + user_id + '&filename='+filename + '&is_portal=' + isportal;
		}
				
		return false;
	});

	//end::list_user_session_log export
	//send the exported file to the user when the export file type link beinhg clicked
	$("#dialog-export-entries a").click(function(e){
		var user_id 	   	    = $("#entries_table").data("user-id");
		var full_selection      = 1;
		var clicked_link		= $(this).attr("id");
		var filename     		= $(this).attr("data-filename");
		var isportal     		= $(this).data("isportal");
		var export_type			= 'xls';

		if(clicked_link == 'export_as_excel'){
			export_type = 'xls';	
		}if(clicked_link == 'export_as_csv'){
			export_type = 'csv';
		}if(clicked_link == 'export_as_txt'){
			export_type = 'txt';
		}

		e.preventDefault();  //stop the browser from following the redirect
		
		if(full_selection == 1){ //all entries being selected
			window.location.href = 'audit_log_entries.php?type='+ export_type +'&user_id=' + user_id + '&filename='+filename + '&is_portal=' + isportal;
		}
				
		return false;
	});

	$("#dialog-export-documents #export_as_csv").click(function(e){
		var user_id 	   	    = $("#entries_table").data("user-id");
		var filename     		= $(this).data("filename");
		var is_portal     		= $(this).data("isportal");

		e.preventDefault();  //stop the browser from following the redirect
		
		// window.location.href = 'audit_log_entries.php?&user_id=' + user_id + '&filename='+filename;
		window.location.href = 'audit_log_documents.php?&user_id=' + user_id + '&filename='+filename + '&is_portal='+is_portal;
				
		return false;
	});
});