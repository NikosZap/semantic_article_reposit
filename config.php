<?php

define("BASE_PATH", __DIR__ . "/"); // project root
define("BASE_URL", "https://9697-2a03-f003-142-c300-6516-812b-ecf3-8e78.ngrok-free.app/my_project2/");

// Fuseki SPARQL Endpoint 
define('SPARQL_ENDPOINT', 'http://localhost:3030/semanticApp/sparql');
define('SPARQL_UPDATE_ENDPOINT','http://localhost:3030/semanticApp/update');

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

    return json_decode($response, true); // JSON μορφή
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
