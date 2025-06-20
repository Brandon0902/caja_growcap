<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\CategoriaIngresoController;
use App\Http\Controllers\SubcategoriaGastoController;
use App\Http\Controllers\SubcategoriaIngresoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('sucursales', SucursalController::class);
    Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class, 'toggle'])
     ->name('sucursales.toggle');

    Route::resource('cajas', CajaController::class);
    Route::patch('cajas/{caja}/toggle', [CajaController::class, 'toggle'])
     ->name('cajas.toggle');

    Route::resource('usuarios', UsuarioController::class);
    Route::patch('usuarios/{usuario}/toggle', [UsuarioController::class, 'toggle'])
         ->name('usuarios.toggle');

    Route::resource('movimientos-caja', MovimientoCajaController::class);

    Route::resource('categoria-gastos', CategoriaGastoController::class);

    Route::resource('subcategoria-gastos', SubcategoriaGastoController::class);

    Route::resource('categoria-ingresos', CategoriaIngresoController::class);

    Route::resource('subcategoria-ingresos', SubcategoriaIngresoController::class);

    Route::resource('prestamos', PrestamoController::class);

});

require __DIR__.'/auth.php';
