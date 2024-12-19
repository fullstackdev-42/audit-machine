$(function(){
	$("form.itauditm").data('active_element','');
	var field_highlight_color = $("form.itauditm").data('highlightcolor');
	
	//attach event handler to all form fields, to highlight the selected list (except for matrix field) 
	$("form.itauditm :input").bind('click focus',function(){
		var current_li = $(this).closest("li").not('.matrix').not('.buttons');
		$("form.itauditm").data('active_element',current_li.attr('id'));	
		
		if(current_li.hasClass('highlighted') != true){
			$("form.itauditm li.highlighted").removeClass('highlighted'); //remove any previous highlight
			
			if(field_highlight_color != ''){
				//if the user goes through fields too fast, we need to make sure to remove the previous highlight coming from previous animation
				current_li.siblings().not('#li_resume_email').stop(); //stop the highlight animation
				current_li.siblings().not('#li_resume_email, #pagination_header, #li_buttons').css('background-color',''); //remove the remaining color style
				
				current_li.animate({ backgroundColor: field_highlight_color }, 500, 'swing',function(){
					if(current_li.attr('id') == $("form.itauditm").data('active_element')){
						current_li.addClass('highlighted').css('background-color','');
						current_li.siblings().not('#li_resume_email, #pagination_header, #li_buttons').css('background-color',''); //remove the remaining color style
					}else{
						current_li.css('background-color','');
					}
				});
			}else{
				//if background pattern/image being used, simply add the highlight class
				current_li.addClass('highlighted').css('background-color','');
			}
		}
		
	});

	$("form.itauditm li").click(function(){
		$("form.itauditm li").removeClass("highlighted");
		$(this).addClass("highlighted");
	});

	$(window).on('load', function() {
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		const element_id = urlParams.get('element_id');
		$(`#li_${element_id}`).click();
	});


	$('#submit_form').click(function(){
		var message_div = $('div#processing-dialog');
		message_div.css("visibility", "visible");
	});
	
	//if the form has file upload field, attach custom submit handler to the form
	//make sure all files are being uploaded prior submitting the form
	if($("#main_body div.file_queue").length > 0){
		$('form.itauditm').submit(function() {
			
			if($("form.itauditm").data('form_submitting') !== true){
				
				$("#li_buttons > input[type=submit],#li_buttons > input[type=image]").prop("disabled",true);
				
				$("form.itauditm").data('form_submitting',true);
				upload_all_files();
				return false;
			}else{
				return true;	
			}
		});
		
	}else{
		$('form.itauditm').submit(function() {
			$("#li_buttons > input[type=submit],#li_buttons > input[type=image]").prop("disabled",true);		
		});
	}
	
	//primary and secondary buttons are being disabled upon submit
	//thus we need to attach additional handler to send the clicked button as hidden variable
	$("#submit_secondary,#submit_img_secondary").click(function(){
		$("#li_buttons").append('<input type="hidden" name="submit_secondary" value="1" />');
	});

	$("#submit_primary,#submit_img_primary").click(function(){
		$("#li_buttons").append('<input type="hidden" name="submit_primary" value="1" />');
	});

	$("#review_submit").click(function(){
		$("#li_buttons").append('<input type="hidden" name="review_submit" value="1" />');
	});

	$("#review_back").click(function(){
		$("#li_buttons").append('<input type="hidden" name="review_back" value="1" />');
	});
	
	//if the form has resume enabled, attach event handler to the resume checkbox
	if($("#li_resume_checkbox").length > 0){
		$('#element_resume_checkbox').bind('change', function() {
			if($(this).prop("checked") == true){
				//display the email input and change the submit button
				$("#li_resume_email").show();
				$("#li_resume_email_top").show();
				$('#element_resume_checkbox_top').prop("checked", true);

				//hide all existing buttons
				$("#li_buttons > input").hide();
				$("#li_buttons_top > input").hide();
				
				//add the save form button
				$("#li_buttons").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" name="button_save_form" class="button_text" id="button_save_form">');
				$("#li_buttons_top").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" class="button_text" id="button_save_form_top">');
			} else {
				//hide the email input and restore the original submit button
				$("#li_resume_email").hide();
				$("#li_resume_email_top").hide();
				$('#element_resume_checkbox_top').prop("checked", false);

				$("#button_save_form").remove();
				$("#button_save_form_top").remove();
				$("#li_buttons > input").show();
				$("#li_buttons_top > input").show();
			}

			if($("html").hasClass("embed")){
				$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
			}
		});
		
		$('#element_resume_checkbox_top').bind('change', function() {
			if($(this).prop("checked") == true){
				//display the email input and change the submit button
				$("#li_resume_email_top").show();
				$("#li_resume_email").show();
				$('#element_resume_checkbox').prop("checked", true);

				//hide all existing buttons
				$("#li_buttons > input").hide();
				$("#li_buttons_top > input").hide();

				//add the save form button
				$("#li_buttons").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" name="button_save_form" class="button_text" id="button_save_form">');
				$("#li_buttons_top").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" class="button_text" id="button_save_form_top">');
			} else {
				//hide the email input and restore the original submit button
				$("#li_resume_email").hide();
				$("#li_resume_email_top").hide();
				$('#element_resume_checkbox').prop("checked", false);

				$("#button_save_form").remove();
				$("#button_save_form_top").remove();
				$("#li_buttons > input").show();
				$("#li_buttons_top > input").show();
			}

			if($("html").hasClass("embed")){
				$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
			}
		});

		//if the user entered invalid address, the 'data-resumebutton' will contain value 1
		//we need to display the save form button again and hide others
		if($("#li_resume_email").data("resumebutton") == '1'){
			//hide all existing buttons
			$("#li_buttons > input").hide();
			$("#li_buttons_top > input").hide();
			
			//add the save form button
			$("#li_buttons").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" name="button_save_form" class="button_text" id="button_save_form">');
			$("#li_buttons_top").append('<input type="button" value="' + $("#li_resume_email").data("resumelabel") + '" class="button_text" id="button_save_form_top">');
		}
		
		$(document).on("click", "#button_save_form", function(){
			$("#li_buttons").append('<input type="hidden" id="save_form_resume_later" name="save_form_resume_later" value="1" />');
			$("#li_buttons").append('<input type="hidden" id="generate_resume_url" name="generate_resume_url" value="1" />');
			if(window.autoMapping == "disabled") { // submit will be handled by auto-mapping since we need it to finish first
				$("form.itauditm").submit();
			}
		});

		$(document).on("click", "#button_save_form_top", function(){
			$("#button_save_form").click();
		});
		
		//attach additional event handler to the form submit
		$('form.itauditm').submit(function(){
			if($("#li_buttons > input:visible").attr("id") == 'button_save_form'){
				$("#li_buttons").append('<input type="hidden" id="generate_resume_url" name="generate_resume_url" value="1" />');
			}
		});

		$(document).on('change', '#element_resume_email', function(){
			$('#element_resume_email_top').val($('#element_resume_email').val());
		});

		$(document).on('change', '#element_resume_email_top', function(){
			$('#element_resume_email').val($('#element_resume_email_top').val());
		});		
	}
	
	$(document).on('click', '#submit_form_top', function(){
		$('#submit_form').click();
	});

	$(document).on('click', '#submit_primary_top', function(){
		$('#submit_primary').click();
	});

	$(document).on('click', '#submit_secondary_top', function(){
		$('#submit_secondary').click();
	});

	$(document).on('click', '#exit_form_view', function(){
		window.location.href = "/auditprotocol/manage_forms.php";
	});
		
	//if the form has payment enabled and the total is being displayed into the form, we need to calculate the total
	//and attach event handler to price-assigned fields
	if($(".total_payment").length > 0){
		
		calculate_total_payment();
		
		//attach event handler on radio buttons with price assigned
		$('#main_body li[data-pricefield="radio"]').delegate('input.radio', 'click', function(e) {
			var selected_radio = $(this);
			var temp = selected_radio.attr("id").split('_');
			var element_id = temp[1];
			
			var pricedef = selected_radio.data('pricedef');
			if(pricedef == null){
				pricedef = 0;
			}

			var quantity_field   = $("#main_body input[data-quantity_link=element_"+ element_id +"]");
			var current_quantity = 1;

			if(quantity_field.length > 0){
				current_quantity = parseFloat(quantity_field.val());			
				if(isNaN(current_quantity) || current_quantity < 0){
					current_quantity = 0;
				}
			}
			

			$("#li_" + element_id).data("pricevalue",pricedef * current_quantity);
			calculate_total_payment();
		});
		
		//attach event handler on checkboxes with price assigned
		$('#main_body li[data-pricefield="checkbox"]').delegate('input.checkbox', 'click', function(e) {
			
			var temp = $(this).attr("id").split('_');
			var element_id = temp[1];
			
			var child_checkboxes = $("#li_" + element_id + " input.checkbox");
			var total_price = 0;

			child_checkboxes.each(function(index){
				if($(this).data('pricedef') != null && $(this).prop("checked") == true){
					var quantity_field   = $("#main_body input[data-quantity_link="+ $(this).attr("id") +"]");
					var current_quantity = 1;

					if(quantity_field.length > 0){
						current_quantity = parseFloat(quantity_field.val());			
						if(isNaN(current_quantity) || current_quantity < 0){
							current_quantity = 0;
						}
					}

					total_price += ($(this).data('pricedef') * current_quantity);
				}
			});
			
			$("#li_" + element_id).data("pricevalue",total_price);
			calculate_total_payment();
		});
		
		//attach event handler on dropdown with price assigned
		$('#main_body li[data-pricefield="select"]').delegate('select', 'change', function(e) {
			var temp = $(this).attr("id").split('_');
			var element_id = temp[1];
			
			var pricedef = $(this).find('option:selected').data('pricedef');
			
			if(pricedef == null){
				pricedef = 0;
			}

			var quantity_field   = $("#main_body input[data-quantity_link=element_"+ element_id +"]");
			var current_quantity = 1;

			if(quantity_field.length > 0){
				current_quantity = parseFloat(quantity_field.val());			
				if(isNaN(current_quantity) || current_quantity < 0){
					current_quantity = 0;
				}
			}
			
			$("#li_" + element_id).data("pricevalue",pricedef * current_quantity);
			calculate_total_payment();
		});
		
		//attach event handler to money field (dollar, euro, etc)
		$('#main_body li[data-pricefield="money"]').delegate('input.text','keyup mouseout change', function(e) {
			var temp = $(this).attr("id").split('_');
			var element_id = temp[1];
			
			var price_value = $("#element_" + element_id + "_1").val() + '.' + $("#element_" + element_id + "_2").val();
			price_value = parseFloat(price_value);
			if(isNaN(price_value)){
				price_value = 0;
			}

			var quantity_field   = $("#main_body input[data-quantity_link=element_"+ element_id +"]");
			var current_quantity = 1;

			if(quantity_field.length > 0){
				current_quantity = parseFloat(quantity_field.val());			
				if(isNaN(current_quantity) || current_quantity < 0){
					current_quantity = 0;
				}
			}
			
			$("#li_" + element_id).data("pricevalue",price_value * current_quantity);
			calculate_total_payment();
		});
		
		//attach event handler to simple money field (yen)
		$('#main_body li[data-pricefield="money_simple"]').delegate('input.text','keyup mouseout change', function(e) {
			var temp = $(this).attr("id").split('_');
			var element_id = temp[1];
			
			var price_value = $(this).val();
			price_value = parseFloat(price_value);
			if(isNaN(price_value)){
				price_value = 0;
			}

			var quantity_field   = $("#main_body input[data-quantity_link=element_"+ element_id +"]");
			var current_quantity = 1;

			if(quantity_field.length > 0){
				current_quantity = parseFloat(quantity_field.val());			
				if(isNaN(current_quantity) || current_quantity < 0){
					current_quantity = 0;
				}
			}
			
			$("#li_" + element_id).data("pricevalue",price_value * current_quantity);
			calculate_total_payment();
		});

		//attach event handler to the number field that has 'quantity' enabled
		$("#main_body input[data-quantity_link]").bind('keyup mouseout change', function() {
			var linked_field_id = $(this).data("quantity_link");
			var temp = linked_field_id.split('_');

			var current_quantity = parseFloat($(this).val());
			if(isNaN(current_quantity) || current_quantity < 0){
				current_quantity = 0;
			}

			//find linked field and trigger the event handle for that field, based on the field type
			var linked_field_type = $('#li_' + temp[1]).data("pricefield");

			if(linked_field_type == 'radio'){
				var selected_radio = $("input[name="+ linked_field_id +"]:checked");
				
				if(selected_radio.length > 0){
					var temp = selected_radio.attr("id").split('_');
					var element_id = temp[1];
					
					var pricedef = selected_radio.data('pricedef');
					if(pricedef == null){
						pricedef = 0;
					}				
				
					$("#li_" + element_id).data("pricevalue",pricedef * current_quantity);
					calculate_total_payment();
				}

			}else if(linked_field_type == 'select'){
				
				var element_id = temp[1];
				var pricedef = $('#' + linked_field_id).find('option:selected').data('pricedef');
				
				if(pricedef == null){
					pricedef = 0;
				}

				$("#li_" + element_id).data("pricevalue",pricedef * current_quantity);
				calculate_total_payment();
			}else if(linked_field_type == 'checkbox'){				
				var element_id = temp[1];
				
				var child_checkboxes = $("#li_" + element_id + " input.checkbox");
				var total_price = 0;

				child_checkboxes.each(function(index){
					if($(this).data('pricedef') != null && $(this).prop("checked") == true){
						var quantity_field   = $("#main_body input[data-quantity_link="+ $(this).attr("id") +"]");
						var current_quantity = 1;

						if(quantity_field.length > 0){
							current_quantity = parseFloat(quantity_field.val());			
							if(isNaN(current_quantity) || current_quantity < 0){
								current_quantity = 0;
							}
						}

						total_price += ($(this).data('pricedef') * current_quantity);
					}
				});
				
				$("#li_" + element_id).data("pricevalue",total_price);
				calculate_total_payment();
			}else if(linked_field_type == 'money'){
				var element_id = temp[1];
				var price_value = $("#element_" + element_id + "_1").val() + '.' + $("#element_" + element_id + "_2").val();
				
				price_value = parseFloat(price_value);
				if(isNaN(price_value)){
					price_value = 0;
				}

				$("#li_" + element_id).data("pricevalue",price_value * current_quantity);
				calculate_total_payment();
			}else if(linked_field_type == 'money_simple'){
				var element_id = temp[1];
				var price_value = $("#element_" + element_id).val();
				
				price_value = parseFloat(price_value);
				if(isNaN(price_value)){
					price_value = 0;
				}

				$("#li_" + element_id).data("pricevalue",price_value * current_quantity);
				calculate_total_payment();
			}

		});
		
		//trigger the event handler on all number fields that has 'quantity' enabled, to initialize the calculation
		$("#main_body input[data-quantity_link]").change();
		
	}

	
	//attach event handler to textarea field to handle resizing
	if($("html").hasClass("embed")){
		$("#main_body textarea.textarea").bind('keyup mouseout mousemove change', function() {
			$.postMessage({la_iframe_height: $('body').outerHeight(true)}, '*', parent );
		});		    	
	}
	
	//if the password box is being displayed, add the class 'no_guidelines into the main_body
	if($("form.itauditm ul.password").length > 0){
		$("#main_body").addClass('no_guidelines');
	}

	//workaround for mobile safari, to allow tapping on label
	$('form.itauditm label').click(function(){});

	$('#switch-form-responsive').click(function(event){
		if (event.target.checked) {
			$("#form_container").css({"max-width": 993});
			$(".all-elements .first-column").addClass("col-lg-6");
			$(".all-elements .second-column").addClass("col-lg-6");
		} else {
			$("#form_container").css({"max-width": 640});
			$(".all-elements .first-column").removeClass("col-lg-6");
			$(".all-elements .second-column").removeClass("col-lg-6");
		}
	});
	//workaround for any Safari browsers, when the form being embedded
	//we need to set the cookies here
	if($("html").hasClass("embed") && navigator.userAgent.indexOf('Safari') != -1 && document.cookie.indexOf("la_safari_cookie_fix") < 0){
		$.postMessage({run_safari_cookie_fix: '1'}, '*', parent );
	}

	//generate share link on share button click
	/*$('.create_share_link').click(function(e){
		e.preventDefault();
        //get current element ID
        var element_id_auto =  $(this).attr('data-element-id');
        //check if url already contains old element ID
        var element_id_already =  $(this).attr('data-element-id-already');
        //select hidden element
        var element_share_link = $('#element_share_link');
        var current_url = window.location.href;
        //remove #main_body from url if exists
        current_url = current_url.replace("#main_body", "");
        //if old element ID already exists then remove it
        if( element_id_already )
        	current_url = current_url.replace("&element_id_auto="+element_id_already, "");
        
        //add new element ID
        element_share_link.val(current_url+'&element_id_auto='+element_id_auto).focus().select();

        document.execCommand('copy');
        $(this).after('<label class="copied_to_clipboard">Copied to clipboard</label>');
        $(this).parent('div').find("label.copied_to_clipboard").fadeOut(1200, function(){
		  	$(this).remove();
		});
		return false;
    });*/

    //Search element by title
	// clear currently displayed search suggestions 
	function clearResults () { 
		document.getElementById('results-container').innerHTML = ""; 
	}

	// make request on keyup 
	document.getElementById('target-element').onkeyup = function() {
		clearResults();
		var input = this.value.replace(/^\s|\s $/, "");
		if (input.length > 1) {
			searchForData(input); 
		} else {
			$("#go-to-target-element").attr({"disabled":true});
		}
	} 

	// logic for requesting results matching search input from database 
	function searchForData(value) {
		$("#loader-img").show();
		$("#go-to-target-element").attr({"disabled":true});
		var form_id = $("#target-element").data("form-id");
		$.ajax({
		   	type: "POST",
			async: true,
			url: "ajax-requests.php",
			data: {
				form_id: form_id,
				search_keyword: value,
				action: "search_element"
			},
			cache: false,
			global: true,
			error: function(xhr,text_status,e){
				clearResults();
				document.getElementById('results-container').innerHTML = "Unable to search form elements!";
				$("#loader-img").hide();
			},
			success: function(response_data){
				clearResults();
				data = response_data;
				var numberOfResults = data.split('class="go-to-field-li"').length - 1;
				if (numberOfResults && numberOfResults == 1) {
					var wrap = document.createElement("ul");
					wrap.classList.add("resultList");
					wrap.innerHTML = data;
					document.getElementById('results-container').appendChild(wrap);

					// if input field matches the only result, auto click the only result
					var inputFieldValue = document.querySelector("#target-element").value.trim().toLowerCase();
					var resultValue     = document.querySelector(".go-to-field-li").innerText.trim().toLowerCase();
					if(inputFieldValue == resultValue) {
						document.querySelector(".go-to-field-li").click();
						document.querySelector("#target-element").blur();
					}
				} else {
					var wrap = document.createElement("ul");
					wrap.classList.add("resultList");
					wrap.innerHTML = data;
					document.getElementById('results-container').appendChild(wrap);
				}
				$("#loader-img").hide();
			}
		});
	}

	// on element clicked, set values 
	document.addEventListener("click", function(e){ 
		if(e.target.classList.contains("go-to-field-li")) {
			$("#target-element").val(e.target.innerText);
			$("#target-element").attr("data-field-link", e.target.getAttribute('data-field-link'));
			$("#go-to-target-element").attr({"disabled":false});
			clearResults();
		} 
	});

	$(document).on("click", "#go-to-target-element", function(){
		var field_link = $("#target-element").attr("data-field-link");
		var current_link = window.location.href.split('?')[0];
		var forward_link = "";
		if(field_link != "") {
			if(current_link.indexOf("view.php") > -1) {
				var url = new URL(window.location.href);
				var company_id = url.searchParams.get("company_id");
				var entry_id = url.searchParams.get("entry_id");
				forward_link = current_link + "?id=" + field_link + "&company_id=" + company_id + "&entry_id=" + entry_id;
			} else if(current_link.indexOf("edit_entry.php") > -1) {
				var url = new URL(window.location.href);
				var company_id = url.searchParams.get("company_id");
				var entry_id = url.searchParams.get("entry_id");
				forward_link = current_link + "?form_id=" + field_link + "&company_id=" + company_id + "&entry_id=" + entry_id;
			}
			window.location.href = forward_link;
		}
	});
});

