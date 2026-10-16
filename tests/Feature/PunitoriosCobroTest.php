<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Asistencia, CargoAlumno, CashflowMovimiento, Clase, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, MovimientoOperativo, Nivel, Pago, Profesor, Subrubro, TipoCaja, User};
use App\Services\{CajaService, CobranzaEstadoService, InscripcionService, LiquidacionService, PagoCuotaService, PunitorioService, ReciboService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PunitoriosCobroTest extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;
    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private DeudaCuota $cuota;
    private CargoAlumno $cargo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 10:00:00');
        Storage::fake();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $deporte = Deporte::create(['nombre' => 'Prueba Mora', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Prueba Mora']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Prueba Mora', 'dni' => '49000001',
            'fecha_nacimiento' => '2005-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2025-01-01',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2025-01-01', 'activo' => true]);
        $this->cuota = $this->crearCuota('2026-10');
        Configuracion::set('mora_porcentaje', '5');
        app(PunitorioService::class)->aplicar();
        $this->cargo = CargoAlumno::where('tipo', 'PUNITORIO')->sole();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearCuota(string $periodo): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo,
            'monto_original' => 10000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }

    private function cobrar(float $entregado = 10500, array $items = [], bool $operativo = false): array
    {
        $datos = ['alumno_id' => $this->alumno->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto_entregado' => $entregado, 'items' => $items ?: [['periodo' => '2026-10', 'monto' => 10000]]];
        return $operativo
            ? app(PagoCuotaService::class)->registrarPagoCuotaOperativo($datos + ['usuario_operativo_id' => $this->operativo->id])
            : app(PagoCuotaService::class)->registrarPagoCuotaAdmin($datos + ['usuario_admin_id' => $this->admin->id]);
    }

    public function test_pago_parcial_no_toca_recargo_y_cobro_completo_conserva_invariante(): void
    {
        $pago = $this->cobrar(4000)['pago'];
        $this->assertSame(4000.0, $pago->monto_cuota);
        $this->assertDatabaseCount('pago_cargo_alumno', 0);
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        $pago2 = $this->cobrar(6500, [['periodo' => '2026-10', 'monto' => 6000]])['pago'];
        $this->assertSame(6000.0, $pago2->monto_cuota);
        $this->assertSame('6500.00', $pago2->monto_final);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame('10000.00', $this->cuota->fresh()->monto_pagado);
        $this->assertEquals(10000, DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $this->cuota->id)->sum('monto_aplicado'));
        $this->assertDatabaseHas('cashflow_movimientos', ['referencia_id' => $pago2->id,
            'subrubro_id' => $this->cargo->subrubro_id, 'monto' => 500]);
    }

    public function test_orden_por_mes_es_cuota_recargo_y_siguiente_cuota(): void
    {
        Carbon::setTestNow('2026-11-11');
        $noviembre = $this->crearCuota('2026-11');
        app(PunitorioService::class)->aplicar();
        $pago = $this->cobrar(10700, [['periodo' => '2026-11', 'monto' => 10000], ['periodo' => '2026-10', 'monto' => 10000]])['pago'];
        $this->assertSame('10000.00', $this->cuota->fresh()->monto_pagado);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame('200.00', $noviembre->fresh()->monto_pagado);
        $this->assertSame(500.0, CargoAlumno::where('deuda_cuota_id', $noviembre->id)->sole()->saldo_pendiente);
        $this->assertSame(10200.0, $pago->monto_cuota);
    }

    public function test_no_salta_capital_con_importe_manipulado_para_cobrar_recargo(): void
    {
        try {
            $this->cobrar(600, [['periodo' => '2026-10', 'monto' => 100]]);
            $this->fail('Se cobró un recargo sin completar el capital.');
        } catch (\RuntimeException) {
            $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame('0.00', $this->cuota->fresh()->monto_pagado);
    }

    public function test_inscripcion_conserva_prioridad_sobre_cuota_y_recargo(): void
    {
        app(InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        $cargo = app(InscripcionService::class)->cargo($this->alumno->dni);
        $pago = $this->cobrar(6000)['pago'];
        $this->assertSame(0.0, $cargo->fresh()->saldo_pendiente);
        $this->assertSame(1000.0, $pago->monto_cuota);
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
    }

    public function test_recargo_solo_sigue_siendo_deuda_y_se_puede_cobrar_por_pantalla(): void
    {
        $this->cobrar(10000);
        $servicio = app(CobranzaEstadoService::class);
        $this->assertSame(500.0, $servicio->saldoDeAlumnos(collect([$this->alumno]))[$this->alumno->id]['total']);
        $this->assertSame('MOROSO', $servicio->estadoAlumno($this->alumno->id)['estado']);
        $this->assertSame('MOROSO', $servicio->estadosParaAlumnos(collect([$this->alumno]))[$this->alumno->id]);
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $this->alumno->id))
            ->assertOk()->assertSee('Cuota pagada')->assertSee('Recargo por mora — Octubre 2026');
        $this->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'], 'monto_entregado' => 500])->assertRedirect(route('web.caja.index'))->assertSessionHasNoErrors();
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame('AL_DIA', $servicio->estadoAlumno($this->alumno->id)['estado']);
        $this->assertSame(0.0, Pago::orderByDesc('id')->firstOrFail()->monto_cuota);
    }

    public function test_condonar_recargo_solo_no_limpia_capital_y_reactivar_conserva_historial(): void
    {
        Carbon::setTestNow('2026-11-01');
        $this->actingAs($this->admin)->post(route('web.punitorios.condonar', $this->cargo->id), ['motivo' => 'Motivo de prueba atendible'])
            ->assertRedirect(route('web.alumnos.show', $this->alumno->id));
        $this->assertSame('DEUDOR', app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']);
        $this->assertSame(10000.0, app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$this->alumno]))[$this->alumno->id]['total']);
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertOk()->assertSee(route('web.punitorios.reactivar', $this->cargo->id));
        $this->post(route('web.punitorios.reactivar', $this->cargo->id), ['motivo' => 'Reactivar por error de prueba'])->assertSessionHasNoErrors();
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertDatabaseCount('cargo_alumno_eventos', 3);
    }

    public function test_operativo_profesor_y_anonimo_no_pueden_perdonar_ni_reactivar(): void
    {
        $profesor = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);
        foreach ([$this->operativo, $profesor] as $usuario) {
            foreach (['condonar', 'reactivar'] as $accion) {
                $this->actingAs($usuario)->post(route('web.punitorios.'.$accion, $this->cargo->id), ['motivo' => 'Intento sin permisos válidos'])->assertForbidden();
            }
        }
        $this->app['auth']->forgetGuards();
        $this->post(route('web.punitorios.condonar', $this->cargo->id), ['motivo' => 'Intento sin sesión válida'])->assertRedirect(route('login'));
        $this->assertSame('0.00', $this->cargo->fresh()->monto_condonado);
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
    }

    public function test_cuatro_pantallas_dicen_mismo_total_incluso_debiendo_solo_recargo(): void
    {
        foreach ([10500.0, 500.0] as $esperado) {
            if ($esperado === 500.0) $this->cobrar(10000);
            $this->actingAs($this->admin);
            $this->get(route('web.cobranza.index'))->assertOk()
                ->assertViewHas('resumen', fn ($r) => (float) $r['total_adeudado'] === $esperado)
                ->assertViewHas('alumnos', fn ($a) => (float) $a->firstWhere('id', $this->alumno->id)->deuda_total === $esperado);
            $this->get(route('web.caja.cobrar-cuota'))->assertOk()->assertViewHas('saldos', fn ($s) => $s[$this->alumno->id]['total'] === $esperado);
            $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('reporte', fn ($r) => $r['deuda']['total'] === (int) round($esperado * 100));
            $this->get(route('web.reportes.index'))->assertOk()->assertSee('Recargo por mora — Octubre 2026')
                ->assertViewHas('reporte', fn ($r) => $r['deuda']['total'] === (int) round($esperado * 100));
        }
    }

    public function test_anular_cobro_admin_restituye_recargo_capital_y_plata(): void
    {
        $pago = $this->cobrar()['pago'];
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Anulación de prueba completa', $this->admin->id);
        $this->assertSame('0.00', $this->cuota->fresh()->monto_pagado);
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertDatabaseCount('pago_cargo_alumno', 0);
        $this->assertEquals(0, CashflowMovimiento::sum('monto'));
        $this->assertStringContainsString('Recargo por mora', $pago->fresh()->detalle_anulacion['cargos'][0]['periodo_texto']);
    }

    public function test_caja_operativa_cuadra_con_recargo_y_cancelacion_revierte_todo(): void
    {
        $caja = \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);
        $resultado = $this->cobrar(operativo: true);
        $this->assertEquals(10500, MovimientoOperativo::activos()->sum('monto'));
        $this->assertDatabaseHas('movimientos_operativos', ['pago_id' => $resultado['pago']->id, 'subrubro_id' => $this->cargo->subrubro_id, 'monto' => 500]);
        $arqueo = app(CajaService::class)->arqueoCaja($caja->id);
        $this->assertEquals(10500, $arqueo['efectivo_esperado']);
        app(PagoCuotaService::class)->cancelarCobroOperativo($resultado['movimiento']->id, 'Anulación de prueba operativa', $this->operativo->id);
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame('0.00', $this->cuota->fresh()->monto_pagado);
        $this->assertEquals(0, app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);
        $this->cobrar(operativo: true);
        $cerrada = app(CajaService::class)->cerrarCajaOperativa($caja->id, $this->operativo->id, false,
            ['efectivo_contado' => 10500, 'cambio_retenido' => 0]);
        $this->assertEquals(0, $cerrada->diferencia_efectivo);
    }

    public function test_recibo_separa_cuota_recargo_e_inscripcion_y_omite_recargo_condonado(): void
    {
        app(InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        $pago = $this->cobrar(15500)['pago'];
        $metodo = new \ReflectionMethod(ReciboService::class, 'obtenerPeriodosImputados');
        $filas = $metodo->invoke(app(ReciboService::class), $pago->fresh(['pagosDeuda.deudaCuota', 'cargos.deudaCuota']));
        $this->assertCount(3, $filas);
        $this->assertContains('Recargo por mora — Octubre 2026', array_column($filas, 'periodo_texto'));
        $this->assertContains('Inscripción al club — por única vez', array_column($filas, 'periodo_texto'));
        $ruta = app(ReciboService::class)->generarReciboCuota($pago->id);
        $this->assertStringStartsWith('%PDF-', Storage::get($ruta));
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Anular para probar el perdón', $this->admin->id);
        app(PunitorioService::class)->condonar($this->cargo->id, 'Perdón de prueba del recargo', $this->admin->id);
        $nuevo = $this->cobrar(15000)['pago'];
        $filas = $metodo->invoke(app(ReciboService::class), $nuevo->fresh(['pagosDeuda.deudaCuota', 'cargos']));
        $this->assertCount(2, $filas);
        $this->assertNotContains('Recargo por mora — Octubre 2026', array_column($filas, 'periodo_texto'));
    }

    public function test_comision_docente_solo_sobre_capital_realmente_cobrado(): void
    {
        $profesor = Profesor::create(['deporte_id' => $this->alumno->deporte_id, 'nombre' => 'Docente', 'apellido' => 'Prueba', 'dni' => '28000001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Domicilio de prueba', 'localidad' => 'Prueba',
            'tipo_liquidacion' => 'COMISION', 'porcentaje_comision' => 40, 'activo' => true]);
        $clase = Clase::create(['grupo_id' => $this->alumno->grupo_id, 'fecha' => '2026-10-10', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false]);
        $clase->profesores()->attach($profesor->id);
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        $this->cobrar();
        $liquidacion = app(LiquidacionService::class)->generarLiquidacionMensual($profesor->id, 10, 2026);
        $this->assertEquals(4000, $liquidacion->total_calculado);
    }

    public function test_importe_parcial_de_cuota_sin_total_entregado_no_cobra_recargo(): void
    {
        $pago = app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $this->alumno->id, 'tipo_caja_id' => $this->efectivo->id, 'usuario_admin_id' => $this->admin->id,
            'items' => [['periodo' => '2026-10', 'monto' => 4000]],
        ])['pago'];
        $this->assertSame('4000.00', $pago->monto_final);
        $this->assertSame(500.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertDatabaseCount('pago_cargo_alumno', 0);
    }

    public function test_cuota_condonada_conserva_recargo_y_admite_cobrarlo_solo(): void
    {
        app(PagoCuotaService::class)->condonarDeuda($this->cuota->id, 'Condonar solo la cuota por prueba', $this->admin->id);
        $this->assertSame(500.0, app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$this->alumno]))[$this->alumno->id]['total']);
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $this->alumno->id))->assertOk()->assertSee('Cuota condonada');
        $this->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'], 'monto_entregado' => 200])->assertSessionHas('success');
        $this->assertSame(300.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame('CONDONADA', $this->cuota->fresh()->estado);
        $this->assertSame('0.00', $this->cuota->fresh()->monto_pagado);
    }

    public function test_apagar_mora_no_borra_deuda_y_recargo_viejo_exige_aviso_al_cobrar_otro_mes(): void
    {
        $this->cobrar(10000);
        Configuracion::set('mora_porcentaje', '0');
        Carbon::setTestNow('2026-11-11');
        $this->crearCuota('2026-11');
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        $this->assertSame('DEUDOR', app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']);
        $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $this->alumno->id),
            ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => 10000]])
            ->assertStatus(409)->assertJsonPath('requiere_confirmacion', true)->assertJsonPath('monto_total', 500);
        $this->assertSame('0.00', DeudaCuota::where('periodo', '2026-11')->sole()->monto_pagado);
    }

    public function test_reporte_de_mes_cerrado_conserva_recargo_anterior_a_perdon_y_anulacion(): void
    {
        $pago = $this->cobrar(10200)['pago'];
        Carbon::setTestNow('2026-11-02');
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Reversión en el mes siguiente', $this->admin->id);
        app(PunitorioService::class)->condonar($this->cargo->id, 'Perdón después del cierre de mes', $this->admin->id);
        $octubre = app(ReporteMensualService::class)->obtener('2026-10');
        $this->assertSame(30000, $octubre['deuda']['punitorios']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-11')['deuda']['punitorios']);
    }

    public function test_porcentaje_cero_sin_cargos_conserva_pantallas_y_exporta_evidencia_a_pedido(): void
    {
        DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $this->cargo->id)->delete();
        $this->cargo->delete();
        Configuracion::set('mora_porcentaje', '0');
        $this->admin->update(['name' => 'Dueño de prueba', 'email' => 'duenio.b11@wings.test']);
        $this->actingAs($this->admin);
        $exportar = getenv('B11_EVIDENCIA') === '1';
        $directorio = base_path('docs/06-pruebas/PRU-04/evidencia-b11');
        $guardar = function (string $nombre, string $html) use ($exportar, $directorio) {
            if (!$exportar) return;
            if (!is_dir($directorio)) mkdir($directorio, 0775, true);
            $html = preg_replace('~https?://[^/"\s]+/(build/|images/|img/|icons/|favicon[^"\s]*|apple-touch-icon.png|site.webmanifest)~', '$1', $html);
            $html = preg_replace('/(name="(?:_token|csrf-token)"\s+(?:content|value)=")[^"]+/', '$1prueba-descartable', $html);
            file_put_contents($directorio.'/'.$nombre.'.html', $html);
        };
        $datosRecibo = null;
        \Illuminate\Support\Facades\View::composer('pdfs.recibo-cuota', function ($vista) use (&$datosRecibo) {
            $datosRecibo = [$vista->name(), $vista->getData()];
        });
        foreach (['0', '5'] as $porcentaje) {
            DB::beginTransaction();
            Configuracion::set('mora_porcentaje', $porcentaje);
            app(PunitorioService::class)->aplicar();
            foreach (['cobrar' => route('web.caja.cobrar', $this->alumno->id),
                'ficha' => route('web.alumnos.show', $this->alumno->id),
                'configuracion' => route('web.configuraciones.index')] as $pantalla => $url) {
                $respuesta = $this->get($url)->assertOk();
                if ($pantalla !== 'configuracion') {
                    if ($porcentaje === '0') $respuesta->assertDontSee('Recargo por mora');
                    else $respuesta->assertSee('Recargo por mora — Octubre 2026');
                }
                $guardar($pantalla.'-'.$porcentaje, $respuesta->getContent());
            }
            // Observar el HTML que el servicio real entrega a DomPDF, conservando el PDF real.
            $datosRecibo = null;
            $pago = $this->cobrar($porcentaje === '0' ? 10000 : 10500)['pago'];
            $ruta = app(ReciboService::class)->generarReciboCuota($pago->id, true);
            $this->assertStringStartsWith('%PDF-', Storage::get($ruta));
            $this->assertCount($porcentaje === '0' ? 1 : 2, $datosRecibo[1]['periodos']);
            $guardar('recibo-'.$porcentaje, view($datosRecibo[0], $datosRecibo[1])->render());
            if ($exportar) file_put_contents($directorio.'/recibo-'.$porcentaje.'.pdf', Storage::get($ruta));
            DB::rollBack();
        }
        $this->assertDatabaseCount('cargos_alumno', 0);
        $this->app['auth']->forgetGuards();
        $guardar('login-control', $this->get(route('login'))->assertOk()->getContent());
    }
}
