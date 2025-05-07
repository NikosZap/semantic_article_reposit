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
    <?php include (BASE_PATH . 'includes/header.php'); ?>
    <title>Approve Publication</title>
</head>
<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
<div class="container mt-5">
    <h2>Unapproved publications</h2>
        <div id="message"></div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>DOI</th>
                    <th>Date Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="unapprovedPubTableBody"></tbody>
        </table>
        <div id="pagination" class="mt-3"></div>
</div>


<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
    </div>
</body>

<script>
    let currentPage = 1;
        
    function loadPendingPublications(page = 1, callback = null) {
    $.ajax({
        url: "../backend/fetch_publications.php",
        method: "POST",
        data: { page },
        dataType: "json",
        success: function(response) {
            const tbody = $("#unapprovedPubTableBody");
            tbody.html(""); // Καθαρισμός πίνακα

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
                            <td>
                                <button class="btn btn-success btn-sm" onclick="approve('${pub.uri}')"><i class="bi bi-check-circle"></i></button>
                                <button class="btn btn-danger btn-sm" onclick="reject('${pub.uri}')"><i class="bi bi-trash"></i></button>
                                <button onclick="reviewPublication('${pub.uri}')" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i></button>
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
            let paginationHtml = `<button class="btn btn-secondary" onclick="changePage(${currentPage - 1})" ${currentPage == 1 ? "disabled" : ""}>Back</button>
                                  <span class="mx-3">Page ${currentPage} / ${totalPages}</span>
                                  <button class="btn btn-secondary" onclick="changePage(${currentPage + 1})" ${currentPage == totalPages ? "disabled" : ""}>Next</button>`;
            $("#pagination").html(paginationHtml);
        }

        function changePage(newPage) {
            if (newPage > 0) {
                currentPage = newPage;
                loadPendingPublications(currentPage);
            }
        }

        $(document).on("click", ".delete-user", function() {
            let userID = $(this).data("userid");

            if (!confirm("Are you sure you want to delete this user?")) {
                return;
            }
        });

        function approve(uri) {
            $.post("../backend/approve_publications_backend.php", { action: "approve", uri }, function (res) {
            if (res.status === "success") {
                $("#message").html('<div class="alert alert-success">' + res.message + '</div>');
                loadPendingPublications(currentPage); // Refresh
            } else {
               $("#message").html('<div class="alert alert-danger">Error approving publication.</div>');
            }
        }, "json");
            }   

        function reject(uri) {
            if (!confirm("Are you sure you want to reject this publication?")) return;

            $.post("../backend/approve_publications_backend.php", { action: "reject", uri }, function (res) {
            if (res.status === "success") {
                $("#message").html('<div class="alert alert-success">' + res.message + '</div>');
                loadPendingPublications(currentPage); // Refresh
            } else {
                $("#message").html('<div class="alert alert-danger">Error rejecting publication.</div>');
            }
            }, "json");
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

        setTimeout(() => { $("#message").fadeOut(); }, 5000);
    }

    $(document).ready(function () {
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

</html>
