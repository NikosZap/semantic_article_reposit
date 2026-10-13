      document.getElementById("loginForm2").addEventListener("submit", function(event) {
      event.preventDefault();

      let formData = new FormData(this);

        fetch('../backend/login_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("loginForm2"))  })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    window.location.href = data.redirect;
                } else {
                    document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }   
            })
        .catch(error => console.error("Error:", error));
    });
