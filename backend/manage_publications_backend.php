<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/fulltext_functions.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uri = $_POST['uri'] ?? null;
    $action = $_POST['action'] ?? null;




    if (!$uri || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Missing URI or action or title']);
        exit;
    }

    if ($action === 'delete') {
        $rdfDeleted = deletePublicationCompletely($uri);
        $solrDeleted = deleteSolrDocumentByPublication($uri);

        if ($rdfDeleted) {
            echo json_encode(['status' => 'success', 'message' => 'Publication deleted.']);
            exit;
        }else{
            echo json_encode(['status' => 'error', 'message' => 'Rejection failed']);
            exit;
       } 
    }
}
else{

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
