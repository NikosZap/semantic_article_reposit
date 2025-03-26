<?php
require_once dirname(__DIR__) . '/config.php';


// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user']['is_logged_in'])) {
    header("Location: login.php?error=Πρέπει να συνδεθείτε πρώτα!");
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
        crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <title>User Profile</title>
</head>
<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <div class="container mt-5">
        <h2>My profile</h2>

        <div id="message"></div>

        <form id="profileForm" action="../backend/profile_backend.php" method="POST">
            <input type="hidden" name="userID" value="<?php echo $user['id']; ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Firstame</label>
                <input type="text" class="form-control" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Lastname</label>
                <input type="text" class="form-control" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
            </div>

            <button type="button" id="editButton" class="btn btn-warning">Edit</button>
            <button type="submit" id="saveButton" class="btn btn-success" style="display: none;">Save</button>
        </form>
    </div>

    <div class="container">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>
        document.getElementById("editButton").addEventListener("click", function() {
            let inputs = document.querySelectorAll(" input[name='firstname'], input[name='lastname'], input[name='email']");
            inputs.forEach(input => input.removeAttribute("readonly"));
            document.getElementById("editButton").style.display = "none";
            document.getElementById("saveButton").style.display = "block";
        });

        document.getElementById("profileForm").addEventListener("submit", function(event) {
           event.preventDefault(); // Αποτρέπει το default submit

        let formData = new FormData(this);

        fetch('../backend/profile_backend.php', {
               method: 'POST',
               body: new FormData(document.getElementById("profileForm"))  })
            .then(response => response.json())
            .then(data => {
            if (data.status === "success") {
                document.getElementById("message").innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            } else {
                document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }   
        })
        .catch(error => console.error("Error:", error));
        });
    </script>
</body>
</html>
