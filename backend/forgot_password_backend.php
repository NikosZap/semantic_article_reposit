<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';
require_once BASE_PATH . 'backend/email/email_functions.php';

header('Content-Type: application/json; charset=UTF-8');
ob_clean();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $userUri = getUserUriByEmail($email);
    if ($userUri) {
        $reset_token = bin2hex(random_bytes(32));
        $reset_link = BASE_URL . "pages/reset_password.php?token=" . $reset_token;

        if (createResetToken($userUri, $reset_token)) {

            if (sendResetPasswordEmail($email, "User", $reset_link)) {
                echo json_encode(["status" => "success", "message" => "A reset link has been sent to your email."]);
                exit;
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to send email!"]);
                exit;
            }
        }
    }

    echo json_encode(["status" => "error", "message" => "No user found with this email!"]);
    exit;
}
?>