<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceChargeShare extends Model
{
    protected $fillable = [
        'service_charge_id',
        'participant_id',
        'monto',
        'estado',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class);
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
     * Marca esta participación como pagada y sincroniza el estado del cargo.
     */
    public function marcarPagada(): void
    {
        $this->update(['estado' => 'pagado', 'fecha_pago' => now()]);
        $this->serviceCharge->sincronizarEstado();
    }

    /**
     * Marca esta participación como pendiente y sincroniza el estado del cargo.
     */
    public function marcarPendiente(): void
    {
        $this->update(['estado' => 'pendiente', 'fecha_pago' => null]);
        $this->serviceCharge->sincronizarEstado();
    }
}
