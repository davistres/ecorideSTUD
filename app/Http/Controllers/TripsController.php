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

        // En fonction de l'URL d'où on vient, redigiger en conséquance => vers trips (par défaut) ou vers welcome
        $referer = $request->headers->get('referer');
        $redirectRoute = 'trips.index';
        $fromWelcome = false;

        if (strpos($referer, '/welcome') !== false || $referer == url('/')) {
            $redirectRoute = 'welcome';
            $fromWelcome = true;
        }

        // Les villes existent?
        // Pour le moment, je fais comme ça, mais le mieux c'est d'avoir une api ou un fichier json avec tous les noms de villes... Mais il faudra rendre cela non sensible à la casse et au accent... Et le TOP serait l'autocomplétion!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $villeDepart = DB::table('COVOITURAGE')
            ->where('city_dep', $validated['lieu_depart'])
            ->where('cancelled', 0)
            ->where('trip_completed', 0)
            ->first();

        $villeArrivee = DB::table('COVOITURAGE')
            ->where('city_arr', $validated['lieu_arrivee'])
            ->where('cancelled', 0)
            ->where('trip_completed', 0)
            ->first();

        if (!$villeDepart && !$villeArrivee) {
            return redirect()->route($redirectRoute)
                ->with('error', 'Les villes de départ et d\'arrivée ne correspondent à aucun covoiturage disponible. Vérifiez l\'orthographe ou essayez d\'autres villes.');
        } else if (!$villeDepart) {
            return redirect()->route($redirectRoute)
                ->with('error', 'La ville de départ ne correspond à aucun covoiturage disponible. Vérifiez l\'orthographe ou essayez une autre ville.');
        } else if (!$villeArrivee) {
            return redirect()->route($redirectRoute)
                ->with('error', 'La ville d\'arrivée ne correspond à aucun covoiturage disponible. Vérifiez l\'orthographe ou essayez une autre ville.');
        }

        // Vérifie si le covoit existe entre ces deux villes
        $trajetExiste = DB::table('COVOITURAGE')
            ->where('city_dep', $validated['lieu_depart'])
            ->where('city_arr', $validated['lieu_arrivee'])
            ->where('cancelled', 0)
            ->where('trip_completed', 0)
            ->first();

        if (!$trajetExiste) {
            return redirect()->route($redirectRoute)
                ->with('error', 'Aucun covoiturage n\'est disponible entre ' . $validated['lieu_depart'] . ' et ' . $validated['lieu_arrivee'] . '. Essayez d\'autres villes.');
        }

        // Recherche la date la plus loin dans la base de donnée
        $datePlusLointaine = DB::table('COVOITURAGE')
            ->where('city_dep', $validated['lieu_depart'])
            ->where('city_arr', $validated['lieu_arrivee'])
            ->where('cancelled', 0)
            ->where('trip_completed', 0)
            ->orderBy('departure_date', 'desc')
            ->first();

        // Si c'est trop loin
        if ($datePlusLointaine && $validated['date'] > $datePlusLointaine->departure_date) {
            return redirect()->route($redirectRoute)
                ->with('error', 'Aucun covoiturage n\'est disponible pour une date aussi lointaine. Le covoiturage le plus éloigné est le ' . date('d/m/Y', strtotime($datePlusLointaine->departure_date)) . '. Veuillez réessayer plus tard ou choisir une date plus proche.')
                ->with('suggested_date', $datePlusLointaine->departure_date)
                ->with('lieu_depart', $validated['lieu_depart'])
                ->with('lieu_arrivee', $validated['lieu_arrivee']);
        }

        // covoit correspondants aux critères?
        $covoiturages = DB::table('COVOITURAGE')
            ->join('CHAUFFEUR', 'COVOITURAGE.driver_id', '=', 'CHAUFFEUR.driver_id')
            ->join('UTILISATEUR', 'CHAUFFEUR.user_id', '=', 'UTILISATEUR.user_id')
            ->join('VOITURE', 'COVOITURAGE.immat', '=', 'VOITURE.immat')
            ->select(
                'COVOITURAGE.covoit_id as id',
                'UTILISATEUR.pseudo as pseudo_chauffeur',
                'CHAUFFEUR.moy_note as note_chauffeur',
                'UTILISATEUR.profile_photo as photo_chauffeur',
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
            ->where('COVOITURAGE.cancelled', 0)
            ->where('COVOITURAGE.trip_completed', 0)
            ->get();

        // Calcule des places restantes (en fonction des confirmations)
        $covoiturages = $covoiturages->map(function ($item) {
            $confirmations = DB::table('CONFIRMATION')
                ->where('covoit_id', $item->id)
                ->count();

            $item->places_restantes = $item->places_restantes - $confirmations;
            return $item;
        });

        // Que les covoit avec des places restantes
        $covoiturages = $covoiturages->filter(function ($item) {
            return $item->places_restantes > 0;
        });

        if ($covoiturages->isEmpty()) {
            // ça n'était pas demandé mais je me suis permi de mettre en place cela:
            $dateRecherche = new \DateTime($validated['date']);
            $dateMoins7 = (clone $dateRecherche)->modify('-7 days')->format('Y-m-d');
            $datePlus7 = (clone $dateRecherche)->modify('+7 days')->format('Y-m-d');
            //Ainsi, ça proposera juste les covoit les plus proches de la date demandée (en fonction des villes) mais dans une distance de 7 jours... Ainsi, ça reste raisonnable.

            // Covoit avant la date demandée (jusqu'à -7 jours)
            $covoituragesAvant = DB::table('COVOITURAGE')
                ->where('city_dep', $validated['lieu_depart'])
                ->where('city_arr', $validated['lieu_arrivee'])
                ->where('departure_date', '<', $validated['date'])
                ->where('departure_date', '>=', $dateMoins7)
                ->where('cancelled', 0)
                ->where('trip_completed', 0)
                ->orderBy('departure_date', 'desc')
                ->get();

            // Covoit après la date demandée (jusqu'à +7 jours)
            $covoituragesApres = DB::table('COVOITURAGE')
                ->where('city_dep', $validated['lieu_depart'])
                ->where('city_arr', $validated['lieu_arrivee'])
                ->where('departure_date', '>', $validated['date'])
                ->where('departure_date', '<=', $datePlus7)
                ->where('cancelled', 0)
                ->where('trip_completed', 0)
                ->orderBy('departure_date', 'asc')
                ->get();

            // Que les covoit avec des places restantes
            $covoituragesAvant = $covoituragesAvant->filter(function ($item) {
                $confirmations = DB::table('CONFIRMATION')
                    ->where('covoit_id', $item->covoit_id)
                    ->count();
                return ($item->n_tickets - $confirmations) > 0;
            });

            $covoituragesApres = $covoituragesApres->filter(function ($item) {
                $confirmations = DB::table('CONFIRMATION')
                    ->where('covoit_id', $item->covoit_id)
                    ->count();
                return ($item->n_tickets - $confirmations) > 0;
            });

            // Suggestions en fonction des dates
            $dateGroups = [];

            // Avant la date recherchée
            if (!$covoituragesAvant->isEmpty()) {
                foreach ($covoituragesAvant as $covoit) {
                    $date = $covoit->departure_date;
                    $formattedDate = date('d/m/Y', strtotime($date));
                    $diff = 'J-' . $dateRecherche->diff(new \DateTime($date))->days;

                    if (!isset($dateGroups[$date])) {
                        $dateGroups[$date] = [
                            'date' => $date,
                            'formatted_date' => $formattedDate,
                            'diff' => $diff,
                            'count' => 1
                        ];
                    } else {
                        $dateGroups[$date]['count']++;
                    }
                }
            }

            // Après la date
            if (!$covoituragesApres->isEmpty()) {
                foreach ($covoituragesApres as $covoit) {
                    $date = $covoit->departure_date;
                    $formattedDate = date('d/m/Y', strtotime($date));
                    $diff = 'J+' . $dateRecherche->diff(new \DateTime($date))->days;

                    if (!isset($dateGroups[$date])) {
                        $dateGroups[$date] = [
                            'date' => $date,
                            'formatted_date' => $formattedDate,
                            'diff' => $diff,
                            'count' => 1
                        ];
                    } else {
                        $dateGroups[$date]['count']++;
                    }
                }
            }

            $suggestions = array_values($dateGroups);

            if (!empty($suggestions)) {
                // Redirection vers la page COVOITURAGE si: - on arrive de la page d'accueil + - il y a des suggestions
                if ($fromWelcome) {
                    $redirectRoute = 'trips.index';
                }

                return redirect()->route($redirectRoute)
                    ->with('info', 'Nous n\'avons pas de covoiturage à la date recherchée.')
                    ->with('suggestions', $suggestions)
                    ->with('lieu_depart', $validated['lieu_depart'])
                    ->with('lieu_arrivee', $validated['lieu_arrivee'])
                    ->with('date_recherche', $validated['date']);
            }

            // Aucune suggestion
            return redirect()->route($redirectRoute)
                ->with('error', 'Aucun covoiturage disponible entre ' . $validated['lieu_depart'] . ' et ' . $validated['lieu_arrivee'] . ' à la date du ' . date('d/m/Y', strtotime($validated['date'])) . ' ni dans les 7 jours avant ou après.');
        }

        // Photo avatar par défaut
        $covoiturages = $covoiturages->map(function ($item) {
            $item->photo_chauffeur = 'images/default-avatar.jpg';
            return $item;
        });

        // Redirection vers la page COVOITURAGE si: -on arrive de la page d'accueil + - il y a des résultats
        if ($fromWelcome) {
            $redirectRoute = 'trips.index';
        }

        return redirect()->route($redirectRoute)
            ->with('covoiturages', $covoiturages)
            ->with('success', count($covoiturages) . ' covoiturage(s) correspond(ent) à votre recherche.')
            ->with('lieu_depart', $validated['lieu_depart'])
            ->with('lieu_arrivee', $validated['lieu_arrivee'])
            ->with('date_recherche', $validated['date']);
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