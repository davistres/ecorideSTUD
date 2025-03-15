@extends('layouts.app')

@section('title', 'Bienvenue sur EcoRide')

@section('content')
    <section class="hero">
        <h1>Bienvenue sur EcoRide</h1>
        <p>La plateforme de covoiturage écologique</p>
    </section>

    <section class="search-section">
        <h1>Rechercher un itinéraire</h1>

        @if (isset($_GET['error']) || isset($_GET['suggested_date']))
            <div class="message-container">
                @if (isset($_GET['error']))
                    <div class="error-message">{{ $_GET['error'] }}</div>
                @endif

                @if (isset($_GET['suggested_date']))
                    <div class="info-message">
                        Aucun trajet trouvé pour la date sélectionnée.
                        <form action="{{ route('search.covoiturage') }}" method="POST" class="suggested-date-form">
                            @csrf
                            <input type="hidden" name="lieu_depart" value="{{ $_GET['departure'] ?? '' }}">
                            <input type="hidden" name="lieu_arrivee" value="{{ $_GET['arrival'] ?? '' }}">
                            <input type="hidden" name="date" value="{{ $_GET['suggested_date'] }}">
                            Essayez plutôt le <strong>{{ date('d/m/Y', strtotime($_GET['suggested_date'])) }}</strong>
                            <button type="submit" class="suggested-date-btn">Rechercher à cette date</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        <form class="search-form" action="{{ route('search.covoiturage') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="departure">Départ</label>
                <input type="text" id="departure" name="lieu_depart" placeholder="Ville de départ" required
                    value="{{ $_GET['departure'] ?? '' }}">
            </div>
            <div class="form-group">
                <label for="arrival">Arrivée</label>
                <input type="text" id="arrival" name="lieu_arrivee" placeholder="Ville d'arrivée" required
                    value="{{ $_GET['arrival'] ?? '' }}">
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required value="{{ $_GET['date'] ?? '' }}">
            </div>
            <button type="submit" class="search-button">Rechercher un trajet</button>
        </form>
    </section>

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
@endsection
