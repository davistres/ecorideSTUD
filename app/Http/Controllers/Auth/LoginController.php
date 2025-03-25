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
            return redirect()->intended('/home');
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
