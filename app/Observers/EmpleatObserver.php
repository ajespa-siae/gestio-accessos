<?php

namespace App\Observers;

use App\Models\Empleat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class EmpleatObserver
{
    /**
     * Handle the Empleat "creating" event.
     */
    public function creating(Empleat $empleat): void
    {
        // Si el usuari_creador_id ya está establecido, no hacer nada
        if ($empleat->usuari_creador_id) {
            return;
        }

        // Intentar obtener el ID del usuario de diferentes fuentes
        $userId = null;

        // 1. Intentar obtener el usuario de la sesión
        if (session()->has('auth_user_id')) {
            $userId = session('auth_user_id');
            Log::info('EmpleatObserver: Usuario obtenido de la sesión', [
                'user_id' => $userId,
            ]);
        }
        // 2. Intentar obtener el usuario actual de Filament
        else if (Filament::auth()->check()) {
            $userId = Filament::auth()->user()->id;
            Log::info('EmpleatObserver: Usuario obtenido de Filament', [
                'user_id' => $userId,
            ]);
        }
        // 3. Intentar obtener el usuario de auth()
        else if (Auth::check()) {
            $userId = Auth::id();
            Log::info('EmpleatObserver: Usuario obtenido de Auth', [
                'user_id' => $userId,
            ]);
        }
        // 4. Intentar obtener el ID del usuario de la sesión de Filament
        else if (session()->has('filament.auth.id')) {
            $userId = session('filament.auth.id');
            Log::info('EmpleatObserver: Usuario obtenido de la sesión de Filament', [
                'user_id' => $userId,
            ]);
        }

        // Si se encontró un usuario, establecer el usuari_creador_id
        if ($userId) {
            $empleat->usuari_creador_id = $userId;
            Log::info('EmpleatObserver: Usuario creador establecido', [
                'user_id' => $userId,
                'empleat_id' => $empleat->id,
            ]);
        } else {
            Log::warning('EmpleatObserver: No se pudo obtener el usuario autenticado');
        }
    }
}
