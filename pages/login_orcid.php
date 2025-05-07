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
<div class="container mt-5">
    <div class="col-md-6 mx-auto"> 
        <div class="card p-4 ">

            <h3>Access via ORCID</h3>
            <div id="message"></div>

            <form action="../backend/login_orcid_backend.php" method="post">
                <button type="submit" class="btn btn-primary">Log In with ORCID</button>
            </form>

        </div>
    </div>
</div>
</div>
</div>
<div class="container">
        <?php include BASE_PATH . 'includes/footer.php'?>
</div>
</body>
</html>
