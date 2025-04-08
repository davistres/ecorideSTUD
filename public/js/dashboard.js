// Ferme la modale avec la croix ou en en cliquant dehors
function initModalClose(modal) {
    if (!modal) return;
    const closeBtn = modal.querySelector('.modal-close');
    if (closeBtn) {
        const closeModalHandler = () => modal.classList.remove('active');
        closeBtn.removeEventListener('click', closeModalHandler);
        closeBtn.addEventListener('click', closeModalHandler);
    }
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
}

// transformer FETCH en JSON.
function handleFetchResponse(response) {
    if (!response.ok) {
        return response.json()
            .then(errData => {
                 let message = errData.message || `Erreur HTTP ${response.status}`;
                 if (errData.errors) {
                     const firstErrorKey = Object.keys(errData.errors)[0];
                     if (firstErrorKey && errData.errors[firstErrorKey][0]) {
                         message = errData.errors[firstErrorKey][0];
                     }
                 }

                 // 'trip' dans l'url ? => message spécifique
                 const url = response.url || '';
                 if (response.status === 422 && (url.includes('/trip/') || url.includes('/trip') || url.includes('covoiturage'))) {
                     message = 'Vous ne pouvez pas créer ce covoiturage car vous ne respectez pas les délais entre l\'heure de la création du covoiturage et l\'heure du départ. Un délai minimum de 4 heures est requis.';
                 }

                 throw new Error(message);
            })
            .catch((parsingError) => {
                console.error("Erreur parsing JSON:", parsingError);

                // pareil
                const url = response.url || '';
                if (response.status === 422 && (url.includes('/trip/') || url.includes('/trip') || url.includes('covoiturage'))) {
                    throw new Error('Vous ne pouvez pas créer ce covoiturage car vous ne respectez pas les délais entre l\'heure de la création du covoiturage et l\'heure du départ. Un délai minimum de 4 heures est requis.');
                } else if (response.status === 422) {
                    throw new Error('Erreur de validation. Veuillez vérifier les informations saisies.');
                } else {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }
            });
    }
     return response.text().then(text => {
        try {
            return text ? JSON.parse(text) : { success: true };
        } catch (e) {
             console.error("Réponse non-JSON reçue:", text);
             return { success: true, rawResponse: text };
        }
    });
}

// Notification en cas d'erreur fetch
function handleFetchError(error) {
    console.error('Erreur Fetch:', error);

    let message = error.message || 'Une erreur réseau est survenue.';

    // handleFetchResponse gére déjà les erreurs 422

    if (typeof showNotification === 'function') {
        showNotification(message, 'error');
    } else {
        console.error("La fonction showNotification n'est pas définie.");
        alert(message);
    }
}

// Créer card pour un vehicule avec détails
function createVehicleCard(vehicleData) {
    const card = document.createElement('div');
    card.className = 'vehicle-card';
    const immat = vehicleData.immat || '';
    card.setAttribute('data-immat', immat);
    const dateRaw = vehicleData.date_first_immat ? vehicleData.date_first_immat.split('T')[0] : '';
    card.setAttribute('data-date', dateRaw);

    const editUrl = `/vehicles/${immat}/edit`;

    let formattedDate = '';
    if (dateRaw) {
        try {
            formattedDate = new Date(dateRaw + 'T00:00:00Z').toLocaleDateString('fr-FR', {
                 year: 'numeric', month: '2-digit', day: '2-digit'
            });
        } catch (e) { formattedDate = 'Date invalide'; }
    }

    card.innerHTML = `
        <div class="vehicle-info">
            <div class="vehicle-model">
                <span class="vehicle-brand">${vehicleData.brand || 'N/A'}</span>
                <span class="vehicle-name">${vehicleData.model || ''}</span>
            </div>
            <div class="vehicle-details">
                <div class="vehicle-detail"><i class="fas fa-palette"></i> <span>${vehicleData.color || 'N/A'}</span></div>
                <div class="vehicle-detail"><i class="fas fa-users"></i> <span>${vehicleData.n_place || '?'} places</span></div>
                <div class="vehicle-detail"><i class="fas fa-charging-station"></i> <span>${vehicleData.energie || 'N/A'}</span></div>
                <div class="vehicle-detail"><i class="fas fa-id-card"></i> <span>${immat || 'N/A'}</span></div>
                ${formattedDate ? `<div class="vehicle-detail"><i class="fas fa-calendar-alt"></i> <span>${formattedDate}</span></div>` : ''}
            </div>
        </div>
        <div class="vehicle-actions">
            <button type="button" class="vehicle-edit-btn js-edit-vehicle" data-immat="${immat}"><i class="fas fa-edit"></i></button>
            <button type="button" class="vehicle-delete-btn js-delete-vehicle"><i class="fas fa-trash-alt"></i></button>
        </div>
    `;
    return card;
}

// Ecouteurs aux btn => card vehicule
function attachVehicleCardListeners(vehicleCard) {
    const editBtn = vehicleCard.querySelector('.js-edit-vehicle');
    const deleteBtn = vehicleCard.querySelector('.js-delete-vehicle');

    if (editBtn) {
        // Clone le btn pour éviter les problèmes d'écouteurs multiples
        const oldEditBtn = editBtn.cloneNode(true);
        editBtn.parentNode.replaceChild(oldEditBtn, editBtn);
        oldEditBtn.addEventListener('click', handleEditVehicleClick);
    }

    if (deleteBtn) {
        // Clone le btn pour éviter les problèmes d'écouteurs multiples
        const oldDeleteBtn = deleteBtn.cloneNode(true);
        deleteBtn.parentNode.replaceChild(oldDeleteBtn, deleteBtn);
        oldDeleteBtn.addEventListener('click', handleDeleteVehicleClick);
    }
}

// Rempli les champs du formulaire d'édition de véhicule
function populateEditVehicleForm(data) {
    const form = document.getElementById('editVehicleForm');
    if (!form || !data) return;
    form.querySelector('#modal_edit_immat').value = data.immat || '';
    form.querySelector('#modal_edit_date_first_immat').value = data.date_first_immat ? data.date_first_immat.split('T')[0] : '';
    form.querySelector('#modal_edit_marque').value = data.brand || data.marque || '';
    form.querySelector('#modal_edit_modele').value = data.model || data.modele || '';
    form.querySelector('#modal_edit_couleur').value = data.color || data.couleur || '';
    form.querySelector('#modal_edit_n_place').value = data.n_place || '';
    form.querySelector('#modal_edit_energie').value = data.energie || '';
    console.log("Formulaire édition peuplé avec:", data);
}

// Mise à jour => card véhicule
function updateVehicleCardDisplay(immat, vehicleData) {
    const card = document.querySelector(`.vehicle-card[data-immat="${immat}"]`);
    if (!card) return;
    console.log(`Mise à jour de la carte pour ${immat} avec:`, vehicleData);

    card.querySelector('.vehicle-brand').textContent = vehicleData.brand || vehicleData.marque || 'N/A';
    card.querySelector('.vehicle-name').textContent = vehicleData.model || vehicleData.modele || '';
    card.querySelector('.fa-palette + span').textContent = vehicleData.color || vehicleData.couleur || 'N/A';
    card.querySelector('.fa-users + span').textContent = `${vehicleData.n_place || '?'} places`;
    card.querySelector('.fa-charging-station + span').textContent = vehicleData.energie || 'N/A';
    card.querySelector('.fa-id-card + span').textContent = vehicleData.immat || 'N/A';

    const dateSpan = card.querySelector('.fa-calendar-alt + span');
    let formattedDate = '';
    const dateRaw = vehicleData.date_first_immat ? vehicleData.date_first_immat.split('T')[0] : '';
    if (dateRaw) {
        try {
            formattedDate = new Date(dateRaw + 'T00:00:00Z').toLocaleDateString('fr-FR', {
                 year: 'numeric', month: '2-digit', day: '2-digit'
            });
            card.setAttribute('data-date', dateRaw);
        } catch (e) { formattedDate = 'Date invalide'; }
    }
    if (dateSpan) {
        dateSpan.textContent = formattedDate;
        const dateDetailDiv = dateSpan.closest('.vehicle-detail');
        if(dateDetailDiv) dateDetailDiv.style.display = formattedDate ? '' : 'none';
    }
}

// Mise à jour des préférences
function updatePreferencesDisplay(smokePref, petPref, librePref) {
    const smokeIcon = document.querySelector('.preferences-widget .fa-smoking, .preferences-widget .fa-smoking-ban');
    const smokeText = smokeIcon ? smokeIcon.closest('.preference-item').querySelector('span') : null;
    const petIcon = document.querySelector('.preferences-widget .fa-paw, .preferences-widget .fa-ban');
    const petText = petIcon ? petIcon.closest('.preference-item').querySelector('span') : null;
    const libreItem = document.querySelector('.preferences-widget .preference-libre');
    const libreText = libreItem ? libreItem.querySelector('span') : null;

    if (smokeIcon && smokeText) {
        smokeIcon.className = `fas ${smokePref === 'Fumeur' ? 'fa-smoking' : 'fa-smoking-ban'}`;
        smokeText.textContent = smokePref;
    }
    if (petIcon && petText) {
        petIcon.className = `fas ${petPref === 'Acceptés' ? 'fa-paw' : 'fa-ban'}`;
        petText.textContent = `Animaux ${petPref}`;
    }
    if (libreItem && libreText) {
         if (librePref && librePref.trim() !== '') {
              libreText.textContent = librePref;
              libreItem.style.display = '';
         } else {
              libreText.textContent = '';
              libreItem.style.display = 'none';
         }
    }
}

