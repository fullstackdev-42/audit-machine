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

if (isset($_POST['file_name'])) {

    echo '<pre>Running DB Upgrade...</pre>';

    $commands = file_get_contents($_POST['file_name']);
    //convert to array
    $commands = explode(";", $commands);
    
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
    foreach($commands as $command){
        if ($command != '') {
            $sth = $dbh->prepare($command);
            try{
                $sth->execute(null);
                $success++;
            }catch(PDOException $e) {
                $sth->debugDumpParams();
                echo '<pre>ERROR Running Statement: ' . $command . '<br>' . $e->getMessage() . '</pre>';
            }
            $total++;
        }
    }
    if ($success == $total) {
        echo 'All SQL Statements Ran Successfully<br><br>';

        //Move the SQL file to the archive folder
        if (!file_exists('archive/')) {
            mkdir('archive', 0777, false);
        }
        $currentDate = date('m.d.Y');
        rename($_POST['file_name'], 'archive/' . $_POST['file_name'] . '.' . $currentDate);
        echo 'The SQL file has been moved to the archive directory';
    }
}

$files = scandir("./");

?>
<html>
    <body style="font-family: arial; text-align: center;">
        <h2 style="margin-top: 200px;">ITAM DB Upgrader</h2>
        <form action="DBUpgrade.php" method="post">
            SQL File: 
            <select name="file_name" id="file_name" style="width: 250px;">
            <?php
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $file != 'DBUpgrade.php' && $file != 'archive') {
                    echo "<option>" . $file . "</option>";
                }
            }
            ?>
            </select>
            <br><br>
            <input type="submit" value="Run DB Upgrade">
        </form>
    </body>
</html>