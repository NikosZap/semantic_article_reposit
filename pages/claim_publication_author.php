<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if (!isset($_SESSION['user']['orcid']) || empty($_SESSION['user']['orcid'])) {
    $_SESSION['unauthorized_orcid'] = true;
    header("Location: ../pages/login.php");
    exit;
}

if (!isset($_GET['uri'])) {
    die("No publication URI provided.");
}

$pubUri = base64_decode($_GET['uri']);
$publication = getPublicationWithAuthors($pubUri);

if (!$publication) {
    die("Publication not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Semantic Search</title>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-4">Claim Publication</h2>

            <div id="claimMessage"></div>

            <p class="text-muted">You are claiming authorship for the following publication:</p>

            <div class="card p-3 mb-4">
                <h5 class="display-6">
                    <strong><?= htmlspecialchars($publication['title']) ?></strong>
                </h5>
                <p>
                    <strong>Authors:</strong>
                    <?= implode(', ', array_map(fn($a) => $a['firstname'] . ' ' . $a['lastname'], $publication['authors'])) ?>
                </p>
            </div>

            <form method="post" id="claimForm" action="../backend/claim_publication_backend.php">
                <input type="hidden" name="pubUri" value="<?= htmlspecialchars($pubUri) ?>">

                <div class="mb-3">
                    <label for="authorUri" class="form-label">Select the author you claim to be:</label>
                    <select name="authorUri" id="authorUri" class="form-select" required>
                        <option value="">-- Select Author --</option>
                        <?php foreach ($publication['authors'] as $author): ?>
                            <option value="<?= htmlspecialchars($author['uri']) ?>">
                                <?= htmlspecialchars($author['firstname'] . ' ' . $author['lastname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Explain why you're claiming this publication:</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit Claim</button>
            </form>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/claim_publication_author.js"></script>
        
</body>
</html>