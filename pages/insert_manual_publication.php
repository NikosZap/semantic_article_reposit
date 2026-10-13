
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
        const currentUserAuthor = {
                firstname: <?= json_encode($_SESSION['user']['firstname'] ?? '') ?>,
                lastname: <?= json_encode($_SESSION['user']['lastname'] ?? '') ?>,
                orcid: <?= json_encode($_SESSION['user']['orcid'] ?? '') ?>
            };
    
    </script>

    <script src="<?= BASE_URL ?>scripts/insert_manual_publication.js"></script>
        																									
</html>
