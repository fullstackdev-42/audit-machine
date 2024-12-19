<?php
/************************************************************************* 
Version 1.0 of ITAM DB Upgrade Utility
Utility will check /ITAM-SHARED/ITAM-Upgrade/ folder for .sql files
All SQL statements must end with a ';'
*************************************************************************/
// die();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$current_dir = getcwd();
if( ! parse_ini_file('allowed_ips.ini') ) {
    echo 'Not able to access allowed_ips.ini'; die();
}

 echo exec('whoami');

$ini = parse_ini_file('allowed_ips.ini');

if( empty($ini['allowed_ips']) ) {
    echo "Valid IP list not created."; die();
}

$allowed_ips = $ini['allowed_ips'];
$allowed_ips_arr = explode(',', $allowed_ips);
print_r($allowed_ips_arr);

function getRealIpAddr(){

    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$client_ip = getRealIpAddr();

if( !in_array($client_ip, $allowed_ips_arr) ) {
    echo "IP address not allowed."; die();
}


require('../../auditprotocol/config.php');
    echo '<pre>Running DB Upgrade...</pre>';
    // echo getcwd();
    $current_dir = getcwd();
    $currentDate = date('m-d-Y-h-i-s');

    $file_path = $current_dir.'/SQL_Files/queries.sql';
    if (!file_exists($file_path)) {
        die('file not found.');
    }
    // die();
    $commands = file_get_contents($file_path);
    //convert to array
    $commands = explode(";", $commands);
    // print_r($commands);
    // die();
    $dbh = null;
    try {
        $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD,
                             array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)
                             );
        $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $dbh->query("SET NAMES utf8");
        $dbh->query("SET sql_mode = ''");
        
    } catch(PDOException $e) {
          die("Error connecting to the database: ".$e->getMessage());
    }
    
    //run commands
    $total = 0;
    $success = 0;

//    $log_file = fopen("query_error_logs.txt", "w") or die("Unable to open file!");

    foreach($commands as $command){
        if ($command != '') {
            $sth = $dbh->prepare($command);
            try{
                $sth->execute(null);
                $success++;
            }catch(PDOException $e) {
                $sth->debugDumpParams();
                echo $error = "$currentDate ERROR Running Statement: " . $command . $e->getMessage()."\n\n";
                echo '<br>';                
				echo '<br>';                
             //   fwrite($log_file, $error);
            }
            $total++;
        }
    }
 //   fclose($log_file);
 
echo "Total Successful Queries: ".$success."<br>";
echo "Total Queries: ".$total."<br>";


    if ($success == $total) {
        echo 'All SQL Statements Ran Successfully<br><br>';

        //Move the SQL file to the archive folder
        if (!file_exists($current_dir.'/archive/')) {
            // mkdir("$current_dir/archive", 0777, true);
            if (!@mkdir("$current_dir/archive", 0777, true)) {
                $error = error_get_last();
                echo 'Not able to create archive directory. Error:- '.$error['message'];
          //      die();
                // echo $error['message'];
            }
        }

        if( !rename($file_path, $current_dir.'/archive/' . 'queries.sql' . '.' . $currentDate)) {
            $error = error_get_last();
            echo 'Not able to move file to archive directory. Error:- '.$error['message'];
        } else {
            echo 'The SQL file has been moved to the archive directory';
        }
        
    }

?>
