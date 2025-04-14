<?php
// Détails d'un covoit
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Covoiturage;
use App\Models\Satisfaction;
use App\Models\Chauffeur;
use App\Models\Voiture;
use App\Models\Utilisateur;

class TripDetailsController extends Controller
{
    public function getDetails($id)
    {
        try {
            $covoiturage = Covoiturage::with([
                'chauffeur.utilisateur',
                'voiture',
                'confirmations',
            ])->find($id);

            if (!$covoiturage) {
                return $this->getTestData($id);
            }

            $reviews = Satisfaction::where('covoit_id', $id)
                ->whereNotNull('review')
                ->whereNotNull('note')
                ->with('utilisateur:user_id,pseudo')
                ->get();

            $placesRestantes = $covoiturage->n_tickets - $covoiturage->confirmations->count();

            $data = $covoiturage->toArray();
            $data['places_restantes'] = $placesRestantes;
            $data['reviews'] = $reviews;

            return response()->json($data);
        } catch (\Exception $e) {
            return $this->getTestData($id);
        }
    }

    private function getTestData($id)
    {
        $data = [
            'covoit_id' => $id,
            'city_dep' => 'PARIS',
            'city_arr' => 'MARSEILLE',
            'departure_address' => '123 Avenue des Gobelins',
            'add_dep_address' => null,
            'postal_code_dep' => '75013',
            'arrival_address' => '25 Quai du Port',
            'add_arr_address' => null,
            'postal_code_arr' => '13002',
            'departure_date' => '2025-04-08',
            'arrival_date' => '2025-04-08',
            'departure_time' => '07:00:00',
            'arrival_time' => '14:30:00',
            'max_travel_time' => '08:00:00',
            'price' => 69,
            'n_tickets' => 4,
            'places_restantes' => 4,
            'eco_travel' => false,
            'immat' => 'AB123CD',
            'chauffeur' => [
                'driver_id' => 1,
                'user_id' => 1,
                'pref_smoke' => 'Non-fumeur',
                'pref_pet' => 'Acceptés',
                'pref_libre' => 'Aime la musique classique',
                'moy_note' => 4.8,
                'utilisateur' => [
                    'user_id' => 1,
                    'pseudo' => 'dd',
                    'mail' => 'davistres@yahoo.fr',
                    'profile_photo' => null,
                    'role' => 'Passager'
                ]
            ],
            'voiture' => [
                'immat' => 'AB123CD',
                'driver_id' => 1,
                'date_first_immat' => '2020-05-15',
                'brand' => 'Renault',
                'model' => 'Clio',
                'color' => 'Bleu',
                'n_place' => 5,
                'energie' => 'Essence'
            ],
            'reviews' => [
                [
                    'satisfaction_id' => 1,
                    'user_id' => 2,
                    'covoit_id' => $id,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Très bon conducteur, ponctuel et sympathique !',
                    'note' => 5,
                    'date' => '2025-03-15',
                    'utilisateur' => [
                        'user_id' => 2,
                        'pseudo' => 'dada'
                    ]
                ],
                [
                    'satisfaction_id' => 2,
                    'user_id' => 3,
                    'covoit_id' => $id,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Bonne expérience, je recommande !',
                    'note' => 4,
                    'date' => '2025-03-20',
                    'utilisateur' => [
                        'user_id' => 3,
                        'pseudo' => 'damdav1'
                    ]
                ]
            ]
        ];

        return response()->json($data);
    }
}