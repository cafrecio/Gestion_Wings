<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, CajaOperativa, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, MovimientoOperativo, Nivel, Pago, Profesor, Subrubro, TipoCaja, User};
use App\Services\{CashflowIntegracionCajaService, HistorialAnaliticoReportesService, PagoCuotaService, ReporteMensualService, ReporteSueldosService};
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail, Notification};
use Tests\TestCase;

class IngresosPorPeriodoT42Test extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;
    private User $admin;
    private TipoCaja $medio;
    private Subrubro $cuota;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 12:00:00');
        Notification::fake();
        Mail::fake();
        foreach (['reporte_cobertura', 'reporte_analitico_cobertura'] as $tabla) {
            DB::table($tabla)->where('id', 1)->update(['desde' => '2026-01-01 00:00:00']);
        }
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->cuota = Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail();
        $deporte = Deporte::create(['nombre' => 'Reportes T42', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel reportes T42']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'Reporte T42', 'dni' => '45123456',
            'fecha_nacimiento' => '2010-01-01', 'celular' => '1100000042', 'fecha_alta' => '2026-01-01',
            'grupo_id' => $grupo->id, 'deporte_id' => $deporte->id, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pago(array $partes, string $fecha = '2026-10-15'): Pago
    {
        $monto = array_sum($partes);
        $pago = Pago::create(['alumno_id' => $this->alumno->id, 'fecha_pago' => $fecha,
            'mes' => (int) substr($fecha, 5, 2), 'anio' => (int) substr($fecha, 0, 4),
            'monto_base' => $monto, 'monto_final' => $monto, 'monto_cuota' => $monto,
            'porcentaje_aplicado' => 100, 'estado' => 'COMPLETADO']);
        foreach ($partes as $periodo => $importe) {
            $deuda = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo,
                'monto_original' => $importe, 'monto_pagado' => $importe, 'estado' => 'PAGADA']);
            $pago->deudasCuota()->attach($deuda->id, ['monto_aplicado' => $importe]);
        }
        return $pago;
    }

    private function asiento(Pago $pago, array $cambios = []): CashflowMovimiento
    {
        return CashflowMovimiento::create(array_replace(['fecha' => $pago->fecha_pago->toDateString(),
            'tipo_caja_id' => $this->medio->id, 'subrubro_id' => $this->cuota->id,
            'monto' => $pago->monto_cuota, 'usuario_admin_id' => $this->admin->id,
            'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA, 'referencia_id' => $pago->id], $cambios));
    }

    private function reporte(string $mes): array
    {
        return app(ReporteMensualService::class)->obtener($mes);
    }

    private function cajaConMovimiento(Pago $pago): array
    {
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $caja = CajaOperativa::create(['usuario_operativo_id' => $operativo->id, 'apertura_at' => $pago->fecha_pago,
            'estado' => 'CERRADA', 'tipo_caja_efectivo_id' => $this->medio->id, 'efectivo_inicial' => 0]);
        $m = MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => $pago->fecha_pago,
            'tipo_caja_id' => $this->medio->id, 'subrubro_id' => $this->cuota->id, 'monto' => $pago->monto_cuota,
            'usuario_id' => $operativo->id, 'alumno_id' => $pago->alumno_id, 'pago_id' => $pago->id, 'estado' => 'ACTIVO']);
        return [$caja, $m];
    }

    public function test_ingreso_adelantado_cambia_de_mes_sin_cambiar_fecha_dinero_ni_saldos(): void
    {
        $pago = $this->pago(['2026-11' => 100]);
        $asiento = $this->asiento($pago);
        Carbon::setTestNow('2026-11-15 12:00:00');
        $octubre = $this->reporte('2026-10');
        $noviembre = $this->reporte('2026-11');
        $this->assertSame(0, $octubre['ingresos']);
        $this->assertSame(10000, $noviembre['ingresos']);
        $this->assertSame(10000, $octubre['resultado_global']);
        $this->assertSame(0, $noviembre['resultado_global']);
        $this->assertSame(10000, $octubre['disponible']);
        $this->assertSame($octubre['disponible'], $noviembre['disponible']);
        $this->assertSame('2026-10-15', $noviembre['filas'][0]['fecha']);
        $this->assertSame('2026-10-15', $asiento->fresh()->fecha->toDateString());
        $this->assertSame('Cuota Mensual (11/2026)', $noviembre['filas'][0]['concepto']);
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->assertViewHas('reporte',
            fn ($r) => $r['ingresos'] === 10000 && $r['disponible'] === 10000);
    }

    public function test_cobro_mixto_reparte_cuotas_y_deja_inscripcion_en_su_fecha(): void
    {
        $pago = $this->pago(['2026-09' => 100, '2026-10' => 200, '2026-11' => 300]);
        $this->asiento($pago);
        $this->asiento($pago, ['subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id, 'monto' => 50]);
        Carbon::setTestNow('2026-11-15');
        $this->assertSame(35000, $this->reporte('2026-10')['ingresos']);
        $this->assertSame(30000, $this->reporte('2026-11')['ingresos']);
        $this->assertSame(65000, $this->reporte('2026-11')['disponible']);
        $this->assertSame(0, $this->reporte('2026-09')['ingresos']);
    }

    public function test_caja_pendiente_y_validada_conservan_el_mismo_reparto_sin_duplicar(): void
    {
        $pago = $this->pago(['2026-11' => 100]);
        [$caja] = $this->cajaConMovimiento($pago);
        Carbon::setTestNow('2026-11-15');
        $antes = $this->reporte('2026-11');
        $this->assertSame(10000, $antes['ingresos']);
        $this->assertFalse($antes['filas'][0]['confirmado']);
        $caja->update(['estado' => 'VALIDADA']);
        app(CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($caja->id, $this->admin->id);
        $despues = $this->reporte('2026-11');
        $this->assertSame($antes['ingresos'], $despues['ingresos']);
        $this->assertSame($antes['disponible'], $despues['disponible']);
        $this->assertTrue($despues['filas'][0]['confirmado']);
        $this->assertDatabaseCount('cashflow_movimientos', 1);
    }

    public function test_asiento_antiguo_con_correspondencia_unica_puede_identificar_el_mes(): void
    {
        $pago = $this->pago(['2026-11' => 100]);
        [$caja] = $this->cajaConMovimiento($pago);
        $caja->update(['estado' => 'VALIDADA']);
        $asiento = $this->asiento($pago, ['referencia_tipo' => CashflowMovimiento::REF_CAJA, 'referencia_id' => $caja->id]);
        Carbon::setTestNow('2026-11-15');
        $this->assertSame(0, $this->reporte('2026-10')['ingresos']);
        $this->assertSame(10000, $this->reporte('2026-11')['ingresos']);
        $this->assertNull($asiento->fresh()->movimiento_operativo_id);
    }

    public function test_asientos_antiguos_ambiguos_conservan_mes_original_por_decision_de_carlos(): void
    {
        $adelantado = $this->pago(['2026-11' => 100]);
        $delMes = $this->pago(['2026-10' => 100]);
        [$caja, $mov] = $this->cajaConMovimiento($adelantado);
        MovimientoOperativo::create(array_replace($mov->only($mov->getFillable()), ['pago_id' => $delMes->id]));
        $caja->update(['estado' => 'VALIDADA']);
        foreach ([$adelantado, $delMes] as $pago) {
            $this->asiento($pago, ['referencia_tipo' => CashflowMovimiento::REF_CAJA, 'referencia_id' => $caja->id]);
        }
        Carbon::setTestNow('2026-11-15');
        $this->assertSame(20000, $this->reporte('2026-10')['ingresos']);
        $this->assertSame(0, $this->reporte('2026-11')['ingresos']);
        $this->assertDatabaseCount('cashflow_movimientos', 2);
    }

    public function test_anular_cobro_admin_revierte_las_partes_aunque_retire_los_pivotes(): void
    {
        $pago = $this->pago(['2026-10' => 100, '2026-11' => 100]);
        $this->asiento($pago);
        Carbon::setTestNow('2026-11-15');
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Prueba de regresión T42', $this->admin->id);
        $this->assertDatabaseCount('pago_deuda_cuota', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 2);
        foreach (['2026-10', '2026-11'] as $mes) {
            $r = $this->reporte($mes);
            $this->assertSame(0, $r['ingresos']);
            $this->assertSame(0, $r['disponible']);
        }
    }

    public function test_historial_del_cobro_real_congela_imputaciones_despues_de_crearlas(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-11',
            'monto_original' => 100, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $resultado = app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
            'usuario_admin_id' => $this->admin->id, 'tipo_caja_id' => $this->medio->id,
            'fecha_pago' => '2026-10-15', 'items' => [['periodo' => '2026-11', 'monto' => 100]]]);
        $ultimo = DB::table('reporte_analitico_historial')->where('tipo', 'PAGO')
            ->where('origen_id', $resultado['pago']->id)->orderByDesc('id')->first();
        $datos = json_decode($ultimo->datos, true);
        $this->assertSame('2026-11', $datos['partes_cuota'][0]['periodo_comision']);
        $this->assertSame(10000, $datos['partes_cuota'][0]['centavos']);
        $this->assertSame(0, $this->reporte('2026-10')['ingresos']);
    }

    public function test_estimacion_de_sueldo_usa_mes_pagado_y_recupera_historial_viejo_sin_reescribirlo(): void
    {
        // No suponer que el cobro adelantado es el primero de todo el historial.
        $this->pago(['2026-10' => 100]);
        $pago = $this->pago(['2026-11' => 100]);
        $profesor = Profesor::create(['deporte_id' => $this->alumno->deporte_id, 'nombre' => 'Prueba', 'apellido' => 'T42',
            'dni' => '32123456', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba',
            'porcentaje_comision' => 40, 'activo' => true]);
        foreach (['2026-10', '2026-11'] as $mes) {
            $clase = Clase::create(['grupo_id' => $this->alumno->grupo_id, 'fecha' => $mes.'-10',
                'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00', 'cancelada' => false]);
            $clase->profesores()->attach($profesor->id);
            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        }
        DB::table('reporte_analitico_historial')->where('tipo', 'PAGO')->where('origen_id', $pago->id)
            ->update(['datos' => json_encode(['cuota_centavos' => 10000, 'estado' => 'COMPLETADO', 'fecha_pago' => '2026-10-15'])]);
        $antes = DB::table('reporte_analitico_historial')->orderBy('id')->get()->toJson();
        Carbon::setTestNow('2026-11-15');
        $hist = app(HistorialAnaliticoReportesService::class)->obtener('PAGO', CarbonImmutable::parse('2026-11-15'));
        $adelantado = collect($hist['filas'])->keyBy('origen_id')->get($pago->id);
        $this->assertSame('2026-11', $adelantado['datos']['partes_cuota'][0]['periodo_comision']);
        $this->assertSame(4000, app(ReporteSueldosService::class)->obtener('2026-10', $this->alumno->deporte_id)['costo']);
        $this->assertSame(4000, app(ReporteSueldosService::class)->obtener('2026-11', $this->alumno->deporte_id)['costo']);
        $this->assertSame($antes, DB::table('reporte_analitico_historial')->orderBy('id')->get()->toJson());
    }

    public function test_adelanto_de_enero_cobrado_en_diciembre_tambien_aparece_en_evolucion(): void
    {
        $pago = $this->pago(['2027-01' => 100], '2026-12-15');
        $this->asiento($pago);
        Carbon::setTestNow('2027-02-15');
        $this->assertSame(0, $this->reporte('2026-12')['ingresos']);
        $this->assertSame(10000, $this->reporte('2027-01')['ingresos']);
        $evolucion = collect(app(ReporteMensualService::class)->evolucion('2027-02'))->keyBy('mes');
        $this->assertSame(10000, $evolucion['2027-01']['ingresos']);
    }
}
