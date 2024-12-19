<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/

 More info at: http://lazarusalliance.com/
 ********************************************************************************/

//generate color picker boxes
	function get_color_picker_markup(){
		$color_picker_markup =<<<EOT
<ul class="et_color_picker">
  <li id="li_transparent" style="background-image: url('images/icons/colorbox_minus.png');background-color: transparent"></li>
  <li style="background-color: #DDA629"></li>
  <li style="background-color: #EF1829"></li>
  <li style="background-color: #3A4BCB"></li>
  <li style="background-color: #008600"></li>
  <li style="background-color: #C5BFE1"></li>
  <li style="background-color: #000000"></li>
  <li style="background-color: #444444"></li>
  <li style="background-color: #666666"></li>
  <li style="background-color: #999999"></li>
  <li style="background-color: #CDCDCD"></li>
  <li style="background-color: #ECECEC"></li>
  <li style="background-color: #FFFFFF"></li>
  <li style="background-color: #1693A5"></li>
  <li style="background-color: #FBB829"></li>
  <li style="background-color: #CDD7B6"></li>
  <li style="background-color: #FF0000"></li>
  <li style="background-color: #FF0066"></li>
  <li style="background-color: #556270"></li>
  <li style="background-color: #ADD8C7"></li>
  <li style="background-color: #333333"></li>
  <li style="background-color: #FCFBE3"></li>
  <li style="background-color: #F0F0D8"></li>
  <li style="background-color: #F02311"></li>
  <li style="background-color: #FF9900"></li>
  <li style="background-color: #800F25"></li>
  <li style="background-color: #2A8FBD"></li>
  <li style="background-color: #CCFF00"></li>
  <li style="background-color: #A40802"></li>
  <li style="background-color: #FF5EAA"></li>
  <li style="background-color: #D8D8C0"></li>
  <li style="background-color: #6CDFEA"></li>
  <li style="background-color: #AD234B"></li>
  <li style="background-color: #666666"></li>
  <li style="background-color: #F0F0F0"></li>
  <li style="background-color: #77CCA4"></li>
  <li style="background-color: #FF0033"></li>
  <li style="background-color: #FE4365"></li>
  <li style="background-color: #025D8C"></li>
  <li style="background-color: #7F94B0"></li>
  <li style="background-color: #C7F464"></li>
  <li style="background-color: #D9FFA9"></li>
  <li style="background-color: #FC0F3E"></li>
  <li style="background-color: #D2E4FC"></li>
  <li style="background-color: #948C75"></li>
  <li style="background-color: #FFFF00"></li>
  <li style="background-color: #CCCCCC"></li>
  <li style="background-color: #FF6666"></li>
  <li style="background-color: #FFCC00"></li>
  <li style="background-color: #F4FCE8"></li>
  <li style="background-color: #999999"></li>
  <li style="background-color: #F7FDFA"></li>
  <li style="background-color: #7FAF1B"></li>
  <li style="background-color: #C0ADDB"></li>
  <li style="background-color: #A0D4A4"></li>
  <li style="background-color: #A1BEE6"></li>
  <li style="background-color: #FF6600"></li>
  <li style="background-color: #7FFF24"></li>
  <li style="background-color: #0B8C8F"></li>
  <li style="background-color: #01D2FF"></li>
  <li style="background-color: #CAE8A2"></li>
  <li style="background-color: #FF5500"></li>
  <li style="background-color: #A80000"></li>
  <li style="background-color: #D70044"></li>
  <li style="background-color: #630947"></li>
  <li style="background-color: #515151"></li>
  <li style="background-color: #FF8800"></li>
  <li style="background-color: #AB0743"></li>
  <li style="background-color: #369699"></li>
  <li style="background-color: #520039"></li>
  <li style="background-color: #D7217E"></li>
  <li style="background-color: #D9E68E"></li>
  <li style="background-color: #107FC9"></li>
  <li style="background-color: #4F4E57"></li>
  <li style="background-color: #A8C0A8"></li>
  <li style="background-color: #44AA55"></li>
  <li style="background-color: #C0D8D8"></li>
  <li style="background-color: #FFA4A0"></li>
  <li style="background-color: #E3F6F3"></li>
  <li style="background-color: #F5F3E5"></li>
  <li style="background-color: #F4FFF9"></li>
  <li style="background-color: #919999"></li>
  <li style="background-color: #FF6B6B"></li>
  <li style="background-color: #C9E69A"></li>
  <li style="background-color: #EDF7FF"></li>
  <li style="background-color: #F56991"></li>
  <li style="background-color: #036564"></li>
  <li style="background-color: #E45635"></li>
  <li style="background-color: #D3B2D1"></li>
  <li style="background-color: #8EAFD1"></li>
  <li style="background-color: #FF9500"></li>
  <li style="background-color: #BAE4E5"></li>
  <li style="background-color: #FAF2F8"></li>
  <li style="background-color: #B1D58B"></li>
  <li style="background-color: #F0D878"></li>
  <li style="background-color: #D8F0F0"></li>
  <li style="background-color: #FFFFCC"></li>
  <li style="background-color: #FFD0D4"></li>
  <li style="background-color: #EFEFEF"></li>
  <li style="background-color: #F5AA1A"></li>
  <li style="background-color: #FFCCCC"></li>
  <li style="background-color: #D5D6CB"></li>
  <li style="background-color: #F0F0C0"></li>
  <li style="background-color: #82AEC8"></li>
  <li style="background-color: #69D2E7"></li>
  <li style="background-color: #B3C7EB"></li>
  <li style="background-color: #87D69B"></li>
  <li style="background-color: #ECCD35"></li>
  <li style="background-color: #E0B5CB"></li>
  <li style="background-color: #484848"></li>
  <li style="background-color: #FF8080"></li>
  <li style="background-color: #ADDDEB"></li>
  <li style="background-color: #E9ECD9"></li>
  <li style="background-color: #BBC793"></li>
  <li style="background-color: #7BA5D1"></li>
  <li style="background-color: #C4CDE6"></li>
  <li style="background-color: #BFA76F"></li>
  <li style="background-color: #814444"></li>
  <li style="background-color: #4E6189"></li>
  <li style="background-color: #9AE4E8"></li>
  <li style="background-color: #BFA76F"></li>
  <li style="background-color: #990000"></li>
  <li style="background-color: #006666"></li>
  <li style="background-color: #F74427"></li>
  <li style="background-color: #0E4E5A"></li>
  <li style="background-color: #C20562"></li>
  <li style="background-color: #A662DE"></li>
  <li style="background-color: #ADC7BE"></li>
  <li style="background-color: #F38630"></li>
  <li style="background-color: #FF005E"></li>
  <li style="background-color: #301830"></li>
  <li style="background-color: #FFFB00"></li>
  <li style="background-color: #FF2A00"></li>
  <li style="background-color: #EBEBEB"></li>
  <li style="background-color: #F0EEE1"></li>
  <li style="background-color: #FF7300"></li>
  <li style="background-color: #C0FF33"></li>
  <li style="background-color: #00A0C6"></li>
  <li style="background-color: #FFD700"></li>
  <li style="background-color: #81971A"></li>
  <li style="background-color: #C7E2C3"></li>
  <li style="background-color: #F8ECC9"></li>
  <li style="background-color: #800149"></li>
  <li style="background-color: #BD8B64"></li>
  <li style="background-color: #8ABFCF"></li>
  <li style="background-color: #F0D8C0"></li>
  <li style="background-color: #D8D8A8"></li>
  <li style="background-color: #FF6699"></li>
  <li style="background-color: #FA5B49"></li>
  <li style="background-color: #9FC2D6"></li>
  <li style="background-color: #549CCC"></li>
  <li style="background-color: #F0D8D8"></li>
  <li style="background-color: #6991AA"></li>
  <li style="background-color: #D4E77D"></li>
  <li style="background-color: #62BECB"></li>
  <li style="background-color: #7D96FF"></li>
  <li style="background-color: #F9FAD2"></li>
  <li style="background-color: #F5FAAC"></li>
  <li style="background-color: #FFAA7D"></li>
  <li style="background-color: #786060"></li>
  <li style="background-color: #A8A878"></li>
  <li style="background-color: #48A09B"></li>
  <li style="background-color: #FFF200"></li>
  <li style="background-color: #FCCD43"></li>
  <li style="background-color: #83AF9B"></li>
  <li style="background-color: #E1F5B0"></li>
  <li style="background-color: #C7E7E6"></li>
  <li style="background-color: #FFBAA9"></li>
</ul>
EOT;
		return $color_picker_markup;
	}
