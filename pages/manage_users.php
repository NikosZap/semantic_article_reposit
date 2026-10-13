<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

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
                <div>    
                    <h2 class="display-6">User Management</h2>
                    <small class="text-muted">Manage system users, roles and access</small>
                </div>
                    <button id="toggleFilters" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-funnel"></i> Show Filters
                    </button>
            </div>

            <div id="message"></div>

            <div id="filterSection" class="card shadow-sm border-0 mb-4" style="display: none;">
                <div class="card-body">    
                    <form id="filterForm" class="mb-3">
                            <input
                                type="text"
                                id="search"
                                name="search"
                                placeholder="Search User..."
                                class="form-control mb-2" >

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
                                    <label for="from_date">From:</label>
                                    <input type="date" id="from_date" name="from_date" class="form-control mb-2">
                                </div>

                                <div class="col-md-6">
                                    <label for="to_date">To:</label>
                                    <input type="date" id="to_date" name="to_date" class="form-control mb-2">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </form>     
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registr. Date</th>
                            <th>Activation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody"></tbody>
                </table>
            </div>

            <div id="pagination" class="mt-3"></div>
        </div>
    </main>

    <div class="container">
        <?php include BASE_PATH . 'includes/footer.php'; ?>
    </div>

    <script src="<?= BASE_URL ?>scripts/manage_users.js"></script>
        
</body>
</html>