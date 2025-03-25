@extends('layouts.app')

@section('title', 'Modifier mon véhicule - EcoRide')

@section('content')
    <div class="dashboard-container">
        <div>
            <div>
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Modifier mon véhicule</h2>
                    </div>

                    <div class="widget-content">
                        @if (session('success'))
                            <div class="alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (isset($vehicle))
                            <form action="{{ route('vehicle.update', $vehicle->immat) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="immat">Immatriculation*</label>
                                    <input type="text" id="immat" name="immat" value="{{ $vehicle->immat }}"
                                        readonly>
                                    <small class="form-help-text">L'immatriculation ne peut pas être modifiée.</small>
                                </div>

                                <div class="form-group">
                                    <label for="date_first_immat">Date de première immatriculation*</label>
                                    <input type="date" id="date_first_immat" name="date_first_immat"
                                        value="{{ $vehicle->date_first_immat->format('Y-m-d') }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="brand">Marque*</label>
                                    <input type="text" id="brand" name="brand" value="{{ $vehicle->brand }}"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="model">Modèle*</label>
                                    <input type="text" id="model" name="model" value="{{ $vehicle->model }}"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="color">Couleur*</label>
                                    <input type="text" id="color" name="color" value="{{ $vehicle->color }}"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="n_place">Nombre de places*</label>
                                    <input type="number" id="n_place" name="n_place" min="2" max="9"
                                        value="{{ $vehicle->n_place }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="energie">Énergie*</label>
                                    <select id="energie" name="energie" required>
                                        <option value="Electrique"
                                            {{ $vehicle->energie == 'Electrique' ? 'selected' : '' }}>Électrique</option>
                                        <option value="Hybride" {{ $vehicle->energie == 'Hybride' ? 'selected' : '' }}>
                                            Hybride</option>
                                        <option value="Diesel/Gazole"
                                            {{ $vehicle->energie == 'Diesel/Gazole' ? 'selected' : '' }}>Diesel/Gazole
                                        </option>
                                        <option value="Essence" {{ $vehicle->energie == 'Essence' ? 'selected' : '' }}>
                                            Essence</option>
                                        <option value="GPL" {{ $vehicle->energie == 'GPL' ? 'selected' : '' }}>GPL
                                        </option>
                                    </select>
                                </div>

                                <div class="form-submit">
                                    <button type="submit" class="search-button">Enregistrer les modifications</button>
                                    <a href="{{ route('home') }}" class="btn-return-home">Annuler</a>
                                </div>
                            </form>
                        @else
                            <div class="info-message">
                                <p>Le véhicule demandé n'existe pas ou vous n'avez pas les droits pour le modifier.</p>
                                <a href="{{ route('home') }}" class="btn-return-home">Retour à mon espace</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
