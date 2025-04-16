// Charger les données du covoit dans la page confirm
document.addEventListener("DOMContentLoaded", function() {
    initTripConfirmPage();
});

function initTripConfirmPage() {
    // Récupérer l'ID depuis l'URL
    const pathParts = window.location.pathname.split('/');
    const tripId = pathParts[pathParts.length - 1];

    if (!tripId) {
        console.error('ID du covoiturage non trouvé dans l\'URL');
        return;
    }

    console.log('Chargement des détails du covoiturage:', tripId);
    fetchTripDetails(tripId);

    // Initialise le slider
    initReviewsCarousel();
}

function fetchTripDetails(tripId) {
    const apiUrl = `/api/trips/${tripId}/details`;
    console.log('URL de l\'API:', apiUrl);

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur lors de la récupération des détails: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            populatePageWithData(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('modal-reviews-list').innerHTML = '<div class="error">Erreur lors du chargement des détails</div>';
        });
}

function populatePageWithData(data) {
    console.log('Données reçues:', data);

    try {
        document.getElementById('modal-city-dep').textContent = data.city_dep || '';
        document.getElementById('modal-city-arr').textContent = data.city_arr || '';

        document.getElementById('modal-departure-address').textContent = data.departure_address || '';
        document.getElementById('modal-add-dep-address').textContent = data.add_dep_address || '';
        document.getElementById('modal-postal-code-dep').textContent = data.postal_code_dep || '';

        document.getElementById('modal-arrival-address').textContent = data.arrival_address || '';
        document.getElementById('modal-add-arr-address').textContent = data.add_arr_address || '';
        document.getElementById('modal-postal-code-arr').textContent = data.postal_code_arr || '';

        document.getElementById('modal-price').textContent = data.price || '';
        document.getElementById('modal-n-tickets').textContent = data.places_restantes || data.n_tickets || '';
        document.getElementById('modal-eco-travel').textContent = data.eco_travel ? 'Écologique' : 'Standard';
    } catch (error) {
        console.error('Erreur lors du traitement des informations de base:', error);
    }

    try {
        const departureDate = data.departure_date ? new Date(data.departure_date) : null;
        const arrivalDate = data.arrival_date ? new Date(data.arrival_date) : null;

        function formatDate(date) {
            if (!date) return '';
            const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return date.toLocaleDateString('fr-FR', options);
        }

        document.getElementById('modal-departure-date').textContent = formatDate(departureDate);
        document.getElementById('modal-arrival-date').textContent = formatDate(arrivalDate);

        document.getElementById('modal-departure-time').textContent = data.departure_time ? data.departure_time.substring(0, 5) :
                                                                    data.heure_depart ? data.heure_depart.substring(0, 5) : '';
        document.getElementById('modal-arrival-time').textContent = data.arrival_time ? data.arrival_time.substring(0, 5) :
                                                                  data.heure_arrivee ? data.heure_arrivee.substring(0, 5) : '';

        function formatDuration(duration) {
            if (!duration) return '';

            // HH:MM:SS?
            if (typeof duration === 'string' && duration.includes(':')) {
                const parts = duration.split(':');
                const hours = parseInt(parts[0]);
                const minutes = parseInt(parts[1]);

                if (hours > 0) {
                    return `${hours} h ${minutes} min`;
                } else {
                    return `${minutes} min`;
                }
            }

            // Si c'est en mn?
            const hours = Math.floor(duration / 60);
            const mins = duration % 60;

            if (hours > 0) {
                return `${hours} h ${mins} min`;
            } else {
                return `${mins} min`;
            }
        }

        document.getElementById('modal-max-travel-time').textContent = data.max_travel_time ? formatDuration(data.max_travel_time) : '';
    } catch (error) {
        console.error('Erreur lors du traitement des dates et heures:', error);
    }

    try {
        const driverPhoto = document.getElementById('modal-driver-photo');
        const driverRating = document.getElementById('modal-driver-rating');
        const driverStars = document.getElementById('modal-driver-stars');
        let driverPseudo = '';

        if (data.chauffeur && data.chauffeur.utilisateur) {
            driverPseudo = data.chauffeur.utilisateur.pseudo || '';
            document.getElementById('modal-driver-pseudo').textContent = driverPseudo;

            if (data.chauffeur.utilisateur.profile_photo) {
                driverPhoto.innerHTML = `<img src="data:image/jpeg;base64,${data.chauffeur.utilisateur.profile_photo}" alt="${driverPseudo}">`;
            } else {
                driverPhoto.innerHTML = '<i class="fas fa-user svg-inline--fa"></i>';
                driverPhoto.classList.add('photo-placeholder');
            }

            if (data.chauffeur.moy_note > 0) {
                driverRating.textContent = data.chauffeur.moy_note.toFixed(1);
                driverStars.innerHTML = generateStars(data.chauffeur.moy_note);
            } else {
                driverRating.textContent = '';
                driverStars.textContent = 'Nouveau conducteur';
            }
        }

        if (data.chauffeur) {
            document.getElementById('modal-pref-smoke').textContent = data.chauffeur.pref_smoke || 'Non spécifié';
            document.getElementById('modal-pref-pet').textContent = data.chauffeur.pref_pet || 'Non spécifié';

            const prefLibreContainer = document.getElementById('modal-pref-libre-container');
            const prefLibre = document.getElementById('modal-pref-libre');

            if (data.chauffeur.pref_libre) {
                prefLibre.textContent = data.chauffeur.pref_libre;
                prefLibreContainer.style.display = 'block';
            } else {
                prefLibreContainer.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Erreur lors du traitement des informations du conducteur:', error);
    }

    try {
        if (data.voiture) {
            document.getElementById('modal-immat').textContent = data.immat || '';
            document.getElementById('modal-brand').textContent = data.voiture.brand || '';
            document.getElementById('modal-model').textContent = data.voiture.model || '';
            document.getElementById('modal-color').textContent = data.voiture.color || '';
            document.getElementById('modal-energie').textContent = data.voiture.energie || 'Non disponible';
        } else {
            document.getElementById('modal-immat').textContent = data.immat || 'Non disponible';
            document.getElementById('modal-brand').textContent = data.marque || data.brand || 'Non disponible';
            document.getElementById('modal-model').textContent = data.modele || data.model || 'Non disponible';
            document.getElementById('modal-color').textContent = data.couleur || data.color || 'Non disponible';
            document.getElementById('modal-energie').textContent = data.energie || 'Non disponible';
        }
    } catch (error) {
        console.error('Erreur lors du traitement des informations du véhicule:', error);
    }

    try {
        console.log('Traitement des reviews dans trip-confirm.js');
        const reviewsList = document.getElementById('confirm-reviews-list');

        if (!reviewsList) {
            console.error('Conteneur confirm-reviews-list non trouvé');
            return;
        }

        console.log('Conteneur confirm-reviews-list trouvé');
        reviewsList.innerHTML = '';

        if (data.reviews && data.reviews.length > 0) {
            console.log(`${data.reviews.length} reviews trouvées dans les données`);

            data.reviews.forEach((review, index) => {
                console.log(`Création de la review-card ${index + 1}/${data.reviews.length}`);
                const reviewCard = createReviewCard(review);
                reviewsList.appendChild(reviewCard);
            });

            console.log('Vérification de la fonction setupSimpleSlider:', typeof setupSimpleSlider);
            // Initialise le slider
            if (typeof setupSimpleSlider === 'function') {
                console.log('Appel de setupSimpleSlider');
                setupSimpleSlider(reviewsList);
            } else {
                console.error('Fonction setupSimpleSlider non disponible');
            }
        } else {
            console.log('Aucune review trouvée dans les données');
            reviewsList.innerHTML = '<div class="no-reviews">Aucun avis pour ce conducteur</div>';
        }
    } catch (error) {
        console.error('Erreur lors du traitement des avis:', error);
    }
}

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

function createReviewCard(review) {
    const card = document.createElement('div');
    card.className = 'review-card';

    const date = review.date ? new Date(review.date) : new Date();
    const formattedDate = date.toLocaleDateString('fr-FR');

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

    return card;
}

function initReviewsCarousel() {
    const prevArrow = document.querySelector('.carousel-arrow.prev-arrow');
    const nextArrow = document.querySelector('.carousel-arrow.next-arrow');
    const reviewsList = document.getElementById('modal-reviews-list');

    if (!prevArrow || !nextArrow || !reviewsList) return;

    prevArrow.addEventListener('click', () => {
        reviewsList.scrollBy({ left: -300, behavior: 'smooth' });
    });

    nextArrow.addEventListener('click', () => {
        reviewsList.scrollBy({ left: 300, behavior: 'smooth' });
    });

    reviewsList.addEventListener('scroll', updateCarouselArrows);
}

function updateCarouselArrows() {
    const carousel = document.querySelector('.reviews-carousel');
    if (!carousel) return;

    const reviewsList = carousel.querySelector('.reviews-list');
    const prevArrow = carousel.querySelector('.prev-arrow');
    const nextArrow = carousel.querySelector('.next-arrow');

    if (!reviewsList || !prevArrow || !nextArrow) return;

    const reviews = reviewsList.querySelectorAll('.review-card');

    if (reviews.length <= 1) {
        prevArrow.style.display = 'none';
        nextArrow.style.display = 'none';
        return;
    }

    prevArrow.style.display = 'flex';
    nextArrow.style.display = 'flex';

    prevArrow.disabled = reviewsList.scrollLeft <= 0;

    const maxScrollLeft = reviewsList.scrollWidth - reviewsList.clientWidth;
    nextArrow.disabled = Math.abs(reviewsList.scrollLeft - maxScrollLeft) < 10;
}
