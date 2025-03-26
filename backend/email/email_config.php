<?php
require_once dirname(__DIR__,2) . '/config.php';

require_once BASE_PATH . 'includes/PHPMailer/PHPMailer.php';
require_once BASE_PATH . 'includes/PHPMailer/SMTP.php';
require_once BASE_PATH . 'includes/PHPMailer/Exception.php';
require_once BASE_PATH .'includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


// Ρυθμίσεις του Email Server
define("EMAIL_HOST", "smtp.gmail.com");
define("EMAIL_USERNAME", "nikdionzapa@gmail.com");
define("EMAIL_PASSWORD", "vktrdysvvgsbamks");
define("EMAIL_PORT", 587);
define("EMAIL_FROM_NAME", "SemanticApp");
define("EMAIL_FROM_ADDRESS", "nikdionzapa@gmail.com");

// Λειτουργία SMTP
define("EMAIL_SMTP_SECURE", "tsl"); // Ή "ssl" αν χρειάζεται (465)
define("EMAIL_DEBUG_MODE", 0); // 0 = Off, 1 = Client messages, 2 = Client and server messages