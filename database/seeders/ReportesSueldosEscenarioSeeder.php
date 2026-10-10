<?php

namespace Database\Seeders;

use App\Models\{Alumno, Deporte, Liquidacion, Profesor, Subrubro, TipoCaja, User};
use App\Services\{LiquidacionPagoService, LiquidacionService, SubrubroSueldoService};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Historia ficticia para capturas. No reconstruye ni modifica historia del club. */
class ReportesSueldosEscenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (!str_starts_with(DB::connection()->getDatabaseName(), 'wings_testing') || Alumno::exists()) {
            throw new RuntimeException('Requiere una base wings_testing sin alumnos.');
        }
        $reloj = Carbon::getTestNow();
        try {
            DB::table('reporte_analitico_cobertura')->where('id',1)->update(['desde'=>'2026-04-01 00:00:00']);
            $this->call(ReportesEscenarioSeeder::class);
            $this->call(ReportesAsistenciaEscenarioSeeder::class);
            Carbon::setTestNow('2026-10-09 18:00:00');
            $base = Profesor::orderBy('id')->firstOrFail();
            $base->update(['porcentaje_comision'=>20]);
            // SOLO ficción: las tarifas del escenario se consideran conocidas desde abril.
            // Los datos originales se crearon cronológicamente por los seeders anteriores.
            DB::table('reporte_analitico_historial')->where('tipo','TARIFA')->update(['observado_en'=>'2026-04-01 00:00:00']);
            $admin = User::where('rol','ADMIN')->firstOrFail();
            $profesores = Profesor::whereHas('deporte',fn ($q) => $q->where('tipo_liquidacion','COMISION'))
                ->whereDoesntHave('liquidaciones')->orderBy('id')->get();
            foreach ($profesores->take(2) as $i=>$profesor) {
                app(SubrubroSueldoService::class)->paraProfesor($profesor);
                $l = app(LiquidacionService::class)->generarLiquidacionMensual($profesor->id,10,2026);
                app(LiquidacionService::class)->cerrarLiquidacion($l->id);
                $l = $l->fresh()->ajustarMontoFinal($i === 0 ? '42000.00' : '18000.00',$admin->id,'Acuerdo ficticio para revisar el monto final');
                if ($i === 1) {
                    $subrubro = Subrubro::findOrFail($profesor->fresh()->subrubro_id);
                    app(LiquidacionPagoService::class)->marcarComoPagada($l->id,[
                        'fecha_pago'=>'2026-10-09','tipo_caja_id'=>TipoCaja::where('abreviatura','BNA')->firstOrFail()->id,
                        'subrubro_id'=>$subrubro->id,'admin_id'=>$admin->id,'monto_esperado'=>'18000.00',
                    ]);
                }
            }
        } finally {
            Carbon::setTestNow($reloj);
        }
    }
}
