<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$results = [];
$selectedUris = [];

if (!empty($_GET['uri']) && is_array($_GET['uri'])) {
    $selectedUris = array_values(array_unique(array_filter($_GET['uri'])));
    $expandedUris = expandConceptUris($selectedUris, 5);
    $relatedOnlyUris = array_values(array_diff($expandedUris, $selectedUris));
    $results = getPublicationsByMentions($expandedUris, $selectedUris, $relatedOnlyUris);
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

            <div class="mb-3">
                <label for="searchText" class="form-label">Enter a phrase or term:</label>
                <input
                    type="search"
                    id="searchText"
                    class="form-control"
                    placeholder="e.g. semantic search"
                >
            </div>

            <div id="resultsContainer" class="mt-4" style="display: <?= $showResultsContainer ? 'block' : 'none' ?>;">
                <h5>Concepts Found:</h5>
                <div id="results"></div>

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
                                            <a href="publication.php?uri=<?= urlencode($pub['uri']) ?>">
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

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <?= !empty($pub['keywords']) && is_array($pub['keywords']) ? implode(', ', $pub['keywords']) : '-' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

        document.getElementById('searchText').addEventListener('keypress', function (e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                triggerSpotlight();
            }
        });

        function triggerSpotlight() {
            const text = document.getElementById('searchText').value.trim();

            if (!text) {
                alert('Please enter a phrase.');
                return;
            }

            fetch('backend/spotlight_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'text=' + encodeURIComponent(text)
            })
                .then(response => response.json())
                .then(entities => {
                    const container = document.getElementById('results');
                    container.innerHTML = '';
                    document.getElementById('resultsContainer').style.display = 'block';

                    if (!entities.length) {
                        container.innerHTML = '<div class="alert alert-warning">No entities found.</div>';
                        return;
                    }

                    let html = '<form method="get" action="semantic_search.php" id="conceptForm">';
                    html += '<div class="list-group">';

                    entities.forEach(ent => {
                        html += `
                            <label class="list-group-item">
                                <input class="form-check-input me-1" type="checkbox" name="uri[]" value="${ent.uri}">
                                ${ent.label} <small class="text-muted">(${ent.uri})</small>
                            </label>
                        `;
                    });

                    html += '</div>';
                    html += '<button class="btn btn-success mt-3" type="submit">Search by Concept</button>';
                    html += '</form>';

                    container.innerHTML = html;

                    const conceptForm = document.getElementById('conceptForm');
                    conceptForm.addEventListener('submit', function (e) {
                        const checked = conceptForm.querySelectorAll('input[name="uri[]"]:checked');

                        if (!checked.length) {
                            e.preventDefault();
                            alert('Please select at least one concept.');
                        }
                    });
                })
                .catch(err => {
                    console.error(err);
                    alert('Error contacting DBpedia Spotlight.');
                });
        }
    </script>
</body>
</html>