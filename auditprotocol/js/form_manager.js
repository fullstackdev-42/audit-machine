//code for overloading the :contains selector to be case insensitive
jQuery.expr[':'].Contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase()
      .indexOf(m[3].toUpperCase()) >= 0;
};
jQuery.expr[':'].contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase()
      .indexOf(m[3].toUpperCase()) >= 0;
};

var delay = (function(){
	var timer = 0;
	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

var selected_form_id = null; 

// GET URL variables and return them as an associative array.
function getUrlVars(){
    var vars = [], hash;
	var qry_string_arr = new Array();
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	
    for(var i = 0; i < hashes.length; i++){
        hash = hashes[i].split('=');
		
		if(hash[0] != "search" && hash[0] != "search_term"){
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}else{
			qry_string_arr.push(hash[0] + "=" + hash[1]);
		}
    }
	
    return qry_string_arr.join("&");
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

//clear the form filter
function reset_form_filter(){
	//$('div#div-btn-wrapper').hide();
	$("#la_form_list > li").hide();
	$("#la_pagination").show();
	
	if($("#la_pagination > li.current_page").length > 0){
		$($("#la_pagination > li.current_page").data('liform_list')).show();
	}else{
		$("#la_form_list > li").show();
	}

	$("#la_form_list h3").unhighlight();
	$("ul.form_tag_list li").unhighlight();
	
	$("#filtered_result_box").fadeOut();
	$("#filtered_result_none").hide();
	
	$("#result_set_show_more").hide();
	$('.div-for-element').hide();
}

$(function(){
    
	/***************************************************************************************************************/	
	/* 1. Attach events to Form Title															   				   */
	/***************************************************************************************************************/
	
	//expand the form list when being clicked
	
	$(document).on('click', ".middle_form_bar", function(){
		var selected_form_li_id = $(this).parents('li').attr('id');
		var tmpVal = selected_form_li_id.split("_");
		//show or hide all the options
		if($(this).hasClass("folder-div")){
			$("#" + selected_form_li_id + ">.li-div-wrapper>.la_link_bottomfolder").slideToggle('medium');
			$("#" + selected_form_li_id + " .la_link_topfolder .form_option").slideToggle('medium');

		} else {
			$("#" + selected_form_li_id + " .form_option").slideToggle('medium');
		}
		
		
		
		//once all options has been shown/hide, toggle the parent class
		$("#" + selected_form_li_id + " .form_option").promise().done(function() {
			$(this).parent().toggleClass('form_selected');
		});
	});
	
	$(document).on('click', 'a.anc-form-elements', function(){
		var _selector = $(this);
		var _toggle = _selector.attr('data-form-toggle');
		_selector.attr('data-form-toggle', (parseInt(_toggle) ? 0 : 1));
		var _img = parseInt(_toggle) ? 'images/icons/49_red_16.png' : 'images/icons/51_red_16.png';
		
		$("#form-" + _selector.attr('data-form-id')).toggle('slow');
		_selector.find('img').attr('src', _img);
	});
		
	/***************************************************************************************************************/	
	/* 2. Attach events to 'Disable' link														   				   */
	/***************************************************************************************************************/
	
	//enable or disable the form
	$(document).on('click', ".la_link_disable", function(){
		var selected_form_li_id = $(this).parents('li').attr('id');
		var temp_form_id = selected_form_li_id.split('_');
		var current_form_id = temp_form_id[1];
		var current_action = '';
		
		if($(this).attr("title") == 'Disable'){
			current_action = 'disable';
		}else if($(this).attr("title") == 'Enable'){
			current_action = 'enable';
		}

		if(current_action == 'disable'){
			selected_form_id = current_form_id;
			$("#dialog-disabled-message").dialog('open');
		}else if(current_action == 'enable'){
			
			//do the ajax call to enable or disable the form
			$.ajax({
				   type: "POST",
				   async: true,
				   url: "toggle_form.php",
				   data: {
						  form_id: current_form_id,
						  action: current_action
						 },
				   cache: false,
				   global: true,
				   dataType: "json",
				   error: function(xhr,text_status,e){
					  //restore the links upon error
					  if(current_action == 'disable'){
						  current_action = 'Disable';
					  }else if(current_action == 'Enable'){
						  current_action = 'Enable';
					  }
					  $("#liform_" + current_form_id + " .la_link_disable").attr("title", current_action);
					  $("#liforminfolder_" + current_form_id + " .la_link_disable").attr("title", current_action);
				   },
				   success: function(response_data){
					   
					   if(response_data.status == 'ok'){
						   if(response_data.action == 'disable'){
							   $("#liform_" + response_data.form_id).addClass('form_inactive');
							   $("#liform_" + response_data.form_id + " .la_link_disable").attr('title', "Enable");
							   $("#liform_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Enable.png'>");
							   $("#liforminfolder_" + response_data.form_id).addClass('form_inactive');
							   $("#liforminfolder_" + response_data.form_id + " .la_link_disable").attr('title', "Enable");
							   $("#liforminfolder_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Enable.png'>");
						   }else{
							   $("#liform_" + response_data.form_id).removeClass('form_inactive');
							   $("#liform_" + response_data.form_id + " .la_link_disable").attr('title', "Disable");
							   $("#liform_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Disable.png'>");
							   $("#liforminfolder_" + response_data.form_id).removeClass('form_inactive');
							   $("#liforminfolder_" + response_data.form_id + " .la_link_disable").attr('title', "Disable");
							   $("#liforminfolder_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Disable.png'>");
						   }
					   }else{
						   //unknown error, response json improperly formatted
						   //restore the links upon error
						   	if(current_action == 'disable'){
							  current_action = 'Disable';
							}else if(current_action == 'Enable'){
							  current_action = 'Enable';
							}
							$("#liform_" + current_form_id + " .la_link_disable").attr("title", current_action);
							$("#liforminfolder_" + current_form_id + " .la_link_disable").attr("title", current_action);
						}					   
				   }
			}); //end of ajax call
		}
		return false;
	});

	//Dialog box to disable a form
	$("#dialog-disabled-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 490,
		draggable: false,
		resizable: false,
		open: function(){
			//populate the current message
			var current_message = $("#liform_" + selected_form_id).data("form_disabled_message");
			if(current_message == "" || current_message == null){
				current_message = 'This form is currently inactive.';
			}
			$("#dialog-disabled-message-input").val(current_message);
		},
		buttons: [{
				text: 'Yes. Disable this form',
				id: 'dialog-disabled-message-btn-save',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					if($("#dialog-disabled-message-input").val() == ""){
						alert('Please enter a message!');
					}else{
						
						//disable the save changes button while processing
						$("#dialog-disabled-message-btn-save").prop("disabled",true);
						
						//display loader image
						$("#dialog-disabled-message-btn-cancel").hide();
						$("#dialog-disabled-message-btn-save").text('Processing...');
						$("#dialog-disabled-message-btn-save").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
						
						//do the ajax call to disable the form						
						$.ajax({
							   type: "POST",
							   async: true,
							   url: "toggle_form.php",
							   data: {
									  form_id: selected_form_id,
									  action: 'disable',
									  disabled_message: $("#dialog-disabled-message-input").val()
									 },
							   cache: false,
							   global: true,
							   dataType: "json",
							   error: function(xhr,text_status,e){
								  alert('Error! Unable to process');
							   },
							   success: function(response_data){
								   
								   if(response_data.status == 'ok'){
									   
								   		//restore the buttons and close the dialog box
								   	   	$("#dialog-disabled-message-btn-save").prop("disabled",false);
								       	$("#dialog-disabled-message-btn-cancel").show();
									   	$("#dialog-disabled-message-btn-save").text('Yes. Disable this form');
									  	$("#dialog-disabled-message-btn-save").next().remove();

									   	$("#dialog-disabled-message").dialog('close');
								   	   
								   	   	//update the dom data
								   	   	$("#liform_" + selected_form_id).data("form_disabled_message",$("#dialog-disabled-message-input").val());
								   	   	$("#liforminfolder_" + selected_form_id).data("form_disabled_message",$("#dialog-disabled-message-input").val());

									   	if(response_data.action == 'disable'){
										   	$("#liform_" + response_data.form_id).addClass('form_inactive');
										  	$("#liform_" + response_data.form_id + " .la_link_disable").attr('title', "Enable");
							   				$("#liform_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Enable.png'>");
							   				$("#liforminfolder_" + response_data.form_id).addClass('form_inactive');
										  	$("#liforminfolder_" + response_data.form_id + " .la_link_disable").attr('title', "Enable");
							   				$("#liforminfolder_" + response_data.form_id + " .la_link_disable").html("<img src='images/navigation/FFFFFF/16x16/Enable.png'>");
									   	}
									   
								   }
								   
							   }
						}); //end of ajax call
						
					}
				}
			},
			{
				text: 'Cancel',
				id: 'dialog-disabled-message-btn-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	
	/***************************************************************************************************************/	
	/* 3. Attach events to pagination buttons													   				   */
	/***************************************************************************************************************/
	
	$(document).on('click', "#la_pagination > li", function(){
		var display_list = $(this).data('liform_list');
		$("#la_form_list > li").hide();
		$(display_list).show();
		
		$("#la_pagination > li.current_page").removeClass('current_page');
		$(this).addClass('current_page');
	});	
	
	/***************************************************************************************************************/	
	/* 4. Attach events to search input															   				   */
	/***************************************************************************************************************/
	
	//expand the search box
	$("#filter_form_input").bind('focusin click',function(){
		if($(this).val() == "find form...") {
			$(this).val("");
		}
		$("#la_search_box").animate({'width': '350px'},{duration:200,queue:false}).css('overflow', 'visible', 'important');
		$("#filter_form_input").animate({'width': '320px'},{duration:200,queue:false}).css('overflow', 'visible', 'important');
		$("#la_search_box,#filter_form_input").promise().done(function() {
			$("#la_search_tag,#la_search_element,#la_search_replace").slideDown('medium');

			$("#la_search_tag,#la_search_element,#la_search_title,#la_search_replace").promise().done(function(){
				$("#la_search_box").addClass('search_focused');
			});
		});
		//shrink all opened forms
		$('.form_selected .form_option').hide();
		$(".form_selected").removeClass('form_selected');
	});
	
	//attach event to 'form title / form tags' tabs
	$(document).on('click', "#la_search_title", function(){
		$("#la_search_element").removeClass('la_pane_selected');
		$("#la_search_tag").removeClass('la_pane_selected');
		$("#la_search_replace").removeClass('la_pane_selected');
		$(this).addClass('la_pane_selected');
		reset_form_filter();
		$("#filter_form_input").focus();
		$("#replace_extra_info").hide();
		return false;
	});
	
	$(document).on('click', "#la_search_tag", function(){
		$("#la_search_element").removeClass('la_pane_selected');
		$("#la_search_title").removeClass('la_pane_selected');
		$("#la_search_replace").removeClass('la_pane_selected');
		$(this).addClass('la_pane_selected');
		reset_form_filter();
		$("#filter_form_input").focus();
		$("#replace_extra_info").hide();
		return false;
	});
	
	$(document).on('click', "#la_search_element", function(){
		$("#la_search_tag").removeClass('la_pane_selected');
		$("#la_search_title").removeClass('la_pane_selected');
		$("#la_search_replace").removeClass('la_pane_selected');
		$(this).addClass('la_pane_selected');
		reset_form_filter();
		$("#filter_form_input").focus();
		$("#replace_extra_info").hide();
		return false;
	});

	$(document).on('click', "#la_search_replace", function(){
		$("#la_search_element").removeClass('la_pane_selected');
		$("#la_search_title").removeClass('la_pane_selected');
		$("#la_search_tag").removeClass('la_pane_selected');
		$(this).addClass('la_pane_selected');
		reset_form_filter();
		$("#filter_form_input").focus();
		$("#replace_extra_info").show();
		return false;
	});
	
	//filter the form when user type the search term
	$("#filter_form_input").keyup(function(){
		
		delay(function(){
		  
		  /****************************************************/
			var search_term = $("#filter_form_input").val();
			var max_search_result = 10;
			
			if(search_term != '' && search_term.length >= 3){				
			}else{
				//if the filter keyword is empty, restore back to the original condition
				reset_form_filter();
				
				if($('#la_search_element').hasClass('la_pane_selected')){
					$("#result_set_show_more").show();
					$('.div-for-element').show();
					
					$("table.list-form-elements tr td").unhighlight().hide();
					$(".div-element-list-wrapper").hide("slow");
					$('a.anc-form-elements').each(function(){
						$(this).attr('data-form-toggle', '0');
						$(this).find('img').attr('src', 'images/icons/49_red_16.png');
					});
				}
				
			}
		  /****************************************************/
		  
		}, 100 );
		
	});
	$("#dialog-confirm-replace").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 680,
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'Save Changes',
				id: 'dialog-confirm-replace-btn',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$("#dialog-confirm-replace").dialog('close');

					var search_data = (($("#filter_form_input").val()).trim() != "find form...") ? ($("#filter_form_input").val()).trim() : "";		
					var replace_data = ($("#replace_form_input").val()).trim() || '';	
					var entity_id = ($("#selected_entity_id").val()).trim() || '';
			
					var folder = $("div#content").attr('data-folder-id');
					var urlString = (window.location.href.slice(window.location.href.indexOf('?') + 1)).trim();

					$('#loader-img').show();
					$('#result_set_show_more').remove();
					$('ul#la_pagination').remove();
		
					$.ajax({
						type: "POST",
						url: "search_form.php" + ((urlString == "") ? "" : "?" + urlString),
						data: {
							search_by:"replace",
							search_data:search_data,
							replace_data:replace_data,
							entity_id:entity_id, 
							folder:folder
						},
						error: function(xhr,text_status,e){
							
						},
						success: function(response){
							$('#loader-img').hide();
							$('#la_form_list').html(response);
							$('#la_form_list div.middle_form_bar h3').highlight(search_data);
						},
						complete:function(){}
					});
				}
			},
			{
				text: 'Cancel',
				id: 'dialog-confirm-replace-btn-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	$(document).on('click', '#search-go', function(){
		var search_by = "";
		var search_data = (($("#filter_form_input").val()).trim() != "find form...") ? ($("#filter_form_input").val()).trim() : "";		
		var replace_data = ($("#replace_form_input").val()).trim() || '';	
		var entity_id = $("#selected_entity_id").val();

		var folder = $("div#content").attr('data-folder-id');
		var urlString = (window.location.href.slice(window.location.href.indexOf('?') + 1)).trim();

		
		if($("#la_search_title").hasClass('la_pane_selected')){
			search_by = "title";
		}else if($('#la_search_element').hasClass('la_pane_selected')){ 
			search_by = "element";
		}else if($('#la_search_tag').hasClass('la_pane_selected')){
			search_by = "tag";
		}else if ($('#la_search_replace').hasClass('la_pane_selected')) {
			search_by = "replace";
		}
		
		if (search_by !== "replace") {
			$('#loader-img').show();
			$('#result_set_show_more').remove();
			$('ul#la_pagination').remove();

			$.ajax({
				type: "POST",
				url: "search_form.php" + ((urlString == "") ? "" : "?" + urlString),
				data: {
					search_by:search_by,
					search_data:search_data,
					replace_data:replace_data,
					entity_id:entity_id, 
					folder:folder
				},
				error: function(xhr,text_status,e){
					
				},
				success: function(response){
					$('#loader-img').hide();
					$('#la_form_list').html(response);
					
					if(search_by == "tag"){
						$('#la_form_list div.form_meta div.form_tag').highlight(search_data);
					}else if(search_by == "element"){
						$('#la_form_list div.div-element-list-wrapper').highlight(search_data);
					}else{
						$('#la_form_list div.middle_form_bar h3').highlight(search_data);
					}
				},
				complete:function(){}
			});
		} else {
			$("#dialog-confirm-replace").dialog("open");
		}
	});
	
	$("#la_filter_reset").click(function(){
		reset_form_filter();

		$("#la_search_box").removeClass('search_focused');
		$("#la_search_title,#la_search_tag,#la_search_element").hide();
		
		$("#filter_form_input").val('find form...');
		$(".div-element-list-wrapper").hide("slow");
		
		return false;
	});
	
	//attach event handler to "show more result" on filter result
	$(document).on('click', "#result_set_show_more > a", function(e) {
		var show_more_increment = 20; //the number of more results being displayed each time the button being clicked
		
		var last_result_index = $(".result_set:visible").last().index('.result_set');
		var next_start_index = last_result_index + 1;
		var next_end_index   = next_start_index + show_more_increment;
		
		$(".result_set").slice(next_start_index,next_end_index).fadeIn();
		
		if(next_end_index >= $(".result_set").length){
			$("#result_set_show_more").hide();
		}
		
		return false;
	});
	
	/***************************************************************************************************************/	
	/* 5. Dialog box to enter a tag name														   				   */
	/***************************************************************************************************************/
	
	//Dialog box to assign tag names to form
	$("#dialog-enter-tagname").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		position: { my: "top", at: "top+150", of: window },
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'Save Changes',
				id: 'dialog-enter-tagname-btn-save-changes',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					var form_id  = parseInt($("#dialog-enter-tagname").data('form_id'));
					
					if($("#dialog-enter-tagname-input").val() == ""){
						alert('Please enter a tag name!');
					}else{
						
						$(this).dialog('close');
						
						//display progress bar
						$("#liform_" + form_id + " ul.form_tag_list").append("<li class=\"processing\"><img src='images/loader_small_grey.gif' /></li>");
						$("#liforminfolder_" + form_id + " ul.form_tag_list").append("<li class=\"processing\"><img src='images/loader_small_grey.gif' /></li>");
						
						//do the ajax call to save the tags
						$.ajax({
							   type: "POST",
							   async: true,
							   url: "save_tags.php",
							   data: {
										action: 'add',
										form_id: form_id,
									  	tags: $("#dialog-enter-tagname-input").val()
									  },
							   cache: false,
							   global: false,
							   dataType: "json",
							   error: function(xhr,text_status,e){
									$("#liform_" + form_id + " ul.form_tag_list li.processing").remove();
									$("#liforminfolder_" + form_id + " ul.form_tag_list li.processing").remove();
									alert('Error! Unable to add tag names. Please try again.');	  
							   },
							   success: function(response_data){
									   
								   if(response_data.status == 'ok'){
									   $("#liform_" + response_data.form_id + " li.form_tag_list_icon").siblings().remove()
									   $("#liform_" + response_data.form_id + " ul.form_tag_list").append(response_data.tags_markup);
									   $("#liforminfolder_" + response_data.form_id + " li.form_tag_list_icon").siblings().remove()
									   $("#liforminfolder_" + response_data.form_id + " ul.form_tag_list").append(response_data.tags_markup);
								   }else{
									   $("#liform_" + response_data.form_id + " ul.form_tag_list li.processing").remove();
									   $("#liforminfolder_" + response_data.form_id + " ul.form_tag_list li.processing").remove();
									   alert('Error! Unable to add tag names. Please try again.');
								   }
									   
							   }
						});
						
					}
				}
			},
			{
				text: 'Cancel',
				id: 'dialog-enter-tagname-btn-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	//if the user submit the form by hitting the enter key, make sure to call the button_save_theme handler
	$("#dialog-enter-tagname-form").submit(function(){
		$("#dialog-enter-tagname-btn-save-changes").click();
		return false;
	});
	
	//attach event to add form tag
	$(document).on('click', "ul.form_tag_list a.addtag", function(e) {
		var temp = $(this).attr('id').split('_');
		
		$("#dialog-enter-tagname").data('form_id',temp[1]);
		$("#dialog-enter-tagname-input").val('');
		$("#dialog-enter-tagname").dialog('open');
		
		return false;
	});
	
	//delegate onclick event to delete tag link
	$(document).on('click', '.la_form_list a.removetag', function(e) {
		
		var selected_list = $(this).parent().parent().closest('li').attr('id');
		
		var temp = selected_list.split('_');
		var form_id = parseInt(temp[1]);
		
		var selected_tagname = $(this).parent().text();
		var parent_list = $(this).parent();
		
		//do the ajax call to delete the tag
		if($(this).find('img').attr("src") != "images/loader_green_16.png"){
			$(this).find('img').attr("src","images/loader_green_16.png");
			
			//do the ajax call to save the tags
			$.ajax({
				   type: "POST",
				   async: true,
				   url: "save_tags.php",
				   data: {
							action: 'delete',
							form_id: form_id,
						  	tags: selected_tagname
						  },
				   cache: false,
				   global: false,
				   dataType: "json",
				   error: function(xhr,text_status,e){
					    parent_list.find('img').attr("src","images/icons/53.png");
						alert('Error! Unable to delete tag name. Please try again.');	  
				   },
				   success: function(response_data){
						   
					   if(response_data.status == 'ok'){
						   parent_list.fadeOut(function(){$(this).remove()});
					   }else{
						   parent_list.find('img').attr("src","images/icons/53.png");
						   alert('Error! Unable to delete tag name. Please try again.');
					   }
						   
				   }
			});
		}
		
		
		return false;
    });
	
	//initialize the tagname input box with the existing tags
	$("#dialog-enter-tagname-input").autocomplete({
	    source: $("#dialog-enter-tagname-input").data('available_tags')
	});
	
	/***************************************************************************************************************/	
	/* 6. Attach events to 'Duplicate' link														   				   */
	/***************************************************************************************************************/
	
	$(document).on('click', ".la_link_duplicate", function(){
		var selected_form_li_id = $(this).parents('li').attr('id');
		
		var temp_form_id = selected_form_li_id.split('_');
		var current_form_id = temp_form_id[1];
		
		if($(this).text() == 'Duplicating...'){
			return false; //prevent the user from clicking multiple times
		}
		
		//change the 'Duplicate' text
		$(this).text('Duplicating...');
		$(this).css("padding-left", "20px");
			
		//display the loader image
		$(this).parent().css("position","relative");
		$(this).before('<img class="duplicate-img" src="images/loader_small_grey.gif" style="position: absolute;" />');
			
		//do the ajax call to duplicate the form
		$.ajax({
			   type: "POST",
			   async: true,
			   url: "duplicate_form.php",
			   data: {
					  form_id: current_form_id
					 },
			   cache: false,
			   global: true,
			   dataType: "json",
			   error: function(xhr,text_status,e){
				  //restore the links upon error
				  $("#" + selected_form_li_id + " .la_link_duplicate").text("");
				  $("#" + selected_form_li_id + " .la_link_duplicate").css("padding-left", "5px");
				  $("#" + selected_form_li_id + " .la_link_duplicate").append('<img src="images/navigation/FFFFFF/16x16/Duplicate.png">');
				  $("#" + selected_form_li_id + " .la_link_group .duplicate-img").remove();
				  alert('Error! Unable to duplicate. Please try again.');
				  
			   },
			   success: function(response_data){
					   
				   if(response_data.status == 'ok'){
					   window.location.replace('manage_forms.php?id=' + response_data.form_id + '&hl=true');
				   }else{
					    //unknown error, response json improperly formatted
					    //restore the links upon error
					   	$("#" + selected_form_li_id + " .la_link_duplicate").text("");
					   	$("#" + selected_form_li_id + " .la_link_duplicate").css("padding-left", "5px");
					   	$("#" + selected_form_li_id + " .la_link_duplicate").append('<img src="images/navigation/FFFFFF/16x16/Duplicate.png">');
				  		$("#" + selected_form_li_id + " .la_link_group .duplicate-img").remove();
					   	alert('Error! Unable to duplicate. Please try again.');
				   }
					   
			   }
			}); //end of ajax call
		
		return false;
	});
	
	/***************************************************************************************************************/	
	/* 7. Highlight particular form if the variable exist														   */
	/***************************************************************************************************************/
	
	//this is being used to highlight a newly created form, as a result of a duplicate action
	if(selected_form_id_highlight > 0){
		$("#liform_" + selected_form_id_highlight + " div.middle_form_bar").hide().fadeIn();
	}
	
	/***************************************************************************************************************/	
	/* Rename folder */
	/***************************************************************************************************************/
	
	$("#dialog-confirm-folder-rename").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-folder-rename-ok").blur();
		},
		buttons: [{
			text: 'Yes. Rename this folder',
			id: 'btn-folder-rename-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				var folder_id  = parseInt($("#dialog-confirm-folder-rename").data('folder_id'));					
				var folder_name  = ($("#folder-rename").val()).trim() == "" ? ($("#dialog-confirm-folder-rename").data('folder_name')).trim() : ($("#folder-rename").val()).trim();					
				$("#dropui_theme_options div.dropui-content").attr("style","");
				
				//disable the delete button while processing
				$("#btn-folder-rename-ok").prop("disabled",true);
					
				//display loader image
				$("#btn-folder-rename-cancel").hide();
				$("#btn-folder-rename-ok").text('Renaming...');
				$("#btn-folder-rename-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				
				//do the ajax call to delete the form
				$.ajax({
					type: "POST",
					url: "processupload.php",
					data: {
						mode: 'rename_folder',
						folder_id: folder_id,
						folder_name: folder_name
					},
					error: function(xhr,text_status,e){
						//error, display the generic error message		  
					},
					success: function(response_data){
						window.location.replace('manage_forms.php');  
					},
					complete: function(){}
				});
			}
		},{
			text: 'Cancel',
			id: 'btn-folder-rename-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]

	});
	
	//open the dialog when the delete link clicked
	$(document).on('click', ".la_folder_rename a", function(){
		var parent_li = $(this).parents('li');
		var folder_id = parseInt(parent_li.attr('data-folder-id'));
		var folder_name = parent_li.attr('data-folder-name');
		
		$("#folder-rename").val(folder_name);
		$("#dialog-confirm-folder-rename").data('folder_name',folder_name);
		$("#dialog-confirm-folder-rename").data('folder_id',folder_id);
		$("#dialog-confirm-folder-rename").dialog('open');
		
		return false;
	});
	
	/***************************************************************************************************************/	
	/* Delete folder														   */
	/***************************************************************************************************************/
	
	$("#dialog-confirm-folder-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-folder-delete-ok").blur();
		},
		buttons: [{
			text: 'Yes. Delete this folder',
			id: 'btn-folder-delete-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				var folder_id  = parseInt($("#dialog-confirm-folder-delete").data('folder_id'));					
				$("#dropui_theme_options div.dropui-content").attr("style","");
				
				//disable the delete button while processing
				$("#btn-folder-delete-ok").prop("disabled",true);
					
				//display loader image
				$("#btn-folder-delete-cancel").hide();
				$("#btn-folder-delete-ok").text('Deleting...');
				$("#btn-folder-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
				
				//do the ajax call to delete the folder
				$.ajax({
					type: "POST",
					url: "processupload.php",
					data: {
						mode: 'delete_folder',
						folder_id: folder_id,
					},
					error: function(xhr,text_status,e){
						//error, display the generic error message		  
					},
					success: function(response_data){
						window.location.replace('manage_forms.php');  
					},
					complete: function(){}
				});
			}
		},{
			text: 'Cancel',
			id: 'btn-folder-delete-cancel',
			'class': 'btn_secondary_action',
			click: function() {
				$(this).dialog('close');
			}
		}]

	});
	
	//open the dialog when the delete link clicked
	$(document).on('click', ".la_folder_delete a", function(){
		var parent_li = $(this).parents('li');
		var folder_id = parseInt(parent_li.attr('data-folder-id'));
		var folder_name = parent_li.attr('data-folder-name');
		
		$("#folder-delete").text(folder_name);
		$("#dialog-confirm-folder-delete").data('folder_id',folder_id);
		$("#dialog-confirm-folder-delete").dialog('open');
		
		return false;
	});
	
	/***************************************************************************************************************/	
	/* Move form to a folder														   */
	/***************************************************************************************************************/
	
	//dialog box to confirm move
	$("#dialog-confirm-form-move").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			//$("#btn-form-move-ok").blur();
		},
		buttons: [{
				text: 'Cancel',
				id: 'btn-form-move-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	//open the dialog when the delete link clicked
	$(document).on('click', ".la_link_move a", function(){
		var parent_li = $(this).parents('li');
		var temp = parent_li.attr('id').split('_');
		var form_id = parseInt(temp[1]);
		var folder_id = parseInt(parent_li.attr('data-folder-id'));
		
		$("#confirm_form_move_name").text(parent_li.find('h3').text());
		$("#dialog-confirm-form-move").data('form_id',form_id);
		$("#dialog-confirm-form-move").data('folder_id',folder_id);
		$("#dialog-confirm-form-move").dialog('open');
		
		return false;
	});
	
	$(document).on('click', '#btn-form-create-and-move-ok', function(){
		$('#btn-form-create-and-move-ok').prop('disabled', true);
		$('#btn-form-move-ok').prop('disabled', true);
		var form_id  = parseInt($("#dialog-confirm-form-move").data('form_id'));
		var folder_name  = ($("#folder-name").val()).trim();
		var folder_description  = "";//($("#folder-description").val()).trim();
		
		if(folder_name == ''){
			$('#btn-form-create-and-move-ok').prop('disabled', false);
			$('#btn-form-move-ok').prop('disabled', false);
			alert('Please enter folder name');
			return false;
		}
		
		/*if(folder_description == ''){
			$('#btn-form-create-and-move-ok').prop('disabled', false);
			$('#btn-form-move-ok').prop('disabled', false);
			alert('Please enter folder description');
			return false;
		}*/
		
		$.ajax({
			type: "POST",
			url: "processupload.php",
			data: {
				mode: 'create_and_move',
				form_id: form_id,
				folder_name: folder_name,
				folder_description: folder_description
			},
			error: function(xhr,text_status,e){
				//error, display the generic error message		  
			},
			success: function(response_data){
				window.location.replace('manage_forms.php');  
			},
			complete: function(){}
		});
	});
	
	$(document).on('click', '#btn-form-move-ok', function(){
		$('#btn-form-create-and-move-ok').prop('disabled', true);
		$('#btn-form-move-ok').prop('disabled', true);
		var form_id  = parseInt($("#dialog-confirm-form-move").data('form_id'));
		var folder_id  = parseInt($("#folder-id").val());
		
		/*if(folder_id == 0){
			$('#btn-form-create-and-move-ok').prop('disabled', false);
			$('#btn-form-move-ok').prop('disabled', false);
			alert('Please select folder');
			return false;
		}*/
		
		$.ajax({
			type: "POST",
			url: "processupload.php",
			data: {
				mode: 'move',
				form_id: form_id,
				folder_id: folder_id
			},
			error: function(xhr,text_status,e){
				//error, display the generic error message		  
			},
			success: function(response_data){
				window.location.replace('manage_forms.php');  
			},
			complete: function(){}
		});
	});
	
	/***************************************************************************************************************/	
	/* 8. Attach events to 'Delete' link														   				   */
	/***************************************************************************************************************/
	
	//dialog box to confirm deletion
	$("#dialog-confirm-form-delete").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function(){
			$("#btn-form-delete-ok").blur();
		},
		buttons: [{
				text: 'Yes. Delete this form',
				id: 'btn-form-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					var form_id  = parseInt($("#dialog-confirm-form-delete").data('form_id'));
					
					$("#dropui_theme_options div.dropui-content").attr("style","");
					
					//disable the delete button while processing
					$("#btn-form-delete-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-form-delete-cancel").hide();
					$("#btn-form-delete-ok").text('Deleting...');
					$("#btn-form-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					//do the ajax call to delete the form
					
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "delete_form.php",
						   data: {
								  	form_id: form_id
								  },
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
								   //error, display the generic error message		  
						   },
						   success: function(response_data){
									   
							   if(response_data.status == 'ok'){
								   //redirect to form manager
								   window.location.replace('manage_forms.php');
							   }	  
									   
						   }
					});
					
					
				}
			},
			{
				text: 'Cancel',
				id: 'btn-form-delete-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	//open the dialog when the delete link clicked
	$(document).on('click', ".la_link_delete", function(){
		var parent_li = $(this).parents('li');
		var temp = parent_li.attr('id').split('_');
		var form_id = parseInt(temp[1]);
		$("#dialog-confirm-form-delete").data('form_id',form_id);
		$("#dialog-confirm-form-delete").dialog('open');
		
		return false;
	});
	
	/***************************************************************************************************************/	
	/* 9. Attach events to 'Theme' link														   				   */
	/***************************************************************************************************************/
	
	//dialog box to change a theme 
	$("#dialog-change-theme").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'Save Changes',
				id: 'btn-change-theme-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					var form_id  = parseInt($("#dialog-change-theme").data('form_id'));
					
					
					//disable the delete button while processing
					$("#btn-change-theme-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-change-theme-cancel").hide();
					$("#btn-change-theme-ok").text('Applying Theme...');
					$("#btn-change-theme-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					//do the ajax call to delete the form
					
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "change_theme.php",
						   data: {
								  	form_id: form_id,
								  	theme_id: $("#dialog-change-theme-input").val()
								  },
						   cache: false,
						   global: false,
						   dataType: "json",
						   error: function(xhr,text_status,e){
								   //error, display the generic error message	
								  $("#btn-change-theme-cancel").show();
								  $("#btn-change-theme-ok").text('Save Changes');
							      $("#btn-change-theme-ok").next().remove();
							      $("#btn-change-theme-ok").prop("disabled",false);
							      
							      alert('Error! Unable to apply the theme. Please try again.');
						   },
						   success: function(response_data){
							   
							   $("#btn-change-theme-cancel").show();
							   $("#btn-change-theme-ok").text('Save Changes');
							   $("#btn-change-theme-ok").next().remove();
							   $("#btn-change-theme-ok").prop("disabled",false);
							  
							   if(response_data.status == 'ok'){
								   $("#liform_" + form_id).data('theme_id',$("#dialog-change-theme-input").val());
								   $("#liforminfolder_" + form_id).data('theme_id',$("#dialog-change-theme-input").val());
								   $("#dialog-change-theme").dialog('close');
							   }else{
								   alert('Error! Unable to apply the theme. Please try again.');
							   }
									   
						   }
					});
					
				}
			},
			{
				text: 'Cancel',
				id: 'btn-change-theme-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	//open the dialog when the change theme link clicked
	$(document).on('click', ".la_link_theme", function(){
		
		var parent_li = $(this).parents('li');
		var temp = parent_li.attr('id').split('_');
		var form_id = parseInt(temp[1]);
		
		$("#dialog-change-theme").data('form_id',form_id);
		
		//set the value of the theme dropdown to the current active theme for this form
		$("#dialog-change-theme-input").val(parent_li.data('theme_id'));
		$("#dialog-change-theme").dialog('open');
		
		return false;
	});
	
	//if the user select "create new theme" on the theme selection dropdown
	$('#dialog-change-theme-input').bind('change', function() {
		if($(this).val() == "new"){
			//redirect to theme editor
			window.location.replace('edit_theme.php');
		}
	});

	$("#dialog-confirm-create-entry").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function()
		{
			$("#btn-confirm-edit-ok").blur();
		},
		buttons: [{
			text: 'Yes. Proceed',
			id: 'btn-confirm-create-entry',
			'class': 'bb_button bb_small bb_green',
			click: function() 
			{
				var form_id = $('#form_id_for_create_entry').val();
				window.location.href = "imported_report_list.php?form_id="+form_id;
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-entry-create-entry',
			'class': 'btn_secondary_action',
			click: function() 
			{
				$(this).dialog('close');
			}
		}]	
	});
	
	$(document).on('click', ".la_link_view", function(){
		var form_id = $(this).data('form_id');
		$('#form_id_for_create_entry').val(form_id);
		$("#dialog-confirm-create-entry").dialog('open');
		
		return false;
	});
});
