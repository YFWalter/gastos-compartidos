<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        // Al registrarse un usuario, se crea su participante "titular" (él mismo),
        // para poder incluirse en el reparto de sus propias compras.
        static::created(function (User $user) {
            $user->crearParticipanteTitular();
        });
    }

    /**
     * Crea (si no existe) el participante que representa al propio usuario.
     */
    public function crearParticipanteTitular(): Participant
    {
        $partes = preg_split('/\s+/', trim($this->name), 2);

        return $this->participants()->create([
            'nombre'     => $partes[0] !== '' ? $partes[0] : 'Yo',
            'apellido'   => $partes[1] ?? null,
            'email'      => $this->email,
            'es_titular' => true,
        ]);
    }

    /**
     * Contactos/participantes cargados por el usuario.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Compras registradas por el usuario.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
