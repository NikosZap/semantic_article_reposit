<?php require_once BASE_PATH .'includes/pub_sparql_queries.php';?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL;?>index.php">SARS App</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL;?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-nowrap" href="<?php echo BASE_URL;?>semantic_search.php">Semantic Search</a></li>
                <?php if (isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
                   <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Users
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/manage_users.php">User Management</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/send_announcements.php">Send Announcements</a></li>                   
                    </ul>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']['is_logged_in']) && in_array($_SESSION['user']['role'], ['Admin', 'Researcher'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Publications
                    </a>
                    
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/insert_manual_publication.php">Insert New Publication Manually</a></li>
                            <?php if( $_SESSION['user']['role'] === 'Admin'){ ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/manage_publications.php">Manage Publications</a></li>                   
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/approve_publications.php">Approve Publications<?php  if(countPendingPublications() > 0): ?>
                                <span class="badge bg-danger ms-2"><?= countPendingPublications() ?></span>
                                <?php endif; ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/approve_claims.php">Approve Claims<?php  if(countPendingClaims() > 0): ?>
                                <span class="badge bg-danger ms-2"><?= countPendingClaims() ?></span>
                                <?php endif; ?></a></li>    
                            <?php } ?>
                            <?php if( $_SESSION['user']['role'] === 'Researcher'){ ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/claim_publication.php">Claim Publication</a></li>  
                            <?php } ?>
                        </ul>
                    <?php endif; ?>               
                </li>
                <?php if( isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin'){ ?>               
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL;?>pages/statistics.php">Statistics</a></li>
                <?php } ?>
            </ul>

            <ul class="navbar-nav mb-2 mb-md-0">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['user']['username'] ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>backend/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"  id="authDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Login in / Register
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="authDropdown">
                            <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/register.php">Register</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- 🔹 MODAL LOGIN FORM -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">User Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <div id="loginModalMessage" class="mb-2"></div>
                <form form id="loginForm">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <p class="mt-3 text-center">Don't have an account? <a href="<?php echo BASE_URL ;?>pages/register.php">Register</a></br>
            Forgot Password? <a href="<?php echo BASE_URL ;?>pages/forgot_password.php">Reset Password</a></br>
            Connect with <a href="<?php echo BASE_URL ;?>pages/login_orcid.php">ORCID</a>   
            </a></p>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // αποτρέπει το default submit

    const formData = new FormData(this);

    fetch("<?php echo BASE_URL; ?>backend/login_backend.php", {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            window.location.href = data.redirect;
        }
        else {
           document.getElementById("loginModalMessage").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }   
    })
    .catch(error => {
        console.error("Login error:", error);
        alert("Unexpected login error!");
    });
});
</script>