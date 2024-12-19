var socket;
var form_enable_auto_mapping;
window.addEventListener('DOMContentLoaded', () => {
	if( form_enable_auto_mapping ) {
		$("#socket-connection-error").show();
		//start::socket logic
 	socket = io('https://'+document.domain+':2020', {secure: true});

		socket.on('connection', function(socket){
			$("#socket-connection-error").hide();
		  	console.log('Connection established !');
		});

		// $('.socket-enabled').keyup(function(){
		$('.socket-enabled').bind("keyup change blur", function(e) {
			var fieldType = '', fieldSubType = '';

			var fieldVal = $(this).val();
			var fieldId = $(this).attr("id");
			var fieldMachineCode = $(this).data('element_machine_code');

			if( fieldMachineCode ) {

				var formObj = $(this)
		  						.closest( "form" );
		  		var formId = formObj.data("formid");
		  		var userEmail = formObj.data("useremail");
		  		var selected_entity_id = formObj.data("selected_entity_id");
		  		var form_domain = formObj.data("domain");

		  		//remove the socket-info message in case user starts typing after field unlock
		  		var liElem = $(this).closest('li');
			    liElem.find('.socket-info').remove();

			    fieldType = $(this).data('field_type');
			    fieldSubType = $(this).data('field_sub_type');
			    var entityId = formObj.attr('entity_id');


				var data = {
					element_id : fieldId,
					field_machine_code : fieldMachineCode,
					value : fieldVal,
					formId : formId,
					userEmail : userEmail,
					fieldType : fieldType,
					fieldSubType : fieldSubType,
					entityId : entityId,
					selected_entity_id : selected_entity_id,
					form_domain : form_domain
				};

				console.log('emitting data', data);
			    
			    if( fieldType == 'checkbox' ) {
			    	data.ischecked = false;
			    	if($(this).prop("checked") == true) {
			    		data.ischecked = true;
			    	} else {
			    		data.value = 0;
			    	}
			    }

			    if( fieldType == 'phone' ) {
			    	//if field type is phone we need to combine values of 3 fields
			    	var element_id = fieldId.substr(0, fieldId.lastIndexOf("_"));
			    	var val1 = $('#'+element_id+'_1').val();
			    	var val2 = $('#'+element_id+'_2').val();
			    	var val3 = $('#'+element_id+'_3').val();

			    	data.valueDb = val1+val2+val3;
			    } else if( fieldType == 'time' ) {
			    	//if field type is time we need to combine values of 4 fields
					var element_id = fieldId.substr(0, fieldId.lastIndexOf("_"));
			    	var val1 = $('#'+element_id+'_1').val();
			    	var val2 = $('#'+element_id+'_2').val();
			    	var val3 = $('#'+element_id+'_3').val();
			    	var val4 = $('#'+element_id+'_4').val();

			    	var element_time = val1+':'+val2+':'+val3+' '+val4;

			    	//convert time to 24 hr format
			    	data.valueDb = moment(element_time, ["h:mm:ss A"]).format("HH:mm:ss");
			    } else if( fieldType == 'money' ) {
			    	//if field type is phone we need to combine values of 2 fields
			    	var element_id = fieldId.substr(0, fieldId.lastIndexOf("_"));
			    	var val1 = $('#'+element_id+'_1').val();
			    	var val2 = $('#'+element_id+'_2').val();

			    	data.valueDb = val1+'.'+val2;
			    } else if( fieldType == 'date' ) {
			    	//if field type is phone we need to combine values of 2 fields
			    	var element_id = fieldId.substr(0, fieldId.lastIndexOf("_"));
			    	var month = $('#'+element_id+'_1').val();
			    	var date = $('#'+element_id+'_2').val();
			    	var year = $('#'+element_id+'_3').val();

			    	data.valueDb = year+'-'+month+'-'+date;
			    }	


			    if( e.type == 'blur' ) {
			    	socket.emit('sync single field', JSON.stringify(data));	
			    } else {
					socket.emit('lock socket field', JSON.stringify(data));

					//when using date picker
					if( e.type == 'change' && fieldType == 'date' ) {
				    	socket.emit('sync single field', JSON.stringify(data));	
				    } else if( e.type == 'change' && fieldType == 'radio' && fieldSubType != 'element_radio_other_text') {
				    	console.log("found");
				    	socket.emit('sync single field', JSON.stringify(data));	
				    } else if( e.type == 'change' && fieldType == 'checkbox' && fieldSubType != 'element_checkbox_other_text') {
				    	socket.emit('sync single field', JSON.stringify(data));	
				    } else if( e.type == 'change' && fieldType == 'select' ) {
				    	socket.emit('sync single field', JSON.stringify(data));	
				    }

				}
			}
		});

		//update and sync field on blur
		/*$('.socket-enabled').bind("blur", function(e) {

		});*/

		socket.on('show locked field message', function(response){

			console.log('in show locked field message !');
			//in case the field got unlocked for all users, keep it unlocked for current user and lock it for others

		    var data = JSON.parse(response.message);
		    var curFormSelectedEntityId = $('form').data("selected_entity_id");
		    var curFormDomain = $('form').data("domain");

		    //only show synced data if selected_entity_id is same on both forms
		    if( data.selected_entity_id == curFormSelectedEntityId && data.form_domain == curFormDomain ) {

				// console.log(data.field_machine_code);
			    var infoHtml = `<p class="socket-info">Field in use by <strong>${data.userEmail}</strong> on <strong>form #${data.formId}</strong>!</p>`;
			    // var inputElem = $('form').find('#'+data.element_id);
			    var inputElem, selectorString = '';
			    selectorString = '*[data-element_machine_code="'+data.field_machine_code+'"]';
			    if( data.fieldType )
			    	selectorString += '[data-field_type="'+data.fieldType+'"]';

			    if( data.fieldSubType )
			    	selectorString += '[data-field_sub_type="'+data.fieldSubType+'"]';

			    console.log(selectorString);
			    console.log(data);
			    
			    inputElem = $('form').find(selectorString);

			    var curFormId = $('form').data("formid");
			    inputElem.addClass('locked');
			    // inputElem = $('#form_'+data.formId).find(selectorString);
			    // inputElem = $('form').find('*[data-element_machine_code="'+data.field_machine_code+'"]');

				inputElem.css('pointer-events', 'none');

			    var liElem = inputElem.closest('li');
			    liElem.find('.socket-info').remove();
			   // liElem.css('background-color', 'rgb(230, 230, 230)');

			    // if( data.fieldType == 'textarea' && data.fieldSubType == 'addresscountry' ) {
			    if( data.fieldType == 'textarea' ) {
			    	if( data.fieldSubType == 'wysiwyg' ) {
				    	var element_machine_code;
				    	document.querySelectorAll('.textarea-formatting').forEach((node, index) => {
				    		element_machine_code = '';
				    		element_machine_code = $(node).data('element_machine_code');
				    		if( element_machine_code == data.field_machine_code ){
				    			editors[index].setData(data.value);
				    		}
				    	});
				    } else {
				    	inputElem.val(data.value);
				    }

			    } else if(data.fieldType == 'radio') {
			    	// console.log('in radio', inputElem);
			    	if( data.fieldSubType == 'element_radio_other_text' ){
			    		inputElem.val(data.value);
			    	} else {
			    		inputElem.prop("checked", true);
			    	}
			    } else if(data.fieldType == 'checkbox') {
			    	// console.log('in checkbox', inputElem);
			    	if( data.fieldSubType == 'element_checkbox_other_text' ){
			    		inputElem.val(data.value);
			    	} else {
			    		if( data.ischecked )
			    			inputElem.prop("checked", true);
			    		else
			    			inputElem.prop("checked", false);
			    	}
			    } else {
			    	inputElem.val(data.value);	
			    }

			    
			    liElem.append(infoHtml);
			}
		});

		socket.on('unlock fields', function(data){

			console.log('unlock fields', data);
			$('.form-element-locked-alert').remove();
			$.each(data.fields, function( index, value ) {
				//let infoHtml = `<p class="socket-info" style="color:#73859f"><strong>${data.userEmail}</strong> unlocked this field from <strong>form #${data.formId}</strong>!</p>`;
				infoHtml = "";
			    var inputElem = $('form').find('*[data-element_machine_code="'+value+'"]');
			    if( inputElem.hasClass( "locked" ) ) {
				    inputElem.css('pointer-events', 'all');
				    var liElem = inputElem.closest('li');
				    liElem.find('.socket-info').remove();
				    // inputElem.val(data.value);
				    // $(infoHtml).insertAfter(inputElem);
				    liElem.append(infoHtml);
				    inputElem.removeClass( "locked" );
				}
			});
		});

		socket.on('show file upload', function(response){
			console.log('in show file upload !', response);
			var data = JSON.parse(response.message);
		    var curFormSelectedEntityId = $('form').data("selected_entity_id");

		    //only show synced data if selected_entity_id is same on both forms
		    if( data.selected_entity_id == curFormSelectedEntityId ) {
		    	var infoHtml = `<p class="socket-info">Field in use by <strong>${data.userEmail}</strong> on <strong>form #${data.formId}</strong>!</p>`;
			    // var inputElem = $('form').find('#'+data.element_id);
			    var inputElem, selectorString = '';
			    selectorString = '*[data-element_machine_code="'+data.field_machine_code+'"]';
			    if( data.fieldType )
			    	selectorString += '[data-field_type="'+data.fieldType+'"]';

			    console.log(selectorString);
			    console.log(data);
			    
			    inputElem = $('form').find(selectorString);

			    var curFormId = $('form').data("formid");
			    var elementId = inputElem.attr('id');
			    var fileOrder = 0;
			    var fileName = data.file_name;
			    var queue_item_id = data.queue_item_id;
			    var field_machine_code = data.field_machine_code;
			    var filename = data.filename;


			    var element_id = elementId.split("_")[1];
			    // var queue_item_arr =  queue_item.split("-");
			    // var queue_item_id =  queue_item_arr.reverse()[0];

			    // var holder_id = 'uploadifive-'+elementId+'-file-'+queue_item_id;

			    var infoHtml = '<div class="uploadifive-queue-item complete '+queue_item_id+'" id="uploadifive-'+elementId+'-file-'+queue_item_id+'"><a class="cancel" href="javascript:remove_synced_attachment(\''+filename+'\',\''+field_machine_code+'\',\''+queue_item_id+'\', '+element_id+');"><img border="0" src="images/icons/delete.png"></a><div><span class="filename"><img align="absmiddle" class="file_attached" src="images/icons/attach.gif">'+fileName+'</span><span class="fileinfo"> - Completed</span></div></div>';

			    // console.log(infoHtml);

			    $('#'+elementId+'_queue').append(infoHtml);
		    }
		});

		socket.on('show file delete', function(response){
			console.log('in show file delete !', response);
			var data = JSON.parse(response.message);
		    var curFormSelectedEntityId = $('form').data("selected_entity_id");

		    //only show synced data if selected_entity_id is same on both forms
		    if( data.selected_entity_id == curFormSelectedEntityId ) {
			    var inputElem, selectorString = '';
			    selectorString = '*[data-element_machine_code="'+data.field_machine_code+'"]';
			    if( data.fieldType )
			    	selectorString += '[data-field_type="file"]';

			    console.log(selectorString);
			    console.log(data);
			    
			    inputElem = $('form').find(selectorString);

			    var curFormId = $('form').data("formid");
			    var elementId = inputElem.attr('id');
			    var fileOrder = 0;
			    var fileName = data.file_name;
			    var fileQueueNo = data.file_queue_no;

		    	console.log('#'+elementId+'_queue .'+fileQueueNo);
		    	$('#'+elementId+'_queue .'+fileQueueNo).fadeOut("slow",function(){$(this).remove();});
		    }
		});



		socket.on('disconnect', function(data){
			$("#socket-connection-error").show();
			console.log("WebSocket is closed now.", event);	
		});

		$("#primary-entity-not-selected").dialog({
			modal: true,
			autoOpen: true,
			closeOnEscape: false,
			width: 350,
			height: 180,
			draggable: false,
			resizable: false
		});
		//end::socket logic
	} else {
		console.log('auto-mapping is disabled.');
		$("#socket-connection-error").hide();
	}
});


