function activate_license(){
	
	//send to backend using ajax call
	$.ajax({
		   type: "GET",
		   async: true,
		   url: "https://auditmachine.com/licensemanager/activate.php",
		   data: {install_url: window.location.href,
		   		  license_key: $("#license_box").data("licensekey")
				  },
		   cache: false,
		   global: true,
		   dataType: "jsonp",
		   error: function(xhr,text_status,e){
		   },
		   success: function(response_data){
		   		$("#dialog-change-license").dialog('close');
		   		$("#dialog-change-license-btn-save-changes").prop("disabled",false);
				$("#dialog-change-license-btn-save-changes").text("Activate New License");

				if(response_data.status == "invalid_key" || response_data.status == "usage_exceed"){
					alert(response_data.message);
					$("#lic_activate").hide();
					$("#unregisted_holder").text('UNREGISTERED LICENSE');
					$("#lic_type").text("Invalid License");
					$("#lic_customer_id").text('-');
					$("#lic_customer_name").text('-');

					//send to backend using ajax call
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "unregister.php",
						   data: {unregister: '1'},
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
								//error, display the generic error message		  
						   },
						   success: function(response_data){
								//do nothing   
						   }
					});
				}else if(response_data.status == "valid_key"){
					$("#lic_customer_id").text(response_data.customer_id);
					$("#lic_customer_name").text(response_data.customer_name);
					$("#lic_activate").hide();
					$("#unregisted_holder").text('');
					$("#lic_type").text(response_data.license_type);

					//send to backend using ajax call
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "register.php",
						   data: {
						   		customer_name: response_data.customer_name,
						   		customer_id: response_data.customer_id,
						   		license_key: $("#license_box").data("licensekey")},
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
								//error, display the generic error message		  
						   },
						   success: function(response_data){
								//do nothing   
						   }
					});
				}
		   }
	});
}

function readImage(file, target) {
    var reader = new FileReader();
    
    reader.onload = function(e) {
        $(target).attr('src', e.target.result);
    }
    reader.readAsDataURL(file); // convert to base64 string
}

function readVideo(file, target) {
    var fileUrl = window.URL.createObjectURL(file);
    $(target).attr("src", fileUrl);
}

function changeBackgroundMedia(e){
	// console.log(e);
	var previewVideo = $(e.target).parent().parent().parent().find('video.preview');
	var previewImage = $(e.target).parent().parent().parent().find('img.preview');
	var previewYoutube = $(e.target).parent().parent().parent().find('iframe.preview');
	var deleteBtn = $(e.target).parent().parent().parent().find('div.delete-media-file-btn');

	if (e.target.files && e.target.files[0]) {
		var file = e.target.files[0];
		if (file.type.includes('video')) {
			deleteBtn.show();
			previewVideo.show();
			previewImage.hide();
			previewYoutube.hide();
			readVideo(file, previewVideo);
		} else if (file.type.includes('image')) {
			deleteBtn.show();
			previewVideo.hide();
			previewYoutube.hide();
			previewImage.show();
			readImage(file, previewImage);
		}
	}
};

function removeMediaFile(e) {
	var previewVideo	= $(e.target).parent().parent().parent().find('video.preview');
	var previewImage	= $(e.target).parent().parent().parent().find('img.preview');
	var backgroundUrl	= $(e.target).parent().parent().parent().parent().find('.background_url');
	var fileInput		= $(e.target).parent().parent().parent().parent().find('.local input');
	
	previewVideo.hide();
	previewImage.hide();
	backgroundUrl.val('');
	fileInput.val('');
}

function changeYoutubeLink(e) {
	var previewVideo = $(e.target).parent().parent().find('video.preview');
	var previewImage = $(e.target).parent().parent().find('img.preview');
	var previewYoutube = $(e.target).parent().parent().find('iframe.preview');
	var url = e.target.value;
	if (url != undefined && url != '') {
		var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
		var match = url.match(regExp);
		if (match && match[2].length == 11) {
			// Do anything for being valid
			// if need to change the url to embed url then use below line
			previewVideo.hide();
			previewImage.hide();
			previewYoutube.show();
			var embedUrl = 'https://www.youtube.com/embed/' + match[2];
			previewYoutube.attr('src', embedUrl);
		} else {
			// Do anything for not being valid
			previewVideo.hide();
			previewImage.hide();
			previewYoutube.hide();
		}
	}
}

