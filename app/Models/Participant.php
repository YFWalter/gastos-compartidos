<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'email',
        'telefono',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseSplits(): HasMany
    {
        return $this->hasMany(PurchaseSplit::class);
    }

    public function installmentShares(): HasMany
    {
        return $this->hasMany(InstallmentShare::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }
}
