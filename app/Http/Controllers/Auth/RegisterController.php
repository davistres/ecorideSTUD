<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    // formulaire d'inscription
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // inscription
    public function register(Request $request)
    {
        $request->validate([
            'pseudo' => ['required', 'string', 'max:18'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:UTILISATEUR,mail'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Créer l'utilisateur
        $user = Utilisateur::create([
            'pseudo' => $request->pseudo,
            'mail' => $request->email,
            'password_hash' => Hash::make($request->password),
            'n_credit' => 20,
            'role' => 'Passager', // Rôle par défaut
        ]);

        // Connecter l'utilisateur automatiquement
        Auth::guard('web')->login($user);

        return redirect('/home');
    }
}