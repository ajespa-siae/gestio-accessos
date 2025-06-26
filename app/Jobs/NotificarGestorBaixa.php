<?php

namespace App\Jobs;

use App\Models\Empleat;
use App\Models\User;
use App\Models\Notificacio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarGestorBaixa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Empleat $empleat
    ) {}

    public function handle(): void
    {
        try {
            $gestor = $this->empleat->departament->gestor;
            
            if (!$gestor) {
                Log::warning("Departament {$this->empleat->departament->nom} no té gestor assignat per notificar baixa");
                return;
            }

            // Notificar gestor del departament
            Notificacio::crear(
                $gestor->id,
                'Empleat donat de baixa',
                "L'empleat {$this->empleat->nom_complet} del vostre departament ha estat donat de baixa.\nData: {$this->empleat->data_baixa->format('d/m/Y')}",
                'warning',
                "/admin/empleats/{$this->empleat->id}",
                $this->empleat->identificador_unic
            );

            // Notificar gestors addicionals del departament
            $gestorsAddicionals = $this->empleat->departament->gestorsAddicionals;
            
            foreach ($gestorsAddicionals as $gestorAddicional) {
                if ($gestorAddicional->id !== $gestor->id) {
                    Notificacio::crear(
                        $gestorAddicional->id,
                        'Empleat donat de baixa',
                        "L'empleat {$this->empleat->nom_complet} del departament {$this->empleat->departament->nom} ha estat donat de baixa.",
                        'warning',
                        "/admin/empleats/{$this->empleat->id}",
                        $this->empleat->identificador_unic
                    );
                }
            }

            Log::info("Gestors notificats de la baixa de {$this->empleat->identificador_unic}");

        } catch (\Exception $e) {
            Log::error("Error notificant gestor baixa: " . $e->getMessage());
            throw $e;
        }
    }
}