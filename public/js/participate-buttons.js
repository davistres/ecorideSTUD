// BoutonS "Participer"
document.addEventListener("DOMContentLoaded", function() {
    initParticipateButtons();
});

function initParticipateButtons() {
    // Toutes les card de covoit
    const covoiturageCards = document.querySelectorAll('.covoiturage-card');

    if (covoiturageCards.length === 0) return;

    console.log('Initialisation des boutons "Participer" pour', covoiturageCards.length, 'covoiturages');

    // Récupérer l'ID du covoit => maj le btn "PARTICIPER"
    covoiturageCards.forEach(card => {
        const detailsButton = card.querySelector('.btn-details');
        if (!detailsButton) return;

        const tripId = detailsButton.getAttribute('data-id');
        if (!tripId) return;

        // Statut de l'utilisateur?
        updateParticipateButton(tripId, card);
    });
}

// updateParticipateButton pour un covoit
function updateParticipateButton(tripId, card) {
    // Chargement visuel
    const participateButtons = card.querySelectorAll('.btn-participate');
    participateButtons.forEach(button => {
        button.classList.add('loading');
        button.dataset.originalText = button.textContent;
        button.textContent = 'Chargement...';
    });

    fetch(`/api/user/status?trip_id=${tripId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur lors de la vérification du statut: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            participateButtons.forEach(button => {
                // Maj => texte et URL
                button.textContent = data.button_text;
                button.href = data.can_participate ? `/covoiturage/${tripId}/participate` : data.redirect_to;
                // Retirer l'indicateur de chargement
                button.classList.remove('loading');
            });
        })
        .catch(error => {
            console.error('Erreur lors de la vérification du statut de l\'utilisateur:', error);
            // En cas d'erreur => text original
            participateButtons.forEach(button => {
                button.textContent = button.dataset.originalText || 'Participer';
                button.classList.remove('loading');
            });
        });
}
