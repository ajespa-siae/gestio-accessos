<?php

namespace App\Mail;

use App\Models\ProcessMobilitat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class MobilitatGestorDeptNou extends Mailable
{
    use Queueable, SerializesModels;

    public ProcessMobilitat $processMobilitat;

    /**
     * Create a new message instance.
     */
    public function __construct(ProcessMobilitat $processMobilitat)
    {
        $this->processMobilitat = $processMobilitat;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Gestió Accessos'),
            subject: 'Procés de Mobilitat - Revisió Departament Nou',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.mobilitat-gestor-dept-nou',
            with: [
                'processMobilitat' => $this->processMobilitat,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
