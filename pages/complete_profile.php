<?php
require_once dirname(__DIR__) . '/config.php';

// check if session has ORCID iD
if (!isset($_SESSION['orcid_id'])) {
    header("Location: login_orcid.php");
    exit;
}

$orcid_id   = $_SESSION['orcid_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name  = $_SESSION['last_name'] ?? '';
$affiliation = $_SESSION['affiliation'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>

    <title>Complete Your Account</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.0.1/js/countrySelect.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.0.1/css/countrySelect.min.css">
</head>
<body>
    <style>
        .country-select {
            width: 100% !important;
        }

        .country-select input {
            width: 100% !important;
            display: block !important;
        }

        .country-select .flag-dropdown {
            left: 0;
        }
    </style>

    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <main>
        <div class="container mt-5">
            <div class="col-md-8 mx-auto">
                <div class="card p-4 shadow">
                    <h3 class="mb-4">Complete your Account</h3>

                    <div id="message"></div>

                    <form id="completeProfileForm" method="post">
                        <div class="mb-3">
                            <label class="form-label">ORCID iD</label>
                            <input
                                type="text"
                                name="orcid_id"
                                class="form-control"
                                value="<?= htmlspecialchars($orcid_id) ?>"
                                disabled
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars($first_name) ?>"
                                disabled
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars($last_name) ?>"
                                disabled
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                value="<?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?>"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="nationality" class="form-label">Nationality</label>
                            <input
                                type="text"
                                name="nationality"
                                id="nationality"
                                class="form-control"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Affiliation</label>
                            <input
                                type="text"
                                name="affiliation"
                                class="form-control"
                                value="<?= htmlspecialchars($affiliation) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Scientific Area</label>
                            <input
                                type="text"
                                name="scientific_area"
                                class="form-control"
                            >
                        </div>

                        <button type="submit" class="btn btn-success">
                            Complete Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div class="container">
        <?php include BASE_PATH . 'includes/footer.php'; ?>
    </div>

    <script>
        $("#nationality").countrySelect();

        document.getElementById("completeProfileForm").addEventListener("submit", function (e) {
            e.preventDefault();

            const form = e.target;
            const data = new URLSearchParams(new FormData(form));

            fetch("../backend/complete_profile_backend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: data
            })
            .then(res => res.json())
            .then(data => {
                const msg = document.getElementById("message");

                if (data.status === "success" && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(() => {
                document.getElementById("message").innerHTML =
                    `<div class="alert alert-danger">Request failed. Try again.</div>`;
            });
        });
    </script>
</body>
</html>