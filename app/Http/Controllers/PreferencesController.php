<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    public function create()
    {
        return view('Preferences.create');
    }

    public function store(Request $request)
    {
        // À faire
    }

    public function edit($immat)
    {
        // À faire
    }

    public function update(Request $request, $immat)
    {
        // À faire
    }

    public function destroy($immat)
    {
        // À faire
    }
}
