<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
date_default_timezone_set('America/Los_Angeles');
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-client-session-ask.php');
require('includes/users-functions.php');
?>
<!DOCTYPE html>
<html>
<head>
<title>IT Audit Machine Catalog</title>
<link rel="stylesheet" type="text/css" href="css/main.css" media="screen" />
<!--[if IE 7]>
			<link rel="stylesheet" type="text/css" href="css/ie7.css" media="screen" />
		<![endif]-->

<!--[if IE 8]>
			<link rel="stylesheet" type="text/css" href="css/ie8.css" media="screen" />
		<![endif]-->

<!--[if IE 9]>
			<link rel="stylesheet" type="text/css" href="css/ie9.css" media="screen" />
		<![endif]-->
<link href="css/theme.css" rel="stylesheet" type="text/css" />
<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link type="text/css" href="js/jquery-ui/jquery-ui.theme.min.css" rel="stylesheet" />
<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
</head>
<body>
<div id="bg">
  <div id="container">
    <div id="header">

    <?php
    if (!empty($la_settings['admin_image_url'])) {
            $itauditmachine_logo_main = $la_settings['admin_image_url'];
    } else {
            $itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
    }
    ?>

      <div id="logo"> <img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" /> </div>
      <div class="clear"></div>
    </div>
    <div id="main">
      <div id="navigation">
        <ul id="nav">
          <li class="page_item nav_logout"><span id="unregisted_holder"><a id="nav_logout" href="client_logout.php" title="Sign Out"><span class="icon-exit"></span>Sign Out</a></span></li>
          <li class="page_item nav_manage_forms"><a href="client_account.php"><span class="icon-file"></span>My Forms</a></li>
          <li class="page_item nav_manage_forms"><a href="template_document.php"><span class="icon-file"></span>My Documents</a></li>
          <li class="page_item nav_manage_forms current_page_item"><a href="form_score.php"><span class="icon-file"></span>My Reports</a></li>
          <li class="page_item nav_users "><a id="nav_users" href="business_info/" title="Business Information"><span class="icon-user"></span>Business Information</a></li>
          <li class="page_item nav_my_account "><a id="nav_my_account" href="manage_account/" title="My Account"><span class="icon-key"></span>My Account</a></li>
        </ul>
        <div class="clear"></div>
      </div>
      <div id="content" class="full">
        <div class="post manage_forms">
          <div class="content_header">
            <div class="content_header_title">
              <div style="float: left">
                <h2>My Form Score</h2>
                <p></p>
              </div>
              <div style="clear: both; height: 1px"></div>
            </div>
          </div>
          <div class="content_body">
            <table cellpadding="4" cellspacing="1" border="0" style="width:60%">
                <?php
				$dbh = la_connect_db();
				$ikLoop = 0;
				$company_id = (int)$_SESSION['la_client_client_id'];
				$query_select = "SELECT * FROM `ap_score_reporting`";
				$param_select = array();
				$result_select = la_do_query($query_select,$param_select,$dbh);
				while($row_select = la_do_fetch_result($result_select)){
					if($row_select['company_id'] == 0){
						if($ikLoop > 0){
				?>
                <tr>
                  <th colspan="3" align="left">&nbsp;</th>
                </tr>
				<?php
                        }
                ?>
                <tr>
                  <th colspan="3" align="left">Report for
				  <div style="margin: 10px 0 0 20px; font-weight: normal;">
                  	<?php
					$query_form = "SELECT `form_id`, `form_name` FROM `ap_forms` WHERE `form_id` IN ({$row_select['form_id']})";
					$param_form = array();
					$result_form = la_do_query($query_form,$param_form,$dbh);
					while($row_form = la_do_fetch_result($result_form)){
					?>
                    <div><?php echo $row_form['form_name']; ?>&nbsp;(id: <?php echo $row_form['form_id']; ?>)</div>
                    <?php
					}
					?>
                  </div>
				  </th>
                </tr>
                <tr>
                  <th colspan="3" align="left">&nbsp;</th>
                </tr>
                <tr>
                  <th width="100px" align="left">Form No</th>
                  <th width="100px" align="left">Score</th>
                  <th width="100px" align="left">Date</th>
                </tr>
				<?php
                            $form_id_str = $row_select['form_id'];
                            $query = "SELECT * FROM `ap_form_score` WHERE `form_id` IN ($form_id_str) ORDER BY `form_id`, `score_date` DESC";
                            $param = array();
                            $result = la_do_query($query,$param,$dbh);
                            while($row_com = la_do_fetch_result($result)){
                ?>
                <tr>
                  <td><?php echo $row_com['form_id']; ?></td>
                  <td><?php echo $row_com['score']; ?></td>
                  <td><?php echo date("m/d/Y", $row_com['score_date']); ?></td>
                </tr>
                <?php
							}
					}else{
						if($row_select['company_id'] == $company_id){
							if($ikLoop > 0){
				?>
                <tr>
                  <th colspan="3" align="left">&nbsp;</th>
                </tr>
                <?php
							}
				?>
                <tr>
                  <th colspan="3" align="left">Report for
				  <div style="margin: 10px 0 0 20px; font-weight: normal;">
                  	<?php
					$query_form = "SELECT `form_id`, `form_name` FROM `ap_forms` WHERE `form_id` IN ({$row_select['form_id']})";
					$param_form = array();
					$result_form = la_do_query($query_form,$param_form,$dbh);
					while($row_form = la_do_fetch_result($result_form)){
					?>
                    <div><?php echo $row_form['form_name']; ?>&nbsp;(id: <?php echo $row_form['form_id']; ?>)</div>
                    <?php
					}
					?>
                  </div>
				  </th>
                </tr>
                <tr>
                  <th colspan="3" align="left">&nbsp;</th>
                </tr>
                <tr>
                  <th width="100px" align="left">Form No</th>
                  <th width="100px" align="left">Score</th>
                  <th width="100px" align="left">Date</th>
                </tr>
				<?php
                            $form_id_str = $row_select['form_id'];
                            $query = "SELECT * FROM `ap_form_score` WHERE `form_id` IN ($form_id_str) ORDER BY `form_id`, `score_date` DESC";
                            $param = array();
                            $result = la_do_query($query,$param,$dbh);
							$number_of_rows = $result->fetchColumn();
							if($number_of_rows > 0){
                            	while($row_com = la_do_fetch_result($result)){
                ?>
                <tr>
                  <td><?php echo $row_com['form_id']; ?></td>
                  <td><?php echo $row_com['score']; ?></td>
                  <td><?php echo date("m/d/Y", $row_com['score_date']); ?></td>
                </tr>
                <?php
								}
							}else{
				?>
                <tr>
                  <td colspan="3">No records found!</td>
                </tr>
                <?php
							}
						}
					}
					$ikLoop++;
                }
                ?>
            </table>
          </div>
        </div>
      </div>
    </div>
    <img src="images/bottom.png" id="bottom_shadow">
    <div id="footer">
      <p class="copyright">Patent Pending, Copyright &copy; 2000-<script>document.write(new Date().getFullYear());</script> - <a href="http://www.continuumgrc.com">Continuum GRC.</a> All rights reserved.</p>
	<div class="clear"></div>
    </div>
    <!-- /#footer -->
  </div>
</div>
</body>
</html>
