<?php
require_once dirname(__DIR__) . '/config.php';

function getTotalUsers() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?user) AS ?count)
        WHERE {
            ?user a app:User .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getTotalAuthors() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?author) AS ?count)
        WHERE {
            ?author a app:Author .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getTotalPublications() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a ?type .
            FILTER(?type IN (
                app:Publication,
                app:Thesis,
                app:Article,
                app:ConferencePaper,
                app:Book,
                app:BookChapter
            ))
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getApprovedPublicationsCount() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?pub a ?type ;
                 app:isApproved true .

            FILTER(?type IN (
                app:Publication,
                app:Thesis,
                app:Article,
                app:ConferencePaper,
                app:Book,
                app:BookChapter
            ))
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getPendingPublicationsCount() {

    $sparql = "
         PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?pub a ?type ;
                 app:isApproved false .

            FILTER(?type IN (
                app:Publication,
                app:Thesis,
                app:Article,
                app:ConferencePaper,
                app:Book
            ))
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getUsersByRoleStats() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?role (COUNT(?user) AS ?count)
        WHERE {
            ?user a app:User ;
                  app:role ?role .
        }
        GROUP BY ?role
    ";

    $result = executeSparqlQuery($sparql);
    $roles = [
        "Admin" => 0,
        "Researcher" => 0
    ];

    foreach ($result['results']['bindings'] as $row) {
        $role = $row['role']['value'];
        $count = (int)$row['count']['value'];

        $roles[$role] = $count;
    }

    return $roles;
}
function getPublicationsPerYearStats() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT ?year (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a app:Publication ;
                 app:publicationDate ?date .

            BIND(YEAR(?date) AS ?year)
        }
        GROUP BY ?year
        ORDER BY ?year
    ";

    $result = executeSparqlQuery($sparql);
    $data = [];

    if (!is_array($result)) {
        return $data;
    }

    foreach ($result as $row) {
        $year  = $row['year']['value']  ?? null;
        $count = $row['count']['value'] ?? null;

        if ($year !== null && $count !== null) {
            $data[$year] = (int)$count;
        }
    }

    return $data;
}

function getTotalTheses() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a app:Thesis .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getTotalJournalArticles() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a app:Article .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getTotalConferencePapers() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a app:ConferencePaper .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getTotalBooks() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a app:Book .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getAuthorsInPublications() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?author) AS ?count)
        WHERE {
            ?pub app:hasAuthor ?author .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getAuthorsWithOrcid() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?author) AS ?count)
        WHERE {
            ?author a app:Author ;
                    app:orcid ?orcid .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getAuthorsWithSystemAccess() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?author) AS ?count)
        WHERE {
            ?author app:correspondTo ?user .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getAverageAuthorsPerPublication() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (AVG(?authorCount) AS ?avgAuthors)
        WHERE {
            SELECT ?pub (COUNT(?author) AS ?authorCount)
            WHERE {
                ?pub app:hasAuthor ?author .
            }
            GROUP BY ?pub
        }
    ";

    $result = executeSparqlQuery($sparql);

    return round((float)($result['results']['bindings'][0]['avgAuthors']['value'] ?? 0), 2);
}

function getAveragePublicationsPerAuthor() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (AVG(?pubCount) AS ?avgPublications)
        WHERE {
            SELECT ?author (COUNT(?pub) AS ?pubCount)
            WHERE {
                ?pub app:hasAuthor ?author .
            }
            GROUP BY ?author
        }
    ";

    $result = executeSparqlQuery($sparql);

    return round((float)($result['results']['bindings'][0]['avgPublications']['value'] ?? 0), 2);
}

