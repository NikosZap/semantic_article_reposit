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
			<div class="container py-5">
                <div class="text-center mb-5">
                    <h1 class="display-5">Scientific Publication Search Platform</h1>
                    <p class="lead text-muted">
                        Choose how you want to explore publications in the system.
                    </p>
                </div>

                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body p-4 text-center">
                                <h3 class="h4 mb-3">Keyword Search</h3>
                                <p class="text-muted">
                                    Search publications using keywords and full-text indexed content.
                                </p>
                                <a href="fulltext_search.php" class="btn btn-outline-dark"">
                                    Go to Keyword Search
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body p-4 text-center">
                                <h3 class="h4 mb-3">Semantic Search</h3>
                                <p class="text-muted">
                                    Search publications through concepts and entities recognized semantically.
                                </p>
                                <a href="semantic_search.php" class="btn btn-outline-dark">
                                    Go to Semantic Search
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
		</main>
		<div class="container mt-auto">
        <?php include(BASE_PATH . 'includes/footer.php')?>
		</div>
	</body>
</html>