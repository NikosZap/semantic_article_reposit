<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

$limit = 15;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;


$filters = [
    'type'  => $_POST['type'] ?? '',
    'pubDateFrom'  => $_POST['pubDateFrom'] ?? '',
    'pubDateTo'    => $_POST['pubDateTo'] ?? '',
    'uploadDateFrom'  => $_POST['uploadDateFrom'] ?? '',
    'uploadDateTo'    => $_POST['uploadDateTo'] ?? '',
    'search'     => $_POST['search'] ?? ''
]; 

$publications =  getPublications($filters, $offset, $limit);

$totalPubs = countPublications($filters) ;

$totalPages = ceil($totalPubs / $limit);

echo json_encode([
    'publications' => $publications,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
