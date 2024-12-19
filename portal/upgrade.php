<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');

	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('lib/password-hash.php');
	require('lib/dbbackup.class.php');
	require('includes/helper-functions.php');
	require('includes/theme-functions.php');
	require('includes/setup-functions.php');

	define('NEW_ITAUDITMACHINE_VERSION', '4.2');
	define('SKIP_DB_BACKUP',false);	 //change this to true if you need to skip the database backup during upgrade

	//1. Check PHP version
	if(version_compare(PHP_VERSION,"5.2.0",">=")){
		$is_php_version_passed = true;
	}else{
		$is_php_version_passed = false;
		$pre_install_error = 'php_version_insufficient';
	}

	if($is_php_version_passed){
		$quick_upgrade = false;

		//2. Check connection to Database
		try {
			  $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD,
			  				 array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)
			  				 );
			  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			  $dbh->query("SET NAMES utf8");
		} catch(PDOException $e) {
			  $error_connecting =  "Error connecting to the database: ".$e->getMessage();
			  $pre_install_error = $error_connecting;
		}

		//3. Check MySQL version
		$params = array();
		if(empty($error_connecting)){
			
			try{
				$query = "select version() mysql_version_number";
				$sth = $dbh->prepare($query);

				$sth->execute($params);
			}catch(PDOException $e) {
				echo "Check version failed: ".$e->getMessage();
			}
			
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$current_mysql_version = $row['mysql_version_number'];

			if(version_compare($current_mysql_version,"4.1.0","<")){
				$error_mysql_version = "Your current MySQL version ({$current_mysql_version}) is less than the minimum requirement (4.1.0)";
				$pre_install_error = $error_mysql_version;
			}

			//4. Check for existing IT Audit Machine installation
			if(empty($error_mysql_version)){

				//check if any version of itauditmachine exist or not
				$is_itauditmachine_db_exist = true;

				try{
					$query = "select count(*) from ".LA_TABLE_PREFIX."forms";
					$sth = $dbh->prepare($query);

					$sth->execute($params);
				}catch(PDOException $e) {
					$is_itauditmachine_db_exist = false;
				}

				if(!$is_itauditmachine_db_exist){ //if there is no previous itauditmachine installation
					$pre_install_error = 'itauditmachine_db_not_exist';
				}else{
					//itauditmachine was installed
					//we need to make sure the current version
					$current_itauditmachine_version = '1';
					
					try{
						$query = "select count(*) from ".LA_TABLE_PREFIX."column_preferences";
						$sth = $dbh->prepare($query);
						
						$sth->execute($params);
						$current_itauditmachine_version = '2';
					}catch(PDOException $e) {
						//do nothing, continue
					}

					try{
						$query = "select count(*) from ".LA_TABLE_PREFIX."fonts";
						$sth = $dbh->prepare($query);

						$sth->execute($params);
						$current_itauditmachine_version = '3';
					}catch(PDOException $e) {
						//do nothing, continue
					}

					if($current_itauditmachine_version === '1'){
						$pre_install_error = 'version1_incompatible';
					}else if($current_itauditmachine_version === '3'){
						//check further version number
						$la_settings = la_get_settings($dbh);

						if(NEW_ITAUDITMACHINE_VERSION === $la_settings['itauditmachine_version']){
							$pre_install_error = 'itauditmachine_already_installed';
						}else{
							$quick_upgrade = true;
							$current_itauditmachine_version = $la_settings['itauditmachine_version'];
							//check the data folder
							if(!is_writable($la_settings['data_dir'])){
								$pre_install_error = 'data_dir_unwritable';
							}
						}
					}else if($current_itauditmachine_version === '2'){
						//this is itauditmachine 2
						//we can continue with the upgrade
						//check the data folder
						if(!is_writable('./data')){
							$pre_install_error = 'data_dir_unwritable';
						}
					}

				}

			}
		}
	}

	if(empty($pre_install_error) && !empty($_POST['run_upgrade'])){

		if($quick_upgrade === false){
			$admin_username = trim($_POST['admin_username']);
			$license_key = trim($_POST['license_key']);
			//do the installation here
			//check the email address first
			
			$email_regex  = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]*\.[A-z0-9]{2,6}$/';
			$regex_result = preg_match($email_regex, $admin_username);
				
			if(empty($regex_result) || empty($admin_username)){
				$is_invalid_admin_email = true;
			}else{
				$is_invalid_admin_email = false;
			}

			//check license key
			if(empty($license_key)){
				$is_invalid_license_key = true;
			}else{
				$v_final_key = str_replace('-', '', $license_key);
				$v_key_hash  = substr($v_final_key, -4);
				$v_license_seed_hash = md5(substr($v_final_key,0,16));
				$v_license_seed_hash_4 = strtoupper(substr($v_license_seed_hash, -4));

				if($v_license_seed_hash_4 == $v_key_hash){
					$is_invalid_license_key = false;
				}else{
					$is_invalid_license_key = true;
				}
			}
		}else{
			$is_invalid_license_key = false;
			$is_invalid_admin_email = false;
		}

		if(!$is_invalid_admin_email && !$is_invalid_license_key){
			//do upgrade tasks here
			$post_install_error = '';

			if($current_itauditmachine_version === '2'){
				//if we're upgrading from v2.x
				$options = array();
				$options['license_key'] 		 = $license_key;
				$options['new_itauditmachine_version'] = NEW_ITAUDITMACHINE_VERSION;
				$options['admin_username']		 = $admin_username;
				$post_install_error .= do_delta_update_2_x_to_3_0($dbh,$options);

				$post_install_error .= do_delta_update_3_0_to_3_2($dbh);

				$post_install_error .= do_delta_update_3_2_to_3_3($dbh,$options);

				$post_install_error .= do_delta_update_3_3_to_3_4($dbh);

				$post_install_error .= do_delta_update_3_4_to_3_5($dbh);

				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version === '3.0' || $current_itauditmachine_version === '3.1'){ 
				//if we're upgrading from v3.0 or v3.1

				$post_install_error .= do_delta_update_3_0_to_3_2($dbh);

				$post_install_error .= do_delta_update_3_2_to_3_3($dbh);

				$post_install_error .= do_delta_update_3_3_to_3_4($dbh);

				$post_install_error .= do_delta_update_3_4_to_3_5($dbh);

				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version === '3.2'){
				//if we're upgrading from v3.2
				$post_install_error .= do_delta_update_3_2_to_3_3($dbh);

				$post_install_error .= do_delta_update_3_3_to_3_4($dbh);

				$post_install_error .= do_delta_update_3_4_to_3_5($dbh);

				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version === '3.3'){
				//if we're upgrading from v3.3
				$post_install_error .= do_delta_update_3_3_to_3_4($dbh);

				$post_install_error .= do_delta_update_3_4_to_3_5($dbh);

				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version === '3.4'){
				//if we're upgrading from v3.4				
				$post_install_error .= do_delta_update_3_4_to_3_5($dbh);

				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version === '3.5'){
				//if we're upgrading from v3.5				
				$post_install_error .= do_delta_update_3_5_to_4_0_beta($dbh);

				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version == '4.0.beta'){
				//if we're upgrading from v4.0.beta				
				$post_install_error .= do_delta_update_4_0_beta_to_4_0($dbh);

				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version == '4.0'){
				//if we're upgrading from v4.0				
				$post_install_error .= do_delta_update_4_0_to_4_1($dbh);

				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}else if($current_itauditmachine_version == '4.1'){
				//if we're upgrading from v4.1				
				$post_install_error .= do_delta_update_4_1_to_4_2($dbh);
			}	

			//repopulate ap_fonts content with updated data
			$post_install_error .= populate_ap_fonts_table($dbh);

			if(empty($post_install_error)){
				$installation_success = true;

				//update ap_settings table with the latest version
				$settings['itauditmachine_version'] = NEW_ITAUDITMACHINE_VERSION;
				la_ap_settings_update($settings,$dbh);

				//clear the session, so that the user need to re-login
				$_SESSION = array();
			}else{
				$installation_has_error = true;
			}

		} //end !is_invalid_admin_email & !is_invalid_license_key
	}//end run_upgrade

	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>IT Audit Machine Upgrade Script</title>
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
<link href="css/bb_buttons.css" rel="stylesheet" type="text/css" />
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/edit_form.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<link href="css/override.css" rel="stylesheet" type="text/css" />
<style>
	#submit_button{
		border-radius: 4px !important;
	}
