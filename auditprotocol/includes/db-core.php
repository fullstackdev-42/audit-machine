<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
 
	 function la_connect_db_new($la_db_host, $la_db_name, $la_db_user, $la_db_password){
			try {
			  $dbh = new PDO("mysql:host={$la_db_host};dbname={$la_db_name}", $la_db_user, $la_db_password,
				  				 array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)
				  				 );
			  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			  $dbh->query("SET NAMES utf8");
			  $dbh->query("SET sql_mode = ''");
			  return $dbh;
			} catch(PDOException $e) {
			    die("Error connecting to the database: ".$e->getMessage());
			}
		}

	function la_connect_db(){
		try {
		  $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD,
			  				 array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)
			  				 );
		  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		  $dbh->query("SET NAMES utf8");
		  $dbh->query("SET sql_mode = ''");
		  
		  return $dbh;
		} catch(PDOException $e) {
		    die("Error connecting to the database: ".$e->getMessage());
		}
	}
	
	function la_do_query($query,$params,$dbh){
		$sth = $dbh->prepare($query);
		try{
			$sth->execute($params);
		}catch(PDOException $e) {
			$sth->debugDumpParams();
			echo '<pre>'.print_r($params, 1).'</pre>';
			die("Query Failed: ".$e->getMessage());
		}
		
		return $sth;
	}
	
	function la_do_fetch_result($sth){
		return $sth->fetch(PDO::FETCH_ASSOC);	
	}

	function la_last_insert_id($dbh) {
		return $dbh->lastInsertId();
	}

	function la_get_row_count($dbh, $params, $query) {
		$sth = $dbh->prepare($query);
		try{
			$sth->execute($params);
			return $sth->rowCount();
		}catch(PDOException $e) {
			$sth->debugDumpParams();
			die("Query Failed: ".$e->getMessage());
		}
	}
	
	function la_ap_forms_update($id,$data,$dbh){
		
		$update_values = '';
		$params = array();
		
		//dynamically create the sql update string, based on the input given
		foreach ($data as $key=>$value){
			if($key == 'form_id'){
				continue;
			}

			if($value === "null"){
					$value = null;
			}
			
			$update_values .= "`{$key}` = :{$key},";
			$params[':'.$key] = $value;
		}
		$update_values = rtrim($update_values,',');
		
		$params[':form_id'] = $id;
		
		$query = "UPDATE `".LA_TABLE_PREFIX."forms` set 
									$update_values
							  where 
						  	  		form_id = :form_id";

		$sth = $dbh->prepare($query);
		try{
			$sth->execute($params);
		}catch(PDOException $e) {
			$sth->debugDumpParams();
			echo "Query Failed: ".$e->getMessage();

			$error_message = "Query Failed: ".$e->getMessage();
			return $error_message;
		}
		
		return true;
	}

	function la_ap_settings_update($data,$dbh){
		
		$update_values = '';
		$params = array();
		
		//dynamically create the sql update string, based on the input given
		foreach ($data as $key=>$value){
			if($value === "null"){
					$value = null;
			}
			
			$update_values .= "`{$key}`= :{$key},";
			$params[':'.$key] = $value;
		}
		$update_values = rtrim($update_values,',');
		
		$query = "UPDATE `".LA_TABLE_PREFIX."settings` set $update_values";

		$sth = $dbh->prepare($query);
		try{
			$sth->execute($params);
		}catch(PDOException $e) {
			$sth->debugDumpParams();
			echo "Query Failed: ".$e->getMessage();

			$error_message = "Query Failed: ".$e->getMessage();
			return $error_message;
		}
		
		return true;
	}
	
	function la_ap_form_themes_update($id,$data,$dbh){
		
		$update_values = '';
		$params = array();
		
		//dynamically create the sql update string, based on the input given
		foreach ($data as $key=>$value){
			if($value === "null"){
					$value = null;
			}
			
			$update_values .= "`{$key}`= :{$key},";
			$params[':'.$key] = $value;
		}
		$update_values = rtrim($update_values,',');
		
		$params[':theme_id'] = $id;
		
		$query = "UPDATE `".LA_TABLE_PREFIX."form_themes` set 
									$update_values
							  where 
						  	  		theme_id = :theme_id";
		
		$sth = $dbh->prepare($query);
		try{
			$sth->execute($params);
		}catch(PDOException $e) {
			$error_message = "Query Failed: ".$e->getMessage();
			return $error_message;
		}
		
		return true;
	}
	
	//check if a column name exist or not within a table
	//return true if column exist
	function la_mysql_column_exist($table_name, $column_name,$dbh) {

		$query = "SHOW COLUMNS FROM $table_name LIKE '$column_name'";
		$sth = la_do_query($query,array(),$dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row)){
			return true;	
		}else{
			return false;
		}
	}

	//check if a table exist or not within a db
	//return true if table exist
	function la_mysql_table_exist($table_name, $dbh) {
		$query = "SELECT COUNT(*) exist FROM information_schema.tables WHERE table_name = ?";
		$sth = la_do_query($query,array($table_name),$dbh);
		$row = la_do_fetch_result($sth);
		
		if($row['exist']){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Replaces any parameter placeholders in a query with the value of that
	 * parameter. Useful for debugging. Assumes anonymous parameters from 
	 * $params are are in the same order as specified in $query
	 *
	 * @param string $query The sql query with parameter placeholders
	 * @param array $params The array of substitution parameters
	 * @return string The interpolated query
	 */
	function interpolateQuery($query, $params) {
		$keys = array();
	
		# build a regular expression for each parameter
		foreach ($params as $key => $value) {
			if (is_string($key)) {
				$keys[] = '/:'.$key.'/';
			} else {
				$keys[] = '/[?]/';
			}
		}
	
		$query = preg_replace($keys, $params, $query, 1, $count);
	
		#trigger_error('replaced '.$count.' keys');
	
		return $query;
	}


?>
