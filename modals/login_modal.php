<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow rounded-4">

            <!-- Header -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Sign in</h5>
                    <small class="text-muted">Access your account</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body pt-3">

                <!-- Messages -->
                <div id="loginModalMessage" class="mb-3"></div>

                <!-- ORCID LOGIN -->
                <div class="text-center mb-3">
                    <a href="<?= BASE_URL ?>pages/login_orcid.php"
                       class="btn btn-outline-dark w-100 mb-2">
                        <i class="bi bi-person-badge"></i> Continue with ORCID
                    </a>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted small">or</span>
                        <hr class="flex-grow-1">
                    </div>
                </div>

                <form id="loginForm">

                    <div class="form-floating mb-3">
                        <input type="text"
                               class="form-control"
                               id="loginUsername"
                               name="username"
                               placeholder="Username"
                               required>
                        <label for="loginUsername">Username</label>
                    </div>


                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password"
                                   class="form-control"
                                   id="loginPassword"
                                   name="password"
                                   placeholder="Password"
                                   required>
                            <label for="loginPassword">Password</label>
                        </div>
                        <button class="btn btn-outline-secondary"
                                type="button"
                                onclick="togglePassword()">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <!-- Submit -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary" id="loginSubmitBtn">
                            Login
                        </button>
                    </div>

                </form>

                <!-- Extra links -->
                <div class="text-center small">
                    <p class="mb-2">
                        Don't have an account?
                        <a href="<?= BASE_URL ?>pages/register.php" class="fw-semibold text-decoration-none">
                            Register
                        </a>
                    </p>
                    <p class="mb-0">
                        Forgot password?
                        <a href="<?= BASE_URL ?>pages/forgot_password.php" class="fw-semibold text-decoration-none">
                            Reset
                        </a>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<script>

// Show / hide password
function togglePassword() {
    const input = document.getElementById("loginPassword");
    input.type = input.type === "password" ? "text" : "password";
}

// Autofocus όταν ανοίγει modal
const loginModal = document.getElementById('loginModal');
loginModal.addEventListener('shown.bs.modal', () => {
    document.getElementById('loginUsername').focus();
});

// Submit
document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const messageBox = document.getElementById("loginModalMessage");
    const submitBtn = document.getElementById("loginSubmitBtn");

    messageBox.innerHTML = "";

    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Logging in...
    `;

    fetch("<?= BASE_URL ?>backend/login_backend.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === "success") {
            window.location.href = data.redirect;
            return;
        }

        messageBox.innerHTML = `
            <div class="alert alert-danger mb-0">
                ${data.message}
            </div>
        `;
    })
    .catch(() => {
        messageBox.innerHTML = `
            <div class="alert alert-danger mb-0">
                Unexpected error. Try again.
            </div>
        `;
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Login';
    });
});

</script>