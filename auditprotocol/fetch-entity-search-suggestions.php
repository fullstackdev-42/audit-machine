<!-- used to pull results for entity search suggestions -->

<?php

if(!empty($_GET['searchTerm'])) {

    require('config.php'); 

    $dbh   = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
    $query = "SELECT * FROM ap_ask_clients WHERE company_name LIKE '%{$_GET['searchTerm']}%' LIMIT 5";
    
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