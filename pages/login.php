<?php require dirname(__DIR__) . '/config.php'; ?>
<!DOCTYPE html>
<html lang="el">
<head>
    <?php include(BASE_PATH . 'includes/header.php')?>     
    <title>Σύνδεση</title>
    
</head>
<body>
<?php include(BASE_PATH . 'includes/navbar.php')?>
    <div class="container mt-5">
    <div class="row">
            <div class="col-md-6 mx-auto"> 
                <div class="card p-4 ">
        <h2 class="text-center">User Log In</h2>
        
        <?php
        // Προβολή μηνύματος λάθους αν υπάρχει
        //if (isset($_GET['error'])) {
        //   echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['error']) . "</div>";
        if (isset($_GET['success'])) {
           echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['success']) . "</div>";
        }
        ?>

        <?php if (isset($_SESSION['unauthorized'])){ ?>
            <div id="unauth_message" class="alert alert-warning">
                You must be logged in to access this page!
            </div>
        <?php unset($_SESSION['unauthorized']); }?>

        <div id="message"></div>

        <form id="loginForm2" >
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Log in</button>
        </form>

        <p class="mt-3">Don't have an account; <a href="<?php echo BASE_URL;?>pages/register.php">Sign Up</a><br>
        Forgot password? <a href="forgot_password.php">Reset password</a></p>
    </div>
    </div>
    </div>
    </div>
    <div class="container">
        <?php include BASE_PATH . 'includes/footer.php'?>
    </div>
</body>
<script>


      document.getElementById("loginForm2").addEventListener("submit", function(event) {
      event.preventDefault(); // Αποτρέπει το default submit

      let formData = new FormData(this);

        fetch('../backend/login_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("loginForm2"))  })
        .then(response => response.json())
        .then(data => {
        if (data.status === "success") {
           window.location.href = data.redirect;
        } else {
           document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }   
    })
.catch(error => console.error("Error:", error));
});

    </script>
</html>