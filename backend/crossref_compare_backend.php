<?php
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$doi = $_GET['doi'] ?? '';
$doi = trim($doi);

// Basic validation
if ($doi === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing DOI']);
    exit;
}

// If user pasted a DOI URL, extract the DOI part
$doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
$doi = trim($doi);

// Crossref endpoint
$url = 'https://api.crossref.org/works/' . rawurlencode($doi);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        // Optional but nice-to-have: identify your app (replace email if you want)
        'User-Agent: SemanticApp/1.0 (mailto:example@example.com)'
    ],
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => 'error',
        'message' => 'Crossref request failed',
        'httpCode' => $httpCode
    ]);
    exit;
}

$data = json_decode($response, true);
$message = $data['message'] ?? null;

if (!$message) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Crossref response']);
    exit;
}

/**
 * Helpers
 */
$first = function($arr) {
    return (is_array($arr) && count($arr) > 0) ? $arr[0] : '';
};

$formatDateParts = function($parts) {
    // parts like [[YYYY, MM, DD]] or [[YYYY, MM]]...
    if (!is_array($parts) || !isset($parts[0]) || !is_array($parts[0])) return '';
    $p = $parts[0];
    $y = $p[0] ?? null;
    $m = $p[1] ?? null;
    $d = $p[2] ?? null;
    if (!$y) return '';
    if ($m && $d) return sprintf('%04d-%02d-%02d', $y, $m, $d);
    if ($m) return sprintf('%04d-%02d', $y, $m);
    return sprintf('%04d', $y);
};

$getBestDate = function($msg) use ($formatDateParts) {
    // Try common fields in order
    $candidates = [
        $msg['published-print']['date-parts'] ?? null,
        $msg['published-online']['date-parts'] ?? null,
        $msg['issued']['date-parts'] ?? null,
        $msg['created']['date-time'] ?? null,
    ];
    foreach ($candidates as $c) {
        if (is_array($c)) {
            $d = $formatDateParts($c);
            if ($d !== '') return $d;
        } elseif (is_string($c) && $c !== '') {
            // date-time -> keep YYYY-MM-DD
            return substr($c, 0, 10);
        }
    }
    return '';
};

$crossrefAuthors = [];
if (!empty($message['author']) && is_array($message['author'])) {
    foreach ($message['author'] as $a) {
        $given = trim($a['given'] ?? '');
        $family = trim($a['family'] ?? '');
        $full = trim($given . ' ' . $family);
        if ($full !== '') $crossrefAuthors[] = $full;
    }
}

$title = html_entity_decode(
            html_entity_decode($first($message['title'] ?? []), ENT_QUOTES | ENT_HTML5),
         ENT_QUOTES | ENT_HTML5);

$normalized = [
    'title' => $title,
    'doi' => $message['DOI'] ?? $doi,
    'type' => $message['type'] ?? '',
    'publicationDate' => $getBestDate($message),
    'journal' => $first($message['container-title'] ?? []),
    'publisher' => $message['publisher'] ?? '',
    'volume' => $message['volume'] ?? '',
    'pages' => $message['page'] ?? '',
    'isbn' => $first($message['ISBN'] ?? []),
    'issn' => $first($message['ISSN'] ?? []),
    'authors' => $crossrefAuthors,
];

echo json_encode([
    'status' => 'success',
    'crossref' => $normalized,
    // (optional) keep raw for debugging:
    // 'raw' => $message
]);