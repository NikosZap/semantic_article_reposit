<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Send Announcements</title>
</head>

<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>
    <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
            <h2>Sending announcements to users</h2>
            <button id="toggleFilters" class="btn btn-secondary btn-sm">Show Filters</button>
        </div>

         <div id="message"></div>

        <div id="filterSection" style="display: none;">
            <form id="filterForm" class="mb-3">
                <input type="text" id="search" name="search" placeholder="Search user..." class="form-control mb-2">
                <select id="role" name="role" class="form-select mb-2">
                    <option value="">All Roles</option>
                    <option value="researcher">Researcher</option>
                    <option value="admin">Admin</option>
                </select>
                <select id="is_active" name="is_active" class="form-select mb-2">
                    <option value="">All </option>
                    <option value="1">Active</option>
                    <option value="0">Not active</option>
                </select>
                <div class="row">
                    <div class="col-md-6">
                        <label>From:</label>
                        <input type="date" id="from_date" name="from_date" class="form-control mb-2">
                    </div>
                    <div class="col-md-6">
                        <label>To:</label>
                        <input type="date" id="to_date" name="to_date" class="form-control mb-2" >
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filtering</button>
            </form>
        </div>

        <textarea id="announcementMessage" class="form-control mb-2" placeholder="Announcement Content"></textarea>

        <button id="sendEmails" class="btn btn-success">Send Email</button>

        <div class="table-responsive">
            <div id="usersTable" class="mt-3"></div>
        </div>    
        <div id="pagination" class="mt-3"></div>
    </div>

    <div class="container">
        <?php include BASE_PATH . 'includes/footer.php'?>
    </div>
</body>

    <script>
        let currentPage = 1;

        function loadUsers(page = 1) {
            let search = $("#search").val();
            let role = $("#role").val();
            let is_active = $("#is_active").val();
            let from_date = $("#from_date").val();
            let to_date = $("#to_date").val();

            //console.log("Data sent to send_announcement_backend.php:");
            //console.log({ search, role, is_active, from_date, to_date });

            $.ajax({
                url: "../backend/fetch_users.php",
                method: "POST",
                data: { page, search, role, is_active, from_date, to_date },
                dataType: "json",
                success: function(response) {
                    $("#usersTable").html(""); // Καθαρισμός πίνακα
                    if (response.users.length === 0) {
                        $("#usersTable").html('<div class="alert alert-warning">No users found.</div>');
                    } else {
                        let table = `<table class="table table-striped table-bordered">
                            <thead><tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Reg. Date</th>
                                <th>Activated</th>
                            </tr></thead><tbody>`;
                        response.users.forEach(user => {

                            const formattedCreatedAt = user.createdAt ? 
                                new Date(user.createdAt).toLocaleString("el-GR", {
                                day: '2-digit', month: '2-digit', year: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            }) : '';

                            table += `<tr>
                                <td>${user.userID}</td>
                                <td>${user.firstname} ${user.lastname}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td>${formattedCreatedAt}</td>
                                <td>${user.is_active == 1 ? "<span class='badge bg-success'>Yes</span>" : "<span class='badge bg-danger'>No</span>"}</td>
                            </tr>`;
                        });
                        table += "</tbody></table>";
                        $("#usersTable").html(table);
                    }
                    updatePagination(response.totalPages, response.currentPage);
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
                data: { message: message, search, role, is_active, from_date, to_date },
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

        // Νέα συνάρτηση για εμφάνιση των μηνυμάτων
function showMessage(type, message) {
    let alertClass = type === "success" ? "alert-success" : "alert-danger";

    if ($("#message").length === 0) {
        console.log("The #message component does not exist");
        return;
    }
    
    $("#message").html(`<div class="alert ${alertClass}">${message}</div>`).fadeIn();

    setTimeout(() => { $("#message").fadeOut(); }, 10000);
}

        $(document).ready(function() {
            loadUsers();
        });

    $("#toggleFilters").on("click", function () {
        $("#filterSection").slideToggle(200);

        // Εναλλαγή κειμένου κουμπιού
        const current = $(this).text();
        $(this).text(current === "Show Filters" ? "Hide Filters" : "Show Filters");
    });
    </script>
</html>