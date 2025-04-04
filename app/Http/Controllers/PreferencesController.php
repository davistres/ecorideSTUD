<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chauffeur;

class PreferencesController extends Controller
{

    public function update(Request $request)
    {
        $user = Auth::user();
        $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();

        if (!$chauffeur) {
            return response()->json(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier les préférences.'], 403);
        }

        $validated = $request->validate([
            'pref_smoke' => 'required|in:Fumeur,Non-fumeur',
            'pref_pet' => 'required|in:Acceptés,Non-acceptés',
            'pref_libre' => 'nullable|string|max:255',
        ]);

        // modele Chauffeur
        $chauffeur->pref_smoke = $validated['pref_smoke'];
        $chauffeur->pref_pet = $validated['pref_pet'];
        $chauffeur->pref_libre = $validated['pref_libre'];

        // Sauvegarde =>  base de données
        try {
            $chauffeur->save();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], 500);
        }
    }

     public function edit()
     {
          $user = Auth::user();
          $chauffeur = Chauffeur::where('user_id', $user->user_id)->first();
           return redirect()->route('home')->with('info', 'Utilisez la modale pour modifier les préférences.');
     }
}