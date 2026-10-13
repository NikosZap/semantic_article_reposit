const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

        document.getElementById('searchText').addEventListener('keypress', function (e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                triggerSpotlight();
            }
        });

        function triggerSpotlight() {
            const text = document.getElementById('searchText').value.trim();

            if (!text) {
                alert('Please enter a phrase.');
                return;
            }

            fetch('backend/spotlight_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'text=' + encodeURIComponent(text)
            })
                .then(response => response.json())
                .then(entities => {
                    const container = document.getElementById('results');
                    container.innerHTML = '';
                    document.getElementById('resultsContainer').style.display = 'block';

                    if (!entities.length) {
                        container.innerHTML = '<div class="alert alert-warning">No entities found.</div>';
                        return;
                    }

                    let html = '<form method="get" action="semantic_search.php" id="conceptForm">';
                    html += '<div class="list-group">';
                    html += '<h5>Concepts Found:</h5>';
                    if (entities.length > 1) {
                        html += `
                            <div class="list-group-item  bg-light">
                                <div class="fw-bold mb-2">Search Mode</div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input search-mode" type="radio" name="mode" id="modeAnd" value="and" checked>
                                    <label class="form-check-label" for="modeAnd">
                                        Match ALL detected concepts (AND)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input search-mode" type="radio" name="mode" id="modeOr" value="or">
                                    <label class="form-check-label" for="modeOr">
                                        Match ANY selected concept (OR)
                                    </label>
                                </div>
                            </div>
                        `;
                    }

                    entities.forEach(ent => {
                        html += `
                            <label class="list-group-item">
                                <input class="form-check-input me-1 concept-checkbox" type="checkbox" name="uri[]" value="${ent.uri}">
                                <span class="fw-semibold">
                                    ${ent.label}
                                </span> 
                                <small class="text-muted d-block d-md-inline">
                                    (${ent.uri})
                                </small>
                            </label>
                        `;
                    });

                    html += '</div>';

                    html += `
                        <div class="mt-3">
                            <button class="btn btn-primary" type="submit">
                                Search Concepts
                            </button>
                        </div>
                    `;

                    html += '</form>';

                    container.innerHTML = html;

                    const conceptForm = document.getElementById('conceptForm');
                    const modeAnd = document.getElementById('modeAnd');
                    const modeOr = document.getElementById('modeOr');

                    if (modeAnd && modeOr) {                        
                        function updateConceptSelectionMode() {
                            const conceptCheckboxes = conceptForm.querySelectorAll('.concept-checkbox');

                            if (modeAnd.checked) {
                                conceptCheckboxes.forEach(cb => {
                                    cb.checked = true;
                                    cb.disabled = true;
                                });
                            } else {
                                conceptCheckboxes.forEach(cb => {
                                    cb.disabled = false;
                                });
                            }
                        }
                                                
                        modeAnd.addEventListener('change', updateConceptSelectionMode);
                        modeOr.addEventListener('change', updateConceptSelectionMode);

                        updateConceptSelectionMode();

                    }

                    conceptForm.addEventListener('submit', function (e) {
                        const checked = conceptForm.querySelectorAll('input[name="uri[]"]:checked');

                        if (!checked.length) {
                            e.preventDefault();
                            alert('Please select at least one concept.');
                            return;
                        }

                        const disabledCheckboxes = conceptForm.querySelectorAll('.concept-checkbox:disabled');
                        disabledCheckboxes.forEach(cb => cb.disabled = false);
                    });
                })
                .catch(err => {
                    console.error(err);
                    alert('Error contacting DBpedia Spotlight.');
                });
        }