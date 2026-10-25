<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User};
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class CobrarMesSinDeudaT77Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $admin;
    private Alumno $alumno;
    private TipoCaja $medio;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 15:00:00');
        Storage::fake();
        $this->seed(CatalogosSeeder::class);
        $this->medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->medio->id);
        $deporte = Deporte::create(['nombre' => 'Patín T77', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T77']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2,
            'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'SinDeudaT77', 'dni' => '77000001',
            'fecha_nacimiento' => '2010-01-01', 'celular' => '1100000077',
            'fecha_alta' => '2026-01-01', 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id,
            'fecha_desde' => '2026-01-01', 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datos(bool $manual = false): array
    {
        return ['tipo_caja_id' => $this->medio->id, 'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 30000]] + ($manual ? ['periodos_manuales' => ['2026-10']] : []);
    }

    private function foto(): array
    {
        $foto = [];
        foreach (['deuda_cuotas', 'pagos', 'pago_deuda_cuota', 'movimientos_operativos',
            'cashflow_movimientos', 'alumnos_revision_cobranza'] as $tabla) {
            $foto[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        return $foto;
    }

    public function test_al_dia_sin_cuota_no_tiene_fila_pendiente_y_total_es_cero(): void
    {
        $antes = $this->foto();
        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))->assertOk();
        $this->assertSame('AL_DIA', app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']);
        $this->assertCount(0, $respuesta->viewData('alumno')->deudaCuotas);
        $respuesta->assertSee('Sin cuotas pendientes.');
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//input[@name="periodos[]"]')->length);
        $this->assertSame('$0.00', trim($xpath->evaluate('string(//p[normalize-space(.)="Total pendiente"]/following-sibling::p[1])')));
        $this->assertSame($antes, $this->foto(), 'Entrar a Cobrar no crea ni altera deuda.');
    }

    public function test_selector_incluye_actual_al_precio_del_plan_y_conserva_doce_futuros(): void
    {
        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))->assertOk();
        $meses = $respuesta->viewData('mesesAdelantables');
        $this->assertCount(13, $meses);
        $this->assertSame(['periodo' => '2026-10', 'etiqueta' => 'Octubre 2026', 'precio' => 30000.0], $meses->first());
        $this->assertSame('2027-10', $meses->last()['periodo']);
    }

    public function test_envio_del_mes_como_deuda_se_rechaza_sin_escribir_para_ambos_roles(): void
    {
        foreach ([$this->operativo, $this->admin] as $usuario) {
            $antes = $this->foto();
            $this->actingAs($usuario)->from(route('web.caja.cobrar', $this->alumno->id))
                ->post(route('web.caja.pagar', $this->alumno->id), $this->datos())
                ->assertSessionHas('error')->assertSessionMissing('success');
            $this->assertSame($antes, $this->foto());
        }
    }

    public function test_mes_elegido_manualmente_se_cobra_una_vez_y_el_segundo_envio_se_rechaza(): void
    {
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), $this->datos(true))
            ->assertSessionHas('success');
        $deuda = DeudaCuota::sole();
        $this->assertSame('2026-10', $deuda->periodo);
        $this->assertSame('PAGADA', $deuda->estado);
        $this->assertEquals(30000, $deuda->monto_original);
        $this->assertEquals(30000, $deuda->monto_pagado);
        $this->assertEquals(30000, Pago::sole()->monto_final);
        $this->assertEquals(30000, DB::table('movimientos_operativos')->sum('monto'));
        $this->assertSame(1, DB::table('pago_deuda_cuota')->count());
        $antes = $this->foto();
        foreach ([$this->operativo, $this->admin] as $usuario) {
            $this->actingAs($usuario)->post(route('web.caja.pagar', $this->alumno->id), $this->datos(true))
                ->assertSessionHas('error')->assertSessionMissing('success');
            $this->assertSame($antes, $this->foto());
        }
        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))->assertOk();
        $this->assertFalse($respuesta->viewData('mesesAdelantables')->contains('periodo', '2026-10'));
    }

    public function test_admin_cobra_mes_elegido_a_cashflow_sin_movimiento_operativo(): void
    {
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), $this->datos(true))
            ->assertSessionHas('success');
        $this->assertSame('PAGADA', DeudaCuota::sole()->estado);
        $this->assertEquals(30000, DB::table('cashflow_movimientos')->sum('monto'));
        $this->assertSame(0, DB::table('movimientos_operativos')->count());
    }

    public function test_alumno_que_debe_octubre_sigue_viendolo_y_pagandolo_como_deuda(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10',
            'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))->assertOk();
        $this->assertCount(1, $respuesta->viewData('alumno')->deudaCuotas);
        $this->assertFalse($respuesta->viewData('mesesAdelantables')->contains('periodo', '2026-10'));
        $respuesta->assertSee('value="2026-10"', false)->assertSee('$30,000.00');
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), $this->datos())
            ->assertSessionHas('success');
        $this->assertSame('PAGADA', DeudaCuota::sole()->estado);
    }

    public function test_en_revision_el_cobro_manual_resuelve_la_cola(): void
    {
        $revision = AlumnoRevisionCobranza::create(['alumno_id' => $this->alumno->id,
            'periodo_objetivo' => '2026-10', 'motivo' => 'Sin asistencia', 'estado_revision' => 'PENDIENTE']);
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))->assertSee('Sin cuotas pendientes.');
        $this->assertSame('PENDIENTE', $revision->fresh()->estado_revision);
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), $this->datos(true))
            ->assertSessionHas('success');
        $this->assertSame('RESUELTO', $revision->fresh()->estado_revision);
        $this->assertSame('REACTIVADO_AUTO', $revision->fresh()->resolucion);
    }

    public function test_eleccion_manual_sin_importe_no_crea_cuota(): void
    {
        $datos = $this->datos(true);
        unset($datos['montos_cuota']);
        $antes = $this->foto();
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), $datos)
            ->assertSessionHas('error');
        $this->assertSame($antes, $this->foto());
    }

    public function test_cobranza_buscador_ficha_e_inicio_conservan_solo_deuda_guardada(): void
    {
        $servicio = app(CobranzaEstadoService::class);
        $this->assertEquals(0, $servicio->saldoDeAlumnos(collect([$this->alumno]))[$this->alumno->id]['total']);
        $this->assertEquals(0, $servicio->resumenDashboard()['total_adeudado']);
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk()->assertDontSee('SinDeudaT77, Prueba');
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'SinDeudaT77']))
            ->assertOk()->assertSee('SinDeudaT77, Prueba');
        $ficha = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id))->assertOk();
        $this->assertSame('AL_DIA', $ficha->viewData('estadoCobranza')['estado']);
        $this->assertCount(0, $ficha->viewData('alumno')->deudaCuotas);
        $cobranza = $this->actingAs($this->operativo)->get(route('web.cobranza.index'))->assertOk();
        $this->assertEquals(0, $cobranza->viewData('resumen')['total_adeudado']);
        $inicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(0, $inicio->viewData('reporte')['deuda']['total']);
        $operativo = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();
        $this->assertSame(0, $operativo->viewData('vencidos'));
        $this->assertSame(0, $operativo->viewData('enPlazo'));
        $this->assertSame(0, DeudaCuota::count());
    }
}
