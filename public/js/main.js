document.addEventListener("DOMContentLoaded", function() {
    console.log("Script JS chargé avec succès !");

    initTooltips();
    setupDateInputs();
    applyCardAnimations();
    detectBrowserCompatibility();
});


// Notification
function showNotification(message, type = 'info') {
    if (!message) return;

    let notificationContainer = document.querySelector('.notification-container');

    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-message">${message}</div>
        <button class="notification-close">×</button>
    `;

    notificationContainer.appendChild(notification);

    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(function() {
                notification.remove();
            }, 300);
        });
    }

    setTimeout(function() {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }
    }, 5000);
}



// infobulles => attribut data-tooltip
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');

    if (!tooltipElements || !tooltipElements.length) return;

    tooltipElements.forEach(function(element) {
        element.addEventListener('mouseenter', function() {

            const tooltipText = this.getAttribute('data-tooltip');
            if (!tooltipText) return;

            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;

            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            const top = rect.top - tooltip.offsetHeight - 5;
            const left = rect.left + (rect.width - tooltip.offsetWidth) / 2;

            tooltip.style.top = `${top + window.scrollY}px`;
            tooltip.style.left = `${left}px`;
            tooltip.style.opacity = '1';

            this.tooltip = tooltip;
        });

        element.addEventListener('mouseleave', function() {
            if (this.tooltip) {
                this.tooltip.remove();
                this.tooltip = null;
            }
        });
    });
}

//champs de date => empêcher la sélection des dates passées
function setupDateInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]');

    if (!dateInputs || !dateInputs.length) return;

    const today = new Date().toISOString().split('T')[0];

    dateInputs.forEach(function(input) {
        if (input.classList.contains('future-only') || input.hasAttribute('data-future-only')) {
            input.setAttribute('min', today);
        }
    });
}

// Animations
function applyCardAnimations() {
    const animatedElements = document.querySelectorAll('.card-animated, .dashboard-widget');

    if (!animatedElements || !animatedElements.length) return;

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                setTimeout(function() {
                    entry.target.classList.add('card-visible');
                }, 100);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    animatedElements.forEach(function(element) {
        observer.observe(element);
    });
}

// compatibilité du navigateur + avertissement si nécessaire
function detectBrowserCompatibility() {
    const isModernBrowser = 'fetch' in window && 'Promise' in window && 'IntersectionObserver' in window;

    if (!isModernBrowser) {
        console.warn("Navigateur potentiellement incompatible détecté. Certaines fonctionnalités peuvent ne pas fonctionner correctement.");

        const compatWarning = document.createElement('div');
        compatWarning.className = 'browser-compat-warning';
        compatWarning.innerHTML = `
            <p><strong>Navigateur potentiellement incompatible</strong></p>
            <p>Pour une meilleure expérience, veuillez utiliser une version récente de Chrome, Firefox, Edge ou Safari.</p>
            <button class="close-warning">×</button>
        `;

        document.body.appendChild(compatWarning);

        const closeBtn = compatWarning.querySelector('.close-warning');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                compatWarning.remove();
            });
        }
    }
}
