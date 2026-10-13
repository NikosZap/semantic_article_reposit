document.getElementById("forgotPassword").addEventListener("submit", function (event) {

    event.preventDefault();

    let formData = new FormData(this);

    fetch("../backend/forgot_password_backend.php", {
        method: "POST",
        body: formData
    })

    .then(response => response.json())

    .then(data => {

        const messageBox = document.getElementById("message");

        if (data.status === "success") {

            messageBox.innerHTML =
                `<div class="alert alert-success">${data.message}</div>`;

        } else {

            messageBox.innerHTML =
                `<div class="alert alert-danger">${data.message}</div>`;

        }

    })

    .catch(error => {
        console.error("Error:", error);

        document.getElementById("message").innerHTML =
            `<div class="alert alert-danger">An unexpected error occurred.</div>`;
    });

});