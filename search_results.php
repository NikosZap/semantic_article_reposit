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
		<main class="flex-grow-1">
			<div class="container mt-5">
				<form class="d-flex justify-content-center mb-5" action="search_results.php" method="get">
					<input type="search" name="q" placeholder="Search..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" required>
				</form>
				<h4>Results</h4>
    <?php if (count($results) === 0): ?>
				<p>No results found</p>
    <?php endif; ?>

<?php foreach ($results as $doc): ?>
    <?php
        $title = htmlspecialchars($doc['title'] ?? 'Unknown title');
        $pubUri = urlencode($doc['publication_uri'] ?? '');
        $authors = isset($doc['authors']) && is_array($doc['authors'])
            ? implode(', ', $doc['authors'])
            : 'Unknown Authors';

        $abstract = !empty($doc['abstract'])
            ? mb_substr($doc['abstract'], 0, 350) . (mb_strlen($doc['abstract']) > 350 ? '...' : '')
            : 'No abstract available.';

        $keywords = isset($doc['keywords']) && is_array($doc['keywords'])
            ? $doc['keywords']
            : [];
    ?>
				<div class="card shadow rounded-4 mb-4 search-result-card">
					<div class="card-body p-4">
						<h4 class="mb-3">
							<a href="publication.php?uri=<?= $pubUri ?>" class="text-decoration-underline fw-bold">
                    <?= $title ?>
							</a>
						</h4>
						<div class="row g-4">
							<div class="col-md-7">
								<h5>Abstract</h5>
								<div class="bg-light p-3 rounded shadow-sm" style="max-height: 220px; overflow-y: auto;">
                        <?= htmlspecialchars($abstract) ?>
								</div>
							</div>
							<div class="col-md-5">
								<ul class="list-group list-group-flush">
									<li class="list-group-item">
										<strong>Authors:</strong>
										<br>
                            <?= htmlspecialchars($authors) ?>
										</li>
										<li class="list-group-item">
											<strong>Keywords:</strong>
											<br>
                            <?php if (!empty($keywords)): ?>
                                <?php foreach ($keywords as $kw): ?>
												<span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($kw) ?>
												</span>
                                <?php endforeach; ?>
                            <?php else: ?>
												<span>-</span>
                            <?php endif; ?>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
<?php endforeach; ?>
					</div>
				</main>
				<div class="container">
    <?php include(BASE_PATH . 'includes/footer.php')?>
				</div>
			</body>
		</html>
