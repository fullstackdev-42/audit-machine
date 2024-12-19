$(document).ready(function(){ 
	// Menu
		$('#menu').dropotron({ baseZIndex: 5, offsetY: -10, IEOffsetX: -40, mode: 'fade' });
	// Banner
		$(function() {
			var	banner_speed = 300, 
				fade_speed = 300,
				banner_width_on = 300,
				banner_width_off = 270;
			var banner = $('#banner'), list = banner.find('.list'), items = banner.find('.item'), images = banner.find('.image'), tmp;
			banner
				.fadeTo(0, 0.01, function() {
					banner.fadeTo(900, 1);
				});
			list.mouseleave(function() {
				images.fadeOut(fade_speed, function() {
					images.css('z-index', 1);
				});
			});
			items.each(function() {
				var t = $(this), i = t.find('.image'), l = t.find('.link');
				i
					.detach()
					.appendTo(banner)
					.hide()
					.css('position', 'absolute')
					.css('z-index', 1)
					.css('right', 0)
					.css('top', 0);
				t
					.css('position', 'relative')
					.css('z-index', '5')
					.mouseenter(function() {
						t.stop().animate({ width: banner_width_on }, banner_speed, 'swing', function() {
							i
								.css('z-index', 3)
								.fadeIn(fade_speed, function() {
									images.not(i).css('z-index', 1).hide();
									i.css('z-index', 2);
								});
						});
					})
					.mouseleave(function() {
						t.stop().animate({ width: banner_width_off }, banner_speed, 'swing'); 
					});
				if (l.length > 0)
					t
						.css('cursor', 'pointer')
						.click(function(e) {
							e.preventDefault();
							window.location.href = l.attr('href');
						});
			});
		});
});
