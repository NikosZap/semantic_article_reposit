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
	<?php include(BASE_PATH . 'includes/header.php'); ?>
	<title><?= htmlspecialchars($publication['title']) ?></title>
</head>

<body>

<?php include(BASE_PATH . 'includes/navbar.php'); ?>

<main>
	<div class="container py-5">

		<div class="row justify-content-center">
			<div class="col-xl-10">

				<div class="mb-4">
					<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
						<div>
							<h1 class="display-5">
								<?= htmlspecialchars($publication['title']) ?>
							</h1>
						</div>

						<?php if (!empty($publication['filePath'])): ?>
							<a href="<?= BASE_URL . 'uploads/' . htmlspecialchars($publication['filePath']) ?>"
							   class="btn btn-outline-primary"
							   target="_blank">
								<i class="bi bi-file-earmark-pdf me-2"></i>View PDF
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="row g-4">

					<div class="col-lg-8">
						<div class="card border-0 shadow-sm h-100">
							<div class="card-body p-4">
								<h5 class="card-title mb-3">Abstract</h5>

								<div class="bg-light rounded p-3">
									<p class="mb-0">
										<?= nl2br(htmlspecialchars($publication['abstract'] ?? 'No abstract available.')) ?>
									</p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4">
						<div class="card border-0 shadow-sm h-100">
							<div class="card-body p-4">
								<h5 class="card-title mb-3">Publication Info</h5>

								<ul class="list-group list-group-flush">

									<li class="list-group-item px-0">
										<div class="fw-semibold mb-2">Authors</div>
										<div class="d-flex flex-column gap-1">
											<?php foreach ($authors as $author): ?>
												<?php
													$fullName = htmlspecialchars($author['firstname'] . ' ' . $author['lastname']);
													if (!empty($author['orcid'])) {
														$orcidID = urlencode($author['orcid']);
														echo "<a href='author.php?orcid=$orcidID' class='text-decoration-none'>$fullName</a>";
													} else {
														echo "<span>$fullName</span>";
													}
												?>
											<?php endforeach; ?>
										</div>
									</li>

									<li class="list-group-item px-0">
										<div class="fw-semibold mb-2">Keywords</div>
										<div class="d-flex flex-wrap gap-2">
											<?php if (!empty($keywords) && is_array($keywords)): ?>
												<?php foreach ($keywords as $keyword): ?>
													<a href="keyword.php?keyword=<?= urlencode($keyword) ?>"
													   class="badge bg-secondary text-decoration-none">
														<?= htmlspecialchars($keyword) ?>
													</a>
												<?php endforeach; ?>
											<?php else: ?>
												<span class="text-muted">-</span>
											<?php endif; ?>
										</div>
									</li>

									<li class="list-group-item px-0">
										<div class="fw-semibold">Type</div>
										<div class="text-muted">
											<?= htmlspecialchars(extractSuffix($publication['type'])) ?>
										</div>
									</li>

									<li class="list-group-item px-0">
										<div class="fw-semibold">DOI</div>
										<div class="text-muted">
											<?= htmlspecialchars($publication['doi'] ?? '-') ?>
										</div>
									</li>

									<li class="list-group-item px-0">
										<div class="fw-semibold">Journal</div>
										<div class="text-muted">
											<?= htmlspecialchars($publication['journal'] ?? '-') ?>
										</div>
									</li>

									<li class="list-group-item px-0">
										<div class="fw-semibold">Publication Date</div>
										<div class="text-muted">
											<?= htmlspecialchars($publication['publicationDate'] ?? '-') ?>
										</div>
									</li>

								</ul>
							</div>
						</div>
					</div>

				</div>

			</div>
		</div>

	</div>
</main>

<div class="container">
	<?php include(BASE_PATH . 'includes/footer.php'); ?>
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