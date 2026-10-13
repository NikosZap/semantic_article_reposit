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


