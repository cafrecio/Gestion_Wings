<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User, Profesor};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumnoSinPlanT56T68Test extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo1;
    private Grupo $grupo2;
    private GrupoPlan $planGrupo1;
    private GrupoPlan $planGrupo2;
    private User $admin;
    private User $operativo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 10:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);

        $deporte = Deporte::first();
        $niveles = Nivel::all();

        $this->grupo1 = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $niveles[0]->id,
            'activo' => true,
        ]);
        $this->planGrupo1 = GrupoPlan::create([
            'grupo_id' => $this->grupo1->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 20000,
            'activo' => true,
        ]);

        $this->grupo2 = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $niveles[1]->id,
            'activo' => true,
        ]);
        $this->planGrupo2 = GrupoPlan::create([
            'grupo_id' => $this->grupo2->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearAlumno(array $attrs = []): Alumno
    {
        $alumno = Alumno::create(array_merge([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '40123456',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $this->grupo1->deporte_id,
            'grupo_id' => $this->grupo1->id,
            'celular' => '1144556677',
            'activo' => true,
        ], $attrs));

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->planGrupo1->id,
            'fecha_desde' => '2026-09-01',
            'fecha_hasta' => null,
            'activo' => true,
        ]);

        return $alumno;
    }

    public function test_cambiar_grupo_exige_plan_del_nuevo_grupo(): void
    {
        $this->actingAs($this->admin);
        $alumno = $this->crearAlumno();

        // Intento 1: Cambiar a grupo2 sin enviar plan_id -> falla validación
        $response = $this->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $alumno->deporte_id,
            'grupo_id' => $this->grupo2->id,
            'celular' => $alumno->celular,
        ]);

        $response->assertSessionHasErrors(['plan_id']);
        $this->assertEquals(
            'Debe seleccionar la frecuencia semanal del nuevo grupo.',
            session('errors')->first('plan_id')
        );

        // Intento 2: Cambiar a grupo2 enviando el plan del grupo1 -> falla validación (exists where grupo_id)
        $response2 = $this->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $alumno->deporte_id,
            'grupo_id' => $this->grupo2->id,
            'plan_id' => $this->planGrupo1->id,
            'celular' => $alumno->celular,
        ]);

        $response2->assertSessionHasErrors(['plan_id']);
        $this->assertEquals(
            'La frecuencia seleccionada no corresponde al grupo.',
            session('errors')->first('plan_id')
        );

        // Intento 3: Cambiar a grupo2 enviando el plan del grupo2 -> éxito
        $response3 = $this->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $alumno->deporte_id,
            'grupo_id' => $this->grupo2->id,
            'plan_id' => $this->planGrupo2->id,
            'celular' => $alumno->celular,
        ]);

        $response3->assertRedirect(route('web.alumnos.index'))->assertSessionHasNoErrors();
        $alumno->refresh();
        $this->assertEquals($this->grupo2->id, $alumno->grupo_id);
    }

    public function test_alumno_sin_plan_valido_en_edicion_exige_plan(): void
    {
        $this->actingAs($this->admin);
        $alumno = Alumno::create([
            'nombre' => 'Sin',
            'apellido' => 'Plan',
            'dni' => '40999888',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $this->grupo1->deporte_id,
            'grupo_id' => $this->grupo1->id,
            'celular' => '1144556677',
            'activo' => true,
        ]);
        // No tiene AlumnoPlan

        $response = $this->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $alumno->deporte_id,
            'grupo_id' => $this->grupo1->id,
            'celular' => $alumno->celular,
        ]);

        $response->assertSessionHasErrors(['plan_id']);
        $this->assertEquals(
            'Debe seleccionar la frecuencia semanal.',
            session('errors')->first('plan_id')
        );
    }

    public function test_cobro_de_deuda_a_inactivo_sin_plan_vigente_no_lo_reactiva(): void
    {
        $alumno = $this->crearAlumno(['activo' => false]);
        // Vencer su plan en 2026-09 para que en 2026-10 no tenga plan
        $plan = $alumno->planActivo;
        $plan->update(['fecha_hasta' => '2026-09-30', 'activo' => false]);

        // Crear una deuda vieja de septiembre
        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $tipoCaja = TipoCaja::first();
        $pagoService = app(PagoCuotaService::class);

        // Cobrarle deuda de septiembre como admin
        $pagoService->registrarPagoCuotaAdmin([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $tipoCaja->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => [
                ['periodo' => '2026-09', 'monto' => 20000],
            ],
            'monto_entregado' => 20000,
        ]);

        $alumno->refresh();
        $deuda->refresh();

        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
        // Debe seguir inactivo porque no tiene plan para el mes actual
        $this->assertFalse((bool) $alumno->activo, 'El alumno no debe reactivarse si no tiene plan vigente para el mes.');
        // Y no debe existir deuda de 2026-10
        $this->assertDatabaseMissing('deuda_cuotas', [
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
        ]);
    }

    public function test_cobro_a_inactivo_con_plan_vigente_si_lo_reactiva_y_crea_reingreso(): void
    {
        $alumno = $this->crearAlumno(['activo' => false]);
        // Su plan sigue vigente (fecha_hasta = null)

        // Crear deuda vieja de septiembre
        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $tipoCaja = TipoCaja::first();
        $pagoService = app(PagoCuotaService::class);

        // Cobrar deuda vieja como admin
        $pagoService->registrarPagoCuotaAdmin([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $tipoCaja->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => [
                ['periodo' => '2026-09', 'monto' => 20000],
            ],
            'monto_entregado' => 20000,
        ]);

        $alumno->refresh();
        $deuda->refresh();

        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
        // Se reactivó porque tiene plan vigente
        $this->assertTrue((bool) $alumno->activo);
        // Se creó la cuota de reingreso de 2026-10
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
        ]);
    }

    public function test_vistas_grupo_y_profesor_no_muestran_proximamente(): void
    {
        $this->actingAs($this->admin);

        // Ficha de grupo
        $resGrupo = $this->get(route('web.grupos.show', $this->grupo1->id));
        $resGrupo->assertOk();
        $resGrupo->assertDontSee('Próximamente');
        $resGrupo->assertDontSee('Cronograma semanal');

        // Ficha de profesor
        $profesor = Profesor::create([
            'nombre' => 'Carlos',
            'apellido' => 'Profesor',
            'dni' => '33444555',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Quilmes',
            'telefono' => '1144556677',
            'valor_hora' => 5000,
            'email' => 'profe@wings.test',
            'deporte_id' => $this->grupo1->deporte_id,
            'activo' => true,
        ]);

        $resProf = $this->get(route('web.profesores.show', $profesor->id));
        $resProf->assertOk();
        $resProf->assertDontSee('Próximamente');
        $resProf->assertDontSee('Historial de liquidaciones');
        $resProf->assertDontSee('Clases asignadas');
    }
}
