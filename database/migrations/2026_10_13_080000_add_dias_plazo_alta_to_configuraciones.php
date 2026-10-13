<?php

use App\Models\Configuracion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const CLAVE = [
        'clave'       => 'dias_plazo_alta',
        'valor'       => '7',
        'tipo'        => 'integer',
        'descripcion' => 'Días de gracia propios del alumno nuevo para pagar inscripción y cuota del mes de ingreso',
    ];

    public function up(): void
    {
        if (! Configuracion::where('clave', self::CLAVE['clave'])->exists()) {
            Configuracion::create(self::CLAVE);
        }
    }

    public function down(): void
    {
        Configuracion::where('clave', self::CLAVE['clave'])->delete();
    }
};
