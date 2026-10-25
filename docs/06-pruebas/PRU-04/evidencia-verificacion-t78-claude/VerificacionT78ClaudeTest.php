<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, MovimientoOperativo, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\CobranzaEstadoService;
use App\Support\FormatoImporte;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Verificación de T78 por Claude (25/10/2026). Casos que la entrega no cubre:
 * la misma persona en dos deportes, caja validada, motivo con marcado, alumno que ya
 * no es nuevo dentro de la gracia, y el profesor.
 * Uso: DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t78-claude/VerificacionT78ClaudeTest.php
 */
class VerificacionT78ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private GrupoPlan $plan;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('dias_plazo_alta', 7);
        Configuracion::set('inscripcion_importe', '5000');
        Carbon::setTestNow('2026-10-05 10:00:00');
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => Deporte::where('nombre', 'Fútbol')->firstOrFail()->id,
            'nivel_id' => Nivel::where('nombre', 'Principiantes')->firstOrFail()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 40000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(string $nombre, string $dni, string $alta, ?int $deporteId = null): Alumno
    {
        $a = Alumno::create(['apellido' => 'Verif', 'nombre' => $nombre, 'dni' => $dni,
            'deporte_id' => $deporteId ?? $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2012-05-10', 'celular' => '11-4000-0000', 'fecha_alta' => $alta, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $alta, 'activo' => true]);
        return $a;
    }

    private function cuota(Alumno $a, string $periodo): void
    {
        DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 40000, 'monto_pagado' => 0,
            'monto_condonado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function inscripcion(Alumno $a): void
    {
        CargoAlumno::create(['alumno_id' => $a->id, 'dni' => $a->dni, 'tipo' => 'INSCRIPCION', 'clave_origen' => 'inscripcion:dni:'.$a->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id, 'monto_original' => 5000,
            'monto_cobrado' => 0, 'monto_condonado' => 0, 'calculo' => ['fecha_ingreso' => $a->fecha_alta->toDateString()], 'estado' => 'VIGENTE']);
    }

    /** La inscripción es una por persona: la ficha de cada deporte dice lo mismo que su fila de Cobranza. */
    public function test_misma_persona_en_dos_deportes_la_ficha_coincide_con_cobranza_en_cada_una(): void
    {
        Carbon::setTestNow('2026-10-03 10:00:00');
        $futbol = $this->alumno('Uno', '41111111', '2026-10-01');
        $patin = $this->alumno('Dos', '41111111', '2026-10-01', Deporte::where('nombre', '!=', 'Fútbol')->firstOrFail()->id);
        $this->cuota($futbol, '2026-10');
        $this->cuota($patin, '2026-10');
        $this->inscripcion($futbol);

        $filas = app(CobranzaEstadoService::class)->listadoCobranza('TODOS')->keyBy('id');
        $this->assertSame(45000.0, (float) $filas[$futbol->id]->deuda_total);
        $this->assertSame(40000.0, (float) $filas[$patin->id]->deuda_total);

        foreach ([$futbol, $patin] as $a) {
            $this->actingAs($this->operativo)->get(route('web.alumnos.show', $a->id))->assertOk()
                ->assertSee('Total adeudado:')->assertSee(FormatoImporte::pesos((float) $filas[$a->id]->deuda_total));
        }
    }

    /** Hallazgo para Carlos: dentro de la gracia del mes, quien no es nuevo ve los días pero no la fecha. */
    public function test_alumno_que_no_es_nuevo_dentro_de_la_gracia_ve_dias_pero_no_fecha(): void
    {
        $lucas = $this->alumno('Lucas', '42222222', '2026-03-01');
        $this->cuota($lucas, '2026-10');

        $this->actingAs($this->operativo)->get(route('web.alumnos.show', $lucas->id))->assertOk()
            ->assertSee('En plazo')->assertSee('días de gracia')->assertSee('Total adeudado:')
            ->assertDontSee('Vence ');
    }

    /** Moroso: total sí, fecha de vencimiento no (ya venció). */
    public function test_moroso_muestra_total_sin_vencimiento(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $a = $this->alumno('Moroso', '43333333', '2026-03-01');
        $this->cuota($a, '2026-09');
        $this->cuota($a, '2026-10');

        $this->actingAs($this->admin)->get(route('web.alumnos.show', $a->id))->assertOk()
            ->assertSee('Total adeudado:')->assertSee(FormatoImporte::pesos(80000))->assertDontSee('Vence ');
    }

    /** Caja validada: el dueño y la operativa ven el motivo; un motivo con marcado se muestra como texto. */
    public function test_caja_validada_muestra_el_motivo_escapado(): void
    {
        $mp = TipoCaja::where('nombre', 'Mercado Pago')->firstOrFail();
        $caja = CajaDeclarada::crear($this->operativo->id, $mp->id);
        $a = $this->alumno('Caja', '44444444', '2026-03-01');
        $pago = Pago::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'mes' => 10, 'anio' => 2026, 'monto_base' => 40000,
            'porcentaje_aplicado' => 100, 'monto_final' => 40000, 'monto_cuota' => 40000, 'fecha_pago' => '2026-10-05', 'estado' => Pago::ESTADO_ANULADO]);
        $motivo = '<b>Medio</b> equivocado';
        MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'usuario_id' => $this->operativo->id, 'fecha' => '2026-10-05',
            'subrubro_id' => Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail()->id, 'tipo_caja_id' => $mp->id, 'alumno_id' => $a->id,
            'pago_id' => $pago->id, 'monto' => 40000, 'estado' => 'CANCELADO', 'cancelado_por_id' => $this->operativo->id,
            'cancelado_at' => now(), 'motivo_cancelacion' => $motivo]);
        $caja->update(['estado' => CajaOperativa::ESTADO_VALIDADA, 'cierre_at' => now(), 'efectivo_contado' => 0]);

        foreach ([$this->admin, $this->operativo] as $u) {
            $html = $this->actingAs($u)->get(route('web.caja.detalle', $caja->id))->assertOk()->getContent();
            $this->assertStringContainsString('Cancelado: &lt;b&gt;Medio&lt;/b&gt; equivocado', $html);
            $this->assertStringNotContainsString('<b>Medio</b>', $html);
        }
    }

    /** El profesor sigue sin entrar a la ficha (PERMISOS-ROLES). */
    public function test_profesor_no_ve_la_ficha(): void
    {
        $a = $this->alumno('Prof', '45555555', '2026-03-01');
        $profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $this->assertContains($this->actingAs($profesor)->get(route('web.alumnos.show', $a->id))->getStatusCode(), [302, 403]);
    }
}
