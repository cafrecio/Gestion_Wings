<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, ReglaPrimerPago, TipoCaja, User};
use App\Services\{CobranzaEstadoService, PagoCuotaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CuotaAltaEstadoTest extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;
    private User $usuario;
    private int $dni = 45000000;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-05 12:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->usuario = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->actingAs($this->usuario);
        $this->grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datos(string $fecha): array
    {
        return ['nombre' => 'Alta', 'apellido' => 'Cuota', 'dni' => (string) $this->dni++,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'fecha_alta' => $fecha,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'plan_id' => $this->plan->id];
    }

    private function alta(string $fecha): Alumno
    {
        Carbon::setTestNow($fecha.' 12:00:00');
        $datos = $this->datos($fecha);
        $this->post(route('web.alumnos.store'), $datos)->assertRedirect()->assertSessionHasNoErrors();
        return Alumno::where('dni', $datos['dni'])->firstOrFail();
    }

    public function test_alta_crea_cuota_con_los_tres_porcentajes_configurados(): void
    {
        foreach ([5 => 30000, 20 => 21000, 26 => 12000] as $dia => $importe) {
            $alumno = $this->alta(sprintf('2026-09-%02d', $dia));
            $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => $importe, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        }
        $this->assertDatabaseCount('deuda_cuotas', 3);
    }

    public function test_cobro_y_preview_respetan_importe_del_alta_aunque_cambien_reglas_y_precio(): void
    {
        $alumno = $this->alta('2026-09-20');
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->firstOrFail();
        ReglaPrimerPago::query()->update(['porcentaje' => 40]);
        $this->plan->update(['precio_mensual' => 90000]);
        $this->get(route('web.caja.cobrar', $alumno))->assertOk()->assertViewHas('alumno', function ($mostrado) {
            return (float) $mostrado->deudaCuotas->firstWhere('periodo', '2026-09')->monto_original === 21000.0;
        });
        $pago = app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id, 'usuario_operativo_id' => $this->usuario->id,
            'tipo_caja_id' => TipoCaja::first()->id, 'fecha_pago' => '2026-09-20',
            'items' => [['periodo' => '2026-09', 'monto' => 21000]],
        ])['pago'];
        $this->assertEquals(21000, $pago->monto_final);
        $this->assertEquals(21000, $deuda->fresh()->monto_original);
        $this->assertSame('PAGADA', $deuda->fresh()->estado);
    }

    public function test_alta_el_30_y_generacion_el_1_no_duplican_periodos(): void
    {
        $alumno = $this->alta('2026-09-30');
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => 12000]);
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        Pago::create(['alumno_id' => $alumno->id, 'mes' => 9, 'anio' => 2026, 'monto_base' => 12000, 'monto_final' => 12000, 'porcentaje_aplicado' => 100, 'fecha_pago' => '2026-09-30', 'estado' => 'COMPLETADO']);
        Carbon::setTestNow('2026-10-01 09:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-09')->count());
        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000]);
    }

    public function test_padron_conciliado_sin_pagos_y_contadores_coinciden(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $alumno = Alumno::create(array_diff_key($this->datos('2020-01-01'), ['plan_id' => true]) + ['activo' => true]);
            DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => 0, 'monto_pagado' => 0, 'estado' => 'PAGADA']);
            if ($i < 20) {
                DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-08', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
            }
        }
        $service = app(CobranzaEstadoService::class);
        $this->assertSame('AL_DIA', $service->estadoAlumno($alumno->id)['estado']);
        $this->assertSame('AL_DIA', $service->estadosParaAlumnos(collect([$alumno]))[$alumno->id]);
        $this->assertCount(40, $service->filtrarAlumnosPorEstado('AL_DIA'));
        $this->get(route('web.cobranza.index'))->assertOk()->assertViewHas('resumen', fn ($r) => $r['por_estado']['DEUDOR'] === 20 && $r['por_estado']['AL_DIA'] === 40);
        $this->get(route('web.operativo.dashboard'))->assertOk()->assertViewHas('alumnosConDeuda', 20);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_nuevo_sin_pagos_pasa_de_en_plazo_a_moroso_y_deudor(): void
    {
        $alumno = $this->alta('2026-09-05');
        $service = app(CobranzaEstadoService::class);
        foreach (['2026-09-05' => 'EN_PLAZO', '2026-09-11' => 'MOROSO', '2026-10-01' => 'DEUDOR'] as $fecha => $estado) {
            Carbon::setTestNow($fecha.' 12:00:00');
            $this->assertSame($estado, $service->estadoAlumno($alumno->id)['estado']);
            $this->assertSame($estado, $service->estadosParaAlumnos(collect([$alumno]))[$alumno->id]);
            $this->assertCount(1, $service->filtrarAlumnosPorEstado($estado));
            $this->assertSame(1, $service->resumenDashboard()['por_estado'][$estado]);
        }
    }

    public function test_mes_cerrado_impago_gana_sobre_corriente_pagado(): void
    {
        $alumno = $this->alta('2026-09-05');
        Carbon::setTestNow('2026-10-05 12:00:00');
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000, 'monto_pagado' => 30000, 'estado' => 'PAGADA']);
        $this->assertSame('DEUDOR', app(CobranzaEstadoService::class)->estadoAlumno($alumno->id)['estado']);
    }

    public function test_fallo_despues_de_insertar_cuota_revierte_toda_el_alta(): void
    {
        $evento = 'eloquent.created: '.DeudaCuota::class;
        Event::listen($evento, fn () => throw new \RuntimeException('Fallo controlado cuota alta'));
        $this->withoutExceptionHandling();
        try {
            $this->alta('2026-09-26');
            $this->fail('Debía intentar insertar la cuota y fallar');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo controlado cuota alta', $e->getMessage());
        } finally {
            Event::forget($evento);
        }
        foreach (['alumnos', 'alumno_planes', 'deuda_cuotas', 'cargos_alumno'] as $tabla) {
            $this->assertDatabaseCount($tabla, 0);
        }
    }

    public function test_reintento_no_duplica_y_alumnos_existentes_no_reciben_cuotas(): void
    {
        $viejo = Alumno::create(array_diff_key($this->datos('2020-01-01'), ['plan_id' => true]));
        $datos = $this->datos('2026-09-05') + ['alta_token' => '916eb125-1a08-44c8-a3e8-b8632c176cb7'];
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('alumnos', 2);
        $this->assertDatabaseCount('deuda_cuotas', 1);
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $viejo->id]);
    }

    public function test_porcentaje_configurable_dia_de_ingreso_y_solo_mes_actual(): void
    {
        ReglaPrimerPago::where('dia_desde', 16)->update(['porcentaje' => 65]);
        $datos = $this->datos('2020-01-20');
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $alumno = Alumno::where('dni', $datos['dni'])->firstOrFail();
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => 19500]);
        $this->assertDatabaseCount('deuda_cuotas', 1);
        $this->assertDatabaseMissing('deuda_cuotas', ['periodo' => '2020-01']);
    }

    public function test_parcial_anulacion_y_recobro_conservan_la_cuota_del_alta(): void
    {
        $alumno = $this->alta('2026-09-20');
        $service = app(PagoCuotaService::class);
        $datos = ['alumno_id' => $alumno->id, 'usuario_operativo_id' => $this->usuario->id,
            'tipo_caja_id' => TipoCaja::first()->id, 'fecha_pago' => '2026-09-20',
            'items' => [['periodo' => '2026-09', 'monto' => 6000]]];
        $resultado = $service->registrarPagoCuotaOperativo($datos);
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->firstOrFail();
        $this->assertEquals(15000, $deuda->saldo_pendiente);
        $service->cancelarCobroOperativo($resultado['movimiento']->id, 'Anulación de prueba cuota alta', $this->usuario->id);
        ReglaPrimerPago::query()->update(['porcentaje' => 40]);
        $this->plan->update(['precio_mensual' => 90000]);
        $datos['items'][0]['monto'] = 21000;
        $resultado = $service->registrarPagoCuotaOperativo($datos);
        $this->assertEquals(21000, $resultado['pago']->monto_cuota);
        $this->assertEquals(21000, $deuda->fresh()->monto_original);
        $this->assertSame('PAGADA', $deuda->fresh()->estado);
    }
}
