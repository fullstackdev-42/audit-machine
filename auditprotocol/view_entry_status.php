<?php
/***************
 * IT Audit Machine
 *
 * Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 * permission from http://lazarusalliance.com
 *
 * More info at: http://lazarusalliance.com
 ********************
 * /*****************************************************************************************************************************/
require( 'includes/init.php' );

require( 'config.php' );
require( 'includes/db-core.php' );
require( 'includes/helper-functions.php' );
require( 'includes/check-session.php' );

require( 'includes/language.php' );
require( 'includes/entry-functions.php' );
require( 'includes/post-functions.php' );
require( 'includes/users-functions.php' );

$form_id = (int)trim( $_POST['form_id'] );
$entry_id = (int)trim( $_POST['entry_id'] );
$filters = $_POST['filter']; # FILTER STATUS QUERY
//console_log($filters,'status filter (from JS array)');

if ( empty( $form_id ) || empty( $entry_id ) )
	die( "Invalid Request" );

# DATABASE INIT
$dbh = la_connect_db();
$la_settings = la_get_settings( $dbh );

# AUTHENTICATION; user has sufficient permissions?
if ( empty( $_SESSION['la_user_privileges']['priv_administer'] ) ) {
	$user_perms = la_get_user_permissions( $dbh, $form_id, $_SESSION['la_user_id'] );

	//this page need edit_entries or view_entries permission
	if ( empty( $user_perms['edit_entries'] ) && empty( $user_perms['view_entries'] ) ) {
		$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";

		$ssl_suffix = la_get_ssl_suffix();
		header( "Location: restricted.php" );
		exit;
	}
} # authentication

# ENTRY DATA //get entry information (date created/updated/ip address/resume key)

# GET COMPANY
$start = $entry_id - 1;
$query1 = "select DISTINCT(company_id) from ".LA_TABLE_PREFIX."form_{$form_id} LIMIT ".$start.",1";
$params = array();
$sth1 = la_do_query( $query1, $params, $dbh );
$row1 = la_do_fetch_result( $sth1 );
$company_id = (int)$row1['company_id'] ?: 0;

# GET COMPANY FORM
$query = "select * from ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id='".$row1['company_id']."'";
$params = array();
$sth = la_do_query( $query, $params, $dbh );

# FORM NAME
$form_full_name = '';
//get form name
$query = "select form_name
			     from
			     	 ".LA_TABLE_PREFIX."forms
			    where
			    	 form_id = ?";
$params = array( $form_id );
$sth = la_do_query( $query, $params, $dbh );
$row = la_do_fetch_result( $sth );
if ( !empty( $row ) ) {
	$form_full_name = $row['form_name'];
	$row['form_name'] = la_trim_max_length( $row['form_name'], 65 );
	$form_name = htmlspecialchars( $row['form_name'] );
} else {
	die( "Error. Unknown form ID." );
} # form name

# STATUS SUMMARY
if ( isset( $_REQUEST['entry_id'] ) ) {

	if ( !$row1 ) {
		header( "location:manage_entries.php?id={$form_id}" );
		exit();
	}

	# COMPANY
	//	console_log( $statusCompanyId, 'statusCompanyId' );

	# TOTAL STATUS (for company)
	$cntStatusArr = array( 0,
	                       0,
	                       0,
	                       0 );
	$sql_query = "SELECT `indicator`, count(`indicator`) `cnt` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = {$form_id} AND `company_id` = {$row1['company_id']} GROUP BY indicator";
	$result = la_do_query( $sql_query, array(), $dbh );
	echo '<script type="text/javascript">';
	while ( $row = la_do_fetch_result( $result ) ) {
		$cntStatusArr[$row['indicator']] = $row['cnt'];
		// SET DIALOG TOTALS
		echo '$("#dialog-status-'.$row['indicator'].', #dialog-list-status-'.$row['indicator'].'-count").text("'.$row['cnt'].'");';
	}
	echo '</script>';
	# total status (company)

} // isset( $_REQUEST['entry_id']
# status summary

# OUTPUT
?>

