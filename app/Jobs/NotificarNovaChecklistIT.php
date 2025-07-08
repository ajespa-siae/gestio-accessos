<?php

namespace App\Jobs;

use App\Models\ChecklistInstance;
use App\Models\User;
use App\Models\Notificacio;
use App\Mail\NovaChecklistMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificarNovaChecklistIT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistInstance $checklistInstance
    ) {}

    public function handle(): void
    {
        try {
            // Obtener usuarios con rol 'it' usando el sistema de roles de Shield
            $usuarisIT = User::whereHas('roles', function($query) {
                    $query->where('name', 'it');
                })
                ->where('actiu', true)
                ->get();
                
            // Fallback: si no hay usuarios con rol 'it' en Shield, intentar con el campo rol_principal
            if ($usuarisIT->isEmpty()) {
                Log::info('No se encontraron usuarios con rol "it" en Shield, intentando con rol_principal');
                $usuarisIT = User::where('rol_principal', 'it')
                               ->where('actiu', true)
                               ->get();
            }

            if ($usuarisIT->isEmpty()) {
                Log::warning('No hi ha usuaris IT actius per notificar');
                return;
            }

            $empleat = $this->checklistInstance->empleat;
            $tipus = $this->checklistInstance->getTipusTemplate();

            foreach ($usuarisIT as $usuariIT) {
                // Crear notificació in-app
                Notificacio::crear(
                    $usuariIT->id,
                    "Nova checklist {$tipus} assignada",
                    "Empleat: {$empleat->nom_complet} ({$empleat->departament->nom})",
                    'info',
                    "/admin/checklist-instances/{$this->checklistInstance->id}",
                    $empleat->identificador_unic
                );

                // Enviar email de notificación
                try {
                    Mail::to($usuariIT->email)->send(
                        new NovaChecklistMail($this->checklistInstance)
                    );
                    Log::info("Email enviado a {$usuariIT->email} para checklist {$tipus}");
                } catch (\Exception $emailError) {
                    Log::error("Error al enviar email a {$usuariIT->email}: " . $emailError->getMessage());
                    // Continuamos con el siguiente usuario aunque falle el email
                }
            }

            Log::info("Notificacions enviades a {$usuarisIT->count()} usuaris IT per checklist {$tipus}");

        } catch (\Exception $e) {
            Log::error("Error notificant usuaris IT: " . $e->getMessage());
            throw $e;
        }
    }
}