<!-- Modale détails du covoit -->
<div class="modal" id="tripDetailsModal">
    <div class="modal-content trip-details-modal-content">
        <div class="modal-header">
            <h3>Détails du covoiturage</h3>
            <button class="modal-close">×</button>
        </div>
        <div class="modal-body">
            <div id="modal-loading" class="modal-loading">
                <div class="loading-spinner"></div>
                <p>Chargement des détails...</p>
            </div>
            <div id="modal-content" class="modal-content-details" style="display: none;">
                <div class="trip-details-section">
                    <h4>Informations sur le trajet</h4>

                    <div class="trip-route-container-flex trip-route-container">
                        <div class="trip-details-departure">
                            <div class="trip-details-date">
                                <i class="fas fa-calendar"></i> <span id="modal-departure-date"></span>
                            </div>
                            <div class="departure-time">
                                <i class="fas fa-clock"></i> Départ: <span id="modal-departure-time"></span>
                            </div>
                        </div>
                        <div class="trip-details-arrival">
                            <div class="trip-details-date">
                                <i class="fas fa-calendar"></i> <span id="modal-arrival-date"></span>
                            </div>
                            <div class="arrival-time">
                                <i class="fas fa-clock"></i> Arrivée: <span id="modal-arrival-time"></span>
                            </div>
                        </div>
                    </div>

                    <div class="trip-route-details">
                        <span class="from" id="modal-city-dep"></span>
                        <span class="route-arrow-details">→</span>
                        <span class="to" id="modal-city-arr"></span>
                    </div>
                    <div class="trip-route-container">
                        <div>
                            <div class="trip-addresses">
                                <div class="address-group">
                                    <h5>Adresse de départ</h5>
                                    <p id="modal-departure-address"></p>
                                    <p id="modal-add-dep-address"></p>
                                    <p id="modal-postal-code-dep"></p>
                                </div>
                                <div class="address-group">
                                    <h5>Adresse d'arrivée</h5>
                                    <p id="modal-arrival-address"></p>
                                    <p id="modal-add-arr-address"></p>
                                    <p id="modal-postal-code-arr"></p>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Prix -->
                    <div class="trip-price-container">
                        <h5>Prix</h5>
                        <p><span id="modal-price"></span> crédits par personne</p>
                    </div>

                    <!-- Info supp -->
                    <div>
                        <div class="trip-grid-details">
                            <div class="trip-info-item">
                                <h5>Places disponibles</h5>
                                <p id="modal-n-tickets"></p>
                            </div>

                            <div class="trip-info-item">
                                <h5>Durée maximale</h5>
                                <p id="modal-max-travel-time"></p>
                            </div>

                            <div class="trip-info-item">
                                <h5>Type de trajet</h5>
                                <p id="modal-eco-travel"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- conducteur -->
                <div class="driver-details-section">
                    <h4>Informations sur le conducteur</h4>

                    <div class="driver-profile">
                        <div id="modal-driver-photo" class="driver-photo"></div>
                        <div class="driver-info">
                            <h5 id="modal-driver-pseudo"></h5>
                            <div class="driver-rating">
                                <span class="rating-value" id="modal-driver-rating"></span>
                                <span class="rating-stars" id="modal-driver-stars"></span>
                            </div>
                        </div>
                    </div>

                    <div class="driver-details-container">
                        <div class="details-vehicle">
                            <h5>Véhicule</h5>
                            <div class="vehicle-info">
                                <p><span class="vehicle-label-details">Immatriculation :</span> <span
                                        id="modal-immat"></span></p>
                                <p><span class="vehicle-label-details">Marque :</span> <span id="modal-brand"></span>
                                </p>
                                <p><span class="vehicle-label-details">Modèle :</span> <span id="modal-model"></span>
                                </p>
                                <p><span class="vehicle-label-details">Couleur :</span> <span id="modal-color"></span>
                                </p>
                                <p><span class="vehicle-label-details">Energie :</span> <span id="modal-energie"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Préférences -->
                        <div class="driver-preferences">
                            <h5>Préférences</h5>
                            <div class="preferences-list-details">
                                <div class="preference-item-details">
                                    <span class="preference-label">Fumeur :</span>
                                    <span class="preference-value" id="modal-pref-smoke"></span>
                                </div>
                                <div class="preference-item-details">
                                    <span class="preference-label">Animaux :</span>
                                    <span class="preference-value" id="modal-pref-pet"></span>
                                </div>
                                <div class="preference-item-details" id="modal-pref-libre-container">
                                    <span class="preference-label">Autres préférences :</span>
                                    <span class="preference-value" id="modal-pref-libre"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avis -->
                <div class="reviews-section">
                    <h4>Avis sur le conducteur</h4>
                    <div class="reviews-container">
                        <div id="modal-reviews-list">
                            <!-- avis => js -->
                            <div class="loading">Chargement des avis...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div id="modal-button-loading" class="button-loading">
                <div class="loading-spinner-small"></div>
            </div>
            <a href="#" class="btn-base btn-participate" id="modal-participate-btn"
                style="display: none;">Participer</a>
        </div>
    </div>
</div>
