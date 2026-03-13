<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

$limit = 15;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;



$pendingClaims =  getPendingClaims($offset, $limit);

$totalPendingClaims = countPendingClaims() ;

$totalPages = ceil($totalPendingClaims / $limit);

echo json_encode([
    'claims' => $pendingClaims,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
