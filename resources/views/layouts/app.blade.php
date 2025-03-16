<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        use Illuminate\Support\Facades\Auth;
    @endphp

    <title>{{ config('app.name', 'EcoRide') }}</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

    @if (config('app.env') === 'production')
        <link rel="stylesheet" href="{{ secure_asset('css/main.css') }}">
    @else
        <link rel="stylesheet" href="{{ url('css/main.css') }}">
    @endif
</head>

<body>
    <!-- HEADER -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="{{ route('welcome') }}">EcoRide</a>
            </div>
            <div class="burger" id="burger">
                <div></div>
                <div></div>
                <div></div>
            </div>
            <ul class="nav-links">
                <li><a href="{{ route('welcome') }}">Accueil</a></li>
                <li><a href="{{ route('trips.index') }}">Covoiturage</a></li>
                <li><a href="{{ route('contact') }}">Contact</a></li>

                @if (Auth::guard('admin')->check())
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="user-nom">
                            ADMIN
                        </a>
                    </li>
                @elseif(Auth::guard('employe')->check())
                    <li>
                        <a href="{{ route('employe.dashboard') }}" class="user-nom">
                            {{ Auth::guard('employe')->user()->name }}
                        </a>
                    </li>
                @elseif(Auth::guard('web')->check())
                    <li>
                        <a href="{{ route('home') }}" class="user-nom">
                            {{ Auth::guard('web')->user()->pseudo }}
                        </a>
                    </li>
                @endif

                @if (Auth::guard('admin')->check() || Auth::guard('employe')->check() || Auth::guard('web')->check())
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cta-button">Déconnexion</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}" class="cta-button">Connexion</a></li>
                @endif
            </ul>
        </nav>

        <div class="mobile-menu" id="mobile-menu">
            <a href="{{ route('welcome') }}">Accueil</a>
            <a href="{{ route('trips.index') }}">Covoiturage</a>
            <a href="{{ route('contact') }}">Contact</a>

            @if (Auth::guard('admin')->check())
                <a href="{{ route('admin.dashboard') }}" class="user-nom">ADMIN</a>
            @elseif(Auth::guard('employe')->check())
                <a href="{{ route('employe.dashboard') }}"
                    class="user-nom">{{ Auth::guard('employe')->user()->name }}</a>
            @elseif(Auth::guard('web')->check())
                <a href="{{ route('home') }}" class="user-nom">{{ Auth::guard('web')->user()->pseudo }}</a>
            @endif

            @if (Auth::guard('admin')->check() || Auth::guard('employe')->check() || Auth::guard('web')->check())
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">
                    Déconnexion
                </a>
                <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @else
                <a href="{{ route('login') }}">Connexion</a>
            @endif

            <div class="close-menu" id="close-menu">&times;</div>
        </div>
    </header>

    <!-- MAIN QUI CHANGE -->
    <main>
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="image-banner">
            <img src="{{ asset('images/pexels-cottonbro-5329298.jpg') }}" alt="Covoiturage EcoRide" class="main-image">
        </div>
        <div class="footer footer-content">
            <p class="copyright">&copy; {{ date('Y') }} EcoRide</p>
            <nav class="footer-nav">
                <a href="{{ route('mentions-legales') }}">Mentions légales</a>
                <a href="mailto:maildelentreprise@ecoride.fr">maildelentreprise@ecoride.fr</a>
            </nav>
        </div>
    </footer>

    <script defer src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script defer src="{{ asset('js/script.js') }}"></script>
</body>

</html>