/** Payment Functions **/
function calculate_total_payment(){
	var total_payment = 0;
	
	//get totals from all visible fields (hidden fields due to logic shouldn't be included)
	$("#main_body li[data-pricevalue]:visible").each(function(index){
		total_payment += parseFloat($(this).data('pricevalue'));
	});
	
	//get totals from all fields intentionally hidden using custom css class "hidden"
	$("#main_body li[data-pricevalue].hidden").each(function(index){
		total_payment += parseFloat($(this).data('pricevalue'));
	});

	total_payment += parseFloat($('.total_payment').data('basetotal'));
	
	$(".total_payment var").text(total_payment.toFixed(2));
}

/** Date Picker Functions **/
function select_date_casecade(dates){
	var ids = $(this).attr("id").split('_');
	
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_1').val(dates.length ? dates[0].getMonth() + 1 : ''); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_2').val(dates.length ? dates[0].getDate() : ''); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_3').val(dates.length ? dates[0].getFullYear() : '');
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_1').change(); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_2').change(); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_3').change(); 
}

function select_europe_date_casecade(dates){
	var ids = $(this).attr("id").split('_');
	
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_2').val(dates.length ? dates[0].getMonth() + 1 : ''); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_1').val(dates.length ? dates[0].getDate() : ''); 
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_3').val(dates.length ? dates[0].getFullYear() : '');
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_1').change();  
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_2').change();  
    $('#element_' + ids[1] + '_' + ids[2] + '_' + ids[3] + '_3').change();  
}
function select_date(dates){
	var ids = $(this).attr("id").split('_');
	
    $('#element_' + ids[1] + '_1').val(dates.length ? dates[0].getMonth() + 1 : ''); 
    $('#element_' + ids[1] + '_2').val(dates.length ? dates[0].getDate() : ''); 
    $('#element_' + ids[1] + '_3').val(dates.length ? dates[0].getFullYear() : '');
    $('#element_' + ids[1] + '_1').change(); 
    $('#element_' + ids[1] + '_2').change(); 
    $('#element_' + ids[1] + '_3').change(); 
}

