document.addEventListener('DOMContentLoaded', function() {
    //Suite à la recherche de covoit => créer des suggestions pour envoyer l'utilisateurs vers des covoit existants au lieu de le laisser continuer à chercher manuellement.

    const suggestionLinks = document.querySelectorAll('.suggestion-link');
    //Sélectionner tous les liens de suggestion

    // Ajouter un écoueteur d'éven à chauque lien
    suggestionLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Récupérer les données
            const date = this.getAttribute('data-date');
            const depart = this.getAttribute('data-depart');
            const arrivee = this.getAttribute('data-arrivee');

            // Remplir le formulaire caché
            document.getElementById('suggestion-lieu-depart').value = depart;
            document.getElementById('suggestion-lieu-arrivee').value = arrivee;
            document.getElementById('suggestion-date').value = date;

            document.getElementById('suggestion-form').submit();
        });
    });
});
