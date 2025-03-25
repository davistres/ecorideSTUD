 // Je ne sais pas si je vais m'en servir pour cette version du projet
// Dans une première version du projet, j'ai utilisé OpenStreetMap pour afficher l'itinéraire entre deux villes après une recherche.
// Mais au final, ce n'est pas obligatoire... Sauf pour l'UX... Donc, à voir si j'ai le temps de le faire bien.

/* Si je change d'avis, penser à décommenter aussi le code dans app.blade.php:
<script defer src="{{ asset('js/map.js') }}"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script defer src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
*/

document.addEventListener("DOMContentLoaded", function () {
    /* Désactivé pour le moment

    // Initialisation de la carte
    const mapElement = document.getElementById("map");
    if (mapElement) {
        console.log("Carte détectée, initialisation...");
        const map = L.map("map").setView([46.603354, 1.888334], 6);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors",
        }).addTo(map);

        // Récupérer les coordonnées
        async function getCoordinates(city) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}`);
                const data = await response.json();

                if (!data.length) {
                    throw new Error(`Aucune donnée trouvée pour : ${city}`);
                }

                const { lat, lon } = data[0];
                return [parseFloat(lat), parseFloat(lon)];
            } catch (error) {
                console.error(`Erreur pour ${city}:`, error);
                return null;
            }
        }

        // Récupérer itinéraire
        async function getRoute(start, end) {
            try {
                if (!start || !end) {
                    alert("Veuillez sélectionner des villes valides.");
                    return;
                }

                const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`);
                const data = await response.json();

                if (!data.routes.length) {
                    alert("Aucun itinéraire disponible.");
                    return;
                }

                map.eachLayer((layer) => {
                    if (layer instanceof L.Polyline) {
                        map.removeLayer(layer);
                    }
                });

                const route = L.polyline(data.routes[0].geometry.coordinates.map(([lon, lat]) => [lat, lon]), {
                    color: "blue",
                    weight: 5,
                }).addTo(map);

                map.fitBounds(route.getBounds());
            } catch (error) {
                console.error("Erreur lors du calcul de l'itinéraire :", error);
            }
        }

        // Formulaire de recherche d'itinéraire
        const searchButton = document.getElementById("search");
        if (searchButton) {
            searchButton.addEventListener("click", async (e) => {
                e.preventDefault();
                const departure = document.getElementById("departure").value.trim();
                const arrival = document.getElementById("arrival").value.trim();

                if (!departure || !arrival) {
                    alert("Veuillez saisir les villes de départ et d'arrivée");
                    return;
                }

                const startCoords = await getCoordinates(departure);
                const endCoords = await getCoordinates(arrival);

                if (startCoords && endCoords) {
                    getRoute(startCoords, endCoords);
                } else {
                    console.error("Impossible de tracer l'itinéraire : coordonnées manquantes.");
                }
            });
        }
    }
        */
});
