<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$results = [];
$selectedUris = [];

$mode = $_GET['mode'] ?? 'or';

$showResultsContainer = false;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$totalResults = 0;
$totalPages = 0;

if (!empty($_GET['uri']) && is_array($_GET['uri'])) {
    $selectedUris = array_values(array_unique(array_filter($_GET['uri'])));
    $expandedUris = expandConceptUris($selectedUris, 8);
    $relatedOnlyUris = array_values(array_diff($expandedUris, $selectedUris));

    $totalResults = countPublicationsByMentions($expandedUris, $selectedUris, $mode);
    $totalPages = (int) ceil($totalResults / $limit);

    $results = getPublicationsByMentions( $expandedUris, $selectedUris, $relatedOnlyUris, $mode, $offset, $limit );


    $showResultsContainer = true;
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Semantic Search</title>
    <?php include(BASE_PATH . 'includes/header.php'); ?>

    <style>
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-4">Semantic Search with DBpedia Spotlight</h2>
            <br>
            <div class="mb-3">
                <label for="searchText" class="form-label">Enter a phrase or term:</label>
                <div class="input-group">
                    <input type="search" id="searchText"  class="form-control" placeholder="e.g. semantic search" >
                    <button class="btn btn-outline-secondary"  type="button" onclick="triggerSpotlight()" aria-label="Search" >
                        <i class="bi bi-search"></i>
                    </button>
                 </div>
            </div>

            <div id="resultsContainer" class="mt-4" style="display: <?= $showResultsContainer ? 'block' : 'none' ?>;">
                
                <div id="results"></div>
            <?php if ($showResultsContainer): ?>
                <?php if (!empty($results)): ?>
                    <div class="mt-5">
                        <h4>Publications mentioning the selected concept:</h4>

                        <?php foreach ($results as $pub): ?>
                            <?php
                            $exactTooltip = '<ul class="mb-0 ps-3"><li>' .
                                implode('</li><li>', array_map('htmlspecialchars', $pub['exactConcepts'])) .
                                '</li></ul>';

                            $relatedTooltip = '<ul class="mb-0 ps-3"><li>' .
                                implode('</li><li>', array_map('htmlspecialchars', $pub['relatedConcepts'])) .
                                '</li></ul>';
                            ?>

                            <div class="card shadow rounded-4 mb-4 search-result-card">
                                <div class="card-body p-4">
                                    <h5>
                                        <strong>
                                            <a href="publication.php?uri=<?= base64_encode($pub['uri']) ?>">
                                                <?= htmlspecialchars($pub['title'] ?? 'Unknown title') ?>
                                            </a>
                                        </strong>
                                    </h5>

                                    <div class="d-flex justify-content-between align-items-center mt-1 mb-2">
                                        <div class="d-flex gap-2">
                                            <span
                                                class="badge bg-light text-dark border"
                                                data-bs-toggle="tooltip"
                                                data-bs-html="true"
                                                title='<?= $exactTooltip ?>'
                                            >
                                                <?= $pub['exactMatchCount'] ?> exact match<?= $pub['exactMatchCount'] != 1 ? 'es' : '' ?>
                                            </span>

                                            <span
                                                class="badge bg-light text-dark border"
                                                data-bs-toggle="tooltip"
                                                data-bs-html="true"
                                                title='<?= $relatedTooltip ?>'
                                            >
                                                <?= $pub['relatedMatchCount'] ?> related match<?= $pub['relatedMatchCount'] != 1 ? 'es' : '' ?>
                                            </span>
                                        </div>

                                        <small class="text-muted">
                                            Semantic relevance score: <?= $pub['semanticScore'] ?? 0 ?>
                                        </small>
                                    </div>

                                    <p>
                                        <?= implode(', ', array_map(fn($a) => $a['firstname'] . ' ' . $a['lastname'], $pub['authors'])) ?>
                                    </p>

                                    <div class="mb-2">
                                        <small>
                                            <?= htmlspecialchars(mb_substr($pub['abstract'], 0, 400)) ?>...
                                        </small>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <?php if (!empty($pub['keywords']) && is_array($pub['keywords'])): ?>    
                                          <?php foreach ($pub['keywords'] as $index => $keyword): ?>
                                          <span class="badge bg-light text-dark border me-1 text-decoration-none">
                                            <a href="keyword.php?keyword=<?= urlencode($keyword) ?>"
                                              class="text-dark text-decoration-none">
                                              <?= htmlspecialchars($keyword) ?>
                                            </a>
                                          </span>  
                                          <?php endforeach; ?>
                                        <?php else: ?>
                                              -
                                        <?php endif; ?>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($totalPages > 1): 
                            
                                $queryParams = $_GET;
                            ?>
                            <nav aria-label="Semantic search pages">
                                <ul class="pagination justify-content-center mt-4">

                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <?php $queryParams['page'] = $page - 1; ?>
                                        <a class="page-link text-dark" href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">Back</a>
                                    </li>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php $queryParams['page'] = $i; ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link <?= $i == $page ? 'bg-dark text-white border-dark' : 'text-dark' ?>"
                                            href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <?php $queryParams['page'] = $page + 1; ?>
                                        <a class="page-link text-dark" href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">Next</a>
                                    </li>

                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        No publications matched the selected concepts.
                    </div>
                <?php endif; ?>
            <?php endif; ?>    
            </div>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/semantic_search.js"></script>
</body>
</html>