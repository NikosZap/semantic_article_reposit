<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$publicationUri = $_GET['publication_uri'] ?? '';
$publicationUri = trim($publicationUri);

$claims = getClaimsHistoryByPublication($publicationUri);

$pubTitle = getPublicationTitleByUri($publicationUri);

?>

<!DOCTYPE html>
<html lang="en">
	<head>
    <?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>Publication Claim History</title>
	</head>
	<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<h3 class="display-6">Publication Claim History</h3>
				<div id="message"/>
				<div class="table-responsive">
					<h5 class="mt-4"><?php echo $pubTitle; ?>
					</h5>
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Date</th>
								<th>Status</th>
								<th>Claimed By</th>
								<th>Claimed Author</th>
								<th>Reason</th>
							</tr>
						</thead>
						<tbody>
            <?php foreach ($claims as $c): ?>
							<tr>
								<td><?= htmlspecialchars($c['claimDate'] ?? '') ?>
								</td>
								<td>
                    <?php if ($c['status'] === 'Pending'):?>
									<span class='badge bg-primary'>Pending</span> 
                    <?php elseif ($c['status'] === 'Approved'):?>
									<span class='badge bg-success'>Approved</span>
                    <?php elseif ($c['status'] === 'Rejected'):?>
									<span class='badge bg-danger'>Rejected</span>    
                    <?php endif; ?>
								</td>
								<td>
                    <?= htmlspecialchars(trim(($c['userFirstName'] ?? '') . ' ' . ($c['userLastName'] ?? ''))) ?>
									<div class="small text-muted"><?= htmlspecialchars($c['userEmail'] ?? '') ?>
									</div>
								</td>
								<td>
                    <?= htmlspecialchars(trim(($c['authorFirstName'] ?? '') . ' ' . ($c['authorLastName'] ?? ''))) ?>
                    <?php if (!empty($c['authorOrcid'])): ?>
									<div class="small text-muted">ORCID: <?= htmlspecialchars($c['authorOrcid']) ?>
									</div>
                    <?php endif; ?>
								</td>
								<td style="max-width: 350px;">
									<div class="text-truncate" title="<?= htmlspecialchars($c['reason'] ?? '') ?>">
                        <?= htmlspecialchars($c['reason'] ?? '') ?>
									</div>
								</td>
							</tr>
            <?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
		</body>
	</html>