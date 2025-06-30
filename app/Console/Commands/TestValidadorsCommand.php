<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sistema;
use App\Models\Departament;
use App\Models\User;

class TestValidadorsCommand extends Command
{
    protected $signature = 'validadors:test {sistema_id} {departament_id}';
    
    protected $description = 'Testa els validadors d\'un sistema per un departament específic';

    public function handle()
    {
        $sistemaId = $this->argument('sistema_id');
        $departamentId = $this->argument('departament_id');
        
        $sistema = Sistema::find($sistemaId);
        $departament = Departament::find($departamentId);
        
        if (!$sistema || !$departament) {
            $this->error('Sistema o departament no trobat');
            return 1;
        }
        
        $this->info("Testant validadors per:");
        $this->info("Sistema: {$sistema->nom}");
        $this->info("Departament: {$departament->nom}");
        $this->info("Gestor departament: " . ($departament->gestor?->name ?? 'Cap'));
        $this->newLine();
        
        $validadors = $sistema->getValidadorsPerDepartament($departamentId);
        
        if ($validadors->isEmpty()) {
            $this->warn('❌ No hi ha validadors configurats per aquest sistema');
            return 0;
        }
        
        $this->info("✅ Validadors trobats:");
        
        foreach ($validadors as $validador) {
            $this->line("- {$validador->name} ({$validador->email})");
        }
        
        $this->newLine();
        $this->info("Configuració detallada:");
        
        $configValidadors = $sistema->sistemaValidadors;
        
        foreach ($configValidadors as $config) {
            $tipus = $config->getTipusFormatted();
            $nom = $config->getNomValidador($departamentId);
            $estat = $config->actiu ? '✅' : '❌';
            $obligatori = $config->requerit ? '⚠️ Obligatori' : '➡️ Opcional';
            
            $this->line("{$estat} {$tipus}: {$nom} ({$obligatori})");
        }
        
        return 0;
    }
}