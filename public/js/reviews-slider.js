//Slider pour les avis et les notes
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé - Prêt à initialiser les carrousels de reviews');
    // Slider initialisé après le chargement des données
    initAllReviewSliders();
});

// Initialisation
function initAllReviewSliders() {
    console.log('Recherche des conteneurs de reviews sur la page');

    const modalContainer = document.getElementById('modal-reviews-list');
    const confirmContainer = document.getElementById('confirm-reviews-list');

    console.log('Conteneur modal trouvé:', !!modalContainer);
    console.log('Conteneur confirm trouvé:', !!confirmContainer);

    // Slider dans le modal
    if (modalContainer) {
        console.log('Initialisation du carrousel dans le modal');
        setupSimpleSlider(modalContainer);
    }

    // Slider dans confirm
    if (confirmContainer) {
        console.log('Initialisation du carrousel dans la page de confirmation');
        setupSimpleSlider(confirmContainer);
    }
}

//Reviews en slider
function setupSimpleSlider(container) {
    console.log('Configuration du carrousel simple pour le conteneur:', container.id);

    console.log('Réinitialisation du carrousel');

    container.dataset.initialized = 'true';

    // Débogage
    console.log('Contenu HTML du conteneur avant traitement:', container.innerHTML);

    // Cards reviews
    const reviewCards = container.querySelectorAll('.review-card');
    console.log('Nombre de review-cards trouvées:', reviewCards.length);

    // Sinon => message
    if (reviewCards.length === 0) {
        console.log('Aucune review trouvée, affichage du message');
        container.innerHTML = '<div class="no-reviews">Aucun avis pour ce conducteur</div>';
        return;
    }

    console.log(`${reviewCards.length} reviews trouvées, création du carrousel simple`);

    // Container du slider
    const sliderContainer = document.createElement('div');
    sliderContainer.className = 'reviews-slider';

    const navContainer = document.createElement('div');
    navContainer.className = 'slider-nav';

    const prevButton = document.createElement('button');
    prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevButton.className = 'prev-btn';

    const nextButton = document.createElement('button');
    nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextButton.className = 'next-btn';

    navContainer.appendChild(prevButton);
    navContainer.appendChild(nextButton);

    // Vider le container et ajouter les nouveaux éléments
    container.innerHTML = '';
    container.appendChild(sliderContainer);
    container.appendChild(navContainer);

    reviewCards.forEach((card, index) => {
        console.log(`Ajout de la review ${index + 1} au slider`);
        const reviewItem = document.createElement('div');
        reviewItem.className = 'review-item';
        reviewItem.appendChild(card.cloneNode(true));
        sliderContainer.appendChild(reviewItem);
    });

    let currentScroll = 0;
    const itemWidth = 295; // Largeur + gap

    // défilement du slider
    function scrollSlider(direction) {
        if (direction === 'prev') {
            currentScroll = Math.max(0, currentScroll - itemWidth);
        } else {
            currentScroll = Math.min(
                sliderContainer.scrollWidth - sliderContainer.clientWidth,
                currentScroll + itemWidth
            );
        }

        sliderContainer.scrollTo({
            left: currentScroll,
            behavior: 'smooth'
        });

        updateButtonState();
    }

    // Maj de l'état des boutons
    function updateButtonState() {
        prevButton.disabled = currentScroll <= 0;
        nextButton.disabled = currentScroll >= sliderContainer.scrollWidth - sliderContainer.clientWidth - 10; // Marge = 10px
    }

    prevButton.addEventListener('click', () => scrollSlider('prev'));
    nextButton.addEventListener('click', () => scrollSlider('next'));

    sliderContainer.addEventListener('scroll', () => {
        currentScroll = sliderContainer.scrollLeft;
        updateButtonState();
    });

    // PROBLEME => next-btn disable au chargement de la modale...
    // SOLUTION => Initialiser les btn avec un délai QUE pour la modale
    if (container.id === 'modal-reviews-list') {
        setTimeout(() => {
            updateButtonState();
            console.log('État des boutons mis à jour après délai pour le modal');
        }, 300);
    } else {
        updateButtonState();
    }

    // Ecouteur d'événement pour swipeleft et swiperight
    if (typeof Hammer !== 'undefined') {
        const hammer = new Hammer(sliderContainer);
        hammer.on('swipeleft', () => scrollSlider('next'));
        hammer.on('swiperight', () => scrollSlider('prev'));
    }

    console.log('Carrousel simple initialisé avec succès');
}



// Charge les reviews pour un covoit
function loadReviewsForTrip(tripId, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Conteneur ${containerId} non trouvé`);
        return;
    }

    console.log(`Chargement des reviews pour le covoiturage ${tripId}`);
    container.innerHTML = '<div class="loading">Chargement des avis...</div>';

    // => détails d'un covoitdepuis l'API
    fetch(`/api/trips/${tripId}/details`)
        .then(response => response.json())
        .then(data => {
            console.log('Données reçues de l\'API:', data);

            if (!data.reviews || data.reviews.length === 0) {
                console.log('Aucune review trouvée dans les données');
                container.innerHTML = '<div class="no-reviews">Aucun avis pour ce conducteur</div>';
                return;
            }

            console.log(`${data.reviews.length} reviews trouvées`);

            container.innerHTML = '';

            // Créer une card pour chaque review
            data.reviews.forEach(review => {
                const card = createReviewCard(review);
                container.appendChild(card);
            });

            // Initialiser le slider
            setupSimpleSlider(container);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des reviews:', error);
            container.innerHTML = '<div class="error">Erreur lors du chargement des avis</div>';
        });
}

// Créer une card par review
function createReviewCard(review) {
    console.log('Création d\'une carte pour la review:', review);

    const card = document.createElement('div');
    card.className = 'review-card';

    const date = review.date ? new Date(review.date) : new Date();
    const formattedDate = date.toLocaleDateString('fr-FR');

    function generateStars(rating) {
        if (!rating) return '';

        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

        let starsHTML = '';

        for (let i = 0; i < fullStars; i++) {
            starsHTML += '<i class="fas fa-star"></i>';
        }

        if (halfStar) {
            starsHTML += '<i class="fas fa-star-half-alt"></i>';
        }

        for (let i = 0; i < emptyStars; i++) {
            starsHTML += '<i class="far fa-star"></i>';
        }

        return starsHTML;
    }

    try {
        card.innerHTML = `
            <div class="review-header">
                <div class="reviewer-name">${review.utilisateur ? review.utilisateur.pseudo : 'Anonyme'}</div>
                <div class="review-date">${formattedDate}</div>
            </div>
            <div class="review-rating">
                <span class="rating-value">${review.note ? review.note.toFixed(1) : '0.0'}</span>
                <span class="rating-stars">${generateStars(review.note)}</span>
            </div>
            <div class="review-text">${review.review || ''}</div>
        `;
    } catch (error) {
        console.error('Erreur lors de la création de la carte de review:', error);
        card.innerHTML = '<div class="error">Erreur lors de l\'affichage de cet avis</div>';
    }

    return card;
}
