<?php

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\AhorroController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UserDataController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\InversionController;
use App\Http\Controllers\ConfigMoraController;
use App\Http\Controllers\UserAhorroController;
use App\Http\Controllers\UserLaboralController;
use App\Http\Controllers\UserInversionController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\CategoriaIngresoController;
use App\Http\Controllers\SubcategoriaGastoController;
use App\Http\Controllers\SubcategoriaIngresoController;

Route::get('/', fn() => view('welcome'));

Route::get('/dashboard', fn() => view('dashboard'))
     ->middleware(['auth','verified'])
     ->name('dashboard');

Route::get('/municipios', function(Request $request) {
    $request->validate(['estado' => 'required|integer|exists:estados,id']);
    return Municipio::where('id_estado',$request->estado)
                    ->orderBy('nombre')
                    ->get(['id','nombre']);
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
    Route::get('user-ahorros',     [UserAhorroController::class,'index'])->name('user_ahorros.index');
    Route::get('user-ahorros/{id}',[UserAhorroController::class,'show'])->name('user_ahorros.show');
    Route::get('user-inversiones',     [UserInversionController::class,'index'])->name('user_inversiones.index');
    Route::get('user-inversiones/{id}',[UserInversionController::class,'show'])->name('user_inversiones.show');

    Route::resource('user_data', UserDataController::class)
     ->parameters(['user_data' => 'userData']);

      // 1. Lista de todos los user_data (con link a sus documentos)
    Route::get('documentos', [DocumentoController::class,'index'])
         ->name('documentos.index');

    // 2. Ver los documentos de un user_data concreto
    Route::get('documentos/{userData}', [DocumentoController::class,'show'])
         ->name('documentos.show');

    // 3. Borrar un campo de documento específico
    Route::delete('documentos/{userData}/{field}', [DocumentoController::class,'destroyField'])
         ->name('documentos.destroyField');

    // Mostrar el formulario de subida
    Route::get('documentos/{userData}/create', [DocumentoController::class,'create'])
        ->name('documentos.create');

    // Procesar la subida
    Route::post('documentos/{userData}', [DocumentoController::class,'store'])
        ->name('documentos.store');

    Route::get('documentos/{userData}/download/{field}', [DocumentoController::class,'download'])
        ->name('documentos.download');

    Route::get('documentos/{userData}/view/{field}', [DocumentoController::class,'view'])
        ->name('documentos.view');

    Route::prefix('user_data/{userData}')
     ->name('user_data.laborales.')
     ->group(function(){
         Route::post   ('/laborales',          [UserLaboralController::class,'store'])->name('store');
         Route::put    ('/laborales/{laboral}',[UserLaboralController::class,'update'])->name('update');
         Route::delete ('/laborales/{laboral}',[UserLaboralController::class,'destroy'])->name('destroy');
     });

});

require __DIR__.'/auth.php';
