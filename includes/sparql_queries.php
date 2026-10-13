<?php 

function getUserByUsername($username) {

    $sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

                SELECT ?user ?userID ?username ?password ?role ?firstname ?lastname ?email ?createdAt ?isSuspended ?orcid
                WHERE { 
                    ?user a app:User ;
                    app:userID ?userID ;
                    app:username ?username ;
                    app:password ?password ;
                    app:role ?role ;
                    app:firstname ?firstname ;
                    app:lastname ?lastname ;
                    app:email ?email.
                    OPTIONAL { ?user app:createdAt ?createdAt }
                    OPTIONAL { ?user app:isSuspended ?isSuspended }

                    OPTIONAL { ?author a app:Author ;
                            app:correspondTo ?user ;
                            app:orcid ?orcid . }
                FILTER(?username = \"$username\")
                }
                LIMIT 1";

    $result = executeSparqlQuery($sparql);

    if (!isset($result['results']['bindings'][0])) {
        return null; // Δεν βρέθηκε χρήστης
    }

    $userData = $result['results']['bindings'][0];

       // Επιστρέφει array όπως έκανε το bind_result
    return ['uri'       => $userData['user']['value'], 
            'userID'    => $userData['userID']['value'],
            'username'  => $userData['username']['value'],
            'password'  => $userData['password']['value'],
            'role'      => $userData['role']['value'],
            'firstname' => $userData['firstname']['value'],
            'lastname'  => $userData['lastname']['value'],
            'email'     => $userData['email']['value'],
            'orcid'     => $userData['orcid']['value'] ?? null,
            'createdAt' => $userData['createdAt']['value'] ?? 'N/A',
            'isSuspended' => isset($userData['isSuspended']) ? $userData['isSuspended']['value'] : "0"];

}


// Επιστροφή χρήστη βάσει username ή email
function getUserByUsernameOrEmail($username, $email) {
    $sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
             
                SELECT ?user
                WHERE {
                    ?user a app:User ;
                        app:username ?username ;
                        app:email ?email .
                FILTER(?username = \"$username\" || ?email = \"$email\")
                }
                LIMIT 1";

    $result = executeSparqlQuery($sparql);
    return isset($result['results']['bindings'][0]); // true = user exists
}

function registerUser($username, $email, $hashedPassword, $firstname, $lastname, $activation_token) {
    $userID = uniqid(); // δημιουργεί μοναδικό ID

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        INSERT DATA {
            app:user_$userID a app:User ;
                app:userID \"$userID\" ;
                app:username \"$username\" ;
                app:password \"$hashedPassword\" ;
                app:firstname \"$firstname\" ;
                app:lastname \"$lastname\" ;
                app:email \"$email\" ;
                app:role \"Researcher\" ;
                app:activation_token \"$activation_token\";
                app:is_active \"0\" ;
                app:isSuspended \"0\"  ;
                app:createdAt \"". date('Y-m-d\TH:i:s') .  "\"^^xsd:dateTime .
        }
    ";

    // Εκτέλεση SPARQL Update
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

function getUserByActivationToken($token) {
    $sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
                
                SELECT ?user
                WHERE {
                    ?user a app:User ;
                    app:activation_token \"$token\" .
                }
                LIMIT 1";

    $result = executeSparqlQuery($sparql);
    if (!isset($result['results']['bindings'][0])) return null;

    return ['uri' => $result['results']['bindings'][0]['user']['value']];
}

function activateUser($userURI, $token) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <$userURI> app:activation_token ?oldToken .
            <$userURI> app:is_active ?status .
        }
        INSERT {
            <$userURI> app:is_active \"1\" .
        }
        WHERE {
            <$userURI> app:activation_token ?oldToken .
            FILTER(STR(?oldToken) = \"$token\")
            OPTIONAL { <$userURI> app:is_active ?status . }
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        error_log("cURL error in activateUser: " . $curlError);
        return false;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("SPARQL update failed in activateUser. HTTP $httpCode. Response: " . $response);
        return false;
    }

    return true;
}

function getUserURIByUserID($userID) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user
        WHERE {
            ?user a app:User ;
                  app:userID \"$userID\" .
        }
        LIMIT 1
    ";

    $result = executeSparqlQuery($sparql);
    if (!isset($result['results']['bindings'][0])) return null;

    return $result['results']['bindings'][0]['user']['value'];
}

function checkExistingEmail($email, $currentUserURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user
        WHERE {
            ?user a app:User ;
                  app:email \"$email\" .
            FILTER(?user != <$currentUserURI>)
        }
        LIMIT 1
    ";

    $result = executeSparqlQuery($sparql);

    return isset($result['results']['bindings'][0]); // true αν υπάρχει ήδη
}


function updateUserProfile($userURI, $email, $firstname, $lastname) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <$userURI> app:email ?oldEmail ;
                       app:firstname ?oldFirstname ;
                       app:lastname ?oldLastname .
        }
        INSERT {
            <$userURI> app:email \"$email\" ;
                       app:firstname \"$firstname\" ;
                       app:lastname \"$lastname\" .
        }
        WHERE {
            <$userURI> a app:User ;
                       app:email ?oldEmail ;
                       app:firstname ?oldFirstname ;
                       app:lastname ?oldLastname .
        }
    ";

    return executeSparqlUpdate($sparql);
}

