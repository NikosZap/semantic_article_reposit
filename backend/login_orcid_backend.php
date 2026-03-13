<?php

require_once dirname(__DIR__) . '/config.php';

$client_id = 'APP-CVZ1AZXUZ8XWB81T'; 
$redirect_uri = BASE_URL . 'backend/orcid_callback.php';
$scope = '/authenticate'; 
$response_type = 'code';


$query_params = http_build_query([
    'client_id' => $client_id,
    'response_type' => $response_type,
    'scope' => $scope,
    'redirect_uri' => $redirect_uri
]);

$auth_url = 'https://sandbox.orcid.org/oauth/authorize?' . $query_params;


header("Location: $auth_url");
exit;
?>