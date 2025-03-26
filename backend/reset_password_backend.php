<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        //header("Location: ../pages/reset_password.php?token=$token&error=Passwords do not match!");
        echo json_encode(["status" => "error","message" => "Passwords do not match!"]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if (updatePasswordByToken($hashedPassword, $token)) {
        echo json_encode(["status" => "success","redirect" => "../pages/login.php?success=Your password has been successfully changed! Log in with the new password."]);
        exit;
    } else {
        //header("Location: ../pages/reset_password.php?token=$token&error=Invalid or expired token!");
        echo json_encode(["status" => "error", "message" => "Token is invalid or expired."]);
        exit;
    }
}
?>