window.immatToDeleteForModal = null;

// clic sur bnt => suppression de véhicule.
function handleDeleteVehicleClick() {
    const vehicleCard = this.closest('.vehicle-card');
    const immat = vehicleCard ? vehicleCard.getAttribute('data-immat') : null;
    if (!immat) return;

    const vehicleCount = document.querySelectorAll('.vehicles-list .vehicle-card').length;
    console.log(`Clic suppression véhicule: ${immat}, Nombre total: ${vehicleCount}`);

    // Si c'est le dernier véhicule => message spécifique pour le changement de rôle
    if (vehicleCount === 1) {
        const lastVehicleModal = document.getElementById('lastVehicleDeleteModal');
        if (lastVehicleModal) {
            window.immatToDeleteForModal = immat;
            console.log(`Stockage immat ${immat} pour confirmation modale.`);
            lastVehicleModal.classList.add('active');
        } else {
            console.error('Modale lastVehicleDeleteModal non trouvée');
        }
    } else {
        // Si ce n'est pas le dernier véhicule =>vérifier s'il est lié à des covoit
        // En fonction, ouvrir la bonne modale
        checkVehicleTrips(immat, (hasTrips, trips) => {
            if (hasTrips) {
                showVehicleWithTripsModal(immat, trips, vehicleCard, vehicleCount);
            } else {
                showSimpleVehicleDeleteModal(immat, vehicleCard);
            }
        });
    }
}

// Check si un véhicule est lié à des covoit
function checkVehicleHasTrips(immat, callback) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken || !immat) {
        callback(false);
        return;
    }

    const url = `/vehicles/${immat}/check-trips`;
    console.log(`Vérification des covoiturages liés au véhicule ${immat}`);

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            console.error(`Erreur HTTP: ${response.status}`);
            callback(false); // En cas d'erreur => je condère qu'il n'y a pas de covoit
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.hasTrips === true) {
            console.log(`Le véhicule ${immat} est lié à des covoiturages`);
            callback(true);
        } else {
            console.log(`Le véhicule ${immat} n'est pas lié à des covoiturages`);
            callback(false);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la vérification des covoiturages:', error);
        callback(false);
    });
}

// Check si un véhicule est lié à des covoit
function checkVehicleTrips(immat, callback) {
    console.log(`Vérification des covoiturages liés au véhicule ${immat}`);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken || !immat) {
        console.error('Token CSRF ou immatriculation manquant');
        // je considère qu'il y a des covoit
        callback(true, [{
            covoit_id: 0,
            city_dep: 'Erreur',
            city_arr: 'Erreur',
            departure_date: new Date().toISOString()
        }]);
        return;
    }

    // timestamp => pour éviter la mise en cache
    const timestamp = new Date().getTime();
    const url = `/vehicles/${immat}/check-trips?_=${timestamp}`;
    console.log(`Appel API: ${url}`);

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        }
    })
    .then(response => {
        console.log(`Réponse reçue: ${response.status}`);
        if (!response.ok) {
            // En cas d'erreur HTTP, je considère qu'il y a des covoiturages
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.hasTrips && data.trips && data.trips.length > 0) {
            console.log(`${data.trips.length} covoiturages trouvés pour ce véhicule`);
            callback(true, data.trips);
        } else {
            console.log('Aucun covoiturage trouvé pour ce véhicule');
            callback(false, []);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la vérification des covoiturages:', error);
        // Erreur = covoit
        callback(true, [{
            covoit_id: 0,
            city_dep: 'Erreur de vérification',
            city_arr: 'Contactez l\'administrateur',
            departure_date: new Date().toISOString()
        }]);
    });
}

// Modale de confirmation +> véhicule lié a un covoit
function showVehicleWithTripsModal(immat, trips, vehicleCard, vehicleCount) {
    const modal = document.getElementById('vehicleWithTripsModal');
    const tripsList = document.getElementById('linked-trips-list');
    const confirmBtn = modal.querySelector('.confirm-delete-vehicle-btn');

    if (!modal || !tripsList || !confirmBtn) {
        console.error('Impossible de trouver les éléments de la modale');
        return;
    }

    console.log('Affichage de la modale de confirmation pour la suppression d\'un véhicule lié à des covoiturages');
    console.log('Nombre de covoiturages liés:', trips.length);

    // liste des covoit
    tripsList.innerHTML = '';

    if (trips.length > 0) {
        const tripListHtml = document.createElement('ul');
        tripListHtml.className = 'linked-trips';

        // Cheak =>message d'erreur
        const isErrorMessage = trips.length === 1 && trips[0].covoit_id === 0;

        if (isErrorMessage) {
            const li = document.createElement('li');
            li.className = 'error-message';
            li.innerHTML = `<span class="trip-route-dash">${trips[0].city_dep}</span> <span class="trip-date">${trips[0].city_arr}</span>`;
            tripListHtml.appendChild(li);

            // Message d'avertissement
            const warningP = document.createElement('p');
            warningP.className = 'warning-text';
            warningP.textContent = "Une erreur s'est produite lors de la vérification des covoiturages. Par mesure de sécurité, nous considérons que ce véhicule est lié à des covoiturages.";
            tripsList.appendChild(warningP);
        } else {
            // Liste des covoit
            trips.forEach(trip => {
                const li = document.createElement('li');
                const departureDate = new Date(trip.departure_date);
                const formattedDate = departureDate.toLocaleDateString();
                li.innerHTML = `<span class="trip-route">${trip.city_dep} → ${trip.city_arr}</span> <span class="trip-date">${formattedDate}</span>`;
                tripListHtml.appendChild(li);
            });
        }

        tripsList.appendChild(tripListHtml);
    } else {
        tripsList.innerHTML = '<p>Aucun covoiturage trouvé.</p>';
    }

    // confirmBtn
    confirmBtn.onclick = () => {
        modal.classList.remove('active');
        if (vehicleCount === 1) {
            deleteVehicleAndResetRole(immat);
        } else {
            deleteVehicleSimple(immat, vehicleCard);
        }
    };

    modal.classList.add('active');

    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.onclick = () => {
            modal.classList.remove('active');
        };
    });
}

// clic sur bnt => nouveau véhicule.
function handleEditVehicleClick() {
    const immat = this.getAttribute('data-immat');
    const editModal = document.getElementById('editVehicleModal');
    const editForm = document.getElementById('editVehicleForm');
    const vehicleCard = this.closest('.vehicle-card');

    if (!immat || !editModal || !editForm || !vehicleCard) {
        console.error("Éléments manquants pour l'édition du véhicule.");
        return;
    }

    const vehicleData = {
        immat: immat,
        brand: vehicleCard.querySelector('.vehicle-brand')?.textContent || '',
        model: vehicleCard.querySelector('.vehicle-name')?.textContent || '',
        color: vehicleCard.querySelector('.fa-palette + span')?.textContent || '',
        n_place: vehicleCard.querySelector('.fa-users + span')?.textContent.split(' ')[0] || '',
        energie: vehicleCard.querySelector('.fa-charging-station + span')?.textContent || '',
        date_first_immat: vehicleCard.getAttribute('data-date') || ''
    };

    populateEditVehicleForm(vehicleData);
    editForm.action = `/vehicles/${immat}`;
    editModal.classList.add('active');
}

