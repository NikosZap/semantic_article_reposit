<?php
require_once dirname(__DIR__) . '/config.php';

function insertManualPublication( $type, $doi, $title, $pubDate, $pdfFile, $abstract, $fields = []) {
    
    $userUri = $_SESSION['user']['uri'];

    $publicationFieldMap = [
        'Article' => [
            'journal' => 'app:journal',
            'volume' => 'app:volume',
            'number' => 'app:number',
            'pages' => 'app:pages',
        ],
        'Book' => [
            'publisher' => 'app:publisher',
            'isbn' => 'app:isbn',
            'volume' => 'app:volume',
            'series' => 'app:series',
            'edition' => 'app:edition',
        ],
        'Thesis' => [
            'school' => 'app:school'
        ],
        'ConferencePaper' => [
            'conferenceName' => 'app:conferenceName',
            'publisher' => 'app:publisher',
            'pages' => 'app:pages'
]
    ];



    $pubID = uniqid(); 
    
    $sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
               PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
               INSERT DATA {
                    app:pub_$pubID a app:$type ;
                    app:title \"$title\" ;
                    app:doi \"$doi\" ;
                    app:abstract \"\"\"$abstract\"\"\";
                    app:filePath \"$pdfFile\" ;
                    app:uploadedDate \"" . date('Y-m-d\TH:i:s') . "\"^^xsd:dateTime ;
                    app:publicationDate \"$pubDate\"^^xsd:dateTime ;
                    app:isApproved \"false\"^^xsd:boolean
                ";
                
                
    // Extra fields ανά τύπο
    if (isset($publicationFieldMap[$type])) {
        foreach ($publicationFieldMap[$type] as $key => $predicate) {
            if (!empty($fields[$key])) {
                $value = addslashes($fields[$key]);
                $sparql .= "; $predicate \"$value\"\n";
            }
        }
    }
    $sparql .= " ; app:hasUploaded <$userUri> .\n";
    $sparql .= "<$userUri> app:uploadedBy app:pub_$pubID \n";         
                
    $sparql .= ".}";

    $url = SPARQL_UPDATE_ENDPOINT;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    $data = http_build_query(['update' => $sparql]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);
    

    
    return BASE_URI . "pub_" . $pubID;
}

function linkAuthorToPublication($authorURI, $publicationURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <{$publicationURI}> app:hasAuthor <{$authorURI}> .
        }
    ";

    $url = SPARQL_UPDATE_ENDPOINT;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    $data = http_build_query(['update' => $sparql]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response !== false;
}

function getPendingPublications($offset = 0, $limit = 15) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT ?uri ?title ?doi ?uploadedDate
            (STRAFTER(STR(?type), \"#\") AS ?publicationType)
            (CONCAT(?firstname, \" \", ?lastname) AS ?uploaderName)
        WHERE {
            ?uri a ?type ;
            app:title ?title ;
            app:doi ?doi ;
            app:uploadedDate ?uploadedDate ;
            app:isApproved \"false\"^^xsd:boolean .

        OPTIONAL {
            ?uri app:hasUploaded ?user .
            ?user app:firstname ?firstname ;
              app:lastname ?lastname .
            }
        }
        ORDER BY DESC(?uploadedDate)
        OFFSET {$offset}
        LIMIT {$limit}";

    $results = executeSparqlQuery($sparql);
    $bindings = $results["results"]["bindings"] ?? [];

    $publications = [];

    foreach ($bindings as $b) {
        $publications[] = [
            'uri'     => $b['uri']['value'] ?? '',
            'title'   => $b['title']['value'] ?? '',
            'doi'     => $b['doi']['value'] ?? '',
            'type'     => $b['publicationType']['value'] ?? '',
            'uploadedDate' => $b['uploadedDate']['value'] ?? '',
            'uploaderName' => $b['uploaderName']['value'] ?? ''
        ];
    }

    return $publications;
}

function countPendingPublications() {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        SELECT (COUNT(?pub) AS ?count) WHERE {
            ?pub a ?type ;
                 app:isApproved \"false\"^^xsd:boolean .
        }
    ";

    $results = executeSparqlQuery($sparql);
    return isset($results['results']['bindings'][0]['count']['value']) 
    ? (int)$results['results']['bindings'][0]['count']['value'] 
    : 0;
}

function approvePublication($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        DELETE {
            <{$uri}> app:isApproved \"false\"^^xsd:boolean .
        } INSERT {
            <{$uri}> app:isApproved \"true\"^^xsd:boolean .
        } WHERE {
            <{$uri}> app:isApproved \"false\"^^xsd:boolean .
        }
    ";

    $url = SPARQL_UPDATE_ENDPOINT;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    $data = http_build_query(['update' => $sparql]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response !== false;
}

function deletePublication($uri) {
    $uri = trim($uri);

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <{$uri}> ?p ?o .
            ?s ?p2 <{$uri}> .
        }
        WHERE {
            { <{$uri}> ?p ?o . }
            UNION
            { ?s ?p2 <{$uri}> . }
        }
    ";

    return executeSparqlUpdate($sparql);
}

function getPublicationByURI($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?p ?o ?user ?username ?firstname ?lastname ?email 
        WHERE {
            <{$uri}> ?p ?o .
            OPTIONAL {
                <{$uri}> app:hasUploaded ?user .
                ?user app:username ?username ;
                      app:firstname ?firstname ;
                      app:lastname ?lastname;
                      app:email ?email .
            }
                    
        }
    ";

    $json = executeSparqlQuery($sparql);
    $results = [];

    if (isset($json['results']['bindings'])) {
        foreach ($json['results']['bindings'] as $row) {

            $predicate = preg_replace("~^.+[#/]~", '', $row['p']['value']);
            $results[$predicate] = $row['o']['value'];

            if (isset($row['user'])) {
                $results['userURI'] = $row['user']['value'];
                $results['username'] = $row['username']['value'] ?? '';
                $results['firstname'] = $row['firstname']['value'] ?? '';
                $results['lastname'] = $row['lastname']['value'] ?? '';
                $results['email'] = $row['email']['value'] ?? '';
            }
        }
    }

    return $results;
}

function normalizeTitleForComparison($title) {
    $title = trim($title);
    $title = mb_strtolower($title, 'UTF-8');
    $title = preg_replace('/\s+/', ' ', $title);
    return $title;
}

function extractYear($date) {
    if (empty($date)) {
        return '';
    }
    return substr($date, 0, 4);
}

function publicationExistsByDOI($doi) {
    $doi = trim($doi);

    if ($doi === '') {
        return false;
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        ASK {
            ?pub app:doi " . sparqlLiteral($doi) . " .
        }
    ";

    $result = executeSparqlQuery($sparql);
    return $result['boolean'] ?? false;
}

function publicationExistsByISBN($isbn) {
    $isbn = trim($isbn);

    if ($isbn === '') {
        return false;
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        ASK {
            ?pub app:bookISBN " . sparqlLiteral($isbn) . " .
        }
    ";

    $result = executeSparqlQuery($sparql);
    return $result['boolean'] ?? false;
}

function publicationExistsByTitleAndDate($title, $pubDate) {
    $title = normalizeTitleForComparison($title);
    $year = extractYear($pubDate);

    if ($title === '' || $year === '') {
        return false;
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        ASK {
            ?pub app:title ?existingTitle ;
                 app:publicationDate ?existingDate .

            FILTER(LCASE(STR(?existingTitle)) = " . sparqlLiteral($title) . ")
            FILTER(SUBSTR(STR(?existingDate), 1, 4) = " . sparqlLiteral($year) . ")
        }
    ";

    $result = executeSparqlQuery($sparql);
    return $result['boolean'] ?? false;
}

function publicationExistsByTitleAndPublisher($title, $publisher, $pubDate) {
    $title = normalizeTitleForComparison($title);
    $publisher = trim($publisher);
    $year = extractYear($pubDate);

    if ($title === '' || $publisher === '' || $year === '') {
        return false;
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        ASK {
            ?pub app:title ?existingTitle ;
                 app:bookPublisher ?existingPublisher ;
                 app:publicationDate ?existingDate .

            FILTER(LCASE(STR(?existingTitle)) = " . sparqlLiteral($title) . ")
            FILTER(LCASE(STR(?existingPublisher)) = " . sparqlLiteral(mb_strtolower($publisher, 'UTF-8')) . ")
            FILTER(SUBSTR(STR(?existingDate), 1, 4) = " . sparqlLiteral($year) . ")
        }
    ";

    $result = executeSparqlQuery($sparql);
    return $result['boolean'] ?? false;
}

function publicationExistsByTitleAndSchool($title, $school, $pubDate) {
    $title = normalizeTitleForComparison($title);
    $school = trim($school);
    $year = extractYear($pubDate);

    if ($title === '' || $school === '' || $year === '') {
        return false;
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        ASK {
            ?pub app:title ?existingTitle ;
                 app:school ?existingSchool ;
                 app:publicationDate ?existingDate .

            FILTER(LCASE(STR(?existingTitle)) = " . sparqlLiteral($title) . ")
            FILTER(LCASE(STR(?existingSchool)) = " . sparqlLiteral(mb_strtolower($school, 'UTF-8')) . ")
            FILTER(SUBSTR(STR(?existingDate), 1, 4) = " . sparqlLiteral($year) . ")
        }
    ";

    $result = executeSparqlQuery($sparql);
    return $result['boolean'] ?? false;
}

function sparqlLiteral($value) {
    $value = str_replace(["\\", "\"", "\n", "\r"], ["\\\\", "\\\"", " ", " "], $value);
    return '"' . $value . '"';
}

function getAuthorsByPublication($uri) {
    $sparql = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            SELECT ?author ?firstname ?lastname ?orcid
            WHERE {
            <{$uri}> app:hasAuthor ?author .
                    ?author a app:Author ;
                    app:firstname ?firstname ;
                    app:lastname ?lastname .
            OPTIONAL { ?author app:orcid ?orcid . }
        }
    ";

    $json = executeSparqlQuery($sparql);
    $authors = [];

    if (isset($json['results']['bindings'])) {
        foreach ($json['results']['bindings'] as $row) {
            $authors[] = [
                'uri' => $row['author']['value'],
                'firstname' => $row['firstname']['value'],
                'lastname' => $row['lastname']['value'],
                'orcid' => $row['orcid']['value'] ?? null
            ];
        }
       
    }
    return $authors;

}

function getOrCreateAuthorURI($author) {
    $orcid = trim($author['orcid'] ?? '');
    $firstname = trim($author['firstname'] ?? '');
    $lastname = trim($author['lastname'] ?? '');
    $email = trim($author['email'] ?? '');

    if ($orcid) {
        $uri = "https://orcid.org/" . $orcid;

        // Έλεγχος αν υπάρχει ήδη
        $checkQuery = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            SELECT ?a WHERE {
                ?a a app:Author ;
                   app:orcid \"$orcid\" .
            } LIMIT 1
        ";
        $result = executeSparqlQuery($checkQuery);
        if (!empty($result['results']['bindings'])) {
            return $result['results']['bindings'][0]['a']['value'];
        }

        $url = "https://pub.orcid.org/v3.0/$orcid/personal-details";
        $headers = ["Accept: application/json"];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        $firstname = $data['name']['given-names']['value'] ?? '';
        $lastname = $data['name']['family-name']['value'] ?? '';
    
        
        $insert = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            INSERT DATA {
                <$uri> a app:Author ;
                       app:orcid \"$orcid\" ;
                       app:firstname \"$firstname\" ;
                       app:lastname \"$lastname\" .
            }
        ";
        executeSparqlUpdate($insert);
        return $uri;
        
    }
    } else {
        // Αναζήτηση με βάση firstname + lastname
        $checkQuery = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            SELECT ?a WHERE {
                ?a a app:Author ;
                   app:firstname \"$firstname\" ;
                   app:lastname \"$lastname\" .
            } LIMIT 1
        ";
        $result = executeSparqlQuery($checkQuery);
        if (!empty($result['results']['bindings'])) {
            return $result['results']['bindings'][0]['a']['value'];
        }

        // Δημιουργία νέου τοπικού URI
        $localURI = BASE_URI . "author_" . uniqid();
        $insert = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            INSERT DATA {
                <$localURI> a app:Author ;
                            app:firstname \"$firstname\" ;
                            app:lastname \"$lastname\" .
            }
        ";
        executeSparqlUpdate($insert);
        return $localURI;
    }
}

function linkAuthorsToPublication($pubURI, $authorURI) {
    
    $sparql = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            INSERT DATA {
                <$pubURI> app:hasAuthor <$authorURI> .
                <$authorURI> app:isAuthorOf <$pubURI> .
            }
        ";

       return executeSparqlUpdate($sparql);
}

function getPublicationUploader($pubURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user ?firstname ?lastname ?email
        WHERE {
            <$pubURI> app:hasUploaded ?user .
            ?user app:firstname ?firstname ;
                  app:lastname ?lastname ;
                  app:email ?email .
        }
        LIMIT 1
    ";

    $json = executeSparqlQuery($sparql);

    if (isset($json['results']['bindings'][0])) {
        $row = $json['results']['bindings'][0];
        return [
            'uri' => $row['user']['value'],
            'firstname' => $row['firstname']['value'],
            'lastname' => $row['lastname']['value'],
            'email' => $row['email']['value']
        ];
    }

    return null;
}

function getOrCreateKeywordURI($keyword) {
    $escapedKeyword = addslashes($keyword);
    $checkQuery = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?kw WHERE {
            ?kw a app:Keyword ;
                app:keywordText \"$escapedKeyword\" .
        } LIMIT 1
    ";

    $result = executeSparqlQuery($checkQuery);

    if (!empty($result['results']['bindings'])) {
        return $result['results']['bindings'][0]['kw']['value'];
    } else {
        $kwURI = BASE_URI . "keyword_" . md5($keyword);
        $insert = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            INSERT DATA {
                <{$kwURI}> a app:Keyword ;
                            app:keywordText \"$escapedKeyword\" .
            }
        ";
        executeSparqlUpdate($insert);
        return $kwURI;
    }
}

function linkKeywordsToPublication($pubURI, $keywordURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <$pubURI> app:hasKeyword <$keywordURI> .
            <$keywordURI> app:isKeywordOf <$pubURI> .
        }
    ";
    executeSparqlUpdate($sparql);
}

function getKeywordsByPublication($pubURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?kwText WHERE {
            <$pubURI> app:hasKeyword ?kw .
            ?kw app:keywordText ?kwText .
        }
    ";
    
    $json = executeSparqlQuery($sparql);
    $keywords = [];

    if (!empty($json['results']['bindings'])) {
        foreach ($json['results']['bindings'] as $row) {
             $keywords[] = $row['kwText']['value'];
 
        }
    }

    return $keywords;
}

function getAbstract($pubURI) {
    
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?abstract WHERE {
            <{$pubURI}> app:abstract ?abstract .
        }
        LIMIT 1
    ";

    $json = executeSparqlQuery($sparql);

    $data = json_decode($json, true);

    if (isset($data['results']['bindings'][0]['abstract']['value'])) {
        return $data['results']['bindings'][0]['abstract']['value'];
    }

    return null; // Αν δεν βρεθεί abstract
}

function getPublicationsByAuthor($orcid) {

    $query = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        SELECT ?pub ?title ?doi ?publicationDate
        WHERE {
            ?author a app:Author ;
                    app:orcid \"$orcid\" ;
                    app:isAuthorOf ?pub .

            ?pub app:title ?title ;
                 app:doi ?doi ;
                 app:publicationDate ?publicationDate ;
                 app:isApproved \"true\"^^xsd:boolean.
        }
        ORDER BY ?title
    ";

    $response = executeSparqlQuery($query);

    $results = [];
    if (!empty($response['results']['bindings'])) {
        foreach ($response['results']['bindings'] as $row) {
            $results[] = [
                'uri' => $row['pub']['value'],
                'title' => $row['title']['value'],
                'doi' => $row['doi']['value'],
                'publicationDate' => $row['publicationDate']['value']
            ];
        }
    }

    return $results;
}
function getPublications($filters, $offset = 0, $limit = 15) {
    $search = strtolower(trim($filters['search'] ?? ''));
    $pubDateFrom = $filters['pubDateFrom'] ?? '';
    $pubDateTo = $filters['pubDateTo'] ?? '';
    $uploadDateFrom = $filters['uploadDateFrom'] ?? '';
    $uploadDateTo = $filters['uploadDateTo'] ?? '';

    $typeMap = [
        'Article' => 'app:Article',
        'ConferencePaper' => 'app:ConferencePaper',
        'Book' => 'app:Book',
        'Thesis' => 'app:Thesis'
    ];
    
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        SELECT DISTINCT ?uri ?title ?doi ?publicationDate ?uploadedDate 
             (STRAFTER(STR(?type), \"#\") AS ?publicationType)
        WHERE {
            ?uri a ?type  ;
                 app:title ?title ;
                 app:doi ?doi ;
                 app:publicationDate ?publicationDate ;
                 app:uploadedDate ?uploadedDate ;
                 app:isApproved \"true\"^^xsd:boolean .
                 
            OPTIONAL { ?uri app:hasKeyword ?kw . ?kw app:keywordText ?keywordText }
            OPTIONAL { ?uri app:hasAuthor ?a . ?a app:firstname ?fn ; app:lastname ?ln }
    ";

    if (!empty($filters['type']) && isset($typeMap[$filters['type']])) {
        $sparql .= "FILTER(?type = {$typeMap[$filters['type']]})\n";
    }

    if (!empty($pubDateFrom)) {
        $from = date('Y-m-d\TH:i:s', strtotime($pubDateFrom));
        $sparql .= "FILTER(?publicationDate >= \"$pubDateFrom\"^^xsd:dateTime)\n";
    }

    if (!empty($pubDateTo)) {
        $to = date('Y-m-d\TH:i:s', strtotime($pubDateTo . ' +1 day -1 second'));
        $sparql .= "FILTER(?publicationDate <= \"$to\"^^xsd:dateTime)\n";
    }

    if (!empty($uploadDateFrom)) {
        $from = date('Y-m-d\TH:i:s', strtotime($uploadDateFrom));
        $sparql .= "FILTER(?uploadedDate >= \"$from\"^^xsd:dateTime)\n";
    }

    if (!empty($uploadDateTo)) {
        $to = date('Y-m-d\TH:i:s', strtotime($uploadDateTo . ' +1 day -1 second'));
        $sparql .= "FILTER(?uploadedDate <= \"$to\"^^xsd:dateTime)\n";
    }

    if (!empty($search)) {
        $search = addslashes($search);
        $sparql .= "FILTER(
            CONTAINS(LCASE(STR(?title)), \"$search\") ||
            CONTAINS(LCASE(STR(?doi)), \"$search\") ||
            CONTAINS(LCASE(STR(?keywordText)), \"$search\") ||
            CONTAINS(LCASE(STR(?fn)), \"$search\") ||
            CONTAINS(LCASE(STR(?ln)), \"$search\") ||
            CONTAINS(CONCAT(LCASE(STR(?fn)), \" \", LCASE(STR(?ln))), \"$search\")
        )\n";
    }

    $sparql .= "}
        ORDER BY DESC(?pubDate)
        OFFSET $offset
        LIMIT $limit
    ";

    $results = executeSparqlQuery($sparql);
    $bindings = $results["results"]["bindings"] ?? [];

    $publications = [];
    foreach ($bindings as $b) {
        $publications[] = [
            'uri'     => $b['uri']['value'],
            'type'     => $b['publicationType']['value'],  
            'title'   => $b['title']['value'],
            'doi'     => $b['doi']['value'],
            'pubDate' => $b['publicationDate']['value'],
            'uploadDate' => $b['uploadedDate']['value']
        ];
    }

    //print_r($sparql);
    return $publications;
}


function countPublications($filters) {
    $search = strtolower(trim($filters['search'] ?? ''));
    $pubDateFrom = $filters['pubDateFrom'] ?? '';
    $pubDateTo = $filters['pubDateTo'] ?? '';
    $uploadDateFrom = $filters['uploadDateFrom'] ?? '';
    $uploadDateTo = $filters['uploadDateTo'] ?? '';

    $typeMap = [
        'Article' => 'app:Article',
        'ConferencePaper' => 'app:ConferencePaper',
        'Book' => 'app:Book',
        'Thesis' => 'app:Thesis'
    ];

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        SELECT (COUNT(DISTINCT ?uri) AS ?count) WHERE {
            ?uri a ?type  ;
                 app:title ?title ;
                 app:doi ?doi ;
                 app:publicationDate ?publicationDate ;
                 app:uploadedDate ?uploadedDate;
                 app:isApproved \"true\"^^xsd:boolean .
                 
            OPTIONAL { ?uri app:hasKeyword ?kw . ?kw app:keywordText ?keywordText }
            OPTIONAL { ?uri app:hasAuthor ?a . ?a app:firstname ?fn ; app:lastname ?ln }
    ";

    if (!empty($filters['type']) && isset($typeMap[$filters['type']])) {
        $sparql .= "FILTER(?type = {$typeMap[$filters['type']]})\n";
    }

    if (!empty($pubDateFrom)) {
        $from = date('Y-m-d\TH:i:s', strtotime($pubDateFrom));
        $sparql .= "FILTER(?publicationDate >= \"$pubDateFrom\"^^xsd:dateTime)\n";
    }

    if (!empty($pubDateTo)) {
        $to = date('Y-m-d\TH:i:s', strtotime($pubDateTo . ' +1 day -1 second'));
        $sparql .= "FILTER(?publicationDate <= \"$to\"^^xsd:dateTime)\n";
    }

    if (!empty($uploadDateFrom)) {
        $from = date('Y-m-d\TH:i:s', strtotime($uploadDateFrom));
        $sparql .= "FILTER(?uploadedDate >= \"$from\"^^xsd:dateTime)\n";
    }

    if (!empty($uploadDateTo)) {
        $to = date('Y-m-d\TH:i:s', strtotime($uploadDateTo . ' +1 day -1 second'));
        $sparql .= "FILTER(?uploadedDate <= \"$to\"^^xsd:dateTime)\n";
    }

    if (!empty($search)) {
        $search = addslashes($search);
        $sparql .= "FILTER(
            CONTAINS(LCASE(STR(?title)), \"$search\") ||
            CONTAINS(LCASE(STR(?doi)), \"$search\") ||
            CONTAINS(LCASE(STR(?keywordText)), \"$search\") ||
            CONTAINS(LCASE(STR(?fn)), \"$search\") ||
            CONTAINS(LCASE(STR(?ln)), \"$search\") ||
            CONTAINS(CONCAT(LCASE(STR(?fn)), \" \", LCASE(STR(?ln))), \"$search\")
        )\n";
    }

    $sparql .= "}";

    $results = executeSparqlQuery($sparql);
    return isset($results['results']['bindings'][0]['count']['value']) 
        ? (int)$results['results']['bindings'][0]['count']['value'] 
        : 0;
}

function addMentionsToPublication($pubURI, $entityURIs) {
    $prefix = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>";

    $entityURIs = array_unique(array_filter($entityURIs));

    if (empty($pubURI) || empty($entityURIs)) {
        return false;
    }

    $triples = "";
    foreach ($entityURIs as $uri) {
        $triples .= "<$pubURI> app:mentions <$uri> .\n";
    }

    $query = $prefix . "\nINSERT DATA {\n" . $triples . "}";

    return executeSparqlUpdate($query);
}

function getPublicationsByMentions($uris, $selectedUris = [], $relatedOnlyUris = [], $mode = 'or') {
    if (empty($uris) || !is_array($uris)) {
        return [];
    }

    $cleanUris = array_values(array_unique(array_filter($uris)));

    if (empty($cleanUris)) {
        return [];
    }

    $values = '';
    foreach ($cleanUris as $uri) {
        $values .= "<{$uri}> ";
    }

    $havingClause = '';

    if ($mode === 'and' && !empty($selectedUris)) {
        $selectedCount = count(array_unique($selectedUris));
        $selectedValues = '';

        foreach ($selectedUris as $uri) {
            $selectedValues .= "<{$uri}> ";
        }

        $havingClause = "
            HAVING (
                COUNT(DISTINCT IF(?mention IN ($selectedValues), ?mention, UNDEF)) = $selectedCount
            )
        ";
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?pub ?title ?abstract (COUNT(DISTINCT ?mention) AS ?matchCount)
        WHERE {
            VALUES ?mention { $values }

            ?pub app:mentions ?mention .
            ?pub app:title ?title .

            OPTIONAL {
                ?pub app:abstract ?abstract .
            }
        }
        GROUP BY ?pub ?title ?abstract
        $havingClause
        ORDER BY DESC(?matchCount)
    ";

    $data = executeSparqlQuery($sparql);

    if ($data === false) {
        return [];
    }

    $results = [];

    if (isset($data['results']['bindings'])) {
        foreach ($data['results']['bindings'] as $row) {
            $pubUri = $row['pub']['value'];

            $pubMentions = getMentionUrisByPublication($pubUri);

            $exactMatches = array_intersect($pubMentions, $selectedUris);
            $relatedMatches = array_intersect($pubMentions, $relatedOnlyUris);

            $exactMatchCount = count($exactMatches);
            $relatedMatchCount = count($relatedMatches);
            $semanticScore = $exactMatchCount + ($relatedMatchCount * 0.5);

            $results[] = [
                'uri' => $pubUri,
                'title' => $row['title']['value'] ?? 'Unknown title',
                'abstract' => $row['abstract']['value'] ?? '',
                'matchCount' => isset($row['matchCount']['value']) ? (int)$row['matchCount']['value'] : 0,
                'exactMatchCount' => $exactMatchCount,
                'relatedMatchCount' => $relatedMatchCount,
                'exactConcepts' => array_map('dbpediaUriToLabel', array_values($exactMatches)),
                'relatedConcepts' => array_map('dbpediaUriToLabel', array_values($relatedMatches)),
                'semanticScore' => $semanticScore,
                'authors' => getAuthorsByPublication($pubUri),
                'keywords' => getKeywordsByPublication($pubUri)
            ];
        }
    }

    usort($results, function ($a, $b) use ($mode) {
        if ($mode === 'and') {
            if ($b['exactMatchCount'] !== $a['exactMatchCount']) {
                return $b['exactMatchCount'] <=> $a['exactMatchCount'];
            }
        }

        return $b['semanticScore'] <=> $a['semanticScore'];
    });

    return $results;
}
function dbpediaUriToLabel($uri) {
    $lastPart = basename($uri);
    $label = str_replace('_', ' ', $lastPart);
    return urldecode($label);
}
function getRelatedMentionsFromLocalGraph($uri, $limit = 5) {
    $uri = trim($uri);

    if (empty($uri)) {
        return [];
    }

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?related (COUNT(?pub) AS ?freq)
        WHERE {
            ?pub app:mentions <{$uri}> .
            ?pub app:mentions ?related .

            FILTER(?related != <{$uri}>)
        }
        GROUP BY ?related
        ORDER BY DESC(?freq)
        LIMIT {$limit}
    ";

    $data = executeSparqlQuery($sparql);

    if ($data === false || !isset($data['results']['bindings'])) {
        return [];
    }

    $relatedUris = [];

    foreach ($data['results']['bindings'] as $row) {
        if (!empty($row['related']['value'])) {
            $relatedUris[] = $row['related']['value'];
        }
    }

    return $relatedUris;
}

function expandConceptUris($uris, $limitPerUri = 5) {
    if (empty($uris) || !is_array($uris)) {
        return [];
    }

    $expanded = array_unique(array_filter($uris));

    foreach ($uris as $uri) {
        $related = getRelatedMentionsFromLocalGraph($uri, $limitPerUri);
        $expanded = array_merge($expanded, $related);
    }

    return array_values(array_unique(array_filter($expanded)));
}

function getMentionUrisByPublication($pubUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?mention
        WHERE {
            <{$pubUri}> app:mentions ?mention .
        }
    ";

    $data = executeSparqlQuery($sparql);

    if ($data === false || !isset($data['results']['bindings'])) {
        return [];
    }

    $mentions = [];

    foreach ($data['results']['bindings'] as $row) {
        if (!empty($row['mention']['value'])) {
            $mentions[] = $row['mention']['value'];
        }
    }

    return array_values(array_unique($mentions));
}


function searchPublicationsWithAuthors($searchTerm) {
    $escapedTerm = addslashes($searchTerm);
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?pub ?title ?authorUri ?firstName ?lastName WHERE {
            ?pub app:title ?title .
        FILTER(CONTAINS(LCASE(?title), LCASE(\"$escapedTerm\"))) .
        OPTIONAL {
            ?pub app:hasAuthor ?authorUri .
            ?authorUri app:firstname ?firstName .
            ?authorUri app:lastname ?lastName .
            }
        }
    ";

    $results = executeSparqlQuery($sparql);
    $bindings = $results['results']['bindings'] ?? [];

    $publications = [];

    foreach ($bindings as $row) {
        $pubUri = $row['pub']['value'];

        // Αν δεν υπάρχει ήδη, φτιάξε το
        if (!isset($publications[$pubUri])) {
            $publications[$pubUri] = [
                'uri' => $pubUri,
                'title' => $row['title']['value'] ?? '(Untitled)',
                'authors' => []
            ];
        }

        // Αν έχει συγγραφέα, πρόσθεσέ τον
        if (!empty($row['authorUri']['value'])) {
            $publications[$pubUri]['authors'][] = [
                'uri' => $row['authorUri']['value'],
                'firstname' => $row['firstName']['value'] ?? '',
                'lastname' => $row['lastName']['value'] ?? ''
            ];
        }
    }

    return array_values($publications);
}

function getPublicationWithAuthors($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?title ?author ?firstname ?lastname ?orcid WHERE {
            <{$uri}> app:title ?title .
            OPTIONAL {
                <{$uri}> app:hasAuthor ?author .
                OPTIONAL { ?author app:firstname ?firstname . }
                OPTIONAL { ?author app:lastname ?lastname . }
                OPTIONAL { ?author app:orcid ?orcid . }
            }
        }
    ";

    $results = executeSparqlQuery($sparql);
    $publication = [
        'uri' => $uri,
        'title' => '',
        'authors' => []
    ];

    $bindings = $results['results']['bindings'] ?? [];


    foreach ($bindings as $row) {
        // Βάζω τον τίτλο μόνο μία φορά
        if (empty($publication['title']) && isset($row['title']['value'])) {
            $publication['title'] = $row['title']['value'];
        }
    
        // Προσθέτω κάθε συγγραφέα ξεχωριστά
        if (!empty($row['author']['value'])) {
            $publication['authors'][] = [
                'uri' => $row['author']['value'],
                'firstname' => $row['firstname']['value'] ?? '',
                'lastname' => $row['lastname']['value'] ?? '',
                'orcid' => $row['orcid']['value'] ?? ''
            ];
        }
    }

    return $publication;
}

function insertClaimPublicationAuthor($userUri, $claimedAuthorUri, $publicationUri, $reason) {
    
    $claimId = uniqid("claim_");
    $claimUri = "app:$claimId";
    $dateNow = date('Y-m-d');


    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
    PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
    
    INSERT DATA {
        $claimUri a app:Claim ;
                app:claimedBy <$userUri> ;
                app:claimedAuthor <$claimedAuthorUri> ;
                app:isClaimOf <$publicationUri> ;
                app:reason \"" . addslashes($reason) . "\" ;
                app:claimDate \"$dateNow\"^^xsd:date ;
                app:claimStatus \"Pending\" .
}
";

return executeSparqlUpdate($sparql);

}

function countPendingClaims() {

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    SELECT (COUNT(?claim) AS ?pendingCount)
    WHERE {
        ?claim a app:Claim ;
               app:claimStatus \"Pending\" .
    }
    ";

    $results = executeSparqlQuery($sparql);
    return isset($results['results']['bindings'][0]['pendingCount']['value'])
        ? (int)$results['results']['bindings'][0]['pendingCount']['value']
        : 0;
}

function getPendingClaims($offset = 0, $limit = 15) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            SELECT  ?claim ?claimDate ?reason ?publication 
                    ?pubTitle ?user ?userFirstName ?userLastName 
                    ?author ?firstname    ?lastname
            WHERE {
                    ?claim a app:Claim ;
                    app:claimStatus \"Pending\" ;
                    app:claimDate ?claimDate;
                    app:claimedBy ?user ;
                    app:claimedAuthor ?author ;
                    app:isClaimOf ?publication ;
		            app:reason ?reason ;

            OPTIONAL { ?publication app:title ?pubTitle . }

            OPTIONAL { 
                    ?user app:firstname ?userFirstName ;
                    app:lastname ?userLastName .   }
    
            OPTIONAL { 
                    ?author app:firstname ?firstname ;
                    app:lastname ?lastname . }
            }
            ORDER BY DESC(?claimDate)
            OFFSET {$offset} LIMIT {$limit} ";

    $results = executeSparqlQuery($sparql);
    $bindings = $results["results"]["bindings"] ?? [];

    $claims = [];

    foreach ($bindings as $b) {
        $claims[] = [
            'uri'     => $b['claim']['value'] ?? '',
            'claimDate'   => $b['claimDate']['value'] ?? '',
            'reason'     => $b['reason']['value'] ?? '',
            'publication' => $b['publication']['value'] ?? '',
            'pubTitle' => $b['pubTitle']['value'] ?? '',
            'user' => $b['user']['value'] ?? '',
            'userFirstName' => $b['userFirstName']['value'] ?? '',
            'userLastName' => $b['userLastName']['value'] ?? '',
            'author' => $b['author']['value'] ?? '',
            'authorFirstname' => $b['firstname']['value'] ?? '',
            'authorLastname' => $b['lastname']['value'] ?? '',
            'pubDate' => $b['publication']['value'] ?? '',
        ];
    }

    return $claims;
}

function getClaimByURI($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT 
            ?claimDate 
            ?status 
            ?reason 
            ?publicationURI 
            ?doi 
            ?pubTitle 
            ?userURI
            ?userEmail 
            ?userFirstName 
            ?userLastName 
            ?oldAuthorURI
            ?authorFirstName
            ?authorLastName 
            ?authorOrcid
            ?realAuthorURI
        WHERE {

            <$uri> a app:Claim ;
                app:claimDate ?claimDate ;
                app:claimStatus ?status ;
                app:claimedBy ?userURI ;
                app:claimedAuthor ?oldAuthorURI ;
                app:isClaimOf ?publicationURI .

            OPTIONAL { <$uri> app:reason ?reason . }
            OPTIONAL { ?publicationURI app:title ?pubTitle . }
            OPTIONAL { ?publicationURI app:doi ?doi . }

            OPTIONAL { ?userURI app:email ?userEmail . }
            OPTIONAL { ?userURI app:firstname ?userFirstName . }
            OPTIONAL { ?userURI app:lastname ?userLastName . }

            OPTIONAL { ?oldAuthorURI app:firstname ?authorFirstName . }
            OPTIONAL { ?oldAuthorURI app:lastname ?authorLastName . }
            OPTIONAL { ?oldAuthorURI app:orcid ?authorOrcid . }

            # Αυτό που επιβεβαίωσες:
            OPTIONAL { 
                ?userURI app:isCorrespondingUser ?realAuthorURI .
            }
        }
        LIMIT 1";

    $json = executeSparqlQuery($sparql);
    $results = [];

    if (!empty($json['results']['bindings'])) {
        $row = $json['results']['bindings'][0];

        $results['claimDate']        = $row['claimDate']['value'] ?? null;
        $results['status']           = $row['status']['value'] ?? null;
        $results['reason']           = $row['reason']['value'] ?? null;

        $results['publication']   = $row['publicationURI']['value'] ?? null;
        $results['oldAuthorURI']     = $row['oldAuthorURI']['value'] ?? null;     // claimedAuthor (παλιός)
        $results['userURI']          = $row['userURI']['value'] ?? null;          // claimedBy (user)
        $results['realAuthorURI']    = $row['realAuthorURI']['value'] ?? null;    // isCorrespondingUser (author-user)

        $results['pubTitle']         = $row['pubTitle']['value'] ?? null;
        $results['doi']              = $row['doi']['value'] ?? null;

        $results['userFirstname']    = $row['userFirstName']['value'] ?? null;
        $results['userLastname']     = $row['userLastName']['value'] ?? null;
        $results['userEmail']        = $row['userEmail']['value'] ?? null;

        $results['authorFirstname']  = $row['authorFirstName']['value'] ?? null;
        $results['authorLastname']   = $row['authorLastName']['value'] ?? null;
        $results['authorOrcid']      = $row['authorOrcid']['value'] ?? null;
    }

    return $results;
}

function updateClaimStatus($claimUri, $newStatus)
{
    $claimUri = trim($claimUri);
    $newStatus = addslashes($newStatus);

    if (empty($claimUri) || empty($newStatus)) {
        return false;
    }

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    DELETE {
        <$claimUri> app:claimStatus ?oldStatus .
    }
    INSERT {
        <$claimUri> app:claimStatus \"$newStatus\" .
    }
    WHERE {
        <$claimUri> app:claimStatus ?oldStatus .
    }
    ";

    return executeSparqlUpdate($sparql);
}

function getClaimsHistoryByPublication($publicationUri)
{
    $publicationUri = trim($publicationUri);
    if ($publicationUri === '') {
        return [];
    }

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    SELECT
        ?claim
        ?claimDate
        ?status
        ?reason

        ?userEmail
        ?userFirstName
        ?userLastName

        ?authorFirstName
        ?authorLastName
        ?authorOrcid
    WHERE {
        ?claim a app:Claim ;
               app:isClaimOf <$publicationUri> ;
               app:claimDate ?claimDate ;
               app:claimStatus ?status ;
               app:claimedBy ?user ;
               app:claimedAuthor ?author .

        OPTIONAL { ?claim app:reason ?reason . }

        OPTIONAL { ?user app:email ?userEmail . }
        OPTIONAL { ?user app:firstname ?userFirstName . }
        OPTIONAL { ?user app:lastname ?userLastName . }

        OPTIONAL { ?author app:firstname ?authorFirstName . }
        OPTIONAL { ?author app:lastname ?authorLastName . }
        OPTIONAL { ?author app:orcid ?authorOrcid . }
    }
    ORDER BY DESC(?claimDate)
    ";

    $json = executeSparqlQuery($sparql);
    $bindings = $json['results']['bindings'] ?? [];

    $history = [];
    foreach ($bindings as $b) {
        $history[] = [
            'claim' => $b['claim']['value'] ?? '',
            'claimDate' => $b['claimDate']['value'] ?? '',
            'status' => $b['status']['value'] ?? '',
            'reason' => $b['reason']['value'] ?? '',

            'userEmail' => $b['userEmail']['value'] ?? '',
            'userFirstName' => $b['userFirstName']['value'] ?? '',
            'userLastName' => $b['userLastName']['value'] ?? '',

            'authorFirstName' => $b['authorFirstName']['value'] ?? '',
            'authorLastName' => $b['authorLastName']['value'] ?? '',
            'authorOrcid' => $b['authorOrcid']['value'] ?? '',
        ];
    }

    return $history;
}

function getPublicationTitleByUri($publicationUri)
{
    $publicationUri = trim($publicationUri);
    if ($publicationUri === '') {
        return null;
    }

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    SELECT ?title
    WHERE {
            <$publicationUri> app:title ?title .
    }
    LIMIT 1
    ";

    $json = executeSparqlQuery($sparql);
    $bindings = $json['results']['bindings'] ?? [];

    if (empty($bindings)) {
        return null;
    }

    return $bindings[0]['title']['value'] ?? null;
}

function changePublicationAuthor($publicationUri, $oldAuthorUri, $newAuthorUri)
{
    $publicationUri = trim($publicationUri);
    $oldAuthorUri = trim($oldAuthorUri);
    $newAuthorUri = trim($newAuthorUri);

    if ($publicationUri === '' || $oldAuthorUri === '' || $newAuthorUri === '') {
        return false;
    }

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    DELETE {
        <$publicationUri> app:hasAuthor <$oldAuthorUri> .
        <$oldAuthorUri> app:isAuthorOf <$publicationUri> .
    }
    INSERT {
        <$publicationUri> app:hasAuthor <$newAuthorUri> .
        <$newAuthorUri> app:isAuthorOf <$publicationUri> .
    }
    WHERE {
        OPTIONAL { <$publicationUri> app:hasAuthor <$oldAuthorUri> . }
        OPTIONAL { <$oldAuthorUri> app:isAuthorOf <$publicationUri> . }
    }
    ";

    return executeSparqlUpdate($sparql);
}

function getPublicationsByKeyword($keyword)
{
    if (empty($keyword)) {
        return [];
    }

    $keyword = addslashes(trim($keyword));

    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

    SELECT
        ?pub
        ?title
        ?abstract
        ?publicationDate
        ?authorFirstName
        ?authorLastName
        ?allKeywordText
    WHERE {
        ?matchedKeyword a app:Keyword ;
                        app:keywordText ?matchedKeywordText .

        FILTER(LCASE(STR(?matchedKeywordText)) = LCASE(\"$keyword\"))

        ?pub app:hasKeyword ?matchedKeyword ;
             app:isApproved true .


        OPTIONAL { ?pub app:title ?title . }
        OPTIONAL { ?pub app:abstract ?abstract . }
        OPTIONAL { ?pub app:publicationDate ?publicationDate . }

        OPTIONAL {
            ?pub app:hasAuthor ?author .
            ?author app:firstname ?authorFirstName .
            ?author app:lastname ?authorLastName .
        }

        OPTIONAL {
            ?pub app:hasKeyword ?allKeyword .
            ?allKeyword app:keywordText ?allKeywordText .
        }
    }
    ORDER BY DESC(?publicationDate)
    ";

    $response = executeSPARQLQuery($sparql);
    $bindings = $response['results']['bindings'] ?? [];

    $publications = [];

    foreach ($bindings as $row) {
        $uri = $row['pub']['value'] ?? '';

        if ($uri === '') {
            continue;
        }

        if (!isset($publications[$uri])) {
            $publications[$uri] = [
                'uri' => $uri,
                'title' => $row['title']['value'] ?? 'Unknown title',
                'abstract' => $row['abstract']['value'] ?? '',
                'publicationDate' => $row['publicationDate']['value'] ?? '',
                'authors' => [],
                'keywords' => []
            ];
        }

        $firstName = $row['authorFirstName']['value'] ?? '';
        $lastName  = $row['authorLastName']['value'] ?? '';

        if ($firstName !== '' || $lastName !== '') {
            $authorExists = false;

            foreach ($publications[$uri]['authors'] as $author) {
                if (
                    $author['firstname'] === $firstName &&
                    $author['lastname'] === $lastName
                ) {
                    $authorExists = true;
                    break;
                }
            }

            if (!$authorExists) {
                $publications[$uri]['authors'][] = [
                    'firstname' => $firstName,
                    'lastname' => $lastName
                ];
            }
        }

        $kw = $row['allKeywordText']['value'] ?? '';

        if ($kw !== '' && !in_array($kw, $publications[$uri]['keywords'], true)) {
            $publications[$uri]['keywords'][] = $kw;
        }
    }

    return array_values($publications);
}

function getUploadedPublicationsByUser($userUri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?pub ?title ?publicationDate ?uploadedDate ?isApproved
        WHERE {
            ?pub app:hasUploaded <$userUri> ;
                 app:title ?title .

            OPTIONAL { ?pub app:publicationDate ?publicationDate . }
            OPTIONAL { ?pub app:uploadedDate ?uploadedDate . }
            OPTIONAL { ?pub app:isApproved ?isApproved . }
        }
        ORDER BY DESC(?uploadedDate) DESC(?publicationDate)
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $data[] = [
            'uri' => $row['pub']['value'] ?? '',
            'title' => $row['title']['value'] ?? 'Unknown title',
            'publicationDate' => $row['publicationDate']['value'] ?? '',
            'uploadedDate' => $row['uploadedDate']['value'] ?? '',
            'isApproved' => $row['isApproved']['value'] ?? ''
        ];
    }

    return $data;
}

function getPublicationsByOrcid($orcid) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT DISTINCT ?pub ?title ?publicationDate
        WHERE {
            ?author app:orcid \"$orcid\" .
            ?pub app:hasAuthor ?author ;
                 app:title ?title .

            OPTIONAL { ?pub app:publicationDate ?publicationDate . }
        }
        ORDER BY DESC(?publicationDate)
    ";

    $result = executeSparqlQuery($sparql);
    $rows = $result['results']['bindings'] ?? [];

    $data = [];

    foreach ($rows as $row) {
        $data[] = [
            'uri' => $row['pub']['value'] ?? '',
            'title' => $row['title']['value'] ?? 'Unknown title',
            'publicationDate' => $row['publicationDate']['value'] ?? ''
        ];
    }

    return $data;
}