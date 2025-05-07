<?php
require_once BASE_PATH . 'vendor/autoload.php'; // pdfparser

use Smalot\PdfParser\Parser;

function storeFullTextFromPDF($publicationURI, $pdfFilePath) {
    $parser = new Parser();

    try {
        $pdf = $parser->parseFile($pdfFilePath);
        $text = $pdf->getText();
    } catch (Exception $e) {
        return null;
    }

   
    $fullTextID = uniqid("fulltext_");
    $fullTextURI = BASE_URI . $fullTextID;

    $$text = escapeForSPARQLLiteral($text);

    $text = addslashes($text); 

    $sparql = "
        PREFIX app: <http://www.semanticweb.org/nikos/ontologies/2025/2/semanticApp#>
        INSERT DATA {
            <$publicationURI> app:hasFullText <$fullTextURI> .
            <$fullTextURI> a app:FullText ;
                           app:textContent \"\"\"$text\"\"\" ;
                           app:isFullTextOf <$publicationURI> .
        }
    ";

    $success = executeSparqlUpdate($sparql);

    return $text;
}
function escapeForSPARQLLiteral($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('"', '\"', $text);
    $text = str_replace("\n", '\\n', $text);
    $text = str_replace("\r", '\\r', $text);
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

    print_r($doc);

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

function getDBpediaEntities($text, $confidence = 0.5, $support = 20) {
    $endpoint = "https://api.dbpedia-spotlight.org/en/annotate";

    $url = $endpoint . "?" . http_build_query([
        "text" => $text,
        "confidence" => $confidence,
        "support" => $support
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    file_put_contents("spotlight_raw.txt", $response . "\n\n", FILE_APPEND);

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    }

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);
    
    $json = json_decode($response, true);

    $entities = [];

    if (isset($json['Resources'])) {
        foreach ($json['Resources'] as $res) {
            $entities[] = [
                'uri' => $res['@URI'],
                'label' => $res['@surfaceForm']
            ];
        }
    }

    return $entities;
}


?>