// Suppressions de véhicule => pas le dernier
function deleteVehicleSimple(immat, cardElement) {
    const actionUrl = `/vehicles/${immat}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    // Problemes de click multiple => désactiver tous les deleteButtons
    const deleteButtons = document.querySelectorAll('.vehicle-delete-btn');
    deleteButtons.forEach(btn => btn.disabled = true);

    showNotification('Suppression en cours...', 'info');

    fetch(actionUrl, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(handleFetchResponse)
    .then(data => {
        if (data.success) {
            showNotification('Véhicule supprimé avec succès.', 'success');

            if (data.cancelledTrips && data.cancelledTrips > 0) {
                // Si des covoit ont été annulés => afficher la modale de confirmation
                const modal = document.getElementById('vehicleWithTripsModal');
                const tripsList = document.getElementById('linked-trips-list');

                if (modal && tripsList && data.upcomingTrips > 0) {
                    // Remplir la liste des covoit
                    tripsList.innerHTML = `<p>${data.cancelledTrips} covoiturage(s) ont été annulés, dont ${data.upcomingTrips} à venir.</p>`;

                    // Bouton de confirmation pour fermer la modale et recharger la page
                    const confirmBtn = modal.querySelector('.confirm-delete-vehicle-btn');
                    if (confirmBtn) {
                        confirmBtn.textContent = 'OK';
                        confirmBtn.onclick = () => {
                            modal.classList.remove('active');
                            window.location.reload();
                        };
                    }

                    modal.classList.add('active');
                } else {
                    // Afficher une notification => si pas de modale ou pas de covoiturages à venir
                    showNotification(`${data.cancelledTrips} covoiturage(s) lié(s) à ce véhicule ont été annulés.`, 'info');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                // Si aucun covoit annulé => actualisation de la page
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            // Réactiver les btn
            deleteButtons.forEach(btn => btn.disabled = false);
            showNotification(data.message || 'Erreur lors de la suppression.', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression du véhicule:', error);
        deleteButtons.forEach(btn => btn.disabled = false);
        showNotification('Une erreur est survenue lors de la suppression.', 'error');
    });
}

// Suppr du dernier véhicule + réinitialisation du rôle
function deleteVehicleAndResetRole(immat) {
     const actionUrl = `/vehicles/${immat}/reset-role`;
     const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
     if (!csrfToken) return;

     // Problemes de click multiple => désactiver tous les deleteButtons
     const deleteButtons = document.querySelectorAll('.vehicle-delete-btn');
     deleteButtons.forEach(btn => btn.disabled = true);

     showNotification('Suppression en cours...', 'info');

     fetch(actionUrl, {
         method: 'DELETE',
         headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
     })
     .then(handleFetchResponse)
     .then(data => {
         if (data.success) {
             showNotification('Véhicule supprimé et rôle mis à jour vers Passager.', 'success');

             if (data.cancelledTrips && data.cancelledTrips > 0) {
                // Si des covoit ont été annulés => afficher la modale de confirmation
                 const modal = document.getElementById('vehicleWithTripsModal');
                 const tripsList = document.getElementById('linked-trips-list');

                 if (modal && tripsList && data.upcomingTrips > 0) {
                    // Remplir la liste des covoit
                     tripsList.innerHTML = `<p>${data.cancelledTrips} covoiturage(s) ont été annulés, dont ${data.upcomingTrips} à venir.</p>`;

                    // Bouton de confirmation pour fermer la modale et recharger la page
                     const confirmBtn = modal.querySelector('.confirm-delete-vehicle-btn');
                     if (confirmBtn) {
                         confirmBtn.textContent = 'OK';
                         confirmBtn.onclick = () => {
                             modal.classList.remove('active');
                             window.location.reload();
                         };
                     }

                     modal.classList.add('active');
                 } else {
                    // Afficher une notification => si pas de modale ou pas de covoiturages à venir
                     showNotification(`${data.cancelledTrips} covoiturage(s) lié(s) à ce véhicule ont été annulés.`, 'info');

                     setTimeout(() => {
                         window.location.reload();
                     }, 1500);
                 }
             } else {
                 // Si aucun covoit annulé => actualisation de la page
                 setTimeout(() => {
                     window.location.reload();
                 }, 1500);
             }
         } else {
             // Réactiver les btn
             deleteButtons.forEach(btn => btn.disabled = false);
             showNotification(data.message || 'Erreur lors de la suppression/reset.', 'error');
         }
     })
     .catch(error => {
         console.error('Erreur lors de la suppression du véhicule:', error);
         deleteButtons.forEach(btn => btn.disabled = false);
         showNotification('Une erreur est survenue lors de la suppression.', 'error');
     });
}

// Ecouteur => btn suppr photo
function attachDeleteButtonListener() {
    const deleteBtn = document.querySelector('#photo-preview .delete-photo-btn');
    if (deleteBtn) {
        deleteBtn.removeEventListener('click', handleDeletePhoto);
        deleteBtn.addEventListener('click', handleDeletePhoto);
        console.log("Écouteur attaché au bouton supprimer photo.");
    } else {
         console.log("Bouton supprimer photo non trouvé pour attacher l'écouteur.");
    }
}

// Requête => suppr photo de profil
function handleDeletePhoto() {
    if (!confirm('Voulez-vous vraiment supprimer votre photo de profil ?')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const deleteUrl = this.getAttribute('data-delete-url');
    if (!csrfToken || !deleteUrl) {
        console.error("CSRF token ou URL de suppression manquant.");
        showNotification("Erreur technique lors de la suppression.", 'error');
        return;
    }

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(handleFetchResponse)
    .then(data => {
        if (data.success) {
            showNotification('Photo de profil supprimée !', 'success');
            const avatarContainer = document.getElementById('profile-avatar-clickable');
            const previewContainer = document.getElementById('photo-preview');
            const placeholderHtml = '<div class="photo-placeholder"><i class="fas fa-user"></i></div>';
            if (avatarContainer) avatarContainer.innerHTML = placeholderHtml;
            if (previewContainer) previewContainer.innerHTML = placeholderHtml;
        } else {
            showNotification(data.message || 'Erreur lors de la suppression.', 'error');
        }
    })
    .catch(handleFetchError);
}




// Nav => section historique
function initHistoryTabs() {
    const tabs = document.querySelectorAll('.history-tab');
    const tabContents = document.querySelectorAll('.history-tab-content');
    if (!tabs.length || !tabContents.length) return;
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            tabContents.forEach(content => content.classList.remove('active'));
            // Affiche
            const tabName = this.getAttribute('data-tab');
            const activeContent = document.getElementById(tabName + '-history');
            if (activeContent) activeContent.classList.add('active');
        });
    });
}

// Ouverture de la modale => affiche les passagers d'un trajet.
function initPassengersModal() {
    const passengersBtns = document.querySelectorAll('.trip-passengers-btn');
    const passengersModal = document.getElementById('passengersModal');
    if (!passengersBtns.length || !passengersModal) return;

    initModalClose(passengersModal);
    passengersBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tripId = this.getAttribute('data-trip');
            const passengersList = passengersModal.querySelector('#passengers-list');
            const actionUrl = `/trip/${tripId}/passengers`;
            if (!tripId || !passengersList || !actionUrl) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) return;

            passengersList.innerHTML = '<p>Chargement...</p>';
            passengersModal.classList.add('active');

            fetch(actionUrl, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
            .then(handleFetchResponse)
            .then(data => {
                passengersList.innerHTML = '';
                if (data.passengers && data.passengers.length > 0) {
                    data.passengers.forEach(p => {
                        const item = document.createElement('div');
                        item.className = 'passenger-item';
                        item.innerHTML = `<div class="passenger-info"><div class="passenger-name">${p.pseudo || 'N/A'}</div><div class="passenger-email">${p.mail || 'N/A'}</div></div>`;
                        passengersList.appendChild(item);
                    });
                } else {
                    passengersList.innerHTML = '<p>Aucun passager.</p>';
                }
            })
            .catch(error => {
                handleFetchError(error);
                passengersList.innerHTML = `<p class="error-message">Erreur chargement.</p>`;
            });
        });
    });
}

// btn démarrer/terminer un covoit
function initTripButtons() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    document.querySelectorAll('.trip-start-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Démarrer ce trajet ?')) return;
            const tripId = this.getAttribute('data-trip');
            if (!tripId) return;
            fetch(`/trip/${tripId}/start`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
            .then(handleFetchResponse).then(data => {
                if (data.success) { showNotification('Trajet démarré.', 'success'); window.location.reload(); }
                else { showNotification(data.message || 'Erreur.', 'error'); }
            }).catch(handleFetchError);
        });
    });

    document.querySelectorAll('.trip-end-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Terminer ce trajet ?')) return;
            const tripId = this.getAttribute('data-trip');
            if (!tripId) return;

            btn.disabled = true;

            const tripCard = btn.closest('.trip-card');

            fetch(`/trip/${tripId}/end`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
            .then(handleFetchResponse).then(data => {
                if (data.success) {
                    showNotification(data.message || 'Trajet terminé.', 'success');

                    if (tripCard) {
                        tripCard.style.transition = 'opacity 0.3s ease';
                        tripCard.style.opacity = '0';

                        setTimeout(() => {
                            tripCard.remove();

                            const container = tripCard.closest('.trip-cards');
                            if (container && container.children.length === 0) {
                                //si plus de covoit
                                const widgetContent = container.closest('.widget-content');
                                if (widgetContent) {
                                    const noTripsHtml = `
                                        <div class="no-trips">
                                            <div class="no-trips-icon"><i class="fas fa-car-side"></i></div>
                                            <p>Vous n'avez pas de trajet en cours.</p>
                                        </div>
                                    `;
                                    widgetContent.innerHTML = noTripsHtml;
                                }
                            }
                        }, 300);
                    } else {
                        // Recharger la page si pas de tripCard
                        setTimeout(() => window.location.reload(), 1000);
                    }
                }
                else {
                    showNotification(data.message || 'Erreur.', 'error');
                    btn.disabled = false;
                }
            }).catch(error => {
                handleFetchError(error);
                btn.disabled = false;
            });
        });
    });
}

// Changement de role ////////////////////////////////////////////////////////////////////////////////
function initRoleChange() {
    const roleForm = document.querySelector('form[action*="user/role"]');
    const roleOptions = document.querySelectorAll('.role-option input[type="radio"]');
    const roleSubmitBtn = document.querySelector('.role-submit-btn');
    const roleModal = document.getElementById('roleChangeModal');
    const revertModal = document.getElementById('revertToPassengerModal');
    const confirmRevertBtn = document.querySelector('#revertToPassengerModal .confirm-revert-btn');
    const modalForm = document.getElementById('roleChangeForm');

    if (!roleForm || !roleOptions.length || !roleSubmitBtn || !roleModal || !revertModal || !modalForm || !confirmRevertBtn) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    initModalClose(roleModal);
    initModalClose(revertModal);

    // compteur de caractères
    const prefLibreTextarea = document.getElementById('pref_libre');
    const maxLength = 255;

    if (prefLibreTextarea) {
        const charCounter = document.createElement('div');
        charCounter.className = 'char-counter';
        charCounter.textContent = `${prefLibreTextarea.value.length}/${maxLength}`;
        prefLibreTextarea.parentNode.insertBefore(charCounter, prefLibreTextarea.nextSibling);

        prefLibreTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength}/${maxLength}`;

            // proche de la limite
            if (currentLength > maxLength * 0.8) {
                charCounter.style.color = currentLength > maxLength ? 'red' : 'orange';
            } else {
                charCounter.style.color = '';
            }
        });
    }

    roleOptions.forEach(option => {
        option.addEventListener('change', function() {
            document.querySelectorAll('.role-option').forEach(label => label.classList.remove('selected'));
            if (this.checked) this.closest('.role-option')?.classList.add('selected');
        });
        if (option.checked) option.closest('.role-option')?.classList.add('selected');
    });

    // clic sur le bouton "Changer mon role"
    roleSubmitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedRadio = roleForm.querySelector('input[name="role"]:checked');
        const roleValueElement = document.querySelector('.role-value');
        if (!selectedRadio || !roleValueElement) return;
        const selectedRole = selectedRadio.value;
        const currentRole = roleValueElement.textContent.trim();

        // Si l'utilisateur choisit le même rôle
        if (selectedRole === currentRole) { showNotification('Vous avez déjà ce rôle! Si vous désirez en changer, vous devez au préalable, en choisir un nouveau.', 'info'); return; }

        if (currentRole === 'Passager' && (selectedRole === 'Conducteur' || selectedRole === 'Les deux')) {
            document.getElementById('modal_role').value = selectedRole;
            document.getElementById('driver-form-section').style.display = 'block';
            document.getElementById('vehicle-form-section').style.display = 'block';
            roleModal.classList.add('active');
        } else if ((currentRole === 'Conducteur' || currentRole === 'Les deux') && selectedRole === 'Passager') {
            const resetUrl = confirmRevertBtn.getAttribute('data-reset-url');
            if (!resetUrl) { showNotification("Erreur technique (URL reset manquante).", "error"); return; }
            revertModal.classList.add('active');
        } else {
            sendRoleUpdateSimple(selectedRole, roleForm.action, csrfToken);
        }
    });

    modalForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // longueur du texte
        if (prefLibreTextarea && prefLibreTextarea.value.length > maxLength) {
            showNotification(`Le champ "Autres préférences" ne doit pas dépasser ${maxLength} caractères. Veuillez réduire votre texte de ${prefLibreTextarea.value.length - maxLength} caractères.`, 'error');
            prefLibreTextarea.focus();
            return;
        }

        // Supprime les messages d'erreur
        modalForm.querySelectorAll('.error-message').forEach(el => el.remove());
        modalForm.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Désactive le bouton de soumission
        const submitButton = modalForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        fetch(modalForm.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: new FormData(modalForm) })
        .then(response => {
            if (response.status === 422) {
                return response.json().then(data => {
                    if (data.errors) {
                        // Afficher les erreurs de validation
                        Object.keys(data.errors).forEach(field => {
                            const input = modalForm.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('error');
                                const errorMessage = document.createElement('span');
                                errorMessage.className = 'error-message';
                                errorMessage.textContent = data.errors[field][0];
                                input.parentNode.appendChild(errorMessage);
                                input.focus();
                            }
                        });

                        // Message général d'erreur
                        showNotification('Veuillez corriger les erreurs dans le formulaire.', 'error');
                    } else if (data.message) {
                        showNotification(data.message, 'error');
                    } else {
                        showNotification('Erreur de validation. Veuillez vérifier les informations saisies.', 'error');
                    }

                    if (submitButton) submitButton.disabled = false;
                    throw new Error('Validation failed');
                });
            }
            return handleFetchResponse(response);
        })
        .then(data => {
            if (data.success) {
                showNotification('Rôle mis à jour!', 'success');
                roleModal.classList.remove('active');
                setTimeout(() => window.location.reload(), 1000);
            }
            else {
                showNotification(data.message || 'Erreur.', 'error');
                if (submitButton) submitButton.disabled = false;
            }
        })
        .catch(error => {
            if (error.message !== 'Validation failed') {
                handleFetchError(error);
                if (submitButton) submitButton.disabled = false;
            }
        });
    });

    confirmRevertBtn.addEventListener('click', function() {
        const actionUrl = this.getAttribute('data-reset-url');
        if (!actionUrl) return;
        fetch(actionUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
        .then(handleFetchResponse).then(data => {
            if (data.success) { showNotification('Rôle réinitialisé.', 'success'); revertModal.classList.remove('active'); setTimeout(() => window.location.reload(), 1000); }
            else { showNotification(data.message || 'Erreur.', 'error'); }
        }).catch(handleFetchError);
    });

    function sendRoleUpdateSimple(role, actionUrl, token) {
        const formData = new FormData();
        formData.append('role', role);
        formData.append('_method', 'PUT');
        fetch(actionUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: formData })
        .then(handleFetchResponse).then(data => {
            if (data.success) { showNotification('Rôle mis à jour!', 'success'); setTimeout(() => window.location.reload(), 1000); }
            else { showNotification(data.message || 'Erreur.', 'error'); }
        }).catch(handleFetchError);
    }
}

