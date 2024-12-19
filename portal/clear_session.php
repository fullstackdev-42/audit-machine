<?php
	
	session_start();
	
	$_SESSION = NULL;
	$_SESSION['csrf_token'] = NULL;
	
	?>