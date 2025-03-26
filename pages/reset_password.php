<?php 
require_once dirname(__DIR__) . '/config.php'; 

$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="el">
    <head>
    <?php include(BASE_PATH . 'includes/header.php')?>
    <title>Password reset</title>

</head>
<body>
<?php include(BASE_PATH . 'includes/navbar.php')?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2>Password reset</h2>

                <div id="message"></div>
            
                <form id="resetPassword" action="../backend/reset_password_backend.php" method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                
                    <button type="submit" class="btn btn-success">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
        <div class="container">
            <?php include BASE_PATH . 'includes/footer.php'?>
        </div>
     </body>
     <script>

        document.getElementById("resetPassword").addEventListener("submit", function(event) {
        event.preventDefault(); // Αποτρέπει το default submit

        let formData = new FormData(this);

        fetch('../backend/reset_password_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("resetPassword"))  })
        .then(response => response.json())
        .then(data => {
        if (data.status === "error") {
            document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
        else{
            window.location.href = data.redirect;
        }  
        })
        .catch(error => console.error("Error:", error));
    });

</script>
</html>    
