<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$text = trim($_POST['text'] ?? '');

if ($text === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing text']);
    exit;
}

$endpoint = 'http://192.168.1.214:2222/rest/annotate';
$confidence = 0.5;
$support = 20;

function callSpotlight(string $endpoint, string $text, float $confidence, int $support): array
{
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('CURL error: ' . $error);
    }

    curl_close($ch);

    $data = json_decode($response, true);
    $entities = [];

    if (isset($data['Resources']) && is_array($data['Resources'])) {
        foreach ($data['Resources'] as $res) {
            if (!empty($res['@URI']) && !empty($res['@surfaceForm'])) {
                $entities[] = [
                    'uri' => $res['@URI'],
                    'label' => $res['@surfaceForm']
                ];
            }
        }
    }

    return $entities;
}

function uniqueEntities(array $entities): array
{
    $unique = [];

    foreach ($entities as $entity) {
        if (!isset($entity['uri'])) {
            continue;
        }

        $unique[$entity['uri']] = $entity;
    }

    return array_values($unique);
}

try {
    $normalizedSpaces = preg_replace('/\s+/', ' ', $text);

    $variants = array_values(array_unique([
        $text,
        $normalizedSpaces,
        mb_convert_case($normalizedSpaces, MB_CASE_TITLE, 'UTF-8'),
        mb_strtolower($normalizedSpaces, 'UTF-8'),
        mb_strtoupper($normalizedSpaces, 'UTF-8')
    ]));

    $allEntities = [];

    foreach ($variants as $variant) {
        $variant = trim($variant);

        if ($variant === '') {
            continue;
        }

        $entities = callSpotlight($endpoint, $variant, $confidence, $support);

        if (!empty($entities)) {
            $allEntities = array_merge($allEntities, $entities);
        }
    }

    $allEntities = uniqueEntities($allEntities);

    header('Content-Type: application/json');
    echo json_encode($allEntities);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}