<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sparql_queries.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';
require_once BASE_PATH . 'includes/stats_sparql_queries.php';

if (!isset($_SESSION['user']) || !$_SESSION['user']['is_logged_in']) {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}

$user = $_SESSION['user'];
$hasOrcid = isset($user['orcid']) && !empty($user['orcid']);

$uploadedPublications = getUploadedPublicationsByUser($user['uri']);

$userStats = getUserPublicationStats($user['uri']);

$authorPubCount = 0;
$orcidPublications = [];

if ($hasOrcid) {
    $authorPubCount = getAuthorPublicationCount($user['orcid']);
    $orcidPublications = getPublicationsByOrcid($user['orcid']);

}

?>

<!DOCTYPE html>
<html lang="el">
<head>
    <?php include(BASE_PATH . 'includes/header.php'); ?>
    <title>User Profile</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.0.1/js/countrySelect.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.0.1/css/countrySelect.min.css">
</head>

<body>
    <?php include(BASE_PATH . 'includes/navbar.php'); ?>

    <style>
        .country-select {
            width: 100% !important;
        }

        .country-select input {
            width: 100% !important;
            display: block !important;
        }

        .country-select .flag-dropdown {
            left: 0;
        }
    </style>
    <main>
        <div class="container mt-5 mb-5">
        <div class="row">

            <!-- LEFT SIDE TABS -->
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="nav flex-column nav-pills" id="profileTabs" role="tablist" aria-orientation="vertical">

                    <button class="nav-link active text-start mb-2" id="info-tab"
                            data-bs-toggle="pill" data-bs-target="#info-tab-pane"
                            type="button" role="tab">
                        Profile Info
                    </button>

                    <button class="nav-link text-start mb-2" id="uploads-tab"
                            data-bs-toggle="pill" data-bs-target="#uploads-tab-pane"
                            type="button" role="tab">
                        Uploaded Publications
                    </button>
                
                <?php if ($hasOrcid): ?>
                    <button class="nav-link text-start" id="orcid-tab"
                            data-bs-toggle="pill" data-bs-target="#orcid-tab-pane"
                            type="button" role="tab">
                        ORCID Publications
                    </button>
                <?php endif; ?>



                    <button class="nav-link text-start mb-2" id="stats-tab"
                            data-bs-toggle="pill" data-bs-target="#stats-tab-pane"
                            type="button" role="tab">
                        Statistics
                    </button>
                </div>
            </div>

            <!-- RIGHT SIDE CONTENT -->
            <div class="col-md-8 col-lg-9">
        <div class="tab-content" id="profileTabsContent">

            <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h3 class="display-6">User's Information</h3>
                        <div id="message"></div>

                        <form id="profileForm">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input
                                    type="text"
                                    name="username"
                                    value="<?= htmlspecialchars($user['username']) ?>"
                                    class="form-control"
                                    disabled >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    value="<?= htmlspecialchars($user['email']) ?>"
                                    class="form-control"
                                    disabled >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input
                                    type="text"
                                    name="firstname"
                                    value="<?= htmlspecialchars($user['firstname']) ?>"
                                    class="form-control"
                                    disabled >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input
                                    type="text"
                                    name="lastname"
                                    value="<?= htmlspecialchars($user['lastname']) ?>"
                                    class="form-control"
                                    disabled >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input
                                    type="text"
                                    name="role"
                                    value="<?= htmlspecialchars($user['role']) ?>"
                                    class="form-control"
                                    disabled >
                            </div>
                            <div class="mb-3">

                                <?php
                                    $createdAt = $user['createdAt'] ?? null;
                                    if (!empty($createdAt) && $createdAt !== 'N/A') {
                                        $formattedDate = date('d M Y, H:i', strtotime($createdAt));
                                    } else {
                                        $formattedDate = 'N/A';
                                    }
                                ?>

                                <label class="form-label">Created At</label>
                                <input
                                    type="text"
                                    name="createdAt"
                                    value="<?= htmlspecialchars($formattedDate) ?>"
                                    class="form-control"
                                    disabled >
                            </div>

                            <?php if ($hasOrcid): ?>
                                <div class="mb-3">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <input
                                        type="text"
                                        name="nationality"
                                        id="nationality"
                                        class="form-control"
                                        disabled >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Affiliation</label>
                                    <input
                                        type="text"
                                        name="affiliation"
                                        value="<?= htmlspecialchars($user['affiliation'] ?? '') ?>"
                                        class="form-control"
                                        disabled >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Scientific Area</label>
                                    <input
                                        type="text"
                                        name="scientificArea"
                                        value="<?= htmlspecialchars($user['scientificArea'] ?? '') ?>"
                                        class="form-control"
                                        disabled >
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-3 justify-content-between">
                                <button type="button" id="editBtn" class="btn btn-warning flex-fill">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>

                                <button type="submit" id="saveBtn" class="btn btn-success flex-fill" style="display:none;">
                                    <i class="bi bi-save"></i> Save
                                </button>

                                <button type="button" id="deleteBtn" class="btn btn-danger flex-fill ms-auto">
                                    <i class="bi bi-trash"></i> Delete User
                                </button>
                            </div>
                        </form>

                        <?php if (!$hasOrcid): ?>
                            <div class="mt-4">
                                <a href="../pages/login_orcid.php?bind=true" class="btn btn-success flex-fill">
                                    <i class="bi bi-box-arrow-in-right"></i> Connect ORCID
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3">
                                Connected with ORCID:
                                <strong><?= htmlspecialchars($user['orcid']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="uploads-tab-pane" role="tabpanel" aria-labelledby="uploads-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="display-6">Uploaded Publications</h4>

                        <?php if (!empty($uploadedPublications)): ?>
                            <?php foreach ($uploadedPublications as $pub): ?>
                                <?php
                                    $dateToShow = '';
                                    if (!empty($pub['uploadedDate'])) {
                                        $dateToShow = substr($pub['uploadedDate'], 0, 10);
                                    }
                                ?>
                                <div class="border rounded-4 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <h5 class="mb-1">
                                                <a href="<?php echo BASE_URL ;?>/publication.php?uri=<?= urlencode($pub['uri']) ?>" class="text-decoration-none publication-title-link">
                                                    <?= htmlspecialchars($pub['title']) ?>
                                                </a>
                                            </h5>

                                            <div class="text-muted small">
                                                <?php if (!empty($pub['uploadedDate'])): ?>
                                                    Uploaded: <?= htmlspecialchars($dateToShow) ?>
                                                <?php else: ?>
                                                    Date not available
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div>
                                            <?php if (($pub['isApproved'] ?? '') === 'true'): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php elseif (($pub['isApproved'] ?? '') === 'false'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Unknown</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0">
                                You have not uploaded any publications yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php if ($hasOrcid): ?>
            <div class="tab-pane fade" id="orcid-tab-pane" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="display-6">Publications associated with your ORCID</h4>
                        <p class="text-muted mb-4">
                            These publications are connected to your author identity through your ORCID.
                        </p>

                        <?php if (!empty($orcidPublications)): ?>
                            <?php foreach ($orcidPublications as $pub): ?>
                                <?php
                                    $dateToShow = '';
                                    if (!empty($pub['publicationDate'])) {
                                        $dateToShow = substr($pub['publicationDate'], 0, 10);
                                    }
                                ?>
                                <div class="border rounded-4 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <h5 class="mb-1">
                                                <a href="<?php echo BASE_URL ;?>/publication.php?uri=<?= urlencode($pub['uri']) ?>"
                                                class="text-decoration-none text-dark fw-semibold">
                                                    <?= htmlspecialchars($pub['title']) ?>
                                                </a>
                                            </h5>

                                            <div class="text-muted small">
                                                <?php if (!empty($dateToShow)): ?>
                                                    Publication Date: <?= htmlspecialchars($dateToShow) ?>
                                                <?php else: ?>
                                                    Publication date not available
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0">
                                No publications were found associated with your ORCID.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>        
            </div>
        <?php endif; ?>


            <div class="tab-pane fade" id="stats-tab-pane" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="display-6">Personal Statistics</h4>

                        <div class="row g-4">
                            <h5 class="mt-5 mb-3">Publications Summary</h5>
                            <div class="col-md-6 col-xl-3">
                                <div class="card shadow-sm border-0">
                                    <div class="card-body">
                                        <h6 class="text-muted">Uploaded Publications</h6>
                                        <h3 id="totalPublicationsCount">0</h3>
                                    </div>    
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <div class="card shadow-sm border-0">
                                    <div class="card-body">
                                        <h6 class="text-muted">Approved Publications</h6>
                                        <h3 id="approvedPublicationsCount" class="text-success">0</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <div class="card shadow-sm border-0">
                                    <div class="card-body">
                                        <h6 class="text-muted">Pending Publications</h6>
                                        <h3 id="pendingPublicationsCount" class="text-warning">0</h3>
                                    </div>
                                </div>
                            </div>                   
                        </div>
                            <div class="row g-4">
                                <h5 class="mt-5 mb-3">Publications by Type</h5>
                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Journal Articles</h6>
                                            <h3 id="journalArticlesCount">0</h3>
                                        </div>
                                    </div>
                                </div>   
                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Conference Papers</h6>
                                            <h3 id="conferencePapersCount">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Theses</h6>
                                            <h3 id="thesesCount">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Books</h6>
                                            <h3 id="booksCount">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                <h5 class="mt-5 mb-3">Publications Charts</h5>
                                <div class="col-lg-6">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="card-title">Publications by Type</h6>
                                            <canvas id="userPublicationsByTypeChart" style="max-height:300px;"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="card-title">Publications per Year by Type</h6>
                                            <canvas id="userPublicationsPerYearByTypeChart" style="max-height:300px;"></canvas>
                                        </div>
                                    </div>
                                </div>     
                            </div>
                            <h5 class="mt-5 mb-3">Semantic Statistics</h5>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body">
                                                    <h6 class="text-muted">Total Mentions</h6>
                                                    <h3 id="totalMentionsCount">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body">
                                                    <h6 class="text-muted">Unique Mentions</h6>
                                                    <h3 id="uniqueMentionsCount">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body">
                                                    <h6 class="text-muted">Avg Mentions per Publication</h6>
                                                    <h3 id="avgMentionsPerPublicationCount">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card shadow-sm border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">Top Mentioned Entities</h6>
                                            <canvas id="topMentionedEntitiesChart" style="max-height:300px;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h5 class="mt-5 mb-3">Claims Statistics</h5>
                            <div class="row g-4">
                                 <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Publications with Claims</h6>
                                            <h3 id="publicationsWithClaimsCount">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Total Claims</h6>
                                            <h3 id="totalClaimsCount">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Pending Claims</h6>
                                            <h3 id="pendingClaimsCount">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Approved Claims</h6>
                                            <h3 id="approvedClaimsCount">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">
                                            <h6 class="text-muted">Rejected Claims</h6>
                                            <h3 id="rejectedClaimsCount">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>          
                        </div>        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
        
    </main>

    <div class="container mt-auto">
        <?php include(BASE_PATH . 'includes/footer.php'); ?>
    </div>

    <script>
        function showMessage(type, message) {
            const el = document.getElementById("message");
            el.innerHTML =
                '<div class="alert alert-' +
                (type === "success" ? "success" : "danger") +
                '">' +
                message +
                '</div>';

            setTimeout(() => {
                el.innerHTML = "";
            }, 4000);
        }

        document.getElementById("editBtn").addEventListener("click", function () {
            document.querySelectorAll("#profileForm input").forEach(input => {
                if (!["username", "role", "createdAt" ].includes(input.name)) {
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

                        document.querySelectorAll("#profileForm input").forEach(input => {
                            input.setAttribute("disabled", true);
                        });

                        document.getElementById("editBtn").style.display = "inline-block";
                        document.getElementById("saveBtn").style.display = "none";
                    } else {
                        showMessage("error", data.message);
                    }
                });
        });

        document.getElementById("deleteBtn").addEventListener("click", function () {
            if (!confirm("Are you sure you want to delete your account?")) {
                return;
            }

            fetch("../backend/profile_backend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
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

        <?php $nationality = $user['nationality'] ?? ''; ?>

       $(document).ready(function () {
            const nationality = <?= json_encode($nationality) ?>;

            $("#nationality").countrySelect();

            if (typeof nationality === "string" && nationality.trim() !== "") {
                $("#nationality").countrySelect("setCountry", nationality);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
        fetch('../backend/profile_statistics_backend.php')
            .then(response => response.json())
            .then(res => {
                if (res.status !== 'success') {
                    console.error('Backend error:', res.message);
                    return;
                }
            
                document.getElementById('totalPublicationsCount').textContent = res.data.summary.total;
                document.getElementById('approvedPublicationsCount').textContent = res.data.summary.approved;                        
                document.getElementById('pendingPublicationsCount').textContent = res.data.summary.pending;
                
                document.getElementById('journalArticlesCount').textContent = res.data.type.Article;
                document.getElementById('conferencePapersCount').textContent = res.data.type.ConferencePaper;                        
                document.getElementById('thesesCount').textContent = res.data.type.Thesis;
                document.getElementById('booksCount').textContent = res.data.type.Book;

                document.getElementById('totalMentionsCount').textContent = res.data.mentions.total;                        
                document.getElementById('uniqueMentionsCount').textContent = res.data.mentions.unique;
                document.getElementById('avgMentionsPerPublicationCount').textContent = res.data.mentions.averagePerPublication;    
                
                document.getElementById('totalClaimsCount').textContent = res.data.claims.status.total;                        
                document.getElementById('pendingClaimsCount').textContent = res.data.claims.status.pending;
                document.getElementById('approvedClaimsCount').textContent = res.data.claims.status.approved;
                document.getElementById('rejectedClaimsCount').textContent = res.data.claims.status.rejected;
                document.getElementById('publicationsWithClaimsCount').textContent = res.data.claims.pubs;

                const typeData = res.data.pie || {};;
                const pieCtx = document.getElementById('userPublicationsByTypeChart');
                            
                if (pieCtx) {
                
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Theses', 'Articles', 'Conference Papers', 'Books'],
                        datasets: [{
                            data: [
                                typeData.Thesis ?? 0,
                                typeData.Article ?? 0,
                                typeData.ConferencePaper ?? 0,
                                typeData.Book ?? 0
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            const perYearTypeData = res.data.publicationsPerYearByType || {};

            const years = Object.keys(perYearTypeData);

            const thesisData = years.map(year => perYearTypeData[year]?.['Thesis'] ?? 0);
            const articleData = years.map(year => perYearTypeData[year]?.['Journal Article'] ?? 0);
            const conferencePaperData = years.map(year => perYearTypeData[year]?.['Conference Paper'] ?? 0);
            const bookData = years.map(year => perYearTypeData[year]?.['Book'] ?? 0);

            new Chart(document.getElementById('userPublicationsPerYearByTypeChart'), {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'Theses',
                            data: thesisData
                        },
                        {
                            label: 'Articles',
                            data: articleData
                        },
                        {
                            label: 'Conference Papers',
                            data: conferencePaperData
                        },
                        {
                            label: 'Books',
                            data: bookData
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            const topMentionedEntities = res.data.mentions.topEntities || [];
            const mentionLabels = topMentionedEntities.map(item => item.label);
            const mentionCounts = topMentionedEntities.map(item => item.count);

            const mentionsCtx = document.getElementById('topMentionedEntitiesChart');

            if (mentionsCtx) {
                new Chart(mentionsCtx, {
                type: 'bar',
                data: {
                    labels: mentionLabels,
                    datasets: [{
                    label: 'Mentions',
                    data: mentionCounts
                    }]
                },
                options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                legend: {
                    position: 'bottom'
                }
                },
                scales: {
                    x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
                }
            }
            });
            }


            })
            .catch(error => {
            console.error('Fetch error:', error);
            });
        });
    </script>
</body>
</html>