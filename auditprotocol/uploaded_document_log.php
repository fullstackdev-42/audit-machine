<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/	
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/users-functions.php');
	//require($_SERVER['DOCUMENT_ROOT'].'/common-lib/web3/web3.class.php');
	 require('../common-lib/web3/web3.class.php');

	$web3Ethereum = new Web3Ethereum();
	
	$user_id = (int) base64_decode(trim($_GET['user_id']));
	$nav 	 = trim($_GET['nav']);

	if(empty($user_id)){
		die("Invalid Request");
	}

	//check user privileges, is this user has privilege to administer IT Audit Machine?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to administer IT Audit Machine.";

		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	}

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);
	
	//get user information
	if( isset($_GET['is_portal']) && $_GET['is_portal'] == 1 ) {
		$query = "SELECT email as user_email, full_name as user_fullname FROM ".LA_TABLE_PREFIX."ask_client_users WHERE client_user_id=?";
		$params = array($user_id);
	} else {
		$query = "SELECT user_email, user_fullname FROM ".LA_TABLE_PREFIX."users WHERE user_id=? and `status` > 0";
		$params = array($user_id);
	}
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	$user_profile = $row;


	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
<style type="text/css">
ul.green li {
	width : auto;
}
#la_pagination .page-link {
	color: inherit;
}
</style>

{$perm_style}
EOT;

	$current_nav_tab = 'manage_users';
	require('includes/header.php'); 

