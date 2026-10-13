<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

$uri = base64_decode($_GET['uri']) ?? null;
if (!$uri) {
    echo "<p>Error: No publication URI provided.</p>";
    exit;
}

if (empty($_SESSION['user']['is_logged_in'])) {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$userID = $_SESSION['user']['id'] ?? '';
$userUri = getUserUriByUserId($userID);

$isAdmin = (($_SESSION['user']['role'] ?? '') === 'Admin');
$isOwner = $userUri ? isPublicationOwnedByUser($uri, $userUri) : false;

if (!$isAdmin && !$isOwner) {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$canUpdate = $isOwner;
$canDelete = $isAdmin || $isOwner;

$publication = getPublicationByURI($uri);
if (!$publication) {
    echo "<p>Error: Publication not found.</p>";
    exit;
}

$authors = getAuthorsByPublication($uri);
if (!$authors || !is_array($authors)) {
    $authors = [];
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
    'number' => 'Number',
    'pages' => 'Pages',
    'publisher' => 'Publisher',
    'isbn' => 'ISBN',
    'issn' => 'ISSN',
    'school' => 'School',
    'department' => 'Department',
    'supervisor' => 'Supervisor',
    'thesisType' => 'Thesis Type',
    'firstname' => 'Firstname',
    'lastname' => 'Lastname',
    'orcid' => 'ORCID'
];

$skipKeys = [
    'userURI',
    'username',
    'firstname',
    'lastname',
    'email',
    'hasUploaded',
    'abstract',
    'hasAuthor',
    'hasKeyword',
    'mentions',
    'hasFullText'
];

function extractSuffix($uri)
{
    if (strpos($uri, '#') !== false) {
        return substr(strrchr($uri, '#'), 1);
    } elseif (strpos($uri, '/') !== false) {
        return substr(strrchr($uri, '/'), 1);
    }

    return $uri;
}

$title = $publication['title'] ?? '';
$abstract = $publication['abstract'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Review Publication</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <div class="mb-4">
                <h3 class="display-6"><?= htmlspecialchars($title ?: 'Untitled Publication') ?></h3>
            </div>

            <div id="message"></div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Publication Details</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <?php foreach ($publication as $key => $value): ?>
                                <?php if (in_array($key, $skipKeys, true)) continue; ?>

                                <tr>
                                    <th class="text-muted" style="width: 25%;">
                                        <?= $fieldLabels[$key] ?? htmlspecialchars($key) ?>
                                    </th>
                                    <td>
                                        <?php if ($key === 'filePath'): ?>
                                            <?php 
                                                $pdfFolder = (($publication['isApproved'] ?? 'false') === 'true') ? 'uploads/' : 'pending/';
                                                $filePath = BASE_PATH . $pdfFolder . $value;
                                                $pdfUrl = '../' . $pdfFolder . $value;   ?>
                                            
                                            <a class="btn btn-outline-primary" onclick="openPdfModal('<?= htmlspecialchars($pdfUrl) ?>')" >
                                                View PDF <i class="bi bi-file-earmark-pdf"></i>
                                            </a>

                                        <?php elseif ($key === 'type'): ?>
                                            <?= htmlspecialchars(extractSuffix($value)) ?>

                                        <?php elseif ($key === 'isApproved'): ?>
                                            <?php if ($value === 'true'): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No</span>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <?= htmlspecialchars($value) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!empty($publication['firstname']) && !empty($publication['lastname'])): ?>
                                <tr>
                                    <th class="text-muted" style="width: 25%;">Submitted By</th>
                                    <td>
                                        <?= htmlspecialchars($publication['firstname'] . ' ' . $publication['lastname']) ?>
                                        <?php if (!empty($publication['email'])): ?>
                                            (<?= htmlspecialchars($publication['email']) ?>)
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($keywords)): ?>
                                <tr>
                                    <th class="text-muted" style="width: 25%;">Keywords</th>
                                    <td>
                                        <?php foreach ($keywords as $kw): ?>
                                            <span class="badge bg-light text-dark border me-1">
                                                <?= htmlspecialchars($kw) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Abstract</h5>
                            <div class="bg-light rounded p-3">
                                <?= isset($abstract) && $abstract !== ''
                                    ? nl2br(htmlspecialchars($abstract))
                                    : '<span class="text-muted">No abstract provided.</span>'; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($authors)): ?>
                        <h5 class="mt-4">Authors</h5>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
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
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <?= htmlspecialchars(trim(($author['firstname'] ?? '') . ' ' . ($author['lastname'] ?? ''))) ?>
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

                    <div class="row g-2 mt-4 justify-content-center">
                        <?php if ($canUpdate): ?>
                            <div class="col-12 col-md-3">
                                <a
                                    href="edit_publication.php?uri=<?= base64_encode($uri) ?>"
                                    class="btn btn-warning text-dark w-100">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($canDelete): ?>
                            <div class="col-12 col-md-3">
                                <button
                                    type="button"
                                    class="btn btn-danger w-100"
                                    id="deletePublicationBtn"
                                    data-uri="<?= base64_encode($uri) ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/review_publication.js"></script>
</body>
<?php include BASE_PATH . 'modals/pdf_modal.php';?>
</html>