function updateAuthorProfileFieldsByUserUri($userUri, $nationality, $affiliation, $scientificArea) {
    $nationality = addslashes(trim($nationality));
    $affiliation = addslashes(trim($affiliation));
    $scientificArea = addslashes(trim($scientificArea));

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            ?author app:nationality ?oldNationality .
            ?author app:affiliation ?oldAffiliation .
            ?author app:scientificArea ?oldScientificArea .
        }
        INSERT {
            " . ($nationality !== '' ? "?author app:nationality \"$nationality\" ." : "") . "
            " . ($affiliation !== '' ? "?author app:affiliation \"$affiliation\" ." : "") . "
            " . ($scientificArea !== '' ? "?author app:scientificArea \"$scientificArea\" ." : "") . "
        }
        WHERE {
            ?author a app:Author ;
                    app:correspondTo <$userUri> .

            OPTIONAL { ?author app:nationality ?oldNationality }
            OPTIONAL { ?author app:affiliation ?oldAffiliation }
            OPTIONAL { ?author app:scientificArea ?oldScientificArea }
        }
    ";

    return executeSparqlUpdate($sparql);
}

function getUserUriByEmail($email) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user WHERE {
            ?user a app:User ;
                  app:email \"$email\" .
        } LIMIT 1
    ";

    $results = executeSparqlQuery($sparql);
    return $results["results"]["bindings"][0]["user"]["value"] ?? null;
}

function createResetToken($userUri, $token) {
    $tokenUri = "http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#token_$token";
    $createdAt = date('c'); // ISO 8601 format

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        INSERT DATA {
            <$tokenUri> a app:Token ;
                        app:tokenValue \"$token\" ;
                        app:createdAt \"$createdAt\"^^xsd:dateTime ;
                        app:belongsTo <$userUri> .
        }
    ";

    return executeSparqlUpdate($sparql);
}

function storeResetTokenByEmail($email, $token) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        DELETE {
            ?user app:activation_token ?oldToken .
        }
        INSERT {
            ?user app:activation_token \"$token\" .
        }
        WHERE {
            ?user a app:User ;
                  app:email \"$email\" .
            OPTIONAL { ?user app:activation_token ?oldToken . }
        }
    ";

    return executeSparqlUpdate($sparql);
}



function updatePasswordByToken($hashedPassword, $token) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        DELETE {
            ?user app:password ?oldPassword ;
                  app:activation_token ?oldToken .
        }
        INSERT {
            ?user app:password \"$hashedPassword\" ;
                  app:activation_token \"\" .
        }
        WHERE {
            ?user a app:User ;
                  app:activation_token \"$token\" ;
                  app:password ?oldPassword ;
                  app:activation_token ?oldToken .
        }
    ";

    $result = executeSparqlUpdate($sparql);

    // αν υπήρχε τουλάχιστον ένας triple binding → επιτυχία
    return $result;
}

function updateUserRole($userURI, $role) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <$userURI> app:role ?oldRole .
        }
        INSERT {
            <$userURI> app:role \"$role\" .
        }
        WHERE {
            <$userURI> a app:User ;
                      app:role ?oldRole .
        }
    ";

    return executeSparqlUpdate($sparql);
}

function getUserUriById($userID) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user WHERE {
            ?user a app:User ;
                  app:userID \"$userID\" .
        }
        LIMIT 1
    ";
    $result = executeSparqlQuery($sparql);
    return $result['results']['bindings'][0]['user']['value'] ?? null;
}

function deleteUser($userURI) {
    $sparql = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
    DELETE WHERE {
        <$userURI> ?p ?o .
    }
";  
    return executeSparqlUpdate($sparql);
}

