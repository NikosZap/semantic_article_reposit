<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if ($q !== '') {
    $url = 'http://localhost:8983/solr/sem_text_core/select?q=fulltext_content:' . urlencode($q) . '&wt=json';
    $response = @file_get_contents($url);

    if ($response === false) {
        $solrError = true;
    } else {
        $solrError = false;
        $data = json_decode($response, true);
        $results = $data['response']['docs'];
    }

}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <?php include(BASE_PATH . 'includes/header.php')?> 
    <title>Search Results</title>
</head>
<body>
<?php include(BASE_PATH . 'includes/navbar.php')?> 

<div class="container mt-5">
    <form class="d-flex justify-content-center mb-5" action="search_results.php" method="get">
        <input type="search" name="q" placeholder="Search..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"  class="form-control"  required>
    </form>

    <h4>Results</h4>
    <?php if (count($results) === 0): ?>
        <p>No results found</p>
    <?php endif; ?>

    <?php foreach ($results as $doc): ?>
        <div class="card shadow rounded-4 p-3 mb-4 row">
            
            <div class="col-md-12">
                <h5><strong><a href="publication.php?uri=<?= urlencode($doc['publication_uri']) ?>">
                    <?= htmlspecialchars($doc['title'] ?? 'Unknown title') ?></a></strong></h5>
                <p><?= isset($doc['authors']) ? implode(', ', $doc['authors']) : 'Unknown Authors' ?></p>
                <div class="mb-2">
                    <small><?= htmlspecialchars(mb_substr($doc['abstract'], 0, 400)) ?>...</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted"><?= isset($doc['keywords']) ? implode(', ', $doc['keywords']) : '-' ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
