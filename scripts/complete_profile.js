    $("#nationality").countrySelect();

    document.getElementById("completeProfileForm").addEventListener("submit", function (e) {
        e.preventDefault();

        const form = e.target;
        const data = new URLSearchParams(new FormData(form));

        fetch("../backend/complete_profile_backend.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: data
        })
        .then(res => res.json())
        .then(data => {
            const msg = document.getElementById("message");

            if (data.status === "success" && data.redirect) {
                window.location.href = data.redirect;
            } else {
                msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(() => {
            document.getElementById("message").innerHTML =
            `<div class="alert alert-danger">Request failed. Try again.</div>`;
        });
    });