// btn recharge de crédits
function initCreditRecharge() {
    const rechargeBtn = document.querySelector('.recharge-btn');
    if (rechargeBtn) {
        rechargeBtn.addEventListener('click', () => showNotification('Fonctionnalité de recharge en cours de développement.', 'info'));
    }
}

// confirmations pour les annulations
function initCancellationConfirmation() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const action = form.getAttribute('action');
            if (!action) return;

            let message = 'Êtes-vous sûr ?';
            if (action.includes('/trip/')) message = 'Annuler ce trajet ? Les passagers seront remboursés.';
            else if (action.includes('/reservation/')) message = 'Annuler cette réservation ? Vos crédits seront remboursés.';

            if (!confirm(message)) return;

            const submitButton = form.querySelector('button[type="submit"]');
            const tripCard = form.closest('.trip-card') || form.closest('.covoiturage-card');

            if (submitButton) submitButton.disabled = true;

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'DELETE'
                })
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Annulation réussie.', 'success');

                    if (tripCard) {
                        tripCard.style.opacity = '0';
                        setTimeout(() => {
                            tripCard.remove();

                            const container = document.querySelector('.trip-cards') || document.querySelector('.covoiturage-list');
                            if (container && container.children.length === 0) {

                                const widgetContent = container.closest('.widget-content') || container.closest('.search-section');
                                if (widgetContent) {
                                    const noTripsHtml = `
                                        <div class="no-trips">
                                            <div class="no-trips-icon"><i class="fas fa-car-side"></i></div>
                                            <p>Vous n'avez pas de trajet.</p>
                                            <a href="/trips" class="search-trips-btn">Rechercher un trajet</a>
                                        </div>
                                    `;
                                    widgetContent.innerHTML = noTripsHtml;
                                }
                            }
                        }, 300);
                    } else {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    showNotification(data.message || 'Erreur lors de l\'annulation.', 'error');
                    if (submitButton) submitButton.disabled = false;
                }
            })
            .catch(error => {
                handleFetchError(error);
                if (submitButton) submitButton.disabled = false;
            });
        });
    });
}

