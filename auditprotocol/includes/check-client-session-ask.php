<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
	//check if client user logged in or not
	//if not check if an internal user is logged in
	//if not redirect them into client login page

if(empty($_SESSION['la_client_logged_in'])){

	$current_dir = dirname($_SERVER['PHP_SELF']);
	if($current_dir == "/" || $current_dir == "\\"){
		$current_dir = '';
	}
	$fail = 1;
}

//Get the form ID and the Client ID
$form_id = $_GET['id'];
$client_id = $_SESSION['la_client_user_id'];

//Connect to the database
$dbh = la_connect_db();

//check to see if user has access to user form
$query = "SELECT `registration_id` FROM ".LA_TABLE_PREFIX."ask_client_forms WHERE `form_id`=" . $form_id . " AND `client_id`=" . $client_id;
$sth2 = $dbh->prepare($query);
try{
	$sth2->execute($params);
}catch(PDOException $e) {
	$fail = 1;
}
$count = $sth2->rowCount();
//if no results are found, so we should kick you out
if($count <1){

	$current_dir = dirname($_SERVER['PHP_SELF']);
	if($current_dir == "/" || $current_dir == "\\"){
		$current_dir = '';
	}
	$fail = 1;
}
if($fail == 1){
	check_admin();
}
//check if back end user is logged in
function check_admin(){
	if(!empty($_COOKIE['la_remember']) && empty($_SESSION['la_logged_in'])){
		$dbh	= la_connect_db();
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
			$_SESSION['la_logged_in'] = true;
			$_SESSION['la_user_id']	  = $la_user_id;
			$_SESSION['la_user_privileges']['priv_administer'] = $la_priv_administer;
			$_SESSION['la_user_privileges']['priv_new_forms']  = $la_priv_new_forms;
			$_SESSION['la_user_privileges']['priv_new_themes'] = $la_priv_new_themes;
		}

	}

	if(empty($_SESSION['la_logged_in'])){

		$current_dir = dirname($_SERVER['PHP_SELF']);
		if($current_dir == "/" || $current_dir == "\\"){
			$current_dir = '';
		}

		$_SESSION['LA_LOGIN_ERROR'] = 'Your session has expired. Please login.';
		header("Location: /portal/index.php?from=".base64_encode($_SERVER['REQUEST_URI']));
		exit;
	}
}
?>
