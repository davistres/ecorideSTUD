document.addEventListener("DOMContentLoaded", function() {
    // Photo de profil
    function getUserProfilePhoto() {
        // L'utilisateur est connecté? Il a une photo de profil?
        const profileAvatar = document.querySelector('.profile-avatar');
        if (!profileAvatar) return null;

        const userPseudo = document.querySelector('.profile-details h3')?.textContent.trim() || '';
        if (!userPseudo) return null;

        const profileImg = profileAvatar.querySelector('img');
        if (profileImg) {
            return {
                src: profileImg.src,
                alt: profileImg.alt || 'Photo de profil',
                pseudo: userPseudo
            };
        }

        return null;
    }

    // MAJ =>photo dans covoiturage-card
    function updateDriverPhotos() {
        const userPhoto = getUserProfilePhoto();
        if (!userPhoto?.pseudo) return;

        const covoiturageCards = document.querySelectorAll('.covoiturage-card');
        if (!covoiturageCards.length) return;

        console.log('Recherche des cartes de covoiturage pour le conducteur:', userPhoto.pseudo);

        covoiturageCards.forEach(card => {
            // =>pseudo du conducteur
            const driverName = card.querySelector('.driver-info h3')?.textContent.trim();
            if (!driverName) return;

            console.log('Carte de covoiturage pour le conducteur:', driverName);

            // Si le conducteur est connecté
            if (driverName.toLowerCase() === userPhoto.pseudo.toLowerCase()) {
                console.log('Correspondance trouvée pour:', driverName);

                // Récupérer le placeholder ou l'image
                const photoPlaceholder = card.querySelector('.driver-photo.photo-placeholder');

                if (photoPlaceholder) {
                    console.log('Remplacement du placeholder par la photo de profil');

                    // Créer une image
                    const img = document.createElement('img');
                    img.src = userPhoto.src;
                    img.alt = userPhoto.alt;
                    img.className = 'driver-photo';

                    // Remplacer le placeholder par l'image
                    photoPlaceholder.parentNode.replaceChild(img, photoPlaceholder);
                }
            }
            // on garde le placeholder par défaut
        });
    }

    // La page est complètement chargée?
    function checkPageLoaded() {
        const profileAvatar = document.querySelector('.profile-avatar');
        const covoiturageCards = document.querySelectorAll('.covoiturage-card');

        if (profileAvatar && covoiturageCards.length > 0) {
            console.log('Page chargée, mise à jour des photos');
            updateDriverPhotos();
            return true;
        }
        return false;
    }

    // Exécuter la fonction après le chargement de la page => donc, ça check pour savoir quand elle est complètement chargée
    if (!checkPageLoaded()) {
        setTimeout(checkPageLoaded, 500);
        setTimeout(checkPageLoaded, 1000);
        setTimeout(checkPageLoaded, 2000);
    }

    // Nouvelles cartes de covoiturage en plus?
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;

        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                const hasCovoiturageCards = Array.from(mutation.addedNodes).some(node => {
                    return node.nodeType === 1 && (
                        node.classList?.contains('covoiturage-card') ||
                        node.querySelector?.('.covoiturage-card')
                    );
                });

                if (hasCovoiturageCards) {
                    shouldUpdate = true;
                }
            }
        });

        if (shouldUpdate) {
            console.log('Nouvelles cartes de covoiturage détectées, mise à jour des photos');
            setTimeout(updateDriverPhotos, 300);
        }
    });

    // Modif dans covoiturage-list?
    const covoiturageList = document.querySelector('.covoiturage-list');
    if (covoiturageList) {
        observer.observe(covoiturageList, { childList: true, subtree: true });
    }

    // Modif après une recherche?
    const mainContent = document.querySelector('main') || document.body;
    observer.observe(mainContent, { childList: true, subtree: true });

    // Soumission du formulaire de recherche => addEventListener (écouter les évenement)
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            console.log('Formulaire de recherche soumis, mise à jour programmée');
            setTimeout(updateDriverPhotos, 500);
            setTimeout(updateDriverPhotos, 1000);
            setTimeout(updateDriverPhotos, 2000);
        });
    }

    // Check si y a de nouvelles cartes de covoiturage?
    setInterval(function() {
        const cards = document.querySelectorAll('.covoiturage-card');
        if (cards.length > 0) {
            updateDriverPhotos();
        }
    }, 3000);
});
