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

    $reason = $_POST['reason'] ?? 'No reason provided';


    if (!$uri || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Missing URI or action or title']);
        exit;
    }

    $userData = getPublicationUploader($uri);
    $email = $userData['email'] ?? '';
    $name = $userData['firstname'] ?? '';

    $publication = getPublicationByURI($uri);
    $abstract = $publication['abstract'] ?? '';
    $title = $publication['title'] ?? '';
    $fileName = $publication['filePath'] ?? '';
    $pendingPath = BASE_PATH . 'pending/' . $fileName;



    if ($action === 'approve') {
        if ($filePath === '') {
            echo json_encode(['status' => 'error', 'message' => 'Missing PDF file path']);
            exit;
        }

        $pendingPath = BASE_PATH . 'pending/' . $fileName;
        $uploadsPath = BASE_PATH . 'uploads/' . basename($fileName);

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
            unlink($pendingPath);
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
    }elseif ($action === 'update') {

        if (!$publication) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Publication not found.'
            ]);
            exit;
        }

        $type = extractSuffix($publication['type'] ?? '');

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'doi' => trim($_POST['doi'] ?? ''),
            'publicationDate' => trim($_POST['publicationDate'] ?? ''),
            'abstract' => trim($_POST['abstract'] ?? ''),
            'journal' => trim($_POST['journal'] ?? ''),
            'publisher' => trim($_POST['publisher'] ?? ''),
            'volume' => trim($_POST['volume'] ?? ''),
            'number' => trim($_POST['number'] ?? ''),
            'pages' => trim($_POST['pages'] ?? ''),
            'isbn' => trim($_POST['isbn'] ?? ''),
            'issn' => trim($_POST['issn'] ?? ''),
            'school' => trim($_POST['school'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'supervisor' => trim($_POST['supervisor'] ?? ''),
            'thesisType' => trim($_POST['thesisType'] ?? '')
        ];

        $result = updatePendingPublicationData($uri, $type, $data);

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Publication updated successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update publication.'
            ]);
        }
        exit;
    }  
    
    elseif ($action === 'crossref') {

        $publication = getPublicationByURI($uri);

        if (!$publication || empty($publication['doi'])) {
            echo json_encode(['status' => 'error', 'message' => 'DOI not found']);
            exit;
        }

        $doi = $publication['doi'];

        $crossrefData = fetchCrossrefByDOI($doi); 

        if (!$crossrefData) {
            echo json_encode(['status' => 'error', 'message' => 'Crossref fetch failed']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'userMeta' => [
                'title' => $publication['title'] ?? '',
                'doi' => $publication['doi'] ?? '',
                'publicationDate' => $publication['publicationDate'] ?? '',
                'journal' => $publication['journal'] ?? '',
                'publisher' => $publication['publisher'] ?? '',
                'volume' => $publication['volume'] ?? '',
                'number' => $publication['number'] ?? '',
                'pages' => $publication['pages'] ?? '',
                'isbn' => $publication['isbn'] ?? '',
                'issn' => $publication['issn'] ?? ''
            ],
            'crossrefMeta' => $crossrefData
        ]);
        exit;
    }

    
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);

function extractSuffix($uri)
{
    if (strpos($uri, '#') !== false) {
        return substr(strrchr($uri, '#'), 1);
    } elseif (strpos($uri, '/') !== false) {
        return substr(strrchr($uri, '/'), 1);
    }

    return $uri;
}
