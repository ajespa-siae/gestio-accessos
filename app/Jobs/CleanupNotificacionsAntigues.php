<?php

namespace App\Jobs;

use App\Models\Notificacio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupNotificacionsAntigues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $diesAntiguitat = 90
    ) {}

    public function handle(): void
    {
        try {
            // Eliminar notificacions llegides més antigues que X dies
            $eliminades = Notificacio::where('llegida', true)
                ->where('data_llegida', '<', now()->subDays($this->diesAntiguitat))
                ->delete();

            // Eliminar notificacions no llegides més antigues que X*2 dies
            $eliminadesNoLlegides = Notificacio::where('llegida', false)
                ->where('created_at', '<', now()->subDays($this->diesAntiguitat * 2))
                ->delete();

            Log::info("Cleanup notificacions: {$eliminades} llegides, {$eliminadesNoLlegides} no llegides eliminades");

        } catch (\Exception $e) {
            Log::error("Error en cleanup notificacions: " . $e->getMessage());
            throw $e;
        }
    }
}