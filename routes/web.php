<?php

use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

use App\Http\Controllers\CajaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\AhorroController;
use App\Http\Controllers\RetiroController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UserDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\UserAbonoController;
use App\Http\Controllers\ConfigMoraController;
use App\Http\Controllers\UserAhorroController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\UserLaboralController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\UserDepositoController;
use App\Http\Controllers\UserPrestamoController;
use App\Http\Controllers\UserInversionController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\CuentaPorPagarController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\TicketRespuestaController;
use App\Http\Controllers\CategoriaIngresoController;
use App\Http\Controllers\SubcategoriaGastoController;
use App\Http\Controllers\SubcategoriaIngresoController;
use App\Http\Controllers\CuentaPorPagarDetalleController;
use App\Http\Controllers\Payments\StripeWebhookController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/* ========================= Dashboard ========================= */
Route::middleware(['auth','permission:dashboard.ver'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


/* ============== Util: municipios por estado ================== */
Route::get('/municipios', function (Request $request) {
    $request->validate(['estado' => 'required|integer|exists:estados,id']);
    return Municipio::where('id_estado', $request->estado)
        ->orderBy('nombre')->pluck('nombre', 'id');
});

/* ======================== Rutas autenticadas ======================== */
Route::middleware('auth')->group(function () {

    /* Perfil */
    Route::get('/profile',    [ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class,'destroy'])->name('profile.destroy');

    /* Sucursales */
    Route::resource('sucursales', SucursalController::class)
        ->middleware('permission:sucursales.ver|sucursales.crear|sucursales.editar|sucursales.eliminar');
    Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class,'toggle'])
        ->name('sucursales.toggle')->middleware('permission:sucursales.editar');

    /* Cajas (+ alcances) */
    Route::resource('cajas', CajaController::class)
        ->middleware('permission:cajas.ver|cajas.crear|cajas.editar|cajas.eliminar|cajas.ver_todas|cajas.ver_sucursal|cajas.ver_asignadas');
    Route::patch('cajas/{caja}/toggle', [CajaController::class,'toggle'])
        ->name('cajas.toggle')->middleware('permission:cajas.editar');

    /* Usuarios */
    Route::resource('usuarios', UsuarioController::class)
        ->middleware('permission:usuarios.ver|usuarios.crear|usuarios.editar|usuarios.eliminar');
    Route::patch('usuarios/{usuario}/toggle', [UsuarioController::class,'toggle'])
        ->name('usuarios.toggle')->middleware('permission:usuarios.editar');

    /* Movimientos de caja (+ alcances) */
    Route::resource('movimientos-caja', MovimientoCajaController::class)
        ->parameters(['movimientos-caja' => 'movimiento'])
        ->middleware('permission:movimientos_caja.ver|movimientos_caja.crear|movimientos_caja.editar|movimientos_caja.eliminar|movimientos_caja.ver_todas|movimientos_caja.ver_sucursal|movimientos_caja.ver_asignadas');

    /* Categorías y Subcategorías */
    Route::resource('categoria-gastos',      CategoriaGastoController::class)
        ->middleware('permission:categoria_gastos.ver|categoria_gastos.crear|categoria_gastos.editar|categoria_gastos.eliminar');
    Route::resource('subcategoria-gastos',   SubcategoriaGastoController::class)
        ->middleware('permission:subcategoria_gastos.ver|subcategoria_gastos.crear|subcategoria_gastos.editar|subcategoria_gastos.eliminar');
    Route::resource('categoria-ingresos',    CategoriaIngresoController::class)
        ->middleware('permission:categoria_ingresos.ver|categoria_ingresos.crear|categoria_ingresos.editar|categoria_ingresos.eliminar');
    Route::resource('subcategoria-ingresos', SubcategoriaIngresoController::class)
        ->middleware('permission:subcategoria_ingresos.ver|subcategoria_ingresos.crear|subcategoria_ingresos.editar|subcategoria_ingresos.eliminar');

    /* Préstamos / Inversiones / Ahorros (Admin) */
    Route::resource('prestamos',   PrestamoController::class)
        ->middleware('permission:prestamos.ver|prestamos.crear|prestamos.editar|prestamos.eliminar');
   
    Route::get('inversiones', [InversionController::class,'index'])
        ->name('inversiones.index')
        ->middleware('permission:inversiones.ver');

    Route::get('inversiones/create', [InversionController::class,'create'])
        ->name('inversiones.create')
        ->middleware('permission:inversiones.crear');

    Route::post('inversiones', [InversionController::class,'store'])
        ->name('inversiones.store')
        ->middleware('permission:inversiones.crear');

    Route::get('inversiones/{inversion}', [InversionController::class,'show'])
        ->name('inversiones.show')
        ->middleware('permission:inversiones.ver');

    Route::get('inversiones/{inversion}/edit', [InversionController::class,'edit'])
        ->name('inversiones.edit')
        ->middleware('permission:inversiones.editar');

    Route::put('inversiones/{inversion}', [InversionController::class,'update'])
        ->name('inversiones.update')
        ->middleware('permission:inversiones.editar');

    Route::delete('inversiones/{inversion}', [InversionController::class,'destroy'])
        ->name('inversiones.destroy')
        ->middleware('permission:inversiones.eliminar');
    
     // Cambio rápido de estado (select en index)
    Route::patch('inversiones/{inversion}/status', [InversionController::class,'updateStatus'])
        ->name('inversiones.updateStatus')
        ->middleware('permission:inversiones.editar');

    // Borrado permanente (la X del index)
    Route::delete('inversiones/{inversion}/force', [InversionController::class,'forceDestroy'])
        ->name('inversiones.forceDestroy')
        ->middleware('permission:inversiones.eliminar');

   
    Route::resource('ahorros',     AhorroController::class)
        ->middleware('permission:ahorros.ver|ahorros.crear|ahorros.editar|ahorros.eliminar');

    /* Empresas / Clientes */
    Route::resource('empresas', EmpresaController::class)
        ->middleware('permission:empresas.ver|empresas.crear|empresas.editar|empresas.eliminar');

    Route::resource('clientes', ClienteController::class)
        ->middleware('permission:clientes.ver|clientes.crear|clientes.editar|clientes.eliminar|clientes.ver_todas|clientes.ver_sucursal|clientes.ver_asignadas');

    /* Config Mora / Preguntas */
    Route::resource('config_mora', ConfigMoraController::class)
        ->middleware('permission:config_mora.ver|config_mora.crear|config_mora.editar|config_mora.eliminar');

    Route::resource('preguntas',   PreguntaController::class)
        ->middleware('permission:preguntas.ver|preguntas.crear|preguntas.editar|preguntas.eliminar');

    /* ==================== Cuentas por pagar ==================== */

    /* Fuerza que {cuenta} sea numérico, para que el resource no capture 'abonos' ni otras palabras */
    Route::pattern('cuenta', '[0-9]+');

    /* Índice global de abonos (ANTES del resource, y en path /abonos) */
    Route::get('cuentas-por-pagar/abonos',
        [CuentaPorPagarDetalleController::class, 'index'])
        ->name('cuentas-por-pagar.abonos.index')
        ->middleware('permission:cuentas_pagar.ver');

    /* Resource principal */
    Route::resource('cuentas-por-pagar', CuentaPorPagarController::class)
        ->parameters(['cuentas-por-pagar' => 'cuenta'])
        ->middleware('permission:cuentas_pagar.ver|cuentas_pagar.crear|cuentas_pagar.editar|cuentas_pagar.eliminar');

    /* Parcial de vencidos (para modal/partial) */
    Route::get('cuentas-por-pagar/{cuenta}/vencidos',
        [CuentaPorPagarController::class, 'vencidos'])
        ->name('cuentas-por-pagar.vencidos')
        ->middleware('permission:cuentas_pagar.ver');

    /* Resource anidado de detalles (shallow), sin index (ya definido arriba) */
    Route::resource('cuentas-por-pagar.detalles', CuentaPorPagarDetalleController::class)
        ->except(['index'])
        ->shallow()
        ->middleware('permission:cuentas_pagar.ver|cuentas_pagar.crear|cuentas_pagar.editar|cuentas_pagar.eliminar');

    /* Pagar un detalle (plural usado por vistas nuevas) */
    Route::patch('cuentas-por-pagar/detalles/{detalle}/pagar',
        [CuentaPorPagarController::class, 'pagarDetalle'])
        ->name('cuentas-por-pagar.detalles.pagar')
        ->middleware('permission:cuentas_pagar.editar');

    /* Alias singular para compatibilidad con vistas antiguas */
    Route::patch('cuentas-por-pagar/detalle/{detalle}/pagar',
        [CuentaPorPagarController::class, 'pagarDetalle'])
        ->name('cuentas-por-pagar.detalle.pagar')
        ->middleware('permission:cuentas_pagar.editar');

    /* Pagar todos los vencidos (pago total) */
    Route::post('cuentas-por-pagar/{cuenta}/pagar-total',
        [CuentaPorPagarController::class, 'pagarTotal'])
        ->name('cuentas-por-pagar.pagar-total')
        ->middleware('permission:cuentas_pagar.editar');
        
        
    /* Contabilidad / Presupuestos */
    Route::get('/contabilidad', [ContabilidadController::class, 'index'])
        ->name('contabilidad.index')->middleware('permission:contabilidad_profunda.ver');

    Route::get ('presupuestos',      [PresupuestoController::class,'index'])->name('presupuestos.index')->middleware('permission:presupuestos.ver');
    Route::post('presupuestos',      [PresupuestoController::class,'store'])->name('presupuestos.store')->middleware('permission:presupuestos.crear');
    Route::put ('presupuestos/{id}', [PresupuestoController::class,'update'])->name('presupuestos.update')->middleware('permission:presupuestos.editar');
    Route::patch('/prestamos/{prestamo}/estado', [PrestamoController::class, 'quickStatus'])
     ->name('prestamos.quick-status');


    /* Ficha cliente */
    Route::get('user_data', [UserDataController::class, 'index'])->name('user_data.index')
        ->middleware('permission:user_data.ver|user_data.crear|user_data.editar|user_data.eliminar');

    Route::get ('clientes/{cliente}/datos', [UserDataController::class, 'form'])->name('clientes.datos.form')
        ->middleware('permission:user_data.ver|user_data.editar|user_data.crear');
    Route::post('clientes/{cliente}/datos', [UserDataController::class, 'save'])->name('clientes.datos.save')
        ->middleware('permission:user_data.crear|user_data.editar');

    /* Documentos */
    Route::get   ('documentos',                             [DocumentoController::class,'index'])->name('documentos.index')->middleware('permission:documentos.ver|documentos.crear|documentos.editar|documentos.eliminar');
    Route::get   ('documentos/{userData}',                  [DocumentoController::class,'show'])->name('documentos.show')->middleware('permission:documentos.ver');
    Route::delete('documentos/{userData}/{field}',          [DocumentoController::class,'destroyField'])->name('documentos.destroyField')->middleware('permission:documentos.eliminar');
    Route::get   ('documentos/{userData}/create',           [DocumentoController::class,'create'])->name('documentos.create')->middleware('permission:documentos.crear');
    Route::post  ('documentos/{userData}',                  [DocumentoController::class,'store'])->name('documentos.store')->middleware('permission:documentos.crear');
    Route::get   ('documentos/{userData}/download/{field}', [DocumentoController::class,'download'])->name('documentos.download')->middleware('permission:documentos.ver');
    Route::get   ('documentos/{userData}/view/{field}',     [DocumentoController::class,'view'])->name('documentos.view')->middleware('permission:documentos.ver');

    /* Laboral embebido */
    Route::prefix('user_data/{userData}')->name('user_data.laborales.')->middleware('permission:user_data.ver|user_data.editar|user_data.crear')->group(function () {
        Route::post  ('/laborales',           [UserLaboralController::class,'store'])->name('store');
        Route::put   ('/laborales/{laboral}', [UserLaboralController::class,'update'])->name('update');
        Route::delete('/laborales/{laboral}', [UserLaboralController::class,'destroy'])->name('destroy')->middleware('permission:user_data.eliminar');
    });

    /* User Préstamos */
    Route::resource('user_prestamos', UserPrestamoController::class)
        ->parameters(['user_prestamos' => 'prestamo'])
        ->names('user_prestamos')
        ->middleware('permission:user_prestamos.ver|user_prestamos.crear|user_prestamos.editar|user_prestamos.eliminar');
    Route::get ('user_prestamos/{prestamo}/edit', [UserPrestamoController::class,'edit'])->name('user_prestamos.edit')->middleware('permission:user_prestamos.editar');
    Route::put ('user_prestamos/{prestamo}',      [UserPrestamoController::class,'update'])->name('user_prestamos.update')->middleware('permission:user_prestamos.editar');

    /* User Inversiones (names y binding correctos) */
    Route::resource('user-inversiones', UserInversionController::class)
        ->parameters(['user-inversiones' => 'inversion'])
        ->names('user_inversiones')
        ->middleware('permission:user_inversiones.ver|user_inversiones.crear|user_inversiones.editar|user_inversiones.eliminar');
    Route::get ('user-inversiones/{inversion}/edit', [UserInversionController::class,'edit'])->name('user_inversiones.edit')->middleware('permission:user_inversiones.editar');
    Route::put ('user-inversiones/{inversion}',      [UserInversionController::class,'update'])->name('user_inversiones.update')->middleware('permission:user_inversiones.editar');

    /* User Ahorros */
    Route::resource('user-ahorros', UserAhorroController::class)
        ->parameters(['user-ahorros' => 'ahorro'])
        ->names('user_ahorros')
        ->middleware('permission:user_ahorros.ver|user_ahorros.crear|user_ahorros.editar|user_ahorros.eliminar');

    /* Retiros */
    Route::get  ('/retiros',                       [RetiroController::class,'index'])->name('retiros.index')->middleware('permission:retiros.ver');
    Route::get  ('/retiros/{cliente}',             [RetiroController::class,'show'])->name('retiros.show')->middleware('permission:retiros.ver');
    Route::patch('/retiros/inversion/{retiro}',    [RetiroController::class,'updateInversion'])->name('retiros.inversion.update')->middleware('permission:retiros.editar');
    Route::patch('/retiros/ahorro/{retiroAhorro}', [RetiroController::class,'updateAhorro'])->name('retiros.ahorro.update')->middleware('permission:retiros.editar');
    // routes/web.php
    Route::post('/retiros/crear', [RetiroController::class, 'store'])
        ->name('retiros.store')
        ->middleware('permission:retiros.crear');

    /* Proveedores */
    Route::resource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedore'])
        ->middleware('permission:proveedores.ver|proveedores.crear|proveedores.editar|proveedores.eliminar');
    Route::patch('proveedores/{proveedore}/toggle', [ProveedorController::class, 'toggle'])->name('proveedores.toggle')->middleware('permission:proveedores.editar');

    /* Depósitos (user) */
    Route::resource('depositos', UserDepositoController::class)
        ->parameters(['depositos' => 'deposito'])
        ->middleware('permission:depositos.ver|depositos.crear|depositos.editar|depositos.eliminar');

    /* Mensajes */
    Route::resource('mensajes', MensajeController::class)
        ->middleware('permission:mensajes.ver|mensajes.crear|mensajes.editar|mensajes.eliminar');
    Route::get('mensajes/{mensaje}/imagen', [MensajeController::class,'viewImage'])->name('mensajes.imagen')->middleware('permission:mensajes.ver');

    /* Gastos (transacciones entre cajas) */
    
    Route::get('/gastos/{gasto}/comprobante', [GastoController::class, 'comprobante'])->name('gastos.comprobante');
    Route::resource('gastos', GastoController::class)
        ->middleware('permission:gastos.ver|gastos.crear|gastos.editar|gastos.eliminar');
    Route::get('gastos/{gasto}/comprobante', [GastoController::class, 'comprobante'])->name('gastos.comprobante')->middleware('permission:gastos.ver');

    /* Tickets */
    Route::get   ('tickets',                     [TicketController::class,'index'])->name('tickets.index')->middleware('permission:tickets.ver');
    Route::get   ('tickets/create',              [TicketController::class,'create'])->name('tickets.create')->middleware('permission:tickets.crear');
    Route::post  ('tickets',                     [TicketController::class,'store'])->name('tickets.store')->middleware('permission:tickets.crear');

    Route::get   ('tickets/{ticket}',            [TicketController::class,'show'])->name('tickets.show')->middleware('permission:tickets.ver');

    /* <-- FALTABAN ESTAS DOS */
    Route::get   ('tickets/{ticket}/edit',       [TicketController::class,'edit'])->name('tickets.edit')->middleware('permission:tickets.editar');
    Route::put   ('tickets/{ticket}',            [TicketController::class,'update'])->name('tickets.update')->middleware('permission:tickets.editar');

    Route::delete('tickets/{ticket}',            [TicketController::class,'destroy'])->name('tickets.destroy')->middleware('permission:tickets.eliminar');

    /* Corrige el método del adjunto: tu controlador usa downloadAttachment */
    Route::get   ('tickets/{ticket}/download',   [TicketController::class,'downloadAttachment'])->name('tickets.download')->middleware('permission:tickets.ver');

    /* Respuestas */
    Route::post  ('tickets/{ticket}/respuestas',    [TicketRespuestaController::class,'store'])->name('tickets.respuestas.store')->middleware('permission:tickets.editar|tickets.crear');
    Route::delete('tickets/respuestas/{respuesta}', [TicketRespuestaController::class,'destroy'])->name('tickets.respuestas.destroy')->middleware('permission:tickets.eliminar');


    /* ======== ADMINUSERABONOS (incluye la ruta general) ======== */
    Route::get('adminuserabonos/clientes',                [UserAbonoController::class,'index'])->name('adminuserabonos.clientes.index')->middleware('permission:adminuserabonos.ver');
    Route::get('adminuserabonos/clientes/{id}/prestamos', [UserAbonoController::class,'showPrestamos'])->name('adminuserabonos.prestamos.index')->middleware('permission:adminuserabonos.ver');
    Route::get('adminuserabonos/prestamos/{id}/abonos',   [UserAbonoController::class,'showAbonos'])->name('adminuserabonos.abonos.index')->middleware('permission:adminuserabonos.ver');
    Route::post('adminuserabonos/abonos/{id}/status',     [UserAbonoController::class,'updateStatus'])->name('adminuserabonos.abonos.updateStatus')->middleware('permission:adminuserabonos.editar');
    Route::get ('adminuserabonos/abonos/{id}/edit',       [UserAbonoController::class,'edit'])->name('adminuserabonos.abonos.edit')->middleware('permission:adminuserabonos.editar');
    Route::put ('adminuserabonos/abonos/{id}',            [UserAbonoController::class,'update'])->name('adminuserabonos.abonos.update')->middleware('permission:adminuserabonos.editar');

    Route::get ('adminuserabonos/abonos',                 [UserAbonoController::class,'generalIndex'])->name('adminuserabonos.abonos.general')->middleware('permission:adminuserabonos.ver');
});

