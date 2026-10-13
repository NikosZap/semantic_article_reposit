<?php 
    require_once dirname(__DIR__) . '/my_project2/config.php';
?>    
<!doctype html>
<html lang="en">
	<head>
    <?php include(BASE_PATH . 'includes/header.php')?>
		<title>Semantic demo</title>
	</head>
	<body class="d-flex flex-column min-vh-100">
    <?php include(BASE_PATH . 'includes/navbar.php')?>
		<main class="flex-grow-1">
			<div class="container text-center mt-5">
				<h1 class="display-4">Semantic Article Repository System</h1>
				<p class="lead">
                Search scientific publications in the repository using full-text search powered by Apache Solr.
            </p>
			</div>
			<div class="container text-center mt-5">
				<div class="mb-3">
					<form action="search_results.php" method="get">
    					<div class="input-group">
        					<input type="search" name="q" placeholder="Search..."  class="form-control" required>
        					<button class="btn btn-outline-secondary" type="submit" aria-label="Search">
            					<i class="bi bi-search"></i>
        					</button>
    					</div>
					</form>
				</div>
			</div>
		</main>
		<div class="container mt-auto">
        <?php include(BASE_PATH . 'includes/footer.php')?>
		</div>
	</body>
</html>