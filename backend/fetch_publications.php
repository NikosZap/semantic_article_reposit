<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

$limit = 15;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;

$userRole = $_SESSION['user']['role'];
$userURI  = $_SESSION['user']['uri']; 

$onlyMine = $_POST['onlyMine'] ?? false;


$filters = [
    'search' => $_POST['search'] ?? '',
    'type' => $_POST['type'] ?? '',
    'pubDateFrom' => $_POST['pubDateFrom'] ?? '',
    'pubDateTo' => $_POST['pubDateTo'] ?? '',
    'uploadDateFrom' => $_POST['uploadDateFrom'] ?? '',
    'uploadDateTo' => $_POST['uploadDateTo'] ?? ''
];

$onlyMine = $_POST['onlyMine'] ?? 'false';
$userRole = $_SESSION['user']['role'] ?? '';
$userUri  = $_SESSION['user']['uri'] ?? '';

if ($userRole === 'Researcher') {
    $filters['uploadedBy'] = $userUri;
} elseif ($userRole === 'Admin' && $onlyMine === 'true') {
    $filters['uploadedBy'] = $userUri;
}

$publications =  getPublications($filters, $offset, $limit);

$totalPubs = countPublications($filters) ;

$totalPages = ceil($totalPubs / $limit);

echo json_encode([
    'publications' => $publications,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
