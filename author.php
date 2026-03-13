<?php
require_once dirname(__DIR__) . '/my_project2/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

$authorORCID = $_GET['orcid'] ?? null;
if (!$authorORCID) {
    die("No author specified.");
}

$authorData = getAuthorDetails($authorORCID);
$publications = getPublicationsByAuthor($authorORCID);


?>
<head>
    <?php include (BASE_PATH . 'includes/header.php'); ?>
	<title><?= htmlspecialchars($authorData['firstname'] . ' ' . $authorData['lastname']) ?>
	</title>
</head>
<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
	<main>
		<div class="container mt-5">
			<h2 class="display-6"><?= htmlspecialchars($authorData['firstname'] . ' ' . $authorData['lastname']) ?>
			</h2>
			<p>
				<strong>ORCID:</strong> <?= '<a href="https://orcid.org/' . htmlspecialchars($authorData['orcid']) . '" target="_blank">'
     . htmlspecialchars($authorData['orcid']) . '</a>' ?>
			</p>
			<p>
				<strong>Affiliation:</strong> <?= htmlspecialchars($authorData['affiliation'] ?? '-') ?>
			</p>
			<p>
				<strong>Nationality:</strong> <?= htmlspecialchars($authorData['nationality'] ?? '-') ?>
			</p>
			<p>
				<strong>Scientific Area:</strong> <?= htmlspecialchars($authorData['scientificArea'] ?? '-') ?>
			</p>
			<hr>
				<h4>Publications</h4>
    <?php if (empty($publications)): ?>
				<p>No publications found for this author.</p>
    <?php else: ?>
				<ul class="list-group">
            <?php foreach ($publications as $pub): ?>
					<li class="list-group-item">
						<a href="publication.php?uri=<?= urlencode($pub['uri']) ?>">
                        <?= htmlspecialchars($pub['title']) ?>
						</a>
						<br>
							<small>
								<strong>DOI:</strong> <?= htmlspecialchars($pub['doi'] ?? '-') ?> | 
                    <strong>Date:</strong> <?= htmlspecialchars($pub['publicationDate'] ?? '-') ?>
							</small>
						</li>
            <?php endforeach; ?>
					</ul>
    <?php endif; ?>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
		</body>    
