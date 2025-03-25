document.addEventListener('DOMContentLoaded', function() {
    initHistoryTabs();
    initPassengersModal();
    initTripButtons();
    initRoleChange();
    initCreditRecharge();
    initCancellationConfirmation();
    initProfileEditModal();
    initRoleChangeModal();
});

// Onglet historique
function initHistoryTabs() {
    const tabs = document.querySelectorAll('.history-tab');
    if (!tabs || !tabs.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Masque
            const tabContents = document.querySelectorAll('.history-tab-content');
            if (!tabContents || !tabContents.length) return;

            tabContents.forEach(content => content.classList.remove('active'));

            // Affiche
            const tabName = this.getAttribute('data-tab');
            const activeContent = document.getElementById(tabName + '-history');
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
}

// Afficher les passagers
function initPassengersModal() {
    const passengersBtns = document.querySelectorAll('.trip-passengers-btn');
    const passengersModal = document.getElementById('passengersModal');
    if (!passengersBtns || !passengersBtns.length || !passengersModal) return;

    const modalClose = passengersModal.querySelector('.modal-close');

    passengersBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tripId = this.getAttribute('data-trip');
            if (!tripId) return;

            // Obtenir le token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token non trouvé');
                return;
            }

            // Charger les passagers
            fetch(`/trip/${tripId}/passengers`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des passagers');
                }
                return response.json();
            })
            .then(data => {
                const passengersList = passengersModal.querySelector('.passengers-list');
                if (!passengersList) return;

                passengersList.innerHTML = '';

                if (data.passengers && data.passengers.length > 0) {
                    data.passengers.forEach(passenger => {
                        passengersList.innerHTML += `
                            <div class="passenger-item">
                                <div class="passenger-info">
                                    <div class="passenger-name">${passenger.pseudo}</div>
                                    <div class="passenger-email">${passenger.mail}</div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    passengersList.innerHTML = '<p>Aucun passager pour ce trajet.</p>';
                }

                passengersModal.classList.add('active');
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors du chargement des passagers.', 'error');
            });
        });
    });

    if (modalClose) {
        modalClose.addEventListener('click', function() {
            passengersModal.classList.remove('active');
        });
    }

    // Fermer le pop-up
    window.addEventListener('click', function(event) {
        if (event.target === passengersModal) {
            passengersModal.classList.remove('active');
        }
    });
}

// btn démarrer/terminer un covoit
function initTripButtons() {
    const startBtns = document.querySelectorAll('.trip-start-btn');
    const endBtns = document.querySelectorAll('.trip-end-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken || (!startBtns.length && !endBtns.length)) return;

    startBtns.forEach(btn => {
        if (!btn) return;

        btn.addEventListener('click', function() {
            if (!confirm('Êtes-vous sûr de vouloir démarrer ce trajet ?')) return;

            const tripId = this.getAttribute('data-trip');
            if (!tripId) return;

            fetch(`/trip/${tripId}/start`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Trajet démarré avec succès !', 'success');
                    window.location.reload();
                } else {
                    showNotification(data.error || 'Une erreur est survenue lors du démarrage du trajet.', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors du démarrage du trajet.', 'error');
            });
        });
    });

    endBtns.forEach(btn => {
        if (!btn) return;

        btn.addEventListener('click', function() {
            if (!confirm('Êtes-vous sûr de vouloir terminer ce trajet ? Un email sera envoyé aux passagers pour compléter le formulaire de satisfaction.')) return;

            const tripId = this.getAttribute('data-trip');
            if (!tripId) return;

            fetch(`/trip/${tripId}/end`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Trajet terminé avec succès ! Les passagers ont été notifiés pour compléter le formulaire de satisfaction.', 'success');
                    window.location.reload();
                } else {
                    showNotification(data.error || 'Une erreur est survenue lors de la terminaison du trajet.', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors de la terminaison du trajet.', 'error');
            });
        });
    });
}

// changement de rôle
function initRoleChange() {
    console.log('Initialisation des boutons de rôle...');

    const roleForm = document.querySelector('form[action*="user/role"]');
    const roleOptions = document.querySelectorAll('.role-option input[type="radio"]');
    const roleSubmitBtn = document.querySelector('form[action*="user/role"] .role-submit-btn');
    const roleModal = document.getElementById('roleChangeModal');

    if (!roleForm || !roleOptions || !roleOptions.length || !roleSubmitBtn || !roleModal) {
        console.error('Un des éléments de changement de rôle est manquant:', {
            roleForm: !!roleForm,
            roleOptions: !!roleOptions,
            roleSubmitBtn: !!roleSubmitBtn,
            roleModal: !!roleModal
        });
        return;
    }

    console.log('Éléments de changement de rôle trouvés, configuration des écouteurs...');


    roleOptions.forEach(option => {
        if (!option) return;

        option.addEventListener('change', function() {
            const roleLabels = document.querySelectorAll('.role-option');
            if (!roleLabels || !roleLabels.length) return;

            roleLabels.forEach(label => {
                if (label) label.classList.remove('selected');
            });

            const parent = this.closest('.role-option');
            if (parent) parent.classList.add('selected');
        });

        if (option.checked) {
            const parent = option.closest('.role-option');
            if (parent) parent.classList.add('selected');
        }
    });

    // clic sur le bouton "Changer mon role"
    roleSubmitBtn.addEventListener('click', function(e) {
        console.log('Bouton de rôle cliqué');
        e.preventDefault();

        const selectedRadio = roleForm.querySelector('input[type="radio"]:checked');
        const roleValueElement = document.querySelector('.role-value');

        if (!selectedRadio || !roleValueElement) {
            console.error('Radio ou élément de valeur du rôle non trouvé');
            return;
        }

        const selectedRole = selectedRadio.value;
        const currentRole = roleValueElement.textContent.trim();


        console.log('Rôle sélectionné:', selectedRole);
        console.log('Rôle actuel:', currentRole);

        // Si l'utilisateur choisit le même rôle
        if (selectedRole === currentRole) {
            showNotification('Vous avez déjà ce rôle! Si vous désirez en changer, vous devez au préalable, en choisir un nouveau.', 'info');
            console.log('Même rôle sélectionné - notification affichée');
            return;
        }

        // Sinon => pop-up
        if (roleModal) {

            const modalRadios = roleModal.querySelectorAll('input[type="radio"]');
            modalRadios.forEach(radio => {
                radio.checked = radio.value === selectedRole;

                if (radio.checked) {
                    const parent = radio.closest('.role-option');
                    if (parent) parent.classList.add('selected');
                } else {
                    const parent = radio.closest('.role-option');
                    if (parent) parent.classList.remove('selected');
                }
            });

            const driverFormSection = document.getElementById('driver-form-section');
            if (driverFormSection) {
                if (selectedRole === 'Conducteur' || selectedRole === 'Les deux') {
                    driverFormSection.style.display = 'block';
                } else {
                    driverFormSection.style.display = 'none';
                }
            }

        roleModal.classList.add('active');
        console.log('Modal ouverte pour changement de rôle');
        }
    });
}

// btn recharge de crédits
function initCreditRecharge() {
    const rechargeBtn = document.querySelector('.recharge-btn');
    if (!rechargeBtn) return;
rechargeBtn.addEventListener('click', handleRechargeClick);

function handleRechargeClick() {
        showNotification('La fonctionnalité de recharge de crédits est en cours de développement. Merci de votre patience !', 'info');
    }
}

// confirmations pour les annulations
function initCancellationConfirmation() {
    const cancelForms = document.querySelectorAll('.cancel-form');
    if (!cancelForms || !cancelForms.length) return;

    cancelForms.forEach(form => {
        if (!form) return;

        form.addEventListener('submit', function(e) {
            // Vérifier si le formulaire concerne un covoit ou une réservation
            const action = form.getAttribute('action');
            if (!action) return;

            const isTripCancellation = action.includes('trip');

            let message = 'Êtes-vous sûr de vouloir annuler cette réservation ? Vos crédits vous seront remboursés.';

            if (isTripCancellation) {
                message = 'Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront remboursés et notifiés.';
            }

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Affiche une notification
function showNotification(message, type = 'info') {
    if (!message) return;

    let notificationContainer = document.querySelector('.notification-container');

    // Créer le conteneur s'il n'existe pas
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }

    // notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-message">${message}</div>
        <button class="notification-close">&times;</button>
    `;

    notificationContainer.appendChild(notification);

    // fermeture
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(function() {
                notification.remove();
            }, 300);
        });
    }

    // Fermeture après 5 secondes
    setTimeout(function() {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }
    }, 5000);
}


