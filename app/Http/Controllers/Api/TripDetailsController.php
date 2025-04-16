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

            // Avis pour le chauffeur de ce covoit
            $driverId = $covoiturage->driver_id;
            $reviews = Satisfaction::where('driver_id', $driverId)
                ->whereNotNull('review')
                ->whereNotNull('note')
                ->with('utilisateur:user_id,pseudo')
                ->orderBy('date', 'desc')
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
                    'driver_id' => 1,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Très bon conducteur, ponctuel et sympathique !',
                    'note' => 5,
                    'date' => '2025-03-15',
                    'utilisateur' => [
                        'user_id' => 2,
                        'pseudo' => 'PassOne'
                    ]
                ],
                [
                    'satisfaction_id' => 2,
                    'user_id' => 3,
                    'covoit_id' => $id,
                    'driver_id' => 1,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Bonne expérience, je recommande !',
                    'note' => 4,
                    'date' => '2025-03-20',
                    'utilisateur' => [
                        'user_id' => 3,
                        'pseudo' => 'PassTwo'
                    ]
                ],
                [
                    'satisfaction_id' => 3,
                    'user_id' => 4,
                    'covoit_id' => 5,
                    'driver_id' => 1,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Voyage très agréable',
                    'note' => 5,
                    'date' => '2025-02-10',
                    'utilisateur' => [
                        'user_id' => 4,
                        'pseudo' => 'PassThree'
                    ]
                ],
                [
                    'satisfaction_id' => 4,
                    'user_id' => 5,
                    'covoit_id' => 6,
                    'driver_id' => 1,
                    'feeling' => false,
                    'comment' => null,
                    'review' => 'Conduite un peu brusque',
                    'note' => 3,
                    'date' => '2025-01-05',
                    'utilisateur' => [
                        'user_id' => 5,
                        'pseudo' => 'PassFour'
                    ]
                ],
                [
                    'satisfaction_id' => 5,
                    'user_id' => 6,
                    'covoit_id' => 8,
                    'driver_id' => 1,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Parfait, à l\'heure et très sympa',
                    'note' => 5,
                    'date' => '2025-04-01',
                    'utilisateur' => [
                        'user_id' => 6,
                        'pseudo' => 'PassFive'
                    ]
                ],
                [
                    'satisfaction_id' => 6,
                    'user_id' => 7,
                    'covoit_id' => 9,
                    'driver_id' => 1,
                    'feeling' => true,
                    'comment' => null,
                    'review' => 'Excellent service',
                    'note' => 5,
                    'date' => '2024-12-15',
                    'utilisateur' => [
                        'user_id' => 7,
                        'pseudo' => 'PassSix'
                    ]
                ]
            ]
        ];

        return response()->json($data);
    }
}