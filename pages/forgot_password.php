<?php 
require_once dirname(__DIR__) . '/config.php'; 
?>

<head>
<?php include(BASE_PATH . 'includes/header.php')?>
    <title>Semantic demo</title>
</head>
<body>
<?php include(BASE_PATH . 'includes/navbar.php')?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2>Password recovery</h2>
            <p  class="lead">Enter your email address to receive a password reset link.</p>
            
            

            <div id="message"></div>
            
            <form id="forgotPassword" action="../backend/forgot_password_backend.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Send Reset URL</button>
            </form>
        </div>
    </div>
</div>
<div class="container">
        <?php include BASE_PATH . 'includes/footer.php'?>
    </div>

 </body>
 <script>
      document.getElementById("forgotPassword").addEventListener("submit", function(event) {
      event.preventDefault(); // Αποτρέπει το default submit

      let formData = new FormData(this);

        fetch('../backend/forgot_password_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("forgotPassword"))  })
        .then(response => response.json())
        .then(data => {
        if (data.status === "success") {
           //window.location.href = "forgot_password.php?success=" + encodeURIComponent(data.message);
           document.getElementById("message").innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        } else {
           document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }   
    })
.catch(error => console.error("Error:", error));
});

    </script>    