?>
<div id="content" class="full">
	<div class="post view_user">
		<div class="content_header">
			<div class="content_header_title">
				<div style="float: left">
					<h2><a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="breadcrumb"><?php echo htmlspecialchars($user_profile['user_fullname']); ?></a> <img src="images/icons/resultset_next.gif" /> <a class="breadcrumb" href="javascript:void(0)">Uploaded Files Document Log</a></h2>
				</div>
				<div style="clear: both; height: 1px"></div>
			</div>
		</div>
		<div class="content_body">
			<div id="entries_actions" class="gradient_red">
				<ul>
					<li><a href="#" id="document_log_export"><img src="images/navigation/ED1C2A/16x16/Export.png"> Export</a></li>
				</ul>
			</div>
			<div style="clear: both"></div>
			<div id="entries_container">
				<table cellspacing="0" cellpadding="0" border="0" width="100%" id="entries_table" data-user-id="<?php echo $user_id; ?>">
					<thead>
						<tr>
							<th scope="col" class="me_number" style="width:5%;">#</th>
							<th scope="col" style="width:10%;">Form #</th>
							<th scope="col" style="width:55%;">File Name</th>
							<th scope="col" style="width:10%;">Datetime</th>
							<th scope="col" style="width:10%;">Added to Chain</th>
							<th scope="col" style="width:10%;text-align: center">Hash Matched</th>
						</tr>
					</thead>
					<tbody>
						<?php
							//add pagination
							$showRecordPerPage = 20;
							if(isset($_GET['page']) && !empty($_GET['page'])){
								$currentPage = (int)$_GET['page'];
							}else{
								$currentPage = 1;
							}

							$startFrom = ($currentPage * $showRecordPerPage) - $showRecordPerPage;
							$endat = ($currentPage * $showRecordPerPage);

							$log_table = LA_TABLE_PREFIX."eth_file_data";
							if ( empty($_GET['is_portal']) )
							$_GET['is_portal'] = 0;

							$query = "SELECT count(*) as row_count FROM `{$log_table}` where user_id = :user_id AND is_portal= :is_portal";
							$count_result = la_do_query($query, array(':user_id' => $user_id, ':is_portal' => $_GET['is_portal']), $dbh);

							$result_log_table = la_do_fetch_result($count_result);
							$row_count = $result_log_table['row_count'];
							
							if( $row_count > 0 ) {
								$lastPage = ceil($row_count/$showRecordPerPage);
								$firstPage = 1;
								$nextPage = $currentPage + 1;
								$previousPage = $currentPage - 1;

								$query = "SELECT `al`.*, `f`.`form_name` FROM `{$log_table}` `al` left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = :user_id AND `al`.`is_portal` = :is_portal order by `al`.`id` DESC LIMIT $showRecordPerPage offset $startFrom";
								$result = la_do_query($query,array(':user_id' => $user_id, ':is_portal' => $_GET['is_portal']),$dbh);

								$i = 0;
								while($row = la_do_fetch_result($result)){
									$i++;
									$row_id = $row['id'];
									$form_id = $row['form_id'];
									$form_name = $row['form_name'];
									$entry_id = $row['entry_id'];
									$date = date("m/d/Y H:i", $row['action_datetime']);

									$full_file_name = $row['data'];
									$file_name = substr($full_file_name,strpos($full_file_name, '-')+1);
									?>
									<tr id="row_<?=$row_id?>">
										<td class="me_number"><?php echo $i; ?></td>
										<td><div><a href="manage_forms.php?id=<?=$form_id?>&hl=1" title="<?=$form_name?>"><?=$form_id?></a></div></td>
										<td><div><?=$file_name?></div></td>
										<td><div><?=$date?></div></td>
										<td>
											<div>
											<?php
												switch ($row['added_to_chain']) {
												case 0:
												echo "Pending";
												break;
												case 1:
												echo "Added";
												break;
												case 2:
												echo "Error";
												break;
											} ?>
											</div>
										</td>
										<td style="text-align: center">
										<?php
										$form_dir = $la_settings['upload_dir']."/form_{$form_id}/files";
										$file_location = $form_dir.'/'.$full_file_name;
										//if file does not exist on server show error image
										if( file_exists ( $file_location ) ) {
											if( !empty($entry_id) ) {
												$file_hash = hash_file ( "sha256", $file_location );
												$result_chain = $web3Ethereum->call('getEntryByDocumentHash','0x'.$file_hash); 
												// $result_chain['documentHash'] = '';
												if( !empty($result_chain['documentHash']) ) {
													echo "<img src=\"images/Checkmark-icon.png\">";
												} else {
													echo "<img src=\"images/cancel.png\">";
												}
											}
										} else {
											echo "<img src=\"images/cancel.png\">";
										}
										?>
										</td>
									</tr>
								<?php } ?>
								<?php
									$encoded_user_id = base64_encode($user_id);
									$link_required_params = '';
									$link_required_params = "user_id=".$encoded_user_id;
								?>
					</tbody>
				</table>
				<ul id="la_pagination" class="pages green small">
					<?php if($currentPage != $firstPage) { ?>
						<a class="page-link" href="?<?=$link_required_params?>&page=<?php echo $firstPage ?>" tabindex="-1" aria-label="Previous">
							<li class="page">
								<span aria-hidden="true">First</span>
							</li>
						</a>
					<?php } ?>
					<?php if($currentPage >= 2) { ?>
						<a class="page-link" href="?<?=$link_required_params?>&page=<?php echo $previousPage ?>">
							<li class="page">
								<?php echo $previousPage ?>
							</li>
						</a>
					<?php } ?>
						<a class="page-link" href="?<?=$link_required_params?>&page=<?php echo $currentPage ?>">
							<li class="page current_page">
								<?php echo $currentPage ?>
							</li>
						</a>
					<?php if($currentPage != $lastPage) { ?>
						<a class="page-link" href="?<?=$link_required_params?>&page=<?php echo $nextPage ?>">
							<li class="page">
								<?php echo $nextPage ?>
							</li>
						</a>
						<a class="page-link" href="?<?=$link_required_params?>&page=<?php echo $lastPage ?>" aria-label="Next">
							<li class="page">
								<span aria-hidden="true">Last</span>
							</li>
						</a>
					<?php } ?>
				</ul>
				<?php
					} else {
				?>
						<tr id="row_<?php echo $row['id']; ?>">
							<td colspan="6">No data found!</td>
						</tr>
					</tbody>
				</table>
				<?php } ?>
			</div>
		</div>
		<!-- /end of content_body -->

		<div id="dialog-export-documents" title="Select File Type" class="buttons" style="display: none">
			<ul style="text-align:center;">
				<li class="gradient_blue" style="display: inline-block;margin: 5px;">
					<a id="export_as_csv" href="#" class="export_link" data-filename="<?php echo str_replace(" ", "_", $user_profile['user_fullname'])."_document_logs_".date("m_d_Y"); ?>" data-isportal="<?=$_GET['is_portal']?>">Comma Separated (.csv)</a>
				</li>
			</ul>
		</div>
	</div>
	<!-- /.post --> 
</div>
<!-- /#content -->
<?php
	
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/view_user.js"></script>
EOT;

	require('includes/footer.php'); 
?>