// Ouverture et soumission => modale édition du profil
function initProfileEditModal() {
    const editBtn = document.querySelector('.profile-widget .widget-action-btn');
    const modal = document.getElementById('profileEditModal');
    const form = document.getElementById('profileEditForm');
    if (!editBtn || !modal || !form) return;

    initModalClose(modal);
    editBtn.addEventListener('click', (e) => { e.preventDefault(); modal.classList.add('active'); });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const actionUrl = form.action;

        if (!csrfToken || !actionUrl || actionUrl === '#') {
             console.error("CSRF token ou URL d'action manquant/invalide pour la mise à jour du profil.");
             showNotification("Erreur technique lors de la soumission.", "error");
             return;
        }

        // Ajouter ('_method', 'PUT') => Laravel = requête PUT
        formData.append('_method', 'PUT');

        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(handleFetchResponse)
        .then(data => {
            if (data.success) {
                showNotification('Profil mis à jour avec succès!', 'success');
                modal.classList.remove('active');

                // Mise à jour dynamique de l'affichage du profil
                const pseudoDisplay = document.querySelector('.profile-details h3');
                const mailParagraph = document.querySelector('.profile-details p:has(i.fa-envelope)');

                if (pseudoDisplay) {
                    pseudoDisplay.textContent = formData.get('pseudo');
                } else {
                    console.warn("Élément h3 pour pseudo non trouvé pour MAJ affichage.");
                }

                if (mailParagraph) {
                    mailParagraph.innerHTML = `<i class="fas fa-envelope"></i> ${formData.get('mail')}`;
                } else {
                    // Problème lié à has()
                    //:has() =>"sélecteur parent" relativement récent (alors que normalement => parent vers l'enfant)
                    // Alternative si il n'est pas supporté:

                    const mailIcon = document.querySelector('.profile-details p i.fa-envelope');
                    if (mailIcon && mailIcon.parentElement && mailIcon.parentElement.tagName === 'P') {
                         mailIcon.parentElement.innerHTML = `<i class="fas fa-envelope"></i> ${formData.get('mail')}`;
                    } else {
                         console.warn("Élément <p> pour email non trouvé pour MAJ affichage.");
                    }
                }
            } else {
                showNotification(data.message || 'Une erreur est survenue lors de la mise à jour.', 'error');
            }
        })
        .catch(handleFetchError);
    });
}

