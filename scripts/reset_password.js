document.getElementById("resetPassword").addEventListener("submit", function(event) {

    event.preventDefault();

    let formData = new FormData(this);

    fetch('../backend/reset_password_backend.php', {
        method: 'POST',
        body: formData
    })

    .then(response => response.json())

    .then(data => {

        if (data.status === "error") {

            document.getElementById("message").innerHTML =
                `<div class="alert alert-danger">${data.message}</div>`;

        } else {

            window.location.href = data.redirect;

        }

    })

    .catch(error => console.error("Error:", error));

});
