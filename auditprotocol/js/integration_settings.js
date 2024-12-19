$(function() {
	//dialog box to display all warnings or errors
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

	//we're using jquery tools for the tooltip
	$(".helpmsg").tooltip({
		position: "bottom center",
		offset: [10, 20],
		effect: "fade",
		opacity: 0.8,		
		events: {
			def: 'click,mouseout'
		}		
	});

	function isUrl(s) {
		var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
   		return regexp.test(s);
	}

	//attach event to show/hide service container
	$(".service-item-logo").click(function() {
		var sevice_content = $(this).parent().children(".service-item-content")[0];
		if($(sevice_content).css("display") == "none") {
			$(".service-item-content").slideUp();
			$(sevice_content).slideDown();
		} else {
			$(sevice_content).slideUp();
		}
	});

	//Start Of Chatbot Integration
	//dialog box to confirm disabling the chatstack integration settings
	$("#dialog-confirm-disable-chatbot").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$("#dialog-confirm-disable-chatbot-ok").blur();
		},
		buttons: [{
			text: 'Disable',
			id: 'btn-disable-chatbot-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the disable button while processing
				$("#btn-disable-chatbot-ok").prop("disabled",true);
				//display loader image
				$("#btn-disable-chatbot-cancel").hide();
				$("#btn-disable-chatbot-ok").text('Disabling...');
				$("#btn-disable-chatbot-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "disable_chatbot_settings";
				//do the ajax call to disable the chatbot integration settings
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#btn-disable-chatbot-ok").html('Disable');
						$("#btn-disable-chatbot-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to disable the ChatStack integration settings. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
				   	success: function(response_data){
						$("#btn-disable-chatbot-ok").html('Disable');
						$("#btn-disable-chatbot-ok").next().remove();
						if(response_data.status == 'ok'){
						   	window.location.reload();
						}else{
							$("#dialog-warning-msg").html("Unable to disable the ChatStack integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
				   	}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-disable-chatbot-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	function checkChatBotErrors() {
		var error = "";
		if($("#chatstack_domain").val() == "") {
			error = "Please enter a ChatStack Domain!";
		} else if($("#chatstack_id").val() == "") {
			error = "Please enter a ChatStack ID!";
		}
		if(error != "") {
			$(".chatbot-error").text(error);
			return true;
		} else {
			$(".chatbot-error").text("");
			return false;
		}
	}

	//check all chatbot errors
	async function all_chatbot_errors() {
		let promise_chatbot = new Promise((resolve, reject) => {
			var chatbot_error = checkChatBotErrors();
			if(chatbot_error) {
				resolve(true);
			} else {
				resolve(false);
			}
		});
		
		let result_chatbot = await promise_chatbot;
		return result_chatbot;
	}

	//attach event to disable the ChatStack integration settings
	$("#disable_chatbot_btn i").click(function() {
		$("#dialog-confirm-disable-chatbot").dialog("open");
	});

	//attach event to save ChatStack integration settings
	$("#save_chatstack_btn").click(function(e) {
		e.preventDefault();
		//check errors
		if($("#save_chatstack_btn").text().trim() != 'Saving...'){
			$("#save_chatstack_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Saving...');
			$("#save_chatstack_btn").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");

			all_chatbot_errors().then(error => {
				if(!error){
					//generate posting data
					var data = {};
					data["form_id"] = $("#form_id").val();
					data["action_type"] = "save_chatbot_settings";
					data["chatbot"] = {"chat_bot_enable": true, "chat_bot_type": "chatstack", "chat_bot_domain": $.trim($("#chatstack_domain").val()), "chat_bot_id": $.trim($("#chatstack_id").val())};
					//do the ajax call to save the settings
					$.ajax({
						type: "POST",
						async: true,
						url: "manage_integration_settings.php",
						data: data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	//error, display the generic error message
						   	$("#save_chatstack_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_chatstack_btn").next().remove();
							$("#dialog-warning-msg").html("Unable to save ChatStack integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						},
					   	success: function(response_data){
							$("#save_chatstack_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_chatstack_btn").next().remove();
							if(response_data.status == 'ok'){
							   	window.location.reload();
							}else{
								$("#dialog-warning-msg").html("Unable to save ChatStack integration settings. Please try again later.");
								$("#dialog-warning").dialog("open");
							}
					   	}
					});
				} else {
					$("#save_chatstack_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
					$("#save_chatstack_btn").next().remove();
				}
			});
		}
	});
	//End Of Chatbot Integration

	//Start Of SAINT Integration
	//dialog box to confirm a single SAINT config deletion
	$("#dialog-remove-saint-config").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-remove-saint-config-ok").blur();
		},
		buttons: [{
			text: 'Delete',
			id: 'btn-remove-saint-config-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {				
				//disable the delete button while processing
				$("#btn-remove-saint-config-ok").prop("disabled",true);				
				//display loader image
				$("#btn-remove-saint-config-cancel").hide();
				$("#btn-remove-saint-config-ok").text('Deleting...');
				$("#btn-remove-saint-config-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "delete_saint_settings";
				data["saint_id"] = $("#remove_saint_id").val();
				$.ajax({
					type: "POST",
					async: false,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#btn-remove-saint-config-ok").html('Delete');
						$("#btn-remove-saint-config-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to delete the SAINT API configuration. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
					success: function(response_data){
						if(response_data.status == 'ok'){
							window.location.reload();
						} else {
							$("#btn-remove-saint-config-ok").html('Delete');
							$("#btn-remove-saint-config-ok").next().remove();
							$("#dialog-warning-msg").html("Unable to delete the SAINT API configuration. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-remove-saint-config-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//dialog box to confirm disabling the SAINT integration settings
	$("#dialog-confirm-disable-saint").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$("#dialog-confirm-disable-saint-ok").blur();
		},
		buttons: [{
			text: 'Disable',
			id: 'btn-disable-saint-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the disable button while processing
				$("#btn-disable-saint-ok").prop("disabled",true);				
				//display loader image
				$("#btn-disable-saint-cancel").hide();
				$("#btn-disable-saint-ok").text('Disabling...');
				$("#btn-disable-saint-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "disable_saint_settings";
				//do the ajax call to disable the saint integration settings
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#btn-disable-saint-ok").html('Disable');
						$("#btn-disable-saint-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to disable the SAINT integration settings. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
				   	success: function(response_data){
						$("#btn-disable-saint-ok").html('Disable');
						$("#btn-disable-saint-ok").next().remove();
						if(response_data.status == 'ok'){
						   	window.location.reload();
						}else{
							$("#dialog-warning-msg").html("Unable to disable the SAINT integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
				   	}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-disable-saint-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	async function checkSaintErrors(saint_config_dom) {
		var api_config = $(saint_config_dom).find(".api-config");
		$(api_config).find(".saint-notification").text("").hide();
		$(api_config).find(".saint-error").text("").hide();

		if($(api_config).find(".saint-api-test").text().trim() != 'Testing...'){
			$(api_config).find(".saint-api-test").text("Testing...");
			$(api_config).find(".saint-api-test").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");
			var saint_url = $(api_config).find(".saint-url").val();
			var saint_port = $(api_config).find(".saint-port").val();
			var saint_api_token = $(api_config).find(".saint-api-token").val();
			var saint_job_id = $(api_config).find(".saint-job-id").val();
			var saint_ssl_enable = $(api_config).find(".saint-ssl-enable").prop("checked") ? 1 : 0;

			let promise = new Promise((resolve, reject) => {
				if(saint_url == "") {
					resolve("Please enter a SAINT web server URL!");
				} else if(!isUrl(saint_url)){
					resolve("Please enter a valid SAINT web server URL!");
				} else if(saint_port == "") {
					resolve("Please enter a SAINT API port!");
				} else if(saint_api_token == "") {
					resolve("Please enter a SAINT API token!");
				} else if(saint_job_id == "") {
					resolve("Please enter SAINT a job ID!");
				} else if(isNaN(saint_port)){
					resolve("The SAINT API port should be a number!");
				} else if(isNaN(saint_job_id)) {
					resolve("The SAINT job ID should be a number!");
				} else if((saint_ssl_enable == 1) && (saint_url.toLowerCase().includes("http:"))) {
					resolve("The SAINT web server URL should be 'https:' or you should disable TLS/SSL.");
				} else {
					//do the ajax call to check if this API config is valid or not
					var data = {};
					data["form_id"] = $("#form_id").val();
					data["saint_url"] = saint_url;
					data["saint_port"] = saint_port;
					data["saint_api_token"] = saint_api_token;
					data["saint_job_id"] = saint_job_id;
					data["saint_ssl_enable"] = saint_ssl_enable;
					data["action_type"] = "test_saint_settings";
					
					$.ajax({
						type: "POST",
						async: true,
						url: "manage_integration_settings.php",
						data: data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	resolve("Unable to import the report data.");
						},
					   	success: function(response_data){
					   		if(response_data.status == "ok"){
					   			$(api_config).find(".saint-notification").text(response_data.msg).show();
					   			resolve("");
					   		} else if(response_data.status == "error") {
					   			resolve(response_data.msg);
					   		}
					   	}
					});
				}
			});

			let saint_error = await promise;

			$(api_config).find(".saint-api-test").text("Test this configuration");
			$(api_config).find(".saint-api-test").next().remove();
			if(saint_error != "") {
				$(api_config).find(".saint-notification").text("").hide();
				$(api_config).find(".saint-error").text(saint_error).show();
				return true;
			} else {
				$(api_config).find(".saint-error").text("").hide();
				return false;
			}
		}
	}

	function addNewSaintSettings() {
		var saint_settings = $(".saint-settings:first");
		$(saint_settings).clone(true, true).insertBefore(".add-new-saint");
		saint_settings = $(".saint-settings:last");
		//format SAINT integration settings of the clone
		$(saint_settings).find(".saint-notification").text("").hide();
		$(saint_settings).find(".saint-error").text("").hide();
		$(saint_settings).attr("saint-id", "0");
		$(saint_settings).find(".saint-url").val("");
		$(saint_settings).find(".saint-port").val("");
		$(saint_settings).find(".saint-api-token").val("");
		$(saint_settings).find(".saint-job-id").val("");
		$(saint_settings).find(".saint-ssl-enable").prop("checked", false);
		$(saint_settings).find(".assignees option:selected").prop("selected", false);
		$(saint_settings).find(".frequency").val("");
		$(saint_settings).find(".remove-saint").show();
		$(saint_settings).insertBefore(".add-new-saint");
	}

	//attach event to test SAINT api configurations
	$(".saint-api-test").click(function(e) {
		e.preventDefault();
		checkSaintErrors($(this).parent().parent().parent());
	});

	//attach event to add new SAINT integration settings
	$(".add-new-saint-btn").click(function(e) {
		e.preventDefault();
		addNewSaintSettings();
	});

	//attach event to remove saint integration settings
	$(".remove-saint-btn").on("click", function(e) {
		e.preventDefault();
		var saint_settings = $(this).closest(".saint-settings");
		if($(saint_settings).attr("saint-id") != "0") {
			$("#remove_saint_id").val($(saint_settings).attr("saint-id"));
			$("#dialog-remove-saint-config").dialog("open");
		} else {
			$(this).closest(".saint-settings").remove();
		}
	});

	//attach event to disable the SAINT integration settings
	$("#disable_saint_btn i").click(function() {
		$("#dialog-confirm-disable-saint").dialog("open");
	});

	//check all SAINT errors
	async function all_saint_errors() {
		let promise_saint = new Promise((resolve, reject) => {
			$(".saint-settings").each(function(index, element){
				checkSaintErrors($(element)).then(res=>{
					if(res) {
						resolve(true);
					} else {
						if(index == $(".saint-settings").length - 1 ) {
							resolve(false);
						}
					}
				});
			});
		});
		
		let result_saint = await promise_saint;
		return result_saint;
	}

	//attach event to save SAINT integration settings
	$("#save_saint_btn").click(function(e) {
		e.preventDefault();
		//check errors
		if($("#save_saint_btn").text().trim() != 'Saving...'){
			$("#save_saint_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Saving...');
			$("#save_saint_btn").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");

			all_saint_errors().then(error => {
				if(!error){
					//generate posting data
					var data = {};
					data["form_id"] = $("#form_id").val();
					data["action_type"] = "save_saint_settings";
					var saint = [];
					$(".saint-settings").each(function(index, element){
						var saint_ssl_enable = $(element).find(".saint-ssl-enable").prop("checked") ? 1 : 0;
						var entity_id = $(element).find(".assignees option:selected").attr("role") == "entity" ? $(element).find(".assignees option:selected").attr("value") : "0";				
						var saint_id = $(element).attr("saint-id");
						saint.push({
							"saint_url": $(element).find(".saint-url").val(),
							"saint_port": $(element).find(".saint-port").val(),
							"saint_job_id": $(element).find(".saint-job-id").val(),
							"saint_api_token": $(element).find(".saint-api-token").val(),
							"saint_url": $(element).find(".saint-url").val(),
							"saint_ssl_enable": saint_ssl_enable,
							"entity_id": entity_id,
							"frequency": $(element).find(".frequency").val(),
							"saint_id": saint_id
						});				
					});
					data["saint"] = saint;
					//do the ajax call to save the settings
					$.ajax({
						type: "POST",
						async: true,
						url: "manage_integration_settings.php",
						data: data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	//error, display the generic error message
						   	$("#save_saint_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_saint_btn").next().remove();
							$("#dialog-warning-msg").html("Unable to save SAINT integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						},
					   	success: function(response_data){
							$("#save_saint_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_saint_btn").next().remove();
							if(response_data.status == 'ok'){
							   	window.location.reload();
							}else{
								$("#dialog-warning-msg").html("Unable to save SAINT integration settings. Please try again later.");
								$("#dialog-warning").dialog("open");
							}
					   	}
					});
				} else {
					$("#save_saint_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
					$("#save_saint_btn").next().remove();
				}
			});
		}
	});
	//End Of SAINT Integration

	//Start Of Nessus Integration
	//dialog box to confirm a single Nessus config deletion
	$("#dialog-remove-nessus-config").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-remove-nessus-config-ok").blur();
		},
		buttons: [{
			text: 'Delete',
			id: 'btn-remove-nessus-config-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {				
				//disable the delete button while processing
				$("#btn-remove-nessus-config-ok").prop("disabled",true);				
				//display loader image
				$("#btn-remove-nessus-config-cancel").hide();
				$("#btn-remove-nessus-config-ok").text('Deleting...');
				$("#btn-remove-nessus-config-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "delete_nessus_settings";
				data["nessus_id"] = $("#remove_nessus_id").val();
				$.ajax({
					type: "POST",
					async: false,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						$("#btn-remove-nessus-config-ok").html('Delete');
						$("#btn-remove-nessus-config-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to delete the Nessus API configuration. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
					success: function(response_data){
						if(response_data.status == 'ok'){
							window.location.reload();
						} else {
							$("#btn-remove-nessus-config-ok").html('Delete');
							$("#btn-remove-nessus-config-ok").next().remove();
							$("#dialog-warning-msg").html("Unable to delete the Nessus API configuration. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-remove-nessus-config-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//dialog box to confirm disabling the Nessus integration settings
	$("#dialog-confirm-disable-nessus").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$("#dialog-confirm-disable-nessus-ok").blur();
		},
		buttons: [{
			text: 'Disable',
			id: 'btn-disable-nessus-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the disable button while processing
				$("#btn-disable-nessus-ok").prop("disabled",true);				
				//display loader image
				$("#btn-disable-nessus-cancel").hide();
				$("#btn-disable-nessus-ok").text('Disabling...');
				$("#btn-disable-nessus-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "disable_nessus_settings";
				//do the ajax call to disable the nessus integration settings
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#btn-disable-nessus-ok").html('Disable');
						$("#btn-disable-nessus-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to disable the Nessus integration settings. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
				   	success: function(response_data){
						$("#btn-disable-nessus-ok").html('Disable');
						$("#btn-disable-nessus-ok").next().remove();
						if(response_data.status == 'ok'){
						   	window.location.reload();
						}else{
							$("#dialog-warning-msg").html("Unable to disable the Nessus integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
				   	}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-disable-nessus-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	async function checkNessusErrors(nessus_config_dom) {
		var api_config = $(nessus_config_dom).find(".api-config");
		$(api_config).find(".nessus-notification").text("").hide();
		$(api_config).find(".nessus-error").text("").hide();
		
		if($(api_config).find(".nessus-api-test").text().trim() != 'Testing...'){
			$(api_config).find(".nessus-api-test").text("Testing...");
			$(api_config).find(".nessus-api-test").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");
			var nessus_access_key = $(api_config).find(".nessus-access-key").val();
			var nessus_secret_key = $(api_config).find(".nessus-secret-key").val();
			var nessus_scan_name = $(api_config).find(".nessus-scan-name").val();
			
			let promise = new Promise((resolve, reject) => {
				if(nessus_access_key == "") {
					resolve("Please enter a Nessus access key!");
				} else if(nessus_secret_key == "") {
					resolve("Please enter a Nessus secret key!");
				} else if(nessus_scan_name == "") {
					resolve("Please enter a Nessus scan name!");
				} else {
					//do the ajax call to check if this API config is valid or not
					var data = {};
					data["form_id"] = $("#form_id").val();
					data["nessus_access_key"] = nessus_access_key;
					data["nessus_secret_key"] = nessus_secret_key;
					data["nessus_scan_name"] = nessus_scan_name;
					data["action_type"] = "test_nessus_settings";
					
					$.ajax({
						type: "POST",
						async: true,
						url: "manage_integration_settings.php",
						data: data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	resolve("Unable to import the report data.");
						},
					   	success: function(response_data){
					   		if(response_data.status == "ok"){
					   			$(api_config).find(".nessus-notification").text(response_data.msg).show();
					   			resolve("");
					   		} else if(response_data.status == "error") {
					   			resolve(response_data.msg);
					   		}
					   	}
					});
				}
			});

			let nessus_error = await promise;
	   		$(api_config).find(".nessus-api-test").text("Test this configuration");
	   		$(api_config).find(".nessus-api-test").next().remove();
			if(nessus_error != "") {
				$(api_config).find(".nessus-notification").text("").hide();
				$(api_config).find(".nessus-error").text(nessus_error).show();
				return true;
			} else {
				$(api_config).find(".nessus-error").text("").hide();
				return false;
			}
		}
	}

	function addNewNessusSettings() {
		var nessus_settings = $(".nessus-settings:first");
		$(nessus_settings).clone(true, true).insertBefore(".add-new-nessus");
		nessus_settings = $(".nessus-settings:last");
		//format Nessus integration settings of the clone
		$(nessus_settings).find(".nessus-notification").text("").hide();
		$(nessus_settings).find(".nessus-error").text("").hide();
		$(nessus_settings).attr("nessus-id", "0");
		$(nessus_settings).find(".nessus-access-key").val("");
		$(nessus_settings).find(".nessus-secret-key").val("");
		$(nessus_settings).find(".nessus-scan-name").val("");
		$(nessus_settings).find(".assignees option:selected").prop("selected", false);
		$(nessus_settings).find(".frequency").val("");
		$(nessus_settings).find(".remove-nessus").show();
		$(nessus_settings).insertBefore(".add-new-nessus");
	}

	//attach event to test Nessus api configurations
	$(".nessus-api-test").click(function(e) {
		e.preventDefault();
		checkNessusErrors($(this).parent().parent().parent());
	});

	//attach event to add new Nessus integration settings
	$(".add-new-nessus-btn").click(function(e) {
		e.preventDefault();
		addNewNessusSettings();
	});

	//attach event to remove nessus integration settings
	$(".remove-nessus-btn").on("click", function(e) {
		e.preventDefault();
		var nessus_settings = $(this).closest(".nessus-settings");
		if($(nessus_settings).attr("nessus-id") != "0") {
			$("#remove_nessus_id").val($(nessus_settings).attr("nessus-id"));
			$("#dialog-remove-nessus-config").dialog("open");
		} else {
			$(this).closest(".nessus-settings").remove();
		}
	});
	
	//attach event to disable the Nessus integration settings
	$("#disable_nessus_btn i").click(function() {
		$("#dialog-confirm-disable-nessus").dialog("open");
	});

	//check all Nessus errors before saving Nessus integration settings
	async function all_nessus_errors() {
		let promise_nessus = new Promise((resolve, reject) => {
			$(".nessus-settings").each(function(index, element){
				checkNessusErrors($(element)).then(res=>{
					if(res) {
						resolve(true);
					} else {
						if(index == $(".nessus-settings").length - 1 ) {
							resolve(false);
						}
					}
				});
			});
		});
		
		let result_nessus = await promise_nessus;
		return result_nessus;
	}	

	//attach event to save Nessus integration settings
	$("#save_nessus_btn").click(function(e) {
		e.preventDefault();
		//check errors
		if($("#save_nessus_btn").text().trim() != 'Saving...'){
			$("#save_nessus_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Saving...');
			$("#save_nessus_btn").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");

			all_nessus_errors().then(error => {
				if(!error){
					//generate posting data
					var data = {};
					data["form_id"] = $("#form_id").val();
					data["action_type"] = "save_nessus_settings";
					var nessus = [];
					$(".nessus-settings").each(function(index, element){				
						var entity_id = $(element).find(".assignees option:selected").attr("role") == "entity" ? $(element).find(".assignees option:selected").attr("value") : "0";				
						var nessus_id = $(element).attr("nessus-id");
						nessus.push({
							"nessus_access_key": $(element).find(".nessus-access-key").val(),
							"nessus_secret_key": $(element).find(".nessus-secret-key").val(),
							"nessus_scan_name": $(element).find(".nessus-scan-name").val(),
							"entity_id": entity_id,
							"frequency": $(element).find(".frequency").val(),
							"nessus_id": nessus_id
						});				
					});
					data["nessus"] = nessus;
					//do the ajax call to save the settings
					$.ajax({
						type: "POST",
						async: true,
						url: "manage_integration_settings.php",
						data: data,
						cache: false,
						global: false,
						dataType: "json",
						error: function(xhr,text_status,e){
						   	//error, display the generic error message
						   	$("#save_nessus_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_nessus_btn").next().remove();
						   	$("#dialog-warning-msg").html("Unable to save Nessus integration settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						},
					   	success: function(response_data){
							$("#save_nessus_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
							$("#save_nessus_btn").next().remove();
							if(response_data.status == 'ok'){
							   	window.location.reload();
							}else{
								$("#dialog-warning-msg").html("Unable to save Nessus integration settings. Please try again later.");
								$("#dialog-warning").dialog("open");
							}
					   	}
					});
				} else {
					$("#save_nessus_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
					$("#save_nessus_btn").next().remove();
				}
			});
		}
	});
	//End Of Nessus Integration

	//Start Of Migration Wizard
	//dialog box to confirm disabling the Migration Wizard settings
	$("#dialog-confirm-disable-migration-wizard").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 600,
		draggable: false,
		resizable: false,
		open: function(){
			$("#dialog-confirm-disable-migration-wizard-ok").blur();
		},
		buttons: [{
			text: 'Disable',
			id: 'btn-disable-migration-wizard-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the disable button while processing
				$("#btn-disable-migration-wizard-ok").prop("disabled",true);				
				//display loader image
				$("#btn-disable-migration-wizard-cancel").hide();
				$("#btn-disable-migration-wizard-ok").text('Disabling...');
				$("#btn-disable-migration-wizard-ok").after("<div class='dialog_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "disable_migration_wizard_settings";
				//do the ajax call to disable the Migration Wizard settings
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
					   	//error, display the generic error message
					   	$("#btn-disable-migration-wizard-ok").html('Disable');
						$("#btn-disable-migration-wizard-ok").next().remove();
						$("#dialog-warning-msg").html("Unable to delete the Migration Wizard's settings. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
				   	success: function(response_data){
						$("#btn-disable-migration-wizard-ok").html('Disable');
						$("#btn-disable-migration-wizard-ok").next().remove();
						if(response_data.status == 'ok'){
						   	window.location.reload();
						} else {
							$("#dialog-warning-msg").html("Unable to delete the Migration Wizard's settings. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
				   	}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-disable-migration-wizard-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//attach event to select Connector Role to show migration_wizard_generate_authorization_key or not
	$("#migration_wizard_connector_role_sender").click(function() {
		$("#migration_wizard_generate_authorization_key").show();
	});

	$("#migration_wizard_connector_role_receiver").click(function() {
		$("#migration_wizard_generate_authorization_key").hide();
	});

	//attach event to generate an Authorization Key
	$("#migration_wizard_generate_authorization_key").click(function() {
		$.ajax({
			type: "POST",
			async: true,
			url: "manage_integration_settings.php",
			data:
				{
					form_id: $("#form_id").val(),
					action_type: 'generate_authorization_key_for_migration_wizard_settings'
				},
			cache: false,
			global: false,
			dataType: "json",
			error: function(xhr,text_status,e){
				$(".migration-wizard-error").text("Unable to generate an Authorization Key!");
			},
			success: function(response_data){
				if(response_data.status == 'ok'){
					$(".migration-wizard-error").text("");
					$("#migration_wizard_key").val(response_data.authorization_key);
				} else {
					$(".migration-wizard-error").text("Unable to generate an Authorization Key!");
				}
			}
		});
	});
	
	//attach event to disable the Migration Wizard settings
	$("#disable_migration_wizard_btn i").click(function() {
		$("#dialog-confirm-disable-migration-wizard").dialog("open");
	});

	//check all Migration Wizard errors before saving Migration Wizard settings
	async function all_migration_wizard_errors() {
		var target_url = $("#migration_wizard_target_url").val();
		var connector_role = $("input[name='migration_wizard_connector_role']:checked").val();
		var key = $("#migration_wizard_key").val();

		let promise_migration_wizard = new Promise((resolve, reject) => {
			if(target_url == "") {
				resolve("Please enter a System URL!");
			} else if(!isUrl(target_url)) {
				resolve("Please enter a valid System URL!");
			} else if(connector_role == undefined) {
				resolve("Please select a Connector Role!");
			} else if(key == "") {
				resolve("Please enter an Authorization Key!");
			} else {
				resolve("");
			}
		});

		let migration_wizard_error = await promise_migration_wizard;
		if(migration_wizard_error != "") {
			$(".migration-wizard-error").text(migration_wizard_error);
			return true;
		} else {
			$(".migration-wizard-error").text("");
			return false;
		}
	}

	//attach event to save Migration Wizard settings
	$("#save_migration_wizard_btn").click(function(e) {
		e.preventDefault();
		//check errors
		if($("#save_migration_wizard_btn").text().trim() == 'Save Settings'){
			$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Saving...');
		}
		if($("#save_migration_wizard_btn").text().trim() == 'Migrate Entry Data'){
			$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Migrating...');
		}
		$("#save_migration_wizard_btn").after("<div class='loader-box'><img src='./images/loader_small_grey.gif' /></div>");
		$("#save_migration_wizard_btn").prop("disabled",true);

		all_migration_wizard_errors().then(error => {
			if(!error){
				//generate posting data
				var data = {};
				data["form_id"] = $("#form_id").val();
				data["action_type"] = "save_migration_wizard_settings";
				data["target_url"] = $("#migration_wizard_target_url").val();
				data["connector_role"] = $("input[name='migration_wizard_connector_role']:checked").val();
				data["key"] = $("#migration_wizard_key").val();
				//do the ajax call to save the settings
				$.ajax({
					type: "POST",
					async: true,
					url: "manage_integration_settings.php",
					data: data,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e) {
					   	//error, display the generic error message
					   	$("#save_migration_wizard_btn").prop("disabled",false);
					   	if($("#save_migration_wizard_btn").text().trim() == 'Saving...'){
							$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
						}
						if($("#save_migration_wizard_btn").text().trim() == 'Migrating...'){
							$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Migrate Entry Data');
						}
						$("#save_migration_wizard_btn").next().remove();
					   	$("#dialog-warning-msg").html("Unable to migrate entry data from the system. Please try again later.");
						$("#dialog-warning").dialog("open");
					},
				   	success: function(response_data) {
				   		$("#save_migration_wizard_btn").prop("disabled",false);
						if($("#save_migration_wizard_btn").text().trim() == 'Saving...'){
							$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
						}
						if($("#save_migration_wizard_btn").text().trim() == 'Migrating...'){
							$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Migrate Entry Data');
						}
						$("#save_migration_wizard_btn").next().remove();
						if(response_data.status == 'ok'){
							window.location.reload();
						} else if(response_data.status == 'error'){
							$(".migration-wizard-error").text(response_data.msg);
						} else {
							$("#dialog-warning-msg").html("Unable to migrate entry data from the system. Please try again later.");
							$("#dialog-warning").dialog("open");
						}
				   	}
				});
			} else {
				if($("#save_migration_wizard_btn").text().trim() == 'Saving...'){
					$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Save Settings');
				}
				if($("#save_migration_wizard_btn").text().trim() == 'Migrating...'){
					$("#save_migration_wizard_btn").html('<img src="images/navigation/FFFFFF/24x24/Save.png"> Migrate Entry Data');
				}
				$("#save_migration_wizard_btn").next().remove();
			}
		});
	});
	//End Of Migration Wizard
});