function getUsers($filters, $offset = 0, $limit = 15) {
    $sparql = "
       PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
       PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

       SELECT ?user ?userID ?username ?firstname ?lastname ?email ?role ?createdAt ?is_active ?isSuspended
       WHERE {
        ?user a app:User .
        OPTIONAL { ?user app:userID ?userID }
        OPTIONAL { ?user app:username ?username }
        OPTIONAL { ?user app:firstname ?firstname }
        OPTIONAL { ?user app:lastname ?lastname }
        OPTIONAL { ?user app:email ?email }
        OPTIONAL { ?user app:role ?role }
        OPTIONAL { ?user app:createdAt ?createdAt }
        OPTIONAL { ?user app:is_active ?is_active }
        OPTIONAL { ?user app:isSuspended ?isSuspended }

    ";

    // Προσθήκη φίλτρων (προαιρετικά)
    if (!empty($filters['role'])) {
        $sparql .= "FILTER(LCASE(STR(?role)) = \"" . strtolower($filters['role']) . "\")\n";
    }

    if ($filters['is_active'] !== '') {
        $activeStr = $filters['is_active'] === "1" ? "1" : "0";
        $sparql .= "FILTER(?is_active = \"$activeStr\")\n";
    }

    if (!empty($filters['from_date'])) {
        $from = date('Y-m-d\TH:i:s', strtotime($filters['from_date']));
        $sparql .= "FILTER(?createdAt >= \" $from \"^^xsd:dateTime)\n";
    }

    if (!empty($filters['to_date'])) {
        $to = date('Y-m-d\TH:i:s', strtotime($filters['to_date']. ' +1 day -1 second')); // μέχρι τέλος ημέρας
        $sparql .= "FILTER(?createdAt <= \" $to\"^^xsd:dateTime)\n";
    }

    if (!empty($filters['search'])) {
        $search = strtolower($filters['search']);
        $sparql .= "FILTER(CONTAINS(LCASE(STR(?username)), \"$search\") || 
                          CONTAINS(LCASE(STR(?email)), \"$search\") || 
                          CONTAINS(LCASE(STR(?firstname)), \"$search\") || 
                          CONTAINS(LCASE(STR(?lastname)), \"$search\"))\n";
    }

    $sparql .= "}
        ORDER BY DESC(?createdAt)
        OFFSET $offset
        LIMIT $limit
    ";

    $results = executeSparqlQuery($sparql);
    $bindings = $results["results"]["bindings"] ?? [];

    $users = [];
    foreach ($bindings as $b) {
        $users[] = [
            'userID'     => $b['userID']['value'],
            'username'   => $b['username']['value'],
            'firstname'  => $b['firstname']['value'],
            'lastname'   => $b['lastname']['value'],
            'email'      => $b['email']['value'],
            'role'       => $b['role']['value'],
            'createdAt'  => isset($b['createdAt']) ? $b['createdAt']['value'] : '',
            'updatedAt'  => isset($b['updatedAt']) ? $b['updatedAt']['value'] : '',
            'is_active'  => isset($b['is_active']) ? ($b['is_active']['value'] === '1' ? 1 : 0) : 0,
            'isSuspended'  => isset($b['isSuspended']) ? ($b['isSuspended']['value'] === '1' ? 1 : 0) : 0
        ];
    }
    return $users;
}

function countUsers($filters) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

        SELECT (COUNT(?user) AS ?total)
        WHERE {
            ?user a app:User .
            OPTIONAL { ?user app:userID ?userID }
            OPTIONAL { ?user app:username ?username }
            OPTIONAL { ?user app:firstname ?firstname }
            OPTIONAL { ?user app:lastname ?lastname }
            OPTIONAL { ?user app:email ?email }
            OPTIONAL { ?user app:role ?role }
            OPTIONAL { ?user app:createdAt ?createdAt }
            OPTIONAL { ?user app:is_active ?is_active }
            OPTIONAL { ?user app:isSuspended ?isSuspended }
    ";

    // Ίδιοι FILTERS όπως πριν
    if (!empty($filters['role'])) {
        $sparql .= "FILTER(LCASE(STR(?role)) = \"" . strtolower($filters['role']) . "\")\n";
    }

    if ($filters['is_active'] !== '') {
        $activeStr = $filters['is_active'] === "1" ? "1" : "0";
        $sparql .= "FILTER(?is_active = \"$activeStr\")\n";
    }

    if (!empty($filters['from_date'])) {
        $from = date('Y-m-d\TH:i:s', strtotime($filters['from_date']));
        $sparql .= "FILTER(?createdAt >= \" $from \"^^xsd:dateTime)\n";
    }

    if (!empty($filters['to_date'])) {
        $to = date('Y-m-d\TH:i:s', strtotime($filters['to_date']. ' +1 day -1 second')); // μέχρι τέλος ημέρας
        $sparql .= "FILTER(?createdAt <= \" $to\"^^xsd:dateTime)\n";
    }

    if (!empty($filters['search'])) {
        $search = strtolower($filters['search']);
        $sparql .= "FILTER(CONTAINS(LCASE(STR(?username)), \"$search\") || 
                          CONTAINS(LCASE(STR(?email)), \"$search\") || 
                          CONTAINS(LCASE(STR(?firstname)), \"$search\") || 
                          CONTAINS(LCASE(STR(?lastname)), \"$search\"))\n";
    }

    $sparql .= "}";


    $results = executeSparqlQuery($sparql);
    return isset($results['results']['bindings'][0]['total']['value']) 
        ? (int)$results['results']['bindings'][0]['total']['value'] 
        : 0;
}

function deleteUserByURI($userUri) {
    $deleteQuery = "
        DELETE WHERE {
            <$userUri> ?p ?o .
        };
    ";

    return executeSparqlUpdate($deleteQuery);
}

function updateUserSuspension($userURI, $suspendBool) {
    $value = $suspendBool ? "1" : "0";
    $query = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <$userURI> app:isSuspended ?status .
        }
        INSERT {
            <$userURI> app:isSuspended \"$value\".
        }
        WHERE {
            <$userURI> a app:User ;
                      app:isSuspended ?status  .
        }
    ";

    return executeSparqlUpdate($query);
}

function getUserByOrcidId($orcid_id) {

    $query = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?user ?userID ?username ?email ?firstname ?lastname ?role ?orcid ?createdAt ?nationality ?affiliation ?scientificArea
        WHERE {
            ?author a app:Author ;
                    app:orcid ?orcid ;
                    app:correspondTo ?user .

            ?user a app:User ;
                  app:userID ?userID ;
                  app:username ?username ;
                  app:email ?email ;
                  app:firstname ?firstname ;
                  app:lastname ?lastname ;
                  app:role ?role .

            FILTER(?orcid = \"$orcid_id\")
            
            OPTIONAL { ?user app:createdAt ?createdAt }
            OPTIONAL { ?author app:nationality ?nationality }
            OPTIONAL { ?author app:affiliation ?affiliation }
            OPTIONAL { ?author app:scientificArea ?scientificArea }
        }
        LIMIT 1
    ";

    $response = executeSparqlQuery($query); 

    if (isset($response['results']['bindings'][0])) {
        $b = $response['results']['bindings'][0];
        return [
            'uri' => $b['user']['value'],
            'userID' => $b['userID']['value'],
            'username' => $b['username']['value'],
            'email' => $b['email']['value'],
            'firstname' => $b['firstname']['value'],
            'lastname' => $b['lastname']['value'],
            'role' => $b['role']['value'],
            'orcid' => $b['orcid']['value'],
            'createdAt' => $b['createdAt']['value'] ?? 'N/A',
            'nationality' => $b['nationality']['value'] ?? '',
            'affiliation' => $b['affiliation']['value'] ?? '',
            'scientificArea' => $b['scientificArea']['value'] ?? ''
        ];
    }

    return false;
}

function insertAuthorWithUser($author_uri, $userID, $data) {
    $userID = uniqid();
    $user_uri = "app:user_". $userID;
    $createdAt = date('Y-m-d\TH:i:s');

    $query = "
    PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
    PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
    
    INSERT DATA {
        <$author_uri> a app:Author ;
            app:orcid \"{$data['orcid_id']}\" ;
            app:firstname \"{$data['firstname']}\" ;
            app:lastname \"{$data['lastname']}\" ;
            app:email \"{$data['email']}\" ;
            app:username \"{$data['username']}\" ;
            app:correspondTo <$user_uri>";
    
        if (!empty($data['affiliation'])) {
            $query .= " ;\n        app:affiliation \"{$data['affiliation']}\"";
        }
        if (!empty($data['scientific_area'])) {
            $query .= " ;\n        app:scientificArea \"{$data['scientific_area']}\"";
        }
        if (!empty($data['nationality'])) {
            $query .= " ;\n        app:nationality \"{$data['nationality']}\"";
        }
    
        $query .= " .\n\n";
    
        $query .= "    <$user_uri> a app:User ;
            app:userID \"$userID\" ;
            app:username \"{$data['username']}\" ;
            app:firstname \"{$data['firstname']}\" ;
            app:lastname \"{$data['lastname']}\" ;
            app:email \"{$data['email']}\" ;
            app:role \"Researcher\" ;
            app:is_active \"1\" ;
            app:isSuspended \"0\" ;
            app:createdAt \"$createdAt\"^^xsd:dateTime ;
            app:isCorrespondingUser <$author_uri> .
    }
    ";

return executeSparqlUpdate($query);

}

function checkUsernameExists($username) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user WHERE {
            ?user a app:User ;
                  app:username \"$username\" .
        } LIMIT 1
    ";

    $result = executeSparqlQuery($sparql);
    return !empty($result['results']['bindings']);
}

function checkEmailExists($email) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?user WHERE {
            ?user a app:User ;
                  app:email \"$email\" .
        } LIMIT 1
    ";

    $result = executeSparqlQuery($sparql);
    return !empty($result['results']['bindings']);
}

function getAuthorURIByOrcid($orcid) {
    $escapedOrcid = addslashes($orcid);
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        SELECT ?a WHERE {
            ?a a app:Author ;
               app:orcid \"$escapedOrcid\" .
        } LIMIT 1
    ";

    $result = executeSparqlQuery($sparql);
    return !empty($result['results']['bindings']) ? $result['results']['bindings'][0]['a']['value'] : null;
}

function createAuthorFromOrcid($orcid, $firstname = '', $lastname = '') {
    $uri = "https://orcid.org/" . $orcid;
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <$uri> a app:Author ;
                  app:orcid \"$orcid\" ;
                  app:firstname \"$firstname\" ;
                  app:lastname \"$lastname\" .
        }
    ";
    executeSparqlUpdate($sparql);
    return $uri;
}

function linkUserToAuthor($userURI, $authorURI) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <$userURI> app:correspondsTo <$authorURI> .
            <$authorURI> app:isCorrespondingUser <$userURI> .
        }
    ";
    return executeSparqlUpdate($sparql);
}

function updateAuthorProfile($authorURI, $fields) {
    $deletes = "";
    $inserts = "";
    foreach ($fields as $predicate => $value) {
        if ($value !== '') {
            $deletes .= "<$authorURI> app:$predicate ?v .\n";
            $inserts .= "<$authorURI> app:$predicate \"" . addslashes($value) . "\" .\n";
        }
    }

    if ($deletes && $inserts) {
        $sparql = "
            PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
            DELETE { $deletes }
            INSERT { $inserts }
            WHERE  { $deletes }
        ";
        return executeSparqlUpdate($sparql);
    }
    return false;
}

function getAuthorDetails($orcid) {
    
    $query = "
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        SELECT ?author ?firstname ?lastname ?orcid ?affiliation ?nationality ?scientificArea
        WHERE {
            ?author a app:Author ;
            app:orcid ?orcid ;
            app:firstname ?firstname ;
            app:lastname ?lastname .
    
        OPTIONAL { ?author app:affiliation ?affiliation }
        OPTIONAL { ?author app:nationality ?nationality }
        OPTIONAL { ?author app:scientificArea ?scientificArea }

        FILTER(STR(?orcid) = \"$orcid\")
    }
    ";

    $response = executeSparqlQuery($query);

    if (!empty($response['results']['bindings'])) {
        $row = $response['results']['bindings'][0];
        return [
            'uri' => $row['author']['value'],
            'firstname' => $row['firstname']['value'],
            'lastname' => $row['lastname']['value'],
            'orcid' => $row['orcid']['value'],
            'affiliation' => $row['affiliation']['value'] ?? '',
            'nationality' => $row['nationality']['value'] ?? '',
            'scientificArea' => $row['scientificArea']['value'] ?? '',
        ];
    }

    return null;
}

function markUserPendingClaimsAsDeleted($userUri) {
    $userUri = trim($userUri);

    if (empty($userUri)) {
        return false;
    }

    $sparql = '
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            ?claim app:claimStatus "Pending" .
        }
        INSERT {
            ?claim app:claimStatus "Cancelled" .
        }
        WHERE {
            ?claim a app:Claim ;
                   app:claimStatus "Pending" ;
                   app:claimedBy <' . $userUri . '> .
        }
    ';

    return executeSparqlUpdate($sparql);
}

?>