function getTotalClaims() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?claim) AS ?count)
        WHERE {
            ?claim a app:Claim .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getPendingClaimsCount() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?claim) AS ?count)
        WHERE {
            ?claim a app:Claim ;
                   app:claimStatus \"Pending\" .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getApprovedClaims() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?claim) AS ?count)
        WHERE {
            ?claim a app:Claim ;
                   app:claimStatus \"Approved\" .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getRejectedClaims() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?claim) AS ?count)
        WHERE {
            ?claim a app:Claim ;
                   app:claimStatus \"Rejected\" .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getPublicationsWithClaims() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?claim a app:Claim ;
                   app:isClaimOf ?pub .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getPublicationsPerYearByType() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT ?year ?typeLabel (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?pub a ?type ;
                 app:uploadedDate ?date .

            VALUES (?type ?typeLabel) {
                (app:Thesis \"Thesis\")
                (app:Article \"Journal Article\")
                (app:ConferencePaper \"Conference Paper\")
                (app:Book \"Book\")
            }

            BIND(YEAR(?date) AS ?year)
        }
        GROUP BY ?year ?typeLabel
        ORDER BY ?year ?typeLabel
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $year = $row['year']['value'] ?? null;
        $type = $row['typeLabel']['value'] ?? null;
        $count = (int)($row['count']['value'] ?? 0);

        if (!$year || !$type) {
            continue;
        }

        if (!isset($data[$year])) {
            $data[$year] = [
                'Thesis' => 0,
                'Journal Article' => 0,
                'Conference Paper' => 0,
                'Book' => 0
            ];
        }

        $data[$year][$type] = $count;
    }

    ksort($data);

    return $data;
}

function getUsersRegistrationStats() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT ?year ?month (COUNT(?user) AS ?count)
        WHERE {
            ?user a app:User ;
                  app:createdAt ?created .

            BIND(YEAR(?created) AS ?year)
            BIND(MONTH(?created) AS ?month)
        }
        GROUP BY ?year ?month
        ORDER BY ?year ?month
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $stats = [];

    foreach ($rows as $row) {
        $year = $row['year']['value'] ?? null;
        $month = $row['month']['value'] ?? null;
        $count = $row['count']['value'] ?? null;

        if ($year === null || $month === null || $count === null) {
            continue;
        }

        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $label = $year . '-' . $month;

        $stats[$label] = (int)$count;
    }

    return $stats;
}

function getTotalMentions() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?mention) AS ?count)
        WHERE {
            ?publication app:mentions ?mention .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getUniqueMentions() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?mention) AS ?count)
        WHERE {
            ?publication app:mentions ?mention .
        }
    ";

    $result = executeSparqlQuery($sparql);

    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getAverageMentionsPerPublication() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (AVG(?m) AS ?avgMentions)
        WHERE {

          SELECT ?publication (COUNT(?mention) AS ?m)
          WHERE {
             ?publication app:mentions ?mention .
          }
          GROUP BY ?publication

        }
    ";

    $result = executeSparqlQuery($sparql);

    return round((float)($result['results']['bindings'][0]['avgMentions']['value'] ?? 0), 2);
}

function getTopMentionedEntities() {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?mention (COUNT(?publication) AS ?count)
        WHERE {
            ?publication app:mentions ?mention .
        }
        GROUP BY ?mention
        ORDER BY DESC(?count)
        LIMIT 10
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $mention = $row['mention']['value'] ?? null;
        $count = (int)($row['count']['value'] ?? 0);

        if (!$mention) {
            continue;
        }

        $label = $mention;

        if (str_contains($mention, '#')) {
            $parts = explode('#', $mention);
            $label = end($parts);
        } elseif (str_contains($mention, '/')) {
            $parts = explode('/', rtrim($mention, '/'));
            $label = end($parts);
        }

        $label = str_replace('_', ' ', $label);

        $data[] = [
            'label' => $label,
            'count' => $count
        ];
    }

    return $data;
}

function getUserPublicationStats($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT 
            (COUNT(?pub) AS ?total)
            (SUM(IF(?isApproved = true, 1, 0)) AS ?approved)
            (SUM(IF(?isApproved = false, 1, 0)) AS ?pending)
        WHERE {
            ?pub app:hasUploaded <$userUri> .

            OPTIONAL { ?pub app:isApproved ?isApproved . }
        }
    ";

    $result = executeSparqlQuery($sparql);
    $row = $result['results']['bindings'][0] ?? [];

    return [
        'total' => (int)($row['total']['value'] ?? 0),
        'approved' => (int)($row['approved']['value'] ?? 0),
        'pending' => (int)($row['pending']['value'] ?? 0)
    ];
}

function getAuthorPublicationCount($orcid) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?pub) AS ?count)
        WHERE {
            ?author app:orcid \"$orcid\" .
            ?author app:isAuthorOf ?pub .
        }
    ";

    $result = executeSparqlQuery($sparql);
    $row = $result['results']['bindings'][0] ?? [];

    return (int)($row['count']['value'] ?? 0);
}

function getUserPublicationSummaryStats($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT
            (COUNT(DISTINCT ?pub) AS ?total)
            (COUNT(DISTINCT ?approvedPub) AS ?approved)
            (COUNT(DISTINCT ?pendingPub) AS ?pending)
        WHERE {
            OPTIONAL {
                ?pub app:hasUploaded <$userUri> .
            }

            OPTIONAL {
                ?approvedPub app:hasUploaded <$userUri> ;
                             app:isApproved true .
            }

            OPTIONAL {
                ?pendingPub app:hasUploaded <$userUri> ;
                            app:isApproved false .
            }
        }
    ";

    $result = executeSparqlQuery($sparql);
    $row = $result['results']['bindings'][0] ?? [];

    return [
        'total' => (int)($row['total']['value'] ?? 0),
        'approved' => (int)($row['approved']['value'] ?? 0),
        'pending' => (int)($row['pending']['value'] ?? 0)
    ];
}
function getUserApprovedPublicationsByType($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?type (COUNT(?pub) AS ?count)
        WHERE {
            ?pub a ?type ;
                 app:hasUploaded <$userUri> ;
                 app:isApproved true .
        }
        GROUP BY ?type
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [
        'Thesis' => 0,
        'Article' => 0,
        'ConferencePaper' => 0,
        'Book' => 0
    ];

    foreach ($rows as $row) {
        $typeUri = $row['type']['value'];
        $count = (int)$row['count']['value'];

        // παίρνουμε μόνο το local name
        $type = '';

        if (str_contains($typeUri, '#')) {
            $parts = explode('#', $typeUri);
            $type = end($parts);
        }

        if (isset($data[$type])) {
            $data[$type] = $count;
        }
    }

    return $data;
}

