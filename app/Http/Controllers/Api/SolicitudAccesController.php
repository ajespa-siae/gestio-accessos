<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAcces;
use App\Models\SolicitudSistema;
use App\Models\SolicitudElementExtra;
use App\Models\SistemaElementExtra;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitudAccesController extends Controller
{
    /**
     * Crear nova sol·licitud amb suport híbrid (simple + elements extra)
     * MANTÉ TOTAL COMPATIBILITAT amb el sistema actual
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'empleat_destinatari_id' => 'required|exists:empleats,id',
            'justificacio' => 'required|string|max:1000',
            'sistemes_simples' => 'sometimes|array',
            'sistemes_simples.*.sistema_id' => 'required_with:sistemes_simples|exists:sistemes,id',
            'sistemes_simples.*.nivell_acces_id' => 'required_with:sistemes_simples|exists:nivell_acces_sistemes,id',
            'elements_extra' => 'sometimes|array',
            'elements_extra.*.element_extra_id' => 'required_with:elements_extra|exists:sistema_elements_extra,id',
            'elements_extra.*.opcio_seleccionada' => 'sometimes|string|max:100',
            'elements_extra.*.valor_text_lliure' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dades de validació incorrectes',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                // 1. Crear sol·licitud principal (CODI EXISTENT - NO CANVIAR)
                $solicitud = SolicitudAcces::create([
                    'usuari_solicitant_id' => auth()->id(),
                    'empleat_destinatari_id' => $request->empleat_destinatari_id,
                    'tipus_solicitud' => 'acces_sistema',
                    'justificacio' => $request->justificacio,
                    'estat' => 'pendent'
                ]);

                // 2. Processar sistemes simples (CODI EXISTENT - NO CANVIAR)
                foreach ($request->sistemes_simples ?? [] as $sistema) {
                    SolicitudSistema::create([
                        'solicitud_id' => $solicitud->id,
                        'sistema_id' => $sistema['sistema_id'],
                        'nivell_acces_id' => $sistema['nivell_acces_id'],
                        'usuari_creador_id' => auth()->id()
                    ]);
                }

                // 3. NOVA FUNCIONALITAT: Processar elements extra (només si existeixen)
                foreach ($request->elements_extra ?? [] as $element) {
                    // Validar que l'element extra existeix i està actiu
                    $elementExtra = SistemaElementExtra::actius()->findOrFail($element['element_extra_id']);
                    
                    // Validar opcions si és necessari
                    if (isset($element['opcio_seleccionada']) && !$elementExtra->opcioValida($element['opcio_seleccionada'])) {
                        throw new \Exception("Opció '{$element['opcio_seleccionada']}' no vàlida per l'element '{$elementExtra->nom}'");
                    }

                    SolicitudElementExtra::create([
                        'solicitud_id' => $solicitud->id,
                        'element_extra_id' => $element['element_extra_id'],
                        'opcio_seleccionada' => $element['opcio_seleccionada'] ?? null,
                        'valor_text_lliure' => $element['valor_text_lliure'] ?? null
                    ]);
                }

                $this->solicitud = $solicitud;
            });

            // Carregar relacions per la resposta
            $this->solicitud->load(['sistemesSolicitats.sistema', 'elementsExtra.elementExtra']);

            return response()->json([
                'success' => true,
                'message' => 'Sol·licitud creada correctament',
                'data' => [
                    'solicitud' => $this->solicitud,
                    'sistemes_simples' => $this->solicitud->sistemesSolicitats,
                    'elements_extra' => $this->solicitud->elementsExtra
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creant la sol·licitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir detalls complets d'una sol·licitud (simple + híbrid)
     */
    public function show(int $solicitudId): JsonResponse
    {
        try {
            $solicitud = SolicitudAcces::with([
                'sistemesSolicitats.sistema.nivellsAcces',
                'elementsExtra.elementExtra',
                'empleatDestinatari',
                'usuariSolicitant'
            ])->findOrFail($solicitudId);

            // Verificar permisos
            if (!auth()->user()->hasRole('admin') && $solicitud->usuari_solicitant_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tens permisos per veure aquesta sol·licitud'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'solicitud' => $solicitud,
                    'te_elements_extra' => $solicitud->elementsExtra->isNotEmpty(),
                    'resum' => [
                        'sistemes_simples' => $solicitud->sistemesSolicitats->count(),
                        'elements_extra' => $solicitud->elementsExtra->count(),
                        'tipus' => $solicitud->elementsExtra->isNotEmpty() ? 'híbrida' : 'simple'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sol·licitud no trobada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    private $solicitud; // Variable temporal per la transacció
}
