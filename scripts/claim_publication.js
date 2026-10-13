document.addEventListener("DOMContentLoaded", function() {
    const alertBox = document.getElementById("flashMessage");

    if (alertBox) {
        setTimeout(function () {
            alertBox.classList.remove("show");
            alertBox.classList.add("fade");

            setTimeout(function () {
                alertBox.remove();
            }, 500); 
        }, 3000); 
    }
});