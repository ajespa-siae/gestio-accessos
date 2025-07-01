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
        
        // Tasques pendents filtrades per rol
        $tasquesPendents = ChecklistTask::with(['checklistInstance.empleat', 'checklistInstance.template', 'usuariAssignat'])
            ->where('completada', false)
            ->whereHas('checklistInstance', function($q) {
                $q->whereHas('template', function($subq) {
                    $subq->where('tipus', 'onboarding');
                });
            });
        
        // Filtrar per rol (excepte admin que veu totes)
        if ($user->rol_principal !== 'admin') {
            // Filtrar per rol assignat (si existeix) o per usuari assignat (compatibilitat)
            $tasquesPendents = $tasquesPendents->where(function($query) use ($user) {
                // Tasques amb rol_assignat que correspon al rol de l'usuari
                $query->where('rol_assignat', $user->rol_principal);
                
                // O tasques assignades directament a l'usuari (compatibilitat)
                $query->orWhere('usuari_assignat_id', $user->id);
            });    
        }
        
        // Obtenir les tasques ordenades per data límit
        $tasquesPendents = $tasquesPendents->orderBy('data_limit')
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
    
    /**
     * Mostrar llistat de tasques pendents per l'usuari
     */
    public function tasques()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();
        
        // Tasques pendents filtrades per rol
        $tasques = ChecklistTask::with(['checklistInstance.empleat', 'checklistInstance.template'])
            ->where('completada', false)
            ->whereHas('checklistInstance', function($q) {
                $q->whereHas('template', function($subq) {
                    // Mostrar tanto onboarding como offboarding
                });
            });
        
        // Filtrar per rol (excepte admin que veu totes)
        if ($user->rol_principal !== 'admin') {
            $tasques = $tasques->where(function($query) use ($user) {
                // Tasques amb rol_assignat que correspon al rol de l'usuari
                $query->where('rol_assignat', $user->rol_principal);
                
                // O tasques assignades directament a l'usuari (compatibilitat)
                $query->orWhere('usuari_assignat_id', $user->id);
            });    
        }
        
        // Obtenir les tasques ordenades per data límit
        $tasques = $tasques->orderBy('data_limit')
            ->paginate(15);
        
        return view('tasques.index', compact('tasques', 'user'));
    }
    
    /**
     * Mostrar detall d'una tasca
     */
    public function mostrarTasca(ChecklistTask $tasca)
    {
        $user = Auth::user();
        
        // Verificar que l'usuari té permís per veure aquesta tasca
        if ($user->rol_principal !== 'admin' && 
            $tasca->rol_assignat !== $user->rol_principal && 
            $tasca->usuari_assignat_id !== $user->id) {
            abort(403, 'No tens permís per veure aquesta tasca');
        }
        
        // Carregar relacions necessàries
        $tasca->load(['checklistInstance.empleat', 'checklistInstance.template']);
        
        return view('tasques.show', compact('tasca', 'user'));
    }
    
    /**
     * Completar una tasca
     */
    public function completarTasca(Request $request, ChecklistTask $tasca)
    {
        $user = Auth::user();
        
        // Verificar que l'usuari té permís per completar aquesta tasca
        if ($user->rol_principal !== 'admin' && 
            $tasca->rol_assignat !== $user->rol_principal && 
            $tasca->usuari_assignat_id !== $user->id) {
            abort(403, 'No tens permís per completar aquesta tasca');
        }
        
        // Validar request
        $validated = $request->validate([
            'observacions' => 'nullable|string|max:500',
        ]);
        
        // Completar la tasca
        $tasca->completar($user, $validated['observacions'] ?? null);
        
        return redirect()
            ->route('tasques.index')
            ->with('success', 'Tasca completada correctament');
    }
}
