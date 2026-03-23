<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Approve Publication</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-6">Pending Publications</h2>

            <div id="message"></div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>DOI</th>
                            <th>Category</th>
                            <th>Upload User</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="unapprovedPubTableBody"></tbody>
                </table>
            </div>
            
        </div>
    </main>

    <div class="container mt-5">
        <div id="pagination" class="mt-3"></div>
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>
        let currentPage = 1;

        function loadPendingPublications(page = 1, callback = null) {
            $.ajax({
                url: "../backend/fetch_publications.php",
                method: "POST",
                data: { page: page },
                dataType: "json",
                success: function(response) {
                    const tbody = $("#unapprovedPubTableBody");
                    tbody.html("");

                    if (!response.publications || response.publications.length === 0) {
                        $("#message").html('<div class="alert alert-warning">No publications found.</div>');
                    } else {
                        $("#message").html('');

                        response.publications.forEach(pub => {
                            const row = `
                                <tr>
                                    <td>${pub.title ?? ''}</td>
                                    <td>${pub.doi ?? ''}</td>
                                    <td>${pub.type ?? ''}</td>
                                    <td>${pub.uploaderName }</td>
                                    <td>${pub.uploadedDate ?? ''}</td>
                                    <td>
                                        <button onclick="reviewPublication('${pub.uri}')" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }

                    updatePagination(response.totalPages, response.currentPage);

                    if (typeof callback === "function") {
                        callback();
                    }
                },
                error: function() {
                    $("#message").html('<div class="alert alert-danger">Data loading error.</div>');
                }
            });
        }

        function updatePagination(totalPages, currentPage) {
            let paginationHtml = `
                <button class="btn btn-secondary" onclick="changePage(${currentPage - 1})" ${currentPage == 1 ? "disabled" : ""}>
                    Back
                </button>
                <span class="mx-3">Page ${currentPage} / ${totalPages}</span>
                <button class="btn btn-secondary" onclick="changePage(${currentPage + 1})" ${currentPage == totalPages ? "disabled" : ""}>
                    Next
                </button>
            `;

            $("#pagination").html(paginationHtml);
        }

        function changePage(newPage) {
            if (newPage > 0) {
                currentPage = newPage;
                loadPendingPublications(currentPage);
            }
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

            console.log("Display message:", message);
            $("#message").html(`<div class="alert ${alertClass}">${message}</div>`).fadeIn();

            setTimeout(() => {
                $("#message").fadeOut();
            }, 5000);
        }

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get("message");

            loadPendingPublications(currentPage, () => {
                if (msg) {
                    showMessage("success", decodeURIComponent(msg));
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        });
    </script>
</body>
</html>