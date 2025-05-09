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



    $pubID = uniqid(); // δημιουργεί μοναδικό ID
    
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

    print_r($sparql);

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
        SELECT ?uri ?title ?doi ?pubDate WHERE {
            ?uri a ?type ;
                 app:title ?title ;
                 app:doi ?doi ;
                 app:publicationDate ?pubDate ;
                 app:isApproved \"false\"^^xsd:boolean .
        }
        OFFSET {$offset} LIMIT {$limit}
    ";

    $results = executeSparqlQuery($sparql);
    $bindings = $results["results"]["bindings"] ?? [];

    $publications = [];

    foreach ($bindings as $b) {
        $publications[] = [
            'uri'     => $b['uri']['value'],
            'title'   => $b['title']['value'],
            'doi'     => $b['doi']['value'],
            'pubDate' => $b['pubDate']['value']
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
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        DELETE WHERE {
            <{$uri}> ?p ?o .
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
        SELECT DISTINCT ?uri ?title ?doi ?publicationDate ?uploadedDate WHERE {
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

    $triples = "";
    foreach ($entityURIs as $uri) {
        $triples .= "<$pubURI> app:mentions <$uri> .\n";
    }

    $query = $prefix . "\nINSERT DATA { $triples }";

    return executeSparqlUpdate($query);

   
}

function getPublicationsByMention($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?pub ?title ?abstract WHERE {
            ?pub app:mentions <{$uri}> .
            ?pub app:title ?title .
            ?pub app:abstract ?abstract .
        }
    ";



    $data = executeSparqlQuery($sparql);
 
    if ($data === false) {
        return [];
    }

    $results = [];
    if (isset($data['results']['bindings'])) {
        foreach ($data['results']['bindings'] as $row) {

            $results[] = [
                'uri' => $row['pub']['value'],
                'title' => $row['title']['value'],
                'abstract' => $row['abstract']['value'],
                'authors' => getAuthorsByPublication($row['pub']['value']),
                'keywords' => getKeywordsByPublication($row['pub']['value'])
            ];
        }
    }
    
    return $results;
}
