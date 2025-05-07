<?php 
    require_once dirname(__DIR__) . '/my_project2/config.php';
?>    
<!doctype html>
<html lang="en">

<head>
    <?php include(BASE_PATH . 'includes/header.php')?> 
    <title>Semantic demo</title>
</head>

<body>
     <?php include(BASE_PATH . 'includes/navbar.php')?>
    <!-- Main Content -->
    <div class="container text-center mt-5">
        <h1 class="display-4">Semantic Article Repository System</h1>
        <p class="lead">This is a simple draft page.</p>
    </div>
    <div class="container text-center mt-5">
        <div class="mb-3">
            <form action="search_results.php" method="get">
                <input type="search" name="q" placeholder="Search..."  class="form-control"  required>
            </form>
        </div>
    </div>


    <div class="container">
     <?php include(BASE_PATH . 'includes/footer.php')?> 
    </div>
</body>

</html>