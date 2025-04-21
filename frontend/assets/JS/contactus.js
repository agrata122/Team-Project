document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("contactForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        alert("Message Sent Successfully!");
        
        setTimeout(() => {
            form.submit();
        }, 1000);
    });
});

// Toggle Menu for Mobile
function toggleMenu() {
    const contactContainer = document.querySelector(".contact-container");
    contactContainer.classList.toggle("show-menu");
}


