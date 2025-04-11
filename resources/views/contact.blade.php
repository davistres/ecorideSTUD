@extends('layouts.app')

@section('title', 'Contactez-nous')

@section('content')
    <section class="login-section">
        <h2>Contactez-nous</h2>
        <p>* champs obligatoire</p>

        <!-- erreurs -->
        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- succès -->
        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form class="login-form" id="contact-form" method="POST" action="{{ route('contact.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nom *</label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label for="subject">Sujet *</label>
                <select id="subject" name="subject" required>
                    <option value="">Sélectionnez un sujet</option>
                    <option value="support" {{ old('subject') == 'support' ? 'selected' : '' }}>Support technique
                    </option>
                    <option value="reservation" {{ old('subject') == 'reservation' ? 'selected' : '' }}>Problème de
                        réservation</option>
                    <option value="other" {{ old('subject') == 'other' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" required rows="5" placeholder="Votre message...">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="search-button">Envoyer le message</button>
        </form>
    </section>
@endsection
