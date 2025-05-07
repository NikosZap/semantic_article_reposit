<?php

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/fulltext_functions.php';

header('Content-Type: application/json');
ob_clean(); 



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid posted data!"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ανάθεση μεταβλητών
$type = $_POST['pubType'] ?? null;
$doi = $_POST['doi'] ?? '';
$title =$_POST['title'] ?? '';
$pubDate = $_POST['pubDate'] ?? '';
$authors = $_POST['authors'] ?? [];

$journal = $_POST['journal'] ?? '';
$volume = $_POST['volume'] ?? '';
$series = $_POST['series'] ?? '';
$pages = $_POST['pages'] ?? '';
$edition = $_POST['edition'] ?? '';
$publisher = $_POST['publisher'] ?? '';
$isbn = $_POST['isbn'] ?? '';
$school = $_POST['school'] ?? '';
$conferenceName = $_POST['confName'] ?? '';
$abstract = $_POST['abstract'] ?? '';

$keywordsJSON = $_POST['keywords'] ?? '[]';
$keywordArray = json_decode($keywordsJSON, true);


$additionalFields = [
    'journal' => $journal,
    'volume' => $volume,
    'series' => $series,
    'pages' => $pages,
    'edition' => $edition,
    'publisher' => $publisher,
    'isbn' => $isbn,
    'school' => $school,
    'conferenceName' => $conferenceName

  ];

//PDF File management
$pdfFilePath = '';

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = BASE_PATH . 'pending/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = basename($_FILES['pdf']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
        // Σχετικό path για αποθήκευση στο RDF
        $pdfFilePath = $fileName;
    }
    else{
        echo json_encode(["status" => "error", 'message' => 'Saving PDF file error!']);
        exit;
    }
}

$abstract = escapeForSPARQLLiteral($abstract);

 

if (!$type || !$title || !$pubDate || !$pdfFilePath) {
    echo json_encode(["status" => "error", 'message' => 'Mandatory data are missing!']);
    exit;
}


$pubURI = insertManualPublication($type, $doi, $title, $pubDate, $pdfFilePath, $abstract, $additionalFields);

if ( $pubURI ) {
    
    $allSuccess = true;

    foreach ($authors as $author) {

        $authorURI = getOrCreateAuthorURI($author);
       
        if (!$authorURI || !linkAuthorsToPublication($pubURI, $authorURI)) {
            $allSuccess = false;
            break;
        }
    }
    $allKeywordsLinked = true;

    if (is_array($keywordArray)) {
    foreach ($keywordArray as $keyword) {

        $keyword = trim(strtolower($keyword['value']));

        if ($keyword === '') continue;

        $keywordURI = getOrCreateKeywordURI($keyword);
        if ($keywordURI) {
            linkKeywordsToPublication($pubURI, $keywordURI);
        } else {
            $allKeywordsLinked = false;
            break; 
        }
    }   
    }
if (!$allKeywordsLinked) {
    echo json_encode(["status" => "error", "message" => "Failed to link all keywords."]);
    exit;
}
   

    if ($allSuccess) {
        echo json_encode(["status" => "success", 'message' => 'Publication inserted successfully!']);
        exit;
    } else {
        echo json_encode(["status" => "error", 'message' => 'Failed to link authors.']);
        exit;
    }
    
    
} else {
    echo json_encode(["status" => "error", "message"  => 'Insert publication failed', 'error' => $response['error'] ?? null]);
    exit;
} 

