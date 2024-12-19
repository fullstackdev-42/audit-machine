<div id="dialog-logout-message" title="" class="buttons" style="display: none"><img alt="" height="48" src="/portal/images/navigation/005499/50x50/Notice.png" width="48"><br><br>Your session will be expired in few min. Save your changes.</div>
		</div>
      </div>
    </div>
    <img src="/portal/images/bottom.png" id="bottom_shadow">
    <div id="footer">
		<p class="copyright">Patent Pending, Copyright &copy; <a href="https://continuumgrc.com/">Continuum GRC</a> 2000-<script>document.write(new Date().getFullYear());</script></p>
		<div class="clear"></div>
	</div>
  </div>  
</div>
<?php
global $exclude_footer_jquery;
if( ! $exclude_footer_jquery ) { ?>
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-migrate.min.js"></script>
<?php } ?>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<?php
if(!empty($footer_data)) {
	echo $footer_data;
}
global $include_code_after_scripts;
echo $include_code_after_scripts;
?>
<script type="text/javascript">
var csrftoken =  (function() {
    // not need Jquery for doing that
    var metas = window.document.getElementsByTagName('meta');

    // finding one has csrf token 
    for(var i=0 ; i < metas.length ; i++) {
        if ( metas[i].name === "csrf-token") {
            return  metas[i].content;       
        }
    }  
})();

function alertSessionTimeout(){
	var a = parseInt('<?php echo $max_session_timeout; ?>');
	var b = parseInt('<?php echo $_SESSION['la_user_logged_in_time']; ?>');
	var c = (new Date(( a + b ) * 1000)).getTime();
	var timeOut = (new Date(((a - ( a * 0.25 )) + b) * 1000)).getTime();
	var timeOut2 = (new Date(((a - ( a * 0.10 )) + b) * 1000)).getTime();
	var i = 1;
	var firstAlert = false;
	var secondAlert = false;
	
	console.log('a: ' + a + ', b: ' + b + ', c: ' + c + ', timeOut: ' + timeOut + ', timeOut2: ' + timeOut2);
	
	setInterval(function(){
		var currTime = (new Date()).getTime();
		var d = currTime - c;
		
		console.log('a: ' + a + ', b: ' + b + ', currTime: ' + currTime + ', timeOut: ' + timeOut + ', timeOut2: ' + timeOut2);
		
		if (currTime >= timeOut && currTime <= timeOut2) {			
			if (firstAlert == false) {
				$("#dialog-logout-message").dialog('open').click();
			}
			
			firstAlert = true;
		}
		
		if (currTime >= timeOut2) {
			if (secondAlert == false) {
				$("#dialog-logout-message").dialog('open').click();
			}
			
			secondAlert = true;
		}
		
		i++;
		
	}, 1000);
}

$(document).ready(function(e) {
	$(document).on("click", function(event) {
		var $trigger = $("#header .dropdown-toggle");
		if($trigger !== event.target && !$trigger.has(event.target).length) {
			$("#header .dropdown-toggle").attr("aria-expanded", "false");
			$("#header .dropdown-menu").css("display", "none");
		}
	});

	$("#header").on("click", ".dropdown-toggle", function(e) {
		e.preventDefault();
		if($(this).attr("aria-expanded") == "false") {
			$(this).attr("aria-expanded", "true");
			$("#header .dropdown-menu").css("display", "block");
		} else {
			$(this).attr("aria-expanded", "false");
			$("#header .dropdown-menu").css("display", "none");
		}
	});

	$("#dialog-logout-message").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [
				{
			text: 'Ok',
			id: 'btn-welcome-message-ok',
			'class': 'btn_secondary_action',
			click: function() {
					$(this).dialog('close');
			}
		}]
	});
	
	//alertSessionTimeout();
	
	$.ajaxSetup({
		headers: {
			"X-CSRFToken": csrftoken
		},
		data: {
			post_csrf_token: csrftoken
		},
		cache: false,
		complete: function (event, request, settings) {
			// location.reload();
		}
	});
});
</script>
</body>
</html>

<?php
	$path = $_SERVER['DOCUMENT_ROOT'];
    $path .= "/itam-shared/includes/session-timeout.php";
	include_once($path);
?>