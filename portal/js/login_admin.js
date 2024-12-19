$(function(){
	
	//attach event to the forgot password checkbox
	$('#admin_forgot').bind('change', function() {
		if($(this).prop("checked") == true){
			//show reset password form
			$("#li_password,#li_remember_me,#li_entity").slideUp("slow",function(){
				$("#submit_button").html('Reset My Password');
			});
			$("#li_email_address label").html("Email");
			$("#username_forgot").prop("checked", false);
			
		}else{
			//show login form
			$("#li_password,#li_remember_me,#li_entity").slideDown("slow",function(){
				$("#submit_button").html('Sign In');
				$("#li_submit .desc").css("display", "block");
			});
			$("#li_email_address label").html("Username");
		}		
	});
	//attach event to the forgot username and entity name checkbox
	$('#username_forgot').bind('change', function() {
		if($(this).prop("checked") == true){
			//show send via email form
			$("#li_password,#li_remember_me,#li_entity").slideUp("slow",function(){
				$("#submit_button").html('Send Email');
			});
			$("#li_email_address label").html("Email");
			$("#admin_forgot").prop("checked", false);
			
		}else{
			//show login form
			$("#li_password,#li_remember_me,#li_entity").slideDown("slow",function(){
				$("#submit_button").html('Sign In');
				$("#li_submit .desc").css("display", "block");
			});
			$("#li_email_address label").html("Username");
		}		
	});

	//override the form submit event
	$("#form_login2").submit(function(){
		if($("#admin_forgot").prop("checked") == true){
			//if forgot password submitted
			if($("#client_username").val().trim() == ""){
				alert('Please enter your email address!');
			}else{
				var email_address = $("#client_username").val().trim();
				$("#submit_button").prop("disabled",true);
				$("#admin_forgot").prop("disabled",true);
				$("#username_forgot").prop("disabled",true);
				//do the ajax call to reset the password
				$.ajax({
					type: "POST",
					async: true,
					url: "reset_password.php",
					data: { target_email: email_address },
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//restore the buttons on the dialog
						//console.log(xhr);
						$("#submit_button").html('Reset My Password');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
						$("#username_forgot").prop("disabled",false);
								
						$(".ui-dialog-title#ui-id-1").text('Error!');
					    $("#dialog-login-page-msg").html('Unable to send new password. Error message:<br/><br/>' + xhr.responseText);
					    $("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png"); 
					    $("#dialog-login-page").dialog('open');
					},
					success: function(response_data){
						$("#submit_button").html('Reset My Password');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
						$("#username_forgot").prop("disabled",false);

						if(response_data.status == 'ok'){
						    //display the confirmation message
						    $(".ui-dialog-title#ui-id-1").text('Password sent!');
					    	$("#dialog-login-page-msg").text(response_data.message);
						    $("#dialog-login-page img").attr("src","images/navigation/005499/50x50/Success.png");
					    }else{
					    	$(".ui-dialog-title#ui-id-1").text('Error!');
					    	$("#dialog-login-page-msg").text(response_data.message);
					    	$("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png");
					    }

					    $("#dialog-login-page").dialog('open');	   
					}
				});
			}
			return false;
		} else if($("#username_forgot").prop("checked") == true){
			//if forgot username submitted
			if($("#client_username").val().trim() == ""){
				alert('Please enter your email address!');
			}else{
				var email_address = $("#client_username").val().trim();
				$("#submit_button").prop("disabled",true);
				$("#admin_forgot").prop("disabled",true);
				$("#username_forgot").prop("disabled",true);
				//do the ajax call to reset the password
				$.ajax({
					type: "POST",
					async: true,
					url: "forgot_username.php",
					data: { target_email: email_address },
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//restore the buttons on the dialog
						$("#submit_button").html('Send Email');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
						$("#username_forgot").prop("disabled",false);
								
						$(".ui-dialog-title#ui-id-1").text('Error!');
					    $("#dialog-login-page-msg").html('Unable to send an email. Error message:<br/><br/>' + xhr.responseText);
					    $("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png"); 
					    $("#dialog-login-page").dialog('open');
					},
					success: function(response_data){
						$("#submit_button").html('Send Email');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
						$("#username_forgot").prop("disabled",false);

						if(response_data.status == 'ok'){
						    //display the confirmation message
						    $(".ui-dialog-title#ui-id-1").text('Success!');
					    	$("#dialog-login-page-msg").text(response_data.message);
						    $("#dialog-login-page img").attr("src","images/navigation/005499/50x50/Success.png");
					    }else{
					    	$(".ui-dialog-title#ui-id-1").text('Error!');
					    	$("#dialog-login-page-msg").text(response_data.message);
					    	$("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png");
					    }

					    $("#dialog-login-page").dialog('open');	   
					}
				});
			}
			return false;
		}else{
			return true;
		}
	});

	//Generic dialog box
	$("#dialog-login-page").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		position: { my: "top", at: "top+175", of: window },
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'OK',
			id: 'dialog-entry-sent-btn-ok',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');

				//show login form
				$("#li_password,#li_remember_me,#li_entity").slideDown("slow",function(){
					$("#li_email_address label").html("Username");
					$("#submit_button").html('Sign In');
					$("#li_submit .desc").css("display", "block");
					$("#admin_password").val('').focus();
					$("#admin_forgot").prop("checked",false);
					$("#username_forgot").prop("checked",false);
				});
			}
		}]
	});
});