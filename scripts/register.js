      document.getElementById("registerForm").addEventListener("submit", function(event) {
      event.preventDefault(); 

      let formData = new FormData(this);

        fetch('../backend/register_backend.php', {
            method: 'POST',
            body: new FormData(document.getElementById("registerForm"))  })
        .then(response => response.json())
        .then(data => {
        if (data.status === "success") {
           document.getElementById("message").innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        } else {
           document.getElementById("message").innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }   
    })
.catch(error => console.error("Error:", error));
});
