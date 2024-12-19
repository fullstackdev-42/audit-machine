function activate_tab(active_tab) {
	var tab_index = 0;
	if(active_tab == "examiner_tab") {
		tab_index = 1;
	} else if(active_tab == "entity_tab") {
		tab_index = 2;
	} else if(active_tab == "user_tab") {
		tab_index = 3;
	}
	$( "#tabs" ).tabs({ active: tab_index });
}

$(document).ready(function() {
	var active_tab = $("#table_settings #active_tab").val();
	activate_tab(active_tab);

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
	
	//initiate dataTables
	var add_admin_flag = $("#table_settings #add_admin_flag").val();
	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	var admin_table = $("#admin_table").DataTable({
		dom: 'rf<"add-admin-btn">tip',
		pageLength: 10,
		sPaginationType: "numbers",
		order: [[0, 'asc']],
		scrollX: true
	});
	var examiner_table = $("#examiner_table").DataTable({
		dom: 'rf<"add-examiner-btn">tip',
		pageLength: 10,
		sPaginationType: "numbers",
		order: [[0, 'asc']],
		scrollX: true
	});
	var entity_able = $("#entity_table").DataTable({
		dom: 'rf<"add-entity-btn">tip',
		pageLength: 10,
		sPaginationType: "numbers",
		order: [[0, 'asc']],
		scrollX: true
	});
	var user_table = $("#user_table").DataTable({
		dom: 'rf<"add-user-btn">tip',
		pageLength: 10,
		sPaginationType: "numbers",
		order: [[0, 'asc']],
		scrollX: true
	});

	$("#tabs").on("click", ".custom-tabs-tab", function(e) {
		$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust().draw();
	});
	/*start managing admin table*/
	if(add_admin_flag == 1) {
		$("div.add-admin-btn").html(
			'<a href="add_user.php" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Add_user.png"> Create Admininistrative User</a>'
		);
	}
	//go to admin details page
	$("#admin_table").on("click", ".action-view", function(e){
		var admin_id = $(this).parent().attr("admin-id");
		window.location.href = "view_user.php?id="+admin_id;
	});

	//resend the invitation to admins
	$("#admin_table").on("click", ".resend-invitation", function(e){
		var admin_id = $(this).parent().parent().attr("admin-id");
		$.ajax({
			type: "POST",
			async: true,
			url: "resend_invitations.php",
			data: {
					action: "resend-invitation",
					user_type: "admin",
					user_id: admin_id
				},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$("#dialog-warning").dialog({title: 'Resend the invitation to the admin'});
				$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){
				if(response_data == 'success'){
					window.location.replace('manage_users.php');
				} else {
					$("#dialog-warning").dialog({title: 'Resend the invitation to the admin'});
					$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});

	/*start admin deletion*/
	$("#admin_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var admin_id = $(this).parent().parent().attr("admin-id");
		$("#admin_delete_id").val(admin_id);
		$("#dialog-confirm-admin-delete").dialog('open');
	});

	//dialog box to confirm admin deletion
	$("#dialog-confirm-admin-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-admin-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete selected admin',
			id: 'btn-confirm-admin-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-admin-delete-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-admin-delete-cancel").hide();
				$("#btn-confirm-admin-delete-ok").text('Deleting...');
				$("#btn-confirm-admin-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var admin_delete_id = $("#dialog-confirm-admin-delete #admin_delete_id").val();
				var current_admin = [{ name : "entry_" + admin_delete_id, value : "1"}];
					
				//do the ajax call to delete admin
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'delete',
						  	user_type: 'admin',
						  	selected_users: current_admin
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Admin Deletion'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-admin-delete").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){ 
					   	if(response_data.status == 'ok'){
					  	 	if(response_data.user_id != '0' && response_data.user_id != ''){
						   		window.location.replace('manage_users.php?active_tab=admin_tab');
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
			id: 'btn-confirm-admin-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end admin deletion*/

	/*start admin suspension*/
	$("#admin_table").on("click", ".action-suspend", function(e){
		e.preventDefault();
		var admin_id = $(this).parent().parent().attr("admin-id");
		$("#admin_suspend_id").val(admin_id);
		$("#dialog-confirm-admin-suspend").dialog('open');
	});

	//dialog box to confirm admin suspension
	$("#dialog-confirm-admin-suspend").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-admin-suspend-ok").blur();
		},
		buttons: [{
			text: 'Yes. Suspend selected admin',
			id: 'btn-confirm-admin-suspend-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the suspend button while processing
				$("#btn-confirm-admin-suspend-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-admin-suspend-cancel").hide();
				$("#btn-confirm-admin-suspend-ok").text('Suspending...');
				$("#btn-confirm-admin-suspend-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var admin_suspend_id = $("#dialog-confirm-admin-suspend #admin_suspend_id").val();
				var current_admin = [{ name : "entry_" + admin_suspend_id, value : "1"}];
				//do the ajax call to suspend admin
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'suspend',
						  	user_type: 'admin',
						  	selected_users: current_admin
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Admin Suspension'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-admin-suspend").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){								   
					   	if(response_data.user_id != '0' && response_data.user_id != ''){
					   		window.location.replace('manage_users.php?active_tab=admin_tab');
					  	}else{
					   		window.location.replace('manage_users.php');
					   	}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-admin-suspend-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end admin suspension*/

	/*start admin unblock*/
	$("#admin_table").on("click", ".action-unblock", function(e){
		e.preventDefault();
		var admin_id = $(this).parent().parent().attr("admin-id");
		$("#admin_unblock_id").val(admin_id);
		$("#dialog-confirm-admin-unblock").dialog('open');
	});

	//dialog box to confirm admin unblock
	$("#dialog-confirm-admin-unblock").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-admin-unblock-ok").blur();
		},
		buttons: [{
			text: 'Yes. Unblock selected admin',
			id: 'btn-confirm-admin-unblock-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the unblock button while processing
				$("#btn-confirm-admin-unblock-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-admin-unblock-cancel").hide();
				$("#btn-confirm-admin-unblock-ok").text('Unblocking...');
				$("#btn-confirm-admin-unblock-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var admin_unblock_id = $("#dialog-confirm-admin-unblock #admin_unblock_id").val();
				var current_admin = [{ name : "entry_" + admin_unblock_id, value : "1"}];
				//do the ajax call to unblock admin
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'unsuspend',
						  	user_type: 'admin',
						  	selected_users: current_admin
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Admin Unblock'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-admin-unblock").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){								   
					   	if(response_data.user_id != '0' && response_data.user_id != ''){
					   		window.location.replace('manage_users.php?active_tab=admin_tab');
					  	}else{
					   		window.location.replace('manage_users.php');
					   	}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-admin-unblock-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end admin unblock*/
	/*end managing admin table*/

	/*start managing examiner table*/
	$("div.add-examiner-btn").html(
		'<a href="add_examiner.php" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Add_user.png"> Create Examiner User</a>'
	);
	//go to examiner details page
	$("#examiner_table").on("click", ".action-view", function(e){
		var examiner_id = $(this).parent().attr("examiner-id");
		window.location.href = "view_user.php?id="+examiner_id;
	});

	//resend the invitation to examiners
	$("#examiner_table").on("click", ".resend-invitation", function(e){
		var examiner_id = $(this).parent().parent().attr("examiner-id");
		$.ajax({
			type: "POST",
			async: true,
			url: "resend_invitations.php",
			data: {
					action: "resend-invitation",
					user_type: "examiner",
					user_id: examiner_id
				},
			cache: false,
			global: false,
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$("#dialog-warning").dialog({title: 'Resend the invitation to the examiner'});
				$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){
				if(response_data == 'success'){
					window.location.replace('manage_users.php?active_tab=examiner_tab');
				} else {
					$("#dialog-warning").dialog({title: 'Resend the invitation to the examiner'});
					$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});

	/*start examiner deletion*/
	$("#examiner_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var examiner_id = $(this).parent().parent().attr("examiner-id");
		$("#examiner_delete_id").val(examiner_id);
		$("#dialog-confirm-examiner-delete").dialog('open');
	});

	//dialog box to confirm examiner deletion
	$("#dialog-confirm-examiner-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-examiner-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete selected examiner',
			id: 'btn-confirm-examiner-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-examiner-delete-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-examiner-delete-cancel").hide();
				$("#btn-confirm-examiner-delete-ok").text('Deleting...');
				$("#btn-confirm-examiner-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var examiner_delete_id = $("#dialog-confirm-examiner-delete #examiner_delete_id").val();
				var current_examiner = [{ name : "entry_" + examiner_delete_id, value : "1"}];
					
				//do the ajax call to delete examiner
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'delete',
						  	user_type: 'examiner',
						  	selected_users: current_examiner
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Examiner Deletion'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-examiner-delete").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){ 
					   	if(response_data.status == 'ok'){
					  	 	if(response_data.user_id != '0' && response_data.user_id != ''){
						   		window.location.replace('manage_users.php?active_tab=examiner_tab');
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
			id: 'btn-confirm-examiner-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end examiner deletion*/

	/*start examiner suspension*/
	$("#examiner_table").on("click", ".action-suspend", function(e){
		e.preventDefault();
		var examiner_id = $(this).parent().parent().attr("examiner-id");
		$("#examiner_suspend_id").val(examiner_id);
		$("#dialog-confirm-examiner-suspend").dialog('open');
	});

	//dialog box to confirm examiner suspension
	$("#dialog-confirm-examiner-suspend").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-examiner-suspend-ok").blur();
		},
		buttons: [{
			text: 'Yes. Suspend selected examiner',
			id: 'btn-confirm-examiner-suspend-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the suspend button while processing
				$("#btn-confirm-examiner-suspend-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-examiner-suspend-cancel").hide();
				$("#btn-confirm-examiner-suspend-ok").text('Suspending...');
				$("#btn-confirm-examiner-suspend-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var examiner_suspend_id = $("#dialog-confirm-examiner-suspend #examiner_suspend_id").val();
				var current_examiner = [{ name : "entry_" + examiner_suspend_id, value : "1"}];
				//do the ajax call to suspend examiner
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'suspend',
						  	user_type: 'examiner',
						  	selected_users: current_examiner
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Examiner Suspension'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-examiner-suspend").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){								   
					   	if(response_data.user_id != '0' && response_data.user_id != ''){
					   		window.location.replace('manage_users.php?active_tab=examiner_tab');
					  	}else{
					   		window.location.replace('manage_users.php');
					   	}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-examiner-suspend-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end examiner suspension*/

	/*start examiner unblock*/
	$("#examiner_table").on("click", ".action-unblock", function(e){
		e.preventDefault();
		var examiner_id = $(this).parent().parent().attr("examiner-id");
		$("#examiner_unblock_id").val(examiner_id);
		$("#dialog-confirm-examiner-unblock").dialog('open');
	});

	//dialog box to confirm examiner unblock
	$("#dialog-confirm-examiner-unblock").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-examiner-unblock-ok").blur();
		},
		buttons: [{
			text: 'Yes. Unblock selected examiner',
			id: 'btn-confirm-examiner-unblock-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the unblock button while processing
				$("#btn-confirm-examiner-unblock-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-examiner-unblock-cancel").hide();
				$("#btn-confirm-examiner-unblock-ok").text('Unblocking...');
				$("#btn-confirm-examiner-unblock-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var examiner_unblock_id = $("#dialog-confirm-examiner-unblock #examiner_unblock_id").val();
				var current_examiner = [{ name : "entry_" + examiner_unblock_id, value : "1"}];
				//do the ajax call to unblock examiner
				$.ajax({
					type: "POST",
					async: true,
					url: "change_user_status.php",
					data:
						{
						  	action: 'unsuspend',
						  	user_type: 'examiner',
						  	selected_users: current_examiner
						},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#dialog-warning").dialog({title: 'Examiner Unblock'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-examiner-unblock").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){								   
					   	if(response_data.user_id != '0' && response_data.user_id != ''){
					   		window.location.replace('manage_users.php?active_tab=examiner_tab');
					  	}else{
					   		window.location.replace('manage_users.php');
					   	}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-examiner-unblock-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end examiner unblock*/
	/*end managing examiner table*/

	/*start managing entity table*/
	$("div.add-entity-btn").html(
		'<a href="add_entity.php" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Add_user.png"> Create Entity</a>'
   	);
   	//go to entity details page
	$("#entity_table").on("click", ".action-view", function(e){
		var entity_id = $(this).parent().attr("entity-id");
		window.location.href = "edit_entity.php?entity_id="+entity_id;
	});
   	/*start entity deletion*/
   	$("#entity_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var entity_id = $(this).parent().parent().attr("entity-id");
		$("#entity_delete_id").val(entity_id);
		$("#dialog-confirm-entity-delete").dialog('open');
	});
	//dialog box to confirm entity deletion
	$("#dialog-confirm-entity-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-entity-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete selected entity',
			id: 'btn-confirm-entity-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-entity-delete-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-entity-delete-cancel").hide();
				$("#btn-confirm-entity-delete-ok").text('Deleting...');
				$("#btn-confirm-entity-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var entity_delete_id = $("#dialog-confirm-entity-delete #entity_delete_id").val();
				var current_entity = [{name:"col_select_portal", value : entity_delete_id}];
				//do the ajax call to delete entity
				$.ajax({
					type: "POST",
					async: true,
					url: "delete-entity.php",
					data: {
						action: 'delete',
						selected_users: current_entity
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#dialog-warning").dialog({title: 'Entity Deletion'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-entity-delete").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){					   
						if(!(response_data.user_exists_in_entity).length){
							window.location.replace('manage_users.php?active_tab=entity_tab');
						} else {
							var entityName = new Array();
							for(l=0; l<(response_data.user_exists_in_entity).length; l++){
								entityName.push(response_data.user_exists_in_entity[l]['company_name']);
							}							
							$("#dialog-warning").dialog({title: 'Entity Deletion'});
							if(entityName.length > 1){
								$("#dialog-warning-msg").html("Entities " + entityName.join(", ") + " could not be deleted because it contains users in it.");
							}else{
								$("#dialog-warning-msg").html("Entity " + entityName.join(" ") + " could not be deleted because it contains users in it.");
							}							
							$("#dialog-confirm-entity-delete").dialog('close');
							$("#dialog-warning").dialog('open');
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-entity-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end entity deletion*/
	/*end managing entity table*/

	/*start managing user table*/	
	$("div.add-user-btn").html(
		'<a href="add_portal_user.php" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Add_user.png"> Create User</a>'
   	);
   	//go to user details page
	$("#user_table").on("click", ".action-view", function(e){
		var user_id = $(this).parent().attr("user-id");
		window.location.href = "view_portal_user.php?user_id="+user_id;
	});

	//resend the invitation to users
	$("#user_table").on("click", ".resend-invitation", function(e){
		var user_id = $(this).parent().parent().attr("user-id");
		$.ajax({
			type: "POST",
			async: true,
			url: "resend_invitations.php",
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
					window.location.replace('manage_users.php?active_tab=user_tab');
				} else {
					$("#dialog-warning").dialog({title: 'Resend the invitation to the user'});
					$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});

	/*start user suspension*/
	$("#user_table").on("click", ".action-suspend", function(e){
		e.preventDefault();
		var user_id = $(this).parent().parent().attr("user-id");
		$("#user_suspend_id").val(user_id);
		$("#dialog-confirm-user-suspend").dialog('open');
	});

	//dialog box to confirm user suspension
	$("#dialog-confirm-user-suspend").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-user-suspend-ok").blur();
		},
		buttons: [{
			text: 'Yes. Suspend selected user',
			id: 'btn-confirm-user-suspend-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the suspend button while processing
				$("#btn-confirm-user-suspend-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-user-suspend-cancel").hide();
				$("#btn-confirm-user-suspend-ok").text('Suspending...');
				$("#btn-confirm-user-suspend-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var user_suspend_id = $("#dialog-confirm-user-suspend #user_suspend_id").val();
				//do the ajax call to suspend user
				$.ajax({
					type: "POST",
					url: "user-ajax-call-portal.php",
					data: {mode: "suspend", user_id: user_suspend_id, called_from:"manage_users"},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#dialog-warning").dialog({title: 'User Suspension'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-user-suspend").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response){
						if(response.status != 0){
							$("#dialog-warning").dialog({title: 'User Suspension'});
						   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
						   	$("#dialog-confirm-user-suspend").dialog('close');
							$("#dialog-warning").dialog('open');
						}else{
							window.location.replace('manage_users.php?active_tab=user_tab');
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-user-suspend-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end user suspension*/

	/*start user unblock*/
	$("#user_table").on("click", ".action-unblock", function(e){
		e.preventDefault();
		var user_id = $(this).parent().parent().attr("user-id");
		$("#user_unblock_id").val(user_id);
		$("#dialog-confirm-user-unblock").dialog('open');
	});

	//dialog box to confirm user unblock
	$("#dialog-confirm-user-unblock").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-user-unblock-ok").blur();
		},
		buttons: [{
			text: 'Yes. Unblock selected user',
			id: 'btn-confirm-user-unblock-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the unblock button while processing
				$("#btn-confirm-user-unblock-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-user-unblock-cancel").hide();
				$("#btn-confirm-user-unblock-ok").text('Unblocking...');
				$("#btn-confirm-user-unblock-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var user_unblock_id = $("#dialog-confirm-user-unblock #user_unblock_id").val();
				//do the ajax call to unblock user
				$.ajax({
					type: "POST",
					url: "user-ajax-call-portal.php",
					data: {mode: "unblock", user_id: user_unblock_id, called_from:"manage_users"},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#dialog-warning").dialog({title: 'User Unblock'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-user-unblock").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response){
						if(response.status != 0){
							$("#dialog-warning").dialog({title: 'User Unblock'});
						   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
						   	$("#dialog-confirm-user-unblock").dialog('close');
							$("#dialog-warning").dialog('open');
						}else{
							window.location.replace('manage_users.php?active_tab=user_tab');
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-user-unblock-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end user unblock*/

	/*start user deletion*/
	$("#user_table").on("click", ".action-delete", function(e){
		e.preventDefault();
		var user_id = $(this).parent().parent().attr("user-id");
		$("#user_delete_id").val(user_id);
		$("#dialog-confirm-user-delete").dialog('open');
	});

	//dialog box to confirm user delete
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
			text: 'Yes. Delete selected user',
			id: 'btn-confirm-user-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-user-delete-ok").prop("disabled",true);
				
				//display loader image
				$("#btn-confirm-user-delete-cancel").hide();
				$("#btn-confirm-user-delete-ok").text('Deleteing...');
				$("#btn-confirm-user-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var user_delete_id = $("#dialog-confirm-user-delete #user_delete_id").val();
				//do the ajax call to delete user
				$.ajax({
					type: "POST",
					url: "user-ajax-call-portal.php",
					data: {mode: "deleted", user_id: user_delete_id, called_from:"manage_users"},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#dialog-warning").dialog({title: 'User delete'});
					   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
					   	$("#dialog-confirm-user-delete").dialog('close');
						$("#dialog-warning").dialog('open');
					},
					success: function(response){
						if(response.status != 2){
							$("#dialog-warning").dialog({title: 'User delete'});
						   	$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
						   	$("#dialog-confirm-user-delete").dialog('close');
							$("#dialog-warning").dialog('open');
						} else {
							window.location.replace('manage_users.php?active_tab=user_tab');
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-user-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	/*end user deletion*/
	/*end managing user table*/

	//display the content after loading the page
	$("#loader-img").hide();
	$("#tabs").show();
	$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust().draw();
})