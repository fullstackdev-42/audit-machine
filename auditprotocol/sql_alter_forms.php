<?php

include('config.php');

$hostname=LA_DB_HOST;
$username=LA_DB_USER;
$password=LA_DB_PASSWORD;
$dbname= LA_DB_NAME;

 
$conn = new mysqli($hostname, $username, $password, $dbname);


if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} else {
	$res = $conn->query("SHOW TABLES");
 	
	while($cRow = $res->fetch_assoc()) {
		
 		
		$tableList[] = $cRow['Tables_in_' . $dbname];
	}

 

	if( count($tableList) > 0 ) {
		$tableCount = 0;
		
		foreach ($tableList as $table) {
			if (strpos($table, LA_TABLE_PREFIX.'form_') !== false) {
 				echo $table;
 				
				$query = "SELECT
						  COLUMN_NAME, DATA_TYPE 
						FROM
						  INFORMATION_SCHEMA.COLUMNS 
						WHERE
						  TABLE_SCHEMA = '".$dbname."'
						AND
						  TABLE_NAME = '".$table."'";
				
 
				if($result = $conn->query($query)){
				    // Get field information for all columns
				    while ($column_info = $result->fetch_assoc()){
				    	$columnName = 'data_value';
				    	if( $column_info['COLUMN_NAME'] == 'data_value' && $column_info['DATA_TYPE'] == 'text'  ) {
				    		echo $updateQuery = "ALTER TABLE $table MODIFY data_value LONGTEXT";
				    		$conn->query($updateQuery);
				    	}
				    }
				}
				
				 
				
			}
		}	
	}
}

?>