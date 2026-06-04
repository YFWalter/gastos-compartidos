<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    protected $fillable = [
        'purchase_id',
        'numero',
        'vencimiento',
        'monto',
        'estado',
    ];

    protected $casts = [
        'vencimiento' => 'date',
        'monto' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(InstallmentShare::class);
    }

    public function estaPagada(): bool
    {
        return $this->estado === 'pagada';
    }

    /**
     * Recalcula el estado de la cuota según sus participaciones:
     * queda 'pagada' solo si todas las participaciones están pagadas.
     */
    public function sincronizarEstado(): void
    {
        $quedanPendientes = $this->shares()->where('estado', '!=', 'pagado')->exists();
        $nuevo = $quedanPendientes ? 'pendiente' : 'pagada';

        if ($this->estado !== $nuevo) {
            $this->update(['estado' => $nuevo]);
        }
    }

    /**
     * Marca toda la cuota (y sus participaciones) como pagada.
     */
    public function marcarPagada(): void
    {
        $this->shares()->update(['estado' => 'pagado', 'fecha_pago' => now()]);
        $this->update(['estado' => 'pagada']);
    }

    /**
     * Marca toda la cuota (y sus participaciones) como pendiente.
     */
    public function marcarPendiente(): void
    {
        $this->shares()->update(['estado' => 'pendiente', 'fecha_pago' => null]);
        $this->update(['estado' => 'pendiente']);
    }
}
