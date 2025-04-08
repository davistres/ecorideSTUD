<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // formulaire de connexion
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = $credentials['email'];
        $password = $credentials['password'];

        // Admin
        if (Auth::guard('admin')->attempt(['mail' => $email, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        // Employé
        if (Auth::guard('employe')->attempt(['mail' => $email, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->route('employe.dashboard');
}

        // Utilisateur normal
        if (Auth::guard('web')->attempt(['mail' => $email, 'password' => $password])) {
            $request->session()->regenerate();

            // middleware CheckUserRole
            $user = Auth::user();
            if ($user->role === 'Conducteur' || $user->role === 'Les deux') {
                $chauffeur = \App\Models\Chauffeur::where('user_id', $user->user_id)->first();

                if (!$chauffeur) {
                    \Illuminate\Support\Facades\Log::info('Connexion: Utilisateur sans profil chauffeur, changement de rôle en Passager', ['user_id' => $user->user_id]);
                    \Illuminate\Support\Facades\DB::table('UTILISATEUR')
                        ->where('user_id', $user->user_id)
                        ->update(['role' => 'Passager']);
                } else {
                    // Vérifier si y a au moins une voiture
                    $hasVehicles = \App\Models\Voiture::where('driver_id', $chauffeur->driver_id)->exists();

                    if (!$hasVehicles) {
                        // Pas de véhicule, changer le rôle en Passager
                        \Illuminate\Support\Facades\Log::info('Connexion: Utilisateur sans véhicule, changement de rôle en Passager', ['user_id' => $user->user_id, 'driver_id' => $chauffeur->driver_id]);
                        \Illuminate\Support\Facades\DB::table('UTILISATEUR')
                            ->where('user_id', $user->user_id)
                            ->update(['role' => 'Passager']);
                    }
                }
            }

            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'email' => 'Les informations d\'identification fournies ne correspondent pas à nos enregistrements.',
        ])->withInput($request->except('password'));
    }


}