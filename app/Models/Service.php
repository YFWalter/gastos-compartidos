<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'user_id',
        'descripcion',
        'monto_mensual',
        'fecha_primer_vencimiento',
        'notas',
        'estado',
        'avisar_participantes',
        'cancelado_en',
    ];

    protected $casts = [
        'fecha_primer_vencimiento' => 'date',
        'monto_mensual' => 'decimal:2',
        'avisar_participantes' => 'boolean',
        'cancelado_en' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ServiceSplit::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ServiceCharge::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'service_splits')
            ->withPivot('porcentaje')
            ->withTimestamps();
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Cancela el servicio: detiene la generación de cargos futuros.
     * Los cargos ya generados y pendientes no se tocan.
     */
    public function cancelar(): void
    {
        $this->update(['estado' => 'cancelado', 'cancelado_en' => now()]);
    }
}
