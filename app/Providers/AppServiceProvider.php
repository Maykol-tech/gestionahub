<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límites propios de la tienda online (no comparten contador con otras rutas públicas)
        \Illuminate\Support\Facades\RateLimiter::for('tienda-cotizar', fn (\Illuminate\Http\Request $r) => \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by('cotizar|' . $r->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('tienda-pedido', fn (\Illuminate\Http\Request $r) => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('pedido|' . $r->ip()));

        // Bases de una versión antigua: llena columnas viejas obligatorias que el programa ya no usa
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: *', function (string $evento, array $datos) {
            if (($datos[0] ?? null) instanceof \Illuminate\Database\Eloquent\Model) {
                \App\Support\ColumnasLegado::completar($datos[0]);
            }
        });
    }
}
