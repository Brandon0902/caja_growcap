<?php

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CajaController;
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
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\UserAbonoController;
use App\Http\Controllers\ConfigMoraController;
use App\Http\Controllers\UserAhorroController;
use App\Http\Controllers\UserLaboralController;
use App\Http\Controllers\UserDepositoController;
use App\Http\Controllers\UserPrestamoController;
use App\Http\Controllers\UserInversionController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\TicketRespuestaController;
use App\Http\Controllers\CategoriaIngresoController;
use App\Http\Controllers\SubcategoriaGastoController;
use App\Http\Controllers\SubcategoriaIngresoController;

Route::get('/', fn() => view('welcome'));

Route::get('/dashboard', fn() => view('dashboard'))
     ->middleware(['auth','verified'])
     ->name('dashboard');

Route::get('/municipios', function (Request $request) {
    $request->validate(['estado' => 'required|integer|exists:estados,id']);

    return \App\Models\Municipio::where('id_estado', $request->estado)
        ->orderBy('nombre')
        ->pluck('nombre', 'id');   //  {"123":"Guadalajara","124":"Zapopan"}
});

Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile',    [ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class,'destroy'])->name('profile.destroy');

    // Recursos varios...
    Route::resource('sucursales',           SucursalController::class);
    Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class,'toggle'])
         ->name('sucursales.toggle');

    Route::resource('cajas',                CajaController::class);
    Route::patch('cajas/{caja}/toggle',[CajaController::class,'toggle'])
         ->name('cajas.toggle');

    Route::resource('usuarios',             UsuarioController::class);
    Route::patch('usuarios/{usuario}/toggle',[UsuarioController::class,'toggle'])
         ->name('usuarios.toggle');

    Route::resource('movimientos-caja',     MovimientoCajaController::class);
    Route::resource('categoria-gastos',     CategoriaGastoController::class);
    Route::resource('subcategoria-gastos',  SubcategoriaGastoController::class);
    Route::resource('categoria-ingresos',   CategoriaIngresoController::class);
    Route::resource('subcategoria-ingresos',SubcategoriaIngresoController::class);
    Route::resource('prestamos',            PrestamoController::class);
    Route::resource('inversiones',          InversionController::class);
    Route::resource('ahorros',              AhorroController::class);
    Route::resource('empresas',             EmpresaController::class);
    Route::resource('clientes',             ClienteController::class);
    Route::resource('config_mora',          ConfigMoraController::class);
    Route::resource('preguntas',            PreguntaController::class);

    // User Ahorros & Inversiones
    Route::get('user-ahorros',      [UserAhorroController::class,'index'])->name('user_ahorros.index');
    Route::get('user-ahorros/{id}', [UserAhorroController::class,'show'])->name('user_ahorros.show');
    Route::get('user-inversiones',      [UserInversionController::class,'index'])->name('user_inversiones.index');
    Route::get('user-inversiones/{id}', [UserInversionController::class,'show'])->name('user_inversiones.show');

    // Listado de clientes (index)
    Route::get('user_data', [UserDataController::class, 'index'])
        ->name('user_data.index');

    // Formulario único (GET) para ver/crear/editar datos de un cliente
    Route::get('clientes/{cliente}/datos', [UserDataController::class, 'form'])
        ->name('clientes.datos.form');

    // Guardar (POST) los datos de ese cliente (crea o actualiza)
    Route::post('clientes/{cliente}/datos', [UserDataController::class, 'save'])
        ->name('clientes.datos.save');

    // Documentos
    Route::get('documentos',                      [DocumentoController::class,'index'])->name('documentos.index');
    Route::get('documentos/{userData}',           [DocumentoController::class,'show'])->name('documentos.show');
    Route::delete('documentos/{userData}/{field}',[DocumentoController::class,'destroyField'])->name('documentos.destroyField');
    Route::get('documentos/{userData}/create',    [DocumentoController::class,'create'])->name('documentos.create');
    Route::post('documentos/{userData}',          [DocumentoController::class,'store'])->name('documentos.store');
    Route::get('documentos/{userData}/download/{field}', [DocumentoController::class,'download'])->name('documentos.download');
    Route::get('documentos/{userData}/view/{field}',     [DocumentoController::class,'view'])->name('documentos.view');

    // Laborales
    Route::prefix('user_data/{userData}')
         ->name('user_data.laborales.')
         ->group(function(){
             Route::post   ('/laborales',            [UserLaboralController::class,'store'])->name('store');
             Route::put    ('/laborales/{laboral}',  [UserLaboralController::class,'update'])->name('update');
             Route::delete ('/laborales/{laboral}',  [UserLaboralController::class,'destroy'])->name('destroy');
         });

    // User Prestamos
    Route::get('user_prestamos',                    [UserPrestamoController::class,'index'])->name('user_prestamos.index');

    // CREAR primero
    Route::get('user_prestamos/create',             [UserPrestamoController::class,'create'])->name('user_prestamos.create');
    Route::post('user_prestamos',                   [UserPrestamoController::class,'store'])->name('user_prestamos.store');

    // Luego SHOW (por ID numérico)
    Route::get('user_prestamos/{cliente}',          [UserPrestamoController::class,'show'])
         ->where('cliente','\d+')
         ->name('user_prestamos.show');

    // Editar / actualizar
    Route::get('user_prestamos/{prestamo}/edit',    [UserPrestamoController::class,'edit'])->name('user_prestamos.edit');
    Route::put('user_prestamos/{prestamo}',         [UserPrestamoController::class,'update'])->name('user_prestamos.update');


    // 1) Clientes
    Route::get('adminuserabonos/clientes',            [UserAbonoController::class,'index'])
         ->name('adminuserabonos.clientes.index');

    // 2) Préstamos de un cliente
    Route::get('adminuserabonos/clientes/{id}/prestamos',
         [UserAbonoController::class,'showPrestamos'])
         ->name('adminuserabonos.prestamos.index');

    // 3) Abonos de un préstamo
    Route::get('adminuserabonos/prestamos/{id}/abonos',
         [UserAbonoController::class,'showAbonos'])
         ->name('adminuserabonos.abonos.index');

    // 4) Status rápido
    Route::post('adminuserabonos/abonos/{id}/status',
         [UserAbonoController::class,'updateStatus'])
         ->name('adminuserabonos.abonos.updateStatus');

    // 5) Modal Editar y 6) Guardar cambios
    Route::get('adminuserabonos/abonos/{id}/edit',
         [UserAbonoController::class,'edit'])
         ->name('adminuserabonos.abonos.edit');
    Route::put('adminuserabonos/abonos/{id}',
         [UserAbonoController::class,'update'])
         ->name('adminuserabonos.abonos.update');


    Route::get('/retiros',                  [RetiroController::class,'index'])->name('retiros.index');
    Route::get('/retiros/{cliente}',        [RetiroController::class,'show' ])->name('retiros.show');
    Route::patch('/retiros/inversion/{retiro}', [RetiroController::class,'updateInversion'])
        ->name('retiros.inversion.update');
    Route::patch('/retiros/ahorro/{retiroAhorro}', [RetiroController::class,'updateAhorro'])
        ->name('retiros.ahorro.update');

    Route::get('depositos',           [UserDepositoController::class,'index']) ->name('depositos.index');
    Route::get('depositos/{cliente}', [UserDepositoController::class,'show'])  ->name('depositos.show');
    Route::patch('depositos/{deposito}', [UserDepositoController::class,'update'])->name('depositos.update');

    Route::resource('mensajes', MensajeController::class);
    Route::get('mensajes/{mensaje}/imagen', [MensajeController::class,'viewImage'])
     ->name('mensajes.imagen');

    
    // ————————————————————————
    // Tickets de Soporte
    // ————————————————————————
    // Listar tickets
    Route::get('tickets', [TicketController::class,'index'])
         ->name('tickets.index');
    // Formulario de creación
    Route::get('tickets/create', [TicketController::class,'create'])
         ->name('tickets.create');
    // Guardar nuevo ticket
    Route::post('tickets', [TicketController::class,'store'])
         ->name('tickets.store');
    // Ver un ticket concreto
    Route::get('tickets/{ticket}', [TicketController::class,'show'])
         ->name('tickets.show');
    // Eliminar un ticket
    Route::delete('tickets/{ticket}', [TicketController::class,'destroy'])
         ->name('tickets.destroy');
    // Descargar adjunto
    Route::get('tickets/{ticket}/download', [TicketController::class,'download'])
         ->name('tickets.download');

    // ————————————————————————
    // Respuestas de Ticket
    // ————————————————————————
    // Guardar una nueva respuesta y cambiar estado
    Route::post('tickets/{ticket}/respuestas', [TicketRespuestaController::class,'store'])
         ->name('tickets.respuestas.store');
    // Eliminar una respuesta concreta
    Route::delete('tickets/respuestas/{respuesta}', [TicketRespuestaController::class,'destroy'])
         ->name('tickets.respuestas.destroy');


});

require __DIR__.'/auth.php';
