<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuotasParticipanteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{descripcion: string, numero: int, cantidad: int, vencimiento: \Illuminate\Support\Carbon, monto: string}>  $items
     */
    public function __construct(
        public string $nombre,
        public array $items,
        public float $total,
        public string $enlace = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tenés cuotas por pagar',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recordatorios.participante',
        );
    }
}
