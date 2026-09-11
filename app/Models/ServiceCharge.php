<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCharge extends Model
{
    protected $fillable = [
        'service_id',
        'numero',
        'vencimiento',
        'monto',
        'estado',
    ];

    protected $casts = [
        'vencimiento' => 'date',
        'monto' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ServiceChargeShare::class);
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'pagado';
    }

    /**
     * Recalcula el estado del cargo según sus participaciones:
     * queda 'pagado' solo si todas las participaciones están pagadas.
     */
    public function sincronizarEstado(): void
    {
        $quedanPendientes = $this->shares()->where('estado', '!=', 'pagado')->exists();
        $nuevo = $quedanPendientes ? 'pendiente' : 'pagado';

        if ($this->estado !== $nuevo) {
            $this->update(['estado' => $nuevo]);
        }
    }

    /**
     * Marca todo el cargo (y sus participaciones) como pagado.
     */
    public function marcarPagado(): void
    {
        $this->shares()->update(['estado' => 'pagado', 'fecha_pago' => now()]);
        $this->update(['estado' => 'pagado']);
    }

    /**
     * Marca todo el cargo (y sus participaciones) como pendiente.
     */
    public function marcarPendiente(): void
    {
        $this->shares()->update(['estado' => 'pendiente', 'fecha_pago' => null]);
        $this->update(['estado' => 'pendiente']);
    }
}
