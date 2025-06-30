<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmpleatRequest;
use App\Http\Requests\UpdateEmpleatRequest;
use App\Models\Empleat;
use App\Models\Departament;
use App\Models\ChecklistInstance;
use App\Models\ChecklistTemplate;
use App\Jobs\CrearChecklistOnboarding;
use App\Jobs\NotificarRRHHConfirmacio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpleatController extends Controller
{
    /**
     * Mostrar el llistat d'empleats
     */
    public function index(Request $request)
    {
        $query = Empleat::with(['departament']);

        // Aplicar filtres
        if ($request->filled('departament')) {
            $query->where('departament_id', $request->departament);
        }

        if ($request->filled('estat')) {
            $query->where('estat', $request->estat);
        }

        if ($request->filled('data_alta')) {
            $query->whereDate('data_alta', $request->data_alta);
        }

        // Cercar
        if ($request->filled('cerca')) {
            $cerca = $request->cerca;
            $query->where(function($q) use ($cerca) {
                $q->where('nom_complet', 'like', "%{$cerca}%")
                  ->orWhere('nif', 'like', "%{$cerca}%")
                  ->orWhere('correu_personal', 'like', "%{$cerca}%");
            });
        }

        // Ordenar per data d'alta descendent per defecte
        $empleats = $query->orderBy('data_alta', 'desc')->paginate(10);

        // Obtenir departaments per al filtre
        $departaments = Departament::where('actiu', true)->orderBy('nom')->get();

        return view('empleats.index', compact('empleats', 'departaments'));
    }

    /**
     * Mostrar el formulari de creació d'empleat
     */
    public function create()
    {
        $departaments = Departament::where('actiu', true)->orderBy('nom')->get();
        $checklistTemplates = ChecklistTemplate::where('actiu', true)
            ->where('tipus', 'onboarding')
            ->orderBy('nom')
            ->get();
        return view('empleats.create', compact('departaments', 'checklistTemplates'));
    }

    /**
     * Guardar un nou empleat
     */
    public function store(StoreEmpleatRequest $request)
    {
        $empleat = new Empleat($request->validated());
        $empleat->estat = 'actiu';
        $empleat->data_alta = now();
        $empleat->usuari_creador_id = Auth::id();
        $empleat->save();

        // Si s'ha seleccionat una plantilla específica, la utilitzem
        if ($request->filled('checklist_template_id')) {
            $template = ChecklistTemplate::find($request->checklist_template_id);
            if ($template) {
                $template->crearInstancia($empleat);
            }
        } else {
            // Si no, el Job CrearChecklistOnboarding es dispara automàticament pel model Empleat
        }

        return redirect()
            ->route('empleats.show', $empleat)
            ->with('success', 'Empleat creat correctament. S\'ha iniciat el procés d\'onboarding.');
    }

    /**
     * Mostrar detall d'empleat i seguiment d'onboarding
     */
    public function show(Empleat $empleat)
    {
        $empleat->load(['departament', 'usuariCreador']);
        
        // Obtenir la checklist d'onboarding si existeix
        $checklistOnboarding = ChecklistInstance::where('empleat_id', $empleat->id)
            ->whereHas('template', function($q) {
                $q->where('tipus', 'onboarding');
            })
            ->with(['tasques' => function($q) {
                $q->orderBy('ordre');
            }, 'tasques.usuariAssignat'])
            ->first();
        
        // Obtenir esdeveniments d'auditoria relacionats amb l'empleat
        $esdeveniments = \App\Models\LogAuditoria::where('model_type', 'App\Models\Empleat')
            ->where('model_id', $empleat->id)
            ->orWhereHas('related', function($q) use ($empleat) {
                $q->where('model_type', 'App\Models\ChecklistInstance')
                  ->whereHas('model', function($subq) use ($empleat) {
                      $subq->where('empleat_id', $empleat->id);
                  });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('empleats.show', compact('empleat', 'checklistOnboarding', 'esdeveniments'));
    }

    /**
     * Mostrar formulari d'edició d'empleat
     */
    public function edit(Empleat $empleat)
    {
        $departaments = Departament::where('actiu', true)->orderBy('nom')->get();
        return view('empleats.edit', compact('empleat', 'departaments'));
    }

    /**
     * Actualitzar empleat
     */
    public function update(UpdateEmpleatRequest $request, Empleat $empleat)
    {
        $empleat->update($request->validated());
        
        return redirect()
            ->route('empleats.show', $empleat)
            ->with('success', 'Empleat actualitzat correctament.');
    }

    /**
     * Donar de baixa un empleat
     */
    public function baixa(Request $request, Empleat $empleat)
    {
        $request->validate([
            'observacions' => 'nullable|string'
        ]);
        
        $empleat->donarBaixa($request->observacions);
        
        return redirect()
            ->route('empleats.show', $empleat)
            ->with('success', 'Empleat donat de baixa correctament. S\'ha iniciat el procés d\'offboarding.');
    }

    /**
     * Enviar recordatori a IT per tasques pendents
     */
    public function enviarRecordatori(Empleat $empleat)
    {
        // Obtenir la checklist d'onboarding
        $checklistOnboarding = ChecklistInstance::where('empleat_id', $empleat->id)
            ->whereHas('template', function($q) {
                $q->where('tipus', 'onboarding');
            })
            ->first();
        
        if ($checklistOnboarding) {
            // Dispatch job per enviar recordatori
            \App\Jobs\NotificarRecordatoriIT::dispatch($checklistOnboarding);
            
            return redirect()
                ->route('empleats.show', $empleat)
                ->with('success', 'S\'ha enviat un recordatori a IT per les tasques pendents.');
        }
        
        return redirect()
            ->route('empleats.show', $empleat)
            ->with('error', 'No s\'ha trobat cap checklist d\'onboarding per aquest empleat.');
    }

    /**
     * Afegir observació a un empleat
     */
    public function afegirObservacio(Request $request, Empleat $empleat)
    {
        $request->validate([
            'observacio' => 'required|string'
        ]);
        
        // Afegir observació a les existents
        $observacions = $empleat->observacions ? $empleat->observacions . "\n\n" : '';
        $observacions .= "[" . now()->format('d/m/Y H:i') . " - " . Auth::user()->name . "]\n";
        $observacions .= $request->observacio;
        
        $empleat->update([
            'observacions' => $observacions
        ]);
        
        return redirect()
            ->route('empleats.show', $empleat)
            ->with('success', 'Observació afegida correctament.');
    }
}
