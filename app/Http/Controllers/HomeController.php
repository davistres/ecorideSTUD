<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Chauffeur;
use App\Models\Voiture;
use App\Models\Covoiturage;
use App\Models\Confirmation;
use App\Models\Satisfaction;
use Carbon\Carbon;
use App\Models\Utilisateur;
use Illuminate\Validation\Rule;

// dashboard utilisateur
class HomeController extends Controller
{

    // Donné classique utilisateurs
    public function index()
    {
        $user = Auth::user();

        $data = [
            'profile_photo' => null,
            'profile_photo_mime' => null,
            'pendingSatisfactions' => [],
            'passengerHistory' => [],
            'driverHistory' => [],
            'offeredTrips' => [],
            'reservations' => [],
            'vehicles' => [],
            'chauffeur' => null,
        ];

        // photo de profil
        $profilePhoto = $user->profile_photo;
        \Log::info('Profile photo data for user', [
            'user_id' => $user->user_id,
            'has_photo' => $profilePhoto !== null ? 'yes' : 'no',
            'photo_size' => $profilePhoto !== null ? strlen($profilePhoto) : 0
        ]);

        if ($profilePhoto !== null && $profilePhoto !== '') {
            $encoded = base64_encode($profilePhoto);
            if ($encoded === false || base64_decode($encoded, true) === false) {
                \Log::error('Données binaires de la photo de profil corrompues', ['user_id' => $user->user_id]);
                $data['profile_photo'] = null;
                $data['profile_photo_mime'] = null;
            } else {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($profilePhoto);
                $data['profile_photo'] = $encoded;
                $data['profile_photo_mime'] = $mimeType;
                \Log::info('Profile photo encoded successfully', [
                    'user_id' => $user->user_id,
                    'mime_type' => $mimeType
                ]);
            }
        } else {
            \Log::info('No profile photo for user', ['user_id' => $user->user_id]);
        }

        // Formulaire de satisfaction en attente
        $data['pendingSatisfactions'] = Satisfaction::where('user_id', $user->user_id)
            ->whereNull('feeling')
            ->with(['covoiturage' => function ($query) {
                $query->with('chauffeur.utilisateur');
            }])
            ->get();

        // historique trajets passagers
        $confirmations = Confirmation::where('user_id', $user->user_id)
            ->with(['covoiturage' => function ($query) {
                $query->with(['chauffeur', 'chauffeur.utilisateur', 'voiture']);
            }])
            ->get();

        // confirmation ok ou annulé
        foreach ($confirmations as $confirmation) {
            // Un trajet est considéré comme terminé si sa date est passée => A CHANGER APRES!!!
            // Mais aussi si le conducteur a confirmé l'arrivée => A NE PAS OUBLIER!!!!!!!!!!!!!!!!!!!!
            $confirmation->completed = Carbon::parse($confirmation->covoiturage->departure_date)->isPast();
            // Déterminer si le trajet a été annulé => créer cette logique!!!!! A NA PAS OUBLIER!!!!!!!!!!!
            $confirmation->cancelled = $confirmation->covoiturage->cancelled;
            $data['passengerHistory'][] = $confirmation;
        }

        // utilisateurs = conducteurs
        if ($user->role === 'Conducteur' || $user->role === 'Les deux') {
            $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();
            $data['chauffeur'] = $chauffeur;

            if ($chauffeur) {
                // véhicules du chauffeur
                $data['vehicles'] = Voiture::where('driver_id', $chauffeur->driver_id)->get();

                // covoit proposés par le chauffeur
                $data['offeredTrips'] = Covoiturage::where('driver_id', $chauffeur->driver_id)
                    ->where('departure_date', '>=', Carbon::today())
                    ->where('cancelled', false) // trajets annulés => exclut
                    ->with(['voiture', 'confirmations.utilisateur'])
                    ->orderBy('departure_date', 'asc')
                    ->get();

                // historique trajets chauffeur
                $driverHistory = Covoiturage::where('driver_id', $chauffeur->driver_id)
                    ->where('departure_date', '<', Carbon::today())
                    ->with('confirmations')
                    ->get();

                foreach ($driverHistory as $trip) {
                    $trip->passengers_count = $trip->confirmations->count();
                    $trip->earnings = ($trip->price - 2) * $trip->passengers_count;
                    $trip->completed = true;
                    $trip->cancelled = $trip->cancelled;
                    $data['driverHistory'][] = $trip;
                }
            }
        }

        if ($user->role === 'Passager' || $user->role === 'Les deux') {
            $data['reservations'] = Confirmation::where('user_id', $user->user_id)
                ->whereHas('covoiturage', function ($query) {
                    $query->where('departure_date', '>=', Carbon::today())
                          ->where('cancelled', false); // trajets annulés => exclut
                })
                ->with(['covoiturage' => function ($query) {
                    $query->with(['chauffeur.utilisateur', 'voiture']);
                }])
                ->get();
        }

        return view('home', $data);
    }

