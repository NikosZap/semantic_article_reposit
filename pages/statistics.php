<?php
require dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/stats_sparql_queries.php';

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header('Location: ../pages/login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="el">
<head>
    <?php include (BASE_PATH . 'includes/header.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Statistics</title>
</head>
<body>

<?php include (BASE_PATH . 'includes/navbar.php') ?>
<main>
    <div class="container mt-5">
    <h3 class="display-6 mb-4">Application Statistics</h3>
    
    <h4 class="mt-5 mb-3">Summary</h4>
    <div class="row g-4">

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Total Users</h6>
                    <h3 id="totalUsers">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Total Authors</h6>
                    <h3 id="totalAuthors">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Total Publications</h6>
                    <h3 id="totalPublications">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Approved Publications</h6>
                    <h3 id="approvedPublications">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Pending Publications</h6>
                    <h3 id="pendingPublications">0</h3>
                </div>
            </div>
        </div>

    </div>
    
    <h4 class="mt-5 mb-3">Publications by Type</h4>

    <div class="row g-4">

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Theses</h6>
                    <h3 id="totalTheses">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Journal Articles</h6>
                    <h3 id="totalJournals">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Conference Papers</h6>
                    <h3 id="totalPapers">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">Books</h6>
                    <h3 id="totalBooks">0</h3>
                </div>
            </div>
        </div>
    <div>
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Publications by Type</h5>
                    <canvas id="publicationTypesPieChart" style="max-height:350px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Publications per Year by Type</h5>
                    <canvas id="publicationsPerYearByTypeChart" style="max-height:350px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <h4 class="mt-5 mb-3">Semantic Statistics</h4>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Total Mentions</h6>
                            <h3 id="totalMentions">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Unique Mentions</h6>
                            <h3 id="uniqueMentions">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Avg Mentions per Publication</h6>
                            <h3 id="avgMentionsPerPublication">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">Top Mentioned Entities</h5>
                    <canvas id="topMentionedEntitiesChart"></canvas>
                </div>
            </div>
        </div>
    </div>    

    <h4 class="mt-5 mb-3">Authors Statistics</h4>
    <div class="row g-4">

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Unique Authors in Publications</h6>
                    <h3 id="authorsInPublications">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Authors with ORCID</h6>
                    <h3 id="authorsWithOrcid">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Authors with System Access</h6>
                    <h3 id="authorsWithSystemAccess">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Average Authors per Publication</h6>
                    <h3 id="avgAuthorsPerPublication">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Average Publications per Author</h6>
                    <h3 id="avgPublicationsPerAuthor">0</h3>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-5 mb-3">Users Statistics</h4>

    <div class="row g-4 align-items-stretch">

    <!-- Tiles -->
    <div class="col-lg-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted">Researchers</h6>
                        <h3 id="researcherUsers">0</h3>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted">Admins</h6>
                        <h3 id="adminUsers">0</h3>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h5 class="card-title">User Registrations per Month</h5>
                <canvas id="usersRegistrationChart" style="height:300px;"></canvas>
            </div>
        </div>
    </div>
    </div>

    <h4 class="mt-5 mb-3">Claims Statistics</h4>
    <div class="row g-4">

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Claims</h6>
                    <h3 id="totalClaims">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Pending Claims</h6>
                    <h3 id="pendingClaims">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Approved Claims</h6>
                    <h3 id="approvedClaims">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Rejected Claims</h6>
                    <h3 id="rejectedClaims">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Publications with Claims</h6>
                    <h3 id="publicationsWithClaims">0</h3>
                </div>
            </div>
        </div>
    </div>

</div>
</main>
    <div class="container">
        <?php include (BASE_PATH . 'includes/footer.php') ?>
    </div>


    <script src="<?= BASE_URL ?>scripts/statistics.js"></script>  
</body>
</html>
