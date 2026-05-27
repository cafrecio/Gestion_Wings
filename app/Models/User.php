<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    const ROL_ADMIN     = 'ADMIN';
    const ROL_OPERATIVO = 'OPERATIVO';
    const ROL_PROFESOR  = 'PROFESOR';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
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
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    /**
     * Cajas operativas del usuario (como operativo)
     */
    public function cajasOperativas(): HasMany
    {
        return $this->hasMany(CajaOperativa::class, 'usuario_operativo_id');
    }

    /**
     * Movimientos operativos registrados por el usuario
     */
    public function movimientosOperativos(): HasMany
    {
        return $this->hasMany(MovimientoOperativo::class, 'usuario_id');
    }

    /**
     * Movimientos de cashflow registrados por el usuario (admin)
     */
    public function cashflowMovimientos(): HasMany
    {
        return $this->hasMany(CashflowMovimiento::class, 'usuario_admin_id');
    }

    public static function getRoles(): array
    {
        return [
            self::ROL_ADMIN     => 'Administrador',
            self::ROL_OPERATIVO => 'Operativo',
            self::ROL_PROFESOR  => 'Profesor',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function isOperativo(): bool
    {
        return $this->rol === self::ROL_OPERATIVO;
    }

    public function isProfesor(): bool
    {
        return $this->rol === self::ROL_PROFESOR;
    }

    public function isActivo(): bool
    {
        return (bool) $this->activo;
    }
}
