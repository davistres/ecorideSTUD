<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionExpirationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ça, c'est très bien mais à terme, il faut trouver la solution pour que même si il y a un changement de page (pour la double confirmation), la recherche reste en session (ou utiliser des modales)
        if (Session::has('covoiturages') || Session::has('lieu_depart') || Session::has('lieu_arrivee') || Session::has('date_recherche')) {
            // Vérifier si un timestamp existe
            if (!Session::has('last_search_time')) {
                // Si non, définir celui-ci
                Session::put('last_search_time', time());
            } else {
                // Si oui, vérifier le délai
                $lastSearchTime = Session::get('last_search_time');
                $currentTime = time();
                $expirationTime = 1800; // 30 minutes en secondes

                if (($currentTime - $lastSearchTime) > $expirationTime) {
                    // Délai est dépassé => supprimer
                    Session::forget(['covoiturages', 'lieu_depart', 'lieu_arrivee', 'date_recherche',
                                    'success', 'error', 'info', 'suggestions', 'suggested_date', 'last_search_time']);
                }
            }
        }

        return $next($request);
    }
}