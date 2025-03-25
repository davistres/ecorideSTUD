@extends('layouts.app')

@section('title', 'Proposer un trajet - EcoRide')

@section('content')
    <div class="dashboard-container">
        <div>
            <div>
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Proposer un trajet</h2>
                    </div>

                    <div class="widget-content">
                        @if (session('success'))
                            <div class="alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (isset($vehicles) && count($vehicles) > 0)
                            <form action="{{ route('trip.store') }}" method="POST">
                                @csrf

                                <div class="form-grid">
                                    <div class="form-section">
                                        <h4>Lieu de départ</h4>
                                        <div class="form-group">
                                            <label for="departure_address">Adresse de départ*</label>
                                            <input type="text" id="departure_address" name="departure_address" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="add_dep_address">Complément d'adresse</label>
                                            <input type="text" id="add_dep_address" name="add_dep_address">
                                        </div>
                                        <div class="form-group">
                                            <label for="postal_code_dep">Code postal*</label>
                                            <input type="text" id="postal_code_dep" name="postal_code_dep" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="city_dep">Ville*</label>
                                            <input type="text" id="city_dep" name="city_dep" required>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h4>Lieu d'arrivée</h4>
                                        <div class="form-group">
                                            <label for="arrival_address">Adresse d'arrivée*</label>
                                            <input type="text" id="arrival_address" name="arrival_address" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="add_arr_address">Complément d'adresse</label>
                                            <input type="text" id="add_arr_address" name="add_arr_address">
                                        </div>
                                        <div class="form-group">
                                            <label for="postal_code_arr">Code postal*</label>
                                            <input type="text" id="postal_code_arr" name="postal_code_arr" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="city_arr">Ville*</label>
                                            <input type="text" id="city_arr" name="city_arr" required>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-grid">
                                    <div class="form-section">
                                        <h4>Date et heure</h4>
                                        <div class="form-group">
                                            <label for="departure_date">Date de départ*</label>
                                            <input type="date" id="departure_date" name="departure_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="departure_time">Heure de départ*</label>
                                            <input type="time" id="departure_time" name="departure_time" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="arrival_date">Date d'arrivée*</label>
                                            <input type="date" id="arrival_date" name="arrival_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="arrival_time">Heure d'arrivée estimée*</label>
                                            <input type="time" id="arrival_time" name="arrival_time" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="max_travel_time">Durée maximale du voyage*</label>
                                            <input type="time" id="max_travel_time" name="max_travel_time" required>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h4>Détails du trajet</h4>
                                        <div class="form-group">
                                            <label for="immat">Véhicule*</label>
                                            <select id="immat" name="immat" required>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->immat }}">{{ $vehicle->brand }}
                                                        {{ $vehicle->model }} ({{ $vehicle->immat }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="price">Prix par passager (en crédits)*</label>
                                            <div class="price-input-group">
                                                <input type="number" id="price" name="price" min="2" required>
                                                <span class="price-unit">crédits</span>
                                            </div>
                                            <small class="form-help-text">Minimum 2 crédits (dont 2 crédits prélevés par la
                                                plateforme)</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="n_tickets">Nombre de places disponibles*</label>
                                            <select id="n_tickets" name="n_tickets" required>
                                                @for ($i = 1; $i <= 9; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-submit">
                                    <button type="submit" class="search-button">Proposer ce trajet</button>
                                </div>
                            </form>
                        @else
                            <div class="info-message">
                                <p>Vous devez ajouter au moins un véhicule avant de pouvoir proposer un trajet.</p>
                                <a href="{{ route('vehicle.create') }}" class="add-vehicle-btn">Ajouter un véhicule</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
