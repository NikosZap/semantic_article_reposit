<?php
require dirname(__DIR__) . '/config.php';
require BASE_PATH . 'includes/sparql_queries.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $user = getUserByActivationToken($token);
    echo $user['uri'];

    if ($user) {
        $userURI = $user['uri'];

        if (activateUser($userURI, $token)) {
            header("Location: ../pages/login.php?success=The account has been activated! You can log in.");
            exit;
        } else {
            header("Location: ../pages/login.php?error=Activation failed!");
            exit;
        }
    } else {
        header("Location: ../pages/login.php?error=Invalid or expired token!");
        exit;
    }
} else {
    header("Location: ../pages/login.php?error=No confirmation token is provided!");
    exit;
}
?>