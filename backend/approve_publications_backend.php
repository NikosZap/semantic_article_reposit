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
    $filePath = $_POST['filePath'];
    $abstract = $_POST['abstract'];


    if (!$uri || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Missing URI or action or title']);
        exit;
    }
    $userData = getPublicationUploader($uri);
    $email = $userData['email'] ?? '';
    $name = $userData['firstname'] ?? '';


    if ($action === 'approve') {
       if( approvePublication($uri) ){
            
            $fullText = storeFullTextFromPDF($uri, $filePath);

            if($fullText){
                //insert SOLR 
                createSolrDocumentFromPublication($uri, $title, $fullText, $abstract);

                //insert Spotlight
                $semanticText = trim($title . ' ' . $abstract . ' ' . $fullText);
                $entities = getDBpediaEntities($semanticText);
                $entityURIs = array_column($entities, 'uri');
                $resultSpotlight = addMentionsToPublication($uri, $entityURIs);

                if(!$resultSpotlight){
                    echo json_encode(['status' => 'error', 'message' => 'Spotlight mentions failed']);
                    exit;
                }

                sendPublicationApprovalEmail($email, $name, $title);
                echo json_encode(['status' => 'success', 'message' => 'Publication approved']);
                exit;
            }
            else{
                echo json_encode(['status' => 'error', 'message' => 'Approval failed']);
                exit;
            }
       }else{
            echo json_encode(['status' => 'error', 'message' => 'Approval failed']);
            exit;
       } 
    }

    if ($action === 'reject') {
        if(deletePublication($uri)){
            sendPublicationRejectionEmail($email, $name, $title, $reason);
            echo json_encode(['status' => 'success', 'message' => 'Publication rejected and deleted.']);
            exit;
        }else{
            echo json_encode(['status' => 'error', 'message' => 'Rejection failed']);
            exit;
       } 
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