/* =================== Módulo de permisos (solo admin) =================== */
Route::middleware(['auth','role:admin'])
    ->prefix('admin/permisos')
    ->name('admin.permisos.')
    ->group(function () {

        Route::get('/', [PermisosController::class, 'index'])->name('index');

        // ✅ NOMBRES EXACTOS como los pide la VISTA
        Route::post('/roles/{role}/sync', [PermisosController::class, 'updateRolePermissions'])
            ->name('updateRolePermissions');

        Route::post('/usuarios/{user}/sync-roles', [PermisosController::class, 'syncUserRoles'])
            ->name('syncUserRoles');

        Route::post('/sync-enum', [PermisosController::class, 'syncEnumToSpatie'])
            ->name('syncEnumToSpatie');

        Route::post('/cache-reset', [PermisosController::class, 'cacheReset'])
            ->name('cacheReset');

        Route::post('/prune', [PermisosController::class, 'pruneAndNormalize'])
            ->name('pruneAndNormalize');
    });

Route::get('/admin/flush-permissions-cache', function () {
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    return 'ok';
})->middleware(['auth','role:admin']);


Route::get('/mail-test', function () {
    Mail::to('salasdegargas1@gmail.com')->send(new TestEmail());
    return 'Correo enviado (si no truena)';
});

Route::post('/stripe/webhook', StripeWebhookController::class);

require __DIR__.'/auth.php';
