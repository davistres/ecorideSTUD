document.addEventListener('DOMContentLoaded', function() {
    // La section filtres existe?
    const filtersSection = document.querySelector('.filters-section');
    if (!filtersSection) return;

    const ecoFilter = document.getElementById('eco-filter');
    const priceFilter = document.getElementById('price-filter');
    const priceValue = document.getElementById('price-value');
    const durationFilter = document.getElementById('duration-filter');
    const durationValue = document.getElementById('duration-value');
    const ratingFilter = document.getElementById('rating-filter');
    const stars = document.querySelectorAll('.rating-filter .star');
    const covoiturageCards = document.querySelectorAll('.covoiturage-card');
    const resultsCount = document.querySelector('.results-title p');
    const priceLabelElement = document.querySelector('label[for="price-filter"]');
    const durationLabelElement = document.querySelector('label[for="duration-filter"]');

    // Affichage du nombre de résultats
    function updateResultsCount() {
        const visibleCards = document.querySelectorAll('.covoiturage-card:not(.filtered-out)').length;
        if (resultsCount) {
            resultsCount.textContent = `${visibleCards} résultat(s) trouvé(s)`;
        }
    }

    // Convertir les mn en heures et mn
    function formatDuration(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;

        if (hours > 0) {
            return `${hours} heure${hours > 1 ? 's' : ''} et ${mins} minute${mins > 1 ? 's' : ''}`;
        } else {
            return `${mins} minute${mins > 1 ? 's' : ''}`;
        }
    }

    // HH:MM:SS de la base de donnée en mn
    function timeToMinutes(timeString) {
        if (!timeString) return 120; // Valeur par défaut/////////////////

        // Si c'est déjà un nombre, le retourner tel quel
        if (!isNaN(timeString)) {
            return Math.ceil(parseFloat(timeString));
        }

        // Format HH:MM:SS ou HH:MM
        const parts = timeString.split(':');
        if (parts.length >= 2) {
            const hours = parseInt(parts[0], 10) || 0;
            const minutes = parseInt(parts[1], 10) || 0;
            const seconds = parts.length > 2 ? parseInt(parts[2], 10) || 0 : 0;

            // Calcule du total en minute (avec arrondi sup pour éviter les problèmes d'arrondi)
            const totalMinutes = Math.ceil(hours * 60 + minutes + seconds / 60);
            console.log(`Conversion de ${timeString} en ${totalMinutes} minutes`);
            return totalMinutes;
        }

        return 120; // Valeur par défaut//////////////////////
    }

    // Affichage de la durée
    function updateDurationDisplay() {
        if (durationValue && durationFilter) {
            const minutes = parseInt(durationFilter.value);
            durationValue.textContent = formatDuration(minutes);
            console.log('Valeur du slider de durée:', minutes);
            console.log('Valeur maximale du slider:', durationFilter.max);
        }
    }

    // Appliquer tous les filtres
    function applyFilters() {
        const isEcoFilterActive = ecoFilter.checked;
        const maxPrice = parseInt(priceFilter.value);
        const maxDuration = parseInt(durationFilter.value);
        const minRating = parseInt(ratingFilter.value);

        console.log('Valeurs des filtres:');
        console.log('- Filtre écologique actif:', isEcoFilterActive);
        console.log('- Prix maximum:', maxPrice);
        console.log('- Durée maximale:', maxDuration);
        console.log('- Note minimale:', minRating);
        console.log('- Valeur max du slider de durée:', durationFilter.max);

        let visibleCount = 0;

        covoiturageCards.forEach(card => {
            // Récupérer les infos du covoit
            const isEco = card.querySelector('.trip-eco-badge.eco') !== null;
            const priceText = card.querySelector('.price-value').textContent;
            const priceMatch = /\d+/.exec(priceText);
            const price = priceMatch ? parseInt(priceMatch[0]) : 0;

            // Récupérer la durée max
            const durationAttr = card.getAttribute('data-max-travel-time') || 120;
            const duration = timeToMinutes(durationAttr);

            console.log('Carte:', card);
            console.log('Durée de la carte (minutes):', duration);
            console.log('Durée maximale du filtre (minutes):', maxDuration);

            // Récupérer la note du conducteur
            let rating = card.querySelector('.rating-value').textContent;
            rating = rating === 'Nouveau conducteur' ? 0 : parseFloat(rating);

            // Appliquer les filtres
            const passesEcoFilter = !isEcoFilterActive || isEco;
            const passesPriceFilter = price <= maxPrice;
            // PROBLEME le filtre ne va pas jusqu'au bout!!!! => ajout d'une marge de 5 minutes en +
            const isMaxDuration = maxDuration === parseInt(durationFilter.max);
            const passesDurationFilter = isMaxDuration || duration <= (maxDuration + 5);
            console.log('Passe le filtre de durée:', passesDurationFilter, duration, maxDuration, 'isMaxDuration:', isMaxDuration);
            const passesRatingFilter = rating >= minRating;

            // Afficher ou masquer covoiturage-card en fonction des filtres
            if (passesEcoFilter && passesPriceFilter && passesDurationFilter && passesRatingFilter) {
                card.classList.remove('filtered-out');
                visibleCount++;
            } else {
                card.classList.add('filtered-out');
            }
        });

        // Maj compteur
        updateResultsCount();

        // Maj des étoiles après le filtrage
        if (typeof updateAllRatingStars === 'function') {
            updateAllRatingStars();
        }

        const noResultsMessage = document.querySelector('.no-results-message');
        if (visibleCount === 0) {
            if (!noResultsMessage) {
                const message = document.createElement('div');
                message.className = 'no-results-message';
                message.textContent = 'Aucun covoiturage ne correspond à vos critères de filtrage.';// si aucun résultat

                const resetButton = document.createElement('button');
                resetButton.className = 'reset-filters-btn';
                resetButton.textContent = 'Réinitialiser les filtres';
                resetButton.addEventListener('click', resetFilters);

                message.appendChild(document.createElement('br'));
                message.appendChild(resetButton);

                const covoiturageList = document.querySelector('.covoiturage-list');
                if (covoiturageList) {
                    covoiturageList.appendChild(message);
                }
            }
        } else if (noResultsMessage) {
            noResultsMessage.remove();
        }
    }

    // Réinitialiser es filtres
    function resetFilters() {
        ecoFilter.checked = false;

        priceFilter.value = priceFilter.max;
        priceValue.textContent = priceFilter.max;

        durationFilter.value = durationFilter.max;
        updateDurationDisplay();

        ratingFilter.value = 0;
        stars.forEach(star => star.classList.remove('active'));

        applyFilters();
    }

    // Filtre écologique
    if (ecoFilter) {
        ecoFilter.addEventListener('change', applyFilters);
    }

    // Filtre prix
    if (priceFilter && priceValue) {
        priceFilter.addEventListener('input', function() {
            priceValue.textContent = this.value;
            applyFilters();
        });
    }

    // Filtre durée
    if (durationFilter && durationValue) {
        updateDurationDisplay();

        durationFilter.addEventListener('input', function() {
            updateDurationDisplay();
            applyFilters();
        });
    }

    // Filtre note
    if (stars.length > 0 && ratingFilter) {
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const clickedRating = parseInt(this.getAttribute('data-rating'));
                const currentRating = parseInt(ratingFilter.value);

                // Si on clique sur l'étoile déjà active, on la désactive ainsi que toutes les étoiles inférieures
                if (clickedRating === currentRating) {
                    ratingFilter.value = 0;

                    // Apparence des étoiles (toutes désactivées)
                    stars.forEach(s => {
                        s.classList.remove('active');
                    });
                } else {
                    ratingFilter.value = clickedRating;

                    // Maj apparence des étoiles
                    stars.forEach(s => {
                        const starRating = parseInt(s.getAttribute('data-rating'));
                        if (starRating <= clickedRating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                }

                applyFilters();
            });
        });
    }

    // Tous les trajets ont le même prix? La même durée?
    function checkSameValues() {
        if (covoiturageCards.length < 2) return;

        let allSamePrice = true;
        let firstPrice = null;

        let allSameDuration = true;
        let firstDuration = null;

        covoiturageCards.forEach(card => {
            const priceText = card.querySelector('.price-value').textContent;
            const priceMatch = /\d+/.exec(priceText);
            const price = priceMatch ? parseInt(priceMatch[0]) : 0;

            if (firstPrice === null) {
                firstPrice = price;
            } else if (price !== firstPrice) {
                allSamePrice = false;
            }

            const durationAttr = card.getAttribute('data-max-travel-time');
            const duration = timeToMinutes(durationAttr);

            if (firstDuration === null) {
                firstDuration = duration;
            } else if (duration !== firstDuration) {
                allSameDuration = false;
            }
        });

        // Si les prix sont pareils
        if (allSamePrice && priceFilter && priceValue && priceLabelElement) {
            priceFilter.disabled = true;
            priceFilter.style.opacity = '0.5';
            priceLabelElement.innerHTML = 'Prix maximum: <span id="price-value" style="color: red; font-weight: bold;">Tous les trajets ont le même prix !</span>';
        }

        // Si les durées sont pareils
        if (allSameDuration && durationFilter && durationValue && durationLabelElement) {
            durationFilter.disabled = true;
            durationFilter.style.opacity = '0.5';
            durationLabelElement.innerHTML = 'Durée maximale: <span id="duration-value" style="color: red; font-weight: bold;">Les trajets ont la même durée</span>';
        }
    }

    // Ajoute de data-max-travel-time à chaque covoiturage-card
    covoiturageCards.forEach(card => {
        if (!card.hasAttribute('data-max-travel-time')) {
            card.setAttribute('data-max-travel-time', durationFilter ? durationFilter.max : 120); // valeur par défaut au cas où!!!
        }
    });

    // Les covoit ont le même prix ou la même durée?
    checkSameValues();
});
