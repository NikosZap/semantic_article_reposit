<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Publication Claims</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <h2 class="display-6">Publication Claims</h2>

            <div id="message"></div>

            <div id="flashMessage">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['status']) ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>DOI</th>
                        <th>Date Uploaded</th>
                        <th>Claimed Author</th>
                        <th>Claimed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="claimPubTableBody"></tbody>
            </table>

            
        </div>
    </main>

    <div class="container">
        <div id="pagination" class="mt-3"></div>
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>
        let currentPage = 1;

        function loadPendingClaims(page = 1, callback = null) {
            $.ajax({
                url: "../backend/fetch_claims.php",
                method: "POST",
                data: { page: page },
                dataType: "json",
                success: function(response) {
                    const tbody = $("#claimPubTableBody");
                    tbody.html("");

                    if (!response.claims || response.claims.length === 0) {
                        $("#message").html('<div class="alert alert-warning">No claims found.</div>');
                    } else {
                        $("#message").html('');

                        response.claims.forEach(claim => {
                            const row = `
                                <tr>
                                    <td>${claim.pubTitle ?? ''}</td>
                                    <td>${claim.doi ?? ''}</td>
                                    <td>${claim.claimDate ?? ''}</td>
                                    <td>${claim.authorFirstname ?? ''} ${claim.authorLastname ?? ''}</td>
                                    <td>${claim.userFirstName ?? ''} ${claim.userLastName ?? ''}</td>
                                    <td>
                                        <button onclick="reviewClaim('${claim.uri}')" class="btn btn-primary btn-sm">
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

            if (!totalPages || totalPages <= 1) {
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
                loadPendingClaims(currentPage);
            }
        }

        function reviewClaim(uri) {
            window.location.href = `review_claim.php?uri=${encodeURIComponent(uri)}`;
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

        $(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get("message");

            loadPendingClaims(currentPage, () => {
                if (msg) {
                    showMessage("success", decodeURIComponent(msg));
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        });

        setTimeout(() => {
            const el = document.querySelector('#flashMessage .alert');
            if (el) {
                el.remove();
            }
        }, 4000);
    </script>
</body>
</html>