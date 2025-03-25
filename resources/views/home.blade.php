@extends('layouts.app')

@section('title', 'Mon Espace - EcoRide')

@section('content')
    <div class="dashboard-container">
        <h1 class="dashboard-title">Mon Espace</h1>

        <div class="dashboard-grid">
            <!-- profil -->
            <div class="dashboard-widget profile-widget">
                <div class="widget-header">
                    <h2>Mon Profil</h2>
                    <div class="widget-actions">
                        <a href="{{ route('profile.edit') }}" class="widget-action-btn"><i class="fas fa-edit"></i></a>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="profile-info">
                        <div class="profile-avatar">
                            @if (isset($chauffeur) && $chauffeur->idphoto)
                                <img src="data:image/jpeg;base64,{{ base64_encode($chauffeur->idphoto) }}"
                                    alt="Photo de profil">
                            @else
                                <div class="avatar-placeholder">
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
                                    <input type="radio" name="role" value="Passager"
                                        {{ Auth::user()->role == 'Passager' ? 'checked' : '' }}>
                                    <span class="role-name">Passager</span>
                                    <span class="role-desc">Je cherche des trajets</span>
                                </label>
                                <label class="role-option">
                                    <input type="radio" name="role" value="Conducteur"
                                        {{ Auth::user()->role == 'Conducteur' ? 'checked' : '' }}>
                                    <span class="role-name">Conducteur</span>
                                    <span class="role-desc">Je propose des trajets</span>
                                </label>
                                <label class="role-option">
                                    <input type="radio" name="role" value="Les deux"
                                        {{ Auth::user()->role == 'Les deux' ? 'checked' : '' }}>
                                    <span class="role-name">Les deux</span>
                                    <span class="role-desc">Je cherche et propose des trajets</span>
                                </label>
                            </div>
                            <button type="button" class="role-submit-btn">Changer mon rôle</button>
                        </form>
                    </div>
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

            <!-- Voiture -->
            @if (Auth::user()->role == 'Conducteur' || Auth::user()->role == 'Les deux')
                <div class="dashboard-widget vehicles-widget">
                    <div class="widget-header">
                        <h2>Mes Véhicules</h2>
                        <div class="widget-actions">
                            <a href="{{ route('vehicle.create') }}" class="widget-action-btn"><i
                                    class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="widget-content">
                        @if (isset($vehicles) && count($vehicles) > 0)
                            <div class="vehicles-list">
                                @foreach ($vehicles as $vehicle)
                                    <div class="vehicle-card">
                                        <div class="vehicle-info">
                                            <div class="vehicle-model">
                                                <span class="vehicle-brand">{{ $vehicle->brand }}</span>
                                                <span class="vehicle-name">{{ $vehicle->model }}</span>
                                            </div>
                                            <div class="vehicle-details">
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-palette"></i>
                                                    <span>{{ $vehicle->color }}</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-users"></i>
                                                    <span>{{ $vehicle->n_place }} places</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-charging-station"></i>
                                                    <span>{{ $vehicle->energie }}</span>
                                                </div>
                                                <div class="vehicle-detail">
                                                    <i class="fas fa-id-card"></i>
                                                    <span>{{ $vehicle->immat }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="vehicle-actions">
                                            <a href="{{ route('vehicle.edit', $vehicle->immat) }}"
                                                class="vehicle-edit-btn"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('vehicle.delete', $vehicle->immat) }}" method="POST"
                                                class="vehicle-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="vehicle-delete-btn"><i
                                                        class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="no-vehicles">
                                <div class="no-vehicles-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                                <p>Vous n'avez pas encore ajouté de véhicule.</p>
                                <a href="{{ route('vehicle.create') }}" class="add-vehicle-btn">Ajouter un véhicule</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Préféérences -->
                <div class="dashboard-widget preferences-widget">
                    <div class="widget-header">
                        <h2>Mes Préférences</h2>
                        <div class="widget-actions">
                            <a href="{{ route('preferences.edit') }}" class="widget-action-btn"><i
                                    class="fas fa-edit"></i></a>
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
                                @if ($chauffeur->pref_libre)
                                    <div class="preference-item preference-libre">
                                        <div class="preference-icon">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="preference-text">
                                            <span>{{ $chauffeur->pref_libre }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="no-preferences">
                                <p>Vous devez être conducteur pour définir vos préférences.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Covoit proposés -->
                <div class="dashboard-widget offered-trips-widget">
                    <div class="widget-header">
                        <h2>Mes Trajets Proposés</h2>
                        <div class="widget-actions">
                            <a href="{{ route('trip.create') }}" class="widget-action-btn"><i
                                    class="fas fa-plus"></i></a>
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
                                                    <button class="trip-end-btn"
                                                        data-trip="{{ $trip->covoit_id }}">Arrivée à destination</button>
                                                @endif
                                                <form action="{{ route('trip.cancel', $trip->covoit_id) }}"
                                                    method="POST" class="cancel-form">
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
                                <a href="{{ route('trip.create') }}" class="create-trip-btn">Proposer un trajet</a>
                            </div>
                        @endif
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

    <!-- Pop-up passager -->
    <div class="modal" id="roleChangeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changement de rôle</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Formulaire conducteur -->
                <div id="driver-form-section" style="display: none;">
                    <h4>Informations conducteur</h4>
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
                        <label for="idphoto">Photo de profil (optionnel)</label>
                        <input type="file" id="idphoto" name="idphoto">
                        <small class="form-help-text">Une photo de profil aide à établir la confiance avec vos
                            passagers.</small>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="submit" class="role-submit-btn">Confirmer le changement</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

<!-- pop-up édition de profil -->
<div class="modal" id="profileEditModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier mon profil</h3>
            <button class="modal-close">&times;</button>
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
