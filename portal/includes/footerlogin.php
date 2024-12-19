<div class="auto-style2">
<?php
if(empty($la_settings['footer_login_image_url'])){
?>
<img alt="Only from Lazarus Alliance!" longdesc="Only from Lazarus Alliance: IT Audit Machine, IT Poic Machine, Continuum, Your Personal CXO, HORSE WIKI and The Security Tirfecta" src="images/Lazarus-Alliance-2015-Proactive-Logos-Rounded-440x119.png" width="500px">
<?php
}else{
?>
<img alt="Only from Lazarus Alliance!" longdesc="Only from Lazarus Alliance: IT Audit Machine, IT Poic Machine, Continuum, Your Personal CXO, HORSE WIKI and The Security Tirfecta" src="<?php echo $la_settings['footer_login_image_url']; ?>" width="500px">
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
<div class="clear"></div>

	</div><!-- /#main -->
	<img <?php echo ($isVideo) ? 'style="width:80%; margin-left:10%;"' : ''; ?> src="images/bottom.png" id="bottom_shadow">
	<div id="footer" <?php echo ($isVideo) ? 'style="margin-left:8%;"' : 'width="440"'; ?>>
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

$(function () {
	$.ajaxSetup({
		headers: {
			"X-CSRFToken": csrftoken
		},
		data: {
			post_csrf_token: csrftoken
		}
	});
});
</script>
<?php if(!empty($footer_data)){ echo $footer_data; } ?>
</body>
</html>
