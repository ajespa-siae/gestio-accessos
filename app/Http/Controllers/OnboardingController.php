<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleat;
use App\Models\ChecklistInstance;
use App\Models\ChecklistTask;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateTasca;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * Mostrar el dashboard amb widgets d'onboarding
     */
    public function dashboard()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();
        
        // Empleats amb onboarding pendent o en procés
        $totalOnboardingActius = Empleat::with('checklists')
            ->whereHas('checklists', function($q) {
                $q->where('estat', '!=', 'completada')
                  ->whereHas('template', function($subq) {
                      $subq->where('tipus', 'onboarding');
                  });
            })->count();
        
        // Onboardings completats
        $totalOnboardingCompletats = ChecklistInstance::where('estat', 'completada')
            ->whereHas('template', function($q) {
                $q->where('tipus', 'onboarding');
            })->count();
            
        // Onboardings amb problemes (més de 7 dies i no completats)
        $totalOnboardingProblemes = ChecklistInstance::where('estat', '!=', 'completada')
            ->whereHas('template', function($q) {
                $q->where('tipus', 'onboarding');
            })
            ->where('created_at', '<', now()->subDays(7))
            ->count();
        
        // Temps mitjà d'onboarding (en dies)
        $tempsMitjaOnboarding = 14; // Valor per defecte
        $completats = ChecklistInstance::where('estat', 'completada')
            ->whereHas('template', function($q) {
                $q->where('tipus', 'onboarding');
            })
            ->whereNotNull('data_finalitzacio')
            ->get();
            
        if ($completats->count() > 0) {
            $tempsMitja = $completats->avg(function($checklist) {
                return $checklist->created_at->diffInDays($checklist->data_finalitzacio);
            });
            $tempsMitjaOnboarding = round($tempsMitja);
        }
        
        // Properes incorporacions (empleats amb data_alta futura)
        $properesIncorporacions = Empleat::with('departament')
            ->where('data_alta', '>', now())
            ->orderBy('data_alta')
            ->take(5)
            ->get();
        
        // Tasques pendents
        $tasquesPendents = ChecklistTask::with(['checklistInstance.empleat', 'checklistInstance.template'])
            ->where('completada', false)
            ->whereHas('checklistInstance', function($q) {
                $q->whereHas('template', function($subq) {
                    $subq->where('tipus', 'onboarding');
                });
            })
            ->orderBy('data_limit')
            ->take(10)
            ->get();
        
        // Plantilles de checklist
        $checklistTemplates = ChecklistTemplate::withCount('tasquesTemplate as total_tasques')
            ->where('tipus', 'onboarding')
            ->orderBy('nom')
            ->take(5)
            ->get();
        
        return view('dashboard.onboarding', compact(
            'user',
            'totalOnboardingActius',
            'totalOnboardingCompletats',
            'totalOnboardingProblemes',
            'tempsMitjaOnboarding',
            'properesIncorporacions',
            'tasquesPendents',
            'checklistTemplates'
        ));
    }
    
    // La gestió de plantilles de checklist s'ha eliminat d'aquesta interfície
    // i ara es fa exclusivament des del panell Filament per administradors
}
