<?php
require_once BASE_PATH . 'vendor/autoload.php'; // pdfparser

use Smalot\PdfParser\Parser;

function storeFullTextFromPDF($publicationURI, $pdfFilePath) {
    $parser = new Parser();

    try {
        $pdf = $parser->parseFile($pdfFilePath);
        $rawText = trim($pdf->getText());
    } catch (Exception $e) {
        return null;
    }

    if ($rawText === '') {
        return null;
    }

    $fullTextID = uniqid("fulltext_");
    $fullTextURI = BASE_URI . $fullTextID;

    $sparqlText = escapeForSPARQLLiteral($rawText);
    $sparqlText = addslashes($sparqlText);

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <$publicationURI> app:hasFullText <$fullTextURI> .
            <$fullTextURI> a app:FullText ;
                           app:textContent \"\"\"$sparqlText\"\"\" ;
                           app:isFullTextOf <$publicationURI> .
        }
    ";

    $success = executeSparqlUpdate($sparql);

    if (!$success) {
        return null;
    }

    return $rawText;
}

function escapeForSPARQLLiteral($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('"""', '\\"\\"\\"', $text);
    return $text;
}

function createSolrDocumentFromPublication($pubURI, $title, $fulltext, $abstract) {

    $authorsData = getAuthorsByPublication($pubURI);
    $authors = array_map(function($a) {
        return $a['firstname'] . ' ' . $a['lastname'];
    }, $authorsData);

    $keywords = getKeywordsByPublication($pubURI);
    
    $doc = [
        "id" => basename($pubURI), 
        "publication_uri" => $pubURI,
        "title" => $title,
        "fulltext_content" => $fulltext,
        "abstract" => $abstract,
        "keywords" => $keywords,
        "authors" => $authors
    ];

    //print_r($doc);

    $payload = json_encode([$doc], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init('http://localhost:8983/solr/sem_text_core/update?commit=true');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

function getDBpediaEntities($text, $confidence = 0.35, $support = 5) {
    $endpoint = "https://api.dbpedia-spotlight.org/en/annotate";

    $postData = http_build_query([
        'text' => $text,
        'confidence' => $confidence,
        'support' => $support
    ]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || !empty($curlError)) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $curlError,
            'http_code' => $httpCode,
            'raw_response' => $response,
            'entities' => []
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Unexpected HTTP code ' . $httpCode,
            'http_code' => $httpCode,
            'raw_response' => $response,
            'entities' => []
        ];
    }

    $json = json_decode($response, true);

    if (!is_array($json)) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response',
            'http_code' => $httpCode,
            'raw_response' => $response,
            'entities' => []
        ];
    }

    $entities = [];

    if (isset($json['Resources']) && is_array($json['Resources'])) {
        foreach ($json['Resources'] as $res) {
            $entities[] = [
                'uri' => $res['@URI'] ?? '',
                'label' => $res['@surfaceForm'] ?? '',
                'similarityScore' => $res['@similarityScore'] ?? null,
                'support' => $res['@support'] ?? null
            ];
        }
    }

    return [
        'success' => true,
        'error' => null,
        'http_code' => $httpCode,
        'raw_response' => $response,
        'entities' => $entities
    ];
}

function ensureSpotlightDebugDir($publicationURI) {
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', basename($publicationURI));
    $dir = BASE_PATH . 'debug/spotlight/' . $safeId . '/';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

function writeSpotlightDebugFiles($publicationURI, $files) {
    $dir = ensureSpotlightDebugDir($publicationURI);

    foreach ($files as $filename => $content) {
        if (is_array($content) || is_object($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        file_put_contents($dir . $filename, $content);
    }
}

function deleteSolrDocumentByPublication($pubURI) {
    $docId = basename($pubURI);

    $payload = json_encode([
        'delete' => ['id' => $docId]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init('http://localhost:8983/solr/sem_text_core/update?commit=true');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $response !== false && $httpCode === 200;
}

function deletePublicationCompletely($uri) {
    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>

        DELETE {
            <{$uri}> ?p ?o .
            ?s ?p2 <{$uri}> .

            ?fullText ?fp ?fo .
            ?fs ?fp2 ?fullText .
        }
        WHERE {
            OPTIONAL { <{$uri}> ?p ?o . }
            OPTIONAL { ?s ?p2 <{$uri}> . }

            OPTIONAL {
                <{$uri}> app:hasFullText ?fullText .
                OPTIONAL { ?fullText ?fp ?fo . }
                OPTIONAL { ?fs ?fp2 ?fullText . }
            }
        }
    ";

    return executeSparqlUpdate($sparql);
}

?>