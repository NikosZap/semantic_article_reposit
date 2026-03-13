<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$uri = $_GET['uri'] ?? null;
if (!$uri) {
    echo "<p>Error: No Claim URI provided.</p>";
    exit;
}

$claim = getClaimByURI($uri);
if (!$claim) {
    echo "<p>Error: Claim not found.</p>";
    exit;
}

$fieldLabels = [
    'pubTitle' => ' Publication Title',
    'doi' => 'DOI',
    'publicationDate' => 'Publication Date',
    'claimDate' => 'Claim Date',
    'claimDate' => 'Claim Date',
    'reason' => 'Claim Reason',
    'status' => 'Status',
    'publisher' => 'Publisher',
    'userFirstname' => 'User Firstname',
    'userLastname' => 'User Lastname',
    'userEmail' => 'User Email',
    'authorFirstname' => 'Author Firstname',
    'authorLastname' => 'Author Lastname',
    'authorOrcid' => 'Author ORCID'];

    $skipKeys = ['oldAuthorURI', 
             'userURI', 
             'realAuthorURI', 
             'publication', 
             ];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
    <?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>Review Claim</title>
	</head>
	<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<h3 class="display-6">Review Claim</h3>
				<div id="message"/>
				<div class="table-responsive">
					<h5 class="mt-4">Claim</h5>
					<table class="table table-bordered">
        <?php foreach ($claim as $key => $value): ?>
            <?php if (in_array($key, $skipKeys)) continue; ?>
						<tr>
							<th><?= $fieldLabels[$key] ?? htmlspecialchars($key) ?>
							</th>
							<td>
                    <?php if($key === 'status'):
                            if ($value === 'Pending'):?>
								<span class='badge bg-primary'>Pending</span> 
                            <?php elseif ($value === 'Approved'):?>
								<span class='badge bg-success'>Approved</span>
                            <?php elseif ($value === 'Rejected'):?>
								<span class='badge bg-danger'>Rejected</span>    
                            <?php endif; ?>    
                    <?php elseif($key === 'pubTitle'): ?>
								<a href="review_publication.php?uri=<?= urlencode($claim['publication']) ?>">
                            <?= htmlspecialchars($claim['pubTitle']) ?>
								</a>    
                    <?php elseif($key === 'authorOrcid'): ?>
								<a href="https://orcid.org/<?= htmlspecialchars($value) ?>">
                                <?= htmlspecialchars($value) ?>
								</a>
                                  
                    <?php else: ?>    
                        <?= htmlspecialchars($value) ?>
                    <?php endif; ?>
							</td>
						</tr>
        <?php endforeach; ?>
					</table>
				</div>
   <?php if($claim['status']==='Pending'){ ?>
				<div class="d-flex justify-content-between gap-3 mt-4">
					<button onclick="handleAction('approve')" class="btn btn-success">
						<i class="bi bi-check-circle"/> Approve
  </button>
					<button onclick="showHistory()" class="btn btn-primary">
						<i class="bi bi-clock-history"/> History
  </button>
					<button onclick="handleAction('reject')" class="btn btn-danger">
						<i class="bi bi-trash"/> Reject
  </button>
				</div>
<?php } else { ?>
				<div class="d-flex justify-content-center mt-4">
					<button onclick="showHistory()" class="btn btn-primary">
						<i class="bi bi-clock-history"/> History
  </button>
				</div>

<?php } ?>
				<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">Reason for Rejection</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"/>
							</div>
							<div class="modal-body">
								<textarea id="rejectReason" class="form-control" rows="4" placeholder="Explain why the claim is rejected..."/>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-danger" id="confirmReject">Confirm Rejection</button>
							</div>
						</div>
					</div>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
			<script>

  const CLAIM_URI = <?= json_encode($uri) ?>;

  window.handleAction = function(action) {

    if (action === 'reject') {
      const modalEl = document.getElementById('rejectModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      return;
    }

    const userEmail = <?= json_encode($claim['userEmail']) ?>;
    const userFirstname = <?= json_encode($claim['userFirstname']) ?>;
    const userLastname =  <?= json_encode($claim['userLastname']) ?>;
    const pubTitle =  <?= json_encode($claim['pubTitle']) ?>;
    
    const publication  =  <?= json_encode($claim['publication']) ?>;
    const userURI  =  <?= json_encode($claim['userURI']) ?>;
    const oldAuthorURI  =  <?= json_encode($claim['oldAuthorURI']) ?>;
    const realAuthorURI  =  <?= json_encode($claim['realAuthorURI']) ?>;
 
    $.ajax({
      url: '../backend/review_claim_backend.php',
      type: 'POST',
      dataType: 'json',
      data: { action: action, 
              claim_uri: CLAIM_URI, 
              userEmail: userEmail, 
              userFirstname: userFirstname, 
              userLastname: userLastname, 
              pubTitle: pubTitle,
              publication: publication,
              userURI: userURI,
              oldAuthorURI: oldAuthorURI,
              realAuthorURI: realAuthorURI
            },
      success: function (response) {
        if (response.status === 'success') {

          window.location.href = 'approve_claims.php'; 
        } else {
          alert(response.message);
        }
      }
    });
  };

  window.showHistory = function() {
    const pubURI = <?= json_encode($claim['publication']) ?>;
    window.location.href = 'claim_publication_history.php?publication_uri=' + encodeURIComponent(pubURI);;
};

  $('#confirmReject').on('click', function() {
    const reason = $('#rejectReason').val().trim();
    const userEmail = <?= json_encode($claim['userEmail']) ?>;
    const userFirstname = <?= json_encode($claim['userFirstname']) ?>;
    const userLastname =  <?= json_encode($claim['userLastname']) ?>;
    const pubTitle =  <?= json_encode($claim['pubTitle']) ?>;
    if (!reason) {
      alert('Please provide a rejection reason.');
      return;
    }

    $.ajax({
      url: '../backend/review_claim_backend.php',
      type: 'POST',
      dataType: 'json',
      data: { action: 'reject', claim_uri: CLAIM_URI, reject_reason: reason, userEmail: userEmail, userFirstname: userFirstname, userLastname: userLastname, pubTitle: pubTitle },
      success: function (response) {
        if (response.status === 'success') {
          
          window.location.href = 'approve_claims.php'; 
        } else {
          alert(response.message);
        }
      }
    });
  });
</script>
		</body>
	</html>
