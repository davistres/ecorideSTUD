<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chauffeur;
use App\Models\Voiture;
use App\Models\Covoiturage;
use App\Models\Confirmation;
use App\Models\Satisfaction;
use Carbon\Carbon;

// dashboard utilisateur
class HomeController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        // Donné classique utilisateurs
        $data = [
            'pendingSatisfactions' => [],
            'passengerHistory' => [],
        ];

        // formulaires de satisfaction en attente
        $data['pendingSatisfactions'] = Satisfaction::where('user_id', $user->user_id)
            ->whereNull('feeling')
            ->with(['covoiturage' => function($query) {
                $query->with('chauffeur.utilisateur');
            }])
            ->get();

        // historique trajets passagers
        $confirmations = Confirmation::where('user_id', $user->user_id)
            ->with(['covoiturage' => function($query) {
                $query->with(['chauffeur', 'chauffeur.utilisateur', 'voiture']);
            }])
            ->get();

        // confirmation ok ou annulé
        foreach ($confirmations as $confirmation) {
            // Un trajet est considéré comme terminé si sa date est passée => A CHANGER APRES!!!
            // Mais aussi si le conducteur a confirmé l'arrivée => A NE PAS OUBLIER!!!!!!!!!!!!!!!!!!!!
            $confirmation->completed = Carbon::parse($confirmation->covoiturage->departure_date)->isPast();

            // Déterminer si le trajet a été annulé => créer cette logique!!!!! A NA PAS OUBLIER!!!!!!!!!!!
            $confirmation->cancelled = false;

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
                    ->with(['voiture', 'confirmations.utilisateur'])
                    ->orderBy('departure_date', 'asc')
                    ->get();

                // historique trajets chauffeur
                $driverHistory = Covoiturage::where('driver_id', $chauffeur->driver_id)
                    ->where('departure_date', '<', Carbon::today())
                    ->with('confirmations')
                    ->get();

                // info supp covoit
                $data['driverHistory'] = [];
                foreach ($driverHistory as $trip) {
                    $trip->passengers_count = $trip->confirmations->count();
                    $trip->earnings = ($trip->price - 2) * $trip->passengers_count; // moins 2 crédits pour la plateforme
                    $trip->completed = true;
                    $trip->cancelled = false; // Logique à créer (pour déterminer si le trajet a été annulé)

                    $data['driverHistory'][] = $trip;
                }
            }
        }

        // utilisateurs = passagers
        if ($user->role === 'Passager' || $user->role === 'Les deux') {
            // covoit à venir
            $data['reservations'] = Confirmation::where('user_id', $user->user_id)
                ->whereHas('covoiturage', function($query) {
                    $query->where('departure_date', '>=', Carbon::today());
                })
                ->with([
                    'covoiturage' => function($query) {
                        $query->with([
                            'chauffeur.utilisateur',
                            'voiture'
                        ]);
                    }
                ])
                ->get();
        }

        return view('home', $data);
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
            ->map(function($confirmation) {
                return [
                    'id' => $confirmation->utilisateur->user_id,
                    'pseudo' => $confirmation->utilisateur->pseudo,
                    'mail' => $confirmation->utilisateur->mail
                ];
            });

        return response()->json(['passengers' => $passengers]);
    }



    // Démarre un covoit
    public function startTrip(Request $request, $tripId)
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

        $trip->trip_started = true;
        $trip->save();

        // Ici, créer le code pour envoyer un email aux passagers
        // A développer si j'ai le temps...

        return response()->json(['success' => true]);
    }


    // Covoit terminé
    public function endTrip(Request $request, $tripId)
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

        $trip->trip_started = false;
        $trip->trip_completed = true; // Marqué comme terminé
        $trip->save();

        // formulaires de satisfaction => passagers
        $confirmations = Confirmation::where('covoit_id', $tripId)->get();

        foreach ($confirmations as $confirmation) {
            Satisfaction::create([
                'user_id' => $confirmation->user_id,
                'covoit_id' => $tripId,
                'date' => Carbon::now()
            ]);

            // Ici, créer le code pour envoyer un email qui demande aux passagers de remplir le formulaire de satisfaction
            // A développer si j'ai le temps...
        }

        return response()->json(['success' => true]);
    }

    // Annule un covoit
    public function cancelTrip(Request $request, $tripId)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return redirect()->route('home')->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $trip = Covoiturage::where('covoit_id', $tripId)
            ->where('driver_id', $chauffeur->driver_id)
            ->first();

        if (!$trip) {
            return redirect()->route('home')->with('error', 'Trajet non trouvé.');
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

            return redirect()->route('home')->with('success', 'Le trajet a été annulé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors de l\'annulation du trajet: ' . $e->getMessage());
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
            return redirect()->route('home')->with('error', 'Réservation non trouvée.');
        }

        DB::beginTransaction();

        try {
            $trip = $confirmation->covoiturage;

            // Remboursement du passager
            $user->n_credit += $trip->price;
            $user->save();

            // A créer= l'enregistrement dans la table FLUX


            // Supprimer la confirmation
            $confirmation->delete();

            DB::commit();

            return redirect()->route('home')->with('success', 'Votre réservation a été annulée avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors de l\'annulation de votre réservation: ' . $e->getMessage());
        }
    }

    // Mise à jour du rôle
    public function updateRole(Request $request)
    {
        $user = Auth::user();
        $newRole = $request->input('role');

        // Valider le rôle
        if (!in_array($newRole, ['Passager', 'Conducteur', 'Les deux'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Rôle invalide.']);
            }
            return redirect()->route('home')->with('error', 'Rôle invalide.');
        }

        // Si le nouveau rôle inclut "Conducteur" et que l'utilisateur n'est pas déjà conducteur
        if (($newRole === 'Conducteur' || $newRole === 'Les deux') &&
            ($user->role === 'Passager' || !Chauffeur::where('user_id', $user->user_id)->exists())) {

            // Valider les données du formulaire conducteur
            $request->validate([
                'pref_smoke' => 'required|in:Fumeur,Non-fumeur',
                'pref_pet' => 'required|in:Acceptés,Non-acceptés',
                'pref_libre' => 'nullable|string|max:255',
                'idphoto' => 'nullable|image|max:2048', // optionnelle et poids max limité
            ]);

            // Créer ou mettre à jour le profil conducteur
            $chauffeur = Chauffeur::updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'pref_smoke' => $request->input('pref_smoke'),
                    'pref_pet' => $request->input('pref_pet'),
                    'pref_libre' => $request->input('pref_libre'),
                    'idphoto' => $request->file('idphoto') ? $request->file('idphoto')->store('photos', 'public') : null,
                ]
            );
        }

        // Mettre à jour le rôle de l'utilisateur
        $user->role = $newRole;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('home')->with('success', 'Votre rôle a été mis à jour avec succès.');
    }
}
