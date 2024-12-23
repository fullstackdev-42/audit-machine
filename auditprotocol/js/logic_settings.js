(function($){
  $.fn.outerHTML = function() {
    var el = this[0];
    return !el ? null : el.outerHTML || $('<div />').append(el).html();
  }
})(jQuery);

function select_date(dates){
	var month = dates[0].getMonth() + 1;
	var day   = dates[0].getDate();
	var year  = dates[0].getFullYear();
	
	var temp = $(this).attr("id").split("_");
	var li_id = temp[1] + '_' + temp[2];

	var selected_date = month + '/' + day + '/' + year;

	$("#conditionkeyword_" + li_id).val(selected_date);
	$("#lifieldrule_" + li_id).data('rule_condition').keyword = selected_date;
}

function select_date_page(dates){
	var month = dates[0].getMonth() + 1;
	var day   = dates[0].getDate();
	var year  = dates[0].getFullYear();
	var selected_date = month + '/' + day + '/' + year;
	
	var month2 = dates[1].getMonth() + 1;
	var day2   = dates[1].getDate();
	var year2  = dates[1].getFullYear();
	var selected_date2 = month2 + '/' + day2 + '/' + year2;
	
	if(dates[0].getTime() != dates[1].getTime()){
		selected_date += "-"+selected_date2;
	}
	
	var temp = $(this).attr("id").split("_");
	var li_id = temp[1] + '_' + temp[2];

	$("#conditionkeyword_" + li_id).val(selected_date);
	$("#lipagerule_" + li_id).data('rule_condition').keyword = selected_date;
}

function select_date_email(dates){

	var month = dates[0].getMonth() + 1;
	var day   = dates[0].getDate();
	var year  = dates[0].getFullYear();
	
	var temp = $(this).attr("id").split("_");
	var li_id = temp[1] + '_' + temp[2];

	var selected_date = month + '/' + day + '/' + year;

	$("#conditionkeyword_" + li_id).val(selected_date);
	$("#liemailrule_" + li_id).data('rule_condition').keyword = selected_date;
}

//handle approver datepicker
function select_date_approver(dates){

	var month = dates[0].getMonth() + 1;
	var day   = dates[0].getDate();
	var year  = dates[0].getFullYear();
	
	var temp = $(this).attr("id").split("_");
	var li_id = temp[1] + '_' + temp[2];

	var selected_date = month + '/' + day + '/' + year;

	$("#conditionkeywordapprover_" + li_id).val(selected_date);
	$("#liapproverrule_" + li_id).data('rule_condition').keyword = selected_date;
}

function select_date_webhook(dates){

	var month = dates[0].getMonth() + 1;
	var day   = dates[0].getDate();
	var year  = dates[0].getFullYear();
	
	var temp = $(this).attr("id").split("_");
	var li_id = temp[1] + '_' + temp[2];

	var selected_date = month + '/' + day + '/' + year;

	$("#conditionkeyword_" + li_id).val(selected_date);
	$("#liwebhookrule_" + li_id).data('rule_condition').keyword = selected_date;
}

