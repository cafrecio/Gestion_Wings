<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoFrecuenciaObligatoriaTest extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]));
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Principiantes']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 30000, 'activo' => true]);
    }

    public function test_alta_sin_frecuencias_se_rechaza_sin_crear_grupo_ni_planes(): void
    {
        $nivel = Nivel::create(['nombre' => 'Avanzadas']);
        $antes = $this->estado();
        foreach ([[], ['planes' => []]] as $entrada) {
            $this->from(route('web.grupos.create'))->post(route('web.grupos.store'), $entrada + [
                'deporte_id' => $this->grupo->deporte_id, 'nivel_id' => $nivel->id,
            ])->assertRedirect(route('web.grupos.create'))
                ->assertSessionHasErrors(['planes'])
                ->assertSessionHas('error', 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.');
            $this->assertSame($antes, $this->estado());
        }
    }

    public function test_editar_quitando_todas_las_frecuencias_no_escribe_nada(): void
    {
        $antes = $this->estado();
        foreach ([[], ['planes' => []]] as $entrada) {
            $this->from(route('web.grupos.edit', $this->grupo))->put(route('web.grupos.update', $this->grupo), $entrada)
                ->assertRedirect(route('web.grupos.edit', $this->grupo))
                ->assertSessionHasErrors(['planes'])
                ->assertSessionHas('error', 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.');
            $this->assertSame($antes, $this->estado());
        }
    }

    public function test_borrar_la_ultima_frecuencia_se_rechaza_con_motivo_sin_escribir(): void
    {
        $antes = $this->estado();
        $this->from(route('web.grupos.show', $this->grupo))->delete(route('web.grupos.plans.destroy', $this->plan))
            ->assertRedirect(route('web.grupos.show', $this->grupo))
            ->assertSessionHas('error', 'No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.');
        $this->assertSame($antes, $this->estado());
    }

    public function test_permite_crear_editar_y_borrar_si_el_grupo_conserva_una_frecuencia(): void
    {
        $nivel = Nivel::create(['nombre' => 'Avanzadas']);
        $this->post(route('web.grupos.store'), [
            'deporte_id' => $this->grupo->deporte_id, 'nivel_id' => $nivel->id,
            'planes' => [['clases_por_semana' => 1, 'precio_mensual' => 35000]],
        ])->assertSessionHasNoErrors();
        $nuevo = Grupo::where('nivel_id', $nivel->id)->sole();
        $this->assertSame(1, $nuevo->planes()->count());
        $this->put(route('web.grupos.update', $this->grupo), ['planes' => [
            ['id' => $this->plan->id, 'clases_por_semana' => 1, 'precio_mensual' => 31000],
            ['clases_por_semana' => 2, 'precio_mensual' => 40000],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->grupo->planes()->count());
        $this->delete(route('web.grupos.plans.destroy', $this->plan))->assertSessionHas('success');
        $this->assertSame(1, $this->grupo->planes()->count());
    }

    public function test_un_id_de_frecuencia_inexistente_no_permite_vaciar_el_grupo(): void
    {
        $antes = $this->estado();
        $this->put(route('web.grupos.update', $this->grupo), ['planes' => [[
            'id' => $this->plan->id + 1000, 'clases_por_semana' => 1, 'precio_mensual' => 30000,
        ]]])->assertSessionHasErrors(['planes.0.id']);
        $this->assertSame($antes, $this->estado());
    }

    private function estado(): array
    {
        return [
            Grupo::orderBy('id')->get()->toArray(),
            GrupoPlan::orderBy('id')->get()->toArray(),
        ];
    }
}
