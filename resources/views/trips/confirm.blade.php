@extends('layouts.app')

@section('title', 'Récapitulatif de votre sélection')

@section('content')
    <script src="{{ asset('js/rating-stars.js') }}"></script>
    <script src="{{ asset('js/trip-confirm.js') }}"></script>
    <div class="confirm-container">
        <div class="trip-details-header">
            <h1>Récapitulatif de votre sélection</h1>
            <a href="{{ route('trips.index') }}" class="btn-details role-submit-btn">Retour aux résultats</a>
        </div>

        <div id="modal-content">
            <div class="block-confirm-marg">
                <h2>Informations sur le trajet</h2>
                <div class="block-confirm">
                    <div class="trip-route-container-flex trip-route-confirm">
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
                    <div class="adress-route-confirm">
                        <div class="trip-addresses">
                            <div class="address-group">
                                <h3>Adresse de départ</h3>
                                <p id="modal-departure-address"></p>
                                <p id="modal-add-dep-address"></p>
                                <p id="modal-postal-code-dep"></p>
                            </div>
                            <div class="address-group">
                                <h3>Adresse d'arrivée</h3>
                                <p id="modal-arrival-address"></p>
                                <p id="modal-add-arr-address"></p>
                                <p id="modal-postal-code-arr"></p>
                            </div>
                        </div>
                    </div>



                    <!-- Prix -->
                    <div class="trip-route-confirm price-route-confirm">
                        <h3>Prix</h3>
                        <p><span id="modal-price"></span> crédits par personne</p>
                    </div>

                    <!-- Info supp -->
                    <div>
                        <div class="trip-grid-details trip-grid-confirm">
                            <div class="trip-info-confirm">
                                <h3>Places disponibles</h3>
                                <p id="modal-n-tickets"></p>
                            </div>

                            <div class="trip-info-confirm">
                                <h3>Durée maximale</h3>
                                <p id="modal-max-travel-time"></p>
                            </div>

                            <div class="trip-info-confirm">
                                <h3>Type de trajet</h3>
                                <p id="modal-eco-travel"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- conducteur -->
            <div class="block-confirm">
                <h2>Informations sur le conducteur</h2>

                <div class="block-confirm-marg driver-profile-confirm">
                    <div id="modal-driver-photo" class="driver-photo"></div>
                    <div class="driver-info">
                        <h3 id="modal-driver-pseudo"></h3>
                        <div class="driver-rating">
                            <span class="rating-value" id="modal-driver-rating"></span>
                            <span class="rating-stars" id="modal-driver-stars"></span>
                        </div>
                    </div>
                </div>

                <div class="driver-details-container">
                    <div class="block-confirm-margbot block-confirm">
                        <h3>Véhicule</h3>
                        <div class="vehicle-info">
                            <p><span class="vehicle-label-details">Immatriculation :</span> <span id="modal-immat"></span>
                            </p>
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
                    <div class="block-confirm-margbot block-confirm">
                        <h3>Préférences</h3>
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


                <!-- Avis -->
                <h2>Avis sur le conducteur</h2>
                <div class="reviews-container-confirm">
                    <div id="confirm-reviews-list">
                        <!-- avis => js -->
                        <div class="loading">Chargement des avis...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-submit-confirm">
            <button type="submit" id="first-confirm-btn" class="next-confirm  role-submit-btn">Confirmer</button>
        </div>
    </div>

    <!-- Modale conf paiement -->
    <div class="modal" id="paymentConfirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmation de paiement</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="block-confirm">
                    <div class="trip-info-confirm bolb-trip-info-confirm">
                        <p>Vos crédits actuels : <span class="credit-cost"
                                id="current-credits">{{ Auth::user()->n_credit }}</span> crédits</p>
                        <p>Coût du trajet : <span id="trip-cost"></span> crédits</p>
                        <p>Crédits restants : <span class="credit-cost" id="remaining-credits"></span> crédits</p>
                    </div>

                    <div class="message-avert">
                        <p>En validant, vous allez confirmer votre participation à ce covoiturage.
                            Vos crédits seront donc déduits et votre inscription à ce trajet programmée.</p>
                        <p>Vous pourrez néanmoins l'annuler et revoir toutes les informations le concernant depuis votre
                            espace utilisateur.</p>
                    </div>

                    <div class="form-submit-confirm">
                        <button type="button" class="final-confirm role-submit-btn" id="final-confirm-btn">En
                            route</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
