<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']['is_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$uri = $_POST['uri'] ?? '';

if (empty($uri)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing publication URI']);
    exit;
}

$userID = $_SESSION['user']['userID'] ?? '';
$userUri = getUserUriByUserId($userID);

$isAdmin = (trim($_SESSION['user']['role'] ?? '') === 'Admin');
$isOwner = $userUri ? isPublicationOwnedByUser($uri, $userUri) : false;

if (!$isAdmin && !$isOwner) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action']);
    exit;
}

if ($action === 'delete_publication') {

    if (!$isAdmin && !$isOwner) {
        echo json_encode(['status' => 'error', 'message' => 'Not allowed']);
        exit;
    }

    $deleted = deletePublication($uri);

    if ($deleted) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Publication deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete publication.'
        ]);
    }

    exit;
}

if ($action === 'update_publication') {

    if (!$isOwner) {
        echo json_encode(['status' => 'error', 'message' => 'Only owner can update']);
        exit;
    }

    $title = $_POST['title'] ?? '';
    $doi = $_POST['doi'] ?? '';

    $updated = updatePublication($uri, $title, $doi);

    if (!$updated) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update publication.'
        ]);
        exit;
    }


    resetPublicationApproval($uri);

    removePublicationFullText($uri);
    deletePublicationFromSolr($uri);

    echo json_encode([
        'status' => 'success',
        'message' => 'Publication updated and sent for review.'
    ]);

    exit;
}

