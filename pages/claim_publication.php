<?php

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php'; 


if (!isset($_SESSION['user']['orcid']) || empty($_SESSION['user']['orcid'])) {
    $_SESSION['unauthorized_orcid'] = true;
    header("Location: ../pages/login.php");
    exit;
 }

$searchTerm = $_GET['search'] ?? '';
$publications = [];

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);


if (!empty($searchTerm)) {
   $publications = searchPublicationsWithAuthors($searchTerm);
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
  <?php include(BASE_PATH . 'includes/header.php')?>
		<title>Claim a Publication</title>
	</head>
	<body>
<?php include(BASE_PATH . 'includes/navbar.php')?>
		<main>
			<div class="container mt-5">
				<h2 class="display-4">Claim Publication</h2>

  <?php if ($flash): ?>
				<div id="flashMessage" class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['text']) ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert">
				</div>
<?php endif; ?>
				<form method="get" class="mb-4">
					<div class="input-group">
						<input type="text" name="search" class="form-control" placeholder="Enter publication title" value="<?= htmlspecialchars($searchTerm) ?>">
							<button class="btn btn-primary" type="submit">Search</button>
						</div>
					</form>

    <?php if ($searchTerm && empty($publications)): ?>
					<div class="alert alert-warning">No results found for "<?= htmlspecialchars($searchTerm) ?>".</div>
    <?php endif; ?>

    <?php foreach ($publications as $pub): ?>
					<div class="card mb-3 p-3">
						<h5>
							<a href="../publication.php?uri=<?= urlencode($pub['uri']) ?>">
                <?= htmlspecialchars($pub['title']) ?>
							</a>
						</h5>

            <?php if (!empty($pub['authors'])): ?>
						<p>
							<strong>Authors:</strong> <?= implode(', ', array_map(fn($a) => trim(($a['firstname'] ?? '') . ' ' . ($a['lastname'] ?? '')), $pub['authors'])) ?>
						</p>
						<a href="claim_publication_author.php?uri=<?= urlencode($pub['uri']) ?>" class="btn btn-outline-success btn-sm">Claim this publication</a>
            <?php else: ?>
						<p class="text-muted">No authors found.</p>
            <?php endif; ?>
					</div>
    <?php endforeach; ?>
				</main>
				<div class="container">
    <?php include(BASE_PATH . 'includes/footer.php')?>
				</div>
			</body>
			<script>
document.addEventListener("DOMContentLoaded", function() {
    const alertBox = document.getElementById("flashMessage");

    if (alertBox) {
        setTimeout(function () {
            alertBox.classList.remove("show");
            alertBox.classList.add("fade");

            setTimeout(function () {
                alertBox.remove();
            }, 500); 
        }, 3000); 
    }
});
</script>
		</html>
