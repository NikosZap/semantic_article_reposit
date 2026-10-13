let currentPage = 1;

        function loadUsers(page = 1, callback = null) {
            let search = $("#search").val();
            let role = $("#role").val();
            let is_active = $("#is_active").val();
            let from_date = $("#from_date").val();
            let to_date = $("#to_date").val();

            $.ajax({
                url: "../backend/fetch_users.php",
                method: "POST",
                data: { page, search, role, is_active, from_date, to_date },
                dataType: "json",
                success: function(response) {
                    $("#userTableBody").html("");

                    if (response.users.length === 0) {
                        $("#message").html('<div class="alert alert-warning">No users found.</div>');
                    } else {
                        const savedMessage = sessionStorage.getItem("userActionMessage");
                        if (savedMessage) {
                            const { type, text } = JSON.parse(savedMessage);
                            showMessage(type, text);
                            sessionStorage.removeItem("userActionMessage");
                        }

                        response.users.forEach(user => {
                            const formattedCreatedAt = user.createdAt
                                ? new Date(user.createdAt).toLocaleString("en-US", {
                                      day: "2-digit",
                                      month: "2-digit",
                                      year: "numeric",
                                      hour: "2-digit",
                                      minute: "2-digit",
                                      hour12: false
                                  })
                                : "";

                            let row = `
                                <tr id="userRow_${user.userID}">
                                    <td>${user.userID}</td>
                                    <td>${user.username}</td>
                                    <td>${user.firstname}</td>
                                    <td>${user.lastname}</td>
                                    <td>${user.email}</td>
                                    <td>${user.role === "Admin"? "<span class='badge bg-dark'>Admin</span>" : "<span class='badge bg-primary'>Researcher</span>" }</td>
                                    <td>${formattedCreatedAt}</td>
                                    <td>${user.is_active == 1 ? "<span class='badge bg-success'>Yes</span>": "<span class='badge bg-danger'>No</span>" }</td>
                                    <td>
                                        <div class="d-flex flex-column gap-2" style="min-width: 190px;">
                                            
                                            <form class="update-role-form" data-userid="${user.userID}">
                                                <input type="hidden" name="userID" value="${user.userID}">
                                                
                                                <label class="form-label small text-muted mb-1">Role</label>
                                                <div class="input-group input-group-sm">
                                                    <select name="role" class="form-select role-select" data-userid="${user.userID}">
                                                        <option value="Researcher" ${user.role === "Researcher" ? "selected" : ""}>Researcher</option>
                                                        <option value="Admin" ${user.role === "Admin" ? "selected" : ""}>Admin</option>
                                                    </select>
                                                     <button type="submit" class="btn btn-primary update-role-btn" title="Update role">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="d-grid gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-${user.isSuspended == 1 ? "success" : "warning"} btn-sm toggle-suspend-user"
                                                    data-userid="${user.userID}"
                                                    data-suspended="${user.isSuspended}"
                                                    title="${user.isSuspended == 1 ? "Lift suspension" : "Suspend user"}">

                                                    <i class="bi ${user.isSuspended == 1 ? "bi-unlock" : "bi-lock"} me-1"></i>
                                                    ${user.isSuspended == 1 ? "Unsuspend" : "Suspend"}
                                                </button>

                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm delete-user"
                                                    data-userid="${user.userID}"
                                                    title="Delete user">
                                                    <i class="bi bi-trash me-1"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;

                            $("#userTableBody").append(row);
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

        $(document).on("submit", ".update-role-form", function(e) {
            e.preventDefault();

            let form = $(this);
            let userID = form.data("userid");
            let newRole = form.find(".role-select").val();

            console.log("Submitting a role change for a user:", userID, "->", newRole);

            $.ajax({
                url: "../backend/manage_users_backend.php",
                method: "POST",
                data: {
                    action: "update_role",
                    userID: userID,
                    role: newRole
                },
                dataType: "json",
                success: function(response) {
                    console.log("Successful Response:", response);

                    if (response.status === "success") {
                        sessionStorage.setItem(
                            "userActionMessage",
                            JSON.stringify({
                                type: "success",
                                text: response.message
                            })
                        );

                        loadUsers(currentPage);
                    } else {
                        showMessage("error", response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", error);
                    showMessage("error", "Server connection error!");
                }
            });
        });

        $(document).on("click", ".delete-user", function() {
            let userID = $(this).data("userid");

            if (!confirm("This action will permanently delete the user. Continue?")) {
                return;
            }

            $.ajax({
                url: "../backend/manage_users_backend.php",
                method: "POST",
                data: {
                    action: "delete_user",
                    userID: userID
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        showMessage("success", response.message);

                        sessionStorage.setItem(
                            "userActionMessage",
                            JSON.stringify({
                                type: "success",
                                text: response.message
                            })
                        );

                        loadUsers(currentPage);
                    } else {
                        showMessage("error", response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", error);
                    showMessage("error", "Server connection error!");
                }
            });
        });

        $(document).on("click", ".toggle-suspend-user", function() {
            let userID = $(this).data("userid");
            let isSuspended = $(this).data("suspended") == 1;
            let newStatus = isSuspended ? 0 : 1;

            let confirmText = isSuspended
                ? "Are you sure you want to lift the user's suspension?"
                : "Are you sure you want to suspend the user?";

            if (!confirm(confirmText)) {
                return;
            }

            $.ajax({
                url: "../backend/manage_users_backend.php",
                method: "POST",
                data: {
                    action: "toggle_suspend_user",
                    userID: userID,
                    suspend: newStatus
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        sessionStorage.setItem(
                            "userActionMessage",
                            JSON.stringify({
                                type: "success",
                                text: response.message
                            })
                        );

                        loadUsers(currentPage);
                    } else {
                        showMessage("error", response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", error);
                    showMessage("error", "Server connection error!");
                }
            });
        });

        function showMessage(type, message) {
            const container = document.getElementById("message");
            container.innerHTML =
                '<div class="alert alert-' +
                (type === "success" ? "success" : "danger") +
                '">' +
                message +
                '</div>';

            setTimeout(() => {
                container.innerHTML = "";
            }, 4000);
        }

        $(document).ready(function() {
            const savedMessage = sessionStorage.getItem("userActionMessage");

            if (savedMessage) {
                const { type, text } = JSON.parse(savedMessage);
                showMessage(type, text);
                sessionStorage.removeItem("userActionMessage");
            }

            loadUsers(currentPage);
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