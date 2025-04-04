@extends('layouts.app')

@section('title', 'Covoiturage')

@section('content')
    <main class="covoiturage-container">
        <h1 class="covoiturage-title">Rechercher un covoiturage</h1>

        <section class="search-section">
            <!-- Gestion des messages -->
            @if (session('error'))
                <div class="error-message">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="info-message">
                    {{ session('info') }}

                    @if (session('suggested_date'))
                        <form action="{{ route('search.covoiturage') }}" method="POST" class="suggested-date-form">
                            @csrf
                            <input type="hidden" name="lieu_depart" value="{{ session('lieu_depart') }}">
                            <input type="hidden" name="lieu_arrivee" value="{{ session('lieu_arrivee') }}">
                            <input type="hidden" name="date" value="{{ session('suggested_date') }}">
                            Essayez plutôt le <strong>{{ date('d/m/Y', strtotime(session('suggested_date'))) }}</strong>
                            <button type="submit" class="suggested-date-btn">Rechercher à cette date</button>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Formulaire de recherche -->
            <form class="search-form" action="{{ route('search.covoiturage') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="lieu_depart">Départ</label>
                    <input type="text" id="lieu_depart" name="lieu_depart" placeholder="Ville de départ" required
                        value="{{ old('lieu_depart') ?? request('lieu_depart') }}">
                </div>
                <div class="form-group">
                    <label for="lieu_arrivee">Arrivée</label>
                    <input type="text" id="lieu_arrivee" name="lieu_arrivee" placeholder="Ville d'arrivée" required
                        value="{{ old('lieu_arrivee') ?? request('lieu_arrivee') }}">
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required
                        value="{{ old('date') ?? request('date') }}">
                </div>
                <button type="submit" class="search-button">Rechercher un trajet</button>
            </form>
        </section>

        @if (session()->has('covoiturages') && count(session('covoiturages')) > 0)
            <div class="results-title">
                <h2>Trajets disponibles</h2>
                <p>{{ count(session('covoiturages')) }} résultat(s) trouvé(s)</p>
            </div>

            <section class="covoiturage-list">
                @foreach (session('covoiturages') as $covoiturage)
                    <div class="covoiturage-card">
                        <div class="covoiturage-top-info">
                            <div class="covoiturage-driver">
                                <img src="{{ asset($covoiturage['photo_chauffeur']) }}"
                                    alt="Photo de {{ $covoiturage['pseudo_chauffeur'] }}" class="driver-photo">
                                <div class="driver-info">
                                    <h3>{{ $covoiturage['pseudo_chauffeur'] }}</h3>
                                    <div class="driver-rating">
                                        <span class="rating-value">{{ $covoiturage['note_chauffeur'] }}</span>
                                        <span class="rating-stars">
                                            @if (is_numeric($covoiturage['note_chauffeur']))
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= floor($covoiturage['note_chauffeur']))
                                                        <span class="star filled">★</span>
                                                    @elseif($i - 0.5 <= $covoiturage['note_chauffeur'])
                                                        <span class="star half-filled">⭐</span>
                                                    @else
                                                        <span class="star empty">☆</span>
                                                    @endif
                                                @endfor
                                            @else
                                                <span>Nouveau conducteur</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="covoiturage-booking-info">
                                <div class="trip-seats">
                                    <i class="fas fa-user"></i>
                                    <span>{{ $covoiturage['places_restantes'] }}
                                        {{ $covoiturage['places_restantes'] > 1 ? 'places disponibles' : 'place disponible' }}</span>
                                </div>
                                <div class="trip-price">
                                    <span class="price-value">{{ $covoiturage['prix'] }} crédits</span>
                                    <span class="price-per-person">par personne</span>
                                </div>
                            </div>
                        </div>

                        <div class="covoiturage-driver">
                            <img src="{{ asset($covoiturage['photo_chauffeur']) }}"
                                alt="Photo de {{ $covoiturage['pseudo_chauffeur'] }}" class="driver-photo">
                            <div class="driver-info">
                                <h3>{{ $covoiturage['pseudo_chauffeur'] }}</h3>
                                <div class="driver-rating">
                                    <span class="rating-value">{{ $covoiturage['note_chauffeur'] }}</span>
                                    <span class="rating-stars">
                                        @if (is_numeric($covoiturage['note_chauffeur']))
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($covoiturage['note_chauffeur']))
                                                    <span class="star filled">★</span>
                                                @elseif($i - 0.5 <= $covoiturage['note_chauffeur'])
                                                    <span class="star half-filled">⭐</span>
                                                @else
                                                    <span class="star empty">☆</span>
                                                @endif
                                            @endfor
                                        @else
                                            <span>Nouveau conducteur</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="covoiturage-details">
                            <div class="trip-info-container">
                                <div class="trip-info-left">
                                    <div class="trip-route">
                                        <span class="from">{{ $covoiturage['lieu_depart'] }}</span>
                                        <span class="route-arrow">→</span>
                                        <span class="to">{{ $covoiturage['lieu_arrivee'] }}</span>
                                    </div>
                                    <div class="trip-date">
                                        <i class="fas fa-calendar"></i>
                                        {{ date('d/m/Y', strtotime($covoiturage['date_depart'])) }}
                                    </div>
                                </div>
                                <div class="trip-time">
                                    <span class="departure-time">
                                        <i class="fas fa-clock"></i>
                                        Départ: {{ $covoiturage['heure_depart'] }}
                                    </span>
                                    <span class="arrival-time">
                                        <i class="fas fa-clock"></i>
                                        Arrivée: {{ $covoiturage['heure_arrivee'] }}
                                    </span>
                                </div>
                            </div>

                            <div class="trip-eco-badge {{ $covoiturage['ecologique'] ? 'eco' : 'standard' }}">
                                @if ($covoiturage['ecologique'])
                                    <i class="fas fa-leaf"></i> Voyage écologique
                                @else
                                    <i class="fas fa-car"></i> Voyage standard
                                @endif
                            </div>
                        </div>


                        <div class="covoiturage-booking">
                            <div class="trip-seats">
                                <i class="fas fa-user"></i>
                                <span>{{ $covoiturage['places_restantes'] }}
                                    {{ $covoiturage['places_restantes'] > 1 ? 'places disponibles' : 'place disponible' }}</span>
                            </div>

                            <div class="trip-price">
                                <span class="price-value">{{ $covoiturage['prix'] }} crédits</span>
                                <span class="price-per-person">par personne</span>
                            </div>

                            <div class="booking-buttons">
                                <a href="{{ route('trips.show', ['id' => $covoiturage['id'] ?? 1]) }}"
                                    class="btn-base btn-details">
                                    Détails
                                </a>
                                <a href="{{ route('trips.participate', ['id' => $covoiturage['id'] ?? 1]) }}"
                                    class="btn-base btn-participate">
                                    Participer
                                </a>
                            </div>
                        </div>

                        <div class="mobile-buttons">
                            <a href="{{ route('trips.show', ['id' => $covoiturage['id'] ?? 1]) }}"
                                class="btn-base btn-details">
                                Détails
                            </a>
                            <a href="{{ route('trips.participate', ['id' => $covoiturage['id'] ?? 1]) }}"
                                class="btn-base btn-participate">
                                Participer
                            </a>
                        </div>
                    </div>
                @endforeach
            </section>
        @elseif(request()->isMethod('POST') || isset($_GET['error']))
            <div class="no-results">
                <p>Aucun covoiturage disponible correspondant à vos critères.</p>
                <p>Essayez de modifier votre recherche ou consultez nos suggestions ci-dessus.</p>
            </div>
        @else
            <div class="welcome-message">
                <div class="welcome-icon">🚗</div>
                <h2>Bienvenue sur la page de covoiturage</h2>
                <p>Utilisez le formulaire ci-dessus pour trouver votre prochain trajet écologique et économique.</p>
                <div class="welcome-tips">
                    <h3>Conseils pour votre recherche :</h3>
                    <ul>
                        <li>Soyez précis sur les noms de villes</li>
                        <li>Essayez différentes dates pour plus d'options</li>
                        <li>Les voyages écologiques sont indiqués par un badge vert</li>
                    </ul>
                </div>
                <section>
                    <div class="covoiturage-card">
                        <div class="covoiturage-top-info">
                            <div class="covoiturage-driver">
                                <img src="{{ asset('images/default-avatar.jpg') }}" alt="Photo de JohnDoe"
                                    class="driver-photo">
                                <div class="driver-info">
                                    <h3>JohnDoe</h3>
                                    <div class="driver-rating">
                                        <span class="rating-value">4.8</span>
                                        <span class="rating-stars">⭐⭐⭐⭐⭐</span>
                                    </div>
                                </div>
                            </div>

                            <div class="covoiturage-booking-info">
                                <div class="trip-seats">
                                    <i class="fas fa-user"></i>
                                    <span>3 places</span>
                                </div>
                                <div class="trip-price">
                                    <span class="price-value">27 crédits</span>
                                    <span class="price-per-person">par personne</span>
                                </div>
                            </div>
                        </div>

                        <div class="covoiturage-driver">
                            <img src="{{ asset('images/default-avatar.jpg') }}" alt="Photo de JohnDoe"
                                class="driver-photo">
                            <div class="driver-info">
                                <h3>JohnDoe</h3>
                                <div class="driver-rating">
                                    <span class="rating-value">4.8</span>
                                    <span class="rating-stars">⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                        </div>

                        <div class="covoiturage-details">
                            <div class="trip-info-container">
                                <div class="trip-info-left">
                                    <div class="trip-route">
                                        <span class="from">Lyon</span>
                                        <span class="route-arrow">→</span>
                                        <span class="to">Paris</span>
                                    </div>
                                    <div class="trip-date">
                                        <i class="fas fa-calendar"></i>
                                        25/03/2025
                                    </div>
                                </div>
                                <div class="trip-time">
                                    <span class="departure-time">
                                        <i class="fas fa-clock"></i>
                                        Départ: 09:45
                                    </span>
                                    <span class="arrival-time">
                                        <i class="fas fa-clock"></i>
                                        Arrivée: 13:30
                                    </span>
                                </div>
                            </div>

                            <div class="trip-eco-badge eco">
                                <i class="fas fa-leaf"></i> Voyage écologique
                            </div>
                        </div>

                        <div class="covoiturage-booking">
                            <div class="trip-seats">
                                <i class="fas fa-user"></i>
                                <span>3 places disponibles</span>
                            </div>

                            <div class="trip-price">
                                <span class="price-value">27 crédits</span>
                                <span class="price-per-person">par personne</span>
                            </div>

                            <div class="booking-buttons">
                                <a href="{{ route('trips.show', ['id' => 33]) }}" class="btn-base btn-details">
                                    Détails
                                </a>
                                <a href="{{ route('trips.participate', ['id' => 33]) }}"
                                    class="btn-base btn-participate">
                                    Participer
                                </a>
                            </div>
                        </div>

                        <div class="mobile-buttons">
                            <a href="{{ route('trips.show', ['id' => 33]) }}" class="btn-base btn-details">
                                Détails
                            </a>
                            <a href="{{ route('trips.participate', ['id' => 33]) }}" class="btn-base btn-participate">
                                Participer
                            </a>
                        </div>
                    </div>

                </section>
            </div>

        @endif

    </main>
@endsection
