<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentShare extends Model
{
    protected $fillable = [
        'installment_id',
        'participant_id',
        'monto',
        'estado',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function estaPagada(): bool
    {
        return $this->estado === 'pagado';
    }

    /**
     * Marca esta participación como pagada y sincroniza el estado de la cuota.
     */
    public function marcarPagada(): void
    {
        $this->update(['estado' => 'pagado', 'fecha_pago' => now()]);
        $this->installment->sincronizarEstado();
    }

    /**
     * Marca esta participación como pendiente y sincroniza el estado de la cuota.
     */
    public function marcarPendiente(): void
    {
        $this->update(['estado' => 'pendiente', 'fecha_pago' => null]);
        $this->installment->sincronizarEstado();
    }
}
