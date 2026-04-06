
<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

if (empty($_SESSION['user']['is_logged_in'])) {
    $_SESSION['unauthorized'] = true;
    header("Location: login.php");
    exit;
}

$uri = $_GET['uri'] ?? null;

if (!$uri) {
    die("Error: No publication specified.");
}

$userID = $_SESSION['user']['id'] ?? null;

if (!$userID) {
    die("Error: Invalid user session.");
}

$userUri = getUserUriByUserId($userID);

if (!$userUri) {
    die("Error: User not found.");
}

$isOwner = isPublicationOwnedByUser($uri, $userUri);

if (!$isOwner) {
   header("Location: my_publications.php");
exit;
}

$publication = getPublicationByURI($uri);
if (!$publication) {
    die("Error: Publication not found.");
}

$authors = getAuthorsByPublication($uri);
if (!$authors || !is_array($authors)) {
    $authors = [];
}

$keywords = getKeywordsByPublication($uri);
if (!$keywords || !is_array($keywords)) {
    $keywords = [];
}

$publicationType = '';
if (!empty($publication['type'])) {
    if (strpos($publication['type'], '#') !== false) {
        $publicationType = substr(strrchr($publication['type'], '#'), 1);
    } elseif (strpos($publication['type'], '/') !== false) {
        $publicationType = substr(strrchr($publication['type'], '/'), 1);
    } else {
        $publicationType = $publication['type'];
    }
}

$typeLabels = [
    'Article' => 'Journal Article',
    'ConferencePaper' => 'Conference Paper',
    'Book' => 'Book',
    'Thesis' => 'Thesis'
];

$keywordsTagifyValue = json_encode(
    array_map(fn($kw) => ['value' => $kw], $keywords),
    JSON_UNESCAPED_UNICODE
);