function select_europe_date(dates){
	var ids = $(this).attr("id").split('_');
	
    $('#element_' + ids[1] + '_2').val(dates.length ? dates[0].getMonth() + 1 : ''); 
    $('#element_' + ids[1] + '_1').val(dates.length ? dates[0].getDate() : ''); 
    $('#element_' + ids[1] + '_3').val(dates.length ? dates[0].getFullYear() : '');
    $('#element_' + ids[1] + '_1').change();  
    $('#element_' + ids[1] + '_2').change();  
    $('#element_' + ids[1] + '_3').change();  
}

/** File Upload Functions **/
function remove_attachment(filename,form_id,element_id,holder_id,is_db_live,key_id){
	
	var itauditmachine_path = '';
	if (typeof __itauditmachine_path != 'undefined'){
		itauditmachine_path = __itauditmachine_path;
	}

	$("#" + holder_id + " > div.cancel img").attr("src", itauditmachine_path + "images/loader_small_grey.gif");
	$.ajax({
		type: "POST",
		async: true,
		url: itauditmachine_path + "delete_file_upload.php",
		data: {
			filename: filename,
			form_id: form_id,
			element_id: element_id,
			holder_id: holder_id,
			is_db_live: is_db_live,
			key_id: key_id,
			file_upload_synced: 0
		},
		cache: false,
		global: true,
		dataType: "json",
		error: function(xhr,text_status,e){
		   //remove the delete progress on error
		   $("#" + holder_id + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
		   alert('Error! Unable to delete file.');
		},
		success: function(response_data){
		   if(response_data.status == 'ok'){
			   if(is_support_html5_uploader()){
			   	   try{
			   	   		$("#element_" + response_data.element_id).uploadifive('cancel',$("#" + response_data.holder_id).data('file'));
			   	   }catch(e){}
			   	   $("#" + response_data.holder_id).fadeOut("slow",function(){$(this).remove();});
			   }else{
			       $("#" + response_data.holder_id).fadeOut("slow",function(){$(this).remove();});
			   }
		   }else{
			   //unknown error, response json improperly formatted
			  $("#" + holder_id + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
			  alert('Error while deleting the file. Please try again.');
		   }
		   
		}, //end on ajax success
		complete:function(){}
	}); //end ajax call
	
}

function remove_synced_attachment(filename, element_machine_code, file_class_name, company_id){
	
	var itauditmachine_path = '';
	if (typeof __itauditmachine_path != 'undefined'){
		itauditmachine_path = __itauditmachine_path;
	}

	$("." + file_class_name + " > div.cancel img").attr("src", itauditmachine_path + "images/loader_small_grey.gif");
	$.ajax({
		type: "POST",
		async: true,
		url: itauditmachine_path + "delete_file_upload.php",
		data: {
			filename: filename,
			element_machine_code: element_machine_code,
			company_id: company_id,
			file_upload_synced: 1
		},
		cache: false,
		global: true,
		dataType: "json",
		error: function(xhr,text_status,e){
		   //remove the delete progress on error
		   $("." + file_class_name + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
		   alert('Error! Unable to delete file.');
		},
		success: function(response_data){
			if(response_data.status == 'ok'){
				$("." + file_class_name).fadeOut("slow",function(){$(this).remove();});
			}else{
				//unknown error, response json improperly formatted
				$("." + file_class_name + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
				alert('Error while deleting the file. Please try again.');
			}
		   
		}, //end on ajax success
		complete:function(){}
	}); //end ajax call	
}

function check_upload_queue(element_id,is_multi,queue_limit,alert_message){
	//check for queue limit
	if(is_multi == true){
		var queue_children = $("#element_" + element_id + "_queue").children().not('.uploadifyError');
		if(queue_children.length > queue_limit){
			alert(alert_message);
			
			for(i=0;i<=queue_children.length;i++){
				if(i>=queue_limit){
					$("#element_" + element_id).uploadifyCancel(queue_children.eq(i).attr('id').slice(-6));
				}
			}
		}
	}	
}

function upload_all_files(){
	if(is_support_html5_uploader()){
		$("#main_body input.file").uploadifive('upload');
		if($("#main_body div.uploadifive-queue-item").not('.complete').not('.error').length < 1){
			if(window.autoMapping !== "enabled") { // submit will be handled by auto-mapping since we need it to finish first
				$('form.itauditm').submit();
			}
		}
	}else{
		$("#main_body div.uploadifyQueueItem").not('.completed').parent().siblings('input.element').eq(0).uploadifyUpload();
		if($("#main_body div.uploadifyQueueItem").not('.completed').length < 1){
			if(window.autoMapping !== "enabled") { // submit will be handled by auto-mapping since we need it to finish first
				$('form.itauditm').submit();
			}
		}
	}
}

//Check if HTML5 uploader is supported by the browser
function is_support_html5_uploader(){
	if (window.File && window.FileList && window.Blob && (window.FileReader || window.FormData)) {
		return true;
	}else{
		return false;
	}
}

/** Input Range Functions **/
function count_input(element_id,range_limit_by){
	var current_length = 0;
	
	if(range_limit_by == 'c' || range_limit_by == 'd'){
		current_length = $("#element_" + element_id).val().length;
	}else if(range_limit_by == 'w'){
		current_length = $("#element_" + element_id).val().trim().split(/[\s\.]+/).length; //we consider a word is one or more characters separated by space or dot
	}
	
	$("#currently_entered_" + element_id).text(current_length);
	
	return current_length;
}

function limit_input(element_id,range_limit_by,range_max){
	var current_length = count_input(element_id,range_limit_by);
	
	if(current_length > range_max){
		if(range_limit_by == 'c' || range_limit_by == 'd'){
			$("#element_" + element_id).val($("#element_" + element_id).val().substr(0,range_max));
			$("#currently_entered_" + element_id).text(range_max);
		}else if(range_limit_by == 'w'){
			//for now, we don't limit the words on client side, only server side validation
		}
	}
}

//clear checkbox 'other'
function clear_cb_other(cb_element){
	var other_id = $(cb_element).attr("id").substring(0, $(cb_element).attr("id").length - 1) + "other";
	if($(cb_element).prop("checked") == false){
		$("#" + other_id).val('');
	}	
}