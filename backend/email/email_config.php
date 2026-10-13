<?php
require_once dirname(__DIR__,2) . '/config.php';

require_once BASE_PATH . 'includes/PHPMailer/PHPMailer.php';
require_once BASE_PATH . 'includes/PHPMailer/SMTP.php';
require_once BASE_PATH . 'includes/PHPMailer/Exception.php';
require_once BASE_PATH .'includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


// Email Server Configuration

define("EMAIL_HOST", "smtp.gmail.com");
define("EMAIL_USERNAME", "semanticresearchexplorer@gmail.com");
define("EMAIL_PASSWORD", "ydekxrmpdwzxibub");
define("EMAIL_PORT", 587);
define("EMAIL_FROM_NAME", "Semantic Research Explorer Team");
define("EMAIL_FROM_ADDRESS", "semanticresearchexplorer@gmail.com");


// SMTP
define("EMAIL_SMTP_SECURE", "tsl"); // ssl (465)
define("EMAIL_DEBUG_MODE", 0);