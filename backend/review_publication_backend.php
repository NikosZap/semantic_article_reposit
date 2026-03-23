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

    $filePath = trim($_POST['filePath'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');


    if ($action === 'approve') {
        if ($filePath === '') {
            echo json_encode(['status' => 'error', 'message' => 'Missing PDF file path']);
            exit;
        }

        $pendingPath = $filePath;
        $fileName = basename($filePath);
        $uploadsPath = BASE_PATH . 'uploads/' . $fileName;

        if (!file_exists($pendingPath) || !is_file($pendingPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Pending PDF file not found']);
            exit;
        }
 
        $fullText = storeFullTextFromPDF($uri, $filePath);

        if ($fullText === null || trim($fullText) === '') {
            echo json_encode(['status' => 'error', 'message' => 'Full text extraction failed']);
            exit;
        }

        $solrResult = createSolrDocumentFromPublication($uri, $title, $fullText, $abstract);

        if (!$solrResult) {
            echo json_encode(['status' => 'error', 'message' => 'Solr indexing failed']);
            exit;
        }

    
        $semanticText = trim($title . ' ' . $abstract . ' ' . $fullText);

   
        $semanticTextForSpotlight = mb_substr($semanticText, 0, 15000);

    
        $entitiesResult = getDBpediaEntities($semanticTextForSpotlight);

    
        writeSpotlightDebugFiles($uri, ['01_semantic_text.txt' => $semanticTextForSpotlight,
                                        '02_spotlight_response.json' => $entitiesResult['raw_response'] ?? null,
                                        '03_entities.json' => $entitiesResult, ]);

        if (!is_array($entitiesResult) || !isset($entitiesResult['success']) || $entitiesResult['success'] !== true )   {
            echo json_encode([
                'status' => 'error',
                'message' => 'DBpedia Spotlight extraction failed',
                'debug_error' => $entitiesResult['error'] ?? 'Unknown error'
            ]);
            exit;
        }

        $entities = $entitiesResult['entities'] ?? [];
        $entityURIs = array_values(array_unique(array_filter(array_column($entities, 'uri'))));

        writeSpotlightDebugFiles($uri, [
            '04_entity_uris.json' => $entityURIs
        ]);

        // 6. Save mentions only if there are entities
        if (!empty($entityURIs)) {
            $resultSpotlight = addMentionsToPublication($uri, $entityURIs);

            writeSpotlightDebugFiles($uri, [
                '05_mentions_insert_result.json' => [
                    'publication_uri' => $uri,
                    'entity_count' => count($entityURIs),
                    'result' => $resultSpotlight
                ]
            ]);

            if (!$resultSpotlight) {
                echo json_encode(['status' => 'error', 'message' => 'Spotlight mentions failed']);
                exit;
            }
        } else {
            writeSpotlightDebugFiles($uri, [ '05_mentions_insert_result.json' => ['publication_uri' => $uri,
                                                                                   'entity_count' => 0,
                                                                                   'result' => 'No entities found']]);
        }

        // 7. Move file
        if (!rename($pendingPath, $uploadsPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move PDF to uploads folder']);
            exit;
        }

        // 8. Approve publication
        if (!approvePublication($uri)) {
            if (file_exists($uploadsPath)) {
                rename($uploadsPath, $pendingPath);
            }

            echo json_encode(['status' => 'error', 'message' => 'Final approval update failed']);
            exit;
        }

        sendPublicationApprovalEmail($email, $name, $title);

        echo json_encode([
            'status' => 'success',
            'message' => 'Publication approved',
            'entities_found' => count($entityURIs)
        ]);
        exit;
    }elseif ($action === 'reject') {
        if(deletePublication($uri)){
            unlink($filePath);
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

        if (!empty($filePath) && file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        }

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
