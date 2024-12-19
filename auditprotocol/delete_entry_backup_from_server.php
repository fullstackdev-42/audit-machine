<?php

if(!empty($_REQUEST['entry_id_to_delete']) && !empty($_REQUEST['form_id']) && !empty($_REQUEST['path_to_file'])) {

    require('config.php');
    $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
    
    // delete the path to the file from ap_form_{$form_id}_saved_entries table
    $dbh->query("DELETE FROM ap_form_{$_REQUEST['form_id']}_saved_entries WHERE id={$_REQUEST['entry_id_to_delete']}")->fetch();
    
    // delete file from server
    unlink($_SERVER['DOCUMENT_ROOT']."/auditprotocol{$_REQUEST['path_to_file']}");

} else {
    die("Not all of the required parameters are present.");
}

?>

