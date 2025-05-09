<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$text = $_POST['text'] ?? '';
if (empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing text']);
    exit;
}

$endpoint = 'https://api.dbpedia-spotlight.org/en/annotate';
$confidence = 0.5;
$support = 20;

$headers = [
    'Accept: application/json'
];

$params = http_build_query([
    'text' => $text,
    'confidence' => $confidence,
    'support' => $support
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Για XAMPP/dev περιβάλλον

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'CURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);
$entities = [];

if (isset($data['Resources'])) {
    foreach ($data['Resources'] as $res) {
        $entities[] = [
            'uri' => $res['@URI'],
            'label' => $res['@surfaceForm']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($entities);
?>
