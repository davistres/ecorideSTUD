@extends('layouts.app')

@section('title', 'Modifier mes préférences - EcoRide')

@section('content')
    <div class="dashboard-container">
        <div>
            <div>
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Modifier mes préférences</h2>
                    </div>

                    <div class="widget-content">
                        @if (session('success'))
                            <div class="alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form action="{{ route('preferences.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="pref_smoke">Préférence fumeur*</label>
                                <select id="pref_smoke" name="pref_smoke" required>
                                    <option value="Fumeur"
                                        {{ isset($chauffeur) && $chauffeur->pref_smoke == 'Fumeur' ? 'selected' : '' }}>
                                        Fumeur</option>
                                    <option value="Non-fumeur"
                                        {{ isset($chauffeur) && $chauffeur->pref_smoke == 'Non-fumeur' ? 'selected' : '' }}>
                                        Non-fumeur</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pref_pet">Préférence animaux*</label>
                                <select id="pref_pet" name="pref_pet" required>
                                    <option value="Acceptés"
                                        {{ isset($chauffeur) && $chauffeur->pref_pet == 'Acceptés' ? 'selected' : '' }}>
                                        Animaux acceptés</option>
                                    <option value="Non-acceptés"
                                        {{ isset($chauffeur) && $chauffeur->pref_pet == 'Non-acceptés' ? 'selected' : '' }}>
                                        Animaux non acceptés</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pref_libre">Autres préférences ou informations</label>
                                <textarea id="pref_libre" name="pref_libre" rows="3"
                                    placeholder="Exemple: Musique classique, conversation limitée, etc.">{{ isset($chauffeur) ? $chauffeur->pref_libre : '' }}</textarea>
                            </div>

                            <div class="form-submit">
                                <button type="submit" class="search-button">Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
