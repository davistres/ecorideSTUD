<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Voiture;
use App\Models\Chauffeur;
use App\Models\Covoiturage;
use App\Models\Confirmation;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Utilisateur;

class VehicleController extends Controller
{
    // créer un véhicule
    public function create()
    {
        return redirect()->route('home')->with('info', 'Utilisez la modale pour ajouter un véhicule.');
    }

    // Enregistre
    public function store(Request $request)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        // Utilisateur chauffeur?
        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'marque' => 'required|string|max:12',
            'modele' => 'required|string|max:24',
            'immat' => ['required', 'string', 'max:10', Rule::unique('VOITURE', 'immat')],
            'couleur' => 'required|string|max:12',
            'n_place' => 'required|integer|min:2|max:9',
            'energie' => 'required|in:Essence,Diesel/Gazole,Electrique,Hybride,GPL',
            'date_first_immat' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            // Données validées pour l\'ajout d\'un véhicule
            Log::info('Données validées pour l\'ajout d\'un véhicule', $validated);

            $voiture = new Voiture();
            $voiture->driver_id = $chauffeur->driver_id;

            try { $voiture->brand = $validated['marque']; }
            catch (\Exception $e) { Log::error('Erreur assignation brand: '.$e->getMessage()); }

            try { $voiture->model = $validated['modele']; }
            catch (\Exception $e) { Log::error('Erreur assignation model: '.$e->getMessage()); }

            try { $voiture->immat = $validated['immat']; }
            catch (\Exception $e) { Log::error('Erreur assignation immat: '.$e->getMessage()); }

            try { $voiture->color = $validated['couleur']; }
            catch (\Exception $e) { Log::error('Erreur assignation color: '.$e->getMessage()); }

            try { $voiture->n_place = $validated['n_place']; }
            catch (\Exception $e) { Log::error('Erreur assignation n_place: '.$e->getMessage()); }

            try { $voiture->energie = $validated['energie']; }
            catch (\Exception $e) { Log::error('Erreur assignation energie: '.$e->getMessage()); }

            try { $voiture->date_first_immat = $validated['date_first_immat']; }
            catch (\Exception $e) { Log::error('Erreur assignation date_first_immat: '.$e->getMessage()); }

            Log::info('Tentative de sauvegarde du véhicule', [
                'driver_id' => $voiture->driver_id,
                'brand' => $voiture->brand,
                'model' => $voiture->model,
                'immat' => $voiture->immat,
                'color' => $voiture->color,
                'n_place' => $voiture->n_place,
                'energie' => $voiture->energie,
                'date_first_immat' => $voiture->date_first_immat
            ]);

            $voiture->save();

            DB::commit();

