<?php
require_once dirname(__DIR__) . '/config.php';

if (!empty($_SESSION['user']['is_logged_in'])) {
    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>Password recovery</title>
</head>

<body>

<?php include(BASE_PATH . 'includes/navbar.php'); ?>

<main>
    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-8">

                <h2>Password recovery</h2>

                <p class="lead">
                    Enter your email address to receive a password reset link.
                </p>

                <div id="message"></div>

                <form id="forgotPassword"
                      action="../backend/forgot_password_backend.php"
                      method="post">

                    <div class="mb-3">

                        <label for="email" class="form-label">Email</label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            required
                        >

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Send Reset URL
                    </button>

                </form>

            </div>

        </div>

    </div>
</main>

<div class="container">
    <?php include BASE_PATH . 'includes/footer.php'; ?>
</div>

<script  src="<?= BASE_URL ?>scripts/forgot_password.js"></script>
        

</body>
</html>