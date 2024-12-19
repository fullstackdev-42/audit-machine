<?php

if(!empty($_GET['form_id'])) {
    require('config.php');

    $dbh             = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
    $searchTerm      = $_GET['form_id'];
    $query           = "SELECT * FROM ap_form_{$searchTerm}_saved_entries ORDER BY id DESC";
    $HTMLElement     = "";
    $numberOfResults = 0;

    $queryResult = $dbh->query($query);

    if($queryResult) {
        foreach ($queryResult as $row) {
            $numberOfResults     = $numberOfResults + 1;
            $dateCreated         = explode("entries_backup_", $row['pathtofile'])[1];
            $dateCreated         = explode("_", $dateCreated)[1];
            $dateCreated         = explode(".zip", $dateCreated)[0];
            $dateCreated         = date("m-d-Y",$dateCreated);
            $fileNameFromPath    = explode("entries_backup_", $row['pathtofile'])[1];
            $fileNameFromPath    = explode("_", $fileNameFromPath)[0];
            $formattedPathToFile = explode("auditprotocol", $row['pathtofile'])[1];
    
            $HTMLElement = $HTMLElement."
            <input type='hidden' name='companyId[]' id='companyId_0' value='1'>
            <tr id='server_entry_backup'><td class='me_action'>
                <input type='radio' id='import-from-server-radio-button' name='import-from-server-radio-button' value='".$row['id']."' data-db-id='".$row['id']."' data-path-to-file='".$formattedPathToFile."'></td>
                <td class='me_number'>".$numberOfResults."</td>
                <td class='fileNameFromPath'>".$fileNameFromPath."<br></td>
                <td>".$dateCreated."<br></td>
            </tr>";
        }
    } else {
        $HTMLElement = "
        <tr id='row_1'>
            <td class='me_action'>
            <td class='me_number'></td>
            <td>There aren't any server backups to choose from.</td>
            <td />
        </tr>";
    }

    echo $HTMLElement;

} else {
    echo "The GET request did not contain a valid form id.";
}


?>