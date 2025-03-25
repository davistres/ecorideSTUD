document.addEventListener("DOMContentLoaded", async function () {
    // liens de covoit
    const covoiturageLinks = document.querySelectorAll(".covoiturage-link");

    // Masquer les liens => chargement de la page
    covoiturageLinks.forEach(link => {
        link.classList.add("hidden");
    });

    async function checkCovoiturageSession() {
        try {
            const response = await fetch("/check-session");

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.hasCovoiturage) {
                covoiturageLinks.forEach(link => {
                    link.classList.remove("hidden");
                });
            } else {
                covoiturageLinks.forEach(link => {
                    link.classList.add("hidden");
                });
            }
        } catch (error) {
            console.error("Erreur lors de la vérification de la session :", error);
            covoiturageLinks.forEach(link => link.classList.add("hidden"));
        }
    }

    // Vérif au chargement
    await checkCovoiturageSession();

    const searchForm = document.querySelector(".search-form");
    if (searchForm) {
        searchForm.addEventListener("submit", async function() {
            // A faire => logique pour rafrachir la page
        });
    }

    // Vérif de session => changement de page
    window.addEventListener("pageshow", checkCovoiturageSession);
});
