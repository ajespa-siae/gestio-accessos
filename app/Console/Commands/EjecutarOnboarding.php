<?php

namespace App\Console\Commands;

use App\Jobs\CrearChecklistOnboarding;
use App\Models\Empleat;
use Illuminate\Console\Command;

class EjecutarOnboarding extends Command
{
    protected $signature = 'app:ejecutar-onboarding {empleat_id}';
    protected $description = 'Ejecuta el proceso de onboarding para un empleado específico';

    public function handle()
    {
        $empleatId = $this->argument('empleat_id');
        $empleat = Empleat::find($empleatId);

        if (!$empleat) {
            $this->error("No se encontró el empleado con ID {$empleatId}");
            return 1;
        }

        $this->info("Ejecutando job de onboarding para: {$empleat->nom_complet}");
        
        try {
            CrearChecklistOnboarding::dispatch($empleat);
            $this->info('Job de onboarding enviado correctamente');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error al ejecutar el job de onboarding: {$e->getMessage()}");
            return 1;
        }
    }
}
