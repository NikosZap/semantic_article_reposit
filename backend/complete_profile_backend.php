<?php

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php'; 

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$affiliation = trim($_POST['affiliation'] ?? '');
$scientific_area = trim($_POST['scientific_area'] ?? '');
$firstname = $_SESSION['first_name'];
$lastname =  $_SESSION['last_name'];
$orcid = $_SESSION['orcid_id'];


if (isset($_SESSION['user']['is_logged_in']) && $_SESSION['user']['is_logged_in']) {

    $userURI = $_SESSION['user']['uri'];


    $authorURI = getAuthorURIByOrcid($orcid);

    if (!$authorURI) {
        $authorURI = createAuthorFromOrcid($orcid, $firstname, $lastname);
    }

    linkUserToAuthor($userURI, $authorURI);

    updateAuthorProfile($authorURI, [
        'nationality' => $nationality,
        'affiliation' => $affiliation,
        'scientific_area' => $scientific_area
    ]);
    $_SESSION['user']['orcid'] = $orcid;
    $_SESSION['user']['affiliation'] = $affiliation;
    $_SESSION['user']['nationality'] = $nationality ;
    $_SESSION['user']['scientificArea'] = $scientific_area;

    echo json_encode(['status' => 'success', 'message' => 'ORCID linked successfully.', 'redirect' => '../pages/profile_upd.php']);
    exit;

}
else{

    if (checkUsernameExists($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
        exit;
    }
    
    if (checkEmailExists($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
        exit;
    }

$userID = uniqid();
$author_uri =  "https://orcid.org/" . $orcid;

$data = [
    'orcid_id' => $orcid,
    'username' => $username,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'nationality' => $nationality,
    'affiliation' => $affiliation,
    'scientific_area' => $scientific_area
];

$success = insertAuthorWithUser($author_uri, $userID, $data);

if ($success) {

    session_unset();    
    session_destroy();  
    session_start();

    $_SESSION['user'] = ['id' => $userID,
                          'username' => $username,
                          'role' => 'Researcher',
                          'firstname' => $firstname,
                          'lastname' => $lastname,
                          'email' => $email,
                          'is_logged_in' => true]; 

    echo json_encode(["status" => "success", "redirect" => "../index.php"]);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Insert with Orcid Failed!']);
    exit;
}
}