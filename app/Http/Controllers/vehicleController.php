<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'marque' => 'required|string|max:50',
            'modele' => 'required|string|max:50',
            'immat' => ['required', 'string', 'max:20', Rule::unique('VOITURE', 'immat')],
            'couleur' => 'required|string|max:30',
            'n_place' => 'required|integer|min:2|max:9',
            'energie' => 'required|in:Essence,Diesel,Électrique,Hybride,GPL',
            'date_first_immat' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            $voiture = new Voiture();
            $voiture->driver_id = $chauffeur->driver_id;
            $voiture->brand = $validated['marque'];
            $voiture->model = $validated['modele'];
            $voiture->immat = $validated['immat'];
            $voiture->color = $validated['couleur'];
            $voiture->n_place = $validated['n_place'];
            $voiture->energie = $validated['energie'];
            $voiture->date_first_immat = $validated['date_first_immat'];
            $voiture->save();

            DB::commit();

            return response()->json(['success' => true, 'vehicle' => $voiture]);

        } catch (\Illuminate\Validation\ValidationException $e) {
             DB::rollback();
             return response()->json(['success' => false, 'message' => $e->validator->errors()->first(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollback();
             \Log::error('Erreur ajout véhicule: '.$e->getMessage());
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
            'marque' => 'required|string|max:50',
            'modele' => 'required|string|max:50',
            // 'immat' => readonly
            'couleur' => 'required|string|max:30',
            'n_place' => 'required|integer|min:2|max:9',
            'energie' => 'required|in:Essence,Diesel,Électrique,Hybride,GPL',
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
            \Log::error('Erreur MAJ véhicule: '.$e->getMessage());
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
             //renvoye une erreur si on essaye de supprimer le dernier véhicule via la mauvaise route => JS => route /reset-role
        }


        DB::beginTransaction();
        try {
            // Annule les covoit avant de supprimer le vehicle,
             $tripsToCancel = Covoiturage::where('immat', $immat)
                ->where('driver_id', $chauffeur->driver_id)
                ->where('departure_date', '>=', Carbon::today())
                ->where('cancelled', false)
                ->get();

             foreach ($tripsToCancel as $trip) {
                 // Remboursement
                 $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                 foreach ($confirmations as $confirmation) {
                     $passenger = $confirmation->utilisateur;
                     if ($passenger) {
                         $passenger->n_credit += $trip->price;
                         $passenger->save();
                     }
                     $confirmation->delete();
                 }
                 $trip->cancelled = true;
                 $trip->save();
             }


            $vehicle->delete();

            DB::commit();
            return response()->json(['success' => true, 'remainingVehicles' => $remainingVehicles - 1]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Erreur suppression véhicule simple: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }


    // Dernier véhicule => role => Passager + Annulation des covoit
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
            $tripsToCancel = Covoiturage::where('driver_id', $chauffeur->driver_id)
                ->where('departure_date', '>=', Carbon::today())
                ->where('cancelled', false)
                ->get();

            foreach ($tripsToCancel as $trip) {
                $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                foreach ($confirmations as $confirmation) {
                    $passenger = $confirmation->utilisateur;
                     if ($passenger) {
                         $passenger->n_credit += $trip->price;
                         $passenger->save();
                     }
                    $confirmation->delete();
                }
                $trip->cancelled = true;
                $trip->save();
            }

            $vehicle->delete();
            $chauffeur->delete();
            $user->role = 'Passager';
            $user->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Dernier véhicule supprimé, rôle mis à jour vers Passager.']);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Erreur suppression dernier véhicule/reset role: " . $e->getMessage());
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
}