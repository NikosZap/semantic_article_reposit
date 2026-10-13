<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$start = ($page - 1) * $limit;
$totalResults = 0;
$totalPages = 0;
$solrError = false;

if ($q !== '') {

    $params = http_build_query([
		'q' => 'fulltext_content:' . $q,
		'start' => $start,
		'rows' => $limit,
		'wt' => 'json',
		'fl' => '*,score'
	]);

	$url = SOLR_SELECT_ENDPOINT . '?' . $params;
	
	$response = @file_get_contents($url);

    if ($response === false) {
        $solrError = true;
    } else {
        $solrError = false;
        $data = json_decode($response, true);
        $results = $data['response']['docs'];

		$totalResults = (int)($data['response']['numFound'] ?? 0);
		$totalPages = (int)ceil($totalResults / $limit);
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
					<div class="input-group">
						<input type="search" name="q" placeholder="Search..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" required>
						<button class="btn btn-outline-secondary" type="submit" aria-label="Search">
            				<i class="bi bi-search"></i>
        				</button>
					</div>	
				</form>
				<h4>Results</h4>

				<?php if ($q !== ''): ?>
					<?php if ($solrError): ?>
						<div class="alert alert-danger">Unable to connect to Solr.</div>
					<?php elseif ($totalResults === 0): ?>
						<div class="alert alert-warning">No results found.</div>
					<?php else: ?>
						<?php
							$fromResult = $start + 1;
							$toResult = min($start + $limit, $totalResults);
						?>
						<div class="text-muted mb-3">
							Showing <?= $fromResult ?>-<?= $toResult ?> of <?= $totalResults ?> results
						</div>
					<?php endif; ?>
				<?php endif; ?>

<?php foreach ($results as $doc): ?>
    <?php
        $title = htmlspecialchars($doc['title'] ?? 'Unknown title');
        $pubUri = base64_encode($doc['publication_uri'] ?? '');
        $authors = isset($doc['authors']) && is_array($doc['authors'])
            ? implode(', ', $doc['authors'])
            : 'Unknown Authors';

        $abstract = !empty($doc['abstract'])
            ? mb_substr($doc['abstract'], 0, 350) . (mb_strlen($doc['abstract']) > 350 ? '...' : '')
            : 'No abstract available.';

        $keywords = isset($doc['keywords']) && is_array($doc['keywords'])
            ? $doc['keywords']
            : [];

		$solrScore = isset($doc['score']) ? round((float)$doc['score'], 2) : 0;			
    ?>
				<div class="card shadow rounded-4 mb-4 search-result-card">
					<div class="card-body p-4">
						<h4 class="mb-3">
							<a href="publication.php?uri=<?= $pubUri ?>" class="text-decoration-underline fw-bold">
                    <?= $title ?>
							</a>
						</h4>
						<div class="d-flex justify-content-end mb-2">
							<small class="text-muted">
								Relevance score: <?= $solrScore ?>
							</small>
						</div>
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
							<div class="d-flex flex-wrap align-items-center gap-2">
                                        <?php if (!empty($keywords) && is_array($keywords)): ?>    
                                          <?php foreach ($keywords as $index => $keyword): ?>
                                          <span class="badge bg-light text-dark border me-1 text-decoration-none">
                                            <a href="keyword.php?keyword=<?= urlencode($keyword) ?>"
                                              class="text-dark  text-decoration-none">
                                              <?= htmlspecialchars($keyword) ?>
                                            </a>
                                          </span>  
                                          <?php endforeach; ?>
                                        <?php else: ?>
                                              -
                                        <?php endif; ?>
                                        
                                    </div>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
<?php endforeach; ?>

<?php if (!$solrError && $totalPages > 1): ?>
    <nav aria-label="Search results pages">
        <ul class="pagination justify-content-center mt-4">

            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link text-dark" href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>">Back</a>
            </li>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
					<a class="page-link <?= $i == $page ? 'bg-dark text-white border-dark' : 'text-dark ' ?>" 
					href="?q=<?= urlencode($q) ?>&page=<?= $i ?>">
						<?= $i ?>
					</a>
				</li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link text-dark" href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>">Next</a>
            </li>

        </ul>
    </nav>
<?php endif; ?>

					</div>
				</main>
				<div class="container">
    				<?php include(BASE_PATH . 'includes/footer.php')?>
				</div>
			</body>
		</html>
