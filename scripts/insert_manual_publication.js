var input = document.querySelector('input[name=keywords]');
const keywordInput = document.querySelector('input[name=keywords]');
const tagify = new Tagify(keywordInput);

let authorIndex = 1;

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
