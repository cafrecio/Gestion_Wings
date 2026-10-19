<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificación independiente de T56 (Claude CAB, 19/10). Fuera de tests/ a propósito:
 * no cambia el contador. Uso:
 *   DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t56-claude/VerificacionT56ClaudeTest.php
 */
class VerificacionT56ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private Grupo $g1;
    private Grupo $g2;
    private GrupoPlan $p1;
    private GrupoPlan $p2;
    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private int $dni = 56000001;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);
        $deporte = Deporte::first();
        $niveles = Nivel::all();
        $this->g1 = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $niveles[0]->id, 'activo' => true]);
        $this->g2 = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $niveles[1]->id, 'activo' => true]);
        $this->p1 = GrupoPlan::create(['grupo_id' => $this->g1->id, 'clases_por_semana' => 2, 'precio_mensual' => 20000, 'activo' => true]);
        $this->p2 = GrupoPlan::create(['grupo_id' => $this->g2->id, 'clases_por_semana' => 3, 'precio_mensual' => 30000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cobro_de_la_operativa_a_inactivo_sin_plan_cobra_y_no_lo_reactiva(): void
    {
        $alumno = $this->alumno(activo: false);
        $alumno->planActivo->update(['fecha_hasta' => '2026-09-30']);
        $this->deuda($alumno, '2026-09');

        $this->cobrarOperativo($alumno, '2026-09', 20000);

        $this->assertSame(DeudaCuota::ESTADO_PAGADA, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-09')->value('estado'));
        $this->assertFalse((bool) $alumno->fresh()->activo);
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10']);
        $this->assertSame(0, Alumno::where('activo', true)->get()
            ->filter(fn ($a) => !app(PagoCuotaService::class)->obtenerPlanParaPeriodo($a->id, '2026-10')?->plan)->count());
    }

    public function test_cobro_de_la_operativa_a_inactivo_con_plan_lo_reactiva_como_en_t38(): void
    {
        $alumno = $this->alumno(activo: false);
        $this->deuda($alumno, '2026-09');

        $this->cobrarOperativo($alumno, '2026-09', 20000);

        $this->assertTrue((bool) $alumno->fresh()->activo);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10']);
    }

    public function test_cambiar_de_grupo_deja_plan_en_cada_mes_y_el_nuevo_rige_hacia_adelante(): void
    {
        $alumno = $this->alumno();
        // Octubre ya pagado: el cambio rige desde noviembre (regla de cambio de plan).
        $this->deuda($alumno, '2026-10', pagado: 20000, estado: DeudaCuota::ESTADO_PAGADA);

        $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno->id), $this->datos($alumno, $this->g2->id, $this->p2->id))
            ->assertSessionHasNoErrors();

        $servicio = app(PagoCuotaService::class);
        $this->assertSame($this->g2->id, $alumno->fresh()->grupo_id);
        $this->assertNotNull($servicio->obtenerPlanParaPeriodo($alumno->id, '2026-10')?->plan, 'Octubre no puede quedar sin plan');
        $this->assertSame($this->p2->id, $servicio->obtenerPlanParaPeriodo($alumno->id, '2026-11')?->plan_id, 'Desde noviembre, el plan del grupo nuevo');
    }

    public function test_sin_cambiar_de_grupo_no_acepta_un_plan_ajeno_y_sin_plan_guarda_igual(): void
    {
        $alumno = $this->alumno();

        $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno->id), $this->datos($alumno, $this->g1->id, $this->p2->id))
            ->assertSessionHasErrors('plan_id');
        $this->assertSame($this->p1->id, $alumno->fresh()->planActivo->plan_id);

        $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno->id), array_merge($this->datos($alumno, $this->g1->id, null), ['nombre' => 'Cambiado']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Cambiado', $alumno->fresh()->nombre);
        $this->assertSame($this->p1->id, $alumno->fresh()->planActivo->plan_id);
    }

    public function test_la_operativa_tambien_tiene_que_elegir_el_plan_al_cambiar_de_grupo(): void
    {
        $alumno = $this->alumno();

        $this->actingAs($this->operativo)->put(route('web.alumnos.update', $alumno->id), $this->datos($alumno, $this->g2->id, null))
            ->assertSessionHasErrors('plan_id');
        $this->assertSame($this->g1->id, $alumno->fresh()->grupo_id);
    }

    private function alumno(bool $activo = true): Alumno
    {
        $a = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Verifica', 'dni' => (string) $this->dni++,
            'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-09-01', 'deporte_id' => $this->g1->deporte_id,
            'grupo_id' => $this->g1->id, 'celular' => '1144556677', 'activo' => $activo]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->p1->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        return $a->fresh();
    }

    private function deuda(Alumno $a, string $periodo, float $pagado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE): void
    {
        DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 20000,
            'monto_pagado' => $pagado, 'monto_condonado' => 0, 'estado' => $estado]);
    }

    private function cobrarOperativo(Alumno $a, string $periodo, float $monto): void
    {
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo(['alumno_id' => $a->id,
            'tipo_caja_id' => $this->efectivo->id, 'items' => [['periodo' => $periodo, 'monto' => $monto]],
            'usuario_operativo_id' => $this->operativo->id, 'monto_entregado' => $monto]);
    }

    private function datos(Alumno $a, int $grupo, ?int $plan): array
    {
        return array_filter(['nombre' => $a->nombre, 'apellido' => $a->apellido, 'dni' => $a->dni,
            'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-09-01', 'deporte_id' => $a->deporte_id,
            'grupo_id' => $grupo, 'plan_id' => $plan, 'celular' => $a->celular], fn ($v) => $v !== null);
    }
}
