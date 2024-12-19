<?php

require('includes/init.php');	
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');

if(empty($_SESSION['la_user_privileges']['priv_administer'])) {
    $_SESSION['LA_DENIED'] = "Insufficient Privileges";
    header("Location: /restricted.php");
    exit;
}

$dbh = null;
try {
    $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME, LA_DB_USER, LA_DB_PASSWORD, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
    $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $dbh->query("SET NAMES utf8");    
} 
catch(PDOException $e) {
    echo $e->getMessage();
}
$sql = "SHOW TABLES FROM `".LA_DB_NAME."` LIKE '%_form_%'";
$qry = $dbh->prepare($sql);
$qry->execute();

while ($row = $qry->fetch(PDO::FETCH_ASSOC)) {

    //$tableName = $row['Tables_in_'.LA_DB_NAME.' (%_form_%)'];
    foreach ($row as $colHeader => $tableName) {
    
        if (preg_match('~[0-9]~', $tableName)){
            $dataQuery = "SELECT id, data_value FROM `" . $tableName . "` WHERE data_value LIKE '<%'";
            $dataResults = $dbh->prepare($dataQuery);
            $dataResults->execute();

            while ($dataRow = $dataResults->fetch(PDO::FETCH_ASSOC)) {
                //DO NOT replace if the string contains image tags
                if (!strpos($dataRow['data_value'], "<img")) {
                    $textData = $dataRow['data_value'];
                    echo '<br><b>TABLE NAME: </b>' . $tableName;
                    echo '<br><div style="vertical-align=top;display: inline-block;"><b>OLD VALUE:</b> <textarea rows=4 cols=200>' . $textData . '</textarea><br></div>';
                    
                    $textData = cleanPTagsFromString($textData);
                    $textData = capitalizeFirstLetter($textData);
                    $textData = capitalizeCompanyName($textData);
                    
                    echo '<div style="vertical-align=top;display: inline-block;"><b>NEW VALUE:</b> <textarea rows=4 cols=200>' . $textData . '</textarea><br></div>';
                    
                    $updateSQL = "UPDATE " . $tableName . " SET data_value = '" . $textData . "' WHERE id = " . $dataRow['id'];
                    $update = $dbh->prepare($sql);
                    echo '<br><br><b>UPDATE SQL:</b> ' . $updateSQL . '<br><br><br>';
                    $updateSuccess = $update->execute();
                    echo '<h1>RECORD UPDATED</h1>';
                }
                else {
                    $textData = $dataRow['data_value'];
                    echo '<br><div style="vertical-align=top;display: inline-block;"><b>NO CHANGE:</b> <textarea rows=4 cols=200>' . $textData . '</textarea><br></div>';
                }
            }
        }
    }  
}

function cleanPTagsFromString($string) {
    $string = str_replace('<p>', '', $string);
    $string = str_replace('</p>', '', $string);
    return $string;
}

function capitalizeCompanyName($string) {
    $string = str_replace('continuum', 'Continuum', $string);
    $string = str_replace('grc', 'GRC', $string);
    $string = str_replace('itam', 'ITAM', $string);
    $string = str_replace('saas', 'SAAS', $string);
    return $string;

}
function capitalizeFirstLetter($string) {

    $letters = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
         'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
    );
    foreach ($letters as $letter) {
        $string = str_replace('. ' . $letter, '. ' . ucwords($letter), $string);
        $string = str_replace('? ' . $letter, '? ' . ucwords($letter), $string);
        $string = str_replace('! ' . $letter, '! ' . ucwords($letter), $string);
    }
    $string = ucfirst($string);
    return $string;
}
?>