$(function(){
    
	/***************************************************************************************************************/	
	/* 1. Load Tooltips															   				   				   */
	/***************************************************************************************************************/
	
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
	/* 2. Initialize Dialog Boxes												   				   				   */
	/***************************************************************************************************************/

	//Generic warning dialog to be used everywhere
	$("#dialog-warning").dialog({
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
			text: 'OK',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	/***************************************************************************************************************/	
	/* 3. 'Show / Hide Fields' pane												   				   				   */
	/***************************************************************************************************************/
	
	//attach event to 'enable show/hide field rules' checkbox
	$("#logic_field_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ls_box_field_rules .ls_box_content").slideDown();
			$(".logic_settings").data('logic_status').logic_field_enable = 1;
		}else{
			$("#ls_box_field_rules .ls_box_content").slideUp();
			$(".logic_settings").data('logic_status').logic_field_enable = 0;
		}
	});

	//attach event to 'add a field to show/hide' dropdown
	$('#ls_select_field_rule').bind('change', function() {
		
		if($(this).val() == ''){
			return true;
		}
		
		var element_id = parseInt($(this).val());

		//build the markup
		var li_markup = '';
		var condition_fieldname_markup = '';

		condition_fieldname_markup = $("#ls_fields_lookup").clone(false).attr("id","conditionfield_" + element_id + "_1").attr("name","conditionfield_" + element_id + "_1").show().outerHTML();

		li_markup 	+= 	'<li id="lifieldrule_'+ element_id +'" style="display: none">' +
							'<table width="100%" cellspacing="0">' +
							  '<thead>' +
							    '<tr>' + '<td>' + 
							         '<strong>' + $(this).find('option:selected').text() + '</strong><a class="delete_lifieldrule" id="deletelifieldrule_'+ element_id +'" href="#"><img src="images/icons/52_blue_16.png"></a>' + 
							      '</td>' +
							    '</tr>' +
							  '</thead>' +
							  '<tbody>' +
							    '<tr>' +
							      '<td>' +
							      	'<h6>' +
							      		'<img src="images/icons/arrow_right_blue.png" style="vertical-align: top" /><select style="margin-left: 5px;margin-right: 5px" name="fieldruleshowhide_'+ element_id +'" id="fieldruleshowhide_' + element_id + '" class="element select rule_show_hide">' + 
											'<option value="show">Show</option>' +
											'<option value="hide">Hide</option>' +
										'</select> this field if ' + 
										'<select style="margin-left: 5px;margin-right: 5px" name="fieldruleallany_' + element_id + '" id="fieldruleallany_' + element_id + '" class="element select rule_all_any">' + 
											'<option value="all">all</option>' +
											'<option value="any">any</option>' +
										'</select>' + 
										' of the following conditions match:' +
									'</h6>' +
							      	'<ul class="ls_field_rules_conditions">' +
							      		'<li id="lifieldrule_' + element_id + '_1"> ' +
							      			 condition_fieldname_markup + ' ' +
											'<select name="conditiontext_'+ element_id +'_1" id="conditiontext_'+ element_id +'_1" class="element select condition_text" style="width: 120px;display: none">' +
												'<option value="is">Is</option>' +
												'<option value="is_not">Is Not</option>' +
												'<option value="begins_with">Begins with</option>' +
												'<option value="ends_with">Ends with</option>' +
												'<option value="contains">Contains</option>' +
												'<option value="not_contain">Does not contain</option>' +
											'</select>' + ' ' +
											'<select id="conditionnumber_'+ element_id +'_1" name="conditionnumber_' + element_id + '_1" style="width: 120px;display: none" class="element select condition_number">' + 
												'<option value="is" selected="selected">Is</option>' + 
												'<option value="less_than">Less than</option>' + 
												'<option value="greater_than">Greater than</option>' + 
											'</select>' + ' ' +
											'<select id="conditiondate_'+ element_id +'_1" name="conditiondate_' + element_id + '_1" style="width: 120px;display: none" class="element select condition_date">' + 
												'<option value="is" selected="selected">Is</option>' + 
												'<option value="is_before">Is Before</option>' + 
												'<option value="is_after">Is After</option>' + 
											'</select>' + ' ' + 
											'<select id="conditioncheckbox_'+ element_id +'_1" name="conditioncheckbox_' + element_id + '_1" style="width: 120px;display: none" class="element select condition_checkbox">' + 
												'<option value="is_one">Is Checked</option>' + 
												'<option value="is_zero">Is Empty</option>' + 
											'</select>' + ' ' + "\n" +
											'<select id="conditionselect_'+ element_id +'_1" name="conditionselect_' + element_id + '_1" style="display: none" class="element select condition_select">' + 
												'<option value=""></option>' +  
											'</select>' + ' ' + "\n" +
											'<span name="conditiontime_' + element_id + '_1" id="conditiontime_'+ element_id + '_1" class="condition_time" style="display: none">' + 
												'<input name="conditiontimehour_' + element_id + '_1" id="conditiontimehour_' + element_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="HH"> : ' + 
												'<input name="conditiontimeminute_' + element_id + '_1" id="conditiontimeminute_' + element_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="MM">  ' + 
												'<span class="conditiontime_second" style="display:none"> : <input name="conditiontimesecond_' + element_id + '_1" id="conditiontimesecond_' + element_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="SS"> </span>' + 
												'<select class="element select conditiontime_ampm conditiontime_input" name="conditiontimeampm_' + element_id + '_1" id="conditiontimeampm_' + element_id + '_1" style="display:none">' + 
													'<option selected="selected" value="AM">AM</option>' + 
													'<option value="PM">PM</option>' + 
												'</select>' + 
											'</span>' + 
											'<input type="text" class="element text condition_keyword" value="" id="conditionkeyword_'+ element_id +'_1" name="conditionkeyword_'+ element_id +'_1" style="display: none"> ' + "\n" +
											'<a href="#" id="deletecondition_' + element_id + '_1" name="deletecondition_'+ element_id +'_1" class="a_delete_condition"><img src="images/icons/51_blue_16.png" /></a>' + "\n" +
							      		'</li>' +
							      		'<li class="ls_add_condition">' +
											'<a href="#" id="addcondition_'+ element_id +'" class="a_add_condition"><img src="images/icons/49_blue_16.png" /></a>' +
										'</li>' +
							      	'</ul>' +
							      '</td>' +
							    '</tr>' +
							  '</tbody>' +
							'</table>' +
						'</li>';

		//append the rule markup
		$("#ls_field_rules_group").prepend(li_markup);
		$("#lifieldrule_" + element_id).hide();

		//remove the current element from the list of condition
		$("#conditionfield_" + element_id + "_1 option[value=element_"+ element_id +"]").remove();
		$("#conditionfield_" + element_id + "_1 option[value^=element_"+ element_id +"_]").remove(); //remove childs element (for checkbox)

		//diplay the condition operator, depends on the first field on the field list
		var first_field_element_name = $("#conditionfield_" + element_id + "_1").eq(0).val();
		var first_field_element_type = $("#ls_fields_lookup").data(first_field_element_name);
		var default_condition = 'is';
		var default_keyword = '';

		//populate options for condition_select
		$("#conditionselect_" + element_id + "_1").html($("#" + first_field_element_name + "_lookup").html());

		if(first_field_element_type == 'money' || first_field_element_type == 'number'){
			$("#conditionnumber_" + element_id + "_1").show();
			$("#conditionkeyword_" + element_id + "_1").show();
		}else if(first_field_element_type == 'date' || first_field_element_type == 'europe_date'){
			$("#conditiondate_" + element_id + "_1").show();
			$("#conditionkeyword_" + element_id + "_1").show();

			$("#lifieldrule_" + element_id + "_1").addClass("condition_date");
		}else if(first_field_element_type == 'time' || first_field_element_type == 'time_showsecond' || first_field_element_type == 'time_24hour' || first_field_element_type == 'time_showsecond24hour'){
			$("#conditiondate_" + element_id + "_1").show();
			$("#conditiontime_" + element_id + "_1").show();
			
			if(first_field_element_type == 'time'){
				$("#conditiontimeampm_" + element_id + "_1").show();
			}else if(first_field_element_type == 'time_showsecond'){
				$("#conditiontimeampm_" + element_id + "_1").show();
				$("#conditiontimesecond_" + element_id + "_1").parent().show();
			}else if(first_field_element_type == 'time_showsecond24hour'){
				$("#conditiontimesecond_" + element_id + "_1").parent().show();
			}

		}else if(first_field_element_type == 'checkbox'){
			$("#conditioncheckbox_" + element_id + "_1").show();
			default_condition = 'is_one'
		}else if(first_field_element_type == 'select' || first_field_element_type == 'radio'){
			$("#conditiontext_" + element_id + "_1").show();
			$("#conditionselect_" + element_id + "_1").show();

			default_keyword =  $("#conditionselect_" + element_id + "_1").eq(0).val();
		}else{
			$("#conditiontext_" + element_id + "_1").show();
			$("#conditionkeyword_" + element_id + "_1").show();
		}

		//build the datepicker
		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_'+ element_id +'_1" id="datepicker_'+ element_id +'_1">' + "\n" +
							 	 ' <span style="display:none"><img id="datepickimg_'+ element_id +'_1" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + element_id + '_1').after(new_datepicker_tag);

		$('#datepicker_' + element_id + '_1').datepick({ 
		   		onSelect: select_date,
		   		showTrigger: '#datepickimg_' + element_id + '_1'
		});

		$("#lifieldrule_" + element_id).slideDown();

		//attach dom data
		$("#lifieldrule_" + element_id).data('rule_properties',{"element_id": element_id,"rule_show_hide":"show","rule_all_any":"all"});
		$("#lifieldrule_" + element_id + "_1").data('rule_condition',{"target_element_id": element_id,"element_name": first_field_element_name, "condition": default_condition,"keyword": default_keyword});

		//remove the option from the dropdown
		$(this).find('option:selected').remove();
		
		if($("#ls_select_field_rule option").length == 1){
			$("#ls_select_field_rule option").text('No More Fields Available');
		}

	});
	
	//delegate change event to the show/hide dropdown
    $('#ls_box_field_rules').delegate('select.rule_show_hide', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#lifieldrule_" + temp[1]).data('rule_properties').rule_show_hide = $(this).val();
    });

    //delegate change event to the all/any dropdown
    $('#ls_box_field_rules').delegate('select.rule_all_any', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#lifieldrule_" + temp[1]).data('rule_properties').rule_all_any = $(this).val();
    });
	
	//delegate change event into condition field name dropdown
	$('#ls_box_field_rules').delegate('select.condition_fieldname', 'change', function(e) {
			
			var new_element_name = $(this).val();
			var new_element_type = $("#ls_fields_lookup").data(new_element_name);

			$(this).parent().find('.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
			$(this).parent().removeClass('condition_date');

			//reset keyword
			$(this).parent().data('rule_condition').keyword = '';
			$(this).parent().find('.condition_keyword').val('');

			//display the appropriate condition type dropdown, depends on the field type
			//and make sure to update the condition property value when the field type has been changed
			if(new_element_type == 'money' || new_element_type == 'number'){
				$(this).parent().find('.condition_number,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_number').val();
			}else if(new_element_type == 'date' || new_element_type == 'europe_date'){
				$(this).parent().addClass('condition_date');
				$(this).parent().find('.condition_date,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'time' || new_element_type == 'time_showsecond' || new_element_type == 'time_24hour' || new_element_type == 'time_showsecond24hour'){
				$(this).parent().find('.condition_date,.condition_time').show();
				
				$(this).parent().find('.condition_time .conditiontime_second,.condition_time .conditiontime_ampm').hide();
				
				if(new_element_type == 'time'){
					$(this).parent().find('.condition_time .conditiontime_ampm').show();
				}else if(new_element_type == 'time_showsecond'){
					$(this).parent().find('.condition_time .conditiontime_ampm,.condition_time .conditiontime_second').show();
				}else if(new_element_type == 'time_showsecond24hour'){
					$(this).parent().find('.condition_time .conditiontime_second').show();
				}

				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'checkbox'){
				$(this).parent().find('.condition_checkbox').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_checkbox').val();
			}else if(new_element_type == 'radio' || new_element_type == 'select'){
				//reset condition type
				$(this).parent().find('.condition_text').show().val('is');
				$(this).parent().data('rule_condition').condition = 'is';

				//reset condition keyword with dropdown values and display it
				$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
				$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
			}else{
				$(this).parent().find('.condition_text,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_text').val();
			}

			$(this).parent().data('rule_condition').element_name = new_element_name;

    });
	
	//delegate change event to the condition type dropdown (for number, date. checkbox)
    $('#ls_box_field_rules').delegate('select.condition_number,select.condition_date,select.condition_checkbox', 'change', function(e) {
		$(this).parent().data('rule_condition').condition = $(this).val();
    });

    //delegate change event to the condition type dropdown (for other fields beside the above)
    $('#ls_box_field_rules').delegate('select.condition_text', 'change', function(e) {
    	var element_name = $(this).parent().data('rule_condition').element_name;
    	var element_type = $("#ls_fields_lookup").data(element_name);

    	var condition_type = $(this).val();
    	
    	//if the field type is radio/dropdown, check for the selected condition type
    	//if condition type = 'is'/'is_not' , display the dropdown
    	if(element_type == 'radio' || element_type == 'select'){
    		$(this).parent().find('.condition_keyword,.condition_select').hide();

    		if(condition_type == 'is' || condition_type == 'is_not'){
    			$(this).parent().find('.condition_select').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
    		}else{
    			$(this).parent().find('.condition_keyword').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_keyword').val();
    		}
    	}

		$(this).parent().data('rule_condition').condition = condition_type;
    });

	//delegate change event to the condition select dropdown (only applicable for radio and select)
  $('#ls_box_field_rules').delegate('select.condition_select', 'change', function(e) {
	$(this).parent().data('rule_condition').keyword = $(this).val();
  });
	
	//delegate event to the condition keyword text
    $('#ls_box_field_rules').delegate('input.condition_keyword', 'keyup mouseout change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();	
    });

    //delegate event to the time condition inputs
    $('#ls_box_field_rules').delegate('input.conditiontime_input,select.conditiontime_input', 'keyup mouseout change', function(e) {
		
		var temp = $(this).attr("id").split("_");

		var hour_value 	 = parseInt($("#conditiontimehour_" + temp[1] + "_" + temp[2]).val(),10);
		var minute_value = parseInt($("#conditiontimeminute_" + temp[1] + "_" + temp[2]).val(),10);
		var second_value = parseInt($("#conditiontimesecond_" + temp[1] + "_" + temp[2]).val(),10);
		
		var ampm_value 	 = $("#conditiontimeampm_" + temp[1] + "_" + temp[2]).val();

		if(isNaN(hour_value)){
			hour_value = '00';
		}

		if(isNaN(minute_value)){
			minute_value = '00';
		}
		
		if(isNaN(second_value)){
			second_value = '00';
		}

		$("#lifieldrule_" + temp[1] + "_" + temp[2]).data('rule_condition').keyword = hour_value.toString() + ':' + minute_value.toString() + ':' + second_value.toString() + ':' + ampm_value;
    });
	
	//attach event to 'delete field rules' icon
	$('#ls_box_field_rules').delegate('a.delete_lifieldrule', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1];
		
		//restore field dropdown values
		$("#ls_select_field_rule").html($("#ls_select_field_rule_lookup").html());

		$("#lifieldrule_" + element_id).fadeOut(400,function(){
			$(this).remove();

			$("#ls_field_rules_group > li").each(function(){
				var temp_name = $(this).attr('id').split('_');
				var cur_element_id = temp_name[1];
				
				$("#ls_select_field_rule option[value="+ cur_element_id +"]").remove();			
			});
		});
		
		return false;
	});

	//attach click event to 'add rule condition' (+) icon
	$('#ls_box_field_rules').delegate('a.a_add_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1];

		var new_id = $("#lifieldrule_" + element_id + " ul > li:not('.ls_add_condition')").length + 1;
		var old_id = new_id - 1;

		//duplicate the last rule condition
		var last_rule_element = $("#lifieldrule_" + element_id + " ul > li:not('.ls_add_condition')").last();
		last_rule_element.clone(false).data('rule_condition',$.extend('{}',last_rule_element.data('rule_condition'))).find("*[id],*[name]").each(function() {
			var temp = $(this).attr("id").split("_"); 
			
			//rename the original id with the new id
			$(this).attr("id", temp[0] + "_" + temp[1] + "_" + new_id);
			$(this).attr("name", temp[0] + "_" + temp[1] + "_" + new_id);
			
		}).end().attr("id","lifieldrule_" + element_id + "_" + new_id).insertBefore("#lifieldrule_" + element_id + " li.ls_add_condition").hide().fadeIn();

		//copy the value of the dropdowns
		$("#conditionfield_" + element_id + "_" + new_id).val($("#conditionfield_" + element_id + "_" + old_id).val());
		$("#conditiontext_" + element_id + "_" + new_id).val($("#conditiontext_" + element_id + "_" + old_id).val());
		$("#conditionnumber_" + element_id + "_" + new_id).val($("#conditionnumber_" + element_id + "_" + old_id).val());
		$("#conditiondate_" + element_id + "_" + new_id).val($("#conditiondate_" + element_id + "_" + old_id).val());
		$("#conditioncheckbox_" + element_id + "_" + new_id).val($("#conditioncheckbox_" + element_id + "_" + old_id).val());
		
		//reset the condition keyword  
		$("#conditionkeyword_" + element_id + "_" + new_id).val('');
		$("#lifieldrule_" + element_id + "_" + new_id).data('rule_condition').keyword = '';

		//remove the datepicker and rebuild it, with the events as well
		$('#datepicker_' + element_id + '_' + new_id).next().next().remove();
		$('#datepicker_' + element_id + '_' + new_id).next().remove();
		$('#datepicker_' + element_id + '_' + new_id).remove();

		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_' + element_id + '_' + new_id +'" id="datepicker_' + element_id + '_' + new_id +'"> ' +
								 '<span style="display:none"> <img id="datepickimg_'+ element_id + '_' + new_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + element_id + '_' + new_id).after(new_datepicker_tag);

		$('#datepicker_' + element_id + '_' + new_id).datepick({ 
	    		onSelect: select_date,
	    		showTrigger: '#datepickimg_' + element_id + '_' + new_id
		});

		return false;
	});

	//delegate click event to the 'delete rule condition' (-) icon
    $('#ls_box_field_rules').delegate('a.a_delete_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1];

		if($("#lifieldrule_" + element_id + " ul > li:not('.ls_add_condition')").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all condition. <br />You must have at least one condition.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });


    /***************************************************************************************************************/	
	/* 4. Attach event to 'Save Settings' button																   */
	/***************************************************************************************************************/

	$('input[name="selected_admin_check_1_a"]').click(function(){
		var selected_admin_check_1_a = $('input[name="selected_admin_check_1_a"]:checked').val();
		if( selected_admin_check_1_a > 1 ) {
			$('.selected_admins_enabled_1_a').show();
			// $('.selected_admins_enabled_1_a, #ls_box_entry_conditional_email').show();
		} else {
			$('.selected_admins_enabled_1_a').hide();
			// $('.selected_admins_enabled_1_a, #ls_box_entry_conditional_email').hide();
		}
	});

	$("#button_save_logics").click(function(){
		
		if($("#button_save_logics").text() != 'Saving...'){
				var users_select_1_a = [];
				var all_selected_users = '';
				var selected_admin_check_1_a = 0;
				var logic_approver_enable = $('input[name="logic_approver_enable"]:checked').val();
				if( !logic_approver_enable )
					logic_approver_enable = 0;

				$('.selected_admins_enabled_1_a_required').hide();
				if( logic_approver_enable == 1  ) {
					if( $('input[name="selected_admin_check_1_a"]').is(':checked') ) {
						selected_admin_check_1_a = $('input[name="selected_admin_check_1_a"]:checked').val();
						if( selected_admin_check_1_a > 1 ) {
							users_select_1_a = $(".users-select-1-a").val();
							
							if( users_select_1_a !==null && users_select_1_a.length !== 0 ) {
								all_selected_users = users_select_1_a.join(',');
							} else {
								$('.selected_admins_enabled_1_a_required').show();
								$('html, body').animate({
								    scrollTop: $("#ls_box_approver_rules").offset().top - 10
								 }, 1000);
								return false;
							}
						}
					}
				}


				if( logic_approver_enable == 2  ) {
					
					users_select_1_a = $(".users-select-1-a").val();
					
					if( users_select_1_a !==null && users_select_1_a.length !== 0 ) {
						all_selected_users = users_select_1_a.join(',');
					} else {
						$('.selected_admins_enabled_1_a_required').show();
						$('html, body').animate({
						    scrollTop: $("#ls_box_approver_rules").offset().top - 10
						 }, 1000);
						return false;
					}
					
				}

				// return false;
				
				//display loader while saving
				$("#button_save_logics").prop("disabled",true);
				$("#button_save_logics").text('Saving...');
				$("#button_save_logics").after("<div class='small_loader_box' style='float: right'><img src='images/loader_small_grey.gif' /></div>");
				
				//get field logic properties data
				var field_rule_properties_elements = $("#ls_field_rules_group > li");
				var field_rule_properties_data 	   = new Array();

				if(field_rule_properties_elements.length >= 1){
					field_rule_properties_elements.each(function(index){
						field_rule_properties_data[index] = $(this).data('rule_properties');
					});
				}

				var field_rule_condition_elements = $("#ls_field_rules_group ul.ls_field_rules_conditions > li:not('.ls_add_condition')");
				var field_rule_condition_data 	= new Array();

				if(field_rule_condition_elements.length >= 1){
					field_rule_condition_elements.each(function(index){
						field_rule_condition_data[index] = $(this).data('rule_condition');
					});
				}

				//get page logic properties data
				var page_rule_properties_elements = $("#ls_page_rules_group > li");
				var page_rule_properties_data 	   = new Array();

				if(page_rule_properties_elements.length >= 1){
					page_rule_properties_elements.each(function(index){
						page_rule_properties_data[index] = $(this).data('rule_properties');
					});
				}

				var page_rule_condition_elements = $("#ls_page_rules_group ul.ls_page_rules_conditions > li:not('.ls_add_condition')");
				var page_rule_condition_data 	= new Array();

				if(page_rule_condition_elements.length >= 1){
					page_rule_condition_elements.each(function(index){
						page_rule_condition_data[index] = $(this).data('rule_condition');
					});
				}

				//get POAM logic properties data
				var poam_rule_properties_elements = $("#ls_poam_rules_group > li");
				var poam_rule_properties_data 	   = new Array();

				if(poam_rule_properties_elements.length >= 1){
					poam_rule_properties_elements.each(function(index){
						poam_rule_properties_data[index] = $(this).data('rule_condition');
					});
				}

				//get email logic properties data
				var email_rule_properties_elements = $("#ls_email_rules_group > li");
				var email_rule_properties_data 	   = new Array();

				if(email_rule_properties_elements.length >= 1){
					email_rule_properties_elements.each(function(index){
						email_rule_properties_data[index] = $(this).data('rule_properties');
					});
				}

				var email_rule_condition_elements = $("#ls_email_rules_group ul.ls_email_rules_conditions > li:not('.ls_add_condition')");
				var email_rule_condition_data 	= new Array();

				if(email_rule_condition_elements.length >= 1){
					email_rule_condition_elements.each(function(index){
						email_rule_condition_data[index] = $(this).data('rule_condition');
					});
				}

				// console.log(email_rule_condition_data);

				// return false;

				//get webhook logic properties data
				var webhook_rule_properties_elements = $("#ls_webhook_rules_group > li");
				var webhook_rule_properties_data 	 = new Array();

				if(webhook_rule_properties_elements.length >= 1){
					webhook_rule_properties_elements.each(function(index){
						webhook_rule_properties_data[index] = $(this).data('rule_properties');
					});
				}

				var webhook_rule_condition_elements = $("#ls_webhook_rules_group ul.ls_webhook_rules_conditions > li:not('.ls_add_condition')");
				var webhook_rule_condition_data 	= new Array();

				if(webhook_rule_condition_elements.length >= 1){
					webhook_rule_condition_elements.each(function(index){
						webhook_rule_condition_data[index] = $(this).data('rule_condition');
					});
				}
				
				var webhook_keyvalue_param_name_data  = $("#ls_webhook_rules_group li.ns_url_params .ns_param_name > input").serializeArray();
				var webhook_keyvalue_param_value_data = $("#ls_webhook_rules_group li.ns_url_params .ns_param_value > input").serializeArray();

				
				//get approvers logic properties data
				var approver_rule_properties_elements = $("#ls_entry_approver_email > li");
				var approver_rule_properties_data 	   = new Array();

				if(approver_rule_properties_elements.length >= 1){
					approver_rule_properties_elements.each(function(index){
						approver_rule_properties_data[index] = $(this).data('rule_properties');
					});
				}

				//get approvers logic conditions data
				var approver_rule_condition_elements = $("#ls_entry_approver_email ul.ls_email_rules_conditions_approver > li:not('.ls_add_condition_approver')");
				var aprover_rule_condition_data 	= new Array();

				if(approver_rule_condition_elements.length >= 1){
					approver_rule_condition_elements.each(function(index){
						aprover_rule_condition_data[index] = $(this).data('rule_condition');
					});
				}


				//set user order if {multi step approval} is selected

				var logic_approver_enable_process = $("input[name='logic_approver_enable']:checked").val();
				var user_order_process = [];

				if( logic_approver_enable_process == 2 ) {
					var select_user_order_li_process = $("#sortable-approver-users > li");
					if(select_user_order_li_process.length >= 1){
						select_user_order_li_process.each(function(index){
							var user_id = $(this).attr('data-multi-id');
							user_order_process.push({'order':index+1, 'user_id':user_id});
						});
					}
				}

				//do the ajax call to save the settings
				$.ajax({
					   type: "POST",
					   async: true,
					   url: "save_logic_settings.php",
					   data: {
							  	form_id: $("#form_id").val(),
							  	logic_status: $(".logic_settings").data('logic_status'),
							  	field_rule_properties: field_rule_properties_data,
							  	field_rule_conditions: field_rule_condition_data,
							  	page_rule_properties: page_rule_properties_data,
							  	page_rule_conditions: page_rule_condition_data,
							  	poam_rule_properties: poam_rule_properties_data,
							  	email_rule_properties: email_rule_properties_data,
							  	email_rule_conditions: email_rule_condition_data,
							  	approver_rule_properties: approver_rule_properties_data,
							  	approver_rule_conditions: aprover_rule_condition_data,
							  	webhook_rule_properties: webhook_rule_properties_data,
							  	webhook_rule_conditions: webhook_rule_condition_data,
							  	webhook_keyvalue_param_names: webhook_keyvalue_param_name_data,
							  	webhook_keyvalue_param_values: webhook_keyvalue_param_value_data,
							  	logic_approver_enable: logic_approver_enable,
							  	selected_admin_check_1_a: selected_admin_check_1_a,
							  	all_selected_users: all_selected_users,
							  	user_order_process: user_order_process
							  },
					   cache: false,
					   global: false,
					   dataType: "json",
					   error: function(xhr,text_status,e){
							   //error, display the generic error message
							   alert('Error! Unable to save logic settings. Please try again.');
					   },
					   success: function(response_data){
						   if(response_data.status == 'ok'){
							   window.location.replace('logic_settings.php?id=' + response_data.form_id);
						   }else{
							   alert('Error! Unable to save logic settings. Please try again.');
						   } 
					   }
				});
		}
		return false;
	});
	
    /***************************************************************************************************************/	
	/* 5. Initialize rule date pickers																			   */
	/***************************************************************************************************************/
	$("#ls_box_field_rules .rule_datepicker").each(function(index){
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1] + '_' + temp[2];

		$('#datepicker_' + element_id).datepick({ 
	    		onSelect: select_date,
	    		showTrigger: '#datepickimg_' + element_id
		});
	});

	$("#ls_box_page_rules .rule_datepicker").each(function(index){
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1] + '_' + temp[2];

		$('#datepicker_' + element_id).datepick({ 
	    		onSelect: select_date_page,
	    		showTrigger: '#datepickimg_' + element_id,
				rangeSelect: true,
				rangeSeparator: " - "
		});
	});

	$("#ls_box_email_rules .rule_datepicker").each(function(index){
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1] + '_' + temp[2];

		$('#datepicker_' + element_id).datepick({ 
	    		onSelect: select_date_email,
	    		showTrigger: '#datepickimg_' + element_id
		});
	});

	$("#ls_box_webhook_rules .rule_datepicker").each(function(index){
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1] + '_' + temp[2];

		$('#datepicker_' + element_id).datepick({ 
	    		onSelect: select_date_webhook,
	    		showTrigger: '#datepickimg_' + element_id
		});
	});

	/***************************************************************************************************************/	
	/* 6. Page Logic (Skip Pages) Pane																			   */
	/***************************************************************************************************************/

	//attach event to 'enable rules to skip pages' checkbox
	$("#logic_page_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ls_box_page_rules .ls_box_content").slideDown();
			$(".logic_settings").data('logic_status').logic_page_enable = 1;
		}else{
			$("#ls_box_page_rules .ls_box_content").slideUp();
			$(".logic_settings").data('logic_status').logic_page_enable = 0;
		}
	});

	//attach event to 'select destination page' dropdown
	$('#ls_select_page_rule').bind('change', function() {
		
		if($(this).val() == ''){
			return true;
		}
		
		//val() could be page number or these: review, payment, success
		//we added 'page' prefix so that the generated id won't be the same with the show/hide logic fields
		var page_id = 'page' + $(this).val(); 

		//build the markup
		var li_markup = '';
		var condition_fieldname_markup = '';

		condition_fieldname_markup = $("#ls_fields_lookup").clone(false).attr("id","conditionpage_" + page_id + "_1").attr("name","conditionpage_" + page_id + "_1").show().outerHTML();

		li_markup 	+= 	'<li id="lipagerule_'+ page_id +'" style="display: none">' +
							'<table width="100%" cellspacing="0">' +
							  '<thead>' +
							    '<tr>' +
							      '<td>' + 
							         '<strong>' + $(this).find('option:selected').text() + '</strong><a class="delete_lipagerule" id="deletelipagerule_'+ page_id +'" href="#"><img src="images/icons/52_red_16.png"></a>' + 
							      '</td>' +
							    '</tr>' +
							  '</thead>' +
							  '<tbody>' +
							    '<tr>' +
							      '<td>' +
							      	'<h6>' +
							      		'<img src="images/icons/arrow_right_red.png" style="vertical-align: top" /> Go to this page if ' + 
										'<select style="margin-left: 5px;margin-right: 5px" name="pageruleallany_' + page_id + '" id="pageruleallany_' + page_id + '" class="element select rule_all_any">' + 
											'<option value="all">all</option>' +
											'<option value="any">any</option>' +
										'</select>' + 
										' of the following conditions match:' +
									'</h6>' +
							      	'<ul class="ls_page_rules_conditions">' +
							      		'<li id="lipagerule_' + page_id + '_1"> ' +
							      			 condition_fieldname_markup + ' ' +
											'<select name="conditiontext_'+ page_id +'_1" id="conditiontext_'+ page_id +'_1" class="element select condition_text" style="width: 120px;display: none">' +
												'<option value="is">Is</option>' +
												'<option value="is_not">Is Not</option>' +
												'<option value="begins_with">Begins with</option>' +
												'<option value="ends_with">Ends with</option>' +
												'<option value="contains">Contains</option>' +
												'<option value="not_contain">Does not contain</option>' +
											'</select>' + ' ' +
											'<select id="conditionnumber_'+ page_id +'_1" name="conditionnumber_' + page_id + '_1" style="width: 120px;display: none" class="element select condition_number">' + 
												'<option value="is" selected="selected">Is</option>' + 
												'<option value="less_than">Less than</option>' + 
												'<option value="greater_than">Greater than</option>' + 
											'</select>' + ' ' +
											'<select id="conditiondate_'+ page_id +'_1" name="conditiondate_' + page_id + '_1" style="width: 120px;display: none" class="element select condition_date">' + 
												'<option value="is" selected="selected">Is</option>' + 
												'<option value="is_before">Is Before</option>' + 
												'<option value="is_after">Is After</option>' + 
											'</select>' + ' ' + 
											'<select id="conditioncheckbox_'+ page_id +'_1" name="conditioncheckbox_' + page_id + '_1" style="width: 120px;display: none" class="element select condition_checkbox">' + 
												'<option value="is_one">Is Checked</option>' + 
												'<option value="is_zero">Is Empty</option>' + 
											'</select>' + ' ' + "\n" +
											'<select id="conditionselect_'+ page_id +'_1" name="conditionselect_' + page_id + '_1" style="display: none" class="element select condition_select">' + 
												'<option value=""></option>' +  
											'</select>' + ' ' + "\n" +
											'<span name="conditiontime_' + page_id + '_1" id="conditiontime_'+ page_id + '_1" class="condition_time" style="display: none">' + 
												'<input name="conditiontimehour_' + page_id + '_1" id="conditiontimehour_' + page_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="HH"> : ' + 
												'<input name="conditiontimeminute_' + page_id + '_1" id="conditiontimeminute_' + page_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="MM">  ' + 
												'<span class="conditiontime_second" style="display:none"> : <input name="conditiontimesecond_' + page_id + '_1" id="conditiontimesecond_' + page_id + '_1" type="text" class="element text conditiontime_input" maxlength="2" size="2" value="" placeholder="SS"> </span>' + 
												'<select class="element select conditiontime_ampm conditiontime_input" name="conditiontimeampm_' + page_id + '_1" id="conditiontimeampm_' + page_id + '_1" style="display:none">' + 
													'<option selected="selected" value="AM">AM</option>' + 
													'<option value="PM">PM</option>' + 
												'</select>' + 
											'</span>' + 
											'<input type="text" class="element text condition_keyword" value="" id="conditionkeyword_'+ page_id +'_1" name="conditionkeyword_'+ page_id +'_1" style="display: none"> ' + "\n" +
											'<a href="#" id="deletecondition_' + page_id + '_1" name="deletecondition_'+ page_id +'_1" class="a_delete_condition"><img src="images/icons/51_red_16.png" /></a>' + "\n" +
							      		'</li>' +
							      		'<li class="ls_add_condition">' +
											'<a href="#" id="addcondition_'+ page_id +'" class="a_add_condition"><img src="images/icons/49_red_16.png" /></a>' +
										'</li>' +
							      	'</ul>' +
							      '</td>' +
							    '</tr>' +
							  '</tbody>' +
							'</table>' +
						'</li>';

		//append the rule markup
		$("#ls_page_rules_group").prepend(li_markup);
		$("#lipagerule_" + page_id).hide();

		//remove the current element from the list of condition
		$("#conditionpage_" + page_id + "_1 option[value=element_"+ page_id +"]").remove();
		$("#conditionpage_" + page_id + "_1 option[value^=element_"+ page_id +"_]").remove(); //remove childs element (for checkbox)

		//diplay the condition operator, depends on the first field on the field list
		var first_field_element_name = $("#conditionpage_" + page_id + "_1").eq(0).val();
		var first_field_element_type = $("#ls_fields_lookup").data(first_field_element_name);
		var default_condition = 'is';
		var default_keyword = '';

		//populate options for condition_select
		$("#conditionselect_" + page_id + "_1").html($("#" + first_field_element_name + "_lookup").html());

		if(first_field_element_type == 'money' || first_field_element_type == 'number'){
			$("#conditionnumber_" + page_id + "_1").show();
			$("#conditionkeyword_" + page_id + "_1").show();
		}else if(first_field_element_type == 'date' || first_field_element_type == 'europe_date'){
			$("#conditiondate_" + page_id + "_1").show();
			$("#conditionkeyword_" + page_id + "_1").show();

			$("#lipagerule_" + page_id + "_1").addClass("condition_date");
		}else if(first_field_element_type == 'time' || first_field_element_type == 'time_showsecond' || first_field_element_type == 'time_24hour' || first_field_element_type == 'time_showsecond24hour'){
			$("#conditiondate_" + page_id + "_1").show();
			$("#conditiontime_" + page_id + "_1").show();
			
			if(first_field_element_type == 'time'){
				$("#conditiontimeampm_" + page_id + "_1").show();
			}else if(first_field_element_type == 'time_showsecond'){
				$("#conditiontimeampm_" + page_id + "_1").show();
				$("#conditiontimesecond_" + page_id + "_1").parent().show();
			}else if(first_field_element_type == 'time_showsecond24hour'){
				$("#conditiontimesecond_" + page_id + "_1").parent().show();
			}

		}else if(first_field_element_type == 'checkbox'){
			$("#conditioncheckbox_" + page_id + "_1").show();
			default_condition = 'is_one'
		}else if(first_field_element_type == 'select' || first_field_element_type == 'radio'){
			$("#conditiontext_" + page_id + "_1").show();
			$("#conditionselect_" + page_id + "_1").show();

			default_keyword =  $("#conditionselect_" + page_id + "_1").eq(0).val();
		}else{
			$("#conditiontext_" + page_id + "_1").show();
			$("#conditionkeyword_" + page_id + "_1").show();
		}

		//build the datepicker
		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_'+ page_id +'_1" id="datepicker_'+ page_id +'_1">' + "\n" +
							 	 ' <span style="display:none"><img id="datepickimg_'+ page_id +'_1" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + page_id + '_1').after(new_datepicker_tag);

		$('#datepicker_' + page_id + '_1').datepick({ 
		   		onSelect: select_date_page,
		   		showTrigger: '#datepickimg_' + page_id + '_1',
				rangeSelect: true,
				rangeSeparator: " - "
		});

		$("#lipagerule_" + page_id).slideDown();

		//attach dom data
		$("#lipagerule_" + page_id).data('rule_properties',{"page_id": page_id, "rule_all_any":"all"});
		$("#lipagerule_" + page_id + "_1").data('rule_condition',{"target_page_id": page_id,"element_name": first_field_element_name, "condition": default_condition,"keyword": default_keyword});

		//remove the option from the dropdown
		$(this).find('option:selected').remove();
		
		if($("#ls_select_page_rule option").length == 1){
			$("#ls_select_page_rule option").text('No More Page Available');
		}

	});

	//delegate change event to the all/any dropdown
    $('#ls_box_page_rules').delegate('select.rule_all_any', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#lipagerule_" + temp[1]).data('rule_properties').rule_all_any = $(this).val();
    });

    //delegate change event into condition field name dropdown
	$('#ls_box_page_rules').delegate('select.condition_fieldname', 'change', function(e) {
			
			var new_element_name = $(this).val();
			var new_element_type = $("#ls_fields_lookup").data(new_element_name);

			$(this).parent().find('.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
			$(this).parent().removeClass('condition_date');

			//reset keyword
			$(this).parent().data('rule_condition').keyword = '';
			$(this).parent().find('.condition_keyword').val('');


			//display the appropriate condition type dropdown, depends on the field type
			//and make sure to update the condition property value when the field type has been changed
			if(new_element_type == 'money' || new_element_type == 'number'){
				$(this).parent().find('.condition_number,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_number').val();
			}else if(new_element_type == 'date' || new_element_type == 'europe_date'){
				$(this).parent().addClass('condition_date');
				$(this).parent().find('.condition_date,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'time' || new_element_type == 'time_showsecond' || new_element_type == 'time_24hour' || new_element_type == 'time_showsecond24hour'){
				$(this).parent().find('.condition_date,.condition_time').show();
				
				$(this).parent().find('.condition_time .conditiontime_second,.condition_time .conditiontime_ampm').hide();
				
				if(new_element_type == 'time'){
					$(this).parent().find('.condition_time .conditiontime_ampm').show();
				}else if(new_element_type == 'time_showsecond'){
					$(this).parent().find('.condition_time .conditiontime_ampm,.condition_time .conditiontime_second').show();
				}else if(new_element_type == 'time_showsecond24hour'){
					$(this).parent().find('.condition_time .conditiontime_second').show();
				}

				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'checkbox'){
				$(this).parent().find('.condition_checkbox').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_checkbox').val();
			}else if(new_element_type == 'radio' || new_element_type == 'select'){
				//reset condition type
				$(this).parent().find('.condition_text').show().val('is');
				$(this).parent().data('rule_condition').condition = 'is';

				//reset condition keyword with dropdown values and display it
				$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
				$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
			}else{
				$(this).parent().find('.condition_text,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_text').val();
			}

			$(this).parent().data('rule_condition').element_name = new_element_name;

    });
	
	//delegate change event to the condition type dropdown (for number, date. checkbox)
    $('#ls_box_page_rules').delegate('select.condition_number,select.condition_date,select.condition_checkbox', 'change', function(e) {
		$(this).parent().data('rule_condition').condition = $(this).val();
    });

    //delegate change event to the condition type dropdown (for other fields beside the above)
    $('#ls_box_page_rules').delegate('select.condition_text', 'change', function(e) {
    	var element_name = $(this).parent().data('rule_condition').element_name;
    	var element_type = $("#ls_fields_lookup").data(element_name);

    	var condition_type = $(this).val();
    	
    	//if the field type is radio/dropdown, check for the selected condition type
    	//if condition type = 'is'/'is_not' , display the dropdown
    	if(element_type == 'radio' || element_type == 'select'){
    		$(this).parent().find('.condition_keyword,.condition_select').hide();

    		if(condition_type == 'is' || condition_type == 'is_not'){
    			$(this).parent().find('.condition_select').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
    		}else{
    			$(this).parent().find('.condition_keyword').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_keyword').val();
    		}
    	}

		$(this).parent().data('rule_condition').condition = condition_type;
    });

	//delegate change event to the condition select dropdown (only applicable for radio and select)
    $('#ls_box_page_rules').delegate('select.condition_select', 'change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();
    });

    //delegate event to the condition keyword text
    $('#ls_box_page_rules').delegate('input.condition_keyword', 'keyup mouseout change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();	
    });

    //delegate event to the time condition inputs
    $('#ls_box_page_rules').delegate('input.conditiontime_input,select.conditiontime_input', 'keyup mouseout change', function(e) {
		
		var temp = $(this).attr("id").split("_");

		var hour_value 	 = parseInt($("#conditiontimehour_" + temp[1] + "_" + temp[2]).val(),10);
		var minute_value = parseInt($("#conditiontimeminute_" + temp[1] + "_" + temp[2]).val(),10);
		var second_value = parseInt($("#conditiontimesecond_" + temp[1] + "_" + temp[2]).val(),10);
		var ampm_value 	 = $("#conditiontimeampm_" + temp[1] + "_" + temp[2]).val();

		if(isNaN(hour_value)){
			hour_value = '00';
		}

		if(isNaN(minute_value)){
			minute_value = '00';
		}
		
		if(isNaN(second_value)){
			second_value = '00';
		}

		$("#lipagerule_" + temp[1] + "_" + temp[2]).data('rule_condition').keyword = hour_value.toString() + ':' + minute_value.toString() + ':' + second_value.toString() + ':' + ampm_value;
    });

    //attach event to 'delete page rules' icon
	$('#ls_box_page_rules').delegate('a.delete_lipagerule', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var page_id = temp[1];
		
		//restore field dropdown values
		$("#ls_select_page_rule").html($("#ls_select_page_rule_lookup").html());

		$("#lipagerule_" + page_id).fadeOut(400,function(){
			$(this).remove();

			$("#ls_page_rules_group > li").each(function(){
				var temp_name = $(this).attr('id').split('_');
				var cur_page_id = temp_name[1];

				cur_page_id = cur_page_id.substring(4); //remove the 'page' prefix
				
				$("#ls_select_page_rule option[value="+ cur_page_id +"]").remove();			
			});
		});
		
		return false;
	});

	//attach click event to 'add rule condition' (+) icon
	$('#ls_box_page_rules').delegate('a.a_add_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var page_id = temp[1];

		var new_id = $("#lipagerule_" + page_id + " ul > li:not('.ls_add_condition')").length + 1;
		var old_id = new_id - 1;

		//duplicate the last rule condition
		var last_rule_element = $("#lipagerule_" + page_id + " ul > li:not('.ls_add_condition')").last();
		last_rule_element.clone(false).data('rule_condition',$.extend('{}',last_rule_element.data('rule_condition'))).find("*[id],*[name]").each(function() {
			var temp = $(this).attr("id").split("_"); 
			
			//rename the original id with the new id
			$(this).attr("id", temp[0] + "_" + temp[1] + "_" + new_id);
			$(this).attr("name", temp[0] + "_" + temp[1] + "_" + new_id);
			
		}).end().attr("id","lipagerule_" + page_id + "_" + new_id).insertBefore("#lipagerule_" + page_id + " li.ls_add_condition").hide().fadeIn();

		//copy the value of the dropdowns
		$("#conditionpage_" + page_id + "_" + new_id).val($("#conditionpage_" + page_id + "_" + old_id).val());
		$("#conditiontext_" + page_id + "_" + new_id).val($("#conditiontext_" + page_id + "_" + old_id).val());
		$("#conditionnumber_" + page_id + "_" + new_id).val($("#conditionnumber_" + page_id + "_" + old_id).val());
		$("#conditiondate_" + page_id + "_" + new_id).val($("#conditiondate_" + page_id + "_" + old_id).val());
		$("#conditioncheckbox_" + page_id + "_" + new_id).val($("#conditioncheckbox_" + page_id + "_" + old_id).val());
		
		//reset the condition keyword  
		$("#conditionkeyword_" + page_id + "_" + new_id).val('');
		$("#lipagerule_" + page_id + "_" + new_id).data('rule_condition').keyword = '';

		//remove the datepicker and rebuild it, with the events as well
		$('#datepicker_' + page_id + '_' + new_id).next().next().remove();
		$('#datepicker_' + page_id + '_' + new_id).next().remove();
		$('#datepicker_' + page_id + '_' + new_id).remove();

		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_' + page_id + '_' + new_id +'" id="datepicker_' + page_id + '_' + new_id +'"> ' +
								 '<span style="display:none"> <img id="datepickimg_'+ page_id + '_' + new_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + page_id + '_' + new_id).after(new_datepicker_tag);

		$('#datepicker_' + page_id + '_' + new_id).datepick({ 
	    		onSelect: select_date_page,
	    		showTrigger: '#datepickimg_' + page_id + '_' + new_id,
				rangeSelect: true,
				rangeSeparator: " - "
		});

		return false;
	});

	//delegate click event to the 'delete rule condition' (-) icon
    $('#ls_box_page_rules').delegate('a.a_delete_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var page_id = temp[1];

		if($("#lipagerule_" + page_id + " ul > li:not('.ls_add_condition')").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all condition. <br />You must have at least one condition.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });

    /***************************************************************************************************************/	
	/* 7. Notification Emails Rules Pane					   												   	   */
	/***************************************************************************************************************/

	//attach event to 'enable rules to skip pages' checkbox
	$("#logic_email_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ls_box_email_rules .ls_box_content").slideDown();
			$(".logic_settings").data('logic_status').logic_email_enable = 1;
		}else{
			$("#ls_box_email_rules .ls_box_content").slideUp();
			$(".logic_settings").data('logic_status').logic_email_enable = 0;
		}
	});

	//delegate change event to the all/any dropdown
    $('#ls_box_email_rules').delegate('select.rule_all_any', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#liemailrule_" + temp[1]).data('rule_properties').rule_all_any = $(this).val();
    });

    //delegate change event into condition field name dropdown
	$('#ls_box_email_rules').delegate('select.condition_fieldname', 'change', function(e) {
			
			var new_element_name = $(this).val();
			var new_element_type = $("#ls_fields_lookup").data(new_element_name);

			// console.log(new_element_name);

			// $(this).parent().find('.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
			$(this).parent().find('.condition_final_approval,.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
			$(this).parent().removeClass('condition_date');

			//reset keyword
			$(this).parent().data('rule_condition').keyword = '';
			$(this).parent().find('.condition_keyword').val('');

			//display the appropriate condition type dropdown, depends on the field type
			//and make sure to update the condition property value when the field type has been changed
			if(new_element_type == 'money' || new_element_type == 'number'){
				$(this).parent().find('.condition_number,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_number').val();
			}else if(new_element_type == 'date' || new_element_type == 'europe_date'){
				$(this).parent().addClass('condition_date');
				$(this).parent().find('.condition_date,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'time' || new_element_type == 'time_showsecond' || new_element_type == 'time_24hour' || new_element_type == 'time_showsecond24hour'){
				$(this).parent().find('.condition_date,.condition_time').show();
				
				$(this).parent().find('.condition_time .conditiontime_second,.condition_time .conditiontime_ampm').hide();
				
				if(new_element_type == 'time'){
					$(this).parent().find('.condition_time .conditiontime_ampm').show();
				}else if(new_element_type == 'time_showsecond'){
					$(this).parent().find('.condition_time .conditiontime_ampm,.condition_time .conditiontime_second').show();
				}else if(new_element_type == 'time_showsecond24hour'){
					$(this).parent().find('.condition_time .conditiontime_second').show();
				}

				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'checkbox'){
				$(this).parent().find('.condition_checkbox').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_checkbox').val();
			}else if(new_element_type == 'radio' || new_element_type == 'select'){
				//reset condition type
				$(this).parent().find('.condition_text').show().val('is');
				$(this).parent().data('rule_condition').condition = 'is';

				//reset condition keyword with dropdown values and display it
				$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
				$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
			}else if( new_element_name == 'element_final_approval' ){
				// console.log('in condition');
				$(this).parent().find('.condition_final_approval').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_final_approval').val();
			}else{
				$(this).parent().find('.condition_text,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_text').val();
			}

			$(this).parent().data('rule_condition').element_name = new_element_name;
			//rule conditions are decided here

    });
	
	//delegate change event to the condition type dropdown (for number, date. checkbox)
    $('#ls_box_email_rules, #ls_box_entry_conditional_email').delegate('select.condition_number,select.condition_date,select.condition_checkbox', 'change', function(e) {
		$(this).parent().data('rule_condition').condition = $(this).val();
    });


    //delegate change event to the condition type dropdown (for condition_final_approval)
    $('#ls_box_email_rules').delegate('select.condition_final_approval', 'change', function(e) {
		$(this).parent().data('rule_condition').condition = $(this).val();
    });

    //delegate change event to the condition type dropdown (for other fields beside the above)
    $('#ls_box_email_rules, #ls_box_entry_conditional_email').delegate('select.condition_text', 'change', function(e) {
    	var element_name = $(this).parent().data('rule_condition').element_name;
    	var element_type = $("#ls_fields_lookup").data(element_name);

    	var condition_type = $(this).val();
    	
    	//if the field type is radio/dropdown, check for the selected condition type
    	//if condition type = 'is'/'is_not' , display the dropdown
    	if(element_type == 'radio' || element_type == 'select'){
    		$(this).parent().find('.condition_keyword,.condition_select').hide();

    		if(condition_type == 'is' || condition_type == 'is_not'){
    			$(this).parent().find('.condition_select').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
    		}else{
    			$(this).parent().find('.condition_keyword').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_keyword').val();
    		}
    	}

		$(this).parent().data('rule_condition').condition = condition_type;
    });

	//delegate change event to the condition select dropdown (only applicable for radio and select)
    $('#ls_box_email_rules, #ls_box_entry_conditional_email').delegate('select.condition_select', 'change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();
    });

    //delegate event to the condition keyword text
    $('#ls_box_email_rules, #ls_box_entry_conditional_email').delegate('input.condition_keyword', 'keyup mouseout change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();		
    });

    //delegate event to the time condition inputs
    $('#ls_box_email_rules, #ls_box_entry_conditional_email').delegate('input.conditiontime_input,select.conditiontime_input', 'keyup mouseout change', function(e) {
		
		var temp = $(this).attr("id").split("_");

		var hour_value 	 = parseInt($("#conditiontimehour_" + temp[1] + "_" + temp[2]).val(),10);
		var minute_value = parseInt($("#conditiontimeminute_" + temp[1] + "_" + temp[2]).val(),10);
		var second_value = parseInt($("#conditiontimesecond_" + temp[1] + "_" + temp[2]).val(),10);
		var ampm_value 	 = $("#conditiontimeampm_" + temp[1] + "_" + temp[2]).val();

		if(isNaN(hour_value)){
			hour_value = '00';
		}

		if(isNaN(minute_value)){
			minute_value = '00';
		}
		
		if(isNaN(second_value)){
			second_value = '00';
		}

		$("#liemailrule_" + temp[1] + "_" + temp[2]).data('rule_condition').keyword = hour_value.toString() + ':' + minute_value.toString() + ':' + second_value.toString() + ':' + ampm_value;
    });

    //attach event to 'delete email rules' icon
	$('#ls_box_email_rules').delegate('a.delete_liemailrule', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];
		
		if($("#ls_email_rules_group > li").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all rules. <br />You must have at least one rule.");
			$("#dialog-warning").dialog('open');
		}else{
			$("#liemailrule_" + rule_id).fadeOut(400,function(){
				$(this).remove();
			});
		}
		
		return false;
	});

	//attach click event to 'add rule condition' (+) icon
	$('#ls_box_email_rules').delegate('a.a_add_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		var new_id = $("#liemailrule_" + rule_id + " ul > li:not('.ls_add_condition')").length + 1;
		var old_id = new_id - 1;

		//duplicate the last rule condition
		var last_rule_element = $("#liemailrule_" + rule_id + " ul > li:not('.ls_add_condition')").last();
		last_rule_element.clone(false).data('rule_condition',$.extend('{}',last_rule_element.data('rule_condition'))).find("*[id],*[name]").each(function() {
			var temp = $(this).attr("id").split("_"); 
			
			//rename the original id with the new id
			$(this).attr("id", temp[0] + "_" + temp[1] + "_" + new_id);
			$(this).attr("name", temp[0] + "_" + temp[1] + "_" + new_id);
			
		}).end().attr("id","liemailrule_" + rule_id + "_" + new_id).insertBefore("#liemailrule_" + rule_id + " li.ls_add_condition").hide().fadeIn();

		//copy the value of the dropdowns
		$("#conditionemail_" + rule_id + "_" + new_id).val($("#conditionemail_" + rule_id + "_" + old_id).val());
		$("#conditiontext_" + rule_id + "_" + new_id).val($("#conditiontext_" + rule_id + "_" + old_id).val());
		$("#conditionnumber_" + rule_id + "_" + new_id).val($("#conditionnumber_" + rule_id + "_" + old_id).val());
		$("#conditiondate_" + rule_id + "_" + new_id).val($("#conditiondate_" + rule_id + "_" + old_id).val());
		$("#conditioncheckbox_" + rule_id + "_" + new_id).val($("#conditioncheckbox_" + rule_id + "_" + old_id).val());
		
		//reset the condition keyword  
		$("#conditionkeyword_" + rule_id + "_" + new_id).val('');
		$("#liemailrule_" + rule_id + "_" + new_id).data('rule_condition').keyword = '';

		//remove the datepicker and rebuild it, with the events as well
		$('#datepicker_' + rule_id + '_' + new_id).next().next().remove();
		$('#datepicker_' + rule_id + '_' + new_id).next().remove();
		$('#datepicker_' + rule_id + '_' + new_id).remove();

		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_' + rule_id + '_' + new_id +'" id="datepicker_' + rule_id + '_' + new_id +'"> ' +
								 '<span style="display:none"> <img id="datepickimg_'+ rule_id + '_' + new_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + rule_id + '_' + new_id).after(new_datepicker_tag);

		$('#datepicker_' + rule_id + '_' + new_id).datepick({ 
	    		onSelect: select_date_email,
	    		showTrigger: '#datepickimg_' + rule_id + '_' + new_id
		});

		return false;
	});

	//delegate click event to the 'delete rule condition' (-) icon
    $('#ls_box_email_rules').delegate('a.a_delete_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		if($("#liemailrule_" + rule_id + " ul > li:not('.ls_add_condition')").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all condition. <br />You must have at least one condition.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });

    //delegate change event to the 'send email to' dropdown
    $('#ls_box_email_rules').delegate('select.target_email_dropdown', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		var target_email = $(this).val();
		
		if(target_email == 'custom'){
			$("#targetemailcustomspan_" + temp[1]).show();
			target_email = $("#targetemailcustom_" + temp[1]).val();
		}else{
			$("#targetemailcustomspan_" + temp[1]).hide();
		}

		$("#liemailrule_" + temp[1]).data('rule_properties').target_email = target_email;
		
    });

    //delegate event to the 'custom target email' text
    $('#ls_box_email_rules').delegate('input.target_email_custom', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').target_email = $(this).val();
    });

    //delegate change event to the 'email template' dropdown
    $('#ls_box_email_rules').delegate('select.template_name', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		
		$("#liemailrule_" + temp[1]).data('rule_properties').template_name = $(this).val();

		if($(this).val() == 'custom'){
			$("#ls_email_custom_template_div_" + temp[1]).fadeIn();
		}else{
			$("#ls_email_custom_template_div_" + temp[1]).fadeOut();
		}
    });

    //delegate change event to the 'custom from name' dropdown
    $('#ls_box_email_rules').delegate('select.custom_from_name_dropdown', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		var from_name = $(this).val();
		
		if(from_name == 'custom'){
			$("#customfromnamespan_" + temp[1]).show();
			from_name = $("#customfromnameuser_" + temp[1]).val();
		}else{
			$("#customfromnamespan_" + temp[1]).hide();
		}

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_from_name = from_name;
		
    });

    //delegate event to the 'custom from name' text
    $('#ls_box_email_rules').delegate('input.custom_from_name_text', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_from_name = $(this).val();
    });

    //delegate change event to the 'custom replyto email' dropdown
    $('#ls_box_email_rules').delegate('select.custom_replyto_email_dropdown', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		var replyto_email = $(this).val();
		
		if(replyto_email == 'custom'){
			$("#customreplytoemailspan_" + temp[1]).show();
			replyto_email = $("#customreplytoemailuser_" + temp[1]).val();
		}else{
			$("#customreplytoemailspan_" + temp[1]).hide();
		}

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_replyto_email = replyto_email;
		
    });

    //delegate event to the 'custom replyto email' text
    $('#ls_box_email_rules').delegate('input.custom_replyto_email_text', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_replyto_email = $(this).val();
    });

    //delegate event to the 'custom from email' text
    $('#ls_box_email_rules').delegate('input.custom_from_email', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_from_email = $(this).val();
    });

    //delegate event to the 'custom email subject' text
    $('#ls_box_email_rules').delegate('input.custom_email_subject', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_subject = $(this).val();
    });

    //delegate event to the 'custom email content' textarea
    $('#ls_box_email_rules').delegate('textarea.custom_email_content', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liemailrule_" + temp[1]).data('rule_properties').custom_content = $(this).val();
    });

    //attach event to 'plain text' checkbox
    $('#ls_box_email_rules').delegate('input.custom_plain_text', 'change', function(e) {
    	var temp = $(this).attr("id").split("_");

    	if($(this).prop("checked") == true){
			$("#liemailrule_" + temp[1]).data('rule_properties').custom_plain_text = 1;
		}else{
			$("#liemailrule_" + temp[1]).data('rule_properties').custom_plain_text = 0;
		}
    });

	//attach event to 'add email rule' link
	$("#ls_add_email_rule").click(function(){
		
		//duplicate the last rule group
		var last_rule_element = $("#ls_email_rules_group > li").last();
		var last_rule_id = last_rule_element.attr("id");

		last_rule_id_clean = parseInt(last_rule_id.replace("liemailrule_email",""));
		var new_rule_id    = last_rule_id_clean + 1;


		last_rule_element.clone(false).find("*[id],*[name],*[for]").each(function() {
			if($(this).attr("id") !== undefined){
				var current_id = $(this).attr("id"); 
				var new_id = current_id.replace("email" + last_rule_id_clean, "email" + new_rule_id);

				//rename the original id with the new id
				$(this).attr("id", new_id);
				$(this).attr("name", new_id);
			}else{
				var current_id = $(this).attr("for"); 
				var new_id = current_id.replace("email" + last_rule_id_clean, "email" + new_rule_id);

				$(this).attr("for", new_id);
			}
		}).end().attr("id","liemailrule_email" + new_rule_id ).appendTo('#ls_email_rules_group').hide().fadeIn();

		//rename the new rule main label
		$("#liemailrule_email" + new_rule_id + " .rule_title").text("Rule #" + new_rule_id);

		//copy the data for main rule properties
		$("#liemailrule_email" + new_rule_id).data('rule_properties',$.extend('{}', last_rule_element.data('rule_properties')));
		$("#liemailrule_email" + new_rule_id).data('rule_properties').rule_id = new_rule_id;

		
		//copy the data from the previous conditions to the new one
		$("#liemailrule_email" + new_rule_id + " .ls_email_rules_conditions > li:not('.ls_add_condition')").each(function(){
			var current_condition_id = $(this).attr("id");
			var old_condition_id = current_condition_id.replace("email" + new_rule_id,"email" + last_rule_id_clean);

			//rule_condition data is an object, we need to use extend so that the object won't get passed by reference, screwing all other elements data
			$(this).data('rule_condition',$.extend('{}',$("#" + old_condition_id).data('rule_condition')));
			$(this).data('rule_condition').target_rule_id = 'email' + new_rule_id;

			//remove the datapicker and rebuild it, with the events as well
			var current_datepicker = $(this).find(".hasDatepick");
			var current_datepicker_id = current_datepicker.attr("id");

			current_datepicker.next().next().remove();
			current_datepicker.next().remove();
			current_datepicker.remove();

			var temp = current_datepicker_id.split("_");
			var condition_id = temp[2];

			var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_email' + new_rule_id + '_' + condition_id +'" id="datepicker_email' + new_rule_id + '_' + condition_id +'"> ' +
									 '<span style="display:none"> <img id="datepickimg_email'+ new_rule_id + '_' + condition_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

			$('#conditionkeyword_email' + new_rule_id + '_' + condition_id).after(new_datepicker_tag);

			$('#datepicker_email' + new_rule_id + '_' + condition_id).datepick({ 
		    		onSelect: select_date_email,
		    		showTrigger: '#datepickimg_email' + new_rule_id + '_' + condition_id
			});
		});

		//copy all dropdown selection state
		var original_rule_dropdowns = $("#liemailrule_email" + last_rule_id_clean).find("select");
		var new_rule_dropdowns =  $("#liemailrule_email" + new_rule_id).find("select");
		new_rule_dropdowns.each(function(index,item){
			//set new select to value of old select
     		$(item).val(original_rule_dropdowns.eq(index).val());
		});

		return false;

	});

	/***************************************************************************************************************/	
	/* 8. Webhook Rules Pane								   												   	   */
	/***************************************************************************************************************/

	//attach event to 'enable rules to skip pages' checkbox
	$("#logic_webhook_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ls_box_webhook_rules .ls_box_content").slideDown();
			$(".logic_settings").data('logic_status').logic_webhook_enable = 1;
		}else{
			$("#ls_box_webhook_rules .ls_box_content").slideUp();
			$(".logic_settings").data('logic_status').logic_webhook_enable = 0;
		}
	});

	//delegate change event to the all/any dropdown
    $('#ls_box_webhook_rules').delegate('select.rule_all_any', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#liwebhookrule_" + temp[1]).data('rule_properties').rule_all_any = $(this).val();
    });

    //delegate change event into condition field name dropdown
	$('#ls_box_webhook_rules').delegate('select.condition_fieldname', 'change', function(e) {
			
			var new_element_name = $(this).val();
			var new_element_type = $("#ls_fields_lookup").data(new_element_name);

			$(this).parent().find('.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
			$(this).parent().removeClass('condition_date');

			//reset keyword
			$(this).parent().data('rule_condition').keyword = '';
			$(this).parent().find('.condition_keyword').val('');

			//display the appropriate condition type dropdown, depends on the field type
			//and make sure to update the condition property value when the field type has been changed
			if(new_element_type == 'money' || new_element_type == 'number'){
				$(this).parent().find('.condition_number,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_number').val();
			}else if(new_element_type == 'date' || new_element_type == 'europe_date'){
				$(this).parent().addClass('condition_date');
				$(this).parent().find('.condition_date,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'time' || new_element_type == 'time_showsecond' || new_element_type == 'time_24hour' || new_element_type == 'time_showsecond24hour'){
				$(this).parent().find('.condition_date,.condition_time').show();
				
				$(this).parent().find('.condition_time .conditiontime_second,.condition_time .conditiontime_ampm').hide();
				
				if(new_element_type == 'time'){
					$(this).parent().find('.condition_time .conditiontime_ampm').show();
				}else if(new_element_type == 'time_showsecond'){
					$(this).parent().find('.condition_time .conditiontime_ampm,.condition_time .conditiontime_second').show();
				}else if(new_element_type == 'time_showsecond24hour'){
					$(this).parent().find('.condition_time .conditiontime_second').show();
				}

				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
			}else if(new_element_type == 'checkbox'){
				$(this).parent().find('.condition_checkbox').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_checkbox').val();
			}else if(new_element_type == 'radio' || new_element_type == 'select'){
				//reset condition type
				$(this).parent().find('.condition_text').show().val('is');
				$(this).parent().data('rule_condition').condition = 'is';

				//reset condition keyword with dropdown values and display it
				$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
				$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
			}else{
				$(this).parent().find('.condition_text,input.text').show();
				$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_text').val();
			}

			$(this).parent().data('rule_condition').element_name = new_element_name;

    });
	
	//delegate change event to the condition type dropdown (for number, date. checkbox)
    $('#ls_box_webhook_rules').delegate('select.condition_number,select.condition_date,select.condition_checkbox', 'change', function(e) {
		$(this).parent().data('rule_condition').condition = $(this).val();
    });

    //delegate change event to the condition type dropdown (for other fields beside the above)
    $('#ls_box_webhook_rules').delegate('select.condition_text', 'change', function(e) {
    	var element_name = $(this).parent().data('rule_condition').element_name;
    	var element_type = $("#ls_fields_lookup").data(element_name);

    	var condition_type = $(this).val();
    	
    	//if the field type is radio/dropdown, check for the selected condition type
    	//if condition type = 'is'/'is_not' , display the dropdown
    	if(element_type == 'radio' || element_type == 'select'){
    		$(this).parent().find('.condition_keyword,.condition_select').hide();

    		if(condition_type == 'is' || condition_type == 'is_not'){
    			$(this).parent().find('.condition_select').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
    		}else{
    			$(this).parent().find('.condition_keyword').show();
    			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_keyword').val();
    		}
    	}

		$(this).parent().data('rule_condition').condition = condition_type;
    });

	//delegate change event to the condition select dropdown (only applicable for radio and select)
    $('#ls_box_webhook_rules').delegate('select.condition_select', 'change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();
    });

    //delegate event to the condition keyword text
    $('#ls_box_webhook_rules').delegate('input.condition_keyword', 'keyup mouseout change', function(e) {
		$(this).parent().data('rule_condition').keyword = $(this).val();		
    });

    //delegate event to the time condition inputs
    $('#ls_box_webhook_rules').delegate('input.conditiontime_input,select.conditiontime_input', 'keyup mouseout change', function(e) {
		
		var temp = $(this).attr("id").split("_");

		var hour_value 	 = parseInt($("#conditiontimehour_" + temp[1] + "_" + temp[2]).val(),10);
		var minute_value = parseInt($("#conditiontimeminute_" + temp[1] + "_" + temp[2]).val(),10);
		var second_value = parseInt($("#conditiontimesecond_" + temp[1] + "_" + temp[2]).val(),10);
		var ampm_value 	 = $("#conditiontimeampm_" + temp[1] + "_" + temp[2]).val();

		if(isNaN(hour_value)){
			hour_value = '00';
		}

		if(isNaN(minute_value)){
			minute_value = '00';
		}
		
		if(isNaN(second_value)){
			second_value = '00';
		}

		$("#liwebhookrule_" + temp[1] + "_" + temp[2]).data('rule_condition').keyword = hour_value.toString() + ':' + minute_value.toString() + ':' + second_value.toString() + ':' + ampm_value;
    });

    //attach event to 'delete webhook rules' icon
	$('#ls_box_webhook_rules').delegate('a.delete_liwebhookrule', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		if($("#ls_webhook_rules_group > li").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all rules. <br />You must have at least one rule.");
			$("#dialog-warning").dialog('open');
		}else{
			$("#liwebhookrule_" + rule_id).fadeOut(400,function(){
				$(this).remove();
			});
		}
		
		return false;
	});

	//attach click event to 'add rule condition' (+) icon
	$('#ls_box_webhook_rules').delegate('a.a_add_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		var new_id = $("#liwebhookrule_" + rule_id + " ul.ls_webhook_rules_conditions > li:not('.ls_add_condition')").length + 1;
		var old_id = new_id - 1;

		//duplicate the last rule condition
		var last_rule_element = $("#liwebhookrule_" + rule_id + " ul.ls_webhook_rules_conditions > li:not('.ls_add_condition')").last();
		last_rule_element.clone(false).data('rule_condition',$.extend('{}',last_rule_element.data('rule_condition'))).find("*[id],*[name]").each(function() {
			var temp = $(this).attr("id").split("_"); 
			
			//rename the original id with the new id
			$(this).attr("id", temp[0] + "_" + temp[1] + "_" + new_id);
			$(this).attr("name", temp[0] + "_" + temp[1] + "_" + new_id);
			
		}).end().attr("id","liwebhookrule_" + rule_id + "_" + new_id).insertBefore("#liwebhookrule_" + rule_id + " li.ls_add_condition").hide().fadeIn();

		//copy the value of the dropdowns
		$("#conditionwebhook_" + rule_id + "_" + new_id).val($("#conditionwebhook_" + rule_id + "_" + old_id).val());
		$("#conditiontext_" + rule_id + "_" + new_id).val($("#conditiontext_" + rule_id + "_" + old_id).val());
		$("#conditionnumber_" + rule_id + "_" + new_id).val($("#conditionnumber_" + rule_id + "_" + old_id).val());
		$("#conditiondate_" + rule_id + "_" + new_id).val($("#conditiondate_" + rule_id + "_" + old_id).val());
		$("#conditioncheckbox_" + rule_id + "_" + new_id).val($("#conditioncheckbox_" + rule_id + "_" + old_id).val());
		
		//reset the condition keyword  
		$("#conditionkeyword_" + rule_id + "_" + new_id).val('');
		$("#liwebhookrule_" + rule_id + "_" + new_id).data('rule_condition').keyword = '';

		//remove the datepicker and rebuild it, with the events as well
		$('#datepicker_' + rule_id + '_' + new_id).next().next().remove();
		$('#datepicker_' + rule_id + '_' + new_id).next().remove();
		$('#datepicker_' + rule_id + '_' + new_id).remove();

		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_' + rule_id + '_' + new_id +'" id="datepicker_' + rule_id + '_' + new_id +'"> ' +
								 '<span style="display:none"> <img id="datepickimg_'+ rule_id + '_' + new_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeyword_' + rule_id + '_' + new_id).after(new_datepicker_tag);

		$('#datepicker_' + rule_id + '_' + new_id).datepick({ 
	    		onSelect: select_date_webhook,
	    		showTrigger: '#datepickimg_' + rule_id + '_' + new_id
		});

		return false;
	});

	//delegate click event to the 'delete rule condition' (-) icon
    $('#ls_box_webhook_rules').delegate('a.a_delete_condition', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		if($("#liwebhookrule_" + rule_id + " ul.ls_webhook_rules_conditions > li:not('.ls_add_condition')").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all condition. <br />You must have at least one condition.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });

    //delegate event to the 'webhook URL' text
    $('#ls_box_webhook_rules').delegate('input.webhook_url', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_url = $(this).val();
    });

    //delegate change event to the 'HTTP Method' dropdown
    $('#ls_box_webhook_rules').delegate('select.webhook_method_dropdown', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		
		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_method = $(this).val();

    });

    //attach event to 'use http authentication' checkbox
	$('#ls_box_webhook_rules').delegate('input.webhook_enable_http_auth', 'change', function(e) {
		var temp = $(this).attr("id").split("_");

		if($(this).prop("checked") == true){
			$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_enable_http_auth = 1;
			$("#liwebhookrule_" + temp[1] + " div.webhook_http_auth_div").slideDown();
		}else{
			$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_enable_http_auth = 0;
			$("#liwebhookrule_" + temp[1] + " div.webhook_http_auth_div").slideUp();
		}
	});

	//delegate event to the 'HTTP username' text
    $('#ls_box_webhook_rules').delegate('input.webhook_http_username', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_http_username = $(this).val();
    });

    //delegate event to the 'HTTP password' text
    $('#ls_box_webhook_rules').delegate('input.webhook_http_password', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_http_password = $(this).val();
    });

    //attach event to 'use custom http headers' checkbox
	$('#ls_box_webhook_rules').delegate('input.webhook_enable_custom_http_headers', 'change', function(e) {
		var temp = $(this).attr("id").split("_");

		if($(this).prop("checked") == true){
			$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_enable_custom_http_headers = 1;
			$("#liwebhookrule_" + temp[1] + " div.webhook_http_header_div").slideDown();
		}else{
			$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_enable_custom_http_headers = 0;
			$("#liwebhookrule_" + temp[1] + " div.webhook_http_header_div").slideUp();
		}
	});

	//delegate event to the 'HTTP headers' text
    $('#ls_box_webhook_rules').delegate('textarea.webhook_custom_http_headers', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_custom_http_headers = $(this).val();
    });

    //delegate event to the 'Send Key-Value' radio button
    $('#ls_box_webhook_rules').delegate('input.webhook_data_format_key_value', 'change', function(e) {
    	var temp = $(this).attr("id").split("_");
    	
    	$("#liwebhookrule_" + temp[1] + " label.webhook_parameters_label").hide();
    	$("#liwebhookrule_" + temp[1] + " ul.ul_webhook_parameters").hide();
    	$("#liwebhookrule_" + temp[1] + " div.webhook_raw_div").hide();

    	if($(this).prop("checked") == true){
    		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_format = 'key-value';
    		$("#liwebhookrule_" + temp[1] + " label.webhook_parameters_label").show();
    		$("#liwebhookrule_" + temp[1] + " ul.ul_webhook_parameters").show();
    	}
    });

    //delegate event to the 'Send Raw Data' radio button
    $('#ls_box_webhook_rules').delegate('input.webhook_data_format_raw', 'change', function(e) {
    	var temp = $(this).attr("id").split("_");
    	
    	$("#liwebhookrule_" + temp[1] + " label.webhook_parameters_label").hide();
    	$("#liwebhookrule_" + temp[1] + " ul.ul_webhook_parameters").hide();
    	$("#liwebhookrule_" + temp[1] + " div.webhook_raw_div").hide();

    	if($(this).prop("checked") == true){
    		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_format = 'raw';
    		$("#liwebhookrule_" + temp[1] + " div.webhook_raw_div").show();
    	}
    });

    //delegate event to the 'HTTP headers' text
    $('#ls_box_webhook_rules').delegate('textarea.webhook_raw_data', 'keyup mouseout change', function(e) {
    	var temp = $(this).attr("id").split("_");

		$("#liwebhookrule_" + temp[1]).data('rule_properties').webhook_raw_data = $(this).val();
    });

    //delegate event to the 'add param' (+) icon on webhook key-value pairs
    $('#ls_box_webhook_rules').delegate('a.add_webhook_param', 'click', function(e) {
    	var temp = $(this).parent().prev().children().filter('.ns_param_name').children().attr("id").split("_");
    	var new_param_number = temp[1] + '_' + (parseInt(temp[2]) + 1);

		var new_param_markup = '<li class="ns_url_params">' + 
									'<div class="ns_param_name">' +
										'<input id="webhookname_' + new_param_number + '" name="webhookname_' + new_param_number + '" class="element text" style="width: 100%" value="" type="text">' +
									'</div>' +
									'<div class="ns_param_spacer">' + 
										'&#8674;' + 
									'</div>' + 
									'<div class="ns_param_value">' + 
										'<input id="webhookvalue_' + new_param_number + '" name="webhookvalue_' + new_param_number + '" class="element text" style="width: 100%" value="" type="text">' + 
									'</div>' + 
									'<div class="ns_param_control">' + 
										'<a class="delete_webhook_param" name="deletewebhookparam_' + new_param_number + '" id="deletewebhookparam_' + new_param_number + '" href="#"><img src="images/icons/51_green_16.png"></a>' +
									'</div>' + 
								'</li>';
		$("#liwebhookrule_webhook" + temp[1] + " .ns_url_add_param").before(new_param_markup);
		$("#liwebhookrule_webhook" + temp[1] + " .ul_webhook_parameters > li.ns_url_params:last").hide().slideDown();

		return false;
    });

	//delegate click event to the 'delete webhook param' (-) icon
    $('#ls_box_webhook_rules').delegate('a.delete_webhook_param', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		if($("#liwebhookrule_webhook" + temp[1] + " .ul_webhook_parameters > li.ns_url_params").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all parameter. <br />You must have at least one parameter.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });
    
	//attach event to 'add webhook rule' link
	$("#ls_add_webhook_rule").click(function(){
		
		//duplicate the last rule group
		var last_rule_element = $("#ls_webhook_rules_group > li").last();
		var last_rule_id = last_rule_element.attr("id");

		last_rule_id_clean = parseInt(last_rule_id.replace("liwebhookrule_webhook",""));
		var new_rule_id    = last_rule_id_clean + 1;


		last_rule_element.clone(false).find("*[id],*[name],*[for]").each(function() {
			
			if($(this).attr("id") !== undefined){
				var current_id = $(this).attr("id"); 
				
				var new_id = current_id.replace("webhook" + last_rule_id_clean, "webhook" + new_rule_id);
				
				new_id = new_id.replace("webhookname_" + last_rule_id_clean + "_","webhookname_" + new_rule_id + "_");
				new_id = new_id.replace("webhookvalue_" + last_rule_id_clean + "_","webhookvalue_" + new_rule_id + "_");
				new_id = new_id.replace("deletewebhookparam_" + last_rule_id_clean + "_","deletewebhookparam_" + new_rule_id + "_");

				//rename the original id with the new id
				$(this).attr("id", new_id);
				$(this).attr("name", new_id);
			}else{
				var current_id = $(this).attr("for"); 
				var new_id = current_id.replace("webhook" + last_rule_id_clean, "webhook" + new_rule_id);

				$(this).attr("for", new_id);
			}

		}).end().attr("id","liwebhookrule_webhook" + new_rule_id).appendTo('#ls_webhook_rules_group').hide().fadeIn();

		//rename the new rule main label
		$("#liwebhookrule_webhook" + new_rule_id + " .rule_title").text("Rule #" + new_rule_id);

		//copy the data for main rule properties
		$("#liwebhookrule_webhook" + new_rule_id).data('rule_properties',$.extend('{}', last_rule_element.data('rule_properties')));
		$("#liwebhookrule_webhook" + new_rule_id).data('rule_properties').rule_id = new_rule_id;

		
		//copy the data from the previous conditions to the new one
		$("#liwebhookrule_webhook" + new_rule_id + " .ls_webhook_rules_conditions > li:not('.ls_add_condition')").each(function(){
			var current_condition_id = $(this).attr("id");
			var old_condition_id = current_condition_id.replace("webhook" + new_rule_id,"webhook" + last_rule_id_clean);

			//rule_condition data is an object, we need to use extend so that the object won't get passed by reference, screwing all other elements data
			$(this).data('rule_condition',$.extend('{}',$("#" + old_condition_id).data('rule_condition')));
			$(this).data('rule_condition').target_rule_id = 'webhook' + new_rule_id;

			//remove the datapicker and rebuild it, with the events as well
			var current_datepicker = $(this).find(".hasDatepick");
			var current_datepicker_id = current_datepicker.attr("id");

			current_datepicker.next().next().remove();
			current_datepicker.next().remove();
			current_datepicker.remove();

			var temp = current_datepicker_id.split("_");
			var condition_id = temp[2];

			var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_webhook' + new_rule_id + '_' + condition_id +'" id="datepicker_webhook' + new_rule_id + '_' + condition_id +'"> ' +
									 '<span style="display:none"> <img id="datepickimg_webhook'+ new_rule_id + '_' + condition_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

			$('#conditionkeyword_webhook' + new_rule_id + '_' + condition_id).after(new_datepicker_tag);

			$('#datepicker_webhook' + new_rule_id + '_' + condition_id).datepick({ 
		    		onSelect: select_date_webhook,
		    		showTrigger: '#datepickimg_webhook' + new_rule_id + '_' + condition_id
			});
		});

		//copy all dropdown selection state
		var original_rule_dropdowns = $("#liwebhookrule_webhook" + last_rule_id_clean).find("select");
		var new_rule_dropdowns =  $("#liwebhookrule_webhook" + new_rule_id).find("select");
		new_rule_dropdowns.each(function(index,item){
			//set new select to value of old select
     		$(item).val(original_rule_dropdowns.eq(index).val());
		});

		return false;

	});
	
	/***************************************************************************************************************/	
	/* 9. Dialog Box for template variable																		   */
	/***************************************************************************************************************/
	
	$("#dialog-template-variable").dialog({
		modal: false,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		position: { my: "top", at: "top+175", of: window },
		draggable: true,
		resizable: false,
		buttons: [{
				text: 'Close',
				id: 'btn-change-theme-ok',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});

	$("a.tempvar_link").click(function(){
		$("#dialog-template-variable").dialog('open');
		return false;
	});

	$("#tempvar_help_trigger a").click(function(){
		if($(this).text() == 'more info'){
			$(this).text('hide info');
			$("#tempvar_help_content").slideDown();
			$("#tempvar_value").effect("pulsate", { times:3 }, 1500);
		}else{
			$(this).text('more info');
			$("#tempvar_help_content").slideUp();
		}
		return false;
	});

	//attach event to template variable dropdown
	$('#dialog-template-variable-input').bind('change', function() {
		$("#tempvar_value").text('{' + $(this).val() + '}');
	});

	//chosen multiselect for approve/deny
	$(".users-select-1-a").chosen({no_results_text: "Oops, nothing found!, width:100%"});
	$(".logic_approval_radio").click(function(){
		var logic_approver_enable = $("input[name='logic_approver_enable']:checked").val();
		$('.logic_approval_radio_div').hide();
		// $('.logic_approval_radio_div, #ls_box_entry_conditional_email').hide();

		if( logic_approver_enable == 1 ) {
			$('.logic_approval_radio_1, .approver-radio-buttons').show();
			$('input[name="selected_admin_check_1_a"]:first').attr('checked', true);
			$('.logic_approval_2').hide();
		}

		if( logic_approver_enable == 2 ){
			$('.logic_approval_2, .selected_admins_enabled_1_a, .logic_approval_radio_1').show();
			$('input[name="selected_admin_check_1_a"]').prop('checked', false);
			$('.approver-radio-buttons').hide();
		}

		if( logic_approver_enable != 1 ) {
			$('input[name="selected_admin_check_1_a"]').prop('checked', false);
		}

	});


	//start::for user approver section


	//approver section on main select change clone the list
	$('select[name="entry_conditional_email_user_select"]').on('change', function() {
		var user_id = $(this).val();
		if( user_id ) {
			//check if user is not already added in approver list
			var approvers_already = [];
			$("ul#ls_entry_approver_email > li").each(function(){
			   	approvers_id = $(this).attr('approver_id');
			   	approvers_already.push(approvers_id);
			});

			if($.inArray(user_id, approvers_already) !== -1) {
				alert('Approver already added.');
				return false;
			}


			var user_details = $.grep(allusers_json, function( user, i ) {
			  	return (user.user_id == user_id );
			});
			// console.log(user_details);
			if( user_details[0].user_email == '' && user_details[0].user_fullname == '' ) {
				alert('Required User details not avaible');
			} else {
				var new_rule_id;
				var last_rule_element = $("#ls_entry_approver_email > li").last();
				var last_rule_id = last_rule_element.attr("id");

				// console.log(last_rule_id);
				var cloned_li;
				var last_rule_id_clean;
				if (last_rule_id == null) {
					//means we are adding first li item
					new_rule_id = 1;
					cloned_li = $('.approver_clone_me').clone();
					last_rule_id_clean = 'n';
				} else {
					// cloned_li = $('.approver_clone_me').clone();
					cloned_li = $("#ls_entry_approver_email > li").last().clone();


					last_rule_id_clean = parseInt(last_rule_id.replace("liapproverrule_email",""));
					new_rule_id    = last_rule_id_clean + 1;	
				}

				

				cloned_li.removeClass('approver_clone_me');
				cloned_li.attr('approver_id', user_id);

				var current_id;
				var new_id;

				


				cloned_li.find("*[id],*[name],*[for]").each(function() {
					if($(this).attr("id") !== undefined){
						current_id = $(this).attr("id"); 
						// console.log('current_id:-'+current_id);
						// console.log('last_rule_id_clean:-'+last_rule_id_clean);
						// console.log('new_rule_id:-'+new_rule_id);

						new_id = current_id.replace("email" + last_rule_id_clean, "email" + new_rule_id);

						//rename the original id with the new id
						$(this).attr("id", new_id);
						$(this).attr("name", new_id);
					}else{
						current_id = $(this).attr("for"); 
						new_id = current_id.replace("email" + last_rule_id_clean, "email" + new_rule_id);

						$(this).attr("for", new_id);
					}
				}).end().attr("id","liapproverrule_email" + new_rule_id ).appendTo('#ls_entry_approver_email').hide().fadeIn();

				

				//rename the new rule main label
				$("#liapproverrule_email" + new_rule_id + " .approver_rule_no").text(new_rule_id);

				//rename username and email
				$("#liapproverrule_email" + new_rule_id + " .approver_username").text(user_details[0].user_fullname);				
				$("#liapproverrule_email" + new_rule_id + " .approver_user_email").text(user_details[0].user_email);
				$("#liapproverrule_email" + new_rule_id).data('rule_properties',$.extend('{}', $("#liapproverrule_email" + last_rule_id_clean).data('rule_properties')));
				$("#liapproverrule_email" + new_rule_id).data('rule_properties').rule_id = new_rule_id;
				$("#liapproverrule_email" + new_rule_id).data('rule_properties').user_id = user_id;

				//copy the data from the previous conditions to the new one
				$("#liapproverrule_email" + new_rule_id + " .ls_email_rules_conditions_approver > li:not('.ls_add_condition_approver')").each(function(){
					var current_condition_id = $(this).attr("id");
					var old_condition_id = current_condition_id.replace("email" + new_rule_id,"email" + last_rule_id_clean);
					//rule_condition data is an object, we need to use extend so that the object won't get passed by reference, screwing all other elements data
					$(this).data('rule_condition',$.extend('{}',$("#" + old_condition_id).data('rule_condition')));
					$(this).data('rule_condition').target_rule_id = 'email' + new_rule_id;


					//start::test datepicker here
					//remove the datapicker and rebuild it, with the events as well
					var current_datepicker = $(this).find(".hasDatepick");
					var current_datepicker_id = current_datepicker.attr("id");

					current_datepicker.next().next().remove();
					current_datepicker.next().remove();
					current_datepicker.remove();

					// console.log(current_datepicker);
					// console.log(current_datepicker_id);

					var temp = current_datepicker_id.split("_");
					var condition_id = temp[2];

					var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_email' + new_rule_id + '_' + condition_id +'" id="datepickerapprover_email' + new_rule_id + '_' + condition_id +'"> ' +
											 '<span style="display:none"> <img id="datepickapproverimg_email'+ new_rule_id + '_' + condition_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

					// console.log(new_datepicker_tag);

					$(this).find('#conditionkeywordapprover_email' + new_rule_id + '_' + condition_id).after(new_datepicker_tag);

					$(this).find('#datepickerapprover_email' + new_rule_id + '_' + condition_id).datepick({ 
				    		onSelect: select_date_approver,
				    		showTrigger: '#datepickapproverimg_email' + new_rule_id + '_' + condition_id
					});
					//end::test datepicker here



				});

				//copy all dropdown selection state
				var original_rule_dropdowns = $("#liapproverrule_email" + last_rule_id_clean).find("select");
				var new_rule_dropdowns =  $("#liapproverrule_email" + new_rule_id).find("select");
				new_rule_dropdowns.each(function(index,item){
					//set new select to value of old select
		     		$(item).val(original_rule_dropdowns.eq(index).val());
				});

				cloned_li.find('.condition_fieldname').trigger('change');

				return false;

			}
			
		}
	});
	

	//delegate change event into condition field name dropdown
	$('#ls_box_entry_conditional_email').delegate('select.condition_fieldname', 'change', function(e) {
		
		var new_element_name = $(this).val();
		var new_element_type = $("#ls_fields_lookup").data(new_element_name);

		$(this).parent().find('.condition_text,.condition_time,.condition_number,.condition_date,.condition_checkbox,.condition_keyword,.condition_select').hide();
		
		$(this).parent().removeClass('condition_date');

		//reset keyword
		$(this).parent().data('rule_condition').keyword = '';
		$(this).parent().find('.condition_keyword').val('');

		//display the appropriate condition type dropdown, depends on the field type
		//and make sure to update the condition property value when the field type has been changed
		if(new_element_type == 'money' || new_element_type == 'number'){
			$(this).parent().find('.condition_number,input.text').show();
			$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_number').val();
		}else if(new_element_type == 'date' || new_element_type == 'europe_date'){
			$(this).parent().addClass('condition_date');
			$(this).parent().find('.condition_date,input.text').show();
			$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
		}else if(new_element_type == 'time' || new_element_type == 'time_showsecond' || new_element_type == 'time_24hour' || new_element_type == 'time_showsecond24hour'){
			$(this).parent().find('.condition_date,.condition_time').show();
			
			$(this).parent().find('.condition_time .conditiontime_second,.condition_time .conditiontime_ampm').hide();
			
			if(new_element_type == 'time'){
				$(this).parent().find('.condition_time .conditiontime_ampm').show();
			}else if(new_element_type == 'time_showsecond'){
				$(this).parent().find('.condition_time .conditiontime_ampm,.condition_time .conditiontime_second').show();
			}else if(new_element_type == 'time_showsecond24hour'){
				$(this).parent().find('.condition_time .conditiontime_second').show();
			}

			$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_date').val();
		}else if(new_element_type == 'checkbox'){
			$(this).parent().find('.condition_checkbox').show();
			$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_checkbox').val();
		}else if(new_element_type == 'radio' || new_element_type == 'select'){
			//reset condition type
			$(this).parent().find('.condition_text').show().val('is');
			$(this).parent().data('rule_condition').condition = 'is';

			//reset condition keyword with dropdown values and display it
			$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
			$(this).parent().data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
		}else{
			$(this).parent().find('.condition_text,input.text').show();
			$(this).parent().data('rule_condition').condition = $(this).parent().find('.condition_text').val();
		}

		$(this).parent().data('rule_condition').element_name = new_element_name;
		//rule conditions are decided here

	});

	//attach click event to 'add rule condition' (+) icon
	$('#ls_box_entry_conditional_email').delegate('a.a_add_condition_approver', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		var new_id = $("#liapproverrule_" + rule_id + " ul > li:not('.ls_add_condition_approver')").length + 1;
		var old_id = new_id - 1;

		// console.log('new_id:-'+new_id);
		// console.log('old_id:-'+old_id);

		//duplicate the last rule condition
		var last_rule_element = $("#liapproverrule_" + rule_id + " ul > li:not('.ls_add_condition_approver')").last();
		// console.log(last_rule_element);

		last_rule_element.clone(false).data('rule_condition',$.extend('{}',last_rule_element.data('rule_condition'))).find("*[id],*[name]").each(function() {
			var temp = $(this).attr("id").split("_"); 
			
			//rename the original id with the new id
			$(this).attr("id", temp[0] + "_" + temp[1] + "_" + new_id);
			$(this).attr("name", temp[0] + "_" + temp[1] + "_" + new_id);
			
		}).end().attr("id","liapproverrule_" + rule_id + "_" + new_id).insertBefore("#liapproverrule_" + rule_id + " li.ls_add_condition_approver").hide().fadeIn();

		
		//copy the value of the dropdowns
		$("#approveremail_" + rule_id + "_" + new_id).val($("#approveremail_" + rule_id + "_" + old_id).val());
		$("#approvertext_" + rule_id + "_" + new_id).val($("#approvertext_" + rule_id + "_" + old_id).val());
		$("#approvernumber_" + rule_id + "_" + new_id).val($("#approvernumber_" + rule_id + "_" + old_id).val());
		$("#approverdate_" + rule_id + "_" + new_id).val($("#approverdate_" + rule_id + "_" + old_id).val());
		$("#approvercheckbox_" + rule_id + "_" + new_id).val($("#approvercheckbox_" + rule_id + "_" + old_id).val());
		
		//reset the condition keyword  
		$("#approverkeyword_" + rule_id + "_" + new_id).val('');
		// $("#liapproverrule" + rule_id + "_" + new_id).data('rule_condition').keyword = '';

		//remove the datepicker and rebuild it, with the events as well
		$('#datepickerapprover_' + rule_id + '_' + new_id).next().next().remove();
		$('#datepickerapprover_' + rule_id + '_' + new_id).next().remove();
		$('#datepickerapprover_' + rule_id + '_' + new_id).remove();

		var new_datepicker_tag = ' <input type="hidden" value="" name="datepicker_' + rule_id + '_' + new_id +'" id="datepickerapprover_' + rule_id + '_' + new_id +'"> ' +
								 '<span style="display:none"> <img id="datepickapproverimg_'+ rule_id + '_' + new_id +'" alt="Pick date." src="images/icons/calendar.png" class="trigger condition_date_trigger" style="vertical-align: top; cursor: pointer" /></span>';

		$('#conditionkeywordapprover_' + rule_id + '_' + new_id).after(new_datepicker_tag);

		$('#datepickerapprover_' + rule_id + '_' + new_id).datepick({ 
	    		onSelect: select_date_approver,
	    		showTrigger: '#datepickapproverimg_' + rule_id + '_' + new_id
		});

		return false;
	});

	//delegate click event to the 'delete rule condition' (-) icon
    $('#ls_box_entry_conditional_email').delegate('a.a_delete_condition_approver', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];

		if($("#liapproverrule_" + rule_id + " ul > li:not('.ls_add_condition_approver')").length <= 1){
			$("#dialog-warning").dialog("option", "title", "Unable to delete!");
			$("#dialog-warning-msg").html("You can't delete all condition. <br />You must have at least one condition.");
			$("#dialog-warning").dialog('open');
		}else{
			$(this).parent().fadeOut(function(){
				$(this).remove();
			});
		}

		return false;
    });

    //attach event to 'delete email rules' icon
	$('#ls_box_entry_conditional_email').delegate('a.delete_liapproverrule', 'click', function(e) {
		var temp = $(this).attr('id').split('_');
		var rule_id = temp[1];
		
		// if($("#ls_entry_approver_email > li").length <= 1){
		// 	$("#dialog-warning").dialog("option", "title", "Unable to delete!");
		// 	$("#dialog-warning-msg").html("You can't delete all rules. <br />You must have at least one rule.");
		// 	$("#dialog-warning").dialog('open');
		// }else{
			$("#liapproverrule_" + rule_id).fadeOut(400,function(){
				$(this).remove();
			});
		// }
		
		return false;
	});

	//delegate change event to the all/any dropdown
    $('#ls_box_entry_conditional_email').delegate('select.rule_all_any', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		$("#liapproverrule_" + temp[1]).data('rule_properties').rule_all_any = $(this).val();
    });


    //delegate change event to the 'email template' dropdown
    $('#ls_box_entry_conditional_email').delegate('select.approve_deny_template_name', 'change', function(e) {
		var temp = $(this).attr("id").split("_");
		
		$("#liapproverrule_" + temp[1]).data('rule_properties').template_name = $(this).val();

		// if($(this).val() == 'custom'){
		// 	$("#ls_email_custom_template_div_" + temp[1]).fadeIn();
		// }else{
		// 	$("#ls_email_custom_template_div_" + temp[1]).fadeOut();
		// }
    });    








	$('.get-info').click(function(){
		// console.log($("#liapproverrule_email1_1").data('rule_condition'));
		// console.log($("#liemailrule_email1_1").data('rule_condition'));
		// return false;
		console.log('clicked');


		var logic_approver_enable_process = $("input[name='logic_approver_enable']:checked").val();

		if( logic_approver_enable_process == 2 ) {
			var select_user_order_li_process = $("#sortable-approver-users > li");
			var user_order_process = [];
			if(select_user_order_li_process.length >= 1){
				select_user_order_li_process.each(function(index){
					var user_id = $(this).attr('data-multi-id');
					user_order_process.push({'order':index, 'user_id':user_id});
				});
			}
		}

		console.log(user_order_process);
		return false;



		//get email logic properties data
		var email_rule_properties_elements = $("#ls_entry_approver_email > li");
		var data_sent = [];
		if(email_rule_properties_elements.length >= 1){
			email_rule_properties_elements.each(function(index){
				data_sent[index] = $(this).data('rule_properties');
			});
		}
		console.log(data_sent);

		var email_rule_condition_elements = $("#ls_entry_approver_email ul.ls_email_rules_conditions_approver > li:not('.ls_add_condition_approver')");
		var data_sent_conditions 	= [];

		if(email_rule_condition_elements.length >= 1){
			email_rule_condition_elements.each(function(index){
				data_sent_conditions[index] = $(this).data('rule_condition');
			});
		}
		console.log(data_sent_conditions);

		/*original*/
		//get email logic properties data
		var email_rule_properties_elements = $("#ls_email_rules_group > li");
		var email_rule_properties_data 	   = new Array();
		
		if(email_rule_properties_elements.length >= 1){
			email_rule_properties_elements.each(function(index){
				email_rule_properties_data[index] = $(this).data('rule_properties');
			});
		}

		// console.log(email_rule_properties_data);

		var email_rule_condition_elements = $("#ls_email_rules_group ul.ls_email_rules_conditions > li:not('.ls_add_condition')");
		var email_rule_condition_data 	= new Array();

		if(email_rule_condition_elements.length >= 1){
			email_rule_condition_elements.each(function(index){
				email_rule_condition_data[index] = $(this).data('rule_condition');
			});
		}

		// console.log(email_rule_condition_data);
		/*original*/

		return false;
	});

	//on change multiselect update approver section
	$(".users-select-1-a").change(function(){
		var approver_select = $('select[name="entry_conditional_email_user_select"]');
		var selected_users_arr = $(this).val();
		// console.log(selected_users_arr);
		// var allusers_ids_json = [];
		// $.each(allusers_json, function(key, value){
		// 	allusers_ids_json.push(value.user_id);
		// });

		var selected_users_obj = $.grep(allusers_json, function( user, i ) {
		  	// return (user.user_id == user_id );
		  	return ($.inArray(user.user_id, selected_users_arr) !== -1 );
		});

		if( selected_users_obj.length >= 1 ) {

			//update select options for approver section
			approver_select.children('option:not(:first)').remove();
			$.each(selected_users_obj, function(key, value) {
		     	approver_select
			        .append($("<option></option>")
			        .attr("value",value.user_id)
			        .text(value.user_email));
			});

			//update list items rules in approver section
			var approver_rule_properties_li = $("#ls_entry_approver_email > li");
			
			if(approver_rule_properties_li.length >= 1){
				approver_rule_properties_li.each(function(index){
					var approver_id = $(this).attr('approver_id');
					if( $.inArray(approver_id, selected_users_arr) === -1 ) {
						$(this).remove();
					}
				});
			}


			//added diff logic than approver select to preserve order of already added users
			//update {Select User Order} lists
			var select_user_order_li = $("#sortable-approver-users > li");
			var select_user_order_already = [];
			if(select_user_order_li.length >= 1){
				select_user_order_li.each(function(index){
					var user_id = $(this).attr('data-multi-id');
					select_user_order_already.push(user_id);
					if( $.inArray(user_id, selected_users_arr) === -1 ) {
						$(this).remove();
					}
				});
			}

			//find newly added user
			var difference = $(selected_users_arr).not(select_user_order_already).get();

			var newly_added_user_obj = $.grep(allusers_json, function( user, i ) {
			  	return ($.inArray(user.user_id, difference) !== -1 );
			});

			//for every new added user add it to bottom of list
			$.each(newly_added_user_obj, function(key, value) {
			     	
				var new_list = "<li data-multi-email=\""+value.user_email+"\" data-multi-id=\""+value.user_id+"\" class=\"ui-state-default\"><span class=\"ui-icon ui-icon-arrowthick-2-n-s\"></span>"+value.user_email+"</li>";
				$('#sortable-approver-users').append(new_list);
			});

		} else {
			approver_select.children('option:not(:first)').remove();
			$("#ls_entry_approver_email > li").remove();
			$("#sortable-approver-users > li").remove();
		}

	});

	//initialize datepickers for approver section
	$("#ls_box_entry_conditional_email .rule_datepicker").each(function(index){
		var temp = $(this).attr('id').split('_');
		var element_id = temp[1] + '_' + temp[2];

		$('#datepickerapprover_' + element_id).datepick({ 
	    		onSelect: select_date_approver,
	    		showTrigger: '#datepickapproverimg_' + element_id
		});
	});

	//end::for user approver section

	/*********** Start POAM Rules ************/
	//attach event to 'enable rules to generate POAM reports' checkbox
	$("#logic_poam_enable").click(function(){
		if($(this).prop("checked") == true){
			$("#ls_box_poam_rules .ls_box_content").slideDown();
			$(".logic_settings").data('logic_status').logic_poam_enable = 1;
		}else{
			$("#ls_box_poam_rules .ls_box_content").slideUp();
			$(".logic_settings").data('logic_status').logic_poam_enable = 0;
		}
	});

	$('#ls_select_poam_rule').bind('change', function() {
		
		if($(this).val() == ''){
			return true;
		}
		//we added 'poam' prefix so that the generated id won't be the same with the show/hide logic fields
		var poam_id = $(this).val(); 

		//build the markup
		var li_markup = '';
		var condition_fieldname_markup = '';

		condition_fieldname_markup = $("#ls_select_fields_lookup").clone(false).attr("id","conditionpoam_" + poam_id + "").attr("name","conditionpoam_" + poam_id + "").show().outerHTML();

		li_markup 	+= 	'<li id="lipoamrule_'+ poam_id +'" style="display: none">' +
							'<table width="100%" cellspacing="0">' +
							  '<thead>' +
							    '<tr>' +
							      '<td>' + 
							         '<strong>Set POAMs to go into ' + $(this).find('option:selected').text() + ' if the following condition matches:</strong><a class="delete_lipoamrule" id="deletelipoamrule_'+ poam_id +'" href="#"><img src="images/icons/52_red_16.png"></a>' + 
							      '</td>' +
							    '</tr>' +
							  '</thead>' +
							  '<tbody>' +
							    '<tr>' +
							      '<td>' +
							      	'<ul class="ls_poam_rules_conditions">' +
							      		'<li id="lipoamrule_' + poam_id + '"> ' +
							      			 condition_fieldname_markup + ' Is ' +
											'<select id="conditionselect_'+ poam_id +'" name="conditionselect_' + poam_id + '" style="display: none" class="element select condition_select">' + 
												'<option value=""></option>' +  
											'</select>' + ' ' + "\n" +
							      	'</ul>' +
							      '</td>' +
							    '</tr>' +
							  '</tbody>' +
							'</table>' +
						'</li>';

		//append the rule markup
		$("#ls_poam_rules_group").prepend(li_markup);
		$("#lipoamrule_" + poam_id).hide();

		//remove the current element from the list of condition
		$("#conditionpoam_" + poam_id + " option[value=element_"+ poam_id +"]").remove();
		$("#conditionpoam_" + poam_id + " option[value^=element_"+ poam_id +"_]").remove(); //remove childs element (for checkbox)

		//diplay the condition operator, depends on the first field on the field list
		var first_field_element_name = $("#conditionpoam_" + poam_id + "").eq(0).val();
		var first_field_element_type = $("#ls_select_fields_lookup").data(first_field_element_name);
		var default_keyword = '';

		//populate options for condition_select
		$("#conditionselect_" + poam_id + "").html($("#" + first_field_element_name + "_lookup").html());

		if(first_field_element_type == 'select' || first_field_element_type == 'radio'){
			$("#conditiontext_" + poam_id + "").show();
			$("#conditionselect_" + poam_id + "").show();
			default_keyword =  $("#conditionselect_" + poam_id).eq(0).val();
		}

		$("#lipoamrule_" + poam_id).slideDown();

		//attach dom data
		$("#lipoamrule_" + poam_id).data('rule_condition',{"target_poam_id": poam_id,"element_name": first_field_element_name, "keyword": default_keyword});

		//remove the option from the dropdown
		$(this).find('option:selected').remove();
		
		if($("#ls_select_poam_rule option").length == 1){
			$("#ls_select_poam_rule option").text('No More POAM Available');
		}
	});

	//delegate change event into condition field name dropdown
	$('#ls_box_poam_rules').delegate('select.condition_fieldname', 'change', function(e) {
		var poam_id = $(this).attr('id').replace('conditionpoam_', '');
		var new_element_name = $(this).val();
		var new_element_type = $("#ls_select_fields_lookup").data(new_element_name);
		$(this).parent().find('.condition_select').hide();

		//display the appropriate condition type dropdown, depends on the field type
		//and make sure to update the condition property value when the field type has been changed
		if(new_element_type == 'radio' || new_element_type == 'select'){
			//reset condition keyword with dropdown values and display it
			$(this).parent().find('.condition_select').html($("#" + new_element_name + "_lookup").html()).show();
			$("#lipoamrule_" + poam_id).data('rule_condition').keyword = $(this).parent().find('.condition_select').eq(0).val();
		}
		$("#lipoamrule_" + poam_id).data('rule_condition').element_name = new_element_name;
	});
	//delegate change event to the condition select dropdown (only applicable for radio and select)
  $('#ls_box_poam_rules').delegate('select.condition_select', 'change', function(e) {
  	var poam_id = $(this).attr('id').replace('conditionselect_', '');
		$("#lipoamrule_" + poam_id).data('rule_condition').keyword = $(this).val();
  });

  //attach event to 'delete poam rules' icon
	$('#ls_box_poam_rules').delegate('a.delete_lipoamrule', 'click', function(e) {
		var poam_id = $(this).attr('id').replace('deletelipoamrule_', '');

		//restore field dropdown values
		$("#ls_select_poam_rule").html($("#ls_select_poam_rule_lookup").html());

		$("#lipoamrule_" + poam_id).fadeOut(400,function(){
			$(this).remove();

			$("#ls_poam_rules_group > li").each(function(){
				var cur_poam_id = $(this).attr('id').replace('lipoamrule_', '');
				
				$("#ls_select_poam_rule option[value="+ cur_poam_id +"]").remove();			
			});
		});
		
		return false;
	});
	/*********** End POAM Rules ************/
});