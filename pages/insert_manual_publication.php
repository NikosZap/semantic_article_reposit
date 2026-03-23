
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
		<main>
			    <div class="container mt-5">
					<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
						<h3 class="display-6">Insert New Publication</h3>
						<button id="showDetails" class="btn btn-primary btn-sm">
							<i class="bi bi-lightbulb" id="helpIcon"></i>
						</button>
					</div>
					<div id="message"></div>
					<div id="detailsSection" style="display: none;">
						<p>Instructions will be added</p>
					</div>
					<form action="../backend/insert_manual_publication_backend.php" method="POST" id="publicationForm" enctype="multipart/form-data">
						<div class="mb-3">
							<label class="form-label">Publication Title</label>
							<input type="text" name="title" class="form-control" required>
						</div>
                        <div class="mb-3">
							<label class="form-label">DOI</label>
							<input type="text" name="doi" id='doi' class="form-control">
                        </div>
						<div class="mb-3">
							<label class="form-label">Date</label>
							<input type="date" name="pubDate" class="form-control" required>
						</div>
						<div class="mb-3">
						    <label for="keywords" class="form-label">Keywords</label>
							<input id="keywords" name="keywords" placeholder="Add keywords seperated with commas" class="form-control">
                        </div>
						<div class="mb-3">
							<label class="form-label">Abstract</label>
							<textarea name="abstract" rows="5" class="form-control" placeholder="Write the abstract of the article here..."></textarea>
						</div>
						<div class="mb-3">
						<!-- <p><strong>Authors</strong></p> -->
							<label class="form-label">Authors</label>
							<div id="authors-container">
								<div class="row g-2 align-items-center mb-2 author-entry" data-index="${index}">
									<div class="col-md-3">
										<input type="text" name="authors[][orcid]" class="form-control orcid-input" placeholder="ORCID ID #1">
                                    </div>
									<div class="col-md-3">
										<input type="text" name="authors[][firstname]" class="form-control firstname" placeholder="First Name #1" disabled>
									</div>
									<div class="col-md-3">
										<input type="text" name="authors[][lastname]" class="form-control lastname" placeholder="Last Name #1" disabled>
									</div>
									<div class="col-md-2 text-center">
										<input type="checkbox" class="form-check-input author-toggle" id="manualAuthorCheck" title="Manual entry">
										<label class="form-check-label" for="manualAuthorCheck">
									    	<span id="entryModeLabel">Manual entry</span>
										</label>
									</div>
								</div>
								<button type="button" class="btn btn-sm btn-outline-primary" id="add-author">
									<i class="bi bi-person-plus-fill"> Add Author</i>
								</button>
							</div>
							<div class="mb-3">
                                <label class="form-label">Upload PDF</label>
                                <input type="file" name="pdf" accept=".pdf" class="form-control">
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Type of Publication</label>
                                <div class="col-12 col-md-9 d-flex justify-content-center flex-wrap gap-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pubType" value="Article" checked>
                                        <label class="form-check-label">Journal Article</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pubType" value="ConferencePaper">
                                        <label class="form-check-label">Conference Paper</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pubType" value="Book">
                                        <label class="form-check-label">Book</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pubType" value="Thesis">
                                        <label class="form-check-label">Thesis</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Article Fields -->
                            <div id="ArticleFields" class="type-fields">
                                <div class="mb-2">
                                    <input type="text" name="journal" class="form-control" placeholder="Journal">
                                </div>
                                <div class="mb-2">
                                    <input type="number" name="volume" class="form-control" placeholder="Volume">
                                </div>
                                <div class="mb-2">
                                    <input type="number" name="number" class="form-control" placeholder="Number">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="pages" class="form-control" placeholder="Pages">
                                </div>
                            </div>

                            <!-- Conference Paper Fields -->
                            <div id="ConferencePaperFields" class="type-fields" style="display:none;">
                                <div class="mb-2">
                                    <input type="text" name="conferenceName" class="form-control" placeholder="Conference Name">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="conferencePages" class="form-control" placeholder="Pages">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="conferencePublisher" class="form-control" placeholder="Publisher">
                                </div>
                            </div>

                            <!-- Book Fields -->
                            <div id="BookFields" class="type-fields" style="display:none;">
                                <div class="mb-2">
                                    <input type="text" name="bookPublisher" class="form-control" placeholder="Publisher">
                                </div>
                                <div class="mb-2">
                                    <input type="number" name="bookVolume" class="form-control" placeholder="Volume">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="series" class="form-control" placeholder="Series">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="edition" class="form-control" placeholder="Edition">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="isbn" class="form-control" placeholder="ISBN">
                                </div>
                            </div>

                            <!-- Thesis Fields -->
                            <div id="ThesisFields" class="type-fields" style="display:none;">
                                <div class="mb-2">
                                    <input type="text" name="school" class="form-control" placeholder="School">
                                </div>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-success">Submit Publication</button>
                    </form>
                </div>
            </main>
            <div class="container">
                <?php include(BASE_PATH . 'includes/footer.php'); ?>
            </div>
    </body>        
