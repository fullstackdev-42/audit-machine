//code for overloading the :contains selector to be case insensitive
jQuery.expr[':'].Contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase()
      .indexOf(m[3].toUpperCase()) >= 0;
};
jQuery.expr[':'].contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase()
      .indexOf(m[3].toUpperCase()) >= 0;
};

var selected_form_id = null; 

$(function(){
    
	/***************************************************************************************************************/	
	/* 1. Attach events to Form Title															   				   */
	/***************************************************************************************************************/
	
	//expand the form list when being clicked
	$(".middle_form_bar > h3").click(function(){
		var selected_form_li_id = $(this).parent().parent().attr('id');
		
		//show or hide all the options
		$("#" + selected_form_li_id + " .form_option").slideToggle('medium');
		
		//once all options has been shown/hide, toggle the parent class
		$("#" + selected_form_li_id + " .form_option").promise().done(function() {
			$(this).parent().toggleClass('form_selected');
		});

	});

	$("#la_pagination > li").click(function(){
		var display_list = $(this).data('liform_list');
		
		$("#la_form_list > li").hide();
		$(display_list).show();
		
		$("#la_pagination > li.current_page").removeClass('current_page');
		$(this).addClass('current_page');
	});
	
	//attach event handler to "show more result" on filter result
	$("#result_set_show_more > a").click(function(){
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
				text: 'Yes. Undelete this form',
				id: 'btn-form-delete-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					
					var form_id  = parseInt($("#dialog-confirm-form-delete").data('form_id'));
					
					$("#dropui_theme_options div.dropui-content").attr("style","");
					
					//disable the delete button while processing
					$("#btn-form-delete-ok").prop("disabled",true);
						
					//display loader image
					$("#btn-form-delete-cancel").hide();
					$("#btn-form-delete-ok").text('Undeleting...');
					$("#btn-form-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
					
					//do the ajax call to delete the form
					
					$.ajax({
						   type: "POST",
						   async: true,
						   url: "undelete_form.php",
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
								   window.location.replace('recycle.php');
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
	$(".la_link_delete").click(function(){
		var parent_li = $(this).parent();
		var temp = parent_li.attr('id').split('_');
		var form_id = parseInt(temp[1]);
		$("#confirm_form_delete_name").text(parent_li.find('h3').text());
		$("#dialog-confirm-form-delete").data('form_id',form_id);
		$("#dialog-confirm-form-delete").dialog('open');
		
		return false;
	});
	
});