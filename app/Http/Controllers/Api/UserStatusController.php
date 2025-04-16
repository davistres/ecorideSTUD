<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Covoiturage;

class UserStatusController extends Controller
{
    public function getStatus(Request $request)
    {
        $tripId = $request->query('trip_id');

        if (!$tripId) {
            return response()->json([
                'can_participate' => false,
                'message' => 'ID du covoiturage manquant',
                'redirect_to' => route('trips.index'),
                'button_text' => 'Participer'
            ]);
        }

        $covoiturage = Covoiturage::find($tripId);

        if (!$covoiturage) {
            return response()->json([
                'can_participate' => false,
                'message' => 'Le covoiturage demandé n\'existe pas',
                'redirect_to' => route('trips.index'),
                'button_text' => 'Participer'
            ]);
        }

        $status = [
            'can_participate' => false,
            'message' => '',
            'redirect_to' => '',
            'button_text' => 'Participer'
        ];

        // Connecté?
        if (!Auth::check()) {
            $status['message'] = 'Vous devez vous connecter pour participer à un covoiturage.';
            $status['redirect_to'] = route('login');
            $status['button_text'] = 'Se connecter / s\'inscrire';
            return response()->json($status);
        }

        $user = Auth::user();

        // Utilisateur?
        if ($user->role === 'Conducteur') {
            $status['message'] = 'Vous devez avoir le rôle "Passager" ou "Les deux" pour participer à un covoiturage.';
            $status['redirect_to'] = route('home');
            $status['button_text'] = 'Changer de rôle / devenir "Passager"';
            return response()->json($status);
        }

        // Assez de crédits?
        if ($user->n_credit < $covoiturage->price) {
            $status['message'] = 'Vous n\'avez pas assez de crédits pour participer à ce covoiturage.';
            $status['redirect_to'] = route('home');
            $status['button_text'] = 'Recharger votre crédit';
            return response()->json($status);
        }

        // Si tout est OK
        $status['can_participate'] = true;
        $status['button_text'] = 'Participer';
        return response()->json($status);
    }
}
