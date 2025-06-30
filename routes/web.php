<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleatController;
use App\Http\Controllers\OnboardingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rutas públicas
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/login', [DashboardController::class, 'showLoginForm'])->name('login');
Route::post('/login', [DashboardController::class, 'login'])->name('login.attempt');
Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

// Actualizar dashboard existente
Route::get('/dashboard', [OnboardingController::class, 'dashboard'])->middleware(['auth'])->name('dashboard');

// Rutas protegidas para RRHH y admin
Route::middleware(['auth', 'check.role:rrhh,admin'])->group(function () {
    // Gestió empleats
    Route::resource('empleats', EmpleatController::class);
    Route::put('empleats/{empleat}/baixa', [EmpleatController::class, 'baixa'])->name('empleats.baixa');
    Route::post('empleats/{empleat}/recordatori', [EmpleatController::class, 'enviarRecordatori'])->name('empleats.recordatori');
    Route::post('empleats/{empleat}/observacio', [EmpleatController::class, 'afegirObservacio'])->name('empleats.observacio');
    
    // La gestió de plantilles de checklist es fa exclusivament des del panell Filament per administradors
});
