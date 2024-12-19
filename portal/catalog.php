<?php
/********************************************************************************
IT Audit Machine

Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com

More info at: http://lazarusalliance.com
********************************************************************************/

// enable the two lines below to hide all php errors

require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');
require('portal-header.php');

//Connect to the database
$dbh = la_connect_db();

$user_subscribed_forms = array();
$universal_form_statement = "";

/**********************************************/
/*   Fetching Company data from client table   */
/**********************************************/

$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);
// print_r($userEntities);

if(!empty($_SESSION['form_not_subscribed'])){
    echo '<div id="li_login_notification"><h5 style="color:#ef1829;">'.$_SESSION['form_not_subscribed'].'</h5></div>';
  	unset($_SESSION['form_not_subscribed']);
}

?>








<style>
#la_search_pane {
	float: left;
	width: 47%;
	margin-bottom: 10px;
	padding: 2px;
}
#la_filter_pane {
	padding-top: 10px;
	float: right;
	width: 47%;
}
#la_search_box {
	padding: 10px;
	width: 135px;
	border-radius: 9px;
	background-color: #008600;
	position: relative;
}
#la_search_box.search_focused {
	width: 300px;
}
.search_focused #filter_form_input {
	width: 275px;
}
#la_search_title {
	display: none;
	position: absolute;
	right: 120px;
	top: 100%;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #008600;
	font-size: 90%;
	padding: 0 10px 3px 10px;
	border-radius: 0 0 7px 7px;
}
#la_search_element {
	display: none;
	position: absolute;
	right: 10px;
	top: 100%;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #008600;
	font-size: 90%;
	padding: 0 10px 3px 10px;
	border-radius: 0 0 7px 7px;
}
#la_search_title a, #la_search_element a {
	color: #FFFFFF;
	-moz-text-decoration-style: wavy;
}
#la_search_title.la_pane_selected a, #la_search_element.la_pane_selected a {
	color: #FFFFFF;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.25), 0 0 1px rgba(255, 255, 255, 0.3);
	font-weight: bold;
}
.search_focused #la_search_title, .search_focused #la_search_element {
	display: block;
}
#la_filter_pane .dropui {
	float: right;
}
.highlight {
	background-color: #31d971 !important;
}
.field_listing {
    float: left;
}
</style>


















<?php
require('portal-footer.php');
?>

<script>

$(document).ready(function() {
//expand the search box

	function searchForms() {
		var la_form_list = $('ul#la_form_list');
		var filter_form_input = $('#filter_form_input').val();
			filter_form_input = filter_form_input.trim();

		var search_by = $("#la_search_title").hasClass('la_pane_selected') ? 'title' : 'element';

		if (filter_form_input != 'find form...' && filter_form_input != '') {
			var userEntities = <?php echo json_encode($userEntities); ?>;
			$.ajax({
				url : 'ajax-catalog-search.php',
				type : 'POST',
				data : {
					'search_by' : search_by,
					'search_value' : filter_form_input,
					'user_entities': userEntities
				},
				beforeSend: function(){
					$('#loader-img').show();
				},
				success: function(r){
					$('#loader-img').hide();
					var response = JSON.parse(r);

					if (!parseInt(response.error)) {
						var _htmlLi = new Array();

						$.each(response.result, function(i, v){
							_htmlLi.push(generateLi(
								{
									theme_id:v.form_theme_id,
									form_id: v.form_id,
									form_name: v.form_name,
									forms_elements: response.forms_elements[v.form_id],
									subscribe_status: v.subscribe_status
								}
							));
						});

						if (search_by == 'element') {
							la_form_list.html(_htmlLi.join('\n'));
						} else {
							la_form_list.html(_htmlLi.join('\n'));
						}
					} else {
						la_form_list.html('<li class="form_active form_visible">' + response.message + '</li>')
					}
				},
				complete:function(){

				}
			});
		}
	}


});
</script>
