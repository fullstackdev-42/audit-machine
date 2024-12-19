<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/

 More info at: http://lazarusalliance.com/
 ********************************************************************************/
 	$dbh	= la_connect_db();
 	$la_settings = la_get_settings($dbh);

 	//check if the site has been down or not
	//check if user logged in or not
	//if not redirect them into login page
	//first we need to check if the user has "remember me" cookie or not

	if($la_settings["enable_site_down"]) {
		header("Location: logout.php");
		exit;
	}

	if(!empty($_COOKIE['la_remember']) && empty($_SESSION['la_logged_in'])){
		$query  = "SELECT
						`user_id`,
						`priv_administer`,
						`priv_new_forms`,
						`priv_new_themes`
					FROM
						`".LA_TABLE_PREFIX."users`
					WHERE
						`cookie_hash`=? and `status`=1";
		$params = array($_COOKIE['la_remember']);

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$la_user_id 		  = $row['user_id'];
		$la_priv_administer	  = (int) $row['priv_administer'];
		$la_priv_new_forms	  = (int) $row['priv_new_forms'];
		$la_priv_new_themes	  = (int) $row['priv_new_themes'];

		if(!empty($la_user_id)){

			if(empty($_SESSION['la_logged_in']) || $_SESSION['la_logged_in'] === false ) {
				$last_ip_address 	  = $_SERVER['REMOTE_ADDR'];
				// add user activity to log: activity - 6 (LOGIN)
				addUserActivity($dbh, $la_user_id, 0, 6, "", time(), $last_ip_address);
			}

			$_SESSION['la_logged_in'] = true;
			$_SESSION['la_user_id']	  = $la_user_id;
			$_SESSION['la_user_privileges']['priv_administer'] = $la_priv_administer;
			$_SESSION['la_user_privileges']['priv_new_forms']  = $la_priv_new_forms;
			$_SESSION['la_user_privileges']['priv_new_themes'] = $la_priv_new_themes;
		}
	}

	if(empty($_SESSION['la_logged_in'])){
		$ssl_suffix  = la_get_ssl_suffix();

		$current_dir = dirname($_SERVER['PHP_SELF']);
      	if($current_dir == "/" || $current_dir == "\\"){
			$current_dir = '';
		}

		$_SESSION['LA_LOGIN_ERROR'] = 'Your session has expired. Please login.';
		header("Location: index.php?from=".base64_encode($_SERVER['REQUEST_URI']));
		exit;
	}

	//redirect to the manage_forms.php if a login msg is enabled but a user attempts to access other pages by entering the url
	if(isset($_SESSION['admin_login_message_enabled']) && $_SESSION['admin_login_message_enabled'] == 1) {
		if($_SERVER['PHP_SELF'] == "/auditprotocol/manage_forms.php" || $_SERVER['PHP_SELF'] == "/auditprotocol/logout.php") {
			//do nothing
		} else {
			header("Location: /auditprotocol/manage_forms.php");
			exit();
		}
	}
?>