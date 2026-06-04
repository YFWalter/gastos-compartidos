<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResumenCuotasMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{descripcion: string, numero: int, cantidad: int, vencimiento: \Illuminate\Support\Carbon, pendiente: float, detalle: array<int, array{nombre: string, monto: string}>}>  $cuotas
     */
    public function __construct(
        public string $nombre,
        public array $cuotas,
        public float $total,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resumen de cuotas próximas a vencer',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recordatorios.resumen',
        );
    }
}
