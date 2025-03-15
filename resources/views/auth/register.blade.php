@extends('layouts.app')

@section('content')
    <div class="auth-container">
        <h2>Inscription</h2>

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input id="pseudo" type="text" name="pseudo" value="{{ old('pseudo') }}" required autofocus>
                @error('pseudo')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password-confirm">Confirmer le mot de passe</label>
                <input id="password-confirm" type="password" name="password_confirmation" required>
            </div>

            <div class="info-message">
                En vous inscrivant, vous recevez automatiquement 20 crédits!
            </div>

            <button type="submit" class="auth-button">S'inscrire</button>

            <div class="auth-links">
                <p>Déjà inscrit? <a href="{{ route('login') }}">Connectez-vous</a></p>
            </div>
        </form>
    </div>
@endsection
