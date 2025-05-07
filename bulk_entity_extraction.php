<?php
require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH. 'includes/fulltext_functions.php'; // περιέχει getDBpediaEntities() και addMentionsToPublication()
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

// 1. Πάρε όλες τις δημοσιεύσεις με fulltext
$sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

SELECT ?pub ?text
WHERE {
  ?ft a app:FullText ;
      app:textContent ?text ;
      app:isFullTextOf ?pub .
}";


$results =  executeSparqlQuery($sparql);

if (!isset($results['results']['bindings'])) {
    die("No results or SPARQL error.\n");
}


foreach ($results['results']['bindings'] as $row) {
    $pubURI = $row['pub']['value'];
    $textContent = $row['text']['value'];

    echo "Processing: $pubURI\n";

    $maxLength = 5000; // μέγιστο που δέχεται αξιόπιστα το API
    $ratio = 1; // παίρνουμε το 30% του κειμένου

    $textShort = substr($textContent, 0, min($maxLength, intval(strlen($textContent) * $ratio)));

    $entities = getDBpediaEntities($textShort);
    $entityURIs = array_column($entities, 'uri');

    print_r($entities);

    if (count($entityURIs) === 0) {
        echo "No entities found.\n";
        continue;
    }

    $result = addMentionsToPublication($pubURI, $entityURIs);

    print_r($result);

    if ($result['success']) {
        echo "✅ Added " . count($entityURIs) . " mentions.\n";
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
    }

    // Προαιρετικά sleep για να μην χτυπήσεις το API
    sleep(1);
}