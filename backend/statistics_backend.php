<?php

require dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/stats_sparql_queries.php';

header('Content-Type: application/json');

try {

    $data = [
        "summary" => [
            "users" => getTotalUsers(),
            "authors" => getTotalAuthors(),
            "publications" => getTotalPublications(),
            "approved_publications" => getApprovedPublicationsCount(),
            "pending_publications" => getPendingPublicationsCount()
        ],

        "usersByRole" => getUsersByRoleStats(),

        "userRegistrations" => getUsersRegistrationStats(),

        "publicationsPerYear" => getPublicationsPerYearStats(),

        "publicationsByType" => [
            "thesis" => getTotalTheses(),
            "journal_articles" => getTotalJournalArticles(),
            "conference_papers" => getTotalConferencePapers(),
            "books" => getTotalBooks()
        ],

        "authorsStats" => [
        "in_publications" => getAuthorsInPublications(),
        "with_orcid" => getAuthorsWithOrcid(),
        "with_system_access" => getAuthorsWithSystemAccess(),
        "avgAuthorsPerPublication" => getAverageAuthorsPerPublication(),
        "avgPublicationsPerAuthor" => getAveragePublicationsPerAuthor()
        ],

        "claimsStats" => [
        "total" => getTotalClaims(),
        "pending" => getPendingClaimsCount(),
        "approved" => getApprovedClaims(),
        "rejected" => getRejectedClaims(),
        "withClaims" => getPublicationsWithClaims()
        ],

        "publicationTypesChart" => [
        "theses" => getTotalTheses(),
        "journal_articles" => getTotalJournalArticles(),
        "conference_papers" => getTotalConferencePapers(),
        "books" => getTotalBooks()
        ],

        "publicationsPerYearByType" => getPublicationsPerYearByType(),

        "semanticStats" => [
        "total_mentions" => getTotalMentions(),
        "unique_mentions" => getUniqueMentions(),
        "avg_mentions_per_publication" => getAverageMentionsPerPublication(),
        ],

        "topMentionedEntities" => getTopMentionedEntities()

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