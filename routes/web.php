<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ContactController;


// authentification
Route::get('/login', 'App\Http\Controllers\Auth\LoginController@showLoginForm')->name('login');
Route::post('/login', 'App\Http\Controllers\Auth\LoginController@login');
Route::post('/logout', 'App\Http\Controllers\Auth\LogoutController@logout')->name('logout');
Route::get('/register', 'App\Http\Controllers\Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('/register', 'App\Http\Controllers\Auth\RegisterController@register');


Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    } elseif (Auth::guard('employe')->check()) {
        return redirect()->route('employe.dashboard');
    } else {
        return redirect()->route('home');
    }
})->name('dashboard');

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

// formulaire de contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');


// Vérif des covoit
Route::get('/check-session', function () {
    // covoit présents dans la session?
    $hasCovoiturage = session()->has('covoiturages') && count(session('covoiturages')) > 0;
    return response()->json(['hasCovoiturage' => $hasCovoiturage]);
})->name('check-session');


// Afin de voir et de tester mon site sans message d'erreur, j'ai du ajouter ces routes provisoires
// La majeur partie son référencées dans le menu
Route::post('/search-covoiturage', function () {
    return redirect()->route('trips.index');
})->name('search.covoiturage');
Route::get('/covoiturage', function () {
    return view('trips.trips');
})->name('trips.index');
Route::get('/mentions-legales', function () {
    return view('mentions-legales');
})->name('mentions-legales');
Route::get('/covoiturage/{id}', function ($id) {
    return view('trips.show', ['id' => $id]);
})->name('trips.show');
