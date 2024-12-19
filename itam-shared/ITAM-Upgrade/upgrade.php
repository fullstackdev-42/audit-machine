<?php
/************************************************************************* 
Version 1.0 of ITAM DB Upgrade Utility
Utility will check /ITAM-SHARED/ITAM-Upgrade/ folder for .sql files
All SQL statements must end with a ';'
*************************************************************************/

require('../../auditprotocol/config.php');

session_start();

if ($_SESSION['la_user_privileges']['priv_administer'] != 1) {
    die;
}

if (isset($_FILES['file_name'])) {
    echo '<pre>Running DB Upgrade...</pre>';

    $fileTmpName  = $_FILES['file_name']['tmp_name'];
    $sql_file_dir = "SQL_Files/";
    if (!file_exists($sql_file_dir)) {
        mkdir($sql_file_dir, 0777);
    }
    $sql_file_path = $sql_file_dir . basename($_FILES["file_name"]["name"]);

    $didUpload = move_uploaded_file($fileTmpName, $sql_file_path);
    if ($didUpload) {
        $commands = file_get_contents($sql_file_path);
        //convert to array
        $commands = explode(";", $commands);
        
        $dbh = null;
        try {
            $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD,array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
            $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $dbh->query("SET NAMES utf8");
            $dbh->query("SET sql_mode = ''");
            
        } catch(PDOException $e) {
            die("Error connecting to the database: ".$e->getMessage());
        }
        
        //run commands
        $total = 0;
        $success = 0;
        foreach($commands as $command){
            if ($command != '') {
                $sth = $dbh->prepare($command);
                try{
                    $sth->execute(null);
                    $success++;
                    echo '<h4 style="color: blue">OK! ';
                    echo $command . '</h4>';
                }catch(PDOException $e) {
                    $sth->debugDumpParams();
                    echo '<pre>Error Running Statement: ' . $command . '<br>' . $e->getMessage() . '</pre>';
                }
                $total++;
            }
        }
        if ($success == $total) {
            echo 'All SQL Statements Ran Successfully<br><br>';
        }
        else {
            echo '<h4 style="color:red">Failure: Check SQL Statements</h4>';
        }
    } 
    else {
        echo '<h3 style="color: red">Error Uploading SQL File!</h3>';
    }
}
if (isset($_POST['new_version'])) {

    if (strlen($_POST['new_version']) > 0) {
        $version_update = "UPDATE ".LA_TABLE_PREFIX."settings SET itauditmachine_version = '" . trim($_POST['new_version']) . "'";
        try {
            $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD,array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
            $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $dbh->query("SET NAMES utf8");
            $dbh->query("SET sql_mode = ''");
            
        } catch(PDOException $e) {
            die("Error connecting to the database: ".$e->getMessage());
        }
        $sth = $dbh->prepare($version_update);
        try{
            $sth->execute(null);
            echo '<h2 style="color:green">ITAM Version Updated</h2>';
        }catch(PDOException $e) {
            $sth->debugDumpParams();
            echo '<pre>ERROR Running Statement: ' . $command . '<br>' . $e->getMessage() . '</pre>';
        }
    }
}

$current_itam_version;
$version_query = "SELECT itauditmachine_version FROM ".LA_TABLE_PREFIX."settings";
$dbh = null;
try {
    $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
    $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $dbh->query("SET NAMES utf8");
    $dbh->query("SET sql_mode = ''");
} catch(PDOException $e) {
    die("Error connecting to the database: ".$e->getMessage());
}
$sth = $dbh->prepare($version_query);
$row = null;
try{
    $sth->execute();
    $row = $sth->fetchAll();
    $current_itam_version = $row[0][0];
}catch(PDOException $e) {
    echo '<pre>***Error Retrieving Current ITAM Version Number***<br>' . $e->getMessage() . '</pre>';
}

$files = scandir("./");

?>
<html>
    <body style="font-family: arial; text-align: center;">
        <h2 style="margin-top: 120px;">ITAM Upgrade Utility</h2>

        <form action="upgrade.php?type=Version" method="post" style="margin-top: 40px;">
            <h4>ITAM Version Number: <?php echo $current_itam_version; ?></h4>
            New Version Number:
            <input type="text" id="new_version" name="new_version" />
            <br><br>
            <input type="submit" value="Update ITAM Version">
        </form>
        <br><br>
        <h4>Database Update Tool</h4>
        <form action="upgrade.php?type=SQL" method="post" enctype="multipart/form-data">
            SQL File: 
            <input type="file" name="file_name" id="file_name" style="width: 250px;">
            <?php
            /*
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $file != 'DBUpgrade.php' && $file != 'archive') {
                    echo "<option>" . $file . "</option>";
                }
            }
            */
            ?>
            <!--</select>-->
            <br><br>
            <input type="submit" value="Run DB Update">
        </form>
    </body>
</html>