<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserDepositoController;
use App\Http\Controllers\Api\UserAhorroApiController;
use App\Http\Controllers\Api\UserInversionApiController;
use App\Http\Controllers\Api\UserPrestamoApiController;
use App\Http\Controllers\Api\RetirosClienteApiController;
use App\Http\Controllers\Api\AbonosApiController;
use App\Http\Controllers\Api\MensajesApiController;
use App\Http\Controllers\Api\TicketsApiController;
use App\Http\Controllers\Api\TicketRepliesApiController;
use App\Http\Controllers\Api\MisDatosApiController;
use App\Http\Controllers\Api\UserLaboralApiController;
use App\Http\Controllers\Api\DocumentoApiController;
use App\Http\Controllers\Api\CreditScoreApiController;
use App\Http\Controllers\Api\PreguntasApiController;
use App\Http\Controllers\Api\DepositosCheckoutController;
use App\Http\Controllers\Api\SaldoDisponibleApiController;

use App\Http\Controllers\Stripe\WebhookController;

use App\Http\Controllers\Api\UserAhorroStripeController;
use App\Http\Controllers\Api\AbonoStripeController;

// ✅ NUEVO (Opción B)
use App\Http\Controllers\Api\StripeReturnMessageController;

// ✅ NUEVO: Inversiones (Stripe checkout + saldo)
use App\Http\Controllers\Api\UserInversionStripeController;
use App\Http\Controllers\Api\UserInversionSaldoController;

/*
|--------------------------------------------------------------------------
| Ping
|--------------------------------------------------------------------------
*/
Route::get('/ping', fn () => response()->json([
    'ok'   => true,
    'pong' => now()->toIso8601String(),
]));

/*
|--------------------------------------------------------------------------
| Webhook Stripe (público)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', WebhookController::class)
    ->name('stripe.webhook')
    ->withoutMiddleware(['auth', 'auth:sanctum'])
    ->middleware([]);

/*
|--------------------------------------------------------------------------
| AUTH (Sanctum – Bearer)
|--------------------------------------------------------------------------
*/


