<?php

namespace App\Mail;

use App\Models\ChecklistInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NovaChecklistMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(
        public ChecklistInstance $checklistInstance
    ) {}

    /**
     * Construir el missatge.
     */
    public function build()
    {
        $empleat = $this->checklistInstance->empleat;
        $tipus = $this->checklistInstance->getTipusTemplate();
        
        return $this->subject("Nova checklist {$tipus} - {$empleat->nom_complet}")
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.nova-checklist', [
                'empleat' => $empleat,
                'tipus' => $tipus,
                'departament' => $empleat->departament->nom ?? 'No assignat',
                'tasques' => $this->getTasquesIT(),
                'url' => url("/admin/checklist-instances/{$this->checklistInstance->id}")
            ]);
    }
    
    /**
     * Obtenir les tasques assignades al rol IT per a aquesta checklist.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getTasquesIT()
    {
        return $this->checklistInstance->tasques()
            ->where('rol_assignat', 'it')
            ->get();
    }
}
