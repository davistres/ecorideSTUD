<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripsController extends Controller
{
    public function index()
    {
        return view('trips.trips');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'lieu_depart' => 'required|string',
            'lieu_arrivee' => 'required|string',
            'date' => 'required|date',
        ]);

        // covoit correspondants aux critères?
        $covoiturages = DB::table('COVOITURAGE')
            ->join('CHAUFFEUR', 'COVOITURAGE.driver_id', '=', 'CHAUFFEUR.driver_id')
            ->join('UTILISATEUR', 'CHAUFFEUR.user_id', '=', 'UTILISATEUR.user_id')
            ->join('VOITURE', 'COVOITURAGE.immat', '=', 'VOITURE.immat')
            ->select(
                'COVOITURAGE.covoit_id as id',
                'UTILISATEUR.pseudo as pseudo_chauffeur',
                'CHAUFFEUR.moy_note as note_chauffeur',
                'CHAUFFEUR.idphoto as photo_chauffeur',
                'COVOITURAGE.city_dep as lieu_depart',
                'COVOITURAGE.city_arr as lieu_arrivee',
                'COVOITURAGE.departure_date as date_depart',
                'COVOITURAGE.departure_time as heure_depart',
                'COVOITURAGE.arrival_time as heure_arrivee',
                'COVOITURAGE.price as prix',
                'COVOITURAGE.n_tickets as places_restantes',
                'COVOITURAGE.eco_travel as ecologique'
            )
            ->where('COVOITURAGE.city_dep', $validated['lieu_depart'])
            ->where('COVOITURAGE.city_arr', $validated['lieu_arrivee'])
            ->where('COVOITURAGE.departure_date', $validated['date'])
            ->where('COVOITURAGE.n_tickets', '>', 0)
            ->get();

        if ($covoiturages->isEmpty()) {
            // Rechercher le prochain covoit disponible
            $nextCovoiturage = DB::table('COVOITURAGE')
                ->where('city_dep', $validated['lieu_depart'])
                ->where('city_arr', $validated['lieu_arrivee'])
                ->where('departure_date', '>', $validated['date'])
                ->where('n_tickets', '>', 0)
                ->orderBy('departure_date', 'asc')
                ->first();

            if ($nextCovoiturage) {
                return redirect()->route('trips.index')
                    ->with('info', 'Aucun trajet trouvé à cette date.')
                    ->with('suggested_date', $nextCovoiturage->departure_date)
                    ->with('lieu_depart', $validated['lieu_depart'])
                    ->with('lieu_arrivee', $validated['lieu_arrivee']);
            }

            return redirect()->route('trips.index')
                ->with('error', 'Aucun trajet trouvé entre ces villes.');
        }

        // Pas de photo? => utiliser une image par défaut (A ne pas oublier)////////////////////////////////
        $covoiturages = $covoiturages->map(function ($item) {
            if (empty($item->photo_chauffeur)) {
                $item->photo_chauffeur = 'images/default-avatar.jpg';
            }
            return $item;
        });

        return view('trips.trips')->with('covoiturages', $covoiturages);
    }

    public function show($id)
    {
        // Afficher les détails d'un covoit => à faire
        return view('trips.show', ['id' => $id]);
    }

    public function participate($id)
    {
        // code pour participer à un covoiturage => à faire
        return redirect()->route('trips.show', ['id' => $id])
            ->with('success', 'Votre demande de participation a été enregistrée.');
    }
}
