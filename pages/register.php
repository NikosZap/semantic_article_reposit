<?php require dirname(__DIR__) . '/config.php'; ?>
<!DOCTYPE html>
<html lang="el">
<head>
    <?php include(BASE_PATH . 'includes/header.php')?> 
    <title>Register</title>
   </head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php')?>
    <div class="container mt-5">
    <div class="row">
            <div class="col-md-6 mx-auto"> 
                <div class="card p-4 ">
        <h2 class="text-center">Sign up</h2>
        <p class="text-center lead">Request access to the system</p>
        <?php
        // Εμφάνιση μηνύματος αν υπάρχει σφάλμα ή επιτυχία
        if (isset($_GET['error'])) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['error']) . "</div>";
        }
        if (isset($_GET['success'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['success']) . "</div>";
        }
        ?>
         <div id="message"></div>

        <form id="registerForm" method="POST" action="../backend/register_backend.php">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Reset Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Firstame</label>
                <input type="text" name="firstname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Lastname</label>
                <input type="text" name="lastname" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Εγγραφή</button>
        </form>

        <p class="mt-3 text-center">Already have an account?<a href="login.php">Log In</a></p>
    </div>
    </div>
    </div>
    </div>
    <div class="container">
        <?php include( BASE_PATH . 'includes/footer.php')?>
    </div>
</body>

<script>
      document.getElementById("registerForm").addEventListener("submit", function(event) {
      event.preventDefault(); // Αποτρέπει το default submit

      let formData = new FormData(this);

        fetch('../backend/register_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("registerForm"))  })
        .then(response => response.json())
        .then(data => {
        if (data.status === "success") {
           //window.location.href = "register.php?success=" + encodeURIComponent(data.message);
           document.getElementById("message").innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        } else {
           document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }   
    })
.catch(error => console.error("Error:", error));
});

    </script>

</html>