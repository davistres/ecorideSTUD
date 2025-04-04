document.addEventListener("DOMContentLoaded", function () {
    // Validation inscription
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("password-confirm");
    const registerForm = document.querySelector(".auth-form");

    function validatePasswords() {
        if (passwordInput?.value !== confirmPasswordInput?.value) {
            confirmPasswordInput.setCustomValidity("Les mots de passe ne correspondent pas");
            confirmPasswordInput.classList.add("password-mismatch");
        } else {
            confirmPasswordInput.setCustomValidity("");
            confirmPasswordInput.classList.remove("password-mismatch");
        }
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener("input", validatePasswords);
        confirmPasswordInput.addEventListener("input", validatePasswords);
    }

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            if (passwordInput && confirmPasswordInput) {
                validatePasswords();
            }
            if (!registerForm.checkValidity()) {
                event.preventDefault();
            }
        });
    }

    // Formulaire de contact
    const contactForm = document.getElementById("contact-form");
    if (contactForm) {
        contactForm.addEventListener("submit", function (event) {
            const email = document.getElementById("email");
            const subject = document.getElementById("subject");
            const message = document.getElementById("message");

            if (!email.value.trim()) {
                email.setCustomValidity("Veuillez saisir votre email");
                event.preventDefault();
            } else {
                email.setCustomValidity("");
            }

            if (!subject.value.trim()) {
                subject.setCustomValidity("Veuillez sélectionner un sujet");
                event.preventDefault();
            } else {
                subject.setCustomValidity("");
            }

            if (!message.value.trim()) {
                message.setCustomValidity("Veuillez saisir un message");
                event.preventDefault();
            } else {
                message.setCustomValidity("");
            }
        });
    }

    // Validation du formulaire de satisfaction
    const satisfactionForm = document.querySelector('form[action*="satisfaction.store"]');
    if (satisfactionForm) {
         const feelingRadios = satisfactionForm.querySelectorAll('input[name="feeling"]');
         const commentField = satisfactionForm.querySelector('#comment');

         feelingRadios.forEach(radio => {
             radio.addEventListener('change', function() {
                 if (this.value === '0') { // Trajet mal passé
                     commentField.setAttribute('required', 'required');
                 } else {
                     commentField.removeAttribute('required');
                 }
             });
         });
    }

});
