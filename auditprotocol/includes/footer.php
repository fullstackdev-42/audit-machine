<div class="clear"></div>
</div>
<!-- /#main -->
<img src="images/bottom.png" id="bottom_shadow">
<div id="footer">
  <p class="copyright">Patent Pending, Copyright &copy; <a href="https://continuumgrc.com">Continuum GRC</a> 2000-<script>document.write(new Date().getFullYear());</script></p>
  <div class="clear"></div>
</div>
<!-- /#footer -->
</div>
<!-- /#container -->
</div>
<!-- /#bg -->

<?php
	$disable_jquery_loading = false;
	if($disable_jquery_loading !== true){
		if(strpos($_SERVER['REQUEST_URI'], "view.php") === false){
			echo '<script type="text/javascript" src="js/jquery.min.js"></script>';
			echo '<script type="text/javascript" src="js/jquery-migrate.min.js"></script>';
		}		
	}
	if(!empty($footer_data)) {
		echo $footer_data;
	}
	include_once("../itam-shared/includes/session-timeout.php");
?>
<script type="text/javascript">
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
	});
</script>
</body>
</html>
