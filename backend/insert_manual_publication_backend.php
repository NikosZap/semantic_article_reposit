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


$type = $_POST['pubType'] ?? null;
$doi = $_POST['doi'] ?? '';
$title = $_POST['title'] ?? '';
$pubDate = $_POST['pubDate'] ?? '';
$authors = $_POST['authors'] ?? [];
$abstract = $_POST['abstract'] ?? '';

$keywordsJSON = $_POST['keywords'] ?? '[]';
$keywordArray = json_decode($keywordsJSON, true);

$additionalFields = [
    "journal" => $_POST['journal'] ?? '',
    "volume" => $_POST['volume'] ?? '',
    "number" => $_POST['number'] ?? '',

    "conferenceName" => $_POST['conferenceName'] ?? '',
    "conferenceLocation" => $_POST['conferenceLocation'] ?? '',
    "conferencePages" => $_POST['conferencePages'] ?? '',
    "conferencePublisher" => $_POST['conferencePublisher'] ?? '',

    "bookPublisher" => $_POST['bookPublisher'] ?? '',
    "bookISBN" => $_POST['bookISBN'] ?? '',
    "bookVolume" => $_POST['bookVolume'] ?? '',

    "series" => $_POST['series'] ?? '',
    "pages" => $_POST['pages'] ?? '',
    "edition" => $_POST['edition'] ?? '',
    "publisher" => $_POST['publisher'] ?? '',
    "isbn" => $_POST['isbn'] ?? '',
    "school" => $_POST['school'] ?? '',
    "issn" => $_POST['issn'] ?? '',
    "department" => $_POST['department'] ?? '',
    "thesisType" => $_POST['thesisType'] ?? '',
];

if (isDuplicatePublication($type, $doi, $title, $pubDate, $authors, $additionalFields)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'This publication already exists in the system.'
    ]);
    exit;
}

$pdfFilePath = '';

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = BASE_PATH . 'pending/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = $_FILES['pdf']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        echo json_encode(["status" => "error", "message" => "Only PDF files are allowed." ]);
        exit;
    }

    $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($title));
    $cleanTitle = preg_replace('/_+/', '_', $cleanTitle);
    $cleanTitle = trim($cleanTitle, '_');
    $cleanTitle = substr($cleanTitle, 0, 50);

    $uniqueId = uniqid();

    $fileName = $cleanTitle . '_' . $uniqueId . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
        $pdfFilePath = $fileName;
    } else {
        echo json_encode(["status" => "error", "message" => "Saving PDF file error!" ]);
        exit;
    }
}

$abstract = escapeForSPARQLLiteral($abstract);

 

if (!$type || !$title || !$pubDate || !$pdfFilePath) {
    echo json_encode(["status" => "error", 'message' => 'Mandatory data are missing!']);
    exit;
}

if (empty($authors)) {
    echo json_encode(["status" => "error", 'message' => 'Missing Author!']);
    exit;
}


$pubURI = insertManualPublication($type, $doi, $title, $pubDate, $pdfFilePath, $abstract, $additionalFields);

if ( $pubURI ) {
    
    $allSuccess = true;

    foreach ($authors as $author) {

        $orcid = trim($author['orcid'] ?? '');
        $firstname = trim($author['firstname'] ?? '');
        $lastname = trim($author['lastname'] ?? '');

        if (!$orcid && !$firstname && !$lastname) {
            continue; 
        }

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

function isDuplicatePublication($type, $doi, $title, $pubDate, $authors, $additionalFields) {

    switch ($type) {
        case 'Article':
        case 'ConferencePaper':
            if (!empty($doi) && publicationExistsByDOI($doi)) {
                return true;
            }

            return publicationExistsByTitleAndDate($title, $pubDate);

        case 'Book':
            if (!empty($additionalFields['bookISBN']) && publicationExistsByISBN($additionalFields['bookISBN'])) {
                return true;
            }

            return publicationExistsByTitleAndPublisher( $title,  $additionalFields['bookPublisher'] ?? '', $pubDate );

        case 'Thesis':
            return publicationExistsByTitleAndSchool( $title,  $additionalFields['school'] ?? '', $pubDate );

        default:
            return false;
    }
}