$pdfFolder = (($publication['isApproved'] ?? 'false') === 'true') ? 'uploads/' : 'pending/';
$pdfUrl = '../' . $pdfFolder . $publication['filePath'];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include(BASE_PATH . 'includes/header.php'); ?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
			<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
			<title>Edit Publication</title>
			<style>
                .required:after {
                    content: " *";
                    color: red;
                }
                .form-section {
                    margin-bottom: 2rem;
                }
                .author-toggle {
                    cursor: pointer;
                }
            </style>
	</head>
	<body>
        <?php include(BASE_PATH . 'includes/navbar.php'); ?>
		<main class="py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-xl-10">

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="display-5">Edit Publication</h1>
                                <p class="text-muted mb-0">Review and update the publication details.</p>
                            </div>
                            <button id="showDetails" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-lightbulb me-1" id="helpIcon"></i> Help
                            </button>
                        </div>

                        <div id="message" class="mb-4"></div>

                       <div id="detailsSection" class="alert alert-info d-none">
                            <h6 class="mb-3">Instructions</h6>
                            <ul class="mb-0 ps-3">
                                <li>Review the existing publication details before making any changes.</li>
                                <li>Only editable fields can be updated. The DOI and publication type remain unchanged.</li>
                                <li>You are allowed to edit metadata, authors, keywords, abstract, and type-specific fields.</li>
                                <li>If needed, you can replace the current PDF by uploading a new one.</li>
                                <li>Save your changes to update the publication, or delete it if it is no longer needed.</li>
                                <li class="text-warning fw-semibold">After submitting changes, the publication will be reviewed again by an administrator.</li>
                            </ul>
                        </div>

                        <form action="../backend/edit_publication_backend.php" method="POST" id="publicationForm" enctype="multipart/form-data">
                            <input type="hidden" name="uri" value="<?= htmlspecialchars($uri) ?>">
                            <input type="hidden" name="pubType" value="<?= htmlspecialchars($publicationType) ?>">

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">Basic Information</h5>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Publication Title <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                name="title"
                                                class="form-control"
                                                required
                                                value="<?= htmlspecialchars($publication['title'] ?? '') ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">DOI</label>
                                            <input
                                                    type="text"
                                                    name="doi"
                                                    id="doi"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($publication['doi'] ?? '') ?>"
                                                    readonly>
                                                <div class="form-text">DOI cannot be changed.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Publication Date <span class="text-danger">*</span></label>
                                           <input
                                                type="date"
                                                name="pubDate"
                                                class="form-control"
                                                required
                                                value="<?= htmlspecialchars($publication['publicationDate'] ?? '') ?>">
                                        </div>

                                        <div class="col-12">
                                            
                                            <?php
                                            $typeLabels = [
                                                'Article' => 'Journal Article',
                                                'ConferencePaper' => 'Conference Paper',
                                                'Book' => 'Book',
                                                'Thesis' => 'Thesis'
                                            ];
                                            ?>

                                            <div class="col-12">
                                                <label class="form-label">Type of Publication</label>
                                                <input type="text"
                                                    class="form-control locked-field always-readonly"
                                                    value="<?= htmlspecialchars($typeLabels[$publicationType] ?? $publicationType) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="pubType" value="<?= htmlspecialchars($publicationType) ?>">
                            <input type="hidden" name="uri" value="<?= htmlspecialchars($uri) ?>">

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">Abstract & Keywords</h5>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="keywords" class="form-label">Keywords</label>
                                            <input
                                                id="keywords"
                                                name="keywords"
                                                class="form-control"
                                                value='<?= htmlspecialchars($keywordsTagifyValue) ?>'>
                                            <div class="form-text">Add keywords separated with commas</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Abstract</label>
                                            <textarea
                                                name="abstract"
                                                rows="6"
                                                class="form-control"
                                                placeholder="Write the abstract here..."><?= htmlspecialchars($publication['abstract'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">
                                        Authors <span class="text-danger">*</span>
                                    </h5>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addCurrentUserAuthor">
                                            <i class="bi bi-person-check me-1"></i>Add Me
                                        </button>

                                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-author">
                                            <i class="bi bi-person-plus-fill me-1"></i>Add Author
                                        </button>
                                    </div>
                                </div>

                                    <div id="authors-container">
                                        <?php if (!empty($authors)): ?>
                                            <?php foreach ($authors as $index => $author): ?>
                                                <?php
                                                    $authorOrcid = $author['orcid'] ?? '';
                                                    $authorFirstname = $author['firstname'] ?? '';
                                                    $authorLastname = $author['lastname'] ?? '';
                                                    $manualEntry = empty($authorOrcid);
                                                ?>
                                                <div class="row g-3 align-items-end mb-3 author-entry" data-index="<?= $index ?>">
                                                    <div class="col-md-3">
                                                        <label class="form-label">ORCID</label>
                                                        <input
                                                            type="text"
                                                            name="authors[<?= $index ?>][orcid]"
                                                            class="form-control orcid-input"
                                                            placeholder="0000-0000-0000-0000"
                                                            value="<?= htmlspecialchars($authorOrcid) ?>"
                                                            <?= $manualEntry ? 'disabled' : '' ?>>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="form-label">First Name</label>
                                                        <input
                                                            type="text"
                                                            name="authors[<?= $index ?>][firstname]"
                                                            class="form-control firstname"
                                                            value="<?= htmlspecialchars($authorFirstname) ?>"
                                                            <?= $manualEntry ? '' : 'disabled' ?>>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="form-label">Last Name</label>
                                                        <input
                                                            type="text"
                                                            name="authors[<?= $index ?>][lastname]"
                                                            class="form-control lastname"
                                                            value="<?= htmlspecialchars($authorLastname) ?>"
                                                            <?= $manualEntry ? '' : 'disabled' ?>>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <div class="form-check mt-4 pt-2">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input author-toggle"
                                                                id="manualAuthorCheck<?= $index ?>"
                                                                <?= $manualEntry ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="manualAuthorCheck<?= $index ?>">Manual entry</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-1 text-end">
                                                        <?php if ($index > 0): ?>
                                                            <button type="button" class="btn btn-outline-danger btn-sm remove-author">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="row g-3 align-items-end mb-3 author-entry" data-index="0">
                                                <div class="col-md-3">
                                                    <label class="form-label">ORCID</label>
                                                    <input type="text" name="authors[0][orcid]" class="form-control orcid-input" placeholder="0000-0000-0000-0000">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">First Name</label>
                                                    <input type="text" name="authors[0][firstname]" class="form-control firstname" disabled>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Last Name</label>
                                                    <input type="text" name="authors[0][lastname]" class="form-control lastname" disabled>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-check mt-4 pt-2">
                                                        <input type="checkbox" class="form-check-input author-toggle" id="manualAuthorCheck0">
                                                        <label class="form-check-label" for="manualAuthorCheck0">Manual entry</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 text-end"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4" id="publicationDetailsTitle">Publication Details — Journal Article</h5>

                                    <div id="ArticleFields" class="type-fields">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Journal</label>
                                                <input type="text" name="journal" class="form-control"
                                                    value="<?= htmlspecialchars($publication['journal'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Volume</label>
                                                <input type="number" name="volume" class="form-control"
                                                    value="<?= htmlspecialchars($publication['volume'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Number</label>
                                                <input type="number" name="number" class="form-control"
                                                    value="<?= htmlspecialchars($publication['number'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Pages</label>
                                                <input type="text" name="pages" class="form-control"
                                                    value="<?= htmlspecialchars($publication['pages'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">ISSN</label>
                                                <input type="text" name="issn" class="form-control"
                                                    value="<?= htmlspecialchars($publication['issn'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">ISBN</label>
                                                <input type="text" name="isbn" class="form-control"
                                                    value="<?= htmlspecialchars($publication['isbn'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="publisher" class="form-control"
                                                    value="<?= htmlspecialchars($publication['publisher'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="ConferencePaperFields" class="type-fields d-none">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Conference Name</label>
                                                <input type="text" name="conferenceName" class="form-control"
                                                    value="<?= htmlspecialchars($publication['conferenceName'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Pages</label>
                                                <input type="text" name="conferencePages" class="form-control"
                                                    value="<?= htmlspecialchars($publication['conferencePages'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="conferencePublisher" class="form-control"
                                                    value="<?= htmlspecialchars($publication['conferencePublisher'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="BookFields" class="type-fields d-none">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="bookPublisher" class="form-control"
                                                    value="<?= htmlspecialchars($publication['bookPublisher'] ?? ($publication['publisher'] ?? '')) ?>">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Volume</label>
                                                <input type="number" name="bookVolume" class="form-control"
                                                    value="<?= htmlspecialchars($publication['bookVolume'] ?? ($publication['volume'] ?? '')) ?>">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Series</label>
                                                <input type="text" name="series" class="form-control"
                                                    value="<?= htmlspecialchars($publication['series'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Edition</label>
                                                <input type="text" name="edition" class="form-control"
                                                    value="<?= htmlspecialchars($publication['edition'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">ISBN</label>
                                                <input type="text" name="isbn" class="form-control"
                                                    value="<?= htmlspecialchars($publication['isbn'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="ThesisFields" class="type-fields d-none">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">School</label>
                                                <input type="text" name="school" class="form-control"
                                                    value="<?= htmlspecialchars($publication['school'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Department</label>
                                                <input type="text" name="department" class="form-control"
                                                    value="<?= htmlspecialchars($publication['department'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Thesis Type</label>
                                                <select name="thesisType" class="form-select">
                                                    <option value="">Select type</option>
                                                    <option value="BSc" <?= (($publication['thesisType'] ?? '') === 'BSc') ? 'selected' : '' ?>>BSc</option>
                                                    <option value="MSc" <?= (($publication['thesisType'] ?? '') === 'MSc') ? 'selected' : '' ?>>MSc</option>
                                                    <option value="PhD" <?= (($publication['thesisType'] ?? '') === 'PhD') ? 'selected' : '' ?>>PhD</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">File Upload</h5>

                                    <?php if (!empty($publication['filePath'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current PDF</label>

                                            <a href="<?= htmlspecialchars($pdfUrl) ?>"
                                            target="_blank"
                                            class="d-block border rounded bg-light px-3 py-2 small text-decoration-none text-dark"
                                            style="word-break: break-all;">

                                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>
                                                <?= htmlspecialchars($publication['filePath']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="form-label">Replace PDF</label>
                                        <input type="file" name="pdf" accept=".pdf" class="form-control">
                                        <div class="form-text">Leave empty if you do not want to replace the current file.</div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" id="submitBtn" class="btn btn-success px-4">
                                            <i class="bi bi-check-circle me-1"></i>Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </main>
            <div class="container">
                <?php include(BASE_PATH . 'includes/footer.php'); ?>
            </div>
    </body>        
<script>

    var input = document.querySelector('input[name=keywords]');
    const keywordInput = document.querySelector('input[name=keywords]');
    const tagify = new Tagify(keywordInput);

    let authorIndex = <?= !empty($authors) ? count($authors) : 1 ?>;

    const currentUserAuthor = {
        firstname: <?= json_encode($_SESSION['user']['firstname'] ?? '') ?>,
        lastname: <?= json_encode($_SESSION['user']['lastname'] ?? '') ?>,
        orcid: <?= json_encode($_SESSION['user']['orcid'] ?? '') ?>
    };

    function showFieldsForType(type) {
        document.querySelectorAll('.type-fields').forEach(div => {
            div.classList.add('d-none');

            div.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = true;
            });
        });

        const target = document.getElementById(type + 'Fields');
        if (target) {
            target.classList.remove('d-none');

            target.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = false;
            });
        }

        const title = document.getElementById('publicationDetailsTitle');
        if (title) {
            title.textContent = `Publication Details — ${getPublicationTypeLabel(type)}`;
        }
    }


    function createInitialAuthorRow() {
    return `
        <div class="row g-3 align-items-end mb-3 author-entry" data-index="0">
            <div class="col-md-3">
                <label class="form-label">ORCID</label>
                <input type="text" name="authors[0][orcid]" class="form-control orcid-input" placeholder="0000-0000-0000-0000">
            </div>
            <div class="col-md-3">
                <label class="form-label">First Name</label>
                <input type="text" name="authors[0][firstname]" class="form-control firstname" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="authors[0][lastname]" class="form-control lastname" disabled>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4 pt-2">
                    <input type="checkbox" class="form-check-input author-toggle" id="manualAuthorCheck0">
                    <label class="form-check-label" for="manualAuthorCheck0">Manual entry</label>
                </div>
            </div>
            <div class="col-md-1 text-end"></div>
        </div>
    `;
}


    function createAuthorEntry(index) {
        return `
            <div class="row g-3 align-items-end mb-3 author-entry" data-index="${index}">
                <div class="col-md-3">
                    <label class="form-label">ORCID</label>
                    <input type="text" name="authors[${index}][orcid]" class="form-control orcid-input" placeholder="0000-0000-0000-0000">
                </div>
                <div class="col-md-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="authors[${index}][firstname]" class="form-control firstname" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="authors[${index}][lastname]" class="form-control lastname" disabled>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4 pt-2">
                        <input type="checkbox" class="form-check-input author-toggle" id="manualAuthorCheck${index}">
                        <label class="form-check-label" for="manualAuthorCheck${index}">Manual entry</label>
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-author">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function setLoadingState(buttonId, text) {
        const btn = document.getElementById(buttonId);
        if (!btn) return;

        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;
    }

    function fetchORCIDInfo(orcid, row) {
        const url = `https://pub.orcid.org/v3.0/${orcid}/person`;
        fetch(url, { headers: { "Accept": "application/json" } })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                const first = data?.name?.["given-names"]?.value || "";
                const last = data?.name?.["family-name"]?.value || "";
                row.querySelector(".firstname").value = first;
                row.querySelector(".lastname").value = last;
            })
            .catch(() => {
                row.querySelector(".firstname").value = "";
                row.querySelector(".lastname").value = "";
                alert("Could not fetch ORCID data.");
            });
    }

    document.getElementById("add-author").addEventListener("click", function () {
        const container = document.getElementById("authors-container");
        container.insertAdjacentHTML("beforeend", createAuthorEntry(authorIndex++));
    });

    document.getElementById("authors-container").addEventListener("input", function (e) {
        if (e.target.classList.contains("orcid-input")) {
            const row = e.target.closest(".author-entry");
            if (!row.querySelector(".author-toggle").checked && e.target.value.length >= 15) {
                fetchORCIDInfo(e.target.value.trim(), row);
            }
        }
    });

    document.getElementById("authors-container").addEventListener("change", function (e) {
        if (e.target.classList.contains("author-toggle")) {
            const row = e.target.closest(".author-entry");
            const orcidField = row.querySelector(".orcid-input");
            const firstname = row.querySelector(".firstname");
            const lastname = row.querySelector(".lastname");

            if (e.target.checked) {
                orcidField.disabled = true;
                orcidField.value = '';

                firstname.disabled = false;
                lastname.disabled = false;
                firstname.readOnly = false;
                lastname.readOnly = false;
            } else {
                orcidField.disabled = false;

                firstname.disabled = true;
                lastname.disabled = true;
                firstname.value = "";
                lastname.value = "";
            }
        }
    });

    document.getElementById("authors-container").addEventListener("click", function (e) {
        const removeBtn = e.target.closest(".remove-author");
        if (removeBtn) {
            removeBtn.closest(".author-entry").remove();
            refreshAddMeButtonState();
        }
    });

    document.getElementById("publicationForm").addEventListener("submit", function(event) {
        event.preventDefault();

        const doi = document.getElementById('doi').value.trim();

        setLoadingState('submitBtn', 'Submitting...');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        document.getElementById('message').innerHTML = `
            <div class="alert alert-info d-flex align-items-center">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Processing publication update. This may take a few seconds...
            </div>`;

        fetch('../backend/edit_publication_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("publicationForm"))
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                sessionStorage.setItem("successMessage", data.message);
                window.location.href = `review_publication.php?uri=${encodeURIComponent("<?= $uri ?>")}`;
            } else {
                document.getElementById("message").innerHTML =
                    `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            document.getElementById("message").innerHTML =
                `<div class="alert alert-danger">Submission failed. Please check the backend response.</div>`;
        })
        .finally(() => {
            const btn = document.getElementById('submitBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Changes';
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        refreshAddMeButtonState();
    });

    document.getElementById('addCurrentUserAuthor').addEventListener('click', function () {
        const hasName = (currentUserAuthor.firstname || '').trim() || (currentUserAuthor.lastname || '').trim();
        const hasOrcid = (currentUserAuthor.orcid || '').trim();

        if (!hasName && !hasOrcid) {
            document.getElementById("message").innerHTML =
                `<div class="alert alert-warning">Your profile does not contain enough author information yet.</div>`;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (currentUserAlreadyExistsInAuthors()) {
            document.getElementById("message").innerHTML =
                `<div class="alert alert-info">You are already included in the author list.</div>`;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const container = document.getElementById("authors-container");
        container.insertAdjacentHTML("beforeend", createAuthorEntry(authorIndex++));
        const row = container.querySelector(".author-entry:last-child");

        fillAuthorRowWithCurrentUser(row);
    });

   document.getElementById("showDetails").addEventListener("click", function () {
        const section = document.getElementById("detailsSection");
        const icon = document.getElementById("helpIcon");

        section.classList.toggle("d-none");

        icon.classList.toggle("bi-lightbulb");
        icon.classList.toggle("bi-lightbulb-off");
    });

    document.addEventListener('keydown', function (e) {

        if (e.key === 'Enter') {

            const target = e.target;

            if (target.tagName === 'TEXTAREA') return;

            if (target.classList.contains('tagify__input')) return;

            e.preventDefault();
        }
    });
    
   function fillAuthorRowWithCurrentUser(row) {
        if (!row) return;

        const orcidField = row.querySelector(".orcid-input");
        const firstnameField = row.querySelector(".firstname");
        const lastnameField = row.querySelector(".lastname");
        const toggle = row.querySelector(".author-toggle");

        const firstname = (currentUserAuthor.firstname || '').trim();
        const lastname = (currentUserAuthor.lastname || '').trim();
        const orcid = (currentUserAuthor.orcid || '').trim();

        if (orcid !== '') {
            toggle.checked = false;

            orcidField.disabled = false;
            orcidField.readOnly = false;
            orcidField.value = orcid;

            firstnameField.disabled = true;
            lastnameField.disabled = true;
            firstnameField.value = firstname;
            lastnameField.value = lastname;
        } else {
            toggle.checked = true;

            orcidField.value = '';
            orcidField.disabled = true;

            firstnameField.disabled = false;
            lastnameField.disabled = false;
            firstnameField.readOnly = false;
            lastnameField.readOnly = false;

            firstnameField.value = firstname;
            lastnameField.value = lastname;
        }
        refreshAddMeButtonState();
    }

function getAvailableAuthorRow() {
    const rows = document.querySelectorAll('#authors-container .author-entry');

    for (const row of rows) {
        const orcid = row.querySelector('.orcid-input')?.value.trim();
        const firstname = row.querySelector('.firstname')?.value.trim();
        const lastname = row.querySelector('.lastname')?.value.trim();

        if (!orcid && !firstname && !lastname) {
            return row;
        }
    }

    const container = document.getElementById("authors-container");
    container.insertAdjacentHTML("beforeend", createAuthorEntry(authorIndex++));
    return container.querySelector(".author-entry:last-child");
}

function normalizeAuthorValue(value) {
    return (value || '').trim().toLowerCase();
}

function currentUserAlreadyExistsInAuthors() {
    const currentFirstname = normalizeAuthorValue(currentUserAuthor.firstname);
    const currentLastname = normalizeAuthorValue(currentUserAuthor.lastname);
    const currentOrcid = normalizeAuthorValue(currentUserAuthor.orcid);

    const rows = document.querySelectorAll('#authors-container .author-entry');

    for (const row of rows) {
        const rowOrcid = normalizeAuthorValue(row.querySelector('.orcid-input')?.value);
        const rowFirstname = normalizeAuthorValue(row.querySelector('.firstname')?.value);
        const rowLastname = normalizeAuthorValue(row.querySelector('.lastname')?.value);

        // Αν ο χρήστης έχει ORCID, αυτό είναι το ισχυρότερο match
        if (currentOrcid && rowOrcid && currentOrcid === rowOrcid) {
            return true;
        }

        // Αν δεν έχει ORCID, ελέγχουμε ονοματεπώνυμο
        if (!currentOrcid && currentFirstname && currentLastname) {
            if (rowFirstname === currentFirstname && rowLastname === currentLastname) {
                return true;
            }
        }

        // fallback: αν έχει ORCID αλλά στη γραμμή υπάρχει μόνο όνομα
        if (currentFirstname && currentLastname) {
            if (rowFirstname === currentFirstname && rowLastname === currentLastname) {
                return true;
            }
        }
    }

    return false;
}

function refreshAddMeButtonState() {
    const addMeBtn = document.getElementById('addCurrentUserAuthor');
    if (!addMeBtn) return;

    if (currentUserAlreadyExistsInAuthors()) {
        addMeBtn.disabled = true;
        addMeBtn.classList.add('disabled');
    } else {
        addMeBtn.disabled = false;
        addMeBtn.classList.remove('disabled');
    }
}

    document.addEventListener('DOMContentLoaded', function () {
        showFieldsForType(<?= json_encode($publicationType ?: 'Article') ?>);
    });

</script>
																										
</html>
