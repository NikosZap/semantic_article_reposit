<?php

require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php'; 

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
    
    <button id="spotlightBtn" class="btn btn-primary mb-3">Find Concepts</button>

    <!-- Container for recognized entities -->
    <div id="entitiesContainer" class="mb-4" style="display:none;">
      <h5>Select a concept to search for related articles:</h5>
      <div id="entityList" class="form-check"></div>
      <button id="searchByConcept" class="btn btn-success mt-2">Search Articles</button>
    </div>

    <!-- Results -->
    <div id="resultsContainer" class="mt-4" style="display:none;">
      <h5>Results:</h5>
      <div id="results"></div>
    </div>
  </div>
  <div class="container">
     <?php include(BASE_PATH . 'includes/footer.php')?> 
    </div>
  </body>
  <script>
    // Call DBpedia Spotlight
    $('#spotlightBtn').on('click', function () {
      const text = $('#searchText').val().trim();
      if (!text) return alert('Please enter a phrase.');

      $.post('spotlight_backend.php', { text: text }, function (data) {
        const entities = JSON.parse(data);
        $('#entityList').empty();
        if (entities.length === 0) {
          alert('No entities found.');
          return;
        }

        entities.forEach((ent, index) => {
          $('#entityList').append(`
            <div class="form-check">
              <input class="form-check-input" type="radio" name="selectedEntity" id="ent${index}" value="${ent.uri}">
              <label class="form-check-label" for="ent${index}">${ent.label} <br><small>${ent.uri}</small></label>
            </div>
          `);
        });

        $('#entitiesContainer').show();
      });
    });

    // Search articles by selected entity URI
    $('#searchByConcept').on('click', function () {
      const selected = $('input[name="selectedEntity"]:checked').val();
      if (!selected) return alert('Please select a concept.');

      $.post('semantic_search_backend.php', { uri: selected }, function (data) {
        const results = JSON.parse(data);
        $('#results').empty();
        if (results.length === 0) {
          $('#results').append('<p>No articles found for this concept.</p>');
        } else {
          results.forEach(res => {
            $('#results').append(`
              <div class="card mb-2 p-2">
                <h5><a href="publication.php?uri=${encodeURIComponent(res.uri)}">${res.title}</a></h5>
                <p><strong>Authors:</strong> ${res.authors.join(', ')}</p>
              </div>
            `);
          });
        }
        $('#resultsContainer').show();
      });
    });
  </script>

</html>