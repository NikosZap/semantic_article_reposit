<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$uri =  base64_decode($_GET['uri']) ?? null;
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
if (!$authors && !is_array($authors)) {
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
    'supervisor' => 'Supervisor',
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
$filePath = '';

$userAuthors = [];
if (!empty($authors) && is_array($authors)) {
    foreach ($authors as $a) {
        $fn = trim($a['firstname'] ?? $a['firstName'] ?? '');
        $ln = trim($a['lastname'] ?? $a['lastName'] ?? '');
        $full = trim($fn . ' ' . $ln);

        if ($full !== '') {
            $userAuthors[] = $full;
        }
    }
}

$userMeta = [
    'title' => $publication['title'] ?? '',
    'doi' => $publication['doi'] ?? '',
    'type' => $publication['type'] ?? '',
    'publicationDate' => $publication['publicationDate'] ?? '',
    'journal' => $publication['journal'] ?? '',
    'publisher' => $publication['publisher'] ?? '',
    'number' => $publication['number'] ?? '',
    'volume' => $publication['volume'] ?? '',
    'pages' => $publication['pages'] ?? '',
    'isbn' => $publication['isbn'] ?? '',
    'issn' => $publication['issn'] ?? '',
    'authors' => $userAuthors
];
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
                <span class="badge bg-warning text-dark">Pending Review</span>
            </div>

            <div id="message"></div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Publication Details</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <?php foreach ($publication as $key => $value): ?>
                                <?php if (in_array($key, $skipKeys)) continue; ?>

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

                                        <?php elseif ($key === 'title'): ?>
                                            <?php
                                            echo htmlspecialchars(extractSuffix($title));
                                            ?>

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
                                        (<?= htmlspecialchars($publication['email'] ?? '') ?>)
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

                        <div class="card shadow-sm border-0 mt-4">
                            <h5 class="card-title mb-3">Abstract</h5>
                            <div class="bg-light rounded p-3">
                                    <?= isset($abstract)
                                        ? nl2br(htmlspecialchars($abstract))
                                        : '<span class="text-muted">No abstract provided.</span>'; ?>
                                
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
                                                    <?= htmlspecialchars($author['firstname']) . ' ' . htmlspecialchars($author['lastname']) ?>
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
                </div>
            </div>    

            <div class="row g-2 mt-4 justify-content-center">
                    <?php if (!empty(trim($publication['doi'] ?? ''))): ?>
                    <div class="col-12 col-md-3">
                        <button id="crossrefBtn"
                                class="btn btn-primary w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#crossrefModal">
                            <i class="bi bi-journal-check"></i> CrossRef
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="col-12 col-md-3">
                        <button class="btn btn-warning text-dark w-100" id="updateBtn"
                                data-bs-toggle="modal"  data-bs-target="#updatePublicationModal">
                            <i class="bi bi-pencil-square"></i> Update
                        </button>
                    </div>

                    <div class="col-12 col-md-3">
                        <button id="approveBtn" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                    </div>

                    <div class="col-12 col-md-3">
                        <button class="btn btn-danger w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal">
                            <i class="bi bi-trash"></i> Reject
                        </button>
                    </div>
                      
            </div>
        </div>
    </main>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>

        const PUBLICATION_URI = <?= json_encode($uri, JSON_UNESCAPED_UNICODE) ?>;
        const PUBLICATION_DOI = <?= json_encode(trim($publication['doi'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
        const USER_META = <?= json_encode($userMeta, JSON_UNESCAPED_UNICODE) ?>;

        let pendingCrossrefUpdates = {};
        let lastCrossrefMeta = {};                

        function togglePublicationTypeFields() {
            const select = document.getElementById('publicationTypeSelect');
            if (!select) return;

            const selectedType = select.value;
            const groups = document.querySelectorAll('.type-group');

            groups.forEach(group => {
                if (group.dataset.typeGroup === selectedType) {
                    group.classList.remove('d-none');
                } else {
                    group.classList.add('d-none');
                }
            });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const typeSelect = document.getElementById('publicationTypeSelect');

                if (typeSelect) {
                    togglePublicationTypeFields();
                    typeSelect.addEventListener('change', togglePublicationTypeFields);
                }
            });

            function showMessage(type, message) {
            const box = document.getElementById('message');
            if (!box) return;

            box.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        function handleAction(action) {
            if (action === 'approve') {
                approvePublication();
            } else if (action === 'crossref') {
                console.log('Crossref action');
            } else if (action === 'reject') {
                console.log('Reject action');
            }
        }

        function approvePublication() {
            const approveBtn = document.getElementById('approveBtn');
            if (!approveBtn) return;

            approveBtn.disabled = true;
            const originalHtml = approveBtn.innerHTML;
            approveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Approving...`;

            fetch('../backend/review_pending_publication_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'approve',
                    uri: PUBLICATION_URI
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('success', data.message || 'Publication approved successfully.');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage('danger', data.message || 'Failed to approve publication.');
                }
            })
            .catch(() => {
                showMessage('danger', 'An unexpected error occurred while approving the publication.');
            })
            .finally(() => {
                approveBtn.disabled = false;
                approveBtn.innerHTML = originalHtml;
            });
        }
        document.addEventListener('click', function (e) {
            if (e.target.id !== 'confirmRejectBtn' && !e.target.closest('#confirmRejectBtn')) {
                return;
            }

            const rejectBtn = e.target.closest('#confirmRejectBtn');
            const reasonInput = document.getElementById('rejectReason');
            const reason = reasonInput ? reasonInput.value.trim() : '';

            if (!reason) {
                showMessage('warning', 'Please provide a rejection reason.');
                return;
            }

            rejectBtn.disabled = true;
            const originalHtml = rejectBtn.innerHTML;
            rejectBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Rejecting...`;

            fetch('../backend/review_pending_publication_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'reject',
                    uri: PUBLICATION_URI,
                    reason: reason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById('rejectModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    showMessage('success', data.message || 'Publication rejected successfully.');

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });

                    setTimeout(() => {
                        window.location.href = 'approve_publications.php';
                    }, 1200);
                } else {
                    showMessage('danger', data.message || 'Failed to reject publication.');
                }
            })
            .catch(() => {
                showMessage('danger', 'An unexpected error occurred while rejecting the publication.');
            })
            .finally(() => {
                rejectBtn.disabled = false;
                rejectBtn.innerHTML = originalHtml;
            });
        });
        
        document.getElementById('crossrefBtn')?.addEventListener('click', function () {
            const body = document.getElementById('crossrefComparisonBody');

            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border"></div>
                    <p class="mt-2">Loading Crossref metadata...</p>
                </div>
            `;

            if (!PUBLICATION_DOI) {
                body.innerHTML = `<div class="alert alert-warning">No DOI available for this publication.</div>`;
                return;
            }

            fetch(`../backend/crossref_compare_backend.php?doi=${encodeURIComponent(PUBLICATION_DOI)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success') {
                        body.innerHTML = `<div class="alert alert-danger">${data.message || 'Crossref error'}</div>`;
                        return;
                    }

                    renderCrossrefTable(USER_META, data.crossref || {});
                    
                    const score = calculateMatchScore(USER_META, data.crossref || {});
                    renderMatchScore(score);
                })
                .catch(() => {
                    body.innerHTML = `<div class="alert alert-danger">Error loading Crossref data.</div>`;
                });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('use-crossref-btn')) return;

            const field = e.target.dataset.field;
            const value = decodeURIComponent(e.target.dataset.value || '');

            console.log('Field:', field);
            console.log('Value:', value);

            const candidates = document.querySelectorAll(`#updatePublicationModal [name="${field}"]`);

            let input = null;

            candidates.forEach(el => {
                const group = el.closest('.type-group');

                if (!group) {
                    input = el;
                    return;
                }

                if (!group.classList.contains('d-none') && !input) {
                    input = el;
                }
            });

            console.log('Found visible input:', input);

            if (!input) {
                console.warn('No visible matching input for:', field);
                showMessage('warning', `Field "${field}" not found in visible update section.`);
                return;
            }

            input.value = value;

            const currentCell = document.getElementById(`current-cell-${field}`);
            if (currentCell) {
                currentCell.textContent = value;
            }

            e.target.classList.remove('btn-primary');
            e.target.classList.add('btn-success');
            e.target.innerText = 'Applied';

            const row = e.target.closest('tr');

            if (row) {
                const badge = row.querySelector('.badge');

                if (badge) {
                    badge.classList.remove('bg-info', 'bg-warning', 'text-dark');
                    badge.classList.add('bg-success');
                    badge.textContent = 'Updated';
                }
            }
        });

        

        document.addEventListener('click', function (e) {
            if (e.target.id !== 'openUpdateFromCrossrefBtn' && !e.target.closest('#openUpdateFromCrossrefBtn')) {
                return;
            }

            const crossrefEl = document.getElementById('crossrefModal');
            const updateEl = document.getElementById('updatePublicationModal');

            if (!updateEl) {
                showMessage('danger', 'Update modal not found.');
                return;
            }

            const crossrefModal = bootstrap.Modal.getInstance(crossrefEl);
            if (crossrefModal) {
                crossrefModal.hide();
            }

            setTimeout(() => {
                const updateModal = new bootstrap.Modal(updateEl);
                updateModal.show();
            }, 200);
        });

        document.addEventListener('click', function (e) {
            if (e.target.id !== 'saveUpdateBtn' && !e.target.closest('#saveUpdateBtn')) {
                return;
            }

            const btn = e.target.closest('#saveUpdateBtn');
            const form = document.getElementById('updatePublicationForm');

            if (!form) {
                showMessage('danger', 'Update form not found.');
                return;
            }

            const formData = new FormData();
            const inputs = document.querySelectorAll('#updatePublicationModal input, #updatePublicationModal textarea, #updatePublicationModal select');

            inputs.forEach(input => {
                const group = input.closest('.type-group');

                if (group && group.classList.contains('d-none')) {
                    return; 
                }

                if (!input.name) return;

                formData.append(input.name, input.value);
            });

            formData.append('action', 'update');
            formData.append('uri', PUBLICATION_URI);

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Saving...`;

            fetch('../backend/review_pending_publication_backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {

                if (data.status === 'success') {
                    const modalEl = document.getElementById('updatePublicationModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    showMessage('success', data.message || 'Publication updated successfully.');

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);

                } else {
                    showMessage('danger', data.message || 'Failed to update publication.');
                }
            })
            .catch(() => {
                showMessage('danger', 'Unexpected error while saving publication changes.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });

        function renderCrossrefTable(userMeta, crossrefMeta) {
            const fields = [
                { key: 'title', label: 'Title' },
                { key: 'doi', label: 'DOI' },
                { key: 'publicationDate', label: 'Publication Date' },
                { key: 'journal', label: 'Journal' },
                { key: 'publisher', label: 'Publisher' },
                { key: 'volume', label: 'Volume' },
                { key: 'number', label: 'Number' },
                { key: 'pages', label: 'Pages' },
                { key: 'isbn', label: 'ISBN' },
                { key: 'issn', label: 'ISSN' },
                { key: 'authors', label: 'Authors' }
            ];

            let rows = '';

            fields.forEach(field => {
                const userVal = userMeta[field.key] ?? '';
                const crossVal = crossrefMeta[field.key] ?? '';

                const displayUser = Array.isArray(userVal) ? userVal.join(', ') : userVal;
                const displayCross = Array.isArray(crossVal) ? crossVal.join(', ') : crossVal;

                const isAuthors = field.key === 'authors';
                const isMatch = String(displayUser).trim() === String(displayCross).trim();

                let badge = '';
                let rowClass = '';

                if (isAuthors) {
                    const result = compareAuthors(userVal, crossVal);

                    if (result.status === 'match') {
                        badge = `<span class="badge bg-success">Match</span>`;
                    } 
                    else if (
                        result.exactMatches.length > 0 ||
                        result.fuzzyMatches.length > 0
                    ) {
                        badge = `<span class="badge bg-warning text-dark">Partially Match</span>`;
                        rowClass = 'table-warning';
                    } 
                    else {
                        badge = `<span class="badge bg-danger">Different</span>`;
                        rowClass = 'table-danger';
                    }

                } else {
                    if (displayUser && displayCross) {
                        badge = isMatch
                            ? `<span class="badge bg-success">Match</span>`
                            : `<span class="badge bg-warning text-dark">Different</span>`;
                    } else if (!displayUser && displayCross) {
                        badge = `<span class="badge bg-info text-dark">Missing</span>`;
                    } else if (displayUser && !displayCross) {
                        badge = `<span class="badge bg-secondary">Missing in Crossref</span>`;
                    }
                }

                let actionBtn = '';
                if (!isMatch && !isAuthors && displayCross) {
                    actionBtn = `
                        <button type="button"
                                class="btn btn-sm btn-primary use-crossref-btn"
                                data-field="${field.key}"
                                data-value="${encodeURIComponent(displayCross)}">
                            Use
                        </button>
                    `;
                }

                rows += `
                    <tr class="${rowClass}">
                        <td><strong>${field.label}</strong><br>${badge}</td>
                        <td id="current-cell-${field.key}">${displayUser || '<span class="text-muted">—</span>'}</td>
                        <td>${displayCross || '<span class="text-muted">—</span>'}</td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            });

            document.getElementById('crossrefComparisonBody').innerHTML = `
                <div class="mb-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-secondary">N/A</span>
                        <span class="badge bg-success">Match</span>
                        <span class="badge bg-warning text-dark">Different</span>
                        <span class="badge bg-info text-dark">Missing</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Field</th>
                                <th>Current</th>
                                <th>Crossref</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
        }

        function calculateMatchScore(userMeta, crossrefMeta) {
            const weights = {
                doi: 25,
                title: 20,
                authors: 20,
                publicationDate: 10,
                journal: 10,
                publisher: 5,
                volume: 3,
                number: 2,
                pages: 3,
                isbn: 1,
                issn: 1
            };

            let earned = 0;
            let possible = 0;

            Object.keys(weights).forEach(key => {
                const weight = weights[key];
                const userVal = userMeta[key];
                const crossVal = crossrefMeta[key];

                if (key === 'authors') {
                    const hasUser = Array.isArray(userVal) ? userVal.length > 0 : comparable(userVal) !== '';
                    const hasCross = Array.isArray(crossVal) ? crossVal.length > 0 : comparable(crossVal) !== '';

                    if (!hasUser && !hasCross) {
                        return;
                    }

                    possible += weight;

                    if (hasUser && hasCross) {
                        const result = compareAuthors(userVal, crossVal);

                        if (result.status === 'match') {
                            earned += weight;
                        } else if (
                            result.fuzzyMatches.length > 0 &&
                            result.missingInUser.length === 0 &&
                            result.extraInUser.length === 0
                        ) {
                            earned += weight * 0.8;
                        } else if (
                            result.exactMatches.length > 0 || result.fuzzyMatches.length > 0
                        ) {
                            earned += weight * 0.4;
                        }
                    }

                    return;
                }

                let compU = normalizeValue(userVal);
                let compC = normalizeValue(crossVal);

                compU = comparable(compU);
                compC = comparable(compC);

                const isEmptyU = compU === '';
                const isEmptyC = compC === '';

                if (isEmptyU && isEmptyC) {
                    return;
                }

                possible += weight;

                if (!isEmptyU && !isEmptyC && compU === compC) {
                    earned += weight;
                }
            });

            if (possible === 0) {
                return 0;
            }

            return Math.round((earned / possible) * 100);
        }

        function renderMatchScore(score) {
            const scoreBox = document.getElementById('crossrefMatchScore');
            if (!scoreBox) return;

            let badgeClass = 'bg-danger';

            if (score >= 80) {
                badgeClass = 'bg-success';
            } else if (score >= 50) {
                badgeClass = 'bg-warning text-dark';
            }

            scoreBox.innerHTML = `
                <span class="me-2">Match Score:</span>
                <span class="badge ${badgeClass}">${score}%</span>
            `;
        }

        function normalizeValue(v) {
            if (v === null || v === undefined) return '';
            if (Array.isArray(v)) {
                return v.map(x => String(x).trim()).filter(Boolean).sort().join(', ');
            }
            return String(v).trim();
        }

        function comparable(v) {
            return normalizeValue(v).toLowerCase().replace(/\s+/g, ' ');
        }


        function normalizeAuthorName(name) {
            if (!name) return '';

            name = String(name)
                .toLowerCase()
                .replace(/\./g, '')
                .replace(/,/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            const parts = name.split(' ').filter(Boolean);
            if (parts.length === 0) return '';
            if (parts.length === 1) return parts[0];

            const lastname = parts[parts.length - 1];
            const firstnameInitial = parts[0][0];

            return `${lastname}_${firstnameInitial}`;
        }

        function normalizeAuthors(authors) {
            if (!authors) return [];

            if (typeof authors === 'string') {
                authors = authors.split(',');
            }

            return authors
                .map(a => normalizeAuthorName(a))
                .filter(Boolean)
                .sort();
        }

        function cleanAuthorText(name) {
            return String(name || '')
                .toLowerCase()
                .replace(/\./g, '')
                .replace(/&/g, ' and ')
                .replace(/,/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function parseAuthorName(name) {
            const original = String(name || '').trim();
            const cleaned = cleanAuthorText(name);

            if (!cleaned) {
                return null;
            }

            const parts = cleaned.split(' ').filter(Boolean);
            if (parts.length === 0) {
                return null;
            }

            const first = parts[0];
            const initials = first ? first.charAt(0) : '';

            let surname = '';
            let surnameParts = [];

            if (parts.length === 1) {
                surname = parts[0];
                surnameParts = [parts[0]];
            } else if (parts.length === 2) {
                surname = parts[1];
                surnameParts = [parts[1]];
            } else {
                surnameParts = parts.slice(1);
                surname = surnameParts[surnameParts.length - 1];
            }

            return {
                original,
                cleaned,
                first,
                initials,
                parts,
                surname,
                surnameParts
            };
        }

        function buildAuthorEntries(authors) {
            if (!authors) return [];

            if (typeof authors === 'string') {
                authors = authors.split(',');
            }

            return authors
                .map(a => parseAuthorName(a))
                .filter(Boolean);
        }

        function exactAuthorMatch(a, b) {
            if (!a || !b) return false;

            const aToken = `${a.surname}_${a.initials}`;
            const bToken = `${b.surname}_${b.initials}`;

            return aToken === bToken;
        }

        function fuzzyAuthorMatch(a, b) {
            if (!a || !b) return false;
            if (!a.initials || !b.initials) return false;
            if (a.initials !== b.initials) return false;

            const aSurname = a.surname;
            const bSurname = b.surname;

            const aSurnameParts = a.surnameParts.join(' ');
            const bSurnameParts = b.surnameParts.join(' ');

            if (aSurname === bSurname) {
                return true;
            }

            if (aSurnameParts.includes(bSurname) || bSurnameParts.includes(aSurname)) {
                return true;
            }

            if (aSurname.includes(bSurname) || bSurname.includes(aSurname)) {
                return true;
            }

            return false;
        }

        function compareAuthors(userAuthors, crossrefAuthors) {
            const userEntries = buildAuthorEntries(userAuthors);
            const crossrefEntries = buildAuthorEntries(crossrefAuthors);

            const usedUser = new Set();
            const usedCross = new Set();

            const exactMatches = [];
            const fuzzyMatches = [];

            for (let i = 0; i < crossrefEntries.length; i++) {
                for (let j = 0; j < userEntries.length; j++) {
                    if (usedCross.has(i) || usedUser.has(j)) continue;

                    if (exactAuthorMatch(crossrefEntries[i], userEntries[j])) {
                        usedCross.add(i);
                        usedUser.add(j);
                        exactMatches.push({
                            crossref: crossrefEntries[i].original,
                            user: userEntries[j].original
                        });
                        break;
                    }
                }
            }

            for (let i = 0; i < crossrefEntries.length; i++) {
                for (let j = 0; j < userEntries.length; j++) {
                    if (usedCross.has(i) || usedUser.has(j)) continue;

                    if (fuzzyAuthorMatch(crossrefEntries[i], userEntries[j])) {
                        usedCross.add(i);
                        usedUser.add(j);
                        fuzzyMatches.push({
                            crossref: crossrefEntries[i].original,
                            user: userEntries[j].original
                        });
                        break;
                    }
                }
            }

            const missingInUser = [];
            const extraInUser = [];

            for (let i = 0; i < crossrefEntries.length; i++) {
                if (!usedCross.has(i)) {
                    missingInUser.push(crossrefEntries[i].original);
                }
            }

            for (let j = 0; j < userEntries.length; j++) {
                if (!usedUser.has(j)) {
                    extraInUser.push(userEntries[j].original);
                }
            }

            return {
                status: missingInUser.length === 0 && extraInUser.length === 0 ? 'match' : 'different',
                exactMatches,
                fuzzyMatches,
                missingInUser,
                extraInUser
            };
        }

        function openPdfModal(pdfUrl) {
            const frame = document.getElementById('pdfFrame');
            frame.src = pdfUrl;

            const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
            modal.show();
        }

        

    </script>

    <?php
    include BASE_PATH . 'modals/pdf_modal.php';
    include BASE_PATH . 'modals/reject_pub_modal.php';
    include BASE_PATH . 'modals/crossref_modal.php';
    include BASE_PATH . 'modals/update_pub_modal.php';
    ?>
    </body>
    </html>