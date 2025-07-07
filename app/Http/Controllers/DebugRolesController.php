<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebugRolesController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Verificar roles directamente en la base de datos
        $rolesFromDb = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->select('roles.name')
            ->get()
            ->pluck('name')
            ->toArray();
            
        // Verificar usando el método hasRole personalizado
        $hasSuperAdminRole = $user->hasRole('super_admin');
        
        // Verificar usando el método original de Spatie (sin sobrescribir)
        $originalHasRole = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->where('roles.name', 'super_admin')
            ->exists();
            
        // Verificar permisos específicos
        $canViewAnyRole = $user->can('view_any_role');
        
        return response()->json([
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles_from_db' => $rolesFromDb,
            'has_super_admin_role_method' => $hasSuperAdminRole,
            'has_super_admin_role_db' => $originalHasRole,
            'can_view_any_role' => $canViewAnyRole,
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }
}
