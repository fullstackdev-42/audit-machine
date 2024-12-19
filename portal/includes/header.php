<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>IT Audit Machine Panel</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="index, nofollow" />
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
<?php
	if(!empty($la_settings['admin_theme'])){
		echo '<link href="css/themes/theme_'.$la_settings['admin_theme'].'.css" rel="stylesheet" type="text/css" />';
	}
?>
<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<?php if(!empty($header_data)){ echo $header_data; } ?>
<link href="css/override.css" rel="stylesheet" type="text/css" />
</head>

<body>

<div id="bg">

<div id="container">

	<div id="header">
	<?php
		if(!empty($la_settings['admin_image_url'])){
			$itauditmachine_logo_main = htmlentities($la_settings['admin_image_url']);
		}else{
			$itauditmachine_logo_main = '/images/Logo/Logo-2019080202-GRCx300.png';
		}
	?>
		<div id="logo">
			<img class="title" src="<?php echo $itauditmachine_logo_main; ?>" style="margin-left: 8px" alt="IT Audit Machine" />
		</div>	

		
		<div class="clear"></div>
		
	</div><!-- /#header -->
	<div id="main">
	
		<div id="navigation">
		
			<ul id="nav">
           		<li class="page_item nav_manage_forms <?php if($current_nav_tab == 'manage_forms'){ echo 'current_page_item'; } ?>"><a href="manage_forms.php"><!--<span class="icon-file"></span>--><img src="images/navigation/office_bag_16.png"></span>Manage Forms</a></li>
				
				<?php if(!empty($_SESSION['la_user_privileges']['priv_new_themes'])){ ?>
				<li class="page_item nav_change_themes <?php if($current_nav_tab == 'edit_theme'){ echo 'current_page_item'; } ?>"><a id="nav_change_themes" href="edit_theme.php" title="Edit Themes"><span class="icon-palette"></span>Edit Themes</a></li>
				<?php } ?>

				<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])){ ?>
				<li class="page_item nav_users <?php if($current_nav_tab == 'users'){ echo 'current_page_item'; } ?>"><a id="nav_users" href="manage_users.php" title="Users"><span class="icon-user"></span>Users</a></li>
				<li class="page_item nav_settings <?php if($current_nav_tab == 'main_settings'){ echo 'current_page_item'; } ?>"><a id="nav_settings" href="main_settings.php" title="Settings"><span class="icon-wrench"></span>Settings</a></li>
				<?php } ?>
				
				<?php if(!empty($_SESSION['la_user_privileges']['priv_administer'])){ ?>
				<li class="page_item nav_help"><a id="nav_help" href="<?php echo $la_settings['user_help_url'];?>" target="_blank" title="Help"><span class="icon-question"></span>Help</a></li>
				<?php } ?>

				<li class="page_item nav_logout"><span id="unregisted_holder"><?php if($la_settings['customer_name'] == 'unregistered'){ echo "UNREGISTERED LICENSE";} ?></span><a id="nav_logout" href="logout.php" title="Sign Out"><span class="icon-exit"></span>Sign Out</a></li>
				<li class="page_item nav_help"><a title="Help" target="_blank" href="<?php echo $la_settings['user_help_url'];?>" id="nav_help"><!--<span class="icon-question"></span>--><img src="images/navigation/help_16.png">  Help</a></li>
            </ul>
			
			<div class="clear"></div>
			
		
		</div><!-- /#navigation -->
