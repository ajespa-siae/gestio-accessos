<?php

namespace App\Jobs;

use App\Models\ProcessMobilitat;
use App\Models\User;
use App\Models\Notificacio;
use App\Mail\MobilitatGestorDeptActual;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarGestorsDepartamentActual implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ProcessMobilitat $processMobilitat
    ) {}

    public function handle(): void
    {
        try {
            // Trobar gestors del departament actual
            $gestors = User::query()
                ->where('actiu', true)
                ->where(function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->where('name', 'gestor');
                    })
                    ->orWhere('rol_principal', 'gestor');
                })
                ->whereHas('empleat', function ($query) {
                    $query->where('departament_id', $this->processMobilitat->departament_actual_id);
                })
                ->get();

            foreach ($gestors as $gestor) {
                // Crear notificació
                Notificacio::create([
                    'user_id' => $gestor->id,
                    'titol' => 'Procés de mobilitat pendent',
                    'missatge' => "El procés de mobilitat {$this->processMobilitat->identificador_unic} necessita la teva revisió",
                    'tipus' => 'info',
                    'metadata' => [
                        'process_mobilitat_id' => $this->processMobilitat->id,
                        'empleat' => $this->processMobilitat->empleat->nom_complet,
                        'departament_nou' => $this->processMobilitat->departamentNou->nom,
                        'tipus_notificacio' => 'mobilitat_dept_actual'
                    ]
                ]);

                // Enviar email
                Mail::to($gestor->email)
                    ->send(new MobilitatGestorDeptActual($this->processMobilitat));

                Log::info("Notificació de mobilitat enviada al gestor {$gestor->name} del departament actual");
            }

        } catch (\Exception $e) {
            Log::error("Error notificant gestors departament actual: {$e->getMessage()}");
            throw $e;
        }
    }
}
