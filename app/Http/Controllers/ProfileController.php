<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return redirect()->route('home');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pseudo' => ['required', 'string', 'max:18'],
            'mail' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('UTILISATEUR', 'mail')->ignore($user->user_id, 'user_id'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->pseudo = $request->pseudo;
        $user->mail = $request->mail;

        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->password);
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('home')->with('success', 'Votre profil a été mis à jour avec succès!');
    }
}