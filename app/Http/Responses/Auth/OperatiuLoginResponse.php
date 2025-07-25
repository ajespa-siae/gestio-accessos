<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class OperatiuLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = auth()->user();
        
        Log::info('OperatiuLoginResponse: Procesando respuesta de login', [
            'user_id' => $user->id,
            'username' => $user->username,
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);
        
        // Redirigir al dashboard del panel operatiu
        $redirectUrl = filament()->getPanel('operatiu')->getUrl();
        
        Log::info('OperatiuLoginResponse: Redirigiendo a: ' . $redirectUrl);
        
        return new RedirectResponse($redirectUrl);
    }
}
