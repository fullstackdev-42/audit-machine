<?php
if(!empty($_GET['searchTerm'])) {
    require('../../portal/config.php');

    $string = $_GET['searchTerm'];
    if (strpos($_GET['searchTerm'], "'") !== false) {
        $string = str_replace ("'", "''", $string);
    }

    $dbh             = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
    $query           = "SELECT * FROM ap_ask_clients WHERE company_name LIKE '%{$string}%' LIMIT 5";
    $numberOfResults = 0;

    foreach($dbh->query($query) as $result) {
        $numberOfResults = $numberOfResults + 1;
        echo '<li class="result" data-client-id='.$result['client_id'].'>'.$result['company_name']."</li>";
    }

    if ($numberOfResults == 0) {
        echo " ";
    }
} else {
    echo "No search term was provided.";
}
