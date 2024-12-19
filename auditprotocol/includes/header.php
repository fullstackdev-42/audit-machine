<?php
	if(!empty($la_settings['admin_image_url'])){
		$itauditmachine_logo_main = htmlentities($la_settings['admin_image_url']);
	}else{
		$itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
	}
	//get user information from DB
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."users WHERE `user_id`= ?";
	$sth = la_do_query($query, array($_SESSION["la_user_id"]), $dbh);
	$res = la_do_fetch_result($sth);
	if(isset($res)) {
		$my_full_name_for_header = $res["user_fullname"];
    	$my_avatar_for_header = $res["avatar_url"];

		if(!file_exists($my_avatar_for_header)) {
			$my_avatar_for_header = "avatars/default.png";
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>IT Audit Machine Panel</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, nofollow" />
		<meta id="csrf-token-meta" name="csrf-token" content="<?php echo noHTML($_SESSION['csrf_token']); ?>">
		<meta http-equiv="Content-Security-Policy" content="default-src * 'self' blob: data: gap:; style-src * 'self' 'unsafe-inline' blob: data: gap:; script-src * 'self' 'unsafe-eval' 'unsafe-inline' blob: data: gap:; object-src * 'self' blob: data: gap:; img-src * 'self' 'unsafe-inline' blob: data: gap:; connect-src 'self' * 'unsafe-inline' blob: data: gap:; frame-src * 'self' blob: data: gap:;">
		
		<link rel="stylesheet" type="text/css" href="../../itam-shared/Plugins/Font Awesome/css/all.css" media="screen" />
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
		<!-- added default theme css for admin -->
		<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
		<?php if(!empty($header_data)){ echo $header_data; } ?>
		<link href="css/override.css" rel="stylesheet" type="text/css" />
		<style type="text/css">
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
		</style>
		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript" src="js/jquery-migrate.min.js"></script>
		<script type="text/javascript">
			function setAjaxDefaultParam(){
				$.ajaxSetup({
					headers: {
						"X-CSRFToken": $('meta#csrf-token-meta').attr('content'),
					},
					data: {
						post_csrf_token: $('meta#csrf-token-meta').attr('content'),
					},
					cache: false,
					complete: function (event, request, settings) {
						// location.reload();
					}
				});
			}

			$(document).ready(function(e) {
				//setAjaxDefaultParam();disable X-SCRToken for ajax call 
				//TODO Come back if we need this or not.
			});
			</script>
			<script type="text/javascript" src="js/jquery.smartmarquee.js"></script>
			<script type="text/javascript" src="js/app.js"></script>
			<?php
				if(!isset($load_custom_js)){
			?>
			<script type="text/javascript" src="custom-view-js-func.js"></script>
		<?php
			}
		?>
	</head>
	<body>
		<div id="bg">
			<div id="container">
				<div id="header">					
					<div id="logo">
						<img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="IT Audit Machine" />
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
								<a href="manage_forms.php">
									<i class="fas fa-tasks"></i> Manage Forms </a>
							</li>
							<li>
								<a href="manage_reports.php">
								<i class="fas fa-chart-pie"></i> Manage Reports </a>
							</li>
							<?php
								if(!empty($_SESSION['la_user_privileges']['priv_new_themes'])) {
							?>
								<li>
									<a href="edit_theme.php">
									<i class="fas fa-paint-roller"></i> Edit Theme </a>
								</li>
							<?php
								}
							?>
							<?php
								if(!empty($_SESSION['la_user_privileges']['priv_administer'])) {
							?>
								<li>
									<a href="manage_users.php">
										<i class="fas fa-user-cog"></i> Manage Users </a>
								</li>
								<li>
									<a href="manage_templates.php">
										<i class="fas fa-file-alt"></i> Manage Templates </a>
								</li>
								<li>
									<a href="main_settings.php">
										<i class="fas fa-cog"></i> Manage Settings </a>
								</li>
							<?php
								}
							?>
							<li class="divider"></li>
							<li>
								<a href="logout.php">
									<i class="fas fa-sign-out-alt"></i> Sign Out </a>
							</li>
					</ul>
					</div>
					<div class="clear"></div>
				</div>
				<div id="main">
					<div id="navigation">
	 					<ul id="nav">
							<?php 
							$tabs = array("Manage Forms", "Manage Reports");
							if(!empty($_SESSION['la_user_privileges']['priv_new_themes'])) {
								$tabs[] = "Edit Theme";
							}
							if(!empty($_SESSION['la_user_privileges']['priv_administer'])){
								$tabs[] = "Manage Users";
								$tabs[] = "Manage Templates";
							}
							foreach ($tabs as $tab) {
								$str = strtolower($tab);
								$id = str_replace(' ', '_', $str);
								$fname = str_replace(' ', '_', ucfirst($str));
								$title = ucwords($str);
								$active = ($current_nav_tab == $id) ? 'current_page_item' : '';
								echo <<<EOT
<li class="page_item nav_{$id} $active"><a href="{$id}.php" title="{$title}"><!--<span class="icon-file"></span>--><img src="images/navigation/FFFFFF/50x50/{$fname}.png"> </a></li>
EOT;
							}
							?>
							<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])){ ?>
								<li class="page_item nav_settings <?php if($current_nav_tab == 'main_settings'){ echo 'current_page_item'; } ?>">
									<a id="nav_settings" href="main_settings.php" title="Settings">
										<img src="images/navigation/FFFFFF/50x50/Settings.png">
									</a>
								</li>
								<li class="page_item nav_help"><a id="nav_help" href="<?php echo $la_settings['admin_help_url'];?>" target="_blank" title="Help"><img src="images/navigation/FFFFFF/50x50/Help.png"></a></li>
							<?php } ?>
							<li class="page_item nav_logout">
								<span id="unregisted_holder">
									<?php if($la_settings['customer_name'] == 'unregistered'){ echo "UNREGISTERED LICENSE";} ?>
								</span>
								<a id="nav_logout" href="logout.php" title="Sign Out"><img src="images/navigation/FFFFFF/50x50/Sign_out.png"></a>
							</li>
						</ul>
						<div class="clear"></div>
					</div>
					<!-- /#navigation -->