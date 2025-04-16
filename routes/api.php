<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripDetailsController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route pour récupérer les détails d'un covoit
Route::get('/trips/{id}/details', [TripDetailsController::class, 'getDetails']);
