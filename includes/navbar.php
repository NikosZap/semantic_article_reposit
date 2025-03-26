
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL;?>index.php">MyApp</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL;?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL;?>about.php">About</a></li>
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
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/new_manual_publication.php">New Publication Manually</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/new_auto_publication.php">New Publication Automatically</a></li>                   
                    </ul>
                    <?php endif; ?>               
                </li>
            </ul>

            <ul class="navbar-nav">
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
                <p class="mt-3 text-center">Don't hava an account? <a href="<?php echo BASE_URL ;?>pages/register.php">Register</a></br>
            Forgot Password? <a href="<?php echo BASE_URL ;?>pages/forgot_password.php">Reset Password</a></p>
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