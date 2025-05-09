<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php'; 

$results = [];
if (isset($_GET['uri'])) {
    $results = getPublicationsByMention($_GET['uri']); // Η συνάρτηση πρέπει να επιστρέφει πίνακα άρθρων με τίτλο και URI
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Semantic Search</title>
  <?php include(BASE_PATH . 'includes/header.php')?> 
</head>
<body>
<?php include(BASE_PATH . 'includes/navbar.php')?> 
  <div class="container mt-5">
    <h2 class="display-4">Semantic Search with DBpedia Spotlight</h2>
    
    <div class="mb-3">
      <label for="searchText" class="form-label">Enter a phrase or term:</label>
      <input type="search" id="searchText" class="form-control" placeholder="e.g. semantic search">
    </div>
    
     <!-- Results -->
     <div id="resultsContainer" class="mt-4" style="display:none;">
      <h5>Concepts Found:</h5>
      <div id="results"></div>
    </div>

    <?php if (!empty($results)): ?>
      <div class="mt-5">
        <h4>Publications mentioning the selected concept:</h4>
        
          <?php foreach ($results as $pub): ?>
           
            <div class="card shadow rounded-4 p-3 mb-4 row">
            
            <div class="col-md-12">
                <h5><strong><a href="publication.php?uri=<?= urlencode($pub['uri']) ?>">
                    <?= htmlspecialchars($pub['title'] ?? 'Unknown title') ?></a></strong></h5>
                <p><?= implode(', ', array_map(fn($a) => $a['firstname'] . ' ' . $a['lastname'], $pub['authors'])) ?></p>
                <div class="mb-2">
                    <small><?= htmlspecialchars(mb_substr($pub['abstract'], 0, 400)) ?>...</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                <?= !empty($pub['keywords']) && is_array($pub['keywords']) ? implode(', ', $pub['keywords']) : '-' ?></span>
                </div>
            </div>
        </div>


          <?php endforeach; ?>
        
      </div>
    <?php endif; ?>
  </div>

  <div class="container">
    <?php include(BASE_PATH . 'includes/footer.php')?> 
  </div>
</body>

  <script>
    document.getElementById('searchText').addEventListener('keypress', function(e) {
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
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    let html = '<form method="get" action="semantic_search.php">';
    html += '<div class="list-group">';
    entities.forEach(ent => {
      html += `
        <label class="list-group-item">
          <input class="form-check-input me-1" type="radio" name="uri" value="${ent.uri}">
          ${ent.label} <small class="text-muted">(${ent.uri})</small>
        </label>
      `;
    });
    html += '</div>';
    html += '<button class="btn btn-success mt-3" type="submit">Search by Concept</button>';
    html += '</form>';

    container.innerHTML = html;
  })
  .catch(err => {
    console.error(err);
    alert('Error contacting DBpedia Spotlight.');
  });
}
  </script>

</html>

<?php
/*
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
*/
?>