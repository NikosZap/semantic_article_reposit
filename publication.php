<?php
require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if (!isset($_GET['uri'])) {
	echo '<p>Error: Publication URI is missing.</p>';
	exit;
}

$pubURI = urldecode($_GET['uri']);
$publication = getPublicationByURI($pubURI);

if (!$publication) {
	echo '<p>No data found for the given publication.</p>';
	exit;
}

$authors = getAuthorsByPublication($pubURI);
if (!$authors) {
	echo '<p>Error: Authors not found.</p>';
	exit;
}

$keywords = getKeywordsByPublication($pubURI);
?>

<!DOCTYPE html>
<html lang="en">

	<head>
		<?php include (BASE_PATH . 'includes/header.php'); ?>
		<title><?= htmlspecialchars($publication['title']) ?></title>
	</head>

	<body>

	<?php include (BASE_PATH . 'includes/navbar.php'); ?>

		<main>

			<div class="container mt-5 mb-5">

				<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">

					<h2 class="display-6"><?= htmlspecialchars($publication['title']) ?></h2>

					<?php if (!empty($publication['filePath'])): ?>

					<a href="<?= BASE_URL . 'pending/' . htmlspecialchars($publication['filePath']) ?>" class="btn btn-outline-primary" target="_blank">
						View PDF <i class="bi bi-file-earmark-pdf"></i>
					</a>

					<?php endif; ?>

				</div>

				<div class="card shadow rounded-4">

					<div class="card-body">

						<div class="row">

							<div class="col-md-7">

								<h5>Abstract</h5>

								<div class="bg-light p-3 rounded shadow-sm text-justify" style="max-height: 300px; overflow-y: auto;">
									<?= nl2br(htmlspecialchars($publication['abstract'] ?? 'No abstract available.')) ?>
								</div>

							</div>

<div class="col-md-5">

<ul class="list-group list-group-flush">

<li class="list-group-item">

<strong>Authors:</strong>

<?php foreach ($authors as $index => $author): ?>

<?php
	$fullName = htmlspecialchars($author['firstname'] . ' ' . $author['lastname']);

	if (!empty($author['orcid'])) {
		$orcidID = urlencode($author['orcid']);

		echo "<a href='author.php?orcid=$orcidID'>$fullName</a>";
	} else {
		echo "$fullName<br>";
	}
?>

<?php endforeach; ?>

</li>

<li class="list-group-item">

<strong>Keywords:</strong>

<div class="d-flex flex-wrap align-items-center gap-2">
                                        <?php if (!empty($keywords) && is_array($keywords)): ?>    
                                          <?php foreach ($keywords as $index => $keyword): ?>
                                          <span class="badge bg-secondary me-1">
                                            <a href="keyword.php?keyword=<?= urlencode($keyword) ?>"
                                              class="text-white text-decoration-none">
                                              <?= htmlspecialchars($keyword) ?>
                                            </a>
                                          </span>  
                                          <?php endforeach; ?>
                                        <?php else: ?>
                                              -
                                        <?php endif; ?>
                                        
                                    </div>

</li>

<li class="list-group-item">

<strong>Type:</strong>
<?= htmlspecialchars(extractSuffix($publication['type'])) ?>

</li>

<li class="list-group-item">

<strong>DOI:</strong>
<?= htmlspecialchars($publication['doi']) ?>

</li>

<li class="list-group-item">

<strong>Journal:</strong>
<?= htmlspecialchars($publication['journal'] ?? '-') ?>

</li>

<li class="list-group-item">

<strong>Publication Date:</strong>
<?= htmlspecialchars($publication['publicationDate']) ?>

</li>

</ul>

</div>

</div>

</div>

</div>

</div>

</main>

<div class="container">
<?php include (BASE_PATH . 'includes/footer.php'); ?>
</div>

</body>

</html>

<?php

function extractSuffix($uri)
{
	if (strpos($uri, '#') !== false) {
		return substr(strrchr($uri, '#'), 1);
	} elseif (strpos($uri, '/') !== false) {
		return substr(strrchr($uri, '/'), 1);
	}

	return $uri;
}

?>