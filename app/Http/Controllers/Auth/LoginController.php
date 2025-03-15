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

    //connexion
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // BDD
        $databaseCredentials = [
            'mail' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        // se connecter
        $guards = ['admin', 'employe', 'web'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->attempt($databaseCredentials)) {
                $request->session()->regenerate();

                // Etre rediriger
                switch ($guard) {
                    case 'admin':
                        return redirect()->route('admin.dashboard');
                    case 'employe':
                        return redirect()->route('employe.dashboard');
                    default:
                        return redirect()->intended('/home');
                }
            }
        }

        return back()->withErrors([
            'email' => 'Les informations d\'identification fournies ne correspondent pas à nos enregistrements.',
        ])->withInput($request->except('password'));
    }

    // Déterminer qui est connecté
    private function getGuardForUserType($userType)
    {
        switch ($userType) {
            case 'admin':
                return 'admin';
            case 'employe':
                return 'employe';
            default:
                return 'web'; // Pour les utilisateurs
        }
    }

    // Redirection après connexion
    private function redirectBasedOnUserType($userType)
    {
        switch ($userType) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'employe':
                return redirect()->route('employe.dashboard');
            default:
                return redirect()->intended('/home');
        }
    }
}
