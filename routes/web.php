<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::post('/search-covoiturage', function () {
    // A remplacer plus tard par un vrai contrôleur
    return redirect()->route('trips.index');
})->name('search.covoiturage');

// Route provisoire pour trips.index (nécessaire car référencée dans le menu)
Route::get('/covoiturage', function () {
    return view('trips.index');
})->name('trips.index');

// Route provisoire pour contact (nécessaire car référencée dans le menu)
Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Route provisoire pour mentions légales (référencée dans le footer)
Route::get('/mentions-legales', function () {
    return view('mentions-legales');
})->name('mentions-legales');

// Route provisoire pour le dashboard (référencée pour les utilisateurs connectés)
Route::get('/dashboard', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    } elseif (Auth::guard('employe')->check()) {
        return redirect()->route('employe.dashboard');
    } else {
        return redirect()->route('home');
    }
})->name('dashboard');


// Routes d'authentification
Route::get('/login', 'App\Http\Controllers\Auth\LoginController@showLoginForm')->name('login');
Route::post('/login', 'App\Http\Controllers\Auth\LoginController@login');
Route::post('/logout', 'App\Http\Controllers\Auth\LogoutController@logout')->name('logout');
Route::get('/register', 'App\Http\Controllers\Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('/register', 'App\Http\Controllers\Auth\RegisterController@register');


// Routes pour les tableaux de bord
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::get('/employe/dashboard', function () {
    return view('employe.dashboard');
})->name('employe.dashboard');

// Pour résoudre l'erreur 404 après inscription
Route::get('/home', function () {
    return view('home');
})->name('home');