function initProfileEditModal() {
    const editProfileBtn = document.querySelector('.profile-widget .widget-action-btn');
    const profileModal = document.getElementById('profileEditModal');

    if (!editProfileBtn || !profileModal) return;

    // Modifier le lien pour qu'il n'ouvre pas une nouvelle page
    editProfileBtn.setAttribute('href', 'javascript:void(0)');

    // Ouvrir la modale au clic
    editProfileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        profileModal.classList.add('active');
    });

    // Fermer avec le btn X
    const closeBtn = profileModal.querySelector('.modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            profileModal.classList.remove('active');
        });
    }

    // Fermer en cliquant à l'extérieur
    window.addEventListener('click', function(event) {
        if (event.target === profileModal) {
            profileModal.classList.remove('active');
        }
    });

    // formulaire via AJAX
    const form = profileModal.querySelector('#profileEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profil mis à jour avec succès!', 'success');
                    profileModal.classList.remove('active');
                    document.querySelector('.profile-details h3').textContent = formData.get('pseudo');
                    document.querySelector('.profile-details p').innerHTML = '<i class="fas fa-envelope"></i> ' + formData.get('mail');
                } else {
                    showNotification(data.message || 'Une erreur est survenue', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors de la mise à jour du profil', 'error');
            });
        });
    }
}