            return response()->json(['success' => true, 'vehicle' => $voiture]);

        } catch (\Illuminate\Validation\ValidationException $e) {
             DB::rollback();
             return response()->json(['success' => false, 'message' => $e->validator->errors()->first(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollback();
             Log::error('Erreur ajout véhicule: '.$e->getMessage());
             $errorMessage = 'Une erreur est survenue lors de l\'ajout du véhicule.';
             if (str_contains($e->getMessage(), 'Duplicate entry')) {
                 $errorMessage = 'Cette immatriculation existe déjà.';
             }
            return response()->json(['success' => false, 'message' => $errorMessage], 500);
        }
    }

    // Formulaire => véhicule
    public function edit($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();
        if (!$chauffeur) { return redirect()->route('home')->withErrors('Non autorisé.'); }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) { return redirect()->route('home')->withErrors('Véhicule non trouvé.'); }

        return redirect()->route('home')->with('info', 'Utilisez la modale pour modifier le véhicule.');
    }

    // Mise à jour
    public function update(Request $request, $immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
        }

        $validated = $request->validate([
            'marque' => 'required|string|max:12',
            'modele' => 'required|string|max:24',
            // 'immat' => readonly
            'couleur' => 'required|string|max:12',
            'n_place' => 'required|integer|min:2|max:9',
            'energie' => 'required|in:Essence,Diesel/Gazole,Electrique,Hybride,GPL',
            'date_first_immat' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            $vehicle->brand = $validated['marque'];
            $vehicle->model = $validated['modele'];
            $vehicle->color = $validated['couleur'];
            $vehicle->n_place = $validated['n_place'];
            $vehicle->energie = $validated['energie'];
            $vehicle->date_first_immat = $validated['date_first_immat'];
            $vehicle->save();

            DB::commit();

            return response()->json(['success' => true, 'vehicle' => $vehicle]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur MAJ véhicule: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour du véhicule.'], 500);
        }
    }

    public function destroy($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Véhicule non trouvé'], 404);
        }

        $remainingVehicles = Voiture::where('driver_id', $chauffeur->driver_id)->count();
        if ($remainingVehicles <= 1) {
             //renvoye une erreur si on essaye de supprimer le dernier véhicule
        }


        DB::beginTransaction();
        try {
            // Annule les covoit avant de supprimer le véhicule
            $tripsToCancel = Covoiturage::where('immat', $immat)
                ->where('driver_id', $chauffeur->driver_id)
                ->where('cancelled', false)
                ->get();

            // Compter les covoiturages à venir qui seront annulés
            $upcomingTripsCount = $tripsToCancel->where('departure_date', '>=', Carbon::today()->format('Y-m-d'))->count();

            foreach ($tripsToCancel as $trip) {
                // Remboursement
                if (Carbon::parse($trip->departure_date)->gte(Carbon::today())) {
                    $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                    foreach ($confirmations as $confirmation) {
                        $passenger = $confirmation->utilisateur;
                        if ($passenger) {
                            DB::table('UTILISATEUR')
                                ->where('user_id', $passenger->user_id)
                                ->increment('n_credit', $trip->price);
                        }
                        $confirmation->delete();
                    }
                }
                // Marquer le covoiturage comme annulé
                DB::table('COVOITURAGE')
                    ->where('covoit_id', $trip->covoit_id)
                    ->update(['cancelled' => true]);
            }

            $vehicle->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'remainingVehicles' => $remainingVehicles - 1,
                'cancelledTrips' => $tripsToCancel->count(),
                'upcomingTrips' => $upcomingTripsCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Erreur suppression véhicule simple: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }


    // Si on supprime le dernier véhicule on redevient Passager + Annulation des covoit
    public function destroyLastAndResetRole($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Non autorisé ou déjà passager.'], 403);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Véhicule non trouvé.'], 404);
        }

        $vehicleCount = Voiture::where('driver_id', $chauffeur->driver_id)->count();
        if ($vehicleCount > 1) {
            return response()->json(['success' => false, 'message' => 'Ce n\'est pas votre dernier véhicule.'], 400);
        }

        DB::beginTransaction();
        try {
            // Annuler tous les covoit du conducteur
            $tripsToCancel = Covoiturage::where('driver_id', $chauffeur->driver_id)
                ->where('cancelled', false)
                ->get();

            // Compter les covoit à venir qui seront annulés
            $upcomingTripsCount = $tripsToCancel->where('departure_date', '>=', Carbon::today()->format('Y-m-d'))->count();

            foreach ($tripsToCancel as $trip) {
                // Remboursement pour les covoit à venir
                if (Carbon::parse($trip->departure_date)->gte(Carbon::today())) {
                    $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                    foreach ($confirmations as $confirmation) {
                        $passenger = $confirmation->utilisateur;
                        if ($passenger) {
                            // Mise à jour des crédits pour l'utilisateur
                            DB::table('UTILISATEUR')
                                ->where('user_id', $passenger->user_id)
                                ->increment('n_credit', $trip->price);
                        }
                        $confirmation->delete();
                    }
                }
                // Marquer le covoit comme annulé
                DB::table('COVOITURAGE')
                    ->where('covoit_id', $trip->covoit_id)
                    ->update(['cancelled' => true]);
            }

            $vehicle->delete();
            $chauffeur->delete();
            // Mise à jour du role
            DB::table('UTILISATEUR')->where('user_id', $user->user_id)->update(['role' => 'Passager']);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Dernier véhicule supprimé, rôle mis à jour vers Passager.',
                'cancelledTrips' => $tripsToCancel->count()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Erreur suppression dernier véhicule/reset role: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression et réinitialisation: ' . $e->getMessage()], 500);
        }
    }

    public function show($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'Véhicule non trouvé'], 404);
        }

        return response()->json($vehicle);
    }

    // Vérifier si un véhicule est lié à un covoit
    public function checkTrips($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'Véhicule non trouvé'], 404);
        }

        // Récupérer les covoiturages liés à ce véhicule
        // Découverte de Carbon::now() => date et l'heure actuelle
        $trips = Covoiturage::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->where('cancelled', false)
            ->where('completed', false)
            ->where('departure_date', '>=', Carbon::now()->format('Y-m-d'))
            ->get(['covoit_id', 'city_dep', 'city_arr', 'departure_date']);

        return response()->json([
            'hasTrips' => $trips->count() > 0,
            'trips' => $trips
        ]);
    }
}