Route::middleware('auth:sanctum')->get(
    '/cliente/saldo-disponible',
    [SaldoDisponibleApiController::class, 'show']
);


Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {

    Route::get ('/me',              [AuthController::class, 'me'])->name('api.me');
    Route::post('/auth/logout',     [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('api.logoutAll');

    /*
    |--------------------------------------------------------------------------
    | ✅ Stripe return messages (Opción B)
    |--------------------------------------------------------------------------
    */
    Route::get('/stripe/return-messages/latest', [StripeReturnMessageController::class, 'latest'])
        ->name('api.stripe.return-messages.latest');

    Route::post('/stripe/return-messages/{id}/seen', [StripeReturnMessageController::class, 'markSeen'])
        ->whereNumber('id')
        ->name('api.stripe.return-messages.seen');

    Route::get('/stripe/return-messages/pull', [StripeReturnMessageController::class, 'pull'])
        ->name('api.stripe.return-messages.pull');

    /*
    |--------------------------------------------------------------------------
    | Depósitos
    |--------------------------------------------------------------------------
    */
    Route::get   ('/depositos',      [UserDepositoController::class, 'index'])->name('api.depositos.index');
    Route::post  ('/depositos',      [UserDepositoController::class, 'store'])->name('api.depositos.store');
    Route::delete('/depositos/{id}', [UserDepositoController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.depositos.destroy');

    Route::post('/depositos/{id}/checkout', [DepositosCheckoutController::class, 'create'])
        ->whereNumber('id')
        ->name('api.depositos.checkout');

    /*
    |--------------------------------------------------------------------------
    | Inversiones
    |--------------------------------------------------------------------------
    */
    Route::get   ('/inversiones',        [UserInversionApiController::class, 'index'])->name('api.inversiones.index');
    Route::post  ('/inversiones',        [UserInversionApiController::class, 'store'])->name('api.inversiones.store');
    Route::delete('/inversiones/{id}',   [UserInversionApiController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.inversiones.destroy');
    Route::get   ('/inversiones/planes', [UserInversionApiController::class, 'planes'])->name('api.inversiones.planes');

    // ✅ pago único (Stripe checkout)
    Route::post('/inversiones/{id}/stripe/checkout', [UserInversionStripeController::class, 'checkout'])
        ->whereNumber('id')
        ->name('api.inversiones.stripe.checkout');

    // ✅ pago con saldo
    Route::post('/inversiones/{id}/pay-saldo', [UserInversionSaldoController::class, 'paySaldo'])
        ->whereNumber('id')
        ->name('api.inversiones.pay-saldo');

    /*
    |--------------------------------------------------------------------------
    | Préstamos
    |--------------------------------------------------------------------------
    */
    Route::get   ('/prestamos',        [UserPrestamoApiController::class, 'index'])->name('api.prestamos.index');
    Route::post  ('/prestamos',        [UserPrestamoApiController::class, 'store'])->name('api.prestamos.store');
    Route::get   ('/prestamos/planes', [UserPrestamoApiController::class, 'planes'])->name('api.prestamos.planes');
    Route::delete('/prestamos/{id}',   [UserPrestamoApiController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.prestamos.destroy');

   
    /*
    |--------------------------------------------------------------------------
    | Ahorros  ✅ (con cambios)
    |--------------------------------------------------------------------------
    */
    Route::get   ('/ahorros',            [UserAhorroApiController::class, 'index'])->name('api.ahorros.index');
    Route::post  ('/ahorros',            [UserAhorroApiController::class, 'store'])->name('api.ahorros.store');
    Route::delete('/ahorros/{id}',       [UserAhorroApiController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.ahorros.destroy');
    
    Route::get ('/ahorros/planes',     [UserAhorroApiController::class, 'planes'])->name('api.ahorros.planes');
    Route::get ('/ahorros/frecuencia', [UserAhorroApiController::class, 'frecuencia'])->name('api.ahorros.frecuencia');
    
    /** Stripe Checkout (alta / update) */
    Route::post('/ahorros/{id}/stripe/checkout', [UserAhorroStripeController::class, 'checkout'])
        ->whereNumber('id')
        ->name('api.ahorros.stripe.checkout');
    
    /** Marcar fallido (cuando se cancela el pago o falla) */
    Route::post('/ahorros/{id}/marcar-fallido', [UserAhorroApiController::class, 'marcarFallido'])
        ->whereNumber('id')
        ->name('api.ahorros.marcar-fallido');
    
    /** Acciones NIP */
    Route::post('/ahorros/{id}/retirar', [UserAhorroApiController::class, 'retirar'])
        ->whereNumber('id')
        ->name('api.ahorros.retirar');
    
    Route::post('/ahorros/{id}/transferir', [UserAhorroApiController::class, 'transferir'])
        ->whereNumber('id')
        ->name('api.ahorros.transferir');
    
    Route::post('/ahorros/{id}/abonar-prestamo', [UserAhorroApiController::class, 'abonarPrestamo'])
        ->whereNumber('id')
        ->name('api.ahorros.abonar-prestamo');
    
    /**
     * ✅ Cambiar cuota (BD + si tiene stripe_subscription_id, también actualiza Stripe)
     * (No necesitas /cambiar-cuota-stripe)
     */
    Route::post('/ahorros/{id}/cambiar-cuota', [UserAhorroApiController::class, 'cambiarCuota'])
        ->whereNumber('id')
        ->name('api.ahorros.cambiar-cuota');
    
    /**
     * ✅ Cancelar suscripción Stripe (en tu controlador se llama cancelarSuscripcionStripe)
     */
    Route::post('/ahorros/{id}/cancelar-stripe', [UserAhorroApiController::class, 'cancelarSuscripcionStripe'])
        ->whereNumber('id')
        ->name('api.ahorros.cancelar-stripe');



    /*
    |--------------------------------------------------------------------------
    | Credit Score
    |--------------------------------------------------------------------------
    */
    Route::get ('/credit-score',        [CreditScoreApiController::class, 'show'])->name('api.credit-score.show');
    Route::post('/credit-score/recalc', [CreditScoreApiController::class, 'recalc'])->name('api.credit-score.recalc');

    /*
    |--------------------------------------------------------------------------
    | Documentos
    |--------------------------------------------------------------------------
    */
    Route::get   ('/documentos',              [DocumentoApiController::class, 'show'])->name('api.documentos.show');
    Route::post  ('/documentos',              [DocumentoApiController::class, 'store'])->name('api.documentos.store');
    Route::delete('/documentos/{field}',      [DocumentoApiController::class, 'destroyField'])->name('api.documentos.destroyField');
    Route::get   ('/documentos/view/{field}', [DocumentoApiController::class, 'view'])->name('api.documentos.view');

    /*
    |--------------------------------------------------------------------------
    | Laborales
    |--------------------------------------------------------------------------
    */
    Route::prefix('user-data/{userData}/laborales')
        ->whereNumber('userData')
        ->group(function () {
            Route::get   ('/',         [UserLaboralApiController::class, 'index'])->name('api.user-data.laborales.index');
            Route::post  ('/',         [UserLaboralApiController::class, 'store'])->name('api.user-data.laborales.store');
            Route::get   ('{laboral}', [UserLaboralApiController::class, 'show'])->whereNumber('laboral')->name('api.user-data.laborales.show');
            Route::put   ('{laboral}', [UserLaboralApiController::class, 'update'])->whereNumber('laboral')->name('api.user-data.laborales.update');
            Route::delete('{laboral}', [UserLaboralApiController::class, 'destroy'])->name('api.user-data.laborales.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Retiros (cliente)
    |--------------------------------------------------------------------------
    */
    Route::prefix('client/retiros')->group(function () {
        Route::get('/saldos',  [RetirosClienteApiController::class, 'saldos'])->name('api.retiros.saldos');
        Route::get('/ahorros', [RetirosClienteApiController::class, 'ahorrosConSaldo'])->name('api.retiros.ahorros');
        Route::get('/mis',     [RetirosClienteApiController::class, 'misRetiros'])->name('api.retiros.mis');

        Route::post('/ahorro',    [RetirosClienteApiController::class, 'solicitarAhorro'])->name('api.retiros.ahorro.store');
        Route::post('/inversion', [RetirosClienteApiController::class, 'solicitarInversion'])->name('api.retiros.inversion.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Cliente (módulos)
    |--------------------------------------------------------------------------
    */
    Route::prefix('cliente')->group(function () {

        Route::get ('abonos',      [AbonosApiController::class, 'index'])->name('api.cliente.abonos.index');
        Route::get ('abonos/{id}', [AbonosApiController::class, 'show'])->whereNumber('id')->name('api.cliente.abonos.show');
        Route::post('abonos/pagar', [AbonosApiController::class, 'pagar'])->name('api.cliente.abonos.pagar');

        Route::post('abonos/{abono}/stripe/checkout', [AbonoStripeController::class, 'checkout'])
            ->whereNumber('abono')
            ->name('api.cliente.abonos.stripe.checkout');

        Route::get('mensajes',      [MensajesApiController::class, 'index'])->name('api.cliente.mensajes.index');
        Route::get('mensajes/{id}', [MensajesApiController::class, 'show'])->whereNumber('id')->name('api.cliente.mensajes.show');

        Route::get('preguntas',      [PreguntasApiController::class, 'index'])->name('api.cliente.preguntas.index');
        Route::get('preguntas/{id}', [PreguntasApiController::class, 'show'])->whereNumber('id')->name('api.cliente.preguntas.show');

        Route::get ('tickets',                  [TicketsApiController::class, 'index'])->name('api.cliente.tickets.index');
        Route::post('tickets',                  [TicketsApiController::class, 'store'])->name('api.cliente.tickets.store');
        Route::get ('tickets/{ticket}',         [TicketsApiController::class, 'show'])->whereNumber('ticket')->name('api.cliente.tickets.show');
        Route::get ('tickets/{ticket}/adjunto', [TicketsApiController::class, 'downloadAttachment'])->whereNumber('ticket')->name('api.cliente.tickets.adjunto');
        Route::get ('tickets/{ticket}/replies', [TicketRepliesApiController::class, 'index'])->whereNumber('ticket')->name('api.cliente.tickets.replies.index');

        Route::get   ('mis-datos',                         [MisDatosApiController::class, 'me'])->name('api.cliente.mis-datos.me');
        Route::match(['put','patch'], 'mis-datos',         [MisDatosApiController::class, 'upsert'])->name('api.cliente.mis-datos.upsert');
        Route::get   ('cat/estados',                       [MisDatosApiController::class, 'estados'])->name('api.cliente.mis-datos.cat.estados');
        Route::get   ('cat/estados/{idEstado}/municipios', [MisDatosApiController::class, 'municipios'])->whereNumber('idEstado')->name('api.cliente.mis-datos.cat.municipios');

        // ✅✅ NUEVO: cambiar contraseña desde portal cliente
        Route::post('password', [MisDatosApiController::class, 'updatePassword'])
            ->name('api.cliente.password.update');
            
        Route::post('mis-datos/password', [MisDatosApiController::class, 'updatePassword'])
            ->name('api.cliente.mis-datos.password.update');
            
    });
});
