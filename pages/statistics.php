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
                    <h6 class="text-muted">Authors in Publications</h6>
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


<script>

document.addEventListener('DOMContentLoaded', function () {
    fetch('../backend/statistics_backend.php')
        .then(response => response.json())
        .then(res => {
            if (res.status !== 'success') {
                console.error('Backend error:', res.message);
                return;
            }

            const data = res.data || {};

            // Summary
            document.getElementById('totalUsers').innerText =
                data.summary?.users ?? 0;

            document.getElementById('totalAuthors').innerText =
                data.summary?.authors ?? 0;

            document.getElementById('totalPublications').innerText =
                data.summary?.publications ?? 0;

            document.getElementById('approvedPublications').innerText =
                data.summary?.approved_publications ?? 0;

            document.getElementById('pendingPublications').innerText =
                data.summary?.pending_publications ?? 0;

            // Publications by type cards
            document.getElementById('totalTheses').innerText =
                data.publicationsByType?.thesis ?? 0;

            document.getElementById('totalJournals').innerText =
                data.publicationsByType?.journal_articles ?? 0;

            document.getElementById('totalPapers').innerText =
                data.publicationsByType?.conference_papers ?? 0;

            document.getElementById('totalBooks').innerText =
                data.publicationsByType?.books ?? 0;

            // Authors stats
            document.getElementById('authorsInPublications').innerText =
                data.authorsStats?.in_publications ?? 0;

            document.getElementById('authorsWithOrcid').innerText =
                data.authorsStats?.with_orcid ?? 0;

            document.getElementById('authorsWithSystemAccess').innerText =
                data.authorsStats?.with_system_access ?? 0;

            document.getElementById('avgAuthorsPerPublication').innerText =
                data.authorsStats?.avgAuthorsPerPublication ?? 0;

            document.getElementById('avgPublicationsPerAuthor').innerText =
                data.authorsStats?.avgPublicationsPerAuthor ?? 0;

            // Claims stats
            document.getElementById('totalClaims').innerText =
                data.claimsStats?.total ?? 0;

            document.getElementById('pendingClaims').innerText =
                data.claimsStats?.pending ?? 0;

            document.getElementById('approvedClaims').innerText =
                data.claimsStats?.approved ?? 0;

            document.getElementById('rejectedClaims').innerText =
                data.claimsStats?.rejected ?? 0;

            document.getElementById('publicationsWithClaims').innerText =
                data.claimsStats?.withClaims ?? 0;

            // Users by role
            const usersByRole = data.usersByRole || {};

            document.getElementById('adminUsers').innerText =
                usersByRole.Admin ?? 0;

            document.getElementById('researcherUsers').innerText =
                usersByRole.Researcher ?? 0;

            document.getElementById('totalMentions').innerText =
                data.semanticStats?.total_mentions ?? 0;
            
            document.getElementById('uniqueMentions').innerText =
                data.semanticStats?.unique_mentions ?? 0;
            
            document.getElementById('avgMentionsPerPublication').innerText =
                data.semanticStats?.avg_mentions_per_publication ?? 0;

            // Pie chart - Publications by type
            const publicationTypes = data.publicationTypesChart || {};
            const pieCtx = document.getElementById('publicationTypesPieChart');

            if (pieCtx) {
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: [
                            'Theses',
                            'Articles',
                            'Conference Papers',
                            'Books'
                        ],
                        datasets: [{
                            data: [
                                publicationTypes.theses ?? 0,
                                publicationTypes.journal_articles ?? 0,
                                publicationTypes.conference_papers ?? 0,
                                publicationTypes.books ?? 0
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Stacked bar chart - Publications per year by type
            const yearlyData = data.publicationsPerYearByType || {};
            const years = Object.keys(yearlyData);

            const thesisData = years.map(year => yearlyData[year]['Thesis'] ?? 0);
            const articleData = years.map(year => yearlyData[year]['Journal Article'] ?? 0);
            const conferenceData = years.map(year => yearlyData[year]['Conference Paper'] ?? 0);
            const bookData = years.map(year => yearlyData[year]['Book'] ?? 0);

            const yearlyCtx = document.getElementById('publicationsPerYearByTypeChart');

            if (yearlyCtx) {
                new Chart(yearlyCtx, {
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
                                data: conferenceData
                            },
                            {
                                label: 'Books',
                                data: bookData
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Year'
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                },
                                title: {
                                    display: true,
                                    text: 'Number of Publications'
                                }
                            }
                        }
                    }
                });
            }

            // User registrations chart
            const registrationData = data.userRegistrations || {};
            const registrationLabels = Object.keys(registrationData);
            const registrationCounts = Object.values(registrationData);

            const usersRegistrationCtx = document.getElementById('usersRegistrationChart');

            if (usersRegistrationCtx) {
                new Chart(usersRegistrationCtx, {
                    type: 'line',
                    data: {
                        labels: registrationLabels,
                        datasets: [{
                            label: 'New Users',
                            data: registrationCounts,
                            fill: false,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            const topMentionedEntities = data.topMentionedEntities || [];

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
