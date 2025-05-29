<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * RouteServiceProvider
 * Proporciona la configuración de rutas para la aplicación.
 * Define las rutas de la API y las agrupa bajo el middleware 'api'.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    }
}
