<?php
require_once  dirname(__DIR__) .'/config.php';
require_once BASE_PATH .'includes/sparql_queries.php';

header('Content-Type: application/json; charset=UTF-8');
ob_clean(); 

if ($_SESSION['user']['role'] !== 'Admin') {
    
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userID = $_POST['userID'];
    $action = $_POST['action'];

    if ($action === 'update_role') {
        $role = $_POST['role'];
        $userURI = getUserUriById($userID);
        if (!$userURI) {
            echo json_encode(["status" => "error", "message" => "User not found"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (updateUserRole($userURI, $role)) {
            echo json_encode(["status" => "success", "message" => "Role successfully updated"], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "Role update failure"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'delete_user') {

        $userURI = getUserUriById($userID);
         
        if (!$userURI) {
            echo json_encode(["status" => "error", "message" => "User not found"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ( deleteUser($userURI)) {
            echo json_encode(["status" => "success", "message" => "User successfully deleted"], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete user"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if ($action === 'toggle_suspend_user'){
      
        $suspend = isset($_POST['suspend']) ? (bool)$_POST['suspend'] : false;
        $userURI = getUserUriById($userID);
         
        if (!$userURI) {
            echo json_encode(["status" => "error", "message" => "User not found"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ( updateUserSuspension($userURI, $suspend) ) {

            $message = $suspend ? "User suspended" : "User's suspension has been lifted";
            echo json_encode(["status" => "success", "message" => "User successfully suspended"], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to suspend / lift suspension user"], JSON_UNESCAPED_UNICODE);
            exit;
        }

    }
    
    exit;
}
?>