/** File Upload Functions **/
function remove_synced_attachment(filename, element_machine_code, holder_id, element_id){
	
	var itauditmachine_path = '';
	if (typeof __itauditmachine_path != 'undefined'){
		itauditmachine_path = __itauditmachine_path;
	}

	var selected_entity_id = $('form').data("selected_entity_id");

	$("." + holder_id + " > div.cancel img").attr("src", itauditmachine_path + "images/loader_small_grey.gif");
	$.ajax({
		   	type: "POST",
		   	async: true,
		   	url: itauditmachine_path + "delete_file_upload.php?file_upload_synced=1",
			data: {
                filename: filename,
                element_machine_code: element_machine_code,
                selected_entity_id : selected_entity_id,
                holder_id : holder_id,
                element_id: element_id
            },
		   	cache: false,
		   	global: true,
		   	error: function(xhr,text_status,e){
			   //console.log(text_status);
			   console.log(e);
			   //remove the delete progress on error
			   $("." + holder_id + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
			   alert('Error! Unable to delete file.');
		   	},
		   	success: function(response){
			   //console.log(response)
			   var response_data = JSON.parse(response);
			   //console.log(response_data)
			   if(response_data.status == 'ok'){
				   if(is_support_html5_uploader()){
				   	   try{
				   	   		$("#element_" + response_data.element_id).uploadifive('cancel',$("#" + response_data.holder_id).data('file'));
				   	   }catch(e){}
				   	   $("." + response_data.holder_id).fadeOut("slow",function(){$(this).remove();});

				   }else{
				       $("." + response_data.holder_id).fadeOut("slow",function(){$(this).remove();});
				   }

				   var data = {
						holder_id : response_data.holder_id,
						field_machine_code : response_data.field_machine_code,
						file_queue_no : response_data.file_queue_no,
						selected_entity_id : response_data.selected_entity_id,
						fieldType : 'file'
					};
					console.log('emitting file data', data);
			        socket.emit('file delete', JSON.stringify(data));


			   }else{
				   //unknown error, response json improperly formatted
				  $("." + holder_id + " > div.cancel img").attr("src",itauditmachine_path + "images/icons/delete.png");
				  alert('Error while deleting the file. Please try again.');
			   }
			   
		   }, //end on ajax success
		   complete:function(){}
	}); //end ajax call
	
}
