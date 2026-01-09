<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $logFile = storage_path('logs/schedule.log');
        $tz      = config('app.timezone', 'UTC');

        // ===== PRODUCCIÓN =====
        $schedule->command('cuentas:marcar-vencidos')
            ->dailyAt('01:00')
            ->timezone($tz)
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo($logFile);

        // Inversiones (acumulación diaria)
        $schedule->command('inversiones:accrue')
            ->dailyAt('12:20')
            ->timezone($tz)
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo($logFile);

        // Ahorros (acumulación diaria)
        $schedule->command('ahorros:accrue')
            ->dailyAt('02:20')
            ->timezone($tz)
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo($logFile);

        // === NUEVO: Mora compuesta de abonos vencidos (respeta período de gracia) ===
        $schedule->command('abonos:mora-daily')
            ->dailyAt('02:10')           // antes de ahorros para que quede listo el estado del día
            ->timezone($tz)
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo($logFile);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            HandleCors::class,
        ]);

        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'client.apikey'      => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
