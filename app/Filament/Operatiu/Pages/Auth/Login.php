<?php

namespace App\Filament\Operatiu\Pages\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\OperatiuLoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('Usuari')
                    ->required()
                    ->autocomplete('username')
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1])
                    ->rules(['alpha_dash']),
                
                TextInput::make('password')
                    ->label('Contrasenya')
                    ->hint(filament()->hasPasswordReset() ? new HtmlString(\Filament\Support\Facades\FilamentView::renderHook(
                        'panels::auth.login.form.actions',
                        fn () => new HtmlString("<a href='" . route('filament.operatiu.auth.password-reset.request') . "' class='text-sm text-primary-600 hover:text-primary-500 hover:underline'>" . __('filament-panels::pages/auth/login.actions.request_password_reset.label') . '</a>')
                    )) : null)
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('current-password')
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),
                
                Checkbox::make('remember')
                    ->label('Recorda la meva sessió')
                    ->extraInputAttributes(['tabindex' => 3]),
            ]);
    }
    
    public function authenticate(): ?LoginResponseContract
    {
        $data = $this->form->getState();
        
        \Log::info('Login Operatiu: Iniciando autenticación', [
            'username' => $data['username'],
            'ip' => request()->ip(),
        ]);
        
        try {
            // Usar el autenticador LDAP personalizado
            $user = \App\Auth\LdapFilamentAuthenticator::attempt(
                $data['username'],
                $data['password']
            );

            // Si la autenticación falló, mostrar error
            if (!$user) {
                \Log::warning('Login Operatiu: Autenticación fallida', [
                    'username' => $data['username'],
                    'ip' => request()->ip(),
                ]);

                $this->throwFailureValidationException();
                return null;
            }

            // Iniciar sesión con el usuario autenticado
            Auth::login($user, $data['remember'] ?? false);
            
            // Regenerar la sesión para prevenir fijación de sesión
            request()->session()->regenerate();
            
            // Registrar el éxito de la autenticación
            \Log::info('Login Operatiu: Autenticación exitosa', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => request()->ip(),
            ]);
            
            // Retornar respuesta de login exitoso
            return app(LoginResponse::class);
            
            // Si llegamos aquí, la autenticación falló
            $this->throwFailureValidationException();
            return null;
            
        } catch (\Exception $e) {
            // Registrar el error para depuración
            \Log::error('Error en el inicio de sesión: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            $this->throwFailureValidationException();
            return null;
        }
    }
    
    public function getTitle(): string
    {
        return 'Accés al Portal Operatiu';
    }
    
    public function getHeading(): string
    {
        return 'Gestor d\'Accessos';
    }
    
    public function hasLogo(): bool
    {
        return false;
    }
}
