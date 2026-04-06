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

<script>

let currentPage = 1;

function loadPublications(page = 1, callback = null) {

    let search = $("#search").val();
    let type = $("#type").val();
    let pubDateFrom = $("#pubDateFrom").val();
    let pubDateTo = $("#pubDateTo").val();
    let uploadDateFrom = $("#uploadDateFrom").val();
    let uploadDateTo = $("#uploadDateTo").val();
    let onlyMine = $("#onlyMine").length ? $("#onlyMine").val() : "false";

    $.ajax({
        url: "../backend/fetch_publications.php",
        method: "POST",
        data: { page, search, type, pubDateFrom, pubDateTo, uploadDateFrom, uploadDateTo, onlyMine },
        dataType: "json",
   

        success: function(response) {

            const tbody = $("#publicationTableBody");
            tbody.html("");

            if (response.publications.length === 0) {

                $("#message").html('<div class="alert alert-warning">No publications found.</div>');

            } else {

                $("#message").html('');

                response.publications.forEach(pub => {

                let statusBadge = `<span class="badge bg-secondary">Unknown</span>`;

                if (pub.isApproved == 1 || pub.isApproved === "true") {
                    statusBadge = `<span class="badge bg-success">Approved</span>`;
                } else {
                    statusBadge = `<span class="badge bg-warning text-dark">Pending</span>`;
                }


                const row = `
                    <tr>
                        <td>${pub.title}</td>
                        <td>${pub.doi}</td>
                        <td>${pub.type}</td>
                        <td>${pub.pubDate}</td>
                        <td>${pub.uploadDate}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button onclick="reviewPublication('${pub.uri}')" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>`;

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

    if (totalPages <= 1) {
        $("#pagination").html("");
        return;
    }

    let html = `<nav><ul class="pagination justify-content-center mt-4">`;

    html += `
        <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link text-dark" href="#" onclick="changePage(${currentPage - 1}); return false;">
                Back
            </a>
        </li>
    `;

    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link ${i === currentPage ? 'bg-dark text-white border-dark' : 'text-dark'}"
                   href="#"
                   onclick="changePage(${i}); return false;">
                    ${i}
                </a>
            </li>
        `;
    }

    html += `
        <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link text-dark" href="#" onclick="changePage(${currentPage + 1}); return false;">
                Next
            </a>
        </li>
    `;

    html += `</ul></nav>`;

    $("#pagination").html(html);
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



function reviewPublication(uri) {
    window.location.href = `review_publication.php?uri=${btoa(uri)}`;
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

        const userRole = "<?= $_SESSION['user']['role'] ?>";

        if (userRole !== "Admin") {
            $("#scopeFilter").remove();
        }

        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get("message");

        loadPublications(currentPage, () => {
            if (msg) {
                showMessage("success", decodeURIComponent(msg));
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

    });

    $("#toggleFilters").on("click", function() {
        $("#filterSection").slideToggle(200);

        const current = $(this).text().trim();

        $(this).html(
            current.includes("Show")
            ? '<i class="bi bi-x"></i> Hide Filters'
            : '<i class="bi bi-funnel"></i> Show Filters'
        );
    });
    

</script>

</body>
</html>