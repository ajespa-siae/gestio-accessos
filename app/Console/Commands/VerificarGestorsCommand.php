<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Departament;
use App\Models\SistemaValidador;
use App\Models\User;

class VerificarGestorsCommand extends Command
{
    protected $signature = 'gestors:verificar {--fix : Intentar arreglar problemes automàticament}';
    
    protected $description = 'Verifica la configuració de gestors múltiples i detecta problemes';

    public function handle()
    {
        $this->info('🔍 Verificant configuració de gestors...');
        $this->newLine();
        
        $problemes = [];
        
        // 1. Departaments sense gestors
        $deptsSenseGestors = Departament::senseGestors()->where('actiu', true)->get();
        if ($deptsSenseGestors->isNotEmpty()) {
            $problemes[] = [
                'tipus' => 'Departaments sense gestors',
                'detall' => $deptsSenseGestors->pluck('nom')->toArray(),
                'gravetat' => 'alta'
            ];
        }
        
        // 2. Departaments validadors sense gestors actius
        $deptsValidadorsSenseGestors = SistemaValidador::where('tipus_validador', 'gestor_departament')
            ->where('actiu', true)
            ->with(['departamentValidador'])
            ->get()
            ->filter(function ($sv) {
                return $sv->departamentValidador && 
                       $sv->departamentValidador->getGestorsActius()->isEmpty();
            });
            
        if ($deptsValidadorsSenseGestors->isNotEmpty()) {
            $problemes[] = [
                'tipus' => 'Departaments validadors sense gestors actius',
                'detall' => $deptsValidadorsSenseGestors->map(fn($sv) => 
                    "{$sv->departamentValidador->nom} (Sistema: {$sv->sistema->nom})"
                )->toArray(),
                'gravetat' => 'crítica'
            ];
        }
        
        // 3. Gestors inactius
        $gestorsInactius = User::where('rol_principal', 'gestor')
            ->where('actiu', false)
            ->whereHas('departamentsGestionats')
            ->with('departamentsGestionats')
            ->get();
            
        if ($gestorsInactius->isNotEmpty()) {
            $problemes[] = [
                'tipus' => 'Gestors inactius amb departaments assignats',
                'detall' => $gestorsInactius->map(fn($u) => 
                    "{$u->name} (Depts: {$u->departamentsGestionats->pluck('nom')->implode(', ')})"
                )->toArray(),
                'gravetat' => 'mitjana'
            ];
        }
        
        // 4. Departaments sense gestor principal
        $deptsSensePrincipal = Departament::whereHas('gestors')
            ->whereDoesntHave('gestorPrincipal')
            ->get();
            
        if ($deptsSensePrincipal->isNotEmpty()) {
            $problemes[] = [
                'tipus' => 'Departaments sense gestor principal',
                'detall' => $deptsSensePrincipal->pluck('nom')->toArray(),
                'gravetat' => 'baixa'
            ];
        }
        
        // Mostrar resultats
        if (empty($problemes)) {
            $this->info('✅ No s\'han detectat problemes en la configuració de gestors');
            return 0;
        }
        
        foreach ($problemes as $problema) {
            $icon = match($problema['gravetat']) {
                'crítica' => '🔴',
                'alta' => '🟠',
                'mitjana' => '🟡',
                'baixa' => '🟢'
            };
            
            $this->warn("{$icon} {$problema['tipus']}:");
            foreach ($problema['detall'] as $item) {
                $this->line("  • {$item}");
            }
            $this->newLine();
        }
        
        // Resum general
        $this->info('📊 Resum configuració gestors:');
        $totalDepts = Departament::count();
        $deptsAmbGestors = Departament::ambGestors()->count();
        $deptsMultiplesGestors = Departament::withCount('gestors')->having('gestors_count', '>', 1)->count();
        $totalGestors = User::gestors()->count();
        $gestorsAmbDepts = User::gestorsAmBDepartaments()->count();
        
        $this->table([
            'Mètrica', 'Valor'
        ], [
            ['Total departaments', $totalDepts],
            ['Departaments amb gestors', $deptsAmbGestors],
            ['Departaments múltiples gestors', $deptsMultiplesGestors],
            ['Total gestors', $totalGestors],
            ['Gestors amb departaments', $gestorsAmbDepts],
        ]);
        
        if ($this->option('fix')) {
            $this->intentarArreglarProblemes($problemes);
        } else {
            $this->info('💡 Per intentar arreglar automàticament: php artisan gestors:verificar --fix');
        }
        
        return count($problemes) > 0 ? 1 : 0;
    }
    
    private function intentarArreglarProblemes(array $problemes): void
    {
        $this->info('🔧 Intentant arreglar problemes automàticament...');
        
        foreach ($problemes as $problema) {
            if ($problema['tipus'] === 'Departaments sense gestor principal') {
                // Assignar primer gestor com a principal
                $departaments = Departament::whereHas('gestors')
                    ->whereDoesntHave('gestorPrincipal')
                    ->get();
                    
                foreach ($departaments as $dept) {
                    $primerGestor = $dept->gestors()->first();
                    if ($primerGestor) {
                        $dept->gestors()->updateExistingPivot($primerGestor->id, ['gestor_principal' => true]);
                        $dept->update(['gestor_id' => $primerGestor->id]);
                    }
                }
                
                $this->info("✅ Assignats gestors principals a {$departaments->count()} departaments");
            }
        }
    }
}