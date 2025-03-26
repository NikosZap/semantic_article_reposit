<?php
require dirname(__DIR__) . '/config.php';
require BASE_PATH . '/includes/sparql_queries.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = getUserByUsername($username);
        

    if ($user) {

        $hashedPassword = $user['password'];

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user'] = ['id' => $user['userID'],
                                 'username' => $user['username'],
                                 'role' => $user['role'],
                                 'firstname' => $user['firstname'],
                                 'lastname' => $user['lastname'],
                                 'email' => $user['email'],
                                 'is_logged_in' => true]; 

            echo json_encode(["status" => "success", "redirect" => BASE_URL . "pages/dashboard.php"]);
            exit;
        } else {

            echo json_encode(["status" => "error", "message" => "Incorrect password!"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found!"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>