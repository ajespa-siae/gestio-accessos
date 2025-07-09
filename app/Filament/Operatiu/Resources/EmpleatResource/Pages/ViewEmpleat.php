<?php

namespace App\Filament\Operatiu\Resources\EmpleatResource\Pages;

use App\Filament\Operatiu\Resources\EmpleatResource;
use App\Models\SolicitudAcces;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Route;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\TextEntry;

class ViewEmpleat extends ViewRecord
{
    protected static string $resource = EmpleatResource::class;
    
    // Deshabilitar el botón de editar
    protected function getEditAction(): Actions\Action
    {
        return parent::getEditAction()->hidden();
    }
    
    // Añadir el botón de historial con modal
    protected function getActions(): array
    {
        return [
            Actions\Action::make('historial_solicituds')
                ->label('Historial de Sol·licituds')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->button()
                ->modalHeading('Cronologia de Sol·licituds')
                ->modalDescription(fn () => "Sol·licituds de l'empleat/da: {$this->record->nom} {$this->record->cognoms}")
                ->modalContent(function () {
                    $solicituds = SolicitudAcces::where('empleat_destinatari_id', $this->record->id)
                        ->with([
                            'usuariSolicitant', 
                            'sistemesSolicitats.sistema',
                            'validacions.sistema',
                            'validacions.validatPer'
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    if ($solicituds->isEmpty()) {
                        return new HtmlString('<div class="p-4 text-center text-gray-500">No hi ha sol·licituds per a aquest/a empleat/da.</div>');
                    }
                    
                    $timeline = '<div class="space-y-6 p-4 max-h-96 overflow-y-auto">';
                    
                    foreach ($solicituds as $solicitud) {
                        $estatClass = match($solicitud->estat) {
                            'pendent' => 'bg-yellow-100 text-yellow-800',
                            'validant' => 'bg-blue-100 text-blue-800',
                            'aprovada' => 'bg-green-100 text-green-800',
                            'rebutjada' => 'bg-red-100 text-red-800',
                            'finalitzada' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                        
                        $sistemes = $solicitud->sistemesSolicitats->map(function($sistema) {
                            return $sistema->sistema ? $sistema->sistema->nom : 'Sistema desconegut';
                        })->join(', ');
                        
                        $nomSolicitant = 'Desconegut';
                        if ($solicitud->usuariSolicitant) {
                            $nomSolicitant = $solicitud->usuariSolicitant->name;
                        }
                        
                        // Determinar la fecha más relevante según el estado
                        if ($solicitud->estat === 'aprovada' || $solicitud->estat === 'rebutjada') {
                            // Si hay validaciones, usar la fecha de la última validación
                            if ($solicitud->validacions && $solicitud->validacions->count() > 0) {
                                $ultimaValidacio = $solicitud->validacions
                                    ->whereIn('estat', ['aprovada', 'rebutjada'])
                                    ->sortByDesc('data_validacio')
                                    ->first();
                                
                                if ($ultimaValidacio && $ultimaValidacio->data_validacio) {
                                    $dataFormatada = $ultimaValidacio->data_validacio->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                                } else {
                                    $dataFormatada = $solicitud->updated_at->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                                }
                            } else {
                                $dataFormatada = $solicitud->updated_at->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                            }
                        } elseif ($solicitud->estat === 'finalitzada') {
                            // Para solicitudes finalizadas, usar la fecha de finalización
                            if ($solicitud->data_finalitzacio) {
                                $dataFormatada = $solicitud->data_finalitzacio->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                            } else {
                                $dataFormatada = $solicitud->updated_at->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                            }
                        } else {
                            // Para otros estados (pendiente, validando), usar la fecha de creación
                            $dataFormatada = $solicitud->created_at->setTimezone('Europe/Madrid')->format('d/m/Y H:i');
                        }
                        $estat = $solicitud->estat;
                        $identificador = $solicitud->identificador_unic;
                        $justificacio = $solicitud->justificacio;
                        
                        // Preparar la sección de validaciones
                        $validacionsHtml = '';
                        if ($solicitud->validacions && $solicitud->validacions->count() > 0) {
                            $validacionsHtml .= '<div class="mt-2 border-t border-gray-200 pt-2">';
                            $validacionsHtml .= '<div class="text-sm font-medium">Validacions:</div>';
                            $validacionsHtml .= '<ul class="mt-1 space-y-1 text-xs">';
                            
                            foreach ($solicitud->validacions as $validacio) {
                                $validacioClass = match($validacio->estat) {
                                    'pendent' => 'text-yellow-600',
                                    'aprovada' => 'text-green-600',
                                    'rebutjada' => 'text-red-600',
                                    default => 'text-gray-600',
                                };
                                
                                $nomValidador = 'Pendent';
                                $dataValidacio = '';
                                
                                if ($validacio->estat !== 'pendent') {
                                    $nomValidador = $validacio->validatPer ? $validacio->validatPer->name : 'Desconegut/da';
                                    $dataValidacio = $validacio->data_validacio ? ' - ' . $validacio->data_validacio->setTimezone('Europe/Madrid')->format('d/m/Y H:i') : '';
                                }
                                
                                $nomSistema = $validacio->sistema ? $validacio->sistema->nom : 'Sistema desconegut';
                                
                                $validacionsHtml .= '<li class="flex items-center justify-between">';
                                $validacionsHtml .= '<span>' . $nomSistema . '</span>';
                                $validacionsHtml .= '<span class="' . $validacioClass . '">';
                                $validacionsHtml .= ucfirst($validacio->estat);
                                
                                if ($validacio->estat !== 'pendent') {
                                    $validacionsHtml .= ' per ' . $nomValidador . $dataValidacio;
                                }
                                
                                $validacionsHtml .= '</span></li>';
                                
                                if (!empty($validacio->observacions)) {
                                    $validacionsHtml .= '<li class="text-gray-500 italic pl-2">"' . $validacio->observacions . '"</li>';
                                }
                            }
                            
                            $validacionsHtml .= '</ul></div>';
                        }
                        
                        $timeline .= <<<HTML
                        <div class="relative flex items-start gap-4">
                            <div class="absolute left-0 top-0 flex h-6 w-6 items-center justify-center rounded-full bg-primary-500 text-white">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-8 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{$dataFormatada}</span>
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {$estatClass}">{$estat}</span>
                                </div>
                                <div class="text-sm text-gray-500">ID: {$identificador}</div>
                                <div class="text-sm">Sol·licitant: {$nomSolicitant}</div>
                                <div class="text-sm">Sistemes: {$sistemes}</div>
                                <div class="text-sm">Justificació: {$justificacio}</div>
                                {$validacionsHtml}
                            </div>
                        </div>
                        HTML;
                    }
                    
                    $timeline .= '</div>';
                    
                    return new HtmlString($timeline);
                })
                ->modalWidth('md')
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }
}
