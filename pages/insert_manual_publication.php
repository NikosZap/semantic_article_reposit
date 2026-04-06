
<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

if (empty($_SESSION['user']['is_logged_in']) || ($_SESSION['user']['role'] !== 'Admin' && $_SESSION['user']['role'] !== 'Researcher')) {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include(BASE_PATH . 'includes/header.php'); ?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
			<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
			<title>Insert New Publication</title>
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
                                <h1 class="display-5">Insert New Publication</h1>
                                <p class="text-muted mb-0">Fill in the publication details and submit it for review.</p>
                            </div>
                            <button id="showDetails" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-lightbulb me-1" id="helpIcon"></i> Help
                            </button>
                        </div>

                        <div id="message" class="mb-4"></div>

                       <div id="detailsSection" class="alert alert-info d-none">
                            <h6 class="mb-3">Submission Guidelines</h6>
                            <ul class="mb-0 ps-3">
                                <li>Complete all required fields before submitting.</li>
                                <li>Add at least one author.</li>
                                <li>Use ORCID when available; otherwise use manual author entry.</li>
                                <li>DOI is optional.</li>
                                <li>Only PDF files are accepted.</li>
                                <li>Choose the correct publication type to complete the appropriate details.</li>
                            </ul>
                        </div>

                        <form action="../backend/insert_manual_publication_backend.php" method="POST" id="publicationForm" enctype="multipart/form-data">

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">Basic Information</h5>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Publication Title <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">DOI</label>
                                            <input type="text" name="doi" id="doi" class="form-control">
                                            <div class="form-text">Optional. It may exist for some publications, but it is not mandatory.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Publication Date <span class="text-danger">*</span></label>
                                            <input type="date" name="pubDate" class="form-control" required>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-2">Type of Publication</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="pubType" value="Article" checked id="typeArticle">
                                                    <label class="form-check-label" for="typeArticle">Journal Article</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="pubType" value="ConferencePaper" id="typeConference">
                                                    <label class="form-check-label" for="typeConference">Conference Paper</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="pubType" value="Book" id="typeBook">
                                                    <label class="form-check-label" for="typeBook">Book</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="pubType" value="Thesis" id="typeThesis">
                                                    <label class="form-check-label" for="typeThesis">Thesis</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">Abstract & Keywords</h5>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="keywords" class="form-label">Keywords</label>
                                            <input id="keywords" name="keywords" class="form-control">
                                            <div class="form-text">Add keywords separated with commas</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Abstract</label>
                                            <textarea name="abstract" rows="6" class="form-control" placeholder="Write the abstract here..."></textarea>
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
                                            <div class="col-md-1 text-end">
                                            </div>
                                        </div>
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
                                                <input type="text" name="journal" class="form-control" >
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Volume</label>
                                                <input type="number" name="volume" class="form-control" >
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Number</label>
                                                <input type="number" name="number" class="form-control" >
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Pages</label>
                                                <input type="text" name="pages" class="form-control">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">ISSN</label>
                                                <input type="text" name="issn" class="form-control" >
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">ISBN</label>
                                                <input type="text" name="isbn" class="form-control" >
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="publisher" class="form-control" >
                                            </div>

                                        </div>
                                    </div>

                                    <div id="ConferencePaperFields" class="type-fields d-none">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Conference Name</label>
                                                <input type="text" name="conferenceName" class="form-control" >
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Pages</label>
                                                <input type="text" name="conferencePages" class="form-control" >
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="conferencePublisher" class="form-control" >
                                            </div>
                                        </div>
                                    </div>

                                    <div id="BookFields" class="type-fields d-none">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" name="bookPublisher" class="form-control" >
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Volume</label>
                                                <input type="number" name="bookVolume" class="form-control" >
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Series</label>
                                                <input type="text" name="series" class="form-control" >
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Edition</label>
                                                <input type="text" name="edition" class="form-control" >
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">ISBN</label>
                                                <input type="text" name="isbn" class="form-control" >
                                            </div>
                                        </div>
                                    </div>

                                    <div id="ThesisFields" class="type-fields d-none">
                                        <div class="row g-3">

                                            <div class="col-md-4">
                                                <label class="form-label">School</label>
                                                <input type="text" name="school" class="form-control" >
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Department</label>
                                                <input type="text" name="department" class="form-control" >
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Thesis Type</label>
                                                <select name="thesisType" class="form-select">
                                                    <option value="">Select type</option>
                                                    <option value="BSc">BSc</option>
                                                    <option value="MSc">MSc</option>
                                                    <option value="PhD">PhD</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">File Upload </h5>

                                    <div class="mb-3">
                                        <label class="form-label">Upload PDF <span class="text-danger">*</span></label>
                                        <input type="file" name="pdf" accept=".pdf" class="form-control" required>
                                        <div class="form-text">Upload the full publication in PDF format.</div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" id="submitBtn" class="btn btn-success px-4">
                                            <i class="bi bi-check-circle me-1"></i>Submit Publication
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

    let authorIndex = 1;

    const currentUserAuthor = {
        firstname: <?= json_encode($_SESSION['user']['firstname'] ?? '') ?>,
        lastname: <?= json_encode($_SESSION['user']['lastname'] ?? '') ?>,
        orcid: <?= json_encode($_SESSION['user']['orcid'] ?? '') ?>
    };

    function showFieldsForType(type) {
        document.querySelectorAll('.type-fields').forEach(div => div.classList.add('d-none'));

        const target = document.getElementById(type + 'Fields');
        if (target) {
            target.classList.remove('d-none');
        }

        const title = document.getElementById('publicationDetailsTitle');
        if (title) {
            title.textContent = `Publication Details — ${getPublicationTypeLabel(type)}`;
        }
    }
        document.addEventListener('DOMContentLoaded', function () {
            const selectedType = document.querySelector('input[name="pubType"]:checked');
            if (selectedType) {
                showFieldsForType(selectedType.value);
            }
        });

        document.addEventListener("DOMContentLoaded", () => {
            const radios = document.querySelectorAll('input[name="pubType"]');
            radios.forEach(r => {
                r.addEventListener('change', (e) => showFieldsForType(e.target.value));
            });
        });


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

            refreshAddMeButtonState();
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

        const type = document.querySelector('input[name="pubType"]:checked')?.value || '';
        const doi = document.getElementById('doi').value.trim();

        setLoadingState('submitBtn', 'Submitting...');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        document.getElementById('message').innerHTML = `
            <div class="alert alert-info d-flex align-items-center">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Processing publication submission. This may take a few seconds...
            </div>`;

        fetch('../backend/insert_manual_publication_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("publicationForm"))
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById("message").innerHTML =
                    `<div class="alert alert-success">${data.message}</div>`;
                const form = document.getElementById('publicationForm');
                form.reset();
                tagify.removeAllTags();
                document.getElementById('authors-container').innerHTML = createInitialAuthorRow();
                showFieldsForType('Article');
                refreshAddMeButtonState();
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
                btn.innerHTML = 'Submit Publication';
            }
        });
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

        let row = getAvailableAuthorRow();

        if (!row) {
            const container = document.getElementById("authors-container");
            container.insertAdjacentHTML("beforeend", createAuthorEntry(authorIndex++));
            row = container.querySelector(".author-entry:last-child");
        }

        fillAuthorRowWithCurrentUser(row);
    });

   document.getElementById("showDetails").addEventListener("click", function () {
        const section = document.getElementById("detailsSection");
        const icon = document.getElementById("helpIcon");

        section.classList.toggle("d-none");

        icon.classList.toggle("bi-lightbulb");
        icon.classList.toggle("bi-lightbulb-off");
    });

    document.addEventListener('DOMContentLoaded', function () {
        refreshAddMeButtonState();
    });

    document.addEventListener('keydown', function (e) {

        if (e.key === 'Enter') {

            const target = e.target;

            if (target.tagName === 'TEXTAREA') return;

            if (target.classList.contains('tagify__input')) return;

            e.preventDefault();
        }
    });

    function getPublicationTypeLabel(type) {
        const labels = {
            Article: 'Journal Article',
            ConferencePaper: 'Conference Paper',
            Book: 'Book',
            Thesis: 'Thesis'
        };

        return labels[type] || type;
        }
    
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

        if (currentOrcid && rowOrcid && currentOrcid === rowOrcid) {
            return true;
        }

        if (!currentOrcid && currentFirstname && currentLastname) {
            if (rowFirstname === currentFirstname && rowLastname === currentLastname) {
                return true;
            }
        }

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

    return null;
}

</script>
																										
</html>
