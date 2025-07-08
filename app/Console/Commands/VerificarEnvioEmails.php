<?php

namespace App\Console\Commands;

use App\Mail\NovaChecklistMail;
use App\Models\ChecklistInstance;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VerificarEnvioEmails extends Command
{
    protected $signature = 'app:verificar-envio-emails {checklist_id}';
    protected $description = 'Verifica el envío de emails a usuarios IT para una checklist específica';

    public function handle()
    {
        $checklistId = $this->argument('checklist_id');
        
        $checklist = ChecklistInstance::find($checklistId);
        
        if (!$checklist) {
            $this->error("No se encontró la checklist con ID: {$checklistId}");
            return 1;
        }
        
        $empleat = $checklist->empleat;
        
        $this->info("Verificando envío de emails para la checklist {$checklistId} del empleado {$empleat->nom_complet}");
        
        // Obtener usuarios con rol IT
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
        }
        
        $this->info("Se encontraron " . $usuariosIT->count() . " usuarios IT:");
        
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
        
        // Verificar la configuración de correo
        $mailConfig = config('mail');
        $this->info("Configuración de correo actual:");
        $this->info("- Driver: " . $mailConfig['default']);
        $this->info("- Host: " . $mailConfig['mailers']['smtp']['host']);
        $this->info("- Puerto: " . $mailConfig['mailers']['smtp']['port']);
        $this->info("- Remitente: " . $mailConfig['from']['address']);
        
        // Enviar correos de prueba a cada usuario IT
        $this->info("\nEnviando correos de prueba a cada usuario IT...");
        
        foreach ($usuariosIT as $usuario) {
            if (empty($usuario->email)) {
                $this->warn("El usuario {$usuario->name} (ID: {$usuario->id}) no tiene email configurado.");
                continue;
            }
            
            $this->info("Enviando correo a {$usuario->name} <{$usuario->email}>...");
            
            try {
                Mail::to($usuario->email)->send(new NovaChecklistMail($checklist));
                $this->info("✓ Correo enviado correctamente a {$usuario->email}");
            } catch (\Exception $e) {
                $this->error("✗ Error al enviar correo a {$usuario->email}: " . $e->getMessage());
                Log::error("Error al enviar correo a {$usuario->email}: " . $e->getMessage());
            }
        }
        
        $this->info("\nProceso de verificación completado.");
        return 0;
    }
}
