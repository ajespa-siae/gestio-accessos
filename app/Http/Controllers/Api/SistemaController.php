<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sistema;
use Illuminate\Http\JsonResponse;

class SistemaController extends Controller
{
    /**
     * Obtenir detalls complets d'un sistema (simple + híbrid)
     */
    public function getSistemaDetails(int $sistemaId): JsonResponse
    {
        try {
            $sistema = Sistema::with(['nivellsAcces', 'elementsExtra'])->findOrFail($sistemaId);
            
            $configuracio = $sistema->getConfiguracioCompleta();
            
            return response()->json([
                'success' => true,
                'data' => $configuracio
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sistema no trobat',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Llistar tots els sistemes amb informació de tipus
     */
    public function index(): JsonResponse
    {
        try {
            $sistemes = Sistema::actius()
                ->with(['nivellsAcces', 'elementsExtra'])
                ->get()
                ->map(function ($sistema) {
                    return [
                        'id' => $sistema->id,
                        'nom' => $sistema->nom,
                        'descripcio' => $sistema->descripcio,
                        'te_elements_complexos' => $sistema->teElementsComplexos(),
                        'tipus_formulari' => $sistema->teElementsComplexos() ? 'mixt' : 'simple',
                        'nivells_count' => $sistema->nivellsAcces->count(),
                        'elements_extra_count' => $sistema->elementsExtra->count()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sistemes
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obtenint sistemes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
