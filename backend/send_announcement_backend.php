<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'backend/email/email_config.php';
require_once BASE_PATH . 'backend/email/email_functions.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json; charset=UTF-8');
print_r($_POST); // Δες τα δεδομένα που φτάνουν στο backend
print_r(error_get_last()); // Δες αν υπάρχουν σφάλματα
$output = ob_get_clean();

// Έλεγχος αν ο admin είναι συνδεδεμένος
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access!"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Λήψη του μηνύματος
$message = isset($_POST['message']) ? trim($_POST['message']) : "";

if (empty($message)) {
    echo json_encode(["status" => "error", "message" => "The message cannot be blank!"], JSON_UNESCAPED_UNICODE);
    exit;
}

$filters = [
    "search" => $_POST["search"] ?? '',
    "role" => $_POST["role"] ?? '',
    "is_active" => $_POST["is_active"] ?? '',
    "from_date" => $_POST["from_date"] ?? '',
    "to_date" => $_POST["to_date"] ?? ''
];


$users = getUsers($filters,0,countUsers($filters));

if (empty($users)) {
    echo json_encode(["status" => "error", "message" => "No users matched the criteria."]);
    exit;
}

$subject = "Announcement";
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(["status" => "error", "message" => "Empty announcement message."]);
    exit;
}

$successCount = 0;
foreach ($users as $user) {
    $email = $user['email'];
    $fullName = $user['firstname'] . ' ' . $user['lastname'];

    $subject = "Announcement from the Administrator";
    $body = $message;

    if (send_email($email, $fullName, $subject, $body)) {
        $successCount++;
    }
}
echo json_encode(["status" => "success", "message" => "The email was sent to $successCount users!"], JSON_UNESCAPED_UNICODE);
exit;
?>
