<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripsController extends Controller
{
    // Convertir un format HH:MM:SS en minutes
    private function timeToMinutes($timeString)
    {
        // Si c'est déjà un nombre, c'est ok
        if (is_numeric($timeString)) {
            return (int) $timeString;
        }

        // Si c'est HH:MM:SS ou HH:MM
        if (is_string($timeString)) {
            $parts = explode(':', $timeString);
            if (count($parts) >= 2) {
                $hours = (int) $parts[0];
                $minutes = (int) $parts[1];
                return $hours * 60 + $minutes;
            }
        }

        // Valeur par défaut///////////////////////////
        return 120;
    }

    // minutes en format heures et minutes
    private function formatDuration($minutes)
    {
        if (!is_numeric($minutes)) {
            $minutes = 0;
        }

        // Convertir en entier
        $minutes = (int) $minutes;

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $hours . ' h ' . $mins . ' min';
        } else {
            return $mins . ' min';
        }
    }

    public function index()
    {
        // Récupérer les données de session
        $data = [
            'covoiturages' => session('covoiturages', []),
            'lieu_depart' => session('lieu_depart'),
            'lieu_arrivee' => session('lieu_arrivee'),
            'date_recherche' => session('date_recherche'),
            'success' => session('success'),
            'error' => session('error'),
            'info' => session('info'),
            'suggestions' => session('suggestions'),
            'suggested_date' => session('suggested_date')
        ];

        // min/max pour les filtres
        $covoiturages = session('covoiturages', []);

        // Valeurs par défaut => A ne pas oublier au cas où////////////////////////////////////////
        $data['min_price'] = 0;
        $data['max_price'] = 100;
        $data['min_duration'] = 30;
        $data['max_duration'] = 120;

        // Formats d'affichage pour les durées => numérique
        $data['min_duration'] = is_numeric($data['min_duration']) ? (int)$data['min_duration'] : 30;
        $data['max_duration'] = is_numeric($data['max_duration']) ? (int)$data['max_duration'] : 120;

        $data['min_duration_formatted'] = $this->formatDuration($data['min_duration']);
        $data['max_duration_formatted'] = $this->formatDuration($data['max_duration']);

        if (count($covoiturages) >= 2) {
            $minPrice = PHP_INT_MAX;
            $maxPrice = 0;
            $minDuration = PHP_INT_MAX;
            $maxDuration = 0;

            foreach ($covoiturages as $covoiturage) {
                $price = $covoiturage->prix;
                $minPrice = min($minPrice, $price);
                $maxPrice = max($maxPrice, $price);

                $durationStr = $covoiturage->max_travel_time ?? '02:00:00'; // Valeur par défaut//////////////////
                $duration = $this->timeToMinutes($durationStr);
                $minDuration = min($minDuration, $duration);
                $maxDuration = max($maxDuration, $duration);
            }

            // valeurs valides?
            if ($minPrice < PHP_INT_MAX) {
                $data['min_price'] = $minPrice;
            }
            if ($maxPrice > 0) {
                $data['max_price'] = $maxPrice;
            }
            if ($minDuration < PHP_INT_MAX) {
                $data['min_duration'] = $minDuration;
                $data['min_duration_formatted'] = $this->formatDuration($minDuration);
            }
            if ($maxDuration > 0) {
                $data['max_duration'] = $maxDuration;
                $data['max_duration_formatted'] = $this->formatDuration($maxDuration);
            }
        }

        return view('trips.trips', $data);
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
                'COVOITURAGE.eco_travel as ecologique',
                'COVOITURAGE.max_travel_time as max_travel_time'
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

        // Photos + vérif des valeurs
        $covoiturages = $covoiturages->map(function ($item) {
            // max_travel_time => HH:MM:SS en minutes
            if (isset($item->max_travel_time)) {
                $item->max_travel_time = $this->timeToMinutes($item->max_travel_time);
            } else {
                $item->max_travel_time = 120;
            }

            // Photo du chauffeur
            \Illuminate\Support\Facades\Log::info('Photo du chauffeur', [
                'pseudo' => $item->pseudo_chauffeur,
                'photo_type' => gettype($item->photo_chauffeur),
                'photo_null' => $item->photo_chauffeur === null ? 'oui' : 'non',
                'photo_empty' => $item->photo_chauffeur === '' ? 'oui' : 'non',
                'photo_length' => is_string($item->photo_chauffeur) ? strlen($item->photo_chauffeur) : 'N/A'
            ]);

            // Résolution problème => Si la photo est en blob => la convertir en base64
            // Le format Base64 fonctionne sur toutes les plateformes et tous les navigateurs => évite les erreurs d'encodage!!!!!!!!!!!!!!!!!! A RETENIR!!!!!!!!!
            if ($item->photo_chauffeur !== null && $item->photo_chauffeur !== '') {
                try {
                    $photoData = $item->photo_chauffeur;
                    if (is_resource($photoData)) {
                        $photoData = stream_get_contents($photoData);
                    }

                    // type MIME? => c'est un standard qui identifie le format d'un fichier (JPEG, PNG, GIF, etc..)/////////////////////////////////////
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($photoData);

                    $encodedPhoto = base64_encode($photoData);

                    $item->photo_chauffeur_data = "data:{$mimeType};base64,{$encodedPhoto}";
                    $item->has_photo = true;

                    \Illuminate\Support\Facades\Log::info('Photo convertie avec succès', [
                        'pseudo' => $item->pseudo_chauffeur,
                        'mime_type' => $mimeType,
                        'encoded_length' => strlen($encodedPhoto)
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur lors de la conversion de la photo', [
                        'pseudo' => $item->pseudo_chauffeur,
                        'error' => $e->getMessage()
                    ]);

                    // En cas d'erreur => null
                    $item->photo_chauffeur = null;
                    $item->photo_chauffeur_data = null;
                    $item->has_photo = false;
                }
            } else {
                // Si pas de photo, on met aussi null pour que le Js affiche le placeholder par défaut
                $item->photo_chauffeur = null;
                $item->photo_chauffeur_data = null;
                $item->has_photo = false;
            }
            return $item;
        });

        // Redirection vers la page COVOITURAGE si: -on arrive de la page d'accueil + - il y a des résultats
        if ($fromWelcome) {
            $redirectRoute = 'trips.index';
        }

        session(['last_search_time' => time()]);

        $minPrice = 0;
        $maxPrice = 100;
        $minDuration = 30;
        $maxDuration = 120;

        // min/max pour les filtres
        if (count($covoiturages) >= 2) {
            $calcMinPrice = PHP_INT_MAX;
            $calcMaxPrice = 0;
            $calcMinDuration = PHP_INT_MAX;
            $calcMaxDuration = 0;

            foreach ($covoiturages as $covoiturage) {
                $price = $covoiturage->prix;
                $calcMinPrice = min($calcMinPrice, $price);
                $calcMaxPrice = max($calcMaxPrice, $price);

                $durationStr = $covoiturage->max_travel_time ?? '02:00:00'; // Valeur par défaut MAIS si max_travel_time est déjà en minutes (car converti plus haut), on l'utilise directement
                $duration = is_numeric($durationStr) ? (int)$durationStr : $this->timeToMinutes($durationStr);
                $calcMinDuration = min($calcMinDuration, $duration);
                $calcMaxDuration = max($calcMaxDuration, $duration);
            }

            // Valeur valide?
            if ($calcMinPrice < PHP_INT_MAX) {
                $minPrice = $calcMinPrice;
            }
            if ($calcMaxPrice > 0) {
                $maxPrice = $calcMaxPrice;
            }
            if ($calcMinDuration < PHP_INT_MAX) {
                $minDuration = $calcMinDuration;
            }
            if ($calcMaxDuration > 0) {
                $maxDuration = $calcMaxDuration;
            }
        }

        // Formats d'affichage pour les durées +> numériques
        $minDuration = is_numeric($minDuration) ? (int)$minDuration : 30;
        $maxDuration = is_numeric($maxDuration) ? (int)$maxDuration : 120;

        $minDurationFormatted = $this->formatDuration($minDuration);
        $maxDurationFormatted = $this->formatDuration($maxDuration);

        // Stocker les valeurs
        session([
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_duration' => $minDuration,
            'max_duration' => $maxDuration,
            'min_duration_formatted' => $minDurationFormatted,
            'max_duration_formatted' => $maxDurationFormatted
        ]);

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