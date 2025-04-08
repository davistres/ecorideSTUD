<?php

namespace App\Http\Controllers;

use App\Models\Covoiturage;
use App\Models\Chauffeur;
use App\Models\Voiture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TripController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    // champ d'heure => Erreur H:i
    private function formatTimeFields(Request $request)
    {
        $timeFields = ['departure_time', 'arrival_time', 'max_travel_time'];

        foreach ($timeFields as $field) {
            if ($request->has($field)) {
                $time = $request->input($field);

                if (preg_match('/^([0-9]{1,2}):([0-9]{2})$/', $time)) {
                    continue;
                }

                try {
                    $parts = explode(':', $time);
                    if (count($parts) >= 2) {
                        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        $minutes = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                        $formattedTime = "{$hours}:{$minutes}";

                        $request->merge([$field => $formattedTime]);

                        Log::info("Champ {$field} formaté : {$time} -> {$formattedTime}");
                    }
                } catch (\Exception $e) {
                    Log::warning("Erreur lors du formatage du champ {$field}", [
                        'value' => $time,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $request;
    }

    public function store(Request $request)
    {
        try {
            Log::info('Données reçues pour la création d\'un trajet', $request->all());

            // formatage du champs d'heure
            $request = $this->formatTimeFields($request);

            $validated = $request->validate([
                'departure_address' => 'required|string|max:255',
                'add_dep_address' => 'nullable|string|max:255',
                'postal_code_dep' => 'required|string|max:10',
                'city_dep' => 'required|string|max:100',
                'arrival_address' => 'required|string|max:255',
                'add_arr_address' => 'nullable|string|max:255',
                'postal_code_arr' => 'required|string|max:10',
                'city_arr' => 'required|string|max:100',
                'departure_date' => 'required|date|after_or_equal:today',
                'arrival_date' => 'required|date|after_or_equal:departure_date',
                'departure_time' => 'required|date_format:H:i',
                'arrival_time' => 'required|date_format:H:i',
                'max_travel_time' => 'required|date_format:H:i',
                'immat' => 'required|exists:VOITURE,immat',
                'price' => 'required|integer|min:2',
                'n_tickets' => 'required|integer|min:1|max:9',
            ]);

            Log::info('Validation réussie', $validated);

            $now = now();
            $departureDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $validated['departure_date'] . ' ' . $validated['departure_time']);

            // entre l\'heure de la création du covoiturage et l\'heure du départ. Un délai minimum de 4 heures est requis.
            if ($departureDateTime->format('Y-m-d') === $now->format('Y-m-d') && $departureDateTime->diffInMinutes($now) < 240) {
                Log::warning('Tentative de création d\'un trajet avec un départ trop proche', [
                    'departure_datetime' => $departureDateTime->format('Y-m-d H:i'),
                    'current_datetime' => $now->format('Y-m-d H:i'),
                    'diff_minutes' => $departureDateTime->diffInMinutes($now)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas créer ce covoiturage car vous ne respectez pas les délais entre l\'heure de la création du covoiturage et l\'heure du départ. Un délai minimum de 4 heures est requis.'
                ], 422);
            }

            $user = Auth::user();
            $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

            if (!$chauffeur) {
                Log::warning('Tentative de création de trajet par un non-conducteur', ['user_id' => $user->user_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être conducteur pour proposer un trajet.'
                ], 403);
            }

            Log::info('Conducteur trouvé', ['driver_id' => $chauffeur->driver_id]);

            $vehicle = Voiture::where('immat', $validated['immat'])
                ->where('driver_id', $chauffeur->driver_id)
                ->first();

            if (!$vehicle) {
                Log::warning('Tentative de création de trajet avec un véhicule qui n\'appartient pas au conducteur', [
                    'driver_id' => $chauffeur->driver_id,
                    'immat' => $validated['immat']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ce véhicule ne vous appartient pas.'
                ], 403);
            }

            Log::info('Véhicule trouvé', ['immat' => $vehicle->immat, 'brand' => $vehicle->brand, 'model' => $vehicle->model]);

            // Création du covoit
            try {
                DB::beginTransaction();

                $departureDate = $validated['departure_date'];
                $arrivalDate = $validated['arrival_date'];
                $departureTime = $validated['departure_time'];
                $arrivalTime = $validated['arrival_time'];
                $maxTravelTime = $validated['max_travel_time'];

                Log::info('Création du covoiturage', [
                    'driver_id' => $chauffeur->driver_id,
                    'immat' => $validated['immat'],
                    'departure_date' => $departureDate,
                    'arrival_date' => $arrivalDate,
                    'departure_time' => $validated['departure_time'],
                    'arrival_time' => $validated['arrival_time'],
                    'max_travel_time' => $validated['max_travel_time']
                ]);

                $covoiturage = new Covoiturage([
                    'driver_id' => $chauffeur->driver_id,
                    'immat' => $validated['immat'],
                    'departure_address' => $validated['departure_address'],
                    'add_dep_address' => $validated['add_dep_address'] ?? null,
                    'postal_code_dep' => $validated['postal_code_dep'],
                    'city_dep' => $validated['city_dep'],
                    'arrival_address' => $validated['arrival_address'],
                    'add_arr_address' => $validated['add_arr_address'] ?? null,
                    'postal_code_arr' => $validated['postal_code_arr'],
                    'city_arr' => $validated['city_arr'],
                    'departure_date' => $departureDate,
                    'arrival_date' => $arrivalDate,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'max_travel_time' => $maxTravelTime,
                    'price' => $validated['price'],
                    'n_tickets' => $validated['n_tickets'],
                    // eco_travel => trigger de la base de données
                ]);

                $covoiturage->save();

                Log::info('Covoiturage créé avec succès', ['covoit_id' => $covoiturage->covoit_id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Votre trajet a été créé avec succès.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors de la création du trajet', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création du trajet : ' . $e->getMessage()
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erreur de validation lors de la création d\'un trajet', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur inattendue lors de la création d\'un trajet', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue : ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            // Recuperer le covoit
            $trip = Covoiturage::findOrFail($id);

            $user = Auth::user();
            $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

            if (!$chauffeur || $trip->driver_id !== $chauffeur->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour modifier ce trajet.'
                ], 403);
            }

            // Info covoit
            return response()->json([
                'success' => true,
                'trip' => $trip
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des informations du trajet', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des informations du trajet : ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // données reçues
            Log::info('Données reçues pour la modification d\'un trajet', $request->all());

            $request = $this->formatTimeFields($request);

            $validated = $request->validate([
                'departure_address' => 'required|string|max:255',
                'add_dep_address' => 'nullable|string|max:255',
                'postal_code_dep' => 'required|string|max:10',
                'city_dep' => 'required|string|max:100',
                'arrival_address' => 'required|string|max:255',
                'add_arr_address' => 'nullable|string|max:255',
                'postal_code_arr' => 'required|string|max:10',
                'city_arr' => 'required|string|max:100',
                'departure_date' => 'required|date|after_or_equal:today',
                'arrival_date' => 'required|date|after_or_equal:departure_date',
                'departure_time' => 'required|date_format:H:i',
                'arrival_time' => 'required|date_format:H:i',
                'max_travel_time' => 'required|date_format:H:i',
                'immat' => 'required|exists:VOITURE,immat',
                'price' => 'required|integer|min:2',
                'n_tickets' => 'required|integer|min:1|max:9',
            ]);

            Log::info('Validation réussie', $validated);

            $trip = Covoiturage::findOrFail($id);

            $user = Auth::user();
            $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

            if (!$chauffeur || $trip->driver_id !== $chauffeur->driver_id) {
                Log::warning('Tentative de modification d\'un trajet par un non-conducteur', [
                    'user_id' => $user->user_id,
                    'trip_id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour modifier ce trajet.'
                ], 403);
            }

            $vehicle = Voiture::where('immat', $validated['immat'])
                ->where('driver_id', $chauffeur->driver_id)
                ->first();

            if (!$vehicle) {
                Log::warning('Tentative de modification d\'un trajet avec un véhicule qui n\'appartient pas au conducteur', [
                    'driver_id' => $chauffeur->driver_id,
                    'immat' => $validated['immat']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ce véhicule ne vous appartient pas.'
                ], 403);
            }

            // Modif du coovoit
            try {
                DB::beginTransaction();

                $departureDate = $validated['departure_date'];
                $arrivalDate = $validated['arrival_date'];
                $departureTime = $validated['departure_time'];
                $arrivalTime = $validated['arrival_time'];
                $maxTravelTime = $validated['max_travel_time'];

                Log::info('Modification du covoiturage', [
                    'trip_id' => $id,
                    'driver_id' => $chauffeur->driver_id,
                    'immat' => $validated['immat'],
                    'departure_date' => $departureDate,
                    'arrival_date' => $arrivalDate,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'max_travel_time' => $maxTravelTime
                ]);

                $trip->immat = $validated['immat'];
                $trip->departure_address = $validated['departure_address'];
                $trip->add_dep_address = $validated['add_dep_address'] ?? null;
                $trip->postal_code_dep = $validated['postal_code_dep'];
                $trip->city_dep = $validated['city_dep'];
                $trip->arrival_address = $validated['arrival_address'];
                $trip->add_arr_address = $validated['add_arr_address'] ?? null;
                $trip->postal_code_arr = $validated['postal_code_arr'];
                $trip->city_arr = $validated['city_arr'];
                $trip->departure_date = $departureDate;
                $trip->arrival_date = $arrivalDate;
                $trip->departure_time = $departureTime;
                $trip->arrival_time = $arrivalTime;
                $trip->max_travel_time = $maxTravelTime;
                $trip->price = $validated['price'];
                $trip->n_tickets = $validated['n_tickets'];

                $trip->save();

                Log::info('Covoiturage modifié avec succès', ['covoit_id' => $trip->covoit_id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Votre trajet a été modifié avec succès.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors de la modification du trajet', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la modification du trajet : ' . $e->getMessage()
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erreur de validation lors de la modification d\'un trajet', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur inattendue lors de la modification d\'un trajet', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue : ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($immat)
    {
        // À faire
    }
}