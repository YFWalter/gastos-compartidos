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
}
