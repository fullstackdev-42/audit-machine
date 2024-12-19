function activate_tab(active_tab) {
	$("#tabs li.tab-item").removeClass("active");
	$("#tabs li." + active_tab + "_tab").addClass("active");
	$(".profile-content .tab-panel").css("display", "none");
	$("#" + active_tab).css("display", "block");
}

$(document).ready(function() {
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
		cropUrl:'save_avatar.php',
		modal: true,
		cropData:{
			"is_admin": 1,
			"user_id": user_id,
			"mode": "my_profile"
		},
		onAfterImgCrop:	function(){location.reload(true);},
		onError: function(errormsg){console.log(errormsg);/*location.reload(true);*/}
	}
	
	var cropperHeader = new Croppic('profile_image_upload', cropperOptions);

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

	//reset MFA
	$(".profile-content").on("click", "#reset_mfa_btn", function(e){
		e.preventDefault();
		$("#reset_mfa_form").submit();
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
	});
	var entity_able = $("#entity_table").DataTable({
		dom: 'rftip',
		pageLength: 10,
		sPaginationType: "numbers",
		order: [[0, 'asc']]
	});
});