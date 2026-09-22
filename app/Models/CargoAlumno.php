<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoAlumno extends Model
{
    protected $table = 'cargos_alumno';
    protected $guarded = ['id'];
    protected $casts = ['calculo' => 'array', 'monto_original' => 'decimal:2', 'monto_condonado' => 'decimal:2'];

    public function pagos()
    {
        return $this->belongsToMany(Pago::class, 'pago_cargo_alumno')->withPivot('monto_aplicado')->withTimestamps();
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->estado === 'ANULADO' ? 0 : round((float) $this->monto_original - (float) $this->monto_condonado - $this->monto_cobrado, 2);
    }

    public function getMontoCobradoAttribute(): float
    {
        $query = \Illuminate\Support\Facades\DB::table('pago_cargo_alumno')->where('cargo_alumno_id', $this->id);
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) $query->lockForUpdate();
        return (float) $query->get()->sum('monto_aplicado');
    }
}
