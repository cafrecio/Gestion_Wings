<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, CashflowMovimiento, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User};
use App\Services\{CobranzaEstadoService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verificación de Claude sobre B11 (Codex). Vive fuera de tests/: no es parte de la suite.
 * Va por los caminos que usa la gente: el comando programado, la pantalla de Configuración,
 * el cobro por pantalla y las pantallas que suman deuda.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit --configuration phpunit.xml \
 *     docs/06-pruebas/PRU-04/evidencia-b11-claude/VerificacionB11ClaudeTest.php
 */
class VerificacionB11ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 09:00:00');
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->deporte = Deporte::where('nombre', 'Patín')->firstOrFail();
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Inicial'])->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(string $dni, string $alta, bool $activo = true): Alumno
    {
        $a = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'Mora '.$dni, 'dni' => $dni, 'fecha_nacimiento' => '2014-01-01',
            'fecha_alta' => $alta, 'celular' => '1140000000', 'deporte_id' => $this->deporte->id, 'grupo_id' => $this->grupo->id, 'activo' => $activo]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $alta, 'activo' => true]);

        return $a;
    }

    private function cuota(Alumno $a, string $periodo, float $monto = 30000): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => $monto, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function porPantalla(string $clave, string $valor): void
    {
        $this->actingAs($this->admin)->patch(route('web.configuraciones.update', $clave), ['valor' => $valor])->assertSessionHasNoErrors();
    }

    private function correr(): void
    {
        $this->artisan('cobranza:aplicar-punitorios')->assertSuccessful();
    }

    private function recargo(DeudaCuota $cuota): ?CargoAlumno
    {
        return CargoAlumno::where('clave_origen', 'punitorio:deuda:'.$cuota->id)->first();
    }

    /** Criterio 3 del contrato: las claves se leen de verdad, cargadas por pantalla. Y 12,5 no es 12. */
    public function test_el_porcentaje_cargado_por_pantalla_es_el_que_se_aplica_con_decimales(): void
    {
        $a = $this->alumno('41000001', '2025-03-01');
        $octubre = $this->cuota($a, '2026-10');
        $this->porPantalla('mora_porcentaje', '12,5');
        $this->porPantalla('mora_dia_desde', '15');

        Carbon::setTestNow('2026-10-15 09:00:00');
        $this->correr();
        $this->assertNull($this->recargo($octubre), 'El día 15 configurado todavía no tiene recargo.');

        Carbon::setTestNow('2026-10-16 09:00:00');
        $this->correr();
        $this->correr();
        $this->assertSame(1, CargoAlumno::where('tipo', 'PUNITORIO')->count(), 'Corrido dos veces, un solo recargo.');
        $this->assertSame('3750.00', $this->recargo($octubre)->monto_original, '12,5 % de 30.000.');

        $this->porPantalla('mora_porcentaje', '20');
        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->correr();
        $this->assertSame('3750.00', $this->recargo($octubre)->fresh()->monto_original, 'Queda congelado al cambiar el porcentaje.');
    }

    public function test_con_porcentaje_cero_no_existe(): void
    {
        $a = $this->alumno('41000002', '2025-03-01');
        $this->cuota($a, '2026-10');
        $antes = app(CobranzaEstadoService::class)->resumenDashboard();
        Carbon::setTestNow('2026-10-20 09:00:00');
        $antes = app(CobranzaEstadoService::class)->resumenDashboard();
        $this->correr();
        $this->assertSame(0, CargoAlumno::where('tipo', 'PUNITORIO')->count());
        $this->assertEquals($antes, app(CobranzaEstadoService::class)->resumenDashboard());
    }

    /** D1 a D3, decididas por Carlos el 16/10. */
    public function test_ni_deuda_vieja_ni_alumno_nuevo_en_su_plazo_ni_inactivo(): void
    {
        $viejo = $this->alumno('41000003', '2025-03-01');
        $septiembre = $this->cuota($viejo, '2026-09');
        $octubreViejo = $this->cuota($viejo, '2026-10');
        $nuevo = $this->alumno('41000004', '2026-10-09');
        $octubreNuevo = $this->cuota($nuevo, '2026-10');
        $inactivo = $this->alumno('41000005', '2025-03-01', false);
        $octubreInactivo = $this->cuota($inactivo, '2026-10');
        $this->porPantalla('mora_porcentaje', '10');

        Carbon::setTestNow('2026-10-11 09:00:00');
        $this->correr();
        $this->assertNull($this->recargo($septiembre), 'D1: la deuda de septiembre no lleva recargo al prender el porcentaje en octubre.');
        $this->assertNotNull($this->recargo($octubreViejo));
        $this->assertNull($this->recargo($octubreNuevo), 'D2: ingresó el 09/10, está en su plazo de 7 días.');
        $this->assertNull($this->recargo($octubreInactivo), 'D3: inactivo.');

        Carbon::setTestNow('2026-10-17 09:00:00');
        $this->correr();
        $this->assertNotNull($this->recargo($octubreNuevo), 'D2: vencido su plazo propio, lleva recargo.');
        $this->assertNull($this->recargo($septiembre));
    }

    /** D4: el recargo impago es deuda, y todas las pantallas dan lo mismo. */
    public function test_el_recargo_suma_igual_en_todas_las_pantallas_y_el_alumno_no_esta_al_dia(): void
    {
        $a = $this->alumno('41000006', '2025-03-01');
        $octubre = $this->cuota($a, '2026-10');
        $this->porPantalla('mora_porcentaje', '5');
        Carbon::setTestNow('2026-10-11 09:00:00');
        $this->correr();

        $cobranza = (int) round(app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'] * 100);
        $reportes = app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total'];
        $inicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('reporte')['deuda']['total'];
        $buscador = (int) round(app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$a]))[$a->id]['total'] * 100);
        $this->assertSame([3150000, 3150000, 3150000, 3150000], [$cobranza, $reportes, $inicio, $buscador], 'Cobranza, Reportes, Inicio y buscador: 30.000 + 1.500.');

        // Paga la cuota y no el recargo: sigue sin estar al día.
        DeudaCuota::whereKey($octubre->id)->update(['monto_pagado' => 30000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
        $this->assertNotSame('AL_DIA', app(CobranzaEstadoService::class)->estadoAlumno($a->id)['estado']);
    }

    /** El cobro por pantalla, como lo hace la gente: cuota primero, recargo después, cada uno en su rubro. */
    public function test_cobro_por_pantalla_cuota_y_recargo_en_su_rubro_y_anulacion(): void
    {
        $a = $this->alumno('41000007', '2025-03-01');
        $octubre = $this->cuota($a, '2026-10');
        $this->porPantalla('mora_porcentaje', '5');
        Carbon::setTestNow('2026-10-11 09:00:00');
        $this->correr();
        $recargo = $this->recargo($octubre);

        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $a->id))->assertOk()->assertSee('Recargo por mora');

        // Entrega menos que la cuota: no toca el recargo.
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $a->id), ['tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 20000], 'monto_entregado' => 20000])->assertSessionHas('success');
        $this->assertEquals(1500.0, $recargo->fresh()->saldo_pendiente);

        // Completa: los 10.000 que faltan de la cuota y el recargo.
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $a->id), ['tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 10000], 'monto_entregado' => 11500])->assertSessionHas('success');
        $this->assertEquals(0.0, $recargo->fresh()->saldo_pendiente);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $octubre->fresh()->estado);
        $pago = Pago::latest('id')->firstOrFail();
        $this->assertEquals(10000.0, (float) $pago->monto_cuota, 'La comisión se calcula sobre monto_cuota: el recargo no entra.');
        $porRubro = CashflowMovimiento::where('referencia_id', $pago->id)->join('subrubros', 'subrubros.id', '=', 'cashflow_movimientos.subrubro_id')
            ->pluck('cashflow_movimientos.monto', 'subrubros.nombre')->map(fn ($m) => (float) $m)->all();
        $this->assertEquals(['Cuota Mensual' => 10000.0, 'Punitorio Cuota' => 1500.0], $porRubro);
        $this->assertSame('AL_DIA', app(CobranzaEstadoService::class)->estadoAlumno($a->id)['estado']);

        // Anular ese pago devuelve el recargo a pendiente.
        $this->actingAs($this->admin)->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Cobro cargado dos veces por error'])->assertSessionHas('success');
        $this->assertEquals(1500.0, $recargo->fresh()->saldo_pendiente);
    }

    public function test_solo_el_duenio_perdona_el_recargo_y_perdonarlo_no_limpia_al_alumno(): void
    {
        $a = $this->alumno('41000008', '2025-03-01');
        $octubre = $this->cuota($a, '2026-10');
        $this->porPantalla('mora_porcentaje', '5');
        Carbon::setTestNow('2026-10-11 09:00:00');
        $this->correr();
        $recargo = $this->recargo($octubre);

        $this->actingAs($this->operativo)->post(route('web.punitorios.condonar', $recargo->id), ['motivo' => 'Intento de la operativa sin permiso']);
        $this->assertEquals(0.0, (float) $recargo->fresh()->monto_condonado, 'La operativa no perdona.');

        $this->actingAs($this->admin)->post(route('web.punitorios.condonar', $recargo->id), ['motivo' => 'Pagó tarde por enfermedad'])->assertSessionHas('success');
        $this->assertEquals(1500.0, (float) $recargo->fresh()->monto_condonado);
        $this->assertNotSame('AL_DIA', app(CobranzaEstadoService::class)->estadoAlumno($a->id)['estado'], 'Sigue debiendo la cuota.');
    }
}
