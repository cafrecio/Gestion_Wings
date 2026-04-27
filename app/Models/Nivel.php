<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nivel extends Model
{
    protected $table = 'niveles';
    protected $fillable = ['nombre', 'descripcion'];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
