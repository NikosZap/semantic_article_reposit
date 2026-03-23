<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !$_SESSION['user']['is_logged_in']) {
    echo json_encode(["status" => "error", "message" => "Not authorized"]);
    exit;
}

$action = $_POST['action'] ?? null;
$userUri = $_SESSION['user']['uri'];

if ($action === "saveEditedAccount") {
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';

    $scientificArea = $_POST['scientificArea'] ?? '';
    $affiliation = $_POST['affiliation'] ?? '';
    $nationality = $_POST['nationality'] ?? '';

    if (!$email || !$firstname || !$lastname) {
        echo json_encode(["status" => "error", "message" => "Please fill all fields"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }

    $userUpdated = updateUserProfile($userUri, $email, $firstname, $lastname);
    if (!$userUpdated) {
        echo json_encode(["status" => "error", "message" => "User profile update failed"]);
        exit;
    }
     $authorUpdated = true;

    if (!empty($_SESSION['user']['orcid'])) {
        $authorUpdated = updateAuthorProfileFieldsByUserUri( $userUri, $nationality, $affiliation, $scientificArea );
    }

    if (!$authorUpdated) {
        echo json_encode(["status" => "error", "message" => "Author profile update failed"]);
        exit;
    }

    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['firstname'] = $firstname;
    $_SESSION['user']['lastname'] = $lastname;

    if (!empty($_SESSION['user']['orcid'])) {
        $_SESSION['user']['nationality'] = $nationality;
        $_SESSION['user']['affiliation'] = $affiliation;
        $_SESSION['user']['scientificArea'] = $scientificArea;
    }

    echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    exit;

}

if ($action === "deleteAccount") {
    $result = deleteUserByURI($userUri);

    if ($result) {
        session_destroy();
        echo json_encode(["status" => "success", "message" => "Your account has been deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete account"]);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit;
