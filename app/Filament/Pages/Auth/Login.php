<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use App\Auth\LdapFilamentAuthenticator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('Nombre de usuario')
                    ->required()
                    ->autocomplete('username')
                    ->autofocus(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required()
                    ->autocomplete('current-password'),
            ]);
    }

    /**
     * Sobrescribe el método de autenticación para usar nuestro autenticador LDAP personalizado
     * @return ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
     */
    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        Log::debug('Filament Login Page: Iniciando autenticación', [
            'username' => $data['username'],
            'ip' => request()->ip(),
        ]);

        // Intentar autenticar con nuestro autenticador LDAP personalizado
        $user = LdapFilamentAuthenticator::attempt(
            $data['username'],
            $data['password']
        );

        // Si la autenticación falló, mostrar error
        if (!$user) {
            Log::warning('Filament Login Page: Autenticación fallida', [
                'username' => $data['username'],
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        // Registrar el éxito de la autenticación
        Log::info('Filament Login Page: Autenticación exitosa', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => request()->ip(),
        ]);

        // Iniciar sesión con el usuario autenticado
        Auth::login($user, $data['remember'] ?? false);

        // Regenerar la sesión
        session()->regenerate();

        // Devolver la respuesta de login
        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}
