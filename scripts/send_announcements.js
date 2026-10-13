let currentPage = 1;

    function loadUsers(page = 1) {

        let search = $("#search").val();
        let role = $("#role").val();
        let is_active = $("#is_active").val();
        let from_date = $("#from_date").val();
        let to_date = $("#to_date").val();

        $.ajax({

        url: "../backend/fetch_users.php",
        method: "POST",

        data: {
            page,
            search,
            role,
            is_active,
            from_date,
            to_date
        },

        dataType: "json",

        success: function(response) {

            $("#usersTable").html("");

            if (response.users.length === 0) {

                $("#usersTable").html( '<div class="alert alert-warning">No users found.</div>' );

            } else {

                let table = `
                    <table class="table table-hover table-bordered">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Reg. Date</th>
                            <th>Activated</th>
                        </tr>
                    </thead>

                    <tbody>
                `;

                response.users.forEach(user => {

                    const formattedCreatedAt = user.createdAt
                        ? new Date(user.createdAt).toLocaleString("en-US", {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        })
                    : '';

                    table += `
                        <tr>
                            <td>${user.userID}</td>
                            <td>${user.username}</td>
                            <td>${user.firstname} ${user.lastname}</td>
                            <td>${user.email}</td>
                            <td>${user.role}</td>
                            <td>${formattedCreatedAt}</td>
                            <td> ${user.is_active == 1 ? "<span class='badge bg-success'>Yes</span>" : "<span class='badge bg-danger'>No</span>" }</td>
                        </tr>
                    `;

                });

                table += `
                        </tbody>    
                    </table>
                `;

                $("#usersTable").html(table);

            }

            updatePagination(response.totalPages,response.currentPage);

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

            loadUsers(currentPage);

        }

    }

    $("#filterForm").on("submit", function(e) {

        e.preventDefault();

        currentPage = 1;

        loadUsers();

    });

    $("#sendEmails").click(function() {

        let message = $("#announcementMessage").val();

        let search = $("#search").val();
        let role = $("#role").val();
        let is_active = $("#is_active").val();
        let from_date = $("#from_date").val();
        let to_date = $("#to_date").val();

        if (message.trim() === "") {

            alert("Message cannot be empty!");

            return;

        }

        $.ajax({

            url: "../backend/send_announcement_backend.php",

            method: "POST",

            data: {
                message,
                search,
                role,
                is_active,
                from_date,
                to_date
            },

            dataType: "json",

            success: function(response) {

                if (response.status === "success") {

                    showMessage("success", response.message);

                } else {

                    showMessage("error", response.message);

                }

            },

            error: function() {

                showMessage("error", "Error connecting to the server!");

            }

        });

    });

    function showMessage(type, message) {

        let alertClass =
        type === "success"
        ? "alert-success"
        : "alert-danger";

        if ($("#message").length === 0) {

            console.log("The #message component does not exist");

            return;

        }

        $("#message")
        .html(`<div class="alert ${alertClass}">
        ${message}
        </div>`)
        .fadeIn();

        setTimeout(() => { $("#message").fadeOut();  }, 10000);

    }

    $(document).ready(function() {

        loadUsers();

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