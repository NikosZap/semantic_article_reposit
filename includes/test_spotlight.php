<?php
$text = "Semantic search and linked data are important fields in the semantic web.";
$url = "https://api.dbpedia-spotlight.org/en/annotate?" . http_build_query([
    "text" => $text,
    "confidence" => 0.5,
    "support" => 20
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// ❗❗❗ Παράκαμψη SSL για test (μην το αφήσεις σε παραγωγή!)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL ERROR: $error\n";
} else {
    echo "✅ RAW RESPONSE:\n";
    echo $response;
}