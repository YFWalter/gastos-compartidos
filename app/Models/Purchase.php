<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'descripcion',
        'monto_total',
        'cantidad_cuotas',
        'fecha_primera_cuota',
        'notas',
    ];

    protected $casts = [
        'fecha_primera_cuota' => 'date',
        'monto_total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(PurchaseSplit::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'purchase_splits')
            ->withPivot('porcentaje')
            ->withTimestamps();
    }
}
