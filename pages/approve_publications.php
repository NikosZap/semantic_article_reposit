<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Approve Publication</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-6">Pending Publications</h2>

            <div id="message"></div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>DOI</th>
                            <th>Category</th>
                            <th>Upload User</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="unapprovedPubTableBody"></tbody>
                </table>
            </div>
            
        </div>
    </main>

    <div class="container mt-5">
        <div id="pagination" class="mt-3"></div>
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/approve_publication.js"></script>
        
</body>
</html>