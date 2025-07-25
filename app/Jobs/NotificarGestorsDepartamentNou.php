<?php

namespace App\Jobs;

use App\Models\ProcessMobilitat;
use App\Models\User;
use App\Models\Notificacio;
use App\Mail\MobilitatGestorDeptNou;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificarGestorsDepartamentNou implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ProcessMobilitat $processMobilitat
    ) {}

    public function handle(): void
    {
        try {
            // Trobar gestors del departament nou
            $gestors = User::query()
                ->where('actiu', true)
                ->where(function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->where('name', 'gestor');
                    })
                    ->orWhere('rol_principal', 'gestor');
                })
                ->whereHas('empleat', function ($query) {
                    $query->where('departament_id', $this->processMobilitat->departament_nou_id);
                })
                ->get();

            foreach ($gestors as $gestor) {
                // Crear notificació
                Notificacio::create([
                    'user_id' => $gestor->id,
                    'titol' => 'Definir accessos per mobilitat',
                    'missatge' => "El procés de mobilitat {$this->processMobilitat->identificador_unic} necessita que defineixes els nous accessos",
                    'tipus' => 'info',
                    'metadata' => [
                        'process_mobilitat_id' => $this->processMobilitat->id,
                        'empleat' => $this->processMobilitat->empleat->nom_complet,
                        'departament_anterior' => $this->processMobilitat->departamentActual->nom,
                        'tipus_notificacio' => 'mobilitat_dept_nou'
                    ]
                ]);

                // Enviar email
                Mail::to($gestor->email)
                    ->send(new MobilitatGestorDeptNou($this->processMobilitat));

                Log::info("Notificació de mobilitat enviada al gestor {$gestor->name} del departament nou");
            }

        } catch (\Exception $e) {
            Log::error("Error notificant gestors departament nou: {$e->getMessage()}");
            throw $e;
        }
    }
}
