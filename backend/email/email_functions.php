<?php
require_once   dirname(__DIR__) . '/email/email_config.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    die("Η κλάση PHPMailer δεν βρέθηκε!");
}

function sendVerificationEmail($toEmail, $toName, $activationLink) {
    $mail = new PHPMailer(true);
    try {
        // Ρυθμίσεις SMTP
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;
        $mail->Port = EMAIL_PORT;
        $mail->SMTPDebug = EMAIL_DEBUG_MODE;
        

        // Πληροφορίες αποστολέα
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Περιεχόμενο email
        $mail->isHTML(true);
        $mail->Subject = 'Semantic App Subscription Confirmation';
        $mail->Body = "Welcome to the Semantic article repository!<br>
                       Click on the link below to activate your account:<br>
                       <a href='$activationLink'>Account Activation</a>";

        
        $mail->Debugoutput = 'html';
        $mail->send();

        $mail->smtpClose();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        echo "Email sending error: " . $mail->ErrorInfo; // Εμφάνιση του σφάλματος στην οθόνη
        return false;
    }
}

function sendResetPasswordEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;
        $mail->Port = EMAIL_PORT;

        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Password reset ";
        $mail->Body = "<p>Click <a href='$resetLink'>here</a> to reset your password</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error (Reset Password): " . $mail->ErrorInfo);
        return false;
    }
}

function send_email($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Ρύθμιση SMTP
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST; 
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME; 
        $mail->Password = EMAIL_PASSWORD; 
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;
        $mail->Port = EMAIL_PORT;; 

        // Ρύθμιση αποστολέα
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to, $name); // Προσθήκη παραλήπτη
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;

        // Αποστολή email
        if ($mail->send()) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        error_log("Email sending error: " . $mail->ErrorInfo); // Καταγραφή λάθους
        return false;
    }
}

function sendPublicationApprovalEmail($toEmail, $toName, $title) {
    $mail = new PHPMailer(true);
    try {
        setupMailer($mail, $toEmail, $toName);
        $mail->Subject = 'Your publication has been approved!';
        $mail->Body = "Dear $toName,<br><br>Your publication titled <strong>\"$title\"</strong> has been approved and is now live in the system.<br><br>Thank you!";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Approval Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
function sendPublicationRejectionEmail($toEmail, $toName, $title, $reason) {
    $mail = new PHPMailer(true);
    try {
        setupMailer($mail, $toEmail, $toName);
        $mail->Subject = 'Your publication has been rejected';
        $mail->Body = "Dear $toName,<br><br>Your publication titled <strong>\"$title\"</strong> has been rejected.<br><br><strong>Reason:</strong><br>$reason<br><br>Feel free to contact us for clarification.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPublicationDeletionEmail($toEmail, $toName, $title) {
    $mail = new PHPMailer(true);
    try {
        setupMailer($mail, $toEmail, $toName);
        $mail->Subject = 'Your publication has been deleted';
        $mail->Body = "Dear $toName,<br><br>Your publication titled <strong>\"$title\"</strong> has been deleted.<br><br>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Deletion Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

function setupMailer($mail, $toEmail, $toName) {
    $mail->isSMTP();
    $mail->Host = EMAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = EMAIL_USERNAME;
    $mail->Password = EMAIL_PASSWORD;
    $mail->SMTPSecure = EMAIL_SMTP_SECURE;
    $mail->Port = EMAIL_PORT;
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
}

function sendClaimRejectionEmail($toEmail, $toName, $title, $reason) {
    $mail = new PHPMailer(true);
    try {
        setupMailer($mail, $toEmail, $toName);
        $mail->Subject = 'Your claim has been rejected';
        $mail->Body = "Dear $toName,<br><br>Your claim of the publication titled <strong>\"$title\"</strong> has been <u>rejected</u>.<br><br><strong>Reason:</strong><br>$reason<br>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendClaimApprovalEmail($toEmail, $toName, $title) {
    $mail = new PHPMailer(true);
    try {
        setupMailer($mail, $toEmail, $toName);
        $mail->Subject = 'Your claim has been approved';
        $mail->Body = "Dear $toName,<br><br>Your claim of the publication titled <strong>\"$title\"</strong> has been <u>approved</u>.<br><br>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

?>