<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\SatisfactionController;


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
})->middleware('auth:admin')->name('admin.dashboard');

Route::get('/employe/dashboard', function () {
    return view('employe.dashboard');
})->middleware('auth:employe')->name('employe.dashboard');

// formulaire de contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');


// Vérif des covoit
Route::get('/check-session', function () {
    // covoit présents dans la session?
    $hasCovoiturage = session()->has('covoiturages') && count(session('covoiturages')) > 0;
    return response()->json(['hasCovoiturage' => $hasCovoiturage]);
})->name('check-session');


// Mentions légales
Route::get('/mentions-legales', function () {
    return view('mentions-legales');
})->name('mentions-legales');


// Routes pour les covoit
Route::get('/covoiturage', 'App\Http\Controllers\TripsController@index')->name('trips.index');
Route::post('/search-covoiturage', 'App\Http\Controllers\TripsController@search')->name('search.covoiturage');
Route::get('/covoiturage/{id}', 'App\Http\Controllers\TripsController@show')->name('trips.show');
Route::get('/covoiturage/{id}/participate', 'App\Http\Controllers\TripsController@participate')->name('trips.participate');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth')->name('home');
Route::put('/user/role', [App\Http\Controllers\HomeController::class, 'updateRole'])->middleware('auth')->name('user.role.update');

Route::get('/trip/create', [App\Http\Controllers\TripController::class, 'create'])->name('trip.create');
Route::post('/trip', [App\Http\Controllers\TripController::class, 'store'])->name('trip.store');
Route::delete('/trip/{id}/cancel', [App\Http\Controllers\HomeController::class, 'cancelTrip'])->middleware('auth')->name('trip.cancel');
Route::post('/trip/{id}/start', [App\Http\Controllers\HomeController::class, 'startTrip'])->middleware('auth')->name('trip.start');
Route::post('/trip/{id}/end', [App\Http\Controllers\HomeController::class, 'endTrip'])->middleware('auth')->name('trip.end');
Route::get('/trip/{id}/passengers', [App\Http\Controllers\HomeController::class, 'getPassengers'])->middleware('auth')->name('trip.passengers');
Route::delete('/reservation/{id}/cancel', [App\Http\Controllers\HomeController::class, 'cancelReservation'])->middleware('auth')->name('reservation.cancel');

// Voitures
Route::get('/vehicles/create', [App\Http\Controllers\VehicleController::class, 'create'])->name('vehicle.create');
Route::post('/vehicles', [App\Http\Controllers\VehicleController::class, 'store'])->name('vehicle.store');
Route::get('/vehicles/{immat}/edit', [App\Http\Controllers\VehicleController::class, 'edit'])->name('vehicle.edit');
Route::put('/vehicles/{immat}', [App\Http\Controllers\VehicleController::class, 'update'])->name('vehicle.update');
Route::delete('/vehicles/{immat}', [App\Http\Controllers\VehicleController::class, 'destroy'])->name('vehicle.delete');

// Préférences
Route::get('/preferences/edit', [App\Http\Controllers\PreferencesController::class, 'edit'])->name('preferences.edit');
Route::put('/preferences', [App\Http\Controllers\PreferencesController::class, 'update'])->name('preferences.update');

// Form satisfaction
Route::get('/satisfaction/{id}', [App\Http\Controllers\SatisfactionController::class, 'show'])->name('satisfaction.form');
Route::post('/satisfaction/{id}', [App\Http\Controllers\SatisfactionController::class, 'store'])->name('satisfaction.store');
Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->middleware('auth')->name('profile.update');