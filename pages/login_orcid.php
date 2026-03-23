<?php
require_once dirname(__DIR__) . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Login or Register with ORCID</title>
</head>
<body>

<?php include(BASE_PATH . 'includes/navbar.php'); ?>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center align-items-center">

            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="row g-0">
                        <div class="col-lg-7 p-4 p-md-5">

                            <span class="badge bg-success-subtle text-success mb-3">
                                ORCID Access
                            </span>

                            <h2 class="display-6">
                                Login or Register with ORCID
                            </h2>

                            <p class="text-muted mb-4">
                                Use your ORCID account to access the platform and connect your researcher identity.
                            </p>

                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Link your researcher profile with your ORCID iD
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Secure login through trusted academic identity
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Complete your profile on first login
                                </li>
                            </ul>

                            <div id="message" class="mb-3"></div>

                            <form action="../backend/login_orcid_backend.php" method="post">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Continue with ORCID
                                </button>
                            </form>

                        </div>
                        <div class="col-lg-5 bg-light p-4 p-md-5 d-flex align-items-center">
                            <div>
                                <h5 class="fw-bold mb-3">Why ORCID?</h5>
                                <p class="text-muted mb-0">
                                    ORCID provides a unique identifier for researchers, ensuring accurate attribution of publications and enabling seamless integration across academic platforms.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<div class="container">
    <?php include BASE_PATH . 'includes/footer.php' ?>
</div>

</body>
</html>