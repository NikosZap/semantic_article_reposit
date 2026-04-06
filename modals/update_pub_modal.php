<div class="modal fade" id="updatePublicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="updatePublicationForm">
                    <input type="hidden" name="uri" value="<?= htmlspecialchars($uri) ?>">

                    <div class="mb-4">
                        <h6 class="fw-semibold border-bottom pb-2">Basic Information</h6>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title"
                                value="<?= htmlspecialchars($publication['title'] ?? '') ?>">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">DOI</label>
                                <input type="text" class="form-control" name="doi"
                                    value="<?= htmlspecialchars($publication['doi'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <div class="form-control bg-light">
                                    <?= htmlspecialchars(extractSuffix($publication['type'] ?? '')) ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Publication Date</label>
                                <input type="text" class="form-control" name="publicationDate"
                                    value="<?= htmlspecialchars($publication['publicationDate'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Abstract</label>
                            <textarea class="form-control" name="abstract" rows="5"><?= htmlspecialchars($publication['abstract'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="type-group" data-type-group="Article">
                        <h6 class="fw-semibold border-bottom pb-2">Journal Article Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Journal</label>
                                <input type="text" class="form-control" name="journal"
                                    value="<?= htmlspecialchars($publication['journal'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Publisher</label>
                                <input type="text" class="form-control" name="publisher"
                                    value="<?= htmlspecialchars($publication['publisher'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Volume</label>
                                <input type="text" class="form-control" name="volume"
                                    value="<?= htmlspecialchars($publication['volume'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Number</label>
                                <input type="text" class="form-control" name="number"
                                    value="<?= htmlspecialchars($publication['number'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pages</label>
                                <input type="text" class="form-control" name="pages"
                                    value="<?= htmlspecialchars($publication['pages'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ISSN</label>
                                <input type="text" class="form-control" name="issn"
                                    value="<?= htmlspecialchars($publication['issn'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="type-group d-none" data-type-group="ConferencePaper">
                        <h6 class="fw-semibold border-bottom pb-2">Conference Paper Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Journal / Venue</label>
                                <input type="text" class="form-control" name="journal"
                                    value="<?= htmlspecialchars($publication['journal'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Publisher</label>
                                <input type="text" class="form-control" name="publisher"
                                    value="<?= htmlspecialchars($publication['publisher'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pages</label>
                                <input type="text" class="form-control" name="pages"
                                    value="<?= htmlspecialchars($publication['pages'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="type-group d-none" data-type-group="Book">
                        <h6 class="fw-semibold border-bottom pb-2">Book Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Publisher</label>
                                <input type="text" class="form-control" name="publisher"
                                    value="<?= htmlspecialchars($publication['publisher'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn"
                                    value="<?= htmlspecialchars($publication['isbn'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="type-group d-none" data-type-group="Thesis">
                        <h6 class="fw-semibold border-bottom pb-2">Thesis Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">School</label>
                                <input type="text" class="form-control" name="school"
                                    value="<?= htmlspecialchars($publication['school'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department"
                                    value="<?= htmlspecialchars($publication['department'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Type of Thesis</label>
                                <select class="form-select" name="thesisType">
                                    <option value="">Select thesis type</option>
                                    <option value="BSc" <?= ($publication['thesisType'] ?? '') === 'BSc' ? 'selected' : '' ?>>BSc</option>
                                    <option value="MSc" <?= ($publication['thesisType'] ?? '') === 'MSc' ? 'selected' : '' ?>>MSc</option>
                                    <option value="PhD" <?= ($publication['thesisType'] ?? '') === 'PhD' ? 'selected' : '' ?>>PhD</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning text-dark" id="saveUpdateBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>