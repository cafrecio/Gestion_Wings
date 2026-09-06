<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'observacion',
    ];

    /**
     * `es_reservado_sistema` queda fuera de $fillable a propósito: sólo lo
     * fijan las migraciones y el seeder de catálogos, nunca un formulario.
     */
    protected $casts = [
        'es_reservado_sistema' => 'boolean',
    ];

    /**
     * Relación con Subrubros
     */
    public function subrubros(): HasMany
    {
        return $this->hasMany(Subrubro::class);
    }
}
