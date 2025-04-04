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
                 throw new Error(message);
            })
            .catch((parsingError) => {
                console.error("Erreur parsing JSON:", parsingError);
                throw new Error(`Erreur HTTP ${response.status}`);
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
    if (typeof showNotification === 'function') {
        showNotification(error.message || 'Une erreur réseau est survenue.', 'error');
    } else {
        console.error("La fonction showNotification n'est pas définie.");
        alert(error.message || 'Une erreur réseau est survenue.');
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
        editBtn.removeEventListener('click', handleEditVehicleClick);
        editBtn.addEventListener('click', handleEditVehicleClick);
    }
    if (deleteBtn) {
        deleteBtn.removeEventListener('click', handleDeleteVehicleClick);
        deleteBtn.addEventListener('click', handleDeleteVehicleClick);
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

    if (vehicleCount === 1) {
        const lastVehicleModal = document.getElementById('lastVehicleDeleteModal');
        if (lastVehicleModal) {
            window.immatToDeleteForModal = immat;
            console.log(`Stockage immat ${immat} pour confirmation modale.`);
            lastVehicleModal.classList.add('active');
        } else {
            if (confirm("AVERTISSEMENT : Supprimer ce dernier véhicule changera votre rôle en Passager. Continuer ?")) {
                deleteVehicleAndResetRole(immat);
            }
        }
    } else {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ?')) {
            deleteVehicleSimple(immat, vehicleCard);
        }
    }
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

    fetch(actionUrl, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(handleFetchResponse)
    .then(data => {
        if (data.success) {
            showNotification('Véhicule supprimé avec succès.', 'success');
            if (cardElement) cardElement.remove();
            const vehiclesList = document.querySelector('.vehicles-list');
            if (vehiclesList && vehiclesList.children.length === 0) {
                const noVehiclesDiv = document.querySelector('.vehicles-widget .no-vehicles');
                 if (!noVehiclesDiv) {
                      const widgetContent = document.querySelector('.vehicles-widget .widget-content');
                      if(widgetContent) {
                           widgetContent.innerHTML = `
                                <div class="no-vehicles" style="display: block;">
                                     <div class="no-vehicles-icon"><i class="fas fa-car"></i></div>
                                     <p>Vous n'avez plus de véhicule enregistré.</p>
                                </div>`;
                      }
                 } else {
                      noVehiclesDiv.style.display = 'block';
                 }
            }
        } else {
            showNotification(data.message || 'Erreur lors de la suppression.', 'error');
        }
    })
    .catch(handleFetchError);
}

// Suppr du dernier véhicule + réinitialisation du rôle
function deleteVehicleAndResetRole(immat) {
     const actionUrl = `/vehicles/${immat}/reset-role`;
     const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
     if (!csrfToken) return;

     fetch(actionUrl, {
         method: 'DELETE',
         headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
     })
     .then(handleFetchResponse)
     .then(data => {
         if (data.success) {
             showNotification('Véhicule supprimé et rôle mis à jour vers Passager.', 'success');
             window.location.reload();
         } else {
             showNotification(data.message || 'Erreur lors de la suppression/reset.', 'error');
         }
     })
     .catch(handleFetchError);
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
            fetch(`/trip/${tripId}/end`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
            .then(handleFetchResponse).then(data => {
                if (data.success) { showNotification('Trajet terminé.', 'success'); window.location.reload(); }
                else { showNotification(data.message || 'Erreur.', 'error'); }
            }).catch(handleFetchError);
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
        fetch(modalForm.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: new FormData(modalForm) })
        .then(handleFetchResponse).then(data => {
            if (data.success) { showNotification('Rôle mis à jour!', 'success'); roleModal.classList.remove('active'); setTimeout(() => window.location.reload(), 1000); }
            else { showNotification(data.message || 'Erreur.', 'error'); }
        }).catch(handleFetchError);
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
    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const action = form.getAttribute('action');
            let message = 'Êtes-vous sûr ?';
            if (action?.includes('/trip/')) message = 'Annuler ce trajet ? Les passagers seront remboursés.';
            else if (action?.includes('/reservation/')) message = 'Annuler cette réservation ? Vos crédits seront remboursés.';
            if (!confirm(message)) e.preventDefault();
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

    form.addEventListener('submit', function(e) {
        e.preventDefault();
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
    addBtn.addEventListener('click', () => { form.reset(); modal.classList.add('active'); });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: new FormData(form) })
        .then(handleFetchResponse).then(data => {
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
                } else { window.location.reload(); }
            } else { showNotification(data.message || 'Erreur ajout.', 'error'); }
        }).catch(handleFetchError);
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

    console.log("Dashboard initialisé.");
});
