<?php
require_once dirname(__DIR__) . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
	<head>
<?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>About</title>
	</head>
	<body>
    <?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container py-5">
				<div class="row justify-content-center align-items-center">
					<div class="col-md-10">
						<div class="row shadow p-4 rounded bg-white">
							<div class="col-md-5 text-center border-end">
								<img src="../images/aegean_logo.png" alt="University of the Aegean Logo" class="img-fluid" style="max-height: 300px;">
        </div>
								<div class="col-md-7">
									<h2 class="mb-4 display-5">About this Application</h2>
									<p>
										<strong>Developer:</strong> Nikos Zapantis</p>
									<p>
										<strong>Supervisor:</strong> Kyriakos Kritikos</p>
									<p class="fst-italic">This web application is part of a diploma thesis at the University of the Aegean.More information will be added later!</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
		</body>
	</html>
