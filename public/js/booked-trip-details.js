// Init => modale covoit réservé => chargement des infos du covoit
document.addEventListener('DOMContentLoaded', function() {
    initBookedTripDetailsModal();
    initBookedTripReviewsSlider();
});

function initBookedTripDetailsModal() {
    const detailButtons = document.querySelectorAll('.booked-trips-widget .trip-detail-btn');
    const modal = document.getElementById('bookedTripDetailsModal');

    if (!detailButtons.length || !modal) return;

    initModalClose(modal);

    detailButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            const tripUrl = this.getAttribute('href');
            if (!tripUrl) return;

            const tripId = tripUrl.split('/').pop();
            console.log('ID du covoiturage réservé:', tripId);

            modal.classList.add('active');

            fetchBookedTripDetails(tripId);
        });
    });
}

// Récupérer les infos
function fetchBookedTripDetails(tripId) {
    const apiUrl = `/api/trips/${tripId}/details`;
    console.log('URL de l\'API:', apiUrl);

    // Indicateurs de chargement
    document.getElementById('modal-loading').style.display = 'flex';
    document.getElementById('modal-content').style.display = 'none';
    document.getElementById('modal-reviews-list').innerHTML = '<div class="loading">Chargement des avis...</div>';

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur lors de la récupération des détails: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            populateBookedTripModal(data);

            // Masquer les indicateurs de chargement
            document.getElementById('modal-loading').style.display = 'none';
            document.getElementById('modal-content').style.display = 'block';
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('modal-loading').style.display = 'none';
            document.getElementById('modal-content').style.display = 'block';
            document.getElementById('modal-reviews-list').innerHTML = '<div class="error">Erreur lors du chargement des détails</div>';
        });
}