//generate pattern picker boxes
	function get_pattern_picker_markup(){
		$pattern_picker_markup =<<<EOT
<ul class="et_pattern_picker">
  <li data-pattern="1.jpg" style="background-image: url('images/form_resources/1.jpg');"></li>
  <li data-pattern="2.jpg" style="background-image: url('images/form_resources/2.jpg');"></li>
  <li data-pattern="3.jpg" style="background-image: url('images/form_resources/3.jpg');"></li>
  <li data-pattern="5.jpg" style="background-image: url('images/form_resources/5.jpg');"></li>
  <li data-pattern="6.jpg" style="background-image: url('images/form_resources/6.jpg');"></li>
  <li data-pattern="9.jpg" style="background-image: url('images/form_resources/9.jpg');"></li>
  <li data-pattern="12.jpg" style="background-image: url('images/form_resources/12.jpg');"></li>
  <li data-pattern="13.jpg" style="background-image: url('images/form_resources/13.jpg');"></li>
  <li data-pattern="14.jpg" style="background-image: url('images/form_resources/14.jpg');"></li>
  <li data-pattern="15.jpg" style="background-image: url('images/form_resources/15.jpg');"></li>
  <li data-pattern="16.jpg" style="background-image: url('images/form_resources/16.jpg');"></li>
  <li data-pattern="17.jpg" style="background-image: url('images/form_resources/17.jpg');"></li>
  <li data-pattern="18.jpg" style="background-image: url('images/form_resources/18.jpg');"></li>
  <li data-pattern="19.jpg" style="background-image: url('images/form_resources/19.jpg');"></li>
  <li data-pattern="20.jpg" style="background-image: url('images/form_resources/20.jpg');"></li>
  <li data-pattern="22.jpg" style="background-image: url('images/form_resources/22.jpg');"></li>
  <li data-pattern="23.jpg" style="background-image: url('images/form_resources/23.jpg');"></li>
  <li data-pattern="24.jpg" style="background-image: url('images/form_resources/24.jpg');"></li>
  <li data-pattern="25.jpg" style="background-image: url('images/form_resources/25.jpg');"></li>
  <li data-pattern="26.jpg" style="background-image: url('images/form_resources/26.jpg');"></li>
  <li data-pattern="27.jpg" style="background-image: url('images/form_resources/27.jpg');"></li>
  <li data-pattern="28.jpg" style="background-image: url('images/form_resources/28.jpg');"></li>
  <li data-pattern="29.jpg" style="background-image: url('images/form_resources/29.jpg');"></li>
  <li data-pattern="30.jpg" style="background-image: url('images/form_resources/30.jpg');"></li>
  <li data-pattern="32.jpg" style="background-image: url('images/form_resources/32.jpg');"></li>
  <li data-pattern="33.jpg" style="background-image: url('images/form_resources/33.jpg');"></li>
  <li data-pattern="34.jpg" style="background-image: url('images/form_resources/34.jpg');"></li>
  <li data-pattern="35.jpg" style="background-image: url('images/form_resources/35.jpg');"></li>
  <li data-pattern="36.jpg" style="background-image: url('images/form_resources/36.jpg');"></li>
  <li data-pattern="38.jpg" style="background-image: url('images/form_resources/38.jpg');"></li>
  <li data-pattern="39.jpg" style="background-image: url('images/form_resources/39.jpg');"></li>
  <li data-pattern="40.jpg" style="background-image: url('images/form_resources/40.jpg');"></li>
  <li data-pattern="41.jpg" style="background-image: url('images/form_resources/41.jpg');"></li>
  <li data-pattern="43.jpg" style="background-image: url('images/form_resources/43.jpg');"></li>
  <li data-pattern="44.jpg" style="background-image: url('images/form_resources/44.jpg');"></li>
  <li data-pattern="45.jpg" style="background-image: url('images/form_resources/45.jpg');"></li>
  <li data-pattern="46.jpg" style="background-image: url('images/form_resources/46.jpg');"></li>
  <li data-pattern="47.jpg" style="background-image: url('images/form_resources/47.jpg');"></li>
  <li data-pattern="48.jpg" style="background-image: url('images/form_resources/48.jpg');"></li>
  <li data-pattern="49.jpg" style="background-image: url('images/form_resources/49.jpg');"></li>
  <li data-pattern="51.jpg" style="background-image: url('images/form_resources/51.jpg');"></li>
  <li data-pattern="53.jpg" style="background-image: url('images/form_resources/53.jpg');"></li>
  <li data-pattern="54.jpg" style="background-image: url('images/form_resources/54.jpg');"></li>
  <li data-pattern="55.jpg" style="background-image: url('images/form_resources/55.jpg');"></li>
  <li data-pattern="56.jpg" style="background-image: url('images/form_resources/56.jpg');"></li>
  <li data-pattern="57.jpg" style="background-image: url('images/form_resources/57.jpg');"></li>
  <li data-pattern="59.jpg" style="background-image: url('images/form_resources/59.jpg');"></li>
  <li data-pattern="60.jpg" style="background-image: url('images/form_resources/60.jpg');"></li>
  <li data-pattern="61.jpg" style="background-image: url('images/form_resources/61.jpg');"></li>
  <li data-pattern="62.jpg" style="background-image: url('images/form_resources/62.jpg');"></li>
  <li data-pattern="63.jpg" style="background-image: url('images/form_resources/63.jpg');"></li>
  <li data-pattern="64.jpg" style="background-image: url('images/form_resources/64.jpg');"></li>
  <li data-pattern="66.jpg" style="background-image: url('images/form_resources/66.jpg');"></li>
  <li data-pattern="67.jpg" style="background-image: url('images/form_resources/67.jpg');"></li>
  <li data-pattern="68.jpg" style="background-image: url('images/form_resources/68.jpg');"></li>
  <li data-pattern="69.jpg" style="background-image: url('images/form_resources/69.jpg');"></li>
  <li data-pattern="70.jpg" style="background-image: url('images/form_resources/70.jpg');"></li>
  <li data-pattern="71.jpg" style="background-image: url('images/form_resources/71.jpg');"></li>
  <li data-pattern="72.jpg" style="background-image: url('images/form_resources/72.jpg');"></li>
  <li data-pattern="73.jpg" style="background-image: url('images/form_resources/73.jpg');"></li>
</ul>
EOT;

		return $pattern_picker_markup;
	}

