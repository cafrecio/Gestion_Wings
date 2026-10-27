<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcepcionCobranza extends Model
{
    protected $table = 'excepciones_cobranza';
    protected $guarded = ['id'];
    protected $casts = ['ocurrida_at' => 'datetime', 'contexto' => 'array'];

    public function autor() { return $this->belongsTo(User::class, 'autor_id'); }
    public function alumno() { return $this->belongsTo(Alumno::class); }

    public function etiqueta(): string
    {
        return match ($this->tipo) {
            'COBRO_PARCIAL' => 'Cobro parcial',
            'COBRO_DEUDA_ANTERIOR' => 'Cobro con meses anteriores impagos',
            'ASISTENCIA_NUEVO' => 'Alumno nuevo sin primer pago',
            'ASISTENCIA_DEUDOR' => 'Asistencia de alumno deudor',
            default => $this->tipo,
        };
    }
}
