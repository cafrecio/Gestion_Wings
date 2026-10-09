<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_cobertura', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->date('desde');
        });
        Schema::create('reporte_eventos', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 20);
            $t->unsignedBigInteger('origen_id');
            $t->foreignId('deuda_cuota_id')->nullable()->constrained('deuda_cuotas')->cascadeOnDelete();
            $t->foreignId('liquidacion_id')->nullable()->constrained('liquidaciones')->cascadeOnDelete();
            $t->string('periodo', 7)->nullable();
            $t->unsignedBigInteger('persona_id')->nullable();
            $t->unsignedBigInteger('deporte_id')->nullable();
            $t->unsignedBigInteger('nivel_id')->nullable();
            $t->date('fecha');
            $t->bigInteger('delta_centavos');
            $t->string('motivo', 40);
            $t->timestamp('registrado_en');
            $t->index(['tipo', 'fecha']);
            $t->index(['tipo', 'origen_id']);
        });
        Schema::table('subrubros', fn (Blueprint $t) => $t->string('clasificacion_resultado', 15)->nullable());
        // Sueldos existentes acreditados por FK, nunca por interpretar nombres.
        $sueldos = DB::table('profesores')->whereNotNull('subrubro_id')->pluck('subrubro_id')
            ->merge(DB::table('users')->where('rol', 'OPERATIVO')->whereNotNull('subrubro_id')->pluck('subrubro_id'));
        DB::table('subrubros')->whereIn('id', $sueldos)->whereIn('rubro_id',
            DB::table('rubros')->where('nombre', 'Sueldos')->where('tipo', 'EGRESO')->select('id'))
            ->update(['clasificacion_resultado' => 'NEGOCIO']);
        foreach (['cashflow_movimientos', 'movimientos_operativos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->unsignedBigInteger('reporte_deporte_id')->nullable();
                $t->string('reporte_clasificacion', 15)->nullable();
                $t->string('reporte_tipo', 7)->nullable();
            });
        }
        // No se retrofecha: solo se acredita el saldo conocido al activar el historial.
        DB::table('reporte_cobertura')->insert(['id' => 1, 'desde' => today()->toDateString()]);
        foreach (DB::table('tipos_caja')->get() as $caja) {
            DB::table('reporte_eventos')->insert([
                'tipo' => 'CAJA_INICIAL', 'origen_id' => $caja->id, 'fecha' => today(),
                'delta_centavos' => (int) round($caja->saldo_inicial * 100),
                'motivo' => 'SALDO_INICIAL_CONOCIDO', 'registrado_en' => now(),
            ]);
        }
        DB::table('deuda_cuotas')->orderBy('id')->chunkById(200, function ($deudas) {
            foreach ($deudas as $d) {
                $alumno = DB::table('alumnos')->where('id', $d->alumno_id)->first();
                $grupo = DB::table('grupos')->where('id', $alumno?->grupo_id)->first();
                $saldo = $d->estado === 'CONDONADA' ? 0 : max(0, $d->monto_original - $d->monto_pagado);
                DB::table('reporte_eventos')->insert([
                    'tipo' => 'CUOTA', 'origen_id' => $d->id, 'periodo' => $d->periodo,
                    'deuda_cuota_id' => $d->id,
                    'persona_id' => $d->alumno_id, 'deporte_id' => $alumno?->deporte_id,
                    'nivel_id' => $grupo?->nivel_id, 'fecha' => today(),
                    'delta_centavos' => (int) round($saldo * 100), 'motivo' => 'SALDO_INICIAL_CONOCIDO',
                    'registrado_en' => now(),
                ]);
            }
        });
        DB::table('liquidaciones')->where('estado', 'CERRADA')->where('estado_pago', 'PENDIENTE')
            ->orderBy('id')->chunkById(200, function ($liquidaciones) {
                foreach ($liquidaciones as $l) {
                    DB::table('reporte_eventos')->insert([
                        'tipo' => 'LIQUIDACION', 'origen_id' => $l->id,
                        'liquidacion_id' => $l->id,
                        'periodo' => sprintf('%04d-%02d', $l->anio, $l->mes), 'persona_id' => $l->profesor_id,
                        'deporte_id' => DB::table('profesores')->where('id', $l->profesor_id)->value('deporte_id'),
                        'fecha' => today(), 'delta_centavos' => (int) round($l->total_calculado * 100),
                        'motivo' => 'SALDO_INICIAL_CONOCIDO', 'registrado_en' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        foreach (['cashflow_movimientos', 'movimientos_operativos'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn(['reporte_deporte_id', 'reporte_clasificacion', 'reporte_tipo']));
        }
        Schema::table('subrubros', fn (Blueprint $t) => $t->dropColumn('clasificacion_resultado'));
        Schema::dropIfExists('reporte_eventos');
        Schema::dropIfExists('reporte_cobertura');
    }
};
