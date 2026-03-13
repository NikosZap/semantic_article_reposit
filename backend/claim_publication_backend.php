<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'backend/email/email_functions.php'; 

if (!$_SESSION['user']['uri']) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$publicationUri = $_POST['pubUri'] ?? '';
$claimedAuthorUri = $_POST['authorUri'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$userUri = $_SESSION['user']['uri'];

if (empty($publicationUri) || empty($claimedAuthorUri) || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

 $result = insertClaimPublicationAuthor($userUri, $claimedAuthorUri, $publicationUri, $reason);

if ($result) {

    $_SESSION['flash_message'] = ['type' => 'success','text' => 'Claim submitted successfully.' ];
    echo json_encode(['status' => 'success', 'message' => 'Claim submitted successfully.', 'redirect' => '../pages/claim_publication.php']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit claim.']);
}
