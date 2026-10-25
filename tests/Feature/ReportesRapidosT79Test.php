<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Services\{CobranzaEstadoService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\ReportesEscenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T79: con dos años de datos, Reportes tardaba 22 s porque releía toda la historia ocho veces.
 * Estas pruebas fijan que se lee una vez por pedido, que fuera de un pedido nunca se sirve
 * algo viejo, y que los atajos dan los mismos números que el cálculo completo.
 */
#[\PHPUnit\Framework\Attributes\Group('lento')]
class ReportesRapidosT79Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE']);
        $this->seed(ReportesEscenarioSeeder::class);
        Carbon::setTestNow('2026-10-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function lecturasDelHistorial(callable $calculo): int
    {
        $lecturas = 0;
        DB::listen(function ($q) use (&$lecturas) {
            if (str_starts_with($q->sql, 'select * from `cashflow_movimientos`')) $lecturas++;
        });
        $calculo();
        return $lecturas;
    }

    public function test_reportes_lee_la_historia_una_sola_vez_por_pedido(): void
    {
        $s = app(ReporteMensualService::class);

        $this->assertSame(1, $this->lecturasDelHistorial(fn () => $s->evolucion('2026-10')));
        $this->assertSame(1, $this->lecturasDelHistorial(fn () => $s->enLote(fn () => [$s->obtener('2026-10'), $s->evolucion('2026-10')])));

        $this->actingAs(\App\Models\User::where('rol', 'ADMIN')->firstOrFail());
        $this->assertSame(1, $this->lecturasDelHistorial(fn () => $this->get('/reportes?mes=2026-10')->assertOk()));
    }

    public function test_fuera_de_un_pedido_el_mismo_servicio_ve_lo_nuevo(): void
    {
        $s = app(ReporteMensualService::class);
        $antes = $s->enLote(fn () => $s->obtener('2026-10'));
        $efectivo = DB::table('tipos_caja')->where('nombre', 'Efectivo')->value('id');
        DB::table('cashflow_movimientos')->insert(['fecha' => '2026-10-08', 'subrubro_id' => DB::table('subrubros')->value('id'),
            'tipo_caja_id' => $efectivo, 'monto' => '1234.56', 'usuario_admin_id' => DB::table('users')->value('id'),
            'reporte_tipo' => 'INGRESO', 'reporte_clasificacion' => 'APORTE', 'created_at' => now(), 'updated_at' => now()]);

        $despues = $s->obtener('2026-10');
        $this->assertSame($antes['disponible'] + 123456, $despues['disponible']);
        $this->assertSame($antes['aportes'] + 123456, $despues['aportes']);
        $this->assertSame($despues, $s->enLote(fn () => $s->obtener('2026-10')));
    }

    public function test_saldos_de_cashflow_son_los_mismos_que_los_del_reporte(): void
    {
        $s = app(ReporteMensualService::class);
        $reporte = $s->obtener('2026-10');

        $this->assertNotSame(0, array_sum(array_column($reporte['cajas'], 'pendiente')), 'El escenario tiene una caja sin validar.');
        $this->assertSame($reporte['cajas'], $s->saldosPorCaja('2026-10-09'));
        $this->assertNull($s->saldosPorCaja('2020-01-31'), 'Antes del historial no se inventan saldos.');
    }

    public function test_cobranza_da_lo_mismo_con_las_deudas_ya_cargadas(): void
    {
        $servicio = app(CobranzaEstadoService::class);
        $sueltos = Alumno::where('activo', true)->get();
        $cargados = Alumno::where('activo', true)->with('deudaCuotas')->get();

        $this->assertGreaterThan(0, array_sum(array_column($servicio->saldoDeAlumnos($sueltos), 'cuotas')));
        $this->assertSame($servicio->saldoDeAlumnos($sueltos), $servicio->saldoDeAlumnos($cargados));
    }
}
