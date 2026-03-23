<?php

require dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/stats_sparql_queries.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['uri'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User session not found.'
    ]);
    exit;
}

$userUri = $_SESSION['user']['uri'];

try {

    $data = [
        'summary' => getUserPublicationSummaryStats($userUri),

        'type' => getUserApprovedPublicationsByType($userUri),

        'pie' => getUserApprovedPublicationsByType($userUri),

        'publicationsPerYearByType' => getUserApprovedPublicationsPerYearByType($userUri),
       
        'mentions' => [ 'total' => getUserTotalMentions($userUri),
                        'unique' => getUserUniqueMentions($userUri),
                        'averagePerPublication' => getUserAverageMentionsPerPublication($userUri),
                        'topEntities' => getUserTopMentionedEntities($userUri)  ],
        
        'claims' => [ 'status' => getUserClaimsStats($userUri),
                       'pubs' => getUserPublicationsWithClaims($userUri) ]

    ];

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}


