<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerificarRolesUsuarios extends Command
{
    protected $signature = 'app:verificar-roles-usuarios {usuario_id?}';
    protected $description = 'Verifica los roles de Shield y rol_principal para usuarios';

    public function handle()
    {
        $usuarioId = $this->argument('usuario_id');
        
        if ($usuarioId) {
            $usuarios = User::where('id', $usuarioId)->get();
        } else {
            $usuarios = User::where('actiu', true)->get();
        }
        
        if ($usuarios->isEmpty()) {
            $this->error("No se encontraron usuarios");
            return 1;
        }
        
        $headers = ['ID', 'Nombre', 'rol_principal', 'Roles Shield'];
        $rows = [];
        
        foreach ($usuarios as $usuario) {
            $rolesShield = $usuario->roles->pluck('name')->join(', ');
            
            $rows[] = [
                $usuario->id,
                $usuario->name,
                $usuario->rol_principal ?? 'No definido',
                $rolesShield ?: 'Sin roles en Shield'
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
}
