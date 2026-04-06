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