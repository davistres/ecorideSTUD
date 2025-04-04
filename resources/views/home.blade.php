@extends('layouts.app')

@section('title', 'Mon Espace - EcoRide')

@section('content')
    <div class="dashboard-container">
        <h1 class="dashboard-title">Mon Espace</h1>
        <div class="dashboard-grid {{ strtolower(str_replace(' ', '-', Auth::user()->role)) }}">
            <!-- Profil -->
            <div
                class="dashboard-widget profile-widget {{ Auth::user()->role === 'Conducteur' || Auth::user()->role === 'Les deux' ? 'half-height' : '' }}">
                <div class="widget-header">
                    <h2>Mon Profil</h2>
                    <div class="widget-actions">
                        <a href="{{ route('profile.edit') }}" class="widget-action-btn"><i class="fas fa-edit"></i></a>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="profile-info">
                        <div class="profile-avatar" id="profile-avatar-clickable">
                            @if ($profile_photo && $profile_photo_mime)
                                <img src="data:{{ $profile_photo_mime }};base64,{{ $profile_photo }}" alt="Photo actuelle">
                            @else
                                <div class="photo-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="profile-details">
                            <h3>{{ Auth::user()->pseudo }}</h3>
                            <p><i class="fas fa-envelope"></i> {{ Auth::user()->mail }}</p>
                            <div class="profile-credits">
                                <span class="credits-amount">{{ Auth::user()->n_credit }}</span>
                                <span class="credits-label">crédits</span>
                                <span class="credits-info">(1 crédit = 10€)</span>
                            </div>
                        </div>
                    </div>
                    <button class="recharge-btn">Recharger mes crédits</button>
                </div>
            </div>



            <!-- role -->
            <div class="dashboard-widget role-widget">
                <div class="widget-header">
                    <h2>Mon Rôle</h2>
                </div>
                <div class="widget-content">
                    <div class="current-role">
                        <span class="role-label">Rôle actuel :</span>
                        <span class="role-value {{ strtolower(Auth::user()->role) }}">{{ Auth::user()->role }}</span>
                    </div>
                    <div class="role-change">
                        <form action="{{ route('user.role.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="role-options">
                                <label class="role-option">
                                    <div class="role-radio">
                                        <span class="role-name">Passager</span>
                                        <input type="radio" name="role" value="Passager"
                                            {{ Auth::user()->role == 'Passager' ? 'checked' : '' }}>
                                    </div>
                                    <span class="role-desc">Je cherche des trajets</span>
                                </label>
                                <label class="role-option">
                                    <div class="role-radio">
                                        <span class="role-name">Conducteur</span>
                                        <input type="radio" name="role" value="Conducteur"
                                            {{ Auth::user()->role == 'Conducteur' ? 'checked' : '' }}>
                                    </div>
                                    <span class="role-desc">Je propose des trajets</span>
                                </label>
                                <label class="role-option">
                                    <div class="role-radio">
                                        <span class="role-name">Les deux</span>
                                        <input type="radio" name="role" value="Les deux"
                                            {{ Auth::user()->role == 'Les deux' ? 'checked' : '' }}>
                                    </div>
                                    <span class="role-desc">Je cherche et propose des trajets</span>
                                </label>
                            </div>
                            <button type="button" class="role-submit-btn">Changer mon rôle</button>
                        </form>
                    </div>
                </div>
            </div>



            <!-- preferences -->
            <div class="dashboard-widget preferences-widget">
                <div class="widget-header">
                    <h2>Mes Préférences</h2>
                    <div class="widget-actions">
                        <button type="button" id="edit-preferences-btn" class="widget-action-btn"><i
                                class="fas fa-edit"></i></button>
                    </div>
                </div>
                <div class="widget-content">
                    @if (isset($chauffeur))
                        <div class="preferences-list">
                            <div class="preference-item">
                                <div class="preference-icon">
                                    <i
                                        class="fas {{ $chauffeur->pref_smoke == 'Fumeur' ? 'fa-smoking' : 'fa-smoking-ban' }}"></i>
                                </div>
                                <div class="preference-text">
                                    <span>{{ $chauffeur->pref_smoke }}</span>
                                </div>
                            </div>
                            <div class="preference-item">
                                <div class="preference-icon">
                                    <i class="fas {{ $chauffeur->pref_pet == 'Acceptés' ? 'fa-paw' : 'fa-ban' }}"></i>
                                </div>
                                <div class="preference-text">
                                    <span>Animaux {{ $chauffeur->pref_pet }}</span>
                                </div>
                            </div>
                            <div class="preference-item preference-libre"
                                style="{{ isset($chauffeur) && $chauffeur->pref_libre ? '' : 'display: none;' }}">
                                <div class="preference-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="preference-text">
                                    <span>{{ isset($chauffeur) ? $chauffeur->pref_libre : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="no-preferences">
                            <p>Vous devez être conducteur pour définir vos préférences.</p>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Nouveau Widget Voitures -->
            @if (Auth::user()->role === 'Conducteur' || Auth::user()->role === 'Les deux')
                <div class="dashboard-widget vehicles-widget">
                    <div class="widget-header">
                        <h2>Mes Véhicules</h2>
                        <div class="widget-actions">
                            <button type="button" id="add-vehicle-btn" class="widget-action-btn"><i
                                    class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="widget-content">
                        @if (isset($vehicles) && count($vehicles) > 0)
                            <div class="vehicles-list">
                                @foreach ($vehicles as $vehicle)
                                    <div class="vehicle-card" data-immat="{{ $vehicle->immat }}"
                                        data-date="{{ $vehicle->date_first_immat ? $vehicle->date_first_immat->format('Y-m-d') : '' }}">
                                        <div class="vehicle-info">
                                            <div class="vehicle-model">
                                                <span class="vehicle-brand">{{ $vehicle->brand ?? 'N/A' }}</span>
                                                <span class="vehicle-name">{{ $vehicle->model ?? '' }}</span>
                                            </div>
                                            <div class="vehicle-details">
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-palette"></i>
                                                    <span>{{ $vehicle->color ?? 'N/A' }}</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-users"></i>
                                                    <span>{{ $vehicle->n_place ?? '?' }} places</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-charging-station"></i>
                                                    <span>{{ $vehicle->energie ?? 'N/A' }}</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-id-card"></i>
                                                    <span>{{ $vehicle->immat ?? 'N/A' }}</span>
                                                </div>
                                                @if ($vehicle->date_first_immat)
                                                    <div class="vehicle-detail">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <span>{{ \Carbon\Carbon::parse($vehicle->date_first_immat)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="vehicle-actions">
                                            <button type="button" class="vehicle-edit-btn js-edit-vehicle"
                                                data-immat="{{ $vehicle->immat }}"><i class="fas fa-edit"></i></button>
                                            <button type="button" class="vehicle-delete-btn js-delete-vehicle"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif


            <!-- Covoit proposés -->
            <div class="dashboard-widget offered-trips-widget">
                <div class="widget-header">
                    <h2>Mes Trajets Proposés</h2>
                    <div class="widget-actions">
                        <a href="{{ route('trip.create') }}" class="widget-action-btn"><i class="fas fa-plus"></i></a>
                    </div>
                </div>
                <div class="widget-content">
                    @if (isset($offeredTrips) && count($offeredTrips) > 0)
                        <div class="trip-cards">
                            @foreach ($offeredTrips as $trip)
                                <div class="trip-card">
                                    <div class="trip-card-header">
                                        <div class="trip-route">
                                            <span class="trip-city">{{ $trip->city_dep }}</span>
                                            <i class="fas fa-arrow-right"></i>
                                            <span class="trip-city">{{ $trip->city_arr }}</span>
                                        </div>
                                        <div class="trip-date">
                                            <i class="far fa-calendar-alt"></i>
                                            {{ \Carbon\Carbon::parse($trip->departure_date)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    <div class="trip-card-content">
                                        <div class="trip-time">
                                            <div class="departure-time">
                                                <span class="time-label">Départ :</span>
                                                <span
                                                    class="time-value">{{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }}</span>
                                            </div>
                                            <div class="arrival-time">
                                                <span class="time-label">Arrivée :</span>
                                                <span
                                                    class="time-value">{{ \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') }}</span>
                                            </div>
                                        </div>
                                        <div class="trip-vehicle">
                                            <span class="vehicle-label">Véhicule :</span>
                                            <span class="vehicle-name">{{ $trip->voiture->brand }}
                                                {{ $trip->voiture->model }}</span>
                                        </div>
                                        <div class="trip-status">
                                            <div class="trip-seats">
                                                <i class="fas fa-users"></i>
                                                <span>{{ $trip->confirmations->count() }}/{{ $trip->n_tickets }}
                                                    places réservées</span>
                                            </div>
                                            <div class="trip-price">
                                                <span class="price-value">{{ $trip->price }} crédits</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="trip-card-footer">
                                        @if ($trip->confirmations->count() > 0)
                                            <button class="trip-passengers-btn" data-trip="{{ $trip->covoit_id }}">
                                                <i class="fas fa-user-friends"></i> Passagers
                                            </button>
                                        @endif
                                        @if (strtotime($trip->departure_date) > strtotime('today'))
                                            @if (!isset($trip->trip_started) || !$trip->trip_started)
                                                <button class="trip-start-btn"
                                                    data-trip="{{ $trip->covoit_id }}">Démarrer</button>
                                            @else
                                                <button class="trip-end-btn" data-trip="{{ $trip->covoit_id }}">Arrivée à
                                                    destination</button>
                                            @endif
                                            <form action="{{ route('trip.cancel', $trip->covoit_id) }}" method="POST"
                                                class="cancel-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="trip-cancel-btn">Annuler</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="no-trips">
                            <div class="no-trips-icon">
                                <i class="fas fa-route"></i>
                            </div>
                            <p>Vous n'avez pas encore proposé de trajet.</p>
                            <a href="{{ route('trip.create') }}" class="create-trip-btn">Proposer un
                                trajet</a>
                        </div>
                    @endif
                </div>
            </div>


            <!-- trajets réservés -->
            @if (Auth::user()->role == 'Passager' || Auth::user()->role == 'Les deux')
                <div class="dashboard-widget booked-trips-widget">
                    <div class="widget-header">
                        <h2>Mes Trajets à Venir</h2>
                    </div>
                    <div class="widget-content">
                        @if (isset($reservations) && count($reservations) > 0)
                            <div class="trip-cards">
                                @foreach ($reservations as $reservation)
                                    <div class="trip-card">
                                        <div class="trip-card-header">
                                            <div class="trip-route">
                                                <span class="trip-city">{{ $reservation->covoiturage->city_dep }}</span>
                                                <i class="fas fa-arrow-right"></i>
                                                <span class="trip-city">{{ $reservation->covoiturage->city_arr }}</span>
                                            </div>
                                            <div class="trip-date">
                                                <i class="far fa-calendar-alt"></i>
                                                {{ \Carbon\Carbon::parse($reservation->covoiturage->departure_date)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="trip-card-content">
                                            <div class="trip-time">
                                                <div class="departure-time">
                                                    <span class="time-label">Départ :</span>
                                                    <span
                                                        class="time-value">{{ \Carbon\Carbon::parse($reservation->covoiturage->departure_time)->format('H:i') }}</span>
                                                </div>
                                                <div class="arrival-time">
                                                    <span class="time-label">Arrivée :</span>
                                                    <span
                                                        class="time-value">{{ \Carbon\Carbon::parse($reservation->covoiturage->arrival_time)->format('H:i') }}</span>
                                                </div>
                                            </div>
                                            <div class="trip-driver">
                                                <span class="driver-label">Conducteur :</span>
                                                <span
                                                    class="driver-name">{{ $reservation->covoiturage->chauffeur->utilisateur->pseudo }}</span>
                                                @if (isset($reservation->covoiturage->chauffeur) && $reservation->covoiturage->chauffeur->moy_note > 0)
                                                    <div class="driver-rating">
                                                        <span
                                                            class="rating-value">{{ number_format($reservation->covoiturage->chauffeur->moy_note, 1) }}</span>
                                                        <div class="rating-stars">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= floor($reservation->covoiturage->chauffeur->moy_note))
                                                                    <span class="star filled"><i
                                                                            class="fas fa-star"></i></span>
                                                                @elseif($i - 0.5 <= $reservation->covoiturage->chauffeur->moy_note)
                                                                    <span class="star half-filled"><i
                                                                            class="fas fa-star-half-alt"></i></span>
                                                                @else
                                                                    <span class="star empty"><i
                                                                            class="far fa-star"></i></span>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="trip-price">
                                                <span class="price-value">{{ $reservation->covoiturage->price }}
                                                    crédits</span>
                                            </div>
                                        </div>
                                        <div class="trip-card-footer">
                                            <a href="{{ route('trips.show', $reservation->covoiturage->covoit_id) }}"
                                                class="trip-detail-btn">Détails</a>
                                            <form action="{{ route('reservation.cancel', $reservation->conf_id) }}"
                                                method="POST" class="cancel-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="trip-cancel-btn">Annuler</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="no-trips">
                                <div class="no-trips-icon">
                                    <i class="fas fa-car-side"></i>
                                </div>
                                <p>Vous n'avez pas encore réservé de trajet.</p>
                                <a href="{{ route('trips.index') }}" class="search-trips-btn">Rechercher un trajet</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- formulaire à compléter -->
            @if (isset($pendingSatisfactions) && count($pendingSatisfactions) > 0)
                <div class="dashboard-widget pending-forms-widget">
                    <div class="widget-header">
                        <h2>Formulaires à Compléter</h2>
                        <div class="widget-badge">{{ count($pendingSatisfactions) }}</div>
                    </div>
                    <div class="widget-content">
                        <div class="pending-forms">
                            @foreach ($pendingSatisfactions as $satisfaction)
                                <div class="form-card">
                                    <div class="form-card-header">
                                        <div class="form-route">
                                            <span class="form-city">{{ $satisfaction->covoiturage->city_dep }}</span>
                                            <i class="fas fa-arrow-right"></i>
                                            <span class="form-city">{{ $satisfaction->covoiturage->city_arr }}</span>
                                        </div>
                                        <div class="form-date">
                                            <i class="far fa-calendar-alt"></i>
                                            {{ \Carbon\Carbon::parse($satisfaction->covoiturage->departure_date)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    <div class="form-card-content">
                                        <p>Merci de compléter le formulaire de satisfaction pour ce trajet.</p>
                                        <a href="{{ route('satisfaction.form', $satisfaction->satisfaction_id) }}"
                                            class="form-btn">Compléter</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif


            <!-- Historique -->
            <div class="dashboard-widget history-widget">
                <div class="widget-header">
                    <h2>Mon Historique</h2>
                </div>
                <div class="widget-content">
                    <div class="history-tabs">
                        <button class="history-tab active" data-tab="passenger">Passager</button>
                        @if (Auth::user()->role == 'Conducteur' || Auth::user()->role == 'Les deux')
                            <button class="history-tab" data-tab="driver">Conducteur</button>
                        @endif
                    </div>
                    <div class="history-content">
                        <div class="history-tab-content active" id="passenger-history">
                            @if (isset($passengerHistory) && count($passengerHistory) > 0)
                                <div class="history-list">
                                    @foreach ($passengerHistory as $item)
                                        <div class="history-item">
                                            <div class="history-item-header">
                                                <div class="history-route">
                                                    <span class="history-city">{{ $item->covoiturage->city_dep }}</span>
                                                    <i class="fas fa-arrow-right"></i>
                                                    <span class="history-city">{{ $item->covoiturage->city_arr }}</span>
                                                </div>
                                                <div class="history-date">
                                                    {{ \Carbon\Carbon::parse($item->covoiturage->departure_date)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div class="history-item-content">
                                                <div class="history-status">
                                                    @if ($item->cancelled)
                                                        <span class="status-cancelled"><i class="fas fa-times-circle"></i>
                                                            Annulé</span>
                                                    @elseif($item->completed)
                                                        <span class="status-completed"><i class="fas fa-check-circle"></i>
                                                            Terminé</span>
                                                    @endif
                                                </div>
                                                <div class="history-price">
                                                    {{ $item->covoiturage->price }} crédits
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="no-history">
                                    <p>Aucun trajet dans votre historique.</p>
                                </div>
                            @endif
                        </div>

                        @if (Auth::user()->role == 'Conducteur' || Auth::user()->role == 'Les deux')
                            <div class="history-tab-content" id="driver-history">
                                @if (isset($driverHistory) && count($driverHistory) > 0)
                                    <div class="history-list">
                                        @foreach ($driverHistory as $item)
                                            <div class="history-item">
                                                <div class="history-item-header">
                                                    <div class="history-route">
                                                        <span class="history-city">{{ $item->city_dep }}</span>
                                                        <i class="fas fa-arrow-right"></i>
                                                        <span class="history-city">{{ $item->city_arr }}</span>
                                                    </div>
                                                    <div class="history-date">
                                                        {{ \Carbon\Carbon::parse($item->departure_date)->format('d/m/Y') }}
                                                    </div>
                                                </div>
                                                <div class="history-item-content">
                                                    <div class="history-status">
                                                        @if ($item->cancelled)
                                                            <span class="status-cancelled"><i
                                                                    class="fas fa-times-circle"></i> Annulé</span>
                                                        @elseif($item->completed)
                                                            <span class="status-completed"><i
                                                                    class="fas fa-check-circle"></i> Terminé</span>
                                                        @endif
                                                    </div>
                                                    <div class="history-passengers">
                                                        <i class="fas fa-users"></i> {{ $item->passengers_count }}
                                                        passagers
                                                    </div>
                                                    <div class="history-earnings">
                                                        <i class="fas fa-coins"></i> {{ $item->earnings }} crédits
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="no-history">
                                        <p>Aucun trajet dans votre historique.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal changement de rôle -->
    <div class="modal" id="roleChangeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Informations conducteur</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <form id="roleChangeForm" action="{{ route('user.role.update') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Champ caché pour le rôle -->
                    <input type="hidden" id="modal_role" name="role" value="">

                    <!-- Préférences conducteur -->
                    <div id="driver-form-section">
                        <h4>Préférences conducteur</h4>
                        <div class="form-group">
                            <label for="pref_smoke">Préférence fumeur*</label>
                            <select id="pref_smoke" name="pref_smoke" required>
                                <option value="Fumeur">Fumeur</option>
                                <option value="Non-fumeur">Non-fumeur</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pref_pet">Préférence animaux*</label>
                            <select id="pref_pet" name="pref_pet" required>
                                <option value="Acceptés">Animaux acceptés</option>
                                <option value="Non-acceptés">Animaux non acceptés</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pref_libre">Autres préférences ou informations</label>
                            <textarea id="pref_libre" name="pref_libre" rows="3"
                                placeholder="Exemple: Musique classique, conversation limitée, etc."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="profile_photo">Photo de profil (optionnel)</label>
                            <input type="file" id="profile_photo" name="profile_photo">
                            <small class="form-help-text">Une photo de profil aide à établir la confiance avec vos
                                passagers.</small>
                        </div>
                    </div>

                    <!-- Informations Véhicule -->
                    <div id="vehicle-form-section">
                        <h4>Informations Véhicule</h4>
                        <div class="info-message">
                            <p>Les informations suivantes sont obligatoires. Veuillez remplir tous les champs correctement.
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="marque">Marque*</label>
                            <input type="text" id="marque" name="marque" required>
                        </div>
                        <div class="form-group">
                            <label for="modele">Modèle*</label>
                            <input type="text" id="modele" name="modele" required>
                        </div>
                        <div class="form-group">
                            <label for="immat">Immatriculation*</label>
                            <input type="text" id="immat" name="immat" required>
                            <small class="form-help-text">Le numéro d'immatriculation doit être unique. Un message d'erreur
                                apparaîtra si ce numéro existe déjà.</small>
                        </div>
                        <div class="form-group">
                            <label for="date_first_immat">Date de la 1ère immatriculation*</label>
                            <input type="date" id="date_first_immat" name="date_first_immat" required
                                max="{{ date('Y-m-d') }}">
                            <small class="form-help-text">La date doit être dans le passé (antérieure à
                                aujourd'hui).</small>
                        </div>
                        <div class="form-group">
                            <label for="couleur">Couleur*</label>
                            <input type="text" id="couleur" name="couleur" required>
                        </div>
                        <div class="form-group">
                            <label for="n_place">Nombre de places*</label>
                            <input type="number" id="n_place" name="n_place" min="2" max="9"
                                value="2" required>
                            <small class="form-help-text">Minimum 2 places, maximum 9 places.</small>
                        </div>
                        <div class="form-group">
                            <label for="energie">Type d'énergie*</label>
                            <select id="energie" name="energie" required>
                                <option value="Essence">Essence</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Hybride">Hybride</option>
                            </select>
                            <small class="form-help-text">Les véhicules électriques sont considérés comme écologiques sur
                                notre plateforme.</small>
                        </div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="role-submit-btn">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Détails véhicule -->
    <div class="modal" id="vehicleDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails du Véhicule</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div id="vehicle-details-content"></div>
                <button class="delete-vehicle-btn">Supprimer ce véhicule</button>
            </div>
        </div>
    </div>

    <!-- Avertissement suppression dernière voiture -->
    <div class="modal" id="removeAllVehiclesModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Avertissement</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <p>Si vous supprimez toutes vos voitures, vous perdrez le statut de conducteur ainsi que toutes les
                    informations déjà enregistrées (préférences, photo, etc.). Voulez-vous confirmer ?</p>
                <button class="confirm-remove-all-btn">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Avertissement retour Passager -->
    <div class="modal" id="revertToPassengerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Avertissement</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <p>En revenant au rôle "Passager", vous ne pourrez plus éditer des covoiturages et vous perdrez toutes les
                    informations
                    enregistrées (préférences, véhicules, etc...). Voulez-vous confirmer ?</p>
                <button class="confirm-revert-btn" data-reset-url="{{ route('user.role.reset') }}">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Modal Passagers => existante -->
    <div class="modal" id="passengersModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Passagers</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div id="passengers-list"></div>
            </div>
        </div>
    </div>

    <!-- pop-up édition de profil -->
    <div class="modal" id="profileEditModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier mon profil</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <form id="profileEditForm" action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="pseudo">Pseudo*</label>
                        <input type="text" id="pseudo" name="pseudo" value="{{ Auth::user()->pseudo }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="mail">Adresse email*</label>
                        <input type="email" id="mail" name="mail" value="{{ Auth::user()->mail }}" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe (laisser vide pour conserver l'actuel)</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmation du nouveau mot de passe</label>
                        <input type="password" id="password_confirmation" name="password_confirmation">
                    </div>
                    <div class="form-submit">
                        <button type="submit" class="search-button">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo de Profil -->
    <div class="modal" id="profilePhotoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changement de Photo de Profil</h3>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div class="profile-photo-container">
                    <div class="profile-photo-preview">
                        <h4>Photo actuelle</h4>
                        <div class="photo-preview-area" id="photo-preview"
                            data-delete-url="{{ route('profile.photo.delete') }}">
                            @if ($profile_photo && $profile_photo_mime)
                                <div class="photo-container" style="position: relative;">
                                    <img src="data:{{ $profile_photo_mime }};base64,{{ $profile_photo }}"
                                        alt="Photo actuelle">
                                    <button class="delete-photo-btn"
                                        data-delete-url="{{ route('profile.photo.delete') }}">×</button>
                                </div>
                            @else
                                <div class="photo-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="profile-photo-upload">
                        <h4>Charger une nouvelle photo</h4>
                        <form id="profilePhotoForm" action="{{ route('profile.photo.update') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <input type="file" id="profile-photo-input" name="profile_photo"
                                    accept="image/png,image/jpeg">
                                <small class="form-help-text">Taille max : 2 Mo. Formats acceptés : PNG, JPEG.</small>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="button" id="profile-photo-submit" class="search-button">Valider</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal preferencesEdit -->
    <div class="modal" id="preferencesEditModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier mes préférences</h3>
                <button type="button" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <form id="preferencesEditForm" action="{{ route('preferences.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @php
                        $pref_smoke = isset($chauffeur) ? $chauffeur->pref_smoke : '';
                        $pref_pet = isset($chauffeur) ? $chauffeur->pref_pet : '';
                        $pref_libre = isset($chauffeur) ? $chauffeur->pref_libre : '';
                    @endphp

                    <div id="driver-form-section-modal">
                        <div class="form-group">
                            <label for="modal_pref_smoke">Préférence fumeur*</label>
                            <select id="modal_pref_smoke" name="pref_smoke" required>
                                <option value="Fumeur" {{ $pref_smoke == 'Fumeur' ? 'selected' : '' }}>Fumeur</option>
                                <option value="Non-fumeur" {{ $pref_smoke == 'Non-fumeur' ? 'selected' : '' }}>Non-fumeur
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modal_pref_pet">Préférence animaux*</label>
                            <select id="modal_pref_pet" name="pref_pet" required>
                                <option value="Acceptés" {{ $pref_pet == 'Acceptés' ? 'selected' : '' }}>Animaux acceptés
                                </option>
                                <option value="Non-acceptés" {{ $pref_pet == 'Non-acceptés' ? 'selected' : '' }}>Animaux
                                    non acceptés</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modal_pref_libre">Autres préférences ou informations</label>
                            <small class="form-help-text">Maximum 255 caractères.</small>
                            <textarea id="modal_pref_libre" name="pref_libre" rows="3"
                                placeholder="Exemple: Musique classique, conversation limitée, etc.">{{ $pref_libre }}</textarea>
                        </div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-submit-prefs search-button">Enregistrer les
                            modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Ajout Véhicule -->
    <div class="modal" id="addVehicleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un véhicule</h3>
                <button type="button" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <form id="addVehicleForm" action="{{ route('vehicle.store') }}" method="POST">
                    @csrf
                    <div id="vehicle-form-section-modal">
                        <div class="info-message">
                            <p>Les informations suivantes sont obligatoires. Veuillez remplir tous les champs correctement.
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_marque">Marque*</label>
                            <input type="text" id="modal_add_marque" name="marque" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_modele">Modèle*</label>
                            <input type="text" id="modal_add_modele" name="modele" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_immat">Immatriculation*</label>
                            <input type="text" id="modal_add_immat" name="immat" required>
                            <small class="form-help-text">Le numéro d'immatriculation doit être unique...</small>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_date_first_immat">Date de la 1ère immatriculation*</label>
                            <input type="date" id="modal_add_date_first_immat" name="date_first_immat" required
                                max="{{ date('Y-m-d') }}">
                            <small class="form-help-text">La date doit être antérieure à aujourd'hui.</small>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_couleur">Couleur*</label>
                            <input type="text" id="modal_add_couleur" name="couleur" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_n_place">Nombre de places*</label>
                            <input type="number" id="modal_add_n_place" name="n_place" min="2" max="9"
                                value="2" required>
                            <small class="form-help-text">Minimum 2 places, maximum 9.</small>
                        </div>
                        <div class="form-group">
                            <label for="modal_add_energie">Type d'énergie*</label>
                            <select id="modal_add_energie" name="energie" required>
                                <option value="Essence">Essence</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Hybride">Hybride</option>
                            </select>
                            <small class="form-help-text">Les véhicules électriques sont considérés comme
                                écologiques.</small>
                        </div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-submit-add-vehicle search-button">Ajouter ce véhicule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Avertissement Suppression du dernier véhicule -->
    <div class="modal" id="lastVehicleDeleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>AVERTISSEMENT</h3>
                <button type="button" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <p>Ceci est votre dernier véhicule enregistré.</p>
                <p>Si vous le supprimez, votre statut sera changé en <strong>Passager</strong>. Vous ne pourrez plus
                    proposer de covoiturage et vous perdrez vos préférences de conducteur.</p>
                <p><strong>Êtes-vous sûr de vouloir continuer ?</strong></p>
            </div>
            <div class="modal-footer" style="padding: 1rem; text-align: right; border-top: 1px solid #f0f0f0;">
                <button type="button" id="confirm-delete-last-vehicle" class="btn-confirm-delete">Supprimer et Devenir
                    Passager</button>
            </div>
        </div>
    </div>

    <!-- Édition Véhicule -->
    <!-- Logiquement, ça ne devrait pas exister... On ne devrait pas pouvoir modif les infos d'une voiture... A la limite, la couleur... Mais j'ai eu quand même envi de le faire (sauf pour l'immat) -->
    <div class="modal" id="editVehicleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier mon véhicule</h3>
                <button type="button" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <form id="editVehicleForm" action="#" method="POST">
                    @csrf
                    @method('PUT')
                    <div id="vehicle-form-section-edit-modal">
                        <div class="form-group">
                            <label for="modal_edit_immat">Immatriculation</label>
                            <!-- Immatriculation non modifiable -->
                            <input type="text" id="modal_edit_immat" name="immat" readonly
                                style="background-color: #eee;">
                            <small class="form-help-text">L'immatriculation ne peut pas être modifiée.</small>
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_date_first_immat">Date de la 1ère immatriculation*</label>
                            <input type="date" id="modal_edit_date_first_immat" name="date_first_immat" required
                                max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_marque">Marque*</label>
                            <input type="text" id="modal_edit_marque" name="marque" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_modele">Modèle*</label>
                            <input type="text" id="modal_edit_modele" name="modele" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_couleur">Couleur*</label>
                            <input type="text" id="modal_edit_couleur" name="couleur" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_n_place">Nombre de places*</label>
                            <input type="number" id="modal_edit_n_place" name="n_place" min="2" max="9"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="modal_edit_energie">Type d'énergie*</label>
                            <select id="modal_edit_energie" name="energie" required>
                                <option value="Essence">Essence</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Hybride">Hybride</option>
                                <option value="GPL">GPL</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-submit-edit-vehicle search-button">Enregistrer les
                            modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