function initRoleChangeModal() {
    const roleModal = document.getElementById('roleChangeModal');
    if (!roleModal) return;

    const modalRoleOptions = roleModal.querySelectorAll('.role-option input[type="radio"]');
    const driverFormSection = document.getElementById('driver-form-section');

    // Afficher ou masquer le formulaire conducteur selon le rôle choisi
    modalRoleOptions.forEach(option => {
        option.addEventListener('change', function() {
            modalRoleOptions.forEach(opt => {
                opt.closest('.role-option').classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.role-option').classList.add('selected');
            }

            if (driverFormSection) {
                if (this.value === 'Conducteur' || this.value === 'Les deux') {
                    driverFormSection.style.display = 'block';
                } else {
                    driverFormSection.style.display = 'none';
                }
            }
        });
    });

    // Fermer avec X
    const closeBtn = roleModal.querySelector('.modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            roleModal.classList.remove('active');
        });
    }

    // Fermer en cliquant à l'extérieur
    window.addEventListener('click', function(event) {
        if (event.target === roleModal) {
            roleModal.classList.remove('active');
        }
    });

    // Envoyer le formulaire
    const modalForm = roleModal.querySelector('#roleChangeForm');
    if (modalForm) {
        modalForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedRole = modalForm.querySelector('input[name="role"]:checked').value;
            const currentRole = document.querySelector('.role-value').textContent.trim();

            if (selectedRole !== currentRole) {
                if (currentRole === 'Conducteur' || currentRole === 'Les deux') {
                    if (selectedRole === 'Passager') {
                        const confirmation = confirm('Attention ! En passant du rôle de conducteur à passager, tous vos trajets proposés seront annulés et les passagers remboursés. Voulez-vous continuer ?');
                        if (!confirmation) {
                            return false;
                        }

                    }
                }
            }

            const formData = new FormData(modalForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(modalForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le role sans recharger la page
                    document.querySelector('.role-value').textContent = selectedRole;
                    document.querySelector('.role-value').className = 'role-value ' + selectedRole.toLowerCase().replace(' ', '-');

                    showNotification('Votre rôle a été mis à jour avec succès!', 'success');
                    roleModal.classList.remove('active');

                    // nouveaux widgets en fonction du role
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Une erreur est survenue', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors de la mise à jour de votre rôle', 'error');
            });
        });
    }
}
