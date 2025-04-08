<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckUserRole;

class CheckUserRoleServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        // Enregistre pour toutes les routes le middleware
        Route::pushMiddlewareToGroup('auth', CheckUserRole::class);
    }
}
