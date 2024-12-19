$(function(){
	
	//attach event to the forgot password checkbox
	$('#admin_forgot').bind('change', function() {
		if($(this).prop("checked") == true){
			//show reset password form
			$("#li_password,#li_remember_me").slideUp("slow",function(){
				$("#submit_button").html('Reset My Password');
			});
			
		}else{
			//show login form
			$("#li_password,#li_remember_me").slideDown("slow",function(){
				$("#submit_button").html(' Sign In');
			});
		}
		
	});

	//override the form submit event
	$("#form_login2").submit(function(){
		if($("#admin_forgot").prop("checked") == true){
			//if forgot password submitted
			if($("#admin_username").val().trim() == ""){
				alert('Please enter your email address!');
			}else{
				var admin_email_address = $("#admin_username").val().trim();
				$("#submit_button").after("<div class='forgot_password_loader'><img src='images/loader_small_grey.gif' /></div>");
				$("#submit_button").prop("disabled",true);
				$("#admin_forgot").prop("disabled",true);

				//do the ajax call to reset the password
				$.ajax({
					type: "POST",
					async: true,
					url: "reset_password.php",
					data: {
						target_email: admin_email_address
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//restore the buttons on the dialog
						$(".forgot_password_loader").remove();
						$("#submit_button").html('Reset My Password');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
							
						$("#dialog-login-page").dialog("option", "title", "Error!");
						$("#dialog-login-page-msg").html('Unable to send new password. Error message:<br/><br/>' + xhr.responseText);
						$("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png"); 
						$("#dialog-login-page").dialog('open');
					},
					success: function(response_data){
						$(".forgot_password_loader").remove();
						$("#submit_button").html('Reset My Password');
						$("#submit_button").prop("disabled",false);
						$("#admin_forgot").prop("disabled",false);
						if(response_data.status == 'ok'){
							//display the confirmation message
							$("#dialog-login-page").dialog("option", "title", "Success!");
							$("#dialog-login-page-msg").text(response_data.message);
							$("#dialog-login-page img").attr("src","images/navigation/005499/50x50/Success.png");
							$("#dialog-login-page").dialog('open');
						}else{
							$("#dialog-login-page").dialog("option", "title", "Error!");
							$("#dialog-login-page-msg").text(response_data.message);
							$("#dialog-login-page img").attr("src","images/navigation/ED1C2A/50x50/Warning.png");
							$("#dialog-login-page").dialog('open');
						}
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
					$("#li_password,#li_remember_me").slideDown("slow",function(){
						$("#submit_button").html('Sign In');

						$("#admin_password").val('').focus();
						$("#admin_forgot").prop("checked",false);
					});
				}
			}]

	});
});