<?php

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/fulltext_functions.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid posted data!"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user']['is_logged_in'])) {
    echo json_encode([
        "status" => "error",
        "message" => "You must be logged in."
    ]);
    exit;
}

$userID = $_SESSION['user']['id'] ?? null;
if (!$userID) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user session."
    ]);
    exit;
}

$userUri = getUserUriByUserId($userID);
if (!$userUri) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found."
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $uri = $_POST['uri'] ?? '';

    if (!$uri) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing publication URI."
        ]);
        exit;
    }

    if (!isPublicationOwnedByUser($uri, $userUri)) {
        echo json_encode([
            "status" => "error",
            "message" => "Access denied."
        ]);
        exit;
    }

    $publication = getPublicationByURI($uri);
    if (!$publication) {
        echo json_encode([
            "status" => "error",
            "message" => "Publication not found."
        ]);
        exit;
    }

    $filePath = $publication['filePath'] ?? '';

    $deleted = deletePublicationByURI($uri);

    if ($deleted) {
        if (!empty($filePath)) {
            $pendingPath = BASE_PATH . 'pending/' . $filePath;
            $uploadPath  = BASE_PATH . 'uploads/' . $filePath;

            if (file_exists($pendingPath)) {
                @unlink($pendingPath);
            }

            if (file_exists($uploadPath)) {
                @unlink($uploadPath);
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Publication deleted successfully."
        ]);
        exit;
    }

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete publication."
    ]);
    exit;
}

$uri = $_POST['uri'] ?? '';
$type = $_POST['pubType'] ?? '';
$postedDoi = trim($_POST['doi'] ?? '');
$title = trim($_POST['title'] ?? '');
$pubDate = trim($_POST['pubDate'] ?? '');
$authors = $_POST['authors'] ?? [];
$abstract = trim($_POST['abstract'] ?? '');

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

if (!$uri || !$type || !$title || !$pubDate) {
    echo json_encode([
        "status" => "error",
        "message" => "Mandatory data are missing!"
    ]);
    exit;
}

if (!isPublicationOwnedByUser($uri, $userUri)) {
    echo json_encode([
        "status" => "error",
        "message" => "Access denied."
    ]);
    exit;
}

$existingPublication = getPublicationByURI($uri);
if (!$existingPublication) {
    echo json_encode([
        "status" => "error",
        "message" => "Publication not found."
    ]);
    exit;
}

/* DOI and TYPE are immutable */
$existingType = '';
if (!empty($existingPublication['type'])) {
    if (strpos($existingPublication['type'], '#') !== false) {
        $existingType = substr(strrchr($existingPublication['type'], '#'), 1);
    } elseif (strpos($existingPublication['type'], '/') !== false) {
        $existingType = substr(strrchr($existingPublication['type'], '/'), 1);
    } else {
        $existingType = $existingPublication['type'];
    }
}

$existingDoi = trim($existingPublication['doi'] ?? '');

if ($type !== $existingType) {
    echo json_encode([
        "status" => "error",
        "message" => "Publication type cannot be changed."
    ]);
    exit;
}

if ($postedDoi !== $existingDoi) {
    echo json_encode([
        "status" => "error",
        "message" => "DOI cannot be changed."
    ]);
    exit;
}

if (empty($authors)) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing Author!"
    ]);
    exit;
}

$hasValidAuthor = false;
foreach ($authors as $author) {
    $orcid = trim($author['orcid'] ?? '');
    $firstname = trim($author['firstname'] ?? '');
    $lastname = trim($author['lastname'] ?? '');

    if ($orcid || $firstname || $lastname) {
        $hasValidAuthor = true;
        break;
    }
}

if (!$hasValidAuthor) {
    echo json_encode([
        "status" => "error",
        "message" => "At least one author is required."
    ]);
    exit;
}

$pdfFilePath = $existingPublication['filePath'] ?? '';

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = BASE_PATH . 'pending/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = $_FILES['pdf']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        echo json_encode([
            "status" => "error",
            "message" => "Only PDF files are allowed."
        ]);
        exit;
    }

    $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($title));
    $cleanTitle = preg_replace('/_+/', '_', $cleanTitle);
    $cleanTitle = trim($cleanTitle, '_');
    $cleanTitle = substr($cleanTitle, 0, 50);

    $uniqueId = uniqid();
    $fileName = $cleanTitle . '_' . $uniqueId . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
        echo json_encode([
            "status" => "error",
            "message" => "Saving PDF file error!"
        ]);
        exit;
    }

    /* delete previous file if new one uploaded */
    $oldPendingPath = BASE_PATH . 'pending/' . $pdfFilePath;
    $oldUploadPath  = BASE_PATH . 'uploads/' . $pdfFilePath;

    if (!empty($pdfFilePath)) {
        if (file_exists($oldPendingPath)) {
            unlink($oldPendingPath);
        }
        if (file_exists($oldUploadPath)) {
            unlink($oldUploadPath);
        }
    }

    $pdfFilePath = $fileName;
}

$abstract = escapeForSPARQLLiteral($abstract);

$updated = updatePublicationByURI( $uri, $type, $title, $pubDate, $pdfFilePath, $abstract, $additionalFields);

if (!$updated) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update publication."
    ]);
    exit;
}

$approvalReset = setPublicationApprovalStatus($uri, false);

if (!$approvalReset) {
    echo json_encode([
        "status" => "error",
        "message" => "Publication updated, but failed to reset approval status."
    ]);
    exit;
}

if (!unlinkAllAuthorsFromPublication($uri)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to clear previous authors."
    ]);
    exit;
}

foreach ($authors as $author) {
    $orcid = trim($author['orcid'] ?? '');
    $firstname = trim($author['firstname'] ?? '');
    $lastname = trim($author['lastname'] ?? '');

    if (!$orcid && !$firstname && !$lastname) {
        continue;
    }

    $authorURI = getOrCreateAuthorURI($author);

    if (!$authorURI || !linkAuthorsToPublication($uri, $authorURI)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update authors."
        ]);
        exit;
    }
}

if (!unlinkAllKeywordsFromPublication($uri)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to clear previous keywords."
    ]);
    exit;
}

$allKeywordsLinked = true;

    if (is_array($keywordArray)) {
    foreach ($keywordArray as $keyword) {

        $keyword = trim(strtolower($keyword['value']));

        if ($keyword === '') continue;

        $keywordURI = getOrCreateKeywordURI($keyword);
        if ($keywordURI) {
            linkKeywordsToPublication($uri, $keywordURI);
        } else {
            $allKeywordsLinked = false;
            break; 
        }
    }   
    }
if (!$allKeywordsLinked) {
    echo json_encode([ "status" => "error", "message" => "Failed to update keywords." ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Publication updated successfully. It will be reviewed again by an administrator."
]);
exit;