</style>
</head>

<body>

<div id="bg" class="installer_page">

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
	
 
		<div id="content">
			<div class="post installer">

				<div style="padding-top: 10px">
					
					<?php if(empty($pre_install_error)){ ?>

					<?php 	if($installation_success && ($current_itauditmachine_version === '2')){ ?>
								<div>
									<img src="images/navigation/005499/50x50/Success.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Success!</h3>
									<p>You have completed the upgrade.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="index.php">
										<ul>
											<li>
												<p>Below is your IT Audit Machine login information:</p>
												<p style="margin-top: 10px; margin-bottom: 20px">Email: <b><?php echo htmlspecialchars($admin_username); ?></b><br/>
												   Password: <b>itauditmachine</b></p>
												<p>Please change your password after logging in!</a>
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">Login to IT Audit Machine</button>
											</li>
										</ul>
									</form>
								</div>
					<?php 	}else if($installation_success && ($current_itauditmachine_version !== '2')){ ?>
								<div>
									<img src="images/navigation/005499/50x50/Success.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Success!</h3>
									<p>You have completed the upgrade.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="index.php">
										<ul>
											<li>
												<h5 style="font-size: 16px;font-weight: 400;color: #529214">IT Audit Machine has been upgraded to version <strong><?php echo NEW_ITAUDITMACHINE_VERSION; ?></strong></h5>
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">Login to IT Audit Machine</button>
											</li>
										</ul>
									</form>
								</div>	
					<?php	}else if($installation_has_error){ //if server meet the requirements but error during install (error while creating tables) ?>
								<div>
									<img src="images/navigation/ED1C2A/50x50/Warning.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Error During Upgrade!</h3>
									<p>Please fix the error below and try again.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="">
										<ul>
											<li id="li_installer_notification">
												<h5><?php echo $post_install_error; ?></h5>	
											</li>
											<li>
												Make sure that the database user is having enough privileges to create and alter tables.
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span> 
											        Try Again
											    </button>
											</li>
										</ul>
									</form>
								</div>
					
					<?php   }else if($quick_upgrade === true){ ?>
								<div>
									<img src="images/icons/advancedsettings.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>IT Audit Machine Ready to Upgrade</h3>
									<p>Click the upgrade button to continue.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
										<ul>
											<li style="margin-bottom: 5px;margin-top: 20px;">
												<h5 style="font-size: 16px;font-weight: 400;color: #529214">Be sure to backup your itauditmachine before upgrading</h5>
											</li>		
								    		<li id="li_submit" class="buttons" style="overflow: auto">
								    			<input type="hidden" name="run_upgrade" id="run_upgrade" value="1">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span>
											        Upgrade to IT Audit Machine <?php echo NEW_ITAUDITMACHINE_VERSION; ?>
											    </button>
											</li>
										</ul>
									</form>
								</div>	

					<?php   }else{ ?>
								<div>
									<img src="images/icons/advancedsettings.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>IT Audit Machine Ready to Upgrade</h3>
									<p>Please fill the form below and click upgrade.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
										<ul>
											<?php if($is_invalid_admin_email){ ?>
												<li id="li_installer_notification">
													<h5>Error! Please enter valid email address!</h5>
												</li>
											<?php }else if($is_invalid_license_key){ ?>
												<li id="li_installer_notification">
													<h5>Error! Please enter valid License Number!</h5>
												</li>
											<?php } ?>

											<li id="li_email_address" style="margin-top: 25px;">		
												<label class="desc" for="admin_username">Your Email Address</label>
												<div>
													<input id="admin_username" name="admin_username" class="element text large" type="text" maxlength="255" value="<?php echo htmlspecialchars($admin_username); ?>"/>
													<span style="font-size: 85%;color: #444444">You will use this to login to the admin panel.</span> 
												</div>
											</li>
											<li id="li_license_key">		
												<label class="desc" for="license_key">License Number</label>
												<div>
													<input id="license_key" name="license_key" class="element text large" type="text" maxlength="255" value="<?php echo htmlspecialchars($license_key); ?>"/> 
												</div>
											</li>
											<li style="margin-bottom: 5px">
												<h5 style="font-size: 16px;font-weight: 400;color: #529214">Be sure to backup your database before upgrading</h5>
											</li>		
								    		<li id="li_submit" class="buttons" style="overflow: auto">
								    			<input type="hidden" name="run_upgrade" id="run_upgrade" value="1">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span>
											        Upgrade to IT Audit Machine <?php echo NEW_ITAUDITMACHINE_VERSION; ?>
											    </button>
											</li>
										</ul>
									</form>
								</div>	

					<?php   }
								
						
						 }else{ //else if there are pre install error 
							if($pre_install_error == 'php_version_insufficient' || $pre_install_error == 'data_dir_unwritable'){
								if($pre_install_error == 'php_version_insufficient'){
									$pre_install_error = "Your current PHP version (".PHP_VERSION.") is less than the minimum requirement (5.2.0)";
								}else{
									$pre_install_error = "The <strong><u>data</u></strong> folder under your itauditmachine folder is not writable. Please set the permission to writable (CHMOD 777)";
								}
					?>
								<div>
									<img src="images/navigation/ED1C2A/50x50/Warning.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Error! Unable to Install</h3>
									<p>Please fix the error below to continue.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="">
										<ul>
											<li id="li_installer_notification">
												<h5><?php echo $pre_install_error; ?></h5>	
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span>
											        Check Again
											    </button>
											</li>
										</ul>
									</form>
								</div>	
					<?php	}else if($pre_install_error == 'itauditmachine_already_installed'){ ?>
								<div>
									<img src="images/navigation/005499/50x50/Notice.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>IT Audit Machine Already Upgraded</h3>
									<p>Please login to the admin panel below.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="index.php">
										<ul>
											<li id="li_installer_notification">
												<h5>You're up to date already! (v<?php echo NEW_ITAUDITMACHINE_VERSION ?>).</h5><h5>You can login to the admin panel to create/edit your forms.</h5>	
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">Login to IT Audit Machine</button>
											</li>
										</ul>
									</form>
								</div>	

					<?php	}else if($pre_install_error == 'itauditmachine_db_not_exist'){ ?>
								<div>
									<img src="images/navigation/ED1C2A/50x50/Warning.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Error! No IT Audit Machine Found</h3>
									<p>Please fix the error below to continue.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="">
										<ul>
											<li id="li_installer_notification">
												<h5>In order to upgrade to version 3, <br />you need to have version 2.x installed.</h5><br /><h5>Make sure to double check your config.php <br />file for any error or typo.</h5><br/><h5>If this is the first time you use IT Audit Machine, <br/>then go to the <a href="installer.php" style="color: blue;text-decoration: underline">installer page</a> instead.</h5>	
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span>
											        Check Again
											    </button>
											</li>
										</ul>
									</form>
								</div>	
					<?php	}else if($pre_install_error == 'version1_incompatible'){ ?>
								<div>
									<img src="images/navigation/ED1C2A/50x50/Warning.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Error! Unable to Upgrade</h3>
									<p>Please fix the error below to continue.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="">
										<ul>
											<li id="li_installer_notification">
												<h5>In order to upgrade to version 4, <br />you need to have at least version 2.x installed.</h5><br /><h5>It seems you have version 1 installed.</h5><br/><h5>You need to upgrade to version 2 first.</h5>	
											</li>
										</ul>
									</form>
								</div>

					<?php	}else{  ?>
								<div>
									<img src="images/navigation/ED1C2A/50x50/Warning.png" align="absmiddle" style="width: 48px; height: 48px;float: left;padding-right: 10px;padding-top: 10px"/>
									<h3>Error Connecting to Database</h3>
									<p>Please fix the error below to continue.</p>
									<div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
								</div>
								
								<div style="margin-top: 10px;margin-bottom: 10px">
									<form id="form_installer" class="itauditm"  method="post" action="">
										<ul>
											<li id="li_installer_notification">
												<h5><?php echo $pre_install_error; ?></h5>
											</li>
											<li style="font-family: Arial, Helvetica, sans-serif">
												<p>There are few things you can try to fix this issue:
													<ul style="list-style-type:disc;">
														<li style="margin-left: 20px;padding-left: 0px;padding-bottom: 0px">Make sure you have the correct database username and password</li>
														<li style="margin-left: 20px;padding-left: 0px">Make sure you have the correct database hostname</li>
													</ul>
												</p>
												<p style="margin-top: 15px">If the problem persist, please <a style="text-decoration: underlin" href="http://lazarusalliance.comsupport/index.php?pg=request" target="_blank">contact us</a> and we'll be happy to help you!</p>	
											</li>
								    		<li id="li_submit" class="buttons" style="overflow: auto">
										    	<button type="submit" class="bb_button bb_green" id="submit_button" name="submit_button" style="float: left">
											        <span class="icon-checkmark"></span>
											        Check Again
											    </button>
											</li>
										</ul>
									</form>
								</div>	


					<?php	}	
 						  } //end - else if there are pre install error
					?>
					
					
				</div>
     
        	</div>  		 
		</div>
	
		

<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
EOT;
	require('includes/footer.php'); 
?>