    public function updateProfilePhoto(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png|max:2048',
        ]);

        try {
            $photo = $request->file('profile_photo');
            if (!$photo->isValid()) {
                \Log::warning('Fichier uploadé invalide', ['user_id' => $user->user_id]);
                return response()->json(['success' => false, 'message' => 'Le fichier uploadé est invalide']);
            }

            $binaryData = file_get_contents($photo->getRealPath());
            if ($binaryData === false) {
                \Log::error('Erreur lors de la lecture du fichier', ['user_id' => $user->user_id]);
                return response()->json(['success' => false, 'message' => 'Erreur lors de la lecture du fichier']);
            }

            \Log::info('Photo de profil mise à jour', ['user_id' => $user->user_id, 'taille' => strlen($binaryData)]);
            // Mettre à jour le profil de l'utilisateur
            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['profile_photo' => $binaryData]);

            $encodedPhoto = base64_encode($binaryData);
            if ($encodedPhoto === false) {
                \Log::error('Erreur lors de l\'encodage base64', ['user_id' => $user->user_id]);
                return response()->json(['success' => false, 'message' => 'Erreur lors de l\'encodage de la photo']);
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($binaryData);

            return response()->json([
                'success' => true,
                'photo' => $encodedPhoto,
                'mime_type' => $mimeType
            ], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de la photo', ['user_id' => $user->user_id, 'message' => $e->getMessage()]);
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Liste des passagers pour un covoit
    public function getPassengers($tripId)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $trip = Covoiturage::where('covoit_id', $tripId)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$trip) {
            return response()->json(['error' => 'Trajet non trouvé'], 404);
        }

        $passengers = Confirmation::where('covoit_id', $tripId)
            ->with('utilisateur')
            ->get()
            ->map(function ($confirmation) {
                return [
                    'id' => $confirmation->utilisateur->user_id,
                    'pseudo' => mb_convert_encoding($confirmation->utilisateur->pseudo, 'UTF-8', 'auto'),
                    'mail' => mb_convert_encoding($confirmation->utilisateur->mail, 'UTF-8', 'auto'),
                ];
            });

        return response()->json(['passengers' => $passengers], 200, [], JSON_INVALID_UTF8_IGNORE);
    }

    // Démarre un covoit
    public function startTrip(Request $request, $tripId)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403, [], JSON_INVALID_UTF8_IGNORE);
        }

        $trip = Covoiturage::where('covoit_id', $tripId)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$trip) {
            return response()->json(['error' => 'Trajet non trouvé'], 404, [], JSON_INVALID_UTF8_IGNORE);
        }

        try {
            $trip->trip_started = true;
            $trip->save();
            // Ici, créer le code pour envoyer un email aux passagers
        // A développer si j'ai le temps...

            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Covoit terminé
    public function endTrip(Request $request, $tripId)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403, [], JSON_INVALID_UTF8_IGNORE);
        }

        $trip = Covoiturage::where('covoit_id', $tripId)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$trip) {
            return response()->json(['error' => 'Trajet non trouvé'], 404, [], JSON_INVALID_UTF8_IGNORE);
        }

        try {
            $trip->trip_started = false;
            $trip->trip_completed = true;
            $trip->save();

            // formulaires de satisfaction => passagers
            $confirmations = Confirmation::where('covoit_id', $tripId)->get();
            foreach ($confirmations as $confirmation) {
                Satisfaction::create([
                    'user_id' => $confirmation->user_id,
                    'covoit_id' => $tripId,
                    'date' => Carbon::now()
                ]);
            }

            // Ici, créer le code pour envoyer un email qui demande aux passagers de remplir le formulaire de satisfaction
            // A développer si j'ai le temps...

            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Annule un covoit
    public function cancelTrip(Request $request, $tripId)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'], 403);
        }

        $trip = Covoiturage::where('covoit_id', $tripId)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trajet non trouvé.'], 404);
        }

        // transaction
        DB::beginTransaction();
        try {
            // confirmations pour un covoit
            $confirmations = Confirmation::where('covoit_id', $tripId)->with('utilisateur')->get();

            // Rembourser les passagers
            foreach ($confirmations as $confirmation) {
                $passenger = $confirmation->utilisateur;
                $passenger->n_credit += $trip->price;
                $passenger->save();

                // A créer= l'enregistrement dans la table FLUX

                // A créer= annulation (mail ou autre)
            }

            // Supprimer toutes les confirmations
            Confirmation::where('covoit_id', $tripId)->delete();
            // Marquer le covoit comme annulé
            $trip->cancelled = true;
            $trip->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Le trajet a été annulé avec succès.']);
        } catch (\Exception $e) {
            DB::rollback();
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue lors de l\'annulation du trajet: ' . $e->getMessage()]);
        }
    }

    // réservation annulée
    public function cancelReservation(Request $request, $confirmationId)
    {
        $user = Auth::user();

        $confirmation = Confirmation::where('conf_id', $confirmationId)
            ->where('user_id', $user->user_id)
            ->with('covoiturage')
            ->first();

        if (!$confirmation) {
            return response()->json(['success' => false, 'message' => 'Réservation non trouvée.'], 404, [], JSON_INVALID_UTF8_IGNORE);
        }

        DB::beginTransaction();
        try {
            $trip = $confirmation->covoiturage;

            // Remboursement du passager
            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['n_credit' => $user->n_credit + $trip->price]);

            // A créer= l'enregistrement dans la table FLUX


            // Supprimer la confirmation
            $confirmation->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Votre réservation a été annulée avec succès.'], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            DB::rollback();
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue lors de l\'annulation de votre réservation: ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Mise à jour du rôle
    public function updateRole(Request $request)
    {
        $user = Auth::user();
        $newRole = $request->input('role');

        \Log::info('Role update request received', [
            'requestData' => $request->all(),
            'newRole' => $newRole,
            'currentRole' => $user->role
        ]);

        // Valider le rôle
        if (!in_array($newRole, ['Passager', 'Conducteur', 'Les deux'])) {
            \Log::warning('Invalid role provided', ['role' => $newRole]);
            return response()->json(['success' => false, 'message' => 'Rôle invalide.'], 400);
        }

        if ($user->role === $newRole) {
            return response()->json(['success' => false, 'message' => 'Vous avez déjà ce rôle! Si vous désirez en changer, vous devez au préalable, en choisir un nouveau.'], 400);
        }

            // Passager => Conducteur ou Les deux
        if ($user->role === 'Passager' && ($newRole === 'Conducteur' || $newRole === 'Les deux')) {
            try {
                \Log::info('Starting validation for role change', ['requestData' => $request->all()]);

                $validated = $request->validate([
                    'pref_smoke' => 'required|in:Fumeur,Non-fumeur',
                    'pref_pet' => 'required|in:Acceptés,Non-acceptés',
                    'pref_libre' => 'nullable|string|max:255',
                    'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                    'marque' => 'required|string|max:50',
                    'modele' => 'required|string|max:50',
                    'immat' => ['required', 'string', 'max:20', Rule::unique('VOITURE', 'immat')],
                    'couleur' => 'required|string|max:30',
                    'n_place' => 'required|integer|min:2|max:9',
                    'energie' => 'required|in:Essence,Diesel,Electrique,Hybride,GPL',
                    'date_first_immat' => 'required|date|before_or_equal:today',
                ]);

                \Log::info('Validation passed for role change', ['validated' => $validated]);

                DB::beginTransaction();
                try {
                    \Log::info('Creating driver profile');
                    // Créer ou mettre à jour le profil conducteur
                    $chauffeur = Chauffeur::updateOrCreate(
                        ['user_id' => $user->user_id],
                        [
                            'pref_smoke' => $validated['pref_smoke'],
                            'pref_pet' => $validated['pref_pet'],
                            'pref_libre' => $validated['pref_libre'] ?? null,
                        ]
                    );

                    \Log::info('Driver profile created/updated', ['driver_id' => $chauffeur->driver_id]);

                    \Log::info('Creating vehicle');
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
                    \Log::info('Vehicle created', ['immat' => $voiture->immat]);

                    $user->role = $newRole;
                    \Log::info('Updating user role', ['newRole' => $newRole]);

                    if ($request->hasFile('profile_photo')) {
                        \Log::info('Processing profile photo');
                        $photoFile = $request->file('profile_photo');
                        if ($photoFile->isValid()) {
                            try {
                                $binaryData = file_get_contents($photoFile->getRealPath());
                                if ($binaryData === false) {
                                    throw new \Exception('Erreur lors de la lecture du fichier photo.');
                                }
                                $user->profile_photo = $binaryData;
                                \Log::info('Profile photo updated during role change', ['user_id' => $user->user_id, 'photo_size' => strlen($binaryData)]);
                            } catch (\Exception $e) {
                                \Log::error('Error reading profile photo', ['error' => $e->getMessage()]);
                                throw new \Exception('Erreur lors de la lecture du fichier photo: ' . $e->getMessage());
                            }
                        } else {
                            \Log::warning('Invalid profile photo uploaded', ['error' => $photoFile->getErrorMessage()]);
                        }
                    }

                    // Mettre à jour le rôle de l'utilisateur
                    DB::table('UTILISATEUR')
                        ->where('user_id', $user->user_id)
                        ->update(['role' => $newRole, 'profile_photo' => $user->profile_photo]);
                    \Log::info('User saved with new role');

                    DB::commit();
                    \Log::info('Role updated successfully to Conducteur/Les deux', ['newRole' => $newRole, 'userId' => $user->user_id]);
                    return response()->json(['success' => true]);

                } catch (\Exception $e) {
                    DB::rollback();
                    \Log::error('Error in transaction for role change', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
                    if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'immat')) {
                        $errorMessage = 'Cette immatriculation existe déjà.';
                    } elseif (str_contains($errorMessage, 'Erreur lors de la lecture du fichier photo')) {
                        $errorMessage = 'Un problème est survenu lors de la lecture de l\'image.';
                    } else {
                        $errorMessage = 'Erreur lors de la mise à jour du rôle: ' . $errorMessage;
                    }
                    return response()->json(['success' => false, 'message' => $errorMessage], 500);
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Validation Error updating role', ['errors' => $e->errors()]);
                return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
            } catch (\Exception $e) {
                \Log::error('Unexpected error during role change validation', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
                return response()->json(['success' => false, 'message' => 'Erreur inattendue: ' . $errorMessage], 500);
            }
        }

        // Conducteur => Les deux ou l'inverse
        if (($user->role === 'Conducteur' && $newRole === 'Les deux') || ($user->role === 'Les deux' && $newRole === 'Conducteur')) {
            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['role' => $newRole]);
            \Log::info('Role updated directly (Conducteur <-> Les deux)', ['newRole' => $newRole, 'userId' => $user->user_id]);
            return response()->json(['success' => true]);
        }

        // Conducteur ou Les deux => Passager
        // gérée route /user/role/reset et VehicleController@destroyLastAndResetRole
        if (($user->role === 'Conducteur' || $user->role === 'Les deux') && $newRole === 'Passager') {
            return response()->json(['success' => false, 'message' => 'Veuillez confirmer ce changement via la modale d\'avertissement.'], 400);
        }

        // normalement => pas possible d'arriber ici
        \Log::warning('Unhandled role update scenario', ['current' => $user->role, 'new' => $newRole]);
        return response()->json(['success' => false, 'message' => 'Action non gérée.'], 400);
    }


    // Reset => Passager
    public function resetRole(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'Passager') {
            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        }

        DB::beginTransaction();
        try {
            $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();
            if ($chauffeur) {
                $trips = Covoiturage::where('driver_id', $chauffeur->driver_id)
                    ->where('departure_date', '>=', Carbon::today())
                    ->where('cancelled', false)
                    ->get();

                foreach ($trips as $trip) {
                    $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                    foreach ($confirmations as $confirmation) {
                        $passenger = $confirmation->utilisateur;
                        $passenger->n_credit += $trip->price;
                        $passenger->save();
                    }
                    $trip->cancelled = true;
                    $trip->save();
                }

                Voiture::where('driver_id', $chauffeur->driver_id)->delete();
                $chauffeur->delete();
            }

            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['role' => 'Passager']);

            DB::commit();
            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            DB::rollback();
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur lors de la réinitialisation: ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Détails d'un véhicule
    public function showVehicle($immat)
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

        return response()->json([
            'brand' => mb_convert_encoding($vehicle->brand, 'UTF-8', 'auto'),
            'model' => mb_convert_encoding($vehicle->model, 'UTF-8', 'auto'),
            'immat' => mb_convert_encoding($vehicle->immat, 'UTF-8', 'auto'),
            'color' => mb_convert_encoding($vehicle->color, 'UTF-8', 'auto'),
            'n_place' => $vehicle->n_place,
            'energie' => mb_convert_encoding($vehicle->energie, 'UTF-8', 'auto'),
        ], 200, [], JSON_INVALID_UTF8_IGNORE);
    }

    // Supprimer un véhicule
    public function deleteVehicle($immat)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403, [], JSON_INVALID_UTF8_IGNORE);
        }

        $vehicle = Voiture::where('immat', $immat)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'Véhicule non trouvé'], 404, [], JSON_INVALID_UTF8_IGNORE);
        }

        DB::beginTransaction();
        try {
            $vehicle->delete();
            $remainingVehicles = Voiture::where('driver_id', $chauffeur->driver_id)->count();

            DB::commit();
            return response()->json(['success' => true, 'remainingVehicles' => $remainingVehicles], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            DB::rollback();
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    // Supprimer toutes les voitures
    public function resetVehicles(Request $request)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['error' => 'Non autorisé'], 403, [], JSON_INVALID_UTF8_IGNORE);
        }

        DB::beginTransaction();
        try {
            $trips = Covoiturage::where('driver_id', $chauffeur->driver_id)
                ->where('departure_date', '>=', Carbon::today())
                ->where('cancelled', false)
                ->get();

            foreach ($trips as $trip) {
                $confirmations = Confirmation::where('covoit_id', $trip->covoit_id)->with('utilisateur')->get();
                foreach ($confirmations as $confirmation) {
                    $passenger = $confirmation->utilisateur;
                    $passenger->n_credit += $trip->price;
                    $passenger->save();
                }
                $trip->cancelled = true;
                $trip->save();
            }

            Voiture::where('driver_id', $chauffeur->driver_id)->delete();
            $chauffeur->delete();
            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['role' => 'Passager']);

            DB::commit();
            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            DB::rollback();
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur lors de la réinitialisation: ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }

    public function deleteProfilePhoto(Request $request)
    {
        $user = Auth::user();

        try {
            \Log::info('Attempting to delete profile photo', ['user_id' => $user->user_id]);

            DB::table('UTILISATEUR')
                ->where('user_id', $user->user_id)
                ->update(['profile_photo' => null]);

            \Log::info('Profile photo deleted successfully', ['user_id' => $user->user_id]);
            return response()->json(['success' => true], 200, [], JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            \Log::error('Error deleting profile photo', ['user_id' => $user->user_id, 'message' => $e->getMessage()]);
            $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression de la photo : ' . $errorMessage], 200, [], JSON_INVALID_UTF8_IGNORE);
        }
    }
}