<div id="content" class="full">
	<div class="post view_entry">

		<?php la_show_message(); ?>
		<div class="content_body">
			<div id="ve_details_status"
			     data-formid="<?php echo $form_id; ?>"
			     data-entryid="<?php echo $entry_id; ?>"
			>
				<?php
				# FILTER
				if ( is_array( $filters ) ) {
					//						console_log($filters,'filters before');
					$filters = join( ',', array_map( 'intval', $filters ) );
					//						console_log($filters,'filters after');
					$filter = ' AND indicator NOT IN ('.$filters.') ';
					//						console_log($filter,'filter query');
				} else $filter = null;
				# filter

				# ENTRY DETAILS
				$entry_details_query = '
							select
								e.element_id element_id,
								e.element_type element_type,
								e.element_title element_title,
							  si.indicator indicator
							from
								'.LA_TABLE_PREFIX.'form_elements e
							LEFT JOIN '.LA_TABLE_PREFIX.'element_status_indicator si
							ON si.element_id = e.element_id
										AND si.form_id = e.form_id
								where
										e.form_id=? and
										e.element_status = 1 and
										si.company_id = '.$company_id.'
										'.$filter.'
								group by
								element_id
								order by
									indicator,
									e.element_position
							';
				$params = array( $form_id );
				$entry_details = la_do_query( $entry_details_query, $params, $dbh );
				# LOOP (entry details)
				$section_heading = null;
				foreach ( $entry_details as $data ) {
					//														console_log($data);

					$status_indicator = "";
					$indicator_count = 0;

					# STATUS
					if ( in_array( $data['element_type'],
					 array( 'text',
					        'textarea',
					        'file',
					        'radio',
					        'checkbox',
					        'select',
					        'signature',
					        'matrix' ) ) ) {
						if ( isset( $data['indicator'] ) ) {
							$indicator_count = $data[$data['indicator']];
						}

						if ( isset( $data['indicator'] ) && $data['indicator'] === '0' ) {
							$status_indicator_image = 'Circle_Gray.png';
						} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '1' ) {
							$status_indicator_image = 'Circle_Red.png';
						} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '2' ) {
							$status_indicator_image = 'Circle_Yellow.png';
						} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '3' ) {
							$status_indicator_image = 'Circle_Green.png';
						} else {
							$status_indicator_image = 'Circle_Gray.png';
						}

						$status_indicator = '
							<img
							 class="status-icon status-icon-action-view-disabled"
							 data-form_id="'.$form_id.'"
							 data-element_id="'.$data['element_id'].'"
							 data-company_id="'.$company_id.'"
							 data-indicator="'.$indicator_count.'"
							 src="images/'.$status_indicator_image.'"
							 style="margin-left:8px; cursor:pointer;"
							/>'; // TODO .status-icon-action-view; link a unique class with colored &bullet;
					}
					# status

					# SECTION HEADING; note order of <ul> & </ul>
					if ( $section_heading !== $data['indicator'] ) {
						$section_heading = $data['indicator'];
						$section_heading_display = true;
					}
					if ( $section_heading_display ) {
						$section_heading_display = false;
						switch ( $section_heading ) {
							case 0: # GREY / 0
								echo '<ul id="dialog-list-status-0-container" class="dialog-list-status-container">';
								echo '<h2 id="dialog-list-status-0-heading" class="dialog-list-status-heading"><span id="dialog-list-status-0-count" class="dialog-list-status-count"></span></h2>';
								break;
							case 1: # RED / 1
								echo '</ul>';
								echo '<ul id="dialog-list-status-1-container" class="dialog-list-status-container">';
								echo '<h2 id="dialog-list-status-1-heading" class="dialog-list-status-heading"><span id="dialog-list-status-1-count" class="dialog-list-status-count"></span></h2>';
								break;
							case 2: # YELLOW / 2
								echo '</ul>';
								echo '<ul id="dialog-list-status-2-container" class="dialog-list-status-container">';
								echo '<h2 id="dialog-list-status-2-heading" class="dialog-list-status-heading"><span id="dialog-list-status-2-count" class="dialog-list-status-count"></span></h2>';
								break;
							case 3: # GREEN / 3
								echo '</ul>';
								echo '<ul id="dialog-list-status-3-container" class="dialog-list-status-container">';
								echo '<h2 id="dialog-list-status-3-heading" class="dialog-list-status-heading"><span id="dialog-list-status-3-count" class="dialog-list-status-count"></span></h2>';
								break;
						}
					} # section heading

					# FIELD LABEL (/w status indicator)
					echo '<li class="field_item element_status_'.$data['indicator'].'">'.$status_indicator.'&nbsp;'.$data['element_title'].'</li>';
				} # foreach $entry_details as $data
				?>
			</ul>
		</div>

	</div>
	<!-- /end of content_body -->

</div>
<!-- /.post -->
</div>
<!-- /#content -->

<div id="status-processing-pdf-dialog"
     style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100;">
	<div style="font-weight: bold; font-size: 150%; text-align: center; vertical-align: middle; position: absolute; top: 35%; left: 40%; color: black; background-color: white; padding: 1rem 0rem; width: 24rem; border-radius: 0.5rem;">
		Generating PDF...<br>
		<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
	</div>
</div>

<?php # JAVASCRIPT ?>
<script type="text/javascript">
	$(document).ready(function (e) {

		// DIALOG TITLE
		$("#dialog-status").dialog({
			title: "<span class=\"form-name\"><?= $form_name ?></span> <span class=\"form-id\"> <span class=\"sub\">Entry</span> #<?= $entry_id ?></span>"
		});

		// PDF EXPORT
		$('#dialog-status-pdf').click(function () {
			event.preventDefault();
			event.stopImmediatePropagation();
			var message_div = $('div#status-processing-pdf-dialog');
			message_div.css("visibility", "visible");
			var _form_details = $('div#ve_details_status').html();

			$.ajax({
				type: "POST",
				async: true,
				url: "generate_entries_pdf.php",
				data: {
					post_csrf_token: $('#csrf-token-meta').attr('content'),
					form_id: <?= $form_id ?>,
					form_name: '<?= $form_full_name ?>',
					form_details: _form_details
				},
				cache: false,
				global: false,
				error: function (xhr, text_status, e) {
					//error, display the generic error message
					console.log(xhr);
					console.log(text_status);
					console.log(e);
				},
				success: function (response) {
					response = JSON.parse(response);
					$('#csrf-token-meta').attr('content', response.csrf_token);
					$('#post_csrf_token').val(response.csrf_token);
					var message_div = $('div#status-processing-pdf-dialog');
					message_div.css("visibility", "hidden");
					window.location.href = 'generate_entries_pdf.php?download_pdf=true&download_pdf_name=' + response['pdf_info'].pdffile_name;
				},
				complete: function () {
					setAjaxDefaultParam();
				}
			});
		}); // pdf export


	}); // document ready
</script>
<?php # javascript ?>
