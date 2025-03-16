@extends('layouts.app')

@section('title', 'EcoRide - Page non trouvée')

@section('content')
    @php
        abort(404);
    @endphp

    <main class="main404">
        <section class="hero">
            <h1>404 - Page non trouvée</h1>
            <p>La page que vous recherchez n'existe pas.</p>
            <a href="{{ route('welcome') }}" class="btn404">Retour à l'accueil</a>
        </section>
    </main>
@endsection