// Remplir la modale avec les infos
function populateBookedTripModal(data) {
    console.log('Données reçues:', data);

    try {
        document.getElementById('modal-city-dep').textContent = data.city_dep || '';
        document.getElementById('modal-city-arr').textContent = data.city_arr || '';

        document.getElementById('modal-departure-address').textContent = data.address_dep || '';
        document.getElementById('modal-add-dep-address').textContent = data.add_address_dep || '';
        document.getElementById('modal-postal-code-dep').textContent = data.postal_code_dep || '';

        document.getElementById('modal-arrival-address').textContent = data.address_arr || '';
        document.getElementById('modal-add-arr-address').textContent = data.add_address_arr || '';
        document.getElementById('modal-postal-code-arr').textContent = data.postal_code_arr || '';

        document.getElementById('modal-price').textContent = data.price || '0';
        document.getElementById('modal-n-tickets').textContent = `${data.places_restantes || 0}/${data.n_tickets || 0}`;

        if (data.max_travel_time) {
            document.getElementById('modal-max-travel-time').textContent = formatDuration(data.max_travel_time);
        } else {
            document.getElementById('modal-max-travel-time').textContent = 'Non spécifiée';
        }

        document.getElementById('modal-eco-travel').textContent = data.eco_travel ? 'Écologique' : 'Standard';

        const departureDate = data.departure_date ? new Date(data.departure_date) : null;
        const arrivalDate = data.arrival_date ? new Date(data.arrival_date) : null;

        if (departureDate) {
            document.getElementById('modal-departure-date').textContent = formatDate(departureDate);
        }

        if (arrivalDate) {
            document.getElementById('modal-arrival-date').textContent = formatDate(arrivalDate);
        }

        document.getElementById('modal-departure-time').textContent = data.departure_time ? data.departure_time.substring(0, 5) : '';
        document.getElementById('modal-arrival-time').textContent = data.arrival_time ? data.arrival_time.substring(0, 5) : '';

        if (data.chauffeur) {
            const driver = data.chauffeur;
            const user = driver.utilisateur;

            document.getElementById('modal-driver-pseudo').textContent = user ? user.pseudo : 'Inconnu';

            const driverPhotoContainer = document.getElementById('modal-driver-photo');
            if (user && user.profile_photo && user.profile_photo_mime) {
                driverPhotoContainer.innerHTML = `<img src="data:${user.profile_photo_mime};base64,${user.profile_photo}" alt="Photo du conducteur">`;
            } else {
                driverPhotoContainer.innerHTML = '<div class="driver-photo photo-placeholder"><i class="fas fa-user"></i></div>';
            }

            const driverRating = driver.moy_note || 0;
            document.getElementById('modal-driver-rating').textContent = driverRating.toFixed(1);

            const starsContainer = document.getElementById('modal-driver-stars');
            starsContainer.innerHTML = generateStars(driverRating);

            document.getElementById('modal-pref-smoke').textContent = driver.pref_smoke || 'Non spécifié';
            document.getElementById('modal-pref-pet').textContent = driver.pref_pet || 'Non spécifié';

            const prefLibreContainer = document.getElementById('modal-pref-libre-container');
            if (driver.pref_libre && driver.pref_libre.trim() !== '') {
                document.getElementById('modal-pref-libre').textContent = driver.pref_libre;
                prefLibreContainer.style.display = 'block';
            } else {
                prefLibreContainer.style.display = 'none';
            }
        }

        if (data.voiture) {
            const vehicle = data.voiture;

            document.getElementById('modal-immat').textContent = vehicle.immat || 'Non spécifié';
            document.getElementById('modal-brand').textContent = vehicle.brand || 'Non spécifié';
            document.getElementById('modal-model').textContent = vehicle.model || 'Non spécifié';
            document.getElementById('modal-color').textContent = vehicle.color || 'Non spécifié';
            document.getElementById('modal-energie').textContent = vehicle.energie || 'Non spécifié';
        }

        const reviewsList = document.getElementById('modal-reviews-list');
        reviewsList.innerHTML = '';

        if (data.reviews && data.reviews.length > 0) {
            console.log(`${data.reviews.length} avis trouvés`);

            data.reviews.forEach(review => {
                const reviewCard = createReviewCard(review);
                const reviewItem = document.createElement('div');
                reviewItem.className = 'review-item';
                reviewItem.appendChild(reviewCard);
                reviewsList.appendChild(reviewItem);
            });

            // Maj btn slider
            setTimeout(() => {
                const modal = document.getElementById('bookedTripDetailsModal');
                const prevBtn = modal.querySelector('.prev-btn');
                const nextBtn = modal.querySelector('.next-btn');
                const reviewsSlider = modal.querySelector('.reviews-slider');

                if (prevBtn && nextBtn && reviewsSlider) {
                    prevBtn.disabled = reviewsSlider.scrollLeft <= 0;
                    nextBtn.disabled = reviewsSlider.scrollLeft >= reviewsSlider.scrollWidth - reviewsSlider.clientWidth - 10;
                }
            }, 500);
        } else {
            reviewsList.innerHTML = '<div class="no-reviews">Aucun avis pour ce conducteur</div>';
        }

        // Bouton d'annulation
        const cancelBtn = document.querySelector('#bookedTripDetailsModal .trip-cancel-btn');
        if (cancelBtn) {
            const reservationId = findReservationId(data.covoit_id);
            if (reservationId) {
                cancelBtn.href = `/reservation/${reservationId}/cancel`;
            } else {
                cancelBtn.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Erreur lors du remplissage de la modale:', error);
    }
}

// formatDate
function formatDate(date) {
    if (!date) return '';

    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

function formatDuration(duration) {
    if (!duration) return 'Non spécifiée';

    if (typeof duration === 'string' && duration.includes(':')) {
        const parts = duration.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);

        if (hours > 0) {
            return `${hours}h${minutes > 0 ? ` ${minutes}min` : ''}`;
        } else {
            return `${minutes}min`;
        }
    }

    const totalMinutes = parseInt(duration);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours > 0) {
        return `${hours}h${minutes > 0 ? ` ${minutes}min` : ''}`;
    } else {
        return `${minutes}min`;
    }
}

// Les étoiles en fonction de la note
function generateStars(rating) {
    let starsHtml = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating - fullStars >= 0.5;

    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            starsHtml += '<span class="star filled"><i class="fas fa-star"></i></span>';
        } else if (i === fullStars + 1 && hasHalfStar) {
            starsHtml += '<span class="star half-filled"><i class="fas fa-star-half-alt"></i></span>';
        } else {
            starsHtml += '<span class="star empty"><i class="far fa-star"></i></span>';
        }
    }

    return starsHtml;
}

