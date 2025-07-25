<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Sistema;
use App\Models\SistemaElementExtra;
use App\Models\SolicitudElementExtra;

class SistemaElementExtraTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function element_extra_pot_validar_opcions()
    {
        $sistema = Sistema::create([
            'nom' => 'Test Sistema',
            'descripcio' => 'Sistema de prova',
            'actiu' => true
        ]);

        $element = SistemaElementExtra::create([
            'sistema_id' => $sistema->id,
            'nom' => 'Element Test',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced', 'premium'],
            'permet_text_lliure' => false,
            'ordre' => 1,
            'actiu' => true
        ]);

        $this->assertTrue($element->teOpcions());
        $this->assertTrue($element->opcioValida('basic'));
        $this->assertTrue($element->opcioValida('advanced'));
        $this->assertTrue($element->opcioValida('premium'));
        $this->assertFalse($element->opcioValida('invalid'));
    }

    /** @test */
    public function element_extra_sense_opcions_no_valida()
    {
        $sistema = Sistema::create([
            'nom' => 'Test Sistema',
            'descripcio' => 'Sistema de prova',
            'actiu' => true
        ]);

        $element = SistemaElementExtra::create([
            'sistema_id' => $sistema->id,
            'nom' => 'Element Sense Opcions',
            'tipus' => 'recurs',
            'opcions_disponibles' => null,
            'permet_text_lliure' => true,
            'ordre' => 1,
            'actiu' => true
        ]);

        $this->assertFalse($element->teOpcions());
        $this->assertFalse($element->opcioValida('qualsevol'));
        $this->assertTrue($element->acceptaTextLliure());
    }

    /** @test */
    public function sistema_detecta_elements_complexos()
    {
        $sistema = Sistema::create([
            'nom' => 'Test Sistema',
            'descripcio' => 'Sistema de prova',
            'actiu' => true
        ]);

        // Inicialment no té elements complexos
        $this->assertFalse($sistema->teElementsComplexos());

        // Afegir element extra
        SistemaElementExtra::create([
            'sistema_id' => $sistema->id,
            'nom' => 'Element Complex',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic'],
            'ordre' => 1,
            'actiu' => true
        ]);

        // Ara sí té elements complexos
        $this->assertTrue($sistema->fresh()->teElementsComplexos());
    }

    /** @test */
    public function configuracio_completa_retorna_estructura_correcta()
    {
        $sistema = Sistema::create([
            'nom' => 'Test Sistema',
            'descripcio' => 'Sistema de prova',
            'actiu' => true
        ]);

        SistemaElementExtra::create([
            'sistema_id' => $sistema->id,
            'nom' => 'Element Test',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'],
            'ordre' => 1,
            'actiu' => true
        ]);

        $configuracio = $sistema->getConfiguracioCompleta();

        $this->assertArrayHasKey('sistema', $configuracio);
        $this->assertArrayHasKey('te_elements_complexos', $configuracio);
        $this->assertArrayHasKey('nivells_simples', $configuracio);
        $this->assertArrayHasKey('elements_extra', $configuracio);
        $this->assertArrayHasKey('tipus_formulari', $configuracio);

        $this->assertTrue($configuracio['te_elements_complexos']);
        $this->assertEquals('mixt', $configuracio['tipus_formulari']);
    }

    /** @test */
    public function solicitud_element_extra_valida_correctament()
    {
        $sistema = Sistema::create([
            'nom' => 'Test Sistema',
            'descripcio' => 'Sistema de prova',
            'actiu' => true
        ]);

        $element = SistemaElementExtra::create([
            'sistema_id' => $sistema->id,
            'nom' => 'Element Validació',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'],
            'permet_text_lliure' => true,
            'ordre' => 1,
            'actiu' => true
        ]);

        // Crear sol·licitud element extra amb opció vàlida
        $solicitudElement = new SolicitudElementExtra([
            'element_extra_id' => $element->id,
            'opcio_seleccionada' => 'basic',
            'valor_text_lliure' => null
        ]);
        $solicitudElement->elementExtra = $element;

        $this->assertTrue($solicitudElement->validarOpcio());

        // Provar amb opció invàlida
        $solicitudElement->opcio_seleccionada = 'invalid';
        $this->assertFalse($solicitudElement->validarOpcio());

        // Provar amb text lliure
        $solicitudElement->opcio_seleccionada = null;
        $solicitudElement->valor_text_lliure = 'Configuració personalitzada';
        $this->assertTrue($solicitudElement->validarOpcio());
    }
}
