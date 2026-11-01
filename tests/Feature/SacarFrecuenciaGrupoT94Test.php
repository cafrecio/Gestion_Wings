<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use Tests\Support\CajaDeclarada;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CambioPlanService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SacarFrecuenciaGrupoT94Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $deporte;
    private Nivel $nivel;
    private Grupo $grupo;
    private GrupoPlan $plan1;
    private GrupoPlan $plan2;
    private int $nextDni = 40000001;

    private function crearAlumno(array $attrs = []): Alumno
    {
        return Alumno::create(array_merge([
            'nombre' => 'Alumno',
            'apellido' => 'Prueba ' . $this->nextDni,
            'dni' => (string) $this->nextDni++,
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => today()->subMonths(2),
            'celular' => '1144445555',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ], $attrs));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($this->admin);

        $this->deporte = Deporte::create([
            'nombre' => 'Patín Artístico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $this->nivel = Nivel::create(['nombre' => 'Avanzado']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $this->nivel->id,
            'activo' => true,
        ]);

        $this->plan1 = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 1,
            'precio_mensual' => 20000,
            'activo' => true,
        ]);

        $this->plan2 = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. NUNCA SE USÓ
    // -------------------------------------------------------------------------

    public function test_frecuencia_nunca_usada_se_elimina_fisicamente_via_destroy(): void
    {
        $id = $this->plan2->id;

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('success', 'Plan eliminado.');

        $this->assertDatabaseMissing('grupo_planes', ['id' => $id]);
        $this->assertSame(1, $this->grupo->planesActivos()->count());
    }

    public function test_frecuencia_nunca_usada_se_elimina_fisicamente_via_update(): void
    {
        $id = $this->plan2->id;

        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [
                    ['id' => $this->plan1->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000],
                ],
            ]);

        $response->assertRedirect(route('web.grupos.index'))
            ->assertSessionHas('success', 'Grupo actualizado correctamente.');

        $this->assertDatabaseMissing('grupo_planes', ['id' => $id]);
        $this->assertSame(1, $this->grupo->planesActivos()->count());
    }

    // -------------------------------------------------------------------------
    // 2. SE USÓ ANTES, SIN ALUMNOS HOY
    // -------------------------------------------------------------------------

    public function test_frecuencia_usada_antes_sin_alumnos_hoy_se_desactiva_via_destroy(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonths(2),
        ]);
        // Asignación pasada ya terminada (activo = null)
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today()->subMonths(2),
            'fecha_hasta' => today()->subMonth()->subDay(),
            'activo' => null,
        ]);
        // Asignación actual en plan1
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');

        $this->plan2->refresh();
        $this->assertFalse($this->plan2->activo);
        $this->assertDatabaseHas('grupo_planes', ['id' => $this->plan2->id, 'activo' => false]);
        $this->assertSame(1, $this->grupo->planesActivos()->count());
    }

    public function test_frecuencia_usada_antes_sin_alumnos_hoy_se_desactiva_via_update(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonths(2),
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today()->subMonths(2),
            'fecha_hasta' => today()->subMonth()->subDay(),
            'activo' => null,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [
                    ['id' => $this->plan1->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000],
                ],
            ]);

        $response->assertRedirect(route('web.grupos.index'))
            ->assertSessionHas('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');

        $this->plan2->refresh();
        $this->assertFalse($this->plan2->activo);
        $this->assertSame(1, $this->grupo->planesActivos()->count());
    }

    public function test_frecuencia_usada_solo_en_pagos_se_desactiva_sin_error_500(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonths(2),
        ]);
        // Solo un pago histórico
        Pago::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'mes' => (int) today()->subMonth()->format('m'),
            'anio' => (int) today()->subMonth()->format('Y'),
            'monto_base' => 30000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 30000,
            'monto_cuota' => 30000,
            'fecha_pago' => today()->subMonth()->toDateString(),
            'estado' => 'COMPLETADO',
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');

        $this->assertFalse($this->plan2->fresh()->activo);
    }

    // -------------------------------------------------------------------------
    // 3. TIENE ALUMNOS HOY
    // -------------------------------------------------------------------------

    public function test_rechaza_sacar_frecuencia_con_alumnos_activos_via_destroy(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 1 alumno: cambiales el plan antes.');

        $this->assertTrue($this->plan2->fresh()->activo);
    }

    public function test_rechaza_sacar_frecuencia_con_varios_alumnos_activos_plural(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $alumno = $this->crearAlumno([
                'deporte_id' => $this->deporte->id,
                'grupo_id' => $this->grupo->id,
                'activo' => true,
            ]);
            AlumnoPlan::create([
                'alumno_id' => $alumno->id,
                'plan_id' => $this->plan2->id,
                'fecha_desde' => today(),
                'activo' => true,
            ]);
        }

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 3 alumnos: cambiales el plan antes.');

        $this->assertTrue($this->plan2->fresh()->activo);
    }

    public function test_rechaza_sacar_frecuencia_con_alumnos_activos_via_update_y_no_guarda_nada(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [
                    // Se intenta cambiar precio de plan1 a 99999 y quitar plan2
                    ['id' => $this->plan1->id, 'clases_por_semana' => 1, 'precio_mensual' => 99999],
                ],
            ]);

        $response->assertRedirect(route('web.grupos.edit', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 1 alumno: cambiales el plan antes.');

        // Comprobar que plan1 NO cambió de precio (rollback total)
        $this->assertSame('20000.00', (string) $this->plan1->fresh()->precio_mensual);
        // plan2 sigue activo
        $this->assertTrue($this->plan2->fresh()->activo);
    }

    public function test_cuenta_alumno_inactivo_con_plan_activo_como_alumno_hoy(): void
    {
        // Alumno dado de baja o inactivo en club, pero cuya asignación deportiva tiene activo=true
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => false,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 1 alumno: cambiales el plan antes.');
    }

    public function test_cuenta_cambio_de_plan_futuro_como_alumno_hoy(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);
        // Asignación actual en plan1
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => true,
        ]);
        // Asignación futura programada en plan2 (activo = true)
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today()->addMonth()->startOfMonth(),
            'activo' => true,
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 1 alumno: cambiales el plan antes.');
    }

    // -------------------------------------------------------------------------
    // 4. ES LA ÚLTIMA FRECUENCIA ACTIVA
    // -------------------------------------------------------------------------

    public function test_rechaza_eliminar_la_ultima_frecuencia_activa_via_destroy(): void
    {
        // Desactivamos plan2
        $this->plan2->update(['activo' => false]);
        $this->assertSame(1, $this->grupo->planesActivos()->count());

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan1));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', 'No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.');

        $this->assertTrue($this->plan1->fresh()->activo);
    }

    public function test_rechaza_vaciar_frecuencias_via_update(): void
    {
        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [],
            ]);

        $response->assertRedirect(route('web.grupos.edit', $this->grupo))
            ->assertSessionHasErrors(['planes'])
            ->assertSessionHas('error', 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.');
    }

    // -------------------------------------------------------------------------
    // 5. VOLVER A AGREGAR UNA FRECUENCIA DESACTIVADA (REACTIVAR)
    // -------------------------------------------------------------------------

    public function test_reactiva_frecuencia_desactivada_via_store_plan_con_nuevo_precio(): void
    {
        $this->plan2->update(['activo' => false]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->post(route('web.grupos.plans.store', $this->grupo), [
                'clases_por_semana' => 2,
                'precio_mensual' => 38000,
            ]);

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('success', 'Plan agregado.');

        // Debe ser el mismo registro reactivado
        $this->plan2->refresh();
        $this->assertTrue($this->plan2->activo);
        $this->assertSame('38000.00', (string) $this->plan2->precio_mensual);
        $this->assertSame(2, $this->grupo->planes()->count());
    }

    public function test_reactiva_frecuencia_desactivada_via_update_con_nuevo_precio(): void
    {
        $this->plan2->update(['activo' => false]);

        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [
                    ['id' => $this->plan1->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000],
                    ['id' => '', 'clases_por_semana' => 2, 'precio_mensual' => 42000],
                ],
            ]);

        $response->assertRedirect(route('web.grupos.index'))
            ->assertSessionHas('success', 'Grupo actualizado correctamente.');

        $this->plan2->refresh();
        $this->assertTrue($this->plan2->activo);
        $this->assertSame('42000.00', (string) $this->plan2->precio_mensual);
        $this->assertSame(2, $this->grupo->planes()->count());
    }

    // -------------------------------------------------------------------------
    // 6. COLISIONES UNIQUE Y CONSOLIDACIÓN
    // -------------------------------------------------------------------------

    public function test_si_existian_dos_filas_inactivas_desactivar_no_da_error_500(): void
    {
        // Simular caso borde donde una fila inactiva ya existía y se desactiva otra
        $otroInactivo = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 25000,
            'activo' => false,
        ]);
        $plan3Activo = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 35000,
            'activo' => true,
        ]);

        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonth(),
        ]);
        // Usado antes
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan3Activo->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => null,
        ]);

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $plan3Activo));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');

        $this->assertFalse($plan3Activo->fresh()->activo);
        $this->assertDatabaseMissing('grupo_planes', ['id' => $otroInactivo->id]);
    }

    public function test_update_con_frecuencias_duplicadas_en_formulario_se_rechaza(): void
    {
        $response = $this->from(route('web.grupos.edit', $this->grupo))
            ->put(route('web.grupos.update', $this->grupo), [
                'planes' => [
                    ['id' => $this->plan1->id, 'clases_por_semana' => 2, 'precio_mensual' => 20000],
                    ['id' => $this->plan2->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000],
                ],
            ]);

        $response->assertRedirect(route('web.grupos.edit', $this->grupo))
            ->assertSessionHas('error', 'Hay dos filas con la misma frecuencia. Dejá una sola.');
    }

    // -------------------------------------------------------------------------
    // 7. VISTAS FILTRAN PLANES INACTIVOS
    // -------------------------------------------------------------------------

    public function test_show_y_edit_no_muestran_frecuencias_desactivadas(): void
    {
        $this->plan2->update(['activo' => false]);

        $this->get(route('web.grupos.show', $this->grupo))
            ->assertOk()
            ->assertSee('1x / semana')
            ->assertDontSee('2x / semana');

        $this->get(route('web.grupos.edit', $this->grupo))
            ->assertOk()
            ->assertSee('name="planes[0][clases_por_semana]"', false)
            ->assertDontSee('name="planes[1][clases_por_semana]"', false);
    }

    // -------------------------------------------------------------------------
    // 8. PEDIDOS DIRECTOS A ENDPOINTS DE ASIGNACIÓN
    // -------------------------------------------------------------------------

    public function test_alta_de_alumno_con_plan_inactivo_se_rechaza(): void
    {
        $this->plan2->update(['activo' => false]);

        $response = $this->post(route('web.alumnos.store'), [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '40123456',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => today()->toDateString(),
            'celular' => '1123456789',
            'email' => 'juan@example.com',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan2->id,
        ]);

        $response->assertSessionHasErrors(['plan_id']);
        $this->assertDatabaseMissing('alumnos', ['dni' => '40123456']);
    }

    public function test_edicion_de_alumno_con_plan_inactivo_se_rechaza(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonth(),
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => true,
        ]);

        $this->plan2->update(['activo' => false]);

        $response = $this->put(route('web.alumnos.update', $alumno), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => $alumno->fecha_nacimiento->toDateString(),
            'fecha_alta' => $alumno->fecha_alta->toDateString(),
            'celular' => $alumno->celular,
            'email' => $alumno->email,
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan2->id,
        ]);

        $response->assertSessionHasErrors(['plan_id']);
        $this->assertSame($this->plan1->id, $alumno->fresh()->planActivo->plan_id);
    }

    public function test_cambio_plan_service_rechaza_asignar_plan_inactivo(): void
    {
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonth(),
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->subMonth(),
            'activo' => true,
        ]);

        $this->plan2->update(['activo' => false]);

        $this->expectException(ValidationException::class);
        app(CambioPlanService::class)->cambiar($alumno, $this->plan2);
    }

    // -------------------------------------------------------------------------
    // 9. PRUEBA DE PUNTA A PUNTA (END-TO-END CON PLATA REAL AL PESO)
    // -------------------------------------------------------------------------

    public function test_flujo_punta_a_punta_desactivar_cobro_historico_y_reactivacion(): void
    {
        // 1. Alumno inicia con plan 2x ($30.000)
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
            'fecha_alta' => today()->subMonth()->startOfMonth(),
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan2->id,
            'fecha_desde' => today()->subMonth()->startOfMonth(),
            'activo' => true,
        ]);
        // Generar deuda del mes pasado ($30.000)
        $periodoAnterior = today()->subMonth()->format('Y-m');
        $deudaVieja = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodoAnterior,
            'monto_original' => 30000,
            'saldo_pendiente' => 30000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // 2. Alumno pasó a plan 1x ($20.000) a partir de este mes; la asignación del plan 2x quedó en el pasado (activo = null)
        AlumnoPlan::where('alumno_id', $alumno->id)->where('plan_id', $this->plan2->id)->update([
            'fecha_hasta' => today()->startOfMonth()->subDay(),
            'activo' => null,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan1->id,
            'fecha_desde' => today()->startOfMonth(),
            'activo' => true,
        ]);
        $this->assertSame($this->plan1->id, $alumno->fresh()->planActivo->plan_id);

        // 3. Dueño desactiva el plan 2x
        $this->delete(route('web.grupos.plans.destroy', $this->plan2))
            ->assertSessionHas('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');
        $this->assertFalse($this->plan2->fresh()->activo);

        // 4. Cobrar en caja la deuda vieja del plan 2x ($30.000)
        $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], [
            'es_efectivo' => true,
            'activo' => true,
        ]);
        CajaDeclarada::crear($this->admin->id, $tipoCaja->id);

        $responseCobro = $this->postJson(route('web.caja.pagar', $alumno), [
            'tipo_caja_id' => $tipoCaja->id,
            'periodos' => [$periodoAnterior],
            'fecha_pago' => today()->toDateString(),
            'confirmar_fecha_vieja' => true,
            'montos_cuota' => [$periodoAnterior => 30000],
        ]);
        $responseCobro->assertSessionHasNoErrors()->assertSessionMissing('error')->assertSessionHas('success');

        // Deuda vieja pagada
        $deudaVieja->refresh();
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaVieja->estado);
        $this->assertEquals(0, (float) $deudaVieja->saldo_pendiente);

        // Pago registrado
        $pago = Pago::where('alumno_id', $alumno->id)->first();
        $this->assertNotNull($pago);
        $this->assertEquals(30000, (float) $pago->monto_final);

        // 5. Se reactiva el plan 2x con precio nuevo $35.000
        $this->post(route('web.grupos.plans.store', $this->grupo), [
            'clases_por_semana' => 2,
            'precio_mensual' => 35000,
        ])->assertSessionHas('success', 'Plan agregado.');

        $this->plan2->refresh();
        $this->assertTrue($this->plan2->activo);
        $this->assertSame('35000.00', (string) $this->plan2->precio_mensual);

        // 6. Alumno nuevo se inscribe y toma el plan 2x reactivado a $35.000
        $this->post(route('web.alumnos.store'), [
            'nombre' => 'Ana',
            'apellido' => 'Gómez',
            'dni' => '42999888',
            'fecha_nacimiento' => '2005-05-15',
            'fecha_alta' => today()->toDateString(),
            'celular' => '1199998888',
            'email' => 'ana@example.com',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan2->id,
        ])->assertSessionHasNoErrors();

        $alumnoNuevo = Alumno::where('dni', '42999888')->firstOrFail();
        $this->assertSame($this->plan2->id, $alumnoNuevo->planActivo->plan_id);
    }

    // -------------------------------------------------------------------------
    // 10. CONCURRENCIA / SERIALIZACIÓN
    // -------------------------------------------------------------------------

    public function test_concurrencia_sacar_frecuencia_mientras_se_asigna_alumno(): void
    {
        // Si una transacción asigna un alumno justo antes de que el plan intente borrarse,
        // al adquirir el lock de actualización el borrado debe detectar el alumno asignado y rechazar.
        $alumno = $this->crearAlumno([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($alumno) {
            AlumnoPlan::create([
                'alumno_id' => $alumno->id,
                'plan_id' => $this->plan2->id,
                'fecha_desde' => today(),
                'activo' => true,
            ]);
        });

        $response = $this->from(route('web.grupos.show', $this->grupo))
            ->delete(route('web.grupos.plans.destroy', $this->plan2));

        $response->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', '2x por semana tiene 1 alumno: cambiales el plan antes.');

        $this->assertTrue($this->plan2->fresh()->activo);
    }
}
