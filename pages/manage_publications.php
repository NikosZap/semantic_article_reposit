<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ( empty($_SESSION['user']['is_logged_in']) || !in_array($_SESSION['user']['role'], ['Admin', 'Researcher'])) {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Publication Management</title>
</head>

<body>

<?php include(BASE_PATH . 'includes/navbar.php'); ?>

<main>
    <div class="container mt-5">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="display-6">Publication Management</h2>
            <button id="toggleFilters" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-funnel"></i> Show Filters
            </button></div>

        <div id="message"></div>

        <div id="filterSection" style="display:none;">
            <form id="filterForm" class="mb-4">

                <div class="mb-3">
                    <label for="search">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Search (title, DOI, keyword, author etc)">
                </div>

                <div class="mb-3">
                    <label for="type">Category</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All</option>
                        <option value="Article">Article</option>
                        <option value="ConferencePaper">Conference Paper</option>
                        <option value="Book">Book</option>
                        <option value="Thesis">Thesis</option>
                    </select>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pubDateFrom">Publication Date (From)</label>
                        <input type="date" class="form-control" id="pubDateFrom" name="pubDateFrom">
                    </div>

                    <div class="col-md-6">
                        <label for="pubDateTo">Publication Date (To)</label>
                        <input type="date" class="form-control" id="pubDateTo" name="pubDateTo">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="uploadDateFrom">Upload Date (From)</label>
                        <input type="date" class="form-control" id="uploadDateFrom" name="uploadDateFrom">
                    </div>

                    <div class="col-md-6">
                        <label for="uploadDateTo">Upload Date (To)</label>
                        <input type="date" class="form-control" id="uploadDateTo" name="uploadDateTo">
                    </div>
                </div>

                <div class="mb-3" id="scopeFilter">
                    <label for="onlyMine">Scope</label>
                    <select id="onlyMine" name="onlyMine" class="form-select">
                        <option value="false">All Publications</option>
                        <option value="true">My Publications</option>
                    </select>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>

            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>DOI</th>
                        <th>Category</th>
                        <th>Publication Date</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="publicationTableBody"></tbody>

            </table>
        </div>
    </div>
</main>

<div class="container">
    <div id="pagination" class="mt-3"></div>
    <?php include(BASE_PATH . 'includes/footer.php'); ?>
</div>

<script src="<?= BASE_URL ?>scripts/manage_publications.js"></script>

</body>
</html>