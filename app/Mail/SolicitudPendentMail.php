<?php

namespace App\Mail;

use App\Models\SolicitudAcces;
use App\Models\Validacio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudPendentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(
        public SolicitudAcces $solicitud,
        public Validacio $validacio
    ) {}

    /**
     * Construir el missatge.
     */
    public function build()
    {
        $empleat = $this->solicitud->empleatDestinatari;
        $sistema = $this->validacio->sistema;
        $esGrup = $this->validacio->tipus_validacio === 'grup';
        
        $subject = $esGrup 
            ? "Nova sol·licitud d'accés per validar (grup) - {$sistema->nom}"
            : "Nova sol·licitud d'accés per validar - {$sistema->nom}";
        
        return $this->subject($subject)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.solicitud-pendent', [
                'empleat' => $empleat,
                'sistema' => $sistema,
                'solicitud' => $this->solicitud,
                'validacio' => $this->validacio,
                'esGrup' => $esGrup,
                'url' => url("/admin/validacions/{$this->validacio->id}")
            ]);
    }
}
