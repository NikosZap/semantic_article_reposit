<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['user']['is_logged_in'])) {
    header("Location: ". BASE_URL . "pages/login.php?error=Unauthorized access!");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_POST['userID'];
    $username = $_SESSION['user']['username']; 
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];

    $userURI = getUserURIByUserID($userID);
    if (!$userURI) {
        echo json_encode(["status" => "error", "message" => "User not found!"]);
        exit;
    }

    if (checkExistingEmail($email, $userURI)) {
        echo json_encode(["status" => "error", "message" => "Email already exists!"]);
        exit;
    }

    
    $success = updateUserProfile($userURI, $email, $firstname, $lastname);

    if ($success) {

        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['firstname'] = $firstname;
        $_SESSION['user']['lastname'] = $lastname;

        echo json_encode(["status" => "success", "message" => "Your profile was successfully updated!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Profile update error!"]);
    }
    exit;
}
?>
