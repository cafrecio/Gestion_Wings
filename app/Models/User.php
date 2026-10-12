<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'profesor_id',
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
            'es_superadmin'     => 'boolean',
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

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class);
    }

    /**
     * Subrubro de sueldo del usuario operativo, bajo el rubro "Sueldos".
     * Lo crea el alta de usuario; el vínculo es por FK, no por nombre.
     */
    public function subrubro(): BelongsTo
    {
        return $this->belongsTo(Subrubro::class);
    }

    /**
     * Iniciales de 2 letras del usuario (para mostrar quién validó en cashflow).
     * Desempata si dos administradores activos comparten iniciales (ej. CB y CZ o CO).
     */
    public function iniciales(): string
    {
        if (empty($this->name)) {
            return '';
        }

        $palabras = array_values(array_filter(explode(' ', trim($this->name))));
        if (count($palabras) === 0) {
            return '';
        }
        if (count($palabras) === 1) {
            return mb_strtoupper(mb_substr($palabras[0], 0, 2));
        }

        $primera = mb_strtoupper(mb_substr($palabras[0], 0, 1));
        $apellido = end($palabras);
        $inicialApellido = mb_strtoupper(mb_substr($apellido, 0, 1));

        // Desempate si hay colisión con otro usuario activo con el mismo rol
        $colision = static::where('id', '!=', $this->id)
            ->where('rol', $this->rol)
            ->where('activo', true)
            ->get()
            ->contains(function ($otro) use ($primera, $inicialApellido) {
                $p = array_values(array_filter(explode(' ', trim($otro->name))));
                if (count($p) < 2) return false;
                $p1 = mb_strtoupper(mb_substr($p[0], 0, 1));
                $pA = mb_strtoupper(mb_substr(end($p), 0, 1));
                return $p1 === $primera && $pA === $inicialApellido && $otro->id < $this->id;
            });

        if ($colision) {
            $ultima = mb_strtoupper(mb_substr($apellido, -1, 1));
            if ($ultima !== $inicialApellido) {
                return $primera . $ultima;
            }
            $segunda = mb_strtoupper(mb_substr($apellido, 1, 1));
            return $primera . $segunda;
        }

        return $primera . $inicialApellido;
    }
}
