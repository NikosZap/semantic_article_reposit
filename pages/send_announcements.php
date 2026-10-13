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
    <main>

        <div class="container mt-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>    
                    <h2 class="display-6">Send Announcements to Users</h2>
                    <small class="text-muted">Select users based on filters and send targeted announcements efficiently</small>
                </div>
                <button id="toggleFilters" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-funnel"></i> Show Filters
                </button>
            </div>
            <div id="message"></div>
                <div id="filterSection" class="card shadow-sm border-0 mb-4" style="display: none;">
                    <div class="card-body">    
                        <form id="filterForm" class="mb-3">

                            <label for="search" class="form-label">Search</label>
                            <input type="text" id="search" name="search" placeholder="Search user..." class="form-control mb-2" >
                            
                            <label for="role" class="form-label">Roles</label>
                            <select id="role" name="role" class="form-select mb-2">
                                <option value="">All</option>
                                <option value="researcher">Researcher</option>
                                <option value="admin">Admin</option>
                            </select>

                            <label for="is_active" class="form-label">Activation Status</label>
                            <select id="is_active" name="is_active" class="form-select mb-2">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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

                            <button type="submit" class="btn btn-primary">
                                Apply Filtering
                            </button>

                        </form>
                    </div>    
                </div>
                
                <textarea id="announcementMessage" class="form-control mb-2" placeholder="Announcement Content"></textarea>
                <button id="sendEmails" class="btn btn-success">
                    Send Email
                </button>

                <div class="table-responsive">

                    <div id="usersTable" class="mt-3"></div>
                </div>



            </div>

        </main>

        <div class="container">
            <div id="pagination" class="mt-3"></div>
            <?php include BASE_PATH . 'includes/footer.php'; ?>
        </div>

<script src="<?= BASE_URL ?>scripts/send_announcements.js"> </script>

</body>

</html>