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

       $(document).ready(function () {


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