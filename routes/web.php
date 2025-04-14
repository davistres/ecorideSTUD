<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\HomeController;

// Accueil
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Mentions légales
Route::get('/mentions-legales', function () {
    return view('mentions-legales');
})->name('mentions-legales');

// Formulaire de contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');



// authentification
Route::get('/login', 'App\Http\Controllers\Auth\LoginController@showLoginForm')->name('login');
Route::post('/login', 'App\Http\Controllers\Auth\LoginController@login');
Route::post('/logout', 'App\Http\Controllers\Auth\LogoutController@logout')->name('logout');
Route::get('/register', 'App\Http\Controllers\Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('/register', 'App\Http\Controllers\Auth\RegisterController@register');







// Redirection vers le dashboard selon le rôle
Route::get('/dashboard', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    } elseif (Auth::guard('employe')->check()) {
        return redirect()->route('employe.dashboard');
    } else {
        return redirect()->route('home');
    }
})->name('dashboard');

// Dashboard admin
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware('auth:admin')->name('admin.dashboard');

// Dashboard employé
Route::get('/employe/dashboard', function () {
    return view('employe.dashboard');
})->middleware('auth:employe')->name('employe.dashboard');

// Dashboard utilisateur
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');




// Mise à jour du rôle
Route::put('/user/role', [HomeController::class, 'updateRole'])->middleware('auth')->name('user.role.update');
Route::post('/user/role/reset', [HomeController::class, 'resetRole'])->middleware('auth')->name('user.role.reset');

// Édition du profil
Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->middleware('auth')->name('profile.update');


Route::post('/profile/photo/update', [HomeController::class, 'updateProfilePhoto'])->name('profile.photo.update');

Route::delete('/profile/photo/delete', [HomeController::class, 'deleteProfilePhoto'])->middleware('auth')->name('profile.photo.delete');






//Gestion des Véhicules
Route::get('/vehicles/create', [VehicleController::class, 'create'])->middleware('auth')->name('vehicle.create');
Route::post('/vehicles', [VehicleController::class, 'store'])->middleware('auth')->name('vehicle.store');
Route::get('/vehicles/{immat}/edit', [VehicleController::class, 'edit'])->middleware('auth')->name('vehicle.edit');
Route::put('/vehicles/{immat}', [VehicleController::class, 'update'])->middleware('auth')->name('vehicle.update');
Route::delete('/vehicles/{immat}', [VehicleController::class, 'destroy'])->middleware('auth')->name('vehicle.delete');
Route::get('/vehicles/{immat}', [VehicleController::class, 'show'])->middleware('auth')->name('vehicle.show');
// Route::delete('/vehicles/reset', [VehicleController::class, 'reset'])->middleware('auth')->name('vehicle.reset');

// Vérifier si un véhicule est lié à au moins un covoit
Route::get('/vehicles/{immat}/check-trips', [VehicleController::class, 'checkTrips'])->middleware('auth')->name('vehicle.check.trips');

// delete le dernier véhicule => réinitialiser le rôle
Route::delete('/vehicles/{immat}/reset-role', [VehicleController::class, 'destroyLastAndResetRole'])->middleware('auth')->name('vehicle.delete.reset.role');

// Routes pour les covoit
Route::get('/covoiturage', 'App\Http\Controllers\TripsController@index')->name('trips.index');
Route::post('/search-covoiturage', 'App\Http\Controllers\TripsController@search')->name('search.covoiturage');
Route::get('/covoiturage/{id}', 'App\Http\Controllers\TripsController@show')->name('trips.show');
Route::get('/covoiturage/{id}/participate', 'App\Http\Controllers\TripsController@participate')->name('trips.participate');

// API pour les détails d'un covoit
Route::get('/api/trips/{id}/details', 'App\Http\Controllers\Api\TripDetailsController@getDetails')->name('api.trips.details');

// Création et gestion des covoit
Route::post('/trip', [TripController::class, 'store'])->middleware('auth')->name('trip.store');
Route::get('/trip/{id}/edit', [TripController::class, 'edit'])->middleware('auth')->name('trip.edit');
Route::put('/trip/{id}', [TripController::class, 'update'])->middleware('auth')->name('trip.update');
Route::delete('/trip/{id}/cancel', [HomeController::class, 'cancelTrip'])->middleware('auth')->name('trip.cancel');
Route::post('/trip/{id}/start', [HomeController::class, 'startTrip'])->middleware('auth')->name('trip.start');
Route::post('/trip/{id}/end', [HomeController::class, 'endTrip'])->middleware('auth')->name('trip.end');
Route::get('/trip/{id}/passengers', [HomeController::class, 'getPassengers'])->middleware('auth')->name('trip.passengers');

// Annulation de résa
Route::delete('/reservation/{id}/cancel', [HomeController::class, 'cancelReservation'])->middleware('auth')->name('reservation.cancel');

// Vérification des covoit dans une session
Route::get('/check-session', function () {
    $hasCovoiturage = session()->has('covoiturages') && count(session('covoiturages')) > 0;
    return response()->json(['hasCovoiturage' => $hasCovoiturage]);
})->name('check-session');

// Préférences
Route::get('/preferences/edit', [PreferencesController::class, 'edit'])->middleware('auth')->name('preferences.edit');
Route::put('/preferences', [PreferencesController::class, 'update'])->middleware('auth')->name('preferences.update');

// Form satisfaction
Route::get('/satisfaction/{id}', [SatisfactionController::class, 'show'])->middleware('auth')->name('satisfaction.form');
Route::post('/satisfaction/{id}', [SatisfactionController::class, 'store'])->middleware('auth')->name('satisfaction.store');