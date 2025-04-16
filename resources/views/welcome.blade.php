@extends('layouts.app')

@section('title', 'Bienvenue sur EcoRide')

@section('content')
    <script src="{{ asset('js/date-restriction.js') }}"></script>
    <script src="{{ asset('js/suggestion-links.js') }}"></script>
    <script src="{{ asset('js/profile-photo-sync.js') }}"></script>
    <script src="{{ asset('js/trip-details-modal.js') }}"></script>
    <script src="{{ asset('js/rating-stars.js') }}"></script>
    <script src="{{ asset('js/participate-buttons.js') }}"></script>
    <section class="hero">
        <h1>Bienvenue sur EcoRide</h1>
        <p>La plateforme de covoiturage écologique</p>
    </section>

    <section class="search-section">
        <h1>Rechercher un itinéraire</h1>

        <div class="message-container">
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

                    @if (session('suggestions'))
                        <div class="date-suggestions">
                            <p>Nous n'avons pas de covoiturage à la date recherchée. Néanmoins, nous en avons
                                @foreach (session('suggestions') as $index => $suggestion)
                                    @if ($index == 0)
                                        @if ($suggestion['count'] > 1)
                                            {{ $suggestion['count'] }} le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @else
                                            le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @endif
                                    @elseif($index == count(session('suggestions')) - 1)
                                        @if ($suggestion['count'] > 1)
                                            et {{ $suggestion['count'] }} le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @else
                                            et le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @endif
                                    @else
                                        @if ($suggestion['count'] > 1)
                                            , {{ $suggestion['count'] }} le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @else
                                            , le <a href="#" class="suggestion-link"
                                                data-date="{{ $suggestion['date'] }}"
                                                data-depart="{{ session('lieu_depart') }}"
                                                data-arrivee="{{ session('lieu_arrivee') }}">{{ $suggestion['formatted_date'] }}</a>
                                            ({{ $suggestion['diff'] }})
                                        @endif
                                    @endif
                                @endforeach
                                ... Si vous êtes flexible, ils n'attendent que vous !
                            </p>

                            <!-- Formulaire caché pour les suggestions -->
                            <form id="suggestion-form" action="{{ route('search.covoiturage') }}" method="POST"
                                style="display: none;">
                                @csrf
                                <input type="hidden" id="suggestion-lieu-depart" name="lieu_depart" value="">
                                <input type="hidden" id="suggestion-lieu-arrivee" name="lieu_arrivee" value="">
                                <input type="hidden" id="suggestion-date" name="date" value="">
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <form class="search-form" action="{{ route('search.covoiturage') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="lieu_depart">Départ</label>
                <input type="text" id="lieu_depart" name="lieu_depart" placeholder="Ville de départ" required
                    value="{{ old('lieu_depart') ?? request('lieu_depart', '') }}">
            </div>
            <div class="form-group">
                <label for="lieu_arrivee">Arrivée</label>
                <input type="text" id="lieu_arrivee" name="lieu_arrivee" placeholder="Ville d'arrivée" required
                    value="{{ old('lieu_arrivee') ?? request('lieu_arrivee', '') }}">
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required
                    value="{{ old('date') ?? request('date', '') }}">
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
                            @if ($covoiturage->photo_chauffeur && $covoiturage->photo_chauffeur != 'images/default-avatar.jpg')
                                <img src="{{ asset($covoiturage->photo_chauffeur) }}"
                                    alt="{{ $covoiturage->pseudo_chauffeur }}" class="driver-photo">
                            @else
                                <div class="driver-photo photo-placeholder">
                                    <i class="fas fa-user svg-inline--fa"></i>
                                </div>
                            @endif
                            <div class="driver-info">
                                <h3>{{ $covoiturage->pseudo_chauffeur }}</h3>
                                <div class="driver-rating">
                                    <span class="rating-value">{{ $covoiturage->note_chauffeur }}</span>
                                    <span class="rating-stars">
                                        @if (is_numeric($covoiturage->note_chauffeur))
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span class="star filled">★</span>
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
                                <span>{{ $covoiturage->places_restantes }}
                                    {{ $covoiturage->places_restantes > 1 ? 'places disponibles' : 'place disponible' }}</span>
                            </div>
                            <div class="trip-price">
                                <span class="price-value">{{ $covoiturage->prix }} crédits</span>
                                <span class="price-per-person">par personne</span>
                            </div>
                        </div>
                    </div>

                    <div class="covoiturage-driver">
                        @if ($covoiturage->photo_chauffeur && $covoiturage->photo_chauffeur != 'images/default-avatar.jpg')
                            <img src="{{ asset($covoiturage->photo_chauffeur) }}"
                                alt="{{ $covoiturage->pseudo_chauffeur }}" class="driver-photo">
                        @else
                            <div class="driver-photo photo-placeholder">
                                <i class="fas fa-user svg-inline--fa"></i>
                            </div>
                        @endif
                        <div class="driver-info">
                            <h3>{{ $covoiturage->pseudo_chauffeur }}</h3>
                            <div class="driver-rating">
                                <span class="rating-value">{{ $covoiturage->note_chauffeur }}</span>
                                <span class="rating-stars">
                                    <!-- C'est le JS qui génére les étoiles -->
                                    @if (!is_numeric($covoiturage->note_chauffeur))
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
                                    <span class="from">{{ $covoiturage->lieu_depart }}</span>
                                    <span class="route-arrow">→</span>
                                    <span class="to">{{ $covoiturage->lieu_arrivee }}</span>
                                </div>
                                <div class="trip-date">
                                    <i class="fas fa-calendar"></i>
                                    {{ date('d/m/Y', strtotime($covoiturage->date_depart)) }}
                                </div>
                            </div>
                            <div class="trip-time">
                                <span class="departure-time">
                                    <i class="fas fa-clock"></i>
                                    Départ: {{ substr($covoiturage->heure_depart, 0, 5) }}
                                </span>
                                <span class="arrival-time">
                                    <i class="fas fa-clock"></i>
                                    Arrivée: {{ substr($covoiturage->heure_arrivee, 0, 5) }}
                                </span>
                            </div>
                        </div>

                        <div class="trip-eco-badge {{ $covoiturage->ecologique ? 'eco' : 'standard' }}">
                            @if ($covoiturage->ecologique)
                                <i class="fas fa-leaf"></i> Voyage écologique
                            @else
                                <i class="fas fa-car"></i> Voyage standard
                            @endif
                        </div>
                    </div>


                    <div class="covoiturage-booking">
                        <div class="trip-seats">
                            <i class="fas fa-user"></i>
                            <span>{{ $covoiturage->places_restantes }}
                                {{ $covoiturage->places_restantes > 1 ? 'places disponibles' : 'place disponible' }}</span>
                        </div>

                        <div class="trip-price">
                            <span class="price-value">{{ $covoiturage->prix }} crédits</span>
                            <span class="price-per-person">par personne</span>
                        </div>

                        <div class="booking-buttons">
                            <a href="{{ route('trips.confirm', ['id' => $covoiturage->id ?? 1]) }}"
                                class="btn-base btn-details" data-id="{{ $covoiturage->id ?? 1 }}">
                                Détails
                            </a>
                            <a href="{{ route('trips.participate', ['id' => $covoiturage->id ?? 1]) }}"
                                class="btn-base btn-participate">
                                Participer
                            </a>
                        </div>
                    </div>

                    <div class="mobile-buttons">
                        <a href="{{ route('trips.confirm', ['id' => $covoiturage->id ?? 1]) }}"
                            class="btn-base btn-details" data-id="{{ $covoiturage->id ?? 1 }}">
                            Détails
                        </a>
                        <a href="{{ route('trips.participate', ['id' => $covoiturage->id ?? 1]) }}"
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
    @endif

    <section class="presentation">
        <div class="text-content">
            <h2>Voyagez autrement avec EcoRide</h2>
            <p>EcoRide est une startup française engagée dans la réduction de l'impact environnemental des déplacements.
                Notre mission est de rendre le covoiturage accessible à tous tout en préservant notre planète.</p>

            <div class="features">
                <div class="feature">
                    <img src="{{ asset('images/ecolo.webp') }}" alt="Icône écologie">
                    <h3>Écologique</h3>
                    <p>Nous encourageons particulièrement les trajets en véhicules électriques</p>
                </div>
                <div class="feature">
                    <img src="{{ asset('images/econo.webp') }}" alt="Icône économie">
                    <h3>Économique</h3>
                    <p>Des trajets à prix réduits pour voyager malin</p>
                </div>
                <div class="feature">
                    <img src="{{ asset('images/commu.webp') }}" alt="Icône communauté">
                    <h3>Communautaire</h3>
                    <p>Rejoignez une communauté de voyageurs responsables</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Inclure la modale => détails des covoit -->
    @include('trips.trip-details-modal')
@endsection
