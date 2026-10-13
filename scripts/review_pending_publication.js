
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
                    window.location.href =
                        'approve_publications.php?message=' +
                        encodeURIComponent(data.message || 'Publication approved successfully.');
                }else {
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
                    window.location.href =
                        'approve_publications.php?message=' +
                        encodeURIComponent(data.message || 'Publication rejected successfully.') +
                        '&type=success';
                }else {
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

        document.getElementById('approveBtn')?.addEventListener('click', function () {
            approvePublication();
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

        
