<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

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
        /**
         * ===== Helpers Blade para permisos en guard 'web' =====
         * Uso:
         *   @canweb('cajas.ver') ... @endcanweb
         *   @cananyweb(['cajas.ver','gastos.ver']) ... @endcananyweb
         */
        Blade::if('canweb', function (string $permission) {
            return auth('web')->check() && auth('web')->user()->can($permission);
        });

        Blade::if('cananyweb', function ($permissions) {
            $user = auth('web')->user();
            return $user ? $user->canAny((array) $permissions) : false;
        });

        /**
         * ===== (Opcional) Superpoder del rol admin =====
         * Si el usuario tiene rol 'admin' en el guard web, pasa cualquier Gate.
         * Quita este bloque si NO quieres ese comportamiento.
         */
        Gate::before(function ($user, $ability) {
            // Importante: asegúrate de que tu modelo User tenga protected $guard_name = 'web';
            return method_exists($user, 'hasRole') && $user->hasRole('superadmin') ? true : null;
        });

        /**
         * Rate limiter específico para /api/login
         * Úsalo con: ->middleware('throttle:login')
         */
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        /**
         * (Opcional) Rate limiter general de API.
         * Úsalo con: ->middleware('throttle:api')
         */
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?? $request->ip();
            return Limit::perMinute(60)->by($key);
        });
    }
}
