@extends('layouts.app')

@section('content')
    <div class="auth-container">
        <h2>Connexion</h2>

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
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

            <button type="submit" class="auth-button">Se connecter</button>

            <div class="auth-links">
                <p>Pas encore de compte? <a href="{{ route('register') }}">Inscrivez-vous</a></p>
            </div>
        </form>
    </div>
@endsection
