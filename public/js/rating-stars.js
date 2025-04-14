// Gestion des étoiles pour les notes
document.addEventListener('DOMContentLoaded', function() {
    updateAllRatingStars();
});

// Mise à jour des étoiles => notation
function updateAllRatingStars() {
    const ratingContainers = document.querySelectorAll('.driver-rating');

    ratingContainers.forEach(container => {
        const ratingValue = container.querySelector('.rating-value');
        const ratingStars = container.querySelector('.rating-stars');

        if (ratingValue && ratingStars) {
            const rating = parseFloat(ratingValue.textContent);

            if (!isNaN(rating)) {
                // Formater la note avec un seul chiffre après la virgule
                ratingValue.textContent = rating.toFixed(1);
                ratingStars.innerHTML = generateStarsHTML(rating);
            } else {
                ratingStars.innerHTML = '<span>Nouveau conducteur</span>';
            }
        }
    });
}

// HTML des étoiles en fonction de la note
function generateStarsHTML(rating) {
    let starsHtml = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating - fullStars >= 0.5;

    // Pleine
    for (let i = 0; i < fullStars; i++) {
        starsHtml += '<span class="star filled">★</span>';
    }

    // A moitié
    if (hasHalfStar) {
        starsHtml += '<span class="star half-filled">★</span>';
    }

    // Vide
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < emptyStars; i++) {
        starsHtml += '<span class="star empty">☆</span>';
    }

    return starsHtml;
}
