function activate_tab(active_tab) {
	$("#tabs li.tab-item").removeClass("active");
	$("#tabs li." + active_tab + "_tab").addClass("active");
	$(".profile-content .tab-panel").css("display", "none");
	$("#" + active_tab).css("display", "block");
}
$(document).ready(function() {
	var delete_target_id = "";
	var delete_target_email = "";
	var active_tab = $("#tabs").attr("active-tab");
	activate_tab(active_tab);

	$("#tabs").on("click", ".tab-item a", function(e){
		e.preventDefault();
		var active_tab = $(this).attr("href").substring(1);
		activate_tab(active_tab);
		if(active_tab == "my_activity") {
			$(".tab-panel-header .actions").find("button.btn-activity").removeClass("active");
			$(".tab-panel-header .actions").find("button.btn-activity").first().addClass("active");
		}
	});
	
	var user_id = $("#my_user_id").val();
	//initiating avatar
	var cropperOptions = {
		processInline: true,
		cropUrl:'../auditprotocol/save_avatar.php',
		modal: true,
		cropData:{
			"is_admin": 0,
			"user_id": user_id,
			"mode": "my_profile"
		},
		onAfterImgCrop:	function(){location.reload(true);},
		onError: function(errormsg){location.reload(true);}
	}
	
	var cropperHeader = new Croppic('profile_image_upload', cropperOptions);

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

	//personal_info_form submission
	$(".profile-content").on("click", "#personal_info_btn", function(e){
		e.preventDefault();
		var error = "";
		if($("#my_full_name").val() == "") {
			error += "Please enter your full name.<br>";
		}
		if($("#my_email").val() == "") {
			error += "Please enter your email address.<br>";
		}
		if($("#my_username").val() == "") {
			error += "Please enter your user name.<br>";
		}
		if($("#my_phone").val() == "") {
			error += "Please enter your phone number.<br>";
		}
		if(error == "") {
			$("#personal_info_form").submit();
		} else {
			$("#personal_info_form p.error").html(error);
		}
	});

	//toggle password show/hide
	$(".profile-content").on("click", ".toggle-password", function(e){
		if($(this).hasClass("fa-eye")) {
			$(this).removeClass("fa-eye").addClass("fa-eye-slash");
			$("#"+$(this).attr("toggle")).attr("type", "text");
		} else {
			$(this).removeClass("fa-eye-slash").addClass("fa-eye");
			$("#"+$(this).attr("toggle")).attr("type", "password");
		}
	});

	//change_password_form submission
	$(".profile-content").on("click", "#change_password_btn", function(e){
		e.preventDefault();
		var error = "";
		if($("#my_password").val() == "") {
			error += "Please enter your new password.<br>";
		}
		if($("#my_password_confirm").val() == "" || $("#my_password").val() != $("#my_password_confirm").val()) {
			error += "Please confirm your password.<br>";
		}
		if(error == "") {
			$("#change_password_form").submit();
		} else {
			$("#change_password_form p.error").html(error);
		}
	});	

	//generate_password_form submission
	$(".profile-content").on("click", "#generate_password_btn", function(e){
		e.preventDefault();
		$("#generate_password_form").submit();
	});

	//my_entity_form submission
	$(".profile-content").on("click", "#my_entity_btn", function(e){
		e.preventDefault();
		var error = "";
		if($("#entity_name").val() == "") {
			error += "Please enter your entity name.<br>";
		}
		if($("#entity_description").val() == "") {
			error += "Please describe your entity.<br>";
		}
		if(error == "") {
			$("#my_entity_form").submit();
		} else {
			$("#my_entity_form p.error").html(error);
		}
	});

	//invite_user_form submission
	$(".profile-content").on("click", "#invite_user_btn", function(e){
		e.preventDefault();
		var error = "";
		if($("#user_full_name").val() == "") {
			error += "Please enter the full name of this new user.<br>";
		}
		if($("#user_email").val() == "") {
			error += "Please enter the email address of this new user.<br>";
		}
		if(error == "") {
			$("#invite_user_form").submit();
		} else {
			$("#invite_user_form p.error").html(error);
		}
	});

	//toggle my activity tabs
	$(".profile-content").on("click", ".btn-activity", function(e){
		$(".tab-panel-header .actions button.btn-activity").removeClass("active");
		$(this).addClass("active");
		var active_div = $(this).attr("toggle");
		$(".profile-content .activity-div").css("display", "none");
		$("#" + active_div).css("display", "block");
	});

	//DataTable for my activity tables
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	$(".data-table").each(function(i, ele){
		var _table_name = $(ele).attr("data-table-name");
		$(ele).DataTable({
			dom: 'Bfrtip',
		    pageLength: 20,
		    sPaginationType: "numbers",
		    order: [[0, 'desc']],
		    responsive: true,
		    buttons: [
	    		{
		            extend: 'csvHtml5',
		            text: 'Save as CSV',
		            filename: _table_name,
		            title: _table_name,
		            className: 'bb_button bb_small bb_green',
		            exportOptions: {
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
	            	className: 'bb_button bb_small bb_green',
	            	exportOptions: {
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
	        ]
		});
	})
	
	//DataTable for other users assigned to the same entity
	$("#other_users_table").DataTable({
		dom: 'frtip',
	    pageLength: 10,
	    sPaginationType: "numbers",
	    order: [[0, 'asc']],
	    responsive: true
	});

	//resend the invitation to users
	$("#other_users_table").on("click", ".resend-invitation", function(e){
		var user_id = $(this).parent().parent().attr("user-id");
		$.ajax({
			type: "POST",
			async: true,
			url: "/auditprotocol/resend_invitations.php",
			data: {
					action: "resend-invitation",
					user_type: "user",
					user_id: user_id
				},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$("#dialog-warning").dialog({title: 'Resend the invitation to the user'});
				$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){ 
				if(response_data == 'success'){
					window.location.replace('my_account.php?active_tab=my_entity');
				} else {
					$("#dialog-warning").dialog({title: 'Resend the invitation to the user'});
					$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});

	//unvite users
	$('#other_users_table').on("click", ".action-delete", function(e){
		e.preventDefault();
		delete_target_id = $(this).attr('data-user-id');
		delete_target_email = $(this).attr('data-user-email');
		$("#dialog-uninvite-message").dialog('open');
	});

	//dialog box to confirm user deletion
	$("#dialog-uninvite-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
			{
				text: 'Yes. UnInvite',
				id: 'btn-welcome-message-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					window.location = 'my_account.php?delete=111&invited_user_id='+delete_target_id;
				}
			},
			{
				text: 'Cancel',
				id: 'btn-welcome-message-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});

	//signature settings
	$('.digital-signature-settings').on("change", ".signature_type", function(e){
		$(".d-sign").css("display", "none");
		$(`#${e.target.value}-d-sign`).css("display", "block");
	});

	$('.digital-signature-settings').on("change", "#image-d-sign-file", function(e){
		if(e.target.files && e.target.files[0]) {
			var reader = new FileReader();

			reader.onload = function (event) {
				$('#image-d-sign-preview').attr('src', event.target.result);
				$('#signature_file_data').val(event.target.result);
			};

			reader.readAsDataURL(e.target.files[0]);
		}
	})

	var sigpad_options = {
		drawOnly : true,
		displayOnly: false,
		bgColour: '#fff',
		penColour: '#000',
		output: '#signature_data',
		clear: '.la_sigpad_clear',
		lineTop: 110,
		lineMargin: 10,
		validateFields: false
	};
	var sigpad_data = $("#signature_data").val();
	$('#draw-d-sign').signaturePad(sigpad_options).regenerate(sigpad_data);
});