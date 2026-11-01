<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T102, regla de Carlos del 01/11/2026: un grupo no puede quedar sin nivel. Un nivel con
 * grupos activos no se inactiva: esos grupos se pasan a otro nivel o se desactivan.
 */
class NivelInactivoT102Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $patin;
    private Nivel $escuelita;
    private Nivel $competencia;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->escuelita = Nivel::create(['nombre' => 'Escuelita']);
        $this->competencia = Nivel::create(['nombre' => 'Competencia']);
        $this->grupo = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $this->escuelita->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->actingAs($this->admin);
    }

    private function alumno(): Alumno
    {
        return Alumno::create([
            'nombre' => 'Clara', 'apellido' => 'Núñez', 'dni' => '45123456', 'fecha_nacimiento' => '2012-01-01',
            'fecha_alta' => '2026-10-01', 'celular' => '1122334455', 'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupo->id, 'activo' => true,
        ]);
    }

    private function alternar(Nivel $nivel)
    {
        return $this->patchJson(route('web.niveles.toggle-activo', $nivel->id));
    }

    private function guardarGrupo(int $nivelId, array $planes = null)
    {
        return $this->from(route('web.grupos.edit', $this->grupo->id))->put(route('web.grupos.update', $this->grupo->id), [
            'nivel_id' => $nivelId,
            'planes' => $planes ?? [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000]],
        ]);
    }

    public function test_un_nivel_nuevo_nace_activo(): void
    {
        $this->assertTrue($this->competencia->fresh()->activo);
    }

    public function test_con_un_grupo_activo_no_se_inactiva_y_el_aviso_dice_que_hacer(): void
    {
        $this->alternar($this->escuelita)->assertStatus(422)->assertExactJson([
            'error' => 'No se puede inactivar el nivel porque tiene 1 grupo activo. Pasalo a otro nivel o desactivalo.',
        ]);
        $this->assertTrue($this->escuelita->fresh()->activo);
    }

    public function test_con_varios_grupos_activos_el_aviso_dice_cuantos(): void
    {
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => $this->escuelita->id, 'activo' => true]);

        $this->from(route('web.niveles.index'))->patch(route('web.niveles.toggle-activo', $this->escuelita->id))
            ->assertRedirect(route('web.niveles.index'))
            ->assertSessionHas('error', 'No se puede inactivar el nivel porque tiene 2 grupos activos. Pasalos a otro nivel o desactivalos.');
        $this->assertTrue($this->escuelita->fresh()->activo);
    }

    public function test_sin_grupos_activos_se_inactiva_y_se_vuelve_a_activar(): void
    {
        $this->alternar($this->competencia)->assertOk()->assertExactJson(['activo' => false, 'mensaje' => 'Nivel inactivado correctamente.']);
        $this->assertFalse($this->competencia->fresh()->activo);

        $this->alternar($this->competencia)->assertOk()->assertExactJson(['activo' => true, 'mensaje' => 'Nivel activado correctamente.']);
        $this->assertTrue($this->competencia->fresh()->activo);
    }

    public function test_con_grupos_solo_inactivos_se_inactiva_y_el_grupo_conserva_su_nivel(): void
    {
        $this->grupo->update(['activo' => false]);

        $this->alternar($this->escuelita)->assertOk();

        $this->assertFalse($this->escuelita->fresh()->activo);
        $this->assertSame($this->escuelita->id, $this->grupo->fresh()->nivel_id);
    }

    public function test_solo_el_dueno_puede_inactivar(): void
    {
        foreach ([User::ROL_OPERATIVO, User::ROL_PROFESOR] as $rol) {
            $otro = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $respuesta = $this->actingAs($otro)->patch(route('web.niveles.toggle-activo', $this->competencia->id));
            $this->assertContains($respuesta->getStatusCode(), [302, 403], "El rol {$rol} inactivó un nivel");
            $this->assertTrue($this->competencia->fresh()->activo, "El rol {$rol} inactivó un nivel");
        }
    }

    public function test_el_nivel_inactivo_no_se_ofrece_al_crear_un_grupo(): void
    {
        $this->competencia->update(['activo' => false]);

        $this->get(route('web.grupos.create'))->assertOk()
            ->assertSee('Escuelita')
            ->assertDontSee('Competencia');
    }

    public function test_el_nivel_inactivo_no_se_acepta_al_crear_un_grupo_forzando_el_pedido(): void
    {
        $this->competencia->update(['activo' => false]);
        $antes = Grupo::count();

        $this->from(route('web.grupos.create'))->post(route('web.grupos.store'), [
            'deporte_id' => $this->patin->id, 'nivel_id' => $this->competencia->id,
            'planes' => [['clases_por_semana' => 1, 'precio_mensual' => 20000]],
        ])->assertRedirect(route('web.grupos.create'))
            ->assertSessionHas('error', 'El nivel seleccionado no existe o está inactivo.');

        $this->assertSame($antes, Grupo::count());
    }

    public function test_cambiar_de_nivel_conserva_alumnos_precios_y_clases(): void
    {
        $alumno = $this->alumno();
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-11-20', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);

        $this->guardarGrupo($this->competencia->id)->assertRedirect(route('web.grupos.index'))
            ->assertSessionHas('success', 'Grupo actualizado correctamente.');

        $grupo = $this->grupo->fresh();
        $this->assertSame($this->competencia->id, $grupo->nivel_id);
        $this->assertSame('Patín — Competencia', $grupo->nombre);
        $this->assertSame($this->grupo->id, $alumno->fresh()->grupo_id);
        $this->assertSame($this->grupo->id, $clase->fresh()->grupo_id);
        $this->assertEquals(30000, $this->plan->fresh()->precio_mensual);
    }

    public function test_cambiar_de_nivel_deja_inactivar_el_nivel_anterior(): void
    {
        $this->alternar($this->escuelita)->assertStatus(422);

        $this->guardarGrupo($this->competencia->id);

        $this->alternar($this->escuelita)->assertOk();
        $this->assertFalse($this->escuelita->fresh()->activo);
    }

    public function test_editar_muestra_elegido_el_nivel_actual(): void
    {
        $html = $this->get(route('web.grupos.edit', $this->grupo->id))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.$this->escuelita->id.'"\s+selected>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="'.$this->competencia->id.'"\s+selected>/', $html);
    }

    public function test_no_se_puede_pasar_a_un_nivel_inactivo(): void
    {
        $this->competencia->update(['activo' => false]);

        $this->guardarGrupo($this->competencia->id, [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => 99000]])
            ->assertRedirect(route('web.grupos.edit', $this->grupo->id))
            ->assertSessionHas('error', 'El nivel seleccionado no existe o está inactivo.');

        $this->assertSame($this->escuelita->id, $this->grupo->fresh()->nivel_id);
        $this->assertEquals(30000, $this->plan->fresh()->precio_mensual, 'Rechazado el nivel, el precio tampoco se guarda');
    }

    public function test_no_se_puede_pasar_a_un_nivel_donde_ese_deporte_ya_tiene_grupo(): void
    {
        $otro = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $this->competencia->id, 'activo' => true]);

        $this->guardarGrupo($this->competencia->id)
            ->assertRedirect(route('web.grupos.edit', $this->grupo->id))
            ->assertSessionHas('error', 'Ya existe un grupo con ese deporte y nivel.');

        $this->assertSame($this->escuelita->id, $this->grupo->fresh()->nivel_id);
        $this->assertSame($this->competencia->id, $otro->fresh()->nivel_id);
    }

    public function test_si_la_frecuencia_no_se_puede_sacar_el_nivel_tampoco_cambia(): void
    {
        \App\Models\AlumnoPlan::create([
            'alumno_id' => $this->alumno()->id,
            'plan_id' => $this->plan->id, 'fecha_desde' => '2026-10-01', 'activo' => true,
        ]);

        $this->guardarGrupo($this->competencia->id, [['clases_por_semana' => 3, 'precio_mensual' => 40000]])
            ->assertSessionHas('error');

        $this->assertSame($this->escuelita->id, $this->grupo->fresh()->nivel_id);
    }

    public function test_un_nivel_que_no_existe_se_rechaza(): void
    {
        $this->guardarGrupo(999999)->assertSessionHasErrors('nivel_id');
        $this->assertSame($this->escuelita->id, $this->grupo->fresh()->nivel_id);
    }

    public function test_editar_ofrece_los_activos_y_el_nivel_actual_aunque_este_inactivo(): void
    {
        $federadas = Nivel::create(['nombre' => 'Federadas', 'activo' => false]);
        $otraInactiva = Nivel::create(['nombre' => 'Intermedio', 'activo' => false]);
        $this->grupo->update(['nivel_id' => $federadas->id, 'activo' => false]);

        $this->get(route('web.grupos.edit', $this->grupo->id))->assertOk()
            ->assertSee('Federadas')
            ->assertSee('(inactivo)')
            ->assertSee('Competencia')
            ->assertDontSee($otraInactiva->nombre);

        // Sigue pudiendo guardar otros datos sin que le exijan cambiar el nivel.
        $this->guardarGrupo($federadas->id, [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000]])
            ->assertRedirect(route('web.grupos.index'));
        $this->assertEquals(35000, $this->plan->fresh()->precio_mensual);
        $this->assertSame($federadas->id, $this->grupo->fresh()->nivel_id);
    }

    public function test_no_se_activa_un_grupo_cuyo_nivel_esta_inactivo(): void
    {
        $this->grupo->update(['activo' => false]);
        $this->escuelita->update(['activo' => false]);

        $this->patchJson(route('web.grupos.toggle-activo', $this->grupo->id))->assertStatus(422)->assertExactJson([
            'error' => 'No se puede activar el grupo porque el nivel «Escuelita» está inactivo. Activá el nivel o pasá el grupo a otro.',
        ]);
        $this->assertFalse($this->grupo->fresh()->activo);

        $this->escuelita->update(['activo' => true]);
        $this->patchJson(route('web.grupos.toggle-activo', $this->grupo->id))->assertOk()->assertExactJson(['activo' => true]);
        $this->assertTrue($this->grupo->fresh()->activo);
    }

    public function test_un_nivel_con_grupos_sigue_sin_poder_eliminarse(): void
    {
        $this->grupo->update(['activo' => false]);
        $this->escuelita->update(['activo' => false]);

        $this->from(route('web.niveles.index'))->delete(route('web.niveles.destroy', $this->escuelita->id))
            ->assertSessionHas('error', 'No se puede eliminar: hay 1 grupo(s) que usan este nivel.');
        $this->assertNotNull($this->escuelita->fresh());
    }

    public function test_la_pantalla_de_niveles_muestra_el_interruptor_y_el_estado(): void
    {
        $this->competencia->update(['activo' => false]);

        $this->get(route('web.niveles.index'))->assertOk()
            ->assertSee(route('web.niveles.toggle-activo', $this->escuelita->id))
            ->assertSee('alumno-dot--success', false)
            ->assertSee('alumno-dot--neutral', false);
    }
}
