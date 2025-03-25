@extends('layouts.app')

@section('title', 'Ajouter un véhicule - EcoRide')

@section('content')
    <div class="dashboard-container">
        <div>
            <div>
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Ajouter un véhicule</h2>
                    </div>
                    <div class="widget-content">
                        <form action="{{ route('vehicle.store') }}" method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="immat">Immatriculation*</label>
                                <input type="text" id="immat" name="immat" required>
                            </div>

                            <div class="form-group">
                                <label for="date_first_immat">Date de première immatriculation*</label>
                                <input type="date" id="date_first_immat" name="date_first_immat" required>
                            </div>

                            <div class="form-group">
                                <label for="brand">Marque*</label>
                                <input type="text" id="brand" name="brand" required>
                            </div>

                            <div class="form-group">
                                <label for="model">Modèle*</label>
                                <input type="text" id="model" name="model" required>
                            </div>

                            <div class="form-group">
                                <label for="color">Couleur*</label>
                                <input type="text" id="color" name="color" required>
                            </div>

                            <div class="form-group">
                                <label for="n_place">Nombre de places*</label>
                                <input type="number" id="n_place" name="n_place" min="2" max="9" required>
                            </div>

                            <div class="form-group">
                                <label for="energie">Énergie*</label>
                                <select id="energie" name="energie" required>
                                    <option value="Electrique">Électrique</option>
                                    <option value="Hybride">Hybride</option>
                                    <option value="Diesel/Gazole">Diesel/Gazole</option>
                                    <option value="Essence">Essence</option>
                                    <option value="GPL">GPL</option>
                                </select>
                            </div>

                            <div class="form-submit">
                                <button type="submit" class="search-button">Ajouter mon véhicule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
