document.addEventListener('DOMContentLoaded', function() {
    // Obtenir tous les champs date
    const dateInputs = document.querySelectorAll('input[type="date"]');

    // Date du jour au bon format
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${year}-${month}-${day}`;

    // Définir min sur aujourd'hui
    dateInputs.forEach(function(input) {
        input.setAttribute('min', todayFormatted);

        // Cas où il y a déjà une date (date passé par rapport à la date du jour) => forcer la réinitialisation à la date du jour
        if (input.value && input.value < todayFormatted) {
            input.value = todayFormatted;
        }

        // Empêcher la saisie de date passé
        input.addEventListener('change', function() {
            if (this.value < todayFormatted) {
                this.value = todayFormatted;
                alert('Vous ne pouvez pas sélectionner une date passée. La date a été réinitialisée à aujourd\'hui.');
            }
        });
    });
});