// Gestion de la photo de profil = ouverture + prévisualisation + upload + suppression
function initProfilePhotoModal() {
    const avatar = document.getElementById('profile-avatar-clickable');
    const modal = document.getElementById('profilePhotoModal');
    const input = document.getElementById('profile-photo-input');
    const preview = document.getElementById('photo-preview');
    const submitBtn = document.getElementById('profile-photo-submit');
    const form = document.getElementById('profilePhotoForm');

    if (!avatar || !modal || !input || !preview || !submitBtn || !form) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    initModalClose(modal);
    avatar.addEventListener('click', () => modal.classList.add('active'));

    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<div class="photo-container" style="position: relative;"><img src="${e.target.result}" alt="Prévisualisation"><button class="delete-photo-btn" data-delete-url="${preview.getAttribute('data-delete-url') || ''}">×</button></div>`;
                attachDeleteButtonListener();
            };
            reader.readAsDataURL(file);
        } else if (!preview.querySelector('img')) {
             preview.innerHTML = '<div class="photo-placeholder"><i class="fas fa-user"></i></div>';
        }
    });

    submitBtn.addEventListener('click', function() {
        if (input.files.length === 0) { showNotification("Sélectionnez une photo.", "info"); return; }
        fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: new FormData(form) })
        .then(handleFetchResponse).then(data => {
            if (data.success && data.photo && data.mime_type) {
                showNotification('Photo mise à jour!', 'success');
                modal.classList.remove('active');
                const imgSrc = `data:${data.mime_type};base64,${data.photo}`;
                const deleteUrl = preview.getAttribute('data-delete-url') || '';
                if (avatar) avatar.innerHTML = `<img src="${imgSrc}" alt="Photo actuelle">`;
                if (preview) {
                    preview.innerHTML = `<div class="photo-container" style="position: relative;"><img src="${imgSrc}" alt="Photo actuelle"><button class="delete-photo-btn" data-delete-url="${deleteUrl}">×</button></div>`;
                    attachDeleteButtonListener();
                }
            } else { showNotification(data.message || 'Erreur upload.', 'error'); }
        }).catch(handleFetchError);
    });

    attachDeleteButtonListener();
}

// modale => préférence conducteur
function initPreferencesEditModal() {
    const editBtn = document.getElementById('edit-preferences-btn');
    const modal = document.getElementById('preferencesEditModal');
    const form = document.getElementById('preferencesEditForm');
    if (!editBtn || !modal || !form) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    initModalClose(modal);
    editBtn.addEventListener('click', () => modal.classList.add('active'));

    const prefLibreTextarea = document.getElementById('modal_pref_libre');
    const maxLength = 255;

    if (prefLibreTextarea) {
        // compteur de caractères
        const charCounter = document.createElement('div');
        charCounter.className = 'char-counter';
        charCounter.textContent = `${prefLibreTextarea.value.length}/${maxLength}`;
        prefLibreTextarea.parentNode.insertBefore(charCounter, prefLibreTextarea.nextSibling);

        prefLibreTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCounter.textContent = `${currentLength}/${maxLength}`;

            // proche de la limite
            if (currentLength > maxLength * 0.8) {
                charCounter.style.color = currentLength > maxLength ? 'red' : 'orange';
            } else {
                charCounter.style.color = '';
            }
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (prefLibreTextarea && prefLibreTextarea.value.length > maxLength) {
            showNotification(`Le champ "Autres préférences" ne doit pas dépasser ${maxLength} caractères. Veuillez réduire votre texte de ${prefLibreTextarea.value.length - maxLength} caractères.`, 'error');
            prefLibreTextarea.focus();
            return;
        }

        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: formData })
        .then(handleFetchResponse).then(data => {
            if (data.success) {
                showNotification('Préférences mises à jour!', 'success');
                modal.classList.remove('active');
                updatePreferencesDisplay(formData.get('pref_smoke'), formData.get('pref_pet'), formData.get('pref_libre'));
            } else { showNotification(data.message || 'Erreur.', 'error'); }
        }).catch(handleFetchError);
    });
}

// Modale => ajout d'un véhicule
function initAddVehicleModal() {
    const addBtn = document.getElementById('add-vehicle-btn');
    const modal = document.getElementById('addVehicleModal');
    const form = document.getElementById('addVehicleForm');
    if (!addBtn || !modal || !form) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    initModalClose(modal);
    addBtn.addEventListener('click', () => {
        form.reset();
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        modal.classList.add('active');
    });

    const marqueInput = form.querySelector('#modal_add_marque');
    const modeleInput = form.querySelector('#modal_add_modele');
    const immatInput = form.querySelector('#modal_add_immat');
    const couleurInput = form.querySelector('#modal_add_couleur');

    // Message d'erreur sous un champ
    function addErrorMessage(input, message) {
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) existingError.remove();

        input.classList.add('error');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = 'red';
        errorDiv.style.fontSize = '0.8rem';
        errorDiv.style.marginTop = '0.25rem';

        const helpText = input.parentNode.querySelector('.form-help-text');
        if (helpText) {
            helpText.parentNode.insertBefore(errorDiv, helpText.nextSibling);
        } else {
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
    }

    // Suppr message d'erreur
    function removeErrorMessage(input) {
        input.classList.remove('error');
        const errorDiv = input.parentNode.querySelector('.error-message');
        if (errorDiv) errorDiv.remove();
    }

    if (marqueInput) {
        marqueInput.addEventListener('input', function() {
            if (this.value.length > 50) {
                addErrorMessage(this, 'La marque ne doit pas dépasser 50 caractères.');
            } else {
                removeErrorMessage(this);
            }
        });
    }

    if (modeleInput) {
        modeleInput.addEventListener('input', function() {
            if (this.value.length > 50) {
                addErrorMessage(this, 'Le modèle ne doit pas dépasser 50 caractères.');
            } else {
                removeErrorMessage(this);
            }
        });
    }

    if (immatInput) {
        immatInput.addEventListener('input', function() {
            if (this.value.length > 20) {
                addErrorMessage(this, 'L\'immatriculation ne doit pas dépasser 20 caractères.');
            } else {
                removeErrorMessage(this);
            }
        });
    }

    if (couleurInput) {
        couleurInput.addEventListener('input', function() {
            if (this.value.length > 30) {
                addErrorMessage(this, 'La couleur ne doit pas dépasser 30 caractères.');
            } else {
                removeErrorMessage(this);
            }
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const hasErrors = form.querySelectorAll('.error').length > 0;
        if (hasErrors) {
            showNotification('Veuillez corriger les erreurs avant de soumettre le formulaire.', 'error');
            return;
        }

        let validationFailed = false;

        if (marqueInput && marqueInput.value.length > 50) {
            addErrorMessage(marqueInput, 'La marque ne doit pas dépasser 50 caractères.');
            validationFailed = true;
        }

        if (modeleInput && modeleInput.value.length > 50) {
            addErrorMessage(modeleInput, 'Le modèle ne doit pas dépasser 50 caractères.');
            validationFailed = true;
        }

        if (immatInput && immatInput.value.length > 20) {
            addErrorMessage(immatInput, 'L\'immatriculation ne doit pas dépasser 20 caractères.');
            validationFailed = true;
        }

        if (couleurInput && couleurInput.value.length > 30) {
            addErrorMessage(couleurInput, 'La couleur ne doit pas dépasser 30 caractères.');
            validationFailed = true;
        }

        if (validationFailed) {
            showNotification('Veuillez corriger les erreurs avant de soumettre le formulaire.', 'error');
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        const formData = new FormData(form);
        console.log('Données du formulaire envoyées:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        })
        .then(response => {
            // erreur serveur=> 500
            if (response.status === 500) {
                if (submitButton) submitButton.disabled = false;
                showNotification('Erreur serveur: Problème lors de l\'ajout du véhicule. Vérifiez les valeurs saisies, en particulier le type d\'energie.', 'error');
                return { success: false };
            }
            // erreurs 422 => validation
            else if (response.status === 422) {
                return response.json().then(data => {
                    if (submitButton) submitButton.disabled = false;

                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                addErrorMessage(input, data.errors[field][0]);
                            }
                        });

                        const firstErrorKey = Object.keys(data.errors)[0];
                        if (firstErrorKey && data.errors[firstErrorKey][0]) {
                            showNotification(data.errors[firstErrorKey][0], 'error');
                        } else {
                            showNotification('Veuillez corriger les erreurs dans le formulaire.', 'error');
                        }
                    } else if (data.message) {
                        showNotification(data.message, 'error');
                    } else {
                        showNotification('Une erreur est survenue lors de la validation du formulaire.', 'error');
                    }

                    return { success: false };
                });
            }
            return handleFetchResponse(response);
        })
        .then(data => {
            if (data.success && data.vehicle) {
                showNotification('Véhicule ajouté!', 'success');
                modal.classList.remove('active');
                const listDiv = document.querySelector('.vehicles-widget .vehicles-list');
                const noVehiclesDiv = document.querySelector('.vehicles-widget .no-vehicles');
                if (listDiv) {
                    if (noVehiclesDiv) noVehiclesDiv.style.display = 'none';
                    const newCard = createVehicleCard(data.vehicle);
                    listDiv.appendChild(newCard);
                    attachVehicleCardListeners(newCard);

                    // Mise à jour du menu déroulant pour les véhicules => formulaire de création de trajet
                    updateVehicleSelectOptions(data.vehicle, false);

                    // Problème: je ne pouvais pas ajouter plus d'une voiture sans devoir recharger la page manuellement...
                    form.reset();
                    form.querySelectorAll('.error-message').forEach(el => el.remove());
                    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

                    // Réactiver le bouton de soumission
                    if (submitButton) submitButton.disabled = false;
                } else {
                    window.location.reload();
                }
            } else if (data.success === false) {

            } else {
                if (submitButton) submitButton.disabled = false;
                showNotification(data.message || 'Erreur lors de l\'ajout du véhicule.', 'error');
            }
        }).catch(error => {
            if (submitButton) submitButton.disabled = false;
            handleFetchError(error);
        });
    });
}

// Modale => edit vehicle existant
function initEditVehicleModal() {
    const modal = document.getElementById('editVehicleModal');
    const form = document.getElementById('editVehicleForm');
    if (!modal || !form) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    initModalClose(modal);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: new FormData(form) })
        .then(handleFetchResponse).then(data => {
            if (data.success) {
                showNotification('Véhicule mis à jour!', 'success');
                modal.classList.remove('active');
                const immat = form.action.substring(form.action.lastIndexOf('/') + 1);
                const updateData = data.vehicle || Object.fromEntries(new FormData(form).entries());
                updateVehicleCardDisplay(immat, updateData);
            } else { showNotification(data.message || 'Erreur MAJ.', 'error'); }
        }).catch(handleFetchError);
    });
}

//Problème, si on entre un véhicule, on ne peut pas directement le sélectionner dans le menu déroulant du formulaire de création de covoit
// Solution => Mise à jour
function updateVehicleSelectOptions(vehicleData, showNotifications = true) {
    const vehicleSelects = document.querySelectorAll('select[name="immat"]');
    let optionsAdded = 0;

    vehicleSelects.forEach(select => {
        // Check si l'option existe déjà
        const existingOption = Array.from(select.options).find(option => option.value === vehicleData.immat);

        if (!existingOption) {
            // Créer une nouvelle option
            const option = document.createElement('option');
            option.value = vehicleData.immat;
            option.setAttribute('data-seats', vehicleData.n_place);
            option.textContent = `${vehicleData.brand} ${vehicleData.model} (${vehicleData.immat})`;

            // Ajout de l'option au menu déroulant
            select.appendChild(option);

            select.value = vehicleData.immat;

            // Mise à jour des places disponibles
            const changeEvent = new Event('change');
            select.dispatchEvent(changeEvent);

            optionsAdded++;
        }
    });

    if (showNotifications && optionsAdded > 0) {
        showNotification('Nouveau véhicule ajouté et sélectionné dans le formulaire', 'success');
    }
}

// Ecouteurs des modales véhicules => édition et suppression
function initVehicleModals() {
    const lastVehicleModal = document.getElementById('lastVehicleDeleteModal');
    const confirmDeleteLastBtn = document.getElementById('confirm-delete-last-vehicle');

    if (lastVehicleModal) initModalClose(lastVehicleModal);

    document.querySelectorAll('.vehicle-card').forEach(card => {
        attachVehicleCardListeners(card);
    });

    // Logique de confirmation dans la modale du dernier véhicule
    if (confirmDeleteLastBtn) {
        confirmDeleteLastBtn.addEventListener('click', function() {
            const immatFromGlobal = window.immatToDeleteForModal;
            if (immatFromGlobal) {
                console.log(`Confirmation suppression dernier véhicule (via global): ${immatFromGlobal}`);
                deleteVehicleAndResetRole(immatFromGlobal);
            } else { console.error("Immat globale manquante."); }
            window.immatToDeleteForModal = null;
        });
    }
}


// création d'un covoit
function initCreateTripModal() {
    const modal = document.getElementById('createTripModal');
    const form = document.getElementById('createTripForm');
    const openButtons = document.querySelectorAll('.open-create-trip-modal');

    if (!modal || !form || !openButtons.length) return;

    initModalClose(modal);

    const departureDate = form.querySelector('#departure_date');
    const departureTime = form.querySelector('#departure_time');
    const arrivalDate = form.querySelector('#arrival_date');
    const arrivalTime = form.querySelector('#arrival_time');
    const maxTravelTime = form.querySelector('#max_travel_time');
    const vehicleSelect = form.querySelector('#vehicle_select');
    const nTicketsSelect = form.querySelector('#n_tickets');

    // Mise à jour des places disponibles
    function updateAvailableSeats() {
        if (!vehicleSelect || !nTicketsSelect) return;

        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        if (!selectedOption) return;

        const vehicleId = selectedOption.value;
        const vehicleSeats = parseInt(selectedOption.getAttribute('data-seats') || '0');

        if (isNaN(vehicleSeats) || vehicleSeats <= 1) return;

        // n place dispo = n place - 1 (pour le conducteur)
        const maxAvailableSeats = Math.max(1, vehicleSeats - 1);

        nTicketsSelect.innerHTML = '';

        for (let i = 1; i <= maxAvailableSeats; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            nTicketsSelect.appendChild(option);
        }
    }

    // date et heure de départ valide?
    function validateDepartureDateTime() {
        if (!departureDate || !departureTime) return true;

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const selectedDate = new Date(departureDate.value);

        // La date de départ ne peut pas être dans le passé
        if (selectedDate < today) {
            showNotification('La date de départ ne peut pas être dans le passé.', 'error');
            departureDate.focus();
            return false;
        }

        // Si la date de départ est aujourd'hui => au moins 4 heures après l'heure actuelle
        if (isSameDay(selectedDate, today)) {
            const [hours, minutes] = departureTime.value.split(':').map(Number);
            const departureTimeMinutes = hours * 60 + minutes;
            const nowMinutes = now.getHours() * 60 + now.getMinutes();

            console.log('Validation départ:', {
                'Date départ': departureDate.value,
                'Heure départ': departureTime.value,
                'Minutes départ': departureTimeMinutes,
                'Heure actuelle': `${now.getHours()}:${now.getMinutes()}`,
                'Minutes actuelles': nowMinutes,
                'Différence': departureTimeMinutes - nowMinutes,
                'Minimum requis': 240
            });

            if (departureTimeMinutes < nowMinutes + 240) { // 240 minutes = 4 heures
                showNotification('Vous ne pouvez pas créer ce covoiturage car vous ne respectez pas les délais entre l\'heure de la création du covoiturage et l\'heure du départ. Un délai minimum de 4 heures est requis.', 'error');
                departureTime.focus();
                return false;
            }
        }

        return true;
    }

    // 2 dates => le même jour?
    function isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }

    // Vérifier si la date et l'heure d'arrivée sont OK!
    function validateArrivalDateTime() {
        if (!departureDate || !departureTime || !arrivalDate || !arrivalTime) return true;

        const departureDateTime = new Date(departureDate.value + 'T' + departureTime.value);
        const arrivalDateTime = new Date(arrivalDate.value + 'T' + arrivalTime.value);

        // La date et l\'heure d\'arrivée doivent être postérieures à la date et l\'heure de départ.
        if (arrivalDateTime <= departureDateTime) {
            showNotification('La date et l\'heure d\'arrivée doivent être postérieures à la date et l\'heure de départ.', 'error');
            arrivalDate.focus();
            return false;
        }

        return true;
    }

    // Cohérance avec la durée maximale
    function validateMaxTravelTime() {
        if (!departureDate || !departureTime || !arrivalDate || !arrivalTime || !maxTravelTime) return true;

        const departureDateTime = new Date(departureDate.value + 'T' + departureTime.value);
        const arrivalDateTime = new Date(arrivalDate.value + 'T' + arrivalTime.value);

        // Covoit en minutes
        const estimatedDurationMinutes = (arrivalDateTime - departureDateTime) / (1000 * 60);

        // Durée max en minutes
        const [maxHours, maxMinutes] = maxTravelTime.value.split(':').map(Number);
        const maxDurationMinutes = maxHours * 60 + maxMinutes;

        // Vérif = durée max > durée estimée
        if (maxDurationMinutes <= estimatedDurationMinutes) {
            showNotification('La durée maximale du voyage doit être supérieure à la durée estimée.', 'error');
            maxTravelTime.focus();
            return false;
        }

        // comparer la durée max avec date de départ et d'arrivée
        const maxArrivalDateTime = new Date(departureDateTime.getTime() + maxDurationMinutes * 60 * 1000);
        const maxArrivalDate = new Date(maxArrivalDateTime.getFullYear(), maxArrivalDateTime.getMonth(), maxArrivalDateTime.getDate());
        const selectedArrivalDate = new Date(arrivalDate.value);

        // Si la date d\'arrivée est trop éloignée par rapport à la durée maximale du voyage (> 1 jour) => erreur
        if (selectedArrivalDate > new Date(maxArrivalDate.getTime() + 24 * 60 * 60 * 1000)) {
            showNotification('La date d\'arrivée est trop éloignée par rapport à la durée maximale du voyage.', 'error');
            arrivalDate.focus();
            return false;
        }

        return true;
    }

    // Ecouteurs d'événements pour ouvrir la modale
    openButtons.forEach(button => {
        button.addEventListener('click', () => {
            form.reset();

            // Mise à jour des chamsp de date avec la date du jour + la définir comme la date minimale (=empêcher la sélect de dates passées)
            const today = new Date().toISOString().split('T')[0];
            if (departureDate) {
                departureDate.value = today;
                departureDate.min = today;
            }
            if (arrivalDate) {

                arrivalDate.disabled = false;
                arrivalDate.value = today;
                arrivalDate.min = today; // Empeche la sélection de dates passées
            }

            // Mise à jour des place dispo
            updateAvailableSeats();

            modal.classList.add('active');
        });
    });

    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', updateAvailableSeats);

    }

    // si arrival_date actif => définir la date minimale à la date de départ (forcer cela)
    if (departureDate) {
        departureDate.addEventListener('change', function() {
            validateDepartureDateTime();

            if (arrivalDate && this.value) {
                arrivalDate.disabled = false;
                arrivalDate.min = this.value;

                if (arrivalDate.value < this.value) {
                    arrivalDate.value = this.value;
                }
            }
        });
    }

    if (departureTime) departureTime.addEventListener('change', validateDepartureDateTime);
    if (arrivalDate) arrivalDate.addEventListener('change', () => { validateArrivalDateTime(); validateMaxTravelTime(); });
    if (arrivalTime) arrivalTime.addEventListener('change', () => { validateArrivalDateTime(); validateMaxTravelTime(); });
    if (maxTravelTime) maxTravelTime.addEventListener('change', validateMaxTravelTime);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateDepartureDateTime() || !validateArrivalDateTime() || !validateMaxTravelTime()) {
            return;
        }

        // Soumission du formulaire
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Erreur H:i => Pour les empêcher, s'assurer que les champs time sont au bon format
        const timeFields = ['departure_time', 'arrival_time', 'max_travel_time'];
        timeFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && field.value) {
                try {
                    // Convertir en objet Date pour avoir un format unique
                    const today = new Date();
                    const [hours, minutes] = field.value.split(':');
                    today.setHours(parseInt(hours, 10));
                    today.setMinutes(parseInt(minutes, 10));
                    today.setSeconds(0);

                    // format H:i (heures:minutes)
                    const formattedHours = today.getHours().toString().padStart(2, '0');
                    const formattedMinutes = today.getMinutes().toString().padStart(2, '0');
                    const formattedTime = `${formattedHours}:${formattedMinutes}`;

                    // Remplacer dans FormData
                    formData.set(fieldName, formattedTime);

                    console.log(`Champ ${fieldName} formaté : ${field.value} -> ${formattedTime}`);
                } catch (e) {
                    console.error(`Erreur lors du formatage du champ ${fieldName}:`, e);
                }
            }
        });

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(handleFetchResponse)
        .then(data => {
            if (data.success) {
                showNotification('Trajet créé avec succès!', 'success');
                modal.classList.remove('active');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message || 'Erreur lors de la création du trajet.', 'error');
            }
        })
        .catch(handleFetchError);
    });

    updateAvailableSeats();
}

// Modale=> Modif du covoit
function initEditTripModal() {
    const modal = document.getElementById('editTripModal');
    const form = document.getElementById('editTripForm');
    const editButtons = document.querySelectorAll('.trip-edit-btn');

    if (!modal || !form || !editButtons.length) return;

    initModalClose(modal);

    const departureDate = form.querySelector('#edit_departure_date');
    const departureTime = form.querySelector('#edit_departure_time');
    const arrivalDate = form.querySelector('#edit_arrival_date');
    const arrivalTime = form.querySelector('#edit_arrival_time');
    const maxTravelTime = form.querySelector('#edit_max_travel_time');
    const vehicleSelect = form.querySelector('#edit_immat');
    const nTicketsSelect = form.querySelector('#edit_n_tickets');

    // places dispo en fonction du vehicle
    function updateAvailableSeats() {
        if (!vehicleSelect || !nTicketsSelect) return;

        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        if (!selectedOption) return;

        const vehicleSeats = parseInt(selectedOption.getAttribute('data-seats') || '0');

        if (isNaN(vehicleSeats) || vehicleSeats <= 1) return;

        const maxAvailableSeats = Math.max(1, vehicleSeats - 1);

        nTicketsSelect.innerHTML = '';

        for (let i = 1; i <= maxAvailableSeats; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            nTicketsSelect.appendChild(option);
        }
    }

    function validateDepartureDateTime() {
        if (!departureDate || !departureTime) return true;

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const selectedDate = new Date(departureDate.value);

        // Check date de depart n'est pas dans le passé
        if (selectedDate < today) {
            showNotification('La date de départ ne peut pas être dans le passé.', 'error');
            departureDate.focus();
            return false;
        }

        // condition si la date de depart = aujourd'hui => + 4 heures que l'heure actuel
        if (selectedDate.getTime() === today.getTime()) {
            const [hours, minutes] = departureTime.value.split(':').map(Number);
            const departureTimeMinutes = hours * 60 + minutes;
            const nowMinutes = now.getHours() * 60 + now.getMinutes();

            if (departureTimeMinutes < nowMinutes + 240) {
                showNotification('Pour un départ aujourd\'hui, l\'heure de départ doit être au moins 4 heures après l\'heure actuelle.', 'error');
                departureTime.focus();
                return false;
            }
        }

        return true;
    }

    function validateArrivalDateTime() {
        if (!departureDate || !departureTime || !arrivalDate || !arrivalTime) return true;

        const departureDateTime = new Date(departureDate.value + 'T' + departureTime.value);
        const arrivalDateTime = new Date(arrivalDate.value + 'T' + arrivalTime.value);

        // Check date et heure d'arrivée => date et heure de départ
        if (arrivalDateTime <= departureDateTime) {
            showNotification('La date et l\'heure d\'arrivée doivent être postérieures à la date et l\'heure de départ.', 'error');
            arrivalDate.focus();
            return false;
        }

        return true;
    }

    // Check = durée max
    function validateMaxTravelTime() {
        if (!departureDate || !departureTime || !arrivalDate || !arrivalTime || !maxTravelTime) return true;

        const departureDateTime = new Date(departureDate.value + 'T' + departureTime.value);
        const arrivalDateTime = new Date(arrivalDate.value + 'T' + arrivalTime.value);

        // Durée estimée en minutes
        const estimatedDurationMinutes = (arrivalDateTime - departureDateTime) / (1000 * 60);

        // Durée max en minutes
        const [maxHours, maxMinutes] = maxTravelTime.value.split(':').map(Number);
        const maxDurationMinutes = maxHours * 60 + maxMinutes;

        // Durée maximale > durée estimée
        if (maxDurationMinutes <= estimatedDurationMinutes) {
            showNotification('La durée maximale du voyage doit être supérieure à la durée estimée.', 'error');
            maxTravelTime.focus();
            return false;
        }

        // Check durée max = cohérente avec dates de départ et d'arrivée
        const maxArrivalDateTime = new Date(departureDateTime.getTime() + maxDurationMinutes * 60 * 1000);
        const maxArrivalDate = new Date(maxArrivalDateTime.getFullYear(), maxArrivalDateTime.getMonth(), maxArrivalDateTime.getDate());
        const selectedArrivalDate = new Date(arrivalDate.value);

        // Si la date d\'arrivée est trop éloignée par rapport à la durée maximale du voyage (> 1 jour) => erreur
        if (selectedArrivalDate > new Date(maxArrivalDate.getTime() + 24 * 60 * 60 * 1000)) {
            showNotification('La date d\'arrivée est trop éloignée par rapport à la durée maximale du voyage.', 'error');
            arrivalDate.focus();
            return false;
        }

        return true;
    }

    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tripId = button.getAttribute('data-trip');
            if (!tripId) return;

            form.action = `/trip/${tripId}`;

            // #id du trajet
            const covoitIdInput = form.querySelector('#edit_covoit_id');
            if (covoitIdInput) covoitIdInput.value = tripId;

            fetch(`/trip/${tripId}/edit`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    const trip = data.trip;

                    form.querySelector('#edit_departure_address').value = trip.departure_address;
                    form.querySelector('#edit_add_dep_address').value = trip.add_dep_address || '';
                    form.querySelector('#edit_postal_code_dep').value = trip.postal_code_dep;
                    form.querySelector('#edit_city_dep').value = trip.city_dep;

                    form.querySelector('#edit_arrival_address').value = trip.arrival_address;
                    form.querySelector('#edit_add_arr_address').value = trip.add_arr_address || '';
                    form.querySelector('#edit_postal_code_arr').value = trip.postal_code_arr;
                    form.querySelector('#edit_city_arr').value = trip.city_arr;

                    // date minimale (aujourd'hui) pour les champs de date
                    const today = new Date().toISOString().split('T')[0];
                    const departureDateField = form.querySelector('#edit_departure_date');
                    const arrivalDateField = form.querySelector('#edit_arrival_date');

                    if (departureDateField) {
                        departureDateField.min = today; // sélection de dates passées IMPOSSIBLE
                        departureDateField.value = trip.departure_date;

                        // Mise à jour de arrivalDateField
                        departureDateField.addEventListener('change', function() {
                            if (arrivalDateField && this.value) {
                                arrivalDateField.disabled = false;
                                arrivalDateField.min = this.value; // Définir la date minimale à la date de départ

                                // forcer la mise à jour
                                if (arrivalDateField.value < this.value) {
                                    arrivalDateField.value = this.value;
                                }
                            }
                        });
                    }

                    form.querySelector('#edit_departure_time').value = trip.departure_time;

                    if (arrivalDateField) {
                        // date minimale = date de départ
                        const minDate = trip.departure_date || today;
                        arrivalDateField.min = minDate; // Sélection de dates passées IMPOSSIBLE
                        arrivalDateField.value = trip.arrival_date;
                    }

                    form.querySelector('#edit_arrival_time').value = trip.arrival_time;
                    form.querySelector('#edit_max_travel_time').value = trip.max_travel_time;

                    const vehicleSelect = form.querySelector('#edit_immat');
                    if (vehicleSelect) {
                        for (let i = 0; i < vehicleSelect.options.length; i++) {
                            if (vehicleSelect.options[i].value === trip.immat) {
                                vehicleSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    updateAvailableSeats();

                    const nTicketsSelect = form.querySelector('#edit_n_tickets');
                    if (nTicketsSelect) {
                        for (let i = 0; i < nTicketsSelect.options.length; i++) {
                            if (parseInt(nTicketsSelect.options[i].value) === trip.n_tickets) {
                                nTicketsSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    form.querySelector('#edit_price').value = trip.price;

                    modal.classList.add('active');
                } else {
                    showNotification(data.message || 'Erreur lors de la récupération des informations du trajet.', 'error');
                }
            })
            .catch(handleFetchError);
        });
    });

    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', updateAvailableSeats);
    }

    // Ecouteurs d'événements pour valider les champs
    if (departureDate) departureDate.addEventListener('change', validateDepartureDateTime);
    if (departureTime) departureTime.addEventListener('change', validateDepartureDateTime);
    if (arrivalDate) arrivalDate.addEventListener('change', () => { validateArrivalDateTime(); validateMaxTravelTime(); });
    if (arrivalTime) arrivalTime.addEventListener('change', () => { validateArrivalDateTime(); validateMaxTravelTime(); });
    if (maxTravelTime) maxTravelTime.addEventListener('change', validateMaxTravelTime);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateDepartureDateTime() || !validateArrivalDateTime() || !validateMaxTravelTime()) {
            return;
        }

        const requiredFields = form.querySelectorAll('[required]');
        let allFieldsValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFieldsValid = false;
                field.classList.add('error');
                showNotification(`Le champ ${field.previousElementSibling?.textContent || 'requis'} est obligatoire.`, 'error');
            } else {
                field.classList.remove('error');
            }
        });

        if (!allFieldsValid) {
            return;
        }

        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        // Les champs time = format H:i
        const timeFields = ['departure_time', 'arrival_time', 'max_travel_time'];
        timeFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && field.value) {
                // Extraire les heures et minutes, pour les convertir en objet Date
                try {
                    const today = new Date();
                    const [hours, minutes] = field.value.split(':');
                    today.setHours(parseInt(hours, 10));
                    today.setMinutes(parseInt(minutes, 10));
                    today.setSeconds(0);

                    // Format H:i
                    const formattedHours = today.getHours().toString().padStart(2, '0');
                    const formattedMinutes = today.getMinutes().toString().padStart(2, '0');
                    const formattedTime = `${formattedHours}:${formattedMinutes}`;

                    formData.set(fieldName, formattedTime);

                    console.log(`Champ ${fieldName} formaté : ${field.value} -> ${formattedTime}`);
                } catch (e) {
                    console.error(`Erreur lors du formatage du champ ${fieldName}:`, e);
                }
            }
        });

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            // Erreurs 422 = validation
            if (response.status === 422) {
                return response.json().then(data => {
                    let errorMessage = 'Erreur de validation';

                    if (data.errors) {
                        const firstErrorKey = Object.keys(data.errors)[0];
                        if (firstErrorKey && data.errors[firstErrorKey][0]) {
                            errorMessage = data.errors[firstErrorKey][0];
                        }
                    } else if (data.message) {
                        errorMessage = data.message;
                    }

                    throw new Error(errorMessage);
                });
            }
            return handleFetchResponse(response);
        })
        .then(data => {
            if (data.success) {
                showNotification('Trajet modifié avec succès!', 'success');
                modal.classList.remove('active');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message || 'Erreur lors de la modification du trajet.', 'error');
                if (submitButton) submitButton.disabled = false;
            }
        })
        .catch(error => {
            handleFetchError(error);
            if (submitButton) submitButton.disabled = false;
        });
    });
}

// Suppression de vehicle lié à au moins un covoit => initialisation de la modale pour confirmation
function initVehicleWithTripsModal() {
    const modal = document.getElementById('vehicleWithTripsModal');
    if (modal) {
        initModalClose(modal);
        console.log("Modale de confirmation pour la suppression d'un véhicule lié à des covoiturages initialisée.");
    }
}

// Modale pour la confirmation de la suppr d'un véhicule sans covoit
function showSimpleVehicleDeleteModal(immat, vehicleCard) {
    const modal = document.getElementById('simpleVehicleDeleteModal');
    const confirmBtn = modal.querySelector('.confirm-simple-delete-btn');

    if (!modal || !confirmBtn) {
        console.error('Impossible de trouver les éléments de la modale simple');
        return;
    }

    console.log('Affichage de la modale de confirmation simple pour la suppression d\'un véhicule');

    confirmBtn.onclick = () => {
        modal.classList.remove('active');
        deleteVehicleSimple(immat, vehicleCard);
    };

    modal.classList.add('active');

    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.onclick = () => {
            modal.classList.remove('active');
        };
    });
}


// Initialisation de la modale (=> suppr d'un véhicule sans covoit)
function initSimpleVehicleDeleteModal() {
    const modal = document.getElementById('simpleVehicleDeleteModal');
    if (modal) {
        initModalClose(modal);
        console.log("Modale de confirmation simple pour la suppression d'un véhicule initialisée.");
    }
}

//Initialisation Principale /////////////////////////////////////////////////////////////////////////////////////////////////////
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation du dashboard...");

    initHistoryTabs();
    initPassengersModal();
    initTripButtons();
    initRoleChange();
    initCreditRecharge();
    initCancellationConfirmation();
    initProfileEditModal();
    initProfilePhotoModal();

    initPreferencesEditModal();
    initAddVehicleModal();
    initEditVehicleModal();
    initVehicleModals();
    initCreateTripModal();
    initEditTripModal();
    initVehicleWithTripsModal();
    initSimpleVehicleDeleteModal();

    console.log("Dashboard initialisé.");
});