function getUserApprovedPublicationsPerYearByType($userUri) {

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT ?year ?typeLabel (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?pub a ?type ;
                 app:uploadedDate ?date ;
                 app:hasUploaded <$userUri> ;
                 app:isApproved true .

            VALUES (?type ?typeLabel) {
                (app:Thesis \"Thesis\")
                (app:Article \"Journal Article\")
                (app:ConferencePaper \"Conference Paper\")
                (app:Book \"Book\")
            }

            BIND(YEAR(?date) AS ?year)
        }
        GROUP BY ?year ?typeLabel
        ORDER BY ?year ?typeLabel
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $year = $row['year']['value'] ?? null;
        $type = $row['typeLabel']['value'] ?? null;
        $count = (int)($row['count']['value'] ?? 0);

        if (!$year || !$type) {
            continue;
        }

        if (!isset($data[$year])) {
            $data[$year] = [
                'Thesis' => 0,
                'Journal Article' => 0,
                'Conference Paper' => 0,
                'Book' => 0
            ];
        }

        $data[$year][$type] = $count;
    }

    ksort($data);

    return $data;
}

function getUserTotalMentions($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?mention) AS ?count)
        WHERE {
            ?pub app:hasUploaded <$userUri> ;
                 app:mentions ?mention .
        }
    ";

    $result = executeSparqlQuery($sparql);
    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getUserUniqueMentions($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?mention) AS ?count)
        WHERE {
            ?pub app:hasUploaded <$userUri> ;
                 app:mentions ?mention .
        }
    ";

    $result = executeSparqlQuery($sparql);
    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

function getUserAverageMentionsPerPublication($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(?mention) AS ?totalMentions) (COUNT(DISTINCT ?pub) AS ?totalPubs)
        WHERE {
            ?pub app:hasUploaded <$userUri> ;
                 app:mentions ?mention .
        }
    ";

    $result = executeSparqlQuery($sparql);
    $row = $result['results']['bindings'][0] ?? [];

    $mentions = (int)($row['totalMentions']['value'] ?? 0);
    $pubs = (int)($row['totalPubs']['value'] ?? 0);

    if ($pubs === 0) {
        return 0;
    }

    return round($mentions / $pubs, 2);
}

function getUserTopMentionedEntities($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?mention (COUNT(?pub) AS ?count)
        WHERE {
            ?pub app:hasUploaded <$userUri> ;
                 app:mentions ?mention .
        }
        GROUP BY ?mention
        ORDER BY DESC(?count)
        LIMIT 10
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $mention = $row['mention']['value'] ?? null;
        $count = (int)($row['count']['value'] ?? 0);

        if (!$mention) {
            continue;
        }

        $label = $mention;

        if (str_contains($mention, '#')) {
            $parts = explode('#', $mention);
            $label = end($parts);
        } elseif (str_contains($mention, '/')) {
            $parts = explode('/', rtrim($mention, '/'));
            $label = end($parts);
        }

        $label = str_replace('_', ' ', $label);

        $data[] = [
            'label' => $label,
            'count' => $count
        ];
    }

    return $data;
}

function getUserClaimsStats($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT 
            (COUNT(DISTINCT ?claim) AS ?total)
            (COUNT(DISTINCT ?pendingClaim) AS ?pending)
            (COUNT(DISTINCT ?approvedClaim) AS ?approved)
            (COUNT(DISTINCT ?rejectedClaim) AS ?rejected)
        WHERE {
            OPTIONAL {
                ?pub app:hasUploaded <$userUri> .
                ?claim app:claimsPublication ?pub .
            }

            OPTIONAL {
                ?pub app:hasUploaded <$userUri> .
                ?pendingClaim app:claimsPublication ?pub ;
                              app:status \"pending\" .
            }

            OPTIONAL {
                ?pub app:hasUploaded <$userUri> .
                ?approvedClaim app:claimsPublication ?pub ;
                               app:status \"approved\" .
            }

            OPTIONAL {
                ?pub app:hasUploaded <$userUri> .
                ?rejectedClaim app:claimsPublication ?pub ;
                               app:status \"rejected\" .
            }
        }
    ";

    $result = executeSparqlQuery($sparql);
    $row = $result['results']['bindings'][0] ?? [];

    return [
        'total' => (int)($row['total']['value'] ?? 0),
        'pending' => (int)($row['pending']['value'] ?? 0),
        'approved' => (int)($row['approved']['value'] ?? 0),
        'rejected' => (int)($row['rejected']['value'] ?? 0)
    ];
}

function getUserPublicationsWithClaims($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT (COUNT(DISTINCT ?pub) AS ?count)
        WHERE {
            ?pub app:hasUploaded <$userUri> .
            ?claim app:claimsPublication ?pub .
        }
    ";

    $result = executeSparqlQuery($sparql);
    return (int)($result['results']['bindings'][0]['count']['value'] ?? 0);
}

?>