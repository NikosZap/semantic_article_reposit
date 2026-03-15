<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if (empty($_SESSION['user']['is_logged_in']) || $_SESSION['user']['role'] !== 'Admin') {
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
            <button id="toggleFilters" class="btn btn-secondary btn-sm">Show Filters</button>
        </div>

        <div id="message"></div>

        <div id="filterSection" style="display:none;">
            <form id="filterForm" class="mb-4">

                <div class="mb-3">
                    <label for="search">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Search (title, DOI, keyword, author etc)">
                </div>

                <div class="mb-3">
                    <label for="type">Type</label>
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

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>

            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>DOI</th>
                        <th>Publication Date</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="publicationTableBody"></tbody>

            </table>
        </div>

        <div id="pagination" class="mt-3"></div>

    </div>
</main>

<div class="container">
    <?php include(BASE_PATH . 'includes/footer.php'); ?>
</div>

<script>

let currentPage = 1;

function loadPublications(page = 1, callback = null) {

    let search = $("#search").val();
    let type = $("#type").val();
    let pubDateFrom = $("#pubDateFrom").val();
    let pubDateTo = $("#pubDateTo").val();
    let uploadDateFrom = $("#uploadDateFrom").val();
    let uploadDateTo = $("#uploadDateTo").val();

    $.ajax({
        url: "../backend/fetch_systems_publications.php",
        method: "POST",
        data: { page, search, type, pubDateFrom, pubDateTo, uploadDateFrom, uploadDateTo },
        dataType: "json",

        success: function(response) {

            const tbody = $("#publicationTableBody");
            tbody.html("");

            if (response.publications.length === 0) {

                $("#message").html('<div class="alert alert-warning">No publications found.</div>');

            } else {

                $("#message").html('');

                response.publications.forEach(pub => {

                    const row = `
<tr>
<td>${pub.title}</td>
<td>${pub.doi}</td>
<td>${pub.pubDate}</td>
<td>${pub.uploadDate}</td>
<td>
<button onclick="reviewPublication('${pub.uri}')" class="btn btn-primary btn-sm">
<i class="bi bi-eye"></i>
</button>
<button class="btn btn-danger btn-sm" onclick="reject('${pub.uri}')">
<i class="bi bi-trash"></i>
</button>
</td>
</tr>
`;

                    tbody.append(row);

                });
            }

            updatePagination(response.totalPages, response.currentPage);

            if (typeof callback === "function") callback();
        },

        error: function() {
            $("#message").html('<div class="alert alert-danger">Data loading error.</div>');
        }

    });

}

function updatePagination(totalPages, currentPage) {

    let paginationHtml = `
<button class="btn btn-secondary" onclick="changePage(${currentPage - 1})" ${currentPage == 1 ? "disabled" : ""}>Back</button>
<span class="mx-3">Page ${currentPage} / ${totalPages}</span>
<button class="btn btn-secondary" onclick="changePage(${currentPage + 1})" ${currentPage == totalPages ? "disabled" : ""}>Next</button>
`;

    $("#pagination").html(paginationHtml);

}

function changePage(newPage) {

    if (newPage > 0) {
        currentPage = newPage;
        loadPublications(currentPage);
    }

}

$("#filterForm").on("submit", function(e) {
    e.preventDefault();
    currentPage = 1;
    loadPublications();
});

function reject(uri) {

    if (!confirm("Are you sure you want to reject this publication?")) return;

    $.post(
        "../backend/approve_publications_backend.php",
        { action: "reject", uri },

        function(res) {

            if (res.status === "success") {

                $("#message").html('<div class="alert alert-success">' + res.message + '</div>');
                loadPublications(currentPage);

            } else {

                $("#message").html('<div class="alert alert-danger">Error rejecting publication.</div>');
            }

        },
        "json"
    );
}

function reviewPublication(uri) {
    window.location.href = `review_publication.php?uri=${encodeURIComponent(uri)}`;
}

function showMessage(type, message) {

    let alertClass = type === "success" ? "alert-success" : "alert-danger";

    if ($("#message").length === 0) {
        console.log("The #message component does not exist");
        return;
    }

    $("#message").html(`<div class="alert ${alertClass}">${message}</div>`).fadeIn();

    setTimeout(() => {
        $("#message").fadeOut();
    }, 5000);
}

$(document).ready(function () {

    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get("message");

    loadPublications(currentPage, () => {

        if (msg) {
            showMessage("success", decodeURIComponent(msg));
            window.history.replaceState({}, document.title, window.location.pathname);
        }

    });

});

$("#toggleFilters").on("click", function () {

    $("#filterSection").slideToggle(200);

    const current = $(this).text();
    $(this).text(current === "Show Filters" ? "Hide Filters" : "Show Filters");

});

</script>

</body>
</html>