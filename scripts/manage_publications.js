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
    