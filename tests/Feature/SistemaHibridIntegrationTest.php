<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Sistema;
use App\Models\SistemaElementExtra;
use App\Models\SolicitudAcces;
use App\Models\SolicitudElementExtra;
use App\Models\User;
use App\Models\Empleat;
use Illuminate\Support\Facades\Auth;

class SistemaHibridIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $empleat;
    protected $sistema;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuari de prova
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'actiu' => true
        ]);
        
        // Crear empleat de prova
        $this->empleat = Empleat::create([
            'nom' => 'Test',
            'cognoms' => 'Employee',
            'email' => 'empleat@example.com',
            'departament_id' => 1, // Assumim que existeix
            'actiu' => true
        ]);
        
        // Crear sistema de prova
        $this->sistema = Sistema::create([
            'nom' => 'Sistema Test',
            'descripcio' => 'Sistema per testing',
            'actiu' => true
        ]);
    }

    /** @test */
    public function sistema_sense_elements_extra_es_simple()
    {
        $this->assertFalse($this->sistema->teElementsComplexos());
        
        $configuracio = $this->sistema->getConfiguracioCompleta();
        $this->assertEquals('simple', $configuracio['tipus_formulari']);
    }

    /** @test */
    public function sistema_amb_elements_extra_es_hibrid()
    {
        // Afegir element extra
        SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Element Test',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'],
            'ordre' => 1,
            'actiu' => true
        ]);

        $this->assertTrue($this->sistema->teElementsComplexos());
        
        $configuracio = $this->sistema->getConfiguracioCompleta();
        $this->assertEquals('mixt', $configuracio['tipus_formulari']);
    }

    /** @test */
    public function api_retorna_detalls_sistema_correctament()
    {
        Auth::login($this->user);
        
        // Afegir elements extra
        SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Subsistema',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'],
            'ordre' => 1,
            'actiu' => true
        ]);

        $response = $this->getJson("/api/sistemes/{$this->sistema->id}/details");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'sistema',
                        'te_elements_complexos',
                        'nivells_simples',
                        'elements_extra',
                        'tipus_formulari'
                    ]
                ]);
                
        $this->assertTrue($response->json('data.te_elements_complexos'));
        $this->assertEquals('mixt', $response->json('data.tipus_formulari'));
    }

    /** @test */
    public function pot_crear_solicitud_simple_tradicional()
    {
        Auth::login($this->user);
        
        $requestData = [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud de prova simple',
            'sistemes_simples' => [
                [
                    'sistema_id' => $this->sistema->id,
                    'nivell_acces_id' => 1
                ]
            ]
        ];

        $response = $this->postJson('/api/solicituds', $requestData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'solicitud',
                        'sistemes_simples',
                        'elements_extra'
                    ]
                ]);
                
        $this->assertDatabaseHas('solicituds_acces', [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud de prova simple'
        ]);
        
        $this->assertDatabaseHas('solicitud_sistemes', [
            'sistema_id' => $this->sistema->id
        ]);
    }

    /** @test */
    public function pot_crear_solicitud_amb_elements_extra()
    {
        Auth::login($this->user);
        
        // Crear element extra
        $elementExtra = SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Element Prova',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'],
            'permet_text_lliure' => true,
            'ordre' => 1,
            'actiu' => true
        ]);

        $requestData = [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud híbrida de prova',
            'elements_extra' => [
                [
                    'element_extra_id' => $elementExtra->id,
                    'opcio_seleccionada' => 'advanced',
                    'valor_text_lliure' => 'Configuració específica necessària'
                ]
            ]
        ];

        $response = $this->postJson('/api/solicituds', $requestData);
        
        $response->assertStatus(201);
                
        $this->assertDatabaseHas('solicituds_acces', [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud híbrida de prova'
        ]);
        
        $this->assertDatabaseHas('solicitud_elements_extra', [
            'element_extra_id' => $elementExtra->id,
            'opcio_seleccionada' => 'advanced',
            'valor_text_lliure' => 'Configuració específica necessària'
        ]);
    }

    /** @test */
    public function valida_opcions_elements_extra()
    {
        Auth::login($this->user);
        
        // Crear element amb opcions limitades
        $elementExtra = SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Element Restringit',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced'], // Només aquestes opcions
            'permet_text_lliure' => false,
            'ordre' => 1,
            'actiu' => true
        ]);

        $requestData = [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud amb opció invàlida',
            'elements_extra' => [
                [
                    'element_extra_id' => $elementExtra->id,
                    'opcio_seleccionada' => 'invalid_option' // Opció no vàlida
                ]
            ]
        ];

        $response = $this->postJson('/api/solicituds', $requestData);
        
        $response->assertStatus(500); // Error per opció invàlida
    }

    /** @test */
    public function solicitud_hibrida_manté_compatibilitat_amb_sistema_actual()
    {
        Auth::login($this->user);
        
        // Crear sol·licitud mixta (simple + híbrida)
        $elementExtra = SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Element Mixt',
            'tipus' => 'funcionalitat',
            'opcions_disponibles' => ['lectura', 'escriptura'],
            'ordre' => 1,
            'actiu' => true
        ]);

        $requestData = [
            'empleat_destinatari_id' => $this->empleat->id,
            'justificacio' => 'Sol·licitud mixta',
            'sistemes_simples' => [
                [
                    'sistema_id' => $this->sistema->id,
                    'nivell_acces_id' => 1
                ]
            ],
            'elements_extra' => [
                [
                    'element_extra_id' => $elementExtra->id,
                    'opcio_seleccionada' => 'escriptura'
                ]
            ]
        ];

        $response = $this->postJson('/api/solicituds', $requestData);
        
        $response->assertStatus(201);
        
        // Verificar que ambdues parts s'han creat
        $solicitud = SolicitudAcces::latest()->first();
        
        $this->assertCount(1, $solicitud->sistemesSolicitats);
        $this->assertCount(1, $solicitud->elementsExtra);
        $this->assertTrue($solicitud->teElementsExtra());
        
        $resum = $solicitud->getResumComplet();
        $this->assertEquals('híbrida', $resum['tipus']);
        $this->assertEquals(2, $resum['total_elements']);
    }

    /** @test */
    public function sistema_element_extra_valida_opcions_correctament()
    {
        $element = SistemaElementExtra::create([
            'sistema_id' => $this->sistema->id,
            'nom' => 'Element Validació',
            'tipus' => 'modul',
            'opcions_disponibles' => ['basic', 'advanced', 'premium'],
            'ordre' => 1,
            'actiu' => true
        ]);

        $this->assertTrue($element->teOpcions());
        $this->assertTrue($element->opcioValida('basic'));
        $this->assertTrue($element->opcioValida('advanced'));
        $this->assertFalse($element->opcioValida('invalid'));
        $this->assertEquals(['basic', 'advanced', 'premium'], $element->getOpcionsPredeterminades());
    }
}
