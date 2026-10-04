<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AltaMesCerradoTest extends TestCase
{
    use RefreshDatabase;

    private GrupoPlan $plan;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 12:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->usuario = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->actingAs($this->usuario);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datos(array $extras = []): array
    {
        return array_replace(['nombre' => 'Alta', 'apellido' => 'Antigua', 'dni' => '45000888',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'fecha_alta' => '2020-01-20',
            'deporte_id' => $this->plan->grupo->deporte_id, 'grupo_id' => $this->plan->grupo_id,
            'plan_id' => $this->plan->id], $extras);
    }

    public function test_mes_cerrado_exige_decision_y_no_deja_alta_parcial(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos())
            ->assertSessionHasErrors('generar_cuota_actual');
        foreach (['alumnos', 'alumno_planes', 'deuda_cuotas', 'cargos_alumno'] as $tabla) {
            $this->assertDatabaseCount($tabla, 0);
        }
    }

    public function test_si_genera_cuota_corriente_completa_y_registra_quien_decidio(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos(['generar_cuota_actual' => '1']))->assertSessionHasNoErrors();
        $alumno = Alumno::firstOrFail();
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 48000, 'porcentaje_alta' => 100]);
        $this->assertDatabaseCount('deuda_cuotas', 1);
        $this->assertDatabaseMissing('deuda_cuotas', ['periodo' => '2020-01']);
        $this->assertDatabaseHas('cargos_alumno', ['alumno_id' => $alumno->id, 'tipo' => 'INSCRIPCION', 'monto_original' => 5000]);
        $this->assertSame('MES_ACTUAL', $alumno->alta_cuota['modo']);
        $this->assertSame($this->usuario->id, $alumno->alta_cuota['usuario_id']);
        $this->assertSame('2020-01-20', $alumno->alta_cuota['fecha_ingreso']);
        $this->assertNotEmpty($alumno->alta_cuota['fecha']);
    }

    public function test_no_evita_solo_cuota_y_conserva_inscripcion_y_decision(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin)->post(route('web.alumnos.store'), $this->datos(['generar_cuota_actual' => '0']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('alumnos', 1);
        $this->assertDatabaseCount('alumno_planes', 1);
        $this->assertDatabaseCount('deuda_cuotas', 0);
        $this->assertDatabaseHas('cargos_alumno', ['tipo' => 'INSCRIPCION', 'monto_original' => 5000]);
        $alumno = Alumno::firstOrFail();
        $this->assertSame('SIN_CUOTA', $alumno->alta_cuota['modo']);
        $this->assertSame($admin->id, $alumno->alta_cuota['usuario_id']);
    }

    public function test_un_no_enviado_para_ingreso_corriente_no_suprime_cuota_automatica(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos(['fecha_alta' => '2026-10-20', 'generar_cuota_actual' => '0']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('deuda_cuotas', ['periodo' => '2026-10', 'monto_original' => 33600, 'porcentaje_alta' => 70]);
        $this->assertSame('AUTOMATICA', Alumno::firstOrFail()->alta_cuota['modo']);
    }

    public function test_ultimo_dia_del_mes_anterior_genera_corriente_al_cien_por_ciento(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos(['fecha_alta' => '2026-09-30', 'generar_cuota_actual' => '1']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('deuda_cuotas', ['periodo' => '2026-10', 'monto_original' => 48000, 'porcentaje_alta' => 100]);
        $this->assertDatabaseCount('deuda_cuotas', 1);
    }

    public function test_reintento_conserva_decision_y_rechaza_cambiarla(): void
    {
        $datos = $this->datos(['generar_cuota_actual' => '0', 'alta_token' => '46060ca0-7e20-40dc-b4cf-e240b4ce3eea']);
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('web.alumnos.store'), array_replace($datos, ['generar_cuota_actual' => '1']))->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('alumnos', 1);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertDatabaseCount('deuda_cuotas', 0);
        $this->assertSame('SIN_CUOTA', Alumno::firstOrFail()->alta_cuota['modo']);
    }

    public function test_preview_informa_mes_cerrado_y_precio_sin_escribir(): void
    {
        $this->getJson(route('web.alumnos.cuota-alta-preview', ['fecha_alta' => '2020-01-20', 'plan_id' => $this->plan->id]))
            ->assertOk()->assertJsonPath('mes_cerrado', true)->assertJsonPath('periodo', '2026-10')
            ->assertJsonPath('importe', 48000)->assertJsonPath('porcentaje', 100);
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('deuda_cuotas', 0);
        $this->assertDatabaseCount('cargos_alumno', 0);
    }

    public function test_fallo_al_guardar_decision_revierte_alumno_plan_cargo_y_cuota(): void
    {
        $evento = 'eloquent.updated: '.Alumno::class;
        Event::listen($evento, function (Alumno $alumno) {
            if ($alumno->alta_cuota !== null) throw new \RuntimeException('Fallo controlado auditoría alta');
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('web.alumnos.store'), $this->datos(['generar_cuota_actual' => '1']));
            $this->fail('Debía guardar la decisión dentro de la transacción');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo controlado auditoría alta', $e->getMessage());
        } finally {
            Event::forget($evento);
        }
        foreach (['alumnos', 'alumno_planes', 'deuda_cuotas', 'cargos_alumno', 'cargo_alumno_eventos'] as $tabla) $this->assertDatabaseCount($tabla, 0);
    }

    public function test_mes_cambio_desde_el_aviso_rechaza_sin_escribir(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos(['generar_cuota_actual' => '1', 'cuota_periodo_visto' => '2026-09']))
            ->assertSessionHasErrors('generar_cuota_actual');
        $this->assertDatabaseCount('alumnos', 0);
    }

    public function test_precio_cambio_desde_el_aviso_rechaza_sin_escribir(): void
    {
        $this->post(route('web.alumnos.store'), $this->datos(['generar_cuota_actual' => '1', 'cuota_importe_visto' => '47000']))
            ->assertSessionHasErrors('generar_cuota_actual');
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('deuda_cuotas', 0);
    }
}

