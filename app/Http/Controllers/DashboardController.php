<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Auth\LdapFilamentAuthenticator;

class DashboardController extends Controller
{
    /**
     * Mostrar la página de inicio de sesión
     */
    public function showLoginForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }
    
    /**
     * Manejar el intento de inicio de sesión
     */
    public function login(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        $username = $request->input('username');
        $password = $request->input('password');
        $remember = $request->boolean('remember');
        
        Log::debug('Intento de inicio de sesión', [
            'username' => $username,
            'ip' => $request->ip(),
        ]);
        
        // Intentar autenticar con nuestro autenticador LDAP personalizado
        $user = LdapFilamentAuthenticator::attempt($username, $password);
        
        if (!$user) {
            Log::warning('Autenticación fallida', [
                'username' => $username,
                'ip' => $request->ip(),
            ]);
            
            return back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors(['username' => 'Las credenciales proporcionadas no coinciden con nuestros registros.']);
        }
        
        // Iniciar sesión
        Auth::login($user, $remember);
        
        // Registrar el éxito
        Log::info('Autenticación exitosa', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
        ]);
        
        // Redirigir según el rol del usuario
        if ($user->rol_principal === 'admin' || $user->rol_principal === 'it') {
            return redirect()->route('filament.admin.pages.dashboard');
        }
        
        return redirect()->intended(route('dashboard'));
    }
    
    /**
     * Mostrar el dashboard del usuario
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        return view('dashboard', compact('user'));
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}
