<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor', 'descripcion', 'tipo'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $config = static::where('clave', $clave)->first();
        if (!$config) return $default;
        return match ($config->tipo) {
            'integer' => (int) $config->valor,
            'boolean' => filter_var($config->valor, FILTER_VALIDATE_BOOLEAN),
            default   => $config->valor,
        };
    }

    public static function set(string $clave, mixed $valor): void
    {
        static::where('clave', $clave)
            ->update(['valor' => (string) $valor]);
    }
}
