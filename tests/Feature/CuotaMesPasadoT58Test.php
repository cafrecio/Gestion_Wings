<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, DeudaCuota, Deporte, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T58 (DU-23, Carlos 13/10): el dueño crea desde la ficha la cuota de un mes pasado que nunca
 * se generó. Vale lo que la habría creado el generador: el precio del plan de ese mes.
 */
class CuotaMesPasadoT58Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private Alumno $alumno;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'name' => 'Dueño T58']);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín T58', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T58']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 12000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Olvidada', 'dni' => '58000001',
            'fecha_nacimiento' => '2010-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-08-10',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-08-10', 'activo' => true]);
        // Agosto y octubre existen; septiembre nunca se generó.
        foreach (['2026-08', '2026-10'] as $periodo) {
            DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo,
                'monto_original' => 12000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
        }
    }

    private function crear(User $usuario, string $periodo)
    {
        return $this->actingAs($usuario)
            ->from(route('web.alumnos.show', $this->alumno->id))
            ->post(route('web.alumnos.cuotas-pasadas', $this->alumno->id), ['periodo' => $periodo]);
    }

    public function test_ofrece_solo_los_meses_pasados_que_faltan(): void
    {
        $this->assertSame([['periodo' => '2026-09', 'precio' => 12000.0]],
            app(PagoCuotaService::class)->mesesPasadosSinCuota($this->alumno));
    }

    public function test_el_dueno_crea_septiembre_al_precio_del_plan(): void
    {
        $this->crear($this->admin, '2026-09')
            ->assertRedirect(route('web.alumnos.show', $this->alumno->id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'septiembre 2026'));

        $septiembre = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->sole();
        $this->assertSame('12000.00', $septiembre->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $septiembre->estado);
        $this->assertStringContainsString('Dueño T58', $septiembre->observaciones);
        $this->assertSame([], app(PagoCuotaService::class)->mesesPasadosSinCuota($this->alumno));
    }

    public function test_la_operativa_no_puede_crearla(): void
    {
        $this->crear($this->operativa, '2026-09')->assertForbidden();
        $this->assertFalse(DeudaCuota::where('periodo', '2026-09')->exists());
    }

    public function test_rechaza_lo_que_no_es_un_mes_pasado_valido(): void
    {
        $casos = [
            '2026-10' => 'Solo se crean desde acá las cuotas de meses que ya pasaron.',
            '2026-11' => 'Solo se crean desde acá las cuotas de meses que ya pasaron.',
            '2026-07' => 'Ese mes es anterior al ingreso del alumno.',
            '2026-08' => 'Ese mes ya tiene su cuota.',
            'cualquiera' => 'El mes elegido no es válido.',
        ];
        foreach ($casos as $periodo => $mensaje) {
            $this->crear($this->admin, $periodo)->assertSessionHas('error', $mensaje);
        }
        $this->assertSame(2, DeudaCuota::where('alumno_id', $this->alumno->id)->count());
    }

    public function test_sin_plan_en_ese_mes_no_la_crea(): void
    {
        AlumnoPlan::where('alumno_id', $this->alumno->id)->update(['fecha_desde' => '2026-10-01']);

        $this->crear($this->admin, '2026-09')->assertSessionHas('error', 'El alumno no tenía un plan en ese mes.');
        $this->assertFalse(DeudaCuota::where('periodo', '2026-09')->exists());
    }

    public function test_despues_se_cobra_como_cualquier_cuota(): void
    {
        $this->crear($this->admin, '2026-09');
        $medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();

        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
            'tipo_caja_id' => $medio->id, 'usuario_admin_id' => $this->admin->id, 'monto_entregado' => 24000,
            'items' => [['periodo' => '2026-08', 'monto' => 12000], ['periodo' => '2026-09', 'monto' => 12000]]]);

        $this->assertSame(DeudaCuota::ESTADO_PAGADA, DeudaCuota::where('periodo', '2026-09')->sole()->estado);
    }
}
