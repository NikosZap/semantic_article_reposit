window.handleAction = function(action) {

    if (action === 'reject') {
        const modalEl = document.getElementById('rejectModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        return;
    }

    
    $.ajax({
        url: '../backend/review_claim_backend.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: action,
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
        success: function(response) {

            if (response.status === 'success') {
                window.location.href = 'approve_claims.php';
            } else {
                alert(response.message);
            }

        }
    });

};

window.showHistory = function() {

    window.location.href =
        'claim_publication_history.php?publication_uri=' +
        btoa(publication);

};

$('#confirmReject').on('click', function() {

    if (!reason) {
        alert('Please provide a rejection reason.');
        return;
    }

    $.ajax({
        url: '../backend/review_claim_backend.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'reject',
            claim_uri: CLAIM_URI,
            reject_reason: reason,
            userEmail: userEmail,
            userFirstname: userFirstname,
            userLastname: userLastname,
            pubTitle: pubTitle
        },
        success: function(response) {

            if (response.status === 'success') {
                window.location.href = 'approve_claims.php';
            } else {
                alert(response.message);
            }

        }
    });

});