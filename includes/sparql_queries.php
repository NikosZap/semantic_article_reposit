<?php 

function getUserByUsername($username) {
    // Ετοίμασε το SPARQL query
    $sparql = "PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

                SELECT ?userID ?username ?password ?role ?firstname ?lastname ?email
                WHERE { 
                    ?user a app:User ;
                        app:userID ?userID ;
                        app:username ?username ;
                        app:password ?password ;
                        app:role ?role ;
                        app:firstname ?firstname ;
                        app:lastname ?lastname ;
                        app:email ?email.
                FILTER(?username = \"$username\")
                }
                LIMIT 1";

    $result = executeSparqlQuery($sparql);

    if (!isset($result['results']['bindings'][0])) {
        return null; // Δεν βρέθηκε χρήστης
    }

    $userData = $result['results']['bindings'][0];

    // Επιστρέφει array όπως έκανε το bind_result
    return ['userID'    => $userData['userID']['value'],
            'username'  => $userData['username']['value'],
            'password'  => $userData['password']['value'],
            'role'      => $userData['role']['value'],
            'firstname' => $userData['firstname']['value'],
            'lastname'  => $userData['lastname']['value'],
            'email'     => $userData['email']['value']];

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
            <$userURI> app:activation_token \"$token\" .
        }
        INSERT {
            <$userURI> app:is_active \"1\" .
        }
        WHERE {
            <$userURI> app:activation_token \"$token\" .
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

       SELECT ?user ?userID ?username ?firstname ?lastname ?email ?role ?createdAt ?is_active
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
            'is_active'  => isset($b['is_active']) ? ($b['is_active']['value'] === '1' ? 1 : 0) : 0
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


?>