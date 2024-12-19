<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
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
			die("Query Failed: ".$e->getMessage());
		}
		
		return $sth;
	}
	
	function la_do_fetch_result($sth){
		return $sth->fetch(PDO::FETCH_ASSOC);	
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

?>