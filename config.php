<?php
define('BASE_PATH', __DIR__ . '/'); // project root
define("BASE_URL", "https://a2bf-109-242-35-165.ngrok-free.app/my_project2/");

//Apache Jena Fuseki SPARQL Endpoint 
define('SPARQL_ENDPOINT', 'http://localhost:3030/semanticApp/sparql');
define('SPARQL_UPDATE_ENDPOINT','http://localhost:3030/semanticApp/update');

// Apache Solr Endpoint
define('SOLR_CORE', 'sem_text_core');
define('SOLR_SELECT_ENDPOINT', 'http://localhost:8983/solr/' . SOLR_CORE . '/select');

define('BASE_URI', 'http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#');

// SPARQL SELECT query
function executeSparqlQuery($query) {
    $url = SPARQL_ENDPOINT;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    $data = http_build_query(['query' => $query]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true); // JSON
}

function executeSparqlUpdate($updateQuery) {
    $url = SPARQL_UPDATE_ENDPOINT;
    $headers = ['Content-Type: application/sparql-update'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $updateQuery);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response !== false;
}

session_start();
?>
