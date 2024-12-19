<div class="auto-style2">
<?php
if(empty($la_settings['footer_login_image_url'])){
?>
<img alt="Only from Lazarus Alliance!" height="119" longdesc="Only from Lazarus Alliance: IT Audit Machine, IT Poic Machine, Continuum, Your Personal CXO, HORSE WIKI and The Security Tirfecta" src="images/Lazarus-Alliance-2015-Proactive-Logos-Rounded-440x119.png" width="440">
<?php
}else{
?>
<img alt="Only from Lazarus Alliance!" height="119" longdesc="Only from Lazarus Alliance: IT Audit Machine, IT Poic Machine, Continuum, Your Personal CXO, HORSE WIKI and The Security Tirfecta" src="<?php echo $la_settings['footer_login_image_url']; ?>" width="440">
<?php
}
?>
</div>
<head>
<style type="text/css">
.auto-style2 {
	text-align: center;
}
</style>
</head>
<br>
<div class="clear"></div>

	</div><!-- /#main -->
	<img src="images/bottom.png" id="bottom_shadow">
	<div id="footer">
		<p class="copyright">Patent Pending, Copyright &copy; 2000-<script>document.write(new Date().getFullYear());</script> - <a href="http://www.continuumgrc.com">Continuum GRC.</a> All rights reserved.</p>	
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

<?php if(!empty($footer_data)){ echo $footer_data; } ?>
</body>
</html>