function changeMediaSource(e) {
	var media_source = e.target.value;
	var local = $(e.target).parent().parent().find('div.local');
	var youtube = $(e.target).parent().parent().find('div.youtube');
	if (media_source == 'local') {
		local.show();
		youtube.hide();
	} else {
		local.hide();
		youtube.show();
	}
}

function removeTile(e){
	const tileId = $(e.target).parent().parent().attr("tile-id");
	if (tileId == "-1") {
		$(e.target).parent().parent().remove();
	} else {
		$("#tile_delete_id").val(tileId);
		$("#dialog-confirm-tile-delete").dialog('open');
	}
};

$(function(){
    
	/***************************************************************************************************************/	
	/* 1. Load Tooltips															   				   				   */
	/***************************************************************************************************************/
	
	$("#processing-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	//we're using jquery tools for the tooltip	
	$(".helpmsg").tooltip({
		
		// place tooltip on the bottom
		position: "bottom center",
		
		// a little tweaking of the position
		offset: [10, 20],
		
		// use the built-in fadeIn/fadeOut effect
		effect: "fade",
		
		// custom opacity setting
		opacity: 0.8,
		
		events: {
			def: 'click,mouseout'
		}
		
	});
	
	/***************************************************************************************************************/	
	/* 2. SMTP Servers settings 														 		  				   */
	/***************************************************************************************************************/
	
	//attach event to 'send notification to my inbox' checkbox
	$("#smtp_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ms_box_smtp .ms_box_email").slideDown();
		}else{
			$("#ms_box_smtp .ms_box_email").slideUp();
		}
	});

	

	/***************************************************************************************************************/	
	/* 3. Misc Settings 																 		  				   */
	/***************************************************************************************************************/
	
	//Attach event to "advanced options" link 
	$("#more_option_misc_settings").click(function(){
		if($(this).text() == 'advanced options'){
			//expand more options
			$("#ms_box_misc .ms_box_more").slideDown();
			$(this).text('hide options');
			$("#misc_settings_img_arrow").attr("src","images/icons/38_topred_16.png");
		}else{
			$("#ms_box_misc .ms_box_more").slideUp();
			$(this).text('advanced options');
			$("#misc_settings_img_arrow").attr("src","images/icons/38_rightred_16.png");
		}
 
		return false;
	});

	//attach event to 'enable ip address restriction' checkbox
	$("#enable_ip_restriction").click(function(){
		if($(this).prop("checked") == true){
			$("#div_ip_whitelist").slideDown();
		}else{
			$("#div_ip_whitelist").slideUp();
		}
	});

	//attach event to 'enable account locking' checkbox
	$("#enable_account_locking").click(function(){
		if($(this).prop("checked") == true){
			$("#div_account_locking").slideDown();
		}else{
			$("#div_account_locking").slideUp();
		}
	});

	//attach event to 'enable session timeout' checkbox
	$("#enable_session_timeout").click(function(){
		if($(this).prop("checked") == true){
			$("#div_session_timeout").slideDown();
		}else{
			$("#div_session_timeout").slideUp();
		}
	});


	/***************************************************************************************************************/
	/* 4. Attach event to 'Save Settings' button																   */
	/***************************************************************************************************************/
	$("#button_save_main_settings").click(function(){

		if($("#button_save_main_settings").text() != 'Saving...'){

				//display loader while saving
				$("#button_save_main_settings").prop("disabled",true);
				$("#button_save_main_settings").text('Saving...');
				$("#button_save_main_settings").after("<img style=\"margin-left: 10px\" src='images/loader_small_grey.gif' />");

				$("#ms_form").submit();
		}


		return false;
	});


	/***************************************************************************************************************/
	/* 5. Dialog Box for change license																		   	   */
	/***************************************************************************************************************/

	$("#dialog-change-license").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		position: { my: "top", at: "top+150", of: window },
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Activate New License',
			id: 'dialog-change-license-btn-save-changes',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				if($("#dialog-change-license-input").val() == ""){
					alert("Please enter your license key!");
				}else{
					$("#dialog-change-license-btn-save-changes").prop("disabled",true);
					$("#dialog-change-license-btn-save-changes").text("Activating...");

					$("#license_box").data("licensekey",$("#dialog-change-license-input").val());
					activate_license();
				}
			}
		},
		{
			text: 'Cancel',
			id: 'dialog-change-license-btn-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$("#dialog-change-license-btn-save-changes").prop("disabled",false);
				$("#dialog-change-license-btn-save-changes").text("Activate New License");
				$(this).dialog('close');
			}
		}]

	});


	$("#ms_change_license").click(function(){
		$("#dialog-change-license").dialog('open');
		return false;
	});

	$("#lic_activate").click(function(){
		if($(this).text() != 'activating...'){
			$(this).text('activating...');
			activate_license();
		}

		return false;
	});

	if($("#lic_activate").length > 0){
		activate_license();
	}

	$("#dialog-change-license-form").submit(function(){
		$("#dialog-change-license-btn-save-changes").click();
		return false;
	});

	/***************************************************************************************************************/	
	/* 6. Form Export / Import Tool																			   	   */
	/***************************************************************************************************************/

	//attach event to export form button
	$("#ms_btn_export_form").click(function(e){
		e.preventDefault();
		$("#processing-dialog").dialog('open');
		var selected_form_id = $("#export_form_id").val();
		$.ajax({
			type: "POST",
			async: true,
			url: "export_form.php",
			data: {
				form_id: selected_form_id
			},
			cache: false,
			global: false,
			dataType: "json",
			error: function(xhr,text_status,e){
				//error, display the generic error message
				$('#processing-dialog').dialog('close');
				$("#dialog-warning").dialog("option", "title", "Unable to export form");
				$("#dialog-warning-msg").html("Sorry, you are unable to export form. Please try again later.");
				$("#dialog-warning").dialog('open');
			},
			success: function(response_data){
				$('#processing-dialog').dialog('close');
				if(response_data.status == "success") {
					window.location.href = response_data.export_link;
				} else {
					$("#dialog-warning").dialog("option", "title", "Unable to export form");
					$("#dialog-warning-msg").html(response_data.message);
					$("#dialog-warning").dialog('open');
				}
			}
		});
	});

	//attach event to the export/import selection
	$("input[name=export_import_type]").click(function(){
		var task_type = $(this).val();
		
		$("#tab_export_form,#tab_import_form").hide();

		if(task_type == 'export'){
			$("#tab_export_form").show();
		}else if(task_type == 'import'){
			$("#tab_import_form").show();
		}
	});

	//initialize file uploader for export/import tool
	$('#ms_form_import_file').uploadifive({
		'uploadScript'     	: 'import_form_uploader.php',
		'buttonText'        : 'Select File',
		'removeCompleted' 	: true,
		'formData'         	: {
								 'session_id': $(".main_settings").data("session_id"),
								 'post_csrf_token': $('meta#csrf-token-meta').attr('content')
			                  },
		'auto'        : true,
	   	'multi'       : false,
	   	'onUploadError' : function(file, errorCode, errorMsg, errorString) {
        						$("#dialog-warning-msg").html("Unable to upload a file. Please try again later.");
		       					$("#dialog-warning").dialog('open');
    					   },
    	'onUploadComplete' : function(file, response) {
    		var is_valid_response = false;
			try{
				var response_json = JSON.parse(response);
				is_valid_response = true;
				$('meta#csrf-token-meta').attr('content', response_json.csrf_token);
				setAjaxDefaultParam();
				//console.log(response_json);
			}catch(e){
				is_valid_response = false;
				//alert('R1' + response);
			}
			
			if(is_valid_response == true && response_json.status == "ok"){
				var uploaded_form_file = response_json.file_name;

				//do ajax call to parse the file
				$.ajax({
					type: "POST",
					async: true,
					url: "import_form_parser.php",
					data: {file_name: uploaded_form_file},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						alert("Error while importing file. Error Message:\n" + xhr.responseText);		  
					},
					success: function(response_data){
						if(response_data.status == 'ok'){
							$("#dialog-form-import-success").data("form_id",response_data.new_form_id);
							$("#dialog-form-import-success").data("form_name",response_data.new_form_name);

							$("#form-imported-link").text(response_data.new_form_name);
							$("#form-imported-link").attr("href","view.php?id=" + response_data.new_form_id);

							//display success dialog
							$("#dialog-form-import-success").dialog('open');
						}else{
							//display error dialog
							if(response_data.status == 'error'){
								$("#dialog-warning-msg").html(response_data.message);
							}

							$("#dialog-warning").dialog('open');
						}
					}
				});
	       	}
			else{
		       	$("#dialog-warning-msg").html(response);
		       	$("#dialog-warning").dialog('open');
			}
    	} 

	});

	//dialog box for import success
	$("#dialog-form-import-success").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 450,
		resizable: false,
		draggable: false,
		open: function(){
			$("#btn-form-success-done").blur();
		},
		buttons: [{
				text: 'Done',
				id: 'btn-form-success-done',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}},
				{
				text: 'Edit Form',
				id: 'btn-form-success-edit',
				'class': 'btn_secondary_action',
				click: function() {
					var current_form_id = $("#dialog-form-import-success").data("form_id");
					window.location.replace('edit_form.php?id=' + current_form_id);
				}
			}]

	});

	//dialog for import failed
	$("#dialog-warning").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 450,
		draggable: false,
		resizable: false,
		open: function(){
			$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'OK',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	

	// management slider

    document.querySelectorAll('.slider-ckeditor').forEach((node, index) => {
		ClassicEditor
			.create(node, {
				fontSize: {
					options: [
						9, 11, 13, 14, 15, 16, 'default', 17, 18, 19, 21
					],
					supportAllValues: true
				},
				toolbar: {
					items: [
						'heading', '|',
						'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
						'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
						'alignment', 'outdent', 'indent', '|',
						'todoList', 'numberedList', 'bulletedList', '|',
						'specialCharacters', 'subscript', 'superscript', '|',
						'horizontalLine', 'blockQuote', '|',
						'insertTable', '|',
						'link', 'imageUpload', 'mediaEmbed', '|',
						'removeFormat', '|',
						'undo', 'redo'
					],
					shouldNotGroupWhenFull: true
				},
				language: 'en',
				image: {
					toolbar: [
						'imageTextAlternative',
						'imageStyle:full',
						'imageStyle:side'
					]
				},
				table: {
					contentToolbar: [
						'tableColumn',
						'tableRow',
						'mergeTableCells',
						'tableCellProperties',
						'tableProperties'
					]
				},
				indentBlock: {
					offset: 1,
					unit: 'em'
				},
				licenseKey: ''
			})
			.catch(error => {
				console.error(error);
			});
	});

    $(".add-new-tile-btn").click(function(){
        var number = $(".tile-settings").length;

		var new_tile_element = '<div class="tile-settings new-ckeditor-div" tile-id="-1" style="display: none;">\
			<div class="delete-tile-btn" style="float:right" onclick="removeTile(event)">\
				<img src="images/icons/51_red_16.png">\
			</div>\
			<div>\
				<label class="description" for="title">Title <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is title"/></label></label>\
				<input type="text" name="tile['+number+'][title]" class="element text large" placeholder="title">\
			</div>\
			<div>\
				<label class="description" for="description">Description <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Description"/></label>\
				<textarea name="tile['+number+'][description]" class="slider-ckeditor"></textarea>\
			</div>\
			<div style="display:flex; justify-content: space-between">\
				<div>\
					<input class="id" type="hidden" name="tile['+number+'][id]" value="-1">\
					<input class="background_url" type="hidden" name="tile['+number+'][background_url]" value="">\
					<input class="background_media_type" type="hidden" name="tile['+number+'][background_media_type]" value="">\
					<input class="order" type="hidden" name="tile['+number+'][order]" value="'+number+'">\
                </div>\
				<div class="media_input_area">\
					<div style="margin-top: 13px">\
						<input id="local_media_'+number+'"  name="tile['+number+'][media_source]" class="element radio" type="radio" value="local" checked onchange="changeMediaSource(event)"/>\
						<label for="local_media_'+number+'">Local Media</label>\
						<input id="youtube_media_'+number+'"  name="tile['+number+'][media_source]" class="element radio" type="radio" value="youtube" onchange="changeMediaSource(event)"/>\
						<label for="youtube_media_'+number+'">Youtube</label>\
					</div>\
					<div class="local">\
						<label class="description" for="upload_tile_media">Media(Image/Video) <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Media(Image/Video)"/></label>\
						<input class="form-control upload_tile_media" name="tilefiles['+number+']" type="file" accept="image/*, video/*" onchange="changeBackgroundMedia(event)"/>\
					</div>\
					<div class="youtube" style="display:none">\
						<label class="description" for="youtube_link">Media(Youtube) <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Youtube Link"/></label>\
						<input class="element text large youtube_link" name="tile['+number+'][youtube_link]" type="text" onchange="changeYoutubeLink(event)"/>\
					</div>\
				</div>\
				<div>\
					<label class="description" for="preview">Preview <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Preview"/></label>\
					<div class="preview_container">\
						<div class="delete-media-file-btn" onclick="removeMediaFile(event)" style="display: none">\
							<img src="images/icons/51_red_16.png">\
						</div>\
						<img class="preview" style="display: none"/>\
						<video  class="preview" autoplay loop style="display: none"></video>\
						<iframe class="preview" style="display: none"></iframe>\
					</div>\
				</div>\
			</div>\
		</div>';
		
        $("#sortable-tiles").append($(new_tile_element));
			var new_ckeditor = $(".new-ckeditor-div").find(".slider-ckeditor")[0];
			ClassicEditor
				.create(new_ckeditor, {
					fontSize: {
						options: [
							9, 11, 13, 14, 15, 16, 'default', 17, 18, 19, 21
						],
						supportAllValues: true
					},
					toolbar: {
						items: [
							'heading', '|',
							'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
							'bold', 'italic', 'underline', 'strikethrough', 'highlight', '|',
							'alignment', 'outdent', 'indent', '|',
							'todoList', 'numberedList', 'bulletedList', '|',
							'specialCharacters', 'subscript', 'superscript', '|',
							'horizontalLine', 'blockQuote', '|',
							'insertTable', '|',
							'link', 'imageUpload', 'mediaEmbed', '|',
							'removeFormat', '|',
							'undo', 'redo'
						],
						shouldNotGroupWhenFull: true
					},
					language: 'en',
					image: {
						toolbar: [
							'imageTextAlternative',
							'imageStyle:full',
							'imageStyle:side'
						]
					},
					table: {
						contentToolbar: [
							'tableColumn',
							'tableRow',
							'mergeTableCells',
							'tableCellProperties',
							'tableProperties'
						]
					},
					indentBlock: {
						offset: 1,
						unit: 'em'
					},
					licenseKey: ''
				})
				.catch(error => {
					console.error(error);
				});
			
			$('.new-ckeditor-div').show().removeClass("new-ckeditor-div");
	});

	//dialog box to confirm admin deletion
	$("#dialog-confirm-tile-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-confirm-tile-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete selected tile',
			id: 'btn-confirm-tile-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				
				//disable the delete button while processing
				$("#btn-confirm-tile-delete-ok").prop("disabled",true);
				//display loader image
				$("#btn-confirm-tile-delete-cancel").hide();
				$("#btn-confirm-tile-delete-ok").text('Deleting...');
				$("#btn-confirm-tile-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				var tile_delete_id = $("#dialog-confirm-tile-delete #tile_delete_id").val();

				//do the ajax call to delete admin
				$.ajax({
					type: "DELETE",
					async: true,
					url: `main_settings.php?tile_id=${tile_delete_id}`,
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						$("#dialog-warning").dialog({title: 'Tile Deletion'});
						$("#dialog-warning-msg").html("Something went wrong. Please try again later.");
						$("#dialog-warning").dialog('open');
					},
					success: function(response_data){ 
						if(response_data.status == 'ok'){
							$("div[tile-id='"+tile_delete_id+"']").remove();
						}
					},
					complete: function(xhr,status) {
						$("#btn-confirm-tile-delete-ok").prop("disabled",false);
						$("#btn-confirm-tile-delete-cancel").show();
						$("#btn-confirm-tile-delete-ok").text('Delete');
						$(".small_loader_box").remove();
						$("#dialog-confirm-tile-delete").dialog('close');
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-tile-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	//slider sortable
	$( "#sortable-tiles" ).sortable({
		update: function(event, ui) {
			$('#management_announcement_slider .tile-settings input.order').each((index, ele) => {
				$(ele).attr('value', index);
			});
        }
	});
	$( "#sortable-tiles" ).disableSelection();
});