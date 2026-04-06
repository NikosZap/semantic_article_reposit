<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

$limit = 15;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;



$pendingPublications =  getPendingPublications($offset, $limit);

$totalPendingPubs = countPendingPublications() ;

$totalPages = ceil($totalPendingPubs / $limit);

echo json_encode([
    'publications' => $pendingPublications,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
