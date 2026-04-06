<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$keyword = trim($_GET['keyword'] ?? '');

$results = [];

if (!empty($keyword)) {
    $results = getPublicationsByKeyword($keyword);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?=$keyword?></title>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main class="flex-grow-1">
        <div class="container mt-5">
            <h2 class="display-6">
                    <?= htmlspecialchars($keyword ?: 'Unknown Keyword') ?>
            </h2>

            <?php if (empty($keyword)): ?>
                <div class="alert alert-warning">No keyword selected.</div>
            <?php elseif (empty($results)): ?>
                <div class="alert alert-info">No publications found for this keyword.</div>
            <?php else: ?>
                        <?php foreach ($results as $pub): ?>
                            <div class="card shadow rounded-4 mb-4 search-result-card">
                                <div class="card-body p-4">
                                    <h5>
                                        <strong>
                                            <a href="publication.php?uri=<?= base64_encode($pub['uri']) ?>">
                                                <?= htmlspecialchars($pub['title'] ?? 'Unknown title') ?>
                                            </a>
                                        </strong>
                                    </h5>
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
            <?php endif; ?>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>
</body>
</html>