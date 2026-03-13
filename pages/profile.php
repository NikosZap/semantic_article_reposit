
<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';

if (!isset($_SESSION['user']) || !$_SESSION['user']['is_logged_in']) {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="el">
	<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
		<title>User Profile</title>
	</head>
	<body>
<?php include(BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<h3>User's Information</h3>
				<div id="message"/>
				<form id="profileForm">
					<div class="mb-3">
						<label class="form-label">Username</label>
						<input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" disabled>
					</div>
					<div class="mb-3">
						<label class="form-label">Email</label>
						<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" disabled>
					</div>
					<div class="mb-3">
						<label class="form-label">First Name</label>
						<input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" class="form-control" disabled>
					</div>
					<div class="mb-3">
						<label class="form-label">Last Name</label>
						<input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" class="form-control" disabled>
					</div>
					<div class="mb-3">
						<label class="form-label">Role</label>
						<input type="text" name="role" value="<?= htmlspecialchars($user['role']) ?>" class="form-control" disabled>
					</div>
					<div class="d-flex gap-2">
						<button type="button" id="editBtn" class="btn btn-warning">
							<i class="bi bi-pencil"/> Edit</button>
						<button type="submit" id="saveBtn" class="btn btn-success" style="display:none;">
							<i class="bi bi-save"/> Save</button>
						<button type="button" id="deleteBtn" class="btn btn-danger ms-auto">
							<i class="bi bi-trash"/> Delete User</button>
					</div>
				</form>
			</div>
		</main>
		<div class="container mt-auto">
        <?php include(BASE_PATH . 'includes/footer.php')?>
		</div>
	</body>
</html>
<script>
function showMessage(type, message) {
    const el = document.getElementById("message");
    el.innerHTML = '<div class="alert alert-' + (type === " success" ?"success" :"danger" ) +'">' + message +'</div>' ; setTimeout(()=> { el.innerHTML = ""; }, 4000);
}

document.getElementById("editBtn").addEventListener("click", function () {
    document.querySelectorAll("#profileForm input").forEach(input => {
        if (!["username", "role"].includes(input.name)) {
            input.removeAttribute("disabled");
        }
    });
    document.getElementById("editBtn").style.display = "none";
    document.getElementById("saveBtn").style.display = "inline-block";
});

document.getElementById("profileForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "saveEditedAccount");

    fetch("../backend/profile_backend.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showMessage("success", data.message);
            document.querySelectorAll("#profileForm input").forEach(input => input.setAttribute("readonly", true));
            document.getElementById("editBtn").style.display = "inline-block";
            document.getElementById("saveBtn").style.display = "none";
        } else {
            showMessage("error", data.message);
        }
    });
});

document.getElementById("deleteBtn").addEventListener("click", function () {
    if (!confirm("Θέλεις σίγουρα να διαγράψεις τον λογαριασμό σου;")) return;
    fetch("../backend/profile_backend.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=deleteAccount"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert(data.message);
            window.location.href = "../pages/login.php";
        } else {
            showMessage("error", data.message);
        }
    });
});
</script>

