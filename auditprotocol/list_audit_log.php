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
	
	$user_id = (int) base64_decode(trim($_GET['user_id']));
	$nav 	 = trim($_GET['nav']);
	$full_username = "";
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
	
	//if there is "nav" parameter, we need to determine the correct entry id and override the existing user_id
	if(!empty($nav)){
		$exclude_admin = false;

		$all_user_id_array = la_get_filtered_users_ids($dbh,$_SESSION['filter_users'],$exclude_admin);
		$user_key = array_keys($all_user_id_array,$user_id);
		$user_key = $user_key[0];

		if($nav == 'prev'){
			$user_key--;
		}else{
			$user_key++;
		}

		$user_id = $all_user_id_array[$user_key];

		//if there is no user_id, fetch the first/last member of the array
		if(empty($user_id)){
			if($nav == 'prev'){
				$user_id = array_pop($all_user_id_array);
			}else{
				$user_id = $all_user_id_array[0];
			}
		}
	}

	if( isset($_GET['client']) && !empty($_GET['client']) ) {
		//used for portal account
		$query = "SELECT 
					email as user_email,
					full_name as user_fullname,
					tsv_enable,
					`status`,
					`avatar_url`
			    FROM 
					".LA_TABLE_PREFIX."ask_client_users 
			   WHERE 
			   		client_user_id=?";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$user_profile = $row;
		$user_fullname = "<img style='margin:0px 5px;width:30px;border-radius:50%;vertical-align:middle;' src='".$la_settings["base_url"].$user_profile["avatar_url"]."'>"."[User] ".$user_profile["user_fullname"]."(".$user_profile["user_email"].")";
	} else {
		//get user information
		$query = "SELECT 
					user_email,
					user_fullname,
					priv_administer,
					priv_new_forms,
					priv_new_themes,
					last_login_date,
					last_ip_address,
					tsv_enable,
					`status` ,
					`avatar_url`,
					`is_examiner`
			    FROM 
					".LA_TABLE_PREFIX."users 
			   WHERE 
			   		user_id=? and `status` > 0";
		$params = array($user_id);
				
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);
		$user_profile = $row;
		if(empty($user_profile['is_examiner'])) {
			$user_fullname = "<img style='margin:0px 5px;width:30px;border-radius:50%;vertical-align:middle;' src='".$la_settings["base_url"].$user_profile["avatar_url"]."'>"."[Admin] ".$user_profile["user_fullname"]."(".$user_profile["user_email"].")";
		} else {
			$user_fullname = "<img style='margin:0px 5px;width:30px;border-radius:50%;vertical-align:middle;' src='".$la_settings["base_url"].$user_profile["avatar_url"]."'>"."[Examiner] ".$user_profile["user_fullname"]."(".$user_profile["user_email"].")";
		}
		//if this user is admin, all privileges should be available
		if(!empty($user_profile['priv_administer'])){
			$user_profile['priv_new_forms'] = 1;
			$user_profile['priv_new_themes'] = 1;
		}

		$is_user_suspended = false;
		if($user_profile['status'] == 2){
			$is_user_suspended = true;
		}	
		
		$privileges = array();
		if(!empty($user_profile['priv_new_forms'])){
			$privileges[] = 'Able to <strong>create new forms</strong>';
		}
		if(!empty($user_profile['priv_new_themes'])){
			$privileges[] = 'Able to <strong>create new themes</strong>';
		}

		$user_is_admin = false;

		if(!empty($user_profile['priv_administer'])){
			if($user_id == 1){
				$privileges[] = 'Able to <strong>administer IT Audit Machine</strong> (Main Administrator)';
			}else{
				$privileges[] = 'Able to <strong>administer IT Audit Machine</strong>';
			}
			$user_is_admin = true;
		}	
	}
	
	if($i >= 15){
		$perm_style =<<<EOT
<style>
	.me_center_div { padding-left: 10px; }
</style>
EOT;
	}

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
          <h2><a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="breadcrumb"><?php echo htmlspecialchars($user_profile['user_fullname']); ?></a> <img src="images/icons/resultset_next.gif" /> <a class="breadcrumb" href="javascript:void(0)">Audit Log</a></h2>
        </div>
        <div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <div class="content_body">
      <div id="entries_actions" class="gradient_red">
        <ul>
          <li><a href="#" id="entry_export"><img src="images/navigation/ED1C2A/16x16/Export.png"> Export</a></li>
        </ul>
      </div>
      <div style="clear: both"></div>
      <div id="entries_container">
        <table cellspacing="0" cellpadding="0" border="0" width="100%" id="entries_table" data-user-id="<?php echo $user_id; ?>">
          <thead>
            <tr>
              <th scope="col" class="me_number" style="width:5%;">#</th>
              <th scope="col" style="width:10%;">Form #</th>
              <th scope="col" style="width:65%;">Audit Log</th>
              <th scope="col" style="width:10%;">Datetime</th>
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

			
			if( isset($_GET['client']) && !empty($_GET['client']) ) {
				$log_table = LA_TABLE_PREFIX."audit_client_log";
			} else {
				$log_table = LA_TABLE_PREFIX."audit_log";
			}

			$hide_login_logs = $hide_login_logs_count_query = '';

			if($_SESSION['la_user_id'] != 1){
		  		$hide_login_logs = "AND `al`.`action_type_id` != 6";
		  		$hide_login_logs_count_query = "AND action_type_id != 6";
		  	}

			$query = "SELECT count(*) as row_count FROM `{$log_table}` where user_id = :user_id {$hide_login_logs_count_query}";
			$query_log_table = la_do_query($query, array(':user_id' => $user_id), $dbh);

			$result_log_table = la_do_fetch_result($query_log_table);
			$row_count = $result_log_table['row_count'];

			if( $row_count > 0 ) {

				$lastPage = ceil($row_count/$showRecordPerPage);
				$firstPage = 1;
				$nextPage = $currentPage + 1;
				$previousPage = $currentPage - 1;

			  	

			  	$query = "SELECT `al`.*, `aat`.`action_type`, `f`.`form_name` FROM `{$log_table}` `al` left join `".LA_TABLE_PREFIX."audit_action_type` `aat` on (`aat`.`action_type_id` = `al`.`action_type_id`) left join  `".LA_TABLE_PREFIX."forms` `f` on (`f`.`form_id` = `al`.`form_id`) where `al`.`user_id` = :user_id {$hide_login_logs} order by `al`.`action_datetime` DESC LIMIT $showRecordPerPage offset $startFrom";
			  	$result = la_do_query($query,array(':user_id' => $user_id),$dbh);
			
		  
		  $i = $startFrom;
		  while($row = la_do_fetch_result($result)){
			  $i++;
			  
			  if($row['action_type_id'] == 6){
				  if($_SESSION['la_user_id'] == 1){
		?>
            <tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div>&nbsp;</div></td>
              <td><div><?php echo $user_fullname; ?> <?php echo $row['action_type']; ?> from <?php echo $row['user_ip']; ?></div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
        <?php 
				  }
			  }elseif($row['action_type_id'] == 7 || $row['action_type_id'] == 8){
		?>
			<tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div><a href="manage_forms.php?id=<?php echo $row['form_id']; ?>&hl=1" title="<?php echo $row['form_name']; ?>"><?php echo $row['form_id']; ?></a></div></td>
              <td><div><?php echo $user_fullname; ?> <?php echo $row['action_text']; ?></div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
		<?php
			}elseif($row['action_type_id'] == 14 || $row['action_type_id'] == 15 || $row['action_type_id'] == 16){
				$file_name = substr($row['action_text'], strpos($row['action_text'], '-') +1 );
		?>
			<tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div><a href="manage_forms.php?id=<?php echo $row['form_id']; ?>&hl=1" title="<?php echo $row['form_name']; ?>"><?php echo $row['form_id']; ?></a></div></td>
              <td><div><?php echo $user_fullname; ?> <?php echo $row['action_type']; ?> <strong><?=$file_name?></strong>  Form #<?php echo $row['form_id']; ?></div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
		<?php
			  }elseif($row['action_type_id'] == 9 || $row['action_type_id'] == 10 || $row['action_type_id'] == 11 || $row['action_type_id'] == 12 || $row['action_type_id'] == 13){
		?>
			<tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div></div></td>
              <td>
              		<div>
              			<?php
              				$action_text_array = json_decode($row['action_text']);
              				if( isset($action_text_array->action_performed_on) ) {
              					echo $user_fullname.' <strong>'.$row['action_type'].'</strong> '.$action_text_array->action_performed_on;
              				} else {
              					echo "[Admin] ".$action_text_array->action_performed_by.' <strong>'.$row['action_type'].'</strong> '.$user_fullname;
              				}
              			?>
              		</div>
              	</td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
		<?php
			} elseif($row['action_type_id'] == 17){
		?>
            <tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td>-</td>
              <td><div><?php echo $user_fullname; ?> was forced to log out for the reason of declining assistance with access to IT Audit Machine.</div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
		<?php
			} elseif($row['action_type_id'] == 18){
		?>
            <tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div><a href="manage_forms.php?id=<?php echo $row['form_id']; ?>&hl=1" title="<?php echo $row['form_name']; ?>"><?php echo $row['form_id']; ?></a></div></td>
              <td><div><?php echo $row['action_text']; ?></div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
		<?php
			}else{
			  	$action_text = '';
			  	if( !empty($row['action_text']) && strrpos($row['action_text'], 'Session') !== false )
			  		$action_text = "({$row['action_text']})";
		?>
            <tr id="row_<?php echo $row['id']; ?>">
              <td class="me_number"><?php echo $i; ?></td>
              <td><div><a href="manage_forms.php?id=<?php echo $row['form_id']; ?>&hl=1" title="<?php echo $row['form_name']; ?>"><?php echo $row['form_id']; ?></a></div></td>
              <td><div><?php echo $user_fullname; ?> <strong><?=$row['action_type']?></strong> Form #<?php echo $row['form_id']; ?> <?=$action_text?></div></td>
              <td><div><?php echo date("m/d/Y H:i", $row['action_datetime']); ?></div></td>
            </tr>
         <?php
			  }
		  ?>
          <?php
		  		}
		  		$encoded_user_id = base64_encode($user_id);
		  		$link_required_params = '';
		  		$link_required_params = "user_id=".$encoded_user_id;

		  		if( isset($_GET['client']) && !empty($_GET['client']) )
		  			$link_required_params .= "&client=1";

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
              <td colspan="4">No data found!</td>
            </tr>
            </tbody>
        </table>
          <?php  
		  }
		  ?>
          
      </div>
    </div>
    <!-- /end of content_body --> 
	<div id="dialog-export-entries" title="Select File Type" class="buttons" style="display: none">
		<ul>
			<!--<li class="gradient_blue"><a id="export_as_excel" href="#" class="export_link">Excel File (.xls)</a></li>-->
			<li class="gradient_blue"><a id="export_as_csv" href="#" class="export_link" data-filename="<?php echo str_replace(" ", "_", $user_profile['user_fullname'])."_".date("m_d_Y"); ?>" data-isportal="<?php echo $client = ( isset($_GET['client'] ) ) ? 1 : 0; ?>">Comma Separated (.csv)</a></li>
			<!--<li class="gradient_blue"><a id="export_as_txt" href="#" class="export_link">Tab Separated (.txt)</a></li>-->
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