// Card des avis
function createReviewCard(review) {
    const card = document.createElement('div');
    card.className = 'review-card';

    const reviewDate = review.date ? new Date(review.date) : new Date();
    const formattedDate = reviewDate.toLocaleDateString('fr-FR');

    const starsHtml = generateStars(review.note || 0);

    card.innerHTML = `
        <div class="review-header">
            <div class="reviewer-name">${review.utilisateur ? review.utilisateur.pseudo : 'Anonyme'}</div>
            <div class="review-date">${formattedDate}</div>
        </div>
        <div class="review-rating">
            <span class="rating-value">${review.note ? review.note.toFixed(1) : '0.0'}</span>
            <span class="rating-stars">${starsHtml}</span>
        </div>
        <div class="review-text">${review.review || 'Aucun commentaire'}</div>
    `;

    return card;
}

// Trouver l'ID de la résa à partir de l'ID du covoiturage
function findReservationId(tripId) {
    const cancelForm = document.querySelector(`.booked-trips-widget .trip-card a[href*="/covoiturage/${tripId}"]`)?.closest('.trip-card')?.querySelector('.cancel-form');

    if (cancelForm) {
        const actionUrl = cancelForm.getAttribute('action');
        if (actionUrl) {
            return actionUrl.split('/').pop();
        }
    }

    return null;
}

// Slider des avis
function initBookedTripReviewsSlider() {
    const modal = document.getElementById('bookedTripDetailsModal');
    if (!modal) return;

    const prevBtn = modal.querySelector('.prev-btn');
    const nextBtn = modal.querySelector('.next-btn');
    const reviewsSlider = modal.querySelector('.reviews-slider');

    if (!prevBtn || !nextBtn || !reviewsSlider) return;

    // Etat des boutons en fonction du début ou de la fin du slider
    function updateSliderButtons() {
        if (!reviewsSlider) return;

        prevBtn.disabled = reviewsSlider.scrollLeft <= 0;

        nextBtn.disabled = reviewsSlider.scrollLeft >= reviewsSlider.scrollWidth - reviewsSlider.clientWidth - 10;
    }

    prevBtn.addEventListener('click', function() {
        reviewsSlider.scrollBy({ left: -300, behavior: 'smooth' });
    });

    nextBtn.addEventListener('click', function() {
        reviewsSlider.scrollBy({ left: 300, behavior: 'smooth' });
    });

    // Défilement à la souris
    let isMouseDown = false;
    let startX;
    let scrollLeft;

    reviewsSlider.addEventListener('mousedown', (e) => {
        isMouseDown = true;
        reviewsSlider.style.cursor = 'grabbing';
        startX = e.pageX - reviewsSlider.offsetLeft;
        scrollLeft = reviewsSlider.scrollLeft;
    });

    reviewsSlider.addEventListener('mouseleave', () => {
        isMouseDown = false;
        reviewsSlider.style.cursor = 'grab';
    });

    reviewsSlider.addEventListener('mouseup', () => {
        isMouseDown = false;
        reviewsSlider.style.cursor = 'grab';
    });

    reviewsSlider.addEventListener('mousemove', (e) => {
        if (!isMouseDown) return;
        e.preventDefault();
        const x = e.pageX - reviewsSlider.offsetLeft;
        const walk = (x - startX) * 2;
        reviewsSlider.scrollLeft = scrollLeft - walk;
        updateSliderButtons();
    });

    // Maj des btn au scroll
    reviewsSlider.addEventListener('scroll', updateSliderButtons);

    // Maj des btn quand la modale s'ouvre
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            setTimeout(updateSliderButtons, 100);
        }
    });

    // Maj des btn quand la fenêtre est redimensionnée
    window.addEventListener('resize', updateSliderButtons);
}
