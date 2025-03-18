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

        // employé?
        if ($credentials['email'] === 'employe@ecoride.fr') {
            $employe = \App\Models\Employe::where('mail', $credentials['email'])->first();

            if ($employe) {
                // comparer les mots de passe
                if (password_verify($credentials['password'], $employe->password_hash)) {
                    session(['employe_id' => $employe->employe_id]);
                    session(['employe_name' => $employe->name]);
                    session(['employe_authenticated' => true]);

                    $request->session()->regenerate();
                    return redirect()->route('employe.dashboard');
                }
            }
        }

        // si c'est pas un EMPLOYE?
        $guards = ['admin', 'web'];
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->attempt([
                'mail' => $credentials['email'],
                'password' => $credentials['password']
            ])) {
                $request->session()->regenerate();

                if ($guard == 'admin') {
                    return redirect()->route('admin.dashboard');
                } else {
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