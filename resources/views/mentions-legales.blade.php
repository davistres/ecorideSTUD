@extends('layouts.app')

@section('title', 'Mentions Légales')

@section('content')
    <main class="mention">
        <section class="mentions-legales">
            <h2>Définitions</h2>
            <p><b>Utilisateur :</b> Internaute se connectant, utilisant le site susnommé.</p>
            <p><b>Informations personnelles :</b> « Les informations qui permettent, sous quelque forme que ce soit,
                directement ou non, l'identification des personnes physiques auxquelles elles s'appliquent » (article 4
                de la loi n° 78-17 du 6 janvier 1978).</p>
            <p>Les termes « données à caractère personnel », « personne concernée », « sous-traitant » et « données
                sensibles » ont le sens défini par le Règlement Général sur la Protection des Données (RGPD : n°
                2016-679).</p>

            <h2>1. Présentation du site internet.</h2>
            <p>En vertu de l'article 6 de la loi n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie
                numérique, il est précisé aux utilisateurs du site internet <a
                    href="{{ url('/') }}">{{ url('/') }}</a>
                l'identité des différents intervenants dans le cadre de sa réalisation et de son suivi :</p>
            <p><strong>Propriétaire</strong> : SARL EcoRide Capital social de 1000€ – 3839 avenue Georges Frêche 34470
                PÉROLS<br>
                <strong>Responsable publication</strong> : D'AMORE – davistres@yahoo.fr<br>
                <strong>Webmaster</strong> : D'AMORE – davistres@yahoo.fr<br>
                <strong>Hébergeur</strong> : InfinityFree – Kwikstaartlaan 42 Unit G1517 3704 GS Zeist The Netherlands
                0100000000<br>
                <strong>Délégué à la protection des données</strong> : José – jose@studi.com<br>
            </p>

            <h2>2. Conditions générales d’utilisation du site et des services proposés.</h2>
            <p>L’utilisation du site <a href="{{ url('/') }}">{{ url('/') }}</a> implique l’acceptation pleine et
                entière des conditions générales d’utilisation ci-après décrites. Ces conditions d’utilisation sont
                susceptibles d’être modifiées ou complétées à tout moment, les utilisateurs du site sont donc invités à
                les consulter régulièrement.</p>

            <h2>3. Description des services fournis.</h2>
            <p>Le site internet <a href="{{ url('/') }}">{{ url('/') }}</a> a pour objet de fournir une information
                concernant l’ensemble des activités de la société.
                <a href="{{ url('/') }}">{{ url('/') }}</a> s’efforce de fournir sur le site des informations aussi
                précises que possible.
            </p>

            <h2>4. Propriété intellectuelle et contrefaçons.</h2>
            <p><a href="{{ url('/') }}">{{ url('/') }}</a> est propriétaire des droits de propriété intellectuelle
                et
                détient les droits d’usage sur tous les éléments accessibles sur le site internet, notamment les textes,
                images, graphismes, logos, vidéos, icônes et sons.</p>

            <h2>5. Gestion des données personnelles.</h2>
            <p>Le Client est informé des réglementations concernant la communication marketing, la loi du 21 Juin 2014
                pour la confiance dans l’Economie Numérique, la Loi Informatique et Liberté du 06 Août 2004 ainsi que du
                Règlement Général sur la Protection des Données (RGPD : n° 2016-679).</p>
        </section>
    </main>
@endsection
