// Modale => détails du covoiturage
document.addEventListener("DOMContentLoaded", function() {
    initTripDetailsModal();
});

function initTripDetailsModal() {
    const detailsButtons = document.querySelectorAll('.btn-details');
    const modal = document.getElementById('tripDetailsModal');

    if (!detailsButtons.length || !modal) return;

    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    });

    // Fermeture en cliquant en dehors
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });

    // Carrousel d'avis
    initReviewsCarousel();

    detailsButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            let tripId;

            if (this.hasAttribute('data-id')) {
                tripId = this.getAttribute('data-id');
            } else {
                const tripUrl = this.getAttribute('href');
                if (tripUrl) {
                    tripId = tripUrl.split('/').pop();
                } else {
                    tripId = '1';
                }
            }

            console.log('ID du covoiturage:', tripId);

            // Charger le détail du covoit
            fetchTripDetails(tripId);
        });
    });
}

function fetchTripDetails(tripId) {
    const modal = document.getElementById('tripDetailsModal');
    if (!modal) return;

    console.log('Récupération des détails du covoiturage:', tripId);

    modal.classList.add('active');
    document.getElementById('modal-reviews-list').innerHTML = '<div class="loading">Chargement des détails...</div>';

    const apiUrl = `/api/trips/${tripId}/details`;
    console.log('URL de l\'API:', apiUrl);

    fetch(apiUrl)
        .then(response => {
            console.log('Réponse de l\'API:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur lors de la récupération des détails: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            populateModalWithData(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('modal-reviews-list').innerHTML = '<div class="error">Erreur lors du chargement des détails</div>';
        });
}

function populateModalWithData(data) {
    console.log('Données reçues:', data);

    document.getElementById('modal-city-dep').textContent = data.city_dep || data.lieu_depart || '';
    document.getElementById('modal-city-arr').textContent = data.city_arr || data.lieu_arrivee || '';
    document.getElementById('modal-departure-address').textContent = data.departure_address || '';
    document.getElementById('modal-add-dep-address').textContent = data.add_dep_address || '';
    document.getElementById('modal-postal-code-dep').textContent = data.postal_code_dep || '';
    document.getElementById('modal-arrival-address').textContent = data.arrival_address || '';
    document.getElementById('modal-add-arr-address').textContent = data.add_arr_address || '';
    document.getElementById('modal-postal-code-arr').textContent = data.postal_code_arr || '';

    try {
        const departureDate = data.departure_date ? new Date(data.departure_date) :
                             data.date_depart ? new Date(data.date_depart) : new Date();
        const arrivalDate = data.arrival_date ? new Date(data.arrival_date) :
                           data.date_arrivee ? new Date(data.date_arrivee) : departureDate;

        document.getElementById('modal-departure-date').textContent = formatDate(departureDate);
        document.getElementById('modal-arrival-date').textContent = formatDate(arrivalDate);

        document.getElementById('modal-departure-time').textContent = data.departure_time ? data.departure_time.substring(0, 5) :
                                                                    data.heure_depart ? data.heure_depart.substring(0, 5) : '';
        document.getElementById('modal-arrival-time').textContent = data.arrival_time ? data.arrival_time.substring(0, 5) :
                                                                  data.heure_arrivee ? data.heure_arrivee.substring(0, 5) : '';

        document.getElementById('modal-max-travel-time').textContent = data.max_travel_time ? formatDuration(data.max_travel_time) : '';
    } catch (error) {
        console.error('Erreur lors du traitement des dates et heures:', error);
    }

    const placesRestantes = data.places_restantes || data.n_tickets || 0;
    document.getElementById('modal-n-tickets').textContent = `${placesRestantes} place${placesRestantes > 1 ? 's' : ''} disponible${placesRestantes > 1 ? 's' : ''}`;
    document.getElementById('modal-price').textContent = data.price || data.prix || '';

    const ecoBadge = document.getElementById('modal-eco-travel');
    if (data.eco_travel || data.ecologique) {
        ecoBadge.innerHTML = '<span class="eco-badge eco">Trajet écologique</span>';
    } else {
        ecoBadge.innerHTML = '<span class="eco-badge standard">Trajet standard</span>';
    }

    try {
        let driverPseudo = '';
        let driverPhoto = document.getElementById('modal-driver-photo');
        let driverRating = document.getElementById('modal-driver-rating');
        let driverStars = document.getElementById('modal-driver-stars');

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

            document.getElementById('modal-pref-smoke').textContent = data.chauffeur.pref_smoke || '';
            document.getElementById('modal-pref-pet').textContent = data.chauffeur.pref_pet || '';

            const prefLibreContainer = document.getElementById('modal-pref-libre-container');
            const prefLibre = document.getElementById('modal-pref-libre');

            if (data.chauffeur.pref_libre) {
                prefLibre.textContent = data.chauffeur.pref_libre;
                prefLibreContainer.style.display = 'flex';
            } else {
                prefLibreContainer.style.display = 'none';
            }
        } else if (data.pseudo_chauffeur) {
            document.getElementById('modal-driver-pseudo').textContent = data.pseudo_chauffeur;

            if (data.photo_chauffeur_data) {
                driverPhoto.innerHTML = `<img src="${data.photo_chauffeur_data}" alt="${data.pseudo_chauffeur}">`;
            } else {
                driverPhoto.innerHTML = '<i class="fas fa-user svg-inline--fa"></i>';
                driverPhoto.classList.add('photo-placeholder');
            }

            if (data.note_chauffeur && data.note_chauffeur > 0) {
                driverRating.textContent = parseFloat(data.note_chauffeur).toFixed(1);
                driverStars.innerHTML = generateStars(data.note_chauffeur);
            } else {
                driverRating.textContent = '';
                driverStars.textContent = 'Nouveau conducteur';
            }

            document.getElementById('modal-pref-smoke').textContent = 'Non disponible';
            document.getElementById('modal-pref-pet').textContent = 'Non disponible';
            document.getElementById('modal-pref-libre-container').style.display = 'none';
        } else {
            document.getElementById('modal-driver-pseudo').textContent = 'Information non disponible';
            driverPhoto.innerHTML = '<i class="fas fa-user svg-inline--fa"></i>';
            driverPhoto.classList.add('photo-placeholder');
            driverRating.textContent = '';
            driverStars.textContent = 'Information non disponible';
            document.getElementById('modal-pref-smoke').textContent = 'Non disponible';
            document.getElementById('modal-pref-pet').textContent = 'Non disponible';
            document.getElementById('modal-pref-libre-container').style.display = 'none';
        }

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
        console.error('Erreur lors du traitement des informations du conducteur:', error);
    }

    try {
        const reviewsList = document.getElementById('modal-reviews-list');
        reviewsList.innerHTML = '';

        if (data.reviews && data.reviews.length > 0) {
            data.reviews.forEach(review => {
                const reviewCard = createReviewCard(review);
                reviewsList.appendChild(reviewCard);
            });

            updateCarouselArrows();
        } else {
            reviewsList.innerHTML = '<div class="no-reviews">Aucun avis pour ce conducteur</div>';
        }

        const participateBtn = document.getElementById('modal-participate-btn');
        const tripId = data.covoit_id || data.id;
        if (tripId) {
            participateBtn.href = `/trips/participate/${tripId}`;
            participateBtn.style.display = 'block';
        } else {
            participateBtn.style.display = 'none';
        }
    } catch (error) {
        console.error('Erreur lors du traitement des avis:', error);
    }
}

