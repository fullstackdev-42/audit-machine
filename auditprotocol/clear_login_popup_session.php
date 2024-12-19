<?php
require('includes/init.php');

if(isset($_POST)) {
	if($_POST["mode"] == "clear_login_popup_session" && $_POST["clear_session"]) {
		unset($_SESSION["admin_login_message_enabled"]);
		echo "cleared";
		exit();
	}
}
?>