<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liquidacion por hora: cada clase se paga tarifa x minutos / 60.
     * Se congelan la tarifa aplicada y los minutos pagados de cada clase, para que
     * un cambio posterior de tarifa u horario no reescriba lo liquidado.
     */
    public function up(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('valor_hora_aplicado', 10, 2)->nullable()->after('porcentaje_comision_aplicado');
        });

        Schema::table('liquidacion_detalles', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutos')->nullable()->after('monto');
        });

        $this->rellenar();
    }

    /**
     * Lo ya liquidado no se recalcula. Hasta hoy cada clase se pagaba la tarifa
     * entera, asi que el importe de cada detalle ES la tarifa que se aplico: se
     * toma de ahi y no del profesor, que pudo cambiar. Los minutos reflejan lo que
     * se pago (importe / tarifa x 60), no lo que dura hoy la clase.
     */
    public function rellenar(): void
    {
        $liquidaciones = DB::table('liquidaciones')
            ->where('tipo', 'HORA')
            ->whereNull('valor_hora_aplicado')
            ->get(['id', 'profesor_id']);

        foreach ($liquidaciones as $liquidacion) {
            $tarifa = DB::table('liquidacion_detalles')
                ->where('liquidacion_id', $liquidacion->id)
                ->max('monto');

            if ($tarifa === null || (float) $tarifa <= 0) {
                $tarifa = DB::table('profesores')->where('id', $liquidacion->profesor_id)->value('valor_hora');
            }

            if ($tarifa === null || (float) $tarifa <= 0) {
                continue;
            }

            DB::table('liquidaciones')
                ->where('id', $liquidacion->id)
                ->update(['valor_hora_aplicado' => $tarifa]);

            DB::table('liquidacion_detalles')
                ->where('liquidacion_id', $liquidacion->id)
                ->whereNull('minutos')
                ->update(['minutos' => DB::raw('ROUND(monto / ' . (float) $tarifa . ' * 60)')]);
        }
    }

    public function down(): void
    {
        Schema::table('liquidacion_detalles', function (Blueprint $table) {
            $table->dropColumn('minutos');
        });

        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn('valor_hora_aplicado');
        });
    }
};
