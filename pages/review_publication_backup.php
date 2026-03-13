<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$uri = $_GET['uri'] ?? null;
if (!$uri) {
    echo "<p>Error: No publication URI provided.</p>";
    exit;
}

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$publication = getPublicationByURI($uri);
if (!$publication) {
    echo "<p>Error: Publication not found.</p>";
    exit;
}
$authors = getAuthorsByPublication($uri);
if (!$publication) {
    echo "<p>Error: Authors not found.</p>";
    exit;
}

$keywords = getKeywordsByPublication($uri);

$fieldLabels = [
    'title' => 'Title',
    'type' => 'Type',
    'doi' => 'DOI',
    'publicationDate' => 'Publication Date',
    'uploadedDate' => 'Upload Date',
    'uploadedAt' => 'Update Date',
    'filePath' => 'Uploaded File',
    'abstract' => 'Abstract',
    'isApproved' => 'Approval Status',
    'journal' => 'Journal',
    'volume' => 'Volume',
    'pages' => 'Pages',
    'publisher' => 'Publisher',
    'isbn' => 'ISBN',
    'school' => 'School',
    'supervisor' => 'Supervisor',
    'firstname' => 'Firstname',
    'lastname' => 'Lastname',
    'orcid' => 'ORCID',
    'abstract' => 'Abstract'];

$skipKeys = ['userURI', 
             'username', 
             'firstname', 
             'lastname', 
             'email', 
             'hasUploaded',
             'abstract',
             'hasAuthor',
             'hasKeyword'];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
    <?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>Review Publication</title>
	</head>
	<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<h3 class="display-6">Review Publication</h3>
				<div id="message"/>
				<div class="table-responsive">
					<h5 class="mt-4">Publication</h5>
					<table class="table table-bordered">
        <?php foreach ($publication as $key => $value): 
            if (in_array($key, $skipKeys)) continue; ?>
						<tr>
							<th><?= $fieldLabels[$key] ?? htmlspecialchars($key) ?>
							</th>
							<td>
                    <?php if ($key === 'filePath'): 
                        $filePath = BASE_PATH . 'pending/' . htmlspecialchars($value); ?>
								<a class="btn btn-outline-primary" onclick="openPdfModal('../pending/<?= htmlspecialchars($value) ?>')">View PDF <i class="bi bi-file-earmark-pdf"/>
								</a>
                    <?php elseif($key === 'type'): ?>
                        <?= htmlspecialchars(extractSuffix($value)) ?>
                    <?php elseif($key === 'isApproved'):
                            if ($value === 'true'):?>
								<span class='badge bg-success'>Yes</span> 
                            <?php else:?>
								<span class='badge bg-danger'>No</span>
                            <?php endif; ?>    
                    <?php elseif($key === 'title'): ?>
                            <?= $title = $value;
                                htmlspecialchars(extractSuffix($value)); ?>
                            <?php else: ?>    
                        <?= htmlspecialchars($value) ?>
                    <?php endif; ?>
							</td>
						</tr>
        <?php endforeach; ?>
        <?php if (!empty($publication['firstname']) && !empty($publication['lastname'])): ?>
						<tr>
							<th>Submitted By</th>
							<td>
                <?= htmlspecialchars($publication['firstname'] . ' ' . $publication['lastname']) ?>
                (<?= htmlspecialchars($publication['email'] ?? '') ?>)
            </td>
						</tr>
    <?php endif; ?>
    <?php if (!empty($keywords)): ?>
						<tr>
							<th>Keywords</th>
							<td>
                <?php foreach ($keywords as $kw): ?>
								<span class="badge bg-secondary me-1"><?= htmlspecialchars($kw) ?>
								</span>
                <?php endforeach; ?>
							</td>
						</tr>
        <?php endif; ?>
						<tr>
							<th>Abstract</th>
							<td>
            <?php echo isset($publication['abstract']) ? htmlspecialchars($publication['abstract']) : '<span class="text-muted">No abstract provided.</span>';
            $abstract = $publication['abstract'] ?? null; ?>
							</td>
						</table>

    <?php if (!empty($authors)): ?>
						<h5 class="mt-4">Authors</h5>
						<div class="table-responsive">
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Full Name</th>
										<th>ORCID</th>
									</tr>
								</thead>
								<tbody>
                <?php foreach ($authors as $index => $author): ?>
									<tr>
										<td><?= $index + 1 ?>
										</td>
										<td><?= htmlspecialchars($author['firstname']) . ' ' . htmlspecialchars($author['lastname']) ?>
										</td>
										<td>
                            <?php if (!empty($author['orcid'])): ?>
											<a href="https://orcid.org/<?= htmlspecialchars($author['orcid']) ?>" target="_blank">
                                    <?= htmlspecialchars($author['orcid']) ?>
											</a>
                            <?php else: ?>
											<span class="text-muted">N/A</span>
                            <?php endif; ?>
										</td>
									</tr>
                <?php endforeach; ?>
								</tbody>
							</table>
						</div>
