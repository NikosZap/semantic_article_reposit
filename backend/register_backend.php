<?php
require dirname(__DIR__) . '/config.php';
require BASE_PATH . 'includes/sparql_queries.php';
require BASE_PATH . 'backend/email/email_functions.php';

header('Content-Type: application/json; charset=UTF-8');
ob_clean(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];

    // Έλεγχος αν οι κωδικοί ταιριάζουν
    if ($password !== $confirm_password) {
        echo json_encode(["status" => "error", "message" => "Passwords do not match!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

      // Έλεγχος αν υπάρχει ήδη χρήστης με το ίδιο username ή email
      if (getUserByUsernameOrEmail($username, $email)) {
        echo json_encode(["status" => "error", "message" => "Username or email already exists!"], JSON_UNESCAPED_UNICODE);
        exit;
    }
      
    // password hashing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $activation_token = bin2hex(random_bytes(16));

     // Εισαγωγή χρήστη μέσω της `registerUser()`
     if (registerUser($username, $email, $hashedPassword, $firstname, $lastname, $activation_token)) {
        
        $activation_link = BASE_URL . "pages/verify.php?token=" . $activation_token;

        if (sendVerificationEmail($email, "$firstname $lastname", $activation_link)) {
            echo json_encode(["status" => "success", "message" => "Please check your email for confirmation!"], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send a confirmation email!"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error during registration!"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>