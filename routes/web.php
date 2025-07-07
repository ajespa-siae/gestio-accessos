<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\DebugRolesController;


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

Route::get('/', [HomeController::class, 'index'])->name('home');

// Ruta personalizada para cerrar sesión
Route::post('/logout', [LogoutController::class, '__invoke'])
    ->name('logout')
    ->middleware('auth');

// Ruta de depuración para mostrar el usuario autenticado
Route::get('/debug/auth-user', [DebugController::class, 'showAuthUser'])
    ->name('debug.auth-user')
    ->middleware('auth');

// Rutas temporales para depuración
Route::get('/debug/roles', [\App\Http\Controllers\DebugRolesController::class, 'index'])->name('debug.roles');
Route::get('/debug/resources', [\App\Http\Controllers\DebugResourcesController::class, 'index'])->name('debug.resources');
