<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Verificación independiente de T26 — Historial de caja del dueño (commit f4a030e).
 *
 * Reglas evaluadas según decisión de Carlos (11/10/2026):
 * - El dueño no tiene límites de fecha: sin fecha arranca en 90 días, con "desde" puede ir a cualquier fecha.
 *   La operativa queda siempre acotada a los últimos 90 días, aun forzando fechas anteriores en el querystring.
 * - El dueño ve todos los subrubros (incluidos los exclusivos de ADMIN) en la grilla y en el select de filtros.
 *   La operativa solo ve los permitidos para OPERATIVO.
 * - Los movimientos cancelados: el dueño los ve marcados (· Cancelado y tachado), la operativa no los ve.
 *   En ambos roles los totales (ingresos/egresos) excluyen los cancelados.
 * - Cobro del dueño sin caja y anulado por el camino real de la aplicación: aparecen ambos asientos en el historial,
 *   la anulación resta como asiento en contra y el total neto de ingresos da 0.
 * - La profesora no tiene acceso (403).
 * - Filtros (búsqueda de texto, subrubro, tipo INGRESO/EGRESO, rango de fechas) y paginación.
 * - Casos no cubiertos por Claude:
 *   1) Caja operativa cerrada y validada por admin (movimientos reflejados en Cashflow con REF_CAJA)
 *      NO duplica movimientos en el historial.
 *   2) Los totales de ingresos y egresos recalculan dinámicamente con filtros aplicados.
 */
