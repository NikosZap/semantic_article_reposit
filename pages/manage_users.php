<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH .'includes/sparql_queries.php';

if (empty($_SESSION['user']['is_logged_in']) || $_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
	<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
		<title>User Management</title>
	</head>
	<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<div class="d-flex justify-content-between align-items-center mb-2">
					<h2>User Management</h2>
					<button id="toggleFilters" class="btn btn-secondary btn-sm">Show Filters</button>
				</div>
				<div id="message"/>
				<div id="filterSection" style="display: none;">
					<form id="filterForm" class="mb-3">
						<input type="text" id="search" name="search" placeholder="Search User..." class="form-control mb-2">
							<select id="role" name="role" class="form-select mb-2">
								<option value="">All roles</option>
								<option value="researcher">Researcher</option>
								<option value="admin">Admin</option>
							</select>
							<select id="is_active" name="is_active" class="form-select mb-2">
								<option value="">All</option>
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
										<input type="date" id="to_date" name="to_date" class="form-control mb-2">
                    </div>
									</div>
									<button type="submit" class="btn btn-primary">Apply Filters</button>
								</form>
							</div>
							<div class="table-responsive">
								<table class="table table-striped table-bordered">
									<thead>
										<tr>
											<th>ID</th>
											<th>Username</th>
											<th>Firstname</th>
											<th>Lastname</th>
											<th>Email</th>
											<th>Role</th>
											<th>Registr. Date</th>
											<th>Last Update</th>
											<th>Activation</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody id="userTableBody"/>
								</table>
							</div>
							<div id="pagination" class="mt-3"/>
						</div>
					</main>
					<div class="container">
        <?php include BASE_PATH . 'includes/footer.php'?>
					</div>
					<script>
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
                data: { page, search, role, is_active, from_date, to_date  },
                dataType: "json",
                success: function(response) {
                    $("#userTableBody").html(""); // Καθαρισμός πίνακα
                    if (response.users.length === 0) {
                        $("#message").html('<div class="alert alert-warning">No users found.</div>');
                    } else {

                        $("#userTableContainer").html(response);


const savedMessage = sessionStorage.getItem("userActionMessage");
if (savedMessage) {
    const { type, text } = JSON.parse(savedMessage);
    showMessage(type, text);
    sessionStorage.removeItem("userActionMessage");
}
                        
                        response.users.forEach(user => {

                            const formattedCreatedAt = user.createdAt ? 
                                new Date(user.createdAt).toLocaleString("el-GR", {
                                day: '2-digit', month: '2-digit', year: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            }) : '';

                            let row = `<tr id="userRow_${user.userID}">
							<td>${user.userID}</td>
							<td>${user.username}</td>
							<td>${user.firstname}</td>
							<td>${user.lastname}</td>
							<td>${user.email}</td>
							<td>${user.role}</td>
							<td>${formattedCreatedAt}</td>
							<td>${user.updatedAt}</td>
							<td>${user.is_active == 1 ? "<span class='badge bg-success'>Yes</span>" : "<span class='badge bg-danger'>No</span>"}</td>
							<td>
								<form class="update-role-form" data-userid="${user.userID}">
									<input type="hidden" name="userID" value="${user.userID}">
										<select name="role" class="form-select role-select" data-userid="${user.userID}">
											<option value="Researcher" ${user.role==="Researcher" ?"selected" :"" }>Researcher< option>
											<option value="Admin" ${user.role==="Admin" ?"selected" :"" }>Admin< option>
										</select>
										<button type="submit" class="btn btn-primary btn-sm update-role-btn" title="Role update">
											<i class="bi bi-arrow-repeat"/>
										</button>
									</form>
									<button type="button" class="btn btn-${user.isSuspended == 1 ? 'success' : 'warning'} btn-sm toggle-suspend-user" data-userid="${user.userID}" data-suspended="${user.isSuspended}" title="${user.isSuspended == 1 ? 'Lift of suspension' : 'Suspend user'}">
										<i class="bi ${user.isSuspended == 1 ? 'bi-unlock' : 'bi-lock'}"/>
									</button>
									<button type="submit" class="btn btn-danger btn-sm delete-user" data-userid="${user.userID}" title=Delete>
									<i class="bi bi-trash"/>
								</button>
							</td>
						</tr>`;
                            $("#userTableBody").append(row);
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
            let paginationHtml = `<button class="btn btn-secondary" onclick="changePage(${currentPage - 1})" ${currentPage==1 ?"disabled" :"" }>Back< button>
						<span class="mx-3">Page ${currentPage} / ${totalPages}</span>
						<button class="btn btn-secondary" onclick="changePage(${currentPage + 1})" ${currentPage==totalPages ?"disabled" :"" }>Next< button>`; $("#pagination").html(paginationHtml); } function changePage(newPage) { if (newPage> 0) {
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
            e.preventDefault(); // Αποτρέπουμε το default submit

            let form = $(this);
            let userID = form.data("userid");
            let newRole = form.find(".role-select").val();

            console.log("Submitting a role change for a user:", userID, "->", newRole);

            $.ajax({
                url: "../backend/manage_users_backend.php",
                method: "POST",
                data: { action: "update_role", userID: userID, role: newRole },
                dataType: "json",
                success: function(response) {
                    console.log("Successful Response:", response);
                    if (response.status === "success") {
                        sessionStorage.setItem("userActionMessage", JSON.stringify({
                        type: "success",
                        text: response.message
                    }));
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

            if (!confirm("Are you sure you want to delete this user?")) {
                return;
            }

        $.ajax({
            url: "../backend/manage_users_backend.php",
            method: "POST",
            data: { action: "delete_user", userID: userID },
            dataType: "json",
            success: function(response) {
            if (response.status === "success") {
                showMessage("success", response.message);
                sessionStorage.setItem("userActionMessage", JSON.stringify({
                type: "success",
                text: response.message
            }));
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

    $(document).on("click", ".toggle-suspend-user", function () {
    let userID = $(this).data("userid");
    let isSuspended = $(this).data("suspended") == 1;
    let newStatus = isSuspended ? 0 : 1;

    let confirmText = isSuspended
        ? "Are you sure you want to lift the user's suspension? "
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
        success: function (response) {
            if (response.status === "success") {
                sessionStorage.setItem("userActionMessage", JSON.stringify({
                type: "success",
                text: response.message
            }));
            loadUsers(currentPage);
            } else {
                showMessage("error", response.message);
            }
        },
        error: function (xhr, status, error) {
            console.log("AJAX Error:", error);
            showMessage("error", "Server connection error!");
        }
    });
});

function showMessage(type, message) {
    const container = document.getElementById("message"); // <-- να ταιριάζει με το div! container.innerHTML='<div class="alert alert-' + (type==="success" ?"success" :"danger" ) +'">' + message +'</div>' ; setTimeout(()=> container.innerHTML = "", 4000);
}

$(document).ready(function () {
    const savedMessage = sessionStorage.getItem("userActionMessage");
    if (savedMessage) {
        const { type, text } = JSON.parse(savedMessage);
        showMessage(type, text);
        sessionStorage.removeItem("userActionMessage");
    }
    loadUsers(currentPage);  
});

$("#toggleFilters").on("click", function () {
    $("#filterSection").slideToggle(200);

    const current = $(this).text();
    $(this).text(current === "Show Filters" ? "Hide Filters" : "Show Filters");
});

    </script>
						</body>
					</html>