document.addEventListener('DOMContentLoaded', function () {
            const deleteBtn = document.getElementById('deletePublicationBtn');

            if (deleteBtn) {
                deleteBtn.addEventListener('click', function () {
                    const uri = this.dataset.uri;

                    if (!confirm('Are you sure you want to delete this publication?')) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('uri', uri);

                    fetch('../backend/review_publication_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const messageBox = document.getElementById('message');

                        if (data.status === 'success') {
                            messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;

                            setTimeout(() => {
                                window.location.href = 'my_publications.php';
                            }, 1200); 
                        } else {
                            messageBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(() => {
                        document.getElementById('message').innerHTML =
                            `<div class="alert alert-danger">Server connection error.</div>`;
                    });
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const message = sessionStorage.getItem("successMessage");

            if (message) {
                document.getElementById("message").innerHTML =
                    `<div class="alert alert-success">${message}</div>`;

                sessionStorage.removeItem("successMessage");
            }
        });

        function openPdfModal(pdfUrl) {
            const frame = document.getElementById('pdfFrame');
            frame.src = pdfUrl;

            const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
            modal.show();
        }