//generate font picker boxes
	function get_font_picker_markup(){
		$font_picker_markup =<<<EOT
												<ul class="et_font_picker">
													<li>
														<div class="font_picker_preview" style="font-family: 'Lucida Grande',sans-serif">Default</div>
														<div class="font_picker_meta">
															<div class="font_name">Lucida Grande</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div id="li_lobster" class="font_picker_preview" style="font-family: Arial,sans-serif">Arial</div>
														<div class="font_picker_meta">
															<div class="font_name">Arial</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Trebuchet MS', Helvetica, sans-serif;">Trebuchet MS</div>
														<div class="font_picker_meta">
															<div class="font_name">Trebuchet MS</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: Verdana, sans-serif;">Verdana</div>
														<div class="font_picker_meta">
															<div class="font_name">Verdana</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: Tahoma, Geneva, sans-serif;">Tahoma</div>
														<div class="font_picker_meta">
															<div class="font_name">Tahoma</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Courier New', Courier, monospace;">Courier New</div>
														<div class="font_picker_meta">
															<div class="font_name">Courier New</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Palatino Linotype', 'Book Antiqua', Palatino, serif;">Palatino Linotype</div>
														<div class="font_picker_meta">
															<div class="font_name">Palatino Linotype</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Times New Roman', serif;">Times New Roman</div>
														<div class="font_picker_meta">
															<div class="font_name">Times New Roman</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: Georgia, serif;">Georgia</div>
														<div class="font_picker_meta">
															<div class="font_name">Georgia</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Comic Sans MS', cursive;">Comic Sans MS</div>
														<div class="font_picker_meta">
															<div class="font_name">Comic Sans MS</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li>
														<div class="font_picker_preview" style="font-family: 'Arial Black', Gadget, sans-serif;">Arial Black</div>
														<div class="font_picker_meta">
															<div class="font_name">Arial Black</div>
															<div class="font_info">System Font</div>
														</div>
													</li>
													<li class="li_show_more">
														<span>Show More Fonts</span> <img src="images/icons/arrow_down.png" style="vertical-align: middle"/>
													</li>
												</ul>
EOT;

		return $font_picker_markup;
	}

	//generate the CSS markup for the selected form theme
	function la_theme_get_css_content($dbh,$theme_id){

		$css_content = "/** DO NOT MODIFY THIS FILE. All code here are generated by IT Audit Machine Theme Editor **/\n\n";
		$theme_properties = new stdClass();

		$la_settings = la_get_settings($dbh);


		$query = "SELECT
						theme_name,
						logo_type,
						ifnull(logo_custom_image,'') logo_custom_image,
						logo_custom_height,
						logo_default_image,
						wallpaper_bg_type,
						wallpaper_bg_color,
						wallpaper_bg_pattern,
						wallpaper_bg_custom,
						header_bg_type,
						header_bg_color,
						header_bg_pattern,
						header_bg_custom,
						form_bg_type,
						form_bg_color,
						form_bg_pattern,
						form_bg_custom,
						highlight_bg_type,
						highlight_bg_color,
						highlight_bg_pattern,
						highlight_bg_custom,
						guidelines_bg_type,
						guidelines_bg_color,
						guidelines_bg_pattern,
						guidelines_bg_custom,
						field_bg_type,
						field_bg_color,
						field_bg_pattern,
						field_bg_custom,
						form_title_font_type,
						form_title_font_weight,
						form_title_font_style,
						form_title_font_size,
						form_title_font_color,
						form_desc_font_type,
						form_desc_font_weight,
						form_desc_font_style,
						form_desc_font_size,
						form_desc_font_color,
						field_title_font_type,
						field_title_font_weight,
						field_title_font_style,
						field_title_font_size,
						field_title_font_color,
						guidelines_font_type,
						guidelines_font_weight,
						guidelines_font_style,
						guidelines_font_size,
						guidelines_font_color,
						section_title_font_type,
						section_title_font_weight,
						section_title_font_style,
						section_title_font_size,
						section_title_font_color,
						section_desc_font_type,
						section_desc_font_weight,
						section_desc_font_style,
						section_desc_font_size,
						section_desc_font_color,
						field_text_font_type,
						field_text_font_weight,
						field_text_font_style,
						field_text_font_size,
						field_text_font_color,
						border_form_width,
						border_form_style,
						border_form_color,
						border_guidelines_width,
						border_guidelines_style,
						border_guidelines_color,
						border_section_width,
						border_section_style,
						border_section_color,
						form_shadow_style,
						form_shadow_size,
						form_shadow_brightness,
						form_button_type,
						form_button_text,
						form_button_image,
						advanced_css
					FROM
						`".LA_TABLE_PREFIX."form_themes`
				   WHERE
				   		theme_id=? and `status`=1";
		$params = array($theme_id);

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$theme_properties->theme_id 		   = $theme_id;
		$theme_properties->theme_name  		   = $row['theme_name'];
		$theme_properties->logo_type 		   = $row['logo_type'];
		$theme_properties->logo_custom_image   = $row['logo_custom_image'];
		$theme_properties->logo_custom_height  = (int) $row['logo_custom_height'];
		$theme_properties->logo_default_image  = $row['logo_default_image'];
		$theme_properties->wallpaper_bg_type 	= $row['wallpaper_bg_type'];
		$theme_properties->wallpaper_bg_color 	= $row['wallpaper_bg_color'];
		$theme_properties->wallpaper_bg_pattern = $row['wallpaper_bg_pattern'];
		$theme_properties->wallpaper_bg_custom 	= $row['wallpaper_bg_custom'];
		$theme_properties->header_bg_type 		= $row['header_bg_type'];
		$theme_properties->header_bg_color 		= $row['header_bg_color'];
		$theme_properties->header_bg_pattern 	= $row['header_bg_pattern'];;
		$theme_properties->header_bg_custom 	= $row['header_bg_custom'];
		$theme_properties->form_bg_type 		= $row['form_bg_type'];
		$theme_properties->form_bg_color 		= $row['form_bg_color'];
		$theme_properties->form_bg_pattern 		= $row['form_bg_pattern'];;
		$theme_properties->form_bg_custom 		= $row['form_bg_custom'];
		$theme_properties->highlight_bg_type 	= $row['highlight_bg_type'];
		$theme_properties->highlight_bg_color 	= $row['highlight_bg_color'];
		$theme_properties->highlight_bg_pattern = $row['highlight_bg_pattern'];
		$theme_properties->highlight_bg_custom 	= $row['highlight_bg_custom'];
		$theme_properties->guidelines_bg_type 	= $row['guidelines_bg_type'];
		$theme_properties->guidelines_bg_color 	= $row['guidelines_bg_color'];
		$theme_properties->guidelines_bg_pattern = $row['guidelines_bg_pattern'];
		$theme_properties->guidelines_bg_custom  = $row['guidelines_bg_custom'];
		$theme_properties->field_bg_type 		 = $row['field_bg_type'];
		$theme_properties->field_bg_color 		 = $row['field_bg_color'];
		$theme_properties->field_bg_pattern 	 = $row['field_bg_pattern'];
		$theme_properties->field_bg_custom  	 = $row['field_bg_custom'];
		$theme_properties->form_title_font_type    = $row['form_title_font_type'];
		$theme_properties->form_title_font_weight  = (int) $row['form_title_font_weight'];
		$theme_properties->form_title_font_style   = $row['form_title_font_style'];
		$theme_properties->form_title_font_size    = $row['form_title_font_size'];
		$theme_properties->form_title_font_color   = $row['form_title_font_color'];
		$theme_properties->form_desc_font_type    = $row['form_desc_font_type'];
		$theme_properties->form_desc_font_weight  = (int) $row['form_desc_font_weight'];
		$theme_properties->form_desc_font_style   = $row['form_desc_font_style'];
		$theme_properties->form_desc_font_size    = $row['form_desc_font_size'];
		$theme_properties->form_desc_font_color   = $row['form_desc_font_color'];
		$theme_properties->field_title_font_type    = $row['field_title_font_type'];
		$theme_properties->field_title_font_weight  = (int) $row['field_title_font_weight'];
		$theme_properties->field_title_font_style   = $row['field_title_font_style'];
		$theme_properties->field_title_font_size    = $row['field_title_font_size'];
		$theme_properties->field_title_font_color   = $row['field_title_font_color'];
		$theme_properties->guidelines_font_type    = $row['guidelines_font_type'];
		$theme_properties->guidelines_font_weight  = (int) $row['guidelines_font_weight'];
		$theme_properties->guidelines_font_style   = $row['guidelines_font_style'];
		$theme_properties->guidelines_font_size    = $row['guidelines_font_size'];
		$theme_properties->guidelines_font_color   = $row['guidelines_font_color'];
		$theme_properties->section_title_font_type    = $row['section_title_font_type'];
		$theme_properties->section_title_font_weight  = (int) $row['section_title_font_weight'];
		$theme_properties->section_title_font_style   = $row['section_title_font_style'];
		$theme_properties->section_title_font_size    = $row['section_title_font_size'];
		$theme_properties->section_title_font_color   = $row['section_title_font_color'];
		$theme_properties->section_desc_font_type    = $row['section_desc_font_type'];
		$theme_properties->section_desc_font_weight  = (int) $row['section_desc_font_weight'];
		$theme_properties->section_desc_font_style   = $row['section_desc_font_style'];
		$theme_properties->section_desc_font_size    = $row['section_desc_font_size'];
		$theme_properties->section_desc_font_color   = $row['section_desc_font_color'];
		$theme_properties->field_text_font_type    = $row['field_text_font_type'];
		$theme_properties->field_text_font_weight  = (int) $row['field_text_font_weight'];
		$theme_properties->field_text_font_style   = $row['field_text_font_style'];
		$theme_properties->field_text_font_size    = $row['field_text_font_size'];
		$theme_properties->field_text_font_color   = $row['field_text_font_color'];
		$theme_properties->border_form_width   = (int) $row['border_form_width'];
		$theme_properties->border_form_style   = $row['border_form_style'];
		$theme_properties->border_form_color   = $row['border_form_color'];
		$theme_properties->border_guidelines_width   = (int) $row['border_guidelines_width'];
		$theme_properties->border_guidelines_style   = $row['border_guidelines_style'];
		$theme_properties->border_guidelines_color   = $row['border_guidelines_color'];
		$theme_properties->border_section_width   = (int) $row['border_section_width'];
		$theme_properties->border_section_style   = $row['border_section_style'];
		$theme_properties->border_section_color   = $row['border_section_color'];
		$theme_properties->form_shadow_style	  = $row['form_shadow_style'];
		$theme_properties->form_shadow_size	  	  = $row['form_shadow_size'];
		$theme_properties->form_shadow_brightness = $row['form_shadow_brightness'];
		$theme_properties->form_button_type	  	  = $row['form_button_type'];
		$theme_properties->form_button_text	  	  = $row['form_button_text'];
		$theme_properties->form_button_image	  = $row['form_button_image'];
		$theme_properties->advanced_css	  		  = $row['advanced_css'];

		/** Form Logo **/
		$form_logo_style  = "#main_body h1 a";
		$form_logo_style .= "\n"."{"."\n";

		$form_logo_height = 40;

		if($theme_properties->logo_type == 'disabled'){ //logo disabled
			$form_logo_style .= "background-image: none;"."\n";
		}else if($theme_properties->logo_type == 'default'){//default logo
			$form_logo_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->logo_default_image}');"."\n";
			$form_logo_style .= "background-repeat: no-repeat;"."\n";

 		}else if($theme_properties->logo_type == 'custom'){//custom logo
			$form_logo_style .= "background-image: url('{$theme_properties->logo_custom_image}');"."\n";
			$form_logo_height  = $theme_properties->logo_custom_height;
		}

		/* added to help logo */

		$form_logo_style .= "display: block;background-repeat: no-repeat;"."\n";


		$form_logo_style .= "height: {$form_logo_height}px;"."\n";
		$form_logo_style .= "}"."\n\n";

