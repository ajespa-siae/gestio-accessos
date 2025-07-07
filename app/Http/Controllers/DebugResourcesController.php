<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Filament\Resources\Resource;

class DebugResourcesController extends Controller
{
    public function index()
    {
        // Verificar autenticación
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();
        
        // Información del usuario
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'is_super_admin' => $user->hasRole('super_admin'),
            'can_view_admin' => $user->hasRole('admin'),
        ];
        
        // Información de roles y permisos
        $rolesData = Role::with('permissions')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name'),
            ];
        });
        
        // Información de recursos de Filament
        $resources = [];
        
        try {
            // Intentar obtener recursos registrados en el panel admin
            $panel = Filament::getPanel('admin');
            if ($panel) {
                $registeredResources = $panel->getResources();
                
                foreach ($registeredResources as $resource) {
                    $resourceClass = $resource;
                    $resourceLabel = $resourceClass::getModelLabel();
                    $resourceNavigation = $resourceClass::canViewAny();
                    $resourceSlug = $resourceClass::getSlug();
                    
                    $resources[] = [
                        'class' => $resourceClass,
                        'label' => $resourceLabel,
                        'can_view' => $resourceNavigation,
                        'slug' => $resourceSlug,
                    ];
                }
            }
        } catch (\Exception $e) {
            $resources[] = ['error' => $e->getMessage()];
        }
        
        // Información de configuración de Shield
        $shieldConfig = [
            'shield_resource_visible' => config('filament-shield.shield_resource.should_register_navigation', false),
            'shield_resource_group' => config('filament-shield.shield_resource.navigation_group', null),
            'super_admin_enabled' => config('filament-shield.super_admin.enabled', false),
            'super_admin_name' => config('filament-shield.super_admin.name', 'super_admin'),
            'define_via_gate' => config('filament-shield.super_admin.define_via_gate', false),
        ];
        
        return response()->json([
            'user' => $userData,
            'roles' => $rolesData,
            'resources' => $resources,
            'shield_config' => $shieldConfig,
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
        ]);
    }
}
