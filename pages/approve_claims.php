<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Publication Claims</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-6">Publication Claims</h2>

            <div id="message"></div>

            <div id="flashMessage">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['status']) ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date Uploaded</th>
                        <th>Claimed Author</th>
                        <th>Claimed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="claimPubTableBody"></tbody>
            </table>

            
        </div>
    </main>

    <div class="container">
        <div id="pagination" class="mt-3"></div>
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/approve_claims.js"></script>
        
</body>
</html>