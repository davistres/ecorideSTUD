<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Voiture;
use App\Models\Chauffeur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckUserRole
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'Conducteur' || $user->role === 'Les deux') {
                $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

                if (!$chauffeur) {
                    Log::info('Utilisateur sans profil chauffeur, changement de rôle en Passager', ['user_id' => $user->user_id]);
                    DB::table('UTILISATEUR')
                        ->where('user_id', $user->user_id)
                        ->update(['role' => 'Passager']);
                } else {
                    // Vérifier s'il a au moins une voiture
                    $hasVehicles = Voiture::where('driver_id', $chauffeur->driver_id)->exists();

                    if (!$hasVehicles) {
                        // Pas de véhicule, changer le rôle en Passager
                        Log::info('Utilisateur sans véhicule, changement de rôle en Passager', ['user_id' => $user->user_id, 'driver_id' => $chauffeur->driver_id]);
                        DB::table('UTILISATEUR')
                            ->where('user_id', $user->user_id)
                            ->update(['role' => 'Passager']);
                    }
                }
            }
        }

        return $next($request);
    }
}