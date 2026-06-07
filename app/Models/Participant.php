<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Participant extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'email',
        'telefono',
    ];

    protected static function booted(): void
    {
        // Genera un token único al crear el participante (para el link de invitado).
        static::creating(function (Participant $participant) {
            if (empty($participant->token)) {
                $participant->token = static::generarToken();
            }
        });
    }

    /**
     * Genera un token único que no colisione con otro participante.
     */
    public static function generarToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Regenera el token (invalida el link anterior).
     */
    public function regenerarToken(): void
    {
        // token no está en $fillable, así que se asigna directo (no por mass-assignment).
        $this->token = static::generarToken();
        $this->save();
    }

    /**
     * URL pública para que el participante vea sus cuotas sin registrarse.
     */
    public function getEnlaceInvitadoAttribute(): string
    {
        return route('guest.participant', $this->token);
    }

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