/*

		$form_logo_style .= ".form_success div { margin-top:300px;"."\n";
		$form_logo_style .= "}"."\n\n";

*/

	$form_logo_style .= "#footer { clear: both;  color: #999999; text-align: center; width: 640px; padding-bottom: 15px; font-size: 85%;}"."\n\n";



		$form_logo_style .= "#main_body .form_success h2 { font-size: 17px !important;  background-color: #d4edda;
    border-color: #c3e6cb; padding-top: 8px; padding-bottom: 8px; padding-left: 19px; color: #155724 !important; margin-top: 20px; border: 1px solid transparent; border-radius: .25rem; } #form_container h1{ margin: 0px; } #form_container {  border-width: 3px; border-style: double; border-color: #000000;max-width:640px; width: 100%; padding:5px;margin: 0 auto; } "."\n";

		$form_logo_style .= "#main_body form li span label {display: initial; }"."\n";
		$form_logo_style .= ".address label {  display: block !important; } div#la_progress_percentage { height: 20px; }"."\n";









		$css_content .= $form_logo_style;

		/** Wallpaper **/
		$form_wallpaper_style = "html";
		$form_wallpaper_style .= "\n"."{"."\n";

		if($theme_properties->wallpaper_bg_type == 'color'){
			$form_wallpaper_style .= "background-color: {$theme_properties->wallpaper_bg_color};"."\n";
			$form_wallpaper_style .= "background-image: none;"."\n";
		}else if($theme_properties->wallpaper_bg_type == 'pattern'){
			$form_wallpaper_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->wallpaper_bg_pattern}');"."\n";
			$form_wallpaper_style .= "background-repeat: repeat;"."\n";
		}else if($theme_properties->wallpaper_bg_type == 'custom'){
			$form_wallpaper_style .= "background-image: url('{$theme_properties->wallpaper_bg_custom}');"."\n";
			$form_wallpaper_style .= "background-repeat: repeat;"."\n";
		}

		$form_wallpaper_style .= "}"."\n\n";
		$css_content .= $form_wallpaper_style;

		/** Form Header **/
		$form_header_style = "#main_body h1";
		$form_header_style .= "\n"."{"."\n";

		if($theme_properties->header_bg_type == 'color'){
			$form_header_style .= "background-color: {$theme_properties->header_bg_color};"."\n";
			$form_header_style .= "background-image: none;"."\n";
		}else if($theme_properties->header_bg_type == 'pattern'){
			$form_header_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->header_bg_pattern}');"."\n";
			$form_header_style .= "background-repeat: repeat;"."\n";
		}else if($theme_properties->header_bg_type == 'custom'){
			$form_header_style .= "background-image: url('{$theme_properties->header_bg_custom}');"."\n";
			$form_header_style .= "background-repeat: repeat;"."\n";
		}

		$form_header_style .= "}"."\n\n";
		$css_content .= $form_header_style;

		/** Form Background **/
		$form_container_style = "#form_container";
		$form_container_style .= "\n"."{"."\n";

		if($theme_properties->form_bg_type == 'color'){
			$form_container_style .= "background-color: {$theme_properties->form_bg_color};"."\n";
		}else if($theme_properties->form_bg_type == 'pattern'){
			$form_container_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->form_bg_pattern}');"."\n";
			$form_container_style .= "background-repeat: repeat;"."\n";
		}else if($theme_properties->form_bg_type == 'custom'){
			$form_container_style .= "background-image: url('{$theme_properties->form_bg_custom}');"."\n";
			$form_container_style .= "background-repeat: repeat;"."\n";
		}

		/** Form Border **/
		if(!empty($theme_properties->border_form_width)){
			$form_container_style .= "border-width: {$theme_properties->border_form_width}px;"."\n";
		}else{
			$form_container_style .= "border-width: 0px;"."\n";
		}

		if(!empty($theme_properties->border_form_style)){
			$form_container_style .= "border-style: {$theme_properties->border_form_style};"."\n";
		}

		if(!empty($theme_properties->border_form_color)){
			$form_container_style .= "border-color: {$theme_properties->border_form_color};"."\n";
		}

		$form_container_style .= "}"."\n\n";
		$css_content .= $form_container_style;

		/** Field Highlight **/
		$field_highlight_style = "#main_body form li.highlighted,#main_body .matrix tbody tr:hover td,#itauditmachine_review_table tr.alt";
		$field_highlight_style .= "\n"."{"."\n";

		if($theme_properties->highlight_bg_type == 'color'){
			$field_highlight_style .= "background-color: {$theme_properties->highlight_bg_color};"."\n";
		}else if($theme_properties->highlight_bg_type == 'pattern'){
			$field_highlight_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->highlight_bg_pattern}');"."\n";
			$field_highlight_style .= "background-repeat: repeat;"."\n";
		}else if($theme_properties->highlight_bg_type == 'custom'){
			$field_highlight_style .= "background-image: url('{$theme_properties->highlight_bg_custom}');"."\n";
			$field_highlight_style .= "background-repeat: repeat;"."\n";
		}

		$field_highlight_style .= "}"."\n\n";
		$css_content .= $field_highlight_style;

		/** Field Guidelines **/
		$field_guidelines_style = "#main_body form .guidelines";
		$field_guidelines_style .= "\n"."{"."\n";

		if($theme_properties->guidelines_bg_type == 'color'){
			$field_guidelines_style .= "background-color: {$theme_properties->guidelines_bg_color};"."\n";
		}else if($theme_properties->guidelines_bg_type == 'pattern'){
			$field_guidelines_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->guidelines_bg_pattern}');"."\n";
			$field_guidelines_style .= "background-repeat: repeat;"."\n";
		}else if($theme_properties->guidelines_bg_type == 'custom'){
			$field_guidelines_style .= "background-image: url('{$theme_properties->guidelines_bg_custom}');"."\n";
			$field_guidelines_style .= "background-repeat: repeat;"."\n";
		}

		//guidelines border
		if(!empty($theme_properties->border_guidelines_width)){
			$field_guidelines_style .= "border-width: {$theme_properties->border_guidelines_width}px;"."\n";
		}else{
			$field_guidelines_style .= "border-width: 0px;"."\n";
		}

		if(!empty($theme_properties->border_guidelines_style)){
			$field_guidelines_style .= "border-style: {$theme_properties->border_guidelines_style};"."\n";
		}

		if(!empty($theme_properties->border_guidelines_color)){
			$field_guidelines_style .= "border-color: {$theme_properties->border_guidelines_color};"."\n";
		}

		$field_guidelines_style .= "}"."\n\n";
		$css_content .= $field_guidelines_style;

		//guidelines font
		$field_guidelines_text_style = "#main_body form .guidelines small";
		$field_guidelines_text_style .= "\n"."{"."\n";

		if(!empty($theme_properties->guidelines_font_type)){
			$field_guidelines_text_style .= "font-family: '{$theme_properties->guidelines_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->guidelines_font_weight)){
			$field_guidelines_text_style .= "font-weight: {$theme_properties->guidelines_font_weight};"."\n";
		}

		if(!empty($theme_properties->guidelines_font_style)){
			$field_guidelines_text_style .= "font-style: {$theme_properties->guidelines_font_style};"."\n";
		}

		if(!empty($theme_properties->guidelines_font_size)){
			$field_guidelines_text_style .= "font-size: {$theme_properties->guidelines_font_size};"."\n";
		}

		if(!empty($theme_properties->guidelines_font_color)){
			$field_guidelines_text_style .= "color: {$theme_properties->guidelines_font_color};"."\n";
		}

		$field_guidelines_text_style .= "}"."\n\n";
		$css_content .= $field_guidelines_text_style;


		/** Field Box **/
		$field_box_style = "#main_body input.text,#main_body input.file,#main_body textarea.textarea,#main_body select.select,#main_body input.checkbox,#main_body input.radio";
		$field_box_style .= "\n"."{"."\n";

		if($theme_properties->field_bg_type == 'color'){
			$field_box_style .= "background-color: {$theme_properties->field_bg_color};"."\n";
		}else if($theme_properties->field_bg_type == 'pattern'){
			$field_box_style .= "background-image: url('{$la_settings['base_url']}images/form_resources/{$theme_properties->field_bg_pattern}');"."\n";
			$field_box_style .= "background-repeat: repeat;";
		}else if($theme_properties->field_bg_type == 'custom'){
			$field_box_style .= "background-image: url('{$theme_properties->field_bg_custom}');"."\n";
			$field_box_style .= "background-repeat: repeat;"."\n";
		}

		//field text values
		if(!empty($theme_properties->field_text_font_type)){
			$field_box_style .= "font-family: '{$theme_properties->field_text_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
			$font_family_array .= $theme_properties->field_text_font_type;
		}

		if(!empty($theme_properties->field_text_font_weight)){
			$field_box_style .= "font-weight: {$theme_properties->field_text_font_weight};"."\n";
		}

		if(!empty($theme_properties->field_text_font_style)){
			$field_box_style .= "font-style: {$theme_properties->field_text_font_style};"."\n";
		}

		if(!empty($theme_properties->field_text_font_size)){
			$field_box_style .= "font-size: {$theme_properties->field_text_font_size};"."\n";
		}

		if(!empty($theme_properties->field_text_font_color)){
			$field_box_style .= "color: {$theme_properties->field_text_font_color};"."\n";
		}

		$field_box_style .= "}"."\n\n";
		$css_content .= $field_box_style;

		/** Review Table, value section (right column) **/
		//this is similar as field box above, except without background
		$review_table_value_style = "#itauditmachine_review_table td.la_review_value";
		$review_table_value_style .= "\n"."{"."\n";

		if(!empty($theme_properties->field_text_font_type)){
			$review_table_value_style .= "font-family: '{$theme_properties->field_text_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->field_text_font_weight)){
			$review_table_value_style .= "font-weight: {$theme_properties->field_text_font_weight};"."\n";
		}

		if(!empty($theme_properties->field_text_font_style)){
			$review_table_value_style .= "font-style: {$theme_properties->field_text_font_style};"."\n";
		}

		if(!empty($theme_properties->field_text_font_size)){
			$review_table_value_style .= "font-size: {$theme_properties->field_text_font_size};"."\n";
		}

		//on review page, special for the value color should be the same as label color
		if(!empty($theme_properties->field_title_font_color)){
			$review_table_value_style .= "color: {$theme_properties->field_title_font_color};"."\n";
		}

		$review_table_value_style .= "}"."\n\n";
		$css_content .= $review_table_value_style;

		/** Form Title **/
		$form_title_style = "#main_body .form_description h2,#main_body .form_success h2";
		$form_title_style .= "\n"."{"."\n";

		if(!empty($theme_properties->form_title_font_type)){
			$form_title_style .= "font-family: '{$theme_properties->form_title_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->form_title_font_weight)){
			$form_title_style .= "font-weight: {$theme_properties->form_title_font_weight};"."\n";
		}

		if(!empty($theme_properties->form_title_font_style)){
			$form_title_style .= "font-style: {$theme_properties->form_title_font_style};"."\n";
		}

		if(!empty($theme_properties->form_title_font_size)){
			$form_title_style .= "font-size: {$theme_properties->form_title_font_size};"."\n";
		}

		if(!empty($theme_properties->form_title_font_color)){
			$form_title_style .= "color: {$theme_properties->form_title_font_color};"."\n";
		}

		$form_title_style .= "}"."\n\n";
		$css_content .= $form_title_style;

		/** Form Description **/
		$form_desc_style = "#main_body .form_description p,#main_body form ul.payment_list_items li";
		$form_desc_style .= "\n"."{"."\n";

		if(!empty($theme_properties->form_desc_font_type)){
			$form_desc_style .= "font-family: '{$theme_properties->form_desc_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->form_desc_font_weight)){
			$form_desc_style .= "font-weight: {$theme_properties->form_desc_font_weight};"."\n";
		}

		if(!empty($theme_properties->form_desc_font_style)){
			$form_desc_style .= "font-style: {$theme_properties->form_desc_font_style};"."\n";
		}

		if(!empty($theme_properties->form_desc_font_size)){
			$form_desc_style .= "font-size: {$theme_properties->form_desc_font_size};"."\n";
		}

		if(!empty($theme_properties->form_desc_font_color)){
			$form_desc_style .= "color: {$theme_properties->form_desc_font_color};"."\n";
		}

		$form_desc_style .= "}"."\n\n";
		$css_content .= $form_desc_style;

		/** Pagination Text **/
		$pagination_desc_style = "#main_body form li span.ap_tp_text";
		$pagination_desc_style .= "\n"."{"."\n";

		if(!empty($theme_properties->form_desc_font_color)){
			$pagination_desc_style .= "color: {$theme_properties->form_desc_font_color};"."\n";
		}

		$pagination_desc_style .= "}"."\n\n";
		$css_content .= $pagination_desc_style;


		/** Field Title **/
		$field_title_style 	   = "#main_body label.description,#main_body .matrix caption,#main_body .matrix td.first_col,#main_body form li.total_payment span,#itauditmachine_review_table td.la_review_label";
		$field_sub_title_style = "#main_body form li span label,#main_body label.choice,#main_body .matrix th,#main_body form li span.symbol,.la_sigpad_clear,#main_body form li div label";

		$field_title_style .= "\n"."{"."\n";
		$field_sub_title_style .= "\n"."{"."\n";

		if(!empty($theme_properties->field_title_font_type)){
			$field_title_style .= "font-family: '{$theme_properties->field_title_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
			$field_sub_title_style .= "font-family: '{$theme_properties->field_title_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->field_title_font_weight)){
			$field_title_style .= "font-weight: {$theme_properties->field_title_font_weight};"."\n";
		}

		if(!empty($theme_properties->field_title_font_style)){
			$field_title_style .= "font-style: {$theme_properties->field_title_font_style};"."\n";
		}

		if(!empty($theme_properties->field_title_font_size)){
			$field_title_style .= "font-size: {$theme_properties->field_title_font_size};"."\n";
		}

		if(!empty($theme_properties->field_title_font_color)){
			$field_title_style .= "color: {$theme_properties->field_title_font_color};"."\n";
			$field_sub_title_style .= "color: {$theme_properties->field_title_font_color};"."\n";
		}

		$field_title_style .= "}"."\n\n";
		$css_content .= $field_title_style;

		$field_sub_title_style .= "}"."\n\n";
		$css_content .= $field_sub_title_style;

		/** Section Title **/
		$section_title_style = "#main_body form .section_break h3,#itauditmachine_review_table td .la_section_title";
		$section_title_style .= "\n"."{"."\n";

		if(!empty($theme_properties->section_title_font_type)){
			$section_title_style .= "font-family: '{$theme_properties->section_title_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->section_title_font_weight)){
			$section_title_style .= "font-weight: {$theme_properties->section_title_font_weight};"."\n";
		}

		if(!empty($theme_properties->section_title_font_style)){
			$section_title_style .= "font-style: {$theme_properties->section_title_font_style};"."\n";
		}

		if(!empty($theme_properties->section_title_font_size)){
			$section_title_style .= "font-size: {$theme_properties->section_title_font_size};"."\n";
		}

		if(!empty($theme_properties->section_title_font_color)){
			$section_title_style .= "color: {$theme_properties->section_title_font_color};"."\n";
		}

		$section_title_style .= "}"."\n\n";
		$css_content .= $section_title_style;

		/** Section Description **/
		$section_desc_style = "#main_body form .section_break p,#itauditmachine_review_table td .la_section_content";
		$section_desc_style .= "\n"."{"."\n";

		if(!empty($theme_properties->section_desc_font_type)){
			$section_desc_style .= "font-family: '{$theme_properties->section_desc_font_type}','Lucida Grande',Tahoma,Arial,sans-serif;"."\n";
		}

		if(!empty($theme_properties->section_desc_font_weight)){
			$section_desc_style .= "font-weight: {$theme_properties->section_desc_font_weight};"."\n";
		}

		if(!empty($theme_properties->section_desc_font_style)){
			$section_desc_style .= "font-style: {$theme_properties->section_desc_font_style};"."\n";
		}

		if(!empty($theme_properties->section_desc_font_size)){
			$section_desc_style .= "font-size: {$theme_properties->section_desc_font_size};"."\n";
		}

		if(!empty($theme_properties->section_desc_font_color)){
			$section_desc_style .= "color: {$theme_properties->section_desc_font_color};"."\n";
		}

		$section_desc_style .= "}"."\n\n";
		$css_content .= $section_desc_style;

		/** Section Block **/
		$section_block_style = "#main_body form li.section_break";
		$section_block_style .= "\n"."{"."\n";

		if(!empty($theme_properties->border_section_width)){
			$section_block_style .= "border-top-width: {$theme_properties->border_section_width}px;"."\n";
		}else{
			$section_block_style .= "border-top-width: 0px;"."\n";
		}

		if(!empty($theme_properties->border_section_style)){
			$section_block_style .= "border-top-style: {$theme_properties->border_section_style};"."\n";
		}

		if(!empty($theme_properties->border_section_color)){
			$section_block_style .= "border-top-color: {$theme_properties->border_section_color};"."\n";
		}

		$section_block_style .= "}"."\n\n";
		$css_content .= $section_block_style;

		/** Advanced CSS Code **/
		if(!empty($theme_properties->advanced_css)){
			$css_content .= "\n\n".'/** Advanced CSS **/'."\n\n";
			$css_content .= $theme_properties->advanced_css;
		}

		return $css_content;

	}

	//generate the links to the fonts
	function la_theme_get_fonts_link($dbh,$theme_id){

		$font_family_array = array();

		$query = "SELECT
						form_title_font_type,
						form_desc_font_type,
						field_title_font_type,
						guidelines_font_type,
						section_title_font_type,
						section_desc_font_type,
						field_text_font_type
					FROM
						`".LA_TABLE_PREFIX."form_themes`
				   WHERE
				   		theme_id=? and `status`=1";
		$params = array($theme_id);

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$font_family_array[] = $row['form_title_font_type'];
		$font_family_array[] = $row['form_desc_font_type'];
		$font_family_array[] = $row['field_title_font_type'];
		$font_family_array[] = $row['guidelines_font_type'];
		$font_family_array[] = $row['section_title_font_type'];
		$font_family_array[] = $row['section_desc_font_type'];
		$font_family_array[] = $row['field_text_font_type'];

		/** Build the font CSS tag **/
		if(!empty($font_family_array)){
			$font_family_joined = implode("','",$font_family_array);

			$query = "SELECT font_family,font_variants FROM ".LA_TABLE_PREFIX."fonts WHERE font_family IN('{$font_family_joined}')";
			$params = array();

			$sth = la_do_query($query,$params,$dbh);
			$font_css_array = array();
			while($row = la_do_fetch_result($sth)){
				$font_css_array[] = urlencode($row['font_family']).":".$row['font_variants'];
			}

			$ssl_suffix = la_get_ssl_suffix();

			$font_css_markup = implode('|',$font_css_array);
			if(!empty($font_css_array)){
				$font_css_markup = "<link href='https://fonts.googleapis.com/css?family={$font_css_markup}' rel='stylesheet' type='text/css'>\n";
			}else{
				$font_css_markup = '';
			}
		}

		return $font_css_markup;
	}
?>
