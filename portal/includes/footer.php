<div class="clear"></div>

	</div><!-- /#main -->
	<img src="images/bottom.png" id="bottom_shadow">
	<div id="footer">

		<p class="copyright">Patent Pending, Copyright &copy; <a href="https://continuumgrc.com/">Continuum GRC</a> 2000-<script>document.write(new Date().getFullYear());</script></p>
		<div class="clear"></div>

	</div><!-- /#footer -->


</div><!-- /#container -->

</div><!-- /#bg -->

<?php
	if($disable_jquery_loading !== true){
		echo '<script type="text/javascript" src="js/jquery.min.js"></script>';
		echo '<script type="text/javascript" src="js/jquery-migrate.min.js"></script>';
	}
?>

<?php if(!empty($footer_data)){ echo $footer_data; }
	//Note: Set the number of seconds to wait until the user timesout, go to the settings
	// screen in the app as an admin and change it, or if you have to hard code it for some
	// terrible reason, then go ahead and is it in sessionTimeout
?>
</body>
</html>
