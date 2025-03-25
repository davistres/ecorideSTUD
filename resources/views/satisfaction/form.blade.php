@extends('layouts.app')

@section('title', 'Formulaire de satisfaction - EcoRide')

@section('content')
    <div class="dashboard-container">
        <div>
            <div>
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Formulaire de satisfaction</h2>
                    </div>

                    <div class="widget-content">
                        @if (isset($satisfaction) && isset($satisfaction->covoiturage))
                            <div class="trip-info">
                                <h3>Détails du trajet</h3>
                                <div class="trip-details-grid">
                                    <div>
                                        <p><strong>Départ :</strong> {{ $satisfaction->covoiturage->city_dep }}</p>
                                        <p><strong>Date :</strong>
                                            {{ \Carbon\Carbon::parse($satisfaction->covoiturage->departure_date)->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    <div>
                                        <p><strong>Arrivée :</strong> {{ $satisfaction->covoiturage->city_arr }}</p>
                                        <p><strong>Conducteur :</strong>
                                            {{ $satisfaction->covoiturage->chauffeur->utilisateur->pseudo }}</p>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('satisfaction.store', $satisfaction->satisfaction_id) }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label>Comment s'est passé votre trajet ?*</label>
                                    <div class="radio-option">
                                        <input type="radio" name="feeling" id="feeling_good" value="1" required>
                                        <label for="feeling_good">
                                            Le trajet s'est bien passé
                                        </label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" name="feeling" id="feeling_bad" value="0">
                                        <label for="feeling_bad">
                                            Le trajet s'est mal passé
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group" id="comment_group">
                                    <label for="comment">Commentaire obligatoire en cas de problème... Sinon, c'est
                                        optionnel!!!</label>
                                    <textarea id="comment" name="comment" rows="3"
                                        placeholder="Commentaire additionnel ou décrivez un problème rencontré..."></textarea>
                                    <small class="form-help-text">Dans tous les cas, un employé prendra en charge votre
                                        requête.</small>
                                </div>

                                <div class="form-group">
                                    <label for="note">Note du conducteur (optionnel)</label>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" name="note" id="star5" value="5">
                                            <label for="star5">5 ★</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" name="note" id="star4" value="4">
                                            <label for="star4">4 ★</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" name="note" id="star3" value="3">
                                            <label for="star3">3 ★</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" name="note" id="star2" value="2">
                                            <label for="star2">2 ★</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" name="note" id="star1" value="1">
                                            <label for="star1">1 ★</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="review">Commentaire (optionnel)</label>
                                    <textarea id="review" name="review" rows="3" placeholder="Partagez votre expérience avec ce conducteur..."></textarea>
                                    <small class="form-help-text">Maximum 1440 caractères. Votre commentaire sera publié sur
                                        le
                                        profil du conducteur.</small>
                                </div>

                                <div class="form-submit">
                                    <button type="submit" class="search-button">Soumettre</button>
                                </div>
                            </form>

                            <script>
                                // Covoit bien ou mal passé? => champs commentaire OBLIGATOIRE ou non en fonction du choix
                                document.querySelectorAll('input[name="feeling"]').forEach(radio => {
                                    radio.addEventListener('change', function() {
                                        if (this.value === '0') { // Trajet mal passé
                                            document.getElementById('comment').setAttribute('required', 'required');
                                        } else {
                                            document.getElementById('comment').removeAttribute('required');
                                        }
                                    });
                                });
                            </script>
                        @else
                            <div class="info-message">
                                <p>Le formulaire demandé n'est pas disponible ou a déjà été complété.</p>
                                <a href="{{ route('home') }}" class="btn-return-home">Retour à mon espace</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
