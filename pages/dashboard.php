<?php
require dirname(__DIR__) . '/config.php';


if (!isset($_SESSION['user'])) {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <?php include( BASE_PATH . 'includes/header.php')?>
    <title>Dashboard</title>
</head>
<body>

<?php include(BASE_PATH . 'includes/navbar.php')?>
    <div class="container mt-5">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h2>
        <a href= "<?php echo BASE_URL; ?>backend/logout.php" class="btn btn-danger">Log out</a>
    </div>
    <div class="container">
        <?php include( BASE_PATH . 'includes/footer.php')?>
    </div>
</body>
</html>
