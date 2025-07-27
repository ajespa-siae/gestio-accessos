<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sistema;
use App\Models\NivellAccesSistema;
use App\Models\SistemaElementExtra;

class EacatSistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el sistema Eacat
        $eacat = Sistema::firstOrCreate(
            ['nom' => 'EACAT'],
            [
                'descripcio' => 'Extranet de l\'Administració Pública de Catalunya',
                'actiu' => true,
                'rol_gestor_defecte' => 'it'
            ]
        );

        // Crear nivells d'accés per Eacat
        $nivells = [
            [
                'nom' => 'Accés',
                'descripcio' => 'Accés estàndard a Eacat',
                'ordre' => 1
            ]
        ];

        foreach ($nivells as $nivellData) {
            NivellAccesSistema::firstOrCreate(
                [
                    'sistema_id' => $eacat->id,
                    'nom' => $nivellData['nom']
                ],
                [
                    'descripcio' => $nivellData['descripcio'],
                    'ordre' => $nivellData['ordre'],
                    'actiu' => true
                ]
            );
        }

        // Crear elements extra per Eacat
        $elementsExtra = [
            [
                'nom' => 'Via Oberta',
                'descripcio' => 'Mòduls de Via Oberta disponibles',
                'tipus' => 'modul',
                'opcions_disponibles' => [
                    'ACTIC – Acreditacions TIC',
                    'AEAT - Dades tributàries',
                    'AEAT – Impost activitats econòmiques',
                    'AEAT – Renda',
                    'Ajuntaments - Dades de residència padró i IDESCAT',
                    'ATC - Deute amb l\'Administració de la Generalitat',
                    'BSF – Títol de Família Monoparental',
                    'CATSALUT - Registre Central d\'Assegurats',
                    'CCAA - Grau de discapacitat',
                    'CCAA - TFN',
                    'CGN- Poders notarials',
                    'Comunicació Domicili',
                    'CORPME - Registre de la Propietat',
                    'CORPME - Registre mercantil',
                    'DCOC - Documents visats',
                    'DG Cadastre - Cadastre',
                    'DGP - Identitat',
                    'DGP - Residència legal',
                    'DGP - Residència legal històric',
                    'DGT - Deutors de l\'impost municipal de vehicles',
                    'DGT - Registre de vehicles',
                    'GC - Beques',
                    'GC - Discapacitat',
                    'IDESCAT – Padró històric',
                    'IGAE - Intervención General Administración Estado',
                    'IMSERSO – Nivell i grau de dependència',
                    'INE - Residència',
                    'INSS – Prestacions socials',
                    'JUS - Registre d\'Entitats Jurídiques',
                    'M Educació - Titulacions',
                    'NOTARIS - Documents notarials',
                    'RCP - Antecedents penals',
                    'Registre Civil',
                    'Registre parelles estables',
                    'Renda Garantida Ciutadana',
                    'Responsable d\'interoperabilitat',
                    'SCT - Adreça electrònica vial',
                    'SCT - Denúncies',
                    'SEPE - Prestacions per desocupació',
                    'SOC - Demandants d\'ocupació',
                    'TGSS - Deutes amb la SS i situació de cotització',
                    'TGSS - Vida laboral',
                    'Títol de família nombrosa'
                ],
                'permet_text_lliure' => false,
                'ordre' => 1
            ]
        ];

        foreach ($elementsExtra as $elementData) {
            SistemaElementExtra::firstOrCreate(
                [
                    'sistema_id' => $eacat->id,
                    'nom' => $elementData['nom']
                ],
                [
                    'descripcio' => $elementData['descripcio'],
                    'tipus' => $elementData['tipus'],
                    'opcions_disponibles' => $elementData['opcions_disponibles'],
                    'permet_text_lliure' => $elementData['permet_text_lliure'],
                    'ordre' => $elementData['ordre'],
                    'actiu' => true
                ]
            );
        }

        $this->command->info('Sistema Eacat creat amb èxit amb ' . count($nivells) . ' nivell d\'accés i ' . count($elementsExtra) . ' element extra (Via Oberta amb ' . count($elementsExtra[0]['opcions_disponibles']) . ' mòduls).');
    }
}