<?php endif; ?>
					</div>
					<div class="d-flex gap-2 mt-3 justify-content-between">
        <?php if ($publication['isApproved']  === 'false'){ ?>
						<button onclick="handleAction('approve')" class="btn btn-success flex-fill">
							<i class="bi bi-check-circle"/> Approve
        </button>
						<button onclick="handleAction('crossref')" class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#crossrefModal">
							<i class="bi bi-journal-check"/> CrossRef 
        </button>
						<button onclick="handleAction('reject')" class="btn btn-danger flex-fill">
							<i class="bi bi-trash"/> Reject
        </button> 
        <?php }elseif($publication['isApproved']  === 'true'){ ?>
						<button onclick="handleAction('delete')" class="btn btn-danger flex-fill">
							<i class="bi bi-trash"/> Delete
        </button> 
        <?php }?>
					</div>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
			<script>

function handleAction(action) {

    if(action === 'reject' || action === 'approve') {

    if (action === 'reject' && !confirm("Are you sure you want to reject this publication?")) return;

    fetch('../backend/approve_publications_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action, uri: "<?= $uri ?>", title: "<?= $title ?>", filePath: "<?= addslashes($filePath) ?>" ,  abstract: "<?= addslashes($abstract) ?>"})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'approve_publications.php?message=' + encodeURIComponent(data.message);
        } else {
            document.getElementById("message").innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(() => {
        document.getElementById("message").innerHTML = '<div class="alert alert-danger">Request failed.</div>';
    });
    }else if(action === 'delete') {
        fetch('../backend/manage_publications_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action, uri: "<?= $uri ?>"})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'manage_publications.php?message=' + encodeURIComponent(data.message);
        } else {
            document.getElementById("message").innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(() => {
        document.getElementById("message").innerHTML = '<div class="alert alert-danger">Request failed.</div>';
    });
    }
}

function openPdfModal(pdfUrl) {
    const frame = document.getElementById('pdfFrame');
    frame.src = pdfUrl;
    const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
    modal.show();
  }

<?php

function extractSuffix($uri) {
    if (strpos($uri, '#') !== false) {
        return substr(strrchr($uri, '#'), 1);
    } elseif (strpos($uri, '/') !== false) {
        return substr(strrchr($uri, '/'), 1);
    }
    return $uri;
}
?>
			</script>
		</body>



<?php
include BASE_PATH . 'modals/pdf_modal.php';
#include BASE_PATH . 'modals/reject_pub_modal.php';
include BASE_PATH . 'modals/crossref_modal.php';
?>
		<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Reason for Rejection</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"/>
					</div>
					<div class="modal-body">
						<textarea id="rejectReason" class="form-control" placeholder="Explain why the publication is rejected..." rows="4" required>< textarea>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-danger" id="confirmReject">Confirm Rejection</button>
					</div>
				</div>
			</div>
		</div>
	</html>
