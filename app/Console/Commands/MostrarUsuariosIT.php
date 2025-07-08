<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MostrarUsuariosIT extends Command
{
    protected $signature = 'app:mostrar-usuarios-it';
    protected $description = 'Muestra los usuarios con rol IT';

    public function handle()
    {
        $this->info("Buscando usuarios con rol IT...");
        
        // Consulta directa a la base de datos para obtener usuarios con rol IT
        $usuariosIT = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', '=', 'it')
            ->where('users.actiu', '=', true)
            ->select('users.id', 'users.name', 'users.email')
            ->get();
        
        if ($usuariosIT->isEmpty()) {
            $this->warn("No se encontraron usuarios con rol IT usando Shield.");
            
            // Intentar con el campo rol_principal
            $usuariosIT = DB::table('users')
                ->where('rol_principal', 'it')
                ->where('actiu', true)
                ->select('id', 'name', 'email')
                ->get();
                
            if ($usuariosIT->isEmpty()) {
                $this->error("No se encontraron usuarios IT en el sistema.");
                return 1;
            }
            
            $this->info("Se encontraron usuarios IT usando el campo rol_principal:");
        } else {
            $this->info("Se encontraron usuarios IT usando Shield:");
        }
        
        // Mostrar los usuarios en una tabla
        $headers = ['ID', 'Nombre', 'Email'];
        $rows = [];
        
        foreach ($usuariosIT as $usuario) {
            $rows[] = [
                $usuario->id,
                $usuario->name,
                $usuario->email ?? 'Sin email'
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
}
