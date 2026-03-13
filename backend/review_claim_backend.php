<?php
require_once  dirname(__DIR__) .'/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'backend/email/email_functions.php';

header('Content-Type: application/json; charset=UTF-8');
ob_clean(); 

if ($_SESSION['user']['role'] !== 'Admin') {
    
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $claimUri = trim($_POST['claim_uri'] ?? '');
    
    $userEmail = $_POST['userEmail'];
    $pubTitle = $_POST['pubTitle'];
  
    $userFullname = $_POST['userFirstname'] . ' ' . $_POST['userLastname'];

    if($action === 'reject'){

        $rejectReason = trim($_POST['reject_reason'] ?? '');
        
        if (updateClaimStatus($claimUri, 'Rejected')) {

            if (sendClaimRejectionEmail($userEmail, $userFullname, $pubTitle, $rejectReason)) {
                $_SESSION['flash_message'] = ['status' => 'success', 'message' => 'Claim rejected successfully. Notification email sent to the user.'];
                echo json_encode(['status' => 'success', 'message' => 'Claim rejected successfully. Notification email sent to the user.' ]);
                exit;
            } else {
                echo json_encode(['status' => 'warning','message' => 'Claim rejected successfully, but the notification email could not be sent.' ]);
                exit;
            }

        } else {
            echo json_encode(['status' => 'error','message' => 'Failed to update claim status. The claim was not rejected.']);
            exit;
        }
    
    }
    elseif($action === 'approve'){

        $publicationURI = $_POST['publication'];
        $oldAuthorURI = $_POST['oldAuthorURI'];
        $realAuthorURI =  $_POST['realAuthorURI'];

        if (changePublicationAuthor($publicationURI, $oldAuthorURI, $realAuthorURI)) {

            if (updateClaimStatus($claimUri, 'Approved')) {

                if(sendClaimApprovalEmail($userEmail, $userFullname, $pubTitle)){
                    $_SESSION['flash_message'] = ['status' => 'success', 'message' => 'Claim approved successfully. Notification email sent to the user.'];
                    echo json_encode(['status' => 'success', 'message' => 'Claim approved successfully. Notification email sent to the user.' ]);
                    exit;
                } else {
                    echo json_encode(['status' => 'warning','message' => 'Claim approved successfully, but the notification email could not be sent.' ]);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error','message' => 'Failed to update claim status. The claim was not rejected.']);
                exit;
            }    
        }
    }
}

?>