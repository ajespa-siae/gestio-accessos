<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sistema;
use App\Models\SistemaElementExtra;

class SistemaElementsExtraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenir sistemes existents
        $gestorExpedients = Sistema::where('nom', 'Gestor d\'Expedients')->first();
        $gestioComptable = Sistema::where('nom', 'Gestió Comptable')->first();
        
        if (!$gestorExpedients || !$gestioComptable) {
            $this->command->warn('No s\'han trobat els sistemes base. Executeu primer els seeders principals.');
            return;
        }

        // ================================
        // EXEMPLE 1: Sistema amb subsistemes i opcions
        // ================================
        SistemaElementExtra::updateOrCreate(
            [
                'sistema_id' => $gestorExpedients->id,
                'nom' => 'Subsistema Expedients'
            ],
            [
                'descripcio' => 'Accés a diferents subsistemes d\'expedients',
                'tipus' => 'modul',
                'opcions_disponibles' => ['basic', 'advanced', 'advanced_download'],
                'permet_text_lliure' => false,
                'ordre' => 1,
                'actiu' => true
            ]
        );

        SistemaElementExtra::updateOrCreate(
            [
                'sistema_id' => $gestorExpedients->id,
                'nom' => 'Permisos Especials'
            ],
            [
                'descripcio' => 'Permisos addicionals per funcionalitats específiques',
                'tipus' => 'funcionalitat',
                'opcions_disponibles' => ['lectura', 'escriptura', 'administracio'],
                'permet_text_lliure' => true,
                'ordre' => 2,
                'actiu' => true
            ]
        );

        // ================================
        // EXEMPLE 2: Sistema amb text lliure per partides
        // ================================
        SistemaElementExtra::updateOrCreate(
            [
                'sistema_id' => $gestioComptable->id,
                'nom' => 'Partides Pressupostàries'
            ],
            [
                'descripcio' => 'Especifica les partides pressupostàries necessàries',
                'tipus' => 'recurs',
                'opcions_disponibles' => null,
                'permet_text_lliure' => true,
                'ordre' => 1,
                'actiu' => true
            ]
        );

        SistemaElementExtra::updateOrCreate(
            [
                'sistema_id' => $gestioComptable->id,
                'nom' => 'Nivell d\'Autorització'
            ],
            [
                'descripcio' => 'Nivell d\'autorització per operacions comptables',
                'tipus' => 'nivell',
                'opcions_disponibles' => ['basic', 'intermig', 'avancat'],
                'permet_text_lliure' => false,
                'ordre' => 2,
                'actiu' => true
            ]
        );

        // ================================
        // EXEMPLE 3: Sistema mixt (nivells simples + elements extra)
        // ================================
        // Nota: El Gestor d'Expedients ja té nivells d'accés simples a la taula nivell_acces_sistemes
        // Ara també té elements extra, convertint-lo en un sistema híbrid
        
        $this->command->info('Seeders d\'elements extra creats correctament:');
        $this->command->info('- Gestor d\'Expedients: Sistema híbrid (nivells simples + elements extra)');
        $this->command->info('- Gestió Comptable: Sistema híbrid amb text lliure i opcions');
        $this->command->info('');
        $this->command->info('Ara podeu provar la funcionalitat híbrida!');
    }
}
