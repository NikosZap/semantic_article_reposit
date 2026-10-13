 $(document).ready(function () {
            $('#claimForm').on('submit', function (e) {
                e.preventDefault();

                $.ajax({
                    url: '../backend/claim_publication_backend.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (msg) {
                        if (msg.status === 'success') {
                            setTimeout(function () {
                                window.location.href = msg.redirect;
                            }, 1200);
                        } else {
                            $('#claimMessage').html(
                                '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    msg.message +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>'
                            );
                        }
                    },
                    error: function (xhr) {
                        console.log(xhr.responseText);
                        $('#claimMessage').html(
                            '<div class="alert alert-danger">An error occurred while submitting the claim.</div>'
                        );
                    }
                });
            });
        });