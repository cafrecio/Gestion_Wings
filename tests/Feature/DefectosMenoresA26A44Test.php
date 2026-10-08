<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, Grupo, GrupoPlan, Nivel, User};
use App\Notifications\AvisoOperativo;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dos defectos chicos de PRU-02 (A38 y A39 estan en MenuYMovimientosA38A39Test):
 *
 * - A26: el alta exigia celular propio a un menor.
 * - A44: el correo de avisos salia con el logo y el pie de Laravel.
 */
class DefectosMenoresA26A44Test extends TestCase
{
    use RefreshDatabase;

    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 12:00:00');
        $this->seed(CatalogosSeeder::class);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function operativo(): User
    {
        return User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
    }

    private function alta(array $extras = []): array
    {
        return array_replace(['nombre' => 'Mora', 'apellido' => 'Chica', 'dni' => '55000111',
            'fecha_nacimiento' => '2016-03-10', 'fecha_alta' => '2026-10-02',
            'nombre_tutor' => 'Laura Chica', 'telefono_tutor' => '1150001111',
            'deporte_id' => $this->plan->grupo->deporte_id, 'grupo_id' => $this->plan->grupo_id,
            'plan_id' => $this->plan->id], $extras);
    }

    // ── A26 ──────────────────────────────────────────────────────────────

    public function test_a26_un_menor_sin_celular_se_guarda_con_el_telefono_del_tutor(): void
    {
        $this->actingAs($this->operativo())
            ->post(route('web.alumnos.store'), $this->alta())
            ->assertSessionHasNoErrors();

        $this->assertSame('1150001111', Alumno::where('dni', '55000111')->value('celular'));
    }

    public function test_a26_un_menor_con_celular_propio_lo_conserva(): void
    {
        $this->actingAs($this->operativo())
            ->post(route('web.alumnos.store'), $this->alta(['celular' => '1177770000']))
            ->assertSessionHasNoErrors();

        $this->assertSame('1177770000', Alumno::where('dni', '55000111')->value('celular'));
    }

    public function test_a26_a_un_mayor_se_le_sigue_exigiendo_el_celular(): void
    {
        $this->actingAs($this->operativo())
            ->post(route('web.alumnos.store'), $this->alta([
                'fecha_nacimiento' => '2000-01-01', 'nombre_tutor' => null, 'telefono_tutor' => null,
            ]))
            ->assertSessionHasErrors('celular');

        $this->assertSame(0, Alumno::count());
    }

    public function test_a26_al_editar_un_menor_y_vaciar_el_celular_queda_el_del_tutor(): void
    {
        $usuario = $this->operativo();
        $this->actingAs($usuario)->post(route('web.alumnos.store'), $this->alta(['celular' => '1177770000']))
            ->assertSessionHasNoErrors();
        $alumno = Alumno::where('dni', '55000111')->firstOrFail();

        $this->actingAs($usuario)
            ->put(route('web.alumnos.update', $alumno->id), $this->alta(['celular' => '', 'telefono_tutor' => '1160002222']))
            ->assertSessionHasNoErrors();

        $this->assertSame('1160002222', $alumno->fresh()->celular);
    }

    // ── A44 ──────────────────────────────────────────────────────────────

    public function test_a44_el_correo_de_avisos_no_lleva_la_firma_de_laravel(): void
    {
        // El peor caso: un servidor donde APP_NAME quedo con el valor de fabrica.
        config(['app.name' => 'Laravel']);

        $correo = (new AvisoOperativo('Resumen diario', ['Cajas cerradas sin validar' => '2'], 'Detalle'))
            ->toMail($this->operativo());
        $html = (string) $correo->render();

        $this->assertStringContainsString('Wings', $html);
        $this->assertStringContainsString('Aviso automático de Wings', $html);
        $this->assertStringNotContainsString('laravel.com', $html);
        $this->assertStringNotContainsString('Laravel', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringNotContainsString('Regards', $html);
    }
}
