<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'backend/email/email_functions.php';
require_once BASE_PATH . 'includes/fulltext_functions.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uri = $_POST['uri'] ?? null;
    $action = $_POST['action'] ?? null;
    $title = $_POST['title'] ?? '';
    $reason = $_POST['reason'] ?? 'No reason provided';


    if (!$uri || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Missing URI or action or title']);
        exit;
    }

    $userData = getPublicationUploader($uri);
    $email = $userData['email'] ?? '';
    $name = $userData['firstname'] ?? '';


    if ($action === 'approve') {

        $filePath = trim($_POST['filePath'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');

        if ($filePath === '') {
            echo json_encode(['status' => 'error', 'message' => 'Missing PDF file path']);
            exit;
        }

        $fullText = storeFullTextFromPDF($uri, $filePath);

        if (!$fullText) {
            echo json_encode(['status' => 'error', 'message' => 'Full text extraction failed']);
            exit;
        }

        $solrResult = createSolrDocumentFromPublication($uri, $title, $fullText, $abstract);

        if (!$solrResult) {
            echo json_encode(['status' => 'error', 'message' => 'Solr indexing failed']);
            exit;
        }

        $semanticText = trim($title . ' ' . $abstract . ' ' . $fullText);
        $entities = getDBpediaEntities($semanticText);

        if ($entities === false) {
            echo json_encode(['status' => 'error', 'message' => 'DBpedia Spotlight extraction failed']);
            exit;
        }

        $entityURIs = array_column($entities, 'uri');

        if (!empty($entityURIs)) {
            $resultSpotlight = addMentionsToPublication($uri, $entityURIs);

            if (!$resultSpotlight) {
                echo json_encode(['status' => 'error', 'message' => 'Spotlight mentions failed']);
                exit;
            }
        }

        if (!approvePublication($uri)) {
            echo json_encode(['status' => 'error', 'message' => 'Final approval update failed']);
            exit;
        }

        sendPublicationApprovalEmail($email, $name, $title);

        echo json_encode(['status' => 'success', 'message' => 'Publication approved']);
        exit;
    }elseif ($action === 'reject') {
        if(deletePublication($uri)){
            sendPublicationRejectionEmail($email, $name, $title, $reason);
            echo json_encode(['status' => 'success', 'message' => 'Publication rejected and deleted.']);
            exit;
        }else{
            echo json_encode(['status' => 'error', 'message' => 'Rejection failed']);
            exit;
       } 
    }elseif($action === 'delete') {
        $rdfDeleted = deletePublicationCompletely($uri);
        $solrDeleted = deleteSolrDocumentByPublication($uri);
        
        if ($rdfDeleted) {
            sendPublicationDeletionEmail($email, $name, $title);
            echo json_encode(['status' => 'success', 'message' => 'Publication deleted.']);
            exit;
        }else{
            echo json_encode(['status' => 'error', 'message' => 'Rejection failed']);
            exit;
       } 
    }

    
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
