<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . '/includes/sparql_queries.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_GET['code'])) {
    die('Authorization code not returned by ORCID.');
}

$code = $_GET['code'];


$client_id = 'APP-CVZ1AZXUZ8XWB81T';         
$client_secret = '8a147c23-0971-41a4-adda-3c175f4e0bca';
$redirect_uri = BASE_URL . 'backend/orcid_callback.php'; 


$token_url = 'https://sandbox.orcid.org/oauth/token';

$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);


if (!isset($data['access_token']) || !isset($data['orcid'])) {
    die('Failed to retrieve access token or ORCID iD. Response: ' . $response);
}

$orcid_id = $data['orcid'];
$access_token = $data['access_token'];



$user = getUserByOrcidId($orcid_id);

if ($user) {
    
    session_unset();    
    session_destroy();  
    session_start(); 
    
    $_SESSION['user'] = [
        'uri' => $user['uri'],
        'id' => $user['userID'],
        'username' => $user['username'],
        'role' => $user['role'],
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'email' => $user['email'],
        'is_logged_in' => true,
        'orcid' => $user['orcid']
    ];
    header("Location: ". BASE_URL . "index.php"); 
    exit;
} else {
    // if orcid is new
    $_SESSION['orcid_id'] = $orcid_id;
    $_SESSION['access_token'] = $access_token;
    header("Location: ". BASE_URL . "pages/complete_profile.php");
    
}

// Call ORCID API to get personal data
$api_url = "https://pub.sandbox.orcid.org/v3.0/$orcid_id/person";
$employment_url = "https://pub.sandbox.orcid.org/v3.0/$orcid_id/employments";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Accept: application/json"
]);

$api_response = curl_exec($ch);
curl_close($ch);



$person_data = json_decode($api_response, true);

$given_name = $person_data['name']['given-names']['value'] ?? '';
$family_name = $person_data['name']['family-name']['value'] ?? '';



$ch = curl_init($employment_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Accept: application/json"
]);
$emp_response = curl_exec($ch);
curl_close($ch);

$employment_data = json_decode($emp_response, true);

$affiliation = '';
if (!empty($employment_data['affiliation-group'][0]['summaries'][0]['employment-summary']['organization']['name'])) {
    $affiliation = $employment_data['affiliation-group'][0]['summaries'][0]['employment-summary']['organization']['name'];
}

$_SESSION['first_name'] = $given_name;
$_SESSION['last_name'] = $family_name;
$_SESSION['affiliation'] = $affiliation;