function createReviewCard(review) {
    const card = document.createElement('div');
    card.className = 'review-card';

    const header = document.createElement('div');
    header.className = 'review-header';

    const author = document.createElement('span');
    author.className = 'review-author';
    author.textContent = review.utilisateur.pseudo;

    const date = document.createElement('span');
    date.className = 'review-date';
    date.textContent = formatDate(new Date(review.date));

    header.appendChild(author);
    header.appendChild(date);

    const rating = document.createElement('div');
    rating.className = 'review-rating';
    rating.innerHTML = generateStars(review.note);

    const content = document.createElement('div');
    content.className = 'review-content';
    content.textContent = review.review;

    card.appendChild(header);
    card.appendChild(rating);
    card.appendChild(content);

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
    const prevArrow = document.querySelector('.carousel-arrow.prev-arrow');
    const nextArrow = document.querySelector('.carousel-arrow.next-arrow');
    const reviewsList = document.getElementById('modal-reviews-list');

    if (!prevArrow || !nextArrow || !reviewsList) return;

    prevArrow.disabled = reviewsList.scrollLeft <= 0;

    const maxScrollLeft = reviewsList.scrollWidth - reviewsList.clientWidth;
    nextArrow.disabled = Math.abs(reviewsList.scrollLeft - maxScrollLeft) < 10;
}

function formatDate(date) {
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDuration(duration) {
    // HH:MM:SS
    const parts = duration.split(':');
    const hours = parseInt(parts[0]);
    const minutes = parseInt(parts[1]);

    let result = '';
    if (hours > 0) {
        result += `${hours}h`;
    }
    if (minutes > 0 || hours === 0) {
        result += `${minutes}min`;
    }

    return result;
}

function generateStars(rating) {
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
