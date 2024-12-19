<?php
   	$max_session_timeout = ini_get('session.gc_maxlifetime');
    if (!empty($la_settings['admin_image_url'])) {
            $itauditmachine_logo_main = $la_settings['admin_image_url'];
    } else {
            $itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
    }
    //get user information from DB
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`= ?";
	$sth = la_do_query($query, array($_SESSION["la_client_user_id"]), $dbh);
	$res = la_do_fetch_result($sth);
	if(isset($res)) {
		$my_full_name_for_header = $res["full_name"];
    	$my_avatar_for_header = "../auditprotocol/".$res["avatar_url"];

		if(!file_exists($my_avatar_for_header)) {
			$my_avatar_for_header = "../auditprotocol/avatars/default.png";
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>IT Audit Machine Client Account</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, nofollow" />
		<meta name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
		<link rel="stylesheet" type="text/css" href="../itam-shared/Plugins/Font Awesome/css/all.css" media="screen" />
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
		<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
		<link type="text/css" href="css/pagination_classic.css" rel="stylesheet" />
		<?php if(!empty($header_data)){ echo $header_data; } ?>
		<link type="text/css" href="css/override.css" rel="stylesheet" />
		<style>
			#header .dropdown-user {
				float: right;
				margin-top: 27px;
				margin-right: 10px;
				position: relative;
			}
			#header .dropdown-toggle {
				display: inline-block;
				text-decoration: none;
				line-height: 15px;
				color: #0085CC;
				z-index: 999;
			}
			#header .dropdown-toggle:hover {
				color: #50a9d8;
			}
			#header .avatar-header {
				width: 40px;
				height: 40px;
				margin-top: -12px;
				margin-right: 8px;
				float: left;
				-webkit-border-radius: 50%!important;
				-moz-border-radius: 50%!important;
				border-radius: 50%!important;
			}
			#header .fullname-header {
				font-size: 17px;
			}
			#header .dropdown-menu {
				background: #2e343b;
				border: 0;
				width: 190px;
				margin-top: 5px;
				position: absolute;
				list-style: none;
				font-size: 15px;
				text-align: left;
				z-index: 9999;
				right: 5px;
				left: auto;
				display: none;
			}
			#header .dropdown-menu:after {
				position: absolute;
				top: -7px;
				right: 10px;
				display: inline-block!important;
				border-right: 7px solid transparent;
				border-bottom: 7px solid #2e343b;
				border-left: 7px solid transparent;
				content: '';
			}
			#header .dropdown-menu a {
				text-decoration: none;
				color: #aaafb7;
				padding: 8px 16px;
				display: block;
				line-height: 18px;
				white-space: nowrap;
			}
			#header .dropdown-menu a:hover {
				background: #373e47;
			}
			#header .dropdown-menu a i {
				margin-right: 20px;
				color: #6FA7D7;
				width: 17px;
			}
			#header .dropdown-menu .divider {
				background: #3b434c;
				height: 1px;
			}
		 	.ui-widget-overlay {
		 		background: #607d8b none repeat scroll 0 0 !important;
		 		height: 100%;
		 		left: 0;
		 		opacity: 0.7 !important;
		 		top: 0;
		 		width: 100%;
		 	}
		 	.ui-dialog .ui-dialog-buttonpane {
		 		margin: 0 0 0 0 !important;
		 	}
		 	.ui-widget-content {
		 		background: #ffffff none repeat scroll 0 0;
		 	}
		 	#dialog-welcome-message {
		 		background-color: #fff !important;
		 	}
		 	.chart-data-row {
		 		color: #000 !important;
		 	}
		</style>
	</head>
   	<body>
      	<div id="bg">
      		<div id="container">
				<div id="header">
				 	<div id="logo">
				 		<img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="Lazarus Alliance" />
				 	</div>
				 	<div class="dropdown-user dropdown-dark">
				    	<a href="javascript:;" class="dropdown-toggle" aria-expanded="false">
				    		<img class="avatar-header" src="<?php echo $my_avatar_for_header; ?>">
				    		<span class="fullname-header"><?php echo $my_full_name_for_header; ?></span>
				    	</a>
				    	<ul class="dropdown-menu">
                            <li>
                                <a href="my_account.php">
                                	<i class="fas fa-user"></i> My Profile </a>
                            </li>
                            <li>
                                <a href="client_account.php">
                                	<i class="fas fa-tasks"></i> My Forms </a>
                            </li>
                            <li>
                                <a href="template_document.php">
                                	<i class="far fa-file-alt"></i> My Documents </a>
                            </li>
                            <li>
                                <a href="my_report.php">
                                	<i class="fas fa-chart-pie"></i> My Reports </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="client_logout.php">
                                	<i class="fas fa-sign-out-alt"></i> Sign Out </a>
                            </li>
                        </ul>
				 	</div>
				 	<div class="clear"></div>
				</div>
      			<div id="main">
					<div id="navigation">
					 	<ul id="nav">
					    	<li class="page_item nav_help"><a title="Help" target="_blank" href="<?php echo $la_settings['user_help_url'];?>" id="nav_help"><img src="images/navigation/FFFFFF/50x50/Help.png"></a></li>
					    	<li class="page_item nav_logout"><span id="unregisted_holder"><a id="nav_logout" href="client_logout.php" title="Sign Out"><img src="images/navigation/FFFFFF/50x50/Sign_out.png"></a></span></li>
					    	<li class="page_item nav_manage_forms<?php echo basename($_SERVER['PHP_SELF']) == "client_account.php" || basename($_SERVER['PHP_SELF']) == "manage_entries.php" || basename($_SERVER['PHP_SELF']) == "view_entry.php" || basename($_SERVER['PHP_SELF']) == "imported_report_list.php" || basename($_SERVER['PHP_SELF']) == "saint_report_details.php" || basename($_SERVER['PHP_SELF']) == "nessus_report_details.php" ? " current_page_item" : ""; ?>"><a href="client_account.php" title="My Forms"><img src="images/navigation/FFFFFF/50x50/Create_new_form.png"></a></li>
					    	<li class="page_item nav_manage_forms<?php echo basename($_SERVER['PHP_SELF']) == "template_document.php" ? " current_page_item" : ""; ?>"><a href="template_document.php" title="My Documents"><img src="images/navigation/FFFFFF/50x50/Create_new_report.png"></a></li>
					    	<li class="page_item nav_manage_forms<?php echo basename($_SERVER['PHP_SELF']) == "my_report.php" || basename($_SERVER['PHP_SELF']) == "view_report.php" ? " current_page_item" : ""; ?>"><a href="my_report.php" title="My Reports"><img src="images/navigation/FFFFFF/50x50/Manage_reports.png"></a></li>
					    	<li class="page_item nav_users<?php echo basename($_SERVER['PHP_SELF']) == "my_account.php" ? " current_page_item" : ""; ?>"><a id="nav_users" href="my_account.php" title="My Profile"><img src="images/navigation/FFFFFF/50x50/My_account.png"></a></li>
					 	</ul>
					 	<div class="clear"></div>
					</div>
      				<div id="content" class="full">
      					<div class="post manage_forms">
							<div class="content_header">
							 	<div class="content_header_title">
							    	<div>
								       <?php
								          if(basename($_SERVER['PHP_SELF']) == "client_account.php"){
								          ?>
							       		<h2>My Forms</h2>
							       		<p>Please select any of your subscriptions below.</p>
							       		<?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "manage_entries.php"){
								          ?>
								        <div style="float: left;">
							          		<h2><a class="breadcrumb" href="manage_entries.php?id=<?php echo $_GET['id']; ?>"><?php echo $form_name_for_header?></a><img src="images/icons/resultset_next.gif"><a class="breadcrumb" href="manage_entries.php?id=<?php echo $_GET['id']; ?>">Entries</a></h2>
							          		<p>Edit and manage your form entry</p>
							       		</div>
							       		<?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "view_entry.php"){
								          ?>
								        <div style="float: left;">
								        	<h2><a class="breadcrumb" href="manage_entries.php?id=<?php echo $_GET['form_id']; ?>"><?php echo $form_name_for_header?></a><img src="images/icons/resultset_next.gif"><a class="breadcrumb" href="manage_entries.php?id=<?php echo $_GET['form_id']; ?>">Entries</a><img src="images/icons/resultset_next.gif">#<?php echo $_GET['entry_id']; ?></h2>
							          		
							          		<p>Edit and manage your form entry</p>
							       		</div>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "imported_report_list.php"){
								          	$form_id = $_GET["form_id"];
								          ?>
							       		<div style="float: left;">
							          		<h2>My Reports From Other Services</h2>
							          		<p>You can easily make an entry by using one of the imported reports below.</p>
							       		</div>
							       		<div style="float: right;">
							          		<a href="<?php echo 'view.php?id='.$form_id.'&entry_id='.time(); ?>" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Forward.png">  Skip </a>
							       		</div>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "saint_report_details.php"){
								          	$form_id = $_GET["form_id"];
								          ?>
							       		<div style="float: left;">
							          		<h2>SAINT Scan Report Data</h2>
							          		<p>You can import this report data into the form by selecting a row on the tables or export as CSV, Excel or PDF files.</p>
							       		</div>
							       		<div style="float: right;">
							          		<a href="<?php echo 'view.php?id='.$form_id.'&entry_id='.time(); ?>" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Forward.png">  Skip </a>
							       		</div>
							       	   <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "nessus_report_details.php"){
								          	$form_id = $_GET["form_id"];
								          ?>
							       		<div style="float: left;">
							          		<h2>Nessus Scan Report Data</h2>
							          		<p>You can import this report data into the form by selecting a row on the tables or export as CSV, Excel or PDF files.</p>
							       		</div>
							       		<div style="float: right;">
							          		<a href="<?php echo 'view.php?id='.$form_id.'&entry_id='.time(); ?>" class="bb_button bb_small bb_green"><img src="images/navigation/FFFFFF/24x24/Forward.png">  Skip </a>
							       		</div>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "template_document.php"){
								          ?>
							       		<h2>My Documents</h2>
							       		<p>Listed are your custom reports and documents created once you've submitted an assessment module or questionnaire specifically associated with your subscriptions. Not all modules produce documents and if you do not see the documents you were expecting to see, <a href="https://continuumgrc.com/contact/"> please contact us today!</a></p>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "sso-entity-select.php"){
								          ?>
							       		<h2>Select Entity</h2>
							       		<p>Please select the entity you wish to use.</p>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "my_report.php"){
								          ?>
							       		<h2>My Reports</h2>
							       		<p>Depending on your subscription level, you may not see reports here. Only ITAM administrators have the ability to create dynamic reports generated from data within the system. If you would like to upgrade your subscription, <a href="https://continuumgrc.com/contact/"> please contact us today!</a><br>Additionally, you will also only see reports for modules that you are actively subscribed to in your catalog.</p>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "view_report.php"){
								          ?>
							       		<h2>Report Data</h2>
								       <?php
								          }elseif(basename($_SERVER['PHP_SELF']) == "my_account.php"){
								          ?>
							       		<h2>My Profile</h2>
							       		<p>Please update any information regarding your business or collaboration group as needed.</p>
								       <?php
								          }
								          ?>
							    	</div>
							    	<div style="clear: both; height: 1px"></div>
								</div>
							</div>