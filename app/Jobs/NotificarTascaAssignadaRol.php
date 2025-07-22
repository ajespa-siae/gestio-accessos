<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChecklistTask;
use App\Models\User;
use App\Models\Notificacio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarTascaAssignadaRol implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ChecklistTask $tasca
    ) {}

    public function handle(): void
    {
        if (!$this->tasca->rol_assignat) {
            Log::warning("Tasca {$this->tasca->id} no té rol assignat");
            return;
        }

        // Obtenir usuaris del rol assignat
        $usuaris = $this->obtenirUsuarisDelRol($this->tasca->rol_assignat);

        if ($usuaris->isEmpty()) {
            Log::warning("No s'han trobat usuaris per al rol {$this->tasca->rol_assignat}");
            return;
        }

        // Crear notificació i enviar email per cada usuari del rol
        foreach ($usuaris as $usuari) {
            $this->crearNotificacio($usuari);
            $this->enviarEmail($usuari);
            Log::info("Notificació i email enviats per tasca '{$this->tasca->nom}' assignada al rol '{$this->tasca->rol_assignat}' per usuari: {$usuari->name}");
        }

        Log::info("Notificacions enviades per tasca {$this->tasca->id} al rol {$this->tasca->rol_assignat} ({$usuaris->count()} usuaris)");
    }

    private function obtenirUsuarisDelRol(string $rol): \Illuminate\Support\Collection
    {
        // Buscar usuaris amb rol Shield
        $usuaris = User::whereHas('roles', function($query) use ($rol) {
                $query->where('name', $rol);
            })
            ->where('actiu', true)
            ->get();

        // Si no hi ha usuaris amb Shield, buscar per rol_principal
        if ($usuaris->isEmpty()) {
            $usuaris = User::where('rol_principal', $rol)
                ->where('actiu', true)
                ->get();
        }

        return $usuaris;
    }

    private function crearNotificacio(User $usuari): void
    {
        $titol = "Nova tasca assignada al teu rol";
        $missatge = "S'ha assignat una nova tasca '{$this->tasca->nom}' al rol {$this->tasca->rol_assignat}.";
        
        // Afegir informació de la sol·licitud si és una tasca independent
        if (!$this->tasca->checklist_instance_id && $this->tasca->observacions) {
            $missatge .= " " . $this->tasca->observacions;
        }

        Notificacio::create([
            'user_id' => $usuari->id,
            'titol' => $titol,
            'missatge' => $missatge,
            'tipus' => 'info',
            'llegida' => false,
            'identificador_relacionat' => 'tasca_' . $this->tasca->id
        ]);
    }

    /**
     * Envia un correu electrònic a l'usuari sobre la nova tasca assignada al seu rol
     */
    private function enviarEmail(User $usuari): void
    {
        try {
            $task = $this->tasca;
            $checklistInstance = $task->checklistInstance;
            $empleat = $checklistInstance ? $checklistInstance->empleat : null;
            
            $data = [
                'task' => $task,
                'empleat' => $empleat,
                'usuari' => $usuari,
                'rol' => $task->rol_assignat
            ];
            
            Mail::send('emails.tasca-assignada-rol', $data, function ($message) use ($usuari, $task) {
                $message->to($usuari->email)
                    ->subject("[SIAE] Nova tasca assignada al teu rol: {$task->nom}")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info("Correu enviat a {$usuari->email} per tasca assignada al rol: {$task->nom}");
            
        } catch (\Exception $e) {
            Log::error("Error enviant correu de tasca assignada al rol: " . $e->getMessage());
        }
    }
}