class VerificacionT26Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesora;
    private TipoCaja $tipoEfectivo;
    private Subrubro $subrubroCuota;
    private Subrubro $subrubroAdminSolo;
    private Subrubro $subrubroEgresoOperativo;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-12 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'name' => 'Carlos Admin']);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra Operativa']);
        $this->profesora = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true, 'name' => 'Valeria Profe']);

        $this->tipoEfectivo = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);

        $rubroIngresos = Rubro::firstOrCreate(['nombre' => 'Cuotas', 'tipo' => 'INGRESO'], ['observacion' => '']);
        $rubroEgresos = Rubro::firstOrCreate(['nombre' => 'Gastos Generales', 'tipo' => 'EGRESO'], ['observacion' => '']);

        $this->subrubroCuota = Subrubro::firstOrCreate(
            ['nombre' => 'Cuota Mensual'],
            [
                'rubro_id' => $rubroIngresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
            ]
        );

        $this->subrubroAdminSolo = Subrubro::firstOrCreate(
            ['nombre' => 'Retiro de Utilidades Dueño'],
            [
                'rubro_id' => $rubroEgresos->id,
                'permitido_para' => User::ROL_ADMIN,
                'afecta_caja' => true,
            ]
        );

        $this->subrubroEgresoOperativo = Subrubro::firstOrCreate(
            ['nombre' => 'Artículos de Limpieza'],
            [
                'rubro_id' => $rubroEgresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Límite de fechas: el dueño ve más allá de 90 días pidiéndolo; la operativa no,
     * aun forzando la fecha en la URL. Sin fecha pedida, el dueño arranca en los últimos 90 días.
     */
    public function test_01_duenio_encuentra_movimiento_mas_de_90_dias_y_operativa_queda_acotada(): void
    {
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        // Movimiento de hace 130 días (> 90 días)
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->subDays(130)->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 38000,
            'observaciones' => 'Cuota antigua de hace 130 dias',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Movimiento reciente (hace 20 días)
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->subDays(20)->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 42000,
            'observaciones' => 'Cuota reciente de hace 20 dias',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $desdeHistorico = today()->subDays(180)->toDateString();

        // 1.1 Dueño sin fecha pedida: arranca en últimos 90 días, NO ve el de 130 días
        $resAdminSinFiltro = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $obsAdminSinFiltro = $resAdminSinFiltro->viewData('filas')->pluck('obs')->all();
        $this->assertContains('Cuota reciente de hace 20 dias', $obsAdminSinFiltro);
        $this->assertNotContains('Cuota antigua de hace 130 dias', $obsAdminSinFiltro);

        // 1.2 Dueño pidiendo fecha anterior a 90 días: SÍ ve el de 130 días
        $resAdminConDesde = $this->actingAs($this->admin)->get(route('web.caja.historial', ['desde' => $desdeHistorico]))->assertOk();
        $obsAdminConDesde = $resAdminConDesde->viewData('filas')->pluck('obs')->all();
        $this->assertContains('Cuota antigua de hace 130 dias', $obsAdminConDesde);
        $this->assertContains('Cuota reciente de hace 20 dias', $obsAdminConDesde);

        // 1.3 Operativa pidiendo la misma fecha anterior: el controlador la acota a 90 días y NO ve el de 130 días
        $resOperativoConDesde = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['desde' => $desdeHistorico]))->assertOk();
        $obsOperativoConDesde = $resOperativoConDesde->viewData('filas')->pluck('obs')->all();
        $this->assertNotContains('Cuota antigua de hace 130 dias', $obsOperativoConDesde);
        $this->assertContains('Cuota reciente de hace 20 dias', $obsOperativoConDesde);

        // 1.4 Operativa forzando un rango que está 100% en el pasado (>90 días)
        $resOperativoRangoViejo = $this->actingAs($this->operativo)->get(route('web.caja.historial', [
            'desde' => today()->subDays(200)->toDateString(),
            'hasta' => today()->subDays(100)->toDateString(),
        ]))->assertOk();
        $this->assertCount(0, $resOperativoRangoViejo->viewData('filas'), 'Operativa forzando rango antiguo no debe ver filas');
    }

    /**
     * 2. Rubros: el dueño ve subrubros exclusivos de ADMIN en la lista y en el filtro;
     * la operativa no ve el movimiento ni el subrubro en el select.
     */
    public function test_02_duenio_ve_rubros_solo_suyos_y_operativa_no_los_ve_ni_en_filtro_ni_en_lista(): void
    {
        // Movimiento cashflow directo del dueño con subrubro solo de ADMIN
        CashflowMovimiento::create([
            'fecha' => today()->toDateString(),
            'subrubro_id' => $this->subrubroAdminSolo->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'monto' => -150000,
            'observaciones' => 'Retiro personal del dueño de octubre',
            'usuario_admin_id' => $this->admin->id,
            'referencia_tipo' => null,
            'referencia_id' => null,
        ]);

        // Movimiento común permitido para OPERATIVO
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 4500,
            'observaciones' => 'Compra de lavandina y trapos',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Consulta ADMIN
        $resAdmin = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $filasAdmin = $resAdmin->viewData('filas')->pluck('obs')->all();
        $subrubrosAdmin = $resAdmin->viewData('subrubros')->pluck('nombre')->all();

        $this->assertContains('Retiro personal del dueño de octubre', $filasAdmin);
        $this->assertContains('Retiro de Utilidades Dueño', $subrubrosAdmin);
        $resAdmin->assertSee('Retiro de Utilidades Dueño');

        // Consulta OPERATIVO
        $resOperativo = $this->actingAs($this->operativo)->get(route('web.caja.historial'))->assertOk();
        $filasOperativo = $resOperativo->viewData('filas')->pluck('obs')->all();
        $subrubrosOperativo = $resOperativo->viewData('subrubros')->pluck('nombre')->all();

        $this->assertNotContains('Retiro personal del dueño de octubre', $filasOperativo);
        $this->assertNotContains('Retiro de Utilidades Dueño', $subrubrosOperativo);
        $resOperativo->assertDontSee('Retiro personal del dueño de octubre');
        $resOperativo->assertDontSee('Retiro de Utilidades Dueño');
    }

    /**
     * 3. Movimientos cancelados: el dueño los ve marcados (· Cancelado y tachados),
     * la operativa no los ve en absoluto, y en ningún caso suman en los totales.
     */
    public function test_03_movimiento_cancelado_duenio_lo_ve_tachado_y_operativa_no_lo_ve_totales_no_lo_suman(): void
    {
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        // Ingreso activo: $35.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 35000,
            'observaciones' => 'Cobro activo de cuota',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Ingreso cancelado: $15.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 15000,
            'observaciones' => 'Cobro cancelado por error de importe',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_CANCELADO,
        ]);

        // Egreso activo: $12.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 12000,
            'observaciones' => 'Gasto de limpieza activo',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Egreso cancelado: $8.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 8000,
            'observaciones' => 'Gasto cancelado duplicado',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_CANCELADO,
        ]);

        // Verificación ADMIN
        $resAdmin = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $filasAdmin = $resAdmin->viewData('filas');
        $this->assertSame(4, $filasAdmin->total(), 'El dueño debe ver los 4 movimientos (activos y cancelados)');

        $canceladoIngreso = $filasAdmin->firstWhere('obs', 'Cobro cancelado por error de importe');
        $this->assertNotNull($canceladoIngreso);
        $this->assertTrue($canceladoIngreso->cancelado);

        $canceladoEgreso = $filasAdmin->firstWhere('obs', 'Gasto cancelado duplicado');
        $this->assertNotNull($canceladoEgreso);
        $this->assertTrue($canceladoEgreso->cancelado);

        $resAdmin->assertSee('· Cancelado');
        $resAdmin->assertSee('text-decoration:line-through;', false);

        // Totales ADMIN: sólo deben sumar los activos (Ingresos: 35.000, Egresos: 12.000)
        $this->assertEquals(35000.0, $resAdmin->viewData('totalIngresos'));
        $this->assertEquals(12000.0, $resAdmin->viewData('totalEgresos'));

        // Verificación OPERATIVO
        $resOperativo = $this->actingAs($this->operativo)->get(route('web.caja.historial'))->assertOk();
        $filasOperativo = $resOperativo->viewData('filas');
        $this->assertSame(2, $filasOperativo->total(), 'La operativa solo debe ver los 2 movimientos activos');

        $this->assertNull($filasOperativo->firstWhere('obs', 'Cobro cancelado por error de importe'));
        $this->assertNull($filasOperativo->firstWhere('obs', 'Gasto cancelado duplicado'));

        $resOperativo->assertDontSee('· Cancelado');
        $resOperativo->assertDontSee('text-decoration:line-through;', false);

        // Totales OPERATIVO: iguales a los del dueño (no cuentan cancelados)
        $this->assertEquals(35000.0, $resOperativo->viewData('totalIngresos'));
        $this->assertEquals(12000.0, $resOperativo->viewData('totalEgresos'));
    }

    /**
     * 4. Cobro del dueño sin caja y anulado por el camino real de la aplicación:
     * Aparecen ambos renglones en el historial, y los ingresos netos de ese cobro dan cero.
     */
    public function test_04_cobro_duenio_sin_caja_y_anulado_camino_real_dos_renglones_ingresos_netos_cero(): void
    {
        $this->seed(CatalogosSeeder::class);

        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Tenis',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Intermedio']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 48000.0,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'apellido' => 'Gomez',
            'nombre' => 'Martina',
            'dni' => '35111222',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2004-05-10',
            'celular' => '11-5555-1234',
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-10-01',
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 48000.0,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Camino real 1: El admin cobra por web (sin caja abierta)
        $resPago = $this->actingAs($this->admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 48000.0],
            'fecha_pago' => '2026-10-12',
            'observaciones' => 'Cobro directo del dueño en administración',
        ]);
        $resPago->assertSessionHas('success');

        $pago = Pago::where('alumno_id', $alumno->id)->sole();
        $this->assertSame('COMPLETADO', $pago->estado);

        // Camino real 2: El admin anula el cobro por web
        $resAnular = $this->actingAs($this->admin)->post(route('web.pagos.anular', $pago->id), [
            'motivo' => 'Cobro duplicado por error de carga en sistema',
        ]);
        $resAnular->assertSessionHas('success');
        $this->assertSame('ANULADO', $pago->fresh()->estado);

        // Verificar Historial de caja para el dueño
        $resHistorialAdmin = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $filasAdmin = $resHistorialAdmin->viewData('filas');

        $this->assertSame(2, $filasAdmin->total(), 'Deben aparecer dos renglones: el cobro y la anulación');

        // Ambos renglones deben identificar a la alumna
        foreach ($filasAdmin as $f) {
            $this->assertSame('Gomez, Martina', $f->alumno);
        }

        $filaCobro = $filasAdmin->firstWhere('neto', 48000.0);
        $filaAnulacion = $filasAdmin->firstWhere('neto', -48000.0);

        $this->assertNotNull($filaCobro, 'Debe existir la fila del cobro con neto positivo');
        $this->assertNotNull($filaAnulacion, 'Debe existir la fila de la anulación con neto negativo');

        // Ingresos netos del dueño deben ser $0.00
        $this->assertEquals(0.0, $resHistorialAdmin->viewData('totalIngresos'));
        $this->assertEquals(0.0, $resHistorialAdmin->viewData('totalEgresos'));

        // HTML debe mostrar el cobro como +$48.000 y la anulación como −$48.000
        $resHistorialAdmin->assertSee('+$48.000', false);
        $resHistorialAdmin->assertSee('−$48.000', false);

        // Verificar que la operativa también ve ambos asientos y el total neto da cero
        $resHistorialOperativo = $this->actingAs($this->operativo)->get(route('web.caja.historial'))->assertOk();
        $this->assertSame(2, $resHistorialOperativo->viewData('filas')->total());
        $this->assertEquals(0.0, $resHistorialOperativo->viewData('totalIngresos'));
    }

    /**
     * 5. La profesora no entra al historial de caja (403 Forbidden).
     */
    public function test_05_profesora_no_tiene_acceso_a_historial_caja(): void
    {
        $this->actingAs($this->profesora)
            ->get(route('web.caja.historial'))
            ->assertStatus(403);
    }

    /**
     * 6. Filtros: texto, rubro, ingreso o egreso, fechas y paginación para ambos roles.
     */
    public function test_06_filtros_busqueda_subrubro_tipo_fechas_y_paginacion(): void
    {
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::first() ?? Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $alumno1 = Alumno::create([
            'nombre' => 'Lucas',
            'apellido' => 'Bernal',
            'dni' => '40111222',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0001',
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);
        $alumno2 = Alumno::create([
            'nombre' => 'Camila',
            'apellido' => 'Sosa',
            'dni' => '40333444',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2006-02-02',
            'celular' => '11-4000-0002',
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);

        // Item A: Ingreso, Alumno Bernal, obs 'Pago cuota adelantada'
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-05',
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'alumno_id' => $alumno1->id,
            'monto' => 30000,
            'observaciones' => 'Pago cuota adelantada de Lucas',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Item B: Ingreso, Alumno Sosa, obs 'Pago mensual regular'
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-08',
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'alumno_id' => $alumno2->id,
            'monto' => 25000,
            'observaciones' => 'Pago mensual regular de Camila',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Item C: Egreso, obs 'Compra de insumos sanitizantes'
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-10',
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 5000,
            'observaciones' => 'Compra de insumos sanitizantes',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // 6.1 Búsqueda por texto (nombre de alumno)
        $resSearchAlumno = $this->actingAs($this->admin)->get(route('web.caja.historial', ['search' => 'Bernal']))->assertOk();
        $this->assertCount(1, $resSearchAlumno->viewData('filas'));
        $this->assertSame('Bernal, Lucas', $resSearchAlumno->viewData('filas')->first()->alumno);

        // 6.2 Búsqueda por texto (observación)
        $resSearchObs = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['search' => 'sanitizantes']))->assertOk();
        $this->assertCount(1, $resSearchObs->viewData('filas'));
        $this->assertSame('Compra de insumos sanitizantes', $resSearchObs->viewData('filas')->first()->obs);

        // 6.3 Filtro por subrubro
        $resSubrubro = $this->actingAs($this->admin)->get(route('web.caja.historial', ['subrubro_id' => $this->subrubroEgresoOperativo->id]))->assertOk();
        $this->assertCount(1, $resSubrubro->viewData('filas'));
        $this->assertSame('Artículos de Limpieza', $resSubrubro->viewData('filas')->first()->subrubro);

        // 6.4 Filtro por tipo: INGRESO vs EGRESO
        $resTipoIngreso = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['tipo' => 'INGRESO']))->assertOk();
        $this->assertCount(2, $resTipoIngreso->viewData('filas'));

        $resTipoEgreso = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['tipo' => 'EGRESO']))->assertOk();
        $this->assertCount(1, $resTipoEgreso->viewData('filas'));

        // 6.5 Filtro por fechas
        $resFechas = $this->actingAs($this->admin)->get(route('web.caja.historial', [
            'desde' => '2026-10-07',
            'hasta' => '2026-10-09',
        ]))->assertOk();
        $this->assertCount(1, $resFechas->viewData('filas'));
        $this->assertSame('Pago mensual regular de Camila', $resFechas->viewData('filas')->first()->obs);

        // 6.6 Paginación: agregar 32 movimientos más para superar la página de 30
        for ($i = 1; $i <= 32; $i++) {
            MovimientoOperativo::create([
                'caja_operativa_id' => $caja->id,
                'fecha' => '2026-10-11',
                'tipo_caja_id' => $this->tipoEfectivo->id,
                'subrubro_id' => $this->subrubroCuota->id,
                'monto' => 1000 + $i,
                'observaciones' => "Movimiento extra {$i} para paginacion",
                'usuario_id' => $this->operativo->id,
                'estado' => MovimientoOperativo::ESTADO_ACTIVO,
            ]);
        }

        // Total: 3 originales + 32 extras = 35 movimientos
        $resPagina1 = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['page' => 1]))->assertOk();
        $this->assertSame(35, $resPagina1->viewData('filas')->total());
        $this->assertCount(30, $resPagina1->viewData('filas'));
        $this->assertTrue($resPagina1->viewData('filas')->hasPages());

        $resPagina2 = $this->actingAs($this->operativo)->get(route('web.caja.historial', ['page' => 2]))->assertOk();
        $this->assertCount(5, $resPagina2->viewData('filas'));
    }

    /**
     * 7. Caso borde: Una caja operativa cerrada y validada por admin
     * copia sus movimientos a Cashflow con referencia REF_CAJA.
     * En el historial NO deben salir duplicados.
     */
    public function test_07_caja_validada_con_movimientos_en_cashflow_no_duplica_en_historial(): void
    {
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        // Movimiento 1 en caja: Ingreso $25.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 25000,
            'observaciones' => 'Cobro en mostrador de turno tarde',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Movimiento 2 en caja: Egreso $10.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 10000,
            'observaciones' => 'Gasto de papelería turno tarde',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Cierre de caja por el operativo
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 15000;
        $caja->save();

        // Validación de caja por el admin (dispara reflejarCajaEnCashflow)
        app(CajaService::class)->validarCaja($caja->id, $this->admin->id);

        $this->assertSame(CajaOperativa::ESTADO_VALIDADA, $caja->fresh()->estado);

        // Verificar que CashflowMovimiento tiene los asientos reflejados con REF_CAJA
        $asientosReflejados = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->where('referencia_id', $caja->id)
            ->get();
        $this->assertCount(2, $asientosReflejados, 'Deben existir 2 asientos reflejados en cashflow');

        // Consultar Historial para ADMIN: no debe duplicar (sigue habiendo exactamente 2 filas)
        $resAdmin = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $this->assertSame(2, $resAdmin->viewData('filas')->total(), 'No debe duplicar movimientos de caja validada');
        $this->assertEquals(25000.0, $resAdmin->viewData('totalIngresos'));
        $this->assertEquals(10000.0, $resAdmin->viewData('totalEgresos'));

        // Consultar Historial para OPERATIVO: tampoco debe duplicar
        $resOperativo = $this->actingAs($this->operativo)->get(route('web.caja.historial'))->assertOk();
        $this->assertSame(2, $resOperativo->viewData('filas')->total());
        $this->assertEquals(25000.0, $resOperativo->viewData('totalIngresos'));
        $this->assertEquals(10000.0, $resOperativo->viewData('totalEgresos'));
    }

    /**
     * 8. Caso borde: Los totales de ingresos y egresos recalculan dinámicamente
     * reflejando con exactitud los filtros aplicados.
     */
    public function test_08_totales_recalculan_exactamente_con_filtros_aplicados(): void
    {
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        // Subrubro Cuota: Activo $10.000, Cancelado $15.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 10000,
            'observaciones' => 'Cuota activa alfa',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroCuota->id,
            'monto' => 15000,
            'observaciones' => 'Cuota cancelada beta',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_CANCELADO,
        ]);

        // Otro subrubro de Ingreso: Torneo $20.000
        $subrubroTorneo = Subrubro::create([
            'rubro_id' => $this->subrubroCuota->rubro_id,
            'nombre' => 'Inscripción Torneo',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
        ]);
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subrubroTorneo->id,
            'monto' => 20000,
            'observaciones' => 'Inscripción torneo gamma',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Subrubro Egreso: Limpieza $7.000
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today()->toDateString(),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroEgresoOperativo->id,
            'monto' => 7000,
            'observaciones' => 'Artículos limpieza delta',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Sin filtros: Ingresos = 10.000 + 20.000 = 30.000. Egresos = 7.000.
        $resSinFiltros = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $this->assertEquals(30000.0, $resSinFiltros->viewData('totalIngresos'));
        $this->assertEquals(7000.0, $resSinFiltros->viewData('totalEgresos'));

        // Filtro por subrubro Cuota: solo debe contar los $10.000 de la cuota activa
        $resFiltroCuota = $this->actingAs($this->admin)->get(route('web.caja.historial', [
            'subrubro_id' => $this->subrubroCuota->id,
        ]))->assertOk();
        $this->assertEquals(10000.0, $resFiltroCuota->viewData('totalIngresos'));
        $this->assertEquals(0.0, $resFiltroCuota->viewData('totalEgresos'));

        // Filtro por subrubro Torneo: solo debe contar los $20.000
        $resFiltroTorneo = $this->actingAs($this->admin)->get(route('web.caja.historial', [
            'subrubro_id' => $subrubroTorneo->id,
        ]))->assertOk();
        $this->assertEquals(20000.0, $resFiltroTorneo->viewData('totalIngresos'));
        $this->assertEquals(0.0, $resFiltroTorneo->viewData('totalEgresos'));

        // Filtro por tipo EGRESO: Ingresos = 0, Egresos = 7.000
        $resFiltroEgreso = $this->actingAs($this->admin)->get(route('web.caja.historial', [
            'tipo' => 'EGRESO',
        ]))->assertOk();
        $this->assertEquals(0.0, $resFiltroEgreso->viewData('totalIngresos'));
        $this->assertEquals(7000.0, $resFiltroEgreso->viewData('totalEgresos'));

        // Filtro por búsqueda de texto 'gamma': Ingresos = 20.000, Egresos = 0
        $resFiltroTexto = $this->actingAs($this->admin)->get(route('web.caja.historial', [
            'search' => 'gamma',
        ]))->assertOk();
        $this->assertEquals(20000.0, $resFiltroTexto->viewData('totalIngresos'));
        $this->assertEquals(0.0, $resFiltroTexto->viewData('totalEgresos'));
    }
}