<script>

var input = document.querySelector('input[name=keywords]');
new Tagify(input);

let authorIndex = 1;

function showFieldsForType(type) {
            document.querySelectorAll('.type-fields').forEach(div => div.style.display = 'none');
            document.getElementById(type + 'Fields').style.display = 'block';
        }

        document.addEventListener("DOMContentLoaded", () => {
            const radios = document.querySelectorAll('input[name="pubType"]');
            radios.forEach(r => {
                r.addEventListener('change', (e) => showFieldsForType(e.target.value));
            });
        });

        document.getElementById('publicationForm').addEventListener('submit', function(e) {
          const type = document.querySelector('input[name="pubType"]:checked')?.value || '';
          const doi = document.getElementById('doi').value.trim();
          const requiredTypes = ['Article', 'ConferencePaper'];

    if (requiredTypes.includes(type) && doi === '') {
      e.preventDefault();
      alert('Field DOI is mandatory for this type of publicaiton');
      document.getElementById('doi').focus();
    }
  });

function createAuthorEntry(index) {
    return `
        <div class="row g-2 align-items-center mb-2 author-entry" data-index="${index}">
			<div class="col-md-3">
				<input type="text" name="authors[${index}][orcid]" class="form-control orcid-input" placeholder="ORCID ID #${index+1}">
            </div>
			<div class="col-md-3">
				<input type="text" name="authors[${index}][firstname]" class="form-control firstname" placeholder="First Name #${index+1}" disabled>
			</div>
			<div class="col-md-3">
				<input type="text" name="authors[${index}][lastname]" class="form-control lastname" placeholder="Last Name #${index+1}" disabled>
			</div>
			<div class="col-md-2 text-center">
				<input type="checkbox" class="form-check-input author-toggle" id="manualAuthorCheck" title="Manual entry">
				<label class="form-check-label" for="manualAuthorCheck">
				<span id="entryModeLabel">Manual entry</span>
			</div>
			<div class="col-md-1 text-center">
			    <button type="button" class="btn btn-sm btn-danger remove-author">
					<i class="bi bi-trash"> Remove </i>
                </button>
			</div>
		</div>`
    ;
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
            orcidField.setAttribute("disabled", true);
            firstname.removeAttribute("disabled");
            lastname.removeAttribute("disabled");
        } else {
            orcidField.removeAttribute("disabled");
            firstname.setAttribute("disabled", true);
            lastname.setAttribute("disabled", true);
            firstname.value = "";
            lastname.value = "";
        }
    }
});

document.getElementById("authors-container").addEventListener("click", function (e) {
    if (e.target.classList.contains("remove-author")) {
        e.target.closest(".author-entry").remove();
    }
});

document.getElementById("publicationForm").addEventListener("submit", function(event) {
    event.preventDefault();

    const type = document.querySelector('input[name="pubType"]:checked')?.value || '';
    const doi = document.getElementById('doi').value.trim();
    const requiredTypes = ['Article', 'ConferencePaper'];

    if (requiredTypes.includes(type) && doi === '') {
        document.getElementById("message").innerHTML =
            `<div class="alert alert-danger">Field DOI is mandatory for this type of publication.</div>`;
        document.getElementById('doi').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

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
            document.getElementById('publicationForm').reset();
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

$("#showDetails").on("click", function () {
  $("#detailsSection").slideToggle(200);

  const icon = $("#helpIcon");
  icon.toggleClass("bi-lightbulb bi-lightbulb-off");
});


</script>
																										
																									</html>
