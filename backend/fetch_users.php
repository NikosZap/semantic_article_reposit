<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json');

$limit = 15;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;

// Συλλογή φίλτρων από POST
$filters = [
    'role'       => $_POST['role'] ?? '',
    'is_active'  => $_POST['is_active'] ?? '',
    'from_date'  => $_POST['from_date'] ?? '',
    'to_date'    => $_POST['to_date'] ?? '',
    'search'     => $_POST['search'] ?? ''
];
$users = getUsers($filters, $offset, $limit);

// Λήψη συνολικού αριθμού
$totalUsers = countUsers($filters);
$totalPages = ceil($totalUsers / $limit);

// Επιστροφή JSON
echo json_encode([
    'users' => $users,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
